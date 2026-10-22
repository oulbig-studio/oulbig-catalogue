<?php
/**
 * OULBIG Radio — administration : réglages, langues signalées, commentaires, demandes d'accès, titres proposés.
 *
 * @package OULBIG_Radio
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------ Titres proposés (soumissions) */
add_action( 'init', function () {
	register_post_type( 'orad_submission', array(
		'labels'       => array( 'name' => 'Titres proposés', 'singular_name' => 'Titre proposé', 'edit_item' => 'Valider le titre', 'all_items' => 'Titres proposés' ),
		'public'       => false,
		'show_ui'      => true,
		'show_in_menu' => 'orad',
		'supports'     => array( 'title', 'author' ),
		'capability_type' => 'post',
	) );
} );

add_action( 'add_meta_boxes_orad_submission', function () {
	add_meta_box( 'orad_sub', 'Piste', function ( $post ) {
		wp_nonce_field( 'orad_sub', 'orad_sub_nonce' );
		$yt   = get_post_meta( $post->ID, 'orad_yt', true );
		$lang = get_post_meta( $post->ID, 'orad_lang', true );
		$type = get_post_meta( $post->ID, 'orad_type', true );
		echo '<table class="form-table"><tr><th>Vidéo</th><td>';
		if ( $yt ) {
			printf( '<a href="%1$s" target="_blank" rel="noopener">%1$s</a><br><img src="https://i.ytimg.com/vi/%2$s/mqdefault.jpg" alt="" style="margin-top:8px;border-radius:8px">', esc_url( 'https://www.youtube.com/watch?v=' . $yt ), esc_attr( $yt ) );
		}
		echo '</td></tr>';
		printf( '<tr><th><label for="orad_yt">ID ou lien YouTube</label></th><td><input class="regular-text" id="orad_yt" name="orad_yt" value="%s"></td></tr>', esc_attr( $yt ) );
		printf( '<tr><th><label for="orad_artist">Artiste</label></th><td><input class="regular-text" id="orad_artist" name="orad_artist" value="%s"></td></tr>', esc_attr( get_post_meta( $post->ID, 'orad_artist', true ) ) );
		echo '<tr><th><label for="orad_lang">Langue</label></th><td><select id="orad_lang" name="orad_lang">';
		foreach ( orad_lang_labels() as $k => $v ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $lang, $k, false ), esc_html( $v ) );
		}
		echo '</select></td></tr><tr><th><label for="orad_type">Type</label></th><td><select id="orad_type" name="orad_type">';
		printf( '<option value="song" %s>Titre</option><option value="podcast" %s>Podcast</option>', selected( $type, 'song', false ), selected( $type, 'podcast', false ) );
		echo '</select></td></tr>';
		printf( '<tr><th><label for="orad_dur">Durée (secondes)</label></th><td><input type="number" min="10" id="orad_dur" name="orad_dur" value="%s"> <span class="description">Facultatif : la radio se recale toute seule en fin de vidéo.</span></td></tr></table>', esc_attr( get_post_meta( $post->ID, 'orad_dur', true ) ) );
		echo '<p><strong>Pour diffuser ce titre, clique sur « Publier ».</strong> Il entre alors dans la grille de sa langue.</p>';
	}, 'orad_submission', 'normal', 'high' );
} );

