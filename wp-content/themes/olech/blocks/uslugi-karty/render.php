<?php
/**
 * Lista usług — edytorski układ naprzemienny: zdjęcie/opis, potem
 * opis/zdjęcie, i tak na przemian (uwaga użytkownika z sesji 2026-08-16 —
 * poprzednia siatka kafli "się rozjechała"). Kolejność wizualna
 * odwracana czystym CSS-em (`direction: rtl` na wierszu parzystym, patrz
 * style.css), nie zmianą DOM-u — czytnik ekranu i tak dostaje zdjęcie
 * przed tekstem w każdym wierszu. Cały wiersz to jeden klikalny link.
 * WP_Query zawsze z posts_per_page (sekcja 3 CLAUDE.md). Zdjęcie z
 * wyróżnionego obrazka usługi (post-thumbnail) — jeśli usługa go nie ma,
 * wiersz renderuje się bez zdjęcia (bez łamania layoutu), nie z LOREM
 * (brak zdjęcia to nie brakujący fakt merytoryczny, tylko jeszcze
 * nieuzupełniony atrybut wizualny).
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

	// Karta 0 (wariograf, flagowa) jest blisko górnej krawędzi tylko na
	// hubie /uslugi/ (H1 + breadcrumbs, nic więcej nad nią) — tam ma sens
	// eager/high-priority. Na stronie głównej ta sama karta jest kilka
	// sekcji niżej (hero, pasek zaufania, dlaczego-my, pasmo interstycjalne),
	// więc tam wszystkie karty, łącznie z nią, mają być leniwie ładowane.
	$pierwsza_karta_nad_zaginieciem = ! is_front_page();

	ob_start();
	?>
	<div class="olech-sekcja olech-uslugi">
		<div class="olech-reveal">
			<h2>Usługi</h2>
			<p class="olech-uslugi__lead">Sześć obszarów działania — od ustalenia miejsca pobytu po badanie wariografem. Każdą sprawę prowadzi licencjonowany zespół, z pełną poufnością na każdym etapie.</p>
		</div>
		<div class="olech-uslugi__lista">
			<?php foreach ( $uslugi as $i => $usluga ) : ?>
				<?php $eager = ( 0 === $i && $pierwsza_karta_nad_zaginieciem ); ?>
				<a
					class="olech-usluga-fila olech-reveal"
					style="--olech-opoznienie: <?php echo esc_attr( min( $i, 3 ) * 100 ); ?>ms"
					href="<?php echo esc_url( get_permalink( $usluga ) ); ?>"
				>
					<span class="olech-usluga-fila__obraz">
						<?php if ( has_post_thumbnail( $usluga ) ) : ?>
							<?php echo get_the_post_thumbnail( $usluga, 'large', array( 'loading' => $eager ? 'eager' : 'lazy', 'alt' => get_the_title( $usluga ) ) ); ?>
						<?php endif; ?>
					</span>
					<span class="olech-usluga-fila__tresc">
						<?php if ( 0 === $i ) : ?>
							<span class="olech-usluga-fila__eyebrow">Usługa flagowa</span>
						<?php endif; ?>
						<span class="olech-usluga-fila__tytul"><?php echo esc_html( get_the_title( $usluga ) ); ?></span>
						<?php if ( $usluga->post_excerpt ) : ?>
							<span class="olech-usluga-fila__opis"><?php echo esc_html( wp_trim_words( $usluga->post_excerpt, 45 ) ); ?></span>
						<?php endif; ?>
						<span class="olech-usluga-fila__link">Czytaj więcej <span aria-hidden="true">→</span></span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
};
