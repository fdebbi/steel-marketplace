<?php
/**
 * Technical part of a building listing ("building" kind of SM Products).
 *
 * Input: mode (assembled|dismantled), specs[length_m|width_m|height_m|portals|slope],
 * lines[i][element|family|section|qty|length], total_kg + weight_source (estimated
 * steel weight of an assembled building without inventory).
 *
 * Dismantled: the inventory is required; every row is weighed by SM Weight Engine.
 * Assembled: the inventory is optional; without it the seller gives an estimated
 * steel weight, shown as estimated. Area = length × width.
 *
 * @package SM\Buildings
 */

namespace SM\Buildings;

defined( 'ABSPATH' ) || exit;

final class Spec {

	const MODES = array( 'assembled', 'dismantled' );

	public static function init() {
		add_action( 'sm_products_spec_kind_' . Market::KIND, array( __CLASS__, 'build' ), 10, 5 );
	}

	/** @param object $cat Category. @param array $in Input. @param array $d Data. @param array $e Errors. */
	public static function build( $cat, $in, &$d, &$e, $for_save ) {
		$s    = Settings::all();
		$mode = sanitize_key( (string) ( $in['mode'] ?? '' ) );
		if ( ! in_array( $mode, self::MODES, true ) ) {
			$e['mode'] = __( 'Say whether the building is assembled or dismantled.', 'sm-buildings' );
			$mode      = '';
		}
		$d['variant'] = $mode;

		// Dimensions.
		$specs = array();
		$dims  = array(
			'length_m' => array( $s['max_length_m'], __( 'Enter the length in metres.', 'sm-buildings' ) ),
			'width_m'  => array( $s['max_length_m'], __( 'Enter the width in metres.', 'sm-buildings' ) ),
			'height_m' => array( $s['max_height_m'], __( 'Enter the eaves height in metres.', 'sm-buildings' ) ),
		);
		foreach ( $dims as $k => list( $max, $msg ) ) {
			$v = \SM\Products\Spec::num( $in['specs'][ $k ] ?? '' );
			if ( $v <= 0 ) {
				$e[ 'specs_' . $k ] = $msg;
			} elseif ( $v > $max ) {
				/* translators: %s: maximum in metres. */
				$e[ 'specs_' . $k ] = sprintf( __( 'At most %s m.', 'sm-buildings' ), self::fmt( $max ) );
			} else {
				$specs[ $k ] = round( $v, 2 );
			}
		}
		$portals = \SM\Products\Spec::num( $in['specs']['portals'] ?? '' );
		if ( $portals < 2 || floor( $portals ) !== $portals ) {
			$e['specs_portals'] = __( 'Enter the number of portal frames (2 or more).', 'sm-buildings' );
		} elseif ( $portals > $s['max_portals'] ) {
			$e['specs_portals'] = __( 'This number of portal frames is outside the accepted range.', 'sm-buildings' );
		} else {
			$specs['portals'] = (int) $portals;
		}
		$slope = trim( (string) ( $in['specs']['slope'] ?? '' ) );
		if ( '' !== $slope ) {
			$sv = \SM\Products\Spec::num( $slope );
			if ( $sv <= 0 || $sv > 100 ) {
				$e['specs_slope'] = __( 'Roof slope in % (between 1 and 100).', 'sm-buildings' );
			} else {
				$specs['slope'] = round( $sv, 1 );
			}
		}
		$d['specs'] = $specs;
		$area       = isset( $specs['length_m'], $specs['width_m'] ) ? round( $specs['length_m'] * $specs['width_m'], 2 ) : 0;
		$d['quantity'] = $area;
		$d['unit']     = 'm2';

		// Inventory of structural elements, weighed by SM Weight Engine.
		$rows = array_filter(
			(array) ( $in['lines'] ?? array() ),
			static fn( $r ) => is_array( $r ) && implode( '', array_map( 'strval', array_intersect_key( $r, array_flip( array( 'section', 'qty', 'length' ) ) ) ) ) !== ''
		);
		if ( 'dismantled' === $mode || $rows ) {
			\SM\Products\Spec::lines( $cat, $in, $d, $e );
			if ( 'dismantled' === $mode && isset( $e['lines'] ) ) {
				$e['lines'] = __( 'A dismantled building is sold with its inventory: add at least one element (type, profile, quantity, length).', 'sm-buildings' );
			}
		} else {
			// Assembled, no inventory: estimated steel weight given by the seller.
			$w = \SM\Products\Spec::num( $in['total_kg'] ?? '' );
			if ( $w <= 0 ) {
				$e['total_kg'] = __( 'Give the estimated weight of the steel structure, or list its elements below.', 'sm-buildings' );
			} elseif ( $w > (float) sm_weight_setting( 'max_total_kg', 10000000 ) ) {
				$e['total_kg'] = __( 'This weight is outside the accepted range.', 'sm-buildings' );
			}
			$d['total_weight']  = $w;
			$d['weight_source'] = 'actual' === ( $in['weight_source'] ?? '' ) ? 'actual' : 'estimated';
			$d['lines']         = array();
			$d['pieces']        = 0;
		}
		$d['qty_mode']    = 'building';
		if ( 'dismantled' === $mode && in_array( 'kg', $cat->units, true ) ) {
			$d['price_unit'] = 'kg'; // Default for a dismantled building; the seller may choose the whole-building price.
		}
		$d['length_mm']   = isset( $specs['length_m'] ) ? (int) round( $specs['length_m'] * 1000 ) : 0;
		$d['width_mm']    = isset( $specs['width_m'] ) ? (int) round( $specs['width_m'] * 1000 ) : 0;
		$d['designation'] = isset( $specs['length_m'], $specs['width_m'] ) ? self::fmt( $specs['length_m'] ) . ' × ' . self::fmt( $specs['width_m'] ) . ' m' : '';
	}

	/** 12.5 → "12,5", 40 → "40". */
	public static function fmt( $v ) {
		return rtrim( rtrim( number_format( (float) $v, 2, ',', '' ), '0' ), ',' );
	}
}
