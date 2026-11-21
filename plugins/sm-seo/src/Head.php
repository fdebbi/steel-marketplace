<?php
/**
 * Tags of the <head>: title, description, canonical address, robots, link previews
 * (Open Graph, X), verification codes and structured data. The theme keeps only the
 * hreflang links of the languages.
 *
 * @package SM\Seo
 */

namespace SM\Seo;

defined( 'ABSPATH' ) || exit;

final class Head {

	const LOCALES = array(
		'fr' => 'fr_FR',
		'ar' => 'ar_DZ',
		'en' => 'en_US',
	);

	public static function init() {
		if ( is_admin() ) {
			return;
		}
		remove_action( 'wp_head', 'rel_canonical' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
		add_filter( 'document_title_separator', static fn() => '|' );
		add_filter( 'document_title_parts', array( __CLASS__, 'title_parts' ), 20 );
		add_filter( 'wp_robots', array( __CLASS__, 'robots' ), 20 );
		add_action( 'wp_head', array( __CLASS__, 'output' ), 3 );
	}

	public static function title_parts( $parts ) {
		$m = Context::get();
		if ( '' === $m['title'] ) {
			return $parts;
		}
		if ( ! empty( $m['title_full'] ) ) {
			return array( 'title' => $m['title'] );
		}
		unset( $parts['tagline'], $parts['page'] );
		$parts['title'] = $m['title'];
		$parts['site']  = get_bloginfo( 'name' );
		return $parts;
	}

	public static function robots( $robots ) {
		$m = Context::get();
		if ( ! $m['index'] ) {
			unset( $robots['max-image-preview'], $robots['index'] );
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}
		return $robots;
	}

	/** Canonical address in the current language. */
	public static function canonical() {
		$m = Context::get();
		return '' === $m['canonical'] ? '' : Text::in_lang( $m['canonical'], Text::lang() );
	}

	public static function output() {
		$m    = Context::get();
		$lang = Text::lang();
		$out  = array();
		$canonical = self::canonical();

		if ( '' !== $m['description'] ) {
			$out[] = sprintf( '<meta name="description" content="%s">', esc_attr( $m['description'] ) );
		}
		if ( '' !== $canonical ) {
			$out[] = sprintf( '<link rel="canonical" href="%s">', esc_url( $canonical ) );
		} elseif ( is_singular() ) {
			rel_canonical();
		}
		$s = Settings::all();
		if ( '' !== $s['google'] ) {
			$out[] = sprintf( '<meta name="google-site-verification" content="%s">', esc_attr( $s['google'] ) );
		}
		if ( '' !== $s['bing'] ) {
			$out[] = sprintf( '<meta name="msvalidate.01" content="%s">', esc_attr( $s['bing'] ) );
		}

		// Link previews (WhatsApp, Facebook, LinkedIn, X).
		$title = '' !== $m['title'] ? $m['title'] : wp_get_document_title();
		$og    = array(
			'og:site_name'   => get_bloginfo( 'name' ),
			'og:type'        => $m['og_type'],
			'og:title'       => $title,
			'og:description' => $m['description'],
			'og:url'         => $canonical,
			'og:locale'      => self::LOCALES[ $lang ] ?? '',
			'og:image'       => $m['image'],
		);
		foreach ( $og as $prop => $val ) {
			if ( '' !== (string) $val ) {
				$out[] = sprintf( '<meta property="%s" content="%s">', esc_attr( $prop ), 'og:url' === $prop || 'og:image' === $prop ? esc_url( $val ) : esc_attr( $val ) );
			}
		}
		foreach ( Text::langs() as $code ) {
			if ( $code !== $lang && isset( self::LOCALES[ $code ] ) ) {
				$out[] = sprintf( '<meta property="og:locale:alternate" content="%s">', esc_attr( self::LOCALES[ $code ] ) );
			}
		}
		$out[] = sprintf( '<meta name="twitter:card" content="%s">', $m['image'] ? 'summary_large_image' : 'summary' );

		// Structured data.
		$graph = $m['schema'];
		if ( $m['crumbs'] ) {
			$graph[] = Schema::breadcrumb( $m['crumbs'] );
		}
		if ( $graph ) {
			$json  = wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => array_values( $graph ) ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG );
			$out[] = '<script type="application/ld+json">' . $json . '</script>';
		}
		echo "\n<!-- SM SEO -->\n" . implode( "\n", $out ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above.
	}
}
