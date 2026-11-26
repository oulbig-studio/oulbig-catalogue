<?php
if (!defined('ABSPATH')) exit;
get_header();
while (have_posts()) : the_post(); ?>
<article class="article fade-in">
  <h1><?php the_title(); ?></h1>
  <div class="body"><?php the_content(); ?></div>
  <?php if (is_page('a-propos')) : echo pe_form_open('pe_contact'); ?>
    <h2>Nous contacter</h2>
    <?php echo pe_form_notice(); ?>
    <label>Nom<input name="name" required autocomplete="name"></label>
    <label>Email<input name="email" type="email" required autocomplete="email"></label>
    <label>Message<textarea name="notes" rows="4" required></textarea></label>
    <button class="btn" type="submit">Envoyer</button>
  </form><?php endif; ?>
</article>
<?php endwhile; get_footer();
