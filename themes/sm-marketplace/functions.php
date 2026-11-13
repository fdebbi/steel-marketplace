<?php
/**
 * SM Marketplace theme bootstrap.
 *
 * The theme is a presentation layer only. It must never contain business rules,
 * SQL queries or pricing/weight logic: those belong to the sm-* plugins.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

define( 'SM_THEME_VERSION', '0.24.0' );
define( 'SM_THEME_DIR', get_template_directory() );
define( 'SM_THEME_URI', get_template_directory_uri() );

$sm_theme_includes = array(
	'inc/compat/language.php',   // Temporary language layer until sm-core (Phase 2) provides it.
	'inc/cache.php',             // Page-cache safety for query-dependent pages.
	'inc/setup.php',             // Theme supports, menus, image sizes, text domain.
	'inc/customizer.php',        // Brand colours and logo editable from wp-admin.
	'inc/assets.php',            // Styles, scripts, fonts, RTL handling.
	'inc/template-tags.php',     // Presentation helpers (icons, components, formatting).
	'inc/data-contract.php',     // Read-only data access contract used by templates.
	'inc/forms.php',             // Form, notice and route helpers (processing is in sm-core).
	'inc/menus.php',             // Header mega menu and mobile navigation.
	'inc/demo/demo-data.php',    // Demo content provider (visual testing only).
	'inc/demo/installer.php',    // Creates the demo pages and assigns templates.
);

foreach ( $sm_theme_includes as $sm_file ) {
	require_once SM_THEME_DIR . '/' . $sm_file;
}
unset( $sm_theme_includes, $sm_file );
