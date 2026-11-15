<?php
/**
 * Kinds of plates (0.3.0): common thicknesses, weight per m² and formats.
 *
 * A plate is sold by the sheet in Algeria: the weight below is an estimate the
 * seller can replace by the weight of one sheet.
 *   density   kg/m³ used when the thickness is not in the table (or for every thickness)
 *   table     base thickness (mm) => [ label, kg/m² ] for plates with a relief (published tables)
 *   holes     true when the weight can deduct round holes (diameter and pitch)
 * Sources (kept for the administration and docs/VERIFICATION.md, never shown on the site):
 *   thicknesses: ArcelorMittal e-steel, steel sheet range (0,5 to 25 mm);
 *   tread plates (larmée/striée) and aluminium checker plates: Codip, weights of the 2000 × 1000 sheet;
 *   stainless density 7 900 kg/m³ (EN 10088-1, 1.4301); aluminium 2 700 kg/m³.
 *
 * @package SM\WeightEngine
 */

defined( 'ABSPATH' ) || exit;

$thin = array( 0.5, 0.6, 0.7, 0.8, 1, 1.2, 1.5, 2, 2.5, 3 );

return array(
	'formats' => array( array( 1000, 2000 ), array( 1250, 2500 ), array( 1500, 3000 ), array( 2000, 6000 ) ),
	'kinds'   => array(
		'hot'        => array(
			'name'        => array( 'fr' => 'Tôle noire', 'ar' => 'صفيحة سوداء' ),
			'hint'        => array( 'fr' => 'Laminée à chaud', 'ar' => 'مدرفلة على الساخن' ),
			'title'       => array( 'fr' => 'Tôle noire', 'ar' => 'صفيحة سوداء' ),
			'density'     => 7850,
			'thicknesses' => array( 2, 2.5, 3, 4, 5, 6, 8, 10, 12, 15, 20, 25 ),
		),
		'cold'       => array(
			'name'        => array( 'fr' => 'Laminée à froid', 'ar' => 'مدرفلة على البارد' ),
			'hint'        => array( 'fr' => 'Tôle fine (DC01…)', 'ar' => 'صفيحة رقيقة (DC01…)' ),
			'title'       => array( 'fr' => 'Tôle laminée à froid', 'ar' => 'صفيحة مدرفلة على البارد' ),
			'density'     => 7850,
			'thicknesses' => $thin,
		),
		'galvanized' => array(
			'name'        => array( 'fr' => 'Galvanisée', 'ar' => 'مجلفنة' ),
			'hint'        => array( 'fr' => 'Zinguée', 'ar' => 'مطلية بالزنك' ),
			'title'       => array( 'fr' => 'Tôle galvanisée', 'ar' => 'صفيحة مجلفنة' ),
			'density'     => 7850,
			'thicknesses' => $thin,
		),
		'tread'      => array(
			'name'        => array( 'fr' => 'Larmée / striée', 'ar' => 'مضلعة (لارمي)' ),
			'hint'        => array( 'fr' => 'Antidérapante', 'ar' => 'مانعة للانزلاق' ),
			'title'       => array( 'fr' => 'Tôle larmée', 'ar' => 'صفيحة مضلعة لارمي' ),
			'density'     => 7850,
			'relief'      => true,
			'table'       => array(
				'3'  => array( '3/5', 29.0 ),
				'4'  => array( '4/6', 36.25 ),
				'5'  => array( '5/7', 44.55 ),
				'6'  => array( '6/8', 49.0 ),
				'8'  => array( '8/10', 65.0 ),
				'10' => array( '10/12', 81.0 ),
			),
		),
		'perforated' => array(
			'name'        => array( 'fr' => 'Perforée', 'ar' => 'مثقبة' ),
			'hint'        => array( 'fr' => 'Trous ronds', 'ar' => 'ثقوب دائرية' ),
			'title'       => array( 'fr' => 'Tôle perforée', 'ar' => 'صفيحة مثقبة' ),
			'density'     => 7850,
			'holes'       => true,
			'thicknesses' => array( 1, 1.5, 2, 2.5, 3, 4, 5 ),
		),
		'stainless'  => array(
			'name'        => array( 'fr' => 'Inox', 'ar' => 'إينوكس' ),
			'hint'        => array( 'fr' => 'Acier inoxydable', 'ar' => 'فولاذ مقاوم للصدأ' ),
			'title'       => array( 'fr' => 'Tôle inox', 'ar' => 'صفيحة إينوكس' ),
			'density'     => 7900,
			'thicknesses' => array( 0.5, 0.6, 0.8, 1, 1.2, 1.5, 2, 2.5, 3, 4, 5, 6, 8, 10 ),
		),
		'alu_tread'  => array(
			'name'        => array( 'fr' => 'Alu à damier', 'ar' => 'ألومنيوم مضلع' ),
			'hint'        => array( 'fr' => 'Aluminium', 'ar' => 'ألومنيوم' ),
			'title'       => array( 'fr' => 'Tôle alu à damier', 'ar' => 'صفيحة ألومنيوم مضلعة' ),
			'density'     => 2700,
			'relief'      => true,
			'table'       => array(
				'2'   => array( '2/3,5', 6.6 ),
				'3'   => array( '3/4,5', 8.9 ),
				'4'   => array( '4/5,5', 11.6 ),
				'5'   => array( '5/6,5', 14.3 ),
				'5.5' => array( '5,5/7', 16.17 ),
			),
		),
		'other'      => array(
			'name'        => array( 'fr' => 'Autre tôle', 'ar' => 'صفيحة أخرى' ),
			'hint'        => array( 'fr' => 'Tout saisir', 'ar' => 'إدخال كل شيء' ),
			'title'       => array( 'fr' => 'Tôle', 'ar' => 'صفيحة' ),
			'density'     => 7850,
			'thicknesses' => array(),
		),
	),
);
