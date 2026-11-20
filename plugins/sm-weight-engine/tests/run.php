<?php
/**
 * Tests of the weight engine. Run on a TEST site only (changes then restores data):
 *   wp eval-file wp-content/plugins/sm-weight-engine/tests/run.php
 *
 * @package SM\WeightEngine
 */

use SM\WeightEngine\Calculator;
use SM\WeightEngine\Catalogue;
use SM\WeightEngine\Domain\Geometry;
use SM\WeightEngine\Install\Installer;
use SM\WeightEngine\Settings;

defined( 'ABSPATH' ) || exit;

$GLOBALS['smw_t'] = array( 'pass' => 0, 'fail' => 0, 'fails' => array() );
function smw_assert( $cond, $label ) {
	$t = &$GLOBALS['smw_t'];
	if ( $cond ) {
		++$t['pass'];
		echo "  ✓ $label\n";
	} else {
		++$t['fail'];
		$t['fails'][] = $label;
		echo "  ✗ $label\n";
	}
}
function smw_section( $title ) {
	echo "\n$title\n";
}
function smw_near( $a, $b, $tol ) {
	return abs( (float) $a - (float) $b ) <= $tol;
}

global $wpdb;
$saved_settings = get_option( Settings::OPTION );

/* ------------------------------------------------------------------ */
smw_section( '1. Installation and reference data' );
smw_assert( (bool) $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}sm_steel_sections'" ), 'tables created' );
$families = require SM_WEIGHT_DIR . '/data/families.php';
$sections = require SM_WEIGHT_DIR . '/data/sections.php';
$expected = array_sum( array_map( 'count', $sections ) );
smw_assert( (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_steel_sections WHERE origin = 'reference'" ) === $expected, "$expected reference sizes imported" );
smw_assert( 16 === count( Catalogue::families( true ) ), '16 families (rolled sections, tubes, bars, plates)' );
smw_assert( get_option( 'sm_weight_ref_version' ) === SM_WEIGHT_REF_VERSION, 'reference version recorded' );
smw_assert( SM\Core\Modules::service( 'weight' ) instanceof SM\WeightEngine\Service, 'service "weight" available to other plugins' );

/* ------------------------------------------------------------------ */
smw_section( '2. Geometry reproduces every reference row' );
$worst = 0;
$mass_out = array();
foreach ( $sections as $fam => $rows ) {
	foreach ( $rows as $r ) {
		$a     = Geometry::area( $families[ $fam ]['shape'], $r[1] );
		$worst = max( $worst, abs( $a - $r[2] ) / $r[2] );
		if ( null !== $r[3] ) {
			$g    = $a * 7850 / 1e6;
			$step = $r[3] >= 100 && floor( $r[3] ) == $r[3] ? 1.0 : 0.1; // phpcs:ignore Universal.Operators.StrictComparisons
			if ( abs( $g - $r[3] ) > max( $step / 2 + 0.02, $r[3] * 0.012 ) ) {
				$mass_out[] = $r[0];
			}
		}
	}
}
smw_assert( $worst < 0.0005, sprintf( 'PHP area = verified area for all %d rows (largest gap %.4f %%)', $expected, $worst * 100 ) );
smw_assert( ! $mass_out, 'computed mass agrees with the published mass for every rolled section' . ( $mass_out ? ': ' . implode( ', ', $mass_out ) : '' ) );
smw_assert( smw_near( Geometry::area( 'rhs', array( 'h' => 100, 'b' => 100, 't' => 4 ) ), 1494.8, 0.1 ), 'SHS 100x100x4 = 14.95 cm2 (EN 10219-2, Ferpinta)' );
smw_assert( smw_near( Geometry::area( 'rhs', array( 'h' => 120, 'b' => 120, 't' => 12 ) ), 4566, 1 ), 'SHS 120x120x12 uses the 3t corner radius (45.66 cm2)' );
smw_assert( smw_near( Geometry::area( 'chs', array( 'd' => 114.3, 't' => 10 ) ), 3276.7, 0.1 ), 'CHS 114.3x10 = 32.77 cm2' );
smw_assert( true !== Geometry::validate( 'rhs', array( 'h' => 20, 'b' => 20, 't' => 6 ) ), 'impossible tube (t too large) refused' );
smw_assert( true !== Geometry::validate( 'i_parallel', array( 'h' => 100, 'b' => 50, 'tw' => 60, 'tf' => 5, 'r' => 5 ) ), 'web thicker than the flange width refused' );

/* ------------------------------------------------------------------ */
smw_section( '3. Calculations' );
$r = Calculator::calculate( array( 'family' => 'IPE', 'section' => 'IPE 200', 'bars' => 10 ) );
smw_assert( ! is_wp_error( $r ) && smw_near( $r['mass_kg_m'], 22.36, 0.01 ) && 12000.0 === (float) $r['length_mm'], 'IPE 200: 22.36 kg/m, default length 12 m' );
smw_assert( smw_near( $r['per_piece_kg'], 268.32, 0.01 ) && smw_near( $r['total_kg'], 2683.2, 0.05 ), '10 bars of 12 m = 2683.2 kg' );
smw_assert( 'theoretical' === $r['weight_source'] && SM_WEIGHT_VERSION === $r['engine'] && SM_WEIGHT_REF_VERSION === $r['reference'] && $r['section_id'] > 0, 'result carries weight source, versions and section ID' );
$w = Calculator::calculate( array( 'family' => 'IPE', 'section' => $r['section_id'], 'mode' => 'weight', 'total_kg' => '5000' ) );
smw_assert( smw_near( $w['bars_exact'], 18.63, 0.01 ) && 18 === $w['bars_full'] && smw_near( $w['remainder_kg'], 170.24, 0.01 ), '5000 kg of IPE 200 = 18.63 bars (18 full bars + 170.24 kg)' );
$six = Calculator::calculate( array( 'family' => 'IPE', 'section' => 'IPE 200', 'bars' => 1, 'length' => 6000 ) );
smw_assert( smw_near( $six['total_kg'], 134.16, 0.01 ), 'a 6 m bar weighs half a 12 m bar' );
$t = Calculator::calculate( array( 'family' => 'RHS', 'dims' => array( 'h' => '120', 'b' => '80', 't' => '4' ), 'bars' => 2, 'length' => 6000 ) );
smw_assert( ! is_wp_error( $t ) && 'RHS 120x80x4' === $t['designation'] && smw_near( $t['mass_kg_m'], 11.734, 0.001 ), 'typed tube RHS 120x80x4 = 11.734 kg/m' );
$p = Calculator::calculate( array( 'family' => 'PLATE', 'dims' => array( 't' => '10' ), 'width' => 1500, 'length' => 3000, 'bars' => 4 ) );
smw_assert( smw_near( $p['kg_per_m2'], 78.5, 0.001 ) && smw_near( $p['per_piece_kg'], 353.25, 0.001 ) && smw_near( $p['total_kg'], 1413, 0.001 ), 'plate 10 mm 1500x3000: 78.5 kg/m², 353.25 kg each' );
$f = Calculator::calculate( array( 'family' => 'FLAT', 'dims' => array( 'b' => '100', 't' => '10' ), 'bars' => 1, 'length' => '6000' ) );
smw_assert( smw_near( $f['mass_kg_m'], 7.85, 0.001 ), 'flat 100x10 = 7.85 kg/m' );
$comma = Calculator::calculate( array( 'family' => 'CHS', 'dims' => array( 'd' => '114,3', 't' => '10' ), 'bars' => 1 ) );
smw_assert( ! is_wp_error( $comma ) && smw_near( $comma['mass_kg_m'], 25.722, 0.001 ), 'decimal comma accepted (114,3)' );

/* ------------------------------------------------------------------ */
smw_section( '4. Refused inputs' );
smw_assert( 'smw_section' === Calculator::calculate( array( 'family' => 'IPE', 'section' => 'IPE 999' ) )->get_error_code(), 'unknown size refused' );
smw_assert( 'smw_family' === Calculator::calculate( array( 'family' => 'XYZ' ) )->get_error_code(), 'unknown family refused' );
smw_assert( 'smw_bars' === Calculator::calculate( array( 'family' => 'IPE', 'section' => 'IPE 200', 'bars' => 0 ) )->get_error_code(), 'zero bars refused' );
smw_assert( 'smw_length' === Calculator::calculate( array( 'family' => 'IPE', 'section' => 'IPE 200', 'length' => 99000 ) )->get_error_code(), 'length above the limit refused' );
smw_assert( 'smw_total' === Calculator::calculate( array( 'family' => 'IPE', 'section' => 'IPE 200', 'mode' => 'weight', 'total_kg' => -5 ) )->get_error_code(), 'negative weight refused' );
smw_assert( 'smw_dims' === Calculator::calculate( array( 'family' => 'RHS', 'dims' => array( 'h' => 20, 'b' => 20, 't' => 8 ) ) )->get_error_code(), 'impossible typed tube refused' );
smw_assert( 'smw_section' === Calculator::calculate( array( 'family' => 'IPE', 'dims' => array( 'h' => 200, 'b' => 100, 'tw' => 5, 'tf' => 8, 'r' => 12 ) ) )->get_error_code(), 'rolled sections cannot be typed freely (catalogue only)' );

/* ------------------------------------------------------------------ */
smw_section( '5. Settings drive the calculation (nothing hard-coded)' );
Settings::save( array( 'density' => 7800, 'bar_lengths' => '12000, 6000, 8000', 'default_length' => 6000 ) );
$r2 = Calculator::calculate( array( 'family' => 'FLAT', 'dims' => array( 'b' => 100, 't' => 10 ), 'bars' => 1 ) );
smw_assert( smw_near( $r2['mass_kg_m'], 7.8, 0.001 ) && 6000.0 === (float) $r2['length_mm'], 'density and default length come from the settings' );
smw_assert( array( 12000, 8000, 6000 ) === Settings::get( 'bar_lengths' ), 'bar lengths saved, sorted' );
update_option( Settings::OPTION, $saved_settings ? $saved_settings : array() );
if ( ! $saved_settings ) {
	delete_option( Settings::OPTION );
}

/* ------------------------------------------------------------------ */
smw_section( '6. Catalogue corrections (admin)' );
$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
wp_set_current_user( $admin->ID );
$ipe = Catalogue::find( 'IPE', 'IPE 200' );
$bad = Catalogue::save( 'IPE', array( 'designation' => 'IPE 200', 'dims' => array_merge( $ipe->dims, array( 'tf' => 18.5 ) ), 'mass_published' => 22.4, 'note' => 'test' ), $ipe->id );
smw_assert( is_wp_error( $bad ) && 'smw_mass_mismatch' === $bad->get_error_code(), 'a typing error (tf 18.5) is caught: mass no longer matches 22.4 kg/m' );
smw_assert( smw_near( Catalogue::get( $ipe->id )->dims['tf'], 8.5, 0.001 ), 'the row was not changed' );
$ok = Catalogue::save( 'IPE', array( 'designation' => 'IPE 200', 'dims' => array_merge( $ipe->dims, array( 'r' => 12.5 ) ), 'mass_published' => 22.4, 'note' => 'test correction' ), $ipe->id );
smw_assert( $ok === $ipe->id && 1 === (int) Catalogue::get( $ipe->id )->edited, 'consistent correction saved and marked as corrected' );
smw_assert( (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}sm_steel_log WHERE section_id = %d AND action = 'section.updated'", $ipe->id ) ), 'correction written to the change log' );
delete_option( 'sm_weight_ref_version' );
$wpdb->update( $wpdb->prefix . 'sm_steel_sections', array( 'ref_version' => 'old' ), array( 'id' => $ipe->id ) );
Installer::import_reference();
smw_assert( smw_near( Catalogue::get( $ipe->id )->dims['r'], 12.5, 0.001 ), 'a reference update never overwrites an administrator correction' );
smw_assert( (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}sm_steel_log WHERE section_id = %d AND action = 'reference.kept_admin_values'", $ipe->id ) ), '…and the difference is logged' );
// Restore the reference row.
$wpdb->update( $wpdb->prefix . 'sm_steel_sections', array( 'dims' => wp_json_encode( $ipe->dims ), 'area_mm2' => $ipe->area_mm2, 'edited' => 0, 'ref_version' => SM_WEIGHT_REF_VERSION ), array( 'id' => $ipe->id ) );
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}sm_steel_log WHERE section_id = %d", $ipe->id ) );
Catalogue::flush();
Catalogue::set_status( $ipe->id, 'disabled' );
smw_assert( 'smw_section' === Calculator::calculate( array( 'family' => 'IPE', 'section' => 'IPE 200' ) )->get_error_code(), 'a disabled size can no longer be used' );
Catalogue::set_status( $ipe->id, 'active' );
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}sm_steel_log WHERE section_id = %d", $ipe->id ) );
wp_set_current_user( 0 );

/* ------------------------------------------------------------------ */
smw_section( '7. REST API' );
$res = rest_do_request( new WP_REST_Request( 'GET', '/sm/v1/weight/families' ) );
$fams = $res->get_data();
smw_assert( 200 === $res->get_status() && 16 === count( $fams ) && false !== strpos( $fams[0]['icon'], '<svg' ), 'GET /weight/families: 16 families with drawings' );
$res = rest_do_request( new WP_REST_Request( 'GET', '/sm/v1/weight/families/HEA' ) );
smw_assert( 24 === count( $res->get_data()['sections'] ), 'GET /weight/families/HEA: 24 sizes' );
$res = rest_do_request( new WP_REST_Request( 'GET', '/sm/v1/weight/sections/' . $ipe->id ) );
smw_assert( false !== strpos( $res->get_data()['drawing'], '<title>IPE 200</title>' ) && 'EN 10365:2017' === $res->get_data()['source']['standard'], 'GET /weight/sections/{id}: drawing and source' );
$req = new WP_REST_Request( 'GET', '/sm/v1/weight/calculate' );
$req->set_query_params( array( 'family' => 'IPE', 'section' => $ipe->id, 'mode' => 'weight', 'total_kg' => 5000 ) );
$res = rest_do_request( $req );
smw_assert( 200 === $res->get_status() && 18 === $res->get_data()['bars_full'], 'GET /weight/calculate (weight -> bars)' );
$req->set_query_params( array( 'family' => 'IPE', 'section' => 'nope' ) );
smw_assert( 400 === rest_do_request( $req )->get_status(), 'bad input -> 400 with a message' );
$req = new WP_REST_Request( 'GET', '/sm/v1/weight/drawing' );
$req->set_query_params( array( 'family' => 'RHS', 'dims' => array( 'h' => 200, 'b' => 100, 't' => 6 ) ) );
smw_assert( 'RHS 200x100x6' === rest_do_request( $req )->get_data()['designation'], 'GET /weight/drawing for a typed tube' );
smw_assert( array( 12000, 6000 ) === rest_do_request( new WP_REST_Request( 'GET', '/sm/v1/weight/settings' ) )->get_data()['bar_lengths'], 'GET /weight/settings: commercial lengths 12 m and 6 m' );

/* ------------------------------------------------------------------ */
smw_section( '8. Drawings' );
$svg = SM\WeightEngine\Drawing::svg( 'i_taper', Catalogue::find( 'IPN', 'IPN 200' )->dims, array( 'title' => 'IPN 200' ) );
smw_assert( 1 === preg_match_all( '/<path class="smw-shape"/', $svg ) && substr_count( $svg, 'A' ) >= 8, 'IPN drawing has its fillets as true arcs' );
smw_assert( false !== strpos( $svg, 'role="img"' ) && false !== strpos( $svg, '<title>IPN 200</title>' ), 'drawing is accessible (role img + title)' );

/* ------------------------------------------------------------------ */
smw_section( '9. Common sizes and plates by kind (0.3.0)' );
smw_assert( 420 === count( Catalogue::sections( 'RHS' ) ) && 58 === count( Catalogue::sections( 'FLAT' ) ) && 24 === count( Catalogue::sections( 'ROUND' ) ) && 40 === count( Catalogue::sections( 'SQUARE' ) ), 'common sizes: 420 RHS, 58 flats, 24 rounds, 40 squares' );
$rhs_out = array();
foreach ( $sections['RHS'] as $r ) {
	if ( abs( Geometry::area( 'rhs', $r[1] ) * 7850 / 1e6 - $r[3] ) > 0.011 ) {
		$rhs_out[] = $r[0];
	}
}
smw_assert( ! $rhs_out, 'every RHS mass agrees with the published mass (≤ 0,01 kg/m)' . ( $rhs_out ? ': ' . implode( ', ', $rhs_out ) : '' ) );
smw_assert( 'RHS 80x40x3' === Catalogue::find( 'RHS', 'RHS 80x40x3' )->designation, 'a typed RHS 80x40x3 is found in the list' );
$p = static fn( $kind, $t, $holes = null ) => Calculator::calculate( array( 'family' => 'PLATE', 'dims' => array( 't' => $t ), 'width' => 1000, 'length' => 2000, 'kind' => $kind, 'holes' => $holes ) );
smw_assert( smw_near( $p( 'tread', 3 )['per_piece_kg'], 58, 0.001 ) && '3/5' === $p( 'tread', 3 )['plate_label'] && 'table' === $p( 'tread', 3 )['plate_basis'], 'tread plate 3/5, 1000 × 2000: 58 kg (29 kg/m², published table)' );
smw_assert( smw_near( $p( 'tread', 7 )['per_piece_kg'], 109.9, 0.001 ), 'tread plate with a thickness out of the table: thickness × 7,85' );
smw_assert( smw_near( $p( 'hot', 10 )['per_piece_kg'], 157, 0.001 ) && smw_near( $p( '', 10 )['per_piece_kg'], 157, 0.001 ), 'black plate 10 mm: 157 kg, same as before 0.3.0' );
smw_assert( smw_near( $p( 'stainless', 2 )['per_piece_kg'], 31.6, 0.001 ), 'stainless 2 mm: 7 900 kg/m³ → 31,6 kg' );
smw_assert( smw_near( $p( 'alu_tread', 3 )['per_piece_kg'], 17.8, 0.001 ) && smw_near( $p( 'alu_tread', 6 )['per_piece_kg'], 32.4, 0.001 ), 'aluminium checker plate: table (3/4,5 = 17,8 kg), else 2 700 kg/m³' );
$perf = $p( 'perforated', 2, array( 'd' => 5, 'p' => 8 ) );
smw_assert( smw_near( $perf['open_ratio'], 0.354, 0.001 ) && smw_near( $perf['per_piece_kg'], 31.4 * ( 1 - 0.35424 ), 0.05 ), 'perforated 2 mm, Ø5 every 8 mm: 35 % open, holes deducted' );
smw_assert( smw_near( $p( 'perforated', 2, array( 'd' => 9, 'p' => 8 ) )['per_piece_kg'], 31.4, 0.001 ), 'holes larger than the pitch are ignored' );
$sheet = Calculator::sheet( 'PLATE', 0, 'PLATE 3', array( 'kind' => 'tread' ) );
smw_assert( smw_near( $sheet['mass_kg_m2'], 29, 0.001 ) && '3/5' === $sheet['plate_label'], 'sheet of a tread plate: 29 kg/m², 3/5' );
wp_set_current_user( 0 );
$pub = SM\WeightEngine\Presenter::family( Catalogue::family( 'RHS' ) );
$sec = SM\WeightEngine\Presenter::section( Catalogue::find( 'RHS', 'RHS 80x40x3' ), true );
smw_assert( '' === $pub['source'] && '' === $pub['source_url'] && '' === $sec['source']['url'] && 'EN 10219-2 (profils creux formés à froid)' === $sec['source']['standard'], 'visitors see the standard, never the source of the data' );
$fam_plate = SM\WeightEngine\Presenter::family( Catalogue::family( 'PLATE' ) );
smw_assert( 8 === count( $fam_plate['plates'] ) && 4 === count( $fam_plate['formats'] ), 'plates: 8 kinds and 4 formats for the form' );

smw_section( 'Cutting optimiser (0.4.0)' );
use SM\WeightEngine\Cutting;
$ipe = Catalogue::find( 'IPE', 'IPE 200' );
$upn = Catalogue::find( 'UPN', 'UPN 100' );
$in  = Cutting::read( array( 'src' => 1, 'bar' => '12', 'kerf' => '3', 'reuse' => '1', 'rows' => array(
	array( 'section' => $ipe->id, 'length' => '5,10', 'qty' => 4 ),
	array( 'section' => $ipe->id, 'length' => '3,30', 'qty' => 6 ),
	array( 'section' => $ipe->id, 'length' => '2,70', 'qty' => 2 ),
	array( 'section' => $upn->id, 'length' => '2,40', 'qty' => 8 ),
) ) );
smw_assert( 12000 === $in['bar'] && 3 === $in['kerf'] && 1000 === $in['reuse'] && 5100 === $in['lines'][0]['length'], 'request read in millimetres ("5,10" → 5100)' );
$r = Cutting::optimize( $in );
smw_assert( $r['ok'] && 2 === count( $r['profiles'] ), 'two profiles optimised' );
$pi = $r['profiles'][0];
$pu = $r['profiles'][1];
smw_assert( 4 === $pi['new_bars'] && $pi['optimal'], 'IPE 200: 4 bars of 12 m (45,6 m of pieces), proven the fewest' );
smw_assert( 2 === $pu['new_bars'] && 2 === $pu['reusable'], 'UPN 100: 5 × 2,40 m no longer fits with the saw kerf → 2 bars, 2 reusable offcuts' );
$pieces = 0;
foreach ( $pi['bars'] as $b ) {
	$pieces += $b['count'] * count( $b['pieces'] );
	smw_assert( array_sum( $b['pieces'] ) + ( count( $b['pieces'] ) - 1 ) * 3 <= $b['length'], 'each bar holds its pieces and the saw cuts (' . implode( '+', $b['pieces'] ) . ')' );
}
smw_assert( 12 === $pieces, 'every piece is placed once' );
smw_near( $pi['bought_kg'], 48 * $ipe->mass_kg_m, 0.05 );
smw_assert( abs( $pi['scrap_kg'] - 2.4 * $ipe->mass_kg_m ) < 0.05 && 5.0 === $pi['scrap_pct'], 'offcuts: 2,40 m of IPE 200 = 5,0 % of the bars' );
smw_assert( 6 === $r['totals']['new_bars'] && 7.9 === $r['totals']['scrap_pct'], 'totals: 6 bars, 7,9 % of offcuts' );
$r2 = Cutting::optimize( Cutting::read( array( 'src' => 1, 'rows' => array( array( 'section' => $upn->id, 'length' => '2,40', 'qty' => 8 ) ), 'stock' => array( array( 'section' => $upn->id, 'length' => '4,90', 'qty' => 2 ) ) ) ) );
smw_assert( 1 === $r2['profiles'][0]['new_bars'] && 2 === $r2['profiles'][0]['stock_used'], 'offcuts in stock used first: 2 × 4,90 m take 4 pieces, 1 new bar' );
$r3 = Cutting::optimize( Cutting::read( array( 'src' => 1, 'rows' => array( array( 'section' => $ipe->id, 'length' => '13', 'qty' => 1 ), array( 'section' => 0, 'length' => '2', 'qty' => 1 ) ) ) ) );
smw_assert( ! $r3['ok'] && 2 === count( $r3['errors'] ), 'piece longer than the bar and missing profile: refused with a message' );
// 0.5.0: offers of the marketplace (a fake one here, sm-products gives the real ones).
$fake = static function ( $offers, $section ) use ( $ipe ) {
	if ( (int) $section->id !== (int) $ipe->id ) {
		return $offers;
	}
	$offers[] = array( 'key' => 'L900001', 'listing_id' => 900001, 'product_id' => 1, 'title' => 'IPE 200 test', 'url' => '#', 'company' => 'Métal Test', 'verified' => true, 'wilaya' => '31', 'condition' => 'excellent', 'grade' => 'S275JR', 'price_per_kg' => 145.0, 'lot_price' => 0.0, 'part' => true, 'lot' => false, 'lines' => array( array( 'length' => 5500, 'qty' => 30 ), array( 'length' => 9000, 'qty' => 1 ) ) );
	$offers[] = array( 'key' => 'L900002', 'listing_id' => 900002, 'product_id' => 2, 'title' => 'IPE 200 lot', 'url' => '#', 'company' => 'Lot Test', 'verified' => false, 'wilaya' => '16', 'condition' => 'good', 'grade' => '', 'price_per_kg' => 0.0, 'lot_price' => 50000.0, 'part' => false, 'lot' => true, 'lines' => array( array( 'length' => 4000, 'qty' => 5 ) ) );
	return $offers;
};
add_filter( 'sm_cutting_market_offers', $fake, 99, 2 );
$rm = Cutting::optimize( Cutting::read( array( 'rows' => array( array( 'section' => $ipe->id, 'length' => '5,40', 'qty' => 30 ), array( 'section' => $ipe->id, 'length' => '3,95', 'qty' => 2 ) ) ) ) );
$pm = $rm['profiles'][0];
$by = array();
foreach ( $pm['market'] as $mo ) {
	$by[ $mo['key'] ] = $mo;
}
smw_assert( isset( $by['L900001'] ) && 30 === ( $by['L900001']['take_bars'][5500] ?? 0 ), 'marketplace: 30 pieces of 5,40 m cut from the 5,50 m bars (closest longer length)' );
smw_assert( 0 === $pm['new_bars'] && isset( $by['L900002'] ) && $by['L900002']['lot'] && 3 === $by['L900002']['lot_rest'] && 50000.0 === $by['L900002']['cost'], 'the 2 × 3,95 m come from a lot sold whole: 3 bars of the lot not needed, lot price' );
smw_assert( abs( $by['L900001']['cost'] - round( 30 * 5.5 * $ipe->mass_kg_m * 145 ) ) < 1, 'estimated cost = weight taken × price shown per kg' );
$rn = Cutting::optimize( Cutting::read( array( 'src' => 1, 'lots' => '', 'market' => 1, 'conds' => array( 'new', 'excellent', 'good' ), 'rows' => array( array( 'section' => $ipe->id, 'length' => '3,95', 'qty' => 2 ) ) ) ) );
smw_assert( ! array_filter( $rn['profiles'][0]['market'], static fn( $mo ) => $mo['lot'] ) && 2 === array_sum( array_map( static fn( $b ) => $b['count'] * count( $b['pieces'] ), $rn['profiles'][0]['bars'] ) ), 'whole lots not ticked: never used, every piece still placed' );
remove_filter( 'sm_cutting_market_offers', $fake, 99 );
smw_assert( 0 === Cutting::optimize( Cutting::read( array( 'src' => 1, 'rows' => array( array( 'section' => $ipe->id, 'length' => '5,40', 'qty' => 2 ) ) ) ) )['profiles'][0]['market_bars'], 'marketplace not ticked: new bars only' );
$pdf = SM\WeightEngine\CuttingActions::render( $r );
smw_assert( 0 === strpos( $pdf, '%PDF-1.4' ) && false !== strpos( $pdf, '%%EOF' ) && false !== strpos( $pdf, 'IPE 200' ), 'cutting plan as a PDF' );
smw_assert( false !== strpos( $pdf, '/Subtype /Link' ) && false !== strpos( $pdf, 'optimiseur-decoupe' ) && false !== strpos( $pdf, '1/1' ), 'PDF footer: ReuSteel, clickable link to the optimiser, page number' );

smw_section( '13. Lines of the DQE IA, plates and quantities in metres (0.7.0)' );
$in = Cutting::read(
	array(
		'src'      => 1,
		'rows'     => array( array( 'section' => $ipe->id, 'length' => '5,40', 'qty' => 2 ) ),
		'ai_bar'   => array( array( 'on' => 1, 'section' => $ipe->id, 'length' => '3,95', 'qty' => 3 ), array( 'section' => $ipe->id, 'length' => '2', 'qty' => 9 ) ),
		'ai_plate' => array( array( 'on' => 1, 't' => '15', 'l' => 300, 'w' => 250, 'qty' => 12, 'label' => 'Platines' ), array( 't' => '10', 'l' => 200, 'w' => 200, 'qty' => 4 ) ),
		'ai_lin'   => array( array( 'on' => 1, 'section' => $ipe->id, 'm' => '84', 'label' => 'Sablières' ) ),
	)
);
smw_assert( 2 === count( $in['lines'] ) && 3950 === $in['lines'][1]['length'] && 1 === count( $in['plates'] ) && 1 === count( $in['lin'] ), 'only the ticked lines of the DQE are added (bars, plates, metres)' );
$rx = Cutting::optimize( $in );
$x  = $rx['extras'];
smw_assert( smw_near( $x['plates'][0]['kg'], 105.98, 0.05 ), 'plate 300 × 250 × 15 × 12 = 106,0 kg (calculator)' );
smw_assert( 7 === $x['linear'][0]['bars'] && smw_near( $x['linear'][0]['kg'], 7 * 12 * (float) $ipe->mass_kg_m, 0.5 ), '84 m → 7 bars of 12 m, weight from the catalogue' );
$kept = Cutting::read( array( 'src' => 1, 'plates' => array( array( 't' => '15', 'l' => 300, 'w' => 250, 'qty' => 12 ) ), 'lin' => array( array( 'section' => $ipe->id, 'm' => '84' ) ) ) );
smw_assert( 1 === count( $kept['plates'] ) && 1 === count( $kept['lin'] ) && ! $kept['lines'], 'plates and metres kept in the address of the page (link, PDF, request)' );
$pdf = SM\WeightEngine\CuttingActions::render( $rx );
smw_assert( false !== strpos( $pdf, 'Plate 15 mm' ) || false !== strpos( $pdf, '15 mm' ), 'PDF: the pieces without a cutting plan are listed' );

$t = $GLOBALS['smw_t'];
printf( "\n%d passed, %d failed\n", $t['pass'], $t['fail'] );
if ( $t['fail'] ) {
	echo "FAILED:\n  - " . implode( "\n  - ", $t['fails'] ) . "\n";
	exit( 1 );
}
