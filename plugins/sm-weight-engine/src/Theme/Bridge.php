<?php
/**
 * Data for the sm-marketplace theme (data contract, sm_theme_data_* filters).
 *
 * @package SM\WeightEngine
 */

namespace SM\WeightEngine\Theme;

use SM\WeightEngine\Calculator;
use SM\WeightEngine\Catalogue;
use SM\WeightEngine\Drawing;
use SM\WeightEngine\Presenter;
use SM\WeightEngine\Settings;

defined( 'ABSPATH' ) || exit;

final class Bridge {

	public static function init() {
		add_filter( 'sm_theme_data_weight_family', array( __CLASS__, 'family' ), 10, 2 );
		add_filter( 'sm_theme_data_weight_section', array( __CLASS__, 'section' ), 10, 2 );
		add_filter( 'sm_theme_data_weight_calc', array( __CLASS__, 'calc' ), 10, 2 );
		add_filter( 'sm_theme_data_weight_drawing', array( __CLASS__, 'drawing' ), 10, 2 );
		add_filter( 'sm_theme_data_weight_families', static fn( $r ) => null !== $r ? $r : array_values( array_map( array( Presenter::class, 'family' ), Catalogue::families() ) ) );
		add_filter(
			'sm_theme_data_weight_settings',
			static function ( $r ) {
				if ( null !== $r ) {
					return $r;
				}
				$s = Settings::all();
				return array(
					'bar_lengths'    => $s['bar_lengths'],
					'default_length' => $s['default_length'],
					'max_length'     => $s['max_length'],
					'density'        => $s['density'],
					'version'        => array( 'engine' => SM_WEIGHT_VERSION, 'reference' => SM_WEIGHT_REF_VERSION ),
				);
			}
		);
	}

	/** A family with its sizes, or null. */
	public static function family( $r, $args ) {
		if ( null !== $r ) {
			return $r;
		}
		$f = Catalogue::family( $args['code'] ?? '' );
		if ( ! $f || ! (int) $f->active ) {
			return null;
		}
		$data             = Presenter::family( $f );
		$data['sections'] = array_map( array( Presenter::class, 'section' ), Catalogue::sections( $f->code ) );
		return $data;
	}

	/** One size with its drawing, or null. */
	public static function section( $r, $args ) {
		if ( null !== $r ) {
			return $r;
		}
		$s = Catalogue::get( (int) ( $args['id'] ?? 0 ) );
		return $s && 'active' === $s->status ? Presenter::section( $s, true ) : null;
	}

	/** Calculation result, or ['error' => message]. */
	public static function calc( $r, $args ) {
		if ( null !== $r ) {
			return $r;
		}
		$res = Calculator::calculate( (array) $args );
		return is_wp_error( $res ) ? array( 'error' => $res->get_error_message(), 'code' => $res->get_error_code() ) : $res;
	}

	/** Drawing of a typed size of a formula family: ['designation','dims','drawing'], ['error' => message] or null. */
	public static function drawing( $r, $args ) {
		if ( null !== $r ) {
			return $r;
		}
		$f = Catalogue::family( $args['family'] ?? '' );
		if ( ! $f || 'formula' !== $f->method ) {
			return null;
		}
		$dims = Calculator::read_dims( $f->shape, (array) ( $args['dims'] ?? array() ) );
		if ( is_wp_error( $dims ) ) {
			return array( 'error' => $dims->get_error_message() );
		}
		$title = Calculator::designation( $f, $dims );
		return array( 'designation' => $title, 'dims' => $dims, 'drawing' => Drawing::svg( $f->shape, $dims, array( 'title' => $title ) ) );
	}
}
