<?php
/**
 * E-mail events and journal entries of listings (SM Core → E-mails).
 *
 * @package SM\Products
 */

namespace SM\Products;

use SM\Core\Mail\Events;

defined( 'ABSPATH' ) || exit;

final class Activity {

	public static function init() {
		add_filter( 'sm_mail_events', array( __CLASS__, 'events' ) );
		add_filter( 'sm_activity_labels', array( __CLASS__, 'labels' ) );
		add_filter( 'sm_activity_link', array( __CLASS__, 'link' ), 10, 2 );
	}

	/** Variables of a listing: titre, marche, poids, prix, lien, lien_admin + company and author. */
	public static function vars( $entry ) {
		$p = Products::get( (int) $entry->object_id );
		if ( ! $p ) {
			return array();
		}
		$market = Markets::get( $p->market );
		return Events::user_vars( $p->user_id ) + Events::company_vars( $p->company_id ) + array(
			'titre'      => Presenter::title_text( $p ),
			'marche'     => $market ? Presenter::pick( (array) $market['label'] ) : $p->market,
			'poids'      => (float) $p->total_weight > 0 ? number_format_i18n( (float) $p->total_weight, 0 ) . ' kg' : '',
			'prix'       => (float) $p->price > 0 ? number_format_i18n( (float) $p->price, 2 ) . ' DA/' . $p->price_unit : '',
			'lien'       => sm_url( 'listing', array( 'id' => $p->listing_id ) ),
			'lien_admin' => admin_url( 'admin.php?page=' . Admin\Admin::SLUG ),
		);
	}

	public static function events( array $events ) {
		$vars = array( 'titre', 'marche', 'poids', 'prix', 'entreprise', 'nom', 'email', 'telephone', 'wilaya', 'lien', 'lien_admin' );
		$events['product.new'] = array(
			'group'     => 'listings',
			'label'     => __( 'New listing published', 'sm-products' ),
			'audit'     => 'product.saved',
			'when'      => static fn( $e ) => Events::is_new( $e ) && 'published' === ( $e->data['status'] ?? '' ),
			'admin'     => 'digest',
			'user'      => true,
			'vars'      => array( __CLASS__, 'vars' ),
			'to'        => array( Events::class, 'author' ),
			'variables' => $vars,
			'describe'  => static fn( $v ) => sprintf( /* translators: 1: company, 2: listing. */ __( '%1$s published "%2$s"', 'sm-products' ), $v['entreprise'], $v['titre'] ?? '' ),
			'templates' => array(
				'admin' => array(
					static fn() => __( 'New listing: {titre}', 'sm-products' ),
					static fn() => __( "{entreprise} ({wilaya}) published a listing.\n\n{titre}\nMarket: {marche}\nWeight: {poids}\nPrice: {prix}\n\nPublished by {nom} — {email} — {telephone}\n\n{lien}", 'sm-products' ),
				),
				'user'  => array(
					static fn() => __( 'Your listing is online: {titre}', 'sm-products' ),
					static fn() => __( "Your listing \"{titre}\" is published on {site}. Buyers can now find it and contact you.\n\nSee your listing:\n{lien}\n\nTip: listings with clear photos and a complete description get more enquiries.\n\nThe {site} team", 'sm-products' ),
				),
			),
		);
		$events['product.submitted'] = array(
			'group'     => 'listings',
			'label'     => __( 'New listing waiting for review', 'sm-products' ),
			'audit'     => 'product.saved',
			'when'      => static fn( $e ) => Events::is_new( $e ) && 'pending' === ( $e->data['status'] ?? '' ),
			'admin'     => 'now',
			'user'      => true,
			'vars'      => array( __CLASS__, 'vars' ),
			'to'        => array( Events::class, 'author' ),
			'variables' => $vars,
			'describe'  => static fn( $v ) => sprintf( /* translators: 1: company, 2: listing. */ __( '%1$s sent "%2$s" for review', 'sm-products' ), $v['entreprise'], $v['titre'] ?? '' ),
			'templates' => array(
				'admin' => array(
					static fn() => __( 'Listing to review: {titre}', 'sm-products' ),
					static fn() => __( "{entreprise} ({wilaya}) sent a listing for review.\n\n{titre}\nMarket: {marche}\nPrice: {prix}\n\nReview it:\n{lien_admin}", 'sm-products' ),
				),
				'user'  => array(
					static fn() => __( 'We received your listing: {titre}', 'sm-products' ),
					static fn() => __( "Your listing \"{titre}\" was received. The platform team checks it quickly; you are told as soon as it is online.\n\nThe {site} team", 'sm-products' ),
				),
			),
		);
		return $events;
	}

	public static function labels( array $labels ) {
		return $labels + array(
			'product.saved'          => __( 'Listing saved', 'sm-products' ),
			'product.published'      => __( 'Listing published', 'sm-products' ),
			'product.rejected'       => __( 'Listing sent back for changes', 'sm-products' ),
			'product.paused'         => __( 'Listing paused', 'sm-products' ),
			'product.sold'           => __( 'Listing marked as sold', 'sm-products' ),
			'product.expired'        => __( 'Listing expired', 'sm-products' ),
			'product.deleted'        => __( 'Listing deleted', 'sm-products' ),
			'product.'               => __( 'Listing', 'sm-products' ),
			'product_category.saved' => __( 'Product category changed', 'sm-products' ),
		);
	}

	public static function link( $url, $entry ) {
		if ( $url || 'product' !== $entry->object_type ) {
			return $url;
		}
		$p = Products::get( (int) $entry->object_id );
		return $p && $p->listing_id ? add_query_arg( 'preview', 1, sm_url( 'listing', array( 'id' => $p->listing_id ) ) ) : '';
	}
}
