<?php
/**
 * Proofs of payment. They show bank details, so they are not media files: they
 * are stored in the private folder of sm-core (outside the web root when the
 * host allows it, otherwise uploads/sm-private with deny-all rules), under
 * payments/, with random names, and served only to the platform team and to the
 * owner or managers of the account (admin-post.php?action=smsub_proof).
 *
 * @package SM\Subscriptions
 */

namespace SM\Subscriptions;

use SM\Core\Accounts\Companies;
use SM\Core\Audit\AuditLog;
use SM\Core\Verification\Verifications;

defined( 'ABSPATH' ) || exit;

final class Proofs {

	const MAX_BYTES = 10485760; // 10 MB.

	public static function init() {
		add_action( 'admin_post_smsub_proof', array( __CLASS__, 'serve' ) );
	}

	private static function types() {
		return array(
			'pdf'      => 'application/pdf',
			'jpg|jpeg' => 'image/jpeg',
			'png'      => 'image/png',
			'webp'     => 'image/webp',
		);
	}

	private static function dir() {
		return untrailingslashit( Verifications::private_dir() ) . '/payments';
	}

	/**
	 * Store the uploaded file of field $field (name="proof[]" or name="proof").
	 *
	 * @return array|\WP_Error { path, type, original }
	 */
	public static function store( $field ) {
		$f = $_FILES[ $field ] ?? null; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		if ( $f && is_array( $f['name'] ) ) {
			$f = array( 'name' => $f['name'][0] ?? '', 'tmp_name' => $f['tmp_name'][0] ?? '', 'error' => $f['error'][0] ?? UPLOAD_ERR_NO_FILE, 'size' => $f['size'][0] ?? 0 );
		}
		if ( ! $f || UPLOAD_ERR_NO_FILE === (int) $f['error'] || '' === (string) $f['tmp_name'] ) {
			return new \WP_Error( 'none', __( 'Add the receipt, the transfer slip or a screenshot of the payment.', 'sm-subscriptions' ) );
		}
		$bad = __( 'The proof must be a PDF, JPG, PNG or WebP file under 10 MB.', 'sm-subscriptions' );
		if ( UPLOAD_ERR_OK !== (int) $f['error'] || (int) $f['size'] > self::MAX_BYTES || ! is_uploaded_file( $f['tmp_name'] ) ) {
			return new \WP_Error( 'bad', $bad );
		}
		$check = wp_check_filetype_and_ext( $f['tmp_name'], $f['name'], self::types() );
		if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
			return new \WP_Error( 'bad', $bad );
		}
		$dir = self::dir();
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		$name = 'proof-' . gmdate( 'Ymd' ) . '-' . wp_generate_password( 12, false ) . '.' . $check['ext'];
		if ( ! move_uploaded_file( $f['tmp_name'], $dir . '/' . $name ) ) { // phpcs:ignore Generic.PHP.ForbiddenFunctions
			return new \WP_Error( 'save', __( 'The file could not be saved. Try again.', 'sm-subscriptions' ) );
		}
		return array( 'path' => 'payments/' . $name, 'type' => $check['type'], 'original' => sanitize_file_name( $f['name'] ) );
	}

	/** Stored file of an order, or null. */
	public static function file( $order ) {
		$file = json_decode( (string) ( $order->proof_file ?? '' ), true );
		return is_array( $file ) && ! empty( $file['path'] ) ? $file : null;
	}

	/** Link to open the proof (nonce bound to the order), or ''. */
	public static function url( $order ) {
		if ( ! self::file( $order ) ) {
			return '';
		}
		return wp_nonce_url( admin_url( 'admin-post.php?action=smsub_proof&order=' . (int) $order->id ), 'smsub_proof_' . (int) $order->id );
	}

	public static function can_view( $order, $user_id ) {
		return user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'sm_moderate' ) || ( $user_id && Companies::can_manage( (int) $order->company_id, $user_id ) );
	}

	public static function serve() {
		$id = absint( $_GET['order'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'smsub_proof_' . $id ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_die( esc_html__( 'Link expired.', 'sm-subscriptions' ), 403 );
		}
		$order = Orders::get( $id );
		$file  = $order ? self::file( $order ) : null;
		if ( ! $file ) {
			wp_die( esc_html__( 'Document not found.', 'sm-subscriptions' ), 404 );
		}
		if ( ! self::can_view( $order, get_current_user_id() ) ) {
			wp_die( esc_html__( 'You are not allowed to view this document.', 'sm-subscriptions' ), 403 );
		}
		$base = realpath( self::dir() );
		$real = realpath( untrailingslashit( Verifications::private_dir() ) . '/' . $file['path'] );
		if ( ! $base || ! $real || 0 !== strpos( $real, $base . DIRECTORY_SEPARATOR ) || ! is_readable( $real ) ) {
			wp_die( esc_html__( 'Document not found.', 'sm-subscriptions' ), 404 );
		}
		AuditLog::add( 'subscription.proof_viewed', 'sub_order', $id, array(), get_current_user_id(), (int) $order->company_id );
		nocache_headers();
		header( 'Content-Type: ' . $file['type'] );
		header( 'Content-Disposition: inline; filename="' . sanitize_file_name( $file['original'] ) . '"' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Length: ' . filesize( $real ) );
		readfile( $real ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
}
