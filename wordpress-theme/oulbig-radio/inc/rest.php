<?php
/**
 * OULBIG Radio — API du lecteur : /wp-json/oulbig-radio/v1/…
 *
 * @package OULBIG_Radio
 */

defined( 'ABSPATH' ) || exit;

function orad_err( $code, $msg, $status = 400 ) {
	return new WP_Error( $code, $msg, array( 'status' => $status ) );
}
function orad_logged() {
	return is_user_logged_in();
}

add_action( 'rest_api_init', function () {
	$ns = 'oulbig-radio/v1';
	register_rest_route( $ns, '/bootstrap', array( 'methods' => 'GET', 'callback' => 'orad_rest_bootstrap', 'permission_callback' => '__return_true' ) );
	register_rest_route( $ns, '/like', array( 'methods' => 'POST', 'callback' => 'orad_rest_like', 'permission_callback' => 'orad_logged' ) );
	register_rest_route( $ns, '/comments', array(
		array( 'methods' => 'GET', 'callback' => 'orad_rest_comments', 'permission_callback' => '__return_true' ),
		array( 'methods' => 'POST', 'callback' => 'orad_rest_comment_add', 'permission_callback' => 'orad_logged' ),
	) );
	register_rest_route( $ns, '/report', array( 'methods' => 'POST', 'callback' => 'orad_rest_report', 'permission_callback' => 'orad_logged' ) );
	register_rest_route( $ns, '/playlists', array(
		array( 'methods' => 'GET', 'callback' => 'orad_rest_playlists', 'permission_callback' => 'orad_logged' ),
		array( 'methods' => 'POST', 'callback' => 'orad_rest_playlist_save', 'permission_callback' => 'orad_logged' ),
	) );
	register_rest_route( $ns, '/playlists/public', array( 'methods' => 'GET', 'callback' => 'orad_rest_playlists_public', 'permission_callback' => '__return_true' ) );
	register_rest_route( $ns, '/playlists/(?P<id>\d+)', array( 'methods' => 'DELETE', 'callback' => 'orad_rest_playlist_delete', 'permission_callback' => 'orad_logged' ) );
	register_rest_route( $ns, '/access', array( 'methods' => 'POST', 'callback' => 'orad_rest_access', 'permission_callback' => 'orad_logged' ) );
	register_rest_route( $ns, '/submit', array( 'methods' => 'POST', 'callback' => 'orad_rest_submit', 'permission_callback' => function () { return current_user_can( 'orad_publish' ); } ) );
} );

function orad_rest_bootstrap() {
	global $wpdb;
	$uid   = get_current_user_id();
	$likes = orad_like_counts();
	$hit   = get_transient( 'orad_hit_likes' );
	if ( ! is_array( $hit ) ) {
		$hit = $likes;
		set_transient( 'orad_hit_likes', $hit, HOUR_IN_SECONDS ); // instantané horaire : même Hit Parade pour tous
	}
	$mine = $uid ? $wpdb->get_col( $wpdb->prepare( 'SELECT track FROM ' . orad_table( 'likes' ) . ' WHERE user_id = %d', $uid ) ) : array();
	$com  = array();
	foreach ( (array) $wpdb->get_results( "SELECT track, COUNT(*) AS n FROM " . orad_table( 'comments' ) . " WHERE status = 'approved' GROUP BY track", ARRAY_A ) as $r ) {
		$com[ $r['track'] ] = (int) $r['n'];
	}
	$user = null;
	if ( $uid ) {
		$u    = wp_get_current_user();
		$user = array( 'id' => $uid, 'name' => $u->display_name, 'canPublish' => current_user_can( 'orad_publish' ), 'requested' => (bool) get_user_meta( $uid, 'orad_access_request', true ) );
	}
	return array(
		'user'      => $user,
		'likes'     => (object) $likes,
		'mine'      => array_values( $mine ),
		'comments'  => (object) $com,
		'overrides' => (object) orad_overrides(),
		'extra'     => orad_extra_tracks(),
		'hitLikes'  => (object) $hit,
	);
}

function orad_rest_like( WP_REST_Request $r ) {
	global $wpdb;
	$t = $r->get_param( 'track' );
	if ( ! orad_valid_track( $t ) ) {
		return orad_err( 'orad_track', 'Piste inconnue.' );
	}
	$uid = get_current_user_id();
	$tbl = orad_table( 'likes' );
	$has = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tbl WHERE user_id = %d AND track = %s", $uid, $t ) );
	if ( $has ) {
		$wpdb->delete( $tbl, array( 'user_id' => $uid, 'track' => $t ), array( '%d', '%s' ) );
	} else {
		$wpdb->insert( $tbl, array( 'user_id' => $uid, 'track' => $t, 'created' => current_time( 'mysql', true ) ), array( '%d', '%s', '%s' ) );
	}
	$n = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tbl WHERE track = %s", $t ) );
	return array( 'liked' => ! $has, 'count' => $n );
}

