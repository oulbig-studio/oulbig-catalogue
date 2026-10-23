<?php
$id    = get_the_ID();
$big   = ! empty( $args['big'] );
$cats  = get_the_category( $id );
$plang = mcl_post_lang( $id );
?>
<article class="mcl-post<?php echo $big ? ' mcl-post--big' : ''; ?>"<?php echo 'ar' === $plang ? ' dir="rtl"' : ''; ?>>
	<a class="mcl-post__img" href="<?php the_permalink(); ?>">
		<?php echo has_post_thumbnail() ? get_the_post_thumbnail( $id, $big ? 'mcl-wide' : 'medium_large', array( 'loading' => 'lazy' ) ) : mcl_placeholder_cover( $id ); // phpcs:ignore ?>
	</a>
	<div class="mcl-post__body">
		<p class="mcl-post__meta">
			<?php if ( $cats ) : ?><span class="mcl-post__cat"><?php echo esc_html( $cats[0]->name ); ?></span><?php endif; ?>
			<span><?php echo esc_html( mcl_date( $id ) ); ?></span>
			<span class="mcl-dot"><?php echo esc_html( mcl_t( 'read_time', mcl_reading_time( $id ) ) ); ?></span>
		</p>
		<h3 class="mcl-post__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<p class="mcl-post__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), $big ? 40 : 22 ) ); ?></p>
		<a class="mcl-link" href="<?php the_permalink(); ?>"><?php mcl_e( 'read_more' ); ?> <?php echo mcl_icon( 'arrow' ); // phpcs:ignore ?></a>
	</div>
</article>
