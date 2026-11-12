<?php
/**
 * Daily task: close the periods that ended (and pause what goes beyond the free
 * plan), remind the accounts whose period ends soon.
 *
 * @package SM\Subscriptions
 */

namespace SM\Subscriptions;

defined( 'ABSPATH' ) || exit;

final class Cron {

	const HOOK = 'sm_sub_daily';

	public static function init() {
		add_action( self::HOOK, array( __CLASS__, 'run' ) );
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK );
		}
	}

	public static function unschedule() {
		wp_clear_scheduled_hook( self::HOOK );
	}

	public static function run() {
		Subscriptions::expire();
		foreach ( Subscriptions::ending_soon( (int) Settings::get( 'reminder_days' ) ) as $sub ) {
			do_action( 'sm_sub_ending', $sub );
			Subscriptions::mark_reminded( (int) $sub->id );
		}
	}
}
