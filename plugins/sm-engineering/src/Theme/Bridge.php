<?php
/**
 * Data for the sm-marketplace theme:
 *
 *   engineers         public list of approved partner engineers (no contacts)
 *   engineer_apply    the application page (guest, rejected engineer, or not allowed)
 *   mission_form      the study request page
 *   studies           the client's study requests (list, or one with ['id'])
 *   engineer_account  the engineer's dashboard: profile, missions, billing, files
 *
 * @package SM\Engineering\Theme
 */

namespace SM\Engineering\Theme;

use SM\Core\Accounts\Companies;
use SM\Core\Routing\Routes as CoreRoutes;
use SM\Engineering\Apply;
use SM\Engineering\Engineers;
use SM\Engineering\Files;
use SM\Engineering\Form;
use SM\Engineering\Messages;
use SM\Engineering\Missions;
use SM\Engineering\Payments;
use SM\Engineering\ProjectTypes;
use SM\Engineering\Services;
use SM\Engineering\Settings;

defined( 'ABSPATH' ) || exit;

final class Bridge {

	public static function init() {
		foreach ( array( 'engineers', 'engineer_apply', 'mission_form', 'studies', 'engineer_account' ) as $r ) {
			add_filter( 'sm_theme_data_' . $r, array( __CLASS__, $r ), 10, 2 );
		}
		add_filter( 'sm_theme_data_account', array( __CLASS__, 'account' ), 20 );
	}

	/** Mission types for selects: code => label (current language). */
	public static function type_options( $all = false ) {
		$ar = 'ar' === sm_current_language();
		return array_map( static fn( $t ) => $ar ? $t['ar'] : $t['fr'], Settings::types( $all ) );
	}

	/**
	 * Active engineering services in the visitor's language, for the engineering
	 * page and the request form.
	 *
	 * @return array list of { code, title, items: string[], image: url ('' = drawing of the theme), demo, url }
	 */
	public static function services() {
		$out = array();
		foreach ( Services::all() as $code => $s ) {
			$out[] = array(
				'code'  => $code,
				'title' => Services::text( $s ),
				'items' => array_map( array( Services::class, 'text' ), $s['items'] ),
				'image' => $s['image'] ? (string) wp_get_attachment_image_url( $s['image'], 'large' ) : '',
				'alt'   => $s['image'] ? (string) get_post_meta( $s['image'], '_wp_attachment_image_alt', true ) : '',
				'demo'  => $s['demo'],
				'url'   => CoreRoutes::url( 'mission-request', array( 'type' => $code ) ),
			);
		}
		return $out;
	}

	/** Engineer status for the dashboard and the account resource. */
	public static function account( $result ) {
		if ( ! is_array( $result ) || 'engineer' !== ( $result['class'] ?? '' ) ) {
			return $result;
		}
		$e = Engineers::for_user( get_current_user_id() );
		$result['engineer'] = $e ? array( 'status' => $e->status, 'reason' => (string) $e->reject_reason, 'available' => (bool) $e->available ) : array( 'status' => 'pending', 'reason' => '', 'available' => false );
		return $result;
	}

	public static function engineers( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$enabled = (bool) Settings::get( 'public_list' );
		$items   = array();
		$total   = 0;
		if ( $enabled ) {
			$q     = Engineers::query(
				array(
					'status'    => 'approved',
					'specialty' => sanitize_key( (string) ( $args['specialty'] ?? '' ) ),
					'wilaya'    => preg_replace( '/\D/', '', (string) ( $args['wilaya'] ?? '' ) ),
					'q'         => sanitize_text_field( (string) ( $args['q'] ?? '' ) ),
					'per_page'  => (int) ( $args['per_page'] ?? 60 ),
				)
			);
			$items = array_values( array_filter( array_map( array( Engineers::class, 'public_shape' ), $q['items'] ) ) );
			$total = $q['total'];
		}
		return array(
			'enabled'     => $enabled,
			'services'    => self::services(),
			'items'       => $items,
			'total'       => $total,
			'types'       => self::type_options(),
			'apply_url'   => CoreRoutes::url( 'engineer-apply' ),
			'request_url' => CoreRoutes::url( 'mission-request' ),
			'open'        => (bool) Settings::get( 'applications_open' ),
		);
	}

