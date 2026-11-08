<?php
/**
 * Daily job: open requests whose answer deadline passed become "expired".
 * The buyer can still accept one of the offers received.
 *
 * @package SM\Rfq
 */

namespace SM\Rfq;

defined( 'ABSPATH' ) || exit;

final class Expiry {

	public static function init() {
		add_action( 'sm_rfq_daily', array( __CLASS__, 'run' ) );
	}

	public static function run() {
		global $wpdb;
		$ids = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . Rfqs::table() . " WHERE status = 'open' AND deadline IS NOT NULL AND deadline < %s LIMIT 500", current_time( 'Y-m-d', true ) ) ); // phpcs:ignore WordPress.DB
		foreach ( $ids as $id ) {
			$r = Rfqs::get( $id );
			Rfqs::set_status( $id, 'expired' );
			\SM\Core\Notifications\Notifications::notify_company( $r->company_id, 'rfq.expired', array( 'rfq_id' => (int) $id, 'offers' => $r->offers_count ) );
		}
		return count( $ids );
	}
}
