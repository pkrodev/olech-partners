<?php
/**
 * Obsługa formularza kontaktowego (blocks/formularz-kontaktowy) — bez
 * pluginu, natywny WP: nonce + honeypot + wp_mail(), przekierowanie na
 * osobny URL podziękowania (checklist pkt 4, sekcja 15).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_nopriv_olech_formularz', 'olech_obsluz_formularz' );
add_action( 'admin_post_olech_formularz', 'olech_obsluz_formularz' );
function olech_obsluz_formularz() {
	if (
		! isset( $_POST['olech_formularz_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['olech_formularz_nonce'] ) ), 'olech_formularz' )
	) {
		wp_die( 'Nieprawidłowe żądanie.', 'Błąd', array( 'response' => 403 ) );
	}

	// Honeypot — pole niewidoczne dla ludzi, wypełniane tylko przez boty.
	if ( ! empty( $_POST['www'] ) ) {
		wp_safe_redirect( home_url( '/kontakt/dziekujemy/' ) );
		exit;
	}

	$imie      = isset( $_POST['imie'] ) ? sanitize_text_field( wp_unslash( $_POST['imie'] ) ) : '';
	$kontakt   = isset( $_POST['kontakt'] ) ? sanitize_text_field( wp_unslash( $_POST['kontakt'] ) ) : '';
	$wiadomosc = isset( $_POST['wiadomosc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wiadomosc'] ) ) : '';
	$zgoda     = ! empty( $_POST['zgoda'] );

	if ( ! $imie || ! $kontakt || ! $wiadomosc || ! $zgoda ) {
		$powrot = wp_get_referer() ?: home_url( '/kontakt/' );
		wp_safe_redirect( add_query_arg( 'formularz', 'blad', $powrot ) );
		exit;
	}

	$odbiorca = olech_ustawienia_firmy( 'email' ) ?: get_option( 'admin_email' );
	$temat    = 'Nowe zgłoszenie ze strony — ' . $imie;
	$tresc    = "Imię: {$imie}\nKontakt: {$kontakt}\n\nWiadomość:\n{$wiadomosc}";

	wp_mail( $odbiorca, $temat, $tresc );

	wp_safe_redirect( home_url( '/kontakt/dziekujemy/' ) );
	exit;
}
