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
use SM\Engineering\Missions;
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
	foreach ( $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sm_companies WHERE name LIKE 'SMETEST %'" ) as $cid ) {
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
	return array_merge( array( 'title' => 'Ingénieur génie civil — structures', 'diploma' => 'Master structures', 'school' => 'USTHB', 'grad_year' => 2012, 'experience' => 12, 'specialties' => array( 'calculation', 'inspection' ), 'bio' => 'Douze ans de charpente métallique : hangars, bâtiments industriels et expertises.' ), $over );
}

sme_cleanup();
$admin = (int) get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) )[0];
$mod_was = get_option( 'sm_engineering_settings', null );

sme_section( '1. Installation and settings' );
global $wpdb;
foreach ( array( 'engineers', 'missions', 'mission_files', 'mission_messages' ) as $t ) {
	sme_assert( (bool) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_{$t}" ) || 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_{$t}" ), "table sm_{$t} exists" );
}
sme_assert( 9 === count( Settings::types() ) && 'Notes de calcul' === Settings::types()['calculation']['fr'], 'nine study types from the former engineering catalogue' );
Settings::save( array( 'public_list' => 1, 'show_missions' => 1, 'applications_open' => 1, 'extensions' => 'pdf, dwg, php, svg', 'types' => array( 'calculation' => array( 'fr' => 'Notes de calcul', 'ar' => '', 'on' => 1 ), 'consultancy' => array( 'fr' => '', 'ar' => '' ) ), 'new_type' => array( 'fr' => 'Audit énergétique', 'ar' => 'تدقيق طاقوي' ) ) );
sme_assert( array( 'pdf', 'dwg' ) === Settings::extensions(), 'program and web files are never accepted, whatever the setting' );
sme_assert( ! isset( Settings::types()['consultancy'] ) && isset( Settings::types( true )['consultancy'] ) && 'Audit énergétique' === Settings::types()['audit_energetique']['fr'], 'administrator hides and adds study types' );
sme_assert( 'مذكرات الحساب' === Settings::types()['calculation']['ar'], 'an empty Arabic name keeps the default' );
null === $mod_was ? delete_option( 'sm_engineering_settings' ) : update_option( 'sm_engineering_settings', $mod_was );
Settings::save( array( 'public_list' => 1, 'show_missions' => 1, 'applications_open' => 1 ) );

sme_section( '2. Accounts and rights' );
list( $u_cli, $c_cli ) = sme_account( 'client', 'client' );
list( $u_com, $c_com ) = sme_account( 'company', 'company', array( 'construction' ) );
list( $u_pro, $c_pro ) = sme_account( 'pro', 'professional', array( 'trade_welder' ) );
list( $u_eng, $c_eng ) = sme_account( 'eng', 'engineer' );
list( $u_en2, $c_en2 ) = sme_account( 'eng2', 'engineer' );
Engineers::save( $c_eng, $u_eng, sme_profile(), 'pending' );
Engineers::save( $c_en2, $u_en2, sme_profile( array( 'specialties' => array( 'design' ), 'experience' => 3 ) ), 'pending' );
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
sme_assert( 1 === count( $list['items'] ) && 'SMETEST eng' === $card['name'] && 12 === $card['experience'] && in_array( 'Notes de calcul', $card['specialties'], true ), 'public card: name, experience, specialties' );
$flat = wp_json_encode( $card );
sme_assert( false === strpos( $flat, '0550000000' ) && false === strpos( $flat, 'smetest-eng@' ) && ! isset( $card['phone'] ) && ! isset( $card['email'] ) && ! isset( $card['website'] ), 'public card has no contact details' );
sme_assert( false !== strpos( $card['request_url'], 'engineer=' . $c_eng ), 'card links to the study request with this engineer' );
sme_assert( 1 === count( Bridge::engineers( null, array( 'q' => 'SMETEST', 'specialty' => 'inspection' ) )['items'] ) && 0 === count( Bridge::engineers( null, array( 'q' => 'SMETEST', 'specialty' => 'design' ) )['items'] ), 'list filtered by specialty' );
sme_assert( null === \SM\Core\Theme\Bridge::company( null, array( 'slug' => Companies::get( $c_eng )->slug ) ), 'engineers have no public company page (no contacts)' );
Engineers::decide( $c_eng, 'suspended', 'Test' );
sme_assert( ! in_array( $c_eng, $ids(), true ), 'a suspended engineer is hidden' );
Engineers::decide( $c_eng, 'approved' );

