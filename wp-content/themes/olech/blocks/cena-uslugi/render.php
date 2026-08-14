<?php
/**
 * Zakres cenowy usługi. Cena stała (np. wariograf) — wpisz tę samą wartość
 * w cena_od i cena_do (patrz instrukcja pola w inc/acf-pola.php).
 */

return function () {
	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return '';
	}

	$od        = get_field( 'cena_od', $post_id );
	$do        = get_field( 'cena_do', $post_id );
	$jednostka = get_field( 'jednostka_ceny', $post_id );

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
