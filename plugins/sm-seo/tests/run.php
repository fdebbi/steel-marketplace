<?php
/**
 * SM SEO tests (test site only): wp eval-file wp-content/plugins/sm-seo/tests/run.php
 *
 * @package SM\Seo
 */

use SM\Seo\Pages;
use SM\Seo\Robots;
use SM\Seo\Schema;
use SM\Seo\Settings;
use SM\Seo\Sitemaps;
use SM\Seo\Text;

defined( 'ABSPATH' ) || exit;

$GLOBALS['smseo_t'] = array( 'pass' => 0, 'fail' => 0 );
function smseo_assert( $cond, $label ) {
	++$GLOBALS['smseo_t'][ $cond ? 'pass' : 'fail' ];
	echo ( $cond ? '  ✓ ' : '  ✗ ' ) . $label . "\n";
}

$saved = get_option( Settings::OPTION, null );

echo "Settings\n";
smseo_assert( 'search' === Settings::get( 'ai_bots' ) || null !== $saved, 'default choice for the AI bots: search engines allowed (A)' );
smseo_assert( 'abc_DEF-123' === Settings::code( '<meta name="google-site-verification" content="abc_DEF-123" />' ), 'verification code taken from a pasted meta tag' );
smseo_assert( 'xyz' === Settings::code( ' x"y<z ' ), 'verification code: only safe characters' );
$s = Settings::save( array( 'ai_bots' => 'hack', 'default_image' => 'javascript:alert(1)', 'google' => 'g1', 'bing' => 'b1' ) );
smseo_assert( 'search' === $s['ai_bots'] && '' === $s['default_image'], 'unknown choice → search; unsafe image address refused' );

echo "\nrobots.txt\n";
$r = implode( "\n", Robots::rules( 'search' ) );
smseo_assert( false !== strpos( $r, "User-agent: OAI-SearchBot" ) && false !== strpos( $r, "User-agent: PerplexityBot" ), 'search: ChatGPT search and Perplexity listed' );
smseo_assert( (bool) preg_match( "/User-agent: GPTBot.*?Disallow: \\//s", $r ) && false !== strpos( $r, 'User-agent: Google-Extended' ), 'search: training bots (GPTBot, Google-Extended) refused' );
smseo_assert( false === strpos( $r, 'Googlebot' ) && false === strpos( $r, 'bingbot' ), 'Google and Bing search not blocked' );
$closed = Robots::closed_paths();
smseo_assert( in_array( '/compte/', $closed, true ) && in_array( '/ar/compte/', $closed, true ) && in_array( '/en/compte/', $closed, true ), 'account closed in the 3 languages' );
$all = implode( "\n", Robots::rules( 'all' ) );
smseo_assert( false === strpos( $all, 'GPTBot' ) && false !== strpos( $all, 'Disallow: /compte/' ), 'all: no AI group, account still closed' );
$none = implode( "\n", Robots::rules( 'none' ) );
smseo_assert( (bool) preg_match( '/User-agent: OAI-SearchBot.*?Disallow: \//s', $none ), 'none: AI search refused' );
$txt = apply_filters( 'robots_txt', '', true );
smseo_assert( false !== strpos( $txt, 'Sitemap: ' ) && false !== strpos( $txt, 'wp-sitemap.xml' ), 'robots.txt ends with the sitemap' );

echo "\nllms.txt\n";
$l = Robots::llms();
smseo_assert( 0 === strpos( $l, '# ' . get_bloginfo( 'name' ) ) && false !== strpos( $l, '## Markets' ), 'title and sections' );
smseo_assert( false !== strpos( $l, 'not sale prices' ) && false === stripos( $l, 'source' ), 'says the prices are asked; no data source' );

echo "\nTexts\n";
smseo_assert( 'Abc def ghijkl…' === Text::cut( 'Abc def ghijkl mnopq', 16 ), 'cut at a word with …' );
smseo_assert( "10,7" === Text::number( 10.7 ) && "2\u{202F}300\u{202F}000" === Text::number( 2300000, 0 ), 'numbers written like the theme' );
smseo_assert( 'b' === Text::pick( array( 'fr' => '', 'ar' => 'b' ) ), 'multilingual field: fallback to another language' );

echo "\nWilaya (0.1.1)\n";
\SM\Core\I18n\Language::with_language(
	'fr',
	static function () {
		smseo_assert( 'Wilaya d’Alger.' === Pages::wilaya_phrase( 'Alger' ) && 'Wilaya d’El Bayadh.' === Pages::wilaya_phrase( 'El Bayadh' ), 'French: « Wilaya d’Alger », « d’El Bayadh »' );
		smseo_assert( "Wilaya de M'Sila." === Pages::wilaya_phrase( "M'Sila" ) && 'Wilaya de Tlemcen.' === Pages::wilaya_phrase( 'Tlemcen' ), 'French: « Wilaya de M’Sila », « de Tlemcen »' );
	}
);

