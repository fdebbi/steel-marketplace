/**
 * Indicative sketch of a study request (0.39.0): front view (gable) and plan
 * view of a portal-frame building, or the front of a building with floors,
 * drawn from the client's dimensions. Live in the request form
 * ([data-smx-form] + [data-smx-sketch]); static on the study page
 * ([data-smx-draw] holding the saved answers as JSON).
 */
( function () {
	'use strict';

	var nf = new Intl.NumberFormat( 'fr-FR', { maximumFractionDigits: 2 } ); // Western digits, French style, in every language (like sm_number()).

	function num( v ) {
		if ( v === undefined || v === null ) { return 0; }
		var n = parseFloat( String( v ).replace( /[\s  ]/g, '' ).replace( ',', '.' ) );
		return isFinite( n ) && n > 0 ? n : 0;
	}
	function f( n ) { return nf.format( Math.round( n * 100 ) / 100 ); }
	/* Isolated left-to-right (LRI … PDI): "20 m" keeps its order inside Arabic labels. */
	function m( n ) { return '\u2066' + f( n ) + '\u00a0m\u2069'; }
	function esc( s ) { return String( s ).replace( /[&<>"]/g, function ( c ) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ c ]; } ); }
	/* "%s", "%d", "%1$s"… → values, in order. */
	function tr( s, vals ) {
		var i = 0;
		return String( s || '' ).replace( /%(?:(\d)\$)?[sd]/g, function ( all, pos ) {
			var v = pos ? vals[ pos - 1 ] : vals[ i++ ];
			return v === undefined ? '' : v;
		} ).replace( /%%/g, '%' );
	}
	function text( x, y, s, cls, extra ) {
		return '<text x="' + x.toFixed( 1 ) + '" y="' + y.toFixed( 1 ) + '" class="' + cls + '"' + ( extra || '' ) + '>' + esc( s ) + '</text>';
	}
	/* A dimension line with two arrows (horizontal or vertical). */
	function dimH( x1, x2, y ) {
		return '<g class="smx-d-dim"><line x1="' + x1 + '" y1="' + y + '" x2="' + x2 + '" y2="' + y + '" stroke-width="1.2"/><path d="M' + x1 + ' ' + y + 'l7-3.5v7zM' + x2 + ' ' + y + 'l-7-3.5v7z" stroke="none"/></g>';
	}
	function dimV( x, y1, y2 ) {
		return '<g class="smx-d-dim"><line x1="' + x + '" y1="' + y1 + '" x2="' + x + '" y2="' + y2 + '" stroke-width="1.2"/><path d="M' + x + ' ' + y1 + 'l-3.5 7h7zM' + x + ' ' + y2 + 'l-3.5-7h7z" stroke="none"/></g>';
	}
	function svg( w, h, body ) {
		return '<svg viewBox="0 0 ' + w + ' ' + h + '" dir="ltr" role="img" xmlns="http://www.w3.org/2000/svg">' + body + '</svg>';
	}

	/* Height of the ridge used for the drawing (a gentle default slope when it is not given). */
	function ridge( b ) {
		var h = num( b.col_h ), r = num( b.ridge_h ), roof = b.roof || 'duo', n = Math.max( 1, Math.round( num( b.spans ) ) || 1 );
		var wn = num( b.width ) / n;
		if ( roof === 'flat' ) { return h; }
		if ( r > h ) { return r; }
		if ( roof === 'mono' ) { return h + num( b.width ) * 0.08; }
		return h + wn * ( roof === 'curved' || roof === 'shed' ? 0.15 : 0.08 );
	}

	function frontPortal( b, T ) {
		var w = num( b.width ), h = num( b.col_h );
		if ( ! w || ! h ) { return ''; }
		var roof = b.roof || 'duo', n = Math.max( 1, Math.min( 20, Math.round( num( b.spans ) ) || 1 ) );
		if ( roof === 'multi' && n < 2 ) { n = 2; }
		var r = ridge( b ), wn = w / n;
		var s = Math.min( 400 / w, 190 / r ), gy = 222, x0 = 60 + ( 400 - w * s ) / 2;
		var X = function ( x ) { return ( x0 + x * s ).toFixed( 1 ); };
		var Y = function ( y ) { return ( gy - y * s ).toFixed( 1 ); };
		var d = 'M' + X( 0 ) + ' ' + Y( 0 ) + 'L' + X( 0 ) + ' ' + Y( h );
		if ( roof === 'mono' ) {
			d += 'L' + X( w ) + ' ' + Y( r );
		} else {
			for ( var i = 0; i < n; i++ ) {
				var xa = i * wn, xb = ( i + 1 ) * wn;
				if ( roof === 'flat' ) { d += 'L' + X( xb ) + ' ' + Y( h ); }
				else if ( roof === 'curved' ) { d += 'Q' + X( xa + wn / 2 ) + ' ' + Y( r + ( r - h ) ) + ' ' + X( xb ) + ' ' + Y( h ); }
				else if ( roof === 'shed' ) { d += 'L' + X( xa ) + ' ' + Y( r ) + 'L' + X( xb ) + ' ' + Y( h ); }
				else { d += 'L' + X( xa + wn / 2 ) + ' ' + Y( r ) + 'L' + X( xb ) + ' ' + Y( h ); }
			}
		}
		d += 'L' + X( w ) + ' ' + Y( 0 );
		var out = '<line x1="20" y1="' + gy + '" x2="500" y2="' + gy + '" class="smx-d-dim" stroke-width="1.5"/>';
		out += '<path d="' + d + 'Z" class="smx-d-fill"/><path d="' + d + '" class="smx-d-frame" stroke-width="3" stroke-linejoin="round"/>';
		for ( var k = 1; k < n; k++ ) {
			out += '<line x1="' + X( k * wn ) + '" y1="' + Y( 0 ) + '" x2="' + X( k * wn ) + '" y2="' + Y( h ) + '" class="smx-d-frame" stroke-width="2"/>';
		}
		if ( +b.mezz ) {
			var mw = Math.min( num( b.mezz_w ) || w / 3, w ), mh = num( b.mezz_h ) || Math.min( 3.5, h * 0.5 );
			if ( mh < h ) {
				out += '<g class="smx-d-mezz"><rect x="' + X( 0 ) + '" y="' + ( +Y( mh ) - 3 ) + '" width="' + ( mw * s ).toFixed( 1 ) + '" height="6" stroke="none"/>';
				out += '<line x1="' + X( mw ) + '" y1="' + Y( mh ) + '" x2="' + X( mw ) + '" y2="' + Y( 0 ) + '" stroke-width="2.5"/><line x1="' + X( mw / 2 ) + '" y1="' + Y( mh ) + '" x2="' + X( mw / 2 ) + '" y2="' + Y( 0 ) + '" stroke-width="2"/></g>';
				out += text( +X( mw / 2 ), +Y( mh ) - 8, tr( T.mezz, [ m( mh ) ] ), 'smx-d-tb', ' text-anchor="middle"' );
			}
		}
		if ( +b.crane ) {
			var rail = num( b.crane_rail ) || h * 0.78;
			if ( rail < h ) {
				var cx = wn * 0.62;
				out += '<g class="smx-d-crane"><line x1="' + X( 0 ) + '" y1="' + Y( rail ) + '" x2="' + X( wn ) + '" y2="' + Y( rail ) + '" stroke-width="3" stroke-dasharray="10 4"/>';
				out += '<rect x="' + ( +X( cx ) - 11 ) + '" y="' + Y( rail ) + '" width="22" height="9" stroke="none"/><line x1="' + X( cx ) + '" y1="' + ( +Y( rail ) + 9 ) + '" x2="' + X( cx ) + '" y2="' + ( +Y( rail ) + 28 ) + '" stroke-width="2"/><path d="M' + ( +X( cx ) - 6 ) + ' ' + ( +Y( rail ) + 28 ) + 'h12l-6 8z" stroke="none"/></g>';
				var lab = num( b.crane_t ) ? tr( T.crane, [ f( num( b.crane_t ) ) + ' t', m( rail ) ] ) : T.craneOnly;
				out += text( +X( cx ) - 16, +Y( rail ) - 7, lab, 'smx-d-to', ' text-anchor="end"' );
			}
		}
		out += dimH( +X( 0 ), +X( w ), gy + 26 ) + text( ( +X( 0 ) + +X( w ) ) / 2, gy + 21, tr( T.span, [ m( w ) ] ), 'smx-d-t', ' text-anchor="middle"' );
		var lx = +X( 0 ) - 22;
		out += dimV( lx, +Y( h ), gy ) + text( lx - 6, ( +Y( h ) + gy ) / 2, tr( T.columns, [ m( h ) ] ), 'smx-d-t', ' text-anchor="middle" transform="rotate(-90 ' + ( lx - 6 ) + ' ' + ( ( +Y( h ) + gy ) / 2 ).toFixed( 1 ) + ')"' );
		var given = num( b.ridge_h );
		if ( given > h && roof !== 'flat' ) {
			var rx = +X( w ) + 24, ty = roof === 'mono' ? +Y( given ) : +Y( given );
			out += '<line x1="' + ( roof === 'mono' ? X( w ) : X( wn / 2 ) ) + '" y1="' + ty + '" x2="' + rx + '" y2="' + ty + '" class="smx-d-dim" stroke-dasharray="3 3"/>';
			out += dimV( rx, ty, gy ) + text( rx + 14, ( ty + gy ) / 2, tr( T.ridge, [ m( given ) ] ), 'smx-d-t', ' text-anchor="middle" transform="rotate(-90 ' + ( rx + 14 ) + ' ' + ( ( ty + gy ) / 2 ).toFixed( 1 ) + ')"' );
			var slope = roof === 'mono' ? ( given - h ) / w * 100 : ( roof === 'duo' || roof === 'multi' ? ( given - h ) / ( wn / 2 ) * 100 : 0 );
			if ( slope > 0 ) {
				out += text( +X( roof === 'mono' ? w * 0.75 : wn / 2 ), +Y( roof === 'mono' ? ( h + given ) / 2 : given ) + 22, tr( T.slope, [ f( Math.round( slope ) ) ] ), 'smx-d-tm', ' text-anchor="middle"' );
			}
		}
		return svg( 520, 262, out );
	}

	function frontLevels( b, T ) {
		var w = num( b.width ), lv = Math.round( num( b.levels ) );
		if ( ! w || ! lv ) { return ''; }
		lv = Math.min( lv, 40 );
		var fh = num( b.floor_h ) || 3, H = lv * fh;
		var s = Math.min( 300 / w, 180 / H ), gy = 214, x0 = 60 + ( 340 - w * s ) / 2;
		var X = function ( x ) { return ( x0 + x * s ).toFixed( 1 ); };
		var Y = function ( y ) { return ( gy - y * s ).toFixed( 1 ); };
		var out = '<line x1="20" y1="' + gy + '" x2="500" y2="' + gy + '" class="smx-d-dim" stroke-width="1.5"/>';
		out += '<rect x="' + X( 0 ) + '" y="' + Y( H ) + '" width="' + ( w * s ).toFixed( 1 ) + '" height="' + ( H * s ).toFixed( 1 ) + '" class="smx-d-fill"/>';
		for ( var i = 1; i < lv; i++ ) {
			out += '<line x1="' + X( 0 ) + '" y1="' + Y( i * fh ) + '" x2="' + X( w ) + '" y2="' + Y( i * fh ) + '" class="smx-d-frame" stroke-width="2"/>';
		}
		out += '<line x1="' + X( w / 3 ) + '" y1="' + Y( 0 ) + '" x2="' + X( w / 3 ) + '" y2="' + Y( H ) + '" class="smx-d-frame" stroke-width="1.5"/><line x1="' + X( 2 * w / 3 ) + '" y1="' + Y( 0 ) + '" x2="' + X( 2 * w / 3 ) + '" y2="' + Y( H ) + '" class="smx-d-frame" stroke-width="1.5"/>';
		out += '<rect x="' + X( 0 ) + '" y="' + Y( H ) + '" width="' + ( w * s ).toFixed( 1 ) + '" height="' + ( H * s ).toFixed( 1 ) + '" class="smx-d-frame" stroke-width="3"/>';
		if ( fh * s >= 13 ) {
			for ( var k = 0; k < lv; k++ ) {
				out += text( +X( w ) - 8, +Y( k * fh + fh / 2 ) + 4, k ? tr( T.level, [ k ] ) : T.ground, 'smx-d-tm', ' text-anchor="end"' );
			}
		}
		out += dimH( +X( 0 ), +X( w ), gy + 26 ) + text( ( +X( 0 ) + +X( w ) ) / 2, gy + 21, tr( T.width, [ m( w ) ] ), 'smx-d-t', ' text-anchor="middle"' );
		var rx = +X( w ) + 26;
		out += dimV( rx, +Y( H ), gy ) + text( rx + 14, ( +Y( H ) + gy ) / 2, tr( T.height, [ m( H ) ] ), 'smx-d-t', ' text-anchor="middle" transform="rotate(-90 ' + ( rx + 14 ) + ' ' + ( ( +Y( H ) + gy ) / 2 ).toFixed( 1 ) + ')"' );
		if ( num( b.floor_h ) ) {
			var lx = +X( 0 ) - 20;
			out += dimV( lx, +Y( fh ), gy ) + text( lx - 6, ( +Y( fh ) + gy ) / 2, m( fh ), 'smx-d-tm', ' text-anchor="middle" transform="rotate(-90 ' + ( lx - 6 ) + ' ' + ( ( +Y( fh ) + gy ) / 2 ).toFixed( 1 ) + ')"' );
		}
		return svg( 520, 254, out );
	}

	function plan( b, T ) {
		var w = num( b.width ), l = num( b.length );
		if ( ! w || ! l ) { return { svg: '', note: '' }; }
		var portal = b.family === 'portal';
		var s = Math.min( 420 / l, 130 / w ), x0 = 50 + ( 420 - l * s ) / 2, y0 = 18;
		var X = function ( x ) { return ( x0 + x * s ).toFixed( 1 ); };
		var Y = function ( y ) { return ( y0 + y * s ).toFixed( 1 ); };
		var out = '<rect x="' + X( 0 ) + '" y="' + Y( 0 ) + '" width="' + ( l * s ).toFixed( 1 ) + '" height="' + ( w * s ).toFixed( 1 ) + '" class="smx-d-fill"/>';
		var note = '';
		if ( portal ) {
			var bay = num( b.bay ), n = Math.max( 1, Math.round( num( b.spans ) ) || 1 );
			if ( b.roof === 'multi' && n < 2 ) { n = 2; }
			if ( bay ) {
				var bays = Math.max( 1, Math.ceil( l / bay - 0.01 ) );
				for ( var i = 1; i < bays; i++ ) {
					var x = Math.min( i * bay, l );
					out += '<line x1="' + X( x ) + '" y1="' + Y( 0 ) + '" x2="' + X( x ) + '" y2="' + Y( w ) + '" class="smx-d-frame" stroke-width="1.5"/>';
				}
				note = tr( T.frames, [ bays + 1, bays, m( bay ) ] );
				if ( bay * s > 20 ) { out += text( ( +X( 0 ) + +X( bay ) ) / 2, +Y( w ) - 8, m( bay ), 'smx-d-tm', ' text-anchor="middle"' ); }
			} else {
				note = T.noBay;
			}
			for ( var k = 1; k < n; k++ ) {
				out += '<line x1="' + X( 0 ) + '" y1="' + Y( k * w / n ) + '" x2="' + X( l ) + '" y2="' + Y( k * w / n ) + '" class="smx-d-frame" stroke-width="1.5" stroke-dasharray="6 4"/>';
			}
			if ( +b.mezz && num( b.mezz_w ) && num( b.mezz_l ) ) {
				var mw = Math.min( num( b.mezz_w ), w ), ml = Math.min( num( b.mezz_l ), l );
				out += '<rect x="' + X( 0 ) + '" y="' + Y( 0 ) + '" width="' + ( ml * s ).toFixed( 1 ) + '" height="' + ( mw * s ).toFixed( 1 ) + '" class="smx-d-mezz" fill-opacity=".14" stroke-dasharray="5 3"/>';
				out += text( ( +X( 0 ) + +X( ml ) ) / 2, ( +Y( 0 ) + +Y( mw ) ) / 2 + 4, tr( T.mezzPlan, [ f( mw ) + ' × ' + f( ml ) + ' m' ] ), 'smx-d-tb', ' text-anchor="middle"' );
			}
			var dn = Math.min( 20, Math.round( num( b.doors_n ) ) );
			if ( dn ) {
				var dw = Math.max( 6, ( num( b.door_w ) || 4 ) * s );
				for ( var j = 0; j < dn; j++ ) {
					var cx = +X( l * ( j + 1 ) / ( dn + 1 ) );
					out += '<rect x="' + ( cx - dw / 2 ).toFixed( 1 ) + '" y="' + ( +Y( w ) - 4 ) + '" width="' + dw.toFixed( 1 ) + '" height="8" class="smx-d-crane" stroke="none"/>';
				}
				var dd = num( b.door_w ) && num( b.door_h ) ? f( num( b.door_w ) ) + ' × ' + f( num( b.door_h ) ) + ' m' : '';
				out += text( +X( l / 2 ), +Y( w ) - 12, dn > 1 ? tr( T.doors, [ dn, dd ] ) : tr( T.door, [ dd ] ), 'smx-d-to', ' text-anchor="middle"' );
			}
		}
		out += '<rect x="' + X( 0 ) + '" y="' + Y( 0 ) + '" width="' + ( l * s ).toFixed( 1 ) + '" height="' + ( w * s ).toFixed( 1 ) + '" class="smx-d-frame" stroke-width="2.5"/>';
		[ [ 0, 0 ], [ l, 0 ], [ 0, w ], [ l, w ] ].forEach( function ( p ) {
			out += '<rect x="' + ( +X( p[ 0 ] ) - 3 ) + '" y="' + ( +Y( p[ 1 ] ) - 3 ) + '" width="6" height="6" class="smx-d-frame" fill="currentColor" stroke="none" style="fill:var(--sm-text,#111820)"/>';
		} );
		var by = +Y( w ) + 22;
		out += dimH( +X( 0 ), +X( l ), by ) + text( +X( l / 2 ), by - 5, tr( T.length, [ m( l ) ] ), 'smx-d-t', ' text-anchor="middle"' );
		var lx = +X( 0 ) - 20;
		out += dimV( lx, +Y( 0 ), +Y( w ) ) + text( lx - 6, ( +Y( 0 ) + +Y( w ) ) / 2, tr( T.width, [ m( w ) ] ), 'smx-d-t', ' text-anchor="middle" transform="rotate(-90 ' + ( lx - 6 ) + ' ' + ( ( +Y( 0 ) + +Y( w ) ) / 2 ).toFixed( 1 ) + ')"' );
		return { svg: svg( 520, by + 14, out ), note: note };
	}

	function figures( b, T ) {
		var w = num( b.width ), l = num( b.length );
		if ( ! w || ! l ) { return ''; }
		var tiles = [ [ T.area, f( Math.round( w * l ) ) + ' m²' ] ];
		if ( b.family === 'portal' ) {
			var h = num( b.col_h ), r = num( b.ridge_h ), roof = b.roof || 'duo';
			if ( h ) {
				var rise = roof === 'flat' || r <= h ? 0 : r - h;
				var vol = w * l * h + w * l * rise * ( roof === 'curved' ? 2 / 3 : 0.5 );
				tiles.push( [ T.volume, '≈ ' + f( Math.round( vol ) ) + ' m³' ] );
			}
			if ( +b.mezz && num( b.mezz_w ) && num( b.mezz_l ) ) {
				tiles.push( [ T.mezzArea, f( Math.round( num( b.mezz_w ) * num( b.mezz_l ) ) ) + ' m²' ] );
			}
			if ( num( b.bay ) ) {
				tiles.push( [ T.nFrames, String( Math.max( 1, Math.ceil( l / num( b.bay ) - 0.01 ) ) + 1 ) ] );
			}
		} else if ( b.family === 'levels' && num( b.levels ) ) {
			tiles.push( [ T.floors, f( Math.round( w * l * Math.round( num( b.levels ) ) ) ) + ' m²' ] );
			if ( num( b.floor_h ) ) { tiles.push( [ T.total, m( num( b.floor_h ) * Math.round( num( b.levels ) ) ) ] ); }
		}
		return tiles.map( function ( t ) { return '<div>' + esc( t[ 0 ] ) + '<b><bdi dir="ltr">' + esc( t[ 1 ] ) + '</bdi></b></div>'; } ).join( '' );
	}

	function render( box, b ) {
		var T = {};
		try { T = JSON.parse( box.getAttribute( 'data-i18n' ) || '{}' ); } catch ( e ) {}
		var front = b.family === 'levels' ? frontLevels( b, T ) : frontPortal( b, T );
		var fEl = box.querySelector( '[data-smx-front]' );
		var pEl = box.querySelector( '[data-smx-plan]' );
		var nEl = box.querySelector( '[data-smx-plan-note]' );
		var gEl = box.querySelector( '[data-smx-figs]' );
		var tEl = box.querySelector( '[data-smx-front-title]' );
		if ( tEl ) { tEl.textContent = b.family === 'levels' ? ( T.frontLevels || T.front ) : T.front; }
		if ( fEl ) { fEl.innerHTML = front || '<p class="sm-help">' + esc( T.empty || '' ) + '</p>'; }
		var p = plan( b, T );
		if ( pEl ) { pEl.innerHTML = p.svg; }
		if ( nEl ) { nEl.textContent = p.note; }
		var pb = box.querySelector( '[data-smx-plan-box]' );
		if ( pb ) { pb.hidden = ! p.svg; }
		if ( gEl ) { gEl.innerHTML = figures( b, T ); }
	}

	function read( form ) {
		var b = {};
		var t = form.querySelector( '[name="project_type"]:checked' );
		b.family = t ? t.getAttribute( 'data-fam' ) : '';
		form.querySelectorAll( '[data-smx-in]' ).forEach( function ( el ) {
			var k = el.getAttribute( 'data-smx-in' );
			if ( el.type === 'radio' ) { if ( el.checked ) { b[ k ] = el.value; } }
			else if ( el.type === 'checkbox' ) { if ( k === 'crane' || k === 'mezz' ) { b[ k ] = el.checked ? 1 : 0; } }
			else { b[ k ] = el.value; }
		} );
		return b;
	}

	document.querySelectorAll( 'form.smx' ).forEach( function ( form ) {
		var box = form.querySelector( '[data-smx-sketch]' );
		if ( ! box ) { return; }
		var timer = 0;
		var go = function () { clearTimeout( timer ); timer = setTimeout( function () { render( box, read( form ) ); }, 60 ); };
		form.addEventListener( 'input', go );
		form.addEventListener( 'change', go );
		render( box, read( form ) );
	} );

	document.querySelectorAll( '[data-smx-draw]' ).forEach( function ( box ) {
		var b = {};
		try { b = JSON.parse( box.getAttribute( 'data-smx-draw' ) || '{}' ); } catch ( e ) {}
		render( box, b );
	} );
}() );
