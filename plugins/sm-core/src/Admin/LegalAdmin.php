<?php
/**
 * Administration of the legal pages (0.10.0): SM → Legal information (company,
 * data protection, version of the texts) and the texts of the two pages in
 * French, Arabic and English, in the editor of each page.
 *
 * @package SM\Core
 */

namespace SM\Core\Admin;

use SM\Core\Legal\Legal;

defined( 'ABSPATH' ) || exit;

final class LegalAdmin {

	const CAP  = 'sm_manage_settings';
	const SLUG = 'sm-legal';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 21 );
		add_action( 'admin_post_sm_legal_save', array( __CLASS__, 'save' ) );
		add_filter( 'use_block_editor_for_post', array( __CLASS__, 'no_blocks' ), 20, 2 );
		add_action( 'add_meta_boxes_page', array( __CLASS__, 'boxes' ) );
		add_action( 'save_post_page', array( __CLASS__, 'save_texts' ), 10, 2 );
	}

	public static function menu() {
		add_submenu_page( 'sm-core', __( 'Legal information', 'sm-core' ), __( 'Legal information', 'sm-core' ), self::CAP, self::SLUG, array( __CLASS__, 'page' ) );
	}

	/** Labels and help of the fields. */
	public static function fields() {
		return array(
			__( 'Publisher of the site', 'sm-core' ) => array(
				'company'       => array( __( 'Company name', 'sm-core' ), Legal::DEFAULT_COMPANY, '' ),
				'legal_form'    => array( __( 'Legal form', 'sm-core' ), 'SARL, SPA, EURL…', '' ),
				'rc'            => array( __( 'Trade register number', 'sm-core' ), '', '' ),
				'nif'           => array( __( 'NIF', 'sm-core' ), '', '' ),
				'address'       => array( __( 'Address of the registered office', 'sm-core' ), '', '' ),
				'court_city'    => array( __( 'City of the court', 'sm-core' ), __( 'city of the registered office', 'sm-core' ), __( 'Article 15 of the terms: “the courts of …”.', 'sm-core' ) ),
				'publisher'     => array( __( 'Publication director', 'sm-core' ), '', '' ),
				'contact_email' => array( __( 'Contact e-mail', 'sm-core' ), Legal::DEFAULT_EMAIL, '' ),
			),
			__( 'Data protection (law 18-07)', 'sm-core' ) => array(
				'dpo'       => array( __( 'Data protection officer', 'sm-core' ), __( 'name or position', 'sm-core' ), '' ),
				'dpo_email' => array( __( 'E-mail of the officer', 'sm-core' ), Legal::DEFAULT_EMAIL, '' ),
				'anpdp'     => array( __( 'ANPDP receipt number', 'sm-core' ), '', '' ),
				'transfer'  => array( __( 'Reference of the transfer authorisation', 'sm-core' ), '', __( 'Article 5 of the policy (data sent outside Algeria).', 'sm-core' ) ),
				'host'      => array( __( 'Host of the site', 'sm-core' ), '', '' ),
			),
			__( 'Version of the texts', 'sm-core' ) => array(
				'version' => array( __( 'Version', 'sm-core' ), '1', __( 'Change it after an important change: the version accepted is kept in each account.', 'sm-core' ) ),
				'date'    => array( __( 'In force from', 'sm-core' ), '', '' ),
			),
		);
	}

	public static function page() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$info = Legal::info();
		echo '<div class="wrap sm-admin"><h1>' . esc_html__( 'Legal information', 'sm-core' ) . '</h1>';
		if ( isset( $_GET['saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Saved.', 'sm-core' ) . '</p></div>';
		}
		echo '<div class="notice notice-info inline"><p>' . esc_html__( 'This information is shown in the Terms of use and the Privacy policy (French, Arabic, English). An empty field is not shown; without a company name the site writes “ReuSteel”, without an e-mail “contact@reusteel.com”.', 'sm-core' ) . '</p></div>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'sm_legal_save' );
		echo '<input type="hidden" name="action" value="sm_legal_save">';
		foreach ( self::fields() as $section => $fields ) {
			echo '<h2>' . esc_html( $section ) . '</h2><table class="form-table"><tbody>';
			foreach ( $fields as $k => $f ) {
				$type = in_array( $k, array( 'contact_email', 'dpo_email' ), true ) ? 'email' : ( 'date' === $k ? 'date' : 'text' );
				$cls  = 'version' === $k ? 'small-text' : ( 'date' === $k ? '' : 'regular-text' );
				echo '<tr><th><label for="sml-' . esc_attr( $k ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
				echo '<input type="' . esc_attr( $type ) . '" class="' . esc_attr( $cls ) . '" id="sml-' . esc_attr( $k ) . '" name="legal[' . esc_attr( $k ) . ']" value="' . esc_attr( $info[ $k ] ) . '" placeholder="' . esc_attr( $f[1] ) . '">';
				if ( '' !== $f[2] ) {
					echo '<p class="description">' . esc_html( $f[2] ) . '</p>';
				}
				echo '</td></tr>';
			}
			echo '</tbody></table>';
		}
		$links = array();
		foreach ( Legal::KEYS as $key ) {
			$id = Legal::page_id( $key );
			if ( $id ) {
				$links[] = '<a href="' . esc_url( (string) get_edit_post_link( $id ) ) . '">' . esc_html( Legal::title( $key, 0 === strpos( get_user_locale(), 'ar' ) ? 'ar' : ( 0 === strpos( get_user_locale(), 'fr' ) ? 'fr' : 'en' ) ) ) . '</a>';
			}
		}
		if ( $links ) {
			echo '<p>' . esc_html__( 'Texts of the pages (French, Arabic, English):', 'sm-core' ) . ' ' . implode( ' · ', $links ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above.
		}
		submit_button();
		echo '</form></div>';
	}

	public static function save() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Not allowed.', 'sm-core' ) );
		}
		check_admin_referer( 'sm_legal_save' );
		$in = isset( $_POST['legal'] ) && is_array( $_POST['legal'] ) ? wp_unslash( $_POST['legal'] ) : array(); // phpcs:ignore WordPress.Security.ValidationSanitization.InputNotSanitized -- sanitized in save_info().
		Legal::save_info( $in );
		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG, 'saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/** The two pages are written as Markdown in three languages, not with blocks. */
	public static function no_blocks( $use, $post ) {
		return $post && Legal::key_of( $post->ID ) ? false : $use;
	}

	public static function boxes( $post ) {
		$key = Legal::key_of( $post->ID );
		if ( ! $key ) {
			return;
		}
		remove_post_type_support( 'page', 'editor' );
		add_meta_box( 'sm-legal-texts', __( 'Text of the page', 'sm-core' ), array( __CLASS__, 'texts' ), 'page', 'normal', 'high' );
	}

	public static function texts( $post ) {
		$key   = Legal::key_of( $post->ID );
		$texts = Legal::texts( $key );
		$names = array( 'fr' => 'Français', 'ar' => 'العربية', 'en' => 'English' );
		wp_nonce_field( 'sm_legal_texts', 'sm_legal_nonce' );
		echo '<style>.sml-lang{margin:0 0 16px}.sml-lang h3{margin:12px 0 6px}.sml-lang textarea{width:100%;min-height:420px;font-family:Consolas,Monaco,monospace;font-size:13px;line-height:1.5}.sml-help{color:#646970}</style>';
		echo '<p class="sml-help">' . esc_html__( 'Markdown: ## Article (a section of the table of contents), **bold**, *italic*, - item of a list, | a | b | for a table, [text](page:contact) for a page of the site. Words replaced from SM → Legal information: {card} {company} {contact_email} {court} {dpo_line} {dpo_email} {anpdp} {transfer} {host}. An empty language shows the French text.', 'sm-core' ) . '</p>';
		foreach ( Legal::LANGS as $l ) {
			echo '<div class="sml-lang"><h3>' . esc_html( $names[ $l ] ) . '</h3><textarea name="sml_text[' . esc_attr( $l ) . ']" dir="' . ( 'ar' === $l ? 'rtl' : 'ltr' ) . '">' . esc_textarea( $texts[ $l ] ) . '</textarea></div>';
		}
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ) . '">' . esc_html__( 'Legal information (company, data protection, version)', 'sm-core' ) . '</a></p>';
	}

	public static function save_texts( $post_id, $post ) {
		if ( ! isset( $_POST['sm_legal_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['sm_legal_nonce'] ) ), 'sm_legal_texts' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) || ! Legal::key_of( $post_id ) ) {
			return;
		}
		$in = isset( $_POST['sml_text'] ) && is_array( $_POST['sml_text'] ) ? wp_unslash( $_POST['sml_text'] ) : array(); // phpcs:ignore WordPress.Security.ValidationSanitization.InputNotSanitized -- sanitized in save_texts().
		Legal::save_texts( $post_id, $in );
	}
}
