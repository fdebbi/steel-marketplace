<?php
/**
 * Tests of sm-engineering. Run on a TEST site only (creates then removes data):
 *   wp eval-file wp-content/plugins/sm-engineering/tests/run.php
 *
 * File uploads themselves are checked by the browser tests (tests/e2e-engineering.py).
 *
 * @package SM\Engineering
 */

use SM\Core\Accounts\Companies;
use SM\Engineering\Engineers;
use SM\Engineering\Files;
use SM\Engineering\Form;
use SM\Engineering\Messages;
use SM\Engineering\Apply;
use SM\Engineering\Missions;
use SM\Engineering\Payments;
use SM\Engineering\Services;
use SM\Engineering\Settings;
use SM\Engineering\Theme\Bridge;

defined( 'ABSPATH' ) || exit;

$GLOBALS['sme_t'] = array( 'pass' => 0, 'fail' => 0, 'fails' => array() );
function sme_assert( $cond, $label ) {
	$t = &$GLOBALS['sme_t'];
	if ( $cond ) {
		++$t['pass'];
		echo "  ✓ $label\n";
	} else {
		++$t['fail'];
		$t['fails'][] = $label;
		echo "  ✗ $label\n";
	}
}
function sme_section( $s ) {
	echo "\n$s\n";
}
function sme_account( $suffix, $class, array $activities = array() ) {
	$uid = wp_insert_user( array( 'user_login' => 'smetest_' . $suffix, 'user_email' => "smetest-{$suffix}@example.test", 'user_pass' => wp_generate_password(), 'role' => 'sm_member', 'display_name' => "Test {$suffix}" ) );
	$cid = Companies::create( array( 'account_class' => $class, 'name' => "SMETEST {$suffix}", 'wilaya' => '28', 'phone' => '0550000000' ), $uid, $activities );
	Companies::raise_level( $cid, 1 );
	return array( $uid, $cid );
}
function sme_cleanup() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sm_companies WHERE name LIKE 'SMETEST %' OR name = 'BET SMETEST'" ) as $cid ) {
		foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}sm_missions WHERE client_id = %d OR engineer_id = %d", $cid, $cid ) ) as $mid ) {
			foreach ( Files::for_mission( $mid ) as $f ) {
				Files::delete( $f->id );
			}
			$wpdb->delete( $wpdb->prefix . 'sm_mission_messages', array( 'mission_id' => $mid ) );
			$wpdb->delete( $wpdb->prefix . 'sm_missions', array( 'id' => $mid ) );
		}
		$wpdb->delete( $wpdb->prefix . 'sm_engineers', array( 'company_id' => $cid ) );
		$wpdb->delete( $wpdb->prefix . 'sm_verifications', array( 'company_id' => $cid ) );
		foreach ( array( 'company_members', 'company_activities' ) as $t ) {
			$wpdb->delete( $wpdb->prefix . 'sm_' . $t, array( 'company_id' => $cid ) );
		}
		$wpdb->delete( $wpdb->prefix . 'sm_companies', array( 'id' => $cid ) );
	}
	foreach ( $wpdb->get_col( "SELECT ID FROM {$wpdb->users} WHERE user_login LIKE 'smetest_%'" ) as $uid ) {
		wp_delete_user( $uid );
	}
}
function sme_profile( array $over = array() ) {
	return array_merge( array( 'title' => 'Ingénieur génie civil — structures', 'diploma' => 'Master structures', 'school' => 'USTHB', 'grad_year' => 2012, 'experience' => 12, 'specialties' => array( 'calc_notes', 'expertise' ), 'bio' => 'Douze ans de charpente métallique : hangars, bâtiments industriels et expertises.' ), $over );
}

sme_cleanup();
$admin = (int) get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) )[0];
$mod_was = get_option( 'sm_engineering_settings', null );

sme_section( '1. Installation and settings' );
global $wpdb;
foreach ( array( 'engineers', 'missions', 'mission_files', 'mission_messages' ) as $t ) {
	sme_assert( (bool) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_{$t}" ) || 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_{$t}" ), "table sm_{$t} exists" );
}
$svc_was = get_option( Services::OPTION, null );
delete_option( Services::OPTION );
$codes = array_keys( Settings::types() );
sme_assert( array( 'studies', 'calc_notes', 'modelling', 'expertise', 'tender_docs', 'site_follow' ) === $codes, 'six engineering services offered, in page order' );
$cn = Services::get( 'calc_notes' );
sme_assert( 'Note de Calcul' === $cn['fr'] && 5 === count( $cn['items'] ) && 'Dimensionnement des assemblages (boulonnés et soudés)' === $cn['items'][2]['fr'] && '' !== $cn['items'][2]['ar'], 'each service has its prestations in French and Arabic' );
sme_assert( 'Notes de calcul' === Settings::types( true )['calculation']['fr'] && 'Notes de calcul' === Settings::type_label( 'calculation' ) && ! isset( Settings::types()['calculation'] ), 'former study types are hidden but keep their name for old missions' );
Settings::save( array( 'public_list' => 1, 'show_missions' => 1, 'applications_open' => 1, 'extensions' => 'pdf, dwg, php, svg' ) );
sme_assert( array( 'pdf', 'dwg' ) === Settings::extensions(), 'program and web files are never accepted, whatever the setting' );
Settings::save(
	array(
		'public_list'       => 1,
		'applications_open' => 1,
		'services'          => array(
			'calc_notes' => array( 'fr' => 'Notes de calcul acier', 'ar' => '', 'items_fr' => "Calcul des profilés\n\nVérification des assemblages\n", 'items_ar' => "حساب المقاطع\n\n", 'image' => 999999, 'on' => 1, 'order' => 5 ),
			'tender_docs' => array( 'fr' => '', 'ar' => '', 'items_fr' => '', 'items_ar' => '', 'on' => 0, 'order' => 50 ),
		),
		'new_service'       => array( 'fr' => 'Audit énergétique', 'ar' => 'تدقيق طاقوي' ),
	)
);
$cn = Services::get( 'calc_notes' );
sme_assert( 'Notes de calcul acier' === $cn['fr'] && 'مذكرة الحساب' === $cn['ar'] && 'calc_notes' === array_keys( Settings::types() )[0], 'administrator renames and reorders a service (empty Arabic title keeps the default)' );
sme_assert( 2 === count( $cn['items'] ) && 'حساب المقاطع' === $cn['items'][0]['ar'] && 'Vérification des assemblages' === $cn['items'][1]['ar'], 'prestations one per line; an empty Arabic line shows the French text' );
sme_assert( 0 === $cn['image'], 'an image id that is not an image of the media library is refused' );
sme_assert( ! isset( Settings::types()['tender_docs'] ) && isset( Settings::types( true )['tender_docs'] ), 'a hidden service is no longer offered but keeps its name' );
sme_assert( 'Audit énergétique' === ( Settings::types()['audit_energetique']['fr'] ?? '' ) && array() === Services::get( 'audit_energetique' )['items'], 'administrator adds a service' );
$picked = Services::pick( 'calc_notes', array( 1, 1, 7, 0 ) );
sme_assert( array( 'Calcul des profilés', 'Vérification des assemblages' ) === array_column( $picked, 'fr' ), 'only prestations of the service are kept, in order, once' );
null === $svc_was ? delete_option( Services::OPTION ) : update_option( Services::OPTION, $svc_was );
null === $mod_was ? delete_option( 'sm_engineering_settings' ) : update_option( 'sm_engineering_settings', $mod_was );
Settings::save( array( 'public_list' => 1, 'show_missions' => 1, 'applications_open' => 1 ) );
delete_option( Services::OPTION );

