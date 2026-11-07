<?php
/**
 * Turns what a seller typed into a checked product record, with its weight and
 * price computed on the server. Used for the live summary of the publication
 * form and, again, when the product is saved: the browser never decides a
 * weight or a total price.
 *
 * Input keys: category, family, section, dims[], grade, length ("custom" + length_custom in m),
 * width, plate_length, qty_mode (bars|weight), bars, total_kg, weight_source (theoretical|actual),
 * actual_weight, price, price_unit, specs[], quantity, unit, wilaya, title[fr|ar], description.
 *
 * @package SM\Products
 */

namespace SM\Products;

defined( 'ABSPATH' ) || exit;

final class Spec {

	/**
	 * @return array{errors: array, data: array, calc: array|null, summary: array|null, category: object|null}
	 */
	public static function build( array $in, $for_save = true ) {
		$out = array( 'errors' => array(), 'data' => array(), 'calc' => null, 'summary' => null, 'category' => null );
		$cat = Categories::get( $in['category'] ?? '' );
		if ( ! $cat || ! $cat->active ) {
			$out['errors']['category'] = __( 'Choose a category.', 'sm-products' );
			return $out;
		}
		$out['category'] = $cat;
		$d               = array( 'category' => $cat->slug );
		$e               = array();

		$d['market'] = $cat->market;
		switch ( $cat->kind ) {
			case 'engine':
				self::engine( $cat, $in, $d, $e, $out );
				break;
			case 'lines':
				self::lines( $cat, $in, $d, $e );
				break;
			case 'weight':
				self::weight( $cat, $in, $d, $e );
				break;
			default:
				self::specs( $cat, $in, $d, $e );
		}
		self::market_fields( $cat, $in, $d, $e, $for_save );

		// Price.
		$price = self::num( $in['price'] ?? '' );
		$s     = Settings::all();
		if ( $price <= 0 ) {
			if ( $for_save ) {
				$e['price'] = __( 'Enter the price.', 'sm-products' );
			}
		} elseif ( $price < $s['min_price'] || $price > $s['max_price'] ) {
			$e['price'] = __( 'This price is outside the accepted range.', 'sm-products' );
		}
		$d['price'] = $price;
		if ( 'specs' !== $cat->kind ) {
			$d['price_unit'] = in_array( $in['price_unit'] ?? '', $cat->units, true ) ? $in['price_unit'] : $cat->units[0];
			if ( 'unit' === $d['price_unit'] && ( 'weight' === ( $d['qty_mode'] ?? '' ) || 'weight' === $cat->kind ) ) {
				$e['price_unit'] = __( 'A price per piece needs the number of pieces: choose "Number of bars" or a price per kg.', 'sm-products' );
			}
		} else {
			$d['price_unit'] = $d['unit'] ?? $cat->units[0];
		}

		// Totals (only when the technical part is complete).
		$total = 0.0;
		if ( $price > 0 && empty( $e ) ) {
			switch ( $d['price_unit'] ) {
				case 'kg':
					$total = $price * (float) ( $d['total_weight'] ?? 0 );
					break;
				case 'unit':
					$total = $price * (float) ( 'specs' !== $cat->kind ? ( $d['pieces'] ?? 0 ) : ( $d['quantity'] ?? 0 ) );
					break;
				case 'lot':
					$total = $price;
					break;
				default:
					$total = $price * (float) ( $d['quantity'] ?? 0 );
			}
		}
		$d['total_price']  = round( $total, 2 );
		$d['price_per_kg'] = ! empty( $d['total_weight'] ) && $total > 0 ? round( $total / $d['total_weight'], 2 ) : 0;

		// Common fields.
		$title = array(
			'fr' => mb_substr( trim( sanitize_text_field( $in['title']['fr'] ?? '' ) ), 0, 140 ),
			'ar' => mb_substr( trim( sanitize_text_field( $in['title']['ar'] ?? '' ) ), 0, 140 ),
		);
		if ( $for_save && '' === $title['fr'] && '' === $title['ar'] ) {
			$e['title'] = __( 'Give the product a title (in French or in Arabic).', 'sm-products' );
		}
		$desc = trim( sanitize_textarea_field( $in['description'] ?? '' ) );
		if ( mb_strlen( $desc ) > 4000 ) {
			$e['description'] = __( 'The description is too long (4000 characters at most).', 'sm-products' );
		}
		$wilaya = (string) ( $in['wilaya'] ?? '' );
		if ( $for_save && ! \SM\Core\Accounts\Wilayas::exists( $wilaya ) ) {
			$e['wilaya'] = __( 'Choose the wilaya where the product is.', 'sm-products' );
		}
		$d['wilaya']       = $wilaya;
		$out['title']      = $title;
		$out['description'] = $desc;

		$out['errors']  = $e;
		$out['data']    = $d;
		$out['summary'] = empty( $e ) || ! $for_save ? self::summary( $cat, $d ) : null;
		return $out;
	}

