<?php
/**
 * Engineering missions and their workflow.
 *
 *   submitted ──quote──▶ quoted ──accept (client)──▶ accepted ──assign──▶ assigned
 *       │                  │ └─refuse (client)─▶ quote_declined          │ └─refuse (engineer)─▶ accepted
 *       └─decline──▶ declined                   (advance paid first)     ▼ take (engineer)
 *   cancel (client, before acceptance) ─▶ cancelled                 in_progress ◀─rework─┐
 *                                                                        ▼ submit        │
 *                                                                      review ───────────┘
 *                                                                        ▼ deliver
 *                                                                    delivered ─confirm / close─▶ closed
 *
 * 0.3.0: a request covers one or more engineering services (with their ticked
 * prestations) and a type of project. The quote gives a price per service (before
 * tax), the TVA of the platform settings, the total and an advance (30 % by
 * default). Once the client accepts, the advance is paid (order of SM
 * Subscriptions) before the mission is given to an engineer; the deliverables are
 * shown to the client once the balance is paid. Quotes are valid 15 days; a daily
 * task reminds the client the day before, reminds an unpaid advance after 3 days
 * and cancels an accepted quote whose advance is still unpaid at its end.
 *
 * The platform sets the client's price (quote) and the engineer's fee
 * separately; the client never sees the fee nor the engineer, the engineer
 * never sees the client's price nor the client's contact details.
 *
 * @package SM\Engineering
 */

namespace SM\Engineering;

use SM\Core\Audit\AuditLog;

defined( 'ABSPATH' ) || exit;

final class Missions {

