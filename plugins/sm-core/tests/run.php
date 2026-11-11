<?php
/**
 * sm-core test suite. Runs inside WordPress with WP-CLI:
 *
 *   wp eval-file wp-content/plugins/sm-core/tests/run.php
 *
 * Use a TEST site only: it creates and deletes companies and users prefixed "smtest".
 * Exit code 1 when a test fails.
 *
 * @package SM\Core
 */

use SM\Core\Accounts\Companies;
use SM\Core\Accounts\PhoneVerification;
use SM\Core\Accounts\Wilayas;
use SM\Core\I18n\Language;
use SM\Core\Permissions\Permissions;
use SM\Core\Support\Crypto;
use SM\Core\Support\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$GLOBALS['sm_t'] = array( 'pass' => 0, 'fail' => 0, 'fails' => array() );

function sm_assert( $cond, $label ) {
	if ( $cond ) {
		$GLOBALS['sm_t']['pass']++;
		echo "  ✓ {$label}\n";
	} else {
		$GLOBALS['sm_t']['fail']++;
		$GLOBALS['sm_t']['fails'][] = $label;
		echo "  ✗ {$label}\n";
	}
}

function sm_section( $title ) {
	echo "\n{$title}\n";
}

// Capture e-mails instead of sending them.
$GLOBALS['sm_mails'] = array();
add_filter(
	'pre_wp_mail',
	static function ( $null, $atts ) {
		$GLOBALS['sm_mails'][] = $atts;
		return true;
	},
	10,
	2
);

/** Create a test user + company. */
function sm_test_company( $suffix, $class, array $activities, $level = 0 ) {
	$email = "smtest-{$suffix}@example.test";
	$uid   = wp_insert_user( array( 'user_login' => $email, 'user_email' => $email, 'user_pass' => wp_generate_password( 16 ), 'role' => 'sm_member', 'display_name' => "Test {$suffix}" ) );
	$cid   = Companies::create( array( 'account_class' => $class, 'name' => "SMTEST {$suffix}", 'wilaya' => '28', 'email' => $email ), $uid, $activities );
	if ( $level ) {
		Companies::set_admin_fields( $cid, array( 'verification_level' => $level ) );
	}
	return array( $uid, $cid );
}

function sm_test_cleanup() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/user.php';
	$ids = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sm_companies WHERE name LIKE 'SMTEST %'" );
	foreach ( $ids as $id ) {
		foreach ( array( 'company_members', 'company_activities', 'verifications' ) as $t ) {
			$wpdb->delete( $wpdb->prefix . 'sm_' . $t, array( 'company_id' => $id ) );
		}
		$wpdb->delete( $wpdb->prefix . 'sm_enquiries', array( 'to_company_id' => $id ) );
		$wpdb->delete( $wpdb->prefix . 'sm_companies', array( 'id' => $id ) );
	}
	foreach ( get_users( array( 'search' => 'smtest-*', 'search_columns' => array( 'user_email' ) ) ) as $u ) {
		$wpdb->delete( $wpdb->prefix . 'sm_notifications', array( 'user_id' => $u->ID ) );
		wp_delete_user( $u->ID );
	}
}

sm_test_cleanup();

