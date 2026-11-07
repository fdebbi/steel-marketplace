<?php
/**
 * Companies repository (sm_companies, sm_company_members, sm_company_activities).
 *
 * A company is the business account. One account_class per company, fixed at
 * creation: "commercial" (buys and sells) or "engineering" (services only).
 * Users belong to a company with a company role: owner, manager, member.
 *
 * @package SM\Core
 */

namespace SM\Core\Accounts;

use SM\Core\Support\Crypto;

defined( 'ABSPATH' ) || exit;

final class Companies {

	const CLASSES = array( 'commercial', 'engineering' );

	/** @var array<int,object|null> */
	private static $cache = array();

	/** Activity codes by account class. */
	public static function activities( $class = null ) {
		$all = array(
			'commercial'  => array( 'manufacturer', 'supplier', 'installer', 'project_owner', 'reuse_dealer', 'fabrication' ),
			'engineering' => array( 'engineer' ),
		);
		$all = apply_filters( 'sm_activities', $all );
		return $class ? ( $all[ $class ] ?? array() ) : $all;
	}

	/** Translated activity labels. */
	public static function activity_labels() {
		return array(
			'manufacturer'  => __( 'Manufacturer', 'sm-core' ),
			'supplier'      => __( 'Supplier / distributor', 'sm-core' ),
			'installer'     => __( 'Installation company', 'sm-core' ),
			'project_owner' => __( 'Project owner', 'sm-core' ),
			'reuse_dealer'  => __( 'Second-hand dealer / recycler', 'sm-core' ),
			'fabrication'   => __( 'Fabrication services', 'sm-core' ),
			'engineer'      => __( 'Engineer / engineering office', 'sm-core' ),
		);
	}

	private static function now() {
		return current_time( 'mysql', true );
	}

