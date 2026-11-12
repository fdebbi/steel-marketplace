<?php
/**
 * Theoretical weight calculations.
 *
 * Input keys:
 *   family        IPE, HEA, … (required)
 *   section       section ID, or designation  "IPE 200" (catalogue sizes)
 *   dims          dimensions in mm for formula families when the size is typed
 *                 (SHS/RHS: h, b, t; CHS: d, t; FLAT: b, t; ROUND: d; SQUARE: a; PLATE: t)
 *   mode          "bars"  : number of bars (or plates) -> total weight (default)
 *                 "weight": total weight -> number of bars (or plates)
 *   length        bar length in mm (default: setting default_length, 12 m)
 *   width         plate width in mm (plates)
 *   bars          number of bars / plates (mode bars)
 *   total_kg      total weight in kg (mode weight)
 *
 * Results are theoretical (nominal dimensions, density from the settings):
 * weight_source = "theoretical". Each result carries the engine and reference
 * data versions so a listing can store how its weight was obtained.
 *
 * @package SM\WeightEngine
 */

namespace SM\WeightEngine;

use SM\WeightEngine\Domain\Geometry;

defined( 'ABSPATH' ) || exit;

final class Calculator {

	/** @return array|\WP_Error */
	public static function calculate( array $in ) {
		$fam = Catalogue::family( $in['family'] ?? '' );
		if ( ! $fam || ! (int) $fam->active ) {
			return self::error( 'smw_family', __( 'Choose a section type.', 'sm-weight-engine' ) );
		}
		$s = Settings::all();

		// 1. The section: catalogue row or typed dimensions.
		$section = null;
		if ( ! empty( $in['section'] ) ) {
			$section = is_numeric( $in['section'] ) ? Catalogue::get( (int) $in['section'] ) : Catalogue::find( $fam->code, $in['section'] );
			if ( ! $section || $section->family !== $fam->code || 'active' !== $section->status ) {
				return self::error( 'smw_section', __( 'Choose a size from the list.', 'sm-weight-engine' ) );
			}
			$dims = $section->dims;
		} elseif ( 'formula' === $fam->method && ! empty( $in['dims'] ) && is_array( $in['dims'] ) ) {
			$raw = $in['dims'];
			if ( 'SHS' === $fam->code ) { // Square tube: one side.
				$raw['h'] = $raw['b'] ?? ( $raw['h'] ?? 0 );
				$raw['b'] = $raw['h'];
			}
			$dims = self::read_dims( $fam->shape, $raw );
			if ( is_wp_error( $dims ) ) {
				return $dims;
			}
		} else {
			return self::error( 'smw_section', 'formula' === $fam->method ? __( 'Enter the dimensions.', 'sm-weight-engine' ) : __( 'Choose a size from the list.', 'sm-weight-engine' ) );
		}

		$density = (float) $s['density'];
		$mode    = ( $in['mode'] ?? 'bars' ) === 'weight' ? 'weight' : 'bars';
		$length  = self::number( $in['length'] ?? $s['default_length'] );
		if ( $length < 100 || $length > $s['max_length'] ) {
			/* translators: %s: maximum length in metres. */
			return self::error( 'smw_length', sprintf( __( 'Enter a length between 0.1 and %s m.', 'sm-weight-engine' ), self::fmt( $s['max_length'] / 1000 ) ) );
		}

		$result = array(
			'family'        => $fam->code,
			'family_name'   => array( 'fr' => $fam->name_fr, 'ar' => $fam->name_ar ),
			'shape'         => $fam->shape,
			'standard'      => $fam->standard,
			'section_id'    => $section ? $section->id : null,
			'designation'   => $section ? $section->designation : self::designation( $fam, $dims ),
			'dims'          => $dims,
			'mode'          => $mode,
			'length_mm'     => $length,
			'weight_source' => 'theoretical',
			'density'       => $density,
			'engine'        => SM_WEIGHT_VERSION,
			'reference'     => SM_WEIGHT_REF_VERSION,
		);

		// 2. Weight of one piece.
		if ( 'plate' === $fam->shape ) {
			$width = self::number( $in['width'] ?? 0 );
			if ( $width < 10 || $width > $s['max_dimension'] * 10 ) {
				return self::error( 'smw_width', __( 'Enter the plate width in mm.', 'sm-weight-engine' ) );
			}
			if ( $width * $length / 1e6 > $s['max_plate_area'] ) {
				return self::error( 'smw_width', __( 'This plate is larger than the allowed size.', 'sm-weight-engine' ) );
			}
			$result['width_mm']  = $width;
			$result['kg_per_m2'] = round( $dims['t'] * $density / 1000, 3 );
			$per_piece           = $dims['t'] * $width * $length * $density / 1e9;
		} else {
			$area                = $section ? $section->area_mm2 : Geometry::area( $fam->shape, $dims );
			$result['area_mm2']  = round( $area, 1 );
			$result['mass_kg_m'] = round( $area * $density / 1e6, 3 );
			if ( $section && null !== $section->mass_published ) {
				$result['mass_published'] = $section->mass_published;
			}
			$per_piece = $area * $density / 1e6 * $length / 1000;
		}
		$result['per_piece_kg'] = round( $per_piece, 3 );

		// 3. Quantity.
		if ( 'bars' === $mode ) {
			$bars = (int) self::number( $in['bars'] ?? 1 );
			if ( $bars < 1 || $bars > $s['max_bars'] ) {
				return self::error( 'smw_bars', __( 'Enter a whole number of bars (1 or more).', 'sm-weight-engine' ) );
			}
			$result['bars']     = $bars;
			$result['total_kg'] = round( $per_piece * $bars, 3 );
		} else {
			$total = self::number( $in['total_kg'] ?? 0 );
			if ( $total <= 0 || $total > $s['max_total_kg'] ) {
				return self::error( 'smw_total', __( 'Enter the total weight in kg.', 'sm-weight-engine' ) );
			}
			$exact                   = $total / $per_piece;
			$full                    = (int) floor( $exact + 1e-9 );
			$result['total_kg']      = round( $total, 3 );
			$result['bars_exact']    = round( $exact, 2 );
			$result['bars_full']     = $full;
			$result['bars_full_kg']  = round( $full * $per_piece, 3 );
			$result['remainder_kg']  = round( $total - $full * $per_piece, 3 );
			$result['bars_next']     = $full + 1;
			$result['bars_next_kg']  = round( ( $full + 1 ) * $per_piece, 3 );
		}
		return $result;
	}

