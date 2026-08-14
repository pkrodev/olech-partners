<?php
/**
 * Rejestracja własnych bloków serwerowych (blocks/*).
 *
 * Bez zależności od ACF Blocks (Pro-only) — zwykłe register_block_type()
 * z metadanymi w block.json i render.php zwracającym callback.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	foreach ( glob( get_template_directory() . '/blocks/*', GLOB_ONLYDIR ) as $dir ) {
		$renderer = $dir . '/render.php';
		if ( ! file_exists( $dir . '/block.json' ) || ! file_exists( $renderer ) ) {
			continue;
		}
		register_block_type( $dir, array(
			'render_callback' => require $renderer,
		) );
	}
} );
