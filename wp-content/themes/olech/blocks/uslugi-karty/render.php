<?php
/**
 * Siatka kart usług — WP_Query zawsze z posts_per_page (sekcja 3 CLAUDE.md).
 */

return function () {
	$uslugi = get_posts( array(
		'post_type'      => 'usluga',
		'posts_per_page' => 12,
		'post_status'    => 'publish',
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	) );

	if ( ! $uslugi ) {
		return '<div class="olech-sekcja olech-uslugi"><h2>Usługi</h2><p>{{LOREM: karty usług — brak jeszcze opublikowanych usług}}</p></div>';
	}

	ob_start();
	?>
	<div class="olech-sekcja olech-uslugi">
		<h2>Usługi</h2>
		<div class="olech-uslugi__siatka">
			<?php foreach ( $uslugi as $usluga ) : ?>
				<a class="olech-usluga-karta" href="<?php echo esc_url( get_permalink( $usluga ) ); ?>">
					<span class="olech-usluga-karta__tytul"><?php echo esc_html( get_the_title( $usluga ) ); ?></span>
					<?php if ( $usluga->post_excerpt ) : ?>
						<span class="olech-usluga-karta__opis"><?php echo esc_html( wp_trim_words( $usluga->post_excerpt, 18 ) ); ?></span>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
};
