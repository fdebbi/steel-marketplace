<?php
/**
 * Tests of sm-ai. Run on a TEST site only (creates then removes data):
 *   wp eval-file wp-content/plugins/sm-ai/tests/run.php
 *
 * No real AI is called: the provider adapters are checked against recorded
 * answers in the formats of the Anthropic and OpenAI APIs (pre_http_request),
 * and the features with the simulation provider (SM_AI_SIMULATION).
 *
 * @package SM\Ai
 */

use SM\Ai\Catalog;
use SM\Ai\Features\Document;
use SM\Ai\Features\Listing;
use SM\Ai\Features\Recognize;
use SM\Ai\Features\Search;
use SM\Ai\Gateway;
use SM\Ai\Providers\Anthropic;
use SM\Ai\Providers\OpenAI;
use SM\Ai\Settings;
use SM\Ai\Usage;
use SM\Core\Accounts\Companies;

defined( 'ABSPATH' ) || exit;

$GLOBALS['smai_t'] = array( 'pass' => 0, 'fail' => 0, 'fails' => array() );
function smai_assert( $cond, $label ) {
	$t = &$GLOBALS['smai_t'];
	if ( $cond ) {
		++$t['pass'];
		echo "  ✓ $label\n";
	} else {
		++$t['fail'];
		$t['fails'][] = $label;
		echo "  ✗ $label\n";
	}
}
function smai_section( $s ) {
	echo "\n$s\n";
}
function smai_user( $suffix, $class, array $activities ) {
	$uid = wp_insert_user( array( 'user_login' => 'smaitest_' . $suffix, 'user_email' => "smaitest-{$suffix}@example.test", 'user_pass' => wp_generate_password(), 'role' => 'sm_member' ) );
	$cid = Companies::create( array( 'account_class' => $class, 'name' => "SMAITEST {$suffix}", 'wilaya' => '16' ), $uid, $activities );
	Companies::raise_level( $cid, 1 );
	return array( $uid, $cid );
}
function smai_cleanup() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sm_companies WHERE name LIKE 'SMAITEST %'" ) as $cid ) {
		$wpdb->delete( $wpdb->prefix . 'sm_ai_usage', array( 'company_id' => $cid ) );
		foreach ( array( 'company_members', 'company_activities' ) as $t ) {
			$wpdb->delete( $wpdb->prefix . 'sm_' . $t, array( 'company_id' => $cid ) );
		}
		$wpdb->delete( $wpdb->prefix . 'sm_companies', array( 'id' => $cid ) );
	}
	foreach ( get_users( array( 'search' => 'smaitest-*', 'search_columns' => array( 'user_email' ) ) ) as $u ) {
		$wpdb->delete( $wpdb->prefix . 'sm_ai_usage', array( 'user_id' => $u->ID ) );
		wp_delete_user( $u->ID );
	}
}

smai_cleanup();
$before = get_option( Settings::OPTION, null );
delete_option( Settings::OPTION );
$_SERVER['REMOTE_ADDR'] = '10.9.8.7';
global $wpdb;
$wpdb->delete( $wpdb->prefix . 'sm_ai_usage', array( 'ip' => '10.9.8.7' ) );

/* ------------------------------------------------------------------ */
smai_section( '1. Settings and keys' );
smai_assert( array( '', '' ) === Settings::route( 'listing' ), 'nothing configured: no route (AI off)' );
Settings::save( array( 'default_provider' => 'anthropic', 'providers' => array( 'anthropic' => array( 'key' => 'sk-ant-TEST-1234abcd', 'model' => 'claude-sonnet-5-5', 'price_in' => 3, 'price_out' => 15 ) ), 'features' => array( 'listing' => array( 'enabled' => 1 ), 'recognize' => array( 'enabled' => 1 ), 'search' => array( 'enabled' => 1 ), 'document' => array( 'enabled' => 1 ) ) ) );
$raw = get_option( Settings::OPTION );
smai_assert( false === strpos( wp_json_encode( $raw ), 'sk-ant-TEST' ) && 'sk-ant-TEST-1234abcd' === Settings::api_key( 'anthropic' ), 'API key stored encrypted, decrypted for the call' );
smai_assert( '••••abcd' === Settings::key_hint( 'anthropic' ), 'key shown only as its 4 last characters' );
smai_assert( array( 'anthropic', 'claude-sonnet-5-5' ) === Settings::route( 'document' ), 'route: default provider and its model' );
Settings::save( array_merge( get_option( Settings::OPTION ), array( 'providers' => array( 'anthropic' => array( 'key' => '', 'model' => 'claude-sonnet-5-5' ) ) ) ) );
smai_assert( 'sk-ant-TEST-1234abcd' === Settings::api_key( 'anthropic' ), 'empty key field keeps the stored key' );
$in = get_option( Settings::OPTION );
$in['features']['search']['model'] = 'claude-haiku-5-5';
$in['features']['search']['enabled'] = 1;
Settings::save( $in );
smai_assert( array( 'anthropic', 'claude-haiku-5-5' ) === Settings::route( 'search' ), 'a service can use another model' );
Settings::save( get_option( Settings::OPTION ) );
smai_assert( 'sk-ant-TEST-1234abcd' === Settings::api_key( 'anthropic' ) && 3.0 === (float) Settings::all()['providers']['anthropic']['price_in'], 'saving the stored settings again keeps the key and the prices' );
smai_assert( isset( Settings::provider_labels()['simulation'] ) === ( defined( 'SM_AI_SIMULATION' ) && SM_AI_SIMULATION ), 'simulation offered only when SM_AI_SIMULATION is set' );

