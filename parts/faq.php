<section class="mcl-section mcl-faq">
	<div class="mcl-wrap mcl-wrap--narrow">
		<h2 class="mcl-h2"><?php mcl_e_html( 'faq_title' ); ?></h2>
		<?php for ( $i = 1; $i <= 6; $i++ ) : ?>
			<details class="mcl-faq__item"<?php echo 1 === $i ? ' open' : ''; ?>>
				<summary><?php mcl_e( 'faq' . $i . '_q' ); ?><span aria-hidden="true">+</span></summary>
				<p><?php mcl_e( 'faq' . $i . '_a' ); ?></p>
			</details>
		<?php endfor; ?>
	</div>
</section>
