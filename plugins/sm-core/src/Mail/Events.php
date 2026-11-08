<?php
/**
 * Catalogue of platform events that can send e-mails.
 *
 * Every event is tied to an action of the audit log: when the action is
 * logged (and its "when" test passes), the administrator is alerted
 * (immediately or in the daily summary, as chosen in the settings) and the
 * user receives a confirmation if that e-mail is on. Other sm-* plugins add
 * their events with the sm_mail_events filter.
 *
 * Definition keys:
 *  - group, label    : shown in the admin (label is translated text).
 *  - audit           : audit action that triggers the event.
 *  - when            : optional callable( object $entry ) : bool.
 *  - vars            : callable( object $entry ) : array of template variables.
 *  - admin           : default admin mode (now, digest, off); null = never e-mailed to the admin.
 *  - user            : default of the user e-mail (true/false); null = no user e-mail for this event.
 *  - to              : callable( object $entry, array $vars ) : recipients [ [email, name, lang] ].
 *  - reply_to        : optional variable whose value is used as Reply-To of the admin alert.
 *  - describe        : callable( array $vars ) : one sentence for the activity journal.
 *  - templates       : [ admin => [subject, body], user => [subject, body] ] of callables returning
 *                      translated text (evaluated in the recipient's language).
 *  - variables       : names of the variables offered in the editor.
 *
 * @package SM\Core
 */

namespace SM\Core\Mail;

use SM\Core\Accounts\Companies;
use SM\Core\Accounts\Wilayas;
use SM\Core\I18n\Language;
use SM\Core\Routing\Routes;

defined( 'ABSPATH' ) || exit;

final class Events {

	private static $cache = null;

	public static function groups() {
		return apply_filters(
			'sm_mail_event_groups',
			array(
				'accounts'     => __( 'Accounts', 'sm-core' ),
				'verification' => __( 'Verification', 'sm-core' ),
				'listings'     => __( 'Listings', 'sm-core' ),
				'rfq'          => __( 'Requests for quotation', 'sm-core' ),
				'services'     => __( 'Services', 'sm-core' ),
				'messages'     => __( 'Messages', 'sm-core' ),
				'security'     => __( 'Security', 'sm-core' ),
			)
		);
	}

	/** All events, keyed by event key. */
	public static function all() {
		if ( null === self::$cache ) {
			$events = apply_filters( 'sm_mail_events', self::core() );
			foreach ( $events as $key => $e ) {
				$events[ $key ] = wp_parse_args(
					$e,
					array(
						'group'     => 'accounts',
						'label'     => $key,
						'audit'     => $key,
						'when'      => null,
						'vars'      => static fn( $entry ) => array(),
						'admin'     => 'off',
						'user'      => null,
						'to'        => static fn( $entry, $vars ) => array(),
						'reply_to'  => '',
						'describe'  => null,
						'templates' => array(),
						'variables' => array(),
					)
				);
			}
			self::$cache = $events;
		}
		return self::$cache;
	}

	public static function get( $key ) {
		return self::all()[ $key ] ?? null;
	}

	public static function reset() {
		self::$cache = null;
	}

	/** Events triggered by an audit entry. */
	public static function for_entry( $entry ) {
		$out = array();
		foreach ( self::all() as $key => $e ) {
			if ( $e['audit'] !== $entry->action ) {
				continue;
			}
			if ( $e['when'] && ! call_user_func( $e['when'], $entry ) ) {
				continue;
			}
			$out[ $key ] = $e;
		}
		return $out;
	}

	/* ---- Helpers for event definitions (also used by other plugins) ----- */

