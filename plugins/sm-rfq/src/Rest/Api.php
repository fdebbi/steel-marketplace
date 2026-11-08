<?php
/**
 * Public REST endpoints (read only): open requests and one request.
 * Offers are never exposed here.
 *
 * @package SM\Rfq
 */

namespace SM\Rfq\Rest;

use SM\Rfq\Presenter;
use SM\Rfq\Rfqs;
use SM\Rfq\Theme\Bridge;

defined( 'ABSPATH' ) || exit;

final class Api {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		register_rest_route( 'sm/v1', '/rfqs', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => array( __CLASS__, 'index' ) ) );
		register_rest_route( 'sm/v1', '/rfqs/(?P<id>\d+)', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => array( __CLASS__, 'show' ) ) );
	}

	public static function index( \WP_REST_Request $r ) {
		return rest_ensure_response( Bridge::rfqs( null, array_merge( $r->get_params(), array( 'per_page' => min( 50, (int) ( $r['per_page'] ?? 20 ) ) ) ) ) );
	}

	public static function show( \WP_REST_Request $r ) {
		$rfq = Rfqs::get( (int) $r['id'] );
		if ( ! $rfq || in_array( $rfq->status, array( 'pending', 'rejected' ), true ) ) {
			return new \WP_Error( 'smq_not_found', __( 'This request is not available.', 'sm-rfq' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( Presenter::rfq( $rfq ) );
	}
}
