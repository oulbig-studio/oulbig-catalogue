<?php
/**
 * Rendu des médias par lien : audio (MP3, SoundCloud, Spotify, Deezer, Audiomack…),
 * vidéo (YouTube, Shorts, Vimeo, Dailymotion, TikTok, Google Drive, MP4…).
 * Chaque lecteur porte data-post / data-kind pour le comptage du hit-parade.
 */
defined( 'ABSPATH' ) || exit;

function mcl_youtube_id( $url ) {
	if ( ! $url ) {
		return '';
	}
	if ( preg_match( '~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/))([A-Za-z0-9_-]{11})~', $url, $m ) ) {
		return $m[1];
	}
	return '';
}

function mcl_is_vertical_url( $url ) {
	return (bool) preg_match( '~youtube\.com/shorts/|tiktok\.com/|instagram\.com/reel~i', (string) $url );
}

/** Convertit un lien en URL d'iframe quand on sait le faire nous-mêmes. */
function mcl_embed_src( $url ) {
	if ( $id = mcl_youtube_id( $url ) ) {
		return 'https://www.youtube-nocookie.com/embed/' . $id . '?autoplay=1&rel=0&playsinline=1&modestbranding=1';
	}
	if ( preg_match( '~vimeo\.com/(?:video/)?(\d+)~', $url, $m ) ) {
		return 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1&title=0&byline=0';
	}
	if ( preg_match( '~(?:dailymotion\.com/video/|dai\.ly/)([A-Za-z0-9]+)~', $url, $m ) ) {
		return 'https://www.dailymotion.com/embed/video/' . $m[1] . '?autoplay=1';
	}
	if ( preg_match( '~drive\.google\.com/(?:file/d/|open\?id=)([A-Za-z0-9_-]+)~', $url, $m ) ) {
		return 'https://drive.google.com/file/d/' . $m[1] . '/preview';
	}
	if ( preg_match( '~deezer\.com/(?:[a-z]{2}/)?(track|album|playlist)/(\d+)~', $url, $m ) ) {
		return 'https://widget.deezer.com/widget/dark/' . $m[1] . '/' . $m[2];
	}
	if ( preg_match( '~audiomack\.com/([^/]+)/song/([^/?#]+)~', $url, $m ) ) {
		return 'https://audiomack.com/embed/song/' . $m[1] . '/' . $m[2] . '?background=1';
	}
	if ( preg_match( '~open\.spotify\.com/(?:intl-[a-z]+/)?(track|album|playlist|episode)/([A-Za-z0-9]+)~', $url, $m ) ) {
		return 'https://open.spotify.com/embed/' . $m[1] . '/' . $m[2] . '?theme=0';
	}
	return '';
}

function mcl_file_type( $url ) {
	$path = strtolower( (string) wp_parse_url( $url, PHP_URL_PATH ) );
	if ( preg_match( '/\.(mp3|m4a|aac|wav|ogg|oga|flac|opus)$/', $path ) ) {
		return 'audio';
	}
	if ( preg_match( '/\.(mp4|webm|mov|m4v|ogv)$/', $path ) ) {
		return 'video';
	}
	return '';
}

/**
 * HTML du lecteur.
 * @param string $url   Lien.
 * @param string $kind  'audio' ou 'video' (sert au comptage).
 * @param int    $post  ID de la chanson (0 = pas de comptage).
 */
