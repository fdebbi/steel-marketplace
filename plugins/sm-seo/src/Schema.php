<?php
/**
 * Structured data (schema.org, JSON-LD). Only facts of the site: the price is the
 * one asked by the seller, never a market price; no review, no rating, no contact,
 * no exact address; the seller is named only when its page is public.
 *
 * @package SM\Seo
 */

namespace SM\Seo;

defined( 'ABSPATH' ) || exit;

final class Schema {

	public static function organization() {
		$org  = array(
			'@type' => 'Organization',
			'@id'   => home_url( '/#organization' ),
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		);
		$logo = (int) get_theme_mod( 'custom_logo' );
		$img  = $logo ? Text::image( $logo, 'full' ) : (string) get_site_icon_url( 512 );
		if ( $img ) {
			$org['logo'] = $img;
		}
		$same = array_values( array_filter( array_map( 'esc_url_raw', (array) apply_filters( 'sm_seo_same_as', array() ) ) ) );
		if ( $same ) {
			$org['sameAs'] = $same;
		}
		return $org;
	}

	public static function breadcrumb( array $crumbs ) {
		$items = array( array( '@type' => 'ListItem', 'position' => 1, 'name' => __( 'Home', 'sm-seo' ), 'item' => home_url( '/' ) ) );
		foreach ( array_values( $crumbs ) as $i => $c ) {
			$items[] = array( '@type' => 'ListItem', 'position' => $i + 2, 'name' => (string) $c[0], 'item' => (string) $c[1] );
		}
		return array( '@type' => 'BreadcrumbList', 'itemListElement' => $items );
	}

	/** Condition of the offer: new steel, or the condition stated by the seller. */
	public static function condition( array $item ) {
		$market = (string) ( $item['market'] ?? '' );
		$code   = (string) ( $item['condition'] ?? '' );
		if ( 'products' === $market || in_array( $code, array( 'new', 'new_surplus' ), true ) ) {
			return 'https://schema.org/NewCondition';
		}
		if ( in_array( $code, array( 'excellent', 'good', 'inspection', 'scrap' ), true ) ) {
			return 'https://schema.org/UsedCondition';
		}
		return '';
	}

	public static function product( array $item, $name, array $m, array $price ) {
		$p = array(
			'@type'       => 'Product',
			'name'        => $name,
			'url'         => $m['canonical'],
			'description' => $m['description'],
		);
		$cat = Text::pick( $item['category_label'] ?? '' );
		if ( '' !== $cat ) {
			$p['category'] = $cat;
		}
		if ( ! empty( $m['images'] ) ) {
			$p['image'] = array_values( $m['images'] );
		}
		if ( (float) ( $item['weight_kg'] ?? 0 ) > 0 ) {
			$p['weight'] = array( '@type' => 'QuantitativeValue', 'value' => round( (float) $item['weight_kg'] ), 'unitCode' => 'KGM' );
		}
		$props = array();
		if ( ! empty( $item['grade'] ) ) {
			$props[] = array( '@type' => 'PropertyValue', 'name' => __( 'Steel grade', 'sm-seo' ), 'value' => (string) $item['grade'] );
		}
		if ( 'buildings' !== ( $item['market'] ?? '' ) && (float) ( $item['length_m'] ?? 0 ) > 0 ) {
			$props[] = array( '@type' => 'PropertyValue', 'name' => __( 'Length', 'sm-seo' ), 'value' => (float) $item['length_m'], 'unitCode' => 'MTR' );
		}
		if ( 'bars' === ( $item['qty_mode'] ?? '' ) && (int) ( $item['quantity'] ?? 0 ) > 0 ) {
			$props[] = array( '@type' => 'PropertyValue', 'name' => __( 'Number of bars', 'sm-seo' ), 'value' => (int) $item['quantity'] );
		}
		if ( (float) ( $item['area'] ?? 0 ) > 0 ) {
			$props[] = array( '@type' => 'PropertyValue', 'name' => __( 'Area', 'sm-seo' ), 'value' => (float) $item['area'], 'unitCode' => 'MTK' );
		}
		if ( $props ) {
			$p['additionalProperty'] = $props;
		}
		if ( $price['price'] > 0 ) {
			$offer = array(
				'@type'         => 'Offer',
				'url'           => $m['canonical'],
				'price'         => round( (float) $price['price'], 2 ),
				'priceCurrency' => 'DZD',
				'availability'  => $m['index'] ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
			);
			if ( $price['per_kg'] ) {
				$offer['priceSpecification'] = array(
					'@type'             => 'UnitPriceSpecification',
					'price'             => round( (float) $price['price'], 2 ),
					'priceCurrency'     => 'DZD',
					'unitCode'          => 'KGM',
					'referenceQuantity' => array( '@type' => 'QuantitativeValue', 'value' => 1, 'unitCode' => 'KGM' ),
				);
			}
			$cond = self::condition( $item );
			if ( $cond ) {
				$offer['itemCondition'] = $cond;
			}
			if ( ! empty( $item['expires_at'] ) ) {
				$offer['priceValidUntil'] = substr( (string) $item['expires_at'], 0, 10 );
			}
			$wilaya = Text::wilaya( $item['wilaya'] ?? '' );
			if ( '' !== $wilaya ) {
				$offer['areaServed'] = array( '@type' => 'AdministrativeArea', 'name' => $wilaya . ', ' . __( 'Algeria', 'sm-seo' ) );
			}
			if ( ! empty( $item['seller_public'] ) && ! empty( $item['company'] ) ) {
				$c = Text::data( 'company', array( 'slug' => $item['company'] ) );
				if ( is_array( $c ) && ! empty( $c['name'] ) ) {
					$offer['seller'] = array( '@type' => 'Organization', 'name' => Text::pick( $c['name'] ), 'url' => Text::url( 'company', array( 'c' => $item['company'] ) ) );
				}
			}
			$p['offers'] = $offer;
		}
		return $p;
	}

	public static function company( array $c, $name, $about, $wilaya, array $m ) {
		$o = array(
			'@type' => 'Organization',
			'name'  => $name,
			'url'   => $m['canonical'],
		);
		if ( '' !== $about ) {
			$o['description'] = Text::cut( $about, 500 );
		}
		if ( $m['image'] ) {
			$o['logo'] = $m['image'];
		}
		$addr = array( '@type' => 'PostalAddress', 'addressCountry' => 'DZ' );
		if ( '' !== $wilaya ) {
			$addr['addressRegion'] = $wilaya;
		}
		$o['address'] = $addr; // The wilaya only, never the street address.
		return $o;
	}
}