/* ------------------------------------------------------------------ */
smai_section( '2. Provider adapters (recorded answers)' );
$req  = array( 'system' => 'S', 'parts' => array( array( 'type' => 'text', 'text' => 'Hello' ), array( 'type' => 'image', 'mime' => 'image/png', 'data' => 'AAAA' ), array( 'type' => 'pdf', 'name' => 'a.pdf', 'data' => 'BBBB' ) ), 'model' => 'm1', 'max_tokens' => 500 );
$body = Anthropic::body( $req );
smai_assert( 'image' === $body['messages'][0]['content'][1]['type'] && 'base64' === $body['messages'][0]['content'][1]['source']['type'] && 'document' === $body['messages'][0]['content'][2]['type'] && 'S' === $body['system'] && 500 === $body['max_tokens'], 'Anthropic request: text, image and PDF blocks, system prompt' );
$body = OpenAI::body( $req );
smai_assert( 'system' === $body['messages'][0]['role'] && 'data:image/png;base64,AAAA' === $body['messages'][1]['content'][1]['image_url']['url'] && 'data:application/pdf;base64,BBBB' === $body['messages'][1]['content'][2]['file']['file_data'], 'OpenAI request: system message, image data URL, PDF file part' );

$GLOBALS['smai_http'] = null;
$GLOBALS['smai_sent'] = null;
$mock = static function ( $pre, $args, $url ) {
	$GLOBALS['smai_sent'] = array( 'url' => $url, 'args' => $args );
	return $GLOBALS['smai_http'] ?? $pre;
};
add_filter( 'pre_http_request', $mock, 10, 3 );
$GLOBALS['smai_http'] = array( 'headers' => array(), 'body' => wp_json_encode( array( 'id' => 'msg_1', 'type' => 'message', 'role' => 'assistant', 'content' => array( array( 'type' => 'text', 'text' => "```json\n{\"ok\":true}\n```" ) ), 'usage' => array( 'input_tokens' => 1200, 'output_tokens' => 300 ) ) ), 'response' => array( 'code' => 200, 'message' => 'OK' ), 'cookies' => array() );
$r = Anthropic::complete( $req );
$h = $GLOBALS['smai_sent']['args']['headers'];
smai_assert( $r['ok'] && 1200 === $r['tokens_in'] && 300 === $r['tokens_out'] && array( 'ok' => true ) === Gateway::decode( $r['text'] ), 'Anthropic answer read: text (even in a code fence) and tokens' );
smai_assert( Anthropic::URL === $GLOBALS['smai_sent']['url'] && 'sk-ant-TEST-1234abcd' === $h['x-api-key'] && '2023-06-01' === $h['anthropic-version'], 'Anthropic call: URL, key and version headers' );
$GLOBALS['smai_http'] = array( 'headers' => array(), 'body' => wp_json_encode( array( 'type' => 'error', 'error' => array( 'type' => 'authentication_error', 'message' => 'invalid x-api-key' ) ) ), 'response' => array( 'code' => 401, 'message' => 'Unauthorized' ), 'cookies' => array() );
$r = Anthropic::complete( $req );
smai_assert( ! $r['ok'] && 'invalid x-api-key' === $r['error'] && 401 === $r['http'], 'Anthropic error read (message and status)' );

