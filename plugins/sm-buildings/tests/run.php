<?php
/**
 * Tests of sm-buildings. Run on a TEST site only (creates then removes data):
 *   wp eval-file wp-content/plugins/sm-buildings/tests/run.php
 *
 * Expected weights are read from SM Weight Engine (never typed here), so the
 * tests check the arithmetic of the lot, not the reference tables.
 *
 * @package SM\Buildings
 */

use SM\Core\Accounts\Companies;
use SM\Core\Listings\Index;
use SM\Products\Categories;
use SM\Products\Markets;
use SM\Products\Products;
use SM\Products\Spec;
use SM\Products\Theme\Bridge;
use SM\Buildings\Settings;

defined( 'ABSPATH' ) || exit;

$GLOBALS['smb_t'] = array( 'pass' => 0, 'fail' => 0, 'fails' => array() );
function smb_assert( $cond, $label ) {
	$t = &$GLOBALS['smb_t'];
	if ( $cond ) {
		++$t['pass'];
		echo "  ✓ $label\n";
	} else {
		++$t['fail'];
		$t['fails'][] = $label;
		echo "  ✗ $label\n";
	}
}
function smb_near( $a, $b, $tol = 0.01 ) {
	return abs( (float) $a - (float) $b ) <= $tol;
}
function smb_user( $suffix, $class, array $activities ) {
	$uid = wp_insert_user( array( 'user_login' => 'smbtest_' . $suffix, 'user_email' => "smbtest-{$suffix}@example.test", 'user_pass' => wp_generate_password(), 'role' => 'sm_member' ) );
	$cid = Companies::create( array( 'account_class' => $class, 'name' => "SMBTEST {$suffix}", 'wilaya' => '28' ), $uid, $activities );
	Companies::raise_level( $cid, 1 );
	return array( $uid, $cid );
}
function smb_cleanup() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sm_companies WHERE name LIKE 'SMBTEST %'" ) as $cid ) {
		foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}sm_products WHERE company_id = %d", $cid ) ) as $pid ) {
			Products::delete( $pid );
		}
		foreach ( array( 'company_members', 'company_activities' ) as $t ) {
			$wpdb->delete( $wpdb->prefix . 'sm_' . $t, array( 'company_id' => $cid ) );
		}
		$wpdb->delete( $wpdb->prefix . 'sm_companies', array( 'id' => $cid ) );
	}
	foreach ( get_users( array( 'search' => 'smbtest-*', 'search_columns' => array( 'user_email' ) ) ) as $u ) {
		$wpdb->delete( $wpdb->prefix . 'sm_notifications', array( 'user_id' => $u->ID ) );
		wp_delete_user( $u->ID );
	}
}
smb_cleanup();
add_filter( 'sm_theme_demo_enabled', '__return_false', 99 );
$suffix = substr( md5( (string) microtime( true ) ), 0, 6 );
list( $u_sel, $c_sel ) = smb_user( 's' . $suffix, 'commercial', array( 'manufacturer' ) );
list( $u_fab, $c_fab ) = smb_user( 'f' . $suffix, 'commercial', array( 'fabrication' ) );
list( $u_eng, $c_eng ) = smb_user( 'e' . $suffix, 'engineering', array( 'engineer' ) );
$engine = \SM\Core\Modules::service( 'weight' );
$kgm    = static function ( $family, $section ) use ( $engine ) {
	$r = $engine->calculate( array( 'family' => $family, 'section' => $section, 'bars' => 1, 'length' => 1000 ) );
	return is_wp_error( $r ) ? 0 : (float) $r['total_kg'];
};
$attrs = array( 'system' => 'portal', 'roof' => 'duo', 'roof_cover' => 'sandwich', 'cladding' => 'ribbed', 'inc_dismantling' => '1', 'inc_transport' => '0', 'inc_reinstall' => '0', 'inc_foundations' => '1' );
$base  = array( 'wilaya' => '31', 'title' => array( 'fr' => 'Hangar test' ), 'condition' => 'good', 'attrs' => $attrs );
$asm   = array_merge( $base, array( 'category' => 'bld-industrial', 'mode' => 'assembled', 'specs' => array( 'length_m' => '40', 'width_m' => '20', 'height_m' => '8', 'portals' => '9', 'slope' => '10' ), 'total_kg' => '32000', 'price' => '9800000', 'price_unit' => 'lot' ) );
$dis_attrs = array_merge( $attrs, array( 'inc_dismantling' => '' ) );
$dis   = array_merge( $base, array( 'attrs' => $dis_attrs, 'category' => 'bld-livestock', 'mode' => 'dismantled', 'specs' => array( 'length_m' => '60', 'width_m' => '12', 'height_m' => '4,5', 'portals' => '11' ), 'price' => '170', 'price_unit' => 'kg',
	'lines' => array(
		array( 'element' => 'column', 'family' => 'HEA', 'section' => 'HEA 160', 'qty' => '22', 'length' => '4,5' ),
		array( 'element' => 'rafter', 'family' => 'IPE', 'section' => 'IPE 220', 'qty' => '22', 'length' => '6,2' ),
		array( 'element' => 'purlin', 'family' => 'IPE', 'section' => 'IPE 140', 'qty' => '50', 'length' => '6' ),
		array( 'element' => '', 'family' => 'IPE', 'section' => '', 'qty' => '', 'length' => '' ),
	) ) );

