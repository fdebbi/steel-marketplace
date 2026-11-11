<?php
/**
 * The service catalogue (fabrication & site work). Engineering studies are
 * handled by SM Engineering (requests to the platform).
 *
 * @package SM\Services
 */

namespace SM\Services;

defined( 'ABSPATH' ) || exit;

final class Catalogue {

	const PRICE_UNITS = array( 'project', 'kg', 't', 'm2', 'ml', 'piece', 'hour', 'day' );

	/** Administrator's changes to the service lists (SM → Types and categories). */
	const OPTION = 'sm_services_categories';

	private static $all = null;

	/**
	 * Kinds with their services. Per kind: categories (offered, code => {fr, ar}),
	 * labels (every known service, hidden ones too), meta (code => desc, image_id).
	 */
	public static function all() {
		if ( null === self::$all ) {
			$base = require SM_SERVICES_DIR . '/data/catalogue.php';
			$opt  = (array) get_option( self::OPTION, array() );
			foreach ( $base as $kind => $def ) {
				$o       = (array) ( $opt[ $kind ] ?? array() );
				$items   = (array) ( $o['items'] ?? array() );
				$deleted = (array) ( $o['deleted'] ?? array() );
				$cats    = $def['categories'];
				foreach ( $items as $code => $it ) {
					if ( ! isset( $cats[ $code ] ) ) {
						$cats[ $code ] = array( 'fr' => $code, 'ar' => '' );
					}
				}
				$labels = array();
				$meta   = array();
				$order  = array();
				$i      = 0;
				foreach ( $cats as $code => $l ) {
					if ( in_array( $code, $deleted, true ) ) {
						continue;
					}
					$it = (array) ( $items[ $code ] ?? array() );
					$fr = trim( (string) ( $it['fr'] ?? '' ) );
					$ar = trim( (string) ( $it['ar'] ?? '' ) );
					$labels[ $code ] = array( 'fr' => '' !== $fr ? $fr : $l['fr'], 'ar' => '' !== $ar ? $ar : ( $l['ar'] ? $l['ar'] : ( '' !== $fr ? $fr : $l['fr'] ) ) );
					$meta[ $code ]   = array(
						'desc'     => array( 'fr' => (string) ( $it['desc_fr'] ?? '' ), 'ar' => (string) ( $it['desc_ar'] ?? '' ) ),
						'image_id' => (int) ( $it['image_id'] ?? 0 ),
						'order'    => (int) ( $it['order'] ?? 0 ),
						'hidden'   => ! empty( $it['hidden'] ),
						'default'  => $l['fr'],
						'builtin'  => isset( $def['categories'][ $code ] ),
					);
					$order[ $code ] = array( $meta[ $code ]['order'], $i++ );
				}
				uksort( $labels, static fn( $a, $b ) => $order[ $a ] <=> $order[ $b ] );
				$base[ $kind ]['labels']     = $labels;
				$base[ $kind ]['meta']       = $meta;
				$base[ $kind ]['categories'] = array_filter( $labels, static fn( $c ) => empty( $meta[ $c ]['hidden'] ), ARRAY_FILTER_USE_KEY );
			}
			self::$all = apply_filters( 'sm_services_catalogue', $base );
		}
		return self::$all;
	}

	public static function reset() {
		self::$all = null;
	}

