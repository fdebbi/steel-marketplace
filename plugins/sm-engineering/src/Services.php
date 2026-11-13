<?php
/**
 * Engineering services offered by the platform (0.2.0).
 *
 * The engineering page shows these services (title, image, prestations); a client
 * asks for one service and ticks the prestations wanted; a partner engineer says
 * which services he can carry out. The administrator renames them (FR / AR),
 * changes the image and the prestations, hides or reorders them, and may add one
 * (wp-admin → SM → Engineering settings).
 *
 * Stored in the option sm_engineering_services: code => {
 *   fr, ar          title
 *   items_fr        prestations, one per line
 *   items_ar        the same prestations in Arabic, same order (empty line = French)
 *   image           attachment id (0 = the theme's drawing named by "demo")
 *   off             1 = hidden
 *   order           position on the page
 * }
 *
 * The Arabic texts below are a first translation: to be read again by an
 * Arabic-speaking engineer before the launch.
 *
 * @package SM\Engineering
 */

namespace SM\Engineering;

defined( 'ABSPATH' ) || exit;

final class Services {

	const OPTION    = 'sm_engineering_services';
	const MAX_ITEMS = 20;

	/** Built-in services: code => [ fr, ar, items => [ [ fr, ar ] ], demo ]. */
	public static function defaults() {
		return array(
			'studies'     => array(
				'fr'    => 'Études et Conception',
				'ar'    => 'الدراسات والتصميم',
				'demo'  => 'warehouse-frame',
				'items' => array(
					array( 'Études de faisabilité', 'دراسات الجدوى' ),
					array( 'Conception de hangars et structures métalliques', 'تصميم الهنغارات والهياكل المعدنية' ),
					array( 'APS / APD', 'الدراسة الأولية الموجزة والمفصلة (APS / APD)' ),
					array( 'Conception des structures porteuses', 'تصميم الهياكل الحاملة' ),
				),
			),
			'calc_notes'  => array(
				'fr'    => 'Note de Calcul',
				'ar'    => 'مذكرة الحساب',
				'demo'  => 'detail-section',
				'items' => array(
					array( 'Calcul et dimensionnement des profilés métalliques', 'حساب وتحجيم المقاطع المعدنية' ),
					array( 'Vérification des poteaux, poutres, pannes et contreventements', 'التحقق من الأعمدة والعوارض والمدادات وأنظمة التكتيف' ),
					array( 'Dimensionnement des assemblages (boulonnés et soudés)', 'تحجيم الوصلات (المثبتة بالبراغي والملحومة)' ),
					array( 'Vérification de la stabilité et de la résistance de la structure', 'التحقق من استقرار الهيكل ومقاومته' ),
					array( 'Élaboration des notes de calcul justificatives', 'إعداد مذكرات الحساب التبريرية' ),
				),
			),
			'modelling'   => array(
				'fr'    => 'Dessin et Modélisation 2D/3D',
				'ar'    => 'الرسم والنمذجة ثنائية وثلاثية الأبعاد',
				'demo'  => 'detail-stack',
				'items' => array(
					array( 'Plans d’exécution et de fabrication', 'مخططات التنفيذ والتصنيع' ),
					array( 'Plans de montage', 'مخططات التركيب' ),
					array( 'Modélisation 3D / BIM', 'النمذجة ثلاثية الأبعاد / BIM' ),
					array( 'Nomenclature des éléments', 'جدول كميات العناصر' ),
				),
			),
			'expertise'   => array(
				'fr'    => 'Expertise et Inspection Technique',
				'ar'    => 'الخبرة والمعاينة التقنية',
				'demo'  => 'inspection',
				'items' => array(
					array( 'Expertise des structures métalliques', 'خبرة الهياكل المعدنية' ),
					array( 'Diagnostic et évaluation de l’état des structures', 'تشخيص وتقييم حالة الهياكل' ),
					array( 'Inspection des soudures et des assemblages', 'معاينة اللحامات والوصلات' ),
					array( 'Contrôle dimensionnel et détection des anomalies', 'المراقبة البعدية وكشف العيوب' ),
				),
			),
			'tender_docs' => array(
				'fr'    => 'CCTP, DQE et BPU',
				'ar'    => 'دفتر الشروط التقنية، الكشف الكمي والتقديري وجدول الأسعار',
				'demo'  => 'engineering',
				'items' => array(
					array( 'Élaboration du CCTP', 'إعداد دفتر الشروط التقنية الخاصة (CCTP)' ),
					array( 'Établissement du DQE', 'إعداد الكشف الكمي والتقديري (DQE)' ),
					array( 'Élaboration du BPU', 'إعداد جدول الأسعار بالوحدة (BPU)' ),
					array( 'Estimation des coûts et préparation des dossiers de consultation', 'تقدير التكاليف وإعداد ملفات الاستشارة' ),
				),
			),
			'site_follow' => array(
				'fr'    => 'Suivi et Assistance Technique',
				'ar'    => 'المتابعة والمساعدة التقنية',
				'demo'  => 'welding',
				'items' => array(
					array( 'Suivi de fabrication', 'متابعة التصنيع' ),
					array( 'Suivi du montage sur chantier', 'متابعة التركيب في الموقع' ),
					array( 'Assistance technique', 'المساعدة التقنية' ),
					array( 'Réception des travaux', 'استلام الأشغال' ),
				),
			),
		);
	}

