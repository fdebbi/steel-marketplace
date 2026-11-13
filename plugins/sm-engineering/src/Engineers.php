<?php
/**
 * Partner engineers: the profile of an engineer account and its approval.
 *
 * Status: pending → approved | rejected; approved ↔ suspended. A rejected
 * engineer may correct the application and send it again (back to pending).
 * Only approved engineers receive missions and appear on the public list,
 * which never shows their contact details.
 *
 * @package SM\Engineering
 */

namespace SM\Engineering;

use SM\Core\Accounts\Companies;
use SM\Core\Audit\AuditLog;

defined( 'ABSPATH' ) || exit;

final class Engineers {

	const STATUSES = array( 'pending', 'approved', 'rejected', 'suspended' );

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sm_engineers';
	}

	private static function hydrate( $r ) {
		foreach ( array( 'company_id', 'user_id', 'grad_year', 'experience', 'available', 'verification_id' ) as $k ) {
			$r->$k = (int) $r->$k;
		}
		$r->specialties = array_values( array_filter( (array) json_decode( (string) $r->specialties, true ) ) );
		return $r;
	}

	public static function get( $company_id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE company_id = %d', $company_id ) ); // phpcs:ignore WordPress.DB
		return $row ? self::hydrate( $row ) : null;
	}

	/** Engineer profile of a user (null when the user is not a partner engineer). */
	public static function for_user( $user_id ) {
		$c = Companies::for_user( $user_id );
		return ( $c && 'engineer' === $c->account_class ) ? self::get( $c->id ) : null;
	}

	public static function is_approved( $company_id ) {
		$e = self::get( $company_id );
		return $e && 'approved' === $e->status;
	}

	/** Insert or update the profile (status is set by the callers). */
	public static function save( $company_id, $user_id, array $d, $status = null ) {
		global $wpdb;
		$row = array(
			'title'       => $d['title'],
			'partner_type'     => (string) ( $d['partner_type'] ?? 'independent' ),
			'bet_name'         => (string) ( $d['bet_name'] ?? '' ),
			'degree'           => (string) ( $d['degree'] ?? '' ),
			'degree_other'     => (string) ( $d['degree_other'] ?? '' ),
			'speciality'       => (string) ( $d['speciality'] ?? '' ),
			'speciality_other' => (string) ( $d['speciality_other'] ?? '' ),
			'diploma'     => $d['diploma'],
			'school'      => $d['school'],
			'grad_year'   => (int) $d['grad_year'],
			'experience'  => (int) $d['experience'],
			'specialties' => wp_json_encode( array_values( $d['specialties'] ) ),
			'bio'         => $d['bio'],
		);
		if ( null !== $status ) {
			$row['status'] = $status;
		}
		if ( self::get( $company_id ) ) {
			$wpdb->update( self::table(), $row, array( 'company_id' => (int) $company_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		} else {
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				self::table(),
				$row + array(
					'company_id' => (int) $company_id,
					'user_id'    => (int) $user_id,
					'status'     => $status ?? 'pending',
					'applied_at' => current_time( 'mysql', true ),
				)
			);
		}
	}

	public static function set( $company_id, array $fields ) {
		global $wpdb;
		$wpdb->update( self::table(), $fields, array( 'company_id' => (int) $company_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Approve, reject, suspend or restore (moderators).
	 *
	 * @return true|\WP_Error
	 */
	public static function decide( $company_id, $status, $reason = '' ) {
		$e = self::get( $company_id );
		if ( ! $e || ! in_array( $status, self::STATUSES, true ) ) {
			return new \WP_Error( 'sm_engineer', __( 'Unknown engineer.', 'sm-engineering' ) );
		}
		if ( 'rejected' === $status && '' === trim( $reason ) ) {
			return new \WP_Error( 'sm_reason', __( 'Give the reason of the refusal: the engineer reads it.', 'sm-engineering' ) );
		}
		$fields = array(
			'status'        => $status,
			'reject_reason' => in_array( $status, array( 'rejected', 'suspended' ), true ) ? sanitize_textarea_field( $reason ) : '',
			'decided_at'    => current_time( 'mysql', true ),
		);
		self::set( $company_id, $fields );
		// The documents sent with the application are the verification of the account.
		if ( $e->verification_id && in_array( $status, array( 'approved', 'rejected' ), true ) ) {
			\SM\Core\Verification\Verifications::decide( $e->verification_id, 'approved' === $status, $reason );
		}
		if ( 'approved' === $status ) {
			Companies::raise_level( $company_id, 3 );
		}
		AuditLog::add( 'engineer.' . $status, 'engineer', $company_id, array( 'reason' => $fields['reject_reason'] ), get_current_user_id(), $company_id );
		return true;
	}

	/**
	 * Engineers list.
	 *
	 * @param array $args status, specialty, wilaya, q, available, per_page, page.
	 */
	public static function query( array $args = array() ) {
		global $wpdb;
		$args  = wp_parse_args( $args, array( 'status' => 'approved', 'specialty' => '', 'wilaya' => '', 'q' => '', 'available' => null, 'per_page' => 50, 'page' => 1 ) );
		$e     = self::table();
		$c     = $wpdb->prefix . 'sm_companies';
		$where = array( "co.status = 'active'", "co.account_class = 'engineer'" );
		$vals  = array();
		if ( $args['status'] ) {
			$where[] = 'e.status = %s';
			$vals[]  = $args['status'];
		}
		if ( $args['specialty'] ) {
			$where[] = 'e.specialties LIKE %s';
			$vals[]  = '%"' . $wpdb->esc_like( sanitize_key( $args['specialty'] ) ) . '"%';
		}
		if ( $args['wilaya'] ) {
			$where[] = 'co.wilaya = %s';
			$vals[]  = $args['wilaya'];
		}
		if ( null !== $args['available'] ) {
			$where[] = 'e.available = %d';
			$vals[]  = $args['available'] ? 1 : 0;
		}
		if ( $args['q'] ) {
			$like    = '%' . $wpdb->esc_like( $args['q'] ) . '%';
			$where[] = '(co.name LIKE %s OR e.title LIKE %s OR e.bio LIKE %s)';
			array_push( $vals, $like, $like, $like );
		}
		$sql_where = implode( ' AND ', $where );
		$per       = max( 1, min( 200, (int) $args['per_page'] ) );
		$offset    = ( max( 1, (int) $args['page'] ) - 1 ) * $per;
		$base      = "FROM {$e} e JOIN {$c} co ON co.id = e.company_id WHERE {$sql_where}";
		$total     = (int) $wpdb->get_var( $vals ? $wpdb->prepare( "SELECT COUNT(*) {$base}", $vals ) : "SELECT COUNT(*) {$base}" ); // phpcs:ignore WordPress.DB
		$rows      = $wpdb->get_results( $wpdb->prepare( "SELECT e.* {$base} ORDER BY e.experience DESC, co.name ASC LIMIT %d OFFSET %d", array_merge( $vals, array( $per, $offset ) ) ) ); // phpcs:ignore WordPress.DB
		return array( 'items' => array_map( array( __CLASS__, 'hydrate' ), $rows ), 'total' => $total );
	}

	public static function count( $status ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE status = %s', $status ) ); // phpcs:ignore WordPress.DB
	}

	/** Completed missions of an engineer. */
	public static function missions_done( $company_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . Missions::table() . " WHERE engineer_id = %d AND status IN ('delivered','closed')", $company_id ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Public shape (no contact details: no phone, e-mail, address or website).
	 */
	public static function public_shape( $e ) {
		$c = Companies::get( $e->company_id );
		if ( ! $c ) {
			return null;
		}
		$types = Settings::types( true );
		$lang  = sm_current_language();
		$words = preg_split( '/\s+/u', trim( remove_accents( $c->name ) ) );
		$init  = '';
		foreach ( $words as $w ) {
			if ( '' !== $w && mb_strlen( $init ) < 2 ) {
				$init .= mb_strtoupper( mb_substr( $w, 0, 1 ) );
			}
		}
		return array(
			'id'          => (int) $e->company_id,
			'name'        => $c->name,
			'initials'    => $init ? $init : 'IN',
			'photo'       => Companies::logo_url( $c, 'medium' ),
			'title'       => $e->title,
			'diploma'     => $e->diploma,
			'experience'  => $e->experience,
			'specialties' => array_values( array_map( static fn( $s ) => isset( $types[ $s ] ) ? ( 'ar' === $lang ? $types[ $s ]['ar'] : $types[ $s ]['fr'] ) : $s, $e->specialties ) ),
			'codes'       => $e->specialties,
			'wilaya'      => $c->wilaya,
			'bio'         => $e->bio,
			'available'   => (bool) $e->available,
			'missions'    => Settings::get( 'show_missions' ) ? self::missions_done( $e->company_id ) : null,
			'request_url' => add_query_arg( 'engineer', (int) $e->company_id, \SM\Core\Routing\Routes::url( 'mission-request' ) ),
		);
	}
}
