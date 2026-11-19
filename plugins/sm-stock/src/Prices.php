<?php
/**
 * One price per kg, or a change in %, for a whole type of new steel (for
 * example 135 DA/kg for every IPE, or + 5 % on IPE 200 to 300 in S275JR).
 * A one-off action: each product keeps its own price afterwards. Orders and
 * offers already in progress keep theirs (they store it).
 *
 * @package SM\Stock
 */

namespace SM\Stock;

use SM\Products\Presenter;
use SM\Products\Products;

defined( 'ABSPATH' ) || exit;

final class Prices {

	public static function init() {
		add_filter( 'sm_notification_types', array( __CLASS__, 'types' ) );
	}

	public static function types( $types ) {
		$types['stock.prices'] = array(
			'email'   => true,
			'subject' => static fn( $p ) => __( 'Prices changed by a seller', 'sm-stock' ),
			/* translators: 1: company, 2: number of listings, 3: what was changed. */
			'body'    => static fn( $p ) => sprintf( __( '%1$s changed the price of %2$d published listings at once (%3$s). The new prices are already online.', 'sm-stock' ), $p['company'] ?? '', (int) ( $p['count'] ?? 0 ), $p['what'] ?? '' ),
			'link'    => static fn( $p ) => admin_url( 'admin.php?page=sm-products&status=published' ),
		);
		return $types;
	}

	/** Selection from a form: family, grade, sizes from / to (section ids), mode price|percent, value. */
	public static function read( array $raw ) {
		$mode  = 'percent' === ( $raw['mode'] ?? '' ) ? 'percent' : 'price';
		$value = (float) str_replace( array( ',', ' ' ), array( '.', '' ), (string) ( $raw['value'] ?? '' ) );
		return array(
			'family' => strtoupper( sanitize_key( (string) ( $raw['family'] ?? '' ) ) ),
			'grade'  => sanitize_text_field( (string) ( $raw['grade'] ?? '' ) ),
			'from'   => absint( $raw['from'] ?? 0 ),
			'to'     => absint( $raw['to'] ?? 0 ),
			'mode'   => $mode,
			'value'  => 'percent' === $mode ? max( -90, min( 200, $value ) ) : max( 0, $value ),
		);
	}

	/** Order of the sections of a family in the catalogue: id => position. */
	private static function order( $family ) {
		$out = array();
		if ( class_exists( '\SM\WeightEngine\Catalogue' ) ) {
			foreach ( \SM\WeightEngine\Catalogue::sections( $family ) as $i => $s ) {
				$out[ (int) $s->id ] = $i;
			}
		}
		return $out;
	}

	/**
	 * New steel of the company concerned by a selection, with the old and the new
	 * price per kg. Only products priced per kg (or not priced yet, for a price).
	 *
	 * @return array[] { id, title, designation, grade, status, old, new }
	 */
	public static function preview( $company_id, array $sel ) {
		if ( '' === $sel['family'] || ( 'price' === $sel['mode'] && $sel['value'] <= 0 ) || ( 'percent' === $sel['mode'] && 0.0 === $sel['value'] ) ) {
			return array();
		}
		$order = self::order( $sel['family'] );
		$from  = $sel['from'] && isset( $order[ $sel['from'] ] ) ? $order[ $sel['from'] ] : null;
		$to    = $sel['to'] && isset( $order[ $sel['to'] ] ) ? $order[ $sel['to'] ] : null;
		$out   = array();
		foreach ( Items::products( $company_id, 'products' ) as $p ) {
			if ( $p->family !== $sel['family'] || ( $sel['grade'] && 0 !== strcasecmp( $p->grade, $sel['grade'] ) ) ) {
				continue;
			}
			$pos = $order[ (int) $p->section_id ] ?? null;
			if ( ( null !== $from && ( null === $pos || $pos < $from ) ) || ( null !== $to && ( null === $pos || $pos > $to ) ) ) {
				continue;
			}
			$priced = 'kg' === $p->price_unit && $p->price > 0;
			if ( ! $priced && ( 'percent' === $sel['mode'] || ( $p->price > 0 && 'kg' !== $p->price_unit ) ) ) {
				continue; // A change in % needs a price per kg; a price per piece or per metre is left alone.
			}
			$old   = $priced ? (float) $p->price : 0.0;
			$new   = 'percent' === $sel['mode'] ? round( $old * ( 1 + $sel['value'] / 100 ), 2 ) : round( $sel['value'], 2 );
			$out[] = array( 'id' => (int) $p->id, 'title' => Presenter::title_text( $p ), 'designation' => $p->designation, 'grade' => $p->grade, 'status' => $p->status, 'old' => $old, 'new' => $new, 'kg' => (float) $p->total_weight );
		}
		return $out;
	}

	/** Change the prices (rows from preview()). Returns the number of products changed. */
	public static function apply( $company_id, array $rows ) {
		global $wpdb;
		$n         = 0;
		$published = 0;
		foreach ( $rows as $r ) {
			$p = Products::get( (int) $r['id'] );
			if ( ! $p || (int) $p->company_id !== (int) $company_id || $r['new'] <= 0 ) {
				continue;
			}
			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				Products::table(),
				array(
					'price'        => $r['new'],
					'price_unit'   => 'kg',
					'price_per_kg' => $r['new'],
					'total_price'  => round( $r['new'] * (float) $p->total_weight, 2 ),
					'updated_at'   => current_time( 'mysql', true ),
				),
				array( 'id' => (int) $p->id )
			);
			Products::index( $p->id );
			/* translators: 1: old price, 2: new price. */
			Moves::add( (int) $company_id, (int) $p->id, 'price', Presenter::title_text( $p ), 0, '', 0, sprintf( __( '%1$s → %2$s DA/kg', 'sm-stock' ), $r['old'] > 0 ? number_format( $r['old'], 2, ',', ' ' ) : '—', number_format( $r['new'], 2, ',', ' ' ) ) );
			++$n;
			if ( 'published' === $p->status ) {
				++$published;
			}
		}
		if ( class_exists( '\SM\Products\PriceTrend' ) ) {
			\SM\Products\PriceTrend::flush();
		}
		// One e-mail to the platform team for the whole change (not one per listing).
		if ( $published && ! current_user_can( 'sm_moderate' ) ) {
			$c = \SM\Core\Accounts\Companies::get( (int) $company_id );
			\SM\Core\Notifications\Notifications::notify_moderators( 'stock.prices', array( 'company_id' => (int) $company_id, 'company' => $c ? $c->name : '', 'count' => $published, 'what' => implode( ', ', array_unique( array_map( static fn( $r ) => $r['designation'], $rows ) ) ) ) );
		}
		return $n;
	}
}
