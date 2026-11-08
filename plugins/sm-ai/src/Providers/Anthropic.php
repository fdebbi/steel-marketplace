<?php
/**
 * Anthropic Messages API (Claude): text, images and PDF documents.
 *
 * @package SM\Ai
 */

namespace SM\Ai\Providers;

use SM\Ai\Settings;

defined( 'ABSPATH' ) || exit;

final class Anthropic extends Provider {

	const URL     = 'https://api.anthropic.com/v1/messages';
	const VERSION = '2023-06-01';

	/** Request body (public for the tests). */
	public static function body( array $req ) {
		$content = array();
		foreach ( (array) $req['parts'] as $p ) {
			if ( 'image' === $p['type'] ) {
				$content[] = array( 'type' => 'image', 'source' => array( 'type' => 'base64', 'media_type' => $p['mime'], 'data' => $p['data'] ) );
			} elseif ( 'pdf' === $p['type'] ) {
				$content[] = array( 'type' => 'document', 'source' => array( 'type' => 'base64', 'media_type' => 'application/pdf', 'data' => $p['data'] ) );
			} else {
				$content[] = array( 'type' => 'text', 'text' => (string) $p['text'] );
			}
		}
		return array(
			'model'      => $req['model'],
			'max_tokens' => (int) $req['max_tokens'],
			'system'     => (string) $req['system'],
			'messages'   => array( array( 'role' => 'user', 'content' => $content ) ),
		);
	}

	public static function complete( array $req ) {
		$key = Settings::api_key( 'anthropic' );
		if ( '' === $key ) {
			return self::result( false, '', 0, 0, 'no_key' );
		}
		list( $code, $json, $err ) = self::post( self::URL, array( 'x-api-key' => $key, 'anthropic-version' => self::VERSION ), self::body( $req ) );
		if ( '' !== $err ) {
			return self::result( false, '', 0, 0, $err );
		}
		if ( $code < 200 || $code >= 300 || ! $json ) {
			return self::result( false, '', 0, 0, (string) ( $json['error']['message'] ?? ( 'HTTP ' . $code ) ), $code );
		}
		$text = '';
		foreach ( (array) ( $json['content'] ?? array() ) as $block ) {
			if ( 'text' === ( $block['type'] ?? '' ) ) {
				$text .= (string) $block['text'];
			}
		}
		return self::result( true, $text, (int) ( $json['usage']['input_tokens'] ?? 0 ), (int) ( $json['usage']['output_tokens'] ?? 0 ), '', $code );
	}
}
