<?php
/**
 * wp-admin -> Steel Marketplace -> Products.
 *
 * Tabs: products (moderation queue: approve / reject with a reason; all
 * products by status), categories (names, units, fields of each category,
 * order, visibility), settings.
 *
 * @package SM\Products
 */

namespace SM\Products\Admin;

use SM\Core\Accounts\Companies;
use SM\Core\Notifications\Notifications;
use SM\Products\Categories;
use SM\Products\Markets;
use SM\Products\Presenter;
use SM\Products\Products;
use SM\Products\Settings;

defined( 'ABSPATH' ) || exit;

final class Admin {

	const SLUG = 'sm-products';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 15 );
		foreach ( array( 'moderate', 'category', 'settings' ) as $a ) {
			add_action( 'admin_post_smp_' . $a, array( __CLASS__, 'handle_' . $a ) );
		}
	}

	public static function menu() {
		global $wpdb;
		$pending = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_products WHERE status = 'pending'" ); // phpcs:ignore WordPress.DB
		$label   = __( 'Products', 'sm-products' ) . ( $pending ? ' <span class="awaiting-mod">' . $pending . '</span>' : '' );
		add_submenu_page( 'sm-core', __( 'Products', 'sm-products' ), $label, 'sm_moderate', self::SLUG, array( __CLASS__, 'page' ) );
	}

	private static function url( array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::SLUG ), $args ), admin_url( 'admin.php' ) );
	}

	private static function back( array $args ) {
		wp_safe_redirect( self::url( $args ) );
		exit;
	}

	public static function page() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$tab    = sanitize_key( $_GET['tab'] ?? 'products' );
		$status = sanitize_key( $_GET['status'] ?? 'pending' );
		$msg    = sanitize_text_field( wp_unslash( $_GET['msg'] ?? '' ) );
		// phpcs:enable
		echo '<div class="wrap"><h1>' . esc_html__( 'Products', 'sm-products' ) . '</h1>';
		if ( $msg ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
		}
		$tabs = array(
			'products'   => __( 'Products', 'sm-products' ),
			'categories' => __( 'Categories', 'sm-products' ),
			'settings'   => __( 'Settings', 'sm-products' ),
		);
		echo '<nav class="nav-tab-wrapper">';
		foreach ( $tabs as $k => $l ) {
			printf( '<a class="nav-tab%s" href="%s">%s</a>', $k === $tab ? ' nav-tab-active' : '', esc_url( self::url( array( 'tab' => $k ) ) ), esc_html( $l ) );
		}
		echo '</nav><div style="margin-top:16px">';
		if ( 'categories' === $tab ) {
			self::tab_categories();
		} elseif ( 'settings' === $tab ) {
			self::tab_settings();
		} else {
			self::tab_products( $status );
		}
		echo '</div></div>';
	}

	private static function status_labels() {
		return array(
			'pending'   => __( 'Waiting for review', 'sm-products' ),
			'published' => __( 'Published', 'sm-products' ),
			'rejected'  => __( 'Changes requested', 'sm-products' ),
			'draft'     => __( 'Drafts', 'sm-products' ),
			'sold'      => __( 'Sold', 'sm-products' ),
			'expired'   => __( 'Expired', 'sm-products' ),
		);
	}

	private static function tab_products( $status ) {
		$labels = self::status_labels();
		if ( ! isset( $labels[ $status ] ) ) {
			$status = 'pending';
		}
		echo '<ul class="subsubsub">';
		$links = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$market = sanitize_key( (string) ( $_GET['market'] ?? '' ) );
		$market = Markets::exists( $market ) ? $market : '';
		foreach ( $labels as $k => $l ) {
			$n       = Products::query( array( 'status' => $k, 'market' => $market, 'per_page' => 1 ) )['total'];
			$links[] = sprintf( '<li><a href="%s"%s>%s <span class="count">(%d)</span></a></li>', esc_url( self::url( array_filter( array( 'status' => $k, 'market' => $market ) ) ) ), $k === $status ? ' class="current"' : '', esc_html( $l ), $n );
		}
		echo implode( ' | ', $links ) . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput
		$markets = Markets::all();
		if ( count( $markets ) > 1 ) {
			echo '<p style="float:right;margin:0">' . esc_html__( 'Market:', 'sm-products' ) . ' ';
			$m_links = array( sprintf( '<a href="%s"%s>%s</a>', esc_url( self::url( array( 'status' => $status ) ) ), '' === $market ? ' style="font-weight:600"' : '', esc_html__( 'All', 'sm-products' ) ) );
			foreach ( $markets as $k => $m ) {
				$m_links[] = sprintf( '<a href="%s"%s>%s</a>', esc_url( self::url( array( 'status' => $status, 'market' => $k ) ) ), $k === $market ? ' style="font-weight:600"' : '', esc_html( $m['label']['fr'] ) );
			}
			echo implode( ' · ', $m_links ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '<br class="clear">';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$q = Products::query( array( 'status' => $status, 'market' => $market, 'per_page' => 30, 'page' => max( 1, (int) ( $_GET['paged'] ?? 1 ) ), 'sort' => 'updated' ) );
		if ( ! $q['items'] ) {
			echo '<p>' . esc_html__( 'Nothing here.', 'sm-products' ) . '</p>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Product', 'sm-products' ) . '</th><th>' . esc_html__( 'Company', 'sm-products' ) . '</th><th>' . esc_html__( 'Weight / quantity', 'sm-products' ) . '</th><th>' . esc_html__( 'Price', 'sm-products' ) . '</th><th>' . esc_html__( 'Updated (UTC)', 'sm-products' ) . '</th><th>' . esc_html__( 'Decision', 'sm-products' ) . '</th></tr></thead><tbody>';
		foreach ( $q['items'] as $p ) {
			$c    = Companies::get( $p->company_id );
			$view = add_query_arg( 'preview', 1, sm_url( 'listing', array( 'id' => $p->listing_id ) ) );
			echo '<tr><td><strong><a href="' . esc_url( $view ) . '" target="_blank">' . esc_html( Presenter::title_text( $p ) ) . '</a></strong><br><code>' . esc_html( $p->designation ) . '</code> · ' . esc_html( Categories::get( $p->category )->name_fr ?? $p->category ) . ( count( $markets ) > 1 ? ' · <em>' . esc_html( $markets[ $p->market ]['label']['fr'] ?? $p->market ) . '</em>' : '' ) . ( $p->item_condition ? ' · ' . esc_html( $markets[ $p->market ]['conditions'][ $p->item_condition ]['fr'] ?? $p->item_condition ) : '' ) . '</td>';
			echo '<td>' . esc_html( $c ? $c->name : '—' ) . ( $c ? '<br><small>' . esc_html( sprintf( /* translators: %d: level. */ __( 'Verification level %d', 'sm-products' ), $c->verification_level ?? 0 ) ) . '</small>' : '' ) . '</td>';
			echo '<td>' . esc_html( $p->total_weight ? Presenter::n( $p->total_weight, 1 ) . ' kg (' . $p->weight_source . ')' : Presenter::n( $p->quantity ) . ' ' . Presenter::unit_label( $p->unit ) ) . '</td>';
			echo '<td>' . esc_html( Presenter::n( $p->price, 2 ) . ' DA / ' . Presenter::unit_label( $p->price_unit ) ) . '<br><small>' . esc_html( Presenter::n( $p->total_price ) . ' DA' ) . '</small></td>';
			echo '<td>' . esc_html( $p->updated_at ) . '</td><td>';
			if ( current_user_can( 'sm_moderate' ) && in_array( $p->status, array( 'pending', 'published', 'rejected' ), true ) ) {
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
				wp_nonce_field( 'smp_moderate' );
				echo '<input type="hidden" name="action" value="smp_moderate"><input type="hidden" name="id" value="' . (int) $p->id . '"><input type="hidden" name="status" value="' . esc_attr( $p->status ) . '">';
				if ( 'published' !== $p->status ) {
					echo '<button class="button button-primary" name="decision" value="approve">' . esc_html__( 'Publish', 'sm-products' ) . '</button> ';
				}
				echo '<input name="reason" placeholder="' . esc_attr__( 'Reason (sent to the seller)', 'sm-products' ) . '" style="width:180px"> <button class="button" name="decision" value="reject">' . esc_html( 'published' === $p->status ? __( 'Unpublish', 'sm-products' ) : __( 'Request changes', 'sm-products' ) ) . '</button>';
				echo '</form>';
			}
			if ( 'rejected' === $p->status && $p->reject_reason ) {
				echo '<p><em>' . esc_html( $p->reject_reason ) . '</em></p>';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table>';
	}

	private static function tab_categories() {
		$can = current_user_can( 'sm_manage_settings' );
		echo '<p>' . esc_html__( 'Profiles, tubes, plates and bars take their technical data and weight from the weight engine. For the other categories, the fields below are the form shown to sellers. Select options: one per line, "value|Français|عربي".', 'sm-products' ) . '</p>';
		$market = '';
		$cats   = Categories::all( true );
		$order  = array_flip( array_keys( Markets::all() ) );
		uasort( $cats, static fn( $x, $y ) => array( $order[ $x->market ] ?? 99, (int) $x->sort ) <=> array( $order[ $y->market ] ?? 99, (int) $y->sort ) );
		foreach ( $cats as $c ) {
			if ( $c->market !== $market ) {
				$market = $c->market;
				echo '<h2 class="title">' . esc_html( Markets::get( $market )['label']['fr'] ?? $market ) . '</h2>';
			}
			echo '<div class="card" style="max-width:none"><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'smp_category' );
			echo '<input type="hidden" name="action" value="smp_category"><input type="hidden" name="slug" value="' . esc_attr( $c->slug ) . '">';
			echo '<h2><code>' . esc_html( $c->slug ) . '</code> ' . esc_html( $c->name_fr ) . ( $c->active ? '' : ' — ' . esc_html__( 'hidden', 'sm-products' ) ) . '</h2>';
			echo '<p><label>' . esc_html__( 'Name (French)', 'sm-products' ) . ' <input name="name_fr" value="' . esc_attr( $c->name_fr ) . '"></label> <label>' . esc_html__( 'Name (Arabic)', 'sm-products' ) . ' <input name="name_ar" dir="rtl" value="' . esc_attr( $c->name_ar ) . '"></label> <label>' . esc_html__( 'Order', 'sm-products' ) . ' <input name="sort" type="number" style="width:70px" value="' . (int) $c->sort . '"></label> <label><input type="checkbox" name="active" value="1"' . checked( $c->active, 1, false ) . '> ' . esc_html__( 'Visible', 'sm-products' ) . '</label></p>';
			echo '<p>' . esc_html__( 'Units:', 'sm-products' ) . ' ';
			foreach ( Categories::UNITS as $u ) {
				echo '<label style="margin-inline-end:12px"><input type="checkbox" name="units[]" value="' . esc_attr( $u ) . '"' . checked( in_array( $u, $c->units, true ), true, false ) . '> ' . esc_html( Presenter::unit_label( $u ) ) . '</label>';
			}
			echo '</p>';
			if ( 'engine' === $c->kind || 'lines' === $c->kind ) {
				echo '<p>' . esc_html__( 'Weight engine families:', 'sm-products' ) . ' <code>' . esc_html( implode( ', ', $c->families ) ) . '</code></p>';
			} else {
				echo '<table class="widefat"><thead><tr><th>' . esc_html__( 'Key', 'sm-products' ) . '</th><th>' . esc_html__( 'Type', 'sm-products' ) . '</th><th>' . esc_html__( 'Label (French)', 'sm-products' ) . '</th><th>' . esc_html__( 'Label (Arabic)', 'sm-products' ) . '</th><th>' . esc_html__( 'Unit', 'sm-products' ) . '</th><th>' . esc_html__( 'Required', 'sm-products' ) . '</th><th>' . esc_html__( 'Options (lists)', 'sm-products' ) . '</th></tr></thead><tbody>';
				$fields   = $c->fields;
				$fields[] = array( 'key' => '', 'type' => 'text', 'label' => array( 'fr' => '', 'ar' => '' ), 'required' => false );
				foreach ( $fields as $i => $f ) {
					$opts = implode( "\n", array_map( static fn( $o ) => $o['value'] . '|' . $o['fr'] . '|' . $o['ar'], $f['options'] ?? array() ) );
					echo '<tr><td><input name="fields[' . (int) $i . '][key]" value="' . esc_attr( $f['key'] ) . '" style="width:120px" placeholder="' . esc_attr__( 'new_field', 'sm-products' ) . '"></td><td><select name="fields[' . (int) $i . '][type]">';
					foreach ( array( 'text', 'number', 'select' ) as $t ) {
						echo '<option' . selected( $f['type'], $t, false ) . '>' . esc_html( $t ) . '</option>';
					}
					echo '</select></td><td><input name="fields[' . (int) $i . '][label][fr]" value="' . esc_attr( $f['label']['fr'] ) . '"></td><td><input dir="rtl" name="fields[' . (int) $i . '][label][ar]" value="' . esc_attr( $f['label']['ar'] ) . '"></td><td><input name="fields[' . (int) $i . '][unit]" value="' . esc_attr( $f['unit'] ?? '' ) . '" style="width:60px"></td><td><input type="checkbox" name="fields[' . (int) $i . '][required]" value="1"' . checked( ! empty( $f['required'] ), true, false ) . '></td><td><textarea name="fields[' . (int) $i . '][options]" rows="2" cols="34">' . esc_textarea( $opts ) . '</textarea></td></tr>';
				}
				echo '</tbody></table><p class="description">' . esc_html__( 'To remove a field, empty its key. The last row adds a field.', 'sm-products' ) . '</p>';
			}
			if ( $can ) {
				submit_button( __( 'Save the category', 'sm-products' ), 'secondary' );
			}
			echo '</form></div>';
		}
	}

	private static function tab_settings() {
		$s = Settings::all();
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'smp_settings' );
		echo '<input type="hidden" name="action" value="smp_settings"><table class="form-table" role="presentation">';
		echo '<tr><th>' . esc_html__( 'Moderation', 'sm-products' ) . '</th><td><label><input type="checkbox" name="moderation" value="1"' . checked( $s['moderation'], 1, false ) . '> ' . esc_html__( 'Check every product before it is published', 'sm-products' ) . '</label></td></tr>';
		echo '<tr><th>' . esc_html__( 'Client accounts', 'sm-products' ) . '</th><td><label><input type="checkbox" name="client_moderation" value="1"' . checked( $s['client_moderation'], 1, false ) . '> ' . esc_html__( 'Always check the listings of client accounts (private sellers), even when moderation is off', 'sm-products' ) . '</label></td></tr>';
		$rows = array(
			'client_max_active' => __( 'Listings online per client account (0 = no limit)', 'sm-products' ),
			'expiry_days' => __( 'Publication period (days, 0 = no end)', 'sm-products' ),
			'max_photos'  => __( 'Photos per product', 'sm-products' ),
			'photo_mb'    => __( 'Largest photo (MB)', 'sm-products' ),
			'sheet_mb'    => __( 'Largest technical sheet (MB)', 'sm-products' ),
			'min_price'   => __( 'Lowest price accepted (DA)', 'sm-products' ),
			'max_price'   => __( 'Highest price accepted (DA)', 'sm-products' ),
			'grades'      => __( 'Steel grades offered (comma separated)', 'sm-products' ),
		);
		foreach ( $rows as $k => $l ) {
			$v = is_array( $s[ $k ] ) ? implode( ', ', $s[ $k ] ) : $s[ $k ];
			echo '<tr><th><label for="smp-' . esc_attr( $k ) . '">' . esc_html( $l ) . '</label></th><td><input id="smp-' . esc_attr( $k ) . '" name="' . esc_attr( $k ) . '" value="' . esc_attr( (string) $v ) . '" class="regular-text"></td></tr>';
		}
		echo '</table>';
		if ( current_user_can( 'sm_manage_settings' ) ) {
			submit_button();
		}
		echo '</form>';
	}

	/* ---- Handlers ------------------------------------------------------ */

	public static function handle_moderate() {
		if ( ! current_user_can( 'sm_moderate' ) ) {
			wp_die( '', 403 );
		}
		check_admin_referer( 'smp_moderate' );
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$id       = absint( $_POST['id'] ?? 0 );
		$decision = sanitize_key( $_POST['decision'] ?? '' );
		$reason   = sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) );
		$from     = sanitize_key( $_POST['status'] ?? 'pending' );
		// phpcs:enable
		$p = Products::get( $id );
		if ( ! $p ) {
			self::back( array( 'status' => $from ) );
		}
		if ( 'approve' === $decision ) {
			Products::set_status( $id, 'published' );
			Notifications::notify_company( $p->company_id, 'product.approved', array( 'product_id' => $id ) );
			self::back( array( 'status' => $from, 'msg' => __( 'Product published. The seller was notified.', 'sm-products' ) ) );
		}
		if ( 'reject' === $decision ) {
			if ( '' === trim( $reason ) ) {
				self::back( array( 'status' => $from, 'msg' => __( 'Give the reason, so the seller knows what to change.', 'sm-products' ) ) );
			}
			Products::set_status( $id, 'rejected', $reason );
			Notifications::notify_company( $p->company_id, 'product.rejected', array( 'product_id' => $id, 'reason' => $reason ) );
			self::back( array( 'status' => $from, 'msg' => __( 'The seller was asked to change the product.', 'sm-products' ) ) );
		}
		self::back( array( 'status' => $from ) );
	}

	public static function handle_category() {
		if ( ! current_user_can( 'sm_manage_settings' ) ) {
			wp_die( '', 403 );
		}
		check_admin_referer( 'smp_category' );
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$slug   = sanitize_key( $_POST['slug'] ?? '' );
		$fields = array();
		foreach ( (array) wp_unslash( $_POST['fields'] ?? array() ) as $f ) {
			$f['options'] = preg_split( '/\r?\n/', (string) ( $f['options'] ?? '' ) );
			$fields[]     = $f;
		}
		Categories::save(
			$slug,
			array(
				'name_fr' => sanitize_text_field( wp_unslash( $_POST['name_fr'] ?? '' ) ),
				'name_ar' => sanitize_text_field( wp_unslash( $_POST['name_ar'] ?? '' ) ),
				'sort'    => (int) ( $_POST['sort'] ?? 0 ),
				'active'  => ! empty( $_POST['active'] ),
				'units'   => array_map( 'sanitize_key', (array) ( $_POST['units'] ?? array() ) ),
				'fields'  => $fields,
			)
		);
		// phpcs:enable
		\SM\Core\Audit\AuditLog::add( 'product_category.saved', 'product_category', 0, array( 'slug' => $slug ) );
		self::back( array( 'tab' => 'categories', 'msg' => __( 'Category saved.', 'sm-products' ) ) );
	}

	public static function handle_settings() {
		if ( ! current_user_can( 'sm_manage_settings' ) ) {
			wp_die( '', 403 );
		}
		check_admin_referer( 'smp_settings' );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		Settings::save( array_map( 'sanitize_text_field', wp_unslash( $_POST ) ) );
		self::back( array( 'tab' => 'settings', 'msg' => __( 'Settings saved.', 'sm-products' ) ) );
	}
}
