<?php
/**
 * OULBIG Radio — tables (likes, commentaires, signalements de langue, playlists) et rôle artiste.
 *
 * @package OULBIG_Radio
 */

defined( 'ABSPATH' ) || exit;

const ORAD_DB_VERSION = '2.0.0';

function orad_table( $name ) {
	global $wpdb;
	return $wpdb->prefix . 'orad_' . $name;
}

function orad_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$c = $wpdb->get_charset_collate();
	dbDelta( "CREATE TABLE " . orad_table( 'likes' ) . " (
		user_id bigint(20) unsigned NOT NULL,
		track varchar(48) NOT NULL,
		created datetime NOT NULL,
		PRIMARY KEY  (user_id,track),
		KEY track (track)
	) $c;" );
	dbDelta( "CREATE TABLE " . orad_table( 'comments' ) . " (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		track varchar(48) NOT NULL,
		user_id bigint(20) unsigned NOT NULL,
		text text NOT NULL,
		lang varchar(5) NOT NULL DEFAULT '',
		status varchar(10) NOT NULL DEFAULT 'approved',
		created datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY track (track)
	) $c;" );
	dbDelta( "CREATE TABLE " . orad_table( 'reports' ) . " (
		user_id bigint(20) unsigned NOT NULL,
		track varchar(48) NOT NULL,
		lang varchar(5) NOT NULL,
		created datetime NOT NULL,
		PRIMARY KEY  (user_id,track),
		KEY track (track)
	) $c;" );
	dbDelta( "CREATE TABLE " . orad_table( 'playlists' ) . " (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		user_id bigint(20) unsigned NOT NULL,
		name varchar(80) NOT NULL,
		public tinyint(1) NOT NULL DEFAULT 0,
		tracks longtext NOT NULL,
		updated datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY user_id (user_id),
		KEY public (public)
	) $c;" );

	if ( ! get_role( 'orad_artist' ) ) {
		add_role( 'orad_artist', 'Artiste OULBIG Radio', array( 'read' => true, 'orad_publish' => true ) );
	}
	$admin = get_role( 'administrator' );
	if ( $admin && ! $admin->has_cap( 'orad_publish' ) ) {
		$admin->add_cap( 'orad_publish' );
	}
	update_option( 'orad_db_version', ORAD_DB_VERSION );
}

add_action( 'after_switch_theme', function () {
	orad_install();
	if ( orad_opt( 'allow_signup' ) ) {
		update_option( 'users_can_register', 1 );
		update_option( 'default_role', 'subscriber' );
	}
} );
add_action( 'init', function () {
	if ( get_option( 'orad_db_version' ) !== ORAD_DB_VERSION ) {
		orad_install();
	}
} );

/** Identifiant de piste accepté : yt-XXXXXXXXXXX, sp-…, tx-… ou ancien format fr-s-… */
function orad_valid_track( $t ) {
	return is_string( $t ) && preg_match( '/^[a-z]{2}-[A-Za-z0-9_-]{3,44}$/', $t );
}

/** Catalogue (pour les titres dans l'administration), mis en cache une heure. */
function orad_catalogue_titles() {
	$map = get_transient( 'orad_catalogue' );
	if ( is_array( $map ) ) {
		return $map;
	}
	$map = array();
	$res = wp_remote_get( orad_opt( 'catalogue_url' ), array( 'timeout' => 15 ) );
	if ( ! is_wp_error( $res ) && 200 === wp_remote_retrieve_response_code( $res ) ) {
		$cat = json_decode( wp_remote_retrieve_body( $res ), true );
		foreach ( (array) ( $cat['items'] ?? array() ) as $i ) {
			if ( empty( $i['youtube']['id'] ) ) {
				continue;
			}
			$map[ 'yt-' . $i['youtube']['id'] ] = array( 'title' => $i['title'], 'artist' => $i['artist'] ?? 'OULBIG', 'lang' => $i['lang'] );
		}
	}
	set_transient( 'orad_catalogue', $map, HOUR_IN_SECONDS );
	return $map;
}

/** Corrections de langue en vigueur : { piste: langue }. */
function orad_overrides() {
	$o = get_option( 'orad_overrides', array() );
	return is_array( $o ) ? $o : array();
}

function orad_like_counts() {
	global $wpdb;
	$rows = $wpdb->get_results( 'SELECT track, COUNT(*) AS n FROM ' . orad_table( 'likes' ) . ' GROUP BY track', ARRAY_A );
	$out  = array();
	foreach ( (array) $rows as $r ) {
		$out[ $r['track'] ] = (int) $r['n'];
	}
	return $out;
}
