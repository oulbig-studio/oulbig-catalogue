<?php
// Publitarium Events — API du back-office.
// Contenu édité : storage/content.json (jamais écrasé par un déploiement Git).
// Mot de passe : storage/admin.json (hash bcrypt). Fichiers envoyés : uploads/.

declare(strict_types=1);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

const ROOT = __DIR__ . '/..';
const STORAGE = ROOT . '/storage';
const UPLOADS = ROOT . '/uploads';
const MAX_UPLOAD = 15 * 1024 * 1024;

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_name('pe_admin');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict']);
session_start();

function out(int $code, array $data): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}
function fail(int $code, string $msg): void { out($code, ['ok' => false, 'error' => $msg]); }
function readJson(string $f, $default) { if (!is_file($f)) return $default; $d = json_decode((string)file_get_contents($f), true); return $d ?? $default; }
function writeJson(string $f, $data): void {
  if (!is_dir(dirname($f))) mkdir(dirname($f), 0750, true);
  $tmp = $f . '.tmp' . bin2hex(random_bytes(3));
  if (file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX) === false || !rename($tmp, $f)) {
    @unlink($tmp);
    fail(500, "Impossible d'enregistrer. Vérifiez les droits du dossier storage.");
  }
}
function content(): array {
  $c = readJson(STORAGE . '/content.json', null);
  return is_array($c) ? $c : readJson(ROOT . '/data/content.json', []);
}
function str($v, int $max = 500): string {
  $v = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string)($v ?? '')));
  return mb_substr($v, 0, $max);
}
function slugify(string $s): string {
  $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
  $s = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $s));
  return trim(substr($s, 0, 60), '-') ?: 'element';
}
function dateOrEmpty($v): string { $v = str($v, 10); return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : ''; }
function urlOrEmpty($v): string {
  $v = str($v, 500);
  if ($v === '') return '';
  if (preg_match('#^uploads/[A-Za-z0-9._-]+$#', $v)) return $v;
  return preg_match('#^https?://[^\s"\'<>()]+$#i', $v) ? $v : '';
}
function listOf($v, int $max = 40, int $len = 60): array {
  if (is_string($v)) $v = preg_split('/\s*,\s*/', $v);
  if (!is_array($v)) return [];
  return array_values(array_slice(array_filter(array_map(fn($x) => str($x, $len), $v), 'strlen'), 0, $max));
}
function color($v, string $def): string { $v = str($v, 7); return preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? strtolower($v) : $def; }
function ipKey(): string { return substr(hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . 'pe'), 0, 16); }

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$in = [];
if ($method === 'POST' && str_starts_with((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
  $in = json_decode((string)file_get_contents('php://input', false, null, 0, 2_000_000), true) ?: [];
}
$adminFile = STORAGE . '/admin.json';
$admin = readJson($adminFile, null);
$logged = !empty($_SESSION['pe_admin']) && is_array($admin) && ($_SESSION['pe_pw'] ?? '') === substr($admin['hash'] ?? '', -12);

// --- Actions publiques ----------------------------------------------------
if ($action === 'state') {
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
  out(200, ['ok' => true, 'setup' => !is_array($admin), 'logged' => $logged, 'csrf' => $_SESSION['csrf']]);
}

// Toute écriture exige le jeton CSRF de la session.
if ($method === 'POST' && (!hash_equals($_SESSION['csrf'] ?? 'x', (string)($_SERVER['HTTP_X_CSRF'] ?? '')))) fail(403, 'Session expirée, rechargez la page.');

if ($action === 'setup' && $method === 'POST') {
  if (is_array($admin)) fail(409, 'Le mot de passe est déjà défini.');
  $pw = (string)($in['password'] ?? '');
  if (mb_strlen($pw) < 10) fail(422, 'Choisissez un mot de passe d\'au moins 10 caractères.');
  writeJson($adminFile, ['hash' => password_hash($pw, PASSWORD_DEFAULT), 'created' => gmdate('c')]);
  $admin = readJson($adminFile, null);
  session_regenerate_id(true);
  $_SESSION['pe_admin'] = true; $_SESSION['pe_pw'] = substr($admin['hash'], -12);
  out(200, ['ok' => true]);
}

if ($action === 'login' && $method === 'POST') {
  if (!is_array($admin)) fail(409, 'Aucun mot de passe défini.');
  $attFile = STORAGE . '/login-attempts.json';
  $att = readJson($attFile, []);
  $k = ipKey(); $now = time();
  $att = array_filter($att, fn($a) => $now - ($a['t'] ?? 0) < 900);
  if (($att[$k]['n'] ?? 0) >= 8) fail(429, 'Trop de tentatives. Réessayez dans 15 minutes.');
  usleep(400000);
  if (!password_verify((string)($in['password'] ?? ''), $admin['hash'])) {
    $att[$k] = ['n' => ($att[$k]['n'] ?? 0) + 1, 't' => $now];
    writeJson($attFile, $att);
    fail(401, 'Mot de passe incorrect.');
  }
  unset($att[$k]); writeJson($attFile, $att);
  session_regenerate_id(true);
  $_SESSION['pe_admin'] = true; $_SESSION['pe_pw'] = substr($admin['hash'], -12);
  out(200, ['ok' => true]);
}

// --- Actions réservées à l'administrateur -----------------------------------
if (!$logged) fail(401, 'Connexion requise.');

switch ($action) {
  case 'logout':
    $_SESSION = []; session_destroy();
    out(200, ['ok' => true]);

  case 'password':
    if (!password_verify((string)($in['current'] ?? ''), $admin['hash'])) fail(401, 'Mot de passe actuel incorrect.');
    if (mb_strlen((string)($in['password'] ?? '')) < 10) fail(422, 'Au moins 10 caractères.');
    $admin['hash'] = password_hash((string)$in['password'], PASSWORD_DEFAULT);
    writeJson($adminFile, $admin);
    $_SESSION['pe_pw'] = substr($admin['hash'], -12);
    out(200, ['ok' => true]);

  case 'content':
    out(200, ['ok' => true, 'content' => content()]);

  case 'save': {
    $col = $in['collection'] ?? '';
    if (!in_array($col, ['events', 'articles', 'places'], true)) fail(400, 'Collection inconnue.');
    $c = content();
    $cats = array_column($c['categories'] ?? [], 'slug');
    $i = is_array($in['item'] ?? null) ? $in['item'] : [];
    $cat = in_array($i['category'] ?? '', $cats, true) ? $i['category'] : ($cats[0] ?? '');
    $cityIn = str($i['city'] ?? '', 40);
    if ($col === 'events') {
      $item = [
        'title' => str($i['title'] ?? '', 160), 'category' => $cat, 'city' => $cityIn,
        'startDate' => dateOrEmpty($i['startDate'] ?? ''), 'endDate' => dateOrEmpty($i['endDate'] ?? ''),
        'recurring' => str($i['recurring'] ?? '', 120), 'dateNote' => str($i['dateNote'] ?? '', 160),
        'venue' => str($i['venue'] ?? '', 200), 'price' => str($i['price'] ?? '', 120),
        'slots' => listOf($i['slots'] ?? [], 12, 10), 'description' => str($i['description'] ?? '', 3000),
        'url' => urlOrEmpty($i['url'] ?? ''), 'image' => urlOrEmpty($i['image'] ?? ''), 'featured' => !empty($i['featured']),
      ];
      if ($item['title'] === '' || $item['startDate'] === '' || $item['venue'] === '') fail(422, 'Titre, date de début et lieu sont obligatoires.');
    } elseif ($col === 'articles') {
      $paras = is_array($i['content'] ?? null) ? $i['content'] : preg_split('/\R\s*\R/', (string)($i['content'] ?? ''));
      $item = [
        'title' => str($i['title'] ?? '', 200), 'category' => $cat, 'city' => $cityIn,
        'date' => dateOrEmpty($i['date'] ?? '') ?: gmdate('Y-m-d'), 'author' => str($i['author'] ?? '', 80) ?: 'Rédaction Publitarium',
        'readTime' => max(1, min(60, (int)($i['readTime'] ?? 3))), 'featured' => !empty($i['featured']),
        'excerpt' => str($i['excerpt'] ?? '', 600), 'voiceSummary' => str($i['voiceSummary'] ?? '', 2500),
        'audioUrl' => urlOrEmpty($i['audioUrl'] ?? ''), 'image' => urlOrEmpty($i['image'] ?? ''),
        'content' => array_values(array_filter(array_map(fn($p) => str($p, 6000), $paras), 'strlen')),
        'tags' => listOf($i['tags'] ?? [], 12, 40),
      ];
      if ($item['title'] === '' || $item['excerpt'] === '') fail(422, 'Titre et chapô sont obligatoires.');
    } else {
      $item = [
        'name' => str($i['name'] ?? '', 160), 'category' => $cat, 'type' => str($i['type'] ?? '', 60), 'city' => $cityIn,
        'area' => str($i['area'] ?? '', 80), 'price' => in_array($i['price'] ?? '', ['€', '€€', '€€€'], true) ? $i['price'] : '€€',
        'mustTry' => str($i['mustTry'] ?? '', 200), 'description' => str($i['description'] ?? '', 2000),
        'address' => str($i['address'] ?? '', 200), 'image' => urlOrEmpty($i['image'] ?? ''),
      ];
      if ($item['name'] === '') fail(422, 'Le nom est obligatoire.');
    }
    $item = array_filter($item, fn($v) => $v !== '' && $v !== [] && $v !== false);
    $list = $c[$col] ?? [];
    $id = str($i['id'] ?? '', 80);
    $idx = $id !== '' ? array_search($id, array_column($list, 'id'), true) : false;
    if ($idx === false) {
      $base = slugify($item['title'] ?? $item['name']); $id = $base; $n = 2;
      $ids = array_column($list, 'id');
      while (in_array($id, $ids, true)) $id = $base . '-' . $n++;
      array_unshift($list, ['id' => $id] + $item);
    } else {
      $list[$idx] = ['id' => $id] + $item;
    }
    $c[$col] = array_values($list);
    writeJson(STORAGE . '/content.json', $c);
    out(200, ['ok' => true, 'id' => $id]);
  }

  case 'delete': {
    $col = $in['collection'] ?? '';
    if (!in_array($col, ['events', 'articles', 'places'], true)) fail(400, 'Collection inconnue.');
    $c = content();
    $c[$col] = array_values(array_filter($c[$col] ?? [], fn($x) => ($x['id'] ?? '') !== ($in['id'] ?? '')));
    writeJson(STORAGE . '/content.json', $c);
    out(200, ['ok' => true]);
  }

  case 'settings': {
    $c = content();
    $s = is_array($in['settings'] ?? null) ? $in['settings'] : [];
    $c['settings'] = [
      'accent' => color($s['accent'] ?? '', '#ff4d6d'),
      'accent2' => color($s['accent2'] ?? '', '#3a56d4'),
      'accent3' => color($s['accent3'] ?? '', '#f4a261'),
      'tagline' => str($s['tagline'] ?? '', 160),
    ];
    $cities = listOf($in['cities'] ?? [], 20, 40);
    if ($cities) $c['cities'] = $cities;
    if (is_array($in['categoryColors'] ?? null)) {
      foreach ($c['categories'] as &$cat) $cat['color'] = color($in['categoryColors'][$cat['slug']] ?? '', $cat['color']);
      unset($cat);
    }
    writeJson(STORAGE . '/content.json', $c);
    out(200, ['ok' => true]);
  }

  case 'upload': {
    $f = $_FILES['file'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK) fail(400, 'Envoi échoué (taille maximale du serveur dépassée ?).');
    if ($f['size'] > MAX_UPLOAD) fail(413, 'Fichier trop lourd (15 Mo maximum).');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'audio/mpeg' => 'mp3', 'audio/mp3' => 'mp3',
      'audio/mp4' => 'm4a', 'audio/x-m4a' => 'm4a', 'audio/ogg' => 'ogg', 'audio/wav' => 'wav', 'audio/x-wav' => 'wav', 'audio/aac' => 'aac'];
    if (!isset($allowed[$mime])) fail(415, 'Format non accepté (JPG, PNG, WebP, MP3, M4A, OGG, WAV).');
    if (!is_dir(UPLOADS)) mkdir(UPLOADS, 0755, true);
    $name = gmdate('Ymd') . '-' . slugify(pathinfo((string)$f['name'], PATHINFO_FILENAME)) . '-' . bin2hex(random_bytes(3)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($f['tmp_name'], UPLOADS . '/' . $name)) fail(500, 'Impossible d\'enregistrer le fichier.');
    @chmod(UPLOADS . '/' . $name, 0644);
    out(200, ['ok' => true, 'url' => 'uploads/' . $name]);
  }

  case 'submissions': {
    $type = $_GET['type'] ?? 'rendezvous';
    if (!in_array($type, ['rendezvous', 'newsletter', 'contact'], true)) fail(400, 'Type inconnu.');
    $rows = [];
    foreach (glob(STORAGE . '/' . $type . '-*.jsonl') ?: [] as $file) {
      foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $r = json_decode($line, true); if (is_array($r)) { unset($r['ip_hash']); $rows[] = $r; }
      }
    }
    usort($rows, fn($a, $b) => strcmp($b['at'] ?? '', $a['at'] ?? ''));
    if (($_GET['format'] ?? '') === 'csv') {
      header('Content-Type: text/csv; charset=utf-8');
      header('Content-Disposition: attachment; filename="publitarium-' . $type . '-' . gmdate('Y-m-d') . '.csv"');
      $o = fopen('php://output', 'w');
      fwrite($o, "\xEF\xBB\xBF");
      $cols = [];
      foreach ($rows as $r) $cols = array_unique(array_merge($cols, array_keys($r)));
      fputcsv($o, $cols, ';');
      foreach ($rows as $r) fputcsv($o, array_map(fn($k) => preg_replace('/^[=+\-@]/', "'$0", (string)($r[$k] ?? '')), $cols), ';');
      exit;
    }
    out(200, ['ok' => true, 'rows' => $rows]);
  }

  case 'reset':
    if (($in['confirm'] ?? '') !== 'REINITIALISER') fail(422, 'Confirmation manquante.');
    @unlink(STORAGE . '/content.json');
    out(200, ['ok' => true]);
}
fail(404, 'Action inconnue.');
