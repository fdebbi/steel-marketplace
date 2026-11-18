<?php
/**
 * What the client needs, in detail (0.6.0).
 *
 * A request belongs to one of three parts: a technical study (design, calculation
 * note, 2D/3D drawings, tender documents), site follow-up, or an expertise. A
 * study of a steel building asks for its geometry: a frame building (portal
 * frames: hangar, factory, farm building, sports hall) or a building with
 * floors (offices, housing, shops). The answers are kept as JSON with the
 * request; the theme draws an indicative sketch from them.
 *
 * The client never chooses loads, sections or seismic / wind / snow zones: the
 * engineer sets them from the municipality and the codes (RPA, RNV).
 *
 * @package SM_Engineering
 */

namespace SM\Engineering;

defined( 'ABSPATH' ) || exit;

final class Brief {

	const VERSION = 1;

	/** Services of the follow-up and expertise parts; every other service is a study. */
	const SERVICE_PART = array(
		'site_follow' => 'follow',
		'expertise'   => 'expertise',
	);

	/** Types of project built with portal frames; the ones with floors. Other types: free description. */
	const PORTAL = array( 'industrial', 'storage', 'agricultural', 'livestock', 'public' );
	const LEVELS = array( 'commercial', 'offices', 'housing' );

	/** The three parts: code => [ title, text ]. */
	public static function parts() {
		return array(
			'study'     => array( __( 'Technical study', 'sm-engineering' ), __( 'Design, calculation note, 2D/3D drawings, specifications, bill of quantities and price schedule.', 'sm-engineering' ) ),
			'follow'    => array( __( 'Site follow-up and technical assistance', 'sm-engineering' ), __( 'Follow-up of fabrication, erection, acceptance on site.', 'sm-engineering' ) ),
			'expertise' => array( __( 'Expertise and technical inspection', 'sm-engineering' ), __( 'Existing building, damage, conformity, extension.', 'sm-engineering' ) ),
		);
	}

	public static function part_of_service( $code ) {
		return self::SERVICE_PART[ $code ] ?? 'study';
	}

	/** Shape of the building for a type of project: portal, levels or free. */
	public static function family( $project_type ) {
		$f = in_array( $project_type, self::PORTAL, true ) ? 'portal' : ( in_array( $project_type, self::LEVELS, true ) ? 'levels' : 'free' );
		return (string) apply_filters( 'sm_engineering_project_family', $f, $project_type );
	}

	/** Lists of choices: name => code => label. */
	public static function choices( $name ) {
		$c = array(
			'roof'      => array(
				'mono'   => __( 'Single slope', 'sm-engineering' ),
				'duo'    => __( 'Two slopes', 'sm-engineering' ),
				'multi'  => __( 'Several spans', 'sm-engineering' ),
				'curved' => __( 'Curved', 'sm-engineering' ),
				'flat'   => __( 'Flat', 'sm-engineering' ),
				'shed'   => __( 'Sawtooth (shed)', 'sm-engineering' ),
			),
			'use'       => array(
				'storage'  => __( 'Storage', 'sm-engineering' ),
				'workshop' => __( 'Workshop', 'sm-engineering' ),
				'livestock' => __( 'Livestock', 'sm-engineering' ),
				'sport'    => __( 'Sport', 'sm-engineering' ),
				'commerce' => __( 'Commerce', 'sm-engineering' ),
				'offices'  => __( 'Offices', 'sm-engineering' ),
				'housing'  => __( 'Housing', 'sm-engineering' ),
				'other'    => __( 'Other', 'sm-engineering' ),
			),
			'mezz_use'  => array(
				'offices' => __( 'Offices', 'sm-engineering' ),
				'storage' => __( 'Storage', 'sm-engineering' ),
				'technical' => __( 'Technical room', 'sm-engineering' ),
				'other'   => __( 'Other', 'sm-engineering' ),
			),
			'cladding'  => array( // Before 0.7.0 (roofing and walls together).
				'unknown'  => __( 'I do not know', 'sm-engineering' ),
				'sheet'    => __( 'Single steel sheet', 'sm-engineering' ),
				'sandwich' => __( 'Sandwich panel', 'sm-engineering' ),
				'other'    => __( 'Other', 'sm-engineering' ),
			),
			'roofing'   => array(
				'sheet'    => __( 'Single steel sheet', 'sm-engineering' ),
				'sandwich' => __( 'Sandwich panel', 'sm-engineering' ),
				'other'    => __( 'Other', 'sm-engineering' ),
				'unknown'  => __( 'I do not know', 'sm-engineering' ),
			),
			'walls'     => array(
				'sheet'    => __( 'Single steel sheet', 'sm-engineering' ),
				'sandwich' => __( 'Sandwich panel', 'sm-engineering' ),
				'wall'     => __( 'Wall', 'sm-engineering' ),
				'mixed'    => __( 'Mixed: wall + cladding', 'sm-engineering' ),
				'open'     => __( 'Open (no cladding)', 'sm-engineering' ),
				'unknown'  => __( 'I do not know', 'sm-engineering' ),
			),
			'walls_top' => array(
				'sandwich' => __( 'Sandwich panel', 'sm-engineering' ),
				'sheet'    => __( 'Single steel sheet', 'sm-engineering' ),
			),
			'long'      => array(
				'closed' => __( 'Closed (no door)', 'sm-engineering' ),
				'doors'  => __( 'Doors', 'sm-engineering' ),
			),
			'gable'     => array(
				'closed' => __( 'Closed (no door)', 'sm-engineering' ),
				'one'    => __( '1 centred', 'sm-engineering' ),
				'two'    => __( '2 spread', 'sm-engineering' ),
			),
			'floor_use' => array(
				'offices'   => __( 'Offices', 'sm-engineering' ),
				'housing'   => __( 'Housing', 'sm-engineering' ),
				'commerce'  => __( 'Commerce', 'sm-engineering' ),
				'storage'   => __( 'Storage', 'sm-engineering' ),
				'parking'   => __( 'Parking', 'sm-engineering' ),
				'technical' => __( 'Technical room', 'sm-engineering' ),
			),
			'floor'     => array(
				'unknown'   => __( 'I do not know', 'sm-engineering' ),
				'composite' => __( 'Composite steel deck', 'sm-engineering' ),
				'concrete'  => __( 'Concrete slab', 'sm-engineering' ),
				'other'     => __( 'Other', 'sm-engineering' ),
			),
			'soil'      => array(
				'unknown' => __( 'I do not know', 'sm-engineering' ),
				'yes'     => __( 'Yes (attach it)', 'sm-engineering' ),
				'no'      => __( 'No', 'sm-engineering' ),
			),
			'phases'    => array(
				'fabrication' => __( 'Fabrication in the workshop', 'sm-engineering' ),
				'erection'    => __( 'Erection', 'sm-engineering' ),
				'acceptance'  => __( 'Acceptance', 'sm-engineering' ),
			),
			'study_by'  => array(
				'platform' => __( 'An engineer of the platform', 'sm-engineering' ),
				'other'    => __( 'Another design office', 'sm-engineering' ),
				'none'     => __( 'No study', 'sm-engineering' ),
			),
			'object'    => array(
				'existing'   => __( 'Existing building', 'sm-engineering' ),
				'conformity' => __( 'Conformity / acceptance', 'sm-engineering' ),
				'extension'  => __( 'Extension / change of use', 'sm-engineering' ),
			),
			'findings'  => array(
				'cracks'    => __( 'Cracks', 'sm-engineering' ),
				'deform'    => __( 'Deformations', 'sm-engineering' ),
				'corrosion' => __( 'Corrosion', 'sm-engineering' ),
				'fire'      => __( 'Fire', 'sm-engineering' ),
				'other'     => __( 'Other', 'sm-engineering' ),
			),
			'urgency'   => array(
				'normal' => __( 'Normal', 'sm-engineering' ),
				'urgent' => __( 'Urgent (risk)', 'sm-engineering' ),
			),
		);
		return $c[ $name ] ?? array();
	}

