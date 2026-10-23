<?php
/**
 * Template Name: Demande de musique spéciale
 * Une page par langue (méta _mcl_lang). Formulaire en 6 étapes + vérification.
 */
get_header();

$lang  = mcl_lang();
$pre   = isset( $_GET['occasion'] ) ? sanitize_key( $_GET['occasion'] ) : '';
$phone = mcl_opt( 'phone' );
$wa    = mcl_whatsapp_url();

wp_enqueue_script( 'mcl-request', MCL_URI . '/assets/js/request.js', array(), MCL_VERSION, true );
wp_localize_script( 'mcl-request', 'MCLR', array(
	'ajax'  => admin_url( 'admin-ajax.php' ),
	'lang'  => $lang,
	'steps' => 6,
	'price' => array(
		'song'    => mcl_price( 'price_song' ),
		'old'     => mcl_price( 'price_old' ),
		'clip'    => mcl_price( 'price_clip' ),
		'express' => mcl_price( 'price_express' ),
	),
	'i18n'  => array(
		'step_of'      => mcl_t( 'step_of' ),
		'complete'     => mcl_t( 'complete' ),
		'last_step'    => mcl_t( 'last_step' ),
		'err_required' => mcl_t( 'err_required' ),
		'err_choose'   => mcl_t( 'err_choose' ),
		'err_email'    => mcl_t( 'err_email' ),
		'err_contact'  => mcl_t( 'err_contact' ),
		'err_story'    => mcl_t( 'err_story' ),
		'err_photo'    => mcl_t( 'err_photo' ),
		'err_server'   => mcl_t( 'err_server' ),
		'sending'      => mcl_t( 'sending' ),
		'this_person'  => mcl_t( 'this_person' ),
		'next'         => mcl_t( 'next' ),
		'finish'       => mcl_t( 'finish' ),
		'submit'       => mcl_t( 'submit' ),
	),
) );