/* ------------------------------------------------------------------ */
sm_section( '1. Installation' );
foreach ( array( 'companies', 'company_members', 'company_activities', 'verifications', 'wilayas', 'translations', 'media_links', 'listing_index', 'notifications', 'enquiries', 'audit_log' ) as $t ) {
	sm_assert( null !== $GLOBALS['wpdb']->get_var( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->prefix}sm_{$t}" ), "table sm_{$t} exists" );
}
sm_assert( 69 === count( Wilayas::all() ), '69 wilayas loaded' );
sm_assert( 'Bou Saâda' === Wilayas::name( '68', 'fr' ) && 'المسيلة' === Wilayas::name( '28', 'ar' ), 'wilaya names in FR and AR' );
sm_assert( (bool) get_role( 'sm_member' ) && (bool) get_role( 'sm_moderator' ), 'roles sm_member and sm_moderator' );
sm_assert( get_role( 'administrator' )->has_cap( 'sm_moderate' ), 'administrators can moderate' );

/* ------------------------------------------------------------------ */
sm_section( '2. Permission matrix (default rights)' );
list( $u_man, $c_man )   = sm_test_company( 'manufacturer', 'company', array( 'manufacturer' ), 1 );
list( $u_sup, $c_sup )   = sm_test_company( 'supplier', 'company', array( 'supplier' ), 1 );
list( $u_ins, $c_ins )   = sm_test_company( 'installer', 'company', array( 'construction' ), 1 );
list( $u_own, $c_own )   = sm_test_company( 'owner', 'client', array(), 1 );
list( $u_rec, $c_rec )   = sm_test_company( 'recycler', 'company', array( 'recycler' ), 1 );
list( $u_pro, $c_pro )   = sm_test_company( 'welder', 'professional', array( 'trade_welder' ), 1 );
list( $u_eng, $c_eng )   = sm_test_company( 'engineer', 'engineer', array( 'engineer' ), 3 );
list( $u_new, $c_new )   = sm_test_company( 'unverified', 'company', array( 'supplier' ), 0 );

$expected = array(
	//                       buy  prod reuse build rfqC rfqO fab  req  misR misW
	$c_man => array( 1, 1, 1, 1, 1, 1, 1, 1, 1, 0 ),
	$c_sup => array( 1, 1, 1, 1, 1, 1, 0, 1, 1, 0 ),
	$c_ins => array( 1, 1, 1, 1, 1, 1, 1, 1, 1, 0 ),
	$c_own => array( 1, 1, 1, 1, 1, 0, 0, 1, 1, 0 ),
	$c_rec => array( 1, 1, 1, 1, 1, 1, 0, 1, 1, 0 ),
	$c_pro => array( 1, 1, 1, 1, 1, 1, 1, 1, 1, 0 ),
	$c_eng => array( 0, 0, 0, 0, 0, 0, 0, 0, 0, 1 ),
);
$caps  = array( 'buy', 'listing.products', 'listing.reuse', 'listing.buildings', 'rfq.create', 'rfq.offer', 'service.fabrication', 'service.request', 'mission.request', 'mission.work' );
$names = array( $c_man => 'manufacturer', $c_sup => 'supplier', $c_ins => 'construction company', $c_own => 'client', $c_rec => 'recycler', $c_pro => 'professional welder', $c_eng => 'partner engineer' );
foreach ( $expected as $cid => $row ) {
	$ok = true;
	foreach ( $caps as $i => $cap ) {
		if ( (bool) $row[ $i ] !== sm_can( $cap, $cid ) ) {
			$ok = false;
			echo "      mismatch: {$names[$cid]} / {$cap}\n";
		}
	}
	sm_assert( $ok, "{$names[$cid]}: all 10 rights as in the architecture matrix" );
}
sm_assert( sm_can( 'buy', $c_man ) && sm_can( 'listing.products', $c_man ), 'one company account both buys and sells' );
sm_assert( sm_can( 'buy', $c_pro ) && sm_can( 'listing.products', $c_pro ), 'a professional both buys and sells' );

sm_section( '3. Partner engineer hard rule' );
foreach ( array( 'buy', 'listing.products', 'listing.reuse', 'listing.buildings', 'rfq.create', 'rfq.offer', 'service.fabrication', 'service.request', 'mission.request', 'order.create' ) as $cap ) {
	$r = sm_can_explain( $cap, $c_eng );
	sm_assert( is_wp_error( $r ) && 'sm_class_forbidden' === $r->get_error_code(), "engineering account refused: {$cap}" );
}
// Admin tries to grant trading rights to engineers through the settings…
$settings                       = get_option( 'sm_core_settings' );
$hacked                         = $settings;
$hacked['rights']['engineer'][] = 'listing.products';
$hacked['rights']['engineer'][] = 'buy';
update_option( 'sm_core_settings', $hacked );
sm_assert( ! sm_can( 'listing.products', $c_eng ) && ! sm_can( 'buy', $c_eng ), 'rights matrix cannot grant trading to engineers' );
sm_assert( ! in_array( 'buy', Permissions::sanitize_rights( $hacked['rights'] )['engineer'], true ), 'admin matrix sanitizer strips trading rights from engineers' );
update_option( 'sm_core_settings', $settings );
// …or through a plan filter.
$grant_all = static fn() => true;
add_filter( 'sm_entitlement', $grant_all, 99 );
sm_assert( ! sm_can( 'rfq.offer', $c_eng ), 'plan filter cannot grant trading to engineers' );
remove_filter( 'sm_entitlement', $grant_all, 99 );
sm_assert( Companies::activities( 'engineer' ) === array( 'engineer' ), 'partner engineer accounts can only have the engineer activity' );
sm_assert( Companies::activities( 'client' ) === array( 'client' ) && Companies::get( $c_own )->activities === array( 'client' ), 'client accounts get the client activity' );
Companies::set_activities( $c_eng, array( 'engineer', 'manufacturer', 'supplier' ) );
sm_assert( Companies::get( $c_eng )->activities === array( 'engineer' ), 'company types cannot be added to a partner engineer account' );

sm_section( '4. Verification levels, suspension, plan' );
$r = sm_can_explain( 'listing.products', $c_new );
sm_assert( is_wp_error( $r ) && 'sm_verification_required' === $r->get_error_code(), 'unverified account cannot publish (level 1 required)' );
sm_assert( sm_can( 'buy', $c_new ), 'unverified account can still buy / contact' );
Companies::set_admin_fields( $c_sup, array( 'status' => 'suspended' ) );
$r = sm_can_explain( 'buy', $c_sup );
sm_assert( is_wp_error( $r ) && 'sm_suspended' === $r->get_error_code(), 'suspended account loses every right' );
Companies::set_admin_fields( $c_sup, array( 'status' => 'active' ) );
$deny = static fn( $allowed, $cap ) => 'listing.buildings' === $cap ? new WP_Error( 'sm_plan_limit', 'limit' ) : $allowed;
add_filter( 'sm_entitlement', $deny, 10, 2 );
sm_assert( ! sm_can( 'listing.buildings', $c_sup ) && sm_can( 'listing.products', $c_sup ), 'plan entitlement filter can refuse one right' );
remove_filter( 'sm_entitlement', $deny, 10 );
sm_assert( is_wp_error( sm_can_explain( 'buy', 999999 ) ), 'unknown company has no right' );
Companies::raise_level( $c_eng, 1 );
sm_assert( 3 === (int) Companies::get( $c_eng )->verification_level, 'raise_level never lowers a level' );

/* ------------------------------------------------------------------ */
sm_section( '5. Companies' );
$c = Companies::get( $c_man );
sm_assert( 'smtest-manufacturer' === $c->slug, 'slug generated from the name' );
list( , $c_dup ) = sm_test_company( 'dup', 'company', array( 'supplier' ) );
$dup2            = Companies::create( array( 'account_class' => 'company', 'name' => 'SMTEST dup', 'wilaya' => '16' ), $u_man, array( 'supplier' ) );
sm_assert( 'smtest-dup-2' === Companies::get( $dup2 )->slug, 'duplicate names get unique slugs' );
sm_assert( is_wp_error( Companies::create( array( 'account_class' => 'company', 'name' => 'SMTEST none' ), $u_man, array() ) ), 'a business account needs at least one activity' );
Companies::update_profile( $c_man, array( 'rc_number' => '28/00-1234567B19', 'nif' => '001928012345678', 'account_class' => 'engineer', 'verification_level' => 3 ) );
$c     = Companies::get( $c_man );
$legal = Companies::legal_numbers( $c );
sm_assert( '28/00-1234567B19' === $legal['rc_number'] && '001928012345678' === $legal['nif'], 'RC and NIF stored and read back' );
sm_assert( false === strpos( (string) $c->nif, '001928012345678' ), 'NIF is encrypted in the database' );
sm_assert( 'company' === $c->account_class && 1 === (int) $c->verification_level, 'profile update cannot change class or verification level' );
sm_assert( 'x' === Crypto::decrypt( Crypto::encrypt( 'x' ) ), 'encryption round trip' );
sm_assert( Companies::can_manage( $c_man, $u_man ) && ! Companies::can_manage( $c_man, $u_sup ), 'only members (owner/manager) manage their company' );
$q = Companies::query( array( 'class' => 'professional', 'search' => 'SMTEST' ) );
sm_assert( 1 === $q['total'] && (int) $q['items'][0]->id === $c_pro, 'directory query filters by class' );
$q   = Companies::query( array( 'class' => 'public', 'search' => 'SMTEST', 'per_page' => 100 ) );
$ids = array_map( static fn( $x ) => (int) $x->id, $q['items'] );
sm_assert( in_array( $c_pro, $ids, true ) && in_array( $c_man, $ids, true ) && ! in_array( $c_own, $ids, true ) && ! in_array( $c_eng, $ids, true ), 'public directory lists companies and professionals, never clients or engineers' );
sm_assert( null === SM\Core\Theme\Bridge::company( null, array( 'slug' => Companies::get( $c_own )->slug ) ) && null === SM\Core\Theme\Bridge::company( null, array( 'slug' => Companies::get( $c_eng )->slug ) ) && is_array( SM\Core\Theme\Bridge::company( null, array( 'slug' => Companies::get( $c_pro )->slug ) ) ), 'clients and engineers have no public page' );
$types = SM\Core\Theme\Bridge::account_types( null );
sm_assert( isset( $types['classes']['professional']['activities']['trade_welder'] ) && ! isset( $types['classes']['company']['activities']['project_owner'] ) && false === $types['classes']['client']['public'], 'account types resource for the theme' );
$q = Companies::query( array( 'activity' => 'recycler', 'search' => 'SMTEST' ) );
sm_assert( 1 === $q['total'] && (int) $q['items'][0]->id === $c_rec, 'directory query filters by activity' );

/* ------------------------------------------------------------------ */
sm_section( '6. Forms helpers' );
sm_assert( '0661234567' === Forms::normalize_mobile( '+213 661 23 45 67' ), 'mobile +213 normalized' );
sm_assert( '0771234567' === Forms::normalize_mobile( '07 71 23 45 67' ), 'mobile with spaces normalized' );
sm_assert( '' === Forms::normalize_mobile( '0212345678' ), 'landline rejected as mobile' );
sm_assert( '' === Forms::normalize_mobile( '066123' ), 'short number rejected' );
$key = 'smtest-' . wp_generate_password( 6, false );
$ok  = Forms::rate_limit( $key, 2, 60 ) && Forms::rate_limit( $key, 2, 60 ) && ! Forms::rate_limit( $key, 2, 60 );
sm_assert( $ok, 'rate limit blocks the 3rd attempt of 2' );

/* ------------------------------------------------------------------ */
sm_section( '7. Contact verification code' );
$GLOBALS['sm_mails'] = array();
update_user_meta( $u_new, 'sm_phone', '0551234567' );
sm_assert( true === PhoneVerification::send_code( $u_new ), 'code sent' );
sm_assert( 1 === count( $GLOBALS['sm_mails'] ) && false !== strpos( $GLOBALS['sm_mails'][0]['message'], (string) get_user_meta( $u_new, 'sm_otp_dev', true ) ), 'code e-mailed (no SMS gateway configured)' );
sm_assert( is_wp_error( PhoneVerification::send_code( $u_new ) ), 'resend blocked for 60 seconds' );
$state = get_user_meta( $u_new, PhoneVerification::META, true );
sm_assert( strlen( $state['hash'] ) === 64 && empty( $state['code'] ), 'code stored hashed only' );
$r = PhoneVerification::verify( $u_new, '000000' );
sm_assert( is_wp_error( $r ) && 'sm_wrong' === $r->get_error_code(), 'wrong code refused' );
sm_assert( true === PhoneVerification::verify( $u_new, get_user_meta( $u_new, 'sm_otp_dev', true ) ), 'right code accepted' );
sm_assert( 1 === (int) Companies::get( $c_new )->verification_level && sm_can( 'listing.products', $c_new ), 'level 1 reached: the account can publish' );
// Expiry and attempts.
delete_user_meta( $u_own, PhoneVerification::META );
PhoneVerification::send_code( $u_own );
$state            = get_user_meta( $u_own, PhoneVerification::META, true );
$state['expires'] = time() - 1;
update_user_meta( $u_own, PhoneVerification::META, $state );
$r = PhoneVerification::verify( $u_own, get_user_meta( $u_own, 'sm_otp_dev', true ) );
sm_assert( is_wp_error( $r ) && 'sm_expired' === $r->get_error_code(), 'expired code refused' );
$state['expires']  = time() + 600;
$state['attempts'] = 5;
update_user_meta( $u_own, PhoneVerification::META, $state );
$r = PhoneVerification::verify( $u_own, get_user_meta( $u_own, 'sm_otp_dev', true ) );
sm_assert( is_wp_error( $r ) && 'sm_tries' === $r->get_error_code(), 'code locked after 5 wrong attempts' );

/* ------------------------------------------------------------------ */
sm_section( '8. Languages' );
sm_assert( 'fr' === Language::default_language(), 'default language is French' );
$home = untrailingslashit( get_option( 'home' ) );
sm_assert( Language::url_for( $home . '/produits/?category=tubes', 'ar' ) === $home . '/ar/produits/?category=tubes', 'URL to Arabic adds /ar/ and keeps the query' );
sm_assert( Language::url_for( $home . '/ar/produits/', 'fr' ) === $home . '/produits/', 'URL to French removes /ar/' );
sm_assert( Language::url_for( $home . '/ar/produits/', 'ar' ) === $home . '/ar/produits/', 'no double prefix' );
sm_assert( Language::url_for( 'https://example.org/x/', 'ar' ) === 'https://example.org/x/', 'external URLs untouched' );
sm_assert( Language::url_for( $home . '/wp-admin/admin-post.php', 'ar' ) === $home . '/wp-admin/admin-post.php', 'wp-admin URLs untouched' );
update_user_meta( $u_eng, Language::META, 'ar' );
$GLOBALS['sm_mails'] = array();
SM\Core\Notifications\Notifications::notify( array( $u_eng ), 'verification.approved' );
$mail = $GLOBALS['sm_mails'][0] ?? array( 'message' => '', 'subject' => '' );
sm_assert( false !== strpos( $mail['message'], 'dir="rtl"' ), 'e-mail to an Arabic-speaking user is right-to-left' );
$ar_mo = file_exists( SM_CORE_DIR . '/languages/sm-core-ar.mo' );
sm_assert( ! $ar_mo || preg_match( '/\p{Arabic}/u', $mail['subject'] ), 'e-mail subject translated into Arabic' );
sm_assert( 'fr' === Language::current(), 'language restored after sending' );

/* ------------------------------------------------------------------ */
sm_section( '9. Notifications and enquiries' );
sm_assert( 1 <= SM\Core\Notifications\Notifications::unread_count( $u_eng ), 'notification stored for the dashboard' );
SM\Core\Notifications\Notifications::mark_read( $u_eng );
sm_assert( 0 === SM\Core\Notifications\Notifications::unread_count( $u_eng ), 'mark all as read' );

/* ------------------------------------------------------------------ */
sm_section( '10. REST API' );
wp_set_current_user( 0 );
$res = rest_do_request( new WP_REST_Request( 'GET', '/sm/v1/reference/wilayas' ) );
sm_assert( 200 === $res->get_status() && 69 === count( $res->get_data() ), 'GET /reference/wilayas (public)' );
$req = new WP_REST_Request( 'GET', '/sm/v1/companies' );
$req->set_param( 'search', 'SMTEST' );
$req->set_param( 'class', 'professional' );
$res = rest_do_request( $req );
sm_assert( 200 === $res->get_status() && 1 === count( $res->get_data() ), 'GET /companies?class=professional' );
$data = $res->get_data()[0];
sm_assert( ! isset( $data['nif'] ) && ! isset( $data['rc_number'] ) && ! isset( $data['email'] ), 'public company data has no private fields' );
$res = rest_do_request( new WP_REST_Request( 'GET', '/sm/v1/me' ) );
sm_assert( 401 === $res->get_status(), 'GET /me requires authentication' );
wp_set_current_user( $u_eng );
$res = rest_do_request( new WP_REST_Request( 'GET', '/sm/v1/me' ) );
$me  = $res->get_data();
sm_assert( 200 === $res->get_status() && false === $me['capabilities']['buy'] && true === $me['capabilities']['mission.work'], 'GET /me returns the rights map' );
$res = rest_do_request( new WP_REST_Request( 'GET', '/sm/v1/me/can/listing.products' ) );
sm_assert( false === $res->get_data()['allowed'] && 'sm_class_forbidden' === $res->get_data()['reason'], 'GET /me/can explains a refusal' );
wp_set_current_user( $u_sup );
$req = new WP_REST_Request( 'PATCH', '/sm/v1/me/company' );
$req->set_header( 'content-type', 'application/json' );
$req->set_body( wp_json_encode( array( 'about_fr' => 'Distributeur test', 'account_class' => 'engineer', 'verification_level' => 3 ) ) );
$res = rest_do_request( $req );
$cs  = Companies::get( $c_sup );
sm_assert( 200 === $res->get_status() && 'Distributeur test' === $cs->about_fr && 'company' === $cs->account_class && 1 === (int) $cs->verification_level, 'PATCH /me/company updates the profile only' );
wp_set_current_user( $u_man );
$req = new WP_REST_Request( 'PATCH', '/sm/v1/me/company' );
$req->set_header( 'content-type', 'application/json' );
$req->set_body( wp_json_encode( array( 'activities' => array( 'manufacturer', 'engineer' ) ) ) );
rest_do_request( $req );
sm_assert( Companies::get( $c_man )->activities === array( 'manufacturer' ), 'a business account cannot add the engineer activity' );
wp_set_current_user( 0 );

/* ------------------------------------------------------------------ */
sm_section( '11. Routes' );
$url = sm_url( 'listing', array( 'id' => 302 ) );
sm_assert( (bool) preg_match( '#/annonce/302/$#', $url ), 'listing URL is /annonce/302/' );
sm_assert( (bool) preg_match( '#/entreprise/smtest-engineer/$#', sm_url( 'company', array( 'c' => 'smtest-engineer' ) ) ), 'company URL is /entreprise/{slug}/' );
sm_assert( (bool) preg_match( '#/compte/annonces/$#', sm_url( 'account', array( 'view' => 'listings' ) ) ), 'dashboard view URL is /compte/annonces/' );

/* ------------------------------------------------------------------ */
sm_section( '12. Company location (map)' );
sm_assert( 2 <= (int) get_option( 'sm_core_db_version' ), 'database migrated to version 2' );
$centres = SM\Core\Accounts\Wilayas::centres();
sm_assert( 69 === count( $centres ) && abs( $centres['16'][0] - 36.754 ) < 0.001 && abs( $centres['16'][1] - 3.059 ) < 0.001, '69 wilaya map centres seeded (Alger 36.754, 3.059)' );
$out_of_box = 0;
foreach ( $centres as $xy ) {
	$out_of_box += is_wp_error( Companies::parse_location( $xy[0], $xy[1] ) ) ? 1 : 0;
}
sm_assert( 0 === $out_of_box, 'every wilaya centre lies inside the Algeria bounds' );
sm_assert( null === Companies::parse_location( '', '' ), 'empty position = no position' );
sm_assert( is_wp_error( Companies::parse_location( '36.7', '' ) ), 'half a position is refused' );
sm_assert( is_wp_error( Companies::parse_location( '48.8566', '2.3522' ) ), 'a position outside Algeria (Paris) is refused' );
sm_assert( is_wp_error( Companies::parse_location( '36.7; DROP', '3.0' ) ), 'non-numeric position is refused' );
$ok = Companies::parse_location( ' 36.19123456 ', '5.41371234' );
sm_assert( is_array( $ok ) && 36.191235 === $ok['lat'] && 5.413712 === $ok['lng'], 'valid position kept with 6 decimals' );

Companies::update_profile( $c_sup, array( 'location' => array( 'lat' => '35.7060', 'lng' => '4.5420' ), 'map_public' => true ) );
$cs = Companies::get( $c_sup );
sm_assert( array( 'lat' => 35.706, 'lng' => 4.542 ) === Companies::public_location( $cs ), 'position saved and public' );
$bad = Companies::update_profile( $c_sup, array( 'location' => array( 'lat' => '10', 'lng' => '4' ) ) );
sm_assert( is_wp_error( $bad ) && abs( Companies::get( $c_sup )->lat - 35.706 ) < 0.0001, 'update with a bad position fails and keeps the old one' );
Companies::update_profile( $c_sup, array( 'map_public' => false ) );
sm_assert( null === Companies::public_location( Companies::get( $c_sup ) ) && null !== Companies::get( $c_sup )->lat, 'hidden map: position kept, not public' );
$res = rest_do_request( new WP_REST_Request( 'GET', '/sm/v1/companies/' . Companies::get( $c_sup )->slug ) );
$data = $res->get_data();
sm_assert( array_key_exists( 'location', $data ) && null === $data['location'], 'REST hides a non-public position' );
Companies::update_profile( $c_sup, array( 'map_public' => true ) );
$res = rest_do_request( new WP_REST_Request( 'GET', '/sm/v1/companies/' . Companies::get( $c_sup )->slug ) );
sm_assert( 35.706 === ( $res->get_data()['location']['lat'] ?? null ), 'REST shows a public position' );
wp_set_current_user( $u_sup );
$req = new WP_REST_Request( 'PATCH', '/sm/v1/me/company' );
$req->set_header( 'content-type', 'application/json' );
$req->set_body( wp_json_encode( array( 'lat' => 48.85, 'lng' => 2.35 ) ) );
sm_assert( 400 === rest_do_request( $req )->get_status(), 'PATCH /me/company refuses a position outside Algeria (400)' );
$req->set_body( wp_json_encode( array( 'lat' => '', 'lng' => '' ) ) );
rest_do_request( $req );
sm_assert( null === Companies::get( $c_sup )->lat, 'PATCH /me/company with empty lat/lng removes the position' );
wp_set_current_user( 0 );
sm_assert( isset( apply_filters( 'sm_theme_data_map', null )['tiles'] ) && 69 === count( apply_filters( 'sm_theme_data_wilaya_centres', null ) ), 'theme receives map settings and wilaya centres' );

/* ------------------------------------------------------------------ */
sm_section( '13. E-mails, templates and activity journal' );
use SM\Core\Audit\AuditLog;
use SM\Core\Mail\Activity;
use SM\Core\Mail\Contact;
use SM\Core\Mail\Events;
use SM\Core\Mail\Mailer;
use SM\Core\Mail\Settings as MailSettings;
use SM\Core\Mail\Templates;
use SM\Core\Notifications\Notifications;

$GLOBALS['sm_mails'] = array();
$GLOBALS['sm_mail_fail'] = false;
// Recording is done by the capture at the top of this file; this one only simulates failures.
$sm_catch = static fn( $null, $atts ) => $GLOBALS['sm_mail_fail'] ? false : $null;
add_filter( 'pre_wp_mail', $sm_catch, 99, 2 );
$mail_settings_before  = get_option( MailSettings::OPTION, null );
$mail_templates_before = get_option( Templates::OPTION, null );
$digest_before         = get_option( Activity::DIGEST_OPTION, null );
delete_option( MailSettings::OPTION );
delete_option( Templates::OPTION );
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$mails = static function () {
	$m = $GLOBALS['sm_mails'];
	$GLOBALS['sm_mails'] = array();
	return $m;
};
$hdr = static fn( $m ) => implode( "\n", (array) $m['headers'] );

sm_assert( array( 'contact@reusteel.com' ) === MailSettings::admin_emails(), 'default administrator address: contact@reusteel.com' );
sm_assert( 'now' === MailSettings::admin_mode( 'account.registered' ) && 'digest' === MailSettings::admin_mode( 'enquiry.sent' ) && MailSettings::user_on( 'account.registered' ) && ! MailSettings::user_on( 'enquiry.sent' ), 'default modes: registration now + welcome, enquiries in the summary' );
sm_assert( 'off' === MailSettings::admin_mode( 'unknown.event' ) && ! MailSettings::user_on( 'auth.locked' ), 'unknown event off; security events have no user e-mail' );
$keys = array_keys( Events::all() );
sm_assert( ! array_diff( array( 'account.registered', 'verification.submitted', 'contact.sent', 'enquiry.sent', 'auth.locked' ), $keys ), 'core events registered' );

sm_assert( 'Bonjour Ali, IPE 270' === Templates::replace( 'Bonjour {nom}, {titre}{inconnu}', array( 'nom' => 'Ali', 'titre' => 'IPE 270' ) ), 'variables replaced, unknown ones empty' );
$html = Templates::body_html( "Ligne 1\nLigne 2\n\nVoir https://example.com/a?b=1&c=2.\n\n{x}", array( 'x' => '<script>alert(1)</script>' ) );
sm_assert( 3 === substr_count( $html, '<p ' ) && false !== strpos( $html, '<br>' ) && false !== strpos( $html, 'href="https://example.com/a?b=1&#038;c=2"' ) && false === strpos( $html, '<script>' ), 'body: paragraphs, line breaks, links, values escaped' );
sm_assert( 'A B' === Templates::subject( "A\n<b>B</b>", array() ), 'subject: one plain line' );

$fr = Templates::default_text( 'event:account.registered:user', 'fr' );
$ar = Templates::default_text( 'event:account.registered:user', 'ar' );
sm_assert( false !== strpos( $fr[0], 'Bienvenue' ) && false !== strpos( $ar[0], 'مرحبًا' ) && false !== strpos( $ar[1], '{entreprise}' ), 'default welcome e-mail translated (FR/AR), variables kept' );
Templates::save( 'event:account.registered:user', 'fr', 'Salut {nom}', '' );
$got = Templates::get( 'event:account.registered:user', 'fr' );
sm_assert( 'Salut {nom}' === $got[0] && $got[1] === $fr[1] && Templates::get( 'event:account.registered:user', 'ar' ) === $ar, 'edited subject used, empty body falls back to the default, other language untouched' );
sm_assert( Templates::is_custom( 'event:account.registered:user' ), 'template marked as edited' );
Templates::reset( 'event:account.registered:user' );
sm_assert( Templates::get( 'event:account.registered:user', 'fr' ) === $fr && ! Templates::is_custom( 'event:account.registered:user' ), 'reset brings back the default' );
sm_assert( null === Templates::default_text( 'event:nope:user', 'fr' ) && array( '{titre}', '{message}' ) === Templates::default_text( 'notice:verification.approved', 'fr' ), 'unknown template refused; notification template defaults to its built-in text' );

// Contact form: admin alert with Reply-To + acknowledgement in the visitor's language.
$mails();
AuditLog::add( 'contact.sent', 'contact', 0, array( 'name' => 'Ali Test', 'email' => 'ali.smtest@example.test', 'phone' => '0550 00 00 00', 'subject' => 'Prix IPE', 'message' => 'Bonjour, combien ?', 'lang' => 'ar' ) );
$m = $mails();
sm_assert( 2 === count( $m ), 'contact message: 2 e-mails' );
sm_assert( 'contact@reusteel.com' === $m[0]['to'] && false !== strpos( $m[0]['subject'], 'Prix IPE' ) && false !== strpos( $hdr( $m[0] ), 'Reply-To: ali.smtest@example.test' ) && false !== strpos( $hdr( $m[0] ), 'From: "ReuSteel" <contact@reusteel.com>' ), 'admin alert: subject, Reply-To = visitor, From = platform' );
sm_assert( 'ali.smtest@example.test' === $m[1]['to'] && false !== strpos( $m[1]['subject'], 'استلمنا' ) && false !== strpos( $m[1]['message'], 'dir="rtl"' ), 'acknowledgement in Arabic, right to left' );
sm_assert( false !== strpos( $m[0]['message'], 'Bonjour, combien ?' ) && false !== strpos( $m[0]['message'], 'Nom' ), 'admin alert in French with the message' );

// Switches.
MailSettings::save( array( 'events' => array( 'contact.sent' => array( 'admin' => 'off', 'user' => false ) ) ) );
AuditLog::add( 'contact.sent', 'contact', 0, array( 'name' => 'Ali Test', 'email' => 'ali.smtest@example.test', 'subject' => 'X', 'message' => 'Y', 'lang' => 'fr' ) );
sm_assert( 0 === count( $mails() ), 'admin alert and acknowledgement can be turned off' );
MailSettings::save( array( 'admins' => 'a.smtest@example.test, b.smtest@example.test', 'admin_lang' => 'ar', 'events' => array( 'contact.sent' => array( 'admin' => 'now', 'user' => false ) ) ) );
AuditLog::add( 'contact.sent', 'contact', 0, array( 'name' => 'Ali Test', 'email' => 'ali.smtest@example.test', 'subject' => 'Z', 'message' => 'Y', 'lang' => 'fr' ) );
$m = $mails();
sm_assert( 2 === count( $m ) && 'b.smtest@example.test' === $m[1]['to'] && false !== strpos( $m[0]['subject'], 'اتصال' ), 'several administrators, alerts in the chosen language (Arabic)' );

// Daily summary.
MailSettings::save( array( 'admins' => 'contact@reusteel.com', 'admin_lang' => 'fr', 'events' => array( 'contact.sent' => array( 'admin' => 'digest', 'user' => false ) ) ) );
Activity::send_digest(); // Starts from now.
$mails();
AuditLog::add( 'contact.sent', 'contact', 0, array( 'name' => 'Nadia Test', 'email' => 'n.smtest@example.test', 'subject' => 'Sujet 1', 'message' => 'Y', 'lang' => 'fr' ) );
AuditLog::add( 'contact.sent', 'contact', 0, array( 'name' => 'Omar Test', 'email' => 'o.smtest@example.test', 'subject' => 'Sujet 2', 'message' => 'Y', 'lang' => 'fr' ) );
sm_assert( 0 === count( $mails() ), 'digest mode: nothing sent immediately' );
$sent = Activity::send_digest();
$m    = $mails();
sm_assert( 1 === $sent && 1 === count( $m ) && false !== strpos( $m[0]['message'], 'Nadia Test' ) && false !== strpos( $m[0]['message'], 'Omar Test' ) && false !== strpos( $m[0]['subject'], 'résumé' ), 'daily summary lists the events since the last one' );
sm_assert( 0 === Activity::send_digest() && 0 === count( $mails() ), 'nothing new: no summary' );
update_option( Activity::DIGEST_OPTION, array( 'last_id' => 0, 'date' => wp_date( 'Y-m-d' ) ) );
sm_assert( false === Activity::maybe_digest(), 'one summary per day at most' );

// E-mail log, failures, resend, verification codes.
$GLOBALS['sm_mail_fail'] = true;
$ok = Mailer::send( 'fail.smtest@example.test', 'fr', 'Échec', '<p>x</p>', array( 'event' => 'test' ) );
$GLOBALS['sm_mail_fail'] = false;
$log = Mailer::query( array( 'q' => 'fail.smtest' ) );
sm_assert( ! $ok && 'failed' === ( $log['items'][0]->status ?? '' ), 'failed e-mail recorded as failed' );
Mailer::send( 'log.smtest@example.test', 'fr', 'Journal', '<p>Bonjour</p>', array( 'event' => 'test' ) );
$row = Mailer::query( array( 'q' => 'log.smtest' ) )['items'][0];
sm_assert( 'sent' === $row->status && false !== strpos( (string) Mailer::get( $row->id )->body, 'Bonjour' ), 'sent e-mail recorded with its content' );
$mails();
sm_assert( Mailer::resend( $row->id ) && 'log.smtest@example.test' === ( $mails()[0]['to'] ?? '' ), 'e-mail sent again from the log' );
Mailer::send( 'otp.smtest@example.test', 'fr', 'Code', '<p>123456</p>', array( 'event' => 'otp' ) );
sm_assert( '' === (string) Mailer::get( Mailer::query( array( 'q' => 'otp.smtest' ) )['items'][0]->id )->body, 'verification codes are never kept in the log' );

// Notification e-mails: switch and rewritten template.
list( $u_n, $c_n ) = sm_test_company( 'mail', 'company', array( 'supplier' ) );
update_user_meta( $u_n, 'sm_locale', 'fr' );
$mails();
Templates::save( 'notice:verification.approved', 'fr', 'Bravo {nom} !', "{message}\n\nL’équipe {site}" );
Notifications::notify( array( $u_n ), 'verification.approved' );
$m = $mails();
sm_assert( 1 === count( $m ) && 'Bravo Test mail !' === $m[0]['subject'] && false !== strpos( $m[0]['message'], 'L’équipe ReuSteel' ), 'notification e-mail uses the rewritten template' );
MailSettings::save( array( 'notices' => array( 'verification.approved' => false ) ) );
Notifications::notify( array( $u_n ), 'verification.approved' );
sm_assert( 0 === count( $mails() ) && 2 === count( Notifications::for_user( $u_n ) ), 'notification e-mail off: in-app notification still created' );

// Journal.
$e = AuditLog::entry( (object) array( 'id' => 0, 'action' => 'contact.sent', 'user_id' => 0, 'company_id' => 0, 'object_type' => 'contact', 'object_id' => 0, 'ip' => '', 'data' => wp_json_encode( array( 'name' => 'Ali', 'subject' => 'Prix' ) ), 'created_at' => '' ) );
sm_assert( false !== strpos( Activity::describe( $e ), 'Ali' ) && false !== strpos( Activity::describe( $e ), 'Prix' ), 'journal sentence of an event' );
$e->action = 'auth.password_changed';
$e2 = clone $e;
$e2->action = 'weird.thing';
sm_assert( 'Mot de passe modifié' === Language::with_language( 'fr', static fn() => Activity::describe( $e ) ) && 'weird.thing' === Activity::describe( $e2 ), 'journal labels for other actions, raw code otherwise' );
sm_assert( 'messages' === Activity::group_of( 'contact.sent' ) && 'security' === Activity::group_of( 'auth.failed' ), 'journal groups' );
$n_all = AuditLog::query( array( 'q' => 'smtest@example.test', 'count' => true ) );
$n_msg = AuditLog::query( array( 'q' => 'smtest@example.test', 'prefixes' => array( 'contact.' ), 'count' => true ) );
$n_ex  = AuditLog::query( array( 'q' => 'smtest@example.test', 'exclude' => array( 'contact.sent' ), 'count' => true ) );
sm_assert( $n_all >= 5 && $n_msg === $n_all && 0 === $n_ex, 'journal filters: text, group prefixes, excluded actions' );
sm_assert( $n_all === AuditLog::query( array( 'q' => 'smtest@example.test', 'from' => wp_date( 'Y-m-d' ), 'to' => wp_date( 'Y-m-d' ), 'count' => true ) ) && 0 === AuditLog::query( array( 'q' => 'smtest@example.test', 'to' => '2000-01-01', 'count' => true ) ), 'journal filters: dates' );

// Contact form validation.
list( $err ) = Contact::validate( array( 'name' => 'A', 'email' => 'x', 'phone' => 'abc', 'subject' => '', 'message' => 'court' ) );
sm_assert( array( 'name', 'email', 'phone', 'subject', 'message' ) === array_keys( $err ), 'contact form: every field checked' );
list( $err, $data ) = Contact::validate( array( 'name' => 'Ali Benali', 'email' => 'ali@example.dz', 'phone' => '', 'subject' => 'Compte', 'message' => 'Bonjour, une question.' ) );
sm_assert( ! $err && 'ali@example.dz' === $data['email'], 'contact form: valid message accepted (phone optional)' );


// Header, signature and social links (0.4.1).
$png = wp_upload_bits( 'sm-test-logo.png', null, base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==' ) );
$att = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => 'sm-test-logo', 'post_status' => 'inherit' ), $png['file'] );
require_once ABSPATH . 'wp-admin/includes/image.php';
wp_update_attachment_metadata( $att, wp_generate_attachment_metadata( $att, $png['file'] ) );
$brand = \SM\Core\Mail\Branding::sanitize( array(
	'header_image' => 999999, 'logo' => $att, 'header_bg' => 'red', 'name' => 'ReuSteel SPA', 'phone' => '+213 550 12 34 56', 'email' => 'pas-une-adresse',
	'website' => 'https://reusteel.com', 'closing' => array( 'fr' => 'Cordialement,', 'ar' => 'مع التحية،' ), 'address' => array( 'fr' => 'M’Sila', 'ar' => 'المسيلة' ), 'signature' => 1,
	'social' => array( 'facebook' => 'https://facebook.com/reusteel', 'instagram' => 'javascript:alert(1)', 'whatsapp' => '0550 12 34 56', 'x' => '' ),
) );
sm_assert( 0 === $brand['header_image'] && $att === $brand['logo'] && '' === $brand['header_bg'] && '' === $brand['email'], 'branding: unknown image, bad colour and bad e-mail refused' );
sm_assert( array( 'facebook' => 'https://facebook.com/reusteel', 'whatsapp' => '213550123456' ) === $brand['social'], 'branding: only valid social links kept; WhatsApp number in international form' );
MailSettings::save( array( 'brand' => $brand ) );
$fr = Mailer::layout( 'fr', '<p>Corps</p>', 'Ali' );
$ar = Mailer::layout( 'ar', '<p>Corps</p>', 'Ali' );
sm_assert( false !== strpos( $fr, 'Cordialement,' ) && false !== strpos( $fr, 'ReuSteel SPA' ) && false !== strpos( $fr, 'M’Sila' ) && false !== strpos( $fr, 'tel:+213550123456' ) && false !== strpos( $fr, 'https://wa.me/213550123456' ) && false !== strpos( $fr, '>Facebook<' ) && false === strpos( $fr, 'javascript' ), 'every e-mail carries the signature, contact details and social links' );
sm_assert( false !== strpos( $ar, 'مع التحية،' ) && false !== strpos( $ar, 'المسيلة' ) && false === strpos( $ar, 'Cordialement' ), 'signature in the language of the e-mail' );
sm_assert( substr_count( $fr, wp_get_attachment_image_url( $att, 'medium' ) ?: wp_get_attachment_url( $att ) ) >= 1, 'logo shown (band and signature)' );
MailSettings::save( array( 'brand' => array_merge( $brand, array( 'header_image' => $att, 'signature' => 0, 'social' => array() ) ) ) );
$fr = Mailer::layout( 'fr', '<p>Corps</p>' );
sm_assert( false !== strpos( $fr, 'width="560"' ) && false === strpos( $fr, 'Cordialement' ) && false === strpos( $fr, 'Facebook' ), 'header image used; signature can be turned off' );
wp_delete_attachment( $att, true );

