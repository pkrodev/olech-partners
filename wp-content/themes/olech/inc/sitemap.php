<?php
/**
 * Sitemapy — sekcja 14 CLAUDE.md: "indeks + pliki po 200 URL, osobno dla
 * każdego CPT".
 *
 * Docelowo ma to obsługiwać Rank Math (sekcja 3: wybrany świadomie zamiast
 * Yoasta pod kątem wydajności przy dużej liczbie wpisów) — plugin jest
 * aktywny (`rank_math_modules` zawiera "sitemap"), ale jego kreator
 * konfiguracji (onboarding wizard) nigdy nie został ukończony
 * (`rank_math_registration_step` nie ustawione), więc `/sitemap_index.xml`
 * na razie zwraca 404, a `rank_math_known_post_types` nie widzi nawet
 * `usluga`/`lokalizacja`. Dokończenie kreatora to krok w panelu admina
 * (wp-admin → Rank Math), którego nie da się bezpiecznie zrobić z CLI —
 * majstrowanie przy wewnętrznych opcjach pluginu bez przejścia wizarda
 * ryzykowałoby zostawienie go w niespójnym stanie.
 *
 * Do czasu ukończenia tego kroku sitemapy obsługuje wbudowany mechanizm
 * WordPressa (`/wp-sitemap.xml`) — w pełni funkcjonalny, automatycznie
 * dzieli wg CPT/taksonomii i pomija typy bez opublikowanej treści. Gdy
 * ktoś z dostępem do wp-admin dokończy kreator Rank Math, jego sitemapy
 * przejmą tę rolę (Rank Math standardowo wyłącza wtedy sitemapy core WP).
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'wp_sitemaps_max_urls', function () {
	return 200;
} );

// Archiwum autora nie ma tu wartości SEO (to nie blog wieloautorski) —
// niepotrzebna cienka treść w sitemapie.
add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}, 10, 2 );
