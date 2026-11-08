<?php
/**
 * The two service catalogues. Codes are stored with each service; labels and
 * the order can be changed with the sm_services_catalogue filter.
 *
 * kind:
 *   fabrication  workshops and site teams (business accounts, permission service.fabrication)
 *   engineering  engineers and engineering offices (engineering accounts, permission service.engineering)
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
	'engineering' => array(
		'label'      => array( 'fr' => 'Ingénierie', 'ar' => 'الهندسة' ),
		'cap'        => 'service.engineering',
		'units'      => array( 'project', 'm2', 'day', 'hour' ),
		'categories' => array(
			'design'      => array( 'fr' => 'Conception de charpente métallique', 'ar' => 'تصميم الهياكل الفولاذية' ),
			'calculation' => array( 'fr' => 'Notes de calcul', 'ar' => 'مذكرات الحساب' ),
			'connections' => array( 'fr' => 'Calcul des assemblages', 'ar' => 'حساب الوصلات' ),
			'drawings'    => array( 'fr' => 'Plans techniques', 'ar' => 'المخططات التقنية' ),
			'shop'        => array( 'fr' => 'Plans d’atelier', 'ar' => 'مخططات الورشة' ),
			'fabrication' => array( 'fr' => 'Plans de fabrication', 'ar' => 'مخططات التصنيع' ),
			'inspection'  => array( 'fr' => 'Inspections techniques', 'ar' => 'المعاينات التقنية' ),
			'assessment'  => array( 'fr' => 'Expertise de structures d’occasion', 'ar' => 'خبرة الهياكل المستعملة' ),
			'consultancy' => array( 'fr' => 'Conseil en ingénierie', 'ar' => 'الاستشارات الهندسية' ),
		),
	),
);
