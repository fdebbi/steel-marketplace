<?php
/**
 * Cutting optimiser (0.4.0): the pieces to cut, profile by profile, are placed
 * on commercial bars (and first on the offcuts the user has in stock) so that
 * the fewest new bars are bought. A plain calculation, no AI: best fit
 * decreasing gives a first plan, then a bounded search proves it optimal or
 * improves it. Very long lists stop at a node limit: the plan is then the best
 * one found, and says so.
 *
 * Order of the bars (0.6.0): the offcuts typed in the form and the warehouse
 * of the company (filter sm_cutting_stock_offers, sm-stock) first, then the
 * marketplace (sm_cutting_market_offers), then new bars to buy. Each stage only
 * gets the pieces the previous one could not place.
 *
 * Model: each cut costs the saw kerf; a bar holding n pieces needs n − 1 cuts
 * between them (a last cut separates the offcut when there is one). Lengths are
 * handled in millimetres. Weights come from the catalogue (mass per metre).
 *
 * @package SM\WeightEngine
 */

namespace SM\WeightEngine;

defined( 'ABSPATH' ) || exit;

final class Cutting {

	const MAX_LINES        = 40;
	const MAX_STOCK_LINES  = 40;
	const MAX_PIECES       = 300; // Per profile.
	const MAX_NODES        = 1000000; // Per profile.
	const DEFAULT_BAR_MM   = 12000;
	const DEFAULT_KERF_MM  = 3;
	const DEFAULT_REUSE_MM = 1000;
	/** Conditions a buyer may accept on the marketplace (scrap never). */
	const CONDITIONS         = array( 'new', 'new_surplus', 'excellent', 'good', 'inspection' );
	const DEFAULT_CONDITIONS = array( 'new', 'new_surplus', 'excellent', 'good' );

	/** Length in metres as typed ("5,10", "5.1") → millimetres, or 0. */
	public static function mm( $metres ) {
		$v = (float) str_replace( array( ',', ' ', "\u{00A0}" ), array( '.', '', '' ), (string) $metres );
		return $v > 0 ? (int) round( $v * 1000 ) : 0;
	}

	/**
	 * Read the raw request (GET of the page, or the same values posted).
	 *
	 * @return array { bar, kerf, reuse, lines: [ {section, length, qty} ], stock: [ … ] } in mm.
	 */
	public static function read( array $raw ) {
		$bar   = self::mm( '' !== (string) ( $raw['bar'] ?? '' ) ? $raw['bar'] : ( $raw['bar_custom'] ?? '' ) ) ?: self::DEFAULT_BAR_MM;
		$kerf  = isset( $raw['kerf'] ) && '' !== $raw['kerf'] ? max( 0, min( 20, (int) round( (float) str_replace( ',', '.', (string) $raw['kerf'] ) ) ) ) : self::DEFAULT_KERF_MM;
		$reuse = isset( $raw['reuse'] ) && '' !== $raw['reuse'] ? self::mm( $raw['reuse'] ) : self::DEFAULT_REUSE_MM;
		$read  = static function ( $rows, $max ) {
			$out = array();
			foreach ( array_slice( (array) $rows, 0, $max ) as $r ) {
				$r   = (array) $r;
				$sid = absint( $r['section'] ?? 0 );
				$len = self::mm( $r['length'] ?? '' );
				$qty = absint( $r['qty'] ?? 0 );
				if ( $sid || $len || $qty ) {
					$out[] = array( 'section' => $sid, 'length' => $len, 'qty' => $qty, 'family' => strtoupper( sanitize_key( (string) ( $r['family'] ?? '' ) ) ) );
				}
			}
			return $out;
		};
		// Where to look for bars (0.5.0). Without the form marker "src", the defaults apply.
		$sent  = isset( $raw['src'] );
		$conds = array_values( array_intersect( array_map( 'sanitize_key', (array) ( $raw['conds'] ?? array() ) ), self::CONDITIONS ) );
		$grade = absint( $raw['grade_min'] ?? 0 );
		return array(
			'bar'       => max( 1000, min( 24000, $bar ) ),
			'kerf'      => $kerf,
			'reuse'     => max( 0, min( 24000, $reuse ) ),
			'lines'     => $read( $raw['rows'] ?? array(), self::MAX_LINES ),
			'stock'     => $read( $raw['stock'] ?? array(), self::MAX_STOCK_LINES ),
			'market'    => $sent ? ! empty( $raw['market'] ) : true,
			'lots'      => $sent ? ! empty( $raw['lots'] ) : true,
			'conds'     => $sent ? $conds : self::DEFAULT_CONDITIONS,
			'grade_min' => in_array( $grade, array( 235, 275, 355, 460 ), true ) ? $grade : 0,
			'wilaya'    => preg_match( '/^\d{2}$/', (string) ( $raw['wilaya'] ?? '' ) ) ? (string) $raw['wilaya'] : '',
			// The warehouse of the company (0.6.0): new and reused steel, published or not.
			'mine'      => $sent ? ! empty( $raw['mine'] ) : true,
			'mine_new'  => $sent ? ! empty( $raw['mine_new'] ) : true,
			'mine_reuse' => $sent ? ! empty( $raw['mine_reuse'] ) : true,
			'mine_unpublished' => $sent && ! empty( $raw['mine_unpublished'] ),
		);
	}

