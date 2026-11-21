<?php
/**
 * Array shapes returned by the REST API, the service and the theme bridge.
 *
 * @package SM\WeightEngine
 */

namespace SM\WeightEngine;

defined( 'ABSPATH' ) || exit;

final class Presenter {

	/** Illustrative sizes for the picture of families whose size is typed by the user (no values shown). */
	const ICON_DIMS = array(
		'rhs'    => array( 'h' => 120, 'b' => 80, 't' => 5 ),
		'flat'   => array( 'b' => 100, 't' => 12 ),
		'round'  => array( 'd' => 60 ),
		'square' => array( 'a' => 60 ),
		'plate'  => array( 't' => 10 ),
	);

	public static function family( $f ) {
		$sections = 'table' === $f->method || $f->count ? Catalogue::sections( $f->code ) : array();
		$sample   = $sections ? $sections[ (int) floor( count( $sections ) / 2 ) ]->dims : ( self::ICON_DIMS[ $f->shape ] ?? array() );
		// 0.3.0: the sources of the data stay in the administration; the public sees the standard only.
		$internal = current_user_can( 'manage_options' );
		$out      = array(
			'code'       => $f->code,
			'shape'      => $f->shape,
			'group'      => $f->group_code,
			'name'       => array( 'fr' => $f->name_fr, 'ar' => $f->name_ar ),
			'standard'   => $f->standard,
			'method'     => $f->method,
			'count'      => (int) $f->count,
			'source'     => $internal ? $f->source : '',
			'source_url' => $internal ? $f->source_url : '',
			'dims_keys'  => Domain\Geometry::SHAPES[ $f->shape ],
			'icon'       => $sample ? Drawing::svg( $f->shape, $sample, array( 'dims' => false ) ) : '',
		);
		if ( 'plate' === $f->shape ) {
			$out['plates']  = Plates::present();
			$out['formats'] = Plates::formats();
		}
		return $out;
	}

	public static function section( $s, $with_drawing = false ) {
		$out = array(
			'id'             => $s->id,
			'family'         => $s->family,
			'designation'    => $s->designation,
			'dims'           => $s->dims,
			'area_mm2'       => round( $s->area_mm2, 1 ),
			'mass_kg_m'      => $s->mass_kg_m,
			'mass_published' => $s->mass_published,
			'origin'         => $s->origin,
		);
		// 0.8.0: its public page in the catalogue (not for the plates).
		$fam_obj              = Catalogue::family( $s->family );
		$out['catalogue_url'] = $fam_obj && 'plate' !== $fam_obj->shape && 'active' === $s->status && CataloguePages::page_id() ? CataloguePages::url( CataloguePages::slug( $s->designation ) ) : '';
		if ( $with_drawing ) {
			$fam            = Catalogue::family( $s->family );
			$out['drawing'] = $fam ? Drawing::svg( $fam->shape, $s->dims, array( 'title' => $s->designation ) ) : '';
			$internal       = current_user_can( 'manage_options' );
			$out['source']  = $fam ? array( 'standard' => $fam->standard, 'text' => $internal ? $fam->source : '', 'url' => $internal ? $fam->source_url : '' ) : null;
		}
		return $out;
	}
}
