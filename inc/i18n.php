<?php
/**
 * Musicology — système multilingue intégré (FR / EN / ES / AR) sans plugin.
 * La langue est choisie via ?lang=xx (mémorisée en cookie) ou imposée par la page
 * (méta _mcl_lang sur les pages de demande).
 */
defined( 'ABSPATH' ) || exit;

function mcl_langs() {
	return array(
		'fr' => array( 'label' => 'Français', 'short' => 'FR', 'dir' => 'ltr', 'html' => 'fr-FR' ),
		'en' => array( 'label' => 'English',  'short' => 'EN', 'dir' => 'ltr', 'html' => 'en' ),
		'es' => array( 'label' => 'Español',  'short' => 'ES', 'dir' => 'ltr', 'html' => 'es' ),
		'ar' => array( 'label' => 'العربية',  'short' => 'ع',  'dir' => 'rtl', 'html' => 'ar' ),
	);
}

function mcl_valid_lang( $l ) {
	return is_string( $l ) && array_key_exists( $l, mcl_langs() );
}

/** Mémorise le choix ?lang=xx dans un cookie (avant l'envoi des en-têtes). */
add_action( 'init', function () {
	if ( is_admin() || empty( $_GET['lang'] ) ) {
		return;
	}
	$l = sanitize_key( wp_unslash( $_GET['lang'] ) );
	if ( mcl_valid_lang( $l ) && ! headers_sent() ) {
		setcookie( 'mcl_lang', $l, time() + YEAR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), false );
		$_COOKIE['mcl_lang'] = $l;
	}
}, 1 );

/** Langue courante. */
function mcl_lang() {
	static $resolved = null;
	if ( null !== $resolved ) {
		return $resolved;
	}
	$lang = '';
	// 1) Page dont la langue est imposée (pages de demande par langue).
	if ( did_action( 'wp' ) && is_page() ) {
		$forced = get_post_meta( get_queried_object_id(), '_mcl_lang', true );
		if ( mcl_valid_lang( $forced ) ) {
			$lang = $forced;
		}
	}
	// 2) Paramètre d'URL.
	if ( ! $lang && ! empty( $_GET['lang'] ) ) {
		$l = sanitize_key( wp_unslash( $_GET['lang'] ) );
		if ( mcl_valid_lang( $l ) ) {
			$lang = $l;
		}
	}
	// 3) Langue envoyée par AJAX.
	if ( ! $lang && wp_doing_ajax() && ! empty( $_POST['lang'] ) ) {
		$l = sanitize_key( wp_unslash( $_POST['lang'] ) );
		if ( mcl_valid_lang( $l ) ) {
			$lang = $l;
		}
	}
	// 4) Cookie.
	if ( ! $lang && ! empty( $_COOKIE['mcl_lang'] ) ) {
		$l = sanitize_key( wp_unslash( $_COOKIE['mcl_lang'] ) );
		if ( mcl_valid_lang( $l ) ) {
			$lang = $l;
		}
	}
	// 5) Langue par défaut (réglages).
	if ( ! $lang ) {
		$d    = mcl_opt( 'default_lang' );
		$lang = mcl_valid_lang( $d ) ? $d : 'fr';
	}
	if ( did_action( 'wp' ) ) {
		$resolved = $lang;
	}
	return $lang;
}

function mcl_is_rtl() {
	return 'rtl' === mcl_langs()[ mcl_lang() ]['dir'];
}

/** Attributs lang / dir de la balise <html>. */
add_filter( 'language_attributes', function ( $output ) {
	if ( is_admin() ) {
		return $output;
	}
	$l = mcl_langs()[ mcl_lang() ];
	return 'lang="' . esc_attr( $l['html'] ) . '" dir="' . esc_attr( $l['dir'] ) . '"';
} );

add_filter( 'body_class', function ( $classes ) {
	$classes[] = 'mcl-lang-' . mcl_lang();
	if ( mcl_is_rtl() ) {
		$classes[] = 'mcl-rtl';
	}
	return $classes;
} );

/** Traduit une clé. Les chaînes peuvent contenir %s / %1$s. */
function mcl_t( $key, ...$args ) {
	$dict = mcl_dict();
	$lang = mcl_lang();
	if ( ! isset( $dict[ $key ] ) ) {
		return $key;
	}
	$s = isset( $dict[ $key ][ $lang ] ) ? $dict[ $key ][ $lang ] : $dict[ $key ]['fr'];
	$s = $args ? vsprintf( $s, $args ) : $s;
	if ( 'fr' === $lang ) {
		// Typographie française : espace insécable avant ? ! : ; » et après «.
		$s = preg_replace( array( '/ ([?!:;»])/u', '/« /u' ), array( "\u{00A0}$1", "«\u{00A0}" ), $s );
	}
	return $s;
}

/** Traduction dans une langue précise (emails, admin). */
function mcl_t_in( $lang, $key ) {
	$dict = mcl_dict();
	if ( ! isset( $dict[ $key ] ) ) {
		return $key;
	}
	return isset( $dict[ $key ][ $lang ] ) ? $dict[ $key ][ $lang ] : $dict[ $key ]['fr'];
}

function mcl_e( $key, ...$args ) {
	echo esc_html( mcl_t( $key, ...$args ) );
}

/** Pour les chaînes qui contiennent <em> (mot mis en avant). */
function mcl_e_html( $key, ...$args ) {
	echo wp_kses( mcl_t( $key, ...$args ), array( 'em' => array( 'data-name' => array() ), 'br' => array(), 'strong' => array( 'data-name' => array() ) ) );
}

/** URL de la page de demande dans une langue donnée. */
function mcl_request_url( $lang = null ) {
	$lang  = $lang ?: mcl_lang();
	$pages = get_option( 'mcl_request_pages', array() );
	if ( ! empty( $pages[ $lang ] ) && 'publish' === get_post_status( $pages[ $lang ] ) ) {
		return get_permalink( $pages[ $lang ] );
	}
	if ( ! empty( $pages['fr'] ) && 'publish' === get_post_status( $pages['fr'] ) ) {
		return get_permalink( $pages['fr'] );
	}
	return home_url( '/' );
}

/** URL de changement de langue pour la page courante. */
function mcl_switch_url( $lang ) {
	if ( is_page() && get_post_meta( get_queried_object_id(), '_mcl_lang', true ) ) {
		return mcl_request_url( $lang );
	}
	$scheme = is_ssl() ? 'https://' : 'http://';
	$host   = isset( $_SERVER['HTTP_HOST'] ) ? wp_unslash( $_SERVER['HTTP_HOST'] ) : '';
	$uri    = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	return add_query_arg( 'lang', $lang, esc_url_raw( $scheme . $host . $uri ) );
}

function mcl_lang_switcher( $class = '' ) {
	echo '<nav class="mcl-langs ' . esc_attr( $class ) . '" aria-label="' . esc_attr( mcl_t( 'lang_label' ) ) . '">';
	foreach ( mcl_langs() as $code => $l ) {
		printf(
			'<a href="%1$s" hreflang="%2$s" class="%3$s" title="%4$s">%5$s</a>',
			esc_url( mcl_switch_url( $code ) ),
			esc_attr( $code ),
			$code === mcl_lang() ? 'is-active' : '',
			esc_attr( $l['label'] ),
			esc_html( $l['short'] )
		);
	}
	echo '</nav>';
}

/** Titre traduit d'une chanson (méta _mcl_title_xx, sinon titre WP). */
function mcl_song_title( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$tr      = get_post_meta( $post_id, '_mcl_title_' . mcl_lang(), true );
	return $tr ? $tr : get_the_title( $post_id );
}

/**
 * Listes d'options du formulaire : clé => [fr, en, es, ar].
 * Les clés sont stockées en base ; le libellé FR sert dans l'admin.
 */
function mcl_options( $group ) {
	$o = array(
		'relation' => array(
			'wife'     => array( 'Ma femme', 'My wife', 'Mi esposa', 'زوجتي' ),
			'husband'  => array( 'Mon mari', 'My husband', 'Mi esposo', 'زوجي' ),
			'gf'       => array( 'Ma copine', 'My girlfriend', 'Mi novia', 'حبيبتي' ),
			'bf'       => array( 'Mon copain', 'My boyfriend', 'Mi novio', 'حبيبي' ),
			'mother'   => array( 'Ma mère', 'My mother', 'Mi madre', 'أمي' ),
			'father'   => array( 'Mon père', 'My father', 'Mi padre', 'أبي' ),
			'children' => array( 'Mes enfants', 'My children', 'Mis hijos', 'أطفالي' ),
			'sibling'  => array( 'Frère / Sœur', 'Brother / Sister', 'Hermano / Hermana', 'أخي / أختي' ),
			'friend'   => array( 'Un(e) ami(e)', 'A friend', 'Un(a) amigo(a)', 'صديق(ة)' ),
			'me'       => array( 'Pour moi', 'For me', 'Para mí', 'لي أنا' ),
			'other'    => array( 'Une personne qui compte', 'Someone who matters', 'Alguien que importa', 'شخص عزيز' ),
		),
		'occasion' => array(
			'birthday'  => array( '🎂 Anniversaire', '🎂 Birthday', '🎂 Cumpleaños', '🎂 عيد ميلاد' ),
			'wedding'   => array( '💍 Mariage', '💍 Wedding', '💍 Boda', '💍 زفاف' ),
			'proposal'  => array( '💎 Demande en mariage', '💎 Proposal', '💎 Pedida de mano', '💎 طلب الزواج' ),
			'mothers'   => array( '💐 Fête des mères', '💐 Mother\'s Day', '💐 Día de la Madre', '💐 عيد الأم' ),
			'love'      => array( '❤️ Déclaration d\'amour', '❤️ Love declaration', '❤️ Declaración de amor', '❤️ إعلان حب' ),
			'anniv'     => array( '🥂 Anniversaire de couple', '🥂 Anniversary', '🥂 Aniversario', '🥂 ذكرى زواج' ),
			'graduation'=> array( '🎓 Réussite / Diplôme', '🎓 Graduation', '🎓 Graduación', '🎓 نجاح / تخرج' ),
			'tribute'   => array( '🕊️ Hommage', '🕊️ Tribute', '🕊️ Homenaje', '🕊️ تكريم' ),
			'other'     => array( '✨ Autre occasion', '✨ Other occasion', '✨ Otra ocasión', '✨ مناسبة أخرى' ),
		),
		'song_lang' => array(
			'fr'      => array( 'Français', 'French', 'Francés', 'الفرنسية' ),
			'ar'      => array( 'Arabe', 'Arabic', 'Árabe', 'العربية الفصحى' ),
			'darija'  => array( 'Darija', 'Darija (Moroccan)', 'Darija (marroquí)', 'الدارجة' ),
			'en'      => array( 'Anglais', 'English', 'Inglés', 'الإنجليزية' ),
			'es'      => array( 'Espagnol', 'Spanish', 'Español', 'الإسبانية' ),
			'amazigh' => array( 'Amazigh', 'Amazigh', 'Amazigh', 'الأمازيغية' ),
			'mix'     => array( 'Mélange de langues', 'Mix of languages', 'Mezcla de idiomas', 'مزيج من اللغات' ),
		),
		'genre' => array(
			'pop'      => array( 'Pop', 'Pop', 'Pop', 'بوب' ),
			'rap'      => array( 'Rap', 'Rap', 'Rap', 'راب' ),
			'rai'      => array( 'Raï', 'Raï', 'Raï', 'راي' ),
			'chaabi'   => array( 'Chaabi', 'Chaabi', 'Chaabi', 'شعبي' ),
			'rnb'      => array( 'R&B', 'R&B', 'R&B', 'آر أند بي' ),
			'oriental' => array( 'Oriental', 'Oriental', 'Oriental', 'شرقي' ),
			'piano'    => array( 'Piano-voix émouvant', 'Emotional piano & voice', 'Piano y voz emotivo', 'بيانو وصوت مؤثر' ),
			'guitar'   => array( 'Guitare-voix intime', 'Intimate guitar & voice', 'Guitarra y voz íntima', 'غيتار وصوت حميمي' ),
			'soul'     => array( 'Soul', 'Soul', 'Soul', 'سول' ),
			'gnawa'    => array( 'Gnaoua', 'Gnawa', 'Gnawa', 'كناوة' ),
			'andalou'  => array( 'Andalou', 'Andalusian', 'Andalusí', 'أندلسي' ),
			'surprise' => array( 'Surprenez-moi', 'Surprise me', 'Sorpréndeme', 'فاجئوني' ),
		),
		'voice' => array(
			'female' => array( 'Voix féminine', 'Female voice', 'Voz femenina', 'صوت نسائي' ),
			'male'   => array( 'Voix masculine', 'Male voice', 'Voz masculina', 'صوت رجالي' ),
			'duo'    => array( 'Duo', 'Duet', 'Dúo', 'ثنائي' ),
			'any'    => array( 'Pas de préférence', 'No preference', 'Sin preferencia', 'بدون تفضيل' ),
		),
		'quality' => array(
			'funny'      => array( 'Drôle', 'Funny', 'Divertido/a', 'مرح' ),
			'generous'   => array( 'Généreux·se', 'Generous', 'Generoso/a', 'كريم' ),
			'patient'    => array( 'Patient·e', 'Patient', 'Paciente', 'صبور' ),
			'gentle'     => array( 'Doux·ce', 'Gentle', 'Dulce', 'لطيف' ),
			'strong'     => array( 'Fort·e', 'Strong', 'Fuerte', 'قوي' ),
			'brave'      => array( 'Courageux·se', 'Brave', 'Valiente', 'شجاع' ),
			'caring'     => array( 'Attentionné·e', 'Caring', 'Atento/a', 'حنون' ),
			'spontaneous'=> array( 'Spontané·e', 'Spontaneous', 'Espontáneo/a', 'عفوي' ),
			'wise'       => array( 'Sage', 'Wise', 'Sabio/a', 'حكيم' ),
			'creative'   => array( 'Créatif·ve', 'Creative', 'Creativo/a', 'مبدع' ),
			'adventurous'=> array( 'Aventurier·ère', 'Adventurous', 'Aventurero/a', 'مغامر' ),
			'empathic'   => array( 'Empathique', 'Empathetic', 'Empático/a', 'متعاطف' ),
			'reliable'   => array( 'Fiable', 'Reliable', 'Confiable', 'وفي' ),
			'romantic'   => array( 'Romantique', 'Romantic', 'Romántico/a', 'رومانسي' ),
			'energetic'  => array( 'Énergique', 'Energetic', 'Enérgico/a', 'نشيط' ),
		),
		'clip' => array(
			'yes'   => array( 'Oui : chanson + clip vidéo', 'Yes: song + music video', 'Sí: canción + videoclip', 'نعم: أغنية + فيديو كليب' ),
			'no'    => array( 'La chanson seulement', 'The song only', 'Solo la canción', 'الأغنية فقط' ),
			'maybe' => array( 'J\'aimerais en discuter', 'I\'d like to discuss it', 'Me gustaría hablarlo', 'أود مناقشة ذلك' ),
		),
		'ab_type' => array(
			'audiobook' => array( '📖 Livre audio', '📖 Audiobook', '📖 Audiolibro', '📖 كتاب صوتي' ),
			'podcast'   => array( '🎙️ Podcast', '🎙️ Podcast', '🎙️ Pódcast', '🎙️ بودكاست' ),
		),
		'ab_source' => array(
			'printed' => array( 'Livre imprimé', 'Printed book', 'Libro impreso', 'كتاب مطبوع' ),
			'novel'   => array( 'Roman / nouvelles', 'Novel / stories', 'Novela / relatos', 'رواية / قصص' ),
			'ebook'   => array( 'Ebook (PDF, EPUB, Word)', 'Ebook (PDF, EPUB, Word)', 'Ebook (PDF, EPUB, Word)', 'كتاب إلكتروني (PDF، EPUB، Word)' ),
			'other'   => array( 'Manuscrit / autre', 'Manuscript / other', 'Manuscrito / otro', 'مخطوط / غير ذلك' ),
		),
		'ab_voice' => array(
			'female' => array( 'Voix féminine', 'Female voice', 'Voz femenina', 'صوت نسائي' ),
			'male'   => array( 'Voix masculine', 'Male voice', 'Voz masculina', 'صوت رجالي' ),
			'multi'  => array( 'Plusieurs voix', 'Several voices', 'Varias voces', 'عدة أصوات' ),
			'any'    => array( 'Conseillez-moi', 'Advise me', 'Aconséjame', 'انصحوني' ),
		),
		'ab_pformat' => array(
			'solo'      => array( 'Solo', 'Solo', 'Solo', 'فردي' ),
			'interview' => array( 'Interview / invités', 'Interview / guests', 'Entrevista / invitados', 'حوار / ضيوف' ),
			'story'     => array( 'Récit / fiction audio', 'Storytelling / audio drama', 'Relato / ficción sonora', 'حكي / دراما صوتية' ),
		),
		'mode' => array(
			'story'  => array( 'Je raconte mon histoire', 'I\'ll tell my story', 'Cuento mi historia', 'سأحكي قصتي' ),
			'lyrics' => array( 'J\'ai déjà mes paroles', 'I already have lyrics', 'Ya tengo la letra', 'لدي كلماتي جاهزة' ),
		),
	);
	return isset( $o[ $group ] ) ? $o[ $group ] : array();
}

