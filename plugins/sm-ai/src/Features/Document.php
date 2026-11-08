<?php
/**
 * AI document analysis: list of steel items read from a document (bill of
 * quantities, list of profiles, specification) in PDF or as a photo. Each
 * profile is checked against the catalogue; theoretical weights are computed
 * by the weight engine. A request for quotation can then be pre-filled.
 *
 * @package SM\Ai
 */

namespace SM\Ai\Features;

use SM\Ai\Catalog;

defined( 'ABSPATH' ) || exit;

final class Document extends Feature {

	const UNITS = array( 'pieces', 'kg', 't', 'm' );

	public static function id() {
		return 'document';
	}

	public static function label() {
		return __( 'Document analysis', 'sm-ai' );
	}

	public static function allowed( $company ) {
		if ( ! $company ) {
			return new \WP_Error( 'sm_ai_account', __( 'Sign in with your company account to analyse a document.', 'sm-ai' ) );
		}
		return true;
	}

	public static function system( $lang ) {
		return "You read technical and commercial documents of steel construction (bills of quantities, lists of profiles, specifications, quotations) and extract the steel items.\n"
			. self::common_rules( $lang )
			. "- One item per line of the document: family code, designation as written (e.g. \"IPE 200\"), grade, length of one piece in metres, quantity and its unit (pieces, kg, t or m), and a short note (location in the document, doubt).\n"
			. "- Do not add items that are not in the document; do not total or convert anything.\n"
			. "- summary: 2 or 3 sentences on the document (type, project, date if written).\n"
			. "JSON keys: summary, items (list of {family, designation, grade, length_m, quantity, unit, note}), questions (list).\n\n"
			. Catalog::context( array( 'families', 'grades' ) );
	}

	public static function parts( array $input ) {
		return array_merge( self::notes_part( $input, 'What the user is looking for' ), self::files( $input ), array( array( 'type' => 'text', 'text' => 'Extract the steel items of this document.' ) ) );
	}

	public static function clean( array $j, array $input ) {
		$fams   = Catalog::families();
		$grades = Catalog::grades();
		$items  = array();
		$known  = 0;
		$total  = 0.0;
		$eng    = class_exists( '\SM\Core\Modules' ) ? \SM\Core\Modules::service( 'weight' ) : null;
		foreach ( array_slice( (array) ( $j['items'] ?? array() ), 0, 60 ) as $it ) {
			if ( ! is_array( $it ) ) {
				continue;
			}
			$f     = strtoupper( self::str( $it['family'] ?? '', 10 ) );
			$des   = self::str( $it['designation'] ?? '', 60 );
			$s     = isset( $fams[ $f ] ) ? Catalog::section( $f, $des ) : null;
			if ( ! $s && '' !== $des ) {
				$hit = Catalog::detect( $des );
				$s   = $hit ? $hit[1] : null;
				$f   = $hit ? $hit[0] : $f;
			}
			$grade = strtoupper( self::str( $it['grade'] ?? '', 12 ) );
			$len   = (float) str_replace( ',', '.', (string) ( $it['length_m'] ?? 0 ) );
			$len   = $len > 0 && $len <= 24 ? round( $len, 3 ) : 0;
			$qty   = (float) str_replace( ',', '.', (string) ( $it['quantity'] ?? 0 ) );
			$qty   = $qty > 0 && $qty <= 1000000 ? $qty : 0;
			$unit  = in_array( $it['unit'] ?? '', self::UNITS, true ) ? $it['unit'] : 'pieces';
			$kg    = 0.0;
			if ( $s && $eng && 'pieces' === $unit && $len > 0 && $qty > 0 && floor( $qty ) === $qty ) {
				$calc = $eng->calculate( array( 'family' => $s->family, 'section' => $s->id, 'length' => $len * 1000, 'bars' => (int) $qty ) );
				$kg   = is_wp_error( $calc ) ? 0.0 : (float) $calc['total_kg'];
			}
			$known += $s ? 1 : 0;
			$total += $kg;
			$items[] = array(
				'family'      => $s ? $s->family : ( isset( $fams[ $f ] ) ? $f : '' ),
				'designation' => $s ? Catalog::label( $s ) : $des,
				'section'     => $s ? self::section_data( $s ) : null,
				'grade'       => in_array( $grade, $grades, true ) ? $grade : '',
				'grade_text'  => $grade,
				'length_m'    => $len,
				'quantity'    => $qty,
				'unit'        => $unit,
				'weight_kg'   => round( $kg, 1 ),
				'note'        => self::str( $it['note'] ?? '', 200 ),
			);
		}
		return array(
			'summary'   => self::text( $j['summary'] ?? '', 800 ),
			'items'     => $items,
			'known'     => $known,
			'weight_kg' => round( $total, 1 ),
			'questions' => self::list( $j['questions'] ?? array(), 6 ),
		);
	}

