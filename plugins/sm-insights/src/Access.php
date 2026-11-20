<?php
/**
 * Who can open the Business report: companies and professionals; with
 * sm-subscriptions, the Business plan only (filter sm_insights_access).
 *
 * @package SM\Insights
 */

namespace SM\Insights;

defined( 'ABSPATH' ) || exit;

final class Access {

	/** Account that may see the page (menu): company or professional. */
	public static function account( $company ) {
		return $company && in_array( (string) $company->account_class, array( 'company', 'professional' ), true );
	}

	/** ok | login | none | upgrade */
	public static function state( $company = null ) {
		if ( ! is_user_logged_in() ) {
			return 'login';
		}
		$company = $company ?? sm_current_company();
		if ( ! self::account( $company ) ) {
			return 'none';
		}
		/**
		 * Business report allowed for this company (sm-subscriptions: Business plan).
		 *
		 * @param bool   $ok      Allowed.
		 * @param object $company Company.
		 */
		return apply_filters( 'sm_insights_access', true, $company ) ? 'ok' : 'upgrade';
	}
}
