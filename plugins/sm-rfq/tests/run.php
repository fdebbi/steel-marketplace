<?php
/**
 * Tests of sm-rfq. Run on a TEST site only (creates then removes data):
 *   wp eval-file wp-content/plugins/sm-rfq/tests/run.php
 *
 * Expected weights are read from SM Weight Engine (never typed here), so the
 * tests check the arithmetic of the lot, not the reference tables.
 *
 * @package SM\Rfq
 */

use SM\Core\Accounts\Companies;
use SM\Rfq\Expiry;
use SM\Rfq\Form;
use SM\Rfq\Offers;
use SM\Rfq\Rfqs;
use SM\Rfq\Settings;
use SM\Rfq\Spec;
use SM\Rfq\Theme\Bridge;

defined( 'ABSPATH' ) || exit;

$GLOBALS['smq_t'] = array( 'pass' => 0, 'fail' => 0, 'fails' => array() );
function smq_assert( $cond, $label ) {
	$t = &$GLOBALS['smq_t'];
	if ( $cond ) {
		++$t['pass'];
		echo "  ✓ $label\n";
	} else {
		++$t['fail'];
		$t['fails'][] = $label;
		echo "  ✗ $label\n";
	}
}
function smq_near( $a, $b, $tol = 0.01 ) {
	return abs( (float) $a - (float) $b ) <= $tol;
}
function smq_user( $suffix, $class, array $activities ) {
	$uid = wp_insert_user( array( 'user_login' => 'smqtest_' . $suffix, 'user_email' => "smqtest-{$suffix}@example.test", 'user_pass' => wp_generate_password(), 'role' => 'sm_member' ) );
	$cid = Companies::create( array( 'account_class' => $class, 'name' => "SMQTEST {$suffix}", 'wilaya' => '28' ), $uid, $activities );
	Companies::raise_level( $cid, 1 );
	return array( $uid, $cid );
}
function smq_cleanup() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sm_companies WHERE name LIKE 'SMQTEST %'" ) as $cid ) {
		foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}sm_rfqs WHERE company_id = %d", $cid ) ) as $rid ) {
			Rfqs::delete( $rid );
		}
		$wpdb->delete( $wpdb->prefix . 'sm_rfq_offers', array( 'company_id' => $cid ) );
		foreach ( array( 'company_members', 'company_activities' ) as $t ) {
			$wpdb->delete( $wpdb->prefix . 'sm_' . $t, array( 'company_id' => $cid ) );
		}
		$wpdb->delete( $wpdb->prefix . 'sm_companies', array( 'id' => $cid ) );
	}
	foreach ( get_users( array( 'search' => 'smqtest-*', 'search_columns' => array( 'user_email' ) ) ) as $u ) {
		$wpdb->delete( $wpdb->prefix . 'sm_notifications', array( 'user_id' => $u->ID ) );
		wp_delete_user( $u->ID );
	}
}
smq_cleanup();
$suffix = substr( md5( (string) microtime( true ) ), 0, 6 );
list( $u_buy, $c_buy ) = smq_user( 'b' . $suffix, 'commercial', array( 'project_owner' ) );
list( $u_s1, $c_s1 )   = smq_user( 's' . $suffix, 'commercial', array( 'supplier' ) );
list( $u_s2, $c_s2 )   = smq_user( 'm' . $suffix, 'commercial', array( 'manufacturer' ) );
list( $u_s3, $c_s3 )   = smq_user( 'r' . $suffix, 'commercial', array( 'reuse_dealer' ) );
list( $u_eng, $c_eng ) = smq_user( 'e' . $suffix, 'engineering', array( 'engineer' ) );
$engine = \SM\Core\Modules::service( 'weight' );
$kgm    = static function ( $family, $section ) use ( $engine ) {
	$r = $engine->calculate( array( 'family' => $family, 'section' => $section, 'bars' => 1, 'length' => 1000 ) );
	return is_wp_error( $r ) ? 0 : (float) $r['total_kg'];
};
$today    = current_time( 'Y-m-d', true );
$deadline = gmdate( 'Y-m-d', strtotime( $today . ' +10 days' ) );
$needed   = gmdate( 'Y-m-d', strtotime( $today . ' +30 days' ) );
$req      = array(
	'market'      => 'products',
	'category'    => 'profiles',
	'lines'       => array(
		array( 'family' => 'IPE', 'section' => 'IPE 270', 'qty' => '48', 'length' => '12' ),
		array( 'family' => 'HEA', 'section' => 'HEA 200', 'qty' => '10', 'length' => '6' ),
		array( 'family' => 'IPE', 'section' => '', 'qty' => '', 'length' => '' ),
	),
	'title'       => array( 'fr' => 'Poutrelles pour hangar' ),
	'description' => 'S275JR, barres droites, certificat 3.1 souhaité.',
	'notes'       => 'Accès camion semi-remorque.',
	'wilaya'      => '16',
	'place'       => 'Zone industrielle de Rouiba',
	'needed_by'   => $needed,
	'deadline'    => $deadline,
);

