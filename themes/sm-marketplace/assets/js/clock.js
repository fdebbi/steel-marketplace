/**
 * Countdowns of the studies (0.42.0): updates every [data-sm-clock] each minute —
 * time left (or passed), colour (green, orange near the end, red once over) and bar.
 * The page is right without JavaScript; this only keeps it current.
 */
( function () {
	'use strict';
	var lang = ( document.documentElement.lang || 'fr' ).slice( 0, 2 );
	var loc = 'ar' === lang ? 'ar-u-nu-latn' : lang;
	var dayFmt = null;
	try { dayFmt = new Intl.NumberFormat( loc, { style: 'unit', unit: 'day', unitDisplay: 'long' } ); } catch ( e ) {}

	function fmt( t, n ) { return String( t ).replace( '%d', n ); }

	function duration( sec, units, short ) {
		sec = Math.abs( sec );
		var d = Math.floor( sec / 86400 ), h = Math.floor( ( sec % 86400 ) / 3600 ), m = Math.floor( ( sec % 3600 ) / 60 );
		if ( d >= 1 ) {
			if ( short ) { return fmt( units.d, d ); }
			return ( dayFmt ? dayFmt.format( d ) : fmt( units.d, d ) ) + ( h ? ' ' + fmt( units.h, h ) : '' );
		}
		if ( h >= 1 ) { return fmt( units.h, h ) + ( m ? ' ' + fmt( units.min, m ) : '' ); }
		return fmt( units.min, Math.max( 1, m ) );
	}

	function update( el ) {
		var c;
		try { c = JSON.parse( el.getAttribute( 'data-sm-clock' ) ); } catch ( e ) { return; }
		var now = Date.now() / 1000, start = Date.parse( c.start ) / 1000, due = Date.parse( c.due ) / 1000;
		if ( ! due ) { return; }
		var state = now >= due ? 'late' : ( now >= due - c.warn ? 'warn' : 'ok' );
		var left = duration( due - now, c.units, false ), short = duration( due - now, c.units, true );
		var fill = function ( t ) { return String( t ).replace( '{d}', left ).replace( '{s}', short ); };
		el.classList.remove( 'is-ok', 'is-warn', 'is-late' );
		el.classList.add( 'is-' + state );
		el.setAttribute( 'data-state', state );
		var w = c.words[ state ] || [ '', '' ];
		var pill = el.querySelector( '[data-sm-clock-pill]' );
		if ( pill ) {
			pill.textContent = fill( c.pill[ state ] );
			el.title = fill( w[0] );
		}
		var title = el.querySelector( '[data-sm-clock-title]' ), note = el.querySelector( '[data-sm-clock-note]' ), bar = el.querySelector( '[data-sm-clock-bar]' );
		if ( title ) { title.textContent = fill( w[0] ); }
		if ( note ) { note.textContent = fill( w[1] ); }
		if ( bar && due > start ) { bar.style.inlineSize = Math.max( 0, Math.min( 100, Math.round( 100 * ( now - start ) / ( due - start ) ) ) ) + '%'; }
		var use = el.querySelector( '.sm-clock__ic use' );
		if ( use && 'ok' !== state ) { use.setAttribute( 'href', '#i-alert' ); }
	}

	function all() { document.querySelectorAll( '[data-sm-clock]' ).forEach( update ); }

	if ( document.querySelector( '[data-sm-clock]' ) ) {
		all();
		setInterval( all, 60000 );
		document.addEventListener( 'visibilitychange', function () { if ( ! document.hidden ) { all(); } } );
	}
}() );
