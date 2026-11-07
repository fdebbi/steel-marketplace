<?php
/**
 * Array shape of a product for the theme and the REST API (data contract "listing").
 *
 * @package SM\Products
 */

namespace SM\Products;

use SM\Core\Accounts\Companies;
use SM\Core\Content\Media;
use SM\Core\Content\Translations;

defined( 'ABSPATH' ) || exit;

final class Presenter {

	/** @param object $p Product. @param array|null $texts Preloaded translations. */
	public static function listing( $p, $texts = null, $first_photo = null ) {
		$texts   = $texts ?? Translations::get( 'product', $p->id );
		$photos  = null === $first_photo ? Media::get( 'product', $p->id, 'photo' ) : array_filter( array( $first_photo ) );
		$company = Companies::get( $p->company_id );
		$cat     = Categories::get( $p->category );
		$title   = array_filter( (array) ( $texts['title'] ?? array() ) );
		if ( ! $title ) {
			$title = array( 'fr' => trim( $p->designation . ' ' . $p->grade ) );
		}
		$out = array(
			'id'             => $p->listing_id,
			'product_id'     => $p->id,
			'market'         => $p->market,
			'category'       => $p->category,
			'category_label' => $cat ? array( 'fr' => $cat->name_fr, 'ar' => $cat->name_ar ) : array(),
			'kind'           => $cat ? $cat->kind : 'specs',
			'title'          => $title,
			'description'    => array_filter( (array) ( $texts['description'] ?? array() ) ),
			'designation'    => $p->designation,
			'grade'          => $p->grade,
			'family'         => $p->family,
			'length_m'       => $p->length_mm ? $p->length_mm / 1000 : 0,
			'quantity'       => $p->pieces,
			'weight_kg'      => $p->total_weight,
			'weight_source'  => $p->weight_source,
			'price'          => $p->price,
			'price_unit'     => $p->price_unit,
			'price_per_kg'   => $p->price_per_kg,
			'total_price'    => $p->total_price,
			'wilaya'         => $p->wilaya,
			'company'        => $company ? $company->slug : '',
			'company_id'     => $p->company_id,
			'date'           => $p->published_at ? $p->published_at : $p->updated_at,
			'image'          => $photos ? (int) $photos[0] : 'demo:placeholder',
			'photos'         => array_map( 'intval', $photos ),
			'status'         => $p->status,
			'views'          => $p->views,
			'expires_at'     => $p->expires_at,
			'reject_reason'  => (string) $p->reject_reason,
			'condition'      => (string) $p->item_condition,
			'mode'           => $p->variant,
			'price_per_m2'   => 'm2' === $p->unit && $p->quantity > 0 && $p->total_price > 0 ? round( $p->total_price / $p->quantity, 2 ) : 0,
			'specs'          => array(),
		);
		/**
		 * Listing shape (cards, lists, REST); market plugins add their keys
		 * (sm-buildings: dimensions, area, includes).
		 *
		 * @param array  $out Listing.
		 * @param object $p   Product row.
		 */
		return apply_filters( 'sm_products_listing', $out, $p );
	}

	/** Details only needed on the product page. */
	public static function detail( $p ) {
		$out           = self::listing( $p );
		$out['sheets'] = array();
		foreach ( Media::get( 'product', $p->id, 'sheet' ) as $aid ) {
			$file            = get_attached_file( $aid );
			$out['sheets'][] = array(
				'id'   => $aid,
				'name' => get_the_title( $aid ) ? get_the_title( $aid ) : basename( (string) $file ),
				'url'  => wp_get_attachment_url( $aid ),
				'size' => $file && file_exists( $file ) ? filesize( $file ) : 0,
			);
		}
		$out['spec_rows'] = self::spec_rows( $p );
		$out['elements']  = self::elements( $p );
		$out['drawing']   = '';
		$out['drawing_dims'] = array();
		if ( $p->family && 'PLATE' !== $p->family && ! $p->lines ) {
			$engine = \SM\Core\Modules::service( 'weight' );
			$fam    = $engine ? \SM\WeightEngine\Catalogue::family( $p->family ) : null;
			if ( $fam && $p->dims ) {
				$out['drawing'] = $engine->drawing( $fam->shape, $p->dims, $p->designation );
			}
		}
		/**
		 * Product page data; market plugins add their notes (sm-reuse: engineer advice).
		 *
		 * @param array  $out Listing detail.
		 * @param object $p   Product row.
		 */
		return apply_filters( 'sm_products_listing_detail', $out, $p );
	}