echo "\n1. Request checks\n";
$s = Spec::build( $req );
$w = 48 * 12 * $kgm( 'IPE', 'IPE 270' ) + 10 * 6 * $kgm( 'HEA', 'HEA 200' );
smq_assert( ! $s['errors'] && 2 === count( $s['data']['lines'] ), 'request with two profile rows accepted, empty row ignored' );
smq_assert( smq_near( $s['data']['weight_kg'], $w, 0.5 ) && 'theoretical' === $s['data']['weight_source'] && 58.0 === $s['data']['quantity'], 'weight of the rows from the weight engine, 58 pieces' );
$free = Spec::build( array_merge( $req, array( 'lines' => array(), 'market' => 'buildings', 'category' => 'bld-storage', 'designation' => 'Hangar 25 × 15 m', 'dimensions' => '25 × 15 × 7 m', 'quantity' => '1', 'unit' => 'lot', 'weight_kg' => '' ) ) );
smq_assert( ! $free['errors'] && 'lot' === $free['data']['unit'] && 0.0 === $free['data']['weight_kg'], 'free description with quantity and unit, weight unknown' );
$x = Spec::build( array_merge( $req, array( 'lines' => array(), 'quantity' => '', 'unit' => '' ) ) );
smq_assert( isset( $x['errors']['quantity'] ), 'quantity needed when no profile rows' );
$x = Spec::build( array_merge( $req, array( 'market' => 'nope', 'category' => '' ) ) );
smq_assert( isset( $x['errors']['market'] ), 'unknown market refused' );
$x = Spec::build( array_merge( $req, array( 'category' => 'reuse-scrap' ) ) );
smq_assert( isset( $x['errors']['category'] ), 'category of another market refused' );
$x = Spec::build( array_merge( $req, array( 'deadline' => $today ) ) );
smq_assert( isset( $x['errors']['deadline'] ), 'deadline must be after today' );
$x = Spec::build( array_merge( $req, array( 'deadline' => gmdate( 'Y-m-d', strtotime( $today . ' +200 days' ) ) ) ) );
smq_assert( isset( $x['errors']['deadline'] ), 'deadline limited by the settings (90 days by default)' );
$x = Spec::build( array_merge( $req, array( 'needed_by' => gmdate( 'Y-m-d', strtotime( $today . ' +5 days' ) ) ) ) );
smq_assert( isset( $x['errors']['needed_by'] ), 'delivery date cannot be before the answer deadline' );
$x = Spec::build( array_merge( $req, array( 'title' => array(), 'description' => 'x', 'wilaya' => '99' ) ) );
smq_assert( isset( $x['errors']['title'], $x['errors']['description'], $x['errors']['wilaya'] ), 'title, description and delivery wilaya required' );

echo "\n2. Publication and board\n";
$rid = Rfqs::save( 0, $c_buy, $u_buy, $s, 'open' );
$r   = Rfqs::get( $rid );
smq_assert( $r && 'open' === $r->status && Rfqs::is_open( $r ) && 2 === count( $r->lines ), 'request saved and open' );
$board = Bridge::rfqs( null, array( 'per_page' => 60 ) );
smq_assert( in_array( $rid, wp_list_pluck( $board['items'], 'id' ), true ), 'listed on the public board' );
smq_assert( in_array( $rid, wp_list_pluck( Bridge::rfqs( null, array( 'q' => 'HEA200', 'per_page' => 60 ) )['items'], 'id' ), true ) && ! in_array( $rid, wp_list_pluck( Bridge::rfqs( null, array( 'wilaya' => '31', 'per_page' => 60 ) )['items'], 'id' ), true ), 'search by profile (HEA200) and wilaya filter' );
$item = $board['items'][ array_search( $rid, wp_list_pluck( $board['items'], 'id' ), true ) ];
smq_assert( 'Poutrelles pour hangar' === $item['title']['fr'] && 10 === $item['days_left'] && 'Profilés' === $item['category_label']['fr'], 'board data: title, days left, category' );

