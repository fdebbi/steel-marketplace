<?php
/**
 * Permission engine: sm_can( $cap, $company_id, $context ).
 *
 * A capability is granted when ALL of the following hold:
 *   1. the company exists and is active;
 *   2. the account class allows it — HARD RULE: an engineering company never
 *      gets a trading capability (buy, listing.*, rfq.*, order.*,
 *      service.fabrication), whatever the settings, plan or filters say;
 *   3. at least one enabled activity grants it (rights matrix, admin-editable);
 *   4. the company's verification level reaches the minimum set for it;
 *   5. the plan allows it (filter "sm_entitlement", used by sm-subscriptions).
 *
 * @package SM\Core
 */

namespace SM\Core\Permissions;

use SM\Core\Accounts\Companies;

defined( 'ABSPATH' ) || exit;

final class Permissions {

	/** All capabilities, with translated labels. */
	public static function capabilities() {
		return apply_filters(
			'sm_capabilities',
			array(
				'buy'                 => __( 'Buy, save listings and contact sellers', 'sm-core' ),
				'listing.products'    => __( 'Publish new steel products', 'sm-core' ),
				'listing.reuse'       => __( 'Publish reuse lots, offcuts and scrap', 'sm-core' ),
				'listing.buildings'   => __( 'Publish buildings and warehouses', 'sm-core' ),
				'rfq.create'          => __( 'Create requests for quotation', 'sm-core' ),
				'rfq.offer'           => __( 'Answer requests for quotation', 'sm-core' ),
				'service.fabrication' => __( 'Offer fabrication, installation and dismantling', 'sm-core' ),
				'service.engineering' => __( 'Publish engineering services', 'sm-core' ),
				'service.request'     => __( 'Request an engineering or fabrication service', 'sm-core' ),
			)
		);
	}

	/** Capabilities an engineering company can never receive (prefix match). */
	public static function trading_capabilities() {
		return array( 'buy', 'listing.', 'rfq.', 'order.', 'service.fabrication' );
	}

	public static function is_trading_capability( $cap ) {
		foreach ( self::trading_capabilities() as $prefix ) {
			if ( $cap === $prefix || ( '.' === substr( $prefix, -1 ) && 0 === strpos( $cap, $prefix ) ) ) {
				return true;
			}
		}
		return false;
	}

	/** Default rights matrix (Phase 0 architecture, section 5). activity => caps. */
	public static function default_rights() {
		$base = array( 'buy', 'listing.reuse', 'rfq.create', 'service.request' );
		return array(
			'manufacturer'  => array_merge( $base, array( 'listing.products', 'listing.buildings', 'rfq.offer', 'service.fabrication' ) ),
			'supplier'      => array_merge( $base, array( 'listing.products', 'listing.buildings', 'rfq.offer' ) ),
			'installer'     => array_merge( $base, array( 'listing.products', 'listing.buildings', 'rfq.offer', 'service.fabrication' ) ),
			'project_owner' => array_merge( $base, array( 'listing.products', 'listing.buildings' ) ),
			'reuse_dealer'  => array_merge( $base, array( 'listing.products', 'listing.buildings', 'rfq.offer', 'service.fabrication' ) ),
			'fabrication'   => array_merge( $base, array( 'rfq.offer', 'service.fabrication' ) ),
			'engineer'      => array( 'service.engineering', 'service.request' ),
		);
	}

	/** Default minimum verification level per capability (0 = e-mail only). */
	public static function default_min_levels() {
		return array(
			'buy'                 => 0,
			'listing.products'    => 1,
			'listing.reuse'       => 1,
			'listing.buildings'   => 1,
			'rfq.create'          => 1,
			'rfq.offer'           => 1,
			'service.fabrication' => 1,
			'service.engineering' => 1,
			'service.request'     => 0,
		);
	}

	public static function rights() {
		$rights = sm_setting( 'rights', self::default_rights() );
		return is_array( $rights ) ? $rights : self::default_rights();
	}

	public static function min_levels() {
		return array_merge( self::default_min_levels(), (array) sm_setting( 'min_levels', array() ) );
	}

	/**
	 * @return true|\WP_Error
	 */
	public static function check( $cap, $company_id = null, array $context = array() ) {
		$company = null === $company_id ? Companies::for_user( get_current_user_id() ) : Companies::get( $company_id );

		if ( ! $company ) {
			return new \WP_Error( 'sm_no_company', __( 'Sign in with a business or engineering account to do this.', 'sm-core' ) );
		}
		if ( 'active' !== $company->status ) {
			return new \WP_Error( 'sm_suspended', __( 'This account is suspended. Contact the platform team.', 'sm-core' ) );
		}

		// Hard rule, evaluated before any setting or filter.
		if ( 'engineering' === $company->account_class && self::is_trading_capability( $cap ) ) {
			return new \WP_Error( 'sm_class_forbidden', __( 'Engineering accounts offer services only. To trade steel, open a separate business account.', 'sm-core' ) );
		}

		$rights  = self::rights();
		$granted = false;
		foreach ( $company->activities as $activity ) {
			if ( in_array( $cap, $rights[ $activity ] ?? array(), true ) ) {
				$granted = true;
				break;
			}
		}
		if ( ! $granted ) {
			return new \WP_Error( 'sm_activity_missing', __( 'None of your business activities allows this action. Add the activity in your company settings.', 'sm-core' ) );
		}

		$min = (int) ( self::min_levels()[ $cap ] ?? 0 );
		if ( (int) $company->verification_level < $min ) {
			return new \WP_Error( 'sm_verification_required', __( 'Verify your account to do this.', 'sm-core' ), array( 'required_level' => $min ) );
		}

		/**
		 * Plan entitlement (sm-subscriptions). Return a WP_Error to refuse.
		 *
		 * @param true|\WP_Error $allowed
		 */
		$entitlement = apply_filters( 'sm_entitlement', true, $cap, $company, $context );
		if ( is_wp_error( $entitlement ) ) {
			return $entitlement;
		}
		if ( true !== $entitlement ) {
			return new \WP_Error( 'sm_plan_limit', __( 'Your plan does not include this action.', 'sm-core' ) );
		}

		// The hard rule cannot be bypassed by the entitlement filter either.
		if ( 'engineering' === $company->account_class && self::is_trading_capability( $cap ) ) {
			return new \WP_Error( 'sm_class_forbidden', __( 'Engineering accounts offer services only.', 'sm-core' ) );
		}
		return true;
	}

	/** Map of every capability => bool for a company (REST /me, theme menus). */
	public static function map( $company_id = null ) {
		$out = array();
		foreach ( array_keys( self::capabilities() ) as $cap ) {
			$out[ $cap ] = true === self::check( $cap, $company_id );
		}
		return $out;
	}

	/** Sanitize a rights matrix posted from the admin screen (hard rule enforced). */
	public static function sanitize_rights( array $posted ) {
		$caps  = array_keys( self::capabilities() );
		$clean = array();
		foreach ( Companies::activities() as $class => $activities ) {
			foreach ( $activities as $activity ) {
				$list = array_values( array_intersect( (array) ( $posted[ $activity ] ?? array() ), $caps ) );
				if ( 'engineering' === $class ) {
					$list = array_values( array_filter( $list, static fn( $c ) => ! self::is_trading_capability( $c ) ) );
				}
				$clean[ $activity ] = $list;
			}
		}
		return $clean;
	}
}
