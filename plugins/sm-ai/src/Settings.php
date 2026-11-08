<?php
/**
 * Settings of the AI services (wp-admin → SM → AI), option sm_ai_settings.
 *
 * API keys are stored encrypted (SM Core Crypto) and never shown again; a key
 * defined in wp-config.php (SM_AI_ANTHROPIC_KEY, SM_AI_OPENAI_KEY) wins over
 * the stored one. Prices are per million tokens, in US dollars, as published
 * by the provider: they only serve the cost estimates and the monthly budget.
 *
 * @package SM\Ai
 */

namespace SM\Ai;

use SM\Core\Support\Crypto;

defined( 'ABSPATH' ) || exit;

final class Settings {

	const OPTION   = 'sm_ai_settings';
	const FEATURES = array( 'listing', 'recognize', 'search', 'document' );

	public static function defaults() {
		return array(
			'default_provider' => '',
			'providers'        => array(
				'anthropic' => array( 'key' => '', 'model' => 'claude-sonnet-5-5', 'price_in' => 0, 'price_out' => 0 ),
				'openai'    => array( 'key' => '', 'model' => '', 'base_url' => 'https://api.openai.com/v1', 'price_in' => 0, 'price_out' => 0 ),
			),
			'features'         => array(
				'listing'   => array( 'enabled' => 1, 'provider' => '', 'model' => '', 'per_company_month' => 30 ),
				'recognize' => array( 'enabled' => 1, 'provider' => '', 'model' => '', 'per_company_month' => 30 ),
				'search'    => array( 'enabled' => 1, 'provider' => '', 'model' => '', 'per_company_month' => 300, 'visitors_per_ip_day' => 10 ),
				'document'  => array( 'enabled' => 1, 'provider' => '', 'model' => '', 'per_company_month' => 10 ),
			),
			'per_user_day'     => 40,
			'monthly_budget'   => 0,
			'max_tokens'       => 1500,
			'image_mb'         => 8,
			'pdf_mb'           => 10,
			'max_images'       => 3,
			'timeout'          => 60,
			'retention'        => 180,
		);
	}

	public static function all() {
		$d = self::defaults();
		$s = (array) get_option( self::OPTION, array() );
		$o = array_merge( $d, $s );
		foreach ( array( 'providers', 'features' ) as $group ) {
			$o[ $group ] = array();
			foreach ( $d[ $group ] as $k => $def ) {
				$o[ $group ][ $k ] = array_merge( $def, (array) ( $s[ $group ][ $k ] ?? array() ) );
			}
		}
		return $o;
	}

	public static function get( $key ) {
		return self::all()[ $key ] ?? null;
	}

	public static function feature( $id ) {
		return self::all()['features'][ $id ] ?? null;
	}

	/** The simulation provider (no external call) is offered only when SM_AI_SIMULATION is true in wp-config.php. */
	public static function simulation_allowed() {
		return defined( 'SM_AI_SIMULATION' ) && SM_AI_SIMULATION;
	}

	/** Providers that can be chosen: id => label. */
	public static function provider_labels() {
		$out = array(
			'anthropic' => 'Anthropic (Claude)',
			'openai'    => __( 'OpenAI-compatible API', 'sm-ai' ),
		);
		if ( self::simulation_allowed() ) {
			$out['simulation'] = __( 'Simulation (tests, no real AI)', 'sm-ai' );
		}
		return $out;
	}

	/** Decrypted API key of a provider ('' when none). */
	public static function api_key( $provider ) {
		$const = 'SM_AI_' . strtoupper( $provider ) . '_KEY';
		if ( defined( $const ) && '' !== (string) constant( $const ) ) {
			return (string) constant( $const );
		}
		$stored = (string) ( self::all()['providers'][ $provider ]['key'] ?? '' );
		return '' !== $stored ? Crypto::decrypt( $stored ) : '';
	}

	public static function key_from_config( $provider ) {
		$const = 'SM_AI_' . strtoupper( $provider ) . '_KEY';
		return defined( $const ) && '' !== (string) constant( $const );
	}

	/** "••••abcd" or ''. */
	public static function key_hint( $provider ) {
		$k = self::api_key( $provider );
		return '' === $k ? '' : '••••' . substr( $k, -4 );
	}

