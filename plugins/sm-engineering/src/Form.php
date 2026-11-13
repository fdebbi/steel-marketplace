<?php
/**
 * Front-end form handlers (admin-post.php):
 *
 *   sm_mission_request   a client asks the platform for a study / an inspection
 *   sm_mission_client    the client accepts or refuses the quote, cancels,
 *                        confirms the delivery, adds documents
 *   sm_mission_engineer  the engineer accepts or declines the mission, uploads
 *                        deliverables or the fee invoice, sends the work
 *   sm_mission_message   a message to the platform (client or engineer channel)
 *
 * @package SM\Engineering
 */

namespace SM\Engineering;

use SM\Core\Accounts\Companies;
use SM\Core\Accounts\Wilayas;
use SM\Core\Routing\Routes as CoreRoutes;
use SM\Core\Support\Forms;

defined( 'ABSPATH' ) || exit;

final class Form {

	public static function init() {
		foreach ( array( 'mission_request', 'mission_client', 'mission_engineer', 'mission_message' ) as $f ) {
			add_action( 'admin_post_sm_' . $f, array( __CLASS__, 'handle_' . $f ) );
		}
	}

	/** Why the user cannot ask for a study ('' = can). */
	public static function request_block( $user_id = null ) {
		$user_id = $user_id ?? get_current_user_id();
		if ( ! $user_id ) {
			return __( 'Sign in or create an account to ask for a study.', 'sm-engineering' );
		}
		$co = Companies::for_user( $user_id );
		if ( ! $co ) {
			return __( 'Your account has no company.', 'sm-engineering' );
		}
		if ( 'engineer' === $co->account_class ) {
			return __( 'Partner engineers receive missions from the platform; they cannot request them.', 'sm-engineering' );
		}
		$ok = sm_can_explain( 'mission.request', $co->id );
		return is_wp_error( $ok ) ? $ok->get_error_message() : '';
	}

