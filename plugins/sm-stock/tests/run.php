<?php
/**
 * SM Stock tests (test site only): wp eval-file wp-content/plugins/sm-stock/tests/run.php
 *
 * @package SM\Stock
 */

use SM\Core\Accounts\Companies;
use SM\Products\Products;
use SM\Stock\Items;
use SM\Stock\Labels;
use SM\Stock\Limits;
use SM\Stock\Moves;
use SM\Stock\Prices;

defined( 'ABSPATH' ) || exit;

$GLOBALS['smst_t'] = array( 'pass' => 0, 'fail' => 0, 'fails' => array() );
function smst_assert( $cond, $label ) {
	if ( $cond ) {
		++$GLOBALS['smst_t']['pass'];
		echo "  ✓ $label\n";
	} else {
		++$GLOBALS['smst_t']['fail'];
		$GLOBALS['smst_t']['fails'][] = $label;
		echo "  ✗ $label\n";
	}
}
function smst_cleanup() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sm_companies WHERE name LIKE 'SMSTTEST %'" ) as $cid ) {
		foreach ( array( 'sm_products', 'sm_stock_refs', 'sm_stock_moves' ) as $t ) {
			$wpdb->delete( $wpdb->prefix . $t, array( 'company_id' => $cid ) );
		}
		foreach ( array( 'company_members', 'company_activities', 'verifications', 'notifications' ) as $t ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}sm_{$t} WHERE company_id = %d", $cid ) ); // phpcs:ignore WordPress.DB
		}
		$wpdb->delete( $wpdb->prefix . 'sm_companies', array( 'id' => $cid ) );
	}
	foreach ( get_users( array( 'search' => 'smsttest-*', 'search_columns' => array( 'user_email' ) ) ) as $u ) {
		wp_delete_user( $u->ID );
	}
}
function smst_product( $cid, array $row ) {
	global $wpdb;
	$d = current_time( 'mysql', true );
	$wpdb->insert( $wpdb->prefix . 'sm_products', $row + array( 'listing_id' => 'T' . wp_generate_password( 7, false ), 'company_id' => $cid, 'user_id' => 0, 'category' => 'profiles', 'created_at' => $d, 'updated_at' => $d ) );
	return (int) $wpdb->insert_id;
}

smst_cleanup();
$uid = wp_insert_user( array( 'user_login' => 'smsttest_a', 'user_email' => 'smsttest-a@example.test', 'user_pass' => wp_generate_password(), 'role' => 'sm_member' ) );
$cid = Companies::create( array( 'account_class' => 'company', 'name' => 'SMSTTEST A', 'wilaya' => '28', 'phone' => '0550000000' ), $uid, array( 'supplier' ) );
wp_set_current_user( $uid );
$company = Companies::get( $cid );

echo "\n1. Items, references and locations\n";
$ipe = smst_product( $cid, array( 'market' => 'products', 'status' => 'published', 'family' => 'IPE', 'section_id' => 7, 'designation' => 'IPE 200', 'grade' => 'S275JR', 'qty_mode' => 'bars', 'length_mm' => 12000, 'pieces' => 10, 'unit_weight' => 268.32, 'total_weight' => 2683.2, 'price' => 230, 'price_unit' => 'kg', 'total_price' => 617136, 'price_per_kg' => 230 ) );
$hea = smst_product( $cid, array( 'market' => 'products', 'status' => 'stock', 'family' => 'HEA', 'section_id' => 22, 'designation' => 'HEA 160', 'grade' => 'S275JR', 'qty_mode' => 'bars', 'length_mm' => 12000, 'pieces' => 12, 'unit_weight' => 365.28, 'total_weight' => 4383.36 ) );
$lot = smst_product( $cid, array( 'market' => 'reuse', 'category' => 'reuse-offcuts', 'status' => 'stock', 'item_condition' => 'good', 'designation' => 'IPE 200 +1', 'qty_mode' => 'bars', 'pieces' => 9, 'total_weight' => 700.627, 'price' => 140, 'price_unit' => 'kg', 'total_price' => 98087.78, 'inventory' => wp_json_encode( array( array( 'family' => 'IPE', 'section_id' => 7, 'designation' => 'IPE 200', 'qty' => 6, 'length_mm' => 2500, 'kg_m' => 22.36, 'total' => 335.4 ), array( 'family' => 'HEA', 'section_id' => 22, 'designation' => 'HEA 160', 'qty' => 3, 'length_mm' => 4000, 'kg_m' => 30.436, 'total' => 365.227 ) ) ) ) );
$new  = Items::products( $cid, 'products' );
$refs = Items::ensure_refs( $cid, $new );
smst_assert( 2 === count( $new ) && 'N-0001' === $refs[ $hea . '|' ]->ref && 'N-0002' === $refs[ $ipe . '|' ]->ref, 'new steel: one reference per item, N-0001, N-0002 (published and in stock only)' );
$reuse = Items::products( $cid, 'reuse' );
$rr    = Items::ensure_refs( $cid, $reuse );
smst_assert( 2 === count( Items::variants( $reuse[0] ) ) && 'R-0001' === $rr[ $lot . '|-7-2500' ]->ref && 'R-0002' === $rr[ $lot . '|-22-4000' ]->ref, 'reuse: one reference per variant (profile and length), R-0001, R-0002' );
smst_assert( true === Items::set_ref( $cid, $ipe, '', 'a-12', 'Hangar 2 – rack B' ) && 'A-12' === Items::refs( array( $ipe ) )[ $ipe . '|' ]->ref, 'reference changed by the seller (capital letters), location saved' );
smst_assert( is_wp_error( Items::set_ref( $cid, $hea, '', 'A-12', '' ) ) && is_wp_error( Items::set_ref( $cid, $hea, '', 'bad ref!', '' ) ), 'same reference twice, or a wrong one: refused' );
smst_assert( Moves::has( $ipe ) && Moves::has( $lot ), 'arrival of each item noted in the movements' );
smst_assert( abs( Items::weight( $cid ) - ( 2683.2 + 4383.36 + 700.627 ) ) < 0.01, 'weight of the warehouse: new and reuse, published or not' );

