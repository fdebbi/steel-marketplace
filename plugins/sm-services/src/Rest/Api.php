<?php
/**
 * Public REST endpoints (read only): published services and one service.
 *
 * @package SM\Services
 */

namespace SM\Services\Rest;

use SM\Services\Presenter;
use SM\Services\Services;
use SM\Services\Theme\Bridge;

defined( 'ABSPATH' ) || exit;

final class Api {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		register_rest_route( 'sm/v1', '/services', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => array( __CLASS__, 'index' ) ) );
		register_rest_route( 'sm/v1', '/services/(?P<id>\d+)', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => array( __CLASS__, 'show' ) ) );
	}

	public static function index( \WP_REST_Request $r ) {
		return rest_ensure_response( (array) Bridge::services( null, array_merge( $r->get_params(), array( 'per_page' => min( 50, (int) ( $r['per_page'] ?? 20 ) ) ) ) ) );
	}

	public static function show( \WP_REST_Request $r ) {
		$s = Services::get( (int) $r['id'] );
		if ( ! $s || 'published' !== $s->status ) {
			return new \WP_Error( 'sms_not_found', __( 'This service is not available.', 'sm-services' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( Presenter::detail( $s ) );
	}
}
