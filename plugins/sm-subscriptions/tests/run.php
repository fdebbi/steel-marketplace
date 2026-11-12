<?php
/**
 * Tests of sm-subscriptions. Run on a TEST site only (creates then removes data):
 *   wp eval-file wp-content/plugins/sm-subscriptions/tests/run.php
 *
 * @package SM\Subscriptions
 */

use SM\Core\Accounts\Companies;
use SM\Subscriptions\Credits;
use SM\Subscriptions\Founders;
use SM\Subscriptions\Limits;
use SM\Subscriptions\Orders;
use SM\Subscriptions\Plans;
use SM\Subscriptions\Settings;
use SM\Subscriptions\Subscriptions;

defined( 'ABSPATH' ) || exit;

$GLOBALS['smsub_t'] = array( 'pass' => 0, 'fail' => 0, 'fails' => array() );
function smsub_assert( $cond, $label ) {
	$t = &$GLOBALS['smsub_t'];
	if ( $cond ) {
		++$t['pass'];
		echo "  ✓ $label\n";
	} else {
		++$t['fail'];
		$t['fails'][] = $label;
		echo "  ✗ $label\n";
	}
}
function smsub_account( $suffix, $class, array $activities ) {
	$uid = wp_insert_user( array( 'user_login' => 'smsubtest_' . $suffix, 'user_email' => "smsubtest-{$suffix}@example.test", 'user_pass' => wp_generate_password(), 'role' => 'sm_member', 'display_name' => "Test {$suffix}" ) );
	$cid = Companies::create( array( 'account_class' => $class, 'name' => "SMSUBTEST {$suffix}", 'wilaya' => '28', 'phone' => '0550000000', 'email' => "contact-{$suffix}@example.test" ), $uid, $activities );
	Companies::raise_level( $cid, 1 );
	return array( $uid, $cid );
}
function smsub_listing( $cid, $weight, $market = 'products', $days_ago = 1 ) {
	global $wpdb;
	$d = gmdate( 'Y-m-d H:i:s', time() - $days_ago * DAY_IN_SECONDS );
	$wpdb->insert( $wpdb->prefix . 'sm_products', array( 'listing_id' => 'S' . wp_generate_password( 7, false ), 'company_id' => $cid, 'user_id' => 0, 'market' => $market, 'category' => 'profiles', 'family' => 'IPE', 'status' => 'published', 'total_weight' => $weight, 'created_at' => $d, 'updated_at' => $d, 'published_at' => $d ) );
	return (int) $wpdb->insert_id;
}
function smsub_cleanup() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sm_companies WHERE name LIKE 'SMSUBTEST %'" ) as $cid ) {
		foreach ( array( 'sm_products', 'sm_subscriptions', 'sm_sub_orders', 'sm_sub_credits', 'sm_ai_usage' ) as $t ) {
			$wpdb->delete( $wpdb->prefix . $t, array( 'company_id' => $cid ) );
		}
		foreach ( array( 'company_members', 'company_activities', 'verifications', 'notifications' ) as $t ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}sm_{$t} WHERE company_id = %d", $cid ) ); // phpcs:ignore WordPress.DB
		}
		$wpdb->delete( $wpdb->prefix . 'sm_companies', array( 'id' => $cid ) );
	}
	foreach ( get_users( array( 'search' => 'smsubtest-*', 'search_columns' => array( 'user_email' ) ) ) as $u ) {
		wp_delete_user( $u->ID );
	}
	$prices = (array) get_option( Founders::PRICES, array() );
	update_option( Founders::PRICES, array_filter( $prices, static fn( $k ) => Companies::get( (int) $k ), ARRAY_FILTER_USE_KEY ), false );
}

smsub_cleanup();
$settings_before = get_option( Settings::OPTION, null );
update_option( Settings::OPTION, array_merge( (array) $settings_before, array( 'founders_on' => 0 ) ), false );

