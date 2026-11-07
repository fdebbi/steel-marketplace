<?php
/**
 * Exact cross-section drawings (SVG), to scale, from the real dimensions.
 *
 * The outline is the same one used to compute the area (Domain\Geometry), so
 * the drawing and the weight can never disagree. Dimension lines show h and b
 * (or D, t); the values themselves are shown next to the drawing by the theme.
 * Styling hooks: .smw-shape, .smw-dim, .smw-label (colours follow currentColor).
 *
 * @package SM\WeightEngine
 */

namespace SM\WeightEngine;

use SM\WeightEngine\Domain\Geometry;

defined( 'ABSPATH' ) || exit;

final class Drawing {

	/**
	 * @param string $shape  Geometry shape.
	 * @param array  $dims   Dimensions in mm.
	 * @param array  $opts   title (accessible name), dims (bool, dimension lines; default true).
	 */
	public static function svg( $shape, array $dims, array $opts = array() ) {
		$opts = wp_parse_args( $opts, array( 'title' => '', 'dims' => true ) );
		if ( 'plate' === $shape ) {
			$dims  = array( 'b' => 120, 't' => max( 4, min( 40, (float) $dims['t'] * 2 ) ) );
			$shape = 'flat';
		}
		$contours = Geometry::contours( $shape, $dims );
		if ( ! $contours ) {
			return '';
		}
		list( $w, $h ) = Geometry::size( $shape, $dims );
		$ref  = max( $w, $h );
		$font = $ref * 0.075;
		$gap  = $ref * 0.07;
		$pad  = $opts['dims'] ? $ref * 0.24 : $ref * 0.04;

		$min_x = -$pad;
		$min_y = -$ref * 0.04;
		$vb_w  = $w + $pad + $ref * 0.04;
		$vb_h  = $h + $pad + $ref * 0.04;
		if ( 'flat' === $shape ) { // Thin bars: give the drawing some height.
			$min_y -= $ref * 0.1;
			$vb_h  += $ref * 0.1;
		}

		$path = '';
		foreach ( $contours as $contour ) {
			foreach ( $contour as $seg ) {
				if ( 'A' === $seg[0] ) {
					$path .= sprintf( 'A%s %s 0 0 %d %s %s', self::n( $seg[1] ), self::n( $seg[1] ), $seg[4], self::n( $seg[2] ), self::n( $seg[3] ) );
				} else {
					$path .= $seg[0] . self::n( $seg[1] ) . ' ' . self::n( $seg[2] );
				}
			}
			$path .= 'Z';
		}

		$title = $opts['title'] ? '<title>' . esc_html( $opts['title'] ) . '</title>' : '';
		$out   = sprintf(
			'<svg class="smw-drawing" xmlns="http://www.w3.org/2000/svg" viewBox="%s %s %s %s" role="img"%s>%s',
			self::n( $min_x ),
			self::n( $min_y ),
			self::n( $vb_w ),
			self::n( $vb_h ),
			$opts['title'] ? '' : ' aria-hidden="true"',
			$title
		);
		$out .= '<path class="smw-shape" d="' . $path . '" fill="currentColor" fill-opacity=".14" fill-rule="evenodd" stroke="currentColor" stroke-width="1.5" vector-effect="non-scaling-stroke"/>';

		if ( $opts['dims'] ) {
			$labels = self::labels( $shape, $dims );
			// Height, on the left.
			$x    = -$gap * 1.6;
			$out .= self::dim_line( $x, 0, $x, $h, $ref );
			$out .= sprintf( '<text class="smw-label" x="%s" y="%s" font-size="%s" text-anchor="middle" transform="rotate(-90 %s %s)" fill="currentColor">%s</text>', self::n( $x - $font * 0.45 ), self::n( $h / 2 ), self::n( $font ), self::n( $x - $font * 0.45 ), self::n( $h / 2 ), esc_html( $labels['h'] ) );
			// Width, below.
			$y    = $h + $gap * 1.6;
			$out .= self::dim_line( 0, $y, $w, $y, $ref );
			$out .= sprintf( '<text class="smw-label" x="%s" y="%s" font-size="%s" text-anchor="middle" fill="currentColor">%s</text>', self::n( $w / 2 ), self::n( $y + $font * 1.15 ), self::n( $font ), esc_html( $labels['b'] ) );
		}
		return $out . '</svg>';
	}

	/** Labels of the two dimension lines. */
	private static function labels( $shape, array $d ) {
		switch ( $shape ) {
			case 'chs':
			case 'round':
				return array( 'h' => 'D', 'b' => 'D' );
			case 'square':
				return array( 'h' => 'a', 'b' => 'a' );
			case 'flat':
				return array( 'h' => 't', 'b' => 'b' );
		}
		return array( 'h' => 'h', 'b' => 'b' );
	}

	private static function dim_line( $x1, $y1, $x2, $y2, $ref ) {
		$tick = $ref * 0.025;
		$vertical = abs( $x1 - $x2 ) < 1e-9;
		$t1 = $vertical ? sprintf( 'M%s %sH%s', self::n( $x1 - $tick ), self::n( $y1 ), self::n( $x1 + $tick ) ) : sprintf( 'M%s %sV%s', self::n( $x1 ), self::n( $y1 - $tick ), self::n( $y1 + $tick ) );
		$t2 = $vertical ? sprintf( 'M%s %sH%s', self::n( $x2 - $tick ), self::n( $y2 ), self::n( $x2 + $tick ) ) : sprintf( 'M%s %sV%s', self::n( $x2 ), self::n( $y2 - $tick ), self::n( $y2 + $tick ) );
		return sprintf( '<path class="smw-dim" d="M%s %sL%s %s%s%s" fill="none" stroke="currentColor" stroke-opacity=".55" stroke-width="1" vector-effect="non-scaling-stroke"/>', self::n( $x1 ), self::n( $y1 ), self::n( $x2 ), self::n( $y2 ), $t1, $t2 );
	}

	private static function n( $v ) {
		return rtrim( rtrim( number_format( (float) $v, 2, '.', '' ), '0' ), '.' );
	}
}
