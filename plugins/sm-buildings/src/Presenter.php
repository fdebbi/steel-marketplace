<?php
/**
 * Building keys of the listing shape (cards, product page) and of the page data:
 * dimensions, area, labels of the structural system, roof and cladding, what the
 * price includes, specification rows, indicative drawing, configurator shape and
 * the engineer advice for used buildings.
 *
 * @package SM\Buildings
 */

namespace SM\Buildings;

defined( 'ABSPATH' ) || exit;

final class Presenter {

	public static function init() {
		add_filter( 'sm_products_listing', array( __CLASS__, 'listing' ), 10, 2 );
		add_filter( 'sm_products_listing_detail', array( __CLASS__, 'detail' ), 10, 2 );
	}

	private static function is_mine( $p ) {
		return Market::CODE === ( $p->market ?? '' );
	}

	/** Label {fr, ar} of a select attribute. */
	private static function attr_label( $p, $key ) {
		$v = (string) ( $p->attrs[ $key ] ?? '' );
		foreach ( Market::definition()['fields'] as $f ) {
			if ( $f['key'] === $key ) {
				foreach ( (array) ( $f['options'] ?? array() ) as $o ) {
					if ( $o['value'] === $v ) {
						return array( 'fr' => $o['fr'], 'ar' => $o['ar'] );
					}
				}
			}
		}
		return array();
	}

	/** Shape data: drawing today, 3D configurator later. */
	public static function shape( $p ) {
		return array(
			'length'  => (float) ( $p->specs['length_m'] ?? 0 ),
			'width'   => (float) ( $p->specs['width_m'] ?? 0 ),
			'height'  => (float) ( $p->specs['height_m'] ?? 0 ),
			'portals' => (int) ( $p->specs['portals'] ?? 0 ),
			'roof'    => (string) ( $p->attrs['roof'] ?? '' ),
			'slope'   => (float) ( $p->specs['slope'] ?? 0 ),
		);
	}

	public static function listing( $out, $p ) {
		if ( ! self::is_mine( $p ) ) {
			return $out;
		}
		$shape = self::shape( $p );
		$out['length']     = $shape['length'];
		$out['width']      = $shape['width'];
		$out['height']     = $shape['height'];
		$out['portals']    = $shape['portals'];
		$out['area']       = (float) $p->quantity;
		$out['system']     = self::attr_label( $p, 'system' );
		$out['roof']       = self::attr_label( $p, 'roof' );
		$out['roof_cover'] = self::attr_label( $p, 'roof_cover' );
		$out['cladding']   = self::attr_label( $p, 'cladding' );
		$out['includes']   = array(
			'dismantling' => ! empty( $p->attrs['inc_dismantling'] ),
			'transport'   => ! empty( $p->attrs['inc_transport'] ),
			'reinstall'   => ! empty( $p->attrs['inc_reinstall'] ),
			'foundations' => ! empty( $p->attrs['inc_foundations'] ),
		);
		if ( 'lot' === $p->price_unit ) {
			$out['asking_price'] = (float) $p->price;
		}
		return $out;
	}

	public static function detail( $out, $p ) {
		if ( ! self::is_mine( $p ) ) {
			return $out;
		}
		$shape = self::shape( $p );
		$tech  = static fn( $s ) => '<bdi dir="ltr">' . esc_html( (string) $s ) . '</bdi>';
		$n     = static fn( $v, $d = 0 ) => number_format( (float) $v, $d, ',', "\u{202F}" );
		$rows  = array(
			array( __( 'Assembled or dismantled', 'sm-buildings' ), esc_html( 'assembled' === $p->variant ? __( 'Assembled', 'sm-buildings' ) : __( 'Dismantled', 'sm-buildings' ) ) ),
			array( __( 'Length × width × height at the column', 'sm-buildings' ), $tech( Spec::fmt( $shape['length'] ) . ' × ' . Spec::fmt( $shape['width'] ) . ' × ' . Spec::fmt( $shape['height'] ) . ' m' ) ),
			array( __( 'Total area', 'sm-buildings' ), $tech( $n( $p->quantity, fmod( (float) $p->quantity, 1 ) ? 1 : 0 ) . ' m²' ) ),
			array( __( 'Number of portal frames', 'sm-buildings' ), $tech( $shape['portals'] ) ),
		);
		if ( ! empty( $p->specs['ridge_m'] ) ) {
			$rows[] = array( __( 'Height of the roof (ridge)', 'sm-buildings' ), $tech( Spec::fmt( $p->specs['ridge_m'] ) . ' m' ) );
		}
		if ( $shape['slope'] > 0 ) {
			$rows[] = array( __( 'Roof slope', 'sm-buildings' ), $tech( Spec::fmt( $shape['slope'] ) . ' %' ) );
		}
		if ( ! empty( $p->specs['floors'] ) ) {
			$rows[] = array( __( 'Number of floors', 'sm-buildings' ), $tech( (int) $p->specs['floors'] ) );
		}
		if ( ! empty( $p->specs['mezzanine'] ) ) {
			$rows[] = array( __( 'Mezzanine', 'sm-buildings' ), empty( $p->specs['mezz_area'] ) ? esc_html__( 'Yes', 'sm-buildings' ) : $tech( $n( $p->specs['mezz_area'], fmod( (float) $p->specs['mezz_area'], 1 ) ? 1 : 0 ) . ' m²' ) );
		}
		if ( $p->total_weight > 0 ) {
			$labels = array(
				'estimated' => __( 'Total steel weight (given by the seller)', 'sm-buildings' ),
				'partial'   => __( 'Weight of the declared elements (not the whole structure)', 'sm-buildings' ),
			);
			$rows[] = array( $labels[ $p->weight_source ] ?? __( 'Steel weight', 'sm-buildings' ), $tech( $n( $p->total_weight ) . ' kg' ) );
			if ( 'estimated' === $p->weight_source && ! empty( $p->specs['elements_kg'] ) ) {
				$rows[] = array( __( 'Of which declared elements (calculated)', 'sm-buildings' ), $tech( $n( $p->specs['elements_kg'] ) . ' kg' ) );
			}
		}
		$out['elements_kg'] = (float) ( $p->specs['elements_kg'] ?? 0 );
		$out['spec_rows'] = array_merge( $rows, (array) ( $out['spec_rows'] ?? array() ) );
		$out['drawing']   = Drawing::svg( $shape );
		$out['drawing_note'] = __( 'Indicative drawing made from the figures given by the seller. It is not a structural design.', 'sm-buildings' );
		$out['configurator'] = $shape;
		if ( 'new' !== $p->item_condition ) {
			$brand          = function_exists( 'sm_brand_name' ) ? sm_brand_name() : 'ReuSteel';
			$out['advice']  = array(
				'level'      => 'inspection' === $p->item_condition ? 'warning' : 'info',
				'title'      => __( 'Before buying and reassembling: ask an engineer', 'sm-buildings' ),
				/* translators: %s: platform name. */
				'text'       => sprintf( __( 'A used steel building must be checked by a qualified engineer before it is reassembled: condition of the members and connections, corrosion, and a new calculation for the new site (wind, snow, foundations). The information in this listing is declared by the seller; %s does not certify it.', 'sm-buildings' ), $brand ),
				'link_label' => __( 'Find an engineer', 'sm-buildings' ),
				'link_page'  => 'engineers',
			);
		}
		return $out;
	}
}
