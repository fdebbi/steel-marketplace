<?php
/**
 * Daily task: open orders and offers without an answer for 7 days expire.
 *
 * @package SM\Trade
 */

namespace SM\Trade;

defined( 'ABSPATH' ) || exit;

final class Cron {

	const HOOK = 'sm_trade_daily';

	public static function init() {
		add_action( self::HOOK, array( Deals::class, 'expire_old' ) );
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			self::schedule();
		}
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK );
		}
	}

	public static function unschedule() {
		wp_clear_scheduled_hook( self::HOOK );
	}
}
