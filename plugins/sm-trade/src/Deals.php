<?php
/**
 * Orders and offers ("deals") between a buyer and a seller.
 *
 *   order  "Buy": the whole lot or a quantity at the listed price; the seller accepts or refuses.
 *   offer  "Make an offer": a quantity and a total price proposed by the buyer; the seller
 *          accepts, refuses or answers with another price; the buyer may accept, refuse or
 *          answer again. At most MAX_COUNTERS answers with a price in all.
 *
 * Status: open (turn = seller|buyer: who must answer), accepted, refused, cancelled
 * (by the buyer while open), expired (no answer for EXPIRY_DAYS days).
 *
 * Accepting takes the quantity from the listing (SM Products Stock::take): if not enough is
 * left, acceptance is refused with the reason. Once accepted, the buyer sees the seller's
 * contact details (also on free plans: the sale must be able to happen). Payment and
 * delivery are settled directly between them, outside the platform.
 *
 * Every step is kept in the events table (history of the administration).
 *
 * @package SM\Trade
 */

namespace SM\Trade;

use SM\Core\Accounts\Companies;
use SM\Core\Audit\AuditLog;
use SM\Products\Presenter;
use SM\Products\Products;
use SM\Products\Stock;

defined( 'ABSPATH' ) || exit;

final class Deals {

