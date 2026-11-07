<?php
/**
 * Indicative drawing of a building from the figures given by the seller:
 * gable elevation (width, eaves height, roof shape and slope) and plan
 * (length, width, portal frames). It only draws the numbers that were typed:
 * no member size, no structural meaning. Prepared as the entry point of the
 * future 3D configurator (same shape data, see Presenter::shape()).
 *
 * @package SM\Buildings
 */

namespace SM\Buildings;

defined( 'ABSPATH' ) || exit;

final class Drawing {

	/**
	 * @param array $shape length, width, height (m), portals, roof (duo|mono|multi|curved|other), slope (%).
	 * @return string SVG (numbers only, safe to print).
	 */
	public static function svg( array $shape ) {
		$l = (float) ( $shape['length'] ?? 0 );
		$w = (float) ( $shape['width'] ?? 0 );
		$h = (float) ( $shape['height'] ?? 0 );
		$n = max( 2, (int) ( $shape['portals'] ?? 2 ) );
		if ( $l <= 0 || $w <= 0 || $h <= 0 ) {
			return '';
		}
		$roof  = (string) ( $shape['roof'] ?? 'duo' );
		$slope = (float) ( $shape['slope'] ?? 0 );
		if ( $slope <= 0 ) {
			$slope = 0; // Unknown slope: drawn flat-topped rather than guessed.
		}
		// Gable: ridge rise.
		$rise = 0.0;
		if ( 'duo' === $roof || 'multi' === $roof ) {
			$rise = $w / 2 * $slope / 100;
		} elseif ( 'mono' === $roof ) {
			$rise = $w * $slope / 100;
		} elseif ( 'curved' === $roof ) {
			$rise = $slope > 0 ? $w / 2 * $slope / 100 : $w * 0.15;
		}

		// Two panels of 260 × 200 units; each view scaled on its own.
		$pw = 260;
		$ph = 170;
		$m  = 34;
		$s1 = min( ( $pw - 2 * $m ) / $w, ( $ph - 2 * $m ) / ( $h + $rise ) );
		$x0 = ( $pw - $w * $s1 ) / 2;
		$y0 = $ph - $m + 6; // Ground line.
		$px = static fn( $x ) => round( $x0 + $x * $s1, 1 );
		$py = static fn( $y ) => round( $y0 - $y * $s1, 1 );

		$g = '<line x1="10" y1="' . $y0 . '" x2="' . ( $pw - 10 ) . '" y2="' . $y0 . '" class="g"/>';
		// Outline of the gable.
		if ( 'mono' === $roof ) {
			$pts = array( array( 0, 0 ), array( 0, $h ), array( $w, $h + $rise ), array( $w, 0 ) );
		} elseif ( 'curved' === $roof ) {
			$pts = null;
		} else {
			$pts = array( array( 0, 0 ), array( 0, $h ), array( $w / 2, $h + $rise ), array( $w, $h ), array( $w, 0 ) );
		}
		if ( $pts ) {
			$g .= '<polyline points="' . implode( ' ', array_map( static fn( $p ) => $px( $p[0] ) . ',' . $py( $p[1] ), $pts ) ) . '" class="s"/>';
		} else {
			$g .= '<path d="M' . $px( 0 ) . ',' . $py( 0 ) . ' L' . $px( 0 ) . ',' . $py( $h ) . ' Q' . $px( $w / 2 ) . ',' . $py( $h + 2 * $rise ) . ' ' . $px( $w ) . ',' . $py( $h ) . ' L' . $px( $w ) . ',' . $py( 0 ) . '" class="s"/>';
		}
		// Dimension lines: width under, height on the left.
		$g .= self::dim( $px( 0 ), $y0 + 14, $px( $w ), $y0 + 14, Spec::fmt( $w ) . ' m', 'h' );
		$g .= self::dim( $px( 0 ) - 14, $py( 0 ), $px( 0 ) - 14, $py( $h ), Spec::fmt( $h ) . ' m', 'v' );
		if ( $rise > 0 && $slope > 0 ) {
			$g .= '<text x="' . $px( 'mono' === $roof ? $w * 0.5 : $w * 0.25 ) . '" y="' . ( $py( $h + $rise * 0.5 ) - 8 ) . '" class="t">' . Spec::fmt( $slope ) . ' %</text>';
		}

		// Plan: length horizontal, width vertical, one line per portal frame.
		$s2  = min( ( $pw - 2 * $m ) / $l, ( $ph - 2 * $m ) / $w );
		$ox  = $pw + ( $pw - $l * $s2 ) / 2;
		$oy  = ( $ph - $w * $s2 ) / 2 - 4;
		$pl  = '<rect x="' . round( $ox, 1 ) . '" y="' . round( $oy, 1 ) . '" width="' . round( $l * $s2, 1 ) . '" height="' . round( $w * $s2, 1 ) . '" class="s"/>';
		for ( $i = 0; $i < $n; $i++ ) {
			$x   = round( $ox + $l * $s2 * $i / ( $n - 1 ), 1 );
			$pl .= '<line x1="' . $x . '" y1="' . round( $oy, 1 ) . '" x2="' . $x . '" y2="' . round( $oy + $w * $s2, 1 ) . '" class="p"/>';
		}
		$pl .= self::dim( round( $ox, 1 ), round( $oy + $w * $s2 + 14, 1 ), round( $ox + $l * $s2, 1 ), round( $oy + $w * $s2 + 14, 1 ), Spec::fmt( $l ) . ' m', 'h' );
		$pl .= self::dim( round( $ox - 14, 1 ), round( $oy + $w * $s2, 1 ), round( $ox - 14, 1 ), round( $oy, 1 ), Spec::fmt( $w ) . ' m', 'v' );

		$style = '<style>.s{fill:color-mix(in srgb,currentColor 8%,transparent);stroke:currentColor;stroke-width:2;stroke-linejoin:round}.p{stroke:currentColor;stroke-width:1.2;opacity:.55}.g{stroke:currentColor;stroke-width:1;opacity:.35;stroke-dasharray:4 3}.d{stroke:currentColor;stroke-width:.8;opacity:.7}.t{font:600 11px system-ui,sans-serif;fill:currentColor;text-anchor:middle}</style>';
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . ( 2 * $pw ) . ' ' . $ph . '" role="img" class="smb-drawing">' . $style . '<g>' . $g . '</g><g>' . $pl . '</g></svg>';
	}

	private static function dim( $x1, $y1, $x2, $y2, $label, $dir ) {
		$out = '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" class="d"/>';
		foreach ( array( array( $x1, $y1 ), array( $x2, $y2 ) ) as $p ) {
			$out .= 'h' === $dir
				? '<line x1="' . $p[0] . '" y1="' . ( $p[1] - 4 ) . '" x2="' . $p[0] . '" y2="' . ( $p[1] + 4 ) . '" class="d"/>'
				: '<line x1="' . ( $p[0] - 4 ) . '" y1="' . $p[1] . '" x2="' . ( $p[0] + 4 ) . '" y2="' . $p[1] . '" class="d"/>';
		}
		if ( 'h' === $dir ) {
			return $out . '<text x="' . round( ( $x1 + $x2 ) / 2, 1 ) . '" y="' . ( $y1 + 14 ) . '" class="t">' . esc_html( $label ) . '</text>';
		}
		$cx = round( $x1 - 6, 1 );
		$cy = round( ( $y1 + $y2 ) / 2, 1 );
		return $out . '<text x="' . $cx . '" y="' . $cy . '" class="t" transform="rotate(-90 ' . $cx . ' ' . $cy . ')">' . esc_html( $label ) . '</text>';
	}
}
