<?php
/**
 * Figures of the Business report (0.1.0). Internal rules (never shown as such):
 *  - only prices asked by sellers, weights put on sale and counts of listings,
 *    searches and requests; no company name, no quantity or amount of sale;
 *  - an average is given only with at least MIN_SOURCES listings (filter
 *    sm_insights_min_sources), the extreme 10 % cut from 10 listings;
 *  - the best-selling profiles are a ranking only (the counts stay here).
 *
 * The figures of the market are computed once a day for each choice of period,
 * market and region (transient); the part of the company (its prices, its
 * warehouse) is computed at each visit and seen by itself only.
 *
 * A listing counts for a period when it was online during it: published before
 * its end and still online, or sold, expired or paused after its start.
 *
 * @package SM\Insights
 */

namespace SM\Insights;

defined( 'ABSPATH' ) || exit;

final class Report {

	const PERIODS     = array( '30' => 30, '90' => 90, '365' => 365 );
	const MARKETS     = array( 'all', 'new', 'reuse' );
	const MIN_SOURCES = 5;
	const MIN_DEMAND  = 5;
	const TOP         = 5;
	const ORDER       = array( 'IPE', 'IPN', 'HEA', 'HEB', 'HEM', 'UPN', 'UPE', 'L', 'LI', 'SHS', 'RHS', 'CHS', 'FLAT', 'ROUND', 'SQUARE', 'PLATE' );

	public static function min_sources() {
		return max( 1, (int) apply_filters( 'sm_insights_min_sources', self::MIN_SOURCES ) );
	}

	/** Choice of the visitor, checked: { period, market, region }. */
	public static function args( array $in ) {
		$period = (string) ( $in['period'] ?? '30' );
		$market = (string) ( $in['market'] ?? 'all' );
		$region = (string) ( $in['region'] ?? '' );
		return array(
			'period' => isset( self::PERIODS[ $period ] ) ? $period : '30',
			'market' => in_array( $market, self::MARKETS, true ) ? $market : 'all',
			'region' => in_array( $region, Regions::KEYS, true ) ? $region : '',
		);
	}

	public static function cache_key( array $a ) {
		return 'sm_ins_r_' . md5( implode( '|', $a ) . '|' . gmdate( 'Y-m-d' ) . '|' . self::min_sources() );
	}

	/** Figures of the market (cached for the day). */
	public static function market( array $a ) {
		$a   = self::args( $a );
		$key = self::cache_key( $a );
		$r   = get_transient( $key );
		if ( is_array( $r ) ) {
			return $r;
		}
		$r = self::compute( $a );
		set_transient( $key, $r, DAY_IN_SECONDS );
		return $r;
	}

	public static function flush() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_sm\\_ins\\_%' OR option_name LIKE '\\_transient\\_timeout\\_sm\\_ins\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/* ------------------------------------------------------------------ */

	/** [ start, end ] of the period ($back = 1: the period before). */
	public static function window( $days, $back = 0, $now = null ) {
		$now = $now ?? time();
		$end = $now - $back * $days * DAY_IN_SECONDS;
		return array( gmdate( 'Y-m-d H:i:s', $end - $days * DAY_IN_SECONDS ), gmdate( 'Y-m-d H:i:s', $end ) );
	}

	private static function in_list( array $values ) {
		global $wpdb;
		return implode( ',', array_map( static fn( $v ) => $wpdb->prepare( '%s', $v ), $values ) );
	}

	/** Where-clause of the listings online during a window, in a region, for some markets. */
	private static function online( array $w, array $markets, $region ) {
		global $wpdb;
		$sql = $wpdb->prepare( "status IN ('published','sold','expired','paused') AND published_at IS NOT NULL AND published_at <= %s AND (status = 'published' OR updated_at >= %s)", $w[1], $w[0] );
		$sql .= ' AND market IN (' . self::in_list( $markets ) . ')';
		$wil  = Regions::wilayas( $region );
		if ( $wil ) {
			$sql .= ' AND wilaya IN (' . self::in_list( $wil ) . ')';
		}
		return $sql;
	}