sme_section( '4. Study request' );
$v = Form::validate_request( array( 'type' => 'nope', 'title' => 'x', 'description' => 'court', 'wilaya' => '99', 'needed_by' => '2001-01-01' ) );
sme_assert( isset( $v['errors']['type'], $v['errors']['title'], $v['errors']['description'], $v['errors']['wilaya'], $v['errors']['needed_by'] ), 'request checked: type, title, description, wilaya, date' );
$v = Form::validate_request( array( 'type' => 'calculation', 'title' => 'Hangar 20 × 40 m à Sétif', 'description' => 'Hangar de stockage, portée 20 m, longueur 40 m, pont roulant 5 t, zone sismique IIa.', 'wilaya' => '19', 'budget' => '150 000', 'engineer' => (string) $c_en2 ) );
sme_assert( ! $v['errors'] && 150000.0 === $v['data']['budget'] && 0 === $v['data']['preferred_id'], 'valid request; a refused engineer cannot be chosen' );
$v['data']['preferred_id'] = $c_eng;
$mid = Missions::create( $c_cli, $u_cli, $v['data'] );
$m   = Missions::get( $mid );
sme_assert( 'submitted' === $m->status && preg_match( '/^ET-\d{4}-\d{4,}$/', $m->ref ), 'mission created with a reference ' . $m->ref );

sme_section( '5. Workflow' );
wp_set_current_user( $admin );
sme_assert( is_wp_error( Missions::transition( $m, 'accept' ) ), 'nothing to accept before the quote' );
sme_assert( is_wp_error( Missions::transition( $m, 'quote', array( 'amount' => 0 ) ) ), 'a quote needs a price' );
$m = Missions::transition( $m, 'quote', array( 'amount' => 180000, 'days' => 15, 'message' => 'Note de calcul complète.' ) );
sme_assert( 'quoted' === $m->status && 180000.0 === $m->quote_amount && $m->quote_valid_until >= gmdate( 'Y-m-d' ), 'quote sent with a validity date' );
wp_set_current_user( $u_cli );
$c = Bridge::studies( null, array( 'id' => $mid ) )['mission'];
sme_assert( $c['can']['accept'] && 180000.0 === $c['quote']['amount'] && 'action' === $c['tone'], 'client sees the quote to accept' );
$m = Missions::transition( $m, 'accept' );
sme_assert( 'accepted' === $m->status, 'client accepts' );
wp_set_current_user( $admin );
sme_assert( is_wp_error( Missions::transition( $m, 'assign', array( 'engineer_id' => $c_en2, 'fee' => 100000 ) ) ), 'cannot assign a refused engineer' );
sme_assert( is_wp_error( Missions::transition( $m, 'assign', array( 'engineer_id' => $c_eng, 'fee' => 0 ) ) ), 'assignment needs a fee' );
$m = Missions::transition( $m, 'assign', array( 'engineer_id' => $c_eng, 'fee' => 120000, 'days' => 12, 'note' => 'Eurocode 3, RPA.' ) );
sme_assert( 'assigned' === $m->status && $c_eng === $m->engineer_id, 'mission offered to the engineer' );

wp_set_current_user( $u_eng );
$ea = Bridge::engineer_account( null, array() );
sme_assert( 1 === $ea['counts']['offered'] && 120000.0 === $ea['missions']['offered'][0]['fee'], 'engineer sees the offer and the fee' );
$ed   = Bridge::engineer_account( null, array( 'id' => $mid ) )['mission'];
$flat = wp_json_encode( $ed );
sme_assert( false === strpos( $flat, '180000' ) && false === strpos( $flat, 'SMETEST client' ) && ! isset( $ed['client'] ) && ! isset( $ed['amount'] ), 'engineer never sees the client nor the client price' );
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
sme_assert( Files::can_view( Files::get( $fid ), $u_cli ), 'client sees the deliverables after delivery' );
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
$m4 = Missions::transition( $m4, 'quote', array( 'amount' => 50000 ) );
global $wpdb;
$wpdb->update( Missions::table(), array( 'quote_valid_until' => '2020-01-01' ), array( 'id' => $m4->id ) );
wp_set_current_user( $u_com );
sme_assert( is_wp_error( Missions::transition( Missions::get( $m4->id ), 'accept' ) ) && ! Bridge::studies( null, array( 'id' => $m4->id ) )['mission']['can']['accept'], 'an expired quote cannot be accepted' );
sme_assert( 2 === count( Bridge::studies( null, array() )['items'] ), 'client list of studies (only its own)' );
$counts = Missions::counts();
sme_assert( $counts['closed'] >= 1 && $counts['cancelled'] >= 1 && $counts['declined'] >= 1, 'admin counts by status' );
sme_assert( 1 === Missions::query( array( 'q' => $m->ref ) )['total'], 'admin finds a mission by its reference' );

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
sme_cleanup();
$t = $GLOBALS['sme_t'];
printf( "\n%d passed, %d failed\n", $t['pass'], $t['fail'] );
if ( $t['fail'] ) {
	echo "FAILED:\n  - " . implode( "\n  - ", $t['fails'] ) . "\n";
	exit( 1 );
}
