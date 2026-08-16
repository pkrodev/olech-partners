/**
 * Odsłanianie elementów .olech-reveal przy wjeździe w viewport
 * (IntersectionObserver, jednorazowo per element). Bez animacji, jeśli
 * użytkownik ma prefers-reduced-motion albo przeglądarka nie wspiera
 * IntersectionObserver — wtedy po prostu od razu .is-visible.
 */
( function () {
	var elementy = document.querySelectorAll( '.olech-reveal' );
	if ( ! elementy.length ) {
		return;
	}

	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	if ( reduceMotion || ! ( 'IntersectionObserver' in window ) ) {
		elementy.forEach( function ( el ) {
			el.classList.add( 'is-visible' );
		} );
		return;
	}

	var observer = new IntersectionObserver(
		function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'is-visible' );
					observer.unobserve( entry.target );
				}
			} );
		},
		{ threshold: 0.15, rootMargin: '0px 0px -40px 0px' }
	);

	elementy.forEach( function ( el ) {
		observer.observe( el );
	} );
} )();