	/** Dimensions typed for a formula family, checked against the shape and the limits. */
	public static function read_dims( $shape, array $raw ) {
		$s    = Settings::all();
		$dims = array();
		foreach ( Geometry::SHAPES[ $shape ] as $k ) {
			$v = self::number( $raw[ $k ] ?? 0 );
			$is_thickness = in_array( $k, array( 't', 'tw', 'tf' ), true );
			if ( $v <= 0 || $v > ( $is_thickness ? $s['max_thickness'] : $s['max_dimension'] ) ) {
				return self::error( 'smw_dims', __( 'Check the dimensions (in mm).', 'sm-weight-engine' ) );
			}
			$dims[ $k ] = $v;
		}
		if ( true !== Geometry::validate( $shape, $dims ) ) {
			return self::error( 'smw_dims', __( 'The thickness is too large for this size.', 'sm-weight-engine' ) );
		}
		return $dims;
	}

	/** Designation of a typed size: "RHS 120x80x4", "Fer plat 100x10", "Tôle 8 mm". */
	public static function designation( $fam, array $d ) {
		$f = static fn( $v ) => self::fmt( $v );
		switch ( $fam->shape ) {
			case 'rhs':
				return sprintf( '%s %sx%sx%s', $fam->code, $f( $d['h'] ), $f( $d['b'] ), $f( $d['t'] ) );
			case 'chs':
				return sprintf( 'CHS %sx%s', $f( $d['d'] ), $f( $d['t'] ) );
			case 'flat':
				return sprintf( 'FLAT %sx%s', $f( $d['b'] ), $f( $d['t'] ) );
			case 'round':
				return sprintf( 'ROUND %s', $f( $d['d'] ) );
			case 'square':
				return sprintf( 'SQUARE %s', $f( $d['a'] ) );
			case 'plate':
				return sprintf( 'PLATE %s', $f( $d['t'] ) );
		}
		return $fam->code;
	}