$in = get_option( Settings::OPTION );
$in['providers']['openai'] = array( 'key' => 'sk-proj-OPENAI-9999', 'model' => 'test-model', 'base_url' => 'https://api.example-ai.test/v1/' );
Settings::save( $in );
$GLOBALS['smai_http'] = array( 'headers' => array(), 'body' => wp_json_encode( array( 'id' => 'c1', 'choices' => array( array( 'index' => 0, 'message' => array( 'role' => 'assistant', 'content' => '{"a":1}' ), 'finish_reason' => 'stop' ) ), 'usage' => array( 'prompt_tokens' => 50, 'completion_tokens' => 7 ) ) ), 'response' => array( 'code' => 200, 'message' => 'OK' ), 'cookies' => array() );
$r = OpenAI::complete( $req );
smai_assert( $r['ok'] && '{"a":1}' === $r['text'] && 50 === $r['tokens_in'] && 7 === $r['tokens_out'], 'OpenAI-compatible answer read' );
smai_assert( 'https://api.example-ai.test/v1/chat/completions' === $GLOBALS['smai_sent']['url'] && 'Bearer sk-proj-OPENAI-9999' === $GLOBALS['smai_sent']['args']['headers']['Authorization'], 'OpenAI-compatible call: base URL setting and bearer key' );
$GLOBALS['smai_http'] = new WP_Error( 'http_request_failed', 'cURL error 28: timeout' );
$r = OpenAI::complete( $req );
smai_assert( ! $r['ok'] && false !== strpos( $r['error'], 'timeout' ), 'network failure reported' );
smai_assert( null === Gateway::decode( 'no json here' ) && array( 'x' => 2 ) === Gateway::decode( 'Voici : {"x":2} fin' ), 'JSON found inside text; none → refused' );

