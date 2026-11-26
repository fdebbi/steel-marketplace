<?php
/**
 * Editing of a question in the administration (SM → Frequently asked questions):
 * the question and its answer in French, Arabic and English, its theme, its order
 * (Attributes → Order) and the pages of the site that show it at the bottom.
 *
 * @package SM\Guides
 */

namespace SM\Guides\Admin;

use SM\Guides\Faq;

defined( 'ABSPATH' ) || exit;

final class FaqEditor {

	public static function init() {
		add_action( 'add_meta_boxes_' . Faq::TYPE, array( __CLASS__, 'boxes' ) );
		add_action( 'save_post_' . Faq::TYPE, array( __CLASS__, 'save' ), 10, 2 );
		add_filter( 'manage_' . Faq::TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . Faq::TYPE . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_action( 'pre_get_posts', array( __CLASS__, 'order' ) );
		add_filter( 'enter_title_here', static fn( $t, $post ) => Faq::TYPE === $post->post_type ? __( 'Question in French', 'sm-guides' ) : $t, 10, 2 );
	}

	public static function page_labels() {
		return array(
			'reuse'     => __( 'Steel reuse', 'sm-guides' ),
			'buildings' => __( 'Steel buildings', 'sm-guides' ),
			'engineers' => __( 'Engineering', 'sm-guides' ),
			'rfq'       => __( 'Requests for quotes', 'sm-guides' ),
		);
	}

	private static function group_name( $key ) {
		$g    = Faq::groups();
		$lang = 0 === strpos( get_user_locale(), 'ar' ) ? 'ar' : ( 0 === strpos( get_user_locale(), 'en' ) ? 'en' : 'fr' );
		return $g[ $key ][ $lang ] ?? $key;
	}

	public static function boxes() {
		add_meta_box( 'sm-faq-texts', __( 'Question and answer', 'sm-guides' ), array( __CLASS__, 'texts' ), Faq::TYPE, 'normal', 'high' );
		add_meta_box( 'sm-faq-place', __( 'Theme and pages', 'sm-guides' ), array( __CLASS__, 'place' ), Faq::TYPE, 'side' );
	}

	public static function texts( $post ) {
		$f     = Faq::fields( $post );
		$names = array( 'fr' => 'Français', 'ar' => 'العربية', 'en' => 'English' );
		wp_nonce_field( 'sm_faq_save', 'sm_faq_nonce' );
		echo '<style>.smf-lang{border:1px solid #dcdcde;padding:12px 16px;margin:0 0 16px;background:#fff}.smf-lang h3{margin:0 0 8px}.smf-lang label{display:block;font-weight:600;margin:10px 0 4px}.smf-lang input[type=text],.smf-lang textarea{width:100%}.smf-lang textarea{min-height:140px}.smf-help{color:#646970;font-size:12px;margin-top:4px}</style>';
		foreach ( Faq::LANGS as $l ) {
			$dir = 'ar' === $l ? 'rtl' : 'ltr';
			echo '<fieldset class="smf-lang"><h3>' . esc_html( $names[ $l ] ) . '</h3>';
			if ( 'fr' !== $l ) {
				/* translators: %s: language. */
				echo '<label for="smf-q-' . esc_attr( $l ) . '">' . esc_html( sprintf( __( 'Question (%s)', 'sm-guides' ), $names[ $l ] ) ) . '</label>';
				echo '<input type="text" id="smf-q-' . esc_attr( $l ) . '" dir="' . esc_attr( $dir ) . '" name="smf[question][' . esc_attr( $l ) . ']" value="' . esc_attr( $f['question'][ $l ] ) . '">';
			}
			/* translators: %s: language. */
			echo '<label for="smf-a-' . esc_attr( $l ) . '">' . esc_html( sprintf( __( 'Answer (%s)', 'sm-guides' ), $names[ $l ] ) ) . '</label>';
			echo '<textarea id="smf-a-' . esc_attr( $l ) . '" dir="' . esc_attr( $dir ) . '" name="smf[answer][' . esc_attr( $l ) . ']">' . esc_textarea( $f['answer'][ $l ] ) . '</textarea>';
			echo '</fieldset>';
		}
		echo '<p class="smf-help">' . esc_html__( 'Short answers, on how the site works only: no market price, no statistics. **bold** · *italic* · - item of a list · [text](https://…) for a link · [text](guide:name-of-the-guide) for a guide · [text](page:contact) for a page of the site (reuse, buildings, engineers, rfq, catalogue, weight, cutting, contact, mission-request, engineer-apply…). A question is shown in a language only when its question and its answer are written in that language; otherwise the French text is shown there.', 'sm-guides' ) . '</p>';
		if ( 'publish' === $post->post_status && '' !== Faq::url() ) {
			echo '<p><a href="' . esc_url( Faq::url() . '#q-' . $post->post_name ) . '" target="_blank" rel="noopener">' . esc_html__( 'See the question on the site', 'sm-guides' ) . '</a></p>';
		}
	}

	public static function place( $post ) {
		$f = Faq::fields( $post );
		echo '<p><label for="smf-group"><strong>' . esc_html__( 'Theme', 'sm-guides' ) . '</strong></label><br><select id="smf-group" name="smf[group]" style="width:100%">';
		foreach ( array_keys( Faq::groups() ) as $k ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( $f['group'], $k, false ) . '>' . esc_html( self::group_name( $k ) ) . '</option>';
		}
		echo '</select></p>';
		echo '<p><strong>' . esc_html__( 'Also shown at the bottom of these pages', 'sm-guides' ) . '</strong></p>';
		foreach ( self::page_labels() as $k => $label ) {
			echo '<label style="display:block;margin:4px 0"><input type="checkbox" name="smf[pages][]" value="' . esc_attr( $k ) . '"' . checked( in_array( $k, $f['pages'], true ), true, false ) . '> ' . esc_html( $label ) . '</label>';
		}
		/* translators: %d: number of questions. */
		echo '<p class="smf-help" style="color:#646970;font-size:12px">' . esc_html( sprintf( __( 'At most %d questions per page, in the order of the questions (Attributes → Order).', 'sm-guides' ), Faq::PER_PAGE ) ) . '</p>';
	}

	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['sm_faq_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['sm_faq_nonce'] ) ), 'sm_faq_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$in = isset( $_POST['smf'] ) && is_array( $_POST['smf'] ) ? wp_unslash( $_POST['smf'] ) : array(); // phpcs:ignore WordPress.Security.ValidationSanitization.InputNotSanitized -- sanitized in save_fields().
		Faq::save_fields( $post_id, $in );
	}

	/** List of the questions in their order on the page. */
	public static function order( $q ) {
		if ( is_admin() && $q->is_main_query() && Faq::TYPE === $q->get( 'post_type' ) && ! $q->get( 'orderby' ) ) {
			$q->set( 'orderby', array( 'menu_order' => 'ASC', 'ID' => 'ASC' ) );
		}
	}

	public static function columns( $cols ) {
		$out = array();
		foreach ( $cols as $k => $v ) {
			$out[ $k ] = $v;
			if ( 'title' === $k ) {
				$out['smf_group'] = __( 'Theme', 'sm-guides' );
				$out['smf_langs'] = __( 'Languages', 'sm-guides' );
				$out['smf_pages'] = __( 'Pages', 'sm-guides' );
				$out['smf_order'] = __( 'Order', 'sm-guides' );
			}
		}
		return $out;
	}

	public static function column( $col, $post_id ) {
		$f = Faq::fields( $post_id );
		if ( 'smf_group' === $col ) {
			echo esc_html( self::group_name( $f['group'] ) );
		} elseif ( 'smf_langs' === $col ) {
			echo esc_html( strtoupper( implode( ' · ', Faq::languages( $post_id ) ) ) );
		} elseif ( 'smf_pages' === $col ) {
			$labels = self::page_labels();
			echo esc_html( implode( ', ', array_map( static fn( $k ) => $labels[ $k ] ?? $k, $f['pages'] ) ) );
		} elseif ( 'smf_order' === $col ) {
			echo (int) get_post_field( 'menu_order', $post_id );
		}
	}
}
