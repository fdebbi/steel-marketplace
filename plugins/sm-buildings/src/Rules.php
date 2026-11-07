<?php
/**
 * Consistency rules of building listings, checked with the rest of the form.
 *
 * - An assembled building is sold at an asking price for the whole building
 *   (price per kg and per m² are then derived, never typed).
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
		$unit = (string) ( $in['price_unit'] ?? '' );
		if ( 'assembled' === $mode && '' !== $unit && 'lot' !== $unit ) {
			$errors['price_unit'] = __( 'An assembled building is sold at a price for the whole building. The price per kg and per m² are calculated from it.', 'sm-buildings' );
		}
		if ( 'dismantled' === $mode && ! empty( $d['attrs']['inc_dismantling'] ) ) {
			$errors['attrs_inc_dismantling'] = __( 'The building is already dismantled: untick "Dismantling included".', 'sm-buildings' );
		}
		return $errors;
	}
}
