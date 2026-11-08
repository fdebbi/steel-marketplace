<?php
/**
 * Offer form of a supplier on a request for quotation (sm-rfq). Totals and the
 * price per kg are computed by the server when the offer is sent.
 *
 * @var array $args { item: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$r    = $args['item'];
$mine = $r['my_offer'] ?? null;
$old  = sm_theme_old_all();
$val  = static function ( $k, $d = '' ) use ( $old, $mine ) {
	if ( isset( $old[ $k ] ) && ! is_array( $old[ $k ] ) ) {
		return (string) $old[ $k ];
	}
	if ( $mine && 'withdrawn' !== $mine['status'] && isset( $mine[ $k ] ) && ! is_array( $mine[ $k ] ) ) {
		return is_bool( $mine[ $k ] ) ? ( $mine[ $k ] ? '1' : '' ) : (string) ( is_float( $mine[ $k ] ) ? 0 + $mine[ $k ] : $mine[ $k ] );
	}
	return $d;
};
$mode = 'kg' === $val( 'price_mode', $r['weight_kg'] ? 'kg' : 'total' ) ? 'kg' : 'total';
$ws   = (array) sm_theme_data( 'rfq_settings' );
?>
<section class="sm-panel smq-offer" id="smq-offer">
	<h2 class="sm-panel__title"><?php echo esc_html( $mine && 'withdrawn' !== $mine['status'] ? __( 'Update your offer', 'sm-marketplace' ) : __( 'Send your offer', 'sm-marketplace' ) ); ?></h2>
	<?php sm_theme_form_open( 'rfq_offer', array( 'class' => 'sm-form smq-offer__form', 'enctype' => 'multipart/form-data' ) ); ?>
		<input type="hidden" name="rfq_id" value="<?php echo esc_attr( $r['id'] ); ?>">
		<fieldset class="sm-field">
			<legend class="sm-field__legend"><?php esc_html_e( 'Your price', 'sm-marketplace' ); ?></legend>
			<div class="sm-segment">
				<label><input type="radio" name="price_mode" value="kg" <?php checked( $mode, 'kg' ); ?>><span><?php esc_html_e( 'Per kg', 'sm-marketplace' ); ?></span></label>
				<label><input type="radio" name="price_mode" value="total" <?php checked( $mode, 'total' ); ?>><span><?php esc_html_e( 'Total price', 'sm-marketplace' ); ?></span></label>
			</div>
		</fieldset>
		<div class="sm-field">
			<label for="smq-price"><?php esc_html_e( 'Price', 'sm-marketplace' ); ?> <small>(<?php echo esc_html( sm_currency_label() ); ?>)</small></label>
			<input id="smq-price" name="price" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $val( 'price' ) ); ?>"<?php sm_theme_aria( 'price' ); ?>>
			<?php sm_theme_error( 'price' ); ?>
		</div>
		<div class="sm-field">
			<label for="smq-weight"><?php esc_html_e( 'Weight your price is for (kg)', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
			<input id="smq-weight" name="weight_kg" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( 'supplier' === ( $mine['weight_from'] ?? '' ) ? $val( 'weight_kg' ) : ( $old['weight_kg'] ?? '' ) ); ?>"<?php sm_theme_aria( 'weight_kg' ); ?> placeholder="<?php echo esc_attr( $r['weight_kg'] ? sm_number( $r['weight_kg'] ) : '' ); ?>">
			<p class="sm-help"><?php echo esc_html( $r['weight_kg'] ? sprintf( /* translators: %s: weight. */ __( 'Leave empty to quote for the requested weight (%s kg). Give your own if it differs (e.g. actual weight of your stock).', 'sm-marketplace' ), sm_number( $r['weight_kg'] ) ) : __( 'The request gives no weight: give it to show your price per kg.', 'sm-marketplace' ) ); ?></p>
			<?php sm_theme_error( 'weight_kg' ); ?>
		</div>
		<div class="sm-field sm-field--row">
			<div>
				<label for="smq-days"><?php esc_html_e( 'Delivery time (days)', 'sm-marketplace' ); ?></label>
				<input id="smq-days" name="delivery_days" inputmode="numeric" dir="ltr" value="<?php echo esc_attr( $val( 'delivery_days' ) ); ?>"<?php sm_theme_aria( 'delivery_days' ); ?>>
				<?php sm_theme_error( 'delivery_days' ); ?>
			</div>
			<div>
				<label for="smq-valid"><?php esc_html_e( 'Price valid for (days)', 'sm-marketplace' ); ?></label>
				<input id="smq-valid" name="validity_days" inputmode="numeric" dir="ltr" value="<?php echo esc_attr( $val( 'validity_days', '15' ) ); ?>"<?php sm_theme_aria( 'validity_days' ); ?>>
				<?php sm_theme_error( 'validity_days' ); ?>
			</div>
		</div>
		<label class="sm-check"><input type="checkbox" name="delivery_included" value="1" <?php checked( '1', $val( 'delivery_included' ) ); ?>><span><?php echo esc_html( sprintf( /* translators: %s: wilaya. */ __( 'Delivery to %s included in the price', 'sm-marketplace' ), sm_t( sm_theme_wilayas()[ $r['wilaya'] ] ?? '' ) ) ); ?></span></label>
		<div class="sm-field">
			<label for="smq-pay"><?php esc_html_e( 'Payment terms', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
			<input id="smq-pay" name="payment_terms" dir="auto" maxlength="190" value="<?php echo esc_attr( $val( 'payment_terms' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. 30 % on order, balance on delivery', 'sm-marketplace' ); ?>">
		</div>
		<div class="sm-field">
			<label for="smq-msg"><?php esc_html_e( 'Message to the buyer', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
			<textarea id="smq-msg" name="message" rows="4" dir="auto" maxlength="3000"><?php echo esc_textarea( $val( 'message' ) ); ?></textarea>
			<?php sm_theme_error( 'message' ); ?>
		</div>
		<?php foreach ( (array) ( $mine['files'] ?? array() ) as $f ) : ?>
			<label class="sm-check"><input type="checkbox" name="keep_files[]" value="<?php echo esc_attr( $f['id'] ); ?>" checked><span><?php sm_icon( 'file' ); ?><?php echo esc_html( $f['name'] ); ?></span></label>
		<?php endforeach; ?>
		<label class="sm-upload sm-upload--small">
			<input type="file" name="files[]" accept="application/pdf,image/jpeg,image/png,image/webp" multiple>
			<?php sm_icon( 'file', 'sm-upload__icon' ); ?>
			<strong><?php esc_html_e( 'Attach your quotation or data sheets', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></strong>
			<small><?php echo esc_html( sprintf( /* translators: 1: files, 2: MB. */ __( 'PDF or images, up to %1$d files of %2$d MB.', 'sm-marketplace' ), (int) ( $ws['max_files'] ?? 5 ), (int) ( $ws['file_mb'] ?? 10 ) ) ); ?></small>
		</label>
		<button class="sm-btn sm-btn--accent sm-btn--block" type="submit"><?php sm_icon( 'check' ); ?><?php echo esc_html( $mine && 'withdrawn' !== $mine['status'] ? __( 'Update my offer', 'sm-marketplace' ) : __( 'Send my offer', 'sm-marketplace' ) ); ?></button>
		<p class="sm-help"><?php esc_html_e( 'Only the buyer sees your offer. Your total and price per kg are calculated when you send it.', 'sm-marketplace' ); ?></p>
	</form>
</section>
