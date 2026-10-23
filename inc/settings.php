<?php
/**
 * Réglages Musicology (email de réception, téléphone, WhatsApp, vidéo pub…).
 * Menu : Demandes musique → Réglages.
 */
defined( 'ABSPATH' ) || exit;

function mcl_defaults() {
	return array(
		'email_to'      => 'musicology4242@gmail.com',
		'contact_email' => 'musicology4242@gmail.com',
		'phone'         => '+212 661 42 62 03',
		'whatsapp'      => '212661426203',
		'phone2'        => '+212 661 69 20 67',
		'whatsapp2'     => '212661692067',
		'price_song'    => 199,
		'price_old'     => 349,
		'price_clip'    => 600,
		'price_express' => 79,
		'express_hours' => 24,
		'delivery_days' => 4,
		'ab_sample'     => '',
		'ab_cover'      => '',
		'pod_sample'    => '',
		'location'      => '',
		'promo_video'   => '',
		'default_lang'  => 'fr',
		'confirm_mail'  => 1,
		'instagram'     => '',
		'tiktok'        => '',
		'youtube'       => '',
	);
}

function mcl_opt( $key ) {
	$opts = wp_parse_args( (array) get_option( 'mcl_settings', array() ), mcl_defaults() );
	return isset( $opts[ $key ] ) ? $opts[ $key ] : '';
}

function mcl_whatsapp_url( $text = '', $key = 'whatsapp' ) {
	$num = preg_replace( '/\D+/', '', (string) mcl_opt( $key ) );
	if ( ! $num ) {
		return '';
	}
	return 'https://wa.me/' . $num . ( $text ? '?text=' . rawurlencode( $text ) : '' );
}

add_action( 'admin_init', function () {
	register_setting( 'mcl_settings', 'mcl_settings', array(
		'type'              => 'array',
		'sanitize_callback' => function ( $in ) {
			$d   = mcl_defaults();
			$out = array();
			$out['email_to']      = sanitize_text_field( $in['email_to'] ?? $d['email_to'] );
			$out['contact_email'] = sanitize_email( $in['contact_email'] ?? $d['contact_email'] );
			$out['phone']         = sanitize_text_field( $in['phone'] ?? '' );
			$out['whatsapp']      = preg_replace( '/\D+/', '', $in['whatsapp'] ?? '' );
			$out['phone2']        = sanitize_text_field( $in['phone2'] ?? '' );
			$out['whatsapp2']     = preg_replace( '/\D+/', '', $in['whatsapp2'] ?? '' );
			foreach ( array( 'price_song', 'price_old', 'price_clip', 'price_express', 'express_hours', 'delivery_days' ) as $k ) {
				$out[ $k ] = isset( $in[ $k ] ) && '' !== $in[ $k ] ? absint( $in[ $k ] ) : '';
			}
			$out['location']      = sanitize_text_field( $in['location'] ?? '' );
			$out['promo_video']   = esc_url_raw( $in['promo_video'] ?? '' );
			$out['ab_sample']     = esc_url_raw( $in['ab_sample'] ?? '' );
			$out['ab_cover']      = esc_url_raw( $in['ab_cover'] ?? '' );
			$out['pod_sample']    = esc_url_raw( $in['pod_sample'] ?? '' );
			$out['default_lang']  = mcl_valid_lang( $in['default_lang'] ?? '' ) ? $in['default_lang'] : 'fr';
			$out['confirm_mail']  = empty( $in['confirm_mail'] ) ? 0 : 1;
			foreach ( array( 'instagram', 'tiktok', 'youtube' ) as $k ) {
				$out[ $k ] = esc_url_raw( $in[ $k ] ?? '' );
			}
			return $out;
		},
	) );
} );

add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=mcl_request',
		'Réglages Musicology',
		'Réglages',
		'manage_options',
		'mcl-settings',
		'mcl_settings_page'
	);
}, 20 );

