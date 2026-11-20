<?php
/**
 * The four regions of the report (wilayas 01–69). Filter sm_insights_regions.
 *
 * @package SM\Insights
 */

namespace SM\Insights;

defined( 'ABSPATH' ) || exit;

final class Regions {

	const KEYS = array( 'centre', 'east', 'west', 'south' );

	public static function map() {
		return apply_filters(
			'sm_insights_regions',
			array(
				'centre' => array( '02', '09', '10', '15', '16', '17', '26', '28', '35', '38', '42', '44', '65', '66', '67', '68' ),
				'east'   => array( '04', '05', '06', '12', '18', '19', '21', '23', '24', '25', '34', '36', '40', '41', '43', '60', '62' ),
				'west'   => array( '13', '14', '20', '22', '27', '29', '31', '32', '45', '46', '48', '63', '64' ),
				'south'  => array( '01', '03', '07', '08', '11', '30', '33', '37', '39', '47', '49', '50', '51', '52', '53', '54', '55', '56', '57', '58', '59', '61', '69' ),
			)
		);
	}

	public static function labels() {
		return array(
			'centre' => __( 'Centre', 'sm-insights' ),
			'east'   => __( 'East', 'sm-insights' ),
			'west'   => __( 'West', 'sm-insights' ),
			'south'  => __( 'South', 'sm-insights' ),
		);
	}

	/** Wilayas of a region, or array() for the whole country. */
	public static function wilayas( $region ) {
		return self::map()[ $region ] ?? array();
	}

	public static function of( $wilaya ) {
		foreach ( self::map() as $k => $list ) {
			if ( in_array( (string) $wilaya, $list, true ) ) {
				return $k;
			}
		}
		return '';
	}
}
