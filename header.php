<?php if (!defined('ABSPATH')) exit; ?><!DOCTYPE html>
<html <?php language_attributes(); ?> data-theme="<?php echo get_theme_mod('pe_dark_default') ? 'dark' : 'light'; ?>">
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <script>try{var t=localStorage.getItem("pe_theme");if(t)document.documentElement.dataset.theme=JSON.parse(t)}catch(e){}</script>
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip" href="#main"><?php esc_html_e('Aller au contenu', 'publitarium'); ?></a>
<header class="topbar">
  <a href="<?php echo esc_url(home_url('/')); ?>" class="brand" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
    <span class="brand-mark" aria-hidden="true">P</span>
    <span class="brand-text">Publitarium<em>Events</em></span>
  </a>
  <nav class="desk-nav" aria-label="<?php esc_attr_e('Navigation principale', 'publitarium'); ?>">
    <a href="<?php echo esc_url(home_url('/')); ?>"<?php echo is_front_page() ? ' class="active"' : ''; ?>>Accueil</a>
    <a href="<?php echo esc_url(get_post_type_archive_link('pe_event')); ?>"<?php echo is_post_type_archive('pe_event') ? ' class="active"' : ''; ?>>Agenda</a>
    <a href="<?php echo esc_url(pe_rubrique_link('festivals')); ?>">Festivals</a>
    <a href="<?php echo esc_url(pe_rubrique_link('rendez-vous')); ?>">Rendez-vous</a>
    <a href="<?php echo esc_url(get_post_type_archive_link('pe_place')); ?>"<?php echo is_post_type_archive('pe_place') ? ' class="active"' : ''; ?>>Bonnes adresses</a>
  </nav>
  <div class="top-actions">
    <a href="<?php echo esc_url(home_url('/?s=')); ?>" class="icon-btn" aria-label="Rechercher"><?php echo pe_icon('search'); ?></a>
    <button class="icon-btn" id="themeBtn" aria-label="Changer de thème"><?php echo pe_icon('moon'); ?></button>
  </div>
</header>
<?php
$cities = get_terms(['taxonomy' => 'pe_city', 'hide_empty' => false, 'exclude' => array_filter([(int)(get_term_by('slug', 'national', 'pe_city')->term_id ?? 0)])]);
$base = remove_query_arg(['ville', 'paged', 'pe_msg', 'pe_ref']);
if (!is_singular() && !is_wp_error($cities) && $cities) : ?>
<div class="city-bar" role="toolbar" aria-label="Choisir une ville">
  <a class="chip<?php echo pe_city() ? '' : ' active'; ?>" href="<?php echo esc_url(add_query_arg('ville', 'tout', $base)); ?>">Tout le Maroc</a>
  <?php foreach ($cities as $c) : ?>
    <a class="chip<?php echo pe_city() === $c->slug ? ' active' : ''; ?>" href="<?php echo esc_url(add_query_arg('ville', $c->slug, $base)); ?>"><?php echo esc_html($c->name); ?></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<main id="main" tabindex="-1">
