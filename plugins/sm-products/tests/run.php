<?php
/**
 * Tests of sm-products. Run on a TEST site only (creates then removes data):
 *   wp eval-file wp-content/plugins/sm-products/tests/run.php
 *
 * @package SM\Products
 */

use SM\Core\Accounts\Companies;
use SM\Core\Content\Translations;
use SM\Core\Listings\Index;
use SM\Products\Categories;
use SM\Products\Expiry;
use SM\Products\Form;
use SM\Products\Products;
use SM\Products\Settings;
use SM\Products\Spec;
use SM\Products\Theme\Bridge;

defined( 'ABSPATH' ) || exit;

$GLOBALS['smp_t'] = array( 'pass' => 0, 'fail' => 0, 'fails' => array() );
function smp_assert( $cond, $label ) {
	$t = &$GLOBALS['smp_t'];
	if ( $cond ) {
		++$t['pass'];
		echo "  ✓ $label\n";
	} else {
		++$t['fail'];
		$t['fails'][] = $label;
		echo "  ✗ $label\n";
	}
}
function smp_section( $s ) {
	echo "\n$s\n";
}
function smp_near( $a, $b, $tol = 0.01 ) {
	return abs( (float) $a - (float) $b ) <= $tol;
}
function smp_user( $suffix, $class, array $activities ) {
	$uid = wp_insert_user( array( 'user_login' => 'smptest_' . $suffix, 'user_email' => "smptest-{$suffix}@example.test", 'user_pass' => wp_generate_password(), 'role' => 'sm_member' ) );
	$cid = Companies::create( array( 'account_class' => $class, 'name' => "SMPTEST {$suffix}", 'wilaya' => '28' ), $uid, $activities );
	Companies::raise_level( $cid, 1 );
	return array( $uid, $cid );
}
function smp_cleanup() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sm_companies WHERE name LIKE 'SMPTEST %'" ) as $cid ) {
		foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}sm_products WHERE company_id = %d", $cid ) ) as $pid ) {
			Products::delete( $pid );
		}
		foreach ( array( 'company_members', 'company_activities' ) as $t ) {
			$wpdb->delete( $wpdb->prefix . 'sm_' . $t, array( 'company_id' => $cid ) );
		}
		$wpdb->delete( $wpdb->prefix . 'sm_companies', array( 'id' => $cid ) );
	}
	foreach ( get_users( array( 'search' => 'smptest-*', 'search_columns' => array( 'user_email' ) ) ) as $u ) {
		$wpdb->delete( $wpdb->prefix . 'sm_notifications', array( 'user_id' => $u->ID ) );
		wp_delete_user( $u->ID );
	}
}
smp_cleanup();
$suffix = substr( md5( (string) microtime( true ) ), 0, 6 );
list( $u_sel, $c_sel ) = smp_user( 's' . $suffix, 'company', array( 'supplier' ) );
list( $u_oth, $c_oth ) = smp_user( 'o' . $suffix, 'company', array( 'manufacturer' ) );
list( $u_eng, $c_eng ) = smp_user( 'e' . $suffix, 'engineer', array( 'engineer' ) );

$base = array( 'wilaya' => '28', 'title' => array( 'fr' => 'Test produit' ), 'price' => '245' );

