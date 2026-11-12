<?php
/**
 * SM → Subscriptions: orders (proofs of payment to check), subscriptions
 * (active plans, give or stop a plan by hand), plans (prices and limits) and
 * settings (invoicing identity, payment details, founders, packs, options).
 *
 * @package SM\Subscriptions
 */

namespace SM\Subscriptions\Admin;

use SM\Core\Accounts\Companies;
use SM\Subscriptions\Founders;
use SM\Subscriptions\Orders;
use SM\Subscriptions\Plans;
use SM\Subscriptions\Proofs;
use SM\Subscriptions\Settings;
use SM\Subscriptions\Subscriptions;

defined( 'ABSPATH' ) || exit;

final class Admin {

	const PAGE = 'sm-subscriptions';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 26 );
		foreach ( array( 'order', 'assign', 'cancel', 'plans', 'settings' ) as $a ) {
			add_action( 'admin_post_smsub_' . $a, array( __CLASS__, 'handle_' . $a ) );
		}
	}

	public static function menu() {
		$n     = Orders::count_status( 'review' );
		$badge = $n ? ' <span class="awaiting-mod"><span class="pending-count">' . (int) $n . '</span></span>' : '';
		add_submenu_page( 'sm-core', __( 'Subscriptions', 'sm-subscriptions' ), __( 'Subscriptions', 'sm-subscriptions' ) . $badge, 'sm_moderate', self::PAGE, array( __CLASS__, 'page' ) );
	}

	private static function url( array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::PAGE ), $args ), admin_url( 'admin.php' ) );
	}

	private static function back( array $args = array(), $error = '' ) {
		$args[ $error ? 'smsub_error' : 'done' ] = $error ? rawurlencode( $error ) : 1;
		wp_safe_redirect( self::url( $args ) );
		exit;
	}

	private static function guard( $action, $cap = 'sm_moderate' ) {
		check_admin_referer( $action );
		if ( ! current_user_can( $cap ) ) {
			wp_die( '', 403 );
		}
	}

	private static function form_open( $action ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( $action );
		echo '<input type="hidden" name="action" value="' . esc_attr( $action ) . '">';
	}

	private static function money( $v ) {
		return number_format_i18n( (float) $v, 0 ) . ' DA';
	}

	private static function date( $d ) {
		return $d ? esc_html( wp_date( get_option( 'date_format' ) . ' H:i', strtotime( $d . ' UTC' ) ) ) : '—';
	}

	private static function company_cell( $company_id ) {
		$c = Companies::get( (int) $company_id );
		return $c ? esc_html( $c->name ) . '<br><small>' . esc_html( Companies::class_label( $c->account_class ) ) . ' · #' . (int) $c->id . '</small>' : '#' . (int) $company_id;
	}

	public static function page() {
		$tab  = sanitize_key( $_GET['tab'] ?? 'orders' ); // phpcs:ignore WordPress.Security.NonceVerification
		$tabs = array(
			'orders'        => __( 'Orders and payments', 'sm-subscriptions' ),
			'subscriptions' => __( 'Plans in force', 'sm-subscriptions' ),
			'plans'         => __( 'Prices and limits', 'sm-subscriptions' ),
			'settings'      => __( 'Settings', 'sm-subscriptions' ),
		);
		echo '<div class="wrap sm-admin"><h1>' . esc_html__( 'Subscriptions', 'sm-subscriptions' ) . '</h1>';
		echo '<style>.smsub-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-top:16px}.smsub-box{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px}.smsub-box h2{margin-top:0;font-size:15px}.smsub-pill{display:inline-block;padding:2px 8px;border-radius:999px;background:#f0f0f1;font-size:12px}.smsub-pill.review{background:#fcf0c3}.smsub-pill.paid{background:#d1f2df}.smsub-pill.rejected{background:#fbd9d7}.smsub-plans td,.smsub-plans th{padding:6px 8px}.smsub-plans input{width:90px}</style>';
		if ( isset( $_GET['done'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Saved.', 'sm-subscriptions' ) . '</p></div>';
		}
		if ( ! empty( $_GET['smsub_error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-error"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['smsub_error'] ) ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification
		}
		echo '<nav class="nav-tab-wrapper">';
		foreach ( $tabs as $k => $label ) {
			if ( in_array( $k, array( 'plans', 'settings' ), true ) && ! current_user_can( 'sm_manage_settings' ) ) {
				continue;
			}
			printf( '<a class="nav-tab%s" href="%s">%s</a>', $k === $tab ? ' nav-tab-active' : '', esc_url( self::url( array( 'tab' => $k ) ) ), esc_html( $label ) );
		}
		echo '</nav>';
		$order = absint( $_GET['order'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( $order ) {
			self::order_screen( $order );
		} elseif ( 'subscriptions' === $tab ) {
			self::subscriptions_tab();
		} elseif ( 'plans' === $tab && current_user_can( 'sm_manage_settings' ) ) {
			self::plans_tab();
		} elseif ( 'settings' === $tab && current_user_can( 'sm_manage_settings' ) ) {
			self::settings_tab();
		} else {
			self::orders_tab();
		}
		echo '</div>';
	}

	/* ---- Orders ------------------------------------------------------- */

	private static function orders_tab() {
		$status = sanitize_key( $_GET['status'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
		$labels = Orders::status_labels();
		echo '<p>';
		foreach ( array_merge( array( '' => __( 'All', 'sm-subscriptions' ) ), $labels ) as $k => $l ) {
			printf( '<a href="%s" class="button%s">%s</a> ', esc_url( self::url( array( 'status' => $k ) ) ), $k === $status ? ' button-primary' : '', esc_html( $l ) );
		}
		echo '</p><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Order', 'sm-subscriptions' ) . '</th><th>' . esc_html__( 'Account', 'sm-subscriptions' ) . '</th><th>' . esc_html__( 'Item', 'sm-subscriptions' ) . '</th><th>' . esc_html__( 'Amount', 'sm-subscriptions' ) . '</th><th>' . esc_html__( 'Status', 'sm-subscriptions' ) . '</th><th>' . esc_html__( 'Date', 'sm-subscriptions' ) . '</th></tr></thead><tbody>';
		$rows = Orders::query( array( 'status' => $status, 'limit' => 200 ) );
		foreach ( $rows as $o ) {
			printf(
				'<tr><td><a href="%s"><strong>%s</strong></a></td><td>%s</td><td>%s</td><td>%s</td><td><span class="smsub-pill %s">%s</span></td><td>%s</td></tr>',
				esc_url( self::url( array( 'order' => $o->id ) ) ),
				esc_html( $o->ref ),
				self::company_cell( $o->company_id ), // phpcs:ignore WordPress.Security.EscapeOutput
				esc_html( $o->label ),
				esc_html( self::money( $o->amount_ttc ) ),
				esc_attr( $o->status ),
				esc_html( $labels[ $o->status ] ?? $o->status ),
				self::date( $o->proof_at ? $o->proof_at : $o->created_at ) // phpcs:ignore WordPress.Security.EscapeOutput
			);
		}
		if ( ! $rows ) {
			echo '<tr><td colspan="6">' . esc_html__( 'No order yet.', 'sm-subscriptions' ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	private static function order_screen( $id ) {
		$o = Orders::get( $id );
		if ( ! $o ) {
			echo '<p>' . esc_html__( 'Order not found.', 'sm-subscriptions' ) . '</p>';
			return;
		}
		$labels = Orders::status_labels();
		echo '<p><a href="' . esc_url( self::url() ) . '">&larr; ' . esc_html__( 'All orders', 'sm-subscriptions' ) . '</a></p>';
		echo '<div class="smsub-grid"><div class="smsub-box"><h2>' . esc_html( $o->ref ) . ' <span class="smsub-pill ' . esc_attr( $o->status ) . '">' . esc_html( $labels[ $o->status ] ?? $o->status ) . '</span></h2><dl>';
		$rows = array(
			__( 'Account', 'sm-subscriptions' )          => self::company_cell( $o->company_id ),
			__( 'Item', 'sm-subscriptions' )             => esc_html( $o->label ),
			__( 'Amount, tax included', 'sm-subscriptions' ) => esc_html( self::money( $o->amount_ttc ) ),
			__( 'Payment method', 'sm-subscriptions' )   => esc_html( Settings::method_labels()[ $o->method ] ?? '—' ),
			__( 'Ordered on', 'sm-subscriptions' )       => self::date( $o->created_at ),
			__( 'Proof sent on', 'sm-subscriptions' )    => self::date( $o->proof_at ),
			__( 'Note of the customer', 'sm-subscriptions' ) => nl2br( esc_html( (string) $o->payer_note ) ),
			__( 'Invoice', 'sm-subscriptions' )          => $o->invoice_no ? esc_html( $o->invoice_no ) : '—',
		);
		foreach ( $rows as $k => $v ) {
			echo '<dt><strong>' . esc_html( $k ) . '</strong></dt><dd>' . $v . '</dd>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</dl>';
		if ( Proofs::url( $o ) ) {
			echo '<p><a class="button" target="_blank" rel="noopener" href="' . esc_url( Proofs::url( $o ) ) . '">' . esc_html__( 'Open the proof of payment', 'sm-subscriptions' ) . '</a></p>';
		}
		echo '</div>';
		if ( in_array( $o->status, array( 'awaiting', 'review' ), true ) ) {
			echo '<div class="smsub-box"><h2>' . esc_html__( 'Decision', 'sm-subscriptions' ) . '</h2>';
			self::form_open( 'smsub_order' );
			echo '<input type="hidden" name="order_id" value="' . (int) $o->id . '">';
			echo '<p>' . esc_html__( 'Check that the amount reached the account of the platform before confirming. Confirming gives the plan, the credits or the option at once and issues the invoice.', 'sm-subscriptions' ) . '</p>';
			echo '<p><label>' . esc_html__( 'Payment method', 'sm-subscriptions' ) . ' <select name="method">';
			foreach ( Settings::method_labels() as $k => $l ) {
				printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $o->method, $k, false ), esc_html( $l ) );
			}
			echo '</select></label></p>';
			echo '<p><button class="button button-primary" name="do" value="approve">' . esc_html__( 'Payment received: confirm', 'sm-subscriptions' ) . '</button></p>';
			echo '<p><label>' . esc_html__( 'Reason (sent to the customer)', 'sm-subscriptions' ) . '<br><textarea name="reason" rows="3" class="large-text"></textarea></label></p>';
			echo '<p><button class="button" name="do" value="reject">' . esc_html__( 'Payment not received: refuse', 'sm-subscriptions' ) . '</button></p></form></div>';
		}
		echo '</div>';
	}

	public static function handle_order() {
		self::guard( 'smsub_order' );
		$o = Orders::get( absint( $_POST['order_id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! $o ) {
			self::back( array(), __( 'Order not found.', 'sm-subscriptions' ) );
		}
		$do = sanitize_key( $_POST['do'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( 'approve' === $do ) {
			$r = Orders::approve( $o, sanitize_key( $_POST['method'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( is_wp_error( $r ) ) {
				self::back( array( 'order' => $o->id ), $r->get_error_message() );
			}
			\SM\Core\Audit\AuditLog::add( 'subscription.order_paid', 'sub_order', (int) $o->id, array( 'ref' => $o->ref, 'amount' => (float) $o->amount_ttc ), null, (int) $o->company_id );
		} elseif ( 'reject' === $do ) {
			$reason = sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( '' === trim( $reason ) ) {
				self::back( array( 'order' => $o->id ), __( 'Give the reason of the refusal.', 'sm-subscriptions' ) );
			}
			Orders::reject( $o, $reason );
		}
		self::back( array( 'order' => $o->id ) );
	}

	/* ---- Plans in force ----------------------------------------------- */

	private static function subscriptions_tab() {
		echo '<p>' . esc_html( sprintf( /* translators: 1: founding members, 2: places left. */ __( 'Founding members: %1$d (places left: %2$d).', 'sm-subscriptions' ), Subscriptions::founders_count(), Founders::places_left() ) ) . '</p>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Account', 'sm-subscriptions' ) . '</th><th>' . esc_html__( 'Plan', 'sm-subscriptions' ) . '</th><th>' . esc_html__( 'Until', 'sm-subscriptions' ) . '</th><th>' . esc_html__( 'Note', 'sm-subscriptions' ) . '</th><th></th></tr></thead><tbody>';
		$rows = Subscriptions::active_list();
		foreach ( $rows as $s ) {
			$c = Companies::get( (int) $s->company_id );
			echo '<tr><td>' . self::company_cell( $s->company_id ) . '</td><td>' . esc_html( Plans::name( $s->plan, $c ? $c->account_class : 'company' ) ) . ( $s->founder ? ' · ' . esc_html__( 'founding member', 'sm-subscriptions' ) : '' ) . '</td><td>' . self::date( $s->ends_at ) . '</td><td>' . esc_html( $s->note ) . '</td><td>'; // phpcs:ignore WordPress.Security.EscapeOutput
			self::form_open( 'smsub_cancel' );
			echo '<input type="hidden" name="company_id" value="' . (int) $s->company_id . '"><button class="button-link-delete" onclick="return confirm(this.dataset.c)" data-c="' . esc_attr__( 'Stop this plan now? What goes beyond the free plan will be paused.', 'sm-subscriptions' ) . '">' . esc_html__( 'Stop', 'sm-subscriptions' ) . '</button></form></td></tr>';
		}
		if ( ! $rows ) {
			echo '<tr><td colspan="5">' . esc_html__( 'No paid plan in force.', 'sm-subscriptions' ) . '</td></tr>';
		}
		echo '</tbody></table><div class="smsub-box" style="margin-top:16px;max-width:640px"><h2>' . esc_html__( 'Give a plan by hand', 'sm-subscriptions' ) . '</h2><p>' . esc_html__( 'For a payment received outside an order, a partner or a gift. The months are added to the plan in force when it is the same plan.', 'sm-subscriptions' ) . '</p>';
		self::form_open( 'smsub_assign' );
		echo '<p><label>' . esc_html__( 'Account number or e-mail of a member', 'sm-subscriptions' ) . '<br><input name="who" class="regular-text" required></label></p><p><label>' . esc_html__( 'Plan', 'sm-subscriptions' ) . '<br><select name="plan">';
		foreach ( Plans::classes() as $class ) {
			foreach ( Plans::paid( $class ) as $code => $p ) {
				printf( '<option value="%s">%s — %s</option>', esc_attr( $class . ':' . $code ), esc_html( Companies::class_label( $class ) ), esc_html( Plans::name( $code, $class ) ) );
			}
		}
		echo '</select></label></p><p><label>' . esc_html__( 'Months', 'sm-subscriptions' ) . ' <input type="number" name="months" value="12" min="1" max="36"></label></p><p><label>' . esc_html__( 'Note', 'sm-subscriptions' ) . '<br><input name="note" class="regular-text"></label></p>';
		submit_button( __( 'Give the plan', 'sm-subscriptions' ) );
		echo '</form></div>';
	}

	public static function handle_assign() {
		self::guard( 'smsub_assign' );
		$who = sanitize_text_field( wp_unslash( $_POST['who'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$c   = is_numeric( $who ) ? Companies::get( (int) $who ) : ( ( $u = get_user_by( 'email', $who ) ) ? Companies::for_user( $u->ID ) : null );
		list( $class, $code ) = array_pad( explode( ':', sanitize_text_field( wp_unslash( $_POST['plan'] ?? '' ) ) ), 2, '' ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! $c || $c->account_class !== $class || ! Plans::get( $class, $code ) ) {
			self::back( array( 'tab' => 'subscriptions' ), __( 'Account not found, or this plan is not for its type of account.', 'sm-subscriptions' ) );
		}
		Subscriptions::activate( (int) $c->id, $code, max( 1, min( 36, absint( $_POST['months'] ?? 12 ) ) ), array( 'period' => 'manual', 'note' => sanitize_text_field( wp_unslash( $_POST['note'] ?? '' ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		\SM\Core\Audit\AuditLog::add( 'subscription.assigned', 'company', (int) $c->id, array( 'plan' => $code ), null, (int) $c->id );
		self::back( array( 'tab' => 'subscriptions' ) );
	}

	public static function handle_cancel() {
		self::guard( 'smsub_cancel' );
		$id = absint( $_POST['company_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		Subscriptions::cancel( $id, 'stopped by the platform' );
		\SM\Core\Audit\AuditLog::add( 'subscription.stopped', 'company', $id, array(), null, $id );
		self::back( array( 'tab' => 'subscriptions' ) );
	}

	/* ---- Prices and limits -------------------------------------------- */

	private static function plans_tab() {
		$all    = Plans::all();
		$labels = array(
			'price_month' => __( 'Price per month (DA, tax incl.)', 'sm-subscriptions' ),
			'price_year'  => __( 'Price per year (DA, tax incl.)', 'sm-subscriptions' ),
		) + Plans::limit_labels();
		$units  = array( 'weight_t' => ' t' );
		self::form_open( 'smsub_plans' );
		echo '<p>' . esc_html__( 'Empty or -1 = no limit. Changes apply at once to every account of the plan; accounts over a lowered limit keep what is online but cannot add more.', 'sm-subscriptions' ) . '</p>';
		foreach ( $all as $class => $plans ) {
			echo '<h2>' . esc_html( Companies::class_label( $class ) ) . '</h2><table class="widefat striped smsub-plans"><thead><tr><th></th>';
			foreach ( $plans as $code => $p ) {
				echo '<th>' . esc_html( Plans::name( $code, $class ) ) . '</th>';
			}
			echo '</tr></thead><tbody>';
			foreach ( $labels as $k => $label ) {
				echo '<tr><th>' . esc_html( $label . ( $units[ $k ] ?? '' ) ) . '</th>';
				foreach ( $plans as $code => $p ) {
					$name = 'plans[' . $class . '][' . $code . '][' . $k . ']';
					if ( 'contacts' === $k ) {
						echo '<td><input type="checkbox" name="' . esc_attr( $name ) . '" value="1"' . checked( (int) $p[ $k ], 1, false ) . '></td>';
					} elseif ( 'featured' === $k ) {
						echo '<td><select name="' . esc_attr( $name ) . '"><option value="">—</option><option value="home"' . selected( $p[ $k ], 'home', false ) . '>' . esc_html__( 'Yes, in rotation', 'sm-subscriptions' ) . '</option></select></td>';
					} else {
						echo '<td><input type="text" inputmode="numeric" name="' . esc_attr( $name ) . '" value="' . esc_attr( Plans::UNLIMITED === $p[ $k ] ? '-1' : (string) $p[ $k ] ) . '"></td>';
					}
				}
				echo '</tr>';
			}
			echo '</tbody></table>';
		}
		submit_button();
		echo '</form>';
	}

	public static function handle_plans() {
		self::guard( 'smsub_plans', 'sm_manage_settings' );
		Plans::save( (array) wp_unslash( $_POST['plans'] ?? array() ) ); // phpcs:ignore WordPress.Security.NonceVerification
		\SM\Core\Audit\AuditLog::add( 'subscription.plans_saved', 'settings' );
		self::back( array( 'tab' => 'plans' ) );
	}

	/* ---- Settings ----------------------------------------------------- */

	private static function settings_tab() {
		$s = Settings::all();
		self::form_open( 'smsub_settings' );
		$text = static function ( $key, $label, $help = '' ) use ( $s ) {
			printf( '<tr><th><label for="smsub-%1$s">%2$s</label></th><td><input id="smsub-%1$s" name="s[%1$s]" class="regular-text" value="%3$s">%4$s</td></tr>', esc_attr( $key ), esc_html( $label ), esc_attr( (string) $s[ $key ] ), $help ? '<p class="description">' . esc_html( $help ) . '</p>' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput
		};
		echo '<h2>' . esc_html__( 'Invoicing identity of the platform', 'sm-subscriptions' ) . '</h2><table class="form-table"><tbody>';
		$text( 'legal_name', __( 'Legal name', 'sm-subscriptions' ) );
		$text( 'rc', __( 'Trade register (RC)', 'sm-subscriptions' ) );
		$text( 'nif', __( 'Tax number (NIF)', 'sm-subscriptions' ) );
		$text( 'nis', __( 'Statistical number (NIS)', 'sm-subscriptions' ) );
		$text( 'art', __( 'Tax article (AI)', 'sm-subscriptions' ) );
		echo '<tr><th>' . esc_html__( 'Address', 'sm-subscriptions' ) . '</th><td><textarea name="s[address]" rows="2" class="large-text">' . esc_textarea( $s['address'] ) . '</textarea></td></tr>';
		$text( 'tva_rate', __( 'TVA rate (%)', 'sm-subscriptions' ), __( 'Prices are tax included; the invoice shows the amount before tax and the TVA. 0 = no TVA on the invoice.', 'sm-subscriptions' ) );
		echo '</tbody></table><h2>' . esc_html__( 'Payment details shown to customers', 'sm-subscriptions' ) . '</h2><table class="form-table"><tbody><tr><th>' . esc_html__( 'Methods offered', 'sm-subscriptions' ) . '</th><td>';
		foreach ( Settings::method_labels() as $k => $l ) {
			printf( '<label style="margin-inline-end:16px"><input type="checkbox" name="s[methods][]" value="%s"%s> %s</label>', esc_attr( $k ), checked( in_array( $k, (array) $s['methods'], true ), true, false ), esc_html( $l ) );
		}
		echo '</td></tr>';
		$text( 'bank_name', __( 'Bank', 'sm-subscriptions' ) );
		$text( 'rib', __( 'RIB (bank transfer)', 'sm-subscriptions' ) );
		$text( 'ccp', __( 'CCP account and key', 'sm-subscriptions' ) );
		$text( 'baridimob', __( 'RIP for BaridiMob', 'sm-subscriptions' ) );
		$text( 'cheque_to', __( 'Cheque to the order of', 'sm-subscriptions' ) );
		echo '<tr><th>' . esc_html__( 'Instructions', 'sm-subscriptions' ) . '</th><td><textarea name="s[instructions]" rows="3" class="large-text">' . esc_textarea( $s['instructions'] ) . '</textarea><p class="description">' . esc_html__( 'For example: write the order number in the reason of the transfer.', 'sm-subscriptions' ) . '</p></td></tr>';
		echo '</tbody></table><h2>' . esc_html__( 'Founding members', 'sm-subscriptions' ) . '</h2><table class="form-table"><tbody>';
		echo '<tr><th>' . esc_html__( 'Offer active', 'sm-subscriptions' ) . '</th><td><label><input type="checkbox" name="s[founders_on]" value="1"' . checked( (int) $s['founders_on'], 1, false ) . '> ' . esc_html__( 'Give a paid plan free to the first verified accounts', 'sm-subscriptions' ) . '</label></td></tr>';
		$text( 'founders_slots', __( 'Places', 'sm-subscriptions' ) );
		$text( 'founders_months', __( 'Free months', 'sm-subscriptions' ) );
		$text( 'founders_level', __( 'Verification level required', 'sm-subscriptions' ), __( '1 = code confirmed, 2 = documents checked.', 'sm-subscriptions' ) );
		foreach ( Plans::classes() as $class ) {
			echo '<tr><th>' . esc_html( sprintf( /* translators: %s: type of account. */ __( 'Plan given (%s)', 'sm-subscriptions' ), Companies::class_label( $class ) ) ) . '</th><td><select name="s[founders_plan][' . esc_attr( $class ) . ']">';
			foreach ( Plans::paid( $class ) as $code => $p ) {
				printf( '<option value="%s"%s>%s</option>', esc_attr( $code ), selected( $s['founders_plan'][ $class ] ?? '', $code, false ), esc_html( Plans::name( $code, $class ) ) );
			}
			echo '</select></td></tr>';
		}
		echo '</tbody></table><h2>' . esc_html__( 'AI credit packs and listing options', 'sm-subscriptions' ) . '</h2><table class="form-table"><tbody>';
		$packs = array_pad( (array) $s['packs'], 3, array( 'credits' => '', 'price' => '' ) );
		foreach ( $packs as $i => $p ) {
			/* translators: %d: pack number. */
			echo '<tr><th>' . esc_html( sprintf( __( 'Pack %d', 'sm-subscriptions' ), $i + 1 ) ) . '</th><td><input name="s[packs][' . (int) $i . '][credits]" value="' . esc_attr( (string) $p['credits'] ) . '" style="width:80px"> ' . esc_html__( 'credits for', 'sm-subscriptions' ) . ' <input name="s[packs][' . (int) $i . '][price]" value="' . esc_attr( (string) $p['price'] ) . '" style="width:100px"> DA</td></tr>';
		}
		$text( 'opt_bump', __( 'Back to the top (DA)', 'sm-subscriptions' ) );
		$text( 'opt_urgent', __( '"Urgent" badge (DA)', 'sm-subscriptions' ) );
		$text( 'opt_urgent_days', __( '"Urgent" badge (days)', 'sm-subscriptions' ) );
		$text( 'opt_featured', __( 'Featured listing (DA)', 'sm-subscriptions' ) );
		$text( 'opt_featured_days', __( 'Featured listing (days)', 'sm-subscriptions' ) );
		$text( 'reminder_days', __( 'Reminder before the end of a plan (days)', 'sm-subscriptions' ) );
		echo '</tbody></table>';
		submit_button();
		echo '</form>';
	}

	public static function handle_settings() {
		self::guard( 'smsub_settings', 'sm_manage_settings' );
		Settings::save( (array) wp_unslash( $_POST['s'] ?? array() ) ); // phpcs:ignore WordPress.Security.NonceVerification
		\SM\Core\Audit\AuditLog::add( 'subscription.settings_saved', 'settings' );
		self::back( array( 'tab' => 'settings' ) );
	}
}