echo "\n1. Plans\n";
$all = Plans::all();
smsub_assert( 3 === count( $all['company'] ) && 3 === count( $all['professional'] ) && 35000.0 === (float) $all['company']['pro']['price_year'], 'three plans for companies and three for professionals, starting prices' );
list( $u_co, $c_co )   = smsub_account( 'co', 'company', array( 'supplier' ) );
list( $u_cli, $c_cli ) = smsub_account( 'cli', 'client', array( 'client' ) );
$p = Subscriptions::plan_of( $c_co );
smsub_assert( $p['has_plans'] && 'free' === $p['code'] && 5 === (int) $p['data']['listings'], 'a new company is on the free plan (Discovery)' );
smsub_assert( ! Subscriptions::plan_of( $c_cli )['has_plans'], 'clients have no plan' );

echo "\n2. Limits of the free plan\n";
$co = Companies::get( $c_co );
for ( $i = 0; $i < 4; $i++ ) {
	smsub_listing( $c_co, 1500, 'products', 20 - $i );
}
$online = SM\Products\Products::online_totals( $c_co );
smsub_assert( 4 === $online['listings'] && 6000.0 === $online['weight'], 'what is online is counted (4 listings, 6 t)' );
smsub_assert( '' === Limits::products( '', $co, array( 'market' => 'products', 'weight' => 1000, 'new' => true ), $online ), 'a 5th listing of 1 t fits' );
smsub_assert( false !== strpos( Limits::products( '', $co, array( 'market' => 'products', 'weight' => 5000, 'new' => true ), $online ), '10' ), 'over 10 t online: refused with the limit' );
smsub_listing( $c_co, 500, 'products', 5 );
smsub_assert( '' !== SM\Products\Form::plan_limit( $co, array( 'market' => 'products', 'total_weight' => 100 ) ), 'a 6th listing is refused (5 online)' );
smsub_assert( '' === SM\Products\Form::plan_limit( $co, array( 'market' => 'buildings', 'total_weight' => 60000 ) ), 'buildings are counted apart: one 60 t building fits' );
smsub_listing( $c_co, 60000, 'buildings', 3 );
smsub_assert( '' !== SM\Products\Form::plan_limit( $co, array( 'market' => 'buildings', 'total_weight' => 1000 ) ), 'a second building is refused' );
smsub_assert( '' !== Limits::services( '', $co, 1 ) && '' === Limits::services( '', $co, 0 ), 'one service page on the free plan' );
smsub_assert( '' !== Limits::rfq_offers( '', $co, 3 ) && '' === Limits::rfq_offers( '', $co, 2 ), 'three first answers to requests for quotation per month' );
smsub_assert( false === Limits::contacts( true, $co ) && true === Limits::contacts( true, Companies::get( $c_cli ) ), 'contacts hidden on the free plan' );
wp_set_current_user( 0 );
$shaped = SM\Core\Theme\Bridge::shape_company( $co );
smsub_assert( '' === $shaped['phone'] && '' === $shaped['email'] && $shaped['contacts_hidden'], 'visitors do not see the phone and e-mail of a free account' );
wp_set_current_user( $u_co );
smsub_assert( '' !== SM\Core\Theme\Bridge::shape_company( $co )['phone'], '…its own members still see them' );
wp_set_current_user( 0 );

