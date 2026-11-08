<?php
/**
 * Steel recognition assistance: possible profile types visible on photos.
 * Each candidate is checked against the catalogue; the result is always a
 * suggestion to confirm, never a certified identification.
 *
 * @package SM\Ai
 */

namespace SM\Ai\Features;

use SM\Ai\Catalog;

defined( 'ABSPATH' ) || exit;

final class Recognize extends Feature {

	public static function id() {
		return 'recognize';
	}

	public static function label() {
		return __( 'Profile recognition', 'sm-ai' );
	}

	public static function allowed( $company ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error( 'sm_ai_account', __( 'Sign in to use the AI assistant.', 'sm-ai' ) );
		}
		return Catalog::has_engine() ? true : new \WP_Error( 'sm_ai_engine', __( 'The weight engine is not active.', 'sm-ai' ) );
	}

	public static function system( $lang ) {
		return "You help identify steel sections on photos (beams, channels, angles, tubes, bars, plates).\n"
			. self::common_rules( $lang )
			. "- Propose up to 4 possible profile types, the most likely first, with a confidence between 0 and 1 and a short reason (shape of the flanges, taper, markings, visible dimensions).\n"
			. "- Give a designation (e.g. \"IPE 200\") only if a marking or a measurement allows it; otherwise leave it empty.\n"
			. "- Ask the questions that would remove the doubt (e.g. measure the height and the flange width).\n"
			. "JSON keys: candidates (list of {family, designation, confidence, reason}), questions (list), note.\n\n"
			. Catalog::context( array( 'families' ) );
	}

	public static function parts( array $input ) {
		return array_merge( self::notes_part( $input, 'Notes of the user' ), self::files( $input ) );
	}

	public static function clean( array $j, array $input ) {
		$fams = Catalog::families();
		$out  = array();
		foreach ( array_slice( (array) ( $j['candidates'] ?? array() ), 0, 6 ) as $c ) {
			$f = strtoupper( self::str( $c['family'] ?? '', 10 ) );
			if ( ! isset( $fams[ $f ] ) ) {
				continue;
			}
			$s     = Catalog::section( $f, self::str( $c['designation'] ?? '', 40 ) );
			$out[] = array(
				'family'      => $f,
				'family_name' => $fams[ $f ],
				'section'     => $s ? self::section_data( $s ) : null,
				'confidence'  => self::confidence( $c['confidence'] ?? 0 ),
				'reason'      => self::str( $c['reason'] ?? '', 240 ),
			);
		}
		usort( $out, static fn( $a, $b ) => $b['confidence'] <=> $a['confidence'] );
		return array(
			'candidates' => array_slice( $out, 0, 4 ),
			'questions'  => self::list( $j['questions'] ?? array(), 5 ),
			'note'       => self::str( $j['note'] ?? '', 300 ),
		);
	}

	public static function simulate( array $input ) {
		$found = Catalog::detect( (string) ( $input['notes'] ?? '' ) );
		$first = $found ? array( 'family' => $found[0], 'designation' => Catalog::label( $found[1] ), 'confidence' => 0.7, 'reason' => 'Ailes parallèles et marquage lisible.' ) : array( 'family' => 'IPE', 'designation' => 'IPE 200', 'confidence' => 0.62, 'reason' => 'Ailes parallèles, âme mince, proportions d’un IPE.' );
		return wp_json_encode(
			array(
				'candidates' => array(
					$first,
					array( 'family' => 'HEA', 'designation' => '', 'confidence' => 0.21, 'reason' => 'Ailes larges possibles selon l’angle de vue.' ),
					array( 'family' => 'XYZ', 'designation' => 'XYZ 1', 'confidence' => 0.5, 'reason' => 'Famille inconnue (doit être écartée).' ),
				),
				'questions'  => array( 'Mesurez la hauteur totale et la largeur des ailes.', 'Y a-t-il un marquage laminé sur l’âme ?' ),
				'note'       => 'Suggestion à confirmer.',
			)
		);
	}
}
