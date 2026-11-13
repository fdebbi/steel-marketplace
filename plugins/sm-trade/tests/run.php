<?php
/**
 * Tests of SM Trade (and of SM Products Stock): wp eval-file wp-content/plugins/sm-trade/tests/run.php
 *
 * @package SM\Trade
 */

use SM\Core\Accounts\Companies;
use SM\Products\Products;
use SM\Products\Spec;
use SM\Products\Stock;
use SM\Trade\Deals;

$GLOBALS['smt_t'] = array( 0, 0 );
function smt_assert( $cond, $label ) {
	if ( $cond ) {
		++$GLOBALS['smt_t'][0];
		echo "  ✓ $label\n";
	} else {
		++$GLOBALS['smt_t'][1];
		echo "  ✗ $label\n";
	}
}
function smt_user( $suffix, $class, array $activities ) {
	$uid = wp_insert_user( array( 'user_login' => 'smttest_' . $suffix, 'user_email' => "smttest-{$suffix}@example.test", 'user_pass' => wp_generate_password(), 'role' => 'sm_member', 'display_name' => 'SMT ' . $suffix ) );
	update_user_meta( $uid, 'sm_phone', '0550 11 22 33' );
	$cid = Companies::create( array( 'account_class' => $class, 'name' => "SMTTEST {$suffix}", 'wilaya' => '28', 'phone' => '0661 00 00 0' . strlen( $suffix ) ), $uid, $activities );
	Companies::raise_level( $cid, 1 );
	return array( $uid, $cid );
}
function smt_cleanup() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sm_companies WHERE name LIKE 'SMTTEST %'" ) as $cid ) {
		foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}sm_trade_deals WHERE seller_company_id = %d OR buyer_company_id = %d", $cid, $cid ) ) as $did ) {
			$wpdb->delete( $wpdb->prefix . 'sm_trade_events', array( 'deal_id' => $did ) );
			$wpdb->delete( $wpdb->prefix . 'sm_trade_deals', array( 'id' => $did ) );
		}
		foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}sm_products WHERE company_id = %d", $cid ) ) as $pid ) {
			Products::delete( $pid );
		}
		foreach ( array( 'company_members', 'company_activities', 'notifications' ) as $t ) {
			$wpdb->delete( $wpdb->prefix . 'sm_' . $t, array( 'company_id' => $cid ) );
		}
		$wpdb->delete( $wpdb->prefix . 'sm_companies', array( 'id' => $cid ) );
	}
	foreach ( get_users( array( 'search' => 'smttest-*', 'search_columns' => array( 'user_email' ) ) ) as $u ) {
		$wpdb->delete( $wpdb->prefix . 'sm_notifications', array( 'user_id' => $u->ID ) );
		wp_delete_user( $u->ID );
	}
}
function smt_listing( $cid, $uid, array $in ) {
	$base = array( 'wilaya' => '28', 'title' => array( 'fr' => 'Test commande' ), 'price' => '200', 'category' => 'profiles', 'family' => 'IPE', 'section' => 'IPE 200' );
	$s    = Spec::build( array_merge( $base, $in ) );
	return Products::save( 0, $cid, $uid, $s, 'published' );
}
function smt_notified( $uid, $type ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_notifications WHERE user_id = %d AND type = %s", $uid, $type ) );
}

smt_cleanup();
$buyer_in = array( 'name' => 'Karim Test', 'phone' => '0550 12 34 56', 'email' => 'karim@example.test' );

