<?php
/**
 * Hero pojedynczej strony usługi — zdjęcie usługi (featured image) w tle,
 * ten sam język wizualny co hero strony głównej (gradient, Ken Burns,
 * fade-in tekstu), ale tło jest DYNAMICZNE per usługa, więc to osobna
 * klasa (.olech-usluga-hero) czytająca URL zdjęcia z custom property
 * ustawionej inline — sam mechanizm gradientu/animacji zdefiniowany raz
 * w style.css.
 *
 * Brak zdjęcia u usługi (na razie żadna go nie brakuje, ale mogłoby się
 * zdarzyć) — hero renderuje się z samym ciemnym tłem, bez URL-a w
 * custom property (przeglądarka po prostu nie ma czego załadować).
 */

return function () {
	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return '';
	}

	$tytul   = get_the_title( $post_id );
	$excerpt = has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : '';
	$obraz   = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail_url( $post_id, 'large' ) : '';

	$terminy = get_the_terms( $post_id, 'kategoria_uslugi' );
	$eyebrow = ( $terminy && ! is_wp_error( $terminy ) ) ? $terminy[0]->name : 'Usługi detektywistyczne';

	// Domyślnie "center" (środek zdjęcia) wystarcza, ale nie zawsze — np.
	// zdjęcie EKG (badanie-wariografem) ma pusty, jasny odstęp dokładnie
	// na środku (między dwoma paskami wykresu), więc w bardzo szerokim,
	// niskim kadrze hero "center" pokazywał tylko ten pusty pasek zamiast
	// samej fali (zgłoszone jako "wygląda bardzo źle", sesja 2026-08-16).
	// Ręczna korekta pozycji per slug, zamiast szukać/wymieniać zdjęcie.
	$pozycje_tla = array(
		'badanie-wariografem' => 'center 78%',
	);
	$pozycja = $pozycje_tla[ get_post_field( 'post_name', $post_id ) ] ?? 'center';

	$styl = $obraz ? ' style="--olech-usluga-hero-bg: url(' . esc_url( $obraz ) . '); --olech-usluga-hero-pos: ' . esc_attr( $pozycja ) . ';"' : '';

	ob_start();
	?>
	<div class="wp-block-group alignfull olech-usluga-hero"<?php echo $styl; // phpcs:ignore -- już escapowane wyżej ?>>
		<?php echo do_blocks( '<!-- wp:olech/breadcrumbs /-->' ); ?>
		<p class="olech-usluga-hero__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
		<h1 class="olech-usluga-hero__tytul"><?php echo esc_html( $tytul ); ?></h1>
		<?php if ( $excerpt ) : ?>
			<p class="olech-usluga-hero__lead"><?php echo esc_html( $excerpt ); ?></p>
		<?php endif; ?>
		<div class="olech-cta-row">
			<?php echo do_blocks( '<!-- wp:olech/cta-telefon {"styl":"obrys"} /-->' ); ?>
			<p><a class="olech-btn olech-btn--zloto" href="#formularz">Zostaw zgłoszenie</a></p>
		</div>
	</div>
	<?php
	return ob_get_clean();
};