	private static function saved() {
		$s = get_option( self::OPTION, array() );
		return is_array( $s ) ? $s : array();
	}

	private static function lines( $text ) {
		return array_values( array_filter( array_map( 'trim', preg_split( '/\R/u', (string) $text ) ), static fn( $l ) => '' !== $l ) );
	}

	/**
	 * Every service, in page order.
	 *
	 * @param bool $all Include hidden services.
	 * @return array code => { code, fr, ar, items: list of {fr, ar}, image, demo, off, order }
	 */
	public static function all( $all = false ) {
		$saved = self::saved();
		$defs  = self::defaults();
		$out   = array();
		$pos   = 0;
		foreach ( $defs + array_fill_keys( array_keys( $saved ), array() ) as $code => $def ) {
			$row = (array) ( $saved[ $code ] ?? array() );
			++$pos;
			if ( ! $def && empty( $row['fr'] ) ) {
				continue;
			}
			$fr = trim( (string) ( $row['fr'] ?? '' ) ) ?: (string) ( $def['fr'] ?? $code );
			$ar = trim( (string) ( $row['ar'] ?? '' ) ) ?: (string) ( $def['ar'] ?? $fr );
			if ( isset( $row['items_fr'] ) ) {
				$ifr   = self::lines( $row['items_fr'] );
				$iar   = preg_split( '/\R/u', (string) ( $row['items_ar'] ?? '' ) );
				$items = array();
				foreach ( $ifr as $i => $l ) {
					$a       = trim( (string) ( $iar[ $i ] ?? '' ) );
					$items[] = array( 'fr' => $l, 'ar' => '' !== $a ? $a : $l );
				}
			} else {
				$items = array_map( static fn( $p ) => array( 'fr' => $p[0], 'ar' => $p[1] ), (array) ( $def['items'] ?? array() ) );
			}
			$s = array(
				'code'  => (string) $code,
				'fr'    => $fr,
				'ar'    => $ar,
				'items' => array_slice( $items, 0, self::MAX_ITEMS ),
				'image' => (int) ( $row['image'] ?? 0 ),
				'demo'  => (string) ( $def['demo'] ?? 'engineering' ),
				'off'   => empty( $row['off'] ) ? 0 : 1,
				'order' => isset( $row['order'] ) ? (int) $row['order'] : $pos * 10,
			);
			if ( ! $all && $s['off'] ) {
				continue;
			}
			$out[ $code ] = $s;
		}
		uasort( $out, static fn( $a, $b ) => $a['order'] <=> $b['order'] );
		return apply_filters( 'sm_engineering_services', $out, $all );
	}

