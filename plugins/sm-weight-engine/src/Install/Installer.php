<?php
/**
 * Tables, reference data import and upgrades.
 *
 * Reference rows (data/sections.php) are imported on activation and when the
 * bundled reference version changes. A row corrected by an administrator is
 * never overwritten by an update: the difference is written to the log
 * instead, so a correction is never lost silently.
 *
 * @package SM\WeightEngine
 */

namespace SM\WeightEngine\Install;

use SM\WeightEngine\Catalogue;
use SM\WeightEngine\Domain\Geometry;

defined( 'ABSPATH' ) || exit;

final class Installer {

	public static function activate() {
		self::migrate();
		self::import_reference();
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( 'sm_manage_catalogue' );
		}
	}

	public static function maybe_upgrade() {
		if ( (int) get_option( 'sm_weight_db_version', 0 ) < SM_WEIGHT_DB_VERSION || get_option( 'sm_weight_ref_version' ) !== SM_WEIGHT_REF_VERSION ) {
			self::activate();
		}
	}

	public static function migrate() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$p       = $wpdb->prefix . 'sm_';
		dbDelta(
			"CREATE TABLE {$p}steel_families (
				code varchar(12) NOT NULL,
				shape varchar(20) NOT NULL,
				group_code varchar(20) NOT NULL DEFAULT '',
				name_fr varchar(120) NOT NULL,
				name_ar varchar(120) NOT NULL,
				standard varchar(120) NOT NULL DEFAULT '',
				source text,
				source_url varchar(255) NOT NULL DEFAULT '',
				method varchar(10) NOT NULL DEFAULT 'table',
				sort int(11) NOT NULL DEFAULT 0,
				active tinyint(1) NOT NULL DEFAULT 1,
				PRIMARY KEY  (code)
			) $charset;"
		);
		dbDelta(
			"CREATE TABLE {$p}steel_sections (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				family varchar(12) NOT NULL,
				designation varchar(60) NOT NULL,
				dims text NOT NULL,
				area_mm2 decimal(12,2) NOT NULL DEFAULT 0,
				mass_published decimal(10,2) DEFAULT NULL,
				status varchar(12) NOT NULL DEFAULT 'active',
				origin varchar(12) NOT NULL DEFAULT 'reference',
				edited tinyint(1) NOT NULL DEFAULT 0,
				ref_version varchar(20) NOT NULL DEFAULT '',
				sort int(11) NOT NULL DEFAULT 0,
				updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY family_designation (family,designation),
				KEY family_status (family,status)
			) $charset;"
		);
		dbDelta(
			"CREATE TABLE {$p}steel_log (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				section_id bigint(20) unsigned NOT NULL DEFAULT 0,
				family varchar(12) NOT NULL DEFAULT '',
				action varchar(40) NOT NULL,
				data longtext,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY section_id (section_id),
				KEY created_at (created_at)
			) $charset;"
		);
		update_option( 'sm_weight_db_version', SM_WEIGHT_DB_VERSION, false );
	}

	/** Import or refresh the bundled reference data. Returns counts. */
	public static function import_reference() {
		global $wpdb;
		$families = require SM_WEIGHT_DIR . '/data/families.php';
		$sections = require SM_WEIGHT_DIR . '/data/sections.php';
		$ft       = $wpdb->prefix . 'sm_steel_families';
		$st       = $wpdb->prefix . 'sm_steel_sections';
		$now      = current_time( 'mysql', true );
		$counts   = array( 'inserted' => 0, 'updated' => 0, 'kept' => 0 );

		$sort = 0;
		foreach ( $families as $code => $f ) {
			$sort += 10;
			$row   = array(
				'shape'      => $f['shape'],
				'group_code' => $f['group'],
				'name_fr'    => $f['name']['fr'],
				'name_ar'    => $f['name']['ar'],
				'standard'   => $f['standard'],
				'source'     => $f['source'],
				'source_url' => $f['url'],
				'method'     => $f['method'],
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT code FROM {$ft} WHERE code = %s", $code ) ) ) {
				$wpdb->update( $ft, $row, array( 'code' => $code ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			} else {
				$wpdb->insert( $ft, $row + array( 'code' => $code, 'sort' => $sort, 'active' => 1 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
		}

		foreach ( $sections as $family => $rows ) {
			foreach ( $rows as $i => list( $designation, $dims, $area, $published ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$cur  = $wpdb->get_row( $wpdb->prepare( "SELECT id, dims, area_mm2, mass_published, edited, ref_version FROM {$st} WHERE family = %s AND designation = %s", $family, $designation ) );
				$data = array(
					'dims'           => wp_json_encode( $dims ),
					'area_mm2'       => round( Geometry::area( $families[ $family ]['shape'], $dims ), 2 ),
					'mass_published' => $published,
					'ref_version'    => SM_WEIGHT_REF_VERSION,
					'sort'           => $i,
					'updated_at'     => $now,
				);
				if ( ! $cur ) {
					$wpdb->insert( $st, $data + array( 'family' => $family, 'designation' => $designation, 'origin' => 'reference', 'status' => 'active' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					++$counts['inserted'];
					continue;
				}
				if ( SM_WEIGHT_REF_VERSION === $cur->ref_version ) {
					continue;
				}
				if ( (int) $cur->edited ) {
					if ( json_decode( $cur->dims, true ) != $dims ) { // phpcs:ignore Universal.Operators.StrictComparisons
						Catalogue::log( (int) $cur->id, $family, 'reference.kept_admin_values', array( 'reference' => $dims, 'admin' => json_decode( $cur->dims, true ), 'version' => SM_WEIGHT_REF_VERSION ) );
					}
					++$counts['kept'];
					continue;
				}
				$wpdb->update( $st, $data, array( 'id' => $cur->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				if ( json_decode( $cur->dims, true ) != $dims ) { // phpcs:ignore Universal.Operators.StrictComparisons
					Catalogue::log( (int) $cur->id, $family, 'reference.updated', array( 'before' => json_decode( $cur->dims, true ), 'after' => $dims, 'version' => SM_WEIGHT_REF_VERSION ) );
				}
				++$counts['updated'];
			}
		}
		update_option( 'sm_weight_ref_version', SM_WEIGHT_REF_VERSION, false );
		Catalogue::flush();
		return $counts;
	}
}
