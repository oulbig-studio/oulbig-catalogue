<?php
get_header();
$req = mcl_request_url();
?>

<!-- Scène 1 — L'accroche -->
<section class="mcl-hero">
	<div class="mcl-hero__bg" aria-hidden="true"></div>
	<div class="mcl-wrap mcl-hero__in">
		<div class="mcl-candle" aria-hidden="true">
			<div class="mcl-candle__glow"></div>
			<div class="mcl-candle__flame"></div>
			<div class="mcl-candle__wick"></div>
			<div class="mcl-candle__body"></div>
		</div>
		<p class="mcl-kicker"><?php mcl_e( 'hero_kicker' ); ?></p>
		<h1 class="mcl-hero__title"><?php mcl_e_html( 'hero_q' ); ?></h1>
		<p class="mcl-hero__sub"><?php mcl_e( 'hero_sub' ); ?></p>
		<div class="mcl-hero__cta">
			<a class="mcl-btn mcl-btn--primary mcl-btn--lg" href="<?php echo esc_url( $req ); ?>"><?php echo mcl_icon( 'gift' ); // phpcs:ignore ?><?php mcl_e( 'cta_create' ); ?><?php echo mcl_icon( 'arrow' ); // phpcs:ignore ?></a>
			<a class="mcl-btn mcl-btn--ghost mcl-btn--lg" href="#mcl-latest"><?php echo mcl_icon( 'play' ); // phpcs:ignore ?><?php mcl_e( 'cta_listen' ); ?></a>
			<a class="mcl-btn mcl-btn--ghost mcl-btn--lg" href="#mcl-services"><?php echo mcl_icon( 'audio' ); // phpcs:ignore ?><?php mcl_e( 'nav_services' ); ?></a>
		</div>
		<p class="mcl-hero__price"><?php echo esc_html( mcl_t( 'price_from', mcl_dh( mcl_price( 'price_song' ) ), mcl_t( 'per_clip', mcl_dh( mcl_price( 'price_clip' ) ) ) ) ); ?></p>
	</div>
	<div class="mcl-hero__wave" aria-hidden="true"><?php for ( $i = 0; $i < 64; $i++ ) { echo '<i style="--i:' . (int) $i . '"></i>'; } ?></div>
</section>

<!-- Scène 2 — Les occasions -->
<section class="mcl-section mcl-occasions">
	<div class="mcl-wrap">
		<h2 class="mcl-h2"><?php mcl_e_html( 'occ_title' ); ?></h2>
		<p class="mcl-lead"><?php mcl_e( 'occ_text' ); ?></p>
		<div class="mcl-chips-row">
			<?php foreach ( mcl_options( 'occasion' ) as $k => $l ) : if ( 'other' === $k ) { continue; } ?>
				<a class="mcl-occ" href="<?php echo esc_url( add_query_arg( 'occasion', $k, $req ) ); ?>"><?php echo esc_html( mcl_opt_label( 'occasion', $k ) ); ?></a>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- Aperçu des services -->
<section class="mcl-section mcl-section--alt" id="mcl-services">
	<div class="mcl-wrap">
		<div class="mcl-section__head">
			<div>
				<h2 class="mcl-h2"><?php mcl_e_html( 'services_title' ); ?></h2>
				<p class="mcl-lead" style="margin:0"><?php mcl_e( 'services_sub' ); ?></p>
			</div>
			<a class="mcl-link" href="<?php echo esc_url( mcl_services_url() ); ?>"><?php mcl_e( 'services_all' ); ?> <?php echo mcl_icon( 'arrow' ); // phpcs:ignore ?></a>
		</div>
		<div class="mcl-svc-grid">
			<?php
			get_template_part( 'parts/special-card' );
			get_template_part( 'parts/audiobook-card' );
			$svcs = new WP_Query( array( 'post_type' => 'mcl_service', 'posts_per_page' => 4, 'orderby' => array( 'menu_order' => 'ASC' ) ) );
			while ( $svcs->have_posts() ) {
				$svcs->the_post();
				get_template_part( 'parts/service-card' );
			}
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
<?php get_template_part( 'parts/platforms' ); ?>

