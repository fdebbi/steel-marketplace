<?php
/**
 * Plugin bootstrap: wires every core service.
 *
 * @package SM\Core
 */

namespace SM\Core;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** Boot once all plugins are loaded. */
	public static function boot() {
		load_plugin_textdomain( 'sm-core', false, dirname( plugin_basename( SM_CORE_FILE ) ) . '/languages' );

		Install\Installer::maybe_upgrade();

		Modules::init();
		I18n\Language::init();
		Routing\Routes::init();
		Accounts\Auth::init();
		Accounts\Registration::init();
		Accounts\PhoneVerification::init();
		Accounts\CompanyForms::init();
		Support\Forms::init();
		Support\Dialog::init();
		Verification\Verifications::init();
		Notifications\Notifications::init();
		Enquiries\Enquiries::init();
		Listings\Index::init();
		Security\Hardening::init(); // 0.9.0
		Content\ImageFormat::init(); // 0.9.0
		Legal\Legal::init(); // 0.10.0
		Audit\AuditLog::init();
		Mail\Activity::init();
		Mail\Contact::init();
		Rest\Api::init();
		Theme\Bridge::init();

		if ( is_admin() ) {
			Admin\Admin::init();
			Admin\Terms::init();
			Admin\MailAdmin::init();
			Admin\LegalAdmin::init(); // 0.10.0
		}

		/**
		 * Fires when sm-core is ready. Other sm-* plugins register here.
		 *
		 * @param Modules $registry Module registry.
		 */
		do_action( 'sm_register_modules', Modules::instance() );
		do_action( 'sm_core_loaded' );
	}
}
