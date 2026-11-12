/* Live preview of SM Brand colours and name in the Customizer. */
( function ( api ) {
	'use strict';
	var vars = window.smColorVars || {};
	// Same rule as sm_theme_on_color(): near-black or white, whichever contrasts more.
	function onColor( hex ) {
		var h = String( hex ).replace( '#', '' ), l = 0, w = [ 0.2126, 0.7152, 0.0722 ];
		if ( h.length === 3 ) { h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2]; }
		for ( var i = 0; i < 3; i++ ) {
			var c = parseInt( h.substr( i * 2, 2 ), 16 ) / 255;
			l += w[ i ] * ( c <= 0.03928 ? c / 12.92 : Math.pow( ( c + 0.055 ) / 1.055, 2.4 ) );
		}
		return ( l + 0.05 ) / 0.0535 >= 1.05 / ( l + 0.05 ) ? '#080B0E' : '#FFFFFF';
	}
	Object.keys( vars ).forEach( function ( setting ) {
		api( setting, function ( value ) {
			value.bind( function ( color ) {
				document.documentElement.style.setProperty( vars[ setting ], color );
				if ( 'sm_color_accent' === setting ) {
					document.documentElement.style.setProperty( '--sm-on-accent', onColor( color ) );
				}
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
