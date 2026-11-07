<?php
/**
 * Bridge between sm-core and the sm-marketplace theme (docs/DATA-CONTRACT.md of the theme).
 *
 * Answers the theme's data filters with real data. Returning null lets the
 * theme fall back to its demo provider (used for listings until Phase 4).
 *
 * @package SM\Core
 */

namespace SM\Core\Theme;

use SM\Core\Accounts\Companies;
use SM\Core\Accounts\PhoneVerification;
use SM\Core\Accounts\Wilayas;
use SM\Core\Enquiries\Enquiries;
use SM\Core\Notifications\Notifications;
use SM\Core\Permissions\Permissions;
use SM\Core\Support\Forms;
use SM\Core\Verification\Verifications;

defined( 'ABSPATH' ) || exit;

final class Bridge {

	public static function init() {
		add_filter( 'sm_theme_data_company', array( __CLASS__, 'company' ), 10, 2 );
		add_filter( 'sm_theme_data_companies', array( __CLASS__, 'companies' ), 10, 2 );
		add_filter( 'sm_theme_data_wilayas', array( __CLASS__, 'wilayas' ) );
		add_filter( 'sm_theme_data_wilaya_centres', static fn( $r ) => null !== $r ? $r : Wilayas::centres() );
		add_filter( 'sm_theme_data_map', array( __CLASS__, 'map' ) );
		add_filter( 'sm_theme_data_account', array( __CLASS__, 'account' ) );
		add_filter( 'sm_theme_data_enquiries', array( __CLASS__, 'enquiries' ), 10, 2 );
		add_filter( 'sm_theme_data_notifications', array( __CLASS__, 'notifications' ) );
		add_filter( 'sm_theme_data_verification', array( __CLASS__, 'verification' ) );
		add_filter( 'sm_theme_data_stats', array( __CLASS__, 'stats' ) );
		add_filter( 'sm_theme_notices', array( __CLASS__, 'notices' ) );
		add_filter( 'sm_form_old', array( __CLASS__, 'old' ), 10, 3 );
		add_filter( 'sm_form_action', array( __CLASS__, 'form_action' ), 10, 2 );
		add_action( 'sm_form_fields', array( __CLASS__, 'form_fields' ) );
		add_filter( 'sm_theme_demo_account', '__return_false' );
	}

	/** Company row -> theme shape. */
	public static function shape_company( $c ) {
		$initials = '';
		foreach ( preg_split( '/\s+/u', trim( remove_accents( $c->name ) ) ) as $word ) {
			if ( '' !== $word && mb_strlen( $initials ) < 2 ) {
				$initials .= mb_strtoupper( mb_substr( $word, 0, 1 ) );
			}
		}
		$name_ar = $c->name_ar ? $c->name_ar : $c->name;
		return array(
			'id'             => (int) $c->id,
			'slug'           => $c->slug,
			'real'           => true,
			'name'           => array( 'fr' => $c->name, 'ar' => $name_ar ),
			'class'          => $c->account_class,
			'activities'     => $c->activities,
			'wilaya'         => $c->wilaya,
			'verification'   => (int) $c->verification_level,
			'since'          => substr( $c->created_at, 0, 4 ),
			'phone'          => $c->phone,
			'email'          => $c->email,
			'website'        => $c->website,
			'initials'       => $initials ? $initials : 'SM',
			'about'          => array( 'fr' => (string) $c->about_fr, 'ar' => (string) $c->about_ar ),
			'title'          => ( $c->title_fr || $c->title_ar ) ? array( 'fr' => $c->title_fr, 'ar' => $c->title_ar ) : null,
			'qualifications' => array_map( static fn( $q ) => array( 'fr' => $q, 'ar' => $q ), $c->profile['qualifications'] ?? array() ),
			'specialities'   => array_map( static fn( $s ) => array( 'fr' => $s, 'ar' => $s ), $c->profile['specialities'] ?? array() ),
			'status'         => $c->status,
			'location'       => Companies::public_location( $c ),
		);
	}

	public static function company( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$c = Companies::get_by_slug( (string) ( $args['slug'] ?? '' ) );
		return ( $c && 'active' === $c->status ) ? self::shape_company( $c ) : null;
	}

	/** Real directory once at least one company exists; demo otherwise. */
	public static function companies( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$query = Companies::query( array( 'class' => $args['class'] ?? '', 'per_page' => 60 ) );
		if ( ! $query['total'] ) {
			return null;
		}
		return array_map( array( __CLASS__, 'shape_company' ), $query['items'] );
	}

	public static function wilayas() {
		$all = Wilayas::all();
		return $all ? $all : null;
	}

	/**
	 * Map settings for the theme: tile server, attribution and Algeria bounds.
	 * Filter sm_map_tiles to use another provider (e.g. a paid one on high traffic).
	 */
	public static function map( $result ) {
		if ( null !== $result ) {
			return $result;
		}
		return apply_filters(
			'sm_map_tiles',
			array(
				'tiles'       => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
				'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
				'max_zoom'    => 19,
				'bounds'      => Companies::BOUNDS,
				'country'     => array( 28.0, 2.6, 5 ),
			)
		);
	}

	public static function stats( $result ) {
		return $result;
	}

