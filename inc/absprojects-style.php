<?php
/**
 * ABSProjects — présentation :
 * - nom du site affiché « ABSProjects » (au lieu du nom de domaine) avec le style de Musicology
 *   (Playfair Display, pastille dégradée) ; titres du site dans la même police pour un ensemble uniforme ;
 * - vraies couvertures générées (SVG) pour les contenus sans image mise en avant :
 *   ebooks (format livre), boutique, design, audiobooks, vidéos — plus d'images cassées.
 * Actif uniquement avec le thème ABSProjects. Aucun fichier du thème n'est modifié.
 */
if (!defined('ABSPATH')) exit;

function oulbig_absp_active() {
    return strpos(get_template(), 'absprojects') === 0;
}

/* ---------- Nom du site ---------- */
add_filter('bloginfo', function ($out, $show) {
    if ($show !== 'name' || !oulbig_absp_active()) return $out;
    // Nom resté sur le domaine (absprojects.almoqayada.com) → nom de marque.
    return preg_match('~^[a-z0-9-]+(\.[a-z0-9-]+)+$~i', trim(wp_strip_all_tags($out))) ? 'ABSProjects' : $out;
}, 20, 2);

/* ---------- Typographie & en-tête façon Musicology ---------- */
add_action('wp_enqueue_scripts', function () {
    if (!oulbig_absp_active()) return;
    wp_enqueue_style('oulbig-absp-fonts', 'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;0,800;1,600;1,700&family=Inter:wght@400;500;600;700&display=swap', [], null);
    $css = <<<'CSS'
:root{--font-display:"Playfair Display","Tajawal",Georgia,serif;--font-body:"Inter","Manrope","Tajawal",system-ui,sans-serif}
h1,h2,h3,h4,h5,h6{letter-spacing:-.015em;line-height:1.08}
.abs-hero h1{letter-spacing:-.025em;line-height:1}
.abs-brand{gap:12px;font-family:"Playfair Display",Georgia,serif;font-weight:700;font-size:25px;letter-spacing:-.01em;white-space:nowrap}
.abs-brand__mark{width:42px;height:42px;border-radius:13px;font-family:"Inter",system-ui,sans-serif;font-weight:800;font-size:.95rem;letter-spacing:.02em;color:#0c0c10;box-shadow:0 10px 30px -10px rgba(230,180,80,.6)}
.abs-header__inner{gap:18px}
.abs-nav a{padding:.6em .8em}
.abs-header__cta .abs-btn{white-space:nowrap}
.abs-ebook h3,.abs-media-card__body h3,.abs-shop-card h3,.abs-brand-card h3{font-weight:700}
.abs-ebook h3{font-size:1.15rem;line-height:1.25}
.abs-ebook__cover{background:linear-gradient(150deg,#1f1f28,#0c0c10)}
.abs-ebook__cover img,.abs-media-card__thumb img,.abs-shop-card__img img{color:transparent}
@media (max-width:1240px){.abs-nav a{padding:.55em .6em;letter-spacing:.06em}}
@media (max-width:1100px){.abs-header__cta .abs-btn{display:none}}
@media (max-width:760px){.abs-brand{font-size:21px}.abs-brand__mark{width:36px;height:36px;border-radius:11px}}
CSS;
    wp_register_style('oulbig-absp', false, [], OULBIG_SYNC_VER);
    wp_enqueue_style('oulbig-absp');
    wp_add_inline_style('oulbig-absp', $css);
}, 30);

/* ---------- Couvertures générées ---------- */
function oulbig_cover_types() {
    return ['abs_ebook', 'abs_shop', 'abs_artwork', 'abs_audiobook', 'abs_video'];
}

function oulbig_cover_url($post_id) {
    $p = get_post($post_id);
    if (!$p) return '';
    return add_query_arg(['abs_cover' => $post_id, 'v' => substr(md5($p->post_title . $p->post_modified), 0, 8)], home_url('/'));
}

// Le thème appelle has_post_thumbnail() puis get_the_post_thumbnail_url() : on fournit la couverture générée.
add_filter('has_post_thumbnail', function ($has, $post) {
    if ($has || is_admin() || !oulbig_absp_active()) return $has;
    $p = get_post($post);
    return ($p && in_array($p->post_type, oulbig_cover_types(), true)) ? true : $has;
}, 10, 2);
add_filter('post_thumbnail_url', function ($url, $post) {
    if ($url || is_admin() || !oulbig_absp_active()) return $url;
    $p = get_post($post);
    return ($p && in_array($p->post_type, oulbig_cover_types(), true)) ? oulbig_cover_url($p->ID) : $url;
}, 10, 2);
// Images de remplacement du thème en data:URI (supprimées par esc_url) : on les laisse passer.
add_filter('clean_url', function ($good, $orig) {
    return ($good === '' && is_string($orig) && strpos($orig, 'data:image/svg+xml') === 0) ? $orig : $good;
}, 10, 2);

function oulbig_cover_lines($text, $max) {
    $words = preg_split('/[ \t\n]+/', trim($text));
    $lines = [];
    $cur = '';
    foreach ($words as $w) {
        $try = $cur === '' ? $w : $cur . ' ' . $w;
        if (mb_strlen($try) > $max && $cur !== '') { $lines[] = $cur; $cur = $w; } else { $cur = $try; }
    }
    if ($cur !== '') $lines[] = $cur;
    return $lines;
}

function oulbig_cover_svg($p) {
    $palettes = [
        ['#2a1d0e', '#0c0c10', '#e6b450'], ['#2b1416', '#0c0c10', '#ff6a4d'], ['#10232a', '#0c0c10', '#4fc3b0'],
        ['#1d1430', '#0c0c10', '#b48cff'], ['#13202e', '#0c0c10', '#6aa8ff'], ['#2a2410', '#0c0c10', '#f0d27a'],
    ];
    $pal = $palettes[abs(crc32($p->post_title)) % count($palettes)];
    $portrait = in_array($p->post_type, ['abs_ebook', 'abs_artwork'], true);
    [$w, $h] = $portrait ? [600, 800] : [800, 500];
    $labels = ['abs_ebook' => 'EBOOK', 'abs_shop' => 'BOUTIQUE', 'abs_artwork' => 'DESIGN', 'abs_audiobook' => 'AUDIO', 'abs_video' => 'VIDÉO'];
    $label = $labels[$p->post_type] ?? '';
    $author = trim((string) get_post_meta($p->ID, 'abs_author', true)) ?: 'OULBIG';
    $title = wp_strip_all_tags($p->post_title);
    // Titre « Nom — sous-titre » : le sous-titre passe en plus petit.
    $sub = '';
    if (preg_match('/^(.+?)\s+[—–-]\s+(.+)$/u', $title, $m)) { $title = $m[1]; $sub = $m[2]; }
    elseif (preg_match('/^(.+?)\s*:\s+(.+)$/u', $title, $m)) { $title = $m[1]; $sub = $m[2]; }
    // Guillemets français insécables (« titre » ne se coupe pas).
    $title = str_replace(['« ', ' »'], ["«\u{00A0}", "\u{00A0}»"], $title);
    $rtl = (bool) preg_match('/\p{Arabic}/u', $title . $sub);
    $max = $portrait ? 13 : 20;
    $lines = array_slice(oulbig_cover_lines($title, $max), 0, 4);
    $fs = $portrait ? (count($lines) > 2 ? 54 : 66) : (count($lines) > 2 ? 46 : 56);
    $sublines = $sub ? array_slice(oulbig_cover_lines($sub, $portrait ? 24 : 34), 0, 3) : [];
    $e = function ($s) { return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8'); };
    $cx = $w / 2;
    $y0 = $portrait ? 330 - (count($lines) - 1) * $fs * 0.55 : 230 - (count($lines) - 1) * $fs * 0.55;
    $dir = $rtl ? ' direction="rtl"' : '';
    $t = '';
    foreach ($lines as $i => $l) $t .= '<text x="' . $cx . '" y="' . round($y0 + $i * $fs * 1.12) . '" text-anchor="middle"' . $dir . ' font-family="Playfair Display, Georgia, \'Times New Roman\', serif" font-weight="700" font-size="' . $fs . '" fill="#f3eee6">' . $e($l) . '</text>';
    $ys = $y0 + count($lines) * $fs * 1.12 + 26;
    foreach ($sublines as $i => $l) $t .= '<text x="' . $cx . '" y="' . round($ys + $i * 38) . '" text-anchor="middle"' . $dir . ' font-family="Georgia, serif"  font-style="italic" font-size="' . ($portrait ? 30 : 26) . '" fill="' . $pal[2] . '">' . $e($l) . '</text>';
    $frame = $portrait ? '<rect x="26" y="26" width="' . ($w - 52) . '" height="' . ($h - 52) . '" rx="6" fill="none" stroke="' . $pal[2] . '" stroke-opacity=".45" stroke-width="1.5"/><rect x="0" y="0" width="14" height="' . $h . '" fill="#000" fill-opacity=".35"/>' : '';
    $foot = $portrait
        ? '<line x1="' . ($cx - 40) . '" y1="' . ($h - 128) . '" x2="' . ($cx + 40) . '" y2="' . ($h - 128) . '" stroke="' . $pal[2] . '" stroke-width="2"/><text x="' . $cx . '" y="' . ($h - 80) . '" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-size="22" letter-spacing="6" font-weight="700" fill="' . $pal[2] . '">' . $e(mb_strtoupper($author)) . '</text>'
        : '<text x="' . $cx . '" y="' . ($h - 52) . '" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-size="18" letter-spacing="6" font-weight="700" fill="' . $pal[2] . '">' . $e(mb_strtoupper($author)) . '</text>';
    return '<svg xmlns="http://www.w3.org/2000/svg" overflow="hidden" viewBox="0 0 ' . $w . ' ' . $h . '" width="' . $w . '" height="' . $h . '"><clipPath id="c"><rect width="' . $w . '" height="' . $h . '"/></clipPath><g clip-path="url(#c)">'
        . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="' . $pal[0] . '"/><stop offset="1" stop-color="' . $pal[1] . '"/></linearGradient>'
        . '<radialGradient id="r" cx=".85" cy=".1" r=".7"><stop offset="0" stop-color="' . $pal[2] . '" stop-opacity=".35"/><stop offset="1" stop-color="' . $pal[2] . '" stop-opacity="0"/></radialGradient></defs>'
        . '<rect width="' . $w . '" height="' . $h . '" fill="url(#g)"/><rect width="' . $w . '" height="' . $h . '" fill="url(#r)"/>'
        . '<circle cx="' . ($w * 0.86) . '" cy="' . ($h * 0.12) . '" r="' . ($portrait ? 150 : 120) . '" fill="none" stroke="' . $pal[2] . '" stroke-opacity=".18" stroke-width="1.5"/>'
        . '<circle cx="' . ($w * 0.86) . '" cy="' . ($h * 0.12) . '" r="' . ($portrait ? 95 : 75) . '" fill="none" stroke="' . $pal[2] . '" stroke-opacity=".12" stroke-width="1.5"/>'
        . $frame
        . '<text x="' . $cx . '" y="' . ($portrait ? 96 : 70) . '" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-size="' . ($portrait ? 18 : 16) . '" letter-spacing="8" font-weight="700" fill="' . $pal[2] . '" fill-opacity=".9">' . $e($label) . '</text>'
        . $t . $foot . '</g></svg>';
}

add_action('init', function () {
    if (empty($_GET['abs_cover'])) return;
    $p = get_post(absint($_GET['abs_cover']));
    if (!$p || $p->post_status !== 'publish' || !in_array($p->post_type, oulbig_cover_types(), true)) { status_header(404); exit; }
    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Cache-Control: public, max-age=604800');
    header('X-Content-Type-Options: nosniff');
    echo oulbig_cover_svg($p); // phpcs:ignore
    exit;
}, 1);

/* Texte d'accroche des ebooks : vraies boutiques. */
add_filter('gettext', function ($t, $orig, $domain) {
    if ($domain === 'absprojects' && $orig === 'Disponibles sur Educaplat, Amazon, Kobo, Gumroad et plus.') {
        return 'Disponibles sur Gumroad, Books2Read (Apple Books, Kobo, Google Play…) et Educaplat — en français, anglais, espagnol, arabe et darija.';
    }
    return $t;
}, 10, 3);
