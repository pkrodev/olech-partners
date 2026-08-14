<?php
/**
 * Sticky pasek mobilny — ukryty na desktopie przez CSS (.olech-cta-sticky).
 */

return function () {
	$telefon  = olech_ustawienia_firmy( 'telefon' );
	$whatsapp = olech_ustawienia_firmy( 'whatsapp' );

	if ( ! $telefon && ! $whatsapp ) {
		return '';
	}

	ob_start();
	?>
	<div class="olech-cta-sticky">
		<?php if ( $telefon ) : ?>
			<a href="<?php echo esc_url( 'tel:' . preg_replace( '/\s+/', '', $telefon ) ); ?>">Zadzwoń</a>
		<?php endif; ?>
		<?php if ( $whatsapp ) : ?>
			<a href="<?php echo esc_url( $whatsapp ); ?>">WhatsApp</a>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
};
