<?php
/**
 * Companies repository (sm_companies, sm_company_members, sm_company_activities).
 *
 * A company is the business account. One account_class per company, fixed at
 * creation: company, professional, client or partner engineer (see CLASSES).
 * Users belong to a company with a company role: owner, manager, member.
 *
 * @package SM\Core
 */

namespace SM\Core\Accounts;

use SM\Core\Support\Crypto;

defined( 'ABSPATH' ) || exit;

final class Companies {

	/**
	 * Account classes (Phase 9.1):
	 *  - company      : company with one or more company types; public page; buys and sells.
	 *  - professional : craftsman / independent with one or more trades; public page; buys, sells, offers services.
	 *  - client       : private account (no public page); buys, sells, requests services and quotations.
	 *  - engineer     : partner engineer of the platform, on application only (sm-engineering); never trades.
	 */
	const CLASSES = array( 'company', 'professional', 'client', 'engineer' );

	/** Option holding the administrator's changes to company types and trades. */
	const TYPES_OPTION = 'sm_account_types';

	/** @var array<int,object|null> */
	private static $cache = array();

	public static function class_labels() {
		return array(
			'company'      => __( 'Company', 'sm-core' ),
			'professional' => __( 'Professional', 'sm-core' ),
			'client'       => __( 'Client', 'sm-core' ),
			'engineer'     => __( 'Partner engineer', 'sm-core' ),
		);
	}

	public static function class_label( $class ) {
		return self::class_labels()[ $class ] ?? $class;
	}

	/** URL of the logo / photo of an account ('' when none). */
	public static function logo_url( $company, $size = 'thumbnail' ) {
		$id = (int) ( $company->logo_id ?? 0 );
		if ( ! $id ) {
			return '';
		}
		$src = wp_get_attachment_image_src( $id, $size );
		return $src ? (string) $src[0] : '';
	}

	/** Classes with a public page, shown in directories. */
	public static function is_public_class( $class ) {
		return in_array( $class, array( 'company', 'professional' ), true );
	}

	/** Classes where the user chooses types / trades. */
	public static function chooses_activities( $class ) {
		return in_array( $class, array( 'company', 'professional' ), true );
	}

	/** Built-in activity codes by class. */
	public static function default_activities() {
		return array(
			'company'      => array( 'construction', 'manufacturer', 'supplier', 'recycler', 'dismantler' ),
			'professional' => array( 'trade_fabricator', 'trade_erector', 'trade_roofing', 'trade_welder', 'trade_boilermaker', 'trade_metalworker', 'trade_lifting', 'trade_painting' ),
			'client'       => array( 'client' ),
			'engineer'     => array( 'engineer' ),
		);
	}

	/** Administrator's changes: labels, disabled codes, added codes. */
	public static function type_settings() {
		return wp_parse_args( (array) get_option( self::TYPES_OPTION, array() ), array( 'labels' => array(), 'disabled' => array(), 'custom' => array(), 'desc' => array(), 'image' => array(), 'order' => array(), 'deleted' => array() ) );
	}

	/**
	 * Activity codes by class.
	 *
	 * @param string|null $class      One class, or all.
	 * @param bool        $all        Include the codes the administrator disabled (to read existing accounts).
	 */
	public static function activities( $class = null, $all = false ) {
		$t   = self::type_settings();
		$out = self::default_activities();
		foreach ( (array) $t['custom'] as $c => $codes ) {
			if ( isset( $out[ $c ] ) ) {
				$out[ $c ] = array_values( array_unique( array_merge( $out[ $c ], (array) $codes ) ) );
			}
		}
		$order = (array) $t['order'];
		foreach ( $out as $c => $codes ) {
			$codes = array_values( array_diff( $codes, (array) $t['deleted'] ) );
			if ( ! $all ) {
				$codes = array_values( array_diff( $codes, (array) $t['disabled'] ) );
			}
			$pos = array_flip( $codes );
			usort( $codes, static fn( $a, $b ) => array( (int) ( $order[ $a ] ?? 0 ), $pos[ $a ] ) <=> array( (int) ( $order[ $b ] ?? 0 ), $pos[ $b ] ) );
			$out[ $c ] = $codes;
		}
		$out = apply_filters( 'sm_activities', $out );
		return $class ? ( $out[ $class ] ?? array() ) : $out;
	}

	/** Class of an activity code ('' when unknown). */
	public static function activity_class( $code ) {
		foreach ( self::activities( null, true ) as $class => $codes ) {
			if ( in_array( $code, $codes, true ) ) {
				return $class;
			}
		}
		return '';
	}

