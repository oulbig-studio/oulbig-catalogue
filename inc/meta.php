<?php
if (!defined('ABSPATH')) exit;

/** Champs personnalisés par type de contenu. */
function pe_fields($type) {
  $f = [
    'pe_event' => [
      'start' => ['Date de début', 'date', 'Obligatoire'],
      'end' => ['Date de fin', 'date', 'Vide si un seul jour'],
      'recurring' => ['Récurrence', 'text', 'Ex. « Tous les jours » pour une activité à réserver'],
      'date_note' => ['Note sur la date', 'text', 'Ex. « Dates à confirmer »'],
      'venue' => ['Lieu / adresse', 'text', ''],
      'price' => ['Tarif', 'text', ''],
      'slots' => ['Créneaux de rendez-vous', 'text', 'Séparés par des virgules (10:00, 14:00). Rempli = formulaire de réservation affiché'],
      'url' => ['Lien officiel / billetterie', 'url', ''],
      'featured' => ['Mettre en avant', 'check', ''],
    ],
    'pe_place' => [
      'type' => ['Type', 'text', 'Ex. Rooftop, Café, Riad & Spa'],
      'area' => ['Quartier', 'text', ''],
      'price' => ['Budget', 'select', '', ['€', '€€', '€€€']],
      'address' => ['Adresse', 'text', ''],
      'must_try' => ['À ne pas manquer', 'text', ''],
    ],
    'post' => [
      'voice' => ['Résumé vocal d’Oulbig (texte lu)', 'textarea', 'Lu par la voix du navigateur si aucun enregistrement n’est fourni'],
      'audio' => ['Enregistrement audio d’Oulbig (MP3)', 'media', 'Prioritaire sur la lecture automatique'],
      'read_time' => ['Temps de lecture (minutes)', 'number', ''],
      'featured' => ['À la une', 'check', ''],
    ],
  ];
  return $f[$type] ?? [];
}

add_action('add_meta_boxes', function () {
  $titles = ['pe_event' => 'Détails de l’événement', 'pe_place' => 'Détails de l’adresse', 'post' => 'Résumé vocal Oulbig & options'];
  foreach ($titles as $type => $title) {
    add_meta_box('pe_details', $title, 'pe_render_box', $type, 'normal', 'high');
  }
  add_meta_box('pe_booking', 'Détails de la demande', 'pe_render_readonly', ['pe_booking', 'pe_subscriber'], 'normal', 'high');
});

function pe_render_box($post) {
  wp_nonce_field('pe_save', 'pe_nonce');
  echo '<table class="form-table" role="presentation"><tbody>';
  foreach (pe_fields($post->post_type) as $key => $def) {
    [$label, $type, $hint] = $def;
    $val = get_post_meta($post->ID, '_pe_' . $key, true);
    $id = 'pe_' . $key;
    echo '<tr><th scope="row"><label for="' . esc_attr($id) . '">' . esc_html($label) . '</label></th><td>';
    switch ($type) {
      case 'check': echo '<label><input type="checkbox" id="' . $id . '" name="' . $id . '" value="1"' . checked($val, '1', false) . '> Oui</label>'; break;
      case 'textarea': echo '<textarea class="large-text" rows="4" id="' . $id . '" name="' . $id . '">' . esc_textarea($val) . '</textarea>'; break;
      case 'select':
        echo '<select id="' . $id . '" name="' . $id . '">';
        foreach ($def[3] as $o) echo '<option' . selected($val, $o, false) . '>' . esc_html($o) . '</option>';
        echo '</select>'; break;
      case 'media':
        echo '<input type="url" class="regular-text" id="' . $id . '" name="' . $id . '" value="' . esc_attr($val) . '"> <button type="button" class="button pe-media" data-target="' . $id . '">Choisir dans la médiathèque</button>';
        if ($val) echo '<p><audio controls preload="none" src="' . esc_url($val) . '"></audio></p>';
        break;
      default:
        echo '<input type="' . esc_attr($type) . '" class="regular-text" id="' . $id . '" name="' . $id . '" value="' . esc_attr($val) . '"' . ($type === 'number' ? ' min="1" max="60"' : '') . '>';
    }
    if ($hint) echo '<p class="description">' . esc_html($hint) . '</p>';
    echo '</td></tr>';
  }
  echo '</tbody></table>';
}

add_action('admin_enqueue_scripts', function ($hook) {
  if (!in_array($hook, ['post.php', 'post-new.php'], true)) return;
  wp_enqueue_media();
  wp_add_inline_script('jquery-core', "jQuery(function($){ $(document).on('click','.pe-media',function(e){ e.preventDefault(); var t=$('#'+$(this).data('target')); var f=wp.media({title:'Enregistrement audio',library:{type:'audio'},button:{text:'Utiliser ce fichier'},multiple:false}); f.on('select',function(){ t.val(f.state().get('selection').first().toJSON().url); }); f.open(); }); });");
});

