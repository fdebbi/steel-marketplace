<?php
/**
 * Public pages of the guides:
 *
 *   /guides/              the list of the guides
 *   /guides/{name}/       a guide, with its table of contents and its tools
 *
 * The page « guides » belongs to the theme (sm_theme_pages); the name after it is
 * read here, the same way as the catalogue of sections. The same addresses exist
 * under /ar/ and /en/ (SM Core). A guide not written in a language is shown in
 * French there, and marked `fallback` (kept out of the search engines by SM SEO).
 *
 * @package SM\Guides
 */

namespace SM\Guides;

defined( 'ABSPATH' ) || exit;

final class Pages {

	const QUERY_VAR = 'sm_guide';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'rewrite' ), 20 );
		add_filter( 'query_vars', static fn( $v ) => array_merge( $v, array( self::QUERY_VAR ) ) );
		add_filter( 'sm_page_url', array( __CLASS__, 'page_url' ), 20, 3 );
		add_filter( 'sm_theme_data_guides', array( __CLASS__, 'data' ), 10, 2 );
	}

	public static function page_id() {
		$map = (array) get_option( 'sm_theme_pages', array() );
		return (int) ( $map['guides'] ?? 0 );
	}

	public static function rewrite() {
		$id = self::page_id();
		if ( ! $id ) {
			return;
		}
		$path = trim( (string) get_page_uri( $id ), '/' );
		if ( '' === $path ) {
			return;
		}
		add_rewrite_rule( '^' . preg_quote( $path, '#' ) . '/([a-z0-9-]+)/?$', 'index.php?page_id=' . $id . '&' . self::QUERY_VAR . '=$matches[1]', 'top' );
		$sig = $path . '|' . $id . '|' . SM_GUIDES_VERSION;
		if ( get_option( 'sm_guides_rules' ) !== $sig ) {
			update_option( 'sm_guides_rules', $sig, false );
			flush_rewrite_rules( false );
		}
	}

	/** sm_page_url( 'guides', [ 'slug' => 'acheter-acier-reemploi' ] ). */
	public static function page_url( $url, $key, $args ) {
		if ( 'guides' !== $key ) {
			return $url;
		}
		$id = self::page_id();
		if ( ! $id ) {
			return $url;
		}
		$base = get_permalink( $id );
		$slug = sanitize_title( (string) ( $args['slug'] ?? '' ) );
		unset( $args['slug'] );
		if ( '' !== $slug ) {
			$base = get_option( 'permalink_structure' ) ? trailingslashit( $base ) . $slug . '/' : add_query_arg( self::QUERY_VAR, $slug, $base );
		}
		return $args ? add_query_arg( $args, $base ) : $base;
	}

	public static function url( $slug = '' ) {
		return self::page_url( '', 'guides', array( 'slug' => $slug ) );
	}

	private static function lang() {
		return function_exists( 'sm_current_language' ) ? sm_current_language() : 'fr';
	}

	/**
	 * sm_theme_data( 'guides', { slug } ): the list ('' ), a guide, or null when the
	 * name is unknown. { for: key } gives the guides that link to a page of the site.
	 */
	public static function data( $r, $args ) {
		if ( null !== $r ) {
			return $r;
		}
		if ( ! empty( $args['for'] ) ) {
			return self::related( (string) $args['for'] );
		}
		$slug = sanitize_title( (string) ( $args['slug'] ?? '' ) );
		if ( '' === $slug ) {
			return self::index();
		}
		$post = Guides::by_slug( $slug );
		return $post ? self::guide( $post ) : null;
	}

	/** Card of a guide in the current language. */
	public static function card( $post ) {
		$lang = self::lang();
		$f    = Guides::fields( $post );
		$body = Guides::pick( $f['body'], $lang );
		return array(
			'slug'     => $post->post_name,
			'url'      => self::url( $post->post_name ),
			'title'    => Guides::pick( $f['title'], $lang ),
			'excerpt'  => Guides::pick( $f['excerpt'], $lang ),
			'topic'    => Guides::pick( $f['topic'], $lang ),
			'sections' => Markdown::sections( $body ),
			'tools'    => self::tools( $f['tools'] ),
		);
	}

	public static function index() {
		return array(
			'kind'  => 'index',
			'url'   => self::url(),
			'items' => array_map( array( __CLASS__, 'card' ), Guides::published() ),
		);
	}

	public static function guide( $post ) {
		$lang   = self::lang();
		$f      = Guides::fields( $post );
		$langs  = Guides::languages( $post );
		$body   = Guides::pick( $f['body'], $lang );
		$md     = Markdown::render( $body );
		$others = array();
		foreach ( Guides::published() as $p ) {
			if ( $p->ID !== $post->ID ) {
				$others[] = self::card( $p );
			}
		}
		return array_merge(
			self::card( $post ),
			array(
				'kind'      => 'guide',
				'id'        => (int) $post->ID,
				'summary'   => Guides::pick( $f['summary'], $lang ),
				'html'      => $md['html'],
				'toc'       => $md['toc'],
				'languages' => $langs,
				'fallback'  => ! in_array( $lang, $langs, true ),
				'updated'   => (string) $post->post_modified_gmt,
				'published' => (string) $post->post_date_gmt,
				'others'    => array_slice( $others, 0, 3 ),
			)
		);
	}

	/** Guides that link to a page of the site (shown on that page). */
	public static function related( $key ) {
		$out = array();
		foreach ( Guides::published() as $p ) {
			$f = Guides::fields( $p );
			if ( in_array( $key, $f['tools'], true ) ) {
				$out[] = array(
					'title' => Guides::pick( $f['title'], self::lang() ),
					'url'   => self::url( $p->post_name ),
				);
			}
		}
		return $out;
	}

	private static function tools( array $keys ) {
		$out = array();
		foreach ( $keys as $k ) {
			$url = (string) apply_filters( 'sm_page_url', '', $k, array() );
			if ( '' === $url ) { // A page of the theme (sm_theme_pages); none: no link.
				$map = (array) get_option( 'sm_theme_pages', array() );
				$url = ! empty( $map[ $k ] ) && 'publish' === get_post_status( (int) $map[ $k ] ) ? (string) get_permalink( (int) $map[ $k ] ) : '';
			}
			if ( '' !== $url ) {
				$out[] = array( 'key' => $k, 'url' => $url );
			}
		}
		return $out;
	}
}