/* ------------------------------------------------------------------ */
smp_section( '1. Categories' );
$cats = Categories::all( false, 'products' );
smp_assert( 7 === count( $cats ) && 'engine' === $cats['profiles']->kind && 'specs' === $cats['roofing']->kind, '7 default categories (4 from the weight engine, 3 with their own fields)' );
smp_assert( array( 'IPE', 'HEA', 'HEB', 'HEM', 'IPN', 'UPN', 'UPE', 'L', 'LI' ) === $cats['profiles']->families, 'profiles cover the 9 rolled-section families' );
$keys = wp_list_pluck( $cats['roofing']->fields, 'key' );
smp_assert( ! array_diff( array( 'roof_type', 'maker_type', 'width_mm', 'length_min_m', 'length_max_m', 'sheet_mm', 'insulation_mm' ), $keys ), 'roofing asks the maker type, width, min/max length, sheet and PS thickness' );
$clean = Categories::clean_fields( array( array( 'key' => 'Coating', 'type' => 'select', 'label' => array( 'fr' => 'Revêtement' ), 'options' => array( 'galva|Galvanisé|مجلفن', '' ) ), array( 'key' => '', 'label' => array( 'fr' => 'x' ) ), array( 'key' => 'coating', 'label' => array( 'fr' => 'doublon' ) ) ) );
smp_assert( 1 === count( $clean ) && 'galva' === $clean[0]['options'][0]['value'] && 'مجلفن' === $clean[0]['options'][0]['ar'], 'admin field editor: keys cleaned, duplicates and empty rows dropped, options parsed' );

