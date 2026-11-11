<?php
/**
 * SM → Types and categories: one screen to add, edit, hide and delete the
 * lists the administrator controls (types of companies, trades of
 * professionals, and the lists other modules register, e.g. the services of
 * sm-services). Each entry has a French and an Arabic name and description,
 * an image and a display order. Deleting an entry that accounts use asks for
 * a replacement first.
 *
 * Other modules add a list with the "sm_term_lists" filter:
 *   key => [
 *     label  => tab label,
 *     items  => callable(): code => [ fr, ar, desc_fr, desc_ar, image_id, order, hidden, usage, default ],
 *     save   => callable( string $code ('' = new), array $data ): string code | WP_Error,
 *     delete => callable( string $code, string $replacement ): true | WP_Error,
 *     hide   => callable( string $code, bool $hidden ): void,
 *     help   => optional text,
 *   ]
 *
 * @package SM\Core
 */

namespace SM\Core\Admin;

use SM\Core\Accounts\Companies;
use SM\Core\Audit\AuditLog;

defined( 'ABSPATH' ) || exit;

final class Terms {

	const PAGE = 'sm-types';

	public static function init() {
		add_action( 'admin_post_sm_term_save', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_sm_term_delete', array( __CLASS__, 'delete' ) );
		add_action( 'admin_post_sm_term_hide', array( __CLASS__, 'hide' ) );
		add_filter( 'sm_term_lists', array( __CLASS__, 'core_lists' ), 5 );
	}

	/** All lists, in tab order. */
	public static function lists() {
		return (array) apply_filters( 'sm_term_lists', array() );
	}

	/* ---- Core lists: company types and trades ------------------------- */

	public static function core_lists( $lists ) {
		$lists['company']      = self::activity_list( 'company', __( 'Company types', 'sm-core' ), __( 'Companies choose one or more types at registration. A new type gets the rights of a contracting company (see Rights).', 'sm-core' ) );
		$lists['professional'] = self::activity_list( 'professional', __( 'Trades of professionals', 'sm-core' ), __( 'Professionals choose one or more trades at registration.', 'sm-core' ) );
		return $lists;
	}

	private static function activity_list( $class, $label, $help ) {
		return array(
			'label'  => $label,
			'help'   => $help,
			'items'  => static function () use ( $class ) {
				$t      = Companies::type_settings();
				$labels = Companies::activity_labels();
				$out    = array();
				foreach ( Companies::activities( $class, true ) as $code ) {
					$l = (array) ( $t['labels'][ $code ] ?? array() );
					$d = (array) ( $t['desc'][ $code ] ?? array() );
					$out[ $code ] = array(
						'fr'       => (string) ( $l['fr'] ?? '' ),
						'ar'       => (string) ( $l['ar'] ?? '' ),
						'default'  => $labels[ $code ] ?? $code,
						'desc_fr'  => (string) ( $d['fr'] ?? '' ),
						'desc_ar'  => (string) ( $d['ar'] ?? '' ),
						'image_id' => (int) ( $t['image'][ $code ] ?? 0 ),
						'order'    => (int) ( $t['order'][ $code ] ?? 0 ),
						'hidden'   => in_array( $code, (array) $t['disabled'], true ),
						'usage'    => Companies::activity_usage( $code ),
					);
				}
				return $out;
			},
			'save'   => static function ( $code, array $data ) use ( $class ) {
				$t = Companies::type_settings();
				if ( '' === $code ) {
					if ( '' === $data['fr'] ) {
						return new \WP_Error( 'sm_name', __( 'Enter the French name.', 'sm-core' ) );
					}
					$base = ( 'professional' === $class ? 'trade_' : 'type_' ) . substr( str_replace( '-', '_', sanitize_title( $data['fr'] ) ), 0, 32 );
					$code = $base;
					for ( $i = 2; '' !== Companies::activity_class( $code ) || in_array( $code, array( 'client', 'engineer' ), true ) || in_array( $code, (array) $t['deleted'], true ); $i++ ) {
						$code = $base . '_' . $i;
					}
					$t['custom'][ $class ]   = array_values( array_unique( array_merge( (array) ( $t['custom'][ $class ] ?? array() ), array( $code ) ) ) );
				} elseif ( Companies::activity_class( $code ) !== $class ) {
					return new \WP_Error( 'sm_unknown', __( 'Unknown type.', 'sm-core' ) );
				}
				$t['labels'][ $code ] = array( 'fr' => $data['fr'], 'ar' => $data['ar'] );
				if ( '' === $data['fr'] && '' === $data['ar'] ) {
					unset( $t['labels'][ $code ] );
				}
				$t['desc'][ $code ]  = array( 'fr' => $data['desc_fr'], 'ar' => $data['desc_ar'] );
				$t['image'][ $code ] = (int) $data['image_id'];
				$t['order'][ $code ] = (int) $data['order'];
				Companies::update_type_settings( $t );
				return $code;
			},
			'hide'   => static function ( $code, $hidden ) {
				$t = Companies::type_settings();
				$t['disabled'] = $hidden ? array_values( array_unique( array_merge( (array) $t['disabled'], array( $code ) ) ) ) : array_values( array_diff( (array) $t['disabled'], array( $code ) ) );
				Companies::update_type_settings( array( 'disabled' => $t['disabled'] ) );
			},
			'delete' => static fn( $code, $replacement ) => Companies::delete_activity( $code, $replacement ),
		);
	}

	/* ---- Screen ------------------------------------------------------- */

	private static function url( array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::PAGE ), $args ), admin_url( 'admin.php' ) );
	}

	private static function image_html( $id, $size = 56 ) {
		$src = $id ? wp_get_attachment_image_src( (int) $id, 'thumbnail' ) : false;
		return $src
			? '<img src="' . esc_url( $src[0] ) . '" alt="" width="' . (int) $size . '" height="' . (int) round( $size * .75 ) . '" style="object-fit:cover;border-radius:4px;display:block">'
			: '<span style="display:block;width:' . (int) $size . 'px;height:' . (int) round( $size * .75 ) . 'px;border:1px dashed #8c8f94;border-radius:4px"></span>';
	}

	public static function page() {
		$lists = self::lists();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$tab   = isset( $_GET['tab'], $lists[ sanitize_key( $_GET['tab'] ) ] ) ? sanitize_key( $_GET['tab'] ) : (string) array_key_first( $lists );
		$edit  = isset( $_GET['edit'] ) ? sanitize_key( $_GET['edit'] ) : '';
		$del   = isset( $_GET['delete'] ) ? sanitize_key( $_GET['delete'] ) : '';
		// phpcs:enable
		$list  = $lists[ $tab ];
		$items = (array) call_user_func( $list['items'] );

		echo '<div class="wrap sm-admin sm-terms"><h1>' . esc_html__( 'Types and categories', 'sm-core' ) . '</h1>';
		self::notice();
		echo '<nav class="nav-tab-wrapper">';
		foreach ( $lists as $key => $l ) {
			echo '<a class="nav-tab' . ( $key === $tab ? ' nav-tab-active' : '' ) . '" href="' . esc_url( self::url( array( 'tab' => $key ) ) ) . '">' . esc_html( $l['label'] ) . '</a>';
		}
		echo '</nav>';
		if ( ! empty( $list['help'] ) ) {
			echo '<p>' . esc_html( $list['help'] ) . '</p>';
		}

		if ( $del && isset( $items[ $del ] ) ) {
			self::delete_screen( $tab, $del, $items );
			echo '</div>';
			return;
		}

		$cur = ( $edit && isset( $items[ $edit ] ) ) ? $items[ $edit ] : array( 'fr' => '', 'ar' => '', 'desc_fr' => '', 'desc_ar' => '', 'image_id' => 0, 'order' => 0, 'default' => '' );
		echo '<div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;margin-top:16px">';

		// Add / edit form.
		echo '<form class="sm-term-form" method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="flex:0 0 340px;background:#fff;border:1px solid #c3c4c7;padding:16px">';
		wp_nonce_field( 'sm_term_save' );
		echo '<input type="hidden" name="action" value="sm_term_save"><input type="hidden" name="list" value="' . esc_attr( $tab ) . '"><input type="hidden" name="code" value="' . esc_attr( $edit && isset( $items[ $edit ] ) ? $edit : '' ) . '">';
		echo '<h2 style="margin-top:0">' . esc_html( $edit && isset( $items[ $edit ] ) ? sprintf( /* translators: %s: name. */ __( 'Edit “%s”', 'sm-core' ), $cur['fr'] ? $cur['fr'] : $cur['default'] ) : __( 'Add', 'sm-core' ) ) . '</h2>';
		$field = static function ( $name, $label, $value, $type = 'text', $rtl = false, $ph = '' ) {
			$dir = $rtl ? ' dir="rtl"' : '';
			echo '<p><label for="sm-term-' . esc_attr( $name ) . '"><strong>' . esc_html( $label ) . '</strong></label><br>';
			if ( 'textarea' === $type ) {
				echo '<textarea id="sm-term-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" rows="3" class="large-text"' . $dir . '>' . esc_textarea( $value ) . '</textarea></p>'; // phpcs:ignore WordPress.Security.EscapeOutput
			} else {
				echo '<input id="sm-term-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" type="' . esc_attr( $type ) . '" value="' . esc_attr( $value ) . '" class="' . ( 'number' === $type ? 'small-text' : 'regular-text' ) . '" placeholder="' . esc_attr( $ph ) . '"' . $dir . '></p>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
		};
		$field( 'fr', __( 'Name (French)', 'sm-core' ), $cur['fr'], 'text', false, $cur['default'] );
		$field( 'ar', __( 'Name (Arabic)', 'sm-core' ), $cur['ar'], 'text', true );
		$field( 'desc_fr', __( 'Description (French)', 'sm-core' ), $cur['desc_fr'], 'textarea' );
		$field( 'desc_ar', __( 'Description (Arabic)', 'sm-core' ), $cur['desc_ar'], 'textarea', true );
		echo '<p><strong>' . esc_html__( 'Image', 'sm-core' ) . '</strong></p><div style="display:flex;gap:10px;align-items:center">' . self::image_html( $cur['image_id'], 64 ) . '<input type="file" name="image" accept="image/jpeg,image/png,image/webp"></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		if ( $cur['image_id'] ) {
			echo '<p><label><input type="checkbox" name="remove_image" value="1"> ' . esc_html__( 'Remove the image', 'sm-core' ) . '</label></p>';
		}
		$field( 'order', __( 'Order', 'sm-core' ), (string) $cur['order'], 'number' );
		echo '<p class="description">' . esc_html__( 'Leave a name empty to use the default wording (translated).', 'sm-core' ) . '</p>';
		submit_button( $edit && isset( $items[ $edit ] ) ? __( 'Save', 'sm-core' ) : __( 'Add', 'sm-core' ), 'primary', 'submit', false );
		if ( $edit ) {
			echo ' <a class="button" href="' . esc_url( self::url( array( 'tab' => $tab ) ) ) . '">' . esc_html__( 'Cancel', 'sm-core' ) . '</a>';
		}
		echo '</form>';

		// Table.
		echo '<table class="widefat striped" style="flex:1 1 560px"><thead><tr><th style="width:72px">' . esc_html__( 'Image', 'sm-core' ) . '</th><th>' . esc_html__( 'Name', 'sm-core' ) . '</th><th>' . esc_html__( 'Description', 'sm-core' ) . '</th><th style="width:80px">' . esc_html__( 'In use', 'sm-core' ) . '</th></tr></thead><tbody>';
		foreach ( $items as $code => $it ) {
			$name = $it['fr'] ? $it['fr'] : $it['default'];
			echo '<tr data-code="' . esc_attr( $code ) . '"><td>' . self::image_html( $it['image_id'] ) . '</td><td><strong>' . esc_html( $name ) . '</strong>'; // phpcs:ignore WordPress.Security.EscapeOutput
			if ( $it['hidden'] ) {
				echo ' <span class="sm-pill warn">' . esc_html__( 'Hidden', 'sm-core' ) . '</span>';
			}
			echo '<br><span dir="rtl" style="color:#646970">' . esc_html( $it['ar'] ) . '</span><br><code style="font-size:11px">' . esc_html( $code ) . '</code>';
			echo '<div class="row-actions visible"><a href="' . esc_url( self::url( array( 'tab' => $tab, 'edit' => $code ) ) ) . '">' . esc_html__( 'Edit', 'sm-core' ) . '</a> | ';
			echo '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'sm_term_hide', 'list' => $tab, 'code' => $code, 'hidden' => $it['hidden'] ? 0 : 1 ), admin_url( 'admin-post.php' ) ), 'sm_term_hide' ) ) . '">' . esc_html( $it['hidden'] ? __( 'Show', 'sm-core' ) : __( 'Hide', 'sm-core' ) ) . '</a> | ';
			echo '<span class="trash"><a class="sm-term-delete" href="' . esc_url( self::url( array( 'tab' => $tab, 'delete' => $code ) ) ) . '">' . esc_html__( 'Delete', 'sm-core' ) . '</a></span></div></td>';
			echo '<td style="color:#50575e">' . esc_html( $it['desc_fr'] ) . '</td><td>' . (int) $it['usage'] . '</td></tr>';
		}
		echo '</tbody></table></div></div>';
	}

	private static function delete_screen( $tab, $code, array $items ) {
		$it   = $items[ $code ];
		$name = $it['fr'] ? $it['fr'] : $it['default'];
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="max-width:560px;background:#fff;border:1px solid #c3c4c7;padding:16px;margin-top:16px">';
		wp_nonce_field( 'sm_term_delete' );
		echo '<input type="hidden" name="action" value="sm_term_delete"><input type="hidden" name="list" value="' . esc_attr( $tab ) . '"><input type="hidden" name="code" value="' . esc_attr( $code ) . '">';
		echo '<h2 style="margin-top:0">' . esc_html( sprintf( /* translators: %s: name. */ __( 'Delete “%s”', 'sm-core' ), $name ) ) . '</h2>';
		if ( $it['usage'] ) {
			echo '<p>' . esc_html( sprintf( /* translators: %d: number of accounts or pages. */ _n( '%d entry uses it. Choose what replaces it before the deletion:', '%d entries use it. Choose what replaces it before the deletion:', $it['usage'], 'sm-core' ), $it['usage'] ) ) . '</p>';
			echo '<p><label for="sm-term-repl"><strong>' . esc_html__( 'Replace with', 'sm-core' ) . '</strong></label><br><select id="sm-term-repl" name="replacement" required><option value="">' . esc_html__( '— choose —', 'sm-core' ) . '</option>';
			foreach ( $items as $c => $other ) {
				if ( $c !== $code ) {
					echo '<option value="' . esc_attr( $c ) . '">' . esc_html( $other['fr'] ? $other['fr'] : $other['default'] ) . '</option>';
				}
			}
			echo '</select></p>';
		} else {
			echo '<p>' . esc_html__( 'Nothing uses it. The deletion cannot be undone.', 'sm-core' ) . '</p>';
		}
		submit_button( $it['usage'] ? __( 'Replace and delete', 'sm-core' ) : __( 'Delete', 'sm-core' ), 'delete', 'submit', false );
		echo ' <a class="button" href="' . esc_url( self::url( array( 'tab' => $tab ) ) ) . '">' . esc_html__( 'Cancel', 'sm-core' ) . '</a></form>';
	}

	private static function notice() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Saved.', 'sm-core' ) . '</p></div>';
		}
		if ( isset( $_GET['sm_error'] ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['sm_error'] ) ) ) . '</p></div>';
		}
		// phpcs:enable
	}

	/* ---- Actions ------------------------------------------------------ */

	private static function guard( $nonce ) {
		check_admin_referer( $nonce );
		if ( ! current_user_can( 'sm_manage_settings' ) ) {
			wp_die( '', 403 );
		}
		$lists = self::lists();
		$key   = sanitize_key( wp_unslash( $_REQUEST['list'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! isset( $lists[ $key ] ) ) {
			wp_die( '', 400 );
		}
		return array( $key, $lists[ $key ], sanitize_key( wp_unslash( $_REQUEST['code'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	private static function back( $tab, $error = null ) {
		$args = array( 'tab' => $tab );
		if ( is_wp_error( $error ) ) {
			$args['sm_error'] = rawurlencode( $error->get_error_message() );
		} else {
			$args['updated'] = 1;
		}
		wp_safe_redirect( self::url( $args ) );
		exit;
	}

	public static function save() {
		list( $key, $list, $code ) = self::guard( 'sm_term_save' );
		$items = (array) call_user_func( $list['items'] );
		if ( '' !== $code && ! isset( $items[ $code ] ) ) {
			self::back( $key, new \WP_Error( 'sm_unknown', __( 'Unknown entry.', 'sm-core' ) ) );
		}
		$text = static fn( $k, $max ) => mb_substr( trim( sanitize_textarea_field( wp_unslash( $_POST[ $k ] ?? '' ) ) ), 0, $max ); // phpcs:ignore WordPress.Security.NonceVerification
		$data = array(
			'fr'       => $text( 'fr', 80 ),
			'ar'       => $text( 'ar', 80 ),
			'desc_fr'  => $text( 'desc_fr', 500 ),
			'desc_ar'  => $text( 'desc_ar', 500 ),
			'order'    => (int) ( $_POST['order'] ?? 0 ), // phpcs:ignore WordPress.Security.NonceVerification
			'image_id' => '' !== $code ? (int) $items[ $code ]['image_id'] : 0,
		);
		if ( ! empty( $_POST['remove_image'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$data['image_id'] = 0;
		}
		if ( ! empty( $_FILES['image']['name'] ) ) {
			$type = wp_check_filetype( sanitize_file_name( wp_unslash( $_FILES['image']['name'] ) ) );
			if ( ! in_array( $type['type'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
				self::back( $key, new \WP_Error( 'sm_image', __( 'The image must be a JPG, PNG or WEBP file.', 'sm-core' ) ) );
			}
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$id = media_handle_upload( 'image', 0 );
			if ( is_wp_error( $id ) ) {
				self::back( $key, $id );
			}
			$data['image_id'] = (int) $id;
		}
		$result = call_user_func( $list['save'], $code, $data );
		if ( is_wp_error( $result ) ) {
			self::back( $key, $result );
		}
		AuditLog::add( 'admin.term_saved', 'settings', 0, array( 'list' => $key, 'code' => $result ) );
		self::back( $key );
	}

	public static function hide() {
		list( $key, $list, $code ) = self::guard( 'sm_term_hide' );
		call_user_func( $list['hide'], $code, ! empty( $_GET['hidden'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		AuditLog::add( 'admin.term_hidden', 'settings', 0, array( 'list' => $key, 'code' => $code ) );
		self::back( $key );
	}

	public static function delete() {
		list( $key, $list, $code ) = self::guard( 'sm_term_delete' );
		$repl   = sanitize_key( wp_unslash( $_POST['replacement'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$result = call_user_func( $list['delete'], $code, $repl );
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( self::url( array( 'tab' => $key, 'delete' => $code, 'sm_error' => rawurlencode( $result->get_error_message() ) ) ) );
			exit;
		}
		AuditLog::add( 'admin.term_deleted', 'settings', 0, array( 'list' => $key, 'code' => $code, 'replacement' => $repl ) );
		self::back( $key );
	}
}
