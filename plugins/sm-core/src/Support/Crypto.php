<?php
/**
 * Symmetric encryption for registration numbers (RC, NIF, NIS).
 *
 * Key: derived from the site's AUTH_KEY/SALT (wp-config.php). Changing those
 * salts makes previously stored numbers unreadable; documented in docs/security.md.
 *
 * @package SM\Core
 */

namespace SM\Core\Support;

defined( 'ABSPATH' ) || exit;

final class Crypto {

	private static function key() {
		return hash( 'sha256', wp_salt( 'auth' ) . 'sm-core-legal', true );
	}

	public static function encrypt( $plain ) {
		if ( function_exists( 'sodium_crypto_secretbox' ) ) {
			$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			return 's1:' . base64_encode( $nonce . sodium_crypto_secretbox( (string) $plain, $nonce, self::key() ) );
		}
		$iv = random_bytes( 16 );
		return 'o1:' . base64_encode( $iv . openssl_encrypt( (string) $plain, 'aes-256-cbc', self::key(), OPENSSL_RAW_DATA, $iv ) );
	}

	public static function decrypt( $stored ) {
		$stored = (string) $stored;
		if ( 0 === strpos( $stored, 's1:' ) && function_exists( 'sodium_crypto_secretbox_open' ) ) {
			$raw   = base64_decode( substr( $stored, 3 ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
			$nonce = substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$plain = sodium_crypto_secretbox_open( substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ), $nonce, self::key() );
			return false === $plain ? '' : $plain;
		}
		if ( 0 === strpos( $stored, 'o1:' ) ) {
			$raw   = base64_decode( substr( $stored, 3 ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
			$plain = openssl_decrypt( substr( $raw, 16 ), 'aes-256-cbc', self::key(), OPENSSL_RAW_DATA, substr( $raw, 0, 16 ) );
			return false === $plain ? '' : $plain;
		}
		return '';
	}
}
