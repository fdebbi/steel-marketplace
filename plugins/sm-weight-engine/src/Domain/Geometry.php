<?php
/**
 * Exact cross-section geometry of every shape, without WordPress.
 *
 * Each shape is described as a sharp polygon whose corners carry a fillet
 * radius (root radius r / r1, toe radius r2, tube corner radii). The same
 * outline gives:
 *   - the area (arcs integrated exactly), hence the theoretical mass;
 *   - the SVG drawing (true arcs), hence the "exact cross-section drawing".
 *
 * Coordinates are in mm, origin at the top-left of the bounding box, y down.
 *
 * Conventions follow EN 10365 (I, H, U), EN 10056-1 (angles) and EN 10219-2
 * (cold-formed hollow sections: external corner radius 2t for t <= 6 mm,
 * 2.5t for t <= 10 mm, 3t above; internal radius = external - t).
 *
 * @package SM\WeightEngine
 */

namespace SM\WeightEngine\Domain;

defined( 'ABSPATH' ) || exit;

final class Geometry {

	/** Dimension keys each shape needs. */
	const SHAPES = array(
		'i_parallel' => array( 'h', 'b', 'tw', 'tf', 'r' ),
		'i_taper'    => array( 'h', 'b', 'tw', 'tf', 'r1', 'r2' ),
		'u_taper'    => array( 'h', 'b', 'tw', 'tf', 'r1', 'r2' ),
		'u_parallel' => array( 'h', 'b', 'tw', 'tf', 'r' ),
		'angle'      => array( 'h', 'b', 't', 'r1', 'r2' ),
		'rhs'        => array( 'h', 'b', 't' ),
		'chs'        => array( 'd', 't' ),
		'flat'       => array( 'b', 't' ),
		'round'      => array( 'd' ),
		'square'     => array( 'a' ),
		'plate'      => array( 't' ),
	);

	/**
	 * Closed contours of a shape: list of contours, each a list of segments.
	 * A segment is ['M'|'L', x, y] or ['A', r, x, y, sweep, cx, cy, a0, a1].
	 * The first contour is the outside; the others are holes.
	 *
	 * @return array<int,array>
	 */
	public static function contours( $shape, array $d ) {
		$d = array_map( 'floatval', $d );
		switch ( $shape ) {
			case 'i_parallel':
				return array( self::fillet( self::i_points( $d['h'], $d['b'], $d['tw'], $d['tf'], 0, $d['r'], 0 ) ) );
			case 'i_taper':
				return array( self::fillet( self::i_points( $d['h'], $d['b'], $d['tw'], $d['tf'], 0.14, $d['r1'], $d['r2'] ) ) );
			case 'u_taper':
				$slope = $d['h'] <= 300 ? 0.08 : 0.05;
				$u     = $d['h'] <= 300 ? $d['b'] / 2 : ( $d['b'] - $d['tw'] ) / 2;
				return array( self::fillet( self::u_points( $d['h'], $d['b'], $d['tw'], $d['tf'], $slope, $u, $d['r1'], $d['r2'] ) ) );
			case 'u_parallel':
				return array( self::fillet( self::u_points( $d['h'], $d['b'], $d['tw'], $d['tf'], 0, 0, $d['r'], 0 ) ) );
			case 'angle':
				$h = $d['h'];
				$b = $d['b'];
				$t = $d['t'];
				return array(
					self::fillet(
						array(
							array( 0, 0, 0 ),
							array( $t, 0, $d['r2'] ),
							array( $t, $h - $t, $d['r1'] ),
							array( $b, $h - $t, $d['r2'] ),
							array( $b, $h, 0 ),
							array( 0, $h, 0 ),
						)
					),
				);
			case 'rhs':
				list( $ro, $ri ) = self::rhs_radii( $d['t'] );
				return array(
					self::fillet( self::rect( 0, 0, $d['b'], $d['h'], $ro ) ),
					self::fillet( self::rect( $d['t'], $d['t'], $d['b'] - $d['t'], $d['h'] - $d['t'], $ri ) ),
				);
			case 'chs':
				return array( self::circle( $d['d'] / 2, $d['d'] / 2, $d['d'] / 2 ), self::circle( $d['d'] / 2, $d['d'] / 2, $d['d'] / 2 - $d['t'] ) );
			case 'round':
				return array( self::circle( $d['d'] / 2, $d['d'] / 2, $d['d'] / 2 ) );
			case 'square':
				return array( self::fillet( self::rect( 0, 0, $d['a'], $d['a'], 0 ) ) );
			case 'flat':
				return array( self::fillet( self::rect( 0, 0, $d['b'], $d['t'], 0 ) ) );
		}
		return array();
	}

