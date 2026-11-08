<?php
/**
 * AI listing assistant: from photos and notes of the seller, proposes the
 * market, category, profile, grade, condition, titles and description, and
 * lists the missing information. The listing form is then pre-filled; the
 * weight is computed by the engine on the form, never by the AI.
 *
 * @package SM\Ai
 */

namespace SM\Ai\Features;

use SM\Ai\Catalog;

defined( 'ABSPATH' ) || exit;

final class Listing extends Feature {

	public static function id() {
		return 'listing';
	}

	public static function label() {
		return __( 'Listing assistant', 'sm-ai' );
	}

	/** Markets the company may publish in. */
	public static function markets_allowed( $company ) {
		$out = array();
		if ( ! $company || ! class_exists( '\SM\Products\Markets' ) ) {
			return $out;
		}
		foreach ( \SM\Products\Markets::all() as $k => $m ) {
			if ( sm_can( $m['cap'], (int) $company->id ) ) {
				$out[] = $k;
			}
		}
		return $out;
	}

	public static function allowed( $company ) {
		if ( ! $company ) {
			return new \WP_Error( 'sm_ai_account', __( 'Sign in with a business account to use the listing assistant.', 'sm-ai' ) );
		}
		if ( ! Catalog::has_products() || ! self::markets_allowed( $company ) ) {
			return new \WP_Error( 'sm_ai_cap', __( 'Your account cannot publish listings.', 'sm-ai' ) );
		}
		return true;
	}

	public static function system( $lang ) {
		return "You are the listing assistant of the marketplace. From the seller's photos and notes, prepare a listing.\n"
			. self::common_rules( $lang )
			. "- Titles: short and factual, e.g. \"IPE 200 S275JR — 12 m bars\" / \"عوارض IPE 200 S275JR — قضبان 12 م\".\n"
			. "- Descriptions: 2 to 5 sentences on what is visible or stated (product, condition, quantity stated by the seller, storage, delivery if stated). No price, no weight.\n"
			. "- If the photo shows a profile and its size can be read or is stated, give family and designation; otherwise leave them empty.\n"
			. "JSON keys: market, category, family, designation, grade, condition, title_fr, title_ar, description_fr, description_ar, missing (list of questions to ask the seller), warnings (list), confidence (0 to 1).\n\n"
			. Catalog::context( array( 'categories', 'families', 'grades' ) );
	}

	public static function parts( array $input ) {
		$parts = array();
		if ( ! empty( $input['market'] ) ) {
			$parts[] = array( 'type' => 'text', 'text' => 'The seller chose the market "' . $input['market'] . '".' );
		}
		$parts = array_merge( $parts, self::notes_part( $input, 'Seller notes' ), self::files( $input ) );
		if ( ! $parts ) {
			$parts[] = array( 'type' => 'text', 'text' => 'No information given.' );
		}
		return $parts;
	}