	/**
	 * Optimise a request read by read().
	 *
	 * @return array { ok, errors: [ {row, kind, message} ], profiles: [ … ], totals: { … }, settings }
	 */
	public static function optimize( array $in ) {
		$errors = array();
		$groups = array();
		foreach ( $in['lines'] as $i => $l ) {
			$s = $l['section'] ? Catalogue::get( $l['section'] ) : null;
			$f = $s ? Catalogue::family( $s->family ) : null;
			if ( ! $s || ! $f || 'plate' === $f->shape ) {
				$errors[] = array( 'row' => $i, 'kind' => 'rows', 'message' => __( 'Choose the profile and its size.', 'sm-weight-engine' ) );
				continue;
			}
			if ( ! $l['length'] || ! $l['qty'] ) {
				$errors[] = array( 'row' => $i, 'kind' => 'rows', 'message' => __( 'Give the length and the number of pieces.', 'sm-weight-engine' ) );
				continue;
			}
			$groups[ $s->id ]['section']  = $s;
			$groups[ $s->id ]['pieces'][] = array( $l['length'], $l['qty'], $i );
		}
		foreach ( $in['stock'] as $i => $l ) {
			if ( ! $l['section'] || ! $l['length'] || ! $l['qty'] ) {
				$errors[] = array( 'row' => $i, 'kind' => 'stock', 'message' => __( 'Give the profile, the length and the number of offcuts.', 'sm-weight-engine' ) );
				continue;
			}
			if ( isset( $groups[ $l['section'] ] ) ) {
				$groups[ $l['section'] ]['stock'][] = array( $l['length'], min( 100, $l['qty'] ) );
			}
		}
		$profiles = array();
		foreach ( $groups as $sid => $g ) {
			$res = self::profile( $g['section'], $g['pieces'], $g['stock'] ?? array(), $in );
			if ( isset( $res['error'] ) ) {
				$errors[] = $res['error'];
				continue;
			}
			$profiles[] = $res;
		}
		$t = array( 'new_bars' => 0, 'bought_kg' => 0.0, 'pieces_kg' => 0.0, 'scrap_kg' => 0.0, 'used_kg' => 0.0, 'stock_used' => 0, 'reusable' => 0, 'reusable_items' => array(), 'market_bars' => 0, 'market_kg' => 0.0, 'market_cost' => 0.0, 'market_offers' => 0, 'mine_bars' => 0, 'mine_kg' => 0.0, 'mine_items' => 0, 'offcuts' => array() );
		foreach ( $profiles as $p ) {
			$t['new_bars']    += $p['new_bars'];
			$t['market_bars'] += $p['market_bars'];
			$t['market_kg']   += $p['market_kg'];
			$t['market_offers'] += count( $p['market'] );
			$t['mine_bars']   += $p['mine_bars'];
			$t['mine_kg']     += $p['mine_kg'];
			$t['mine_items']  += count( $p['mine'] );
			foreach ( $p['offcuts'] as $o ) {
				$t['offcuts'][] = $o;
			}
			foreach ( $p['market'] as $m ) {
				$t['market_cost'] += $m['cost'];
			}
			$t['bought_kg']  += $p['bought_kg'];
			$t['pieces_kg']  += $p['pieces_kg'];
			$t['scrap_kg']   += $p['scrap_kg'];
			$t['used_kg']    += $p['used_kg'];
			$t['stock_used'] += $p['stock_used'];
			$t['reusable']   += $p['reusable'];
			foreach ( $p['reusable_items'] as $mm => $n ) {
				$t['reusable_items'][] = array( 'designation' => $p['designation'], 'length' => $mm, 'count' => $n );
			}
		}
		$t['scrap_pct']  = $t['used_kg'] > 0 ? round( 100 * $t['scrap_kg'] / $t['used_kg'], 1 ) : 0.0;
		$t['acquire_kg'] = round( $t['bought_kg'] + $t['market_kg'], 2 );
		return array(
			'ok'       => ! $errors && (bool) $profiles,
			'errors'   => $errors,
			'profiles' => $profiles,
			'totals'   => $t,
			'settings' => array( 'bar' => $in['bar'], 'kerf' => $in['kerf'], 'reuse' => $in['reuse'], 'market' => ! empty( $in['market'] ), 'mine' => ! empty( $in['mine'] ) ),
			'at'       => time(),
		);
	}