/* ------------------------------------------------------------------ */
smai_section( '3. Gateway: rights, quotas, budget, usage log' );
// sm-ai on its own: the plan credits of sm-subscriptions (tested there) replace these quotas.
foreach ( array( 'sm_ai_plan_credits', 'sm_ai_can_use', 'sm_ai_remaining', 'sm_ai_used' ) as $h ) {
	remove_all_filters( $h );
}
list( $u_seller, $c_seller ) = smai_user( 'seller', 'company', array( 'supplier' ) );
list( $u_eng, $c_eng )       = smai_user( 'eng', 'engineer', array( 'engineer' ) );
wp_set_current_user( $u_seller );
$GLOBALS['smai_http'] = array( 'headers' => array(), 'body' => wp_json_encode( array( 'content' => array( array( 'type' => 'text', 'text' => wp_json_encode( array( 'market' => 'products', 'category' => 'profiles', 'family' => 'IPE', 'designation' => 'IPE 200', 'grade' => 'S275JR', 'title_fr' => 'IPE 200 S275JR', 'title_ar' => 'IPE 200', 'description_fr' => '<b>Barres</b> neuves', 'description_ar' => '', 'missing' => array( 'Longueur ?' ), 'warnings' => array(), 'confidence' => 1.7, 'weight' => 999 ) ) ) ), 'usage' => array( 'input_tokens' => 2000, 'output_tokens' => 400 ) ) ), 'response' => array( 'code' => 200, 'message' => 'OK' ), 'cookies' => array() );
$in = get_option( Settings::OPTION );
$in['features']['listing']['per_company_month'] = 2;
$in['features']['listing']['model'] = '';
Settings::save( $in );
$r = Gateway::run( 'listing', array( 'notes' => 'IPE 200 neufs' ) );
smai_assert( is_array( $r ) && 'profiles' === $r['category'] && 'IPE 200' === $r['section']['designation'] && 'S275JR' === $r['grade'], 'listing assistant through Anthropic: category, profile and grade checked against the catalogue' );
smai_assert( 1.0 === $r['confidence'] && 'Barres neuves' === $r['description']['fr'] && ! isset( $r['weight'] ), 'answer cleaned: confidence capped, HTML removed, no weight kept' );
$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sm_ai_usage WHERE company_id = %d ORDER BY id DESC LIMIT 1", $c_seller ) );
smai_assert( 'ok' === $row->status && 2000 === (int) $row->tokens_in && abs( (float) $row->cost - ( 2000 * 3 + 400 * 15 ) / 1000000 ) < 0.000001, 'usage logged with tokens and cost (prices per million tokens)' );
Gateway::run( 'listing', array( 'notes' => 'x' ) );
$r = Gateway::run( 'listing', array( 'notes' => 'x' ) );
smai_assert( is_wp_error( $r ) && 'sm_ai_month' === $r->get_error_code() && 0 === Gateway::remaining( 'listing', Companies::get( $c_seller ) ), 'monthly quota of the company enforced' );
smai_assert( 'blocked' === $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$wpdb->prefix}sm_ai_usage WHERE company_id = %d ORDER BY id DESC LIMIT 1", $c_seller ) ), 'refusal logged as blocked' );
wp_set_current_user( $u_eng );
$r = Gateway::run( 'listing', array( 'notes' => 'x' ) );
smai_assert( is_wp_error( $r ) && 'sm_ai_cap' === $r->get_error_code(), 'engineering account: no listing assistant (no trading)' );
smai_assert( true === Gateway::check( 'recognize' ) && true === Gateway::check( 'document' ), 'engineering account: recognition and document analysis allowed' );
add_filter( 'sm_ai_can_use', $deny = static fn( $ok, $f, $c ) => 'document' === $f ? new WP_Error( 'sm_ai_credits', 'Plus de crédits IA.' ) : $ok, 10, 3 );
smai_assert( 'sm_ai_credits' === Gateway::check( 'document' )->get_error_code(), 'sm_ai_can_use filter can refuse (AI credits, Phase 10)' );
remove_filter( 'sm_ai_can_use', $deny, 10 );
$in = get_option( Settings::OPTION );
$in['monthly_budget'] = 0.000001;
Settings::save( $in );
smai_assert( 'sm_ai_budget' === Gateway::check( 'recognize' )->get_error_code(), 'monthly budget reached: every service stops' );
$in['monthly_budget'] = 0;
$in['per_user_day'] = 1;
Settings::save( $in );
wp_set_current_user( $u_seller );
smai_assert( 'sm_ai_day' === Gateway::check( 'recognize' )->get_error_code(), 'daily limit per user' );
$in['per_user_day'] = 40;
$in['features']['listing']['enabled'] = 0;
Settings::save( $in );
smai_assert( 'sm_ai_off' === Gateway::check( 'listing' )->get_error_code(), 'service switched off' );
wp_set_current_user( 0 );
smai_assert( 'sm_ai_account' === Gateway::check( 'recognize' )->get_error_code() && true === Gateway::check( 'search' ), 'visitors: smart search only' );
$GLOBALS['smai_http'] = array( 'headers' => array(), 'body' => wp_json_encode( array( 'content' => array( array( 'type' => 'text', 'text' => 'Désolé, je ne peux pas.' ) ), 'usage' => array( 'input_tokens' => 10, 'output_tokens' => 5 ) ) ), 'response' => array( 'code' => 200, 'message' => 'OK' ), 'cookies' => array() );
$r = Gateway::run( 'search', array( 'q' => 'IPE' ) );
smai_assert( is_wp_error( $r ) && 'sm_ai_answer' === $r->get_error_code(), 'unreadable answer: clear error, logged' );
$in['features']['search']['visitors_per_ip_day'] = 1;
Settings::save( $in );
smai_assert( 'sm_ai_visitor' === Gateway::check( 'search' )->get_error_code(), 'visitor limit per IP and day' );
remove_filter( 'pre_http_request', $mock, 10 );

