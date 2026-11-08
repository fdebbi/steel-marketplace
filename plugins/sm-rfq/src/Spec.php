<?php
/**
 * Checks what a buyer typed for a request for quotation, and what a supplier
 * typed for an offer. Weights of profile rows come from SM Weight Engine; totals
 * and prices per kg are computed here, never by the browser.
 *
 * Request input: market, category, lines[i][family|section|qty|length], designation,
 * dimensions, quantity, unit, weight_kg, title[fr|ar], description, notes, wilaya,
 * place, needed_by (Y-m-d), deadline (Y-m-d).
 *
 * Offer input: price_mode (total|kg), price, weight_kg, delivery_days,
 * delivery_included, validity_days, payment_terms, message.
 *
 * @package SM\Rfq
 */

namespace SM\Rfq;

defined( 'ABSPATH' ) || exit;

final class Spec {

	/** Markets a request can be about: the listing markets plus "other". */
	public static function markets() {
		$out = array();
		foreach ( \SM\Products\Markets::all() as $k => $m ) {
			$out[ $k ] = $m['label'];
		}
		$out['other'] = array( 'fr' => 'Autre besoin acier', 'ar' => 'حاجة أخرى من الفولاذ' );
		return apply_filters( 'sm_rfq_markets', $out );
	}

	/** Pseudo category for the profile rows: every family of the weight engine. */
	private static function rows_category() {
		$fam = array();
		$eng = \SM\Core\Modules::service( 'weight' );
		if ( $eng ) {
			foreach ( $eng->families() as $f ) {
				$code = is_array( $f ) ? ( $f['code'] ?? '' ) : ( $f->code ?? '' );
				if ( $code && 'PLATE' !== $code ) {
					$fam[] = $code;
				}
			}
		}
		return (object) array( 'slug' => 'rfq', 'market' => 'rfq', 'kind' => 'lines', 'families' => $fam, 'units' => array( 'kg' ) );
	}

	public static function families() {
		return self::rows_category()->families;
	}

