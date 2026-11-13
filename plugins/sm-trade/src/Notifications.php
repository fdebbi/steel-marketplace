<?php
/**
 * Notifications of orders and offers (on the site and by e-mail, in the recipient's
 * language, through SM Core).
 *
 * @package SM\Trade
 */

namespace SM\Trade;

use SM\Core\Notifications\Notifications as Notify;
use SM\Core\Routing\Routes as CoreRoutes;

defined( 'ABSPATH' ) || exit;

final class Notifications {

	public static function init() {
		add_filter( 'sm_notification_types', array( __CLASS__, 'types' ) );
	}

	public static function notify_company( $company_id, $type, array $payload ) {
		Notify::notify_company( (int) $company_id, $type, $payload );
	}

	public static function notify_user( $user_id, $type, array $payload, $company_id = 0 ) {
		Notify::notify( array( (int) $user_id ), $type, $payload, (int) $company_id );
	}

	/** Page of a deal in the account. */
	public static function deal_url( $id ) {
		return CoreRoutes::url( 'account', array( 'view' => 'deal', 'deal' => (int) $id ) );
	}

	/** "ACH-2026-00012 · IPE 160 · S275JR" */
	private static function label( $p ) {
		$d = Deals::get( (int) ( $p['deal_id'] ?? 0 ) );
		return $d ? $d->ref . ' · ' . $d->title : '';
	}

	private static function money( $p ) {
		$d = Deals::get( (int) ( $p['deal_id'] ?? 0 ) );
		return $d ? number_format_i18n( (float) $d->amount, 0 ) . ' DA' : '';
	}

	private static function note( $p ) {
		$n = trim( (string) ( $p['note'] ?? '' ) );
		/* translators: %s: message written with the answer. */
		return '' === $n ? '' : ' ' . sprintf( __( 'Message: "%s"', 'sm-trade' ), $n );
	}

	public static function types( $types ) {
		$link = static fn( $p ) => self::deal_url( (int) ( $p['deal_id'] ?? 0 ) );
		$types['trade.order'] = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'New order on your listing', 'sm-trade' ),
			/* translators: 1: reference and listing, 2: total. */
			'body'    => static fn( $p ) => sprintf( __( 'A buyer orders %1$s for %2$s at your price. Accept or refuse it within 7 days; the buyer\'s name, phone and e-mail are on the order.', 'sm-trade' ), self::label( $p ), self::money( $p ) ),
			'link'    => $link,
		);
		$types['trade.offer'] = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'New offer on your listing', 'sm-trade' ),
			/* translators: 1: reference and listing, 2: offered total. */
			'body'    => static fn( $p ) => sprintf( __( 'A buyer offers %2$s for %1$s. Accept, refuse or propose another price within 7 days.', 'sm-trade' ), self::label( $p ), self::money( $p ) ),
			'link'    => $link,
		);
		$types['trade.countered'] = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'New price proposed', 'sm-trade' ),
			/* translators: 1: reference and listing, 2: proposed total. */
			'body'    => static fn( $p ) => sprintf( __( 'For %1$s, the other party proposes %2$s. Accept, refuse or answer within 7 days.', 'sm-trade' ), self::label( $p ), self::money( $p ) ) . self::note( $p ),
			'link'    => $link,
		);
		$types['trade.accepted'] = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Order accepted', 'sm-trade' ),
			/* translators: 1: reference and listing, 2: total. */
			'body'    => static fn( $p ) => sprintf( __( '%1$s is accepted for %2$s. The contact details are on the order page: agree on payment and delivery directly.', 'sm-trade' ), self::label( $p ), self::money( $p ) ) . self::note( $p ),
			'link'    => $link,
		);
		$types['trade.refused'] = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Order or offer refused', 'sm-trade' ),
			/* translators: %s: reference and listing. */
			'body'    => static fn( $p ) => sprintf( __( '%s was refused.', 'sm-trade' ), self::label( $p ) ) . self::note( $p ),
			'link'    => $link,
		);
		$types['trade.cancelled'] = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Order or offer cancelled by the buyer', 'sm-trade' ),
			/* translators: %s: reference and listing. */
			'body'    => static fn( $p ) => sprintf( __( 'The buyer cancelled %s.', 'sm-trade' ), self::label( $p ) ) . self::note( $p ),
			'link'    => $link,
		);
		$types['trade.expired'] = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Order or offer expired', 'sm-trade' ),
			/* translators: %s: reference and listing. */
			'body'    => static fn( $p ) => sprintf( __( '%s got no answer within 7 days and is closed.', 'sm-trade' ), self::label( $p ) ),
			'link'    => $link,
		);
		return $types;
	}
}
