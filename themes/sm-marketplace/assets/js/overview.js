/**
 * SM Marketplace — account overview (0.35.0).
 *
 * Draws the curves of sales and purchases from the JSON written by the page
 * (sm-trade Stats), animates them and the key figures, shows the details of a
 * period under the pointer, and switches period and measure without reloading.
 * With "reduce motion", nothing moves: the final state is drawn at once.
 */
( function () {
	'use strict';

	var root = document.querySelector( '[data-smo]' );
	if ( ! root ) { return; }
	var dataEl = root.querySelector( '[data-smo-data]' );
	var data = null;
	try { data = dataEl ? JSON.parse( dataEl.textContent ) : null; } catch ( e ) { data = null; }

	var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var locale = data && data.lang === 'ar' ? 'ar-DZ' : 'fr-FR';
	var cur = data ? data.currency : 'DA';
	var NS = 'http://www.w3.org/2000/svg';
	var state = { period: root.getAttribute( 'data-period' ) || '12m', measure: 'amount' };

	function nf( v, d ) {
		return new Intl.NumberFormat( locale, { maximumFractionDigits: d || 0, minimumFractionDigits: d || 0, numberingSystem: 'latn' } ).format( v );
	}
	function money( v ) { return v >= 1e6 ? nf( v / 1e6, 1 ) + ' M' : nf( Math.round( v ) ); }
	function weight( kg ) { return kg >= 1000 ? [ nf( kg / 1000, 1 ), 't' ] : [ nf( Math.round( kg ) ), 'kg' ]; }
	function ease( t ) { return 1 - Math.pow( 1 - t, 3 ); }

	/* ---- Count-up of a number ------------------------------------------- */
	function countTo( el, to, fmt ) {
		if ( reduce ) { el.textContent = fmt( to ); return; }
		var from = parseFloat( el.getAttribute( 'data-now' ) || '0' );
		var t0 = null;
		function step( t ) {
			if ( ! t0 ) { t0 = t; }
			var k = Math.min( 1, ( t - t0 ) / 900 );
			el.textContent = fmt( from + ( to - from ) * ease( k ) );
			if ( k < 1 ) { requestAnimationFrame( step ); }
		}
		requestAnimationFrame( step );
		el.setAttribute( 'data-now', String( to ) );
	}

	/* ---- Key figures for the chosen period ------------------------------ */
	function plural( el, n ) {
		var tpl = n > 1 ? el.getAttribute( 'data-many' ) : el.getAttribute( 'data-one' );
		el.textContent = ( tpl || '%s' ).replace( '%s', nf( n ) );
	}
	function delta( holder, now, before ) {
		var old = holder.querySelector( '[data-smo-delta]' );
		if ( old ) { old.remove(); }
		if ( ! before || before <= 0 ) { return; }
		var pct = Math.round( ( now - before ) / before * 100 );
		var s = document.createElement( 'span' );
		s.className = 'smo-delta' + ( pct < 0 ? ' is-down' : '' );
		s.setAttribute( 'data-smo-delta', '' );
		s.textContent = ( pct > 0 ? '+' : ( pct < 0 ? '−' : '' ) ) + nf( Math.abs( pct ) ) + ' %';
		holder.insertBefore( s, holder.firstChild );
	}
	function figures() {
		if ( ! data ) { return; }
		var p = data.periods[ state.period ];
		var t = p.totals;
		[ 'sold', 'bought' ].forEach( function ( side ) {
			var v = root.querySelector( '[data-smo-v="' + side + '"]' );
			if ( v ) { countTo( v, t[ side ], money ); }
			var n = root.querySelector( '[data-smo-n="' + side + '"]' );
			if ( n ) {
				plural( n, t[ side + '_n' ] );
				delta( n.parentNode, t[ side ], p.prev ? p.prev[ side ] : 0 );
			}
			spark( side, p.buckets.map( function ( b ) { return b[ side ]; } ) );
		} );
		var kgEl = root.querySelector( '[data-smo-v="kg"]' );
		if ( kgEl ) {
			var total = t.sold_kg + t.bought_kg;
			var unit = root.querySelector( '[data-smo-unit]' );
			if ( unit ) { unit.textContent = weight( total )[1]; }
			countTo( kgEl, total, function ( v ) { return total >= 1000 ? nf( v / 1000, 1 ) : nf( Math.round( v ) ); } );
			var split = root.querySelector( '[data-smo-kgsplit]' );
			if ( split ) {
				var a = weight( t.sold_kg ), b = weight( t.bought_kg );
				split.textContent = split.getAttribute( 'data-tpl' ).replace( '%1$s', a.join( ' ' ) ).replace( '%2$s', b.join( ' ' ) );
			}
		}
		var nw = root.querySelector( '[data-smo-noweight]' );
		if ( nw ) {
			nw.hidden = ! t.no_weight;
			nw.textContent = nw.getAttribute( 'data-tpl' ).replace( '%s', nf( t.no_weight ) );
		}
	}

	/* ---- Small curves in the key figures -------------------------------- */
	function spark( side, vals ) {
		var svg = root.querySelector( '[data-smo-spark="' + side + '"]' );
		if ( ! svg ) { return; }
		var max = Math.max.apply( null, vals.concat( [ 0 ] ) );
		svg.innerHTML = '';
		if ( max <= 0 ) { return; }
		var pts = vals.map( function ( v, i ) { return ( i * 120 / Math.max( 1, vals.length - 1 ) ).toFixed( 1 ) + ',' + ( 33 - v / max * 29 ).toFixed( 1 ); } ).join( ' ' );
		var pl = document.createElementNS( NS, 'polyline' );
		pl.setAttribute( 'points', pts );
		svg.appendChild( pl );
		draw( pl, 700 );
	}

	/* ---- Line drawing animation ----------------------------------------- */
	function draw( path, ms ) {
		if ( reduce || ! path.getTotalLength ) { return; }
		var len = path.getTotalLength();
		path.style.strokeDasharray = len;
		path.style.strokeDashoffset = len;
		path.getBoundingClientRect();
		path.style.transition = 'stroke-dashoffset ' + ms + 'ms cubic-bezier(.22,.61,.36,1)';
		path.style.strokeDashoffset = '0';
		// The dash was measured in drawing units; once drawn, the plain line takes over.
		setTimeout( function () { path.style.transition = ''; path.style.strokeDasharray = ''; path.style.strokeDashoffset = ''; }, ms + 60 );
	}

	/* ---- Main chart ------------------------------------------------------ */
	var chart = root.querySelector( '[data-smo-chart]' );
	var svg = chart ? chart.querySelector( 'svg' ) : null;
	var tip = chart ? chart.querySelector( '[data-smo-tip]' ) : null;
	var W = 820, H = 260, PL = 52, PR = 14, PT = 14, PB = 32;
	var still = false; // Redraw without animation (window resized).
	var geo = null;

	function el( name, attrs, parent ) {
		var n = document.createElementNS( NS, name );
		Object.keys( attrs || {} ).forEach( function ( k ) { n.setAttribute( k, attrs[ k ] ); } );
		if ( parent ) { parent.appendChild( n ); }
		return n;
	}
	function value( b, side ) {
		if ( state.measure === 'kg' ) { return b[ side + '_kg' ] / 1000; }
		if ( state.measure === 'n' ) { return b[ side + '_n' ]; }
		return b[ side ];
	}
	// Top of the scale: four round steps (0, 1, 2, 3, 4 M rather than 1,25 M…).
	function niceMax( m ) {
		if ( m <= 0 ) { return 4; }
		var raw = m / 4;
		var p = Math.pow( 10, Math.floor( Math.log10( raw ) ) );
		var steps = [ 1, 2, 2.5, 5, 10 ];
		for ( var i = 0; i < steps.length; i++ ) { if ( steps[ i ] * p >= raw ) { return steps[ i ] * p * 4; } }
		return 40 * p;
	}
	function axisLabel( v ) {
		if ( state.measure === 'amount' ) { return v >= 1e6 ? nf( v / 1e6, v % 1e6 ? 1 : 0 ) + ' M' : ( v >= 1000 ? nf( v / 1000 ) + ' k' : nf( v ) ); }
		if ( state.measure === 'kg' ) { return nf( v, v % 1 ? 1 : 0 ) + ' t'; }
		return nf( v );
	}
	function smooth( pts ) {
		var d = 'M' + pts[0][0].toFixed( 1 ) + ' ' + pts[0][1].toFixed( 1 );
		for ( var i = 1; i < pts.length; i++ ) {
			var x0 = pts[ i - 1 ][0], y0 = pts[ i - 1 ][1], x1 = pts[ i ][0], y1 = pts[ i ][1], cx = ( x0 + x1 ) / 2;
			d += ' C' + cx.toFixed( 1 ) + ' ' + y0.toFixed( 1 ) + ' ' + cx.toFixed( 1 ) + ' ' + y1.toFixed( 1 ) + ' ' + x1.toFixed( 1 ) + ' ' + y1.toFixed( 1 );
		}
		return d;
	}
	function render() {
		if ( ! svg || ! data ) { return; }
		// Draw at the real size of the box, so the text is never stretched.
		var r = svg.getBoundingClientRect();
		if ( r.width > 0 ) { W = Math.round( r.width ); H = Math.round( r.height ); }
		PL = W < 480 ? 40 : 52;
		svg.setAttribute( 'viewBox', '0 0 ' + W + ' ' + H );
		var b = data.periods[ state.period ].buckets;
		var n = b.length;
		var sold = b.map( function ( x ) { return value( x, 'sold' ); } );
		var bought = b.map( function ( x ) { return value( x, 'bought' ); } );
		var max = niceMax( Math.max.apply( null, sold.concat( bought ) ) );
		var empty = sold.concat( bought ).every( function ( v ) { return ! v; } );
		var x = function ( i ) { return PL + ( n > 1 ? i * ( W - PL - PR ) / ( n - 1 ) : ( W - PL - PR ) / 2 ); };
		var y = function ( v ) { return PT + ( 1 - v / max ) * ( H - PT - PB ); };
		svg.innerHTML = '';
		var defs = el( 'defs', {}, svg );
		[ [ 'smo-g1', 'var(--sm-accent)', 0.26 ], [ 'smo-g2', '#3B82F6', 0.16 ] ].forEach( function ( g ) {
			var lg = el( 'linearGradient', { id: g[0], x1: 0, y1: 0, x2: 0, y2: 1 }, defs );
			el( 'stop', { offset: 0, 'stop-color': g[1], 'stop-opacity': g[2] }, lg );
			el( 'stop', { offset: 1, 'stop-color': g[1], 'stop-opacity': 0 }, lg );
		} );
		var grid = el( 'g', { 'class': 'smo-grid' }, svg );
		for ( var k = 0; k <= 4; k++ ) {
			var gv = max * k / 4, gy = y( gv );
			el( 'line', { x1: PL, x2: W - PR, y1: gy, y2: gy }, grid );
			var tx = el( 'text', { x: PL - 8, y: Math.max( gy + 4, 11 ), 'text-anchor': 'end' }, grid );
			tx.textContent = axisLabel( gv );
		}
		var every = Math.ceil( n / ( W < 480 ? 6 : 12 ) );
		var xl = el( 'g', { 'class': 'smo-xl' }, svg );
		b.forEach( function ( bk, i ) {
			if ( ( n - 1 - i ) % every ) { return; } // Counted from the last period, so the last label is never crowded.
			var t = el( 'text', { x: x( i ), y: H - 8, 'text-anchor': 'middle' }, xl );
			t.textContent = bk.label;
		} );
		var ps = sold.map( function ( v, i ) { return [ x( i ), y( v ) ]; } );
		var pb = bought.map( function ( v, i ) { return [ x( i ), y( v ) ]; } );
		var base = H - PB;
		var areas = el( 'g', { 'class': 'smo-areas' }, svg );
		[ [ pb, 'smo-g2' ], [ ps, 'smo-g1' ] ].forEach( function ( a ) {
			el( 'path', { d: smooth( a[0] ) + ' L' + a[0][ n - 1 ][0].toFixed( 1 ) + ' ' + base + ' L' + a[0][0][0].toFixed( 1 ) + ' ' + base + ' Z', fill: 'url(#' + a[1] + ')' }, areas );
		} );
		var l2 = el( 'path', { d: smooth( pb ), 'class': 'smo-line smo-line--bought' }, svg );
		var l1 = el( 'path', { d: smooth( ps ), 'class': 'smo-line smo-line--sold' }, svg );
		var dots = el( 'g', { 'class': 'smo-dots' }, svg );
		if ( n <= 31 ) {
			ps.forEach( function ( p ) { el( 'circle', { cx: p[0], cy: p[1], r: n > 14 ? 2.5 : 3.5, 'class': 'is-sold' }, dots ); } );
			pb.forEach( function ( p ) { el( 'circle', { cx: p[0], cy: p[1], r: n > 14 ? 2.5 : 3.5, 'class': 'is-bought' }, dots ); } );
		}
		var cross = el( 'line', { 'class': 'smo-cross', y1: PT, y2: base, x1: -10, x2: -10 }, svg );
		var hs = el( 'circle', { r: 6, 'class': 'smo-hot is-sold', cx: -20, cy: -20 }, svg );
		var hb = el( 'circle', { r: 6, 'class': 'smo-hot is-bought', cx: -20, cy: -20 }, svg );
		geo = { n: n, x: x, ps: ps, pb: pb, cross: cross, hs: hs, hb: hb, b: b };
		if ( ! reduce && ! still ) {
			areas.style.opacity = '0';
			dots.style.opacity = '0';
			draw( l2, 1100 );
			draw( l1, 1100 );
			setTimeout( function () { areas.style.transition = dots.style.transition = 'opacity .5s ease'; areas.style.opacity = '1'; dots.style.opacity = '1'; }, 500 );
		}
		var em = chart.querySelector( '[data-smo-empty]' );
		if ( em ) { em.hidden = ! empty; }
		hide();
	}

	/* ---- Details under the pointer -------------------------------------- */
	function fmtMeasure( bk, side ) {
		if ( state.measure === 'kg' ) { return weight( bk[ side + '_kg' ] ).join( ' ' ); }
		if ( state.measure === 'n' ) { return nf( bk[ side + '_n' ] ); }
		return nf( Math.round( bk[ side ] ) ) + ' ' + cur;
	}
	function show( i ) {
		if ( ! geo ) { return; }
		var bk = geo.b[ i ];
		var cx = geo.x( i );
		geo.cross.setAttribute( 'x1', cx ); geo.cross.setAttribute( 'x2', cx );
		geo.hs.setAttribute( 'cx', geo.ps[ i ][0] ); geo.hs.setAttribute( 'cy', geo.ps[ i ][1] );
		geo.hb.setAttribute( 'cx', geo.pb[ i ][0] ); geo.hb.setAttribute( 'cy', geo.pb[ i ][1] );
		var t = data.i18n;
		tip.innerHTML = '';
		var h = document.createElement( 'b' ); h.textContent = bk.title; tip.appendChild( h );
		[ [ 'sold', t.sold ], [ 'bought', t.bought ] ].forEach( function ( r ) {
			var row = document.createElement( 'div' );
			var i2 = document.createElement( 'i' ); i2.className = 'is-' + r[0];
			var s = document.createElement( 'span' ); s.textContent = r[1];
			var st = document.createElement( 'strong' ); st.textContent = fmtMeasure( bk, r[0] );
			row.appendChild( i2 ); row.appendChild( s ); row.appendChild( st ); tip.appendChild( row );
		} );
		var sm = document.createElement( 'small' );
		sm.textContent = nf( bk.sold_n ) + ' ' + ( bk.sold_n > 1 ? t.sales : t.sale ) + ' · ' + nf( bk.bought_n ) + ' ' + ( bk.bought_n > 1 ? t.purchases : t.purchase ) + ' · ' + weight( bk.sold_kg + bk.bought_kg ).join( ' ' );
		tip.appendChild( sm );
		tip.hidden = false;
		var box = chart.getBoundingClientRect();
		var px = cx / W * box.width;
		var left = px + 14;
		if ( left + tip.offsetWidth > box.width ) { left = px - tip.offsetWidth - 14; }
		tip.style.insetInlineStart = 'auto';
		tip.style.left = Math.max( 0, left ) + 'px';
		tip.style.top = '8px';
	}
	function hide() {
		if ( ! geo ) { return; }
		if ( tip ) { tip.hidden = true; }
		geo.cross.setAttribute( 'x1', -10 ); geo.cross.setAttribute( 'x2', -10 );
		[ geo.hs, geo.hb ].forEach( function ( c ) { c.setAttribute( 'cx', -20 ); c.setAttribute( 'cy', -20 ); } );
	}
	function pointer( e ) {
		if ( ! geo ) { return; }
		var box = svg.getBoundingClientRect();
		var vx = ( e.clientX - box.left ) / box.width * W;
		var best = 0, dist = Infinity;
		for ( var i = 0; i < geo.n; i++ ) {
			var d = Math.abs( geo.x( i ) - vx );
			if ( d < dist ) { dist = d; best = i; }
		}
		show( best );
	}
	if ( svg ) {
		var rt = null, lastW = 0;
		window.addEventListener( 'resize', function () {
			clearTimeout( rt );
			rt = setTimeout( function () {
				var w = Math.round( svg.getBoundingClientRect().width );
				if ( w && w !== lastW ) { lastW = w; still = true; render(); still = false; }
			}, 150 );
		} );
		svg.addEventListener( 'pointermove', pointer );
		svg.addEventListener( 'pointerdown', pointer );
		svg.addEventListener( 'pointerleave', function ( e ) { if ( e.pointerType === 'mouse' ) { hide(); } } );
	}

	/* ---- Switches -------------------------------------------------------- */
	function press( attr, val ) {
		root.querySelectorAll( '[' + attr + ']' ).forEach( function ( b ) { b.setAttribute( 'aria-pressed', String( b.getAttribute( attr ) === val ) ); } );
	}
	root.addEventListener( 'click', function ( e ) {
		var p = e.target.closest ? e.target.closest( '[data-smo-period]' ) : null;
		var m = e.target.closest ? e.target.closest( '[data-smo-measure]' ) : null;
		if ( p ) {
			state.period = p.getAttribute( 'data-smo-period' );
			press( 'data-smo-period', state.period );
			figures();
			render();
		} else if ( m ) {
			state.measure = m.getAttribute( 'data-smo-measure' );
			press( 'data-smo-measure', state.measure );
			render();
		}
	} );

	/* ---- Start when the page is shown ------------------------------------ */
	function start() {
		if ( data ) {
			root.querySelectorAll( '[data-smo-v]' ).forEach( function ( n ) { n.setAttribute( 'data-now', '0' ); } );
			figures();
			render();
		}
		root.querySelectorAll( '[data-smo-count]' ).forEach( function ( n ) {
			var to = parseFloat( n.getAttribute( 'data-smo-count' ) );
			var d = parseInt( n.getAttribute( 'data-dec' ) || '0', 10 );
			countTo( n, to, function ( v ) { return nf( v, d ); } );
		} );
		root.classList.add( 'is-ready' );
	}
	start();
}() );
