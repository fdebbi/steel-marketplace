/**
 * ReuSteel — motion: header state on scroll and gentle reveal of sections.
 * Presentation only. Does nothing when the device asks for reduced motion or
 * when IntersectionObserver is missing (content then simply shows).
 */
( function () {
	'use strict';

	// Skipped page transitions are silenced by a tiny script in <head> (inc/assets.php).

	var header = document.querySelector( '.sm-header' );
	if ( header ) {
		var ticking = false;
		var onScroll = function () {
			if ( ticking ) { return; }
			ticking = true;
			requestAnimationFrame( function () {
				header.classList.toggle( 'is-scrolled', window.scrollY > 8 );
				ticking = false;
			} );
		};
		window.addEventListener( 'scroll', onScroll, { passive: true } );
		onScroll();
	}

	if ( ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) || ! ( 'IntersectionObserver' in window ) ) {
		return;
	}

	var SELECTOR = [
		'.sm-section__head', '.sm-hero__content', '.sm-card', '.sm-panel', '.sm-split__card', '.sm-markets > *',
		'.sm-cta', '.sm-stats > *', '.sm-vsteps > *', '.smw-step', '.smp-step', '.smp-row', '.sm-pagehead__title', '.sm-pagehead__intro'
	].join( ',' );

	document.documentElement.classList.add( 'sm-js-motion' );

	var io = new IntersectionObserver( function ( entries ) {
		var batch = entries.filter( function ( e ) { return e.isIntersecting; } );
		batch.forEach( function ( e, i ) {
			e.target.style.setProperty( '--sm-delay', Math.min( i * 60, 360 ) + 'ms' );
			e.target.classList.add( 'is-in' );
			io.unobserve( e.target );
			// Remove the helper once shown, so hover effects use their own timing.
			setTimeout( function () { e.target.classList.remove( 'sm-reveal', 'is-in' ); e.target.style.removeProperty( '--sm-delay' ); }, 900 + Math.min( i * 60, 360 ) );
		} );
	}, { rootMargin: '0px 0px -6% 0px', threshold: 0.06 } );

	function watch( root ) {
		( root || document ).querySelectorAll( SELECTOR ).forEach( function ( el ) {
			if ( el.dataset.smSeen || el.closest( '[data-smp-tech], [data-smw], .sm-mega, .sm-drawer, .sm-header' ) ) { return; }
			el.dataset.smSeen = '1';
			el.classList.add( 'sm-reveal' );
			io.observe( el );
		} );
	}
	watch();

	// Safety net: never leave anything hidden (printing, unusual scroll containers).
	setTimeout( function () {
		document.querySelectorAll( '.sm-reveal:not(.is-in)' ).forEach( function ( el ) {
			var r = el.getBoundingClientRect();
			if ( r.top < window.innerHeight ) { el.classList.add( 'is-in' ); }
		} );
	}, 1500 );
	window.addEventListener( 'beforeprint', function () {
		document.querySelectorAll( '.sm-reveal' ).forEach( function ( el ) { el.classList.add( 'is-in' ); } );
	} );
}() );
