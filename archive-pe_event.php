<?php
if (!defined('ABSPATH')) exit;
get_header();
$filter = sanitize_title(wp_unslash($_GET['rubrique'] ?? ''));
$used = get_terms(['taxonomy' => 'pe_rubrique', 'object_ids' => get_posts(['post_type' => 'pe_event', 'numberposts' => -1, 'fields' => 'ids'])]);
$groups = [];
while (have_posts()) {
  the_post();
  $s = pe_meta('start'); $today = current_time('Y-m-d');
  $k = ($s && $s < $today) ? $today : $s;
  $groups[date_i18n('F Y', strtotime($k . ' 12:00'))][] = get_post();
}
global $wp_query;
$city = pe_city() ? get_term_by('slug', pe_city(), 'pe_city')->name : 'Partout au Maroc';
?>
<section class="section fade-in">
  <span class="kicker">Agenda</span>
  <h1 style="font-size:34px;margin:4px 0 6px">Événements, festivals et rendez-vous</h1>
  <p class="muted"><?php echo esc_html($city . ' · ' . sprintf(_n('%d événement à venir', '%d événements à venir', $wp_query->found_posts, 'publitarium'), $wp_query->found_posts)); ?></p>
  <div class="filters" role="toolbar" aria-label="Filtrer par type">
    <a class="chip<?php echo $filter ? '' : ' active'; ?>" href="<?php echo esc_url(get_post_type_archive_link('pe_event')); ?>">Tout</a>
    <?php if (!is_wp_error($used)) foreach ($used as $t) : ?>
      <a class="chip<?php echo $filter === $t->slug ? ' active' : ''; ?>" href="<?php echo esc_url(add_query_arg('rubrique', $t->slug, get_post_type_archive_link('pe_event'))); ?>"><?php echo esc_html($t->name); ?></a>
    <?php endforeach; ?>
  </div>
  <?php if ($groups) : foreach ($groups as $month => $list) : ?>
    <h2 class="month-title"><?php echo esc_html($month); ?></h2>
    <div class="grid"><?php echo implode('', array_map('pe_event_card', $list)); ?></div>
  <?php endforeach; else : echo pe_empty('Aucun événement à venir pour ce filtre.'); endif; ?>
</section>
<?php get_footer();