echo "\n3. Order, proof, payment, invoice\n";
$oid = Orders::create( $co, $u_co, 'plan', 'pro', 'year' );
$o   = Orders::get( $oid );
smsub_assert( 'awaiting' === $o->status && 35000.0 === (float) $o->amount_ttc && abs( (float) $o->amount_ht - 29411.76 ) < 0.01 && preg_match( '/^CMD-\d{4}-\d{5}$/', $o->ref ), 'order of Pro for a year: 35 000 DA tax included, 29 411,76 DA before 19 % TVA' );
smsub_assert( is_wp_error( Orders::create( $co, $u_co, 'plan', 'pro', 'week' ) ) && is_wp_error( Orders::create( Companies::get( $c_cli ), $u_cli, 'plan', 'pro', 'year' ) ), 'unknown period or a client ordering a plan: refused' );
Orders::send_proof( $o, array(), 'transfer', 'Virement du 12/11' );
smsub_assert( 'review' === Orders::get( $oid )->status, 'proof sent: payment being checked' );
$r = Orders::approve( Orders::get( $oid ) );
$o = Orders::get( $oid );
smsub_assert( true === $r && 'paid' === $o->status && preg_match( '/^FAC-\d{4}-\d{5}$/', $o->invoice_no ) && 'SMSUBTEST co' === ( json_decode( $o->billing, true )['buyer']['name'] ?? '' ), 'payment confirmed: invoice number and billing snapshot' );
$p = Subscriptions::plan_of( $c_co );
smsub_assert( 'pro' === $p['code'] && abs( strtotime( $p['sub']->ends_at . ' UTC' ) - strtotime( '+12 months' ) ) < 120, 'Pro active for 12 months' );
smsub_assert( is_wp_error( Orders::approve( $o ) ), 'a paid order cannot be confirmed twice' );
smsub_assert( '' === SM\Products\Form::plan_limit( $co, array( 'market' => 'products', 'total_weight' => 50000 ) ) && true === Limits::contacts( false, $co ), 'with Pro: more listings and tonnage, contacts visible' );
$inv = SM\Subscriptions\Theme\Bridge::invoice( null, array( 'id' => $oid ) );
smsub_assert( null === $inv, 'a visitor cannot open the invoice' );
wp_set_current_user( $u_co );
$inv = SM\Subscriptions\Theme\Bridge::invoice( null, array( 'id' => $oid ) );
smsub_assert( $inv && 35000.0 === $inv['ttc'] && abs( $inv['tva'] - 5588.24 ) < 0.01, 'the account opens its invoice (TVA 5 588,24 DA)' );
wp_set_current_user( 0 );
$o2 = Orders::get( Orders::create( $co, $u_co, 'plan', 'pro', 'month' ) );
Orders::approve( $o2 );
smsub_assert( abs( strtotime( Subscriptions::plan_of( $c_co )['sub']->paid_until . ' UTC' ) - strtotime( '+13 months' ) ) < 3 * DAY_IN_SECONDS, 'paying again for the same plan extends it' );
$o3 = Orders::get( Orders::create( $co, $u_co, 'plan', 'business', 'month' ) );
Orders::reject( $o3, 'Montant non reçu' );
smsub_assert( 'rejected' === Orders::get( $o3->id )->status && 'pro' === Subscriptions::plan_of( $c_co )['code'], 'refused payment: nothing changes' );

echo "\n4. AI credits\n";
global $wpdb;
for ( $i = 0; $i < 5; $i++ ) {
	$wpdb->insert( $wpdb->prefix . 'sm_ai_usage', array( 'feature' => 'listing', 'company_id' => $c_cli, 'status' => 'ok', 'created_at' => current_time( 'mysql', true ) ) );
}
$st = Credits::status( $c_cli );
smsub_assert( 5 === $st['monthly'] && 5 === $st['used'] && 0 === $st['total_left'], 'a client gets 5 credits a month; all used' );
smsub_assert( is_wp_error( Limits::ai_can_use( true, 'listing', $c_cli ) ), 'no credit left: the AI refuses' );
$po = Orders::get( Orders::create( Companies::get( $c_cli ), $u_cli, 'pack', 'ai50' ) );
smsub_assert( 1000.0 === (float) $po->amount_ttc, 'a client can buy a pack of 50 credits (1 000 DA)' );
Orders::approve( $po );
smsub_assert( 50 === Credits::balance( $c_cli ) && true === Limits::ai_can_use( true, 'listing', $c_cli ), 'credits added: the AI works again' );
$wpdb->insert( $wpdb->prefix . 'sm_ai_usage', array( 'feature' => 'listing', 'company_id' => $c_cli, 'status' => 'ok', 'created_at' => current_time( 'mysql', true ) ) );
Credits::on_used( 'listing', $c_cli );
smsub_assert( 49 === Credits::balance( $c_cli ), 'beyond the monthly credits, one bought credit is used' );
smsub_assert( 50 === Credits::status( $c_co )['monthly'], 'Pro: 50 credits a month' );

