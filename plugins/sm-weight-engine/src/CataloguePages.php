<?php
/**
 * Public pages of the catalogue (0.8.0): one page per family and per size of the
 * catalogue, for the people who search « IPE 200 poids » or « HEA 300 kg/m ».
 *
 *   /catalogue/              the families
 *   /catalogue/ipe/          a family: table of its sizes
 *   /catalogue/ipe-200/      a size: weight per metre, bars of 6 and 12 m, drawing
 *
 * Only the values of the engine (dimensions of the standard tables, area, weight
 * at the density of the settings). The sources of the data are never shown; the
 * plates stay in the calculator (no page per thickness). The page « catalogue »
 * belongs to the theme (sm_theme_pages); the name after it is read here.
 *
 * @package SM\WeightEngine
 */

namespace SM\WeightEngine;

defined( 'ABSPATH' ) || exit;

final class CataloguePages {

	const QUERY_VAR = 'sm_cat';

	/** English names of the families (French and Arabic names are in the catalogue). */
	const NAMES_EN = array(
		'IPE'    => 'IPE beams',
		'HEA'    => 'HEA beams',
		'HEB'    => 'HEB beams',
		'HEM'    => 'HEM beams',
		'IPN'    => 'IPN beams',
		'UPN'    => 'UPN channels',
		'UPE'    => 'UPE channels',
		'L'      => 'Equal angles',
		'LI'     => 'Unequal angles',
		'SHS'    => 'Square hollow sections',
		'RHS'    => 'Rectangular hollow sections',
		'CHS'    => 'Circular hollow sections',
		'FLAT'   => 'Flat bars',
		'ROUND'  => 'Round bars',
		'SQUARE' => 'Square bars',
	);

	public static function init() {
		add_action( 'init', array( __CLASS__, 'rewrite' ), 20 );
		add_filter( 'query_vars', static fn( $v ) => array_merge( $v, array( self::QUERY_VAR ) ) );
		add_filter( 'sm_page_url', array( __CLASS__, 'page_url' ), 20, 3 );
		add_filter( 'sm_theme_data_catalogue', array( __CLASS__, 'data' ), 10, 2 );
	}

	public static function page_id() {
		$map = (array) get_option( 'sm_theme_pages', array() );
		return (int) ( $map['catalogue'] ?? 0 );
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
		$sig = $path . '|' . $id . '|' . SM_WEIGHT_VERSION;
		if ( get_option( 'sm_weight_catalogue_rules' ) !== $sig ) {
			update_option( 'sm_weight_catalogue_rules', $sig, false );
			flush_rewrite_rules( false );
		}
	}