sme_section( '2. Accounts and rights' );
list( $u_cli, $c_cli ) = sme_account( 'client', 'client' );
list( $u_com, $c_com ) = sme_account( 'company', 'company', array( 'construction' ) );
list( $u_pro, $c_pro ) = sme_account( 'pro', 'professional', array( 'trade_welder' ) );
list( $u_eng, $c_eng ) = sme_account( 'eng', 'engineer' );
list( $u_en2, $c_en2 ) = sme_account( 'eng2', 'engineer' );
Engineers::save( $c_eng, $u_eng, sme_profile(), 'pending' );
Engineers::save( $c_en2, $u_en2, sme_profile( array( 'specialties' => array( 'studies' ), 'experience' => 3 ) ), 'pending' );
foreach ( array( $u_cli => 'client', $u_com => 'company', $u_pro => 'professional' ) as $u => $label ) {
	sme_assert( '' === Form::request_block( $u ), "a {$label} can ask for a study" );
}
sme_assert( '' !== Form::request_block( $u_eng ) && ! sm_can( 'mission.request', $c_eng ), 'a partner engineer cannot request missions' );
sme_assert( sm_can( 'mission.work', $c_eng ) && ! sm_can( 'mission.work', $c_com ), 'only engineers work on missions' );
sme_assert( ! sm_can( 'buy', $c_eng ) && ! sm_can( 'listing.products', $c_eng ) && ! sm_can( 'rfq.offer', $c_eng ), 'engineer accounts never trade' );
sme_assert( '' !== Form::request_block( 0 ), 'visitors are asked to sign in' );

sme_section( '3. Approval and public list' );
$ids = static fn() => array_map( static fn( $e ) => $e->company_id, Engineers::query( array( 'per_page' => 200 ) )['items'] );
sme_assert( ! in_array( $c_eng, $ids(), true ), 'a pending engineer is not listed' );
wp_set_current_user( $u_cli );
sme_assert( is_wp_error( Engineers::decide( $c_eng, 'rejected', '' ) ), 'a refusal needs a reason' );
wp_set_current_user( $admin );
sme_assert( true === Engineers::decide( $c_eng, 'approved' ) && Engineers::is_approved( $c_eng ) && 3 === (int) Companies::get( $c_eng )->verification_level, 'approval lists the engineer and gives level 3' );
Engineers::decide( $c_en2, 'rejected', 'Diplôme illisible' );
sme_assert( 'rejected' === Engineers::get( $c_en2 )->status && 'Diplôme illisible' === Engineers::get( $c_en2 )->reject_reason, 'refusal stored with its reason' );
sme_assert( in_array( $c_eng, $ids(), true ) && ! in_array( $c_en2, $ids(), true ), 'only approved engineers are listed' );
$list = Bridge::engineers( null, array( 'q' => 'SMETEST' ) );
$card = $list['items'][0] ?? array();
sme_assert( 1 === count( $list['items'] ) && 'SMETEST eng' === $card['name'] && 12 === $card['experience'] && in_array( 'Note de Calcul', $card['specialties'], true ), 'public card: name, experience, specialties' );
$flat = wp_json_encode( $card );
sme_assert( false === strpos( $flat, '0550000000' ) && false === strpos( $flat, 'smetest-eng@' ) && ! isset( $card['phone'] ) && ! isset( $card['email'] ) && ! isset( $card['website'] ), 'public card has no contact details' );
sme_assert( false !== strpos( $card['request_url'], 'engineer=' . $c_eng ), 'card links to the study request with this engineer' );
sme_assert( 1 === count( Bridge::engineers( null, array( 'q' => 'SMETEST', 'specialty' => 'expertise' ) )['items'] ) && 0 === count( Bridge::engineers( null, array( 'q' => 'SMETEST', 'specialty' => 'studies' ) )['items'] ), 'list filtered by specialty' );
sme_assert( null === \SM\Core\Theme\Bridge::company( null, array( 'slug' => Companies::get( $c_eng )->slug ) ), 'engineers have no public company page (no contacts)' );
Engineers::decide( $c_eng, 'suspended', 'Test' );
sme_assert( ! in_array( $c_eng, $ids(), true ), 'a suspended engineer is hidden' );
Engineers::decide( $c_eng, 'approved' );
$page = Bridge::engineers( null, array() );
sme_assert( 6 === count( $page['services'] ) && 'studies' === $page['services'][0]['code'] && '' === $page['services'][0]['image'] && 'warehouse-frame' === $page['services'][0]['demo'] && 4 === count( $page['services'][0]['items'] ) && false !== strpos( $page['services'][0]['url'], 'type=studies' ), 'engineering page: services with drawing, prestations and request link' );
wp_set_current_user( $u_cli );
sme_assert( isset( Bridge::mission_form( null, array() )['engineers'][ $c_eng ] ), 'request form offers the partner engineers when the list is public' );
$keep = get_option( Settings::OPTION );
Settings::save( array( 'public_list' => 0, 'show_missions' => 1, 'applications_open' => 1 ) );
sme_assert( ! Bridge::engineers( null, array() )['enabled'] && array() === Bridge::engineers( null, array() )['items'] && array() === Bridge::mission_form( null, array() )['engineers'], 'list hidden: no engineer on the page nor in the request form' );
update_option( Settings::OPTION, $keep );
wp_set_current_user( $admin );