add_action( 'save_post_orad_submission', function ( $id ) {
	if ( ! isset( $_POST['orad_sub_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['orad_sub_nonce'] ), 'orad_sub' ) || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	update_post_meta( $id, 'orad_yt', orad_yt_id( sanitize_text_field( wp_unslash( $_POST['orad_yt'] ?? '' ) ) ) );
	update_post_meta( $id, 'orad_artist', sanitize_text_field( wp_unslash( $_POST['orad_artist'] ?? '' ) ) );
	$lang = sanitize_key( $_POST['orad_lang'] ?? 'fr' );
	update_post_meta( $id, 'orad_lang', in_array( $lang, ORAD_LANGS, true ) ? $lang : 'fr' );
	update_post_meta( $id, 'orad_type', 'podcast' === ( $_POST['orad_type'] ?? '' ) ? 'podcast' : 'song' );
	update_post_meta( $id, 'orad_dur', absint( $_POST['orad_dur'] ?? 0 ) );
} );

/* ------------------------------------------------------------------ Menu */
add_action( 'admin_menu', function () {
	add_menu_page( 'OULBIG Radio', 'OULBIG Radio', 'manage_options', 'orad', 'orad_page_settings', 'dashicons-format-audio', 3 );
	add_submenu_page( 'orad', 'Réglages', 'Réglages', 'manage_options', 'orad', 'orad_page_settings' );
	add_submenu_page( 'orad', 'Langues signalées', 'Langues signalées', 'manage_options', 'orad-reports', 'orad_page_reports' );
	add_submenu_page( 'orad', 'Commentaires', 'Commentaires', 'moderate_comments', 'orad-comments', 'orad_page_comments' );
	add_submenu_page( 'orad', 'Demandes de publication', 'Demandes de publication', 'promote_users', 'orad-access', 'orad_page_access' );
} );

function orad_field( $name, $value, $type = 'text', $attrs = '' ) {
	printf( '<input type="%s" name="orad_settings%s" value="%s" %s>', esc_attr( $type ), esc_attr( $name ), esc_attr( $value ), $attrs ); // phpcs:ignore
}

function orad_page_settings() {
	$s = orad_settings();
	?>
	<div class="wrap">
	<h1>OULBIG Radio — Réglages</h1>
	<p>Les titres, podcasts et liens YouTube/Spotify viennent du catalogue synchronisé chaque jour sur GitHub (OULBIG et TALIM).</p>
	<form method="post" action="options.php">
	<?php settings_fields( 'orad_settings' ); ?>
	<h2>Général</h2>
	<table class="form-table">
		<tr><th>Apparence</th><td><select name="orad_settings[skin]"><?php foreach ( array( 'sombre' => 'Sombre', 'clair' => 'Clair', 'auto' => 'Selon l’appareil' ) as $k => $v ) { printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $s['skin'], $k, false ), esc_html( $v ) ); } ?></select></td></tr>
		<tr><th>Adresse du catalogue</th><td><?php orad_field( '[catalogue_url]', $s['catalogue_url'], 'url', 'class="large-text"' ); ?></td></tr>
		<tr><th>Classement du Hit Parade</th><td><select name="orad_settings[hit_mode]"><?php foreach ( array( 'mixte' => 'Mixte : vues YouTube + votes des auditeurs', 'votes' => 'Votes des auditeurs', 'vues' => 'Vues YouTube' ) as $k => $v ) { printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $s['hit_mode'], $k, false ), esc_html( $v ) ); } ?></select></td></tr>
		<tr><th>Inscriptions</th><td><label><input type="checkbox" name="orad_settings[allow_signup]" value="1" <?php checked( $s['allow_signup'], 1 ); ?>> Autoriser les auditeurs à créer un compte (playlists, votes, commentaires)</label></td></tr>
		<tr><th>Commentaires</th><td><label><input type="checkbox" name="orad_settings[moderate]" value="1" <?php checked( $s['moderate'], 1 ); ?>> Valider chaque commentaire avant affichage</label></td></tr>
		<tr><th>Langues signalées</th><td>Changer la station d’une piste après <?php orad_field( '[report_threshold]', $s['report_threshold'], 'number', 'min="1" style="width:70px"' ); ?> signalements identiques (les tiens s’appliquent tout de suite).</td></tr>
	</table>

	<h2>Jingles par langue</h2>
	<p class="description">Colle le lien YouTube de chaque jingle quand il est publié. Vide = pas de jingle. Ils passent en début de boucle et avant le Hit Parade.</p>
	<table class="form-table">
	<?php foreach ( orad_lang_labels() as $l => $label ) : ?>
		<tr><th><?php echo esc_html( $label ); ?></th><td><?php orad_field( "[jingles][$l][yt]", $s['jingles'][ $l ]['yt'], 'text', 'class="regular-text" placeholder="https://youtu.be/…"' ); ?> durée <?php orad_field( "[jingles][$l][dur]", $s['jingles'][ $l ]['dur'], 'number', 'min="2" style="width:70px"' ); ?> s</td></tr>
	<?php endforeach; ?>
	</table>

	<h2>Pubs vidéo dans le lecteur</h2>
	<p class="description">Une pub passe toutes les <?php orad_field( '[ad_every]', $s['ad_every'], 'number', 'min="2" style="width:60px"' ); ?> pistes quand sa vidéo est renseignée. En attendant les vidéos, une carte partenaire s’affiche sur le lecteur toutes les <?php orad_field( '[promo_every]', $s['promo_every'], 'number', 'min="2" style="width:60px"' ); ?> pistes.</p>
	<table class="widefat striped" style="max-width:1100px"><thead><tr><th>Nom</th><th>Langue</th><th>Lien YouTube</th><th>Durée (s)</th><th>Lien « Découvrir »</th></tr></thead><tbody>
	<?php foreach ( $s['ads'] as $i => $a ) : ?>
		<tr><td><?php orad_field( "[ads][$i][label]", $a['label'] ); ?></td>
		<td><select name="orad_settings[ads][<?php echo (int) $i; ?>][lang]"><?php foreach ( array_merge( array( 'all' => 'Toutes' ), orad_lang_labels() ) as $k => $v ) { printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $a['lang'], $k, false ), esc_html( $v ) ); } ?></select></td>
		<td><?php orad_field( "[ads][$i][yt]", $a['yt'], 'text', 'placeholder="https://youtu.be/…"' ); ?></td>
		<td><?php orad_field( "[ads][$i][dur]", $a['dur'], 'number', 'min="5" style="width:70px"' ); ?></td>
		<td><?php orad_field( "[ads][$i][url]", $a['url'], 'url' ); ?></td></tr>
	<?php endforeach; ?>
	</tbody></table>

	<h2>Partenaires (bannières et sponsors)</h2>
	<p class="description">Laisse le slogan vide pour garder le texte traduit automatiquement dans les 5 langues.</p>
	<table class="widefat striped" style="max-width:1100px"><thead><tr><th>Nom</th><th>Lien</th><th>Slogan (facultatif)</th><th>Couleur</th></tr></thead><tbody>
	<?php foreach ( $s['sponsors'] as $i => $p ) : ?>
		<tr><td><?php orad_field( "[sponsors][$i][name]", $p['name'] ); ?></td><td><?php orad_field( "[sponsors][$i][url]", $p['url'], 'url' ); ?></td><td><?php orad_field( "[sponsors][$i][tagline]", $p['tagline'], 'text', 'class="regular-text"' ); ?></td><td><?php orad_field( "[sponsors][$i][accent]", $p['accent'], 'color' ); ?></td></tr>
	<?php endforeach; ?>
	</tbody></table>

	<h2>Artistes</h2>
	<table class="widefat striped" style="max-width:1100px"><thead><tr><th>Nom (comme dans le catalogue)</th><th>YouTube</th><th>2ᵉ chaîne YouTube</th><th>Spotify</th><th>Slogan (facultatif)</th><th>Couleur</th></tr></thead><tbody>
	<?php foreach ( $s['artists'] as $i => $a ) : ?>
		<tr><td><?php orad_field( "[artists][$i][name]", $a['name'] ); ?></td><td><?php orad_field( "[artists][$i][youtube]", $a['youtube'], 'url' ); ?></td><td><?php orad_field( "[artists][$i][youtube2]", $a['youtube2'], 'url' ); ?></td><td><?php orad_field( "[artists][$i][spotify]", $a['spotify'], 'url' ); ?></td><td><?php orad_field( "[artists][$i][tagline]", $a['tagline'] ); ?></td><td><?php orad_field( "[artists][$i][color]", $a['color'], 'color' ); ?></td></tr>
	<?php endforeach; ?>
	</tbody></table>

	<h2>Réseaux sociaux de la radio</h2>
	<table class="form-table">
	<?php foreach ( array( 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'facebook' => 'Facebook', 'x' => 'X', 'tiktok' => 'TikTok', 'spotify' => 'Spotify' ) as $k => $v ) : ?>
		<tr><th><?php echo esc_html( $v ); ?></th><td><?php orad_field( "[socials][$k]", $s['socials'][ $k ], 'url', 'class="large-text"' ); ?></td></tr>
	<?php endforeach; ?>
	</table>
	<?php submit_button( 'Enregistrer' ); ?>
	</form></div>
	<?php
}

