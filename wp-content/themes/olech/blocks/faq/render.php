<?php
/**
 * FAQ — ładowane z data/faq/*.json (nie ACF: darmowy ACF nie ma Repeatera,
 * a JSON nie wymaga żadnej biblioteki YAML — czyste json_decode()).
 *
 * Konwencja nazw plików:
 *   data/faq/usluga-{slug-uslugi}.json
 *   data/faq/miasto-tier-{1|2|3}.json
 *
 * Każdy plik: tablica obiektów {"pytanie": "...", "odpowiedz": "..."}.
 * Każde FAQ własne dla usługi (sekcja 8.2) — brak współdzielenia między usługami.
 */

return function () {
	$post_id   = get_the_ID();
	$post_type = $post_id ? get_post_type( $post_id ) : '';

	if ( 'usluga' === $post_type ) {
		$plik = ABSPATH . 'data/faq/usluga-' . get_post_field( 'post_name', $post_id ) . '.json';
	} elseif ( 'lokalizacja' === $post_type ) {
		$tier = get_field( 'tier', $post_id ) ?: '3';
		$plik = ABSPATH . 'data/faq/miasto-tier-' . $tier . '.json';
	} else {
		return '';
	}

	if ( ! file_exists( $plik ) ) {
		return '<div class="olech-sekcja olech-faq"><h2>Najczęściej zadawane pytania</h2><p>{{LOREM: pytania FAQ — plik ' . esc_html( basename( $plik ) ) . ' jeszcze nie istnieje w data/faq/}}</p></div>';
	}

	$dane = json_decode( (string) file_get_contents( $plik ), true );
	if ( ! is_array( $dane ) || empty( $dane ) ) {
		return '';
	}

	$schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => array(),
	);

	ob_start();
	?>
	<div class="olech-sekcja olech-faq">
		<h2>Najczęściej zadawane pytania</h2>
		<?php foreach ( $dane as $pozycja ) :
			if ( empty( $pozycja['pytanie'] ) || empty( $pozycja['odpowiedz'] ) ) {
				continue;
			}
			$schema['mainEntity'][] = array(
				'@type'          => 'Question',
				'name'           => $pozycja['pytanie'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $pozycja['odpowiedz'],
				),
			);
			?>
			<details class="olech-faq__pozycja">
				<summary><?php echo esc_html( $pozycja['pytanie'] ); ?></summary>
				<div><?php echo wp_kses_post( wpautop( $pozycja['odpowiedz'] ) ); ?></div>
			</details>
		<?php endforeach; ?>
	</div>
	<?php if ( $schema['mainEntity'] ) : ?>
		<script type="application/ld+json"><?php echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
	<?php endif; ?>
	<?php
	return ob_get_clean();
};