	private static function markets( $filter, $buildings = true ) {
		$m = array( 'all' => array( 'products', 'reuse' ), 'new' => array( 'products' ), 'reuse' => array( 'reuse' ) )[ $filter ];
		return $buildings && 'all' === $filter ? array_merge( $m, array( 'buildings' ) ) : $m;
	}

	private static function has_table( $name ) {
		global $wpdb;
		static $known = array();
		if ( ! isset( $known[ $name ] ) ) {
			$known[ $name ] = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . $name ) );
		}
		return $known[ $name ];
	}

	/** Change in % (one decimal), or null. */
	public static function change( $now, $before ) {
		if ( null === $now || null === $before || (float) $before <= 0 ) {
			return null;
		}
		return round( ( (float) $now - (float) $before ) / (float) $before * 100, 1 );
	}

	/** Average with the extreme 10 % cut from 10 values; null under the minimum of sources. */
	public static function average( array $values ) {
		$n = count( $values );
		if ( $n < self::min_sources() ) {
			return null;
		}
		sort( $values );
		if ( $n >= 10 ) {
			$cut    = (int) floor( $n * 0.1 );
			$values = array_slice( $values, $cut, $n - 2 * $cut );
		}
		return round( array_sum( $values ) / count( $values ), 2 );
	}

	public static function family_label( $code ) {
		static $names = null;
		if ( preg_match( '/^(IPE|HE[ABM]|IPN|UPN|UPE)$/', (string) $code ) ) {
			return (string) $code;
		}
		if ( null === $names ) {
			$names = array();
			if ( class_exists( '\SM\WeightEngine\Catalogue' ) ) {
				foreach ( \SM\WeightEngine\Catalogue::families( true ) as $f ) {
					$names[ $f->code ] = array( 'fr' => (string) ( $f->name_fr ?? '' ), 'ar' => (string) ( $f->name_ar ?? '' ), 'en' => (string) ( $f->name_en ?? '' ) );
				}
			}
		}
		$l = class_exists( '\SM\Core\I18n\Language' ) ? \SM\Core\I18n\Language::current() : 'fr';
		$n = $names[ $code ] ?? array();
		return '' !== ( $n[ $l ] ?? '' ) ? $n[ $l ] : ( '' !== ( $n['fr'] ?? '' ) && 'ar' !== $l ? $n['fr'] : (string) $code );
	}

	/** Label of a term counted in the searches (a size, or a whole type). */
	public static function term_label( $term ) {
		return preg_match( '/\d/', (string) $term ) ? (string) $term : sprintf( /* translators: %s: type of profile. */ __( '%s (all sizes)', 'sm-insights' ), self::family_label( $term ) );
	}

	/* ------------------------------------------------------------------ */

	public static function compute( array $a ) {
		global $wpdb;
		$t    = $wpdb->prefix . 'sm_products';
		$days = self::PERIODS[ $a['period'] ];
		$w    = self::window( $days );
		$wp   = self::window( $days, 1 );
		$mk   = self::markets( $a['market'] );

		// Counts.
		$count = static fn( $win, $markets ) => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE " . self::online( $win, $markets, $a['region'] ) ); // phpcs:ignore WordPress.DB
		$kpis  = array(
			'listings'  => array( $count( $w, $mk ), $count( $wp, $mk ) ),
			'buildings' => array( $count( $w, array( 'buildings' ) ), $count( $wp, array( 'buildings' ) ) ),
		);
		$demand            = self::demand( $w, $a );
		$demand_prev       = self::demand( $wp, $a );
		$kpis['rfqs']      = array( $demand['rfqs'], $demand_prev['rfqs'] );
		$kpis['searches']  = array( $demand['searches'], $demand_prev['searches'] );
		foreach ( $kpis as $k => $v ) {
			$kpis[ $k ] = array( 'value' => $v[0], 'before' => $v[1], 'change' => self::change( $v[0], $v[1] ), 'diff' => $v[0] - $v[1] );
		}

		// Steel on sale: always the three markets (period and region only).
		$kg = array( 'products' => 0.0, 'reuse' => 0.0, 'buildings' => 0.0 );
		foreach ( $wpdb->get_results( "SELECT market, SUM(total_weight) kg FROM $t WHERE " . self::online( $w, array_keys( $kg ), $a['region'] ) . ' GROUP BY market' ) as $row ) { // phpcs:ignore WordPress.DB
			$kg[ $row->market ] = (float) $row->kg;
		}
		$total   = array_sum( $kg );
		$weights = array( 'total_t' => round( $total / 1000, 1 ) );
		foreach ( $kg as $m => $v ) {
			$weights[ $m . '_t' ]   = round( $v / 1000, 1 );
			$weights[ $m . '_pct' ] = $total > 0 ? round( $v / $total * 100, 1 ) : 0;
		}

		return array(
			'args'         => $a,
			'from'         => substr( $w[0], 0, 10 ),
			'to'           => substr( $w[1], 0, 10 ),
			'kpis'         => $kpis,
			'weights'      => $weights,
			'prices'       => self::with_series( self::prices( $w, $wp, $a ), $a ),
			'top_sold'     => self::top_sold( $w, $a ),
			'top_searched' => array_slice( $demand['terms'], 0, self::TOP ),
			'opportunities' => self::opportunities( $w, $a, $demand['terms'] ),
			'regions'      => self::regions( $w, $a ),
			'at'           => time(),
		);
	}

	/** Average asked prices per type of profile, new and reuse. */
	private static function prices( array $w, array $wp, array $a ) {
		global $wpdb;
		$t    = $wpdb->prefix . 'sm_products';
		$rows = static fn( $win ) => $wpdb->get_results( "SELECT family, designation, market, price_per_kg FROM $t WHERE " . self::online( $win, array( 'products', 'reuse' ), $a['region'] ) . " AND price_per_kg > 0 AND family <> ''" ); // phpcs:ignore WordPress.DB
		$group = static function ( $list ) {
			$g = array();
			foreach ( $list as $r ) {
				$g[ $r->family ][ $r->market ][]                         = (float) $r->price_per_kg;
				$g[ $r->family ]['_d'][ $r->designation ][ $r->market ][] = (float) $r->price_per_kg;
			}
			return $g;
		};
		$now    = $group( $rows( $w ) );
		$before = $group( $rows( $wp ) );
		$fams   = array_keys( $now );
		usort(
			$fams,
			static function ( $x, $y ) {
				$i = array_search( $x, self::ORDER, true );
				$j = array_search( $y, self::ORDER, true );
				return ( false === $i ? 99 : $i ) <=> ( false === $j ? 99 : $j ) ?: strcmp( $x, $y );
			}
		);
		$show_new   = 'reuse' !== $a['market'];
		$show_reuse = 'new' !== $a['market'];
		$out        = array();
		foreach ( $fams as $f ) {
			$new   = $show_new ? self::average( $now[ $f ]['products'] ?? array() ) : null;
			$reuse = $show_reuse ? self::average( $now[ $f ]['reuse'] ?? array() ) : null;
			if ( null === $new && null === $reuse ) {
				continue;
			}
			$details = array();
			foreach ( (array) ( $now[ $f ]['_d'] ?? array() ) as $des => $m ) {
				$dn = $show_new ? self::average( $m['products'] ?? array() ) : null;
				$dr = $show_reuse ? self::average( $m['reuse'] ?? array() ) : null;
				if ( ( null !== $dn || null !== $dr ) && 'PLATE' !== $f && false === strpos( (string) $des, '+' ) ) { // A size, not a lot of several sizes.
					$details[] = array( 'designation' => (string) $des, 'new' => $dn, 'reuse' => $dr );
				}
			}
			usort( $details, static fn( $x, $y ) => strnatcmp( $x['designation'], $y['designation'] ) );
			$out[] = array(
				'family'       => $f,
				'label'        => self::family_label( $f ),
				'new'          => $new,
				'new_change'   => self::change( $new, $show_new ? self::average( $before[ $f ]['products'] ?? array() ) : null ),
				'reuse'        => $reuse,
				'reuse_change' => self::change( $reuse, $show_reuse ? self::average( $before[ $f ]['reuse'] ?? array() ) : null ),
				'gap'          => self::change( $reuse, $new ),
				'details'      => $details,
			);
		}
		return $out;
	}

	/**
	 * Curve of the last 12 months for each type of the table: average asked price per
	 * month (new, else reuse), null for a month without enough listings.
	 */
	private static function with_series( array $prices, array $a ) {
		global $wpdb;
		if ( ! $prices ) {
			return $prices;
		}
		$t      = $wpdb->prefix . 'sm_products';
		$year   = self::window( 365 );
		$rows   = $wpdb->get_results( "SELECT family, market, price_per_kg, status, published_at, updated_at FROM $t WHERE " . self::online( $year, array( 'products', 'reuse' ), $a['region'] ) . " AND price_per_kg > 0 AND family <> ''" ); // phpcs:ignore WordPress.DB
		$months = array();
		for ( $i = 11; $i >= 0; $i-- ) {
			$start            = strtotime( gmdate( 'Y-m-01', strtotime( "-$i month", strtotime( gmdate( 'Y-m-15' ) ) ) ) );
			$months[ gmdate( 'Y-m', $start ) ] = array( gmdate( 'Y-m-d H:i:s', $start ), gmdate( 'Y-m-d H:i:s', strtotime( '+1 month', $start ) ) );
		}
		$values = array();
		foreach ( $rows as $r ) {
			foreach ( $months as $m => $win ) {
				if ( $r->published_at < $win[1] && ( 'published' === $r->status || $r->updated_at >= $win[0] ) ) {
					$values[ $r->family ][ $r->market ][ $m ][] = (float) $r->price_per_kg;
				}
			}
		}
		foreach ( $prices as $i => $row ) {
			$col    = null !== $row['new'] ? 'products' : 'reuse';
			$series = array();
			foreach ( array_keys( $months ) as $m ) {
				$series[] = array( 'month' => $m, 'price' => self::average( $values[ $row['family'] ][ $col ][ $m ] ?? array() ) );
			}
			$prices[ $i ]['series']        = count( array_filter( array_column( $series, 'price' ), static fn( $v ) => null !== $v ) ) >= 2 ? $series : array();
			$prices[ $i ]['series_market'] = 'products' === $col ? 'new' : 'reuse';
		}
		return $prices;
	}

	/** Searches and requests for quotation: totals and the terms asked, most asked first. */
	private static function demand( array $w, array $a ) {
		global $wpdb;
		$terms    = array();
		$searches = 0;
		$wil      = Regions::wilayas( $a['region'] );
		if ( self::has_table( 'sm_search_counts' ) ) {
			$where = $wpdb->prepare( 'day >= %s AND day <= %s', substr( $w[0], 0, 10 ), substr( $w[1], 0, 10 ) );
			if ( 'all' !== $a['market'] ) {
				$where .= $wpdb->prepare( ' AND market = %s', 'new' === $a['market'] ? 'products' : 'reuse' );
			}
			if ( $wil ) {
				$where .= ' AND wilaya IN (' . self::in_list( $wil ) . ')';
			}
			foreach ( $wpdb->get_results( 'SELECT term, SUM(hits) n FROM ' . $wpdb->prefix . "sm_search_counts WHERE $where GROUP BY term" ) as $r ) { // phpcs:ignore WordPress.DB
				$terms[ $r->term ] = ( $terms[ $r->term ] ?? 0 ) + (int) $r->n;
				$searches         += (int) $r->n;
			}
		}
		$rfqs = 0;
		if ( self::has_table( 'sm_rfqs' ) ) {
			$where = $wpdb->prepare( "status IN ('open','awarded','closed','expired') AND published_at IS NOT NULL AND published_at >= %s AND published_at <= %s", $w[0], $w[1] );
			if ( 'all' !== $a['market'] ) {
				$where .= $wpdb->prepare( ' AND market = %s', 'new' === $a['market'] ? 'products' : 'reuse' );
			}
			if ( $wil ) {
				$where .= ' AND wilaya IN (' . self::in_list( $wil ) . ')';
			}
			foreach ( $wpdb->get_results( 'SELECT designation, inventory FROM ' . $wpdb->prefix . "sm_rfqs WHERE $where" ) as $r ) { // phpcs:ignore WordPress.DB
				++$rfqs;
				$seen  = array();
				$lines = json_decode( (string) $r->inventory, true );
				foreach ( is_array( $lines ) && $lines ? $lines : array( array( 'designation' => $r->designation, 'family' => '' ) ) as $l ) {
					$t = Searches::term( (string) ( $l['family'] ?? '' ), (string) ( $l['designation'] ?? '' ), '' );
					if ( $t && ! isset( $seen[ $t[0] ] ) && preg_match( '/\d/', $t[0] ) ) {
						$seen[ $t[0] ]   = true;
						$terms[ $t[0] ] = ( $terms[ $t[0] ] ?? 0 ) + 1;
					}
				}
			}
		}
		arsort( $terms );
		$list = array();
		foreach ( $terms as $term => $n ) {
			$list[] = array( 'term' => (string) $term, 'label' => self::term_label( $term ), 'count' => (int) $n );
		}
		return array( 'searches' => $searches, 'rfqs' => $rfqs, 'terms' => $list );
	}

	/** Best-selling profiles: the ranking only, never the counts (accepted orders and offers). */
	private static function top_sold( array $w, array $a ) {
		global $wpdb;
		if ( ! self::has_table( 'sm_trade_deals' ) ) {
			return array();
		}
		$p     = $wpdb->prefix . 'sm_products';
		$where = $wpdb->prepare( "d.status = 'accepted' AND d.updated_at >= %s AND d.updated_at <= %s AND p.family <> '' AND p.market IN ('products','reuse')", $w[0], $w[1] );
		if ( 'all' !== $a['market'] ) {
			$where .= $wpdb->prepare( ' AND p.market = %s', 'new' === $a['market'] ? 'products' : 'reuse' );
		}
		$wil = Regions::wilayas( $a['region'] );
		if ( $wil ) {
			$where .= ' AND p.wilaya IN (' . self::in_list( $wil ) . ')';
		}
		$rows = $wpdb->get_col( 'SELECT p.designation FROM ' . $wpdb->prefix . "sm_trade_deals d JOIN $p p ON p.id = d.product_id WHERE $where GROUP BY p.designation ORDER BY COUNT(*) DESC, p.designation ASC LIMIT " . self::TOP ); // phpcs:ignore WordPress.DB
		return array_values( array_filter( array_map( 'strval', $rows ), static fn( $d ) => '' !== $d && (bool) preg_match( '/\d/', $d ) ) );
	}

	/** Asked a lot, little offered: demand of at least MIN_DEMAND, twice the listings at least. */
	private static function opportunities( array $w, array $a, array $terms ) {
		global $wpdb;
		$t   = $wpdb->prefix . 'sm_products';
		$out = array();
		foreach ( $terms as $x ) {
			if ( $x['count'] < self::MIN_DEMAND || ! preg_match( '/\d/', $x['term'] ) ) {
				continue;
			}
			$n = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE " . self::online( $w, self::markets( $a['market'], false ), $a['region'] ) . " AND REPLACE(designation, ' ', '') = %s", str_replace( ' ', '', $x['term'] ) ) ); // phpcs:ignore WordPress.DB
			if ( $x['count'] >= 2 * max( 1, $n ) ) {
				$out[] = $x + array( 'listings' => $n, 'ratio' => $x['count'] / ( $n + 1 ) );
			}
		}
		usort( $out, static fn( $x, $y ) => $y['ratio'] <=> $x['ratio'] );
		return array_map( static fn( $o ) => array_diff_key( $o, array( 'ratio' => 1 ) ), array_slice( $out, 0, 3 ) );
	}

	/** Per region: listings and requests, share of reuse in the weight on sale. */
	private static function regions( array $w, array $a ) {
		global $wpdb;
		$t   = $wpdb->prefix . 'sm_products';
		$out = array();
		foreach ( Regions::KEYS as $r ) {
			$listings = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE " . self::online( $w, self::markets( $a['market'] ), $r ) ); // phpcs:ignore WordPress.DB
			$kg       = $wpdb->get_results( "SELECT market, SUM(total_weight) kg FROM $t WHERE " . self::online( $w, array( 'products', 'reuse', 'buildings' ), $r ) . ' GROUP BY market', OBJECT_K ); // phpcs:ignore WordPress.DB
			$all      = array_sum( array_map( static fn( $x ) => (float) $x->kg, $kg ) );
			$rfqs     = self::demand( $w, array( 'market' => $a['market'], 'region' => $r ) )['rfqs'];
			$out[ $r ] = array(
				'listings'  => $listings,
				'rfqs'      => $rfqs,
				'reuse_pct' => $all > 0 ? round( (float) ( $kg['reuse']->kg ?? 0 ) / $all * 100 ) : null,
			);
		}
		return $out;
	}

	/* ------------------------------------------------------------------ */

	/**
	 * The company's own part, seen by itself only: its average asked price per type
	 * (listings online now) against the market, and its warehouse for the opportunities.
	 */
	public static function mine( array $market, $company ) {
		global $wpdb;
		$t   = $wpdb->prefix . 'sm_products';
		$cid = (int) $company->id;
		$own = array();
		foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT family, market, AVG(price_per_kg) avg_kg, COUNT(*) n FROM $t WHERE company_id = %d AND status = 'published' AND price_per_kg > 0 AND family <> '' AND market IN ('products','reuse') GROUP BY family, market", $cid ) ) as $r ) { // phpcs:ignore WordPress.DB
			$own[ $r->family ][ 'products' === $r->market ? 'new' : 'reuse' ] = round( (float) $r->avg_kg, 2 );
		}
		$prices = array();
		$best   = null;
		foreach ( $market['prices'] as $row ) {
			foreach ( array( 'new', 'reuse' ) as $col ) {
				if ( null === $row[ $col ] || ! isset( $own[ $row['family'] ][ $col ] ) ) {
					continue;
				}
				$mine = $own[ $row['family'] ][ $col ];
				$diff = self::change( $mine, $row[ $col ] );
				$prices[ $row['family'] ][ $col ] = array( 'price' => $mine, 'diff' => $diff );
				if ( null !== $diff && ( null === $best || abs( $diff ) > abs( $best['diff'] ) ) ) {
					$best = array( 'label' => $row['label'], 'diff' => $diff, 'col' => $col );
				}
			}
		}
		$stock = array();
		foreach ( $market['opportunities'] as $o ) {
			$kg = (float) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(total_weight) FROM $t WHERE company_id = %d AND status IN ('stock','published','paused') AND REPLACE(designation, ' ', '') = %s", $cid, str_replace( ' ', '', $o['term'] ) ) ); // phpcs:ignore WordPress.DB
			if ( $kg > 0 ) {
				$stock[ $o['term'] ] = round( $kg / 1000, 1 );
			}
		}
		$note = '';
		if ( $best ) {
			$pct  = number_format_i18n( abs( $best['diff'] ) );
			$note = abs( $best['diff'] ) < 1
				/* translators: %s: type of profile. */
				? sprintf( __( 'Your %s are shown at the average price of the market.', 'sm-insights' ), $best['label'] )
				: sprintf(
					/* translators: 1: type of profile, 2: percentage. */
					$best['diff'] > 0 ? __( 'Your %1$s are shown %2$s %% above the average of the market.', 'sm-insights' ) : __( 'Your %1$s are shown %2$s %% below the average of the market.', 'sm-insights' ),
					$best['label'],
					$pct
				);
		}
		return array( 'prices' => $prices, 'stock' => $stock, 'note' => $note );
	}
}
