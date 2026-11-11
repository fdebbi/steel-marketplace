<?php
/**
 * Checks of the service, request, quote and reference forms. Texts are stored
 * under the language they were written in.
 *
 * Service input: kind, categories[], title[fr|ar], description, capabilities,
 * equipment, coverage (all|list), wilayas[], lead_days, price_mode (quote|from),
 * price_from, price_unit.
 *
 * @package SM\Services
 */

namespace SM\Services;

use SM\Core\Accounts\Wilayas;

defined( 'ABSPATH' ) || exit;

final class Spec {

	public static function by_lang( $text ) {
		if ( '' === $text ) {
			return array( 'fr' => '', 'ar' => '' );
		}
		$ar = preg_match_all( '/\p{Arabic}/u', $text );
		$la = preg_match_all( '/\p{Latin}/u', $text );
		return $ar > $la ? array( 'ar' => $text, 'fr' => '' ) : array( 'fr' => $text, 'ar' => '' );
	}

	private static function num( $v ) {
		$v = str_replace( array( ',', ' ', "\u{00A0}", "\u{202F}" ), array( '.', '', '', '' ), (string) $v );
		return is_numeric( $v ) ? (float) $v : null;
	}

	private static function text( $in, $key, $max ) {
		return trim( sanitize_textarea_field( (string) ( $in[ $key ] ?? '' ) ) );
	}

	private static function title( array $in, array &$e, $for_save, $msg ) {
		$t = array(
			'fr' => mb_substr( trim( sanitize_text_field( $in['title']['fr'] ?? '' ) ), 0, 140 ),
			'ar' => mb_substr( trim( sanitize_text_field( $in['title']['ar'] ?? '' ) ), 0, 140 ),
		);
		if ( $for_save && '' === $t['fr'] && '' === $t['ar'] ) {
			$e['title'] = $msg;
		}
		return $t;
	}

	public static function service( array $in, $for_save = true ) {
		$e    = array();
		$kind = sanitize_key( (string) ( $in['kind'] ?? '' ) );
		$def  = Catalogue::kind( $kind );
		if ( ! $def ) {
			return array( 'errors' => array( 'kind' => __( 'Choose the type of service.', 'sm-services' ) ), 'data' => array(), 'texts' => array() );
		}
		// One service per page (0.3.1): a code of the list, or "other" with the name the company writes.
		$code = sanitize_key( (string) ( $in['category'] ?? ( array_values( (array) ( $in['categories'] ?? array() ) )[0] ?? '' ) ) );
		$custom = '';
		if ( Catalogue::OTHER === $code ) {
			$custom = mb_substr( trim( preg_replace( '/\s+/u', ' ', sanitize_text_field( (string) ( $in['custom_service'] ?? '' ) ) ) ), 0, 80 );
			if ( mb_strlen( $custom ) < 3 ) {
				$e['custom_service'] = __( 'Write the name of your service (3 characters at least).', 'sm-services' );
			}
		} elseif ( ! isset( ( $def['labels'] ?? $def['categories'] )[ $code ] ) ) {
			$e['categories'] = __( 'Choose the service.', 'sm-services' );
			$code            = '';
		}
		$cats = '' === $code ? array() : array( $code );
		// The title of the page is the name of the service (0.3.2): no title to type.
		$labels = $def['labels'] ?? $def['categories'];
		$title  = Catalogue::OTHER === $code ? array( 'fr' => $custom, 'ar' => $custom ) : (array) ( $labels[ $code ] ?? array( 'fr' => '', 'ar' => '' ) );
		$title  = array( 'fr' => (string) ( $title['fr'] ?? '' ), 'ar' => (string) ( $title['ar'] ?? '' ) );
		$desc   = self::text( $in, 'description', 4000 );
		if ( $for_save && mb_strlen( $desc ) < 15 ) {
			$e['description'] = __( 'Describe the service in a few words (15 characters at least).', 'sm-services' );
		} elseif ( mb_strlen( $desc ) > 4000 ) {
			$e['description'] = __( 'The description is too long (4000 characters at most).', 'sm-services' );
		}
		$caps  = self::text( $in, 'capabilities', 2000 );
		$equip = self::text( $in, 'equipment', 2000 );
		foreach ( array( 'capabilities' => $caps, 'equipment' => $equip ) as $k => $v ) {
			if ( mb_strlen( $v ) > 2000 ) {
				$e[ $k ] = __( 'Too long (2000 characters at most).', 'sm-services' );
			}
		}
		$wilayas = array();
		if ( 'list' === ( $in['coverage'] ?? 'all' ) ) {
			$wilayas = array_values( array_unique( array_filter( array_map( 'strval', (array) ( $in['wilayas'] ?? array() ) ), array( Wilayas::class, 'exists' ) ) ) );
			if ( ! $wilayas ) {
				$e['wilayas'] = __( 'Tick the wilayas you serve, or choose "All of Algeria".', 'sm-services' );
			}
		}
		$lead = self::num( $in['lead_days'] ?? '' );
		if ( '' !== trim( (string) ( $in['lead_days'] ?? '' ) ) && ( null === $lead || $lead < 0 || $lead > 365 || floor( $lead ) !== $lead ) ) {
			$e['lead_days'] = __( 'Usual lead time in days (0 to 365).', 'sm-services' );
			$lead           = 0;
		}
		$mode  = 'from' === ( $in['price_mode'] ?? '' ) ? 'from' : 'quote';
		$price = 0.0;
		$unit  = '';
		if ( 'from' === $mode ) {
			$price = (float) self::num( $in['price_from'] ?? '' );
			$unit  = sanitize_key( (string) ( $in['price_unit'] ?? '' ) );
			if ( $price <= 0 || $price > (float) Settings::get( 'max_price' ) ) {
				$e['price_from'] = __( 'Give the starting price, or choose "On quotation".', 'sm-services' );
			}
			if ( ! in_array( $unit, $def['units'], true ) ) {
				$e['price_unit'] = __( 'Choose the unit of the price.', 'sm-services' );
			}
		}
		return array(
			'errors' => $e,
			'data'   => array(
				'kind'       => $kind,
				'categories' => $cats,
				'custom_service' => $custom,
				'wilayas'    => $wilayas,
				'lead_days'  => (int) $lead,
				'price_mode' => $mode,
				'price_from' => $price,
				'price_unit' => $unit,
			),
			'texts'  => array(
				'title'        => $title,
				'description'  => self::by_lang( $desc ),
				'capabilities' => self::by_lang( $caps ),
				'equipment'    => self::by_lang( $equip ),
			),
		);
	}

