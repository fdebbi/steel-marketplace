<?php
/**
 * Kinds of plates (0.3.0): black, cold rolled, galvanized, tread (larmée),
 * perforated, stainless, aluminium checker plate, other. See data/plates.php.
 *
 * @package SM\WeightEngine
 */

namespace SM\WeightEngine;

defined( 'ABSPATH' ) || exit;

final class Plates {

	private static $data = null;

	public static function data() {
		if ( null === self::$data ) {
			self::$data = require SM_WEIGHT_DIR . '/data/plates.php';
		}
		return self::$data;
	}

	/** kind => definition. */
	public static function kinds() {
		return self::data()['kinds'];
	}

	public static function kind( $key ) {
		$k = self::kinds();
		return isset( $k[ $key ] ) ? $k[ $key ] : null;
	}

	/** Formats [ width, length ] in mm. */
	public static function formats() {
		return self::data()['formats'];
	}

	/** Key of a thickness in a table ("5.5"). */
	public static function key( $t ) {
		return rtrim( rtrim( number_format( (float) $t, 2, '.', '' ), '0' ), '.' );
	}

	/** Thickness label: "3/5" for a plate with a relief, else the number. */
	public static function label( $kind, $t ) {
		$def = self::kind( $kind );
		$key = self::key( $t );
		if ( $def && isset( $def['table'][ $key ] ) ) {
			return $def['table'][ $key ][0];
		}
		return str_replace( '.', ',', $key );
	}

	/** Thicknesses offered for a kind: [ { value, label } ]. */
	public static function thicknesses( $kind ) {
		$def = self::kind( $kind );
		if ( ! $def ) {
			return array();
		}
		if ( ! empty( $def['table'] ) ) {
			$out = array();
			foreach ( $def['table'] as $k => $row ) {
				$out[] = array( 'value' => (float) $k, 'label' => $row[0] );
			}
			return $out;
		}
		return array_map( static fn( $t ) => array( 'value' => (float) $t, 'label' => self::label( $kind, $t ) ), (array) $def['thicknesses'] );
	}

	/** Share of the surface taken by round holes in a 60° staggered pattern (0 to 0.9). */
	public static function open_ratio( $d, $p ) {
		$d = (float) $d;
		$p = (float) $p;
		if ( $d <= 0 || $p <= $d ) {
			return 0.0;
		}
		return min( 0.9, M_PI / ( 2 * sqrt( 3 ) ) * ( $d / $p ) ** 2 );
	}

	/**
	 * Weight of one m² of the plate.
	 *
	 * @param string     $kind    Kind ('' = plain steel with the density setting).
	 * @param float      $t       Thickness (base thickness for a plate with a relief) in mm.
	 * @param array|null $holes   [ d, p ] in mm for a perforated plate.
	 * @param float      $density Density setting (kg/m³) for plain steel.
	 * @return array { kg_m2, basis: table|density, open: ratio, label }
	 */
	public static function per_m2( $kind, $t, $holes, $density ) {
		$def   = self::kind( $kind );
		$key   = self::key( $t );
		$open  = 0.0;
		$basis = 'density';
		if ( $def && isset( $def['table'][ $key ] ) ) {
			$kg    = (float) $def['table'][ $key ][1];
			$basis = 'table';
		} else {
			$rho = $def && 7850 !== (int) $def['density'] ? (float) $def['density'] : (float) $density;
			$kg  = $t * $rho / 1000;
			if ( $def && ! empty( $def['holes'] ) && $holes ) {
				$open = self::open_ratio( $holes['d'] ?? 0, $holes['p'] ?? 0 );
				$kg  *= 1 - $open;
			}
		}
		return array(
			'kg_m2' => round( $kg, 3 ),
			'basis' => $basis,
			'open'  => round( $open, 3 ),
			'label' => self::label( $kind, $t ),
		);
	}

	/** Kinds for the theme: { key, name, hint, title, relief, holes, thicknesses }. */
	public static function present() {
		$out = array();
		foreach ( self::kinds() as $k => $def ) {
			$out[] = array(
				'key'         => $k,
				'name'        => $def['name'],
				'hint'        => $def['hint'],
				'title'       => $def['title'],
				'relief'      => ! empty( $def['relief'] ),
				'holes'       => ! empty( $def['holes'] ),
				'thicknesses' => self::thicknesses( $k ),
			);
		}
		return $out;
	}
}
