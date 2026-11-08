<?php
/**
 * References (portfolio) of a company: project title and description (FR/AR),
 * year, wilaya, role, photo.
 *
 * @package SM\Services
 */

namespace SM\Services;

use SM\Core\Content\Media;
use SM\Core\Content\Translations;

defined( 'ABSPATH' ) || exit;

final class Portfolio {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sm_portfolio';
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) ); // phpcs:ignore WordPress.DB
	}

	/** @return array[] Items with texts and photo. */
	public static function for_company( $company_id ) {
		global $wpdb;
		$rows  = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE company_id = %d ORDER BY year DESC, id DESC', $company_id ) ); // phpcs:ignore WordPress.DB
		$texts = Translations::get_many( 'portfolio', wp_list_pluck( $rows, 'id' ) );
		$out   = array();
		foreach ( $rows as $r ) {
			$t      = $texts[ $r->id ] ?? array();
			$photos = Media::get( 'portfolio', $r->id, 'photo' );
			$out[]  = array(
				'id'          => (int) $r->id,
				'title'       => array_filter( (array) ( $t['title'] ?? array() ) ),
				'description' => array_filter( (array) ( $t['description'] ?? array() ) ),
				'role'        => array_filter( (array) ( $t['role'] ?? array() ) ),
				'year'        => (int) $r->year,
				'wilaya'      => $r->wilaya,
				'image'       => $photos ? (int) $photos[0] : 0,
			);
		}
		return $out;
	}

	public static function save( $id, $company_id, array $d, $photo = null ) {
		global $wpdb;
		$row = array( 'year' => $d['year'], 'wilaya' => $d['wilaya'] );
		if ( $id ) {
			$wpdb->update( self::table(), $row, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		} else {
			$wpdb->insert( self::table(), $row + array( 'company_id' => (int) $company_id, 'created_at' => current_time( 'mysql', true ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$id = (int) $wpdb->insert_id;
		}
		Translations::set( 'portfolio', $id, $d['texts'] );
		if ( null !== $photo ) {
			Media::set( 'portfolio', $id, 'photo', $photo ? array( $photo ) : array() );
		}
		return (int) $id;
	}

	public static function delete( $id ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		Translations::delete( 'portfolio', $id );
		Media::set( 'portfolio', $id, 'photo', array() );
	}

	public static function count( $company_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE company_id = %d', $company_id ) ); // phpcs:ignore WordPress.DB
	}
}
