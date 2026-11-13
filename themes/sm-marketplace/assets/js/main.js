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

	/* ---- A card picture that fails to load leaves the steel ground, not a broken icon */
	document.addEventListener( 'error', function ( e ) {
		var img = e.target;
		if ( img && img.tagName === 'IMG' && img.closest && img.closest( '.sm-card__media' ) ) { img.style.visibility = 'hidden'; }
	}, true );
	$$( '.sm-card__media img' ).forEach( function ( img ) {
		if ( img.complete && ! img.naturalWidth ) { img.style.visibility = 'hidden'; }
	} );

	/* ---- Account menu: rail on computers, sliding panel on phones -------- */
	$$( '[data-sm-dash]' ).forEach( function ( dash ) {
		var root = document.documentElement, side = $( '#sm-dash-nav', dash ), scrim = $( '.sm-dash__scrim', dash );
		var collapse = $( '[data-sm-nav-collapse]', dash ), opener = $( '[data-sm-nav-open]', dash ), lastFocus = null;
		function syncCollapse() {
			if ( ! collapse ) { return; }
			var mini = root.getAttribute( 'data-sm-nav' ) === 'mini';
			collapse.setAttribute( 'aria-expanded', mini ? 'false' : 'true' );
			collapse.setAttribute( 'aria-label', collapse.getAttribute( mini ? 'data-label-expand' : 'data-label-collapse' ) );
		}
		syncCollapse();
		if ( collapse ) {
			collapse.addEventListener( 'click', function () {
				var mini = root.getAttribute( 'data-sm-nav' ) !== 'mini';
				if ( mini ) { root.setAttribute( 'data-sm-nav', 'mini' ); } else { root.removeAttribute( 'data-sm-nav' ); }
				try { localStorage.setItem( 'sm-nav', mini ? 'mini' : 'full' ); } catch ( e ) {}
				syncCollapse();
			} );
		}
		function open() {
			lastFocus = document.activeElement;
			dash.setAttribute( 'data-nav-open', '' );
			if ( scrim ) { scrim.hidden = false; }
			root.classList.add( 'sm-nav-lock' );
			if ( opener ) { opener.setAttribute( 'aria-expanded', 'true' ); }
			var first = $( 'a, button', side );
			if ( first ) { setTimeout( function () { first.focus(); }, 60 ); }
		}
		function close() {
			if ( ! dash.hasAttribute( 'data-nav-open' ) ) { return; }
			dash.removeAttribute( 'data-nav-open' );
			if ( scrim ) { scrim.hidden = true; }
			root.classList.remove( 'sm-nav-lock' );
			if ( opener ) { opener.setAttribute( 'aria-expanded', 'false' ); }
			if ( lastFocus && lastFocus.focus ) { lastFocus.focus(); }
		}
		if ( opener ) { opener.addEventListener( 'click', open ); }
		$$( '[data-sm-nav-close]', dash ).forEach( function ( el ) { el.addEventListener( 'click', close ); } );
		document.addEventListener( 'keydown', function ( e ) { if ( 'Escape' === e.key ) { close(); } } );
		window.addEventListener( 'resize', function () { if ( window.innerWidth >= 1024 ) { close(); } } );
	} );

	/* ---- Account dark mode: remembered in this browser ------------------ */
	$$( '[data-sm-theme-toggle]' ).forEach( function ( btn ) {
		var root = document.documentElement;
		function sync() { btn.setAttribute( 'aria-pressed', root.getAttribute( 'data-sm-theme' ) === 'dark' ? 'true' : 'false' ); }
		sync();
		btn.addEventListener( 'click', function () {
			var dark = root.getAttribute( 'data-sm-theme' ) !== 'dark';
			if ( dark ) { root.setAttribute( 'data-sm-theme', 'dark' ); } else { root.removeAttribute( 'data-sm-theme' ); }
			try { localStorage.setItem( 'sm-theme', dark ? 'dark' : 'light' ); } catch ( e ) {}
			sync();
		} );
	} );

	/* ---- Dashboard tabs: keep the current tab in view on small screens --- */
	$$( '.sm-dash__tabs' ).forEach( function ( bar ) {
		var cur = $( '[aria-current]', bar );
		if ( cur && bar.scrollWidth > bar.clientWidth ) {
			var start = document.documentElement.dir === 'rtl' ? bar.getBoundingClientRect().right - cur.getBoundingClientRect().right : cur.getBoundingClientRect().left - bar.getBoundingClientRect().left;
			bar.scrollLeft += ( document.documentElement.dir === 'rtl' ? -1 : 1 ) * Math.max( 0, start - 24 );
		}
	} );

	/* ---- Mobile drawer -------------------------------------------------- */
	var drawer = $( '[data-sm-drawer]' );
	var opener = $( '[data-sm-drawer-open]' );
	var calm = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	function setDrawer( open ) {
		if ( ! drawer ) { return; }
		if ( ! open && ! drawer.hidden && ! calm ) {
			// Let the panel slide out before hiding it.
			drawer.classList.add( 'is-leaving' );
			setTimeout( function () { drawer.hidden = true; drawer.classList.remove( 'is-leaving' ); }, 180 );
		} else {
			drawer.classList.remove( 'is-leaving' );
			drawer.hidden = ! open;
		}
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
	/* Live count of the filter panel; empty fields left out of the address (0.24.0). */
	$$( 'form[data-sm-count]' ).forEach( function ( form ) {
		var btn = form.querySelector( '[data-sm-count-label]' );
		var timer = null;
		var seq = 0;
		function params() {
			var out = new URLSearchParams();
			new FormData( form ).forEach( function ( v, k ) { if ( String( v ).trim() !== '' ) { out.append( k, v ); } } );
			return out;
		}
		function refresh() {
			if ( ! btn || ! window.fetch ) { return; }
			var mine = ++seq;
			btn.classList.add( 'is-loading' );
			fetch( form.getAttribute( 'data-sm-count' ) + '&' + params().toString(), { credentials: 'same-origin' } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( j ) { if ( mine === seq && j && j.success ) { btn.textContent = j.data.label; } } )
				.catch( function () {} )
				.then( function () { if ( mine === seq ) { btn.classList.remove( 'is-loading' ); } } );
		}
		form.addEventListener( 'change', refresh );
		form.addEventListener( 'input', function ( e ) {
			if ( e.target.type === 'radio' || e.target.type === 'checkbox' || e.target.tagName === 'SELECT' ) { return; }
			clearTimeout( timer );
			timer = setTimeout( refresh, 400 );
		} );
		form.addEventListener( 'submit', function () {
			Array.prototype.forEach.call( form.elements, function ( el ) {
				if ( el.name && ( el.type === 'search' || el.type === 'number' || el.type === 'text' || el.tagName === 'SELECT' || ( el.type === 'radio' && el.checked ) ) && String( el.value ).trim() === '' ) { el.disabled = true; }
			} );
		} );
	} );
	/* Filters (0.25.0): designations of the chosen family only. */
	$$( '[data-sm-family]' ).forEach( function ( set ) {
		var form = set.closest( 'form' );
		var sel = form && form.querySelector( '[data-sm-designation]' );
		if ( ! sel ) { return; }
		function narrow() {
			var fam = ( set.querySelector( 'input:checked' ) || {} ).value || '';
			Array.prototype.forEach.call( sel.options, function ( o ) {
				if ( ! o.value ) { return; }
				var ok = ! fam || o.value.indexOf( fam + ' ' ) === 0 || o.value === fam;
				o.hidden = ! ok;
				o.disabled = ! ok;
				if ( ! ok && o.selected ) { sel.value = ''; }
			} );
		}
		set.addEventListener( 'change', narrow );
		narrow();
	} );

	/* Study request (0.27.0): show the prestations of the chosen engineering service. */
	$$( '[data-sm-service]' ).forEach( function ( sel ) {
		var form = sel.form;
		function show() {
			$$( '[data-sm-prest]', form ).forEach( function ( fs ) {
				var on = fs.getAttribute( 'data-sm-prest' ) === sel.value;
				fs.hidden = ! on;
				fs.disabled = ! on;
			} );
		}
		sel.addEventListener( 'change', show );
		show();
	} );

	/* Buy / make an offer on a listing (0.24.0). */
	var buyPanel = $( '[data-sm-buy-panel]' );
	var buyForm = $( '[data-sm-buy-form]' );
	function buyUpdate() {
		if ( ! buyForm ) { return; }
		var kindEl = buyForm.querySelector( 'input[name="kind"]:checked' );
		var offer = kindEl && kindEl.value === 'offer';
		var modeEl = buyForm.querySelector( 'input[name="mode"]:checked' ) || buyForm.querySelector( 'input[name="mode"]' );
		var lot = modeEl && modeEl.value === 'lot';
		buyForm.classList.toggle( 'is-offer', offer );
		buyForm.classList.toggle( 'is-lot', lot );
		var total = 0;
		if ( lot ) {
			total = parseFloat( modeEl.getAttribute( 'data-total' ) || '0' );
		} else {
			var q = buyForm.querySelector( '[name="quantity"]' );
			if ( q ) { total = ( parseFloat( String( q.value ).replace( ',', '.' ) ) || 0 ) * parseFloat( q.getAttribute( 'data-unit-price' ) || '0' ); }
		}
		var out = buyForm.querySelector( '[data-sm-buy-total]' );
		if ( out ) {
			var lang = document.documentElement.lang || 'fr';
			out.textContent = total > 0 ? new Intl.NumberFormat( lang.indexOf( 'ar' ) === 0 ? 'ar-DZ' : 'fr-FR', { maximumFractionDigits: 0 } ).format( Math.round( total ) ) + '\u00a0' + ( out.getAttribute( 'data-currency' ) || 'DA' ) : '—';
		}
		var btn = buyForm.querySelector( '[data-sm-buy-submit] span' );
		if ( btn ) { btn.textContent = buyForm.querySelector( '[data-sm-buy-submit]' ).getAttribute( offer ? 'data-offer' : 'data-order' ); }
	}
	if ( buyForm ) {
		buyForm.addEventListener( 'change', buyUpdate );
		buyForm.addEventListener( 'input', buyUpdate );
		buyUpdate();
	}
	$$( '[data-sm-buy]' ).forEach( function ( a ) {
		a.addEventListener( 'click', function ( e ) {
			if ( ! buyPanel ) { return; }
			e.preventDefault();
			buyPanel.hidden = false;
			var want = a.getAttribute( 'data-sm-buy' );
			var r = buyForm && buyForm.querySelector( 'input[name="kind"][value="' + want + '"]' );
			if ( r ) { r.checked = true; buyUpdate(); }
			buyPanel.scrollIntoView( { behavior: window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth', block: 'start' } );
			var first = buyPanel.querySelector( want === 'offer' ? '#buy-amount' : '#buy-qty' ) || buyPanel.querySelector( 'input:not([type=hidden]),a.sm-btn' );
			if ( first ) { setTimeout( function () { first.focus( { preventScroll: true } ); }, 300 ); }
		} );
	} );
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
			var cls = checked ? checked.value : 'company';
			$$( '[data-sm-show-for]', form ).forEach( function ( el ) {
				// One or more classes, separated by spaces.
				var on = el.getAttribute( 'data-sm-show-for' ).split( ' ' ).indexOf( cls ) !== -1;
				el.hidden = ! on;
				$$( 'input,select', el ).forEach( function ( i ) {
					// A field inside a visible block that is itself hidden for this class stays disabled.
					var own = i.closest( '[data-sm-show-for]' );
					i.disabled = ! on || ( own && own !== el && own.getAttribute( 'data-sm-show-for' ).split( ' ' ).indexOf( cls ) === -1 );
				} );
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
	/* ---- Bell (0.16.0): floating panel of the latest notifications. ------ */
	$$( '[data-sm-notif]' ).forEach( function ( box ) {
		var btn = $( '[data-sm-notif-toggle]', box );
		var panel = $( '[data-sm-notif-panel]', box );
		if ( ! btn || ! panel ) { return; }
		function set( open ) {
			panel.hidden = ! open;
			btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			if ( open ) { var first = $( 'a, button', panel ); if ( first ) { first.focus(); } }
		}
		btn.addEventListener( 'click', function ( e ) { e.preventDefault(); set( panel.hidden ); } );
		document.addEventListener( 'click', function ( e ) { if ( ! panel.hidden && ! box.contains( e.target ) ) { set( false ); } } );
		document.addEventListener( 'keydown', function ( e ) { if ( 'Escape' === e.key && ! panel.hidden ) { set( false ); btn.focus(); } } );
	} );

	/* ---- Service form (0.15.2): "Other" shows the name field. ---------- */
	$$( '[data-sms-category]' ).forEach( function ( sel ) {
		var box = sel.form && $( '[data-sms-custom]', sel.form );
		if ( ! box ) { return; }
		var other = sel.options[ sel.options.length - 1 ].value;
		function upd() {
			var on = sel.value === other;
			box.hidden = ! on;
			var input = $( 'input', box );
			if ( input ) { input.required = on; if ( on && document.activeElement === sel ) { input.focus(); } }
		}
		sel.addEventListener( 'change', upd );
		upd();
	} );

	/* ---- Company page (0.14.0): request dialogs, phone, copy link -------- */
	$$( '[data-smc-open]' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var dlg = document.getElementById( btn.getAttribute( 'data-smc-open' ) );
			if ( ! dlg || typeof dlg.showModal !== 'function' ) { return; }
			var sel = $( '[data-smc-service-select]', dlg );
			if ( sel && btn.hasAttribute( 'data-smc-service' ) ) { sel.value = btn.getAttribute( 'data-smc-service' ); }
			dlg.showModal();
			var first = $( 'textarea, input:not([type=hidden]), select', dlg );
			if ( first ) { first.focus(); }
		} );
	} );
	$$( 'dialog.smc-dialog' ).forEach( function ( dlg ) {
		$$( '[data-smc-close]', dlg ).forEach( function ( b ) { b.addEventListener( 'click', function () { dlg.close(); } ); } );
		dlg.addEventListener( 'click', function ( e ) { if ( e.target === dlg ) { dlg.close(); } } );
	} );
	$$( '[data-smc-reveal]' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var li = btn.closest( 'li' );
			var link = $( '[data-smc-phone]', li );
			var mask = $( '[data-smc-phone-mask]', li );
			if ( link ) { link.hidden = false; link.focus(); }
			if ( mask ) { mask.hidden = true; }
			btn.hidden = true;
		} );
	} );
	$$( '[data-smc-copy]' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var url = btn.getAttribute( 'data-smc-copy' );
			if ( navigator.clipboard ) { navigator.clipboard.writeText( url ).then( function () { toast( btn.getAttribute( 'data-smc-copied' ) || url ); } ); }
		} );
	} );

	/* ---- File fields (0.15.0): every chosen file is listed with its name,
	   size and "Uploaded ✓"; it can be removed, and more can be added before
	   the form is saved (the input keeps the whole list). ------------------- */
	function fmtSize( b ) {
		var mb = b >= 1048576;
		var v = String( mb ? ( b / 1048576 ).toFixed( 1 ) : Math.max( 1, Math.round( b / 1024 ) ) );
		if ( 'ar' !== cfg.lang ) { v = v.replace( '.', ',' ); }
		return v + ( 'ar' === cfg.lang ? ( mb ? ' MB' : ' KB' ) : ( mb ? ' Mo' : ' Ko' ) );
	}
	var canSet = ( function () { try { return !! new DataTransfer(); } catch ( e ) { return false; } } )();
	$$( 'input[type="file"]' ).forEach( function ( input ) {
		if ( input.hasAttribute( 'data-sm-files-off' ) ) { return; }
		var box = input.closest( 'label' ) || input;
		var list = document.createElement( 'ul' );
		list.className = 'sm-files';
		list.setAttribute( 'role', 'list' );
		list.setAttribute( 'aria-live', 'polite' );
		box.insertAdjacentElement( 'afterend', list );
		var old = input.form && $( '[data-sm-previews]', input.form );
		if ( old ) { old.hidden = true; }
		var store = [];
		var label = box.querySelector && $( '[data-sm-filename-label]', box );
		var initial = label ? label.textContent : '';
		var max = parseInt( input.getAttribute( 'data-sm-max' ) || '0', 10 );
		function sync() {
			if ( canSet ) {
				var dt = new DataTransfer();
				store.forEach( function ( f ) { dt.items.add( f ); } );
				input.files = dt.files;
			}
			render();
		}
		function render() {
			list.innerHTML = '';
			store.forEach( function ( f, i ) {
				var li = document.createElement( 'li' );
				li.className = 'sm-files__item';
				var th = document.createElement( 'span' );
				th.className = 'sm-files__thumb';
				if ( /^image\//.test( f.type ) ) {
					var img = document.createElement( 'img' );
					img.alt = '';
					img.src = URL.createObjectURL( f );
					th.appendChild( img );
				} else {
					th.textContent = ( f.name.split( '.' ).pop() || '' ).toUpperCase().slice( 0, 4 );
				}
				var info = document.createElement( 'span' );
				info.className = 'sm-files__info';
				var nm = document.createElement( 'span' );
				nm.className = 'sm-files__name';
				nm.textContent = f.name;
				var meta = document.createElement( 'span' );
				meta.className = 'sm-files__meta';
				meta.textContent = fmtSize( f.size ) + ' · ';
				var ok = document.createElement( 'strong' );
				ok.textContent = ( cfg.i18n.uploaded || 'Uploaded' ) + ' ✓';
				meta.appendChild( ok );
				info.appendChild( nm );
				info.appendChild( meta );
				li.appendChild( th );
				li.appendChild( info );
				if ( canSet ) {
					var rm = document.createElement( 'button' );
					rm.type = 'button';
					rm.className = 'sm-files__remove';
					rm.setAttribute( 'aria-label', ( cfg.i18n.removeFile || 'Remove %s' ).replace( '%s', f.name ) );
					rm.textContent = '×';
					rm.addEventListener( 'click', function () { store.splice( i, 1 ); sync(); input.focus(); } );
					li.appendChild( rm );
				}
				list.appendChild( li );
			} );
			if ( label ) { label.textContent = store.length ? store.map( function ( f ) { return f.name; } ).join( ', ' ) : initial; }
			if ( box.classList ) { box.classList.toggle( 'has-file', store.length > 0 ); }
			var more = box.querySelector && $( 'strong', box );
			if ( more && input.multiple ) {
				if ( ! more.hasAttribute( 'data-sm-initial' ) ) { more.setAttribute( 'data-sm-initial', more.innerHTML ); }
				if ( store.length ) { more.textContent = cfg.i18n.addMore || 'Add other files'; } else { more.innerHTML = more.getAttribute( 'data-sm-initial' ); }
			}
		}
		input.addEventListener( 'change', function () {
			var picked = Array.prototype.slice.call( input.files || [] );
			if ( ! input.multiple || ! canSet ) {
				store = picked;
			} else {
				picked.forEach( function ( f ) {
					var dup = store.some( function ( g ) { return g.name === f.name && g.size === f.size; } );
					if ( ! dup ) { store.push( f ); }
				} );
				if ( max && store.length > max ) { store = store.slice( 0, max ); toast( cfg.i18n.tooMany ); }
			}
			sync();
		} );
	} );

	/* Files already saved: Replace / Delete (undo until the form is saved). */
	$$( '[data-sm-saved]' ).forEach( function ( list ) {
		var mode = list.getAttribute( 'data-sm-saved-mode' );
		var field = list.getAttribute( 'data-sm-saved-field' );
		list.classList.add( 'is-enhanced' );
		$$( '.sm-saved__item', list ).forEach( function ( li ) {
			var cb = $( 'input[type="checkbox"]', li );
			var actions = $( '[data-sm-saved-actions]', li );
			var state = $( '[data-sm-saved-state]', li );
			var initialState = state ? state.textContent : '';
			var del = $( '[data-sm-saved-delete]', li );
			var rep = $( '[data-sm-saved-replace]', li );
			if ( actions ) { actions.hidden = false; }
			function set( deleted, text ) {
				cb.checked = 'keep' === mode ? ! deleted : deleted;
				li.classList.toggle( 'is-deleted', deleted );
				if ( state ) { state.textContent = deleted ? text : initialState; }
				if ( del ) { $( 'span', del ).textContent = deleted ? cfg.i18n.undo : cfg.i18n.delete; }
			}
			if ( del ) { del.addEventListener( 'click', function () { set( ! li.classList.contains( 'is-deleted' ), cfg.i18n.toDelete ); } ); }
			if ( rep ) {
				rep.addEventListener( 'click', function () {
					var input = li.closest( 'form' ) && li.closest( 'form' ).querySelector( 'input[type="file"][name="' + field + '"]' );
					if ( ! input ) { return; }
					set( true, cfg.i18n.toReplace );
					input.click();
				} );
			}
		} );
	} );

	/* ---- Photo viewer (data-sm-lightbox="group"): click a photo to see it large. */
	( function () {
		var links = $$( 'a[data-sm-lightbox]' );
		if ( ! links.length ) { return; }
		var dlg = document.createElement( 'dialog' );
		dlg.className = 'sm-lightbox';
		dlg.innerHTML = '<div class="sm-lightbox__bar"><p class="sm-lightbox__cap"></p><span class="sm-lightbox__pos"></span><a class="sm-lightbox__orig" target="_blank" rel="noopener"></a><button type="button" class="sm-lightbox__close">×</button></div>' +
			'<div class="sm-lightbox__stage"><button type="button" class="sm-lightbox__nav sm-lightbox__nav--prev">‹</button><img alt=""><button type="button" class="sm-lightbox__nav sm-lightbox__nav--next">›</button></div>';
		document.body.appendChild( dlg );
		var img = $( 'img', dlg ), cap = $( '.sm-lightbox__cap', dlg ), pos = $( '.sm-lightbox__pos', dlg ), orig = $( '.sm-lightbox__orig', dlg );
		var prev = $( '.sm-lightbox__nav--prev', dlg ), next = $( '.sm-lightbox__nav--next', dlg ), close = $( '.sm-lightbox__close', dlg );
		close.setAttribute( 'aria-label', cfg.i18n.close || 'Close' );
		prev.setAttribute( 'aria-label', cfg.i18n.prev || 'Previous' );
		next.setAttribute( 'aria-label', cfg.i18n.next || 'Next' );
		orig.textContent = ( cfg.i18n.original || 'Original size' ) + ' ↗';
		var group = [], idx = 0, opener = null;
		function show( i ) {
			idx = ( i + group.length ) % group.length;
			var a = group[ idx ];
			img.src = a.href;
			img.alt = a.getAttribute( 'data-sm-caption' ) || '';
			cap.textContent = a.getAttribute( 'data-sm-caption' ) || '';
			pos.textContent = group.length > 1 ? ( idx + 1 ) + ' / ' + group.length : '';
			orig.href = a.href;
			prev.hidden = next.hidden = group.length < 2;
		}
		links.forEach( function ( a ) {
			a.addEventListener( 'click', function ( e ) {
				if ( typeof dlg.showModal !== 'function' || e.ctrlKey || e.metaKey ) { return; }
				e.preventDefault();
				opener = a;
				group = links.filter( function ( l ) { return l.getAttribute( 'data-sm-lightbox' ) === a.getAttribute( 'data-sm-lightbox' ); } );
				show( group.indexOf( a ) );
				dlg.showModal();
				close.focus();
			} );
		} );
		var rtl = document.documentElement.dir === 'rtl';
		prev.addEventListener( 'click', function () { show( idx - 1 ); } );
		next.addEventListener( 'click', function () { show( idx + 1 ); } );
		close.addEventListener( 'click', function () { dlg.close(); } );
		dlg.addEventListener( 'close', function () { if ( opener ) { opener.focus(); } } );
		dlg.addEventListener( 'click', function ( e ) { if ( e.target === dlg || e.target.classList.contains( 'sm-lightbox__stage' ) ) { dlg.close(); } } );
		dlg.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key ) { e.preventDefault(); dlg.close(); return; }
			if ( 'ArrowLeft' === e.key ) { show( idx + ( rtl ? 1 : -1 ) ); }
			if ( 'ArrowRight' === e.key ) { show( idx + ( rtl ? -1 : 1 ) ); }
		} );
		var x0 = null;
		dlg.addEventListener( 'touchstart', function ( e ) { x0 = e.touches[0].clientX; }, { passive: true } );
		dlg.addEventListener( 'touchend', function ( e ) {
			if ( null === x0 ) { return; }
			var dx = e.changedTouches[0].clientX - x0;
			if ( Math.abs( dx ) > 50 ) { show( idx + ( ( dx < 0 ) !== rtl ? 1 : -1 ) ); }
			x0 = null;
		} );
	} )();

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

	/* Long requests (AI assistant): disable the button and say it is working (data-sm-busy="label"). */
	document.querySelectorAll( 'form[data-sm-busy]' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function () {
			var btn = form.querySelector( 'button[type="submit"]' );
			form.setAttribute( 'aria-busy', 'true' );
			if ( btn ) {
				btn.disabled = true;
				btn.classList.add( 'is-busy' );
				var label = btn.querySelector( '[data-sm-busy-label]' ) || btn;
				label.textContent = form.getAttribute( 'data-sm-busy' );
			}
		} );
	} );

	/* Simple tabs (pricing page): buttons [data-sm-tab] show the panel #plans-{key}. */
	document.querySelectorAll( '[role="tablist"]' ).forEach( function ( list ) {
		var tabs = list.querySelectorAll( '[data-sm-tab]' );
		if ( ! tabs.length ) {
			return;
		}
		function show( tab ) {
			tabs.forEach( function ( t ) {
				var on = t === tab;
				t.setAttribute( 'aria-selected', on ? 'true' : 'false' );
				t.tabIndex = on ? 0 : -1;
				var panel = document.getElementById( t.getAttribute( 'aria-controls' ) );
				if ( panel ) {
					panel.hidden = ! on;
				}
			} );
		}
		tabs.forEach( function ( t, i ) {
			t.tabIndex = 'true' === t.getAttribute( 'aria-selected' ) ? 0 : -1;
			t.addEventListener( 'click', function () {
				show( t );
			} );
			t.addEventListener( 'keydown', function ( e ) {
				var rtl = 'rtl' === document.documentElement.dir;
				var next = { ArrowRight: rtl ? -1 : 1, ArrowLeft: rtl ? 1 : -1 }[ e.key ];
				if ( next ) {
					var n = tabs[ ( i + next + tabs.length ) % tabs.length ];
					n.focus();
					show( n );
					e.preventDefault();
				}
			} );
		} );
	} );

}() );