	const STATUSES = array( 'submitted', 'quoted', 'accepted', 'assigned', 'in_progress', 'review', 'delivered', 'closed', 'declined', 'quote_declined', 'cancelled' );
	const OPEN     = array( 'submitted', 'quoted', 'accepted', 'assigned', 'in_progress', 'review', 'delivered' );

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sm_missions';
	}

	private static function hydrate( $r ) {
		foreach ( array( 'id', 'client_id', 'client_user', 'preferred_id', 'engineer_id', 'quote_days', 'engineer_days' ) as $k ) {
			$r->$k = (int) $r->$k;
		}
		foreach ( array( 'budget', 'quote_amount', 'engineer_fee', 'amount_ht', 'tva_rate', 'advance_pct', 'advance_amount' ) as $k ) {
			if ( ! isset( $r->$k ) ) {
				$r->$k = 0;
			}
			$r->$k = (float) $r->$k;
		}
		$r->ref = self::ref( $r );
		return $r;
	}

	/** Reference shown to everyone: ET-2026-0042. */
	public static function ref( $m ) {
		return sprintf( 'ET-%s-%04d', substr( (string) $m->created_at, 0, 4 ), (int) $m->id );
	}

	public static function get( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) ); // phpcs:ignore WordPress.DB
		return $row ? self::hydrate( $row ) : null;
	}

	public static function create( $client_id, $client_user, array $d ) {
		global $wpdb;
		$now = current_time( 'mysql', true );
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'client_id'    => (int) $client_id,
				'client_user'  => (int) $client_user,
				'type'         => $d['type'],
				'title'        => $d['title'],
				'description'  => $d['description'],
				'prestations'  => wp_json_encode( array_values( (array) ( $d['prestations'] ?? array() ) ), JSON_UNESCAPED_UNICODE ),
				'services'     => wp_json_encode( array_values( (array) ( $d['services'] ?? array() ) ), JSON_UNESCAPED_UNICODE ),
				'project_type' => (string) ( $d['project_type'] ?? '' ),
				'wilaya'       => $d['wilaya'],
				'needed_by'    => $d['needed_by'],
				'budget'       => (float) ( $d['budget'] ?? 0 ),
				'preferred_id' => (int) $d['preferred_id'],
				'status'       => 'submitted',
				'created_at'   => $now,
				'updated_at'   => $now,
			)
		);
		$id = (int) $wpdb->insert_id;
		AuditLog::add( 'mission.submitted', 'mission', $id, array( 'type' => $d['type'], 'preferred' => (int) $d['preferred_id'] ), $client_user, $client_id );
		return $id;
	}

	/** Prestations ticked by the client, in the visitor's language (copy kept with the request). */
	public static function prestations( $m ) {
		$out = array();
		foreach ( self::lines( $m ) as $l ) {
			$out = array_merge( $out, $l['labels'] );
		}
		return $out;
	}

	/**
	 * Services of a request with their prestations and their price before tax.
	 * Requests made before 0.3.0 have one service (type + prestations).
	 *
	 * @return array list of { code, fr, ar, title, items: list of {fr, ar}, labels: string[], amount }
	 */
	public static function lines( $m ) {
		$ar   = 'ar' === sm_current_language();
		$list = json_decode( (string) ( $m->services ?? '' ), true );
		if ( ! is_array( $list ) || ! $list ) {
			$types = Settings::types( true );
			$t     = $types[ $m->type ] ?? array( 'fr' => (string) $m->type, 'ar' => (string) $m->type );
			$items = json_decode( (string) ( $m->prestations ?? '' ), true );
			$list  = array(
				array(
					'code'   => (string) $m->type,
					'fr'     => $t['fr'],
					'ar'     => $t['ar'],
					'items'  => is_array( $items ) ? $items : array(),
					'amount' => (float) ( $m->amount_ht > 0 ? $m->amount_ht : $m->quote_amount ),
				),
			);
		}
		$out = array();
		foreach ( $list as $l ) {
			if ( ! is_array( $l ) ) {
				continue;
			}
			$items = array_values( array_filter( (array) ( $l['items'] ?? array() ), 'is_array' ) );
			$out[] = array(
				'code'   => (string) ( $l['code'] ?? '' ),
				'fr'     => (string) ( $l['fr'] ?? '' ),
				'ar'     => (string) ( $l['ar'] ?? '' ),
				'title'  => $ar && '' !== (string) ( $l['ar'] ?? '' ) ? (string) $l['ar'] : (string) ( $l['fr'] ?? '' ),
				'items'  => $items,
				'labels' => array_values( array_filter( array_map( static fn( $p ) => (string) ( $ar && '' !== (string) ( $p['ar'] ?? '' ) ? $p['ar'] : ( $p['fr'] ?? '' ) ), $items ) ) ),
				'amount' => (float) ( $l['amount'] ?? 0 ),
			);
		}
		return $out;
	}

	/** Codes of the services of a request. */
	public static function codes( $m ) {
		return array_values( array_filter( array_column( self::lines( $m ), 'code' ) ) );
	}

	/** Names of the services, for lists and e-mails ("Note de Calcul + Expertise…"). */
	public static function services_label( $m ) {
		return implode( ' + ', array_column( self::lines( $m ), 'title' ) );
	}

	/**
	 * Money of the quote: before tax, TVA, total, advance and balance, and what is paid.
	 */
	public static function totals( $m ) {
		$ttc     = (float) $m->quote_amount;
		$ht      = $m->amount_ht > 0 ? (float) $m->amount_ht : $ttc;
		$rate    = (float) $m->tva_rate;
		$advance = (float) $m->advance_amount;
		$paid_a  = $advance <= 0 || ! empty( $m->advance_paid_at );
		$paid_b  = ! empty( $m->client_paid_at );
		// Quotes sent before 0.3.0 have no price before tax: their payment is recorded by hand, as before.
		$legacy  = $m->amount_ht <= 0 && $ttc > 0;
		if ( $legacy ) {
			return array( 'ht' => $ttc, 'tva_rate' => 0.0, 'tva' => 0.0, 'ttc' => $ttc, 'advance_pct' => 0.0, 'advance' => 0.0, 'balance' => $ttc, 'advance_paid' => true, 'balance_paid' => true, 'advance_due' => false, 'balance_due' => false, 'legacy' => true );
		}
		$started = in_array( $m->status, array( 'accepted', 'assigned', 'in_progress', 'review', 'delivered', 'closed' ), true );
		return array(
			'ht'           => $ht,
			'tva_rate'     => $rate,
			'tva'          => max( 0, $ttc - $ht ),
			'ttc'          => $ttc,
			'advance_pct'  => (float) $m->advance_pct,
			'advance'      => $advance,
			'balance'      => max( 0, $ttc - $advance ),
			'advance_paid' => $paid_a,
			'balance_paid' => $paid_b || $ttc - $advance <= 0,
			'advance_due'  => $started && ! $paid_a && ! $paid_b,
			// The balance is asked once the study is delivered (documents released after it).
			'balance_due'  => in_array( $m->status, array( 'delivered', 'closed' ), true ) && $paid_a && ! $paid_b && $ttc - $advance > 0,
			'legacy'       => false,
		);
	}

	/** Amounts of a quote from the prices of the services (before tax). */
	public static function compute( array $amounts, $rate, $pct ) {
		$ht  = round( array_sum( array_map( 'floatval', $amounts ) ), 2 );
		$ttc = round( $ht * ( 1 + max( 0, (float) $rate ) / 100 ) );
		$pct = max( 0, min( 100, (float) $pct ) );
		return array(
			'ht'      => $ht,
			'ttc'     => (float) $ttc,
			'advance' => (float) round( $ttc * $pct / 100 ),
			'pct'     => $pct,
		);
	}

	/**
	 * A payment of the client is confirmed (order paid, or recorded by the platform).
	 *
	 * @param string $what advance|balance
	 */
	public static function payment_received( $m, $what ) {
		if ( ! $m ) {
			return new \WP_Error( 'mission', __( 'Mission not found.', 'sm-engineering' ) );
		}
		$now = current_time( 'mysql', true );
		$t   = self::totals( $m );
		if ( 'advance' === $what ) {
			if ( ! empty( $m->advance_paid_at ) ) {
				return $m;
			}
			$row = array( 'advance_paid_at' => $now );
			if ( $t['balance'] <= 0 ) {
				$row['client_paid_at'] = $now; // The advance was the whole price.
			}
			return self::update( $m->id, $row, 'advance_paid', array( 'amount' => $t['advance'] ) );
		}
		if ( ! empty( $m->client_paid_at ) ) {
			return $m;
		}
		$row = array( 'client_paid_at' => $now );
		if ( empty( $m->advance_paid_at ) ) {
			$row['advance_paid_at'] = $now;
		}
		if ( in_array( $m->status, array( 'delivered', 'closed' ), true ) ) {
			Files::set_visibility( $m->id, 'deliverable', true );
		}
		return self::update( $m->id, $row, 'balance_paid', array( 'amount' => $t['balance'] ) );
	}

	/**
	 * Daily task: reminder the day before a quote ends, reminder of an unpaid
	 * advance, cancellation of an accepted quote whose advance is unpaid at its end.
	 */
	public static function daily() {
		global $wpdb;
		$t        = self::table();
		$today    = gmdate( 'Y-m-d' );
		$tomorrow = gmdate( 'Y-m-d', time() + DAY_IN_SECONDS );
		$now      = current_time( 'mysql', true );
		// 1. Quotes ending tomorrow (or today), not yet reminded.
		foreach ( (array) $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$t} WHERE status = 'quoted' AND quote_reminded_at IS NULL AND quote_valid_until IS NOT NULL AND quote_valid_until >= %s AND quote_valid_until <= %s", $today, $tomorrow ) ) as $id ) { // phpcs:ignore WordPress.DB
			self::update( (int) $id, array( 'quote_reminded_at' => $now ), 'quote_reminder' );
		}
		// 2. and 3. Accepted quotes whose advance is not paid.
		$days = max( 1, (int) Settings::get( 'pay_reminder_days' ) );
		$rows = $wpdb->get_results( "SELECT * FROM {$t} WHERE status = 'accepted' AND advance_amount > 0 AND advance_paid_at IS NULL AND client_paid_at IS NULL" ); // phpcs:ignore WordPress.DB
		foreach ( (array) $rows as $r ) {
			$m = self::hydrate( $r );
			if ( $m->quote_valid_until && $m->quote_valid_until < $today ) {
				if ( Payments::has_proof( $m ) ) {
					continue; // A proof of payment is being checked by the team: not cancelled.
				}
				Payments::cancel_open( $m );
				self::update( $m->id, array( 'status' => 'cancelled', 'reason' => __( 'The advance was not paid before the end of the quote.', 'sm-engineering' ), 'closed_at' => $now ), 'expired_unpaid' );
				continue;
			}
			if ( ! $m->pay_reminded_at && $m->accepted_at && strtotime( $m->accepted_at . ' UTC' ) <= time() - $days * DAY_IN_SECONDS ) {
				self::update( $m->id, array( 'pay_reminded_at' => $now ), 'payment_reminder' );
			}
		}
	}

	private static function update( $id, array $row, $action, array $log = array() ) {
		global $wpdb;
		$row['updated_at'] = current_time( 'mysql', true );
		$wpdb->update( self::table(), $row, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$m = self::get( $id );
		AuditLog::add( 'mission.' . $action, 'mission', $id, $log, get_current_user_id(), $m ? $m->client_id : 0 );
		do_action( 'sm_mission_' . $action, $m, $log );
		return $m;
	}

	/**
	 * Apply a step of the workflow. Who may do it is checked by the callers
	 * (forms for clients and engineers, wp-admin for the platform).
	 *
	 * @return object|\WP_Error The updated mission.
	 */
	public static function transition( $m, $action, array $a = array() ) {
		$now  = current_time( 'mysql', true );
		$from = array(
			'quote'    => array( 'submitted', 'quoted' ),
			'decline'  => array( 'submitted', 'quoted' ),
			'accept'   => array( 'quoted' ),
			'refuse'   => array( 'quoted' ),
			'cancel'   => array( 'submitted', 'quoted' ),
			'assign'   => array( 'accepted', 'assigned' ),
			'unassign' => array( 'assigned', 'in_progress' ),
			'take'     => array( 'assigned' ),
			'giveup'   => array( 'assigned' ),
			'submit'   => array( 'in_progress' ),
			'rework'   => array( 'review' ),
			'deliver'  => array( 'review' ),
			'confirm'  => array( 'delivered' ),
			'close'    => array( 'delivered' ),
		);
		if ( ! $m || ! isset( $from[ $action ] ) ) {
			return new \WP_Error( 'sm_mission', __( 'Unknown action.', 'sm-engineering' ) );
		}
		if ( ! in_array( $m->status, $from[ $action ], true ) ) {
			return new \WP_Error( 'sm_mission_status', __( 'This step is no longer possible: the mission has moved on. Reload the page.', 'sm-engineering' ) );
		}
		switch ( $action ) {
			case 'quote':
				$lines   = self::lines( $m );
				$amounts = array_values( (array) ( $a['lines'] ?? array() ) );
				if ( ! $amounts && isset( $a['amount'] ) ) {
					$amounts = array( (float) $a['amount'] ); // One price for the whole request.
				}
				foreach ( $lines as $i => $l ) {
					$v = (float) ( $amounts[ $i ] ?? 0 );
					if ( $v <= 0 ) {
						return new \WP_Error( 'amount', __( 'Enter the price of each service for the client.', 'sm-engineering' ) );
					}
					$lines[ $i ]['amount'] = round( $v, 2 );
				}
				$rate  = isset( $a['tva_rate'] ) ? (float) $a['tva_rate'] : Settings::tva_rate();
				$pct   = isset( $a['advance_pct'] ) && '' !== (string) $a['advance_pct'] ? (float) $a['advance_pct'] : (float) Settings::get( 'advance_pct' );
				$c     = self::compute( array_column( $lines, 'amount' ), $rate, $pct );
				$days  = max( 0, (int) ( $a['days'] ?? 0 ) );
				$valid = gmdate( 'Y-m-d', time() + DAY_IN_SECONDS * (int) Settings::get( 'quote_valid_days' ) );
				$store = array_map( static fn( $l ) => array( 'code' => $l['code'], 'fr' => $l['fr'], 'ar' => $l['ar'], 'items' => $l['items'], 'amount' => $l['amount'] ), $lines );
				return self::update(
					$m->id,
					array(
						'status'            => 'quoted',
						'services'          => wp_json_encode( $store, JSON_UNESCAPED_UNICODE ),
						'amount_ht'         => $c['ht'],
						'tva_rate'          => $rate,
						'quote_amount'      => $c['ttc'],
						'advance_pct'       => $c['pct'],
						'advance_amount'    => $c['advance'],
						'quote_days'        => $days,
						'quote_message'     => (string) ( $a['message'] ?? '' ),
						'quote_valid_until' => $valid,
						'quoted_at'         => $now,
						'quote_reminded_at' => null,
					),
					'quoted',
					array( 'amount' => $c['ttc'], 'advance' => $c['advance'], 'days' => $days )
				);
			case 'decline':
				if ( '' === trim( (string) ( $a['reason'] ?? '' ) ) ) {
					return new \WP_Error( 'reason', __( 'Give the reason: the client reads it.', 'sm-engineering' ) );
				}
				return self::update( $m->id, array( 'status' => 'declined', 'reason' => $a['reason'], 'closed_at' => $now ), 'declined' );
			case 'accept':
				if ( $m->quote_valid_until && $m->quote_valid_until < gmdate( 'Y-m-d' ) ) {
					return new \WP_Error( 'expired', __( 'This quote has expired. Write to the platform to get an updated one.', 'sm-engineering' ) );
				}
				return self::update( $m->id, array( 'status' => 'accepted', 'accepted_at' => $now, 'pay_reminded_at' => null ), 'accepted', array( 'amount' => $m->quote_amount, 'advance' => $m->advance_amount ) );
			case 'refuse':
				return self::update( $m->id, array( 'status' => 'quote_declined', 'reason' => (string) ( $a['reason'] ?? '' ), 'closed_at' => $now ), 'quote_declined' );
			case 'cancel':
				return self::update( $m->id, array( 'status' => 'cancelled', 'reason' => (string) ( $a['reason'] ?? '' ), 'closed_at' => $now ), 'cancelled' );
			case 'assign':
				$eng = (int) ( $a['engineer_id'] ?? 0 );
				$fee = (float) ( $a['fee'] ?? 0 );
				if ( self::totals( $m )['advance_due'] ) {
					return new \WP_Error( 'advance', __( 'The advance of the client is not paid yet: the mission can be given to an engineer once it is received.', 'sm-engineering' ) );
				}
				if ( ! Engineers::is_approved( $eng ) ) {
					return new \WP_Error( 'engineer', __( 'Choose an approved engineer.', 'sm-engineering' ) );
				}
				if ( $fee <= 0 ) {
					return new \WP_Error( 'fee', __( 'Enter the fee paid to the engineer.', 'sm-engineering' ) );
				}
				return self::update( $m->id, array( 'status' => 'assigned', 'engineer_id' => $eng, 'engineer_fee' => $fee, 'engineer_days' => max( 0, (int) ( $a['days'] ?? 0 ) ), 'engineer_note' => (string) ( $a['note'] ?? '' ), 'assigned_at' => $now, 'fee_status' => 'none' ), 'assigned', array( 'engineer_id' => $eng, 'fee' => $fee, 'previous' => $m->engineer_id ) );
			case 'unassign':
				return self::update( $m->id, array( 'status' => 'accepted', 'engineer_id' => 0, 'fee_status' => 'none', 'started_at' => null ), 'unassigned', array( 'engineer_id' => $m->engineer_id ) );
			case 'take':
				return self::update( $m->id, array( 'status' => 'in_progress', 'started_at' => $now, 'fee_status' => 'planned' ), 'started', array( 'engineer_id' => $m->engineer_id ) );
			case 'giveup':
				return self::update( $m->id, array( 'status' => 'accepted', 'engineer_id' => 0, 'reason' => (string) ( $a['reason'] ?? '' ) ), 'engineer_declined', array( 'engineer_id' => $m->engineer_id, 'reason' => (string) ( $a['reason'] ?? '' ) ) );
			case 'submit':
				if ( ! Files::for_mission( $m->id, 'admin', 'deliverable' ) ) {
					return new \WP_Error( 'files', __( 'Add the deliverables (plans, calculation notes, report) before sending.', 'sm-engineering' ) );
				}
				return self::update( $m->id, array( 'status' => 'review', 'submitted_at' => $now ), 'review', array( 'engineer_id' => $m->engineer_id ) );
			case 'rework':
				return self::update( $m->id, array( 'status' => 'in_progress' ), 'rework', array( 'note' => (string) ( $a['note'] ?? '' ) ) );
			case 'deliver':
				// The client sees the deliverables once the balance is paid.
				if ( self::totals( $m )['balance_paid'] ) {
					Files::set_visibility( $m->id, 'deliverable', true );
				}
				return self::update( $m->id, array( 'status' => 'delivered', 'delivered_at' => $now, 'fee_status' => 'due' ), 'delivered', array( 'engineer_id' => $m->engineer_id ) );
			case 'confirm':
			case 'close':
				return self::update( $m->id, array( 'status' => 'closed', 'closed_at' => $now ), 'closed', array( 'by' => $action ) );
		}
		return new \WP_Error( 'sm_mission', __( 'Unknown action.', 'sm-engineering' ) );
	}

	/** Record the payment of the engineer's fee (platform). */
	public static function fee_paid( $m, $reference = '' ) {
		if ( ! $m || 'due' !== $m->fee_status ) {
			return new \WP_Error( 'fee', __( 'The fee of this mission is not due.', 'sm-engineering' ) );
		}
		return self::update( $m->id, array( 'fee_status' => 'paid', 'fee_paid_at' => current_time( 'mysql', true ), 'fee_reference' => mb_substr( sanitize_text_field( $reference ), 0, 80 ) ), 'fee_paid', array( 'engineer_id' => $m->engineer_id, 'fee' => $m->engineer_fee ) );
	}

	/** Record the client's payment (platform). */
	public static function client_paid( $m, $paid ) {
		if ( $paid && ! self::totals( $m )['legacy'] ) {
			return self::payment_received( $m, 'balance' );
		}
		return self::update( $m->id, array( 'client_paid_at' => $paid ? current_time( 'mysql', true ) : null ), $paid ? 'client_paid' : 'client_unpaid' );
	}

	/**
	 * Missions list.
	 *
	 * @param array $args client, engineer, status (string|array), q, per_page, page.
	 */
	public static function query( array $args = array() ) {
		global $wpdb;
		$args  = wp_parse_args( $args, array( 'client' => 0, 'engineer' => 0, 'status' => '', 'q' => '', 'per_page' => 50, 'page' => 1 ) );
		$where = array( '1=1' );
		$vals  = array();
		if ( $args['client'] ) {
			$where[] = 'client_id = %d';
			$vals[]  = (int) $args['client'];
		}
		if ( $args['engineer'] ) {
			$where[] = 'engineer_id = %d';
			$vals[]  = (int) $args['engineer'];
		}
		$status = array_values( array_intersect( (array) $args['status'], self::STATUSES ) );
		if ( $status ) {
			$where[] = 'status IN (' . implode( ',', array_fill( 0, count( $status ), '%s' ) ) . ')';
			$vals    = array_merge( $vals, $status );
		}
		if ( '' !== (string) $args['q'] ) {
			$q = (string) $args['q'];
			if ( preg_match( '/^ET-\d{4}-0*(\d+)$/i', trim( $q ), $mm ) ) {
				$where[] = 'id = %d';
				$vals[]  = (int) $mm[1];
			} else {
				$where[] = '(title LIKE %s OR description LIKE %s)';
				$like    = '%' . $wpdb->esc_like( $q ) . '%';
				array_push( $vals, $like, $like );
			}
		}
		$sql_where = implode( ' AND ', $where );
		$per       = max( 1, min( 200, (int) $args['per_page'] ) );
		$offset    = ( max( 1, (int) $args['page'] ) - 1 ) * $per;
		$t         = self::table();
		$total     = (int) $wpdb->get_var( $vals ? $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE {$sql_where}", $vals ) : "SELECT COUNT(*) FROM {$t} WHERE {$sql_where}" ); // phpcs:ignore WordPress.DB
		$rows      = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} WHERE {$sql_where} ORDER BY updated_at DESC, id DESC LIMIT %d OFFSET %d", array_merge( $vals, array( $per, $offset ) ) ) ); // phpcs:ignore WordPress.DB
		return array( 'items' => array_map( array( __CLASS__, 'hydrate' ), $rows ), 'total' => $total );
	}

	/** Number of missions by status (admin tabs). */
	public static function counts() {
		global $wpdb;
		$out = array_fill_keys( self::STATUSES, 0 );
		foreach ( $wpdb->get_results( 'SELECT status, COUNT(*) n FROM ' . self::table() . ' GROUP BY status' ) as $r ) { // phpcs:ignore WordPress.DB
			$out[ $r->status ] = (int) $r->n;
		}
		return $out;
	}

	/** Missions of a client sent today (rate limit). */
	public static function sent_today( $client_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE client_id = %d AND created_at >= %s', $client_id, gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB
	}

	/** Status as the client sees it (the subcontracting steps are internal). */
	public static function client_status( $status ) {
		$map = array(
			'submitted'      => array( 'review', __( 'Being studied by the platform', 'sm-engineering' ) ),
			'quoted'         => array( 'action', __( 'Quote to accept', 'sm-engineering' ) ),
			'accepted'       => array( 'progress', __( 'Accepted — engineer being appointed', 'sm-engineering' ) ),
			'accepted_pay'   => array( 'action', __( 'Accepted — advance to pay', 'sm-engineering' ) ),
			'delivered_pay'  => array( 'action', __( 'Ready — balance to pay', 'sm-engineering' ) ),
			'assigned'       => array( 'progress', __( 'Accepted — engineer being appointed', 'sm-engineering' ) ),
			'in_progress'    => array( 'progress', __( 'Study in progress', 'sm-engineering' ) ),
			'review'         => array( 'progress', __( 'Study in progress', 'sm-engineering' ) ),
			'delivered'      => array( 'done', __( 'Delivered', 'sm-engineering' ) ),
			'closed'         => array( 'done', __( 'Closed', 'sm-engineering' ) ),
			'declined'       => array( 'off', __( 'Not taken by the platform', 'sm-engineering' ) ),
			'quote_declined' => array( 'off', __( 'Quote declined', 'sm-engineering' ) ),
			'cancelled'      => array( 'off', __( 'Cancelled', 'sm-engineering' ) ),
		);
		return $map[ $status ] ?? array( 'off', $status );
	}

	/** Status as the engineer sees it. */
	public static function engineer_status( $status ) {
		$map = array(
			'assigned'    => array( 'action', __( 'Offered to you', 'sm-engineering' ) ),
			'in_progress' => array( 'progress', __( 'In progress', 'sm-engineering' ) ),
			'review'      => array( 'review', __( 'Sent, being checked by the platform', 'sm-engineering' ) ),
			'delivered'   => array( 'done', __( 'Delivered to the client', 'sm-engineering' ) ),
			'closed'      => array( 'done', __( 'Closed', 'sm-engineering' ) ),
		);
		return $map[ $status ] ?? array( 'off', __( 'No longer assigned to you', 'sm-engineering' ) );
	}

	/** Status in wp-admin. */
	public static function admin_labels() {
		return array(
			'submitted'      => __( 'New request', 'sm-engineering' ),
			'quoted'         => __( 'Quote sent', 'sm-engineering' ),
			'accepted'       => __( 'Accepted, to assign', 'sm-engineering' ),
			'assigned'       => __( 'Offered to an engineer', 'sm-engineering' ),
			'in_progress'    => __( 'In progress', 'sm-engineering' ),
			'review'         => __( 'Deliverables to check', 'sm-engineering' ),
			'delivered'      => __( 'Delivered', 'sm-engineering' ),
			'closed'         => __( 'Closed', 'sm-engineering' ),
			'declined'       => __( 'Declined', 'sm-engineering' ),
			'quote_declined' => __( 'Quote refused', 'sm-engineering' ),
			'cancelled'      => __( 'Cancelled', 'sm-engineering' ),
		);
	}
}