function orad_rest_comments( WP_REST_Request $r ) {
	global $wpdb;
	$t = $r->get_param( 'track' );
	if ( ! orad_valid_track( $t ) ) {
		return orad_err( 'orad_track', 'Piste inconnue.' );
	}
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, user_id, text, lang, created FROM " . orad_table( 'comments' ) . " WHERE track = %s AND status = 'approved' ORDER BY id ASC LIMIT 200", $t ), ARRAY_A );
	$out  = array();
	foreach ( (array) $rows as $c ) {
		$u     = get_userdata( (int) $c['user_id'] );
		$out[] = array( 'id' => (int) $c['id'], 'name' => $u ? $u->display_name : '—', 'text' => $c['text'], 'lang' => $c['lang'], 'date' => mysql_to_rfc3339( $c['created'] ) );
	}
	return $out;
}

function orad_rest_comment_add( WP_REST_Request $r ) {
	global $wpdb;
	$t    = $r->get_param( 'track' );
	$text = trim( sanitize_textarea_field( (string) $r->get_param( 'text' ) ) );
	$lang = (string) $r->get_param( 'lang' );
	if ( ! orad_valid_track( $t ) ) {
		return orad_err( 'orad_track', 'Piste inconnue.' );
	}
	if ( '' === $text || mb_strlen( $text ) > 600 ) {
		return orad_err( 'orad_text', 'Le commentaire doit faire entre 1 et 600 caractères.' );
	}
	$uid = get_current_user_id();
	if ( get_transient( 'orad_c_' . $uid ) ) {
		return orad_err( 'orad_wait', 'Patiente quelques secondes avant de commenter à nouveau.', 429 );
	}
	set_transient( 'orad_c_' . $uid, 1, 15 );
	$status = orad_opt( 'moderate' ) && ! current_user_can( 'moderate_comments' ) ? 'pending' : 'approved';
	$wpdb->insert( orad_table( 'comments' ), array(
		'track'   => $t,
		'user_id' => $uid,
		'text'    => $text,
		'lang'    => in_array( $lang, ORAD_LANGS, true ) ? $lang : '',
		'status'  => $status,
		'created' => current_time( 'mysql', true ),
	) );
	$u = wp_get_current_user();
	return array( 'id' => (int) $wpdb->insert_id, 'name' => $u->display_name, 'text' => $text, 'lang' => $lang, 'date' => gmdate( 'c' ), 'status' => $status );
}

function orad_rest_report( WP_REST_Request $r ) {
	global $wpdb;
	$t    = $r->get_param( 'track' );
	$lang = (string) $r->get_param( 'lang' );
	if ( ! orad_valid_track( $t ) || ! in_array( $lang, ORAD_LANGS, true ) ) {
		return orad_err( 'orad_report', 'Signalement invalide.' );
	}
	$uid = get_current_user_id();
	$tbl = orad_table( 'reports' );
	$wpdb->delete( $tbl, array( 'user_id' => $uid, 'track' => $t ), array( '%d', '%s' ) );
	$wpdb->insert( $tbl, array( 'user_id' => $uid, 'track' => $t, 'lang' => $lang, 'created' => current_time( 'mysql', true ) ) );
	$n       = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tbl WHERE track = %s AND lang = %s", $t, $lang ) );
	$applied = current_user_can( 'manage_options' ) || $n >= (int) orad_opt( 'report_threshold' );
	if ( $applied ) {
		$o       = orad_overrides();
		$o[ $t ] = $lang;
		update_option( 'orad_overrides', $o, false );
	}
	return array( 'applied' => $applied, 'lang' => $lang, 'count' => $n );
}

function orad_playlist_row( $p, $owner = false ) {
	$row = array( 'id' => (string) $p['id'], 'name' => $p['name'], 'public' => (bool) $p['public'], 'tracks' => json_decode( $p['tracks'], true ) ?: array() );
	if ( $owner ) {
		$u            = get_userdata( (int) $p['user_id'] );
		$row['owner'] = $u ? $u->display_name : '';
	}
	return $row;
}

function orad_rest_playlists() {
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . orad_table( 'playlists' ) . ' WHERE user_id = %d ORDER BY updated DESC', get_current_user_id() ), ARRAY_A );
	return array_map( 'orad_playlist_row', (array) $rows );
}

function orad_rest_playlists_public() {
	global $wpdb;
	$rows = $wpdb->get_results( 'SELECT * FROM ' . orad_table( 'playlists' ) . ' WHERE public = 1 ORDER BY updated DESC LIMIT 60', ARRAY_A );
	return array_map( function ( $p ) { return orad_playlist_row( $p, true ); }, (array) $rows );
}