	/** Validate a mission request. */
	public static function validate_request( array $in ) {
		$errors = array();
		$types  = Settings::types();
		// One or more services (0.3.0); "type" (one service) is still read for old links and tests.
		$codes = array_map( 'sanitize_key', (array) ( $in['services'] ?? array() ) );
		if ( ! $codes && ! empty( $in['type'] ) ) {
			$codes = array( sanitize_key( (string) $in['type'] ) );
		}
		$codes = array_values( array_unique( array_filter( $codes, static fn( $c ) => isset( $types[ $c ] ) ) ) );
		if ( ! $codes ) {
			$errors['services'] = __( 'Choose at least one engineering service.', 'sm-engineering' );
		}
		$ticked   = (array) ( $in['prestations'] ?? array() );
		$services = array();
		$picked   = array();
		foreach ( $codes as $code ) {
			$sv   = Services::get( $code );
			$pick = Services::pick( $code, array_map( 'absint', (array) ( $ticked[ $code ] ?? array() ) ) );
			if ( $sv && $sv['items'] && ! $pick ) {
				/* translators: %s: engineering service. */
				$errors['prestations'] = sprintf( __( 'Tick at least one prestation of the service “%s”.', 'sm-engineering' ), Services::text( $sv ) );
			}
			$services[] = array( 'code' => $code, 'fr' => $sv ? $sv['fr'] : $types[ $code ]['fr'], 'ar' => $sv ? $sv['ar'] : $types[ $code ]['ar'], 'items' => $pick, 'amount' => 0 );
			$picked     = array_merge( $picked, $pick );
		}
		$ptypes  = ProjectTypes::all();
		$project = sanitize_key( (string) ( $in['project_type'] ?? '' ) );
		if ( ! isset( $ptypes[ $project ] ) ) {
			$errors['project_type'] = __( 'Choose the type of project.', 'sm-engineering' );
		}
		$title = mb_substr( sanitize_text_field( (string) ( $in['title'] ?? '' ) ), 0, 200 );
		if ( mb_strlen( $title ) < 5 ) {
			$errors['title'] = __( 'Give a short title, for example “Hangar 20 × 40 m in Sétif”.', 'sm-engineering' );
		}
		$desc = mb_substr( sanitize_textarea_field( (string) ( $in['description'] ?? '' ) ), 0, 5000 );
		if ( mb_strlen( $desc ) < 30 ) {
			$errors['description'] = __( 'Describe the project: dimensions, use, loads, what you expect (30 characters at least).', 'sm-engineering' );
		}
		$wilaya = preg_replace( '/\D/', '', (string) ( $in['wilaya'] ?? '' ) );
		if ( ! Wilayas::exists( $wilaya ) ) {
			$errors['wilaya'] = __( 'Choose the wilaya of the project.', 'sm-engineering' );
		}
		$needed = sanitize_text_field( (string) ( $in['needed_by'] ?? '' ) );
		if ( '' !== $needed && ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $needed ) || $needed < gmdate( 'Y-m-d' ) ) ) {
			$errors['needed_by'] = __( 'Choose a date in the future, or leave it empty.', 'sm-engineering' );
		}
		$pref   = absint( $in['engineer'] ?? 0 );
		if ( $pref && ! Engineers::is_approved( $pref ) ) {
			$pref = 0;
		}
		return array(
			'errors' => $errors,
			'data'   => array(
				'type'         => $codes[0] ?? '',
				'services'     => $services,
				'project_type' => $project,
				'title'        => $title,
				'description'  => $desc,
				'prestations'  => $picked,
				'wilaya'       => $wilaya,
				'needed_by'    => '' !== $needed ? $needed : null,
				'preferred_id' => $pref,
			),
		);
	}

	public static function handle_mission_request() {
		Forms::begin( 'mission_request' );
		$user_id = get_current_user_id();
		$block   = self::request_block( $user_id );
		if ( $block ) {
			Forms::back( array( $block ) );
		}
		$company = Companies::for_user( $user_id );
		if ( Missions::sent_today( $company->id ) >= (int) Settings::get( 'requests_per_day' ) ) {
			Forms::back( array( __( 'You sent many study requests today. Try again tomorrow or write to the platform.', 'sm-engineering' ) ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in Forms::begin().
		$v = self::validate_request( (array) wp_unslash( $_POST ) );
		if ( $v['errors'] ) {
			Forms::back( $v['errors'] );
		}
		$id    = Missions::create( $company->id, $user_id, $v['data'] );
		$files = Files::upload( $id, 'files', 'brief', true, true );
		\SM\Core\Notifications\Notifications::notify_moderators( 'mission.submitted', array( 'mission_id' => $id ) );
		Forms::back( $files['errors'], __( 'Your request is sent to the platform. You receive a quote, usually within 2 working days.', 'sm-engineering' ), Notifications::link( 'client', $id ) );
	}

	/** The mission when the user is a member of the client company, or null. */
	private static function client_mission( $id, $user_id ) {
		$m = Missions::get( $id );
		return ( $m && Companies::member_role( $m->client_id, $user_id ) ) ? $m : null;
	}

	/** The mission when the user belongs to the engineer it is assigned to, or null. */
	private static function engineer_mission( $id, $user_id ) {
		$m = Missions::get( $id );
		return ( $m && $m->engineer_id && Companies::member_role( $m->engineer_id, $user_id ) ) ? $m : null;
	}

	public static function handle_mission_client() {
		Forms::begin( 'mission_client' );
		$user_id = get_current_user_id();
		$m       = self::client_mission( absint( Forms::post( 'mission_id' ) ), $user_id );
		if ( ! $m ) {
			Forms::back( array( __( 'You cannot change this request.', 'sm-engineering' ) ) );
		}
		$do = sanitize_key( Forms::post( 'do' ) );
		if ( 'files' === $do ) {
			if ( ! in_array( $m->status, Missions::OPEN, true ) ) {
				Forms::back( array( __( 'This request is closed.', 'sm-engineering' ) ) );
			}
			$r = Files::upload( $m->id, 'files', 'brief', true, true );
			if ( $r['files'] ) {
				Messages::add( $m->id, 'client', sprintf( /* translators: %d: number of files. */ _n( '%d document added.', '%d documents added.', count( $r['files'] ), 'sm-engineering' ), count( $r['files'] ) ) );
				Notifications::message( $m, 'client', false );
			}
			Forms::back( $r['errors'], $r['files'] ? __( 'Documents added. The platform is told.', 'sm-engineering' ) : '' );
		}
		if ( 'pay' === $do ) {
			$t    = Missions::totals( $m );
			$item = $t['advance_due'] ? 'advance' : ( $t['balance_due'] ? 'balance' : '' );
			if ( '' === $item ) {
				Forms::back( array( __( 'Nothing is to pay for this study now.', 'sm-engineering' ) ) );
			}
			$order = Payments::order_for( $m, $item, $user_id );
			if ( is_wp_error( $order ) ) {
				Forms::back( array( $order->get_error_message() ) );
			}
			Forms::back( array(), __( 'Pay with one of the methods below, then send the proof of payment.', 'sm-engineering' ), Payments::order_url( $order ) );
		}
		$map = array(
			'accept'  => array( 'accept', __( 'Quote accepted. The platform appoints a partner engineer and you are told when the study starts.', 'sm-engineering' ) ),
			'refuse'  => array( 'refuse', __( 'You declined the quote. The platform is told.', 'sm-engineering' ) ),
			'cancel'  => array( 'cancel', __( 'Your request is cancelled.', 'sm-engineering' ) ),
			'confirm' => array( 'confirm', __( 'Thank you. The mission is closed.', 'sm-engineering' ) ),
		);
		if ( ! isset( $map[ $do ] ) ) {
			Forms::back( array( __( 'Unknown action.', 'sm-engineering' ) ) );
		}
		$r = Missions::transition( $m, $map[ $do ][0], array( 'reason' => Forms::post_textarea( 'reason' ) ) );
		if ( is_wp_error( $r ) ) {
			Forms::back( array( $r->get_error_message() ) );
		}
		// Quote accepted: the client goes to the payment of the advance.
		if ( 'accept' === $do && Missions::totals( $r )['advance_due'] ) {
			$order = Payments::order_for( $r, 'advance', $user_id );
			if ( ! is_wp_error( $order ) ) {
				Forms::back( array(), __( 'Quote accepted. Pay the advance to start the requested services: pay with one of the methods below, then send the proof of payment.', 'sm-engineering' ), Payments::order_url( $order ) );
			}
			Forms::back( array(), __( 'Quote accepted. The platform sends you how to pay the advance; the services start once it is received.', 'sm-engineering' ) );
		}
		Forms::back( array(), $map[ $do ][1] );
	}

	public static function handle_mission_engineer() {
		Forms::begin( 'mission_engineer' );
		$user_id = get_current_user_id();
		$m       = self::engineer_mission( absint( Forms::post( 'mission_id' ) ), $user_id );
		if ( ! $m ) {
			Forms::back( array( __( 'This mission is not assigned to you.', 'sm-engineering' ) ) );
		}
		if ( ! Engineers::is_approved( $m->engineer_id ) ) {
			Forms::back( array( __( 'Your partner account is not active.', 'sm-engineering' ) ) );
		}
		$do = sanitize_key( Forms::post( 'do' ) );
		switch ( $do ) {
			case 'take':
				$r = Missions::transition( $m, 'take' );
				$msg = __( 'Mission accepted. You can start; send the deliverables from this page.', 'sm-engineering' );
				break;
			case 'giveup':
				$r = Missions::transition( $m, 'giveup', array( 'reason' => Forms::post_textarea( 'reason' ) ) );
				$msg = __( 'You declined the mission. The platform is told.', 'sm-engineering' );
				Forms::back( is_wp_error( $r ) ? array( $r->get_error_message() ) : array(), is_wp_error( $r ) ? '' : $msg, CoreRoutes::url( 'account', array( 'view' => 'missions' ) ) );
				break;
			case 'upload':
				if ( 'in_progress' !== $m->status ) {
					Forms::back( array( __( 'Deliverables can be added while the mission is in progress.', 'sm-engineering' ) ) );
				}
				$f = Files::upload( $m->id, 'files', 'deliverable', false, true );
				Forms::back( $f['errors'], $f['files'] ? __( 'Files added. Send the work to the platform when everything is there.', 'sm-engineering' ) : '' );
				break;
			case 'remove':
				$file = Files::get( absint( Forms::post( 'file_id' ) ) );
				if ( ! $file || (int) $file->mission_id !== $m->id || 'deliverable' !== $file->kind || 'in_progress' !== $m->status ) {
					Forms::back( array( __( 'This file cannot be removed.', 'sm-engineering' ) ) );
				}
				Files::delete( $file->id );
				Forms::back( array(), __( 'File removed.', 'sm-engineering' ) );
				break;
			case 'invoice':
				if ( ! in_array( $m->fee_status, array( 'due', 'planned' ), true ) ) {
					Forms::back( array( __( 'An invoice can be sent once the mission is accepted.', 'sm-engineering' ) ) );
				}
				$f = Files::store( 'invoice', 'missions/' . $m->id, 1, array( 'pdf', 'jpg', 'jpeg', 'png' ) );
				foreach ( $f['files'] as $one ) {
					Files::add( $m->id, 'invoice', $one, false, true );
				}
				if ( $f['files'] ) {
					Notifications::message( $m, 'engineer', false );
				}
				Forms::back( $f['errors'], $f['files'] ? __( 'Invoice sent to the platform.', 'sm-engineering' ) : '' );
				break;
			case 'submit':
				$r   = Missions::transition( $m, 'submit' );
				$msg = __( 'Work sent. The platform checks it and delivers it to the client.', 'sm-engineering' );
				break;
			default:
				Forms::back( array( __( 'Unknown action.', 'sm-engineering' ) ) );
				return;
		}
		if ( is_wp_error( $r ) ) {
			Forms::back( array( $r->get_error_message() ) );
		}
		Forms::back( array(), $msg );
	}

	public static function handle_mission_message() {
		Forms::begin( 'mission_message' );
		$user_id = get_current_user_id();
		$id      = absint( Forms::post( 'mission_id' ) );
		$m       = self::client_mission( $id, $user_id );
		$channel = 'client';
		if ( ! $m ) {
			$m       = self::engineer_mission( $id, $user_id );
			$channel = 'engineer';
		}
		if ( ! $m ) {
			Forms::back( array( __( 'You cannot write on this mission.', 'sm-engineering' ) ) );
		}
		$body = Forms::post_textarea( 'body' );
		if ( mb_strlen( trim( $body ) ) < 2 ) {
			Forms::back( array( 'body' => __( 'Write your message.', 'sm-engineering' ) ) );
		}
		if ( ! Forms::rate_limit( 'mission_message|' . $user_id, 60, HOUR_IN_SECONDS ) ) {
			Forms::back( array( __( 'Too many messages. Try again later.', 'sm-engineering' ) ) );
		}
		Messages::add( $m->id, $channel, $body );
		Notifications::message( $m, $channel, false );
		Forms::back( array(), __( 'Message sent to the platform.', 'sm-engineering' ) );
	}
}
