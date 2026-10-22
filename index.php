<?php
/**
 * OULBIG Radio — toutes les adresses du site affichent la radio.
 *
 * @package OULBIG_Radio
 */

defined( 'ABSPATH' ) || exit;
$orad_uri  = get_template_directory_uri();
$orad_conf = orad_front_config();
$orad_bg   = 'clair' === $orad_conf['skin'] ? '#F6F4FB' : '#0B0916';
?><!doctype html>
<html <?php language_attributes(); ?> data-skin="<?php echo esc_attr( $orad_conf['skin'] ); ?>">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="<?php echo esc_attr( $orad_bg ); ?>">
<meta name="description" content="OULBIG Radio : la web radio d'OULBIG en 5 langues — podcasts, titres OULBIG et TALIM, Hit Parade.">
<link rel="manifest" href="<?php echo esc_url( add_query_arg( 'oulbig_manifest', '1', home_url( '/' ) ) ); ?>">
<link rel="icon" href="<?php echo esc_url( $orad_uri . '/assets/favicon.ico' ); ?>" sizes="any">
<link rel="icon" href="<?php echo esc_url( $orad_uri . '/assets/icon.svg' ); ?>" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?php echo esc_url( $orad_uri . '/assets/icon-maskable-512.png' ); ?>">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Unbounded:wght@500;700;900&family=Readex+Pro:wght@300;400;500;600&family=JetBrains+Mono:wght@400;600&display=swap">
<link rel="stylesheet" href="<?php echo esc_url( $orad_uri . '/assets/radio.css?v=' . ORAD_VERSION ); ?>">
<style>:root{padding-top:env(safe-area-inset-top,0px);padding-bottom:env(safe-area-inset-bottom,0px)}</style>
<?php wp_head(); ?>
</head>
<body <?php body_class( 'oulbig-radio' ); ?>>
<?php wp_body_open(); ?>
<?php readfile( __DIR__ . '/app.html' ); // gabarit statique du thème ?>
<script>window.ORAD_CONFIG = <?php echo wp_json_encode( $orad_conf, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ); ?>;</script>
<script src="<?php echo esc_url( $orad_uri . '/assets/i18n.js?v=' . ORAD_VERSION ); ?>"></script>
<script src="<?php echo esc_url( $orad_uri . '/assets/radio.js?v=' . ORAD_VERSION ); ?>"></script>
<?php wp_footer(); ?>
</body>
</html>