/* ------------------------------------------------------------------ */
smai_section( '4. Answers checked against the platform' );
$ipe = Catalog::section( 'IPE', 'IPE 200' );
smai_assert( $ipe && 'IPE' === $ipe->family && Catalog::section( 'IPE', '200' ) && Catalog::section( 'ipe', 'IPE200' ) && null === Catalog::section( 'IPE', 'IPE 999' ) && null === Catalog::section( 'XYZ', '1' ), 'designations matched in the catalogue ("IPE 200", "200", "IPE200"); unknown ones refused' );
$hit = Catalog::detect( 'Besoin de 12 HEA200 et 4 UPN 120' );
smai_assert( $hit && 'HEA' === $hit[0], 'designation found in a sentence' );
smai_assert( '31' === Catalog::wilaya( 'Oran' ) && '16' === Catalog::wilaya( 'الجزائر' ) && '28' === Catalog::wilaya( '28' ) && '' === Catalog::wilaya( 'Paris' ), 'wilaya from a name (FR/AR) or a number' );
$c = Recognize::clean( array( 'candidates' => array( array( 'family' => 'HEA', 'designation' => '', 'confidence' => 0.2 ), array( 'family' => 'IPE', 'designation' => 'IPE 200', 'confidence' => 0.7, 'reason' => 'ailes' ), array( 'family' => 'XYZ', 'confidence' => 0.9 ) ) ), array() );
smai_assert( 2 === count( $c['candidates'] ) && 'IPE' === $c['candidates'][0]['family'] && $c['candidates'][0]['section'] && null === $c['candidates'][1]['section'], 'recognition: unknown family dropped, sorted by confidence, size only when in the catalogue' );
smai_assert( abs( $c['candidates'][0]['section']['mass_kg_m'] - (float) $ipe->mass_kg_m ) < 0.0001, 'mass per metre from the catalogue, not from the AI' );
$s = Search::clean( array( 'market' => 'nope', 'category' => 'reuse-profiles', 'q' => 'ipe200', 'wilaya' => 'Oran', 'length_min' => '6', 'price_max' => -5, 'condition' => 'good', 'mode' => 'assembled' ), array( 'q' => 'x' ) );
smai_assert( array( 'market' => 'reuse', 'category' => 'reuse-profiles', 'q' => 'IPE 200', 'wilaya' => '31', 'condition' => 'good', 'length_min' => '6' ) === $s['filters'], 'search filters: market from the category, designation normalised, bad values dropped' );
$d = Document::clean( array( 'items' => array( array( 'family' => 'IPE', 'designation' => 'IPE 200', 'grade' => 's275jr', 'length_m' => '6', 'quantity' => 10, 'unit' => 'pieces' ), array( 'family' => 'UPN', 'designation' => 'UPN 999', 'length_m' => 50, 'quantity' => 2 ), array( 'designation' => 'HEA 160', 'quantity' => 300, 'unit' => 'kg' ) ) ), array() );
$eng = SM\Core\Modules::service( 'weight' );
$exp = $eng->calculate( array( 'family' => 'IPE', 'section' => $ipe->id, 'length' => 6000, 'bars' => 10 ) );
smai_assert( 3 === count( $d['items'] ) && 2 === $d['known'] && 'S275JR' === $d['items'][0]['grade'] && abs( $d['items'][0]['weight_kg'] - round( $exp['total_kg'], 1 ) ) < 0.05, 'document: catalogue profiles found, weights from the weight engine' );
smai_assert( null === $d['items'][1]['section'] && 0.0 === (float) $d['items'][1]['length_m'] && 'HEA' === $d['items'][2]['family'] && 0.0 === (float) $d['items'][2]['weight_kg'], 'unknown size kept to specify; impossible length dropped; kilos not converted' );
$v = Document::rfq_values( $d, 'fr' );
smai_assert( 1 === count( $v['lines'] ) && (string) $ipe->id === $v['lines'][0]['section'] && '10' === $v['lines'][0]['qty'] && false !== strpos( $v['description'], 'UPN 999' ), 'request for quotation: weighable rows, other items in the description' );
$l = Listing::clean( array( 'category' => 'reuse-profiles', 'family' => 'HEA', 'designation' => 'HEA 200', 'condition' => 'good', 'title_fr' => str_repeat( 'a', 300 ) ), array( 'company' => Companies::get( $c_seller ) ) );
smai_assert( 'reuse' === $l['market'] && 'good' === $l['condition'] && 140 === mb_strlen( $l['title']['fr'] ) && 'HEA 200' === $l['section']['designation'], 'listing: market of the category, condition of that market, titles limited' );
$lv = Listing::form_values( $l, 'fr' );
smai_assert( isset( $lv['section'] ) && 'HEA' === $lv['family'] && ! isset( $lv['lines'] ), 'listing form values: the size for an engine category (reuse too since sm-reuse 0.3.0; the seller adds the lengths)' );
$l2 = Listing::clean( array( 'category' => 'bld-industrial', 'title_fr' => 'Hangar' ), array( 'company' => Companies::get( $c_eng ) ) );
smai_assert( '' === $l2['market'] && '' === $l2['category'], 'listing: no market for an account that cannot publish' );

