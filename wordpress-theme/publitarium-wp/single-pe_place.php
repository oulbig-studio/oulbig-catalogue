<?php
if (!defined('ABSPATH')) exit;
get_header();
while (have_posts()) : the_post();
  $c = pe_cat(); $v = pe_visual_attr(null, 'pe-hero'); $city = pe_city_name();
  $fav = wp_json_encode(['k' => 'pe_place:' . get_the_ID(), 't' => get_the_title(), 'u' => get_permalink(), 'm' => $city]);
  $q = trim((pe_meta('address') ?: get_the_title()) . ', ' . $city, ', ');
?>
<article class="article fade-in">
  <a class="back" href="<?php echo esc_url(wp_get_referer() ?: get_post_type_archive_link('pe_place')); ?>"><?php echo pe_icon('back'); ?> Retour</a>
  <div class="visual<?php echo $v['class']; ?>" style="<?php echo $v['style']; ?>"><?php echo pe_icon($c->icon, 'v-icon'); ?><div><span class="tag"><?php echo esc_html(pe_meta('type') ?: $c->name); ?></span> <span class="tag"><?php echo esc_html($city); ?></span></div></div>
  <h1><?php the_title(); ?></h1>
  <div class="body"><?php the_content(); ?></div>
  <dl class="info-list">
    <?php if (pe_meta('area')) : ?><div><dt>Quartier</dt><dd style="margin:0"><?php echo esc_html(pe_meta('area') . ', ' . $city); ?></dd></div><?php endif; ?>
    <?php if (pe_meta('address')) : ?><div><dt>Adresse</dt><dd style="margin:0"><?php echo esc_html(pe_meta('address')); ?></dd></div><?php endif; ?>
    <?php if (pe_meta('price')) : ?><div><dt>Budget</dt><dd style="margin:0"><?php echo esc_html(pe_meta('price')); ?></dd></div><?php endif; ?>
    <?php if (pe_meta('must_try')) : ?><div><dt>À ne pas manquer</dt><dd style="margin:0"><?php echo esc_html(pe_meta('must_try')); ?></dd></div><?php endif; ?>
  </dl>
  <div class="article-actions">
    <a class="btn" href="<?php echo esc_url('https://www.google.com/maps/search/?api=1&query=' . rawurlencode($q)); ?>" target="_blank" rel="noopener"><?php echo pe_icon('pin'); ?> Voir sur la carte</a>
    <button class="btn btn-ghost" data-fav-inline="<?php echo esc_attr($fav); ?>"><?php echo pe_icon('heart'); ?> Ajouter aux favoris</button>
    <button class="btn btn-ghost" data-share><?php echo pe_icon('share'); ?> Partager</button>
  </div>
</article>
<?php endwhile; get_footer();