echo "\n3. Offers\n";
wp_set_current_user( $u_s1 );
smq_assert( '' === Form::offer_block( $r ), 'a supplier may answer' );
$o1 = Spec::offer( $r, array( 'price_mode' => 'kg', 'price' => '235', 'delivery_days' => '7', 'delivery_included' => '1', 'validity_days' => '15', 'payment_terms' => '30 % à la commande' ) );
smq_assert( ! $o1['errors'] && smq_near( $o1['data']['total_price'], round( $r->weight_kg * 235, 2 ), 0.05 ) && smq_near( $o1['data']['price_per_kg'], 235 ), 'price per kg × requested weight = total' );
list( $oid1, $new1 ) = Offers::save( $rid, $c_s1, $u_s1, $o1['data'] );
$o2 = Spec::offer( $r, array( 'price_mode' => 'total', 'price' => '4500000', 'weight_kg' => '19500', 'delivery_days' => '3', 'validity_days' => '10' ) );
smq_assert( ! $o2['errors'] && smq_near( $o2['data']['price_per_kg'], round( 4500000 / 19500, 2 ) ), 'total price with the supplier\'s own weight → price per kg' );
list( $oid2 ) = Offers::save( $rid, $c_s2, $u_s2, $o2['data'] );
$o3 = Spec::offer( $r, array( 'price_mode' => 'total', 'price' => '5100000', 'delivery_days' => '15', 'validity_days' => '30' ) );
list( $oid3 ) = Offers::save( $rid, $c_s3, $u_s3, $o3['data'] );
smq_assert( $new1 && 3 === Rfqs::get( $rid )->offers_count, 'three offers counted' );
list( $again, $new_again ) = Offers::save( $rid, $c_s1, $u_s1, array_merge( $o1['data'], array( 'delivery_days' => 5 ) ) );
smq_assert( $again === $oid1 && ! $new_again && 3 === Rfqs::get( $rid )->offers_count, 'one offer per company: sending again updates it' );
$bad = Spec::offer( $free['data'] ? (object) array( 'weight_kg' => 0 ) : $r, array( 'price_mode' => 'kg', 'price' => '200', 'delivery_days' => '', 'validity_days' => '0' ) );
smq_assert( isset( $bad['errors']['weight_kg'], $bad['errors']['delivery_days'], $bad['errors']['validity_days'] ), 'per-kg price without weight, missing delivery time and validity refused' );
wp_set_current_user( $u_buy );
smq_assert( '' !== Form::offer_block( $r ), 'the buyer cannot answer his own request' );
wp_set_current_user( $u_eng );
smq_assert( '' !== Form::offer_block( $r ) && ! sm_can( 'rfq.create' ), 'an engineering account can neither answer nor publish' );

echo "\n4. Privacy and comparison\n";
wp_set_current_user( $u_s2 );
$view = Bridge::rfq( null, array( 'id' => $rid ) );
smq_assert( array() === $view['offers'] && $oid2 === $view['my_offer']['id'] && $view['viewer']['can_offer'], 'a supplier sees only his own offer' );
wp_set_current_user( 0 );
$guest = Bridge::rfq( null, array( 'id' => $rid ) );
smq_assert( array() === $guest['offers'] && null === $guest['my_offer'] && array() === $guest['files'], 'a visitor sees no offer and no drawing' );
wp_set_current_user( $u_buy );
$view = Bridge::rfq( null, array( 'id' => $rid ) );
$ids  = wp_list_pluck( $view['offers'], 'id' );
smq_assert( 3 === count( $view['offers'] ) && $view['viewer']['is_buyer'] && $view['viewer']['can_decide'], 'the buyer sees the three offers' );
smq_assert( $oid2 === $ids[0] && $view['offers'][0]['best']['total'] && $view['offers'][0]['best']['days'], 'sorted by total price; lowest total and fastest delivery flagged' );
$by_kg = Bridge::rfq( null, array( 'id' => $rid, 'sort' => 'kg' ) )['offers'];
smq_assert( $by_kg[0]['best']['kg'] && $by_kg[0]['price_per_kg'] <= $by_kg[1]['price_per_kg'], 'sorted by price per kg' );
smq_assert( ! empty( $view['offers'][0]['supplier']['name']['fr'] ) && isset( $view['offers'][0]['supplier']['verification'], $view['offers'][0]['supplier']['phone'] ), 'supplier information to compare and contact' );
smq_assert( ! $view['viewer']['can_edit'] && ! $view['viewer']['can_delete'], 'a request with offers can no longer be edited or deleted' );