	/**
	 * Dimensions of a typed size from its designation ("RHS 120x80x4" -> h, b, t),
	 * the reverse of designation(). Null when it cannot be read.
	 */
	public static function dims_from_designation( $fam, $designation ) {
		if ( ! preg_match_all( '/\d+(?:[.,]\d+)?/', (string) $designation, $m ) ) {
			return null;
		}
		$n    = array_map( static fn( $v ) => (float) str_replace( ',', '.', $v ), $m[0] );
		$keys = array( 'rhs' => array( 'h', 'b', 't' ), 'chs' => array( 'd', 't' ), 'flat' => array( 'b', 't' ), 'round' => array( 'd' ), 'square' => array( 'a' ), 'plate' => array( 't' ) )[ $fam->shape ] ?? null;
		if ( ! $keys || count( $n ) < count( $keys ) ) {
			return null;
		}
		$raw = array_combine( $keys, array_slice( $n, -count( $keys ) ) );
		if ( 'rhs' === $fam->shape && 'SHS' === $fam->code && count( $n ) >= 3 ) {
			$raw = array( 'h' => $n[ count( $n ) - 3 ], 'b' => $n[ count( $n ) - 2 ], 't' => $n[ count( $n ) - 1 ] );
		}
		$dims = self::read_dims( $fam->shape, $raw );
		return is_wp_error( $dims ) ? null : $dims;
	}

	/**
	 * Section sheet of a listed product (0.2.0): shape, exact dimensions, area,
	 * mass per metre and standard, from the catalogue (section id) or from the
	 * designation of a typed size. Null when unknown.
	 */
	public static function sheet( $family, $section_id, $designation ) {
		$fam = Catalogue::family( $family );
		if ( ! $fam ) {
			return null;
		}
		$in = array( 'family' => $family, 'mode' => 'bars', 'bars' => 1, 'length' => 1000 );
		if ( 'PLATE' === $family ) {
			$in['width']  = 1000;
			$in['length'] = 1000;
		}
		if ( $section_id ) {
			$in['section'] = (int) $section_id;
		} else {
			$dims = self::dims_from_designation( $fam, $designation );
			if ( ! $dims ) {
				return null;
			}
			$in['dims'] = $dims;
		}
		$r = self::calculate( $in );
		if ( is_wp_error( $r ) ) {
			return null;
		}
		return array(
			'shape'       => $r['shape'],
			'designation' => $r['designation'],
			'dims'        => $r['dims'],
			'area_mm2'    => (float) ( $r['area_mm2'] ?? 0 ),
			'mass_kg_m'   => 'PLATE' === $family ? null : (float) $r['mass_kg_m'],
			'mass_kg_m2'  => 'PLATE' === $family ? (float) $r['total_kg'] : null,
			'standard'    => (string) ( $r['standard'] ?? '' ),
		);
	}

	/** Accepts "12,5" as well as "12.5". */
	public static function number( $v ) {
		return is_numeric( $v ) ? (float) $v : ( is_string( $v ) && is_numeric( str_replace( array( ',', ' ' ), array( '.', '' ), $v ) ) ? (float) str_replace( array( ',', ' ' ), array( '.', '' ), $v ) : 0.0 );
	}

	public static function fmt( $v ) {
		return rtrim( rtrim( number_format( (float) $v, 3, '.', '' ), '0' ), '.' );
	}

	private static function error( $code, $message ) {
		return new \WP_Error( $code, $message, array( 'status' => 400 ) );
	}
}
