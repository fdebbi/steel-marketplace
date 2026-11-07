<?php
/**
 * The "reuse" market, added to the SM Products listing engine.
 *
 * The five condition classes come from the brief. Origin and surface state are
 * declared by the seller; they describe the lot, they do not certify it.
 *
 * @package SM\Reuse
 */

namespace SM\Reuse;

defined( 'ABSPATH' ) || exit;

final class Market {

	const CODE = 'reuse';

	public static function init() {
		add_filter( 'sm_products_markets', array( __CLASS__, 'register' ) );
	}

	public static function register( array $markets ) {
		$markets[ self::CODE ] = self::definition();
		return $markets;
	}

	public static function definition() {
		$def = array(
			'label'      => array( 'fr' => 'Réemploi acier', 'ar' => 'إعادة استعمال الفولاذ' ),
			'intro'      => array(
				'fr' => 'Chutes, surplus de chantier, profilés de démontage et ferraille : décrivez le lot taille par taille.',
				'ar' => 'بقايا، فائض الورشات، مقاطع من التفكيك وخردة: صِف الدفعة مقاسًا بمقاس.',
			),
			'icon'       => 'recycle',
			'cap'        => 'listing.reuse',
			'conditions' => self::conditions(),
			'fields'     => array(
				array(
					'key'      => 'origin',
					'type'     => 'select',
					'label'    => array( 'fr' => 'Origine', 'ar' => 'المصدر' ),
					'required' => true,
					'options'  => array(
						array( 'value' => 'site_surplus', 'fr' => 'Surplus de chantier', 'ar' => 'فائض ورشة بناء' ),
						array( 'value' => 'workshop', 'fr' => 'Chutes d’atelier', 'ar' => 'بقايا الورشة' ),
						array( 'value' => 'dismantling', 'fr' => 'Démontage d’ouvrage', 'ar' => 'تفكيك منشأة' ),
						array( 'value' => 'end_of_stock', 'fr' => 'Fin de stock', 'ar' => 'نهاية المخزون' ),
						array( 'value' => 'other', 'fr' => 'Autre', 'ar' => 'أخرى' ),
					),
				),
				array(
					'key'      => 'surface',
					'type'     => 'select',
					'label'    => array( 'fr' => 'État de surface', 'ar' => 'حالة السطح' ),
					'required' => false,
					'options'  => array(
						array( 'value' => 'clean', 'fr' => 'Propre', 'ar' => 'نظيف' ),
						array( 'value' => 'light_rust', 'fr' => 'Rouille superficielle', 'ar' => 'صدأ سطحي' ),
						array( 'value' => 'painted', 'fr' => 'Peint', 'ar' => 'مدهون' ),
						array( 'value' => 'galvanized', 'fr' => 'Galvanisé', 'ar' => 'مُغلفن' ),
						array( 'value' => 'heavy_corrosion', 'fr' => 'Corrosion importante', 'ar' => 'تآكل كبير' ),
					),
				),
				array(
					'key'   => 'cut',
					'type'  => 'bool',
					'label' => array( 'fr' => 'Découpe à la demande', 'ar' => 'القص حسب الطلب' ),
				),
				array(
					'key'   => 'loading',
					'type'  => 'bool',
					'label' => array( 'fr' => 'Chargement assuré sur place', 'ar' => 'التحميل مضمون في الموقع' ),
				),
			),
		);
		/**
		 * Reuse market definition (labels, conditions, extra fields).
		 *
		 * @param array $def Market definition in the SM Products shape.
		 */
		return apply_filters( 'sm_reuse_market', $def );
	}

	/** code => {fr, ar}, best first. */
	public static function conditions() {
		return array(
			'new_surplus' => array( 'fr' => 'Surplus neuf non utilisé', 'ar' => 'فائض جديد غير مستعمل' ),
			'excellent'   => array( 'fr' => 'Occasion – excellent état', 'ar' => 'مستعمل – حالة ممتازة' ),
			'good'        => array( 'fr' => 'Occasion – bon état', 'ar' => 'مستعمل – حالة جيدة' ),
			'inspection'  => array( 'fr' => 'À inspecter ou à traiter', 'ar' => 'يحتاج إلى فحص أو معالجة' ),
			'scrap'       => array( 'fr' => 'Ferraille / recyclage uniquement', 'ar' => 'خردة / للرسكلة فقط' ),
		);
	}
}
