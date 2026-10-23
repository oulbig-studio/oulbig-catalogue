<?php /** Carte « Livres audio & podcasts » (3e pôle). */ ?>
<article class="mcl-svc mcl-svc--special mcl-svc--book">
	<div class="mcl-svc__top">
		<span class="mcl-svc__icon" aria-hidden="true">🎧</span>
		<span class="mcl-svc__price"><?php mcl_e( 'quote' ); ?></span>
	</div>
	<h3 class="mcl-svc__title"><a href="<?php echo esc_url( mcl_audio_url() ); ?>"><?php mcl_e( 'ab_kicker' ); ?></a></h3>
	<p class="mcl-svc__tag"><?php mcl_e( 'ab_card_tag' ); ?></p>
	<ul class="mcl-svc__list">
		<li><?php echo mcl_icon( 'check' ); // phpcs:ignore ?><?php echo esc_html( mcl_t( 'ab_f1_t' ) . ', ' . mb_strtolower( mcl_t( 'ab_f2_t' ) ) . ', ' . mcl_t( 'ab_f3_t' ) ); ?></li>
		<li><?php echo mcl_icon( 'check' ); // phpcs:ignore ?><?php mcl_e( 'ab_w1' ); ?></li>
		<li><?php echo mcl_icon( 'check' ); // phpcs:ignore ?>🎙️ <?php echo wp_strip_all_tags( mcl_t( 'ab_pod_title' ) ); // phpcs:ignore ?></li>
	</ul>
	<div class="mcl-svc__btns">
		<a class="mcl-btn mcl-btn--primary mcl-svc__cta" href="<?php echo esc_url( mcl_audio_url() ); ?>"><?php mcl_e( 'see_service' ); ?><?php echo mcl_icon( 'arrow' ); // phpcs:ignore ?></a>
		<a class="mcl-link" href="<?php echo esc_url( mcl_audio_url( 'exemple' ) ); ?>"><?php mcl_e( 'ab_see_example' ); ?></a>
	</div>
</article>
