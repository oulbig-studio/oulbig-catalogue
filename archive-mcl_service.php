<?php
get_header();
?>
<section class="mcl-pagehead mcl-pagehead--center">
	<div class="mcl-wrap">
		<p class="mcl-kicker">Musicology</p>
		<h1 class="mcl-h1"><?php mcl_e_html( 'services_title' ); ?></h1>
		<p class="mcl-lead"><?php mcl_e( 'services_sub' ); ?></p>
	</div>
</section>
<section class="mcl-section mcl-section--tight">
	<div class="mcl-wrap">
		<div class="mcl-svc-grid">
			<?php
			get_template_part( 'parts/special-card' );
			get_template_part( 'parts/audiobook-card' );
			while ( have_posts() ) {
				the_post();
				get_template_part( 'parts/service-card' );
			}
			?>
		</div>
	</div>
</section>
<?php
get_template_part( 'parts/platforms' );
get_template_part( 'parts/process' );
get_template_part( 'parts/faq' );
?>
<section class="mcl-band">
	<div class="mcl-wrap mcl-band__in">
		<p class="mcl-band__q"><?php mcl_e( 'need_service' ); ?></p>
		<?php if ( mcl_whatsapp_url() ) : ?>
			<a class="mcl-btn mcl-btn--primary" href="<?php echo esc_url( mcl_whatsapp_url( sprintf( mcl_t( 'wa_service_msg' ), mcl_t( 'nav_services' ) ) ) ); ?>" target="_blank" rel="noopener"><?php echo mcl_icon( 'whatsapp' ); // phpcs:ignore ?>WhatsApp</a>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
