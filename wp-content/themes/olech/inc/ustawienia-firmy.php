<?php
/**
 * Dane firmowe (nazwa podmiotu, wpis MSWiA, licencja, kontakt, adres Radom).
 *
 * Ustawienia globalne, wspólne dla całej witryny (stopka, schema, sticky CTA).
 * ACF Options Page jest Pro-only (w darmowej wersji to tylko podgląd/upsell —
 * sprawdzone w kodzie pluginu), więc te dane trzymamy przez natywne
 * WordPress Settings API, bez zależności od żadnego pluginu.
 *
 * Odczyt w szablonach: olech_ustawienia_firmy( 'numer_mswia' ).
 */

defined( 'ABSPATH' ) || exit;

function olech_ustawienia_firmy_pola() {
	return array(
		'nazwa_podmiotu'  => 'Nazwa podmiotu (zgodna z wpisem MSWiA — sekcja 2.3 CLAUDE.md)',
		'numer_mswia'     => 'Numer wpisu MSWiA',
		'numer_licencji'  => 'Numer licencji detektywa',
		'telefon'         => 'Telefon',
		'whatsapp'        => 'WhatsApp',
		'email'           => 'E-mail',
		'adres_radom'     => 'Adres (wyłącznie Radom — jedyny dozwolony w LocalBusiness, sekcja 10)',
	);
}

add_action( 'admin_menu', function () {
	add_options_page(
		'Dane firmy',
		'Dane firmy',
		'manage_options',
		'olech-dane-firmy',
		'olech_render_ustawienia_firmy'
	);
} );

add_action( 'admin_init', function () {
	register_setting( 'olech_ustawienia_firmy', 'olech_ustawienia_firmy', array(
		'type'              => 'array',
		'sanitize_callback' => function ( $wartosci ) {
			$wartosci = is_array( $wartosci ) ? $wartosci : array();
			$czyste   = array();
			foreach ( array_keys( olech_ustawienia_firmy_pola() ) as $klucz ) {
				$wartosc          = isset( $wartosci[ $klucz ] ) ? wp_unslash( $wartosci[ $klucz ] ) : '';
				$czyste[ $klucz ] = 'email' === $klucz ? sanitize_email( $wartosc ) : sanitize_text_field( $wartosc );
			}
			return $czyste;
		},
	) );
} );

function olech_render_ustawienia_firmy() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$wartosci = get_option( 'olech_ustawienia_firmy', array() );
	?>
	<div class="wrap">
		<h1>Dane firmy</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'olech_ustawienia_firmy' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( olech_ustawienia_firmy_pola() as $klucz => $etykieta ) : ?>
					<tr>
						<th scope="row">
							<label for="olech_<?php echo esc_attr( $klucz ); ?>"><?php echo esc_html( $etykieta ); ?></label>
						</th>
						<td>
							<input
								type="text"
								id="olech_<?php echo esc_attr( $klucz ); ?>"
								name="olech_ustawienia_firmy[<?php echo esc_attr( $klucz ); ?>]"
								value="<?php echo esc_attr( $wartosci[ $klucz ] ?? '' ); ?>"
								class="regular-text"
							/>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Odczyt jednej wartości danych firmowych w szablonach.
 * Zwraca pusty string, jeśli dana jeszcze nie wpłynęła od klienta (sekcja 17).
 */
function olech_ustawienia_firmy( $klucz ) {
	$wartosci = get_option( 'olech_ustawienia_firmy', array() );
	return $wartosci[ $klucz ] ?? '';
}
