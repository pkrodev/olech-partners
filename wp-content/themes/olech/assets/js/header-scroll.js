/**
 * Nagłówek ukryty na samej górze strony głównej, pojawia się po
 * przewinięciu — zachowanie tylko na front-page (body.home), CSS w
 * style.css robi resztę przez klasę .olech-scrolled na <body>.
 */
( function () {
	var PROG_THRESHOLD = 80;
	var ticking = false;

	function update() {
		document.body.classList.toggle( 'olech-scrolled', window.scrollY > PROG_THRESHOLD );
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
