<?php
/**
 * Blog : langue par article, filtre par langue du visiteur, temps de lecture,
 * articles de départ avec images.
 */
defined( 'ABSPATH' ) || exit;

require MCL_DIR . '/inc/blog-data.php';

function mcl_blog_url() {
	$id = (int) get_option( 'page_for_posts' );
	return $id ? get_permalink( $id ) : home_url( '/?post_type=post' );
}

function mcl_post_lang( $id ) {
	$l = get_post_meta( $id, '_mcl_lang', true );
	return mcl_valid_lang( $l ) ? $l : '';
}

function mcl_reading_time( $id ) {
	$words = count( preg_split( '/\s+/u', trim( wp_strip_all_tags( get_post_field( 'post_content', $id ) ) ) ) );
	return max( 1, (int) ceil( $words / 200 ) );
}

/** Date de l'article dans la langue du visiteur. */
function mcl_date( $id = null ) {
	$ts = get_post_time( 'U', false, $id );
	$m  = array(
		'fr' => array( 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre' ),
		'en' => array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ),
		'es' => array( 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre' ),
		'ar' => array( 'يناير', 'فبراير', 'مارس', 'أبريل', 'ماي', 'يونيو', 'يوليوز', 'غشت', 'شتنبر', 'أكتوبر', 'نونبر', 'دجنبر' ),
	);
	$l   = mcl_lang();
	$d   = (int) wp_date( 'j', $ts );
	$mon = $m[ $l ][ (int) wp_date( 'n', $ts ) - 1 ];
	$y   = wp_date( 'Y', $ts );
	if ( 'en' === $l ) {
		return $mon . ' ' . $d . ', ' . $y;
	}
	if ( 'es' === $l ) {
		return $d . ' de ' . $mon . ' de ' . $y;
	}
	return $d . ' ' . $mon . ' ' . $y;
}

/** meta_query : articles de la langue courante + articles « toutes langues ». */
function mcl_blog_lang_meta_query() {
	$lang = mcl_lang();
	static $has = array();
	if ( ! isset( $has[ $lang ] ) ) {
		$has[ $lang ] = (bool) get_posts( array( 'post_type' => 'post', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_mcl_lang', 'meta_value' => $lang, 'suppress_filters' => true ) );
	}
	if ( ! $has[ $lang ] ) {
		return array(); // aucun article dans cette langue : on montre tout.
	}
	return array(
		'relation' => 'OR',
		array( 'key' => '_mcl_lang', 'value' => $lang ),
		array( 'key' => '_mcl_lang', 'value' => '' ),
		array( 'key' => '_mcl_lang', 'compare' => 'NOT EXISTS' ),
	);
}

add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_home() || $q->is_category() || $q->is_tag() ) {
		$q->set( 'posts_per_page', 9 );
		$mq = mcl_blog_lang_meta_query();
		if ( $mq ) {
			$q->set( 'meta_query', array( $mq ) );
		}
	}
}, 20 );

/** Derniers articles pour l'accueil / les suggestions. */
function mcl_latest_posts( $n = 3, $exclude = array(), $cats = array() ) {
	$args = array( 'post_type' => 'post', 'posts_per_page' => $n, 'post__not_in' => $exclude, 'ignore_sticky_posts' => true );
	$mq   = mcl_blog_lang_meta_query();
	if ( $mq ) {
		$args['meta_query'] = array( $mq );
	}
	if ( $cats ) {
		$args['category__in'] = $cats;
	}
	return new WP_Query( $args );
}

