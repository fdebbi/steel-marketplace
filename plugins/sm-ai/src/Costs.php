<?php
/**
 * Costs and margin of the AI services (0.7.0). The user always sees a fixed
 * price in credits before the click; the real cost (tokens × prices of the
 * provider, kept for each call) only serves the administration:
 *  - value of one credit in DA (filter sm_ai_credit_value: sm-subscriptions gives
 *    the cheapest pack per credit, the least favourable case for the margin);
 *  - 1 US dollar in DA typed by the administrator (no rate is invented: without
 *    it, the margin is not computed);
 *  - monthly budget of the platform: e-mail at the alert level and at 100 %.
 *
 * @package SM\Ai
 */

namespace SM\Ai;

defined( 'ABSPATH' ) || exit;

final class Costs {

	const ALERT_OPTION = 'sm_ai_budget_alerts';

	/** Value of one credit in DA (0 = unknown). */
	public static function credit_value() {
		return max( 0, (float) apply_filters( 'sm_ai_credit_value', 0 ) );
	}

	/** 1 US dollar in DA (0 = not typed). */
	public static function rate() {
		return (float) Settings::get( 'usd_rate' );
	}

	/** [ start, end ) of a month 'Y-m' (UTC, as the log). */
	public static function month_range( $ym ) {
		$start = get_gmt_from_date( $ym . '-01 00:00:00' );
		$end   = get_gmt_from_date( gmdate( 'Y-m-01 00:00:00', strtotime( $ym . '-01 +1 month' ) ) );
		return array( $start, $end );
	}

	/** Months 'Y-m', the current one first. */
	public static function months( $n = 6 ) {
		$out = array();
		$t   = strtotime( wp_date( 'Y-m-15' ) );
		for ( $i = 0; $i < $n; $i++ ) {
			$out[] = gmdate( 'Y-m', strtotime( "-$i month", $t ) );
		}
		return $out;
	}

	/**
	 * Per service for a month: uses, credits charged, real cost ($), and with a
	 * rate and a credit value: cost in DA, income per use, margin, advice.
	 */
	public static function by_feature( $ym = '' ) {
		global $wpdb;
		$ym    = $ym ? $ym : wp_date( 'Y-m' );
		$r     = self::month_range( $ym );
		$rows  = $wpdb->get_results( $wpdb->prepare( 'SELECT feature, COUNT(*) n, COALESCE(SUM(credits),0) credits, COALESCE(SUM(cost),0) cost FROM ' . Usage::table() . " WHERE status = 'ok' AND created_at >= %s AND created_at < %s GROUP BY feature", $r[0], $r[1] ), OBJECT_K ); // phpcs:ignore WordPress.DB
		$value = self::credit_value();
		$rate  = self::rate();
		$aim   = (int) Settings::get( 'target_margin' );
		$out   = array();
		foreach ( Settings::FEATURES as $f ) {
			$x       = $rows[ $f ] ?? null;
			$uses    = $x ? (int) $x->n : 0;
			$credits = $x ? (int) $x->credits : 0;
			$cost    = $x ? (float) $x->cost : 0.0;
			$price   = Gateway::credits( $f );
			$avg     = $uses ? $cost / $uses : null;
			$row     = array(
				'feature'  => $f,
				'price'    => $price,
				'uses'     => $uses,
				'credits'  => $credits,
				'cost'     => round( $cost, 6 ),
				'avg_usd'  => null === $avg ? null : round( $avg, 6 ),
				'avg_da'   => null === $avg || $rate <= 0 ? null : round( $avg * $rate, 2 ),
				'income'   => $price > 0 && $value > 0 ? round( $price * $value, 2 ) : null,
				'margin'   => null,
				'cost_da'  => $rate > 0 ? round( $cost * $rate, 2 ) : null,
				'advice'   => null,
			);
			if ( null !== $row['avg_da'] && null !== $row['income'] && $row['income'] > 0 ) {
				$row['margin'] = round( ( $row['income'] - $row['avg_da'] ) / $row['income'] * 100, 1 );
				if ( $row['margin'] < $aim && $value > 0 ) {
					// Fewest credits that reach the margin aimed at.
					$need = (int) ceil( $row['avg_da'] / ( $value * max( 0.05, 1 - $aim / 100 ) ) - 1e-9 );
					$need = max( $price + 1, $need );
					$row['advice'] = array( 'credits' => $need, 'margin' => round( ( $need * $value - $row['avg_da'] ) / ( $need * $value ) * 100, 1 ) );
				}
			}
			$out[ $f ] = $row;
		}
		return $out;
	}

