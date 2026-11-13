<?php
/**
 * Technical part of a building listing ("building" kind of SM Products).
 *
 * Input: mode (assembled|dismantled),
 * specs[length_m|width_m|height_m|ridge_m|portals|floors|mezzanine|mezz_area],
 * lines[i][element|family|section|qty|length], total_kg (total steel weight, optional).
 *
 * 0.2.0
 * - Geometry: eaves height and ridge height (the roof slope is calculated from
 *   them and the roof shape, never typed), number of floors (1 by default),
 *   mezzanine (yes / no, optional area).
 * - Elements: the columns and the rafters (or the trusses) are required when the
 *   structural system is made of catalogue sections (column / rafter frame,
 *   truss). A welded built-up structure (PRS) or "other" has no catalogue size:
 *   its elements are optional and the total steel weight is then required.
 * - Weight: the weight of the declared elements is calculated by SM Weight
 *   Engine (source "partial": it is not the whole structure). The seller may
 *   add the total weight he knows (source "estimated"); it cannot be lower than
 *   the declared elements.
 * - Title: generated (type, size, height, system, wilaya), in French and Arabic.
 *
 * @package SM\Buildings
 */

namespace SM\Buildings;

defined( 'ABSPATH' ) || exit;

final class Spec {

	const MODES = array( 'assembled', 'dismantled' );

	public static function init() {
		add_action( 'sm_products_spec_kind_' . Market::KIND, array( __CLASS__, 'build' ), 10, 5 );
		add_filter( 'sm_products_generated_title', array( __CLASS__, 'title' ), 10, 3 );
		add_filter( 'sm_products_location_required', static fn( $need, $cat ) => $need || Market::KIND === ( $cat->kind ?? '' ), 10, 2 );
		add_filter( 'sm_products_visits', static fn( $ok, $p ) => $ok || Market::CODE === ( $p->market ?? '' ), 10, 2 );
		add_filter( 'sm_products_form_values', array( __CLASS__, 'form_values' ), 10, 2 );
	}

	/** Element types required for a structural system ([] = none: no catalogue size). */
	public static function required_elements( $system ) {
		if ( 'portal' === $system ) {
			return array( 'column', 'rafter' );
		}
		if ( 'truss' === $system ) {
			return array( 'column', 'truss' );
		}
		return array();
	}

	/** Roof slope in % from the two heights; 0 when the roof shape gives no single slope. */
	public static function slope( $width, $eaves, $ridge, $roof ) {
		$rise = $ridge - $eaves;
		if ( $width <= 0 || $rise <= 0 ) {
			return 0.0;
		}
		if ( 'mono' === $roof ) {
			return round( $rise / $width * 100, 1 );
		}
		if ( in_array( $roof, array( 'duo', 'curved' ), true ) ) {
			return round( $rise / ( $width / 2 ) * 100, 1 );
		}
		return 0.0; // Multi-span, other: the number of spans is not known.
	}