	private static function profile_values( $e ) {
		return $e ? array(
			'title'       => $e->title,
			'diploma'     => $e->diploma,
			'school'      => $e->school,
			'grad_year'   => $e->grad_year ? (string) $e->grad_year : '',
			'experience'  => (string) $e->experience,
			'specialties' => $e->specialties,
			'bio'         => (string) $e->bio,
			'available'   => (bool) $e->available,
		) : array();
	}

	public static function engineer_apply( $result ) {
		if ( null !== $result ) {
			return $result;
		}
		$user  = get_current_user_id();
		$e     = $user ? Engineers::for_user( $user ) : null;
		$state = 'guest';
		if ( $user ) {
			$state = $e ? ( 'rejected' === $e->status ? 'reapply' : 'engineer' ) : ( Companies::for_user( $user ) ? 'other' : 'guest' );
		}
		$docs = array();
		foreach ( Apply::documents() as $k => list( $label, $required ) ) {
			$docs[ $k ] = array( 'label' => $label, 'required' => $required );
		}
		return array(
			'open'      => (bool) Settings::get( 'applications_open' ),
			'state'     => $state,
			'status'    => $e ? $e->status : '',
			'reason'    => $e ? (string) $e->reject_reason : '',
			'values'    => self::profile_values( $e ),
			'types'     => self::type_options(),
			'documents' => $docs,
			'file_mb'   => (int) Settings::get( 'file_mb' ),
			'account'   => CoreRoutes::url( 'account' ),
		);
	}

	public static function mission_form( $result, $args ) {
		if ( null !== $result ) {
			return $result;
		}
		$pref = absint( $args['engineer'] ?? 0 );
		$pe   = $pref ? Engineers::get( $pref ) : null;
		$list = array();
		foreach ( Engineers::query( array( 'status' => 'approved', 'per_page' => 200 ) )['items'] as $e ) {
			$c = Companies::get( $e->company_id );
			if ( $c ) {
				$list[ $e->company_id ] = $c->name . ( $e->title ? ' — ' . $e->title : '' );
			}
		}
		return array(
			'block'      => Form::request_block(),
			'types'      => self::type_options(),
			'services'   => self::services(),
			'projects'   => ProjectTypes::options(),
			'type'       => sanitize_key( (string) ( $args['type'] ?? '' ) ),
			'preferred'  => ( $pe && 'approved' === $pe->status ) ? Engineers::public_shape( $pe ) : null,
			// The list of partner engineers is offered only when it is public (setting).
			'engineers'  => Settings::get( 'public_list' ) ? $list : array(),
			'max_files'  => (int) Settings::get( 'max_files' ),
			'file_mb'    => (int) Settings::get( 'file_mb' ),
			'extensions' => Settings::extensions(),
			'list_url'   => CoreRoutes::url( 'engineers' ),
		);
	}

	private static function files( $m, $viewer, $kind ) {
		return array_map( array( Files::class, 'shape' ), Files::for_mission( $m->id, $viewer, $kind ) );
	}

