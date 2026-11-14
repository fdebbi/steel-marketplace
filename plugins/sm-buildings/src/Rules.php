<?php
/**
 * Consistency rules of building listings, checked with the rest of the form.
 *
 * - 0.3.0: an assembled building may be priced for the whole building or per
 *   kg, like a dismantled one (the per-kg price helps to value it as a lot).
 * - "Dismantling included" only makes sense for a building that is still assembled.
 *
 * @package SM\Buildings
 */

namespace SM\Buildings;

defined( 'ABSPATH' ) || exit;

final class Rules {

	public static function init() {
		add_filter( 'sm_products_spec_check', array( __CLASS__, 'check' ), 10, 4 );
	}

	public static function check( $errors, $cat, $d, $in ) {
		if ( Market::CODE !== ( $cat->market ?? '' ) ) {
			return $errors;
		}
		$mode = (string) ( $d['variant'] ?? '' );
		if ( 'dismantled' === $mode && ! empty( $d['attrs']['inc_dismantling'] ) ) {
			$errors['attrs_inc_dismantling'] = __( 'The building is already dismantled: untick "Dismantling included".', 'sm-buildings' );
		}
		return $errors;
	}
}
