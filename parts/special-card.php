<?php /** Carte « Chanson personnalisée » (lien vers le formulaire de demande). */ ?>
<article class="mcl-svc mcl-svc--special">
	<div class="mcl-svc__top">
		<span class="mcl-svc__icon" aria-hidden="true">🎁</span>
		<span class="mcl-svc__price" dir="ltr"><?php if ( mcl_price( 'price_old' ) > mcl_price( 'price_song' ) ) : ?><s class="mcl-svc__old"><?php echo esc_html( mcl_dh( mcl_price( 'price_old' ) ) ); ?></s> <?php endif; ?><?php echo esc_html( mcl_dh( mcl_price( 'price_song' ) ) ); ?></span>
	</div>
	<h3 class="mcl-svc__title"><a href="<?php echo esc_url( mcl_request_url() ); ?>"><?php mcl_e( 'special_title' ); ?></a></h3>
	<p class="mcl-svc__tag"><?php mcl_e( 'special_tag' ); ?></p>
	<ul class="mcl-svc__list">
		<li><?php echo mcl_icon( 'check' ); // phpcs:ignore ?><?php mcl_e( 'w2_t' ); ?></li>
		<li><?php echo mcl_icon( 'check' ); // phpcs:ignore ?><?php echo esc_html( mcl_t( 'sum_clip' ) . ' : ' . mcl_t( 'per_clip', mcl_dh( mcl_price( 'price_clip' ) ) ) ); ?></li>
		<li><?php echo mcl_icon( 'check' ); // phpcs:ignore ?><?php echo esc_html( mcl_t( 'express_t' ) . ' ' . mcl_price( 'express_hours' ) . ' h : +' . mcl_dh( mcl_price( 'price_express' ) ) ); ?></li>
		<li><?php echo mcl_icon( 'check' ); // phpcs:ignore ?><?php mcl_e( 'w4_s' ); ?></li>
	</ul>
	<a class="mcl-btn mcl-btn--primary mcl-svc__cta" href="<?php echo esc_url( mcl_request_url() ); ?>"><?php echo mcl_icon( 'gift' ); // phpcs:ignore ?><?php mcl_e( 'cta_create' ); ?></a>
</article>