	/** Unique slug from a name. */
	public static function unique_slug( $name ) {
		global $wpdb;
		$base = sanitize_title( remove_accents( $name ) );
		if ( '' === $base || preg_match( '/^%/', $base ) ) {
			$base = 'entreprise';
		}
		$base = substr( $base, 0, 150 );
		$slug = $base;
		$i    = 2;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		while ( $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . sm_table( 'companies' ) . ' WHERE slug = %s', $slug ) ) ) {
			$slug = $base . '-' . $i++;
		}
		return $slug;
	}

	/**
	 * Create a company with its owner and activities.
	 *
	 * @return int|\WP_Error Company ID.
	 */
	public static function create( array $data, $owner_id, array $activities ) {
		global $wpdb;
		$class = in_array( $data['account_class'] ?? '', self::CLASSES, true ) ? $data['account_class'] : 'commercial';
		$allowed_activities = self::activities( $class );
		$activities = array_values( array_intersect( $activities, $allowed_activities ) );
		if ( 'engineering' === $class ) {
			$activities = array( 'engineer' );
		}
		if ( ! $activities ) {
			return new \WP_Error( 'sm_no_activity', __( 'Choose at least one business activity.', 'sm-core' ) );
		}

		$now = self::now();
		$row = array(
			'slug'          => self::unique_slug( $data['name'] ),
			'account_class' => $class,
			'name'          => sanitize_text_field( $data['name'] ),
			'name_ar'       => sanitize_text_field( $data['name_ar'] ?? '' ),
			'wilaya'        => sanitize_text_field( $data['wilaya'] ?? '' ),
			'phone'         => sanitize_text_field( $data['phone'] ?? '' ),
			'email'         => sanitize_email( $data['email'] ?? '' ),
			'profile'       => wp_json_encode( array() ),
			'about_fr'      => '',
			'about_ar'      => '',
			'verification_level' => 0,
			'status'        => 'active',
			'created_at'    => $now,
			'updated_at'    => $now,
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( ! $wpdb->insert( sm_table( 'companies' ), $row ) ) {
			return new \WP_Error( 'sm_db', __( 'The account could not be saved. Please try again.', 'sm-core' ) );
		}
		$company_id = (int) $wpdb->insert_id;

		self::add_member( $company_id, $owner_id, 'owner' );
		self::set_activities( $company_id, $activities );

		sm_event( 'company.created', array( 'company_id' => $company_id, 'user_id' => $owner_id, 'class' => $class ) );
		return $company_id;
	}

	public static function add_member( $company_id, $user_id, $role = 'member' ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->replace(
			sm_table( 'company_members' ),
			array( 'company_id' => $company_id, 'user_id' => $user_id, 'company_role' => $role, 'created_at' => self::now() )
		);
		unset( self::$cache[ 'u' . $user_id ] );
	}

	/** Replace the activity list (limited to activities allowed for the class). */
	public static function set_activities( $company_id, array $activities ) {
		global $wpdb;
		$company = self::get( $company_id );
		if ( ! $company ) {
			return;
		}
		$allowed    = self::activities( $company->account_class );
		$activities = array_values( array_unique( array_intersect( $activities, $allowed ) ) );
		if ( ! $activities ) {
			return;
		}
		$table = sm_table( 'company_activities' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $table, array( 'company_id' => $company_id ) );
		foreach ( $activities as $code ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->insert( $table, array( 'company_id' => $company_id, 'activity_code' => $code, 'enabled_at' => self::now() ) );
		}
		self::flush( $company_id );
	}

	public static function get_activities( $company_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_col( $wpdb->prepare( 'SELECT activity_code FROM ' . sm_table( 'company_activities' ) . ' WHERE company_id = %d ORDER BY activity_code', $company_id ) );
	}

	/** Company by ID (object with ->activities) or null. */
	public static function get( $company_id ) {
		global $wpdb;
		$company_id = (int) $company_id;
		if ( ! $company_id ) {
			return null;
		}
		if ( array_key_exists( $company_id, self::$cache ) ) {
			return self::$cache[ $company_id ];
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . sm_table( 'companies' ) . ' WHERE id = %d AND deleted_at IS NULL', $company_id ) );
		if ( $row ) {
			$row->activities = self::get_activities( $company_id );
			$row->profile    = json_decode( (string) $row->profile, true ) ?: array();
		}
		self::$cache[ $company_id ] = $row ?: null;
		return self::$cache[ $company_id ];
	}

	public static function get_by_slug( $slug ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$id = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . sm_table( 'companies' ) . ' WHERE slug = %s AND deleted_at IS NULL', $slug ) );
		return $id ? self::get( $id ) : null;
	}

	/** Company of a user (first membership) or null. */
	public static function for_user( $user_id ) {
		global $wpdb;
		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			return null;
		}
		$key = 'u' . $user_id;
		if ( ! array_key_exists( $key, self::$cache ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::$cache[ $key ] = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT company_id FROM ' . sm_table( 'company_members' ) . ' WHERE user_id = %d ORDER BY id LIMIT 1', $user_id ) );
		}
		return self::get( self::$cache[ $key ] );
	}

	public static function member_role( $company_id, $user_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (string) $wpdb->get_var( $wpdb->prepare( 'SELECT company_role FROM ' . sm_table( 'company_members' ) . ' WHERE company_id = %d AND user_id = %d', $company_id, $user_id ) );
	}

	/** User IDs of a company (optionally limited to roles). */
	public static function member_ids( $company_id, array $roles = array() ) {
		global $wpdb;
		$sql = 'SELECT user_id FROM ' . sm_table( 'company_members' ) . ' WHERE company_id = %d';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $company_id ) );
		$ids  = array();
		foreach ( $rows as $r ) {
			$role = self::member_role( $company_id, $r->user_id );
			if ( ! $roles || in_array( $role, $roles, true ) ) {
				$ids[] = (int) $r->user_id;
			}
		}
		return $ids;
	}

	/** Whether $user_id may edit the company (owner or manager, or a moderator). */
	public static function can_manage( $company_id, $user_id ) {
		return user_can( $user_id, 'sm_moderate' ) || in_array( self::member_role( $company_id, $user_id ), array( 'owner', 'manager' ), true );
	}

	/** Bounding box of Algeria (with a small margin), for map positions. */
	const BOUNDS = array( 'lat_min' => 18.9, 'lat_max' => 37.2, 'lng_min' => -8.7, 'lng_max' => 12.0 );

	/**
	 * Normalise a map position.
	 *
	 * @return array{lat: float, lng: float}|null|\WP_Error  null when both are empty (no position).
	 */
	public static function parse_location( $lat, $lng ) {
		$lat = trim( (string) $lat );
		$lng = trim( (string) $lng );
		if ( '' === $lat && '' === $lng ) {
			return null;
		}
		if ( ! is_numeric( $lat ) || ! is_numeric( $lng ) ) {
			return new \WP_Error( 'sm_location', __( 'The map position is not valid. Place the marker again.', 'sm-core' ) );
		}
		$lat = round( (float) $lat, 6 );
		$lng = round( (float) $lng, 6 );
		$b   = self::BOUNDS;
		if ( $lat < $b['lat_min'] || $lat > $b['lat_max'] || $lng < $b['lng_min'] || $lng > $b['lng_max'] ) {
			return new \WP_Error( 'sm_location', __( 'The map position must be in Algeria.', 'sm-core' ) );
		}
		return array( 'lat' => $lat, 'lng' => $lng );
	}

	/** Public map position of a company, or null (none, or hidden by the company). */
	public static function public_location( $company ) {
		if ( ! $company || null === $company->lat || null === $company->lng || '' === (string) $company->lat || ! (int) $company->map_public ) {
			return null;
		}
		return array( 'lat' => round( (float) $company->lat, 6 ), 'lng' => round( (float) $company->lng, 6 ) );
	}

	/** Fields a company may edit itself (class, slug, level and status are not among them). */
	public static function update_profile( $company_id, array $data ) {
		global $wpdb;
		$fields = array();
		foreach ( array( 'name', 'name_ar', 'commune', 'address', 'phone', 'website', 'title_fr', 'title_ar' ) as $f ) {
			if ( isset( $data[ $f ] ) ) {
				$fields[ $f ] = 'website' === $f ? esc_url_raw( $data[ $f ] ) : sanitize_text_field( $data[ $f ] );
			}
		}
		if ( isset( $data['email'] ) ) {
			$fields['email'] = sanitize_email( $data['email'] );
		}
		if ( isset( $data['wilaya'] ) && Wilayas::exists( $data['wilaya'] ) ) {
			$fields['wilaya'] = $data['wilaya'];
		}
		foreach ( array( 'about_fr', 'about_ar' ) as $f ) {
			if ( isset( $data[ $f ] ) ) {
				$fields[ $f ] = sanitize_textarea_field( $data[ $f ] );
			}
		}
		if ( array_key_exists( 'location', $data ) ) {
			$loc = is_array( $data['location'] ) ? self::parse_location( $data['location']['lat'] ?? '', $data['location']['lng'] ?? '' ) : null;
			if ( is_wp_error( $loc ) ) {
				return $loc;
			}
			$fields['lat'] = $loc ? $loc['lat'] : null;
			$fields['lng'] = $loc ? $loc['lng'] : null;
		}
		if ( isset( $data['map_public'] ) ) {
			$fields['map_public'] = $data['map_public'] ? 1 : 0;
		}
		if ( isset( $data['logo_id'] ) ) {
			$fields['logo_id'] = absint( $data['logo_id'] );
		}
		if ( isset( $data['profile'] ) && is_array( $data['profile'] ) ) {
			$profile = self::get( $company_id )->profile ?? array();
			foreach ( array( 'qualifications', 'specialities' ) as $list ) {
				if ( isset( $data['profile'][ $list ] ) ) {
					$items = array_filter( array_map( 'sanitize_text_field', (array) $data['profile'][ $list ] ) );
					$profile[ $list ] = array_slice( array_values( $items ), 0, 20 );
				}
			}
			$fields['profile'] = wp_json_encode( $profile );
		}
		foreach ( array( 'rc_number', 'nif', 'nis' ) as $f ) {
			if ( isset( $data[ $f ] ) ) {
				$value        = preg_replace( '/[^A-Za-z0-9\/ -]/', '', (string) $data[ $f ] );
				$fields[ $f ] = '' === $value ? '' : Crypto::encrypt( $value );
			}
		}
		if ( ! $fields ) {
			return true;
		}
		$fields['updated_at'] = self::now();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ok = false !== $wpdb->update( sm_table( 'companies' ), $fields, array( 'id' => $company_id ) );
		self::flush( $company_id );
		if ( $ok ) {
			sm_event( 'company.updated', array( 'company_id' => $company_id, 'fields' => array_keys( $fields ) ) );
		}
		return $ok;
	}

	/** Decrypted registration numbers (for the owner and moderators only). */
	public static function legal_numbers( $company ) {
		return array(
			'rc_number' => $company->rc_number ? Crypto::decrypt( $company->rc_number ) : '',
			'nif'       => $company->nif ? Crypto::decrypt( $company->nif ) : '',
			'nis'       => $company->nis ? Crypto::decrypt( $company->nis ) : '',
		);
	}

	/** Admin-only changes: level, status. */
	public static function set_admin_fields( $company_id, array $fields ) {
		global $wpdb;
		$clean = array();
		if ( isset( $fields['verification_level'] ) ) {
			$clean['verification_level'] = max( 0, min( 3, (int) $fields['verification_level'] ) );
		}
		if ( isset( $fields['status'] ) && in_array( $fields['status'], array( 'active', 'suspended' ), true ) ) {
			$clean['status'] = $fields['status'];
		}
		if ( ! $clean ) {
			return false;
		}
		$clean['updated_at'] = self::now();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( sm_table( 'companies' ), $clean, array( 'id' => $company_id ) );
		self::flush( $company_id );
		return true;
	}

	/** Raise (never lower) the verification level. */
	public static function raise_level( $company_id, $level ) {
		$company = self::get( $company_id );
		if ( $company && (int) $company->verification_level < $level ) {
			self::set_admin_fields( $company_id, array( 'verification_level' => $level ) );
			sm_event( 'company.verified', array( 'company_id' => $company_id, 'level' => $level ) );
		}
	}

	/**
	 * Query companies.
	 *
	 * @param array $args class, activity, wilaya, status (default active), search, per_page, page, min_level.
	 * @return array{items:object[],total:int}
	 */
	public static function query( array $args = array() ) {
		global $wpdb;
		$args  = wp_parse_args( $args, array( 'class' => '', 'activity' => '', 'wilaya' => '', 'status' => 'active', 'search' => '', 'per_page' => 20, 'page' => 1, 'min_level' => 0, 'orderby' => 'created_at' ) );
		$t     = sm_table( 'companies' );
		$where = array( "$t.deleted_at IS NULL" );
		$vals  = array();
		if ( $args['status'] ) {
			$where[] = "$t.status = %s";
			$vals[]  = $args['status'];
		}
		if ( $args['class'] ) {
			$where[] = "$t.account_class = %s";
			$vals[]  = $args['class'];
		}
		if ( $args['wilaya'] ) {
			$where[] = "$t.wilaya = %s";
			$vals[]  = $args['wilaya'];
		}
		if ( $args['min_level'] ) {
			$where[] = "$t.verification_level >= %d";
			$vals[]  = (int) $args['min_level'];
		}
		if ( $args['search'] ) {
			$where[] = "($t.name LIKE %s OR $t.name_ar LIKE %s)";
			$like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$vals[]  = $like;
			$vals[]  = $like;
		}
		$join = '';
		if ( $args['activity'] ) {
			$a       = sm_table( 'company_activities' );
			$join    = "INNER JOIN $a ON $a.company_id = $t.id";
			$where[] = "$a.activity_code = %s";
			$vals[]  = $args['activity'];
		}
		$order    = 'name' === $args['orderby'] ? "$t.name ASC" : "$t.verification_level DESC, $t.created_at DESC";
		$per_page = max( 1, min( 100, (int) $args['per_page'] ) );
		$offset   = ( max( 1, (int) $args['page'] ) - 1 ) * $per_page;
		$w        = implode( ' AND ', $where );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
		$total = (int) $wpdb->get_var( $vals ? $wpdb->prepare( "SELECT COUNT(DISTINCT $t.id) FROM $t $join WHERE $w", $vals ) : "SELECT COUNT(DISTINCT $t.id) FROM $t $join WHERE $w" );
		$sql   = "SELECT DISTINCT $t.id FROM $t $join WHERE $w ORDER BY $order LIMIT %d OFFSET %d";
		$ids   = $wpdb->get_col( $wpdb->prepare( $sql, array_merge( $vals, array( $per_page, $offset ) ) ) );
		// phpcs:enable
		return array( 'items' => array_values( array_filter( array_map( array( __CLASS__, 'get' ), $ids ) ) ), 'total' => $total );
	}

	public static function flush( $company_id ) {
		unset( self::$cache[ (int) $company_id ] );
	}
}
