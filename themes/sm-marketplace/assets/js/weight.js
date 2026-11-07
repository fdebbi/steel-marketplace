/**
 * SM Marketplace — weight calculator: live refresh.
 * Presentation only. The server renders the calculator (same HTML as without
 * JavaScript); every change re-requests it with ?smw_partial=1 and swaps it in.
 */
( function () {
	'use strict';

	var ctrl = null;
	var timer = null;

	function params( form, changed ) {
		var data = new FormData( form );
		var out = new URLSearchParams();
		data.forEach( function ( value, key ) {
			if ( '' === String( value ).trim() ) { return; }
			out.append( key, value );
		} );
		if ( changed && 'family' === changed.name ) {
			// New product type: start the size and quantity again.
			[ 'section', 'length', 'length_custom', 'width', 'plate_length', 'bars', 'total_kg', 'mode' ].forEach( function ( k ) { out.delete( k ); } );
			Array.from( out.keys() ).filter( function ( k ) { return 0 === k.indexOf( 'dims[' ); } ).forEach( function ( k ) { out.delete( k ); } );
		} else if ( changed && 'section' === changed.name ) {
			Array.from( out.keys() ).filter( function ( k ) { return 0 === k.indexOf( 'dims[' ); } ).forEach( function ( k ) { out.delete( k ); } );
		} else if ( changed && 0 === changed.name.indexOf( 'dims[' ) ) {
			out.delete( 'section' );
		}
		return out;
	}

	function refresh( form, changed ) {
		var query = params( form, changed );
		var url = form.getAttribute( 'action' ).split( '?' )[ 0 ];
		var shown = url + ( query.toString() ? '?' + query.toString() : '' );
		query.set( 'smw_partial', '1' );
		if ( ctrl ) { ctrl.abort(); }
		ctrl = 'AbortController' in window ? new AbortController() : null;
		form.setAttribute( 'aria-busy', 'true' );

		var active = document.activeElement;
		var focusId = active && form.contains( active ) ? active.id : '';
		var caret = active && 'selectionStart' in active ? active.selectionStart : null;
		var familyChanged = changed && 'family' === changed.name;
		var before = {};
		form.querySelectorAll( 'input[name]:not([type="radio"]), select[name]' ).forEach( function ( el ) { before[ el.name ] = el.value; } );

		fetch( url + '?' + query.toString(), { credentials: 'same-origin', signal: ctrl ? ctrl.signal : undefined } )
			.then( function ( r ) { if ( ! r.ok ) { throw new Error( r.status ); } return r.text(); } )
			.then( function ( html ) {
				var tpl = document.createElement( 'template' );
				tpl.innerHTML = html.trim();
				var fresh = tpl.content.querySelector( '[data-smw]' );
				if ( ! fresh ) { throw new Error( 'empty' ); }
				// Keep what was typed while the request was running.
				var again = false;
				form.querySelectorAll( 'input[name]:not([type="radio"]), select[name]' ).forEach( function ( el ) {
					if ( el.value === before[ el.name ] ) { return; }
					var twin = fresh.querySelector( '[name="' + el.name.replace( /"/g, '' ) + '"]:not([type="radio"])' );
					if ( twin ) { twin.value = el.value; again = true; }
				} );
				fresh.classList.add( 'sm-swap-in' );
				form.replaceWith( fresh );
				bind( fresh );
				if ( again ) { clearTimeout( timer ); timer = setTimeout( function () { refresh( fresh, null ); }, 300 ); }
				if ( window.history && history.replaceState ) { history.replaceState( null, '', shown ); }
				var el = focusId ? document.getElementById( focusId ) : null;
				if ( el ) {
					el.focus( { preventScroll: true } );
					if ( null !== caret && el.setSelectionRange && 'text' === el.type ) { try { el.setSelectionRange( caret, caret ); } catch ( e ) {} }
				}
				if ( familyChanged ) {
					var step = fresh.querySelector( '#smw-size' );
					if ( step && step.getBoundingClientRect().top > window.innerHeight * 0.75 ) {
						step.scrollIntoView( { behavior: 'smooth', block: 'start' } );
					}
				}
			} )
			.catch( function ( e ) {
				if ( 'AbortError' === e.name ) { return; }
				form.removeAttribute( 'aria-busy' );
				form.submit(); // Fall back to a normal page load.
			} );
	}

	function bind( form ) {
		if ( form._smw ) { return; }
		form._smw = true;
		form.addEventListener( 'change', function ( e ) {
			if ( ! e.target.name ) { return; }
			// Typed fields are handled by the "input" listener below.
			if ( 'INPUT' === e.target.tagName && 'radio' !== e.target.type && 'checkbox' !== e.target.type ) { return; }
			clearTimeout( timer );
			refresh( form, e.target );
		} );
		form.addEventListener( 'input', function ( e ) {
			if ( 'INPUT' !== e.target.tagName || 'radio' === e.target.type || 'checkbox' === e.target.type ) { return; }
			clearTimeout( timer );
			var target = e.target;
			timer = setTimeout( function () { refresh( form, target ); }, 450 );
		} );
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			clearTimeout( timer );
			refresh( form, null );
		} );
	}

	document.querySelectorAll( '[data-smw]' ).forEach( bind );
}() );
