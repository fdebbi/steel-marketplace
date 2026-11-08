<?php
/**
 * Files sent to the AI with a request: photos (JPEG, PNG, WebP, GIF), resized
 * to 1568 px at most to save tokens, and PDF documents. Read from the upload,
 * checked by content type, never stored.
 *
 * @package SM\Ai
 */

namespace SM\Ai;

defined( 'ABSPATH' ) || exit;

final class Files {

	const IMAGE_TYPES = array( 'image/jpeg', 'image/png', 'image/webp', 'image/gif' );
	const MAX_SIDE    = 1568;

	/**
	 * Parts from an upload field ($_FILES[$field], single or multiple).
	 *
	 * @param string $field   Field name.
	 * @param bool   $pdf     Accept PDF.
	 * @param int    $max     Maximum number of files.
	 * @return array|\WP_Error List of parts.
	 */
	public static function read( $field, $pdf = false, $max = 3 ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the form handler checked the nonce.
		$f = $_FILES[ $field ] ?? null;
		if ( ! $f || empty( $f['name'] ) ) {
			return array();
		}
		$list = array();
		if ( is_array( $f['name'] ) ) {
			foreach ( array_keys( $f['name'] ) as $i ) {
				if ( '' !== (string) $f['name'][ $i ] ) {
					$list[] = array( 'name' => $f['name'][ $i ], 'tmp_name' => $f['tmp_name'][ $i ], 'error' => $f['error'][ $i ], 'size' => $f['size'][ $i ] );
				}
			}
		} else {
			$list[] = $f;
		}
		if ( count( $list ) > $max ) {
			/* translators: %d: number of files. */
			return new \WP_Error( 'sm_ai_files', sprintf( _n( 'Send %d file at most.', 'Send %d files at most.', $max, 'sm-ai' ), $max ) );
		}
		$parts = array();
		foreach ( $list as $file ) {
			$part = self::part( $file, $pdf );
			if ( is_wp_error( $part ) ) {
				return $part;
			}
			$parts[] = $part;
		}
		return $parts;
	}

	public static function part( array $file, $pdf ) {
		if ( UPLOAD_ERR_OK !== (int) $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) && ! ( defined( 'SM_AI_TESTING' ) && SM_AI_TESTING && is_readable( $file['tmp_name'] ) ) ) {
			return new \WP_Error( 'sm_ai_upload', __( 'The file could not be received. Try again.', 'sm-ai' ) );
		}
		$mime = self::mime( $file['tmp_name'] );
		$s    = Settings::all();
		if ( 'application/pdf' === $mime ) {
			if ( ! $pdf ) {
				return new \WP_Error( 'sm_ai_type', __( 'Send photos (JPG, PNG, WebP).', 'sm-ai' ) );
			}
			if ( (int) $file['size'] > $s['pdf_mb'] * MB_IN_BYTES ) {
				/* translators: %d: megabytes. */
				return new \WP_Error( 'sm_ai_size', sprintf( __( 'The document is too large (%d MB at most).', 'sm-ai' ), $s['pdf_mb'] ) );
			}
			return array( 'type' => 'pdf', 'name' => sanitize_file_name( (string) $file['name'] ), 'data' => base64_encode( (string) file_get_contents( $file['tmp_name'] ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions, WordPress.PHP.DiscouragedPHPFunctions
		}
		if ( ! in_array( $mime, self::IMAGE_TYPES, true ) ) {
			return new \WP_Error( 'sm_ai_type', $pdf ? __( 'Send a PDF document or a photo (JPG, PNG, WebP).', 'sm-ai' ) : __( 'Send photos (JPG, PNG, WebP).', 'sm-ai' ) );
		}
		if ( (int) $file['size'] > $s['image_mb'] * MB_IN_BYTES ) {
			/* translators: %d: megabytes. */
			return new \WP_Error( 'sm_ai_size', sprintf( __( 'A photo is too large (%d MB at most).', 'sm-ai' ), $s['image_mb'] ) );
		}
		return self::image( $file['tmp_name'], $mime );
	}

	/** Real content type (not the name or what the browser says). */
	public static function mime( $path ) {
		$head = (string) file_get_contents( $path, false, null, 0, 12 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( 0 === strpos( $head, '%PDF' ) ) {
			return 'application/pdf';
		}
		$info = @getimagesize( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		return $info ? (string) $info['mime'] : '';
	}

	/** Image part, resized when larger than MAX_SIDE. */
	public static function image( $path, $mime ) {
		$data = null;
		$info = @getimagesize( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( $info && max( $info[0], $info[1] ) > self::MAX_SIDE ) {
			$ed = wp_get_image_editor( $path );
			if ( ! is_wp_error( $ed ) ) {
				$ed->resize( self::MAX_SIDE, self::MAX_SIDE, false );
				$ed->set_quality( 85 );
				$tmp   = wp_tempnam( 'sm-ai' ) . '.jpg';
				$saved = $ed->save( $tmp, 'image/jpeg' );
				if ( ! is_wp_error( $saved ) ) {
					$data = (string) file_get_contents( $saved['path'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions
					$mime = 'image/jpeg';
					wp_delete_file( $saved['path'] );
				}
				wp_delete_file( $tmp );
			}
		}
		$data = $data ?? (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		return array( 'type' => 'image', 'mime' => $mime, 'data' => base64_encode( $data ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	}
}
