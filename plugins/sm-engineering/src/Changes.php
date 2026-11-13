<?php
/**
 * Changes of a partner engineer profile (0.5.0).
 *
 *   - while the application is being checked, the profile cannot be changed;
 *   - once approved, a change is a request: the approved profile stays in use
 *     until the platform approves it (or refuses it with a reason);
 *   - free, at once: availability (button at the top of the menu), mobile
 *     phone, password and language (account settings).
 *
 * A request is kept with the engineer (column pending_change, JSON):
 * { fields: { key => new value }, photo_id, at }. One request at a time; the
 * engineer may cancel it.
 *
 *   sm_engineer_available  availability on / off
 *   sm_engineer_phone      mobile phone
 *   (sm_engineer_profile, in Apply, stores the request)
 *
 * @package SM\Engineering
 */

namespace SM\Engineering;

use SM\Core\Accounts\Auth;
use SM\Core\Accounts\Companies;
use SM\Core\Accounts\Wilayas;
use SM\Core\Audit\AuditLog;
use SM\Core\Notifications\Notifications as Notify;
use SM\Core\Support\Forms;

defined( 'ABSPATH' ) || exit;

final class Changes {

	/** Fields of a request, in the order shown. */
	const FIELDS = array( 'last_name', 'first_name', 'wilaya', 'partner_type', 'bet_name', 'degree', 'speciality', 'experience', 'specialties', 'bio' );

	public static function init() {
		add_action( 'admin_post_sm_engineer_available', array( __CLASS__, 'handle_available' ) );
		add_action( 'admin_post_sm_engineer_phone', array( __CLASS__, 'handle_phone' ) );
		// The name of a partner engineer changes through a request, never in the account settings.
		add_action( 'admin_post_sm_settings_save', array( __CLASS__, 'keep_name' ), 1 );
	}

	public static function keep_name() {
		$user = wp_get_current_user();
		if ( $user && $user->ID && Engineers::for_user( $user->ID ) ) {
			$_POST['display_name'] = wp_slash( $user->display_name ); // phpcs:ignore WordPress.Security.NonceVerification -- checked by the handler that follows.
		}
	}

	/** Name of a field for the engineer and the platform team. */
	public static function field_label( $key ) {
		$labels = array(
			'last_name'    => __( 'Last name', 'sm-engineering' ),
			'first_name'   => __( 'First name', 'sm-engineering' ),
			'wilaya'       => __( 'Wilaya', 'sm-engineering' ),
			'partner_type' => __( 'Partner', 'sm-engineering' ),
			'bet_name'     => __( 'Name of the engineering office', 'sm-engineering' ),
			'degree'       => __( 'Professional title', 'sm-engineering' ),
			'speciality'   => __( 'Speciality', 'sm-engineering' ),
			'experience'   => __( 'Years of experience', 'sm-engineering' ),
			'specialties'  => __( 'Services offered', 'sm-engineering' ),
			'bio'          => __( 'Presentation', 'sm-engineering' ),
			'photo'        => __( 'Photo or logo', 'sm-engineering' ),
		);
		return $labels[ $key ] ?? $key;
	}

	/** Values in use today, in the shape of a request. */
	public static function current( $e ) {
		$u = get_userdata( $e->user_id );
		$c = Companies::get( $e->company_id );
		return array(
			'last_name'        => $u ? (string) $u->last_name : '',
			'first_name'       => $u ? (string) $u->first_name : '',
			'wilaya'           => $c ? (string) $c->wilaya : '',
			'partner_type'     => (string) $e->partner_type,
			'bet_name'         => (string) $e->bet_name,
			'degree'           => (string) $e->degree,
			'degree_other'     => (string) $e->degree_other,
			'speciality'       => (string) $e->speciality,
			'speciality_other' => (string) $e->speciality_other,
			'experience'       => (int) $e->experience,
			'specialties'      => array_values( (array) $e->specialties ),
			'bio'              => (string) $e->bio,
		);
	}

	/** Value of a field as text, for the comparison before / after. */
	public static function display( $key, array $v ) {
		switch ( $key ) {
			case 'wilaya':
				return '' !== (string) $v['wilaya'] ? $v['wilaya'] . ' ' . Wilayas::name( $v['wilaya'] ) : '';
			case 'partner_type':
				return 'bet' === $v['partner_type'] ? __( 'Engineering office (BET)', 'sm-engineering' ) : __( 'Independent engineer', 'sm-engineering' );
			case 'degree':
				return Lists::label( 'titles', $v['degree'], $v['degree_other'] ?? '' );
			case 'speciality':
				return Lists::label( 'specialities', $v['speciality'], $v['speciality_other'] ?? '' );
			case 'specialties':
				$types = Settings::types( true );
				$ar    = 'ar' === sm_current_language();
				return implode( ', ', array_map( static fn( $s ) => isset( $types[ $s ] ) ? ( $ar ? $types[ $s ]['ar'] : $types[ $s ]['fr'] ) : $s, (array) $v['specialties'] ) );
			default:
				return (string) ( $v[ $key ] ?? '' );
		}
	}

