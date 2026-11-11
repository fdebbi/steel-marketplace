<?php
/**
 * Tests of sm-services. Run on a TEST site only (creates then removes data):
 *   wp eval-file wp-content/plugins/sm-services/tests/run.php
 *
 * Expected weights are read from SM Weight Engine (never typed here), so the
 * tests check the arithmetic of the lot, not the reference tables.
 *
 * @package SM\Services
 */

use SM\Core\Accounts\Companies;
use SM\Services\Catalogue;
use SM\Services\Form;
use SM\Services\Portfolio;
use SM\Services\Presenter;
use SM\Services\Requests;
use SM\Services\Services;
use SM\Services\Settings;
use SM\Services\Spec;
use SM\Services\Theme\Bridge;

defined( 'ABSPATH' ) || exit;

$GLOBALS['sms_t'] = array( 'pass' => 0, 'fail' => 0, 'fails' => array() );
function sms_assert( $cond, $label ) {
	$t = &$GLOBALS['sms_t'];
	if ( $cond ) {
		++$t['pass'];
		echo "  ✓ $label\n";
	} else {
		++$t['fail'];
		$t['fails'][] = $label;
		echo "  ✗ $label\n";
	}
}
function sms_near( $a, $b, $tol = 0.01 ) {
	return abs( (float) $a - (float) $b ) <= $tol;
}
function sms_user( $suffix, $class, array $activities ) {
	$uid = wp_insert_user( array( 'user_login' => 'smstest_' . $suffix, 'user_email' => "smstest-{$suffix}@example.test", 'user_pass' => wp_generate_password(), 'role' => 'sm_member' ) );
	$cid = Companies::create( array( 'account_class' => $class, 'name' => "SMSTEST {$suffix}", 'wilaya' => '28' ), $uid, $activities );
	Companies::raise_level( $cid, 1 );
	return array( $uid, $cid );
}
function sms_cleanup() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sm_companies WHERE name LIKE 'SMSTEST %'" ) as $cid ) {
		foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}sm_services WHERE company_id = %d", $cid ) ) as $sid ) {
			Services::delete( $sid );
		}
		foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}sm_portfolio WHERE company_id = %d", $cid ) ) as $pid ) {
			Portfolio::delete( $pid );
		}
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}sm_service_requests WHERE provider_id = %d OR client_id = %d", $cid, $cid ) );
		foreach ( array( 'company_members', 'company_activities' ) as $t ) {
			$wpdb->delete( $wpdb->prefix . 'sm_' . $t, array( 'company_id' => $cid ) );
		}
		$wpdb->delete( $wpdb->prefix . 'sm_companies', array( 'id' => $cid ) );
	}
	foreach ( get_users( array( 'search' => 'smstest-*', 'search_columns' => array( 'user_email' ) ) ) as $u ) {
		$wpdb->delete( $wpdb->prefix . 'sm_notifications', array( 'user_id' => $u->ID ) );
		wp_delete_user( $u->ID );
	}
}
sms_cleanup();
$suffix = substr( md5( (string) microtime( true ) ), 0, 6 );
list( $u_fab, $c_fab ) = sms_user( 'f' . $suffix, 'company', array( 'manufacturer' ) );
list( $u_ins, $c_ins ) = sms_user( 'i' . $suffix, 'company', array( 'construction' ) );
list( $u_buy, $c_buy ) = sms_user( 'b' . $suffix, 'client', array() );
list( $u_sup, $c_sup ) = sms_user( 's' . $suffix, 'company', array( 'supplier' ) );
list( $u_pro, $c_pro ) = sms_user( 'p' . $suffix, 'professional', array( 'trade_welder', 'trade_erector' ) );
list( $u_eng, $c_eng ) = sms_user( 'e' . $suffix, 'engineer', array( 'engineer' ) );
global $wpdb;
$notes = static fn( $uid, $type ) => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_notifications WHERE user_id = %d AND type = %s", $uid, $type ) );
$fab   = array(
	'kind'         => 'fabrication',
	'category'     => 'cutting',
	'title'        => array( 'fr' => 'Charpente métallique sur mesure' ),
	'description'  => 'Découpe, perçage, soudage et assemblage de charpentes jusqu’à 24 m de portée.',
	'capabilities' => "Poutres PRS jusqu'à 1 200 mm\nÉpaisseurs 4 à 40 mm",
	'equipment'    => "Scie à ruban\nPerceuse à colonne\nPostes MIG",
	'coverage'     => 'list',
	'wilayas'      => array( '28', '19', '99' ),
	'lead_days'    => '15',
	'price_mode'   => 'from',
	'price_from'   => '180',
	'price_unit'   => 'kg',
);

