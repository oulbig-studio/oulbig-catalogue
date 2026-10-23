<?php
/** Carte d'un titre. $args['mode'] = 'audio' | 'video' (oriente le lien et le compteur). */
$id    = get_the_ID();
$mode  = isset( $args['mode'] ) ? $args['mode'] : ( mcl_has_audio( $id ) ? 'audio' : 'video' );
$cover = mcl_cover_url( $id, 'video' === $mode ? 'mcl-wide' : 'mcl-cover' );
$n     = mcl_plays( $id, $mode );
?>
<article class="mcl-card mcl-card--<?php echo esc_attr( $mode ); ?>">
	<a class="mcl-card__media" href="<?php the_permalink(); ?>#<?php echo esc_attr( $mode ); ?>">
		<?php echo $cover ? '<img src="' . esc_url( $cover ) . '" alt="" loading="lazy">' : mcl_placeholder_cover( $id ); // phpcs:ignore ?>
		<span class="mcl-card__play"><?php echo mcl_icon( 'play' ); // phpcs:ignore ?></span>
		<span class="mcl-card__badges">
			<?php if ( mcl_has_audio( $id ) ) : ?><span title="<?php echo esc_attr( mcl_t( 'audio' ) ); ?>"><?php echo mcl_icon( 'audio' ); // phpcs:ignore ?></span><?php endif; ?>
			<?php if ( mcl_has_video( $id ) ) : ?><span title="<?php echo esc_attr( mcl_t( 'video' ) ); ?>"><?php echo mcl_icon( 'video' ); // phpcs:ignore ?></span><?php endif; ?>
			<?php if ( get_post_meta( $id, '_mcl_lyrics', true ) ) : ?><span title="<?php echo esc_attr( mcl_t( 'lyrics' ) ); ?>"><?php echo mcl_icon( 'lyrics' ); // phpcs:ignore ?></span><?php endif; ?>
		</span>
		<?php $dur = get_post_meta( $id, '_mcl_duration', true ); if ( $dur ) : ?><span class="mcl-card__dur" dir="ltr"><?php echo esc_html( $dur ); ?></span><?php endif; ?>
	</a>
	<div class="mcl-card__body">
		<h3 class="mcl-card__title"><a href="<?php the_permalink(); ?>"><?php echo esc_html( mcl_song_title( $id ) ); ?></a></h3>
		<p class="mcl-card__meta">
			<span><?php echo esc_html( mcl_song_artist( $id ) ); ?></span>
			<?php $g = mcl_song_genre( $id ); if ( $g ) : ?><span class="mcl-dot"><?php echo esc_html( $g ); ?></span><?php endif; ?>
		</p>
		<?php if ( $n ) : ?>
			<p class="mcl-card__plays"><?php echo esc_html( 'video' === $mode ? mcl_t( 'views_n', mcl_num( $n ) ) : mcl_t( 'plays_n', mcl_num( $n ) ) ); ?></p>
		<?php endif; ?>
	</div>
</article>
