<?php
/**
 * Notification types of services (sent through sm-core in the recipient's language).
 *
 * @package SM\Services
 */

namespace SM\Services;

use SM\Core\Accounts\Companies;
use SM\Core\Routing\Routes as CoreRoutes;

defined( 'ABSPATH' ) || exit;

final class Notifications {

	public static function init() {
		add_filter( 'sm_notification_types', array( __CLASS__, 'types' ) );
	}

	public static function types( $types ) {
		$title    = static function ( $p ) {
			$s = Services::get( $p['service_id'] ?? 0 );
			return $s ? Presenter::title_text( $s ) : '';
		};
		$co       = static fn( $p ) => Companies::get( $p['company_id'] ?? 0 )->name ?? '';
		$received = static fn( $p ) => CoreRoutes::url( 'account', array( 'view' => 'service-requests' ) );
		$sent     = static fn( $p ) => CoreRoutes::url( 'account', array( 'view' => 'my-service-requests' ) );
		$types['service.submitted']         = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'New service page to review', 'sm-services' ),
			/* translators: %s: service title. */
			'body'    => static fn( $p ) => sprintf( __( 'A provider submitted "%s". Check it before it is published.', 'sm-services' ), $title( $p ) ),
			'link'    => static fn( $p ) => admin_url( 'admin.php?page=sm-services&status=pending' ),
		);
		$types['service.approved']          = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Your service page is published', 'sm-services' ),
			/* translators: %s: service title. */
			'body'    => static fn( $p ) => sprintf( __( '"%s" is now visible to clients.', 'sm-services' ), $title( $p ) ),
			'link'    => static fn( $p ) => Routes::link( (int) ( $p['service_id'] ?? 0 ) ),
		);
		$types['service.rejected']          = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Your service page needs changes', 'sm-services' ),
			/* translators: 1: service title, 2: reason. */
			'body'    => static fn( $p ) => sprintf( __( '"%1$s" was not published: %2$s', 'sm-services' ), $title( $p ), $p['reason'] ?? '' ),
			'link'    => static fn( $p ) => CoreRoutes::url( 'account', array( 'view' => 'services' ) ),
		);
		$types['service.request_received']  = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'New service request', 'sm-services' ),
			/* translators: 1: client company, 2: service title. */
			'body'    => static fn( $p ) => sprintf( __( '%1$s sent a request about "%2$s". Answer with a quote from your dashboard.', 'sm-services' ), $co( $p ), $title( $p ) ),
			'link'    => $received,
		);
		$types['service.quote_received']    = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'You received a quote', 'sm-services' ),
			/* translators: 1: provider, 2: service title. */
			'body'    => static fn( $p ) => sprintf( __( '%1$s answered your request about "%2$s" with a quote.', 'sm-services' ), $co( $p ), $title( $p ) ),
			'link'    => $sent,
		);
		$types['service.request_declined']  = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'A provider cannot take your request', 'sm-services' ),
			/* translators: 1: provider, 2: service title. */
			'body'    => static fn( $p ) => sprintf( __( '%1$s cannot take your request about "%2$s". Other providers may.', 'sm-services' ), $co( $p ), $title( $p ) ),
			'link'    => $sent,
		);
		$types['service.quote_accepted']    = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Your quote was accepted', 'sm-services' ),
			/* translators: 1: client, 2: service title. */
			'body'    => static fn( $p ) => sprintf( __( '%1$s accepted your quote for "%2$s". Contact the client to organise the work.', 'sm-services' ), $co( $p ), $title( $p ) ),
			'link'    => $received,
		);
		$types['service.quote_declined']    = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Your quote was not accepted', 'sm-services' ),
			/* translators: 1: client, 2: service title. */
			'body'    => static fn( $p ) => sprintf( __( '%1$s did not accept your quote for "%2$s".', 'sm-services' ), $co( $p ), $title( $p ) ),
			'link'    => $received,
		);
		$types['service.request_cancelled'] = array(
			'email'   => false,
			'subject' => static fn( $p ) => __( 'A service request was cancelled', 'sm-services' ),
			/* translators: 1: client, 2: service title. */
			'body'    => static fn( $p ) => sprintf( __( '%1$s cancelled its request about "%2$s".', 'sm-services' ), $co( $p ), $title( $p ) ),
			'link'    => $received,
		);
		return $types;
	}
}
