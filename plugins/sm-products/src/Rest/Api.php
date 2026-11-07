<?php
/**
 * REST API, namespace sm/v1 (public, read-only, published products only):
 *   GET /products?category=&family=&wilaya=&q=&sort=&page=
 *   GET /products/{listing_id}
 *   GET /products/categories
 *
 * @package SM\Products
 */

namespace SM\Products\Rest;

use SM\Products\Categories;
use SM\Products\Presenter;
use SM\Products\Products;
use SM\Products\Theme\Bridge;

defined( 'ABSPATH' ) || exit;

final class Api {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		register_rest_route( 'sm/v1', '/products', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => array( __CLASS__, 'index' ) ) );
		register_rest_route( 'sm/v1', '/products/categories', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => array( __CLASS__, 'categories' ) ) );
		register_rest_route( 'sm/v1', '/products/(?P<id>\d+)', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => array( __CLASS__, 'show' ) ) );
	}

	public static function index( \WP_REST_Request $r ) {
		$market = sanitize_key( (string) ( $r['market'] ?? 'products' ) );
		if ( ! \SM\Products\Markets::exists( $market ) ) {
			return new \WP_Error( 'smp_market', __( 'Unknown market.', 'sm-products' ), array( 'status' => 400 ) );
		}
		$args = array_merge( $r->get_params(), array( 'market' => $market, 'per_page' => min( 50, (int) ( $r['per_page'] ?? 20 ) ) ) );
		return rest_ensure_response( Bridge::listings( null, $args ) );
	}

	public static function categories( \WP_REST_Request $r ) {
		return rest_ensure_response( Bridge::product_categories( null, array( 'market' => sanitize_key( (string) ( $r['market'] ?? '' ) ) ) ) );
	}

	public static function show( \WP_REST_Request $r ) {
		$p = Products::by_listing( (int) $r['id'] );
		if ( ! $p || 'published' !== $p->status ) {
			return new \WP_Error( 'smp_not_found', __( 'This listing is not available.', 'sm-products' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( Presenter::detail( $p ) );
	}
}
