<?php
/**
 * Lista usług — układ pionowy: zdjęcie + tytuł + dłuższy opis + link,
 * każdy wiersz w całości klikalny. WP_Query zawsze z posts_per_page
 * (sekcja 3 CLAUDE.md). Zdjęcie z wyróżnionego obrazka usługi
 * (post-thumbnail) — jeśli usługa go nie ma, wiersz renderuje się bez
 * zdjęcia (bez łamania layoutu), nie z LOREM (brak zdjęcia to nie
 * brakujący fakt merytoryczny, tylko jeszcze nieuzupełniony atrybut
 * wizualny).
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
		<div class="olech-uslugi__lista">
			<?php foreach ( $uslugi as $usluga ) : ?>
				<a class="olech-usluga-wiersz" href="<?php echo esc_url( get_permalink( $usluga ) ); ?>">
					<?php if ( has_post_thumbnail( $usluga ) ) : ?>
						<span class="olech-usluga-wiersz__zdjecie">
							<?php echo get_the_post_thumbnail( $usluga, 'medium_large', array( 'loading' => 'lazy', 'alt' => get_the_title( $usluga ) ) ); ?>
						</span>
					<?php endif; ?>
					<span class="olech-usluga-wiersz__tresc">
						<span class="olech-usluga-wiersz__tytul"><?php echo esc_html( get_the_title( $usluga ) ); ?></span>
						<?php if ( $usluga->post_excerpt ) : ?>
							<span class="olech-usluga-wiersz__opis"><?php echo esc_html( wp_trim_words( $usluga->post_excerpt, 45 ) ); ?></span>
						<?php endif; ?>
						<span class="olech-usluga-wiersz__link">Czytaj więcej →</span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
};
