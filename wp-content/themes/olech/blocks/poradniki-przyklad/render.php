<?php
/**
 * Kilka ostatnich artykułów poradnika, do wstawienia na stronach usług.
 *
 * Sekcja 8.3: każda opublikowana strona ma linkować do min. 1 artykułu
 * poradnika. Blok `powiazane` z polem `poradniki_powiazane` (ACF
 * relationship) zależy od ręcznego ustawienia relacji przez redaktora —
 * jeśli nikt jej nie ustawi, strona usługi zostaje bez tego linku mimo
 * istniejących artykułów. Ten blok jest automatycznym fallbackiem
 * niezależnym od ręcznej pracy redakcyjnej — ta sama logika co
 * lokalizacje-przyklad.
 */

return function () {
	$wyklucz = 'post' === get_post_type() ? array( get_the_ID() ) : array();

	$artykuly = get_posts( array(
		'post_type'      => 'post',
		'posts_per_page' => 4,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
		'post__not_in'   => $wyklucz,
	) );

	if ( ! $artykuly ) {
		return '<div class="olech-sekcja olech-poradniki-przyklad"><p>{{LOREM: brak jeszcze opublikowanych artykułów poradnika do wyświetlenia}}</p></div>';
	}

	ob_start();
	?>
	<div class="olech-sekcja olech-poradniki-przyklad">
		<h2>Z poradnika</h2>
		<ul>
			<?php foreach ( $artykuly as $artykul ) : ?>
				<li><a href="<?php echo esc_url( get_permalink( $artykul ) ); ?>"><?php echo esc_html( get_the_title( $artykul ) ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
	return ob_get_clean();
};
