<?php
/**
 * CTA "Zadzwoń" — jeden dynamiczny blok, żeby numer telefonu miał jedno
 * źródło prawdy (Ustawienia → Dane firmy) nawet w statycznych szablonach
 * HTML (templates/*.html), gdzie nie da się wywołać PHP wprost.
 */

return function ( $attributes ) {
	$telefon = olech_ustawienia_firmy( 'telefon' );

	if ( ! $telefon ) {
		return '';
	}

	$styl          = $attributes['styl'] ?? 'zloto';
	$etykieta      = $attributes['etykieta'] ?? 'Zadzwoń';
	$pokaz_etykiete = $attributes['pokazEtykiete'] ?? true;

	$klasy = array(
		'zloto' => 'olech-btn olech-btn--zloto',
		'obrys' => 'olech-btn olech-btn--obrys',
		'tekst' => 'olech-cta-tel-tekst',
	);
	$klasa = $klasy[ $styl ] ?? $klasy['zloto'];

	$tresc = $pokaz_etykiete
		? esc_html( $etykieta ) . ': ' . esc_html( $telefon )
		: esc_html( $telefon );

	return sprintf(
		'<a class="%1$s" href="tel:%2$s">%3$s</a>',
		esc_attr( $klasa ),
		esc_attr( preg_replace( '/\s+/', '', $telefon ) ),
		$tresc
	);
};
