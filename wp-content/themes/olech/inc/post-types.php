<?php
/**
 * CPT i taksonomie — model danych z sekcji 6 CLAUDE.md.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'olech_register_post_types' );
function olech_register_post_types() {

	register_post_type( 'lokalizacja', array(
		'labels'        => array(
			'name'          => 'Lokalizacje',
			'singular_name' => 'Lokalizacja',
			'add_new_item'  => 'Dodaj lokalizację',
			'edit_item'     => 'Edytuj lokalizację',
			'new_item'      => 'Nowa lokalizacja',
			'view_item'     => 'Zobacz lokalizację',
			'search_items'  => 'Szukaj lokalizacji',
			'not_found'     => 'Brak lokalizacji',
			'all_items'     => 'Wszystkie lokalizacje',
			'menu_name'     => 'Lokalizacje',
		),
		'public'        => true,
		'show_in_rest'  => true,
		'menu_icon'     => 'dashicons-location-alt',
		'menu_position' => 6,
		'hierarchical'  => false,
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions' ),
		'taxonomies'    => array( 'wojewodztwo', 'powiat' ),
		// Hub /obszar-dzialania/ to osobna, ręcznie zbudowana strona (punkt 3
		// z sekcji 16 CLAUDE.md), nie automatyczne archiwum CPT.
		'has_archive'   => false,
		'rewrite'       => array( 'slug' => 'obszar-dzialania', 'with_front' => false ),
	) );

	register_post_type( 'usluga', array(
		'labels'        => array(
			'name'          => 'Usługi',
			'singular_name' => 'Usługa',
			'add_new_item'  => 'Dodaj usługę',
			'edit_item'     => 'Edytuj usługę',
			'new_item'      => 'Nowa usługa',
			'view_item'     => 'Zobacz usługę',
			'search_items'  => 'Szukaj usług',
			'not_found'     => 'Brak usług',
			'all_items'     => 'Wszystkie usługi',
			'menu_name'     => 'Usługi',
		),
		'public'        => true,
		'show_in_rest'  => true,
		'menu_icon'     => 'dashicons-portfolio',
		'menu_position' => 7,
		'hierarchical'  => false,
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions' ),
		'taxonomies'    => array( 'kategoria_uslugi' ),
		'has_archive'   => true, // /uslugi/ — hub usług.
		'rewrite'       => array( 'slug' => 'uslugi', 'with_front' => false ),
	) );

	register_post_type( 'realizacja', array(
		'labels'        => array(
			'name'          => 'Realizacje',
			'singular_name' => 'Realizacja',
			'add_new_item'  => 'Dodaj realizację',
			'edit_item'     => 'Edytuj realizację',
			'new_item'      => 'Nowa realizacja',
			'view_item'     => 'Zobacz realizację',
			'search_items'  => 'Szukaj realizacji',
			'not_found'     => 'Brak realizacji',
			'all_items'     => 'Wszystkie realizacje',
			'menu_name'     => 'Realizacje',
		),
		'public'        => true,
		'show_in_rest'  => true,
		'menu_icon'     => 'dashicons-analytics',
		'menu_position' => 8,
		'hierarchical'  => false,
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions' ),
		'has_archive'   => true, // /realizacje/
		'rewrite'       => array( 'slug' => 'realizacje', 'with_front' => false ),
	) );
}

add_action( 'init', 'olech_register_taxonomies' );
function olech_register_taxonomies() {

	register_taxonomy( 'wojewodztwo', array( 'lokalizacja' ), array(
		'labels'            => array(
			'name'          => 'Województwa',
			'singular_name' => 'Województwo',
			'menu_name'     => 'Województwa',
		),
		'hierarchical'      => true,
		'public'            => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'query_var'         => 'wojewodztwo',
		// Bez własnej generycznej reguły — kolidowałaby z regułą CPT lokalizacja,
		// bo oba mają wzorzec obszar-dzialania/{slug}/ (sekcja 5). Jawna reguła
		// dla 16 znanych slugów województw jest niżej, w olech_rewrite_wojewodztwo().
		'rewrite'           => false,
	) );

	register_taxonomy( 'powiat', array( 'lokalizacja' ), array(
		'labels'            => array(
			'name'          => 'Powiaty',
			'singular_name' => 'Powiat',
			'menu_name'     => 'Powiaty',
		),
		'hierarchical'      => true,
		'public'            => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'query_var'         => 'powiat',
		// Brak osobnej strony huba w architekturze URL (sekcja 5) — służy do
		// grupowania (sądy, sąsiednie miasta z tego samego powiatu).
		'rewrite'           => false,
	) );

	register_taxonomy( 'kategoria_uslugi', array( 'usluga' ), array(
		'labels'            => array(
			'name'          => 'Kategorie usług',
			'singular_name' => 'Kategoria usługi',
			'menu_name'     => 'Kategorie usług',
		),
		'hierarchical'      => true,
		'public'            => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'query_var'         => 'kategoria_uslugi',
		// Jak wyżej — brak osobnej strony huba w architekturze URL.
		'rewrite'           => false,
	) );
}

/**
 * Ręczna reguła dla /obszar-dzialania/{wojewodztwo}/ — hub wojewódzki.
 *
 * CPT lokalizacja i taksonomia wojewodztwo dzielą ten sam prefiks URL
 * (sekcja 5 CLAUDE.md — nie zmieniamy po pierwszej publikacji). Zamiast
 * generycznej reguły taksonomii, która przechwyciłaby też adresy
 * pojedynczych miast, rejestrujemy jawnie tylko 16 znanych slugów
 * województw z priorytetem 'top'. Wszystko inne pod tym prefiksem trafia
 * do reguły CPT lokalizacja.
 */
add_action( 'init', 'olech_rewrite_wojewodztwo', 20 );
function olech_rewrite_wojewodztwo() {
	$wojewodztwa = array(
		'dolnoslaskie', 'kujawsko-pomorskie', 'lubelskie', 'lubuskie',
		'lodzkie', 'malopolskie', 'mazowieckie', 'opolskie',
		'podkarpackie', 'podlaskie', 'pomorskie', 'slaskie',
		'swietokrzyskie', 'warminsko-mazurskie', 'wielkopolskie', 'zachodniopomorskie',
	);

	add_rewrite_rule(
		'^obszar-dzialania/(' . implode( '|', $wojewodztwa ) . ')/?$',
		'index.php?wojewodztwo=$matches[1]',
		'top'
	);
}

// Rejestracje CPT/taksonomii/reguł żyją w 'init' — po (re)aktywacji motywu
// trzeba przeflushować reguły przepisywania, inaczej permalinki 404-ują.
add_action( 'after_switch_theme', 'flush_rewrite_rules' );
