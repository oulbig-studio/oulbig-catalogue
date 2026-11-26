<?php
if (!defined('ABSPATH')) exit;

function pe_register_types() {
  if (post_type_exists('pe_event')) return;
  register_post_type('pe_event', [
    'labels' => [
      'name' => 'Événements', 'singular_name' => 'Événement', 'add_new' => 'Ajouter', 'add_new_item' => 'Ajouter un événement',
      'edit_item' => 'Modifier l’événement', 'all_items' => 'Tous les événements', 'menu_name' => 'Agenda & festivals', 'search_items' => 'Rechercher',
    ],
    'public' => true, 'has_archive' => 'agenda', 'rewrite' => ['slug' => 'evenement', 'with_front' => false],
    'menu_icon' => 'dashicons-calendar-alt', 'menu_position' => 5, 'show_in_rest' => true,
    'supports' => ['title', 'editor', 'excerpt', 'thumbnail'],
  ]);
  register_post_type('pe_place', [
    'labels' => [
      'name' => 'Bonnes adresses', 'singular_name' => 'Adresse', 'add_new' => 'Ajouter', 'add_new_item' => 'Ajouter une adresse',
      'edit_item' => 'Modifier l’adresse', 'all_items' => 'Toutes les adresses', 'menu_name' => 'Bonnes adresses',
    ],
    'public' => true, 'has_archive' => 'adresses', 'rewrite' => ['slug' => 'adresse', 'with_front' => false],
    'menu_icon' => 'dashicons-location', 'menu_position' => 6, 'show_in_rest' => true,
    'supports' => ['title', 'editor', 'excerpt', 'thumbnail'],
  ]);
  $private = [
    'public' => false, 'show_ui' => true, 'show_in_rest' => false, 'supports' => ['title'],
    'capability_type' => 'post', 'capabilities' => ['create_posts' => 'do_not_allow'], 'map_meta_cap' => true,
  ];
  register_post_type('pe_booking', $private + [
    'labels' => ['name' => 'Rendez-vous reçus', 'singular_name' => 'Rendez-vous', 'menu_name' => 'Rendez-vous reçus', 'edit_item' => 'Rendez-vous'],
    'menu_icon' => 'dashicons-clipboard', 'menu_position' => 7,
  ]);
  register_post_type('pe_subscriber', $private + [
    'labels' => ['name' => 'Newsletter', 'singular_name' => 'Inscrit', 'menu_name' => 'Newsletter'],
    'menu_icon' => 'dashicons-email-alt', 'menu_position' => 8,
  ]);

  register_taxonomy('pe_rubrique', ['post', 'pe_event', 'pe_place'], [
    'labels' => ['name' => 'Rubriques', 'singular_name' => 'Rubrique', 'add_new_item' => 'Ajouter une rubrique', 'menu_name' => 'Rubriques'],
    'hierarchical' => true, 'show_admin_column' => true, 'show_in_rest' => true,
    'rewrite' => ['slug' => 'rubrique', 'with_front' => false], 'query_var' => 'pe_rubrique',
  ]);
  register_taxonomy('pe_city', ['post', 'pe_event', 'pe_place'], [
    'labels' => ['name' => 'Villes', 'singular_name' => 'Ville', 'add_new_item' => 'Ajouter une ville', 'menu_name' => 'Villes'],
    'hierarchical' => true, 'show_admin_column' => true, 'show_in_rest' => true,
    'rewrite' => ['slug' => 'ville', 'with_front' => false], 'query_var' => 'pe_city',
  ]);
}
add_action('init', 'pe_register_types');

/* Couleur et icône des rubriques (écran Rubriques). */
$pe_icons = ['spark', 'calendar', 'music', 'clock', 'palette', 'pin', 'fork', 'moon', 'bag', 'compass', 'leaf'];
add_action('pe_rubrique_add_form_fields', function () use ($pe_icons) { ?>
  <div class="form-field"><label for="pe_color">Couleur</label><input type="color" name="pe_color" id="pe_color" value="#2f4bd8"></div>
  <div class="form-field"><label for="pe_icon">Icône</label><select name="pe_icon" id="pe_icon"><?php foreach ($pe_icons as $i) echo '<option>' . esc_html($i) . '</option>'; ?></select></div>
<?php });
add_action('pe_rubrique_edit_form_fields', function ($term) use ($pe_icons) {
  $c = get_term_meta($term->term_id, 'pe_color', true) ?: '#2f4bd8'; $ic = get_term_meta($term->term_id, 'pe_icon', true); ?>
  <tr class="form-field"><th><label for="pe_color">Couleur</label></th><td><input type="color" name="pe_color" id="pe_color" value="<?php echo esc_attr($c); ?>"></td></tr>
  <tr class="form-field"><th><label for="pe_icon">Icône</label></th><td><select name="pe_icon" id="pe_icon"><?php foreach ($pe_icons as $i) echo '<option' . selected($ic, $i, false) . '>' . esc_html($i) . '</option>'; ?></select></td></tr>
<?php });
$pe_save_term = function ($term_id) use ($pe_icons) {
  if (!current_user_can('manage_categories')) return;
  if (isset($_POST['pe_color']) && preg_match('/^#[0-9a-f]{6}$/i', $_POST['pe_color'])) update_term_meta($term_id, 'pe_color', strtolower($_POST['pe_color']));
  if (isset($_POST['pe_icon']) && in_array($_POST['pe_icon'], $pe_icons, true)) update_term_meta($term_id, 'pe_icon', $_POST['pe_icon']);
};
add_action('created_pe_rubrique', $pe_save_term);
add_action('edited_pe_rubrique', $pe_save_term);