sme_section( '4. Study request' );
$v = Form::validate_request( array( 'services' => array( 'nope' ), 'title' => 'x', 'description' => 'court', 'wilaya' => '99', 'needed_by' => '2001-01-01', 'project_type' => 'nope' ) );
sme_assert( isset( $v['errors']['services'], $v['errors']['title'], $v['errors']['description'], $v['errors']['wilaya'], $v['errors']['needed_by'], $v['errors']['project_type'] ), 'request checked: services, type of project, title, description, wilaya, date' );
sme_assert( 'Hangar de stockage' === \SM\Engineering\ProjectTypes::all()['storage']['fr'] && 'other' === array_key_last( \SM\Engineering\ProjectTypes::all() ), 'types of project, "Other" last' );
$req = array( 'services' => array( 'calc_notes', 'expertise' ), 'project_type' => 'storage', 'title' => 'Hangar 20 × 40 m à Sétif', 'description' => 'Hangar de stockage, portée 20 m, longueur 40 m, pont roulant 5 t, zone sismique IIa.', 'wilaya' => '19', 'budget' => '150 000', 'engineer' => (string) $c_en2 );
$v   = Form::validate_request( $req + array( 'prestations' => array( 'calc_notes' => array( 0 ) ) ) );
sme_assert( isset( $v['errors']['prestations'] ), 'each chosen service needs at least one prestation' );
$v = Form::validate_request( $req + array( 'prestations' => array( 'studies' => array( 0 ), 'calc_notes' => array( 9 ), 'expertise' => array( 1 ) ) ) );
sme_assert( isset( $v['errors']['prestations'] ), 'prestations of another service or unknown ones are ignored' );
$v = Form::validate_request( $req + array( 'prestations' => array( 'calc_notes' => array( 2, 0 ), 'expertise' => array( 0, 1, 2, 3 ) ) ) );
sme_assert( ! $v['errors'] && 0.0 === (float) ( $v['data']['budget'] ?? 0 ) && 0 === $v['data']['preferred_id'] && 'storage' === $v['data']['project_type'], 'valid request with two services; no budget; a refused engineer cannot be chosen' );
sme_assert( array( 'calc_notes', 'expertise' ) === array_column( $v['data']['services'], 'code' ) && array( 'Calcul et dimensionnement des profilés métalliques', 'Dimensionnement des assemblages (boulonnés et soudés)' ) === array_column( $v['data']['services'][0]['items'], 'fr' ) && 4 === count( $v['data']['services'][1]['items'] ), 'chosen services and prestations kept with the request' );
$v['data']['preferred_id'] = $c_eng;
$mid = Missions::create( $c_cli, $u_cli, $v['data'] );
$m   = Missions::get( $mid );
sme_assert( 'submitted' === $m->status && preg_match( '/^ET-\d{4}-\d{4,}$/', $m->ref ), 'mission created with a reference ' . $m->ref );
$cs = Bridge::client_shape( $m );
sme_assert( 'Note de Calcul + Expertise et Inspection Technique' === $cs['type'] && 'Hangar de stockage' === $cs['project'] && 6 === count( $cs['prestations'] ), 'client sees the services, the project and the prestations' );
$one = Form::validate_request( array( 'type' => 'calc_notes', 'project_type' => 'industrial', 'title' => 'Usine à Sétif', 'description' => 'Usine de transformation, portée 24 m, deux nefs, pont roulant 10 t.', 'wilaya' => '19', 'prestations' => array( 'calc_notes' => array( 0 ) ) ) );
sme_assert( ! $one['errors'] && array( 'calc_notes' ) === array_column( $one['data']['services'], 'code' ), 'a link with one service (?type=) still works' );

