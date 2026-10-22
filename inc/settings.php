<?php
/**
 * OULBIG Radio — réglages (Tableau de bord → OULBIG Radio → Réglages).
 *
 * @package OULBIG_Radio
 */

defined( 'ABSPATH' ) || exit;

const ORAD_LANGS = array( 'fr', 'en', 'es', 'ar', 'ma' );

function orad_lang_labels() {
	return array( 'fr' => 'Français', 'en' => 'English', 'es' => 'Español', 'ar' => 'Arabe (fusha)', 'ma' => 'Darija' );
}

function orad_defaults() {
	$jingles = array();
	foreach ( ORAD_LANGS as $l ) {
		$jingles[ $l ] = array( 'yt' => '', 'dur' => 8 );
	}
	return array(
		'skin'            => 'sombre',
		'catalogue_url'   => 'https://oulbig-studio.github.io/oulbig-catalogue/catalogue.json',
		'hit_mode'        => 'mixte',
		'report_threshold'=> 3,
		'moderate'        => 0,
		'allow_signup'    => 1,
		'ad_every'        => 6,
		'promo_every'     => 3,
		'jingles'         => $jingles,
		'ads'             => array(
			array( 'label' => 'OULBIG Radio', 'lang' => 'all', 'yt' => '', 'dur' => 30, 'url' => '' ),
			array( 'label' => 'Musicology', 'lang' => 'all', 'yt' => '', 'dur' => 30, 'url' => 'https://musicology.almoqayada.com' ),
			array( 'label' => '', 'lang' => 'all', 'yt' => '', 'dur' => 30, 'url' => '' ),
			array( 'label' => '', 'lang' => 'all', 'yt' => '', 'dur' => 30, 'url' => '' ),
		),
		'sponsors'        => array(
			array( 'name' => 'Musicology', 'url' => 'https://musicology.almoqayada.com', 'tagline' => '', 'accent' => '#FF3D6E' ),
			array( 'name' => 'ABSProjects', 'url' => '', 'tagline' => '', 'accent' => '#9D8BFF' ),
			array( 'name' => 'Almoqayada.com', 'url' => 'https://almoqayada.com', 'tagline' => '', 'accent' => '#3DDC97' ),
			array( 'name' => 'Almoqayada.pro', 'url' => 'https://almoqayada.pro', 'tagline' => '', 'accent' => '#FFB347' ),
			array( 'name' => '', 'url' => '', 'tagline' => '', 'accent' => '#FF3D6E' ),
		),
		'socials'         => array(
			'instagram' => 'https://www.instagram.com/oulbig_radio',
			'youtube'   => 'https://youtube.com/playlist?list=PLx1bfRrl4XWPEGJS3QzmR_NUvmIVtahKX',
			'facebook'  => 'https://www.facebook.com/share/18AcNQdDYA/',
			'x'         => '',
			'tiktok'    => '',
			'spotify'   => 'https://open.spotify.com/artist/2DHkzizRab1JFRb61bQAUi',
		),
		'artists'         => array(
			array( 'name' => 'OULBIG', 'youtube' => 'https://www.youtube.com/@OULBIG', 'youtube2' => 'https://www.youtube.com/channel/UCpWPGzDk5xXbE2fc48AeM8Q', 'spotify' => 'https://open.spotify.com/artist/2DHkzizRab1JFRb61bQAUi', 'color' => '#FF3D6E', 'tagline' => '' ),
			array( 'name' => 'TALIM', 'youtube' => 'https://www.youtube.com/@TALIM4Art', 'youtube2' => 'https://www.youtube.com/channel/UCqLXTRscQHOkMjI8RZzU7KQ', 'spotify' => 'https://open.spotify.com/artist/2FAKBSpY965IRet4aXTmEm', 'color' => '#9D8BFF', 'tagline' => '' ),
			array( 'name' => '', 'youtube' => '', 'youtube2' => '', 'spotify' => '', 'color' => '#3DDC97', 'tagline' => '' ),
		),
	);
}

function orad_settings() {
	$saved = get_option( 'orad_settings', array() );
	return orad_merge( orad_defaults(), is_array( $saved ) ? $saved : array() );
}

function orad_merge( $def, $val ) {
	foreach ( $def as $k => $d ) {
		if ( ! array_key_exists( $k, $val ) ) {
			$val[ $k ] = $d;
		} elseif ( is_array( $d ) && is_array( $val[ $k ] ) && ! isset( $d[0] ) ) {
			$val[ $k ] = orad_merge( $d, $val[ $k ] );
		}
	}
	return $val;
}

function orad_opt( $key ) {
	$s = orad_settings();
	return isset( $s[ $key ] ) ? $s[ $key ] : null;
}

/** Identifiant YouTube à partir d'un lien ou d'un ID. */
function orad_yt_id( $s ) {
	$s = trim( (string) $s );
	if ( preg_match( '/^[\w-]{11}$/', $s ) ) {
		return $s;
	}
	if ( preg_match( '~(?:v=|youtu\.be/|shorts/|embed/|live/)([\w-]{11})~', $s, $m ) ) {
		return $m[1];
	}
	return '';
}