echo "\n2. Corrections\n";
$res = Items::adjust( Products::get( $ipe ), '', 8 );
$p   = Products::get( $ipe );
smst_assert( 10.0 === (float) $res['before'] && 8 === $res['after'] && 8.0 === (float) $p->pieces && abs( $p->total_weight - 2146.56 ) < 0.01 && abs( $p->total_price - 2146.56 * 230 ) < 0.1, 'bars: 10 → 8, weight and total price follow' );
$res = Items::adjust( Products::get( $lot ), '-7-2500', 4 );
$p   = Products::get( $lot );
smst_assert( 4 === $res['after'] && 7 === (int) $p->pieces && abs( $p->total_weight - ( 223.6 + 365.227 ) ) < 0.01, 'reuse line: 6 → 4 pieces, the lot follows (7 pieces, weight of the lines)' );
smst_assert( is_wp_error( Items::adjust( Products::get( $lot ), '-99-100', 1 ) ), 'unknown variant: refused' );

echo "\n3. Movements\n";
SM\Products\Stock::take( $ipe, 'detail', 2, 'C-TEST' );
$mv = Moves::recent( $cid, 5 );
smst_assert( 'sale' === $mv[0]->kind && -2.0 === (float) $mv[0]->qty && 'C-TEST' === $mv[0]->ref && abs( (float) $mv[0]->kg + 536.64 ) < 0.01, 'sale accepted on the platform: taken off and noted (− 2 bars, − 536,6 kg, order ref)' );
Products::set_status( $hea, 'published' );
Products::set_status( $hea, 'stock' );
$mv = Moves::recent( $cid, 2 );
smst_assert( 'withdraw' === $mv[0]->kind && 'publish' === $mv[1]->kind, 'published, then kept in stock only: both noted' );

echo "\n4. Limits of the plan\n";
$used = Items::weight( $cid );
$cap  = static fn( $kg, $c ) => $used + 500.0;
add_filter( 'sm_stock_capacity_kg', $cap, 99, 2 );
smst_assert( '' !== Limits::check( '', $company, 1000, null ) && '' === Limits::check( '', $company, 10, null ), 'capacity full but 500 kg: a new item of 1 t refused, 10 kg accepted' );
smst_assert( '' === Limits::check( '', $company, (float) Products::get( $hea )->total_weight + 400, Products::get( $hea ) ), 'editing an item: its own weight is not counted twice' );
remove_filter( 'sm_stock_capacity_kg', $cap, 99 );
$none = static fn( $kg, $c ) => null;
add_filter( 'sm_stock_capacity_kg', $none, 99, 2 );
smst_assert( '' === Limits::check( '', $company, 99999, null ), 'without a plan limit: no limit' );
remove_filter( 'sm_stock_capacity_kg', $none, 99 );
smst_assert( 20000.0 === Limits::capacity( $company ) && false === Limits::tools( $company ), 'free company plan: 20 t, no advanced tools (sm-subscriptions)' );
$client = (object) array( 'id' => 0, 'account_class' => 'client' );
smst_assert( ! Limits::has_warehouse( $client ) && '' !== Limits::check( '', $client, 1, null ), 'client accounts have no warehouse' );

