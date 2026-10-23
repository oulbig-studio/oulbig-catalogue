<?php
/**
 * Installation automatique à l'activation : genres, pages (hit-parade + 4 pages de demande),
 * titres de démonstration (supprimables en un clic).
 */
defined( 'ABSPATH' ) || exit;

add_action( 'after_switch_theme', 'mcl_install' );
add_action( 'admin_init', function () {
	if ( get_option( 'mcl_installed' ) !== MCL_VERSION ) {
		mcl_install();
	}
} );

function mcl_install() {
	// Thème installé dans un nouveau dossier : on reprend menus et logo de l'ancienne version.
	$self = get_stylesheet();
	if ( ! get_option( 'theme_mods_' . $self ) ) {
		foreach ( array( 'musicology', 'musicology-light', 'hostinger-ai-theme' ) as $old ) {
			$mods = $old !== $self ? get_option( 'theme_mods_' . $old ) : false;
			if ( is_array( $mods ) && ( ! empty( $mods['nav_menu_locations'] ) || ! empty( $mods['custom_logo'] ) ) ) {
				update_option( 'theme_mods_' . $self, $mods );
				break;
			}
		}
	}

	// Genres.
	foreach ( array( 'Pop', 'Rap', 'Raï', 'Chaabi', 'R&B', 'Oriental', 'Gnaoua', 'Andalou', 'Piano-voix', 'Soul' ) as $g ) {
		if ( ! term_exists( $g, 'mcl_genre' ) ) {
			wp_insert_term( $g, 'mcl_genre' );
		}
	}

	// Page Hit-parade.
	$charts = (int) get_option( 'mcl_charts_page' );
	if ( ! $charts || ! get_post( $charts ) ) {
		$charts = wp_insert_post( array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_title'  => 'Hit-Parade',
			'post_name'   => 'hit-parade',
		) );
		update_post_meta( $charts, '_wp_page_template', 'page-templates/charts.php' );
		update_option( 'mcl_charts_page', $charts );
	}

	// Une page de demande distincte par langue.
	$defs  = array(
		'fr' => array( 'Demande de musique spéciale', 'demande-speciale' ),
		'en' => array( 'Special music request', 'special-request' ),
		'es' => array( 'Pedido de música especial', 'pedido-especial' ),
		'ar' => array( 'طلب موسيقى خاصة', 'talab-khass' ),
	);
	$pages = (array) get_option( 'mcl_request_pages', array() );
	foreach ( $defs as $lang => $d ) {
		if ( ! empty( $pages[ $lang ] ) && get_post( $pages[ $lang ] ) ) {
			continue;
		}
		$pid = wp_insert_post( array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_title'  => $d[0],
			'post_name'   => $d[1],
		) );
		update_post_meta( $pid, '_wp_page_template', 'page-templates/request.php' );
		update_post_meta( $pid, '_mcl_lang', $lang );
		$pages[ $lang ] = $pid;
	}
	update_option( 'mcl_request_pages', $pages );

	// Services : ajoute les nouveaux et met à jour les services remplacés (les autres ne sont pas touchés).
	if ( (int) get_option( 'mcl_services_seeded' ) < 2 ) {
		mcl_seed_services();
		update_option( 'mcl_services_seeded', 2 );
	}
	mcl_setup_blog_pages();
	mcl_setup_audio_page();
	if ( ! get_option( 'mcl_blog_seeded' ) ) {
		mcl_seed_blog();
		update_option( 'mcl_blog_seeded', 1 );
	}

	if ( ! get_option( 'mcl_demo_seeded' ) ) {
		mcl_seed_demo();
		update_option( 'mcl_demo_seeded', 1 );
	}

	$opts = get_option( 'mcl_settings' );
	if ( ! $opts ) {
		update_option( 'mcl_settings', mcl_defaults() );
	} else {
		// 1.2 : format international + 2e numéro WhatsApp + tarifs.
		if ( isset( $opts['phone'] ) && '0661426203' === preg_replace( '/\D+/', '', $opts['phone'] ) ) {
			$opts['phone'] = '+212 661 42 62 03';
		}
		$opts = wp_parse_args( $opts, mcl_defaults() );
		update_option( 'mcl_settings', $opts );
	}
	update_option( 'mcl_installed', MCL_VERSION );
	flush_rewrite_rules();
}

