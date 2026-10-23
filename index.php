<?php
get_header();
?>
<section class="mcl-pagehead">
	<div class="mcl-wrap">
		<h1 class="mcl-h1"><?php echo is_search() ? esc_html( mcl_t( 'search' ) . ' : ' . get_search_query() ) : esc_html( wp_strip_all_tags( get_the_archive_title() ? get_the_archive_title() : get_bloginfo( 'name' ) ) ); ?></h1>
	</div>
</section>
<section class="mcl-section mcl-section--tight">
	<div class="mcl-wrap">
		<?php if ( have_posts() ) : ?>
			<div class="mcl-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					if ( 'mcl_song' === get_post_type() ) {
						get_template_part( 'parts/song-card' );
						continue;
					}
					?>
					<article class="mcl-card">
						<a class="mcl-card__media" href="<?php the_permalink(); ?>"><?php echo has_post_thumbnail() ? get_the_post_thumbnail( null, 'mcl-cover' ) : mcl_placeholder_cover( get_the_ID() ); // phpcs:ignore ?></a>
						<div class="mcl-card__body">
							<h3 class="mcl-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							<p class="mcl-card__meta"><?php echo esc_html( get_the_date() ); ?></p>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
			<?php mcl_pagination(); ?>
		<?php else : ?>
			<p class="mcl-empty"><?php mcl_e( 'empty' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