	/** "7,5" or "7.5" → 7.5; '' → null; not a number → false. */
	private static function num( $v ) {
		$v = trim( str_replace( array( ',', ' ', "\u{00A0}" ), array( '.', '', '' ), (string) $v ) );
		if ( '' === $v ) {
			return null;
		}
		return is_numeric( $v ) ? round( (float) $v, 2 ) : false;
	}

	/**
	 * Read and check the answers of one part.
	 *
	 * @param string $part    study, follow or expertise.
	 * @param string $project Type of project.
	 * @param array  $in      The b[...] fields.
	 * @param array  $errors  Errors, by field (b_<key>).
	 * @return array The brief to keep.
	 */
	public static function validate( $part, $project, array $in, array &$errors ) {
		$out = array( 'v' => self::VERSION, 'part' => $part );
		$num = static function ( $key, $min, $max, $required, $label ) use ( $in, &$errors, &$out ) {
			$n = self::num( $in[ $key ] ?? '' );
			if ( null === $n ) {
				if ( $required ) {
					/* translators: %s: name of a dimension. */
					$errors[ 'b_' . $key ] = sprintf( __( 'Give the %s.', 'sm-engineering' ), $label );
				}
				return null;
			}
			if ( false === $n || $n < $min || $n > $max ) {
				/* translators: 1: name of a dimension, 2: minimum, 3: maximum. */
				$errors[ 'b_' . $key ] = sprintf( __( 'The %1$s must be a number between %2$s and %3$s.', 'sm-engineering' ), $label, self::fmt( $min ), self::fmt( $max ) );
				return null;
			}
			$out[ $key ] = $n;
			return $n;
		};
		$pick  = static function ( $key, $list, $default = '' ) use ( $in, &$out ) {
			$v = sanitize_key( (string) ( $in[ $key ] ?? '' ) );
			$v = isset( self::choices( $list )[ $v ] ) ? $v : $default;
			if ( '' !== $v ) {
				$out[ $key ] = $v;
			}
			return $v;
		};
		$many  = static function ( $key, $list ) use ( $in, &$out ) {
			$v = array_values( array_intersect( array_map( 'sanitize_key', (array) ( $in[ $key ] ?? array() ) ), array_keys( self::choices( $list ) ) ) );
			if ( $v ) {
				$out[ $key ] = $v;
			}
			return $v;
		};
		// 0.7.0: the commune is required (from the list of the wilaya when there is one); the map position is optional and private.
		$commune = \SM\Core\Accounts\Communes::find( (string) ( $in['_wilaya'] ?? '' ), (string) ( $in['commune'] ?? '' ) );
		if ( $commune ) {
			$out['commune']    = $commune[0];
			$out['commune_ar'] = $commune[1];
		} elseif ( '' !== (string) ( $in['_wilaya'] ?? '' ) ) {
			$errors['b_commune'] = '' === trim( (string) ( $in['commune'] ?? '' ) ) ? __( 'Choose the commune.', 'sm-engineering' ) : __( 'This commune is not in the chosen wilaya.', 'sm-engineering' );
		}
		$loc = \SM\Core\Accounts\Companies::parse_location( $in['lat'] ?? '', $in['lng'] ?? '' );
		if ( is_wp_error( $loc ) ) {
			$errors['b_location'] = $loc->get_error_message();
		} elseif ( $loc ) {
			$out['lat'] = $loc['lat'];
			$out['lng'] = $loc['lng'];
		}

		if ( 'study' === $part ) {
			$family        = self::family( $project );
			$out['family'] = $family;
			if ( 'portal' === $family ) {
				$w = $num( 'width', 3, 200, true, __( 'width (span)', 'sm-engineering' ) );
				$l = $num( 'length', 3, 600, true, __( 'length', 'sm-engineering' ) );
				$h = $num( 'col_h', 2, 40, true, __( 'height of the columns', 'sm-engineering' ) );
				$roof = $pick( 'roof', 'roof', 'duo' );
				$r    = $num( 'ridge_h', 2, 60, false, __( 'height at the ridge', 'sm-engineering' ) );
				if ( $h && $r && $r < $h ) {
					$errors['b_ridge_h'] = __( 'The ridge cannot be lower than the columns.', 'sm-engineering' );
					unset( $out['ridge_h'] );
				}
				if ( 'flat' === $roof ) {
					unset( $out['ridge_h'] );
				}
				$spans = $num( 'spans', 1, 20, false, __( 'number of spans', 'sm-engineering' ) );
				if ( null !== $spans ) {
					$out['spans'] = (int) $spans;
				}
				if ( 'multi' === $roof && ( $out['spans'] ?? 1 ) < 2 ) {
					$out['spans'] = 2;
				}
				$num( 'bay', 2, 15, false, __( 'spacing of the frames', 'sm-engineering' ) );
				if ( ! empty( $in['crane'] ) ) {
					$out['crane'] = 1;
					$num( 'crane_t', 0.25, 200, false, __( 'capacity of the crane', 'sm-engineering' ) );
					$rail = $num( 'crane_rail', 1, 40, false, __( 'height of the rail', 'sm-engineering' ) );
					if ( $rail && $h && $rail >= $h ) {
						$errors['b_crane_rail'] = __( 'The rail must be lower than the columns.', 'sm-engineering' );
					}
					$cn = $num( 'crane_n', 1, 10, false, __( 'number of cranes', 'sm-engineering' ) );
					if ( null !== $cn ) {
						$out['crane_n'] = (int) $cn;
					}
				}
				if ( ! empty( $in['mezz'] ) ) {
					$out['mezz'] = 1;
					$mw = $num( 'mezz_w', 1, 200, false, __( 'width of the mezzanine', 'sm-engineering' ) );
					$ml = $num( 'mezz_l', 1, 600, false, __( 'length of the mezzanine', 'sm-engineering' ) );
					$mh = $num( 'mezz_h', 1.5, 30, false, __( 'height of the mezzanine floor', 'sm-engineering' ) );
					if ( ( $mw && $w && $mw > $w ) || ( $ml && $l && $ml > $l ) ) {
						$errors['b_mezz_w'] = __( 'The mezzanine must fit in the building.', 'sm-engineering' );
					}
					if ( $mh && $h && $mh >= $h ) {
						$errors['b_mezz_h'] = __( 'The mezzanine floor must be lower than the columns.', 'sm-engineering' );
					}
					$pick( 'mezz_use', 'mezz_use' );
				}
				// 0.7.0: roofing and walls apart; a mixed wall has its height.
				$pick( 'roofing', 'roofing' );
				$walls = $pick( 'walls', 'walls' );
				if ( 'mixed' === $walls ) {
					$wh = $num( 'wall_h', 0.3, 30, true, __( 'height of the wall', 'sm-engineering' ) );
					if ( $wh && $h && $wh >= $h ) {
						$errors['b_wall_h'] = __( 'The wall must be lower than the columns.', 'sm-engineering' );
					}
					$pick( 'walls_top', 'walls_top', 'sandwich' );
				}
				// Doors on the 4 sides, centred in a bay (long sides) or on the gable.
				$bays = self::bays( $out );
				foreach ( array( 'front', 'back' ) as $side ) {
					if ( 'doors' !== sanitize_key( (string) ( $in[ 'd_' . $side ] ?? '' ) ) ) {
						continue;
					}
					$list = array_values( array_unique( array_filter( array_map( 'absint', (array) ( $in[ 'd_' . $side . '_bays' ] ?? array() ) ), static fn( $n ) => $n >= 1 && $n <= $bays ) ) );
					sort( $list );
					if ( ! $list ) {
						$errors[ 'b_d_' . $side ] = __( 'Tick the bays that have a door.', 'sm-engineering' );
						continue;
					}
					$out[ 'd_' . $side ]           = 'doors';
					$out[ 'd_' . $side . '_bays' ] = $list;
					$num( 'd_' . $side . '_w', 0.5, 40, false, __( 'width of the doors', 'sm-engineering' ) );
					$num( 'd_' . $side . '_h', 0.5, 30, false, __( 'height of the doors', 'sm-engineering' ) );
				}
				foreach ( array( 'left', 'right' ) as $side ) {
					$mode = sanitize_key( (string) ( $in[ 'd_' . $side ] ?? '' ) );
					if ( ! in_array( $mode, array( 'one', 'two' ), true ) ) {
						continue;
					}
					$out[ 'd_' . $side ] = $mode;
					$num( 'd_' . $side . '_w', 0.5, 40, false, __( 'width of the doors', 'sm-engineering' ) );
					$num( 'd_' . $side . '_h', 0.5, 30, false, __( 'height of the doors', 'sm-engineering' ) );
				}
			} elseif ( 'levels' === $family ) {
				$num( 'width', 3, 200, true, __( 'width', 'sm-engineering' ) );
				$num( 'length', 3, 600, true, __( 'length', 'sm-engineering' ) );
				// 0.7.0: one row per level, with its height and its use.
				$floors = array();
				foreach ( array_slice( array_values( (array) ( $in['floors'] ?? array() ) ), 0, 40 ) as $i => $row ) {
					$row = (array) $row;
					$fh  = self::num( $row['h'] ?? '' );
					$use = sanitize_key( (string) ( $row['use'] ?? '' ) );
					if ( null === $fh && '' === $use ) {
						continue;
					}
					if ( null === $fh || false === $fh || $fh < 2 || $fh > 12 ) {
						/* translators: %s: name of the level (Ground floor, F+1…). */
						$errors[ 'b_floors_' . $i ] = sprintf( __( '%s: give a height between 2 and 12 m.', 'sm-engineering' ), self::level_label( count( $floors ) ) );
						continue;
					}
					$floors[] = array( 'h' => $fh, 'use' => isset( self::choices( 'floor_use' )[ $use ] ) ? $use : '' );
				}
				if ( ! $floors ) {
					$errors['b_floors'] = __( 'Give the levels with their height.', 'sm-engineering' );
				} else {
					$out['floors'] = $floors;
					$out['levels'] = count( $floors );
				}
				$pick( 'floor', 'floor' );
			}
			$pick( 'use', 'use' );
			$pick( 'soil', 'soil' );
		} elseif ( 'follow' === $part ) {
			if ( ! $many( 'phases', 'phases' ) ) {
				$errors['b_phases'] = __( 'Tick the phases to follow.', 'sm-engineering' );
			}
			$v = $num( 'visits', 1, 500, false, __( 'number of visits', 'sm-engineering' ) );
			if ( null !== $v ) {
				$out['visits'] = (int) $v;
			}
			$num( 'months', 0.5, 60, false, __( 'duration of the works', 'sm-engineering' ) );
			$pick( 'study_by', 'study_by' );
			$from = absint( $in['from'] ?? 0 );
			if ( $from ) {
				$out['from'] = $from;
			}
		} elseif ( 'expertise' === $part ) {
			if ( '' === $pick( 'object', 'object' ) ) {
				$errors['b_object'] = __( 'Choose what is to inspect.', 'sm-engineering' );
			}
			$many( 'findings', 'findings' );
			$age = $num( 'age', 0, 200, false, __( 'age of the building', 'sm-engineering' ) );
			if ( null !== $age ) {
				$out['age'] = (int) $age;
			}
			$num( 'exp_w', 1, 300, false, __( 'width', 'sm-engineering' ) );
			$num( 'exp_l', 1, 600, false, __( 'length', 'sm-engineering' ) );
			$pick( 'urgency', 'urgency', 'normal' );
		}
		return $out;
	}