	/** sm_page_url( 'catalogue', [ 'slug' => 'ipe-200' ] ). */
	public static function page_url( $url, $key, $args ) {
		if ( 'catalogue' !== $key ) {
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
		return self::page_url( '', 'catalogue', array( 'slug' => $slug ) );
	}

	/** « IPE 200 » → ipe-200, « CHS 48.3x3.2 » → chs-48-3x3-2. */
	public static function slug( $designation ) {
		$s = strtolower( remove_accents( str_replace( array( '×', '.', ',' ), array( 'x', '-', '-' ), (string) $designation ) ) );
		$s = preg_replace( '/[^a-z0-9]+/', '-', $s );
		return trim( preg_replace( '/-+/', '-', $s ), '-' );
	}

	/** Families with pages: active, with sizes in the catalogue, not the plates. */
	public static function families() {
		$out = array();
		foreach ( Catalogue::families() as $f ) {
			if ( 'plate' === $f->shape || ! $f->count || ! Catalogue::sections( $f->code ) ) {
				continue;
			}
			$out[ $f->code ] = $f;
		}
		return $out;
	}

	/** slug => [ family code, section id ] of every size with a page. */
	public static function map() {
		static $map = null;
		if ( null !== $map ) {
			return $map;
		}
		$map = wp_cache_get( 'catalogue_map', Catalogue::GROUP );
		if ( false !== $map ) {
			return $map;
		}
		$map = array();
		foreach ( self::families() as $code => $f ) {
			foreach ( Catalogue::sections( $code ) as $s ) {
				$map[ self::slug( $s->designation ) ] = array( $code, (int) $s->id );
			}
		}
		wp_cache_set( 'catalogue_map', $map, Catalogue::GROUP, HOUR_IN_SECONDS );
		return $map;
	}

	public static function name( $f ) {
		return array(
			'fr' => (string) $f->name_fr,
			'ar' => (string) $f->name_ar,
			'en' => self::NAMES_EN[ $f->code ] ?? (string) $f->name_fr,
		);
	}

	/**
	 * Page asked: index (''), a family (« ipe ») or a size (« ipe-200 »); null when the
	 * name is unknown (the page answers « not found »).
	 */
	public static function data( $r, $args ) {
		if ( null !== $r ) {
			return $r;
		}
		$slug = sanitize_title( (string) ( $args['slug'] ?? '' ) );
		if ( '' === $slug ) {
			return self::index();
		}
		$families = self::families();
		$code     = strtoupper( $slug );
		if ( isset( $families[ $code ] ) && strtolower( $code ) === $slug ) {
			return self::family( $families[ $code ] );
		}
		$map = self::map();
		if ( isset( $map[ $slug ] ) ) {
			return self::section( $families[ $map[ $slug ][0] ], (int) $map[ $slug ][1] );
		}
		return null;
	}

	private static function family_card( $f ) {
		$sections = Catalogue::sections( $f->code );
		$masses   = array_map( static fn( $s ) => (float) $s->mass_kg_m, $sections );
		$sample   = $sections[ (int) floor( count( $sections ) / 2 ) ];
		return array(
			'code'     => $f->code,
			'group'    => $f->group_code,
			'name'     => self::name( $f ),
			'standard' => (string) $f->standard,
			'count'    => count( $sections ),
			'min'      => $masses ? min( $masses ) : 0,
			'max'      => $masses ? max( $masses ) : 0,
			'first'    => $sections ? $sections[0]->designation : '',
			'last'     => $sections ? end( $sections )->designation : '',
			'url'      => self::url( strtolower( $f->code ) ),
			'icon'     => Drawing::svg( $f->shape, $sample->dims, array( 'dims' => false ) ),
		);
	}

	public static function index() {
		$groups = array();
		foreach ( self::families() as $f ) {
			$groups[ $f->group_code ][] = self::family_card( $f );
		}
		return array(
			'kind'    => 'index',
			'url'     => self::url(),
			'groups'  => $groups,
			'density' => (float) Settings::get( 'density' ),
		);
	}

	public static function family( $f ) {
		$rows = array();
		foreach ( Catalogue::sections( $f->code ) as $s ) {
			$rows[] = array(
				'designation' => $s->designation,
				'kg_m'        => (float) $s->mass_kg_m,
				'kg_12'       => round( (float) $s->mass_kg_m * 12, 1 ),
				'dims'        => $s->dims,
				'url'         => self::url( self::slug( $s->designation ) ),
			);
		}
		return array_merge(
			self::family_card( $f ),
			array(
				'kind'      => 'family',
				'shape'     => $f->shape,
				'dims_keys' => Domain\Geometry::SHAPES[ $f->shape ],
				'rows'      => $rows,
				'density'   => (float) Settings::get( 'density' ),
			)
		);
	}

	public static function section( $f, $id ) {
		$sections = Catalogue::sections( $f->code );
		$pos      = null;
		foreach ( $sections as $i => $s ) {
			if ( (int) $s->id === $id ) {
				$pos = $i;
				break;
			}
		}
		if ( null === $pos ) {
			return null;
		}
		$s    = $sections[ $pos ];
		$kg   = (float) $s->mass_kg_m;
		$near = static function ( $x ) {
			return $x ? array( 'designation' => $x->designation, 'kg_m' => (float) $x->mass_kg_m, 'url' => self::url( self::slug( $x->designation ) ) ) : null;
		};
		return array(
			'kind'        => 'section',
			'id'          => (int) $s->id,
			'designation' => $s->designation,
			'slug'        => self::slug( $s->designation ),
			'url'         => self::url( self::slug( $s->designation ) ),
			'family'      => array(
				'code'     => $f->code,
				'name'     => self::name( $f ),
				'url'      => self::url( strtolower( $f->code ) ),
				'standard' => (string) $f->standard,
				'count'    => count( $sections ),
			),
			'shape'       => $f->shape,
			'dims'        => $s->dims,
			'area_mm2'    => round( (float) $s->area_mm2, 2 ),
			'kg_m'        => $kg,
			'kg_6'        => round( $kg * 6, 1 ),
			'kg_12'       => round( $kg * 12, 1 ),
			'm_per_t'     => $kg > 0 ? round( 1000 / $kg, 1 ) : 0,
			'density'     => (float) Settings::get( 'density' ),
			'drawing'     => Drawing::svg( $f->shape, $s->dims, array( 'title' => $s->designation ) ),
			'prev'        => $near( $sections[ $pos - 1 ] ?? null ),
			'next'        => $near( $sections[ $pos + 1 ] ?? null ),
			'calc_url'    => add_query_arg( array( 'family' => $f->code, 'section' => (int) $s->id ), (string) apply_filters( 'sm_page_url', '', 'weight', array() ) ),
		);
	}
}
