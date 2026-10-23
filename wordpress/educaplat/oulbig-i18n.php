<?php
/**
 * Educaplat : traduction du site (FR, EN, ES, AR) + traduction automatique de secours.
 * À ajouter à la fin du functions.php du thème educaplat-theme (sans la ligne <?php).
 */


/* =====================================================================
 * OULBIG : site en 4 langues (FR · EN · ES · ع)
 * 1) Traduction écrite à la main pour les textes du site (dictionnaire).
 * 2) Traduction automatique de secours pour toute page sans traduction
 *    manuelle (pages du thème, futures pages) : Google Traduction.
 * La langue passe par ?lang=xx (compatible avec le cache LiteSpeed)
 * et le choix du visiteur est mémorisé dans son navigateur.
 * ===================================================================== */
function oulbig_lang() {
	static $l = null;
	if ( null !== $l ) { return $l; }
	$l = 'fr';
	if ( isset( $_GET['lang'] ) && in_array( $_GET['lang'], array( 'fr', 'en', 'es', 'ar' ), true ) ) {
		$l = $_GET['lang'];
	}
	return $l;
}

function oulbig_dict() {
	return array(
		/* En-tête et pied de page */
		'Accueil'            => array( 'en' => 'Home', 'es' => 'Inicio', 'ar' => 'الرئيسية' ),
		'Catalogue'          => array( 'en' => 'Catalogue', 'es' => 'Catálogo', 'ar' => 'الكتالوج' ),
		'Publier'            => array( 'en' => 'Publish', 'es' => 'Publicar', 'ar' => 'انشر معنا' ),
		'Abonnement'         => array( 'en' => 'Subscription', 'es' => 'Suscripción', 'ar' => 'الاشتراك' ),
		"S'abonner"          => array( 'en' => 'Subscribe', 'es' => 'Suscribirse', 'ar' => 'اشترك' ),
		'S&#039;abonner'     => array( 'en' => 'Subscribe', 'es' => 'Suscribirse', 'ar' => 'اشترك' ),
		'S&rsquo;abonner'    => array( 'en' => 'Subscribe', 'es' => 'Suscribirse', 'ar' => 'اشترك' ),
		'Mon compte'         => array( 'en' => 'My account', 'es' => 'Mi cuenta', 'ar' => 'حسابي' ),
		'Commandes par WhatsApp :' => array( 'en' => 'Orders via WhatsApp:', 'es' => 'Pedidos por WhatsApp:', 'ar' => 'الطلبات عبر واتساب:' ),
		'Audiolivres &amp; ebooks multilingues — Maroc' => array( 'en' => 'Multilingual audiobooks &amp; ebooks — Morocco', 'es' => 'Audiolibros y ebooks multilingües — Marruecos', 'ar' => 'كتب صوتية وإلكترونية متعددة اللغات — المغرب' ),
		'Commander sur WhatsApp' => array( 'en' => 'Order on WhatsApp', 'es' => 'Pedir por WhatsApp', 'ar' => 'اطلب عبر واتساب' ),
		/* Accueil */
		'Éditions OULBIG · Marrakech' => array( 'en' => 'OULBIG Editions · Marrakech', 'es' => 'Ediciones OULBIG · Marrakech', 'ar' => 'منشورات OULBIG · مراكش' ),
		'Des livres qui font avancer, en cinq langues.' => array( 'en' => 'Books that move you forward, in five languages.', 'es' => 'Libros que te hacen avanzar, en cinco idiomas.', 'ar' => 'كتب تدفعك إلى الأمام، بخمس لغات.' ),
		'Ebooks en français, anglais, espagnol, arabe et darija. Commande simple par WhatsApp, livre envoyé en PDF et EPUB.' => array( 'en' => 'Ebooks in French, English, Spanish, Arabic and Darija. Order easily on WhatsApp and get the book as PDF and EPUB.', 'es' => 'Ebooks en francés, inglés, español, árabe y dariya. Pide fácilmente por WhatsApp y recibe el libro en PDF y EPUB.', 'ar' => 'كتب إلكترونية بالفرنسية والإنجليزية والإسبانية والعربية والدارجة. اطلب بسهولة عبر واتساب واستلم الكتاب بصيغتي PDF وEPUB.' ),
		'Voir les livres'    => array( 'en' => 'See the books', 'es' => 'Ver los libros', 'ar' => 'تصفّح الكتب' ),
		'Écouter les extraits' => array( 'en' => 'Listen to excerpts', 'es' => 'Escuchar fragmentos', 'ar' => 'استمع إلى المقتطفات' ),
		'Nos livres'         => array( 'en' => 'Our books', 'es' => 'Nuestros libros', 'ar' => 'كتبنا' ),
		'Deux livres, cinq langues chacun' => array( 'en' => 'Two books, five languages each', 'es' => 'Dos libros, cinco idiomas cada uno', 'ar' => 'كتابان، بخمس لغات لكلّ منهما' ),
		'Développement personnel · Tome 1 de la saga L’Étincelle' => array( 'en' => 'Personal growth · Book 1 of The Spark saga', 'es' => 'Desarrollo personal · Tomo 1 de la saga La Chispa', 'ar' => 'تطوير الذات · الجزء الأول من سلسلة «لمحة»' ),
		'Roman · Marrakech'  => array( 'en' => 'Novel · Marrakech', 'es' => 'Novela · Marrakech', 'ar' => 'رواية · مراكش' ),
		'Choisir la langue :' => array( 'en' => 'Choose a language:', 'es' => 'Elige el idioma:', 'ar' => 'اختر اللغة:' ),
		'Livres audio'       => array( 'en' => 'Audiobooks', 'es' => 'Audiolibros', 'ar' => 'كتب صوتية' ),
		'Écoutez gratuitement' => array( 'en' => 'Listen for free', 'es' => 'Escucha gratis', 'ar' => 'استمع مجانًا' ),
		'Les derniers extraits du podcast L’Étincelle, qui accompagne « Introduction : 37 étapes + 1 ».' => array( 'en' => 'The latest excerpts from The Spark podcast, the companion to “Introduction: 37 Steps + 1”.', 'es' => 'Los últimos fragmentos del pódcast La Chispa, que acompaña a «Introducción: 37 pasos + 1».', 'ar' => 'أحدث المقتطفات من برنامج «لمحة» الصوتي، رفيق كتاب «مقدّمة: 37 خطوة + 1».' ),
		'Tous les extraits audio' => array( 'en' => 'All audio excerpts', 'es' => 'Todos los fragmentos', 'ar' => 'كل المقتطفات الصوتية' ),
		/* Services (sections de la 1re version) */
		'Ce que vous trouverez ici' => array( 'en' => 'What you will find here', 'es' => 'Lo que encontrarás aquí', 'ar' => 'ما ستجده هنا' ),
		'Audiolivres'        => array( 'en' => 'Audiobooks', 'es' => 'Audiolibros', 'ar' => 'كتب صوتية' ),
		'Écoutez des extraits gratuits du podcast, puis passez au livre complet.' => array( 'en' => 'Listen to free podcast excerpts, then move on to the full book.', 'es' => 'Escucha fragmentos gratis del pódcast y luego pasa al libro completo.', 'ar' => 'استمع إلى مقتطفات مجانية من البرنامج، ثم انتقل إلى الكتاب كاملًا.' ),
		'Ebooks PDF / EPUB'  => array( 'en' => 'PDF / EPUB ebooks', 'es' => 'Ebooks PDF / EPUB', 'ar' => 'كتب إلكترونية PDF / EPUB' ),
		'Achat à l’unité par WhatsApp, livre envoyé en PDF et EPUB.' => array( 'en' => 'Buy each book on WhatsApp and get it as PDF and EPUB.', 'es' => 'Compra cada libro por WhatsApp y recíbelo en PDF y EPUB.', 'ar' => 'اشترِ كل كتاب عبر واتساب واستلمه بصيغتي PDF وEPUB.' ),
		'Publier avec nous'  => array( 'en' => 'Publish with us', 'es' => 'Publica con nosotros', 'ar' => 'انشر معنا' ),
		'Vous êtes auteur ? Publiez votre livre en plusieurs langues.' => array( 'en' => 'Are you an author? Publish your book in several languages.', 'es' => '¿Eres autor? Publica tu libro en varios idiomas.', 'ar' => 'هل أنت كاتب؟ انشر كتابك بعدّة لغات.' ),
		'Découvrir'          => array( 'en' => 'Discover', 'es' => 'Descubrir', 'ar' => 'اكتشف' ),
		/* Catalogue */
		'Nos ebooks'         => array( 'en' => 'Our ebooks', 'es' => 'Nuestros ebooks', 'ar' => 'كتبنا الإلكترونية' ),
		'Chaque livre existe en PDF et en EPUB, à télécharger dès la validation de votre paiement.' => array( 'fr' => 'Chaque livre existe en PDF et en EPUB. Commande et paiement par WhatsApp.', 'en' => 'Every book comes as PDF and EPUB. Order and pay on WhatsApp.', 'es' => 'Cada libro está disponible en PDF y EPUB. Pide y paga por WhatsApp.', 'ar' => 'كل كتاب متوفّر بصيغتي PDF وEPUB. الطلب والدفع عبر واتساب.' ),
		'Tout'               => array( 'en' => 'All', 'es' => 'Todo', 'ar' => 'الكل' ),
		'Développement personnel' => array( 'en' => 'Personal growth', 'es' => 'Desarrollo personal', 'ar' => 'تطوير الذات' ),
		'Roman'              => array( 'en' => 'Novels', 'es' => 'Novelas', 'ar' => 'روايات' ),
		'Toutes les langues' => array( 'en' => 'All languages', 'es' => 'Todos los idiomas', 'ar' => 'كل اللغات' ),
		'Voir le livre'      => array( 'en' => 'See the book', 'es' => 'Ver el libro', 'ar' => 'عرض الكتاب' ),
		'Des extraits du podcast L’Étincelle, qui accompagne « Introduction : 37 étapes + 1 ». Ils se mettent à jour automatiquement à chaque nouvel épisode.' => array( 'en' => 'Excerpts from The Spark podcast, the companion to “Introduction: 37 Steps + 1”. New episodes are added automatically.', 'es' => 'Fragmentos del pódcast La Chispa, que acompaña a «Introducción: 37 pasos + 1». Los nuevos episodios se añaden automáticamente.', 'ar' => 'مقتطفات من برنامج «لمحة» الصوتي، رفيق كتاب «مقدّمة: 37 خطوة + 1». تُضاف الحلقات الجديدة تلقائيًا.' ),
		/* Fiche produit */
		'Ebook · '           => array( 'en' => 'Ebook · ', 'es' => 'Ebook · ', 'ar' => 'كتاب إلكتروني · ' ),
		/* Bandeau de traduction automatique */
		'Page traduite automatiquement.' => array( 'en' => 'This page was translated automatically.', 'es' => 'Esta página se ha traducido automáticamente.', 'ar' => 'تُرجمت هذه الصفحة تلقائيًا.' ),
	);
}