	/** Label / value rows of the technical specification (values are safe HTML). */
	public static function spec_rows( $p ) {
		$rows = array();
		$tech = static fn( $s ) => '<bdi dir="ltr">' . esc_html( (string) $s ) . '</bdi>';
		$cat  = Categories::get( $p->category );
		if ( $cat && 'engine' === $cat->kind ) {
			$fam = class_exists( '\\SM\\WeightEngine\\Catalogue' ) ? \SM\WeightEngine\Catalogue::family( $p->family ) : null;
			if ( $fam ) {
				$rows[] = array( __( 'Product type', 'sm-products' ), esc_html( self::pick( array( 'fr' => $fam->name_fr, 'ar' => $fam->name_ar ) ) ) );
			}
			$rows[] = array( __( 'Designation', 'sm-products' ), $tech( $p->designation ) );
			$rows[] = array( __( 'Steel grade', 'sm-products' ), $p->grade ? $tech( $p->grade ) : esc_html__( 'Not specified', 'sm-products' ) );
			if ( $fam ) {
				$rows[] = array( __( 'Standard', 'sm-products' ), $tech( $fam->standard ) );
			}
			if ( $p->dims ) {
				$dims = array();
				foreach ( $p->dims as $k => $v ) {
					if ( 'SHS' === $p->family && 'h' === $k ) {
						continue;
					}
					$dims[] = ( 'd' === $k ? 'D' : $k ) . ' ' . self::n( $v, fmod( (float) $v, 1 ) ? 1 : 0 );
				}
				$rows[] = array( __( 'Dimensions', 'sm-products' ), $tech( implode( ' · ', $dims ) . ' mm' ) );
			}
			if ( 'PLATE' === $p->family ) {
				$rows[] = array( __( 'Plate size', 'sm-products' ), $tech( self::n( $p->width_mm ) . ' × ' . self::n( $p->length_mm ) . ' mm' ) );
			} elseif ( $p->length_mm ) {
				$rows[] = array( __( 'Bar length', 'sm-products' ), $tech( self::n( $p->length_mm / 1000, fmod( $p->length_mm, 1000 ) ? 2 : 0 ) . ' m' ) );
			}
			/* translators: %s: number of plates. */
			$fmt    = 'PLATE' === $p->family ? __( '%s plates', 'sm-products' ) : __( '%s bars', 'sm-products' ); // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
			$count  = 'weight' === $p->qty_mode ? '≈ ' . self::n( $p->pieces, 1 ) : self::n( $p->pieces );
			$rows[] = array( __( 'Quantity', 'sm-products' ), sprintf( esc_html( $fmt ), '<bdi dir="ltr">' . esc_html( $count ) . '</bdi>' ) );
			if ( $p->unit_weight && $p->length_mm && 'PLATE' !== $p->family ) {
				$rows[] = array( __( 'Weight per metre', 'sm-products' ), $tech( self::n( $p->unit_weight / ( $p->length_mm / 1000 ), 2 ) . ' kg/m' ) );
			}
		} elseif ( $cat && 'lines' === $cat->kind ) {
			$rows[] = array( __( 'Designation', 'sm-products' ), '<bdi dir="ltr">' . esc_html( implode( ', ', array_unique( wp_list_pluck( $p->lines, 'designation' ) ) ) ) . '</bdi>' );
			$rows[] = array( __( 'Steel grade', 'sm-products' ), $p->grade ? $tech( $p->grade ) : esc_html__( 'Not specified', 'sm-products' ) );
			/* translators: %s: number of pieces. */
			$rows[] = array( __( 'Quantity', 'sm-products' ), sprintf( esc_html__( '%s pieces in the lot', 'sm-products' ), '<bdi dir="ltr">' . esc_html( self::n( $p->pieces ) ) . '</bdi>' ) );
		} elseif ( $cat ) {
			if ( 'weight' === $cat->kind ) {
				$rows[] = array( __( 'Weight of the lot', 'sm-products' ), $tech( self::n( $p->total_weight ) . ' kg' ) );
			}
			foreach ( $cat->fields as $f ) {
				if ( ! isset( $p->specs[ $f['key'] ] ) ) {
					continue;
				}
				$v = $p->specs[ $f['key'] ];
				if ( 'select' === $f['type'] ) {
					foreach ( $f['options'] as $o ) {
						if ( $o['value'] === $v ) {
							$v = self::pick( $o );
						}
					}
					$html = esc_html( $v );
				} elseif ( 'number' === $f['type'] ) {
					$html = $tech( self::n( $v, fmod( (float) $v, 1 ) ? 2 : 0 ) . ( empty( $f['unit'] ) ? '' : ' ' . $f['unit'] ) );
				} else {
					$html = '<bdi>' . esc_html( $v ) . '</bdi>';
				}
				$rows[] = array( self::pick( $f['label'] ), $html );
			}
			if ( 'specs' === $cat->kind ) {
				$rows[] = array( __( 'Available quantity', 'sm-products' ), $tech( self::n( $p->quantity, fmod( $p->quantity, 1 ) ? 2 : 0 ) . ' ' . self::unit_label( $p->unit ) ) );
			}
		}
		// Condition and extra fields of the market.
		$m = Markets::get( $p->market );
		if ( $m && $p->item_condition && isset( $m['conditions'][ $p->item_condition ] ) ) {
			$rows[] = array( __( 'Condition', 'sm-products' ), esc_html( self::pick( $m['conditions'][ $p->item_condition ] ) ) );
		}
		foreach ( (array) ( $m['fields'] ?? array() ) as $f ) {
			if ( ! isset( $p->attrs[ $f['key'] ] ) || ( isset( $f['row'] ) && ! $f['row'] ) ) {
				continue;
			}
			$v = $p->attrs[ $f['key'] ];
			if ( 'bool' === $f['type'] ) {
				$v = $v ? __( 'Yes', 'sm-products' ) : __( 'No', 'sm-products' );
			} else {
				foreach ( $f['options'] as $o ) {
					if ( $o['value'] === $v ) {
						$v = self::pick( $o );
					}
				}
			}
			$rows[] = array( self::pick( $f['label'] ), esc_html( $v ) );
		}
		return $rows;
	}