sme_section( '5. Workflow' );
wp_set_current_user( $admin );
sme_assert( is_wp_error( Missions::transition( $m, 'accept' ) ), 'nothing to accept before the quote' );
sme_assert( is_wp_error( Missions::transition( $m, 'quote', array( 'lines' => array( 100000, 0 ), 'tva_rate' => 19 ) ) ), 'a quote needs the price of each service' );
$m = Missions::transition( $m, 'quote', array( 'lines' => array( 100000, 50000 ), 'tva_rate' => 19, 'advance_pct' => '', 'days' => 15, 'message' => 'Note de calcul complète.' ) );
$t = Missions::totals( $m );
sme_assert( 'quoted' === $m->status && 150000.0 === $t['ht'] && 28500.0 === $t['tva'] && 178500.0 === $t['ttc'] && 30.0 === $t['advance_pct'] && 53550.0 === $t['advance'] && 124950.0 === $t['balance'], 'quote per service: HT 150 000, TVA 19 %, TTC 178 500, advance 30 % = 53 550' );
sme_assert( gmdate( 'Y-m-d', time() + 15 * DAY_IN_SECONDS ) === $m->quote_valid_until, 'quote valid 15 days' );
$c0 = Missions::compute( array( 100000, 50000 ), 0, 40 );
sme_assert( 150000.0 === $c0['ttc'] && 60000.0 === $c0['advance'], 'without TVA the total is before tax; advance changed by the platform' );
$txt = \SM\Engineering\Notifications::quote_text( $m );
sme_assert( false !== strpos( $txt, 'Note de Calcul' ) && false !== strpos( $txt, '178 500 DA' ) && false !== strpos( $txt, '53 550 DA' ) && false !== strpos( $txt, wp_date( 'd/m/Y', strtotime( $m->quote_valid_until . ' 12:00:00 UTC' ) ) ), 'detailed offer sent: services, total, advance, validity' );
wp_set_current_user( $u_cli );
$c = Bridge::studies( null, array( 'id' => $mid ) )['mission'];
sme_assert( $c['can']['accept'] && 178500.0 === $c['quote']['amount'] && 2 === count( $c['lines'] ) && 100000.0 === $c['lines'][0]['amount'] && 53550.0 === $c['quote']['totals']['advance'] && 'action' === $c['tone'], 'client sees the detailed offer to accept' );
$m = Missions::transition( $m, 'accept' );
sme_assert( 'accepted' === $m->status && Missions::totals( $m )['advance_due'], 'client accepts: the advance is due' );
$c = Bridge::studies( null, array( 'id' => $mid ) )['mission'];
sme_assert( 'advance' === $c['can']['pay'] && 'action' === $c['tone'] && $c['payments']['advance']['due'], 'client is asked to complete the payment' );
$oid = Payments::order_for( $m, 'advance', $u_cli );
$o   = \SM\Subscriptions\Orders::get( $oid );
sme_assert( ! is_wp_error( $oid ) && 'mission' === $o->kind && 53550.0 === (float) $o->amount_ttc && 19.0 === (float) $o->tva_rate && 45000.0 === (float) $o->amount_ht && $oid === Payments::order_for( $m, 'advance', $u_cli ), 'order of the advance: 53 550 DA incl. TVA, one at a time' );
sme_assert( is_wp_error( \SM\Subscriptions\Orders::create( Companies::get( $c_cli ), $u_cli, 'mission', 'balance', '', $mid ) ) && is_wp_error( \SM\Subscriptions\Orders::create( Companies::get( $c_com ), $u_com, 'mission', 'advance', '', $mid ) ), 'no balance before the advance; another account cannot pay this study' );
wp_set_current_user( $admin );
sme_assert( is_wp_error( Missions::transition( $m, 'assign', array( 'engineer_id' => $c_eng, 'fee' => 100000 ) ) ), 'no engineer before the advance is received' );
\SM\Subscriptions\Orders::approve( \SM\Subscriptions\Orders::get( $oid ), 'transfer' );
$m = Missions::get( $mid );
sme_assert( $m->advance_paid_at && ! Missions::totals( $m )['advance_due'] && ! $m->client_paid_at, 'order paid: advance received' );
sme_assert( is_wp_error( Missions::transition( $m, 'assign', array( 'engineer_id' => $c_en2, 'fee' => 100000 ) ) ), 'cannot assign a refused engineer' );
sme_assert( is_wp_error( Missions::transition( $m, 'assign', array( 'engineer_id' => $c_eng, 'fee' => 0 ) ) ), 'assignment needs a fee' );
$m = Missions::transition( $m, 'assign', array( 'engineer_id' => $c_eng, 'fee' => 120000, 'days' => 12, 'note' => 'Eurocode 3, RPA.' ) );
sme_assert( 'assigned' === $m->status && $c_eng === $m->engineer_id, 'mission offered to the engineer' );

wp_set_current_user( $u_eng );
$ea = Bridge::engineer_account( null, array() );
sme_assert( 1 === $ea['counts']['offered'] && 120000.0 === $ea['missions']['offered'][0]['fee'], 'engineer sees the offer and the fee' );
$ed   = Bridge::engineer_account( null, array( 'id' => $mid ) )['mission'];
$flat = wp_json_encode( $ed );
sme_assert( false === strpos( $flat, '178500' ) && false === strpos( $flat, '53550' ) && false === strpos( $flat, '100000' ) && false === strpos( $flat, 'SMETEST client' ) && ! isset( $ed['client'] ) && ! isset( $ed['amount'] ) && 2 === count( $ed['lines'] ), 'engineer sees the services, never the client nor the client prices' );
sme_assert( $ed['can']['take'] && 'Eurocode 3, RPA.' === $ed['note'], 'engineer can accept; reads the instructions' );
wp_set_current_user( $u_cli );
$c    = Bridge::studies( null, array( 'id' => $mid ) )['mission'];
$flat = wp_json_encode( $c );
sme_assert( false === strpos( $flat, '120000' ) && false === strpos( $flat, 'SMETEST eng' ) && ! isset( $c['fee'] ), 'client never sees the engineer nor the fee' );
wp_set_current_user( $u_com );
sme_assert( array( 'mission' => null ) === Bridge::studies( null, array( 'id' => $mid ) ), 'another account cannot read the mission' );
wp_set_current_user( $u_en2 );
sme_assert( null === Bridge::engineer_account( null, array( 'id' => $mid ) )['mission'], 'another engineer cannot read the mission' );

wp_set_current_user( $u_eng );
$m = Missions::transition( $m, 'giveup', array( 'reason' => 'Pas disponible' ) );
sme_assert( 'accepted' === $m->status && 0 === $m->engineer_id, 'engineer declines: back to the platform' );
wp_set_current_user( $admin );
$m = Missions::transition( $m, 'assign', array( 'engineer_id' => $c_eng, 'fee' => 120000, 'days' => 12 ) );
wp_set_current_user( $u_eng );
$m = Missions::transition( $m, 'take' );
sme_assert( 'in_progress' === $m->status && 'planned' === $m->fee_status, 'engineer accepts: in progress, fee planned' );
sme_assert( is_wp_error( Missions::transition( $m, 'submit' ) ), 'cannot send without deliverables' );