function oulbig_t( $fr ) {
	$d = oulbig_dict();
	$l = oulbig_lang();
	return isset( $d[ $fr ][ $l ] ) ? $d[ $fr ][ $l ] : $fr;
}

/* Textes du lecteur d'extraits audio (plugin OULBIG Catalogue) */
add_filter( 'oulbig_audiolivres_labels', function ( $t ) {
	$x = array(
		'en' => array( 'all' => 'All languages', 'kicker' => 'Audiobook · free excerpt', 'book' => 'The full book', 'listen' => 'Listen' ),
		'es' => array( 'all' => 'Todos los idiomas', 'kicker' => 'Audiolibro · fragmento gratis', 'book' => 'El libro completo', 'listen' => 'Escuchar' ),
		'ar' => array( 'all' => 'كل اللغات', 'kicker' => 'كتاب صوتي · مقتطف مجاني', 'book' => 'الكتاب كاملًا', 'listen' => 'استمع' ),
	);
	$l = oulbig_lang();
	return isset( $x[ $l ] ) ? array_merge( $t, $x[ $l ] ) : $t;
} );

/* Liens « autre langue » pour Google et mémorisation du choix */
add_action( 'wp_head', function () {
	$base = strtok( ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], '?' );
	foreach ( array( 'fr', 'en', 'es', 'ar' ) as $c ) {
		echo '<link rel="alternate" hreflang="' . esc_attr( $c ) . '" href="' . esc_url( add_query_arg( 'lang', $c, $base ) ) . '">' . "\n";
	}
	echo '<script>(function(){try{var u=new URL(location.href),p=u.searchParams.get("lang"),s=localStorage.getItem("oulbig_lang");if(p){localStorage.setItem("oulbig_lang",p);}else if(s&&s!=="fr"&&/^(en|es|ar)$/.test(s)){u.searchParams.set("lang",s);location.replace(u.toString());}}catch(e){}})();</script>' . "\n";
}, 1 );

