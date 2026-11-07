<?php
/**
 * wp-admin -> Steel Marketplace -> Steel catalogue.
 *
 * Tabs: families and sizes (view, correct, add, disable), settings (density,
 * commercial bar lengths, limits), change log, sources and verification.
 * Every change is checked against the geometry and written to the log.
 *
 * @package SM\WeightEngine
 */

namespace SM\WeightEngine\Admin;

use SM\WeightEngine\Catalogue;
use SM\WeightEngine\Domain\Geometry;
use SM\WeightEngine\Drawing;
use SM\WeightEngine\Install\Installer;
use SM\WeightEngine\Settings;

defined( 'ABSPATH' ) || exit;

final class Admin {

	const SLUG = 'sm-steel';
	const CAP  = 'sm_manage_settings';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
		foreach ( array( 'save_section', 'section_status', 'family_active', 'save_settings', 'reimport' ) as $a ) {
			add_action( 'admin_post_smw_' . $a, array( __CLASS__, 'handle_' . $a ) );
		}
	}

	public static function menu() {
		add_submenu_page( 'sm-core', __( 'Steel catalogue', 'sm-weight-engine' ), __( 'Steel catalogue', 'sm-weight-engine' ), 'sm_moderate', self::SLUG, array( __CLASS__, 'page' ) );
	}

	private static function url( array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::SLUG ), $args ), admin_url( 'admin.php' ) );
	}

	private static function back( array $args ) {
		wp_safe_redirect( self::url( $args ) );
		exit;
	}

	private static function guard( $action ) {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You are not allowed to change the catalogue.', 'sm-weight-engine' ), 403 );
		}
		check_admin_referer( 'smw_' . $action );
	}

	/* ------------------------------------------------------------------ */

	public static function page() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only navigation.
		$tab    = sanitize_key( $_GET['tab'] ?? 'families' );
		$family = strtoupper( sanitize_text_field( wp_unslash( $_GET['family'] ?? '' ) ) );
		$msg    = sanitize_key( $_GET['msg'] ?? '' );
		$err    = sanitize_text_field( wp_unslash( $_GET['err'] ?? '' ) );
		// phpcs:enable
		echo '<div class="wrap"><h1>' . esc_html__( 'Steel catalogue', 'sm-weight-engine' ) . '</h1>';
		printf(
			'<p>%s</p>',
			esc_html(
				sprintf(
					/* translators: 1: engine version, 2: reference data version. */
					__( 'Weight engine %1$s, reference data %2$s. Weights are theoretical: nominal dimensions × density.', 'sm-weight-engine' ),
					SM_WEIGHT_VERSION,
					SM_WEIGHT_REF_VERSION
				)
			)
		);
		if ( $msg ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( self::message( $msg ) ) . '</p></div>';
		}
		if ( $err ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $err ) . '</p></div>';
		}
		$tabs = array(
			'families' => __( 'Families and sizes', 'sm-weight-engine' ),
			'settings' => __( 'Settings', 'sm-weight-engine' ),
			'log'      => __( 'Change log', 'sm-weight-engine' ),
			'sources'  => __( 'Sources and verification', 'sm-weight-engine' ),
		);
		echo '<nav class="nav-tab-wrapper">';
		foreach ( $tabs as $k => $label ) {
			printf( '<a class="nav-tab%s" href="%s">%s</a>', $k === $tab ? ' nav-tab-active' : '', esc_url( self::url( array( 'tab' => $k ) ) ), esc_html( $label ) );
		}
		echo '</nav><div style="margin-top:16px">';
		echo '<style>.smw-drawing{color:#1d2327;max-width:260px;max-height:260px;display:block}.smw-grid{display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:24px;align-items:start}@media(max-width:900px){.smw-grid{grid-template-columns:1fr}}.smw-num{text-align:right;font-variant-numeric:tabular-nums}</style>';
		if ( 'settings' === $tab ) {
			self::tab_settings();
		} elseif ( 'log' === $tab ) {
			self::tab_log();
		} elseif ( 'sources' === $tab ) {
			self::tab_sources();
		} elseif ( $family && Catalogue::family( $family ) ) {
			self::tab_family( Catalogue::family( $family ) );
		} else {
			self::tab_families();
		}
		echo '</div></div>';
	}

	private static function message( $code ) {
		$m = array(
			'saved'    => __( 'Size saved.', 'sm-weight-engine' ),
			'status'   => __( 'Status changed.', 'sm-weight-engine' ),
			'family'   => __( 'Family updated.', 'sm-weight-engine' ),
			'settings' => __( 'Settings saved.', 'sm-weight-engine' ),
			'reimport' => __( 'Reference data checked. Sizes corrected by an administrator were kept.', 'sm-weight-engine' ),
		);
		return $m[ $code ] ?? '';
	}

	private static function tab_families() {
		$can = current_user_can( self::CAP );
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Family', 'sm-weight-engine' ) . '</th><th>' . esc_html__( 'Standard', 'sm-weight-engine' ) . '</th><th>' . esc_html__( 'Method', 'sm-weight-engine' ) . '</th><th class="smw-num">' . esc_html__( 'Sizes', 'sm-weight-engine' ) . '</th><th>' . esc_html__( 'Status', 'sm-weight-engine' ) . '</th><th></th></tr></thead><tbody>';
		foreach ( Catalogue::families( true ) as $f ) {
			echo '<tr><td><strong><a href="' . esc_url( self::url( array( 'family' => $f->code ) ) ) . '">' . esc_html( $f->code . ' — ' . $f->name_fr ) . '</a></strong><br><span lang="ar" dir="rtl">' . esc_html( $f->name_ar ) . '</span></td>';
			echo '<td>' . esc_html( $f->standard ) . '</td>';
			echo '<td>' . esc_html( 'table' === $f->method ? __( 'Reference table', 'sm-weight-engine' ) : __( 'Standard formula', 'sm-weight-engine' ) ) . '</td>';
			echo '<td class="smw-num">' . (int) $f->count . '</td>';
			echo '<td>' . esc_html( (int) $f->active ? __( 'Active', 'sm-weight-engine' ) : __( 'Hidden', 'sm-weight-engine' ) ) . '</td><td>';
			if ( $can ) {
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">';
				wp_nonce_field( 'smw_family_active' );
				echo '<input type="hidden" name="action" value="smw_family_active"><input type="hidden" name="family" value="' . esc_attr( $f->code ) . '"><input type="hidden" name="active" value="' . ( (int) $f->active ? '0' : '1' ) . '">';
				submit_button( (int) $f->active ? __( 'Hide', 'sm-weight-engine' ) : __( 'Show', 'sm-weight-engine' ), 'small', '', false );
				echo '</form>';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table>';
		if ( $can ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:16px">';
			wp_nonce_field( 'smw_reimport' );
			echo '<input type="hidden" name="action" value="smw_reimport">';
			submit_button( __( 'Check the reference data again', 'sm-weight-engine' ), 'secondary', '', false );
			echo '</form>';
		}
	}

	private static function tab_family( $f ) {
		$can     = current_user_can( self::CAP );
		$keys    = Geometry::SHAPES[ $f->shape ];
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$edit_id = isset( $_GET['edit'] ) ? sanitize_text_field( wp_unslash( $_GET['edit'] ) ) : '';
		echo '<p><a href="' . esc_url( self::url() ) . '">&larr; ' . esc_html__( 'All families', 'sm-weight-engine' ) . '</a></p>';
		echo '<h2>' . esc_html( $f->code . ' — ' . $f->name_fr ) . '</h2>';
		echo '<p><strong>' . esc_html( $f->standard ) . '</strong> — ' . esc_html( $f->source );
		if ( $f->source_url ) {
			echo ' <a href="' . esc_url( $f->source_url ) . '" target="_blank" rel="noopener">' . esc_html__( 'Source document', 'sm-weight-engine' ) . '</a>';
		}
		echo '</p>';

		if ( $can && '' !== $edit_id ) {
			self::edit_form( $f, 'new' === $edit_id ? null : Catalogue::get( (int) $edit_id ) );
		}

		if ( $can ) {
			echo '<p><a class="button" href="' . esc_url( self::url( array( 'family' => $f->code, 'edit' => 'new' ) ) ) . '">' . esc_html__( 'Add a size', 'sm-weight-engine' ) . '</a></p>';
		}
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Designation', 'sm-weight-engine' ) . '</th>';
		foreach ( $keys as $k ) {
			echo '<th class="smw-num">' . esc_html( $k ) . ' (mm)</th>';
		}
		echo '<th class="smw-num">A (cm²)</th><th class="smw-num">' . esc_html__( 'Computed kg/m', 'sm-weight-engine' ) . '</th><th class="smw-num">' . esc_html__( 'Published kg/m', 'sm-weight-engine' ) . '</th><th>' . esc_html__( 'Origin', 'sm-weight-engine' ) . '</th><th></th></tr></thead><tbody>';
		foreach ( Catalogue::sections( $f->code, true ) as $s ) {
			$off = 'active' !== $s->status;
			echo '<tr' . ( $off ? ' style="opacity:.55"' : '' ) . '><td><strong>' . esc_html( $s->designation ) . '</strong>' . ( $off ? ' — ' . esc_html__( 'disabled', 'sm-weight-engine' ) : '' ) . '</td>';
			foreach ( $keys as $k ) {
				echo '<td class="smw-num">' . esc_html( (string) ( $s->dims[ $k ] ?? '' ) ) . '</td>';
			}
			echo '<td class="smw-num">' . esc_html( number_format( $s->area_mm2 / 100, 2, '.', ' ' ) ) . '</td><td class="smw-num">' . esc_html( number_format( $s->mass_kg_m, 2, '.', ' ' ) ) . '</td><td class="smw-num">' . esc_html( null === $s->mass_published ? '—' : (string) $s->mass_published ) . '</td>';
			echo '<td>' . esc_html( 'reference' === $s->origin ? ( (int) $s->edited ? __( 'Reference, corrected', 'sm-weight-engine' ) : __( 'Reference', 'sm-weight-engine' ) ) : __( 'Added by an administrator', 'sm-weight-engine' ) ) . '</td><td>';
			if ( $can ) {
				echo '<a href="' . esc_url( self::url( array( 'family' => $f->code, 'edit' => $s->id ) ) ) . '">' . esc_html__( 'Edit', 'sm-weight-engine' ) . '</a> · ';
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">';
				wp_nonce_field( 'smw_section_status' );
				echo '<input type="hidden" name="action" value="smw_section_status"><input type="hidden" name="id" value="' . (int) $s->id . '"><input type="hidden" name="status" value="' . ( $off ? 'active' : 'disabled' ) . '">';
				echo '<button type="submit" class="button-link">' . esc_html( $off ? __( 'Enable', 'sm-weight-engine' ) : __( 'Disable', 'sm-weight-engine' ) ) . '</button></form>';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table>';
	}

	private static function edit_form( $f, $s ) {
		$keys = Geometry::SHAPES[ $f->shape ];
		echo '<div class="card" style="max-width:none;margin-bottom:20px"><div class="smw-grid"><div>';
		echo '<h3>' . esc_html( $s ? sprintf( /* translators: %s: designation. */ __( 'Edit %s', 'sm-weight-engine' ), $s->designation ) : __( 'New size', 'sm-weight-engine' ) ) . '</h3>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'smw_save_section' );
		echo '<input type="hidden" name="action" value="smw_save_section"><input type="hidden" name="family" value="' . esc_attr( $f->code ) . '"><input type="hidden" name="id" value="' . (int) ( $s->id ?? 0 ) . '">';
		echo '<table class="form-table" role="presentation"><tr><th><label for="smw-des">' . esc_html__( 'Designation', 'sm-weight-engine' ) . '</label></th><td><input id="smw-des" name="designation" class="regular-text" value="' . esc_attr( $s->designation ?? '' ) . '" required></td></tr>';
		foreach ( $keys as $k ) {
			echo '<tr><th><label for="smw-' . esc_attr( $k ) . '">' . esc_html( $k ) . ' (mm)</label></th><td><input id="smw-' . esc_attr( $k ) . '" name="dims[' . esc_attr( $k ) . ']" inputmode="decimal" value="' . esc_attr( (string) ( $s->dims[ $k ] ?? '' ) ) . '" required></td></tr>';
		}
		echo '<tr><th><label for="smw-pub">' . esc_html__( 'Published mass (kg/m)', 'sm-weight-engine' ) . '</label></th><td><input id="smw-pub" name="mass_published" inputmode="decimal" value="' . esc_attr( null === ( $s->mass_published ?? null ) ? '' : (string) $s->mass_published ) . '"><p class="description">' . esc_html__( 'Value printed in the source table. It must agree with the mass computed from the dimensions (2 %).', 'sm-weight-engine' ) . '</p></td></tr>';
		echo '<tr><th><label for="smw-note">' . esc_html__( 'Reason and source of the change', 'sm-weight-engine' ) . '</label></th><td><textarea id="smw-note" name="note" rows="3" class="large-text" required></textarea></td></tr>';
		echo '<tr><th></th><td><label><input type="checkbox" name="confirm" value="1"> ' . esc_html__( 'I confirm these values even if the masses disagree', 'sm-weight-engine' ) . '</label></td></tr></table>';
		submit_button( __( 'Save the size', 'sm-weight-engine' ) );
		echo '</form></div><div>';
		if ( $s ) {
			echo Drawing::svg( $f->shape, $s->dims, array( 'title' => $s->designation ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG built from numbers.
		}
		echo '</div></div></div>';
	}

	private static function tab_settings() {
		$s   = Settings::all();
		$dis = current_user_can( self::CAP ) ? '' : ' disabled';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'smw_save_settings' );
		echo '<input type="hidden" name="action" value="smw_save_settings"><table class="form-table" role="presentation">';
		$rows = array(
			'density'        => array( __( 'Density of steel (kg/m³)', 'sm-weight-engine' ), __( '7850 is the value used by EN 10365 and EN 10219-2 for their tables.', 'sm-weight-engine' ) ),
			'bar_lengths'    => array( __( 'Commercial bar lengths (mm)', 'sm-weight-engine' ), __( 'Separated by commas, for example 12000, 6000. The seller can also type another length.', 'sm-weight-engine' ) ),
			'default_length' => array( __( 'Default bar length (mm)', 'sm-weight-engine' ), '' ),
			'max_length'     => array( __( 'Longest length accepted (mm)', 'sm-weight-engine' ), '' ),
			'max_bars'       => array( __( 'Most bars in one calculation', 'sm-weight-engine' ), '' ),
			'max_total_kg'   => array( __( 'Largest total weight (kg)', 'sm-weight-engine' ), '' ),
			'max_dimension'  => array( __( 'Largest typed dimension (mm)', 'sm-weight-engine' ), __( 'Tubes, flats, bars.', 'sm-weight-engine' ) ),
			'max_thickness'  => array( __( 'Largest typed thickness (mm)', 'sm-weight-engine' ), '' ),
			'max_plate_area' => array( __( 'Largest plate (m²)', 'sm-weight-engine' ), '' ),
		);
		foreach ( $rows as $k => list( $label, $help ) ) {
			$v = is_array( $s[ $k ] ) ? implode( ', ', $s[ $k ] ) : $s[ $k ];
			echo '<tr><th><label for="smw-s-' . esc_attr( $k ) . '">' . esc_html( $label ) . '</label></th><td><input id="smw-s-' . esc_attr( $k ) . '" name="' . esc_attr( $k ) . '" value="' . esc_attr( (string) $v ) . '" class="regular-text"' . esc_attr( $dis ) . '>' . ( $help ? '<p class="description">' . esc_html( $help ) . '</p>' : '' ) . '</td></tr>';
		}
		echo '</table>';
		if ( ! $dis ) {
			submit_button();
		}
		echo '</form>';
	}

	private static function tab_log() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sm_steel_log ORDER BY id DESC LIMIT 200" );
		if ( ! $rows ) {
			echo '<p>' . esc_html__( 'No change yet.', 'sm-weight-engine' ) . '</p>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Date (UTC)', 'sm-weight-engine' ) . '</th><th>' . esc_html__( 'User', 'sm-weight-engine' ) . '</th><th>' . esc_html__( 'Action', 'sm-weight-engine' ) . '</th><th>' . esc_html__( 'Family', 'sm-weight-engine' ) . '</th><th>' . esc_html__( 'Details', 'sm-weight-engine' ) . '</th></tr></thead><tbody>';
		foreach ( $rows as $r ) {
			$u = $r->user_id ? get_userdata( $r->user_id ) : null;
			echo '<tr><td>' . esc_html( $r->created_at ) . '</td><td>' . esc_html( $u ? $u->display_name : '—' ) . '</td><td><code>' . esc_html( $r->action ) . '</code></td><td>' . esc_html( $r->family ) . '</td><td><code style="white-space:pre-wrap;font-size:11px">' . esc_html( (string) $r->data ) . '</code></td></tr>';
		}
		echo '</tbody></table>';
	}

	private static function tab_sources() {
		echo '<p>' . esc_html__( 'Every size of a reference table was accepted only if (1) its dimensions match an independent compilation, (2) the area recomputed from the dimensions matches the published area and (3) the mass recomputed (area × 7850 kg/m³) matches the published mass. Sizes that failed were left out. The full report is in the plugin (docs/VERIFICATION.md).', 'sm-weight-engine' ) . '</p>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Family', 'sm-weight-engine' ) . '</th><th>' . esc_html__( 'Standard', 'sm-weight-engine' ) . '</th><th>' . esc_html__( 'Source and checks', 'sm-weight-engine' ) . '</th></tr></thead><tbody>';
		foreach ( Catalogue::families( true ) as $f ) {
			echo '<tr><td>' . esc_html( $f->code ) . '</td><td>' . esc_html( $f->standard ) . '</td><td>' . esc_html( $f->source ) . ( $f->source_url ? ' <a href="' . esc_url( $f->source_url ) . '" target="_blank" rel="noopener">' . esc_html__( 'Open', 'sm-weight-engine' ) . '</a>' : '' ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	/* ---- Handlers ------------------------------------------------------ */

	public static function handle_save_section() {
		self::guard( 'save_section' );
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified in guard().
		$family = sanitize_text_field( wp_unslash( $_POST['family'] ?? '' ) );
		$id     = (int) ( $_POST['id'] ?? 0 );
		$data   = array(
			'designation'    => sanitize_text_field( wp_unslash( $_POST['designation'] ?? '' ) ),
			'dims'           => array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['dims'] ?? array() ) ),
			'mass_published' => sanitize_text_field( wp_unslash( $_POST['mass_published'] ?? '' ) ),
			'note'           => sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) ),
		);
		$confirm = ! empty( $_POST['confirm'] );
		// phpcs:enable
		if ( '' === trim( $data['note'] ) ) {
			self::back( array( 'family' => $family, 'edit' => $id ? $id : 'new', 'err' => __( 'Explain the change and give its source.', 'sm-weight-engine' ) ) );
		}
		$saved = Catalogue::save( $family, $data, $id, $confirm );
		if ( is_wp_error( $saved ) ) {
			self::back( array( 'family' => $family, 'edit' => $id ? $id : 'new', 'err' => $saved->get_error_message() ) );
		}
		self::back( array( 'family' => $family, 'msg' => 'saved' ) );
	}

	public static function handle_section_status() {
		self::guard( 'section_status' );
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$id = (int) ( $_POST['id'] ?? 0 );
		Catalogue::set_status( $id, sanitize_key( $_POST['status'] ?? '' ) );
		// phpcs:enable
		$s = Catalogue::get( $id );
		self::back( array( 'family' => $s ? $s->family : '', 'msg' => 'status' ) );
	}

	public static function handle_family_active() {
		self::guard( 'family_active' );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		Catalogue::set_family_active( sanitize_text_field( wp_unslash( $_POST['family'] ?? '' ) ), ! empty( $_POST['active'] ) );
		self::back( array( 'msg' => 'family' ) );
	}

	public static function handle_save_settings() {
		self::guard( 'save_settings' );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		Settings::save( array_map( 'sanitize_text_field', wp_unslash( $_POST ) ) );
		Catalogue::log( 0, '', 'settings.saved', Settings::all() );
		self::back( array( 'tab' => 'settings', 'msg' => 'settings' ) );
	}

	public static function handle_reimport() {
		self::guard( 'reimport' );
		delete_option( 'sm_weight_ref_version' );
		$counts = Installer::import_reference();
		Catalogue::log( 0, '', 'reference.checked', $counts );
		self::back( array( 'msg' => 'reimport' ) );
	}
}
