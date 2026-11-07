<?php
/**
 * Service exposed to the other sm-* plugins:
 *   $weight = SM\Core\Modules::service( 'weight' ); // null when the engine is off
 *   $weight->calculate( array( 'family' => 'IPE', 'section' => 'IPE 200', 'bars' => 10 ) );
 *
 * @package SM\WeightEngine
 */

namespace SM\WeightEngine;

defined( 'ABSPATH' ) || exit;

class Service {

	public function version() {
		return array( 'engine' => SM_WEIGHT_VERSION, 'reference' => SM_WEIGHT_REF_VERSION );
	}

	/** @return array<string,array> */
	public function families() {
		return array_map( array( Presenter::class, 'family' ), Catalogue::families() );
	}

	public function sections( $family ) {
		return array_map( array( Presenter::class, 'section' ), Catalogue::sections( $family ) );
	}

	public function section( $id ) {
		$s = Catalogue::get( $id );
		return $s ? Presenter::section( $s, true ) : null;
	}

	/** @return array|\WP_Error */
	public function calculate( array $input ) {
		return Calculator::calculate( $input );
	}

	public function drawing( $shape, array $dims, $title = '' ) {
		return Drawing::svg( $shape, $dims, array( 'title' => $title ) );
	}
}
