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
list( $u_sel, $c_sel ) = smr_user( 's' . $suffix, 'company', array( 'manufacturer' ) );
list( $u_eng, $c_eng ) = smr_user( 'e' . $suffix, 'engineer', array( 'engineer' ) );
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
smr_assert( array( 'origin', 'surface', 'loading', 'transport', 'cut' ) === wp_list_pluck( $m['fields'], 'key' ) && ! empty( $m['piece_rows'] ) && 2 === $m['min_photos'] && 'after' === $m['order'], '0.3.0: origin, surface, then what the price includes (loading, transport, cutting); lengths table; 2 photos' );
$cats = Categories::all( false, 'reuse' );
smr_assert( array( 'reuse-profiles', 'reuse-hollow', 'reuse-sheets', 'reuse-bars', 'reuse-bolts', 'reuse-scrap', 'reuse-lot' ) === array_keys( $cats ) && 'engine' === $cats['reuse-profiles']->kind && 'group' === $cats['reuse-lot']->kind, '0.3.0: the categories of new steel, bolts, scrap and lots' );
smr_assert( ! Categories::get( 'reuse-offcuts' )->active && ! Categories::get( 'reuse-mixed' )->active, 'the former categories are no longer offered' );
smr_assert( 6 === count( Bridge::product_categories( null, array( 'market' => 'reuse' ) ) ), 'lots are not published from the form (6 categories offered)' );
smr_assert( array_key_exists( 'reuse-scrap', Categories::labels( 'reuse' ) ) && ! array_key_exists( 'reuse-scrap', Categories::labels( 'products' ) ), 'reuse categories kept out of the new products market' );

echo "\n2. One size in several lengths\n";
$in = $base + array(
	'category'   => 'reuse-profiles',
	'family'     => 'IPE',
	'section'    => 'IPE 200',
	'price_unit' => 'kg',
	'rows'       => array(
		array( 'length' => '2,5', 'qty' => '6' ),
		array( 'length' => '4', 'qty' => '3' ),
		array( 'length' => '', 'qty' => '' ),
	),
);
$s        = Spec::build( $in );
$expected = 6 * 2.5 * $kgm( 'IPE', 'IPE 200' ) + 3 * 4 * $kgm( 'IPE', 'IPE 200' );
smr_assert( ! $s['errors'] && 2 === count( $s['data']['lines'] ), 'two filled rows kept, the empty row ignored' );
smr_assert( $expected > 0 && smr_near( $s['data']['total_weight'], $expected, 0.01 ), 'weight = sum of the rows computed by the weight engine' );
smr_assert( smr_near( $s['data']['total_price'], $s['data']['total_weight'] * 120, 0.01 ) && 9 === (int) $s['data']['pieces'], 'price per kg × weight; 9 bars' );
smr_assert( 'IPE 200' === $s['data']['designation'] && 4000 === (int) $s['data']['length_mm'] && 'IPE 200 · 9 barres' === $s['title']['fr'], 'designation IPE 200, longest bar stored, title "IPE 200 · 9 barres"' );
smr_assert( 'good' === $s['data']['item_condition'] && 'dismantling' === $s['data']['attrs']['origin'] && 1 === $s['data']['attrs']['cut'] && 0 === $s['data']['attrs']['loading'], 'condition and market fields stored' );
$lot = Spec::build( array_merge( $in, array( 'sale_mode' => 'lot', 'lot_price' => '250000' ) ) );
smr_assert( ! $lot['errors'] && smr_near( $lot['data']['total_price'], 250000 ) && smr_near( $lot['data']['price_per_kg'], 250000 / $lot['data']['total_weight'], 0.01 ), 'price for the whole lot, with its per-kg equivalent' );
$bad = Spec::build( array_merge( $in, array( 'rows' => array( array( 'length' => '3', 'qty' => '0' ), array( 'length' => '', 'qty' => '2' ) ) ) ) );
smr_assert( isset( $bad['errors']['rows_0'], $bad['errors']['rows_1'] ), 'a zero number and a missing length are refused row by row' );
$none = Spec::build( array_merge( $in, array( 'rows' => array() ) ) );
smr_assert( isset( $none['errors']['rows'] ), 'a listing without any length is refused' );
$act = Spec::build( array_merge( $in, array( 'weight_source' => 'actual', 'actual_weight' => (string) round( $expected * 1.04 ) ) ) );
smr_assert( 'actual' === $act['data']['weight_source'] && smr_near( $act['data']['total_weight'], round( $expected * 1.04 ), 0.5 ), 'weighed weight replaces the computed one' );
$loose = Spec::build( array_merge( $in, array( 'qty_mode' => 'weight', 'total_kg' => '800', 'rows' => array() ) ) );
smr_assert( ! $loose['errors'] && smr_near( $loose['data']['total_weight'], 800 ) && 0 === (int) $loose['data']['pieces'] && empty( $loose['data']['lines'] ), 'loose pieces by the total weight: no count, no length' );
$pl = Spec::build( $base + array( 'category' => 'reuse-sheets', 'plate_kind' => 'hot', 'plate_t' => '10', 'rows' => array( array( 'length' => '3000', 'width' => '1500', 'qty' => '2' ), array( 'length' => '1200', 'width' => '800', 'qty' => '5' ) ), 'price_unit' => 'kg' ) );
smr_assert( ! $pl['errors'] && smr_near( $pl['data']['total_weight'], 1083.3, 0.05 ) && 7 === (int) $pl['data']['pieces'] && 1500 === $pl['data']['lines'][0]['width_mm'], 'plates 10 mm in two sizes: 706,5 + 376,8 = 1 083,3 kg, 7 pieces' );
smr_assert( 0 === strpos( $pl['title']['fr'], 'Tôle noire 10 mm · 7 morceaux' ), 'plate title: ' . $pl['title']['fr'] );