	/** A mission as the client sees it (no engineer, no fee). */
	public static function client_shape( $m, $detail = false ) {
		$totals = Missions::totals( $m );
		$key    = $m->status;
		if ( 'accepted' === $m->status && $totals['advance_due'] ) {
			$key = 'accepted_pay';
		} elseif ( 'delivered' === $m->status && $totals['balance_due'] ) {
			$key = 'delivered_pay';
		}
		list( $tone, $label ) = Missions::client_status( $key );
		$out = array(
			'id'          => $m->id,
			'ref'         => $m->ref,
			'title'       => $m->title,
			'type'        => Missions::services_label( $m ),
			'project'     => ProjectTypes::label( $m->project_type ),
			'prestations' => Missions::prestations( $m ),
			'status'      => $m->status,
			'tone'        => $tone,
			'label'       => $label,
			'wilaya'      => $m->wilaya,
			'created'     => $m->created_at,
			'updated'     => $m->updated_at,
			'amount'      => $m->quote_amount > 0 && ! in_array( $m->status, array( 'submitted', 'declined' ), true ) ? $m->quote_amount : 0,
			'url'         => CoreRoutes::url( 'account', array( 'view' => 'studies', 'id' => $m->id ) ),
		);
		if ( ! $detail ) {
			return $out;
		}
		$expired = $m->quote_valid_until && $m->quote_valid_until < gmdate( 'Y-m-d' );
		return $out + array(
			'description' => (string) $m->description,
			'needed_by'   => $m->needed_by,
			'budget'      => $m->budget,
			'quote'       => $m->quote_amount > 0 ? array( 'amount' => $m->quote_amount, 'days' => $m->quote_days, 'message' => (string) $m->quote_message, 'valid_until' => $m->quote_valid_until, 'expired' => $expired, 'totals' => $totals, 'valid_days' => (int) Settings::get( 'quote_valid_days' ) ) : null,
			'lines'       => array_map( static fn( $l ) => array( 'title' => $l['title'], 'items' => $l['labels'], 'amount' => $l['amount'] ), Missions::lines( $m ) ),
			'payments'    => in_array( $m->status, array( 'accepted', 'assigned', 'in_progress', 'review', 'delivered', 'closed' ), true ) && ! $totals['legacy'] ? Payments::state( $m ) : null,
			'reason'      => in_array( $m->status, array( 'declined' ), true ) ? (string) $m->reason : '',
			'paid'        => (bool) $m->client_paid_at,
			'brief'       => self::files( $m, 'client', 'brief' ),
			'deliverables' => in_array( $m->status, array( 'delivered', 'closed' ), true ) ? self::files( $m, 'client', 'deliverable' ) : array(),
			'platform'    => self::files( $m, 'client', 'platform' ),
			'messages'    => Messages::shape( Messages::for_mission( $m->id, 'client' ), get_current_user_id() ),
			'can'         => array(
				'accept'  => 'quoted' === $m->status && ! $expired,
				'pay'     => $totals['advance_due'] ? 'advance' : ( $totals['balance_due'] ? 'balance' : '' ),
				'refuse'  => 'quoted' === $m->status,
				'cancel'  => in_array( $m->status, array( 'submitted', 'quoted' ), true ),
				'confirm' => 'delivered' === $m->status && $totals['balance_paid'],
				'files'   => in_array( $m->status, Missions::OPEN, true ),
				'write'   => true,
			),
			'steps'       => self::client_steps( $m ),
		);
	}

	/** Progress shown to the client. */
	private static function client_steps( $m ) {
		$order = array( 'submitted' => 0, 'quoted' => 1, 'accepted' => 2, 'assigned' => 2, 'in_progress' => 3, 'review' => 3, 'delivered' => 4, 'closed' => 4 );
		$at    = $order[ $m->status ] ?? -1;
		$names = array( __( 'Request sent', 'sm-engineering' ), __( 'Quote', 'sm-engineering' ), __( 'Accepted', 'sm-engineering' ), __( 'Study in progress', 'sm-engineering' ), __( 'Delivered', 'sm-engineering' ) );
		$out   = array();
		foreach ( $names as $i => $n ) {
			$out[] = array( 'label' => $n, 'state' => $at < 0 ? 'off' : ( $i < $at || ( 4 === $i && 4 === $at ) ? 'done' : ( $i === $at ? 'current' : 'todo' ) ) );
		}
		return $out;
	}

	public static function studies( $result, $args ) {
		if ( null !== $result || ! is_user_logged_in() ) {
			return $result;
		}
		$co = Companies::for_user( get_current_user_id() );
		if ( ! $co || 'engineer' === $co->account_class ) {
			return null;
		}
		$id = absint( $args['id'] ?? 0 );
		if ( $id ) {
			$m = Missions::get( $id );
			return ( $m && (int) $m->client_id === (int) $co->id ) ? array( 'mission' => self::client_shape( $m, true ) ) : array( 'mission' => null );
		}
		$q = Missions::query( array( 'client' => $co->id, 'per_page' => 100 ) );
		return array(
			'items'       => array_map( static fn( $m ) => self::client_shape( $m ), $q['items'] ),
			'request_url' => CoreRoutes::url( 'mission-request' ),
			'block'       => Form::request_block(),
		);
	}