echo "\n5. Price for a whole type\n";
$ipe2 = smst_product( $cid, array( 'market' => 'products', 'status' => 'stock', 'family' => 'IPE', 'section_id' => 9, 'designation' => 'IPE 240', 'grade' => 'S235JR', 'qty_mode' => 'bars', 'length_mm' => 12000, 'pieces' => 2, 'unit_weight' => 368.52, 'total_weight' => 737.04 ) );
$rows = Prices::preview( $cid, Prices::read( array( 'family' => 'IPE', 'mode' => 'price', 'value' => '135' ) ) );
smst_assert( 2 === count( $rows ) && 230.0 === $rows[0]['old'] + $rows[1]['old'] - 0 && 135.0 === $rows[0]['new'], 'preview « 135 DA/kg for every IPE »: both IPE, old and new price (one not priced yet)' );
smst_assert( 1 === count( Prices::preview( $cid, Prices::read( array( 'family' => 'IPE', 'grade' => 's275jr', 'mode' => 'price', 'value' => '135' ) ) ) ), 'with the grade S275JR: one product' );
$n = Prices::apply( $cid, $rows );
$p = Products::get( $ipe );
smst_assert( 2 === $n && 135.0 === (float) $p->price && 135.0 === (float) $p->price_per_kg && abs( $p->total_price - 135 * $p->total_weight ) < 0.1, 'applied: price, price per kg and total price' );
$rows = Prices::preview( $cid, Prices::read( array( 'family' => 'IPE', 'mode' => 'percent', 'value' => '+10' ) ) );
smst_assert( 148.5 === $rows[0]['new'], '« + 10 % »: 135 → 148,5 DA/kg' );
smst_assert( 'price' === Moves::recent( $cid, 1 )[0]->kind, 'price change noted in the movements' );

echo "\n6. Labels\n";
$qr = Labels::qr( 'https://reusteel.com/compte/magasin/?ref=N-0001' );
smst_assert( count( $qr ) >= 21 && count( $qr ) === count( $qr[0] ) && $qr[0][0] && $qr[6][6] && ! $qr[7][7], 'QR code: square, finder patterns in the corners' );
$pdf = Labels::pdf( $company, 'reuse' );
smst_assert( 0 === strpos( $pdf, '%PDF-1.4' ) && false !== strpos( $pdf, 'R-0001' ), 'labels of the reuse warehouse as a PDF' );

