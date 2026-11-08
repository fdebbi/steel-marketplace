<?php
/**
 * Notification types of requests for quotation (sent through sm-core, in the
 * recipient's language, in the app and by e-mail).
 *
 * @package SM\Rfq
 */

namespace SM\Rfq;

use SM\Core\Accounts\Companies;
use SM\Core\Routing\Routes;

defined( 'ABSPATH' ) || exit;

final class Notifications {

	public static function init() {
		add_filter( 'sm_notification_types', array( __CLASS__, 'types' ) );
	}

	public static function types( $types ) {
		$title = static function ( $p ) {
			$r = Rfqs::get( $p['rfq_id'] ?? 0 );
			return $r ? Presenter::title_text( $r ) : '';
		};
		$link  = static fn( $p ) => \SM\Rfq\Routes::link( (int) ( $p['rfq_id'] ?? 0 ) );
		$co    = static fn( $p ) => Companies::get( $p['company_id'] ?? 0 )->name ?? '';
		$types['rfq.submitted']       = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'New request for quotation to review', 'sm-rfq' ),
			/* translators: %s: request title. */
			'body'    => static fn( $p ) => sprintf( __( 'A buyer submitted "%s". Check it before it is published.', 'sm-rfq' ), $title( $p ) ),
			'link'    => static fn( $p ) => admin_url( 'admin.php?page=sm-rfq&status=pending' ),
		);
		$types['rfq.approved']        = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Your request for quotation is published', 'sm-rfq' ),
			/* translators: %s: request title. */
			'body'    => static fn( $p ) => sprintf( __( '"%s" is visible to suppliers. You will be told of each offer.', 'sm-rfq' ), $title( $p ) ),
			'link'    => $link,
		);
		$types['rfq.rejected']        = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Your request for quotation needs changes', 'sm-rfq' ),
			/* translators: 1: request title, 2: reason. */
			'body'    => static fn( $p ) => sprintf( __( '"%1$s" was not published: %2$s', 'sm-rfq' ), $title( $p ), $p['reason'] ?? '' ),
			'link'    => static fn( $p ) => Routes::url( 'account', array( 'view' => 'rfq' ) ),
		);
		$types['rfq.offer_received']  = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'New offer on your request for quotation', 'sm-rfq' ),
			/* translators: 1: supplier name, 2: request title. */
			'body'    => static fn( $p ) => sprintf( __( '%1$s sent an offer for "%2$s". Compare the offers on the request page.', 'sm-rfq' ), $co( $p ), $title( $p ) ),
			'link'    => $link,
		);
		$types['rfq.offer_updated']   = array(
			'email'   => false,
			'subject' => static fn( $p ) => __( 'An offer was updated', 'sm-rfq' ),
			/* translators: 1: supplier name, 2: request title. */
			'body'    => static fn( $p ) => sprintf( __( '%1$s updated its offer for "%2$s".', 'sm-rfq' ), $co( $p ), $title( $p ) ),
			'link'    => $link,
		);
		$types['rfq.offer_withdrawn'] = array(
			'email'   => false,
			'subject' => static fn( $p ) => __( 'An offer was withdrawn', 'sm-rfq' ),
			/* translators: 1: supplier name, 2: request title. */
			'body'    => static fn( $p ) => sprintf( __( '%1$s withdrew its offer for "%2$s".', 'sm-rfq' ), $co( $p ), $title( $p ) ),
			'link'    => $link,
		);
		$types['rfq.offer_accepted']  = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Your offer was accepted', 'sm-rfq' ),
			/* translators: %s: request title. */
			'body'    => static fn( $p ) => sprintf( __( 'The buyer accepted your offer for "%s". Contact him to confirm the order and the delivery.', 'sm-rfq' ), $title( $p ) ),
			'link'    => $link,
		);
		$types['rfq.offer_declined']  = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Your offer was not retained', 'sm-rfq' ),
			/* translators: %s: request title. */
			'body'    => static fn( $p ) => sprintf( __( 'The buyer did not retain your offer for "%s".', 'sm-rfq' ), $title( $p ) ),
			'link'    => $link,
		);
		$types['rfq.awarded_other']   = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'A request you answered was awarded', 'sm-rfq' ),
			/* translators: %s: request title. */
			'body'    => static fn( $p ) => sprintf( __( 'The buyer chose another offer for "%s". Thank you for your answer.', 'sm-rfq' ), $title( $p ) ),
			'link'    => $link,
		);
		$types['rfq.closed']          = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'A request you answered was closed', 'sm-rfq' ),
			/* translators: %s: request title. */
			'body'    => static fn( $p ) => sprintf( __( 'The buyer closed "%s" without choosing an offer on the platform.', 'sm-rfq' ), $title( $p ) ),
			'link'    => $link,
		);
		$types['rfq.expired']         = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'The answer deadline of your request has passed', 'sm-rfq' ),
			/* translators: 1: request title, 2: number of offers. */
			'body'    => static fn( $p ) => sprintf( __( '"%1$s" no longer accepts offers (%2$d received). You can still accept one of them.', 'sm-rfq' ), $title( $p ), (int) ( $p['offers'] ?? 0 ) ),
			'link'    => $link,
		);
		return $types;
	}
}