function mcl_media_html( $url, $kind = 'video', $post = 0, $args = array() ) {
	$url = trim( (string) $url );
	if ( ! $url ) {
		return '';
	}
	$args   = wp_parse_args( $args, array( 'title' => '', 'artist' => '', 'cover' => '' ) );
	$data   = sprintf( ' data-post="%d" data-kind="%s"', (int) $post, esc_attr( $kind ) );
	$cover  = $args['cover'] ? $args['cover'] : ( $post ? mcl_cover_url( $post, 'mcl-wide' ) : '' );
	$ftype  = mcl_file_type( $url );

	// 1) Fichier audio direct → lecteur maison.
	if ( 'audio' === $ftype ) {
		ob_start(); ?>
		<div class="mcl-player"<?php echo $data; // phpcs:ignore ?>>
			<audio preload="metadata" src="<?php echo esc_url( $url ); ?>"></audio>
			<button type="button" class="mcl-player__btn" aria-label="<?php echo esc_attr( mcl_t( 'listen' ) ); ?>">
				<?php echo mcl_icon( 'play' ) . mcl_icon( 'pause' ); // phpcs:ignore ?>
			</button>
			<div class="mcl-player__body">
				<?php if ( $args['title'] ) : ?>
					<div class="mcl-player__meta"><strong><?php echo esc_html( $args['title'] ); ?></strong><span><?php echo esc_html( $args['artist'] ); ?></span></div>
				<?php endif; ?>
				<div class="mcl-player__bar" role="slider" tabindex="0" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="Position">
					<div class="mcl-player__buffer"></div><div class="mcl-player__fill"></div>
				</div>
				<div class="mcl-player__time"><span class="mcl-cur">0:00</span><span class="mcl-dur">--:--</span></div>
			</div>
			<div class="mcl-eq" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
		</div>
		<?php
		return ob_get_clean();
	}

	// 2) Fichier vidéo direct.
	if ( 'video' === $ftype ) {
		return '<div class="mcl-embed mcl-embed--video' . ( ! empty( $args['vertical'] ) ? ' is-vertical' : '' ) . '"' . $data . '><video controls playsinline preload="metadata"' . ( $cover ? ' poster="' . esc_url( $cover ) . '"' : '' ) . ' src="' . esc_url( $url ) . '"></video></div>';
	}

	$vertical = mcl_is_vertical_url( $url ) || ! empty( $args['vertical'] );
	$src      = mcl_embed_src( $url );

	// 3) Lien connu (YouTube, Vimeo, Drive…) → vignette cliquable, iframe chargée au clic.
	if ( $src && preg_match( '~youtube|vimeo|dailymotion|drive\.google~', $src ) ) {
		$yt    = mcl_youtube_id( $url );
		$thumb = $yt ? 'https://i.ytimg.com/vi/' . $yt . '/hqdefault.jpg' : $cover;
		if ( 'audio' === $kind && $yt ) {
			$vertical = false;
		}
		return sprintf(
			'<div class="mcl-embed mcl-embed--lazy%1$s"%2$s data-src="%3$s"><button type="button" class="mcl-embed__poster" aria-label="%4$s">%5$s<span class="mcl-embed__play">%6$s</span></button></div>',
			$vertical ? ' is-vertical' : '',
			$data,
			esc_url( $src ),
			esc_attr( 'audio' === $kind ? mcl_t( 'listen' ) : mcl_t( 'watch' ) ),
			$thumb ? '<img src="' . esc_url( $thumb ) . '" alt="" loading="lazy">' : '<span class="mcl-embed__noimg"></span>',
			mcl_icon( 'play' )
		);
	}

	// 4) Lecteurs audio connus (Spotify, Deezer, Audiomack) → iframe directe.
	if ( $src ) {
		$src = remove_query_arg( 'autoplay', $src );
		$h   = false !== strpos( $src, 'spotify' ) ? 152 : 160;
		return '<div class="mcl-embed mcl-embed--audio"' . $data . '><iframe src="' . esc_url( $src ) . '" height="' . $h . '" loading="lazy" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" frameborder="0"></iframe></div>';
	}

	// 5) oEmbed WordPress (SoundCloud, Mixcloud, TikTok, etc.).
	$html = wp_oembed_get( $url, array( 'width' => 900 ) );
	if ( $html ) {
		$cls = 'audio' === $kind ? 'mcl-embed--audio' : 'mcl-embed--oembed';
		if ( false !== stripos( $url, 'tiktok' ) ) {
			$cls = 'mcl-embed--tiktok';
		}
		return '<div class="mcl-embed ' . $cls . ( $vertical ? ' is-vertical' : '' ) . '"' . $data . '>' . $html . '</div>';
	}

	// 6) Dernier recours : bouton lien externe (Instagram, Facebook, Anghami…).
	$host = preg_replace( '/^www\./', '', (string) wp_parse_url( $url, PHP_URL_HOST ) );
	return sprintf(
		'<div class="mcl-embed mcl-embed--link"%1$s>%2$s<a class="mcl-btn mcl-btn--ghost mcl-countlink" href="%3$s" target="_blank" rel="noopener">%4$s <small>%5$s</small> ↗</a></div>',
		$data,
		$cover ? '<img src="' . esc_url( $cover ) . '" alt="" loading="lazy">' : '',
		esc_url( $url ),
		esc_html( 'audio' === $kind ? mcl_t( 'listen' ) : mcl_t( 'watch' ) ),
		esc_html( $host )
	);
}
