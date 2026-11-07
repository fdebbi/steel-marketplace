<?php
/**
 * Public helpers of the weight engine.
 *
 * @package SM\WeightEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Weight calculation (see SM\WeightEngine\Calculator::calculate for the input keys).
 *
 * @return array|WP_Error
 */
function sm_weight_calculate( array $input ) {
	return SM\WeightEngine\Calculator::calculate( $input );
}

/** Weight engine setting (density, bar_lengths, default_length, limits…). */
function sm_weight_setting( $key, $default = null ) {
	return SM\WeightEngine\Settings::get( $key, $default );
}
