/**
 * « Write with the AI » (0.45.0, sm-ai 0.2.0): the description of a listing is
 * written from what the seller already entered in the publication form
 * (labels and values, and the summary computed by the platform). The text goes
 * into the field, where the seller edits it; « Undo » brings back his text.
 */
( function () {
	'use strict';
	var $$ = function ( s, c ) { return Array.prototype.slice.call( ( c || document ).querySelectorAll( s ) ); };
	var clean = function ( t ) { return String( t || '' ).replace( /\s+/g, ' ' ).trim(); };

	function shown( el ) { return !! ( el.offsetWidth || el.offsetHeight || el.getClientRects().length ); }

	function labelOf( el ) {
		var l = el.id && document.querySelector( 'label[for="' + el.id + '"]' );
		if ( ! l ) { l = el.closest( 'label' ); }
		if ( ! l ) {
			var f = el.closest( '.sm-field, fieldset' );
			l = f && f.querySelector( 'label, legend' );
		}
		var t = l ? l.cloneNode( true ) : null;
		if ( t ) { $$( '.sm-optional, .sm-field-error, svg', t ).forEach( function ( x ) { x.remove(); } ); }
		return clean( t ? t.textContent : ( el.getAttribute( 'aria-label' ) || el.name ) );
	}

	/* What the seller entered, as « Label: value » lines. */
	function facts( form, target ) {
		var out = [], seen = {};
		$$( 'input, select, textarea', form ).forEach( function ( el ) {
			if ( el === target || el.disabled || ! el.name || /^(_|sm_|action|intent|product_id)/.test( el.name ) ) { return; }
			var type = ( el.type || '' ).toLowerCase();
			if ( [ 'hidden', 'file', 'password', 'submit', 'button' ].indexOf( type ) > -1 ) { return; }
			if ( ! shown( el ) && 'SELECT' !== el.tagName ) { return; }
			var v = '';
			if ( 'checkbox' === type || 'radio' === type ) {
				if ( ! el.checked ) { return; }
				var lb = el.closest( 'label' );
				v = clean( lb ? lb.textContent : el.value );
			} else if ( 'SELECT' === el.tagName ) {
				if ( ! el.value ) { return; }
				v = clean( el.options[ el.selectedIndex ] && el.options[ el.selectedIndex ].text );
			} else {
				v = clean( el.value );
			}
			if ( ! v ) { return; }
			var line = labelOf( el ) + ': ' + v;
			if ( ! seen[ line ] ) { seen[ line ] = 1; out.push( line ); }
		} );
		// Figures computed by the platform (weight, bars…), only those already known.
		$$( '.smp-summary dl > div', form ).forEach( function ( row ) {
			var dt = row.querySelector( 'dt' ), dd = row.querySelector( 'dd' );
			var v = clean( dd && dd.textContent );
			if ( dt && v && '—' !== v ) { out.push( clean( dt.textContent ) + ' (computed by the platform): ' + v ); }
		} );
		return out.join( '\n' ).slice( 0, 3000 );
	}

	function dialog( o ) { return window.smDialog ? window.smDialog.open( o ) : Promise.resolve( window.confirm( o.title ) ); }

	$$( '[data-smai-describe]' ).forEach( function ( btn ) {
		var cfg;
		try { cfg = JSON.parse( btn.getAttribute( 'data-smai-describe' ) ); } catch ( e ) { return; }
		var T = cfg.i18n || {};
		var target = document.getElementById( cfg.target );
		var form = btn.closest( 'form' );
		if ( ! target || ! form ) { return; }
		var note = target.parentNode.querySelector( '[data-smai-desc-note]' );
		var label = btn.querySelector( '[data-smai-describe-label]' );
		var idle = label ? label.textContent : '';
		var before = null;

		var timer = null;
		function busy( on ) {
			btn.disabled = on;
			btn.classList.toggle( 'is-busy', on );
			target.classList.toggle( 'is-ai-writing', on );
			target.setAttribute( 'aria-busy', on ? 'true' : 'false' );
			clearInterval( timer );
			if ( ! label ) { return; }
			if ( ! on ) { label.textContent = idle; return; }
			// 0.51.0: seconds counter while the AI writes (the button stays blocked: one credit).
			var t0 = Date.now();
			var tick = function () { label.textContent = T.busy + ' ' + Math.round( ( Date.now() - t0 ) / 1000 ) + ' s'; };
			tick();
			timer = setInterval( tick, 1000 );
		}

		function showNote( text ) {
			if ( ! note ) { return; }
			note.textContent = '';
			note.appendChild( document.createTextNode( text + ' ' ) );
			var undo = document.createElement( 'button' );
			undo.type = 'button';
			undo.className = 'smai-desc-note__undo';
			undo.textContent = T.undo;
			undo.addEventListener( 'click', function () {
				target.value = before || '';
				target.dispatchEvent( new Event( 'input', { bubbles: true } ) );
				note.hidden = true;
				target.focus();
			} );
			note.appendChild( undo );
			note.hidden = false;
		}

		function run() {
			busy( true );
			var body = new FormData();
			body.append( 'action', 'sm_ai_describe' );
			body.append( 'nonce', cfg.nonce );
			body.append( 'facts', facts( form, target ) );
			fetch( cfg.ajax, { method: 'POST', body: body, credentials: 'same-origin' } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( r ) {
					busy( false );
					if ( ! r || ! r.success ) {
						var d = ( r && r.data ) || {};
						if ( 'upgrade' === d.state ) {
							dialog( { tone: 'warning', title: T.upgradeTx, items: [
								{ label: T.plans, go: function () { window.location.href = T.plansUrl; } },
								{ label: T.buy, go: function () { window.location.href = T.buyUrl; } }
							] } );
						} else {
							dialog( { tone: 'warning', title: d.message || T.error } );
						}
						return;
					}
					before = target.value;
					target.value = r.data.description || '';
					target.classList.add( 'is-ai' );
					target.dispatchEvent( new Event( 'input', { bubbles: true } ) );
					var left = r.data.remaining;
					var msg = ( null === left || undefined === left ) ? T.doneNoLeft : String( T.done ).replace( '%s', left );
					showNote( ( r.data.simulation ? '[' + T.simulation + '] ' : '' ) + msg );
					target.focus();
				} )
				.catch( function () { busy( false ); dialog( { tone: 'warning', title: T.error } ); } );
		}

		btn.addEventListener( 'click', function () {
			if ( clean( target.value ).length > 0 ) {
				dialog( { tone: 'question', title: T.replace, text: T.replaceTx, ok: T.replaceOk } ).then( function ( yes ) { if ( yes ) { run(); } } );
			} else {
				run();
			}
		} );
	} );
}() );