	/** External and internal corner radii of a cold-formed hollow section (EN 10219-2). */
	public static function rhs_radii( $t ) {
		$ro = $t <= 6 ? 2 * $t : ( $t <= 10 ? 2.5 * $t : 3 * $t );
		return array( $ro, $ro - $t );
	}

	/** Area in mm2 (outside minus holes). Plates have no section area (per m2 instead). */
	public static function area( $shape, array $d ) {
		$area = 0.0;
		foreach ( self::contours( $shape, $d ) as $i => $contour ) {
			$a     = abs( self::contour_area( $contour ) );
			$area += 0 === $i ? $a : -$a;
		}
		return $area;
	}

	/** Bounding box [w, h] in mm. */
	public static function size( $shape, array $d ) {
		switch ( $shape ) {
			case 'chs':
			case 'round':
				return array( (float) $d['d'], (float) $d['d'] );
			case 'square':
				return array( (float) $d['a'], (float) $d['a'] );
			case 'flat':
				return array( (float) $d['b'], (float) $d['t'] );
		}
		return array( (float) ( $d['b'] ?? 0 ), (float) ( $d['h'] ?? 0 ) );
	}

	/**
	 * Check the dimensions of a shape: all present, positive and consistent.
	 *
	 * @return true|string Error key.
	 */
	public static function validate( $shape, array $d ) {
		if ( ! isset( self::SHAPES[ $shape ] ) ) {
			return 'shape';
		}
		foreach ( self::SHAPES[ $shape ] as $k ) {
			if ( ! isset( $d[ $k ] ) || ! is_numeric( $d[ $k ] ) || (float) $d[ $k ] < 0 ) {
				return 'missing';
			}
			if ( (float) $d[ $k ] <= 0 && ! in_array( $k, array( 'r', 'r1', 'r2' ), true ) ) {
				return 'missing';
			}
		}
		$v = array_map( 'floatval', $d );
		switch ( $shape ) {
			case 'i_parallel':
			case 'i_taper':
				if ( $v['tw'] >= $v['b'] || 2 * $v['tf'] >= $v['h'] ) {
					return 'thickness';
				}
				break;
			case 'u_parallel':
			case 'u_taper':
				if ( $v['tw'] >= $v['b'] || 2 * $v['tf'] >= $v['h'] ) {
					return 'thickness';
				}
				break;
			case 'angle':
				if ( $v['t'] >= min( $v['h'], $v['b'] ) ) {
					return 'thickness';
				}
				break;
			case 'rhs':
				list( $ro ) = self::rhs_radii( $v['t'] );
				if ( 2 * $ro >= min( $v['h'], $v['b'] ) ) {
					return 'thickness';
				}
				break;
			case 'chs':
				if ( 2 * $v['t'] >= $v['d'] ) {
					return 'thickness';
				}
				break;
		}
		return true;
	}

	/* ------------------------------------------------------------------ */

	/** Sharp outline of an I section (slope 0 = parallel flanges). Points [x, y, radius]. */
	private static function i_points( $h, $b, $tw, $tf, $slope, $r1, $r2 ) {
		$c    = $b / 2;
		$tip  = $tf - $b / 4 * $slope;          // flange thickness at the tip
		$root = $tf + ( $b / 4 - $tw / 2 ) * $slope; // at the web face
		return array(
			array( 0, 0, 0 ),
			array( $b, 0, 0 ),
			array( $b, $tip, $r2 ),
			array( $c + $tw / 2, $root, $r1 ),
			array( $c + $tw / 2, $h - $root, $r1 ),
			array( $b, $h - $tip, $r2 ),
			array( $b, $h, 0 ),
			array( 0, $h, 0 ),
			array( 0, $h - $tip, $r2 ),
			array( $c - $tw / 2, $h - $root, $r1 ),
			array( $c - $tw / 2, $root, $r1 ),
			array( 0, $tip, $r2 ),
		);
	}

	/** Sharp outline of a channel, back on the left. $u = distance from the toe where tf is measured. */
	private static function u_points( $h, $b, $tw, $tf, $slope, $u, $r1, $r2 ) {
		$tip  = $tf - $slope * $u;
		$root = $tf + $slope * ( $b - $u - $tw );
		return array(
			array( 0, 0, 0 ),
			array( $b, 0, 0 ),
			array( $b, $tip, $r2 ),
			array( $tw, $root, $r1 ),
			array( $tw, $h - $root, $r1 ),
			array( $b, $h - $tip, $r2 ),
			array( $b, $h, 0 ),
			array( 0, $h, 0 ),
		);
	}

