<?php
/**
 * Tests of sm-reuse. Run on a TEST site only (creates then removes data):
 *   wp eval-file wp-content/plugins/sm-reuse/tests/run.php
 *
 * Expected weights are read from SM Weight Engine (never typed here), so the
 * tests check the arithmetic of the lot, not the reference tables.
 *
 * @package SM\Reuse
 */

use SM\Core\Accounts\Companies;
use SM\Core\Listings\Index;
use SM\Products\Categories;
use SM\Products\Markets;
use SM\Products\Products;
use SM\Products\Spec;
use SM\Products\Theme\Bridge;
use SM\Reuse\Advice;

defined( 'ABSPATH' ) || exit;

$GLOBALS['smr_t'] = array( 'pass' => 0, 'fail' => 0, 'fails' => array() );
function smr_assert( $cond, $label ) {
	$t = &$GLOBALS['smr_t'];
	if ( $cond ) {
		++$t['pass'];
		echo "  ✓ $label\n";
	} else {
		++$t['fail'];
		$t['fails'][] = $label;
		echo "  ✗ $label\n";
	}
}
function smr_near( $a, $b, $tol = 0.01 ) {
	return abs( (float) $a - (float) $b ) <= $tol;
}
function smr_user( $suffix, $class, array $activities ) {
	$uid = wp_insert_user( array( 'user_login' => 'smrtest_' . $suffix, 'user_email' => "smrtest-{$suffix}@example.test", 'user_pass' => wp_generate_password(), 'role' => 'sm_member' ) );
	$cid = Companies::create( array( 'account_class' => $class, 'name' => "SMRTEST {$suffix}", 'wilaya' => '28' ), $uid, $activities );
	Companies::raise_level( $cid, 1 );
	return array( $uid, $cid );
}
function smr_cleanup() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sm_companies WHERE name LIKE 'SMRTEST %'" ) as $cid ) {
		foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}sm_products WHERE company_id = %d", $cid ) ) as $pid ) {
			Products::delete( $pid );
		}
		foreach ( array( 'company_members', 'company_activities' ) as $t ) {
			$wpdb->delete( $wpdb->prefix . 'sm_' . $t, array( 'company_id' => $cid ) );
		}
		$wpdb->delete( $wpdb->prefix . 'sm_companies', array( 'id' => $cid ) );
	}
	foreach ( get_users( array( 'search' => 'smrtest-*', 'search_columns' => array( 'user_email' ) ) ) as $u ) {
		$wpdb->delete( $wpdb->prefix . 'sm_notifications', array( 'user_id' => $u->ID ) );
		wp_delete_user( $u->ID );
	}
}
smr_cleanup();
add_filter( 'sm_theme_demo_enabled', '__return_false', 99 );
$suffix = substr( md5( (string) microtime( true ) ), 0, 6 );
list( $u_sel, $c_sel ) = smr_user( 's' . $suffix, 'commercial', array( 'fabrication' ) );
list( $u_eng, $c_eng ) = smr_user( 'e' . $suffix, 'engineering', array( 'engineer' ) );
$engine = \SM\Core\Modules::service( 'weight' );
$kgm    = static function ( $family, $section ) use ( $engine ) {
	$r = $engine->calculate( array( 'family' => $family, 'section' => $section, 'bars' => 1, 'length' => 1000 ) );
	return is_wp_error( $r ) ? 0 : (float) $r['total_kg'];
};
$base = array(
	'wilaya'    => '28',
	'title'     => array( 'fr' => 'Lot test réemploi' ),
	'price'     => '120',
	'condition' => 'good',
	'attrs'     => array( 'origin' => 'dismantling', 'surface' => 'light_rust', 'cut' => '1', 'loading' => '0' ),
);

echo "\n1. Market and categories\n";
$m = Markets::get( 'reuse' );
smr_assert( $m && 'listing.reuse' === $m['cap'] && 5 === count( $m['conditions'] ), 'the reuse market is registered with the five condition classes of the brief' );
smr_assert( array( 'new_surplus', 'excellent', 'good', 'inspection', 'scrap' ) === array_keys( $m['conditions'] ), 'conditions ordered from best to scrap' );
smr_assert( array( 'origin', 'surface', 'cut', 'loading' ) === wp_list_pluck( $m['fields'], 'key' ), 'market fields: origin, surface state, cutting, loading' );
$cats = Categories::all( false, 'reuse' );
smr_assert( 6 === count( $cats ) && 'lines' === $cats['reuse-offcuts']->kind && 'weight' === $cats['reuse-scrap']->kind, '6 reuse categories (3 inventories by size, 3 by weight)' );
smr_assert( array_key_exists( 'reuse-scrap', Categories::labels( 'reuse' ) ) && ! array_key_exists( 'reuse-scrap', Categories::labels( 'products' ) ), 'reuse categories kept out of the new products market' );