// A deliverable stored in the private folder.
$dir = \SM\Core\Verification\Verifications::private_dir() . '/missions/' . $mid;
wp_mkdir_p( $dir );
file_put_contents( $dir . '/test-note.pdf', "%PDF-1.4\n%test\n" );
$fid = Files::add( $mid, 'deliverable', array( 'path' => 'missions/' . $mid . '/test-note.pdf', 'original' => 'note-de-calcul.pdf', 'mime' => 'application/pdf', 'size' => 16 ), false, true, $u_eng );
$bid = Files::add( $mid, 'brief', array( 'path' => 'missions/' . $mid . '/test-note.pdf', 'original' => 'plan-client.pdf', 'mime' => 'application/pdf', 'size' => 16 ), true, true, $u_cli );
$iid = Files::add( $mid, 'invoice', array( 'path' => 'missions/' . $mid . '/test-note.pdf', 'original' => 'facture.pdf', 'mime' => 'application/pdf', 'size' => 16 ), false, true, $u_eng );
$f   = Files::get( $fid );
sme_assert( Files::can_view( $f, $u_eng ) && ! Files::can_view( $f, $u_cli ) && ! Files::can_view( $f, $u_com ) && Files::can_view( $f, $admin ), 'deliverable: engineer and platform only, before delivery' );
sme_assert( Files::can_view( Files::get( $bid ), $u_eng ) && Files::can_view( Files::get( $bid ), $u_cli ) && ! Files::can_view( Files::get( $bid ), $u_en2 ), 'client documents: client, assigned engineer, platform' );
sme_assert( ! Files::can_view( Files::get( $iid ), $u_cli ), 'the engineer invoice is never shown to the client' );
sme_assert( 0 !== strpos( wp_normalize_path( $dir ), wp_normalize_path( ABSPATH ) ) || file_exists( dirname( dirname( $dir ) ) . '/.htaccess' ), 'files kept outside the web root (or behind deny rules)' );
sme_assert( false !== strpos( Files::url( $fid ), 'action=sm_mission_file' ) && false !== strpos( Files::url( $fid ), '_wpnonce=' ), 'files are served through a signed link' );

$m = Missions::transition( $m, 'submit' );
sme_assert( 'review' === $m->status, 'engineer sends the work: review by the platform' );
wp_set_current_user( $u_cli );
sme_assert( array() === Bridge::studies( null, array( 'id' => $mid ) )['mission']['deliverables'] && 'progress' === Bridge::studies( null, array( 'id' => $mid ) )['mission']['tone'], 'client does not see the deliverables during the review' );
wp_set_current_user( $admin );
Messages::add( $mid, 'engineer', 'Ajouter le calcul des platines.', true );
$m = Missions::transition( $m, 'rework' );
sme_assert( 'in_progress' === $m->status, 'platform asks for changes' );
wp_set_current_user( $u_eng );
$m = Missions::transition( $m, 'submit' );
wp_set_current_user( $admin );
$m = Missions::transition( $m, 'deliver' );
sme_assert( 'delivered' === $m->status && 'due' === $m->fee_status, 'platform delivers: fee due' );
sme_assert( ! Files::can_view( Files::get( $fid ), $u_cli ), 'balance not paid: the client cannot download the deliverables' );
wp_set_current_user( $u_cli );
$c = Bridge::studies( null, array( 'id' => $mid ) )['mission'];
sme_assert( array() === $c['deliverables'] && 'balance' === $c['can']['pay'] && ! $c['can']['confirm'] && 124950.0 === $c['payments']['balance']['amount'], 'client is asked to pay the balance (124 950 DA)' );
$bo = Payments::order_for( Missions::get( $mid ), 'balance', $u_cli );
wp_set_current_user( $admin );
\SM\Subscriptions\Orders::approve( \SM\Subscriptions\Orders::get( $bo ), 'ccp' );
$m = Missions::get( $mid );
sme_assert( $m->client_paid_at && Files::can_view( Files::get( $fid ), $u_cli ), 'balance paid: the client sees the deliverables' );
wp_set_current_user( $u_cli );
$c = Bridge::studies( null, array( 'id' => $mid ) )['mission'];
sme_assert( 1 === count( $c['deliverables'] ) && 'note-de-calcul.pdf' === $c['deliverables'][0]['name'] && $c['can']['confirm'], 'client downloads the study and can confirm' );
sme_assert( array() === array_filter( $c['messages'], static fn( $x ) => false !== strpos( $x['body'], 'platines' ) ), 'engineer channel never shown to the client' );
$m = Missions::transition( $m, 'confirm' );
sme_assert( 'closed' === $m->status, 'client confirms: closed' );
wp_set_current_user( $admin );
sme_assert( is_wp_error( Missions::fee_paid( Missions::get( 999999 ) ) ), 'unknown mission fee refused' );
$m = Missions::fee_paid( $m, 'VIR-2026-001' );
sme_assert( 'paid' === $m->fee_status && 'VIR-2026-001' === $m->fee_reference, 'fee payment recorded' );
wp_set_current_user( $u_eng );
$bill = Bridge::engineer_account( null, array( 'section' => 'billing' ) );
sme_assert( 120000.0 === (float) $bill['money']['paid'] && 1 === count( $bill['billing'] ) && 'VIR-2026-001' === $bill['billing'][0]['reference'], 'engineer billing: fee paid with its reference' );
$files = Bridge::engineer_account( null, array( 'section' => 'files' ) )['files'];
sme_assert( 1 === count( $files ) && 3 === count( $files[0]['files'] ), 'engineer files and plans grouped by mission' );
sme_assert( 1 === Engineers::missions_done( $c_eng ), 'completed missions counted on the public list' );