echo "\n7. The warehouse in the cutting optimiser (0.2.0)\n";
$cut  = \SM\WeightEngine\Cutting::class;
$s220 = 8; // IPE 220.
$n12  = smst_product( $cid, array( 'market' => 'products', 'status' => 'stock', 'family' => 'IPE', 'section_id' => $s220, 'designation' => 'IPE 220', 'grade' => 'S275JR', 'qty_mode' => 'bars', 'length_mm' => 12000, 'pieces' => 3, 'unit_weight' => 314.35, 'total_weight' => 943.05 ) );
$r62  = smst_product( $cid, array( 'market' => 'reuse', 'category' => 'reuse-offcuts', 'status' => 'published', 'item_condition' => 'excellent', 'designation' => 'IPE 220', 'qty_mode' => 'bars', 'pieces' => 2, 'total_weight' => 324.8, 'inventory' => wp_json_encode( array( array( 'family' => 'IPE', 'section_id' => $s220, 'designation' => 'IPE 220', 'qty' => 2, 'length_mm' => 6200, 'kg_m' => 26.196, 'total' => 324.83, 'grade' => 'S235JR' ) ) ) ) );
$offers = \SM\Stock\Cutting::offers( array(), $s220, array( 'new' => true, 'reuse' => true ) );
$keys   = wp_list_pluck( $offers, 'key' );
smst_assert( 2 === count( $offers ) && in_array( 'P' . $n12 . ':', $keys, true ) && in_array( 'P' . $r62 . ':-' . $s220 . '-6200', $keys, true ), 'offers of the warehouse: the new bars and the reused variant of the same section, with their keys' );
$o12 = $offers[ array_search( 'P' . $n12 . ':', $keys, true ) ];
smst_assert( 0 === strpos( $o12['ref'], 'N-' ) && 'new' === $o12['condition'] && ! $o12['published'] && 3 === $o12['lines'][0]['qty'], 'with reference, condition, published or not, number of bars' );
smst_assert( 1 === count( \SM\Stock\Cutting::offers( array(), $s220, array( 'new' => true, 'reuse' => true, 'unpublished' => true ) ) ) && 1 === count( \SM\Stock\Cutting::offers( array(), $s220, array( 'reuse' => true ) ) ), 'options: in stock only (the published lot left out), reuse only' );
$in  = $cut::read( array( 'src' => 1, 'mine' => 1, 'mine_new' => 1, 'mine_reuse' => 1, 'rows' => array( array( 'section' => $s220, 'length' => '5,40', 'qty' => 4 ), array( 'section' => $s220, 'length' => '2,10', 'qty' => 2 ) ) ) );
$res = $cut::optimize( $in );
$t   = $res['totals'];
smst_assert( $res['ok'] && 0 === $t['new_bars'] && $t['mine_bars'] >= 3 && 2 === $t['mine_items'], 'plan: the pieces come from the warehouse, nothing to buy (' . $t['mine_bars'] . ' bars, 2 references)' );
$src = array_unique( array_column( $res['profiles'][0]['bars'], 'source' ) );
smst_assert( array( 'mine' ) === array_values( $src ), 'every bar of the plan is labelled « mine »' );
$no  = $cut::optimize( $cut::read( array( 'src' => 1, 'rows' => $in['lines'] ? array( array( 'section' => $s220, 'length' => '5,40', 'qty' => 4 ) ) : array() ) ) );
smst_assert( 0 === $no['totals']['mine_bars'] && $no['totals']['new_bars'] > 0, 'box unticked: new bars only' );
$off = $t['offcuts'];
smst_assert( $off && $off[0]['ref'] && 'mine' === $off[0]['source'] && $off[0]['length'] >= 1000, 'reusable offcuts with their origin (reference ' . ( $off[0]['ref'] ?? '' ) . ', ' . ( $off[0]['length'] ?? 0 ) . ' mm)' );
$take = array();
foreach ( $res['profiles'][0]['mine'] as $m ) {
	$take[ $m['key'] ] = $m['take_count'];
}
$before = (float) Products::get( $n12 )->pieces + (float) Products::get( $r62 )->pieces;
$done   = \SM\Stock\Cutting::take_out( $company, $take );
$after  = (float) Products::get( $n12 )->pieces + (float) Products::get( $r62 )->pieces;
smst_assert( ! is_wp_error( $done ) && $done['bars'] === array_sum( $take ) && $before - $after === (float) array_sum( $take ), 'bars taken out of the warehouse once the cutting is done' );
smst_assert( 'cutting' === Moves::recent( $cid, 1 )[0]->kind && '' !== Moves::recent( $cid, 1 )[0]->ref, 'movement « cutting » with the reference' );
smst_assert( is_wp_error( \SM\Stock\Cutting::take_out( $company, array( 'P' . $n12 . ':' => 99 ) ) ), 'more bars than left: refused (run the optimiser again)' );
smst_assert( is_wp_error( \SM\Stock\Cutting::take_out( $company, array( 'P' . $ipe2 . ':' => 1, 'P999999:' => 1 ) ) ), 'an item of another company or unknown: refused' );
$add = \SM\Stock\Cutting::add( $company, array( array( 'section' => $s220, 'length' => 1194, 'count' => 2, 'condition' => 'new', 'grade' => 'S275JR' ), array( 'section' => $s220, 'length' => 1600, 'count' => 1, 'condition' => '', 'grade' => '' ) ), 'Hangar 2 – chutes' );
$a1  = is_array( $add ) ? Products::get( $add['items'][0] ) : null;
smst_assert( is_array( $add ) && 2 === count( $add['items'] ) && 3 === $add['pieces'], 'offcuts added: one item per condition and grade (2 items, 3 pieces)' );
smst_assert( $a1 && 'stock' === $a1->status && 'reuse' === $a1->market && 'new_surplus' === $a1->item_condition && 'S275JR' === $a1->grade && 0.0 === (float) $a1->price, 'in stock only, reuse market, condition of the new bar → new surplus, its grade, no price yet' );
$a2 = is_array( $add ) ? Products::get( $add['items'][1] ) : null;
smst_assert( $a2 && 'good' === $a2->item_condition, 'offcut of a bought bar: condition « good »' );
$rr = Items::refs( array( $a1->id ) );
smst_assert( 1 === count( $rr ) && 0 === strpos( reset( $rr )->ref, 'R-' ) && 'Hangar 2 – chutes' === reset( $rr )->location && 'offcuts' === Moves::recent( $cid, 1 )[0]->kind, 'automatic reference R-, location, movement « offcuts »' );
$cap = static fn( $kg, $c ) => Items::weight( $cid ) + 10.0;
add_filter( 'sm_stock_capacity_kg', $cap, 99, 2 );
smst_assert( is_wp_error( \SM\Stock\Cutting::add( $company, array( array( 'section' => $s220, 'length' => 3000, 'count' => 5 ) ) ) ), 'warehouse full: the offcuts are refused, nothing is added' );
remove_filter( 'sm_stock_capacity_kg', $cap, 99 );