echo "\nAsked price\n";
$p = Pages::price( array( 'price_unit' => 'kg', 'price_per_kg' => 230, 'sale_mode' => 'kg' ) );
smseo_assert( $p['per_kg'] && 230.0 === (float) $p['price'] && false !== strpos( $p['text'], '230' ), 'per kg' );
$p = Pages::price( array( 'price_unit' => 'lot', 'price_per_kg' => 356.8, 'lot_price' => 250000 ) );
smseo_assert( ! $p['per_kg'] && 250000.0 === (float) $p['price'], 'lot price (the per-kg figure is not shown as an asked price)' );
$p = Pages::price( array( 'price_unit' => 'kg', 'price_per_kg' => 0 ) );
smseo_assert( 0 === $p['price'], 'no price: on request, no offer' );

echo "\nStructured data\n";
smseo_assert( 'https://schema.org/NewCondition' === Schema::condition( array( 'market' => 'products' ) ), 'new steel → NewCondition' );
smseo_assert( 'https://schema.org/UsedCondition' === Schema::condition( array( 'market' => 'reuse', 'condition' => 'good' ) ), 'reuse in good condition → UsedCondition' );
smseo_assert( '' === Schema::condition( array( 'market' => 'buildings', 'condition' => '' ) ), 'building without condition: none invented' );
$item = array( 'market' => 'products', 'category_label' => array( 'fr' => 'Profilés' ), 'weight_kg' => 1000, 'grade' => 'S275JR', 'length_m' => 12, 'qty_mode' => 'bars', 'quantity' => 4, 'expires_at' => '2030-01-01 00:00:00', 'wilaya' => '16', 'seller_public' => false, 'company' => '' );
$prod = Schema::product( $item, 'IPE 200 S275JR', array( 'canonical' => 'https://x.test/annonce/1/', 'description' => 'd', 'index' => true, 'images' => array() ), array( 'price' => 230, 'per_kg' => true ) );
$json = wp_json_encode( $prod );
smseo_assert( 'DZD' === $prod['offers']['priceCurrency'] && 'KGM' === $prod['offers']['priceSpecification']['unitCode'], 'offer in DZD, per kilogram' );
smseo_assert( ! isset( $prod['offers']['seller'] ) && ! isset( $prod['aggregateRating'] ) && ! isset( $prod['review'] ) && false === stripos( $json, 'rating' ), 'no seller when the page is private, never a rating or a review' );
smseo_assert( '2030-01-01' === $prod['offers']['priceValidUntil'], 'price valid until the end of the listing' );
$co = Schema::company( array(), 'X', '', 'Oran', array( 'canonical' => 'u', 'image' => '' ) );
smseo_assert( ! isset( $co['telephone'] ) && ! isset( $co['email'] ) && ! isset( $co['address']['streetAddress'] ) && 'Oran' === $co['address']['addressRegion'], 'company: wilaya only, no phone, e-mail or street' );

echo "\nSitemaps\n";
$e = Sitemaps::entries( home_url( '/produits/' ), '2026-11-01 10:00:00' );
smseo_assert( count( $e ) === count( Text::langs() ) && false !== strpos( $e[1]['loc'], '/ar/produits/' ) && '2026-11-01T10:00:00+00:00' === $e[0]['lastmod'], 'one entry per language, with the date' );
$providers = wp_get_sitemap_providers();
smseo_assert( isset( $providers['smlistings'], $providers['smpages'], $providers['smcompanies'] ) && ! isset( $providers['users'] ), 'providers: pages, listings, companies; no users' );
$pages = implode( ' ', array_column( $providers['smpages']->get_url_list( 1 ), 'loc' ) );
smseo_assert( false === strpos( $pages, '/compte/' ) && false === strpos( $pages, '/connexion/' ) && false === strpos( $pages, '/demander-une-etude/' ), 'no account, login or form page in the sitemap' );
smseo_assert( false !== strpos( $pages, '/ingenierie/' ), 'engineering services page in the sitemap' );
global $wpdb;
$pub = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_listing_index WHERE module = 'products' AND status = 'published'" );
$n   = 0;
for ( $i = 1; $i <= $providers['smlistings']->get_max_num_pages( 'products' ); $i++ ) {
	$n += count( $providers['smlistings']->get_url_list( $i, 'products' ) );
}
smseo_assert( $n === $pub * count( Text::langs() ), "every published listing of new steel, in each language ($n)" );
$eng = $wpdb->get_col( "SELECT slug FROM {$wpdb->prefix}sm_companies WHERE account_class IN ('engineer','client')" );
$cos = implode( ' ', array_column( $providers['smcompanies']->get_url_list( 1 ), 'loc' ) );
$leak = false;
foreach ( $eng as $slug ) {
	$leak = $leak || ( '' !== $slug && false !== strpos( $cos, '/' . $slug . '/' ) );
}
smseo_assert( ! $leak, 'no partner engineer and no client in the sitemap' );

if ( null === $saved ) {
	delete_option( Settings::OPTION );
} else {
	update_option( Settings::OPTION, $saved, false );
}
printf( "\n%d/%d passed\n", $GLOBALS['smseo_t']['pass'], $GLOBALS['smseo_t']['pass'] + $GLOBALS['smseo_t']['fail'] );
