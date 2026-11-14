<?php
/**
 * Visit requests (0.12.0): a buyer asks the seller for a visit on a date and
 * time; the seller accepts, refuses or proposes another date. An accepted visit
 * shows the exact position of the listing to that buyer only (unless the seller
 * made the map public).
 *
 * Offered on listings sold as a whole lot and on the markets that ask for it
 * (filter sm_products_visits; sm-buildings: every building).
 *
 * Status: pending → accepted | refused | proposed (seller) ;
 *         proposed → accepted | cancelled (buyer) ; pending / accepted → cancelled (buyer).
 * Dates are kept in the time of the site (wall clock of the visit).
 *
 * @package SM\Products
 */

namespace SM\Products;

use SM\Core\Accounts\Companies;
use SM\Core\Notifications\Notifications as Notify;
use SM\Core\Routing\Routes;
use SM\Core\Support\Forms;

defined( 'ABSPATH' ) || exit;

final class Visits {

	const OPEN      = array( 'pending', 'proposed', 'accepted' );
	const MAX_DAYS  = 90;  // A visit is asked at most three months ahead.
	const KEEP_DAYS = 2;   // A passed visit stays shown two days; then a new one can be asked.

	public static function init() {
		add_action( 'admin_post_sm_visit_request', array( __CLASS__, 'handle_request' ) );
		add_action( 'admin_post_sm_visit_reply', array( __CLASS__, 'handle_reply' ) );
		add_action( 'admin_post_sm_visit_answer', array( __CLASS__, 'handle_answer' ) );
		add_filter( 'sm_dashboard_view_slugs', static fn( $slugs ) => array_merge( $slugs, array( 'visits' => 'visites', 'my-visits' => 'mes-visites' ) ) );
		add_filter( 'sm_dashboard_badges', array( __CLASS__, 'badges' ), 10, 2 );
		add_filter( 'sm_theme_data_product_visits', array( __CLASS__, 'theme_list' ), 10, 2 );
		add_filter( 'sm_notification_types', array( __CLASS__, 'types' ) );
	}

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sm_product_visits';
	}

	/** Can a visit be asked for this listing? */
	public static function eligible( $p ) {
		if ( ! $p || 'published' !== $p->status ) {
			return false;
		}
		/**
		 * @param bool   $offered Whole-lot sales by default.
		 * @param object $p       Product row.
		 */
		return (bool) apply_filters( 'sm_products_visits', in_array( (string) ( $p->sale_mode ?? '' ), array( 'lot', 'both' ), true ), $p );
	}

	public static function get( $id ) {
		global $wpdb;
		$r = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) ); // phpcs:ignore WordPress.DB
		return $r ? self::hydrate( $r ) : null;
	}

	private static function hydrate( $r ) {
		foreach ( array( 'id', 'product_id', 'seller_id', 'company_id', 'user_id' ) as $k ) {
			$r->$k = (int) $r->$k;
		}
		return $r;
	}

	/** Latest request of a company for a listing (open ones first). */
	public static function latest( $product_id, $company_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB
		$r = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . " WHERE product_id = %d AND company_id = %d ORDER BY (status IN ('pending','proposed','accepted')) DESC, id DESC LIMIT 1", $product_id, $company_id ) );
		return $r ? self::hydrate( $r ) : null;
	}

	/** Is the visit still current (not passed for more than KEEP_DAYS)? */
	private static function current( $v ) {
		return strtotime( $v->visit_at ) + self::KEEP_DAYS * DAY_IN_SECONDS >= strtotime( current_time( 'mysql' ) );
	}

	/**
	 * Did the seller accept a visit of this company for the listing? Once
	 * accepted the buyer keeps the map, even after the visit (the buyer who
	 * cancels, or whom the seller refuses afterwards, loses it).
	 */
	public static function was_accepted( $product_id, $company_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table() . " WHERE product_id = %d AND company_id = %d AND status = 'accepted' LIMIT 1", $product_id, $company_id ) );
	}

	/**
	 * Exact position the current visitor may see, or null: the seller and the
	 * platform team always; everyone when the seller made the map public;
	 * otherwise the buyer whose visit was accepted, with no time limit.
	 */
	public static function location_for( $p, $user_id = null ) {
		if ( null === $p->lat || null === $p->lng ) {
			return null;
		}
		$loc     = array( 'lat' => round( (float) $p->lat, 6 ), 'lng' => round( (float) $p->lng, 6 ) );
		$user_id = $user_id ?? get_current_user_id();
		if ( (int) $p->map_public ) {
			return $loc;
		}
		if ( ! $user_id ) {
			return null;
		}
		if ( user_can( $user_id, 'sm_moderate' ) || Companies::member_role( $p->company_id, $user_id ) ) {
			return $loc;
		}
		$company = Companies::for_user( $user_id );
		return $company && self::was_accepted( $p->id, $company->id ) ? $loc : null;
	}

	/** What the product page shows about visits to the current visitor. */
	public static function state_for( $p ) {
		$user_id = get_current_user_id();
		$company = $user_id ? Companies::for_user( $user_id ) : null;
		$own     = $user_id && Companies::member_role( $p->company_id, $user_id );
		$v       = $company && ! $own ? self::latest( $p->id, $company->id ) : null;
		if ( $v && ( ! in_array( $v->status, self::OPEN, true ) || ! self::current( $v ) ) && 'refused' !== $v->status ) {
			$v = null; // Cancelled or passed: a new request can be sent.
		}
		return array(
			'eligible'     => self::eligible( $p ),
			'own'          => (bool) $own,
			'guest'        => ! $user_id,
			'has_position' => null !== $p->lat,
			'map_public'   => (bool) $p->map_public,
			'request'      => $v ? self::shape( $v ) : null,
			'min_date'     => wp_date( 'Y-m-d', time() + DAY_IN_SECONDS ),
			'max_date'     => wp_date( 'Y-m-d', time() + self::MAX_DAYS * DAY_IN_SECONDS ),
		);
	}

	private static function shape( $v ) {
		return array(
			'id'          => $v->id,
			'status'      => $v->status,
			'visit_at'    => $v->visit_at,
			'proposed_at' => (string) $v->proposed_at,
			'message'     => (string) $v->message,
			'reply'       => (string) $v->reply,
			'created_at'  => $v->created_at,
		);
	}

	/** "2026-11-18" + "10:00" → site-time datetime, or '' when not a date. */
	public static function parse_when( $date, $time ) {
		$date = trim( (string) $date );
		$time = trim( (string) $time );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || ! preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $time ) ) {
			return '';
		}
		list( $y, $m, $d ) = array_map( 'intval', explode( '-', $date ) );
		return checkdate( $m, $d, $y ) ? $date . ' ' . $time . ':00' : '';
	}

	/** Error message when the date cannot be asked, '' when fine. */
	public static function check_when( $when ) {
		if ( '' === $when ) {
			return __( 'Choose the date and the time of the visit.', 'sm-products' );
		}
		$now = strtotime( current_time( 'mysql' ) );
		$ts  = strtotime( $when );
		if ( $ts < $now + HOUR_IN_SECONDS ) {
			return __( 'Choose a date and a time in the future.', 'sm-products' );
		}
		if ( $ts > $now + self::MAX_DAYS * DAY_IN_SECONDS ) {
			return __( 'Choose a date within the next three months.', 'sm-products' );
		}
		return '';
	}

	/**
	 * Ask for a visit.
	 *
	 * @return int|\WP_Error Request ID.
	 */
	public static function request( $p, $company, $user_id, $when, $message ) {
		global $wpdb;
		if ( ! self::eligible( $p ) ) {
			return new \WP_Error( 'visit', __( 'A visit cannot be asked for this listing.', 'sm-products' ) );
		}
		if ( ! $company ) {
			return new \WP_Error( 'visit', __( 'Your account has no company.', 'sm-products' ) );
		}
		if ( (int) $company->id === (int) $p->company_id ) {
			return new \WP_Error( 'visit', __( 'This is your own listing.', 'sm-products' ) );
		}
		$err = self::check_when( $when );
		if ( $err ) {
			return new \WP_Error( 'visit_date', $err );
		}
		$last = self::latest( $p->id, $company->id );
		if ( $last && in_array( $last->status, self::OPEN, true ) && self::current( $last ) ) {
			return new \WP_Error( 'visit', __( 'You already asked for a visit of this listing. Follow it in "My visits".', 'sm-products' ) );
		}
		$now = current_time( 'mysql' );
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'product_id' => (int) $p->id,
				'seller_id'  => (int) $p->company_id,
				'company_id' => (int) $company->id,
				'user_id'    => (int) $user_id,
				'visit_at'   => $when,
				'message'    => mb_substr( trim( sanitize_textarea_field( (string) $message ) ), 0, 1000 ),
				'reply'      => '',
				'status'     => 'pending',
				'created_at' => $now,
				'updated_at' => $now,
			)
		);
		$id = (int) $wpdb->insert_id;
		Notify::notify_company( $p->company_id, 'visit.requested', array( 'visit_id' => $id, 'product_id' => $p->id, 'company_id' => $company->id ) );
		return $id;
	}

	private static function update( $id, array $row ) {
		global $wpdb;
		$row['updated_at'] = current_time( 'mysql' );
		$wpdb->update( self::table(), $row, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Answer of the seller: accept, refuse, or propose another date.
	 *
	 * @return true|\WP_Error
	 */
	public static function reply( $v, $do, $when = '', $note = '' ) {
		$note = mb_substr( trim( sanitize_textarea_field( (string) $note ) ), 0, 500 );
		$pay  = array( 'visit_id' => $v->id, 'product_id' => $v->product_id, 'company_id' => $v->seller_id );
		if ( 'accept' === $do && 'pending' === $v->status ) {
			self::update( $v->id, array( 'status' => 'accepted', 'reply' => $note ) );
			Notify::notify( array( $v->user_id ), 'visit.accepted', $pay, $v->company_id );
			return true;
		}
		if ( 'refuse' === $do && in_array( $v->status, array( 'pending', 'accepted', 'proposed' ), true ) ) {
			self::update( $v->id, array( 'status' => 'refused', 'reply' => $note ) );
			Notify::notify( array( $v->user_id ), 'visit.refused', $pay, $v->company_id );
			return true;
		}
		if ( 'propose' === $do && in_array( $v->status, array( 'pending', 'proposed' ), true ) ) {
			$err = self::check_when( $when );
			if ( $err ) {
				return new \WP_Error( 'visit_date', $err );
			}
			self::update( $v->id, array( 'status' => 'proposed', 'proposed_at' => $when, 'reply' => $note ) );
			Notify::notify( array( $v->user_id ), 'visit.proposed', $pay, $v->company_id );
			return true;
		}
		return new \WP_Error( 'visit', __( 'This request can no longer be answered.', 'sm-products' ) );
	}

	/**
	 * Answer of the buyer: accept the proposed date, or cancel the request.
	 *
	 * @return true|\WP_Error
	 */
	public static function answer( $v, $do ) {
		$pay = array( 'visit_id' => $v->id, 'product_id' => $v->product_id, 'company_id' => $v->company_id );
		if ( 'accept' === $do && 'proposed' === $v->status && $v->proposed_at ) {
			self::update( $v->id, array( 'status' => 'accepted', 'visit_at' => $v->proposed_at, 'proposed_at' => null ) );
			Notify::notify_company( $v->seller_id, 'visit.confirmed', $pay );
			return true;
		}
		if ( 'cancel' === $do && in_array( $v->status, self::OPEN, true ) ) {
			self::update( $v->id, array( 'status' => 'cancelled' ) );
			Notify::notify_company( $v->seller_id, 'visit.cancelled', $pay );
			return true;
		}
		return new \WP_Error( 'visit', __( 'This request can no longer be changed.', 'sm-products' ) );
	}

	/** Requests waiting for the seller. */
	public static function count_new( $company_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . " WHERE seller_id = %d AND status = 'pending'", $company_id ) ); // phpcs:ignore WordPress.DB
	}

	public static function badges( $badges, $company_id ) {
		$badges['visits'] = self::count_new( (int) $company_id );
		return $badges;
	}

	/** Requests received (box "received") or sent (box "sent") by a company, newest first. */
	public static function for_company( $company_id, $box = 'received' ) {
		global $wpdb;
		$col = 'sent' === $box ? 'company_id' : 'seller_id';
		// phpcs:ignore WordPress.DB
		return array_map( array( __CLASS__, 'hydrate' ), (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . " WHERE {$col} = %d ORDER BY (status = 'pending') DESC, id DESC LIMIT 100", $company_id ) ) );
	}

	/** Theme data "product_visits": { box } → rows ready to show. */
	public static function theme_list( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$company = Companies::for_user( get_current_user_id() );
		if ( ! $company ) {
			return array();
		}
		$box  = 'sent' === ( $args['box'] ?? '' ) ? 'sent' : 'received';
		$out  = array();
		foreach ( self::for_company( $company->id, $box ) as $v ) {
			$p     = Products::get( $v->product_id );
			$other = Companies::get( 'sent' === $box ? $v->seller_id : $v->company_id );
			$user  = 'received' === $box ? get_userdata( $v->user_id ) : null;
			$out[] = self::shape( $v ) + array(
				'title'      => $p ? Presenter::title_text( $p ) : '',
				'listing_id' => $p ? $p->listing_id : 0,
				'online'     => $p && 'published' === $p->status,
				'company'    => $other ? $other->name : '',
				'contact'    => $user ? $user->display_name : '',
				'passed'     => strtotime( $v->visit_at ) < strtotime( current_time( 'mysql' ) ),
				'min_date'   => wp_date( 'Y-m-d', time() + DAY_IN_SECONDS ),
				'max_date'   => wp_date( 'Y-m-d', time() + self::MAX_DAYS * DAY_IN_SECONDS ),
			);
		}
		return $out;
	}

	/* ---- Form handlers ---------------------------------------------------- */

	public static function handle_request() {
		Forms::begin( 'visit_request' );
		$user_id = get_current_user_id();
		$company = Companies::for_user( $user_id );
		$p       = Products::get( absint( Forms::post( 'product_id' ) ) );
		if ( ! $p ) {
			Forms::back( array( __( 'This listing no longer exists.', 'sm-products' ) ) );
		}
		if ( $company && ! Forms::rate_limit( 'visit|' . $company->id, 10, DAY_IN_SECONDS ) ) {
			Forms::back( array( __( 'You asked for many visits today. Try again tomorrow.', 'sm-products' ) ) );
		}
		$r = self::request( $p, $company, $user_id, self::parse_when( Forms::post( 'visit_date' ), Forms::post( 'visit_time' ) ), Forms::post( 'message' ) );
		if ( is_wp_error( $r ) ) {
			Forms::back( 'visit_date' === $r->get_error_code() ? array( 'visit_date' => $r->get_error_message() ) : array( $r->get_error_message() ) );
		}
		Forms::back( array(), __( 'Your request is sent. The seller will answer; you will be told by notification and e-mail.', 'sm-products' ) );
	}

	public static function handle_reply() {
		Forms::begin( 'visit_reply' );
		$v = self::get( absint( Forms::post( 'visit_id' ) ) );
		if ( ! $v || ! Companies::member_role( $v->seller_id, get_current_user_id() ) ) {
			Forms::back( array( __( 'You cannot answer this request.', 'sm-products' ) ) );
		}
		$do = sanitize_key( Forms::post( 'do' ) );
		$r  = self::reply( $v, $do, self::parse_when( Forms::post( 'visit_date' ), Forms::post( 'visit_time' ) ), Forms::post( 'reply' ) );
		if ( is_wp_error( $r ) ) {
			Forms::back( array( $r->get_error_message() ) );
		}
		$msg = array(
			'accept'  => __( 'Visit accepted. The buyer now sees the position on the map.', 'sm-products' ),
			'refuse'  => __( 'Request refused. The buyer is told.', 'sm-products' ),
			'propose' => __( 'New date proposed. The buyer must confirm it.', 'sm-products' ),
		);
		Forms::back( array(), $msg[ $do ] ?? '' );
	}

	public static function handle_answer() {
		Forms::begin( 'visit_answer' );
		$v       = self::get( absint( Forms::post( 'visit_id' ) ) );
		$company = Companies::for_user( get_current_user_id() );
		if ( ! $v || ! $company || (int) $company->id !== $v->company_id ) {
			Forms::back( array( __( 'You cannot change this request.', 'sm-products' ) ) );
		}
		$do = sanitize_key( Forms::post( 'do' ) );
		$r  = self::answer( $v, $do );
		if ( is_wp_error( $r ) ) {
			Forms::back( array( $r->get_error_message() ) );
		}
		Forms::back( array(), 'accept' === $do ? __( 'Visit confirmed. The position is now shown on the listing.', 'sm-products' ) : __( 'Request cancelled.', 'sm-products' ) );
	}

	/* ---- Notifications ---------------------------------------------------- */

	/** "18/11/2026 10:00" of a request field. */
	private static function when( $p, $field = 'visit_at' ) {
		$v = self::get( (int) ( $p['visit_id'] ?? 0 ) );
		return $v && $v->$field ? mysql2date( 'd/m/Y H:i', $v->$field ) : '';
	}

	public static function types( $types ) {
		$title = static function ( $p ) {
			$prod = Products::get( $p['product_id'] ?? 0 );
			return $prod ? Presenter::title_text( $prod ) : '';
		};
		$who      = static function ( $p ) {
			$c = Companies::get( (int) ( $p['company_id'] ?? 0 ) );
			return $c ? $c->name : '';
		};
		$received = static fn( $p ) => Routes::url( 'account', array( 'view' => 'visits' ) );
		$sent     = static fn( $p ) => Routes::url( 'account', array( 'view' => 'my-visits' ) );
		$types['visit.requested'] = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'New visit request', 'sm-products' ),
			/* translators: 1: company, 2: listing title, 3: date and time. */
			'body'    => static fn( $p ) => sprintf( __( '%1$s asks to visit "%2$s" on %3$s. Accept, refuse or propose another date from your account.', 'sm-products' ), $who( $p ), $title( $p ), self::when( $p ) ),
			'link'    => $received,
		);
		$types['visit.accepted'] = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Your visit is accepted', 'sm-products' ),
			/* translators: 1: listing title, 2: date and time. */
			'body'    => static fn( $p ) => sprintf( __( 'The seller accepted your visit of "%1$s" on %2$s. The exact position is now shown on the listing.', 'sm-products' ), $title( $p ), self::when( $p ) ),
			'link'    => $sent,
		);
		$types['visit.refused'] = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Your visit request was refused', 'sm-products' ),
			/* translators: %s: listing title. */
			'body'    => static fn( $p ) => sprintf( __( 'The seller cannot receive you for "%s". You can write to him from the listing.', 'sm-products' ), $title( $p ) ),
			'link'    => $sent,
		);
		$types['visit.proposed'] = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'The seller proposes another date for your visit', 'sm-products' ),
			/* translators: 1: listing title, 2: date and time. */
			'body'    => static fn( $p ) => sprintf( __( 'For "%1$s", the seller proposes %2$s. Confirm or cancel from your account.', 'sm-products' ), $title( $p ), self::when( $p, 'proposed_at' ) ),
			'link'    => $sent,
		);
		$types['visit.confirmed'] = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Visit confirmed by the buyer', 'sm-products' ),
			/* translators: 1: company, 2: listing title, 3: date and time. */
			'body'    => static fn( $p ) => sprintf( __( '%1$s confirmed the visit of "%2$s" on %3$s.', 'sm-products' ), $who( $p ), $title( $p ), self::when( $p ) ),
			'link'    => $received,
		);
		$types['visit.cancelled'] = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Visit cancelled by the buyer', 'sm-products' ),
			/* translators: 1: company, 2: listing title. */
			'body'    => static fn( $p ) => sprintf( __( '%1$s cancelled the visit of "%2$s".', 'sm-products' ), $who( $p ), $title( $p ) ),
			'link'    => $received,
		);
		return $types;
	}
}