/* ------------------------------------------------------------------ Langues signalées */
function orad_page_reports() {
	global $wpdb;
	if ( isset( $_POST['orad_rep_action'] ) && check_admin_referer( 'orad_rep' ) ) {
		$t = sanitize_text_field( wp_unslash( $_POST['track'] ?? '' ) );
		$o = orad_overrides();
		if ( orad_valid_track( $t ) ) {
			if ( 'apply' === $_POST['orad_rep_action'] ) {
				$l = sanitize_key( $_POST['lang'] ?? '' );
				if ( in_array( $l, ORAD_LANGS, true ) ) {
					$o[ $t ] = $l;
				}
			} else {
				unset( $o[ $t ] );
				$wpdb->delete( orad_table( 'reports' ), array( 'track' => $t ) );
			}
			update_option( 'orad_overrides', $o, false );
			echo '<div class="updated"><p>Enregistré.</p></div>';
		}
	}
	$titles = orad_catalogue_titles();
	$labels = orad_lang_labels();
	$over   = orad_overrides();
	$rows   = $wpdb->get_results( 'SELECT track, lang, COUNT(*) AS n FROM ' . orad_table( 'reports' ) . ' GROUP BY track, lang ORDER BY n DESC', ARRAY_A );
	$by     = array();
	foreach ( (array) $rows as $r ) {
		$by[ $r['track'] ][ $r['lang'] ] = (int) $r['n'];
	}
	foreach ( array_keys( $over ) as $t ) {
		if ( ! isset( $by[ $t ] ) ) {
			$by[ $t ] = array();
		}
	}
	echo '<div class="wrap"><h1>Langues signalées</h1><p>Les auditeurs signalent les pistes rangées dans la mauvaise station. Applique une langue pour déplacer la piste ; « Réinitialiser » rend la langue du catalogue.</p>';
	echo '<table class="widefat striped"><thead><tr><th>Piste</th><th>Langue du catalogue</th><th>Langue en vigueur</th><th>Signalements</th><th>Actions</th></tr></thead><tbody>';
	if ( ! $by ) {
		echo '<tr><td colspan="5">Aucun signalement pour l’instant.</td></tr>';
	}
	foreach ( $by as $t => $counts ) {
		$info = $titles[ $t ] ?? array( 'title' => $t, 'artist' => '', 'lang' => '' );
		$cur  = $over[ $t ] ?? $info['lang'];
		$list = array();
		foreach ( $counts as $l => $n ) {
			$list[] = esc_html( ( $labels[ $l ] ?? $l ) . ' : ' . $n );
		}
		echo '<tr><td><strong>' . esc_html( $info['title'] ) . '</strong><br><span class="description">' . esc_html( $info['artist'] ) . '</span>';
		if ( 0 === strpos( $t, 'yt-' ) ) {
			echo ' · <a href="' . esc_url( 'https://www.youtube.com/watch?v=' . substr( $t, 3 ) ) . '" target="_blank" rel="noopener">YouTube</a>';
		}
		echo '</td><td>' . esc_html( $labels[ $info['lang'] ] ?? '—' ) . '</td><td>' . esc_html( $labels[ $cur ] ?? '—' ) . ( isset( $over[ $t ] ) ? ' <em>(corrigée)</em>' : '' ) . '</td><td>' . implode( '<br>', $list ) . '</td><td>';
		echo '<form method="post" style="display:flex;gap:6px;flex-wrap:wrap">';
		wp_nonce_field( 'orad_rep' );
		echo '<input type="hidden" name="track" value="' . esc_attr( $t ) . '"><select name="lang">';
		foreach ( $labels as $l => $v ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $l ), selected( $cur, $l, false ), esc_html( $v ) );
		}
		echo '</select><button class="button button-primary" name="orad_rep_action" value="apply">Appliquer</button><button class="button" name="orad_rep_action" value="reset">Réinitialiser</button></form></td></tr>';
	}
	echo '</tbody></table></div>';
}

