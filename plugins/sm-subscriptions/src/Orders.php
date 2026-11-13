<?php
/**
 * Orders: a plan, an AI credit pack, a listing option or (0.2.0) what another
 * extension sells through the filter sm_sub_order_quote, paid outside the site.
 *
 * Steps: awaiting (payment details shown) → review (proof of payment sent)
 * → paid (checked by the platform team: invoice number, the plan / credits /
 * option are given) or rejected (reason sent). An awaiting order can be cancelled.
 *
 * Prices are tax included; the invoice splits the amount before tax and the TVA.
 * The online payment (Chargily Pay: CIB, Edahabia) will mark orders paid through
 * the same Orders::approve() once its gateway is added.
 *
 * @package SM\Subscriptions
 */

namespace SM\Subscriptions;

use SM\Core\Accounts\Companies;

defined( 'ABSPATH' ) || exit;

final class Orders {

	const STATUSES = array( 'awaiting', 'review', 'paid', 'rejected', 'cancelled' );

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sm_sub_orders';
	}

	private static function now() {
		return current_time( 'mysql', true );
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * What can be ordered by a company: kind plan (item = plan code, period month|year),
	 * pack (item = pack code), option (item = bump|urgent|featured, target = product id).
	 * Returns { label, amount, months, credits, days } or a WP_Error.
	 */
	public static function quote( $company, $kind, $item, $period = '', $target = 0 ) {
		$s = Settings::all();
		if ( 'plan' === $kind ) {
			$plan = Plans::get( $company->account_class, $item );
			if ( ! $plan || ! in_array( $period, array( 'month', 'year' ), true ) || ( 'month' === $period ? $plan['price_month'] : $plan['price_year'] ) <= 0 ) {
				return new \WP_Error( 'plan', __( 'This plan cannot be ordered.', 'sm-subscriptions' ) );
			}
			$amount = 'month' === $period ? $plan['price_month'] : $plan['price_year'];
			$locked = Founders::locked_price( (int) $company->id, $item, $period );
			if ( null !== $locked && $locked < $amount ) {
				$amount = $locked; // Founding member: the price of the offer stays.
			}
			return array(
				/* translators: 1: plan name, 2: "1 month" or "1 year". */
				'label'  => sprintf( __( 'Plan %1$s — %2$s', 'sm-subscriptions' ), Plans::name( $item, $company->account_class ), 'month' === $period ? __( '1 month', 'sm-subscriptions' ) : __( '1 year', 'sm-subscriptions' ) ),
				'amount' => (float) $amount,
				'months' => 'month' === $period ? 1 : 12,
			);
		}
		if ( 'pack' === $kind ) {
			foreach ( (array) $s['packs'] as $p ) {
				if ( $p['code'] === $item ) {
					/* translators: %d: number of AI credits. */
					return array( 'label' => sprintf( __( '%d AI credits', 'sm-subscriptions' ), $p['credits'] ), 'amount' => (float) $p['price'], 'credits' => (int) $p['credits'] );
				}
			}
			return new \WP_Error( 'pack', __( 'This credit pack does not exist.', 'sm-subscriptions' ) );
		}
		if ( 'option' === $kind ) {
			$p = Compat::ready( 'products' ) ? \SM\Products\Products::get( (int) $target ) : null;
			if ( ! $p || (int) $p->company_id !== (int) $company->id || 'published' !== $p->status ) {
				return new \WP_Error( 'option', __( 'Options are for your listings online.', 'sm-subscriptions' ) );
			}
			$title = \SM\Products\Presenter::title_text( $p );
			$map   = array(
				/* translators: %s: listing title. */
				'bump'     => array( sprintf( __( 'Back to the top: %s', 'sm-subscriptions' ), $title ), $s['opt_bump'], 0 ),
				/* translators: 1: listing title, 2: number of days. */
				'urgent'   => array( sprintf( __( '"Urgent" badge, %2$d days: %1$s', 'sm-subscriptions' ), $title, $s['opt_urgent_days'] ), $s['opt_urgent'], $s['opt_urgent_days'] ),
				/* translators: 1: listing title, 2: number of days. */
				'featured' => array( sprintf( __( 'Featured listing, %2$d days: %1$s', 'sm-subscriptions' ), $title, $s['opt_featured_days'] ), $s['opt_featured'], $s['opt_featured_days'] ),
			);
			if ( ! isset( $map[ $item ] ) || $map[ $item ][1] <= 0 ) {
				return new \WP_Error( 'option', __( 'This option is not offered.', 'sm-subscriptions' ) );
			}
			return array( 'label' => mb_substr( $map[ $item ][0], 0, 200 ), 'amount' => (float) $map[ $item ][1], 'days' => (int) $map[ $item ][2] );
		}
		/**
		 * Orders of other extensions (0.2.0), e.g. the advance and the balance of an
		 * engineering study (sm-engineering). Return { label, amount (tax included),
		 * tva_rate (optional: the rate of that sale) } or a WP_Error.
		 */
		$ext = apply_filters( 'sm_sub_order_quote', null, $company, $kind, $item, $period, $target );
		if ( is_array( $ext ) || is_wp_error( $ext ) ) {
			return $ext;
		}
		return new \WP_Error( 'kind', __( 'Unknown order.', 'sm-subscriptions' ) );
	}

	/**
	 * Label of an order in the language of the current page (the stored label
	 * keeps the language of the person who ordered; the invoice uses it as issued).
	 */
	public static function display_label( $order ) {
		$s = Settings::all();
		if ( 'plan' === $order->kind ) {
			$company = Companies::get( (int) $order->company_id );
			if ( $company && in_array( $order->period, array( 'month', 'year' ), true ) ) {
				/* translators: 1: plan name, 2: "1 month" or "1 year". */
				return sprintf( __( 'Plan %1$s — %2$s', 'sm-subscriptions' ), Plans::name( $order->item, $company->account_class ), 'month' === $order->period ? __( '1 month', 'sm-subscriptions' ) : __( '1 year', 'sm-subscriptions' ) );
			}
		} elseif ( 'pack' === $order->kind ) {
			foreach ( (array) $s['packs'] as $p ) {
				if ( $p['code'] === $order->item ) {
					/* translators: %d: number of AI credits. */
					return sprintf( __( '%d AI credits', 'sm-subscriptions' ), $p['credits'] );
				}
			}
		} elseif ( 'option' === $order->kind && Compat::ready( 'products' ) ) {
			$p = \SM\Products\Products::get( (int) $order->target_id );
			if ( $p ) {
				$title = \SM\Products\Presenter::title_text( $p );
				$days  = 'urgent' === $order->item ? (int) $s['opt_urgent_days'] : (int) $s['opt_featured_days'];
				$map   = array(
					/* translators: %s: listing title. */
					'bump'     => sprintf( __( 'Back to the top: %s', 'sm-subscriptions' ), $title ),
					/* translators: 1: listing title, 2: number of days. */
					'urgent'   => sprintf( __( '"Urgent" badge, %2$d days: %1$s', 'sm-subscriptions' ), $title, $days ),
					/* translators: 1: listing title, 2: number of days. */
					'featured' => sprintf( __( 'Featured listing, %2$d days: %1$s', 'sm-subscriptions' ), $title, $days ),
				);
				if ( isset( $map[ $order->item ] ) ) {
					return $map[ $order->item ];
				}
			}
		}
		return (string) apply_filters( 'sm_sub_order_label', (string) $order->label, $order );
	}

	/** Create an order (status awaiting). @return int|\WP_Error */
	public static function create( $company, $user_id, $kind, $item, $period = '', $target = 0 ) {
		global $wpdb;
		$q = self::quote( $company, $kind, $item, $period, $target );
		if ( is_wp_error( $q ) ) {
			return $q;
		}
		$rate = isset( $q['tva_rate'] ) ? max( 0, (float) $q['tva_rate'] ) : (float) Settings::get( 'tva_rate' );
		$wpdb->insert(
			self::table(),
			array(
				'company_id' => (int) $company->id,
				'user_id'    => (int) $user_id,
				'kind'       => $kind,
				'item'       => $item,
				'period'     => 'plan' === $kind ? $period : '',
				'target_id'  => (int) $target,
				'label'      => $q['label'],
				'amount_ttc' => round( $q['amount'], 2 ),
				'tva_rate'   => $rate,
				'amount_ht'  => round( $q['amount'] / ( 1 + $rate / 100 ), 2 ),
				'status'     => 'awaiting',
				'created_at' => self::now(),
			)
		);
		$id = (int) $wpdb->insert_id;
		$wpdb->update( self::table(), array( 'ref' => sprintf( 'CMD-%s-%05d', gmdate( 'Y' ), $id ) ), array( 'id' => $id ) );
		return $id;
	}

	/**
	 * The customer sent the proof of payment. $file: private file stored by
	 * Proofs::store() { path, type, original }, or an empty array (tests).
	 */
	public static function send_proof( $order, $file, $method, $note ) {
		global $wpdb;
		$wpdb->update(
			self::table(),
			array(
				'status'     => 'review',
				'proof_file' => $file ? wp_json_encode( $file ) : '',
				'method'     => sanitize_key( $method ),
				'payer_note' => mb_substr( (string) $note, 0, 1000 ),
				'proof_at'   => self::now(),
			),
			array( 'id' => (int) $order->id )
		);
		do_action( 'sm_sub_order_review', self::get( $order->id ) );
	}

	public static function cancel( $order ) {
		global $wpdb;
		$wpdb->update( self::table(), array( 'status' => 'cancelled' ), array( 'id' => (int) $order->id ) );
	}

	/** Next invoice number of the year: FAC-2026-00001. */
	private static function next_invoice_no() {
		$year = gmdate( 'Y' );
		$key  = 'sm_sub_invoice_seq_' . $year;
		$n    = (int) get_option( $key, 0 ) + 1;
		update_option( $key, $n, false );
		return sprintf( 'FAC-%s-%05d', $year, $n );
	}

	/**
	 * Payment checked: invoice number, billing snapshot, then the plan, the
	 * credits or the option are given. $method: how it was paid (admin may set it).
	 */
	public static function approve( $order, $method = '' ) {
		global $wpdb;
		if ( ! in_array( $order->status, array( 'awaiting', 'review' ), true ) ) {
			return new \WP_Error( 'status', __( 'This order is already closed.', 'sm-subscriptions' ) );
		}
		$company = Companies::get( (int) $order->company_id );
		if ( ! $company ) {
			return new \WP_Error( 'company', __( 'The account of this order no longer exists.', 'sm-subscriptions' ) );
		}
		$s       = Settings::all();
		$billing = array(
			'seller' => array_intersect_key( $s, array_flip( array( 'legal_name', 'rc', 'nif', 'nis', 'art', 'address' ) ) ),
			'buyer'  => array(
				'name'    => $company->name,
				'address' => trim( (string) ( $company->address ?? '' ) . ' ' . (string) ( $company->commune ?? '' ) ),
				'wilaya'  => (string) $company->wilaya,
				'rc'      => (string) ( $company->rc_number ?? '' ),
				'nif'     => (string) ( $company->nif ?? '' ),
				'nis'     => (string) ( $company->nis ?? '' ),
			),
		);
		$wpdb->update(
			self::table(),
			array(
				'status'     => 'paid',
				'method'     => $method ? sanitize_key( $method ) : $order->method,
				'invoice_no' => self::next_invoice_no(),
				'billing'    => wp_json_encode( $billing ),
				'paid_at'    => self::now(),
			),
			array( 'id' => (int) $order->id )
		);
		$order = self::get( $order->id );
		self::fulfil( $order, $company );
		do_action( 'sm_sub_order_paid', $order );
		return true;
	}

	/** Give what was paid for. */
	private static function fulfil( $order, $company ) {
		$q = self::quote( $company, $order->kind, $order->item, $order->period, $order->target_id );
		if ( 'plan' === $order->kind ) {
			Subscriptions::activate( (int) $company->id, $order->item, 'month' === $order->period ? 1 : 12, array( 'period' => $order->period, 'price' => $order->amount_ttc, 'order_id' => $order->id ) );
		} elseif ( 'pack' === $order->kind && ! is_wp_error( $q ) ) {
			Credits::add( (int) $company->id, (int) $q['credits'], 'pack', (int) $order->id );
		} elseif ( 'option' === $order->kind && Compat::ready( 'products' ) ) {
			$days = 'urgent' === $order->item ? (int) Settings::get( 'opt_urgent_days' ) : (int) Settings::get( 'opt_featured_days' );
			\SM\Products\Products::boost( (int) $order->target_id, $order->item, $days );
		}
	}

	public static function reject( $order, $reason ) {
		global $wpdb;
		$wpdb->update( self::table(), array( 'status' => 'rejected', 'reject_reason' => mb_substr( (string) $reason, 0, 1000 ) ), array( 'id' => (int) $order->id ) );
		do_action( 'sm_sub_order_rejected', self::get( $order->id ) );
	}

	/** Orders: company (int), status (string|array), limit. */
	public static function query( array $a = array() ) {
		global $wpdb;
		$a     = wp_parse_args( $a, array( 'company' => 0, 'status' => '', 'limit' => 100 ) );
		$where = array( '1=1' );
		$args  = array();
		if ( $a['company'] ) {
			$where[] = 'company_id = %d';
			$args[]  = (int) $a['company'];
		}
		if ( $a['status'] ) {
			$st      = (array) $a['status'];
			$where[] = 'status IN (' . implode( ',', array_fill( 0, count( $st ), '%s' ) ) . ')';
			$args    = array_merge( $args, $st );
		}
		$args[] = (int) $a['limit'];
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE ' . implode( ' AND ', $where ) . " ORDER BY CASE status WHEN 'review' THEN 0 WHEN 'awaiting' THEN 1 ELSE 2 END, id DESC LIMIT %d", $args ) ); // phpcs:ignore WordPress.DB
	}

	public static function count_status( $status ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE status = %s', $status ) ); // phpcs:ignore WordPress.DB
	}

	/** Translated status labels. */
	public static function status_labels() {
		return array(
			'awaiting'  => __( 'Waiting for payment', 'sm-subscriptions' ),
			'review'    => __( 'Payment being checked', 'sm-subscriptions' ),
			'paid'      => __( 'Paid', 'sm-subscriptions' ),
			'rejected'  => __( 'Payment not accepted', 'sm-subscriptions' ),
			'cancelled' => __( 'Cancelled', 'sm-subscriptions' ),
		);
	}
}
