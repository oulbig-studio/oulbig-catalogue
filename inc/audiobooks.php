<?php
/**
 * 3e pôle : livres audio (à partir de livres, romans, ebooks) + option podcast.
 * Page dédiée (modèle « Livres audio & podcasts ») avec exemple de page de livre audio
 * et formulaire de devis enregistré dans « Demandes musique ».
 */
defined( 'ABSPATH' ) || exit;

function mcl_audio_url( $anchor = '' ) {
	$id  = (int) get_option( 'mcl_audio_page' );
	$url = ( $id && 'publish' === get_post_status( $id ) ) ? get_permalink( $id ) : home_url( '/' );
	return $url . ( $anchor ? '#' . $anchor : '' );
}

function mcl_setup_audio_page() {
	$id = (int) get_option( 'mcl_audio_page' );
	if ( $id && get_post( $id ) ) {
		return;
	}
	$id = wp_insert_post( array(
		'post_type'   => 'page',
		'post_status' => 'publish',
		'post_title'  => 'Livres audio & podcasts',
		'post_name'   => 'livres-audio',
	) );
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( $id, '_wp_page_template', 'page-templates/audiobooks.php' );
		update_option( 'mcl_audio_page', $id );
	}
}

/* Devis livre audio / podcast (AJAX) */
function mcl_submit_audio() {
	$lang = isset( $_POST['lang'] ) && mcl_valid_lang( sanitize_key( $_POST['lang'] ) ) ? sanitize_key( $_POST['lang'] ) : 'fr';
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'mcl_order' ) ) {
		wp_send_json_error( array( 'message' => mcl_t_in( $lang, 'err_server' ) . ' (nonce)' ), 403 );
	}
	if ( ! empty( $_POST['website'] ) ) {
		wp_send_json_success( array( 'ref' => 'MCL-00000' ) );
	}
	$started = isset( $_POST['started'] ) ? (int) $_POST['started'] : 0;
	if ( $started && ( time() - $started ) < 4 ) {
		wp_send_json_error( array( 'message' => mcl_t_in( $lang, 'err_server' ) ), 429 );
	}
	$p    = wp_unslash( $_POST );
	$pick = function ( $k, $group ) use ( $p ) {
		$v = isset( $p[ $k ] ) ? sanitize_key( $p[ $k ] ) : '';
		return array_key_exists( $v, mcl_options( $group ) ) ? $v : '';
	};
	$txt  = function ( $k, $max ) use ( $p ) {
		return isset( $p[ $k ] ) ? mb_substr( sanitize_text_field( $p[ $k ] ), 0, $max ) : '';
	};
	$type = $pick( 'type', 'ab_type' ) ?: 'audiobook';
	$d    = array(
		'type'      => $type,
		'form_lang' => $lang,
		'service'   => 'podcast' === $type ? 'Podcast' : 'Livre audio',
		'name'      => $txt( 'name', 120 ),
		'email'     => isset( $p['email'] ) ? sanitize_email( $p['email'] ) : '',
		'phone'     => $txt( 'phone', 40 ),
		'read_lang' => $pick( 'read_lang', 'song_lang' ),
		'narration' => $pick( 'narration', 'ab_voice' ),
		'files'     => isset( $p['files'] ) ? mb_substr( sanitize_textarea_field( $p['files'] ), 0, 1000 ) : '',
		'details'   => isset( $p['details'] ) ? mb_substr( sanitize_textarea_field( $p['details'] ), 0, 4000 ) : '',
	);
	if ( 'podcast' === $type ) {
		$d['pformat']  = $pick( 'pformat', 'ab_pformat' );
		$d['episodes'] = $txt( 'episodes', 160 );
	} else {
		$d['book_source'] = $pick( 'book_source', 'ab_source' );
		$d['book_size']   = $txt( 'book_size', 160 );
		$d['music']       = ! empty( $p['music'] ) ? 'Oui' : '';
	}
	if ( '' === $d['name'] ) {
		wp_send_json_error( array( 'message' => mcl_t_in( $lang, 'err_required' ) ), 422 );
	}
	if ( '' === $d['email'] && '' === $d['phone'] ) {
		wp_send_json_error( array( 'message' => mcl_t_in( $lang, 'err_contact' ) ), 422 );
	}
	if ( ! empty( $p['email'] ) && ! is_email( $d['email'] ) ) {
		wp_send_json_error( array( 'message' => mcl_t_in( $lang, 'err_email' ) ), 422 );
	}
	$post_id = wp_insert_post( array(
		'post_type'   => 'mcl_request',
		'post_status' => 'publish',
		'post_title'  => $d['name'] . ' — ' . $d['service'],
	), true );
	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( array( 'message' => mcl_t_in( $lang, 'err_server' ) ), 500 );
	}
	foreach ( $d as $k => $v ) {
		update_post_meta( $post_id, '_mcl_r_' . $k, $v );
	}
	update_post_meta( $post_id, '_mcl_status', 'new' );
	mcl_mail_request( $post_id );
	wp_send_json_success( array( 'ref' => mcl_request_ref( $post_id ) ) );
}
add_action( 'wp_ajax_mcl_audio', 'mcl_submit_audio' );
add_action( 'wp_ajax_nopriv_mcl_audio', 'mcl_submit_audio' );
