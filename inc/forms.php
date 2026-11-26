<?php
if (!defined('ABSPATH')) exit;

/** Formulaires publics : rendez-vous, newsletter, contact (via admin-post.php). */
foreach (['pe_booking', 'pe_newsletter', 'pe_contact'] as $a) {
  add_action('admin_post_nopriv_' . $a, 'pe_handle_form');
  add_action('admin_post_' . $a, 'pe_handle_form');
}

function pe_form_redirect($status, $extra = []) {
  $back = wp_get_referer() ?: home_url('/');
  $back = remove_query_arg(['pe_msg', 'pe_ref'], $back);
  wp_safe_redirect(add_query_arg(array_merge(['pe_msg' => $status], $extra), $back) . ($status === 'nl' ? '#pe-nl' : '#pe-form'));
  exit;
}

function pe_handle_form() {
  $action = sanitize_key($_POST['action'] ?? '');
  if (!empty($_POST['website'])) pe_form_redirect('ok'); // pot de miel
  if (!wp_verify_nonce($_POST['pe_nonce'] ?? '', $action)) pe_form_redirect('expired');

  $ip = substr(hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . wp_salt()), 0, 16);
  $key = 'pe_rl_' . $ip;
  $n = (int)get_transient($key);
  if ($n >= 10) pe_form_redirect('limit');
  set_transient($key, $n + 1, HOUR_IN_SECONDS);

  $g = fn($k, $max = 300) => mb_substr(sanitize_text_field(wp_unslash($_POST[$k] ?? '')), 0, $max);
  $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
  $admin = get_option('admin_email');

  if ($action === 'pe_newsletter') {
    if (!is_email($email)) pe_form_redirect('invalid');
    $exists = get_posts(['post_type' => 'pe_subscriber', 'title' => $email, 'post_status' => 'any', 'fields' => 'ids', 'numberposts' => 1]);
    if (!$exists) {
      $id = wp_insert_post(['post_type' => 'pe_subscriber', 'post_status' => 'private', 'post_title' => $email]);
      update_post_meta($id, '_pe_data', ['email' => $email, 'city' => pe_city(), 'date' => current_time('mysql')]);
    }
    pe_form_redirect('nl');
  }

  if ($action === 'pe_contact') {
    $name = $g('name', 120); $msg = mb_substr(sanitize_textarea_field(wp_unslash($_POST['notes'] ?? '')), 0, 3000);
    if (!$name || !is_email($email) || !$msg) pe_form_redirect('invalid');
    wp_mail($admin, '[Publitarium] Message de ' . $name, $msg . "\n\n— " . $name . ' <' . $email . '>', ['Reply-To: ' . $name . ' <' . $email . '>']);
    pe_form_redirect('sent');
  }

  // Rendez-vous
  $event_id = absint($_POST['event_id'] ?? 0);
  $event = $event_id ? get_post($event_id) : null;
  $data = [
    'reference' => 'PE-' . strtoupper(wp_generate_password(6, false, false)),
    'event' => $event ? get_the_title($event) . ' (' . pe_city_name($event) . ')' : '',
    'name' => $g('name', 120), 'phone' => $g('phone', 40), 'email' => is_email($email) ? $email : '',
    'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['date'] ?? '') ? $_POST['date'] : '',
    'slot' => $g('slot', 10), 'guests' => max(1, min(50, (int)($_POST['guests'] ?? 1))),
    'notes' => mb_substr(sanitize_textarea_field(wp_unslash($_POST['notes'] ?? '')), 0, 1500),
  ];
  if (!$event || $event->post_type !== 'pe_event' || !$data['name'] || !$data['phone'] || !$data['date']) pe_form_redirect('invalid');
  $id = wp_insert_post(['post_type' => 'pe_booking', 'post_status' => 'private', 'post_title' => $data['reference'] . ' — ' . $data['name']]);
  update_post_meta($id, '_pe_data', $data);
  update_post_meta($id, '_pe_event_id', $event_id);
  $body = "Nouvelle demande de rendez-vous\n\n";
  foreach ($data as $k => $v) $body .= ucfirst($k) . ' : ' . $v . "\n";
  $body .= "\n" . admin_url('edit.php?post_type=pe_booking');
  wp_mail($admin, '[Publitarium] Rendez-vous ' . $data['reference'] . ' — ' . get_the_title($event), $body);
  pe_form_redirect('booked', ['pe_ref' => $data['reference']]);
}

/** Message affiché après l'envoi d'un formulaire. */
function pe_form_notice() {
  $m = sanitize_key($_GET['pe_msg'] ?? '');
  if (!$m) return '';
  $ref = sanitize_text_field(wp_unslash($_GET['pe_ref'] ?? ''));
  $txt = [
    'booked' => sprintf('Demande envoyée ! Référence : %s. Nous vous recontactons rapidement.', $ref),
    'nl' => 'Merci, vous êtes inscrit·e à la newsletter !', 'sent' => 'Message envoyé, merci !', 'ok' => 'Merci !',
    'invalid' => 'Merci de remplir les champs obligatoires.', 'expired' => 'La page a expiré, réessayez.', 'limit' => 'Trop d’envois, réessayez plus tard.',
  ][$m] ?? '';
  if (!$txt) return '';
  $ok = in_array($m, ['booked', 'nl', 'sent', 'ok'], true);
  return '<p class="' . ($ok ? 'notice-ok' : 'notice-err') . '" role="status">' . esc_html($txt) . '</p>';
}

function pe_form_open($action, $id = 'pe-form') {
  return '<form class="' . ($id === 'pe-form' ? 'form' : 'newsletter') . '" id="' . esc_attr($id) . '" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">'
    . '<input type="hidden" name="action" value="' . esc_attr($action) . '">'
    . wp_nonce_field($action, 'pe_nonce', false, false)
    . '<input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">';
}

/* Export CSV des rendez-vous et inscrits */
add_action('admin_menu', function () {
  foreach (['pe_booking' => 'rendez-vous', 'pe_subscriber' => 'inscrits'] as $type => $label) {
    add_submenu_page('edit.php?post_type=' . $type, 'Exporter', 'Exporter (Excel / CSV)', 'edit_others_posts', 'pe-export-' . $type, function () use ($type) {
      $url = wp_nonce_url(admin_url('admin-post.php?action=pe_export&type=' . $type), 'pe_export');
      echo '<div class="wrap"><h1>Exporter</h1><p><a class="button button-primary" href="' . esc_url($url) . '">Télécharger le fichier CSV</a></p></div>';
    });
  }
});
add_action('admin_post_pe_export', function () {
  if (!current_user_can('edit_others_posts') || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'pe_export')) wp_die('Accès refusé');
  $type = in_array($_GET['type'] ?? '', ['pe_booking', 'pe_subscriber'], true) ? $_GET['type'] : 'pe_booking';
  $posts = get_posts(['post_type' => $type, 'post_status' => 'any', 'numberposts' => -1]);
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="publitarium-' . ($type === 'pe_booking' ? 'rendez-vous' : 'newsletter') . '-' . date('Y-m-d') . '.csv"');
  $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
  $rows = array_map(fn($p) => ['recu_le' => $p->post_date] + (array)get_post_meta($p->ID, '_pe_data', true), $posts);
  $cols = []; foreach ($rows as $r) $cols = array_unique(array_merge($cols, array_keys($r)));
  fputcsv($o, $cols, ';');
  foreach ($rows as $r) fputcsv($o, array_map(fn($k) => preg_replace('/^[=+\-@]/', "'$0", (string)($r[$k] ?? '')), $cols), ';');
  exit;
});
