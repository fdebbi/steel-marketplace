/**
 * AI photo tools of the publication form (0.52.0): « Recognise from my photos » and
 * « Assess the condition from my photos ». Sends the photos of the form (new files
 * and kept photos) to sm-ai, shows the common AI wait screen, then the suggestion.
 * Nothing is filled without a click: « Choose » sets type and size (window.smpSet),
 * « Apply » sets the condition and the surface.
 */
( function () {
	'use strict';
	var root = document.querySelector( '[data-smaip]' );
	var form = root ? root.closest( 'form' ) : null;
	if ( ! root || ! form ) { return; }
	var cfg;
	try { cfg = JSON.parse( root.getAttribute( 'data-smaip' ) ); } catch ( e ) { return; }
	var T = cfg.i18n || {};

	function el( tag, cls, text ) {
		var n = document.createElement( tag );
		if ( cls ) { n.className = cls; }
		if ( undefined !== text && null !== text ) { n.textContent = text; }
		return n;
	}
	function level( c ) { return c >= 0.55 ? T.likely : ( c >= 0.3 ? T.possible : T.unlikely ); }

	function photos() {
		var fd = new FormData();
		var n = 0;
		form.querySelectorAll( 'input[type="checkbox"][name="keep_photos[]"]:checked' ).forEach( function ( k ) { fd.append( 'keep[]', k.value ); n++; } );
		form.querySelectorAll( 'input[type="file"][name="photos[]"]' ).forEach( function ( f ) {
			Array.prototype.forEach.call( f.files || [], function ( file ) { fd.append( 'photos[]', file, file.name ); n++; } );
		} );
		return { fd: fd, n: n };
	}

	function credits( box, left ) {
		var small = box.querySelector( ':scope > small' );
		if ( ! small || 'number' !== typeof left || left < 0 || ! T.left ) { return; }
		if ( ! small.hasAttribute( 'data-hint' ) ) { small.setAttribute( 'data-hint', small.textContent ); }
		small.textContent = small.getAttribute( 'data-hint' ) + ' · ' + T.left.replace( '%d', left );
	}

	function message( out, text, warn ) {
		out.innerHTML = '';
		var p = el( 'p', 'smaip__msg' + ( warn ? ' is-warn' : '' ), text );
		out.appendChild( p );
	}

	function recognize( out, r ) {
		out.innerHTML = '';
		var list = el( 'div', 'smaip__cands' );
		var cands = ( r.candidates || [] ).filter( function ( c ) { return c && c.family; } );
		if ( ! cands.length ) {
			message( out, T.none, true );
		}
		cands.forEach( function ( c ) {
			var card = el( 'div', 'smaip__cand' );
			var sec = c.section;
			card.appendChild( el( 'strong', '', sec ? sec.designation : c.family_name ) );
			card.appendChild( el( 'span', 'smaip__lvl', level( c.confidence ) + ( sec ? '' : ' · ' + T.no_size ) ) );
			var bar = el( 'span', 'smaip__bar' ); var i = el( 'i' ); i.style.width = Math.round( c.confidence * 100 ) + '%'; bar.appendChild( i ); card.appendChild( bar );
			if ( c.reason ) { card.appendChild( el( 'small', '', c.reason ) ); }
			var ok = ( cfg.families || [] ).indexOf( c.family ) !== -1;
			if ( ok ) {
				var b = el( 'button', 'sm-btn sm-btn--ghost sm-btn--sm', T.choose );
				b.type = 'button';
				b.addEventListener( 'click', function () {
					list.querySelectorAll( '.smaip__cand' ).forEach( function ( x ) { x.classList.remove( 'is-chosen' ); var bb = x.querySelector( 'button' ); if ( bb ) { bb.textContent = T.choose; } } );
					card.classList.add( 'is-chosen' );
					b.textContent = T.chosen;
					var v = { family: c.family };
					if ( sec && sec.section_id ) { v.section = String( sec.section_id ); }
					if ( window.smpSet ) { window.smpSet( v ); }
				} );
				card.appendChild( b );
			} else {
				card.appendChild( el( 'em', 'smaip__other', T.other_cat ) );
				card.classList.add( 'is-off' );
			}
			list.appendChild( card );
		} );
		if ( cands.length ) {
			list.appendChild( el( 'p', 'smaip__checked', T.checked ) );
			out.appendChild( list );
		}
		if ( ( r.questions || [] ).length ) {
			out.appendChild( el( 'p', 'smaip__qh', T.doubt ) );
			var ul = el( 'ul', 'smaip__q' );
			r.questions.forEach( function ( q ) { ul.appendChild( el( 'li', '', q ) ); } );
			out.appendChild( ul );
		}
		if ( r.note ) { out.appendChild( el( 'p', 'smaip__note', r.note ) ); }
	}

	function chips( map, on ) {
		var w = el( 'div', 'smaip__chips' );
		Object.keys( map ).forEach( function ( k ) { var c = el( 'span', 'smaip__chip' + ( k === on ? ' is-on' : '' ), map[ k ] ); w.appendChild( c ); } );
		return w;
	}

	function condition( out, r ) {
		out.innerHTML = '';
		var box = el( 'div', 'smaip__sugg' );
		box.appendChild( el( 'p', 'smaip__h', T.suggest ) );
		box.appendChild( chips( cfg.conds || {}, r.condition ) );
		if ( r.surface ) {
			box.appendChild( el( 'p', 'smaip__qh', T.surface ) );
			box.appendChild( chips( cfg.surfaces || {}, r.surface ) );
		}
		var sees = ( r.sees || [] ).join( ', ' ), cannot = ( r.cannot_see || [] ).join( ', ' ) || T.cannot_default;
		if ( sees ) { var p1 = el( 'p', 'smaip__obs' ); p1.appendChild( el( 'strong', '', T.sees + ' ' ) ); p1.appendChild( document.createTextNode( sees + '.' ) ); box.appendChild( p1 ); }
		var p2 = el( 'p', 'smaip__obs' ); p2.appendChild( el( 'strong', '', T.cannot + ' ' ) ); p2.appendChild( document.createTextNode( cannot + '.' ) ); box.appendChild( p2 );
		if ( 'scrap' === r.condition && cfg.scrap_url ) {
			var s = el( 'p', 'smaip__msg is-warn' ); var a = el( 'a', '', T.scrap ); a.href = cfg.scrap_url; s.appendChild( a ); box.appendChild( s );
		}
		var acts = el( 'div', 'smaip__acts' );
		var label = ( cfg.conds || {} )[ r.condition ] || r.condition;
		var apply = el( 'button', 'sm-btn sm-btn--primary sm-btn--sm', ( T.apply || '%s' ).replace( '%s', label ) );
		apply.type = 'button';
		var ignore = el( 'button', 'sm-btn sm-btn--ghost sm-btn--sm', T.ignore );
		ignore.type = 'button';
		apply.addEventListener( 'click', function () {
			var radio = form.querySelector( 'input[type="radio"][name="condition"][value="' + r.condition + '"]' );
			var surf = form.querySelector( 'select[name="attrs[surface]"]' );
			if ( surf && r.surface && surf.querySelector( 'option[value="' + r.surface + '"]' ) ) { surf.value = r.surface; }
			if ( radio ) {
				radio.checked = true;
				radio.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			} else if ( surf ) {
				surf.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			}
			acts.innerHTML = '';
			acts.appendChild( el( 'p', 'smaip__done', T.applied ) );
		} );
		ignore.addEventListener( 'click', function () { out.innerHTML = ''; } );
		acts.appendChild( apply );
		acts.appendChild( ignore );
		box.appendChild( acts );
		out.appendChild( box );
	}

	root.querySelectorAll( '[data-smaip-run]' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var tool = btn.getAttribute( 'data-smaip-run' );
			var box = btn.closest( '[data-smaip-tool]' );
			var out = box.querySelector( '[data-smaip-out]' );
			if ( btn.getAttribute( 'aria-busy' ) === 'true' ) { return; } // Never twice: one credit.
			var p = photos();
			p.fd.append( 'action', 'sm_ai_form_photo' );
			p.fd.append( 'nonce', cfg.nonce );
			p.fd.append( 'tool', tool );
			btn.setAttribute( 'aria-busy', 'true' );
			btn.disabled = true;
			var stop = window.smAiWait ? window.smAiWait( box, T[ 'wait_' + tool ], T.sub, T.slow, '' ) : function () {};
			fetch( cfg.ajax, { method: 'POST', credentials: 'same-origin', body: p.fd } )
				.then( function ( r ) { return r.json().catch( function () { return { success: false, data: { message: T.error } }; } ); } )
				.then( function ( j ) {
					stop();
					btn.removeAttribute( 'aria-busy' );
					btn.disabled = false;
					if ( ! j || ! j.success ) {
						message( out, ( j && j.data && j.data.message ) || T.error, true );
						if ( j && j.data && 'blocked' !== j.data.state && 'ok' !== j.data.state && j.data.state ) { btn.disabled = true; }
						return;
					}
					credits( box, j.data.remaining );
					if ( 'condition' === tool ) { condition( out, j.data.result || {} ); } else { recognize( out, j.data.result || {} ); }
				} )
				.catch( function () {
					stop();
					btn.removeAttribute( 'aria-busy' );
					btn.disabled = false;
					message( out, T.error, true );
				} );
		} );
	} );
}() );
