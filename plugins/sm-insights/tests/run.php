<?php
/**
 * SM Insights tests (test site only): wp eval-file wp-content/plugins/sm-insights/tests/run.php
 * Uses an invented type of profile (ZZT) so the figures of the site do not interfere.
 *
 * @package SM\Insights
 */

use SM\Core\Accounts\Companies;
use SM\Insights\Access;
use SM\Insights\Regions;
use SM\Insights\Report;
use SM\Insights\Searches;
use SM\Insights\Summary;

defined( 'ABSPATH' ) || exit;

$GLOBALS['smin_t'] = array( 'pass' => 0, 'fail' => 0, 'fails' => array() );
function smin_assert( $cond, $label ) {
	if ( $cond ) {
		++$GLOBALS['smin_t']['pass'];
		echo "  ✓ $label\n";
	} else {
		++$GLOBALS['smin_t']['fail'];
		$GLOBALS['smin_t']['fails'][] = $label;
		echo "  ✗ $label\n";
	}
}
function smin_cleanup() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/user.php';
	$wpdb->query( "DELETE FROM {$wpdb->prefix}sm_search_counts WHERE term LIKE 'ZZT%' OR term = 'IPE 777'" );
	$wpdb->query( "DELETE FROM {$wpdb->prefix}sm_products WHERE family = 'ZZT'" );
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sm_trade_deals'" ) ) {
		$wpdb->query( "DELETE FROM {$wpdb->prefix}sm_trade_deals WHERE ref LIKE 'ZZT%'" );
	}
	foreach ( $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sm_companies WHERE name LIKE 'SMINTEST %'" ) as $cid ) {
		foreach ( array( 'company_members', 'company_activities', 'verifications', 'notifications' ) as $t ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}sm_{$t} WHERE company_id = %d", $cid ) ); // phpcs:ignore WordPress.DB
		}
		$wpdb->delete( $wpdb->prefix . 'sm_companies', array( 'id' => $cid ) );
	}
	foreach ( get_users( array( 'search' => 'smintest-*', 'search_columns' => array( 'user_email' ) ) ) as $u ) {
		wp_delete_user( $u->ID );
	}
	Report::flush();
}
function smin_product( $cid, array $row ) {
	global $wpdb;
	$d = current_time( 'mysql', true );
	$wpdb->insert( $wpdb->prefix . 'sm_products', $row + array( 'listing_id' => 'Z' . wp_generate_password( 7, false ), 'company_id' => $cid, 'user_id' => 0, 'category' => 'profiles', 'family' => 'ZZT', 'status' => 'published', 'market' => 'products', 'wilaya' => '16', 'qty_mode' => 'bars', 'price_unit' => 'kg', 'created_at' => $d, 'updated_at' => $d, 'published_at' => $d ) );
	return (int) $wpdb->insert_id;
}
function smin_row( $m, $family ) {
	foreach ( $m['prices'] as $r ) {
		if ( $family === $r['family'] ) {
			return $r;
		}
	}
	return null;
}

smin_cleanup();
$before_ai = get_option( 'sm_ai_settings', null );

echo "\n1. Regions\n";
$wil   = require SM_CORE_DIR . '/data/wilayas.php';
$count = array();
foreach ( Regions::map() as $k => $list ) {
	foreach ( $list as $w ) {
		$count[ $w ] = ( $count[ $w ] ?? 0 ) + 1;
	}
}
smin_assert( ! array_diff( array_keys( $wil ), array_keys( $count ) ) && 1 === max( $count ) && count( $count ) === count( $wil ), 'each of the ' . count( $wil ) . ' wilayas is in one region exactly' );
smin_assert( 'centre' === Regions::of( '16' ) && 'east' === Regions::of( '25' ) && 'west' === Regions::of( '31' ) && 'south' === Regions::of( '30' ) && '' === Regions::of( '99' ), 'Alger centre, Constantine east, Oran west, Ouargla south' );

