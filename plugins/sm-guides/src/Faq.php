<?php
/**
 * Frequently asked questions (0.2.0): a private post type (SM → Frequently asked
 * questions), shown by the theme on the page /faq/ and, three or four at a time,
 * at the bottom of the pages of the site they are linked to.
 *
 * The French question is the title of the post. Meta `_sm_faq`: question {ar, en},
 * answer {fr, ar, en} in simple Markdown, group (a key of GROUPS), pages (keys of
 * PAGES). In an answer, [text](guide:name) links to a guide and [text](page:key) to
 * a page of the site, in the language of the page.
 *
 * Only how the site works: no market price, no statistics.
 *
 * @package SM\Guides
 */

namespace SM\Guides;

defined( 'ABSPATH' ) || exit;

final class Faq {

	const TYPE  = 'sm_faq';
	const META  = '_sm_faq';
	const LANGS = array( 'fr', 'ar', 'en' );

	/** Pages of the site that show their questions at the bottom (keys of sm_page_url). */
	const PAGES = array( 'reuse', 'buildings', 'engineers', 'rfq' );

	/** Most questions shown at the bottom of a page. */
	const PER_PAGE = 4;

	/** Themes of the questions, in their order on the page. */
	public static function groups() {
		return array(
			'account'     => array( 'fr' => 'Compte', 'ar' => 'الحساب', 'en' => 'Account' ),
			'publish'     => array( 'fr' => 'Publier une annonce', 'ar' => 'نشر إعلان', 'en' => 'Publishing a listing' ),
			'buy'         => array( 'fr' => 'Acheter et demander un devis', 'ar' => 'الشراء وطلب عرض السعر', 'en' => 'Buying and requesting a quote' ),
			'engineering' => array( 'fr' => 'Ingénierie', 'ar' => 'الهندسة', 'en' => 'Engineering' ),
			'tools'       => array( 'fr' => 'Outils', 'ar' => 'الأدوات', 'en' => 'Tools' ),
			'trust'       => array( 'fr' => 'Langues et confiance', 'ar' => 'اللغات والثقة', 'en' => 'Languages and trust' ),
		);
	}

	public static function init() {
		add_filter( 'sm_theme_data_faq', array( __CLASS__, 'data' ), 10, 2 );
	}

