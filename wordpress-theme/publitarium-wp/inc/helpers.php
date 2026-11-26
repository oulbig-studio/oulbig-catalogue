<?php
if (!defined('ABSPATH')) exit;

/** Icône SVG du sprite (défini dans footer.php). */
function pe_icon($id, $cls = '') {
  return '<svg class="' . esc_attr($cls) . '" aria-hidden="true"><use href="#i-' . esc_attr($id) . '"/></svg>';
}

/** Rubrique principale d'un contenu : objet {name, slug, color, icon, link}. */
function pe_cat($post = null) {
  $post = get_post($post);
  $terms = $post ? get_the_terms($post, 'pe_rubrique') : false;
  if ($terms && !is_wp_error($terms)) {
    $t = $terms[0];
    return (object)[
      'name' => $t->name, 'slug' => $t->slug,
      'color' => get_term_meta($t->term_id, 'pe_color', true) ?: pe_color('accent'),
      'icon' => get_term_meta($t->term_id, 'pe_icon', true) ?: 'spark',
      'link' => get_term_link($t),
    ];
  }
  return (object)['name' => '', 'slug' => '', 'color' => pe_color('accent'), 'icon' => 'spark', 'link' => ''];
}

function pe_city_name($post = null) {
  $terms = get_the_terms(get_post($post), 'pe_city');
  return ($terms && !is_wp_error($terms)) ? $terms[0]->name : '';
}

/** Dégradé ou photo de fond d'un visuel. */
function pe_visual_attr($post = null, $size = 'pe-card') {
  $post = get_post($post);
  $c = pe_cat($post)->color;
  $g = 'linear-gradient(135deg, ' . $c . ', color-mix(in srgb, ' . $c . ' 45%, #141a33))';
  $img = has_post_thumbnail($post) ? get_the_post_thumbnail_url($post, $size) : '';
  $style = '--g:' . $g;
  if ($img) $style .= ";background-image:linear-gradient(180deg,rgba(0,0,0,.05),rgba(0,0,0,.55)),url('" . esc_url($img) . "')";
  return ['class' => $img ? ' has-img' : '', 'style' => esc_attr($style)];
}

function pe_meta($key, $post = null) { return get_post_meta(get_post($post)->ID ?? 0, '_pe_' . $key, true); }

function pe_date($ymd, $format = 'j F Y') {
  if (!$ymd) return '';
  $ts = strtotime($ymd . ' 12:00:00');
  return $ts ? date_i18n($format, $ts) : '';
}

/** Ville choisie par le visiteur (paramètre ?ville= ou cookie). */
function pe_city() {
  static $city = null;
  if ($city !== null) return $city;
  $raw = isset($_GET['ville']) ? sanitize_title(wp_unslash($_GET['ville'])) : (isset($_COOKIE['pe_city']) ? sanitize_title(wp_unslash($_COOKIE['pe_city'])) : '');
  $city = ($raw && $raw !== 'tout' && term_exists($raw, 'pe_city')) ? $raw : '';
  return $city;
}
add_action('init', function () {
  if (isset($_GET['ville']) && !headers_sent()) {
    $v = sanitize_title(wp_unslash($_GET['ville']));
    setcookie('pe_city', $v === 'tout' ? '' : $v, ['expires' => $v === 'tout' ? time() - 3600 : time() + 180 * DAY_IN_SECONDS, 'path' => COOKIEPATH ?: '/', 'secure' => is_ssl(), 'httponly' => false, 'samesite' => 'Lax']);
  }
});
function pe_city_tax() {
  return ['relation' => 'OR',
    ['taxonomy' => 'pe_city', 'field' => 'slug', 'terms' => [pe_city(), 'national']],
  ];
}

/** Arguments de requête pour les événements à venir, triés par date. */
function pe_events_query_args($q = null) {
  $args = [
    'meta_key' => '_pe_start', 'orderby' => 'meta_value', 'order' => 'ASC',
    'meta_query' => [['key' => '_pe_until', 'value' => current_time('Y-m-d'), 'compare' => '>=', 'type' => 'CHAR']],
  ];
  if ($q instanceof WP_Query) { foreach ($args as $k => $v) $q->set($k, $v); return null; }
  return $args;
}

function pe_upcoming_events($rubrique = '', $n = 6) {
  $args = array_merge(pe_events_query_args(), ['post_type' => 'pe_event', 'posts_per_page' => $n, 'no_found_rows' => true]);
  $tax = [];
  if ($rubrique) $tax[] = ['taxonomy' => 'pe_rubrique', 'field' => 'slug', 'terms' => $rubrique];
  if (pe_city()) $tax[] = pe_city_tax();
  if ($tax) $args['tax_query'] = array_merge(['relation' => 'AND'], $tax);
  return new WP_Query($args);
}

/* ------------------------------------------------------------------ */
/* Cartes                                                              */
/* ------------------------------------------------------------------ */
function pe_fav_btn($post = null) {
  $post = get_post($post);
  $data = wp_json_encode(['k' => $post->post_type . ':' . $post->ID, 't' => get_the_title($post), 'u' => get_permalink($post), 'm' => pe_city_name($post)]);
  return '<button class="fav-btn" data-fav="' . esc_attr($data) . '" aria-pressed="false" aria-label="' . esc_attr(sprintf(__('Favori : %s', 'publitarium'), get_the_title($post))) . '">' . pe_icon('heart') . '</button>';
}

