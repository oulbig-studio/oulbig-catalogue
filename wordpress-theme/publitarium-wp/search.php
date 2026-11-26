<?php
if (!defined('ABSPATH')) exit;
get_header(); ?>
<section class="section fade-in">
  <span class="kicker"><?php echo is_search() ? 'Recherche' : 'Actualité'; ?></span>
  <h1 style="font-size:34px;margin:4px 0 16px"><?php
    if (is_search()) echo 'Résultats pour « ' . esc_html(get_search_query()) . ' »';
    elseif (is_archive()) echo esc_html(wp_strip_all_tags(get_the_archive_title()));
    else echo 'Dernières actus'; ?></h1>
  <?php if (is_search()) : ?>
    <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="search-wrap">
      <label class="screen-reader-text" for="s">Rechercher</label>
      <input class="search-input" type="search" id="s" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="Festival, rooftop, Essaouira, hammam…" <?php echo get_search_query() ? '' : 'autofocus'; ?>>
    </form>
  <?php endif; ?>
  <?php if (have_posts()) : ?>
    <div class="grid"><?php while (have_posts()) { the_post(); echo pe_card(); } ?></div>
    <div class="pagination"><?php echo paginate_links(); ?></div>
  <?php elseif (!is_search() || get_search_query()) : echo pe_empty('Aucun résultat.'); endif; ?>
</section>
<?php get_footer();
