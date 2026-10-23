<?php
/**
 * Demandes de musique spéciale : enregistrement en back-office + envoi par email.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	register_post_type( 'mcl_request', array(
		'labels'          => array(
			'name'               => 'Demandes musique',
			'singular_name'      => 'Demande',
			'all_items'          => 'Toutes les demandes',
			'edit_item'          => 'Demande',
			'search_items'       => 'Rechercher un demandeur',
			'not_found'          => 'Aucune demande pour le moment.',
			'menu_name'          => 'Demandes musique',
		),
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => true,
		'menu_position'   => 6,
		'menu_icon'       => 'dashicons-email-alt',
		'supports'        => array( 'title' ),
		'capability_type' => 'post',
		'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap'    => true,
	) );
} );

/** Champs d'une demande : clé méta => [libellé FR, groupe d'options ou ''] */
function mcl_request_fields() {
	return array(
		'type'          => array( 'Type de demande', '' ),
		'service'       => array( 'Service commandé', '' ),
		'option'        => array( 'Formule', '' ),
		'total'         => array( 'Total estimé', '' ),
		'pricing'       => array( 'Détail du prix', '' ),
		'express'       => array( 'Livraison express', '' ),
		'name'          => array( 'Demandeur', '' ),
		'email'         => array( 'Email', '' ),
		'phone'         => array( 'Téléphone / WhatsApp', '' ),
		'city'          => array( 'Ville / Pays', '' ),
		'form_lang'     => array( 'Langue du formulaire', '' ),
		'relation'      => array( 'Destinataire', 'relation' ),
		'occasion'      => array( 'Occasion', 'occasion' ),
		'occasion_date' => array( 'Date de l\'occasion', '' ),
		'recipient'     => array( 'Prénom(s) à célébrer', '' ),
		'pronunciation' => array( 'Prononciation', '' ),
		'song_lang'     => array( 'Langue de la chanson', 'song_lang' ),
		'genre'         => array( 'Genre musical', 'genre' ),
		'voice'         => array( 'Voix', 'voice' ),
		'qualities'     => array( 'Qualités', 'quality' ),
		'own_words'     => array( 'Mots personnels (qualités)', '' ),
		'mode'          => array( 'Type de texte', 'mode' ),
		'story'         => array( 'Histoire / souvenirs', '' ),
		'lyrics'        => array( 'Paroles fournies', '' ),
		'message'       => array( 'Message du cœur', '' ),
		'clip'          => array( 'Clip vidéo', 'clip' ),
		'book_source'   => array( 'Format du texte', 'ab_source' ),
		'book_size'     => array( 'Longueur du livre', '' ),
		'read_lang'     => array( 'Langue de lecture', 'song_lang' ),
		'narration'     => array( 'Voix / narration', 'ab_voice' ),
		'music'         => array( 'Ambiances & musique', '' ),
		'pformat'       => array( 'Format du podcast', 'ab_pformat' ),
		'episodes'      => array( 'Épisodes', '' ),
		'files'         => array( 'Liens vers les fichiers', '' ),
		'details'       => array( 'Détails du projet', '' ),
	);
}

/** Valeur lisible (FR) d'un champ d'une demande. */
function mcl_request_value( $post_id, $key ) {
	$fields = mcl_request_fields();
	$raw    = get_post_meta( $post_id, '_mcl_r_' . $key, true );
	if ( 'type' === $key ) {
		$types = array( 'service' => 'Commande de service', 'audiobook' => 'Livre audio (devis)', 'podcast' => 'Podcast (devis)' );
		return isset( $types[ $raw ] ) ? $types[ $raw ] : 'Chanson sur mesure (cadeau)';
	}
	if ( 'form_lang' === $key ) {
		$l = mcl_langs();
		return isset( $l[ $raw ] ) ? $l[ $raw ]['label'] : $raw;
	}
	$group = isset( $fields[ $key ] ) ? $fields[ $key ][1] : '';
	if ( $group ) {
		$vals = is_array( $raw ) ? $raw : ( '' === $raw ? array() : array( $raw ) );
		$out  = array();
		foreach ( $vals as $v ) {
			$out[] = trim( preg_replace( '/^[^\p{L}]+/u', '', mcl_opt_label( $group, $v, 'fr' ) ) );
		}
		return implode( ', ', $out );
	}
	return is_array( $raw ) ? implode( ', ', $raw ) : (string) $raw;
}