/** Libellé d'une option dans la langue voulue (par défaut : courante). */
function mcl_opt_label( $group, $key, $lang = null ) {
	$lang  = $lang ?: mcl_lang();
	$idx   = array_search( $lang, array( 'fr', 'en', 'es', 'ar' ), true );
	$items = mcl_options( $group );
	if ( ! isset( $items[ $key ] ) ) {
		return $key;
	}
	return $items[ $key ][ false === $idx ? 0 : $idx ];
}

/** Dictionnaire de l'interface. */
function mcl_dict() {
	static $d = null;
	if ( null !== $d ) {
		return $d;
	}
	$d = array(
		// Navigation & général.
		'nav_home'     => array( 'fr' => 'Accueil', 'en' => 'Home', 'es' => 'Inicio', 'ar' => 'الرئيسية' ),
		'nav_songs'    => array( 'fr' => 'Chansons', 'en' => 'Songs', 'es' => 'Canciones', 'ar' => 'الأغاني' ),
		'nav_clips'    => array( 'fr' => 'Clips', 'en' => 'Videos', 'es' => 'Videoclips', 'ar' => 'الكليبات' ),
		'nav_charts'   => array( 'fr' => 'Hit-Parade', 'en' => 'Charts', 'es' => 'Lo más escuchado', 'ar' => 'الأكثر استماعاً' ),
		'nav_request'  => array( 'fr' => 'Ta chanson sur mesure', 'en' => 'Your custom song', 'es' => 'Tu canción a medida', 'ar' => 'أغنيتك حسب الطلب' ),
		'lang_label'   => array( 'fr' => 'Langue', 'en' => 'Language', 'es' => 'Idioma', 'ar' => 'اللغة' ),
		'menu'         => array( 'fr' => 'Menu', 'en' => 'Menu', 'es' => 'Menú', 'ar' => 'القائمة' ),
		'see_all'      => array( 'fr' => 'Tout voir', 'en' => 'See all', 'es' => 'Ver todo', 'ar' => 'عرض الكل' ),
		'all'          => array( 'fr' => 'Tout', 'en' => 'All', 'es' => 'Todo', 'ar' => 'الكل' ),
		'audio'        => array( 'fr' => 'Audio', 'en' => 'Audio', 'es' => 'Audio', 'ar' => 'صوت' ),
		'video'        => array( 'fr' => 'Clip', 'en' => 'Video', 'es' => 'Videoclip', 'ar' => 'كليب' ),
		'listen'       => array( 'fr' => 'Écouter', 'en' => 'Listen', 'es' => 'Escuchar', 'ar' => 'استمع' ),
		'watch'        => array( 'fr' => 'Regarder le clip', 'en' => 'Watch the video', 'es' => 'Ver el videoclip', 'ar' => 'شاهد الكليب' ),
		'open_link'    => array( 'fr' => 'Ouvrir le lien', 'en' => 'Open link', 'es' => 'Abrir enlace', 'ar' => 'افتح الرابط' ),
		'plays_n'      => array( 'fr' => '%s écoutes', 'en' => '%s plays', 'es' => '%s escuchas', 'ar' => '%s استماع' ),
		'views_n'      => array( 'fr' => '%s vues', 'en' => '%s views', 'es' => '%s vistas', 'ar' => '%s مشاهدة' ),
		'search_ph'    => array( 'fr' => 'Rechercher une chanson, un artiste…', 'en' => 'Search a song, an artist…', 'es' => 'Buscar una canción, un artista…', 'ar' => 'ابحث عن أغنية أو فنان…' ),
		'search'       => array( 'fr' => 'Rechercher', 'en' => 'Search', 'es' => 'Buscar', 'ar' => 'بحث' ),
		'media_soon'   => array( 'fr' => 'Bientôt disponible', 'en' => 'Coming soon', 'es' => 'Próximamente', 'ar' => 'قريباً' ),
		'empty'        => array( 'fr' => 'Les premiers titres arrivent très bientôt.', 'en' => 'The first tracks are coming very soon.', 'es' => 'Los primeros temas llegan muy pronto.', 'ar' => 'أولى الأغاني قادمة قريباً جداً.' ),
		'genre'        => array( 'fr' => 'Genre', 'en' => 'Genre', 'es' => 'Género', 'ar' => 'النوع' ),
		'artist'       => array( 'fr' => 'Artiste', 'en' => 'Artist', 'es' => 'Artista', 'ar' => 'الفنان' ),
		'related'      => array( 'fr' => 'Vous aimerez aussi', 'en' => 'You may also like', 'es' => 'También te gustará', 'ar' => 'قد يعجبك أيضاً' ),
		'about_song'   => array( 'fr' => 'À propos', 'en' => 'About', 'es' => 'Acerca de', 'ar' => 'نبذة' ),
		'not_found'    => array( 'fr' => 'Page introuvable', 'en' => 'Page not found', 'es' => 'Página no encontrada', 'ar' => 'الصفحة غير موجودة' ),
		'not_found_t'  => array( 'fr' => 'Cette mélodie s\'est perdue. Revenez à l\'accueil.', 'en' => 'This melody got lost. Head back home.', 'es' => 'Esta melodía se perdió. Vuelve al inicio.', 'ar' => 'ضاعت هذه النغمة. عُد إلى الرئيسية.' ),
		'prev'         => array( 'fr' => 'Précédent', 'en' => 'Previous', 'es' => 'Anterior', 'ar' => 'السابق' ),
		'next_page'    => array( 'fr' => 'Suivant', 'en' => 'Next', 'es' => 'Siguiente', 'ar' => 'التالي' ),

		// Paroles.
		'lyrics'          => array( 'fr' => 'Paroles', 'en' => 'Lyrics', 'es' => 'Letra', 'ar' => 'الكلمات' ),
		'lyrics_original' => array( 'fr' => 'Originale', 'en' => 'Original', 'es' => 'Original', 'ar' => 'الأصلية' ),
		'lyrics_none'     => array( 'fr' => 'Les paroles arrivent bientôt.', 'en' => 'Lyrics coming soon.', 'es' => 'La letra llega pronto.', 'ar' => 'الكلمات قريباً.' ),
		'has_lyrics'      => array( 'fr' => 'Avec paroles', 'en' => 'With lyrics', 'es' => 'Con letra', 'ar' => 'مع الكلمات' ),

		// Hit-parade.
		'hit_title'    => array( 'fr' => 'Hit-Parade', 'en' => 'The Charts', 'es' => 'Lo más escuchado', 'ar' => 'قائمة الأكثر استماعاً' ),
		'hit_sub'      => array( 'fr' => 'Les chansons et les clips les plus écoutés sur Musicology.', 'en' => 'The most played songs and videos on Musicology.', 'es' => 'Las canciones y videoclips más escuchados en Musicology.', 'ar' => 'الأغاني والكليبات الأكثر استماعاً ومشاهدة على Musicology.' ),
		'tab_songs'    => array( 'fr' => 'Top chansons', 'en' => 'Top songs', 'es' => 'Top canciones', 'ar' => 'أفضل الأغاني' ),
		'tab_clips'    => array( 'fr' => 'Top clips', 'en' => 'Top videos', 'es' => 'Top videoclips', 'ar' => 'أفضل الكليبات' ),
		'period_all'   => array( 'fr' => 'Depuis toujours', 'en' => 'All time', 'es' => 'Histórico', 'ar' => 'منذ البداية' ),
		'period_month' => array( 'fr' => 'Ce mois-ci', 'en' => 'This month', 'es' => 'Este mes', 'ar' => 'هذا الشهر' ),
		'top_now'      => array( 'fr' => 'Top du moment', 'en' => 'Trending now', 'es' => 'Tendencia', 'ar' => 'الأكثر رواجاً الآن' ),
		'hit_empty'    => array( 'fr' => 'Le classement se remplit à chaque écoute. Revenez vite !', 'en' => 'The chart fills up with every play. Come back soon!', 'es' => 'La lista se llena con cada escucha. ¡Vuelve pronto!', 'ar' => 'يمتلئ الترتيب مع كل استماع. عُد قريباً!' ),

		// Accueil — script pub « La chanson de tes rêves ».
		'hero_kicker'  => array( 'fr' => 'Studio de chansons & clips sur mesure', 'en' => 'Custom songs & music video studio', 'es' => 'Estudio de canciones y videoclips a medida', 'ar' => 'استوديو أغانٍ وكليبات حسب الطلب' ),
		'hero_q'       => array( 'fr' => 'Et si le plus beau cadeau… <em>c\'était une chanson ?</em>', 'en' => 'What if the most beautiful gift… <em>was a song?</em>', 'es' => '¿Y si el regalo más bonito… <em>fuera una canción?</em>', 'ar' => 'ماذا لو كانت أجمل هدية… <em>أغنية؟</em>' ),
		'hero_sub'     => array( 'fr' => 'Un cadeau qu\'on n\'oublie jamais.', 'en' => 'A gift no one ever forgets.', 'es' => 'Un regalo que nunca se olvida.', 'ar' => 'هدية لا تُنسى أبداً.' ),
		'cta_create'   => array( 'fr' => 'Créer ma chanson', 'en' => 'Create my song', 'es' => 'Crear mi canción', 'ar' => 'أنشئ أغنيتي' ),
		'cta_listen'   => array( 'fr' => 'Écouter nos créations', 'en' => 'Listen to our work', 'es' => 'Escuchar nuestras creaciones', 'ar' => 'استمع إلى أعمالنا' ),
		'occ_title'    => array( 'fr' => 'Chaque moment mérite <em>sa propre musique</em>', 'en' => 'Every moment deserves <em>its own music</em>', 'es' => 'Cada momento merece <em>su propia música</em>', 'ar' => 'كل لحظة تستحق <em>موسيقاها الخاصة</em>' ),
		'occ_text'     => array( 'fr' => 'Anniversaire, mariage, demande en mariage, fête des mères, déclaration d\'amour…', 'en' => 'Birthday, wedding, proposal, Mother\'s Day, a declaration of love…', 'es' => 'Cumpleaños, boda, pedida de mano, Día de la Madre, una declaración de amor…', 'ar' => 'عيد ميلاد، زفاف، طلب زواج، عيد الأم، إعلان حب…' ),
		'promo_title'  => array( 'fr' => 'La chanson <em>de tes rêves</em>', 'en' => 'The song <em>of your dreams</em>', 'es' => 'La canción <em>de tus sueños</em>', 'ar' => 'أغنية <em>أحلامك</em>' ),
		'promo_text'   => array( 'fr' => 'Regarde comment une histoire devient une chanson — et parfois un clip.', 'en' => 'See how a story becomes a song — and sometimes a music video.', 'es' => 'Mira cómo una historia se convierte en canción — y a veces en videoclip.', 'ar' => 'شاهد كيف تتحول قصة إلى أغنية — وأحياناً إلى كليب.' ),
		'how_title'    => array( 'fr' => 'C\'est <em>simple</em>', 'en' => 'It\'s <em>simple</em>', 'es' => 'Es <em>sencillo</em>', 'ar' => 'الأمر <em>بسيط</em>' ),
		'how_text'     => array( 'fr' => 'Envoie-nous tes paroles, ou raconte-nous ton histoire. Dis-nous les prénoms, la langue que tu veux, le style de musique que tu aimes…', 'en' => 'Send us your lyrics, or tell us your story. Give us the names, the language you want, the music style you love…', 'es' => 'Envíanos tu letra, o cuéntanos tu historia. Dinos los nombres, el idioma que quieres, el estilo musical que te gusta…', 'ar' => 'أرسل لنا كلماتك، أو احكِ لنا قصتك. أخبرنا بالأسماء، واللغة التي تريدها، ونوع الموسيقى الذي تحبه…' ),
		'how_1'        => array( 'fr' => 'Tes paroles ou ton histoire', 'en' => 'Your lyrics or your story', 'es' => 'Tu letra o tu historia', 'ar' => 'كلماتك أو قصتك' ),
		'how_2'        => array( 'fr' => 'La langue de ton choix', 'en' => 'The language of your choice', 'es' => 'El idioma que elijas', 'ar' => 'اللغة التي تختارها' ),
		'how_3'        => array( 'fr' => 'Le genre musical : rap, pop, raï, chaabi, R&B, oriental…', 'en' => 'The genre: rap, pop, raï, chaabi, R&B, oriental…', 'es' => 'El género: rap, pop, raï, chaabi, R&B, oriental…', 'ar' => 'النوع الموسيقي: راب، بوب، راي، شعبي، آر أند بي، شرقي…' ),
		'how_4'        => array( 'fr' => 'Les prénoms à célébrer', 'en' => 'The names to celebrate', 'es' => 'Los nombres a celebrar', 'ar' => 'الأسماء التي نحتفي بها' ),
		'how_promise'  => array( 'fr' => '…et on te crée la chanson de tes rêves. Et même le clip, tel que tu l\'imagines. Et encore plus !', 'en' => '…and we create the song of your dreams. Even the music video, just as you imagine it. And even more!', 'es' => '…y te creamos la canción de tus sueños. Y hasta el videoclip, tal como lo imaginas. ¡Y mucho más!', 'ar' => '…ونصنع لك أغنية أحلامك. بل وحتى الكليب، كما تتخيله تماماً. وأكثر من ذلك!' ),
		'drop_1'       => array( 'fr' => 'Ta chanson.', 'en' => 'Your song.', 'es' => 'Tu canción.', 'ar' => 'أغنيتك.' ),
		'drop_2'       => array( 'fr' => 'Ton clip.', 'en' => 'Your video.', 'es' => 'Tu videoclip.', 'ar' => 'كليبك.' ),
		'drop_3'       => array( 'fr' => 'Ton histoire.', 'en' => 'Your story.', 'es' => 'Tu historia.', 'ar' => 'قصتك.' ),
		'cta_title'    => array( 'fr' => 'Ne laisse pas passer le moment. <em>Contacte-nous vite !</em>', 'en' => 'Don\'t let the moment pass. <em>Contact us now!</em>', 'es' => 'No dejes pasar el momento. <em>¡Contáctanos ya!</em>', 'ar' => 'لا تدع اللحظة تفوتك. <em>تواصل معنا بسرعة!</em>' ),
		'cta_final'    => array( 'fr' => 'Ton histoire mérite d\'être chantée.', 'en' => 'Your story deserves to be sung.', 'es' => 'Tu historia merece ser cantada.', 'ar' => 'قصتك تستحق أن تُغنّى.' ),
		'latest_songs' => array( 'fr' => 'Dernières chansons', 'en' => 'Latest songs', 'es' => 'Últimas canciones', 'ar' => 'أحدث الأغاني' ),
		'latest_clips' => array( 'fr' => 'Derniers clips', 'en' => 'Latest videos', 'es' => 'Últimos videoclips', 'ar' => 'أحدث الكليبات' ),
		'email'        => array( 'fr' => 'Email', 'en' => 'Email', 'es' => 'Email', 'ar' => 'البريد' ),
		'whatsapp'     => array( 'fr' => 'Téléphone / WhatsApp', 'en' => 'Phone / WhatsApp', 'es' => 'Teléfono / WhatsApp', 'ar' => 'الهاتف / واتساب' ),
		'location'     => array( 'fr' => 'Adresse', 'en' => 'Location', 'es' => 'Dirección', 'ar' => 'العنوان' ),

		// Formulaire — commun.
		'step_of'      => array( 'fr' => 'Étape %1$s sur %2$s', 'en' => 'Step %1$s of %2$s', 'es' => 'Paso %1$s de %2$s', 'ar' => 'الخطوة %1$s من %2$s' ),
		'last_step'    => array( 'fr' => 'Dernière étape', 'en' => 'Last step', 'es' => 'Último paso', 'ar' => 'الخطوة الأخيرة' ),
		'complete'     => array( 'fr' => '%s %% complété', 'en' => '%s%% complete', 'es' => '%s %% completado', 'ar' => 'مكتمل %s٪' ),
		'private'      => array( 'fr' => 'Vos réponses restent privées', 'en' => 'Your answers stay private', 'es' => 'Tus respuestas son privadas', 'ar' => 'إجاباتك تبقى خاصة' ),
		'back'         => array( 'fr' => 'Retour', 'en' => 'Back', 'es' => 'Atrás', 'ar' => 'رجوع' ),
		'next'         => array( 'fr' => 'Suivant', 'en' => 'Next', 'es' => 'Siguiente', 'ar' => 'التالي' ),
		'finish'       => array( 'fr' => 'Finaliser', 'en' => 'Review', 'es' => 'Finalizar', 'ar' => 'إنهاء' ),
		'submit'       => array( 'fr' => 'Envoyer ma demande', 'en' => 'Send my request', 'es' => 'Enviar mi pedido', 'ar' => 'أرسل طلبي' ),
		'need_help'    => array( 'fr' => 'Besoin d\'aide ?', 'en' => 'Need help?', 'es' => '¿Necesitas ayuda?', 'ar' => 'تحتاج مساعدة؟' ),
		'optional'     => array( 'fr' => 'Facultatif', 'en' => 'Optional', 'es' => 'Opcional', 'ar' => 'اختياري' ),
		'inspiration'  => array( 'fr' => 'Besoin d\'inspiration ?', 'en' => 'Need inspiration?', 'es' => '¿Necesitas inspiración?', 'ar' => 'تحتاج إلهاماً؟' ),
		'this_person'  => array( 'fr' => 'cette personne', 'en' => 'this person', 'es' => 'esta persona', 'ar' => 'هذا الشخص' ),
		'req_kicker'   => array( 'fr' => 'Demande de musique spéciale', 'en' => 'Special music request', 'es' => 'Pedido de música especial', 'ar' => 'طلب موسيقى خاصة' ),
		'req_intro'    => array( 'fr' => 'Réponds à quelques questions : on compose une chanson unique, dans la langue et le style de ton choix.', 'en' => 'Answer a few questions: we compose a unique song, in the language and style you choose.', 'es' => 'Responde unas preguntas: componemos una canción única, en el idioma y estilo que elijas.', 'ar' => 'أجب عن بعض الأسئلة: نلحن لك أغنية فريدة، باللغة والأسلوب الذي تختاره.' ),

		// Étape 1.
		's1_title'     => array( 'fr' => 'Commençons par les <em>bases</em>', 'en' => 'Let\'s start with the <em>basics</em>', 'es' => 'Empecemos por lo <em>básico</em>', 'ar' => 'لنبدأ <em>بالأساسيات</em>' ),
		's1_sub'       => array( 'fr' => 'Parlez-nous de la personne spéciale dans votre vie.', 'en' => 'Tell us about the special person in your life.', 'es' => 'Háblanos de la persona especial en tu vida.', 'ar' => 'حدثنا عن الشخص المميز في حياتك.' ),
		'q_for'        => array( 'fr' => 'À qui est destinée la chanson ?', 'en' => 'Who is the song for?', 'es' => '¿Para quién es la canción?', 'ar' => 'لمن هذه الأغنية؟' ),
		'q_occasion'   => array( 'fr' => 'Pour quelle occasion ?', 'en' => 'For which occasion?', 'es' => '¿Para qué ocasión?', 'ar' => 'لأي مناسبة؟' ),

		// Étape 2.
		's2_title'     => array( 'fr' => 'Comment s\'appelle <em>cette personne</em> ?', 'en' => 'What is <em>their name</em>?', 'es' => '¿Cómo se llama <em>esta persona</em>?', 'ar' => 'ما اسم <em>هذا الشخص</em>؟' ),
		's2_sub'       => array( 'fr' => 'Pour personnaliser la chanson. Vous pouvez indiquer plusieurs prénoms.', 'en' => 'To personalise the song. You can enter several names.', 'es' => 'Para personalizar la canción. Puedes indicar varios nombres.', 'ar' => 'لتخصيص الأغنية. يمكنك كتابة عدة أسماء.' ),
		'f_first'      => array( 'fr' => 'Prénom(s)', 'en' => 'First name(s)', 'es' => 'Nombre(s)', 'ar' => 'الاسم / الأسماء' ),
		'f_first_ph'   => array( 'fr' => 'Ex. : Lina', 'en' => 'e.g. Lina', 'es' => 'Ej.: Lina', 'ar' => 'مثال: لينا' ),
		'f_pron'       => array( 'fr' => 'Ajouter la prononciation', 'en' => 'Add pronunciation', 'es' => 'Añadir la pronunciación', 'ar' => 'أضف طريقة النطق' ),
		'f_pron_ph'    => array( 'fr' => 'Ex. : Li-na — ou le surnom affectueux', 'en' => 'e.g. Lee-na — or a nickname', 'es' => 'Ej.: Li-na — o el apodo cariñoso', 'ar' => 'مثال: لِينَا — أو اسم الدلع' ),
		'f_date'       => array( 'fr' => 'Date de l\'occasion', 'en' => 'Date of the occasion', 'es' => 'Fecha de la ocasión', 'ar' => 'تاريخ المناسبة' ),

		// Étape 3.
		's3_title'     => array( 'fr' => 'Quel style pour <em data-name>%s</em> ?', 'en' => 'Which style for <em data-name>%s</em>?', 'es' => '¿Qué estilo para <em data-name>%s</em>?', 'ar' => 'أي أسلوب لـ<em data-name>%s</em>؟' ),
		's3_sub'       => array( 'fr' => 'Choisissez la langue et le style qui correspondent à votre histoire.', 'en' => 'Choose the language and the style that fit your story.', 'es' => 'Elige el idioma y el estilo que encajan con tu historia.', 'ar' => 'اختر اللغة والأسلوب المناسبين لقصتك.' ),
		'q_song_lang'  => array( 'fr' => 'Langue de la chanson', 'en' => 'Song language', 'es' => 'Idioma de la canción', 'ar' => 'لغة الأغنية' ),
		'q_genre'      => array( 'fr' => 'Genre musical', 'en' => 'Music genre', 'es' => 'Género musical', 'ar' => 'النوع الموسيقي' ),
		'q_voice'      => array( 'fr' => 'Voix préférée', 'en' => 'Preferred voice', 'es' => 'Voz preferida', 'ar' => 'الصوت المفضل' ),
		'voice_hint'   => array( 'fr' => 'Beaucoup choisissent une voix qui leur ressemble — la chanson n\'en sera que plus personnelle.', 'en' => 'Many choose a voice that sounds like them — it makes the song even more personal.', 'es' => 'Muchos eligen una voz que se les parezca — la canción será aún más personal.', 'ar' => 'يختار الكثيرون صوتاً يشبههم — فتصبح الأغنية أكثر خصوصية.' ),

		// Étape 4.
		's4_title'     => array( 'fr' => 'Pourquoi <em data-name>%s</em> est si spécial(e) ?', 'en' => 'Why is <em data-name>%s</em> so special?', 'es' => '¿Por qué <em data-name>%s</em> es tan especial?', 'ar' => 'لماذا <em data-name>%s</em> مميز(ة) إلى هذا الحد؟' ),
		's4_sub'       => array( 'fr' => 'Choisissez les qualités qui le/la décrivent le mieux.', 'en' => 'Pick the qualities that describe them best.', 'es' => 'Elige las cualidades que mejor lo/la describen.', 'ar' => 'اختر الصفات التي تصفه/تصفها أفضل وصف.' ),
		'f_own_words'  => array( 'fr' => 'Ajouter mes propres mots', 'en' => 'Add my own words', 'es' => 'Añadir mis propias palabras', 'ar' => 'أضف كلماتي الخاصة' ),
		'f_own_ph'     => array( 'fr' => 'Ex. : sa joie de vivre, son rire…', 'en' => 'e.g. their zest for life, their laugh…', 'es' => 'Ej.: su alegría, su risa…', 'ar' => 'مثال: حبه للحياة، ضحكته…' ),

		// Étape 5.
		's5_title'     => array( 'fr' => 'Partagez vos souvenirs avec <em data-name>%s</em>', 'en' => 'Share your memories with <em data-name>%s</em>', 'es' => 'Comparte tus recuerdos con <em data-name>%s</em>', 'ar' => 'شاركنا ذكرياتك مع <em data-name>%s</em>' ),
		's5_sub'       => array( 'fr' => 'Envoyez vos paroles, ou décrivez les moments qui vous tiennent le plus à cœur.', 'en' => 'Send your lyrics, or describe the moments that matter most to you.', 'es' => 'Envía tu letra, o describe los momentos que más te importan.', 'ar' => 'أرسل كلماتك، أو صِف اللحظات الأعز على قلبك.' ),
		'f_story'      => array( 'fr' => 'Vos moments précieux', 'en' => 'Your precious moments', 'es' => 'Tus momentos preciosos', 'ar' => 'لحظاتكم الثمينة' ),
		'f_story_ph'   => array( 'fr' => 'Votre rencontre, vos plus belles années, vos petites complicités, ou les moments qui rendent votre histoire unique…', 'en' => 'How you met, your best years, your little inside jokes, the moments that make your story unique…', 'es' => 'Cómo os conocisteis, vuestros mejores años, vuestras complicidades, los momentos que hacen única vuestra historia…', 'ar' => 'كيف التقيتما، أجمل سنواتكما، تفاصيلكما الصغيرة، واللحظات التي تجعل قصتكما فريدة…' ),
		'f_lyrics'     => array( 'fr' => 'Vos paroles', 'en' => 'Your lyrics', 'es' => 'Tu letra', 'ar' => 'كلماتك' ),
		'f_lyrics_ph'  => array( 'fr' => 'Collez ou écrivez vos paroles ici. Nous pouvons les adapter à la mélodie.', 'en' => 'Paste or write your lyrics here. We can adapt them to the melody.', 'es' => 'Pega o escribe tu letra aquí. Podemos adaptarla a la melodía.', 'ar' => 'الصق أو اكتب كلماتك هنا. يمكننا تكييفها مع اللحن.' ),
		'insp_1'       => array( 'fr' => 'Un moment que je n\'oublierai jamais — ', 'en' => 'A moment I\'ll never forget — ', 'es' => 'Un momento que nunca olvidaré — ', 'ar' => 'لحظة لن أنساها أبداً — ' ),
		'insp_2'       => array( 'fr' => 'Notre premier rendez-vous — ', 'en' => 'Our first date — ', 'es' => 'Nuestra primera cita — ', 'ar' => 'لقاؤنا الأول — ' ),
		'insp_3'       => array( 'fr' => 'Ce qui me fait sourire chez toi — ', 'en' => 'What makes me smile about you — ', 'es' => 'Lo que me hace sonreír de ti — ', 'ar' => 'ما يجعلني أبتسم فيك — ' ),

		// Étape 6.
		's6_title'     => array( 'fr' => 'Un message venu du <em>cœur</em>', 'en' => 'A message from the <em>heart</em>', 'es' => 'Un mensaje desde el <em>corazón</em>', 'ar' => 'رسالة من <em>القلب</em>' ),
		's6_sub'       => array( 'fr' => 'Ajoutez tout ce qui vous semble important pour votre chanson, et nous ferons de notre mieux pour l\'inclure.', 'en' => 'Add anything that feels important for your song, and we\'ll do our best to include it.', 'es' => 'Añade todo lo que te parezca importante para tu canción y haremos lo posible por incluirlo.', 'ar' => 'أضف كل ما تراه مهماً لأغنيتك، وسنبذل قصارى جهدنا لتضمينه.' ),
		'f_msg'        => array( 'fr' => 'Quelques mots du cœur', 'en' => 'A few words from the heart', 'es' => 'Unas palabras del corazón', 'ar' => 'كلمات من القلب' ),
		'f_msg_ph'     => array( 'fr' => 'Une dédicace, une déclaration, quelque chose que vous aimeriez retrouver dans la chanson…', 'en' => 'A dedication, a declaration, something you\'d love to hear in the song…', 'es' => 'Una dedicatoria, una declaración, algo que te gustaría escuchar en la canción…', 'ar' => 'إهداء، اعتراف، شيء تحب أن تسمعه في الأغنية…' ),
		'insp_m1'      => array( 'fr' => 'Je t\'aime de tout mon cœur… ', 'en' => 'I love you with all my heart… ', 'es' => 'Te quiero con todo mi corazón… ', 'ar' => 'أحبك من كل قلبي… ' ),
		'insp_m2'      => array( 'fr' => 'Tu es tout pour moi… ', 'en' => 'You are everything to me… ', 'es' => 'Eres todo para mí… ', 'ar' => 'أنت كل شيء بالنسبة لي… ' ),
		'q_clip'       => array( 'fr' => 'Voulez-vous aussi le clip ?', 'en' => 'Do you also want a music video?', 'es' => '¿Quieres también el videoclip?', 'ar' => 'هل تريد الكليب أيضاً؟' ),
		'f_photo'      => array( 'fr' => 'Une photo (pour la pochette ou le clip)', 'en' => 'A photo (for the cover or the video)', 'es' => 'Una foto (para la portada o el videoclip)', 'ar' => 'صورة (للغلاف أو الكليب)' ),
		'f_photo_hint' => array( 'fr' => 'JPG, PNG ou WEBP — 5 Mo maximum.', 'en' => 'JPG, PNG or WEBP — 5 MB max.', 'es' => 'JPG, PNG o WEBP — 5 MB máximo.', 'ar' => 'JPG أو PNG أو WEBP — بحد أقصى 5 ميغابايت.' ),
		'f_photo_btn'  => array( 'fr' => 'Choisir une photo', 'en' => 'Choose a photo', 'es' => 'Elegir una foto', 'ar' => 'اختر صورة' ),

		// Étape finale.
		'f_title'      => array( 'fr' => 'Vérifiez les détails de <em>votre chanson</em>', 'en' => 'Check the details of <em>your song</em>', 'es' => 'Revisa los detalles de <em>tu canción</em>', 'ar' => 'راجع تفاصيل <em>أغنيتك</em>' ),
		'f_sub'        => array( 'fr' => 'Vérifiez que tout est correct avant que nous composions votre chanson pour <strong data-name>%s</strong>.', 'en' => 'Make sure everything is right before we compose your song for <strong data-name>%s</strong>.', 'es' => 'Comprueba que todo está bien antes de que compongamos tu canción para <strong data-name>%s</strong>.', 'ar' => 'تأكد أن كل شيء صحيح قبل أن نلحن أغنيتك لـ<strong data-name>%s</strong>.' ),
		'contact_t'    => array( 'fr' => 'Vos coordonnées', 'en' => 'Your contact details', 'es' => 'Tus datos de contacto', 'ar' => 'معلومات الاتصال بك' ),
		'contact_s'    => array( 'fr' => 'Pour vous envoyer votre chanson et le suivi de votre demande.', 'en' => 'To send you your song and follow up on your request.', 'es' => 'Para enviarte tu canción y el seguimiento de tu pedido.', 'ar' => 'لنرسل لك أغنيتك ونتابع طلبك.' ),
		'f_name'       => array( 'fr' => 'Votre nom complet', 'en' => 'Your full name', 'es' => 'Tu nombre completo', 'ar' => 'اسمك الكامل' ),
		'f_email'      => array( 'fr' => 'Votre adresse email', 'en' => 'Your email address', 'es' => 'Tu correo electrónico', 'ar' => 'بريدك الإلكتروني' ),
		'f_phone'      => array( 'fr' => 'Téléphone / WhatsApp', 'en' => 'Phone / WhatsApp', 'es' => 'Teléfono / WhatsApp', 'ar' => 'الهاتف / واتساب' ),
		'f_city'       => array( 'fr' => 'Ville / Pays', 'en' => 'City / Country', 'es' => 'Ciudad / País', 'ar' => 'المدينة / البلد' ),
		'f_consent'    => array( 'fr' => 'J\'accepte d\'être recontacté(e) au sujet de ma demande.', 'en' => 'I agree to be contacted about my request.', 'es' => 'Acepto que me contacten sobre mi pedido.', 'ar' => 'أوافق على التواصل معي بخصوص طلبي.' ),
		'recap'        => array( 'fr' => 'Votre demande', 'en' => 'Your request', 'es' => 'Tu pedido', 'ar' => 'طلبك' ),
		'edit'         => array( 'fr' => 'Modifier', 'en' => 'Edit', 'es' => 'Editar', 'ar' => 'تعديل' ),
		'r_for'        => array( 'fr' => 'Pour', 'en' => 'For', 'es' => 'Para', 'ar' => 'إلى' ),
		'r_relation'   => array( 'fr' => 'Destinataire', 'en' => 'Recipient', 'es' => 'Destinatario', 'ar' => 'المُهدى إليه' ),
		'r_occasion'   => array( 'fr' => 'Occasion', 'en' => 'Occasion', 'es' => 'Ocasión', 'ar' => 'المناسبة' ),
		'r_song_lang'  => array( 'fr' => 'Langue', 'en' => 'Language', 'es' => 'Idioma', 'ar' => 'اللغة' ),
		'r_genre'      => array( 'fr' => 'Style musical', 'en' => 'Music style', 'es' => 'Estilo musical', 'ar' => 'الأسلوب الموسيقي' ),
		'r_voice'      => array( 'fr' => 'Voix', 'en' => 'Voice', 'es' => 'Voz', 'ar' => 'الصوت' ),
		'r_qualities'  => array( 'fr' => 'Ses qualités', 'en' => 'Qualities', 'es' => 'Cualidades', 'ar' => 'صفاته' ),
		'r_story'      => array( 'fr' => 'Vos souvenirs / paroles', 'en' => 'Memories / lyrics', 'es' => 'Recuerdos / letra', 'ar' => 'الذكريات / الكلمات' ),
		'r_msg'        => array( 'fr' => 'Votre message personnel', 'en' => 'Your personal message', 'es' => 'Tu mensaje personal', 'ar' => 'رسالتك الشخصية' ),
		'r_clip'       => array( 'fr' => 'Clip', 'en' => 'Video', 'es' => 'Videoclip', 'ar' => 'الكليب' ),
		'r_date'       => array( 'fr' => 'Date', 'en' => 'Date', 'es' => 'Fecha', 'ar' => 'التاريخ' ),
		'err_required' => array( 'fr' => 'Ce champ est obligatoire.', 'en' => 'This field is required.', 'es' => 'Este campo es obligatorio.', 'ar' => 'هذا الحقل إلزامي.' ),
		'err_choose'   => array( 'fr' => 'Faites un choix pour continuer.', 'en' => 'Please make a choice to continue.', 'es' => 'Elige una opción para continuar.', 'ar' => 'اختر خياراً للمتابعة.' ),
		'err_email'    => array( 'fr' => 'Adresse email invalide.', 'en' => 'Invalid email address.', 'es' => 'Correo electrónico no válido.', 'ar' => 'بريد إلكتروني غير صالح.' ),
		'err_contact'  => array( 'fr' => 'Indiquez au moins un email ou un numéro WhatsApp.', 'en' => 'Please give at least an email or a WhatsApp number.', 'es' => 'Indica al menos un email o un número de WhatsApp.', 'ar' => 'أدخل على الأقل بريداً إلكترونياً أو رقم واتساب.' ),
		'err_story'    => array( 'fr' => 'Racontez-nous au moins quelques mots, ou collez vos paroles.', 'en' => 'Tell us at least a few words, or paste your lyrics.', 'es' => 'Cuéntanos al menos unas palabras, o pega tu letra.', 'ar' => 'اكتب لنا بضع كلمات على الأقل، أو الصق كلماتك.' ),
		'err_photo'    => array( 'fr' => 'Photo refusée (format ou taille).', 'en' => 'Photo rejected (format or size).', 'es' => 'Foto rechazada (formato o tamaño).', 'ar' => 'تم رفض الصورة (الصيغة أو الحجم).' ),
		'err_server'   => array( 'fr' => 'Une erreur est survenue. Réessayez ou écrivez-nous directement.', 'en' => 'Something went wrong. Try again or write to us directly.', 'es' => 'Algo salió mal. Inténtalo de nuevo o escríbenos directamente.', 'ar' => 'حدث خطأ. حاول مرة أخرى أو راسلنا مباشرة.' ),
		'sending'      => array( 'fr' => 'Envoi en cours…', 'en' => 'Sending…', 'es' => 'Enviando…', 'ar' => 'جارٍ الإرسال…' ),
		'thanks_title' => array( 'fr' => 'Merci, votre demande est <em>bien arrivée</em> !', 'en' => 'Thank you, your request <em>has arrived</em>!', 'es' => '¡Gracias, tu pedido <em>ha llegado</em>!', 'ar' => 'شكراً، وصل <em>طلبك</em> بنجاح!' ),
		'thanks_text'  => array( 'fr' => 'Nous vous recontactons très vite par email ou WhatsApp. Votre référence :', 'en' => 'We\'ll get back to you very soon by email or WhatsApp. Your reference:', 'es' => 'Te contactaremos muy pronto por email o WhatsApp. Tu referencia:', 'ar' => 'سنتواصل معك قريباً جداً عبر البريد أو واتساب. رقم طلبك:' ),
		'thanks_home'  => array( 'fr' => 'Retour à l\'accueil', 'en' => 'Back to home', 'es' => 'Volver al inicio', 'ar' => 'العودة إلى الرئيسية' ),
		'what_title'   => array( 'fr' => 'Ce que vous recevrez', 'en' => 'What you\'ll receive', 'es' => 'Lo que recibirás', 'ar' => 'ما ستحصل عليه' ),
		'w1_t'         => array( 'fr' => 'Qualité studio', 'en' => 'Studio quality', 'es' => 'Calidad de estudio', 'ar' => 'جودة استوديو' ),
		'w1_s'         => array( 'fr' => 'Mixage soigné, prête à diffuser', 'en' => 'Carefully mixed, ready to share', 'es' => 'Mezcla cuidada, lista para compartir', 'ar' => 'مكساج متقن، جاهزة للنشر' ),
		'w2_t'         => array( 'fr' => 'Paroles personnalisées', 'en' => 'Personalised lyrics', 'es' => 'Letra personalizada', 'ar' => 'كلمات مخصصة' ),
		'w2_s'         => array( 'fr' => 'Écrites à partir de vos réponses', 'en' => 'Written from your answers', 'es' => 'Escrita a partir de tus respuestas', 'ar' => 'مكتوبة انطلاقاً من إجاباتك' ),
		'w3_t'         => array( 'fr' => 'Le clip, si vous le voulez', 'en' => 'The video, if you want it', 'es' => 'El videoclip, si lo quieres', 'ar' => 'الكليب، إن أردت' ),
		'w3_s'         => array( 'fr' => 'Tel que vous l\'imaginez', 'en' => 'Just as you imagine it', 'es' => 'Tal como lo imaginas', 'ar' => 'كما تتخيله تماماً' ),
		'w4_t'         => array( 'fr' => 'Votre langue, votre style', 'en' => 'Your language, your style', 'es' => 'Tu idioma, tu estilo', 'ar' => 'لغتك، أسلوبك' ),
		'w4_s'         => array( 'fr' => 'Français, arabe, darija, anglais, espagnol…', 'en' => 'French, Arabic, Darija, English, Spanish…', 'es' => 'Francés, árabe, darija, inglés, español…', 'ar' => 'الفرنسية، العربية، الدارجة، الإنجليزية، الإسبانية…' ),
		'listen_more'  => array( 'fr' => 'Écoutez d\'autres chansons que nous avons créées', 'en' => 'Listen to other songs we\'ve created', 'es' => 'Escucha otras canciones que hemos creado', 'ar' => 'استمع إلى أغانٍ أخرى من إبداعنا' ),

		// Footer.
		'f_tagline'    => array( 'fr' => 'Studio de chansons et de clips personnalisés. Chaque histoire mérite sa mélodie.', 'en' => 'A studio for personalised songs and music videos. Every story deserves its melody.', 'es' => 'Estudio de canciones y videoclips personalizados. Cada historia merece su melodía.', 'ar' => 'استوديو للأغاني والكليبات المخصصة. كل قصة تستحق لحنها.' ),
		'f_explore'    => array( 'fr' => 'Explorer', 'en' => 'Explore', 'es' => 'Explorar', 'ar' => 'استكشف' ),
		'f_contact'    => array( 'fr' => 'Contact', 'en' => 'Contact', 'es' => 'Contacto', 'ar' => 'تواصل' ),
		'f_rights'     => array( 'fr' => 'Tous droits réservés.', 'en' => 'All rights reserved.', 'es' => 'Todos los derechos reservados.', 'ar' => 'جميع الحقوق محفوظة.' ),

		// Emails.
		'mail_client_subject' => array( 'fr' => 'Musicology — nous avons bien reçu votre demande (%s)', 'en' => 'Musicology — we received your request (%s)', 'es' => 'Musicology — hemos recibido tu pedido (%s)', 'ar' => 'Musicology — استلمنا طلبك (%s)' ),
		'mail_client_hello'   => array( 'fr' => 'Bonjour %s,', 'en' => 'Hello %s,', 'es' => 'Hola %s:', 'ar' => 'مرحباً %s،' ),
		'mail_client_body'    => array( 'fr' => 'Merci ! Nous avons bien reçu votre demande de chanson pour %1$s (référence %2$s). Notre équipe vous recontacte très vite pour la suite.', 'en' => 'Thank you! We received your song request for %1$s (reference %2$s). Our team will get back to you very soon.', 'es' => '¡Gracias! Hemos recibido tu pedido de canción para %1$s (referencia %2$s). Nuestro equipo te contactará muy pronto.', 'ar' => 'شكراً! استلمنا طلب أغنيتك لـ%1$s (رقم الطلب %2$s). سيتواصل معك فريقنا قريباً جداً.' ),

		// Services.
		'nav_services'   => array( 'fr' => 'Services', 'en' => 'Services', 'es' => 'Servicios', 'ar' => 'الخدمات' ),
		'nav_blog'       => array( 'fr' => 'Blog', 'en' => 'Blog', 'es' => 'Blog', 'ar' => 'المدونة' ),
		'services_title' => array( 'fr' => 'Nos <em>services</em>', 'en' => 'Our <em>services</em>', 'es' => 'Nuestros <em>servicios</em>', 'ar' => '<em>خدماتنا</em>' ),
		'services_sub'   => array( 'fr' => 'Tout ce qu\'il faut pour ta musique, de l\'idée à la mise en ligne.', 'en' => 'Everything your music needs, from the idea to the release.', 'es' => 'Todo lo que tu música necesita, de la idea a la publicación.', 'ar' => 'كل ما تحتاجه موسيقاك، من الفكرة إلى النشر.' ),
		'services_all'   => array( 'fr' => 'Tous les services', 'en' => 'All services', 'es' => 'Todos los servicios', 'ar' => 'كل الخدمات' ),
		'see_service'    => array( 'fr' => 'Voir le service', 'en' => 'View service', 'es' => 'Ver el servicio', 'ar' => 'عرض الخدمة' ),
		'other_services' => array( 'fr' => 'Autres services', 'en' => 'Other services', 'es' => 'Otros servicios', 'ar' => 'خدمات أخرى' ),
		'svc_included'   => array( 'fr' => 'Ce qui est inclus', 'en' => 'What\'s included', 'es' => 'Qué incluye', 'ar' => 'ماذا تتضمن' ),
		'svc_options'    => array( 'fr' => 'Choisis ta formule', 'en' => 'Choose your package', 'es' => 'Elige tu fórmula', 'ar' => 'اختر باقتك' ),
		'svc_req_title'  => array( 'fr' => 'Enregistrement à distance : envoie 3 fichiers séparés', 'en' => 'Remote recording: send 3 separate files', 'es' => 'Grabación a distancia: envía 3 archivos separados', 'ar' => 'التسجيل عن بُعد: أرسل 3 ملفات منفصلة' ),
		'svc_order'      => array( 'fr' => 'Commander ce service', 'en' => 'Order this service', 'es' => 'Pedir este servicio', 'ar' => 'اطلب هذه الخدمة' ),
		'svc_order_sub'  => array( 'fr' => 'Remplis ce formulaire : on te recontacte vite sur WhatsApp ou par email.', 'en' => 'Fill in this form: we\'ll get back to you quickly on WhatsApp or by email.', 'es' => 'Rellena este formulario: te contactamos rápido por WhatsApp o email.', 'ar' => 'املأ هذه الاستمارة: سنتواصل معك بسرعة عبر واتساب أو البريد.' ),
		'svc_files'      => array( 'fr' => 'Liens vers tes fichiers', 'en' => 'Links to your files', 'es' => 'Enlaces a tus archivos', 'ar' => 'روابط ملفاتك' ),
		'svc_files_ph'   => array( 'fr' => 'Google Drive, WeTransfer, Dropbox…', 'en' => 'Google Drive, WeTransfer, Dropbox…', 'es' => 'Google Drive, WeTransfer, Dropbox…', 'ar' => 'Google Drive، WeTransfer، Dropbox…' ),
		'svc_details'    => array( 'fr' => 'Parle-nous de ton projet', 'en' => 'Tell us about your project', 'es' => 'Háblanos de tu proyecto', 'ar' => 'حدثنا عن مشروعك' ),
		'svc_details_ph' => array( 'fr' => 'Titre, style, délais, nom d\'artiste, liens utiles…', 'en' => 'Title, style, deadline, artist name, useful links…', 'es' => 'Título, estilo, plazos, nombre artístico, enlaces útiles…', 'ar' => 'العنوان، الأسلوب، الآجال، اسمك الفني، روابط مفيدة…' ),
		'svc_send'       => array( 'fr' => 'Envoyer ma commande', 'en' => 'Send my order', 'es' => 'Enviar mi pedido', 'ar' => 'أرسل طلبي' ),
		'svc_or'         => array( 'fr' => 'ou', 'en' => 'or', 'es' => 'o', 'ar' => 'أو' ),
		'svc_wa'         => array( 'fr' => 'Commander sur WhatsApp', 'en' => 'Order on WhatsApp', 'es' => 'Pedir por WhatsApp', 'ar' => 'اطلب عبر واتساب' ),
		'wa_service_msg' => array( 'fr' => 'Bonjour Musicology, je suis intéressé(e) par : %s', 'en' => 'Hello Musicology, I\'m interested in: %s', 'es' => 'Hola Musicology, me interesa: %s', 'ar' => 'مرحباً Musicology، أنا مهتم بـ: %s' ),
		'svc_thanks'     => array( 'fr' => 'Merci, ta commande est <em>bien arrivée</em> !', 'en' => 'Thank you, your order <em>has arrived</em>!', 'es' => '¡Gracias, tu pedido <em>ha llegado</em>!', 'ar' => 'شكراً، وصل <em>طلبك</em> بنجاح!' ),
		'special_title'  => array( 'fr' => 'Chanson personnalisée en cadeau', 'en' => 'Personalised song as a gift', 'es' => 'Canción personalizada de regalo', 'ar' => 'أغنية مخصصة كهدية' ),
		'special_tag'    => array( 'fr' => 'Anniversaire, mariage, déclaration : ta chanson et même ton clip, sur mesure', 'en' => 'Birthday, wedding, declaration: your song and even your video, made to measure', 'es' => 'Cumpleaños, boda, declaración: tu canción e incluso tu videoclip, a medida', 'ar' => 'عيد ميلاد، زفاف، اعتراف: أغنيتك وحتى الكليب، حسب الطلب' ),
		'quote'          => array( 'fr' => 'Sur devis', 'en' => 'Custom quote', 'es' => 'Presupuesto a medida', 'ar' => 'حسب الطلب' ),
		'process_title'  => array( 'fr' => 'Comment ça <em>se passe</em>', 'en' => 'How it <em>works</em>', 'es' => 'Cómo <em>funciona</em>', 'ar' => 'كيف <em>تجري الأمور</em>' ),
		'p1_t'           => array( 'fr' => 'Discussion', 'en' => 'Discussion', 'es' => 'Conversación', 'ar' => 'النقاش' ),
		'p1_s'           => array( 'fr' => 'On parle de ton projet sur WhatsApp : ton idée, ton style, tes délais.', 'en' => 'We talk about your project on WhatsApp: your idea, your style, your deadline.', 'es' => 'Hablamos de tu proyecto por WhatsApp: tu idea, tu estilo, tus plazos.', 'ar' => 'نتحدث عن مشروعك عبر واتساب: فكرتك، أسلوبك، آجالك.' ),
		'p2_t'           => array( 'fr' => 'Enregistrement', 'en' => 'Recording', 'es' => 'Grabación', 'ar' => 'التسجيل' ),
		'p2_s'           => array( 'fr' => 'Chez toi, au téléphone, guidé pas à pas. Paiement avant le début du travail.', 'en' => 'At home, on your phone, guided step by step. Payment before work starts.', 'es' => 'En casa, con el móvil, guiado paso a paso. Pago antes de empezar.', 'ar' => 'من بيتك، بهاتفك، بتوجيه خطوة بخطوة. الدفع قبل بدء العمل.' ),
		'p3_t'           => array( 'fr' => 'Production', 'en' => 'Production', 'es' => 'Producción', 'ar' => 'الإنتاج' ),
		'p3_s'           => array( 'fr' => 'Arrangement, mixage, mastering et autotune.', 'en' => 'Arrangement, mixing, mastering and autotune.', 'es' => 'Arreglo, mezcla, masterización y autotune.', 'ar' => 'توزيع موسيقي، مكساج، ماسترينغ وأوتوتون.' ),
		'p4_t'           => array( 'fr' => 'Publication', 'en' => 'Release', 'es' => 'Publicación', 'ar' => 'النشر' ),
		'p4_s'           => array( 'fr' => 'Tes titres sur Spotify, Apple Music, Deezer et toutes les plateformes.', 'en' => 'Your tracks on Spotify, Apple Music, Deezer and every platform.', 'es' => 'Tus temas en Spotify, Apple Music, Deezer y todas las plataformas.', 'ar' => 'أغانيك على سبوتيفاي وآبل ميوزك وديزر وكل المنصات.' ),
		'pol_1'          => array( 'fr' => 'Tu restes l\'administrateur de tes comptes', 'en' => 'You stay the admin of your accounts', 'es' => 'Sigues siendo el administrador de tus cuentas', 'ar' => 'تبقى المسؤول عن حساباتك' ),
		'pol_2'          => array( 'fr' => 'Paiement avant le début du travail', 'en' => 'Payment before work starts', 'es' => 'Pago antes de empezar', 'ar' => 'الدفع قبل بدء العمل' ),
		'pol_3'          => array( 'fr' => 'Tarifs transparents, aucun frais caché', 'en' => 'Transparent prices, no hidden fees', 'es' => 'Precios transparentes, sin costes ocultos', 'ar' => 'أسعار واضحة، بدون رسوم خفية' ),
		'pol_4'          => array( 'fr' => 'La distribution ne garantit ni écoutes ni revenus', 'en' => 'Distribution doesn\'t guarantee streams or revenue', 'es' => 'La distribución no garantiza escuchas ni ingresos', 'ar' => 'التوزيع لا يضمن الاستماعات ولا الأرباح' ),
		'platforms'      => array( 'fr' => 'Tes titres sur toutes les plateformes', 'en' => 'Your tracks on every platform', 'es' => 'Tus temas en todas las plataformas', 'ar' => 'أغانيك على كل المنصات' ),
		'faq_title'      => array( 'fr' => 'Questions <em>fréquentes</em>', 'en' => 'Frequently asked <em>questions</em>', 'es' => 'Preguntas <em>frecuentes</em>', 'ar' => 'الأسئلة <em>الشائعة</em>' ),
		'faq1_q'         => array( 'fr' => 'Est-ce que je garde le contrôle de mes comptes artiste ?', 'en' => 'Do I keep control of my artist accounts?', 'es' => '¿Mantengo el control de mis cuentas de artista?', 'ar' => 'هل أحتفظ بالتحكم في حساباتي كفنان؟' ),
		'faq1_a'         => array( 'fr' => 'Oui. Nous créons tes comptes, mais tu en restes l\'administrateur : tes titres t\'appartiennent.', 'en' => 'Yes. We create your accounts, but you remain the admin: your tracks belong to you.', 'es' => 'Sí. Creamos tus cuentas, pero tú sigues siendo el administrador: tus temas te pertenecen.', 'ar' => 'نعم. ننشئ حساباتك، لكنك تبقى المسؤول عنها: أغانيك ملكك.' ),
		'faq2_q'         => array( 'fr' => 'Peut-on tout faire à distance ?', 'en' => 'Can everything be done remotely?', 'es' => '¿Se puede hacer todo a distancia?', 'ar' => 'هل يمكن القيام بكل شيء عن بُعد؟' ),
		'faq2_a'         => array( 'fr' => 'Oui. Tu peux t\'enregistrer au téléphone en suivant nos conseils et tout se règle sur WhatsApp. Pour un cover complet, envoie trois fichiers : la voix avec la musique, la voix seule et l\'instrumental seul.', 'en' => 'Yes. You can record yourself on your phone with our tips and everything is handled on WhatsApp. For a full cover, send three files: vocals with music, vocals only and instrumental only.', 'es' => 'Sí. Puedes grabarte con el móvil siguiendo nuestros consejos y todo se gestiona por WhatsApp. Para un cover completo, envía tres archivos: voz con música, solo voz y solo instrumental.', 'ar' => 'نعم. يمكنك التسجيل بهاتفك باتباع نصائحنا وكل شيء يتم عبر واتساب. للكوفر المتكامل، أرسل ثلاثة ملفات: الصوت مع الموسيقى، الصوت وحده، والموسيقى وحدها.' ),
		'faq3_q'         => array( 'fr' => 'Est-ce légal de publier un cover ?', 'en' => 'Is it legal to release a cover?', 'es' => '¿Es legal publicar un cover?', 'ar' => 'هل نشر الكوفر قانوني؟' ),
		'faq3_a'         => array( 'fr' => 'Oui, avec une licence de cover. Elle est incluse dans le Pack Cover et le Cover complet.', 'en' => 'Yes, with a cover licence. It is included in the Cover Pack and the Full Cover.', 'es' => 'Sí, con una licencia de cover. Está incluida en el Pack Cover y el Cover completo.', 'ar' => 'نعم، برخصة كوفر. وهي مشمولة في باقة الكوفر والكوفر المتكامل.' ),
		'faq4_q'         => array( 'fr' => 'Quand dois-je payer ?', 'en' => 'When do I pay?', 'es' => '¿Cuándo pago?', 'ar' => 'متى أدفع؟' ),
		'faq4_a'         => array( 'fr' => 'Le paiement se fait avant le début du travail. Les tarifs sont affichés, sans frais cachés.', 'en' => 'Payment is made before work starts. Prices are shown upfront, with no hidden fees.', 'es' => 'El pago se hace antes de empezar. Los precios están a la vista, sin costes ocultos.', 'ar' => 'يتم الدفع قبل بدء العمل. الأسعار معروضة بدون رسوم خفية.' ),
		'faq5_q'         => array( 'fr' => 'La distribution garantit-elle des écoutes ?', 'en' => 'Does distribution guarantee streams?', 'es' => '¿La distribución garantiza escuchas?', 'ar' => 'هل يضمن التوزيع الاستماعات؟' ),
		'faq5_a'         => array( 'fr' => 'Non. Nous publions ton titre partout, mais les écoutes et les revenus dépendent de ta promotion et de ton public.', 'en' => 'No. We release your track everywhere, but streams and revenue depend on your promotion and audience.', 'es' => 'No. Publicamos tu tema en todas partes, pero las escuchas y los ingresos dependen de tu promoción y tu público.', 'ar' => 'لا. ننشر أغنيتك في كل مكان، لكن الاستماعات والأرباح تعتمد على ترويجك وجمهورك.' ),
		'faq6_q'         => array( 'fr' => 'Combien coûte le maintien en ligne ?', 'en' => 'How much does it cost to stay online?', 'es' => '¿Cuánto cuesta mantenerse en línea?', 'ar' => 'كم تكلفة إبقاء الأغاني منشورة؟' ),
		'faq6_a'         => array( 'fr' => 'Le renouvellement de la distribution coûte 200 DH par an pour garder tes titres disponibles.', 'en' => 'Distribution renewal costs 200 DH per year to keep your tracks available.', 'es' => 'La renovación de la distribución cuesta 200 DH al año para mantener tus temas disponibles.', 'ar' => 'تجديد التوزيع يكلف 200 درهم سنوياً لإبقاء أغانيك متاحة.' ),
		'mail_order_body' => array( 'fr' => 'Merci ! Nous avons bien reçu ta commande « %1$s » (référence %2$s). Nous te recontactons très vite pour la suite.', 'en' => 'Thank you! We received your order "%1$s" (reference %2$s). We\'ll get back to you very soon.', 'es' => '¡Gracias! Hemos recibido tu pedido «%1$s» (referencia %2$s). Te contactaremos muy pronto.', 'ar' => 'شكراً! استلمنا طلبك «%1$s» (رقم الطلب %2$s). سنتواصل معك قريباً جداً.' ),

		// Blog.
		'blog_title'     => array( 'fr' => 'Le <em>blog</em>', 'en' => 'The <em>blog</em>', 'es' => 'El <em>blog</em>', 'ar' => '<em>المدونة</em>' ),
		'blog_sub'       => array( 'fr' => 'Conseils pour enregistrer, publier et offrir de la musique.', 'en' => 'Tips to record, release and gift music.', 'es' => 'Consejos para grabar, publicar y regalar música.', 'ar' => 'نصائح للتسجيل والنشر وإهداء الموسيقى.' ),
		'latest_posts'   => array( 'fr' => 'Derniers articles', 'en' => 'Latest articles', 'es' => 'Últimos artículos', 'ar' => 'أحدث المقالات' ),
		'read_more'      => array( 'fr' => 'Lire l\'article', 'en' => 'Read article', 'es' => 'Leer el artículo', 'ar' => 'اقرأ المقال' ),
		'read_time'      => array( 'fr' => '%s min de lecture', 'en' => '%s min read', 'es' => '%s min de lectura', 'ar' => '%s دقائق قراءة' ),
		'share'          => array( 'fr' => 'Partager', 'en' => 'Share', 'es' => 'Compartir', 'ar' => 'شارك' ),
		'copy_link'      => array( 'fr' => 'Copier le lien', 'en' => 'Copy link', 'es' => 'Copiar enlace', 'ar' => 'نسخ الرابط' ),
		'copied'         => array( 'fr' => 'Lien copié !', 'en' => 'Link copied!', 'es' => '¡Enlace copiado!', 'ar' => 'تم نسخ الرابط!' ),
		'related_posts'  => array( 'fr' => 'À lire aussi', 'en' => 'Read next', 'es' => 'Te puede interesar', 'ar' => 'اقرأ أيضاً' ),
		'all_posts'      => array( 'fr' => 'Tous les articles', 'en' => 'All articles', 'es' => 'Todos los artículos', 'ar' => 'كل المقالات' ),
		'no_posts'       => array( 'fr' => 'Les premiers articles arrivent bientôt.', 'en' => 'The first articles are coming soon.', 'es' => 'Los primeros artículos llegan pronto.', 'ar' => 'أولى المقالات قريباً.' ),
		'need_service'   => array( 'fr' => 'Besoin d\'un coup de main pour ton projet ?', 'en' => 'Need a hand with your project?', 'es' => '¿Necesitas ayuda con tu proyecto?', 'ar' => 'تحتاج مساعدة في مشروعك؟' ),

		// Tarifs de la chanson personnalisée.
		'price_from'     => array( 'fr' => 'Ta chanson dès %1$s · Clip vidéo %2$s', 'en' => 'Your song from %1$s · Music video %2$s', 'es' => 'Tu canción desde %1$s · Videoclip %2$s', 'ar' => 'أغنيتك ابتداءً من %1$s · الفيديو كليب %2$s' ),
		'promo_bar'      => array( 'fr' => 'Ta chanson à %1$s au lieu de %2$s', 'en' => 'Your song for %1$s instead of %2$s', 'es' => 'Tu canción a %1$s en vez de %2$s', 'ar' => 'أغنيتك بـ%1$s بدلاً من %2$s' ),
		'promo_price'    => array( 'fr' => 'Ta chanson à %s', 'en' => 'Your song for %s', 'es' => 'Tu canción a %s', 'ar' => 'أغنيتك بـ%s' ),
		'delivery'       => array( 'fr' => 'Livraison sous %s jours', 'en' => 'Delivered within %s days', 'es' => 'Entrega en %s días', 'ar' => 'التسليم خلال %s أيام' ),
		'per_clip'       => array( 'fr' => '%s / clip', 'en' => '%s / video', 'es' => '%s / videoclip', 'ar' => '%s / كليب' ),
		'opt_title'      => array( 'fr' => 'Ajoutez la touche finale', 'en' => 'Add the finishing touch', 'es' => 'Añade el toque final', 'ar' => 'أضف اللمسة الأخيرة' ),
		'express_t'      => array( 'fr' => 'Livraison express', 'en' => 'Express delivery', 'es' => 'Entrega exprés', 'ar' => 'تسليم سريع' ),
		'express_s'      => array( 'fr' => 'Recevez votre chanson en %1$s h au lieu de %2$s jours.', 'en' => 'Get your song in %1$s h instead of %2$s days.', 'es' => 'Recibe tu canción en %1$s h en lugar de %2$s días.', 'ar' => 'استلم أغنيتك خلال %1$s ساعة بدلاً من %2$s أيام.' ),
		'tip_t'          => array( 'fr' => 'Ajouter un pourboire', 'en' => 'Add a tip', 'es' => 'Añadir una propina', 'ar' => 'أضف بقشيشاً' ),
		'tip_s'          => array( 'fr' => 'Votre pourboire soutient directement votre compositeur (facultatif).', 'en' => 'Your tip goes straight to your composer (optional).', 'es' => 'Tu propina apoya directamente a tu compositor (opcional).', 'ar' => 'بقشيشك يدعم الملحن مباشرة (اختياري).' ),
		'tip_none'       => array( 'fr' => 'Aucun', 'en' => 'None', 'es' => 'Ninguna', 'ar' => 'لا شيء' ),
		'tip_custom'     => array( 'fr' => 'Pourboire personnalisé', 'en' => 'Custom tip', 'es' => 'Propina personalizada', 'ar' => 'بقشيش مخصص' ),
		'tip_thanks'     => array( 'fr' => 'Merci du fond du cœur ❤️', 'en' => 'Thank you from the bottom of our hearts ❤️', 'es' => 'Gracias de todo corazón ❤️', 'ar' => 'شكراً من أعماق القلب ❤️' ),
		'popular'        => array( 'fr' => 'Populaire', 'en' => 'Popular', 'es' => 'Popular', 'ar' => 'الأكثر اختياراً' ),
		'sum_title'      => array( 'fr' => 'Récapitulatif', 'en' => 'Summary', 'es' => 'Resumen', 'ar' => 'الملخص' ),
		'sum_song'       => array( 'fr' => 'Chanson pour <strong data-name>%s</strong>', 'en' => 'Song for <strong data-name>%s</strong>', 'es' => 'Canción para <strong data-name>%s</strong>', 'ar' => 'أغنية لـ<strong data-name>%s</strong>' ),
		'sum_clip'       => array( 'fr' => 'Clip vidéo', 'en' => 'Music video', 'es' => 'Videoclip', 'ar' => 'فيديو كليب' ),
		'sum_tip'        => array( 'fr' => 'Pourboire', 'en' => 'Tip', 'es' => 'Propina', 'ar' => 'بقشيش' ),
		'sum_total'      => array( 'fr' => 'Total', 'en' => 'Total', 'es' => 'Total', 'ar' => 'المجموع' ),
		'sum_save'       => array( 'fr' => 'Éco. %s', 'en' => 'Save %s', 'es' => 'Ahorras %s', 'ar' => 'توفير %s' ),
		'pay_note'       => array( 'fr' => 'Rien à payer maintenant : on confirme ta demande sur WhatsApp, puis paiement par virement ou en agence avant le début du travail.', 'en' => 'Nothing to pay now: we confirm your request on WhatsApp, then payment by bank transfer or cash agency before work starts.', 'es' => 'No pagas nada ahora: confirmamos tu pedido por WhatsApp y luego pagas por transferencia o en agencia antes de empezar.', 'ar' => 'لا شيء للدفع الآن: نؤكد طلبك عبر واتساب، ثم الدفع بتحويل بنكي أو في وكالة قبل بدء العمل.' ),

		// Livres audio & podcasts.
		'nav_audiobooks' => array( 'fr' => 'Livres audio', 'en' => 'Audiobooks', 'es' => 'Audiolibros', 'ar' => 'الكتب الصوتية' ),
		'ab_kicker'      => array( 'fr' => 'Livres audio & podcasts', 'en' => 'Audiobooks & podcasts', 'es' => 'Audiolibros y pódcasts', 'ar' => 'الكتب الصوتية والبودكاست' ),
		'ab_title'       => array( 'fr' => 'Ton livre, <em>en voix</em>', 'en' => 'Your book, <em>in voice</em>', 'es' => 'Tu libro, <em>en voz</em>', 'ar' => 'كتابك، <em>بصوت حيّ</em>' ),
		'ab_sub'         => array( 'fr' => 'Nous transformons livres, romans et ebooks en livres audio professionnels. Et nous produisons aussi ton podcast.', 'en' => 'We turn books, novels and ebooks into professional audiobooks. And we produce your podcast too.', 'es' => 'Convertimos libros, novelas y ebooks en audiolibros profesionales. Y también producimos tu pódcast.', 'ar' => 'نحوّل الكتب والروايات والكتب الإلكترونية إلى كتب صوتية احترافية، وننتج أيضاً البودكاست الخاص بك.' ),
		'ab_cta'         => array( 'fr' => 'Produire mon livre audio', 'en' => 'Produce my audiobook', 'es' => 'Producir mi audiolibro', 'ar' => 'أنتج كتابي الصوتي' ),
		'ab_cta_pod'     => array( 'fr' => 'Option podcast', 'en' => 'Podcast option', 'es' => 'Opción pódcast', 'ar' => 'خيار البودكاست' ),
		'ab_see_example' => array( 'fr' => 'Voir un exemple', 'en' => 'See an example', 'es' => 'Ver un ejemplo', 'ar' => 'شاهد مثالاً' ),
		'ab_card_tag'    => array( 'fr' => 'Livres, romans et ebooks transformés en livres audio professionnels, et production de podcasts', 'en' => 'Books, novels and ebooks turned into professional audiobooks, plus podcast production', 'es' => 'Libros, novelas y ebooks convertidos en audiolibros profesionales, y producción de pódcasts', 'ar' => 'تحويل الكتب والروايات والكتب الإلكترونية إلى كتب صوتية احترافية، وإنتاج البودكاست' ),
		'ab_from_title'  => array( 'fr' => 'Ce que tu peux <em>nous confier</em>', 'en' => 'What you can <em>send us</em>', 'es' => 'Lo que nos puedes <em>confiar</em>', 'ar' => 'ما يمكنك <em>أن ترسله لنا</em>' ),
		'ab_f1_t'        => array( 'fr' => 'Livre imprimé', 'en' => 'Printed book', 'es' => 'Libro impreso', 'ar' => 'كتاب مطبوع' ),
		'ab_f1_s'        => array( 'fr' => 'Ton livre papier ou ton fichier d\'impression.', 'en' => 'Your paper book or your print file.', 'es' => 'Tu libro en papel o tu archivo de imprenta.', 'ar' => 'كتابك الورقي أو ملف الطباعة.' ),
		'ab_f2_t'        => array( 'fr' => 'Roman & nouvelles', 'en' => 'Novels & short stories', 'es' => 'Novelas y relatos', 'ar' => 'روايات وقصص' ),
		'ab_f2_s'        => array( 'fr' => 'Romans, recueils, contes, récits jeunesse.', 'en' => 'Novels, collections, tales, children\'s stories.', 'es' => 'Novelas, recopilaciones, cuentos, relatos infantiles.', 'ar' => 'روايات، مجموعات قصصية، حكايات، قصص للأطفال.' ),
		'ab_f3_t'        => array( 'fr' => 'Ebook', 'en' => 'Ebook', 'es' => 'Ebook', 'ar' => 'كتاب إلكتروني' ),
		'ab_f3_s'        => array( 'fr' => 'PDF, EPUB, Word ou Google Docs.', 'en' => 'PDF, EPUB, Word or Google Docs.', 'es' => 'PDF, EPUB, Word o Google Docs.', 'ar' => 'PDF أو EPUB أو Word أو Google Docs.' ),
		'ab_f4_t'        => array( 'fr' => 'Essais & formations', 'en' => 'Non-fiction & courses', 'es' => 'Ensayos y formación', 'ar' => 'مقالات ودورات' ),
		'ab_f4_s'        => array( 'fr' => 'Développement personnel, cours, documents pédagogiques.', 'en' => 'Self-help, courses, learning material.', 'es' => 'Desarrollo personal, cursos, material didáctico.', 'ar' => 'تنمية ذاتية، دروس، محتوى تعليمي.' ),
		'ab_what_title'  => array( 'fr' => 'Ce que nous <em>produisons</em>', 'en' => 'What we <em>produce</em>', 'es' => 'Lo que <em>producimos</em>', 'ar' => 'ما <em>ننتجه</em>' ),
		'ab_w1'          => array( 'fr' => 'Narration professionnelle : voix féminine, masculine ou plusieurs voix', 'en' => 'Professional narration: female, male or multiple voices', 'es' => 'Narración profesional: voz femenina, masculina o varias voces', 'ar' => 'سرد احترافي: صوت نسائي أو رجالي أو عدة أصوات' ),
		'ab_w2'          => array( 'fr' => 'Adaptation du texte pour l\'écoute et direction de la lecture', 'en' => 'Text adapted for listening, with directed reading', 'es' => 'Adaptación del texto para la escucha y dirección de la lectura', 'ar' => 'تكييف النص للاستماع وتوجيه القراءة' ),
		'ab_w3'          => array( 'fr' => 'Ambiances sonores et musique originale (en option)', 'en' => 'Soundscapes and original music (optional)', 'es' => 'Ambientes sonoros y música original (opcional)', 'ar' => 'مؤثرات صوتية وموسيقى أصلية (اختياري)' ),
		'ab_w4'          => array( 'fr' => 'Montage, nettoyage, mixage et mastering', 'en' => 'Editing, cleanup, mixing and mastering', 'es' => 'Montaje, limpieza, mezcla y masterización', 'ar' => 'مونتاج، تنقية، مكساج وماسترينغ' ),
		'ab_w5'          => array( 'fr' => 'Découpage en chapitres, fichiers MP3 et M4B', 'en' => 'Split into chapters, MP3 and M4B files', 'es' => 'División en capítulos, archivos MP3 y M4B', 'ar' => 'تقسيم إلى فصول، ملفات MP3 وM4B' ),
		'ab_w6'          => array( 'fr' => 'Couverture carrée adaptée aux plateformes audio', 'en' => 'Square cover designed for audio platforms', 'es' => 'Portada cuadrada adaptada a las plataformas de audio', 'ar' => 'غلاف مربع مناسب لمنصات الصوت' ),
		'ab_langs'       => array( 'fr' => 'En français, arabe, darija, anglais, espagnol et amazigh.', 'en' => 'In French, Arabic, Darija, English, Spanish and Amazigh.', 'es' => 'En francés, árabe, darija, inglés, español y amazigh.', 'ar' => 'بالفرنسية والعربية والدارجة والإنجليزية والإسبانية والأمازيغية.' ),
		'ab_s1_t'        => array( 'fr' => 'Envoi du texte', 'en' => 'Send the text', 'es' => 'Envío del texto', 'ar' => 'إرسال النص' ),
		'ab_s1_s'        => array( 'fr' => 'Tu nous envoies ton livre ou ton ebook et la voix que tu imagines.', 'en' => 'You send your book or ebook and the voice you have in mind.', 'es' => 'Nos envías tu libro o ebook y la voz que imaginas.', 'ar' => 'ترسل لنا كتابك أو كتابك الإلكتروني والصوت الذي تتخيله.' ),
		'ab_s2_t'        => array( 'fr' => 'Casting & extrait', 'en' => 'Casting & sample', 'es' => 'Casting y muestra', 'ar' => 'اختيار الصوت ومقطع تجريبي' ),
		'ab_s2_s'        => array( 'fr' => 'Nous te proposons une voix et un extrait test à valider, avec le devis.', 'en' => 'We suggest a voice and a test sample to approve, with the quote.', 'es' => 'Te proponemos una voz y una muestra para validar, con el presupuesto.', 'ar' => 'نقترح عليك صوتاً ومقطعاً تجريبياً للموافقة، مع عرض السعر.' ),
		'ab_s3_t'        => array( 'fr' => 'Enregistrement', 'en' => 'Recording', 'es' => 'Grabación', 'ar' => 'التسجيل' ),
		'ab_s3_s'        => array( 'fr' => 'Narration chapitre par chapitre, avec relecture attentive.', 'en' => 'Chapter-by-chapter narration, carefully proof-listened.', 'es' => 'Narración capítulo a capítulo, con revisión atenta.', 'ar' => 'سرد فصلاً بفصل مع مراجعة دقيقة.' ),
		'ab_s4_t'        => array( 'fr' => 'Livraison', 'en' => 'Delivery', 'es' => 'Entrega', 'ar' => 'التسليم' ),
		'ab_s4_s'        => array( 'fr' => 'Fichiers masterisés et chapitrés, prêts à vendre ou à diffuser.', 'en' => 'Mastered, chaptered files, ready to sell or share.', 'es' => 'Archivos masterizados y por capítulos, listos para vender o difundir.', 'ar' => 'ملفات جاهزة ومقسمة إلى فصول، للبيع أو النشر.' ),
		'ab_ex_kicker' => array( 'fr' => 'Exemple : un livre OULBIG en version audio', 'en' => 'Example: an OULBIG book in audio', 'es' => 'Ejemplo: un libro de OULBIG en audio', 'ar' => 'مثال: كتاب لـ OULBIG بنسخة صوتية' ),
		'ab_ex_note' => array( 'fr' => 'Extraits : épisodes du podcast Étincelle, synchronisés automatiquement depuis YouTube.', 'en' => 'Samples: episodes of the Étincelle podcast, synced automatically from YouTube.', 'es' => 'Fragmentos: episodios del pódcast Étincelle, sincronizados automáticamente desde YouTube.', 'ar' => 'المقاطع: حلقات بودكاست «لمحة»، تتم مزامنتها تلقائياً من يوتيوب.' ),
		'ab_ex_title' => array( 'fr' => 'Introduction : 37 étapes + 1', 'en' => 'Introduction: 37 Steps + 1', 'es' => 'Introducción: 37 pasos + 1', 'ar' => 'مقدمة: 37 خطوة + 1' ),
		'ab_ex_author' => array( 'fr' => 'OULBIG · Saga L\'Étincelle, tome 1', 'en' => 'OULBIG · The Spark saga, book 1', 'es' => 'OULBIG · Saga La Chispa, tomo 1', 'ar' => 'OULBIG · سلسلة «لمحة»، الجزء الأول' ),
		'ab_ex_narr'     => array( 'fr' => 'Narration', 'en' => 'Narrated by', 'es' => 'Narración', 'ar' => 'السرد' ),
		'ab_ex_narr_v' => array( 'fr' => 'OULBIG', 'en' => 'OULBIG', 'es' => 'OULBIG', 'ar' => 'OULBIG' ),
		'ab_ex_len' => array( 'fr' => 'Extraits', 'en' => 'Samples', 'es' => 'Fragmentos', 'ar' => 'المقاطع' ),
		'ab_ex_len_v' => array( 'fr' => '%s épisodes', 'en' => '%s episodes', 'es' => '%s episodios', 'ar' => '%s حلقة' ),
		'ab_ex_lang'     => array( 'fr' => 'Langue', 'en' => 'Language', 'es' => 'Idioma', 'ar' => 'اللغة' ),
		'ab_ex_lang_v' => array( 'fr' => 'FR · EN · ES · AR · Darija', 'en' => 'FR · EN · ES · AR · Darija', 'es' => 'FR · EN · ES · AR · Darija', 'ar' => 'الفرنسية · الإنجليزية · الإسبانية · العربية · الدارجة' ),
		'ab_ex_format'   => array( 'fr' => 'Formats', 'en' => 'Formats', 'es' => 'Formatos', 'ar' => 'الصيغ' ),
		'ab_ex_synopsis' => array( 'fr' => 'Le tome 1 de la saga L\'Étincelle : un livre de développement personnel en 37 étapes, plus une, publié en français, anglais, espagnol, arabe et darija. Il prolonge le podcast Étincelle et l\'album de TALIM.', 'en' => 'Book 1 of The Spark saga: a self-development book in 37 steps, plus one, published in French, English, Spanish, Arabic and Darija. It extends the Étincelle podcast and TALIM\'s album.', 'es' => 'El tomo 1 de la saga La Chispa: un libro de desarrollo personal en 37 pasos, más uno, publicado en francés, inglés, español, árabe y darija. Prolonga el pódcast Étincelle y el álbum de TALIM.', 'ar' => 'الجزء الأول من سلسلة «لمحة»: كتاب في تطوير الذات من 37 خطوة وخطوة إضافية، صدر بالفرنسية والإنجليزية والإسبانية والعربية والدارجة. وهو امتداد لبودكاست «لمحة» وألبوم TALIM.' ),
		'ab_ex_ch1'      => array( 'fr' => 'Prologue — La lettre', 'en' => 'Prologue — The letter', 'es' => 'Prólogo — La carta', 'ar' => 'تمهيد — الرسالة' ),
		'ab_ex_ch2'      => array( 'fr' => 'Chapitre 1 — Le port', 'en' => 'Chapter 1 — The harbour', 'es' => 'Capítulo 1 — El puerto', 'ar' => 'الفصل 1 — الميناء' ),
		'ab_ex_ch3'      => array( 'fr' => 'Chapitre 2 — La médina', 'en' => 'Chapter 2 — The medina', 'es' => 'Capítulo 2 — La medina', 'ar' => 'الفصل 2 — المدينة القديمة' ),
		'ab_ex_ch4'      => array( 'fr' => 'Chapitre 3 — Le jardin caché', 'en' => 'Chapter 3 — The hidden garden', 'es' => 'Capítulo 3 — El jardín escondido', 'ar' => 'الفصل 3 — الحديقة المخفية' ),
		'ab_ex_chapters' => array( 'fr' => 'Extraits du podcast Étincelle', 'en' => 'Samples from the Étincelle podcast', 'es' => 'Fragmentos del pódcast Étincelle', 'ar' => 'مقاطع من بودكاست «لمحة»' ),
		'ab_ex_sample'   => array( 'fr' => '« Extrait »', 'en' => '"Sample"', 'es' => '«Fragmento»', 'ar' => '«مقطع»' ),
		'ab_ex_more'     => array( 'fr' => '+ 24 autres chapitres', 'en' => '+ 24 more chapters', 'es' => '+ 24 capítulos más', 'ar' => '+ 24 فصلاً آخر' ),
		'ab_pod_title'   => array( 'fr' => 'Option <em>podcast</em>', 'en' => 'Podcast <em>option</em>', 'es' => 'Opción <em>pódcast</em>', 'ar' => 'خيار <em>البودكاست</em>' ),
		'ab_pod_sub'     => array( 'fr' => 'Ta voix, ton émission : on s\'occupe du son de A à Z, épisode après épisode.', 'en' => 'Your voice, your show: we handle the sound from A to Z, episode after episode.', 'es' => 'Tu voz, tu programa: nos ocupamos del sonido de la A a la Z, episodio tras episodio.', 'ar' => 'صوتك، برنامجك: نتكفل بالصوت من الألف إلى الياء، حلقة بعد حلقة.' ),
		'ab_p1'          => array( 'fr' => 'Enregistrement guidé à distance, ou montage de tes fichiers', 'en' => 'Remote guided recording, or editing of your files', 'es' => 'Grabación guiada a distancia, o montaje de tus archivos', 'ar' => 'تسجيل موجّه عن بُعد أو مونتاج ملفاتك' ),
		'ab_p2'          => array( 'fr' => 'Nettoyage des voix, mixage et mastering', 'en' => 'Voice cleanup, mixing and mastering', 'es' => 'Limpieza de voces, mezcla y masterización', 'ar' => 'تنقية الأصوات، مكساج وماسترينغ' ),
		'ab_p3'          => array( 'fr' => 'Générique et jingle musical sur mesure', 'en' => 'Custom intro and musical jingle', 'es' => 'Cabecera y jingle musical a medida', 'ar' => 'مقدمة وجينغل موسيقي حسب الطلب' ),
		'ab_p4'          => array( 'fr' => 'Publication sur Spotify, Apple Podcasts, YouTube, Deezer…', 'en' => 'Publishing on Spotify, Apple Podcasts, YouTube, Deezer…', 'es' => 'Publicación en Spotify, Apple Podcasts, YouTube, Deezer…', 'ar' => 'النشر على سبوتيفاي وآبل بودكاست ويوتيوب وديزر…' ),
		'ab_p5'          => array( 'fr' => 'Habillage : cover, description et extraits vidéo pour les réseaux', 'en' => 'Branding: cover, description and video clips for social media', 'es' => 'Identidad: portada, descripción y clips de vídeo para redes', 'ar' => 'هوية بصرية: غلاف ووصف ومقاطع فيديو للشبكات' ),
		'ab_pod_ex' => array( 'fr' => 'Dernier épisode', 'en' => 'Latest episode', 'es' => 'Último episodio', 'ar' => 'آخر حلقة' ),
		'ab_form_title'  => array( 'fr' => 'Demande de devis', 'en' => 'Request a quote', 'es' => 'Pide tu presupuesto', 'ar' => 'اطلب عرض سعر' ),
		'ab_form_sub'    => array( 'fr' => 'Décris ton projet : nous revenons vers toi avec un extrait de voix et un devis.', 'en' => 'Describe your project: we\'ll come back with a voice sample and a quote.', 'es' => 'Describe tu proyecto: te responderemos con una muestra de voz y un presupuesto.', 'ar' => 'صِف مشروعك: سنعود إليك بمقطع صوتي وعرض سعر.' ),
		'ab_q_type'      => array( 'fr' => 'Type de projet', 'en' => 'Project type', 'es' => 'Tipo de proyecto', 'ar' => 'نوع المشروع' ),
		'ab_q_source'    => array( 'fr' => 'Format du texte', 'en' => 'Text format', 'es' => 'Formato del texto', 'ar' => 'صيغة النص' ),
		'ab_q_size'      => array( 'fr' => 'Longueur', 'en' => 'Length', 'es' => 'Extensión', 'ar' => 'الطول' ),
		'ab_size_ph'     => array( 'fr' => 'Ex. : 220 pages ou 60 000 mots', 'en' => 'e.g. 220 pages or 60,000 words', 'es' => 'Ej.: 220 páginas o 60 000 palabras', 'ar' => 'مثال: 220 صفحة أو 60000 كلمة' ),
		'ab_q_lang'      => array( 'fr' => 'Langue de lecture', 'en' => 'Reading language', 'es' => 'Idioma de lectura', 'ar' => 'لغة القراءة' ),
		'ab_q_voice'     => array( 'fr' => 'Voix souhaitée', 'en' => 'Preferred voice', 'es' => 'Voz deseada', 'ar' => 'الصوت المطلوب' ),
		'ab_q_music'     => array( 'fr' => 'Ajouter ambiances sonores et musique', 'en' => 'Add soundscapes and music', 'es' => 'Añadir ambientes sonoros y música', 'ar' => 'أضف مؤثرات صوتية وموسيقى' ),
		'ab_q_episodes'  => array( 'fr' => 'Nombre et durée des épisodes', 'en' => 'Number and length of episodes', 'es' => 'Número y duración de episodios', 'ar' => 'عدد الحلقات ومدتها' ),
		'ab_episodes_ph' => array( 'fr' => 'Ex. : 8 épisodes de 30 min', 'en' => 'e.g. 8 episodes of 30 min', 'es' => 'Ej.: 8 episodios de 30 min', 'ar' => 'مثال: 8 حلقات من 30 دقيقة' ),
		'ab_q_pformat'   => array( 'fr' => 'Format du podcast', 'en' => 'Podcast format', 'es' => 'Formato del pódcast', 'ar' => 'صيغة البودكاست' ),
		'ab_files'       => array( 'fr' => 'Lien vers ton texte ou tes fichiers', 'en' => 'Link to your text or files', 'es' => 'Enlace a tu texto o archivos', 'ar' => 'رابط النص أو الملفات' ),
		'ab_send'        => array( 'fr' => 'Envoyer ma demande de devis', 'en' => 'Send my quote request', 'es' => 'Enviar mi solicitud', 'ar' => 'أرسل طلب عرض السعر' ),
		'ab_thanks'      => array( 'fr' => 'Merci, ta demande est <em>bien arrivée</em> !', 'en' => 'Thank you, your request <em>has arrived</em>!', 'es' => '¡Gracias, tu solicitud <em>ha llegado</em>!', 'ar' => 'شكراً، وصل <em>طلبك</em> بنجاح!' ),

		'ab_pod_latest'  => array( 'fr' => 'Derniers épisodes OULBIG', 'en' => 'Latest OULBIG episodes', 'es' => 'Últimos episodios de OULBIG', 'ar' => 'أحدث حلقات OULBIG' ),
		'on_youtube'     => array( 'fr' => 'Sur YouTube', 'en' => 'On YouTube', 'es' => 'En YouTube', 'ar' => 'على يوتيوب' ),
		'artist_channel' => array( 'fr' => 'Chaîne de l\'artiste', 'en' => 'Artist channel', 'es' => 'Canal del artista', 'ar' => 'قناة الفنان' ),
	);
	return $d;
}
