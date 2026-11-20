<?php
/**
 * Data for the theme (sm_theme_data):
 *  - ai_tools  : the assistant tools for the current visitor (available or why not, remaining uses, limits);
 *  - ai_result : a result of the current user (token);
 *  - ai_search : what the smart search understood (token of the results page);
 *  - ai_search_ready : whether the smart search box can be shown;
 *  - ai_catalog : every AI service for the public page (0.2.0): state ok|login|upgrade|blocked, credits per use;
 *  - ai_describe : the description writer of the publication form (0.2.0).
 *
 * @package SM\Ai
 */

namespace SM\Ai\Theme;

use SM\Ai\Catalog;
use SM\Ai\Form;
use SM\Ai\Gateway;
use SM\Ai\Settings;

defined( 'ABSPATH' ) || exit;

final class Bridge {

	public static function init() {
		add_filter( 'sm_theme_data_ai_tools', array( __CLASS__, 'tools' ), 10, 2 );
		add_filter( 'sm_theme_data_ai_result', array( __CLASS__, 'result' ), 10, 2 );
		add_filter( 'sm_theme_data_ai_search', array( __CLASS__, 'search' ), 10, 2 );
		add_filter( 'sm_theme_data_ai_search_ready', static fn( $r ) => null !== $r ? $r : true === Gateway::check( 'search' ) );
		add_filter( 'sm_theme_data_ai_catalog', array( __CLASS__, 'catalog' ), 10, 2 );
		add_filter( 'sm_theme_data_ai_describe', array( __CLASS__, 'describe' ), 10, 2 );
		add_filter( 'sm_theme_data_ai_cutlist', array( __CLASS__, 'cutlist' ), 10, 2 );
	}

	/**
	 * DQE IA in the cutting optimiser (0.3.0): the service (state ok/login/upgrade/blocked,
	 * credits left) and, with args token, the result to check. null when not offered.
	 */
	public static function cutlist( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$s = self::service( 'cutlist', sm_current_company() );
		if ( ! $s ) {
			return null;
		}
		$token = preg_replace( '/[^A-Za-z0-9]/', '', (string) ( $args['token'] ?? '' ) );
		$entry = '' !== $token ? Form::cutlist_entry( $token ) : null;
		return $s + array(
			'result'    => $entry ? $entry['result'] : null,
			'for'       => $entry ? $entry['for'] : '',
			'token'     => $entry ? $token : '',
			'can_rfq'   => sm_can( 'rfq.create' ),
			'warehouse' => (bool) apply_filters( 'sm_theme_data_stock_enabled', null ),
		);
	}

	/** One service for the interface: available now, or why not and what to do. */
	private static function service( $id, $company ) {
		$class = Gateway::feature( $id );
		$f     = Settings::feature( $id );
		if ( ! $class || ! $f || empty( $f['enabled'] ) ) {
			return null;
		}
		$ok = Gateway::check( $id, $company );
		if ( is_wp_error( $ok ) && in_array( $ok->get_error_code(), array( 'sm_ai_off', 'sm_ai_config', 'sm_ai_products', 'sm_ai_engine' ), true ) ) {
			return null; // Not offered on this site.
		}
		return array(
			'id'        => $id,
			'label'     => $class::label(),
			'credits'   => (int) $class::credits(),
			'state'     => Form::state_of( $ok ),
			'reason'    => is_wp_error( $ok ) ? $ok->get_error_message() : '',
			'remaining' => Gateway::remaining( $id, $company ),
		);
	}

	public static function catalog( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$company = sm_current_company();
		$out     = array();
		foreach ( array( 'search', 'describe', 'cutlist', 'recognize', 'listing' ) as $id ) { // 0.4.0: « document » is read by the DQE IA (cutlist).
			$s = self::service( $id, $company );
			if ( $s ) {
				$out[ $id ] = $s;
			}
		}
		return $out ? array( 'services' => $out, 'logged_in' => is_user_logged_in(), 'has_company' => (bool) $company ) : null;
	}

	public static function describe( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$s = self::service( 'describe', sm_current_company() );
		if ( ! $s ) {
			return null;
		}
		return $s + array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'sm_ai_describe' ),
		);
	}

	public static function tools( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$company = sm_current_company();
		$s       = Settings::all();
		$out     = array();
		// 0.4.0: the « document » tab reads with the DQE IA (one tool: cutting, request, warehouse).
		foreach ( array( 'listing', 'recognize', 'document' ) as $tab ) {
			$id    = 'document' === $tab ? 'cutlist' : $tab;
			$class = Gateway::feature( $id );
			$ok    = Gateway::check( $id, $company );
			list( $provider ) = Settings::route( $id );
			$out[ $tab ] = array(
				'id'         => $tab,
				'feature'    => $id,
				'label'      => $class::label(),
				'available'  => true === $ok,
				'reason'     => is_wp_error( $ok ) ? $ok->get_error_message() : '',
				'state'      => Form::state_of( $ok ),
				'remaining'  => Gateway::remaining( $id, $company ),
				'quota'      => ( $f = Settings::feature( $id ) ) && $f['per_company_month'] > 0 ? (int) $f['per_company_month'] : null,
				'used'       => $company ? \SM\Ai\Usage::company_month( (int) $company->id, $id ) : 0,
				'simulation' => 'simulation' === $provider,
			);
		}
		$markets = array();
		if ( Catalog::has_products() ) {
			foreach ( \SM\Ai\Features\Listing::markets_allowed( $company ) as $k ) {
				$m             = \SM\Products\Markets::get( $k );
				$markets[ $k ] = $m['label'];
			}
		}
		return array(
			'tools'      => $out,
			'markets'    => $markets,
			'max_images' => (int) $s['max_images'],
			'image_mb'   => (int) $s['image_mb'],
			'pdf_mb'     => (int) $s['pdf_mb'],
			'can_rfq'    => sm_can( 'rfq.create' ),
		);
	}

	public static function result( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$r = Form::result( $args['token'] ?? '' );
		return $r ? array( 'tool' => $r['tool'], 'token' => preg_replace( '/[^A-Za-z0-9]/', '', (string) $args['token'] ), 'data' => $r['result'] ) : null;
	}

	public static function search( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		return Form::search_info( $args['token'] ?? '' );
	}
}
