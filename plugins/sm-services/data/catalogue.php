<?php
/**
 * The service catalogue. Codes are stored with each service; labels and the
 * order can be changed with the sm_services_catalogue filter.
 *
 * kind:
 *   fabrication  workshops, site teams and professionals (permission service.fabrication)
 *
 * Engineering is not a published service any more (0.2.0): studies and
 * inspections are requested from the platform, which subcontracts them to its
 * partner engineers (SM Engineering).
 * units: units of an indicative price ("from X DA per …").
 *
 * @package SM\Services
 */

defined( 'ABSPATH' ) || exit;

return array(
	'fabrication' => array(
		'label'      => array( 'fr' => 'Fabrication et chantier', 'ar' => 'التصنيع والورشات' ),
		'cap'        => 'service.fabrication',
		'units'      => array( 'project', 'kg', 't', 'm2', 'ml', 'piece', 'hour', 'day' ),
		'categories' => array(
			'cutting'      => array( 'fr' => 'Découpe', 'ar' => 'القص' ),
			'drilling'     => array( 'fr' => 'Perçage', 'ar' => 'الثقب' ),
			'welding'      => array( 'fr' => 'Soudage', 'ar' => 'اللحام' ),
			'structural'   => array( 'fr' => 'Fabrication de charpente', 'ar' => 'تصنيع الهياكل المعدنية' ),
			'beams'        => array( 'fr' => 'Fabrication de poutres (PRS)', 'ar' => 'تصنيع الروافد' ),
			'columns'      => array( 'fr' => 'Fabrication de poteaux', 'ar' => 'تصنيع الأعمدة' ),
			'assembly'     => array( 'fr' => 'Assemblage', 'ar' => 'التجميع' ),
			'sandblasting' => array( 'fr' => 'Sablage', 'ar' => 'السفع بالرمل' ),
			'painting'     => array( 'fr' => 'Peinture', 'ar' => 'الطلاء' ),
			'galvanizing'  => array( 'fr' => 'Galvanisation', 'ar' => 'الغلفنة' ),
			'installation' => array( 'fr' => 'Montage', 'ar' => 'التركيب' ),
			'dismantling'  => array( 'fr' => 'Démontage', 'ar' => 'التفكيك' ),
			'transport'    => array( 'fr' => 'Coordination du transport', 'ar' => 'تنسيق النقل' ),
		),
	),
);
