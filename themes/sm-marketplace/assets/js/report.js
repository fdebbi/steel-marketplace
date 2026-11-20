/**
 * Business report (0.53.0): « Download as PDF » prints the page (the browser saves
 * it as PDF, in every language); a type of the price table opens its sizes and curve.
 */
( function () {
	'use strict';
	document.querySelectorAll( '[data-smbr-print]' ).forEach( function ( b ) {
		b.addEventListener( 'click', function () { window.print(); } );
	} );
	document.querySelectorAll( '.smbr-more' ).forEach( function ( b ) {
		b.addEventListener( 'click', function () {
			var row = document.getElementById( b.getAttribute( 'aria-controls' ) );
			if ( ! row ) { return; }
			var open = 'true' !== b.getAttribute( 'aria-expanded' );
			b.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			row.hidden = ! open;
		} );
	} );
}() );
