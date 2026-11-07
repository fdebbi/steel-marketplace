<?php
/**
 * Seller actions (admin-post.php): save a product as a draft or submit it,
 * mark it sold, withdraw it, delete a draft.
 *
 * @package SM\Products
 */

namespace SM\Products;

use SM\Core\Accounts\Companies;
use SM\Core\Content\Media;
use SM\Core\Routing\Routes;
use SM\Core\Support\Forms;

defined( 'ABSPATH' ) || exit;

final class Form {

	public static function init() {
		add_action( 'admin_post_sm_product_save', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_sm_product_action', array( __CLASS__, 'handle_action' ) );
	}

	/** Product the current user may edit, or null. */
	public static function editable( $id, $user_id = null ) {
		$user_id = $user_id ?? get_current_user_id();
		$p       = Products::get( $id );
		if ( ! $p ) {
			return null;
		}
		return Companies::can_manage( $p->company_id, $user_id ) || ( (int) $p->user_id === (int) $user_id && Companies::member_role( $p->company_id, $user_id ) ) ? $p : null;
	}

	public static function handle_save() {
		Forms::begin( 'product_save' );
		$user_id = get_current_user_id();
		$company = Companies::for_user( $user_id );
		$intent  = sanitize_key( Forms::post( 'intent', 'submit' ) );
		$cat     = Categories::get( Forms::post( 'category' ) );
		$market  = Markets::get( $cat ? $cat->market : 'products' );
		$allowed = sm_can_explain( $market ? $market['cap'] : 'listing.products' );
		if ( is_wp_error( $allowed ) && 'refresh' !== $intent ) {
			Forms::back( array( $allowed->get_error_message() ) );
		}
		if ( ! $company ) {
			Forms::back( array( __( 'Your account has no company.', 'sm-products' ) ) );
		}
		$id      = absint( Forms::post( 'product_id' ) );
		$product = $id ? self::editable( $id, $user_id ) : null;
		if ( $id && ! $product ) {
			Forms::back( array( __( 'You cannot edit this product.', 'sm-products' ) ) );
		}
		if ( ! $id && ! Forms::rate_limit( 'product|' . $company->id, 30, DAY_IN_SECONDS ) ) {
			Forms::back( array( __( 'You published many products today. Try again tomorrow.', 'sm-products' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in Forms::begin().
		$input = wp_unslash( $_POST );
		if ( 'refresh' === $intent ) { // Without JavaScript: show the form again with the new choices.
			Forms::back( array( '_refresh' => '' ) );
		}
		$spec = Spec::build( (array) $input, 'draft' !== $intent );

		// Files: keep the ones still listed, add the new uploads (even if the form has errors).
		$s      = Settings::all();
		$keep   = array_map( 'absint', (array) ( $input['keep_photos'] ?? array() ) );
		$keep   = array_values( array_filter( $keep, static fn( $a ) => self::owns_attachment( $a, $user_id, $product ) ) );
		$new    = Media::upload( 'photos', Media::IMAGE_TYPES, max( 0, $s['max_photos'] - count( $keep ) ), $s['photo_mb'] * 1048576 );
		$photos = array_merge( $keep, $new['ids'] );
		$skeep  = array_map( 'absint', (array) ( $input['keep_sheets'] ?? array() ) );
		$skeep  = array_values( array_filter( $skeep, static fn( $a ) => self::owns_attachment( $a, $user_id, $product ) ) );
		$snew   = Media::upload( 'sheets', Media::PDF_TYPES, max( 0, 3 - count( $skeep ) ), $s['sheet_mb'] * 1048576 );
		$sheets = array_merge( $skeep, $snew['ids'] );
		$file_errors = array_merge( $new['errors'], $snew['errors'] );

		$errors = $spec['errors'];
		if ( 'draft' !== $intent && ! $photos ) {
			$errors['photos'] = __( 'Add at least one photo of the product.', 'sm-products' );
		}
		if ( 'draft' === $intent && empty( $spec['category'] ) ) {
			$errors = array( 'category' => $errors['category'] ?? __( 'Choose a category.', 'sm-products' ) );
		} elseif ( 'draft' === $intent ) {
			$errors = array(); // A draft may be incomplete.
		}
		if ( $errors || $file_errors ) {
			$_POST['keep_photos'] = $photos; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$_POST['keep_sheets'] = $sheets; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( $errors ) {
				Forms::back( array_merge( $errors, $file_errors ) );
			}
		}

		$status = 'draft';
		if ( 'draft' !== $intent ) {
			$status = (int) $s['moderation'] && ! current_user_can( 'sm_moderate' ) ? 'pending' : 'published';
		}
		$pid = Products::save( $id, $company->id, $user_id, $spec, $status, $photos, $sheets );
		foreach ( array_merge( $photos, $sheets ) as $aid ) {
			update_post_meta( $aid, '_sm_owner', $user_id );
		}
		if ( 'pending' === $status ) {
			\SM\Core\Notifications\Notifications::notify_moderators( 'product.submitted', array( 'product_id' => $pid, 'company_id' => $company->id ) );
		}
		$messages = array(
			'draft'     => __( 'Draft saved. You can finish it later from "My listings".', 'sm-products' ),
			'pending'   => __( 'Thank you. Your product will be visible after a quick check by the platform team.', 'sm-products' ),
			'published' => __( 'Your product is published.', 'sm-products' ),
		);
		$target = 'draft' === $status ? Routes::url( 'account', array( 'view' => 'publish', 'edit' => $pid ) ) : Routes::url( 'account', array( 'view' => 'listings' ) );
		if ( $file_errors ) {
			Forms::back( $file_errors, $messages[ $status ], $target );
		}
		Forms::back( array(), $messages[ $status ], $target );
	}

	/** An attachment uploaded by this user, or already linked to the product. */
	private static function owns_attachment( $aid, $user_id, $product ) {
		if ( ! $aid || 'attachment' !== get_post_type( $aid ) ) {
			return false;
		}
		if ( (int) get_post_field( 'post_author', $aid ) === (int) $user_id ) {
			return true;
		}
		return $product && ( in_array( $aid, Media::get( 'product', $product->id, 'photo' ), true ) || in_array( $aid, Media::get( 'product', $product->id, 'sheet' ), true ) );
	}

	public static function handle_action() {
		Forms::begin( 'product_action' );
		$p   = self::editable( absint( Forms::post( 'product_id' ) ) );
		$act = sanitize_key( Forms::post( 'do' ) );
		if ( ! $p ) {
			Forms::back( array( __( 'You cannot change this product.', 'sm-products' ) ) );
		}
		switch ( $act ) {
			case 'sold':
				if ( 'published' !== $p->status ) {
					Forms::back( array( __( 'Only a published product can be marked as sold.', 'sm-products' ) ) );
				}
				Products::set_status( $p->id, 'sold' );
				Forms::back( array(), __( 'Marked as sold. Buyers no longer see it.', 'sm-products' ) );
				break;
			case 'withdraw':
				Products::set_status( $p->id, 'draft' );
				Forms::back( array(), __( 'The product is withdrawn and saved as a draft.', 'sm-products' ) );
				break;
			case 'republish':
				if ( ! in_array( $p->status, array( 'sold', 'expired' ), true ) ) {
					Forms::back( array( __( 'This product cannot be published again from here. Edit it instead.', 'sm-products' ) ) );
				}
				$status = (int) Settings::get( 'moderation' ) && ! current_user_can( 'sm_moderate' ) ? 'pending' : 'published';
				Products::set_status( $p->id, $status );
				if ( 'pending' === $status ) {
					\SM\Core\Notifications\Notifications::notify_moderators( 'product.submitted', array( 'product_id' => $p->id, 'company_id' => $p->company_id ) );
				}
				Forms::back( array(), 'pending' === $status ? __( 'Sent for review again.', 'sm-products' ) : __( 'Your product is published.', 'sm-products' ) );
				break;
			case 'delete':
				if ( ! in_array( $p->status, array( 'draft', 'rejected', 'sold', 'expired' ), true ) ) {
					Forms::back( array( __( 'Withdraw the product before deleting it.', 'sm-products' ) ) );
				}
				Products::delete( $p->id );
				Forms::back( array(), __( 'Product deleted.', 'sm-products' ) );
				break;
		}
		Forms::back( array( __( 'Unknown action.', 'sm-products' ) ) );
	}
}
