<?php
/**
 * Obrazek z assets/img motywu (nie z biblioteki mediów) — dynamiczny
 * blok zamiast surowego <img> w templates/*.html, bo te pliki NIE
 * wykonują PHP. Poprzednia wersja miała ścieżki zahardkodowane od
 * korzenia domeny ("/wp-content/themes/olech/..."), co zakładało
 * WordPressa zainstalowanego w katalogu głównym — złamało się to przy
 * realnym wdrożeniu na home.pl, gdzie WordPress stoi w podkatalogu
 * (/autoinstalator/wordpressplugins/): przeglądarka szukała plików od
 * korzenia domeny i trafiała w zupełnie inną stronę klienta, która tam
 * już stała. get_template_directory_uri() zawsze zwraca poprawny,
 * absolutny URL niezależnie od tego, gdzie stoi instalacja.
 */

return function ( $attributes ) {
	$plik = $attributes['plik'] ?? '';
	if ( ! $plik ) {
		return '';
	}

	$src    = esc_url( get_template_directory_uri() . '/assets/img/' . ltrim( $plik, '/' ) );
	$alt    = esc_attr( $attributes['alt'] ?? '' );
	$szer   = (int) ( $attributes['szerokosc'] ?? 0 );
	$wys    = (int) ( $attributes['wysokosc'] ?? 0 );
	$podpis = $attributes['podpis'] ?? '';
	$klasa  = $attributes['klasaFigury'] ?? '';

	$wymiary = '';
	if ( $szer ) {
		$wymiary .= ' width="' . $szer . '"';
	}
	if ( $wys ) {
		$wymiary .= ' height="' . $wys . '"';
	}

	$img = sprintf(
		'<img src="%s" alt="%s"%s loading="lazy" decoding="async" />',
		$src,
		$alt,
		$wymiary
	);

	if ( ! $podpis && ! $klasa ) {
		return $img;
	}

	return sprintf(
		'<figure class="%s">%s%s</figure>',
		esc_attr( $klasa ),
		$img,
		$podpis ? '<figcaption>' . esc_html( $podpis ) . '</figcaption>' : ''
	);
};
