<?php
/**
 * Notification types of the products market (sent through sm-core).
 *
 * @package SM\Products
 */

namespace SM\Products;

use SM\Core\Routing\Routes;

defined( 'ABSPATH' ) || exit;

final class Notifications {

	public static function init() {
		add_filter( 'sm_notification_types', array( __CLASS__, 'types' ) );
	}

	public static function types( $types ) {
		$title = static function ( $p ) {
			$prod = Products::get( $p['product_id'] ?? 0 );
			return $prod ? Presenter::title_text( $prod ) : '';
		};
		$types['product.submitted'] = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'New product to review', 'sm-products' ),
			/* translators: %s: product title. */
			'body'    => static fn( $p ) => sprintf( __( 'A seller submitted "%s". Check it before it is published.', 'sm-products' ), $title( $p ) ),
			'link'    => static fn( $p ) => admin_url( 'admin.php?page=sm-products&status=pending' ),
		);
		$types['product.revision']  = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Changes of a listing to review', 'sm-products' ),
			/* translators: %s: product title. */
			'body'    => static fn( $p ) => sprintf( __( 'A seller changed "%s". The published version stays online until you check the changes.', 'sm-products' ), $title( $p ) ),
			'link'    => static fn( $p ) => admin_url( 'admin.php?page=sm-products&status=revisions' ),
		);
		$types['product.revision_approved'] = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Your changes are published', 'sm-products' ),
			/* translators: %s: product title. */
			'body'    => static fn( $p ) => sprintf( __( 'The changes of "%s" are now visible to buyers.', 'sm-products' ), $title( $p ) ),
			'link'    => static fn( $p ) => Routes::url( 'account', array( 'view' => 'listings' ) ),
		);
		$types['product.revision_rejected'] = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Your changes were not accepted', 'sm-products' ),
			/* translators: 1: product title, 2: reason. */
			'body'    => static fn( $p ) => sprintf( __( 'The changes of "%1$s" were not published: %2$s The previous version is still online; you can edit it again.', 'sm-products' ), $title( $p ), $p['reason'] ?? '' ),
			'link'    => static fn( $p ) => Routes::url( 'account', array( 'view' => 'publish', 'edit' => $p['product_id'] ?? 0 ) ),
		);
		$types['product.admin_edited'] = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'The platform changed one of your listings', 'sm-products' ),
			/* translators: 1: product title, 2: reason, 3: changes. */
			'body'    => static fn( $p ) => sprintf( __( 'The platform team changed "%1$s". Reason: %2$s Changes: %3$s', 'sm-products' ), $title( $p ), $p['reason'] ?? '', $p['changes'] ?? '' ),
			'link'    => static fn( $p ) => Routes::url( 'account', array( 'view' => 'listings' ) ),
		);
		$types['product.approved']  = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Your product is published', 'sm-products' ),
			/* translators: %s: product title. */
			'body'    => static fn( $p ) => sprintf( __( '"%s" is now visible to buyers.', 'sm-products' ), $title( $p ) ),
			'link'    => static fn( $p ) => Routes::url( 'account', array( 'view' => 'listings' ) ),
		);
		$types['product.rejected']  = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Your product needs changes', 'sm-products' ),
			/* translators: 1: product title, 2: reason. */
			'body'    => static fn( $p ) => sprintf( __( '"%1$s" was not published: %2$s Edit it and send it again.', 'sm-products' ), $title( $p ), $p['reason'] ?? '' ),
			'link'    => static fn( $p ) => Routes::url( 'account', array( 'view' => 'publish', 'edit' => $p['product_id'] ?? 0 ) ),
		);
		$types['product.expired']   = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Your product has expired', 'sm-products' ),
			/* translators: %s: product title. */
			'body'    => static fn( $p ) => sprintf( __( '"%s" is no longer shown. Publish it again from "My listings" if it is still available.', 'sm-products' ), $title( $p ) ),
			'link'    => static fn( $p ) => Routes::url( 'account', array( 'view' => 'listings' ) ),
		);
		return $types;
	}
}