function pe_article_card($post = null) {
  $post = get_post($post); $c = pe_cat($post); $v = pe_visual_attr($post); $link = get_permalink($post);
  $rt = (int)pe_meta('read_time', $post);
  ob_start(); ?>
  <article class="card fade-in">
    <?php echo pe_fav_btn($post); ?>
    <a href="<?php echo esc_url($link); ?>" tabindex="-1" aria-hidden="true">
      <div class="visual<?php echo $v['class']; ?>" style="<?php echo $v['style']; ?>">
        <?php if ($c->name) : ?><span class="tag"><?php echo pe_icon($c->icon) . esc_html($c->name); ?></span><?php endif; ?>
        <?php echo pe_icon($c->icon, 'v-icon'); ?>
      </div>
    </a>
    <div class="card-body">
      <div class="meta"><span><?php echo esc_html(pe_city_name($post)); ?></span><span class="dot"><?php echo esc_html(get_the_date('j M', $post)); ?></span><?php if ($rt) : ?><span class="dot"><?php echo $rt; ?> min</span><?php endif; ?></div>
      <h3><a href="<?php echo esc_url($link); ?>"><?php echo esc_html(get_the_title($post)); ?></a></h3>
      <p><?php echo esc_html(get_the_excerpt($post)); ?></p>
      <?php if (pe_meta('voice', $post) || pe_meta('audio', $post)) : ?><span class="listen-pill"><?php echo pe_icon('headphones'); ?> <?php esc_html_e('Résumé vocal par Oulbig', 'publitarium'); ?></span><?php endif; ?>
    </div>
  </article>
  <?php return ob_get_clean();
}

function pe_event_card($post = null) {
  $post = get_post($post); $c = pe_cat($post);
  $start = pe_meta('start', $post); $end = pe_meta('end', $post); $rec = pe_meta('recurring', $post);
  $g = 'linear-gradient(135deg, ' . $c->color . ', color-mix(in srgb, ' . $c->color . ' 45%, #141a33))';
  $shown = ($start && $start < current_time('Y-m-d') && ($rec || $end)) ? current_time('Y-m-d') : $start;
  ob_start(); ?>
  <article class="event-card fade-in">
    <div class="date-badge" style="--g:<?php echo esc_attr($g); ?>">
      <b><?php echo esc_html(pe_date($shown, 'j')); ?></b><span><?php echo esc_html(rtrim(pe_date($shown, 'M'), '.')); ?></span>
    </div>
    <div>
      <?php echo pe_fav_btn($post); ?>
      <div class="meta"><span class="kicker" style="color:<?php echo esc_attr($c->color); ?>"><?php echo esc_html($c->name); ?></span><span><?php echo esc_html(pe_city_name($post)); ?></span></div>
      <h3><a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html(get_the_title($post)); ?></a></h3>
      <div class="meta">
        <span><?php echo $rec ? esc_html($rec) : esc_html(pe_date($start, 'j M') . ($end ? ' → ' . pe_date($end, 'j M Y') : '')); ?></span>
        <?php if (pe_meta('venue', $post)) : ?><span class="dot"><?php echo esc_html(pe_meta('venue', $post)); ?></span><?php endif; ?>
      </div>
    </div>
  </article>
  <?php return ob_get_clean();
}

function pe_place_card($post = null) {
  $post = get_post($post); $c = pe_cat($post); $v = pe_visual_attr($post); $link = get_permalink($post);
  ob_start(); ?>
  <article class="card fade-in">
    <?php echo pe_fav_btn($post); ?>
    <a href="<?php echo esc_url($link); ?>" tabindex="-1" aria-hidden="true">
      <div class="visual<?php echo $v['class']; ?>" style="<?php echo $v['style']; ?>;height:130px">
        <span class="tag"><?php echo esc_html(pe_meta('type', $post) ?: $c->name); ?></span>
        <?php echo pe_icon($c->icon, 'v-icon'); ?>
      </div>
    </a>
    <div class="card-body">
      <div class="meta"><span><?php echo esc_html(pe_city_name($post)); ?></span><?php if (pe_meta('area', $post)) : ?><span class="dot"><?php echo esc_html(pe_meta('area', $post)); ?></span><?php endif; ?><?php if (pe_meta('price', $post)) : ?><span class="dot"><?php echo esc_html(pe_meta('price', $post)); ?></span><?php endif; ?></div>
      <h3><a href="<?php echo esc_url($link); ?>"><?php echo esc_html(get_the_title($post)); ?></a></h3>
      <p><?php echo esc_html(get_the_excerpt($post)); ?></p>
    </div>
  </article>
  <?php return ob_get_clean();
}

function pe_card($post = null) {
  $post = get_post($post);
  if ($post->post_type === 'pe_event') return pe_event_card($post);
  if ($post->post_type === 'pe_place') return pe_place_card($post);
  return pe_article_card($post);
}

function pe_section_head($title, $link = '', $label = '') {
  $label = $label ?: __('Tout voir', 'publitarium');
  return '<div class="section-head"><h2>' . esc_html($title) . '</h2>' . ($link ? '<a href="' . esc_url($link) . '">' . esc_html($label) . ' ' . pe_icon('arrow') . '</a>' : '') . '</div>';
}

function pe_empty($msg) { return '<div class="empty">' . esc_html($msg) . '</div>'; }

/** Lien vers une rubrique (archive filtrée pour les événements et adresses). */
function pe_rubrique_link($slug) {
  $t = get_term_by('slug', $slug, 'pe_rubrique');
  return $t ? get_term_link($t) : home_url('/');
}