	public static function clean( array $j, array $input ) {
		$company = $input['company'] ?? null;
		$allowed = self::markets_allowed( $company );
		$cats    = Catalog::categories();
		$cat     = (string) ( $j['category'] ?? '' );
		$market  = (string) ( $j['market'] ?? ( $input['market'] ?? '' ) );
		if ( isset( $cats[ $cat ] ) ) {
			$market = $cats[ $cat ]['market'];
		} else {
			$cat = '';
		}
		if ( ! in_array( $market, $allowed, true ) ) {
			$market = in_array( (string) ( $input['market'] ?? '' ), $allowed, true ) ? $input['market'] : ( $allowed[0] ?? '' );
			$cat    = '';
		}
		$family  = strtoupper( self::str( $j['family'] ?? '', 10 ) );
		$section = null;
		if ( $cat && in_array( $family, $cats[ $cat ]['families'], true ) ) {
			$section = Catalog::section( $family, self::str( $j['designation'] ?? '', 40 ) );
		} else {
			$family = '';
		}
		$grade = strtoupper( self::str( $j['grade'] ?? '', 12 ) );
		$grade = in_array( $grade, Catalog::grades(), true ) ? $grade : '';
		$conds = Catalog::markets()[ $market ]['conditions'] ?? array();
		$cond  = (string) ( $j['condition'] ?? '' );
		$cond  = in_array( $cond, $conds, true ) ? $cond : '';
		return array(
			'market'         => $market,
			'category'       => $cat,
			'category_name'  => $cat ? array( 'fr' => $cats[ $cat ]['name_fr'], 'ar' => $cats[ $cat ]['name_ar'] ) : array(),
			'family'         => $family,
			'section'        => $section ? self::section_data( $section ) : null,
			'grade'          => $grade,
			'condition'      => $cond,
			'title'          => array( 'fr' => self::str( $j['title_fr'] ?? '', 140 ), 'ar' => self::str( $j['title_ar'] ?? '', 140 ) ),
			'description'    => array( 'fr' => self::text( $j['description_fr'] ?? '', 2000 ), 'ar' => self::text( $j['description_ar'] ?? '', 2000 ) ),
			'missing'        => self::list( $j['missing'] ?? array() ),
			'warnings'       => self::list( $j['warnings'] ?? array() ),
			'confidence'     => self::confidence( $j['confidence'] ?? 0 ),
		);
	}

	/** Values for the listing form (pre-fill). */
	public static function form_values( array $r, $lang ) {
		$v = array(
			'market'      => $r['market'],
			'category'    => $r['category'],
			'title'       => $r['title'],
			'description' => $r['description'][ $lang ] ?: ( $r['description']['fr'] ?: $r['description']['ar'] ),
		);
		if ( $r['condition'] ) {
			$v['condition'] = $r['condition'];
		}
		if ( $r['grade'] ) {
			$v['grade'] = $r['grade'];
		}
		if ( $r['section'] ) {
			$cats = Catalog::categories();
			if ( 'engine' === ( $cats[ $r['category'] ]['kind'] ?? '' ) ) {
				$v['family']  = $r['section']['family'];
				$v['section'] = (string) $r['section']['section_id'];
			} else {
				$v['lines'] = array( array( 'family' => $r['section']['family'], 'section' => (string) $r['section']['section_id'], 'qty' => '', 'length' => '' ) );
			}
		}
		return $v;
	}

	public static function simulate( array $input ) {
		$notes = (string) ( $input['notes'] ?? '' );
		$found = Catalog::detect( $notes );
		$cats  = Catalog::categories( ! empty( $input['market'] ) ? $input['market'] : null );
		$cat   = '';
		foreach ( $cats as $slug => $c ) {
			if ( $found && in_array( $found[0], $c['families'], true ) ) {
				$cat = $slug;
				break;
			}
		}
		$cat   = $cat ?: (string) array_key_first( $cats );
		$des   = $found ? Catalog::label( $found[1] ) : '';
		$grade = preg_match( '/S\d{3}J[R02]H?/i', $notes, $m ) ? strtoupper( $m[0] ) : '';
		return wp_json_encode(
			array(
				'market'         => $cats[ $cat ]['market'] ?? '',
				'category'       => $cat,
				'family'         => $found ? $found[0] : '',
				'designation'    => $des,
				'grade'          => $grade,
				'condition'      => '',
				'title_fr'       => trim( ( $des ?: 'Lot d’acier' ) . ' ' . $grade ),
				'title_ar'       => trim( ( $des ?: 'دفعة من الفولاذ' ) . ' ' . $grade ),
				'description_fr' => 'Produit visible sur les photos : ' . ( $des ?: 'profilés en acier' ) . '. ' . ( '' !== trim( $notes ) ? 'Précisions du vendeur : ' . trim( $notes ) : '' ),
				'description_ar' => 'المنتج الظاهر في الصور: ' . ( $des ?: 'مقاطع فولاذية' ) . '.',
				'missing'        => array( 'Quelle est la longueur des barres ?', 'Combien de pièces sont disponibles ?' ),
				'warnings'       => array(),
				'confidence'     => $found ? 0.8 : 0.4,
			)
		);
	}
}
