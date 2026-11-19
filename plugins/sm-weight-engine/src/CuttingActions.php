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
		$y += 26;
		$colors = array( array( 0.75, 0.93, 0.85 ), array( 0.84, 0.90, 1.0 ), array( 1.0, 0.89, 0.76 ), array( 0.93, 0.87, 1.0 ) );
		foreach ( $r['profiles'] as $pr ) {
			if ( $y > Pdf::H - 90 ) {
				$p->page();
				$y = 46;
				$head();
			}
			/* translators: 1: profile, 2: number of new bars, 3: bar length (m). */
			$title = sprintf( _n( '%1$s - %2$d bar of %3$s m', '%1$s - %2$d bars of %3$s m', max( 1, (int) $pr['new_bars'] ), 'sm-weight-engine' ), $pr['designation'], (int) $pr['new_bars'], self::num( $set['bar'] / 1000 ) );
			if ( $pr['stock_used'] ) {
				/* translators: %d: offcuts from the stock. */
				$title .= ' + ' . sprintf( _n( '%d offcut from the stock', '%d offcuts from the stock', (int) $pr['stock_used'], 'sm-weight-engine' ), (int) $pr['stock_used'] );
			}
			$p->text( $x0, $y, $title, 12, true );
			/* translators: 1: kg per metre, 2: offcuts (kg), 3: percent. */
			$p->text( $x0 + 470, $y, sprintf( __( '%1$s kg/m · offcuts %2$s kg (%3$s %%)', 'sm-weight-engine' ), self::num( $pr['mass_kg_m'] ), self::num( $pr['scrap_kg'], 1 ), self::num( $pr['scrap_pct'], 1 ) ), 9, false, array( 0.35, 0.4, 0.45 ) );
			$y += 10;
			$lens  = array_values( array_unique( array_merge( ...array_map( static fn( $b ) => $b['pieces'], $pr['bars'] ) ) ) );
			rsort( $lens );
			foreach ( $pr['bars'] as $b ) {
				if ( $y > Pdf::H - 50 ) {
					$p->page();
					$y = 46;
					$head();
				}
				$src = 'stock' === $b['source'] ? __( 'stock offcut', 'sm-weight-engine' ) . ' ' . self::num( $b['length'] / 1000 ) . ' m' : __( 'new bar', 'sm-weight-engine' );
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
		if ( $y > Pdf::H - 40 ) {
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
		if ( ! $r['profiles'] ) {
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
		if ( ! $lines ) {
			Forms::back( array( __( 'The offcuts in stock are enough: there is no bar to buy.', 'sm-weight-engine' ) ) );
		}
		$values = array(
			'market'      => 'products',
			'title'       => array( $lang => __( 'Bars according to a cutting plan', 'sm-weight-engine' ) ),
			'lines'       => $lines,
			'description' => __( 'Pieces to cut (cutting optimiser):', 'sm-weight-engine' ) . "\n- " . implode( "\n- ", $desc ),
		);
		Forms::prefill( $values, Language::url_for( sm_url( 'account', array( 'view' => 'rfq', 'new' => 1 ) ), $lang ), __( 'Request pre-filled with the bars of your cutting plan: check it before publishing.', 'sm-weight-engine' ) );
	}
}
