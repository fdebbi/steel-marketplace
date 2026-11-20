<?php
/**
 * DQE IA (0.3.0): the pieces of a document (bill of quantities, quotation,
 * purchase order, take-off, public tender) for the cutting optimiser.
 *  - bars: profile, length of one piece, number → the list of pieces to cut;
 *  - linear: quantities given only in metres → bars to buy (request for quotation);
 *  - plates: thickness, length × width, number → request for quotation;
 *  - other lines (concrete, roofing, paint…) are only counted.
 * Every profile is checked against the catalogue; weights come from the weight
 * engine. A line the AI deduced instead of read, a document sent as a photo,
 * a missing length or an unknown profile are « to check »: the user decides.
 * Plans are not read (later): documents only.
 *
 * @package SM\Ai
 */

namespace SM\Ai\Features;

use SM\Ai\Catalog;

defined( 'ABSPATH' ) || exit;

final class Cutlist extends Feature {

	const MAX_LINES = 80;

	public static function id() {
		return 'cutlist';
	}

	public static function label() {
		return __( 'DQE for the cutting optimiser', 'sm-ai' );
	}

	public static function allowed( $company ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error( 'sm_ai_account', __( 'Sign in to use this tool.', 'sm-ai' ) );
		}
		if ( ! $company ) {
			return new \WP_Error( 'sm_ai_account', __( 'Your account is not complete: finish your profile to use the AI tools.', 'sm-ai' ) );
		}
		if ( ! Catalog::has_engine() ) {
			return new \WP_Error( 'sm_ai_engine', __( 'This AI service is not available.', 'sm-ai' ) );
		}
		return true;
	}

	/** Answers can be long (one entry per line of the document). */
	public static function max_tokens() {
		return 6000;
	}

	public static function system( $lang ) {
		return "You read documents of steel construction (bill of quantities, quotation, purchase order, take-off, tender) and list the steel pieces to cut from bars, for a cutting optimiser.\n"
			. self::common_rules( $lang )
			. "- One entry per line of the document that is steel framing. Copy the text of the line (text) and its number or position (line).\n"
			. "- bars: profiles cut from bars (IPE, HEA, HEB, UPN, angles, tubes, flats…): family code, designation as written, length of ONE piece in metres, number of pieces. If the length or the number is not written, leave it 0. If you deduced it (e.g. a span split in two halves, a number of frames multiplied), set deduced=true and explain in note.\n"
			. "- linear: profiles given only as a total length in metres (ml), with no piece length: family, designation, metres.\n"
			. "- plates: plates, base plates, gussets, stiffeners: thickness_mm, length_mm, width_mm, number. Leave 0 what is not written; deduced=true if deduced.\n"
			. "- ignored: number of lines that are not steel framing (concrete, roofing sheets, paint, bolts, labour…) and up to 5 short examples.\n"
			. "- unreadable=true only if the document cannot be read (blurred photo, not a steel document); then lists are empty.\n"
			. "- Never compute weights or prices. Do not total lines.\n"
			. "JSON keys: summary (1 sentence), unreadable, bars (list of {line, text, family, designation, length_m, quantity, deduced, note}), linear (list of {line, text, family, designation, metres}), plates (list of {line, text, thickness_mm, length_mm, width_mm, quantity, deduced, note}), ignored (number), ignored_examples (list).\n\n"
			. Catalog::context( array( 'families' ) );
	}

	public static function parts( array $input ) {
		return array_merge( self::files( $input ), array( array( 'type' => 'text', 'text' => 'List the steel pieces of this document.' ) ) );
	}

	/** True when one of the files is a photo (then every line is « to check »). */
	public static function is_photo( array $input ) {
		foreach ( self::files( $input ) as $f ) {
			if ( 'image' === ( $f['type'] ?? '' ) ) {
				return true;
			}
		}
		return false;
	}

	private static function num( $v, $max ) {
		$n = (float) str_replace( ',', '.', (string) ( is_scalar( $v ) ? $v : 0 ) );
		return $n > 0 && $n <= $max ? $n : 0.0;
	}

	/** Section of the catalogue for a family and a designation as written (or null). */
	private static function find( $family, $designation ) {
		$fams = Catalog::families();
		$f    = strtoupper( self::str( $family, 10 ) );
		$s    = isset( $fams[ $f ] ) ? Catalog::section( $f, $designation ) : null;
		if ( ! $s && '' !== $designation ) {
			$hit = Catalog::detect( $designation );
			$s   = $hit ? $hit[1] : null;
		}
		return $s && 'PLATE' !== $s->family ? $s : null;
	}

	private static function engine() {
		return class_exists( '\SM\Core\Modules' ) ? \SM\Core\Modules::service( 'weight' ) : null;
	}

	public static function clean( array $j, array $input ) {
		$photo = self::is_photo( $input );
		$eng   = self::engine();
		$bars  = array();
		foreach ( array_slice( (array) ( $j['bars'] ?? array() ), 0, self::MAX_LINES ) as $it ) {
			if ( ! is_array( $it ) ) {
				continue;
			}
			$des  = self::str( $it['designation'] ?? '', 60 );
			$s    = self::find( $it['family'] ?? '', $des );
			$len  = round( self::num( $it['length_m'] ?? 0, 24 ), 3 );
			$qty  = self::num( $it['quantity'] ?? 0, 10000 );
			$qty  = floor( $qty ) === $qty ? (int) $qty : 0;
			$why  = array();
			if ( ! $s ) {
				$why[] = 'section';
			}
			if ( ! $len ) {
				$why[] = 'length';
			}
			if ( ! $qty ) {
				$why[] = 'quantity';
			}
			if ( ! empty( $it['deduced'] ) ) {
				$why[] = 'deduced';
			}
			if ( $photo ) {
				$why[] = 'photo';
			}
			$bars[] = array(
				'line'        => self::str( $it['line'] ?? '', 20 ),
				'text'        => self::str( $it['text'] ?? '', 160 ),
				'designation' => $des,
				'section'     => $s ? self::section_data( $s ) : null,
				'length_m'    => $len,
				'quantity'    => $qty,
				'sure'        => ! $why,
				'why'         => $why,
				'note'        => self::str( $it['note'] ?? '', 160 ),
			);
		}
		$linear = array();
		foreach ( array_slice( (array) ( $j['linear'] ?? array() ), 0, 30 ) as $it ) {
			if ( ! is_array( $it ) ) {
				continue;
			}
			$des = self::str( $it['designation'] ?? '', 60 );
			$s   = self::find( $it['family'] ?? '', $des );
			$m   = round( self::num( $it['metres'] ?? 0, 100000 ), 2 );
			if ( ! $m ) {
				continue;
			}
			$linear[] = array(
				'line'        => self::str( $it['line'] ?? '', 20 ),
				'text'        => self::str( $it['text'] ?? '', 160 ),
				'designation' => $des,
				'section'     => $s ? self::section_data( $s ) : null,
				'metres'      => $m,
				'sure'        => $s && ! $photo,
			);
		}
		$plates = array();
		foreach ( array_slice( (array) ( $j['plates'] ?? array() ), 0, 30 ) as $it ) {
			if ( ! is_array( $it ) ) {
				continue;
			}
			$t   = round( self::num( $it['thickness_mm'] ?? 0, 200 ), 1 );
			$l   = (int) round( self::num( $it['length_mm'] ?? 0, 12000 ) );
			$w   = (int) round( self::num( $it['width_mm'] ?? 0, 3000 ) );
			$qty = self::num( $it['quantity'] ?? 0, 10000 );
			$qty = floor( $qty ) === $qty ? (int) $qty : 0;
			$kg  = 0.0;
			if ( $eng && $t && $l && $w && $qty ) {
				$c  = $eng->calculate( array( 'family' => 'PLATE', 'dims' => array( 't' => $t ), 'length' => $l, 'width' => $w, 'bars' => $qty ) );
				$kg = is_wp_error( $c ) ? 0.0 : round( (float) $c['total_kg'], 1 );
			}
			$why = array();
			if ( ! $t || ! $l || ! $w ) {
				$why[] = 'dimensions';
			}
			if ( ! $qty ) {
				$why[] = 'quantity';
			}
			if ( ! empty( $it['deduced'] ) ) {
				$why[] = 'deduced';
			}
			if ( $photo ) {
				$why[] = 'photo';
			}
			$plates[] = array(
				'line'      => self::str( $it['line'] ?? '', 20 ),
				'text'      => self::str( $it['text'] ?? '', 160 ),
				'thickness' => $t,
				'length'    => $l,
				'width'     => $w,
				'quantity'  => $qty,
				'kg'        => $kg,
				'sure'      => ! $why,
				'why'       => $why,
			);
		}
		return array(
			'summary'          => self::str( $j['summary'] ?? '', 300 ),
			'unreadable'       => ! empty( $j['unreadable'] ) && ! $bars && ! $plates && ! $linear,
			'photo'            => $photo,
			'name'             => self::str( $input['name'] ?? '', 120 ),
			'bars'             => $bars,
			'linear'           => $linear,
			'plates'           => $plates,
			'ignored'          => max( 0, min( 10000, (int) ( $j['ignored'] ?? 0 ) ) ),
			'ignored_examples' => self::list( $j['ignored_examples'] ?? array(), 5, 80 ),
		);
	}

	public static function simulate( array $input ) {
		if ( false !== stripos( (string) ( $input['name'] ?? '' ), 'flou' ) ) {
			return wp_json_encode( array( 'summary' => '', 'unreadable' => true, 'bars' => array(), 'linear' => array(), 'plates' => array(), 'ignored' => 0 ) );
		}
		return wp_json_encode(
			array(
				'summary'          => 'DQE du lot charpente d’un hangar (simulation).',
				'unreadable'       => false,
				'bars'             => array(
					array( 'line' => '12', 'text' => 'Poteaux HEA 200 – H = 6,50 m', 'family' => 'HEA', 'designation' => 'HEA 200', 'length_m' => 6.5, 'quantity' => 12, 'deduced' => false, 'note' => '' ),
					array( 'line' => '14', 'text' => 'Traverses IPE 270 portée 10,20 (6 portiques)', 'family' => 'IPE', 'designation' => 'IPE 270', 'length_m' => 5.1, 'quantity' => 12, 'deduced' => true, 'note' => '2 demi-traverses par portique' ),
					array( 'line' => '17', 'text' => 'Pannes IPE 120 L=6m', 'family' => 'IPE', 'designation' => 'IPE 120', 'length_m' => 6, 'quantity' => 28, 'deduced' => false, 'note' => '' ),
					array( 'line' => '19', 'text' => 'Lisses UAP 100 x 6,00', 'family' => 'UAP', 'designation' => 'UAP 100', 'length_m' => 6, 'quantity' => 16, 'deduced' => false, 'note' => 'UAP' ),
					array( 'line' => '22', 'text' => 'Contreventement cornière 60x6', 'family' => 'L', 'designation' => 'L 60x60x6', 'length_m' => 0, 'quantity' => 8, 'deduced' => false, 'note' => 'longueur absente' ),
				),
				'linear'           => array(
					array( 'line' => '25', 'text' => 'Sablières IPE 160 : 84 ml', 'family' => 'IPE', 'designation' => 'IPE 160', 'metres' => 84 ),
				),
				'plates'           => array(
					array( 'line' => '31', 'text' => 'Platines pieds de poteaux 300x250 ép.15', 'thickness_mm' => 15, 'length_mm' => 300, 'width_mm' => 250, 'quantity' => 12, 'deduced' => false ),
					array( 'line' => '32', 'text' => 'Goussets 200x200x10', 'thickness_mm' => 10, 'length_mm' => 200, 'width_mm' => 200, 'quantity' => 24, 'deduced' => false ),
					array( 'line' => '33', 'text' => 'Platines d’about ép.12', 'thickness_mm' => 12, 'length_mm' => 0, 'width_mm' => 0, 'quantity' => 12, 'deduced' => false ),
				),
				'ignored'          => 36,
				'ignored_examples' => array( 'Béton de propreté', 'Couverture TN40', 'Peinture antirouille' ),
			)
		);
	}
}