echo "1. What can be bought (SM Products Stock)\n";
list( $u_s, $c_s ) = smt_user( 'seller', 'company', array( 'supplier' ) );
list( $u_b, $c_b ) = smt_user( 'buyer', 'client', array( 'client' ) );
list( $u_x, $c_x ) = smt_user( 'other', 'client', array( 'client' ) );
$bars = smt_listing( $c_s, $u_s, array( 'bars' => '10', 'price_unit' => 'kg' ) );
$pb   = Products::get( $bars );
$ob   = Stock::options( $pb );
smt_assert( null === $ob['lot'] && 'unit' === $ob['detail']['unit'] && 10.0 === (float) $ob['detail']['max'] && abs( $ob['detail']['unit_price'] - 200 * $pb->total_weight / 10 ) < 0.01, 'bars priced per kg: bought by whole bars, price of one bar from the weight' );
$kg  = smt_listing( $c_s, $u_s, array( 'qty_mode' => 'weight', 'total_kg' => '5000', 'sale_mode' => 'both', 'lot_price' => '900000' ) );
$ok  = Stock::options( Products::get( $kg ) );
smt_assert( 900000.0 === $ok['lot']['total'] && 'kg' === $ok['detail']['unit'] && 5000.0 === (float) $ok['detail']['max'] && 200.0 === (float) $ok['detail']['unit_price'], 'sold by weight, lot or detail: whole lot at the lot price, or kg up to the weight listed' );
$chk = Stock::check( Products::get( $kg ), 'detail', 6000 );
smt_assert( is_wp_error( $chk ) && 'qty' === $chk->get_error_code(), 'more than available: refused with the quantity left' );
smt_assert( is_wp_error( Stock::check( $pb, 'detail', 2.5 ) ), 'bars: whole number only' );
smt_assert( is_wp_error( Stock::check( $pb, 'lot', 1 ) ), 'not sold as a lot: lot refused' );
$c = Stock::check( $pb, 'detail', 3 );
smt_assert( ! is_wp_error( $c ) && 3.0 === $c[0] && 'unit' === $c[1] && abs( $c[2] - 3 * $ob['detail']['unit_price'] ) < 0.02, 'quantity checked: total at the listed price' );

echo "2. Order at the listed price\n";
$e = Deals::create( $bars, $u_s, array( 'kind' => 'order', 'mode' => 'detail', 'quantity' => 1 ) + $buyer_in );
smt_assert( is_wp_error( $e ) && 'own' === $e->get_error_code(), 'a seller cannot buy his own listing' );
$e = Deals::create( $bars, $u_b, array( 'kind' => 'order', 'mode' => 'detail', 'quantity' => 1, 'phone' => '12' ) + $buyer_in );
smt_assert( is_wp_error( $e ) && 'phone' === $e->get_error_code(), 'phone of the buyer required' );
$d1 = Deals::create( $bars, $u_b, array( 'kind' => 'order', 'mode' => 'detail', 'quantity' => 4, 'message' => 'Livraison M\'Sila' ) + $buyer_in );
$o1 = Deals::get( $d1 );
smt_assert( $o1 && 'open' === $o1->status && 'seller' === $o1->turn && 0 === strpos( $o1->ref, 'ACH-' ) && 4.0 === (float) $o1->quantity && abs( $o1->amount - 4 * $ob['detail']['unit_price'] ) < 0.05 && 'Karim Test' === $o1->buyer_name, 'order created: reference, quantity, total at the listed price, buyer details' );
smt_assert( smt_notified( $u_s, 'trade.order' ) >= 1, 'seller notified of the order' );
$e = Deals::create( $bars, $u_b, array( 'kind' => 'order', 'mode' => 'detail', 'quantity' => 1 ) + $buyer_in );
smt_assert( is_wp_error( $e ) && 'open' === $e->get_error_code(), 'one open order or offer per buyer and listing' );
smt_assert( array( 'cancel' ) === Deals::actions( $o1, $u_b ) && array( 'accept', 'counter', 'refuse' ) === Deals::actions( $o1, $u_s ) && array() === Deals::actions( $o1, $u_x ), 'answers: buyer may cancel, seller may accept, propose a price or refuse, others nothing' );
smt_assert( is_wp_error( Deals::act( $d1, $u_b, 'accept' ) ), 'the buyer cannot accept his own order' );
smt_assert( null === Deals::seller_contacts( $o1 ), 'seller contacts hidden while not accepted' );
$r = Deals::act( $d1, $u_s, 'accept', 0, 'OK, livraison jeudi' );
$o1 = Deals::get( $d1 );
$pb = Products::get( $bars );
smt_assert( true === $r && 'accepted' === $o1->status && 6.0 === (float) $pb->pieces && 'published' === $pb->status, 'accepted: 4 bars taken from the listing, 6 left, still online' );
$sc = Deals::seller_contacts( $o1 );
smt_assert( $sc && 'SMTTEST seller' === $sc['name'] && '' !== $sc['phone'] && is_email( $sc['email'] ), 'accepted: the buyer gets the seller\'s contact details' );
smt_assert( smt_notified( $u_b, 'trade.accepted' ) >= 1, 'buyer notified of the acceptance' );
smt_assert( is_wp_error( Deals::act( $d1, $u_s, 'refuse' ) ), 'closed: no more answers' );

