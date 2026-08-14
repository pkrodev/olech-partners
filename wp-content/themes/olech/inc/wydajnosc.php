<?php
/**
 * Wydajność — sekcja 14 CLAUDE.md.
 *
 * Reszta wymagań z tej sekcji jest już spełniona bez dodatkowego kodu:
 * brak jQuery (nic go nie enqueue'uje), mapa Google jako facade
 * ładowany po kliknięciu (blocks/mapa-radom), sitemapy (inc/sitemap.php,
 * punkt 8), brak fontów z zewnętrznego CDN (theme.json świadomie pusty —
 * czeka na branding, sekcja 1 z sekcji 16). `loading="lazy"` i wymiary
 * obrazów to domyślne zachowanie WordPressa od 5.5/5.9 dla `<img>` w
 * treści i miniaturkach — nie wymaga kodu w motywie, o ile obrazy są
 * wstawiane standardowymi mechanizmami WP (co i tak jest jedyną opcją,
 * bo page builder jest zakazany, sekcja 3).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Automatyczne WebP przy uploadzie JPEG/PNG — natywny mechanizm WP 5.8+,
 * bez pluginu. Dotyczy zarówno wygenerowanych rozmiarów pośrednich
 * (thumbnail/medium/...), jak i głównego pliku roboczego przy dużych
 * zdjęciach (WP tworzy wtedy wersję "scaled" i to ona staje się plikiem
 * używanym na stronie) — oryginalny, niezmieniony upload zostaje osobno
 * dostępny przez `original_image` w metadanych załącznika, nic go nie
 * nadpisuje. Zweryfikowane realnym uploadem testowym: wszystkie wygenerowane
 * rozmiary trafiły jako `.webp` / `image/webp`. Serwer w tym środowisku
 * (DDEV) wspiera WebP przez GD i Imagick — zweryfikowane
 * (`imagewebp()` + `Imagick::queryFormats('WEBP')`).
 */
add_filter( 'image_editor_output_format', function ( $formats ) {
	$formats['image/jpeg'] = 'image/webp';
	$formats['image/png']  = 'image/webp';
	return $formats;
} );