	/** The request waiting for the platform, or null. */
	public static function pending( $e ) {
		$p = json_decode( (string) ( $e->pending_change ?? '' ), true );
		return is_array( $p ) && ( ! empty( $p['fields'] ) || ! empty( $p['photo_id'] ) ) ? $p : null;
	}

	/**
	 * Lines of the comparison: list of { key, label, before, after }.
	 */
	public static function diff( $e ) {
		$p = self::pending( $e );
		if ( ! $p ) {
			return array();
		}
		$now  = self::current( $e );
		$new  = array_merge( $now, (array) $p['fields'] );
		$rows = array();
		foreach ( self::FIELDS as $k ) {
			$b = self::display( $k, $now );
			$a = self::display( $k, $new );
			if ( $a !== $b ) {
				$rows[] = array( 'key' => $k, 'label' => self::field_label( $k ), 'before' => $b, 'after' => $a );
			}
		}
		if ( ! empty( $p['photo_id'] ) ) {
			$rows[] = array( 'key' => 'photo', 'label' => self::field_label( 'photo' ), 'before' => '', 'after' => __( 'New picture', 'sm-engineering' ), 'photo' => (string) wp_get_attachment_image_url( (int) $p['photo_id'], 'thumbnail' ) );
		}
		return $rows;
	}

	/**
	 * Store a request.
	 *
	 * @param array $fields   Validated values (same keys as current()).
	 * @param int   $photo_id New picture (attachment), 0 for none.
	 * @return true|\WP_Error
	 */
	public static function request( $e, array $fields, $photo_id = 0 ) {
		if ( 'pending' === $e->status ) {
			return new \WP_Error( 'locked', __( 'Your application is being checked by the platform: your profile cannot be changed for the moment.', 'sm-engineering' ) );
		}
		$now     = self::current( $e );
		$changed = array();
		foreach ( $now as $k => $v ) {
			if ( array_key_exists( $k, $fields ) && $fields[ $k ] != $v ) { // phpcs:ignore Universal.Operators.StrictComparisons -- "16" and 16 are the same years.
				$changed[ $k ] = $fields[ $k ];
			}
		}
		if ( ! $changed && ! $photo_id ) {
			return new \WP_Error( 'same', __( 'Nothing changed: your profile is the same.', 'sm-engineering' ) );
		}
		$old = self::pending( $e );
		if ( $old && ! empty( $old['photo_id'] ) && (int) $old['photo_id'] !== (int) $photo_id ) {
			if ( $photo_id ) {
				wp_delete_attachment( (int) $old['photo_id'], true );
			} else {
				$photo_id = (int) $old['photo_id']; // The picture of the request being replaced is kept.
			}
		}
		Engineers::set(
			$e->company_id,
			array(
				'pending_change' => wp_json_encode( array( 'fields' => $changed, 'photo_id' => (int) $photo_id, 'at' => time() ), JSON_UNESCAPED_UNICODE ),
				'change_reason'  => '',
			)
		);
		AuditLog::add( 'engineer.change_requested', 'engineer', $e->company_id, array( 'fields' => array_keys( $changed ), 'photo' => (bool) $photo_id ), $e->user_id, $e->company_id );
		Notify::notify_moderators( 'engineer.change_requested', array( 'company_id' => $e->company_id ) );
		return true;
	}

	/** The engineer cancels his request. */
	public static function cancel( $e ) {
		$p = self::pending( $e );
		if ( ! $p ) {
			return;
		}
		if ( ! empty( $p['photo_id'] ) ) {
			wp_delete_attachment( (int) $p['photo_id'], true );
		}
		Engineers::set( $e->company_id, array( 'pending_change' => '' ) );
		AuditLog::add( 'engineer.change_cancelled', 'engineer', $e->company_id, array(), get_current_user_id(), $e->company_id );
	}

