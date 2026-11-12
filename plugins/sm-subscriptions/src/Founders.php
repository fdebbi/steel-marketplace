<?php
/**
 * Founding members offer: the first verified companies and professionals
 * (as many as the places set by the administrator) get a paid plan free for
 * some months; afterwards they keep the price of that plan as it was that day.
 *
 * @package SM\Subscriptions
 */

namespace SM\Subscriptions;

use SM\Core\Accounts\Companies;

defined( 'ABSPATH' ) || exit;

final class Founders {

	const PRICES = 'sm_sub_founder_prices';

	public static function init() {
		add_action( 'sm_event_company_verified', array( __CLASS__, 'on_verified' ) );
	}

	/** Places left. */
	public static function places_left() {
		$s = Settings::all();
		return $s['founders_on'] ? max( 0, (int) $s['founders_slots'] - Subscriptions::founders_count() ) : 0;
	}

	public static function on_verified( $payload ) {
		self::maybe_grant( (int) ( $payload['company_id'] ?? 0 ), (int) ( $payload['level'] ?? 0 ) );
	}

	/** Give the offer when the account qualifies. Returns the subscription id or 0. */
	public static function maybe_grant( $company_id, $level = null ) {
		$s       = Settings::all();
		$company = Companies::get( $company_id );
		if ( ! $company || ! $s['founders_on'] || 'active' !== $company->status ) {
			return 0;
		}
		$level = null === $level ? (int) $company->verification_level : (int) $level;
		$code  = (string) ( $s['founders_plan'][ $company->account_class ] ?? '' );
		$plan  = Plans::get( $company->account_class, $code );
		if ( ! $plan || $level < (int) $s['founders_level'] || self::places_left() <= 0 || Subscriptions::is_founder( $company_id ) || Subscriptions::current( $company_id ) ) {
			return 0;
		}
		$id = Subscriptions::activate( $company_id, $code, (int) $s['founders_months'], array( 'period' => 'offer', 'price' => 0, 'founder' => 1, 'note' => 'founding member' ) );
		$prices = (array) get_option( self::PRICES, array() );
		$prices[ $company_id ] = array( 'plan' => $code, 'month' => (float) $plan['price_month'], 'year' => (float) $plan['price_year'] );
		update_option( self::PRICES, $prices, false );
		do_action( 'sm_sub_founder', $company_id, $code );
		return $id;
	}

	/** Price kept by a founding member for a plan and period, or null. */
	public static function locked_price( $company_id, $plan, $period ) {
		$p = ( (array) get_option( self::PRICES, array() ) )[ $company_id ] ?? null;
		return $p && $p['plan'] === $plan ? (float) ( $p[ $period ] ?? 0 ) : null;
	}
}