	public static function request( array $in ) {
		$e   = array();
		$msg = self::text( $in, 'message', 3000 );
		if ( mb_strlen( $msg ) < 20 ) {
			$e['message'] = __( 'Describe your project in a few sentences (20 characters at least).', 'sm-services' );
		} elseif ( mb_strlen( $msg ) > 3000 ) {
			$e['message'] = __( 'The message is too long (3000 characters at most).', 'sm-services' );
		}
		$wilaya = (string) ( $in['wilaya'] ?? '' );
		if ( ! Wilayas::exists( $wilaya ) ) {
			$e['wilaya'] = __( 'Choose the wilaya of the project.', 'sm-services' );
		}
		$needed = trim( (string) ( $in['needed_by'] ?? '' ) );
		if ( '' !== $needed ) {
			$ok = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $needed ) && checkdate( (int) substr( $needed, 5, 2 ), (int) substr( $needed, 8, 2 ), (int) substr( $needed, 0, 4 ) );
			if ( ! $ok || $needed < current_time( 'Y-m-d', true ) ) {
				$e['needed_by'] = __( 'The date must be today or later.', 'sm-services' );
				$needed         = '';
			}
		}
		$budget = self::num( $in['budget'] ?? '' );
		if ( '' !== trim( (string) ( $in['budget'] ?? '' ) ) && ( null === $budget || $budget < 0 || $budget > (float) Settings::get( 'max_price' ) ) ) {
			$e['budget'] = __( 'This budget is outside the accepted range.', 'sm-services' );
		}
		return array(
			'errors' => $e,
			'data'   => array( 'message' => $msg, 'wilaya' => $wilaya, 'needed_by' => $needed ? $needed : null, 'budget' => (float) $budget ),
		);
	}

	public static function quote( array $in ) {
		$e      = array();
		$amount = self::num( $in['quote_amount'] ?? '' );
		if ( null === $amount || $amount <= 0 || $amount > (float) Settings::get( 'max_price' ) ) {
			$e['quote_amount'] = __( 'Give the amount of your quote.', 'sm-services' );
		}
		$days = self::num( $in['quote_days'] ?? '' );
		if ( null === $days || $days < 0 || $days > 365 || floor( $days ) !== $days ) {
			$e['quote_days'] = __( 'Give the time needed in days.', 'sm-services' );
		}
		$msg = self::text( $in, 'quote_message', 3000 );
		if ( mb_strlen( $msg ) > 3000 ) {
			$e['quote_message'] = __( 'The message is too long (3000 characters at most).', 'sm-services' );
		}
		return array( 'errors' => $e, 'data' => array( 'quote_amount' => (float) $amount, 'quote_days' => (int) $days, 'quote_message' => $msg ) );
	}

	public static function reference( array $in ) {
		$e     = array();
		$title = self::title( $in, $e, true, __( 'Give the project a title.', 'sm-services' ) );
		$desc  = self::text( $in, 'description', 1500 );
		if ( mb_strlen( $desc ) > 1500 ) {
			$e['description'] = __( 'Too long (1500 characters at most).', 'sm-services' );
		}
		$role = mb_substr( trim( sanitize_text_field( (string) ( $in['role'] ?? '' ) ) ), 0, 120 );
		$year = (int) ( $in['year'] ?? 0 );
		$max  = (int) current_time( 'Y', true ) + 1;
		if ( $year && ( $year < 1950 || $year > $max ) ) {
			$e['year'] = __( 'Check the year.', 'sm-services' );
		}
		$wilaya = (string) ( $in['wilaya'] ?? '' );
		if ( '' !== $wilaya && ! Wilayas::exists( $wilaya ) ) {
			$wilaya = '';
		}
		return array(
			'errors' => $e,
			'data'   => array( 'year' => $year, 'wilaya' => $wilaya, 'texts' => array( 'title' => $title, 'description' => self::by_lang( $desc ), 'role' => self::by_lang( $role ) ) ),
		);
	}
}
