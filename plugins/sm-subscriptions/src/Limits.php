<?php
/**
 * The plan in force applied across the platform, through the filters of the
 * other extensions: listings online, tonnage and buildings (sm-products),
 * service pages and completed projects (sm-services), first answers to requests
 * for quotation per month (sm-rfq), AI credits (sm-ai), contacts on the public
 * page and featured place (sm-core). When a paid period ends, what goes beyond
 * the free plan is paused, the oldest first; nothing is deleted.
 *
 * @package SM\Subscriptions
 */

namespace SM\Subscriptions;

use SM\Core\Accounts\Companies;

defined( 'ABSPATH' ) || exit;

final class Limits {

	public static function init() {
		add_filter( 'sm_products_publish_limit', array( __CLASS__, 'products' ), 10, 4 );
		add_filter( 'sm_services_limit', array( __CLASS__, 'services' ), 10, 3 );
		add_filter( 'sm_portfolio_limit', array( __CLASS__, 'portfolio' ), 10, 3 );
		add_filter( 'sm_rfq_offer_limit', array( __CLASS__, 'rfq_offers' ), 10, 3 );
		add_filter( 'sm_ai_plan_credits', '__return_true' );
		add_filter( 'sm_ai_can_use', array( __CLASS__, 'ai_can_use' ), 10, 3 );
		add_filter( 'sm_ai_remaining', array( __CLASS__, 'ai_remaining' ), 10, 2 );
		add_action( 'sm_ai_used', array( Credits::class, 'on_used' ), 10, 2 );
		add_filter( 'sm_company_contacts_visible', array( __CLASS__, 'contacts' ), 10, 2 );
		add_filter( 'sm_company_featured', array( __CLASS__, 'featured' ), 10, 2 );
		add_action( 'sm_subscription_ended', array( __CLASS__, 'enforce' ), 10, 1 );
	}

	/** Limit of the plan in force (-1 = none), or null when the account has no plan. */
	public static function limit( $company, $key ) {
		$plan = Subscriptions::plan_of( $company );
		return $plan['has_plans'] ? $plan['data'][ $key ] : null;
	}

	private static function over( $limit, $value ) {
		return null !== $limit && Plans::UNLIMITED !== (int) $limit && $value > $limit;
	}

	/** Sentence closing every refusal. */
	private static function upgrade_hint() {
		return __( 'Choose a higher plan in your account (Plan) or take a listing offline.', 'sm-subscriptions' );
	}

	public static function products( $reason, $company, $listing, $online ) {
		if ( $reason ) {
			return $reason;
		}
		$plan = Subscriptions::plan_of( $company );
		if ( ! $plan['has_plans'] ) {
			return '';
		}
		$d = $plan['data'];
		if ( 'buildings' === $listing['market'] ) {
			if ( self::over( $d['buildings'], $online['buildings'] + 1 ) ) {
				/* translators: 1: plan name, 2: number of buildings, 3: hint. */
				return sprintf( _n( 'Your plan %1$s allows %2$d building online. %3$s', 'Your plan %1$s allows %2$d buildings online. %3$s', (int) $d['buildings'], 'sm-subscriptions' ), $plan['name'], (int) $d['buildings'], self::upgrade_hint() );
			}
			return '';
		}
		if ( self::over( $d['listings'], $online['listings'] + 1 ) ) {
			/* translators: 1: plan name, 2: number of listings, 3: hint. */
			return sprintf( _n( 'Your plan %1$s allows %2$d listing online. %3$s', 'Your plan %1$s allows %2$d listings online. %3$s', (int) $d['listings'], 'sm-subscriptions' ), $plan['name'], (int) $d['listings'], self::upgrade_hint() );
		}
		if ( self::over( $d['weight_t'], ( $online['weight'] + $listing['weight'] ) / 1000 ) ) {
			return sprintf(
				/* translators: 1: plan name, 2: tonnes allowed, 3: tonnes already online, 4: hint. */
				__( 'Your plan %1$s allows %2$s t of steel online in total; you already have %3$s t online. %4$s', 'sm-subscriptions' ),
				$plan['name'],
				number_format_i18n( (float) $d['weight_t'] ),
				number_format_i18n( $online['weight'] / 1000, 1 ),
				self::upgrade_hint()
			);
		}
		return '';
	}

	public static function services( $reason, $company, $count ) {
		$limit = $reason ? null : self::limit( $company, 'services' );
		if ( self::over( $limit, $count + 1 ) ) {
			/* translators: 1: number of pages, 2: hint. */
			return sprintf( _n( 'Your plan allows %1$d service page. %2$s', 'Your plan allows %1$d service pages. %2$s', (int) $limit, 'sm-subscriptions' ), (int) $limit, __( 'Choose a higher plan in your account (Plan).', 'sm-subscriptions' ) );
		}
		return $reason;
	}

	public static function portfolio( $reason, $company, $count ) {
		$limit = $reason ? null : self::limit( $company, 'portfolio' );
		if ( self::over( $limit, $count + 1 ) ) {
			/* translators: 1: number of projects, 2: hint. */
			return sprintf( _n( 'Your plan allows %1$d completed project. %2$s', 'Your plan allows %1$d completed projects. %2$s', (int) $limit, 'sm-subscriptions' ), (int) $limit, __( 'Choose a higher plan in your account (Plan).', 'sm-subscriptions' ) );
		}
		return $reason;
	}

