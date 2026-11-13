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
			'type_etude' => Settings::type_label( $m->type ),
			'prestations' => implode( ', ', Missions::prestations( $m ) ),
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
		$mvars = array( 'reference', 'titre', 'type_etude', 'prestations', 'client', 'ingenieur', 'nom', 'email', 'telephone', 'wilaya', 'message', 'montant', 'honoraires', 'lien', 'lien_admin' );
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
					static fn() => __( "{client} ({wilaya}) asks for a study.\n\n{reference} — {titre}\nType: {type_etude}\nPrestations: {prestations}\n\n{message}\n\nContact: {nom} — {email} — {telephone}\n\nPrepare the quote:\n{lien_admin}", 'sm-engineering' ),
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
					static fn() => __( "{client} accepted the quote of {reference} — {titre}.\n\nAmount: {montant}\n\nAssign a partner engineer:\n{lien_admin}", 'sm-engineering' ),
				),
			),
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
