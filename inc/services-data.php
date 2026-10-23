<?php
/**
 * Services de départ (inspirés de Morocco Production) — modifiables dans l'admin.
 * Chaque champ texte : [fr, en, es, ar]. Listes : une entrée par ligne.
 */
defined( 'ABSPATH' ) || exit;

function mcl_services_seed() {
	return array(
		array(
			'slug' => 'pack-song', 'icon' => '🚀', 'price' => '500 DH', 'order' => 1,
			'title' => array( 'Pack Song', 'Song Pack', 'Pack Canción', 'باقة الأغنية' ),
			'tagline' => array( 'Ta chanson déjà prête, mise en ligne partout', 'Your finished song, released everywhere', 'Tu canción ya lista, publicada en todas partes', 'أغنيتك الجاهزة، منشورة على كل المنصات' ),
			'content' => array(
				'Tu as déjà ta chanson terminée ? On la publie sur toutes les plateformes de streaming et on crée ton compte artiste. Tu en restes l\'administrateur.',
				'Already have a finished song? We release it on every streaming platform and create your artist account. You stay the admin.',
				'¿Ya tienes tu canción terminada? La publicamos en todas las plataformas de streaming y creamos tu cuenta de artista. Tú sigues siendo el administrador.',
				'لديك أغنية جاهزة؟ ننشرها على جميع منصات البث وننشئ حسابك كفنان، وتبقى أنت المسؤول عنه.',
			),
			'features' => array(
				"Distribution sur Spotify, Apple Music, Deezer et toutes les plateformes\nCréation de ton compte artiste\nTu restes administrateur de tes comptes\n100 % à distance",
				"Distribution to Spotify, Apple Music, Deezer and every platform\nArtist account creation\nYou keep admin access to your accounts\n100% remote",
				"Distribución en Spotify, Apple Music, Deezer y todas las plataformas\nCreación de tu cuenta de artista\nSigues siendo administrador de tus cuentas\n100 % a distancia",
				"التوزيع على سبوتيفاي وآبل ميوزك وديزر وكل المنصات\nإنشاء حسابك كفنان\nتبقى المسؤول عن حساباتك\n100٪ عن بُعد",
			),
		),
		array(
			'slug' => 'pack-cover', 'icon' => '🎙️', 'price' => '600 DH', 'order' => 2,
			'title' => array( 'Pack Cover', 'Cover Pack', 'Pack Cover', 'باقة الكوفر' ),
			'tagline' => array( 'Ton cover en ligne, en toute légalité', 'Your cover online, fully legal', 'Tu cover en línea, totalmente legal', 'الكوفر ديالك أونلاين، بشكل قانوني' ),
			'content' => array(
				'Tu as enregistré la reprise d\'une chanson ? On s\'occupe de la licence de cover pour que ta version soit publiée légalement, puis on la distribue partout.',
				'Recorded a cover of a song? We handle the cover licence so your version is released legally, then distribute it everywhere.',
				'¿Grabaste una versión de una canción? Nos ocupamos de la licencia de cover para que tu versión se publique legalmente y la distribuimos en todas partes.',
				'سجلت نسخة من أغنية معروفة؟ نتكفل برخصة الكوفر لتُنشر نسختك بشكل قانوني، ثم نوزعها على كل المنصات.',
			),
			'features' => array(
				"Licence de cover incluse\nDistribution sur toutes les plateformes\nCréation du compte artiste\n100 % à distance",
				"Cover licence included\nDistribution on every platform\nArtist account creation\n100% remote",
				"Licencia de cover incluida\nDistribución en todas las plataformas\nCreación de la cuenta de artista\n100 % a distancia",
				"رخصة الكوفر مشمولة\nالتوزيع على كل المنصات\nإنشاء حساب الفنان\n100٪ عن بُعد",
			),
		),
		array(
			'slug' => 'cover-complet', 'icon' => '🎚️', 'price' => '1200 DH', 'order' => 3,
			'badge' => array( 'Le plus demandé', 'Most popular', 'El más pedido', 'الأكثر طلباً' ),
			'title' => array( 'Cover complet', 'Full Cover', 'Cover completo', 'كوفر متكامل' ),
			'tagline' => array( 'Tu chantes, on s\'occupe de tout le reste', 'You sing, we take care of everything else', 'Tú cantas, nosotros nos ocupamos del resto', 'أنت تغني، ونحن نتكفل بالباقي' ),
			'content' => array(
				'Tu envoies ta voix, on produit le titre de A à Z : arrangement, mixage, mastering et autotune, licence de cover et mise en ligne sur toutes les plateformes.',
				'Send us your voice and we produce the track from A to Z: arrangement, mixing, mastering and autotune, cover licence and release on every platform.',
				'Envías tu voz y producimos el tema de la A a la Z: arreglo, mezcla, masterización y autotune, licencia de cover y publicación en todas las plataformas.',
				'ترسل لنا صوتك، ونُنتج الأغنية من الألف إلى الياء: توزيع موسيقي، مكساج، ماسترينغ وأوتوتون، رخصة الكوفر والنشر على كل المنصات.',
			),
			'features' => array(
				"Production complète : arrangement, mixage, mastering, autotune\nDistribution digitale\nLicence de cover\nCréation du compte artiste",
				"Full production: arrangement, mixing, mastering, autotune\nDigital distribution\nCover licence\nArtist account creation",
				"Producción completa: arreglo, mezcla, masterización, autotune\nDistribución digital\nLicencia de cover\nCreación de la cuenta de artista",
				"إنتاج كامل: توزيع موسيقي، مكساج، ماسترينغ، أوتوتون\nتوزيع رقمي\nرخصة الكوفر\nإنشاء حساب الفنان",
			),
			'options' => array(
				"Formule complète — 1200 DH\nAvec comptes artiste existants — 900 DH\nSans distribution — 700 DH",
				"Full package — 1200 DH\nWith existing artist accounts — 900 DH\nWithout distribution — 700 DH",
				"Fórmula completa — 1200 DH\nCon cuentas de artista existentes — 900 DH\nSin distribución — 700 DH",
				"الباقة الكاملة — 1200 درهم\nمع حسابات فنان موجودة — 900 درهم\nبدون توزيع — 700 درهم",
			),
			'requirements' => array(
				"La voix avec la musique\nLa voix seule\nL'instrumental seul",
				"Vocals with the music\nVocals only\nInstrumental only",
				"La voz con la música\nSolo la voz\nSolo el instrumental",
				"الصوت مع الموسيقى\nالصوت وحده\nالموسيقى وحدها",
			),
		),
		array(
			'slug' => 'chanson-originale', 'icon' => '✍️', 'price' => '', 'order' => 4,
			'price_label' => array( 'Sur devis', 'Custom quote', 'Presupuesto a medida', 'حسب الطلب' ),
			'title' => array( 'Chanson originale', 'Original Song', 'Canción original', 'أغنية أصلية' ),
			'tagline' => array( 'Un titre sur mesure, qui n\'appartient qu\'à toi', 'A tailor-made track that belongs only to you', 'Un tema a medida que solo te pertenece a ti', 'أغنية مفصّلة على مقاسك، ملكك وحدك' ),
			'content' => array(
				'Paroles, mélodie et production créées pour toi, adaptées à ta voix et à ton style. Idéal pour lancer ta carrière avec un vrai titre original.',
				'Lyrics, melody and production created for you, tailored to your voice and style. Ideal to launch your career with a true original.',
				'Letra, melodía y producción creadas para ti, adaptadas a tu voz y a tu estilo. Ideal para lanzar tu carrera con un tema original.',
				'كلمات ولحن وإنتاج مصمم خصيصاً لك، يناسب صوتك وأسلوبك. مثالي لانطلاق مسيرتك بعمل أصلي حقيقي.',
			),
			'features' => array(
				"Paroles et mélodie sur mesure\nProduction complète\nAdaptée à ta voix et à ton style",
				"Custom lyrics and melody\nFull production\nTailored to your voice and style",
				"Letra y melodía a medida\nProducción completa\nAdaptada a tu voz y estilo",
				"كلمات ولحن حسب الطلب\nإنتاج كامل\nمناسبة لصوتك وأسلوبك",
			),
		),
		array(
			'slug' => 'cover-a-distance', 'old_slug' => 'cover-studio', 'icon' => '🎧', 'price' => '1000 DH', 'order' => 5,
			'price_label' => array( '', '', '', '1000 درهم' ),
			'title' => array( 'Cover à distance', 'Remote Cover', 'Cover a distancia', 'كوفر عن بُعد' ),
			'tagline' => array( 'Tu t\'enregistres chez toi, on fait sonner ta voix comme en studio', 'Record at home, we make your voice sound studio-grade', 'Te grabas en casa, hacemos que tu voz suene como en estudio', 'سجّل من بيتك، ونجعل صوتك يبدو كأنه في الاستوديو' ),
			'content' => array(
				'Enregistre-toi au téléphone en suivant nos conseils, sans te déplacer : nous nous chargeons de la finition professionnelle, du mixage et du mastering.',
				'Record yourself on your phone following our tips, without travelling: we handle the professional finish, mixing and mastering.',
				'Grábate con el móvil siguiendo nuestros consejos, sin desplazarte: nos encargamos del acabado profesional, la mezcla y la masterización.',
				'سجّل بهاتفك باتباع نصائحنا دون أن تتنقل: ونحن نتكفل باللمسة الاحترافية والمكساج والماسترينغ.',
			),
			'features' => array(
				"Enregistrement au téléphone, guidé pas à pas\nNettoyage et traitement de la voix\nFinition production pro : mixage et mastering\n100 % à distance",
				"Phone recording, guided step by step\nVocal cleanup and processing\nPro production finish: mixing and mastering\n100% remote",
				"Grabación con el móvil, guiada paso a paso\nLimpieza y tratamiento de la voz\nAcabado profesional: mezcla y masterización\n100 % a distancia",
				"تسجيل بالهاتف بتوجيه خطوة بخطوة\nتنقية الصوت ومعالجته\nلمسة إنتاج احترافية: مكساج وماسترينغ\n100٪ عن بُعد",
			),
			'requirements' => array(
				"La voix avec la musique\nLa voix seule\nL'instrumental seul",
				"Vocals with the music\nVocals only\nInstrumental only",
				"La voz con la música\nSolo la voz\nSolo el instrumental",
				"الصوت مع الموسيقى\nالصوت وحده\nالموسيقى وحدها",
			),
		),
		array(
			'slug' => 'clip-video', 'icon' => '🎥', 'price' => '600 DH', 'order' => 2,
			'price_label' => array( '600 DH / clip', '600 DH / video', '600 DH / videoclip', '600 درهم / كليب' ),
			'title' => array( 'Clip vidéo', 'Music Video', 'Videoclip', 'فيديو كليب' ),
			'tagline' => array( 'Ton clip, tel que tu l\'imagines', 'Your music video, just as you imagine it', 'Tu videoclip, tal como lo imaginas', 'الكليب ديالك، كما تتخيله تماماً' ),
			'content' => array(
				'Un clip pour ta chanson : montage à partir de tes photos et vidéos ou images d\'ambiance, aux couleurs de ton histoire. Prêt pour YouTube, Reels et TikTok.',
				'A music video for your song: edited from your photos and videos or mood footage, in the colours of your story. Ready for YouTube, Reels and TikTok.',
				'Un videoclip para tu canción: montaje con tus fotos y vídeos o imágenes de ambiente, con los colores de tu historia. Listo para YouTube, Reels y TikTok.',
				'كليب لأغنيتك: مونتاج من صورك وفيديوهاتك أو لقطات معبّرة، بألوان قصتك. جاهز ليوتيوب وريلز وتيك توك.',
			),
			'features' => array(
				"Montage à partir de tes photos, vidéos ou images d'ambiance\nFormat 16:9 ou vertical 9:16\nPrêt pour YouTube, Reels et TikTok\nPrix fixe : 600 DH par clip",
				"Edited from your photos, videos or mood footage\n16:9 or vertical 9:16 format\nReady for YouTube, Reels and TikTok\nFixed price: 600 DH per video",
				"Montaje con tus fotos, vídeos o imágenes de ambiente\nFormato 16:9 o vertical 9:16\nListo para YouTube, Reels y TikTok\nPrecio fijo: 600 DH por videoclip",
				"مونتاج من صورك وفيديوهاتك أو لقطات معبّرة\nصيغة 16:9 أو عمودية 9:16\nجاهز ليوتيوب وريلز وتيك توك\nسعر ثابت: 600 درهم للكليب",
			),
		),
		array(
			'slug' => 'lyrics-video', 'icon' => '🎬', 'price' => '', 'order' => 6,
			'price_label' => array( 'Sur demande', 'On request', 'Bajo pedido', 'عند الطلب' ),
			'title' => array( 'Lyrics video', 'Lyrics Video', 'Lyric video', 'فيديو الكلمات' ),
			'tagline' => array( 'Tes paroles en vidéo, prêtes à publier', 'Your lyrics on video, ready to post', 'Tu letra en vídeo, lista para publicar', 'كلماتك في فيديو، جاهز للنشر' ),
			'content' => array(
				'Une vidéo avec les paroles synchronisées sur ta musique, aux couleurs de ton titre, parfaite pour YouTube, Reels et TikTok.',
				'A video with lyrics synced to your music, styled to match your track, perfect for YouTube, Reels and TikTok.',
				'Un vídeo con la letra sincronizada con tu música, con la estética de tu tema, perfecto para YouTube, Reels y TikTok.',
				'فيديو بكلمات متزامنة مع موسيقاك وبألوان أغنيتك، مثالي ليوتيوب وريلز وتيك توك.',
			),
			'features' => array(
				"Paroles synchronisées sur la musique\nFormat YouTube, Reels, TikTok (16:9 ou 9:16)\nHabillage aux couleurs de ton titre",
				"Lyrics synced to the music\nYouTube, Reels, TikTok formats (16:9 or 9:16)\nDesign that matches your track",
				"Letra sincronizada con la música\nFormato YouTube, Reels, TikTok (16:9 o 9:16)\nDiseño con la estética de tu tema",
				"كلمات متزامنة مع الموسيقى\nصيغ يوتيوب وريلز وتيك توك (16:9 أو 9:16)\nتصميم بألوان أغنيتك",
			),
		),
		array(
			'slug' => 'renouvellement-distribution', 'icon' => '🔁', 'price' => '200 DH', 'order' => 7,
			'price_label' => array( '200 DH / an', '200 DH / year', '200 DH / año', '200 درهم / سنة' ),
			'title' => array( 'Renouvellement distribution', 'Distribution Renewal', 'Renovación de distribución', 'تجديد التوزيع' ),
			'tagline' => array( 'Garde tes titres en ligne, année après année', 'Keep your tracks online, year after year', 'Mantén tus temas en línea, año tras año', 'حافظ على أغانيك منشورة سنة بعد سنة' ),
			'content' => array(
				'Un forfait annuel pour que tes titres restent disponibles sur toutes les plateformes, sans interruption.',
				'A yearly fee so your tracks stay available on every platform, without interruption.',
				'Una cuota anual para que tus temas sigan disponibles en todas las plataformas, sin interrupción.',
				'اشتراك سنوي لتبقى أغانيك متاحة على كل المنصات دون انقطاع.',
			),
			'features' => array(
				"Maintien de tes titres sur toutes les plateformes\nGestion annuelle simplifiée\nAucun frais caché",
				"Your tracks kept on every platform\nSimple yearly management\nNo hidden fees",
				"Tus temas siguen en todas las plataformas\nGestión anual sencilla\nSin costes ocultos",
				"إبقاء أغانيك على كل المنصات\nتسيير سنوي مبسّط\nبدون رسوم خفية",
			),
		),
	);
}