	const STATUSES     = array( 'open', 'accepted', 'refused', 'cancelled', 'expired' );
	const MAX_COUNTERS = 3;
	const EXPIRY_DAYS  = 7;

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sm_trade_deals';
	}

	public static function events_table() {
		global $wpdb;
		return $wpdb->prefix . 'sm_trade_events';
	}

	private static function now() {
		return current_time( 'mysql', true );
	}

	public static function get( $id ) {
		global $wpdb;
		$t = self::table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id = %d", (int) $id ) ); // phpcs:ignore WordPress.DB
	}

	/** Pieces of the inventory of a deal by element: list of { key, element, designation, length_mm, n, kg }. */
	public static function items( $deal ) {
		$items = json_decode( (string) ( $deal->items ?? '' ), true );
		return is_array( $items ) ? $items : array();
	}

	/** The same pieces as key => number (what Stock expects). */
	private static function item_counts( $deal ) {
		$out = array();
		foreach ( self::items( $deal ) as $it ) {
			$out[ (string) $it['key'] ] = (int) $it['n'];
		}
		return $out;
	}

	/** Open deal of this buyer on this listing, or null (one at a time). */
	public static function open_for( $product_id, $user_id ) {
		global $wpdb;
		$t = self::table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE product_id = %d AND buyer_user_id = %d AND status = 'open' ORDER BY id DESC LIMIT 1", (int) $product_id, (int) $user_id ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Create an order or an offer. Returns the id or a WP_Error.
	 *
	 * @param array $in kind (order|offer), mode (lot|detail), quantity, amount (offer total, DA),
	 *                  name, phone, email, message; items (pieces of the inventory: key => number)
	 *                  and amount_kg (offer per kg) for a listing bought by element (0.2.0).
	 */
	public static function create( $product_id, $user_id, array $in ) {
		global $wpdb;
		$p = Products::get( (int) $product_id );
		if ( ! $p || 'published' !== $p->status ) {
			return new \WP_Error( 'gone', __( 'This listing is no longer on sale.', 'sm-trade' ) );
		}
		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			return new \WP_Error( 'login', __( 'Sign in to buy or make an offer.', 'sm-trade' ) );
		}
		$buyer = Companies::for_user( $user_id );
		if ( $buyer && (int) $buyer->id === (int) $p->company_id ) {
			return new \WP_Error( 'own', __( 'This is one of your own listings.', 'sm-trade' ) );
		}
		if ( self::open_for( $p->id, $user_id ) ) {
			return new \WP_Error( 'open', __( 'You already have an order or an offer waiting on this listing. Follow it in "My purchases".', 'sm-trade' ) );
		}
		$kind = 'offer' === ( $in['kind'] ?? '' ) ? 'offer' : 'order';
		$mode = 'lot' === ( $in['mode'] ?? '' ) ? 'lot' : 'detail';
		$ok   = Stock::check( $p, $mode, (float) ( $in['quantity'] ?? 0 ), array_map( 'absint', (array) ( $in['items'] ?? array() ) ) );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}
		list( $qty, $unit, $listed ) = $ok;
		$items  = (array) ( $ok[3] ?? array() );
		$amount = $listed;
		if ( 'offer' === $kind ) {
			$amount = round( (float) ( $in['amount'] ?? 0 ), 2 );
			if ( $items ) {
				// By element: the buyer offers a price per kg; the total follows the pieces chosen.
				$per_kg = round( (float) ( $in['amount_kg'] ?? 0 ), 2 );
				if ( $per_kg <= 0 ) {
					return new \WP_Error( 'amount_kg', __( 'Enter the price per kg you offer.', 'sm-trade' ) );
				}
				$amount = round( $qty * $per_kg, 2 );
			}
			if ( $amount <= 0 ) {
				return new \WP_Error( 'amount', __( 'Enter the total price you offer.', 'sm-trade' ) );
			}
		}
		$name  = mb_substr( sanitize_text_field( (string) ( $in['name'] ?? '' ) ), 0, 190 );
		$phone = preg_replace( '/[^\d+ ]/', '', (string) ( $in['phone'] ?? '' ) );
		$email = sanitize_email( (string) ( $in['email'] ?? '' ) );
		if ( '' === trim( $name ) ) {
			return new \WP_Error( 'name', __( 'Enter your name.', 'sm-trade' ) );
		}
		if ( strlen( preg_replace( '/\D/', '', $phone ) ) < 9 ) {
			return new \WP_Error( 'phone', __( 'Enter a valid phone number, so the seller can call you.', 'sm-trade' ) );
		}
		if ( ! is_email( $email ) ) {
			return new \WP_Error( 'email', __( 'Enter a valid e-mail address.', 'sm-trade' ) );
		}
		$now = self::now();
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'kind'              => $kind,
				'status'            => 'open',
				'turn'              => 'seller',
				'product_id'        => (int) $p->id,
				'listing_id'        => (int) $p->listing_id,
				'market'            => (string) $p->market,
				'title'             => mb_substr( Presenter::title_text( $p ), 0, 255 ),
				'seller_company_id' => (int) $p->company_id,
				'buyer_company_id'  => $buyer ? (int) $buyer->id : 0,
				'buyer_user_id'     => $user_id,
				'buyer_name'        => $name,
				'buyer_phone'       => trim( $phone ),
				'buyer_email'       => $email,
				'mode'              => $mode,
				'quantity'          => $qty,
				'unit'              => $unit,
				'listed_total'      => $listed,
				'amount'            => $amount,
				'message'           => mb_substr( sanitize_textarea_field( (string) ( $in['message'] ?? '' ) ), 0, 2000 ),
				'items'             => $items ? wp_json_encode( $items ) : '',
				'created_at'        => $now,
				'updated_at'        => $now,
			)
		);
		$id = (int) $wpdb->insert_id;
		if ( ! $id ) {
			return new \WP_Error( 'db', __( 'The request could not be saved. Try again.', 'sm-trade' ) );
		}
		$ref = ( 'offer' === $kind ? 'OFR-' : 'ACH-' ) . gmdate( 'Y' ) . '-' . str_pad( (string) $id, 5, '0', STR_PAD_LEFT );
		$wpdb->update( self::table(), array( 'ref' => $ref ), array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		self::log( $id, 'buyer', $user_id, 'created', $amount, (string) ( $in['message'] ?? '' ) );
		AuditLog::add( 'trade.created', 'trade', $id, array( 'ref' => $ref, 'kind' => $kind, 'amount' => $amount ), $user_id, (int) $p->company_id );
		Notifications::notify_company( (int) $p->company_id, 'offer' === $kind ? 'trade.offer' : 'trade.order', array( 'deal_id' => $id ) );
		return $id;
	}

	/** Role of a user in a deal: buyer, seller (member of the selling company), or ''. */
	public static function role( $deal, $user_id ) {
		if ( ! $deal || ! $user_id ) {
			return '';
		}
		if ( (int) $deal->buyer_user_id === (int) $user_id ) {
			return 'buyer';
		}
		return Companies::member_role( (int) $deal->seller_company_id, (int) $user_id ) ? 'seller' : '';
	}

	/** Answers the user can give now: accept, refuse, counter, cancel. */
	public static function actions( $deal, $user_id ) {
		$role = self::role( $deal, $user_id );
		if ( ! $deal || 'open' !== $deal->status || ! $role ) {
			return array();
		}
		$out = array();
		if ( $role === $deal->turn ) {
			$out[] = 'accept';
			if ( (int) $deal->counters < self::MAX_COUNTERS && ( 'offer' === $deal->kind || 'seller' === $role ) ) {
				$out[] = 'counter';
			}
		}
		if ( 'seller' === $role || $role === $deal->turn ) {
			$out[] = 'refuse';
		}
		if ( 'buyer' === $role ) {
			$out[] = 'cancel';
		}
		return $out;
	}

	/**
	 * Answer a deal. Returns true or a WP_Error.
	 *
	 * @param string $action accept|refuse|counter|cancel
	 */
	public static function act( $deal_id, $user_id, $action, $amount = 0, $note = '' ) {
		global $wpdb;
		$deal = self::get( $deal_id );
		if ( ! $deal ) {
			return new \WP_Error( 'none', __( 'This order or offer does not exist.', 'sm-trade' ) );
		}
		$role = self::role( $deal, $user_id );
		if ( ! in_array( $action, self::actions( $deal, $user_id ), true ) ) {
			return new \WP_Error( 'action', 'open' === $deal->status ? __( 'It is not your turn to answer.', 'sm-trade' ) : __( 'This order or offer is already closed.', 'sm-trade' ) );
		}
		$note  = mb_substr( sanitize_textarea_field( (string) $note ), 0, 1000 );
		$now   = self::now();
		$other = 'seller' === $role ? 'buyer' : 'seller';
		$row   = array( 'updated_at' => $now );
		switch ( $action ) {
			case 'accept':
				$taken = Stock::take( (int) $deal->product_id, $deal->mode, (float) $deal->quantity, $deal->ref, self::item_counts( $deal ) );
				if ( is_wp_error( $taken ) ) {
					return $taken;
				}
				$row += array( 'status' => 'accepted', 'closed_at' => $now, 'turn' => '' );
				break;
			case 'refuse':
				$row += array( 'status' => 'refused', 'closed_at' => $now, 'turn' => '' );
				break;
			case 'cancel':
				$row += array( 'status' => 'cancelled', 'closed_at' => $now, 'turn' => '' );
				break;
			case 'counter':
				$amount = round( (float) $amount, 2 );
				if ( $amount <= 0 ) {
					return new \WP_Error( 'amount', __( 'Enter the total price you propose.', 'sm-trade' ) );
				}
				if ( abs( $amount - (float) $deal->amount ) < 0.01 ) {
					return new \WP_Error( 'amount', __( 'Propose a different price, or accept this one.', 'sm-trade' ) );
				}
				$row += array( 'amount' => $amount, 'turn' => $other, 'counters' => (int) $deal->counters + 1, 'kind' => 'offer' );
				break;
		}
		$wpdb->update( self::table(), $row, array( 'id' => (int) $deal->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$done = array( 'accept' => 'accepted', 'refuse' => 'refused', 'cancel' => 'cancelled', 'counter' => 'countered' )[ $action ];
		self::log( (int) $deal->id, $role, $user_id, $done, 'counter' === $action ? $amount : (float) $deal->amount, $note );
		AuditLog::add( 'trade.' . $action, 'trade', (int) $deal->id, array( 'ref' => $deal->ref, 'amount' => $row['amount'] ?? $deal->amount ), $user_id, (int) $deal->seller_company_id );
		$payload = array( 'deal_id' => (int) $deal->id, 'note' => $note );
		$type    = array(
			'accept'  => 'trade.accepted',
			'refuse'  => 'trade.refused',
			'cancel'  => 'trade.cancelled',
			'counter' => 'trade.countered',
		)[ $action ];
		if ( 'buyer' === $other ) {
			Notifications::notify_user( (int) $deal->buyer_user_id, $type, $payload, (int) $deal->buyer_company_id );
		} else {
			Notifications::notify_company( (int) $deal->seller_company_id, $type, $payload );
		}
		return true;
	}

	/** Open deals without an answer for EXPIRY_DAYS days are closed (daily task). */
	public static function expire_old() {
		global $wpdb;
		$t     = self::table();
		$limit = gmdate( 'Y-m-d H:i:s', time() - self::EXPIRY_DAYS * DAY_IN_SECONDS );
		$ids   = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$t} WHERE status = 'open' AND updated_at < %s", $limit ) ); // phpcs:ignore WordPress.DB
		foreach ( $ids as $id ) {
			$deal = self::get( $id );
			$now  = self::now();
			$wpdb->update( $t, array( 'status' => 'expired', 'turn' => '', 'closed_at' => $now, 'updated_at' => $now ), array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::log( (int) $id, 'system', 0, 'expired', (float) $deal->amount, '' );
			Notifications::notify_user( (int) $deal->buyer_user_id, 'trade.expired', array( 'deal_id' => (int) $id ), (int) $deal->buyer_company_id );
			Notifications::notify_company( (int) $deal->seller_company_id, 'trade.expired', array( 'deal_id' => (int) $id ) );
		}
		return count( $ids );
	}

	public static function log( $deal_id, $actor, $user_id, $action, $amount = 0, $note = '' ) {
		global $wpdb;
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::events_table(),
			array(
				'deal_id'    => (int) $deal_id,
				'actor'      => $actor,
				'user_id'    => (int) $user_id,
				'action'     => $action,
				'amount'     => round( (float) $amount, 2 ),
				'note'       => mb_substr( (string) $note, 0, 2000 ),
				'created_at' => self::now(),
			)
		);
	}

	/** Steps of a deal, oldest first. */
	public static function events( $deal_id ) {
		global $wpdb;
		$t = self::events_table();
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE deal_id = %d ORDER BY id ASC", (int) $deal_id ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Deals.
	 *
	 * @param array $a buyer (user id), seller (company id), status (string|array), kind, market,
	 *                 from, to (Y-m-d), q (ref, title, buyer), per_page (0 = all), page.
	 * @return array{items: object[], total: int, pages: int}
	 */
	public static function query( array $a = array() ) {
		global $wpdb;
		$a     = wp_parse_args( $a, array( 'per_page' => 20, 'page' => 1 ) );
		$where = array( '1=1' );
		$args  = array();
		if ( ! empty( $a['buyer'] ) ) {
			$where[] = 'buyer_user_id = %d';
			$args[]  = (int) $a['buyer'];
		}
		if ( ! empty( $a['seller'] ) ) {
			$where[] = 'seller_company_id = %d';
			$args[]  = (int) $a['seller'];
		}
		if ( ! empty( $a['status'] ) ) {
			$st      = array_values( array_intersect( (array) $a['status'], self::STATUSES ) );
			$st      = $st ? $st : array( 'none' );
			$where[] = 'status IN (' . implode( ',', array_fill( 0, count( $st ), '%s' ) ) . ')';
			$args    = array_merge( $args, $st );
		}
		foreach ( array( 'kind', 'market' ) as $k ) {
			if ( ! empty( $a[ $k ] ) ) {
				$where[] = "$k = %s";
				$args[]  = (string) $a[ $k ];
			}
		}
		if ( ! empty( $a['from'] ) ) {
			$where[] = 'created_at >= %s';
			$args[]  = $a['from'] . ' 00:00:00';
		}
		if ( ! empty( $a['to'] ) ) {
			$where[] = 'created_at <= %s';
			$args[]  = $a['to'] . ' 23:59:59';
		}
		if ( ! empty( $a['q'] ) ) {
			$like    = '%' . $wpdb->esc_like( (string) $a['q'] ) . '%';
			$where[] = '(ref LIKE %s OR title LIKE %s OR buyer_name LIKE %s OR buyer_email LIKE %s)';
			$args    = array_merge( $args, array( $like, $like, $like, $like ) );
		}
		$t     = self::table();
		$w     = implode( ' AND ', $where );
		$total = (int) $wpdb->get_var( $args ? $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE {$w}", $args ) : "SELECT COUNT(*) FROM {$t} WHERE {$w}" ); // phpcs:ignore WordPress.DB
		$per   = (int) $a['per_page'];
		$sql   = "SELECT * FROM {$t} WHERE {$w} ORDER BY CASE WHEN status = 'open' THEN 0 ELSE 1 END, updated_at DESC, id DESC";
		if ( $per > 0 ) {
			$sql   .= ' LIMIT %d OFFSET %d';
			$args[] = $per;
			$args[] = ( max( 1, (int) $a['page'] ) - 1 ) * $per;
		}
		$rows = $args ? $wpdb->get_results( $wpdb->prepare( $sql, $args ) ) : $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB
		return array(
			'items' => $rows,
			'total' => $total,
			'pages' => $per > 0 ? (int) ceil( $total / $per ) : 1,
		);
	}

	/** Open deals waiting for an answer: { seller: of this company, buyer: of this user }. */
	public static function waiting( $company_id, $user_id ) {
		global $wpdb;
		$t = self::table();
		return array(
			'seller' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE status = 'open' AND seller_company_id = %d AND turn = 'seller'", (int) $company_id ) ), // phpcs:ignore WordPress.DB
			'buyer'  => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE status = 'open' AND buyer_user_id = %d AND turn = 'buyer'", (int) $user_id ) ), // phpcs:ignore WordPress.DB
		);
	}

	/**
	 * Contact details of the seller, given to the buyer once the deal is accepted
	 * (also when the seller's plan hides them on the public pages).
	 */
	public static function seller_contacts( $deal ) {
		if ( ! $deal || 'accepted' !== $deal->status ) {
			return null;
		}
		$c = Companies::get( (int) $deal->seller_company_id );
		if ( ! $c ) {
			return null;
		}
		$owners = Companies::member_ids( (int) $c->id, array( 'owner' ) );
		$owners = $owners ? $owners : Companies::member_ids( (int) $c->id );
		$owner  = $owners ? get_userdata( (int) $owners[0] ) : null;
		return array(
			'name'     => (string) $c->name,
			'phone'    => (string) ( $c->phone ?: ( $owner ? get_user_meta( $owner->ID, 'sm_phone', true ) : '' ) ),
			'whatsapp' => (string) ( $c->whatsapp ?? '' ),
			'email'    => (string) ( $c->email ?: ( $owner ? $owner->user_email : '' ) ),
			'address'  => trim( (string) ( $c->address ?? '' ) ),
		);
	}
}
