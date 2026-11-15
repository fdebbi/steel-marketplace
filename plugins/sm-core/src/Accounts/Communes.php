<?php
/**
 * Communes by wilaya (data/communes.php, 69 wilayas) with the centre of each
 * commune, used to move the maps to the chosen commune.
 *
 * @package SM\Core
 */

namespace SM\Core\Accounts;

defined( 'ABSPATH' ) || exit;

final class Communes {

	private static $data = null;

	/** code => list of [ fr, ar, lat, lng, code ]. */
	public static function all() {
		if ( null === self::$data ) {
			self::$data = (array) apply_filters( 'sm_core_communes', require SM_CORE_DIR . '/data/communes.php' );
		}
		return self::$data;
	}

	/** Communes of a wilaya, [] when the list is not known. */
	public static function of( $wilaya ) {
		return (array) ( self::all()[ (string) $wilaya ] ?? array() );
	}

	/** "Aïn-Taya" → "aintaya": accents, case, spaces, hyphens and apostrophes do not count. */
	public static function key( $name ) {
		return preg_replace( '/[^a-z0-9\x{0600}-\x{06FF}]+/u', '', mb_strtolower( remove_accents( trim( (string) $name ) ) ) );
	}

	/**
	 * The commune given for a wilaya: its row [ fr, ar, lat, lng, code ] from the list,
	 * [ text, text ] when the wilaya has no list, or null when it is not a commune of that wilaya.
	 */
	public static function find( $wilaya, $name ) {
		$name = trim( (string) $name );
		if ( '' === $name ) {
			return null;
		}
		$list = self::of( $wilaya );
		if ( ! $list ) {
			$t = mb_substr( sanitize_text_field( $name ), 0, 80 );
			return array( $t, $t );
		}
		$k = self::key( $name );
		foreach ( $list as $c ) {
			if ( self::key( $c[0] ) === $k || self::key( $c[1] ) === $k ) {
				return $c;
			}
		}
		return null;
	}

	/** Centre of a commune [ lat, lng ], or null. */
	public static function point( $wilaya, $name ) {
		$c = self::find( $wilaya, $name );
		return $c && isset( $c[2], $c[3] ) ? array( (float) $c[2], (float) $c[3] ) : null;
	}

	/**
	 * A commune typed or chosen in a form, written as in the list when it is found;
	 * otherwise the text as typed (older entries stay as they were).
	 */
	public static function clean( $wilaya, $name ) {
		$c = self::find( $wilaya, $name );
		return $c ? (string) $c[0] : mb_substr( trim( sanitize_text_field( (string) $name ) ), 0, 80 );
	}
}
