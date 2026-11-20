<?php
/**
 * Data for the theme (sm_theme_data):
 *  - insights_enabled : the Business report is in the menu (every company, the plan
 *                       being offered on the page; a professional whose plan includes it);
 *  - insights_report  : args { period: 30|90|365, market: all|new|reuse, region: ''|centre|east|west|south }
 *                       → { state: ok|login|none|upgrade, args, options, report?, summary?, mine? }.
 *
 * @package SM\Insights
 */

namespace SM\Insights\Theme;

use SM\Insights\Access;
use SM\Insights\Regions;
use SM\Insights\Report;
use SM\Insights\Summary;

defined( 'ABSPATH' ) || exit;

final class Bridge {

	public static function init() {
		add_filter( 'sm_theme_data_insights_enabled', array( __CLASS__, 'enabled' ) );
		add_filter( 'sm_theme_data_insights_report', array( __CLASS__, 'report' ), 10, 2 );
	}

	/** Page in the menu: every company (the plan is offered on the page), a professional whose plan includes it. */
	public static function enabled( $result ) {
		if ( null !== $result ) {
			return $result;
		}
		$c = is_user_logged_in() ? sm_current_company() : null;
		if ( ! Access::account( $c ) ) {
			return false;
		}
		return 'company' === $c->account_class || 'ok' === Access::state( $c );
	}

	public static function options() {
		return array(
			'period' => array(
				'30'  => __( '30 days', 'sm-insights' ),
				'90'  => __( '3 months', 'sm-insights' ),
				'365' => __( '12 months', 'sm-insights' ),
			),
			'market' => array(
				'all'   => __( 'All', 'sm-insights' ),
				'new'   => __( 'New', 'sm-insights' ),
				'reuse' => __( 'Reuse', 'sm-insights' ),
			),
			'region' => array( '' => __( 'Algeria', 'sm-insights' ) ) + Regions::labels(),
		);
	}

	public static function report( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$company = sm_current_company();
		$state   = Access::state( $company );
		$a       = Report::args( (array) $args );
		$out     = array( 'state' => $state, 'args' => $a, 'options' => self::options(), 'min_sources' => Report::min_sources() );
		if ( 'ok' !== $state ) {
			return $out;
		}
		$m              = Report::market( $a );
		$out['report']  = $m;
		$out['summary'] = Summary::get( $m );
		$out['mine']    = Report::mine( $m, $company );
		return $out;
	}
}
