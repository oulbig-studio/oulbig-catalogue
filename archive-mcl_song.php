<?php
get_header();
$media = isset( $_GET['media'] ) ? sanitize_key( $_GET['media'] ) : '';
$base  = is_tax() ? get_term_link( get_queried_object() ) : get_post_type_archive_link( 'mcl_song' );
$title = 'video' === $media ? mcl_t( 'nav_clips' ) : mcl_t( 'nav_songs' );
if ( is_tax() ) {
	$title = single_term_title( '', false );
}
$q = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
?>
<section class="mcl-pagehead">
	<div class="mcl-wrap">
		<p class="mcl-kicker">Musicology</p>
		<h1 class="mcl-h1"><?php echo esc_html( $title ); ?></h1>
		<form class="mcl-search" action="<?php echo esc_url( $base ); ?>" method="get" role="search">
			<?php if ( $media ) : ?><input type="hidden" name="media" value="<?php echo esc_attr( $media ); ?>"><?php endif; ?>
			<?php echo mcl_icon( 'search' ); // phpcs:ignore ?>
			<input type="search" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="<?php echo esc_attr( mcl_t( 'search_ph' ) ); ?>" aria-label="<?php echo esc_attr( mcl_t( 'search' ) ); ?>">
		</form>
		<div class="mcl-filters">
			<div class="mcl-seg">
				<a href="<?php echo esc_url( remove_query_arg( array( 'media', 'paged' ), $base ) ); ?>" class="<?php echo '' === $media ? 'is-active' : ''; ?>"><?php mcl_e( 'all' ); ?></a>
				<a href="<?php echo esc_url( add_query_arg( 'media', 'audio', $base ) ); ?>" class="<?php echo 'audio' === $media ? 'is-active' : ''; ?>"><?php echo mcl_icon( 'audio' ); // phpcs:ignore ?><?php mcl_e( 'audio' ); ?></a>
				<a href="<?php echo esc_url( add_query_arg( 'media', 'video', $base ) ); ?>" class="<?php echo 'video' === $media ? 'is-active' : ''; ?>"><?php echo mcl_icon( 'video' ); // phpcs:ignore ?><?php mcl_e( 'nav_clips' ); ?></a>
			</div>
			<div class="mcl-chips-row mcl-chips-row--small">
				<?php
				$genres = get_terms( array( 'taxonomy' => 'mcl_genre', 'hide_empty' => true ) );
				if ( ! is_wp_error( $genres ) ) {
					foreach ( $genres as $g ) {
						$link = get_term_link( $g );
						if ( $media ) {
							$link = add_query_arg( 'media', $media, $link );
						}
						printf( '<a class="mcl-occ%s" href="%s">%s</a>', is_tax( 'mcl_genre', $g->term_id ) ? ' is-active' : '', esc_url( $link ), esc_html( $g->name ) );
					}
				}
				?>
			</div>
		</div>
	</div>
</section>

<section class="mcl-section mcl-section--tight">
	<div class="mcl-wrap">
		<?php if ( have_posts() ) : ?>
			<div class="mcl-grid<?php echo 'video' === $media ? ' mcl-grid--wide' : ''; ?>">
				<?php
				while ( have_posts() ) {
					the_post();
					get_template_part( 'parts/song-card', null, $media ? array( 'mode' => $media ) : array() );
				}
				?>
			</div>
			<?php mcl_pagination(); ?>
		<?php else : ?>
			<p class="mcl-empty"><?php mcl_e( 'empty' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
