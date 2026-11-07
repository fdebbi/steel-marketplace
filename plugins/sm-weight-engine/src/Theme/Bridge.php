<?php
/**
 * Data for the sm-marketplace theme (data contract, sm_theme_data_* filters).
 *
 * @package SM\WeightEngine
 */

namespace SM\WeightEngine\Theme;

use SM\WeightEngine\Catalogue;
use SM\WeightEngine\Presenter;
use SM\WeightEngine\Settings;

defined( 'ABSPATH' ) || exit;

final class Bridge {

	public static function init() {
		add_filter( 'sm_theme_data_weight_families', static fn( $r ) => null !== $r ? $r : array_values( array_map( array( Presenter::class, 'family' ), Catalogue::families() ) ) );
		add_filter(
			'sm_theme_data_weight_settings',
			static function ( $r ) {
				if ( null !== $r ) {
					return $r;
				}
				$s = Settings::all();
				return array(
					'bar_lengths'    => $s['bar_lengths'],
					'default_length' => $s['default_length'],
					'max_length'     => $s['max_length'],
					'density'        => $s['density'],
					'version'        => array( 'engine' => SM_WEIGHT_VERSION, 'reference' => SM_WEIGHT_REF_VERSION ),
				);
			}
		);
	}
}