	/** Variables describing a company: entreprise, telephone, email_entreprise, wilaya, type, lien_entreprise, lien_admin_entreprise. */
	public static function company_vars( $company_id ) {
		$c = $company_id ? Companies::get( (int) $company_id ) : null;
		if ( ! $c ) {
			return array( 'entreprise' => '', 'telephone' => '', 'wilaya' => '', 'type' => '', 'lien_entreprise' => '', 'lien_admin_entreprise' => '' );
		}
		$ar = 'ar' === Language::current();
		return array(
			'entreprise'            => $ar && $c->name_ar ? $c->name_ar : $c->name,
			'telephone'             => (string) $c->phone,
			'email_entreprise'      => (string) $c->email,
			'wilaya'                => $c->wilaya ? $c->wilaya . ' ' . Wilayas::name( $c->wilaya ) : '',
			'type'                  => 'engineering' === $c->account_class ? __( 'Engineering account', 'sm-core' ) : __( 'Business account', 'sm-core' ),
			'activites'             => implode( ', ', array_intersect_key( Companies::activity_labels(), array_flip( Companies::get_activities( $c->id ) ) ) ),
			'lien_entreprise'       => Routes::url( 'company', array( 'c' => $c->slug ) ),
			'lien_admin_entreprise' => admin_url( 'admin.php?page=sm-companies&id=' . (int) $c->id ),
		);
	}

	/** Variables describing a user: nom, email. */
	public static function user_vars( $user_id ) {
		$u = $user_id ? get_userdata( (int) $user_id ) : null;
		return array(
			'nom'   => $u ? $u->display_name : '',
			'email' => $u ? $u->user_email : '',
		);
	}

	/** Recipient entry for a user (in the user's language). */
	public static function user_recipient( $user_id ) {
		$u = $user_id ? get_userdata( (int) $user_id ) : null;
		return $u && is_email( $u->user_email ) ? array( array( 'email' => $u->user_email, 'name' => $u->display_name, 'lang' => Language::user_language( $u->ID ) ) ) : array();
	}

	/** Recipient from the entry's author (user_id of the audit entry). */
	public static function author( $entry, $vars ) {
		return self::user_recipient( $entry->user_id );
	}

	/** True when an audit entry of a save says "new". */
	public static function is_new( $entry ) {
		return ! empty( $entry->data['new'] );
	}

	/* ---- Core events -------------------------------------------------- */

