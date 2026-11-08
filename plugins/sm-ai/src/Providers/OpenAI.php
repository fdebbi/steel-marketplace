<?php
/**
 * OpenAI-compatible Chat Completions API (OpenAI and the providers that copy
 * its format: the base URL is a setting). Images as data URLs, PDF as a file part
 * (accepted by OpenAI; other providers may refuse PDF: the error is shown).
 *
 * @package SM\Ai
 */

namespace SM\Ai\Providers;

use SM\Ai\Settings;

defined( 'ABSPATH' ) || exit;

final class OpenAI extends Provider {

	/** Request body (public for the tests). */
	public static function body( array $req ) {
		$content = array();
		foreach ( (array) $req['parts'] as $p ) {
			if ( 'image' === $p['type'] ) {
				$content[] = array( 'type' => 'image_url', 'image_url' => array( 'url' => 'data:' . $p['mime'] . ';base64,' . $p['data'] ) );
			} elseif ( 'pdf' === $p['type'] ) {
				$content[] = array( 'type' => 'file', 'file' => array( 'filename' => (string) ( $p['name'] ?? 'document.pdf' ), 'file_data' => 'data:application/pdf;base64,' . $p['data'] ) );
			} else {
				$content[] = array( 'type' => 'text', 'text' => (string) $p['text'] );
			}
		}
		return array(
			'model'      => $req['model'],
			'max_tokens' => (int) $req['max_tokens'],
			'messages'   => array(
				array( 'role' => 'system', 'content' => (string) $req['system'] ),
				array( 'role' => 'user', 'content' => $content ),
			),
		);
	}

	public static function url() {
		return untrailingslashit( (string) ( Settings::all()['providers']['openai']['base_url'] ?? 'https://api.openai.com/v1' ) ) . '/chat/completions';
	}

	public static function complete( array $req ) {
		$key = Settings::api_key( 'openai' );
		if ( '' === $key ) {
			return self::result( false, '', 0, 0, 'no_key' );
		}
		list( $code, $json, $err ) = self::post( self::url(), array( 'Authorization' => 'Bearer ' . $key ), self::body( $req ) );
		if ( '' !== $err ) {
			return self::result( false, '', 0, 0, $err );
		}
		if ( $code < 200 || $code >= 300 || ! $json ) {
			return self::result( false, '', 0, 0, (string) ( $json['error']['message'] ?? ( 'HTTP ' . $code ) ), $code );
		}
		$msg  = $json['choices'][0]['message']['content'] ?? '';
		$text = is_array( $msg ) ? implode( '', array_map( static fn( $c ) => (string) ( $c['text'] ?? '' ), $msg ) ) : (string) $msg;
		return self::result( true, $text, (int) ( $json['usage']['prompt_tokens'] ?? 0 ), (int) ( $json['usage']['completion_tokens'] ?? 0 ), '', $code );
	}
}
