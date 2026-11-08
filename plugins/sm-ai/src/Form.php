<?php
/**
 * Form handlers of the AI assistant (admin-post):
 *  - sm_ai_run    : listing assistant, profile recognition, document analysis → result page;
 *  - sm_ai_search : smart search → marketplace search with the filters understood;
 *  - sm_ai_use    : open the listing or request for quotation form pre-filled with a result.
 * Results are kept 2 hours for the user who asked (transient), never published as is.
 *
 * @package SM\Ai
 */

namespace SM\Ai;

use SM\Core\Audit\AuditLog;
use SM\Core\I18n\Language;
use SM\Core\Support\Forms;

defined( 'ABSPATH' ) || exit;

final class Form {

	const TOOLS = array( 'listing', 'recognize', 'document' );

	public static function init() {
		add_action( 'admin_post_sm_ai_run', array( __CLASS__, 'run' ) );
		add_action( 'admin_post_sm_ai_use', array( __CLASS__, 'use_result' ) );
		add_action( 'admin_post_sm_ai_search', array( __CLASS__, 'search' ) );
		add_action( 'admin_post_nopriv_sm_ai_search', array( __CLASS__, 'search' ) );
	}

	private static function account_url( array $args ) {
		return Language::url_for( sm_url( 'account', array_merge( array( 'view' => 'ai' ), $args ) ), Language::current() );
	}

	public static function run() {
		Forms::begin( 'ai_run' );
		$tool = sanitize_key( (string) Forms::post( 'tool' ) );
		if ( ! in_array( $tool, self::TOOLS, true ) ) {
			Forms::back( array( __( 'Choose an AI tool.', 'sm-ai' ) ) );
		}
		$s     = Settings::all();
		$input = array(
			'notes'  => mb_substr( Forms::post_textarea( 'notes' ), 0, 2000 ),
			'market' => sanitize_key( (string) Forms::post( 'market' ) ),
		);
		$files = Files::read( 'document' === $tool ? 'document' : 'photos', 'document' === $tool, 'document' === $tool ? 1 : (int) $s['max_images'] );
		if ( is_wp_error( $files ) ) {
			Forms::back( array( 'photos' => $files->get_error_message() ) );
		}
		if ( 'recognize' === $tool && ! $files ) {
			Forms::back( array( 'photos' => __( 'Add at least one photo of the profile.', 'sm-ai' ) ) );
		}
		if ( 'document' === $tool && ! $files ) {
			Forms::back( array( 'document' => __( 'Add the document to analyse (PDF or photo).', 'sm-ai' ) ) );
		}
		if ( 'listing' === $tool && ! $files && mb_strlen( trim( $input['notes'] ) ) < 10 ) {
			Forms::back( array( 'notes' => __( 'Add photos or describe the product in a few words.', 'sm-ai' ) ) );
		}
		$input['files'] = $files;
		$res            = Gateway::run( $tool, $input );
		if ( is_wp_error( $res ) ) {
			Forms::back( array( $res->get_error_message() ) );
		}
		$company = sm_current_company();
		AuditLog::add( 'ai.used', 'ai', 0, array( 'feature' => $tool ), null, $company ? (int) $company->id : 0 );
		$token = wp_generate_password( 16, false );
		set_transient( 'sm_ai_r_' . $token, array( 'user' => get_current_user_id(), 'tool' => $tool, 'result' => $res ), 2 * HOUR_IN_SECONDS );
		wp_safe_redirect( self::account_url( array( 'tool' => $tool, 'r' => $token ) ) . '#sm-ai-result' );
		exit;
	}

	/** A stored result of the current user, or null. */
	public static function result( $token ) {
		$token = preg_replace( '/[^A-Za-z0-9]/', '', (string) $token );
		$r     = '' !== $token ? get_transient( 'sm_ai_r_' . $token ) : false;
		return is_array( $r ) && (int) $r['user'] === get_current_user_id() && get_current_user_id() ? $r : null;
	}

	public static function use_result() {
		Forms::begin( 'ai_use' );
		$r    = self::result( Forms::post( 'r' ) );
		$lang = Language::current();
		if ( ! $r ) {
			Forms::back( array( __( 'This suggestion expired. Run the assistant again.', 'sm-ai' ) ) );
		}
		$msg = __( 'Form pre-filled by the AI assistant: check every field before publishing. The weight is computed by the weight engine.', 'sm-ai' );
		if ( 'listing' === $r['tool'] ) {
			$v = Features\Listing::form_values( $r['result'], $lang );
			Forms::prefill( $v, Language::url_for( sm_url( 'account', array( 'view' => 'publish' ) ), $lang ), $msg );
		}
		if ( 'document' === $r['tool'] ) {
			if ( ! sm_can( 'rfq.create' ) ) {
				Forms::back( array( __( 'Your account cannot create requests for quotation.', 'sm-ai' ) ) );
			}
			$v = Features\Document::rfq_values( $r['result'], $lang );
			Forms::prefill( $v, Language::url_for( sm_url( 'account', array( 'view' => 'rfq', 'new' => 1 ) ), $lang ), $msg );
		}
		Forms::back( array( __( 'Nothing to use from this result.', 'sm-ai' ) ) );
	}

	public static function search() {
		Forms::begin( 'ai_search' );
		$q    = mb_substr( trim( (string) Forms::post( 'q' ) ), 0, 300 );
		$lang = Language::current();
		$base = Language::url_for( sm_url( 'search' ), $lang );
		if ( mb_strlen( $q ) < 3 ) {
			wp_safe_redirect( add_query_arg( 'q', rawurlencode( $q ), $base ) );
			exit;
		}
		$res = Gateway::run( 'search', array( 'q' => $q ) );
		if ( is_wp_error( $res ) ) {
			// Plain keyword search instead, with the reason.
			$token = wp_generate_password( 12, false );
			set_transient( 'sm_ai_s_' . $token, array( 'error' => $res->get_error_message(), 'query' => $q ), HOUR_IN_SECONDS );
			wp_safe_redirect( add_query_arg( array( 'q' => rawurlencode( $q ), 'ais' => $token ), $base ) );
			exit;
		}
		$token = wp_generate_password( 12, false );
		set_transient( 'sm_ai_s_' . $token, array( 'query' => $q, 'explanation' => $res['explanation'], 'filters' => $res['filters'], 'simulation' => $res['_meta']['simulation'] ), DAY_IN_SECONDS );
		$args = array_map( 'rawurlencode', $res['filters'] );
		if ( isset( $args['price_max'] ) ) {
			$args['max_price'] = $args['price_max'];
		}
		wp_safe_redirect( add_query_arg( $args + array( 'ais' => $token ), $base ) );
		exit;
	}

	/** What the smart search understood (for the results page), or null. */
	public static function search_info( $token ) {
		$token = preg_replace( '/[^A-Za-z0-9]/', '', (string) $token );
		$r     = '' !== $token ? get_transient( 'sm_ai_s_' . $token ) : false;
		if ( ! is_array( $r ) ) {
			return null;
		}
		$r['chips'] = isset( $r['filters'] ) ? Features\Search::chips( $r['filters'] ) : array();
		return $r;
	}
}
