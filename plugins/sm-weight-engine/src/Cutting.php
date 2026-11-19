<?php
/**
 * Cutting optimiser (0.4.0): the pieces to cut, profile by profile, are placed
 * on commercial bars (and first on the offcuts the user has in stock) so that
 * the fewest new bars are bought. A plain calculation, no AI: best fit
 * decreasing gives a first plan, then a bounded search proves it optimal or
 * improves it. Very long lists stop at a node limit: the plan is then the best
 * one found, and says so.
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
		return array(
			'bar'   => max( 1000, min( 24000, $bar ) ),
			'kerf'  => $kerf,
			'reuse' => max( 0, min( 24000, $reuse ) ),
			'lines' => $read( $raw['rows'] ?? array(), self::MAX_LINES ),
			'stock' => $read( $raw['stock'] ?? array(), self::MAX_STOCK_LINES ),
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
		$t = array( 'new_bars' => 0, 'bought_kg' => 0.0, 'pieces_kg' => 0.0, 'scrap_kg' => 0.0, 'used_kg' => 0.0, 'stock_used' => 0, 'reusable' => 0, 'reusable_items' => array() );
		foreach ( $profiles as $p ) {
			$t['new_bars']   += $p['new_bars'];
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
		$t['scrap_pct'] = $t['used_kg'] > 0 ? round( 100 * $t['scrap_kg'] / $t['used_kg'], 1 ) : 0.0;
		return array(
			'ok'       => ! $errors && (bool) $profiles,
			'errors'   => $errors,
			'profiles' => $profiles,
			'totals'   => $t,
			'settings' => array( 'bar' => $in['bar'], 'kerf' => $in['kerf'], 'reuse' => $in['reuse'] ),
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
				$bins[] = $len;
			}
		}
		sort( $bins ); // Shortest offcuts first: they are the hardest to use.
		$plan = self::solve( $items, $bins, $bar, $kerf );

		$kg_m   = (float) $s->mass_kg_m;
		$bars   = array();
		$reuse  = (int) $in['reuse'];
		$out    = array( 'pieces_mm' => 0, 'used_mm' => 0, 'scrap_mm' => 0, 'new' => 0, 'stock' => 0, 'reusable' => 0, 'reusable_items' => array() );
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
			$key  = $b['source'] . '|' . $b['length'] . '|' . implode( ',', $b['pieces'] );
			if ( ! isset( $bars[ $key ] ) ) {
				$bars[ $key ] = array( 'source' => $b['source'], 'length' => $b['length'], 'pieces' => $b['pieces'], 'loss' => $loss, 'offcut' => $rest, 'reusable' => $can, 'count' => 0 );
			}
			++$bars[ $key ]['count'];
			$out['pieces_mm'] += $sum;
			$out['used_mm']   += $b['length'];
			$out['scrap_mm']  += $loss;
			'stock' === $b['source'] ? ++$out['stock'] : ++$out['new'];
			if ( $can ) {
				++$out['reusable'];
				$out['reusable_items'][ $rest ] = ( $out['reusable_items'][ $rest ] ?? 0 ) + 1;
			}
		}
		$bars = array_values( $bars );
		usort( $bars, static fn( $a, $b ) => array( 'stock' === $a['source'] ? 1 : 0, $a['loss'] ) <=> array( 'stock' === $b['source'] ? 1 : 0, $b['loss'] ) );
		$new_mm = $out['new'] * $bar;
		return array(
			'section_id'     => (int) $s->id,
			'family'         => $s->family,
			'designation'    => $s->designation,
			'mass_kg_m'      => $kg_m,
			'pieces'         => count( $items ),
			'cuts'           => array_sum( array_map( static fn( $x ) => $x['count'] * max( 0, count( $x['pieces'] ) - 1 + ( $x['offcut'] > 0 ? 1 : 0 ) ), $bars ) ),
			'bars'           => $bars,
			'new_bars'       => $out['new'],
			'stock_used'     => $out['stock'],
			'stock_left'     => count( array_filter( $plan['bins'], static fn( $b ) => 'stock' === $b['source'] && ! $b['pieces'] ) ),
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
		$bins = array();
		foreach ( $stock as $len ) {
			$bins[] = array( 'source' => 'stock', 'length' => $len, 'rem' => $len + $kerf, 'pieces' => array() );
		}
		foreach ( $size as $i => $sz ) {
			$best = -1;
			foreach ( $bins as $j => $b ) {
				if ( $b['rem'] >= $sz && ( -1 === $best || $b['rem'] < $bins[ $best ]['rem'] ) ) {
					$best = $j;
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
		$stock_cap = array_sum( array_map( static fn( $l ) => $l + $kerf, $stock ) );
		$lb        = (int) max( 0, ceil( ( array_sum( $size ) - $stock_cap ) / $cap_new ) );
		$too_long  = count( array_filter( $items, static fn( $l ) => $l > ( $stock ? max( $stock ) : 0 ) ) ); // Pieces no offcut can take.
		$lb        = max( $lb, $too_long > 0 ? 1 : 0 );
		if ( $ub <= $lb ) {
			return array( 'bins' => $bins, 'optimal' => true );
		}

		// 3. Depth-first search for fewer new bars.
		$rem    = array_map( static fn( $l ) => $l + $kerf, $stock );
		$src    = array_fill( 0, count( $stock ), 'stock' );
		$len    = $stock;
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
				$bins[ $j ] = array( 'source' => $source, 'length' => $l[ $j ], 'pieces' => array() );
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