echo "\n2. Inventory lots (lines)\n";
$in = $base + array(
	'category'   => 'reuse-offcuts',
	'price_unit' => 'kg',
	'lines'      => array(
		array( 'family' => 'IPE', 'section' => 'IPE 200', 'qty' => '6', 'length' => '2,5' ),
		array( 'family' => 'HEA', 'section' => 'HEA 160', 'qty' => '3', 'length' => '4' ),
		array( 'family' => 'IPE', 'section' => '', 'qty' => '', 'length' => '' ),
	),
);
$s        = Spec::build( $in );
$expected = 6 * 2.5 * $kgm( 'IPE', 'IPE 200' ) + 3 * 4 * $kgm( 'HEA', 'HEA 160' );
smr_assert( ! $s['errors'] && 2 === count( $s['data']['lines'] ), 'two filled rows kept, the empty row ignored' );
smr_assert( $expected > 0 && smr_near( $s['data']['total_weight'], $expected, 0.01 ), 'lot weight = sum of the rows computed by the weight engine' );
smr_assert( smr_near( $s['data']['total_price'], $s['data']['total_weight'] * 120, 0.01 ) && 9 === (int) $s['data']['pieces'], 'price per kg × lot weight; 9 pieces' );
smr_assert( 'IPE 200 +1' === $s['data']['designation'] && 4000 === (int) $s['data']['length_mm'], 'designation "IPE 200 +1"; longest piece stored for the length filter' );
smr_assert( 'good' === $s['data']['item_condition'] && 'dismantling' === $s['data']['attrs']['origin'] && 1 === $s['data']['attrs']['cut'] && 0 === $s['data']['attrs']['loading'], 'condition and market fields stored' );
$lot = Spec::build( array_merge( $in, array( 'price' => '250000', 'price_unit' => 'lot' ) ) );
smr_assert( ! $lot['errors'] && smr_near( $lot['data']['total_price'], 250000 ) && smr_near( $lot['data']['price_per_kg'], 250000 / $lot['data']['total_weight'], 0.01 ), 'price for the whole lot, with its per-kg equivalent' );
$bad = Spec::build( array_merge( $in, array( 'lines' => array( array( 'family' => 'SHS', 'section' => 'SHS 100x100x5', 'qty' => '2', 'length' => '3' ), array( 'family' => 'IPE', 'section' => 'IPE 200', 'qty' => '0', 'length' => '3' ) ) ) ) );
smr_assert( isset( $bad['errors']['lines_0'], $bad['errors']['lines_1'] ), 'a family outside the category and a zero quantity are refused row by row' );
$none = Spec::build( array_merge( $in, array( 'lines' => array() ) ) );
smr_assert( isset( $none['errors']['lines'] ), 'a lot without any row is refused' );
$act = Spec::build( array_merge( $in, array( 'weight_source' => 'actual', 'actual_weight' => (string) round( $expected * 1.04 ) ) ) );
smr_assert( 'actual' === $act['data']['weight_source'] && smr_near( $act['data']['total_weight'], round( $expected * 1.04 ), 0.5 ), 'weighed weight replaces the computed one' );

echo "\n3. Lots by weight and rules\n";
$base['attrs'] = array( 'origin' => 'workshop', 'surface' => 'clean' );
$pl = Spec::build( $base + array( 'category' => 'reuse-plates', 'specs' => array( 'thickness_mm' => '8' ), 'total_kg' => '1200', 'price_unit' => 'kg' ) );
smr_assert( ! $pl['errors'] && smr_near( $pl['data']['total_weight'], 1200 ) && 'estimated' === $pl['data']['weight_source'] && smr_near( $pl['data']['total_price'], 144000 ), 'plate offcuts: 1 200 kg (estimated) × 120 DA/kg' );
$pl2 = Spec::build( $base + array( 'category' => 'reuse-plates', 'specs' => array(), 'total_kg' => '0' ) );
smr_assert( isset( $pl2['errors']['specs_thickness_mm'], $pl2['errors']['total_kg'] ), 'plate offcuts need a thickness and a weight' );
smr_assert( isset( $pl['errors']['attrs_cut'] ) === false && isset( Spec::build( array_merge( $base, array( 'category' => 'reuse-plates', 'specs' => array( 'thickness_mm' => '8' ), 'total_kg' => '1200', 'attrs' => array( 'origin' => 'workshop', 'cut' => '1' ) ) ) )['errors']['attrs_cut'] ), 'cutting on demand only for bars and profiles' );
$scrap_ok = Spec::build( array_merge( $base, array( 'category' => 'reuse-scrap', 'condition' => 'scrap', 'attrs' => array( 'origin' => 'dismantling' ), 'specs' => array( 'scrap_type' => 'heavy' ), 'total_kg' => '8000', 'price' => '45' ) ) );
smr_assert( ! $scrap_ok['errors'], 'scrap with the "scrap" condition is accepted' );
$scrap_bad = Spec::build( array_merge( $base, array( 'category' => 'reuse-scrap', 'condition' => 'good', 'attrs' => array( 'origin' => 'dismantling' ), 'specs' => array( 'scrap_type' => 'heavy' ), 'total_kg' => '8000' ) ) );
smr_assert( isset( $scrap_bad['errors']['condition'] ), 'scrap cannot be presented as reusable' );
$scrap_cat = Spec::build( array_merge( $in, array( 'condition' => 'scrap' ) ) );
smr_assert( isset( $scrap_cat['errors']['condition'] ), 'steel only good for recycling must go in the Scrap category' );
$nocond = Spec::build( array_merge( $in, array( 'condition' => '', 'attrs' => array() ) ) );
smr_assert( isset( $nocond['errors']['condition'], $nocond['errors']['attrs_origin'] ), 'condition and origin are required' );
$draft = Spec::build( array_merge( $in, array( 'condition' => '' ) ), false );
smr_assert( ! isset( $draft['errors']['condition'] ), 'live preview does not nag about the condition' );