	private static function core() {
		return array(
			'account.registered'     => array(
				'group'     => 'accounts',
				'label'     => __( 'New registration', 'sm-core' ),
				'audit'     => 'account.registered',
				'admin'     => 'now',
				'user'      => true,
				'vars'      => static fn( $e ) => Companies::get( $e->company_id ) ? self::user_vars( $e->user_id ) + self::company_vars( $e->company_id ) + array( 'lien' => Routes::url( 'account' ) ) : array(),
				'to'        => array( __CLASS__, 'author' ),
				'reply_to'  => 'email',
				'variables' => array( 'nom', 'email', 'telephone', 'entreprise', 'wilaya', 'type', 'activites', 'lien', 'lien_admin_entreprise' ),
				'describe'  => static fn( $v ) => sprintf( /* translators: 1: person, 2: company, 3: wilaya. */ __( '%1$s registered (%2$s, %3$s)', 'sm-core' ), $v['nom'], $v['entreprise'], $v['wilaya'] ),
				'templates' => array(
					'admin' => array(
						static fn() => __( 'New registration: {entreprise}', 'sm-core' ),
						static fn() => __( "A new account was created on {site}.\n\nName: {nom}\nE-mail: {email}\nPhone: {telephone}\nCompany: {entreprise}\nWilaya: {wilaya}\nAccount: {type}\nActivities: {activites}\n\nOpen the company in the administration:\n{lien_admin_entreprise}", 'sm-core' ),
					),
					'user'  => array(
						static fn() => __( 'Welcome to {site}', 'sm-core' ),
						static fn() => __( "Thank you for registering on {site}, the steel marketplace of Algeria.\n\nYour account for {entreprise} is ready. Complete your company page and send your documents for verification: verified companies get more enquiries.\n\nYour dashboard:\n{lien}\n\nWe are at your disposal for any question.\nThe {site} team", 'sm-core' ),
					),
				),
			),
			'verification.submitted' => array(
				'group'     => 'verification',
				'label'     => __( 'Documents sent for verification', 'sm-core' ),
				'audit'     => 'verification.submitted',
				'admin'     => 'now',
				'user'      => true,
				'vars'      => static fn( $e ) => Companies::get( $e->company_id ) ? self::user_vars( $e->user_id ) + self::company_vars( $e->company_id ) + array( 'lien' => Routes::url( 'account', array( 'view' => 'verification' ) ), 'lien_admin' => admin_url( 'admin.php?page=sm-verifications' ) ) : array(),
				'to'        => array( __CLASS__, 'author' ),
				'variables' => array( 'nom', 'email', 'telephone', 'entreprise', 'wilaya', 'lien', 'lien_admin' ),
				'describe'  => static fn( $v ) => sprintf( /* translators: %s: company. */ __( '%s sent documents for verification', 'sm-core' ), $v['entreprise'] ),
				'templates' => array(
					'admin' => array(
						static fn() => __( 'Verification to review: {entreprise}', 'sm-core' ),
						static fn() => __( "{entreprise} ({wilaya}) sent its documents for verification.\n\nSent by: {nom} — {email} — {telephone}\n\nReview the documents:\n{lien_admin}", 'sm-core' ),
					),
					'user'  => array(
						static fn() => __( 'We received your documents', 'sm-core' ),
						static fn() => __( "We received the documents of {entreprise}. The platform team checks them and you will be told of the result.\n\nFollow your request:\n{lien}\n\nThe {site} team", 'sm-core' ),
					),
				),
			),
			'verification.contact'   => array(
				'group'     => 'verification',
				'label'     => __( 'Phone or e-mail confirmed', 'sm-core' ),
				'audit'     => 'verification.contact',
				'admin'     => 'digest',
				'user'      => null,
				'vars'      => static fn( $e ) => Companies::get( $e->company_id ) ? self::user_vars( $e->user_id ) + self::company_vars( $e->company_id ) : array(),
				'variables' => array( 'nom', 'email', 'telephone', 'entreprise', 'wilaya', 'lien_admin_entreprise' ),
				'describe'  => static fn( $v ) => sprintf( /* translators: %s: company. */ __( '%s confirmed its phone or e-mail', 'sm-core' ), $v['entreprise'] ),
				'templates' => array(
					'admin' => array(
						static fn() => __( 'Contact confirmed: {entreprise}', 'sm-core' ),
						static fn() => __( "{entreprise} ({wilaya}) confirmed its phone or e-mail.\n\n{nom} — {email} — {telephone}\n\n{lien_admin_entreprise}", 'sm-core' ),
					),
				),
			),
			'enquiry.sent'           => array(
				'group'     => 'messages',
				'label'     => __( 'Enquiry sent to a company', 'sm-core' ),
				'audit'     => 'enquiry.sent',
				'admin'     => 'digest',
				'user'      => false,
				'vars'      => array( __CLASS__, 'enquiry_vars' ),
				'to'        => static fn( $e, $v ) => is_email( $v['email'] ) ? array( array( 'email' => $v['email'], 'name' => $v['nom'], 'lang' => $e->user_id ? Language::user_language( $e->user_id ) : Language::current() ) ) : array(),
				'reply_to'  => 'email',
				'variables' => array( 'nom', 'email', 'telephone', 'destinataire', 'sujet', 'message' ),
				'describe'  => static fn( $v ) => sprintf( /* translators: 1: sender, 2: company. */ __( '%1$s sent an enquiry to %2$s', 'sm-core' ), $v['nom'], $v['destinataire'] ),
				'templates' => array(
					'admin' => array(
						static fn() => __( 'Enquiry to {destinataire}', 'sm-core' ),
						static fn() => __( "{nom} ({email}, {telephone}) sent an enquiry to {destinataire}.\n\nSubject: {sujet}\n\n{message}", 'sm-core' ),
					),
					'user'  => array(
						static fn() => __( 'Your enquiry was sent to {destinataire}', 'sm-core' ),
						static fn() => __( "Your enquiry \"{sujet}\" was sent to {destinataire}. The company answers you directly by phone or e-mail.\n\nThe {site} team", 'sm-core' ),
					),
				),
			),
			'contact.sent'           => array(
				'group'     => 'messages',
				'label'     => __( 'Contact form message', 'sm-core' ),
				'audit'     => 'contact.sent',
				'admin'     => 'now',
				'user'      => true,
				'vars'      => static fn( $e ) => array(
					'nom'       => (string) ( $e->data['name'] ?? '' ),
					'email'     => (string) ( $e->data['email'] ?? '' ),
					'telephone' => (string) ( $e->data['phone'] ?? '' ),
					'sujet'     => (string) ( $e->data['subject'] ?? '' ),
					'message'   => (string) ( $e->data['message'] ?? '' ),
				),
				'to'        => static fn( $e, $v ) => is_email( $v['email'] ) ? array( array( 'email' => $v['email'], 'name' => $v['nom'], 'lang' => (string) ( $e->data['lang'] ?? 'fr' ) ) ) : array(),
				'reply_to'  => 'email',
				'variables' => array( 'nom', 'email', 'telephone', 'sujet', 'message' ),
				'describe'  => static fn( $v ) => sprintf( /* translators: 1: person, 2: subject. */ __( '%1$s wrote through the contact form: %2$s', 'sm-core' ), $v['nom'], $v['sujet'] ),
				'templates' => array(
					'admin' => array(
						static fn() => __( 'Contact: {sujet}', 'sm-core' ),
						static fn() => __( "Message from the contact form of {site}.\n\nName: {nom}\nE-mail: {email}\nPhone: {telephone}\nSubject: {sujet}\n\n{message}\n\nAnswer this e-mail to reply to {nom}.", 'sm-core' ),
					),
					'user'  => array(
						static fn() => __( 'We received your message', 'sm-core' ),
						static fn() => __( "Thank you for your message \"{sujet}\". The {site} team answers you as soon as possible.\n\nThe {site} team", 'sm-core' ),
					),
				),
			),
			'auth.locked'            => array(
				'group'     => 'security',
				'label'     => __( 'Sign-in blocked after failed attempts', 'sm-core' ),
				'audit'     => 'auth.locked',
				'admin'     => 'now',
				'user'      => null,
				'vars'      => static fn( $e ) => array( 'identifiant' => (string) ( $e->data['identifier'] ?? '' ), 'ip' => (string) $e->ip ),
				'variables' => array( 'identifiant', 'ip' ),
				'describe'  => static fn( $v ) => sprintf( /* translators: 1: login, 2: IP. */ __( 'Sign-in blocked for %1$s (IP %2$s)', 'sm-core' ), $v['identifiant'], $v['ip'] ),
				'templates' => array(
					'admin' => array(
						static fn() => __( 'Security: sign-in blocked', 'sm-core' ),
						static fn() => __( "Too many failed sign-ins: the account \"{identifiant}\" is blocked for a while.\n\nIP address: {ip}\n\nNo action is needed if this was the user. Several alerts in a row may mean an attack.", 'sm-core' ),
					),
				),
			),
		);
	}

	public static function enquiry_vars( $e ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . sm_table( 'enquiries' ) . ' WHERE id = %d', $e->object_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( ! $row ) {
			return array();
		}
		$to  = Companies::get( (int) $row->to_company_id );
		return array(
			'nom'          => $row ? $row->name : '',
			'email'        => $row ? $row->email : '',
			'telephone'    => $row ? $row->phone : '',
			'destinataire' => $to ? ( 'ar' === Language::current() && $to->name_ar ? $to->name_ar : $to->name ) : '',
			'sujet'        => $row ? $row->subject : '',
			'message'      => $row ? (string) $row->message : '',
		);
	}
}
