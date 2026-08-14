<?php
/**
 * Zakres cenowy usługi. Cena stała — wpisz tę samą wartość w cena_od i
 * cena_do (patrz instrukcja pola w inc/acf-pola.php).
 *
 * jednostka_ceny = "ukryta" to inny przypadek niż puste cena_od/cena_do:
 * cena JEST znana wewnętrznie, ale świadomie nie publikujemy jej (sekcja 9
 * CLAUDE.md, decyzja 2026-08-14 dot. wariografu) — pokazujemy CTA
 * kontaktowe, nie {{LOREM}}, bo to nie brakujące dane klienta.
 */

return function () {
	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return '';
	}

	$od        = get_field( 'cena_od', $post_id );
	$do        = get_field( 'cena_do', $post_id );
	$jednostka = get_field( 'jednostka_ceny', $post_id );

	if ( 'ukryta' === $jednostka ) {
		return '<p class="olech-cena-uslugi olech-cena-uslugi--ukryta"><strong>Cena:</strong> ustalana indywidualnie podczas kontaktu — <a href="#formularz">zostaw zgłoszenie</a> albo zadzwoń.</p>';
	}

	$etykiety        = array( 'zl' => 'zł', 'zl_h' => 'zł/h', 'zakres' => 'zł' );
	$jednostka_tekst = $etykiety[ $jednostka ] ?? 'zł';

	if ( ! $od && ! $do ) {
		$tekst = '{{LOREM: zakres cenowy tej usługi}}';
	} elseif ( $od && $do && (float) $od === (float) $do ) {
		$tekst = sprintf( '%s %s', number_format_i18n( (float) $od ), $jednostka_tekst );
	} elseif ( $od && $do ) {
		$tekst = sprintf( 'od %s do %s %s', number_format_i18n( (float) $od ), number_format_i18n( (float) $do ), $jednostka_tekst );
	} else {
		$tekst = sprintf( 'od %s %s', number_format_i18n( (float) ( $od ?: $do ) ), $jednostka_tekst );
	}

	return '<p class="olech-cena-uslugi"><strong>Cena:</strong> ' . esc_html( $tekst ) . '</p>';
};
