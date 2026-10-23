<?php
$mail  = mcl_opt( 'contact_email' );
$phone = mcl_opt( 'phone' );
$wa    = mcl_whatsapp_url();
$loc   = mcl_opt( 'location' );
?>
<ul class="mcl-contact">
	<?php if ( $mail ) : ?>
		<li><a href="mailto:<?php echo esc_attr( $mail ); ?>"><?php echo mcl_icon( 'mail' ); // phpcs:ignore ?><span><?php echo esc_html( $mail ); ?></span></a></li>
	<?php endif; ?>
	<?php
	foreach ( array( array( mcl_opt( 'phone' ), 'whatsapp' ), array( mcl_opt( 'phone2' ), 'whatsapp2' ) ) as $ph ) :
		if ( ! $ph[0] ) {
			continue;
		}
		$wlink = mcl_whatsapp_url( '', $ph[1] );
		?>
		<li><a href="<?php echo esc_url( $wlink ? $wlink : 'tel:' . preg_replace( '/\s+/', '', $ph[0] ) ); ?>" <?php echo $wlink ? 'target="_blank" rel="noopener"' : ''; ?>><?php echo mcl_icon( 'whatsapp' ); // phpcs:ignore ?><span dir="ltr"><?php echo esc_html( $ph[0] ); ?></span></a></li>
	<?php endforeach; ?>
	<?php if ( $loc ) : ?>
		<li><span class="mcl-contact__loc"><?php echo mcl_icon( 'pin' ); // phpcs:ignore ?><span><?php echo esc_html( $loc ); ?></span></span></li>
	<?php endif; ?>
	<?php foreach ( array( 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube' ) as $k => $label ) : ?>
		<?php if ( mcl_opt( $k ) ) : ?>
			<li><a href="<?php echo esc_url( mcl_opt( $k ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $label ); ?> ↗</a></li>
		<?php endif; ?>
	<?php endforeach; ?>
</ul>
