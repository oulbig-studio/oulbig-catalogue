<?php
if (!defined('ABSPATH')) exit;

function pe_palettes() {
  return [
    'majorelle' => ['Majorelle (défaut)', '#2f4bd8', '#1b998b', '#f2c14e'],
    'medina' => ['Medina Pop', '#ff4d6d', '#3a56d4', '#f4a261'],
    'terracotta' => ['Terracotta', '#c8553d', '#2d6a4f', '#f2a65a'],
    'atlantique' => ['Atlantique', '#0077b6', '#00b4d8', '#ffb703'],
    'safran' => ['Safran nuit', '#e09f3e', '#540b0e', '#9e2a2b'],
    'menthe' => ['Menthe fraîche', '#2a9d8f', '#264653', '#e9c46a'],
    'custom' => ['Personnalisée (couleurs ci-dessous)', '', '', ''],
  ];
}

/** Couleur active : accent, accent2 ou accent3. */
function pe_color($which) {
  $p = get_theme_mod('pe_palette', 'majorelle');
  $all = pe_palettes();
  $idx = ['accent' => 1, 'accent2' => 2, 'accent3' => 3][$which] ?? 1;
  if ($p !== 'custom' && isset($all[$p])) return $all[$p][$idx];
  return get_theme_mod('pe_' . $which, $all['majorelle'][$idx]);
}

add_action('customize_register', function ($wp) {
  $wp->add_section('pe_colors', ['title' => 'Couleurs Publitarium', 'priority' => 30]);
  $wp->add_setting('pe_palette', ['default' => 'majorelle', 'sanitize_callback' => fn($v) => array_key_exists($v, pe_palettes()) ? $v : 'majorelle']);
  $wp->add_control('pe_palette', ['section' => 'pe_colors', 'label' => 'Palette', 'type' => 'radio', 'choices' => array_map(fn($p) => $p[0], pe_palettes())]);
  foreach (['accent' => 'Couleur principale', 'accent2' => 'Couleur secondaire', 'accent3' => 'Couleur d’accent'] as $k => $label) {
    $wp->add_setting('pe_' . $k, ['default' => pe_palettes()['majorelle'][['accent' => 1, 'accent2' => 2, 'accent3' => 3][$k]], 'sanitize_callback' => 'sanitize_hex_color']);
    $wp->add_control(new WP_Customize_Color_Control($wp, 'pe_' . $k, ['section' => 'pe_colors', 'label' => $label . ' (palette personnalisée)']));
  }
  $wp->add_setting('pe_dark_default', ['default' => false, 'sanitize_callback' => fn($v) => (bool)$v]);
  $wp->add_control('pe_dark_default', ['section' => 'pe_colors', 'label' => 'Mode sombre par défaut', 'type' => 'checkbox']);
  $wp->add_setting('pe_tagline', ['default' => 'L’actualité culturelle, les rendez-vous, les festivals et les bonnes adresses du Maroc. Résumés vocaux par Oulbig.', 'sanitize_callback' => 'sanitize_text_field']);
  $wp->add_control('pe_tagline', ['section' => 'pe_colors', 'label' => 'Phrase du pied de page', 'type' => 'text']);
});

add_action('wp_head', function () {
  printf("<style id=\"pe-colors\">:root{--accent:%s;--accent-2:%s;--accent-3:%s}</style>\n", esc_html(pe_color('accent')), esc_html(pe_color('accent2')), esc_html(pe_color('accent3')));
}, 20);