// Restore.
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->prefix}sm_mail_log WHERE recipient LIKE '%smtest@example.test' OR recipient = 'contact@reusteel.com' AND subject LIKE '%Prix IPE%'" );
$wpdb->query( "DELETE FROM {$wpdb->prefix}sm_audit_log WHERE action = 'contact.sent' AND data LIKE '%smtest@example.test%'" );
foreach ( array( MailSettings::OPTION => $mail_settings_before, Templates::OPTION => $mail_templates_before, Activity::DIGEST_OPTION => $digest_before ) as $opt => $val ) {
	null === $val ? delete_option( $opt ) : update_option( $opt, $val, false );
}
remove_filter( 'pre_wp_mail', $sm_catch, 99 );

/* ------------------------------------------------------------------ */
sm_section( '14. Account types and migration to the 0.6.0 model' );
$types_before = get_option( Companies::TYPES_OPTION, null );
Companies::save_type_settings( array( 'trade_welder' => array( 'fr' => 'Soudeur qualifié', 'ar' => '' ), 'type_test' => array( 'fr' => 'Galvanisation', 'ar' => 'غلفنة' ) ), array( 'dismantler' ), array( 'company' => array( 'type_test' ) ) );
$labels = Companies::activity_labels();
sm_assert( 'Soudeur qualifié' === $labels['trade_welder'] && 'Galvanisation' === $labels['type_test'], 'administrator renames a trade and adds a company type' );
sm_assert( in_array( 'type_test', Companies::activities( 'company' ), true ) && ! in_array( 'dismantler', Companies::activities( 'company' ), true ) && in_array( 'dismantler', Companies::activities( 'company', true ), true ), 'hidden type no longer offered, still known' );
sm_assert( 'company' === Companies::activity_class( 'type_test' ) && sm_can( 'service.fabrication', ( static function () { list( , $id ) = sm_test_company( 'galva', 'company', array( 'type_test' ), 1 ); return $id; } )() ), 'an added company type gets the rights of a contracting company' );
list( , $c_dis ) = sm_test_company( 'dis', 'company', array( 'supplier' ), 1 );
$wpdb->insert( "{$wpdb->prefix}sm_company_activities", array( 'company_id' => $c_dis, 'activity_code' => 'dismantler', 'enabled_at' => current_time( 'mysql', true ) ) );
Companies::flush( $c_dis );
Companies::set_activities( $c_dis, array( 'supplier', 'dismantler' ) );
sm_assert( array( 'dismantler', 'supplier' ) === array_values( array_intersect( array( 'dismantler', 'supplier' ), Companies::get( $c_dis )->activities ) ) && 2 === count( Companies::get( $c_dis )->activities ), 'an account keeps a type the administrator hid' );
null === $types_before ? delete_option( Companies::TYPES_OPTION ) : update_option( Companies::TYPES_OPTION, $types_before, false );

