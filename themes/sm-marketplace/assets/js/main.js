/**
 * SM Marketplace — front-end interactions (no dependencies).
 * Presentation behaviour only: no business rules, no calculations.
 */
( function () {
	'use strict';

	var cfg = window.smTheme || { i18n: {} };
	var $ = function ( s, c ) { return ( c || document ).querySelector( s ); };
	var $$ = function ( s, c ) { return Array.prototype.slice.call( ( c || document ).querySelectorAll( s ) ); };

	function toast( msg ) {
		var t = $( '[data-sm-toast]' );
		if ( ! t ) { return; }
		t.textContent = msg;
		t.hidden = false;
		clearTimeout( t._timer );
		t._timer = setTimeout( function () { t.hidden = true; }, 2600 );
	}

	/* ---- Mobile drawer -------------------------------------------------- */
	var drawer = $( '[data-sm-drawer]' );
	var opener = $( '[data-sm-drawer-open]' );
	function setDrawer( open ) {
		if ( ! drawer ) { return; }
		drawer.hidden = ! open;
		document.body.classList.toggle( 'sm-no-scroll', open );
		if ( opener ) { opener.setAttribute( 'aria-expanded', String( open ) ); }
		if ( open ) {
			var first = $( '.sm-drawer__panel a, .sm-drawer__panel button', drawer );
			if ( first ) { first.focus(); }
		} else if ( opener ) {
			opener.focus();
		}
	}
	if ( opener ) { opener.addEventListener( 'click', function () { setDrawer( true ); } ); }
	$$( '[data-sm-drawer-close]' ).forEach( function ( el ) { el.addEventListener( 'click', function () { setDrawer( false ); } ); } );

	/* ---- Mega menu (click/keyboard, closes on outside click and Esc) ----- */
	var megaButtons = $$( '[data-sm-mega]' );
	function closeMegas( except ) {
		megaButtons.forEach( function ( b ) {
			if ( b === except ) { return; }
			b.setAttribute( 'aria-expanded', 'false' );
			var panel = document.getElementById( b.getAttribute( 'aria-controls' ) );
			if ( panel ) { panel.hidden = true; }
		} );
	}
	megaButtons.forEach( function ( btn ) {
		btn.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			var open = btn.getAttribute( 'aria-expanded' ) !== 'true';
			closeMegas( btn );
			btn.setAttribute( 'aria-expanded', String( open ) );
			var panel = document.getElementById( btn.getAttribute( 'aria-controls' ) );
			if ( panel ) { panel.hidden = ! open; }
		} );
	} );
	document.addEventListener( 'click', function ( e ) {
		if ( ! e.target.closest( '.sm-mega' ) ) { closeMegas(); }
	} );
	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key !== 'Escape' ) { return; }
		closeMegas();
		if ( drawer && ! drawer.hidden ) { setDrawer( false ); }
		setFilters( false );
	} );

	/* ---- Filters sheet --------------------------------------------------- */
	var filters = $( '[data-sm-filters]' );
	var filtersOpen = $( '[data-sm-filters-open]' );
	function setFilters( open ) {
		if ( ! filters ) { return; }
		filters.classList.toggle( 'is-open', open );
		if ( window.matchMedia( '(max-width: 1023px)' ).matches ) {
			document.body.classList.toggle( 'sm-no-scroll', open );
		}
		if ( filtersOpen ) { filtersOpen.setAttribute( 'aria-expanded', String( open ) ); }
	}
	if ( filtersOpen ) { filtersOpen.addEventListener( 'click', function () { setFilters( true ); } ); }
	$$( '[data-sm-filters-close]' ).forEach( function ( b ) { b.addEventListener( 'click', function () { setFilters( false ); } ); } );
	if ( filters ) {
		filters.addEventListener( 'click', function ( e ) { if ( e.target === filters ) { setFilters( false ); } } );
	}
	$$( '[data-sm-autosubmit]' ).forEach( function ( s ) { s.addEventListener( 'change', function () { s.form.submit(); } ); } );

	/* ---- Saved listings (local only until sm-core stores them) ----------- */
	var store = {
		get: function () { try { return JSON.parse( localStorage.getItem( 'sm_saved' ) || '[]' ); } catch ( e ) { return []; } },
		set: function ( v ) { try { localStorage.setItem( 'sm_saved', JSON.stringify( v ) ); } catch ( e ) {} }
	};
	var saved = store.get();
	$$( '[data-sm-save]' ).forEach( function ( btn ) {
		var id = btn.getAttribute( 'data-sm-save' );
		btn.setAttribute( 'aria-pressed', String( saved.indexOf( id ) > -1 ) );
		btn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			var list = store.get();
			var i = list.indexOf( id );
			if ( i > -1 ) { list.splice( i, 1 ); toast( cfg.i18n.removed ); } else { list.push( id ); toast( cfg.i18n.saved ); }
			store.set( list );
			$$( '[data-sm-save="' + id + '"]' ).forEach( function ( b ) { b.setAttribute( 'aria-pressed', String( i === -1 ) ); } );
		} );
	} );

	/* ---- Share ----------------------------------------------------------- */
	$$( '[data-sm-share]' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			if ( navigator.share ) { navigator.share( { title: document.title, url: location.href } ).catch( function () {} ); return; }
			if ( navigator.clipboard ) { navigator.clipboard.writeText( location.href ).then( function () { toast( location.href ); } ); }
		} );
	} );

	/* ---- Gallery --------------------------------------------------------- */
	$$( '[data-sm-gallery]' ).forEach( function ( g ) {
		var main = $( '[data-sm-gallery-main]', g );
		$$( '.sm-gallery__thumb', g ).forEach( function ( t ) {
			t.addEventListener( 'click', function () {
				main.src = t.getAttribute( 'data-src' );
				$$( '.sm-gallery__thumb', g ).forEach( function ( o ) { o.setAttribute( 'aria-pressed', String( o === t ) ); } );
			} );
		} );
	} );

	/* ---- Password visibility -------------------------------------------- */
	$$( '[data-sm-password]' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var input = document.getElementById( btn.getAttribute( 'aria-controls' ) );
			var show = input.type === 'password';
			input.type = show ? 'text' : 'password';
			var label = $( '.screen-reader-text', btn );
			if ( label ) { label.textContent = show ? cfg.i18n.hide : cfg.i18n.show; }
		} );
	} );

	/* ---- Register: show fields for the chosen account class -------------- */
	$$( '[data-sm-register]' ).forEach( function ( form ) {
		function sync() {
			var checked = $( 'input[name="account_class"]:checked', form );
			var cls = checked ? checked.value : 'commercial';
			$$( '[data-sm-show-for]', form ).forEach( function ( el ) {
				var on = el.getAttribute( 'data-sm-show-for' ) === cls;
				el.hidden = ! on;
				$$( 'input,select', el ).forEach( function ( i ) { i.disabled = ! on; } );
			} );
		}
		$$( 'input[name="account_class"]', form ).forEach( function ( r ) { r.addEventListener( 'change', sync ); } );
		sync();
	} );

	/* ---- Publish stepper -------------------------------------------------- */
	$$( '[data-sm-stepper]' ).forEach( function ( form ) {
		var steps = $$( '.sm-step', form );
		var labels = $$( '[data-step-label]', form );
		var prev = $( '[data-sm-prev]', form );
		var next = $( '[data-sm-next]', form );
		var submit = $( '[data-sm-submit]', form );
		var current = 0;

		function show( i, initial ) {
			current = Math.max( 0, Math.min( steps.length - 1, i ) );
			steps.forEach( function ( s, n ) { s.hidden = n !== current; } );
			labels.forEach( function ( l, n ) {
				if ( n === current ) { l.setAttribute( 'aria-current', 'step' ); } else { l.removeAttribute( 'aria-current' ); }
				l.classList.toggle( 'is-done', n < current );
			} );
			prev.hidden = current === 0;
			next.hidden = current === steps.length - 1;
			submit.hidden = current !== steps.length - 1;
			if ( initial ) { return; }
			var title = $( '.sm-panel__title', steps[ current ] );
			if ( title ) { title.setAttribute( 'tabindex', '-1' ); title.focus( { preventScroll: true } ); }
			form.scrollIntoView( { block: 'start', behavior: 'smooth' } );
		}
		next.addEventListener( 'click', function () { show( current + 1 ); } );
		prev.addEventListener( 'click', function () { show( current - 1 ); } );

		function syncMarket() {
			var m = $( '[data-sm-market]:checked', form );
			var market = m ? m.value : 'products';
			$$( '[data-sm-for-market]', form ).forEach( function ( el ) {
				var on = el.getAttribute( 'data-sm-for-market' ).split( ' ' ).indexOf( market ) > -1;
				el.hidden = ! on;
				if ( el.tagName === 'SELECT' ) { el.disabled = ! on; }
			} );
		}
		$$( '[data-sm-market]', form ).forEach( function ( r ) { r.addEventListener( 'change', syncMarket ); } );
		syncMarket();

		function syncWeight() {
			var w = $( '[data-sm-weight-source]:checked', form );
			var box = $( '[data-sm-actual-weight]', form );
			if ( box ) { box.hidden = ! w || w.value !== 'actual'; }
		}
		$$( '[data-sm-weight-source]', form ).forEach( function ( r ) { r.addEventListener( 'change', syncWeight ); } );
		syncWeight();

		function syncMode() {
			var mode = $( '[data-sm-mode]:checked', form );
			var inv = $( '[data-sm-inventory]', form );
			if ( inv ) { inv.hidden = ! mode || mode.value !== 'dismantled'; }
		}
		$$( '[data-sm-mode]', form ).forEach( function ( r ) { r.addEventListener( 'change', syncMode ); } );
		syncMode();

		var rows = $( '[data-sm-rows]', form );
		var add = $( '[data-sm-row-add]', form );
		if ( rows && add ) {
			add.addEventListener( 'click', function () {
				var clone = rows.rows[ 0 ].cloneNode( true );
				$$( 'input', clone ).forEach( function ( i ) { i.value = ''; } );
				rows.appendChild( clone );
				$( 'input', clone ).focus();
			} );
			rows.addEventListener( 'click', function ( e ) {
				var rm = e.target.closest( '[data-sm-row-remove]' );
				if ( rm && rows.rows.length > 1 ) { rm.closest( 'tr' ).remove(); }
			} );
		}
		show( 0, true );
	} );

	/* ---- Photo previews --------------------------------------------------- */
	$$( '[data-sm-upload] input[type="file"]' ).forEach( function ( input ) {
		var list = input.closest( 'form' ) && $( '[data-sm-previews]', input.closest( 'form' ) );
		input.addEventListener( 'change', function () {
			if ( ! list ) { return; }
			list.innerHTML = '';
			Array.prototype.slice.call( input.files, 0, 10 ).forEach( function ( f ) {
				var li = document.createElement( 'li' );
				var img = document.createElement( 'img' );
				img.alt = '';
				img.src = URL.createObjectURL( f );
				li.appendChild( img );
				list.appendChild( li );
			} );
		} );
	} );

	/* ---- Demo forms: no submission until the plugins handle them ---------- */
	$$( '[data-sm-demo-form]' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			toast( form.getAttribute( 'data-sm-demo-message' ) || '✓' );
		} );
	} );
	/* Confirm destructive buttons (data-sm-confirm="question"). */
	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest && e.target.closest( '[data-sm-confirm]' );
		if ( btn && ! window.confirm( btn.getAttribute( 'data-sm-confirm' ) ) ) {
			e.preventDefault();
		}
	} );

}() );