<?php
$promo = mcl_opt( 'promo_video' );
if ( $promo ) :
	?>
<section class="mcl-section mcl-promo">
	<div class="mcl-wrap mcl-promo__grid">
		<div class="mcl-promo__video"><?php echo mcl_media_html( $promo, 'video', 0, array( 'vertical' => true ) ); // phpcs:ignore ?></div>
		<div class="mcl-promo__text">
			<p class="mcl-kicker">Musicology</p>
			<h2 class="mcl-h2"><?php mcl_e_html( 'promo_title' ); ?></h2>
			<p class="mcl-lead"><?php mcl_e( 'promo_text' ); ?></p>
			<a class="mcl-btn mcl-btn--primary" href="<?php echo esc_url( $req ); ?>"><?php mcl_e( 'cta_create' ); ?><?php echo mcl_icon( 'arrow' ); // phpcs:ignore ?></a>
		</div>
	</div>
</section>
<?php endif; ?>

<!-- Dernières chansons -->
<section class="mcl-section" id="mcl-latest">
	<div class="mcl-wrap">
		<div class="mcl-section__head">
			<h2 class="mcl-h2"><?php mcl_e( 'latest_songs' ); ?></h2>
			<a class="mcl-link" href="<?php echo esc_url( add_query_arg( 'media', 'audio', get_post_type_archive_link( 'mcl_song' ) ) ); ?>"><?php mcl_e( 'see_all' ); ?> <?php echo mcl_icon( 'arrow' ); // phpcs:ignore ?></a>
		</div>
		<?php
		$songs = new WP_Query( array( 'post_type' => 'mcl_song', 'posts_per_page' => 8, 'meta_query' => array( array( 'key' => '_mcl_audio_url', 'value' => '', 'compare' => '!=' ) ) ) );
		if ( $songs->have_posts() ) :
			echo '<div class="mcl-grid">';
			while ( $songs->have_posts() ) {
				$songs->the_post();
				get_template_part( 'parts/song-card', null, array( 'mode' => 'audio' ) );
			}
			echo '</div>';
			wp_reset_postdata();
		else :
			echo '<p class="mcl-empty">' . esc_html( mcl_t( 'empty' ) ) . '</p>';
		endif;
		?>
	</div>
</section>

<!-- Hit-parade (aperçu) -->
<section class="mcl-section mcl-section--alt">
	<div class="mcl-wrap">
		<div class="mcl-section__head">
			<h2 class="mcl-h2"><?php echo mcl_icon( 'trophy' ); // phpcs:ignore ?> <?php mcl_e( 'top_now' ); ?></h2>
			<a class="mcl-link" href="<?php echo esc_url( mcl_charts_url() ); ?>"><?php mcl_e( 'nav_charts' ); ?> <?php echo mcl_icon( 'arrow' ); // phpcs:ignore ?></a>
		</div>
		<div class="mcl-two">
			<div class="mcl-panel">
				<h3 class="mcl-panel__title"><?php echo mcl_icon( 'audio' ); // phpcs:ignore ?><?php mcl_e( 'tab_songs' ); ?></h3>
				<?php mcl_render_chart( 'audio', 'all', 5, true ); ?>
			</div>
			<div class="mcl-panel">
				<h3 class="mcl-panel__title"><?php echo mcl_icon( 'video' ); // phpcs:ignore ?><?php mcl_e( 'tab_clips' ); ?></h3>
				<?php mcl_render_chart( 'video', 'all', 5, true ); ?>
			</div>
		</div>
	</div>
</section>

<!-- Derniers clips -->
<?php
$clips = new WP_Query( array( 'post_type' => 'mcl_song', 'posts_per_page' => 6, 'meta_query' => array( array( 'key' => '_mcl_video_url', 'value' => '', 'compare' => '!=' ) ) ) );
if ( $clips->have_posts() ) :
	?>
