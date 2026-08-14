<?php
/**
 * Facade mapy — realny <iframe> Google Maps ładowany dopiero po kliknięciu
 * (sekcja 14: „Mapa Google ładowana dopiero po kliknięciu"). Adres wyłącznie
 * Radom — jedyny dozwolony w LocalBusiness (sekcja 10).
 */

return function () {
	ob_start();
	?>
	<div class="olech-mapa" data-olech-mapa>
		<button type="button" class="olech-mapa__przycisk">Pokaż mapę i wyznacz trasę</button>
		<noscript><p><a href="https://www.google.com/maps/search/?api=1&amp;query=Radom">Otwórz mapę w Google Maps</a></p></noscript>
	</div>
	<?php
	return ob_get_clean();
};