sme_section( '6. Other paths' );
wp_set_current_user( $u_pro );
$m2 = Missions::get( Missions::create( $c_pro, $u_pro, $v['data'] ) );
$m2 = Missions::transition( $m2, 'cancel' );
sme_assert( 'cancelled' === $m2->status, 'client cancels before acceptance' );
$m3 = Missions::get( Missions::create( $c_com, $u_com, $v['data'] ) );
wp_set_current_user( $admin );
sme_assert( is_wp_error( Missions::transition( $m3, 'decline', array( 'reason' => '' ) ) ), 'declining needs a reason' );
$m3 = Missions::transition( $m3, 'decline', array( 'reason' => 'Hors périmètre' ) );
wp_set_current_user( $u_com );
sme_assert( 'Hors périmètre' === Bridge::studies( null, array( 'id' => $m3->id ) )['mission']['reason'], 'client reads why the platform declined' );
wp_set_current_user( $admin );
$m4 = Missions::get( Missions::create( $c_com, $u_com, $v['data'] ) );
$m4 = Missions::transition( $m4, 'quote', array( 'lines' => array( 30000, 20000 ) ) );
global $wpdb;
$wpdb->update( Missions::table(), array( 'quote_valid_until' => '2020-01-01' ), array( 'id' => $m4->id ) );
wp_set_current_user( $u_com );
sme_assert( is_wp_error( Missions::transition( Missions::get( $m4->id ), 'accept' ) ) && ! Bridge::studies( null, array( 'id' => $m4->id ) )['mission']['can']['accept'], 'an expired quote cannot be accepted' );
sme_assert( 2 === count( Bridge::studies( null, array() )['items'] ), 'client list of studies (only its own)' );
$m4_done = true;
$counts = Missions::counts();
sme_assert( $counts['closed'] >= 1 && $counts['cancelled'] >= 1 && $counts['declined'] >= 1, 'admin counts by status' );
sme_assert( 1 === Missions::query( array( 'q' => $m->ref ) )['total'], 'admin finds a mission by its reference' );

sme_section( '6b. Reminders and unpaid advances (daily task)' );
wp_set_current_user( $admin );
$q1 = Missions::transition( Missions::get( Missions::create( $c_com, $u_com, $v['data'] ) ), 'quote', array( 'lines' => array( 1000, 2000 ), 'tva_rate' => 0 ) );
$wpdb->update( Missions::table(), array( 'quote_valid_until' => gmdate( 'Y-m-d', time() + DAY_IN_SECONDS ) ), array( 'id' => $q1->id ) );
$q2 = Missions::transition( Missions::get( Missions::create( $c_com, $u_com, $v['data'] ) ), 'quote', array( 'lines' => array( 1000, 2000 ), 'tva_rate' => 0 ) );
$q2 = Missions::transition( $q2, 'accept' );
$wpdb->update( Missions::table(), array( 'accepted_at' => gmdate( 'Y-m-d H:i:s', time() - 4 * DAY_IN_SECONDS ) ), array( 'id' => $q2->id ) );
$q3 = Missions::transition( Missions::get( Missions::create( $c_com, $u_com, $v['data'] ) ), 'quote', array( 'lines' => array( 1000, 2000 ), 'tva_rate' => 0 ) );
$q3 = Missions::transition( $q3, 'accept' );
$o3 = Payments::order_for( $q3, 'advance', $u_com );
$wpdb->update( Missions::table(), array( 'quote_valid_until' => gmdate( 'Y-m-d', time() - DAY_IN_SECONDS ) ), array( 'id' => $q3->id ) );
Missions::daily();
Missions::daily(); // Twice: each reminder is sent once.
$nq = static fn( $type ) => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_notifications WHERE user_id = %d AND type = %s", $u_com, $type ) );
sme_assert( Missions::get( $q1->id )->quote_reminded_at && 1 === $nq( 'mission.quote_reminder' ), 'reminder the day before the quote ends (once)' );
sme_assert( Missions::get( $q2->id )->pay_reminded_at && 1 === $nq( 'mission.pay_reminder' ) && 'accepted' === Missions::get( $q2->id )->status, 'unpaid advance after 3 days: reminder to the client (once)' );
sme_assert( 'cancelled' === Missions::get( $q3->id )->status && 1 === $nq( 'mission.expired_unpaid' ) && 'cancelled' === \SM\Subscriptions\Orders::get( $o3 )->status, 'advance unpaid at the end of the quote: request and order cancelled' );
$adm = wp_list_pluck( \SM\Core\Audit\AuditLog::query( array( 'prefixes' => array( 'mission.' ), 'per_page' => 300 ) ), 'action' );
sme_assert( in_array( 'mission.payment_reminder', $adm, true ) && in_array( 'mission.expired_unpaid', $adm, true ) && isset( \SM\Core\Mail\Events::all()['mission.payment_reminder'], \SM\Core\Mail\Events::all()['mission.expired_unpaid'], \SM\Core\Mail\Events::all()['mission.advance_paid'] ), 'the platform is told by e-mail (reminder, cancellation, advance received)' );
$q4 = Missions::transition( Missions::transition( Missions::get( Missions::create( $c_com, $u_com, $v['data'] ) ), 'quote', array( 'lines' => array( 1000, 2000 ), 'tva_rate' => 0 ) ), 'accept' );
$q4 = Missions::payment_received( $q4, 'advance' );
sme_assert( $q4->advance_paid_at && ! Missions::totals( $q4 )['advance_due'], 'the platform can record an advance paid by hand' );

