<?php
/**
 * Common part of the AI providers.
 *
 * Request: system (text), parts (list of [type => text|image|pdf, text | mime + data (base64) | name]),
 * model, max_tokens. Result: ok, text, tokens_in, tokens_out, error, http (status code).
 *
 * @package SM\Ai
 */

namespace SM\Ai\Providers;

use SM\Ai\Settings;

defined( 'ABSPATH' ) || exit;

abstract class Provider {

	/** @return array{ok:bool,text:string,tokens_in:int,tokens_out:int,error:string,http:int} */
	abstract public static function complete( array $req );

	/** Which inputs the provider accepts. */
	public static function accepts( $provider ) {
		return in_array( $provider, array( 'anthropic', 'openai', 'simulation' ), true ) ? array( 'text', 'image', 'pdf' ) : array( 'text' );
	}

	public static function for( $provider ) {
		return array(
			'anthropic'  => Anthropic::class,
			'openai'     => OpenAI::class,
			'simulation' => Simulation::class,
		)[ $provider ] ?? null;
	}

	protected static function result( $ok, $text = '', $in = 0, $out = 0, $error = '', $http = 0 ) {
		return array( 'ok' => (bool) $ok, 'text' => (string) $text, 'tokens_in' => (int) $in, 'tokens_out' => (int) $out, 'error' => (string) $error, 'http' => (int) $http );
	}

	/** POST JSON, decode the answer. Returns [status, decoded array|null, transport error]. */
	protected static function post( $url, array $headers, array $body ) {
		$res = wp_remote_post(
			$url,
			array(
				'timeout' => (int) Settings::get( 'timeout' ),
				'headers' => $headers + array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $body ),
			)
		);
		if ( is_wp_error( $res ) ) {
			return array( 0, null, $res->get_error_message() );
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$json = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		return array( $code, is_array( $json ) ? $json : null, '' );
	}
}
