<?php
/**
 * Dane prawne w stopce — checklist pkt 23 CLAUDE.md: numer wpisu MSWiA i
 * licencji na każdej stronie. Czyta z Ustawienia → Dane firmy
 * (inc/ustawienia-firmy.php), każde pole osobno gracefully degraduje do
 * {{LOREM}}, jeśli jeszcze puste.
 */

return function () {
	$nazwa    = olech_ustawienia_firmy( 'nazwa_podmiotu' );
	$mswia    = olech_ustawienia_firmy( 'numer_mswia' );
	$licencja = olech_ustawienia_firmy( 'numer_licencji' );
	$krs      = olech_ustawienia_firmy( 'krs' );
	$nip      = olech_ustawienia_firmy( 'nip' );
	$regon    = olech_ustawienia_firmy( 'regon' );
	$siedziba = olech_ustawienia_firmy( 'adres_siedziby' );

	$czesci = array(
		$nazwa ? esc_html( $nazwa ) : '{{LOREM: dokładna nazwa prawna podmiotu}}',
		'Wpis MSWiA: ' . ( $mswia ? esc_html( $mswia ) : '{{LOREM: numer wpisu MSWiA}}' ),
		'Licencja detektywa: ' . ( $licencja ? esc_html( $licencja ) : '{{LOREM: numer licencji}}' ),
	);

	if ( $krs ) {
		$czesci[] = 'KRS: ' . esc_html( $krs );
	}
	if ( $nip ) {
		$czesci[] = 'NIP: ' . esc_html( $nip );
	}
	if ( $regon ) {
		$czesci[] = 'REGON: ' . esc_html( $regon );
	}
	if ( $siedziba ) {
		$czesci[] = esc_html( $siedziba );
	}

	return '<p class="olech-stopka-prawna">' . implode( ' &middot; ', $czesci ) . '</p>';
};
