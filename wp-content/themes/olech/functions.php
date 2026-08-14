<?php
defined( 'ABSPATH' ) || exit;

require get_template_directory() . '/inc/post-types.php';
require get_template_directory() . '/inc/acf-pola.php';
require get_template_directory() . '/inc/ustawienia-firmy.php';
require get_template_directory() . '/inc/blocks.php';
require get_template_directory() . '/inc/formularz.php';
require get_template_directory() . '/inc/schema.php';
require get_template_directory() . '/inc/sitemap.php';

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'olech', get_template_directory() . '/languages' );

	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support(
		'html5',
		array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'script', 'style' )
	);
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'olech-style',
		get_stylesheet_uri(),
		array(),
		wp_get_theme()->get( 'Version' )
	);
} );

// Bez emoji-skryptów i zbędnych meta w <head> — sekcja 14 CLAUDE.md (wydajność).
add_action( 'init', function () {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
} );
