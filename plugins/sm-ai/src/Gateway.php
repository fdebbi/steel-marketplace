<?php
/**
 * Single entry point of the AI services: checks (feature on, provider
 * configured, rights, quotas, monthly budget), call to the provider, usage
 * log, decoding and checking of the answer.
 *
 * Plugins can refuse or allow a call with the sm_ai_can_use filter
 * (true|WP_Error, feature id, company id): Phase 10 plugs the AI credits there.
 *
 * @package SM\Ai
 */

namespace SM\Ai;

use SM\Core\I18n\Language;
use SM\Core\Support\Forms;

defined( 'ABSPATH' ) || exit;

final class Gateway {

	/** Feature classes: id => class. */
	public static function features() {
		return apply_filters(
			'sm_ai_features',
			array(
				'listing'   => Features\Listing::class,
				'recognize' => Features\Recognize::class,
				'search'    => Features\Search::class,
				'document'  => Features\Document::class,
			)
		);
	}

	public static function feature( $id ) {
		return self::features()[ $id ] ?? null;
	}

	/**
	 * Can the current visitor use a feature now? true or WP_Error (message for the user).
	 * Does not count anything.
	 */
	public static function check( $id, $company = null ) {
		$class = self::feature( $id );
		$f     = Settings::feature( $id );
		if ( ! $class || ! $f || empty( $f['enabled'] ) ) {
			return new \WP_Error( 'sm_ai_off', __( 'This AI service is not available.', 'sm-ai' ) );
		}
		list( $provider ) = Settings::route( $id );
		if ( '' === $provider ) {
			return new \WP_Error( 'sm_ai_config', __( 'The AI assistant is not configured yet. Try again later.', 'sm-ai' ) );
		}
		$company = $company ?? sm_current_company();
		if ( ! is_user_logged_in() && ! $class::public_feature() ) {
			return new \WP_Error( 'sm_ai_account', __( 'Sign in to use the AI assistant.', 'sm-ai' ) );
		}
		$ok = $class::allowed( $company );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}
		$ok = apply_filters( 'sm_ai_can_use', true, $id, $company ? (int) $company->id : 0 );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}
		$s = Settings::all();
		if ( $s['monthly_budget'] > 0 && Usage::month_cost() >= $s['monthly_budget'] ) {
			return new \WP_Error( 'sm_ai_budget', __( 'The AI assistant has reached its monthly limit. It will be available again next month.', 'sm-ai' ) );
		}
		if ( is_user_logged_in() ) {
			if ( Usage::user_day( get_current_user_id() ) >= $s['per_user_day'] ) {
				return new \WP_Error( 'sm_ai_day', __( 'You used the AI assistant many times today. Try again tomorrow.', 'sm-ai' ) );
			}
			if ( $company && $f['per_company_month'] > 0 && Usage::company_month( (int) $company->id, $id ) >= $f['per_company_month'] ) {
				return new \WP_Error( 'sm_ai_month', __( 'Your company used all its requests of this month for this AI service.', 'sm-ai' ) );
			}
		} elseif ( Usage::ip_day( Forms::ip(), $id ) >= (int) ( $f['visitors_per_ip_day'] ?? 0 ) ) {
			return new \WP_Error( 'sm_ai_visitor', __( 'Create a free account to keep using the smart search today.', 'sm-ai' ) );
		}
		return true;
	}

	/** Remaining uses this month for the company (null = no limit). */
	public static function remaining( $id, $company ) {
		$f = Settings::feature( $id );
		if ( ! $company || ! $f || $f['per_company_month'] <= 0 ) {
			return null;
		}
		return max( 0, $f['per_company_month'] - Usage::company_month( (int) $company->id, $id ) );
	}

	/**
	 * Run a feature. $input: notes, q, market, files (parts)…
	 *
	 * @return array|\WP_Error Checked result + meta (feature, provider, simulation).
	 */
	public static function run( $id, array $input ) {
		$company = sm_current_company();
		$ok      = self::check( $id, $company );
		$class   = self::feature( $id );
		if ( is_wp_error( $ok ) ) {
			if ( $class && 'sm_ai_off' !== $ok->get_error_code() ) {
				Usage::log( array( 'feature' => $id, 'company_id' => $company ? (int) $company->id : 0, 'status' => 'blocked', 'error' => $ok->get_error_code() ) );
			}
			return $ok;
		}
		list( $provider, $model ) = Settings::route( $id );
		$lang             = in_array( Language::current(), array( 'fr', 'ar' ), true ) ? Language::current() : 'fr';
		$input['company'] = $company;
		$p                = Providers\Provider::for( $provider );
		$req              = array(
			'system'     => $class::system( $lang ),
			'parts'      => $class::parts( $input ),
			'model'      => $model,
			'max_tokens' => (int) Settings::get( 'max_tokens' ),
			'simulate'   => static fn() => $class::simulate( $input ),
		);
		$t0  = microtime( true );
		$res = $p::complete( $req );
		$ms  = (int) round( ( microtime( true ) - $t0 ) * 1000 );
		$log = array(
			'feature'     => $id,
			'provider'    => $provider,
			'model'       => $model,
			'company_id'  => $company ? (int) $company->id : 0,
			'tokens_in'   => $res['tokens_in'],
			'tokens_out'  => $res['tokens_out'],
			'cost'        => Usage::cost( $provider, $res['tokens_in'], $res['tokens_out'] ),
			'duration_ms' => $ms,
		);
		if ( ! $res['ok'] ) {
			Usage::log( $log + array( 'status' => 'error', 'error' => $res['error'] ) );
			return new \WP_Error( 'sm_ai_provider', __( 'The AI service did not answer. Try again in a moment.', 'sm-ai' ), array( 'detail' => $res['error'] ) );
		}
		$json = self::decode( $res['text'] );
		if ( null === $json ) {
			Usage::log( $log + array( 'status' => 'error', 'error' => 'invalid_json: ' . mb_substr( $res['text'], 0, 120 ) ) );
			return new \WP_Error( 'sm_ai_answer', __( 'The AI answer could not be read. Try again.', 'sm-ai' ) );
		}
		Usage::log( $log + array( 'status' => 'ok' ) );
		$out          = $class::clean( $json, $input );
		$out['_meta'] = array( 'feature' => $id, 'provider' => $provider, 'simulation' => 'simulation' === $provider, 'lang' => $lang, 'at' => time() );
		return $out;
	}

	/** First JSON object in a text (models sometimes wrap it in ```json fences). */
	public static function decode( $text ) {
		$text  = (string) $text;
		$start = strpos( $text, '{' );
		$end   = strrpos( $text, '}' );
		if ( false === $start || false === $end || $end < $start ) {
			return null;
		}
		$json = json_decode( substr( $text, $start, $end - $start + 1 ), true );
		return is_array( $json ) ? $json : null;
	}

	/** Connection test of a provider (admin): a tiny request. */
	public static function test( $provider ) {
		$p = Providers\Provider::for( $provider );
		if ( ! $p ) {
			return new \WP_Error( 'sm_ai_provider', 'unknown provider' );
		}
		$model = (string) ( Settings::all()['providers'][ $provider ]['model'] ?? '' );
		if ( 'simulation' !== $provider && '' === $model ) {
			return new \WP_Error( 'sm_ai_model', __( 'Give the model name first.', 'sm-ai' ) );
		}
		$t0  = microtime( true );
		$res = $p::complete( array( 'system' => 'Reply with the JSON object {"ok":true} and nothing else.', 'parts' => array( array( 'type' => 'text', 'text' => 'Test' ) ), 'model' => $model, 'max_tokens' => 20, 'simulate' => static fn() => '{"ok":true}' ) );
		Usage::log( array( 'feature' => 'test', 'provider' => $provider, 'model' => $model, 'status' => $res['ok'] ? 'ok' : 'error', 'tokens_in' => $res['tokens_in'], 'tokens_out' => $res['tokens_out'], 'cost' => Usage::cost( $provider, $res['tokens_in'], $res['tokens_out'] ), 'duration_ms' => (int) round( ( microtime( true ) - $t0 ) * 1000 ), 'error' => $res['error'] ) );
		return $res['ok'] ? true : new \WP_Error( 'sm_ai_test', $res['error'] );
	}
}
