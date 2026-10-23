<?php
/**
 * Template Name: Hit-Parade
 */
get_header();
?>
<section class="mcl-pagehead mcl-pagehead--charts">
	<div class="mcl-wrap">
		<p class="mcl-kicker"><?php echo mcl_icon( 'trophy' ); // phpcs:ignore ?> Musicology</p>
		<h1 class="mcl-h1"><?php mcl_e( 'hit_title' ); ?></h1>
		<p class="mcl-lead"><?php mcl_e( 'hit_sub' ); ?></p>
		<div class="mcl-filters mcl-charts-ctrl">
			<div class="mcl-seg" data-group="kind">
				<button type="button" class="is-active" data-val="audio"><?php echo mcl_icon( 'audio' ); // phpcs:ignore ?><?php mcl_e( 'tab_songs' ); ?></button>
				<button type="button" data-val="video"><?php echo mcl_icon( 'video' ); // phpcs:ignore ?><?php mcl_e( 'tab_clips' ); ?></button>
			</div>
			<div class="mcl-seg mcl-seg--soft" data-group="period">
				<button type="button" class="is-active" data-val="all"><?php mcl_e( 'period_all' ); ?></button>
				<button type="button" data-val="month"><?php mcl_e( 'period_month' ); ?></button>
			</div>
		</div>
	</div>
</section>
<section class="mcl-section mcl-section--tight">
	<div class="mcl-wrap mcl-charts">
		<?php foreach ( array( 'audio', 'video' ) as $kind ) : ?>
			<?php foreach ( array( 'all', 'month' ) as $period ) : ?>
				<div class="mcl-charts__panel<?php echo ( 'audio' === $kind && 'all' === $period ) ? ' is-active' : ''; ?>" data-kind="<?php echo esc_attr( $kind ); ?>" data-period="<?php echo esc_attr( $period ); ?>">
					<?php mcl_render_chart( $kind, $period, 20 ); ?>
				</div>
			<?php endforeach; ?>
		<?php endforeach; ?>
	</div>
</section>
<section class="mcl-band">
	<div class="mcl-wrap mcl-band__in">
		<p class="mcl-band__q"><?php mcl_e_html( 'hero_q' ); ?></p>
		<a class="mcl-btn mcl-btn--primary" href="<?php echo esc_url( mcl_request_url() ); ?>"><?php echo mcl_icon( 'gift' ); // phpcs:ignore ?><?php mcl_e( 'cta_create' ); ?></a>
	</div>
</section>
<?php
get_footer();
