<?php
/**
 * Default building categories (imported once into the SM Products category
 * table, market "buildings"; then edited in wp-admin).
 *
 * kind "building" (handled by SM Buildings): assembled or dismantled, dimensions,
 * portals, structural element inventory weighed by SM Weight Engine.
 * families: profile families accepted in the inventory.
 * units: lot (asking price of the whole building), kg (price per kg, dismantled).
 *
 * @package SM\Buildings
 */

defined( 'ABSPATH' ) || exit;

$families = array( 'IPE', 'HEA', 'HEB', 'HEM', 'IPN', 'UPN', 'UPE', 'L', 'LI', 'SHS', 'RHS', 'CHS', 'FLAT', 'ROUND', 'SQUARE' );

return array(
	'bld-industrial'   => array(
		'name'     => array( 'fr' => 'Hangars et bâtiments industriels', 'ar' => 'هناجر ومبان صناعية' ),
		'icon'     => 'warehouse',
		'kind'     => 'building',
		'families' => $families,
		'units'    => array( 'lot', 'kg' ),
	),
	'bld-storage'      => array(
		'name'     => array( 'fr' => 'Entrepôts et stockage', 'ar' => 'مستودعات وتخزين' ),
		'icon'     => 'package',
		'kind'     => 'building',
		'families' => $families,
		'units'    => array( 'lot', 'kg' ),
	),
	'bld-workshop'     => array(
		'name'     => array( 'fr' => 'Ateliers', 'ar' => 'ورشات' ),
		'icon'     => 'wrench',
		'kind'     => 'building',
		'families' => $families,
		'units'    => array( 'lot', 'kg' ),
	),
	'bld-agricultural' => array(
		'name'     => array( 'fr' => 'Hangars agricoles', 'ar' => 'هناجر فلاحية' ),
		'icon'     => 'warehouse',
		'kind'     => 'building',
		'families' => $families,
		'units'    => array( 'lot', 'kg' ),
	),
	'bld-livestock'    => array(
		'name'     => array( 'fr' => 'Bâtiments d’élevage (volaille, bétail)', 'ar' => 'مباني تربية الدواجن والمواشي' ),
		'icon'     => 'home',
		'kind'     => 'building',
		'families' => $families,
		'units'    => array( 'lot', 'kg' ),
	),
	'bld-kit'          => array(
		'name'     => array( 'fr' => 'Kits et structures préfabriqués', 'ar' => 'هياكل جاهزة ومجموعات تركيب' ),
		'icon'     => 'layers',
		'kind'     => 'building',
		'families' => $families,
		'units'    => array( 'lot', 'kg' ),
	),
);