	/** Values for the request for quotation form (rows the engine can weigh). */
	public static function rfq_values( array $r, $lang ) {
		$lines = array();
		$other = array();
		foreach ( $r['items'] as $it ) {
			if ( $it['section'] && 'pieces' === $it['unit'] && $it['quantity'] > 0 && $it['length_m'] > 0 && 'PLATE' !== $it['family'] ) {
				$lines[] = array( 'family' => $it['family'], 'section' => (string) $it['section']['section_id'], 'qty' => (string) (int) $it['quantity'], 'length' => (string) $it['length_m'] );
			} else {
				$other[] = trim( $it['designation'] . ' ' . $it['grade_text'] . ( $it['length_m'] ? ' L=' . $it['length_m'] . ' m' : '' ) . ( $it['quantity'] ? ' × ' . ( 0 + $it['quantity'] ) . ' ' . $it['unit'] : '' ) );
			}
		}
		$desc = $r['summary'];
		if ( $other ) {
			$desc .= ( '' !== $desc ? "\n\n" : '' ) . ( 'ar' === $lang ? 'بنود أخرى للتأكيد:' : 'Autres articles à préciser :' ) . "\n- " . implode( "\n- ", $other );
		}
		$first = trim( (string) preg_split( '/(?<=[.!?؟])\s/u', $r['summary'] )[0] );
		$title = '' !== $first ? mb_substr( rtrim( $first, '.' ), 0, 100 ) : ( 'ar' === $lang ? 'طلب أسعار حسب الكشف' : 'Demande de prix selon métré' );
		return array(
			'market'      => 'products',
			'title'       => array( $lang => $title ),
			'lines'       => array_slice( $lines, 0, 30 ),
			'description' => mb_substr( $desc, 0, 3000 ),
		);
	}

	public static function simulate( array $input ) {
		return wp_json_encode(
			array(
				'summary'   => 'Métré de charpente métallique d’un hangar de stockage (simulation).',
				'items'     => array(
					array( 'family' => 'IPE', 'designation' => 'IPE 200', 'grade' => 'S275JR', 'length_m' => 6, 'quantity' => 12, 'unit' => 'pieces', 'note' => 'Pannes' ),
					array( 'family' => 'HEA', 'designation' => 'HEA 160', 'grade' => 'S275JR', 'length_m' => 6, 'quantity' => 8, 'unit' => 'pieces', 'note' => 'Poteaux' ),
					array( 'family' => 'UPN', 'designation' => 'UPN 999', 'grade' => 'S235JR', 'length_m' => 4, 'quantity' => 2, 'unit' => 'pieces', 'note' => 'Désignation inconnue' ),
					array( 'family' => '', 'designation' => 'Tôle nervurée TN40', 'grade' => '', 'length_m' => 0, 'quantity' => 480, 'unit' => 'm', 'note' => 'Couverture' ),
				),
				'questions' => array( 'La nuance des poteaux est-elle bien S275JR ?' ),
			)
		);
	}
}