echo "\n2. Searches (who searched is never kept)\n";
smin_assert( array( 'IPE 200', 'IPE' ) === Searches::term( '', '', 'ipe200' ) && array( 'HEA 160', 'HEA' ) === Searches::term( 'HEA', 'HEA 160', '' ), 'a size typed (« ipe200 ») or chosen is counted as « IPE 200 »' );
smin_assert( array( 'UPN', 'UPN' ) === Searches::term( 'UPN', '', '' ) && null === Searches::term( '', '', 'tôle pas chère' ), 'a whole type is counted; a free text is not' );
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 test-insights';
$_SERVER['REMOTE_ADDR']     = '10.1.2.3';
$hits = static fn() => (int) $GLOBALS['wpdb']->get_var( "SELECT SUM(hits) FROM {$GLOBALS['wpdb']->prefix}sm_search_counts WHERE term = 'IPE 777'" );
do_action( 'sm_products_searched', array( 'market' => array( 'products' ), 'family' => 'IPE', 'designation' => '', 'q' => 'IPE 777', 'wilaya' => '16' ) );
do_action( 'sm_products_searched', array( 'market' => array( 'products' ), 'family' => 'IPE', 'designation' => '', 'q' => 'IPE 777', 'wilaya' => '16' ) );
smin_assert( 1 === $hits(), 'a visitor counts once a day for a profile (the second search is not counted)' );
$_SERVER['REMOTE_ADDR'] = '10.1.2.4';
do_action( 'sm_products_searched', array( 'market' => array( 'products' ), 'family' => '', 'designation' => '', 'q' => 'IPE 777', 'wilaya' => '' ) );
$_SERVER['HTTP_USER_AGENT'] = 'Googlebot/2.1';
$_SERVER['REMOTE_ADDR']     = '10.1.2.5';
do_action( 'sm_products_searched', array( 'market' => array( 'products' ), 'family' => '', 'designation' => '', 'q' => 'IPE 777', 'wilaya' => '' ) );
smin_assert( 2 === $hits(), 'another visitor counts; a robot does not' );
$cols = $GLOBALS['wpdb']->get_col( "SHOW COLUMNS FROM {$GLOBALS['wpdb']->prefix}sm_search_counts" );
smin_assert( ! array_intersect( array( 'user_id', 'ip', 'company_id' ), $cols ), 'the table keeps no user, address or company' );

echo "\n3. Averages (internal rule)\n";
smin_assert( null === Report::average( array( 100, 110, 120, 130 ) ) && 120.0 === Report::average( array( 100, 110, 120, 130, 140 ) ), 'no average under 5 listings; 5 listings give one' );
smin_assert( 105.5 === Report::average( array( 1, 100, 101, 102, 103, 108, 109, 110, 111, 9000 ) ), 'from 10 listings, the extreme 10 % are cut (1 and 9 000 DA/kg)' );
smin_assert( 20.0 === Report::change( 120, 100 ) && null === Report::change( 120, null ), 'change in %' );

