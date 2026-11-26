<?php
if (!defined('ABSPATH')) exit;
get_header();
$filter = sanitize_title(wp_unslash($_GET['rubrique'] ?? ''));
$used = get_terms(['taxonomy' => 'pe_rubrique', 'object_ids' => get_posts(['post_type' => 'pe_place', 'numberposts' => -1, 'fields' => 'ids'])]);
global $wp_query;
$city = pe_city() ? get_term_by('slug', pe_city(), 'pe_city')->name : 'Partout au Maroc';
?>
<section class="section fade-in">
  <span class="kicker">Bonnes adresses</span>
  <h1 style="font-size:34px;margin:4px 0 6px">Où manger, sortir, flâner</h1>
  <p class="muted"><?php echo esc_html($city . ' · ' . sprintf(_n('%d adresse', '%d adresses', $wp_query->found_posts, 'publitarium'), $wp_query->found_posts)); ?></p>
  <div class="filters" role="toolbar" aria-label="Filtrer par type">
    <a class="chip<?php echo $filter ? '' : ' active'; ?>" href="<?php echo esc_url(get_post_type_archive_link('pe_place')); ?>">Tout</a>
    <?php if (!is_wp_error($used)) foreach ($used as $t) : ?>
      <a class="chip<?php echo $filter === $t->slug ? ' active' : ''; ?>" href="<?php echo esc_url(add_query_arg('rubrique', $t->slug, get_post_type_archive_link('pe_place'))); ?>"><?php echo esc_html($t->name); ?></a>
    <?php endforeach; ?>
  </div>
  <?php if (have_posts()) : ?><div class="grid"><?php while (have_posts()) { the_post(); echo pe_place_card(); } ?></div>
  <?php else : echo pe_empty('Aucune adresse pour ce filtre.'); endif; ?>
</section>
<?php get_footer();