echo "3. Offer, counter-offers, limit\n";
$d2 = Deals::create( $kg, $u_b, array( 'kind' => 'offer', 'mode' => 'lot', 'amount' => \SM\Trade\Form::amount( '800 000' ) ) + $buyer_in );
$o2 = Deals::get( $d2 );
smt_assert( $o2 && 0 === strpos( $o2->ref, 'OFR-' ) && 800000.0 === (float) $o2->amount && 900000.0 === (float) $o2->listed_total && 'lot' === $o2->mode, 'offer on the whole lot: proposed price kept with the listed price' );
smt_assert( 1250000.0 === \SM\Trade\Form::amount( '1.250.000' ) && 1250000.5 === \SM\Trade\Form::amount( '1 250 000,5' ), 'prices typed with spaces, dots or commas' );
smt_assert( is_wp_error( Deals::act( $d2, $u_s, 'counter', 800000 ) ), 'counter-offer at the same price refused' );
Deals::act( $d2, $u_s, 'counter', 870000, 'Dernier prix ?' );
$o2 = Deals::get( $d2 );
smt_assert( 'buyer' === $o2->turn && 870000.0 === (float) $o2->amount && 1 === (int) $o2->counters && smt_notified( $u_b, 'trade.countered' ) >= 1, 'seller proposes another price: buyer\'s turn, buyer notified' );
smt_assert( in_array( 'accept', Deals::actions( $o2, $u_b ), true ) && ! in_array( 'accept', Deals::actions( $o2, $u_s ), true ), 'only the party whose turn it is can accept' );
Deals::act( $d2, $u_b, 'counter', 840000 );
Deals::act( $d2, $u_s, 'counter', 860000 );
$o2 = Deals::get( $d2 );
smt_assert( 3 === (int) $o2->counters && ! in_array( 'counter', Deals::actions( $o2, $u_b ), true ) && in_array( 'accept', Deals::actions( $o2, $u_b ), true ), 'after 3 answers with a price: accept or refuse only' );
Deals::act( $d2, $u_b, 'accept' );
$o2 = Deals::get( $d2 );
smt_assert( 'accepted' === $o2->status && 860000.0 === (float) $o2->amount && 'sold' === Products::get( $kg )->status, 'buyer accepts the last price: whole lot sold, listing marked sold' );
$ev = Deals::events( $d2 );
smt_assert( array( 'created', 'countered', 'countered', 'countered', 'accepted' ) === wp_list_pluck( $ev, 'action' ) && array( 'buyer', 'seller', 'buyer', 'seller', 'buyer' ) === wp_list_pluck( $ev, 'actor' ), 'every step kept in the history' );

