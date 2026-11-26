<?php
if (!defined('ABSPATH')) exit;
get_header();
$tax = pe_city() ? [pe_city_tax()] : [];
$featured = get_posts(['post_type' => 'post', 'numberposts' => 3, 'meta_key' => '_pe_featured', 'meta_value' => '1', 'tax_query' => $tax]);
$latest = get_posts(['post_type' => 'post', 'numberposts' => 12, 'tax_query' => $tax]);
if (count($featured) < 3) {
  $ids = wp_list_pluck($featured, 'ID');
  foreach ($latest as $p) { if (count($featured) >= 3) break; if (!in_array($p->ID, $ids, true)) $featured[] = $p; }
}
$top = $featured[0] ?? null;
$side = array_slice($featured, 1, 2);
$used = wp_list_pluck($featured, 'ID');
$rest = array_filter($latest, fn($p) => !in_array($p->ID, $used, true));
$rdv = pe_upcoming_events('rendez-vous', 3);
$fest = pe_upcoming_events('festivals', 6);
$places = get_posts(['post_type' => 'pe_place', 'numberposts' => 8, 'tax_query' => $tax, 'orderby' => 'rand']);
?>
<?php if ($top) : $v = pe_visual_attr($top, 'pe-hero'); $c = pe_cat($top); ?>
<section class="hero fade-in">
  <a class="visual hero-main<?php echo $v['class']; ?>" href="<?php echo esc_url(get_permalink($top)); ?>" style="<?php echo $v['style']; ?>">
    <?php echo pe_icon($c->icon, 'v-icon'); ?>
    <div><span class="tag"><?php echo pe_icon('spark'); ?> À la une · <?php echo esc_html(pe_city_name($top)); ?></span></div>
    <h1><?php echo esc_html(get_the_title($top)); ?></h1>
    <p><?php echo esc_html(get_the_excerpt($top)); ?></p>
    <span class="btn btn-light" style="align-self:flex-start"><?php echo pe_icon('headphones'); ?> Écouter le résumé d'Oulbig</span>
  </a>
  <div class="hero-side"><?php foreach ($side as $p) echo pe_article_card($p); ?></div>
</section>
<?php else : echo pe_empty('Aucun article pour cette ville pour le moment.'); endif; ?>

<section class="section">
  <?php echo pe_section_head('Explorer'); ?>
  <div class="cats">
    <?php foreach (get_terms(['taxonomy' => 'pe_rubrique', 'hide_empty' => false]) as $t) :
      $col = get_term_meta($t->term_id, 'pe_color', true) ?: pe_color('accent'); $ic = get_term_meta($t->term_id, 'pe_icon', true) ?: 'spark'; ?>
      <a class="cat" href="<?php echo esc_url(get_term_link($t)); ?>"><span class="ico" style="--c:<?php echo esc_attr($col); ?>;background:<?php echo esc_attr($col); ?>"><?php echo pe_icon($ic); ?></span><?php echo esc_html($t->name); ?></a>
    <?php endforeach; ?>
  </div>
</section>

<section class="section">
  <?php echo pe_section_head('Rendez-vous à réserver', pe_rubrique_link('rendez-vous')); ?>
  <?php echo $rdv->have_posts() ? '<div class="grid">' . implode('', array_map('pe_event_card', $rdv->posts)) . '</div>' : pe_empty('Pas de rendez-vous dans cette ville pour l’instant.'); ?>
</section>

<section class="section">
  <?php echo pe_section_head('Festivals à venir', pe_rubrique_link('festivals')); ?>
  <?php echo $fest->have_posts() ? '<div class="grid">' . implode('', array_map('pe_event_card', $fest->posts)) . '</div>' : pe_empty('Pas de festival annoncé dans cette ville.'); ?>
</section>

<section class="section">
  <?php echo pe_section_head('Bonnes adresses', get_post_type_archive_link('pe_place')); ?>
  <?php echo $places ? '<div class="hscroll">' . implode('', array_map('pe_place_card', $places)) . '</div>' : pe_empty('Pas encore d’adresse dans cette ville.'); ?>
</section>

<?php if ($rest) : ?>
<section class="section">
  <?php echo pe_section_head('Dernières actus', get_permalink(get_option('page_for_posts')) ?: ''); ?>
  <div class="grid"><?php foreach ($rest as $p) echo pe_article_card($p); ?></div>
</section>
<?php endif; ?>
<?php get_footer();