/* ------------------------------------------------------------------ Commentaires */
function orad_page_comments() {
	global $wpdb;
	$tbl = orad_table( 'comments' );
	if ( isset( $_POST['orad_com_action'], $_POST['id'] ) && check_admin_referer( 'orad_com' ) ) {
		$id = absint( $_POST['id'] );
		if ( 'delete' === $_POST['orad_com_action'] ) {
			$wpdb->delete( $tbl, array( 'id' => $id ) );
		} elseif ( 'approve' === $_POST['orad_com_action'] ) {
			$wpdb->update( $tbl, array( 'status' => 'approved' ), array( 'id' => $id ) );
		}
		echo '<div class="updated"><p>Enregistré.</p></div>';
	}
	$titles = orad_catalogue_titles();
	$rows   = $wpdb->get_results( "SELECT * FROM $tbl ORDER BY (status = 'pending') DESC, id DESC LIMIT 200", ARRAY_A );
	echo '<div class="wrap"><h1>Commentaires des auditeurs</h1><table class="widefat striped"><thead><tr><th>Auteur</th><th>Commentaire</th><th>Piste</th><th>Date</th><th>Statut</th><th></th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="6">Aucun commentaire pour l’instant.</td></tr>';
	}
	foreach ( (array) $rows as $c ) {
		$u = get_userdata( (int) $c['user_id'] );
		echo '<tr><td>' . esc_html( $u ? $u->display_name : '—' ) . '</td><td>' . nl2br( esc_html( $c['text'] ) ) . '</td><td>' . esc_html( $titles[ $c['track'] ]['title'] ?? $c['track'] ) . '</td><td>' . esc_html( $c['created'] ) . '</td><td>' . ( 'pending' === $c['status'] ? '<strong>À valider</strong>' : 'Publié' ) . '</td><td><form method="post" style="display:flex;gap:6px">';
		wp_nonce_field( 'orad_com' );
		echo '<input type="hidden" name="id" value="' . (int) $c['id'] . '">';
		if ( 'pending' === $c['status'] ) {
			echo '<button class="button button-primary" name="orad_com_action" value="approve">Publier</button>';
		}
		echo '<button class="button" name="orad_com_action" value="delete">Supprimer</button></form></td></tr>';
	}
	echo '</tbody></table></div>';
}

