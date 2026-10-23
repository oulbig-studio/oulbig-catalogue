<?php
get_header();
while ( have_posts() ) :
	the_post();
	$id    = get_the_ID();
	$plang = mcl_post_lang( $id );
	$cats  = get_the_category( $id );
	$url   = get_permalink();
	?>
	<article class="mcl-article"<?php echo 'ar' === $plang ? ' dir="rtl"' : ''; ?>>
		<header class="mcl-article__head">
			<div class="mcl-wrap mcl-wrap--narrow">
				<a class="mcl-link" href="<?php echo esc_url( mcl_blog_url() ); ?>"><?php echo mcl_icon( 'back' ); // phpcs:ignore ?><?php echo wp_strip_all_tags( mcl_t( 'blog_title' ) ); // phpcs:ignore ?></a>
				<p class="mcl-post__meta">
					<?php if ( $cats ) : ?><a class="mcl-post__cat" href="<?php echo esc_url( get_category_link( $cats[0] ) ); ?>"><?php echo esc_html( $cats[0]->name ); ?></a><?php endif; ?>
					<span><?php echo esc_html( mcl_date( $id ) ); ?></span>
					<span class="mcl-dot"><?php echo esc_html( mcl_t( 'read_time', mcl_reading_time( $id ) ) ); ?></span>
				</p>
				<h1 class="mcl-article__title"><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?><p class="mcl-article__lead"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
			</div>
		</header>
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="mcl-wrap mcl-article__cover"><?php the_post_thumbnail( 'mcl-wide' ); ?></div>
		<?php endif; ?>
		<div class="mcl-wrap mcl-wrap--narrow">
			<div class="mcl-prose mcl-article__body"><?php the_content(); ?></div>
			<div class="mcl-share">
				<strong><?php mcl_e( 'share' ); ?></strong>
				<a href="<?php echo esc_url( 'https://wa.me/?text=' . rawurlencode( get_the_title() . ' ' . $url ) ); ?>" target="_blank" rel="noopener">WhatsApp</a>
				<a href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ) ); ?>" target="_blank" rel="noopener">Facebook</a>
				<a href="<?php echo esc_url( 'https://twitter.com/intent/tweet?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( get_the_title() ) ); ?>" target="_blank" rel="noopener">X</a>
				<button type="button" class="mcl-copy" data-url="<?php echo esc_url( $url ); ?>" data-done="<?php echo esc_attr( mcl_t( 'copied' ) ); ?>"><?php mcl_e( 'copy_link' ); ?></button>
			</div>
		</div>
	</article>

	<section class="mcl-band">
		<div class="mcl-wrap mcl-band__in">
			<p class="mcl-band__q"><?php mcl_e( 'need_service' ); ?></p>
			<div class="mcl-hero__cta">
				<a class="mcl-btn mcl-btn--ghost" href="<?php echo esc_url( mcl_services_url() ); ?>"><?php mcl_e( 'nav_services' ); ?></a>
				<a class="mcl-btn mcl-btn--primary" href="<?php echo esc_url( mcl_request_url() ); ?>"><?php echo mcl_icon( 'gift' ); // phpcs:ignore ?><?php mcl_e( 'cta_create' ); ?></a>
			</div>
		</div>
	</section>

	<?php
	$rel = mcl_latest_posts( 3, array( $id ) );
	if ( $rel->have_posts() ) :
		?>
		<section class="mcl-section">
			<div class="mcl-wrap">
				<h2 class="mcl-h2"><?php mcl_e( 'related_posts' ); ?></h2>
				<div class="mcl-blog-grid">
					<?php
					while ( $rel->have_posts() ) {
						$rel->the_post();
						get_template_part( 'parts/post-card' );
					}
					wp_reset_postdata();
					?>
				</div>
			</div>
		</section>
	<?php endif; ?>
	<?php
endwhile;
get_footer();
