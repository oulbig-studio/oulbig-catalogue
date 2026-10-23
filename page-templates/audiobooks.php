<?php
/**
 * Template Name: Livres audio & podcasts
 */
get_header();

$cover    = mcl_opt( 'ab_cover' ) ?: MCL_URI . '/assets/demo/audiobook-example.jpg';
// Vrais extraits : épisodes du podcast Étincelle (catalogue OULBIG), dans la langue du visiteur.
$etin     = mcl_catalogue_podcasts( 6, null, 'tincelle' );
if ( ! $etin ) {
	$etin = mcl_catalogue_podcasts( 6, null, 'لمحة' );
}
if ( ! $etin ) {
	$etin = mcl_catalogue_podcasts( 6 );
}
$latest   = mcl_catalogue_podcasts( 4 );
$fallback = MCL_URI . '/assets/demo/demo-melody.mp3';
$sample   = mcl_opt( 'ab_sample' ) ?: ( $etin ? $etin[0]['youtube']['url'] : $fallback );
$psample  = mcl_opt( 'pod_sample' ) ?: ( $latest ? $latest[0]['youtube']['url'] : $fallback );
$s_title  = $etin ? $etin[0]['title'] : mcl_t( 'ab_ex_ch1' );
$p_title  = $latest ? $latest[0]['title'] : mcl_t( 'ab_pod_ex' );
$type    = isset( $_GET['type'] ) && 'podcast' === $_GET['type'] ? 'podcast' : 'audiobook';

wp_enqueue_script( 'mcl-order', MCL_URI . '/assets/js/order.js', array(), MCL_VERSION, true );
wp_localize_script( 'mcl-order', 'MCLO', array(
	'ajax' => admin_url( 'admin-ajax.php' ),
	'i18n' => array(
		'err_required' => mcl_t( 'err_required' ),
		'err_contact'  => mcl_t( 'err_contact' ),
		'err_email'    => mcl_t( 'err_email' ),
		'err_choose'   => mcl_t( 'err_choose' ),
		'err_server'   => mcl_t( 'err_server' ),
		'sending'      => mcl_t( 'sending' ),
	),
) );

$radios = function ( $name, $group, $checked = '', $cls = '' ) {
	echo '<div class="mcl-choices ' . esc_attr( $cls ) . '">';
	foreach ( mcl_options( $group ) as $k => $l ) {
		printf(
			'<label class="mcl-choice"><input type="radio" name="%1$s" value="%2$s" %3$s><span>%4$s</span></label>',
			esc_attr( $name ), esc_attr( $k ), checked( $checked, $k, false ), esc_html( mcl_opt_label( $group, $k ) )
		);
	}
	echo '</div>';
};
?>

<!-- Héros -->
<section class="mcl-ab-hero">
	<div class="mcl-wrap mcl-ab-hero__grid">
		<div>
			<p class="mcl-kicker"><?php echo mcl_icon( 'mic' ); // phpcs:ignore ?><?php mcl_e( 'ab_kicker' ); ?></p>
			<h1 class="mcl-hero__title mcl-ab-hero__title"><?php mcl_e_html( 'ab_title' ); ?></h1>
			<p class="mcl-lead"><?php mcl_e( 'ab_sub' ); ?></p>
			<div class="mcl-hero__cta mcl-ab-hero__cta">
				<a class="mcl-btn mcl-btn--primary mcl-btn--lg" href="#devis"><?php echo mcl_icon( 'pen' ); // phpcs:ignore ?><?php mcl_e( 'ab_cta' ); ?></a>
				<a class="mcl-btn mcl-btn--ghost mcl-btn--lg" href="#exemple"><?php echo mcl_icon( 'play' ); // phpcs:ignore ?><?php mcl_e( 'ab_see_example' ); ?></a>
				<a class="mcl-btn mcl-btn--ghost mcl-btn--lg" href="#podcast"><?php echo mcl_icon( 'mic' ); // phpcs:ignore ?><?php mcl_e( 'ab_cta_pod' ); ?></a>
			</div>
			<p class="mcl-hero__price"><?php mcl_e( 'ab_langs' ); ?></p>
		</div>
		<div class="mcl-ab-visual" aria-hidden="true">
			<div class="mcl-ab-book mcl-ab-book--back"></div>
			<div class="mcl-ab-book">
				<img src="<?php echo esc_url( $cover ); ?>" alt="">
				<?php if ( ! mcl_opt( 'ab_cover' ) ) : ?><span class="mcl-ab-book__title"><?php mcl_e( 'ab_ex_title' ); ?></span><?php endif; ?>
			</div>
			<div class="mcl-ab-wave"><?php for ( $i = 0; $i < 28; $i++ ) { echo '<i style="--i:' . (int) $i . '"></i>'; } ?></div>
		</div>
	</div>
</section>