echo "\n4. Figures of the market\n";
$uid = wp_insert_user( array( 'user_login' => 'smintest_a', 'user_email' => 'smintest-a@example.test', 'user_pass' => wp_generate_password(), 'role' => 'sm_member' ) );
$cid = Companies::create( array( 'account_class' => 'company', 'name' => 'SMINTEST A', 'wilaya' => '16', 'phone' => '0550000001' ), $uid, array( 'supplier' ) );
$u2  = wp_insert_user( array( 'user_login' => 'smintest_b', 'user_email' => 'smintest-b@example.test', 'user_pass' => wp_generate_password(), 'role' => 'sm_member' ) );
$c2  = Companies::create( array( 'account_class' => 'company', 'name' => 'SMINTEST B', 'wilaya' => '31', 'phone' => '0550000002' ), $u2, array( 'supplier' ) );
$base = Report::compute( Report::args( array() ) );
$old  = gmdate( 'Y-m-d H:i:s', time() - 40 * DAY_IN_SECONDS );
foreach ( array( 100, 110, 120, 130, 140 ) as $i => $p ) {
	smin_product( $c2, array( 'designation' => 'ZZT 100', 'price' => $p, 'price_per_kg' => $p, 'total_weight' => 1000 ) );
	smin_product( $c2, array( 'designation' => 'ZZT 100', 'price' => 100, 'price_per_kg' => 100, 'total_weight' => 500, 'status' => 'sold', 'published_at' => $old, 'updated_at' => $old ) );
}
foreach ( array( 70, 80, 90 ) as $p ) {
	smin_product( $c2, array( 'designation' => 'ZZT 100', 'market' => 'reuse', 'price' => $p, 'price_per_kg' => $p, 'total_weight' => 250, 'wilaya' => '31' ) );
}
smin_product( $c2, array( 'designation' => 'ZZT 100 +1', 'market' => 'reuse', 'price' => 60, 'price_per_kg' => 60, 'total_weight' => 250, 'wilaya' => '31' ) );
smin_product( $cid, array( 'designation' => 'ZZT 100', 'price' => 132, 'price_per_kg' => 132, 'total_weight' => 800 ) );
smin_product( $cid, array( 'designation' => 'ZZT 300', 'price' => 0, 'price_per_kg' => 0, 'total_weight' => 2000, 'status' => 'stock' ) );
smin_product( $c2, array( 'designation' => 'ZZT 100', 'price' => 140, 'price_per_kg' => 140, 'total_weight' => 300, 'status' => 'draft' ) );
$deal = smin_product( $c2, array( 'designation' => 'ZZT 200', 'price' => 0, 'price_per_kg' => 0, 'total_weight' => 600, 'status' => 'sold' ) );
global $wpdb;
$now = current_time( 'mysql', true );
for ( $i = 0; $i < 3; $i++ ) {
	$wpdb->insert( $wpdb->prefix . 'sm_trade_deals', array( 'ref' => 'ZZT' . $i, 'kind' => 'order', 'status' => 'accepted', 'product_id' => $deal, 'market' => 'products', 'seller_company_id' => $c2, 'buyer_user_id' => $uid, 'quantity' => 1, 'amount' => 99999, 'created_at' => $now, 'updated_at' => $now ) );
}
for ( $i = 0; $i < 6; $i++ ) {
	Searches::add( 'ZZT 300', 'ZZT', 'products', '16' );
}
Report::flush();
$m   = Report::compute( Report::args( array() ) );
$row = smin_row( $m, 'ZZT' );
smin_assert( $row && abs( $row['new'] - 122 ) < 0.01, 'new: average asked price of the 6 listings online = 122 DA/kg (drafts and listings without price not counted)' );
smin_assert( $row && null === $row['reuse'], 'reuse: 4 listings only, no average (« not enough listings »)' );
smin_assert( $row && abs( $row['new_change'] - 22.0 ) < 0.01, 'change against the period before (sold listings of 40 days ago, 100 DA/kg): +22 %' );
$d100 = array_values( array_filter( $row['details'] ?? array(), static fn( $x ) => 'ZZT 100' === $x['designation'] ) );
smin_assert( $d100 && ! array_filter( $row['details'], static fn( $x ) => false !== strpos( $x['designation'], '+' ) ), 'detail per size; a lot of several sizes is not a size' );
smin_assert( abs( ( $m['weights']['products_t'] - $base['weights']['products_t'] ) - 6.4 ) < 0.11 && abs( ( $m['weights']['reuse_t'] - $base['weights']['reuse_t'] ) - 1.0 ) < 0.11, 'weights on sale: +6,4 t new (online and sold in the period), +1 t reuse; stock and drafts not counted' );
smin_assert( abs( array_sum( array( $m['weights']['products_pct'], $m['weights']['reuse_pct'], $m['weights']['buildings_pct'] ) ) - 100 ) < 0.5, 'the three shares make 100 %' );
smin_assert( in_array( 'ZZT 200', $m['top_sold'], true ) && ! array_filter( $m['top_sold'], 'is_array' ), 'best-selling: the ranking only, names without any count or amount' );
smin_assert( false === strpos( wp_json_encode( $m ), '99999' ) && false === strpos( wp_json_encode( $m ), 'SMINTEST' ), 'no amount of sale and no company name in the figures' );
$t300 = array_values( array_filter( $m['top_searched'], static fn( $x ) => 'ZZT 300' === $x['term'] ) );
$o300 = array_values( array_filter( $m['opportunities'], static fn( $x ) => 'ZZT 300' === $x['term'] ) );
smin_assert( $t300 && 6 === $t300[0]['count'], 'most searched: ZZT 300 with 6 searches' );
smin_assert( $o300 && 0 === $o300[0]['listings'], 'asked, little offered: ZZT 300 (6 searches, no listing)' );
$west = Report::compute( Report::args( array( 'region' => 'west' ) ) );
$wrow = smin_row( $west, 'ZZT' );
smin_assert( ! $wrow || null === $wrow['new'], 'region West: the new listings of Alger are not counted' );
$new = Report::compute( Report::args( array( 'market' => 'new' ) ) );
smin_assert( null === smin_row( $new, 'ZZT' )['reuse'] && 'new' === $new['args']['market'], 'market New: no reuse column' );
smin_assert( Report::args( array( 'period' => '7', 'market' => 'x', 'region' => 'north' ) ) === array( 'period' => '30', 'market' => 'all', 'region' => '' ), 'unknown choices fall back to 30 days, all markets, Algeria' );
foreach ( array( 70, 80 ) as $p ) {
	smin_product( $c2, array( 'designation' => 'ZZT 100', 'market' => 'reuse', 'price' => $p, 'price_per_kg' => $p, 'total_weight' => 250, 'wilaya' => '31' ) );
}
$row = smin_row( Report::compute( Report::args( array() ) ), 'ZZT' );
smin_assert( $row && null !== $row['reuse'] && $row['gap'] < 0, 'reuse: with enough listings, its average and the gap with new steel (negative)' );

