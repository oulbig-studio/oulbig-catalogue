<?php
/** Carte d'un service (dans la boucle). */
$id    = get_the_ID();
$badge = mcl_sv( $id, 'badge' );
$feats = array_slice( mcl_sv_lines( $id, 'features' ), 0, 4 );
?>
<article class="mcl-svc<?php echo $badge ? ' is-featured' : ''; ?>">
	<?php if ( $badge ) : ?><span class="mcl-svc__badge"><?php echo esc_html( $badge ); ?></span><?php endif; ?>
	<div class="mcl-svc__top">
		<span class="mcl-svc__icon" aria-hidden="true"><?php echo esc_html( mcl_service_icon( $id ) ); ?></span>
		<span class="mcl-svc__price" dir="auto"><?php echo esc_html( mcl_service_price( $id ) ?: mcl_t( 'quote' ) ); ?></span>
	</div>
	<h3 class="mcl-svc__title"><a href="<?php the_permalink(); ?>"><?php echo esc_html( mcl_sv( $id, 'title' ) ); ?></a></h3>
	<p class="mcl-svc__tag"><?php echo esc_html( mcl_sv( $id, 'tagline' ) ); ?></p>
	<?php if ( $feats ) : ?>
		<ul class="mcl-svc__list">
			<?php foreach ( $feats as $f ) : ?><li><?php echo mcl_icon( 'check' ); // phpcs:ignore ?><?php echo esc_html( $f ); ?></li><?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<a class="mcl-btn mcl-btn--ghost mcl-svc__cta" href="<?php the_permalink(); ?>"><?php mcl_e( 'see_service' ); ?><?php echo mcl_icon( 'arrow' ); // phpcs:ignore ?></a>
</article>