/* Réécriture de la page : sélecteur, textes traduits, liens, sens de lecture */
add_action( 'template_redirect', function () {
	if ( is_admin() || wp_doing_ajax() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	ob_start( 'oulbig_i18n_buffer' );
}, 0 );

function oulbig_i18n_buffer( $html ) {
	if ( false === stripos( $html, '<html' ) ) {
		return $html;
	}
	$l    = oulbig_lang();
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	$base = strtok( ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], '?' );

	/* 1. Sélecteur de langue actif : FR · EN · ES · ع */
	$labels = array( 'fr' => 'FR', 'en' => 'EN', 'es' => 'ES', 'ar' => 'ع' );
	$sw = '<div class="lang notranslate">';
	foreach ( $labels as $c => $t ) {
		$sw .= '<a class="oulbig-lang-link" hreflang="' . $c . '" href="' . esc_url( add_query_arg( 'lang', $c, $base ) ) . '"><span' . ( $c === $l ? ' class="current-lang"' : '' ) . '>' . $t . '</span></a>';
	}
	$sw .= '</div>';
	$html = preg_replace( '#<div class="lang">.*?</div>#s', $sw, $html, 1 );

	/* 2. Pied de page avec toutes les pages du site */
	$links = array(
		'Accueil'      => home_url( '/' ),
		'Catalogue'    => home_url( '/catalogue/' ),
		'Livres audio' => home_url( '/catalogue/#livres-audio' ),
		'Publier'      => home_url( '/publier/' ),
		'Abonnement'   => home_url( '/abonnement/' ),
		'Mon compte'   => home_url( '/my-account/' ),
	);
	$f = '<footer class="site-footer notranslate"><div class="wrap oulbig-footer"><nav>';
	foreach ( $links as $t => $u ) { $f .= '<a href="' . esc_url( $u ) . '">' . $t . '</a>'; }
	$f .= '</nav><p>' . oulbig_t( 'Commandes par WhatsApp :' ) . ' <a href="https://wa.me/212661692067" target="_blank" rel="noopener">+212 661 692 067</a></p>';
	$f .= '<p>educaplat.almoqayada.com · ' . oulbig_t( 'Audiolivres &amp; ebooks multilingues — Maroc' ) . '</p></div></footer>';
	$f .= '<style>.oulbig-footer{display:flex;flex-direction:column;gap:8px;align-items:center;text-align:center}.oulbig-footer nav{display:flex;flex-wrap:wrap;gap:6px 18px;justify-content:center}.oulbig-footer a{color:inherit}.oulbig-footer p{margin:0;opacity:.8}.lang a{text-decoration:none;color:inherit}</style>';
	$html = preg_replace( '#<footer class="site-footer">.*?</footer>#s', $f, $html, 1 );

	/* 3. Sections de la 1re version sur l'accueil (services) */
	if ( false !== strpos( $html, '<section class="oulbig-audio-home">' ) ) {
		$cards = array(
			array( '🎧', 'Audiolivres', 'Écoutez des extraits gratuits du podcast, puis passez au livre complet.', home_url( '/catalogue/#livres-audio' ) ),
			array( '📕', 'Ebooks PDF / EPUB', 'Achat à l’unité par WhatsApp, livre envoyé en PDF et EPUB.', home_url( '/catalogue/' ) ),
			array( '✍️', 'Publier avec nous', 'Vous êtes auteur ? Publiez votre livre en plusieurs langues.', home_url( '/publier/' ) ),
		);
		$s = '<section class="oulbig-services"><p class="oulbig-k">Ce que vous trouverez ici</p><div class="oulbig-svc">';
		foreach ( $cards as $c ) {
			$s .= '<a class="oulbig-svc-card" href="' . esc_url( $c[3] ) . '"><span class="oulbig-svc-ico">' . $c[0] . '</span><h3>' . $c[1] . '</h3><p>' . $c[2] . '</p><span class="oulbig-svc-more">Découvrir</span></a>';
		}
		$s .= '</div></section><style>.oulbig-svc{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px}.oulbig-svc-card{display:flex;flex-direction:column;gap:6px;padding:22px;border-radius:12px;border:1px solid rgba(127,127,127,.25);color:inherit;text-decoration:none}.oulbig-svc-card:hover{border-color:#c8102e}.oulbig-svc-ico{font-size:28px}.oulbig-svc-card h3{margin:4px 0 0}.oulbig-svc-card p{margin:0;opacity:.8}.oulbig-svc-more{margin-top:auto;color:#c8102e;font-weight:700}</style>';
		$html = str_replace( '<section class="oulbig-audio-home">', $s . '<section class="oulbig-audio-home">', $html );
	}

	/* 4. Zones traduites à la main : exclues de la traduction automatique */
	$html = str_replace( '<main class="oulbig-', '<main data-oulbig-manual="1" class="notranslate oulbig-', $html );
	$html = preg_replace( '#<header class="#', '<header class="notranslate ', $html, 1 );
	$manual = ( false !== strpos( $html, 'data-oulbig-manual="1"' ) );

	/* 5. Textes traduits à la main (et petites corrections du français) */
	foreach ( oulbig_dict() as $fr => $tr ) {
		if ( ! isset( $tr[ $l ] ) ) { continue; }
		$html = str_replace( array( '>' . $fr . '<', '"' . $fr . '"' ), array( '>' . $tr[ $l ] . '<', '"' . $tr[ $l ] . '"' ), $html );
	}
	$html = str_replace( '<p class="oulbig-product-lang">Ebook · ', '<p class="oulbig-product-lang">' . oulbig_t( 'Ebook · ' ), $html );

	if ( 'fr' !== $l ) {
		/* 6. Garder la langue en naviguant d'une page à l'autre */
		$html = preg_replace_callback(
			'#href="(https?://' . preg_quote( $host, '#' ) . '(?!/wp-(?:admin|content|json|includes|login))[^"\#]*?)(\#[^"]*)?"#',
			function ( $m ) {
				$u = html_entity_decode( $m[1] );
				if ( preg_match( '#\.(jpe?g|png|webp|gif|svg|pdf|epub|css|js|xml)(\?|$)#i', $u ) || false !== strpos( $u, 'lang=' ) ) {
					return $m[0];
				}
				return 'href="' . esc_url( add_query_arg( 'lang', oulbig_lang(), $u ) ) . ( isset( $m[2] ) ? $m[2] : '' ) . '"';
			},
			$html
		);
		/* 7. Langue et sens de lecture de la page */
		$html = preg_replace( '#<html([^>]*?)\slang="[^"]*"#', '<html$1 lang="' . $l . '"' . ( 'ar' === $l ? ' dir="rtl"' : '' ), $html, 1 );
	}

	/* 8. Traduction automatique de secours (pages sans traduction manuelle) */
	if ( 'fr' !== $l && ! $manual ) {
		$gt  = '<div id="oulbig_gt" style="display:none"></div>';
		$gt .= '<p class="oulbig-auto-note notranslate">' . oulbig_t( 'Page traduite automatiquement.' ) . '</p>';
		$gt .= '<script>document.cookie="googtrans=/fr/' . $l . ';path=/";function oulbigGT(){new google.translate.TranslateElement({pageLanguage:"fr",includedLanguages:"en,es,ar",autoDisplay:false},"oulbig_gt");}</script>';
		$gt .= '<script src="https://translate.google.com/translate_a/element.js?cb=oulbigGT"></script>';
		$gt .= '<style>.goog-te-banner-frame,.skiptranslate>iframe,#goog-gt-tt,.goog-te-balloon-frame{display:none!important}body{top:0!important}.goog-text-highlight{background:none!important;box-shadow:none!important}.oulbig-auto-note{text-align:center;font-size:.8em;opacity:.65;margin:16px 0}</style>';
		$html = preg_replace( '#</body>#i', $gt . '</body>', $html, 1 );
	} else {
		$html = preg_replace( '#</body>#i', '<script>document.cookie="googtrans=;path=/;max-age=0";</script></body>', $html, 1 );
	}
	return $html;
}
