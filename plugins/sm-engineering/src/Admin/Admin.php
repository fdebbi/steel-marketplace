<?php
/**
 * wp-admin: partner engineers (applications and decisions), engineering
 * missions (quote, assignment, checking, delivery, fees) and settings.
 *
 * @package SM\Engineering
 */

namespace SM\Engineering\Admin;

use SM\Core\Accounts\Companies;
use SM\Core\Accounts\Wilayas;
use SM\Core\Notifications\Notifications as Notify;
use SM\Core\Verification\Verifications;
use SM\Engineering\Apply;
use SM\Engineering\Engineers;
use SM\Engineering\Files;
use SM\Engineering\Messages;
use SM\Engineering\Missions;
use SM\Engineering\Notifications;
use SM\Engineering\Payments;
use SM\Engineering\Changes;
use SM\Engineering\Lists;
use SM\Engineering\ProjectTypes;
use SM\Engineering\Services;
use SM\Engineering\Settings;

defined( 'ABSPATH' ) || exit;

final class Admin {

	const ENGINEERS = 'sm-engineers';
	const MISSIONS  = 'sm-missions';
	const SETTINGS  = 'sm-engineering-settings';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 25 );
		foreach ( array( 'engineer_decide', 'engineer_change', 'mission', 'mission_message', 'mission_file', 'settings' ) as $a ) {
			add_action( 'admin_post_sme_' . $a, array( __CLASS__, 'handle_' . $a ) );
		}
	}

	public static function menu() {
		$pending = Engineers::count( 'pending' );
		$todo    = Missions::counts();
		$todo    = $todo['submitted'] + $todo['accepted'] + $todo['review'];
		$badge   = static fn( $n ) => $n ? ' <span class="awaiting-mod">' . (int) $n . '</span>' : '';
		add_submenu_page( 'sm-core', __( 'Engineering missions', 'sm-engineering' ), __( 'Engineering missions', 'sm-engineering' ) . $badge( $todo ), 'sm_moderate', self::MISSIONS, array( __CLASS__, 'page_missions' ) );
		add_submenu_page( 'sm-core', __( 'Partner engineers', 'sm-engineering' ), __( 'Partner engineers', 'sm-engineering' ) . $badge( $pending ), 'sm_moderate', self::ENGINEERS, array( __CLASS__, 'page_engineers' ) );
		add_submenu_page( 'sm-core', __( 'Engineering settings', 'sm-engineering' ), __( 'Engineering settings', 'sm-engineering' ), 'sm_manage_settings', self::SETTINGS, array( __CLASS__, 'page_settings' ) );
	}

	/* ------------------------------------------------------------------ */

	private static function notice() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['sme_error'] ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['sme_error'] ) ) ) . '</p></div>';
		} elseif ( isset( $_GET['done'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Saved.', 'sm-engineering' ) . '</p></div>';
		}
		// phpcs:enable
	}

	private static function back( $page, array $args = array(), $error = '' ) {
		$args = array_merge( array( 'page' => $page ), $args );
		$args[ $error ? 'sme_error' : 'done' ] = $error ? rawurlencode( $error ) : 1;
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	private static function guard( $action, $cap = 'sm_moderate' ) {
		check_admin_referer( $action );
		if ( ! current_user_can( $cap ) ) {
			wp_die( '', 403 );
		}
	}

	private static function money( $v ) {
		return number_format_i18n( (float) $v, 0 ) . ' DA';
	}

	private static function pill( $text, $tone = '' ) {
		return '<span class="sm-pill ' . esc_attr( $tone ) . '">' . esc_html( $text ) . '</span>';
	}

	private static function date( $d ) {
		return $d ? esc_html( wp_date( get_option( 'date_format' ) . ' H:i', strtotime( $d . ' UTC' ) ) ) : '—';
	}

	private static function form_open( $action, $extra = '' ) {
		echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"' . $extra . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
		wp_nonce_field( $action );
		echo '<input type="hidden" name="action" value="' . esc_attr( $action ) . '">';
	}

	private static function styles() {
		echo '<style>.sme-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:16px;margin-top:16px}.sme-box{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px}.sme-box h2{margin-top:0;font-size:15px}.sme-box dl{display:grid;grid-template-columns:max-content 1fr;gap:4px 12px;margin:0}.sme-box dt{color:#646970}.sme-box dd{margin:0}.sme-thread{max-height:360px;overflow:auto;display:grid;gap:8px;margin-bottom:10px}.sme-msg{padding:8px 10px;border-radius:8px;background:#f6f7f7}.sme-msg.platform{background:#e7f5ec}.sme-msg small{display:block;color:#646970}.sme-files li{display:flex;gap:8px;align-items:center}.sme-photo{width:56px;height:56px;border-radius:50%;object-fit:cover;background:#f0f0f1;display:inline-grid;place-items:center;font-weight:700}.sme-margin{font-size:20px;font-weight:700}.sme-warn{color:#b32d2e}</style>';
	}

	/* ---- Engineers ---------------------------------------------------- */

	private static function engineer_status_labels() {
		return array(
			'pending'   => array( __( 'To review', 'sm-engineering' ), 'warn' ),
			'approved'  => array( __( 'Approved', 'sm-engineering' ), 'ok' ),
			'rejected'  => array( __( 'Refused', 'sm-engineering' ), 'bad' ),
			'suspended' => array( __( 'Suspended', 'sm-engineering' ), 'bad' ),
		);
	}

	private static function photo( $company, $size = 56 ) {
		$url = $company ? Companies::logo_url( $company, 'thumbnail' ) : '';
		if ( $url ) {
			return '<img class="sme-photo" width="' . (int) $size . '" height="' . (int) $size . '" src="' . esc_url( $url ) . '" alt="">';
		}
		return '<span class="sme-photo">' . esc_html( $company ? mb_strtoupper( mb_substr( $company->name, 0, 1 ) ) : '?' ) . '</span>';
	}

	public static function page_engineers() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		echo '<div class="wrap sm-admin">';
		self::styles();
		if ( $id ) {
			self::engineer_detail( $id );
			echo '</div>';
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : ( Engineers::count( 'pending' ) ? 'pending' : 'approved' );
		echo '<h1>' . esc_html__( 'Partner engineers', 'sm-engineering' ) . '</h1>';
		self::notice();
		echo '<p>' . esc_html__( 'Engineers apply on the page “Become a partner engineer”. Check the diploma and the documents, then approve or refuse. Approved engineers appear on the public list, without their contact details, and can receive missions.', 'sm-engineering' ) . '</p>';
		$links = array();
		foreach ( self::engineer_status_labels() as $k => $l ) {
			$links[] = sprintf( '<li><a href="%s"%s>%s <span class="count">(%d)</span></a></li>', esc_url( admin_url( 'admin.php?page=' . self::ENGINEERS . '&status=' . $k ) ), $k === $status ? ' class="current"' : '', esc_html( $l[0] ), Engineers::count( $k ) );
		}
		echo '<ul class="subsubsub">' . implode( ' | ', $links ) . '</ul><br class="clear">'; // phpcs:ignore WordPress.Security.EscapeOutput
		$q = Engineers::query( array( 'status' => isset( self::engineer_status_labels()[ $status ] ) ? $status : 'pending', 'per_page' => 100 ) );
		if ( ! $q['items'] ) {
			echo '<p>' . esc_html__( 'Nobody here.', 'sm-engineering' ) . '</p></div>';
			return;
		}
		$types = Settings::types( true );
		echo '<table class="widefat striped"><thead><tr><th></th><th>' . esc_html__( 'Engineer', 'sm-engineering' ) . '</th><th>' . esc_html__( 'Services offered', 'sm-engineering' ) . '</th><th>' . esc_html__( 'Experience', 'sm-engineering' ) . '</th><th>' . esc_html__( 'Wilaya', 'sm-engineering' ) . '</th><th>' . esc_html__( 'Missions', 'sm-engineering' ) . '</th><th>' . esc_html__( 'Applied', 'sm-engineering' ) . '</th></tr></thead><tbody>';
		foreach ( $q['items'] as $e ) {
			$c   = Companies::get( $e->company_id );
			$url = admin_url( 'admin.php?page=' . self::ENGINEERS . '&id=' . $e->company_id );
			echo '<tr><td>' . self::photo( $c, 40 ) . '</td><td><a href="' . esc_url( $url ) . '"><strong>' . esc_html( $c ? $c->name : '#' . $e->company_id ) . '</strong></a><br>' . esc_html( $e->title ) . ( Changes::pending( $e ) ? '<br>' . self::pill( __( 'Change to approve', 'sm-engineering' ), 'warn' ) : '' ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<td>' . esc_html( implode( ', ', array_map( static fn( $s ) => $types[ $s ]['fr'] ?? $s, $e->specialties ) ) ) . '</td>';
			/* translators: %d: years. */
			echo '<td>' . esc_html( sprintf( _n( '%d year', '%d years', $e->experience, 'sm-engineering' ), $e->experience ) ) . '</td><td>' . esc_html( $c ? Wilayas::name( $c->wilaya ) : '' ) . '</td><td>' . (int) Engineers::missions_done( $e->company_id ) . '</td><td>' . self::date( $e->applied_at ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</tbody></table></div>';
	}

	private static function engineer_detail( $id ) {
		$e = Engineers::get( $id );
		$c = Companies::get( $id );
		if ( ! $e || ! $c ) {
			echo '<h1>' . esc_html__( 'Engineer not found', 'sm-engineering' ) . '</h1>';
			return;
		}
		$u      = get_userdata( $e->user_id );
		$labels = self::engineer_status_labels();
		$types  = Settings::types( true );
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=' . self::ENGINEERS ) ) . '">← ' . esc_html__( 'Partner engineers', 'sm-engineering' ) . '</a></p>';
		echo '<h1 style="display:flex;gap:12px;align-items:center">' . self::photo( $c ) . ' ' . esc_html( $c->name ) . ' ' . self::pill( $labels[ $e->status ][0] ?? $e->status, $labels[ $e->status ][1] ?? '' ) . '</h1>'; // phpcs:ignore WordPress.Security.EscapeOutput
		self::notice();
		echo '<div class="sme-grid"><div class="sme-box"><h2>' . esc_html__( 'Profile', 'sm-engineering' ) . '</h2><dl>';
		// Applications sent before 0.4.0 keep their school and year.
		$rows = array(
			__( 'Partner', 'sm-engineering' )     => 'bet' === ( $e->partner_type ?? '' ) ? __( 'Engineering office (BET)', 'sm-engineering' ) . ( $e->bet_name ? ' — ' . $e->bet_name : '' ) : __( 'Independent engineer', 'sm-engineering' ),
			__( 'Professional title', 'sm-engineering' ) => '' !== (string) ( $e->degree ?? '' ) ? Lists::label( 'titles', $e->degree, $e->degree_other ) : $e->diploma,
			__( 'Speciality', 'sm-engineering' )  => '' !== (string) ( $e->speciality ?? '' ) ? Lists::label( 'specialities', $e->speciality, $e->speciality_other ) : $e->title,
			__( 'School', 'sm-engineering' )      => $e->school ? $e->school . ( $e->grad_year ? ' (' . $e->grad_year . ')' : '' ) : '',
			/* translators: %d: years. */
			__( 'Experience', 'sm-engineering' )  => sprintf( _n( '%d year', '%d years', $e->experience, 'sm-engineering' ), $e->experience ),
			__( 'Services offered', 'sm-engineering' ) => implode( ', ', array_map( static fn( $s ) => $types[ $s ]['fr'] ?? $s, $e->specialties ) ),
			__( 'Wilaya', 'sm-engineering' )      => $c->wilaya . ' ' . Wilayas::name( $c->wilaya ),
			__( 'Available', 'sm-engineering' )   => $e->available ? __( 'Yes', 'sm-engineering' ) : __( 'No', 'sm-engineering' ),
			__( 'Applied', 'sm-engineering' )     => wp_strip_all_tags( self::date( $e->applied_at ) ),
		);
		foreach ( array_filter( $rows, static fn( $x ) => '' !== (string) $x ) as $k => $v ) {
			echo '<dt>' . esc_html( $k ) . '</dt><dd>' . esc_html( $v ) . '</dd>';
		}
		echo '</dl><p>' . nl2br( esc_html( (string) $e->bio ) ) . '</p></div>';

		echo '<div class="sme-box"><h2>' . esc_html__( 'Contact (platform only)', 'sm-engineering' ) . '</h2><dl>';
		echo '<dt>' . esc_html__( 'E-mail', 'sm-engineering' ) . '</dt><dd>' . ( $u ? '<a href="mailto:' . esc_attr( $u->user_email ) . '">' . esc_html( $u->user_email ) . '</a>' : '' ) . '</dd>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<dt>' . esc_html__( 'Phone', 'sm-engineering' ) . '</dt><dd>' . esc_html( (string) $c->phone ) . '</dd>';
		echo '<dt>' . esc_html__( 'Verification', 'sm-engineering' ) . '</dt><dd>' . (int) $c->verification_level . '</dd></dl>';
		$v = $e->verification_id ? Verifications::get( $e->verification_id ) : null;
		echo '<h2 style="margin-top:16px">' . esc_html__( 'Documents', 'sm-engineering' ) . '</h2>';
		if ( $v && $v->documents ) {
			echo '<ul>';
			foreach ( $v->documents as $key => $doc ) {
				echo '<li><a target="_blank" rel="noopener" href="' . esc_url( Verifications::document_url( $v->id, $key ) ) . '">' . esc_html( Apply::document_label( $key ) ) . '</a> — ' . esc_html( $doc['original'] ) . '</li>';
			}
			echo '</ul>';
		} else {
			echo '<p>' . esc_html__( 'No documents.', 'sm-engineering' ) . '</p>';
		}
		echo '</div>';

		$diff = Changes::diff( $e );
		if ( $diff ) {
			echo '<div class="sme-box" style="grid-column:1/-1;border-color:#dba617"><h2>' . esc_html__( 'Change of profile to approve', 'sm-engineering' ) . '</h2>';
			echo '<p>' . esc_html__( 'The approved profile stays in use until you decide. Approving replaces it with the new values.', 'sm-engineering' ) . '</p>';
			echo '<table class="widefat striped"><thead><tr><th style="width:22%">' . esc_html__( 'Field', 'sm-engineering' ) . '</th><th>' . esc_html__( 'Today', 'sm-engineering' ) . '</th><th>' . esc_html__( 'Asked', 'sm-engineering' ) . '</th></tr></thead><tbody>';
			foreach ( $diff as $row ) {
				$after = ! empty( $row['photo'] ) ? '<img src="' . esc_url( $row['photo'] ) . '" alt="" width="64" height="64" style="border-radius:8px;object-fit:cover">' : '<strong>' . nl2br( esc_html( $row['after'] ) ) . '</strong>';
				$before = 'photo' === $row['key'] ? self::photo( $c, 64 ) : nl2br( esc_html( $row['before'] ) );
				echo '<tr><td>' . esc_html( $row['label'] ) . '</td><td>' . $before . '</td><td>' . $after . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above.
			}
			echo '</tbody></table>';
			self::form_open( 'sme_engineer_change' );
			echo '<input type="hidden" name="company_id" value="' . (int) $id . '">';
			echo '<p><label>' . esc_html__( 'Reason (required to refuse; the engineer reads it)', 'sm-engineering' ) . '<br><textarea name="reason" rows="2" class="large-text"></textarea></label></p>';
			echo '<p><button class="button button-primary" name="decision" value="approve">' . esc_html__( 'Approve the change', 'sm-engineering' ) . '</button> <button class="button" name="decision" value="refuse">' . esc_html__( 'Refuse the change', 'sm-engineering' ) . '</button></p></form></div>';
		}
		echo '<div class="sme-box"><h2>' . esc_html__( 'Decision', 'sm-engineering' ) . '</h2>';
		if ( $e->reject_reason ) {
			echo '<p class="sme-warn">' . esc_html( $e->reject_reason ) . '</p>';
		}
		self::form_open( 'sme_engineer_decide' );
		echo '<input type="hidden" name="company_id" value="' . (int) $id . '">';
		echo '<p><label>' . esc_html__( 'Reason (required to refuse or suspend; the engineer reads it)', 'sm-engineering' ) . '<br><textarea name="reason" rows="3" class="large-text"></textarea></label></p><p>';
		$buttons = array(
			'pending'   => array( 'approved' => __( 'Approve', 'sm-engineering' ), 'rejected' => __( 'Refuse', 'sm-engineering' ) ),
			'approved'  => array( 'suspended' => __( 'Suspend', 'sm-engineering' ) ),
			'suspended' => array( 'approved' => __( 'Restore', 'sm-engineering' ) ),
			'rejected'  => array( 'approved' => __( 'Approve anyway', 'sm-engineering' ) ),
		);
		foreach ( $buttons[ $e->status ] ?? array() as $to => $label ) {
			echo '<button class="button' . ( 'approved' === $to ? ' button-primary' : '' ) . '" name="status" value="' . esc_attr( $to ) . '">' . esc_html( $label ) . '</button> ';
		}
		echo '</p></form>';
		$m = Missions::query( array( 'engineer' => $id, 'per_page' => 200 ) );
		$due = 0;
		$paid = 0;
		foreach ( $m['items'] as $x ) {
			if ( 'due' === $x->fee_status ) {
				$due += $x->engineer_fee;
			} elseif ( 'paid' === $x->fee_status ) {
				$paid += $x->engineer_fee;
			}
		}
		echo '<h2 style="margin-top:16px">' . esc_html__( 'Missions', 'sm-engineering' ) . '</h2><dl><dt>' . esc_html__( 'Missions', 'sm-engineering' ) . '</dt><dd>' . (int) $m['total'] . '</dd><dt>' . esc_html__( 'Fees due', 'sm-engineering' ) . '</dt><dd>' . esc_html( self::money( $due ) ) . '</dd><dt>' . esc_html__( 'Fees paid', 'sm-engineering' ) . '</dt><dd>' . esc_html( self::money( $paid ) ) . '</dd></dl>';
		if ( $m['items'] ) {
			echo '<ul>';
			foreach ( array_slice( $m['items'], 0, 10 ) as $x ) {
				echo '<li><a href="' . esc_url( Notifications::admin_link( $x->id ) ) . '">' . esc_html( $x->ref . ' — ' . $x->title ) . '</a></li>';
			}
			echo '</ul>';
		}
		echo '</div></div>';
	}

	public static function handle_engineer_decide() {
		self::guard( 'sme_engineer_decide' );
		$id     = isset( $_POST['company_id'] ) ? absint( $_POST['company_id'] ) : 0;
		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$reason = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';
		if ( 'suspended' === $status && '' === trim( $reason ) ) {
			self::back( self::ENGINEERS, array( 'id' => $id ), __( 'Give the reason: the engineer reads it.', 'sm-engineering' ) );
		}
		$r = Engineers::decide( $id, $status, $reason );
		if ( is_wp_error( $r ) ) {
			self::back( self::ENGINEERS, array( 'id' => $id ), $r->get_error_message() );
		}
		if ( in_array( $status, array( 'approved', 'rejected', 'suspended' ), true ) ) {
			Notify::notify_company( $id, 'engineer.' . $status, array( 'reason' => $reason ) );
		}
		self::back( self::ENGINEERS, array( 'id' => $id ) );
	}

	public static function handle_engineer_change() {
		self::guard( 'sme_engineer_change' );
		$id     = isset( $_POST['company_id'] ) ? absint( $_POST['company_id'] ) : 0;
		$ok     = isset( $_POST['decision'] ) && 'approve' === sanitize_key( wp_unslash( $_POST['decision'] ) );
		$reason = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';
		$r      = Changes::decide( $id, $ok, $reason );
		self::back( self::ENGINEERS, array( 'id' => $id ), is_wp_error( $r ) ? $r->get_error_message() : '' );
	}

	/* ---- Missions ----------------------------------------------------- */

	public static function page_missions() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		echo '<div class="wrap sm-admin">';
		self::styles();
		if ( $id ) {
			self::mission_detail( $id );
			echo '</div>';
			return;
		}
		$counts = Missions::counts();
		$labels = Missions::admin_labels();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'open';
		$q      = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		// phpcs:enable
		echo '<h1>' . esc_html__( 'Engineering missions', 'sm-engineering' ) . '</h1>';
		self::notice();
		echo '<p>' . esc_html__( 'Every study or inspection request comes here. Send the quote to the client, then give the mission to a partner engineer with the fee you agree with him. Check the deliverables before they reach the client. The client and the engineer never see each other.', 'sm-engineering' ) . '</p>';
		$open  = array_sum( array_intersect_key( $counts, array_flip( Missions::OPEN ) ) );
		$base  = admin_url( 'admin.php?page=' . self::MISSIONS );
		$links = array( sprintf( '<li><a href="%s"%s>%s <span class="count">(%d)</span></a></li>', esc_url( $base ), 'open' === $status ? ' class="current"' : '', esc_html__( 'Open', 'sm-engineering' ), $open ) );
		foreach ( $labels as $k => $l ) {
			if ( $counts[ $k ] ) {
				$links[] = sprintf( '<li><a href="%s"%s>%s <span class="count">(%d)</span></a></li>', esc_url( $base . '&status=' . $k ), $k === $status ? ' class="current"' : '', esc_html( $l ), $counts[ $k ] );
			}
		}
		echo '<ul class="subsubsub">' . implode( ' | ', $links ) . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<form method="get" style="float:right"><input type="hidden" name="page" value="' . esc_attr( self::MISSIONS ) . '"><input type="search" name="q" value="' . esc_attr( $q ) . '" placeholder="ET-2026-0001"> <button class="button">' . esc_html__( 'Search', 'sm-engineering' ) . '</button></form><br class="clear">';
		$res = Missions::query( array( 'status' => 'open' === $status ? Missions::OPEN : $status, 'q' => $q, 'per_page' => 100 ) );
		if ( ! $res['items'] ) {
			echo '<p>' . esc_html__( 'No mission here.', 'sm-engineering' ) . '</p></div>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Mission', 'sm-engineering' ) . '</th><th>' . esc_html__( 'Client', 'sm-engineering' ) . '</th><th>' . esc_html__( 'Engineer', 'sm-engineering' ) . '</th><th>' . esc_html__( 'Status', 'sm-engineering' ) . '</th><th>' . esc_html__( 'Client price', 'sm-engineering' ) . '</th><th>' . esc_html__( 'Engineer fee', 'sm-engineering' ) . '</th><th>' . esc_html__( 'Margin', 'sm-engineering' ) . '</th><th>' . esc_html__( 'Updated', 'sm-engineering' ) . '</th></tr></thead><tbody>';
		$tones = array( 'submitted' => 'warn', 'accepted' => 'warn', 'review' => 'warn', 'delivered' => 'ok', 'closed' => 'ok', 'declined' => 'bad', 'quote_declined' => 'bad', 'cancelled' => 'bad' );
		foreach ( $res['items'] as $m ) {
			$cl = Companies::get( $m->client_id );
			$en = $m->engineer_id ? Companies::get( $m->engineer_id ) : null;
			echo '<tr><td><a href="' . esc_url( $base . '&id=' . $m->id ) . '"><strong>' . esc_html( $m->ref ) . '</strong></a><br>' . esc_html( $m->title ) . '<br><small>' . esc_html( Missions::services_label( $m ) ) . '</small></td>';
			echo '<td>' . esc_html( $cl ? $cl->name : '—' ) . '</td><td>' . esc_html( $en ? $en->name : '—' ) . '</td><td>' . self::pill( $labels[ $m->status ] ?? $m->status, $tones[ $m->status ] ?? '' ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<td>' . ( $m->quote_amount ? esc_html( self::money( $m->quote_amount ) ) : '—' ) . '</td><td>' . ( $m->engineer_fee && $m->engineer_id ? esc_html( self::money( $m->engineer_fee ) ) : '—' ) . '</td><td>' . ( $m->quote_amount && $m->engineer_fee && $m->engineer_id ? esc_html( self::money( $m->quote_amount - $m->engineer_fee ) ) : '—' ) . '</td><td>' . self::date( $m->updated_at ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</tbody></table></div>';
	}

	private static function files_box( $m ) {
		$kinds = array(
			'brief'       => __( 'Client documents', 'sm-engineering' ),
			'deliverable' => __( 'Deliverables of the engineer', 'sm-engineering' ),
			'platform'    => __( 'Platform documents', 'sm-engineering' ),
			'invoice'     => __( 'Engineer invoices', 'sm-engineering' ),
		);
		echo '<div class="sme-box"><h2>' . esc_html__( 'Files', 'sm-engineering' ) . '</h2>';
		foreach ( $kinds as $kind => $label ) {
			$files = Files::for_mission( $m->id, 'admin', $kind );
			if ( ! $files ) {
				continue;
			}
			echo '<h3>' . esc_html( $label ) . '</h3><ul class="sme-files">';
			foreach ( $files as $f ) {
				$who = array();
				if ( $f->to_client ) {
					$who[] = __( 'client', 'sm-engineering' );
				}
				if ( $f->to_engineer ) {
					$who[] = __( 'engineer', 'sm-engineering' );
				}
				echo '<li><a target="_blank" rel="noopener" href="' . esc_url( Files::url( $f->id ) ) . '">' . esc_html( $f->original ) . '</a> <small>' . esc_html( size_format( (int) $f->size ) ) . ' · ' . esc_html( $who ? sprintf( /* translators: %s: who sees the file. */ __( 'seen by: %s', 'sm-engineering' ), implode( ', ', $who ) ) : __( 'platform only', 'sm-engineering' ) ) . '</small></li>';
			}
			echo '</ul>';
		}
		self::form_open( 'sme_mission_file' );
		echo '<input type="hidden" name="mission_id" value="' . (int) $m->id . '"><p><input type="file" name="files[]" multiple></p><p><label><input type="checkbox" name="to_client" value="1"> ' . esc_html__( 'Visible to the client', 'sm-engineering' ) . '</label> &nbsp; <label><input type="checkbox" name="to_engineer" value="1"> ' . esc_html__( 'Visible to the engineer', 'sm-engineering' ) . '</label></p><p><button class="button">' . esc_html__( 'Add platform documents', 'sm-engineering' ) . '</button></p></form></div>';
	}

	private static function thread_box( $m, $channel ) {
		$title = 'client' === $channel ? __( 'Messages with the client', 'sm-engineering' ) : __( 'Messages with the engineer', 'sm-engineering' );
		echo '<div class="sme-box"><h2>' . esc_html( $title ) . '</h2><div class="sme-thread">';
		$rows = Messages::for_mission( $m->id, $channel );
		if ( ! $rows ) {
			echo '<p>' . esc_html__( 'No message yet.', 'sm-engineering' ) . '</p>';
		}
		foreach ( $rows as $r ) {
			$u = get_userdata( (int) $r->user_id );
			echo '<div class="sme-msg' . ( $r->from_platform ? ' platform' : '' ) . '"><small>' . esc_html( ( $r->from_platform ? __( 'Platform', 'sm-engineering' ) . ' · ' : '' ) . ( $u ? $u->display_name : '' ) ) . ' — ' . self::date( $r->created_at ) . '</small>' . nl2br( esc_html( (string) $r->body ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</div>';
		if ( 'engineer' !== $channel || $m->engineer_id ) {
			self::form_open( 'sme_mission_message' );
			echo '<input type="hidden" name="mission_id" value="' . (int) $m->id . '"><input type="hidden" name="channel" value="' . esc_attr( $channel ) . '"><textarea name="body" rows="3" class="large-text" required></textarea><p><button class="button">' . esc_html__( 'Send', 'sm-engineering' ) . '</button></p></form>';
		}
		echo '</div>';
	}

	private static function mission_detail( $id ) {
		$m = Missions::get( $id );
		if ( ! $m ) {
			echo '<h1>' . esc_html__( 'Mission not found', 'sm-engineering' ) . '</h1>';
			return;
		}
		$labels = Missions::admin_labels();
		$client = Companies::get( $m->client_id );
		$cuser  = get_userdata( $m->client_user );
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=' . self::MISSIONS ) ) . '">← ' . esc_html__( 'Engineering missions', 'sm-engineering' ) . '</a></p>';
		echo '<h1>' . esc_html( $m->ref . ' — ' . $m->title ) . ' ' . self::pill( $labels[ $m->status ] ?? $m->status ) . '</h1>'; // phpcs:ignore WordPress.Security.EscapeOutput
		self::notice();
		echo '<div class="sme-grid">';

		// Request.
		echo '<div class="sme-box"><h2>' . esc_html__( 'Request', 'sm-engineering' ) . '</h2><dl>';
		$pref = $m->preferred_id ? Companies::get( $m->preferred_id ) : null;
		$rows = array(
			__( 'Type of project', 'sm-engineering' ) => ProjectTypes::label( $m->project_type ) ?: '—',
			__( 'Wilaya', 'sm-engineering' )          => $m->wilaya . ' ' . Wilayas::name( $m->wilaya ),
			__( 'Wanted for', 'sm-engineering' )      => $m->needed_by ? wp_date( get_option( 'date_format' ), strtotime( $m->needed_by ) ) : '—',
			__( 'Sent', 'sm-engineering' )            => wp_strip_all_tags( self::date( $m->created_at ) ),
		);
		// Requests sent before 0.3.0 may have a budget or a preferred engineer.
		if ( $m->budget > 0 ) {
			$rows[ __( 'Indicative budget', 'sm-engineering' ) ] = self::money( $m->budget );
		}
		if ( $pref ) {
			$rows[ __( 'Preferred engineer', 'sm-engineering' ) ] = $pref->name;
		}
		foreach ( $rows as $k => $v ) {
			echo '<dt>' . esc_html( $k ) . '</dt><dd>' . esc_html( $v ) . '</dd>';
		}
		echo '</dl>';
		echo '<h3>' . esc_html__( 'Services and prestations wanted', 'sm-engineering' ) . '</h3>';
		foreach ( Missions::lines( $m ) as $l ) {
			echo '<p style="margin:.6em 0 .2em"><strong>' . esc_html( $l['title'] ) . '</strong></p>';
			if ( $l['labels'] ) {
				echo '<ul style="list-style:disc;margin-top:0;padding-inline-start:20px">';
				foreach ( $l['labels'] as $p ) {
					echo '<li>' . esc_html( $p ) . '</li>';
				}
				echo '</ul>';
			}
		}
		echo '<p>' . nl2br( esc_html( (string) $m->description ) ) . '</p>';
		echo '<h2>' . esc_html__( 'Client (never shown to the engineer)', 'sm-engineering' ) . '</h2><dl>';
		echo '<dt>' . esc_html__( 'Account', 'sm-engineering' ) . '</dt><dd>' . ( $client ? '<a href="' . esc_url( admin_url( 'admin.php?page=sm-companies&id=' . $client->id ) ) . '">' . esc_html( $client->name ) . '</a> · ' . esc_html( Companies::class_label( $client->account_class ) ) : '—' ) . '</dd>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<dt>' . esc_html__( 'Contact', 'sm-engineering' ) . '</dt><dd>' . esc_html( $cuser ? $cuser->display_name . ' — ' . $cuser->user_email : '' ) . '</dd><dt>' . esc_html__( 'Phone', 'sm-engineering' ) . '</dt><dd>' . esc_html( $client ? (string) $client->phone : '' ) . '</dd></dl>';
		if ( $m->reason ) {
			echo '<p class="sme-warn"><strong>' . esc_html__( 'Reason given:', 'sm-engineering' ) . '</strong> ' . esc_html( $m->reason ) . '</p>';
		}
		echo '</div>';

		// Money and steps.
		echo '<div class="sme-box"><h2>' . esc_html__( 'Quote, engineer and fee', 'sm-engineering' ) . '</h2>';
		$tot = Missions::totals( $m );
		if ( $m->quote_amount > 0 ) {
			if ( ! $tot['legacy'] ) {
				echo '<table class="widefat striped" style="margin-bottom:10px"><tbody>';
				foreach ( Missions::lines( $m ) as $l ) {
					echo '<tr><td>' . esc_html( $l['title'] ) . '</td><td style="text-align:end">' . esc_html( self::money( $l['amount'] ) ) . '</td></tr>';
				}
				if ( $tot['tva_rate'] > 0 ) {
					echo '<tr><td>' . esc_html__( 'Total before tax', 'sm-engineering' ) . '</td><td style="text-align:end">' . esc_html( self::money( $tot['ht'] ) ) . '</td></tr>';
					echo '<tr><td>' . esc_html( sprintf( /* translators: %s: rate. */ __( 'TVA %s %%', 'sm-engineering' ), rtrim( rtrim( number_format( $tot['tva_rate'], 2, '.', '' ), '0' ), '.' ) ) ) . '</td><td style="text-align:end">' . esc_html( self::money( $tot['tva'] ) ) . '</td></tr>';
				}
				echo '<tr><th>' . esc_html( $tot['tva_rate'] > 0 ? __( 'Total including tax', 'sm-engineering' ) : __( 'Total (no TVA)', 'sm-engineering' ) ) . '</th><th style="text-align:end">' . esc_html( self::money( $tot['ttc'] ) ) . '</th></tr>';
				echo '<tr><td>' . esc_html( sprintf( /* translators: %s: percentage. */ __( 'Advance %s %%', 'sm-engineering' ), rtrim( rtrim( number_format( $tot['advance_pct'], 2, '.', '' ), '0' ), '.' ) ) ) . '</td><td style="text-align:end">' . esc_html( self::money( $tot['advance'] ) ) . ' — ' . ( $tot['advance_paid'] && $tot['advance'] > 0 ? '<strong style="color:#008a20">' . esc_html__( 'received', 'sm-engineering' ) . '</strong>' : esc_html__( 'not received', 'sm-engineering' ) ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
				echo '<tr><td>' . esc_html__( 'Balance', 'sm-engineering' ) . '</td><td style="text-align:end">' . esc_html( self::money( $tot['balance'] ) ) . ' — ' . ( $tot['balance_paid'] && $tot['balance'] > 0 && $m->client_paid_at ? '<strong style="color:#008a20">' . esc_html__( 'received', 'sm-engineering' ) . '</strong>' : esc_html__( 'not received', 'sm-engineering' ) ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
				echo '</tbody></table>';
				foreach ( Payments::orders( $m ) as $item => $o ) {
					echo '<p class="description">' . esc_html( 'advance' === $item ? __( 'Order of the advance', 'sm-engineering' ) : __( 'Order of the balance', 'sm-engineering' ) ) . ' : <a href="' . esc_url( admin_url( 'admin.php?page=sm-subscriptions&order=' . (int) $o->id ) ) . '">' . esc_html( $o->ref ) . '</a> — ' . esc_html( \SM\Subscriptions\Orders::status_labels()[ $o->status ] ?? $o->status ) . '</p>';
				}
			}
			echo '<dl><dt>' . esc_html__( 'Client price', 'sm-engineering' ) . '</dt><dd>' . esc_html( self::money( $m->quote_amount ) ) . ( $m->quote_days ? ' · ' . esc_html( sprintf( /* translators: %d: days. */ _n( '%d day', '%d days', $m->quote_days, 'sm-engineering' ), $m->quote_days ) ) : '' ) . '</dd>';
			if ( $m->quote_valid_until ) {
				echo '<dt>' . esc_html__( 'Valid until', 'sm-engineering' ) . '</dt><dd>' . esc_html( wp_date( get_option( 'date_format' ), strtotime( $m->quote_valid_until ) ) ) . '</dd>';
			}
			if ( $m->engineer_id ) {
				$eng = Companies::get( $m->engineer_id );
				echo '<dt>' . esc_html__( 'Engineer', 'sm-engineering' ) . '</dt><dd><a href="' . esc_url( admin_url( 'admin.php?page=' . self::ENGINEERS . '&id=' . $m->engineer_id ) ) . '">' . esc_html( $eng ? $eng->name : '' ) . '</a></dd>';
				echo '<dt>' . esc_html__( 'Engineer fee', 'sm-engineering' ) . '</dt><dd>' . esc_html( self::money( $m->engineer_fee ) ) . ' · ' . esc_html( array( 'none' => __( 'not started', 'sm-engineering' ), 'planned' => __( 'planned', 'sm-engineering' ), 'due' => __( 'due', 'sm-engineering' ), 'paid' => __( 'paid', 'sm-engineering' ) )[ $m->fee_status ] ?? $m->fee_status ) . '</dd>';
				echo '<dt>' . esc_html__( 'Platform margin', 'sm-engineering' ) . '</dt><dd class="sme-margin">' . esc_html( self::money( ( $tot['legacy'] ? $m->quote_amount : $tot['ht'] ) - $m->engineer_fee ) ) . '</dd>';
			}
			echo '<dt>' . esc_html__( 'Client payment', 'sm-engineering' ) . '</dt><dd>' . ( $m->client_paid_at ? esc_html__( 'received', 'sm-engineering' ) . ' — ' . self::date( $m->client_paid_at ) : ( $m->advance_paid_at ? esc_html__( 'advance received', 'sm-engineering' ) . ' — ' . self::date( $m->advance_paid_at ) : esc_html__( 'not recorded', 'sm-engineering' ) ) ) . '</dd></dl>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		$act = static function ( $do, $label, $primary = false, $confirm = '' ) use ( $m ) {
			echo '<button class="button' . ( $primary ? ' button-primary' : '' ) . '" name="do" value="' . esc_attr( $do ) . '"' . ( $confirm ? ' onclick="return confirm(\'' . esc_js( $confirm ) . '\')"' : '' ) . '>' . esc_html( $label ) . '</button> ';
		};
		if ( in_array( $m->status, array( 'submitted', 'quoted' ), true ) ) {
			echo '<h3>' . esc_html( 'quoted' === $m->status ? __( 'Change the quote', 'sm-engineering' ) : __( 'Send the quote to the client', 'sm-engineering' ) ) . '</h3>';
			self::form_open( 'sme_mission', ' class="sme-quote-form"' );
			$rate = $tot['legacy'] || 'submitted' === $m->status ? Settings::tva_rate() : $tot['tva_rate'];
			$pct  = 'quoted' === $m->status && ! $tot['legacy'] ? $tot['advance_pct'] : (float) Settings::get( 'advance_pct' );
			echo '<input type="hidden" name="mission_id" value="' . (int) $m->id . '"><input type="hidden" name="tva_rate" value="' . esc_attr( (string) $rate ) . '">';
			echo '<table class="widefat striped" style="margin:8px 0"><thead><tr><th>' . esc_html__( 'Service', 'sm-engineering' ) . '</th><th style="width:170px">' . esc_html( $rate > 0 ? __( 'Price before tax (DA)', 'sm-engineering' ) : __( 'Price (DA)', 'sm-engineering' ) ) . '</th></tr></thead><tbody>';
			foreach ( Missions::lines( $m ) as $i => $l ) {
				$val = $l['amount'] > 0 && ! $tot['legacy'] ? (string) (int) $l['amount'] : '';
				echo '<tr><td><strong>' . esc_html( $l['title'] ) . '</strong>' . ( $l['labels'] ? '<br><small>' . esc_html( implode( ' · ', $l['labels'] ) ) . '</small>' : '' ) . '</td><td><input type="number" min="1" step="1" name="lines[' . (int) $i . ']" value="' . esc_attr( $val ) . '" class="sme-line" required style="width:150px"></td></tr>';
			}
			echo '</tbody></table>';
			echo '<p><label>' . esc_html__( 'Advance (%)', 'sm-engineering' ) . '<br><input type="number" min="0" max="100" step="1" name="advance_pct" class="sme-pct" value="' . esc_attr( rtrim( rtrim( number_format( $pct, 2, '.', '' ), '0' ), '.' ) ) . '" style="width:90px"></label> <label>' . esc_html__( 'Time (days)', 'sm-engineering' ) . '<br><input type="number" min="0" step="1" name="days" value="' . esc_attr( $m->quote_days ? (string) $m->quote_days : '' ) . '" style="width:90px"></label></p>';
			echo '<p class="sme-sum" data-rate="' . esc_attr( (string) $rate ) . '" data-l-ht="' . esc_attr__( 'Total before tax', 'sm-engineering' ) . '" data-l-tva="' . esc_attr( sprintf( /* translators: %s: rate. */ __( 'TVA %s %%', 'sm-engineering' ), rtrim( rtrim( number_format( $rate, 2, '.', '' ), '0' ), '.' ) ) ) . '" data-l-ttc="' . esc_attr( $rate > 0 ? __( 'Total including tax', 'sm-engineering' ) : __( 'Total (no TVA)', 'sm-engineering' ) ) . '" data-l-adv="' . esc_attr__( 'Advance', 'sm-engineering' ) . '" data-l-bal="' . esc_attr__( 'Balance', 'sm-engineering' ) . '" style="font-size:14px;background:#f6f7f7;padding:8px 10px;border-radius:4px"></p>';
			/* translators: %d: number of days. */
			echo '<p class="description">' . esc_html( sprintf( __( 'The quote is valid %d days; the client is reminded the day before it ends. The TVA rate comes from the payment settings.', 'sm-engineering' ), (int) Settings::get( 'quote_valid_days' ) ) ) . '</p>';
			?>
			<script>
			( function () {
				var f = document.querySelector( '.sme-quote-form' ), out = f && f.querySelector( '.sme-sum' );
				if ( ! out ) { return; }
				var rate = parseFloat( out.dataset.rate ) || 0, fmt = function ( v ) { return Math.round( v ).toLocaleString( 'fr-FR' ) + ' DA'; };
				function sum() {
					var ht = 0;
					f.querySelectorAll( '.sme-line' ).forEach( function ( i ) { ht += parseFloat( i.value ) || 0; } );
					var ttc = Math.round( ht * ( 1 + rate / 100 ) ), pct = Math.min( 100, Math.max( 0, parseFloat( f.querySelector( '.sme-pct' ).value ) || 0 ) ), adv = Math.round( ttc * pct / 100 );
					var parts = [];
					if ( rate > 0 ) { parts.push( out.dataset.lHt + ' : ' + fmt( ht ), out.dataset.lTva + ' : ' + fmt( ttc - ht ) ); }
					parts.push( '<strong>' + out.dataset.lTtc + ' : ' + fmt( ttc ) + '</strong>', out.dataset.lAdv + ' (' + pct + ' %) : <strong>' + fmt( adv ) + '</strong>', out.dataset.lBal + ' : ' + fmt( ttc - adv ) );
					out.innerHTML = parts.join( ' · ' );
				}
				f.addEventListener( 'input', sum );
				sum();
			} )();
			</script>
			<?php
			echo '<p><label>' . esc_html__( 'Message to the client (what is included, conditions)', 'sm-engineering' ) . '<br><textarea name="message" rows="4" class="large-text">' . esc_textarea( (string) $m->quote_message ) . '</textarea></label></p>';
			echo '<p><label>' . esc_html__( 'Reason, if you decline the request', 'sm-engineering' ) . '<br><textarea name="reason" rows="2" class="large-text"></textarea></label></p><p>';
			$act( 'quote', __( 'Send the quote', 'sm-engineering' ), true );
			$act( 'decline', __( 'Decline the request', 'sm-engineering' ), false, __( 'Decline this request? The client is told.', 'sm-engineering' ) );
			echo '</p></form>';
		}
		if ( 'accepted' === $m->status && $tot['advance_due'] ) {
			echo '<h3>' . esc_html__( 'Waiting for the advance', 'sm-engineering' ) . '</h3><p class="sme-warn">' . esc_html__( 'The mission can be given to an engineer once the advance is received (order checked in SM → Orders, or recorded below).', 'sm-engineering' ) . '</p>';
		} elseif ( in_array( $m->status, array( 'accepted', 'assigned' ), true ) ) {
			echo '<h3>' . esc_html( 'assigned' === $m->status ? __( 'Give the mission to another engineer', 'sm-engineering' ) : __( 'Give the mission to an engineer', 'sm-engineering' ) ) . '</h3>';
			$list = Engineers::query( array( 'status' => 'approved', 'per_page' => 200 ) )['items'];
			usort(
				$list,
				static function ( $a, $b ) use ( $m ) {
					$codes = Missions::codes( $m );
					$sa    = ( $a->company_id === $m->preferred_id ? 40 : 0 ) + 2 * count( array_intersect( $codes, $a->specialties ) ) + $a->available;
					$sb    = ( $b->company_id === $m->preferred_id ? 40 : 0 ) + 2 * count( array_intersect( $codes, $b->specialties ) ) + $b->available;
					return $sb <=> $sa;
				}
			);
			if ( ! $list ) {
				echo '<p class="sme-warn">' . esc_html__( 'No approved engineer yet.', 'sm-engineering' ) . '</p>';
			} else {
				self::form_open( 'sme_mission' );
				echo '<input type="hidden" name="mission_id" value="' . (int) $m->id . '"><p><select name="engineer_id">';
				foreach ( $list as $e ) {
					$c    = Companies::get( $e->company_id );
					$tags = array();
					if ( $e->company_id === $m->preferred_id ) {
						$tags[] = __( 'chosen by the client', 'sm-engineering' );
					}
					if ( array_intersect( Missions::codes( $m ), $e->specialties ) ) {
						$tags[] = __( 'service', 'sm-engineering' );
					}
					if ( ! $e->available ) {
						$tags[] = __( 'not available', 'sm-engineering' );
					}
					echo '<option value="' . (int) $e->company_id . '"' . selected( $m->engineer_id ?: $m->preferred_id, $e->company_id, false ) . '>' . esc_html( ( $c ? $c->name : '' ) . ' — ' . Wilayas::name( $c ? $c->wilaya : '' ) . ( $tags ? ' (' . implode( ', ', $tags ) . ')' : '' ) ) . '</option>';
				}
				echo '</select></p><p><label>' . esc_html__( 'Engineer fee (DA)', 'sm-engineering' ) . '<br><input type="number" min="1" step="1" name="fee" value="' . esc_attr( $m->engineer_fee ? (string) (int) $m->engineer_fee : '' ) . '"></label> <label>' . esc_html__( 'Time (days)', 'sm-engineering' ) . '<br><input type="number" min="0" step="1" name="days" value="' . esc_attr( $m->engineer_days ? (string) $m->engineer_days : (string) $m->quote_days ) . '"></label></p>';
				echo '<p><label>' . esc_html__( 'Instructions for the engineer (he does not see the client’s name nor price)', 'sm-engineering' ) . '<br><textarea name="note" rows="3" class="large-text">' . esc_textarea( (string) $m->engineer_note ) . '</textarea></label></p><p>';
				$act( 'assign', __( 'Offer the mission', 'sm-engineering' ), true );
				echo '</p></form>';
			}
		}
		if ( in_array( $m->status, array( 'assigned', 'in_progress' ), true ) ) {
			self::form_open( 'sme_mission' );
			echo '<input type="hidden" name="mission_id" value="' . (int) $m->id . '"><p>';
			$act( 'unassign', __( 'Withdraw the mission from the engineer', 'sm-engineering' ), false, __( 'Withdraw the mission? The engineer is told.', 'sm-engineering' ) );
			echo '</p></form>';
		}
		if ( 'review' === $m->status ) {
			echo '<h3>' . esc_html__( 'Check the deliverables', 'sm-engineering' ) . '</h3>';
			self::form_open( 'sme_mission' );
			echo '<input type="hidden" name="mission_id" value="' . (int) $m->id . '"><p><label>' . esc_html__( 'Changes to ask the engineer', 'sm-engineering' ) . '<br><textarea name="note" rows="3" class="large-text"></textarea></label></p><p>';
			$act( 'deliver', __( 'Validate and deliver to the client', 'sm-engineering' ), true, __( 'The client will see the deliverables. Continue?', 'sm-engineering' ) );
			$act( 'rework', __( 'Ask for changes', 'sm-engineering' ) );
			echo '</p></form>';
		}
		if ( 'delivered' === $m->status ) {
			self::form_open( 'sme_mission' );
			echo '<input type="hidden" name="mission_id" value="' . (int) $m->id . '"><p>';
			$act( 'close', __( 'Close the mission', 'sm-engineering' ) );
			echo '</p></form>';
		}
		if ( 'due' === $m->fee_status ) {
			echo '<h3>' . esc_html__( 'Pay the engineer', 'sm-engineering' ) . '</h3>';
			self::form_open( 'sme_mission' );
			echo '<input type="hidden" name="mission_id" value="' . (int) $m->id . '"><p><label>' . esc_html__( 'Payment reference (transfer, cheque…)', 'sm-engineering' ) . '<br><input type="text" name="reference" maxlength="80"></label></p><p>';
			$act( 'fee_paid', __( 'Record the payment of the fee', 'sm-engineering' ), true );
			echo '</p></form>';
		} elseif ( 'paid' === $m->fee_status ) {
			echo '<p>' . esc_html__( 'Fee paid', 'sm-engineering' ) . ' — ' . self::date( $m->fee_paid_at ) . ( $m->fee_reference ? ' · ' . esc_html( $m->fee_reference ) : '' ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		if ( in_array( $m->status, array( 'accepted', 'assigned', 'in_progress', 'review', 'delivered', 'closed' ), true ) ) {
			self::form_open( 'sme_mission' );
			echo '<input type="hidden" name="mission_id" value="' . (int) $m->id . '"><p>';
			if ( $tot['advance_due'] ) {
				$act( 'advance_paid', __( 'Record the advance received (cash, transfer…)', 'sm-engineering' ), true, __( 'Record the advance as received? The client is told and the mission can start.', 'sm-engineering' ) );
			} elseif ( $tot['balance_due'] ) {
				$act( 'client_paid', __( 'Record the balance received', 'sm-engineering' ), false, __( 'Record the balance as received? The client can download the delivered documents.', 'sm-engineering' ) );
			} elseif ( $tot['legacy'] ) {
				$act( $m->client_paid_at ? 'client_unpaid' : 'client_paid', $m->client_paid_at ? __( 'Cancel the client payment', 'sm-engineering' ) : __( 'Record the client payment', 'sm-engineering' ) );
			}
			echo '</p></form>';
		}
		echo '</div>';

		self::files_box( $m );
		self::thread_box( $m, 'client' );
		self::thread_box( $m, 'engineer' );
		echo '</div>';
	}

	public static function handle_mission() {
		self::guard( 'sme_mission' );
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- checked in guard().
		$m  = Missions::get( isset( $_POST['mission_id'] ) ? absint( $_POST['mission_id'] ) : 0 );
		$do = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
		$in = static fn( $k ) => isset( $_POST[ $k ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $k ] ) ) : '';
		// phpcs:enable
		if ( ! $m ) {
			self::back( self::MISSIONS, array(), __( 'Mission not found.', 'sm-engineering' ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked in guard().
		$lines = isset( $_POST['lines'] ) && is_array( $_POST['lines'] ) ? array_map( static fn( $v ) => (float) str_replace( array( ' ', ',' ), array( '', '.' ), sanitize_text_field( wp_unslash( $v ) ) ), wp_unslash( $_POST['lines'] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		ksort( $lines );
		$args = array(
			'lines'       => array_values( $lines ),
			'advance_pct' => $in( 'advance_pct' ),
			'tva_rate'    => '' !== $in( 'tva_rate' ) ? (float) $in( 'tva_rate' ) : Settings::tva_rate(),
			'amount'      => (float) $in( 'amount' ),
			'days'        => (int) $in( 'days' ),
			'message'     => $in( 'message' ),
			'reason'      => $in( 'reason' ),
			'engineer_id' => (int) $in( 'engineer_id' ),
			'fee'         => (float) $in( 'fee' ),
			'note'        => $in( 'note' ),
		);
		switch ( $do ) {
			case 'fee_paid':
				$r = Missions::fee_paid( $m, $in( 'reference' ) );
				break;
			case 'advance_paid':
				Payments::cancel_open( $m );
				$r = Missions::payment_received( $m, 'advance' );
				break;
			case 'client_paid':
			case 'client_unpaid':
				if ( 'client_paid' === $do ) {
					Payments::cancel_open( $m );
				}
				$r = Missions::client_paid( $m, 'client_paid' === $do );
				break;
			case 'rework':
				if ( '' === trim( $args['note'] ) ) {
					self::back( self::MISSIONS, array( 'id' => $m->id ), __( 'Write the changes you ask for.', 'sm-engineering' ) );
				}
				Messages::add( $m->id, 'engineer', $args['note'], true );
				$r = Missions::transition( $m, 'rework', $args );
				break;
			default:
				$r = Missions::transition( $m, $do, $args );
		}
		if ( is_wp_error( $r ) ) {
			self::back( self::MISSIONS, array( 'id' => $m->id ), $r->get_error_message() );
		}
		self::back( self::MISSIONS, array( 'id' => $m->id ) );
	}

	public static function handle_mission_message() {
		self::guard( 'sme_mission_message' );
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- checked in guard().
		$m       = Missions::get( isset( $_POST['mission_id'] ) ? absint( $_POST['mission_id'] ) : 0 );
		$channel = isset( $_POST['channel'] ) ? sanitize_key( wp_unslash( $_POST['channel'] ) ) : '';
		$body    = isset( $_POST['body'] ) ? sanitize_textarea_field( wp_unslash( $_POST['body'] ) ) : '';
		// phpcs:enable
		if ( ! $m || ! Messages::add( $m->id, $channel, $body, true ) ) {
			self::back( self::MISSIONS, array( 'id' => $m ? $m->id : 0 ), __( 'Write the message.', 'sm-engineering' ) );
		}
		Notifications::message( $m, $channel, true );
		self::back( self::MISSIONS, array( 'id' => $m->id ) );
	}

	public static function handle_mission_file() {
		self::guard( 'sme_mission_file' );
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- checked in guard().
		$m = Missions::get( isset( $_POST['mission_id'] ) ? absint( $_POST['mission_id'] ) : 0 );
		if ( ! $m ) {
			self::back( self::MISSIONS, array(), __( 'Mission not found.', 'sm-engineering' ) );
		}
		$r = Files::upload( $m->id, 'files', 'platform', ! empty( $_POST['to_client'] ), ! empty( $_POST['to_engineer'] ) );
		// phpcs:enable
		self::back( self::MISSIONS, array( 'id' => $m->id ), $r['errors'] ? implode( ' ', $r['errors'] ) : '' );
	}

	/* ---- Settings ----------------------------------------------------- */

	public static function page_settings() {
		$s = Settings::all();
		echo '<div class="wrap sm-admin"><h1>' . esc_html__( 'Engineering settings', 'sm-engineering' ) . '</h1>';
		self::notice();
		self::form_open( 'sme_settings' );
		echo '<table class="form-table"><tbody>';
		$checks = array(
			'applications_open' => __( 'Accept new partner engineer applications', 'sm-engineering' ),
			'public_list'       => __( 'Also show the list of partner engineers under the services', 'sm-engineering' ),
			'show_missions'     => __( 'Show the number of completed missions on the list', 'sm-engineering' ),
		);
		foreach ( $checks as $k => $label ) {
			echo '<tr><th>' . esc_html( $label ) . '</th><td><input type="checkbox" name="' . esc_attr( $k ) . '" value="1"' . checked( (int) $s[ $k ], 1, false ) . '></td></tr>';
		}
		$nums = array(
			'quote_valid_days' => __( 'Quote valid for (days)', 'sm-engineering' ),
			'advance_pct'      => __( 'Advance asked on acceptance (% of the total)', 'sm-engineering' ),
			'pay_reminder_days' => __( 'Reminder of an unpaid advance after (days)', 'sm-engineering' ),
			'requests_per_day' => __( 'Study requests a client may send per day', 'sm-engineering' ),
			'max_files'        => __( 'Files per sending', 'sm-engineering' ),
			'file_mb'          => __( 'Size of one file (MB)', 'sm-engineering' ),
		);
		foreach ( $nums as $k => $label ) {
			echo '<tr><th>' . esc_html( $label ) . '</th><td><input type="number" min="' . ( 'advance_pct' === $k ? 0 : 1 ) . '" ' . ( 'advance_pct' === $k ? 'max="100" ' : '' ) . 'name="' . esc_attr( $k ) . '" value="' . (int) $s[ $k ] . '" class="small-text"></td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '<tr><th>' . esc_html__( 'Accepted file types', 'sm-engineering' ) . '</th><td><input type="text" class="large-text" name="extensions" value="' . esc_attr( $s['extensions'] ) . '"><p class="description">' . esc_html__( 'Extensions separated by commas. Program and web files are always refused.', 'sm-engineering' ) . '</p></td></tr>';
		echo '</tbody></table>';
		self::services_editor();
		self::project_types_editor();
		self::lists_editor();
		submit_button();
		echo '</form></div>';
	}

	/** Professional titles and specialities of the partner engineer application. */
	private static function lists_editor() {
		$names = array( 'titles' => __( 'Professional titles (engineer application)', 'sm-engineering' ), 'specialities' => __( 'Specialities (engineer application)', 'sm-engineering' ) );
		foreach ( $names as $list => $title ) {
			echo '<h2>' . esc_html( $title ) . '</h2><table class="widefat striped" style="max-width:900px"><thead><tr><th>' . esc_html__( 'French', 'sm-engineering' ) . '</th><th>' . esc_html__( 'Arabic', 'sm-engineering' ) . '</th><th style="width:80px">' . esc_html__( 'Order', 'sm-engineering' ) . '</th><th style="width:60px">' . esc_html__( 'Active', 'sm-engineering' ) . '</th></tr></thead><tbody>';
			foreach ( Lists::all( $list, true ) as $code => $t ) {
				$n     = 'lists[' . $list . '][' . esc_attr( $code ) . ']';
				$fixed = 'other' === $code;
				echo '<tr><td><input class="large-text" name="' . $n . '[fr]" value="' . esc_attr( $t['fr'] ) . '"></td><td><input class="large-text" dir="rtl" name="' . $n . '[ar]" value="' . esc_attr( $t['ar'] ) . '"></td><td>' . ( $fixed ? '<em>' . esc_html__( 'last', 'sm-engineering' ) . '</em>' : '<input type="number" min="0" max="998" class="small-text" name="' . $n . '[order]" value="' . (int) $t['order'] . '">' ) . '</td><td>' . ( $fixed ? '<input type="hidden" name="' . $n . '[on]" value="1">✓' : '<input type="checkbox" name="' . $n . '[on]" value="1"' . checked( $t['off'], 0, false ) . '>' ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '<tr><td><input class="large-text" name="new_list[' . esc_attr( $list ) . '][fr]" placeholder="' . esc_attr__( 'New entry', 'sm-engineering' ) . '"></td><td><input class="large-text" dir="rtl" name="new_list[' . esc_attr( $list ) . '][ar]"></td><td></td><td></td></tr></tbody></table>';
		}
	}

	/** Types of project of the request form (FR / AR, shown, order). */
	private static function project_types_editor() {
		echo '<h2>' . esc_html__( 'Types of project', 'sm-engineering' ) . '</h2><table class="widefat striped" style="max-width:900px"><thead><tr><th>' . esc_html__( 'French', 'sm-engineering' ) . '</th><th>' . esc_html__( 'Arabic', 'sm-engineering' ) . '</th><th style="width:80px">' . esc_html__( 'Order', 'sm-engineering' ) . '</th><th style="width:60px">' . esc_html__( 'Active', 'sm-engineering' ) . '</th></tr></thead><tbody>';
		foreach ( ProjectTypes::all( true ) as $code => $t ) {
			$n = 'project_types[' . esc_attr( $code ) . ']';
			echo '<tr><td><input class="large-text" name="' . $n . '[fr]" value="' . esc_attr( $t['fr'] ) . '" aria-label="' . esc_attr__( 'Title in French', 'sm-engineering' ) . '"></td><td><input class="large-text" dir="rtl" name="' . $n . '[ar]" value="' . esc_attr( $t['ar'] ) . '" aria-label="' . esc_attr__( 'Title in Arabic', 'sm-engineering' ) . '"></td><td><input type="number" min="0" max="999" class="small-text" name="' . $n . '[order]" value="' . (int) $t['order'] . '"></td><td><input type="checkbox" name="' . $n . '[on]" value="1"' . checked( $t['off'], 0, false ) . '></td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '<tr><td><input class="large-text" name="new_project_type[fr]" placeholder="' . esc_attr__( 'New type of project', 'sm-engineering' ) . '"></td><td><input class="large-text" dir="rtl" name="new_project_type[ar]"></td><td></td><td></td></tr></tbody></table>';
	}

	/** Engineering services: title, image, prestations (FR / AR), shown, order. */
	private static function services_editor() {
		wp_enqueue_media();
		echo '<h2>' . esc_html__( 'Engineering services', 'sm-engineering' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Shown on the engineering page and offered in the study request form and to partner engineers. One prestation per line; the Arabic lines follow the French lines in the same order (an empty Arabic line shows the French text). Without an image, the page shows a drawing.', 'sm-engineering' ) . '</p>';
		echo '<table class="widefat striped sme-services" style="max-width:1200px"><thead><tr><th style="width:150px">' . esc_html__( 'Image', 'sm-engineering' ) . '</th><th>' . esc_html__( 'French', 'sm-engineering' ) . '</th><th>' . esc_html__( 'Arabic', 'sm-engineering' ) . '</th><th style="width:90px">' . esc_html__( 'Order', 'sm-engineering' ) . '<br>' . esc_html__( 'Active', 'sm-engineering' ) . '</th></tr></thead><tbody>';
		foreach ( Services::all( true ) as $code => $sv ) {
			$n   = 'services[' . esc_attr( $code ) . ']';
			$img = $sv['image'] ? wp_get_attachment_image_url( $sv['image'], 'medium' ) : '';
			echo '<tr><td><div class="sme-img" style="width:140px;height:90px;background:#f0f0f1 center/cover no-repeat;border:1px solid #dcdcde;border-radius:4px' . ( $img ? ';background-image:url(' . esc_url( $img ) . ')' : '' ) . '"></div>';
			echo '<input type="hidden" name="' . $n . '[image]" value="' . (int) $sv['image'] . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<p><button type="button" class="button sme-img-pick">' . esc_html__( 'Choose', 'sm-engineering' ) . '</button> <button type="button" class="button-link sme-img-clear">' . esc_html__( 'Remove', 'sm-engineering' ) . '</button></p><code>' . esc_html( $code ) . '</code></td>';
			$fr = implode( "\n", array_column( $sv['items'], 'fr' ) );
			$ar = implode( "\n", array_map( static fn( $i ) => $i['ar'] === $i['fr'] ? '' : $i['ar'], $sv['items'] ) );
			echo '<td><input class="large-text" name="' . $n . '[fr]" value="' . esc_attr( $sv['fr'] ) . '" aria-label="' . esc_attr__( 'Title in French', 'sm-engineering' ) . '"><textarea class="large-text" rows="5" name="' . $n . '[items_fr]" aria-label="' . esc_attr__( 'Prestations in French', 'sm-engineering' ) . '">' . esc_textarea( $fr ) . '</textarea></td>'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<td><input class="large-text" dir="rtl" name="' . $n . '[ar]" value="' . esc_attr( $sv['ar'] ) . '" aria-label="' . esc_attr__( 'Title in Arabic', 'sm-engineering' ) . '"><textarea class="large-text" dir="rtl" rows="5" name="' . $n . '[items_ar]" aria-label="' . esc_attr__( 'Prestations in Arabic', 'sm-engineering' ) . '">' . esc_textarea( $ar ) . '</textarea></td>'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<td><input type="number" min="0" max="999" class="small-text" name="' . $n . '[order]" value="' . (int) $sv['order'] . '" aria-label="' . esc_attr__( 'Order', 'sm-engineering' ) . '"><p><label><input type="checkbox" name="' . $n . '[on]" value="1"' . checked( $sv['off'], 0, false ) . '> ' . esc_html__( 'Active', 'sm-engineering' ) . '</label></p></td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '<tr><td><em>' . esc_html__( 'New service', 'sm-engineering' ) . '</em></td><td><input class="large-text" name="new_service[fr]" aria-label="' . esc_attr__( 'Title in French', 'sm-engineering' ) . '"></td><td><input class="large-text" dir="rtl" name="new_service[ar]" aria-label="' . esc_attr__( 'Title in Arabic', 'sm-engineering' ) . '"></td><td></td></tr></tbody></table>';
		?>
		<script>
		document.addEventListener( 'click', function ( e ) {
			var pick = e.target.closest( '.sme-img-pick' ), clear = e.target.closest( '.sme-img-clear' );
			if ( ! pick && ! clear ) { return; }
			var td = ( pick || clear ).closest( 'td' ), input = td.querySelector( 'input[type=hidden]' ), box = td.querySelector( '.sme-img' );
			if ( clear ) { input.value = 0; box.style.backgroundImage = ''; return; }
			var frame = wp.media( { title: <?php echo wp_json_encode( __( 'Image of the service', 'sm-engineering' ) ); ?>, library: { type: 'image' }, multiple: false } );
			frame.on( 'select', function () {
				var a = frame.state().get( 'selection' ).first().toJSON();
				input.value = a.id;
				box.style.backgroundImage = 'url(' + ( ( a.sizes && a.sizes.medium ) ? a.sizes.medium.url : a.url ) + ')';
			} );
			frame.open();
		} );
		</script>
		<?php
	}

	public static function handle_settings() {
		self::guard( 'sme_settings', 'sm_manage_settings' );
		Settings::save( map_deep( wp_unslash( $_POST ), static fn( $v ) => is_string( $v ) ? $v : $v ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- each value is sanitized in Settings::save().
		self::back( self::SETTINGS );
	}
}