	/** Account of the signed-in user, or null (no company). */
	public static function account( $result ) {
		if ( null !== $result || ! is_user_logged_in() ) {
			return $result;
		}
		$user    = wp_get_current_user();
		$company = Companies::for_user( $user->ID );
		if ( ! $company ) {
			return null;
		}
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$active = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . sm_table( 'listing_index' ) . " WHERE company_id = %d AND status = 'published'", $company->id ) );
		$is_eng = 'engineering' === $company->account_class;
		$can_manage = Companies::can_manage( $company->id, $user->ID );

		return array(
			'real'          => true,
			'class'         => $company->account_class,
			'company'       => self::shape_company( $company ),
			'edit'          => array(
				'name'           => $company->name,
				'name_ar'        => $company->name_ar,
				'wilaya'         => $company->wilaya,
				'commune'        => $company->commune,
				'address'        => $company->address,
				'lat'            => null === $company->lat ? '' : (string) round( (float) $company->lat, 6 ),
				'lng'            => null === $company->lng ? '' : (string) round( (float) $company->lng, 6 ),
				'map_public'     => (int) $company->map_public,
				'phone'          => $company->phone,
				'email'          => $company->email,
				'website'        => $company->website,
				'about_fr'       => (string) $company->about_fr,
				'about_ar'       => (string) $company->about_ar,
				'title_fr'       => $company->title_fr,
				'title_ar'       => $company->title_ar,
				'qualifications' => implode( "\n", $company->profile['qualifications'] ?? array() ),
				'specialities'   => implode( "\n", $company->profile['specialities'] ?? array() ),
			) + ( $can_manage ? Companies::legal_numbers( $company ) : array() ),
			'user'          => $user->display_name,
			'user_email'    => $user->user_email,
			'user_phone'    => (string) get_user_meta( $user->ID, 'sm_phone', true ),
			'role'          => Companies::member_role( $company->id, $user->ID ),
			'can_manage'    => $can_manage,
			'language'      => \SM\Core\I18n\Language::user_language( $user->ID ),
			'plan'          => null,
			'usage'         => array(),
			'caps'          => Permissions::map( $company->id ),
			'contact_verified' => (bool) get_user_meta( $user->ID, 'sm_contact_verified', true ) || (int) $company->verification_level >= 1,
			'unread'        => Notifications::unread_count( $user->ID ),
			'kpis'          => $is_eng
				? array( 'requests' => Enquiries::count_new( $company->id ), 'views' => 0, 'quotes' => 0 )
				: array( 'active' => $active, 'views' => 0, 'enquiries' => Enquiries::count_new( $company->id ), 'offers' => 0 ),
		);
	}

	/** Enquiries received by the current company, theme shape. */
	public static function enquiries( $result, $args ) {
		if ( null !== $result || ! is_user_logged_in() ) {
			return $result;
		}
		$company = Companies::for_user( get_current_user_id() );
		if ( ! $company ) {
			return array();
		}
		$box = ( $args['box'] ?? 'received' ) === 'sent' ? Enquiries::sent( get_current_user_id() ) : Enquiries::received( $company->id );
		return array_map(
			static fn( $e ) => array(
				'id'       => (int) $e->id,
				'kind'     => $e->kind,
				'name'     => $e->name,
				'phone'    => $e->phone,
				'email'    => $e->email,
				'quantity' => $e->quantity,
				'subject'  => $e->subject,
				'message'  => $e->message,
				'status'   => $e->status,
				'date'     => get_date_from_gmt( $e->created_at ),
				'module'   => $e->module,
				'entity'   => (int) $e->entity_id,
			),
			$box
		);
	}

	public static function notifications( $result ) {
		if ( null !== $result || ! is_user_logged_in() ) {
			return $result;
		}
		return array_map(
			static fn( $n ) => array( 'id' => (int) $n->id, 'title' => $n->title, 'text' => $n->text, 'link' => $n->link, 'read' => (bool) $n->read_at, 'date' => get_date_from_gmt( $n->created_at ) ),
			Notifications::for_user( get_current_user_id(), 10 )
		);
	}

	/** Verification state for the dashboard. */
	public static function verification( $result ) {
		if ( null !== $result || ! is_user_logged_in() ) {
			return $result;
		}
		$company = Companies::for_user( get_current_user_id() );
		if ( ! $company ) {
			return null;
		}
		$latest = Verifications::latest( $company->id );
		$state  = get_user_meta( get_current_user_id(), PhoneVerification::META, true );
		return array(
			'level'        => (int) $company->verification_level,
			'target_level' => Verifications::target_level( $company->account_class ),
			'channel'      => PhoneVerification::channel(),
			'code_pending' => is_array( $state ) && ! empty( $state['hash'] ) && time() < (int) $state['expires'],
			'sent_message' => PhoneVerification::sent_message( get_current_user_id() ),
			'documents'    => Verifications::document_types( $company->account_class ),
			'request'      => $latest ? array(
				'status'   => $latest->status,
				'note'     => (string) $latest->decision_note,
				'date'     => get_date_from_gmt( $latest->created_at ),
				'files'    => array_keys( $latest->documents ),
			) : null,
			'legal'        => Companies::legal_numbers( $company ),
		);
	}

	public static function notices( $notices ) {
		$n = Forms::current_notice();
		foreach ( (array) $n['errors'] as $field => $message ) {
			$notices[] = array( 'type' => 'error', 'message' => $message, 'field' => is_string( $field ) ? $field : '' );
		}
		if ( $n['success'] ) {
			$notices[] = array( 'type' => 'success', 'message' => $n['success'], 'field' => '' );
		}
		return $notices;
	}

	public static function old( $value, $field, $default = '' ) {
		$n = Forms::current_notice();
		return array_key_exists( $field, (array) $n['old'] ) ? $n['old'][ $field ] : $value;
	}

	public static function form_action( $url, $form ) {
		return Forms::action_url();
	}

	public static function form_fields( $form ) {
		Forms::fields( $form );
		if ( 'login' === $form ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '';
			if ( $redirect ) {
				printf( '<input type="hidden" name="sm_redirect" value="%s">', esc_url( $redirect ) );
			}
		}
	}
}
