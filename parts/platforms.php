<section class="mcl-platforms">
	<div class="mcl-wrap">
		<p class="mcl-platforms__title"><?php mcl_e( 'platforms' ); ?></p>
		<ul class="mcl-platforms__list">
			<?php foreach ( array( 'Spotify', 'Apple Music', 'Deezer', 'YouTube Music', 'Anghami', 'Amazon Music', 'TikTok', 'Instagram' ) as $p ) : ?>
				<li><?php echo esc_html( $p ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
