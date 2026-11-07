<?php
/**
 * REST API, namespace sm/v1 (public, read-only):
 *   GET /weight/families                 section families with a drawing
 *   GET /weight/families/{code}          sizes of a family
 *   GET /weight/sections/{id}            one size with its exact drawing and source
 *   GET /weight/drawing?family=&dims[]   drawing of a typed size (formula families)
 *   GET /weight/calculate?...            see Calculator
 *   GET /weight/settings                 commercial bar lengths
 * Add ?lang=ar for Arabic messages.
 *
 * @package SM\WeightEngine
 */

namespace SM\WeightEngine\Rest;

use SM\WeightEngine\Calculator;
use SM\WeightEngine\Catalogue;
use SM\WeightEngine\Drawing;
use SM\WeightEngine\Presenter;
use SM\WeightEngine\Settings;

defined( 'ABSPATH' ) || exit;

final class Api {

	const NS = 'sm/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		$public = '__return_true';
		register_rest_route( self::NS, '/weight/families', array( 'methods' => 'GET', 'permission_callback' => $public, 'callback' => array( __CLASS__, 'families' ) ) );
		register_rest_route(
			self::NS,
			'/weight/families/(?P<code>[A-Za-z]{1,12})',
			array( 'methods' => 'GET', 'permission_callback' => $public, 'callback' => array( __CLASS__, 'family' ) )
		);
		register_rest_route(
			self::NS,
			'/weight/sections/(?P<id>\d+)',
			array( 'methods' => 'GET', 'permission_callback' => $public, 'callback' => array( __CLASS__, 'section' ) )
		);
		register_rest_route( self::NS, '/weight/drawing', array( 'methods' => 'GET', 'permission_callback' => $public, 'callback' => array( __CLASS__, 'drawing' ) ) );
		register_rest_route( self::NS, '/weight/calculate', array( 'methods' => 'GET', 'permission_callback' => $public, 'callback' => array( __CLASS__, 'calculate' ) ) );
		register_rest_route( self::NS, '/weight/settings', array( 'methods' => 'GET', 'permission_callback' => $public, 'callback' => array( __CLASS__, 'settings' ) ) );
	}

	private static function cached( $data ) {
		$res = rest_ensure_response( $data );
		$res->header( 'Cache-Control', 'public, max-age=3600' );
		return $res;
	}

	public static function families() {
		return self::cached( array_values( array_map( array( Presenter::class, 'family' ), Catalogue::families() ) ) );
	}

	public static function family( \WP_REST_Request $r ) {
		$f = Catalogue::family( $r['code'] );
		if ( ! $f || ! (int) $f->active ) {
			return new \WP_Error( 'smw_family', __( 'Unknown section family.', 'sm-weight-engine' ), array( 'status' => 404 ) );
		}
		$data             = Presenter::family( $f );
		$data['sections'] = array_map( array( Presenter::class, 'section' ), Catalogue::sections( $f->code ) );
		return self::cached( $data );
	}

	public static function section( \WP_REST_Request $r ) {
		$s = Catalogue::get( (int) $r['id'] );
		if ( ! $s || 'active' !== $s->status ) {
			return new \WP_Error( 'smw_section', __( 'Unknown size.', 'sm-weight-engine' ), array( 'status' => 404 ) );
		}
		return self::cached( Presenter::section( $s, true ) );
	}

	public static function drawing( \WP_REST_Request $r ) {
		$f = Catalogue::family( $r['family'] );
		if ( ! $f || 'formula' !== $f->method ) {
			return new \WP_Error( 'smw_family', __( 'Unknown section family.', 'sm-weight-engine' ), array( 'status' => 404 ) );
		}
		$dims = Calculator::read_dims( $f->shape, (array) $r['dims'] );
		if ( is_wp_error( $dims ) ) {
			return $dims;
		}
		return self::cached( array( 'designation' => Calculator::designation( $f, $dims ), 'dims' => $dims, 'drawing' => Drawing::svg( $f->shape, $dims, array( 'title' => Calculator::designation( $f, $dims ) ) ) ) );
	}

	public static function calculate( \WP_REST_Request $r ) {
		$in     = array_intersect_key( $r->get_params(), array_flip( array( 'family', 'section', 'dims', 'mode', 'length', 'width', 'bars', 'total_kg' ) ) );
		$result = Calculator::calculate( $in );
		return is_wp_error( $result ) ? $result : self::cached( $result );
	}

	public static function settings() {
		$s = Settings::all();
		return self::cached(
			array(
				'bar_lengths'    => $s['bar_lengths'],
				'default_length' => $s['default_length'],
				'max_length'     => $s['max_length'],
				'density'        => $s['density'],
			)
		);
	}
}