echo "\n5. End of a plan: over-limit listings paused, oldest first\n";
for ( $i = 0; $i < 4; $i++ ) {
	smsub_listing( $c_co, 800, 'products', 2 );
}
$wpdb->update( Subscriptions::table(), array( 'ends_at' => gmdate( 'Y-m-d H:i:s', time() - 60 ) ), array( 'company_id' => $c_co, 'status' => 'active' ) );
$ended = Subscriptions::expire();
smsub_assert( in_array( $c_co, $ended, true ) && 'free' === Subscriptions::plan_of( $c_co )['code'], 'the plan ends: back on the free plan' );
$online = SM\Products\Products::online_totals( $c_co );
$paused = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_products WHERE company_id = %d AND status = 'paused'", $c_co ) );
smsub_assert( 5 >= $online['listings'] && $online['weight'] <= 10000 && $paused >= 4 && 1 === $online['buildings'], 'within the free plan again: the rest is paused, nothing deleted' );
$oldest = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$wpdb->prefix}sm_products WHERE company_id = %d AND market = 'products' ORDER BY published_at ASC LIMIT 1", $c_co ) );
smsub_assert( 'paused' === $oldest, 'the oldest listing is paused first' );

echo "\n6. Founding members\n";
update_option( Settings::OPTION, array_merge( (array) $settings_before, array( 'founders_on' => 1, 'founders_slots' => 1000, 'founders_level' => 2 ) ), false );
list( $u_f, $c_f ) = smsub_account( 'founder', 'professional', array( 'trade_welder' ) );
smsub_assert( 0 === Founders::maybe_grant( $c_f ), 'not yet: documents not checked' );
Companies::raise_level( $c_f, 2 );
$fp = Subscriptions::plan_of( $c_f );
smsub_assert( 'artisan_pro' === $fp['code'] && $fp['sub']->founder && abs( strtotime( $fp['sub']->ends_at . ' UTC' ) - strtotime( '+6 months' ) ) < 120, 'documents checked: Artisan Pro free for 6 months' );
smsub_assert( 15000.0 === Founders::locked_price( $c_f, 'artisan_pro', 'year' ) && 0 === Founders::maybe_grant( $c_f ), 'price kept for later; the offer is given once' );
smsub_assert( 3 === count( (array) apply_filters( 'sm_theme_data_plans', null )['classes']['professional'] ), 'public plans for the pricing page' );

// ---- Plans page by kind of account, versions of the other plugins (0.1.1) ----
wp_set_current_user( 0 );
smsub_assert( null === apply_filters( 'sm_theme_data_plans', null )['viewer'], 'visitor not signed in: no account on the plans page' );
list( $u_v ) = smsub_account( 'viewer', 'professional', array( 'trade_welder' ) );
wp_set_current_user( $u_v );
$pd  = apply_filters( 'sm_theme_data_plans', null );
smsub_assert( 'professional' === $pd['viewer']['class'] && $pd['viewer']['has_plans'] && 'free' === $pd['viewer']['plan'] && true === $pd['classes']['professional'][0]['current'] && ! in_array( true, array_column( $pd['classes']['company'], 'current' ), true ), 'signed in: kind of account and plan in force marked on its own tab only' );
wp_set_current_user( 0 );
smsub_assert( SM\Subscriptions\Compat::ready( 'products' ) && SM\Subscriptions\Compat::ready( 'core' ) && array() === SM\Subscriptions\Compat::outdated(), 'plugins in the needed versions' );
smsub_assert( ! SM\Subscriptions\Compat::ready( 'nothing' ), 'unknown plugin is not ready' );

smsub_cleanup();
null === $settings_before ? delete_option( Settings::OPTION ) : update_option( Settings::OPTION, $settings_before, false );
$t = $GLOBALS['smsub_t'];
printf( "\n%d passed, %d failed\n", $t['pass'], $t['fail'] );
if ( $t['fail'] ) {
	echo "FAILED:\n  - " . implode( "\n  - ", $t['fails'] ) . "\n";
	exit( 1 );
}
