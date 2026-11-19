<?php
/**
 * Labels of the warehouse (PDF, A4 landscape, 4 × 3 per page): reference,
 * profile, length, condition and a QR code that opens the item in the
 * warehouse. To stick on the bars or to copy with paint.
 *
 * @package SM\Stock
 */

namespace SM\Stock;

use SM\Core\Routing\Routes;
use SM\Products\Products;

defined( 'ABSPATH' ) || exit;

final class Labels {

	/** Dark modules of a QR code (rows of booleans). */
	public static function qr( $text ) {
		require_once SM_STOCK_DIR . '/vendor/qrcode.php';
		$q   = Vendor\QRCode::getMinimumQRCode( (string) $text, QR_ERROR_CORRECT_LEVEL_M );
		$n   = $q->getModuleCount();
		$out = array();
		for ( $r = 0; $r < $n; $r++ ) {
			for ( $c = 0; $c < $n; $c++ ) {
				$out[ $r ][ $c ] = (bool) $q->isDark( $r, $c );
			}
		}
		return $out;
	}

	public static function pdf( $company, $tab, array $ids = array() ) {
		$market   = Items::MARKETS[ $tab ];
		$products = Items::products( (int) $company->id, $market );
		if ( $ids ) {
			$products = array_values( array_filter( $products, static fn( $p ) => in_array( (int) $p->id, $ids, true ) ) );
		}
		$refs  = Items::ensure_refs( (int) $company->id, $products );
		$conds = array( 'new' => __( 'New', 'sm-stock' ), 'new_surplus' => __( 'New surplus', 'sm-stock' ), 'excellent' => __( 'Excellent', 'sm-stock' ), 'good' => __( 'Good', 'sm-stock' ), 'inspection' => __( 'To inspect', 'sm-stock' ), 'scrap' => __( 'Scrap', 'sm-stock' ) );
		$cells = array();
		foreach ( $products as $p ) {
			foreach ( Items::variants( $p ) as $v ) {
				$r       = $refs[ $p->id . '|' . $v['key'] ] ?? null;
				$cells[] = array(
					'ref'   => $r ? $r->ref : '',
					'loc'   => $r ? $r->location : '',
					'name'  => trim( $v['designation'] . ( $p->grade ? ' · ' . $p->grade : '' ) ),
					'len'   => $v['length_mm'] ? number_format( $v['length_mm'] / 1000, 2, ',', ' ' ) . ' m' : '',
					'qty'   => 'kg' === $v['unit'] ? number_format( $v['qty'], 0, ',', ' ' ) . ' kg' : (int) $v['qty'] . ' ×',
					'cond'  => 'products' === $p->market ? $conds['new'] : ( $conds[ $p->item_condition ] ?? '' ),
					'url'   => add_query_arg( 'ref', $r ? $r->ref : '', Routes::url( 'account', array( 'view' => 'reuse' === $tab ? 'stock-reuse' : 'stock' ) ) ),
				);
			}
		}
		$pdf = new \SM\WeightEngine\Pdf();
		if ( ! $cells ) {
			$pdf->text( 40, 60, __( 'No item in this warehouse.', 'sm-stock' ), 14, true );
			return $pdf->output();
		}
		$cols = 4;
		$rows = 3;
		$w    = ( \SM\WeightEngine\Pdf::W - 60 ) / $cols;
		$h    = ( \SM\WeightEngine\Pdf::H - 60 ) / $rows;
		foreach ( $cells as $i => $c ) {
			if ( $i && 0 === $i % ( $cols * $rows ) ) {
				$pdf->page();
			}
			$k  = $i % ( $cols * $rows );
			$x  = 30 + ( $k % $cols ) * $w;
			$y  = 30 + (int) floor( $k / $cols ) * $h;
			$pdf->rect( $x + 4, $y + 4, $w - 8, $h - 8, array( 1, 1, 1 ), array( 0.55, 0.6, 0.65 ) );
			$pdf->text( $x + 14, $y + 34, $c['ref'], 22, true );
			$pdf->text( $x + 14, $y + 54, $c['name'], 10.5, true, array( 0.15, 0.2, 0.25 ) );
			$pdf->text( $x + 14, $y + 70, trim( $c['len'] . '  ' . $c['qty'] ), 10, false, array( 0.25, 0.3, 0.35 ) );
			$pdf->text( $x + 14, $y + 85, $c['cond'], 9, false, array( 0.35, 0.4, 0.45 ) );
			if ( $c['loc'] ) {
				$pdf->text( $x + 14, $y + 100, $c['loc'], 8.5, false, array( 0.35, 0.4, 0.45 ) );
			}
			// QR code at the bottom right.
			$m    = self::qr( $c['url'] );
			$n    = count( $m );
			$size = min( 78, $h - 70 );
			$cell = $size / ( $n + 2 );
			$qx   = $x + $w - 14 - $size;
			$qy   = $y + $h - 14 - $size;
			foreach ( $m as $r => $row ) {
				foreach ( $row as $cc => $dark ) {
					if ( $dark ) {
						$pdf->rect( $qx + ( $cc + 1 ) * $cell, $qy + ( $r + 1 ) * $cell, $cell + 0.05, $cell + 0.05, array( 0, 0, 0 ) );
					}
				}
			}
			$pdf->text( $x + 14, $y + $h - 16, 'ReuSteel', 7.5, true, array( 0.02, 0.6, 0.36 ) );
		}
		return $pdf->output();
	}
}
