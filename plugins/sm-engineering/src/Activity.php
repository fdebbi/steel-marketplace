<?php
/**
 * E-mail events and journal entries of engineering (SM Core → E-mails and
 * Activity journal). Admin alerts go to the platform address; the applicant
 * and the client receive a confirmation they can be sure was sent.
 *
 * @package SM\Engineering
 */

namespace SM\Engineering;

use SM\Core\Mail\Events;

defined( 'ABSPATH' ) || exit;

final class Activity {

	public static function init() {
		add_filter( 'sm_mail_events', array( __CLASS__, 'events' ) );
		add_filter( 'sm_mail_event_groups', static fn( $g ) => $g + array( 'engineering' => __( 'Engineering', 'sm-engineering' ) ) );
		add_filter(
			'sm_activity_group_prefixes',
			static function ( $g ) {
				$g['engineering'] = array( 'engineer.', 'mission.' );
				return $g;
			}
		);
		add_filter( 'sm_activity_labels', array( __CLASS__, 'labels' ) );
		add_filter( 'sm_activity_link', array( __CLASS__, 'link' ), 10, 2 );
	}

	public static function engineer_vars( $entry ) {
		$e = Engineers::get( (int) $entry->object_id );
		if ( ! $e ) {
			return array();
		}
		$types = Settings::types( true );
		return Events::user_vars( $e->user_id ) + Events::company_vars( $e->company_id ) + array(
			'titre'       => $e->title,
			'diplome'     => trim( $e->diploma . ( $e->school ? ' — ' . $e->school : '' ) . ( $e->grad_year ? ' (' . $e->grad_year . ')' : '' ) ),
			'experience'  => (string) $e->experience,
			'specialites' => implode( ', ', array_map( static fn( $s ) => $types[ $s ]['fr'] ?? $s, $e->specialties ) ),
			'lien'        => \SM\Core\Routing\Routes::url( 'account' ),
			'lien_admin'  => admin_url( 'admin.php?page=' . Admin\Admin::ENGINEERS . '&id=' . (int) $e->company_id ),
		);
	}

	public static function mission_vars( $entry ) {
		$m = Missions::get( (int) $entry->object_id );
		if ( ! $m ) {
			return array();
		}
		$c = Events::company_vars( $m->client_id );
		$e = $m->engineer_id ? Events::company_vars( $m->engineer_id ) : array( 'entreprise' => '' );
		return Events::user_vars( $m->client_user ) + $c + array(
			'reference'  => $m->ref,
			'titre'      => $m->title,
			'type_etude' => Missions::services_label( $m ),
			'prestations' => implode( "\n", array_map( static fn( $l ) => '• ' . $l['title'] . ( $l['labels'] ? ' : ' . implode( ', ', $l['labels'] ) : '' ), Missions::lines( $m ) ) ),
			'type_projet' => ProjectTypes::label( $m->project_type ),
			'avance'     => $m->advance_amount > 0 ? number_format( $m->advance_amount, 0, ',', ' ' ) . ' DA' : '',
			'offre'      => $m->quote_amount > 0 ? Notifications::quote_text( $m ) : '',
			'client'     => $c['entreprise'],
			'ingenieur'  => $e['entreprise'],
			'message'    => (string) $m->description,
			'montant'    => $m->quote_amount > 0 ? number_format( $m->quote_amount, 0, ',', ' ' ) . ' DA' : '',
			'honoraires' => $m->engineer_fee > 0 ? number_format( $m->engineer_fee, 0, ',', ' ' ) . ' DA' : '',
			'lien'       => Notifications::link( 'client', $m->id ),
			'lien_admin' => Notifications::admin_link( $m->id ),
		);
	}