/* ------------------------------------------------------------------ */
smp_section( '2. Server-side checks and calculations (Spec)' );
$s = Spec::build( $base + array( 'category' => 'profiles', 'family' => 'IPE', 'section' => 'IPE 200', 'bars' => '10', 'price_unit' => 'kg' ) );
smp_assert( ! $s['errors'] && smp_near( $s['data']['total_weight'], 2683.202, 0.001 ) && smp_near( $s['data']['total_price'], 657384.49 ), 'IPE 200 × 10 bars of 12 m at 245 DA/kg = 2 683,2 kg, 657 384,49 DA' );
smp_assert( 'theoretical' === $s['data']['weight_source'] && '0.1.0' === $s['data']['engine_version'] && $s['data']['section_id'] > 0, 'weight marked theoretical, engine version and section stored' );
$w = Spec::build( $base + array( 'category' => 'profiles', 'family' => 'IPE', 'section' => 'IPE 200', 'qty_mode' => 'weight', 'total_kg' => '5000' ) );
smp_assert( smp_near( $w['data']['pieces'], 18.63 ) && smp_near( $w['data']['total_price'], 1225000 ), 'by total weight: 5 000 kg ≈ 18,63 bars, total 1 225 000 DA' );
$a = Spec::build( $base + array( 'category' => 'profiles', 'family' => 'IPE', 'section' => 'IPE 200', 'bars' => '10', 'weight_source' => 'actual', 'actual_weight' => '2700' ) );
smp_assert( 'actual' === $a['data']['weight_source'] && smp_near( $a['data']['total_weight'], 2700 ), 'weighed weight replaces the theoretical one' );
$far = Spec::build( $base + array( 'category' => 'profiles', 'family' => 'IPE', 'section' => 'IPE 200', 'bars' => '10', 'weight_source' => 'actual', 'actual_weight' => '500' ) );
smp_assert( isset( $far['errors']['actual_weight'] ), 'a weighed weight far from the theoretical one is questioned' );
$t = Spec::build( $base + array( 'category' => 'tubes', 'family' => 'RHS', 'dims' => array( 'h' => '120', 'b' => '80', 't' => '4' ), 'bars' => '5', 'length' => 'custom', 'length_custom' => '6,5' ) );
smp_assert( ! $t['errors'] && 'RHS 120x80x4' === $t['data']['designation'] && 6500 === $t['data']['length_mm'], 'typed tube with a free length of 6,5 m' );
$pl = Spec::build( array_merge( $base, array( 'category' => 'plates', 'dims' => array( 't' => '10' ), 'width' => '1500', 'plate_length' => '3000', 'bars' => '4', 'price' => '20000', 'price_unit' => 'unit' ) ) );
smp_assert( ! $pl['errors'] && smp_near( $pl['data']['total_weight'], 1413 ) && smp_near( $pl['data']['total_price'], 80000 ) && smp_near( $pl['data']['price_per_kg'], 56.62 ), 'plates priced per piece: 4 × 20 000 DA, 56,62 DA/kg equivalent' );
// 0.5.0: sale by the whole lot, or both.
$ipe = $base + array( 'category' => 'profiles', 'family' => 'IPE', 'section' => 'IPE 200', 'bars' => '10', 'price_unit' => 'kg' );
$lot = Spec::build( array( 'sale_mode' => 'lot', 'lot_price' => '600000' ) + $ipe );
smp_assert( ! $lot['errors'] && 'lot' === $lot['data']['sale_mode'] && 'lot' === $lot['data']['price_unit'] && smp_near( $lot['data']['total_price'], 600000 ) && smp_near( $lot['data']['price_per_kg'], 223.61 ), 'whole lot only: 600 000 DA, ≈ 223,61 DA/kg shown for comparison' );
$both = Spec::build( array( 'sale_mode' => 'both', 'lot_price' => '600000' ) + $ipe );
smp_assert( ! $both['errors'] && smp_near( $both['data']['total_price'], 657384.49 ) && smp_near( $both['data']['lot_price'], 600000 ) && smp_near( $both['summary']['lot_per_kg'], 223.61 ), 'both: 245 DA/kg at detail, or 600 000 DA for the lot' );
smp_assert( isset( Spec::build( array( 'sale_mode' => 'both', 'lot_price' => '700000' ) + $ipe )['errors']['lot_price'] ) && isset( Spec::build( array( 'sale_mode' => 'lot' ) + $ipe )['errors']['lot_price'] ), 'lot price required, and not above the detail total' );
smp_assert( 'detail' === Spec::build( array( 'sale_mode' => 'lot', 'lot_price' => '1000', 'category' => 'roofing', 'specs' => array( 'roof_type' => 'sandwich_ps', 'maker_type' => 'TL 40', 'width_mm' => '1000', 'sheet_mm' => '0,4', 'insulation_mm' => '40' ), 'quantity' => '500', 'unit' => 'm2' ) + $base )['data']['sale_mode'], 'roofing panels keep their unit price (no whole lot)' );
$x = Spec::build( $base + array( 'category' => 'profiles', 'family' => 'IPE', 'section' => 'IPE 200', 'qty_mode' => 'weight', 'total_kg' => '5000', 'price_unit' => 'unit' ) );
smp_assert( isset( $x['errors']['price_unit'] ), 'price per piece refused when only the total weight is known' );
$r = Spec::build( array_merge( $base, array( 'category' => 'roofing', 'specs' => array( 'roof_type' => 'sandwich_ps', 'maker_type' => 'TL 40', 'width_mm' => '1000', 'sheet_mm' => '0,4', 'insulation_mm' => '40' ), 'quantity' => '500', 'unit' => 'm2', 'price' => '2900' ) ) );
smp_assert( ! $r['errors'] && smp_near( $r['data']['total_price'], 1450000 ) && 'none' === $r['data']['weight_source'] && 'm2' === $r['data']['price_unit'], 'roofing: 500 m² × 2 900 DA/m², no invented weight' );
$r2 = Spec::build( array_merge( $base, array( 'category' => 'roofing', 'specs' => array( 'roof_type' => 'tile', 'width_mm' => '-3' ), 'quantity' => '0' ) ) );
smp_assert( isset( $r2['errors']['specs_roof_type'], $r2['errors']['specs_maker_type'], $r2['errors']['specs_width_mm'], $r2['errors']['specs_sheet_mm'], $r2['errors']['quantity'] ), 'roofing: unknown type, missing fields, negative width and zero quantity refused' );
$bad = Spec::build( array( 'category' => 'profiles', 'family' => 'XYZ' ) );
smp_assert( isset( $bad['errors']['family'], $bad['errors']['title'], $bad['errors']['wilaya'], $bad['errors']['price'] ), 'missing type, title, wilaya and price reported' );
$hea = Spec::build( $base + array( 'category' => 'tubes', 'family' => 'HEA', 'section' => 'HEA 200', 'bars' => '1' ) );
smp_assert( isset( $hea['errors']['family'] ), 'a family outside the category is refused' );