echo "\n1. Catalogues and permissions\n";
$cat = Catalogue::all();
sms_assert( 13 === count( $cat['fabrication']['categories'] ) && ! isset( $cat['engineering'] ), 'one catalogue: 13 fabrication and site services (engineering goes through the platform)' );
sms_assert( array( 'fabrication' ) === Catalogue::allowed_kinds( $c_fab ) && array( 'fabrication' ) === Catalogue::allowed_kinds( $c_ins ), 'manufacturers and contractors publish fabrication and site services' );
sms_assert( array( 'fabrication' ) === Catalogue::allowed_kinds( $c_pro ), 'a professional publishes his services' );
sms_assert( array() === Catalogue::allowed_kinds( $c_eng ), 'a partner engineer publishes no service' );
sms_assert( array() === Catalogue::allowed_kinds( $c_sup ) && array() === Catalogue::allowed_kinds( $c_buy ), 'a supplier or a client publishes no service' );

echo "\n2. Service page checks\n";
$s = Spec::service( $fab );
sms_assert( ! $s['errors'] && array( 'cutting' ) === $s['data']['categories'], 'complete page accepted: one service per page' );
sms_assert( isset( Spec::service( array_merge( $fab, array( 'category' => 'nope' ) ) )['errors']['categories'] ), 'unknown service refused' );
$o = Spec::service( array_merge( $fab, array( 'category' => Catalogue::OTHER, 'custom_service' => 'x' ) ) );
sms_assert( isset( $o['errors']['custom_service'] ), '"Other" needs the name of the service' );
$o = Spec::service( array_merge( $fab, array( 'category' => Catalogue::OTHER, 'custom_service' => '  Pliage   de tôles ' ) ) );
sms_assert( ! $o['errors'] && array( Catalogue::OTHER ) === $o['data']['categories'] && 'Pliage de tôles' === $o['data']['custom_service'], '"Other" with its name accepted'  );
sms_assert( array( '28', '19' ) === $s['data']['wilayas'] && 15 === $s['data']['lead_days'] && 'kg' === $s['data']['price_unit'], 'served wilayas (unknown one dropped), lead time, price from 180 DA/kg' );
$x = Spec::service( array_merge( $fab, array( 'category' => '', 'title' => array(), 'description' => 'court', 'coverage' => 'list', 'wilayas' => array(), 'price_from' => '' ) ) );
sms_assert( isset( $x['errors']['categories'], $x['errors']['description'], $x['errors']['wilayas'], $x['errors']['price_from'] ) && ! isset( $x['errors']['title'] ), 'service, description (15 characters), wilayas and starting price checked; no title to type' );
sms_assert( ! isset( Spec::service( array_merge( $fab, array( 'description' => 'Soudure sur site' ) ) )['errors']['description'] ), 'a 16-character description is accepted' );
$x = Spec::service( array_merge( $fab, array( 'kind' => 'engineering' ) ) );
sms_assert( ! empty( $x['errors'] ), 'the former engineering catalogue is refused' );
$q = Spec::service( array_merge( $fab, array( 'price_mode' => 'quote', 'coverage' => 'all' ) ) );
sms_assert( ! $q['errors'] && array() === $q['data']['wilayas'] && 0.0 === $q['data']['price_from'], '"on quotation" and "all of Algeria"' );

