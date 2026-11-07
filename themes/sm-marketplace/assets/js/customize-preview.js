/* Live preview of SM Brand colours and name in the Customizer. */
( function ( api ) {
	'use strict';
	var vars = window.smColorVars || {};
	Object.keys( vars ).forEach( function ( setting ) {
		api( setting, function ( value ) {
			value.bind( function ( color ) {
				document.documentElement.style.setProperty( vars[ setting ], color );
			} );
		} );
	} );
	api( 'sm_brand_name', function ( value ) {
		value.bind( function ( name ) {
			document.querySelectorAll( '.sm-brand__name' ).forEach( function ( el ) {
				el.textContent = name;
			} );
		} );
	} );
} )( wp.customize );
