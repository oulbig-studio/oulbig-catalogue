<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php
foreach ( array_keys( mcl_langs() ) as $hl ) {
	echo '<link rel="alternate" hreflang="' . esc_attr( $hl ) . '" href="' . esc_url( mcl_switch_url( $hl ) ) . '">' . "\n";
}
wp_head();
?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="mcl-skip" href="#main">↓</a>
<header class="mcl-header" id="top">
	<div class="mcl-wrap mcl-header__in">
		<a class="mcl-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php bloginfo( 'name' ); ?>">
			<?php if ( has_custom_logo() ) : ?>
				<?php echo wp_get_attachment_image( get_theme_mod( 'custom_logo' ), 'full', false, array( 'class' => 'mcl-logo__img' ) ); ?>
			<?php else : ?>
				<span class="mcl-logo__mark" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
				<span class="mcl-logo__text">Musicology</span>
			<?php endif; ?>
		</a>

		<nav class="mcl-nav" id="mcl-nav" aria-label="Menu">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="<?php echo is_front_page() ? 'is-active' : ''; ?>"><?php mcl_e( 'nav_home' ); ?></a>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'mcl_song' ) ); ?>" class="<?php echo ( is_post_type_archive( 'mcl_song' ) && 'video' !== ( $_GET['media'] ?? '' ) ) || is_singular( 'mcl_song' ) ? 'is-active' : ''; ?>"><?php mcl_e( 'nav_songs' ); ?></a>
			<a href="<?php echo esc_url( mcl_clips_url() ); ?>" class="<?php echo is_post_type_archive( 'mcl_song' ) && 'video' === ( $_GET['media'] ?? '' ) ? 'is-active' : ''; ?>"><?php mcl_e( 'nav_clips' ); ?></a>
			<a href="<?php echo esc_url( mcl_charts_url() ); ?>" class="<?php echo is_page( (int) get_option( 'mcl_charts_page' ) ) ? 'is-active' : ''; ?>"><?php mcl_e( 'nav_charts' ); ?></a>
			<a href="<?php echo esc_url( mcl_services_url() ); ?>" class="<?php echo is_post_type_archive( 'mcl_service' ) || is_singular( 'mcl_service' ) ? 'is-active' : ''; ?>"><?php mcl_e( 'nav_services' ); ?></a>
			<a href="<?php echo esc_url( mcl_audio_url() ); ?>" class="<?php echo is_page( (int) get_option( 'mcl_audio_page' ) ) ? 'is-active' : ''; ?>"><?php mcl_e( 'nav_audiobooks' ); ?></a>
			<a href="<?php echo esc_url( mcl_blog_url() ); ?>" class="<?php echo is_home() || is_singular( 'post' ) || is_category() ? 'is-active' : ''; ?>"><?php mcl_e( 'nav_blog' ); ?></a>
			<a href="<?php echo esc_url( mcl_request_url() ); ?>" class="mcl-nav__cta"><?php echo mcl_icon( 'gift' ); // phpcs:ignore ?><?php mcl_e( 'nav_request' ); ?></a>
			<?php mcl_lang_switcher( 'mcl-langs--mobile' ); ?>
		</nav>

		<div class="mcl-header__tools">
			<?php mcl_lang_switcher(); ?>
			<button class="mcl-burger" type="button" aria-controls="mcl-nav" aria-expanded="false" aria-label="<?php echo esc_attr( mcl_t( 'menu' ) ); ?>"><span></span><span></span></button>
		</div>
	</div>
</header>
<main id="main" class="mcl-main">