echo "\n3. Publication and lists\n";
$sid = Services::save( 0, $c_fab, $u_fab, $s, 'published' );
$es  = Spec::service( array( 'kind' => 'fabrication', 'category' => 'installation', 'title' => array( 'fr' => 'Soudure et montage sur chantier' ), 'description' => 'Soudeur qualifié, montage de charpentes et réparations sur site dans toute l’Algérie.', 'coverage' => 'all', 'price_mode' => 'quote' ) );
$eid = Services::save( 0, $c_pro, $u_pro, $es, 'published' );
$list = Bridge::services( null, array( 'kind' => 'fabrication' ) );
sms_assert( in_array( $sid, wp_list_pluck( (array) $list, 'id' ), true ) && in_array( $eid, wp_list_pluck( (array) $list, 'id' ), true ), 'company and professional services in the same list' );
sms_assert( in_array( $eid, wp_list_pluck( (array) Bridge::services( null, array( 'kind' => 'fabrication', 'category' => 'installation' ) ), 'id' ), true ) && ! in_array( $sid, wp_list_pluck( (array) Bridge::services( null, array( 'kind' => 'fabrication', 'category' => 'installation' ) ), 'id' ), true ), 'category filter' );
global $wpdb;
$wpdb->insert( $wpdb->prefix . 'sm_services', array( 'company_id' => $c_fab, 'user_id' => $u_fab, 'kind' => 'engineering', 'status' => 'published', 'created_at' => current_time( 'mysql', true ), 'updated_at' => current_time( 'mysql', true ) ) );
$old = (int) $wpdb->insert_id;
\SM\Services\Install\Installer::retire_engineering();
sms_assert( 'retired' === Services::get( $old )->status && null === Bridge::service( null, array( 'id' => $old ) ), 'former engineering pages are retired and never shown' );
Services::delete( $old );
sms_assert( in_array( $sid, wp_list_pluck( (array) Bridge::services( null, array( 'kind' => 'fabrication', 'wilaya' => '19' ) ), 'id' ), true ) && ! in_array( $sid, wp_list_pluck( (array) Bridge::services( null, array( 'kind' => 'fabrication', 'wilaya' => '31' ) ), 'id' ), true ), 'wilaya filter uses the served wilayas' );
sms_assert( in_array( $eid, wp_list_pluck( (array) Bridge::services( null, array( 'kind' => 'fabrication', 'wilaya' => '31' ) ), 'id' ), true ), 'a service covering all Algeria is found in every wilaya' );
$card = Bridge::services( null, array( 'kind' => 'fabrication', 'q' => '24 m de portée' ) )[0];
sms_assert( 'Découpe' === $card['title']['fr'] && 'القص' === $card['title']['ar'] && 'Découpe' === $card['tags'][0]['fr'], 'card data: the title is the name of the service in both languages (search by word)' );
Catalogue::update_item( 'fabrication', 'cutting', array( 'fr' => 'Découpe de profilés' ) );
sms_assert( 'Découpe de profilés' === Presenter::service( Services::get( $sid ) )['title']['fr'], 'renaming the service in the list renames its pages' );
Catalogue::update_item( 'fabrication', 'cutting', array( 'fr' => '' ) );
sms_assert( Services::same_service( $c_fab, 'cutting', '' ) === $sid && null === Services::same_service( $c_fab, 'cutting', '', $sid ) && null === Services::same_service( $c_fab, 'welding', '' ), 'one page per service and per company' );

