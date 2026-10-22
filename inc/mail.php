<?php
/**
 * OULBIG Radio — envoi des e-mails du site par la boîte radio@almoqayada.com (SMTP Hostinger).
 *
 * Le mot de passe n'est jamais dans le thème ni dans GitHub : il est lu dans un fichier
 * du serveur placé HORS du dossier public, à côté de public_html :
 *   /home/<compte>/domains/radio.almoqayada.com/orad-smtp.php
 * qui contient : <?php define( 'ORAD_SMTP_PASS', '…' );
 * Constantes facultatives dans ce même fichier : ORAD_SMTP_USER, ORAD_SMTP_HOST, ORAD_SMTP_PORT.
 *
 * @package OULBIG_Radio
 */

defined( 'ABSPATH' ) || exit;

$orad_smtp_file = dirname( untrailingslashit( ABSPATH ) ) . '/orad-smtp.php';
if ( is_readable( $orad_smtp_file ) ) {
	require_once $orad_smtp_file;
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
		: 'E-mails : envoi PHP simple (fichier orad-smtp.php absent sur le serveur).';
}

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
