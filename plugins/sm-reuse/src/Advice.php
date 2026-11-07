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
	}

	public static function add( $out, $p ) {
		if ( Market::CODE !== ( $p->market ?? '' ) ) {
			return $out;
		}
		$out['advice'] = self::for_condition( (string) $p->item_condition );
		return $out;
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
