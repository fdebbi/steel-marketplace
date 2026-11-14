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
list( $u_sel, $c_sel ) = smb_user( 's' . $suffix, 'company', array( 'manufacturer' ) );
list( $u_fab, $c_fab ) = smb_user( 'f' . $suffix, 'company', array( 'manufacturer' ) );
list( $u_eng, $c_eng ) = smb_user( 'e' . $suffix, 'engineer', array( 'engineer' ) );
$engine = \SM\Core\Modules::service( 'weight' );
$kgm    = static function ( $family, $section ) use ( $engine ) {
	$r = $engine->calculate( array( 'family' => $family, 'section' => $section, 'bars' => 1, 'length' => 1000 ) );
	return is_wp_error( $r ) ? 0 : (float) $r['total_kg'];
};
$attrs = array( 'system' => 'portal', 'roof' => 'duo', 'roof_cover' => 'sandwich', 'cladding' => 'ribbed', 'inc_dismantling' => '1', 'inc_transport' => '0', 'inc_reinstall' => '0', 'inc_foundations' => '1' );
$base  = array( 'wilaya' => '31', 'commune' => 'Es Sénia', 'address' => 'Zone industrielle', 'lat' => '35.6477', 'lng' => '-0.6240', 'title' => array( 'fr' => 'Titre tapé, ignoré' ), 'condition' => 'good', 'attrs' => $attrs );
$frame = array(
	array( 'element' => 'column', 'family' => 'HEA', 'section' => 'HEA 300', 'qty' => '10', 'length' => '6' ),
	array( 'element' => 'rafter', 'family' => 'IPE', 'section' => 'IPE 360', 'qty' => '10', 'length' => '10,2' ),
);
$asm   = array_merge( $base, array( 'category' => 'bld-industrial', 'mode' => 'assembled', 'specs' => array( 'length_m' => '40', 'width_m' => '20', 'height_m' => '8', 'ridge_m' => '9', 'portals' => '9' ), 'lines' => $frame, 'total_kg' => '32000', 'price' => '9800000', 'price_unit' => 'lot' ) );
$dis_attrs = array_merge( $attrs, array( 'inc_dismantling' => '' ) );
$dis   = array_merge( $base, array( 'attrs' => $dis_attrs, 'category' => 'bld-livestock', 'mode' => 'dismantled', 'specs' => array( 'length_m' => '60', 'width_m' => '12', 'height_m' => '4,5', 'ridge_m' => '6', 'portals' => '11' ), 'price' => '170', 'price_unit' => 'kg',
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
smb_assert( 9 === count( $m['line_elements'] ) && isset( $m['line_elements']['column'], $m['line_elements']['purlin'], $m['line_elements']['truss'] ), 'nine element types for the inventory (trusses added in 0.2.0)' );
$cats = Categories::all( false, 'buildings' );
smb_assert( 6 === count( $cats ) && 'building' === $cats['bld-kit']->kind && in_array( 'building', Categories::kinds(), true ), 'six building categories of the new "building" kind' );

echo "\n2. Assembled building\n";
$a = Spec::build( $asm );
smb_assert( ! $a['errors'], 'complete assembled building accepted' );
smb_assert( smb_near( $a['data']['quantity'], 800 ) && 'm2' === $a['data']['unit'] && '40 × 20 m' === $a['data']['designation'] && 'assembled' === $a['data']['variant'], 'area 40 × 20 = 800 m², designation "40 × 20 m"' );
smb_assert( smb_near( $a['data']['total_price'], 9800000 ) && smb_near( $a['data']['price_per_kg'], 306.25 ) && smb_near( $a['data']['price_per_m2'], 12250 ), 'asking price 9 800 000 DA → 306,25 DA/kg and 12 250 DA/m²' );
$wf = 10 * 6 * $kgm( 'HEA', 'HEA 300' ) + 10 * 10.2 * $kgm( 'IPE', 'IPE 360' );
smb_assert( 'estimated' === $a['data']['weight_source'] && smb_near( $a['data']['total_weight'], 32000 ) && smb_near( $a['data']['specs']['elements_kg'], $wf, 0.5 ), 'total weight given by the seller (estimated); declared elements kept beside it' );
$x = Spec::build( array_merge( $asm, array( 'price_unit' => 'kg', 'price' => '300' ) ) );
smb_assert( ! $x['errors'] && 'lot' === $x['data']['sale_mode'] && 'lot' === $x['data']['price_unit'] && smb_near( $x['data']['total_price'], 300 * 32000 ) && smb_near( $x['data']['price_per_kg'], 300 ), '0.4.0: a price typed per kg gives the price of the whole building (300 DA/kg × 32 000 kg), sold as a whole' );
$inv = Spec::build( array_merge( $asm, array( 'total_kg' => '', 'lines' => $dis['lines'] ) ) );
$w   = 22 * 4.5 * $kgm( 'HEA', 'HEA 160' ) + 22 * 6.2 * $kgm( 'IPE', 'IPE 220' ) + 50 * 6 * $kgm( 'IPE', 'IPE 140' );
smb_assert( ! $inv['errors'] && smb_near( $inv['data']['total_weight'], $w, 0.5 ) && 'partial' === $inv['data']['weight_source'], 'without a total: weight of the declared elements, marked "partial"' );
$x = Spec::build( array_merge( $asm, array( 'mode' => '', 'specs' => array( 'length_m' => '0', 'width_m' => '-2', 'height_m' => '', 'ridge_m' => '', 'portals' => '1' ) ) ) );
smb_assert( isset( $x['errors']['mode'], $x['errors']['specs_length_m'], $x['errors']['specs_width_m'], $x['errors']['specs_height_m'], $x['errors']['specs_ridge_m'], $x['errors']['specs_portals'] ), 'state, dimensions, roof height and portal frames checked' );
update_option( Settings::OPTION, array( 'max_length_m' => 30 ) );
$x = Spec::build( $asm );
smb_assert( isset( $x['errors']['specs_length_m'] ), 'limits come from the settings (here 30 m)' );
delete_option( Settings::OPTION );
$x = Spec::build( array_merge( $asm, array( 'attrs' => array( 'system' => 'portal' ) ) ) );
smb_assert( isset( $x['errors']['attrs_roof'], $x['errors']['attrs_roof_cover'], $x['errors']['attrs_cladding'] ), 'roof, covering and cladding required' );

echo "\n3. Dismantled building\n";
$d = Spec::build( $dis );
smb_assert( ! $d['errors'] && 3 === count( $d['data']['lines'] ) && 'column' === $d['data']['lines'][0]['element'], 'inventory of three element rows kept with their element type' );
smb_assert( smb_near( $d['data']['total_weight'], $w, 0.5 ) && smb_near( $d['data']['total_price'], floor( $d['data']['total_weight'] * 170 ) ) && 'lot' === $d['data']['sale_mode'], 'weights from the weight engine; whole-building price = weight × 170 DA/kg, rounded down to the dinar' );
smb_assert( smb_near( $d['data']['price_per_m2'], $d['data']['total_price'] / 720, 0.01 ) && 60000 === $d['data']['length_mm'], 'price per m² over 60 × 12 = 720 m²; building length kept (not the longest piece)' );
$x = Spec::build( array_merge( $dis, array( 'lines' => array() ) ) );
smb_assert( isset( $x['errors']['lines'] ), 'columns and rafters are required (dismantled too)' );
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
smb_assert( 40.0 === $item['length'] && 800.0 === $item['area'] && 9 === $item['portals'] && 'Portique poteau / traverse' === $item['system']['fr'], 'card data: dimensions, area, portal frames, structural system' );
smb_assert( $item['includes']['dismantling'] && ! $item['includes']['transport'] && $item['includes']['foundations'] && smb_near( $item['price_per_m2'], 12250 ) && 'assembled' === $item['mode'], 'what the price includes, price per m², state' );
smb_assert( false !== strpos( $item['drawing'], '<svg' ) && false !== strpos( $item['drawing'], '40 m' ) && 9 === $item['configurator']['portals'] && 'duo' === $item['configurator']['roof'], 'indicative drawing and shape data for the future configurator' );
smb_assert( ! empty( $item['drawing_note'] ) && empty( $item['advice'] ) && ! empty( $item['building']['inspection']['text'] ) && 'expertise' === $item['building']['inspection']['type'] && false === strpos( $item['building']['inspection']['text'], 'certif' ), 'drawing marked as indicative; inspection request offered for a used building (0.4.0)' );
$labels = wp_list_pluck( $item['spec_rows'], 0 );
smb_assert( count( $item['spec_rows'] ) >= 9 && ! array_filter( $labels, static fn( $l ) => false !== stripos( $l, 'inclus' ) || false !== stripos( $l, 'included' ) ), 'specification rows (dimensions, system, roof…) without the "included" answers' );
$di = Bridge::listing( null, array( 'id' => $ld ) );
smb_assert( 3 === count( $di['elements'] ) && 'Poteaux' === $di['elements'][0]['type']['fr'] && 'HEA 160' === $di['elements'][0]['designation'], 'inventory table: element type, profile, quantity, length, weights' );
wp_set_current_user( $u_sel );
$form = Bridge::product_form( null, array( 'id' => $pd ) );
$fa = Bridge::product_form( null, array( 'id' => $pa ) );
smb_assert( '32000' === $fa['values']['total_kg'] && '' === Bridge::product_form( null, array( 'id' => $pd ) )['values']['total_kg'] && 'Es Sénia' === $fa['values']['commune'] && '35.6477' === $fa['values']['lat'], 'edit form: total weight only when the seller gave one; commune and position back' );
smb_assert( 'dismantled' === $form['values']['mode'] && 3 === count( $form['values']['lines'] ) && 'rafter' === $form['values']['lines'][1]['element'] && '4.5' === (string) $form['values']['specs']['height_m'], 'edit form gets back state, dimensions and inventory' );

echo "\n5. Geometry, required elements, title (0.2.0)\n";
smb_assert( 9.0 === (float) $a['data']['specs']['ridge_m'] && smb_near( $a['data']['specs']['slope'], 10 ) && smb_near( $a['data']['specs']['bay_m'], 5 ) && 1 === $a['data']['specs']['floors'], 'ridge 9 m over eaves 8 m, span 20 m → slope 10 %; frame spacing 40 / 8 = 5 m; one floor by default' );
smb_assert( smb_near( \SM\Buildings\Spec::slope( 20, 6, 8, 'duo' ), 20 ) && smb_near( \SM\Buildings\Spec::slope( 20, 6, 8, 'mono' ), 10 ) && 0.0 === \SM\Buildings\Spec::slope( 20, 6, 8, 'multi' ) && 0.0 === \SM\Buildings\Spec::slope( 20, 6, 6, 'duo' ), 'slope: two pitches over half the span, one pitch over the span, none for multi-span or a flat roof' );
$x = Spec::build( array_merge( $asm, array( 'specs' => array_merge( $asm['specs'], array( 'ridge_m' => '7' ) ) ) ) );
smb_assert( isset( $x['errors']['specs_ridge_m'] ), 'roof height lower than the column height refused' );
$x = Spec::build( array_merge( $asm, array( 'specs' => array_merge( $asm['specs'], array( 'floors' => '2', 'mezzanine' => '1', 'mezz_area' => '120' ) ) ) ) );
smb_assert( ! $x['errors'] && 2 === $x['data']['specs']['floors'] && 1 === $x['data']['specs']['mezzanine'] && 120.0 === (float) $x['data']['specs']['mezz_area'], 'floors and mezzanine with its area kept' );
$x = Spec::build( array_merge( $asm, array( 'specs' => array_merge( $asm['specs'], array( 'floors' => '0', 'mezzanine' => '1', 'mezz_area' => '900' ) ) ) ) );
smb_assert( isset( $x['errors']['specs_floors'], $x['errors']['specs_mezz_area'] ), 'floors from 1; mezzanine not larger than the floor area' );
$x = Spec::build( array_merge( $asm, array( 'lines' => array( $frame[0] ) ) ) );
smb_assert( isset( $x['errors']['lines'] ), 'column / rafter frame: columns alone are not enough' );
$tr = array_merge( $attrs, array( 'system' => 'truss' ) );
$x  = Spec::build( array_merge( $asm, array( 'attrs' => $tr ) ) );
smb_assert( isset( $x['errors']['lines'] ), 'truss system: rafters do not replace the trusses' );
$x  = Spec::build( array_merge( $asm, array( 'attrs' => $tr, 'lines' => array( $frame[0], array_merge( $frame[1], array( 'element' => 'truss' ) ) ) ) ) );
smb_assert( ! $x['errors'], 'truss system accepted with columns and trusses' );
$prs = array_merge( $attrs, array( 'system' => 'tapered' ) );
$x   = Spec::build( array_merge( $asm, array( 'attrs' => $prs, 'lines' => array() ) ) );
smb_assert( ! $x['errors'] && 'estimated' === $x['data']['weight_source'] && 0.0 === (float) $x['data']['specs']['elements_kg'], 'PRS structure: no catalogue element required, the total weight is enough' );
$x   = Spec::build( array_merge( $asm, array( 'attrs' => $prs, 'lines' => array(), 'total_kg' => '' ) ) );
smb_assert( isset( $x['errors']['total_kg'] ) && ! isset( $x['errors']['lines'] ), 'PRS structure without elements: the total weight is required' );
$x = Spec::build( array_merge( $asm, array( 'total_kg' => '5000' ) ) );
smb_assert( isset( $x['errors']['total_kg'] ), 'a total weight lower than the declared elements is refused' );
smb_assert( 'Hangars et bâtiments industriels 20 × 40 m — hauteur 8 m — Portique poteau / traverse — Oran' === $a['title']['fr'] && false !== strpos( $a['title']['ar'], 'وهران' ) && false !== strpos( $a['title']['ar'], 'الارتفاع 8 م' ), 'title generated in French and Arabic; the typed title is ignored' );
$x = Spec::build( array_merge( $asm, array( 'lat' => '', 'lng' => '', 'commune' => '' ) ) );
smb_assert( isset( $x['errors']['location'], $x['errors']['commune'] ), 'a building needs its commune and its position on the map' );
$x = Spec::build( array_merge( $asm, array( 'lat' => '48.85', 'lng' => '2.35' ) ) );
smb_assert( isset( $x['errors']['location'] ), 'position outside Algeria refused' );

echo "\n6. Place and visit requests (sm-products 0.12.0)\n";
$V   = '\\SM\\Products\\Visits';
$pp  = Products::get( $pa );
wp_set_current_user( 0 );
$g = Bridge::listing( null, array( 'id' => $la, 'no_count' => 1 ) );
smb_assert( null === $g['location'] && 'Es Sénia' === $g['commune'] && 'Zone industrielle' === $g['address'] && $g['visit']['eligible'] && $g['visit']['guest'] && $g['visit']['has_position'], 'visitor: commune and address, no position, visit offered after sign-in' );
wp_set_current_user( $u_sel );
$o = Bridge::listing( null, array( 'id' => $la, 'no_count' => 1 ) );
smb_assert( $o['location'] && smb_near( $o['location']['lat'], 35.6477, 0.0001 ) && $o['visit']['own'], 'the seller sees his own position' );
$when = wp_date( 'Y-m-d', time() + 5 * DAY_IN_SECONDS ) . ' 10:00:00';
smb_assert( is_wp_error( $V::request( $pp, Companies::get( $c_sel ), $u_sel, $when, '' ) ), 'no visit of one\'s own listing' );
smb_assert( '' === $V::parse_when( '2026-02-30', '10:00' ) && '' === $V::parse_when( '2026-12-01', '25:00' ) && is_wp_error( $V::request( $pp, Companies::get( $c_fab ), $u_fab, wp_date( 'Y-m-d H:i:s', time() - 3600 ), '' ) ) && is_wp_error( $V::request( $pp, Companies::get( $c_fab ), $u_fab, wp_date( 'Y-m-d H:i:s', time() + 200 * DAY_IN_SECONDS ), '' ) ), 'dates checked: real date and time, in the future, within three months' );
$vid = $V::request( $pp, Companies::get( $c_fab ), $u_fab, $when, 'Deux personnes' );
smb_assert( is_int( $vid ) && 1 === $V::count_new( $c_sel ) && is_wp_error( $V::request( $pp, Companies::get( $c_fab ), $u_fab, $when, '' ) ), 'request saved, counted for the seller; no second open request for the same listing' );
smb_assert( null === $V::location_for( $pp, $u_fab ), 'a pending request does not show the position' );
$new_when = wp_date( 'Y-m-d', time() + 7 * DAY_IN_SECONDS ) . ' 15:30:00';
smb_assert( true === $V::reply( $V::get( $vid ), 'propose', $new_when, 'Matin impossible' ) && 'proposed' === $V::get( $vid )->status && null === $V::location_for( $pp, $u_fab ), 'the seller proposes another date: still no position' );
smb_assert( true === $V::answer( $V::get( $vid ), 'accept' ) && 'accepted' === $V::get( $vid )->status && $new_when === $V::get( $vid )->visit_at, 'the buyer confirms: accepted at the proposed date' );
smb_assert( null !== $V::location_for( $pp, $u_fab ) && null === $V::location_for( $pp, $u_eng ) && null === $V::location_for( $pp, 0 ), 'position shown to the accepted buyer only' );
global $wpdb;
$wpdb->update( $V::table(), array( 'visit_at' => wp_date( 'Y-m-d H:i:s', time() - 5 * DAY_IN_SECONDS ) ), array( 'id' => $vid ) );
smb_assert( null !== $V::location_for( $pp, $u_fab ), 'sm-products 0.12.1: the accepted buyer keeps the position after the visit, with no time limit' );
$wpdb->update( $V::table(), array( 'visit_at' => $new_when ), array( 'id' => $vid ) );
smb_assert( true === $V::reply( $V::get( $vid ), 'refuse' ) && null === $V::location_for( $pp, $u_fab ) && is_wp_error( $V::answer( $V::get( $vid ), 'accept' ) ), 'the seller cancels an accepted visit: position hidden, request closed' );
$v2 = $V::request( $pp, Companies::get( $c_fab ), $u_fab, $when, '' );
smb_assert( is_int( $v2 ) && true === $V::reply( $V::get( $v2 ), 'accept' ) && true === $V::answer( $V::get( $v2 ), 'cancel' ) && 'cancelled' === $V::get( $v2 )->status, 'a new request after a refusal; accepted directly; cancelled by the buyer' );
$types = wp_list_pluck( $wpdb->get_results( $wpdb->prepare( "SELECT type FROM {$wpdb->prefix}sm_notifications WHERE user_id IN (%d, %d)", $u_sel, $u_fab ) ), 'type' );
smb_assert( ! array_diff( array( 'visit.requested', 'visit.proposed', 'visit.confirmed', 'visit.refused', 'visit.accepted', 'visit.cancelled' ), $types ), 'each step notified to the other side' );
$wpdb->update( Products::table(), array( 'map_public' => 1 ), array( 'id' => $pa ) );
smb_assert( null !== $V::location_for( Products::get( $pa ), 0 ), 'map made public by the seller: position for everyone' );
$wpdb->update( Products::table(), array( 'status' => 'sold' ), array( 'id' => $pa ) );
smb_assert( ! $V::eligible( Products::get( $pa ) ), 'no visit of a listing that is no longer online' );
Products::delete( $pa );
smb_assert( 0 === (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $V::table() . ' WHERE product_id = %d', $pa ) ), 'visit requests removed with the listing' );

echo "\n7. Rules\n";
smb_assert( Bridge::product_markets( null )['buildings']['allowed'], 'a manufacturer may publish buildings' );
wp_set_current_user( $u_fab );
smb_assert( sm_can( 'listing.buildings' ), 'every company type may sell a building (new model)' );
wp_set_current_user( $u_eng );
smb_assert( ! sm_can( 'listing.buildings' ) && ! Bridge::product_markets( null )['buildings']['allowed'], 'a partner engineer account never publishes buildings' );
wp_set_current_user( 0 );

// ---- 0.3.0: overhead crane, elements proposed from the dimensions ----
echo "\nCrane and proposed elements\n";
use SM\Buildings\Proposal;
$w    = \SM\Core\Modules::service( 'weight' );
$name = static function ( $id ) use ( $w ) {
	$sec = $w->section( (int) $id );
	return $sec ? $sec['designation'] : '';
};
$x = Spec::build( array_merge( $asm, array( 'specs' => $asm['specs'] + array( 'crane' => '1', 'crane_t' => '10' ) ) ) );
smb_assert( ! $x['errors'] && 1 === $x['data']['specs']['crane'] && 10.0 === (float) $x['data']['specs']['crane_t'], 'overhead crane kept with its capacity' );
$x = Spec::build( array_merge( $asm, array( 'specs' => $asm['specs'] + array( 'crane' => '1' ) ) ) );
smb_assert( ! $x['errors'] && 1 === $x['data']['specs']['crane'] && ! isset( $x['data']['specs']['crane_t'] ), 'capacity of the crane is optional' );
$x = Spec::build( array_merge( $asm, array( 'specs' => $asm['specs'] + array( 'crane' => '1', 'crane_t' => '9000' ) ) ) );
smb_assert( isset( $x['errors']['specs_crane_t'] ), 'absurd crane capacity refused' );

$pst  = array(
	'specs' => array( 'length_m' => '40', 'width_m' => '20', 'height_m' => '6', 'ridge_m' => '8', 'portals' => '5' ),
	'attrs' => array( 'system' => 'portal', 'roof' => 'duo', 'roof_cover' => 'sandwich', 'cladding' => 'sandwich' ),
);
$rows = Proposal::rows( $pst );
$by   = array();
foreach ( (array) $rows as $r ) {
	$by[ $r['element'] ] = $r;
}
smb_assert( 5 === count( (array) $rows ) && array( 'column', 'rafter', 'purlin', 'rail', 'bracing' ) === array_column( $rows, 'element' ), 'frame 20 × 40 m: columns, rafters, purlins, rails, bracing proposed' );
smb_assert( 'IPE 400' === $name( $by['column']['section'] ) && '10' === $by['column']['qty'] && '6' === $by['column']['length'], 'columns: IPE 400 (20 × 20), 2 per frame, as long as the column height' );
smb_assert( 'IPE 400' === $name( $by['rafter']['section'] ) && '10' === $by['rafter']['qty'] && '10' === $by['rafter']['length'], 'rafters, two slopes: IPE 400, 2 per frame, half the width' );
smb_assert( 'IPE 120' === $name( $by['purlin']['section'] ) && '68' === $by['purlin']['qty'] && '10' === $by['purlin']['length'], 'purlins: IPE 120, ceil(20 / 1.2) = 17 rows × 4 bays, as long as the frame spacing' );
smb_assert( 'UPN 120' === $name( $by['rail']['section'] ) && '40' === $by['rail']['qty'] && '10' === $by['rail']['length'], 'rails: UPN 120, 2 × ceil(6 / 1.2) × 4 bays' );
smb_assert( 'L 80x80x8' === $name( $by['bracing']['section'] ) && '32' === $by['bracing']['qty'] && '6' === $by['bracing']['length'], 'bracing: 32 angles L 80x80x8 of 6 m' );
$c = Proposal::rows( array_replace_recursive( $pst, array( 'specs' => array( 'crane' => '1' ) ) ) );
smb_assert( 'HEA' === $c[0]['family'] && 'HEA 360' === $name( $c[0]['section'] ), 'with a crane: columns HEA 360 (20 × 18)' );
$c = Proposal::rows( array_replace_recursive( $pst, array( 'specs' => array( 'width_m' => '22' ) ) ) );
smb_assert( 'IPE 450' === $name( $c[0]['section'] ), 'nearest catalogue size: 22 m → 440 → IPE 450' );
$c = Proposal::rows( array_replace_recursive( $pst, array( 'specs' => array( 'width_m' => '36' ) ) ) );
smb_assert( '' === $c[0]['section'] && '10' === $c[0]['qty'], 'beyond the largest size the dimension is left to the seller' );
$c = Proposal::rows( array_replace_recursive( $pst, array( 'attrs' => array( 'roof' => 'mono' ) ) ) );
smb_assert( '5' === $c[1]['qty'] && '20' === $c[1]['length'], 'one slope: 1 rafter per frame, as long as the width' );
$c = Proposal::rows( array_replace_recursive( $pst, array( 'attrs' => array( 'roof' => 'curved', 'cladding' => 'none' ) ) ) );
smb_assert( array( 'column', 'purlin', 'bracing' ) === array_column( $c, 'element' ), 'curved roof: no rafters; no cladding: no rails' );
$c = Proposal::rows( array_replace_recursive( $pst, array( 'specs' => array( 'length_m' => '50', 'portals' => '4' ) ) ) );
smb_assert( '16.67' === $c[2]['length'], 'purlin length = length / (frames − 1), two decimals' );
foreach ( array( 'tapered', 'truss', 'other' ) as $sys ) {
	smb_assert( null === Proposal::rows( array_replace_recursive( $pst, array( 'attrs' => array( 'system' => $sys ) ) ) ), "nothing proposed for the system $sys" );
}
smb_assert( null === Proposal::rows( array_replace_recursive( $pst, array( 'specs' => array( 'portals' => '' ) ) ) ) && null === Proposal::rows( array_replace_recursive( $pst, array( 'attrs' => array( 'cladding' => '' ) ) ) ), 'nothing proposed while the dimensions or the envelope are missing' );

$bcat = Categories::get( 'bld-industrial' );
$s1   = Proposal::state( $pst, $bcat );
smb_assert( 'synced' === $s1['lines_proposal'] && 5 === count( $s1['lines'] ) && Proposal::signature( $s1['lines'] ) === $s1['lines_auto'], 'empty inventory: the proposal is placed and signed' );
$s2 = Proposal::state( array_replace_recursive( $s1, array( 'specs' => array( 'portals' => '6' ) ) ), $bcat );
smb_assert( 'synced' === $s2['lines_proposal'] && '12' === $s2['lines'][0]['qty'], 'untouched proposal follows the dimensions (6 frames → 12 columns)' );
$edit                    = $s2;
$edit['lines'][0]['qty'] = '14';
$s3 = Proposal::state( array_replace_recursive( $edit, array( 'specs' => array( 'portals' => '7' ) ) ), $bcat );
smb_assert( 'available' === $s3['lines_proposal'] && '14' === $s3['lines'][0]['qty'], 'a row changed by the seller is never overwritten' );
$s4 = Proposal::state( $s3 + array( 'lines_recalc' => '1' ), $bcat );
smb_assert( 'synced' === $s4['lines_proposal'] && '14' === $s4['lines'][0]['qty'] && '7' === $s4['specs']['portals'], 'on request the rows are replaced by a new proposal (7 frames → 14 columns)' );
$gone          = $s4;
$gone['lines'] = array();
$s5 = Proposal::state( $gone, $bcat );
smb_assert( ! $s5['lines'] && 'available' === $s5['lines_proposal'], 'rows removed by the seller are not proposed again on their own' );
$s6 = Proposal::state( array_replace_recursive( $s1, array( 'attrs' => array( 'system' => 'tapered' ) ) ), $bcat );
smb_assert( ! $s6['lines'] && '' === $s6['lines_auto'], 'untouched proposal removed when the system becomes PRS' );
$typed = array_merge( $pst, array( 'lines' => array( array( 'element' => 'column', 'family' => 'HEA', 'section' => $by['column']['section'], 'qty' => '4', 'length' => '5' ) ) ) );
$s7    = Proposal::state( $typed, $bcat );
smb_assert( 1 === count( $s7['lines'] ) && Proposal::MANUAL === $s7['lines_auto'] && 'available' === $s7['lines_proposal'], 'rows typed before any proposal are kept' );
$full = Spec::build( array_merge( $asm, $pst, array( 'lines' => $s1['lines'], 'total_kg' => '' ) ) );
smb_assert( ! $full['errors'] && 'partial' === $full['data']['weight_source'] && smb_near( $full['data']['total_weight'], 24900, 150 ), 'the proposed rows are valid and weighed (' . round( $full['data']['total_weight'] ) . ' kg)' );

echo "\nWhole building and / or by element (0.4.0)\n";
$Sale = '\\SM\\Buildings\\Sale';
list( $pk, $pt ) = $Sale::prices( 'kg', 180, 0, 31124.66 );
list( $qk, $qt ) = $Sale::prices( 'total', 0, 5000000, 31124.66 );
smb_assert( 180.0 === $pk && 5602438.0 === $pt && 160.65 === $qk && 5000000.0 === $qt, 'linked prices: 180 DA/kg → 5 602 438 DA (rounded down); 5 000 000 DA → 160,65 DA/kg (rounded up)' );
$sell = static fn( $lot, $el, $more = array() ) => Spec::build( array_merge( $dis, array( 'sell_set' => '1', 'sell_lot' => $lot ? '1' : '', 'sell_elements' => $el ? '1' : '', 'price_basis' => 'kg' ), $more ) );
$both = $sell( true, true );
smb_assert( ! $both['errors'] && 'both' === $both['data']['sale_mode'] && 'kg' === $both['data']['price_unit'] && smb_near( $both['data']['price'], 170 ) && smb_near( $both['data']['lot_price'], floor( 170 * $both['data']['total_weight'] ) ), 'both boxes ticked: sold whole (weight × 170, rounded down) and by element at 170 DA/kg' );
$bt = $sell( true, true, array( 'price_basis' => 'total', 'price' => '', 'lot_price' => '1 000 000' ) );
smb_assert( ! $bt['errors'] && smb_near( $bt['data']['lot_price'], 1000000 ) && smb_near( $bt['data']['price'], ceil( 1000000 / $bt['data']['total_weight'] * 100 ) / 100 ) && 'total' === $bt['data']['specs']['price_basis'], 'total typed: the price per kg is calculated from it' );
$only = $sell( false, true );
smb_assert( ! $only['errors'] && 'detail' === $only['data']['sale_mode'] && 0.0 === (float) $only['data']['lot_price'] && smb_near( $only['data']['price'], 170 ), 'by element only: no whole-building price' );
$lotonly = $sell( true, false );
smb_assert( ! $lotonly['errors'] && 'lot' === $lotonly['data']['sale_mode'] && 'lot' === $lotonly['data']['price_unit'] && smb_near( $lotonly['data']['price_per_kg'], 170, 0.01 ), 'whole building only (default): total as the price, 170 DA/kg as its equivalent' );
$none = $sell( false, false );
smb_assert( isset( $none['errors']['sale_mode'] ), 'no box ticked: refused' );
$noinv = Spec::build( array_merge( $asm, array( 'attrs' => array_merge( $attrs, array( 'system' => 'other' ) ), 'lines' => array(), 'sell_set' => '1', 'sell_elements' => '1', 'sell_lot' => '1', 'price_basis' => 'total', 'price' => '', 'lot_price' => '9800000', 'price_unit' => '' ) ) );
smb_assert( isset( $noinv['errors']['sale_mode'] ), 'by element without an inventory: refused, with the reason' );

$pb    = Products::save( 0, $c_sel, $u_sel, $both, 'published' );
$opts  = \SM\Products\Stock::options( Products::get( $pb ) );
$lines = (array) ( $opts['detail']['lines'] ?? array() );
$kcol  = $lines[0]['key'] ?? '';
$kpur  = $lines[2]['key'] ?? '';
smb_assert( $opts['lot'] && smb_near( $opts['lot']['total'], $both['data']['lot_price'] ) && 3 === count( $lines ) && 22 === $lines[0]['qty'] && 'Poteaux' === $lines[0]['label']['fr'] && smb_near( $lines[0]['piece_kg'], 4.5 * $kgm( 'HEA', 'HEA 160' ), 0.01 ) && smb_near( $opts['detail']['max'], $both['data']['total_weight'], 0.5 ), 'what can be bought: the whole building, or pieces of the three inventory rows' );
$kg_want = 4 * 4.5 * $kgm( 'HEA', 'HEA 160' ) + 10 * 6 * $kgm( 'IPE', 'IPE 140' );
$chk     = \SM\Products\Stock::check( Products::get( $pb ), 'detail', 0, array( $kcol => 4, $kpur => 10 ) );
smb_assert( ! is_wp_error( $chk ) && smb_near( $chk[0], $kg_want, 0.05 ) && 'kg' === $chk[1] && smb_near( $chk[2], $kg_want * 170, 10 ) && 2 === count( $chk[3] ), '4 columns + 10 purlins: weight from the section tables, total at 170 DA/kg' );
$e1 = \SM\Products\Stock::check( Products::get( $pb ), 'detail', 0, array( $kcol => 23 ) );
$e2 = \SM\Products\Stock::check( Products::get( $pb ), 'detail', 500, array() );
smb_assert( is_wp_error( $e1 ) && 'items' === $e1->get_error_code() && is_wp_error( $e2 ) && 'items' === $e2->get_error_code(), 'more pieces than listed, or a quantity in kg without pieces: refused' );
if ( class_exists( '\\SM\\Trade\\Deals' ) ) {
	$who  = array( 'name' => 'Acheteur Test', 'phone' => '0550 00 00 00', 'email' => 'smbtest-buyer@example.test' );
	$deal = \SM\Trade\Deals::create( $pb, $u_fab, $who + array( 'kind' => 'offer', 'mode' => 'detail', 'items' => array( $kcol => 4, $kpur => 10 ), 'amount_kg' => 150 ) );
	$dd   = is_wp_error( $deal ) ? null : \SM\Trade\Deals::get( $deal );
	smb_assert( $dd && smb_near( $dd->quantity, $kg_want, 0.05 ) && smb_near( $dd->amount, round( $dd->quantity * 150, 2 ), 0.01 ) && 2 === count( \SM\Trade\Deals::items( $dd ) ), 'offer by element: 150 DA/kg × the weight of the pieces chosen' );
	$nokg = \SM\Trade\Deals::create( $pb, $u_eng, $who + array( 'kind' => 'offer', 'mode' => 'detail', 'items' => array( $kcol => 1 ), 'amount' => 5000 ) );
	smb_assert( is_wp_error( $nokg ) && 'amount_kg' === $nokg->get_error_code(), 'offer by element without a price per kg: refused' );
	$acc = $dd ? \SM\Trade\Deals::act( $dd->id, $u_sel, 'accept' ) : false;
	$pa2 = Products::get( $pb );
	$qty = wp_list_pluck( $pa2->lines, 'qty' );
	smb_assert( true === $acc && array( 18, 22, 40 ) === array_map( 'intval', $qty ) && 'detail' === $pa2->sale_mode && 0.0 === (float) $pa2->lot_price && smb_near( $pa2->total_weight, $both['data']['total_weight'] - $kg_want, 0.5 ) && smb_near( $pa2->specs['elements_kg'], $pa2->total_weight, 0.5 ) && 'published' === $pa2->status, 'accepted: the pieces leave the inventory, the weight follows, the whole-building sale closes' );
	$o2 = \SM\Products\Stock::options( $pa2 );
	wp_set_current_user( $u_eng );
	$it2 = Bridge::listing( null, array( 'id' => $pa2->listing_id ) );
	smb_assert( ! $o2['lot'] && 18 === $o2['detail']['lines'][0]['qty'] && 0.0 === (float) $it2['building_sale']['lot_total'] && $it2['building_sale']['by_element'] && smb_near( $it2['building_sale']['per_kg'], 170 ), 'the page then offers the elements only, at 170 DA/kg' );
	$rest = array();
	foreach ( $o2['detail']['lines'] as $l ) {
		$rest[ $l['key'] ] = $l['qty'];
	}
	$all = \SM\Trade\Deals::create( $pb, $u_eng, $who + array( 'kind' => 'order', 'mode' => 'detail', 'items' => $rest ) );
	$ok2 = is_wp_error( $all ) ? $all : \SM\Trade\Deals::act( $all, $u_sel, 'accept' );
	smb_assert( true === $ok2 && 'sold' === Products::get( $pb )->status, 'the last pieces sold: the listing is marked sold' );
	wp_set_current_user( 0 );
}
$pl   = Products::save( 0, $c_sel, $u_sel, $lotonly, 'published' );
$ol   = \SM\Products\Stock::options( Products::get( $pl ) );
smb_assert( $ol['lot'] && ! $ol['detail'], 'whole building only: nothing can be bought by element' );
$fv   = apply_filters( 'sm_products_form_values', array(), Products::get( $pl ) );
smb_assert( '1' === $fv['sell_lot'] && '' === $fv['sell_elements'] && 'kg' === $fv['price_basis'] && '170' === $fv['price'], 'edit form: the box and the price that were typed come back' );
$pre  = apply_filters( 'sm_theme_data_mission_prefill', null, array( 'listing' => Products::get( $pl )->listing_id ) );
smb_assert( is_array( $pre ) && 'expertise' === $pre['type'] && false !== strpos( $pre['description'], '60 × 12 × 4,5 m' ) && false !== strpos( $pre['description'], (string) $pre['url'] ) && 0 === strpos( $pre['title'], 'Inspection' ), 'inspection request: title, dimensions and link of the building already filled in' );
global $wpdb;
$wpdb->update( $wpdb->prefix . 'sm_products', array( 'variant' => 'assembled', 'sale_mode' => 'detail', 'price_unit' => 'kg', 'price' => 300, 'lot_price' => 0, 'total_price' => 9600000.4 ), array( 'id' => $pl ) );
$Sale::migrate();
$mig = Products::get( $pl );
smb_assert( 'lot' === $mig->sale_mode && 'lot' === $mig->price_unit && smb_near( $mig->lot_price, 9600000 ) && smb_near( $mig->price, 9600000 ), 'listings of before 0.4.0: an assembled building priced per kg becomes a whole-building sale at its total' );

smb_cleanup();
remove_filter( 'sm_theme_demo_enabled', '__return_false', 99 );
$t = $GLOBALS['smb_t'];
printf( "\n%d passed, %d failed\n", $t['pass'], $t['fail'] );
if ( $t['fail'] ) {
	echo "FAILED:\n  - " . implode( "\n  - ", $t['fails'] ) . "\n";
	exit( 1 );
}
