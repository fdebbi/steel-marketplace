<?php
/**
 * Default reuse categories (imported into the SM Products category table,
 * market "reuse"; then edited in wp-admin).
 *
 * 0.3.0: the same categories as new steel. One size per listing, in several
 * lengths (or plate sizes) with their number ("piece_rows" of the market), or by
 * the total weight. Offcuts and dismantled sections are told apart by the origin.
 * Lots of several products are made in "My listings" (kind "group").
 * Before 0.3.0: reuse-offcuts, reuse-dismantled, reuse-tubes, reuse-plates and
 * reuse-mixed (switched off by the upgrade; their listings stay online).
 *
 * kind:
 *   engine  one size from SM Weight Engine (families = engine family codes)
 *   specs   category fields (bolts)
 *   weight  total weight of the lot entered by the seller (scrap)
 *   group   lot made of several listings of the same seller
 *
 * @package SM\Reuse
 */

defined( 'ABSPATH' ) || exit;

return array(
	'reuse-profiles' => array(
		'name'     => array( 'fr' => 'Profilés', 'ar' => 'المقاطع (البروفيلات)' ),
		'icon'     => 'layers',
		'kind'     => 'engine',
		'families' => array( 'IPE', 'HEA', 'HEB', 'HEM', 'IPN', 'UPN', 'UPE', 'L', 'LI' ),
		'units'    => array( 'kg', 'lot' ),
	),
	'reuse-hollow'   => array(
		'name'     => array( 'fr' => 'Tubes', 'ar' => 'الأنابيب' ),
		'icon'     => 'layers',
		'kind'     => 'engine',
		'families' => array( 'SHS', 'RHS', 'CHS' ),
		'units'    => array( 'kg', 'lot' ),
	),
	'reuse-sheets'   => array(
		'name'     => array( 'fr' => 'Tôles', 'ar' => 'الصفائح' ),
		'icon'     => 'layers',
		'kind'     => 'engine',
		'families' => array( 'PLATE' ),
		'units'    => array( 'kg', 'lot' ),
	),
	'reuse-bars'     => array(
		'name'     => array( 'fr' => 'Fers plats et barres', 'ar' => 'الحديد المسطح والقضبان' ),
		'icon'     => 'layers',
		'kind'     => 'engine',
		'families' => array( 'FLAT', 'ROUND', 'SQUARE' ),
		'units'    => array( 'kg', 'lot' ),
	),
	'reuse-bolts'    => array(
		'name'   => array( 'fr' => 'Boulonnerie', 'ar' => 'البراغي والصواميل' ),
		'icon'   => 'wrench',
		'kind'   => 'specs',
		'units'  => array( 'unit', 'kg' ),
		'fields' => require SM_PRODUCTS_DIR . '/data/bolt-fields.php',
	),
	'reuse-scrap'      => array(
		'name'   => array( 'fr' => 'Ferraille', 'ar' => 'الخردة' ),
		'icon'   => 'recycle',
		'kind'   => 'weight',
		'units'  => array( 'kg', 'lot' ),
		'fields' => array(
			array(
				'key'      => 'scrap_type',
				'type'     => 'select',
				'label'    => array( 'fr' => 'Type de ferraille', 'ar' => 'نوع الخردة' ),
				'required' => true,
				'options'  => array(
					array( 'value' => 'heavy', 'fr' => 'Lourde (profilés, tôles épaisses)', 'ar' => 'ثقيلة (مقاطع، صفائح سميكة)' ),
					array( 'value' => 'light', 'fr' => 'Légère (tôles minces, bardage)', 'ar' => 'خفيفة (صفائح رقيقة، تكسية)' ),
					array( 'value' => 'mixed', 'fr' => 'Mélangée', 'ar' => 'مختلطة' ),
				),
			),
		),
	),
	'reuse-lot'      => array(
		'name'  => array( 'fr' => 'Lots groupés', 'ar' => 'دفعات مجمّعة' ),
		'icon'  => 'package',
		'kind'  => 'group',
		'units' => array( 'lot' ),
	),
);
