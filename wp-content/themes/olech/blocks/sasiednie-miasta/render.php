<?php
/**
 * Sąsiednie miasta — najpierw ten sam powiat, dopełnienie z tego samego
 * województwa jeśli za mało wyników (sekcja 8.1 pkt 12: 4-6 linków).
 */

return function () {
	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return '';
	}

	$powiaty  = wp_get_post_terms( $post_id, 'powiat', array( 'fields' => 'ids' ) );
	$sasiedzi = array();

	if ( ! is_wp_error( $powiaty ) && $powiaty ) {
		$sasiedzi = get_posts( array(
			'post_type'      => 'lokalizacja',
			'posts_per_page' => 6,
			'post_status'    => 'publish',
			'post__not_in'   => array( $post_id ),
			'no_found_rows'  => true,
			'tax_query'      => array( array(
				'taxonomy' => 'powiat',
				'field'    => 'term_id',
				'terms'    => $powiaty,
			) ),
		) );
	}

	if ( count( $sasiedzi ) < 4 ) {
		$wojewodztwa = wp_get_post_terms( $post_id, 'wojewodztwo', array( 'fields' => 'ids' ) );
		if ( ! is_wp_error( $wojewodztwa ) && $wojewodztwa ) {
			$wykluczone = array_merge( array( $post_id ), wp_list_pluck( $sasiedzi, 'ID' ) );
			$dodatkowi  = get_posts( array(
				'post_type'      => 'lokalizacja',
				'posts_per_page' => 6 - count( $sasiedzi ),
				'post_status'    => 'publish',
				'post__not_in'   => $wykluczone,
				'no_found_rows'  => true,
				'tax_query'      => array( array(
					'taxonomy' => 'wojewodztwo',
					'field'    => 'term_id',
					'terms'    => $wojewodztwa,
				) ),
			) );
			$sasiedzi = array_merge( $sasiedzi, $dodatkowi );
		}
	}

	if ( ! $sasiedzi ) {
		return '';
	}

	ob_start();
	?>
	<div class="olech-sekcja olech-sasiednie-miasta">
		<h2>Sąsiednie miasta</h2>
		<ul>
			<?php foreach ( $sasiedzi as $miasto ) : ?>
				<li><a href="<?php echo esc_url( get_permalink( $miasto ) ); ?>"><?php echo esc_html( get_the_title( $miasto ) ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
	return ob_get_clean();
};
