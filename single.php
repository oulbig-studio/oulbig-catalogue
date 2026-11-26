<?php
if (!defined('ABSPATH')) exit;
get_header();
while (have_posts()) : the_post();
  $c = pe_cat(); $v = pe_visual_attr(null, 'pe-hero');
  $voice = pe_meta('voice'); $audio = pe_meta('audio'); $rt = (int)pe_meta('read_time');
  $fav = wp_json_encode(['k' => 'post:' . get_the_ID(), 't' => get_the_title(), 'u' => get_permalink(), 'm' => pe_city_name()]);
?>
<article class="article fade-in">
  <a class="back" href="<?php echo esc_url(wp_get_referer() ?: home_url('/')); ?>"><?php echo pe_icon('back'); ?> Retour</a>
  <div class="visual<?php echo $v['class']; ?>" style="<?php echo $v['style']; ?>">
    <?php echo pe_icon($c->icon, 'v-icon'); ?>
    <div><?php if ($c->name) : ?><a class="tag" href="<?php echo esc_url($c->link); ?>"><?php echo pe_icon($c->icon) . esc_html($c->name); ?></a><?php endif; ?> <span class="tag"><?php echo esc_html(pe_city_name()); ?></span></div>
  </div>
  <h1><?php the_title(); ?></h1>
  <div class="meta"><span><?php the_author(); ?></span><span class="dot"><?php echo esc_html(get_the_date()); ?></span><?php if ($rt) : ?><span class="dot"><?php echo $rt; ?> min de lecture</span><?php endif; ?></div>
  <?php if ($voice || $audio) : ?>
  <div class="voice-card" data-voice-card="<?php the_ID(); ?>">
    <div class="avatar" aria-hidden="true">O</div>
    <div><small>Résumé vocal</small><strong>Écoutez l'essentiel, lu par Oulbig</strong><div class="wave" aria-hidden="true"><?php echo str_repeat('<i></i>', 24); ?></div></div>
    <button class="btn" data-listen="<?php the_ID(); ?>" data-title="<?php echo esc_attr(get_the_title()); ?>" data-text="<?php echo esc_attr($voice ?: get_the_excerpt()); ?>" data-audio="<?php echo esc_url($audio); ?>" aria-label="Écouter le résumé vocal"><?php echo pe_icon('play'); ?></button>
  </div>
  <?php endif; ?>
  <?php if (has_excerpt()) : ?><p class="lead"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
  <div class="body"><?php the_content(); ?></div>
  <div class="article-actions">
    <button class="btn btn-ghost" data-share><?php echo pe_icon('share'); ?> Partager</button>
    <button class="btn btn-ghost" data-fav-inline="<?php echo esc_attr($fav); ?>"><?php echo pe_icon('heart'); ?> Ajouter aux favoris</button>
  </div>
  <?php $tags = get_the_tags(); if ($tags) : ?><div class="btn-row"><?php foreach ($tags as $t) echo '<a class="chip" href="' . esc_url(get_tag_link($t)) . '">#' . esc_html($t->name) . '</a>'; ?></div><?php endif; ?>
  <?php
  $related = get_posts(['post_type' => 'post', 'numberposts' => 3, 'post__not_in' => [get_the_ID()], 'tax_query' => $c->slug ? [['taxonomy' => 'pe_rubrique', 'field' => 'slug', 'terms' => $c->slug]] : []]);
  if ($related) echo '<div class="section">' . pe_section_head('À lire aussi') . '<div class="grid">' . implode('', array_map('pe_article_card', $related)) . '</div></div>';
  ?>
</article>
<?php endwhile; get_footer();
