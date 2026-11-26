<?php
/**
 * robots.txt and llms.txt.
 *
 * robots.txt, choice of the owner (SM → SEO):
 *  - 'search' (default): the AI search engines (ChatGPT search, Perplexity, Claude…)
 *    may read and quote the public pages; the bots that collect texts to train AI
 *    models are refused. Google and Bing search are not concerned (Google-Extended
 *    only covers the training of Gemini).
 *  - 'all': every bot reads the public pages; 'none': every AI bot is refused.
 * The account pages are closed to every bot. Ends with the address of the sitemap.
 * A real robots.txt file at the root of the site replaces this one.
 *
 * llms.txt: a short description of the site for the AI assistants (llmstxt.org).
 *
 * @package SM\Seo
 */

namespace SM\Seo;

defined( 'ABSPATH' ) || exit;

final class Robots {

	const AI_SEARCH = array( 'OAI-SearchBot', 'ChatGPT-User', 'PerplexityBot', 'Perplexity-User', 'Claude-SearchBot', 'Claude-User' );

	const AI_TRAINING = array( 'GPTBot', 'Google-Extended', 'ClaudeBot', 'anthropic-ai', 'CCBot', 'Applebot-Extended', 'meta-externalagent', 'Bytespider', 'cohere-training-data-crawler' );

	public static function init() {
		add_filter( 'robots_txt', array( __CLASS__, 'robots_txt' ), 5, 2 );
		add_action( 'parse_request', array( __CLASS__, 'llms_txt' ), 1 );
	}

	/** Paths closed to every bot: the account pages, in each language. */
	public static function closed_paths() {
		$paths = array();
		$base  = Text::url( 'account' );
		foreach ( Text::langs() as $code ) {
			$p = (string) wp_parse_url( Text::in_lang( $base, $code ), PHP_URL_PATH );
			if ( '' !== $p && '/' !== $p ) {
				$paths[] = trailingslashit( $p );
			}
		}
		return array_values( array_unique( $paths ) );
	}

	public static function rules( $mode = null ) {
		$mode   = $mode ?? (string) Settings::get( 'ai_bots' );
		$closed = self::closed_paths();
		$block  = static function ( array $agents, array $lines ) {
			$out = array();
			foreach ( $agents as $a ) {
				$out[] = 'User-agent: ' . $a;
			}
			return array_merge( $out, $lines, array( '' ) );
		};
		$public = array_merge( array( 'Disallow: /wp-admin/', 'Allow: /wp-admin/admin-ajax.php' ), array_map( static fn( $p ) => 'Disallow: ' . $p, $closed ) );

		$lines = $block( array( '*' ), $public );
		if ( 'search' === $mode ) {
			$lines[] = '# AI search engines: may read and quote the public pages.';
			$lines   = array_merge( $lines, $block( self::AI_SEARCH, array_merge( array( 'Allow: /' ), $public ) ) );
			$lines[] = '# Collection of texts to train AI models: refused.';
			$lines   = array_merge( $lines, $block( self::AI_TRAINING, array( 'Disallow: /' ) ) );
		} elseif ( 'none' === $mode ) {
			$lines[] = '# AI bots: refused.';
			$lines   = array_merge( $lines, $block( array_merge( self::AI_SEARCH, self::AI_TRAINING ), array( 'Disallow: /' ) ) );
		}
		return $lines;
	}

	public static function robots_txt( $output, $public ) {
		if ( ! $public ) {
			return $output; // « Discourage search engines » (Settings → Reading): left to WordPress.
		}
		$lines = self::rules();
		if ( function_exists( 'wp_sitemaps_get_server' ) && wp_sitemaps_get_server()->sitemaps_enabled() ) {
			$lines[] = 'Sitemap: ' . Text::in_lang( home_url( '/wp-sitemap.xml' ), 'fr' );
		}
		return implode( "\n", $lines ) . "\n";
	}

