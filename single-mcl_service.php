<?php
get_header();
while ( have_posts() ) :
	the_post();
	$id    = get_the_ID();
	$title = mcl_sv( $id, 'title' );
	$price = mcl_service_price( $id ) ?: mcl_t( 'quote' );
	$badge = mcl_sv( $id, 'badge' );
	$feats = mcl_sv_lines( $id, 'features' );
	$opts  = mcl_sv_lines( $id, 'options' );
	$reqs  = mcl_sv_lines( $id, 'requirements' );
	$body  = 'fr' === mcl_lang() ? get_the_content() : mcl_sv( $id, 'content' );
	$wa    = mcl_service_whatsapp( $id );
	$cover = has_post_thumbnail() ? get_the_post_thumbnail_url( $id, 'mcl-wide' ) : '';

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
	?>
	<article class="mcl-service">
		<header class="mcl-service__hero">
			<?php if ( $cover ) : ?><div class="mcl-single__blur" style="background-image:url('<?php echo esc_url( $cover ); ?>')" aria-hidden="true"></div><?php endif; ?>
			<div class="mcl-wrap">
				<a class="mcl-link mcl-service__back" href="<?php echo esc_url( mcl_services_url() ); ?>"><?php echo mcl_icon( 'back' ); // phpcs:ignore ?><?php mcl_e( 'services_all' ); ?></a>
				<div class="mcl-service__head">
					<span class="mcl-service__icon" aria-hidden="true"><?php echo esc_html( mcl_service_icon( $id ) ); ?></span>
					<div>
						<?php if ( $badge ) : ?><p class="mcl-kicker"><?php echo esc_html( $badge ); ?></p><?php endif; ?>
						<h1 class="mcl-single__title"><?php echo esc_html( $title ); ?></h1>
						<p class="mcl-service__tag"><?php echo esc_html( mcl_sv( $id, 'tagline' ) ); ?></p>
						<p class="mcl-service__price" dir="auto"><?php echo esc_html( $price ); ?></p>
					</div>
				</div>
			</div>
		</header>

		<div class="mcl-wrap mcl-service__grid">
			<div class="mcl-service__main">
				<?php if ( trim( wp_strip_all_tags( $body ) ) ) : ?>
					<section class="mcl-block mcl-prose"><?php echo wp_kses_post( wpautop( $body ) ); ?></section>
				<?php endif; ?>

				<?php if ( $feats ) : ?>
					<section class="mcl-block">
						<h2 class="mcl-block__title"><?php echo mcl_icon( 'check' ); // phpcs:ignore ?><?php mcl_e( 'svc_included' ); ?></h2>
						<ul class="mcl-svc__list mcl-svc__list--big">
							<?php foreach ( $feats as $f ) : ?><li><?php echo mcl_icon( 'check' ); // phpcs:ignore ?><?php echo esc_html( $f ); ?></li><?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>

				<?php if ( $reqs ) : ?>
					<section class="mcl-block mcl-block--note">
						<h2 class="mcl-block__title"><?php echo mcl_icon( 'mic' ); // phpcs:ignore ?><?php mcl_e( 'svc_req_title' ); ?></h2>
						<ol class="mcl-req-files">
							<?php foreach ( $reqs as $r ) : ?><li><?php echo esc_html( $r ); ?></li><?php endforeach; ?>
						</ol>
					</section>
				<?php endif; ?>
			</div>

			<aside class="mcl-order" id="commander">
				<form class="mcl-order__form" novalidate>
					<h2 class="mcl-card-box__title"><?php mcl_e( 'svc_order' ); ?></h2>
					<p class="mcl-hint"><?php mcl_e( 'svc_order_sub' ); ?></p>
					<input type="hidden" name="action" value="mcl_order">
					<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'mcl_order' ) ); ?>">
					<input type="hidden" name="lang" value="<?php echo esc_attr( mcl_lang() ); ?>">
					<input type="hidden" name="service" value="<?php echo (int) $id; ?>">
					<input type="hidden" name="started" value="<?php echo esc_attr( time() ); ?>">
					<div class="mcl-hp" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

					<?php if ( $opts ) : ?>
						<div class="mcl-q" data-req="option">
							<p class="mcl-q__label mcl-q__label--caps"><?php mcl_e( 'svc_options' ); ?></p>
							<div class="mcl-choices">
								<?php foreach ( $opts as $i => $o ) : ?>
									<label class="mcl-choice"><input type="radio" name="option" value="<?php echo (int) $i; ?>" <?php checked( 0, $i ); ?>><span><?php echo esc_html( $o ); ?></span></label>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>

					<div class="mcl-q" data-req="name">
						<label class="mcl-q__label mcl-q__label--caps" for="o-name"><?php mcl_e( 'f_name' ); ?></label>
						<input id="o-name" class="mcl-input" type="text" name="name" maxlength="120" autocomplete="name">
					</div>
					<div class="mcl-q">
						<label class="mcl-q__label mcl-q__label--caps" for="o-phone"><?php mcl_e( 'f_phone' ); ?></label>
						<input id="o-phone" class="mcl-input" type="tel" name="phone" maxlength="40" autocomplete="tel" dir="ltr" placeholder="06 00 00 00 00">
					</div>
					<div class="mcl-q">
						<label class="mcl-q__label mcl-q__label--caps" for="o-email"><?php mcl_e( 'f_email' ); ?></label>
						<input id="o-email" class="mcl-input" type="email" name="email" maxlength="160" autocomplete="email" dir="ltr" placeholder="vous@exemple.com">
					</div>
					<div class="mcl-q">
						<label class="mcl-q__label mcl-q__label--caps" for="o-files"><?php mcl_e( 'svc_files' ); ?> <span class="mcl-badge"><?php mcl_e( 'optional' ); ?></span></label>
						<input id="o-files" class="mcl-input" type="text" name="files" maxlength="1000" dir="ltr" placeholder="<?php echo esc_attr( mcl_t( 'svc_files_ph' ) ); ?>">
					</div>
					<div class="mcl-q">
						<label class="mcl-q__label mcl-q__label--caps" for="o-details"><?php mcl_e( 'svc_details' ); ?></label>
						<textarea id="o-details" name="details" rows="4" maxlength="4000" placeholder="<?php echo esc_attr( mcl_t( 'svc_details_ph' ) ); ?>"></textarea>
					</div>
					<p class="mcl-wizard__error" role="alert" hidden></p>
					<button type="submit" class="mcl-btn mcl-btn--primary mcl-btn--lg mcl-order__submit"><span><?php mcl_e( 'svc_send' ); ?></span><?php echo mcl_icon( 'arrow' ); // phpcs:ignore ?></button>
					<?php if ( $wa ) : ?>
						<p class="mcl-order__or"><span><?php mcl_e( 'svc_or' ); ?></span></p>
						<a class="mcl-btn mcl-btn--wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener"><?php echo mcl_icon( 'whatsapp' ); // phpcs:ignore ?><?php mcl_e( 'svc_wa' ); ?></a>
					<?php endif; ?>
				</form>
				<div class="mcl-order__thanks" hidden>
					<div class="mcl-thanks__icon"><?php echo mcl_icon( 'check' ); // phpcs:ignore ?></div>
					<h2 class="mcl-step__title mcl-step__title--center"><?php mcl_e_html( 'svc_thanks' ); ?></h2>
					<p class="mcl-step__sub mcl-step__sub--center"><?php mcl_e( 'thanks_text' ); ?> <strong class="mcl-thanks__ref"></strong></p>
				</div>
			</aside>
		</div>

		<?php get_template_part( 'parts/process' ); ?>

		<?php
		$others = new WP_Query( array( 'post_type' => 'mcl_service', 'posts_per_page' => 3, 'post__not_in' => array( $id ), 'orderby' => 'menu_order', 'order' => 'ASC' ) );
		if ( $others->have_posts() ) :
			?>
			<section class="mcl-section mcl-section--tight">
				<div class="mcl-wrap">
					<div class="mcl-section__head">
						<h2 class="mcl-h2"><?php mcl_e( 'other_services' ); ?></h2>
						<a class="mcl-link" href="<?php echo esc_url( mcl_services_url() ); ?>"><?php mcl_e( 'services_all' ); ?> <?php echo mcl_icon( 'arrow' ); // phpcs:ignore ?></a>
					</div>
					<div class="mcl-svc-grid">
						<?php
						while ( $others->have_posts() ) {
							$others->the_post();
							get_template_part( 'parts/service-card' );
						}
						wp_reset_postdata();
						?>
					</div>
				</div>
			</section>
		<?php endif; ?>
	</article>
	<?php
endwhile;
get_footer();
