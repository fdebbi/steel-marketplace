<?php
/**
 * Product categories and their fields.
 *
 * @package SM\Products
 */

namespace SM\Products;

defined( 'ABSPATH' ) || exit;

final class Categories {

	const UNITS = array( 'kg', 'unit', 'm2', 'ml' );

	/** slug => object (families, units, fields decoded). */
	public static function all( $include_inactive = false ) {
		global $wpdb;
		$rows = wp_cache_get( 'product_categories', 'sm' );
		if ( false === $rows ) {
			$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sm_product_categories ORDER BY sort, slug", OBJECT_K ); // phpcs:ignore WordPress.DB
			foreach ( $rows as $r ) {
				$r->families = json_decode( (string) $r->families, true ) ?: array();
				$r->units    = array_values( array_intersect( json_decode( (string) $r->units, true ) ?: array(), self::UNITS ) );
				$r->fields   = json_decode( (string) $r->fields, true ) ?: array();
				$r->active   = (int) $r->active;
			}
			wp_cache_set( 'product_categories', $rows, 'sm', HOUR_IN_SECONDS );
		}
		return $include_inactive ? $rows : array_filter( $rows, static fn( $r ) => $r->active );
	}

	public static function get( $slug ) {
		$all = self::all( true );
		return $all[ sanitize_key( (string) $slug ) ] ?? null;
	}

	/** slug => [fr, ar] for the theme (data contract "categories"). */
	public static function labels() {
		$out = array();
		foreach ( self::all() as $slug => $c ) {
			$out[ $slug ] = array( 'fr' => $c->name_fr, 'ar' => $c->name_ar );
		}
		return $out;
	}

	public static function save( $slug, array $data ) {
		global $wpdb;
		$c = self::get( $slug );
		if ( ! $c ) {
			return false;
		}
		$row = array();
		foreach ( array( 'name_fr', 'name_ar' ) as $k ) {
			if ( isset( $data[ $k ] ) && '' !== trim( $data[ $k ] ) ) {
				$row[ $k ] = sanitize_text_field( $data[ $k ] );
			}
		}
		if ( isset( $data['active'] ) ) {
			$row['active'] = $data['active'] ? 1 : 0;
		}
		if ( isset( $data['sort'] ) ) {
			$row['sort'] = (int) $data['sort'];
		}
		if ( isset( $data['units'] ) ) {
			$units = array_values( array_intersect( (array) $data['units'], self::UNITS ) );
			if ( $units ) {
				$row['units'] = wp_json_encode( $units );
			}
		}
		if ( isset( $data['fields'] ) && 'specs' === $c->kind ) {
			$row['fields'] = wp_json_encode( self::clean_fields( (array) $data['fields'] ) );
		}
		if ( $row ) {
			$wpdb->update( $wpdb->prefix . 'sm_product_categories', $row, array( 'slug' => $c->slug ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}
		self::flush();
		return true;
	}

	/** Normalise field definitions typed in the admin. */
	public static function clean_fields( array $fields ) {
		$out  = array();
		$keys = array();
		foreach ( $fields as $f ) {
			$key = sanitize_key( $f['key'] ?? '' );
			$fr  = sanitize_text_field( $f['label']['fr'] ?? ( $f['label_fr'] ?? '' ) );
			if ( '' === $key || '' === $fr || isset( $keys[ $key ] ) ) {
				continue;
			}
			$keys[ $key ] = true;
			$type         = in_array( $f['type'] ?? '', array( 'text', 'number', 'select' ), true ) ? $f['type'] : 'text';
			$field        = array(
				'key'      => $key,
				'type'     => $type,
				'label'    => array( 'fr' => $fr, 'ar' => sanitize_text_field( $f['label']['ar'] ?? ( $f['label_ar'] ?? '' ) ) ),
				'required' => ! empty( $f['required'] ),
			);
			if ( ! empty( $f['unit'] ) ) {
				$field['unit'] = sanitize_text_field( $f['unit'] );
			}
			if ( 'select' === $type ) {
				$opts = array();
				foreach ( (array) ( $f['options'] ?? array() ) as $o ) {
					if ( is_string( $o ) ) { // "value|Français|عربي" typed in the admin.
						$parts = array_map( 'trim', explode( '|', $o ) );
						$o     = array( 'value' => $parts[0] ?? '', 'fr' => $parts[1] ?? ( $parts[0] ?? '' ), 'ar' => $parts[2] ?? '' );
					}
					$v = sanitize_key( $o['value'] ?? '' );
					if ( '' !== $v ) {
						$opts[] = array( 'value' => $v, 'fr' => sanitize_text_field( $o['fr'] ?? $v ), 'ar' => sanitize_text_field( $o['ar'] ?? '' ) );
					}
				}
				if ( ! $opts ) {
					continue;
				}
				$field['options'] = $opts;
			}
			$out[] = $field;
		}
		return $out;
	}

	public static function flush() {
		wp_cache_delete( 'product_categories', 'sm' );
	}
}
