<?php
/**
 * Dane strukturalne (JSON-LD) wg tabeli z sekcji 10 CLAUDE.md.
 *
 * FAQPage jest już wyprowadzana bezpośrednio w blocks/faq/render.php (razem
 * z widoczną treścią FAQ — jedno źródło prawdy, ten sam wzorzec zastosowany
 * tu dla Organization/Service/Article). BreadcrumbList analogicznie żyje
 * w blocks/breadcrumbs/render.php, razem z widocznymi breadcrumbs.
 *
 * Review/AggregateRating i Person (/daniel-olech/) świadomie pominięte:
 * brak realnych opinii z GBP i brak jeszcze strony/danych Daniela Olecha
 * (sekcja 17) — sekcja 2.1 zabrania fabrykowania opinii i danych.
 *
 * Każda funkcja poniżej cicho nic nie wypisuje, jeśli brakuje danych
 * wymaganych do poprawnego schema — niekompletne/błędne dane strukturalne
 * są gorsze niż ich brak (błędy w Search Console).
 */

defined( 'ABSPATH' ) || exit;

function olech_schema_print( array $schema ) {
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}

/**
 * Organization + LocalBusiness — WYŁĄCZNIE strona główna, WYŁĄCZNIE adres
 * Radom (sekcja 10: zakaz LocalBusiness z innym adresem na stronach miast).
 */
function olech_schema_organization() {
	if ( ! is_front_page() ) {
		return;
	}

	$nazwa = olech_ustawienia_firmy( 'nazwa_podmiotu' );
	$adres = olech_ustawienia_firmy( 'adres_radom' );

	// Bez nazwy i adresu nie da się zbudować poprawnego LocalBusiness —
	// czeka na dane klienta (sekcja 17), nic nie wypisujemy.
	if ( '' === $nazwa || '' === $adres ) {
		return;
	}

	$schema = array(
		'@context' => 'https://schema.org',
		'@type'    => array( 'Organization', 'LocalBusiness' ),
		'name'     => $nazwa,
		'url'      => home_url( '/' ),
		'address'  => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $adres,
			'addressLocality' => 'Radom',
			'addressCountry'  => 'PL',
		),
	);

	$telefon = olech_ustawienia_firmy( 'telefon' );
	if ( '' !== $telefon ) {
		$schema['telephone'] = $telefon;
	}
	$email = olech_ustawienia_firmy( 'email' );
	if ( '' !== $email ) {
		$schema['email'] = $email;
	}

	olech_schema_print( $schema );
}
add_action( 'wp_head', 'olech_schema_organization' );

/**
 * Service — /uslugi/{x}/. Bez areaServed (to rozróżnienie z lokalizacją,
 * sekcja 10 wymienia areaServed tylko przy /obszar-dzialania/{miasto}/).
 */
function olech_schema_usluga() {
	if ( ! is_singular( 'usluga' ) ) {
		return;
	}
	$post_id = get_the_ID();
	$excerpt = get_the_excerpt( $post_id );
	if ( '' === trim( (string) get_the_title( $post_id ) ) ) {
		return;
	}

	$schema = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Service',
		'name'     => get_the_title( $post_id ),
		'url'      => get_permalink( $post_id ),
	);
	if ( '' !== $excerpt ) {
		$schema['description'] = $excerpt;
	}

	$nazwa_podmiotu = olech_ustawienia_firmy( 'nazwa_podmiotu' );
	if ( '' !== $nazwa_podmiotu ) {
		$schema['provider'] = array(
			'@type' => 'Organization',
			'name'  => $nazwa_podmiotu,
		);
	}

	olech_schema_print( $schema );
}
add_action( 'wp_head', 'olech_schema_usluga' );

/**
 * Service + areaServed — /obszar-dzialania/{miasto}/.
 */
function olech_schema_lokalizacja() {
	if ( ! is_singular( 'lokalizacja' ) ) {
		return;
	}
	$post_id = get_the_ID();
	$nazwa   = get_the_title( $post_id );
	if ( '' === trim( (string) $nazwa ) ) {
		return;
	}

	$schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'Service',
		'name'       => 'Usługi detektywistyczne — ' . $nazwa,
		'url'        => get_permalink( $post_id ),
		'areaServed' => array(
			'@type' => 'City',
			'name'  => $nazwa,
		),
	);

	$nazwa_podmiotu = olech_ustawienia_firmy( 'nazwa_podmiotu' );
	if ( '' !== $nazwa_podmiotu ) {
		$schema['provider'] = array(
			'@type' => 'Organization',
			'name'  => $nazwa_podmiotu,
		);
	}

	olech_schema_print( $schema );
}
add_action( 'wp_head', 'olech_schema_lokalizacja' );

/**
 * Article z author → Person — /poradnik/{x}/ (CPT `post`, sekcja 5 URL
 * architecture). Daniel Olech jako autor to potwierdzony fakt ze
 * specyfikacji klienta (sekcja 1: "Marka eksperta — Daniel Olech"), nie
 * wymyślony szczegół — ale bez dodatkowych, niepotwierdzonych pól
 * (jobTitle/hasCredential czekają na /daniel-olech/ i dane z sekcji 17).
 */
function olech_schema_artykul() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	$post_id = get_the_ID();
	if ( '' === trim( (string) get_the_title( $post_id ) ) ) {
		return;
	}

	$schema = array(
		'@context'         => 'https://schema.org',
		'@type'            => 'Article',
		'headline'         => get_the_title( $post_id ),
		'datePublished'    => get_the_date( DATE_W3C, $post_id ),
		'dateModified'     => get_the_modified_date( DATE_W3C, $post_id ),
		'mainEntityOfPage' => get_permalink( $post_id ),
		'author'           => array(
			'@type' => 'Person',
			'name'  => 'Daniel Olech',
		),
	);

	$nazwa_podmiotu = olech_ustawienia_firmy( 'nazwa_podmiotu' );
	if ( '' !== $nazwa_podmiotu ) {
		$schema['publisher'] = array(
			'@type' => 'Organization',
			'name'  => $nazwa_podmiotu,
		);
	}

	olech_schema_print( $schema );
}
add_action( 'wp_head', 'olech_schema_artykul' );
