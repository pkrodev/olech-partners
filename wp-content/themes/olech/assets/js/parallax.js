/**
 * Lekki parallax dla pasma .olech-cien — tło porusza się wolniej niż
 * scroll (transform, GPU), JS tylko ustawia zmienną CSS. Bez zależności,
 * throttled przez requestAnimationFrame, wyłączony przy
 * prefers-reduced-motion. Osobno od scroll-reveal.js (inny cel: ciągły
 * efekt podczas scrollowania, nie jednorazowe odsłonięcie).
 */
( function () {
	var elementy = document.querySelectorAll( '.olech-cien' );
	if ( ! elementy.length ) {
		return;
	}

	if ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
		return;
	}

	var ticking = false;

	function update() {
		var vh = window.innerHeight;
		elementy.forEach( function ( el ) {
			var rect = el.getBoundingClientRect();
			var srodekEl = rect.top + rect.height / 2;
			var srodekViewport = vh / 2;
			var offset = ( srodekViewport - srodekEl ) * 0.12;
			offset = Math.max( -40, Math.min( 40, offset ) );
			el.style.setProperty( '--olech-parallax', offset.toFixed( 1 ) + 'px' );
		} );
		ticking = false;
	}

	window.addEventListener(
		'scroll',
		function () {
			if ( ! ticking ) {
				window.requestAnimationFrame( update );
				ticking = true;
			}
		},
		{ passive: true }
	);

	update();
} )();
