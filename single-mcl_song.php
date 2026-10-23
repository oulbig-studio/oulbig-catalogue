<?php
get_header();
while ( have_posts() ) :
	the_post();
	$id     = get_the_ID();
	$title  = mcl_song_title( $id );
	$artist = mcl_song_artist( $id );
	$cover  = mcl_cover_url( $id, 'mcl-cover' );
	$audio  = get_post_meta( $id, '_mcl_audio_url', true );
	$video  = get_post_meta( $id, '_mcl_video_url', true );
	$genre  = mcl_song_genre( $id );
	$dur    = get_post_meta( $id, '_mcl_duration', true );
	$same   = $audio && $video && $audio === $video; // titre synchronisé : le clip YouTube sert aussi d'écoute.

	// Paroles : originale + traductions remplies.
	$lyrics = array();
	$orig   = get_post_meta( $id, '_mcl_lyrics', true );
	$olang  = get_post_meta( $id, '_mcl_lyrics_lang', true );
	if ( $orig ) {
		$lyrics['orig'] = array(
			'label' => mcl_t( 'lyrics_original' ) . ( $olang ? ' · ' . mcl_opt_label( 'song_lang', $olang ) : '' ),
			'text'  => $orig,
			'rtl'   => in_array( $olang, array( 'ar', 'darija' ), true ) || preg_match( '/\p{Arabic}/u', $orig ),
		);
	}
	foreach ( mcl_langs() as $c => $l ) {
		$t = get_post_meta( $id, '_mcl_lyrics_' . $c, true );
		if ( $t ) {
			$lyrics[ $c ] = array( 'label' => $l['label'], 'text' => $t, 'rtl' => 'ar' === $c );
		}
	}
	$active = isset( $lyrics[ mcl_lang() ] ) ? mcl_lang() : ( $lyrics ? array_key_first( $lyrics ) : '' );
	?>
	<article class="mcl-single">
		<header class="mcl-single__hero">
			<?php if ( $cover ) : ?><div class="mcl-single__blur" style="background-image:url('<?php echo esc_url( $cover ); ?>')" aria-hidden="true"></div><?php endif; ?>
			<div class="mcl-wrap mcl-single__head">
				<div class="mcl-single__cover">
					<?php echo $cover ? '<img src="' . esc_url( $cover ) . '" alt="' . esc_attr( $title ) . '">' : mcl_placeholder_cover( $id ); // phpcs:ignore ?>
				</div>
				<div class="mcl-single__info">
					<?php if ( $genre ) : ?><p class="mcl-kicker"><?php echo esc_html( $genre ); ?></p><?php endif; ?>
					<h1 class="mcl-single__title"><?php echo esc_html( $title ); ?></h1>
					<p class="mcl-single__artist"><?php echo esc_html( $artist ); ?><?php echo $dur ? ' · <span dir="ltr">' . esc_html( $dur ) . '</span>' : ''; ?></p>
					<ul class="mcl-stats">
						<?php if ( $audio && mcl_plays( $id, 'audio' ) ) : ?><li><?php echo mcl_icon( 'audio' ); // phpcs:ignore ?><span data-count="audio"><?php echo esc_html( mcl_t( 'plays_n', mcl_num( mcl_plays( $id, 'audio' ) ) ) ); ?></span></li><?php endif; ?>
						<?php if ( $video && ! $same && mcl_plays( $id, 'video' ) ) : ?><li><?php echo mcl_icon( 'video' ); // phpcs:ignore ?><span data-count="video"><?php echo esc_html( mcl_t( 'views_n', mcl_num( mcl_plays( $id, 'video' ) ) ) ); ?></span></li><?php endif; ?>
					</ul>
					<div class="mcl-single__jump">
						<?php if ( $audio ) : ?><a class="mcl-btn mcl-btn--primary" href="<?php echo $same ? '#video' : '#audio'; ?>"><?php echo mcl_icon( 'play' ); // phpcs:ignore ?><?php mcl_e( 'listen' ); ?></a><?php endif; ?>
						<?php if ( $video && ! $same ) : ?><a class="mcl-btn mcl-btn--ghost" href="#video"><?php echo mcl_icon( 'video' ); // phpcs:ignore ?><?php mcl_e( 'watch' ); ?></a><?php endif; ?>
						<?php if ( $lyrics ) : ?><a class="mcl-btn mcl-btn--ghost" href="#lyrics"><?php echo mcl_icon( 'lyrics' ); // phpcs:ignore ?><?php mcl_e( 'lyrics' ); ?></a><?php endif; ?>
					</div>
					<?php
					$sync_id = get_post_meta( $id, '_mcl_sync_id', true );
					if ( $sync_id ) :
						$cat    = mcl_catalogue();
						$ch     = $cat['artists'][ $artist ]['youtube'] ?? '';
						$sp     = get_post_meta( $id, '_mcl_spotify_url', true ) ?: ( $cat['artists'][ $artist ]['spotify'] ?? '' );
						$yt_url = $audio ? $audio : $video;
						?>
						<p class="mcl-single__links">
							<?php if ( $yt_url ) : ?><a href="<?php echo esc_url( $yt_url ); ?>" target="_blank" rel="noopener">▶ <?php mcl_e( 'on_youtube' ); ?></a><?php endif; ?>
							<?php if ( $ch ) : ?><a href="<?php echo esc_url( $ch ); ?>" target="_blank" rel="noopener"><?php echo esc_html( mcl_t( 'artist_channel' ) . ' ' . $artist ); ?></a><?php endif; ?>
							<?php if ( $sp ) : ?><a href="<?php echo esc_url( $sp ); ?>" target="_blank" rel="noopener">Spotify</a><?php endif; ?>
						</p>
					<?php endif; ?>
				</div>
			</div>
		</header>

		<div class="mcl-wrap mcl-single__grid">
			<div class="mcl-single__main">
				<?php if ( $audio && ! $same ) : ?>
					<section id="audio" class="mcl-block">
						<h2 class="mcl-block__title"><?php echo mcl_icon( 'audio' ); // phpcs:ignore ?><?php mcl_e( 'listen' ); ?></h2>
						<?php echo mcl_media_html( $audio, 'audio', $id, array( 'title' => $title, 'artist' => $artist ) ); // phpcs:ignore ?>
					</section>
				<?php endif; ?>

				<?php if ( $video ) : ?>
					<section id="video" class="mcl-block">
						<h2 class="mcl-block__title"><?php echo mcl_icon( 'video' ); // phpcs:ignore ?><?php mcl_e( 'watch' ); ?></h2>
						<?php echo mcl_media_html( $video, 'video', $id ); // phpcs:ignore ?>
					</section>
				<?php endif; ?>

				<?php if ( ! $audio && ! $video ) : ?>
					<section class="mcl-block mcl-block--soon">
						<p><?php mcl_e( 'media_soon' ); ?></p>
						<?php if ( current_user_can( 'edit_post', $id ) ) : ?>
							<a class="mcl-link" href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>">+ Ajouter un lien audio ou vidéo</a>
						<?php endif; ?>
					</section>
				<?php endif; ?>

				<?php if ( trim( get_the_content() ) ) : ?>
					<section class="mcl-block mcl-prose">
						<h2 class="mcl-block__title"><?php mcl_e( 'about_song' ); ?></h2>
						<?php the_content(); ?>
					</section>
				<?php endif; ?>
			</div>

			<aside id="lyrics" class="mcl-block mcl-lyrics">
				<h2 class="mcl-block__title"><?php echo mcl_icon( 'lyrics' ); // phpcs:ignore ?><?php mcl_e( 'lyrics' ); ?></h2>
				<?php if ( $lyrics ) : ?>
					<?php if ( count( $lyrics ) > 1 ) : ?>
						<div class="mcl-tabs" role="tablist">
							<?php foreach ( $lyrics as $k => $ly ) : ?>
								<button type="button" role="tab" class="<?php echo $k === $active ? 'is-active' : ''; ?>" data-tab="<?php echo esc_attr( $k ); ?>" aria-selected="<?php echo $k === $active ? 'true' : 'false'; ?>"><?php echo esc_html( $ly['label'] ); ?></button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<?php foreach ( $lyrics as $k => $ly ) : ?>
						<div class="mcl-lyrics__text<?php echo $k === $active ? ' is-active' : ''; ?>" data-panel="<?php echo esc_attr( $k ); ?>" dir="<?php echo $ly['rtl'] ? 'rtl' : 'ltr'; ?>">
							<?php
							$html = esc_html( $ly['text'] );
							$html = preg_replace( '/\[([^\]]+)\]/u', '<span class="mcl-lyrics__tag">$1</span>', $html );
							$paras = preg_split( "/\n\s*\n/", $html );
							foreach ( $paras as $para ) {
								echo '<p>' . nl2br( $para ) . '</p>'; // phpcs:ignore
							}
							?>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="mcl-empty"><?php mcl_e( 'lyrics_none' ); ?></p>
				<?php endif; ?>
			</aside>
		</div>

		<?php
		$terms   = wp_get_post_terms( $id, 'mcl_genre', array( 'fields' => 'ids' ) );
		$related = new WP_Query( array(
			'post_type'      => 'mcl_song',
			'posts_per_page' => 4,
			'post__not_in'   => array( $id ),
			'tax_query'      => $terms ? array( array( 'taxonomy' => 'mcl_genre', 'terms' => $terms ) ) : array(),
		) );
		if ( ! $related->have_posts() ) {
			$related = new WP_Query( array( 'post_type' => 'mcl_song', 'posts_per_page' => 4, 'post__not_in' => array( $id ) ) );
		}
		if ( $related->have_posts() ) :
			?>
			<section class="mcl-section">
				<div class="mcl-wrap">
					<h2 class="mcl-h2"><?php mcl_e( 'related' ); ?></h2>
					<div class="mcl-grid">
						<?php
						while ( $related->have_posts() ) {
							$related->the_post();
							get_template_part( 'parts/song-card' );
						}
						wp_reset_postdata();
						?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<section class="mcl-band">
			<div class="mcl-wrap mcl-band__in">
				<p class="mcl-band__q"><?php mcl_e_html( 'hero_q' ); ?></p>
				<a class="mcl-btn mcl-btn--primary" href="<?php echo esc_url( mcl_request_url() ); ?>"><?php echo mcl_icon( 'gift' ); // phpcs:ignore ?><?php mcl_e( 'cta_create' ); ?></a>
			</div>
		</section>
	</article>
	<?php
endwhile;
get_footer();
