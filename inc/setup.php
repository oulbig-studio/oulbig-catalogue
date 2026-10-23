<?php
defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 240, 'flex-width' => true, 'flex-height' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	add_image_size( 'mcl-cover', 600, 600, true );
	add_image_size( 'mcl-wide', 1280, 720, true );
} );

/* ---------- Types de contenu ---------- */
add_action( 'init', function () {
	register_post_type( 'mcl_song', array(
		'labels'        => array(
			'name'          => 'Chansons & clips',
			'singular_name' => 'Chanson',
			'add_new'       => 'Ajouter un titre',
			'add_new_item'  => 'Ajouter une chanson / un clip',
			'edit_item'     => 'Modifier le titre',
			'all_items'     => 'Tous les titres',
			'menu_name'     => 'Musicology',
		),
		'public'        => true,
		'has_archive'   => true,
		'rewrite'       => array( 'slug' => 'chansons', 'with_front' => false ),
		'menu_icon'     => 'dashicons-format-audio',
		'menu_position' => 5,
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'show_in_rest'  => true,
	) );

	register_taxonomy( 'mcl_genre', 'mcl_song', array(
		'labels'            => array( 'name' => 'Genres', 'singular_name' => 'Genre', 'add_new_item' => 'Ajouter un genre' ),
		'hierarchical'      => true,
		'public'            => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => 'genre' ),
	) );
} );

/* ---------- Scripts & styles ---------- */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'mcl-fonts', 'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;0,800;1,500;1,700&family=Inter:wght@400;500;600;700&family=Cairo:wght@400;600;700;800&display=swap', array(), null );
	wp_enqueue_style( 'mcl-style', get_stylesheet_uri(), array( 'mcl-fonts' ), MCL_VERSION );
	wp_enqueue_script( 'mcl-main', MCL_URI . '/assets/js/main.js', array(), MCL_VERSION, true );
	wp_localize_script( 'mcl-main', 'MCL', array(
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'mcl_play' ),
		'lang'  => mcl_lang(),
	) );
} );

add_action( 'wp_head', function () {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
	echo '<meta name="theme-color" content="#0e0b0a">' . "\n";
}, 1 );

/* ---------- Helpers ---------- */
function mcl_num( $n ) {
	$n = (int) $n;
	if ( $n >= 1000000 ) {
		return round( $n / 1000000, 1 ) . 'M';
	}
	if ( $n >= 10000 ) {
		return round( $n / 1000, 1 ) . 'k';
	}
	return number_format_i18n( $n );
}

/** Image de couverture : image mise en avant, sinon miniature YouTube. */
function mcl_cover_url( $post_id, $size = 'mcl-cover' ) {
	if ( has_post_thumbnail( $post_id ) ) {
		return get_the_post_thumbnail_url( $post_id, $size );
	}
	$yt = mcl_youtube_id( get_post_meta( $post_id, '_mcl_video_url', true ) );
	if ( ! $yt ) {
		$yt = mcl_youtube_id( get_post_meta( $post_id, '_mcl_audio_url', true ) );
	}
	return $yt ? 'https://i.ytimg.com/vi/' . $yt . '/hqdefault.jpg' : '';
}

/** Initiales + teinte stable pour les pochettes générées. */
function mcl_placeholder_cover( $post_id ) {
	$title = get_the_title( $post_id );
	$hue   = hexdec( substr( md5( $title ), 0, 2 ) ) % 26 + 14; // tons braise / ambre (14–40°).
	$ini   = mb_strtoupper( mb_substr( trim( $title ), 0, 1 ) );
	return '<div class="mcl-ph" style="--h:' . (int) $hue . '"><span>' . esc_html( $ini ) . '</span><i class="mcl-ph__wave" aria-hidden="true"></i></div>';
}

function mcl_has_audio( $id ) {
	return '' !== trim( (string) get_post_meta( $id, '_mcl_audio_url', true ) );
}
function mcl_has_video( $id ) {
	return '' !== trim( (string) get_post_meta( $id, '_mcl_video_url', true ) );
}

function mcl_song_artist( $id ) {
	$a = get_post_meta( $id, '_mcl_artist', true );
	return $a ? $a : get_bloginfo( 'name' );
}

function mcl_song_genre( $id ) {
	$terms = get_the_terms( $id, 'mcl_genre' );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
}

