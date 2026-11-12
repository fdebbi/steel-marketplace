<?php
/**
 * Theme data contract:
 *   plans          public plans of companies and professionals, credit packs, options, founders offer
 *   subscription   the current account: plan, use of each limit, orders, payment details, credits
 *   invoice        one paid order as an invoice (owner of the account or the platform team)
 *
 * @package SM\Subscriptions
 */

namespace SM\Subscriptions\Theme;

use SM\Core\Accounts\Companies;
use SM\Core\Routing\Routes as CoreRoutes;
use SM\Subscriptions\Compat;
use SM\Subscriptions\Credits;
use SM\Subscriptions\Founders;
use SM\Subscriptions\Form;
use SM\Subscriptions\Orders;
use SM\Subscriptions\Plans;
use SM\Subscriptions\Proofs;
use SM\Subscriptions\Settings;
use SM\Subscriptions\Subscriptions;

defined( 'ABSPATH' ) || exit;

final class Bridge {

	public static function init() {
		foreach ( array( 'plans', 'subscription', 'invoice' ) as $r ) {
			add_filter( 'sm_theme_data_' . $r, array( __CLASS__, $r ), 10, 2 );
		}
		add_filter( 'sm_theme_data_account', array( __CLASS__, 'account' ), 20 );
	}

	/** Plans of a class, shaped for the theme. */
	private static function shape_plans( $class, $current = '' ) {
		$out = array();
		foreach ( Plans::for_class( $class ) as $code => $p ) {
			$out[] = array(
				'code'        => $code,
				'name'        => Plans::name( $code, $class ),
				'price_month' => (float) $p['price_month'],
				'price_year'  => (float) $p['price_year'],
				'free'        => $p['price_month'] <= 0 && $p['price_year'] <= 0,
				'limits'      => array_intersect_key( $p, array_flip( Plans::limit_keys() ) ),
				'current'     => $code === $current,
			);
		}
		return $out;
	}

	private static function founders() {
		$s = Settings::all();
		return array(
			'on'          => (bool) $s['founders_on'] && Founders::places_left() > 0,
			'places_left' => Founders::places_left(),
			'months'      => (int) $s['founders_months'],
			'plans'       => array_map( static fn( $class ) => Plans::name( $s['founders_plan'][ $class ] ?? '', $class ), array_combine( Plans::classes(), Plans::classes() ) ),
		);
	}

	private static function options() {
		$s = Settings::all();
		return array(
			'bump'     => array( 'price' => (float) $s['opt_bump'], 'days' => 0 ),
			'urgent'   => array( 'price' => (float) $s['opt_urgent'], 'days' => (int) $s['opt_urgent_days'] ),
			'featured' => array( 'price' => (float) $s['opt_featured'], 'days' => (int) $s['opt_featured_days'] ),
		);
	}

	public static function plans( $result ) {
		$viewer  = null;
		$current = array( 'company' => '', 'professional' => '' );
		$user_id = get_current_user_id();
		$company = $user_id ? Companies::for_user( $user_id ) : null;
		if ( $company ) {
			$plan   = Subscriptions::plan_of( $company );
			$viewer = array(
				'class'     => (string) $company->account_class,
				'has_plans' => (bool) $plan['has_plans'],
				'plan'      => (string) $plan['code'],
				'plan_name' => (string) $plan['name'],
			);
			if ( $plan['has_plans'] && isset( $current[ $company->account_class ] ) ) {
				$current[ $company->account_class ] = (string) $plan['code'];
			}
		}
		return array(
			'viewer'   => $viewer,
			'classes'  => array(
				'company'      => self::shape_plans( 'company', $current['company'] ),
				'professional' => self::shape_plans( 'professional', $current['professional'] ),
			),
			'labels'   => Plans::limit_labels(),
			'packs'    => array_values( (array) Settings::get( 'packs' ) ),
			'options'  => self::options(),
			'founders' => self::founders(),
			'methods'  => Settings::methods(),
		);
	}

	/** What the company uses of each limit. */
	public static function usage( $company ) {
		$online = Compat::ready( 'products' ) ? \SM\Products\Products::online_totals( (int) $company->id ) : array( 'listings' => 0, 'weight' => 0, 'buildings' => 0 );
		return array(
			'listings'   => $online['listings'],
			'weight_t'   => round( $online['weight'] / 1000, 2 ),
			'buildings'  => $online['buildings'],
			'services'   => Compat::ready( 'services' ) ? \SM\Services\Services::count_for_company( (int) $company->id ) : 0,
			'portfolio'  => Compat::ready( 'services' ) ? \SM\Services\Portfolio::count( (int) $company->id ) : 0,
			'rfq_offers' => Compat::ready( 'rfq' ) ? \SM\Rfq\Offers::count_month( (int) $company->id ) : 0,
		);
	}