echo "\n8. Publishing an item of the warehouse (sm-products 0.21.0)\n";
smst_assert( '' !== \SM\Products\Form::missing_to_publish( $a1 ), 'no price yet: it cannot go on the market' );
$GLOBALS['wpdb']->update( Products::table(), array( 'price' => 150 ), array( 'id' => $a1->id ) );
smst_assert( false !== strpos( \SM\Products\Form::missing_to_publish( Products::get( $a1->id ) ), '2' ), 'priced, but the reuse market asks for 2 photos' );

if ( class_exists( '\\SM\\Trade\\Deals' ) ) {
	echo "\n9. Reference of the item in an order (sm-trade)\n";
	$sp  = \SM\Products\Spec::build( array( 'wilaya' => '28', 'title' => array( 'fr' => 'Test magasin' ), 'price' => '200', 'price_unit' => 'kg', 'category' => 'profiles', 'family' => 'IPE', 'section' => 'IPE 200', 'bars' => '10' ) );
	$pub = Products::save( 0, $cid, $uid, $sp, 'published' );
	Items::ensure_refs( $cid, array( Products::get( $pub ) ) );
	Items::set_ref( $cid, $pub, '', 'IPE-ORD', 'Hangar 9' );
	$bu  = wp_insert_user( array( 'user_login' => 'smsttest_b', 'user_email' => 'smsttest-b@example.test', 'user_pass' => wp_generate_password(), 'role' => 'sm_member' ) );
	Companies::create( array( 'account_class' => 'client', 'name' => 'SMSTTEST B', 'wilaya' => '28', 'phone' => '0550000001' ), $bu, array( 'client' ) );
	$did = \SM\Trade\Deals::create( $pub, $bu, array( 'kind' => 'order', 'mode' => 'detail', 'quantity' => 2, 'name' => 'Test', 'phone' => '0550 00 00 01', 'email' => 'smsttest-b@example.test' ) );
	wp_set_current_user( $bu );
	$view = apply_filters( 'sm_theme_data_deal', null, array( 'id' => $did ) );
	smst_assert( is_array( $view ) && empty( $view['stock_refs'] ), 'buyer: no reference before the order is accepted' );
	wp_set_current_user( $uid );
	$view = apply_filters( 'sm_theme_data_deal', null, array( 'id' => $did ) );
	smst_assert( 'IPE-ORD' === ( $view['stock_refs'][0]['ref'] ?? '' ) && 'Hangar 9' === $view['stock_refs'][0]['location'], 'seller: the reference and the location' );
	\SM\Trade\Deals::act( $did, $uid, 'accept' );
	wp_set_current_user( $bu );
	$view = apply_filters( 'sm_theme_data_deal', null, array( 'id' => $did ) );
	smst_assert( 'IPE-ORD' === ( $view['stock_refs'][0]['ref'] ?? '' ) && '' === $view['stock_refs'][0]['location'], 'buyer, once accepted: the reference for the pickup, not the location' );
	$GLOBALS['wpdb']->delete( \SM\Trade\Deals::table(), array( 'id' => $did ) );
	$GLOBALS['wpdb']->delete( \SM\Trade\Deals::events_table(), array( 'deal_id' => $did ) );
}

wp_set_current_user( 0 );
smst_cleanup();
$t = $GLOBALS['smst_t'];
printf( "\n%d passed, %d failed\n", $t['pass'], $t['fail'] );
if ( $t['fail'] ) {
	echo "FAILED:\n  - " . implode( "\n  - ", $t['fails'] ) . "\n";
	exit( 1 );
}