echo "\n1. Market and categories\n";
$m = Markets::get( 'buildings' );
smb_assert( $m && 'listing.buildings' === $m['cap'] && array( 'new', 'excellent', 'good', 'inspection' ) === array_keys( $m['conditions'] ), 'buildings market registered: permission and four conditions' );
smb_assert( array( 'system', 'roof', 'roof_cover', 'cladding', 'inc_dismantling', 'inc_transport', 'inc_reinstall', 'inc_foundations' ) === wp_list_pluck( $m['fields'], 'key' ), 'structural system, roof, covering, cladding and the four "included" answers' );
smb_assert( 8 === count( $m['line_elements'] ) && isset( $m['line_elements']['column'], $m['line_elements']['purlin'] ), 'eight element types for the inventory' );
$cats = Categories::all( false, 'buildings' );
smb_assert( 6 === count( $cats ) && 'building' === $cats['bld-kit']->kind && in_array( 'building', Categories::kinds(), true ), 'six building categories of the new "building" kind' );

echo "\n2. Assembled building\n";
$a = Spec::build( $asm );
smb_assert( ! $a['errors'], 'complete assembled building accepted' );
smb_assert( smb_near( $a['data']['quantity'], 800 ) && 'm2' === $a['data']['unit'] && '40 × 20 m' === $a['data']['designation'] && 'assembled' === $a['data']['variant'], 'area 40 × 20 = 800 m², designation "40 × 20 m"' );
smb_assert( smb_near( $a['data']['total_price'], 9800000 ) && smb_near( $a['data']['price_per_kg'], 306.25 ) && smb_near( $a['data']['price_per_m2'], 12250 ), 'asking price 9 800 000 DA → 306,25 DA/kg and 12 250 DA/m²' );
smb_assert( 'estimated' === $a['data']['weight_source'] && smb_near( $a['data']['total_weight'], 32000 ), 'steel weight without inventory shown as estimated' );
$x = Spec::build( array_merge( $asm, array( 'price_unit' => 'kg', 'price' => '300' ) ) );
smb_assert( isset( $x['errors']['price_unit'] ), 'an assembled building is sold at a price for the whole building' );
$x = Spec::build( array_merge( $asm, array( 'total_kg' => '' ) ) );
smb_assert( isset( $x['errors']['total_kg'] ), 'assembled without inventory needs the estimated steel weight' );
$inv = Spec::build( array_merge( $asm, array( 'total_kg' => '', 'lines' => $dis['lines'] ) ) );
$w   = 22 * 4.5 * $kgm( 'HEA', 'HEA 160' ) + 22 * 6.2 * $kgm( 'IPE', 'IPE 220' ) + 50 * 6 * $kgm( 'IPE', 'IPE 140' );
smb_assert( ! $inv['errors'] && smb_near( $inv['data']['total_weight'], $w, 0.5 ) && 'theoretical' === $inv['data']['weight_source'], 'assembled with an inventory: weight calculated from the elements (theoretical)' );
$x = Spec::build( array_merge( $asm, array( 'mode' => '', 'specs' => array( 'length_m' => '0', 'width_m' => '-2', 'height_m' => '', 'portals' => '1' ) ) ) );
smb_assert( isset( $x['errors']['mode'], $x['errors']['specs_length_m'], $x['errors']['specs_width_m'], $x['errors']['specs_height_m'], $x['errors']['specs_portals'] ), 'state, dimensions and portal frames checked' );
update_option( Settings::OPTION, array( 'max_length_m' => 30 ) );
$x = Spec::build( $asm );
smb_assert( isset( $x['errors']['specs_length_m'] ), 'limits come from the settings (here 30 m)' );
delete_option( Settings::OPTION );
$x = Spec::build( array_merge( $asm, array( 'attrs' => array( 'system' => 'portal' ) ) ) );
smb_assert( isset( $x['errors']['attrs_roof'], $x['errors']['attrs_roof_cover'], $x['errors']['attrs_cladding'] ), 'roof, covering and cladding required' );