// Old accounts (0.5.0 model) are converted.
list( , $m1 ) = sm_test_company( 'old1', 'company', array( 'supplier' ) );
list( , $m2 ) = sm_test_company( 'old2', 'company', array( 'supplier' ) );
list( , $m3 ) = sm_test_company( 'old3', 'company', array( 'supplier' ) );
$ct = "{$wpdb->prefix}sm_companies";
$at = "{$wpdb->prefix}sm_company_activities";
$wpdb->update( $ct, array( 'account_class' => 'commercial' ), array( 'id' => $m1 ) );
$wpdb->update( $ct, array( 'account_class' => 'commercial' ), array( 'id' => $m2 ) );
$wpdb->update( $ct, array( 'account_class' => 'engineering' ), array( 'id' => $m3 ) );
$wpdb->delete( $at, array( 'company_id' => $m1 ) );
$wpdb->delete( $at, array( 'company_id' => $m2 ) );
foreach ( array( 'installer', 'reuse_dealer', 'project_owner' ) as $code ) {
	$wpdb->insert( $at, array( 'company_id' => $m1, 'activity_code' => $code, 'enabled_at' => current_time( 'mysql', true ) ) );
}
$wpdb->insert( $at, array( 'company_id' => $m2, 'activity_code' => 'project_owner', 'enabled_at' => current_time( 'mysql', true ) ) );
$core_settings = get_option( 'sm_core_settings' );
update_option( 'sm_core_settings', array_merge( (array) $core_settings, array( 'rights' => array( 'installer' => array( 'buy' ) ) ) ) );
SM\Core\Install\Installer::migrate_account_classes();
foreach ( array( $m1, $m2, $m3 ) as $m ) {
	Companies::flush( $m );
}
$a1 = Companies::get( $m1 )->activities;
sort( $a1 );
sm_assert( 'company' === Companies::get( $m1 )->account_class && array( 'construction', 'recycler' ) === $a1, 'commercial account -> company, activities mapped, project owner dropped' );
sm_assert( 'client' === Companies::get( $m2 )->account_class && array( 'client' ) === Companies::get( $m2 )->activities, 'project-owner-only account -> client' );
sm_assert( 'engineer' === Companies::get( $m3 )->account_class && array( 'engineer' ) === Companies::get( $m3 )->activities, 'engineering account -> partner engineer' );
sm_assert( ! isset( get_option( 'sm_core_settings' )['rights'] ) && sm_can( 'buy', $c_man ), 'old rights matrix reset to the new defaults' );
update_option( 'sm_core_settings', $core_settings );

sm_test_cleanup();

$t = $GLOBALS['sm_t'];
printf( "\n%d passed, %d failed\n", $t['pass'], $t['fail'] );
if ( $t['fail'] ) {
	echo "FAILED:\n  - " . implode( "\n  - ", $t['fails'] ) . "\n";
	exit( 1 );
}