	/** @param object $cat Category. @param array $in Input. @param array $d Data. @param array $e Errors. */
	public static function build( $cat, $in, &$d, &$e, $for_save ) {
		$s    = Settings::all();
		$num  = static fn( $v ) => \SM\Products\Spec::num( $v );
		$mode = sanitize_key( (string) ( $in['mode'] ?? '' ) );
		if ( ! in_array( $mode, self::MODES, true ) ) {
			if ( $for_save ) {
				$e['mode'] = __( 'Say whether the building is assembled or dismantled.', 'sm-buildings' );
			}
			$mode = '';
		}
		$d['variant'] = $mode;

		// Dimensions.
		$specs = array();
		$dims  = array(
			'length_m' => array( $s['max_length_m'], __( 'Enter the length in metres.', 'sm-buildings' ) ),
			'width_m'  => array( $s['max_length_m'], __( 'Enter the width in metres.', 'sm-buildings' ) ),
			'height_m' => array( $s['max_height_m'], __( 'Enter the height at the column in metres.', 'sm-buildings' ) ),
			'ridge_m'  => array( $s['max_height_m'] * 1.5, __( 'Enter the height of the roof (at the ridge) in metres.', 'sm-buildings' ) ),
		);
		foreach ( $dims as $k => list( $max, $msg ) ) {
			$v = $num( $in['specs'][ $k ] ?? '' );
			if ( $v <= 0 ) {
				$e[ 'specs_' . $k ] = $msg;
			} elseif ( $v > $max ) {
				/* translators: %s: maximum in metres. */
				$e[ 'specs_' . $k ] = sprintf( __( 'At most %s m.', 'sm-buildings' ), self::fmt( $max ) );
			} else {
				$specs[ $k ] = round( $v, 2 );
			}
		}
		if ( isset( $specs['height_m'], $specs['ridge_m'] ) && $specs['ridge_m'] < $specs['height_m'] ) {
			$e['specs_ridge_m'] = __( 'The height of the roof cannot be lower than the height at the column.', 'sm-buildings' );
			unset( $specs['ridge_m'] );
		}
		$portals = $num( $in['specs']['portals'] ?? '' );
		if ( $portals < 2 || floor( $portals ) !== $portals ) {
			$e['specs_portals'] = __( 'Enter the number of portal frames (2 or more).', 'sm-buildings' );
		} elseif ( $portals > $s['max_portals'] ) {
			$e['specs_portals'] = __( 'This number of portal frames is outside the accepted range.', 'sm-buildings' );
		} else {
			$specs['portals'] = (int) $portals;
		}
		// Floors: 1 (ground floor only) unless the seller says more.
		$floors_in = trim( (string) ( $in['specs']['floors'] ?? '' ) );
		$floors    = '' === $floors_in ? 1.0 : $num( $floors_in );
		if ( $floors < 1 || floor( $floors ) !== $floors || $floors > (int) $s['max_floors'] ) {
			/* translators: %d: maximum number of floors. */
			$e['specs_floors'] = sprintf( __( 'Number of floors: a whole number from 1 to %d.', 'sm-buildings' ), (int) $s['max_floors'] );
		} else {
			$specs['floors'] = (int) $floors;
		}
		$area = isset( $specs['length_m'], $specs['width_m'] ) ? round( $specs['length_m'] * $specs['width_m'], 2 ) : 0;
		if ( ! empty( $in['specs']['mezzanine'] ) ) {
			$specs['mezzanine'] = 1;
			$ma                 = trim( (string) ( $in['specs']['mezz_area'] ?? '' ) );
			if ( '' !== $ma ) {
				$mv = $num( $ma );
				if ( $mv <= 0 || ( $area > 0 && $mv > $area ) ) {
					$e['specs_mezz_area'] = __( 'Area of the mezzanine in m²: more than 0 and at most the floor area.', 'sm-buildings' );
				} else {
					$specs['mezz_area'] = round( $mv, 1 );
				}
			}
		}
		// Slope: calculated; listings of before 0.2.0 keep the slope that was typed.
		if ( isset( $specs['width_m'], $specs['height_m'], $specs['ridge_m'] ) ) {
			$slope = self::slope( $specs['width_m'], $specs['height_m'], $specs['ridge_m'], sanitize_key( (string) ( $in['attrs']['roof'] ?? '' ) ) );
			if ( $slope > 0 ) {
				$specs['slope'] = $slope;
			}
		}
		if ( isset( $specs['length_m'], $specs['portals'] ) && $specs['portals'] > 1 ) {
			$specs['bay_m'] = round( $specs['length_m'] / ( $specs['portals'] - 1 ), 2 ); // Spacing of the frames.
		}
		$d['specs']    = $specs;
		$d['quantity'] = $area;
		$d['unit']     = 'm2';

		// Elements of the structure, weighed by SM Weight Engine.
		$system = sanitize_key( (string) ( $in['attrs']['system'] ?? '' ) );
		$need   = self::required_elements( $system );
		$rows   = array_filter(
			(array) ( $in['lines'] ?? array() ),
			static fn( $r ) => is_array( $r ) && implode( '', array_map( 'strval', array_intersect_key( $r, array_flip( array( 'section', 'qty', 'length' ) ) ) ) ) !== ''
		);
		$elements_kg = 0.0;
		if ( $rows ) {
			\SM\Products\Spec::lines( $cat, array_diff_key( $in, array( 'weight_source' => 1, 'actual_weight' => 1 ) ), $d, $e );
			$elements_kg = (float) ( $d['total_weight'] ?? 0 );
		} else {
			$d['lines']  = array();
			$d['pieces'] = 0;
		}
		$have    = array_unique( array_filter( array_map( static fn( $l ) => (string) ( $l['element'] ?? '' ), (array) ( $d['lines'] ?? array() ) ) ) );
		$missing = array_diff( $need, $have );
		if ( $missing && ! array_filter( array_keys( $e ), static fn( $k ) => 0 === strpos( $k, 'lines_' ) ) ) {
			$e['lines'] = 'truss' === $system
				? __( 'Describe at least the columns and the trusses (profile, number, length).', 'sm-buildings' )
				: __( 'Describe at least the columns and the rafters (profile, number, length).', 'sm-buildings' );
		} else {
			unset( $e['lines'] ); // "At least one line" of SM Products does not apply here.
		}

		// Total steel weight known by the seller (optional when elements are declared).
		$total = $num( $in['total_kg'] ?? '' );
		if ( $total > 0 ) {
			if ( $total > (float) sm_weight_setting( 'max_total_kg', 10000000 ) ) {
				$e['total_kg'] = __( 'This weight is outside the accepted range.', 'sm-buildings' );
			} elseif ( $total < $elements_kg ) {
				$e['total_kg'] = __( 'The total weight cannot be lower than the weight of the elements you declared.', 'sm-buildings' );
			}
			$d['total_weight']  = round( $total, 3 );
			$d['weight_source'] = 'estimated';
		} elseif ( $elements_kg > 0 ) {
			$d['total_weight']  = round( $elements_kg, 3 );
			$d['weight_source'] = 'partial'; // Declared elements only, not the whole structure.
		} else {
			if ( ! $need ) {
				$e['total_kg'] = __( 'Give the total weight of the steel structure, or list its elements.', 'sm-buildings' );
			}
			$d['total_weight']  = 0;
			$d['weight_source'] = 'estimated';
		}
		$d['specs']['elements_kg'] = round( $elements_kg, 1 );

		$d['qty_mode'] = 'building';
		if ( 'dismantled' === $mode && in_array( 'kg', $cat->units, true ) ) {
			$d['price_unit'] = 'kg'; // Default for a dismantled building; the seller may choose the whole-building price.
		}
		$d['length_mm']   = isset( $specs['length_m'] ) ? (int) round( $specs['length_m'] * 1000 ) : 0;
		$d['width_mm']    = isset( $specs['width_m'] ) ? (int) round( $specs['width_m'] * 1000 ) : 0;
		$d['designation'] = isset( $specs['length_m'], $specs['width_m'] ) ? self::fmt( $specs['length_m'] ) . ' × ' . self::fmt( $specs['width_m'] ) . ' m' : '';
	}

