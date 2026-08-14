<?php
/**
 * Kilka opublikowanych lokalizacji, do wstawienia na stronach usług.
 *
 * Sekcja 8.3: każda opublikowana strona ma linkować do min. 1 innej
 * lokalizacji — szablon single-usluga.html nie miał dotąd żadnego takiego
 * odnośnika (linki do lokalizacji istniały tylko NA stronach lokalizacji,
 * nie z powrotem ze stron usług). Ten blok domyka tę lukę.
 */

return function () {
	$lokalizacje = get_posts( array(
		'post_type'      => 'lokalizacja',
		'posts_per_page' => 6,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	) );

	if ( ! $lokalizacje ) {
		return '<div class="olech-sekcja olech-lokalizacje-przyklad"><p>{{LOREM: brak jeszcze opublikowanych lokalizacji do wyświetlenia}}</p></div>';
	}

	ob_start();
	?>
	<div class="olech-sekcja olech-lokalizacje-przyklad">
		<h2>Działamy też w Twojej okolicy</h2>
		<ul>
			<?php foreach ( $lokalizacje as $lokalizacja ) : ?>
				<li><a href="<?php echo esc_url( get_permalink( $lokalizacja ) ); ?>"><?php echo esc_html( get_the_title( $lokalizacja ) ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
	return ob_get_clean();
};