	public static function events( array $events ) {
		$mvars = array( 'reference', 'titre', 'type_etude', 'prestations', 'type_projet', 'avance', 'offre', 'client', 'ingenieur', 'nom', 'email', 'telephone', 'wilaya', 'message', 'montant', 'honoraires', 'lien', 'lien_admin' );
		$events['engineer.applied']   = array(
			'group'     => 'engineering',
			'label'     => __( 'Partner engineer application', 'sm-engineering' ),
			'audit'     => 'engineer.applied',
			'admin'     => 'now',
			'user'      => true,
			'vars'      => array( __CLASS__, 'engineer_vars' ),
			'to'        => array( Events::class, 'author' ),
			'reply_to'  => 'email',
			'variables' => array( 'nom', 'email', 'telephone', 'wilaya', 'titre', 'diplome', 'experience', 'specialites', 'lien', 'lien_admin' ),
			'describe'  => static fn( $v ) => sprintf( /* translators: 1: engineer, 2: title. */ __( '%1$s applied as a partner engineer (%2$s)', 'sm-engineering' ), $v['nom'], $v['titre'] ),
			'templates' => array(
				'admin' => array(
					static fn() => __( 'Partner engineer application: {nom}', 'sm-engineering' ),
					static fn() => __( "{nom} applied to become a partner engineer.\n\nTitle: {titre}\nDiploma: {diplome}\nExperience: {experience} years\nServices: {specialites}\nWilaya: {wilaya}\n\n{email} — {telephone}\n\nCheck the documents and decide:\n{lien_admin}", 'sm-engineering' ),
				),
				'user'  => array(
					static fn() => __( 'Your partner engineer application is received', 'sm-engineering' ),
					static fn() => __( "Hello {nom},\n\nThank you for applying to become a partner engineer of {site}. The platform team checks your diploma and your documents, usually within 3 working days, and tells you the decision by e-mail.\n\nOnce approved, the platform offers you missions matching the services you chose: {specialites}.\n\nFollow your application:\n{lien}\n\nThe {site} team", 'sm-engineering' ),
				),
			),
		);
		$events['engineer.change_requested'] = array(
			'group'     => 'engineering',
			'label'     => __( 'Partner engineer: change to approve', 'sm-engineering' ),
			'audit'     => 'engineer.change_requested',
			'admin'     => 'now',
			'user'      => null,
			'vars'      => array( __CLASS__, 'engineer_vars' ),
			'variables' => array( 'nom', 'email', 'telephone', 'wilaya', 'titre', 'lien_admin' ),
			'describe'  => static fn( $v ) => sprintf( /* translators: %s: engineer. */ __( '%s asks to change his partner profile', 'sm-engineering' ), $v['nom'] ),
			'templates' => array(
				'admin' => array(
					static fn() => __( 'Profile change to approve: {nom}', 'sm-engineering' ),
					static fn() => __( "{nom} asks to change his partner engineer profile. The approved profile stays in use until you decide.\n\nCompare before and after, then approve or refuse:\n{lien_admin}", 'sm-engineering' ),
				),
			),
		);
		$events['mission.submitted']  = array(
			'group'     => 'engineering',
			'label'     => __( 'Study request', 'sm-engineering' ),
			'audit'     => 'mission.submitted',
			'admin'     => 'now',
			'user'      => true,
			'vars'      => array( __CLASS__, 'mission_vars' ),
			'to'        => array( Events::class, 'author' ),
			'reply_to'  => 'email',
			'variables' => $mvars,
			'describe'  => static fn( $v ) => sprintf( /* translators: 1: client, 2: reference, 3: type of study. */ __( '%1$s asked for a study %2$s (%3$s)', 'sm-engineering' ), $v['client'], $v['reference'], $v['type_etude'] ),
			'templates' => array(
				'admin' => array(
					static fn() => __( 'Study request {reference}: {type_etude}', 'sm-engineering' ),
					static fn() => __( "{client} ({wilaya}) asks for a study.\n\n{reference} — {titre}\nProject: {type_projet}\nServices:\n{prestations}\n\n{message}\n\nContact: {nom} — {email} — {telephone}\n\nPrepare the quote:\n{lien_admin}", 'sm-engineering' ),
				),
				'user'  => array(
					static fn() => __( 'Your study request {reference} is received', 'sm-engineering' ),
					static fn() => __( "Hello {nom},\n\nYour request \"{titre}\" ({type_etude}) is received under the reference {reference}.\n\nThe platform studies it and sends you a quote, usually within 2 working days. Once you accept it, a partner engineer of the platform carries out the study and the platform delivers the documents in your account.\n\nFollow your request:\n{lien}\n\nThe {site} team", 'sm-engineering' ),
				),
			),
		);
		$events['mission.accepted']   = array(
			'group'     => 'engineering',
			'label'     => __( 'Study quote accepted', 'sm-engineering' ),
			'audit'     => 'mission.accepted',
			'admin'     => 'now',
			'user'      => null,
			'vars'      => array( __CLASS__, 'mission_vars' ),
			'variables' => $mvars,
			'describe'  => static fn( $v ) => sprintf( /* translators: 1: client, 2: reference, 3: amount. */ __( '%1$s accepted the quote of %2$s (%3$s)', 'sm-engineering' ), $v['client'], $v['reference'], $v['montant'] ),
			'templates' => array(
				'admin' => array(
					static fn() => __( 'Quote accepted: {reference} ({montant})', 'sm-engineering' ),
					static fn() => __( "{client} accepted the quote of {reference} — {titre}.\n\nAmount: {montant}\nAdvance to receive: {avance}\n\nThe mission is given to a partner engineer once the advance is received (orders to check in SM → Orders):\n{lien_admin}", 'sm-engineering' ),
				),
			),
		);
		$simple = static function ( $key, $label, $describe, $subject, $body ) use ( $mvars ) {
			return array(
				'group'     => 'engineering',
				'label'     => $label,
				'audit'     => $key,
				'admin'     => 'now',
				'user'      => null,
				'vars'      => array( __CLASS__, 'mission_vars' ),
				'variables' => $mvars,
				'describe'  => $describe,
				'templates' => array( 'admin' => array( $subject, $body ) ),
			);
		};
		$events['mission.advance_paid']     = $simple(
			'mission.advance_paid',
			__( 'Advance of a study received', 'sm-engineering' ),
			static fn( $v ) => sprintf( /* translators: 1: reference, 2: amount. */ __( 'Advance of %1$s received (%2$s)', 'sm-engineering' ), $v['reference'], $v['avance'] ),
			static fn() => __( 'Advance received: {reference}', 'sm-engineering' ),
			static fn() => __( "The advance of {reference} — {titre} is received ({avance}).\n\nGive the mission to a partner engineer:\n{lien_admin}", 'sm-engineering' )
		);
		$events['mission.payment_reminder'] = $simple(
			'mission.payment_reminder',
			__( 'Advance of a study not paid', 'sm-engineering' ),
			static fn( $v ) => sprintf( /* translators: %s: reference. */ __( 'Advance of %s not paid yet: reminder sent to the client', 'sm-engineering' ), $v['reference'] ),
			static fn() => __( 'Advance not paid: {reference}', 'sm-engineering' ),
			static fn() => __( "{client} accepted the quote of {reference} — {titre} but has not paid the advance ({avance}) yet. A reminder was sent to the client.\n\nContact: {nom} — {email} — {telephone}\n\n{lien_admin}", 'sm-engineering' )
		);
		$events['mission.expired_unpaid']   = $simple(
			'mission.expired_unpaid',
			__( 'Study cancelled: advance not paid', 'sm-engineering' ),
			static fn( $v ) => sprintf( /* translators: %s: reference. */ __( '%s cancelled: advance not paid before the end of the quote', 'sm-engineering' ), $v['reference'] ),
			static fn() => __( 'Cancelled, advance not paid: {reference}', 'sm-engineering' ),
			static fn() => __( "The advance of {reference} — {titre} ({client}) was not paid before the end of the quote. The request is cancelled and the client is told.\n\n{lien_admin}", 'sm-engineering' )
		);
		$events['mission.balance_paid']     = $simple(
			'mission.balance_paid',
			__( 'Balance of a study received', 'sm-engineering' ),
			static fn( $v ) => sprintf( /* translators: %s: reference. */ __( 'Balance of %s received', 'sm-engineering' ), $v['reference'] ),
			static fn() => __( 'Balance received: {reference}', 'sm-engineering' ),
			static fn() => __( "The balance of {reference} — {titre} is received. The documents are available to the client once the study is delivered.\n\n{lien_admin}", 'sm-engineering' )
		);
		$events['mission.engineer_declined'] = array(
			'group'     => 'engineering',
			'label'     => __( 'Mission declined by the engineer', 'sm-engineering' ),
			'audit'     => 'mission.engineer_declined',
			'admin'     => 'now',
			'user'      => null,
			'vars'      => array( __CLASS__, 'mission_vars' ),
			'variables' => $mvars,
			'describe'  => static fn( $v ) => sprintf( /* translators: %s: reference. */ __( 'The engineer declined %s', 'sm-engineering' ), $v['reference'] ),
			'templates' => array(
				'admin' => array(
					static fn() => __( 'Engineer to replace: {reference}', 'sm-engineering' ),
					static fn() => __( "The engineer declined {reference} — {titre}. Choose another partner engineer:\n{lien_admin}", 'sm-engineering' ),
				),
			),
		);
		$events['mission.review']     = array(
			'group'     => 'engineering',
			'label'     => __( 'Deliverables sent by the engineer', 'sm-engineering' ),
			'audit'     => 'mission.review',
			'admin'     => 'now',
			'user'      => null,
			'vars'      => array( __CLASS__, 'mission_vars' ),
			'variables' => $mvars,
			'describe'  => static fn( $v ) => sprintf( /* translators: 1: engineer, 2: reference. */ __( '%1$s sent the deliverables of %2$s', 'sm-engineering' ), $v['ingenieur'], $v['reference'] ),
			'templates' => array(
				'admin' => array(
					static fn() => __( 'Deliverables to check: {reference}', 'sm-engineering' ),
					static fn() => __( "{ingenieur} sent the deliverables of {reference} — {titre}.\n\nCheck them, then deliver them to the client or ask for changes:\n{lien_admin}", 'sm-engineering' ),
				),
			),
		);
		$events['mission.message']    = array(
			'group'     => 'engineering',
			'label'     => __( 'Message on a mission', 'sm-engineering' ),
			'audit'     => 'mission.message',
			'when'      => static fn( $e ) => empty( $e->data['platform'] ),
			'admin'     => 'digest',
			'user'      => null,
			'vars'      => array( __CLASS__, 'mission_vars' ),
			'variables' => $mvars,
			'describe'  => static fn( $v ) => sprintf( /* translators: %s: reference. */ __( 'New message on %s', 'sm-engineering' ), $v['reference'] ),
			'templates' => array(
				'admin' => array(
					static fn() => __( 'Message on {reference}', 'sm-engineering' ),
					static fn() => __( "New message on {reference} — {titre}.\n\nAnswer from the mission page:\n{lien_admin}", 'sm-engineering' ),
				),
			),
		);
		return $events;
	}

