<?php
/**
 * REST API sm/v1 (foundation for the future mobile apps).
 *
 * Authentication: WordPress cookie + X-WP-Nonce (website) or Application
 * Passwords (mobile apps, HTTPS). Responses are localized with ?lang=fr|ar.
 *
 * @package SM\Core
 */

namespace SM\Core\Rest;

use SM\Core\Accounts\Companies;
use SM\Core\Accounts\Wilayas;
use SM\Core\Enquiries\Enquiries;
use SM\Core\I18n\Language;
use SM\Core\Notifications\Notifications;
use SM\Core\Permissions\Permissions;

defined( 'ABSPATH' ) || exit;

final class Api {

	const NS = 'sm/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		$auth = static fn() => is_user_logged_in();

		register_rest_route( self::NS, '/reference/wilayas', array(
			'methods'             => 'GET',
			'callback'            => static fn() => rest_ensure_response( self::wilayas() ),
			'permission_callback' => '__return_true',
		) );
		register_rest_route( self::NS, '/reference/activities', array(
			'methods'             => 'GET',
			'callback'            => static fn() => rest_ensure_response( self::activities() ),
			'permission_callback' => '__return_true',
		) );
		register_rest_route( self::NS, '/companies', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'companies' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'class'    => array( 'type' => 'string', 'enum' => array( '', 'company', 'professional' ), 'default' => '' ),
				'activity' => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_key' ),
				'wilaya'   => array( 'type' => 'string', 'default' => '', 'pattern' => '^([0-9]{2})?$' ),
				'search'   => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
				'page'     => array( 'type' => 'integer', 'default' => 1, 'minimum' => 1 ),
				'per_page' => array( 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100 ),
			),
		) );
		register_rest_route( self::NS, '/companies/(?P<slug>[a-z0-9-]+)', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'company' ),
			'permission_callback' => '__return_true',
		) );
		register_rest_route( self::NS, '/me', array(
			array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'me' ), 'permission_callback' => $auth ),
			array(
				'methods'             => 'PATCH',
				'callback'            => array( __CLASS__, 'update_me' ),
				'permission_callback' => $auth,
				'args'                => array( 'language' => array( 'type' => 'string', 'enum' => array_keys( Language::languages() ) ) ),
			),
		) );
		register_rest_route( self::NS, '/me/company', array(
			'methods'             => 'PATCH',
			'callback'            => array( __CLASS__, 'update_company' ),
			'permission_callback' => static function () {
				$c = Companies::for_user( get_current_user_id() );
				return $c && Companies::can_manage( $c->id, get_current_user_id() );
			},
		) );
		register_rest_route( self::NS, '/me/can/(?P<cap>[a-z.]+)', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'can' ),
			'permission_callback' => $auth,
		) );
		register_rest_route( self::NS, '/me/notifications', array(
			'methods'             => 'GET',
			'callback'            => static fn() => rest_ensure_response( array_map( array( __CLASS__, 'notification' ), Notifications::for_user( get_current_user_id(), 50 ) ) ),
			'permission_callback' => $auth,
		) );
		register_rest_route( self::NS, '/me/notifications/(?P<id>\d+)/read', array(
			'methods'             => 'POST',
			'callback'            => static fn( $r ) => rest_ensure_response( array( 'updated' => (bool) Notifications::mark_read( get_current_user_id(), (int) $r['id'] ) ) ),
			'permission_callback' => $auth,
		) );
		register_rest_route( self::NS, '/me/enquiries', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'my_enquiries' ),
			'permission_callback' => $auth,
		) );
	}

	public static function wilayas() {
		$out = array();
		$centres = Wilayas::centres();
		foreach ( Wilayas::all() as $code => $names ) {
			$out[] = array( 'code' => (string) $code, 'name' => $names[ Language::current() ] ?? $names['fr'], 'names' => $names, 'centre' => $centres[ $code ] ?? null );
		}
		return $out;
	}

	public static function activities() {
		$labels = Companies::activity_labels();
		$out    = array();
		foreach ( Companies::activities() as $class => $codes ) {
			foreach ( $codes as $code ) {
				$out[] = array( 'code' => $code, 'class' => $class, 'label' => $labels[ $code ] ?? $code );
			}
		}
		return $out;
	}

	/** Public representation of a company (no private data). */
	public static function company_public( $c ) {
		return array(
			'slug'          => $c->slug,
			'account_class' => $c->account_class,
			'name'          => array( 'fr' => $c->name, 'ar' => $c->name_ar ?: $c->name ),
			'title'         => array( 'fr' => $c->title_fr, 'ar' => $c->title_ar ),
			'about'         => array( 'fr' => (string) $c->about_fr, 'ar' => (string) $c->about_ar ),
			'activities'    => $c->activities,
			'wilaya'        => $c->wilaya,
			'phone'         => $c->phone,
			'website'       => $c->website,
			'verification'  => (int) $c->verification_level,
			'since'         => substr( $c->created_at, 0, 4 ),
			'profile'       => $c->profile,
			'location'      => Companies::public_location( $c ),
			'url'           => sm_url( 'company', array( 'c' => $c->slug ) ),
		);
	}

	public static function companies( \WP_REST_Request $r ) {
		$result = Companies::query(
			array(
				'class'    => $r['class'] ? $r['class'] : 'public',
				'activity' => $r['activity'],
				'wilaya'   => $r['wilaya'],
				'search'   => $r['search'],
				'page'     => $r['page'],
				'per_page' => $r['per_page'],
			)
		);
		$response = rest_ensure_response( array_map( array( __CLASS__, 'company_public' ), $result['items'] ) );
		$response->header( 'X-WP-Total', (string) $result['total'] );
		$response->header( 'X-WP-TotalPages', (string) ceil( $result['total'] / max( 1, (int) $r['per_page'] ) ) );
		return $response;
	}

	public static function company( \WP_REST_Request $r ) {
		$c = Companies::get_by_slug( $r['slug'] );
		if ( ! $c || 'active' !== $c->status ) {
			return new \WP_Error( 'sm_not_found', __( 'Company not found.', 'sm-core' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( self::company_public( $c ) );
	}

	public static function me() {
		$user    = wp_get_current_user();
		$company = Companies::for_user( $user->ID );
		return rest_ensure_response(
			array(
				'user'          => array(
					'id'       => $user->ID,
					'name'     => $user->display_name,
					'email'    => $user->user_email,
					'phone'    => (string) get_user_meta( $user->ID, 'sm_phone', true ),
					'language' => Language::user_language( $user->ID ),
				),
				'company'       => $company ? self::company_public( $company ) + array( 'role' => Companies::member_role( $company->id, $user->ID ) ) : null,
				'capabilities'  => $company ? Permissions::map( $company->id ) : array(),
				'notifications' => Notifications::unread_count( $user->ID ),
			)
		);
	}

	public static function update_me( \WP_REST_Request $r ) {
		if ( $r['language'] ) {
			update_user_meta( get_current_user_id(), Language::META, $r['language'] );
		}
		return self::me();
	}

	public static function update_company( \WP_REST_Request $r ) {
		$company = Companies::for_user( get_current_user_id() );
		$params  = $r->get_json_params() ?: $r->get_body_params();
		$allowed = array_intersect_key( (array) $params, array_flip( array( 'name', 'name_ar', 'wilaya', 'commune', 'address', 'phone', 'email', 'website', 'about_fr', 'about_ar', 'title_fr', 'title_ar' ) ) );
		if ( isset( $allowed['wilaya'] ) && ! Wilayas::exists( $allowed['wilaya'] ) ) {
			return new \WP_Error( 'sm_invalid_wilaya', __( 'Choose your wilaya.', 'sm-core' ), array( 'status' => 400 ) );
		}
		if ( array_key_exists( 'lat', (array) $params ) || array_key_exists( 'lng', (array) $params ) ) {
			$allowed['location'] = array( 'lat' => $params['lat'] ?? '', 'lng' => $params['lng'] ?? '' );
		}
		if ( isset( $params['map_public'] ) ) {
			$allowed['map_public'] = (bool) $params['map_public'];
		}
		$saved = Companies::update_profile( $company->id, $allowed );
		if ( is_wp_error( $saved ) ) {
			$saved->add_data( array( 'status' => 400 ) );
			return $saved;
		}
		if ( isset( $params['activities'] ) && Companies::chooses_activities( $company->account_class ) ) {
			Companies::set_activities( $company->id, (array) $params['activities'] );
		}
		\SM\Core\Audit\AuditLog::add( 'company.updated', 'company', $company->id, array( 'via' => 'rest' ), null, $company->id );
		return rest_ensure_response( self::company_public( Companies::get( $company->id ) ) );
	}

	public static function can( \WP_REST_Request $r ) {
		$result = sm_can_explain( $r['cap'] );
		return rest_ensure_response(
			array(
				'cap'     => $r['cap'],
				'allowed' => true === $result,
				'reason'  => is_wp_error( $result ) ? $result->get_error_code() : null,
				'message' => is_wp_error( $result ) ? $result->get_error_message() : null,
			)
		);
	}

	public static function notification( $n ) {
		return array( 'id' => (int) $n->id, 'type' => $n->type, 'title' => $n->title, 'text' => $n->text, 'link' => $n->link, 'read' => (bool) $n->read_at, 'created_at' => mysql_to_rfc3339( $n->created_at ) );
	}

	public static function my_enquiries() {
		$company = Companies::for_user( get_current_user_id() );
		$map     = static fn( $e ) => array(
			'id'         => (int) $e->id,
			'kind'       => $e->kind,
			'module'     => $e->module,
			'entity_id'  => (int) $e->entity_id,
			'name'       => $e->name,
			'phone'      => $e->phone,
			'email'      => $e->email,
			'quantity'   => $e->quantity,
			'subject'    => $e->subject,
			'message'    => $e->message,
			'status'     => $e->status,
			'created_at' => mysql_to_rfc3339( $e->created_at ),
		);
		return rest_ensure_response(
			array(
				'received' => $company ? array_map( $map, Enquiries::received( $company->id ) ) : array(),
				'sent'     => array_map( $map, Enquiries::sent( get_current_user_id() ) ),
			)
		);
	}
}
