<?php
/**
 * Default reuse categories (imported once into the SM Products category table,
 * market "reuse"; then edited in wp-admin).
 *
 * kind:
 *   lines   inventory of the lot: one row per size (family / size / pieces / length);
 *           weights computed by SM Weight Engine from the European tables
 *   weight  total weight of the lot entered by the seller (estimated or weighed)
 * units: kg (price per kg), unit (per piece), lot (price for the whole lot).
 *
 * No steel reference data here: sizes come from SM Weight Engine.
 *
 * @package SM\Reuse
 */

defined( 'ABSPATH' ) || exit;

return array(
	'reuse-offcuts'    => array(
		'name'     => array( 'fr' => 'Chutes de profilés et barres', 'ar' => 'بقايا المقاطع والقضبان' ),
		'icon'     => 'layers',
		'kind'     => 'lines',
		'families' => array( 'IPE', 'HEA', 'HEB', 'HEM', 'IPN', 'UPN', 'UPE', 'L', 'LI', 'FLAT', 'ROUND', 'SQUARE' ),
		'units'    => array( 'kg', 'lot', 'unit' ),
	),
	'reuse-dismantled' => array(
		'name'     => array( 'fr' => 'Profilés de démontage', 'ar' => 'مقاطع من التفكيك' ),
		'icon'     => 'warehouse',
		'kind'     => 'lines',
		'families' => array( 'IPE', 'HEA', 'HEB', 'HEM', 'IPN', 'UPN', 'UPE', 'L', 'LI' ),
		'units'    => array( 'kg', 'lot', 'unit' ),
	),
	'reuse-tubes'      => array(
		'name'     => array( 'fr' => 'Tubes de réemploi', 'ar' => 'أنابيب لإعادة الاستعمال' ),
		'icon'     => 'layers',
		'kind'     => 'lines',
		'families' => array( 'SHS', 'RHS', 'CHS' ),
		'units'    => array( 'kg', 'lot', 'unit' ),
	),
	'reuse-plates'     => array(
		'name'   => array( 'fr' => 'Chutes de tôles', 'ar' => 'بقايا الصفائح' ),
		'icon'   => 'layers',
		'kind'   => 'weight',
		'units'  => array( 'kg', 'lot' ),
		'fields' => array(
			array( 'key' => 'thickness_mm', 'type' => 'number', 'unit' => 'mm', 'label' => array( 'fr' => 'Épaisseur', 'ar' => 'السُّمك' ), 'required' => true ),
			array( 'key' => 'piece_count', 'type' => 'number', 'label' => array( 'fr' => 'Nombre de morceaux (environ)', 'ar' => 'عدد القطع (تقريبًا)' ), 'required' => false ),
			array( 'key' => 'largest', 'type' => 'text', 'label' => array( 'fr' => 'Plus grand morceau (dimensions)', 'ar' => 'أكبر قطعة (الأبعاد)' ), 'required' => false ),
		),
	),
	'reuse-mixed'      => array(
		'name'   => array( 'fr' => 'Lots mixtes', 'ar' => 'دفعات مختلطة' ),
		'icon'   => 'package',
		'kind'   => 'weight',
		'units'  => array( 'kg', 'lot' ),
		'fields' => array(
			array( 'key' => 'item', 'type' => 'text', 'label' => array( 'fr' => 'Contenu du lot', 'ar' => 'محتوى الدفعة' ), 'required' => true ),
		),
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
);
