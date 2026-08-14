<?php
/**
 * Hub wojewódzki (/obszar-dzialania/{wojewodztwo}/) — lista miast w terminie.
 */

return function () {
	$term = get_queried_object();
	if ( ! ( $term instanceof WP_Term ) ) {
		return '';
	}

	$lokalizacje = get_posts( array(
		'post_type'      => 'lokalizacja',
		'posts_per_page' => 100,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
		'tax_query'      => array( array(
			'taxonomy' => 'wojewodztwo',
			'field'    => 'term_id',
			'terms'    => $term->term_id,
		) ),
	) );

	if ( ! $lokalizacje ) {
		return '<p>{{LOREM: brak jeszcze opublikowanych lokalizacji w tym województwie}}</p>';
	}

	ob_start();
	?>
	<ul class="olech-lokalizacje-wojewodztwa">
		<?php foreach ( $lokalizacje as $lokalizacja ) : ?>
			<li><a href="<?php echo esc_url( get_permalink( $lokalizacja ) ); ?>"><?php echo esc_html( get_the_title( $lokalizacja ) ); ?></a></li>
		<?php endforeach; ?>
	</ul>
	<?php
	return ob_get_clean();
};
