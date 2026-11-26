<?php
if (!defined('ABSPATH')) exit;

/**
 * À l'activation du thème : crée les rubriques, villes, pages, et importe le contenu de départ
 * (une seule fois). Le contenu se gère ensuite entièrement dans l'administration WordPress.
 */
add_action('after_switch_theme', 'pe_setup_site');

function pe_setup_site() {
  if (!get_option('permalink_structure')) update_option('permalink_structure', '/%postname%/');
  pe_register_for_import();

  if (!get_option('pe_imported')) {
    $data = json_decode((string)file_get_contents(PE_DIR . '/data/content.json'), true);
    if (is_array($data)) pe_import($data);
    update_option('pe_imported', PE_VER);
  }

  foreach (['favoris' => ['Favoris', 'page-favoris.php'], 'a-propos' => ['À propos', '']] as $slug => [$title, $tpl]) {
    if (get_page_by_path($slug)) continue;
    $content = $slug === 'a-propos'
      ? "<p>Publitarium Events est le magazine mobile des sorties au Maroc : actualité culturelle, agenda, festivals, rendez-vous à réserver et bonnes adresses, de Casablanca à Essaouira.</p>\n<p>Chaque article est accompagné d’un résumé vocal lu par Oulbig.</p>\n<p>Les dates des événements sont communiquées à titre indicatif : vérifiez-les auprès des organisateurs avant de vous déplacer.</p>"
      : '';
    $id = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_content' => $content]);
    if ($tpl && $id) update_post_meta($id, '_wp_page_template', $tpl);
  }
  update_option('blogdescription', 'Actualité, agenda, festivals et bonnes adresses au Maroc');
  flush_rewrite_rules();
}

/** Les types et taxonomies doivent exister pendant l'import (le hook init est déjà passé). */
function pe_register_for_import() {
  pe_register_types();
}

function pe_term($name, $tax, $slug = '') {
  $t = $slug ? get_term_by('slug', $slug, $tax) : get_term_by('name', $name, $tax);
  if ($t) return (int)$t->term_id;
  $r = wp_insert_term($name, $tax, $slug ? ['slug' => $slug] : []);
  return is_wp_error($r) ? 0 : (int)$r['term_id'];
}

function pe_import(array $d) {
  foreach ($d['categories'] ?? [] as $c) {
    $id = pe_term($c['name'], 'pe_rubrique', $c['slug']);
    if ($id) { update_term_meta($id, 'pe_color', $c['color']); update_term_meta($id, 'pe_icon', $c['icon']); }
  }
  foreach (array_merge($d['cities'] ?? [], ['National']) as $city) pe_term($city, 'pe_city');

  $set_terms = function ($id, $item) {
    if (!empty($item['category'])) wp_set_object_terms($id, [$item['category']], 'pe_rubrique');
    if (!empty($item['city'])) wp_set_object_terms($id, [pe_term($item['city'], 'pe_city')], 'pe_city');
  };

  foreach ($d['articles'] ?? [] as $a) {
    $content = implode("\n\n", array_map(fn($p) => '<!-- wp:paragraph --><p>' . esc_html($p) . '</p><!-- /wp:paragraph -->', $a['content'] ?? []));
    $id = wp_insert_post([
      'post_type' => 'post', 'post_status' => 'publish', 'post_title' => $a['title'], 'post_name' => $a['id'],
      'post_excerpt' => $a['excerpt'] ?? '', 'post_content' => $content, 'post_date' => ($a['date'] ?? date('Y-m-d')) . ' 09:00:00',
    ]);
    if (!$id || is_wp_error($id)) continue;
    $set_terms($id, $a);
    if (!empty($a['tags'])) wp_set_post_tags($id, $a['tags']);
    update_post_meta($id, '_pe_voice', $a['voiceSummary'] ?? '');
    update_post_meta($id, '_pe_read_time', (string)($a['readTime'] ?? ''));
    if (!empty($a['featured'])) update_post_meta($id, '_pe_featured', '1');
  }

  foreach ($d['events'] ?? [] as $e) {
    $id = wp_insert_post([
      'post_type' => 'pe_event', 'post_status' => 'publish', 'post_title' => $e['title'], 'post_name' => $e['id'],
      'post_excerpt' => $e['description'] ?? '', 'post_content' => '<!-- wp:paragraph --><p>' . esc_html($e['description'] ?? '') . '</p><!-- /wp:paragraph -->',
    ]);
    if (!$id || is_wp_error($id)) continue;
    $set_terms($id, $e);
    $map = ['startDate' => 'start', 'endDate' => 'end', 'recurring' => 'recurring', 'dateNote' => 'date_note', 'venue' => 'venue', 'price' => 'price', 'url' => 'url'];
    foreach ($map as $from => $to) if (!empty($e[$from])) update_post_meta($id, '_pe_' . $to, $e[$from]);
    if (!empty($e['slots'])) update_post_meta($id, '_pe_slots', implode(', ', $e['slots']));
    if (!empty($e['featured'])) update_post_meta($id, '_pe_featured', '1');
    pe_update_until($id);
  }

  foreach ($d['places'] ?? [] as $p) {
    $id = wp_insert_post([
      'post_type' => 'pe_place', 'post_status' => 'publish', 'post_title' => $p['name'], 'post_name' => $p['id'],
      'post_excerpt' => $p['description'] ?? '', 'post_content' => '<!-- wp:paragraph --><p>' . esc_html($p['description'] ?? '') . '</p><!-- /wp:paragraph -->',
    ]);
    if (!$id || is_wp_error($id)) continue;
    $set_terms($id, $p);
    foreach (['type' => 'type', 'area' => 'area', 'price' => 'price', 'mustTry' => 'must_try', 'address' => 'address'] as $from => $to) {
      if (!empty($p[$from])) update_post_meta($id, '_pe_' . $to, $p[$from]);
    }
  }

  // L'article d'exemple de WordPress n'a pas sa place ici.
  $hello = get_page_by_path('bonjour-tout-le-monde', OBJECT, 'post') ?: get_page_by_path('hello-world', OBJECT, 'post');
  if ($hello) wp_trash_post($hello->ID);
}
