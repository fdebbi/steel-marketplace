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
$s = Search::clean( array( 'market' => 'nope', 'category' => 'reuse-offcuts', 'q' => 'ipe200', 'wilaya' => 'Oran', 'length_min' => '6', 'price_max' => -5, 'condition' => 'good', 'mode' => 'assembled' ), array( 'q' => 'x' ) );
smai_assert( array( 'market' => 'reuse', 'category' => 'reuse-offcuts', 'q' => 'IPE 200', 'wilaya' => '31', 'condition' => 'good', 'length_min' => '6' ) === $s['filters'], 'search filters: market from the category, designation normalised, bad values dropped' );
$d = Document::clean( array( 'items' => array( array( 'family' => 'IPE', 'designation' => 'IPE 200', 'grade' => 's275jr', 'length_m' => '6', 'quantity' => 10, 'unit' => 'pieces' ), array( 'family' => 'UPN', 'designation' => 'UPN 999', 'length_m' => 50, 'quantity' => 2 ), array( 'designation' => 'HEA 160', 'quantity' => 300, 'unit' => 'kg' ) ) ), array() );
$eng = SM\Core\Modules::service( 'weight' );
$exp = $eng->calculate( array( 'family' => 'IPE', 'section' => $ipe->id, 'length' => 6000, 'bars' => 10 ) );
smai_assert( 3 === count( $d['items'] ) && 2 === $d['known'] && 'S275JR' === $d['items'][0]['grade'] && abs( $d['items'][0]['weight_kg'] - round( $exp['total_kg'], 1 ) ) < 0.05, 'document: catalogue profiles found, weights from the weight engine' );
smai_assert( null === $d['items'][1]['section'] && 0.0 === (float) $d['items'][1]['length_m'] && 'HEA' === $d['items'][2]['family'] && 0.0 === (float) $d['items'][2]['weight_kg'], 'unknown size kept to specify; impossible length dropped; kilos not converted' );
$v = Document::rfq_values( $d, 'fr' );
smai_assert( 1 === count( $v['lines'] ) && (string) $ipe->id === $v['lines'][0]['section'] && '10' === $v['lines'][0]['qty'] && false !== strpos( $v['description'], 'UPN 999' ), 'request for quotation: weighable rows, other items in the description' );
$l = Listing::clean( array( 'category' => 'reuse-offcuts', 'family' => 'HEA', 'designation' => 'HEA 200', 'condition' => 'good', 'title_fr' => str_repeat( 'a', 300 ) ), array( 'company' => Companies::get( $c_seller ) ) );
smai_assert( 'reuse' === $l['market'] && 'good' === $l['condition'] && 140 === mb_strlen( $l['title']['fr'] ) && 'HEA 200' === $l['section']['designation'], 'listing: market of the category, condition of that market, titles limited' );
$lv = Listing::form_values( $l, 'fr' );
smai_assert( isset( $lv['lines'][0]['section'] ) && ! isset( $lv['section'] ), 'listing form values: rows for a lot category, single profile for a product category' );
$l2 = Listing::clean( array( 'category' => 'bld-industrial', 'title_fr' => 'Hangar' ), array( 'company' => Companies::get( $c_eng ) ) );
smai_assert( '' === $l2['market'] && '' === $l2['category'], 'listing: no market for an account that cannot publish' );

/* ------------------------------------------------------------------ */
smai_section( '5. Simulation provider and stats' );
if ( Settings::simulation_allowed() ) {
	$in = get_option( Settings::OPTION );
	$in['default_provider'] = 'simulation';
	$in['features']['listing']['enabled'] = 1;
	$in['features']['listing']['per_company_month'] = 30;
	foreach ( array( 'listing', 'recognize', 'search', 'document' ) as $f ) {
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
