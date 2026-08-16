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

// Bez emoji-skryptów i zbędnych meta w <head> — sekcja 14 CLAUDE.md (wydajność).
add_action( 'init', function () {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
} );
