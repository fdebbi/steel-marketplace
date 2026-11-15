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

	var uid = 0;
	/* Hatched pattern of a masonry wall. */
	function hatch( id ) {
		return '<defs><pattern id="' + id + '" width="8" height="8" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><rect width="8" height="8" class="smx-d-wallbg"/><line x1="0" y1="0" x2="0" y2="8" class="smx-d-wall" stroke-width="2"/></pattern></defs>';
	}
	/* Number of bays along the length (6 m when the spacing is not given), and the middle of bay k. */
	function bays( b ) {
		var l = num( b.length ), bay = num( b.bay ) || 6;
		return l ? Math.max( 1, Math.ceil( l / bay - 0.01 ) ) : 0;
	}
	function bayMid( b, k ) {
		var l = num( b.length ), bay = num( b.bay ) || 6;
		return ( ( k - 1 ) * bay + Math.min( k * bay, l ) ) / 2;
	}
	function bayWidth( b, k ) {
		var l = num( b.length ), bay = num( b.bay ) || 6;
		return Math.min( k * bay, l ) - ( k - 1 ) * bay;
	}
	function list( v ) {
		return ( Array.isArray( v ) ? v : [] ).map( function ( x ) { return +x; } ).filter( function ( x ) { return x > 0; } );
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
		var walls = b.walls || '', wallH = walls === 'wall' ? h : ( walls === 'mixed' ? Math.min( num( b.wall_h ), h ) : 0 );
		out += '<path d="' + d + 'Z" class="smx-d-fill"' + ( walls === 'open' ? ' fill-opacity=".25"' : '' ) + '/>';
		if ( walls === 'sheet' || walls === 'sandwich' || walls === 'mixed' ) {
			for ( var c = Math.max( wallH, 0 ) + 1.2; c < h; c += 1.2 ) {
				out += '<line x1="' + X( 0 ) + '" y1="' + Y( c ) + '" x2="' + X( w ) + '" y2="' + Y( c ) + '" class="smx-d-clad" stroke-width="1"/>';
			}
		}
		if ( wallH > 0 ) {
			var hid = 'smx-h' + ( ++uid );
			out += hatch( hid ) + '<rect x="' + X( 0 ) + '" y="' + Y( wallH ) + '" width="' + ( w * s ).toFixed( 1 ) + '" height="' + ( wallH * s ).toFixed( 1 ) + '" fill="url(#' + hid + ')"/>';
			if ( walls === 'mixed' ) {
				out += text( +X( w * 0.18 ), +Y( wallH / 2 ) + 4, tr( T.wall, [ m( wallH ) ] ), 'smx-d-tw', ' text-anchor="middle"' );
			}
		}
		/* Doors of the left gable, seen from the front. */
		var gl = b.d_left || '';
		if ( gl === 'one' || gl === 'two' ) {
			var gw = Math.min( num( b.d_left_w ) || 5, w * 0.8 / ( gl === 'two' ? 2 : 1 ) ), gh = Math.min( num( b.d_left_h ) || 5, h * 0.95 );
			( gl === 'one' ? [ w / 2 ] : [ w / 4, 3 * w / 4 ] ).forEach( function ( cx ) {
				out += '<rect x="' + X( cx - gw / 2 ) + '" y="' + Y( gh ) + '" width="' + ( gw * s ).toFixed( 1 ) + '" height="' + ( gh * s ).toFixed( 1 ) + '" class="smx-d-door"/>';
				for ( var p = 1; p < 5; p++ ) {
					out += '<line x1="' + X( cx - gw / 2 ) + '" y1="' + Y( gh * p / 5 ) + '" x2="' + X( cx + gw / 2 ) + '" y2="' + Y( gh * p / 5 ) + '" class="smx-d-crane" stroke-width="1"/>';
				}
			} );
			var dsz = num( b.d_left_w ) && num( b.d_left_h ) ? f( num( b.d_left_w ) ) + ' × ' + f( num( b.d_left_h ) ) + '\u00a0m' : '';
			out += text( +X( w / 2 ), +Y( gh ) - 6, tr( gl === 'one' ? T.door : T.doors, gl === 'one' ? [ dsz ] : [ 2, dsz ] ), 'smx-d-to', ' text-anchor="middle"' );
		}
		out += '<path d="' + d + '" class="smx-d-frame" stroke-width="3" stroke-linejoin="round"/>';
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
		var w = num( b.width );
		var fl = ( Array.isArray( b.floors ) ? b.floors : [] ).filter( function ( r ) { return num( r.h ); } ).slice( 0, 40 );
		if ( ! fl.length && num( b.levels ) ) {
			for ( var q = 0; q < Math.min( 40, Math.round( num( b.levels ) ) ); q++ ) { fl.push( { h: num( b.floor_h ) || 3, use: '' } ); }
		}
		var lv = fl.length;
		if ( ! w || ! lv ) { return ''; }
		var tops = [ 0 ];
		fl.forEach( function ( r ) { tops.push( tops[ tops.length - 1 ] + num( r.h ) ); } );
		var H = tops[ lv ], fh = num( fl[ 0 ].h );
		var s = Math.min( 300 / w, 180 / H ), gy = 214, x0 = 60 + ( 340 - w * s ) / 2;
		var X = function ( x ) { return ( x0 + x * s ).toFixed( 1 ); };
		var Y = function ( y ) { return ( gy - y * s ).toFixed( 1 ); };
		var out = '<line x1="20" y1="' + gy + '" x2="500" y2="' + gy + '" class="smx-d-dim" stroke-width="1.5"/>';
		out += '<rect x="' + X( 0 ) + '" y="' + Y( H ) + '" width="' + ( w * s ).toFixed( 1 ) + '" height="' + ( H * s ).toFixed( 1 ) + '" class="smx-d-fill"/>';
		for ( var i = 1; i < lv; i++ ) {
			out += '<line x1="' + X( 0 ) + '" y1="' + Y( tops[ i ] ) + '" x2="' + X( w ) + '" y2="' + Y( tops[ i ] ) + '" class="smx-d-frame" stroke-width="2"/>';
		}
		out += '<line x1="' + X( w / 3 ) + '" y1="' + Y( 0 ) + '" x2="' + X( w / 3 ) + '" y2="' + Y( H ) + '" class="smx-d-frame" stroke-width="1.5"/><line x1="' + X( 2 * w / 3 ) + '" y1="' + Y( 0 ) + '" x2="' + X( 2 * w / 3 ) + '" y2="' + Y( H ) + '" class="smx-d-frame" stroke-width="1.5"/>';
		out += '<rect x="' + X( 0 ) + '" y="' + Y( H ) + '" width="' + ( w * s ).toFixed( 1 ) + '" height="' + ( H * s ).toFixed( 1 ) + '" class="smx-d-frame" stroke-width="3"/>';
		var uses = T.uses || {};
		for ( var k = 0; k < lv; k++ ) {
			var hk = num( fl[ k ].h );
			if ( hk * s < 12 ) { continue; }
			var nm = ( k ? tr( T.level, [ k ] ) : T.ground ) + ( fl[ k ].use && uses[ fl[ k ].use ] ? ' · ' + uses[ fl[ k ].use ] : '' );
			out += text( ( +X( 0 ) + +X( w ) ) / 2, +Y( tops[ k ] + hk / 2 ) + 4, nm, 'smx-d-t', ' text-anchor="middle"' );
			out += '<g class="smx-d-dim"><line x1="' + ( +X( w ) + 10 ) + '" y1="' + Y( tops[ k + 1 ] ) + '" x2="' + ( +X( w ) + 10 ) + '" y2="' + Y( tops[ k ] ) + '" stroke-width="1"/><path d="M' + ( +X( w ) + 10 ) + ' ' + Y( tops[ k + 1 ] ) + 'l-3 6h6zM' + ( +X( w ) + 10 ) + ' ' + Y( tops[ k ] ) + 'l-3-6h6z" stroke="none"/></g>';
			out += text( +X( w ) + 16, +Y( tops[ k ] + hk / 2 ) + 4, m( hk ), 'smx-d-tm' );
		}
		out += dimH( +X( 0 ), +X( w ), gy + 26 ) + text( ( +X( 0 ) + +X( w ) ) / 2, gy + 21, tr( T.width, [ m( w ) ] ), 'smx-d-t', ' text-anchor="middle"' );
		var rx = +X( w ) + 64;
		out += dimV( rx, +Y( H ), gy ) + text( rx + 14, ( +Y( H ) + gy ) / 2, tr( T.height, [ m( H ) ] ), 'smx-d-t', ' text-anchor="middle" transform="rotate(-90 ' + ( rx + 14 ) + ' ' + ( ( +Y( H ) + gy ) / 2 ).toFixed( 1 ) + ')"' );
		if ( false ) {
			var lx = +X( 0 ) - 20;
			out += dimV( lx, +Y( fh ), gy ) + text( lx - 6, ( +Y( fh ) + gy ) / 2, m( fh ), 'smx-d-tm', ' text-anchor="middle" transform="rotate(-90 ' + ( lx - 6 ) + ' ' + ( ( +Y( fh ) + gy ) / 2 ).toFixed( 1 ) + ')"' );
		}
		return svg( 520, 254, out );
	}

	function plan( b, T ) {
		var w = num( b.width ), l = num( b.length );
		if ( ! w || ! l ) { return { svg: '', note: '' }; }
		var portal = b.family === 'portal';
		var s = Math.min( 420 / l, 130 / w ), x0 = 50 + ( 420 - l * s ) / 2, y0 = 24;
		var X = function ( x ) { return ( x0 + x * s ).toFixed( 1 ); };
		var Y = function ( y ) { return ( y0 + y * s ).toFixed( 1 ); };
		var out = '<rect x="' + X( 0 ) + '" y="' + Y( 0 ) + '" width="' + ( l * s ).toFixed( 1 ) + '" height="' + ( w * s ).toFixed( 1 ) + '" class="smx-d-fill"/>';
		var note = '', hits = '';
		if ( portal ) {
			var bay = num( b.bay ), n = Math.max( 1, Math.round( num( b.spans ) ) || 1 );
			if ( b.roof === 'multi' && n < 2 ) { n = 2; }
			if ( bay ) {
				var nbays = Math.max( 1, Math.ceil( l / bay - 0.01 ) );
				for ( var i = 1; i < nbays; i++ ) {
					var x = Math.min( i * bay, l );
					out += '<line x1="' + X( x ) + '" y1="' + Y( 0 ) + '" x2="' + X( x ) + '" y2="' + Y( w ) + '" class="smx-d-frame" stroke-width="1.5"/>';
				}
				note = tr( T.frames, [ nbays + 1, nbays, m( bay ) ] );
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
			if ( dn && ! b.d_front && ! b.d_back && ! b.d_left && ! b.d_right ) { /* Requests sent before 0.40.0. */
				var dw = Math.max( 6, ( num( b.door_w ) || 4 ) * s );
				for ( var j = 0; j < dn; j++ ) {
					var cx = +X( l * ( j + 1 ) / ( dn + 1 ) );
					out += '<rect x="' + ( cx - dw / 2 ).toFixed( 1 ) + '" y="' + ( +Y( w ) - 4 ) + '" width="' + dw.toFixed( 1 ) + '" height="8" class="smx-d-crane" stroke="none"/>';
				}
			}
			/* 0.40.0: doors in the middle of their bay (long sides) or on the gables. */
			var nb = bays( b ), side, sides = { front: w, back: 0 };
			hits = '';
			for ( side in sides ) {
				var yy = +Y( sides[ side ] );
				if ( b._live ) {
					for ( var t = 1; t <= nb; t++ ) {
						var bx = +X( ( t - 1 ) * ( num( b.bay ) || 6 ) ), bw = bayWidth( b, t ) * s;
						hits += '<rect x="' + bx.toFixed( 1 ) + '" y="' + ( yy - 9 ) + '" width="' + bw.toFixed( 1 ) + '" height="18" class="smx-d-hit" data-smx-hit="' + side + ':' + t + '"><title>' + esc( ( side === 'front' ? T.front_side : T.back_side ) + ' · T' + t ) + '</title></rect>';
					}
				}
				if ( b[ 'd_' + side ] !== 'doors' ) { continue; }
				var on = list( b[ 'd_' + side + '_bays' ] ).filter( function ( k ) { return k <= nb; } );
				var ww = num( b[ 'd_' + side + '_w' ] ) || 4;
				on.forEach( function ( k ) {
					var dwk = Math.max( 5, Math.min( ww, bayWidth( b, k ) * 0.9 ) * s );
					out += '<rect x="' + ( +X( bayMid( b, k ) ) - dwk / 2 ).toFixed( 1 ) + '" y="' + ( yy - 4 ) + '" width="' + dwk.toFixed( 1 ) + '" height="8" class="smx-d-crane" stroke="none" pointer-events="none"/>';
				} );
				if ( on.length ) {
					var sz = num( b[ 'd_' + side + '_w' ] ) && num( b[ 'd_' + side + '_h' ] ) ? f( num( b[ 'd_' + side + '_w' ] ) ) + ' × ' + f( num( b[ 'd_' + side + '_h' ] ) ) + '\u00a0m' : '';
					out += text( +X( l / 2 ), side === 'front' ? yy - 12 : yy + 20, ( side === 'front' ? T.front_side : T.back_side ) + ' : ' + ( on.length > 1 ? tr( T.doors, [ on.length, sz ] ) : tr( T.door, [ sz ] ) ), 'smx-d-to', ' text-anchor="middle" pointer-events="none"' );
				}
			}
			[ [ 'left', 0 ], [ 'right', l ] ].forEach( function ( g ) {
				var mode = b[ 'd_' + g[ 0 ] ];
				if ( mode !== 'one' && mode !== 'two' ) { return; }
				var gw = Math.max( 5, Math.min( num( b[ 'd_' + g[ 0 ] + '_w' ] ) || 5, w * 0.4 ) * s );
				( mode === 'one' ? [ w / 2 ] : [ w / 4, 3 * w / 4 ] ).forEach( function ( cy ) {
					out += '<rect x="' + ( +X( g[ 1 ] ) - 4 ) + '" y="' + ( +Y( cy ) - gw / 2 ).toFixed( 1 ) + '" width="8" height="' + gw.toFixed( 1 ) + '" class="smx-d-crane" stroke="none"/>';
				} );
			} );
			if ( nb && nb * 1 <= 40 && ( l * s ) / nb >= 16 ) {
				for ( var u = 1; u <= nb; u++ ) {
					var act = b.d_front === 'doors' && list( b.d_front_bays ).indexOf( u ) >= 0;
					out += text( +X( bayMid( b, u ) ), +Y( w ) + 16, 'T' + u, act ? 'smx-d-to' : 'smx-d-tm', ' text-anchor="middle" font-size="11"' );
				}
			}
		}
		out += '<rect x="' + X( 0 ) + '" y="' + Y( 0 ) + '" width="' + ( l * s ).toFixed( 1 ) + '" height="' + ( w * s ).toFixed( 1 ) + '" class="smx-d-frame" stroke-width="2.5"/>';
		[ [ 0, 0 ], [ l, 0 ], [ 0, w ], [ l, w ] ].forEach( function ( p ) {
			out += '<rect x="' + ( +X( p[ 0 ] ) - 3 ) + '" y="' + ( +Y( p[ 1 ] ) - 3 ) + '" width="6" height="6" class="smx-d-frame" fill="currentColor" stroke="none" style="fill:var(--sm-text,#111820)"/>';
		} );
		out += hits; /* Clickable bays on top of everything (live form only). */
		var by = +Y( w ) + ( portal ? 38 : 22 );
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
		} else if ( b.family === 'levels' ) {
			var fl = ( Array.isArray( b.floors ) ? b.floors : [] ).filter( function ( r ) { return num( r.h ); } );
			var nl = fl.length || Math.round( num( b.levels ) );
			var ht = fl.length ? fl.reduce( function ( a, r ) { return a + num( r.h ); }, 0 ) : num( b.floor_h ) * nl;
			if ( nl ) { tiles.push( [ T.floors, f( Math.round( w * l * nl ) ) + '\u00a0m²' ] ); }
			if ( ht ) { tiles.push( [ T.total, m( ht ) ] ); }
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
			else if ( el.type === 'checkbox' ) {
				if ( k === 'crane' || k === 'mezz' ) { b[ k ] = el.checked ? 1 : 0; }
				else if ( /_bays$/.test( k ) ) { b[ k ] = b[ k ] || []; if ( el.checked ) { b[ k ].push( +el.value ); } }
			}
			else if ( k !== 'floor_h' && k !== 'floor_use' ) { b[ k ] = el.value; }
		} );
		b.floors = [];
		form.querySelectorAll( '[data-smx-floor]' ).forEach( function ( row ) {
			var hi = row.querySelector( '[data-smx-in="floor_h"]' ), us = row.querySelector( '[data-smx-in="floor_use"]' );
			b.floors.push( { h: hi ? hi.value : '', use: us ? us.value : '' } );
		} );
		b._live = 1;
		return b;
	}

	/* Communes of the chosen wilaya (a text field when the wilaya has no list). */
	function communes( form ) {
		var wrap = form.querySelector( '[data-smx-commune]' ), wil = form.querySelector( '#m-wilaya' );
		if ( ! wrap || ! wil ) { return; }
		var map = {};
		try { map = JSON.parse( wrap.getAttribute( 'data-communes' ) || '{}' ); } catch ( e ) {}
		var sel = wrap.querySelector( '[data-smx-commune-list]' ), txt = wrap.querySelector( '[data-smx-commune-text]' );
		var first = sel.options[ 0 ] ? sel.options[ 0 ].textContent : '';
		wil.addEventListener( 'change', function () {
			var list = map[ wil.value ] || [];
			var has = list.length > 0;
			sel.hidden = sel.disabled = ! has;
			txt.hidden = txt.disabled = has || ! wil.value;
			if ( ! wil.value ) { sel.hidden = false; sel.disabled = true; }
			sel.innerHTML = '';
			var o = document.createElement( 'option' );
			o.value = ''; o.textContent = first; sel.appendChild( o );
			list.forEach( function ( c ) {
				var op = document.createElement( 'option' );
				op.value = c[ 0 ]; op.textContent = c[ 1 ]; sel.appendChild( op );
			} );
			txt.value = '';
		} );
	}

	/* Levels: one row each, "+ Add a level", × to remove; names GF, F+1… */
	function floors( form ) {
		var box = form.querySelector( '[data-smx-floors]' ), add = form.querySelector( '[data-smx-floor-add]' );
		if ( ! box || ! add ) { return; }
		var renumber = function () {
			box.querySelectorAll( '[data-smx-floor]' ).forEach( function ( row, i ) {
				row.querySelector( '[data-smx-floor-name]' ).textContent = i ? ( box.getAttribute( 'data-level' ) || 'F+%d' ).replace( '%d', i ) : box.getAttribute( 'data-ground' );
				row.querySelectorAll( '[name]' ).forEach( function ( el ) { el.name = el.name.replace( /\[floors\]\[\d+\]/, '[floors][' + i + ']' ); } );
			} );
		};
		add.addEventListener( 'click', function () {
			var rows = box.querySelectorAll( '[data-smx-floor]' );
			if ( rows.length >= 40 ) { return; }
			var last = rows[ rows.length - 1 ], row = last.cloneNode( true );
			row.querySelectorAll( '.sm-field-error' ).forEach( function ( e ) { e.remove(); } );
			var h = row.querySelector( 'input' ), lh = last.querySelector( 'input' );
			h.value = lh.value; /* Same height as the level below; the client changes it. */
			last.parentNode.appendChild( row );
			renumber();
			h.focus();
			form.dispatchEvent( new Event( 'change' ) );
		} );
		box.addEventListener( 'click', function ( e ) {
			var rm = e.target.closest( '[data-smx-floor-remove]' );
			if ( ! rm ) { return; }
			var rows = box.querySelectorAll( '[data-smx-floor]' );
			var row = rm.closest( '[data-smx-floor]' );
			if ( rows.length > 1 ) { row.remove(); } else { row.querySelector( 'input' ).value = ''; row.querySelector( 'select' ).value = ''; }
			renumber();
			form.dispatchEvent( new Event( 'change' ) );
		} );
	}

	/* Bays: as many T1…Tn as bays along the length; a click on the plan ticks the same box. */
	function doors( form, box ) {
		var sync = function () {
			var n = bays( read( form ) ) || 10;
			form.querySelectorAll( '[data-smx-bay]' ).forEach( function ( cb ) {
				var hide = +cb.getAttribute( 'data-smx-bay' ) > n;
				cb.parentNode.hidden = hide;
				if ( hide ) { cb.checked = false; }
			} );
		};
		form.addEventListener( 'input', function ( e ) { if ( /\[(length|bay)\]/.test( e.target.name || '' ) ) { sync(); } } );
		sync();
		if ( ! box ) { return; }
		box.addEventListener( 'click', function ( e ) {
			var hit = e.target.closest( '[data-smx-hit]' );
			if ( ! hit ) { return; }
			var p = hit.getAttribute( 'data-smx-hit' ).split( ':' );
			var cb = form.querySelector( 'input[name="b[d_' + p[ 0 ] + '_bays][]"][value="' + p[ 1 ] + '"]' );
			if ( ! cb ) { return; }
			cb.checked = ! cb.checked;
			var mode = form.querySelector( 'input[name="b[d_' + p[ 0 ] + ']"][value="doors"]' );
			if ( cb.checked && mode ) { mode.checked = true; }
			form.dispatchEvent( new Event( 'change' ) );
		} );
	}

	document.querySelectorAll( 'form.smx' ).forEach( function ( form ) {
		communes( form );
		floors( form );
		var box = form.querySelector( '[data-smx-sketch]' );
		doors( form, box );
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