	public static function labels( array $labels ) {
		return $labels + array(
			'engineer.applied'          => __( 'Partner engineer application', 'sm-engineering' ),
			'engineer.approved'         => __( 'Partner engineer approved', 'sm-engineering' ),
			'engineer.rejected'         => __( 'Partner engineer application refused', 'sm-engineering' ),
			'engineer.suspended'        => __( 'Partner engineer suspended', 'sm-engineering' ),
			'engineer.profile_saved'    => __( 'Engineer profile saved', 'sm-engineering' ),
			'engineer.change_requested' => __( 'Partner engineer: change to approve', 'sm-engineering' ),
			'engineer.change_approved'  => __( 'Engineer profile change approved', 'sm-engineering' ),
			'engineer.change_refused'   => __( 'Engineer profile change refused', 'sm-engineering' ),
			'engineer.change_cancelled' => __( 'Engineer profile change cancelled', 'sm-engineering' ),
			'engineer.availability'     => __( 'Engineer availability changed', 'sm-engineering' ),
			'engineer.phone'            => __( 'Engineer phone changed', 'sm-engineering' ),
			'mission.submitted'         => __( 'Study request', 'sm-engineering' ),
			'mission.quoted'            => __( 'Quote sent to the client', 'sm-engineering' ),
			'mission.declined'          => __( 'Study request declined by the platform', 'sm-engineering' ),
			'mission.accepted'          => __( 'Quote accepted by the client', 'sm-engineering' ),
			'mission.quote_declined'    => __( 'Quote refused by the client', 'sm-engineering' ),
			'mission.cancelled'         => __( 'Study request cancelled', 'sm-engineering' ),
			'mission.assigned'          => __( 'Mission offered to an engineer', 'sm-engineering' ),
			'mission.unassigned'        => __( 'Mission withdrawn from the engineer', 'sm-engineering' ),
			'mission.started'           => __( 'Mission accepted by the engineer', 'sm-engineering' ),
			'mission.engineer_declined' => __( 'Mission declined by the engineer', 'sm-engineering' ),
			'mission.review'            => __( 'Deliverables sent by the engineer', 'sm-engineering' ),
			'mission.rework'            => __( 'Changes asked to the engineer', 'sm-engineering' ),
			'mission.delivered'         => __( 'Study delivered to the client', 'sm-engineering' ),
			'mission.closed'            => __( 'Mission closed', 'sm-engineering' ),
			'mission.fee_paid'          => __( 'Engineer fee paid', 'sm-engineering' ),
			'mission.client_paid'       => __( 'Client payment recorded', 'sm-engineering' ),
			'mission.client_unpaid'     => __( 'Client payment cancelled', 'sm-engineering' ),
			'mission.advance_paid'      => __( 'Advance of a study received', 'sm-engineering' ),
			'mission.balance_paid'      => __( 'Balance of a study received', 'sm-engineering' ),
			'mission.quote_reminder'    => __( 'Reminder: quote ending', 'sm-engineering' ),
			'mission.payment_reminder'  => __( 'Reminder: advance not paid', 'sm-engineering' ),
			'mission.expired_unpaid'    => __( 'Study cancelled: advance not paid', 'sm-engineering' ),
			'mission.message'           => __( 'Message on a mission', 'sm-engineering' ),
			'mission.file_downloaded'   => __( 'Mission file downloaded', 'sm-engineering' ),
			'mission.'                  => __( 'Mission', 'sm-engineering' ),
			'engineer.'                 => __( 'Partner engineer', 'sm-engineering' ),
		);
	}

	public static function link( $url, $entry ) {
		if ( $url ) {
			return $url;
		}
		if ( 'mission' === $entry->object_type ) {
			return Notifications::admin_link( (int) $entry->object_id );
		}
		if ( 'engineer' === $entry->object_type ) {
			return admin_url( 'admin.php?page=' . Admin\Admin::ENGINEERS . '&id=' . (int) $entry->object_id );
		}
		return $url;
	}
}