/* ------------------------------------------------------------------ */
smai_section( '5. Simulation provider and stats' );
if ( Settings::simulation_allowed() ) {
	$in = get_option( Settings::OPTION );
	$in['default_provider'] = 'simulation';
	$in['features']['listing']['enabled'] = 1;
	$in['features']['listing']['per_company_month'] = 30;
	foreach ( array( 'listing', 'recognize', 'search', 'document', 'describe', 'cutlist', 'condition', 'report' ) as $f ) {
		$in['features'][ $f ]['enabled']  = 1;
		$in['features'][ $f ]['provider'] = '';
	}
	Settings::save( $in );
	wp_set_current_user( $u_seller );
	$r = Gateway::run( 'document', array() );
	smai_assert( is_array( $r ) && $r['_meta']['simulation'] && $r['known'] >= 2, 'simulation: document analysis flow without external call' );
	$r = Gateway::run( 'search', array( 'q' => 'IPE 200 occasion Oran 6 m' ) );
	smai_assert( 'reuse' === $r['filters']['market'] && '31' === $r['filters']['wilaya'] && 'IPE 200' === $r['filters']['q'], 'simulation: smart search' );
	$st = Usage::month_stats();
	smai_assert( $st['document']['ok'] >= 1 && $st['listing']['blocked'] >= 1, 'monthly statistics per service' );

	// 0.2.0: description writer, free smart search.
	$cid    = (int) sm_current_company()->id;
	$before_total = Usage::company_month_total( $cid );
	$r = Gateway::run( 'search', array( 'q' => 'HEA 160' ) );
	smai_assert( is_array( $r ) && Usage::company_month_total( $cid ) === $before_total, 'smart search is free: no AI credit counted' );
	smai_assert( in_array( 'search', Gateway::free_features(), true ) && ! in_array( 'describe', Gateway::free_features(), true ), 'free services: search only (and the connection test)' );
	$r = Gateway::run( 'describe', array( 'facts' => "Catégorie: Poutrelles IPE\nProfilé: IPE 200\nNombre de barres: 24\nLongueur: 6 m" ) );
	smai_assert( is_array( $r ) && false !== strpos( $r['description'], 'IPE 200' ) && Usage::company_month_total( $cid ) === $before_total + 1, 'description written from the facts of the form, 1 credit' );
	$c = SM\Ai\Features\Describe::clean( array( 'description' => 'Beau lot. Appelez le 0550 12 34 56 ou ecrire a vente@exemple.dz, voir https://exemple.dz/lot' ), array() );
	smai_assert( false === strpos( $c['description'], '0550' ) && false === strpos( $c['description'], '@' ) && false === strpos( $c['description'], 'http' ), 'no phone, e-mail or link kept in a description' );
	wp_set_current_user( 0 );
	$e = Gateway::check( 'describe' );
	smai_assert( is_wp_error( $e ) && 'login' === SM\Ai\Form::state_of( $e ), 'visitor: the description writer asks to sign in' );
	wp_set_current_user( $u_seller );

	echo "\nDQE IA for the cutting optimiser (0.3.0)\n";
	$fx   = __DIR__ . '/fixtures/';
	$xlsx = SM\Ai\Sheets::text( $fx . 'dqe-test.xlsx', SM\Ai\Sheets::kind( $fx . 'dqe-test.xlsx', 'dqe-test.xlsx' ) );
	smai_assert( is_string( $xlsx ) && false !== strpos( $xlsx, 'Poteaux HEA 200' ) && false !== strpos( $xlsx, 'Platines 300x250' ) && false !== strpos( $xlsx, 'Tôle TN40' ), 'Excel read as a table, every sheet (shared strings and numbers)' );
	$csv = SM\Ai\Sheets::text( $fx . 'dqe-test.csv', SM\Ai\Sheets::kind( $fx . 'dqe-test.csv', 'dqe-test.csv' ) );
	smai_assert( is_string( $csv ) && false !== strpos( $csv, 'Pannes IPE 120 L=6m | u | 28' ), 'CSV with « ; » read as a table' );
	smai_assert( '' === SM\Ai\Sheets::kind( $fx . 'dqe-test.xlsx', 'dqe.pdf' ), 'a file is a spreadsheet only by its content and its name' );
	$before_total = Usage::company_month_total( $cid );
	$r = Gateway::run( 'cutlist', array( 'files' => array( array( 'type' => 'text', 'sheet' => true, 'text' => $xlsx ) ), 'name' => 'dqe-test.xlsx' ) );
	$b = is_array( $r ) ? array_column( $r['bars'], null, 'line' ) : array();
	smai_assert( is_array( $r ) && 7 === count( $r['bars'] ) && Usage::company_month_total( $cid ) === $before_total + 1, 'document read: 7 lines of bars (one by weight, one in metres), 1 credit' );
	smai_assert( $b && $b['12']['sure'] && 'HEA 200' === $b['12']['section']['designation'] && 6.5 === $b['12']['length_m'] && 12 === $b['12']['quantity'], 'a written line is « sure »: HEA 200, 6,50 m × 12 (checked against the catalogue)' );
	smai_assert( ! $b['14']['sure'] && in_array( 'deduced', $b['14']['why'], true ), 'a line deduced by the AI (half rafters) is « to check »' );
	smai_assert( ! $b['19']['sure'] && null === $b['19']['section'] && in_array( 'section', $b['19']['why'], true ), 'a profile not in the catalogue (UAP) is « to check », to be chosen' );
	smai_assert( ! $b['22']['sure'] && in_array( 'length', $b['22']['why'], true ), 'a missing length is « to check »' );
	$w = $b['24'];
	smai_assert( 'weight' === $w['origin'] && 12.0 === $w['length_m'] && 13.04 === $w['exact'] && 14 === $w['quantity'] && 13 === $w['qty_stock'] && 3500.0 === $w['doc_kg'] && ! $w['sure'] && in_array( 'weight', $w['why'], true ), 'IPE 200 : 3 500 kg → 12 m bars, 13,04 bars: 14 to buy, 13 for the warehouse; to check' );
	$l = $b['25'];
	smai_assert( 'metres' === $l['origin'] && 7 === $l['quantity'] && 7 === $l['qty_stock'] && 'IPE 160' === $l['section']['designation'], '84 m of IPE 160 → 7 bars of 12 m (to check)' );
	$one = SM\Ai\Features\Cutlist::bar( array( 'family' => 'IPE', 'designation' => 'IPE 200', 'quantity' => 10, 'weight_kg' => 1341.6 ), false, \SM\Core\Modules::service( 'weight' ) );
	smai_assert( 6.0 === $one['length_m'] && 10 === $one['quantity'], 'number and weight written: the length of one bar is computed (1 341,6 kg / 10 IPE 200 = 6 m)' );
	$pl = $r['plates'];
	smai_assert( 3 === count( $pl ) && $pl[0]['sure'] && abs( $pl[0]['kg'] - 105.98 ) < 0.1 && ! $pl[2]['sure'] && 0.0 === $pl[2]['kg'], 'plates: 12 × 300 × 250 × 15 = 106,0 kg by the weight engine; missing dimensions: to check, no weight' );
	smai_assert( 36 === $r['ignored'] && ! $r['photo'] && ! $r['unreadable'], 'lines that are not steel framing only counted' );
	$r = Gateway::run( 'cutlist', array( 'files' => array( array( 'type' => 'image', 'mime' => 'image/jpeg', 'data' => '' ) ), 'name' => 'photo.jpg' ) );
	smai_assert( is_array( $r ) && $r['photo'] && ! array_filter( array_column( $r['bars'], 'sure' ) ) && ! array_filter( array_column( $r['plates'], 'sure' ) ), 'photo: every line is « to check »' );
	$n = Usage::company_month_total( $cid );
	$r = Gateway::run( 'cutlist', array( 'files' => array( array( 'type' => 'image', 'mime' => 'image/jpeg', 'data' => '' ) ), 'name' => 'flou.jpg' ) );
	smai_assert( is_array( $r ) && $r['unreadable'] && ! $r['bars'] && Usage::company_month_total( $cid ) === $n + 1, 'unreadable photo: said so, and the credit is used' );
	wp_set_current_user( 0 );
	smai_assert( 'login' === SM\Ai\Form::state_of( Gateway::check( 'cutlist' ) ), 'visitor: asked to sign in' );
	wp_set_current_user( $u_seller );

	smai_section( '7. Photos of the publication form: condition of reused steel (0.5.0)' );
	$C = 'SM\\Ai\\Features\\Condition';
	$c = $C::clean( array( 'condition' => 'excellent', 'surface' => 'clean', 'close_photos' => false, 'confidence' => 0.9, 'sees' => array( 'a' ), 'cannot_see' => array() ), array() );
	smai_assert( 'inspection' === $c['condition'], '« excellent » without clear close photos becomes « to inspect »' );
	$c = $C::clean( array( 'condition' => 'excellent', 'surface' => 'clean', 'close_photos' => true, 'confidence' => 0.9 ), array() );
	smai_assert( 'excellent' === $c['condition'] && 'clean' === $c['surface'], '« excellent » kept with clear close photos' );
	$c = $C::clean( array( 'condition' => 'perfect', 'surface' => 'shiny', 'confidence' => 4, 'sees' => array_fill( 0, 9, 'x' ) ), array() );
	smai_assert( 'inspection' === $c['condition'] && '' === $c['surface'] && $c['confidence'] <= 1 && count( $c['sees'] ) <= 5, 'unknown condition: to inspect; unknown surface dropped; confidence and lists bounded' );
	$n = Usage::company_month_total( $cid );
	$r = Gateway::run( 'condition', array( 'files' => array( array( 'type' => 'image', 'mime' => 'image/jpeg', 'data' => '' ) ), 'notes' => '' ) );
	smai_assert( is_array( $r ) && 'good' === $r['condition'] && 'light_rust' === $r['surface'] && $r['sees'] && $r['cannot_see'] && Usage::company_month_total( $cid ) === $n + 1, 'simulation: good, light surface rust, what is seen and not seen; one use counted' );
	$d = apply_filters( 'sm_theme_data_ai_form_photo', null, array() );
	smai_assert( is_array( $d ) && isset( $d['tools']['recognize'], $d['tools']['condition'] ) && 'ok' === $d['tools']['condition']['state'] && 1 === $d['tools']['condition']['credits'] && wp_verify_nonce( $d['nonce'], 'sm_ai_form_photo' ), 'theme data: both photo tools, state, credits, ajax key' );
	wp_set_current_user( 0 );
	$d = apply_filters( 'sm_theme_data_ai_form_photo', null, array() );
	smai_assert( ! is_array( $d ) || 'login' === $d['tools']['condition']['state'], 'visitor: asked to sign in' );
	wp_set_current_user( $u_seller );
	$in = get_option( Settings::OPTION );
	$in['features']['condition']['enabled'] = 0;
	Settings::save( $in );
	$d = apply_filters( 'sm_theme_data_ai_form_photo', null, array() );
	smai_assert( is_array( $d ) && ! isset( $d['tools']['condition'] ) && isset( $d['tools']['recognize'] ), 'service off: not offered in the form' );
	$in['features']['condition']['enabled'] = 1;
	Settings::save( $in );

	smai_section( '8. Summary of the Business report (0.6.0)' );
	$R = 'SM\\Ai\\Features\\Report';
	smai_assert( 0 === $R::credits() && in_array( 'report', Gateway::free_features(), true ), 'free: no AI credit (the report belongs to the Business plan)' );
	$c = $R::clean( array( 'points' => array( 'Les IPE montent de 3 %.', 'Il y a eu 41 ventes de IPE 200.', 'IPE 200 : 12 sales this month', 'Le réemploi fait 22 % du poids.' ) ), array() );
	smai_assert( array( 'Les IPE montent de 3 %.', 'Le réemploi fait 22 % du poids.' ) === $c['points'], 'a number of sales written by the model is dropped' );
	$n   = Usage::company_month_total( $cid );
	$pts = apply_filters( 'sm_ai_report_summary', null, array( 'prices' => array( array( 'label' => 'IPE', 'new_change' => 3.1 ) ), 'top_searched' => array( 'IPE 200' ), 'weights' => array( 'reuse_pct' => 22 ) ), 'fr' );
	smai_assert( is_array( $pts ) && 3 === count( $pts ) && Usage::company_month_total( $cid ) === $n, 'filter sm_ai_report_summary: points from the figures, no credit counted' );
	wp_set_current_user( 0 );
	smai_assert( is_wp_error( apply_filters( 'sm_ai_report_summary', null, array(), 'fr' ) ), 'visitor: refused' );
	wp_set_current_user( $u_seller );
} else {
	echo "  (SM_AI_SIMULATION not set: simulation tests skipped)\n";
}

/* ------------------------------------------------------------------ */
wp_set_current_user( 0 );
smai_cleanup();
$wpdb->delete( $wpdb->prefix . 'sm_ai_usage', array( 'ip' => '10.9.8.7' ) );
null === $before ? delete_option( Settings::OPTION ) : update_option( Settings::OPTION, $before, false );
$t = $GLOBALS['smai_t'];
echo "\n{$t['pass']} passed, {$t['fail']} failed\n";
if ( $t['fail'] ) {
	echo 'Failed: ' . implode( ' | ', $t['fails'] ) . "\n";
}
