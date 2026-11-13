<?php
/**
 * Lists of the partner engineer application (0.4.0): professional titles and
 * specialities, chosen in drop-down lists. The administrator renames them
 * (FR / AR), hides, reorders or adds entries (Engineering settings). "other"
 * stays last and asks the engineer to write his own.
 *
 * Stored in the option sm_engineering_lists: list => code => { fr, ar, off, order }.
 *
 * @package SM\Engineering
 */

namespace SM\Engineering;

defined( 'ABSPATH' ) || exit;

final class Lists {

	const OPTION = 'sm_engineering_lists';

	/** Built-in entries: list => code => [ fr, ar ]. */
	public static function defaults() {
		return array(
			'titles'       => array(
				'doctor'         => array( 'Docteur en génie civil', 'دكتور في الهندسة المدنية' ),
				'state_engineer' => array( 'Ingénieur d’État en génie civil', 'مهندس دولة في الهندسة المدنية' ),
				'master'         => array( 'Master en génie civil', 'ماستر في الهندسة المدنية' ),
				'other'          => array( 'Autre (à préciser)', 'أخرى (حدد)' ),
			),
			'specialities' => array(
				'steel'            => array( 'Structure métallique', 'المنشآت المعدنية' ),
				'civil_industrial' => array( 'Construction civile et industrielle', 'البناء المدني والصناعي' ),
				'structure'        => array( 'Structure', 'الهياكل' ),
				'other'            => array( 'Autre (à préciser)', 'أخرى (حدد)' ),
			),
		);
	}

	public static function names() {
		return array_keys( self::defaults() );
	}

	private static function saved( $list ) {
		$s = get_option( self::OPTION, array() );
		return is_array( $s ) && is_array( $s[ $list ] ?? null ) ? $s[ $list ] : array();
	}

	/**
	 * @param string $list titles|specialities
	 * @param bool   $all  Include hidden entries (to read saved profiles).
	 * @return array code => { code, fr, ar, off, order }
	 */
	public static function all( $list, $all = false ) {
		$defs  = self::defaults()[ $list ] ?? array();
		$saved = self::saved( $list );
		$out   = array();
		$pos   = 0;
		foreach ( $defs + array_fill_keys( array_keys( $saved ), array() ) as $code => $def ) {
			$row = (array) ( $saved[ $code ] ?? array() );
			++$pos;
			if ( ! $def && empty( $row['fr'] ) ) {
				continue;
			}
			$fr = trim( (string) ( $row['fr'] ?? '' ) ) ?: (string) ( $def[0] ?? $code );
			$ar = trim( (string) ( $row['ar'] ?? '' ) ) ?: (string) ( $def[1] ?? $fr );
			$e  = array(
				'code'  => (string) $code,
				'fr'    => $fr,
				'ar'    => $ar,
				'off'   => empty( $row['off'] ) || 'other' === $code ? 0 : 1, // "Other" cannot be hidden.
				'order' => 'other' === $code ? 999 : ( isset( $row['order'] ) ? (int) $row['order'] : $pos * 10 ),
			);
			if ( ! $all && $e['off'] ) {
				continue;
			}
			$out[ $code ] = $e;
		}
		uasort( $out, static fn( $a, $b ) => $a['order'] <=> $b['order'] );
		return apply_filters( 'sm_engineering_list', $out, $list, $all );
	}

	/** code => label in the visitor's language. */
	public static function options( $list ) {
		$ar = 'ar' === sm_current_language();
		return array_map( static fn( $e ) => $ar ? $e['ar'] : $e['fr'], self::all( $list ) );
	}

	/** Label of a code; "other" gives the text written by the engineer. */
	public static function label( $list, $code, $other = '', $lang = '' ) {
		if ( 'other' === $code && '' !== trim( (string) $other ) ) {
			return trim( (string) $other );
		}
		$e = self::all( $list, true )[ (string) $code ] ?? null;
		if ( ! $e ) {
			return '';
		}
		$lang = $lang ? $lang : sm_current_language();
		return 'ar' === $lang ? $e['ar'] : $e['fr'];
	}

	public static function save( $list, array $rows, array $new = array() ) {
		if ( ! in_array( $list, self::names(), true ) ) {
			return;
		}
		$opt  = get_option( self::OPTION, array() );
		$opt  = is_array( $opt ) ? $opt : array();
		$out  = self::saved( $list );
		$defs = self::defaults()[ $list ];
		foreach ( $rows as $code => $row ) {
			$code = sanitize_key( $code );
			if ( '' === $code || ! is_array( $row ) ) {
				continue;
			}
			$out[ $code ] = array(
				'fr'    => mb_substr( sanitize_text_field( (string) ( $row['fr'] ?? '' ) ), 0, 120 ),
				'ar'    => mb_substr( sanitize_text_field( (string) ( $row['ar'] ?? '' ) ), 0, 120 ),
				'off'   => empty( $row['on'] ) ? 1 : 0,
				'order' => max( 0, min( 998, (int) ( $row['order'] ?? 0 ) ) ),
			);
		}
		$title = trim( sanitize_text_field( (string) ( $new['fr'] ?? '' ) ) );
		if ( '' !== $title ) {
			$base = substr( str_replace( '-', '_', sanitize_title( $title ) ), 0, 30 ) ?: 'entry';
			$code = $base;
			for ( $i = 2; isset( $out[ $code ] ) || isset( $defs[ $code ] ); $i++ ) {
				$code = $base . '_' . $i;
			}
			$out[ $code ] = array(
				'fr'    => mb_substr( $title, 0, 120 ),
				'ar'    => mb_substr( sanitize_text_field( (string) ( $new['ar'] ?? '' ) ), 0, 120 ),
				'off'   => 0,
				'order' => 900,
			);
		}
		$opt[ $list ] = $out;
		update_option( self::OPTION, $opt, false );
	}
}