/* ------------------------------------------------------------------ */
smp_section( '3. Saving, index, visibility' );
$pid = Products::save( 0, $c_sel, $u_sel, $s, 'pending' );
$p   = Products::get( $pid );
$idx = Index::get( $p->listing_id );
smp_assert( $p->listing_id > 0 && $idx && 'products' === $idx->module && 'pending' === $idx->status && smp_near( $idx->total_weight, 2683.202, 0.001 ), 'saved product indexed in the cross-market index (public listing number)' );
smp_assert( 'Test produit' === ( Translations::get( 'product', $pid )['title']['fr'] ?? '' ), 'title stored in the translations table' );
smp_assert( $c_sel === (int) apply_filters( 'sm_listing_owner', 0, 'products', $p->listing_id ), 'enquiries reach the seller company (sm_listing_owner)' );
wp_set_current_user( $u_oth );
smp_assert( null === Bridge::listing( null, array( 'id' => $p->listing_id ) ), 'a product waiting for review is hidden from other users' );
smp_assert( 0 === Bridge::listings( null, array( 'market' => 'products' ) )['total'] || ! in_array( $p->listing_id, wp_list_pluck( Bridge::listings( null, array( 'market' => 'products', 'per_page' => 60 ) )['items'], 'id' ), true ), '…and from the public list' );
smp_assert( null === Form::editable( $pid ), 'another company cannot edit it' );
wp_set_current_user( $u_sel );
smp_assert( 'pending' === Bridge::listing( null, array( 'id' => $p->listing_id ) )['status'], 'the seller can preview it' );
Products::set_status( $pid, 'published' );
wp_set_current_user( $u_oth );
$item = Bridge::listing( null, array( 'id' => $p->listing_id ) );
smp_assert( $item && 'IPE 200' === $item['designation'] && false !== strpos( $item['drawing'], '<svg' ) && count( $item['spec_rows'] ) >= 7, 'published: public page data with exact drawing and spec rows' );
smp_assert( 1 === Products::get( $pid )->views, 'a visit by a buyer is counted' );
$list = Bridge::listings( null, array( 'market' => 'products', 'category' => 'profiles', 'q' => 'IPE200', 'per_page' => 60 ) );
smp_assert( in_array( $p->listing_id, wp_list_pluck( $list['items'], 'id' ), true ), 'found by category and by designation typed without space (IPE200)' );
smp_assert( ! in_array( $p->listing_id, wp_list_pluck( Bridge::listings( null, array( 'market' => 'products', 'wilaya' => '16', 'per_page' => 60 ) )['items'], 'id' ), true ), 'wilaya filter' );
add_filter( 'sm_theme_demo_enabled', '__return_true', 99 );
smp_assert( null === Bridge::listings( null, array( 'market' => array( 'products', 'services' ) ) ), 'a search mixing a market no plugin serves yet (services) is left to the demo provider' );
remove_filter( 'sm_theme_demo_enabled', '__return_true', 99 );
add_filter( 'sm_theme_demo_enabled', '__return_false', 99 );
$mixed = Bridge::listings( null, array( 'market' => array( 'products', 'services' ), 'per_page' => 60 ) );
smp_assert( is_array( $mixed ) && in_array( $p->listing_id, wp_list_pluck( $mixed['items'], 'id' ), true ), '…and answered with the served markets once demo mode is off' );
remove_filter( 'sm_theme_demo_enabled', '__return_false', 99 );
smp_assert( null === Bridge::listings( null, array( 'market' => 'services' ) ), 'a market no plugin serves is never answered' );