/* ------------------------------------------------------------------ Demandes de publication */
function orad_page_access() {
	if ( isset( $_POST['orad_acc_action'], $_POST['uid'] ) && check_admin_referer( 'orad_acc' ) ) {
		$u = get_userdata( absint( $_POST['uid'] ) );
		if ( $u ) {
			if ( 'grant' === $_POST['orad_acc_action'] ) {
				$u->add_role( 'orad_artist' );
				wp_mail( $u->user_email, 'OULBIG Radio : publication activée', "Bonne nouvelle ! Tu peux maintenant proposer tes titres depuis la section « Publier » de la radio :\n" . home_url( '/#publier' ) );
			} else {
				$u->remove_role( 'orad_artist' );
			}
			delete_user_meta( $u->ID, 'orad_access_request' );
			echo '<div class="updated"><p>Enregistré.</p></div>';
		}
	}
	$req     = get_users( array( 'meta_key' => 'orad_access_request', 'number' => 200 ) );
	$artists = get_users( array( 'role' => 'orad_artist', 'number' => 200 ) );
	echo '<div class="wrap"><h1>Demandes de publication</h1><p>Les artistes sélectionnés peuvent proposer des titres ; chaque proposition arrive dans « Titres proposés » et n’est diffusée qu’une fois publiée.</p>';
	foreach ( array( 'En attente' => $req, 'Artistes autorisés' => $artists ) as $label => $users ) {
		echo '<h2>' . esc_html( $label ) . '</h2><table class="widefat striped" style="max-width:900px"><tbody>';
		if ( ! $users ) {
			echo '<tr><td>Personne pour l’instant.</td></tr>';
		}
		foreach ( $users as $u ) {
			$is = in_array( 'orad_artist', (array) $u->roles, true );
			echo '<tr><td><strong>' . esc_html( $u->display_name ) . '</strong> — ' . esc_html( $u->user_email ) . '</td><td><form method="post">';
			wp_nonce_field( 'orad_acc' );
			echo '<input type="hidden" name="uid" value="' . (int) $u->ID . '">';
			echo $is ? '<button class="button" name="orad_acc_action" value="revoke">Retirer l’accès</button>' : '<button class="button button-primary" name="orad_acc_action" value="grant">Accorder</button> <button class="button" name="orad_acc_action" value="deny">Refuser</button>';
			echo '</form></td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '</div>';
}

/* ------------------------------------------------------------------ Confort */
add_filter( 'show_admin_bar', function ( $show ) {
	return current_user_can( 'edit_posts' ) ? $show : false;
} );
add_action( 'login_enqueue_scripts', function () {
	$icon = get_template_directory_uri() . '/assets/icon.svg';
	?>
	<style>
	body.login{background:#0B0916 radial-gradient(900px 500px at 85% -10%,rgba(255,61,110,.22),transparent 60%)}
	.login h1 a{background-image:url(<?php echo esc_url( $icon ); ?>);background-size:84px;width:84px;height:84px}
	.login form{border-radius:16px;border:1px solid #2C2450;background:#17122C;color:#F4EFFF;box-shadow:none}
	.login label,.login #nav a,.login #backtoblog a,.login .message,.login .privacy-policy-page-link a{color:#F4EFFF!important}
	.login form .input{background:#120E24;border-color:#2C2450;color:#F4EFFF}
	.wp-core-ui .button-primary{background:#FF3D6E;border-color:#FF3D6E;border-radius:999px}
	</style>
	<?php
} );
add_filter( 'login_headerurl', function () { return home_url( '/' ); } );
add_filter( 'login_headertext', function () { return 'OULBIG Radio'; } );