echo "\n5. Decisions and notifications\n";
global $wpdb;
$notes = static fn( $uid, $type ) => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_notifications WHERE user_id = %d AND type = %s", $uid, $type ) );
Offers::set_status( $oid3, 'withdrawn' );
smq_assert( 2 === Rfqs::get( $rid )->offers_count, 'a withdrawn offer is no longer counted' );
Offers::set_status( $oid1, 'shortlisted' );
// Accept through the same steps as the form handler.
Offers::set_status( $oid2, 'accepted' );
Rfqs::set_status( $rid, 'awarded', array( 'awarded_offer' => $oid2 ) );
foreach ( Offers::for_rfq( $rid, false ) as $o ) {
	if ( $o->id !== $oid2 && in_array( $o->status, array( 'submitted', 'shortlisted' ), true ) ) {
		Offers::set_status( $o->id, 'declined' );
	}
}
smq_assert( 'awarded' === Rfqs::get( $rid )->status && 'accepted' === Offers::get( $oid2 )->status && 'declined' === Offers::get( $oid1 )->status && 'withdrawn' === Offers::get( $oid3 )->status, 'accepting one offer awards the request and declines the others' );
smq_assert( ! in_array( $rid, wp_list_pluck( Bridge::rfqs( null, array( 'per_page' => 60 ) )['items'], 'id' ), true ), 'an awarded request leaves the board' );
\SM\Core\Notifications\Notifications::notify_company( $c_buy, 'rfq.offer_received', array( 'rfq_id' => $rid, 'company_id' => $c_s1 ) );
smq_assert( $notes( $u_buy, 'rfq.offer_received' ) >= 1, 'notification types registered and delivered' );
$types = \SM\Core\Notifications\Notifications::types();
smq_assert( isset( $types['rfq.offer_accepted'], $types['rfq.awarded_other'], $types['rfq.closed'], $types['rfq.expired'] ), 'notification texts for every decision' );

echo "\n6. Deadline and my lists\n";
$rid2 = Rfqs::save( 0, $c_buy, $u_buy, $free, 'open' );
$wpdb->update( Rfqs::table(), array( 'deadline' => gmdate( 'Y-m-d', strtotime( $today . ' -1 day' ) ) ), array( 'id' => $rid2 ) );
smq_assert( ! Rfqs::is_open( Rfqs::get( $rid2 ) ), 'past deadline: no more offers, even before the daily job' );
Expiry::run();
smq_assert( 'expired' === Rfqs::get( $rid2 )->status && $notes( $u_buy, 'rfq.expired' ) >= 1, 'daily job: expired and buyer notified' );
$mine = Bridge::my_rfqs( null, array() );
smq_assert( 2 === $mine['total'] && 1 === ( $mine['counts']['awarded'] ?? 0 ), '"My requests" with counts by status' );
wp_set_current_user( $u_s2 );
$mo = Bridge::my_offers( null, array() );
smq_assert( 1 === count( $mo['items'] ) && 'accepted' === $mo['items'][0]['status'] && 'awarded' === $mo['items'][0]['rfq']['status'], '"Quotations sent" shows the accepted offer' );
update_option( Settings::OPTION, array( 'moderation' => 1 ) );
smq_assert( 1 === (int) Settings::get( 'moderation' ), 'moderation can be switched on in the settings' );
delete_option( Settings::OPTION );
wp_set_current_user( 0 );

smq_cleanup();
$t = $GLOBALS['smq_t'];
printf( "\n%d passed, %d failed\n", $t['pass'], $t['fail'] );
if ( $t['fail'] ) {
	echo "FAILED:\n  - " . implode( "\n  - ", $t['fails'] ) . "\n";
	exit( 1 );
}