echo "4. Not enough left, refuse, cancel, expire\n";
$kg2 = smt_listing( $c_s, $u_s, array( 'qty_mode' => 'weight', 'total_kg' => '1000' ) );
$a   = Deals::create( $kg2, $u_b, array( 'kind' => 'order', 'mode' => 'detail', 'quantity' => 700 ) + $buyer_in );
$b   = Deals::create( $kg2, $u_x, array( 'kind' => 'order', 'mode' => 'detail', 'quantity' => 600 ) + $buyer_in );
Deals::act( $a, $u_s, 'accept' );
$r = Deals::act( $b, $u_s, 'accept' );
smt_assert( is_wp_error( $r ) && 'open' === Deals::get( $b )->status && abs( Products::get( $kg2 )->total_weight - 300 ) < 0.01, 'second order larger than what is left: acceptance refused, 300 kg left' );
Deals::act( $b, $u_s, 'refuse', 0, 'Plus assez de stock' );
smt_assert( 'refused' === Deals::get( $b )->status && smt_notified( $u_x, 'trade.refused' ) >= 1, 'refused with a reason, buyer notified' );
$c3 = Deals::create( $kg2, $u_x, array( 'kind' => 'offer', 'mode' => 'detail', 'quantity' => 300, 'amount' => 50000 ) + $buyer_in );
Deals::act( $c3, $u_x, 'cancel' );
smt_assert( 'cancelled' === Deals::get( $c3 )->status && smt_notified( $u_s, 'trade.cancelled' ) >= 1, 'buyer cancels, seller notified' );
$c4 = Deals::create( $kg2, $u_x, array( 'kind' => 'order', 'mode' => 'detail', 'quantity' => 100 ) + $buyer_in );
global $wpdb;
$wpdb->update( Deals::table(), array( 'updated_at' => gmdate( 'Y-m-d H:i:s', time() - 8 * DAY_IN_SECONDS ) ), array( 'id' => $c4 ) );
smt_assert( Deals::expire_old() >= 1 && 'expired' === Deals::get( $c4 )->status && smt_notified( $u_x, 'trade.expired' ) >= 1 && smt_notified( $u_s, 'trade.expired' ) >= 1, 'no answer for 7 days: expired, both told' );
$c5 = Deals::create( $kg2, $u_x, array( 'kind' => 'order', 'mode' => 'detail', 'quantity' => 300 ) + $buyer_in );
Deals::act( $c5, $u_s, 'accept' );
smt_assert( 'sold' === Products::get( $kg2 )->status, 'last kilograms sold: listing marked sold' );
smt_assert( is_wp_error( Deals::create( $kg2, $u_b, array( 'kind' => 'order', 'mode' => 'detail', 'quantity' => 1 ) + $buyer_in ) ), 'sold listing: no new order' );

echo "5. Lists, history, theme data\n";
$qs = Deals::query( array( 'seller' => $c_s, 'per_page' => 0 ) );
$qb = Deals::query( array( 'buyer' => $u_b, 'per_page' => 0 ) );
smt_assert( 7 === $qs['total'] && 3 === $qb['total'], 'deals of the seller (7) and of one buyer (3)' );
smt_assert( 2 === Deals::query( array( 'seller' => $c_s, 'kind' => 'offer', 'per_page' => 0 ) )['total'] && 1 === Deals::query( array( 'seller' => $c_s, 'status' => 'expired', 'per_page' => 0 ) )['total'] && 1 <= Deals::query( array( 'q' => 'Karim', 'per_page' => 0 ) )['total'], 'filters: type, status, search' );
wp_set_current_user( $u_b );
$box = apply_filters( 'sm_theme_data_trade_box', null, array( 'id' => Products::get( $bars )->listing_id ) );
smt_assert( 'ok' === $box['state'] && 'Karim' !== $box['contact']['name'] && '0550 11 22 33' === $box['contact']['phone'] && 6.0 === (float) $box['detail']['max'], 'buy box: signed-in buyer, contact prefilled from the account, 6 bars left' );
$dd = apply_filters( 'sm_theme_data_deal', null, array( 'id' => $d1 ) );
smt_assert( 'buyer' === $dd['role'] && $dd['seller_contacts'] && 2 === count( $dd['events'] ), 'deal page of the buyer: contacts and steps' );
wp_set_current_user( $u_x );
smt_assert( null === apply_filters( 'sm_theme_data_deal', null, array( 'id' => $d1 ) ), 'deal of others: not shown' );
wp_set_current_user( $u_s );
$box = apply_filters( 'sm_theme_data_trade_box', null, array( 'id' => Products::get( $bars )->listing_id ) );
smt_assert( 'own' === $box['state'], 'buy box on one\'s own listing: no order button' );
$w = apply_filters( 'sm_theme_data_trade_waiting', null, array() );
smt_assert( 0 === $w['seller'], 'nothing waiting for the seller' );
wp_set_current_user( 0 );
$box = apply_filters( 'sm_theme_data_trade_box', null, array( 'id' => Products::get( $bars )->listing_id ) );
smt_assert( 'login' === $box['state'] && false !== strpos( $box['login_url'], 'redirect_to' ), 'visitor: sign-in link that comes back to the listing' );

smt_cleanup();
$t = $GLOBALS['smt_t'];
echo "\n{$t[0]} passed, {$t[1]} failed\n";
