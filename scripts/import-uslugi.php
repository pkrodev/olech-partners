<?php
/**
 * Import usług z data/uslugi.csv do CPT `usluga` — komenda `wp olech import-uslugi`.
 *
 * Treść landing page'a wczytywana z content/uslugi/{slug}.md (konwerter
 * niżej wspiera podzbiór Markdown: nagłówki ##/###, akapity oddzielone
 * pustą linią, listy "- ", inline **bold** i [tekst](url) — bez
 * zewnętrznej biblioteki, ten sam powód co brak parsera YAML dla FAQ
 * w inc/acf-pola.php).
 *
 * Idempotentny: dopasowanie po post_name+post_type przed insertem,
 * wp_set_object_terms z $append=false, pola ACF zawsze nadpisywane z CSV.
 *
 * KRYTYCZNE: blocks/faq/render.php buduje ścieżkę pliku FAQ z aktualnego
 * post_name w momencie renderu (data/faq/usluga-{post_name}.json) — dlatego
 * ten importer musi wymusić, że zapisany slug jest dokładnie tym z CSV,
 * inaczej blok FAQ po cichu przestanie znajdować swój plik.
 *
 * UWAGA: ten plik jest ładowany przez `require:` w wp-cli.yml, czyli PRZED
 * pełnym bootstrapem WordPressa (WP-CLI rejestruje komendy zanim załaduje
 * WP) — ABSPATH jeszcze nie istnieje na tym etapie. Standardowy guard
 * `defined('ABSPATH') || exit;` ubiłby tu cicho cały proces WP-CLI dla
 * KAŻDEJ komendy, nie tylko tej. Jedyny potrzebny guard to WP_CLI poniżej.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

function olech_read_uslugi_csv( string $path ): array {
	$rows   = array();
	$handle = fopen( $path, 'r' );
	if ( false === $handle ) {
		return $rows;
	}
	$header = fgetcsv( $handle );
	while ( ( $data = fgetcsv( $handle ) ) !== false ) {
		if ( 1 === count( $data ) && '' === trim( (string) $data[0] ) ) {
			continue;
		}
		$rows[] = array_combine( $header, $data );
	}
	fclose( $handle );
	return $rows;
}

function olech_ensure_kategoria_terms( array $rows ): array {
	$map = array();
	foreach ( $rows as $row ) {
		$name = trim( $row['kategoria_uslugi'] ?? '' );
		if ( '' === $name || isset( $map[ $name ] ) ) {
			continue;
		}
		$term = term_exists( $name, 'kategoria_uslugi' );
		if ( ! $term ) {
			$term = wp_insert_term( $name, 'kategoria_uslugi' );
		}
		if ( is_wp_error( $term ) ) {
			WP_CLI::error( "Nie udało się utworzyć kategorii '{$name}': " . $term->get_error_message() );
		}
		$map[ $name ] = (int) $term['term_id'];
	}
	return $map;
}

function olech_md_lite_inline( string $text ): string {
	$text = htmlspecialchars( $text, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	$text = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text );
	$text = preg_replace( '/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $text );
	return $text;
}

function olech_md_lite_to_blocks( string $md ): string {
	$md     = trim( str_replace( "\r\n", "\n", $md ) );
	$chunks = preg_split( '/\n\s*\n/', $md );
	$out    = '';

	foreach ( $chunks as $chunk ) {
		$chunk = trim( $chunk );
		if ( '' === $chunk ) {
			continue;
		}

		if ( preg_match( '/^###\s+(.+)$/', $chunk, $m ) ) {
			$out .= '<!-- wp:heading {"level":3} -->' . "\n" . '<h3 class="wp-block-heading">' . olech_md_lite_inline( $m[1] ) . '</h3>' . "\n" . '<!-- /wp:heading -->' . "\n\n";
			continue;
		}
		if ( preg_match( '/^##\s+(.+)$/', $chunk, $m ) ) {
			$out .= '<!-- wp:heading -->' . "\n" . '<h2 class="wp-block-heading">' . olech_md_lite_inline( $m[1] ) . '</h2>' . "\n" . '<!-- /wp:heading -->' . "\n\n";
			continue;
		}

		$lines   = explode( "\n", $chunk );
		$is_list = true;
		foreach ( $lines as $line ) {
			if ( ! preg_match( '/^-\s+/', $line ) ) {
				$is_list = false;
				break;
			}
		}
		if ( $is_list ) {
			$items = '';
			foreach ( $lines as $line ) {
				$items .= '<li>' . olech_md_lite_inline( preg_replace( '/^-\s+/', '', $line ) ) . '</li>';
			}
			$out .= '<!-- wp:list -->' . "\n" . '<ul class="wp-block-list">' . $items . '</ul>' . "\n" . '<!-- /wp:list -->' . "\n\n";
			continue;
		}

		$out .= '<!-- wp:paragraph -->' . "\n" . '<p>' . olech_md_lite_inline( str_replace( "\n", ' ', $chunk ) ) . '</p>' . "\n" . '<!-- /wp:paragraph -->' . "\n\n";
	}

	return trim( $out );
}

WP_CLI::add_command( 'olech import-uslugi', function ( $args, $assoc_args ) {
	$csv_path = $assoc_args['csv'] ?? ABSPATH . 'data/uslugi.csv';
	$dry_run  = isset( $assoc_args['dry-run'] );
	$only     = isset( $assoc_args['only'] ) ? array_map( 'trim', explode( ',', $assoc_args['only'] ) ) : null;

	if ( ! file_exists( $csv_path ) ) {
		WP_CLI::error( "CSV nie znaleziony: {$csv_path}" );
	}

	$rows = olech_read_uslugi_csv( $csv_path );
	if ( empty( $rows ) ) {
		WP_CLI::error( 'CSV jest puste.' );
	}

	$author_login = $assoc_args['user'] ?? 'admin';
	$author       = get_user_by( 'login', $author_login );
	if ( ! $dry_run && ! $author ) {
		WP_CLI::error( "Nie znaleziono użytkownika '{$author_login}' (--user=login)." );
	}

	$term_map     = $dry_run ? array() : olech_ensure_kategoria_terms( $rows );
	$target_slugs = $only ?? wp_list_pluck( $rows, 'slug' );
	$processed    = array();

	foreach ( $rows as $row ) {
		$slug = trim( $row['slug'] ?? '' );
		if ( '' === $slug ) {
			WP_CLI::warning( 'Pominięto wiersz bez sluga.' );
			continue;
		}
		if ( ! in_array( $slug, $target_slugs, true ) ) {
			continue;
		}

		$existing = get_posts( array(
			'post_type'      => 'usluga',
			'name'           => $slug,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );

		if ( $dry_run ) {
			WP_CLI::log( ( $existing ? 'UPDATE ' : 'INSERT ' ) . $slug );
			continue;
		}

		$content_path = ABSPATH . "content/uslugi/{$slug}.md";
		if ( ! file_exists( $content_path ) ) {
			WP_CLI::error( "Brak pliku treści: {$content_path}" );
		}
		$body = olech_md_lite_to_blocks( (string) file_get_contents( $content_path ) );

		$postarr = array(
			'post_type'    => 'usluga',
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_title'   => $row['nazwa'],
			'post_excerpt' => $row['excerpt'] ?? '',
			'post_content' => $body,
			'menu_order'   => (int) ( $row['menu_order'] ?? 0 ),
			'post_author'  => $author->ID,
		);

		if ( $existing ) {
			$postarr['ID'] = $existing[0];
			$post_id       = wp_update_post( $postarr, true );
		} else {
			$post_id = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $post_id ) ) {
			WP_CLI::error( "Błąd zapisu '{$slug}': " . $post_id->get_error_message() );
		}

		$actual_slug = get_post_field( 'post_name', $post_id );
		if ( $actual_slug !== $slug ) {
			WP_CLI::error( "Kolizja sluga: '{$slug}' zapisany jako '{$actual_slug}' (ID {$post_id}). Sprawdź, czy istnieje inny post/strona o tym slugu." );
		}

		update_field( 'cena_od', '' !== ( $row['cena_od'] ?? '' ) ? (float) $row['cena_od'] : '', $post_id );
		update_field( 'cena_do', '' !== ( $row['cena_do'] ?? '' ) ? (float) $row['cena_do'] : '', $post_id );
		update_field( 'jednostka_ceny', $row['jednostka_ceny'] ?? '', $post_id );

		$kategoria = trim( $row['kategoria_uslugi'] ?? '' );
		if ( '' !== $kategoria && isset( $term_map[ $kategoria ] ) ) {
			wp_set_object_terms( $post_id, array( $term_map[ $kategoria ] ), 'kategoria_uslugi', false );
		}

		$processed[ $slug ] = $post_id;
		WP_CLI::log( ( $existing ? 'Zaktualizowano: ' : 'Utworzono: ' ) . "{$slug} (ID {$post_id})" );
	}

	if ( $dry_run ) {
		WP_CLI::success( 'Dry-run — nic nie zapisano.' );
		return;
	}

	// Pass 2: relacje uslugi_powiazane — rozwiązywane po tym, jak WSZYSTKIE
	// usługi z CSV istnieją w bazie (nie tylko te z --only), żeby częściowy
	// przebieg nie fałszował ostrzeżeń o "nieznanym" slugu.
	$all_ids = array();
	foreach ( $rows as $row ) {
		$slug = trim( $row['slug'] ?? '' );
		if ( '' === $slug ) {
			continue;
		}
		$found = get_posts( array(
			'post_type'      => 'usluga',
			'name'           => $slug,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );
		if ( $found ) {
			$all_ids[ $slug ] = $found[0];
		}
	}

	foreach ( $rows as $row ) {
		$slug = trim( $row['slug'] ?? '' );
		if ( ! isset( $processed[ $slug ] ) ) {
			continue;
		}
		$related_ids = array();
		foreach ( array_filter( array_map( 'trim', explode( '|', $row['uslugi_powiazane'] ?? '' ) ) ) as $rel_slug ) {
			if ( isset( $all_ids[ $rel_slug ] ) ) {
				$related_ids[] = $all_ids[ $rel_slug ];
			} else {
				WP_CLI::warning( "Nieznany slug powiązany '{$rel_slug}' w wierszu '{$slug}'." );
			}
		}
		update_field( 'uslugi_powiazane', $related_ids, $processed[ $slug ] );
	}

	WP_CLI::success( 'Import usług zakończony: ' . count( $processed ) . ' wpisów.' );
} );
