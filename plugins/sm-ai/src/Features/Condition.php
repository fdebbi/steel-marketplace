<?php
/**
 * Condition of reused steel from the photos (0.5.0): a suggestion of the
 * condition (excellent, good, to inspect, scrap) and of the surface, with what
 * the AI sees and what it cannot see. A visual estimate, never an inspection:
 * the seller decides, and « excellent » needs clear close photos — otherwise the
 * suggestion is « to inspect ».
 *
 * @package SM\Ai
 */

namespace SM\Ai\Features;

defined( 'ABSPATH' ) || exit;

final class Condition extends Feature {

	const CONDITIONS = array( 'excellent', 'good', 'inspection', 'scrap' );
	const SURFACES   = array( 'clean', 'light_rust', 'painted', 'galvanized', 'heavy_corrosion' );

	public static function id() {
		return 'condition';
	}

	public static function label() {
		return __( 'Condition of the steel', 'sm-ai' );
	}

	public static function allowed( $company ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error( 'sm_ai_account', __( 'Sign in to use the AI assistant.', 'sm-ai' ) );
		}
		if ( ! $company ) {
			return new \WP_Error( 'sm_ai_account', __( 'Sign in with a business account to use the listing assistant.', 'sm-ai' ) );
		}
		return true;
	}

	public static function system( $lang ) {
		return "You look at photos of reused steel (beams, tubes, plates, offcuts) offered on a marketplace and suggest its visible condition.\n"
			. self::common_rules( $lang )
			. "- condition: one of excellent (like new, no rust, straight), good (light surface rust, straight, reusable), inspection (doubt: corrosion, deformation, cuts or welds to check, or photos not clear enough), scrap (only good for recycling).\n"
			. "- Never say excellent unless close, sharp photos show the surfaces; when in doubt choose inspection. You cannot certify anything.\n"
			. "- surface: one of clean, light_rust, painted, galvanized, heavy_corrosion.\n"
			. "- close_photos: true only if at least one photo shows the surface close and sharp.\n"
			. "- sees: up to 5 short observations (rust, straightness, holes, welds, paint…). cannot_see: what photos cannot show (remaining thickness, internal defects, grade…).\n"
			. "JSON keys: condition, surface, close_photos, confidence (0 to 1), sees (list), cannot_see (list).\n";
	}

	public static function parts( array $input ) {
		return array_merge( self::files( $input ), array( array( 'type' => 'text', 'text' => 'Suggest the visible condition of this steel.' ) ) );
	}

	public static function clean( array $j, array $input ) {
		$cond  = in_array( $j['condition'] ?? '', self::CONDITIONS, true ) ? $j['condition'] : 'inspection';
		$close = ! empty( $j['close_photos'] );
		if ( 'excellent' === $cond && ! $close ) {
			$cond = 'inspection'; // No « excellent » without clear close photos: in doubt, to inspect.
		}
		return array(
			'condition'    => $cond,
			'surface'      => in_array( $j['surface'] ?? '', self::SURFACES, true ) ? $j['surface'] : '',
			'close_photos' => $close,
			'confidence'   => self::confidence( $j['confidence'] ?? 0 ),
			'sees'         => self::list( $j['sees'] ?? array(), 5, 160 ),
			'cannot_see'   => self::list( $j['cannot_see'] ?? array(), 5, 120 ),
		);
	}

	public static function simulate( array $input ) {
		return wp_json_encode(
			array(
				'condition'    => 'good',
				'surface'      => 'light_rust',
				'close_photos' => true,
				'confidence'   => 0.7,
				'sees'         => array( 'Rouille légère et uniforme', 'Pas de corrosion feuilletante visible', 'Ailes droites', 'Trous de boulons sur une extrémité' ),
				'cannot_see'   => array( 'Épaisseur restante', 'Déformations', 'Soudures', 'Nuance' ),
			)
		);
	}
}
