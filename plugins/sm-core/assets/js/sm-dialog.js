/**
 * Centred messages of the platform (SM Core 0.8.6), on the site and in the
 * administration: questions before an action, success, warnings and errors,
 * instead of the browser's grey windows and of the banners at the top.
 *
 *   smDialog.open( { tone, title, text, items, ok, cancel, auto } ) → Promise<boolean>
 *     tone   question | danger | success | warning
 *     items  [ { label, go: function } ] — clickable lines (e.g. missing fields)
 *     ok     label of the main button; cancel: label or false; auto: ms before closing.
 *
 * Any button or link with data-sm-confirm="Question ? Details." asks first
 * (data-sm-confirm-ok: label of the button; data-sm-confirm-danger: red button).
 */
( function () {
	'use strict';
	var T = window.smDialogI18n || {};
	var t = function ( k, d ) { return T[ k ] || d; };
	var icons = { question: '✓', success: '✓', danger: '!', warning: '!' };
	var open = null;

	function el( tag, cls, text ) {
		var e = document.createElement( tag );
		if ( cls ) { e.className = cls; }
		if ( null != text ) { e.textContent = text; }
		return e;
	}

	function show( o ) {
		o = o || {};
		if ( T.auto ) { return Promise.resolve( true ); } // Automated tests (filter sm_dialog_auto).
		var tone = o.tone || 'question';
		if ( open ) { open.close( 'replaced' ); }
		return new Promise( function ( resolve ) {
			var d = el( 'dialog', 'sm-dlg sm-dlg--' + tone );
			d.setAttribute( 'aria-labelledby', 'sm-dlg-title' );
			var box = el( 'div', 'sm-dlg__box' );
			var ic = el( 'span', 'sm-dlg__ic', icons[ tone ] || '!' );
			ic.setAttribute( 'aria-hidden', 'true' );
			box.appendChild( ic );
			var h = el( 'h2', 'sm-dlg__title', o.title || '' );
			h.id = 'sm-dlg-title';
			box.appendChild( h );
			if ( o.text ) { box.appendChild( el( 'p', 'sm-dlg__text', o.text ) ); }
			var result = false;
			function finish( v ) { result = v; d.close(); }
			if ( o.items && o.items.length ) {
				var ul = el( 'ul', 'sm-dlg__items' );
				o.items.forEach( function ( it ) {
					var li = el( 'li' ), b = el( 'button', 'sm-dlg__item' );
					b.type = 'button';
					b.appendChild( el( 'span', '', it.label ) );
					b.appendChild( el( 'span', 'sm-dlg__go', '→' ) ).setAttribute( 'aria-hidden', 'true' );
					b.addEventListener( 'click', function () { finish( false ); if ( it.go ) { setTimeout( it.go, 30 ); } } );
					li.appendChild( b );
					ul.appendChild( li );
				} );
				box.appendChild( ul );
			}
			var bar = el( 'div', 'sm-dlg__buttons' );
			var cancel = null;
			if ( o.cancel !== false && o.cancel !== undefined ) {
				cancel = el( 'button', 'sm-dlg__btn sm-dlg__btn--ghost', o.cancel || t( 'cancel', 'Cancel' ) );
				cancel.type = 'button';
				cancel.addEventListener( 'click', function () { finish( false ); } );
				bar.appendChild( cancel );
			}
			var ok = el( 'button', 'sm-dlg__btn' + ( 'danger' === tone ? ' sm-dlg__btn--danger' : '' ), o.ok || t( 'ok', 'OK' ) );
			ok.type = 'button';
			ok.addEventListener( 'click', function () { finish( true ); } );
			bar.appendChild( ok );
			box.appendChild( bar );
			var timer = null;
			if ( o.auto ) {
				var prog = el( 'div', 'sm-dlg__bar' ), fill = el( 'i' );
				prog.appendChild( fill );
				box.appendChild( prog );
				fill.style.animationDuration = o.auto + 'ms';
				timer = setTimeout( function () { finish( true ); }, o.auto );
				d.addEventListener( 'mouseenter', function () { clearTimeout( timer ); fill.style.animationPlayState = 'paused'; } );
			}
			d.appendChild( box );
			d.addEventListener( 'cancel', function () { result = false; } ); // Escape.
			d.addEventListener( 'click', function ( e ) { if ( e.target === d && cancel ) { finish( false ); } } );
			d.addEventListener( 'close', function () {
				clearTimeout( timer );
				if ( open === d ) { open = null; }
				d.remove();
				if ( opener && opener.focus && 'replaced' !== d.returnValue ) { try { opener.focus( { preventScroll: true } ); } catch ( e ) {} }
				resolve( result );
			} );
			var opener = document.activeElement;
			document.body.appendChild( d );
			open = d;
			if ( d.showModal ) { d.showModal(); } else { d.setAttribute( 'open', '' ); }
			( 'danger' === tone && cancel ? cancel : ok ).focus();
		} );
	}

	window.smDialog = { open: show };

	/* Buttons and links that ask first: data-sm-confirm. */
	function split( q ) {
		var i = q.indexOf( '?' );
		if ( i < 0 ) { i = q.indexOf( '؟' ); }
		return i > -1 && i < q.length - 1 ? [ q.slice( 0, i + 1 ).trim(), q.slice( i + 1 ).trim() ] : [ q.trim(), '' ];
	}
	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest && e.target.closest( '[data-sm-confirm]' );
		if ( ! btn || btn.hasAttribute( 'data-sm-confirmed' ) ) { return; }
		e.preventDefault();
		e.stopImmediatePropagation();
		var q = split( btn.getAttribute( 'data-sm-confirm' ) || '' );
		var label = btn.getAttribute( 'data-sm-confirm-ok' ) || ( btn.textContent || btn.value || '' ).trim();
		var danger = btn.hasAttribute( 'data-sm-confirm-danger' ) || /danger|delete/.test( btn.className ) || /^(delete|withdraw|decline|cancel|remove|stop|undo|unlist|do_reset|reject)/.test( ( btn.value || btn.name || '' ).toLowerCase() );
		show( { tone: danger ? 'danger' : 'question', title: q[0], text: q[1], ok: label && label.length < 40 ? label : t( 'confirm', 'Confirm' ), cancel: t( 'cancel', 'Cancel' ) } ).then( function ( yes ) {
			if ( ! yes ) { return; }
			btn.setAttribute( 'data-sm-confirmed', '' );
			if ( btn.form && ( 'submit' === btn.type || 'BUTTON' === btn.tagName && ! btn.type ) ) {
				if ( btn.form.requestSubmit ) { btn.form.requestSubmit( btn ); } else { btn.click(); }
			} else {
				btn.click();
			}
			btn.removeAttribute( 'data-sm-confirmed' );
		} );
	}, true );

	/* Administration: the notices of the ReuSteel pages open in the centre. */
	if ( T.adminFlash && ! T.auto ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			var n = document.querySelector( '.wrap > .notice:not(.inline):not(.sm-keep), .wrap > div.updated, .wrap > div.error' );
			if ( ! n ) { return; }
			var err = /notice-error|error/.test( n.className ), warn = /notice-warning/.test( n.className );
			var text = ( n.textContent || '' ).replace( /\s+/g, ' ' ).replace( t( 'dismiss', 'Dismiss this notice.' ), '' ).trim();
			if ( ! text ) { return; }
			n.hidden = true;
			show( { tone: err || warn ? 'warning' : 'success', title: text, ok: err || warn ? t( 'understood', 'Understood' ) : t( 'ok', 'OK' ), auto: err || warn ? 0 : 4000 } );
		} );
	}
}() );