	private static function shape_order( $o ) {
		$labels = Orders::status_labels();
		return array(
			'id'         => (int) $o->id,
			'ref'        => $o->ref,
			'kind'       => $o->kind,
			'item'       => $o->item,
			'label'      => Orders::display_label( $o ),
			'amount'     => (float) $o->amount_ttc,
			'status'     => $o->status,
			'status_label' => $labels[ $o->status ] ?? $o->status,
			'date'       => $o->created_at,
			'paid_at'    => $o->paid_at,
			'method'     => Settings::method_labels()[ $o->method ] ?? '',
			'proof'      => Proofs::url( $o ),
			'reason'     => (string) $o->reject_reason,
			'invoice_no' => $o->invoice_no,
			'url'        => CoreRoutes::url( 'account', array( 'view' => 'order', 'order' => (int) $o->id ) ),
			'invoice'    => 'paid' === $o->status ? CoreRoutes::url( 'account', array( 'view' => 'invoice', 'id' => (int) $o->id ) ) : '',
		);
	}

	public static function subscription( $result ) {
		$user_id = get_current_user_id();
		$company = $user_id ? Companies::for_user( $user_id ) : null;
		if ( ! $company ) {
			return null;
		}
		$plan  = Subscriptions::plan_of( $company );
		$s     = Settings::all();
		$open  = absint( $_GET['order'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		$order = $open ? Orders::get( $open ) : null;
		$order = $order && (int) $order->company_id === (int) $company->id ? $order : null;
		$st    = Credits::status( $company );
		return array(
			'has_plans' => $plan['has_plans'],
			'class'     => $company->account_class,
			'can_order' => (bool) Form::company(),
			'current'   => array(
				'code'    => $plan['code'],
				'name'    => $plan['name'],
				'limits'  => $plan['data'] ? array_intersect_key( $plan['data'], array_flip( Plans::limit_keys() ) ) : array(),
				'ends_at' => $plan['sub'] ? $plan['sub']->paid_until : '',
				'period'  => $plan['sub'] ? $plan['sub']->period : '',
				'founder' => $plan['sub'] ? (bool) $plan['sub']->founder : false,
			),
			'plans'     => self::shape_plans( $company->account_class, $plan['code'] ),
			'labels'    => Plans::limit_labels(),
			'usage'     => self::usage( $company ),
			'credits'   => array(
				'monthly'      => PHP_INT_MAX === $st['monthly'] ? null : $st['monthly'],
				'used'         => $st['used'],
				'monthly_left' => $st['monthly_left'],
				'bought'       => $st['bought'],
				'total_left'   => PHP_INT_MAX === $st['total_left'] ? null : $st['total_left'],
			),
			'packs'     => array_values( (array) $s['packs'] ),
			'options'   => self::options(),
			'founders'  => self::founders() + array( 'member' => Subscriptions::is_founder( (int) $company->id ) ),
			'orders'    => array_map( array( __CLASS__, 'shape_order' ), Orders::query( array( 'company' => $company->id, 'limit' => 30 ) ) ),
			'order'     => $order ? self::shape_order( $order ) : null,
			'methods'   => Settings::methods(),
			'payment'   => array(
				'legal_name'   => $s['legal_name'],
				'bank_name'    => $s['bank_name'],
				'rib'          => $s['rib'],
				'ccp'          => $s['ccp'],
				'baridimob'    => $s['baridimob'],
				'cheque_to'    => $s['cheque_to'],
				'instructions' => $s['instructions'],
			),
		);
	}

	public static function invoice( $result, $args ) {
		$order = Orders::get( absint( $args['id'] ?? 0 ) );
		if ( ! $order || 'paid' !== $order->status ) {
			return null;
		}
		$user_id = get_current_user_id();
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'sm_moderate' ) && ! ( $user_id && Companies::member_role( (int) $order->company_id, $user_id ) ) ) {
			return null;
		}
		$billing = json_decode( (string) $order->billing, true );
		return array(
			'number'  => $order->invoice_no,
			'date'    => $order->paid_at,
			'ref'     => $order->ref,
			'label'   => $order->label,
			'ht'      => (float) $order->amount_ht,
			'rate'    => (float) $order->tva_rate,
			'tva'     => round( (float) $order->amount_ttc - (float) $order->amount_ht, 2 ),
			'ttc'     => (float) $order->amount_ttc,
			'method'  => Settings::method_labels()[ $order->method ] ?? '',
			'seller'  => (array) ( $billing['seller'] ?? array() ),
			'buyer'   => (array) ( $billing['buyer'] ?? array() ),
		);
	}

	/** The account resource gets the plan name (menu, dashboard). */
	public static function account( $account ) {
		if ( is_array( $account ) && ! empty( $account['company']['id'] ) ) {
			$plan = Subscriptions::plan_of( (int) $account['company']['id'] );
			if ( $plan['has_plans'] ) {
				$account['plan'] = $plan['name'];
			}
		}
		return $account;
	}
}