	public static function register_type() {
		register_post_type(
			self::TYPE,
			array(
				'labels'          => array(
					'name'          => __( 'Frequently asked questions', 'sm-guides' ),
					'singular_name' => __( 'Question', 'sm-guides' ),
					'menu_name'     => __( 'Frequently asked questions', 'sm-guides' ),
					'add_new_item'  => __( 'Add a question', 'sm-guides' ),
					'edit_item'     => __( 'Edit the question', 'sm-guides' ),
					'all_items'     => __( 'Frequently asked questions', 'sm-guides' ),
					'search_items'  => __( 'Search the questions', 'sm-guides' ),
					'not_found'     => __( 'No question.', 'sm-guides' ),
				),
				'public'          => false, // The theme shows them on the page /faq/.
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

	/** Stored fields of a question, completed with empty values. */
	public static function fields( $post ) {
		$post   = get_post( $post );
		$raw    = $post ? (array) get_post_meta( $post->ID, self::META, true ) : array();
		$groups = array_keys( self::groups() );
		$group  = (string) ( $raw['group'] ?? '' );
		$out    = array(
			'group' => in_array( $group, $groups, true ) ? $group : $groups[0],
			'pages' => array_values( array_intersect( (array) ( $raw['pages'] ?? array() ), self::PAGES ) ),
		);
		foreach ( array( 'question', 'answer' ) as $k ) {
			foreach ( self::LANGS as $l ) {
				$out[ $k ][ $l ] = (string) ( $raw[ $k ][ $l ] ?? '' );
			}
		}
		if ( $post ) {
			$out['question']['fr'] = (string) $post->post_title;
		}
		return $out;
	}

	public static function save_fields( $post_id, array $f ) {
		$group = (string) ( $f['group'] ?? '' );
		$clean = array(
			'group' => array_key_exists( $group, self::groups() ) ? $group : 'account',
			'pages' => array_values( array_intersect( (array) ( $f['pages'] ?? array() ), self::PAGES ) ),
		);
		foreach ( self::LANGS as $l ) {
			$clean['question'][ $l ] = 'fr' === $l ? '' : trim( sanitize_text_field( (string) ( $f['question'][ $l ] ?? '' ) ) ); // French: the title of the post.
			$clean['answer'][ $l ]   = trim( sanitize_textarea_field( (string) ( $f['answer'][ $l ] ?? '' ) ) );
		}
		update_post_meta( $post_id, self::META, $clean );
	}

	/** Published questions, in their order. */
	public static function published() {
		return get_posts(
			array(
				'post_type'      => self::TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'orderby'        => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ),
				'no_found_rows'  => true,
			)
		);
	}

	/** Languages in which a question is written (its question and its answer). */
	public static function languages( $post ) {
		$f   = self::fields( $post );
		$out = array();
		foreach ( self::LANGS as $l ) {
			if ( '' !== $f['question'][ $l ] && '' !== $f['answer'][ $l ] ) {
				$out[] = $l;
			}
		}
		return $out;
	}

	private static function lang() {
		return function_exists( 'sm_current_language' ) ? sm_current_language() : 'fr';
	}

	public static function url() {
		$map = (array) get_option( 'sm_theme_pages', array() );
		$id  = (int) ( $map['faq'] ?? 0 );
		return $id && 'publish' === get_post_status( $id ) ? (string) get_permalink( $id ) : '';
	}

	/**
	 * sm_theme_data( 'faq' ): the page, with its themes and their questions.
	 * sm_theme_data( 'faq', { for: key } ): the questions shown at the bottom of a page.
	 */
	public static function data( $r, $args ) {
		if ( null !== $r ) {
			return $r;
		}
		$lang  = self::lang();
		$posts = self::published();
		if ( ! empty( $args['for'] ) ) {
			$key   = (string) $args['for'];
			$items = array();
			foreach ( $posts as $p ) {
				if ( count( $items ) < self::PER_PAGE && in_array( $key, self::fields( $p )['pages'], true ) ) {
					$items[] = self::item( $p, $lang );
				}
			}
			return array( 'url' => self::url(), 'items' => $items );
		}
		$groups  = array();
		$updated = '';
		foreach ( self::groups() as $key => $names ) {
			$groups[ $key ] = array( 'key' => $key, 'id' => 'faq-' . $key, 'title' => $names[ $lang ] ?? $names['fr'], 'items' => array() );
		}
		foreach ( $posts as $p ) {
			$groups[ self::fields( $p )['group'] ]['items'][] = self::item( $p, $lang );
			$updated = max( $updated, (string) $p->post_modified_gmt );
		}
		$groups = array_values( array_filter( $groups, static fn( $g ) => (bool) $g['items'] ) );
		return array(
			'kind'    => 'faq',
			'url'     => self::url(),
			'groups'  => $groups,
			'count'   => count( $posts ),
			'updated' => $updated,
		);
	}

	/** A question in a language (in French when it is not written in that language). */
	public static function item( $post, $lang ) {
		$f        = self::fields( $post );
		$fallback = ! in_array( $lang, self::languages( $post ), true );
		$show     = $fallback ? 'fr' : $lang;
		$html     = Markdown::render( self::links( $f['answer'][ $show ] ) )['html'];
		return array(
			'id'       => 'q-' . $post->post_name,
			'slug'     => $post->post_name,
			'question' => $f['question'][ $show ],
			'q_html'   => self::isolate( esc_html( $f['question'][ $show ] ), $show ),
			'answer'   => self::isolate( $html, $show ),
			'text'     => trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( str_replace( '</li>', '</li> ', $html ) ), ENT_QUOTES, 'UTF-8' ) ) ),
			'fallback' => $fallback,
			'lang'     => $show,
		);
	}

	/** [text](guide:name) and [text](page:key) turned into addresses of the site; unknown: the text alone. */
	public static function links( $text ) {
		return (string) preg_replace_callback(
			'/\[([^\]]+)\]\((guide|page):([a-z0-9-]+)\)/u',
			static function ( $m ) {
				$url = 'guide' === $m[2] ? ( Guides::by_slug( $m[3] ) ? Pages::url( $m[3] ) : '' ) : self::page_link( $m[3] );
				return '' === $url ? $m[1] : '[' . $m[1] . '](' . $url . ')';
			},
			(string) $text
		);
	}

	private static function page_link( $key ) {
		$map = (array) get_option( 'sm_theme_pages', array() );
		$url = (string) apply_filters( 'sm_page_url', '', $key, array() );
		if ( '' === $url || ( empty( $map[ $key ] ) && untrailingslashit( $url ) === untrailingslashit( home_url() ) ) ) { // Unknown key: some filters give the home page.
			$url = ! empty( $map[ $key ] ) && 'publish' === get_post_status( (int) $map[ $key ] ) ? (string) get_permalink( (int) $map[ $key ] ) : '';
		}
		return $url;
	}

	/**
	 * Arabic: Latin words (DA/kg, IPE 200, PDF, ReuSteel, an address) inside <bdi>, so
	 * the punctuation around them stays in its place in the right-to-left text.
	 */
	public static function isolate( $html, $lang ) {
		if ( 'ar' !== $lang ) {
			return $html;
		}
		$parts = preg_split( '/(<[^>]+>)/u', (string) $html, -1, PREG_SPLIT_DELIM_CAPTURE );
		foreach ( $parts as $i => $part ) {
			if ( '' === $part || '<' === $part[0] ) {
				continue;
			}
			// A Latin word (not an entity like &amp;), with a number after it (IPE 200).
			$parts[ $i ] = preg_replace( '/(?<![&#\w])[A-Za-z](?:[A-Za-z0-9@.\/_-]*[A-Za-z0-9])?(?: [0-9]+\b)?/u', '<bdi>$0</bdi>', $part );
		}
		return implode( '', $parts );
	}
}
