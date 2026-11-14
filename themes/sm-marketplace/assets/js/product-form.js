/**
 * SM Marketplace — product form: live refresh of the technical part.
 * The server (sm-products + sm-weight-engine) renders the fields and the
 * summary; this script only re-requests them when a technical field changes.
 */
( function () {
	'use strict';

	var form = document.querySelector( '[data-smp]' );
	if ( ! form ) { return; }
	var ctrl = null;
	var timer = null;

	function tech() { return form.querySelector( '[data-smp-tech]' ); }

	/** Current values of the technical fields (text inputs and selects). */
	function snapshot( block ) {
		var out = {};
		block.querySelectorAll( 'input[name], select[name], textarea[name]' ).forEach( function ( el ) {
			if ( 'radio' === el.type || 'checkbox' === el.type ) {
				if ( el.checked ) { out[ el.name ] = el.value; }
				return;
			}
			out[ el.name ] = el.value;
		} );
		return out;
	}

	/** Keep what the person typed while the request was running. */
	function carry( before, oldBlock, fresh ) {
		var now = snapshot( oldBlock );
		var changed = false;
		Object.keys( now ).forEach( function ( name ) {
			if ( now[ name ] === before[ name ] ) { return; }
			var el = fresh.querySelector( '[name="' + name.replace( /"/g, '' ) + '"]:not([type="radio"])' );
			if ( el ) { el.value = now[ name ]; changed = true; }
		} );
		return changed;
	}

	function query( changed ) {
		var q = new URLSearchParams();
		q.set( 'category', form.querySelector( '[name="category"]' ).value );
		var block = tech();
		new FormData( form ).forEach( function ( value, key ) {
			if ( value instanceof File ) { return; }
			var field = form.querySelector( '[name="' + key.replace( /"/g, '' ) + '"]' );
			if ( ! field || ! block.contains( field ) ) { return; }
			if ( '' === String( value ).trim() ) { return; }
			q.append( key, value );
		} );
		// The generated title of a building names the wilaya, which sits outside the technical part.
		var wil = form.querySelector( '[name="wilaya"]' );
		if ( wil && wil.value && block.querySelector( '.smb-title' ) ) { q.set( 'wilaya', wil.value ); }
		var line = changed && changed.name ? changed.name.match( /^lines\[(\d+)\]\[family\]$/ ) : null;
		if ( changed && 'mode' === changed.name ) {
			q.delete( 'price_unit' ); // Assembled and dismantled buildings have different default units.
		}
		if ( line ) {
			q.delete( 'lines[' + line[1] + '][section]' ); // A size of another type means nothing.
		} else if ( changed && 'family' === changed.name ) {
			[ 'section', 'grade', 'grade_other' ].forEach( function ( k ) { q.delete( k ); } );
			Array.from( q.keys() ).filter( function ( k ) { return 0 === k.indexOf( 'dims[' ); } ).forEach( function ( k ) { q.delete( k ); } );
		} else if ( changed && 'section' === changed.name ) {
			Array.from( q.keys() ).filter( function ( k ) { return 0 === k.indexOf( 'dims[' ); } ).forEach( function ( k ) { q.delete( k ); } );
		} else if ( changed && 0 === changed.name.indexOf( 'dims[' ) ) {
			q.delete( 'section' );
		}
		return q;
	}

	function refresh( changed ) {
		var url = form.getAttribute( 'data-smp-partial' );
		var q = query( changed );
		if ( ctrl ) { ctrl.abort(); }
		ctrl = 'AbortController' in window ? new AbortController() : null;
		var block = tech();
		var before = snapshot( block );
		block.setAttribute( 'aria-busy', 'true' );
		var active = document.activeElement;
		var focusId = active && block.contains( active ) ? active.id : '';
		var caret = active && 'selectionStart' in active ? active.selectionStart : null;

		fetch( url + '&' + q.toString(), { credentials: 'same-origin', signal: ctrl ? ctrl.signal : undefined } )
			.then( function ( r ) { if ( ! r.ok ) { throw new Error( r.status ); } return r.text(); } )
			.then( function ( html ) {
				var tpl = document.createElement( 'template' );
				tpl.innerHTML = html.trim();
				var fresh = tpl.content.querySelector( '[data-smp-tech]' );
				if ( ! fresh ) { throw new Error( 'empty' ); }
				var again = carry( before, tech(), fresh );
				fresh.classList.add( 'sm-swap-in' );
				tech().replaceWith( fresh );
				if ( again ) { clearTimeout( timer ); timer = setTimeout( function () { refresh( null ); }, 300 ); }
				var el = focusId ? document.getElementById( focusId ) : null;
				if ( el ) {
					el.focus( { preventScroll: true } );
					if ( null !== caret && el.setSelectionRange && 'text' === el.type ) { try { el.setSelectionRange( caret, caret ); } catch ( e ) {} }
				}
			} )
			.catch( function ( e ) {
				if ( 'AbortError' !== e.name ) { block.removeAttribute( 'aria-busy' ); }
			} );
	}

	function inTech( el ) { return tech() && tech().contains( el ); }

	form.addEventListener( 'change', function ( e ) {
		if ( 'wilaya' === e.target.name && tech() && tech().querySelector( '.smb-title' ) ) { clearTimeout( timer ); refresh( null ); return; }
		if ( ! e.target.name || ! inTech( e.target ) ) { return; }
		if ( 'INPUT' === e.target.tagName && 'radio' !== e.target.type && 'checkbox' !== e.target.type ) { return; }
		clearTimeout( timer );
		refresh( e.target );
	} );
	form.addEventListener( 'input', function ( e ) {
		if ( ! inTech( e.target ) || 'INPUT' !== e.target.tagName || 'radio' === e.target.type || 'checkbox' === e.target.type ) { return; }
		clearTimeout( timer );
		var t = e.target;
		timer = setTimeout( function () { refresh( t ); }, 500 );
	} );
	// Inventory rows: removing a row clears it; the server drops empty rows.
	form.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest ? e.target.closest( '[data-smp-line-remove]' ) : null;
		if ( ! btn || ! inTech( btn ) ) { return; }
		var row = btn.closest( '[data-smp-line]' );
		if ( ! row ) { return; }
		row.querySelectorAll( 'input' ).forEach( function ( el ) { el.value = ''; } );
		row.querySelectorAll( 'select' ).forEach( function ( el ) { if ( el.name && /\[section\]$/.test( el.name ) ) { el.value = ''; } } );
		row.classList.add( 'is-leaving' );
		clearTimeout( timer );
		timer = setTimeout( function () { refresh( null ); }, 180 );
	} );
	// Buildings: replace the element rows by a new proposal calculated by the server.
	form.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest ? e.target.closest( '[data-smb-recalc]' ) : null;
		if ( ! btn || ! inTech( btn ) ) { return; }
		var flag = tech().querySelector( '[data-smb-recalc-flag]' );
		if ( ! flag ) { return; }
		flag.value = '1';
		clearTimeout( timer );
		refresh( null );
	} );
	// Enter in a technical field must not submit the whole form.
	form.addEventListener( 'keydown', function ( e ) {
		if ( 'Enter' === e.key && inTech( e.target ) && 'INPUT' === e.target.tagName ) {
			e.preventDefault();
			clearTimeout( timer );
			refresh( e.target );
		}
	} );
}() );
