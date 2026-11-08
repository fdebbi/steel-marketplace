<?php
/**
 * Simulation provider: no external call. Each feature gives a plausible
 * answer built from the user's input (Feature::simulate). Offered only when
 * SM_AI_SIMULATION is true (tests, demonstrations); the interface marks the
 * results "Simulation".
 *
 * @package SM\Ai
 */

namespace SM\Ai\Providers;

defined( 'ABSPATH' ) || exit;

final class Simulation extends Provider {

	public static function complete( array $req ) {
		$text = isset( $req['simulate'] ) && is_callable( $req['simulate'] ) ? (string) call_user_func( $req['simulate'] ) : '{"ok":true}';
		$in   = (int) ceil( strlen( (string) $req['system'] . wp_json_encode( array_column( (array) $req['parts'], 'text' ) ) ) / 4 );
		return self::result( true, $text, $in, (int) ceil( strlen( $text ) / 4 ), '', 200 );
	}
}
