<?php
/**
 * Private files of missions: the client's brief (plans, photos, notes), the
 * engineer's deliverables, the platform's documents and the engineer's fee
 * invoices. Stored in SM Core's private folder (outside the web root when the
 * host allows it) and streamed only to the people of the mission:
 *
 *   brief        client → platform and the assigned engineer
 *   deliverable  engineer → platform; the client sees it once the platform delivers
 *   platform     platform → client and/or engineer (chosen when uploading)
 *   invoice      engineer → platform only (fee invoice)
 *
 * @package SM\Engineering
 */

namespace SM\Engineering;

use SM\Core\Accounts\Companies;
use SM\Core\Audit\AuditLog;
use SM\Core\Verification\Verifications;

defined( 'ABSPATH' ) || exit;

final class Files {

	const KINDS = array( 'brief', 'deliverable', 'platform', 'invoice' );

	public static function init() {
		add_action( 'admin_post_sm_mission_file', array( __CLASS__, 'serve' ) );
		add_action( 'admin_post_nopriv_sm_mission_file', array( __CLASS__, 'serve' ) ); // Visitors get a clean 403.
	}

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sm_mission_files';
	}

	private static function mime_of( $ext ) {
		$map = array(
			'pdf'  => 'application/pdf',
			'jpg'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png'  => 'image/png',
			'webp' => 'image/webp',
			'zip'  => 'application/zip',
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'xls'  => 'application/vnd.ms-excel',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'doc'  => 'application/msword',
		);
		return $map[ $ext ] ?? 'application/octet-stream';
	}

	/** Whether the content matches the extension, for the types that can be checked. */
	private static function content_ok( $tmp, $ext ) {
		if ( 'pdf' === $ext ) {
			return '%PDF' === (string) file_get_contents( $tmp, false, null, 0, 4 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		if ( in_array( $ext, array( 'jpg', 'jpeg', 'png', 'webp' ), true ) ) {
			$info = @getimagesize( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			return $info && in_array( $info['mime'], array( 'image/jpeg', 'image/png', 'image/webp' ), true );
		}
		if ( in_array( $ext, array( 'zip', 'xlsx', 'docx' ), true ) ) {
			return 'PK' === (string) file_get_contents( $tmp, false, null, 0, 2 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		return true;
	}

	/** Files posted in a field (single or multiple), normalised. */
	private static function posted( $field ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the caller verified the form.
		$f = $_FILES[ $field ] ?? null;
		if ( ! $f || ! isset( $f['name'] ) ) {
			return array();
		}
		if ( ! is_array( $f['name'] ) ) {
			$f = array_map( static fn( $v ) => array( $v ), $f );
		}
		$out = array();
		foreach ( array_keys( $f['name'] ) as $i ) {
			if ( UPLOAD_ERR_NO_FILE === (int) $f['error'][ $i ] ) {
				continue;
			}
			$out[] = array(
				'name'     => (string) $f['name'][ $i ],
				'tmp_name' => (string) $f['tmp_name'][ $i ],
				'error'    => (int) $f['error'][ $i ],
				'size'     => (int) $f['size'][ $i ],
			);
		}
		return $out;
	}

	/**
	 * Move the posted files to the private folder.
	 *
	 * @param string   $field  Form field.
	 * @param string   $subdir Folder inside the private folder.
	 * @param int      $max    Files allowed.
	 * @param string[] $exts   Allowed extensions (default: settings).
	 * @return array{files: array, errors: string[]} Files: path (relative), original, mime, size.
	 */
	public static function store( $field, $subdir, $max, array $exts = array() ) {
		$exts   = $exts ? $exts : Settings::extensions();
		$limit  = (int) Settings::get( 'file_mb' ) * 1048576;
		$files  = array();
		$errors = array();
		$posted = self::posted( $field );
		if ( count( $posted ) > $max ) {
			/* translators: %d: number of files. */
			$errors[] = sprintf( _n( 'Send at most %d file.', 'Send at most %d files.', $max, 'sm-engineering' ), $max );
			$posted   = array_slice( $posted, 0, $max );
		}
		$base = Verifications::private_dir();
		$dir  = $base . '/' . $subdir;
		foreach ( $posted as $file ) {
			$name = sanitize_file_name( $file['name'] );
			$ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
			if ( UPLOAD_ERR_OK !== $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ) {
				/* translators: %s: file name. */
				$errors[] = sprintf( __( '%s could not be received. Try again.', 'sm-engineering' ), $name );
				continue;
			}
			if ( $file['size'] > $limit ) {
				/* translators: 1: file name, 2: size in MB. */
				$errors[] = sprintf( __( '%1$s is too large (%2$d MB at most).', 'sm-engineering' ), $name, (int) Settings::get( 'file_mb' ) );
				continue;
			}
			if ( ! in_array( $ext, $exts, true ) || ! self::content_ok( $file['tmp_name'], $ext ) ) {
				/* translators: 1: file name, 2: list of extensions. */
				$errors[] = sprintf( __( '%1$s: this type of file is not accepted (%2$s).', 'sm-engineering' ), $name, implode( ', ', $exts ) );
				continue;
			}
			if ( ! is_dir( $dir ) ) {
				wp_mkdir_p( $dir );
			}
			$stored = wp_generate_password( 16, false ) . '.' . $ext;
			if ( ! move_uploaded_file( $file['tmp_name'], $dir . '/' . $stored ) ) { // phpcs:ignore Generic.PHP.ForbiddenFunctions
				/* translators: %s: file name. */
				$errors[] = sprintf( __( '%s could not be saved. Try again.', 'sm-engineering' ), $name );
				continue;
			}
			$files[] = array(
				'path'     => $subdir . '/' . $stored,
				'original' => mb_substr( $name, 0, 190 ),
				'mime'     => self::mime_of( $ext ),
				'size'     => (int) $file['size'],
			);
		}
		return array( 'files' => $files, 'errors' => $errors );
	}

	/** Upload files of a mission and record them. */
	public static function upload( $mission_id, $field, $kind, $to_client, $to_engineer ) {
		$r = self::store( $field, 'missions/' . (int) $mission_id, (int) Settings::get( 'max_files' ) );
		foreach ( $r['files'] as $f ) {
			self::add( $mission_id, $kind, $f, $to_client, $to_engineer );
		}
		return $r;
	}

	public static function add( $mission_id, $kind, array $f, $to_client, $to_engineer, $user_id = null ) {
		global $wpdb;
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'mission_id'  => (int) $mission_id,
				'kind'        => $kind,
				'user_id'     => (int) ( $user_id ?? get_current_user_id() ),
				'path'        => $f['path'],
				'original'    => $f['original'],
				'mime'        => $f['mime'],
				'size'        => (int) $f['size'],
				'to_client'   => $to_client ? 1 : 0,
				'to_engineer' => $to_engineer ? 1 : 0,
				'created_at'  => current_time( 'mysql', true ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Files of a mission seen by someone.
	 *
	 * @param string $viewer admin|client|engineer.
	 */
	public static function for_mission( $mission_id, $viewer = 'admin', $kind = '' ) {
		global $wpdb;
		$where = 'mission_id = %d';
		$args  = array( (int) $mission_id );
		if ( 'client' === $viewer ) {
			$where .= ' AND to_client = 1';
		} elseif ( 'engineer' === $viewer ) {
			$where .= ' AND to_engineer = 1';
		}
		if ( $kind ) {
			$where .= ' AND kind = %s';
			$args[] = $kind;
		}
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . " WHERE {$where} ORDER BY id ASC", $args ) ); // phpcs:ignore WordPress.DB
	}

	/** Files of all the missions of an engineer (the "files and plans" view). */
	public static function for_engineer( $engineer_id ) {
		global $wpdb;
		$m = Missions::table();
		return $wpdb->get_results( $wpdb->prepare( 'SELECT f.* FROM ' . self::table() . " f JOIN {$m} m ON m.id = f.mission_id WHERE m.engineer_id = %d AND f.to_engineer = 1 AND m.status NOT IN ('assigned') ORDER BY f.mission_id DESC, f.id ASC LIMIT 500", $engineer_id ) ); // phpcs:ignore WordPress.DB
	}

	public static function set_visibility( $mission_id, $kind, $to_client ) {
		global $wpdb;
		$wpdb->update( self::table(), array( 'to_client' => $to_client ? 1 : 0 ), array( 'mission_id' => (int) $mission_id, 'kind' => $kind ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	public static function delete( $id ) {
		global $wpdb;
		$f = self::get( $id );
		if ( ! $f ) {
			return;
		}
		$path = Verifications::private_dir() . '/' . $f->path;
		if ( is_file( $path ) ) {
			wp_delete_file( $path );
		}
		$wpdb->delete( self::table(), array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/** Whether the current user may download a file. */
	public static function can_view( $f, $user_id = null ) {
		$user_id = $user_id ?? get_current_user_id();
		if ( ! $f || ! $user_id ) {
			return false;
		}
		if ( user_can( $user_id, 'sm_moderate' ) ) {
			return true;
		}
		$m = Missions::get( (int) $f->mission_id );
		if ( ! $m ) {
			return false;
		}
		if ( $f->to_client && Companies::member_role( $m->client_id, $user_id ) ) {
			return true;
		}
		return $f->to_engineer && $m->engineer_id && Companies::member_role( $m->engineer_id, $user_id );
	}

	public static function url( $id ) {
		return wp_nonce_url( add_query_arg( array( 'action' => 'sm_mission_file', 'file' => (int) $id ), admin_url( 'admin-post.php' ) ), 'sm_mission_file_' . (int) $id );
	}

	/** File as shown in lists. */
	public static function shape( $f ) {
		return array(
			'id'    => (int) $f->id,
			'name'  => $f->original,
			'kind'  => $f->kind,
			'size'  => size_format( (int) $f->size, 1 ),
			'date'  => $f->created_at,
			'url'   => self::url( $f->id ),
			'image' => 0 === strpos( $f->mime, 'image/' ),
		);
	}

	public static function serve() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$id    = isset( $_GET['file'] ) ? absint( $_GET['file'] ) : 0;
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		// phpcs:enable
		if ( ! wp_verify_nonce( $nonce, 'sm_mission_file_' . $id ) ) {
			wp_die( esc_html__( 'Link expired. Open the mission again.', 'sm-engineering' ), 403 );
		}
		$f = self::get( $id );
		if ( ! self::can_view( $f ) ) {
			wp_die( esc_html__( 'You are not allowed to open this file.', 'sm-engineering' ), 403 );
		}
		$base = realpath( Verifications::private_dir() );
		$real = realpath( Verifications::private_dir() . '/' . $f->path );
		if ( ! $real || ! $base || 0 !== strpos( $real, $base ) || ! is_readable( $real ) ) {
			wp_die( esc_html__( 'File not found.', 'sm-engineering' ), 404 );
		}
		AuditLog::add( 'mission.file_downloaded', 'mission', (int) $f->mission_id, array( 'file' => (int) $f->id ) );
		$inline = in_array( $f->mime, array( 'application/pdf', 'image/jpeg', 'image/png', 'image/webp' ), true );
		nocache_headers();
		header( 'Content-Type: ' . $f->mime );
		header( 'Content-Disposition: ' . ( $inline ? 'inline' : 'attachment' ) . '; filename="' . str_replace( '"', '', $f->original ) . '"' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Length: ' . filesize( $real ) );
		readfile( $real ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
}
