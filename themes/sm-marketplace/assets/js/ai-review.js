/**
 * « Read a document » — check (0.51.0): the size list follows the profile; on a
 * line given by its weight (or metres), changing the length recomputes the
 * number of bars: rounded up to buy, to the nearest for the warehouse.
 */
( function () {
	'use strict';
	var root = document.querySelector( '[data-smdoc]' );
	if ( ! root ) { return; }
	var cfg;
	try { cfg = JSON.parse( root.getAttribute( 'data-smdoc' ) ); } catch ( e ) { return; }
	var S = cfg.sections || {}, T = cfg.i18n || {};
	var num = function ( v ) { return parseFloat( String( v || '' ).replace( ',', '.' ).replace( /\s/g, '' ) ) || 0; };
	var fmt = function ( v ) { return ( Math.round( v * 100 ) / 100 ).toString().replace( '.', ',' ); };

	Array.prototype.forEach.call( root.querySelectorAll( '[data-smdoc-row]' ), function ( row ) {
		var fam = row.querySelector( '[data-smdoc-family]' ), sec = row.querySelector( '[data-smdoc-section]' );
		var len = row.querySelector( '[data-smdoc-length]' ), qty = row.querySelector( '[data-smdoc-qty]' );
		var stock = row.querySelector( '[data-smdoc-stock]' ), calc = row.querySelector( '[data-smdoc-calc]' );
		var kgm = num( row.getAttribute( 'data-kgm' ) ), dockg = num( row.getAttribute( 'data-dockg' ) ), metres = num( row.getAttribute( 'data-metres' ) );
		if ( fam && sec ) {
			fam.addEventListener( 'change', function () {
				sec.innerHTML = '';
				var o = document.createElement( 'option' ); o.value = ''; o.textContent = T.size || ''; sec.appendChild( o );
				( S[ fam.value ] || [] ).forEach( function ( s ) {
					var op = document.createElement( 'option' ); op.value = s[0]; op.textContent = s[1]; sec.appendChild( op );
				} );
			} );
		}
		if ( len && qty && ( dockg > 0 || metres > 0 ) && kgm > 0 ) {
			len.addEventListener( 'input', function () {
				var l = num( len.value );
				if ( l <= 0 || l > 24 ) { return; }
				var exact = Math.round( ( dockg > 0 ? dockg / ( kgm * l ) : metres / l ) * 100 ) / 100;
				var buy = Math.ceil( exact - 0.005 ), keep = Math.max( 1, Math.round( exact ) );
				qty.value = buy;
				if ( stock ) { stock.value = keep; }
				if ( calc && T.calc ) { calc.textContent = T.calc.replace( '%1$s', fmt( exact ) ).replace( '%2$s', buy ).replace( '%3$s', keep ); }
			} );
		}
		if ( qty && stock ) {
			// A number typed by hand is the number for the warehouse too.
			qty.addEventListener( 'change', function () { if ( num( qty.value ) > 0 ) { stock.value = Math.round( num( qty.value ) ); } } );
		}
	} );
}() );