echo "\n4. Saving, search and product page\n";
$pid = Products::save( 0, $c_sel, $u_sel, $s, 'published' );
$p   = Products::get( $pid );
smr_assert( 'reuse' === $p->market && 'reuse' === Index::get( $p->listing_id )->module && 2 === count( $p->lines ), 'saved in the reuse market, indexed under "reuse", inventory kept' );
$find = static fn( $args ) => in_array( $p->listing_id, wp_list_pluck( (array) ( Bridge::listings( null, $args + array( 'per_page' => 60 ) )['items'] ?? array() ), 'id' ), true );
smr_assert( $find( array( 'market' => 'reuse' ) ) && ! $find( array( 'market' => 'products' ) ), 'listed in reuse, not in new products' );
smr_assert( $find( array( 'market' => 'reuse', 'condition' => 'good' ) ) && ! $find( array( 'market' => 'reuse', 'condition' => 'excellent' ) ), 'condition filter' );
smr_assert( $find( array( 'market' => 'reuse', 'length_min' => '3.5' ) ) && ! $find( array( 'market' => 'reuse', 'length_min' => '5' ) ), 'minimum length filter (longest piece)' );
smr_assert( $find( array( 'market' => 'reuse', 'qty_min' => '9' ) ) && ! $find( array( 'market' => 'reuse', 'qty_min' => '10' ) ), 'minimum number of pieces filter' );
smr_assert( $find( array( 'market' => 'reuse', 'q' => 'HEA160' ) ), 'found by a size of the inventory that is not the first one (HEA160)' );
smr_assert( $find( array( 'market' => array( 'products', 'reuse' ) ) ), 'found in a search across products and reuse' );
wp_set_current_user( $u_eng );
$d = Bridge::listing( null, array( 'id' => $p->listing_id ) );
smr_assert( 2 === count( $d['elements'] ) && 'IPE 200' === $d['elements'][0]['designation'] && 6 === $d['elements'][0]['qty'] && smr_near( $d['elements'][0]['length'], 2.5 ), 'product page: inventory table (type, size, pieces, length, weights)' );
$labels = wp_list_pluck( $d['spec_rows'], 0 );
smr_assert( in_array( 'Condition', $labels, true ) || in_array( 'État', $labels, true ) || in_array( 'الحالة', $labels, true ), 'product page: condition row' );
smr_assert( 'good' === $d['condition'] && ! empty( $d['advice']['text'] ) && 'engineers' === $d['advice']['link_page'], 'product page: engineer advice with a link to the engineers' );
smr_assert( 'warning' === Advice::for_condition( 'inspection' )['level'] && empty( Advice::for_condition( 'scrap' )['link_page'] ), 'stronger advice for "to inspect"; recycling note for scrap' );
smr_assert( '' === $d['drawing'], 'no single drawing for a lot of several sizes' );
$form = null;
wp_set_current_user( $u_sel );
$form = Bridge::product_form( null, array( 'id' => $pid ) );
smr_assert( 'reuse' === $form['values']['market'] && 'good' === $form['values']['condition'] && 2 === count( $form['values']['lines'] ) && '2.5' === $form['values']['lines'][0]['length'] && '1' === $form['values']['attrs']['cut'], 'edit form gets back the market, condition, inventory rows and market fields' );
$mk = Bridge::product_markets( null );
smr_assert( $mk['reuse']['allowed'] && $mk['products']['allowed'] === false && 6 === $mk['reuse']['count'], 'a fabrication company may publish reuse lots but not new products' );

echo "\n5. Rules\n";
wp_set_current_user( $u_eng );
smr_assert( ! sm_can( 'listing.reuse' ), 'an engineering account can never publish reuse lots' );
$mk = Bridge::product_markets( null );
smr_assert( ! $mk['reuse']['allowed'] && '' !== $mk['reuse']['reason'], '…and is told why' );
wp_set_current_user( 0 );

smr_cleanup();
remove_filter( 'sm_theme_demo_enabled', '__return_false', 99 );
$t = $GLOBALS['smr_t'];
printf( "\n%d passed, %d failed\n", $t['pass'], $t['fail'] );
if ( $t['fail'] ) {
	echo "FAILED:\n  - " . implode( "\n  - ", $t['fails'] ) . "\n";
	exit( 1 );
}
