<?php
/** Articles de départ du blog (supprimables comme les autres démos). */
defined( 'ABSPATH' ) || exit;

function mcl_blog_seed() {
	return array(
		array(
			'lang' => 'fr', 'img' => 1, 'cat' => 'Conseils', 'days' => 2,
			'title' => 'Enregistrer sa voix au téléphone : le guide pour une prise propre à la maison',
			'excerpt' => 'Pas besoin d\'un studio pour commencer : un téléphone récent, une pièce calme et quelques réflexes suffisent pour une voix exploitable en production.',
			'content' => '<p>Beaucoup d\'artistes attendent « le bon studio » pour enregistrer leurs idées. Résultat : les chansons restent dans un carnet. La vérité, c\'est qu\'un téléphone récent capte aujourd\'hui une voix tout à fait exploitable, à condition de respecter quelques règles simples.</p>
<h2>1. Choisis la bonne pièce</h2>
<p>L\'ennemi numéro un, ce n\'est pas le micro, c\'est l\'écho. Évite la cuisine et la salle de bain. Une chambre avec des rideaux, un lit et une armoire pleine de vêtements absorbe bien le son. Astuce : enregistre face à l\'armoire ouverte.</p>
<h2>2. Prépare ton téléphone</h2>
<ul>
<li>Active le mode avion pour éviter les notifications et les interférences.</li>
<li>Utilise une application d\'enregistrement en qualité maximale (WAV si possible).</li>
<li>Pose le téléphone sur un support stable, à 15–20 cm de ta bouche, micro orienté vers toi.</li>
</ul>
<h2>3. Écoute la musique au casque</h2>
<p>Le téléphone ne doit enregistrer que ta voix. Écoute l\'instrumental avec des écouteurs (filaires de préférence, pour éviter la latence du Bluetooth) sur un second appareil.</p>
<h2>4. Fais plusieurs prises</h2>
<p>Enregistre la chanson en entier deux ou trois fois, puis les passages difficiles séparément. Garde une petite marge de silence au début et à la fin de chaque prise.</p>
<h2>5. Envoie les bons fichiers</h2>
<p>Pour une production complète, nous avons besoin de trois fichiers séparés : <strong>la voix avec la musique</strong>, <strong>la voix seule</strong> et <strong>l\'instrumental seul</strong>. Envoie-les via Google Drive ou WeTransfer, sans compression WhatsApp.</p>
<p>La régularité fait progresser plus vite que le matériel : enregistre tes idées dès qu\'elles arrivent. On s\'occupe ensuite de l\'arrangement, du mixage et du mastering.</p>',
		),
		array(
			'lang' => 'fr', 'img' => 3, 'cat' => 'Distribution', 'days' => 5,
			'title' => 'Publier sa chanson sur Spotify, Apple Music et Deezer : ce qu\'il faut savoir',
			'excerpt' => 'Comptes artiste, délais, droits, renouvellement : tout ce qu\'il faut préparer avant de sortir un titre sur les plateformes de streaming.',
			'content' => '<p>Sortir un titre sur les plateformes de streaming n\'a jamais été aussi accessible. Mais pour que tout se passe bien, mieux vaut préparer quelques éléments à l\'avance.</p>
<h2>Ce dont tu as besoin</h2>
<ul>
<li><strong>Le fichier audio final</strong>, mixé et masterisé (WAV de préférence).</li>
<li><strong>Une pochette carrée</strong> en haute définition (3000 × 3000 px), sans logo de plateforme ni texte flou.</li>
<li><strong>Les informations du titre</strong> : nom d\'artiste, titre exact, compositeurs, langue, présence de paroles explicites.</li>
</ul>
<h2>Ton compte artiste t\'appartient</h2>
<p>Quand nous créons tes comptes artiste, tu en restes l\'administrateur. Tu gardes la main sur ton profil, ta photo, ta biographie et tes statistiques.</p>
<h2>Les délais</h2>
<p>Compte en général quelques jours à deux semaines avant la mise en ligne sur l\'ensemble des plateformes. Pour préparer ta promotion, prévois ta date de sortie un peu à l\'avance.</p>
<h2>Distribution ne veut pas dire écoutes</h2>
<p>Être présent sur Spotify ou Apple Music ne garantit ni écoutes ni revenus : c\'est ta promotion (réseaux sociaux, clip, lyrics video, concerts) qui fera découvrir ton titre.</p>
<h2>Penser au renouvellement</h2>
<p>Pour garder tes titres disponibles, la distribution se renouvelle chaque année. C\'est un petit montant fixe, sans frais cachés.</p>',
		),
		array(
			'lang' => 'fr', 'img' => 4, 'cat' => 'Distribution', 'days' => 9,
			'title' => 'Publier un cover : pourquoi la licence change tout',
			'excerpt' => 'Reprendre une chanson connue est un excellent moyen de se faire connaître, à condition de le faire dans les règles.',
			'content' => '<p>Le cover est souvent la première porte d\'entrée d\'un artiste : le public connaît déjà la chanson et découvre ta voix. Mais une chanson appartient à ses auteurs et compositeurs. Pour la publier sur les plateformes, il faut une <strong>licence de cover</strong>.</p>
<h2>Ce que la licence permet</h2>
<p>Elle t\'autorise à distribuer ta version enregistrée d\'une chanson existante, tout en reversant aux auteurs la part qui leur revient. Ta version reste la tienne, la composition reste la leur.</p>
<h2>Ce qu\'elle ne permet pas</h2>
<ul>
<li>Modifier les paroles ou le sens de la chanson sans accord.</li>
<li>Utiliser l\'instrumental original sans autorisation : il faut une nouvelle production.</li>
</ul>
<h2>La solution simple</h2>
<p>Avec le Pack Cover, la licence est incluse et nous nous occupons de la distribution. Avec le Cover complet, tu n\'as qu\'à chanter : arrangement, mixage, mastering, autotune, licence et mise en ligne sont pris en charge.</p>',
		),
		array(
			'lang' => 'fr', 'img' => 5, 'cat' => 'Chansons sur mesure', 'days' => 12,
			'title' => 'Offrir une chanson personnalisée : 7 occasions qui marquent pour toujours',
			'excerpt' => 'Anniversaire, mariage, demande en mariage, fête des mères… Une chanson écrite pour quelqu\'un reste un cadeau qu\'on n\'oublie jamais.',
			'content' => '<p>Les fleurs fanent, les parfums s\'épuisent. Une chanson, elle, se réécoute pendant des années. Voici les occasions où elle fait le plus d\'effet.</p>
<h2>1. L\'anniversaire</h2><p>Le prénom chanté dans le refrain, les souvenirs dans les couplets : effet garanti au moment du gâteau.</p>
<h2>2. Le mariage</h2><p>Pour l\'entrée des mariés ou la première danse, une chanson qui raconte votre histoire.</p>
<h2>3. La demande en mariage</h2><p>Une déclaration en musique, avec ou sans clip, pour un « oui » inoubliable.</p>
<h2>4. La fête des mères</h2><p>Dire merci en chanson, en darija, en arabe, en français ou dans la langue de son cœur.</p>
<h2>5. L\'anniversaire de couple</h2><p>Dix, vingt, trente ans : chaque étape mérite sa mélodie.</p>
<h2>6. Une réussite</h2><p>Un diplôme, un nouveau travail, un projet accompli : célébrer en musique.</p>
<h2>7. Un hommage</h2><p>Pour honorer une personne chère, avec douceur et respect.</p>
<p>Il suffit de nous raconter ton histoire, de donner les prénoms, la langue et le style (rap, pop, raï, chaabi, R&B, oriental…). On compose la chanson de tes rêves, et même le clip si tu le souhaites.</p>',
		),
		array(
			'lang' => 'ar', 'img' => 6, 'cat' => 'Conseils', 'days' => 3, 'slug' => 'tasjil-sawt-bil-hatif',
			'title' => 'كيف تسجّل صوتك بالهاتف في المنزل وتحصل على أغنية احترافية',
			'excerpt' => 'لا تحتاج إلى استوديو باهظ لتبدأ: هاتف حديث وغرفة هادئة وبعض العادات البسيطة تكفي لتسجيل صوت صالح للإنتاج.',
			'content' => '<p>كثير من الفنانين ينتظرون «الاستوديو المثالي» لتسجيل أفكارهم، فتبقى الأغاني حبيسة الدفتر. الحقيقة أن الهاتف الحديث اليوم قادر على التقاط صوت جيد جداً، بشرط احترام بعض القواعد البسيطة.</p>
<h2>١. اختر الغرفة المناسبة</h2>
<p>العدو الأول هو الصدى وليس الميكروفون. تجنّب المطبخ والحمام، واختر غرفة فيها ستائر وسرير وخزانة ملابس. نصيحة: سجّل وأنت مقابل خزانة مفتوحة مليئة بالملابس.</p>
<h2>٢. جهّز هاتفك</h2>
<ul>
<li>فعّل وضع الطيران لتجنّب الإشعارات والتشويش.</li>
<li>استعمل تطبيق تسجيل بأعلى جودة ممكنة.</li>
<li>ضع الهاتف على حامل ثابت على بعد ١٥ إلى ٢٠ سنتيمتراً من فمك.</li>
</ul>
<h2>٣. استمع إلى الموسيقى بالسماعات</h2>
<p>يجب أن يسجّل الهاتف صوتك فقط. استمع إلى الموسيقى بسماعات سلكية من جهاز آخر.</p>
<h2>٤. سجّل عدة مرات</h2>
<p>سجّل الأغنية كاملة مرتين أو ثلاثاً، ثم المقاطع الصعبة على حدة.</p>
<h2>٥. أرسل الملفات الصحيحة</h2>
<p>للإنتاج الكامل نحتاج إلى ثلاثة ملفات منفصلة: <strong>الصوت مع الموسيقى</strong>، <strong>الصوت وحده</strong>، و<strong>الموسيقى وحدها</strong>. أرسلها عبر Google Drive أو WeTransfer وليس عبر واتساب حتى لا تفقد جودتها.</p>
<p>السرعة والانتظام في الإنتاج هما ما يجعلك تتطور. سجّل أفكارك فور ولادتها، ونحن نتكفل بالتوزيع والمكساج والماسترينغ.</p>',
		),
		array(
			'lang' => 'en', 'img' => 2, 'cat' => 'Chansons sur mesure', 'days' => 7,
			'title' => 'How a personalised song is made, step by step',
			'excerpt' => 'From your story to the final master — and even the music video — here is what happens behind the scenes.',
			'content' => '<p>A personalised song is one of the few gifts that gets better with time. Here is how we turn your story into music.</p>
<h2>1. You tell us the story</h2>
<p>Through our request form, you share who the song is for, the names to celebrate, the occasion, the language and the style you love — rap, pop, raï, chaabi, R&amp;B, oriental and more. You can also send your own lyrics.</p>
<h2>2. We write the lyrics</h2>
<p>We turn your memories into verses and a chorus that is easy to sing along to, with the names placed where they shine.</p>
<h2>3. Composition and recording</h2>
<p>Melody, arrangement and vocals are recorded with the voice type you chose — female, male or duet.</p>
<h2>4. Mixing and mastering</h2>
<p>The track is polished to studio quality, ready to be shared on WhatsApp, social media or streaming platforms.</p>
<h2>5. The music video (optional)</h2>
<p>If you want, we create the video too, just as you imagine it: your photos, your colours, your moments.</p>
<p>Your story deserves to be sung.</p>',
		),
		array(
			'lang' => 'es', 'img' => 7, 'cat' => 'Chansons sur mesure', 'days' => 10,
			'title' => 'Regalar una canción: la sorpresa que nadie olvida',
			'excerpt' => 'Una canción escrita para alguien es un regalo que se escucha una y otra vez. Así se prepara.',
			'content' => '<p>¿Buscas un regalo diferente para un cumpleaños, una boda o una declaración de amor? Una canción personalizada emociona como pocas cosas.</p>
<h2>Qué necesitamos de ti</h2>
<ul>
<li>Tu historia o tu letra.</li>
<li>Los nombres que quieres celebrar.</li>
<li>El idioma: español, francés, árabe, darija, inglés…</li>
<li>El estilo: pop, rap, R&amp;B, raï, oriental…</li>
</ul>
<h2>Qué recibes</h2>
<p>Una canción con calidad de estudio, con letra escrita a partir de tus respuestas y, si lo deseas, el videoclip tal como lo imaginas.</p>
<p>Tu historia merece ser cantada.</p>',
		),
	);
}
