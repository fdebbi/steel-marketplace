<?php
/**
 * wp-admin → SM → AI: providers and keys, services and limits, consumption
 * (calls, tokens, estimated cost, errors) and the log of calls.
 *
 * @package SM\Ai
 */

namespace SM\Ai\Admin;

use SM\Ai\Gateway;
use SM\Ai\Settings;
use SM\Ai\Usage;
use SM\Core\Accounts\Companies;
use SM\Core\Audit\AuditLog;

defined( 'ABSPATH' ) || exit;

final class Admin {

	const SLUG = 'sm-ai';
	const CAP  = 'sm_manage_settings';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 25 );
		add_action( 'admin_post_sm_ai_settings', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_sm_ai_test', array( __CLASS__, 'test' ) );
	}

	public static function menu() {
		add_submenu_page( 'sm-core', __( 'AI assistant', 'sm-ai' ), __( 'AI assistant', 'sm-ai' ), self::CAP, self::SLUG, array( __CLASS__, 'page' ) );
	}

	private static function url( array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::SLUG ), $args ), admin_url( 'admin.php' ) );
	}

	public static function labels() {
		return array(
			'listing'   => __( 'Listing assistant', 'sm-ai' ),
			'recognize' => __( 'Profile recognition', 'sm-ai' ),
			'search'    => __( 'Smart search', 'sm-ai' ),
			'document'  => __( 'Document analysis', 'sm-ai' ),
			'test'      => __( 'Connection test', 'sm-ai' ),
		);
	}

	public static function page() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';
		echo '<div class="wrap sm-admin"><h1>' . esc_html__( 'AI assistant', 'sm-ai' ) . '</h1>';
		if ( isset( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Saved.', 'sm-ai' ) . '</p></div>';
		}
		if ( isset( $_GET['test'] ) ) {
			$ok  = 'ok' === $_GET['test'];
			$msg = $ok ? __( 'Connection works: the provider answered.', 'sm-ai' ) : sprintf( /* translators: %s: error. */ __( 'Connection failed: %s', 'sm-ai' ), sanitize_text_field( wp_unslash( $_GET['err'] ?? '' ) ) );
			echo '<div class="notice notice-' . ( $ok ? 'success' : 'error' ) . ' is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
		}
		// phpcs:enable
		echo '<p>' . esc_html__( 'The AI gives suggestions that users confirm. It never gives weights (computed by the weight engine), never replaces engineering calculations, structural checks or certifications.', 'sm-ai' ) . '</p>';
		echo '<nav class="nav-tab-wrapper">';
		foreach ( array( 'settings' => __( 'Providers and services', 'sm-ai' ), 'usage' => __( 'Consumption', 'sm-ai' ) ) as $k => $l ) {
			echo '<a class="nav-tab' . ( $k === $tab ? ' nav-tab-active' : '' ) . '" href="' . esc_url( self::url( array( 'tab' => $k ) ) ) . '">' . esc_html( $l ) . '</a>';
		}
		echo '</nav>';
		'usage' === $tab ? self::usage() : self::settings();
		echo '</div>';
	}

	private static function settings() {
		$s      = Settings::all();
		$labels = Settings::provider_labels();
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sm_ai_settings">';
		wp_nonce_field( 'sm_ai_settings' );
		echo '<h2>' . esc_html__( 'Providers', 'sm-ai' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Keys are stored encrypted and never shown again. For more safety, define them in wp-config.php: SM_AI_ANTHROPIC_KEY, SM_AI_OPENAI_KEY. Prices (US dollars per million tokens, as published by the provider) only serve the cost estimates and the monthly budget.', 'sm-ai' ) . '</p>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Provider', 'sm-ai' ) . '</th><th>' . esc_html__( 'API key', 'sm-ai' ) . '</th><th>' . esc_html__( 'Model', 'sm-ai' ) . '</th><th>' . esc_html__( 'Price in / out ($ per 1M tokens)', 'sm-ai' ) . '</th></tr></thead><tbody>';
		foreach ( $s['providers'] as $p => $row ) {
			$hint = Settings::key_hint( $p );
			echo '<tr><td><strong>' . esc_html( $labels[ $p ] ?? $p ) . '</strong>';
			if ( isset( $row['base_url'] ) ) {
				echo '<p><label>' . esc_html__( 'Base URL', 'sm-ai' ) . '<br><input class="regular-text" name="providers[' . esc_attr( $p ) . '][base_url]" value="' . esc_attr( $row['base_url'] ) . '" dir="ltr"></label></p>';
			}
			echo '</td><td>';
			if ( Settings::key_from_config( $p ) ) {
				echo '<span class="description">' . esc_html__( 'Defined in wp-config.php', 'sm-ai' ) . ' (' . esc_html( $hint ) . ')</span>';
			} else {
				echo '<input type="password" autocomplete="new-password" class="regular-text" name="providers[' . esc_attr( $p ) . '][key]" placeholder="' . esc_attr( $hint ? $hint : __( 'Paste the key', 'sm-ai' ) ) . '" dir="ltr">';
				if ( $hint ) {
					echo '<br><label><input type="checkbox" name="providers[' . esc_attr( $p ) . '][clear]" value="1"> ' . esc_html__( 'Remove the key', 'sm-ai' ) . '</label>';
				}
			}
			echo '</td><td><input class="regular-text" name="providers[' . esc_attr( $p ) . '][model]" value="' . esc_attr( $row['model'] ) . '" dir="ltr" placeholder="' . esc_attr__( 'Model name of the provider', 'sm-ai' ) . '"></td>';
			echo '<td><input type="number" step="0.01" min="0" style="width:90px" name="providers[' . esc_attr( $p ) . '][price_in]" value="' . esc_attr( $row['price_in'] ) . '"> / <input type="number" step="0.01" min="0" style="width:90px" name="providers[' . esc_attr( $p ) . '][price_out]" value="' . esc_attr( $row['price_out'] ) . '"></td></tr>';
		}
		echo '</tbody></table>';
		echo '<p><label>' . esc_html__( 'Default provider', 'sm-ai' ) . ' <select name="default_provider"><option value="">' . esc_html__( '— none (AI off) —', 'sm-ai' ) . '</option>';
		foreach ( $labels as $k => $l ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( $s['default_provider'], $k, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></label></p>';

		echo '<h2>' . esc_html__( 'Services', 'sm-ai' ) . '</h2><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Service', 'sm-ai' ) . '</th><th>' . esc_html__( 'On', 'sm-ai' ) . '</th><th>' . esc_html__( 'Provider', 'sm-ai' ) . '</th><th>' . esc_html__( 'Model (empty = the provider\'s)', 'sm-ai' ) . '</th><th>' . esc_html__( 'Uses per company per month', 'sm-ai' ) . '</th><th>' . esc_html__( 'Visitors: uses per day', 'sm-ai' ) . '</th><th>' . esc_html__( 'State', 'sm-ai' ) . '</th></tr></thead><tbody>';
		$fl = self::labels();
		foreach ( $s['features'] as $f => $row ) {
			list( $prov, $model ) = Settings::route( $f );
			echo '<tr><td><strong>' . esc_html( $fl[ $f ] ) . '</strong></td>';
			echo '<td><input type="checkbox" name="features[' . esc_attr( $f ) . '][enabled]" value="1"' . checked( (int) $row['enabled'], 1, false ) . '></td>';
			echo '<td><select name="features[' . esc_attr( $f ) . '][provider]"><option value="">' . esc_html__( 'Default', 'sm-ai' ) . '</option>';
			foreach ( $labels as $k => $l ) {
				echo '<option value="' . esc_attr( $k ) . '"' . selected( $row['provider'], $k, false ) . '>' . esc_html( $l ) . '</option>';
			}
			echo '</select></td><td><input name="features[' . esc_attr( $f ) . '][model]" value="' . esc_attr( $row['model'] ) . '" dir="ltr"></td>';
			echo '<td><input type="number" min="0" style="width:90px" name="features[' . esc_attr( $f ) . '][per_company_month]" value="' . esc_attr( $row['per_company_month'] ) . '"> <span class="description">' . esc_html__( '0 = no limit', 'sm-ai' ) . '</span></td>';
			echo '<td>' . ( isset( $row['visitors_per_ip_day'] ) ? '<input type="number" min="0" style="width:80px" name="features[' . esc_attr( $f ) . '][visitors_per_ip_day]" value="' . esc_attr( $row['visitors_per_ip_day'] ) . '">' : '—' ) . '</td>';
			echo '<td>' . ( '' !== $prov && $row['enabled'] ? '<span style="color:#065f46">' . esc_html( ( $labels[ $prov ] ?? $prov ) . ' · ' . $model ) . '</span>' : '<span style="color:#991b1b">' . esc_html__( 'Not available (no provider, key or model)', 'sm-ai' ) . '</span>' ) . '</td></tr>';
		}
		echo '</tbody></table>';

		echo '<h2>' . esc_html__( 'Limits', 'sm-ai' ) . '</h2><table class="form-table"><tbody>';
		$fields = array(
			'monthly_budget' => array( __( 'Monthly budget (US dollars, 0 = no limit)', 'sm-ai' ), 'step="0.01" min="0"' ),
			'per_user_day'   => array( __( 'Uses per user per day', 'sm-ai' ), 'min="1"' ),
			'max_tokens'     => array( __( 'Maximum length of an answer (tokens)', 'sm-ai' ), 'min="300" max="8000"' ),
			'max_images'     => array( __( 'Photos per request', 'sm-ai' ), 'min="1" max="5"' ),
			'image_mb'       => array( __( 'Largest photo (MB)', 'sm-ai' ), 'min="1" max="20"' ),
			'pdf_mb'         => array( __( 'Largest document (MB)', 'sm-ai' ), 'min="1" max="32"' ),
			'timeout'        => array( __( 'Waiting time for an answer (seconds)', 'sm-ai' ), 'min="10" max="180"' ),
			'retention'      => array( __( 'Keep the consumption log (days)', 'sm-ai' ), 'min="30" max="3650"' ),
		);
		foreach ( $fields as $k => $f ) {
			echo '<tr><th><label for="smai-' . esc_attr( $k ) . '">' . esc_html( $f[0] ) . '</label></th><td><input id="smai-' . esc_attr( $k ) . '" type="number" ' . $f[1] . ' name="' . esc_attr( $k ) . '" value="' . esc_attr( $s[ $k ] ) . '" style="width:120px"></td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput -- fixed attributes.
		}
		echo '</tbody></table>';
		submit_button();
		echo '</form><h2>' . esc_html__( 'Test the connection', 'sm-ai' ) . '</h2><p>';
		foreach ( $labels as $p => $l ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin-inline-end:8px"><input type="hidden" name="action" value="sm_ai_test"><input type="hidden" name="provider" value="' . esc_attr( $p ) . '">';
			wp_nonce_field( 'sm_ai_test' );
			echo '<button class="button">' . esc_html( $l ) . '</button></form>';
		}
		echo '</p><p class="description">' . esc_html__( 'Sends a tiny request (a few tokens) with the saved key and model.', 'sm-ai' ) . '</p>';
	}

	private static function usage() {
		$stats = Usage::month_stats();
		$fl    = self::labels();
		$cost  = Usage::month_cost();
		$bud   = (float) Settings::get( 'monthly_budget' );
		echo '<h2>' . esc_html( sprintf( /* translators: %s: month. */ __( 'This month (%s)', 'sm-ai' ), wp_date( 'Y-m' ) ) ) . '</h2>';
		echo '<p><strong>' . esc_html( sprintf( /* translators: %s: amount. */ __( 'Estimated cost: %s $', 'sm-ai' ), number_format_i18n( $cost, 4 ) ) ) . '</strong>' . ( $bud > 0 ? ' / ' . esc_html( number_format_i18n( $bud, 2 ) ) . ' $ (' . esc_html( number_format_i18n( min( 100, $cost / $bud * 100 ), 1 ) ) . ' %)' : '' ) . '</p>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Service', 'sm-ai' ) . '</th><th>' . esc_html__( 'Answers', 'sm-ai' ) . '</th><th>' . esc_html__( 'Errors', 'sm-ai' ) . '</th><th>' . esc_html__( 'Refused (limits)', 'sm-ai' ) . '</th><th>' . esc_html__( 'Tokens in / out', 'sm-ai' ) . '</th><th>' . esc_html__( 'Estimated cost ($)', 'sm-ai' ) . '</th></tr></thead><tbody>';
		foreach ( $stats as $f => $r ) {
			echo '<tr><td>' . esc_html( $fl[ $f ] ?? $f ) . '</td><td>' . (int) $r['ok'] . '</td><td>' . (int) $r['error'] . '</td><td>' . (int) $r['blocked'] . '</td><td>' . esc_html( number_format_i18n( $r['tokens_in'] ) . ' / ' . number_format_i18n( $r['tokens_out'] ) ) . '</td><td>' . esc_html( number_format_i18n( $r['cost'], 4 ) ) . '</td></tr>';
		}
		echo '</tbody></table>';
		$top = Usage::top_companies();
		if ( $top ) {
			echo '<h2>' . esc_html__( 'Companies using it the most', 'sm-ai' ) . '</h2><table class="widefat striped"><tbody>';
			foreach ( $top as $t ) {
				$c = Companies::get( (int) $t->company_id );
				echo '<tr><td>' . esc_html( $c ? $c->name : '#' . $t->company_id ) . '</td><td>' . (int) $t->n . '</td><td>' . esc_html( number_format_i18n( (float) $t->cost, 4 ) ) . ' $</td></tr>';
			}
			echo '</tbody></table>';
		}
		echo '<h2>' . esc_html__( 'Last calls', 'sm-ai' ) . '</h2><table class="widefat striped sm-ai-log"><thead><tr><th>' . esc_html__( 'Date', 'sm-ai' ) . '</th><th>' . esc_html__( 'Service', 'sm-ai' ) . '</th><th>' . esc_html__( 'Provider · model', 'sm-ai' ) . '</th><th>' . esc_html__( 'Company', 'sm-ai' ) . '</th><th>' . esc_html__( 'State', 'sm-ai' ) . '</th><th>' . esc_html__( 'Tokens', 'sm-ai' ) . '</th><th>' . esc_html__( 'Time', 'sm-ai' ) . '</th></tr></thead><tbody>';
		$rows = Usage::recent( 50 );
		if ( ! $rows ) {
			echo '<tr><td colspan="7">—</td></tr>';
		}
		foreach ( $rows as $r ) {
			$c = $r->company_id ? Companies::get( (int) $r->company_id ) : null;
			echo '<tr><td>' . esc_html( get_date_from_gmt( $r->created_at, 'Y-m-d H:i' ) ) . '</td><td>' . esc_html( $fl[ $r->feature ] ?? $r->feature ) . '</td><td>' . esc_html( trim( $r->provider . ' · ' . $r->model, ' ·' ) ) . '</td><td>' . esc_html( $c ? $c->name : ( $r->user_id ? '—' : __( 'Visitor', 'sm-ai' ) ) ) . '</td>';
			echo '<td>' . esc_html( $r->status ) . ( $r->error ? '<br><span class="description">' . esc_html( $r->error ) . '</span>' : '' ) . '</td><td>' . esc_html( $r->tokens_in . ' / ' . $r->tokens_out ) . '</td><td>' . esc_html( number_format_i18n( $r->duration_ms / 1000, 1 ) ) . ' s</td></tr>';
		}
		echo '</tbody></table>';
	}

	public static function save() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You cannot change these settings.', 'sm-ai' ) );
		}
		check_admin_referer( 'sm_ai_settings' );
		Settings::save( (array) wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized in Settings::save().
		AuditLog::add( 'ai.settings', 'settings', 0, array() );
		wp_safe_redirect( self::url( array( 'updated' => 1 ) ) );
		exit;
	}

	public static function test() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You cannot change these settings.', 'sm-ai' ) );
		}
		check_admin_referer( 'sm_ai_test' );
		$res = Gateway::test( sanitize_key( wp_unslash( $_POST['provider'] ?? '' ) ) );
		wp_safe_redirect( self::url( is_wp_error( $res ) ? array( 'test' => 'fail', 'err' => rawurlencode( mb_substr( $res->get_error_message(), 0, 200 ) ) ) : array( 'test' => 'ok' ) ) );
		exit;
	}
}
