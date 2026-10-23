<?php
/**
 * Synchronisation du répertoire OULBIG / TALIM depuis le catalogue officiel
 * (https://oulbig-studio.github.io/oulbig-catalogue/catalogue.json, mis à jour chaque jour
 * à partir de YouTube @OULBIG, OULBIG – Topic, @TALIM4Art et Spotify).
 *
 * - Chansons → titres Musicology (lien YouTube, artiste, langue, durée, vues → hit-parade).
 * - Vidéos de la chaîne principale → aussi dans « Clips » ; vidéos « – Topic » → audio seul.
 * - Podcasts (Étincelle…) → extraits sur la page Livres audio & podcasts.
 * Mise à jour automatique toutes les heures (WP-Cron) + bouton « Synchroniser maintenant ».
 */
defined( 'ABSPATH' ) || exit;

const MCL_CATALOGUE_URL = 'https://oulbig-studio.github.io/oulbig-catalogue/catalogue.json';

function mcl_sync_opt( $k ) {
	$d = array(
		'enabled'   => 1,
		'url'       => MCL_CATALOGUE_URL,
		'artists'   => array( 'OULBIG', 'TALIM' ),
		'max'       => 150,
		'del_demo'  => 1,
	);
	$o = wp_parse_args( (array) get_option( 'mcl_sync', array() ), $d );
	return $o[ $k ];
}

/** Catalogue (cache 1 h, puis dernière version connue, puis copie livrée avec le thème). */
function mcl_catalogue( $force = false ) {
	static $mem = null;
	if ( null !== $mem && ! $force ) {
		return $mem;
	}
	$cat = $force ? false : get_transient( 'mcl_catalogue' );
	if ( false === $cat ) {
		$cat = null;
		$res = wp_remote_get( mcl_sync_opt( 'url' ), array( 'timeout' => 20 ) );
		if ( ! is_wp_error( $res ) && 200 === wp_remote_retrieve_response_code( $res ) ) {
			$j = json_decode( wp_remote_retrieve_body( $res ), true );
			if ( is_array( $j ) && ! empty( $j['items'] ) ) {
				$cat = $j;
				update_option( 'mcl_catalogue_last', $cat, false );
				update_option( 'mcl_catalogue_source', 'live', false );
			}
		}
		if ( ! $cat ) {
			$cat = get_option( 'mcl_catalogue_last' );
			update_option( 'mcl_catalogue_source', $cat ? 'cache' : 'snapshot', false );
		}
		if ( ! $cat ) {
			$file = MCL_DIR . '/assets/data/catalogue-snapshot.json';
			$cat  = file_exists( $file ) ? json_decode( file_get_contents( $file ), true ) : null; // phpcs:ignore
		}
		set_transient( 'mcl_catalogue', $cat ? $cat : array(), HOUR_IN_SECONDS );
	}
	$mem = $cat ? $cat : null;
	return $mem;
}

function mcl_cat_lang( $l ) {
	$map = array( 'ma' => 'darija', 'fr' => 'fr', 'en' => 'en', 'es' => 'es', 'ar' => 'ar' );
	return isset( $map[ $l ] ) ? $map[ $l ] : '';
}

/** Chansons retenues : tout TALIM + OULBIG par vues puis récence, sans doublons. */
function mcl_sync_selection() {
	$cat = mcl_catalogue();
	if ( ! $cat ) {
		return array();
	}
	$artists = (array) mcl_sync_opt( 'artists' );
	$songs   = array();
	foreach ( $cat['items'] as $i ) {
		if ( 'song' !== $i['type'] || empty( $i['youtube']['id'] ) || ! in_array( $i['artist'], $artists, true ) ) {
			continue;
		}
		if ( isset( $i['youtube']['embeddable'] ) && ! $i['youtube']['embeddable'] ) {
			continue;
		}
		$key = $i['artist'] . '|' . $i['lang'] . '|' . mb_strtolower( trim( $i['title'] ) );
		$v   = (int) ( $i['youtube']['views'] ?? 0 );
		if ( ! isset( $songs[ $key ] ) || $v > (int) ( $songs[ $key ]['youtube']['views'] ?? 0 ) ) {
			$songs[ $key ] = $i;
		}
	}
	$songs = array_values( $songs );
	usort( $songs, function ( $a, $b ) {
		$ta = 'TALIM' === $a['artist'] ? 1 : 0;
		$tb = 'TALIM' === $b['artist'] ? 1 : 0;
		if ( $ta !== $tb ) {
			return $tb - $ta;
		}
		$va = (int) ( $a['youtube']['views'] ?? 0 );
		$vb = (int) ( $b['youtube']['views'] ?? 0 );
		return $vb === $va ? strcmp( $b['published'], $a['published'] ) : $vb - $va;
	} );
	return array_slice( $songs, 0, max( 1, (int) mcl_sync_opt( 'max' ) ) );
}