sme_section( '7. Notifications and journal' );
$types = apply_filters( 'sm_notification_types', array() );
foreach ( array( 'engineer.applied', 'engineer.approved', 'engineer.rejected', 'mission.submitted', 'mission.quote', 'mission.offered', 'mission.delivered', 'mission.fee_paid', 'mission.reply_client', 'mission.reply_engineer' ) as $k ) {
	sme_assert( isset( $types[ $k ] ), "notification {$k}" );
}
$n = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_notifications WHERE user_id = %d AND type = 'mission.quote'", $u_cli ) );
sme_assert( (int) $n >= 1, 'the client was told about the quote' );
$n = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_notifications WHERE user_id = %d AND type IN ('mission.offered','mission.fee_paid')", $u_eng ) );
sme_assert( (int) $n >= 3, 'the engineer was told (offers, payment)' );
$events = \SM\Core\Mail\Events::all();
sme_assert( isset( $events['engineer.applied'], $events['mission.submitted'], $events['mission.accepted'], $events['mission.review'] ), 'admin e-mail events registered' );
$steps = wp_list_pluck( \SM\Core\Audit\AuditLog::query( array( 'prefixes' => array( 'mission.' ), 'per_page' => 200 ) ), 'action' );
sme_assert( ! array_diff( array( 'mission.submitted', 'mission.quoted', 'mission.accepted', 'mission.assigned', 'mission.started', 'mission.review', 'mission.delivered', 'mission.closed', 'mission.fee_paid' ), $steps ), 'journal records every mission step' );
$labels = apply_filters( 'sm_activity_labels', array() );
sme_assert( isset( $labels['mission.delivered'], $labels['engineer.approved'] ), 'journal labels' );

wp_set_current_user( 0 );
sme_section( '8. Application in steps (lists, profile, e-mail code)' );
$lists_was = get_option( \SM\Engineering\Lists::OPTION, null );
delete_option( \SM\Engineering\Lists::OPTION );
sme_assert( array( 'doctor', 'state_engineer', 'master', 'other' ) === array_keys( \SM\Engineering\Lists::all( 'titles' ) ) && array( 'steel', 'civil_industrial', 'structure', 'other' ) === array_keys( \SM\Engineering\Lists::all( 'specialities' ) ), 'titles and specialities lists, "Other" last' );
\SM\Engineering\Lists::save( 'titles', array( 'master' => array( 'fr' => '', 'ar' => '', 'on' => 0, 'order' => 5 ), 'other' => array( 'fr' => 'Autre', 'on' => 0 ) ), array( 'fr' => 'Architecte', 'ar' => 'مهندس معماري' ) );
$tl = \SM\Engineering\Lists::all( 'titles' );
sme_assert( ! isset( $tl['master'] ) && isset( $tl['architecte'], $tl['other'] ) && 'other' === array_key_last( $tl ), 'administrator hides, adds; "Other" cannot be hidden and stays last' );
sme_assert( 'Ouvrages d’art' === \SM\Engineering\Lists::label( 'specialities', 'other', 'Ouvrages d’art' ) && 'المنشآت المعدنية' === \SM\Engineering\Lists::label( 'specialities', 'steel', '', 'ar' ), 'labels: written text for "Other", Arabic names' );
delete_option( \SM\Engineering\Lists::OPTION );
$post_was = $_POST;
$_POST    = array( 'partner_type' => 'bet', 'bet_name' => '', 'degree' => 'nope', 'speciality' => 'other', 'speciality_other' => '', 'experience' => '12', 'specialties' => array( 'calc_notes', 'nope' ), 'bio' => 'court' );
$pv       = Apply::validate_profile();
sme_assert( isset( $pv['errors']['bet_name'], $pv['errors']['degree'], $pv['errors']['speciality_other'], $pv['errors']['bio'] ) && array( 'calc_notes' ) === $pv['data']['specialties'], 'profile checked: BET name, title from the list, "Other" written, presentation' );
$_POST = array( 'partner_type' => 'bet', 'bet_name' => 'BET SNC LEMC', 'degree' => 'state_engineer', 'speciality' => 'steel', 'experience' => '16', 'specialties' => array( 'calc_notes', 'expertise' ), 'bio' => 'Études de hangars métalliques depuis 2008.' );
$pv    = Apply::validate_profile();
sme_assert( ! $pv['errors'] && 'bet' === $pv['data']['partner_type'] && 'Ingénieur d’État en génie civil — Structure métallique' === $pv['data']['title'] && '' === $pv['data']['school'], 'valid profile: title "Ingénieur d’État en génie civil — Structure métallique", no school nor year' );
$_POST['partner_type'] = 'independent';
sme_assert( '' === Apply::validate_profile()['data']['bet_name'], 'an independent engineer has no office name' );
$_POST = $post_was;
sme_assert( array( 'diploma', 'id' ) === array_keys( Apply::documents() ) && array( 'diploma', 'id', 'bet_agrement' ) === array_keys( Apply::documents( true ) ) && Apply::documents( true )['bet_agrement'][1] && 'Agrément de BET (MHUV, en cours de validité)' === Apply::document_label( 'bet_agrement' ), 'documents: all required; BET approval (MHUV) for an office' );
$mail = 'sme-code-' . wp_rand( 1000, 9999 ) . '@example.test';
sme_assert( is_wp_error( \SM\Engineering\EmailCode::send( 'pas-un-email' ) ) && is_wp_error( \SM\Engineering\EmailCode::send( get_userdata( $u_cli )->user_email ) ), 'no code for a wrong address or an existing account' );
sme_assert( true === \SM\Engineering\EmailCode::send( $mail ) && is_wp_error( \SM\Engineering\EmailCode::send( $mail ) ), 'code sent; a new one only after 45 seconds' );
$st   = get_transient( 'sme_code_' . md5( $mail ) );
$code = (string) ( $st['dev'] ?? '' );
sme_assert( 6 === strlen( $code ) && $st['hash'] !== $code && ! \SM\Engineering\EmailCode::confirmed( $mail ), 'code of 6 digits, stored hashed, address not yet confirmed' );
sme_assert( is_wp_error( \SM\Engineering\EmailCode::check( $mail, '000000' === $code ? '111111' : '000000' ) ) && true === \SM\Engineering\EmailCode::check( $mail, $code ) && \SM\Engineering\EmailCode::confirmed( $mail ), 'wrong code refused; right code confirms the address' );
\SM\Engineering\EmailCode::forget( $mail );
sme_assert( ! \SM\Engineering\EmailCode::confirmed( $mail ), 'confirmation used once' );
$mail2 = 'sme-code2-' . wp_rand( 1000, 9999 ) . '@example.test';
\SM\Engineering\EmailCode::send( $mail2 );
for ( $k = 0; $k < 5; $k++ ) {
	\SM\Engineering\EmailCode::check( $mail2, '00000' . $k );
}
$c2 = get_transient( 'sme_code_' . md5( $mail2 ) )['dev'] ?? '';
sme_assert( is_wp_error( \SM\Engineering\EmailCode::check( $mail2, $c2 ) ), 'after 5 wrong codes a new code is needed' );
\SM\Engineering\EmailCode::forget( $mail2 );
null === $lists_was ? delete_option( \SM\Engineering\Lists::OPTION ) : update_option( \SM\Engineering\Lists::OPTION, $lists_was );

