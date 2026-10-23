<?php
/**
 * Back-office des demandes : liste par nom de demandeur, statut, fiche détaillée,
 * téléchargement (.txt / fiche imprimable PDF / photo) et export CSV.
 */
defined( 'ABSPATH' ) || exit;

/* Colonnes */
add_filter( 'manage_mcl_request_posts_columns', function () {
	return array(
		'cb'           => '<input type="checkbox">',
		'title'        => 'Demandeur',
		'mcl_type'     => 'Type',
		'mcl_for'      => 'Pour / Service',
		'mcl_occasion' => 'Occasion',
		'mcl_style'    => 'Langue · Genre',
		'mcl_contact'  => 'Contact',
		'mcl_form'     => 'Formulaire',
		'mcl_total'    => 'Total',
		'mcl_status'   => 'Statut',
		'date'         => 'Reçue le',
	);
} );

add_filter( 'the_title', function ( $title, $id = 0 ) {
	if ( is_admin() && $id && 'mcl_request' === get_post_type( $id ) ) {
		$name = get_post_meta( $id, '_mcl_r_name', true );
		return $name ? $name : $title;
	}
	return $title;
}, 10, 2 );

add_action( 'manage_mcl_request_posts_custom_column', function ( $col, $id ) {
	switch ( $col ) {
		case 'mcl_type':
			$rt   = get_post_meta( $id, '_mcl_r_type', true );
			$pill = array( 'service' => array( 'svc', 'Service' ), 'audiobook' => array( 'book', 'Livre audio' ), 'podcast' => array( 'pod', 'Podcast' ) );
			echo isset( $pill[ $rt ] ) ? '<span class="mcl-pill mcl-pill--' . esc_attr( $pill[ $rt ][0] ) . '">' . esc_html( $pill[ $rt ][1] ) . '</span>' : '<span class="mcl-pill">Chanson cadeau</span>';
			break;
		case 'mcl_for':
			if ( in_array( get_post_meta( $id, '_mcl_r_type', true ), array( 'audiobook', 'podcast' ), true ) ) {
				echo '<strong>' . esc_html( get_post_meta( $id, '_mcl_r_service', true ) ) . '</strong><br><small>' . esc_html( trim( mcl_request_value( $id, 'book_source' ) . ' ' . get_post_meta( $id, '_mcl_r_book_size', true ) . ' ' . get_post_meta( $id, '_mcl_r_episodes', true ) ) ) . '</small>';
				break;
			}
			if ( 'service' === get_post_meta( $id, '_mcl_r_type', true ) ) {
				echo '<strong>' . esc_html( get_post_meta( $id, '_mcl_r_service', true ) ) . '</strong><br><small>' . esc_html( get_post_meta( $id, '_mcl_r_option', true ) ) . '</small>';
				break;
			}
			echo '<strong>' . esc_html( get_post_meta( $id, '_mcl_r_recipient', true ) ) . '</strong><br><small>' . esc_html( mcl_request_value( $id, 'relation' ) ) . '</small>';
			break;
		case 'mcl_occasion':
			echo esc_html( mcl_request_value( $id, 'occasion' ) );
			$d = get_post_meta( $id, '_mcl_r_occasion_date', true );
			echo $d ? '<br><small>📅 ' . esc_html( $d ) . '</small>' : '';
			break;
		case 'mcl_style':
			echo esc_html( mcl_request_value( $id, 'song_lang' ) ) . '<br><small>' . esc_html( mcl_request_value( $id, 'genre' ) ) . '</small>';
			if ( 'yes' === get_post_meta( $id, '_mcl_r_clip', true ) ) {
				echo ' <span class="mcl-pill">+ clip</span>';
			}
			break;
		case 'mcl_contact':
			$e = get_post_meta( $id, '_mcl_r_email', true );
			$p = get_post_meta( $id, '_mcl_r_phone', true );
			echo $e ? '<a href="mailto:' . esc_attr( $e ) . '">' . esc_html( $e ) . '</a><br>' : '';
			if ( $p ) {
				$wa = preg_replace( '/\D+/', '', $p );
				if ( 0 === strpos( $wa, '0' ) ) {
					$wa = '212' . substr( $wa, 1 );
				}
				echo '<a href="https://wa.me/' . esc_attr( $wa ) . '" target="_blank">' . esc_html( $p ) . '</a>';
			}
			break;
		case 'mcl_form':
			echo esc_html( mcl_request_value( $id, 'form_lang' ) );
			break;
		case 'mcl_total':
			echo '<strong>' . esc_html( get_post_meta( $id, '_mcl_r_total', true ) ) . '</strong>';
			if ( get_post_meta( $id, '_mcl_r_express', true ) ) {
				echo '<br><small>⚡ express</small>';
			}
			break;
		case 'mcl_status':
			$s  = get_post_meta( $id, '_mcl_status', true ) ?: 'new';
			$st = mcl_request_statuses();
			echo '<span class="mcl-status mcl-status--' . esc_attr( $s ) . '">' . esc_html( $st[ $s ] ?? $s ) . '</span>';
			break;
	}
}, 10, 2 );