add_action('save_post', function ($post_id, $post) {
  if (!isset($_POST['pe_nonce']) || !wp_verify_nonce($_POST['pe_nonce'], 'pe_save')) return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post', $post_id)) return;
  foreach (pe_fields($post->post_type) as $key => $def) {
    $raw = isset($_POST['pe_' . $key]) ? wp_unslash($_POST['pe_' . $key]) : '';
    switch ($def[1]) {
      case 'check': $v = $raw ? '1' : ''; break;
      case 'date': $v = preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) ? $raw : ''; break;
      case 'url': case 'media': $v = esc_url_raw($raw); break;
      case 'number': $v = $raw === '' ? '' : (string)max(1, min(60, (int)$raw)); break;
      case 'textarea': $v = sanitize_textarea_field($raw); break;
      case 'select': $v = in_array($raw, $def[3], true) ? $raw : ''; break;
      default: $v = sanitize_text_field($raw);
    }
    if ($v === '') delete_post_meta($post_id, '_pe_' . $key); else update_post_meta($post_id, '_pe_' . $key, $v);
  }
  if ($post->post_type === 'pe_event') pe_update_until($post_id);
}, 10, 2);

/** Date jusqu'à laquelle l'événement est « à venir » (sert au tri et au filtre). */
function pe_update_until($post_id) {
  $start = get_post_meta($post_id, '_pe_start', true);
  $end = get_post_meta($post_id, '_pe_end', true);
  $until = get_post_meta($post_id, '_pe_recurring', true) ? '9999-12-31' : ($end ?: $start);
  if (!$start) { update_post_meta($post_id, '_pe_start', current_time('Y-m-d')); $until = $until ?: current_time('Y-m-d'); }
  update_post_meta($post_id, '_pe_until', $until ?: '0000-00-00');
}

function pe_render_readonly($post) {
  $rows = get_post_meta($post->ID, '_pe_data', true);
  if (!is_array($rows)) { echo '<p>—</p>'; return; }
  echo '<table class="widefat striped"><tbody>';
  foreach ($rows as $k => $v) echo '<tr><th style="width:160px">' . esc_html(ucfirst($k)) . '</th><td>' . nl2br(esc_html((string)$v)) . '</td></tr>';
  echo '</tbody></table>';
}

/* Colonnes d'administration */
add_filter('manage_pe_event_posts_columns', function ($c) { $c['pe_when'] = 'Dates'; return $c; });
add_action('manage_pe_event_posts_custom_column', function ($col, $id) {
  if ($col !== 'pe_when') return;
  $r = get_post_meta($id, '_pe_recurring', true);
  echo esc_html($r ?: pe_date(get_post_meta($id, '_pe_start', true), 'j M Y') . (($e = get_post_meta($id, '_pe_end', true)) ? ' → ' . pe_date($e, 'j M Y') : ''));
}, 10, 2);
foreach (['pe_booking' => ['pe_ref' => 'Référence', 'pe_event' => 'Activité', 'pe_date' => 'Date / créneau', 'pe_contact' => 'Contact'], 'pe_subscriber' => ['pe_city' => 'Ville']] as $type => $cols) {
  add_filter("manage_{$type}_posts_columns", function ($c) use ($cols) { return array_slice($c, 0, 2) + $cols + array_slice($c, 2); });
  add_action("manage_{$type}_posts_custom_column", function ($col, $id) {
    $d = (array)get_post_meta($id, '_pe_data', true);
    $map = ['pe_ref' => $d['reference'] ?? '', 'pe_event' => $d['event'] ?? '', 'pe_date' => trim(($d['date'] ?? '') . ' ' . ($d['slot'] ?? '')) . (isset($d['guests']) ? ' · ' . $d['guests'] . ' pers.' : ''), 'pe_city' => $d['city'] ?? ''];
    if ($col === 'pe_contact') {
      $tel = $d['phone'] ?? '';
      echo esc_html($tel) . ($tel ? ' · <a href="https://wa.me/' . esc_attr(preg_replace('/\D/', '', $tel)) . '" target="_blank" rel="noopener">WhatsApp</a>' : '');
      if (!empty($d['email'])) echo '<br><a href="mailto:' . esc_attr($d['email']) . '">' . esc_html($d['email']) . '</a>';
    } elseif (isset($map[$col])) echo esc_html($map[$col]);
  }, 10, 2);
}

/* Éditeur classique pour nos contenus : les champs (dates, lieu, résumé vocal…) restent visibles sous le texte. */
add_filter('use_block_editor_for_post_type', function ($use, $type) {
  return in_array($type, ['post', 'pe_event', 'pe_place'], true) ? false : $use;
}, 10, 2);