	/** Copy of a brief kept for a follow-up of the same project (geometry, place). */
	public static function carry( array $b ) {
		$keep = array( 'family', 'width', 'length', 'col_h', 'ridge_h', 'spans', 'bay', 'roof', 'crane', 'crane_t', 'crane_rail', 'crane_n', 'mezz', 'mezz_w', 'mezz_l', 'mezz_h', 'mezz_use', 'cladding', 'doors_n', 'door_w', 'door_h', 'levels', 'floor_h', 'floor', 'use', 'commune', 'commune_ar', 'lat', 'lng', 'roofing', 'walls', 'wall_h', 'walls_top', 'd_front', 'd_front_bays', 'd_front_w', 'd_front_h', 'd_back', 'd_back_bays', 'd_back_w', 'd_back_h', 'd_left', 'd_left_w', 'd_left_h', 'd_right', 'd_right_w', 'd_right_h', 'floors' );
		return array_intersect_key( $b, array_flip( $keep ) );
	}

	/** Figures worked out from the geometry (indicative). */
	public static function figures( array $b ) {
		$w = (float) ( $b['width'] ?? 0 );
		$l = (float) ( $b['length'] ?? 0 );
		if ( $w <= 0 || $l <= 0 ) {
			return array();
		}
		$out = array( 'area' => $w * $l );
		if ( 'portal' === ( $b['family'] ?? '' ) ) {
			$h = (float) ( $b['col_h'] ?? 0 );
			$r = max( $h, (float) ( $b['ridge_h'] ?? $h ) );
			$roof = $b['roof'] ?? 'duo';
			if ( $h > 0 ) {
				$rise          = 'flat' === $roof ? 0 : $r - $h;
				$k             = 'curved' === $roof ? 2 / 3 : ( in_array( $roof, array( 'mono', 'shed' ), true ) ? 0.5 : 0.5 );
				$out['volume'] = $w * $l * $h + $w * $l * $rise * $k;
				if ( $rise > 0 && in_array( $roof, array( 'duo', 'multi' ), true ) ) {
					$half         = $w / max( 1, (int) ( $b['spans'] ?? 1 ) ) / 2;
					$out['slope'] = $rise / $half * 100;
				} elseif ( $rise > 0 && 'mono' === $roof ) {
					$out['slope'] = $rise / $w * 100;
				}
			}
			$bay = (float) ( $b['bay'] ?? 0 );
			if ( $bay > 0 ) {
				$out['frames'] = (int) ceil( $l / $bay - 0.01 ) + 1;
			}
			if ( ! empty( $b['mezz'] ) && ! empty( $b['mezz_w'] ) && ! empty( $b['mezz_l'] ) ) {
				$out['mezz_area'] = (float) $b['mezz_w'] * (float) $b['mezz_l'];
			}
		} elseif ( 'levels' === ( $b['family'] ?? '' ) && ! empty( $b['levels'] ) ) {
			$out['floor_area'] = $w * $l * (int) $b['levels'];
			if ( ! empty( $b['floors'] ) ) {
				$out['height'] = array_sum( array_column( (array) $b['floors'], 'h' ) );
			} elseif ( ! empty( $b['floor_h'] ) ) {
				$out['height'] = (float) $b['floor_h'] * (int) $b['levels'];
			}
		}
		return $out;
	}

