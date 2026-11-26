<?php
/**
 * Guides written at installation (texts validated by ReuSteel on 2026-11-26).
 * The title, the first paragraph (summary) and the body come from
 * data/guides/{slug}-{lang}.md. Never written over a guide that exists.
 *
 * @package SM\Guides
 */

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'slug'    => 'acheter-acier-reemploi',
		'order'   => 1,
		'tools'   => array( 'reuse', 'weight', 'engineers', 'catalogue' ),
		'topic'   => array( 'fr' => 'Réemploi', 'ar' => 'إعادة الاستعمال', 'en' => 'Reuse' ),
		'excerpt' => array(
			'fr' => "État déclaré, poids pesé ou calculé, photos, visite et avis d'un ingénieur.",
			'ar' => 'الحالة المصرّح بها، الوزن الموزون أو المحسوب، الصور، المعاينة ورأي المهندس.',
			'en' => "Declared condition, weighed or calculated weight, photos, visit and an engineer's opinion.",
		),
	),
	array(
		'slug'    => 'batiment-metallique-occasion',
		'order'   => 2,
		'tools'   => array( 'buildings', 'engineers', 'services' ),
		'topic'   => array( 'fr' => 'Bâtiments', 'ar' => 'المباني', 'en' => 'Buildings' ),
		'excerpt' => array(
			'fr' => 'Bâtiment monté ou démonté, inventaire, visite, démontage et remontage.',
			'ar' => 'مبنى مركّب أو مفكّك، الجرد، المعاينة، التفكيك وإعادة التركيب.',
			'en' => 'Assembled or dismantled building, inventory, visit, dismantling and re-erection.',
		),
	),
	array(
		'slug'    => 'reduire-chutes-barres-6-12-m',
		'order'   => 3,
		'tools'   => array( 'cutting', 'weight', 'rfq' ),
		'topic'   => array( 'fr' => 'Découpe', 'ar' => 'القص', 'en' => 'Cutting' ),
		'excerpt' => array(
			'fr' => "Un exemple calculé, l'optimiseur de découpe pas à pas, le DQE lu par l'IA.",
			'ar' => 'مثال محسوب، مُحسِّن القص خطوة بخطوة، DQE بالذكاء الاصطناعي.',
			'en' => 'A calculated example, the cutting optimiser step by step, the bill of quantities read by AI.',
		),
	),
	array(
		'slug'    => 'vendre-chutes-surplus-acier',
		'order'   => 4,
		'tools'   => array( 'reuse', 'weight' ),
		'topic'   => array( 'fr' => 'Vendre', 'ar' => 'البيع', 'en' => 'Selling' ),
		'excerpt' => array(
			'fr' => 'Bonne catégorie, description honnête, prix, règles et réponses aux acheteurs.',
			'ar' => 'الفئة الصحيحة، وصف صادق، السعر، القواعد والرد على المشترين.',
			'en' => 'The right category, an honest description, the price, the rules and replying to buyers.',
		),
	),
);