function orad_sanitize( $in ) {
	$d   = orad_defaults();
	$out = array();
	$out['skin']             = in_array( $in['skin'] ?? '', array( 'sombre', 'clair', 'auto' ), true ) ? $in['skin'] : 'sombre';
	$out['catalogue_url']    = esc_url_raw( $in['catalogue_url'] ?? $d['catalogue_url'] );
	$out['hit_mode']         = in_array( $in['hit_mode'] ?? '', array( 'mixte', 'votes', 'vues' ), true ) ? $in['hit_mode'] : 'mixte';
	$out['report_threshold'] = max( 1, absint( $in['report_threshold'] ?? 3 ) );
	$out['moderate']         = empty( $in['moderate'] ) ? 0 : 1;
	$out['allow_signup']     = empty( $in['allow_signup'] ) ? 0 : 1;
	$out['ad_every']         = max( 2, absint( $in['ad_every'] ?? 6 ) );
	$out['promo_every']      = max( 2, absint( $in['promo_every'] ?? 3 ) );
	foreach ( ORAD_LANGS as $l ) {
		$j                       = $in['jingles'][ $l ] ?? array();
		$out['jingles'][ $l ] = array( 'yt' => orad_yt_id( $j['yt'] ?? '' ), 'dur' => max( 2, absint( $j['dur'] ?? 8 ) ) );
	}
	$out['ads'] = array();
	foreach ( (array) ( $in['ads'] ?? array() ) as $a ) {
		$lang          = $a['lang'] ?? 'all';
		$out['ads'][] = array(
			'label' => sanitize_text_field( $a['label'] ?? '' ),
			'lang'  => in_array( $lang, array_merge( array( 'all' ), ORAD_LANGS ), true ) ? $lang : 'all',
			'yt'    => orad_yt_id( $a['yt'] ?? '' ),
			'dur'   => max( 5, absint( $a['dur'] ?? 30 ) ),
			'url'   => esc_url_raw( $a['url'] ?? '' ),
		);
	}
	$out['sponsors'] = array();
	foreach ( (array) ( $in['sponsors'] ?? array() ) as $s ) {
		$out['sponsors'][] = array(
			'name'    => sanitize_text_field( $s['name'] ?? '' ),
			'url'     => esc_url_raw( $s['url'] ?? '' ),
			'tagline' => sanitize_text_field( $s['tagline'] ?? '' ),
			'accent'  => sanitize_hex_color( $s['accent'] ?? '' ) ? sanitize_hex_color( $s['accent'] ) : '#FF3D6E',
		);
	}
	foreach ( array_keys( $d['socials'] ) as $k ) {
		$out['socials'][ $k ] = esc_url_raw( $in['socials'][ $k ] ?? '' );
	}
	$out['artists'] = array();
	foreach ( (array) ( $in['artists'] ?? array() ) as $a ) {
		$out['artists'][] = array(
			'name'     => sanitize_text_field( $a['name'] ?? '' ),
			'youtube'  => esc_url_raw( $a['youtube'] ?? '' ),
			'youtube2' => esc_url_raw( $a['youtube2'] ?? '' ),
			'spotify'  => esc_url_raw( $a['spotify'] ?? '' ),
			'color'    => sanitize_hex_color( $a['color'] ?? '' ) ? sanitize_hex_color( $a['color'] ) : '#FF3D6E',
			'tagline'  => sanitize_text_field( $a['tagline'] ?? '' ),
		);
	}
	update_option( 'users_can_register', $out['allow_signup'] );
	if ( $out['allow_signup'] && 'subscriber' !== get_option( 'default_role' ) ) {
		update_option( 'default_role', 'subscriber' );
	}
	delete_transient( 'orad_catalogue' );
	return $out;
}

add_action( 'admin_init', function () {
	register_setting( 'orad_settings', 'orad_settings', array( 'type' => 'array', 'sanitize_callback' => 'orad_sanitize' ) );
} );

/** Configuration transmise au lecteur (window.ORAD_CONFIG). */
function orad_front_config() {
	$s    = orad_settings();
	$user = wp_get_current_user();
	$u    = null;
	if ( $user && $user->exists() ) {
		$u = array(
			'id'         => $user->ID,
			'name'       => $user->display_name,
			'canPublish' => user_can( $user, 'orad_publish' ),
			'requested'  => (bool) get_user_meta( $user->ID, 'orad_access_request', true ),
		);
	}
	$clean = function ( $list, $keys ) {
		$out = array();
		foreach ( $list as $row ) {
			if ( empty( $row['name'] ?? $row['label'] ?? '' ) ) {
				continue;
			}
			$r = array();
			foreach ( $keys as $k ) {
				if ( isset( $row[ $k ] ) && '' !== $row[ $k ] ) {
					$r[ $k ] = $row[ $k ];
				}
			}
			if ( isset( $row['url'] ) && ! isset( $r['url'] ) ) {
				$r['url'] = '';
			}
			$out[] = $r;
		}
		return $out;
	};
	return array(
		'mode'         => 'wp',
		'skin'         => $s['skin'],
		'catalogueUrl' => $s['catalogue_url'],
		'site'         => home_url( '/' ),
		'rest'         => esc_url_raw( rest_url( 'oulbig-radio/v1/' ) ),
		'nonce'        => wp_create_nonce( 'wp_rest' ),
		'user'         => $u,
		'loginUrl'     => wp_login_url( home_url( '/' ) ),
		'registerUrl'  => get_option( 'users_can_register' ) ? wp_registration_url() : wp_login_url( home_url( '/' ) ),
		'logoutUrl'    => wp_logout_url( home_url( '/' ) ),
		'hitMode'      => $s['hit_mode'],
		'adEvery'      => (int) $s['ad_every'],
		'promoEvery'   => (int) $s['promo_every'],
		'socials'      => $s['socials'],
		'jingles'      => $s['jingles'],
		'ads'          => array_values( array_filter( $s['ads'], function ( $a ) { return '' !== $a['label']; } ) ),
		'sponsors'     => $clean( $s['sponsors'], array( 'name', 'url', 'tagline', 'accent' ) ),
		'artists'      => $clean( $s['artists'], array( 'name', 'youtube', 'youtube2', 'spotify', 'color', 'tagline' ) ),
	);
}
