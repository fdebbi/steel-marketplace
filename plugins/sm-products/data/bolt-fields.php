<?php
/**
 * Fields of the bolts category (0.18.0), shared by the new market and by
 * sm-reuse. Standard metric sizes and property classes only (ISO 898-1,
 * ISO 261 coarse thread); no weight table: the seller gives the weight when he
 * sells by the kg. "chips": the choices are shown as buttons.
 *
 * @package SM\Products
 */

defined( 'ABSPATH' ) || exit;

$opt = static fn( $values ) => array_map( static fn( $v ) => array( 'value' => strtolower( str_replace( '.', '', $v ) ), 'fr' => $v, 'ar' => $v ), $values );

return array(
	array(
		'key'      => 'bolt_type',
		'type'     => 'select',
		'chips'    => true,
		'label'    => array( 'fr' => 'Type', 'ar' => 'النوع' ),
		'required' => true,
		'options'  => array(
			array( 'value' => 'bolt', 'fr' => 'Boulons (vis + écrou)', 'ar' => 'براغي (برغي + صامولة)' ),
			array( 'value' => 'rod', 'fr' => 'Tiges filetées', 'ar' => 'قضبان ملولبة' ),
			array( 'value' => 'nut', 'fr' => 'Écrous', 'ar' => 'صواميل' ),
			array( 'value' => 'washer', 'fr' => 'Rondelles', 'ar' => 'حلقات' ),
			array( 'value' => 'anchor', 'fr' => 'Tiges d’ancrage', 'ar' => 'قضبان التثبيت' ),
		),
	),
	array(
		'key'      => 'diameter',
		'type'     => 'select',
		'chips'    => true,
		'label'    => array( 'fr' => 'Diamètre', 'ar' => 'القطر' ),
		'required' => true,
		'options'  => $opt( array( 'M6', 'M8', 'M10', 'M12', 'M14', 'M16', 'M18', 'M20', 'M22', 'M24', 'M27', 'M30', 'M33', 'M36' ) ),
	),
	array( 'key' => 'length_mm', 'type' => 'number', 'unit' => 'mm', 'label' => array( 'fr' => 'Longueur', 'ar' => 'الطول' ), 'required' => false ),
	array(
		'key'      => 'class',
		'type'     => 'select',
		'chips'    => true,
		'label'    => array( 'fr' => 'Classe', 'ar' => 'الدرجة' ),
		'required' => false,
		'options'  => $opt( array( '4.6', '5.6', '6.8', '8.8', '10.9', '12.9' ) ),
	),
	array(
		'key'      => 'finish',
		'type'     => 'select',
		'chips'    => true,
		'label'    => array( 'fr' => 'Finition', 'ar' => 'التشطيب' ),
		'required' => false,
		'options'  => array(
			array( 'value' => 'black', 'fr' => 'Noir', 'ar' => 'أسود' ),
			array( 'value' => 'galvanized', 'fr' => 'Galvanisé à chaud', 'ar' => 'مجلفن على الساخن' ),
			array( 'value' => 'zinc', 'fr' => 'Zingué', 'ar' => 'مطلي بالزنك' ),
			array( 'value' => 'stainless', 'fr' => 'Inox', 'ar' => 'إينوكس' ),
		),
	),
	array(
		'key'      => 'preloaded',
		'type'     => 'select',
		'chips'    => true,
		'label'    => array( 'fr' => 'Précontraint (HR / HV)', 'ar' => 'مسبق الشد (HR / HV)' ),
		'required' => false,
		'options'  => array(
			array( 'value' => 'no', 'fr' => 'Non', 'ar' => 'لا' ),
			array( 'value' => 'yes', 'fr' => 'Oui', 'ar' => 'نعم' ),
		),
	),
);
