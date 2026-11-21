<?php
/**
 * Summary of the Business report (0.6.0, sm-insights): « What to remember » in 3
 * or 4 short points, written from the figures computed by the platform. The AI
 * computes nothing and invents no figure; it never speaks of sales quantities.
 * Free (no credit): the report belongs to the Business plan, checked by sm-insights.
 *
 * Filter sm_ai_report_summary( null, array $figures, string $lang ) → string[] | WP_Error.
 *
 * @package SM\Ai
 */

namespace SM\Ai\Features;

defined( 'ABSPATH' ) || exit;

final class Report extends Feature {

	const MAX_FACTS = 6000;

	public static function id() {
		return 'report';
	}

	public static function label() {
		return __( 'Business report summary', 'sm-ai' );
	}

	public static function credits() {
		return 0;
	}

	public static function allowed( $company ) {
		if ( ! is_user_logged_in() || ! $company ) {
			return new \WP_Error( 'sm_ai_account', __( 'Sign in with a business account to use the AI assistant.', 'sm-ai' ) );
		}
		return true;
	}

	public static function system( $lang ) {
		return "You write the summary « What to remember » of a monthly report on the steel market of ReuSteel, a steel marketplace in Algeria.\n"
			. "Rules:\n"
			. "- Use ONLY the figures given below, computed by the platform. Never compute a new figure, never round differently, never add a fact, a cause or a forecast.\n"
			. "- Prices are prices asked by sellers on ReuSteel (DA/kg), not market or transaction prices: say « asked prices » or « displayed prices ».\n"
			. "- Never give a number of sales, a sold quantity or a sales amount. The best-selling list is a ranking only.\n"
			. "- Never name a company or a person.\n"
			. "- A change of the buildings share can come from a single building: do not call it a trend.\n"
			. "- 3 or 4 points, one short sentence each, the most useful first (prices that move, products in demand, reuse).\n"
			. '- Write in ' . self::lang_name( $lang ) . ". Keep designations and units as written (IPE 200, DA/kg, t, %).\n"
			. "- Answer with ONE JSON object only: {\"points\": [\"…\", \"…\"]}.\n";
	}

	public static function parts( array $input ) {
		return array( array( 'type' => 'text', 'text' => "Figures of the report:\n" . self::facts( $input ) ) );
	}

	public static function facts( array $input ) {
		return mb_substr( (string) wp_json_encode( $input['figures'] ?? array(), JSON_UNESCAPED_UNICODE ), 0, self::MAX_FACTS );
	}

	public static function clean( array $j, array $input ) {
		$out = array();
		foreach ( self::list( $j['points'] ?? array(), 4, 220 ) as $p ) {
			// A number of sales is never shown, even if the model writes one.
			if ( preg_match( '/\d[\d\s.,]*\s*(ventes?|sales?|vendus?|مبيع|بيع)/iu', $p ) ) {
				continue;
			}
			$out[] = $p;
		}
		return array( 'points' => $out );
	}

	public static function simulate( array $input ) {
		$f   = (array) ( $input['figures'] ?? array() );
		$pts = array();
		// Keys of the figures sent by sm-insights (Summary::facts), or the short test ones.
		foreach ( (array) ( $f['prices_DA_kg'] ?? $f['prices'] ?? array() ) as $row ) {
			if ( isset( $row['new_change'] ) && null !== $row['new_change'] ) {
				$pts[] = sprintf( 'Prix demandés des %s : %s %% sur la période.', $row['label'], str_replace( '.', ',', (string) $row['new_change'] ) );
				break;
			}
		}
		if ( ! empty( $f['top_searched'][0] ) ) {
			$pts[] = sprintf( '%s est le plus recherché.', preg_replace( '/:\s*\d+$/', '', (string) $f['top_searched'][0] ) );
		}
		$reuse = $f['weights_on_sale']['reuse_pct'] ?? ( $f['weights']['reuse_pct'] ?? null );
		if ( null !== $reuse ) {
			$pts[] = sprintf( 'Le réemploi représente %s %% du poids mis en vente.', str_replace( '.', ',', (string) $reuse ) );
		}
		$pts[] = 'Il y a eu 12 ventes de IPE 200.'; // Must be dropped by clean().
		return wp_json_encode( array( 'points' => $pts ) );
	}

	/** Filter sm_ai_report_summary. */
	public static function summary( $done, $figures, $lang = '' ) {
		if ( null !== $done ) {
			return $done;
		}
		$res = \SM\Ai\Gateway::run( 'report', array( 'figures' => (array) $figures ) );
		return is_wp_error( $res ) ? $res : $res['points'];
	}
}