	/** Profiles, tubes, plates, bars: technical data and weight from SM Weight Engine. */
	private static function engine( $cat, array $in, array &$d, array &$e, array &$out ) {
		$engine = \SM\Core\Modules::service( 'weight' );
		$family = strtoupper( sanitize_text_field( $in['family'] ?? '' ) );
		if ( 1 === count( $cat->families ) ) {
			$family = $cat->families[0];
		}
		if ( ! $engine ) {
			$e['family'] = __( 'The weight engine is not active.', 'sm-products' );
			return;
		}
		if ( ! in_array( $family, $cat->families, true ) ) {
			$e['family'] = __( 'Choose the product type.', 'sm-products' );
			return;
		}
		$d['family']   = $family;
		$mode          = 'weight' === ( $in['qty_mode'] ?? '' ) ? 'weight' : 'bars';
		$d['qty_mode'] = $mode;
		$grade         = strtoupper( preg_replace( '/[^A-Za-z0-9+]/', '', (string) ( $in['grade'] ?? '' ) ) );
		if ( 'OTHER' === $grade ) {
			$grade = strtoupper( preg_replace( '/[^A-Za-z0-9+]/', '', (string) ( $in['grade_other'] ?? '' ) ) );
		}
		$d['grade'] = mb_substr( $grade, 0, 30 );

		$calc = array( 'family' => $family, 'mode' => $mode );
		if ( ! empty( $in['section'] ) ) {
			$calc['section'] = is_numeric( $in['section'] ) ? (int) $in['section'] : sanitize_text_field( (string) $in['section'] );
		} elseif ( ! empty( $in['dims'] ) && is_array( $in['dims'] ) && array_filter( $in['dims'], static fn( $v ) => '' !== (string) $v ) ) {
			$calc['dims'] = array_map( 'sanitize_text_field', $in['dims'] );
		} else {
			$e['section'] = __( 'Choose the size.', 'sm-products' );
			return;
		}
		if ( 'PLATE' === $family ) {
			$calc['width']  = self::num( $in['width'] ?? '' );
			$calc['length'] = self::num( $in['plate_length'] ?? '' );
		} else {
			$len            = (string) ( $in['length'] ?? '' );
			$calc['length'] = 'custom' === $len ? self::num( $in['length_custom'] ?? '' ) * 1000 : ( '' === $len ? sm_weight_setting( 'default_length' ) : self::num( $len ) );
		}
		if ( 'weight' === $mode ) {
			$calc['total_kg'] = $in['total_kg'] ?? '';
		} else {
			$calc['bars'] = $in['bars'] ?? '';
		}
		$has_qty = 'weight' === $mode ? '' !== trim( (string) ( $in['total_kg'] ?? '' ) ) : '' !== trim( (string) ( $in['bars'] ?? '' ) );
		if ( ! $has_qty ) {
			$e[ 'weight' === $mode ? 'total_kg' : 'bars' ] = 'weight' === $mode ? __( 'Enter the total weight.', 'sm-products' ) : __( 'Enter the number of pieces.', 'sm-products' );
			// Still describe the size for the preview.
			$probe = $engine->calculate( array_merge( $calc, array( 'mode' => 'bars', 'bars' => 1 ) ) );
			if ( ! is_wp_error( $probe ) ) {
				$out['calc'] = $probe;
				self::describe( $probe, $d );
			}
			return;
		}
		$r = $engine->calculate( $calc );
		if ( is_wp_error( $r ) ) {
			$code = $r->get_error_code();
			$map  = array( 'smw_section' => 'section', 'smw_dims' => 'section', 'smw_length' => 'length', 'smw_width' => 'width', 'smw_bars' => 'bars', 'smw_total' => 'total_kg' );
			$e[ $map[ $code ] ?? 'section' ] = $r->get_error_message();
			return;
		}
		$out['calc'] = $r;
		self::describe( $r, $d );
		$d['length_mm']   = (int) round( $r['length_mm'] );
		$d['width_mm']    = (int) round( $r['width_mm'] ?? 0 );
		$d['unit_weight'] = $r['per_piece_kg'];
		$d['engine_version'] = $r['engine'];
		$d['ref_version']    = $r['reference'];
		if ( 'weight' === $mode ) {
			$d['pieces']       = $r['bars_exact'];
			$d['total_weight'] = $r['total_kg'];
		} else {
			$d['pieces']       = $r['bars'];
			$d['total_weight'] = $r['total_kg'];
		}
		$source = 'actual' === ( $in['weight_source'] ?? '' ) ? 'actual' : 'theoretical';
		if ( 'actual' === $source && 'bars' === $mode ) {
			$w = self::num( $in['actual_weight'] ?? '' );
			if ( $w <= 0 ) {
				$e['actual_weight'] = __( 'Enter the weighed weight in kg.', 'sm-products' );
			} elseif ( $w > $r['total_kg'] * 1.5 || $w < $r['total_kg'] * 0.5 ) {
				/* translators: %s: theoretical weight in kg. */
				$e['actual_weight'] = sprintf( __( 'The weighed weight is very far from the theoretical %s kg. Check it.', 'sm-products' ), number_format( $r['total_kg'], 1, '.', ' ' ) );
			} else {
				$d['total_weight'] = round( $w, 3 );
			}
		}
		$d['weight_source'] = $source;
	}