	/** Inventory lines in the shape of the theme's element table. */
	public static function elements( $p ) {
		$out = array();
		$elements = (array) ( Markets::get( $p->market )['line_elements'] ?? array() );
		foreach ( (array) $p->lines as $l ) {
			$fam   = class_exists( '\\SM\\WeightEngine\\Catalogue' ) ? \SM\WeightEngine\Catalogue::family( $l['family'] ) : null;
			$type  = $fam ? array( 'fr' => $fam->name_fr, 'ar' => $fam->name_ar ) : $l['family'];
			if ( ! empty( $l['element'] ) && isset( $elements[ $l['element'] ] ) ) {
				$type = $elements[ $l['element'] ];
			}
			$out[] = array(
				'type'        => $type,
				'designation' => $l['designation'],
				'qty'         => (int) $l['qty'],
				'length'      => $l['length_mm'] / 1000,
				'unit_weight' => (float) $l['kg_m'],
				'total'       => (float) $l['total'],
			);
		}
		return $out;
	}

	public static function unit_label( $unit ) {
		$l = array(
			'kg'   => 'kg',
			'm2'   => 'm²',
			'ml'   => __( 'm (linear)', 'sm-products' ),
			'unit' => __( 'pieces', 'sm-products' ),
			'lot'  => __( 'lot', 'sm-products' ),
		);
		return $l[ $unit ] ?? $unit;
	}

	/** Title in the current language (for notifications and admin). */
	public static function title_text( $p ) {
		$t = Translations::get( 'product', $p->id );
		return self::pick( (array) ( $t['title'] ?? array() ) ) ?: trim( $p->designation . ' ' . $p->grade );
	}

	public static function pick( array $values ) {
		$lang = function_exists( 'sm_current_language' ) ? sm_current_language() : 'fr';
		return (string) ( $values[ $lang ] ?? '' ) !== '' ? $values[ $lang ] : (string) ( array_values( array_filter( $values ) )[0] ?? '' );
	}

	/** Western digits, comma decimals, narrow no-break space thousands (same as the theme). */
	public static function n( $v, $decimals = 0 ) {
		return number_format( (float) $v, $decimals, ',', "\u{202F}" );
	}
}