function mcl_seed_demo() {
	$audio = MCL_URI . '/assets/demo/demo-melody.mp3';
	$clip  = MCL_URI . '/assets/demo/demo-clip.mp4';
	$songs = array(
		array(
			'title' => 'Sous la même étoile', 'genre' => 'Pop', 'lang' => 'fr', 'artist' => 'Musicology Studio', 'dur' => '3:24',
			'audio' => $audio, 'video' => $clip, 'pa' => 1240, 'pv' => 860,
			'titles' => array( 'en' => 'Under the Same Star', 'es' => 'Bajo la misma estrella', 'ar' => 'تحت النجمة نفسها' ),
			'excerpt' => 'Une chanson d\'anniversaire de mariage, écrite à partir de trente ans de souvenirs.',
			'lyrics' => "On s'est trouvés un soir d'été\nSous la même étoile, sans rien demander\nTrente ans plus tard, ta main dans la mienne\nC'est toujours toi, c'est toujours nous\n\n[Refrain]\nEt si le temps passe, je reste là\nChaque matin je te choisis\nSous la même étoile, toi et moi\nNotre histoire, notre mélodie",
			'tr' => array(
				'en' => "We found each other one summer night\nUnder the same star, asking for nothing\nThirty years later, your hand in mine\nIt's still you, it's still us\n\n[Chorus]\nAnd if time goes by, I'm still here\nEvery morning I choose you\nUnder the same star, you and me\nOur story, our melody",
				'es' => "Nos encontramos una noche de verano\nBajo la misma estrella, sin pedir nada\nTreinta años después, tu mano en la mía\nSigues siendo tú, seguimos siendo nosotros\n\n[Estribillo]\nY si el tiempo pasa, aquí sigo\nCada mañana te elijo\nBajo la misma estrella, tú y yo\nNuestra historia, nuestra melodía",
				'ar' => "التقينا في ليلة صيفية\nتحت النجمة نفسها، دون أن نطلب شيئاً\nبعد ثلاثين عاماً، يدك في يدي\nما زلتِ أنتِ، وما زلنا نحن\n\n[اللازمة]\nوإن مرّ الزمن، أبقى هنا\nكل صباح أختارك من جديد\nتحت النجمة نفسها، أنا وأنتِ\nقصتنا، لحننا",
			),
		),
		array(
			'title' => 'Nour', 'genre' => 'Raï', 'lang' => 'darija', 'artist' => 'Musicology Studio', 'dur' => '3:58',
			'audio' => $audio, 'video' => $clip, 'pa' => 980, 'pv' => 1320,
			'titles' => array( 'ar' => 'نور' ),
			'excerpt' => 'Une déclaration en darija sur un raï moderne, avec clip tourné de nuit.',
			'lyrics' => "نتي النور ديال حياتي\nملي جيتي تبدلات الدنيا\nكل نهار نقول ليك\nبلا بيك ما كاين والو\n\n[اللازمة]\nنور، يا نور\nقلبي معاك يدور\nنور، يا نور\nنتي الفرحة والسرور",
			'tr' => array(
				'fr' => "Tu es la lumière de ma vie\nDepuis que tu es là, le monde a changé\nChaque jour je te le dis\nSans toi, il n'y a rien\n\n[Refrain]\nNour, ô Nour\nMon cœur tourne avec toi\nNour, ô Nour\nTu es la joie et le bonheur",
				'en' => "You are the light of my life\nSince you came, the world has changed\nEvery day I tell you\nWithout you there is nothing\n\n[Chorus]\nNour, oh Nour\nMy heart turns with you\nNour, oh Nour\nYou are joy and happiness",
			),
		),
		array(
			'title' => 'Twenty-Five Candles', 'genre' => 'R&B', 'lang' => 'en', 'artist' => 'Musicology Studio', 'dur' => '2:51',
			'audio' => $audio, 'video' => '', 'pa' => 760, 'pv' => 0,
			'titles' => array( 'fr' => 'Vingt-cinq bougies', 'es' => 'Veinticinco velas', 'ar' => 'خمس وعشرون شمعة' ),
			'excerpt' => 'Un R&B doux pour un 25ᵉ anniversaire, offert par une sœur.',
			'lyrics' => "Twenty-five candles, one little wish\nI hope you know you're the best gift\nFrom the backyard games to the city lights\nYou were always my guiding light\n\n[Chorus]\nSo blow them out, make it count\nThis is your year, there's no doubt",
			'tr' => array(
				'fr' => "Vingt-cinq bougies, un petit vœu\nJ'espère que tu sais que tu es le plus beau cadeau\nDes jeux dans le jardin aux lumières de la ville\nTu as toujours été ma lumière\n\n[Refrain]\nAlors souffle-les, fais un vœu\nC'est ton année, sans aucun doute",
			),
		),
		array(
			'title' => 'Canción para Lucía', 'genre' => 'Pop', 'lang' => 'es', 'artist' => 'Musicology Studio', 'dur' => '3:12',
			'audio' => $audio, 'video' => $clip, 'pa' => 540, 'pv' => 410,
			'titles' => array( 'fr' => 'Chanson pour Lucía', 'en' => 'A Song for Lucía', 'ar' => 'أغنية إلى لوسيا' ),
			'excerpt' => 'Une demande en mariage en chanson, en espagnol.',
			'lyrics' => "Lucía, mírame a los ojos\nTengo algo que decirte hoy\nCon esta guitarra y estos versos\nTe regalo todo lo que soy\n\n[Estribillo]\n¿Te quieres casar conmigo?\nQue la vida sea nuestra canción",
			'tr' => array(
				'fr' => "Lucía, regarde-moi dans les yeux\nJ'ai quelque chose à te dire aujourd'hui\nAvec cette guitare et ces vers\nJe t'offre tout ce que je suis\n\n[Refrain]\nVeux-tu m'épouser ?\nQue la vie soit notre chanson",
			),
		),
		array(
			'title' => 'Mama', 'genre' => 'Chaabi', 'lang' => 'darija', 'artist' => 'Musicology Studio', 'dur' => '4:05',
			'audio' => $audio, 'video' => '', 'pa' => 1510, 'pv' => 0,
			'titles' => array( 'ar' => 'ماما' ),
			'excerpt' => 'Un chaabi festif pour la fête des mères.',
			'lyrics' => "ماما يا الغالية\nنتي الدوا ونتي الضو\nسهرتي عليا الليالي\nوعطيتيني كلشي بلا ما تشكي\n\n[اللازمة]\nماما، الله يخليك ليا\nفرحتك هي فرحتي",
			'tr' => array(
				'fr' => "Maman, ma précieuse\nTu es le remède et la lumière\nTu as veillé sur moi des nuits entières\nEt tu m'as tout donné sans jamais te plaindre\n\n[Refrain]\nMaman, que Dieu te garde pour moi\nTa joie est ma joie",
			),
		),
	);

	foreach ( $songs as $i => $s ) {
		$id = wp_insert_post( array(
			'post_type'    => 'mcl_song',
			'post_status'  => 'publish',
			'post_title'   => $s['title'],
			'post_excerpt' => $s['excerpt'],
			'post_content' => $s['excerpt'] . "\n\n(Titre de démonstration — remplacez-le par vos propres créations.)",
			'post_date'    => gmdate( 'Y-m-d H:i:s', time() - ( $i * DAY_IN_SECONDS ) ),
		) );
		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}
		wp_set_object_terms( $id, $s['genre'], 'mcl_genre' );
		update_post_meta( $id, '_mcl_demo', 1 );
		update_post_meta( $id, '_mcl_artist', $s['artist'] );
		update_post_meta( $id, '_mcl_duration', $s['dur'] );
		update_post_meta( $id, '_mcl_audio_url', $s['audio'] );
		update_post_meta( $id, '_mcl_video_url', $s['video'] );
		update_post_meta( $id, '_mcl_lyrics', $s['lyrics'] );
		update_post_meta( $id, '_mcl_lyrics_lang', $s['lang'] );
		foreach ( $s['tr'] as $l => $t ) {
			update_post_meta( $id, '_mcl_lyrics_' . $l, $t );
		}
		foreach ( $s['titles'] as $l => $t ) {
			update_post_meta( $id, '_mcl_title_' . $l, $t );
		}
		update_post_meta( $id, '_mcl_plays_audio', $s['pa'] );
		update_post_meta( $id, '_mcl_plays_video', $s['pv'] );
		update_post_meta( $id, mcl_month_key( 'audio' ), (int) round( $s['pa'] / 6 ) );
		if ( $s['pv'] ) {
			update_post_meta( $id, mcl_month_key( 'video' ), (int) round( $s['pv'] / 5 ) );
		}
	}
}