	public static function rfq_offers( $reason, $company, $month_offers ) {
		$limit = $reason ? null : self::limit( $company, 'rfq_offers' );
		if ( self::over( $limit, $month_offers + 1 ) ) {
			/* translators: 1: number of answers, 2: hint. */
			return sprintf( _n( 'Your plan allows %1$d answer to requests for quotation per month. %2$s', 'Your plan allows %1$d answers to requests for quotation per month. %2$s', (int) $limit, 'sm-subscriptions' ), (int) $limit, __( 'Choose a higher plan in your account (Plan) to answer without limit.', 'sm-subscriptions' ) );
		}
		return $reason;
	}

	public static function ai_can_use( $ok, $feature, $company_id ) {
		if ( is_wp_error( $ok ) || ! $company_id ) {
			return $ok;
		}
		$st = Credits::status( (int) $company_id );
		if ( $st['total_left'] <= 0 ) {
			return new \WP_Error( 'sm_ai_credits', __( 'You used all your AI credits. Buy a pack of credits in AI assistant → AI credits.', 'sm-subscriptions' ) );
		}
		return $ok;
	}

	public static function ai_remaining( $remaining, $company ) {
		$st = Credits::status( $company );
		return PHP_INT_MAX === $st['total_left'] ? null : $st['total_left'];
	}

	public static function contacts( $visible, $company ) {
		$plan = Subscriptions::plan_of( $company );
		return $plan['has_plans'] ? (bool) $plan['data']['contacts'] : $visible;
	}

	/**
	 * Accounts whose plan includes the featured place join the home page row,
	 * in an order that changes every day (rotation).
	 */
	public static function featured( $ids, $class ) {
		global $wpdb;
		$codes = array_keys( array_filter( Plans::for_class( $class ), static fn( $p ) => 'home' === $p['featured'] ) );
		if ( ! $codes ) {
			return $ids;
		}
		$now  = current_time( 'mysql', true );
		$rows = $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT s.company_id FROM ' . Subscriptions::table() . " s JOIN {$wpdb->prefix}sm_companies c ON c.id = s.company_id WHERE s.status = 'active' AND s.starts_at <= %s AND s.ends_at > %s AND c.account_class = %s AND c.status = 'active' AND s.plan IN (" . implode( ',', array_fill( 0, count( $codes ), '%s' ) ) . ')', array_merge( array( $now, $now, $class ), $codes ) ) ); // phpcs:ignore WordPress.DB
		$rows = array_map( 'intval', $rows );
		usort( $rows, static fn( $a, $b ) => crc32( gmdate( 'Ymd' ) . $a ) <=> crc32( gmdate( 'Ymd' ) . $b ) );
		return array_merge( (array) $ids, $rows );
	}

	/**
	 * Bring a company back within its plan: listings and service pages over the
	 * limits are paused, the oldest first. Returns the number paused.
	 */
	public static function enforce( $company_id ) {
		$company = Companies::get( (int) $company_id );
		$plan    = Subscriptions::plan_of( $company );
		if ( ! $plan['has_plans'] ) {
			return 0;
		}
		$d      = $plan['data'];
		$paused = 0;
		if ( class_exists( '\\SM\\Products\\Products' ) ) {
			$rows = array_reverse( \SM\Products\Products::online_oldest_first( (int) $company_id ) ); // Newest first: they are kept.
			$keep = array( 'listings' => 0, 'weight' => 0.0, 'buildings' => 0 );
			foreach ( $rows as $r ) {
				if ( 'buildings' === $r->market ) {
					$ok = ! self::over( $d['buildings'], $keep['buildings'] + 1 );
					$keep['buildings'] += $ok ? 1 : 0;
				} else {
					$ok = ! self::over( $d['listings'], $keep['listings'] + 1 ) && ! self::over( $d['weight_t'], ( $keep['weight'] + (float) $r->total_weight ) / 1000 );
					if ( $ok ) {
						++$keep['listings'];
						$keep['weight'] += (float) $r->total_weight;
					}
				}
				if ( ! $ok ) {
					\SM\Products\Products::set_status( (int) $r->id, 'paused' );
					++$paused;
				}
			}
		}
		if ( class_exists( '\\SM\\Services\\Services' ) && Plans::UNLIMITED !== (int) $d['services'] ) {
			$services = \SM\Services\Services::query( array( 'company' => (int) $company_id, 'status' => 'published', 'per_page' => 200 ) );
			$items    = $services['items'] ?? $services;
			$items    = array_values( (array) $items );
			usort( $items, static fn( $a, $b ) => strcmp( (string) ( $b->published_at ?? $b->created_at ?? '' ), (string) ( $a->published_at ?? $a->created_at ?? '' ) ) );
			foreach ( array_slice( $items, (int) $d['services'] ) as $s ) {
				\SM\Services\Services::set_status( (int) $s->id, 'paused' );
				++$paused;
			}
		}
		if ( $paused ) {
			do_action( 'sm_sub_paused', (int) $company_id, $paused );
		}
		return $paused;
	}
}
