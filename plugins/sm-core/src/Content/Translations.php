<?php
/**
 * Translatable texts of marketplace records (titles, descriptions) in
 * {prefix}sm_translations: one row per entity, field and language. Technical
 * fields stay in the records themselves and are never translated.
 *
 * @package SM\Core
 */

namespace SM\Core\Content;

defined( 'ABSPATH' ) || exit;

final class Translations {

	/** Save texts: $values = [ field => [ 'fr' => '…', 'ar' => '…' ] ]. Empty values are removed. */
	public static function set( $entity_type, $entity_id, array $values, $source = 'author' ) {
		global $wpdb;
		$table = sm_table( 'translations' );
		foreach ( $values as $field => $langs ) {
			foreach ( (array) $langs as $lang => $text ) {
				$text = trim( (string) $text );
				$where = array( 'entity_type' => $entity_type, 'entity_id' => (int) $entity_id, 'field' => $field, 'locale' => $lang );
				if ( '' === $text ) {
					$wpdb->delete( $table, $where ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					continue;
				}
				$id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE entity_type = %s AND entity_id = %d AND field = %s AND locale = %s", $entity_type, $entity_id, $field, $lang ) ); // phpcs:ignore WordPress.DB
				$row = array( 'value' => $text, 'source' => $source, 'updated_at' => current_time( 'mysql', true ) );
				if ( $id ) {
					$wpdb->update( $table, $row, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				} else {
					$wpdb->insert( $table, $row + $where ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				}
			}
		}
	}

	/** @return array [ field => [ lang => text ] ] */
	public static function get( $entity_type, $entity_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT field, locale, value FROM ' . sm_table( 'translations' ) . ' WHERE entity_type = %s AND entity_id = %d', $entity_type, $entity_id ) ); // phpcs:ignore WordPress.DB
		$out  = array();
		foreach ( $rows as $r ) {
			$out[ $r->field ][ $r->locale ] = $r->value;
		}
		return $out;
	}

	/** Bulk read for lists: [ entity_id => [ field => [ lang => text ] ] ]. */
	public static function get_many( $entity_type, array $ids ) {
		global $wpdb;
		$ids = array_filter( array_map( 'intval', $ids ) );
		if ( ! $ids ) {
			return array();
		}
		$in   = implode( ',', $ids );
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT entity_id, field, locale, value FROM ' . sm_table( 'translations' ) . " WHERE entity_type = %s AND entity_id IN ($in)", $entity_type ) ); // phpcs:ignore WordPress.DB
		$out  = array();
		foreach ( $rows as $r ) {
			$out[ (int) $r->entity_id ][ $r->field ][ $r->locale ] = $r->value;
		}
		return $out;
	}

	public static function delete( $entity_type, $entity_id ) {
		global $wpdb;
		$wpdb->delete( sm_table( 'translations' ), array( 'entity_type' => $entity_type, 'entity_id' => (int) $entity_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
}