/* Notice + suppression des démos en un clic */
add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$demo = get_posts( array( 'post_type' => array( 'mcl_song', 'post' ), 'meta_key' => '_mcl_demo', 'meta_value' => 1, 'posts_per_page' => 1, 'fields' => 'ids' ) );
	if ( ! $demo ) {
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=mcl_delete_demo' ), 'mcl_delete_demo' );
	echo '<div class="notice notice-info"><p><strong>Musicology</strong> — des titres et des articles de démonstration sont en ligne pour montrer la mise en page. <a class="button" href="' . esc_url( $url ) . '" onclick="return confirm(\'Supprimer tous les titres et articles de démo ?\')">Supprimer les démos</a> &nbsp; <a href="' . esc_url( admin_url( 'post-new.php?post_type=mcl_song' ) ) . '">+ Ajouter mon premier titre</a></p></div>';
} );

add_action( 'admin_post_mcl_delete_demo', function () {
	check_admin_referer( 'mcl_delete_demo' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Accès refusé.' );
	}
	$ids = get_posts( array( 'post_type' => array( 'mcl_song', 'post', 'attachment' ), 'meta_key' => '_mcl_demo', 'meta_value' => 1, 'posts_per_page' => -1, 'fields' => 'ids', 'post_status' => 'any' ) );
	foreach ( $ids as $id ) {
		wp_delete_post( $id, true );
	}
	wp_safe_redirect( admin_url( 'edit.php?post_type=mcl_song' ) );
	exit;
} );
