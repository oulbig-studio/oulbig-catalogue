<?php
/**
 * OULBIG Radio — installation du mot de passe SMTP (ligne de commande uniquement).
 *
 * Usage (tâche cron ou terminal Hostinger) :
 *   php wp-content/themes/<thème>/tools/smtp-setup.php <mot_de_passe>
 *
 * Écrit le mot de passe dans ../orad-smtp.txt (hors du dossier public, droits 600),
 * puis envoie UN e-mail de test à l'adresse d'administration et note le résultat
 * (sans aucune donnée personnelle) dans wp-content/uploads/orad-mail-status.txt.
 * Le mot de passe n'est jamais stocké dans le thème ni dans GitHub.
 *
 * @package OULBIG_Radio
 */

if ( PHP_SAPI !== 'cli' ) {
	http_response_code( 404 );
	exit;
}

$pub = __DIR__;
while ( $pub !== dirname( $pub ) && ! is_file( $pub . '/wp-load.php' ) ) {
	$pub = dirname( $pub );
}
if ( ! is_file( $pub . '/wp-load.php' ) ) {
	fwrite( STDERR, "wp-load.php introuvable\n" );
	exit( 1 );
}
$secret_file = dirname( $pub ) . '/orad-smtp.txt';
$status_file = $pub . '/wp-content/uploads/orad-mail-status.txt';

$pass = isset( $argv[1] ) ? trim( $argv[1] ) : '';
if ( '' !== $pass ) {
	if ( ! preg_match( '/^[A-Za-z0-9_.\-]{8,128}$/', $pass ) ) {
		fwrite( STDERR, "Mot de passe refusé (caractères autorisés : lettres, chiffres, _ . -)\n" );
		exit( 1 );
	}
	file_put_contents( $secret_file, $pass . "\n" );
	chmod( $secret_file, 0600 );
}

// Un seul e-mail de test : on ne renvoie pas si le résultat est déjà noté.
if ( is_file( $status_file ) ) {
	echo "Déjà testé : " . file_get_contents( $status_file ) . "\n";
	exit( 0 );
}

$_SERVER['HTTP_HOST']   = 'radio.almoqayada.com';
$_SERVER['REQUEST_URI'] = '/';
define( 'WP_USE_THEMES', false );
require $pub . '/wp-load.php';

$ok   = wp_mail( get_option( 'admin_email' ), 'OULBIG Radio — test des e-mails', "Les e-mails du site radio.almoqayada.com fonctionnent.\n\n— OULBIG Radio" );
$line = gmdate( 'Y-m-d H:i' ) . ' UTC | ' . ( function_exists( 'orad_mail_status' ) ? orad_mail_status() : 'thème inactif' ) . ' | ' . ( $ok ? 'ENVOI OK' : 'ECHEC : ' . get_option( 'orad_mail_error' ) );
wp_mkdir_p( dirname( $status_file ) );
file_put_contents( $status_file, $line . "\n" );
echo $line . "\n";
