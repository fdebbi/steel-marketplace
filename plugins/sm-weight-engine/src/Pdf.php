<?php
/**
 * Small PDF writer (0.4.0) for the cutting plan: A4 landscape pages, Helvetica
 * text (Windows-1252, enough for French and English) and filled rectangles.
 * No library: the plan is made of bars, labels and a few lines of text.
 *
 * @package SM\WeightEngine
 */

namespace SM\WeightEngine;

defined( 'ABSPATH' ) || exit;

final class Pdf {

	const W = 842; // A4 landscape, points.
	const H = 595;

	private $pages  = array();
	private $ops    = '';
	private $footer = null;

	/**
	 * Footer of every page (0.4.1): a bold line, a small line with "{page}" and
	 * "{pages}", and a clickable link on the first line.
	 */
	public function footer( $line, $small, $url ) {
		$this->footer = array( $line, $small, $url );
	}

	public function page() {
		if ( '' !== $this->ops ) {
			$this->pages[] = $this->ops;
		}
		$this->ops = '';
	}

	/** Text at x, y from the top-left corner. */
	public function text( $x, $y, $str, $size = 10, $bold = false, $rgb = array( 0.07, 0.09, 0.13 ) ) {
		$t          = $this->encode( $str );
		$this->ops .= sprintf( "BT %s rg /F%d %.1F Tf %.2F %.2F Td (%s) Tj ET\n", $this->rgb( $rgb ), $bold ? 2 : 1, $size, $x, self::H - $y, $t );
	}

	/** Text centred in a box of width $w. */
	public function text_center( $x, $y, $w, $str, $size = 9, $bold = true, $rgb = array( 0.05, 0.23, 0.16 ) ) {
		$tw = $this->width( $str, $size );
		if ( $tw > $w - 2 ) {
			return;
		}
		$this->text( $x + ( $w - $tw ) / 2, $y, $str, $size, $bold, $rgb );
	}

	public function rect( $x, $y, $w, $h, $fill, $stroke = null ) {
		$this->ops .= sprintf( "%s rg %.2F %.2F %.2F %.2F re f\n", $this->rgb( $fill ), $x, self::H - $y - $h, $w, $h );
		if ( $stroke ) {
			$this->ops .= sprintf( "%s RG 0.6 w %.2F %.2F %.2F %.2F re S\n", $this->rgb( $stroke ), $x, self::H - $y - $h, $w, $h );
		}
	}

	/** Approximate width of Helvetica text (average glyph width). */
	public function width( $str, $size ) {
		return strlen( $this->encode( $str, false ) ) * $size * 0.52;
	}

	public function output() {
		if ( '' !== $this->ops || ! $this->pages ) {
			$this->pages[] = $this->ops;
		}
		$objs   = array();
		$objs[] = '<< /Type /Catalog /Pages 2 0 R >>';
		$kids   = array();
		$n      = count( $this->pages );
		$annots = array();
		if ( $this->footer ) {
			list( $line, $small, $url ) = $this->footer;
			foreach ( $this->pages as $i => $content ) {
				$this->ops = '';
				$this->rect( 40, self::H - 44, self::W - 80, 0.6, array( 0.85, 0.87, 0.89 ) );
				$this->text( 40, self::H - 30, $line, 8.5, true, array( 0.2, 0.25, 0.3 ) );
				$this->text( 40, self::H - 18, strtr( $small, array( '{page}' => $i + 1, '{pages}' => $n ) ), 7.5, false, array( 0.45, 0.5, 0.55 ) );
				$this->pages[ $i ] = $content . $this->ops;
				$w                 = min( self::W - 80, $this->width( $line, 8.5 ) );
				$annots[ $i ]      = sprintf( '<< /Type /Annot /Subtype /Link /Rect [40 %.2F %.2F %.2F] /Border [0 0 0] /A << /S /URI /URI (%s) >> >>', self::H - 33, 40 + $w, self::H - 21, $this->encode( $url ) );
			}
			$this->ops = '';
		}
		for ( $i = 0; $i < $n; $i++ ) {
			$kids[] = ( 5 + 2 * $i ) . ' 0 R';
		}
		$objs[] = '<< /Type /Pages /Kids [' . implode( ' ', $kids ) . '] /Count ' . $n . ' >>';
		$objs[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
		$objs[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
		foreach ( $this->pages as $i => $content ) {
			$annot  = isset( $annots[ $i ] ) ? ' /Annots [' . ( 5 + 2 * $n + $i ) . ' 0 R]' : '';
			$objs[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::W . ' ' . self::H . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . ( 6 + 2 * $i ) . ' 0 R' . $annot . ' >>';
			$objs[] = '<< /Length ' . strlen( $content ) . " >>\nstream\n" . $content . "\nendstream";
		}
		foreach ( $annots as $a ) {
			$objs[] = $a;
		}
		$pdf  = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
		$offs = array();
		foreach ( $objs as $k => $o ) {
			$offs[] = strlen( $pdf );
			$pdf   .= ( $k + 1 ) . " 0 obj\n" . $o . "\nendobj\n";
		}
		$xref = strlen( $pdf );
		$pdf .= 'xref' . "\n0 " . ( count( $objs ) + 1 ) . "\n0000000000 65535 f \n";
		foreach ( $offs as $o ) {
			$pdf .= sprintf( "%010d 00000 n \n", $o );
		}
		$pdf .= 'trailer << /Size ' . ( count( $objs ) + 1 ) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
		return $pdf;
	}

	private function rgb( array $c ) {
		return sprintf( '%.3F %.3F %.3F', $c[0], $c[1], $c[2] );
	}

	/** UTF-8 → Windows-1252, escaped for a PDF string. */
	private function encode( $str, $escape = true ) {
		$str = str_replace( array( "\u{202F}", "\u{00A0}", '’', '×', '≥', '—', '…' ), array( ' ', ' ', "'", 'x', '>=', '-', '...' ), (string) $str );
		$out = function_exists( 'iconv' ) ? @iconv( 'UTF-8', 'Windows-1252//TRANSLIT', $str ) : utf8_decode( $str ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		$out = false === $out ? preg_replace( '/[^\x20-\x7E]/', '?', $str ) : $out;
		return $escape ? strtr( $out, array( '\\' => '\\\\', '(' => '\\(', ')' => '\\)' ) ) : $out;
	}
}