echo "\n3. Bolts, scrap and rules\n";
$bo = Spec::build( array_merge( $base, array( 'category' => 'reuse-bolts', 'specs' => array( 'bolt_type' => 'bolt', 'diameter' => 'm16', 'length_mm' => '60', 'class' => '109', 'finish' => 'galvanized', 'preloaded' => 'yes' ), 'quantity' => '400', 'unit' => 'unit', 'price_unit' => 'unit', 'attrs' => array( 'origin' => 'dismantling' ) ) ) );
smr_assert( ! $bo['errors'] && 'M16 × 60' === $bo['data']['designation'] && 'Boulons (vis + écrou) M16 × 60 · 10.9 · Galvanisé à chaud' === $bo['title']['fr'] && 0 === strpos( $bo['title']['ar'], 'براغي' ), 'bolts: designation and title in both languages' );
smr_assert( smr_near( $bo['data']['total_price'], 48000 ), '400 bolts × 120 DA' );
$base['attrs'] = array( 'origin' => 'workshop', 'surface' => 'clean' );
$scrap_ok = Spec::build( array_merge( $base, array( 'category' => 'reuse-scrap', 'condition' => 'scrap', 'attrs' => array( 'origin' => 'dismantling' ), 'specs' => array( 'scrap_type' => 'heavy' ), 'total_kg' => '8000', 'price' => '45' ) ) );
smr_assert( ! $scrap_ok['errors'], 'scrap with the "scrap" condition is accepted' );
$scrap_bad = Spec::build( array_merge( $base, array( 'category' => 'reuse-scrap', 'condition' => 'good', 'attrs' => array( 'origin' => 'dismantling' ), 'specs' => array( 'scrap_type' => 'heavy' ), 'total_kg' => '8000' ) ) );
smr_assert( isset( $scrap_bad['errors']['condition'] ), 'scrap cannot be presented as reusable' );
smr_assert( isset( Spec::build( array_merge( $base, array( 'category' => 'reuse-scrap', 'condition' => 'scrap', 'specs' => array( 'scrap_type' => 'heavy' ), 'total_kg' => '8000', 'attrs' => array( 'origin' => 'workshop', 'cut' => '1' ) ) ) )['errors']['attrs_cut'] ), 'cutting on demand only for bars, profiles and plates' );
$scrap_cat = Spec::build( array_merge( $in, array( 'condition' => 'scrap' ) ) );
smr_assert( isset( $scrap_cat['errors']['condition'] ), 'steel only good for recycling must go in the Scrap category' );
$nocond = Spec::build( array_merge( $in, array( 'condition' => '', 'attrs' => array() ) ) );
smr_assert( isset( $nocond['errors']['condition'], $nocond['errors']['attrs_origin'] ), 'condition and origin are required' );
$draft = Spec::build( array_merge( $in, array( 'condition' => '' ) ), false );
smr_assert( ! isset( $draft['errors']['condition'] ), 'live preview does not nag about the condition' );

