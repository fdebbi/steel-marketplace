<?php
/**
 * Spreadsheets sent to the AI (0.3.0): Excel (.xlsx) and CSV are turned into a
 * plain text table (one line per row, cells separated by « | »), every sheet,
 * at most MAX_ROWS rows. Read from the upload, never stored.
 *
 * @package SM\Ai
 */

namespace SM\Ai;

defined( 'ABSPATH' ) || exit;

final class Sheets {

	const MAX_ROWS  = 600;
	const MAX_CHARS = 60000;

	/** Kind of a file from its content and name: xlsx, csv or ''. */
	public static function kind( $path, $name ) {
		$head = (string) file_get_contents( $path, false, null, 0, 4 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$ext  = strtolower( pathinfo( (string) $name, PATHINFO_EXTENSION ) );
		if ( "PK\x03\x04" === $head && 'xlsx' === $ext ) {
			return 'xlsx';
		}
		if ( in_array( $ext, array( 'csv', 'txt' ), true ) && ! preg_match( '/[\x00-\x08\x0E-\x1F]/', (string) file_get_contents( $path, false, null, 0, 2048 ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
			return 'csv';
		}
		return '';
	}

	/** Text of the table, or WP_Error. */
	public static function text( $path, $kind ) {
		$rows = 'xlsx' === $kind ? self::xlsx( $path ) : self::csv( $path );
		if ( is_wp_error( $rows ) ) {
			return $rows;
		}
		$out = '';
		$n   = 0;
		foreach ( $rows as $r ) {
			$cells = array_map( static fn( $c ) => trim( preg_replace( '/\s+/u', ' ', (string) $c ) ), $r );
			if ( '' === implode( '', $cells ) ) {
				continue;
			}
			$out .= ( ++$n ) . ' | ' . implode( ' | ', $cells ) . "\n";
			if ( $n >= self::MAX_ROWS || strlen( $out ) > self::MAX_CHARS ) {
				break;
			}
		}
		if ( '' === trim( $out ) ) {
			return new \WP_Error( 'sm_ai_empty', __( 'The spreadsheet is empty.', 'sm-ai' ) );
		}
		return $out;
	}

	private static function csv( $path ) {
		$raw = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! mb_check_encoding( $raw, 'UTF-8' ) ) {
			$raw = mb_convert_encoding( $raw, 'UTF-8', 'Windows-1252' );
		}
		$raw   = preg_replace( '/^\xEF\xBB\xBF/', '', $raw );
		$first = strtok( $raw, "\n" );
		$sep   = substr_count( (string) $first, ';' ) >= substr_count( (string) $first, ',' ) ? ';' : ',';
		$rows  = array();
		foreach ( preg_split( '/\r\n|\n|\r/', $raw ) as $line ) {
			$rows[] = str_getcsv( $line, $sep );
			if ( count( $rows ) > self::MAX_ROWS ) {
				break;
			}
		}
		return $rows;
	}

	/** Rows of every sheet of a workbook (shared strings and inline values). */
	private static function xlsx( $path ) {
		if ( ! class_exists( '\ZipArchive' ) ) {
			return new \WP_Error( 'sm_ai_type', __( 'Excel files cannot be read on this server: send a PDF or a CSV.', 'sm-ai' ) );
		}
		$zip = new \ZipArchive();
		if ( true !== $zip->open( $path ) ) {
			return new \WP_Error( 'sm_ai_type', __( 'This Excel file cannot be read.', 'sm-ai' ) );
		}
		$strings = array();
		$xml     = $zip->getFromName( 'xl/sharedStrings.xml' );
		if ( $xml ) {
			$doc = self::xml( $xml );
			foreach ( $doc ? $doc->si : array() as $si ) {
				$t = '';
				if ( isset( $si->t ) ) {
					$t = (string) $si->t;
				}
				foreach ( $si->r ?? array() as $r ) {
					$t .= (string) $r->t;
				}
				$strings[] = $t;
			}
		}
		$rows = array();
		for ( $i = 1; $i <= 20 && count( $rows ) < self::MAX_ROWS; $i++ ) {
			$sheet = $zip->getFromName( 'xl/worksheets/sheet' . $i . '.xml' );
			if ( false === $sheet ) {
				break;
			}
			$doc = self::xml( $sheet );
			if ( ! $doc || ! isset( $doc->sheetData ) ) {
				continue;
			}
			foreach ( $doc->sheetData->row as $row ) {
				$cells = array();
				foreach ( $row->c as $c ) {
					$col = self::column( (string) $c['r'] );
					$v   = isset( $c->v ) ? (string) $c->v : ( isset( $c->is->t ) ? (string) $c->is->t : '' );
					if ( 's' === (string) $c['t'] ) {
						$v = $strings[ (int) $v ] ?? '';
					}
					$cells[ $col ] = $v;
				}
				if ( $cells ) {
					$line = array();
					for ( $k = 0, $max = max( array_keys( $cells ) ); $k <= $max && $k < 40; $k++ ) {
						$line[] = $cells[ $k ] ?? '';
					}
					$rows[] = $line;
				}
				if ( count( $rows ) >= self::MAX_ROWS ) {
					break;
				}
			}
		}
		$zip->close();
		return $rows;
	}

	private static function xml( $text ) {
		$prev = libxml_use_internal_errors( true );
		$doc  = simplexml_load_string( $text, 'SimpleXMLElement', LIBXML_NONET );
		libxml_use_internal_errors( $prev );
		return $doc ?: null;
	}

	/** "C12" → 2. */
	private static function column( $ref ) {
		$letters = preg_replace( '/\d+/', '', strtoupper( $ref ) );
		$n       = 0;
		foreach ( str_split( $letters ) as $ch ) {
			$n = $n * 26 + ( ord( $ch ) - 64 );
		}
		return max( 0, $n - 1 );
	}
}