	public static function llms_txt( $wp ) {
		if ( 'llms.txt' !== trim( (string) $wp->request, '/' ) ) {
			return;
		}
		nocache_headers();
		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
		echo self::llms(); // phpcs:ignore WordPress.Security.EscapeOutput -- plain text.
		exit;
	}

	/** Content of llms.txt (English, the usual language of the format). */
	public static function llms() {
		$name  = get_bloginfo( 'name' );
		$link  = static fn( $label, $key, $text, $args = array() ) => '- [' . $label . '](' . Text::in_lang( Text::url( $key, $args ), 'fr' ) . '): ' . $text;
		$has   = static fn( $key ) => Text::page_id( $key ) > 0;
		$lines = array(
			'# ' . $name,
			'',
			'> Marketplace of steel in Algeria: new steel, steel reuse (offcuts, surplus, used steel, scrap) and steel buildings. Weights are computed from the standard catalogue of profiles; prices are the prices asked by the sellers, in Algerian dinars per kilogram (DA/kg) or per lot. The site is in French, Arabic and English.',
			'',
			'Notes:',
			'- Prices shown are asked by the sellers: they are not sale prices or market prices.',
			'- Buyers and sellers contact each other through the platform.',
			'- Engineering studies are delivered by the platform, carried out by verified partner engineers.',
			'',
			'## Markets',
		);
		$markets = array(
			'products'  => array( 'New steel', 'Profiles, tubes, plates and bars.' ),
			'reuse'     => array( 'Steel reuse', 'Offcuts, surplus, used steel and scrap, with the condition stated by the seller.' ),
			'buildings' => array( 'Steel buildings', 'Hangars and steel buildings, assembled or dismantled.' ),
		);
		foreach ( $markets as $key => $t ) {
			if ( $has( $key ) ) {
				$lines[] = $link( $t[0], $key, $t[1] );
			}
		}
		$lines[] = '';
		$lines[] = '## Services and tools';
		$tools   = array(
			'engineers' => array( 'Engineering services', 'Studies of steel structures, one quote and one contact: the platform.' ),
			'services'  => array( 'Services around steel', 'Services offered by companies of the marketplace.' ),
			'catalogue' => array( 'Catalogue of steel sections', 'Weight per metre and dimensions of every standard size (IPE, HEA, HEB, UPN, angles, tubes, bars), one page per size.' ),
			'weight'    => array( 'Steel weight calculator', 'Weight of beams, tubes, plates and bars from the standard catalogue.' ),
			'cutting'   => array( 'Cutting optimiser', 'Plan the cutting of steel bars to reduce the offcuts.' ),
			'guides'    => array( 'Guides', 'Practical guides: buying reused steel, used steel buildings, ordering bars to cut less, selling offcuts. Methods only, no market price.' ),
			'companies' => array( 'Companies directory', 'Companies that sell steel, by activity and by wilaya.' ),
			'rfq'       => array( 'Requests for quotes', 'Requests published by buyers of steel.' ),
			'pricing'   => array( 'Plans and prices', 'Plans for the sellers.' ),
			'contact'   => array( 'Contact', 'Contact the team.' ),
		);
		foreach ( $tools as $key => $t ) {
			if ( $has( $key ) ) {
				$lines[] = $link( $t[0], $key, $t[1] );
			}
		}
		$home    = home_url( '/' );
		$lines[] = '';
		$lines[] = '## Languages';
		$lines[] = '- [Français](' . Text::in_lang( $home, 'fr' ) . ')';
		if ( in_array( 'ar', Text::langs(), true ) ) {
			$lines[] = '- [العربية](' . Text::in_lang( $home, 'ar' ) . ')';
		}
		if ( in_array( 'en', Text::langs(), true ) ) {
			$lines[] = '- [English](' . Text::in_lang( $home, 'en' ) . ')';
		}
		$lines[] = '';
		$lines[] = '## Optional';
		$lines[] = '- [Sitemap](' . Text::in_lang( home_url( '/wp-sitemap.xml' ), 'fr' ) . ')';
		return (string) apply_filters( 'sm_seo_llms_txt', implode( "\n", $lines ) . "\n" );
	}
}