/** Vidéo de la chaîne principale (clip) ou d'une chaîne « – Topic » (audio) ? */
function mcl_yt_kind( $id ) {
	$res = wp_remote_get( 'https://www.youtube.com/oembed?format=json&url=' . rawurlencode( 'https://www.youtube.com/watch?v=' . $id ), array( 'timeout' => 8 ) );
	if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
		return 'unknown';
	}
	$j = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( empty( $j['author_name'] ) ) {
		return 'unknown';
	}
	return false !== stripos( $j['author_name'], 'topic' ) ? 'audio' : 'clip';
}

function mcl_find_synced( $sync_id ) {
	$q = get_posts( array( 'post_type' => 'mcl_song', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_mcl_sync_id', 'meta_value' => $sync_id ) );
	return $q ? (int) $q[0] : 0;
}

function mcl_genre_from_title( $t ) {
	$map = array( 'chaabi' => 'Chaabi', 'raï' => 'Raï', 'rai ' => 'Raï', 'rap' => 'Rap', 'gnaoua' => 'Gnaoua', 'gnawa' => 'Gnaoua', 'r&b' => 'R&B', 'soul' => 'Soul', 'pop' => 'Pop', 'oriental' => 'Oriental', 'andalou' => 'Andalou' );
	$l   = mb_strtolower( $t . ' ' );
	foreach ( $map as $k => $g ) {
		if ( false !== mb_strpos( $l, $k ) ) {
			return $g;
		}
	}
	return '';
}

/**
 * Une passe de synchronisation.
 * @param int $batch Nombre maximum de nouveaux titres créés pendant cette passe.
 * @return array Statistiques.
 */
function mcl_sync_run( $batch = 40, $force = false ) {
	$stats = array( 'created' => 0, 'updated' => 0, 'pending' => 0, 'source' => '' );
	if ( ! mcl_sync_opt( 'enabled' ) && ! $force ) {
		return $stats;
	}
	if ( $force ) {
		delete_transient( 'mcl_catalogue' );
		mcl_catalogue( true );
	}
	$sel   = mcl_sync_selection();
	$month = mcl_month_key( 'audio' );
	$monv  = mcl_month_key( 'video' );
	foreach ( $sel as $i ) {
		$sid   = $i['id'];
		$yt    = $i['youtube'];
		$url   = 'https://www.youtube.com/watch?v=' . $yt['id'];
		$views = (int) ( $yt['views'] ?? 0 );
		$pid   = mcl_find_synced( $sid );

		if ( ! $pid ) {
			if ( $stats['created'] >= $batch ) {
				$stats['pending']++;
				continue;
			}
			$kind = mcl_yt_kind( $yt['id'] );
			$pid  = wp_insert_post( array(
				'post_type'    => 'mcl_song',
				'post_status'  => 'publish',
				'post_title'   => wp_strip_all_tags( $i['title'] ),
				'post_excerpt' => '',
				'post_content' => '',
				'post_date'    => ! empty( $i['published'] ) ? $i['published'] . ' 12:00:00' : current_time( 'mysql' ),
			) );
			if ( ! $pid || is_wp_error( $pid ) ) {
				continue;
			}
			update_post_meta( $pid, '_mcl_sync_id', $sid );
			update_post_meta( $pid, '_mcl_sync_kind', $kind );
			update_post_meta( $pid, '_mcl_artist', $i['artist'] );
			update_post_meta( $pid, '_mcl_audio_url', $url );
			update_post_meta( $pid, '_mcl_video_url', 'clip' === $kind ? $url : '' );
			update_post_meta( $pid, '_mcl_lyrics_lang', mcl_cat_lang( $i['lang'] ) );
			if ( ! empty( $i['duration'] ) ) {
				update_post_meta( $pid, '_mcl_duration', floor( $i['duration'] / 60 ) . ':' . str_pad( $i['duration'] % 60, 2, '0', STR_PAD_LEFT ) );
			}
			$g = mcl_genre_from_title( $i['title'] );
			if ( $g ) {
				wp_set_object_terms( $pid, $g, 'mcl_genre' );
			}
			if ( ! empty( $i['spotify']['url'] ) ) {
				update_post_meta( $pid, '_mcl_spotify_url', esc_url_raw( $i['spotify']['url'] ) );
			}
			$stats['created']++;
		} else {
			$kind = get_post_meta( $pid, '_mcl_sync_kind', true );
			// Nouvelle tentative de classement clip / audio si YouTube n'avait pas répondu.
			$tries = (int) get_post_meta( $pid, '_mcl_sync_tries', true );
			if ( 'unknown' === $kind && $tries < 3 && $stats['updated'] < 15 ) {
				$kind = mcl_yt_kind( $yt['id'] );
				update_post_meta( $pid, '_mcl_sync_kind', $kind );
				update_post_meta( $pid, '_mcl_sync_tries', $tries + 1 );
				if ( 'clip' === $kind && ! get_post_meta( $pid, '_mcl_video_url', true ) ) {
					update_post_meta( $pid, '_mcl_video_url', $url );
				}
			}
			$stats['updated']++;
		}

		// Vues YouTube → compteurs du hit-parade (on ne descend jamais) + progression du mois.
		$prev = (int) get_post_meta( $pid, '_mcl_yt_views', true );
		update_post_meta( $pid, '_mcl_yt_views', $views );
		$delta = max( 0, $views - $prev );
		$cur_a = (int) get_post_meta( $pid, '_mcl_plays_audio', true );
		update_post_meta( $pid, '_mcl_plays_audio', max( $cur_a + $delta, $views ) );
		if ( $prev && $delta ) {
			update_post_meta( $pid, $month, (int) get_post_meta( $pid, $month, true ) + $delta );
		}
		if ( 'clip' === $kind ) {
			$cur_v = (int) get_post_meta( $pid, '_mcl_plays_video', true );
			update_post_meta( $pid, '_mcl_plays_video', max( $cur_v + $delta, $views ) );
			if ( $prev && $delta ) {
				update_post_meta( $pid, $monv, (int) get_post_meta( $pid, $monv, true ) + $delta );
			}
		}
	}

	// Les titres de démonstration laissent la place au vrai répertoire.
	if ( mcl_sync_opt( 'del_demo' ) && ( $stats['created'] || $stats['updated'] ) ) {
		$demo = get_posts( array( 'post_type' => 'mcl_song', 'meta_key' => '_mcl_demo', 'meta_value' => 1, 'posts_per_page' => -1, 'fields' => 'ids', 'post_status' => 'any' ) );
		foreach ( $demo as $d ) {
			wp_delete_post( $d, true );
		}
	}
	$stats['source'] = get_option( 'mcl_catalogue_source', 'snapshot' );
	update_option( 'mcl_sync_last', array( 'time' => time(), 'stats' => $stats ), false );
	return $stats;
}

/* Cron horaire : continue l'import par lots puis met à jour les vues. */
add_action( 'mcl_sync_cron', function () {
	$s = mcl_sync_run( 40 );
	// Premier import : tant qu'il reste des titres, on enchaîne un lot une minute plus tard.
	if ( ! empty( $s['pending'] ) && ! wp_next_scheduled( 'mcl_sync_catchup' ) ) {
		wp_schedule_single_event( time() + 60, 'mcl_sync_catchup' );
	}
} );
add_action( 'mcl_sync_catchup', function () {
	do_action( 'mcl_sync_cron' );
} );
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'mcl_sync_cron' ) ) {
		wp_schedule_event( time() + 60, 'hourly', 'mcl_sync_cron' );
	}
} );
add_action( 'switch_theme', function () {
	wp_clear_scheduled_hook( 'mcl_sync_cron' );
} );