sme_section( '9. Profile changes need the approval of the platform' );
wp_set_current_user( $admin );
Engineers::decide( $c_eng, 'approved' );
Engineers::save( $c_eng, $u_eng, sme_profile( array( 'partner_type' => 'independent', 'degree' => 'state_engineer', 'speciality' => 'steel', 'experience' => 12 ) ) );
wp_update_user( array( 'ID' => $u_eng, 'first_name' => 'Karim', 'last_name' => 'Test' ) );
$e9  = Engineers::get( $c_eng );
$now = \SM\Engineering\Changes::current( $e9 );
sme_assert( is_wp_error( \SM\Engineering\Changes::request( $e9, $now ) ), 'a request without any change is refused' );
$pe = Engineers::get( $c_en2 );
Engineers::set( $c_en2, array( 'status' => 'pending' ) );
sme_assert( is_wp_error( \SM\Engineering\Changes::request( Engineers::get( $c_en2 ), array( 'bio' => 'Autre présentation de plus de vingt caractères.' ) ) ), 'application being checked: the profile cannot be changed' );
Engineers::set( $c_en2, array( 'status' => $pe->status ) );
$want = array_merge( $now, array( 'experience' => 20, 'speciality' => 'other', 'speciality_other' => 'Ouvrages d’art', 'partner_type' => 'bet', 'bet_name' => 'BET SMETEST', 'specialties' => array( 'studies' ) ) );
sme_assert( true === \SM\Engineering\Changes::request( $e9, $want ), 'approved engineer: the change is a request' );
$e9 = Engineers::get( $c_eng );
sme_assert( 12 === (int) $e9->experience && 'steel' === $e9->speciality && array( 'calc_notes', 'expertise' ) === $e9->specialties && 'independent' === $e9->partner_type, 'the approved profile stays in use while the request waits' );
$rows = array_column( \SM\Engineering\Changes::diff( $e9 ), 'after', 'key' );
sme_assert( array( 'partner_type', 'bet_name', 'speciality', 'experience', 'specialties' ) === array_keys( $rows ) && 'Ouvrages d’art' === $rows['speciality'] && '20' === $rows['experience'], 'comparison before / after for the platform' );
$n9 = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_notifications WHERE type = %s", 'engineer.change_requested' ) );
sme_assert( $n9 >= 1 && isset( \SM\Core\Mail\Events::all()['engineer.change_requested'] ), 'the platform is told (notification and e-mail event)' );
sme_assert( is_wp_error( \SM\Engineering\Changes::decide( $c_eng, false, '' ) ), 'a refusal needs a reason' );
\SM\Engineering\Changes::decide( $c_eng, false, 'Joignez l’agrément du bureau.' );
$e9 = Engineers::get( $c_eng );
sme_assert( null === \SM\Engineering\Changes::pending( $e9 ) && 12 === (int) $e9->experience && 'Joignez l’agrément du bureau.' === $e9->change_reason, 'refused: nothing changes, the engineer reads the reason' );
\SM\Engineering\Changes::request( $e9, $want );
\SM\Engineering\Changes::cancel( Engineers::get( $c_eng ) );
sme_assert( null === \SM\Engineering\Changes::pending( Engineers::get( $c_eng ) ), 'the engineer can cancel his request' );
\SM\Engineering\Changes::request( Engineers::get( $c_eng ), array_merge( $want, array( 'last_name' => 'Nouveau' ) ) );
sme_assert( true === \SM\Engineering\Changes::decide( $c_eng, true ), 'the platform approves the request' );
$e9 = Engineers::get( $c_eng );
sme_assert( 20 === (int) $e9->experience && 'bet' === $e9->partner_type && array( 'studies' ) === $e9->specialties && 'Ingénieur d’État en génie civil — Ouvrages d’art' === $e9->title && 'BET SMETEST' === Companies::get( $c_eng )->name && 'Nouveau' === get_userdata( $u_eng )->last_name && 'approved' === $e9->status, 'approved: profile, title, office name and name updated; still approved' );
sme_assert( null === \SM\Engineering\Changes::pending( $e9 ) && is_wp_error( \SM\Engineering\Changes::decide( $c_eng, true ) ), 'a request is decided once' );

sme_cleanup();
null === $mod_was ? delete_option( 'sm_engineering_settings' ) : update_option( 'sm_engineering_settings', $mod_was );
$t = $GLOBALS['sme_t'];
printf( "\n%d passed, %d failed\n", $t['pass'], $t['fail'] );
if ( $t['fail'] ) {
	echo "FAILED:\n  - " . implode( "\n  - ", $t['fails'] ) . "\n";
	exit( 1 );
}
