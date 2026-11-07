<?php
/**
 * Photos and documents of marketplace records ({prefix}sm_media_links), and
 * the upload helper used by the market plugins.
 *
 * @package SM\Core
 */

namespace SM\Core\Content;

defined( 'ABSPATH' ) || exit;

final class Media {

	const IMAGE_TYPES = array( 'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' );
	const PDF_TYPES   = array( 'pdf' => 'application/pdf' );

	/** Replace the attachments of a role, in order. */
	public static function set( $entity_type, $entity_id, $role, array $attachment_ids ) {
		global $wpdb;
		$table = sm_table( 'media_links' );
		$wpdb->delete( $table, array( 'entity_type' => $entity_type, 'entity_id' => (int) $entity_id, 'role' => $role ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		foreach ( array_values( array_unique( array_filter( array_map( 'intval', $attachment_ids ) ) ) ) as $i => $aid ) {
			$wpdb->insert( $table, array( 'entity_type' => $entity_type, 'entity_id' => (int) $entity_id, 'attachment_id' => $aid, 'role' => $role, 'sort' => $i ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}
	}

	/** @return int[] attachment IDs of a role, in order. */
	public static function get( $entity_type, $entity_id, $role ) {
		global $wpdb;
		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( 'SELECT attachment_id FROM ' . sm_table( 'media_links' ) . ' WHERE entity_type = %s AND entity_id = %d AND role = %s ORDER BY sort', $entity_type, $entity_id, $role ) ) ); // phpcs:ignore WordPress.DB
	}

	/** First photo of many records: [ entity_id => attachment_id ]. */
	public static function first_many( $entity_type, array $ids, $role = 'photo' ) {
		global $wpdb;
		$ids = array_filter( array_map( 'intval', $ids ) );
		if ( ! $ids ) {
			return array();
		}
		$in   = implode( ',', $ids );
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT entity_id, attachment_id FROM ' . sm_table( 'media_links' ) . " WHERE entity_type = %s AND role = %s AND entity_id IN ($in) ORDER BY sort DESC", $entity_type, $role ) ); // phpcs:ignore WordPress.DB
		$out  = array();
		foreach ( $rows as $r ) {
			$out[ (int) $r->entity_id ] = (int) $r->attachment_id;
		}
		return $out;
	}

	/**
	 * Store uploaded files of one form field in the media library.
	 *
	 * @param string $field     $_FILES key (multiple allowed).
	 * @param array  $mimes     Allowed types (IMAGE_TYPES or PDF_TYPES).
	 * @param int    $max       Most files kept.
	 * @param int    $max_bytes Largest file.
	 * @return array{ids: int[], errors: string[]}
	 */
	public static function upload( $field, array $mimes, $max, $max_bytes ) {
		$out = array( 'ids' => array(), 'errors' => array() );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked by the calling form handler.
		if ( empty( $_FILES[ $field ] ) || ! is_array( $_FILES[ $field ]['name'] ) ) {
			return $out;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		$files = $_FILES[ $field ]; // phpcs:ignore WordPress.Security
		foreach ( $files['name'] as $i => $name ) {
			if ( UPLOAD_ERR_NO_FILE === (int) $files['error'][ $i ] ) {
				continue;
			}
			if ( count( $out['ids'] ) >= $max ) {
				/* translators: %d: maximum number of files. */
				$out['errors'][] = sprintf( __( 'Only the first %d files were kept.', 'sm-core' ), $max );
				break;
			}
			if ( UPLOAD_ERR_OK !== (int) $files['error'][ $i ] || (int) $files['size'][ $i ] > $max_bytes ) {
				/* translators: 1: file name, 2: size limit in MB. */
				$out['errors'][] = sprintf( __( '%1$s was not added: files must be smaller than %2$s MB.', 'sm-core' ), sanitize_file_name( $name ), number_format( $max_bytes / 1048576, 0 ) );
				continue;
			}
			$check = wp_check_filetype_and_ext( $files['tmp_name'][ $i ], $name, $mimes );
			if ( empty( $check['type'] ) ) {
				/* translators: %s: file name. */
				$out['errors'][] = sprintf( __( '%s was not added: this file type is not accepted.', 'sm-core' ), sanitize_file_name( $name ) );
				continue;
			}
			$single = array(
				'name'     => sanitize_file_name( $name ),
				'type'     => $check['type'],
				'tmp_name' => $files['tmp_name'][ $i ],
				'error'    => 0,
				'size'     => $files['size'][ $i ],
			);
			$_FILES[ 'sm_single_upload' ] = $single;
			$id = media_handle_upload( 'sm_single_upload', 0, array(), array( 'test_form' => false, 'mimes' => $mimes ) );
			unset( $_FILES['sm_single_upload'] );
			if ( is_wp_error( $id ) ) {
				$out['errors'][] = sanitize_file_name( $name ) . ' : ' . $id->get_error_message();
				continue;
			}
			$out['ids'][] = (int) $id;
		}
		return $out;
	}
}
