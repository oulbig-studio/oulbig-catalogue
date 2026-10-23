</main>
<footer class="mcl-footer">
	<div class="mcl-wrap mcl-footer__grid">
		<div class="mcl-footer__brand">
			<a class="mcl-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<span class="mcl-logo__mark" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
				<span class="mcl-logo__text">Musicology</span>
			</a>
			<p><?php mcl_e( 'f_tagline' ); ?></p>
			<p class="mcl-footer__quote">« <?php mcl_e( 'cta_final' ); ?> »</p>
		</div>
		<div>
			<h4><?php mcl_e( 'f_explore' ); ?></h4>
			<ul>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'mcl_song' ) ); ?>"><?php mcl_e( 'nav_songs' ); ?></a></li>
				<li><a href="<?php echo esc_url( mcl_clips_url() ); ?>"><?php mcl_e( 'nav_clips' ); ?></a></li>
				<li><a href="<?php echo esc_url( mcl_charts_url() ); ?>"><?php mcl_e( 'nav_charts' ); ?></a></li>
				<li><a href="<?php echo esc_url( mcl_services_url() ); ?>"><?php mcl_e( 'nav_services' ); ?></a></li>
				<li><a href="<?php echo esc_url( mcl_audio_url() ); ?>"><?php mcl_e( 'nav_audiobooks' ); ?></a></li>
				<li><a href="<?php echo esc_url( mcl_blog_url() ); ?>"><?php mcl_e( 'nav_blog' ); ?></a></li>
				<li><a href="<?php echo esc_url( mcl_request_url() ); ?>"><?php mcl_e( 'nav_request' ); ?></a></li>
			</ul>
		</div>
		<div>
			<h4><?php mcl_e( 'f_contact' ); ?></h4>
			<?php get_template_part( 'parts/contact-list' ); ?>
		</div>
		<div>
			<h4><?php mcl_e( 'lang_label' ); ?></h4>
			<ul class="mcl-footer__langs">
				<?php foreach ( mcl_langs() as $c => $l ) : ?>
					<li><a href="<?php echo esc_url( mcl_switch_url( $c ) ); ?>" class="<?php echo mcl_lang() === $c ? 'is-active' : ''; ?>"><?php echo esc_html( $l['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
	<div class="mcl-wrap mcl-footer__bottom">
		<span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> Musicology · OULBIG. <?php mcl_e( 'f_rights' ); ?></span>
		<span class="mcl-footer__wave" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></span>
	</div>
</footer>
<?php if ( mcl_whatsapp_url() && ! is_page_template( 'page-templates/request.php' ) ) : ?>
	<a class="mcl-wa-float" href="<?php echo esc_url( mcl_whatsapp_url() ); ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><?php echo mcl_icon( 'whatsapp' ); // phpcs:ignore ?></a>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
