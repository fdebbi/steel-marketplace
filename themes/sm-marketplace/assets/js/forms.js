/**
 * Forms (0.43.0):
 * - the messages after sending (success, errors) open in the centre (smDialog of SM Core);
 * - missing or wrong information: the message lists the fields, « Complete » takes the
 *   user to the first one (opens its step or section, scrolls, focuses, outlines it);
 * - before sending, required fields still empty are caught the same way;
 * - offer on a request for quotation: total while typing, « Round to the thousand ».
 * Without JavaScript, the banners and the server checks stay as before.
 */
( function () {
	'use strict';
	var T = ( window.smTheme || {} ).i18n || {};
	var $$ = function ( s, c ) { return Array.prototype.slice.call( ( c || document ).querySelectorAll( s ) ); };
	var reduced = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var fmt = function ( s, n ) { return String( s || '' ).replace( '%d', n ); };

	function shown( el ) { return !! ( el.offsetWidth || el.offsetHeight || el.getClientRects().length ); }

	/* Name of a field, as the user reads it. */
	function labelOf( el ) {
		var lab = el.id ? document.querySelector( 'label[for="' + el.id + '"]' ) : null;
		if ( ! lab ) {
			var box = el.closest( '.sm-field, fieldset, .smx-field, .sm-check' );
			lab = box ? box.querySelector( 'label, legend, .sm-field__legend' ) : null;
		}
		var txt = '';
		if ( lab ) {
			var c = lab.cloneNode( true );
			$$( '.sm-optional, small, svg, input, select, textarea', c ).forEach( function ( x ) { x.remove(); } );
			txt = c.textContent.replace( /\s+/g, ' ' ).trim();
		}
		return txt || el.getAttribute( 'aria-label' ) || el.placeholder || el.name || '';
	}

	/* Open what hides a field: sections, steps (wizard.js listens to sm-reveal). */
	function reveal( el ) {
		var d = el.closest( 'details' );
		while ( d ) { d.open = true; d = d.parentElement && d.parentElement.closest( 'details' ); }
		el.dispatchEvent( new CustomEvent( 'sm-reveal', { bubbles: true } ) );
	}

	function go( el ) {
		if ( ! el ) { return; }
		reveal( el );
		setTimeout( function () {
			var box = el.closest( '.sm-field, fieldset, .smx-field' ) || el;
			var target = shown( el ) ? el : box;
			target.scrollIntoView( { block: 'center', behavior: reduced ? 'auto' : 'smooth' } );
			try { el.focus( { preventScroll: true } ); } catch ( e ) { el.focus(); }
			box.classList.remove( 'sm-flag' );
			void box.offsetWidth;
			box.classList.add( 'sm-flag' );
			setTimeout( function () { box.classList.remove( 'sm-flag' ); }, 2600 );
		}, 80 );
	}

	/* The field of an error message (id "err-<field>"). */
	function fieldOf( err ) {
		var id = err.id || '';
		var el = id ? document.querySelector( '[aria-describedby~="' + id + '"]' ) : null;
		if ( ! el && 0 === id.indexOf( 'err-' ) ) {
			var n = id.slice( 4 );
			el = document.querySelector( '[name="' + n + '"], [name="' + n + '[]"], [name^="' + n + '["]' );
		}
		if ( ! el ) {
			var box = err.closest( '.sm-field, fieldset' );
			el = box ? box.querySelector( 'input:not([type=hidden]), select, textarea, button' ) : null;
		}
		return el;
	}

	function dialog( o ) { return window.smDialog ? window.smDialog.open( o ) : Promise.resolve( false ); }

	function listDialog( fields, general ) {
		var items = fields.map( function ( f ) { return { label: f.label, go: function () { go( f.el ); } }; } );
		dialog( {
			tone: 'warning',
			title: fmt( T.missing || 'To complete (%d)', fields.length ),
			text: general.join( ' ' ),
			items: items,
			ok: T.complete || 'Complete',
		} ).then( function ( ok ) { if ( ok ) { go( fields[0].el ); } } );
	}

	/* ---- Messages after sending ------------------------------------------ */
	var auto = !! ( window.smDialogI18n || {} ).auto; // Automated tests: banners stay, the server checks.
	function flash() {
		if ( ! window.smDialog || auto ) { return; }
		var errBanner = document.querySelector( '[data-sm-flash="error"]' );
		var fields = [], seen = [], general = [];
		$$( '.sm-field-error' ).forEach( function ( err ) {
			if ( ! shown( err ) && ! err.closest( 'details, [data-wz-step]' ) ) { return; }
			var el = fieldOf( err );
			if ( ! el || seen.indexOf( el ) > -1 ) { return; }
			seen.push( el );
			fields.push( { el: el, label: labelOf( el ) || err.textContent.trim(), msg: err.textContent.trim() } );
		} );
		if ( errBanner ) {
			$$( '[data-field]', errBanner ).forEach( function ( x ) {
				var f = x.getAttribute( 'data-field' );
				var el = f ? document.querySelector( '[name="' + f + '"], [name="' + f + '[]"], [aria-describedby~="err-' + f + '"]' ) : null;
				if ( el && seen.indexOf( el ) < 0 ) {
					seen.push( el );
					fields.push( { el: el, label: labelOf( el ) || x.textContent.trim(), msg: x.textContent.trim() } );
				} else if ( ! el ) {
					general.push( x.textContent.trim() );
				}
			} );
		}
		if ( fields.length ) {
			fields.sort( function ( a, b ) { return a.el.compareDocumentPosition( b.el ) & 2 ? 1 : -1; } );
			if ( errBanner ) { errBanner.hidden = true; }
			listDialog( fields, general );
			return;
		}
		if ( general.length ) {
			errBanner.hidden = true;
			dialog( { tone: 'warning', title: general.length > 1 ? ( T.attention || 'Please note' ) : general[0], text: general.length > 1 ? general.join( ' ' ) : '', ok: ( window.smDialogI18n || {} ).understood } );
			return;
		}
		var ok = document.querySelector( '[data-sm-flash="success"]' );
		if ( ok ) {
			ok.hidden = true;
			$$( '[data-sm-flash="success"]' ).forEach( function ( x ) { x.hidden = true; } );
			dialog( { tone: 'success', title: ok.textContent.replace( /\s+/g, ' ' ).trim(), auto: 4000 } );
		}
	}

	/* ---- Required fields still empty, before sending ---------------------- */
	document.addEventListener( 'submit', function ( e ) {
		var form = e.target;
		if ( ! window.smDialog || auto || ! form || 'FORM' !== form.tagName || form.hasAttribute( 'data-sm-demo-form' ) || form.querySelector( '[data-wz-step]' ) ) { return; }
		var sub = e.submitter;
		if ( sub && ( sub.formNoValidate || 'draft' === sub.value ) ) { return; }
		var bad = [], names = [];
		$$( 'input, select, textarea', form ).forEach( function ( el ) {
			if ( el.disabled || 'hidden' === el.type || ! el.willValidate || el.checkValidity() ) { return; }
			if ( ! shown( el ) && ! el.closest( 'details' ) ) { return; }
			if ( 'radio' === el.type ) { if ( names.indexOf( el.name ) > -1 ) { return; } names.push( el.name ); }
			bad.push( el );
		} );
		if ( ! bad.length ) { return; }
		e.preventDefault();
		e.stopImmediatePropagation();
		bad.forEach( function ( el, i ) {
			var v = el.validity, msg = v.valueMissing ? T.required : ( v.typeMismatch && 'email' === el.type ? T.email : T.invalid );
			var id = el.getAttribute( 'aria-describedby' ) || ( 'err-js-' + ( el.name || i ).toString().replace( /[^\w-]/g, '' ) );
			var p = document.getElementById( id );
			if ( ! p ) {
				p = document.createElement( 'p' );
				p.className = 'sm-field-error sm-field-error--js';
				p.id = id;
				var box = el.closest( '.sm-field, fieldset' );
				( box && box !== form ? box : el.parentNode ).appendChild( p );
			}
			p.textContent = msg || '';
			el.setAttribute( 'aria-invalid', 'true' );
			el.setAttribute( 'aria-describedby', id );
		} );
		listDialog( bad.map( function ( el ) { return { el: el, label: labelOf( el ) }; } ), [] );
	}, true );

	/* A field filled again: back to normal. */
	function clear( e ) {
		var el = e.target;
		if ( ! el || 'true' !== ( el.getAttribute && el.getAttribute( 'aria-invalid' ) ) ) { return; }
		if ( el.willValidate && ! el.checkValidity() ) { return; }
		if ( ! String( el.value || '' ).trim() && 'checkbox' !== el.type && 'radio' !== el.type ) { return; }
		el.removeAttribute( 'aria-invalid' );
		var p = document.getElementById( el.getAttribute( 'aria-describedby' ) || '' );
		if ( p && p.classList.contains( 'sm-field-error' ) ) { p.hidden = true; }
	}
	document.addEventListener( 'input', clear );
	document.addEventListener( 'change', clear );

	/* ---- Offer on a request for quotation: total and rounding -------------- */
	function num( v ) {
		v = String( v || '' ).replace( /[\s  ]/g, '' ).replace( ',', '.' );
		var n = parseFloat( v );
		return isFinite( n ) ? n : 0;
	}
	function money( n, dec ) {
		return Number( n ).toLocaleString( 'fr-FR', { minimumFractionDigits: dec || 0, maximumFractionDigits: dec || 0 } ).replace( /[  ]/g, ' ' );
	}
	$$( '[data-smq-total]' ).forEach( function ( box ) {
		var form = box.closest( 'form' );
		var I = {};
		try { I = JSON.parse( box.getAttribute( 'data-i18n' ) || '{}' ); } catch ( e ) {}
		var price = form.querySelector( '[name="price"]' ), weight = form.querySelector( '[name="weight_kg"]' );
		var total = box.querySelector( '[name="total"]' ), forIn = box.querySelector( '[data-smq-total-for]' );
		var round = box.querySelector( '[data-smq-round]' ), formula = box.querySelector( '[data-smq-formula]' );
		var note = box.querySelector( '[data-smq-note]' ), row = box.querySelector( '[data-smq-kg-only]' );
		var label = box.querySelector( '[data-smq-total-label]' ), reqKg = parseFloat( box.getAttribute( 'data-request-kg' ) ) || 0;
		var helpText = note.textContent, rounded = false;
		var mode = function () { var r = form.querySelector( '[name="price_mode"]:checked' ); return r ? r.value : 'kg'; };
		var kg = function () { return num( weight && weight.value ) || reqKg; };
		var key = function () { return num( price.value ) + '|' + num( weight && weight.value ); };
		// A total kept from before (edit, or sent back with an error) is "set by hand".
		var manual = !! ( forIn.value && forIn.value === key() && num( total.value ) > 0 );
		box.hidden = false;

		function perKgText( t, w ) { return fmt( I.perkg || '%1$s DA/kg for %2$s kg.', 0 ).replace( '%1$s', money( t / w, 2 ) ).replace( '%2$s', money( w ) ); }
		function update( from ) {
			var p = num( price.value ), w = kg(), m = mode();
			box.classList.toggle( 'is-total-mode', 'total' === m );
			if ( 'total' === m ) {
				row.hidden = true; total.disabled = true; forIn.value = '';
				label.textContent = label.getAttribute( 'data-total' );
				formula.textContent = '';
				note.textContent = p > 0 && w > 0 ? perKgText( p, w ) : ( p > 0 ? I.noweight || '' : '' );
				box.hidden = ! ( p > 0 );
				return;
			}
			box.hidden = false; row.hidden = false; total.disabled = false;
			label.textContent = label.getAttribute( 'data-kg' );
			var exact = p * w;
			if ( ! ( p > 0 && w > 0 ) ) {
				formula.textContent = ''; note.textContent = w > 0 ? helpText : ( I.noweight || '' ); round.hidden = true;
				if ( ! manual ) { total.value = ''; }
				return;
			}
			formula.textContent = ( I.formula || '%1$s × %2$s kg' ).replace( '%1$s', money( p, p % 1 ? 2 : 0 ) ).replace( '%2$s', money( w ) );
			if ( 'price' === from || ! manual ) {
				manual = false; rounded = false;
				total.value = money( Math.round( exact * 100 ) / 100, exact % 1 ? 2 : 0 );
				forIn.value = '';
			}
			var t = num( total.value );
			round.hidden = exact < 1000 || ( ! rounded && Math.round( exact / 1000 ) * 1000 === exact );
			round.textContent = rounded ? I.unround : I.round;
			box.classList.toggle( 'is-changed', manual );
			note.textContent = manual && t > 0 ? perKgText( t, w ) : helpText;
		}
		[ price, weight ].forEach( function ( x ) { if ( x ) { x.addEventListener( 'input', function () { update( 'price' ); } ); } } );
		$$( '[name="price_mode"]', form ).forEach( function ( r ) { r.addEventListener( 'change', function () { update( 'price' ); } ); } );
		total.addEventListener( 'input', function () {
			manual = num( total.value ) > 0; rounded = false;
			forIn.value = manual ? key() : '';
			update( 'total' );
		} );
		round.addEventListener( 'click', function () {
			var exact = num( price.value ) * kg();
			if ( rounded ) {
				rounded = false; manual = false; forIn.value = '';
				total.value = money( Math.round( exact * 100 ) / 100, exact % 1 ? 2 : 0 );
			} else {
				rounded = true; manual = true;
				total.value = money( Math.round( exact / 1000 ) * 1000 );
				forIn.value = key();
			}
			update( 'round' );
			total.focus();
		} );
		update( manual ? 'init' : 'price' );
	} );

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', flash );
	} else {
		flash();
	}
}() );