/* Podcasts du catalogue (pour la page Livres audio & podcasts). */
function mcl_catalogue_podcasts( $n = 6, $lang = null, $match = '' ) {
	$cat = mcl_catalogue();
	if ( ! $cat ) {
		return array();
	}
	$lang = $lang ?: mcl_lang();
	$pick = function ( $l ) use ( $cat, $match ) {
		$out = array();
		foreach ( $cat['items'] as $i ) {
			if ( 'podcast' !== $i['type'] || empty( $i['youtube']['id'] ) || $i['lang'] !== $l ) {
				continue;
			}
			if ( $match && false === mb_stripos( $i['title'], $match ) ) {
				continue;
			}
			$out[] = $i;
		}
		usort( $out, function ( $a, $b ) {
			return strcmp( $b['published'], $a['published'] );
		} );
		return $out;
	};
	$list = $pick( $lang );
	if ( ! $list && 'fr' !== $lang ) {
		$list = $pick( 'fr' );
	}
	return array_slice( $list, 0, $n );
}

function mcl_fmt_dur( $s ) {
	$s = (int) $s;
	return $s ? floor( $s / 60 ) . ':' . str_pad( $s % 60, 2, '0', STR_PAD_LEFT ) : '';
}

/* ---------- Admin : section dans « Réglages » ---------- */
add_action( 'admin_init', function () {
	register_setting( 'mcl_sync', 'mcl_sync', array(
		'type'              => 'array',
		'sanitize_callback' => function ( $in ) {
			delete_transient( 'mcl_catalogue' );
			$artists = array_values( array_intersect( (array) ( $in['artists'] ?? array() ), array( 'OULBIG', 'TALIM' ) ) );
			return array(
				'enabled'  => empty( $in['enabled'] ) ? 0 : 1,
				'url'      => esc_url_raw( $in['url'] ?? MCL_CATALOGUE_URL ) ?: MCL_CATALOGUE_URL,
				'artists'  => $artists ? $artists : array( 'OULBIG' ),
				'max'      => max( 1, min( 1000, absint( $in['max'] ?? 150 ) ) ),
				'del_demo' => empty( $in['del_demo'] ) ? 0 : 1,
			);
		},
	) );
} );

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=mcl_song', 'Synchronisation OULBIG', '🔄 Synchronisation', 'manage_options', 'mcl-sync', 'mcl_sync_page' );
}, 30 );

