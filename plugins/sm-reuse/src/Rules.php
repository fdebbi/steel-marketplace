<?php
/**
 * Consistency rules of reuse listings, checked with the rest of the form.
 *
 * - Scrap is published in the Scrap category, and the Scrap category only takes
 *   the "scrap" condition: a buyer looking for reusable steel never finds scrap,
 *   and scrap is never presented as reusable.
 * - Cutting on demand only makes sense for an inventory of bars (lines).
 *
 * @package SM\Reuse
 */

namespace SM\Reuse;

defined( 'ABSPATH' ) || exit;

final class Rules {

	const SCRAP_CATEGORY = 'reuse-scrap';

	public static function init() {
		add_filter( 'sm_products_spec_check', array( __CLASS__, 'check' ), 10, 4 );
	}

	/**
	 * @param array  $errors Extra errors (field => message).
	 * @param object $cat    Category.
	 * @param array  $d      Validated data so far.
	 * @param array  $in     Raw input.
	 */
	public static function check( $errors, $cat, $d, $in ) {
		if ( Market::CODE !== ( $cat->market ?? '' ) ) {
			return $errors;
		}
		$condition = (string) ( $d['item_condition'] ?? '' );
		if ( self::SCRAP_CATEGORY === $cat->slug && '' !== $condition && 'scrap' !== $condition ) {
			$errors['condition'] = __( 'Scrap is published with the condition "Scrap / recycling only". For reusable steel, choose another category.', 'sm-reuse' );
		} elseif ( self::SCRAP_CATEGORY !== $cat->slug && 'scrap' === $condition ) {
			$errors['condition'] = __( 'Steel only good for recycling goes in the "Scrap" category.', 'sm-reuse' );
		}
		if ( ! empty( $d['attrs']['cut'] ) && 'lines' !== $cat->kind ) {
			$errors['attrs_cut'] = __( 'Cutting on demand is offered for bars and profiles only.', 'sm-reuse' );
		}
		return $errors;
	}
}
