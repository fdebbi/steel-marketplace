<?php
/**
 * Filters of a results page (0.11.0): the types of a market as tabs (with the number of
 * listings online), and the filters of the chosen type, built from what sellers really
 * entered. A choice is offered only when at least one listing online uses it: no empty
 * lists while the platform is young.
 *
 *   profiles, tubes, bars, reused sections      family (chips), designation, minimum length
 *   plates, reused plates                       thickness (designation)
 *   roofing, finishing, other                   the list fields of the category (spec[key])
 *   offcuts, mixed lots, scrap (sold by weight) minimum weight
 *   reuse                                       condition
 *   buildings                                   assembled / dismantled, condition, area, height
 *
 * The filters common to every type (keyword, wilaya, price per kg, sale mode, verified
 * sellers) are drawn by the theme.
 *
 * @package SM\Products
 */

namespace SM\Products;

defined( 'ABSPATH' ) || exit;

final class Facets {

	/** Engine families shown as chips (the others are a single family, like plates). */
	const CHIP_FAMILIES = array( 'profiles', 'tubes', 'bars' );

	private static function lang() {
		$l = class_exists( '\\SM\\Core\\I18n\\Language' ) ? \SM\Core\I18n\Language::current() : substr( get_locale(), 0, 2 );
		return in_array( $l, array( 'fr', 'ar', 'en' ), true ) ? $l : 'fr';
	}

	/** Label of an engine family in the visitor's language (from the weight engine). */
	private static function family_label( $code ) {
		static $names = null;
		if ( null === $names ) {
			$names = array();
			if ( class_exists( '\SM\WeightEngine\Catalogue' ) ) {
				foreach ( \SM\WeightEngine\Catalogue::families( true ) as $f ) {
					$names[ $f->code ] = array( 'fr' => (string) ( $f->name_fr ?? '' ), 'ar' => (string) ( $f->name_ar ?? '' ), 'en' => (string) ( $f->name_en ?? '' ) );
				}
			}
		}
		// Beams and channels are known by their code (IPE, HEA…); the others by their name.
		if ( preg_match( '/^(IPE|HE[ABM]|IPN|UPN|UPE)$/', (string) $code ) ) {
			return (string) $code;
		}
		$n = $names[ $code ] ?? array();
		$l = self::lang();
		return '' !== ( $n[ $l ] ?? '' ) ? $n[ $l ] : ( '' !== ( $n['fr'] ?? '' ) && 'ar' !== $l ? $n['fr'] : $code );
	}

	/** Counts of the listings online for one column, with the other filters applied. */
	private static function values( $column, array $where_args ) {
		global $wpdb;
		$t = Products::table();
		list( $where, $args ) = $where_args;
		$sql = "SELECT {$column} v, COUNT(*) n FROM {$t} WHERE {$where} AND {$column} IS NOT NULL AND {$column} <> '' GROUP BY {$column} ORDER BY n DESC, v ASC LIMIT 60";
		return $wpdb->get_results( $args ? $wpdb->prepare( $sql, $args ) : $sql ); // phpcs:ignore WordPress.DB
	}

	private static function base_where( $market, $category, $family = '' ) {
		$where = array( "status = 'published'", 'market = %s' );
		$args  = array( $market );
		if ( '' !== $category ) {
			$where[] = 'category = %s';
			$args[]  = $category;
		}
		if ( '' !== $family ) {
			$where[] = 'family = %s';
			$args[]  = $family;
		}
		return array( implode( ' AND ', $where ), $args );
	}

	/**
	 * @param string $market   products|reuse|buildings
	 * @param string $category slug or '' (all types)
	 * @param string $family   chosen family, to narrow the designations
	 * @return array{tabs: array, facets: array}
	 */
	public static function get( $market, $category = '', $family = '' ) {
		global $wpdb;
		$t    = Products::table();
		$cats = Categories::all( false, $market );
		$l    = self::lang();

		// Tabs: every active type of the market, with its number of listings online.
		$counts = array();
		foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT category, COUNT(*) n FROM {$t} WHERE status = 'published' AND market = %s GROUP BY category", $market ) ) as $r ) { // phpcs:ignore WordPress.DB
			$counts[ $r->category ] = (int) $r->n;
		}
		$tabs = array(
			array( 'slug' => '', 'label' => '', 'count' => array_sum( $counts ) ),
		);
		$cat  = null;
		foreach ( $cats as $c ) {
			$name   = 'ar' === $l && '' !== (string) $c->name_ar ? $c->name_ar : $c->name_fr;
			$tabs[] = array( 'slug' => $c->slug, 'label' => (string) $name, 'count' => $counts[ $c->slug ] ?? 0 );
			if ( $c->slug === $category ) {
				$cat = $c;
			}
		}
		$category = $cat ? $category : '';
		$facets   = array();