	/** Totals of a month: cost ($ and DA), credits charged (count and DA), margin, share of the budget. */
	public static function totals( $ym = '' ) {
		$rows    = self::by_feature( $ym );
		$cost    = array_sum( array_column( $rows, 'cost' ) );
		$credits = array_sum( array_column( $rows, 'credits' ) );
		$value   = self::credit_value();
		$rate    = self::rate();
		$income  = $value > 0 ? $credits * $value : null;
		$cost_da = $rate > 0 ? $cost * $rate : null;
		$budget  = (float) Settings::get( 'monthly_budget' );
		$under   = 0;
		foreach ( $rows as $r ) {
			if ( null !== $r['advice'] && $r['uses'] > 0 ) {
				++$under;
			}
		}
		return array(
			'cost'        => round( $cost, 4 ),
			'cost_da'     => null === $cost_da ? null : round( $cost_da ),
			'credits'     => $credits,
			'income'      => null === $income ? null : round( $income ),
			'margin_da'   => null === $income || null === $cost_da ? null : round( $income - $cost_da ),
			'margin'      => null === $income || null === $cost_da || $income <= 0 ? null : round( ( $income - $cost_da ) / $income * 100, 1 ),
			'budget_used' => $budget > 0 ? round( $cost / $budget * 100, 1 ) : null,
			'under'       => $under,
		);
	}

	/**
	 * After each answer: e-mail to the administrators when the month reaches the
	 * alert level, then 100 % of the budget (once each per month).
	 */
	public static function watch_budget() {
		$s = Settings::all();
		if ( (float) $s['monthly_budget'] <= 0 ) {
			return;
		}
		$pct  = Usage::month_cost() / (float) $s['monthly_budget'] * 100;
		$ym   = wp_date( 'Y-m' );
		$sent = (array) get_option( self::ALERT_OPTION, array() );
		foreach ( array( 'full' => 100, 'alert' => (int) $s['budget_alert'] ) as $k => $level ) {
			if ( $pct >= $level ) {
				if ( ( $sent[ $k ] ?? '' ) !== $ym ) {
					$sent[ $k ] = $ym;
					if ( 'full' === $k ) {
						$sent['alert'] = $ym; // No second mail for the alert.
					}
					update_option( self::ALERT_OPTION, $sent, false );
					self::mail( $k, $pct, $s );
				}
				return;
			}
		}
	}

	private static function mail( $kind, $pct, array $s ) {
		if ( ! class_exists( '\SM\Core\Mail\Mailer' ) ) {
			return;
		}
		$lang = class_exists( '\SM\Core\Mail\Activity' ) ? \SM\Core\Mail\Activity::admin_lang() : 'fr';
		$to   = class_exists( '\SM\Core\Mail\Settings' ) ? \SM\Core\Mail\Settings::admin_emails() : array();
		if ( ! $to ) {
			$to = array( get_option( 'admin_email' ) );
		}
		$url = add_query_arg( array( 'page' => 'sm-ai', 'tab' => 'costs' ), admin_url( 'admin.php' ) );
		\SM\Core\I18n\Language::with_language(
			$lang,
			static function () use ( $kind, $pct, $s, $to, $lang, $url ) {
				$pause   = 'warn' !== $s['budget_mode'];
				$subject = 'full' === $kind ? __( 'AI assistant: the monthly budget is reached', 'sm-ai' ) : __( 'AI assistant: alert on the monthly budget', 'sm-ai' );
				$body    = '<p>' . esc_html( sprintf( /* translators: 1: percentage, 2: budget in dollars. */ __( 'The AI costs of this month reach %1$s %% of the budget (%2$s $).', 'sm-ai' ), number_format_i18n( $pct, 0 ), number_format_i18n( (float) $s['monthly_budget'], 2 ) ) ) . '</p>';
				if ( 'full' === $kind ) {
					$body .= '<p>' . esc_html( $pause ? __( 'The AI tools that use credits are paused until the 1st of next month (free tools keep working). You can raise the budget.', 'sm-ai' ) : __( 'The AI tools keep working (warning only).', 'sm-ai' ) ) . '</p>';
				}
				$body .= class_exists( '\\SM\\Core\\Mail\\Templates' ) ? \SM\Core\Mail\Templates::button( __( 'See the costs and the margin', 'sm-ai' ), $url ) : '<p><a href="' . esc_url( $url ) . '">' . esc_html__( 'See the costs and the margin', 'sm-ai' ) . '</a></p>';
				foreach ( $to as $email ) {
					\SM\Core\Mail\Mailer::send( $email, $lang, $subject, $body, array( 'event' => 'ai.budget' ) );
				}
			}
		);
	}
}
