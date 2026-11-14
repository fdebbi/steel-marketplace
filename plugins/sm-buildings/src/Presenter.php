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
		add_filter( 'sm_theme_data_mission_prefill', array( __CLASS__, 'mission_prefill' ), 10, 2 );
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
		// 0.4.0: the whole building (sale price) and / or by element (price per kg).
		$lot = in_array( $p->sale_mode, array( 'lot', 'both' ), true ) ? ( (float) $p->lot_price > 0 ? (float) $p->lot_price : ( 'lot' === $p->price_unit ? (float) $p->price : 0.0 ) ) : ( 'lot' === $p->price_unit ? (float) $p->price : 0.0 );
		$out['building_sale'] = array(
			'lot_total'  => $lot,
			'by_element' => in_array( $p->sale_mode, array( 'both', 'detail' ), true ) && 'kg' === $p->price_unit && ! empty( $p->lines ),
			'per_kg'     => 'kg' === $p->price_unit ? (float) $p->price : ( $lot > 0 && $p->total_weight > 0 ? round( $lot / $p->total_weight, 2 ) : 0.0 ),
		);
		return $out;
	}

	public static function detail( $out, $p ) {
		if ( ! self::is_mine( $p ) ) {
			return $out;
		}
		$shape = self::shape( $p );
		$tech  = static fn( $s ) => '<bdi dir="ltr">' . esc_html( (string) $s ) . '</bdi>';
		$n     = static fn( $v, $d = 0 ) => number_format( (float) $v, $d, ',', "\u{202F}" );
		$area  = $n( $p->quantity, fmod( (float) $p->quantity, 1 ) ? 1 : 0 ) . ' m²';
		$rows  = array(
			array( __( 'Assembled or dismantled', 'sm-buildings' ), esc_html( 'assembled' === $p->variant ? __( 'Assembled', 'sm-buildings' ) : __( 'Dismantled', 'sm-buildings' ) ) ),
		);
		// 0.4.0: the geometry is shown as key figures above the drawing; the other rows open on demand.
		$figures = array(
			array( $tech( Spec::fmt( $shape['length'] ) . ' × ' . Spec::fmt( $shape['width'] ) . ' m' ), __( 'Length × width', 'sm-buildings' ) ),
			array( $tech( $area ), __( 'Floor area', 'sm-buildings' ) ),
			array( $tech( Spec::fmt( $shape['height'] ) . ' m' ), __( 'Height at the column', 'sm-buildings' ) . ( ! empty( $p->specs['ridge_m'] ) ? ' · ' . sprintf( /* translators: %s: height in metres. */ __( 'ridge %s m', 'sm-buildings' ), Spec::fmt( $p->specs['ridge_m'] ) ) : '' ) ),
			array( $tech( $shape['portals'] ), __( 'Portal frames', 'sm-buildings' ) . ( $shape['slope'] > 0 ? ' · ' . sprintf( /* translators: %s: slope in percent. */ __( 'slope %s %%', 'sm-buildings' ), Spec::fmt( $shape['slope'] ) ) : '' ) ),
		);
		if ( ! empty( $p->specs['floors'] ) ) {
			$rows[] = array( __( 'Number of floors', 'sm-buildings' ), $tech( (int) $p->specs['floors'] ) );
		}
		if ( ! empty( $p->specs['mezzanine'] ) ) {
			$rows[] = array( __( 'Mezzanine', 'sm-buildings' ), empty( $p->specs['mezz_area'] ) ? esc_html__( 'Yes', 'sm-buildings' ) : $tech( $n( $p->specs['mezz_area'], fmod( (float) $p->specs['mezz_area'], 1 ) ? 1 : 0 ) . ' m²' ) );
		}
		if ( ! empty( $p->specs['crane'] ) ) {
			$rows[] = array( __( 'Overhead crane', 'sm-buildings' ), empty( $p->specs['crane_t'] ) ? esc_html__( 'Yes', 'sm-buildings' ) : $tech( Spec::fmt( $p->specs['crane_t'] ) . ' t' ) );
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
		$pieces = array_sum( array_map( static fn( $l ) => (int) ( $l['qty'] ?? 0 ), (array) $p->lines ) );
		$out['building'] = array(
			'figures'    => $figures,
			'system'     => self::attr_label( $p, 'system' ),
			'condition'  => (array) ( Market::conditions()[ (string) $p->item_condition ] ?? array() ),
			'pieces'     => $pieces,
			'kinds'      => count( (array) $p->lines ),
			'inspection' => null,
		);
		if ( 'new' !== $p->item_condition ) {
			$brand = function_exists( 'sm_brand_name' ) ? sm_brand_name() : 'ReuSteel';
			// 0.4.0: the request goes to the platform, which appoints the engineer.
			$out['building']['inspection'] = array(
				'level' => 'inspection' === $p->item_condition ? 'warning' : 'info',
				'title' => __( 'Have this building inspected before you buy', 'sm-buildings' ),
				/* translators: %s: platform name. */
				'text'  => sprintf( __( 'An engineer verified on %s checks the condition of the members, the connections and the corrosion, and tells you whether the building can be reassembled on your site. The information in this listing is declared by the seller.', 'sm-buildings' ), $brand ),
				'label' => __( 'Ask for an inspection', 'sm-buildings' ),
				'type'  => 'expertise',
			);
		}
		return $out;
	}

	/**
	 * Inspection request opened from a building listing: the building is already
	 * filled in (title, description, summary). Args: listing (listing id).
	 */
	public static function mission_prefill( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$p = \SM\Products\Products::by_listing( (int) ( $args['listing'] ?? 0 ) );
		if ( ! $p || ! self::is_mine( $p ) || 'published' !== $p->status ) {
			return null;
		}
		$shape = self::shape( $p );
		$n     = static fn( $v, $d = 0 ) => number_format( (float) $v, $d, ',', ' ' );
		$title = \SM\Products\Presenter::title_text( $p );
		$url   = function_exists( 'sm_url' ) ? sm_url( 'listing', array( 'id' => (int) $p->listing_id ) ) : '';
		$dims  = Spec::fmt( $shape['length'] ) . ' × ' . Spec::fmt( $shape['width'] ) . ' × ' . Spec::fmt( $shape['height'] ) . ' m · ' . $n( $p->quantity, fmod( (float) $p->quantity, 1 ) ? 1 : 0 ) . ' m²';
		if ( $shape['portals'] > 0 ) {
			/* translators: %s: number of portal frames. */
			$dims .= ' · ' . sprintf( __( '%s portal frames', 'sm-buildings' ), $shape['portals'] );
		}
		$place = trim( (string) ( $p->commune ?? '' ) . ( ! empty( $p->wilaya ) ? ', ' . \SM\Core\Accounts\Wilayas::name( $p->wilaya ) : '' ), ', ' );
		$rows  = array(
			array( __( 'Building', 'sm-buildings' ), $title ),
			array( __( 'Dimensions', 'sm-buildings' ), $dims ),
		);
		if ( $p->total_weight > 0 ) {
			$rows[] = array( 'partial' === $p->weight_source ? __( 'Weight of the declared elements', 'sm-buildings' ) : __( 'Steel weight', 'sm-buildings' ), $n( $p->total_weight ) . ' kg' );
		}
		$rows[] = array( __( 'Where it is', 'sm-buildings' ), $place );
		$rows[] = array( __( 'Listing', 'sm-buildings' ), $url );
		$text   = __( 'Inspection before buying a building listed on the platform.', 'sm-buildings' ) . "\n";
		foreach ( $rows as $r ) {
			$text .= $r[0] . ' : ' . $r[1] . "\n";
		}
		$text .= "\n" . __( 'What I expect from the inspection:', 'sm-buildings' ) . ' ';
		return array(
			'type'        => 'expertise',
			/* translators: %s: title of the listing. */
			'title'       => mb_substr( sprintf( __( 'Inspection: %s', 'sm-buildings' ), $title ), 0, 200 ),
			'description' => $text,
			'rows'        => $rows,
			'url'         => $url,
		);
	}
}
