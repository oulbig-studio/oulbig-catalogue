<?php
get_header();
$is_cat = is_category();
?>
<section class="mcl-pagehead">
	<div class="mcl-wrap">
		<p class="mcl-kicker">Musicology</p>
		<h1 class="mcl-h1"><?php echo $is_cat ? esc_html( single_cat_title( '', false ) ) : wp_kses( mcl_t( 'blog_title' ), array( 'em' => array() ) ); ?></h1>
		<p class="mcl-lead"><?php mcl_e( 'blog_sub' ); ?></p>
		<div class="mcl-chips-row mcl-chips-row--small">
			<a class="mcl-occ<?php echo $is_cat ? '' : ' is-active'; ?>" href="<?php echo esc_url( mcl_blog_url() ); ?>"><?php mcl_e( 'all_posts' ); ?></a>
			<?php foreach ( get_categories( array( 'hide_empty' => true ) ) as $c ) : ?>
				<a class="mcl-occ<?php echo is_category( $c->term_id ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_category_link( $c ) ); ?>"><?php echo esc_html( $c->name ); ?></a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<section class="mcl-section mcl-section--tight">
	<div class="mcl-wrap">
		<?php if ( have_posts() ) : ?>
			<div class="mcl-blog-grid">
				<?php
				$i = 0;
				while ( have_posts() ) {
					the_post();
					get_template_part( 'parts/post-card', null, array( 'big' => ( 0 === $i && ! is_paged() ) ) );
					$i++;
				}
				?>
			</div>
			<?php mcl_pagination(); ?>
		<?php else : ?>
			<p class="mcl-empty"><?php mcl_e( 'no_posts' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
