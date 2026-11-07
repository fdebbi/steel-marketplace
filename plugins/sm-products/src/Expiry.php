<?php
/**
 * Daily job: published products whose publication period ended become "expired".
 *
 * @package SM\Products
 */

namespace SM\Products;

defined( 'ABSPATH' ) || exit;

final class Expiry {

	public static function init() {
		add_action( 'sm_products_daily', array( __CLASS__, 'run' ) );
	}

	public static function run() {
		global $wpdb;
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}sm_products WHERE status = 'published' AND expires_at IS NOT NULL AND expires_at < %s LIMIT 500", current_time( 'mysql', true ) ) ); // phpcs:ignore WordPress.DB
		foreach ( $ids as $id ) {
			$p = Products::get( $id );
			Products::set_status( $id, 'expired' );
			\SM\Core\Notifications\Notifications::notify_company( $p->company_id, 'product.expired', array( 'product_id' => (int) $id ) );
		}
		return count( $ids );
	}
}
