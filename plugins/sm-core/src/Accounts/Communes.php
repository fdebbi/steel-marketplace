<?php
/**
 * Communes by wilaya (data/communes.php). Wilayas without a list (59-69, from
 * 2027) take the commune as free text.
 *
 * @package SM\Core
 */

namespace SM\Core\Accounts;

defined( 'ABSPATH' ) || exit;

final class Communes {

	private static $data = null;

	/** code => list of [ fr, ar ]. */
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

	/**
	 * The commune given for a wilaya: [ fr, ar ] from the list, the typed text when
	 * the wilaya has no list, or null when it is not a commune of that wilaya.
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
		foreach ( $list as $c ) {
			if ( 0 === strcasecmp( $c[0], $name ) || $c[1] === $name ) {
				return $c;
			}
		}
		return null;
	}
}
