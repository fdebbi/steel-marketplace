<?php
/**
 * The guides: a private post type (edited in the administration, shown by the theme
 * at /guides/{name}/), with their texts in French, Arabic and English.
 *
 * Meta `_sm_guide`: title {ar, en} (the French title is the title of the post),
 * excerpt, summary, topic and body {fr, ar, en} (body in Markdown), tools (keys of
 * the pages of the site the guide links to, the first one is the main button).
 *
 * @package SM\Guides
 */

namespace SM\Guides;

defined( 'ABSPATH' ) || exit;

final class Guides {

	const TYPE  = 'sm_guide';
	const META  = '_sm_guide';
	const LANGS = array( 'fr', 'ar', 'en' );

	/** Pages of the site a guide can link to (keys of sm_page_url). */
	const TOOLS = array( 'reuse', 'products', 'buildings', 'weight', 'catalogue', 'cutting', 'engineers', 'services', 'rfq' );

	public static function register_type() {
		register_post_type(
			self::TYPE,
			array(
				'labels'          => array(
					'name'          => __( 'Guides', 'sm-guides' ),
					'singular_name' => __( 'Guide', 'sm-guides' ),
					'add_new_item'  => __( 'Add a guide', 'sm-guides' ),
					'edit_item'     => __( 'Edit the guide', 'sm-guides' ),
					'all_items'     => __( 'Guides', 'sm-guides' ),
					'search_items'  => __( 'Search the guides', 'sm-guides' ),
					'not_found'     => __( 'No guide.', 'sm-guides' ),
				),
				'public'          => false, // The theme shows them at /guides/{name}/ (Pages).
				'show_ui'         => true,
				'show_in_menu'    => defined( 'SM_CORE_VERSION' ) ? 'sm-core' : true,
				'show_in_rest'    => false,
				'capability_type' => 'page',
				'map_meta_cap'    => true,
				'hierarchical'    => false,
				'supports'        => array( 'title', 'page-attributes' ),
				'rewrite'         => false,
				'query_var'       => false,
			)
		);
	}

	/** Stored fields of a guide, completed with empty values. */
	public static function fields( $post ) {
		$post = get_post( $post );
		$raw  = $post ? (array) get_post_meta( $post->ID, self::META, true ) : array();
		$out  = array( 'tools' => array_values( array_intersect( (array) ( $raw['tools'] ?? array() ), self::TOOLS ) ) );
		foreach ( array( 'title', 'excerpt', 'summary', 'topic', 'body' ) as $k ) {
			foreach ( self::LANGS as $l ) {
				$out[ $k ][ $l ] = (string) ( $raw[ $k ][ $l ] ?? '' );
			}
		}
		if ( $post ) {
			$out['title']['fr'] = (string) $post->post_title;
		}
		return $out;
	}

	public static function save_fields( $post_id, array $f ) {
		$clean = array( 'tools' => array_values( array_intersect( (array) ( $f['tools'] ?? array() ), self::TOOLS ) ) );
		foreach ( array( 'title', 'excerpt', 'summary', 'topic', 'body' ) as $k ) {
			foreach ( self::LANGS as $l ) {
				$v = (string) ( $f[ $k ][ $l ] ?? '' );
				$clean[ $k ][ $l ] = 'body' === $k || 'summary' === $k ? trim( sanitize_textarea_field( $v ) ) : trim( sanitize_text_field( $v ) );
			}
		}
		$clean['title']['fr'] = ''; // The title of the post.
		update_post_meta( $post_id, self::META, $clean );
	}

	/** Published guides, in their order. */
	public static function published() {
		return get_posts(
			array(
				'post_type'      => self::TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ),
				'no_found_rows'  => true,
			)
		);
	}

	public static function by_slug( $slug ) {
		$slug = sanitize_title( (string) $slug );
		if ( '' === $slug ) {
			return null;
		}
		foreach ( self::published() as $p ) {
			if ( $p->post_name === $slug ) {
				return $p;
			}
		}
		return null;
	}

	/** Languages in which a guide is written (its title and its body). */
	public static function languages( $post ) {
		$f   = self::fields( $post );
		$out = array();
		foreach ( self::LANGS as $l ) {
			if ( '' !== $f['title'][ $l ] && '' !== $f['body'][ $l ] ) {
				$out[] = $l;
			}
		}
		return $out;
	}

	/** Text in a language; the French one when it is not written in that language. */
	public static function pick( array $field, $lang ) {
		return '' !== ( $field[ $lang ] ?? '' ) ? $field[ $lang ] : ( $field['fr'] ?? '' );
	}
}
