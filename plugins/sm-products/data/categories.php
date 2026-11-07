<?php
/**
 * Default product categories (imported once; then edited in wp-admin).
 *
 * kind:
 *   engine   technical data and weight from SM Weight Engine (families = engine family codes)
 *   specs    category-specific fields below; quantity in the chosen unit
 * units: price / quantity units offered (kg, unit, m2, ml).
 * fields (specs kind): key, type (text|number|select), unit, label {fr, ar}, required, options [{value, fr, ar}].
 *
 * @package SM\Products
 */

defined( 'ABSPATH' ) || exit;

return array(
	'profiles'  => array(
		'name'     => array( 'fr' => 'Profilés', 'ar' => 'المقاطع (البروفيلات)' ),
		'icon'     => 'layers',
		'kind'     => 'engine',
		'families' => array( 'IPE', 'HEA', 'HEB', 'HEM', 'IPN', 'UPN', 'UPE', 'L', 'LI' ),
		'units'    => array( 'kg', 'unit' ),
		'fields'   => array(),
	),
	'tubes'     => array(
		'name'     => array( 'fr' => 'Tubes', 'ar' => 'الأنابيب' ),
		'icon'     => 'layers',
		'kind'     => 'engine',
		'families' => array( 'SHS', 'RHS', 'CHS' ),
		'units'    => array( 'kg', 'unit' ),
		'fields'   => array(),
	),
	'plates'    => array(
		'name'     => array( 'fr' => 'Tôles', 'ar' => 'الصفائح' ),
		'icon'     => 'layers',
		'kind'     => 'engine',
		'families' => array( 'PLATE' ),
		'units'    => array( 'kg', 'unit' ),
		'fields'   => array(),
	),
	'bars'      => array(
		'name'     => array( 'fr' => 'Fers plats et barres', 'ar' => 'الحديد المسطح والقضبان' ),
		'icon'     => 'layers',
		'kind'     => 'engine',
		'families' => array( 'FLAT', 'ROUND', 'SQUARE' ),
		'units'    => array( 'kg', 'unit' ),
		'fields'   => array(),
	),
	'roofing'   => array(
		'name'   => array( 'fr' => 'Toiture et bardage', 'ar' => 'التسقيف والتكسية' ),
		'icon'   => 'warehouse',
		'kind'   => 'specs',
		'units'  => array( 'm2', 'ml', 'unit' ),
		'fields' => array(
			array(
				'key'      => 'roof_type',
				'type'     => 'select',
				'label'    => array( 'fr' => 'Type', 'ar' => 'النوع' ),
				'required' => true,
				'options'  => array(
					array( 'value' => 'sandwich_ps', 'fr' => 'Panneau sandwich (PS)', 'ar' => 'لوح ساندويتش (PS)' ),
					array( 'value' => 'ribbed', 'fr' => 'Tôle nervurée', 'ar' => 'صفيحة مضلعة' ),
					array( 'value' => 'other', 'fr' => 'Autre', 'ar' => 'أخرى' ),
				),
			),
			array( 'key' => 'maker_type', 'type' => 'text', 'label' => array( 'fr' => 'Nom du type (fabricant)', 'ar' => 'اسم النوع (المُصنّع)' ), 'required' => true ),
			array( 'key' => 'width_mm', 'type' => 'number', 'unit' => 'mm', 'label' => array( 'fr' => 'Largeur utile', 'ar' => 'العرض المفيد' ), 'required' => true ),
			array( 'key' => 'length_min_m', 'type' => 'number', 'unit' => 'm', 'label' => array( 'fr' => 'Longueur minimale', 'ar' => 'الطول الأدنى' ), 'required' => false ),
			array( 'key' => 'length_max_m', 'type' => 'number', 'unit' => 'm', 'label' => array( 'fr' => 'Longueur maximale', 'ar' => 'الطول الأقصى' ), 'required' => false ),
			array( 'key' => 'sheet_mm', 'type' => 'number', 'unit' => 'mm', 'label' => array( 'fr' => 'Épaisseur de la tôle', 'ar' => 'سُمك الصفيحة' ), 'required' => true ),
			array( 'key' => 'insulation_mm', 'type' => 'number', 'unit' => 'mm', 'label' => array( 'fr' => 'Épaisseur de l’isolant (PS)', 'ar' => 'سُمك العازل (PS)' ), 'required' => false ),
			array( 'key' => 'finish', 'type' => 'text', 'label' => array( 'fr' => 'Finition / couleur (RAL)', 'ar' => 'التشطيب / اللون (RAL)' ), 'required' => false ),
		),
	),
	'finishing' => array(
		'name'   => array( 'fr' => 'Pièces de finition', 'ar' => 'قطع التشطيب' ),
		'icon'   => 'wrench',
		'kind'   => 'specs',
		'units'  => array( 'unit', 'ml', 'kg' ),
		'fields' => array(
			array( 'key' => 'item', 'type' => 'text', 'label' => array( 'fr' => 'Pièce (faîtière, rive, gouttière…)', 'ar' => 'القطعة (قمة، حافة، مزراب…)' ), 'required' => true ),
			array( 'key' => 'dimensions', 'type' => 'text', 'label' => array( 'fr' => 'Dimensions', 'ar' => 'الأبعاد' ), 'required' => false ),
			array( 'key' => 'sheet_mm', 'type' => 'number', 'unit' => 'mm', 'label' => array( 'fr' => 'Épaisseur', 'ar' => 'السُّمك' ), 'required' => false ),
			array( 'key' => 'finish', 'type' => 'text', 'label' => array( 'fr' => 'Finition / couleur (RAL)', 'ar' => 'التشطيب / اللون (RAL)' ), 'required' => false ),
		),
	),
	'other'     => array(
		'name'   => array( 'fr' => 'Autres produits acier', 'ar' => 'منتجات فولاذية أخرى' ),
		'icon'   => 'package',
		'kind'   => 'specs',
		'units'  => array( 'unit', 'kg', 'ml', 'm2' ),
		'fields' => array(
			array( 'key' => 'item', 'type' => 'text', 'label' => array( 'fr' => 'Produit', 'ar' => 'المنتج' ), 'required' => true ),
			array( 'key' => 'dimensions', 'type' => 'text', 'label' => array( 'fr' => 'Dimensions', 'ar' => 'الأبعاد' ), 'required' => false ),
		),
	),
);
