<?php
/**
 * Usage log of the AI services (table sm_ai_usage): one row per call, error
 * or refusal, with tokens and estimated cost. Feeds the quotas, the monthly
 * budget and the consumption screen.
 *
 * @package SM\Ai
 */

namespace SM\Ai;

use SM\Core\Support\Forms;

defined( 'ABSPATH' ) || exit;

final class Usage {

	public static function table() {
		return sm_table( 'ai_usage' );
	}

	/** Estimated cost in US dollars from the provider prices (per million tokens). */
	public static function cost( $provider, $tokens_in, $tokens_out ) {
		$p = Settings::all()['providers'][ $provider ] ?? null;
		if ( ! $p ) {
			return 0.0;
		}
		return round( ( $tokens_in * (float) $p['price_in'] + $tokens_out * (float) $p['price_out'] ) / 1000000, 6 );
	}

	public static function log( array $row ) {
		global $wpdb;
		$row = wp_parse_args(
			$row,
			array( 'feature' => '', 'provider' => '', 'model' => '', 'company_id' => 0, 'user_id' => get_current_user_id(), 'status' => 'ok', 'tokens_in' => 0, 'tokens_out' => 0, 'cost' => 0, 'duration_ms' => 0, 'error' => '' )
		);
		$row['ip']         = Forms::ip();
		$row['error']      = mb_substr( (string) $row['error'], 0, 255 );
		$row['created_at'] = current_time( 'mysql', true );
		$wpdb->insert( self::table(), $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->insert_id;
	}

	private static function month_start() {
		return get_gmt_from_date( wp_date( 'Y-m-01 00:00:00' ) );
	}

	private static function day_start() {
		return get_gmt_from_date( wp_date( 'Y-m-d 00:00:00' ) );
	}

	/** Successful calls of a company for a feature this month. */
	public static function company_month( $company_id, $feature ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . " WHERE company_id = %d AND feature = %s AND status = 'ok' AND created_at >= %s", $company_id, $feature, self::month_start() ) ); // phpcs:ignore WordPress.DB
	}

	/** Calls of a user today (all features). */
	public static function user_day( $user_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . " WHERE user_id = %d AND status IN ('ok','error') AND created_at >= %s", $user_id, self::day_start() ) ); // phpcs:ignore WordPress.DB
	}

	/** Calls from an IP address today for a feature (visitors). */
	public static function ip_day( $ip, $feature ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . " WHERE ip = %s AND feature = %s AND user_id = 0 AND status IN ('ok','error') AND created_at >= %s", $ip, $feature, self::day_start() ) ); // phpcs:ignore WordPress.DB
	}

	/** Estimated cost of the month (US dollars). */
	public static function month_cost() {
		global $wpdb;
		return (float) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(cost),0) FROM ' . self::table() . ' WHERE created_at >= %s', self::month_start() ) ); // phpcs:ignore WordPress.DB
	}

	/** Totals of the month per feature: calls, errors, refusals, tokens, cost. */
	public static function month_stats() {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT feature, status, COUNT(*) n, SUM(tokens_in) tin, SUM(tokens_out) tout, SUM(cost) cost FROM ' . self::table() . ' WHERE created_at >= %s GROUP BY feature, status', self::month_start() ) ); // phpcs:ignore WordPress.DB
		$out  = array();
		foreach ( Settings::FEATURES as $f ) {
			$out[ $f ] = array( 'ok' => 0, 'error' => 0, 'blocked' => 0, 'tokens_in' => 0, 'tokens_out' => 0, 'cost' => 0.0 );
		}
		foreach ( $rows as $r ) {
			if ( ! isset( $out[ $r->feature ] ) ) {
				continue;
			}
			$out[ $r->feature ][ $r->status ] = (int) $r->n;
			$out[ $r->feature ]['tokens_in']  += (int) $r->tin;
			$out[ $r->feature ]['tokens_out'] += (int) $r->tout;
			$out[ $r->feature ]['cost']       += (float) $r->cost;
		}
		return $out;
	}

	/** Companies using the AI the most this month. */
	public static function top_companies( $limit = 10 ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT company_id, COUNT(*) n, SUM(cost) cost FROM ' . self::table() . " WHERE created_at >= %s AND company_id > 0 AND status = 'ok' GROUP BY company_id ORDER BY n DESC LIMIT %d", self::month_start(), $limit ) ); // phpcs:ignore WordPress.DB
	}

	public static function recent( $limit = 50, $page = 1, $status = '' ) {
		global $wpdb;
		$where = in_array( $status, array( 'ok', 'error', 'blocked' ), true ) ? $wpdb->prepare( 'WHERE status = %s', $status ) : '';
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . " $where ORDER BY id DESC LIMIT %d OFFSET %d", $limit, ( max( 1, $page ) - 1 ) * $limit ) ); // phpcs:ignore WordPress.DB
	}

	public static function purge() {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table() . ' WHERE created_at < %s', gmdate( 'Y-m-d H:i:s', time() - (int) Settings::get( 'retention' ) * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
}