	/** Translated labels (the administrator's wording first, in the current language). */
	public static function activity_labels() {
		$labels = array(
			'construction'      => __( 'Steel construction contractor', 'sm-core' ),
			'manufacturer'      => __( 'Manufacturing', 'sm-core' ),
			'supplier'          => __( 'Supply and distribution', 'sm-core' ),
			'recycler'          => __( 'Recycling and scrap', 'sm-core' ),
			'dismantler'        => __( 'Dismantling and second-hand resale', 'sm-core' ),
			'trade_fabricator'  => __( 'Structural steel fabricator', 'sm-core' ),
			'trade_erector'     => __( 'Steel structure erector', 'sm-core' ),
			'trade_roofing'     => __( 'Roofing and cladding installer', 'sm-core' ),
			'trade_welder'      => __( 'Welder', 'sm-core' ),
			'trade_boilermaker' => __( 'Boilermaker', 'sm-core' ),
			'trade_metalworker' => __( 'Metalworker (locksmith)', 'sm-core' ),
			'trade_lifting'     => __( 'Lifting and handling', 'sm-core' ),
			'trade_painting'    => __( 'Sandblasting and painting', 'sm-core' ),
			'client'            => __( 'Client', 'sm-core' ),
			'engineer'          => __( 'Partner engineer', 'sm-core' ),
		);
		$lang = function_exists( 'sm_current_language' ) ? sm_current_language() : 'fr';
		foreach ( (array) self::type_settings()['labels'] as $code => $l ) {
			$text = trim( (string) ( $l[ $lang ] ?? '' ) );
			if ( '' === $text && ! isset( $labels[ $code ] ) ) { // An added type without an Arabic name shows its French name.
				$text = trim( (string) ( $l['fr'] ?? '' ) );
			}
			if ( '' !== $text ) {
				$labels[ $code ] = $text;
			}
		}
		return $labels;
	}

	/** Save the administrator's changes (labels, disabled, new codes); descriptions, images and order are kept. */
	public static function save_type_settings( array $labels, array $disabled, array $custom ) {
		self::update_type_settings( array( 'labels' => $labels, 'disabled' => array_values( $disabled ), 'custom' => $custom ) );
	}

	/** Change some keys of the administrator's settings, keep the others. */
	public static function update_type_settings( array $patch ) {
		update_option( self::TYPES_OPTION, array_merge( self::type_settings(), $patch ), false );
	}

	/**
	 * Description and image of each type / trade, in the current language:
	 * code => { label, desc, image (URL or ''), image_id, hidden }.
	 */
	public static function activity_meta( $class = null ) {
		$t      = self::type_settings();
		$labels = self::activity_labels();
		$lang   = function_exists( 'sm_current_language' ) ? sm_current_language() : 'fr';
		$out    = array();
		foreach ( self::activities( $class, true ) as $c => $codes ) {
			foreach ( $class ? array( $codes ) : $codes as $code ) {
				$d   = (array) ( $t['desc'][ $code ] ?? array() );
				$img = (int) ( $t['image'][ $code ] ?? 0 );
				$src = $img ? wp_get_attachment_image_src( $img, 'medium' ) : false;
				$out[ $code ] = array(
					'label'    => $labels[ $code ] ?? $code,
					'desc'     => trim( sm_pick( $d, $lang ) ),
					'image'    => $src ? (string) $src[0] : '',
					'image_id' => $img,
					'hidden'   => in_array( $code, (array) $t['disabled'], true ),
				);
			}
		}
		return $out;
	}