	/** Copy designation and dimensions of the engine result. */
	private static function describe( array $r, array &$d ) {
		$d['section_id']  = (int) ( $r['section_id'] ?? 0 );
		$d['designation'] = mb_substr( $r['designation'], 0, 80 );
		$d['dims']        = $r['dims'];
	}

	/** Roofing, finishing parts, others: category fields and a quantity in the chosen unit. */
	private static function specs( $cat, array $in, array &$d, array &$e ) {
		self::fields( $cat, $in, $d, $e );
		$specs            = $d['specs'];
		$d['designation'] = mb_substr( (string) ( $specs['maker_type'] ?? ( $specs['item'] ?? '' ) ), 0, 80 );
		$d['unit']        = in_array( $in['unit'] ?? '', $cat->units, true ) ? $in['unit'] : $cat->units[0];
		$q                = self::num( $in['quantity'] ?? '' );
		if ( $q <= 0 ) {
			$e['quantity'] = __( 'Enter the available quantity.', 'sm-products' );
		}
		$d['quantity'] = $q;
		$d['pieces']   = 'unit' === $d['unit'] ? $q : 0;
		if ( 'kg' === $d['unit'] ) {
			$d['total_weight']  = $q;
			$d['weight_source'] = 'actual';
		} else {
			$w                  = self::num( $in['actual_weight'] ?? '' );
			$d['total_weight']  = $w > 0 ? $w : 0;
			$d['weight_source'] = $w > 0 ? 'actual' : 'none';
		}
	}

	/** Category fields (text, number, select). */
	private static function fields( $cat, array $in, array &$d, array &$e ) {
		$specs = array();
		foreach ( $cat->fields as $f ) {
			$v = $in['specs'][ $f['key'] ] ?? '';
			$v = is_string( $v ) ? trim( sanitize_text_field( $v ) ) : '';
			if ( '' === $v ) {
				if ( ! empty( $f['required'] ) ) {
					$e[ 'specs_' . $f['key'] ] = __( 'Required.', 'sm-products' );
				}
				continue;
			}
			if ( 'number' === $f['type'] ) {
				$n = self::num( $v );
				if ( $n <= 0 || $n > 1e6 ) {
					$e[ 'specs_' . $f['key'] ] = __( 'Enter a positive number.', 'sm-products' );
					continue;
				}
				$v = $n;
			} elseif ( 'select' === $f['type'] && ! in_array( $v, wp_list_pluck( $f['options'], 'value' ), true ) ) {
				$e[ 'specs_' . $f['key'] ] = __( 'Choose a value from the list.', 'sm-products' );
				continue;
			}
			$specs[ $f['key'] ] = is_string( $v ) ? mb_substr( $v, 0, 120 ) : $v;
		}
		$d['specs'] = $specs;
	}