/* Actions de ligne : télécharger */
add_filter( 'post_row_actions', function ( $actions, $post ) {
	if ( 'mcl_request' !== $post->post_type ) {
		return $actions;
	}
	unset( $actions['inline hide-if-no-js'] );
	$actions['mcl_txt']   = '<a href="' . esc_url( mcl_download_url( $post->ID, 'txt' ) ) . '">⬇ Télécharger (.txt)</a>';
	$actions['mcl_print'] = '<a href="' . esc_url( mcl_download_url( $post->ID, 'print' ) ) . '" target="_blank">🖨 Fiche PDF</a>';
	return $actions;
}, 10, 2 );

function mcl_download_url( $id, $format ) {
	return wp_nonce_url( admin_url( 'admin-post.php?action=mcl_download&id=' . $id . '&format=' . $format ), 'mcl_download_' . $id );
}

/* Filtre par statut + bouton d'export CSV */
add_action( 'restrict_manage_posts', function ( $post_type ) {
	if ( 'mcl_request' !== $post_type ) {
		return;
	}
	$cur = isset( $_GET['mcl_status'] ) ? sanitize_key( $_GET['mcl_status'] ) : '';
	echo '<select name="mcl_status"><option value="">Tous les statuts</option>';
	foreach ( mcl_request_statuses() as $k => $l ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $cur, $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	echo '</select>';
	$t = isset( $_GET['mcl_type'] ) ? sanitize_key( $_GET['mcl_type'] ) : '';
	echo '<select name="mcl_type"><option value="">Tous les types</option><option value="song" ' . selected( $t, 'song', false ) . '>Chansons cadeau</option><option value="service" ' . selected( $t, 'service', false ) . '>Commandes de services</option><option value="audiobook" ' . selected( $t, 'audiobook', false ) . '>Livres audio</option><option value="podcast" ' . selected( $t, 'podcast', false ) . '>Podcasts</option></select>';
} );

add_action( 'manage_posts_extra_tablenav', function ( $which ) {
	$screen = get_current_screen();
	if ( 'top' !== $which || ! $screen || 'edit-mcl_request' !== $screen->id ) {
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=mcl_export_csv' . ( isset( $_GET['mcl_status'] ) ? '&mcl_status=' . sanitize_key( $_GET['mcl_status'] ) : '' ) ), 'mcl_export' );
	echo '<div class="alignleft actions"><a href="' . esc_url( $url ) . '" class="button button-primary">⬇ Exporter en CSV (Excel)</a></div>';
} );

add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() || 'mcl_request' !== $q->get( 'post_type' ) ) {
		return;
	}
	$mq = array();
	if ( ! empty( $_GET['mcl_status'] ) ) {
		$mq[] = array( 'key' => '_mcl_status', 'value' => sanitize_key( $_GET['mcl_status'] ) );
	}
	if ( ! empty( $_GET['mcl_type'] ) ) {
		$mq[] = in_array( $_GET['mcl_type'], array( 'service', 'audiobook', 'podcast' ), true )
			? array( 'key' => '_mcl_r_type', 'value' => sanitize_key( $_GET['mcl_type'] ) )
			: array( 'relation' => 'OR', array( 'key' => '_mcl_r_type', 'value' => 'song' ), array( 'key' => '_mcl_r_type', 'compare' => 'NOT EXISTS' ) );
	}
	if ( $mq ) {
		$q->set( 'meta_query', $mq );
	}
	// Recherche par nom de demandeur, prénom célébré, email ou téléphone.
	if ( $q->get( 's' ) ) {
		$s = $q->get( 's' );
		$q->set( 's', '' );
		$q->set( 'meta_query', array(
			'relation' => 'OR',
			array( 'key' => '_mcl_r_name', 'value' => $s, 'compare' => 'LIKE' ),
			array( 'key' => '_mcl_r_recipient', 'value' => $s, 'compare' => 'LIKE' ),
			array( 'key' => '_mcl_r_email', 'value' => $s, 'compare' => 'LIKE' ),
			array( 'key' => '_mcl_r_phone', 'value' => $s, 'compare' => 'LIKE' ),
		) );
	}
} );