	/** 7.5 → "7,5" (the visitor's decimal separator). */
	public static function fmt( $n ) {
		$n = round( (float) $n, 2 );
		$d = fmod( $n, 1 ) ? ( fmod( $n * 10, 1 ) ? 2 : 1 ) : 0;
		return number_format( $n, $d, ',', "\u{202F}" ); // Western digits, French style, in every language (like the theme).
	}

	/** Number of bays along the length: from the spacing of the frames, 6 m when it is not given. */
	public static function bays( array $b ) {
		$l   = (float) ( $b['length'] ?? 0 );
		$bay = (float) ( $b['bay'] ?? 0 );
		$bay = $bay > 0 ? $bay : 6.0;
		return $l > 0 ? max( 1, (int) ceil( $l / $bay - 0.01 ) ) : 0;
	}

	/** Name of one level: Ground floor, F+1, F+2… (0 = ground floor). */
	public static function level_label( $i ) {
		/* translators: %d: floor number above the ground floor. */
		return 0 === (int) $i ? __( 'Ground floor', 'sm-engineering' ) : sprintf( __( 'F+%d', 'sm-engineering' ), (int) $i );
	}

	/** Name of a level: RDC, R+1, R+2… */
	public static function level_name( $levels ) {
		$levels = (int) $levels;
		/* translators: %d: number of floors above the ground floor. */
		return $levels <= 1 ? __( 'Ground floor only', 'sm-engineering' ) : sprintf( __( 'Ground floor + %d', 'sm-engineering' ), $levels - 1 );
	}