	/** A mission as the engineer sees it (no client identity, no client price). */
	public static function engineer_shape( $m, $detail = false ) {
		list( $tone, $label ) = Missions::engineer_status( $m->status );
		$out = array(
			'id'      => $m->id,
			'ref'     => $m->ref,
			'title'   => $m->title,
			'type'    => Missions::services_label( $m ),
			'project' => ProjectTypes::label( $m->project_type ),
			'prestations' => Missions::prestations( $m ),
			// Services and prestations, without the client's prices.
			'lines'   => array_map( static fn( $l ) => array( 'title' => $l['title'], 'items' => $l['labels'] ), Missions::lines( $m ) ),
			'status'  => $m->status,
			'tone'    => $tone,
			'label'   => $label,
			'wilaya'  => $m->wilaya,
			'fee'     => $m->engineer_fee,
			'days'    => $m->engineer_days,
			'fee_status' => $m->fee_status,
			'assigned'   => $m->assigned_at,
			'url'     => CoreRoutes::url( 'account', array( 'view' => 'missions', 'id' => $m->id ) ),
		);
		if ( ! $detail ) {
			return $out;
		}
		return $out + array(
			'description'  => (string) $m->description,
			'needed_by'    => $m->needed_by,
			'note'         => (string) $m->engineer_note,
			'brief'        => self::files( $m, 'engineer', 'brief' ),
			'platform'     => self::files( $m, 'engineer', 'platform' ),
			'deliverables' => self::files( $m, 'engineer', 'deliverable' ),
			'invoices'     => self::files( $m, 'engineer', 'invoice' ),
			'messages'     => Messages::shape( Messages::for_mission( $m->id, 'engineer' ), get_current_user_id() ),
			'can'          => array(
				'take'    => 'assigned' === $m->status,
				'giveup'  => 'assigned' === $m->status,
				'upload'  => 'in_progress' === $m->status,
				'submit'  => 'in_progress' === $m->status,
				'invoice' => in_array( $m->fee_status, array( 'planned', 'due' ), true ),
				'write'   => true,
			),
		);
	}

	public static function engineer_account( $result, $args ) {
		if ( null !== $result || ! is_user_logged_in() ) {
			return $result;
		}
		$e = Engineers::for_user( get_current_user_id() );
		if ( ! $e ) {
			return null;
		}
		$c   = Companies::get( $e->company_id );
		$out = array(
			'status'    => $e->status,
			'reason'    => (string) $e->reject_reason,
			'available' => (bool) $e->available,
			'profile'   => self::profile_values( $e ),
			'public'    => Engineers::public_shape( $e ),
			'types'     => self::type_options(),
			'photo'     => $c ? Companies::logo_url( $c, 'medium' ) : '',
			'photo_id'  => $c ? (int) $c->logo_id : 0,
			'apply_url' => CoreRoutes::url( 'engineer-apply' ),
		);
		$section = sanitize_key( (string) ( $args['section'] ?? 'missions' ) );
		$id      = absint( $args['id'] ?? 0 );
		if ( $id ) {
			$m              = Missions::get( $id );
			$out['mission'] = ( $m && $m->engineer_id === (int) $e->company_id ) ? self::engineer_shape( $m, true ) : null;
			return $out;
		}
		$all    = Missions::query( array( 'engineer' => $e->company_id, 'per_page' => 200 ) )['items'];
		$groups = array( 'offered' => array(), 'current' => array(), 'done' => array() );
		$money  = array( 'planned' => 0, 'due' => 0, 'paid' => 0 );
		foreach ( $all as $m ) {
			$key = 'assigned' === $m->status ? 'offered' : ( in_array( $m->status, array( 'in_progress', 'review' ), true ) ? 'current' : 'done' );
			$groups[ $key ][] = self::engineer_shape( $m );
			if ( isset( $money[ $m->fee_status ] ) ) {
				$money[ $m->fee_status ] += $m->engineer_fee;
			}
		}
		$out['missions'] = $groups;
		$out['counts']   = array_map( 'count', $groups );
		$out['money']    = $money;
		if ( 'billing' === $section ) {
			$out['billing'] = array_values(
				array_map(
					static fn( $m ) => self::engineer_shape( $m ) + array(
						'delivered' => $m->delivered_at,
						'paid_at'   => $m->fee_paid_at,
						'reference' => $m->fee_reference,
						'invoices'  => self::files( $m, 'engineer', 'invoice' ),
					),
					array_filter( $all, static fn( $m ) => 'none' !== $m->fee_status )
				)
			);
		}
		if ( 'files' === $section ) {
			$by = array();
			foreach ( Files::for_engineer( $e->company_id ) as $f ) {
				$mid = (int) $f->mission_id;
				if ( ! isset( $by[ $mid ] ) ) {
					$m          = Missions::get( $mid );
					$by[ $mid ] = array( 'ref' => $m ? $m->ref : '', 'title' => $m ? $m->title : '', 'url' => CoreRoutes::url( 'account', array( 'view' => 'missions', 'id' => $mid ) ), 'files' => array() );
				}
				$by[ $mid ]['files'][] = Files::shape( $f );
			}
			$out['files'] = array_values( $by );
		}
		return $out;
	}
}
