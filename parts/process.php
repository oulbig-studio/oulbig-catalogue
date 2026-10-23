<section class="mcl-section mcl-process">
	<div class="mcl-wrap">
		<h2 class="mcl-h2"><?php mcl_e_html( 'process_title' ); ?></h2>
		<ol class="mcl-process__list">
			<?php for ( $i = 1; $i <= 4; $i++ ) : ?>
				<li><span class="mcl-process__n">0<?php echo (int) $i; ?></span><strong><?php mcl_e( 'p' . $i . '_t' ); ?></strong><p><?php mcl_e( 'p' . $i . '_s' ); ?></p></li>
			<?php endfor; ?>
		</ol>
		<ul class="mcl-policies">
			<?php for ( $i = 1; $i <= 4; $i++ ) : ?><li><?php echo mcl_icon( 'check' ); // phpcs:ignore ?><?php mcl_e( 'pol_' . $i ); ?></li><?php endfor; ?>
		</ul>
	</div>
</section>