	/**
	 * Inventory lot: several lines "type + size + number of pieces + length",
	 * each weighed by the engine; the lot weight is their sum.
	 */
	private static function lines( $cat, array $in, array &$d, array &$e ) {
		$engine = \SM\Core\Modules::service( 'weight' );
		if ( ! $engine ) {
			$e['lines'] = __( 'The weight engine is not active.', 'sm-products' );
			return;
		}
		$rows  = array_slice( array_values( (array) ( $in['lines'] ?? array() ) ), 0, 30 );
		$lines = array();
		$total = 0.0;
		$count = 0;
		foreach ( $rows as $i => $row ) {
			$row = array_map( static fn( $v ) => is_string( $v ) ? trim( sanitize_text_field( $v ) ) : '', (array) $row );
			if ( '' === ( $row['section'] ?? '' ) && '' === ( $row['qty'] ?? '' ) && '' === ( $row['length'] ?? '' ) ) {
				continue; // Empty row.
			}
			$family = strtoupper( $row['family'] ?? '' );
			if ( ! in_array( $family, $cat->families, true ) || '' === ( $row['section'] ?? '' ) ) {
				$e[ 'lines_' . $i ] = __( 'Choose the type and the size.', 'sm-products' );
				continue;
			}
			$qty = (int) self::num( $row['qty'] ?? '' );
			$len = self::num( $row['length'] ?? '' );
			if ( $qty < 1 || $len <= 0 ) {
				$e[ 'lines_' . $i ] = __( 'Enter the number of pieces and their length in metres.', 'sm-products' );
				continue;
			}
			$r = $engine->calculate( array( 'family' => $family, 'section' => is_numeric( $row['section'] ) ? (int) $row['section'] : $row['section'], 'bars' => $qty, 'length' => $len * 1000 ) );
			if ( is_wp_error( $r ) ) {
				$e[ 'lines_' . $i ] = $r->get_error_message();
				continue;
			}
			$lines[] = array(
				'family'      => $family,
				'section_id'  => (int) $r['section_id'],
				'designation' => $r['designation'],
				'qty'         => $qty,
				'length_mm'   => (int) round( $r['length_mm'] ),
				'kg_m'        => $r['mass_kg_m'],
				'total'       => $r['total_kg'],
			);
			$d['line_rows'][ $i ] = array( 'kg_m' => $r['mass_kg_m'], 'total' => $r['total_kg'], 'designation' => $r['designation'] ); // Form row => result.
			$total += $r['total_kg'];
			$count += $qty;
			$d['engine_version'] = $r['engine'];
			$d['ref_version']    = $r['reference'];
		}
		if ( ! $lines && ! array_filter( array_keys( $e ), static fn( $k ) => 0 === strpos( $k, 'lines_' ) ) ) {
			$e['lines'] = __( 'Describe the lot: at least one line with type, size, number of pieces and length.', 'sm-products' );
		}
		$d['lines']        = $lines;
		$d['qty_mode']     = 'bars';
		$d['pieces']       = $count;
		$d['total_weight'] = round( $total, 3 );
		if ( $lines ) {
			$names            = array_values( array_unique( wp_list_pluck( $lines, 'designation' ) ) );
			$d['designation'] = mb_substr( $names[0] . ( count( $names ) > 1 ? ' +' . ( count( $names ) - 1 ) : '' ), 0, 80 );
			$d['family']      = $lines[0]['family'];
			$d['section_id']  = $lines[0]['section_id'];
			$d['length_mm']   = max( wp_list_pluck( $lines, 'length_mm' ) ); // Longest piece, for the length filter.
		}
		$source = 'actual' === ( $in['weight_source'] ?? '' ) ? 'actual' : 'theoretical';
		if ( 'actual' === $source && $total > 0 ) {
			$w = self::num( $in['actual_weight'] ?? '' );
			if ( $w <= 0 ) {
				$e['actual_weight'] = __( 'Enter the weighed weight in kg.', 'sm-products' );
			} elseif ( $w > $total * 1.5 || $w < $total * 0.5 ) {
				/* translators: %s: theoretical weight in kg. */
				$e['actual_weight'] = sprintf( __( 'The weighed weight is very far from the theoretical %s kg. Check it.', 'sm-products' ), number_format( $total, 1, '.', ' ' ) );
			} else {
				$d['total_weight'] = round( $w, 3 );
			}
		}
		$d['weight_source'] = $source;
	}

