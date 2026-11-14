<?php
/**
 * Engineer advice on reuse listings.
 *
 * The platform describes lots; it never says that a used element can carry a load.
 * Every reuse listing that may end up in a structure carries a fixed notice that
 * sends the buyer to a qualified engineer. Scrap gets a short recycling note instead.
 *
 * @package SM\Reuse
 */

namespace SM\Reuse;

defined( 'ABSPATH' ) || exit;

final class Advice {

	public static function init() {
		add_filter( 'sm_products_listing_detail', array( __CLASS__, 'add' ), 10, 2 );
		add_filter( 'sm_theme_data_mission_prefill', array( __CLASS__, 'mission_prefill' ), 20, 2 );
	}

	public static function add( $out, $p ) {
		if ( Market::CODE !== ( $p->market ?? '' ) ) {
			return $out;
		}
		$out['advice'] = self::for_condition( (string) $p->item_condition );
		// 0.2.0: an inspection request sent to the platform, which appoints the engineer (not for scrap).
		$out['inspection'] = null;
		if ( 'scrap' !== (string) $p->item_condition ) {
			$brand             = function_exists( 'sm_brand_name' ) ? sm_brand_name() : 'ReuSteel';
			$out['inspection'] = array(
				'level' => 'inspection' === (string) $p->item_condition ? 'warning' : 'info',
				'title' => __( 'Have this steel checked before you buy', 'sm-reuse' ),
				/* translators: %s: platform name. */
				'text'  => sprintf( __( 'An engineer verified on %s checks the condition, the corrosion and the likely grade of the reused elements. The information in this listing is declared by the seller.', 'sm-reuse' ), $brand ),
				'label' => __( 'Ask for an inspection', 'sm-reuse' ),
				'type'  => 'expertise',
			);
		}
		return $out;
	}

	/**
	 * Inspection request opened from a reuse listing: the lot is already filled in.
	 * The type of project is left to the buyer (the use of the steel is his).
	 */
	public static function mission_prefill( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$p = \SM\Products\Products::by_listing( (int) ( $args['listing'] ?? 0 ) );
		if ( ! $p || Market::CODE !== $p->market || 'published' !== $p->status || 'scrap' === $p->item_condition ) {
			return null;
		}
		$n     = static fn( $v, $d = 0 ) => number_format( (float) $v, $d, ',', ' ' );
		$title = \SM\Products\Presenter::title_text( $p );
		$url   = function_exists( 'sm_url' ) ? sm_url( 'listing', array( 'id' => (int) $p->listing_id ) ) : '';
		$place = trim( (string) ( $p->commune ?? '' ) . ( ! empty( $p->wilaya ) ? ', ' . \SM\Core\Accounts\Wilayas::name( $p->wilaya ) : '' ), ', ' );
		$rows  = array( array( __( 'Lot', 'sm-reuse' ), $title ) );
		if ( $p->total_weight > 0 ) {
			$rows[] = array( __( 'Weight', 'sm-reuse' ), $n( $p->total_weight ) . ' kg' );
		}
		$rows[] = array( __( 'Where it is', 'sm-reuse' ), $place );
		$rows[] = array( __( 'Listing', 'sm-reuse' ), $url );
		$text   = __( 'Inspection before buying reused steel listed on the platform.', 'sm-reuse' ) . "\n";
		foreach ( $rows as $r ) {
			$text .= $r[0] . ' : ' . $r[1] . "\n";
		}
		$text .= "\n" . __( 'What I expect from the inspection:', 'sm-reuse' ) . ' ';
		return array(
			'type'        => 'expertise',
			/* translators: %s: title of the listing. */
			'title'       => mb_substr( sprintf( __( 'Inspection: %s', 'sm-reuse' ), $title ), 0, 200 ),
			'description' => $text,
			'rows'        => $rows,
			'url'         => $url,
			'heading'     => __( 'Steel concerned by the inspection', 'sm-reuse' ),
			'icon'        => 'recycle',
		);
	}

	/** {level: info|warning, title, text, link_label, link_page} for a condition code. */
	public static function for_condition( $condition ) {
		if ( 'scrap' === $condition ) {
			return array(
				'level' => 'info',
				'title' => __( 'For recycling only', 'sm-reuse' ),
				'text'  => __( 'This lot is sold as scrap. It must not be used in a structure.', 'sm-reuse' ),
			);
		}
		$brand = function_exists( 'sm_brand_name' ) ? sm_brand_name() : 'ReuSteel';
		/* translators: %s: platform name. */
		$text = sprintf( __( 'Used or surplus steel can be reused in a structure only after a check by a qualified engineer: condition of the elements, steel grade, holes, welds and corrosion. The information in this listing is declared by the seller; %s does not certify it.', 'sm-reuse' ), $brand );
		if ( 'inspection' === $condition ) {
			/* translators: %s: platform name. */
			$text = sprintf( __( 'The seller says these elements need an inspection or a treatment. Have them checked by a qualified engineer before any structural use. The information in this listing is declared by the seller; %s does not certify it.', 'sm-reuse' ), $brand );
		}
		return array(
			'level'      => 'inspection' === $condition ? 'warning' : 'info',
			'title'      => __( 'Structural reuse: ask an engineer', 'sm-reuse' ),
			'text'       => $text,
			'link_label' => __( 'Find an engineer', 'sm-reuse' ),
			'link_page'  => 'engineers',
		);
	}
}
