<?php
/**
 * Section families: shape, names, standard and the source each table comes from.
 *
 * method: "table"   = sizes and dimensions from the reference table (data/sections.php);
 *         "formula" = any size inside the limits, mass from the standard's formula
 *                     (the listed sizes are only suggestions).
 *
 * @package SM\WeightEngine
 */

defined( 'ABSPATH' ) || exit;

$stad  = 'STAD, Technical data of sections %s (stad.fr, mise à jour du 13/10/2026)';
$check = 'Contrôle : dimensions comparées à fib structuralcodes 0.7.3 (Apache-2.0) ; aire recalculée à partir des dimensions, masse = aire × 7850 kg/m³.';

return array(
	'IPE' => array(
		'shape'    => 'i_parallel',
		'name'     => array( 'fr' => 'Poutrelles IPE', 'ar' => 'عوارض IPE' ),
		'group'    => 'beams',
		'standard' => 'EN 10365:2017',
		'source'   => sprintf( $stad, 'IPE' ) . '. ' . $check . ' Contre-vérification eurocodepy.',
		'url'      => 'https://www.stad.fr/downloads/pdf/stad-technical-data-of-sections-ipe.pdf',
		'method'   => 'table',
	),
	'HEA' => array(
		'shape'    => 'i_parallel',
		'name'     => array( 'fr' => 'Poutrelles HEA', 'ar' => 'عوارض HEA' ),
		'group'    => 'beams',
		'standard' => 'EN 10365:2017',
		'source'   => sprintf( $stad, 'HE' ) . '. ' . $check . ' Contre-vérification eurocodepy.',
		'url'      => 'https://www.stad.fr/downloads/pdf/stad-technical-data-of-sections-he.pdf',
		'method'   => 'table',
	),
	'HEB' => array(
		'shape'    => 'i_parallel',
		'name'     => array( 'fr' => 'Poutrelles HEB', 'ar' => 'عوارض HEB' ),
		'group'    => 'beams',
		'standard' => 'EN 10365:2017',
		'source'   => sprintf( $stad, 'HE' ) . '. ' . $check . ' Contre-vérification eurocodepy.',
		'url'      => 'https://www.stad.fr/downloads/pdf/stad-technical-data-of-sections-he.pdf',
		'method'   => 'table',
	),
	'HEM' => array(
		'shape'    => 'i_parallel',
		'name'     => array( 'fr' => 'Poutrelles HEM', 'ar' => 'عوارض HEM' ),
		'group'    => 'beams',
		'standard' => 'EN 10365:2017',
		'source'   => sprintf( $stad, 'HE' ) . '. ' . $check . ' Contre-vérification eurocodepy.',
		'url'      => 'https://www.stad.fr/downloads/pdf/stad-technical-data-of-sections-he.pdf',
		'method'   => 'table',
	),
	'IPN' => array(
		'shape'    => 'i_taper',
		'name'     => array( 'fr' => 'Poutrelles IPN', 'ar' => 'عوارض IPN' ),
		'group'    => 'beams',
		'standard' => 'EN 10365:2017',
		'source'   => 'ArcelorMittal, Poutrelles en I à ailes inclinées (catalogue Sections and Merchant Bars, p. 87). ' . $check . ' Pente des ailes 14 %.',
		'url'      => 'https://sections.arcelormittal.com/repository2/Sections/Sections_MB_ArcelorMittal_FR_EN_DE_IPN.pdf',
		'method'   => 'table',
	),
	'UPN' => array(
		'shape'    => 'u_taper',
		'name'     => array( 'fr' => 'Profilés en U (UPN)', 'ar' => 'مجاري UPN' ),
		'group'    => 'channels',
		'standard' => 'EN 10365:2017',
		'source'   => 'ArcelorMittal, table UPN de l’Orange Book (rayons r1 et r2, UPN 50 et 65) et ' . sprintf( $stad, 'UPN' ) . ' (UPN 80 à 400). ' . $check . ' Pente des ailes 8 % (h ≤ 300) ou 5 %.',
		'url'      => 'https://www.stad.fr/downloads/pdf/stad-technical-data-of-sections-upn.pdf',
		'method'   => 'table',
	),
	'UPE' => array(
		'shape'    => 'u_parallel',
		'name'     => array( 'fr' => 'Profilés en U à ailes parallèles (UPE)', 'ar' => 'مجاري UPE' ),
		'group'    => 'channels',
		'standard' => 'EN 10365:2017',
		'source'   => sprintf( $stad, 'UPE' ) . '. ' . $check,
		'url'      => 'https://www.stad.fr/downloads/pdf/stad-technical-data-of-sections-upe.pdf',
		'method'   => 'table',
	),
	'L'   => array(
		'shape'    => 'angle',
		'name'     => array( 'fr' => 'Cornières à ailes égales', 'ar' => 'زوايا متساوية الأضلاع' ),
		'group'    => 'angles',
		'standard' => 'EN 10056-1',
		'source'   => sprintf( $stad, 'L' ) . ' ; tailles et rayons (r1 congé, r2 arrondi d’aile) de la liste EN 10056-1 de structuralcodes. Tailles absentes de l’EN 10056-1 écartées. ' . $check,
		'url'      => 'https://www.stad.fr/downloads/pdf/stad-technical-data-of-sections-l.pdf',
		'method'   => 'table',
	),
	'LI'  => array(
		'shape'    => 'angle',
		'name'     => array( 'fr' => 'Cornières à ailes inégales', 'ar' => 'زوايا غير متساوية الأضلاع' ),
		'group'    => 'angles',
		'standard' => 'EN 10056-1',
		'source'   => sprintf( $stad, 'L' ) . ' ; tailles et rayons de la liste EN 10056-1 de structuralcodes. ' . $check,
		'url'      => 'https://www.stad.fr/downloads/pdf/stad-technical-data-of-sections-l.pdf',
		'method'   => 'table',
	),
	'SHS' => array(
		'shape'    => 'rhs',
		'name'     => array( 'fr' => 'Tubes carrés', 'ar' => 'أنابيب مربعة' ),
		'group'    => 'tubes',
		'standard' => 'EN 10219-2 (profils creux formés à froid)',
		'source'   => 'Formule de l’EN 10219-2 (rayons extérieurs 2t, 2,5t ou 3t selon l’épaisseur ; masse volumique 7850 kg/m³), vérifiée sur le catalogue Ferpinta EN 10219 (22 tailles contrôlées, écart nul). Tailles proposées : gamme Ferpinta.',
		'url'      => 'https://www.ferpinta.pt/en/products-en/tubes-en/structural-hollow-sections-en/',
		'method'   => 'formula',
	),
	'RHS' => array(
		'shape'    => 'rhs',
		'name'     => array( 'fr' => 'Tubes rectangulaires', 'ar' => 'أنابيب مستطيلة' ),
		'group'    => 'tubes',
		'standard' => 'EN 10219-2 (profils creux formés à froid)',
		'source'   => 'Formule de l’EN 10219-2, identique à celle des tubes carrés. Dimensions saisies par le vendeur.',
		'url'      => '',
		'method'   => 'formula',
	),
	'CHS' => array(
		'shape'    => 'chs',
		'name'     => array( 'fr' => 'Tubes ronds', 'ar' => 'أنابيب دائرية' ),
		'group'    => 'tubes',
		'standard' => 'EN 10219-2 (profils creux formés à froid)',
		'source'   => 'Aire π·t·(D − t), masse volumique 7850 kg/m³, vérifiée sur le catalogue Ferpinta EN 10219 (12 tailles contrôlées). Tailles proposées : gamme Ferpinta.',
		'url'      => 'https://www.ferpinta.pt/en/products-en/tubes-en/structural-hollow-sections-en/',
		'method'   => 'formula',
	),
	'FLAT' => array(
		'shape'    => 'flat',
		'name'     => array( 'fr' => 'Fers plats', 'ar' => 'حديد مسطح' ),
		'group'    => 'bars',
		'standard' => 'EN 10058',
		'source'   => 'Section rectangulaire largeur × épaisseur, masse volumique 7850 kg/m³.',
		'url'      => '',
		'method'   => 'formula',
	),
	'ROUND' => array(
		'shape'    => 'round',
		'name'     => array( 'fr' => 'Ronds pleins', 'ar' => 'قضبان دائرية مصمتة' ),
		'group'    => 'bars',
		'standard' => 'EN 10060',
		'source'   => 'Section π·d²/4, masse volumique 7850 kg/m³.',
		'url'      => '',
		'method'   => 'formula',
	),
	'SQUARE' => array(
		'shape'    => 'square',
		'name'     => array( 'fr' => 'Carrés pleins', 'ar' => 'قضبان مربعة مصمتة' ),
		'group'    => 'bars',
		'standard' => 'EN 10059',
		'source'   => 'Section a², masse volumique 7850 kg/m³.',
		'url'      => '',
		'method'   => 'formula',
	),
	'PLATE' => array(
		'shape'    => 'plate',
		'name'     => array( 'fr' => 'Tôles', 'ar' => 'صفائح فولاذية' ),
		'group'    => 'plates',
		'standard' => 'EN 10029 / EN 10051',
		'source'   => 'Épaisseur × largeur × longueur, masse volumique 7850 kg/m³ (7,85 kg par m² et par mm d’épaisseur).',
		'url'      => '',
		'method'   => 'formula',
	),
);