echo "\n4. Saving, search and product page\n";
$pid = Products::save( 0, $c_sel, $u_sel, $s, 'published' );
$p   = Products::get( $pid );
smr_assert( 'reuse' === $p->market && 'reuse' === Index::get( $p->listing_id )->module && 2 === count( $p->lines ), 'saved in the reuse market, indexed under "reuse", lengths kept' );
$find = static fn( $args ) => in_array( $p->listing_id, wp_list_pluck( (array) ( Bridge::listings( null, $args + array( 'per_page' => 60 ) )['items'] ?? array() ), 'id' ), true );
smr_assert( $find( array( 'market' => 'reuse' ) ) && ! $find( array( 'market' => 'products' ) ), 'listed in reuse, not in new products' );
smr_assert( $find( array( 'market' => 'reuse', 'condition' => 'good' ) ) && ! $find( array( 'market' => 'reuse', 'condition' => 'excellent' ) ), 'condition filter' );
smr_assert( $find( array( 'market' => 'reuse', 'length_min' => '3.5' ) ) && ! $find( array( 'market' => 'reuse', 'length_min' => '5' ) ), 'minimum length filter (longest bar)' );
smr_assert( $find( array( 'market' => 'reuse', 'q' => 'IPE200' ) ), 'found by its size' );
wp_set_current_user( $u_eng );
$d = Bridge::listing( null, array( 'id' => $p->listing_id ) );
smr_assert( 2 === count( $d['elements'] ) && 'IPE 200' === $d['elements'][0]['designation'] && 6 === $d['elements'][0]['qty'] && smr_near( $d['elements'][0]['length'], 2.5 ), 'product page: table of lengths and numbers' );
$rows = array_column( $d['spec_rows'], 1, 0 );
smr_assert( false !== strpos( implode( ' ', $rows ), '2,5 m × 6' ), 'specification: lengths × number' );
smr_assert( 'good' === $d['condition'] && ! empty( $d['advice']['text'] ) && 'engineers' === $d['advice']['link_page'], 'product page: engineer advice with a link to the engineers' );
smr_assert( 'expertise' === ( $d['inspection']['type'] ?? '' ), 'inspection request offered' );
$pre = apply_filters( 'sm_theme_data_mission_prefill', null, array( 'listing' => $d['id'] ) );
smr_assert( is_array( $pre ) && 'expertise' === $pre['type'] && 0 === strpos( $pre['title'], 'Inspection' ) && ! isset( $pre['project_type'] ), 'inspection request pre-filled with the lot; type of project left to the buyer' );
smr_assert( 'warning' === Advice::for_condition( 'inspection' )['level'] && empty( Advice::for_condition( 'scrap' )['link_page'] ), 'stronger advice for "to inspect"; recycling note for scrap' );
$bid = Products::save( 0, $c_sel, $u_sel, $bo, 'published' );
$bd  = Bridge::listing( null, array( 'id' => Products::get( $bid )->listing_id ) );
smr_assert( 'warning' === $bd['advice']['level'] && false !== strpos( $bd['advice']['text'], 'EN 1090-2' ), 'preloaded bolts: warning, not reused in a preloaded joint (EN 1090-2)' );
wp_set_current_user( $u_sel );
$form = Bridge::product_form( null, array( 'id' => $pid ) );
smr_assert( 'reuse' === $form['values']['market'] && 'rows' === $form['values']['qty_mode'] && 2 === count( $form['values']['rows'] ) && '2.5' === $form['values']['rows'][0]['length'] && '1' === $form['values']['attrs']['cut'], 'edit form gets back the lengths, condition and market fields' );
$mk = Bridge::product_markets( null );
smr_assert( $mk['reuse']['allowed'] && $mk['products']['allowed'], 'a manufacturer may publish reuse and new products' );

