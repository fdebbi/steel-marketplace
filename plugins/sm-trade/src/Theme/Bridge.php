<?php
/**
 * Data for the theme:
 *
 *   trade_box     { id: listing id } what the visitor can do on a listing page (buy, offer)
 *   deals         { role: buyer|seller, status: open|closed|'', page } orders and offers of the account
 *   deal          { id } one order or offer, its steps and the answers the visitor can give
 *   trade_waiting { seller, buyer } orders and offers waiting for an answer of the visitor
 *
 * @package SM\Trade
 */

namespace SM\Trade\Theme;

use SM\Core\Accounts\Companies;
use SM\Products\Presenter;
use SM\Products\Products;
use SM\Products\Stock;
use SM\Trade\Deals;
use SM\Trade\Form;
use SM\Trade\Notifications;

defined( 'ABSPATH' ) || exit;

final class Bridge {

	public static function init() {
		add_filter( 'sm_theme_data_trade_box', array( __CLASS__, 'box' ), 10, 2 );
		add_filter( 'sm_theme_data_deals', array( __CLASS__, 'deals' ), 10, 2 );
		add_filter( 'sm_theme_data_deal', array( __CLASS__, 'deal' ), 10, 2 );
		add_filter( 'sm_theme_data_trade_waiting', array( __CLASS__, 'waiting' ), 10, 2 );
	}

	public static function unit_label( $unit ) {
		return 'lot' === $unit ? __( 'whole lot', 'sm-trade' ) : Presenter::unit_label( $unit );
	}

	public static function box( $result, $args ) {
		$p = Products::by_listing( (int) ( $args['id'] ?? 0 ) );
		if ( ! $p ) {
			return $result;
		}
		$opts    = Stock::options( $p );
		$user_id = get_current_user_id();
		$here    = sm_url( 'listing', array( 'id' => $p->listing_id ) );
		$out     = array(
			'product_id' => (int) $p->id,
			'state'      => 'ok',
			'reason'     => '',
			'login_url'  => Form::login_url( $here . '#sm-buy' ),
			'open_url'   => '',
			'lot'        => $opts['lot'],
			'detail'     => $opts['detail'] ? array_merge( $opts['detail'], array( 'unit_label' => Presenter::unit_label( $opts['detail']['unit'] ) ) ) : null,
			'contact'    => array( 'name' => '', 'phone' => '', 'email' => '' ),
			'can_buy'    => (bool) ( $opts['lot'] || $opts['detail'] ),
		);
		if ( 'published' !== $p->status ) {
			$out['state'] = 'unavailable';
			return $out;
		}
		if ( ! $out['can_buy'] ) {
			$out['state'] = 'unavailable';
			return $out;
		}
		if ( ! $user_id ) {
			$out['state'] = 'login';
			return $out;
		}
		$company = Companies::for_user( $user_id );
		if ( $company && (int) $company->id === (int) $p->company_id ) {
			$out['state'] = 'own';
			return $out;
		}
		$allowed = sm_can_explain( 'buy' );
		if ( is_wp_error( $allowed ) ) {
			$out['state']  = 'blocked';
			$out['reason'] = $allowed->get_error_message();
			return $out;
		}
		$open = Deals::open_for( $p->id, $user_id );
		if ( $open ) {
			$out['state']    = 'open';
			$out['open_url'] = Notifications::deal_url( $open->id );
			$out['open_ref'] = $open->ref;
		}
		$user           = wp_get_current_user();
		$out['contact'] = array(
			'name'  => $user->display_name,
			'phone' => (string) get_user_meta( $user_id, 'sm_phone', true ),
			'email' => $user->user_email,
		);
		return $out;
	}

	private static function status_label( $d ) {
		if ( 'open' === $d->status ) {
			return 'seller' === $d->turn ? __( 'Waiting for the seller', 'sm-trade' ) : __( 'Waiting for the buyer', 'sm-trade' );
		}
		return array(
			'accepted'  => __( 'Accepted', 'sm-trade' ),
			'refused'   => __( 'Refused', 'sm-trade' ),
			'cancelled' => __( 'Cancelled', 'sm-trade' ),
			'expired'   => __( 'Expired', 'sm-trade' ),
		)[ $d->status ] ?? $d->status;
	}