	/**
	 * The platform approves (the request becomes the profile) or refuses it.
	 *
	 * @return true|\WP_Error
	 */
	public static function decide( $company_id, $approve, $reason = '' ) {
		$e = Engineers::get( $company_id );
		$p = $e ? self::pending( $e ) : null;
		if ( ! $p ) {
			return new \WP_Error( 'none', __( 'No change is waiting for this engineer.', 'sm-engineering' ) );
		}
		if ( ! $approve ) {
			if ( '' === trim( (string) $reason ) ) {
				return new \WP_Error( 'reason', __( 'Give the reason of the refusal: the engineer reads it.', 'sm-engineering' ) );
			}
			if ( ! empty( $p['photo_id'] ) ) {
				wp_delete_attachment( (int) $p['photo_id'], true );
			}
			Engineers::set( $company_id, array( 'pending_change' => '', 'change_reason' => sanitize_textarea_field( $reason ) ) );
			AuditLog::add( 'engineer.change_refused', 'engineer', $company_id, array( 'reason' => $reason ), get_current_user_id(), $company_id );
			Notify::notify_company( $company_id, 'engineer.change_refused', array( 'reason' => $reason ) );
			return true;
		}
		$v = array_merge( self::current( $e ), (array) $p['fields'] );
		if ( 'bet' !== $v['partner_type'] ) {
			$v['bet_name'] = '';
		}
		$degree = Lists::label( 'titles', $v['degree'], $v['degree_other'], 'fr' );
		Engineers::save(
			$company_id,
			$e->user_id,
			array(
				'title'            => mb_substr( trim( ( '' !== $degree ? $degree : $e->diploma ) . ' — ' . Lists::label( 'specialities', $v['speciality'], $v['speciality_other'], 'fr' ), ' —' ), 0, 160 ),
				'partner_type'     => $v['partner_type'],
				'bet_name'         => $v['bet_name'],
				'degree'           => $v['degree'],
				'degree_other'     => $v['degree_other'],
				'speciality'       => $v['speciality'],
				'speciality_other' => $v['speciality_other'],
				'diploma'          => '' !== $degree ? $degree : $e->diploma,
				'school'           => $e->school,
				'grad_year'        => $e->grad_year,
				'experience'       => (int) $v['experience'],
				'specialties'      => (array) $v['specialties'],
				'bio'              => $v['bio'],
			)
		);
		$full = trim( $v['first_name'] . ' ' . $v['last_name'] );
		if ( '' !== $full ) {
			wp_update_user( array( 'ID' => $e->user_id, 'first_name' => $v['first_name'], 'last_name' => $v['last_name'], 'display_name' => $full ) );
		}
		$profile = array( 'name' => 'bet' === $v['partner_type'] && '' !== $v['bet_name'] ? $v['bet_name'] : ( '' !== $full ? $full : Companies::get( $company_id )->name ), 'wilaya' => $v['wilaya'] );
		if ( ! empty( $p['photo_id'] ) ) {
			$profile['logo_id'] = (int) $p['photo_id'];
		}
		Companies::update_profile( $company_id, $profile );
		Engineers::set( $company_id, array( 'pending_change' => '', 'change_reason' => '' ) );
		AuditLog::add( 'engineer.change_approved', 'engineer', $company_id, array( 'fields' => array_keys( (array) $p['fields'] ), 'photo' => ! empty( $p['photo_id'] ) ), get_current_user_id(), $company_id );
		Notify::notify_company( $company_id, 'engineer.change_approved', array() );
		return true;
	}

	/** Availability for new missions, at once (approved engineers). */
	public static function handle_available() {
		Forms::begin( 'engineer_available' );
		$e = Engineers::for_user( get_current_user_id() );
		if ( ! $e || 'approved' !== $e->status ) {
			Forms::back( array( __( 'Availability is for approved partner engineers.', 'sm-engineering' ) ) );
		}
		$on = Forms::post( 'available' ) ? 1 : 0;
		Engineers::set( $e->company_id, array( 'available' => $on ) );
		AuditLog::add( 'engineer.availability', 'engineer', $e->company_id, array( 'available' => $on ), get_current_user_id(), $e->company_id );
		Forms::back( array(), $on ? __( 'You are available: the platform can offer you new missions.', 'sm-engineering' ) : __( 'You are unavailable: the platform offers you no new mission. Your current missions go on.', 'sm-engineering' ) );
	}

	/** Mobile phone, at once. */
	public static function handle_phone() {
		Forms::begin( 'engineer_phone' );
		$user_id = get_current_user_id();
		$e       = Engineers::for_user( $user_id );
		if ( ! $e ) {
			Forms::back( array( __( 'This page is for partner engineers.', 'sm-engineering' ) ) );
		}
		$phone = Forms::normalize_mobile( Forms::post( 'phone' ) );
		$owner = $phone ? Auth::user_by_phone( $phone ) : null;
		if ( ! $phone ) {
			Forms::back( array( 'phone' => __( 'Enter a valid Algerian mobile number (05, 06 or 07 followed by 8 digits).', 'sm-engineering' ) ) );
		}
		if ( $owner && (int) ( is_object( $owner ) ? $owner->ID : $owner ) !== $user_id ) {
			Forms::back( array( 'phone' => __( 'This phone number is already used by another account.', 'sm-engineering' ) ) );
		}
		update_user_meta( $user_id, 'sm_phone', $phone );
		Companies::update_profile( $e->company_id, array( 'phone' => $phone ) );
		AuditLog::add( 'engineer.phone', 'engineer', $e->company_id, array(), $user_id, $e->company_id );
		Forms::back( array(), __( 'Your phone number is saved.', 'sm-engineering' ) );
	}
}
