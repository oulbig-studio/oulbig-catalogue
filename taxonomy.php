<?php
if (!defined('ABSPATH')) exit;
get_header();
$term = get_queried_object();
$col = get_term_meta($term->term_id, 'pe_color', true) ?: pe_color('accent');
$ic = get_term_meta($term->term_id, 'pe_icon', true) ?: ($term->taxonomy === 'pe_city' ? 'pin' : 'spark');
$g = 'linear-gradient(135deg, ' . $col . ', color-mix(in srgb, ' . $col . ' 45%, #141a33))';
$taxq = [['taxonomy' => $term->taxonomy, 'field' => 'term_id', 'terms' => $term->term_id]];
if ($term->taxonomy === 'pe_rubrique' && pe_city()) $taxq[] = pe_city_tax();
$events = new WP_Query(array_merge(pe_events_query_args(), ['post_type' => 'pe_event', 'posts_per_page' => 30, 'tax_query' => $taxq]));
$articles = get_posts(['post_type' => 'post', 'numberposts' => 24, 'tax_query' => $taxq]);
$places = get_posts(['post_type' => 'pe_place', 'numberposts' => 24, 'tax_query' => $taxq]);
?>
<section class="section fade-in">
  <div class="visual" style="--g:<?php echo esc_attr($g); ?>;padding:26px 20px;min-height:150px;display:flex;flex-direction:column;justify-content:flex-end">
    <?php echo pe_icon($ic, 'v-icon'); ?>
    <span class="kicker" style="color:#fff;opacity:.85"><?php echo $term->taxonomy === 'pe_city' ? 'Ville' : 'Rubrique'; ?></span>
    <h1 style="font-size:34px"><?php echo esc_html($term->name); ?></h1>
  </div>
  <?php if ($term->description) echo '<p class="lead" style="margin-top:14px">' . esc_html($term->description) . '</p>'; ?>
  <?php if ($events->have_posts()) : ?><div class="section"><?php echo pe_section_head($term->slug === 'rendez-vous' ? 'À réserver' : 'Événements'); ?><div class="grid"><?php echo implode('', array_map('pe_event_card', $events->posts)); ?></div></div><?php endif; ?>
  <?php if ($articles) : ?><div class="section"><?php echo pe_section_head('Articles'); ?><div class="grid"><?php echo implode('', array_map('pe_article_card', $articles)); ?></div></div><?php endif; ?>
  <?php if ($places) : ?><div class="section"><?php echo pe_section_head('Adresses'); ?><div class="grid"><?php echo implode('', array_map('pe_place_card', $places)); ?></div></div><?php endif; ?>
  <?php if (!$events->have_posts() && !$articles && !$places) echo '<div class="section">' . pe_empty('Rien dans cette rubrique pour le moment.') . '</div>'; ?>
</section>
<?php get_footer();