	/** One profile: plan, groups of identical bars, figures. */
	private static function profile( $s, array $pieces, array $stock, array $in ) {
		$bar  = (int) $in['bar'];
		$kerf = (int) $in['kerf'];
		$max  = $bar;
		foreach ( $stock as $st ) {
			$max = max( $max, $st[0] );
		}
		$items = array();
		foreach ( $pieces as list( $len, $qty, $row ) ) {
			if ( $len > $max ) {
				/* translators: 1: piece length in m, 2: profile, 3: bar length in m. */
				return array( 'error' => array( 'row' => $row, 'kind' => 'rows', 'message' => sprintf( __( 'A piece of %1$s m of %2$s is longer than the bars (%3$s m).', 'sm-weight-engine' ), self::m( $len ), $s->designation, self::m( $bar ) ) ) );
			}
			for ( $k = 0; $k < $qty; $k++ ) {
				$items[] = $len;
			}
		}
		if ( count( $items ) > self::MAX_PIECES ) {
			/* translators: 1: profile, 2: maximum number of pieces. */
			return array( 'error' => array( 'row' => $pieces[0][2], 'kind' => 'rows', 'message' => sprintf( __( '%1$s: %2$d pieces at most per profile.', 'sm-weight-engine' ), $s->designation, self::MAX_PIECES ) ) );
		}
		rsort( $items );
		$bins = array();
		foreach ( $stock as list( $len, $qty ) ) {
			for ( $k = 0; $k < $qty; $k++ ) {
				$bins[] = array( 'length' => $len, 'source' => 'stock', 'origin' => '' );
			}
		}
		$min_piece = $items ? min( $items ) : 0;
		$n_items   = count( $items );
		// Warehouse of the company (0.6.0): same section, published or kept in stock only.
		$own = array();
		if ( ! empty( $in['mine'] ) && ( ! empty( $in['mine_new'] ) || ! empty( $in['mine_reuse'] ) ) ) {
			$mf = array( 'new' => ! empty( $in['mine_new'] ), 'reuse' => ! empty( $in['mine_reuse'] ), 'unpublished' => ! empty( $in['mine_unpublished'] ) );
			foreach ( (array) apply_filters( 'sm_cutting_stock_offers', array(), $s, $mf ) as $o ) {
				$own[ $o['key'] ] = $o;
				foreach ( $o['lines'] as $l ) {
					if ( $l['length'] < $min_piece ) {
						continue;
					}
					$take = min( (int) $l['qty'], $n_items );
					for ( $k = 0; $k < $take; $k++ ) {
						$bins[] = array( 'length' => (int) $l['length'], 'source' => 'mine', 'origin' => $o['key'] );
					}
				}
			}
		}
		// Marketplace (0.5.0): same section, bars at least as long as the shortest piece.
		$offers = array();
		$mk     = array();
		if ( ! empty( $in['market'] ) ) {
			$filters = array(
				'grade_min'       => (int) $in['grade_min'],
				'conds'           => (array) $in['conds'],
				'wilaya'          => (string) $in['wilaya'],
				'lots'            => ! empty( $in['lots'] ),
				'exclude_company' => function_exists( 'sm_current_company' ) && sm_current_company() ? (int) sm_current_company()->id : 0,
			);
			foreach ( (array) apply_filters( 'sm_cutting_market_offers', array(), $s, $filters ) as $o ) {
				if ( ! empty( $o['lot'] ) && empty( $filters['lots'] ) ) {
					continue; // Whole lots only when asked.
				}
				$offers[ $o['key'] ] = $o;
				foreach ( $o['lines'] as $l ) {
					if ( $l['length'] < $min_piece ) {
						continue;
					}
					$take = min( (int) $l['qty'], $n_items );
					for ( $k = 0; $k < $take; $k++ ) {
						$mk[] = array( 'length' => (int) $l['length'], 'source' => 'market', 'origin' => $o['key'], 'price' => (float) $o['price_per_kg'] );
					}
				}
			}
		}
		// Shortest first (the closest length fits first): the offcuts typed in the form, then the warehouse.
		usort( $bins, static fn( $a, $b ) => array( $a['length'], 'stock' === $a['source'] ? 0 : 1 ) <=> array( $b['length'], 'stock' === $b['source'] ? 0 : 1 ) );
		// Then the cheapest offer of the same length.
		usort( $mk, static fn( $a, $b ) => array( $a['length'], $a['price'] ) <=> array( $b['length'], $b['price'] ) );
		$plan = self::stages( $items, $bins, $mk, $bar, $kerf );

		$kg_m   = (float) $s->mass_kg_m;
		$bars   = array();
		$reuse  = (int) $in['reuse'];
		$out    = array( 'pieces_mm' => 0, 'used_mm' => 0, 'scrap_mm' => 0, 'new' => 0, 'stock' => 0, 'market_bars' => 0, 'market_mm' => 0, 'mine_bars' => 0, 'mine_mm' => 0, 'reusable' => 0, 'reusable_items' => array(), 'offcuts' => array(), 'market' => array(), 'mine' => array() );
		foreach ( $plan['bins'] as $b ) {
			if ( ! $b['pieces'] ) {
				continue;
			}
			rsort( $b['pieces'] );
			$sum  = array_sum( $b['pieces'] );
			$n    = count( $b['pieces'] );
			$raw  = $b['length'] - $sum - ( $n - 1 ) * $kerf; // After the cuts between pieces.
			$rest = $raw > $kerf ? $raw - $kerf : 0;          // Usable offcut after its own cut.
			$loss = $b['length'] - $sum;                      // Offcut and saw cuts.
			$can  = $rest > 0 && $reuse > 0 && $rest >= $reuse;
			$org  = (string) ( $b['origin'] ?? '' );
			$key  = $b['source'] . '|' . $org . '|' . $b['length'] . '|' . implode( ',', $b['pieces'] );
			if ( ! isset( $bars[ $key ] ) ) {
				$bars[ $key ] = array( 'source' => $b['source'], 'origin' => $org, 'length' => $b['length'], 'pieces' => $b['pieces'], 'loss' => $loss, 'offcut' => $rest, 'reusable' => $can, 'count' => 0 );
			}
			if ( 'market' === $b['source'] || 'mine' === $b['source'] ) {
				$m = &$out[ $b['source'] ][ $org ];
				$m['bars'][ $b['length'] ] = ( $m['bars'][ $b['length'] ] ?? 0 ) + 1;
				foreach ( $b['pieces'] as $pc ) {
					$m['pieces'][ $pc ] = ( $m['pieces'][ $pc ] ?? 0 ) + 1;
				}
				$m['mm'] = ( $m['mm'] ?? 0 ) + $b['length'];
				unset( $m );
			}
			++$bars[ $key ]['count'];
			$out['pieces_mm'] += $sum;
			$out['used_mm']   += $b['length'];
			$out['scrap_mm']  += $loss;
			if ( 'stock' === $b['source'] ) {
				++$out['stock'];
			} elseif ( 'market' === $b['source'] ) {
				++$out['market_bars'];
				$out['market_mm'] += $b['length'];
			} elseif ( 'mine' === $b['source'] ) {
				++$out['mine_bars'];
				$out['mine_mm'] += $b['length'];
			} else {
				++$out['new'];
			}
			if ( $can ) {
				++$out['reusable'];
				$out['reusable_items'][ $rest ] = ( $out['reusable_items'][ $rest ] ?? 0 ) + 1;
				$ok = $b['source'] . '|' . $org . '|' . $rest;
				$out['offcuts'][ $ok ] = ( $out['offcuts'][ $ok ] ?? 0 ) + 1;
			}
		}
		$bars = array_values( $bars );
		$rank = array( 'stock' => 0, 'mine' => 1, 'market' => 2, 'new' => 3 );
		usort( $bars, static fn( $a, $b ) => array( $rank[ $a['source'] ] ?? 3, $a['origin'], $a['loss'] ) <=> array( $rank[ $b['source'] ] ?? 3, $b['origin'], $b['loss'] ) );
		$new_mm = $out['new'] * $bar;
		// What to take from each offer of the marketplace: bars, pieces cut, weight, estimated cost.
		$market = array();
		foreach ( $out['market'] as $key => $m ) {
			$o   = $offers[ $key ];
			$kg  = round( $m['mm'] / 1000 * $kg_m, 2 );
			$all = 0;
			foreach ( $o['lines'] as $l ) {
				$all += $l['qty'];
			}
			krsort( $m['bars'] );
			krsort( $m['pieces'] );
			$market[] = $o + array(
				'take_bars'   => $m['bars'],
				'take_pieces' => $m['pieces'],
				'take_count'  => array_sum( $m['bars'] ),
				'take_kg'     => $kg,
				'lot_rest'    => $o['lot'] ? max( 0, $all - array_sum( $m['bars'] ) ) : 0,
				'cost'        => $o['lot'] ? (float) $o['lot_price'] : ( $o['price_per_kg'] > 0 ? round( $kg * $o['price_per_kg'] ) : 0.0 ),
			);
		}
		// What to take from the warehouse: same shape, no price (the steel is already there).
		$mine = array();
		foreach ( $out['mine'] as $key => $m ) {
			krsort( $m['bars'] );
			krsort( $m['pieces'] );
			$mine[] = $own[ $key ] + array(
				'take_bars'   => $m['bars'],
				'take_pieces' => $m['pieces'],
				'take_count'  => array_sum( $m['bars'] ),
				'take_kg'     => round( $m['mm'] / 1000 * $kg_m, 2 ),
			);
		}
		// Reusable offcuts, with where they come from (to add them to the warehouse).
		$offcuts = array();
		foreach ( $out['offcuts'] as $ok => $n ) {
			list( $src, $org, $mm ) = explode( '|', $ok );
			$from      = 'mine' === $src ? ( $own[ $org ] ?? array() ) : ( 'market' === $src ? ( $offers[ $org ] ?? array() ) : array() );
			$offcuts[] = array(
				'section_id'  => (int) $s->id,
				'family'      => $s->family,
				'designation' => $s->designation,
				'kg_m'        => $kg_m,
				'length'      => (int) $mm,
				'count'       => $n,
				'kg'          => round( (int) $mm / 1000 * $kg_m * $n, 2 ),
				'source'      => $src,
				'origin'      => $org,
				'ref'         => (string) ( $from['ref'] ?? '' ),
				'condition'   => (string) ( $from['condition'] ?? '' ),
				'grade'       => (string) ( $from['grade'] ?? '' ),
			);
		}
		usort( $offcuts, static fn( $a, $b ) => $b['length'] <=> $a['length'] );
		return array(
			'section_id'     => (int) $s->id,
			'family'         => $s->family,
			'designation'    => $s->designation,
			'mass_kg_m'      => $kg_m,
			'pieces'         => count( $items ),
			'cuts'           => array_sum( array_map( static fn( $x ) => $x['count'] * max( 0, count( $x['pieces'] ) - 1 + ( $x['offcut'] > 0 ? 1 : 0 ) ), $bars ) ),
			'bars'           => $bars,
			'new_bars'       => $out['new'],
			'market_bars'    => $out['market_bars'],
			'market_kg'      => round( $out['market_mm'] / 1000 * $kg_m, 2 ),
			'market'         => $market,
			'mine'           => $mine,
			'mine_bars'      => $out['mine_bars'],
			'mine_kg'        => round( $out['mine_mm'] / 1000 * $kg_m, 2 ),
			'offcuts'        => $offcuts,
			'stock_used'     => $out['stock'],
			'stock_left'     => count( array_filter( $plan['bins'], static fn( $b ) => 'stock' === $b['source'] && ! $b['pieces'] ) ),
			'offers_seen'    => count( $offers ),
			'bought_m'       => $new_mm / 1000,
			'bought_kg'      => round( $new_mm / 1000 * $kg_m, 2 ),
			'pieces_m'       => $out['pieces_mm'] / 1000,
			'pieces_kg'      => round( $out['pieces_mm'] / 1000 * $kg_m, 2 ),
			'used_kg'        => round( $out['used_mm'] / 1000 * $kg_m, 2 ),
			'scrap_m'        => $out['scrap_mm'] / 1000,
			'scrap_kg'       => round( $out['scrap_mm'] / 1000 * $kg_m, 2 ),
			'scrap_pct'      => $out['used_mm'] ? round( 100 * $out['scrap_mm'] / $out['used_mm'], 1 ) : 0.0,
			'reusable'       => $out['reusable'],
			'reusable_items' => $out['reusable_items'],
			'optimal'        => $plan['optimal'],
		);
	}

