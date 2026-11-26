<?php
/**
 * Editing of a guide in the administration (SM → Guides): its texts in French,
 * Arabic and English, its topic, its short text for the list, and the pages of the
 * site it links to. The body is written in simple Markdown (help under the field).
 *
 * @package SM\Guides
 */

namespace SM\Guides\Admin;

use SM\Guides\Guides;
use SM\Guides\Markdown;
use SM\Guides\Pages;

defined( 'ABSPATH' ) || exit;

final class Editor {

	public static function init() {
		add_action( 'add_meta_boxes_' . Guides::TYPE, array( __CLASS__, 'boxes' ) );
		add_action( 'save_post_' . Guides::TYPE, array( __CLASS__, 'save' ), 10, 2 );
		add_filter( 'manage_' . Guides::TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . Guides::TYPE . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_filter( 'enter_title_here', static fn( $t, $post ) => Guides::TYPE === $post->post_type ? __( 'Title of the guide in French', 'sm-guides' ) : $t, 10, 2 );
	}

	public static function tool_labels() {
		return array(
			'reuse'     => __( 'Steel reuse', 'sm-guides' ),
			'products'  => __( 'Steel products', 'sm-guides' ),
			'buildings' => __( 'Steel buildings', 'sm-guides' ),
			'weight'    => __( 'Weight calculator', 'sm-guides' ),
			'catalogue' => __( 'Catalogue of sections', 'sm-guides' ),
			'cutting'   => __( 'Cutting optimiser', 'sm-guides' ),
			'engineers' => __( 'Engineering', 'sm-guides' ),
			'services'  => __( 'Fabrication and erection services', 'sm-guides' ),
			'rfq'       => __( 'Requests for quotes', 'sm-guides' ),
		);
	}

	public static function boxes() {
		add_meta_box( 'sm-guide-texts', __( 'Texts of the guide', 'sm-guides' ), array( __CLASS__, 'texts' ), Guides::TYPE, 'normal', 'high' );
		add_meta_box( 'sm-guide-tools', __( 'Pages of the site linked', 'sm-guides' ), array( __CLASS__, 'tools' ), Guides::TYPE, 'side' );
	}

	public static function texts( $post ) {
		$f     = Guides::fields( $post );
		$names = array( 'fr' => 'Français', 'ar' => 'العربية', 'en' => 'English' );
		wp_nonce_field( 'sm_guide_save', 'sm_guide_nonce' );
		echo '<style>.smg-lang{border:1px solid #dcdcde;padding:12px 16px;margin:0 0 16px;background:#fff}.smg-lang h3{margin:0 0 8px}.smg-lang label{display:block;font-weight:600;margin:10px 0 4px}.smg-lang input[type=text],.smg-lang textarea{width:100%}.smg-lang textarea.smg-body{min-height:420px;font-family:Menlo,Consolas,monospace;font-size:13px}.smg-help{color:#646970;font-size:12px;margin-top:4px}</style>';
		foreach ( Guides::LANGS as $l ) {
			$dir = 'ar' === $l ? 'rtl' : 'ltr';
			echo '<fieldset class="smg-lang"><h3>' . esc_html( $names[ $l ] ) . '</h3>';
			if ( 'fr' !== $l ) {
				/* translators: %s: language. */
				echo '<label for="smg-title-' . esc_attr( $l ) . '">' . esc_html( sprintf( __( 'Title (%s)', 'sm-guides' ), $names[ $l ] ) ) . '</label>';
				echo '<input type="text" id="smg-title-' . esc_attr( $l ) . '" dir="' . esc_attr( $dir ) . '" name="smg[title][' . esc_attr( $l ) . ']" value="' . esc_attr( $f['title'][ $l ] ) . '">';
			}
			echo '<label for="smg-topic-' . esc_attr( $l ) . '">' . esc_html__( 'Topic (short label on the card)', 'sm-guides' ) . '</label>';
			echo '<input type="text" id="smg-topic-' . esc_attr( $l ) . '" dir="' . esc_attr( $dir ) . '" name="smg[topic][' . esc_attr( $l ) . ']" value="' . esc_attr( $f['topic'][ $l ] ) . '">';
			echo '<label for="smg-excerpt-' . esc_attr( $l ) . '">' . esc_html__( 'Short text of the card (one line)', 'sm-guides' ) . '</label>';
			echo '<input type="text" id="smg-excerpt-' . esc_attr( $l ) . '" dir="' . esc_attr( $dir ) . '" name="smg[excerpt][' . esc_attr( $l ) . ']" value="' . esc_attr( $f['excerpt'][ $l ] ) . '">';
			echo '<label for="smg-summary-' . esc_attr( $l ) . '">' . esc_html__( 'Summary (under the title, and description for the search engines)', 'sm-guides' ) . '</label>';
			echo '<textarea id="smg-summary-' . esc_attr( $l ) . '" rows="3" dir="' . esc_attr( $dir ) . '" name="smg[summary][' . esc_attr( $l ) . ']">' . esc_textarea( $f['summary'][ $l ] ) . '</textarea>';
			echo '<label for="smg-body-' . esc_attr( $l ) . '">' . esc_html__( 'Text of the guide', 'sm-guides' ) . '</label>';
			echo '<textarea class="smg-body" id="smg-body-' . esc_attr( $l ) . '" dir="' . esc_attr( $dir ) . '" name="smg[body][' . esc_attr( $l ) . ']">' . esc_textarea( $f['body'][ $l ] ) . '</textarea>';
			echo '<p class="smg-help">' . esc_html__( '## Title of a section (it goes in the table of contents) · **bold** · - item of a list · 1. step · - [ ] point of the checklist · | column | column | for a table · [text](https://…) for a link. A guide is shown in a language only when its title and its text are written in that language; otherwise the French text is shown there.', 'sm-guides' ) . '</p>';
			if ( 'publish' === $post->post_status && class_exists( '\SM\Core\I18n\Language' ) ) {
				echo '<p><a href="' . esc_url( \SM\Core\I18n\Language::url_for( Pages::url( $post->post_name ), $l ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'See the guide on the site', 'sm-guides' ) . '</a></p>';
			}
			echo '</fieldset>';
		}
	}

	public static function tools( $post ) {
		$f = Guides::fields( $post );
		echo '<p class="smg-help">' . esc_html__( 'Shown beside the guide; the first one ticked is the main button. The guide is also proposed on these pages.', 'sm-guides' ) . '</p>';
		$order = array_merge( $f['tools'], array_diff( Guides::TOOLS, $f['tools'] ) );
		foreach ( $order as $k ) {
			$labels = self::tool_labels();
			echo '<label style="display:block;margin:4px 0"><input type="checkbox" name="smg[tools][]" value="' . esc_attr( $k ) . '"' . checked( in_array( $k, $f['tools'], true ), true, false ) . '> ' . esc_html( $labels[ $k ] ?? $k ) . '</label>';
		}
	}

	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['sm_guide_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['sm_guide_nonce'] ) ), 'sm_guide_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$in = isset( $_POST['smg'] ) && is_array( $_POST['smg'] ) ? wp_unslash( $_POST['smg'] ) : array(); // phpcs:ignore WordPress.Security.ValidationSanitization.InputNotSanitized -- sanitized in save_fields().
		Guides::save_fields( $post_id, $in );
	}

	public static function columns( $cols ) {
		$out = array();
		foreach ( $cols as $k => $v ) {
			$out[ $k ] = $v;
			if ( 'title' === $k ) {
				$out['smg_langs']    = __( 'Languages', 'sm-guides' );
				$out['smg_sections'] = __( 'Sections', 'sm-guides' );
				$out['smg_order']    = __( 'Order', 'sm-guides' );
			}
		}
		return $out;
	}

	public static function column( $col, $post_id ) {
		if ( 'smg_langs' === $col ) {
			echo esc_html( strtoupper( implode( ' · ', Guides::languages( $post_id ) ) ) );
		} elseif ( 'smg_sections' === $col ) {
			echo (int) Markdown::sections( Guides::fields( $post_id )['body']['fr'] );
		} elseif ( 'smg_order' === $col ) {
			echo (int) get_post_field( 'menu_order', $post_id );
		}
	}
}