add_action( 'admin_post_mcl_sync_now', function () {
	check_admin_referer( 'mcl_sync_now' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Accès refusé.' );
	}
	$s = mcl_sync_run( 40, true );
	wp_safe_redirect( add_query_arg( array( 'page' => 'mcl-sync', 'post_type' => 'mcl_song', 'done' => $s['created'] . '-' . $s['updated'] . '-' . $s['pending'] ), admin_url( 'edit.php' ) ) );
	exit;
} );

function mcl_sync_page() {
	$last = get_option( 'mcl_sync_last' );
	$cat  = mcl_catalogue();
	$nb   = (int) wp_count_posts( 'mcl_song' )->publish;
	$syn  = count( get_posts( array( 'post_type' => 'mcl_song', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_mcl_sync_id' ) ) );
	$src  = array( 'live' => 'en ligne (à jour)', 'cache' => 'dernière version téléchargée', 'snapshot' => 'copie livrée avec le thème (le serveur n\'a pas pu joindre le catalogue)' );
	?>
	<div class="wrap">
		<h1>🔄 Synchronisation du répertoire OULBIG / TALIM</h1>
		<?php if ( isset( $_GET['done'] ) ) : list( $c, $u, $p ) = array_map( 'intval', explode( '-', sanitize_text_field( wp_unslash( $_GET['done'] ) ) . '-0-0' ) ); ?>
			<div class="notice notice-success"><p><strong><?php echo (int) $c; ?></strong> titre(s) ajouté(s), <strong><?php echo (int) $u; ?></strong> mis à jour.<?php echo $p ? ' Encore <strong>' . (int) $p . '</strong> à importer : cliquez à nouveau (ou laissez la synchro horaire continuer).' : ' Tout est à jour.'; ?></p></div>
		<?php endif; ?>
		<p>Le catalogue officiel est mis à jour chaque jour à partir de YouTube (@OULBIG, OULBIG – Topic, @TALIM4Art) et Spotify. Musicology en importe les chansons (avec leurs vues pour le hit-parade) et affiche les podcasts « Étincelle » sur la page Livres audio &amp; podcasts. Synchronisation automatique toutes les heures.</p>
		<table class="widefat striped" style="max-width:760px">
			<tr><th>Catalogue</th><td><?php echo $cat ? count( $cat['items'] ) . ' éléments · généré le ' . esc_html( $cat['generated'] ?? '?' ) . ' · source : ' . esc_html( $src[ get_option( 'mcl_catalogue_source', 'snapshot' ) ] ?? '' ) : 'indisponible'; ?></td></tr>
			<tr><th>Titres sur le site</th><td><?php echo (int) $nb; ?> publiés, dont <?php echo (int) $syn; ?> synchronisés</td></tr>
			<tr><th>Dernière synchro</th><td><?php echo $last ? esc_html( wp_date( 'd/m/Y H:i', $last['time'] ) ) . ' — +' . (int) $last['stats']['created'] . ' nouveaux, ' . (int) $last['stats']['updated'] . ' mis à jour, ' . (int) $last['stats']['pending'] . ' en attente' : 'jamais'; ?></td></tr>
		</table>
		<p><a class="button button-primary button-hero" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mcl_sync_now' ), 'mcl_sync_now' ) ); ?>">Synchroniser maintenant</a></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'mcl_sync' ); ?>
			<table class="form-table" role="presentation">
				<tr><th>Synchro automatique</th><td><label><input type="checkbox" name="mcl_sync[enabled]" value="1" <?php checked( mcl_sync_opt( 'enabled' ), 1 ); ?>> Activée (toutes les heures)</label></td></tr>
				<tr><th>Artistes</th><td>
					<?php foreach ( array( 'OULBIG', 'TALIM' ) as $a ) : ?>
						<label style="margin-right:16px"><input type="checkbox" name="mcl_sync[artists][]" value="<?php echo esc_attr( $a ); ?>" <?php checked( in_array( $a, (array) mcl_sync_opt( 'artists' ), true ) ); ?>> <?php echo esc_html( $a ); ?></label>
					<?php endforeach; ?>
				</td></tr>
				<tr><th>Nombre de titres</th><td><input type="number" min="1" max="1000" name="mcl_sync[max]" value="<?php echo (int) mcl_sync_opt( 'max' ); ?>"><p class="description">Tous les titres de TALIM, puis les titres OULBIG les plus vus. Le catalogue en compte <?php echo $cat ? count( array_filter( $cat['items'], function ( $i ) { return 'song' === $i['type']; } ) ) : '?'; ?>.</p></td></tr>
				<tr><th>Titres de démo</th><td><label><input type="checkbox" name="mcl_sync[del_demo]" value="1" <?php checked( mcl_sync_opt( 'del_demo' ), 1 ); ?>> Les supprimer automatiquement dès la première synchro</label></td></tr>
				<tr><th>Adresse du catalogue</th><td><input type="url" class="large-text" name="mcl_sync[url]" value="<?php echo esc_attr( mcl_sync_opt( 'url' ) ); ?>"></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<p class="description">Vos modifications sur un titre synchronisé (titre, paroles, pochette, genre) sont conservées : la synchro ne met à jour que les vues. Supprimer un titre synchronisé le fait revenir à la prochaine synchro : passez-le plutôt en brouillon.</p>
	</div>
	<?php
}

/* Rappel dans l'admin tant que le répertoire n'est pas importé. */
add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) || get_posts( array( 'post_type' => 'mcl_song', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_mcl_sync_id' ) ) ) {
		return;
	}
	echo '<div class="notice notice-warning"><p><strong>Musicology</strong> — importez le vrai répertoire OULBIG / TALIM (chansons, clips, vues YouTube). <a class="button button-primary" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mcl_sync_now' ), 'mcl_sync_now' ) ) . '">Synchroniser maintenant</a></p></div>';
} );
