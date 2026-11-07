<?php
/**
 * Uninstall: removes data ONLY when "Delete all platform data" is enabled in
 * Steel Marketplace -> Settings. Otherwise companies, enquiries and logs are kept.
 *
 * @package SM\Core
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$sm_settings = get_option( 'sm_core_settings', array() );
wp_clear_scheduled_hook( 'sm_core_daily' );
if ( empty( $sm_settings['delete_on_uninstall'] ) ) {
	return;
}
global $wpdb;
foreach ( array( 'companies', 'company_members', 'company_activities', 'verifications', 'wilayas', 'translations', 'media_links', 'listing_index', 'notifications', 'enquiries', 'audit_log' ) as $sm_t ) {
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'sm_' . $sm_t ); // phpcs:ignore WordPress.DB
}
delete_option( 'sm_core_settings' );
delete_option( 'sm_core_db_version' );
remove_role( 'sm_moderator' );
