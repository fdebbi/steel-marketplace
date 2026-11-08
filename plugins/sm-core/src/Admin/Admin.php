<?php
/**
 * wp-admin screens: Steel Marketplace menu.
 *
 *  - Overview          counts and shortcuts
 *  - Companies         list (search, class, status) + detail (level, status, activities, documents)
 *  - Verifications     pending queue with approve / reject
 *  - Rights            activity x capability matrix + minimum verification levels
 *  - Wilayas           reference list (names, active)
 *  - Settings          language, verification channel, limits
 *  - Activity journal (MailAdmin)
 *
 * @package SM\Core
 */

namespace SM\Core\Admin;

use SM\Core\Accounts\Companies;
use SM\Core\Accounts\PhoneVerification;
use SM\Core\Accounts\Wilayas;
use SM\Core\Audit\AuditLog;
use SM\Core\I18n\Language;
use SM\Core\Permissions\Permissions;
use SM\Core\Verification\Verifications;

defined( 'ABSPATH' ) || exit;

final class Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_sm_admin_company', array( __CLASS__, 'save_company' ) );
		add_action( 'admin_post_sm_admin_decide', array( __CLASS__, 'save_decision' ) );
		add_action( 'admin_post_sm_admin_rights', array( __CLASS__, 'save_rights' ) );
		add_action( 'admin_post_sm_admin_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_sm_admin_wilayas', array( __CLASS__, 'save_wilayas' ) );
		add_action( 'admin_head', array( __CLASS__, 'styles' ) );
	}

	public static function menu() {
		$pending = count( Verifications::pending() );
		$badge   = $pending ? ' <span class="awaiting-mod">' . (int) $pending . '</span>' : '';
		add_menu_page( sm_brand_name(), sm_brand_name() . $badge, 'sm_moderate', 'sm-core', array( __CLASS__, 'page_overview' ), 'dashicons-building', 3 );
		add_submenu_page( 'sm-core', __( 'Overview', 'sm-core' ), __( 'Overview', 'sm-core' ), 'sm_moderate', 'sm-core', array( __CLASS__, 'page_overview' ) );
		add_submenu_page( 'sm-core', __( 'Companies', 'sm-core' ), __( 'Companies', 'sm-core' ), 'sm_moderate', 'sm-companies', array( __CLASS__, 'page_companies' ) );
		add_submenu_page( 'sm-core', __( 'Verifications', 'sm-core' ), __( 'Verifications', 'sm-core' ) . $badge, 'sm_moderate', 'sm-verifications', array( __CLASS__, 'page_verifications' ) );
		add_submenu_page( 'sm-core', __( 'Rights', 'sm-core' ), __( 'Rights', 'sm-core' ), 'sm_manage_settings', 'sm-rights', array( __CLASS__, 'page_rights' ) );
		add_submenu_page( 'sm-core', __( 'Wilayas', 'sm-core' ), __( 'Wilayas', 'sm-core' ), 'sm_manage_settings', 'sm-wilayas', array( __CLASS__, 'page_wilayas' ) );
		add_submenu_page( 'sm-core', __( 'Settings', 'sm-core' ), __( 'Settings', 'sm-core' ), 'sm_manage_settings', 'sm-settings', array( __CLASS__, 'page_settings' ) );
		add_submenu_page( 'sm-core', __( 'Activity journal', 'sm-core' ), __( 'Activity journal', 'sm-core' ), 'sm_manage_settings', 'sm-audit', array( MailAdmin::class, 'page_activity' ) );
	}

	public static function styles() {
		$screen = get_current_screen();
		if ( ! $screen || false === strpos( $screen->id, 'sm-' ) && 'toplevel_page_sm-core' !== $screen->id ) {
			return;
		}
		echo '<style>.sm-admin .sm-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin:16px 0}.sm-admin .sm-cards div{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px}.sm-admin .sm-cards strong{display:block;font-size:28px;line-height:1.2}.sm-admin .sm-matrix th,.sm-admin .sm-matrix td{text-align:center}.sm-admin .sm-matrix th:first-child,.sm-admin .sm-matrix td:first-child{text-align:start}.sm-admin .sm-lock{color:#b32d2e}.sm-admin .sm-pill{display:inline-block;padding:1px 8px;border-radius:999px;background:#f0f0f1;font-size:12px}.sm-admin .sm-pill.ok{background:#d1fae5;color:#065f46}.sm-admin .sm-pill.warn{background:#fef3c7;color:#92400e}.sm-admin .sm-pill.bad{background:#fee2e2;color:#991b1b}</style>';
	}

	private static function notice() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Saved.', 'sm-core' ) . '</p></div>';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['sm_error'] ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['sm_error'] ) ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
	}

	private static function level_pill( $level ) {
		$labels = self::level_labels();
		$class  = $level >= 2 ? 'ok' : ( 1 === (int) $level ? 'warn' : '' );
		return '<span class="sm-pill ' . esc_attr( $class ) . '">' . esc_html( $labels[ (int) $level ] ?? $level ) . '</span>';
	}

	public static function level_labels() {
		return array(
			0 => __( '0 — E-mail only', 'sm-core' ),
			1 => __( '1 — Contact verified', 'sm-core' ),
			2 => __( '2 — Verified business', 'sm-core' ),
			3 => __( '3 — Verified engineer', 'sm-core' ),
		);
	}

	private static function class_label( $class ) {
		return 'engineering' === $class ? __( 'Engineering', 'sm-core' ) : __( 'Business', 'sm-core' );
	}

	public static function page_overview() {
		global $wpdb;
		$count = static fn( $sql ) => (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB
		$c     = sm_table( 'companies' );
		echo '<div class="wrap sm-admin"><h1>' . esc_html( sm_brand_name() ) . '</h1>';
		echo '<div class="sm-cards">';
		printf( '<div><span>%s</span><strong>%d</strong></div>', esc_html__( 'Business accounts', 'sm-core' ), $count( "SELECT COUNT(*) FROM $c WHERE account_class='commercial' AND deleted_at IS NULL" ) ); // phpcs:ignore
		printf( '<div><span>%s</span><strong>%d</strong></div>', esc_html__( 'Engineering accounts', 'sm-core' ), $count( "SELECT COUNT(*) FROM $c WHERE account_class='engineering' AND deleted_at IS NULL" ) ); // phpcs:ignore
		printf( '<div><span>%s</span><strong>%d</strong></div>', esc_html__( 'Verified (level 2+)', 'sm-core' ), $count( "SELECT COUNT(*) FROM $c WHERE verification_level>=2 AND deleted_at IS NULL" ) ); // phpcs:ignore
		printf( '<div><span>%s</span><strong>%d</strong></div>', esc_html__( 'Verifications to review', 'sm-core' ), count( Verifications::pending() ) );
		printf( '<div><span>%s</span><strong>%d</strong></div>', esc_html__( 'Enquiries (30 days)', 'sm-core' ), $count( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . sm_table( 'enquiries' ) . ' WHERE created_at > %s', gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS ) ) ) ); // phpcs:ignore
		echo '</div>';
		if ( 'email' === PhoneVerification::channel() ) {
			echo '<div class="notice notice-info inline"><p>' . esc_html__( 'No SMS gateway is configured: verification codes are sent by e-mail. Make sure the site can send e-mails (Hostinger: create the mailbox and set up SMTP).', 'sm-core' ) . '</p></div>';
		}
		echo '<h2>' . esc_html__( 'Modules', 'sm-core' ) . '</h2><table class="widefat striped" style="max-width:600px"><tbody>';
		foreach ( \SM\Core\Modules::instance()->all() as $slug => $m ) {
			printf( '<tr><td><code>%s</code></td><td>%s</td><td>%s</td></tr>', esc_html( $slug ), esc_html( $m['version'] ), empty( $m['error'] ) ? '<span class="sm-pill ok">OK</span>' : '<span class="sm-pill bad">' . esc_html( $m['error'] ) . '</span>' );
		}
		echo '</tbody></table></div>';
	}

	public static function page_companies() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['company'] ) ) {
			self::page_company( absint( $_GET['company'] ) );
			return;
		}
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$class  = isset( $_GET['class'] ) ? sanitize_key( wp_unslash( $_GET['class'] ) ) : '';
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$paged  = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );
		// phpcs:enable
		$result = Companies::query( array( 'search' => $search, 'class' => $class, 'status' => $status, 'per_page' => 30, 'page' => $paged ) );
		$labels = Companies::activity_labels();

		echo '<div class="wrap sm-admin"><h1>' . esc_html__( 'Companies', 'sm-core' ) . '</h1>';
		self::notice();
		echo '<form method="get"><input type="hidden" name="page" value="sm-companies"><p class="search-box">';
		echo '<select name="class"><option value="">' . esc_html__( 'All types', 'sm-core' ) . '</option><option value="commercial"' . selected( $class, 'commercial', false ) . '>' . esc_html__( 'Business', 'sm-core' ) . '</option><option value="engineering"' . selected( $class, 'engineering', false ) . '>' . esc_html__( 'Engineering', 'sm-core' ) . '</option></select> ';
		echo '<select name="status"><option value="">' . esc_html__( 'All statuses', 'sm-core' ) . '</option><option value="active"' . selected( $status, 'active', false ) . '>' . esc_html__( 'Active', 'sm-core' ) . '</option><option value="suspended"' . selected( $status, 'suspended', false ) . '>' . esc_html__( 'Suspended', 'sm-core' ) . '</option></select> ';
		echo '<input type="search" name="s" value="' . esc_attr( $search ) . '"> <button class="button">' . esc_html__( 'Filter', 'sm-core' ) . '</button></p></form>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Company', 'sm-core' ) . '</th><th>' . esc_html__( 'Type', 'sm-core' ) . '</th><th>' . esc_html__( 'Activities', 'sm-core' ) . '</th><th>' . esc_html__( 'Wilaya', 'sm-core' ) . '</th><th>' . esc_html__( 'Verification', 'sm-core' ) . '</th><th>' . esc_html__( 'Status', 'sm-core' ) . '</th><th>' . esc_html__( 'Created', 'sm-core' ) . '</th></tr></thead><tbody>';
		if ( ! $result['items'] ) {
			echo '<tr><td colspan="7">' . esc_html__( 'No company yet.', 'sm-core' ) . '</td></tr>';
		}
		foreach ( $result['items'] as $c ) {
			$acts = implode( ', ', array_map( static fn( $a ) => $labels[ $a ] ?? $a, $c->activities ) );
			printf(
				'<tr><td><strong><a href="%s">%s</a></strong><br><code>%s</code></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
				esc_url( admin_url( 'admin.php?page=sm-companies&company=' . $c->id ) ),
				esc_html( $c->name ),
				esc_html( $c->slug ),
				esc_html( self::class_label( $c->account_class ) ),
				esc_html( $acts ),
				esc_html( $c->wilaya . ' ' . Wilayas::name( $c->wilaya, 'fr' ) ),
				self::level_pill( (int) $c->verification_level ), // phpcs:ignore WordPress.Security.EscapeOutput
				'active' === $c->status ? '<span class="sm-pill ok">' . esc_html__( 'Active', 'sm-core' ) . '</span>' : '<span class="sm-pill bad">' . esc_html__( 'Suspended', 'sm-core' ) . '</span>',
				esc_html( get_date_from_gmt( $c->created_at, 'Y-m-d' ) )
			);
		}
		echo '</tbody></table>';
		$pages = (int) ceil( $result['total'] / 30 );
		if ( $pages > 1 ) {
			echo '<p>' . paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'total' => $pages, 'current' => $paged ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</div>';
	}

	private static function page_company( $id ) {
		$c = Companies::get( $id );
		if ( ! $c ) {
			echo '<div class="wrap"><p>' . esc_html__( 'Company not found.', 'sm-core' ) . '</p></div>';
			return;
		}
		$legal  = Companies::legal_numbers( $c );
		$labels = Companies::activity_labels();
		$req    = Verifications::latest( $c->id );
		echo '<div class="wrap sm-admin"><h1>' . esc_html( $c->name ) . ' <a class="page-title-action" href="' . esc_url( sm_url( 'company', array( 'c' => $c->slug ) ) ) . '" target="_blank">' . esc_html__( 'View public page', 'sm-core' ) . '</a></h1>';
		self::notice();
		echo '<table class="form-table"><tbody>';
		$rows = array(
			__( 'Type', 'sm-core' )          => esc_html( self::class_label( $c->account_class ) ) . ' <span class="description">(' . esc_html__( 'fixed at registration', 'sm-core' ) . ')</span>',
			__( 'Wilaya', 'sm-core' )        => esc_html( $c->wilaya . ' ' . Wilayas::name( $c->wilaya, 'fr' ) ),
			__( 'Phone', 'sm-core' )         => esc_html( $c->phone ),
			__( 'E-mail', 'sm-core' )        => esc_html( $c->email ),
			'RC'                             => esc_html( $legal['rc_number'] ),
			'NIF'                            => esc_html( $legal['nif'] ),
			'NIS'                            => esc_html( $legal['nis'] ),
		);
		foreach ( $rows as $k => $v ) {
			echo '<tr><th>' . esc_html( $k ) . '</th><td>' . $v . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '<tr><th>' . esc_html__( 'Members', 'sm-core' ) . '</th><td>';
		foreach ( Companies::member_ids( $c->id ) as $uid ) {
			$u = get_userdata( $uid );
			if ( $u ) {
				echo esc_html( $u->display_name . ' — ' . $u->user_email . ' (' . Companies::member_role( $c->id, $uid ) . ')' ) . '<br>';
			}
		}
		echo '</td></tr></tbody></table>';

		echo '<h2>' . esc_html__( 'Moderation', 'sm-core' ) . '</h2><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'sm_admin_company_' . $c->id );
		echo '<input type="hidden" name="action" value="sm_admin_company"><input type="hidden" name="company" value="' . (int) $c->id . '"><table class="form-table"><tbody>';
		echo '<tr><th>' . esc_html__( 'Verification level', 'sm-core' ) . '</th><td><select name="verification_level">';
		foreach ( self::level_labels() as $lvl => $label ) {
			if ( 3 === $lvl && 'engineering' !== $c->account_class ) {
				continue;
			}
			echo '<option value="' . (int) $lvl . '"' . selected( (int) $c->verification_level, $lvl, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></td></tr>';
		echo '<tr><th>' . esc_html__( 'Status', 'sm-core' ) . '</th><td><select name="status"><option value="active"' . selected( $c->status, 'active', false ) . '>' . esc_html__( 'Active', 'sm-core' ) . '</option><option value="suspended"' . selected( $c->status, 'suspended', false ) . '>' . esc_html__( 'Suspended', 'sm-core' ) . '</option></select></td></tr>';
		echo '<tr><th>' . esc_html__( 'Activities', 'sm-core' ) . '</th><td>';
		foreach ( Companies::activities( $c->account_class ) as $a ) {
			echo '<label style="display:block"><input type="checkbox" name="activities[]" value="' . esc_attr( $a ) . '"' . checked( in_array( $a, $c->activities, true ), true, false ) . '> ' . esc_html( $labels[ $a ] ?? $a ) . '</label>';
		}
		echo '</td></tr></tbody></table>';
		submit_button( __( 'Save', 'sm-core' ) );
		echo '</form>';

		if ( $req ) {
			echo '<h2>' . esc_html__( 'Latest verification request', 'sm-core' ) . '</h2>';
			self::request_box( $req, $c );
		}
		echo '<h2>' . esc_html__( 'Recent activity', 'sm-core' ) . '</h2>';
		self::audit_table( AuditLog::query( array( 'company_id' => $c->id, 'per_page' => 15 ) ) );
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=sm-audit&c=' . (int) $c->id ) ) . '">' . esc_html__( 'Full activity of this company', 'sm-core' ) . '</a></p>';
		echo '</div>';
	}

	private static function request_box( $req, $company ) {
		$types = Verifications::document_types( $company->account_class );
		echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px 16px;max-width:760px">';
		/* translators: 1: date, 2: status. */
		echo '<p>' . esc_html( sprintf( __( 'Submitted %1$s — status: %2$s', 'sm-core' ), get_date_from_gmt( $req->created_at, 'Y-m-d H:i' ), $req->status ) ) . '</p><ul>';
		foreach ( $req->documents as $key => $doc ) {
			printf( '<li><a href="%s" target="_blank" rel="noopener">%s</a> <span class="description">(%s, %s)</span></li>', esc_url( Verifications::document_url( $req->id, $key ) ), esc_html( $types[ $key ] ?? $key ), esc_html( $doc['original'] ), esc_html( size_format( $doc['size'] ) ) );
		}
		echo '</ul>';
		if ( $req->note ) {
			echo '<p><em>' . esc_html( $req->note ) . '</em></p>';
		}
		if ( 'pending' === $req->status ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'sm_admin_decide_' . $req->id );
			echo '<input type="hidden" name="action" value="sm_admin_decide"><input type="hidden" name="request" value="' . (int) $req->id . '">';
			echo '<p><textarea name="note" rows="2" class="large-text" placeholder="' . esc_attr__( 'Reason (sent to the company if rejected)', 'sm-core' ) . '"></textarea></p>';
			echo '<p><button class="button button-primary" name="decision" value="approve">' . esc_html__( 'Approve', 'sm-core' ) . '</button> <button class="button" name="decision" value="reject">' . esc_html__( 'Reject', 'sm-core' ) . '</button></p></form>';
		} elseif ( $req->decision_note ) {
			echo '<p>' . esc_html( $req->decision_note ) . '</p>';
		}
		echo '</div>';
	}

	public static function page_verifications() {
		echo '<div class="wrap sm-admin"><h1>' . esc_html__( 'Verifications to review', 'sm-core' ) . '</h1>';
		self::notice();
		$pending = Verifications::pending();
		if ( ! $pending ) {
			echo '<p>' . esc_html__( 'Nothing to review.', 'sm-core' ) . '</p>';
		}
		foreach ( $pending as $row ) {
			$req     = Verifications::get( $row->id );
			$company = Companies::get( $req->company_id );
			if ( ! $company ) {
				continue;
			}
			echo '<h2><a href="' . esc_url( admin_url( 'admin.php?page=sm-companies&company=' . $company->id ) ) . '">' . esc_html( $company->name ) . '</a> — ' . esc_html( self::class_label( $company->account_class ) ) . '</h2>';
			self::request_box( $req, $company );
		}
		echo '</div>';
	}

	public static function page_rights() {
		$rights = Permissions::rights();
		$levels = Permissions::min_levels();
		$caps   = Permissions::capabilities();
		$labels = Companies::activity_labels();
		echo '<div class="wrap sm-admin"><h1>' . esc_html__( 'Rights by activity', 'sm-core' ) . '</h1>';
		self::notice();
		echo '<p>' . esc_html__( 'A company gets a right when at least one of its activities has it, its verification level is high enough, and its plan allows it.', 'sm-core' ) . '</p>';
		echo '<p class="sm-lock">🔒 ' . esc_html__( 'Engineering accounts can never receive trading rights (buy, listings, quotations, fabrication). This rule is enforced in code and cannot be changed here.', 'sm-core' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'sm_admin_rights' );
		echo '<input type="hidden" name="action" value="sm_admin_rights"><table class="widefat striped sm-matrix"><thead><tr><th>' . esc_html__( 'Right', 'sm-core' ) . '</th>';
		$activities = array_merge( ...array_values( Companies::activities() ) );
		foreach ( $activities as $a ) {
			echo '<th>' . esc_html( $labels[ $a ] ?? $a ) . '</th>';
		}
		echo '<th>' . esc_html__( 'Minimum level', 'sm-core' ) . '</th></tr></thead><tbody>';
		foreach ( $caps as $cap => $label ) {
			echo '<tr><td><strong>' . esc_html( $label ) . '</strong><br><code>' . esc_html( $cap ) . '</code></td>';
			foreach ( $activities as $a ) {
				$locked = 'engineer' === $a && Permissions::is_trading_capability( $cap );
				echo '<td>' . ( $locked ? '<span class="sm-lock" title="' . esc_attr__( 'Locked', 'sm-core' ) . '">—</span>' : '<input type="checkbox" name="rights[' . esc_attr( $a ) . '][]" value="' . esc_attr( $cap ) . '"' . checked( in_array( $cap, $rights[ $a ] ?? array(), true ), true, false ) . '>' ) . '</td>';
			}
			echo '<td><select name="min_levels[' . esc_attr( $cap ) . ']">';
			foreach ( array( 0, 1, 2, 3 ) as $lvl ) {
				echo '<option value="' . (int) $lvl . '"' . selected( (int) ( $levels[ $cap ] ?? 0 ), $lvl, false ) . '>' . (int) $lvl . '</option>';
			}
			echo '</select></td></tr>';
		}
		echo '</tbody></table>';
		submit_button( __( 'Save rights', 'sm-core' ) );
		echo '<p><button class="button-link" name="reset" value="1">' . esc_html__( 'Restore the default rights', 'sm-core' ) . '</button></p></form></div>';
	}

	public static function page_wilayas() {
		echo '<div class="wrap sm-admin"><h1>' . esc_html__( 'Wilayas', 'sm-core' ) . '</h1>';
		self::notice();
		echo '<p>' . esc_html__( 'Official list (69 wilayas: loi n° 26-06 of 4 April 2026 and décret présidentiel n° 26-206 of 25 May 2026, effective 1 January 2027). Correct a spelling or hide a wilaya here.', 'sm-core' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'sm_admin_wilayas' );
		echo '<input type="hidden" name="action" value="sm_admin_wilayas"><table class="widefat striped" style="max-width:760px"><thead><tr><th>' . esc_html__( 'Code', 'sm-core' ) . '</th><th>' . esc_html__( 'French', 'sm-core' ) . '</th><th>' . esc_html__( 'Arabic', 'sm-core' ) . '</th><th>' . esc_html__( 'Active', 'sm-core' ) . '</th></tr></thead><tbody>';
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		foreach ( $wpdb->get_results( 'SELECT * FROM ' . sm_table( 'wilayas' ) . ' ORDER BY code' ) as $w ) {
			$code = esc_attr( $w->code );
			echo "<tr><td><strong>{$code}</strong></td><td><input name=\"w[{$code}][fr]\" value=\"" . esc_attr( $w->name_fr ) . "\"></td><td><input dir=\"rtl\" name=\"w[{$code}][ar]\" value=\"" . esc_attr( $w->name_ar ) . "\"></td><td><input type=\"checkbox\" name=\"w[{$code}][active]\" value=\"1\"" . checked( (int) $w->active, 1, false ) . '></td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</tbody></table>';
		submit_button();
		echo '</form></div>';
	}

	public static function page_settings() {
		$s = get_option( 'sm_core_settings', array() );
		echo '<div class="wrap sm-admin"><h1>' . esc_html__( 'Settings', 'sm-core' ) . '</h1>';
		self::notice();
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'sm_admin_settings' );
		echo '<input type="hidden" name="action" value="sm_admin_settings"><table class="form-table"><tbody>';
		echo '<tr><th>' . esc_html__( 'Default language', 'sm-core' ) . '</th><td><select name="default_language">';
		foreach ( Language::languages() as $code => $lang ) {
			echo '<option value="' . esc_attr( $code ) . '"' . selected( $s['default_language'] ?? 'fr', $code, false ) . '>' . esc_html( $lang['label'] ) . '</option>';
		}
		echo '</select><p class="description">' . esc_html__( 'Pages in the default language have no prefix; the other language uses /ar/ (or its code).', 'sm-core' ) . '</p></td></tr>';
		echo '<tr><th>' . esc_html__( 'Verification code channel', 'sm-core' ) . '</th><td>' . esc_html( 'sms' === PhoneVerification::channel() ? __( 'SMS', 'sm-core' ) : __( 'E-mail (no SMS gateway configured)', 'sm-core' ) ) . '<p class="description">' . esc_html__( 'An SMS gateway is added by a connector plugin through the sm_sms_provider filter.', 'sm-core' ) . '</p></td></tr>';
		$numbers = array(
			'login_attempts'    => __( 'Failed sign-ins before a 15-minute lock', 'sm-core' ),
			'register_per_hour' => __( 'Sign-ups per hour from one connection', 'sm-core' ),
			'enquiry_per_hour'  => __( 'Enquiries per hour per account', 'sm-core' ),
		);
		foreach ( $numbers as $key => $label ) {
			echo '<tr><th>' . esc_html( $label ) . '</th><td><input type="number" min="1" max="1000" name="' . esc_attr( $key ) . '" value="' . (int) ( $s[ $key ] ?? 5 ) . '" class="small-text"></td></tr>';
		}
		echo '<tr><th>' . esc_html__( 'Uninstall', 'sm-core' ) . '</th><td><label><input type="checkbox" name="delete_on_uninstall" value="1"' . checked( ! empty( $s['delete_on_uninstall'] ), true, false ) . '> ' . esc_html__( 'Delete all platform data (companies, enquiries, logs) when the plugin is deleted', 'sm-core' ) . '</label></td></tr>';
		echo '</tbody></table>';
		submit_button();
		echo '</form></div>';
	}

	private static function audit_table( $rows ) {
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Date', 'sm-core' ) . '</th><th>' . esc_html__( 'Activity', 'sm-core' ) . '</th><th>' . esc_html__( 'User', 'sm-core' ) . '</th><th>IP</th></tr></thead><tbody>';
		if ( ! $rows ) {
			echo '<tr><td colspan="4">—</td></tr>';
		}
		foreach ( $rows as $r ) {
			$e = AuditLog::entry( $r );
			$u = $e->user_id ? get_userdata( $e->user_id ) : null;
			printf(
				'<tr><td>%s</td><td>%s <code style="font-size:11px">%s</code></td><td>%s</td><td>%s</td></tr>',
				esc_html( get_date_from_gmt( $e->created_at, 'Y-m-d H:i' ) ),
				esc_html( \SM\Core\Mail\Activity::describe( $e ) ),
				esc_html( $e->action ),
				esc_html( $u ? $u->user_email : '—' ),
				esc_html( $e->ip )
			);
		}
		echo '</tbody></table>';
	}

	/* ---- Handlers ------------------------------------------------------- */

	private static function back( $page, array $args = array() ) {
		wp_safe_redirect( add_query_arg( array_merge( array( 'page' => $page, 'updated' => 1 ), $args ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function save_company() {
		$id = isset( $_POST['company'] ) ? absint( $_POST['company'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		check_admin_referer( 'sm_admin_company_' . $id );
		if ( ! current_user_can( 'sm_moderate' ) ) {
			wp_die( '', 403 );
		}
		$before = Companies::get( $id );
		Companies::set_admin_fields(
			$id,
			array(
				'verification_level' => isset( $_POST['verification_level'] ) ? absint( $_POST['verification_level'] ) : 0,
				'status'             => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active',
			)
		);
		$activities = isset( $_POST['activities'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['activities'] ) ) : array();
		Companies::set_activities( $id, $activities );
		$after = Companies::get( $id );
		AuditLog::add( 'admin.company_updated', 'company', $id, array( 'level' => array( (int) $before->verification_level, (int) $after->verification_level ), 'status' => array( $before->status, $after->status ), 'activities' => $after->activities ), null, $id );
		self::back( 'sm-companies', array( 'company' => $id ) );
	}

	public static function save_decision() {
		$id = isset( $_POST['request'] ) ? absint( $_POST['request'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		check_admin_referer( 'sm_admin_decide_' . $id );
		$note   = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
		$result = Verifications::decide( $id, isset( $_POST['decision'] ) && 'approve' === $_POST['decision'], $note );
		if ( is_wp_error( $result ) ) {
			self::back( 'sm-verifications', array( 'updated' => null, 'sm_error' => rawurlencode( $result->get_error_message() ) ) );
		}
		self::back( 'sm-verifications' );
	}

	public static function save_rights() {
		check_admin_referer( 'sm_admin_rights' );
		if ( ! current_user_can( 'sm_manage_settings' ) ) {
			wp_die( '', 403 );
		}
		$settings = get_option( 'sm_core_settings', array() );
		if ( ! empty( $_POST['reset'] ) ) {
			$settings['rights']     = Permissions::default_rights();
			$settings['min_levels'] = Permissions::default_min_levels();
		} else {
			$settings['rights'] = Permissions::sanitize_rights( isset( $_POST['rights'] ) ? map_deep( wp_unslash( $_POST['rights'] ), 'sanitize_text_field' ) : array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$levels             = array();
			foreach ( array_keys( Permissions::capabilities() ) as $cap ) {
				$levels[ $cap ] = isset( $_POST['min_levels'][ $cap ] ) ? max( 0, min( 3, absint( $_POST['min_levels'][ $cap ] ) ) ) : 0;
			}
			$settings['min_levels'] = $levels;
		}
		update_option( 'sm_core_settings', $settings );
		AuditLog::add( 'admin.rights_updated', 'settings', 0, array( 'rights' => $settings['rights'], 'min_levels' => $settings['min_levels'] ) );
		self::back( 'sm-rights' );
	}

	public static function save_settings() {
		check_admin_referer( 'sm_admin_settings' );
		if ( ! current_user_can( 'sm_manage_settings' ) ) {
			wp_die( '', 403 );
		}
		$settings = get_option( 'sm_core_settings', array() );
		$lang     = isset( $_POST['default_language'] ) ? sanitize_key( wp_unslash( $_POST['default_language'] ) ) : 'fr';
		$settings['default_language'] = isset( Language::languages()[ $lang ] ) ? $lang : 'fr';
		foreach ( array( 'login_attempts', 'register_per_hour', 'enquiry_per_hour' ) as $key ) {
			$settings[ $key ] = isset( $_POST[ $key ] ) ? max( 1, min( 1000, absint( $_POST[ $key ] ) ) ) : 5;
		}
		$settings['delete_on_uninstall'] = ! empty( $_POST['delete_on_uninstall'] );
		update_option( 'sm_core_settings', $settings );
		AuditLog::add( 'admin.settings_updated', 'settings', 0, array( 'default_language' => $settings['default_language'] ) );
		self::back( 'sm-settings' );
	}

	public static function save_wilayas() {
		check_admin_referer( 'sm_admin_wilayas' );
		if ( ! current_user_can( 'sm_manage_settings' ) ) {
			wp_die( '', 403 );
		}
		$posted = isset( $_POST['w'] ) ? map_deep( wp_unslash( $_POST['w'] ), 'sanitize_text_field' ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		foreach ( Wilayas::all( true ) as $code => $names ) {
			if ( isset( $posted[ $code ] ) ) {
				Wilayas::update( $code, $posted[ $code ]['fr'] ?? $names['fr'], $posted[ $code ]['ar'] ?? $names['ar'], ! empty( $posted[ $code ]['active'] ) );
			}
		}
		AuditLog::add( 'admin.wilayas_updated', 'settings' );
		self::back( 'sm-wilayas' );
	}
}