	/**
	 * The answers as rows [label, value], in the visitor's language.
	 *
	 * @return array list of [ label, value ]
	 */
	public static function rows( array $b ) {
		if ( empty( $b['part'] ) ) {
			return array();
		}
		$m    = static fn( $v ) => self::fmt( $v ) . "\u{00A0}m";
		$c    = static fn( $list, $v ) => self::choices( $list )[ $v ] ?? '';
		$rows = array();
		$part = self::parts()[ $b['part'] ] ?? null;
		if ( $part ) {
			$rows[] = array( __( 'Need', 'sm-engineering' ), $part[0] );
		}
		if ( ! empty( $b['from'] ) ) {
			$parent = Missions::get( (int) $b['from'] );
			if ( $parent ) {
				$rows[] = array( __( 'Follow-up of the study', 'sm-engineering' ), $parent->ref );
			}
		}
		$fam = $b['family'] ?? '';
		if ( 'portal' === $fam && ! empty( $b['width'] ) ) {
			$dims = self::fmt( $b['width'] ) . ' × ' . self::fmt( $b['length'] ) . "\u{00A0}m";
			if ( ! empty( $b['col_h'] ) ) {
				/* translators: %s: height. */
				$dims .= ' · ' . sprintf( __( 'columns %s', 'sm-engineering' ), $m( $b['col_h'] ) );
			}
			if ( ! empty( $b['ridge_h'] ) ) {
				/* translators: %s: height. */
				$dims .= ' · ' . sprintf( __( 'ridge %s', 'sm-engineering' ), $m( $b['ridge_h'] ) );
			}
			$rows[] = array( __( 'Dimensions', 'sm-engineering' ), $dims );
			$roof   = array_filter( array( $c( 'roof', $b['roof'] ?? '' ) ) );
			$spans  = (int) ( $b['spans'] ?? 1 );
			/* translators: %d: number of spans. */
			$roof[] = sprintf( _n( '%d span', '%d spans', $spans, 'sm-engineering' ), $spans );
			if ( ! empty( $b['bay'] ) ) {
				/* translators: %s: spacing of the frames. */
				$roof[] = sprintf( __( 'frames every %s', 'sm-engineering' ), $m( $b['bay'] ) );
			}
			$rows[] = array( __( 'Roof', 'sm-engineering' ), implode( ' · ', $roof ) );
			if ( ! empty( $b['crane'] ) ) {
				$cr = array();
				if ( ! empty( $b['crane_t'] ) ) {
					$cr[] = self::fmt( $b['crane_t'] ) . "\u{00A0}t";
				}
				if ( ! empty( $b['crane_rail'] ) ) {
					/* translators: %s: height of the rail. */
					$cr[] = sprintf( __( 'rail at %s', 'sm-engineering' ), $m( $b['crane_rail'] ) );
				}
				if ( ! empty( $b['crane_n'] ) ) {
					/* translators: %d: number of overhead cranes. */
					$cr[] = sprintf( _n( '%d crane', '%d cranes', (int) $b['crane_n'], 'sm-engineering' ), (int) $b['crane_n'] );
				}
				$rows[] = array( __( 'Overhead crane', 'sm-engineering' ), $cr ? implode( ' · ', $cr ) : __( 'Yes', 'sm-engineering' ) );
			}
			if ( ! empty( $b['mezz'] ) ) {
				$mz = array();
				if ( ! empty( $b['mezz_w'] ) && ! empty( $b['mezz_l'] ) ) {
					$mz[] = self::fmt( $b['mezz_w'] ) . ' × ' . self::fmt( $b['mezz_l'] ) . "\u{00A0}m";
				}
				if ( ! empty( $b['mezz_h'] ) ) {
					/* translators: %s: height of the floor. */
					$mz[] = sprintf( __( 'floor at %s', 'sm-engineering' ), $m( $b['mezz_h'] ) );
				}
				if ( ! empty( $b['mezz_use'] ) ) {
					$mz[] = $c( 'mezz_use', $b['mezz_use'] );
				}
				$rows[] = array( __( 'Mezzanine', 'sm-engineering' ), $mz ? implode( ' · ', $mz ) : __( 'Yes', 'sm-engineering' ) );
			}
			if ( ! empty( $b['roofing'] ) && 'unknown' !== $b['roofing'] ) {
				$rows[] = array( __( 'Roofing', 'sm-engineering' ), $c( 'roofing', $b['roofing'] ) );
			}
			if ( ! empty( $b['walls'] ) && 'unknown' !== $b['walls'] ) {
				$wl = $c( 'walls', $b['walls'] );
				if ( 'mixed' === $b['walls'] ) {
					/* translators: 1: height of the wall, 2: cladding above it. */
					$wl = sprintf( __( 'Wall of %1$s, %2$s above', 'sm-engineering' ), $m( $b['wall_h'] ?? 0 ), mb_strtolower( $c( 'walls_top', $b['walls_top'] ?? 'sandwich' ) ) );
				}
				$rows[] = array( __( 'Walls', 'sm-engineering' ), $wl );
			}
			$sides = array(
				'front' => __( 'Front long side', 'sm-engineering' ),
				'back'  => __( 'Back long side', 'sm-engineering' ),
				'left'  => __( 'Left gable', 'sm-engineering' ),
				'right' => __( 'Right gable', 'sm-engineering' ),
			);
			$doors = array();
			foreach ( $sides as $side => $label ) {
				$mode = $b[ 'd_' . $side ] ?? '';
				if ( ! $mode ) {
					continue;
				}
				$size = ! empty( $b[ 'd_' . $side . '_w' ] ) && ! empty( $b[ 'd_' . $side . '_h' ] ) ? ', ' . self::fmt( $b[ 'd_' . $side . '_w' ] ) . ' × ' . self::fmt( $b[ 'd_' . $side . '_h' ] ) . "\u{00A0}m" : '';
				if ( 'doors' === $mode ) {
					$bays = (array) ( $b[ 'd_' . $side . '_bays' ] ?? array() );
					/* translators: 1: number of doors, 2: list of bays (T3, T8). */
					/* translators: 1: side of the building, 2: its doors. */
					$doors[] = sprintf( __( '%1$s: %2$s', 'sm-engineering' ), $label, sprintf( _n( '%1$d door in bay %2$s', '%1$d doors in bays %2$s', count( $bays ), 'sm-engineering' ), count( $bays ), implode( ', ', array_map( static fn( $n ) => 'T' . $n, $bays ) ) ) . $size );
				} else {
					$doors[] = sprintf( __( '%1$s: %2$s', 'sm-engineering' ), $label, ( 'one' === $mode ? __( '1 centred door', 'sm-engineering' ) : __( '2 spread doors', 'sm-engineering' ) ) . $size );
				}
			}
			if ( isset( $b['walls'] ) || $doors ) {
				$rows[] = array( __( 'Doors', 'sm-engineering' ), $doors ? implode( ' ; ', $doors ) : __( 'None (all sides closed)', 'sm-engineering' ) );
			}
			$env = array();
			if ( ! empty( $b['cladding'] ) && 'unknown' !== $b['cladding'] ) {
				$env[] = $c( 'cladding', $b['cladding'] );
			}
			if ( ! empty( $b['doors_n'] ) ) {
				/* translators: %d: number of doors. */
				$d = sprintf( _n( '%d door', '%d doors', (int) $b['doors_n'], 'sm-engineering' ), (int) $b['doors_n'] );
				if ( ! empty( $b['door_w'] ) && ! empty( $b['door_h'] ) ) {
					$d .= ' ' . self::fmt( $b['door_w'] ) . ' × ' . self::fmt( $b['door_h'] ) . "\u{00A0}m";
				}
				$env[] = $d;
			}
			if ( $env ) {
				$rows[] = array( __( 'Envelope', 'sm-engineering' ), implode( ' · ', $env ) );
			}
		} elseif ( 'levels' === $fam && ! empty( $b['width'] ) ) {
			$dims = self::fmt( $b['width'] ) . ' × ' . self::fmt( $b['length'] ) . "\u{00A0}m";
			if ( ! empty( $b['levels'] ) ) {
				$dims .= ' · ' . self::level_name( $b['levels'] );
			}
			$fig = self::figures( $b );
			if ( ! empty( $b['floors'] ) && ! empty( $fig['height'] ) ) {
				/* translators: %s: total height. */
				$dims .= ' · ' . sprintf( __( 'total height %s', 'sm-engineering' ), $m( $fig['height'] ) );
			}
			if ( ! empty( $b['floor_h'] ) ) {
				/* translators: %s: height of a floor. */
				$dims .= ' · ' . sprintf( __( 'floors of %s', 'sm-engineering' ), $m( $b['floor_h'] ) );
			}
			$rows[] = array( __( 'Dimensions', 'sm-engineering' ), $dims );
			foreach ( (array) ( $b['floors'] ?? array() ) as $i => $fl ) {
				$rows[] = array( self::level_label( $i ), $m( $fl['h'] ) . ( ! empty( $fl['use'] ) ? ' · ' . $c( 'floor_use', $fl['use'] ) : '' ) );
			}
			if ( ! empty( $b['floor'] ) && 'unknown' !== $b['floor'] ) {
				$rows[] = array( __( 'Floors', 'sm-engineering' ), $c( 'floor', $b['floor'] ) );
			}
		}
		if ( ! empty( $b['use'] ) ) {
			$rows[] = array( __( 'Use of the building', 'sm-engineering' ), $c( 'use', $b['use'] ) );
		}
		$f = self::figures( $b );
		if ( $f ) {
			$fig = array( self::fmt( round( $f['area'] ) ) . "\u{00A0}m²" );
			if ( ! empty( $f['volume'] ) ) {
				$fig[] = '≈ ' . self::fmt( round( $f['volume'] ) ) . "\u{00A0}m³";
			}
			if ( ! empty( $f['floor_area'] ) && ( $f['floor_area'] - $f['area'] ) > 0.01 ) {
				/* translators: %s: floor area of all levels. */
				$fig[] = sprintf( __( '%s of floors in all', 'sm-engineering' ), self::fmt( round( $f['floor_area'] ) ) . "\u{00A0}m²" );
			}
			if ( ! empty( $f['frames'] ) ) {
				/* translators: %d: number of portal frames. */
				$fig[] = sprintf( _n( '%d portal frame', '%d portal frames', $f['frames'], 'sm-engineering' ), $f['frames'] );
			}
			$rows[] = array( __( 'Worked out', 'sm-engineering' ), implode( ' · ', $fig ) );
		}
		if ( ! empty( $b['soil'] ) ) {
			$rows[] = array( __( 'Soil study', 'sm-engineering' ), $c( 'soil', $b['soil'] ) );
		}
		if ( 'follow' === $b['part'] ) {
			if ( ! empty( $b['phases'] ) ) {
				$rows[] = array( __( 'Phases', 'sm-engineering' ), implode( ' · ', array_map( static fn( $p ) => $c( 'phases', $p ), $b['phases'] ) ) );
			}
			if ( ! empty( $b['visits'] ) ) {
				$rows[] = array( __( 'Visits', 'sm-engineering' ), (string) (int) $b['visits'] );
			}
			if ( ! empty( $b['months'] ) ) {
				/* translators: %s: number of months. */
				$rows[] = array( __( 'Duration of the works', 'sm-engineering' ), sprintf( __( '%s months', 'sm-engineering' ), self::fmt( $b['months'] ) ) );
			}
			if ( ! empty( $b['study_by'] ) ) {
				$rows[] = array( __( 'Study made by', 'sm-engineering' ), $c( 'study_by', $b['study_by'] ) );
			}
		}
		if ( 'expertise' === $b['part'] ) {
			if ( ! empty( $b['object'] ) ) {
				$rows[] = array( __( 'To inspect', 'sm-engineering' ), $c( 'object', $b['object'] ) );
			}
			if ( ! empty( $b['findings'] ) ) {
				$rows[] = array( __( 'Findings', 'sm-engineering' ), implode( ' · ', array_map( static fn( $p ) => $c( 'findings', $p ), $b['findings'] ) ) );
			}
			if ( isset( $b['age'] ) ) {
				/* translators: %d: age in years. */
				$rows[] = array( __( 'Age of the building', 'sm-engineering' ), sprintf( _n( '%d year', '%d years', (int) $b['age'], 'sm-engineering' ), (int) $b['age'] ) );
			}
			if ( ! empty( $b['exp_w'] ) && ! empty( $b['exp_l'] ) ) {
				$rows[] = array( __( 'Approximate dimensions', 'sm-engineering' ), self::fmt( $b['exp_w'] ) . ' × ' . self::fmt( $b['exp_l'] ) . "\u{00A0}m" );
			}
			if ( ! empty( $b['urgency'] ) ) {
				$rows[] = array( __( 'Urgency', 'sm-engineering' ), $c( 'urgency', $b['urgency'] ) );
			}
		}
		if ( ! empty( $b['commune'] ) ) {
			$ar     = 0 === strpos( determine_locale(), 'ar' ) && ! empty( $b['commune_ar'] );
			$rows[] = array( __( 'Municipality', 'sm-engineering' ), $ar ? $b['commune_ar'] : $b['commune'] );
		}
		if ( isset( $b['lat'], $b['lng'] ) ) {
			$rows[] = array( __( 'Position on the map', 'sm-engineering' ), number_format( (float) $b['lat'], 5, '.', '' ) . ', ' . number_format( (float) $b['lng'], 5, '.', '' ) );
		}
		return array_values( array_filter( $rows, static fn( $r ) => '' !== (string) $r[1] ) );
	}

