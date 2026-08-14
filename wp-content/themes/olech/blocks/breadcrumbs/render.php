<?php
/**
 * Breadcrumbs — wizualne (<nav><ol>) + schema BreadcrumbList w jednym
 * miejscu (ten sam wzorzec co FAQPage w blocks/faq/render.php: jedno
 * źródło prawdy zamiast osobnego pliku na markup i osobnego na schema).
 *
 * Pominięte na stronie głównej (is_front_page) — breadcrumbs na
 * homepage nie mają sensu (nie ma "wyżej" niż strona główna).
 *
 * "Obszar działania" (/obszar-dzialania/) nie ma jeszcze własnej strony
 * (poza zakresem punktu 3) — link do niej pojawia się automatycznie,
 * gdy tylko taka strona (page o slugu "obszar-dzialania") powstanie;
 * do tego czasu renderuje się jako zwykły, nieklikalny tekst.
 */

return function () {
	if ( is_front_page() ) {
		return '';
	}

	$trail = array( array( 'label' => 'Strona główna', 'url' => home_url( '/' ) ) );

	if ( is_singular( 'usluga' ) ) {
		$trail[] = array( 'label' => 'Usługi', 'url' => get_post_type_archive_link( 'usluga' ) ?: null );
		$trail[] = array( 'label' => get_the_title(), 'url' => null );
	} elseif ( is_singular( 'lokalizacja' ) ) {
		$obszar = get_page_by_path( 'obszar-dzialania' );
		$trail[] = array( 'label' => 'Obszar działania', 'url' => $obszar ? get_permalink( $obszar ) : null );

		$wojewodztwa = get_the_terms( get_the_ID(), 'wojewodztwo' );
		if ( ! is_wp_error( $wojewodztwa ) && $wojewodztwa ) {
			$woj = $wojewodztwa[0];
			$trail[] = array( 'label' => $woj->name, 'url' => get_term_link( $woj ) ?: null );
		}
		$trail[] = array( 'label' => get_the_title(), 'url' => null );
	} elseif ( is_singular( 'post' ) ) {
		$strona_poradnika = (int) get_option( 'page_for_posts' );
		$trail[] = array(
			'label' => 'Poradnik',
			'url'   => $strona_poradnika ? get_permalink( $strona_poradnika ) : null,
		);
		$trail[] = array( 'label' => get_the_title(), 'url' => null );
	} elseif ( is_post_type_archive( 'usluga' ) ) {
		$trail[] = array( 'label' => post_type_archive_title( '', false ), 'url' => null );
	} elseif ( is_tax( 'wojewodztwo' ) ) {
		$obszar = get_page_by_path( 'obszar-dzialania' );
		$trail[] = array( 'label' => 'Obszar działania', 'url' => $obszar ? get_permalink( $obszar ) : null );
		$trail[] = array( 'label' => single_term_title( '', false ), 'url' => null );
	} elseif ( is_home() ) {
		$trail[] = array( 'label' => 'Poradnik', 'url' => null );
	} elseif ( is_page() ) {
		$trail[] = array( 'label' => get_the_title(), 'url' => null );
	} else {
		return '';
	}

	$schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => array(),
	);
	foreach ( $trail as $i => $item ) {
		$entry = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $item['label'],
		);
		if ( $item['url'] ) {
			$entry['item'] = $item['url'];
		}
		$schema['itemListElement'][] = $entry;
	}

	ob_start();
	?>
	<nav class="olech-breadcrumbs" aria-label="Okruszki nawigacyjne">
		<ol>
			<?php foreach ( $trail as $i => $item ) : ?>
				<li>
					<?php if ( $item['url'] && $i !== count( $trail ) - 1 ) : ?>
						<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
					<?php else : ?>
						<span aria-current="page"><?php echo esc_html( $item['label'] ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<script type="application/ld+json"><?php echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
	<?php
	return ob_get_clean();
};
