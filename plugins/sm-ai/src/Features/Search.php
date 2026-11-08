<?php
/**
 * AI smart search: a sentence of a buyer turned into the filters of the
 * marketplace search (market, category, designation, wilaya, condition…).
 * Only known values are kept; the normal search then runs.
 *
 * @package SM\Ai
 */

namespace SM\Ai\Features;

use SM\Ai\Catalog;
use SM\Core\Accounts\Wilayas;

defined( 'ABSPATH' ) || exit;

final class Search extends Feature {

	public static function id() {
		return 'search';
	}

	public static function label() {
		return __( 'Smart search', 'sm-ai' );
	}

	public static function public_feature() {
		return true;
	}

	public static function allowed( $company ) {
		return Catalog::has_products() ? true : new \WP_Error( 'sm_ai_products', __( 'The marketplace is not active.', 'sm-ai' ) );
	}

	public static function system( $lang ) {
		return "You turn the request of a buyer (French, Arabic or Algerian Darija) into search filters of the marketplace.\n"
			. self::common_rules( $lang )
			. "- q: the profile designation (e.g. \"IPE 200\") or 1 to 3 key words, else empty.\n"
			. "- wilaya: the Algerian province named in the request (name or number), else empty.\n"
			. "- market: \"products\" for new steel, \"reuse\" for used/second-hand/offcuts/scrap, \"buildings\" for hangars and warehouses; empty if not clear.\n"
			. "- mode (buildings only): \"assembled\" or \"dismantled\". length_min in metres, qty_min in pieces, price_max in DA per kg: only if stated.\n"
			. "JSON keys: market, category, q, wilaya, condition, mode, length_min, qty_min, price_max, explanation (one short sentence describing the search).\n\n"
			. Catalog::context( array( 'categories', 'families' ) );
	}

	public static function parts( array $input ) {
		return array( array( 'type' => 'text', 'text' => 'Request: ' . (string) ( $input['q'] ?? '' ) ) );
	}

	public static function clean( array $j, array $input ) {
		$markets = Catalog::markets();
		$market  = isset( $markets[ $j['market'] ?? '' ] ) ? $j['market'] : '';
		$cats    = Catalog::categories();
		$cat     = (string) ( $j['category'] ?? '' );
		if ( isset( $cats[ $cat ] ) ) {
			$market = $cats[ $cat ]['market'];
		} else {
			$cat = '';
		}
		$cond = (string) ( $j['condition'] ?? '' );
		$cond = $market && in_array( $cond, $markets[ $market ]['conditions'], true ) ? $cond : '';
		$mode = in_array( $j['mode'] ?? '', array( 'assembled', 'dismantled' ), true ) && 'buildings' === $market ? $j['mode'] : '';
		$q    = self::str( $j['q'] ?? '', 80 );
		$hit  = Catalog::detect( $q );
		if ( $hit ) {
			$q = Catalog::label( $hit[1] );
		}
		$num = static fn( $v, $max ) => ( (float) $v > 0 && (float) $v <= $max ) ? (string) ( 0 + round( (float) $v, 1 ) ) : '';
		$f   = array_filter(
			array(
				'market'     => $market,
				'category'   => $cat,
				'q'          => $q,
				'wilaya'     => Catalog::wilaya( $j['wilaya'] ?? '' ),
				'condition'  => $cond,
				'mode'       => $mode,
				'length_min' => $num( $j['length_min'] ?? 0, 30 ),
				'qty_min'    => $num( $j['qty_min'] ?? 0, 100000 ),
				'price_max'  => $num( $j['price_max'] ?? 0, 100000 ),
			),
			static fn( $v ) => '' !== $v
		);
		return array(
			'filters'     => $f,
			'explanation' => self::str( $j['explanation'] ?? '', 200 ),
			'query'       => self::str( $input['q'] ?? '', 300 ),
		);
	}

	/** Labels of the kept filters, for the "understood as" chips. */
	public static function chips( array $f ) {
		$out  = array();
		$lang = sm_current_language();
		if ( ! empty( $f['market'] ) ) {
			$m     = class_exists( '\SM\Products\Markets' ) ? \SM\Products\Markets::get( $f['market'] ) : null;
			$out[] = $m ? (string) ( $m['label'][ $lang ] ?? $m['label']['fr'] ) : $f['market'];
		}
		if ( ! empty( $f['category'] ) ) {
			$c     = Catalog::categories()[ $f['category'] ] ?? null;
			$out[] = $c ? ( 'ar' === $lang ? $c['name_ar'] : $c['name_fr'] ) : $f['category'];
		}
		if ( ! empty( $f['q'] ) ) {
			$out[] = '« ' . $f['q'] . ' »';
		}
		if ( ! empty( $f['wilaya'] ) ) {
			$out[] = $f['wilaya'] . ' ' . Wilayas::name( $f['wilaya'] );
		}
		if ( ! empty( $f['length_min'] ) ) {
			/* translators: %s: metres. */
			$out[] = sprintf( __( '≥ %s m', 'sm-ai' ), $f['length_min'] );
		}
		if ( ! empty( $f['qty_min'] ) ) {
			/* translators: %s: pieces. */
			$out[] = sprintf( __( '≥ %s pieces', 'sm-ai' ), $f['qty_min'] );
		}
		if ( ! empty( $f['price_max'] ) ) {
			/* translators: %s: price. */
			$out[] = sprintf( __( '≤ %s DA/kg', 'sm-ai' ), $f['price_max'] );
		}
		if ( ! empty( $f['mode'] ) ) {
			$out[] = 'assembled' === $f['mode'] ? __( 'Assembled', 'sm-ai' ) : __( 'Dismantled', 'sm-ai' );
		}
		return $out;
	}

	/** Rule-based answer for the simulation: enough to show the flow. */
	public static function simulate( array $input ) {
		$q   = (string) ( $input['q'] ?? '' );
		$low = mb_strtolower( $q );
		$hit = Catalog::detect( $q );
		$w   = '';
		foreach ( Wilayas::all() as $code => $n ) {
			foreach ( $n as $name ) {
				if ( '' !== $name && false !== mb_stripos( $q, $name ) ) {
					$w = (string) $code;
					break 2;
				}
			}
		}
		$market = preg_match( '/occasion|réemploi|reemploi|démont|demont|chute|ferraille|مستعمل|خردة/u', $low ) ? 'reuse' : ( preg_match( '/hangar|bâtiment|batiment|entrepôt|مستودع|هنغار/u', $low ) ? 'buildings' : ( preg_match( '/neuf|neuve|جديد/u', $low ) ? 'products' : '' ) );
		$len    = preg_match( '/(\d+(?:[.,]\d+)?)\s*m\b/u', $low, $m ) ? str_replace( ',', '.', $m[1] ) : '';
		$price  = preg_match( '/(\d+)\s*da/u', $low, $m2 ) ? $m2[1] : '';
		return wp_json_encode(
			array(
				'market'      => $market,
				'category'    => '',
				'q'           => $hit ? Catalog::label( $hit[1] ) : '',
				'wilaya'      => $w,
				'length_min'  => $len,
				'price_max'   => $price,
				'explanation' => 'Recherche de ' . ( $hit ? Catalog::label( $hit[1] ) : 'produits' ) . ( $w ? ' en wilaya ' . $w : '' ) . '.',
			)
		);
	}
}