<section class="mcl-section">
	<div class="mcl-wrap">
		<div class="mcl-section__head">
			<h2 class="mcl-h2"><?php mcl_e( 'latest_clips' ); ?></h2>
			<a class="mcl-link" href="<?php echo esc_url( mcl_clips_url() ); ?>"><?php mcl_e( 'see_all' ); ?> <?php echo mcl_icon( 'arrow' ); // phpcs:ignore ?></a>
		</div>
		<div class="mcl-grid mcl-grid--wide">
			<?php
			while ( $clips->have_posts() ) {
				$clips->the_post();
				get_template_part( 'parts/song-card', null, array( 'mode' => 'video' ) );
			}
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
<?php endif; ?>

<!-- Scène 3 — Comment ça marche -->
<section class="mcl-section mcl-how">
	<div class="mcl-wrap mcl-how__grid">
		<div>
			<h2 class="mcl-h2"><?php mcl_e_html( 'how_title' ); ?></h2>
			<p class="mcl-lead"><?php mcl_e( 'how_text' ); ?></p>
		</div>
		<ol class="mcl-steps">
			<li><span>✍️</span><?php mcl_e( 'how_1' ); ?></li>
			<li><span>🌍</span><?php mcl_e( 'how_2' ); ?></li>
			<li><span>🎵</span><?php mcl_e( 'how_3' ); ?></li>
			<li><span>👤</span><?php mcl_e( 'how_4' ); ?></li>
		</ol>
	</div>
	<p class="mcl-wrap mcl-how__promise"><?php mcl_e( 'how_promise' ); ?></p>
</section>

<!-- Scène 4 — Le drop -->
<section class="mcl-drop" aria-label="<?php echo esc_attr( mcl_t( 'drop_1' ) . ' ' . mcl_t( 'drop_2' ) . ' ' . mcl_t( 'drop_3' ) ); ?>">
	<div class="mcl-drop__lights" aria-hidden="true"><i></i><i></i><i></i></div>
	<p class="mcl-wrap mcl-drop__text">
		<span><?php mcl_e( 'drop_1' ); ?></span>
		<span><?php mcl_e( 'drop_2' ); ?></span>
		<span><?php mcl_e( 'drop_3' ); ?></span>
	</p>
</section>

<!-- Blog -->
<?php
$posts = mcl_latest_posts( 3 );
if ( $posts->have_posts() ) :
	?>
<section class="mcl-section">
	<div class="mcl-wrap">
		<div class="mcl-section__head">
			<h2 class="mcl-h2"><?php mcl_e( 'latest_posts' ); ?></h2>
			<a class="mcl-link" href="<?php echo esc_url( mcl_blog_url() ); ?>"><?php mcl_e( 'all_posts' ); ?> <?php echo mcl_icon( 'arrow' ); // phpcs:ignore ?></a>
		</div>
		<div class="mcl-blog-grid">
			<?php
			while ( $posts->have_posts() ) {
				$posts->the_post();
				get_template_part( 'parts/post-card' );
			}
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
<?php endif; ?>

<!-- Scène 5 — L'appel à l'action -->
<section class="mcl-section mcl-cta">
	<div class="mcl-wrap mcl-cta__box">
		<div class="mcl-cta__wave" aria-hidden="true"><?php for ( $i = 0; $i < 40; $i++ ) { echo '<i style="--i:' . (int) $i . '"></i>'; } ?></div>
		<span class="mcl-logo__mark mcl-cta__logo" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
		<h2 class="mcl-h2"><?php mcl_e_html( 'cta_title' ); ?></h2>
		<?php get_template_part( 'parts/contact-list' ); ?>
		<a class="mcl-btn mcl-btn--primary mcl-btn--lg" href="<?php echo esc_url( $req ); ?>"><?php echo mcl_icon( 'gift' ); // phpcs:ignore ?><?php mcl_e( 'cta_create' ); ?><?php echo mcl_icon( 'arrow' ); // phpcs:ignore ?></a>
		<p class="mcl-cta__final"><?php mcl_e( 'cta_final' ); ?></p>
	</div>
</section>

<?php
get_footer();
