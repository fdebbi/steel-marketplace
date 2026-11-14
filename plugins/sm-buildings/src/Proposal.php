<?php
/**
 * Elements proposed from the declared dimensions (0.3.0), to make the inventory
 * of a column / rafter frame quicker to fill. Rules given by the platform owner;
 * every figure is a setting (SM → Buildings) and every proposed row can be
 * changed by the seller.
 *
 * - Columns: IPE of height = width (m) × 20; with an overhead crane, HEA of
 *   height = width × 18. The nearest catalogue size is taken; beyond the largest
 *   size the dimension is left empty (the seller chooses). 2 per frame, as long
 *   as the height at the column.
 * - Rafters: IPE of height = width × 20. Two slopes: 2 per frame, width / 2
 *   long; one slope: 1 per frame, as long as the width. Other roof shapes: none.
 * - Purlins: IPE 120, ceil( width / 1.2 ) rows × ( frames − 1 ), as long as the
 *   spacing of the frames (length / ( frames − 1 )).
 * - Cladding rails: UPN 120, 2 × ceil( column height / 1.2 ) rows × ( frames − 1 ),
 *   same length; none without cladding.
 * - Bracing: 32 angles L 80x80x8 of 6 m.
 *
 * Only for the column / rafter frame system: the other systems are typed by the
 * seller. The proposal follows the dimensions as long as the seller has not
 * changed a row; afterwards it is replaced only when he asks for it.
 *
 * @package SM\Buildings
 */

namespace SM\Buildings;

defined( 'ABSPATH' ) || exit;

final class Proposal {

	const MANUAL = 'manual';

	public static function init() {
		add_filter( 'sm_products_form_state', array( __CLASS__, 'state' ), 10, 2 );
		add_filter( 'sm_products_form_values', array( __CLASS__, 'form_values' ), 20, 2 );
	}

	/** An existing listing is never recalculated on its own. */
	public static function form_values( $v, $p ) {
		if ( Market::CODE === ( $p->market ?? '' ) ) {
			$v['lines_auto'] = self::MANUAL;
		}
		return $v;
	}

	/** Rows that hold something, in the shape of the form. */
	private static function filled( $lines ) {
		$out = array();
		foreach ( (array) $lines as $r ) {
			if ( ! is_array( $r ) || '' === trim( (string) ( $r['section'] ?? '' ) ) . trim( (string) ( $r['qty'] ?? '' ) ) . trim( (string) ( $r['length'] ?? '' ) ) ) {
				continue;
			}
			$out[] = array(
				'element' => sanitize_key( (string) ( $r['element'] ?? '' ) ),
				'family'  => strtoupper( trim( (string) ( $r['family'] ?? '' ) ) ),
				'section' => trim( (string) ( $r['section'] ?? '' ) ),
				'qty'     => trim( (string) ( $r['qty'] ?? '' ) ),
				'length'  => trim( (string) ( $r['length'] ?? '' ) ),
			);
		}
		return $out;
	}

	/** Signature of a set of rows (lengths compared as numbers: "6" = "6,0"). */
	public static function signature( array $rows ) {
		$parts = array();
		foreach ( self::filled( $rows ) as $r ) {
			$parts[] = implode( '|', array( $r['element'], $r['family'], $r['section'], (string) (int) \SM\Products\Spec::num( $r['qty'] ), (string) round( \SM\Products\Spec::num( $r['length'] ), 3 ) ) );
		}
		return md5( implode( ';', $parts ) );
	}

	/**
	 * State of the publication form before it is shown: proposes the rows, keeps
	 * them in step with the dimensions while untouched, replaces them on request.
	 * Adds `lines_auto` (signature of the proposal, or "manual") and
	 * `lines_proposal` ("synced" | "available" | "") for the form.
	 */
	public static function state( $st, $cat ) {
		if ( Market::KIND !== ( is_object( $cat ) ? ( $cat->kind ?? '' ) : ( $cat['kind'] ?? '' ) ) ) {
			return $st;
		}
		$st     = (array) $st;
		$lines  = self::filled( $st['lines'] ?? array() );
		$auto   = (string) ( $st['lines_auto'] ?? '' );
		$recalc = ! empty( $st['lines_recalc'] );
		unset( $st['lines_recalc'] );
		$prop   = self::rows( $st );
		$synced = '' !== $auto && self::MANUAL !== $auto && self::signature( $lines ) === $auto;

		if ( null !== $prop && ( $recalc || $synced || ( '' === $auto && ! $lines ) ) ) {
			$st['lines']          = $prop;
			$st['lines_auto']     = self::signature( $prop );
			$st['lines_proposal'] = 'synced';
			return $st;
		}
		if ( null === $prop && $synced && 'portal' !== sanitize_key( (string) ( $st['attrs']['system'] ?? '' ) ) ) {
			// Untouched proposal and another structural system: nothing is proposed for it.
			$st['lines']          = array();
			$st['lines_auto']     = '';
			$st['lines_proposal'] = '';
			return $st;
		}
		if ( '' === $auto && $lines ) {
			$st['lines_auto'] = self::MANUAL; // Typed by the seller before any proposal.
		}
		$st['lines_proposal'] = $synced ? 'synced' : ( null !== $prop ? 'available' : '' );
		return $st;
	}