	private static function date( $v ) {
		$v = trim( (string) $v );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ) {
			return '';
		}
		list( $y, $m, $d ) = array_map( 'intval', explode( '-', $v ) );
		return checkdate( $m, $d, $y ) ? $v : '';
	}

	/**
	 * @return array{errors: array, data: array, summary: array, title: array, description: string, notes: string}
	 */
	public static function build( array $in, $for_save = true ) {
		$s = Settings::all();
		$e = array();
		$d = array();

		$markets = self::markets();
		$market  = sanitize_key( (string) ( $in['market'] ?? '' ) );
		if ( ! isset( $markets[ $market ] ) ) {
			$e['market'] = __( 'Choose what you need.', 'sm-rfq' );
			$market      = '';
		}
		$d['market'] = $market;
		$cat         = sanitize_key( (string) ( $in['category'] ?? '' ) );
		$c           = $cat ? \SM\Products\Categories::get( $cat ) : null;
		if ( $cat && ( ! $c || $c->market !== $market ) ) {
			$e['category'] = __( 'This category does not belong to the chosen market.', 'sm-rfq' );
			$cat           = '';
		}
		$d['category'] = $cat;

		// Profile rows (optional): weighed by SM Weight Engine.
		$rows = array_filter(
			(array) ( $in['lines'] ?? array() ),
			static fn( $r ) => is_array( $r ) && implode( '', array_map( 'strval', array_intersect_key( $r, array_flip( array( 'section', 'qty', 'length' ) ) ) ) ) !== ''
		);
		$d['lines'] = array();
		if ( $rows ) {
			$tmp = array();
			$te  = array();
			\SM\Products\Spec::lines( self::rows_category(), $in, $tmp, $te );
			unset( $te['lines'] );
			$e              += $te;
			$d['lines']      = $tmp['lines'] ?? array();
			$d['line_rows']  = $tmp['line_rows'] ?? array();
			$d['weight_kg']  = (float) ( $tmp['total_weight'] ?? 0 );
			$d['weight_source'] = 'theoretical';
			$d['quantity']   = (float) ( $tmp['pieces'] ?? 0 );
			$d['unit']       = 'pieces';
			$d['designation'] = (string) ( $tmp['designation'] ?? '' );
			$d['engine_version'] = (string) ( $tmp['engine_version'] ?? '' );
			$d['dimensions'] = '';
		} else {
			$d['designation'] = mb_substr( trim( sanitize_text_field( (string) ( $in['designation'] ?? '' ) ) ), 0, 120 );
			$d['dimensions']  = mb_substr( trim( sanitize_text_field( (string) ( $in['dimensions'] ?? '' ) ) ), 0, 190 );
			$q                = \SM\Products\Spec::num( $in['quantity'] ?? '' );
			$u                = sanitize_key( (string) ( $in['unit'] ?? '' ) );
			if ( $q <= 0 ) {
				if ( $for_save ) {
					$e['quantity'] = __( 'Give the quantity, or list the profiles above.', 'sm-rfq' );
				}
			} elseif ( ! in_array( $u, Rfqs::UNITS, true ) ) {
				$e['unit'] = __( 'Choose the unit.', 'sm-rfq' );
			}
			$d['quantity'] = $q;
			$d['unit']     = in_array( $u, Rfqs::UNITS, true ) ? $u : '';
			$w             = \SM\Products\Spec::num( $in['weight_kg'] ?? '' );
			if ( $w < 0 || $w > (float) sm_weight_setting( 'max_total_kg', 10000000 ) ) {
				$e['weight_kg'] = __( 'This weight is outside the accepted range.', 'sm-rfq' );
				$w              = 0;
			}
			$d['weight_kg']     = $w;
			$d['weight_source'] = $w > 0 ? 'given' : '';
			$d['engine_version'] = '';
		}

		$title = array(
			'fr' => mb_substr( trim( sanitize_text_field( $in['title']['fr'] ?? '' ) ), 0, 140 ),
			'ar' => mb_substr( trim( sanitize_text_field( $in['title']['ar'] ?? '' ) ), 0, 140 ),
		);
		if ( $for_save && '' === $title['fr'] && '' === $title['ar'] ) {
			$e['title'] = __( 'Give your request a title (in French or in Arabic).', 'sm-rfq' );
		}
		$desc = trim( sanitize_textarea_field( (string) ( $in['description'] ?? '' ) ) );
		if ( $for_save && mb_strlen( $desc ) < 10 ) {
			$e['description'] = __( 'Describe what you need (grade, finish, tolerances, standards…).', 'sm-rfq' );
		} elseif ( mb_strlen( $desc ) > 4000 ) {
			$e['description'] = __( 'The description is too long (4000 characters at most).', 'sm-rfq' );
		}
		$notes = trim( sanitize_textarea_field( (string) ( $in['notes'] ?? '' ) ) );
		if ( mb_strlen( $notes ) > 2000 ) {
			$e['notes'] = __( 'The notes are too long (2000 characters at most).', 'sm-rfq' );
		}

		$wilaya = (string) ( $in['wilaya'] ?? '' );
		if ( $for_save && ! \SM\Core\Accounts\Wilayas::exists( $wilaya ) ) {
			$e['wilaya'] = __( 'Choose the delivery wilaya.', 'sm-rfq' );
		}
		$d['wilaya'] = $wilaya;
		$d['place']  = mb_substr( trim( sanitize_text_field( (string) ( $in['place'] ?? '' ) ) ), 0, 190 );

		$today    = current_time( 'Y-m-d', true );
		$needed   = self::date( $in['needed_by'] ?? '' );
		$deadline = self::date( $in['deadline'] ?? '' );
		if ( '' !== (string) ( $in['needed_by'] ?? '' ) && ( ! $needed || $needed < $today ) ) {
			$e['needed_by'] = __( 'The delivery date must be today or later.', 'sm-rfq' );
			$needed         = '';
		}
		$max_deadline = gmdate( 'Y-m-d', strtotime( $today . ' +' . (int) $s['max_days'] . ' days' ) );
		if ( ! $deadline ) {
			if ( $for_save ) {
				$e['deadline'] = __( 'Choose the date until which suppliers can answer.', 'sm-rfq' );
			}
		} elseif ( $deadline <= $today || $deadline > $max_deadline ) {
			/* translators: %d: number of days. */
			$e['deadline'] = sprintf( __( 'The answer deadline must be between tomorrow and %d days from today.', 'sm-rfq' ), (int) $s['max_days'] );
		} elseif ( $needed && $needed < $deadline ) {
			$e['needed_by'] = __( 'The delivery date cannot be before the answer deadline.', 'sm-rfq' );
		}
		$d['needed_by'] = $needed ? $needed : null;
		$d['deadline']  = $deadline ? $deadline : null;

		return array(
			'errors'      => $e,
			'data'        => $d,
			'title'       => $title,
			'description' => $desc,
			'notes'       => $notes,
			'summary'     => array(
				'weight_kg'     => (float) ( $d['weight_kg'] ?? 0 ),
				'weight_source' => (string) ( $d['weight_source'] ?? '' ),
				'quantity'      => (float) ( $d['quantity'] ?? 0 ),
				'unit'          => (string) ( $d['unit'] ?? '' ),
				'lines'         => $d['lines'],
				'line_rows'     => $d['line_rows'] ?? array(),
			),
		);
	}

	/**
	 * Offer of a supplier. Price per kg needs a weight: the request's, or the one
	 * the supplier states (e.g. actual weight of his stock).
	 *
	 * @return array{errors: array, data: array}
	 */
	public static function offer( $rfq, array $in, $for_save = true ) {
		$s = Settings::all();
		$e = array();
		$d = array();
		$mode  = 'kg' === ( $in['price_mode'] ?? '' ) ? 'kg' : 'total';
		$price = \SM\Products\Spec::num( $in['price'] ?? '' );
		$w     = \SM\Products\Spec::num( $in['weight_kg'] ?? '' );
		if ( $w < 0 || $w > (float) sm_weight_setting( 'max_total_kg', 10000000 ) ) {
			$e['weight_kg'] = __( 'This weight is outside the accepted range.', 'sm-rfq' );
			$w              = 0;
		}
		$weight = $w > 0 ? $w : (float) $rfq->weight_kg;
		if ( $price <= 0 ) {
			if ( $for_save ) {
				$e['price'] = __( 'Enter your price.', 'sm-rfq' );
			}
		} elseif ( $price < $s['min_price'] || $price > $s['max_price'] ) {
			$e['price'] = __( 'This price is outside the accepted range.', 'sm-rfq' );
		}
		if ( 'kg' === $mode && $weight <= 0 ) {
			$e['weight_kg'] = __( 'A price per kg needs a weight: give the weight you quote for.', 'sm-rfq' );
		}
		$total = 'kg' === $mode ? $price * $weight : $price;
		$d['price_mode']   = $mode;
		$d['price']        = $price;
		$d['weight_kg']    = $w;
		$d['total_price']  = round( $total, 2 );
		$d['price_per_kg'] = $weight > 0 && $total > 0 ? round( $total / $weight, 2 ) : 0;

		$days = \SM\Products\Spec::num( $in['delivery_days'] ?? '' );
		if ( $days < 0 || $days > 365 || floor( $days ) !== $days || ( $for_save && '' === trim( (string) ( $in['delivery_days'] ?? '' ) ) ) ) {
			$e['delivery_days'] = __( 'Give the delivery time in days (0 if in stock today).', 'sm-rfq' );
		}
		$d['delivery_days']     = (int) $days;
		$d['delivery_included'] = empty( $in['delivery_included'] ) ? 0 : 1;
		$valid = \SM\Products\Spec::num( $in['validity_days'] ?? '' );
		if ( $valid < 1 || $valid > 180 || floor( $valid ) !== $valid ) {
			if ( $for_save || '' !== trim( (string) ( $in['validity_days'] ?? '' ) ) ) {
				$e['validity_days'] = __( 'Say how many days your price is valid (1 to 180).', 'sm-rfq' );
			}
		}
		$d['validity_days'] = (int) $valid;
		$d['payment_terms'] = mb_substr( trim( sanitize_text_field( (string) ( $in['payment_terms'] ?? '' ) ) ), 0, 190 );
		$msg                = trim( sanitize_textarea_field( (string) ( $in['message'] ?? '' ) ) );
		if ( mb_strlen( $msg ) > 3000 ) {
			$e['message'] = __( 'The message is too long (3000 characters at most).', 'sm-rfq' );
		}
		$d['message'] = $msg;
		return array( 'errors' => $e, 'data' => $d );
	}
}
