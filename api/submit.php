<?php
// Publitarium Events — enregistre les demandes de rendez-vous, inscriptions newsletter et messages.
// Les données sont écrites dans ../storage (protégé par .htaccess), une ligne JSON par envoi.
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function out($code, $data) { http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, ['ok' => false, 'error' => 'Méthode non autorisée']);
$raw = file_get_contents('php://input', false, null, 0, 20000);
$in = json_decode($raw, true);
if (!is_array($in)) out(400, ['ok' => false, 'error' => 'Requête invalide']);
if (!empty($in['website'])) out(200, ['ok' => true, 'reference' => 'OK']); // pot de miel anti-robots

$type = $in['type'] ?? '';
if (!in_array($type, ['rendezvous', 'newsletter', 'contact'], true)) out(400, ['ok' => false, 'error' => 'Type inconnu']);

$clean = function ($k, $max = 300) use ($in) {
  $v = isset($in[$k]) ? trim((string)$in[$k]) : '';
  $v = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $v);
  return mb_substr($v, 0, $max);
};
$rec = ['type' => $type, 'at' => gmdate('c'), 'ip_hash' => substr(hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . 'publitarium'), 0, 16)];
$email = $clean('email', 200);
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) out(422, ['ok' => false, 'error' => 'Email invalide']);

if ($type === 'newsletter') {
  if ($email === '') out(422, ['ok' => false, 'error' => 'Email requis']);
  $rec += ['email' => $email, 'city' => $clean('city', 40)];
} elseif ($type === 'contact') {
  $rec += ['name' => $clean('name', 120), 'email' => $email, 'message' => $clean('notes', 3000)];
  if ($rec['name'] === '' || $email === '' || $rec['message'] === '') out(422, ['ok' => false, 'error' => 'Champs requis manquants']);
} else {
  $rec += [
    'event' => $clean('event', 200), 'name' => $clean('name', 120), 'phone' => $clean('phone', 40), 'email' => $email,
    'date' => $clean('date', 10), 'slot' => $clean('slot', 10), 'guests' => max(1, min(50, (int)($in['guests'] ?? 1))), 'notes' => $clean('notes', 1500),
  ];
  if ($rec['name'] === '' || $rec['phone'] === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $rec['date'])) out(422, ['ok' => false, 'error' => 'Nom, téléphone et date requis']);
  $rec['reference'] = 'PE-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}

$dir = __DIR__ . '/../storage';
if (!is_dir($dir)) @mkdir($dir, 0750, true);
$file = $dir . '/' . $type . '-' . gmdate('Y-m') . '.jsonl';
if (@file_put_contents($file, json_encode($rec, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX) === false) {
  out(500, ['ok' => false, 'error' => 'Enregistrement impossible, réessayez plus tard']);
}
out(200, ['ok' => true, 'reference' => $rec['reference'] ?? 'OK']);
