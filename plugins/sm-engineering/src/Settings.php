<?php
/**
 * Settings of engineering missions (wp-admin → SM → Engineering → Settings)
 * and the list of mission types.
 *
 * Mission types are stored with each mission by code. The built-in list can be
 * renamed (FR / AR), hidden or extended by the administrator; the filter
 * sm_mission_types also receives it.
 *
 * @package SM\Engineering
 */

namespace SM\Engineering;

defined( 'ABSPATH' ) || exit;

final class Settings {

	const OPTION = 'sm_engineering_settings';

	public static function defaults() {
		return array(
			'max_files'         => 10,      // Files per upload (brief, deliverables).
			'file_mb'           => 25,      // Size of one file.
			'extensions'        => 'pdf, jpg, jpeg, png, webp, dwg, dxf, ifc, zip, xlsx, xls, docx, doc',
			'quote_valid_days'  => 30,      // Validity of the platform quote.
			'requests_per_day'  => 5,       // Mission requests a client may send per day.
			'public_list'       => 1,       // Show the list of approved engineers.
			'show_missions'     => 1,       // Show the number of completed missions on the list.
			'applications_open' => 1,       // Accept new engineer applications.
			'types'             => array(), // code => [ fr, ar, off ].
		);
	}

	public static function all() {
		$s = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $s ) ? $s : array() );
	}

	public static function get( $key ) {
		return self::all()[ $key ] ?? null;
	}

	/** Built-in mission types: code => [ fr, ar ]. */
	public static function default_types() {
		return array(
			'design'      => array( 'fr' => 'Conception de charpente métallique', 'ar' => 'تصميم الهياكل الفولاذية' ),
			'calculation' => array( 'fr' => 'Notes de calcul', 'ar' => 'مذكرات الحساب' ),
			'connections' => array( 'fr' => 'Calcul des assemblages', 'ar' => 'حساب الوصلات' ),
			'drawings'    => array( 'fr' => 'Plans techniques', 'ar' => 'المخططات التقنية' ),
			'shop'        => array( 'fr' => 'Plans d’atelier', 'ar' => 'مخططات الورشة' ),
			'fabrication' => array( 'fr' => 'Plans de fabrication', 'ar' => 'مخططات التصنيع' ),
			'inspection'  => array( 'fr' => 'Inspection technique sur site', 'ar' => 'معاينة تقنية في الموقع' ),
			'assessment'  => array( 'fr' => 'Expertise de structure d’occasion', 'ar' => 'خبرة هيكل مستعمل' ),
			'consultancy' => array( 'fr' => 'Conseil en ingénierie', 'ar' => 'استشارة هندسية' ),
		);
	}

	/**
	 * Mission types: code => [ fr, ar ].
	 *
	 * @param bool $all Include hidden types (to read existing missions).
	 */
	public static function types( $all = false ) {
		$saved = (array) self::get( 'types' );
		$out   = array();
		foreach ( self::default_types() + array_fill_keys( array_keys( $saved ), array() ) as $code => $def ) {
			$row = (array) ( $saved[ $code ] ?? array() );
			if ( ! $all && ! empty( $row['off'] ) ) {
				continue;
			}
			$fr = trim( (string) ( $row['fr'] ?? '' ) ) ?: ( $def['fr'] ?? $code );
			$ar = trim( (string) ( $row['ar'] ?? '' ) ) ?: ( $def['ar'] ?? $fr );
			$out[ $code ] = array( 'fr' => $fr, 'ar' => $ar );
		}
		return apply_filters( 'sm_mission_types', $out, $all );
	}

	/** Label of a mission type in the current language. */
	public static function type_label( $code ) {
		$t = self::types( true )[ $code ] ?? null;
		if ( ! $t ) {
			return (string) $code;
		}
		return 'ar' === sm_current_language() ? $t['ar'] : $t['fr'];
	}

	/** Allowed file extensions (lower case). */
	public static function extensions() {
		$list = array_filter( array_map( static fn( $e ) => strtolower( trim( $e, " .\t\n" ) ), explode( ',', (string) self::get( 'extensions' ) ) ) );
		// Executable or web files are never accepted, whatever the setting.
		return array_values( array_diff( array_unique( $list ), array( 'php', 'phtml', 'phar', 'js', 'html', 'htm', 'svg', 'exe', 'sh', 'bat', 'cmd', 'msi', 'jar' ) ) );
	}

	public static function save( array $in ) {
		$d   = self::defaults();
		$cur = self::all();
		$out = array();
		foreach ( array( 'public_list', 'show_missions', 'applications_open' ) as $k ) {
			$out[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
		}
		foreach ( array( 'max_files' => array( 1, 50 ), 'file_mb' => array( 1, 200 ), 'quote_valid_days' => array( 1, 365 ), 'requests_per_day' => array( 1, 200 ) ) as $k => $r ) {
			$out[ $k ] = max( $r[0], min( $r[1], (int) ( $in[ $k ] ?? $d[ $k ] ) ) );
		}
		$ext               = sanitize_text_field( (string) ( $in['extensions'] ?? $d['extensions'] ) );
		$out['extensions'] = '' !== trim( $ext ) ? $ext : $d['extensions'];

		$types = array();
		foreach ( (array) ( $in['types'] ?? array() ) as $code => $row ) {
			$code = sanitize_key( $code );
			if ( '' === $code ) {
				continue;
			}
			$types[ $code ] = array(
				'fr'  => mb_substr( sanitize_text_field( (string) ( $row['fr'] ?? '' ) ), 0, 100 ),
				'ar'  => mb_substr( sanitize_text_field( (string) ( $row['ar'] ?? '' ) ), 0, 100 ),
				'off' => empty( $row['on'] ) ? 1 : 0,
			);
		}
		// Added types that are not in the built-in list keep existing when not posted.
		foreach ( (array) $cur['types'] as $code => $row ) {
			if ( ! isset( $types[ $code ] ) && ! isset( self::default_types()[ $code ] ) ) {
				$types[ $code ] = $row;
			}
		}
		$new = trim( sanitize_text_field( (string) ( $in['new_type']['fr'] ?? '' ) ) );
		if ( '' !== $new ) {
			$base = substr( str_replace( '-', '_', sanitize_title( $new ) ), 0, 30 ) ?: 'type';
			$code = $base;
			for ( $i = 2; isset( $types[ $code ] ) || isset( self::default_types()[ $code ] ); $i++ ) {
				$code = $base . '_' . $i;
			}
			$types[ $code ] = array( 'fr' => mb_substr( $new, 0, 100 ), 'ar' => mb_substr( sanitize_text_field( (string) ( $in['new_type']['ar'] ?? '' ) ), 0, 100 ), 'off' => 0 );
		}
		$out['types'] = $types;
		update_option( self::OPTION, $out );
		return $out;
	}
}
