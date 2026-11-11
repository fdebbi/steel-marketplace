<?php
/**
 * Engineering missions and their workflow.
 *
 *   submitted ──quote──▶ quoted ──accept (client)──▶ accepted ──assign──▶ assigned
 *       │                  │ └─refuse (client)─▶ quote_declined          │ └─refuse (engineer)─▶ accepted
 *       └─decline──▶ declined                                            ▼ take (engineer)
 *   cancel (client, before acceptance) ─▶ cancelled                 in_progress ◀─rework─┐
 *                                                                        ▼ submit        │
 *                                                                      review ───────────┘
 *                                                                        ▼ deliver
 *                                                                    delivered ─confirm / close─▶ closed
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
		foreach ( array( 'budget', 'quote_amount', 'engineer_fee' ) as $k ) {
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
				'wilaya'       => $d['wilaya'],
				'needed_by'    => $d['needed_by'],
				'budget'       => $d['budget'],
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
				$amount = (float) ( $a['amount'] ?? 0 );
				if ( $amount <= 0 ) {
					return new \WP_Error( 'amount', __( 'Enter the price of the study for the client.', 'sm-engineering' ) );
				}
				$days  = max( 0, (int) ( $a['days'] ?? 0 ) );
				$valid = gmdate( 'Y-m-d', time() + DAY_IN_SECONDS * (int) Settings::get( 'quote_valid_days' ) );
				return self::update( $m->id, array( 'status' => 'quoted', 'quote_amount' => $amount, 'quote_days' => $days, 'quote_message' => (string) ( $a['message'] ?? '' ), 'quote_valid_until' => $valid, 'quoted_at' => $now ), 'quoted', array( 'amount' => $amount, 'days' => $days ) );
			case 'decline':
				if ( '' === trim( (string) ( $a['reason'] ?? '' ) ) ) {
					return new \WP_Error( 'reason', __( 'Give the reason: the client reads it.', 'sm-engineering' ) );
				}
				return self::update( $m->id, array( 'status' => 'declined', 'reason' => $a['reason'], 'closed_at' => $now ), 'declined' );
			case 'accept':
				if ( $m->quote_valid_until && $m->quote_valid_until < gmdate( 'Y-m-d' ) ) {
					return new \WP_Error( 'expired', __( 'This quote has expired. Write to the platform to get an updated one.', 'sm-engineering' ) );
				}
				return self::update( $m->id, array( 'status' => 'accepted', 'accepted_at' => $now ), 'accepted', array( 'amount' => $m->quote_amount ) );
			case 'refuse':
				return self::update( $m->id, array( 'status' => 'quote_declined', 'reason' => (string) ( $a['reason'] ?? '' ), 'closed_at' => $now ), 'quote_declined' );
			case 'cancel':
				return self::update( $m->id, array( 'status' => 'cancelled', 'reason' => (string) ( $a['reason'] ?? '' ), 'closed_at' => $now ), 'cancelled' );
			case 'assign':
				$eng = (int) ( $a['engineer_id'] ?? 0 );
				$fee = (float) ( $a['fee'] ?? 0 );
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
				Files::set_visibility( $m->id, 'deliverable', true );
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
