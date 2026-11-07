<?php
/**
 * Read and edit the steel section catalogue.
 *
 * @package SM\WeightEngine
 */

namespace SM\WeightEngine;

use SM\WeightEngine\Domain\Geometry;

defined( 'ABSPATH' ) || exit;

final class Catalogue {

	const GROUP = 'sm_weight';

	/** Families (active only unless $all), ordered. code => object. */
	public static function families( $all = false ) {
		global $wpdb;
		$rows = wp_cache_get( 'families', self::GROUP );
		if ( false === $rows ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sm_steel_families ORDER BY sort, code", OBJECT_K );
			$counts = $wpdb->get_results( "SELECT family, COUNT(*) AS n FROM {$wpdb->prefix}sm_steel_sections WHERE status = 'active' GROUP BY family", OBJECT_K ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			foreach ( $rows as $code => $r ) {
				$r->count = isset( $counts[ $code ] ) ? (int) $counts[ $code ]->n : 0;
			}
			wp_cache_set( 'families', $rows, self::GROUP, HOUR_IN_SECONDS );
		}
		return $all ? $rows : array_filter( $rows, static fn( $r ) => (int) $r->active );
	}

	public static function family( $code ) {
		$all = self::families( true );
		return $all[ strtoupper( (string) $code ) ] ?? null;
	}

	/** Sections of a family (active only unless $all), in catalogue order. */
	public static function sections( $family, $all = false ) {
		global $wpdb;
		$family = strtoupper( (string) $family );
		$key    = 'sections_' . $family;
		$rows   = wp_cache_get( $key, self::GROUP );
		if ( false === $rows ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sm_steel_sections WHERE family = %s ORDER BY sort, id", $family ) );
			$rows = array_map( array( __CLASS__, 'hydrate' ), $rows );
			wp_cache_set( $key, $rows, self::GROUP, HOUR_IN_SECONDS );
		}
		return $all ? $rows : array_values( array_filter( $rows, static fn( $r ) => 'active' === $r->status ) );
	}

	public static function get( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sm_steel_sections WHERE id = %d", $id ) );
		return $row ? self::hydrate( $row ) : null;
	}

	public static function find( $family, $designation ) {
		$norm = static fn( $s ) => strtoupper( preg_replace( '/[\s×*]+/u', '', (string) $s ) );
		foreach ( self::sections( $family, true ) as $s ) {
			if ( $norm( $s->designation ) === $norm( $designation ) || $norm( $s->family . $s->designation ) === $norm( $family . $designation ) ) {
				return $s;
			}
		}
		return null;
	}

	/** Mass per metre (kg/m) from the area and the density setting. */
	public static function mass_per_metre( $area_mm2 ) {
		return (float) $area_mm2 * (float) Settings::get( 'density' ) / 1e6;
	}

	private static function hydrate( $r ) {
		$r->id             = (int) $r->id;
		$r->dims           = json_decode( $r->dims, true ) ?: array();
		$r->area_mm2       = (float) $r->area_mm2;
		$r->mass_kg_m      = round( self::mass_per_metre( $r->area_mm2 ), 3 );
		$r->mass_published = null === $r->mass_published ? null : (float) $r->mass_published;
		return $r;
	}

	/**
	 * Save a section (new or existing) after checking its geometry. When a
	 * published mass is given it must agree with the mass computed from the
	 * dimensions (2 %), unless $confirm is set with a note.
	 *
	 * @return int|\WP_Error Section ID.
	 */
	public static function save( $family, array $data, $id = 0, $confirm = false ) {
		global $wpdb;
		$fam = self::family( $family );
		if ( ! $fam ) {
			return new \WP_Error( 'smw_family', __( 'Unknown section family.', 'sm-weight-engine' ) );
		}
		$designation = trim( sanitize_text_field( $data['designation'] ?? '' ) );
		if ( '' === $designation ) {
			return new \WP_Error( 'smw_designation', __( 'Enter the designation (for example IPE 200).', 'sm-weight-engine' ) );
		}
		$dims = array();
		foreach ( Geometry::SHAPES[ $fam->shape ] as $k ) {
			$dims[ $k ] = isset( $data['dims'][ $k ] ) && is_numeric( str_replace( ',', '.', $data['dims'][ $k ] ) ) ? (float) str_replace( ',', '.', $data['dims'][ $k ] ) : null;
		}
		$valid = Geometry::validate( $fam->shape, $dims );
		if ( true !== $valid ) {
			return new \WP_Error( 'smw_dims', __( 'Check the dimensions: every value is required, in mm, and thicknesses must be smaller than the section.', 'sm-weight-engine' ) );
		}
		$area      = Geometry::area( $fam->shape, $dims );
		$mass      = self::mass_per_metre( $area );
		$published = isset( $data['mass_published'] ) && '' !== $data['mass_published'] ? (float) str_replace( ',', '.', $data['mass_published'] ) : null;
		if ( null !== $published && abs( $mass - $published ) > 0.02 * $published && ! $confirm ) {
			return new \WP_Error(
				'smw_mass_mismatch',
				/* translators: 1: mass computed from the dimensions, 2: published mass. */
				sprintf( __( 'The dimensions give %1$s kg/m but the published mass is %2$s kg/m. Check the values or confirm with a note.', 'sm-weight-engine' ), number_format( $mass, 2, '.', '' ), number_format( $published, 2, '.', '' ) )
			);
		}
		$table  = $wpdb->prefix . 'sm_steel_sections';
		$exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE family = %s AND designation = %s AND id <> %d", $fam->code, $designation, (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( $exists ) {
			return new \WP_Error( 'smw_duplicate', __( 'This designation already exists in the family.', 'sm-weight-engine' ) );
		}
		$row    = array(
			'designation'    => $designation,
			'dims'           => wp_json_encode( $dims ),
			'area_mm2'       => round( $area, 2 ),
			'mass_published' => $published,
			'edited'         => 1,
			'updated_by'     => get_current_user_id(),
			'updated_at'     => current_time( 'mysql', true ),
		);
		$before = $id ? self::get( $id ) : null;
		if ( $id && $before ) {
			$wpdb->update( $table, $row, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		} else {
			$wpdb->insert( $table, $row + array( 'family' => $fam->code, 'origin' => 'admin', 'status' => 'active', 'sort' => 9999 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$id = (int) $wpdb->insert_id;
		}
		self::log(
			(int) $id,
			$fam->code,
			$before ? 'section.updated' : 'section.created',
			array(
				'before'  => $before ? array( 'designation' => $before->designation, 'dims' => $before->dims, 'mass_published' => $before->mass_published ) : null,
				'after'   => array( 'designation' => $designation, 'dims' => $dims, 'mass_published' => $published ),
				'note'    => sanitize_textarea_field( $data['note'] ?? '' ),
				'confirm' => (bool) $confirm,
			)
		);
		self::flush();
		return (int) $id;
	}

	public static function set_status( $id, $status ) {
		global $wpdb;
		$s = self::get( $id );
		if ( ! $s || ! in_array( $status, array( 'active', 'disabled' ), true ) ) {
			return false;
		}
		$wpdb->update( $wpdb->prefix . 'sm_steel_sections', array( 'status' => $status, 'updated_by' => get_current_user_id(), 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		self::log( (int) $id, $s->family, 'section.' . $status, array( 'designation' => $s->designation ) );
		self::flush();
		return true;
	}

	public static function set_family_active( $code, $active ) {
		global $wpdb;
		$wpdb->update( $wpdb->prefix . 'sm_steel_families', array( 'active' => $active ? 1 : 0 ), array( 'code' => $code ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		self::log( 0, $code, $active ? 'family.enabled' : 'family.disabled', array() );
		self::flush();
	}

	public static function log( $section_id, $family, $action, array $data ) {
		global $wpdb;
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . 'sm_steel_log',
			array(
				'section_id' => (int) $section_id,
				'family'     => (string) $family,
				'action'     => $action,
				'data'       => wp_json_encode( $data ),
				'user_id'    => get_current_user_id(),
				'created_at' => current_time( 'mysql', true ),
			)
		);
	}

	public static function flush() {
		wp_cache_delete( 'families', self::GROUP );
		foreach ( array_keys( require SM_WEIGHT_DIR . '/data/families.php' ) as $code ) {
			wp_cache_delete( 'sections_' . $code, self::GROUP );
		}
	}
}