		// Reuse: condition, for every type.
		if ( 'reuse' === $market ) {
			$facets[] = array( 'key' => 'condition', 'type' => 'chips', 'label' => 'condition', 'options' => self::options( self::values( 'item_condition', self::base_where( $market, $category ) ) ) );
		}
		// Buildings: assembled / dismantled, condition, area, height.
		if ( 'buildings' === $market ) {
			$facets[] = array( 'key' => 'mode', 'type' => 'chips', 'label' => 'variant', 'options' => self::options( self::values( 'variant', self::base_where( $market, $category ) ) ) );
			$facets[] = array( 'key' => 'condition', 'type' => 'chips', 'label' => 'condition', 'options' => self::options( self::values( 'item_condition', self::base_where( $market, $category ) ) ) );
			$facets[] = array( 'key' => 'area_min', 'type' => 'number', 'label' => 'area_min', 'unit' => 'm²', 'step' => 1 );
			$facets[] = array( 'key' => 'height_min', 'type' => 'number', 'label' => 'height_min', 'unit' => 'm', 'step' => 0.5 );
		}

		if ( $cat ) {
			$kind = (string) $cat->kind;
			if ( in_array( $kind, array( 'engine', 'lines' ), true ) ) {
				$fams = self::values( 'family', self::base_where( $market, $category ) );
				$one  = count( $fams ) <= 1 && ! in_array( $category, self::CHIP_FAMILIES, true );
				if ( 'plates' === $category || 'reuse-plates' === $category || ( $fams && 'PLATE' === $fams[0]->v && 1 === count( $fams ) ) ) {
					$facets[] = array( 'key' => 'designation', 'type' => 'select', 'label' => 'thickness', 'options' => array_map( static fn( $o ) => array_merge( $o, array( 'label' => trim( preg_replace( '/^PLATE\s*/', '', $o['value'] ) ) . ' mm' ) ), self::options( self::values( 'designation', self::base_where( $market, $category ) ) ) ) );
				} else {
					if ( count( $fams ) > 1 || ( ! $one && $fams ) ) {
						$facets[] = array( 'key' => 'family', 'type' => 'chips', 'label' => 'family', 'options' => array_map( static fn( $o ) => array_merge( $o, array( 'label' => self::family_label( $o['value'] ) ) ), self::options( $fams ) ) );
					}
					// Lots of several lines (reuse) carry a summary designation: no list of designations there.
					if ( 'engine' === $kind ) {
						$facets[] = array( 'key' => 'designation', 'type' => 'select', 'label' => 'designation', 'options' => self::options( self::values( 'designation', self::base_where( $market, $category, (string) $family ) ) ) );
					}
					$facets[] = array( 'key' => 'length_min', 'type' => 'number', 'label' => 'length_min', 'unit' => 'm', 'step' => 0.1 );
				}
			}
			if ( 'weight' === $kind || 'lines' === $kind && 'reuse' === $market ) {
				$facets[] = array( 'key' => 'weight_min', 'type' => 'number', 'label' => 'weight_min', 'unit' => 'kg', 'step' => 10 );
			}
			if ( 'specs' === $kind ) {
				foreach ( (array) $cat->fields as $f ) {
					if ( 'select' !== ( $f['type'] ?? '' ) || empty( $f['key'] ) ) {
						continue;
					}
					$key  = sanitize_key( $f['key'] );
					$sql  = "SELECT JSON_UNQUOTE(JSON_EXTRACT(specs, '$.{$key}')) v, COUNT(*) n FROM {$t} WHERE status = 'published' AND market = %s AND category = %s GROUP BY v ORDER BY n DESC";
					$rows = array_filter( (array) $wpdb->get_results( $wpdb->prepare( $sql, $market, $category ) ), static fn( $r ) => '' !== (string) $r->v && 'null' !== (string) $r->v ); // phpcs:ignore WordPress.DB
					if ( ! $rows ) {
						continue;
					}
					$labels = array();
					foreach ( (array) ( $f['options'] ?? array() ) as $o ) {
						$labels[ (string) $o['value'] ] = (string) ( $o[ $l ] ?? $o['fr'] ?? $o['value'] );
					}
					$facets[] = array(
						'key'     => 'spec[' . $key . ']',
						'type'    => 'select',
						'label'   => (string) ( $f['label'][ $l ] ?? $f['label']['fr'] ?? $key ),
						'custom'  => true,
						'options' => array_map( static fn( $r ) => array( 'value' => (string) $r->v, 'count' => (int) $r->n, 'label' => $labels[ (string) $r->v ] ?? (string) $r->v ), array_values( $rows ) ),
					);
				}
			}
		}
		// A list without any value is left out.
		$facets = array_values( array_filter( $facets, static fn( $f ) => 'number' === $f['type'] || ! empty( $f['options'] ) ) );
		return array( 'tabs' => $tabs, 'facets' => $facets, 'category' => $category );
	}

	private static function options( $rows ) {
		return array_map( static fn( $r ) => array( 'value' => (string) $r->v, 'count' => (int) $r->n ), (array) $rows );
	}

	/** Keys of the custom list fields of a category (accepted in spec[key]). */
	public static function spec_keys( $category ) {
		$c = $category ? Categories::get( $category ) : null;
		if ( ! $c || 'specs' !== $c->kind ) {
			return array();
		}
		$out = array();
		foreach ( (array) $c->fields as $f ) {
			if ( 'select' === ( $f['type'] ?? '' ) && ! empty( $f['key'] ) ) {
				$out[] = sanitize_key( $f['key'] );
			}
		}
		return $out;
	}
}
