<?php
/**
 * Markets handled by the listing engine. sm-products defines "products"
 * (new steel); other plugins add theirs with the sm_products_markets filter
 * (sm-reuse adds "reuse").
 *
 * A market: label {fr, ar}, intro {fr, ar}, icon (theme icon name), cap (sm-core capability needed to publish),
 * conditions (code => label {fr, ar}, empty = no condition asked),
 * fields (extra fields asked for every category of the market: key, type
 * select|bool, label {fr, ar}, options [{value, fr, ar}], required).
 *
 * @package SM\Products
 */

namespace SM\Products;

defined( 'ABSPATH' ) || exit;

final class Markets {

	public static function all() {
		$markets = array(
			'products' => array(
				'label'      => array( 'fr' => 'Produits neufs', 'ar' => 'منتجات جديدة' ),
				'intro'      => array( 'fr' => 'Profilés, tubes, tôles et barres neufs, vendus au kilo, à la pièce ou au mètre.', 'ar' => 'مقاطع وأنابيب وصفائح وقضبان جديدة، تباع بالكيلو أو بالقطعة أو بالمتر.' ),
				'icon'       => 'layers',
				'cap'        => 'listing.products',
				'conditions' => array(),
				'fields'     => array(),
			),
		);
		return apply_filters( 'sm_products_markets', $markets );
	}

	public static function get( $code ) {
		$all = self::all();
		return $all[ (string) $code ] ?? null;
	}

	public static function exists( $code ) {
		return null !== self::get( $code );
	}
}
