<?php
/**
 * Pasek zaufania — dane z Ustawienia → Dane firmy (inc/ustawienia-firmy.php).
 */

return function () {
	$licencja = olech_ustawienia_firmy( 'numer_licencji' );
	$mswia    = olech_ustawienia_firmy( 'numer_mswia' );

	ob_start();
	?>
	<div class="olech-trust-bar">
		<span>Licencja detektywa: <?php echo $licencja ? esc_html( $licencja ) : '{{LOREM: numer licencji}}'; ?></span>
		<span>Wpis MSWiA: <?php echo $mswia ? esc_html( $mswia ) : '{{LOREM: numer wpisu MSWiA}}'; ?></span>
		<span>Tajemnica zawodowa gwarantowana ustawą</span>
	</div>
	<?php
	return ob_get_clean();
};
