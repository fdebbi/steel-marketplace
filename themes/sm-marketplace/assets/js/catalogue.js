/**
 * Catalogue page of a size (0.59.0): weight of « length × number of bars » from the
 * weight per metre of the engine (data-kgm). Display only, nothing is sent.
 */
( function () {
	'use strict';
	var fmt = function ( v ) {
		var t = v >= 1000 ? v / 1000 : v;
		var s = t.toLocaleString( 'fr-FR', { maximumFractionDigits: v >= 1000 ? 2 : 1 } ).replace( / | /g, ' ' );
		return s + ' ' + ( v >= 1000 ? 't' : 'kg' );
	};
	document.querySelectorAll( '[data-smk-calc]' ).forEach( function ( form ) {
		var kgm = parseFloat( form.getAttribute( 'data-kgm' ) ) || 0;
		var out = form.querySelector( '[data-smk-kg]' );
		var run = function () {
			var l = parseFloat( String( form.elements.l.value ).replace( ',', '.' ) ) || 0;
			var n = parseInt( form.elements.n.value, 10 ) || 0;
			out.textContent = fmt( Math.max( 0, kgm * l * n ) );
		};
		form.addEventListener( 'input', run );
		run();
	} );
}() );