	/**
	 * Own bars first (offcuts typed in the form, warehouse), then the marketplace
	 * for the pieces left, then new bars. Unused bins are kept (offcuts not needed).
	 *
	 * @return array { bins, optimal }
	 */
	public static function stages( array $items, array $own, array $market, $bar, $kerf ) {
		if ( ! $market ) {
			return self::solve( $items, $own, $bar, $kerf );
		}
		$one = $own ? self::solve( $items, $own, $bar, $kerf ) : array( 'bins' => array(), 'optimal' => true );
		$left = $own ? array() : $items;
		$bins = array();
		foreach ( $one['bins'] as $b ) {
			if ( 'new' === $b['source'] ) {
				$left = array_merge( $left, $b['pieces'] );
			} else {
				$bins[] = $b;
			}
		}
		if ( ! $left ) {
			return array( 'bins' => $bins, 'optimal' => $one['optimal'] );
		}
		rsort( $left );
		$two = self::solve( $left, $market, $bar, $kerf );
		return array( 'bins' => array_merge( $bins, $two['bins'] ), 'optimal' => $one['optimal'] && $two['optimal'] );
	}

	/**
	 * Bin packing with offcuts in stock (free) and new bars (counted).
	 * Item size = length + kerf, bin capacity = length + kerf (n − 1 cuts).
	 *
	 * @return array { bins: [ {source, length, pieces} ], optimal: bool }
	 */
	public static function solve( array $items, array $stock, $bar, $kerf ) {
		$cap_new = $bar + $kerf;
		$size    = array_map( static fn( $l ) => $l + $kerf, $items );
		$n       = count( $size );

		// 1. Best fit decreasing: offcuts in stock first, then new bars.
		$bins  = array();
		$stock = array_map( static fn( $b ) => is_array( $b ) ? $b : array( 'length' => (int) $b, 'source' => 'stock', 'origin' => '' ), $stock );
		foreach ( $stock as $b ) {
			$bins[] = array( 'source' => $b['source'], 'origin' => $b['origin'] ?? '', 'length' => $b['length'], 'rem' => $b['length'] + $kerf, 'pieces' => array() );
		}
		foreach ( $size as $i => $sz ) {
			// Best fit among the bars already started; else the first unused one that fits
			// (shortest first, as given): the fewest bars of the stock are taken (0.6.0).
			$best = -1;
			foreach ( $bins as $j => $b ) {
				if ( $b['pieces'] && $b['rem'] >= $sz && ( -1 === $best || $b['rem'] < $bins[ $best ]['rem'] ) ) {
					$best = $j;
				}
			}
			if ( -1 === $best ) {
				foreach ( $bins as $j => $b ) {
					if ( ! $b['pieces'] && 'new' !== $b['source'] && $b['rem'] >= $sz ) {
						$best = $j;
						break;
					}
				}
			}
			if ( -1 === $best ) {
				$bins[] = array( 'source' => 'new', 'length' => $bar, 'rem' => $cap_new, 'pieces' => array() );
				$best   = count( $bins ) - 1;
			}
			$bins[ $best ]['rem']     -= $sz;
			$bins[ $best ]['pieces'][] = $items[ $i ];
		}
		$ub = count( array_filter( $bins, static fn( $b ) => 'new' === $b['source'] ) );

		// 2. Lower bound on the new bars.
		$lens      = array_map( static fn( $b ) => $b['length'], $stock );
		$stock_cap = array_sum( array_map( static fn( $l ) => $l + $kerf, $lens ) );
		$lb        = (int) max( 0, ceil( ( array_sum( $size ) - $stock_cap ) / $cap_new ) );
		$too_long  = count( array_filter( $items, static fn( $l ) => $l > ( $lens ? max( $lens ) : 0 ) ) ); // Pieces no offcut can take.
		$lb        = max( $lb, $too_long > 0 ? 1 : 0 );
		if ( $ub <= $lb ) {
			return array( 'bins' => $bins, 'optimal' => true );
		}

		// 3. Depth-first search for fewer new bars.
		$rem    = array_map( static fn( $l ) => $l + $kerf, $lens );
		$src    = array_map( static fn( $b ) => $b['source'] . '|' . ( $b['origin'] ?? '' ), $stock );
		$len    = $lens;
		$assign = array_fill( 0, $n, -1 );
		$suffix = array_fill( 0, $n + 1, 0 );
		for ( $i = $n - 1; $i >= 0; $i-- ) {
			$suffix[ $i ] = $suffix[ $i + 1 ] + $size[ $i ];
		}
		$state = array( 'best' => $ub, 'nodes' => 0, 'found' => null, 'stop' => false );
		$dfs   = static function ( $i, $new ) use ( &$dfs, &$rem, &$src, &$len, &$assign, &$state, $size, $suffix, $n, $cap_new, $bar, $lb ) {
			if ( $state['stop'] ) {
				return;
			}
			if ( ++$state['nodes'] > self::MAX_NODES ) {
				$state['stop'] = true;
				return;
			}
			if ( $i === $n ) {
				if ( $new < $state['best'] ) {
					$state['best']  = $new;
					$state['found'] = array( $assign, $src, $len );
					if ( $new <= $lb ) {
						$state['stop'] = true;
					}
				}
				return;
			}
			$free = 0;
			foreach ( $rem as $r ) {
				$free += $r;
			}
			$need = $suffix[ $i ] - $free;
			if ( $new + ( $need > 0 ? (int) ceil( $need / $cap_new ) : 0 ) >= $state['best'] ) {
				return;
			}
			$sz   = $size[ $i ];
			$seen = array();
			$from = ( $i > 0 && $size[ $i - 1 ] === $sz ) ? max( 0, $assign[ $i - 1 ] ) : 0; // Same pieces: same order of bins.
			$m    = count( $rem );
			for ( $j = $from; $j < $m; $j++ ) {
				$k = $src[ $j ] . $rem[ $j ];
				if ( $rem[ $j ] < $sz || isset( $seen[ $k ] ) ) {
					continue;
				}
				$seen[ $k ]   = true;
				$rem[ $j ]   -= $sz;
				$assign[ $i ] = $j;
				$dfs( $i + 1, $new );
				$rem[ $j ]   += $sz;
				if ( $state['stop'] ) {
					return;
				}
			}
			if ( $new + 1 < $state['best'] ) {
				$rem[]        = $cap_new - $sz;
				$src[]        = 'new';
				$len[]        = $bar;
				$assign[ $i ] = $m;
				$dfs( $i + 1, $new + 1 );
				array_pop( $rem );
				array_pop( $src );
				array_pop( $len );
			}
			$assign[ $i ] = -1;
		};
		$dfs( 0, 0 );

		if ( $state['found'] ) {
			list( $a, $s, $l ) = $state['found'];
			$bins = array();
			foreach ( $s as $j => $source ) {
				list( $so, $or ) = array_pad( explode( '|', $source, 2 ), 2, '' );
				$bins[ $j ]      = array( 'source' => $so, 'origin' => $or, 'length' => $l[ $j ], 'pieces' => array() );
			}
			foreach ( $a as $i => $j ) {
				$bins[ $j ]['pieces'][] = $items[ $i ];
			}
			$bins = array_values( $bins );
		}
		// Optimal when the search ended by itself, or reached the lower bound.
		$optimal = ! $state['stop'] || $state['best'] <= $lb;
		return array( 'bins' => $bins, 'optimal' => $optimal );
	}

	/** Millimetres → "5,10" style metres (2 decimals). */
	public static function m( $mm ) {
		return number_format( $mm / 1000, 2, ',', '' );
	}
}