/* ------------------------------------------------------------------ */
smp_section( '4. Life cycle' );
Products::set_status( $pid, 'sold' );
$sold = Bridge::listing( null, array( 'id' => $p->listing_id ) );
smp_assert( ! empty( $sold['unavailable'] ) && 'sold' === Index::get( $p->listing_id )->status, 'sold: page kept with a notice, index updated' );
Products::set_status( $pid, 'published' );
global $wpdb;
$wpdb->update( $wpdb->prefix . 'sm_products', array( 'expires_at' => '2000-01-01 00:00:00' ), array( 'id' => $pid ) );
Expiry::run();
smp_assert( 'expired' === Products::get( $pid )->status, 'daily job: publication period over -> expired' );
smp_assert( (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}sm_notifications WHERE user_id = %d AND type = 'product.expired'", $u_sel ) ), 'seller notified of the expiry' );
Products::set_status( $pid, 'rejected', 'Photo floue' );
smp_assert( 'Photo floue' === Products::get( $pid )->reject_reason, 'moderation: reason stored with the rejection' );
$counts = Products::count_by_status( $c_sel );
smp_assert( 1 === ( $counts['rejected'] ?? 0 ), 'counts by status for "My listings"' );
wp_set_current_user( $u_sel );
$form = Bridge::product_form( null, array( 'id' => $pid ) );
smp_assert( 'IPE' === $form['values']['family'] && '10' === $form['values']['bars'] && '12000' === $form['values']['length'], 'edit form refilled from the saved product' );
Products::delete( $pid );
smp_assert( null === Products::get( $pid ) && null === Index::get( $p->listing_id ), 'deleted with its index row' );

/* ------------------------------------------------------------------ */
smp_section( '5. Rules' );
wp_set_current_user( $u_eng );
smp_assert( ! sm_can( 'listing.products' ), 'a partner engineer account can never publish products' );
wp_set_current_user( $u_oth );
smp_assert( sm_can( 'listing.products' ) && sm_can( 'buy' ), 'every company type both buys and sells' );
wp_set_current_user( $u_sel );
smp_assert( sm_can( 'listing.products' ), 'a supplier with a verified contact can publish' );

smp_section( '6. Client accounts (private sellers)' );
$settings_before = get_option( Settings::OPTION, null );
Settings::save( array_merge( Settings::all(), array( 'moderation' => 0, 'client_moderation' => 1, 'client_max_active' => 1 ) ) );
list( $u_cli, $c_cli ) = smp_user( 'c' . $suffix, 'client', array() );
wp_set_current_user( $u_cli );
smp_assert( sm_can( 'listing.products' ) && sm_can( 'buy' ), 'a client can sell and buy' );
smp_assert( 'pending' === \SM\Products\Form::publish_status( Companies::get( $c_cli ) ) && 'published' === \SM\Products\Form::publish_status( Companies::get( $c_sel ) ), 'client listings are checked even when moderation is off' );
smp_assert( '' === \SM\Products\Form::client_limit( Companies::get( $c_cli ) ), 'first client listing allowed' );
$cid = Products::save( 0, $c_cli, $u_cli, $s, 'pending' );
smp_assert( '' !== \SM\Products\Form::client_limit( Companies::get( $c_cli ) ) && '' === \SM\Products\Form::client_limit( Companies::get( $c_cli ), Products::get( $cid ) ), 'cap on listings online; editing an online listing stays possible' );
smp_assert( '' === \SM\Products\Form::client_limit( Companies::get( $c_sel ) ), 'no cap for companies' );
Products::set_status( $cid, 'published' );
$card = Bridge::listing( null, array( 'id' => Products::get( $cid )->listing_id ) );
smp_assert( '' === $card['company'] && false === $card['seller_public'] && 'client' === $card['seller_class'], 'private seller: no public page link' );
smp_assert( 1 === Bridge::settings( null )['client']['active'] && 1 === Bridge::settings( null )['client']['max'], 'the form knows how many listings are online' );
Products::delete( $cid );
null === $settings_before ? delete_option( Settings::OPTION ) : update_option( Settings::OPTION, $settings_before );
wp_set_current_user( 0 );

smp_cleanup();
$t = $GLOBALS['smp_t'];
printf( "\n%d passed, %d failed\n", $t['pass'], $t['fail'] );
if ( $t['fail'] ) {
	echo "FAILED:\n  - " . implode( "\n  - ", $t['fails'] ) . "\n";
	exit( 1 );
}
