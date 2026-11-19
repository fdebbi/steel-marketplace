<?php
/**
 * Description writer (0.2.0): in the publication form, the AI turns what the
 * seller already entered (product, condition, quantities, lengths, weight
 * computed by the engine, place…) into a clear description. It uses only
 * these facts; the seller edits the text before publishing.
 *
 * @package SM\Ai
 */

namespace SM\Ai\Features;

use SM\Ai\Catalog;

defined( 'ABSPATH' ) || exit;

final class Describe extends Feature {

	/** Longest text of facts sent to the model. */
	const MAX_FACTS = 3000;

	public static function id() {
		return 'describe';
	}

	public static function label() {
		return __( 'Listing description', 'sm-ai' );
	}

	public static function allowed( $company ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error( 'sm_ai_account', __( 'Sign in to use the AI assistant.', 'sm-ai' ) );
		}
		if ( ! $company ) {
			return new \WP_Error( 'sm_ai_account', __( 'Sign in with a business account to use the listing assistant.', 'sm-ai' ) );
		}
		if ( ! Catalog::has_products() || ! Listing::markets_allowed( $company ) ) {
			return new \WP_Error( 'sm_ai_cap', __( 'Your account cannot publish listings.', 'sm-ai' ) );
		}
		return true;
	}

	public static function system( $lang ) {
		return "You write the description of a listing on ReuSteel, a steel marketplace in Algeria.\n"
			. "Rules:\n"
			. "- Use ONLY the facts given by the seller below. Never add a fact, a standard, a grade, a dimension, a quantity, a weight, a price or a condition that is not in them.\n"
			. "- You may repeat the figures given (weights computed by the platform, lengths, quantities) exactly as written; never compute new ones.\n"
			. "- No contact details, no phone number, no e-mail, no link.\n"
			. "- Clear and professional, for buyers of steel: what it is, condition, quantity and lengths, place and pickup or delivery if stated, then the useful technical notes. 3 to 7 sentences, short paragraphs or a short list.\n"
			. '- Write the description in ' . self::lang_name( $lang ) . ". Keep profile designations, grades and units as written (IPE 200, S275JR, kg, m).\n"
			. "- Answer with ONE JSON object only: {\"description\": \"…\"}.\n";
	}

	public static function parts( array $input ) {
		return array( array( 'type' => 'text', 'text' => "Facts entered by the seller:\n" . self::facts( $input ) ) );
	}

	/** Facts as plain lines, cleaned and limited. */
	public static function facts( array $input ) {
		return mb_substr( trim( sanitize_textarea_field( (string) ( $input['facts'] ?? '' ) ) ), 0, self::MAX_FACTS );
	}

	public static function clean( array $j, array $input ) {
		$text = self::text( $j['description'] ?? '', 4000 );
		// Contact details are never kept, even if the model writes some.
		$text = preg_replace( '/[\w.+-]+@[\w-]+\.[\w.]+/u', '', $text );
		$text = preg_replace( '/(?:\+?213|0)[\s.\-]?[5-7](?:[\s.\-]?\d){8}/u', '', $text );
		$text = preg_replace( '#https?://\S+#u', '', $text );
		return array( 'description' => trim( $text ) );
	}

	public static function simulate( array $input ) {
		$lines = array_filter( array_map( 'trim', explode( "\n", self::facts( $input ) ) ) );
		$text  = "Annonce préparée à partir de vos informations :\n- " . implode( "\n- ", array_slice( $lines, 0, 12 ) ) . "\nMarchandise visible sur place ; vérifiez et complétez ce texte avant de publier.";
		return wp_json_encode( array( 'description' => $text ) );
	}
}