function orad_rest_playlist_save( WP_REST_Request $r ) {
	global $wpdb;
	$uid    = get_current_user_id();
	$tbl    = orad_table( 'playlists' );
	$name   = mb_substr( trim( sanitize_text_field( (string) $r->get_param( 'name' ) ) ), 0, 80 );
	$tracks = array_values( array_unique( array_filter( (array) $r->get_param( 'tracks' ), 'orad_valid_track' ) ) );
	$tracks = array_slice( $tracks, 0, 500 );
	if ( '' === $name ) {
		return orad_err( 'orad_name', 'Donne un nom à ta playlist.' );
	}
	$data = array( 'name' => $name, 'public' => $r->get_param( 'public' ) ? 1 : 0, 'tracks' => wp_json_encode( $tracks ), 'updated' => current_time( 'mysql', true ) );
	$id   = absint( $r->get_param( 'id' ) );
	if ( $id ) {
		$own = (int) $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $tbl WHERE id = %d", $id ) );
		if ( $own !== $uid ) {
			return orad_err( 'orad_forbidden', 'Cette playlist ne t’appartient pas.', 403 );
		}
		$wpdb->update( $tbl, $data, array( 'id' => $id ) );
	} else {
		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tbl WHERE user_id = %d", $uid ) );
		if ( $count >= 50 ) {
			return orad_err( 'orad_limit', 'Limite de 50 playlists atteinte.' );
		}
		$data['user_id'] = $uid;
		$wpdb->insert( $tbl, $data );
		$id = (int) $wpdb->insert_id;
	}
	return orad_playlist_row( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tbl WHERE id = %d", $id ), ARRAY_A ) );
}

function orad_rest_playlist_delete( WP_REST_Request $r ) {
	global $wpdb;
	$n = $wpdb->delete( orad_table( 'playlists' ), array( 'id' => absint( $r['id'] ), 'user_id' => get_current_user_id() ), array( '%d', '%d' ) );
	return $n ? array( 'deleted' => true ) : orad_err( 'orad_forbidden', 'Playlist introuvable.', 404 );
}

function orad_rest_access() {
	$uid = get_current_user_id();
	if ( ! get_user_meta( $uid, 'orad_access_request', true ) ) {
		update_user_meta( $uid, 'orad_access_request', time() );
		$u = wp_get_current_user();
		wp_mail( get_option( 'admin_email' ), 'OULBIG Radio : demande de publication', sprintf( "%s (%s) demande l'accès à la publication.\n\nAccorder : %s", $u->display_name, $u->user_email, admin_url( 'admin.php?page=orad-access' ) ) );
	}
	return array( 'requested' => true );
}

function orad_rest_submit( WP_REST_Request $r ) {
	$yt    = orad_yt_id( (string) $r->get_param( 'url' ) );
	$title = mb_substr( trim( sanitize_text_field( (string) $r->get_param( 'title' ) ) ), 0, 140 );
	$art   = mb_substr( trim( sanitize_text_field( (string) $r->get_param( 'artist' ) ) ), 0, 80 );
	$lang  = (string) $r->get_param( 'lang' );
	$type  = 'podcast' === $r->get_param( 'type' ) ? 'podcast' : 'song';
	if ( ! $yt || '' === $title || ! in_array( $lang, ORAD_LANGS, true ) ) {
		return orad_err( 'orad_submit', 'Lien YouTube, titre et langue sont obligatoires.' );
	}
	$id = wp_insert_post( array( 'post_type' => 'orad_submission', 'post_status' => 'pending', 'post_title' => $title, 'post_author' => get_current_user_id() ), true );
	if ( is_wp_error( $id ) ) {
		return orad_err( 'orad_submit', 'Envoi impossible pour le moment.', 500 );
	}
	update_post_meta( $id, 'orad_yt', $yt );
	update_post_meta( $id, 'orad_artist', $art ? $art : wp_get_current_user()->display_name );
	update_post_meta( $id, 'orad_lang', $lang );
	update_post_meta( $id, 'orad_type', $type );
	return array( 'status' => 'pending', 'id' => $id );
}

/** Titres validés (soumissions publiées) ajoutés à la grille. */
function orad_extra_tracks() {
	$out = array();
	$q   = get_posts( array( 'post_type' => 'orad_submission', 'post_status' => 'publish', 'numberposts' => 300, 'orderby' => 'date', 'order' => 'DESC' ) );
	foreach ( $q as $p ) {
		$out[] = array(
			'yt'     => get_post_meta( $p->ID, 'orad_yt', true ),
			'title'  => $p->post_title,
			'artist' => get_post_meta( $p->ID, 'orad_artist', true ),
			'lang'   => get_post_meta( $p->ID, 'orad_lang', true ),
			'type'   => get_post_meta( $p->ID, 'orad_type', true ),
			'dur'    => (int) get_post_meta( $p->ID, 'orad_dur', true ),
			'date'   => get_the_date( 'Y-m-d', $p ),
		);
	}
	return $out;
}