	/**
	 * Generated title: "Hangar 20 × 40 m — hauteur 6 m — Portique poteau / traverse — Sétif".
	 * Parts that are not known yet are left out.
	 */
	public static function title( $title, $cat, $d ) {
		if ( Market::KIND !== ( $cat->kind ?? '' ) ) {
			return $title;
		}
		$specs  = (array) ( $d['specs'] ?? array() );
		$out    = array();
		$system = (string) ( $d['attrs']['system'] ?? '' );
		$sys    = array();
		foreach ( Market::definition()['fields'] as $f ) {
			if ( 'system' === $f['key'] ) {
				foreach ( $f['options'] as $o ) {
					if ( $o['value'] === $system && 'other' !== $system ) {
						$sys = $o;
					}
				}
			}
		}
		foreach ( array( 'fr', 'ar' ) as $lang ) {
			$m     = 'ar' === $lang ? 'م' : 'm';
			$parts = array( (string) ( 'ar' === $lang ? ( $cat->name_ar ?: $cat->name_fr ) : $cat->name_fr ) );
			if ( isset( $specs['width_m'], $specs['length_m'] ) ) {
				$parts[0] .= ' ' . self::fmt( $specs['width_m'] ) . ' × ' . self::fmt( $specs['length_m'] ) . ' ' . $m;
			}
			if ( isset( $specs['height_m'] ) ) {
				$parts[] = ( 'ar' === $lang ? 'الارتفاع ' : 'hauteur ' ) . self::fmt( $specs['height_m'] ) . ' ' . $m;
			}
			if ( $sys ) {
				$parts[] = $sys[ $lang ];
			}
			$w = ! empty( $d['wilaya'] ) ? \SM\Core\Accounts\Wilayas::name( $d['wilaya'], $lang ) : '';
			if ( $w ) {
				$parts[] = $w;
			}
			$out[ $lang ] = implode( ' — ', $parts );
		}
		return $out;
	}

	/** Edit form: the total weight typed by the seller. */
	public static function form_values( $v, $p ) {
		if ( Market::CODE === ( $p->market ?? '' ) ) {
			$v['total_kg'] = 'estimated' === $p->weight_source && $p->total_weight > 0 ? (string) ( 0 + $p->total_weight ) : '';
		}
		return $v;
	}

	/** 12.5 → "12,5", 40 → "40". */
	public static function fmt( $v ) {
		return rtrim( rtrim( number_format( (float) $v, 2, ',', '' ), '0' ), ',' );
	}
}
