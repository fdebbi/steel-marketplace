<?php
/**
 * What the platform knows, given to the AI as context and used to check its
 * answers: markets, categories, profile families and designations of the
 * weight engine catalogue, grades, wilayas. Every value the AI proposes is
 * kept only if it exists here; weights always come from the weight engine.
 *
 * @package SM\Ai
 */

namespace SM\Ai;

use SM\Core\Accounts\Wilayas;

defined( 'ABSPATH' ) || exit;

final class Catalog {

	public static function has_products() {
		return class_exists( '\SM\Products\Categories' ) && class_exists( '\SM\Products\Markets' );
	}

	public static function has_engine() {
		return class_exists( '\SM\WeightEngine\Catalogue' );
	}

	/** market => [ label (fr), conditions[] ]. */
	public static function markets() {
		if ( ! self::has_products() ) {
			return array();
		}
		$out = array();
		foreach ( \SM\Products\Markets::all() as $k => $m ) {
			$out[ $k ] = array( 'label' => (string) ( $m['label']['fr'] ?? $k ), 'conditions' => array_map( 'strval', array_keys( (array) $m['conditions'] ) ) );
		}
		return $out;
	}

	/** slug => [ market, kind, name_fr, name_ar, families[] ] (active categories). */
	public static function categories( $market = null ) {
		if ( ! self::has_products() ) {
			return array();
		}
		$out = array();
		foreach ( \SM\Products\Categories::all( false, $market ) as $slug => $c ) {
			$out[ $slug ] = array( 'market' => $c->market, 'kind' => $c->kind, 'name_fr' => $c->name_fr, 'name_ar' => $c->name_ar, 'families' => array_values( (array) $c->families ) );
		}
		return $out;
	}

	/** Active profile families: code => name. */
	public static function families() {
		if ( ! self::has_engine() ) {
			return array();
		}
		$out = array();
		foreach ( \SM\WeightEngine\Catalogue::families() as $code => $f ) {
			$out[ $code ] = (string) ( $f->name_fr ?? $code );
		}
		return $out;
	}

	public static function grades() {
		$g = class_exists( '\SM\Products\Settings' ) ? (array) \SM\Products\Settings::get( 'grades' ) : array();
		return $g ? array_values( $g ) : array( 'S235JR', 'S275JR', 'S355JR', 'S355J2' );
	}

	/** Section of the catalogue matching a family and designation ("IPE 200", "200", "IPE200"), or null. */
	public static function section( $family, $designation ) {
		if ( ! self::has_engine() ) {
			return null;
		}
		$family = strtoupper( trim( (string) $family ) );
		if ( ! isset( self::families()[ $family ] ) ) {
			return null;
		}
		$d = trim( (string) $designation );
		$d = preg_replace( '/^' . preg_quote( $family, '/' ) . '\s*/i', '', $d );
		$s = \SM\WeightEngine\Catalogue::find( $family, $d );
		if ( ! $s ) {
			$s = \SM\WeightEngine\Catalogue::find( $family, $family . ' ' . $d );
		}
		return $s && 'active' === $s->status ? $s : null;
	}

	/** Designation as shown ("IPE 200"). */
	public static function label( $section ) {
		$d = (string) $section->designation;
		return 0 === stripos( $d, $section->family ) ? $d : $section->family . ' ' . $d;
	}

	/** Find "IPE 200", "HEA200"… in a text. Returns [family, section] or null. */
	public static function detect( $text ) {
		$fams = array_keys( self::families() );
		usort( $fams, static fn( $a, $b ) => strlen( $b ) - strlen( $a ) );
		foreach ( $fams as $f ) {
			if ( preg_match( '/\b' . preg_quote( $f, '/' ) . '\s*([0-9][0-9.x×*\s]*[0-9])/iu', (string) $text, $m ) ) {
				$s = self::section( $f, trim( $m[1] ) );
				if ( $s ) {
					return array( $f, $s );
				}
			}
		}
		return null;
	}

	/** Wilaya code from a code or a name in French or Arabic ('' when unknown). */
	public static function wilaya( $value ) {
		$v = trim( (string) $value );
		if ( '' === $v ) {
			return '';
		}
		$all = Wilayas::all();
		$num = ltrim( preg_replace( '/\D/', '', $v ), '0' );
		if ( '' !== $num && preg_match( '/^\d+$/', $v ) ) {
			foreach ( $all as $code => $w ) {
				if ( ltrim( (string) $code, '0' ) === $num ) {
					return (string) $code;
				}
			}
		}
		$norm = static fn( $s ) => remove_accents( mb_strtolower( preg_replace( '/[\s\'’-]+/u', '', (string) $s ) ) );
		foreach ( $all as $code => $w ) {
			$w = (array) $w;
			foreach ( array( $w['fr'] ?? '', $w['ar'] ?? '' ) as $n ) {
				if ( '' !== $n && $norm( $n ) === $norm( $v ) ) {
					return (string) $code;
				}
			}
		}
		return '';
	}

	/** Compact text given to the AI so it answers with codes the platform knows. */
	public static function context( array $what ) {
		$lines = array();
		if ( in_array( 'categories', $what, true ) ) {
			$lines[] = 'MARKETS AND CATEGORIES (use only these slugs):';
			foreach ( self::markets() as $mk => $m ) {
				$lines[] = '- market "' . $mk . '" (' . $m['label'] . ')' . ( $m['conditions'] ? ', conditions: ' . implode( ', ', $m['conditions'] ) : '' );
				foreach ( self::categories( $mk ) as $slug => $c ) {
					$lines[] = '  - category "' . $slug . '": ' . $c['name_fr'] . ( $c['families'] ? ' (profile families: ' . implode( ', ', $c['families'] ) . ')' : '' );
				}
			}
		}
		if ( in_array( 'families', $what, true ) ) {
			$lines[] = 'PROFILE FAMILIES OF THE CATALOGUE (codes): ' . implode( ', ', array_map( static fn( $k, $v ) => $k . ' = ' . $v, array_keys( self::families() ), self::families() ) );
			$lines[] = 'Designations are written like "IPE 200", "HEA 240", "UPN 120", "L 50x5", "SHS 100x5", "CHS 114.3x5".';
		}
		if ( in_array( 'grades', $what, true ) ) {
			$lines[] = 'STEEL GRADES: ' . implode( ', ', self::grades() );
		}
		return implode( "\n", $lines );
	}
}
