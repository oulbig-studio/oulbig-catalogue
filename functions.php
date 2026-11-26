<?php
/**
 * Publitarium Events — thème WordPress.
 */
if (!defined('ABSPATH')) exit;

define('PE_VER', '1.0.0');
define('PE_DIR', get_template_directory());
define('PE_URI', get_template_directory_uri());

require PE_DIR . '/inc/helpers.php';
require PE_DIR . '/inc/cpt.php';
require PE_DIR . '/inc/meta.php';
require PE_DIR . '/inc/forms.php';
require PE_DIR . '/inc/customizer.php';
require PE_DIR . '/inc/import.php';

add_action('after_setup_theme', function () {
  load_theme_textdomain('publitarium', PE_DIR . '/languages');
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
  add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
  add_theme_support('responsive-embeds');
  add_theme_support('align-wide');
  add_image_size('pe-card', 800, 520, true);
  add_image_size('pe-hero', 1600, 1000, true);
  register_nav_menus(['footer' => __('Pied de page', 'publitarium')]);
});

add_action('wp_enqueue_scripts', function () {
  wp_enqueue_style('pe-fonts', 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,800&family=Inter:wght@400;500;600;700&display=swap', [], null);
  wp_enqueue_style('pe-style', get_stylesheet_uri(), ['pe-fonts'], PE_VER);
  wp_enqueue_script('pe-theme', PE_URI . '/assets/theme.js', [], PE_VER, true);
  wp_localize_script('pe-theme', 'PE', ['home' => home_url('/')]);
});

add_action('wp_head', function () {
  echo '<meta name="theme-color" content="' . esc_attr(pe_color('accent')) . '">' . "\n";
});

// Recherche : articles, événements et adresses.
add_action('pre_get_posts', function ($q) {
  if (is_admin() || !$q->is_main_query()) return;
  if ($q->is_search()) $q->set('post_type', ['post', 'pe_event', 'pe_place']);
  if ($q->is_post_type_archive('pe_event')) {
    pe_events_query_args($q);
    $q->set('posts_per_page', 60);
    if (!empty($_GET['rubrique'])) {
      $q->set('tax_query', array_merge((array)$q->get('tax_query'), [['taxonomy' => 'pe_rubrique', 'field' => 'slug', 'terms' => sanitize_title(wp_unslash($_GET['rubrique']))]]));
    }
  }
  if ($q->is_post_type_archive('pe_place')) {
    $q->set('posts_per_page', 60); $q->set('orderby', 'title'); $q->set('order', 'ASC');
    if (!empty($_GET['rubrique'])) {
      $q->set('tax_query', array_merge((array)$q->get('tax_query'), [['taxonomy' => 'pe_rubrique', 'field' => 'slug', 'terms' => sanitize_title(wp_unslash($_GET['rubrique']))]]));
    }
  }
  if (($q->is_home() || $q->is_post_type_archive(['pe_event', 'pe_place']) || $q->is_tax('pe_rubrique')) && pe_city()) {
    $q->set('tax_query', array_merge((array)$q->get('tax_query'), [pe_city_tax()]));
  }
});
