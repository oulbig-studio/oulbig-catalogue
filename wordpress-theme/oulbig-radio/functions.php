<?php
/**
 * OULBIG Radio — thème WordPress v2.0.
 *
 * @package OULBIG_Radio
 */

defined( 'ABSPATH' ) || exit;

define( 'ORAD_VERSION', '2.0.1' );

require_once __DIR__ . '/inc/settings.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/rest.php';
require_once __DIR__ . '/inc/admin.php';

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
} );

/* Manifeste d'application (installation sur téléphone) : /?oulbig_manifest=1 */
add_action( 'init', function () {
	if ( empty( $_GET['oulbig_manifest'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$clair = 'clair' === orad_opt( 'skin' );
	$bg    = $clair ? '#F6F4FB' : '#0B0916';
	header( 'Content-Type: application/manifest+json; charset=utf-8' );
	echo wp_json_encode( array(
		'name'             => 'OULBIG Radio',
		'short_name'       => 'OULBIG Radio',
		'description'      => "La web radio d'OULBIG en 5 langues.",
		'start_url'        => home_url( '/' ),
		'scope'            => home_url( '/' ),
		'display'          => 'standalone',
		'orientation'      => 'portrait',
		'background_color' => $bg,
		'theme_color'      => $bg,
		'icons'            => array(
			array( 'src' => get_template_directory_uri() . '/assets/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any' ),
			array( 'src' => get_template_directory_uri() . '/assets/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable' ),
			array( 'src' => get_template_directory_uri() . '/assets/icon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml' ),
		),
	), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	exit;
} );

/* La page radio n'a pas besoin des styles de blocs WordPress */
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}, 100 );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
