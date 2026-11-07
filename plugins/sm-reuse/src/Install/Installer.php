<?php
/**
 * Seeds the reuse categories in the SM Products category table (once; they are
 * then edited in wp-admin like the other categories).
 *
 * @package SM\Reuse
 */

namespace SM\Reuse\Install;

defined( 'ABSPATH' ) || exit;

final class Installer {

	public static function maybe_upgrade() {
		if ( (int) get_option( 'sm_reuse_db_version', 0 ) >= SM_REUSE_DB_VERSION ) {
			return;
		}
		if ( ! class_exists( '\\SM\\Products\\Install\\Installer' ) ) {
			return;
		}
		\SM\Products\Install\Installer::insert_categories( 'reuse', self::categories() );
		update_option( 'sm_reuse_db_version', SM_REUSE_DB_VERSION, false );
	}

	public static function categories() {
		return require SM_REUSE_DIR . '/data/categories.php';
	}
}