echo "\n4. Service page and requests\n";
wp_set_current_user( $u_buy );
$page = Bridge::service( null, array( 'id' => $sid ) );
sms_assert( $page['viewer']['can_request'] && false !== strpos( $page['capabilities']['fr'], 'PRS' ) && false !== strpos( $page['equipment']['fr'], 'MIG' ), 'client sees capabilities and equipment, may send a request' );
$r  = Spec::request( array( 'message' => 'Fabrication de 12 portiques IPE 300 pour un hangar de 30 × 18 m.', 'wilaya' => '28', 'budget' => '2500000' ) );
sms_assert( ! $r['errors'], 'request accepted' );
$x = Spec::request( array( 'message' => 'court', 'wilaya' => '99', 'needed_by' => '2000-01-01' ) );
sms_assert( isset( $x['errors']['message'], $x['errors']['wilaya'], $x['errors']['needed_by'] ), 'message, wilaya and date checked' );
$rid = Requests::create( Services::get( $sid ), $c_buy, $u_buy, $r['data'] );
\SM\Core\Notifications\Notifications::notify_company( $c_fab, 'service.request_received', array( 'request_id' => $rid, 'service_id' => $sid, 'company_id' => $c_buy ) );
sms_assert( $notes( $u_fab, 'service.request_received' ) >= 1 && 1 === Requests::count_new( $c_fab ), 'provider notified, one new request' );
$page = Bridge::service( null, array( 'id' => $sid ) );
sms_assert( ! $page['viewer']['can_request'] && $rid === $page['viewer']['open_request'], 'no second request while one is open' );
wp_set_current_user( $u_fab );
sms_assert( '' !== Form::request_block( Services::get( $sid ) ), 'a provider cannot request his own service' );
$qt = Spec::quote( array( 'quote_amount' => '2 350 000', 'quote_days' => '21', 'quote_message' => 'Peinture antirouille comprise.' ) );
sms_assert( ! $qt['errors'] && 2350000.0 === $qt['data']['quote_amount'], 'quote checked (amount with spaces)' );
sms_assert( isset( Spec::quote( array( 'quote_amount' => '0', 'quote_days' => '-1' ) )['errors']['quote_days'] ), 'quote amount and time required' );
Requests::update( $rid, $qt['data'] + array( 'status' => 'quoted' ) );
$recv = Bridge::service_requests( null, array( 'box' => 'received' ) );
sms_assert( 1 === count( $recv['items'] ) && 'quoted' === $recv['items'][0]['status'] && 'SMSTEST' === substr( $recv['items'][0]['other']['name']['fr'], 0, 7 ), 'provider sees the request he answered and who sent it' );
wp_set_current_user( $u_buy );
$sent = Bridge::service_requests( null, array( 'box' => 'sent' ) );
sms_assert( 1 === count( $sent['items'] ) && 2350000.0 === $sent['items'][0]['quote_amount'] && 21 === $sent['items'][0]['quote_days'], 'client sees the quote (amount, time)' );
Requests::update( $rid, array( 'status' => 'accepted' ) );
sms_assert( 'accepted' === Requests::get( $rid )->status && Bridge::service( null, array( 'id' => $sid ) )['viewer']['can_request'], 'quote accepted; a new request is possible afterwards' );
$types = \SM\Core\Notifications\Notifications::types();
sms_assert( isset( $types['service.quote_received'], $types['service.quote_accepted'], $types['service.request_declined'], $types['service.request_cancelled'] ), 'notification texts for every step' );

echo "\n5. Professional profile, portfolio and separation\n";
wp_set_current_user( $u_eng );
sms_assert( ! sm_can( 'listing.products' ) && ! sm_can( 'rfq.offer' ) && ! sm_can( 'service.fabrication' ) && ! sm_can( 'service.request' ), 'partner engineer: no trading, no services' );
wp_set_current_user( $u_pro );
sms_assert( sm_can( 'service.fabrication' ) && sm_can( 'listing.products' ) && sm_can( 'buy' ), 'professional: services, selling and buying' );
\SM\Core\Accounts\Companies::update_profile( $c_pro, array( 'title_fr' => 'Soudeur qualifié', 'profile' => array( 'qualifications' => array( 'Certificat soudeur EN 9606' ), 'specialities' => array( 'Soudure MIG' ), 'experience_years' => 12 ) ) );
$co = \SM\Core\Theme\Bridge::shape_company( \SM\Core\Accounts\Companies::get( $c_pro ) );
sms_assert( 12 === $co['experience'] && 'Certificat soudeur EN 9606' === $co['qualifications'][0]['fr'] && 'professional' === $co['class'], 'profile: title, qualifications, specialities, years of experience' );
$ref = Spec::reference( array( 'title' => array( 'fr' => 'Hangar de stockage 40 × 20 m' ), 'description' => 'Étude complète et plans d’exécution.', 'role' => 'Bureau d’études', 'year' => '2024', 'wilaya' => '28' ) );
sms_assert( ! $ref['errors'], 'reference accepted' );
sms_assert( isset( Spec::reference( array( 'title' => array(), 'year' => '1800' ) )['errors']['year'] ), 'reference title and year checked' );
Portfolio::save( 0, $c_pro, $ref['data'] );
$co_slug = \SM\Core\Accounts\Companies::get( $c_pro )->slug;
$pf = Bridge::portfolio( null, array( 'company' => $co_slug ) );
sms_assert( 1 === count( $pf ) && 2024 === $pf[0]['year'] && 'Bureau d’études' === $pf[0]['role']['fr'], 'portfolio of completed projects shown on the profile' );
$page = Bridge::service( null, array( 'id' => $eid ) );
sms_assert( $page['viewer']['is_owner'] && 1 === count( $page['portfolio'] ) && 'quote' === $page['price_mode'], 'professional service page with the portfolio' );