/* Métabox langue de l'article */
add_action( 'add_meta_boxes_post', function () {
	add_meta_box( 'mcl_post_lang', '🌍 Langue de l\'article', function ( $post ) {
		wp_nonce_field( 'mcl_post_lang', 'mcl_post_lang_nonce' );
		$cur = get_post_meta( $post->ID, '_mcl_lang', true );
		echo '<select name="_mcl_lang" class="widefat"><option value="">Toutes les langues</option>';
		foreach ( mcl_langs() as $c => $l ) {
			echo '<option value="' . esc_attr( $c ) . '" ' . selected( $cur, $c, false ) . '>' . esc_html( $l['label'] ) . '</option>';
		}
		echo '</select><p class="description">Le blog montre à chaque visiteur les articles de sa langue (et ceux « Toutes les langues »).</p>';
	}, 'post', 'side', 'high' );
} );
add_action( 'save_post_post', function ( $post_id ) {
	if ( ! isset( $_POST['mcl_post_lang_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mcl_post_lang_nonce'] ) ), 'mcl_post_lang' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$l = isset( $_POST['_mcl_lang'] ) ? sanitize_key( $_POST['_mcl_lang'] ) : '';
	update_post_meta( $post_id, '_mcl_lang', mcl_valid_lang( $l ) ? $l : '' );
} );
add_filter( 'manage_post_posts_columns', function ( $c ) {
	$c['mcl_lang'] = 'Langue';
	return $c;
} );
add_action( 'manage_post_posts_custom_column', function ( $col, $id ) {
	if ( 'mcl_lang' === $col ) {
		$l = mcl_post_lang( $id );
		echo $l ? esc_html( mcl_langs()[ $l ]['label'] ) : '—';
	}
}, 10, 2 );

/* ---------- Installation : pages Accueil / Blog + articles ---------- */
function mcl_setup_blog_pages() {
	if ( 'page' === get_option( 'show_on_front' ) && get_option( 'page_for_posts' ) ) {
		return;
	}
	$home = get_page_by_path( 'accueil' );
	$home = $home ? $home->ID : wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Accueil', 'post_name' => 'accueil' ) );
	$blog = get_page_by_path( 'blog' );
	$blog = $blog ? $blog->ID : wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Blog', 'post_name' => 'blog' ) );
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home );
	update_option( 'page_for_posts', $blog );
}

function mcl_import_image( $file, $parent = 0 ) {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$src = MCL_DIR . '/assets/demo/' . $file;
	if ( ! file_exists( $src ) ) {
		return 0;
	}
	$up = wp_upload_bits( 'musicology-' . $file, null, file_get_contents( $src ) ); // phpcs:ignore
	if ( ! empty( $up['error'] ) ) {
		return 0;
	}
	$aid = wp_insert_attachment( array( 'post_mime_type' => 'image/jpeg', 'post_title' => 'Musicology', 'post_status' => 'inherit' ), $up['file'], $parent );
	if ( is_wp_error( $aid ) ) {
		return 0;
	}
	wp_update_attachment_metadata( $aid, wp_generate_attachment_metadata( $aid, $up['file'] ) );
	update_post_meta( $aid, '_mcl_demo', 1 );
	return $aid;
}

function mcl_seed_blog() {
	// Supprime l'article « Hello world! » d'origine s'il n'a pas été modifié.
	$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
	if ( $hello && $hello->post_modified === $hello->post_date ) {
		wp_delete_post( $hello->ID, true );
	}
	foreach ( mcl_blog_seed() as $a ) {
		$cat = term_exists( $a['cat'], 'category' );
		if ( ! $cat ) {
			$cat = wp_insert_term( $a['cat'], 'category' );
		}
		$cat_id = is_array( $cat ) ? (int) $cat['term_id'] : (int) $cat;
		$id     = wp_insert_post( array(
			'post_type'     => 'post',
			'post_status'   => 'publish',
			'post_title'    => $a['title'],
			'post_name'     => isset( $a['slug'] ) ? $a['slug'] : '',
			'post_excerpt'  => $a['excerpt'],
			'post_content'  => $a['content'],
			'post_date'     => gmdate( 'Y-m-d H:i:s', time() - $a['days'] * DAY_IN_SECONDS ),
			'post_category' => array( $cat_id ),
		) );
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		update_post_meta( $id, '_mcl_lang', $a['lang'] );
		update_post_meta( $id, '_mcl_demo', 1 );
		$img = mcl_import_image( 'blog-' . $a['img'] . '.jpg', $id );
		if ( $img ) {
			set_post_thumbnail( $id, $img );
		}
	}
}
