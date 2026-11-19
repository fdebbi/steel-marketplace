<?php
/**
 * Who has a warehouse and how much it holds. Companies and professionals have
 * one. Without sm-subscriptions there is no limit; with it, each plan gives a
 * capacity in tonnes (everything in the warehouse, published or not) and the
 * advanced tools (price for a whole type, labels) or not:
 *   sm_stock_capacity_kg ( null|float $kg, object $company )   null = no limit;
 *   sm_stock_tools       ( bool $allowed, object $company ).
 * Over the capacity, nothing is deleted or hidden: only new items kept in stock
 * only are refused.
 *
 * @package SM\Stock
 */

namespace SM\Stock;

defined( 'ABSPATH' ) || exit;

final class Limits {

	const CLASSES = array( 'company', 'professional' );

	public static function init() {
		add_filter( 'sm_products_stock_limit', array( __CLASS__, 'check' ), 10, 4 );
	}

	public static function has_warehouse( $company ) {
		return $company && in_array( $company->account_class, self::CLASSES, true );
	}

	/** Capacity in kg, or null without limit. */
	public static function capacity( $company ) {
		$kg = apply_filters( 'sm_stock_capacity_kg', null, $company );
		return null === $kg ? null : max( 0.0, (float) $kg );
	}

	public static function tools( $company ) {
		return (bool) apply_filters( 'sm_stock_tools', true, $company );
	}

	public static function tools_reason() {
		return __( 'The price for a whole type and the labels come with the paid plans. Choose a plan in your account (Plan).', 'sm-stock' );
	}

	/** Keep an item in stock only: '' when there is room. */
	public static function check( $reason, $company, $weight, $product = null ) {
		if ( $reason || ! $company ) {
			return $reason;
		}
		if ( ! self::has_warehouse( $company ) ) {
			return __( 'Only company and professional accounts have a warehouse.', 'sm-stock' );
		}
		$max = self::capacity( $company );
		if ( null === $max ) {
			return '';
		}
		$used = Items::weight( (int) $company->id, $product ? (int) $product->id : 0 );
		if ( $used + (float) $weight <= $max + 0.001 ) {
			return '';
		}
		/* translators: 1: capacity in tonnes, 2: used in tonnes. */
		return sprintf( __( 'Your warehouse is full for your plan: %1$s t (%2$s t used). Nothing is deleted; choose a higher plan in your account (Plan) to add more.', 'sm-stock' ), number_format( $max / 1000, 1, ',', ' ' ), number_format( $used / 1000, 1, ',', ' ' ) );
	}
}