function mcl_request_ref( $post_id ) {
	return 'MCL-' . str_pad( (string) $post_id, 5, '0', STR_PAD_LEFT );
}

function mcl_request_statuses() {
	return array(
		'new'      => 'Nouvelle',
		'progress' => 'En cours',
		'sent'     => 'Devis / réponse envoyé',
		'done'     => 'Livrée',
		'archived' => 'Archivée',
	);
}

/* ---------- Soumission AJAX ---------- */
function mcl_submit_request() {
	$lang = isset( $_POST['lang'] ) && mcl_valid_lang( sanitize_key( $_POST['lang'] ) ) ? sanitize_key( $_POST['lang'] ) : 'fr';

	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'mcl_request' ) ) {
		wp_send_json_error( array( 'message' => mcl_t_in( $lang, 'err_server' ) . ' (nonce)' ), 403 );
	}
	// Pot de miel anti-robots + délai minimum.
	if ( ! empty( $_POST['website'] ) ) {
		wp_send_json_success( array( 'ref' => 'MCL-00000' ) );
	}
	$started = isset( $_POST['started'] ) ? (int) $_POST['started'] : 0;
	if ( $started && ( time() - $started ) < 5 ) {
		wp_send_json_error( array( 'message' => mcl_t_in( $lang, 'err_server' ) ), 429 );
	}

	$p    = wp_unslash( $_POST );
	$txt  = function ( $k, $max = 200 ) use ( $p ) {
		return isset( $p[ $k ] ) ? mb_substr( sanitize_text_field( $p[ $k ] ), 0, $max ) : '';
	};
	$area = function ( $k, $max = 6000 ) use ( $p ) {
		return isset( $p[ $k ] ) ? mb_substr( sanitize_textarea_field( $p[ $k ] ), 0, $max ) : '';
	};
	$pick = function ( $k, $group ) use ( $p ) {
		$v = isset( $p[ $k ] ) ? sanitize_key( $p[ $k ] ) : '';
		return array_key_exists( $v, mcl_options( $group ) ) ? $v : '';
	};

	$d = array(
		'type'          => 'song',
		'form_lang'     => $lang,
		'relation'      => $pick( 'relation', 'relation' ),
		'occasion'      => $pick( 'occasion', 'occasion' ),
		'occasion_date' => $txt( 'occasion_date', 20 ),
		'recipient'     => $txt( 'recipient', 120 ),
		'pronunciation' => $txt( 'pronunciation', 160 ),
		'song_lang'     => $pick( 'song_lang', 'song_lang' ),
		'genre'         => $pick( 'genre', 'genre' ),
		'voice'         => $pick( 'voice', 'voice' ),
		'qualities'     => array(),
		'own_words'     => $txt( 'own_words', 300 ),
		'mode'          => $pick( 'mode', 'mode' ),
		'story'         => $area( 'story' ),
		'lyrics'        => $area( 'lyrics' ),
		'message'       => $area( 'message', 2000 ),
		'clip'          => $pick( 'clip', 'clip' ),
		'name'          => $txt( 'name', 120 ),
		'email'         => isset( $p['email'] ) ? sanitize_email( $p['email'] ) : '',
		'phone'         => $txt( 'phone', 40 ),
		'city'          => $txt( 'city', 120 ),
	);
	if ( ! empty( $p['qualities'] ) && is_array( $p['qualities'] ) ) {
		$ok = mcl_options( 'quality' );
		foreach ( $p['qualities'] as $q ) {
			$q = sanitize_key( $q );
			if ( isset( $ok[ $q ] ) ) {
				$d['qualities'][] = $q;
			}
		}
	}

	// Validation.
	$errors = array();
	foreach ( array( 'relation', 'recipient', 'song_lang', 'genre', 'name' ) as $req ) {
		if ( '' === $d[ $req ] ) {
			$errors[ $req ] = mcl_t_in( $lang, 'err_required' );
		}
	}
	if ( '' === $d['story'] && '' === $d['lyrics'] ) {
		$errors['story'] = mcl_t_in( $lang, 'err_story' );
	}
	if ( '' === $d['email'] && '' === $d['phone'] ) {
		$errors['email'] = mcl_t_in( $lang, 'err_contact' );
	}
	if ( ! empty( $p['email'] ) && ! is_email( $d['email'] ) ) {
		$errors['email'] = mcl_t_in( $lang, 'err_email' );
	}
	if ( $errors ) {
		wp_send_json_error( array( 'message' => reset( $errors ), 'fields' => $errors ), 422 );
	}

	// Prix recalculés côté serveur (le navigateur ne fait qu'afficher).
	$p_song  = mcl_price( 'price_song' );
	$p_clip  = 'yes' === $d['clip'] ? mcl_price( 'price_clip' ) : 0;
	$p_exp   = ! empty( $p['express'] ) ? mcl_price( 'price_express' ) : 0;
	$sub     = $p_song + $p_clip + $p_exp;
	$t_pct   = isset( $p['tip_pct'] ) ? (int) $p['tip_pct'] : 0;
	$t_pct   = in_array( $t_pct, array( 0, 5, 10, 15 ), true ) ? $t_pct : 0;
	$t_cust  = isset( $p['tip_custom'] ) ? min( 5000, absint( $p['tip_custom'] ) ) : 0;
	$tip     = $t_cust > 0 ? $t_cust : (int) round( $sub * $t_pct / 100 );
	$lines   = array( 'Chanson : ' . mcl_dh( $p_song ) );
	if ( $p_clip ) {
		$lines[] = 'Clip vidéo : +' . mcl_dh( $p_clip );
	}
	if ( $p_exp ) {
		$lines[] = 'Livraison express ' . mcl_price( 'express_hours' ) . ' h : +' . mcl_dh( $p_exp );
	}
	if ( $tip ) {
		$lines[] = 'Pourboire' . ( $t_cust ? '' : ' (' . $t_pct . ' %)' ) . ' : +' . mcl_dh( $tip );
	}
	$d['express'] = $p_exp ? 'Oui — en ' . mcl_price( 'express_hours' ) . ' h' : '';
	$d['pricing'] = implode( "\n", $lines );
	$d['total']   = mcl_dh( $sub + $tip );
	$d['tip']     = $tip;

	$post_id = wp_insert_post( array(
		'post_type'   => 'mcl_request',
		'post_status' => 'publish',
		'post_title'  => $d['name'] . ' — pour ' . $d['recipient'],
	), true );
	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( array( 'message' => mcl_t_in( $lang, 'err_server' ) ), 500 );
	}
	foreach ( $d as $k => $v ) {
		update_post_meta( $post_id, '_mcl_r_' . $k, $v );
	}
	update_post_meta( $post_id, '_mcl_status', 'new' );

	// Photo facultative.
	$attachment = 0;
	if ( ! empty( $_FILES['photo']['name'] ) && ! empty( $_FILES['photo']['tmp_name'] ) ) {
		$f     = $_FILES['photo']; // phpcs:ignore
		$check = wp_check_filetype_and_ext( $f['tmp_name'], $f['name'], array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' ) );
		if ( $f['size'] <= 5 * MB_IN_BYTES && ! empty( $check['type'] ) && 0 === (int) $f['error'] ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$_FILES['photo']['name'] = sanitize_file_name( 'demande-' . $post_id . '-' . $f['name'] );
			$aid = media_handle_upload( 'photo', $post_id, array(), array( 'test_form' => false ) );
			if ( ! is_wp_error( $aid ) ) {
				$attachment = (int) $aid;
				update_post_meta( $post_id, '_mcl_r_photo', $attachment );
			}
		}
	}

	mcl_mail_request( $post_id, $attachment );

	wp_send_json_success( array( 'ref' => mcl_request_ref( $post_id ) ) );
}
add_action( 'wp_ajax_mcl_request', 'mcl_submit_request' );
add_action( 'wp_ajax_nopriv_mcl_request', 'mcl_submit_request' );

