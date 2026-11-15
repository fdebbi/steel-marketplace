/**
 * SM Marketplace — size list with search (0.36.0).
 *
 * Enhances every select[data-sm-picker]: a button opens a list grouped like the
 * select (optgroup), with a search field ("80x40", "80 × 40" and "80*40" all
 * match). The native select stays in the form and keeps the value; choosing a
 * size sets it and fires "change", so the rest of the page works as before.
 * Without JavaScript the plain select is used.
 */
( function () {
	'use strict';

	var n = 0;

	function norm( s ) {
		return String( s ).toLowerCase().replace( /[×*]/g, 'x' ).replace( /,/g, '.' ).replace( /\s+/g, '' );
	}

	function enhance( select ) {
		if ( select.hasAttribute( 'data-sm-picker-ready' ) ) { return; }
		select.setAttribute( 'data-sm-picker-ready', '' );
		n++;
		var id = ( select.id || 'sm-picker-' + n ) + '-picker';
		var label = select.id ? document.querySelector( 'label[for="' + select.id + '"]' ) : null;
		if ( label && ! label.id ) { label.id = id + '-label'; }

		var wrap = document.createElement( 'div' );
		wrap.className = 'sm-picker';
		select.parentNode.insertBefore( wrap, select );
		wrap.appendChild( select );
		select.classList.add( 'sm-picker__native' );
		select.tabIndex = -1;
		select.setAttribute( 'aria-hidden', 'true' );

		var button = document.createElement( 'button' );
		button.type = 'button';
		button.id = id;
		button.className = 'sm-picker__button';
		button.setAttribute( 'aria-haspopup', 'listbox' );
		button.setAttribute( 'aria-expanded', 'false' );
		if ( label ) { button.setAttribute( 'aria-labelledby', label.id + ' ' + id ); label.setAttribute( 'for', id ); }
		var value = document.createElement( 'span' );
		value.className = 'sm-picker__value';
		var chev = document.createElement( 'span' );
		chev.className = 'sm-picker__chev';
		chev.setAttribute( 'aria-hidden', 'true' );
		button.appendChild( value );
		button.appendChild( chev );
		wrap.appendChild( button );

		var pop = document.createElement( 'div' );
		pop.className = 'sm-picker__pop';
		pop.hidden = true;
		var search = document.createElement( 'input' );
		search.type = 'search';
		search.className = 'sm-picker__search';
		search.setAttribute( 'role', 'combobox' );
		search.setAttribute( 'aria-expanded', 'true' );
		search.setAttribute( 'aria-controls', id + '-list' );
		search.setAttribute( 'aria-autocomplete', 'list' );
		search.setAttribute( 'autocomplete', 'off' );
		search.placeholder = select.getAttribute( 'data-sm-picker-search' ) || '';
		search.setAttribute( 'aria-label', search.placeholder );
		var list = document.createElement( 'ul' );
		list.className = 'sm-picker__list';
		list.id = id + '-list';
		list.setAttribute( 'role', 'listbox' );
		if ( label ) { list.setAttribute( 'aria-labelledby', label.id ); }
		var empty = document.createElement( 'p' );
		empty.className = 'sm-picker__empty';
		empty.hidden = true;
		empty.textContent = select.getAttribute( 'data-sm-picker-empty' ) || '';
		pop.appendChild( search );
		pop.appendChild( list );
		pop.appendChild( empty );

		var other = null;
		var items = [];
		Array.prototype.forEach.call( select.children, function ( child ) {
			if ( 'OPTGROUP' === child.tagName ) {
				var g = document.createElement( 'li' );
				g.className = 'sm-picker__group';
				g.setAttribute( 'role', 'presentation' );
				g.textContent = child.label;
				list.appendChild( g );
				var members = [];
				Array.prototype.forEach.call( child.children, function ( o ) { members.push( add( o, g ) ); } );
				g._members = members;
				return;
			}
			if ( 'OPTION' !== child.tagName || '' === child.value ) { return; }
			if ( child.hasAttribute( 'data-sm-picker-other' ) ) {
				other = document.createElement( 'button' );
				other.type = 'button';
				other.className = 'sm-picker__other';
				other.id = id + '-other';
				other.textContent = child.textContent;
				other.setAttribute( 'data-value', child.value );
				pop.appendChild( other );
				other.addEventListener( 'click', function () { choose( child.value ); } );
				return;
			}
			add( child, null );
		} );

		function add( o, group ) {
			var li = document.createElement( 'li' );
			li.className = 'sm-picker__opt';
			li.id = id + '-o' + items.length;
			li.setAttribute( 'role', 'option' );
			li.setAttribute( 'data-value', o.value );
			var parts = o.textContent.split( ' — ' );
			var main = document.createElement( 'span' );
			main.textContent = parts[0];
			li.appendChild( main );
			if ( parts[1] ) {
				var small = document.createElement( 'small' );
				small.textContent = parts[1];
				li.appendChild( small );
			}
			li._key = norm( o.textContent + ' ' + ( group ? group.textContent : '' ) );
			li._group = group;
			li.addEventListener( 'click', function () { choose( o.value ); } );
			li.addEventListener( 'mousemove', function () { activate( li ); } );
			list.appendChild( li );
			items.push( li );
			return li;
		}

		var active = null;

		function shown() {
			var out = items.filter( function ( li ) { return ! li.hidden; } );
			if ( other ) { out.push( other ); }
			return out;
		}

		function activate( el, scroll ) {
			if ( active ) { active.classList.remove( 'is-active' ); }
			active = el;
			if ( ! el ) { search.removeAttribute( 'aria-activedescendant' ); return; }
			el.classList.add( 'is-active' );
			search.setAttribute( 'aria-activedescendant', el.id );
			if ( scroll && el.scrollIntoView ) { el.scrollIntoView( { block: 'nearest' } ); }
		}

		function sync() {
			var opt = select.options[ select.selectedIndex ];
			var has = opt && '' !== opt.value;
			value.textContent = opt ? opt.textContent : '';
			value.classList.toggle( 'is-empty', ! has );
			items.forEach( function ( li ) { li.setAttribute( 'aria-selected', li.getAttribute( 'data-value' ) === select.value ? 'true' : 'false' ); } );
		}

		function filter() {
			var q = norm( search.value );
			var count = 0;
			items.forEach( function ( li ) {
				li.hidden = '' !== q && -1 === li._key.indexOf( q );
				if ( ! li.hidden ) { count++; }
			} );
			list.querySelectorAll( '.sm-picker__group' ).forEach( function ( g ) {
				g.hidden = ! ( g._members || [] ).some( function ( li ) { return ! li.hidden; } );
			} );
			empty.hidden = count > 0;
			activate( shown()[0] || null );
		}

		function open() {
			if ( ! pop.hidden ) { return; }
			pop.hidden = false;
			button.setAttribute( 'aria-expanded', 'true' );
			search.value = '';
			filter();
			var cur = items.filter( function ( li ) { return li.getAttribute( 'data-value' ) === select.value; } )[0];
			if ( cur ) {
				activate( cur );
				list.scrollTop = Math.max( 0, cur.offsetTop - list.clientHeight / 2 );
			}
			search.focus( { preventScroll: true } );
			document.addEventListener( 'mousedown', outside, true );
		}

		function close( focus ) {
			if ( pop.hidden ) { return; }
			pop.hidden = true;
			button.setAttribute( 'aria-expanded', 'false' );
			document.removeEventListener( 'mousedown', outside, true );
			if ( focus ) { button.focus(); }
		}

		function outside( e ) {
			if ( ! wrap.contains( e.target ) ) { close( false ); }
		}

		function choose( v ) {
			close( true );
			if ( select.value === v ) { return; }
			select.value = v;
			sync();
			select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}

		button.addEventListener( 'click', function () { if ( pop.hidden ) { open(); } else { close( true ); } } );
		button.addEventListener( 'keydown', function ( e ) {
			if ( 'ArrowDown' === e.key || 'ArrowUp' === e.key ) { e.preventDefault(); open(); }
		} );
		// The publication form refreshes on every input: the search is not a field of the form.
		search.addEventListener( 'input', function ( e ) { e.stopPropagation(); filter(); } );
		search.addEventListener( 'change', function ( e ) { e.stopPropagation(); } );
		search.addEventListener( 'keydown', function ( e ) {
			var list2 = shown();
			var i = list2.indexOf( active );
			if ( 'ArrowDown' === e.key ) { e.preventDefault(); activate( list2[ Math.min( list2.length - 1, i + 1 ) ] || null, true ); }
			else if ( 'ArrowUp' === e.key ) { e.preventDefault(); activate( list2[ Math.max( 0, i - 1 ) ] || null, true ); }
			else if ( 'Enter' === e.key ) { e.preventDefault(); e.stopPropagation(); if ( active ) { choose( active.getAttribute( 'data-value' ) ); } }
			else if ( 'Escape' === e.key ) { e.preventDefault(); close( true ); }
			else if ( 'Tab' === e.key ) { close( false ); }
		} );
		wrap.appendChild( pop );
		select.addEventListener( 'change', sync );
		sync();
	}

	function scan( root ) {
		( root || document ).querySelectorAll( 'select[data-sm-picker]:not([data-sm-picker-ready])' ).forEach( enhance );
	}

	window.smSizePicker = { scan: scan };
	scan();
	// Parts of the publication form are replaced after each change: enhance the new lists too.
	if ( 'MutationObserver' in window ) {
		new MutationObserver( function ( records ) {
			for ( var i = 0; i < records.length; i++ ) {
				if ( records[ i ].addedNodes.length ) { scan(); return; }
			}
		} ).observe( document.body, { childList: true, subtree: true } );
	}
}() );