function mcl_clips_url() {
	return add_query_arg( 'media', 'video', get_post_type_archive_link( 'mcl_song' ) );
}

function mcl_charts_url() {
	$id = (int) get_option( 'mcl_charts_page' );
	return ( $id && 'publish' === get_post_status( $id ) ) ? get_permalink( $id ) : home_url( '/' );
}

/** Filtres d'archive : ?media=audio|video, ?q=… */
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_post_type_archive( 'mcl_song' ) || $q->is_tax( 'mcl_genre' ) ) {
		$q->set( 'posts_per_page', 12 );
		$media = isset( $_GET['media'] ) ? sanitize_key( $_GET['media'] ) : '';
		if ( in_array( $media, array( 'audio', 'video' ), true ) ) {
			$q->set( 'meta_query', array(
				array( 'key' => '_mcl_' . $media . '_url', 'value' => '', 'compare' => '!=' ),
			) );
		}
		if ( ! empty( $_GET['q'] ) ) {
			$q->set( 's', sanitize_text_field( wp_unslash( $_GET['q'] ) ) );
		}
	}
} );

/** Titre de document : on ajoute le titre traduit des chansons. */
add_filter( 'document_title_parts', function ( $parts ) {
	if ( is_singular( 'mcl_song' ) ) {
		$parts['title'] = mcl_song_title( get_queried_object_id() );
	}
	return $parts;
} );

/** Pagination traduite. */
function mcl_pagination() {
	$links = paginate_links( array(
		'type'      => 'array',
		'prev_text' => '‹ ' . mcl_t( 'prev' ),
		'next_text' => mcl_t( 'next_page' ) . ' ›',
	) );
	if ( $links ) {
		echo '<nav class="mcl-pagination">' . implode( '', $links ) . '</nav>'; // phpcs:ignore
	}
}

/** Icônes SVG inline. */
function mcl_icon( $name ) {
	$i = array(
		'play'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5.5v13a1 1 0 0 0 1.5.86l10.4-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5z" fill="currentColor"/></svg>',
		'pause'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 5h3.5v14H7zM13.5 5H17v14h-3.5z" fill="currentColor"/></svg>',
		'audio'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18V6l11-2v12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="6" cy="18" r="3" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17" cy="16" r="3" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
		'video'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2.5" y="5" width="14" height="14" rx="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M16.5 10.5l5-3v9l-5-3z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
		'lyrics' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 6h14M5 11h14M5 16h9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
		'mail'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M4 7l8 6 8-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
		'phone'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 3h3l1.5 4.5-2.2 1.4a11 11 0 0 0 6.2 6.2l1.4-2.2L21 14.4v3A2.6 2.6 0 0 1 18.4 20 15.4 15.4 0 0 1 4 5.6 2.6 2.6 0 0 1 6.6 3z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
		'pin'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="9.5" r="2.5" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
		'gift'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11h16v9H4zM3 7h18v4H3zM12 7v13M12 7S10.5 3 8 3.5 7 7 12 7zm0 0s1.5-4 4-3.5S17 7 12 7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
		'arrow'  => '<svg viewBox="0 0 24 24" aria-hidden="true" class="mcl-flip"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'back'   => '<svg viewBox="0 0 24 24" aria-hidden="true" class="mcl-flip"><path d="M19 12H5M11 6l-6 6 6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'lock'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
		'check'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'mic'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="3" width="6" height="11" rx="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M5.5 11a6.5 6.5 0 0 0 13 0M12 17.5V21" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
		'pen'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20l4-1 11-11-3-3L5 16z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
		'globe'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9S14.5 18.4 12 21c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3z" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
		'search' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/><path d="M20 20l-4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
		'plus'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>',
		'trophy' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4h8v5a4 4 0 0 1-8 0zM8 6H4.5a3 3 0 0 0 3.5 4M16 6h3.5a3 3 0 0 1-3.5 4M12 13v4M8 20h8M9.5 17h5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'whatsapp' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20l1.2-3.6A8.5 8.5 0 1 1 8 19z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 8.5c0 3.5 3 6.5 6.5 6.5l1-1.6-2-1-1 .8a4.6 4.6 0 0 1-2.2-2.2l.8-1-1-2z" fill="currentColor"/></svg>',
	);
	return isset( $i[ $name ] ) ? '<span class="mcl-ico mcl-ico--' . esc_attr( $name ) . '">' . $i[ $name ] . '</span>' : '';
}
