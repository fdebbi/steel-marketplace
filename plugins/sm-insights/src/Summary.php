<?php
/**
 * « What to remember »: 3 or 4 points written by the AI (sm-ai, filter
 * sm_ai_report_summary) from the figures of the market only — never the part of
 * the company, never a sales count. Kept for the day per choice and language;
 * without AI (or on error), the report has no summary.
 *
 * @package SM\Insights
 */

namespace SM\Insights;

defined( 'ABSPATH' ) || exit;

final class Summary {

	/** Figures sent to the AI: short, no company, no details per size. */
	public static function facts( array $m ) {
		$prices = array();
		foreach ( $m['prices'] as $row ) {
			$prices[] = array_intersect_key( $row, array_flip( array( 'label', 'new', 'new_change', 'reuse', 'reuse_change', 'gap' ) ) );
		}
		$labels = Regions::labels();
		$reg    = array();
		foreach ( $m['regions'] as $k => $r ) {
			$reg[ $labels[ $k ] ] = array( 'listings' => $r['listings'], 'requests' => $r['rfqs'], 'reuse_share_pct' => $r['reuse_pct'] );
		}
		return array(
			'period_days'          => (int) $m['args']['period'],
			'market'               => $m['args']['market'],
			'region'               => '' === $m['args']['region'] ? 'Algeria' : $labels[ $m['args']['region'] ],
			'prices_DA_kg'         => $prices,
			'weights_on_sale'      => array(
				'total_t'      => $m['weights']['total_t'],
				'new_pct'      => $m['weights']['products_pct'],
				'reuse_pct'    => $m['weights']['reuse_pct'],
				'buildings_pct' => $m['weights']['buildings_pct'],
			),
			'listings_online'      => $m['kpis']['listings'],
			'requests_for_quotation' => $m['kpis']['rfqs'],
			'searches'             => $m['kpis']['searches'],
			'best_selling_ranking' => $m['top_sold'],
			'top_searched'         => array_map( static fn( $t ) => $t['label'] . ': ' . $t['count'], $m['top_searched'] ),
			'asked_little_offered' => array_map( static fn( $o ) => $o['label'] . ': ' . $o['count'] . ' searches and requests, ' . $o['listings'] . ' listings', $m['opportunities'] ),
			'regions'              => $reg,
		);
	}

	/** Points of the summary (string[]), or array() without AI. */
	public static function get( array $m ) {
		$lang = class_exists( '\SM\Core\I18n\Language' ) ? \SM\Core\I18n\Language::current() : 'fr';
		$key  = 'sm_ins_ai_' . md5( Report::cache_key( $m['args'] ) . '|' . $lang );
		$got  = get_transient( $key );
		if ( is_array( $got ) ) {
			return $got;
		}
		if ( ! $m['prices'] && ! $m['top_searched'] && ! $m['weights']['total_t'] ) {
			return array(); // Nothing to sum up yet.
		}
		$res = apply_filters( 'sm_ai_report_summary', null, self::facts( $m ), $lang );
		if ( ! is_array( $res ) ) {
			set_transient( $key, array(), HOUR_IN_SECONDS ); // AI off or busy: try again in an hour.
			return array();
		}
		$points = array_values( array_filter( array_map( 'strval', $res ) ) );
		set_transient( $key, $points, DAY_IN_SECONDS );
		return $points;
	}
}