function mcl_settings_page() {
	$o = wp_parse_args( (array) get_option( 'mcl_settings', array() ), mcl_defaults() );
	?>
	<div class="wrap mcl-admin">
		<h1>Réglages Musicology</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'mcl_settings' ); ?>
			<h2>Réception des demandes</h2>
			<table class="form-table" role="presentation">
				<tr><th><label for="mcl_email_to">Email(s) de réception</label></th>
					<td><input type="text" class="regular-text" id="mcl_email_to" name="mcl_settings[email_to]" value="<?php echo esc_attr( $o['email_to'] ); ?>">
					<p class="description">Chaque demande est envoyée ici <strong>et</strong> enregistrée dans « Demandes musique ». Plusieurs adresses : séparez-les par des virgules.</p></td></tr>
				<tr><th>Email de confirmation</th>
					<td><label><input type="checkbox" name="mcl_settings[confirm_mail]" value="1" <?php checked( $o['confirm_mail'], 1 ); ?>> Envoyer un accusé de réception au demandeur (dans sa langue)</label></td></tr>
			</table>
			<h2>Coordonnées affichées sur le site</h2>
			<table class="form-table" role="presentation">
				<tr><th><label>Email de contact</label></th><td><input type="email" class="regular-text" name="mcl_settings[contact_email]" value="<?php echo esc_attr( $o['contact_email'] ); ?>"></td></tr>
				<tr><th><label>Téléphone affiché</label></th><td><input type="text" class="regular-text" name="mcl_settings[phone]" value="<?php echo esc_attr( $o['phone'] ); ?>"></td></tr>
				<tr><th><label>Numéro WhatsApp</label></th><td><input type="text" class="regular-text" name="mcl_settings[whatsapp]" value="<?php echo esc_attr( $o['whatsapp'] ); ?>"><p class="description">Format international sans + ni espaces (ex. 212661426203).</p></td></tr>
				<tr><th><label>2ᵉ téléphone affiché</label></th><td><input type="text" class="regular-text" name="mcl_settings[phone2]" value="<?php echo esc_attr( $o['phone2'] ); ?>"></td></tr>
				<tr><th><label>2ᵉ numéro WhatsApp</label></th><td><input type="text" class="regular-text" name="mcl_settings[whatsapp2]" value="<?php echo esc_attr( $o['whatsapp2'] ); ?>"><p class="description">Vide = un seul numéro affiché.</p></td></tr>
				<tr><th><label>Adresse / lieu (📍)</label></th><td><input type="text" class="regular-text" name="mcl_settings[location]" value="<?php echo esc_attr( $o['location'] ); ?>"></td></tr>
				<tr><th><label>Instagram</label></th><td><input type="url" class="regular-text" name="mcl_settings[instagram]" value="<?php echo esc_attr( $o['instagram'] ); ?>"></td></tr>
				<tr><th><label>TikTok</label></th><td><input type="url" class="regular-text" name="mcl_settings[tiktok]" value="<?php echo esc_attr( $o['tiktok'] ); ?>"></td></tr>
				<tr><th><label>YouTube</label></th><td><input type="url" class="regular-text" name="mcl_settings[youtube]" value="<?php echo esc_attr( $o['youtube'] ); ?>"></td></tr>
			</table>
			<h2>Tarifs de la chanson personnalisée (en DH)</h2>
			<table class="form-table" role="presentation">
				<tr><th><label>Prix de la chanson</label></th><td><input type="number" min="0" name="mcl_settings[price_song]" value="<?php echo esc_attr( $o['price_song'] ); ?>"> DH</td></tr>
				<tr><th><label>Ancien prix barré</label></th><td><input type="number" min="0" name="mcl_settings[price_old]" value="<?php echo esc_attr( $o['price_old'] ); ?>"> DH <span class="description">(vide = pas de prix barré)</span></td></tr>
				<tr><th><label>Clip vidéo</label></th><td><input type="number" min="0" name="mcl_settings[price_clip]" value="<?php echo esc_attr( $o['price_clip'] ); ?>"> DH / clip</td></tr>
				<tr><th><label>Livraison express</label></th><td><input type="number" min="0" name="mcl_settings[price_express]" value="<?php echo esc_attr( $o['price_express'] ); ?>"> DH, livrée en <input type="number" min="1" style="width:70px" name="mcl_settings[express_hours]" value="<?php echo esc_attr( $o['express_hours'] ); ?>"> h</td></tr>
				<tr><th><label>Délai standard</label></th><td><input type="number" min="1" style="width:70px" name="mcl_settings[delivery_days]" value="<?php echo esc_attr( $o['delivery_days'] ); ?>"> jours</td></tr>
			</table>
			<h2>Livres audio & podcasts</h2>
			<table class="form-table" role="presentation">
				<tr><th><label>Extrait de livre audio (exemple)</label></th><td><input type="url" class="large-text" name="mcl_settings[ab_sample]" value="<?php echo esc_attr( $o['ab_sample'] ); ?>" placeholder="Lien MP3, SoundCloud, YouTube…"><p class="description">Vide = dernier épisode du podcast Étincelle (catalogue OULBIG synchronisé).</p></td></tr>
				<tr><th><label>Couverture du livre (exemple)</label></th><td><input type="url" class="large-text" name="mcl_settings[ab_cover]" value="<?php echo esc_attr( $o['ab_cover'] ); ?>" placeholder="Lien de la vraie couverture de « Introduction : 37 étapes + 1 »"><p class="description">Collez l'adresse de l'image de couverture (Médias > ajouter > copier l'URL). Vide = illustration Musicology.</p></td></tr>
				<tr><th><label>Extrait de podcast (exemple)</label></th><td><input type="url" class="large-text" name="mcl_settings[pod_sample]" value="<?php echo esc_attr( $o['pod_sample'] ); ?>"><p class="description">Vide = dernier épisode OULBIG dans la langue du visiteur.</p></td></tr>
			</table>
			<h2>Accueil</h2>
			<table class="form-table" role="presentation">
				<tr><th><label>Vidéo pub « La chanson de tes rêves »</label></th><td><input type="url" class="large-text" name="mcl_settings[promo_video]" value="<?php echo esc_attr( $o['promo_video'] ); ?>" placeholder="https://youtube.com/shorts/… ou lien .mp4"><p class="description">YouTube (Shorts compris), Vimeo, TikTok, ou fichier MP4. Vide = section masquée.</p></td></tr>
				<tr><th><label>Langue par défaut</label></th><td><select name="mcl_settings[default_lang]">
					<?php foreach ( mcl_langs() as $c => $l ) : ?>
						<option value="<?php echo esc_attr( $c ); ?>" <?php selected( $o['default_lang'], $c ); ?>><?php echo esc_html( $l['label'] ); ?></option>
					<?php endforeach; ?>
				</select></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<h2>Pages de demande par langue</h2>
		<ul>
		<?php foreach ( mcl_langs() as $c => $l ) : ?>
			<li><strong><?php echo esc_html( $l['label'] ); ?></strong> : <a href="<?php echo esc_url( mcl_request_url( $c ) ); ?>" target="_blank"><?php echo esc_html( mcl_request_url( $c ) ); ?></a></li>
		<?php endforeach; ?>
		</ul>
		<p class="description">Astuce : si les emails n'arrivent pas, installez une extension SMTP (ex. « WP Mail SMTP ») — les demandes restent de toute façon enregistrées ici.</p>
	</div>
	<?php
}

/** Prix (int) depuis les réglages. */
function mcl_price( $key ) {
	$v = mcl_opt( $key );
	return '' === $v ? 0 : (int) $v;
}
function mcl_dh( $n ) {
	// Isolé (LRI…PDI) pour rester lisible « 199 DH » dans une phrase en arabe.
	return "\u{2066}" . number_format( (int) $n, 0, ',', ' ' ) . " DH\u{2069}";
}
