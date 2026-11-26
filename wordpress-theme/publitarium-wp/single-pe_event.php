<?php
if (!defined('ABSPATH')) exit;
get_header();
while (have_posts()) : the_post();
  $c = pe_cat(); $v = pe_visual_attr(null, 'pe-hero');
  $start = pe_meta('start'); $end = pe_meta('end'); $rec = pe_meta('recurring'); $venue = pe_meta('venue'); $city = pe_city_name();
  $slots = array_filter(array_map('trim', explode(',', (string)pe_meta('slots'))));
  $bookable = $slots || $c->slug === 'rendez-vous';
  $ics = ['title' => get_the_title(), 'start' => $start, 'end' => $end, 'loc' => trim($venue . ', ' . $city, ', '), 'desc' => wp_strip_all_tags(get_the_excerpt()), 'id' => get_post_field('post_name')];
?>
<article class="article fade-in">
  <a class="back" href="<?php echo esc_url(wp_get_referer() ?: get_post_type_archive_link('pe_event')); ?>"><?php echo pe_icon('back'); ?> Retour</a>
  <div class="visual<?php echo $v['class']; ?>" style="<?php echo $v['style']; ?>">
    <?php echo pe_icon($c->icon, 'v-icon'); ?>
    <div><span class="tag"><?php echo esc_html($c->name); ?></span> <span class="tag"><?php echo esc_html($city); ?></span></div>
  </div>
  <h1><?php the_title(); ?></h1>
  <div class="body"><?php the_content(); ?></div>
  <dl class="info-list">
    <div><dt>Quand</dt><dd style="margin:0"><?php echo esc_html($rec ?: pe_date($start) . ($end ? ' → ' . pe_date($end) : '')); ?></dd></div>
    <?php if ($venue) : ?><div><dt>Où</dt><dd style="margin:0"><?php echo esc_html($venue); ?></dd></div><?php endif; ?>
    <?php if (pe_meta('price')) : ?><div><dt>Tarif</dt><dd style="margin:0"><?php echo esc_html(pe_meta('price')); ?></dd></div><?php endif; ?>
  </dl>
  <?php if (pe_meta('date_note')) : ?><p class="note"><?php echo esc_html(pe_meta('date_note')); ?></p><?php endif; ?>
  <div class="article-actions">
    <?php if ($start) : ?><button class="btn" data-ics="<?php echo esc_attr(wp_json_encode($ics)); ?>"><?php echo pe_icon('plus'); ?> Ajouter au calendrier</button><?php endif; ?>
    <?php if (pe_meta('url')) : ?><a class="btn btn-ghost" href="<?php echo esc_url(pe_meta('url')); ?>" target="_blank" rel="noopener">Site officiel <?php echo pe_icon('arrow'); ?></a><?php endif; ?>
    <a class="btn btn-ghost" href="<?php echo esc_url('https://www.google.com/maps/search/?api=1&query=' . rawurlencode(trim($venue . ', ' . $city, ', '))); ?>" target="_blank" rel="noopener"><?php echo pe_icon('pin'); ?> Itinéraire</a>
    <button class="btn btn-ghost" data-share><?php echo pe_icon('share'); ?> Partager</button>
  </div>
  <?php if ($bookable) : echo pe_form_open('pe_booking'); ?>
    <h2>Prendre rendez-vous</h2>
    <?php echo pe_form_notice(); ?>
    <p class="muted" style="margin:0">Envoyez votre demande, nous vous recontactons pour confirmer.</p>
    <input type="hidden" name="event_id" value="<?php the_ID(); ?>">
    <label>Nom complet<input name="name" required autocomplete="name"></label>
    <div class="row">
      <label>Téléphone<input name="phone" type="tel" required autocomplete="tel" placeholder="+212 6…"></label>
      <label>Email<input name="email" type="email" autocomplete="email"></label>
    </div>
    <div class="row">
      <label>Date<input name="date" type="date" required min="<?php echo esc_attr(current_time('Y-m-d')); ?>"></label>
      <label>Personnes<input name="guests" type="number" min="1" max="50" value="2" required></label>
    </div>
    <?php if ($slots) : ?>
    <fieldset style="border:0;padding:0;margin:0"><legend style="font-weight:600;font-size:14px;margin-bottom:6px">Créneau</legend>
      <div class="slots"><?php foreach (array_values($slots) as $i => $s) : ?><label><input type="radio" name="slot" value="<?php echo esc_attr($s); ?>"<?php checked($i, 0); ?>><span><?php echo esc_html($s); ?></span></label><?php endforeach; ?></div>
    </fieldset>
    <?php endif; ?>
    <label>Message (optionnel)<textarea name="notes" rows="3"></textarea></label>
    <button class="btn" type="submit">Envoyer ma demande</button>
  </form>
  <?php endif; ?>
</article>
<?php endwhile; get_footer();
