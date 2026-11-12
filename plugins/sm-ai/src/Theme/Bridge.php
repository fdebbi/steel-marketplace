<?php
/**
 * Data for the theme (sm_theme_data):
 *  - ai_tools  : the assistant tools for the current visitor (available or why not, remaining uses, limits);
 *  - ai_result : a result of the current user (token);
 *  - ai_search : what the smart search understood (token of the results page);
 *  - ai_search_ready : whether the smart search box can be shown.
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
	}

	public static function tools( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$company = sm_current_company();
		$s       = Settings::all();
		$out     = array();
		foreach ( array( 'listing', 'recognize', 'document' ) as $id ) {
			$class = Gateway::feature( $id );
			$ok    = Gateway::check( $id, $company );
			list( $provider ) = Settings::route( $id );
			$out[ $id ] = array(
				'id'         => $id,
				'label'      => $class::label(),
				'available'  => true === $ok,
				'reason'     => is_wp_error( $ok ) ? $ok->get_error_message() : '',
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