	private static function rect( $x0, $y0, $x1, $y1, $r ) {
		return array( array( $x0, $y0, $r ), array( $x1, $y0, $r ), array( $x1, $y1, $r ), array( $x0, $y1, $r ) );
	}

	/** Full circle as two half arcs. */
	private static function circle( $cx, $cy, $r ) {
		return array(
			array( 'M', $cx + $r, $cy ),
			array( 'A', $r, $cx - $r, $cy, 1, $cx, $cy, 0.0, M_PI ),
			array( 'A', $r, $cx + $r, $cy, 1, $cx, $cy, M_PI, 2 * M_PI ),
		);
	}

	/**
	 * Round the corners of a sharp polygon: each vertex [x, y, r] with r > 0
	 * becomes a tangent arc of radius r.
	 */
	private static function fillet( array $pts ) {
		$n   = count( $pts );
		$out = array();
		for ( $i = 0; $i < $n; $i++ ) {
			list( $x, $y, $r ) = $pts[ $i ];
			$p = $pts[ ( $i - 1 + $n ) % $n ];
			$q = $pts[ ( $i + 1 ) % $n ];
			if ( $r <= 0 ) {
				$out[] = array( $out ? 'L' : 'M', $x, $y );
				continue;
			}
			$u1x = $p[0] - $x;
			$u1y = $p[1] - $y;
			$u2x = $q[0] - $x;
			$u2y = $q[1] - $y;
			$l1  = hypot( $u1x, $u1y );
			$l2  = hypot( $u2x, $u2y );
			$u1x /= $l1;
			$u1y /= $l1;
			$u2x /= $l2;
			$u2y /= $l2;
			$theta = acos( max( -1.0, min( 1.0, $u1x * $u2x + $u1y * $u2y ) ) );
			$dist  = $r / tan( $theta / 2 );
			$t1    = array( $x + $u1x * $dist, $y + $u1y * $dist );
			$t2    = array( $x + $u2x * $dist, $y + $u2y * $dist );
			$bx    = $u1x + $u2x;
			$by    = $u1y + $u2y;
			$bl    = hypot( $bx, $by );
			$cd    = $r / sin( $theta / 2 );
			$cx    = $x + $bx / $bl * $cd;
			$cy    = $y + $by / $bl * $cd;
			$a0    = atan2( $t1[1] - $cy, $t1[0] - $cx );
			$a1    = atan2( $t2[1] - $cy, $t2[0] - $cx );
			$delta = $a1 - $a0;
			while ( $delta > M_PI ) {
				$delta -= 2 * M_PI;
			}
			while ( $delta < -M_PI ) {
				$delta += 2 * M_PI;
			}
			$out[] = array( $out ? 'L' : 'M', $t1[0], $t1[1] );
			$out[] = array( 'A', $r, $t2[0], $t2[1], $delta > 0 ? 1 : 0, $cx, $cy, $a0, $a0 + $delta );
		}
		return $out;
	}

	/** Exact signed area of a contour of lines and circular arcs (Green's theorem). */
	private static function contour_area( array $segs ) {
		$sum   = 0.0;
		$start = null;
		$px    = 0.0;
		$py    = 0.0;
		foreach ( $segs as $s ) {
			if ( 'M' === $s[0] ) {
				$start = array( $s[1], $s[2] );
				$px    = $s[1];
				$py    = $s[2];
				continue;
			}
			if ( 'L' === $s[0] ) {
				$sum += $px * $s[2] - $s[1] * $py;
				$px   = $s[1];
				$py   = $s[2];
				continue;
			}
			// Arc: integral of (x dy - y dx) along x = cx + r cos a, y = cy + r sin a.
			list( , $r, $x, $y, , $cx, $cy, $a0, $a1 ) = $s;
			$sum += $r * $r * ( $a1 - $a0 )
				+ $cx * $r * ( sin( $a1 ) - sin( $a0 ) )
				- $cy * $r * ( cos( $a1 ) - cos( $a0 ) );
			$px   = $x;
			$py   = $y;
		}
		if ( $start ) {
			$sum += $px * $start[1] - $start[0] * $py;
		}
		return $sum / 2;
	}
}
