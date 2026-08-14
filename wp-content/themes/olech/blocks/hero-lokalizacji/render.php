<?php
/**
 * Hero lokalizacji — H1 + lead + CTA (sekcja 8.1 pkt 1, model pracy z sekcji 6.3).
 */

return function () {
	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return '';
	}

	$nazwa       = get_the_title( $post_id );
	$miejscownik = get_field( 'nazwa_miejscownik', $post_id ) ?: ( 'w ' . $nazwa );
	$telefon     = olech_ustawienia_firmy( 'telefon' );

	ob_start();
	?>
	<div class="wp-block-group olech-hero">
		<h1><?php echo esc_html( sprintf( 'Detektyw %s — dojazd w 24 godziny', $nazwa ) ); ?></h1>
		<p class="olech-hero__lead">
			Sprawy <?php echo esc_html( $miejscownik ); ?> prowadzimy dojazdowo z bazy w Radomiu przez sieć licencjonowanych współpracowników — start działań zwykle w ciągu 24 h od potwierdzenia zlecenia. Pełna poufność, tajemnica zawodowa.
		</p>
		<div class="olech-cta-row">
			<?php if ( $telefon ) : ?>
				<a class="olech-btn" href="<?php echo esc_url( 'tel:' . preg_replace( '/\s+/', '', $telefon ) ); ?>">Zadzwoń: <?php echo esc_html( $telefon ); ?></a>
			<?php endif; ?>
			<a class="olech-btn olech-btn--secondary" href="#formularz">Zostaw zgłoszenie</a>
		</div>
	</div>
	<?php
	return ob_get_clean();
};