echo "\n3. Dismantled building\n";
$d = Spec::build( $dis );
smb_assert( ! $d['errors'] && 3 === count( $d['data']['lines'] ) && 'column' === $d['data']['lines'][0]['element'], 'inventory of three element rows kept with their element type' );
smb_assert( smb_near( $d['data']['total_weight'], $w, 0.5 ) && smb_near( $d['data']['total_price'], $d['data']['total_weight'] * 170, 0.05 ), 'weights from the weight engine; total = weight × 170 DA/kg' );
smb_assert( smb_near( $d['data']['price_per_m2'], $d['data']['total_price'] / 720, 0.01 ) && 60000 === $d['data']['length_mm'], 'price per m² over 60 × 12 = 720 m²; building length kept (not the longest piece)' );
$x = Spec::build( array_merge( $dis, array( 'lines' => array() ) ) );
smb_assert( isset( $x['errors']['lines'] ), 'a dismantled building needs its inventory' );
$x = Spec::build( array_merge( $dis, array( 'lines' => array( array( 'element' => '', 'family' => 'IPE', 'section' => 'IPE 200', 'qty' => '4', 'length' => '6' ) ) ) ) );
smb_assert( isset( $x['errors']['lines_0'] ), 'each row says which element it is' );
$x = Spec::build( array_merge( $dis, array( 'attrs' => $attrs ) ) );
smb_assert( isset( $x['errors']['attrs_inc_dismantling'] ), '"dismantling included" refused for a dismantled building' );
$lot = Spec::build( array_merge( $dis, array( 'price_unit' => 'lot', 'price' => '1200000' ) ) );
smb_assert( ! $lot['errors'] && smb_near( $lot['data']['total_price'], 1200000 ), 'a dismantled building may also be priced as a whole' );

