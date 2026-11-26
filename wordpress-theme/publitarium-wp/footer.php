<?php if (!defined('ABSPATH')) exit; ?>
</main>
<footer class="site-footer">
  <div class="footer-inner">
    <div>
      <a href="<?php echo esc_url(home_url('/')); ?>" class="brand"><span class="brand-mark">P</span><span class="brand-text">Publitarium<em>Events</em></span></a>
      <p><?php echo esc_html(get_theme_mod('pe_tagline', 'L’actualité culturelle, les rendez-vous, les festivals et les bonnes adresses du Maroc. Résumés vocaux par Oulbig.')); ?></p>
      <?php wp_nav_menu(['theme_location' => 'footer', 'container' => false, 'fallback_cb' => false, 'depth' => 1]); ?>
    </div>
    <?php echo pe_form_open('pe_newsletter', 'pe-nl'); ?>
      <label for="nl-email"><strong>La newsletter du week-end</strong></label>
      <div class="nl-row">
        <input id="nl-email" name="email" type="email" required placeholder="votre@email.com" autocomplete="email">
        <button class="btn" type="submit">S'inscrire</button>
      </div>
      <?php if (in_array($_GET['pe_msg'] ?? '', ['nl'], true)) echo pe_form_notice(); ?>
    </form>
  </div>
  <p class="copyright">© <?php echo esc_html(date('Y')); ?> Publitarium Events · <a href="<?php echo esc_url(home_url('/a-propos/')); ?>">À propos</a></p>
</footer>
<nav class="tabbar" aria-label="Navigation mobile">
  <a href="<?php echo esc_url(home_url('/')); ?>"<?php echo is_front_page() ? ' class="active"' : ''; ?>><?php echo pe_icon('home'); ?><span>Accueil</span></a>
  <a href="<?php echo esc_url(get_post_type_archive_link('pe_event')); ?>"<?php echo (is_post_type_archive('pe_event') || is_singular('pe_event')) ? ' class="active"' : ''; ?>><?php echo pe_icon('calendar'); ?><span>Agenda</span></a>
  <a href="<?php echo esc_url(get_post_type_archive_link('pe_place')); ?>"<?php echo (is_post_type_archive('pe_place') || is_singular('pe_place')) ? ' class="active"' : ''; ?>><?php echo pe_icon('pin'); ?><span>Adresses</span></a>
  <a href="<?php echo esc_url(home_url('/?s=')); ?>"<?php echo is_search() ? ' class="active"' : ''; ?>><?php echo pe_icon('search'); ?><span>Chercher</span></a>
  <a href="<?php echo esc_url(home_url('/favoris/')); ?>"<?php echo is_page('favoris') ? ' class="active"' : ''; ?>><?php echo pe_icon('heart'); ?><span>Favoris</span></a>
</nav>
<div class="player" id="player" hidden>
  <div class="player-avatar" aria-hidden="true">O</div>
  <div class="player-info">
    <span class="player-by">Résumé vocal · Oulbig</span>
    <strong class="player-title" id="playerTitle"></strong>
    <div class="player-bar"><span id="playerProgress"></span></div>
  </div>
  <button class="icon-btn" id="playerSpeed" aria-label="Vitesse de lecture">1×</button>
  <button class="icon-btn play" id="playerToggle" aria-label="Pause"><?php echo pe_icon('pause'); ?></button>
  <button class="icon-btn" id="playerClose" aria-label="Fermer le lecteur"><?php echo pe_icon('x'); ?></button>
</div>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
  <svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="i-moon" viewBox="0 0 24 24"><path d="M21 13A9 9 0 1 1 11 3a7 7 0 0 0 10 10z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
    <symbol id="i-home" viewBox="0 0 24 24"><path d="M3 11 12 4l9 7v9a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
    <symbol id="i-calendar" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M8 3v4M16 3v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="i-pin" viewBox="0 0 24 24"><path d="M12 22s7-6.5 7-12a7 7 0 0 0-14 0c0 5.5 7 12 7 12z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="10" r="2.5" fill="currentColor"/></symbol>
    <symbol id="i-heart" viewBox="0 0 24 24"><path d="M12 20s-8-4.7-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 9c0 6.3-8 11-8 11z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
    <symbol id="i-heart-fill" viewBox="0 0 24 24"><path d="M12 20s-8-4.7-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 9c0 6.3-8 11-8 11z" fill="currentColor"/></symbol>
    <symbol id="i-play" viewBox="0 0 24 24"><path d="M8 5v14l11-7z" fill="currentColor"/></symbol>
    <symbol id="i-pause" viewBox="0 0 24 24"><path d="M7 5h4v14H7zM13 5h4v14h-4z" fill="currentColor"/></symbol>
    <symbol id="i-x" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="i-share" viewBox="0 0 24 24"><path d="M4 12v7a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-7M12 3v12M7 8l5-5 5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="i-back" viewBox="0 0 24 24"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="i-spark" viewBox="0 0 24 24"><path d="M12 2l2.4 6.6L21 11l-6.6 2.4L12 20l-2.4-6.6L3 11l6.6-2.4z" fill="currentColor"/></symbol>
    <symbol id="i-music" viewBox="0 0 24 24"><path d="M9 18V5l11-2v13" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="6.5" cy="18" r="2.5" fill="currentColor"/><circle cx="17.5" cy="16" r="2.5" fill="currentColor"/></symbol>
    <symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="i-palette" viewBox="0 0 24 24"><path d="M12 3a9 9 0 0 0 0 18c1.5 0 2-1 2-2s-1-1.5-1-2.5S14 15 15 15h2a4 4 0 0 0 4-4c0-4.4-4-8-9-8z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="7.5" cy="11" r="1.3" fill="currentColor"/><circle cx="10" cy="7" r="1.3" fill="currentColor"/><circle cx="15" cy="7.5" r="1.3" fill="currentColor"/></symbol>
    <symbol id="i-fork" viewBox="0 0 24 24"><path d="M7 3v8M5 3v5a2 2 0 0 0 4 0V3M7 11v10M17 21V3c-2 1-3 4-3 7h3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="i-bag" viewBox="0 0 24 24"><path d="M5 8h14l-1 13H6zM9 8V6a3 3 0 0 1 6 0v2" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
    <symbol id="i-compass" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="m15.5 8.5-2 5-5 2 2-5z" fill="currentColor"/></symbol>
    <symbol id="i-leaf" viewBox="0 0 24 24"><path d="M5 19c0-9 6-14 15-14 0 9-5 15-14 15M5 19l7-7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="i-headphones" viewBox="0 0 24 24"><path d="M4 15v-3a8 8 0 0 1 16 0v3" fill="none" stroke="currentColor" stroke-width="2"/><rect x="3" y="14" width="5" height="7" rx="2" fill="currentColor"/><rect x="16" y="14" width="5" height="7" rx="2" fill="currentColor"/></symbol>
    <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="i-arrow" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  </svg>
<?php wp_footer(); ?>
</body>
</html>
