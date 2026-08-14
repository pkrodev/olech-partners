document.addEventListener( 'DOMContentLoaded', function () {
	document.querySelectorAll( '[data-olech-mapa]' ).forEach( function ( el ) {
		var btn = el.querySelector( '.olech-mapa__przycisk' );
		if ( ! btn ) {
			return;
		}
		btn.addEventListener( 'click', function () {
			var iframe = document.createElement( 'iframe' );
			iframe.src = 'https://www.google.com/maps?q=Radom&output=embed';
			iframe.width = '100%';
			iframe.height = '400';
			iframe.loading = 'lazy';
			iframe.style.border = '0';
			iframe.setAttribute( 'title', 'Mapa — Radom' );
			el.replaceChildren( iframe );
		}, { once: true } );
	} );
} );
