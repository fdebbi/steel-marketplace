<?php
/**
 * Seeds the building categories in the SM Products category table (once; they are
 * then edited in wp-admin like the other categories).
 *
 * @package SM\Buildings
 */

namespace SM\Buildings\Install;

defined( 'ABSPATH' ) || exit;

final class Installer {

	public static function maybe_upgrade() {
		if ( (int) get_option( 'sm_buildings_db_version', 0 ) >= SM_BUILDINGS_DB_VERSION ) {
			return;
		}
		if ( ! class_exists( '\\SM\\Products\\Install\\Installer' ) ) {
			return;
		}
		\SM\Products\Install\Installer::insert_categories( 'buildings', self::categories() );
		update_option( 'sm_buildings_db_version', SM_BUILDINGS_DB_VERSION, false );
	}

	public static function categories() {
		return require SM_BUILDINGS_DIR . '/data/categories.php';
	}
}
