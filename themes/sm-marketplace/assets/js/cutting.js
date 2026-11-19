/**
 * Cutting optimiser (0.46.0): the size list follows the profile, « Add a line »,
 * a list pasted from Excel (profile ; length ; number), print and copy the link.
 * The plan itself is computed by the server (sm-weight-engine).
 */
( function () {
	'use strict';
	var root = document.querySelector( '[data-smc]' );
	if ( ! root ) { return; }
	var cfg;
	try { cfg = JSON.parse( root.getAttribute( 'data-smc' ) ); } catch ( e ) { return; }
	var S = cfg.sections || {}, T = cfg.i18n || {};
	var $$ = function ( s, c ) { return Array.prototype.slice.call( ( c || document ).querySelectorAll( s ) ); };
	var norm = function ( s ) { return String( s ).toUpperCase().replace( /[×*]/g, 'X' ).replace( /,/g, '.' ).replace( /\s+/g, '' ); };

	function fill( famSel ) {
		var row = famSel.closest( '[data-smc-row]' );
		var sec = row.querySelector( '[data-smc-section]' );
		var keep = sec.value;
		sec.innerHTML = '';
		var o = document.createElement( 'option' ); o.value = ''; o.textContent = T.size; sec.appendChild( o );
		( S[ famSel.value ] || [] ).forEach( function ( s ) {
			var op = document.createElement( 'option' ); op.value = s[0]; op.textContent = s[1];
			if ( String( s[0] ) === keep ) { op.selected = true; }
			sec.appendChild( op );
		} );
	}

	function bind( row ) {
		var f = row.querySelector( '[data-smc-family]' );
		if ( f ) { f.addEventListener( 'change', function () { fill( f ); } ); }
	}
	$$( '[data-smc-row]', root ).forEach( bind );

	function addRow( kind ) {
		var body = root.querySelector( '[data-smc-table="' + kind + '"] tbody' );
		var rows = $$( '[data-smc-row]', body );
		var last = rows[ rows.length - 1 ];
		var copy = last.cloneNode( true );
		var n = rows.length;
		$$( 'select, input', copy ).forEach( function ( el ) {
			el.name = el.name.replace( /\[\d+\]/, '[' + n + ']' );
			if ( 'SELECT' === el.tagName ) { el.selectedIndex = 0; } else { el.value = ''; }
		} );
		copy.classList.remove( 'has-error' );
		body.appendChild( copy );
		bind( copy );
		fill( copy.querySelector( '[data-smc-family]' ) );
		return copy;
	}
	$$( '[data-smc-add]', root ).forEach( function ( b ) {
		b.addEventListener( 'click', function () { addRow( b.getAttribute( 'data-smc-add' ) ).querySelector( 'select' ).focus(); } );
	} );

	/* « Other… » length of bar. */
	var custom = root.querySelector( '.smc-custom' );
	function syncCustom() {
		var on = root.querySelector( '[data-smc-custom]' ).checked;
		custom.hidden = ! on;
	}
	$$( 'input[name="bar"]', root ).forEach( function ( r ) { r.addEventListener( 'change', syncCustom ); } );
	syncCustom();

	/* Paste a list. */
	var box = root.querySelector( '[data-smc-pastebox]' );
	var pasteBtn = root.querySelector( '[data-smc-paste]' );
	if ( box && pasteBtn ) {
		box.querySelector( '[data-smc-pastehelp]' ).textContent = T.pasteHelp;
		var ok = box.querySelector( '[data-smc-pasteok]' );
		ok.textContent = T.pasteOk;
		pasteBtn.addEventListener( 'click', function () { box.hidden = ! box.hidden; if ( ! box.hidden ) { box.querySelector( 'textarea' ).focus(); } } );
		ok.addEventListener( 'click', function () {
			var ta = box.querySelector( 'textarea' ), bad = 0;
			ta.value.split( /\r?\n/ ).forEach( function ( line ) {
				if ( ! line.trim() ) { return; }
				var p = line.split( /\t|;/ ).map( function ( x ) { return x.trim(); } );
				if ( p.length < 3 ) { bad++; return; }
				var want = norm( p[0] ), found = null;
				Object.keys( S ).some( function ( fam ) {
					return S[ fam ].some( function ( s ) { if ( norm( s[1] ) === want ) { found = [ fam, s[0] ]; return true; } return false; } );
				} );
				var len = parseFloat( p[1].replace( ',', '.' ) ), qty = parseInt( p[2], 10 );
				if ( ! found || ! ( len > 0 ) || ! ( qty > 0 ) ) { bad++; return; }
				var rows = $$( '[data-smc-table="rows"] [data-smc-row]', root ), row = null;
				rows.some( function ( r ) { if ( ! r.querySelector( '[data-smc-section]' ).value && ! r.querySelector( 'input' ).value ) { row = r; return true; } return false; } );
				row = row || addRow( 'rows' );
				var fam = row.querySelector( '[data-smc-family]' );
				fam.value = found[0]; fill( fam );
				row.querySelector( '[data-smc-section]' ).value = String( found[1] );
				var ins = row.querySelectorAll( 'input' );
				ins[0].value = String( len ).replace( '.', ',' ); ins[1].value = qty;
			} );
			ta.value = '';
			box.hidden = true;
			if ( bad && window.smDialog ) { window.smDialog.open( { tone: 'warning', title: String( T.pasteBad ).replace( '%d', bad ) } ); }
		} );
	}

	var pr = root.querySelector( '[data-smc-print]' );
	if ( pr ) { pr.addEventListener( 'click', function () { window.print(); } ); }
	var cp = root.querySelector( '[data-smc-copy]' );
	if ( cp ) {
		cp.addEventListener( 'click', function () {
			var url = cp.getAttribute( 'data-smc-copy' );
			var done = function () { if ( window.smDialog ) { window.smDialog.open( { tone: 'success', title: T.copied, auto: 2500 } ); } };
			if ( navigator.clipboard ) { navigator.clipboard.writeText( url ).then( done, function () { window.prompt( '', url ); } ); } else { window.prompt( '', url ); }
		} );
	}
}() );