	/** Catalogue size of a family nearest to a height in mm ('' beyond the largest size). */
	public static function nearest( $family, $target ) {
		$engine = \SM\Core\Modules::service( 'weight' );
		if ( ! $engine || $target <= 0 ) {
			return '';
		}
		$best = '';
		$gap  = null;
		$max  = 0;
		foreach ( (array) $engine->sections( $family ) as $s ) {
			if ( ! preg_match( '/(\d+(?:[.,]\d+)?)/', (string) $s['designation'], $m ) ) {
				continue;
			}
			$n   = (float) str_replace( ',', '.', $m[1] );
			$max = max( $max, $n );
			$g   = abs( $n - $target );
			if ( null === $gap || $g < $gap - 1e-9 || ( abs( $g - $gap ) < 1e-9 && $n > $target ) ) {
				$gap  = $g;
				$best = (string) $s['id'];
			}
		}
		return $target > $max + 1e-9 ? '' : $best;
	}

	/** Section id of a catalogue designation such as "IPE 120" or "L 80x80x8" ('' if unknown). */
	public static function by_name( $name ) {
		$engine = \SM\Core\Modules::service( 'weight' );
		$family = strtoupper( (string) strtok( trim( (string) $name ), ' ' ) );
		if ( ! $engine || '' === $family ) {
			return array( '', '' );
		}
		$norm = static fn( $s ) => strtoupper( preg_replace( '/[\s×*]+/u', '', str_replace( 'X', 'x', (string) $s ) ) );
		foreach ( (array) $engine->sections( $family ) as $s ) {
			if ( $norm( $s['designation'] ) === $norm( $name ) ) {
				return array( $family, (string) $s['id'] );
			}
		}
		return array( $family, '' );
	}

	/**
	 * Proposed rows for the typed state, or null when nothing can be proposed
	 * (another structural system, dimensions or envelope not given yet).
	 *
	 * @return array[]|null Rows {element, family, section, qty, length}.
	 */
	public static function rows( array $st ) {
		$num    = static fn( $v ) => \SM\Products\Spec::num( $v );
		$specs  = (array) ( $st['specs'] ?? array() );
		$attrs  = (array) ( $st['attrs'] ?? array() );
		$roof   = sanitize_key( (string) ( $attrs['roof'] ?? '' ) );
		$clad   = sanitize_key( (string) ( $attrs['cladding'] ?? '' ) );
		$w      = $num( $specs['width_m'] ?? '' );
		$l      = $num( $specs['length_m'] ?? '' );
		$h      = $num( $specs['height_m'] ?? '' );
		$n      = $num( $specs['portals'] ?? '' );
		if ( 'portal' !== sanitize_key( (string) ( $attrs['system'] ?? '' ) ) || '' === $roof || '' === $clad || $w <= 0 || $l <= 0 || $h <= 0 || $n < 2 || floor( $n ) !== $n ) {
			return null;
		}
		$s     = Settings::all();
		$n     = (int) $n;
		$bays  = $n - 1;
		$bay   = self::len( $l / $bays );
		$crane = ! empty( $specs['crane'] );
		$rows  = array();
		$add   = static function ( $element, $family, $section, $qty, $length ) use ( &$rows ) {
			if ( $qty > 0 && '' !== $length ) {
				$rows[] = array( 'element' => $element, 'family' => $family, 'section' => (string) $section, 'qty' => (string) (int) $qty, 'length' => $length );
			}
		};
		$col_fam = strtoupper( (string) ( $crane ? $s['prop_crane_family'] : $s['prop_col_family'] ) );
		$add( 'column', $col_fam, self::nearest( $col_fam, $w * (float) ( $crane ? $s['prop_crane_factor'] : $s['prop_col_factor'] ) ), 2 * $n, self::len( $h ) );
		if ( in_array( $roof, array( 'duo', 'mono' ), true ) ) {
			$raf_fam = strtoupper( (string) $s['prop_rafter_family'] );
			$add( 'rafter', $raf_fam, self::nearest( $raf_fam, $w * (float) $s['prop_rafter_factor'] ), 'duo' === $roof ? 2 * $n : $n, self::len( 'duo' === $roof ? $w / 2 : $w ) );
		}
		list( $fam, $sec ) = self::by_name( $s['prop_purlin'] );
		$add( 'purlin', $fam, $sec, (int) ceil( $w / (float) $s['prop_purlin_step'] - 1e-9 ) * $bays, $bay );
		if ( 'none' !== $clad ) {
			list( $fam, $sec ) = self::by_name( $s['prop_rail'] );
			$add( 'rail', $fam, $sec, 2 * (int) ceil( $h / (float) $s['prop_rail_step'] - 1e-9 ) * $bays, $bay );
		}
		list( $fam, $sec ) = self::by_name( $s['prop_brace'] );
		$add( 'bracing', $fam, $sec, (int) $s['prop_brace_qty'], self::len( (float) $s['prop_brace_len'] ) );
		return $rows;
	}

	/** Length in metres as typed in the form: 6 → "6", 6.666… → "6.67". */
	private static function len( $v ) {
		return rtrim( rtrim( number_format( round( (float) $v, 2 ), 2, '.', '' ), '0' ), '.' );
	}
}
