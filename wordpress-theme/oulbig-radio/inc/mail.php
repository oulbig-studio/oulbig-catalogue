<?php
/**
 * OULBIG Radio — envoi des e-mails du site par la boîte radio@almoqayada.com (SMTP Hostinger).
 *
 * Le mot de passe n'est jamais dans le thème ni dans GitHub : il est lu dans un simple fichier
 * texte du serveur, placé HORS du dossier public, à côté de public_html :
 *   /home/<compte>/domains/radio.almoqayada.com/orad-smtp.txt   (une ligne : le mot de passe)
 *
 * @package OULBIG_Radio
 */

defined( 'ABSPATH' ) || exit;

$orad_smtp_file = dirname( untrailingslashit( ABSPATH ) ) . '/orad-smtp.txt';
if ( ! defined( 'ORAD_SMTP_PASS' ) && is_readable( $orad_smtp_file ) ) {
	$orad_smtp_pass = trim( (string) file_get_contents( $orad_smtp_file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( '' !== $orad_smtp_pass && false === strpos( $orad_smtp_pass, "\n" ) ) {
		define( 'ORAD_SMTP_PASS', $orad_smtp_pass );
	}
	unset( $orad_smtp_pass );
}
unset( $orad_smtp_file );

defined( 'ORAD_SMTP_USER' ) || define( 'ORAD_SMTP_USER', 'radio@almoqayada.com' );
defined( 'ORAD_SMTP_HOST' ) || define( 'ORAD_SMTP_HOST', 'smtp.hostinger.com' );
defined( 'ORAD_SMTP_PORT' ) || define( 'ORAD_SMTP_PORT', 465 );

/** Expéditeur : une vraie adresse du domaine (sinon Gmail & co rejettent les messages). */
add_filter( 'wp_mail_from', function ( $from ) {
	return ( 0 === strpos( $from, 'wordpress@' ) ) ? ORAD_SMTP_USER : $from;
} );
add_filter( 'wp_mail_from_name', function ( $name ) {
	return ( 'WordPress' === $name ) ? 'OULBIG Radio' : $name;
} );

/** Envoi authentifié par SMTP quand le mot de passe est présent sur le serveur. */
add_action( 'phpmailer_init', function ( $mailer ) {
	if ( ! defined( 'ORAD_SMTP_PASS' ) || '' === ORAD_SMTP_PASS ) {
		return;
	}
	$mailer->isSMTP();
	$mailer->Host       = ORAD_SMTP_HOST;
	$mailer->Port       = (int) ORAD_SMTP_PORT;
	$mailer->SMTPSecure = 465 === (int) ORAD_SMTP_PORT ? 'ssl' : 'tls';
	$mailer->SMTPAuth   = true;
	$mailer->Username   = ORAD_SMTP_USER;
	$mailer->Password   = ORAD_SMTP_PASS;
	$mailer->Sender     = ORAD_SMTP_USER;
	if ( 0 === strpos( (string) $mailer->From, 'wordpress@' ) || '' === (string) $mailer->From ) {
		$mailer->From = ORAD_SMTP_USER;
	}
} );

/** État de l'envoi, affiché dans OULBIG Radio → Réglages. */
function orad_mail_status() {
	return defined( 'ORAD_SMTP_PASS' ) && '' !== ORAD_SMTP_PASS
		? sprintf( 'E-mails envoyés par %s (SMTP %s).', ORAD_SMTP_USER, ORAD_SMTP_HOST )
		: 'E-mails : envoi PHP simple (fichier orad-smtp.txt absent sur le serveur).';
}

/** Dernière erreur d'envoi, affichée dans les réglages. */
add_action( 'wp_mail_failed', function ( $err ) {
	update_option( 'orad_mail_error', gmdate( 'Y-m-d H:i' ) . ' UTC — ' . wp_strip_all_tags( $err->get_error_message() ), false );
} );
add_action( 'wp_mail_succeeded', function () {
	delete_option( 'orad_mail_error' );
} );

/** Mail de bienvenue après l'inscription (le compte est déjà actif, pas de lien à cliquer). */
add_action( 'register_new_user', function ( $user_id ) {
	$u = get_userdata( $user_id );
	if ( ! $u || ! $u->user_email ) {
		return;
	}
	$msg  = "Bonjour {$u->user_login},\n\n";
	$msg .= "Bienvenue sur OULBIG Radio ! Votre compte est actif : likez vos titres, commentez et créez vos playlists.\n\n";
	$msg .= home_url( '/' ) . "\n\n";
	$msg .= "Mot de passe oublié ? " . wp_lostpassword_url() . "\n\n— OULBIG Radio";
	wp_mail( $u->user_email, 'Bienvenue sur OULBIG Radio', $msg );
}, 5 );