	/** [provider id, model] used by a feature, or [ '', '' ] when nothing is configured. */
	public static function route( $feature ) {
		$s  = self::all();
		$f  = $s['features'][ $feature ] ?? array();
		$p  = (string) ( $f['provider'] ?? '' ) ?: (string) $s['default_provider'];
		if ( 'simulation' === $p ) {
			return self::simulation_allowed() ? array( 'simulation', 'simulation' ) : array( '', '' );
		}
		if ( ! isset( $s['providers'][ $p ] ) || '' === self::api_key( $p ) ) {
			return array( '', '' );
		}
		$model = (string) ( $f['model'] ?? '' ) ?: (string) $s['providers'][ $p ]['model'];
		return '' === $model ? array( '', '' ) : array( $p, $model );
	}

	/** Save from the admin form. Empty key fields keep the stored key; "clear" removes it. */
	public static function save( array $in ) {
		$cur = self::all();
		$d   = self::defaults();
		$out = $cur;
		$labels = self::provider_labels();
		$out['default_provider'] = isset( $labels[ $in['default_provider'] ?? '' ] ) ? $in['default_provider'] : '';
		foreach ( $d['providers'] as $p => $def ) {
			$row = (array) ( $in['providers'][ $p ] ?? array() );
			$key = trim( (string) ( $row['key'] ?? '' ) );
			if ( ! empty( $row['clear'] ) ) {
				$out['providers'][ $p ]['key'] = '';
			} elseif ( '' !== $key && $key !== (string) $cur['providers'][ $p ]['key'] ) { // The stored (encrypted) value sent back is kept as is.
				$out['providers'][ $p ]['key'] = Crypto::encrypt( preg_replace( '/\s+/', '', $key ) );
			}
			$c = $cur['providers'][ $p ];
			$out['providers'][ $p ]['model']     = mb_substr( sanitize_text_field( (string) ( $row['model'] ?? $c['model'] ) ), 0, 80 );
			$out['providers'][ $p ]['price_in']  = max( 0, min( 1000, (float) ( $row['price_in'] ?? $c['price_in'] ) ) );
			$out['providers'][ $p ]['price_out'] = max( 0, min( 1000, (float) ( $row['price_out'] ?? $c['price_out'] ) ) );
			if ( isset( $def['base_url'] ) ) {
				$url = esc_url_raw( trim( (string) ( $row['base_url'] ?? $c['base_url'] ) ), array( 'https' ) );
				$out['providers'][ $p ]['base_url'] = $url ? untrailingslashit( $url ) : $def['base_url'];
			}
		}
		foreach ( self::FEATURES as $f ) {
			$row = (array) ( $in['features'][ $f ] ?? array() );
			$out['features'][ $f ]['enabled']           = empty( $row['enabled'] ) ? 0 : 1;
			$out['features'][ $f ]['provider']          = isset( $labels[ $row['provider'] ?? '' ] ) ? $row['provider'] : '';
			$out['features'][ $f ]['model']             = mb_substr( sanitize_text_field( (string) ( $row['model'] ?? '' ) ), 0, 80 );
			$out['features'][ $f ]['per_company_month'] = max( 0, min( 100000, (int) ( $row['per_company_month'] ?? $d['features'][ $f ]['per_company_month'] ) ) );
			if ( isset( $d['features'][ $f ]['visitors_per_ip_day'] ) ) {
				$out['features'][ $f ]['visitors_per_ip_day'] = max( 0, min( 1000, (int) ( $row['visitors_per_ip_day'] ?? 10 ) ) );
			}
		}
		foreach ( array( 'per_user_day' => array( 1, 10000 ), 'max_tokens' => array( 300, 8000 ), 'image_mb' => array( 1, 20 ), 'pdf_mb' => array( 1, 32 ), 'max_images' => array( 1, 5 ), 'timeout' => array( 10, 180 ), 'retention' => array( 30, 3650 ) ) as $k => $r ) {
			$out[ $k ] = max( $r[0], min( $r[1], (int) ( $in[ $k ] ?? $d[ $k ] ) ) );
		}
		$out['monthly_budget'] = max( 0, (float) ( $in['monthly_budget'] ?? 0 ) );
		update_option( self::OPTION, $out, false );
		return $out;
	}
}