// 0.3.0: services editable by the administrator; deletion with a replacement.
$cat_before = get_option( Catalogue::OPTION, null );
Catalogue::update_item( 'fabrication', 'welding', array( 'fr' => 'Soudure TIG / MIG', 'desc_fr' => 'Atelier ou chantier', 'order' => -5 ) );
Catalogue::update_item( 'fabrication', 'drilling', array( 'hidden' => true ) );
$def = Catalogue::kind( 'fabrication' );
sms_assert( 'Soudure TIG / MIG' === $def['labels']['welding']['fr'] && 'welding' === array_key_first( $def['categories'] ) && ! isset( $def['categories']['drilling'] ) && isset( $def['labels']['drilling'] ), 'service renamed, ordered first, hidden one still known' );
$lists = Catalogue::term_lists( array() );
$new   = call_user_func( $lists['services_fabrication']['save'], '', array( 'fr' => 'Pliage de tôles', 'ar' => 'ثني الصفائح', 'desc_fr' => '', 'desc_ar' => '', 'order' => 0, 'image_id' => 0 ) );
sms_assert( is_string( $new ) && isset( Catalogue::kind( 'fabrication' )['categories'][ $new ] ), 'service added by the administrator is offered' );
$used = Catalogue::usage( 'installation' );
sms_assert( $used > 0 && is_wp_error( Catalogue::delete_item( 'fabrication', 'installation', '' ) ), 'a used service needs a replacement' );
sms_assert( true === Catalogue::delete_item( 'fabrication', 'installation', 'assembly' ) && 0 === Catalogue::usage( 'installation' ) && ! isset( Catalogue::kind( 'fabrication' )['labels']['installation'] ), 'service deleted, pages moved to the replacement' );
// "Other": published under other services, filed by the administrator.
$oid = Services::save( 0, $c_pro, $u_pro, $o, 'published' );
$card = Presenter::service( Services::get( $oid ) );
sms_assert( 'Pliage de tôles' === $card['tags'][0]['fr'] && in_array( $oid, wp_list_pluck( (array) Bridge::services( null, array( 'kind' => 'fabrication', 'category' => Catalogue::OTHER ) ), 'id' ), true ) && in_array( $oid, wp_list_pluck( (array) Bridge::services( null, array( 'kind' => 'fabrication', 'q' => 'pliage' ) ), 'id' ), true ), '"Other" page shows its name, under "Other services", found by search' );
sms_assert( in_array( 'pliage de tôles', array_map( static fn( $p ) => mb_strtolower( $p['name'] ), Catalogue::proposals( 'fabrication' ) ), true ), 'proposed name listed for the administrator' );
sms_assert( 1 === Catalogue::file_proposal( 'fabrication', 'PLIAGE DE TÔLES', 'cutting' ) && array( 'cutting' ) === Services::get( $oid )->categories && '' === Services::get( $oid )->custom_service, 'administrator attaches it to an existing service' );
Services::delete( $oid );
null === $cat_before ? delete_option( Catalogue::OPTION ) : update_option( Catalogue::OPTION, $cat_before, false );
Catalogue::reset();

wp_set_current_user( 0 );
sms_cleanup();
$t = $GLOBALS['sms_t'];
printf( "\n%d passed, %d failed\n", $t['pass'], $t['fail'] );
if ( $t['fail'] ) {
	echo "FAILED:\n  - " . implode( "\n  - ", $t['fails'] ) . "\n";
	exit( 1 );
}