/** Rendu d'un groupe de choix. */
$chips = function ( $name, $group, $type = 'radio', $cols = '', $checked = '' ) {
	echo '<div class="mcl-choices ' . esc_attr( $cols ) . '">';
	$first = true;
	foreach ( mcl_options( $group ) as $k => $labels ) {
		printf(
			'<label class="mcl-choice"><input type="%1$s" name="%2$s" value="%3$s" %4$s><span>%5$s%6$s</span></label>',
			esc_attr( $type ),
			esc_attr( 'checkbox' === $type ? $name . '[]' : $name ),
			esc_attr( $k ),
			checked( $checked, $k, false ),
			esc_html( mcl_opt_label( $group, $k ) ),
			''
		);
		$first = false;
	}
	echo '</div>';
};
$opt_badge = '<span class="mcl-badge">' . esc_html( mcl_t( 'optional' ) ) . '</span>';
?>
<section class="mcl-req">
	<div class="mcl-req__glow" aria-hidden="true"></div>
	<div class="mcl-req__wrap">

		<?php $p_song = mcl_price( 'price_song' ); $p_old = mcl_price( 'price_old' ); ?>
		<p class="mcl-promo-bar">
			<span>🎁 <?php echo esc_html( $p_old > $p_song ? mcl_t( 'promo_bar', mcl_dh( $p_song ), mcl_dh( $p_old ) ) : mcl_t( 'promo_price', mcl_dh( $p_song ) ) ); ?></span>
			<span><?php echo esc_html( mcl_t( 'delivery', mcl_price( 'delivery_days' ) ) ); ?></span>
			<span>🎥 <?php echo esc_html( mcl_t( 'sum_clip' ) . ' ' . mcl_t( 'per_clip', mcl_dh( mcl_price( 'price_clip' ) ) ) ); ?></span>
		</p>
		<header class="mcl-req__intro">
			<p class="mcl-kicker"><?php mcl_e( 'req_kicker' ); ?></p>
			<p class="mcl-req__lead"><?php mcl_e( 'req_intro' ); ?></p>
		</header>

		<form id="mcl-wizard" class="mcl-wizard" novalidate enctype="multipart/form-data">
			<input type="hidden" name="action" value="mcl_request">
			<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'mcl_request' ) ); ?>">
			<input type="hidden" name="lang" value="<?php echo esc_attr( $lang ); ?>">
			<input type="hidden" name="started" value="<?php echo esc_attr( time() ); ?>">
			<div class="mcl-hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

			<div class="mcl-progress">
				<div class="mcl-progress__labels"><span class="mcl-progress__step"></span><span class="mcl-progress__pct"></span></div>
				<div class="mcl-progress__bar"><i></i><b></b></div>
			</div>

			<!-- Étape 1 -->
			<section class="mcl-step is-active" data-step="1">
				<p class="mcl-private"><?php echo mcl_icon( 'lock' ); // phpcs:ignore ?><?php mcl_e( 'private' ); ?></p>
				<h1 class="mcl-step__title"><?php mcl_e_html( 's1_title' ); ?></h1>
				<p class="mcl-step__sub"><?php mcl_e( 's1_sub' ); ?></p>
				<div class="mcl-q" data-required="relation">
					<h2 class="mcl-q__label"><?php mcl_e( 'q_for' ); ?></h2>
					<?php $chips( 'relation', 'relation', 'radio', 'is-2col' ); ?>
				</div>
				<div class="mcl-q" data-required="occasion">
					<h2 class="mcl-q__label"><?php mcl_e( 'q_occasion' ); ?></h2>
					<?php $chips( 'occasion', 'occasion', 'radio', 'is-pills', $pre ); ?>
				</div>
			</section>

			<!-- Étape 2 -->
			<section class="mcl-step" data-step="2">
				<h1 class="mcl-step__title"><?php mcl_e_html( 's2_title' ); ?></h1>
				<p class="mcl-step__sub"><?php mcl_e( 's2_sub' ); ?></p>
				<div class="mcl-q" data-required="recipient">
					<label class="mcl-q__label mcl-q__label--caps" for="mcl-recipient"><?php mcl_e( 'f_first' ); ?></label>
					<div class="mcl-input-big">
						<input id="mcl-recipient" type="text" name="recipient" maxlength="120" autocomplete="off" placeholder="<?php echo esc_attr( mcl_t( 'f_first_ph' ) ); ?>">
						<span class="mcl-input-big__ok"><?php echo mcl_icon( 'check' ); // phpcs:ignore ?></span>
					</div>
				</div>
				<details class="mcl-more">
					<summary><?php echo mcl_icon( 'plus' ); // phpcs:ignore ?><?php mcl_e( 'f_pron' ); ?> <?php echo $opt_badge; // phpcs:ignore ?></summary>
					<input type="text" name="pronunciation" maxlength="160" placeholder="<?php echo esc_attr( mcl_t( 'f_pron_ph' ) ); ?>">
				</details>
				<div class="mcl-q">
					<label class="mcl-q__label mcl-q__label--caps" for="mcl-date"><?php mcl_e( 'f_date' ); ?> <?php echo $opt_badge; // phpcs:ignore ?></label>
					<input id="mcl-date" type="date" name="occasion_date" class="mcl-input">
				</div>
			</section>

			<!-- Étape 3 -->
			<section class="mcl-step" data-step="3">
				<h1 class="mcl-step__title"><?php mcl_e_html( 's3_title', esc_html( mcl_t( 'this_person' ) ) ); ?></h1>
				<p class="mcl-step__sub"><?php mcl_e( 's3_sub' ); ?></p>
				<div class="mcl-q" data-required="song_lang">
					<h2 class="mcl-q__label"><?php echo mcl_icon( 'globe' ); // phpcs:ignore ?><?php mcl_e( 'q_song_lang' ); ?></h2>
					<?php $chips( 'song_lang', 'song_lang', 'radio', 'is-pills' ); ?>
				</div>
				<div class="mcl-q" data-required="genre">
					<h2 class="mcl-q__label"><?php echo mcl_icon( 'audio' ); // phpcs:ignore ?><?php mcl_e( 'q_genre' ); ?></h2>
					<?php $chips( 'genre', 'genre', 'radio', 'is-2col' ); ?>
				</div>
				<div class="mcl-q">
					<h2 class="mcl-q__label mcl-q__label--serif"><?php mcl_e( 'q_voice' ); ?> <?php echo $opt_badge; // phpcs:ignore ?></h2>
					<p class="mcl-hint"><?php mcl_e( 'voice_hint' ); ?></p>
					<?php $chips( 'voice', 'voice', 'radio', 'is-2col' ); ?>
				</div>
			</section>

			<!-- Étape 4 -->
			<section class="mcl-step" data-step="4">
				<h1 class="mcl-step__title"><?php mcl_e_html( 's4_title', esc_html( mcl_t( 'this_person' ) ) ); ?></h1>
				<p class="mcl-step__sub"><?php mcl_e( 's4_sub' ); ?></p>
				<div class="mcl-q">
					<?php $chips( 'qualities', 'quality', 'checkbox', 'is-3col' ); ?>
				</div>
				<details class="mcl-more">
					<summary><?php echo mcl_icon( 'plus' ); // phpcs:ignore ?><?php mcl_e( 'f_own_words' ); ?> <?php echo $opt_badge; // phpcs:ignore ?></summary>
					<input type="text" name="own_words" maxlength="300" placeholder="<?php echo esc_attr( mcl_t( 'f_own_ph' ) ); ?>">
				</details>
			</section>

			<!-- Étape 5 -->
			<section class="mcl-step" data-step="5">
				<h1 class="mcl-step__title"><?php mcl_e_html( 's5_title', esc_html( mcl_t( 'this_person' ) ) ); ?></h1>
				<p class="mcl-step__sub"><?php mcl_e( 's5_sub' ); ?></p>
				<div class="mcl-seg mcl-seg--full mcl-mode" role="radiogroup">
					<?php foreach ( mcl_options( 'mode' ) as $k => $l ) : ?>
						<label><input type="radio" name="mode" value="<?php echo esc_attr( $k ); ?>" <?php checked( 'story', $k ); ?>><span><?php echo mcl_icon( 'story' === $k ? 'pen' : 'lyrics' ); // phpcs:ignore ?><?php echo esc_html( mcl_opt_label( 'mode', $k ) ); ?></span></label>
					<?php endforeach; ?>
				</div>
				<div class="mcl-q mcl-mode-panel is-active" data-mode="story">
					<label class="mcl-q__label mcl-q__label--serif" for="mcl-story"><?php mcl_e( 'f_story' ); ?></label>
					<textarea id="mcl-story" name="story" rows="7" maxlength="6000" placeholder="<?php echo esc_attr( mcl_t( 'f_story_ph' ) ); ?>"></textarea>
					<p class="mcl-q__label mcl-q__label--caps"><?php mcl_e( 'inspiration' ); ?></p>
					<div class="mcl-insp" data-target="mcl-story">
						<?php foreach ( array( 'insp_1', 'insp_2', 'insp_3' ) as $k ) : ?>
							<button type="button" data-text="<?php echo esc_attr( mcl_t( $k ) ); ?>"><?php echo mcl_icon( 'plus' ); // phpcs:ignore ?><em><?php echo esc_html( trim( mcl_t( $k ), ' —' ) ); ?>…</em></button>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="mcl-q mcl-mode-panel" data-mode="lyrics">
					<label class="mcl-q__label mcl-q__label--serif" for="mcl-lyrics"><?php mcl_e( 'f_lyrics' ); ?></label>
					<textarea id="mcl-lyrics" name="lyrics" rows="12" maxlength="6000" placeholder="<?php echo esc_attr( mcl_t( 'f_lyrics_ph' ) ); ?>"></textarea>
				</div>
			</section>

			<!-- Étape 6 -->
			<section class="mcl-step" data-step="6">
				<h1 class="mcl-step__title"><?php mcl_e_html( 's6_title' ); ?></h1>
				<p class="mcl-step__sub"><?php mcl_e( 's6_sub' ); ?></p>
				<div class="mcl-q">
					<label class="mcl-q__label mcl-q__label--serif" for="mcl-msg"><?php mcl_e( 'f_msg' ); ?> <?php echo $opt_badge; // phpcs:ignore ?></label>
					<textarea id="mcl-msg" name="message" rows="5" maxlength="2000" placeholder="<?php echo esc_attr( mcl_t( 'f_msg_ph' ) ); ?>"></textarea>
					<div class="mcl-insp" data-target="mcl-msg">
						<?php foreach ( array( 'insp_m1', 'insp_m2' ) as $k ) : ?>
							<button type="button" data-text="<?php echo esc_attr( mcl_t( $k ) ); ?>"><?php echo mcl_icon( 'plus' ); // phpcs:ignore ?><em><?php echo esc_html( trim( mcl_t( $k ) ) ); ?></em></button>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="mcl-q">
					<h2 class="mcl-q__label"><?php echo mcl_icon( 'video' ); // phpcs:ignore ?><?php mcl_e( 'q_clip' ); ?></h2>
					<div class="mcl-choices">
						<?php foreach ( mcl_options( 'clip' ) as $k => $l ) : ?>
							<label class="mcl-choice"><input type="radio" name="clip" value="<?php echo esc_attr( $k ); ?>" <?php checked( 'no', $k ); ?>><span><?php echo esc_html( mcl_opt_label( 'clip', $k ) ); ?><?php if ( 'yes' === $k && mcl_price( 'price_clip' ) ) : ?><b class="mcl-choice__price" dir="ltr"> +<?php echo esc_html( mcl_dh( mcl_price( 'price_clip' ) ) ); ?></b><?php endif; ?></span></label>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="mcl-q">
					<span class="mcl-q__label mcl-q__label--serif"><?php mcl_e( 'f_photo' ); ?> <?php echo $opt_badge; // phpcs:ignore ?></span>
					<label class="mcl-file">
						<input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
						<span class="mcl-file__preview" aria-hidden="true"></span>
						<span class="mcl-file__txt"><strong><?php mcl_e( 'f_photo_btn' ); ?></strong><small><?php mcl_e( 'f_photo_hint' ); ?></small></span>
					</label>
				</div>
			</section>

			<!-- Étape finale -->
			<section class="mcl-step" data-step="7">
				<h1 class="mcl-step__title mcl-step__title--center"><?php mcl_e_html( 'f_title' ); ?></h1>
				<p class="mcl-step__sub mcl-step__sub--center"><?php mcl_e_html( 'f_sub', esc_html( mcl_t( 'this_person' ) ) ); ?></p>

				<div class="mcl-card-box">
					<h2 class="mcl-card-box__title"><?php mcl_e( 'contact_t' ); ?></h2>
					<p class="mcl-hint"><?php mcl_e( 'contact_s' ); ?></p>
					<div class="mcl-q" data-required="name">
						<label class="mcl-q__label mcl-q__label--caps" for="mcl-name"><?php mcl_e( 'f_name' ); ?></label>
						<input id="mcl-name" class="mcl-input" type="text" name="name" maxlength="120" autocomplete="name">
					</div>
					<div class="mcl-row2">
						<div class="mcl-q">
							<label class="mcl-q__label mcl-q__label--caps" for="mcl-email"><?php mcl_e( 'f_email' ); ?></label>
							<input id="mcl-email" class="mcl-input" type="email" name="email" maxlength="160" autocomplete="email" placeholder="vous@exemple.com" dir="ltr">
						</div>
						<div class="mcl-q">
							<label class="mcl-q__label mcl-q__label--caps" for="mcl-phone"><?php mcl_e( 'f_phone' ); ?></label>
							<input id="mcl-phone" class="mcl-input" type="tel" name="phone" maxlength="40" autocomplete="tel" placeholder="06 00 00 00 00" dir="ltr">
						</div>
					</div>
					<div class="mcl-q">
						<label class="mcl-q__label mcl-q__label--caps" for="mcl-city"><?php mcl_e( 'f_city' ); ?> <?php echo $opt_badge; // phpcs:ignore ?></label>
						<input id="mcl-city" class="mcl-input" type="text" name="city" maxlength="120" autocomplete="address-level2">
					</div>
					<label class="mcl-consent" data-required="consent"><input type="checkbox" name="consent" value="1"><span><?php mcl_e( 'f_consent' ); ?></span></label>
				</div>

				<div class="mcl-card-box mcl-recap">
					<div class="mcl-recap__head"><h2 class="mcl-card-box__title">♪ <?php mcl_e( 'recap' ); ?></h2></div>
					<dl class="mcl-recap__list"></dl>
				</div>
			
				<?php if ( mcl_price( 'price_express' ) ) : ?>
				<h2 class="mcl-q__label mcl-q__label--caps mcl-opt-head"><?php mcl_e( 'opt_title' ); ?></h2>
				<label class="mcl-card-box mcl-upsell">
					<input type="checkbox" name="express" value="1">
					<span class="mcl-upsell__check" aria-hidden="true"><?php echo mcl_icon( 'check' ); // phpcs:ignore ?></span>
					<span class="mcl-upsell__body">
						<span class="mcl-upsell__tag">⚡ <?php mcl_e( 'express_t' ); ?></span>
						<strong><?php echo esc_html( mcl_t( 'express_s', mcl_price( 'express_hours' ), mcl_price( 'delivery_days' ) ) ); ?></strong>
					</span>
					<span class="mcl-upsell__price" dir="ltr">+<?php echo esc_html( mcl_dh( mcl_price( 'price_express' ) ) ); ?></span>
				</label>
				<?php endif; ?>

				<div class="mcl-card-box mcl-tip">
					<h2 class="mcl-card-box__title"><?php mcl_e( 'tip_t' ); ?></h2>
					<p class="mcl-hint"><?php mcl_e( 'tip_s' ); ?></p>
					<div class="mcl-tip__grid">
						<?php foreach ( array( 5, 10, 15 ) as $pct ) : ?>
							<label class="mcl-tip__opt"><input type="radio" name="tip_pct" value="<?php echo (int) $pct; ?>"><span><?php if ( 10 === $pct ) : ?><i class="mcl-tip__pop">★ <?php mcl_e( 'popular' ); ?></i><?php endif; ?><b dir="ltr"><?php echo (int) $pct; ?> %</b><small data-tip-amount="<?php echo (int) $pct; ?>" dir="ltr"></small></span></label>
						<?php endforeach; ?>
						<label class="mcl-tip__opt"><input type="radio" name="tip_pct" value="0" checked><span><b><?php mcl_e( 'tip_none' ); ?></b></span></label>
					</div>
					<div class="mcl-tip__custom">
						<input type="number" name="tip_custom" min="0" max="5000" step="10" inputmode="numeric" placeholder="<?php echo esc_attr( mcl_t( 'tip_custom' ) ); ?>">
						<span>DH</span>
						<button type="button" data-tip-step="-10" aria-label="-10">−</button>
						<button type="button" data-tip-step="10" aria-label="+10">+</button>
					</div>
					<p class="mcl-tip__thanks"><?php mcl_e( 'tip_thanks' ); ?></p>
				</div>

				<div class="mcl-card-box mcl-sum">
					<h2 class="mcl-card-box__title"><?php mcl_e( 'sum_title' ); ?></h2>
					<div class="mcl-sum__row"><span><?php mcl_e_html( 'sum_song', esc_html( mcl_t( 'this_person' ) ) ); ?></span><span dir="ltr"><?php if ( $p_old > $p_song ) : ?><s><?php echo esc_html( mcl_dh( $p_old ) ); ?></s> <?php endif; ?><b class="mcl-sum__accent"><?php echo esc_html( mcl_dh( $p_song ) ); ?></b></span></div>
					<div class="mcl-sum__row" data-sum="clip" hidden><span><?php mcl_e( 'sum_clip' ); ?></span><span dir="ltr">+<?php echo esc_html( mcl_dh( mcl_price( 'price_clip' ) ) ); ?></span></div>
					<div class="mcl-sum__row" data-sum="express" hidden><span><?php echo esc_html( mcl_t( 'express_t' ) . ' ' . mcl_price( 'express_hours' ) . ' h' ); ?></span><span dir="ltr">+<?php echo esc_html( mcl_dh( mcl_price( 'price_express' ) ) ); ?></span></div>
					<div class="mcl-sum__row" data-sum="tip" hidden><span><?php mcl_e( 'sum_tip' ); ?></span><span dir="ltr" data-sum-tip></span></div>
					<div class="mcl-sum__row mcl-sum__total"><span><?php mcl_e( 'sum_total' ); ?></span><span><?php if ( $p_old > $p_song ) : ?><i class="mcl-sum__save" dir="ltr"><?php echo esc_html( mcl_t( 'sum_save', mcl_dh( $p_old - $p_song ) ) ); ?></i><?php endif; ?><b dir="ltr" data-sum-total><?php echo esc_html( mcl_dh( $p_song ) ); ?></b></span></div>
					<p class="mcl-hint mcl-sum__note"><?php echo mcl_icon( 'lock' ); // phpcs:ignore ?><?php mcl_e( 'pay_note' ); ?></p>
				</div>
			</section>

			<p class="mcl-wizard__error" role="alert" hidden></p>

			<div class="mcl-wizard__nav">
				<button type="button" class="mcl-btn mcl-btn--soft mcl-prev" hidden><?php echo mcl_icon( 'back' ); // phpcs:ignore ?><?php mcl_e( 'back' ); ?></button>
				<button type="button" class="mcl-btn mcl-btn--primary mcl-next"><span><?php mcl_e( 'next' ); ?></span><?php echo mcl_icon( 'arrow' ); // phpcs:ignore ?></button>
				<button type="submit" class="mcl-btn mcl-btn--primary mcl-submit" hidden><?php echo mcl_icon( 'gift' ); // phpcs:ignore ?><span><?php mcl_e( 'submit' ); ?></span><?php echo mcl_icon( 'arrow' ); // phpcs:ignore ?></button>
			</div>
			<?php if ( $phone ) : ?>
				<p class="mcl-help"><?php mcl_e( 'need_help' ); ?>
					<?php foreach ( array( array( mcl_opt( 'phone' ), 'whatsapp' ), array( mcl_opt( 'phone2' ), 'whatsapp2' ) ) as $ph ) : if ( ! $ph[0] ) { continue; } $wl = mcl_whatsapp_url( '', $ph[1] ); ?>
						<a href="<?php echo esc_url( $wl ? $wl : 'tel:' . preg_replace( '/\s+/', '', $ph[0] ) ); ?>" target="_blank" rel="noopener" dir="ltr"><?php echo mcl_icon( 'whatsapp' ); // phpcs:ignore ?><?php echo esc_html( $ph[0] ); ?></a>
					<?php endforeach; ?>
				</p>
			<?php endif; ?>
		</form>

		<div class="mcl-thanks" hidden>
			<div class="mcl-thanks__icon"><?php echo mcl_icon( 'check' ); // phpcs:ignore ?></div>
			<h1 class="mcl-step__title mcl-step__title--center"><?php mcl_e_html( 'thanks_title' ); ?></h1>
			<p class="mcl-step__sub mcl-step__sub--center"><?php mcl_e( 'thanks_text' ); ?> <strong class="mcl-thanks__ref"></strong></p>
			<p class="mcl-cta__final"><?php mcl_e( 'cta_final' ); ?></p>
			<a class="mcl-btn mcl-btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php mcl_e( 'thanks_home' ); ?></a>
		</div>

		<section class="mcl-card-box mcl-receive">
			<h2 class="mcl-card-box__title mcl-card-box__title--center"><?php mcl_e( 'what_title' ); ?></h2>
			<ul>
				<li><span class="mcl-receive__ico"><?php echo mcl_icon( 'mic' ); // phpcs:ignore ?></span><div><strong><?php mcl_e( 'w1_t' ); ?></strong><small><?php mcl_e( 'w1_s' ); ?></small></div></li>
				<li><span class="mcl-receive__ico"><?php echo mcl_icon( 'pen' ); // phpcs:ignore ?></span><div><strong><?php mcl_e( 'w2_t' ); ?></strong><small><?php mcl_e( 'w2_s' ); ?></small></div></li>
				<li><span class="mcl-receive__ico"><?php echo mcl_icon( 'video' ); // phpcs:ignore ?></span><div><strong><?php mcl_e( 'w3_t' ); ?></strong><small><?php mcl_e( 'w3_s' ); ?></small></div></li>
				<li><span class="mcl-receive__ico"><?php echo mcl_icon( 'globe' ); // phpcs:ignore ?></span><div><strong><?php mcl_e( 'w4_t' ); ?></strong><small><?php mcl_e( 'w4_s' ); ?></small></div></li>
			</ul>
		</section>

		<?php
		$samples = new WP_Query( array( 'post_type' => 'mcl_song', 'posts_per_page' => 3, 'meta_key' => '_mcl_plays_audio', 'orderby' => 'meta_value_num', 'meta_query' => array( array( 'key' => '_mcl_audio_url', 'value' => '', 'compare' => '!=' ) ) ) );
		if ( $samples->have_posts() ) :
			?>
			<section class="mcl-card-box mcl-samples">
				<h2 class="mcl-card-box__title mcl-card-box__title--center"><?php mcl_e( 'listen_more' ); ?></h2>
				<?php
				while ( $samples->have_posts() ) {
					$samples->the_post();
					$sid = get_the_ID();
					$sub = trim( mcl_song_genre( $sid ) . ( get_post_meta( $sid, '_mcl_duration', true ) ? ' · ' . get_post_meta( $sid, '_mcl_duration', true ) : '' ), ' ·' );
					echo mcl_media_html( get_post_meta( $sid, '_mcl_audio_url', true ), 'audio', $sid, array( 'title' => '« ' . mcl_song_title( $sid ) . ' »', 'artist' => $sub ) ); // phpcs:ignore
				}
				wp_reset_postdata();
				?>
			</section>
		<?php endif; ?>

	</div>
</section>
<?php
get_footer();
