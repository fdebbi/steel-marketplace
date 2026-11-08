<?php
/**
 * One AI service. A feature says who may use it, what is sent to the model
 * (instructions + parts), and keeps from the answer only what the platform
 * can check (Catalog). It also gives the simulated answer used in tests.
 *
 * @package SM\Ai
 */

namespace SM\Ai\Features;

use SM\Ai\Catalog;

defined( 'ABSPATH' ) || exit;

abstract class Feature {

	/** Short id (listing, recognize, search, document). */
	abstract public static function id();

	abstract public static function label();

	/** True or WP_Error explaining why the current visitor cannot use it. */
	abstract public static function allowed( $company );

	/** Instructions to the model (English; it answers in $lang). */
	abstract public static function system( $lang );

	/** Content parts sent with the request. */
	abstract public static function parts( array $input );

	/** Checked result from the decoded JSON answer. */
	abstract public static function clean( array $json, array $input );

	/** JSON text a plausible model would return (simulation provider). */
	abstract public static function simulate( array $input );

	/** Visitors without an account may use it. */
	public static function public_feature() {
		return false;
	}

	protected static function lang_name( $lang ) {
		return 'ar' === $lang ? 'Arabic (Modern Standard Arabic)' : 'French';
	}

	protected static function common_rules( $lang ) {
		return "You work for ReuSteel, a steel marketplace in Algeria (new steel, reused steel, steel buildings).\n"
			. "Rules:\n"
			. "- Never give weights, masses, prices, structural capacities or certifications: the platform computes weights with its own engine and engineering is done by engineers.\n"
			. "- Never invent facts that are not visible or stated; when unsure, say so and ask.\n"
			. "- Use only the codes listed below for markets, categories, profile families and grades.\n"
			. '- Write the free texts (questions, warnings, explanations) in ' . static::lang_name( $lang ) . ".\n"
			. "- Answer with ONE JSON object only, no text before or after.\n";
	}

	/** Trimmed single-line text. */
	protected static function str( $v, $max = 200 ) {
		return mb_substr( trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) ( is_scalar( $v ) ? $v : '' ) ) ) ), 0, $max );
	}

	/** Trimmed multi-line text. */
	protected static function text( $v, $max = 2000 ) {
		return mb_substr( trim( sanitize_textarea_field( (string) ( is_scalar( $v ) ? $v : '' ) ) ), 0, $max );
	}

	/** List of short texts. */
	protected static function list( $v, $max_items = 8, $max_len = 200 ) {
		$out = array();
		foreach ( array_slice( (array) $v, 0, $max_items ) as $item ) {
			$t = self::str( $item, $max_len );
			if ( '' !== $t ) {
				$out[] = $t;
			}
		}
		return $out;
	}

	protected static function confidence( $v ) {
		return max( 0.0, min( 1.0, round( (float) $v, 2 ) ) );
	}

	/** User notes part. */
	protected static function notes_part( array $input, $label ) {
		$notes = trim( (string) ( $input['notes'] ?? '' ) );
		return '' !== $notes ? array( array( 'type' => 'text', 'text' => $label . ":\n" . $notes ) ) : array();
	}

	protected static function files( array $input ) {
		return array_values( (array) ( $input['files'] ?? array() ) );
	}

	/** A catalogue section as data for the interface (mass per metre from the engine). */
	protected static function section_data( $s ) {
		return array( 'section_id' => (int) $s->id, 'family' => $s->family, 'designation' => Catalog::label( $s ), 'mass_kg_m' => (float) $s->mass_kg_m );
	}
}
