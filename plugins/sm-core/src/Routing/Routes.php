<?php
/**
 * Clean URLs for the pages the theme creates, and access rules.
 *
 *   /annonce/302/          listing detail     (query var sm_id)
 *   /entreprise/acier-x/   company profile    (query var sm_c)
 *   /compte/annonces/      dashboard view     (query var sm_view)
 *
 * Page IDs come from the theme's "sm_theme_pages" option, so renaming a page
 * keeps working. Filters stay as query strings (and are not cached).
 *
 * @package SM\Core
 */

namespace SM\Core\Routing;

use SM\Core\Accounts\Companies;

defined( 'ABSPATH' ) || exit;

final class Routes {

	/** Dashboard views and their URL slugs (French, the default language). */
	public static function view_slugs() {
		return apply_filters(
			'sm_dashboard_view_slugs',
			array(
				'overview'     => '',
				'publish'      => 'publier',
				'listings'     => 'annonces',
				'offers'       => 'devis-envoyes',
				'messages'     => 'demandes',
				'stats'        => 'statistiques',
				'saved'        => 'favoris',
				'rfq'          => 'mes-devis',
				'history'      => 'historique',
				'company'      => 'entreprise',
				'plan'         => 'abonnement',
				'verification' => 'verification',
				'settings'     => 'parametres',
				'profile'      => 'profil',
				'services'     => 'services',
				'requests'     => 'demandes-clients',
				'portfolio'    => 'references',
				'verify-phone' => 'verifier',
				'notifications' => 'notifications',
			)
		);
	}

	public static function init() {
		add_action( 'init', array( __CLASS__, 'rewrite_rules' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'update_option_sm_theme_pages', array( __CLASS__, 'schedule_flush' ) );
		add_action( 'add_option_sm_theme_pages', array( __CLASS__, 'schedule_flush' ) );
		add_action( 'init', array( __CLASS__, 'maybe_flush' ), 99 );
		add_action( 'template_redirect', array( __CLASS__, 'access_rules' ), 5 );
		add_filter( 'sm_page_url', array( __CLASS__, 'filter_page_url' ), 10, 3 );
	}

	public static function page_id( $key ) {
		$map = get_option( 'sm_theme_pages', array() );
		return (int) ( $map[ $key ] ?? 0 );
	}

	/** Page path (e.g. "annonce") for a logical key. */
	private static function page_path( $key ) {
		$id = self::page_id( $key );
		return $id ? get_page_uri( $id ) : '';
	}

	public static function rewrite_rules() {
		$listing = self::page_path( 'listing' );
		$company = self::page_path( 'company' );
		$account = self::page_path( 'account' );
		if ( $listing ) {
			add_rewrite_rule( '^' . preg_quote( $listing, '#' ) . '/([0-9]+)/?$', 'index.php?page_id=' . self::page_id( 'listing' ) . '&sm_id=$matches[1]', 'top' );
		}
		if ( $company ) {
			add_rewrite_rule( '^' . preg_quote( $company, '#' ) . '/([a-z0-9-]+)/?$', 'index.php?page_id=' . self::page_id( 'company' ) . '&sm_c=$matches[1]', 'top' );
		}
		if ( $account ) {
			add_rewrite_rule( '^' . preg_quote( $account, '#' ) . '/([a-z0-9-]+)/?$', 'index.php?page_id=' . self::page_id( 'account' ) . '&sm_view=$matches[1]', 'top' );
		}
	}

	public static function query_vars( $vars ) {
		return array_merge( $vars, array( 'sm_id', 'sm_c', 'sm_view' ) );
	}

	public static function schedule_flush() {
		update_option( 'sm_core_flush_rewrite', 1 );
	}

	public static function maybe_flush() {
		if ( get_option( 'sm_core_flush_rewrite' ) ) {
			delete_option( 'sm_core_flush_rewrite' );
			flush_rewrite_rules( false );
		}
	}

	/** Dashboard view requested (from /compte/{slug}/ or ?view=). */
	public static function current_view() {
		$slug = get_query_var( 'sm_view' );
		if ( $slug ) {
			$view = array_search( $slug, self::view_slugs(), true );
			return false !== $view ? $view : sanitize_key( $slug );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'overview';
	}

	/**
	 * Clean URL for a logical page + arguments. Used by the theme through the
	 * sm_page_url filter, and by sm_url().
	 */
	public static function url( $key, array $args = array() ) {
		$id = self::page_id( $key );
		if ( ! $id ) {
			return add_query_arg( $args, home_url( '/' ) );
		}
		$base = get_permalink( $id );
		$pretty = (bool) get_option( 'permalink_structure' );

		if ( $pretty && 'listing' === $key && ! empty( $args['id'] ) ) {
			$base = trailingslashit( $base ) . absint( $args['id'] ) . '/';
			unset( $args['id'] );
		} elseif ( $pretty && 'company' === $key && ! empty( $args['c'] ) ) {
			$base = trailingslashit( $base ) . sanitize_title( $args['c'] ) . '/';
			unset( $args['c'] );
		} elseif ( $pretty && 'account' === $key && ! empty( $args['view'] ) ) {
			$slugs = self::view_slugs();
			$slug  = $slugs[ $args['view'] ] ?? sanitize_title( $args['view'] );
			$base  = $slug ? trailingslashit( $base ) . $slug . '/' : $base;
			unset( $args['view'] );
		}
		unset( $args['account'] ); // Demo-only switch of the theme.
		return $args ? add_query_arg( $args, $base ) : $base;
	}

	public static function filter_page_url( $url, $key, $args ) {
		return self::url( $key, (array) $args );
	}

	/** Login required for the dashboard; signed-in users skip login/register pages. */
	public static function access_rules() {
		if ( ! is_page() ) {
			return;
		}
		$page = (int) get_queried_object_id();

		if ( $page && $page === self::page_id( 'account' ) && ! is_user_logged_in() ) {
			$target = add_query_arg( 'redirect_to', rawurlencode( \SM\Core\I18n\Language::current_unprefixed_url() ), self::url( 'login' ) );
			wp_safe_redirect( $target );
			exit;
		}
		if ( is_user_logged_in() && in_array( $page, array( self::page_id( 'login' ), self::page_id( 'register' ) ), true ) && $page ) {
			// A logged-in user without a company (e.g. an administrator) may still view these pages.
			if ( Companies::for_user( get_current_user_id() ) ) {
				wp_safe_redirect( self::url( 'account' ) );
				exit;
			}
		}
	}
}