/* Pastille « nouvelles demandes » dans le menu */
add_action( 'admin_menu', function () {
	global $menu;
	$q = new WP_Query( array( 'post_type' => 'mcl_request', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_mcl_status', 'meta_value' => 'new' ) );
	$n = (int) $q->found_posts;
	if ( ! $n ) {
		return;
	}
	foreach ( $menu as $i => $item ) {
		if ( 'edit.php?post_type=mcl_request' === $item[2] ) {
			$menu[ $i ][0] .= ' <span class="awaiting-mod count-' . $n . '"><span class="pending-count">' . $n . '</span></span>'; // phpcs:ignore
		}
	}
}, 99 );

/* Fiche détaillée */
add_action( 'add_meta_boxes_mcl_request', function () {
	remove_meta_box( 'submitdiv', 'mcl_request', 'side' );
	add_meta_box( 'mcl_req_detail', 'Détail de la demande', 'mcl_box_request', 'mcl_request', 'normal', 'high' );
	add_meta_box( 'mcl_req_status', 'Suivi', 'mcl_box_request_status', 'mcl_request', 'side', 'high' );
} );

function mcl_box_request( $post ) {
	$id = $post->ID;
	echo '<p class="mcl-ref">' . esc_html( mcl_request_ref( $id ) ) . ' · reçue le ' . esc_html( get_the_date( 'd/m/Y à H:i', $post ) ) . '</p>';
	echo '<table class="mcl-req-table">';
	foreach ( mcl_request_fields() as $k => $f ) {
		$v = mcl_request_value( $id, $k );
		if ( '' === $v ) {
			continue;
		}
		echo '<tr><th>' . esc_html( $f[0] ) . '</th><td>' . nl2br( esc_html( $v ) ) . '</td></tr>';
	}
	echo '</table>';
	$photo = (int) get_post_meta( $id, '_mcl_r_photo', true );
	if ( $photo ) {
		echo '<h3>Photo envoyée</h3><a href="' . esc_url( wp_get_attachment_url( $photo ) ) . '" target="_blank" download>' . wp_get_attachment_image( $photo, 'medium', false, array( 'style' => 'border-radius:10px;max-width:280px;height:auto' ) ) . '<br>⬇ Télécharger la photo</a>';
	}
}

function mcl_box_request_status( $post ) {
	wp_nonce_field( 'mcl_req_save', 'mcl_req_nonce' );
	$s = get_post_meta( $post->ID, '_mcl_status', true ) ?: 'new';
	echo '<p><label><strong>Statut</strong><br><select name="mcl_status" class="widefat">';
	foreach ( mcl_request_statuses() as $k => $l ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $s, $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	echo '</select></label></p>';
	echo '<p><label><strong>Notes internes</strong><textarea name="mcl_notes" rows="5" class="widefat">' . esc_textarea( get_post_meta( $post->ID, '_mcl_notes', true ) ) . '</textarea></label></p>';
	echo '<p><button type="submit" class="button button-primary button-large widefat">Enregistrer</button></p>';
	echo '<hr><p><a class="button widefat" href="' . esc_url( mcl_download_url( $post->ID, 'txt' ) ) . '">⬇ Télécharger (.txt)</a></p>';
	echo '<p><a class="button widefat" target="_blank" href="' . esc_url( mcl_download_url( $post->ID, 'print' ) ) . '">🖨 Fiche imprimable / PDF</a></p>';
	$e = get_post_meta( $post->ID, '_mcl_r_email', true );
	if ( $e ) {
		echo '<p><a class="button widefat" href="mailto:' . esc_attr( $e ) . '?subject=' . rawurlencode( 'Musicology — ' . mcl_request_ref( $post->ID ) ) . '">✉ Répondre par email</a></p>';
	}
	$sent = get_post_meta( $post->ID, '_mcl_mail_sent', true );
	echo '<p class="description">Email de notification : ' . ( '1' === (string) $sent ? '✅ envoyé' : '⚠️ non confirmé (vérifiez la config email / SMTP)' ) . '</p>';
	echo '<p><a class="submitdelete" style="color:#b32d2e" href="' . esc_url( get_delete_post_link( $post->ID ) ) . '">Mettre à la corbeille</a></p>';
}

add_action( 'save_post_mcl_request', function ( $post_id ) {
	if ( ! isset( $_POST['mcl_req_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mcl_req_nonce'] ) ), 'mcl_req_save' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['mcl_status'] ) && array_key_exists( sanitize_key( $_POST['mcl_status'] ), mcl_request_statuses() ) ) {
		update_post_meta( $post_id, '_mcl_status', sanitize_key( $_POST['mcl_status'] ) );
	}
	if ( isset( $_POST['mcl_notes'] ) ) {
		update_post_meta( $post_id, '_mcl_notes', sanitize_textarea_field( wp_unslash( $_POST['mcl_notes'] ) ) );
	}
} );

/* Ouvrir une demande la passe de « Nouvelle » à « En cours » ? Non : on garde le choix manuel. */

/* ---------- Téléchargements ---------- */
add_action( 'admin_post_mcl_download', function () {
	$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	check_admin_referer( 'mcl_download_' . $id );
	if ( ! $id || 'mcl_request' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
		wp_die( 'Accès refusé.' );
	}
	$format = isset( $_GET['format'] ) ? sanitize_key( $_GET['format'] ) : 'txt';
	$name   = get_post_meta( $id, '_mcl_r_name', true );
	$slug   = sanitize_title( remove_accents( $name ) ) ?: 'demande';
	$file   = 'demande-' . $slug . '-' . get_the_date( 'Y-m-d', $id );

	if ( 'print' === $format ) {
		header( 'Content-Type: text/html; charset=utf-8' );
		$photo = (int) get_post_meta( $id, '_mcl_r_photo', true );
		echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>' . esc_html( $file ) . '</title><style>body{font:14px Arial,sans-serif;margin:40px;color:#222}h1{font:700 26px Georgia,serif;color:#c4521f;margin:0}.meta{color:#777;margin:4px 0 24px}.bar{position:fixed;top:12px;right:12px}@media print{.bar{display:none}body{margin:0}}button{background:#e8642c;color:#fff;border:0;padding:10px 18px;border-radius:8px;font-weight:700;cursor:pointer}</style></head><body>';
		echo '<div class="bar"><button onclick="window.print()">Imprimer / Enregistrer en PDF</button></div>';
		echo '<h1>🎵 Musicology — Demande de chanson</h1><p class="meta">' . esc_html( mcl_request_ref( $id ) ) . ' · ' . esc_html( $name ) . ' · reçue le ' . esc_html( get_the_date( 'd/m/Y à H:i', $id ) ) . '</p>';
		echo mcl_request_table_html( $id ); // phpcs:ignore
		if ( $photo ) {
			echo '<p style="margin-top:20px">' . wp_get_attachment_image( $photo, 'medium', false, array( 'style' => 'max-width:260px;height:auto;border-radius:8px' ) ) . '</p>';
		}
		$notes = get_post_meta( $id, '_mcl_notes', true );
		if ( $notes ) {
			echo '<h3>Notes internes</h3><p>' . nl2br( esc_html( $notes ) ) . '</p>';
		}
		echo '</body></html>';
		exit;
	}

	$lines   = array();
	$lines[] = 'MUSICOLOGY — DEMANDE DE CHANSON';
	$lines[] = mcl_request_ref( $id ) . ' — reçue le ' . get_the_date( 'd/m/Y H:i', $id );
	$lines[] = str_repeat( '=', 48 );
	foreach ( mcl_request_fields() as $k => $f ) {
		$v = mcl_request_value( $id, $k );
		if ( '' === $v ) {
			continue;
		}
		$lines[] = strtoupper( remove_accents( $f[0] ) ) . ' :';
		$lines[] = $v;
		$lines[] = '';
	}
	$photo = (int) get_post_meta( $id, '_mcl_r_photo', true );
	if ( $photo ) {
		$lines[] = 'PHOTO : ' . wp_get_attachment_url( $photo );
	}
	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $file . '.txt"' );
	echo "\xEF\xBB\xBF" . implode( "\r\n", $lines ); // phpcs:ignore
	exit;
} );

add_action( 'admin_post_mcl_export_csv', function () {
	check_admin_referer( 'mcl_export' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Accès refusé.' );
	}
	$args = array( 'post_type' => 'mcl_request', 'posts_per_page' => -1, 'post_status' => 'any', 'orderby' => 'date', 'order' => 'DESC' );
	if ( ! empty( $_GET['mcl_status'] ) ) {
		$args['meta_query'] = array( array( 'key' => '_mcl_status', 'value' => sanitize_key( $_GET['mcl_status'] ) ) );
	}
	$posts = get_posts( $args );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="musicology-demandes-' . gmdate( 'Y-m-d' ) . '.csv"' );
	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" );
	$fields = mcl_request_fields();
	$head   = array( 'Référence', 'Date', 'Statut' );
	foreach ( $fields as $f ) {
		$head[] = $f[0];
	}
	$head[] = 'Photo';
	$head[] = 'Notes';
	fputcsv( $out, $head, ';' );
	$st = mcl_request_statuses();
	foreach ( $posts as $p ) {
		$s   = get_post_meta( $p->ID, '_mcl_status', true ) ?: 'new';
		$row = array( mcl_request_ref( $p->ID ), get_the_date( 'Y-m-d H:i', $p ), $st[ $s ] ?? $s );
		foreach ( array_keys( $fields ) as $k ) {
			$row[] = mcl_request_value( $p->ID, $k );
		}
		$photo = (int) get_post_meta( $p->ID, '_mcl_r_photo', true );
		$row[] = $photo ? wp_get_attachment_url( $photo ) : '';
		$row[] = get_post_meta( $p->ID, '_mcl_notes', true );
		fputcsv( $out, $row, ';' );
	}
	fclose( $out );
	exit;
} );