<!-- Ce que tu peux nous confier -->
<section class="mcl-section mcl-section--tight">
	<div class="mcl-wrap">
		<h2 class="mcl-h2"><?php mcl_e_html( 'ab_from_title' ); ?></h2>
		<div class="mcl-ab-formats">
			<?php foreach ( array( 1 => '📖', 2 => '📚', 3 => '📱', 4 => '🎓' ) as $i => $ico ) : ?>
				<div class="mcl-ab-format">
					<span class="mcl-svc__icon"><?php echo esc_html( $ico ); ?></span>
					<strong><?php mcl_e( 'ab_f' . $i . '_t' ); ?></strong>
					<p><?php mcl_e( 'ab_f' . $i . '_s' ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- Exemple de page de livre audio -->
<section class="mcl-section mcl-section--alt" id="exemple">
	<div class="mcl-wrap">
		<p class="mcl-kicker"><?php mcl_e( 'ab_ex_kicker' ); ?></p>
		<article class="mcl-abx">
			<div class="mcl-abx__cover">
				<img src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( mcl_t( 'ab_ex_title' ) ); ?>">
				<?php if ( ! mcl_opt( 'ab_cover' ) ) : ?><span class="mcl-ab-book__title"><?php mcl_e( 'ab_ex_title' ); ?></span><?php endif; ?>
			</div>
			<div class="mcl-abx__info">
				<span class="mcl-post__cat">🎧 <?php mcl_e( 'nav_audiobooks' ); ?></span>
				<h2 class="mcl-abx__title"><?php mcl_e( 'ab_ex_title' ); ?></h2>
				<p class="mcl-abx__author"><?php mcl_e( 'ab_ex_author' ); ?></p>
				<dl class="mcl-abx__meta">
					<div><dt><?php mcl_e( 'ab_ex_narr' ); ?></dt><dd><?php mcl_e( 'ab_ex_narr_v' ); ?></dd></div>
					<div><dt><?php mcl_e( 'ab_ex_len' ); ?></dt><dd><?php echo esc_html( mcl_t( 'ab_ex_len_v', max( 1, count( $etin ) ) ) ); ?></dd></div>
					<div><dt><?php mcl_e( 'ab_ex_lang' ); ?></dt><dd><?php mcl_e( 'ab_ex_lang_v' ); ?></dd></div>
					<div><dt><?php mcl_e( 'ab_ex_format' ); ?></dt><dd dir="ltr">Livre · Ebook · Audio</dd></div>
				</dl>
				<p class="mcl-abx__synopsis"><?php mcl_e( 'ab_ex_synopsis' ); ?></p>
				<?php echo mcl_media_html( $sample, 'audio', 0, array( 'title' => $s_title, 'artist' => 'OULBIG · Podcast' ) ); // phpcs:ignore ?>
				<h3 class="mcl-abx__chtitle"><?php mcl_e( 'ab_ex_chapters' ); ?></h3>
				<ol class="mcl-abx__chapters">
					<?php if ( $etin ) : ?>
						<?php foreach ( $etin as $ep ) : ?>
							<li><a href="<?php echo esc_url( $ep['youtube']['url'] ); ?>" target="_blank" rel="noopener"<?php echo in_array( $ep['lang'], array( 'ar', 'ma' ), true ) ? ' dir="rtl"' : ''; ?>><?php echo mcl_icon( 'play' ); // phpcs:ignore ?><?php echo esc_html( $ep['title'] ); ?></a><span dir="ltr"><?php echo esc_html( mcl_fmt_dur( $ep['duration'] ) ); ?></span></li>
						<?php endforeach; ?>
					<?php else : ?>
						<?php foreach ( array( 'ab_ex_ch1', 'ab_ex_ch2', 'ab_ex_ch3', 'ab_ex_ch4' ) as $k ) : ?>
							<li><span><?php echo mcl_icon( 'play' ); // phpcs:ignore ?><?php mcl_e( $k ); ?></span></li>
						<?php endforeach; ?>
					<?php endif; ?>
				</ol>
				<p class="mcl-hint mcl-abx__note"><?php mcl_e( 'ab_ex_note' ); ?></p>
			</div>
		</article>
	</div>
</section>

<!-- Ce que nous produisons + étapes -->
<section class="mcl-section">
	<div class="mcl-wrap mcl-how__grid">
		<div>
			<h2 class="mcl-h2"><?php mcl_e_html( 'ab_what_title' ); ?></h2>
			<ul class="mcl-svc__list mcl-svc__list--big">
				<?php for ( $i = 1; $i <= 6; $i++ ) : ?><li><?php echo mcl_icon( 'check' ); // phpcs:ignore ?><?php mcl_e( 'ab_w' . $i ); ?></li><?php endfor; ?>
			</ul>
		</div>
		<ol class="mcl-steps mcl-ab-steps">
			<?php for ( $i = 1; $i <= 4; $i++ ) : ?>
				<li><div><strong><?php mcl_e( 'ab_s' . $i . '_t' ); ?></strong><small><?php mcl_e( 'ab_s' . $i . '_s' ); ?></small></div></li>
			<?php endfor; ?>
		</ol>
	</div>
</section>

<!-- Option podcast -->
<section class="mcl-section mcl-ab-pod" id="podcast">
	<div class="mcl-wrap mcl-ab-pod__grid">
		<div>
			<p class="mcl-kicker">🎙️ Podcast</p>
			<h2 class="mcl-h2"><?php mcl_e_html( 'ab_pod_title' ); ?></h2>
			<p class="mcl-lead"><?php mcl_e( 'ab_pod_sub' ); ?></p>
			<ul class="mcl-svc__list mcl-svc__list--big">
				<?php for ( $i = 1; $i <= 5; $i++ ) : ?><li><?php echo mcl_icon( 'check' ); // phpcs:ignore ?><?php mcl_e( 'ab_p' . $i ); ?></li><?php endfor; ?>
			</ul>
			<a class="mcl-btn mcl-btn--primary" href="#devis" data-ab-type="podcast"><?php echo mcl_icon( 'mic' ); // phpcs:ignore ?><?php mcl_e( 'ab_form_title' ); ?></a>
		</div>
		<div class="mcl-ab-pod__card">
			<div class="mcl-ab-pod__mic" aria-hidden="true"><?php echo mcl_icon( 'mic' ); // phpcs:ignore ?></div>
			<?php echo mcl_media_html( $psample, 'audio', 0, array( 'title' => $p_title, 'artist' => 'OULBIG · Podcast' ) ); // phpcs:ignore ?>
			<?php if ( count( $latest ) > 1 ) : ?>
				<h3 class="mcl-abx__chtitle"><?php mcl_e( 'ab_pod_latest' ); ?></h3>
				<ol class="mcl-abx__chapters">
					<?php foreach ( array_slice( $latest, 1 ) as $ep ) : ?>
						<li><a href="<?php echo esc_url( $ep['youtube']['url'] ); ?>" target="_blank" rel="noopener"<?php echo in_array( $ep['lang'], array( 'ar', 'ma' ), true ) ? ' dir="rtl"' : ''; ?>><?php echo mcl_icon( 'play' ); // phpcs:ignore ?><?php echo esc_html( $ep['title'] ); ?></a><span dir="ltr"><?php echo esc_html( mcl_fmt_dur( $ep['duration'] ) ); ?></span></li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
			<ul class="mcl-platforms__list mcl-ab-pod__plats"><li>Spotify</li><li>Apple Podcasts</li><li>YouTube</li><li>Deezer</li></ul>
		</div>
	</div>
</section>

<!-- Demande de devis -->
<section class="mcl-section" id="devis">
	<div class="mcl-wrap mcl-wrap--narrow">
		<div class="mcl-order mcl-order--static">
			<form class="mcl-order__form" novalidate>
				<h2 class="mcl-card-box__title"><?php mcl_e( 'ab_form_title' ); ?> <span class="mcl-svc__price"><?php mcl_e( 'quote' ); ?></span></h2>
				<p class="mcl-hint"><?php mcl_e( 'ab_form_sub' ); ?></p>
				<input type="hidden" name="action" value="mcl_audio">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'mcl_order' ) ); ?>">
				<input type="hidden" name="lang" value="<?php echo esc_attr( mcl_lang() ); ?>">
				<input type="hidden" name="started" value="<?php echo esc_attr( time() ); ?>">
				<div class="mcl-hp" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

				<div class="mcl-q">
					<p class="mcl-q__label mcl-q__label--caps"><?php mcl_e( 'ab_q_type' ); ?></p>
					<?php $radios( 'type', 'ab_type', $type, 'is-2col mcl-ab-type' ); ?>
				</div>

				<div data-show-for="audiobook">
					<div class="mcl-q">
						<p class="mcl-q__label mcl-q__label--caps"><?php mcl_e( 'ab_q_source' ); ?></p>
						<?php $radios( 'book_source', 'ab_source', 'novel', 'is-2col' ); ?>
					</div>
					<div class="mcl-q">
						<label class="mcl-q__label mcl-q__label--caps" for="ab-size"><?php mcl_e( 'ab_q_size' ); ?></label>
						<input id="ab-size" class="mcl-input" type="text" name="book_size" maxlength="160" placeholder="<?php echo esc_attr( mcl_t( 'ab_size_ph' ) ); ?>">
					</div>
				</div>
				<div data-show-for="podcast" hidden>
					<div class="mcl-q">
						<p class="mcl-q__label mcl-q__label--caps"><?php mcl_e( 'ab_q_pformat' ); ?></p>
						<?php $radios( 'pformat', 'ab_pformat', 'solo', 'is-pills' ); ?>
					</div>
					<div class="mcl-q">
						<label class="mcl-q__label mcl-q__label--caps" for="ab-ep"><?php mcl_e( 'ab_q_episodes' ); ?></label>
						<input id="ab-ep" class="mcl-input" type="text" name="episodes" maxlength="160" placeholder="<?php echo esc_attr( mcl_t( 'ab_episodes_ph' ) ); ?>">
					</div>
				</div>

				<div class="mcl-row2">
					<div class="mcl-q">
						<label class="mcl-q__label mcl-q__label--caps" for="ab-lang"><?php mcl_e( 'ab_q_lang' ); ?></label>
						<select id="ab-lang" class="mcl-input" name="read_lang">
							<?php foreach ( mcl_options( 'song_lang' ) as $k => $l ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( mcl_lang() === $k ); ?>><?php echo esc_html( mcl_opt_label( 'song_lang', $k ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="mcl-q">
						<label class="mcl-q__label mcl-q__label--caps" for="ab-voice"><?php mcl_e( 'ab_q_voice' ); ?></label>
						<select id="ab-voice" class="mcl-input" name="narration">
							<?php foreach ( mcl_options( 'ab_voice' ) as $k => $l ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( mcl_opt_label( 'ab_voice', $k ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<label class="mcl-consent" data-show-for="audiobook"><input type="checkbox" name="music" value="1"><span><?php mcl_e( 'ab_q_music' ); ?></span></label>

				<div class="mcl-q">
					<label class="mcl-q__label mcl-q__label--caps" for="ab-files"><?php mcl_e( 'ab_files' ); ?> <span class="mcl-badge"><?php mcl_e( 'optional' ); ?></span></label>
					<input id="ab-files" class="mcl-input" type="text" name="files" maxlength="1000" dir="ltr" placeholder="<?php echo esc_attr( mcl_t( 'svc_files_ph' ) ); ?>">
				</div>
				<div class="mcl-q">
					<label class="mcl-q__label mcl-q__label--caps" for="ab-details"><?php mcl_e( 'svc_details' ); ?></label>
					<textarea id="ab-details" name="details" rows="4" maxlength="4000" placeholder="<?php echo esc_attr( mcl_t( 'svc_details_ph' ) ); ?>"></textarea>
				</div>
				<div class="mcl-q" data-req="name">
					<label class="mcl-q__label mcl-q__label--caps" for="ab-name"><?php mcl_e( 'f_name' ); ?></label>
					<input id="ab-name" class="mcl-input" type="text" name="name" maxlength="120" autocomplete="name">
				</div>
				<div class="mcl-row2">
					<div class="mcl-q">
						<label class="mcl-q__label mcl-q__label--caps" for="ab-phone"><?php mcl_e( 'f_phone' ); ?></label>
						<input id="ab-phone" class="mcl-input" type="tel" name="phone" maxlength="40" dir="ltr" autocomplete="tel" placeholder="06 00 00 00 00">
					</div>
					<div class="mcl-q">
						<label class="mcl-q__label mcl-q__label--caps" for="ab-email"><?php mcl_e( 'f_email' ); ?></label>
						<input id="ab-email" class="mcl-input" type="email" name="email" maxlength="160" dir="ltr" autocomplete="email" placeholder="vous@exemple.com">
					</div>
				</div>
				<p class="mcl-wizard__error" role="alert" hidden></p>
				<button type="submit" class="mcl-btn mcl-btn--primary mcl-btn--lg mcl-order__submit"><span><?php mcl_e( 'ab_send' ); ?></span><?php echo mcl_icon( 'arrow' ); // phpcs:ignore ?></button>
				<?php if ( mcl_whatsapp_url() ) : ?>
					<p class="mcl-order__or"><span><?php mcl_e( 'svc_or' ); ?></span></p>
					<a class="mcl-btn mcl-btn--wa" href="<?php echo esc_url( mcl_whatsapp_url( sprintf( mcl_t( 'wa_service_msg' ), mcl_t( 'ab_kicker' ) ) ) ); ?>" target="_blank" rel="noopener"><?php echo mcl_icon( 'whatsapp' ); // phpcs:ignore ?>WhatsApp</a>
				<?php endif; ?>
			</form>
			<div class="mcl-order__thanks" hidden>
				<div class="mcl-thanks__icon"><?php echo mcl_icon( 'check' ); // phpcs:ignore ?></div>
				<h2 class="mcl-step__title mcl-step__title--center"><?php mcl_e_html( 'ab_thanks' ); ?></h2>
				<p class="mcl-step__sub mcl-step__sub--center"><?php mcl_e( 'thanks_text' ); ?> <strong class="mcl-thanks__ref"></strong></p>
			</div>
		</div>
	</div>
</section>

<?php
get_footer();