	private static function action_label( $e ) {
		$who = array(
			'buyer'  => __( 'Buyer', 'sm-trade' ),
			'seller' => __( 'Seller', 'sm-trade' ),
			'system' => __( 'Platform', 'sm-trade' ),
			'admin'  => __( 'Platform', 'sm-trade' ),
		)[ $e->actor ] ?? $e->actor;
		$what = array(
			'created'   => __( 'sent the request', 'sm-trade' ),
			'countered' => __( 'proposed another price', 'sm-trade' ),
			'accepted'  => __( 'accepted', 'sm-trade' ),
			'refused'   => __( 'refused', 'sm-trade' ),
			'cancelled' => __( 'cancelled', 'sm-trade' ),
			'expired'   => __( 'closed: no answer within 7 days', 'sm-trade' ),
		)[ $e->action ] ?? $e->action;
		return $who . ' · ' . $what;
	}

	public static function shape( $d, $user_id = 0 ) {
		$seller = Companies::get( (int) $d->seller_company_id );
		$role   = Deals::role( $d, $user_id );
		return array(
			'id'           => (int) $d->id,
			'ref'          => $d->ref,
			'kind'         => $d->kind,
			'kind_label'   => 'offer' === $d->kind ? __( 'Offer', 'sm-trade' ) : __( 'Order', 'sm-trade' ),
			'status'       => $d->status,
			'status_label' => self::status_label( $d ),
			'my_turn'      => 'open' === $d->status && $role === $d->turn,
			'role'         => $role,
			'title'        => $d->title,
			'listing_url'  => sm_url( 'listing', array( 'id' => (int) $d->listing_id ) ),
			'url'          => Notifications::deal_url( $d->id ),
			'market'       => $d->market,
			'mode'         => $d->mode,
			'quantity'     => (float) $d->quantity,
			'unit'         => $d->unit,
			'unit_label'   => self::unit_label( $d->unit ),
			'listed_total' => (float) $d->listed_total,
			'amount'       => (float) $d->amount,
			'counters'     => (int) $d->counters,
			'counters_left' => max( 0, Deals::MAX_COUNTERS - (int) $d->counters ),
			'created'      => $d->created_at,
			'updated'      => $d->updated_at,
			'expires'      => 'open' === $d->status ? gmdate( 'Y-m-d H:i:s', strtotime( $d->updated_at . ' UTC' ) + Deals::EXPIRY_DAYS * DAY_IN_SECONDS ) : '',
			'seller'       => array( 'name' => $seller ? $seller->name : '', 'slug' => $seller ? $seller->slug : '' ),
			'buyer'        => array( 'name' => $d->buyer_name, 'phone' => $d->buyer_phone, 'email' => $d->buyer_email ),
			'message'      => (string) $d->message,
		);
	}

	public static function deals( $result, $args ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return array( 'items' => array(), 'total' => 0, 'pages' => 0 );
		}
		$role   = 'seller' === ( $args['role'] ?? '' ) ? 'seller' : 'buyer';
		$q      = array( 'per_page' => 20, 'page' => max( 1, (int) ( $args['page'] ?? 1 ) ) );
		$status = (string) ( $args['status'] ?? '' );
		if ( 'open' === $status ) {
			$q['status'] = 'open';
		} elseif ( 'closed' === $status ) {
			$q['status'] = array( 'accepted', 'refused', 'cancelled', 'expired' );
		}
		if ( 'seller' === $role ) {
			$company = Companies::for_user( $user_id );
			if ( ! $company ) {
				return array( 'items' => array(), 'total' => 0, 'pages' => 0 );
			}
			$q['seller'] = (int) $company->id;
		} else {
			$q['buyer'] = $user_id;
		}
		$r = Deals::query( $q );
		return array(
			'items' => array_map( static fn( $d ) => self::shape( $d, $user_id ), $r['items'] ),
			'total' => $r['total'],
			'pages' => $r['pages'],
		);
	}

	public static function deal( $result, $args ) {
		$d       = Deals::get( (int) ( $args['id'] ?? 0 ) );
		$user_id = get_current_user_id();
		$role    = Deals::role( $d, $user_id );
		if ( ! $d || ( ! $role && ! current_user_can( 'sm_moderate' ) ) ) {
			return null;
		}
		$out             = self::shape( $d, $user_id );
		$out['actions']  = Deals::actions( $d, $user_id );
		$out['events']   = array_map(
			static fn( $e ) => array(
				'label'  => self::action_label( $e ),
				'action' => $e->action,
				'actor'  => $e->actor,
				'amount' => (float) $e->amount,
				'note'   => (string) $e->note,
				'date'   => $e->created_at,
			),
			Deals::events( $d->id )
		);
		$out['seller_contacts'] = Deals::seller_contacts( $d );
		return $out;
	}

	public static function waiting( $result, $args ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return array( 'seller' => 0, 'buyer' => 0 );
		}
		$company = Companies::for_user( $user_id );
		return Deals::waiting( $company ? (int) $company->id : 0, $user_id );
	}
}