echo "\n4b. Lots of several listings\n";
$pid2 = Products::save( 0, $c_sel, $u_sel, $pl, 'published' );
$scr  = Products::save( 0, $c_sel, $u_sel, $scrap_ok, 'published' );
smr_assert( is_wp_error( \SM\Reuse\Groups::create( $c_sel, $u_sel, array( $pid ), '', 0 ) ), 'one listing is not a lot' );
smr_assert( is_wp_error( \SM\Reuse\Groups::create( $c_sel, $u_sel, array( $pid, $scr ), '', 0 ) ), 'scrap is not grouped' );
smr_assert( is_wp_error( \SM\Reuse\Groups::create( $c_eng, $u_eng, array( $pid, $pid2 ), '', 0 ) ), 'only the listings of one\'s company' );
$sum = \SM\Reuse\Groups::sum_prices( array( Products::get( $pid ), Products::get( $pid2 ) ) );
$gid = \SM\Reuse\Groups::create( $c_sel, $u_sel, array( $pid, $pid2 ), 'Lot test', 0 );
$g   = Products::get( $gid );
smr_assert( ! is_wp_error( $gid ) && 'published' === $g->status && 'lot' === $g->sale_mode && smr_near( $g->lot_price, $sum ) && smr_near( $g->total_weight, Products::get( $pid )->total_weight + Products::get( $pid2 )->total_weight, 0.01 ), 'lot online: whole lot only, price = sum of the prices, weight = sum' );
smr_assert( 'grouped' === Products::get( $pid )->status && 'grouped' === Products::get( $pid2 )->status && ! $find( array( 'market' => 'reuse' ) ), 'its products are no longer sold separately' );
$gd = Bridge::listing( null, array( 'id' => $g->listing_id ) );
smr_assert( 2 === count( $gd['group']['items'] ) && 'IPE 200' === $gd['group']['items'][0]['designation'] && false !== strpos( $gd['group']['items'][0]['sizes'], '2,5 m × 6' ) && ! empty( $gd['group']['items'][1]['condition'] ), 'lot page: each product with its sizes and its own condition' );
smr_assert( count( \SM\Core\Content\Media::get( 'product', $gid, 'photo' ) ) === count( array_unique( array_merge( \SM\Core\Content\Media::get( 'product', $pid, 'photo' ), \SM\Core\Content\Media::get( 'product', $pid2, 'photo' ) ) ) ), 'lot photos = photos of its products' );
\SM\Reuse\Groups::dissolve( $gid );
smr_assert( null === Products::get( $gid ) && 'published' === Products::get( $pid )->status && empty( Products::get( $pid )->specs['group'] ), 'lot undone: products back on sale' );
$gid = \SM\Reuse\Groups::create( $c_sel, $u_sel, array( $pid, $pid2 ), '', 300000 );
Products::set_status( $gid, 'sold' );
smr_assert( 'sold' === Products::get( $pid )->status && 'sold' === Products::get( $pid2 )->status && smr_near( Products::get( $gid )->lot_price, 300000 ), 'selling the lot sells its products; typed price kept' );

echo "\n5. Rules\n";
wp_set_current_user( $u_eng );
smr_assert( ! sm_can( 'listing.reuse' ), 'a partner engineer account can never publish reuse lots' );
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
