<?php
/**
 * Types of project of a study request (0.3.0): industrial building, storage
 * hangar, farm building… Required in the request form; renamed (FR / AR),
 * hidden, reordered or added by the administrator (Engineering settings).
 *
 * Stored in the option sm_engineering_project_types: code => { fr, ar, off, order }.
 *
 * @package SM\Engineering
 */

namespace SM\Engineering;

defined( 'ABSPATH' ) || exit;

final class ProjectTypes {

	const OPTION = 'sm_engineering_project_types';

	/** Built-in types: code => [ fr, ar ]. "other" stays last. */
	public static function defaults() {
		return array(
			'industrial'  => array( 'Bâtiment industriel / usine', 'مبنى صناعي / مصنع' ),
			'storage'     => array( 'Hangar de stockage', 'هنغار تخزين' ),
			'agricultural' => array( 'Bâtiment agricole', 'مبنى فلاحي' ),
			'livestock'   => array( 'Bâtiment d’élevage', 'مبنى تربية الحيوانات' ),
			'commercial'  => array( 'Bâtiment commercial / showroom', 'مبنى تجاري / صالة عرض' ),
			'offices'     => array( 'Bureaux / bâtiment administratif', 'مكاتب / مبنى إداري' ),
			'public'      => array( 'Équipement public (salle de sport, école…)', 'تجهيز عمومي (قاعة رياضة، مدرسة…)' ),
			'housing'     => array( 'Habitation / construction métallique légère', 'سكن / بناء معدني خفيف' ),
			'other'       => array( 'Autre (précisez dans la description)', 'أخرى (حدد في الوصف)' ),
		);
	}

	private static function saved() {
		$s = get_option( self::OPTION, array() );
		return is_array( $s ) ? $s : array();
	}

	/**
	 * @param bool $all Include hidden types (to read existing requests).
	 * @return array code => { code, fr, ar, off, order }
	 */
	public static function all( $all = false ) {
		$saved = self::saved();
		$out   = array();
		$pos   = 0;
		foreach ( self::defaults() + array_fill_keys( array_keys( $saved ), array() ) as $code => $def ) {
			$row = (array) ( $saved[ $code ] ?? array() );
			++$pos;
			if ( ! $def && empty( $row['fr'] ) ) {
				continue;
			}
			$fr = trim( (string) ( $row['fr'] ?? '' ) ) ?: (string) ( $def[0] ?? $code );
			$ar = trim( (string) ( $row['ar'] ?? '' ) ) ?: (string) ( $def[1] ?? $fr );
			$t  = array(
				'code'  => (string) $code,
				'fr'    => $fr,
				'ar'    => $ar,
				'off'   => empty( $row['off'] ) ? 0 : 1,
				'order' => isset( $row['order'] ) ? (int) $row['order'] : ( 'other' === $code ? 990 : $pos * 10 ),
			);
			if ( ! $all && $t['off'] ) {
				continue;
			}
			$out[ $code ] = $t;
		}
		uasort( $out, static fn( $a, $b ) => $a['order'] <=> $b['order'] );
		return apply_filters( 'sm_engineering_project_types', $out, $all );
	}

	/** code => label in the visitor's language. */
	public static function options() {
		$ar = 'ar' === sm_current_language();
		return array_map( static fn( $t ) => $ar ? $t['ar'] : $t['fr'], self::all() );
	}

	public static function label( $code ) {
		$t = self::all( true )[ (string) $code ] ?? null;
		if ( ! $t ) {
			return '';
		}
		return 'ar' === sm_current_language() ? $t['ar'] : $t['fr'];
	}

	public static function save( array $rows, array $new = array() ) {
		$out = self::saved();
		foreach ( $rows as $code => $row ) {
			$code = sanitize_key( $code );
			if ( '' === $code || ! is_array( $row ) ) {
				continue;
			}
			$out[ $code ] = array(
				'fr'    => mb_substr( sanitize_text_field( (string) ( $row['fr'] ?? '' ) ), 0, 120 ),
				'ar'    => mb_substr( sanitize_text_field( (string) ( $row['ar'] ?? '' ) ), 0, 120 ),
				'off'   => empty( $row['on'] ) ? 1 : 0,
				'order' => max( 0, min( 999, (int) ( $row['order'] ?? 0 ) ) ),
			);
		}
		$title = trim( sanitize_text_field( (string) ( $new['fr'] ?? '' ) ) );
		if ( '' !== $title ) {
			$base = substr( str_replace( '-', '_', sanitize_title( $title ) ), 0, 30 ) ?: 'project';
			$code = $base;
			for ( $i = 2; isset( $out[ $code ] ) || isset( self::defaults()[ $code ] ); $i++ ) {
				$code = $base . '_' . $i;
			}
			$out[ $code ] = array(
				'fr'    => mb_substr( $title, 0, 120 ),
				'ar'    => mb_substr( sanitize_text_field( (string) ( $new['ar'] ?? '' ) ), 0, 120 ),
				'off'   => 0,
				'order' => 980,
			);
		}
		update_option( self::OPTION, $out, false );
		return $out;
	}
}