	/** Number of service pages that use a service code. */
	public static function usage( $code ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . Services::table() . ' WHERE categories LIKE %s', '%"' . $wpdb->esc_like( $code ) . '"%' ) );
	}

	/** Change the administrator's settings of one service code. */
	public static function update_item( $kind, $code, array $fields ) {
		$opt = (array) get_option( self::OPTION, array() );
		$cur = (array) ( $opt[ $kind ]['items'][ $code ] ?? array() );
		$opt[ $kind ]['items'][ $code ] = array_merge( $cur, $fields );
		update_option( self::OPTION, $opt, false );
		self::reset();
	}

	/**
	 * Delete a service code; the pages that use it get $replacement.
	 */
	public static function delete_item( $kind, $code, $replacement ) {
		global $wpdb;
		$def = self::kind( $kind );
		if ( ! $def || ! isset( $def['labels'][ $code ] ) ) {
			return new \WP_Error( 'sm_unknown', __( 'Unknown service.', 'sm-services' ) );
		}
		$used = self::usage( $code );
		if ( $used && ( $replacement === $code || ! isset( $def['labels'][ $replacement ] ) ) ) {
			return new \WP_Error( 'sm_replacement', __( 'Choose the service that replaces it on the pages that use it.', 'sm-services' ) );
		}
		if ( $used ) {
			$t = Services::table();
			// phpcs:disable WordPress.DB.DirectDatabaseQuery
			foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT id, categories FROM $t WHERE categories LIKE %s", '%"' . $wpdb->esc_like( $code ) . '"%' ) ) as $row ) {
				$cats = (array) json_decode( (string) $row->categories, true );
				$cats = array_values( array_unique( array_map( static fn( $c ) => $c === $code ? $replacement : $c, $cats ) ) );
				$wpdb->update( $t, array( 'categories' => wp_json_encode( $cats ) ), array( 'id' => $row->id ) );
			}
			// phpcs:enable
		}
		$opt = (array) get_option( self::OPTION, array() );
		unset( $opt[ $kind ]['items'][ $code ] );
		$opt[ $kind ]['deleted'][] = $code;
		$opt[ $kind ]['deleted']   = array_values( array_unique( $opt[ $kind ]['deleted'] ) );
		update_option( self::OPTION, $opt, false );
		self::reset();
		return true;
	}

	/** List for SM → Types and categories (sm-core). */
	public static function term_lists( $lists ) {
		foreach ( self::all() as $kind => $def ) {
			$lists[ 'services_' . $kind ] = array(
				'label'  => __( 'Services', 'sm-services' ),
				'help'   => __( 'Services that workshops and professionals tick on their service pages, and that clients filter on.', 'sm-services' ),
				'items'  => static function () use ( $kind ) {
					$def = Catalogue::kind( $kind );
					$opt = (array) get_option( Catalogue::OPTION, array() );
					$out = array();
					foreach ( $def['labels'] as $code => $l ) {
						$it = (array) ( $opt[ $kind ]['items'][ $code ] ?? array() );
						$m  = $def['meta'][ $code ];
						$out[ $code ] = array(
							'fr'       => (string) ( $it['fr'] ?? '' ),
							'ar'       => (string) ( $it['ar'] ?? '' ),
							'default'  => $m['default'],
							'desc_fr'  => $m['desc']['fr'],
							'desc_ar'  => $m['desc']['ar'],
							'image_id' => $m['image_id'],
							'order'    => $m['order'],
							'hidden'   => $m['hidden'],
							'usage'    => Catalogue::usage( $code ),
						);
					}
					return $out;
				},
				'save'   => static function ( $code, array $data ) use ( $kind ) {
					if ( '' === $code ) {
						if ( '' === $data['fr'] ) {
							return new \WP_Error( 'sm_name', __( 'Enter the French name.', 'sm-services' ) );
						}
						$base = substr( str_replace( '-', '_', sanitize_title( $data['fr'] ) ), 0, 32 );
						$base = $base ? $base : 'service';
						$code = $base;
						$opt  = (array) get_option( Catalogue::OPTION, array() );
						$def  = Catalogue::kind( $kind );
						for ( $i = 2; isset( $def['labels'][ $code ] ) || in_array( $code, (array) ( $opt[ $kind ]['deleted'] ?? array() ), true ); $i++ ) {
							$code = $base . '_' . $i;
						}
					}
					Catalogue::update_item( $kind, $code, $data );
					return $code;
				},
				'hide'   => static fn( $code, $hidden ) => Catalogue::update_item( $kind, $code, array( 'hidden' => (bool) $hidden ) ),
				'delete' => static fn( $code, $replacement ) => Catalogue::delete_item( $kind, $code, $replacement ),
			);
		}
		return $lists;
	}

	public static function kind( $kind ) {
		return self::all()[ (string) $kind ] ?? null;
	}

	public static function kinds() {
		return array_keys( self::all() );
	}

	/** Kinds the company may publish (by permission). */
	public static function allowed_kinds( $company_id = null ) {
		$out = array();
		foreach ( self::all() as $k => $def ) {
			if ( ! is_wp_error( sm_can_explain( $def['cap'], $company_id ) ) ) {
				$out[] = $k;
			}
		}
		return $out;
	}
}
