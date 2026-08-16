<?php
defined( 'ABSPATH' ) || exit;

require get_template_directory() . '/inc/post-types.php';
require get_template_directory() . '/inc/acf-pola.php';
require get_template_directory() . '/inc/ustawienia-firmy.php';
require get_template_directory() . '/inc/blocks.php';
require get_template_directory() . '/inc/formularz.php';
require get_template_directory() . '/inc/schema.php';
require get_template_directory() . '/inc/sitemap.php';
require get_template_directory() . '/inc/wydajnosc.php';

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'olech', get_template_directory() . '/languages' );

	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'custom-logo', array(
		'height'      => 200,
		'width'       => 200,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support(
		'html5',
		array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'script', 'style' )
	);
} );

/**
 * Strony z sekcją hero (pełnoekranowe, ciemne zdjęcie na górze) — nagłówek
 * na tych stronach chowa się do momentu przewinięcia, tak jak na stronie
 * głównej (rozszerzone w sesji 2026-08-16 na wszystkie strony z hero, nie
 * tylko front-page). Strony BEZ hero (kontakt, poradnik, dziękujemy...)
 * celowo nie są tu wymienione — tam nagłówek musi być widoczny od razu,
 * bo nie ma czarnego tła hero, na którym mógłby się "ukrywać".
 */
function olech_ma_hero() {
	return is_front_page() || is_singular( 'usluga' ) || is_post_type_archive( 'usluga' );
}

add_filter( 'body_class', function ( $klasy ) {
	if ( olech_ma_hero() ) {
		$klasy[] = 'olech-ma-hero';
	}
	return $klasy;
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'olech-style',
		get_stylesheet_uri(),
		array(),
		wp_get_theme()->get( 'Version' )
	);

	if ( olech_ma_hero() ) {
		wp_enqueue_script(
			'olech-header-scroll',
			get_template_directory_uri() . '/assets/js/header-scroll.js',
			array(),
			wp_get_theme()->get( 'Version' ),
			array( 'strategy' => 'defer' )
		);
	}

	if ( is_front_page() ) {
		wp_enqueue_script(
			'olech-parallax',
			get_template_directory_uri() . '/assets/js/parallax.js',
			array(),
			wp_get_theme()->get( 'Version' ),
			array( 'strategy' => 'defer' )
		);
	}

	// Animacje "wjazdu" przy scrollu (.olech-reveal) — używane m.in. przez
	// blocks/uslugi-karty i sekcje strony głównej. Jeden mały skrypt,
	// bez zależności, deferred, więc nie blokuje renderowania.
	wp_enqueue_script(
		'olech-scroll-reveal',
		get_template_directory_uri() . '/assets/js/scroll-reveal.js',
		array(),
		wp_get_theme()->get( 'Version' ),
		array( 'strategy' => 'defer' )
	);
} );

/**
 * Klasa .js-reveal na <html> tylko gdy JS faktycznie działa — dzięki temu
 * CSS chowające .olech-reveal przed animacją (opacity:0) jest zaszyte pod
 * tą klasą i nigdy nie ukryje treści użytkownikom bez JS (np. część
 * czytników ekranu, wyłączony JS). Musi wykonać się jak najwcześniej,
 * stąd inline w <head>, nie osobny plik.
 */
add_action( 'wp_head', function () {
	echo '<script>document.documentElement.classList.add("js-reveal");</script>' . "\n";
}, 1 );

/**
 * Naprawa linków wewnętrznych, gdy WordPress stoi w podkatalogu, nie w
 * korzeniu domeny (diagnoza sesji 2026-08-16 na żywym serwerze home.pl,
 * FTP: /public_html/autoinstalator/wordpressplugins/ — instalacja
 * tymczasowa/podglądowa dla klienta, nie docelowa domena z sekcji 13
 * CLAUDE.md). Znaleziony realny powód "404 po kliknięciu w menu na
 * telefonie": blok Nawigacja (core/navigation) zawsze renderuje odnośniki
 * wewnętrzne jako ścieżki względem KORZENIA domeny (np. href="/kontakt/"),
 * nigdy względem faktycznego katalogu instalacji — to zachowanie rdzenia
 * WP, nie błąd tego motywu. Lokalnie niewidoczne, bo DDEV stoi w korzeniu
 * swojej domeny (olech.ddev.site/), więc "/kontakt/" tam trafia poprawnie.
 * Na home.pl "/kontakt/" ląduje w korzeniu CAŁEGO serwera (gdzie stoi
 * inna strona klienta), nie w podkatalogu z WordPressem — stąd 404 przy
 * każdym kliknięciu w menu, mimo że bezpośredni URL działał (curl/testy
 * trafiały w poprawny, pełny adres, nie w link z menu).
 * Ten sam problem dotyczy też pojedynczych zahardkodowanych odnośników
 * względnych wpisanych ręcznie w szablonach (np. przycisk "Dowiedz się
 * więcej" w front-page.html, href="/uslugi/badanie-wariografem/") — stąd
 * naprawa na poziomie render_block (każdy blok), nie tylko nawigacji.
 * Gdy instalacja stoi w korzeniu domeny (produkcja docelowa), prefiks jest
 * pusty i filtr nic nie robi.
 */
add_filter( 'render_block', function ( $block_content ) {
	static $prefiks = null;
	static $wzorzec = null;
	if ( null === $prefiks ) {
		$prefiks = untrailingslashit( (string) wp_parse_url( home_url(), PHP_URL_PATH ) );
		// render_block wywołuje się dla każdego poziomu zagnieżdżenia bloków
		// (blok-rodzic dostaje do filtrowania już przefiltrowaną treść dzieci),
		// więc bez wykluczenia już-doklejonego prefiksu w ujemnym lookahead
		// ta sama treść dostawałaby prefiks wielokrotnie (raz na poziom).
		$wzorzec = '#href="/(?!' . preg_quote( ltrim( $prefiks, '/' ), '#' ) . '/)#';
	}
	if ( '' === $prefiks || false === strpos( $block_content, 'href="/' ) ) {
		return $block_content;
	}
	return preg_replace( $wzorzec, 'href="' . $prefiks . '/', $block_content );
} );

// Bez emoji-skryptów i zbędnych meta w <head> — sekcja 14 CLAUDE.md (wydajność).
add_action( 'init', function () {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
} );
