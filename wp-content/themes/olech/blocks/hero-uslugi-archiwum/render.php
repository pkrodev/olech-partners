<?php
/**
 * Hero na /uslugi/ — statyczny odpowiednik olech/hero-usluga (patrz tamten
 * plik po wyjaśnienie mechanizmu tła/animacji). Treść stała, jedyna
 * różnica względem hero-usluga: brak dynamicznego tytułu/excerptu/kategorii,
 * bo to strona archiwum, nie pojedynczy post.
 */

return function () {
	$obraz = get_template_directory_uri() . '/assets/img/sylwetka-teczka.webp';

	ob_start();
	?>
	<div class="wp-block-group alignfull olech-usluga-hero" style="--olech-usluga-hero-bg: url(<?php echo esc_url( $obraz ); ?>)">
		<?php echo do_blocks( '<!-- wp:olech/breadcrumbs /-->' ); ?>
		<p class="olech-usluga-hero__eyebrow">Pełna oferta</p>
		<h1 class="olech-usluga-hero__tytul">Usługi detektywistyczne</h1>
		<p class="olech-usluga-hero__lead">Sześć obszarów działania — od ustalenia miejsca pobytu po badanie wariografem. Każdą sprawę prowadzi licencjonowany zespół, z pełną poufnością na każdym etapie.</p>
		<div class="olech-cta-row">
			<?php echo do_blocks( '<!-- wp:olech/cta-telefon {"styl":"obrys"} /-->' ); ?>
			<p><a class="olech-btn olech-btn--zloto" href="#formularz">Zostaw zgłoszenie</a></p>
		</div>
	</div>
	<?php
	return ob_get_clean();
};