	public static function get( $code, $all = false ) {
		return self::all( $all )[ sanitize_key( (string) $code ) ] ?? null;
	}

	/** Text in the visitor's language. */
	public static function text( array $pair ) {
		return 'ar' === sm_current_language() ? (string) $pair['ar'] : (string) $pair['fr'];
	}

	/**
	 * The prestations ticked by a client, checked against the service.
	 *
	 * @param string $code    Service.
	 * @param array  $indexes Positions in the list of the service.
	 * @return array list of { fr, ar } (a copy kept with the request).
	 */
	public static function pick( $code, array $indexes ) {
		$s = self::get( $code );
		if ( ! $s ) {
			return array();
		}
		$out = array();
		foreach ( array_unique( array_map( 'intval', $indexes ) ) as $i ) {
			if ( isset( $s['items'][ $i ] ) ) {
				$out[ $i ] = $s['items'][ $i ];
			}
		}
		ksort( $out );
		return array_values( $out );
	}

	/** Save the admin table. */
	public static function save( array $rows, array $new = array() ) {
		$cur = self::saved();
		$out = array();
		foreach ( $rows as $code => $row ) {
			$code = sanitize_key( $code );
			if ( '' === $code || ! is_array( $row ) ) {
				continue;
			}
			$out[ $code ] = array(
				'fr'       => mb_substr( sanitize_text_field( (string) ( $row['fr'] ?? '' ) ), 0, 120 ),
				'ar'       => mb_substr( sanitize_text_field( (string) ( $row['ar'] ?? '' ) ), 0, 120 ),
				'items_fr' => implode( "\n", array_slice( array_map( static fn( $l ) => mb_substr( sanitize_text_field( $l ), 0, 200 ), self::lines( $row['items_fr'] ?? '' ) ), 0, self::MAX_ITEMS ) ),
				'items_ar' => implode( "\n", array_slice( array_map( static fn( $l ) => mb_substr( sanitize_text_field( $l ), 0, 200 ), preg_split( '/\R/u', (string) ( $row['items_ar'] ?? '' ) ) ), 0, self::MAX_ITEMS ) ),
				'image'    => self::image_id( $row['image'] ?? 0 ),
				'off'      => empty( $row['on'] ) ? 1 : 0,
				'order'    => max( 0, min( 999, (int) ( $row['order'] ?? 0 ) ) ),
			);
		}
		// Services added earlier and not posted are kept.
		foreach ( $cur as $code => $row ) {
			if ( ! isset( $out[ $code ] ) ) {
				$out[ $code ] = $row;
			}
		}
		$title = trim( sanitize_text_field( (string) ( $new['fr'] ?? '' ) ) );
		if ( '' !== $title ) {
			$base = substr( str_replace( '-', '_', sanitize_title( $title ) ), 0, 30 ) ?: 'service';
			$code = $base;
			for ( $i = 2; isset( $out[ $code ] ) || isset( self::defaults()[ $code ] ) || isset( Settings::default_types()[ $code ] ); $i++ ) {
				$code = $base . '_' . $i;
			}
			$out[ $code ] = array(
				'fr'       => mb_substr( $title, 0, 120 ),
				'ar'       => mb_substr( sanitize_text_field( (string) ( $new['ar'] ?? '' ) ), 0, 120 ),
				'items_fr' => '',
				'items_ar' => '',
				'image'    => 0,
				'off'      => 0,
				'order'    => 900,
			);
		}
		update_option( self::OPTION, $out, false );
		return $out;
	}

	/** An image of the media library, or 0. */
	private static function image_id( $id ) {
		$id = absint( $id );
		return ( $id && wp_attachment_is_image( $id ) ) ? $id : 0;
	}
}