echo "\n5. The part of the company (seen by itself only)\n";
$mine = Report::mine( $m, Companies::get( $cid ) );
smin_assert( isset( $mine['prices']['ZZT']['new'] ) && 132.0 === $mine['prices']['ZZT']['new']['price'] && abs( $mine['prices']['ZZT']['new']['diff'] - 8.2 ) < 0.01, 'its ZZT at 132 DA/kg: +8,2 % against the market' );
smin_assert( isset( $mine['stock']['ZZT 300'] ) && 2.0 === $mine['stock']['ZZT 300'], 'opportunity: « you have 2 t of it » (warehouse)' );
smin_assert( '' !== $mine['note'] && false !== strpos( $mine['note'], '8' ), 'sentence on its prices: ' . $mine['note'] );
$m2 = Report::mine( $m, Companies::get( $c2 ) );
smin_assert( ! isset( $m2['stock']['ZZT 300'] ), 'another company does not see this warehouse' );

echo "\n6. Access (Business plan)\n";
wp_set_current_user( 0 );
smin_assert( 'login' === Access::state(), 'visitor: asked to sign in' );
wp_set_current_user( $uid );
$f = static fn() => false;
add_filter( 'sm_insights_access', $f, 99 );
smin_assert( 'upgrade' === Access::state( Companies::get( $cid ) ), 'plan without the report: « Move to the Business plan »' );
remove_filter( 'sm_insights_access', $f, 99 );
$t = static fn() => true;
add_filter( 'sm_insights_access', $t, 99 );
smin_assert( 'ok' === Access::state( Companies::get( $cid ) ), 'plan with the report: open' );
$client = (object) array( 'id' => 0, 'account_class' => 'client' );
smin_assert( 'none' === Access::state( $client ) && ! Access::account( $client ), 'client account: no report' );

echo "\n7. Summary by the AI (simulation)\n";
if ( defined( 'SM_AI_VERSION' ) ) {
	update_option( 'sm_ai_settings', array( 'default_provider' => 'simulation' ) );
	$facts = Summary::facts( $m );
	smin_assert( ! isset( $facts['mine'] ) && false === strpos( wp_json_encode( $facts ), 'SMINTEST' ) && false === strpos( wp_json_encode( $facts ), '"details"' ), 'figures sent to the AI: market only, no company, no detail per size' );
	$pts = Summary::get( $m );
	smin_assert( is_array( $pts ) && count( $pts ) >= 1 && ! preg_grep( '/\d+\s*ventes?/iu', $pts ), 'points written; a number of sales written by the AI is dropped' );
	$n = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_ai_usage WHERE feature = 'report'" );
	Summary::get( $m );
	smin_assert( (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_ai_usage WHERE feature = 'report'" ) === $n, 'kept for the day: no second call to the AI' );
	$d = apply_filters( 'sm_theme_data_insights_report', null, array( 'period' => '90' ) );
	smin_assert( 'ok' === $d['state'] && '90' === $d['args']['period'] && isset( $d['report'], $d['summary'], $d['mine'], $d['options']['region']['south'] ), 'theme data: report, summary, part of the company, choices' );
	null === $before_ai ? delete_option( 'sm_ai_settings' ) : update_option( 'sm_ai_settings', $before_ai );
} else {
	echo "  (sm-ai not active: skipped)\n";
}
remove_filter( 'sm_insights_access', $t, 99 );

wp_set_current_user( 0 );
smin_cleanup();
unset( $_SERVER['HTTP_USER_AGENT'] );
$t = $GLOBALS['smin_t'];
echo "\n{$t['pass']} passed, {$t['fail']} failed\n";
if ( $t['fail'] ) {
	echo 'Failed: ' . implode( ' | ', $t['fails'] ) . "\n";
}
