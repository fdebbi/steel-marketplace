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

$t = $GLOBALS['smw_t'];
printf( "\n%d passed, %d failed\n", $t['pass'], $t['fail'] );
if ( $t['fail'] ) {
	echo "FAILED:\n  - " . implode( "\n  - ", $t['fails'] ) . "\n";
	exit( 1 );
}
