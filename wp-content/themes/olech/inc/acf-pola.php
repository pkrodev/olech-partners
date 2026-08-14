<?php
/**
 * Pola ACF — model danych z sekcji 6 i 9 CLAUDE.md.
 *
 * Budowane na darmowej wersji ACF: Repeater i Flexible Content są Pro-only
 * i nie są tu używane. Listy o zmiennej długości (unikalne_gminy) to pola
 * textarea, jedna wartość na linię — do zamiany na Repeater po
 * doinstalowaniu ACF Pro (licencja klienta, sekcja 17).
 *
 * FAQ celowo nie ma tu pola: ładowane bezpośrednio z data/faq/*.yml
 * w szablonach (sekcja 4), nie przez ACF.
 *
 * Nazwy pól w grupie „Lokalizacja” są identyczne z nazwami kolumn
 * w data/miasta.csv (sekcja 6.2) — ułatwia to 1:1 mapowanie w przyszłym
 * importerze WP-CLI (sekcja 16, punkt 6).
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'acf_add_local_field_group' ) ) {
	return;
}

add_action( 'acf/init', 'olech_register_acf_lokalizacja' );
function olech_register_acf_lokalizacja() {
	acf_add_local_field_group( array(
		'key'      => 'group_olech_lokalizacja',
		'title'    => 'Lokalizacja — dane miasta',
		'fields'   => array(
			array(
				'key'          => 'field_olech_nazwa_miejscownik',
				'label'        => 'Nazwa (miejscownik)',
				'name'         => 'nazwa_miejscownik',
				'type'         => 'text',
				'instructions' => 'np. „w Piasecznie”.',
			),
			array(
				'key'          => 'field_olech_nazwa_dopelniacz',
				'label'        => 'Nazwa (dopełniacz)',
				'name'         => 'nazwa_dopelniacz',
				'type'         => 'text',
				'instructions' => 'np. „Piaseczna”.',
			),
			array(
				'key'   => 'field_olech_ludnosc',
				'label' => 'Ludność',
				'name'  => 'ludnosc',
				'type'  => 'number',
			),
			array(
				'key'     => 'field_olech_tier',
				'label'   => 'Tier',
				'name'    => 'tier',
				'type'    => 'select',
				'choices' => array(
					'1' => '1 — pełna treść (900–1500 słów)',
					'2' => '2 — standard (500–700 słów)',
					'3' => '3 — podstawowa (300–450 słów)',
				),
			),
			array(
				'key'   => 'field_olech_unikalne_sad_okregowy',
				'label' => 'Sąd okręgowy',
				'name'  => 'unikalne_sad_okregowy',
				'type'  => 'text',
			),
			array(
				'key'          => 'field_olech_unikalne_sad_rejonowy',
				'label'        => 'Sąd rejonowy',
				'name'         => 'unikalne_sad_rejonowy',
				'type'         => 'text',
				'instructions' => 'Nazwa, adres, wydział rodzinny.',
			),
			array(
				'key'          => 'field_olech_unikalne_gminy',
				'label'        => 'Obsługiwane gminy',
				'name'         => 'unikalne_gminy',
				'type'         => 'textarea',
				'instructions' => 'Jedna gmina na linię, minimum 3 — warunek importu (sekcja 6.2 CLAUDE.md).',
			),
			array(
				'key'   => 'field_olech_unikalne_dystans_km',
				'label' => 'Dystans od Radomia (km)',
				'name'  => 'unikalne_dystans_km',
				'type'  => 'number',
			),
			array(
				'key'   => 'field_olech_unikalne_czas_dojazdu',
				'label' => 'Czas dojazdu (minuty)',
				'name'  => 'unikalne_czas_dojazdu',
				'type'  => 'number',
			),
			array(
				'key'          => 'field_olech_wspolpracownik_id',
				'label'        => 'ID współpracownika',
				'name'         => 'wspolpracownik_id',
				'type'         => 'text',
				'instructions' => 'Odniesienie do data/wspolpracownicy.csv. Puste = brak przypisania.',
			),
			array(
				'key'   => 'field_olech_fala',
				'label' => 'Fala publikacji',
				'name'  => 'fala',
				'type'  => 'number',
				'min'   => 1,
				'max'   => 10,
			),
		),
		'location' => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'lokalizacja',
				),
			),
		),
	) );
}

add_action( 'acf/init', 'olech_register_acf_usluga' );
function olech_register_acf_usluga() {
	acf_add_local_field_group( array(
		'key'      => 'group_olech_usluga',
		'title'    => 'Usługa — dane',
		'fields'   => array(
			array(
				'key'          => 'field_olech_cena_od',
				'label'        => 'Cena od',
				'name'         => 'cena_od',
				'type'         => 'number',
				'instructions' => 'Puste jeśli nieznane — publikujemy zakresy, nie sztywne stawki (sekcja 9).',
			),
			array(
				'key'          => 'field_olech_cena_do',
				'label'        => 'Cena do',
				'name'         => 'cena_do',
				'type'         => 'number',
				'instructions' => 'Dla ceny stałej (np. wariograf) wpisz tę samą wartość co „Cena od”.',
			),
			array(
				'key'     => 'field_olech_jednostka_ceny',
				'label'   => 'Jednostka ceny',
				'name'    => 'jednostka_ceny',
				'type'    => 'select',
				'choices'      => array(
					'zl'     => 'zł',
					'zl_h'   => 'zł/h',
					'zakres' => 'zakres',
					'ukryta' => 'ukryta (cena znana, celowo nie publikujemy)',
				),
				'instructions' => '„ukryta" = cena jest znana wewnętrznie, ale świadomie nie pokazujemy jej publicznie (sekcja 9 CLAUDE.md) — inne niż puste pola, które znaczą „jeszcze nie znamy ceny".',
			),
			array(
				'key'       => 'field_olech_uslugi_powiazane',
				'label'     => 'Usługi powiązane',
				'name'      => 'uslugi_powiazane',
				'type'      => 'relationship',
				'post_type' => array( 'usluga' ),
			),
			array(
				'key'       => 'field_olech_poradniki_powiazane',
				'label'     => 'Poradniki powiązane',
				'name'      => 'poradniki_powiazane',
				'type'      => 'relationship',
				'post_type' => array( 'post' ),
			),
		),
		'location' => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'usluga',
				),
			),
		),
	) );
}

add_action( 'acf/init', 'olech_register_acf_realizacja' );
function olech_register_acf_realizacja() {
	acf_add_local_field_group( array(
		'key'      => 'group_olech_realizacja',
		'title'    => 'Realizacja — dane sprawy',
		'fields'   => array(
			array(
				'key'       => 'field_olech_usluga_powiazana',
				'label'     => 'Usługa',
				'name'      => 'usluga_powiazana',
				'type'      => 'post_object',
				'post_type' => array( 'usluga' ),
			),
			array(
				'key'          => 'field_olech_lokalizacja_powiazana',
				'label'        => 'Lokalizacja',
				'name'         => 'lokalizacja_powiazana',
				'type'         => 'post_object',
				'post_type'    => array( 'lokalizacja' ),
				'instructions' => 'Opcjonalne — do sekcji „Realizacja z regionu” na stronach miast tier 1.',
			),
			array(
				'key'          => 'field_olech_streszczenie',
				'label'        => 'Streszczenie',
				'name'         => 'streszczenie',
				'type'         => 'textarea',
				'instructions' => 'Krótki opis do karty. Wyłącznie realny, zanonimizowany przypadek (sekcja 2.1 i 17) — bez przykładów zmyślonych.',
			),
		),
		'location' => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'realizacja',
				),
			),
		),
	) );
}