	/** Mixed lots and scrap: category fields and the total weight (weighed or estimated). */
	private static function weight( $cat, array $in, array &$d, array &$e ) {
		self::fields( $cat, $in, $d, $e );
		$w = self::num( $in['total_kg'] ?? '' );
		if ( $w <= 0 ) {
			$e['total_kg'] = __( 'Enter the total weight.', 'sm-products' );
		} elseif ( $w > (float) sm_weight_setting( 'max_total_kg', 10000000 ) ) {
			$e['total_kg'] = __( 'This weight is outside the accepted range.', 'sm-products' );
		}
		$d['total_weight']  = $w;
		$d['qty_mode']      = 'weight';
		$d['weight_source'] = 'actual' === ( $in['weight_source'] ?? '' ) ? 'actual' : 'estimated';
		$d['designation']   = mb_substr( (string) ( $d['specs']['item'] ?? $cat->name_fr ), 0, 80 );
	}

	/** Condition and extra fields of the market (e.g. reuse: origin, surface, cutting). */
	private static function market_fields( $cat, array $in, array &$d, array &$e, $for_save ) {
		$m = Markets::get( $cat->market );
		if ( ! $m ) {
			return;
		}
		if ( ! empty( $m['conditions'] ) ) {
			$c = sanitize_key( (string) ( $in['condition'] ?? '' ) );
			if ( isset( $m['conditions'][ $c ] ) ) {
				$d['item_condition'] = $c;
			} elseif ( $for_save ) {
				$e['condition'] = __( 'Choose the condition.', 'sm-products' );
			}
		}
		$attrs = array();
		foreach ( (array) ( $m['fields'] ?? array() ) as $f ) {
			$v = sanitize_key( (string) ( $in['attrs'][ $f['key'] ] ?? '' ) );
			if ( 'bool' === $f['type'] ) {
				if ( '' !== $v ) {
					$attrs[ $f['key'] ] = '1' === $v || 'yes' === $v ? 1 : 0;
				}
			} elseif ( in_array( $v, wp_list_pluck( $f['options'], 'value' ), true ) ) {
				$attrs[ $f['key'] ] = $v;
			}
			if ( ! isset( $attrs[ $f['key'] ] ) && ! empty( $f['required'] ) && $for_save ) {
				$e[ 'attrs_' . $f['key'] ] = __( 'Required.', 'sm-products' );
			}
		}
		$d['attrs'] = $attrs;
		$extra      = apply_filters( 'sm_products_spec_check', array(), $cat, $d, $in );
		foreach ( (array) $extra as $k => $msg ) {
			$e[ $k ] = $msg;
		}
	}

	/** Figures shown to the seller before publishing (and to buyers). */
	private static function summary( $cat, array $d ) {
		return array(
			'designation'   => $d['designation'] ?? '',
			'total_weight'  => (float) ( $d['total_weight'] ?? 0 ),
			'weight_source' => $d['weight_source'] ?? '',
			'pieces'        => (float) ( $d['pieces'] ?? 0 ),
			'qty_mode'      => $d['qty_mode'] ?? '',
			'quantity'      => (float) ( $d['quantity'] ?? 0 ),
			'unit'          => $d['unit'] ?? '',
			'price'         => (float) ( $d['price'] ?? 0 ),
			'price_unit'    => $d['price_unit'] ?? '',
			'total_price'   => (float) ( $d['total_price'] ?? 0 ),
			'price_per_kg'  => (float) ( $d['price_per_kg'] ?? 0 ),
			'length_mm'     => (int) ( $d['length_mm'] ?? 0 ),
			'lines'         => $d['lines'] ?? array(),
			'line_rows'     => $d['line_rows'] ?? array(),
			'condition'     => $d['item_condition'] ?? '',
		);
	}

	/** "12,5" and "12 500" accepted. */
	public static function num( $v ) {
		$v = str_replace( array( ',', ' ', "\u{00A0}", "\u{202F}" ), array( '.', '', '', '' ), (string) $v );
		return is_numeric( $v ) ? (float) $v : 0.0;
	}
}