/** Tableau HTML récapitulatif (emails + fiche imprimable). */
function mcl_request_table_html( $post_id ) {
	$rows = '';
	foreach ( mcl_request_fields() as $k => $f ) {
		$v = mcl_request_value( $post_id, $k );
		if ( '' === $v ) {
			continue;
		}
		$rows .= '<tr><th style="text-align:left;vertical-align:top;padding:8px 12px;background:#faf3ec;color:#7a4a2e;font:600 13px Arial,sans-serif;width:190px;border-bottom:1px solid #eee">' . esc_html( $f[0] ) . '</th>'
			. '<td style="padding:8px 12px;font:14px/1.5 Arial,sans-serif;color:#222;border-bottom:1px solid #eee">' . nl2br( esc_html( $v ) ) . '</td></tr>';
	}
	return '<table style="border-collapse:collapse;width:100%;max-width:720px;border:1px solid #eee">' . $rows . '</table>';
}

function mcl_mail_request( $post_id, $attachment = 0 ) {
	$ref     = mcl_request_ref( $post_id );
	$name    = get_post_meta( $post_id, '_mcl_r_name', true );
	$email   = get_post_meta( $post_id, '_mcl_r_email', true );
	$lang    = get_post_meta( $post_id, '_mcl_r_form_lang', true ) ?: 'fr';
	$who     = get_post_meta( $post_id, '_mcl_r_recipient', true );
	$is_svc  = in_array( get_post_meta( $post_id, '_mcl_r_type', true ), array( 'service', 'audiobook', 'podcast' ), true );
	$svc     = get_post_meta( $post_id, '_mcl_r_service', true );
	$to      = array_filter( array_map( 'trim', explode( ',', (string) mcl_opt( 'email_to' ) ) ), 'is_email' );
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	if ( $email ) {
		$headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
	}
	$admin_link = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
	$body       = '<div style="font:14px Arial,sans-serif;color:#222">'
		. '<h2 style="font:700 20px Georgia,serif;color:#c4521f;margin:0 0 6px">' . ( $is_svc ? '🎛️ Nouvelle commande — ' . esc_html( $svc ) : '🎵 Nouvelle demande de chanson' ) . ' — ' . esc_html( $ref ) . '</h2>'
		. '<p style="margin:0 0 16px;color:#666">Reçue le ' . esc_html( wp_date( 'd/m/Y à H:i' ) ) . ' via le formulaire ' . esc_html( mcl_langs()[ $lang ]['label'] ) . '.</p>'
		. mcl_request_table_html( $post_id )
		. '<p style="margin:18px 0"><a href="' . esc_url( $admin_link ) . '" style="background:#e8642c;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:700">Ouvrir dans le back-office</a></p>'
		. '</div>';
	$files = array();
	if ( $attachment ) {
		$path = get_attached_file( $attachment );
		if ( $path && file_exists( $path ) ) {
			$files[] = $path;
		}
	}
	if ( $to ) {
		$subj = $is_svc ? 'Nouvelle commande « ' . $svc . ' » — ' . $name . ' (' . $ref . ')' : 'Nouvelle demande de chanson — ' . $name . ' (' . $ref . ')';
		$ok   = wp_mail( $to, $subj, $body, $headers, $files );
		update_post_meta( $post_id, '_mcl_mail_sent', $ok ? 1 : 0 );
	}

	// Accusé de réception au demandeur, dans sa langue.
	if ( $email && mcl_opt( 'confirm_mail' ) ) {
		$dir   = 'ar' === $lang ? 'rtl' : 'ltr';
		$cbody = '<div dir="' . $dir . '" style="font:15px/1.6 Arial,sans-serif;color:#222;max-width:560px">'
			. '<p>' . esc_html( sprintf( mcl_t_in( $lang, 'mail_client_hello' ), $name ) ) . '</p>'
			. '<p>' . esc_html( $is_svc ? sprintf( mcl_t_in( $lang, 'mail_order_body' ), $svc, $ref ) : sprintf( mcl_t_in( $lang, 'mail_client_body' ), $who, $ref ) ) . '</p>'
			. '<p style="font:italic 18px Georgia,serif;color:#c4521f">' . esc_html( mcl_t_in( $lang, 'cta_final' ) ) . '</p>'
			. '<p>— Musicology<br>' . esc_html( mcl_opt( 'contact_email' ) ) . ' · ' . esc_html( mcl_opt( 'phone' ) ) . '</p></div>';
		$ch    = array( 'Content-Type: text/html; charset=UTF-8' );
		if ( mcl_opt( 'contact_email' ) ) {
			$ch[] = 'Reply-To: Musicology <' . mcl_opt( 'contact_email' ) . '>';
		}
		wp_mail( $email, sprintf( mcl_t_in( $lang, 'mail_client_subject' ), $ref ), $cbody, $ch );
	}
}
