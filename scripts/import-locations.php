<?php
/**
 * Import lokalizacji z data/miasta.csv do CPT `lokalizacja` — komenda
 * `wp olech import-locations`.
 *
 * Przed importem uruchamia scripts/validate-miasta.py jako obowiązkową
 * bramkę (sekcja 6.2 CLAUDE.md: „Wiersz bez kompletu pól unikalne_* nie
 * przechodzi importu”) — import przerywa się, jeśli walidacja nie przejdzie
 * czysto. Pominięcie tego (--skip-validate) jest świadomym wyjątkiem, nie
 * trybem domyślnym — ten sam duch co „nie obchodź bramki flagą” przy
 * dedup-gate.py (sekcja 11 CLAUDE.md).
 *
 * DOMYŚLNIE ZAPISUJE JAKO DRAFT, NIE PUBLISH. Publikacja falami (sekcja 12)
 * ma twarde progi STOP między falami (sekcja 12.1) — import nie powinien
 * sam z siebie wypychać stron na żywo. Żeby faktycznie opublikować,
 * trzeba świadomie podać --publish. To odwracalny, bezpieczny domyślny
 * wybór: łatwiej ręcznie opublikować draft niż cofnąć publikację, którą
 * zaindeksował już Google.
 *
 * Idempotentny: dopasowanie po post_name+post_type przed insertem,
 * wp_set_object_terms z $append=false, pola ACF zawsze nadpisywane z CSV.
 * Draft → import ponowny nie nadpisuje ręcznie zmienionego post_status na
 * publish ani z powrotem na draft — status ustawiamy tylko przy tworzeniu
 * nowego posta lub gdy jawnie podano --publish.
 *
 * UWAGA: ten plik ładuje się przez `require:` w wp-cli.yml, PRZED pełnym
 * bootstrapem WordPressa — ABSPATH jeszcze nie istnieje na tym etapie.
 * Guard `defined('ABSPATH') || exit;` ubiłby tu cicho cały proces WP-CLI
 * dla KAŻDEJ komendy (odkryte przy punkcie 4, patrz scripts/import-uslugi.php).
 * Jedyny potrzebny guard to WP_CLI poniżej.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

function olech_read_miasta_csv( string $path ): array {
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

function olech_run_miasta_validator( string $csv_path, string $validator_path ): array {
	$cmd = sprintf(
		'python3 %s --csv %s 2>&1',
		escapeshellarg( $validator_path ),
		escapeshellarg( $csv_path )
	);
	exec( $cmd, $output, $exit_code );
	return array( $exit_code, implode( "\n", $output ) );
}

function olech_ensure_wojewodztwo_term( string $slug ): int {
	$term = term_exists( $slug, 'wojewodztwo' );
	if ( ! $term ) {
		$term = wp_insert_term( ucfirst( $slug ), 'wojewodztwo', array( 'slug' => $slug ) );
	}
	if ( is_wp_error( $term ) ) {
		WP_CLI::error( "Nie udało się utworzyć województwa '{$slug}': " . $term->get_error_message() );
	}
	return (int) $term['term_id'];
}

function olech_ensure_powiat_term( string $name ): int {
	$term = term_exists( $name, 'powiat' );
	if ( ! $term ) {
		$term = wp_insert_term( $name, 'powiat' );
	}
	if ( is_wp_error( $term ) ) {
		WP_CLI::error( "Nie udało się utworzyć powiatu '{$name}': " . $term->get_error_message() );
	}
	return (int) $term['term_id'];
}

WP_CLI::add_command( 'olech import-locations', function ( $args, $assoc_args ) {
	$csv_path       = $assoc_args['csv'] ?? ABSPATH . 'data/miasta.csv';
	$validator_path = $assoc_args['validator'] ?? ABSPATH . 'scripts/validate-miasta.py';
	$dry_run        = isset( $assoc_args['dry-run'] );
	$skip_validate  = isset( $assoc_args['skip-validate'] );
	$publish        = isset( $assoc_args['publish'] );
	$only           = isset( $assoc_args['only'] ) ? array_map( 'trim', explode( ',', $assoc_args['only'] ) ) : null;
	$fala_filter    = isset( $assoc_args['fala'] ) ? (int) $assoc_args['fala'] : null;

	if ( ! file_exists( $csv_path ) ) {
		WP_CLI::error( "CSV nie znaleziony: {$csv_path}" );
	}

	if ( ! $skip_validate ) {
		list( $exit_code, $output ) = olech_run_miasta_validator( $csv_path, $validator_path );
		if ( '' !== $output ) {
			WP_CLI::log( $output );
		}
		if ( 0 !== $exit_code ) {
			WP_CLI::error( 'Walidacja data/miasta.csv nie przeszła (scripts/validate-miasta.py) — import przerwany. Nie obchodź tego przez --skip-validate bez świadomej decyzji (sekcja 11 CLAUDE.md).' );
		}
	}

	$rows = olech_read_miasta_csv( $csv_path );
	if ( empty( $rows ) ) {
		WP_CLI::success( 'CSV nie zawiera wierszy danych — nic do zaimportowania (sekcja 16 pkt 5: dane wypełniane etapami).' );
		return;
	}

	$author_login = $assoc_args['user'] ?? 'admin';
	$author       = get_user_by( 'login', $author_login );
	if ( ! $dry_run && ! $author ) {
		WP_CLI::error( "Nie znaleziono użytkownika '{$author_login}' (--user=login)." );
	}

	$target_slugs = $only ?? wp_list_pluck( $rows, 'slug' );
	$processed    = array();

	foreach ( $rows as $row ) {
		$slug = trim( $row['slug'] ?? '' );
		if ( '' === $slug || ! in_array( $slug, $target_slugs, true ) ) {
			continue;
		}
		if ( null !== $fala_filter && (int) ( $row['fala'] ?? 0 ) !== $fala_filter ) {
			continue;
		}

		$existing = get_posts( array(
			'post_type'      => 'lokalizacja',
			'name'           => $slug,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );

		if ( $dry_run ) {
			WP_CLI::log( ( $existing ? 'UPDATE ' : 'INSERT ' ) . $slug . ( $publish ? ' (publish)' : ' (draft)' ) );
			continue;
		}

		$postarr = array(
			'post_type'   => 'lokalizacja',
			'post_name'   => $slug,
			'post_title'  => $row['nazwa'],
			'post_author' => $author->ID,
		);
		// Status ustawiamy tylko przy tworzeniu nowego posta albo gdy jawnie
		// podano --publish — nie chcemy przy re-imporcie cofać ręcznie
		// opublikowanej strony z powrotem do draftu.
		if ( ! $existing || $publish ) {
			$postarr['post_status'] = $publish ? 'publish' : 'draft';
		}

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

		update_field( 'nazwa_miejscownik', $row['nazwa_miejscownik'] ?? '', $post_id );
		update_field( 'nazwa_dopelniacz', $row['nazwa_dopelniacz'] ?? '', $post_id );
		update_field( 'ludnosc', '' !== trim( $row['ludnosc'] ?? '' ) ? (int) str_replace( array( ' ', "\xc2\xa0" ), '', $row['ludnosc'] ) : '', $post_id );
		update_field( 'tier', $row['tier'] ?? '', $post_id );
		update_field( 'unikalne_sad_okregowy', $row['unikalne_sad_okregowy'] ?? '', $post_id );
		update_field( 'unikalne_sad_rejonowy', $row['unikalne_sad_rejonowy'] ?? '', $post_id );

		// Konwencja tego repo: lista w jednej komórce CSV rozdzielona '|'
		// (ta sama co uslugi_powiazane w data/uslugi.csv). Pole ACF
		// unikalne_gminy to textarea, jedna gmina na linię.
		$gminy_raw = trim( $row['unikalne_gminy'] ?? '' );
		$gminy_txt = '' !== $gminy_raw
			? implode( "\n", array_filter( array_map( 'trim', explode( '|', $gminy_raw ) ) ) )
			: '';
		update_field( 'unikalne_gminy', $gminy_txt, $post_id );

		update_field( 'unikalne_dystans_km', '' !== trim( $row['unikalne_dystans_km'] ?? '' ) ? (float) str_replace( ',', '.', $row['unikalne_dystans_km'] ) : '', $post_id );
		update_field( 'unikalne_czas_dojazdu', '' !== trim( $row['unikalne_czas_dojazdu'] ?? '' ) ? (float) str_replace( ',', '.', $row['unikalne_czas_dojazdu'] ) : '', $post_id );
		update_field( 'wspolpracownik_id', $row['wspolpracownik_id'] ?? '', $post_id );
		update_field( 'fala', '' !== trim( $row['fala'] ?? '' ) ? (int) $row['fala'] : '', $post_id );

		$wojewodztwo = trim( $row['wojewodztwo'] ?? '' );
		if ( '' !== $wojewodztwo ) {
			wp_set_object_terms( $post_id, array( olech_ensure_wojewodztwo_term( $wojewodztwo ) ), 'wojewodztwo', false );
		}
		$powiat = trim( $row['powiat'] ?? '' );
		if ( '' !== $powiat ) {
			wp_set_object_terms( $post_id, array( olech_ensure_powiat_term( $powiat ) ), 'powiat', false );
		}

		$processed[ $slug ] = $post_id;
		WP_CLI::log( ( $existing ? 'Zaktualizowano: ' : 'Utworzono: ' ) . "{$slug} (ID {$post_id})" );
	}

	if ( $dry_run ) {
		WP_CLI::success( 'Dry-run — nic nie zapisano.' );
		return;
	}

	WP_CLI::success( 'Import lokalizacji zakończony: ' . count( $processed ) . ' wpisów.' );
} );
