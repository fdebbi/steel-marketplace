<?php
/**
 * Form handlers of the AI assistant (admin-post):
 *  - sm_ai_run    : listing assistant, profile recognition, document analysis → result page;
 *  - sm_ai_search : smart search → marketplace search with the filters understood;
 *  - sm_ai_use    : open the listing or request for quotation form pre-filled with a result;
 *  - sm_ai_describe (admin-ajax, 0.2.0): description of a listing from the facts of the publication form;
 *  - sm_ai_cutlist (0.3.0): read a document (DQE IA) → back to the page that asked, with the lines to check;
 *  - sm_ai_doc_use (0.4.0): the lines checked go to the cutting optimiser, a request for quotation or the warehouse;
 *  - sm_ai_form_photo (admin-ajax, 0.5.0): in the publication form, recognise the profile or suggest the
 *    condition of reused steel from the photos of the form (new files and photos already saved).
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
		add_action( 'wp_ajax_sm_ai_describe', array( __CLASS__, 'describe' ) );
		add_action( 'wp_ajax_nopriv_sm_ai_describe', array( __CLASS__, 'describe' ) );
		add_action( 'admin_post_sm_ai_cutlist', array( __CLASS__, 'cutlist' ) );
		add_action( 'admin_post_nopriv_sm_ai_cutlist', array( __CLASS__, 'cutlist' ) );
		add_action( 'admin_post_sm_ai_doc_use', array( __CLASS__, 'doc_use' ) );
		add_action( 'wp_ajax_sm_ai_form_photo', array( __CLASS__, 'form_photo' ) );
	}

	/**
	 * Recognise the profile, or suggest the condition, from the photos of the
	 * publication form. JSON: { tool, result, remaining } or { message, state }.
	 */
	public static function form_photo() {
		if ( ! check_ajax_referer( 'sm_ai_form_photo', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'The page has expired. Reload it and try again.', 'sm-ai' ), 'state' => 'blocked' ), 403 );
		}
		$tool = 'condition' === sanitize_key( (string) ( $_POST['tool'] ?? '' ) ) ? 'condition' : 'recognize'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$s    = Settings::all();
		$max  = (int) $s['max_images'];
		$parts = array();
		// Photos already saved with the listing (or uploaded before by this user).
		foreach ( array_slice( array_map( 'absint', (array) ( $_POST['keep'] ?? array() ) ), 0, $max ) as $aid ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! $aid || 'attachment' !== get_post_type( $aid ) || (int) get_post_field( 'post_author', $aid ) !== get_current_user_id() ) {
				continue;
			}
			$path = get_attached_file( $aid );
			$mime = $path && is_readable( $path ) ? Files::mime( $path ) : '';
			if ( in_array( $mime, Files::IMAGE_TYPES, true ) ) {
				$parts[] = Files::image( $path, $mime );
			}
		}
		$new = Files::read( 'photos', false, max( 0, $max - count( $parts ) ) );
		if ( is_wp_error( $new ) ) {
			wp_send_json_error( array( 'message' => $new->get_error_message(), 'state' => 'blocked' ) );
		}
		$parts = array_slice( array_merge( $parts, $new ), 0, $max );
		if ( ! $parts ) {
			wp_send_json_error( array( 'message' => __( 'Add your photos first (Photos part of the form): the AI looks at them.', 'sm-ai' ), 'state' => 'blocked' ) );
		}
		$res = Gateway::run( $tool, array( 'files' => $parts, 'notes' => '' ) );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message(), 'state' => self::state_of( $res ) ) );
		}
		$company = sm_current_company();
		AuditLog::add( 'ai.used', 'ai', 0, array( 'feature' => $tool, 'where' => 'form' ), null, $company ? (int) $company->id : 0 );
		if ( 'recognize' === $tool ) {
			$res['candidates'] = array_slice( $res['candidates'], 0, 3 );
		}
		unset( $res['_meta'] );
		wp_send_json_success( array( 'tool' => $tool, 'result' => $res, 'remaining' => Gateway::remaining( $tool, $company ) ) );
	}

	/** Page of the optimiser to go back to (same site only). */
	private static function cut_back() {
		$fallback = Language::url_for( sm_url( 'cutting' ), Language::current() );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Forms::begin().
		$back = isset( $_POST['back'] ) ? esc_url_raw( wp_unslash( (string) $_POST['back'] ) ) : '';
		$back = remove_query_arg( array( 'ai', 'sm_n' ), wp_validate_redirect( $back, $fallback ) );
		return $back;
	}

	/**
	 * DQE IA: read the document (Excel, CSV, PDF or photo), keep the result 2 hours
	 * for this user and go back to the optimiser, where the user checks it.
	 * The credit is used once the AI has read the document (even if unreadable).
	 */
	public static function cutlist() {
		Forms::begin( 'ai_cutlist' );
		$back = self::cut_back();
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( Language::url_for( sm_url( 'login' ), Language::current() ) );
			exit;
		}
		$name  = '';
		$field = ! empty( $_FILES['document']['name'] ) ? 'document' : 'photo'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$files = Files::read( $field, 'document' === $field, 1, 'document' === $field );
		if ( ! is_wp_error( $files ) && $files ) {
			$name = sanitize_file_name( (string) ( $_FILES[ $field ]['name'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
		}
		if ( is_wp_error( $files ) ) {
			Forms::back( array( $files->get_error_message() ), '', $back . '#smcut-ai' );
		}
		if ( ! $files ) {
			Forms::back( array( __( 'Choose the document: Excel, PDF or a photo.', 'sm-ai' ) ), '', $back . '#smcut-ai' );
		}
		$res = Gateway::run( 'cutlist', array( 'files' => $files, 'name' => $name ) );
		if ( is_wp_error( $res ) ) {
			Forms::back( array( $res->get_error_message() ), '', $back . '#smcut-ai' );
		}
		$company = sm_current_company();
		AuditLog::add( 'ai.used', 'ai', 0, array( 'feature' => 'cutlist' ), null, $company ? (int) $company->id : 0 );
		$token = wp_generate_password( 16, false );
		$for = sanitize_key( (string) Forms::post( 'for' ) );
		set_transient( 'sm_ai_c_' . $token, array( 'user' => get_current_user_id(), 'result' => $res, 'back' => $back, 'for' => in_array( $for, array( 'cutting', 'rfq', 'stock' ), true ) ? $for : '' ), 2 * HOUR_IN_SECONDS );
		wp_safe_redirect( add_query_arg( 'ai', $token, $back ) . '#smcut-ai' );
		exit;
	}

	/** A DQE result of the current user, or null. */
	public static function cutlist_result( $token ) {
		$r = self::cutlist_entry( $token );
		return $r ? $r['result'] : null;
	}

	/** The stored reading (result, back, for) of the current user, or null. */
	public static function cutlist_entry( $token ) {
		$token = preg_replace( '/[^A-Za-z0-9]/', '', (string) $token );
		$r     = '' !== $token ? get_transient( 'sm_ai_c_' . $token ) : false;
		return is_array( $r ) && get_current_user_id() && (int) $r['user'] === get_current_user_id() ? $r : null;
	}

	/**
	 * Lines ticked in the check: bars {family, section, length (m), qty, qty_stock,
	 * doc_kg, text} and plates {t, l, w, qty, label}, read from the form.
	 */
	public static function checked_lines() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Forms::begin() in the handler.
		$raw = static fn( $k ) => isset( $_POST[ $k ] ) && is_array( $_POST[ $k ] ) ? map_deep( wp_unslash( $_POST[ $k ] ), 'sanitize_text_field' ) : array();
		// phpcs:enable
		$num  = static fn( $v ) => (float) str_replace( array( ',', ' ' ), array( '.', '' ), (string) $v );
		$bars = array();
		foreach ( array_slice( $raw( 'ai_bar' ), 0, 80 ) as $r ) {
			if ( ! is_array( $r ) || empty( $r['on'] ) ) {
				continue;
			}
			$sid = absint( $r['section'] ?? 0 );
			$s   = $sid && class_exists( '\SM\WeightEngine\Catalogue' ) ? \SM\WeightEngine\Catalogue::get( $sid ) : null;
			$len = round( $num( $r['length'] ?? 0 ), 3 );
			$qty = absint( $r['qty'] ?? 0 );
			if ( ! $s || $len <= 0 || $len > 24 || ! $qty ) {
				continue;
			}
			$bars[] = array(
				'family'    => $s->family,
				'section'   => (int) $s->id,
				'designation' => $s->designation,
				'length'    => $len,
				'qty'       => min( 10000, $qty ),
				'qty_stock' => min( 10000, max( 1, absint( $r['qty_stock'] ?? $qty ) ) ),
				'doc_kg'    => max( 0, round( $num( $r['doc_kg'] ?? 0 ), 1 ) ),
				'text'      => mb_substr( (string) ( $r['text'] ?? '' ), 0, 120 ),
			);
		}
		$plates = array();
		foreach ( array_slice( $raw( 'ai_plate' ), 0, 40 ) as $r ) {
			if ( ! is_array( $r ) || empty( $r['on'] ) ) {
				continue;
			}
			$t = round( $num( $r['t'] ?? 0 ), 1 );
			$l = absint( $r['l'] ?? 0 );
			$w = absint( $r['w'] ?? 0 );
			$q = absint( $r['qty'] ?? 0 );
			if ( $t > 0 && $t <= 200 && $l > 0 && $l <= 12000 && $w > 0 && $w <= 3000 && $q > 0 ) {
				$plates[] = array( 't' => $t, 'l' => $l, 'w' => $w, 'qty' => min( 10000, $q ), 'label' => mb_substr( (string) ( $r['label'] ?? '' ), 0, 80 ) );
			}
		}
		return array( $bars, $plates );
	}

	/** Lines checked → cutting optimiser, request for quotation or warehouse (0.4.0). */
	public static function doc_use() {
		Forms::begin( 'ai_doc_use' );
		$entry = self::cutlist_entry( Forms::post( 'r' ) );
		if ( ! $entry ) {
			Forms::back( array( __( 'This reading expired. Read the document again.', 'sm-ai' ) ) );
		}
		list( $bars, $plates ) = self::checked_lines();
		if ( ! $bars && ! $plates ) {
			Forms::back( array( __( 'Tick at least one complete line (profile, length and number).', 'sm-ai' ) ) );
		}
		$lang = Language::current();
		$dest = sanitize_key( (string) Forms::post( 'dest' ) );
		if ( 'cutting' === $dest ) {
			// Back to the optimiser that asked (its list and settings), or a new one; the lines are added.
			$back = (string) $entry['back'];
			$base = false !== strpos( $back, wp_parse_url( sm_url( 'cutting' ), PHP_URL_PATH ) ) ? $back : Language::url_for( sm_url( 'cutting' ), $lang );
			$q    = array();
			wp_parse_str( (string) wp_parse_url( $base, PHP_URL_QUERY ), $q );
			$rows = array_values( (array) ( $q['rows'] ?? array() ) );
			foreach ( $bars as $b ) {
				$rows[] = array( 'family' => $b['family'], 'section' => $b['section'], 'length' => $b['length'], 'qty' => $b['qty'] );
			}
			$pl = array_values( (array) ( $q['plates'] ?? array() ) );
			foreach ( $plates as $p ) {
				$pl[] = $p;
			}
			$q['rows']   = $rows;
			$q['plates'] = $pl;
			$q['src']    = $q['src'] ?? 1;
			$q           = array_merge( array( 'market' => 1, 'mine' => 1, 'mine_new' => 1, 'mine_reuse' => 1, 'lots' => 1, 'conds' => array( 'new', 'new_surplus', 'excellent', 'good' ) ), $q );
			wp_safe_redirect( strtok( $base, '?' ) . '?' . http_build_query( $q ) . '#smcut-result' );
			exit;
		}
		if ( 'rfq' === $dest ) {
			if ( ! sm_can( 'rfq.create' ) ) {
				Forms::back( array( __( 'Your account cannot create requests for quotation.', 'sm-ai' ) ) );
			}
			$lines = array();
			$notes = array();
			foreach ( array_slice( $bars, 0, 30 ) as $b ) {
				$lines[] = array( 'family' => $b['family'], 'section' => (string) $b['section'], 'qty' => (string) $b['qty'], 'length' => (string) $b['length'] );
				if ( $b['doc_kg'] > 0 ) {
					/* translators: 1: profile, 2: weight written, 3: bars, 4: length. */
					$notes[] = sprintf( __( '%1$s: %2$s kg in the document (%3$d bars of %4$s m)', 'sm-ai' ), $b['designation'], number_format_i18n( $b['doc_kg'], 0 ), $b['qty'], number_format_i18n( $b['length'], 2 ) );
				}
			}
			foreach ( $plates as $p ) {
				/* translators: 1: thickness, 2: length, 3: width, 4: number. */
				$notes[] = sprintf( __( 'Plate %1$s mm, %2$d × %3$d mm × %4$d', 'sm-ai' ), number_format_i18n( $p['t'], fmod( $p['t'], 1 ) ? 1 : 0 ), $p['l'], $p['w'], $p['qty'] ) . ( '' !== $p['label'] ? ' - ' . $p['label'] : '' );
			}
			$r     = $entry['result'];
			$title = '' !== (string) ( $r['summary'] ?? '' ) ? mb_substr( rtrim( (string) $r['summary'], '.' ), 0, 100 ) : __( 'Request according to a document', 'sm-ai' );
			$v     = array(
				'market'      => 'products',
				'title'       => array( $lang => $title ),
				'lines'       => $lines,
				'description' => $notes ? __( 'From the document:', 'sm-ai' ) . "\n- " . implode( "\n- ", $notes ) : '',
			);
			if ( ! $lines ) {
				$v['designation'] = __( 'Plates', 'sm-ai' );
				$v['quantity']    = '';
				$v['unit']        = 'kg';
			}
			Forms::prefill( $v, Language::url_for( sm_url( 'account', array( 'view' => 'rfq', 'new' => 1 ) ), $lang ), __( 'Request pre-filled from your document: check every line before publishing. Weights are computed by the weight engine.', 'sm-ai' ) );
		}
		if ( 'stock' === $dest ) {
			$opts = array( 'market' => 'reuse' === Forms::post( 'stock_market' ) ? 'reuse' : 'new', 'location' => mb_substr( sanitize_text_field( (string) Forms::post( 'location' ) ), 0, 120 ) );
			/**
			 * The warehouse (sm-stock 0.3.0) takes the lines: WP_Error, or { items, url }.
			 *
			 * @param mixed  $done    null when no warehouse.
			 * @param object $company Company.
			 * @param array  $bars    Bars (qty_stock, doc_kg…).
			 * @param array  $plates  Plates.
			 * @param array  $opts    market new|reuse, location.
			 */
			$done = apply_filters( 'sm_ai_doc_to_stock', null, sm_current_company(), $bars, $plates, $opts );
			if ( null === $done ) {
				Forms::back( array( __( 'Your account has no warehouse.', 'sm-ai' ) ) );
			}
			if ( is_wp_error( $done ) ) {
				Forms::back( array( $done->get_error_message() ) );
			}
			/* translators: %d: lines. */
			Forms::back( array(), sprintf( _n( '%d line added to your warehouse, kept in stock only.', '%d lines added to your warehouse, kept in stock only.', count( $done['items'] ), 'sm-ai' ), count( $done['items'] ) ), $done['url'] );
		}
		Forms::back( array( __( 'Choose what to do with the lines.', 'sm-ai' ) ) );
	}

	/** Where a refusal leads: login, upgrade (no credit left) or nothing to do. */
	public static function state_of( $error ) {
		if ( ! is_wp_error( $error ) ) {
			return 'ok';
		}
		if ( ! is_user_logged_in() ) {
			return 'login';
		}
		return in_array( $error->get_error_code(), array( 'sm_ai_credits', 'sm_ai_plan' ), true ) ? 'upgrade' : 'blocked';
	}

	/** Description writer: JSON answer for the publication form. */
	public static function describe() {
		if ( ! check_ajax_referer( 'sm_ai_describe', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'The page has expired. Reload it and try again.', 'sm-ai' ), 'state' => 'blocked' ), 403 );
		}
		$facts = Features\Describe::facts( array( 'facts' => wp_unslash( (string) ( $_POST['facts'] ?? '' ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		if ( mb_strlen( $facts ) < 15 ) {
			wp_send_json_error( array( 'message' => __( 'Fill in the product first (category, profile, quantity…): the description is written from it.', 'sm-ai' ), 'state' => 'blocked' ) );
		}
		$res = Gateway::run( 'describe', array( 'facts' => $facts ) );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message(), 'state' => self::state_of( $res ) ) );
		}
		$company = sm_current_company();
		AuditLog::add( 'ai.used', 'ai', 0, array( 'feature' => 'describe' ), null, $company ? (int) $company->id : 0 );
		wp_send_json_success(
			array(
				'description' => $res['description'],
				'simulation'  => ! empty( $res['_meta']['simulation'] ),
				'remaining'   => Gateway::remaining( 'describe', $company ),
			)
		);
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
