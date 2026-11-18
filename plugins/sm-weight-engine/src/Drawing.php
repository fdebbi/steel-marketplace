<?php
/**
 * Exact cross-section drawings (SVG), to scale, from the real dimensions.
 *
 * The outline is the same one used to compute the area (Domain\Geometry), so
 * the drawing and the weight can never disagree. Dimension lines show the real
 * values (0.3.1): height and width, the diameter of round sections, and the wall
 * thickness of tubes and angles ("t = 2 mm"). With values => false: letters.
 * Styling hooks: .smw-shape, .smw-dim, .smw-label (colours follow currentColor).
 *
 * @package SM\WeightEngine
 */

namespace SM\WeightEngine;

use SM\WeightEngine\Domain\Geometry;

defined( 'ABSPATH' ) || exit;

final class Drawing {

	/** Version of the stored picture files (3: wall thickness shown). */
	const FILES_VERSION = '3';

	/**
	 * @param string $shape  Geometry shape.
	 * @param array  $dims   Dimensions in mm.
	 * @param array  $opts   title (accessible name), dims (bool, dimension lines; default true),
	 *                       values (bool, real values in mm instead of letters; default true since 0.3.1).
	 */
	public static function svg( $shape, array $dims, array $opts = array() ) {
		$opts = wp_parse_args( $opts, array( 'title' => '', 'dims' => true, 'values' => true ) );
		$real = $dims; // Before the plate stand-in below: the values shown are the real ones.
		$real_shape = $shape;
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

		$round = in_array( $shape, array( 'chs', 'round' ), true );
		// 0.3.1: the wall thickness of tubes and angles, written beside the section.
		$wall = null;
		if ( $opts['dims'] && in_array( $shape, array( 'rhs', 'chs', 'angle' ), true ) && ! empty( $dims['t'] ) ) {
			$t    = (float) $dims['t'];
			$wall = array(
				'x'    => 'angle' === $shape ? $w - min( $t, $w * 0.08 ) : $w - $t / 2,
				'y'    => 'angle' === $shape ? $h - $t / 2 : $h / 2,
				'text' => $opts['values'] ? 't = ' . self::mm( $t ) : 't',
			);
		}
		$wall_w = $wall ? $gap * 1.4 + mb_strlen( $wall['text'] ) * $font * 0.58 : 0;

		$min_x = $round && $opts['dims'] ? -$ref * 0.04 : -$pad; // Round sections: one diameter line, below.
		$min_y = -$ref * 0.04;
		$vb_w  = $w - $min_x + $ref * 0.04 + $wall_w;
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
			$labels = $opts['values'] ? self::value_labels( $real_shape, $real ) : self::labels( $shape, $dims );
			// Height, on the left (not for round sections: the diameter is given once, below).
			if ( ! $round ) {
				$x    = -$gap * 1.6;
				$out .= self::dim_line( $x, 0, $x, $h, $ref );
				$out .= sprintf( '<text class="smw-label" x="%s" y="%s" font-size="%s" text-anchor="middle" transform="rotate(-90 %s %s)" fill="currentColor">%s</text>', self::n( $x - $font * 0.45 ), self::n( $h / 2 ), self::n( $font ), self::n( $x - $font * 0.45 ), self::n( $h / 2 ), esc_html( $labels['h'] ) );
			}
			// Width, below.
			$y    = $h + $gap * 1.6;
			$out .= self::dim_line( 0, $y, $w, $y, $ref );
			$out .= sprintf( '<text class="smw-label" x="%s" y="%s" font-size="%s" text-anchor="middle" fill="currentColor">%s</text>', self::n( $w / 2 ), self::n( $y + $font * 1.15 ), self::n( $font ), esc_html( $round ? 'Ø ' . $labels['b'] : $labels['b'] ) );
			if ( $wall ) {
				// A dot on the wall, a short line to the right, then "t = 2 mm".
				$end  = $w + $gap * 0.9;
				$out .= sprintf( '<circle class="smw-dim" cx="%s" cy="%s" r="%s" fill="currentColor"/>', self::n( $wall['x'] ), self::n( $wall['y'] ), self::n( $ref * 0.012 ) );
				$out .= sprintf( '<path class="smw-dim" d="M%s %sH%s" fill="none" stroke="currentColor" stroke-opacity=".55" stroke-width="1" vector-effect="non-scaling-stroke"/>', self::n( $wall['x'] ), self::n( $wall['y'] ), self::n( $end ) );
				$out .= sprintf( '<text class="smw-label smw-label--t" x="%s" y="%s" font-size="%s" fill="currentColor">%s</text>', self::n( $end + $gap * 0.3 ), self::n( $wall['y'] + $font * 0.35 ), self::n( $font ), esc_html( $wall['text'] ) );
			}
		}
		return $out . '</svg>';
	}

	/**
	 * Real values on the two dimension lines (0.2.0): "160 mm" / "82 mm";
	 * tubes and bars show the diameter or side, plates their thickness.
	 */
	/** A value in mm, French style: "48,3 mm". */
	private static function mm( $v ) {
		return str_replace( '.', ',', rtrim( rtrim( number_format( (float) $v, 1, '.', '' ), '0' ), '.' ) ) . ' mm';
	}

	private static function value_labels( $shape, array $d ) {
		$mm = static fn( $v ) => self::mm( $v );
		switch ( $shape ) {
			case 'chs':
			case 'round':
				return array( 'h' => $mm( $d['d'] ), 'b' => $mm( $d['d'] ) );
			case 'square':
				return array( 'h' => $mm( $d['a'] ), 'b' => $mm( $d['a'] ) );
			case 'flat':
				return array( 'h' => $mm( $d['t'] ), 'b' => $mm( $d['b'] ) );
			case 'plate':
				return array( 'h' => $mm( $d['t'] ), 'b' => '' );
		}
		return array( 'h' => $mm( $d['h'] ?? 0 ), 'b' => $mm( $d['b'] ?? 0 ) );
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

	/**
	 * Contours as polygons (arcs flattened), in mm: list of [ [x, y], … ].
	 * Used to draw the section without SVG support (PNG pictures).
	 */
	public static function polygons( $shape, array $dims ) {
		if ( 'plate' === $shape ) {
			$dims  = array( 'b' => 120, 't' => max( 4, min( 40, (float) $dims['t'] * 2 ) ) );
			$shape = 'flat';
		}
		$out = array();
		foreach ( (array) Geometry::contours( $shape, $dims ) as $contour ) {
			$pts = array();
			$cx  = 0.0;
			$cy  = 0.0;
			foreach ( $contour as $seg ) {
				if ( 'A' === $seg[0] ) {
					foreach ( self::arc_points( $cx, $cy, (float) $seg[1], (float) $seg[2], (float) $seg[3], (int) $seg[4] ) as $pt ) {
						$pts[] = $pt;
					}
					$cx = (float) $seg[2];
					$cy = (float) $seg[3];
				} else {
					$cx    = (float) $seg[1];
					$cy    = (float) $seg[2];
					$pts[] = array( $cx, $cy );
				}
			}
			if ( count( $pts ) >= 3 ) {
				$out[] = $pts;
			}
		}
		return $out;
	}

	/** Points along an SVG arc (small arc) from (x0, y0) to (x1, y1). */
	private static function arc_points( $x0, $y0, $r, $x1, $y1, $sweep ) {
		$dx = $x1 - $x0;
		$dy = $y1 - $y0;
		$d  = sqrt( $dx * $dx + $dy * $dy );
		if ( $d < 1e-9 ) {
			return array();
		}
		if ( $r < $d / 2 ) {
			$r = $d / 2;
		}
		$h    = sqrt( max( 0, $r * $r - $d * $d / 4 ) );
		$sign = $sweep ? 1 : -1; // Small arc: the centre is on the side given by the sweep flag.
		$mx   = ( $x0 + $x1 ) / 2 - $sign * $h * $dy / $d;
		$my   = ( $y0 + $y1 ) / 2 + $sign * $h * $dx / $d;
		$a0   = atan2( $y0 - $my, $x0 - $mx );
		$a1   = atan2( $y1 - $my, $x1 - $mx );
		$da   = $a1 - $a0;
		if ( $sweep && $da < 0 ) {
			$da += 2 * M_PI;
		} elseif ( ! $sweep && $da > 0 ) {
			$da -= 2 * M_PI;
		}
		$n   = max( 4, (int) ceil( abs( $da ) / ( M_PI / 24 ) ) );
		$pts = array();
		for ( $i = 1; $i <= $n; $i++ ) {
			$a     = $a0 + $da * $i / $n;
			$pts[] = array( $mx + $r * cos( $a ), $my + $r * sin( $a ) );
		}
		return $pts;
	}

	/**
	 * Picture files of a section for listings without photos (0.2.0): an SVG and
	 * a 1200 × 630 PNG (for link previews, which do not read SVG), stored once in
	 * uploads/sm-drawings/. Returns { svg, png } URLs, or null.
	 */
	public static function files( $shape, array $dims, $key, $title = '' ) {
		$key  = sanitize_file_name( strtolower( $key ) );
		$up   = wp_upload_dir();
		$dir  = trailingslashit( $up['basedir'] ) . 'sm-drawings';
		$url  = trailingslashit( $up['baseurl'] ) . 'sm-drawings';
		$key  = $key . '-' . self::FILES_VERSION;
		$svgf = $dir . '/' . $key . '.svg';
		$pngf = $dir . '/' . $key . '.png';
		if ( ! file_exists( $svgf ) || ! file_exists( $pngf ) ) {
			if ( ! wp_mkdir_p( $dir ) ) {
				return null;
			}
			$svg = self::svg( $shape, $dims, array( 'title' => $title, 'values' => true ) );
			if ( '' === $svg ) {
				return null;
			}
			// Light steel on a transparent ground; the theme puts it on its dark steel card.
			$svg = preg_replace( '/^<svg /', '<svg style="color:#C6CDD2;font-family:Arial,Helvetica,sans-serif" ', $svg );
			file_put_contents( $svgf, $svg ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			$png = self::png( $shape, $dims );
			if ( $png ) {
				file_put_contents( $pngf, $png ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			}
		}
		return array(
			'svg' => $url . '/' . $key . '.svg',
			'png' => file_exists( $pngf ) ? $url . '/' . $key . '.png' : '',
		);
	}

	/** 1200 × 630 PNG of the section (GD): dark steel ground, light section, green edge. */
	public static function png( $shape, array $dims, $w = 1200, $h = 630 ) {
		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			return '';
		}
		$polys = self::polygons( $shape, $dims );
		if ( ! $polys ) {
			return '';
		}
		$min_x = INF;
		$min_y = INF;
		$max_x = -INF;
		$max_y = -INF;
		foreach ( $polys as $poly ) {
			foreach ( $poly as $p ) {
				$min_x = min( $min_x, $p[0] );
				$max_x = max( $max_x, $p[0] );
				$min_y = min( $min_y, $p[1] );
				$max_y = max( $max_y, $p[1] );
			}
		}
		$scale = min( ( $w * 0.6 ) / max( 1e-6, $max_x - $min_x ), ( $h * 0.72 ) / max( 1e-6, $max_y - $min_y ) );
		$ox    = ( $w - ( $max_x - $min_x ) * $scale ) / 2 - $min_x * $scale;
		$oy    = ( $h - ( $max_y - $min_y ) * $scale ) / 2 - $min_y * $scale;
		$s     = 2; // Draw twice as large, then reduce: smooth edges.
		$img   = imagecreatetruecolor( $w * $s, $h * $s );
		$bg    = imagecolorallocate( $img, 11, 16, 21 );
		imagefill( $img, 0, 0, $bg );
		$glow = imagecolorallocatealpha( $img, 6, 206, 119, 125 );
		for ( $i = 0; $i < 14; $i++ ) { // Soft green glow behind the section (layers of 2 % green).
			imagefilledellipse( $img, (int) ( $w * $s / 2 ), (int) ( $h * $s * 0.56 ), (int) ( $w * $s * ( 0.75 - $i * 0.04 ) ), (int) ( $h * $s * ( 0.9 - $i * 0.05 ) ), $glow );
		}
		$fill  = imagecolorallocate( $img, 198, 205, 210 );
		$edge  = imagecolorallocate( $img, 6, 206, 119 );
		imagesetthickness( $img, 3 * $s );
		foreach ( $polys as $k => $poly ) {
			$flat = array();
			foreach ( $poly as $p ) {
				$flat[] = (int) round( ( $p[0] * $scale + $ox ) * $s );
				$flat[] = (int) round( ( $p[1] * $scale + $oy ) * $s );
			}
			// The first contour is the outline; the next ones are holes (tubes).
			imagefilledpolygon( $img, $flat, 0 === $k ? $fill : $bg );
			imagepolygon( $img, $flat, $edge );
		}
		$out = imagecreatetruecolor( $w, $h );
		imagecopyresampled( $out, $img, 0, 0, 0, 0, $w, $h, $w * $s, $h * $s );
		imagedestroy( $img );
		ob_start();
		imagepng( $out, null, 8 );
		imagedestroy( $out );
		return (string) ob_get_clean();
	}

	private static function n( $v ) {
		return rtrim( rtrim( number_format( (float) $v, 2, '.', '' ), '0' ), '.' );
	}
}
