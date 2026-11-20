<?php
/**
 * Actions of the cutting optimiser (0.4.0), with the same parameters as the page
 * (the plan is computed again, never taken from the browser):
 *  - sm_weight_cut_pdf : the cutting plan as a PDF (everybody);
 *  - sm_weight_cut_rfq : a request for quotation pre-filled with the new bars to buy.
 *
 * @package SM\WeightEngine
 */

namespace SM\WeightEngine;

use SM\Core\I18n\Language;
use SM\Core\Support\Forms;

defined( 'ABSPATH' ) || exit;

final class CuttingActions {

	public static function init() {
		add_action( 'admin_post_sm_weight_cut_pdf', array( __CLASS__, 'pdf' ) );
		add_action( 'admin_post_nopriv_sm_weight_cut_pdf', array( __CLASS__, 'pdf' ) );
		add_action( 'admin_post_sm_weight_cut_rfq', array( __CLASS__, 'rfq' ) );
		add_action( 'admin_post_nopriv_sm_weight_cut_rfq', array( __CLASS__, 'rfq' ) );
	}

	/** The request of the page, from the query string. */
	private static function request() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only calculation, same as the public page.
		return Cutting::read( wp_unslash( $_GET ) );
	}

	/** Language of the page that asked (?lang=), else the current one. */
	private static function lang() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$l = sanitize_key( (string) wp_unslash( $_GET['lang'] ?? '' ) );
		return in_array( $l, array( 'fr', 'ar', 'en' ), true ) ? $l : Language::current();
	}

	/** Numbers as on the site: decimal comma and narrow space in French and Arabic (Western digits). */
	private static function num( $v, $d = 2 ) {
		return 'en' === Language::current() ? number_format( (float) $v, $d, '.', ',' ) : number_format( (float) $v, $d, ',', ' ' );
	}

	public static function pdf() {
		$in = self::request();
		$r  = Cutting::optimize( $in );
		if ( ! $r['profiles'] ) {
			wp_die( esc_html__( 'Nothing to print: give the pieces to cut first.', 'sm-weight-engine' ), '', array( 'response' => 400 ) );
		}
		// Helvetica has no Arabic: the plan is printed in French for an Arabic page.
		$code = 'ar' === self::lang() ? 'fr' : self::lang();
		$pdf  = Language::with_language( $code, static fn() => self::render( $r ) );
		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="plan-de-coupe.pdf"' );
		header( 'Content-Length: ' . strlen( $pdf ) );
		echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}

	/** Draw the plan. */
	public static function render( array $r ) {
		$p   = new Pdf();
		$url = function_exists( 'sm_url' ) ? (string) sm_url( 'cutting' ) : '';
		$url = $url && false === strpos( $url, '#' ) && home_url( '/' ) !== $url ? $url : home_url( '/optimiseur-decoupe/' );
		$p->footer(
			/* translators: %s: address of the optimiser page. */
			sprintf( __( 'Cutting plan made with the ReuSteel cutting optimiser - %s', 'sm-weight-engine' ), preg_replace( '#^https?://(www\.)?#', '', untrailingslashit( $url ) ) ),
			/* translators: {page} and {pages} stay as they are (page numbers). */
			__( 'Indicative calculation to check before cutting · Theoretical weights from the catalogue · Page {page}/{pages}', 'sm-weight-engine' ),
			$url
		);
		$x0  = 40;
		$w   = Pdf::W - 80;
		$y   = 46;
		$set = $r['settings'];
		$t   = $r['totals'];
		$head = static function () use ( $p, $x0, &$y, $set ) {
			$p->text( $x0, $y, __( 'Cutting plan', 'sm-weight-engine' ), 18, true );
			$p->text( $x0 + 520, $y, 'ReuSteel · ' . wp_date( 'd/m/Y' ), 9, false, array( 0.4, 0.45, 0.5 ) );
			$y += 18;
			/* translators: 1: bar length (m), 2: saw kerf (mm), 3: reusable offcut length (m). */
			$p->text( $x0, $y, sprintf( __( 'Bars of %1$s m · saw kerf %2$d mm · offcuts kept from %3$s m', 'sm-weight-engine' ), self::num( $set['bar'] / 1000 ), (int) $set['kerf'], self::num( $set['reuse'] / 1000 ) ), 9.5, false, array( 0.35, 0.4, 0.45 ) );
			$y += 22;
		};
		$head();
		/* translators: 1: number of bars, 2: weight bought, 3: scrap weight, 4: scrap percent. */
		$p->text( $x0, $y, sprintf( __( 'Bars to buy: %1$d · Weight bought: %2$s kg · Offcuts: %3$s kg (%4$s %%)', 'sm-weight-engine' ), (int) $t['new_bars'], self::num( $t['bought_kg'], 1 ), self::num( $t['scrap_kg'], 1 ), self::num( $t['scrap_pct'], 1 ) ), 11, true );
		if ( ! empty( $t['mine_bars'] ) ) {
			$y += 15;
			/* translators: 1: bars, 2: weight. */
			$p->text( $x0, $y, sprintf( __( 'From your warehouse: %1$d bars (%2$s kg).', 'sm-weight-engine' ), (int) $t['mine_bars'], self::num( $t['mine_kg'], 1 ) ), 9.5, false, array( 0.29, 0.23, 0.72 ) );
		}
		if ( ! empty( $t['market_bars'] ) ) {
			$y += 15;
			/* translators: 1: bars, 2: listings, 3: weight. */
			$p->text( $x0, $y, sprintf( __( 'From the ReuSteel marketplace: %1$d bars in %2$d listings (%3$s kg) - picture of the marketplace at the time of the calculation, nothing is reserved.', 'sm-weight-engine' ), (int) $t['market_bars'], (int) $t['market_offers'], self::num( $t['market_kg'], 1 ) ), 9.5, false, array( 0.02, 0.45, 0.28 ) );
		}
		$y += 26;
		$colors = array( array( 0.75, 0.93, 0.85 ), array( 0.84, 0.90, 1.0 ), array( 1.0, 0.89, 0.76 ), array( 0.93, 0.87, 1.0 ) );
		foreach ( $r['profiles'] as $pr ) {
			if ( $y > Pdf::H - 120 ) {
				$p->page();
				$y = 46;
				$head();
			}
			$parts = array();
			if ( ! empty( $pr['mine_bars'] ) ) {
				/* translators: %d: bars from the warehouse. */
				$parts[] = sprintf( _n( '%d bar from your warehouse', '%d bars from your warehouse', (int) $pr['mine_bars'], 'sm-weight-engine' ), (int) $pr['mine_bars'] );
			}
			if ( ! empty( $pr['market_bars'] ) ) {
				/* translators: %d: bars from the marketplace. */
				$parts[] = sprintf( _n( '%d bar from the marketplace', '%d bars from the marketplace', (int) $pr['market_bars'], 'sm-weight-engine' ), (int) $pr['market_bars'] );
			}
			if ( $pr['new_bars'] || ! $parts ) {
				/* translators: 1: number of new bars, 2: bar length (m). */
				$parts[] = sprintf( _n( '%1$d bar of %2$s m', '%1$d bars of %2$s m', max( 1, (int) $pr['new_bars'] ), 'sm-weight-engine' ), (int) $pr['new_bars'], self::num( $set['bar'] / 1000 ) );
			}
			if ( $pr['stock_used'] ) {
				/* translators: %d: offcuts from the stock. */
				$parts[] = sprintf( _n( '%d offcut from the stock', '%d offcuts from the stock', (int) $pr['stock_used'], 'sm-weight-engine' ), (int) $pr['stock_used'] );
			}
			$title = $pr['designation'] . ' - ' . implode( ' + ', $parts );
			$p->text( $x0, $y, $title, 12, true );
			/* translators: 1: kg per metre, 2: offcuts (kg), 3: percent. */
			$p->text( $x0 + 470, $y, sprintf( __( '%1$s kg/m · offcuts %2$s kg (%3$s %%)', 'sm-weight-engine' ), self::num( $pr['mass_kg_m'] ), self::num( $pr['scrap_kg'], 1 ), self::num( $pr['scrap_pct'], 1 ) ), 9, false, array( 0.35, 0.4, 0.45 ) );
			$y += 10;
			$lens  = array_values( array_unique( array_merge( ...array_map( static fn( $b ) => $b['pieces'], $pr['bars'] ) ) ) );
			rsort( $lens );
			foreach ( $pr['bars'] as $b ) {
				if ( $y > Pdf::H - 84 ) {
					$p->page();
					$y = 46;
					$head();
				}
				if ( 'market' === $b['source'] ) {
					$co  = '';
					foreach ( $pr['market'] as $mo ) {
						$co = $mo['key'] === $b['origin'] ? $mo['company'] : $co;
					}
					/* translators: %s: length of the bar. */
					$src = sprintf( __( 'marketplace %s m', 'sm-weight-engine' ), self::num( $b['length'] / 1000 ) ) . ( $co ? ' · ' . mb_substr( $co, 0, 18 ) : '' );
				} elseif ( 'mine' === $b['source'] ) {
					$ref = '';
					foreach ( $pr['mine'] ?? array() as $mo ) {
						$ref = $mo['key'] === $b['origin'] ? $mo['ref'] : $ref;
					}
					/* translators: %s: length of the bar. */
					$src = sprintf( __( 'warehouse %s m', 'sm-weight-engine' ), self::num( $b['length'] / 1000 ) ) . ( $ref ? ' · ' . $ref : '' );
				} else {
					$src = 'stock' === $b['source'] ? __( 'stock offcut', 'sm-weight-engine' ) . ' ' . self::num( $b['length'] / 1000 ) . ' m' : __( 'new bar', 'sm-weight-engine' );
				}
				$y  += 8;
				$p->text( $x0, $y + 9, '× ' . (int) $b['count'] . '  ' . $src, 8.5, true, array( 0.3, 0.35, 0.4 ) );
				$bx    = $x0 + 150;
				$bw    = $w - 150;
				$scale = $bw / max( 1, $b['length'] );
				$p->rect( $bx, $y, $bw, 18, array( 0.91, 0.93, 0.94 ), array( 0.6, 0.65, 0.7 ) );
				$cx = $bx;
				foreach ( $b['pieces'] as $piece ) {
					$pw = $piece * $scale;
					$c  = $colors[ array_search( $piece, $lens, true ) % count( $colors ) ];
					$p->rect( $cx, $y, $pw, 18, $c );
					$p->rect( $cx + $pw - 0.8, $y, 1.6, 18, array( 0.07, 0.09, 0.13 ) );
					$p->text_center( $cx, $y + 12.5, $pw, self::num( $piece / 1000 ), 8.5 );
					$cx += $pw + $set['kerf'] * $scale;
				}
				if ( $b['loss'] > 0 ) {
					$lw    = $bx + $bw - $cx;
					$label = self::num( $b['loss'] / 1000 ) . ( $b['reusable'] ? ' *' : '' );
					$p->text_center( $cx, $y + 12.5, max( 0, $lw ), $label, 7.5, false, array( 0.4, 0.45, 0.5 ) );
				}
				$y += 22;
			}
			$y += 16;
		}
		// 0.7.0: what goes to the request for quotation without a cutting plan.
		$x = $r['extras'] ?? array( 'plates' => array(), 'linear' => array() );
		if ( $x['plates'] || $x['linear'] ) {
			if ( $y > Pdf::H - 90 ) {
				$p->page();
				$y = 46;
				$head();
			}
			$p->text( $x0, $y, __( 'Without a cutting plan (request for quotation)', 'sm-weight-engine' ), 11, true );
			$y += 15;
			foreach ( $x['linear'] as $l ) {
				/* translators: 1: profile, 2: metres, 3: bars, 4: bar length, 5: weight. */
				$p->text( $x0, $y, sprintf( __( '%1$s: %2$s m -> %3$d bars of %4$s m (%5$s kg)', 'sm-weight-engine' ), $l['designation'], self::num( $l['m'] ), $l['bars'], self::num( $l['bar'] / 1000 ), self::num( $l['kg'], 1 ) ), 9 );
				$y += 13;
			}
			foreach ( $x['plates'] as $pl ) {
				/* translators: 1: thickness, 2: length, 3: width, 4: number, 5: weight. */
				$p->text( $x0, $y, sprintf( __( 'Plate %1$s mm, %2$s × %3$s mm × %4$d (%5$s kg)', 'sm-weight-engine' ), self::num( $pl['t'], fmod( $pl['t'], 1 ) ? 1 : 0 ), $pl['l'], $pl['w'], $pl['qty'], self::num( $pl['kg'], 1 ) ), 9 );
				$y += 13;
				if ( $y > Pdf::H - 60 ) {
					$p->page();
					$y = 46;
				}
			}
			$y += 10;
		}
		if ( $y > Pdf::H - 70 ) {
			$p->page();
			$y = 46;
		}
		$p->text( $x0, $y, __( '* reusable offcut. Lengths in metres. Weights from the catalogue of the platform (theoretical weights).', 'sm-weight-engine' ), 8, false, array( 0.4, 0.45, 0.5 ) );
		if ( array_filter( $r['profiles'], static fn( $x ) => ! $x['optimal'] ) ) {
			$p->text( $x0, $y + 12, __( 'Long list: this is the best plan found in the time given, not proven to be the best possible.', 'sm-weight-engine' ), 8, false, array( 0.55, 0.35, 0.05 ) );
		}
		return $p->output();
	}

	/** Request for quotation with the new bars to buy. */
	public static function rfq() {
		Language::with_language( self::lang(), array( __CLASS__, 'rfq_in_language' ) );
	}

	public static function rfq_in_language() {
		$in   = self::request();
		$lang = Language::current();
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( Language::url_for( sm_url( 'login' ), $lang ) );
			exit;
		}
		if ( ! sm_can( 'rfq.create' ) ) {
			Forms::back( array( __( 'Your account cannot create requests for quotation.', 'sm-weight-engine' ) ) );
		}
		$r = Cutting::optimize( $in );
		$x = $r['extras'];
		if ( ! $r['profiles'] && ! $x['plates'] && ! $x['linear'] ) {
			Forms::back( array( __( 'Nothing to order: give the pieces to cut first.', 'sm-weight-engine' ) ) );
		}
		$lines = array();
		$desc  = array();
		foreach ( $r['profiles'] as $p ) {
			if ( $p['new_bars'] > 0 ) {
				$lines[] = array( 'family' => $p['family'], 'section' => (string) $p['section_id'], 'qty' => (string) $p['new_bars'], 'length' => (string) ( $in['bar'] / 1000 ) );
			}
			$pieces = array();
			foreach ( $p['bars'] as $b ) {
				foreach ( array_count_values( array_map( 'strval', $b['pieces'] ) ) as $len => $n ) {
					$pieces[ $len ] = ( $pieces[ $len ] ?? 0 ) + $n * $b['count'];
				}
			}
			krsort( $pieces, SORT_NUMERIC );
			$desc[] = $p['designation'] . ' : ' . implode( ', ', array_map( static fn( $l, $n ) => $n . ' × ' . self::num( $l / 1000 ) . ' m', array_keys( $pieces ), $pieces ) );
		}
		// 0.7.0: quantities given in metres (bars to buy) and plates (in the description, with their weight).
		foreach ( $x['linear'] as $l ) {
			$lines[] = array( 'family' => $l['family'], 'section' => (string) $l['section'], 'qty' => (string) $l['bars'], 'length' => (string) ( $l['bar'] / 1000 ) );
			/* translators: 1: profile, 2: metres. */
			$desc[] = sprintf( __( '%1$s: %2$s m (no cutting plan)', 'sm-weight-engine' ), $l['designation'], self::num( $l['m'] ) );
		}
		$pl = array();
		foreach ( $x['plates'] as $p ) {
			/* translators: 1: thickness, 2: length, 3: width, 4: number, 5: weight. */
			$pl[] = sprintf( __( 'Plate %1$s mm, %2$s × %3$s mm × %4$d (%5$s kg)', 'sm-weight-engine' ), self::num( $p['t'], fmod( $p['t'], 1 ) ? 1 : 0 ), $p['l'], $p['w'], $p['qty'], self::num( $p['kg'], 1 ) ) . ( '' !== $p['label'] ? ' - ' . $p['label'] : '' );
		}
		if ( ! $lines && ! $pl ) {
			Forms::back( array( __( 'The offcuts in stock are enough: there is no bar to buy.', 'sm-weight-engine' ) ) );
		}
		$values = array(
			'market'      => 'products',
			'title'       => array( $lang => __( 'Bars according to a cutting plan', 'sm-weight-engine' ) ),
			'lines'       => $lines,
			'description' => ( $desc ? __( 'Pieces to cut (cutting optimiser):', 'sm-weight-engine' ) . "\n- " . implode( "\n- ", $desc ) : '' )
				. ( $pl ? ( $desc ? "\n\n" : '' ) . __( 'Plates:', 'sm-weight-engine' ) . "\n- " . implode( "\n- ", $pl ) : '' ),
		);
		if ( ! $lines ) { // Plates only: the request is by weight.
			$values['designation'] = __( 'Plates', 'sm-weight-engine' );
			$values['quantity']    = (string) round( $x['kg'] );
			$values['unit']        = 'kg';
		}
		Forms::prefill( $values, Language::url_for( sm_url( 'account', array( 'view' => 'rfq', 'new' => 1 ) ), $lang ), __( 'Request pre-filled with the bars of your cutting plan: check it before publishing.', 'sm-weight-engine' ) );
	}
}
