<?php
/**
 * Settings of engineering missions (wp-admin → SM → Engineering → Settings)
 * and the list of mission types.
 *
 * Mission types are stored with each mission by code. Since 0.2.0 they are the
 * engineering services (Services); the filter sm_mission_types also receives
 * the list.
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
			'quote_valid_days'  => 15,      // Validity of the platform quote (a reminder goes the day before).
			'advance_pct'       => 30,      // Advance asked when the client accepts the quote (% of the total).
			'pay_reminder_days' => 3,       // Reminder when the advance is still unpaid this many days after acceptance.
			'requests_per_day'  => 5,       // Mission requests a client may send per day.
			'public_list'       => 0,       // Also show the list of approved engineers under the services (off since 0.2.0).
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
	 * Mission types: code => [ fr, ar ]. Since 0.2.0 the types offered are the
	 * engineering services (Services); the former types are kept, hidden, so the
	 * missions and the engineer profiles that use them keep their name.
	 *
	 * @param bool $all Include hidden services and former types (to read existing data).
	 */
	public static function types( $all = false ) {
		$out = array();
		foreach ( Services::all( $all ) as $code => $s ) {
			$out[ $code ] = array( 'fr' => $s['fr'], 'ar' => $s['ar'] );
		}
		if ( $all ) {
			$saved = (array) self::get( 'types' );
			foreach ( self::default_types() + array_fill_keys( array_keys( $saved ), array() ) as $code => $def ) {
				if ( isset( $out[ $code ] ) ) {
					continue;
				}
				$row = (array) ( $saved[ $code ] ?? array() );
				$fr  = trim( (string) ( $row['fr'] ?? '' ) ) ?: ( $def['fr'] ?? $code );
				$ar  = trim( (string) ( $row['ar'] ?? '' ) ) ?: ( $def['ar'] ?? $fr );
				$out[ $code ] = array( 'fr' => $fr, 'ar' => $ar );
			}
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

	/** TVA rate of the platform (SM Subscriptions → payment settings), 0 without it. */
	public static function tva_rate() {
		if ( class_exists( '\\SM\\Subscriptions\\Settings' ) ) {
			return max( 0, (float) \SM\Subscriptions\Settings::get( 'tva_rate' ) );
		}
		return 0.0;
	}

	/** Payments through the orders of SM Subscriptions (payment page, proof, invoice). */
	public static function payments_ready() {
		return class_exists( '\\SM\\Subscriptions\\Orders' ) && method_exists( '\\SM\\Subscriptions\\Orders', 'create' );
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
		foreach ( array( 'max_files' => array( 1, 50 ), 'file_mb' => array( 1, 200 ), 'quote_valid_days' => array( 1, 365 ), 'requests_per_day' => array( 1, 200 ), 'advance_pct' => array( 0, 100 ), 'pay_reminder_days' => array( 1, 60 ) ) as $k => $r ) {
			$out[ $k ] = max( $r[0], min( $r[1], (int) ( $in[ $k ] ?? $d[ $k ] ) ) );
		}
		$ext               = sanitize_text_field( (string) ( $in['extensions'] ?? $d['extensions'] ) );
		$out['extensions'] = '' !== trim( $ext ) ? $ext : $d['extensions'];

		// Former mission types (before 0.2.0) are kept as they were: they name old missions.
		$out['types'] = (array) $cur['types'];
		if ( isset( $in['project_types'] ) && is_array( $in['project_types'] ) ) {
			ProjectTypes::save( $in['project_types'], (array) ( $in['new_project_type'] ?? array() ) );
		}
		if ( isset( $in['services'] ) && is_array( $in['services'] ) ) {
			Services::save( $in['services'], (array) ( $in['new_service'] ?? array() ) );
		}
		update_option( self::OPTION, $out );
		return $out;
	}
}