echo "\n4. Saving, search and product page\n";
$pa = Products::save( 0, $c_sel, $u_sel, $a, 'published' );
$pd = Products::save( 0, $c_sel, $u_sel, $d, 'published' );
$la = Products::get( $pa )->listing_id;
$ld = Products::get( $pd )->listing_id;
smb_assert( 'buildings' === Index::get( $la )->module && 'assembled' === Products::get( $pa )->variant, 'saved in the buildings market with its state' );
$find = static fn( $lid, $args ) => in_array( $lid, wp_list_pluck( (array) ( Bridge::listings( null, $args + array( 'per_page' => 60 ) )['items'] ?? array() ), 'id' ), true );
smb_assert( $find( $la, array( 'market' => 'buildings', 'mode' => 'assembled' ) ) && ! $find( $ld, array( 'market' => 'buildings', 'mode' => 'assembled' ) ) && $find( $ld, array( 'market' => 'buildings', 'mode' => 'dismantled' ) ), 'filter assembled / dismantled' );
smb_assert( $find( $la, array( 'market' => array( 'products', 'reuse', 'buildings' ) ) ), 'found by the general search across the three markets' );
smb_assert( ! $find( $la, array( 'market' => 'products' ) ), 'not in the new products market' );
wp_set_current_user( $u_eng );
$item = Bridge::listing( null, array( 'id' => $la ) );
smb_assert( 40.0 === $item['length'] && 800.0 === $item['area'] && 9 === $item['portals'] && 'Portiques à âme pleine' === $item['system']['fr'], 'card data: dimensions, area, portal frames, structural system' );
smb_assert( $item['includes']['dismantling'] && ! $item['includes']['transport'] && $item['includes']['foundations'] && smb_near( $item['price_per_m2'], 12250 ) && 'assembled' === $item['mode'], 'what the price includes, price per m², state' );
smb_assert( false !== strpos( $item['drawing'], '<svg' ) && false !== strpos( $item['drawing'], '40 m' ) && 9 === $item['configurator']['portals'] && 'duo' === $item['configurator']['roof'], 'indicative drawing and shape data for the future configurator' );
smb_assert( ! empty( $item['drawing_note'] ) && ! empty( $item['advice']['text'] ) && 'engineers' === $item['advice']['link_page'], 'drawing marked as indicative; engineer advice for a used building' );
$labels = wp_list_pluck( $item['spec_rows'], 0 );
smb_assert( count( $item['spec_rows'] ) >= 9 && ! array_filter( $labels, static fn( $l ) => false !== stripos( $l, 'inclus' ) || false !== stripos( $l, 'included' ) ), 'specification rows (dimensions, system, roof…) without the "included" answers' );
$di = Bridge::listing( null, array( 'id' => $ld ) );
smb_assert( 3 === count( $di['elements'] ) && 'Poteaux' === $di['elements'][0]['type']['fr'] && 'HEA 160' === $di['elements'][0]['designation'], 'inventory table: element type, profile, quantity, length, weights' );
wp_set_current_user( $u_sel );
$form = Bridge::product_form( null, array( 'id' => $pd ) );
smb_assert( 'dismantled' === $form['values']['mode'] && 3 === count( $form['values']['lines'] ) && 'rafter' === $form['values']['lines'][1]['element'] && '4.5' === (string) $form['values']['specs']['height_m'], 'edit form gets back state, dimensions and inventory' );

echo "\n5. Rules\n";
smb_assert( Bridge::product_markets( null )['buildings']['allowed'], 'a manufacturer may publish buildings' );
wp_set_current_user( $u_fab );
smb_assert( ! sm_can( 'listing.buildings' ), 'a fabrication-only workshop cannot (default rights)' );
wp_set_current_user( $u_eng );
smb_assert( ! sm_can( 'listing.buildings' ) && ! Bridge::product_markets( null )['buildings']['allowed'], 'an engineering account never publishes buildings' );
wp_set_current_user( 0 );

smb_cleanup();
remove_filter( 'sm_theme_demo_enabled', '__return_false', 99 );
$t = $GLOBALS['smb_t'];
printf( "\n%d passed, %d failed\n", $t['pass'], $t['fail'] );
if ( $t['fail'] ) {
	echo "FAILED:\n  - " . implode( "\n  - ", $t['fails'] ) . "\n";
	exit( 1 );
}
