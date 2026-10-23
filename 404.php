<?php
get_header();
?>
<section class="mcl-hero mcl-hero--small">
	<div class="mcl-wrap mcl-hero__in">
		<p class="mcl-kicker">404</p>
		<h1 class="mcl-hero__title"><?php mcl_e( 'not_found' ); ?></h1>
		<p class="mcl-hero__sub"><?php mcl_e( 'not_found_t' ); ?></p>
		<div class="mcl-hero__cta"><a class="mcl-btn mcl-btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php mcl_e( 'nav_home' ); ?></a></div>
	</div>
</section>
<?php
get_footer();