	/** Number of accounts that have a type / trade. */
	public static function activity_usage( $code ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . sm_table( 'company_activities' ) . ' WHERE activity_code = %s', $code ) );
	}

	/**
	 * Delete a type / trade. Accounts that have it get $replacement instead
	 * (required when the code is used). Returns true or a WP_Error.
	 */
	public static function delete_activity( $code, $replacement = '' ) {
		global $wpdb;
		$class = self::activity_class( $code );
		if ( ! in_array( $class, array( 'company', 'professional' ), true ) ) {
			return new \WP_Error( 'sm_unknown', __( 'Unknown type.', 'sm-core' ) );
		}
		$used = self::activity_usage( $code );
		if ( $used && ( $replacement === $code || self::activity_class( $replacement ) !== $class ) ) {
			return new \WP_Error( 'sm_replacement', __( 'Choose a replacement of the same kind for the accounts that use it.', 'sm-core' ) );
		}
		$a = sm_table( 'company_activities' );
		if ( $used ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery
			$ids = $wpdb->get_col( $wpdb->prepare( "SELECT company_id FROM $a WHERE activity_code = %s", $code ) );
			foreach ( $ids as $cid ) {
				$has = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $a WHERE company_id = %d AND activity_code = %s", $cid, $replacement ) );
				if ( $has ) {
					$wpdb->delete( $a, array( 'company_id' => $cid, 'activity_code' => $code ) );
				} else {
					$wpdb->update( $a, array( 'activity_code' => $replacement ), array( 'company_id' => $cid, 'activity_code' => $code ) );
				}
				self::flush( $cid );
			}
			// phpcs:enable
		}
		$t = self::type_settings();
		$t['deleted'][] = $code;
		foreach ( (array) $t['custom'] as $c => $codes ) {
			$t['custom'][ $c ] = array_values( array_diff( (array) $codes, array( $code ) ) );
		}
		foreach ( array( 'labels', 'desc', 'image', 'order' ) as $k ) {
			unset( $t[ $k ][ $code ] );
		}
		$t['disabled'] = array_values( array_diff( (array) $t['disabled'], array( $code ) ) );
		$t['deleted']  = array_values( array_unique( $t['deleted'] ) );
		update_option( self::TYPES_OPTION, $t, false );
		return true;
	}

	/**
	 * Accounts shown "featured" on the home page: those the administrator (or,
	 * later, a subscription through the sm_company_featured filter) put forward,
	 * then the latest verified accounts with a logo to fill the row.
	 *
	 * @return array{0:object[],1:int[]} Companies, and the ids that are really featured.
	 */
	public static function featured( $class, $limit = 4 ) {
		global $wpdb;
		$t   = sm_table( 'companies' );
		$now = self::now();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$ids = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $t WHERE deleted_at IS NULL AND status = 'active' AND account_class = %s AND featured_until IS NOT NULL AND featured_until >= %s ORDER BY featured_priority ASC, featured_until DESC LIMIT 24", $class, $now ) ) );
		// phpcs:enable
		$ids = array_values( array_unique( array_map( 'intval', (array) apply_filters( 'sm_company_featured', $ids, $class ) ) ) );
		$featured = array_slice( $ids, 0, $limit );
		$out      = array_values( array_filter( array_map( array( __CLASS__, 'get' ), $featured ) ) );
		if ( count( $out ) < $limit ) {
			$fill = self::query( array( 'class' => $class, 'min_level' => 1, 'has_logo' => true, 'per_page' => $limit + count( $out ), 'orderby' => 'newest' ) )['items'];
			foreach ( $fill as $c ) {
				if ( count( $out ) >= $limit ) {
					break;
				}
				if ( ! in_array( (int) $c->id, $featured, true ) ) {
					$out[] = $c;
				}
			}
		}
		return array( $out, $featured );
	}

	/** Label shown on a featured account ('' when it is not featured now). */
	public static function featured_label( $company ) {
		if ( empty( $company->featured_until ) || $company->featured_until < self::now() ) {
			return '';
		}
		$labels = self::featured_labels();
		return $labels[ $company->featured_label ] ?? $labels['featured'];
	}

	public static function featured_labels() {
		return array(
			'featured'  => __( 'Featured', 'sm-core' ),
			'sponsored' => __( 'Sponsored', 'sm-core' ),
			'vip'       => 'VIP',
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
		$class = in_array( $data['account_class'] ?? '', self::CLASSES, true ) ? $data['account_class'] : 'company';
		$allowed_activities = self::activities( $class );
		$activities = array_values( array_intersect( $activities, $allowed_activities ) );
		if ( ! self::chooses_activities( $class ) ) {
			$activities = self::activities( $class, true );
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
		// Types the administrator hid stay allowed for accounts that already have them.
		$allowed    = array_merge( self::activities( $company->account_class ), array_intersect( (array) $company->activities, self::activities( $company->account_class, true ) ) );
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
			if ( isset( $data['profile']['experience_years'] ) ) {
				$years = (int) $data['profile']['experience_years'];
				$profile['experience_years'] = max( 0, min( 70, $years ) );
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
		if ( array_key_exists( 'featured_until', $fields ) ) {
			$d = (string) $fields['featured_until'];
			$clean['featured_until'] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) ? $d . ' 23:59:59' : null;
		}
		if ( isset( $fields['featured_label'] ) ) {
			$clean['featured_label'] = isset( self::featured_labels()[ $fields['featured_label'] ] ) ? $fields['featured_label'] : 'featured';
		}
		if ( isset( $fields['featured_priority'] ) ) {
			$clean['featured_priority'] = max( 0, min( 999, (int) $fields['featured_priority'] ) );
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
		$args  = wp_parse_args( $args, array( 'class' => '', 'activity' => '', 'wilaya' => '', 'status' => 'active', 'search' => '', 'per_page' => 20, 'page' => 1, 'min_level' => 0, 'orderby' => 'created_at', 'has_logo' => false ) );
		$t     = sm_table( 'companies' );
		$where = array( "$t.deleted_at IS NULL" );
		$vals  = array();
		if ( $args['status'] ) {
			$where[] = "$t.status = %s";
			$vals[]  = $args['status'];
		}
		if ( 'public' === $args['class'] ) {
			$where[] = "$t.account_class IN ('company','professional')";
		} elseif ( $args['class'] ) {
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
		if ( $args['has_logo'] ) {
			$where[] = "$t.logo_id > 0";
		}
		if ( $args['search'] ) {
			$where[] = "($t.name LIKE %s OR $t.name_ar LIKE %s OR $t.title_fr LIKE %s OR $t.title_ar LIKE %s)";
			$vals[]  = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$vals[]  = '%' . $wpdb->esc_like( $args['search'] ) . '%';
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
		$now   = self::now();
		$feat  = "(CASE WHEN $t.featured_until IS NOT NULL AND $t.featured_until >= '" . esc_sql( $now ) . "' THEN 0 ELSE 1 END)";
		$orders = array(
			'name'     => "$t.name ASC",
			'newest'   => "$t.created_at DESC, $t.id DESC",
			'featured' => "$feat ASC, $t.featured_priority ASC, $t.verification_level DESC, $t.created_at DESC",
		);
		$order    = $orders[ $args['orderby'] ] ?? "$t.verification_level DESC, $t.created_at DESC";
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