	/** Can the sketch be drawn? (study of a frame building or of a building with floors). */
	public static function drawable( array $b ) {
		return in_array( $b['family'] ?? '', array( 'portal', 'levels' ), true ) && ! empty( $b['width'] ) && ! empty( $b['length'] ) && ( ! empty( $b['col_h'] ) || ! empty( $b['levels'] ) || ! empty( $b['floors'] ) );
	}

	/** Automatic title when the client gives none. */
	public static function title( array $b, $project_label ) {
		$parts = array(
			'follow'    => __( 'Follow-up', 'sm-engineering' ),
			'expertise' => __( 'Expertise', 'sm-engineering' ),
		);
		$t = trim( (string) $project_label );
		if ( ! empty( $b['width'] ) && ! empty( $b['length'] ) ) {
			$t .= ' ' . self::fmt( $b['width'] ) . ' × ' . self::fmt( $b['length'] ) . "\u{00A0}m";
		} elseif ( ! empty( $b['exp_w'] ) && ! empty( $b['exp_l'] ) ) {
			$t .= ' ' . self::fmt( $b['exp_w'] ) . ' × ' . self::fmt( $b['exp_l'] ) . "\u{00A0}m";
		}
		if ( isset( $parts[ $b['part'] ?? '' ] ) ) {
			$t = $parts[ $b['part'] ] . ' — ' . $t;
		}
		return mb_substr( trim( $t ), 0, 200 );
	}
	/** Words of the indicative sketch (study-sketch.js), in the visitor's language (0.8.0: moved from the theme). */
	public static function sketch_i18n() {
		return array(
			'span'     => __( 'Span %s', 'sm-engineering' ),
			'columns'  => __( 'Columns %s', 'sm-engineering' ),
			'ridge'    => __( 'Ridge %s', 'sm-engineering' ),
			'length'   => __( 'Length %s', 'sm-engineering' ),
			'width'    => __( 'Width %s', 'sm-engineering' ),
			'height'   => __( 'Height %s', 'sm-engineering' ),
			'slope'    => __( 'slope ≈ %s %%', 'sm-engineering' ),
			'mezz'     => __( 'Mezzanine +%s', 'sm-engineering' ),
			'mezzPlan' => __( 'Mezzanine %s', 'sm-engineering' ),
			'crane'    => __( 'Overhead crane %1$s · rail +%2$s', 'sm-engineering' ),
			'craneOnly' => __( 'Overhead crane', 'sm-engineering' ),
			'doors'    => __( '%1$d doors %2$s', 'sm-engineering' ),
			'door'     => __( '1 door %s', 'sm-engineering' ),
			'frames'   => __( '%1$d portal frames · %2$d bays of %3$s', 'sm-engineering' ),
			'noBay'    => __( 'Spacing of the frames: set by the engineer', 'sm-engineering' ),
			'ground'   => __( 'GF', 'sm-engineering' ),
			'level'    => __( 'F+%d', 'sm-engineering' ),
			'area'     => __( 'Floor area', 'sm-engineering' ),
			'volume'   => __( 'Volume', 'sm-engineering' ),
			'mezzArea' => __( 'Mezzanine', 'sm-engineering' ),
			'nFrames'  => __( 'Portal frames', 'sm-engineering' ),
			'floors'   => __( 'Floor area of all levels', 'sm-engineering' ),
			'total'    => __( 'Total height', 'sm-engineering' ),
			'front'    => __( 'Front view (left gable)', 'sm-engineering' ),
			'frontLevels' => __( 'Front view', 'sm-engineering' ),
			'plan'     => __( 'Plan view', 'sm-engineering' ),
			'empty'    => __( 'Give the width, the length and the height: the sketch draws itself.', 'sm-engineering' ),
			'wall'       => __( 'Wall %s', 'sm-engineering' ),
			'front_side' => __( 'Front long side', 'sm-engineering' ),
			'back_side'  => __( 'Back long side', 'sm-engineering' ),
			'uses'       => self::choices( 'floor_use' ),
		);
	}

	/**
	 * The sketch script, registered for the site and the administration (0.8.0).
	 * The theme enqueues it on the request form and the study pages.
	 */
	public static function register_script() {
		wp_register_script( 'sm-study-sketch', plugins_url( 'assets/js/study-sketch.js', SM_ENGINEERING_FILE ), array(), SM_ENGINEERING_VERSION . '-' . (int) @filemtime( SM_ENGINEERING_DIR . '/assets/js/study-sketch.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}

}
