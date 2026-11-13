<?php
/**
 * The "buildings" market, added to the SM Products listing engine.
 *
 * 0.2.0: structural systems renamed (codes kept: portal = column / rafter frame,
 * tapered = welded built-up section PRS, truss); element type "truss" added.
 * Structural system, roof and cladding are declared by the seller as listed
 * choices; "what the price includes" is a set of yes/no answers shown as a
 * checklist (not as specification rows).
 *
 * @package SM\Buildings
 */

namespace SM\Buildings;

defined( 'ABSPATH' ) || exit;

final class Market {

	const CODE = 'buildings';
	const KIND = 'building';

	public static function init() {
		add_filter( 'sm_products_markets', array( __CLASS__, 'register' ) );
		add_filter( 'sm_products_kinds', static fn( $k ) => array_merge( (array) $k, array( self::KIND ) ) );
		add_filter( 'sm_products_max_lines', static fn( $max, $cat ) => self::KIND === ( $cat->kind ?? '' ) ? max( $max, (int) Settings::get( 'max_lines' ) ) : $max, 10, 2 );
	}

	public static function register( array $markets ) {
		$markets[ self::CODE ] = self::definition();
		return $markets;
	}

	private static function opt( $value, $fr, $ar ) {
		return array( 'value' => $value, 'fr' => $fr, 'ar' => $ar );
	}

	public static function definition() {
		$def = array(
			'label'         => array( 'fr' => 'Bâtiments métalliques', 'ar' => 'المباني الفولاذية' ),
			'intro'         => array(
				'fr' => 'Hangars, entrepôts, ateliers et kits, montés ou démontés : dimensions, inventaire et prix au m².',
				'ar' => 'هناجر ومستودعات وورشات وهياكل جاهزة، مركبة أو مفككة: الأبعاد والجرد والسعر للمتر المربع.',
			),
			'icon'          => 'warehouse',
			'cap'           => 'listing.buildings',
			'conditions'    => self::conditions(),
			'fields'        => array(
				array(
					'key'      => 'system',
					'type'     => 'select',
					'label'    => array( 'fr' => 'Système porteur', 'ar' => 'النظام الإنشائي' ),
					'required' => true,
					'options'  => array(
						self::opt( 'portal', 'Portique poteau / traverse', 'إطار عمود / عارضة' ),
						self::opt( 'tapered', 'Profilé reconstitué soudé (PRS)', 'مقطع مركب ملحوم (PRS)' ),
						self::opt( 'truss', 'Ferme en treillis', 'جملون شبكي' ),
						self::opt( 'other', 'Autre', 'أخرى' ),
					),
				),
				array(
					'key'      => 'roof',
					'type'     => 'select',
					'label'    => array( 'fr' => 'Forme de la toiture', 'ar' => 'شكل السقف' ),
					'required' => true,
					'options'  => array(
						self::opt( 'duo', 'Deux versants', 'منحدران' ),
						self::opt( 'mono', 'Un versant', 'منحدر واحد' ),
						self::opt( 'multi', 'Multi-travées / sheds', 'متعدد البحور' ),
						self::opt( 'curved', 'Cintrée', 'مقوس' ),
						self::opt( 'other', 'Autre', 'أخرى' ),
					),
				),
				array(
					'key'      => 'roof_cover',
					'type'     => 'select',
					'label'    => array( 'fr' => 'Couverture', 'ar' => 'التغطية' ),
					'required' => true,
					'options'  => array(
						self::opt( 'sandwich', 'Panneaux sandwich', 'ألواح ساندويتش' ),
						self::opt( 'ribbed', 'Bac acier / tôle nervurée', 'صفائح فولاذية مضلعة' ),
						self::opt( 'none', 'Sans couverture', 'بدون تغطية' ),
						self::opt( 'other', 'Autre', 'أخرى' ),
					),
				),
				array(
					'key'      => 'cladding',
					'type'     => 'select',
					'label'    => array( 'fr' => 'Bardage', 'ar' => 'الكسوة الجانبية' ),
					'required' => true,
					'options'  => array(
						self::opt( 'sandwich', 'Panneaux sandwich', 'ألواح ساندويتش' ),
						self::opt( 'ribbed', 'Bac acier / tôle nervurée', 'صفائح فولاذية مضلعة' ),
						self::opt( 'masonry', 'Maçonnerie', 'بناء بالطوب' ),
						self::opt( 'none', 'Sans bardage', 'بدون كسوة' ),
						self::opt( 'other', 'Autre', 'أخرى' ),
					),
				),
				array( 'key' => 'inc_dismantling', 'type' => 'bool', 'row' => false, 'group' => 'includes', 'label' => array( 'fr' => 'Démontage inclus', 'ar' => 'التفكيك مشمول' ) ),
				array( 'key' => 'inc_transport', 'type' => 'bool', 'row' => false, 'group' => 'includes', 'label' => array( 'fr' => 'Transport inclus', 'ar' => 'النقل مشمول' ) ),
				array( 'key' => 'inc_reinstall', 'type' => 'bool', 'row' => false, 'group' => 'includes', 'label' => array( 'fr' => 'Remontage inclus', 'ar' => 'إعادة التركيب مشمولة' ) ),
				array( 'key' => 'inc_foundations', 'type' => 'bool', 'row' => false, 'group' => 'includes', 'label' => array( 'fr' => 'Fondations et accessoires inclus (tiges d’ancrage, platines, boulonnerie)', 'ar' => 'الأساسات والملحقات مشمولة (براغي التثبيت، الصفائح، البراغي)' ) ),
			),
			'line_elements' => self::elements(),
		);
		/**
		 * Buildings market definition (labels, conditions, fields, element types).
		 *
		 * @param array $def Market definition in the SM Products shape.
		 */
		return apply_filters( 'sm_buildings_market', $def );
	}

	/** code => {fr, ar}, best first. */
	public static function conditions() {
		return array(
			'new'        => array( 'fr' => 'Neuf', 'ar' => 'جديد' ),
			'excellent'  => array( 'fr' => 'Occasion – excellent état', 'ar' => 'مستعمل – حالة ممتازة' ),
			'good'       => array( 'fr' => 'Occasion – bon état', 'ar' => 'مستعمل – حالة جيدة' ),
			'inspection' => array( 'fr' => 'À inspecter ou à réparer', 'ar' => 'يحتاج إلى فحص أو إصلاح' ),
		);
	}

	/** Element types of the structural inventory. */
	public static function elements() {
		return array(
			'column'  => array( 'fr' => 'Poteaux', 'ar' => 'أعمدة' ),
			'rafter'  => array( 'fr' => 'Traverses (arbalétriers)', 'ar' => 'عوارض السقف' ),
			'truss'   => array( 'fr' => 'Fermes', 'ar' => 'جملونات' ),
			'purlin'  => array( 'fr' => 'Pannes', 'ar' => 'مدادات السقف' ),
			'rail'    => array( 'fr' => 'Lisses de bardage', 'ar' => 'مدادات جانبية' ),
			'bracing' => array( 'fr' => 'Contreventements', 'ar' => 'تكتيف' ),
			'gable'   => array( 'fr' => 'Potelets de pignon', 'ar' => 'أعمدة الواجهة' ),
			'crane'   => array( 'fr' => 'Poutres de roulement', 'ar' => 'روافد الرافعة' ),
			'other'   => array( 'fr' => 'Autres éléments', 'ar' => 'عناصر أخرى' ),
		);
	}
}
