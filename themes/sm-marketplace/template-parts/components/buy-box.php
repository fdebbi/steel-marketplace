<?php
/**
 * "Buy" and "Make an offer" on a listing page (0.24.0, data from sm-trade).
 *
 * Buy: the whole lot or a quantity at the listed price (what the seller chose).
 * Offer: the same choice of quantity, with the total price the buyer proposes.
 * The seller accepts or refuses (or answers with another price for an offer).
 * Nothing is paid on the platform.
 *
 * @var array $args { item: listing, box: sm_theme_data( 'trade_box' ) }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$box  = $args['box'];
$item = $args['item'];
$lot  = $box['lot'] ?? null;
$det  = $box['detail'] ?? null;
$kind = 'offer' === sm_theme_old( 'kind' ) ? 'offer' : 'order';
$mode = sm_theme_old( 'mode', $lot && ! $det ? 'lot' : ( $det ? 'detail' : 'lot' ) );
$dec  = $det && $det['step'] < 1 ? 2 : 0;
?>
<section class="sm-panel sm-buy" id="sm-buy" data-sm-buy-panel>
	<h2 class="sm-panel__title"><?php esc_html_e( 'Buy or make an offer', 'sm-marketplace' ); ?></h2>
	<?php
	// Messages of this form only (the contact form of the page has its own).
	if ( '' !== sm_theme_old( 'product_id' ) ) {
		sm_theme_notices();
	}
	?>

	<?php if ( 'open' === $box['state'] ) : ?>
		<p class="sm-notice sm-notice--info"><?php sm_icon( 'clock' ); ?><span>
			<?php
			/* translators: %s: reference of the order or offer. */
			echo esc_html( sprintf( __( 'You already have %s waiting on this listing.', 'sm-marketplace' ), $box['open_ref'] ?? '' ) );
			?>
			<a href="<?php echo esc_url( $box['open_url'] ); ?>"><?php esc_html_e( 'Follow it', 'sm-marketplace' ); ?></a></span></p>

	<?php elseif ( 'blocked' === $box['state'] ) : ?>
		<p class="sm-notice sm-notice--warn"><?php sm_icon( 'lock' ); ?><span><?php echo esc_html( $box['reason'] ); ?></span></p>

	<?php else : ?>
		<?php sm_theme_form_open( 'trade_create', array( 'class' => 'sm-form sm-buy__form', 'data-sm-buy-form' => '' ) ); ?>
			<input type="hidden" name="product_id" value="<?php echo esc_attr( $box['product_id'] ); ?>">

			<fieldset class="sm-field">
				<legend class="screen-reader-text"><?php esc_html_e( 'What do you want to do?', 'sm-marketplace' ); ?></legend>
				<div class="sm-segment sm-buy__kind">
					<label><input type="radio" name="kind" value="order" <?php checked( $kind, 'order' ); ?>><span><?php esc_html_e( 'Buy at this price', 'sm-marketplace' ); ?></span></label>
					<label><input type="radio" name="kind" value="offer" <?php checked( $kind, 'offer' ); ?>><span><?php esc_html_e( 'Make an offer', 'sm-marketplace' ); ?></span></label>
				</div>
			</fieldset>

			<?php if ( $lot && $det ) : ?>
				<fieldset class="sm-field">
					<legend><?php esc_html_e( 'Quantity', 'sm-marketplace' ); ?></legend>
					<label class="sm-check"><input type="radio" name="mode" value="lot" data-total="<?php echo esc_attr( $lot['total'] ); ?>" <?php checked( $mode, 'lot' ); ?>><span>
						<?php
						/* translators: %s: price of the whole lot. */
						echo esc_html( sprintf( __( 'The whole lot · %s', 'sm-marketplace' ), sm_money( $lot['total'] ) ) );
						?>
					</span></label>
					<label class="sm-check"><input type="radio" name="mode" value="detail" <?php checked( $mode, 'detail' ); ?>><span><?php esc_html_e( 'A quantity of my choice', 'sm-marketplace' ); ?></span></label>
				</fieldset>
			<?php else : ?>
				<input type="hidden" name="mode" value="<?php echo $det ? 'detail' : 'lot'; ?>"<?php echo $lot ? ' data-total="' . esc_attr( $lot['total'] ) . '"' : ''; ?>>
			<?php endif; ?>

			<?php if ( $det ) : ?>
				<div class="sm-field sm-buy__qty" data-sm-buy-qty>
					<label for="buy-qty">
						<?php
						/* translators: %s: unit (kg, pieces, m²…). */
						echo esc_html( sprintf( __( 'Quantity (%s)', 'sm-marketplace' ), $det['unit_label'] ) );
						?>
					</label>
					<input id="buy-qty" name="quantity" type="number" inputmode="decimal" min="<?php echo esc_attr( $det['step'] ); ?>" max="<?php echo esc_attr( $det['max'] ); ?>" step="<?php echo esc_attr( $det['step'] ); ?>" value="<?php echo esc_attr( sm_theme_old( 'quantity' ) ); ?>" data-unit-price="<?php echo esc_attr( $det['unit_price'] ); ?>" dir="ltr"<?php sm_theme_aria( 'quantity' ); ?>>
					<p class="sm-help">
						<?php
						/* translators: 1: available quantity, 2: unit, 3: price of one unit. */
						echo esc_html( sprintf( __( 'Available: %1$s %2$s · %3$s each', 'sm-marketplace' ), sm_number( $det['max'], $dec ), $det['unit_label'], sm_money( $det['unit_price'], $det['unit_price'] - floor( $det['unit_price'] ) > 0 ? 2 : 0 ) ) );
						?>
					</p>
					<?php sm_theme_error( 'quantity' ); ?>
				</div>
			<?php endif; ?>

			<p class="sm-buy__total" aria-live="polite"><span><?php esc_html_e( 'Total at the listed price', 'sm-marketplace' ); ?></span><strong data-sm-buy-total data-currency="<?php echo esc_attr( sm_currency_label() ); ?>">—</strong></p>

			<div class="sm-field sm-buy__offer" data-sm-buy-offer>
				<label for="buy-amount"><?php esc_html_e( 'Total price you offer (DA)', 'sm-marketplace' ); ?></label>
				<input id="buy-amount" name="amount" type="text" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( sm_theme_old( 'amount' ) ); ?>"<?php sm_theme_aria( 'amount' ); ?>>
				<p class="sm-help"><?php esc_html_e( 'The seller accepts, refuses or proposes another price. Up to 3 answers with a price; without an answer the offer closes after 7 days.', 'sm-marketplace' ); ?></p>
				<?php sm_theme_error( 'amount' ); ?>
			</div>

			<?php if ( 'login' === $box['state'] || 'own' === $box['state'] ) : ?>
				<?php if ( 'own' === $box['state'] ) : ?>
					<p class="sm-notice sm-notice--info"><?php sm_icon( 'info' ); ?><span><?php esc_html_e( 'This is your listing: buyers order and make offers here.', 'sm-marketplace' ); ?></span></p>
				<?php else : ?>
					<p class="sm-help"><?php esc_html_e( 'Sign in or create a free account to send your order or offer: the seller needs to know who buys.', 'sm-marketplace' ); ?></p>
					<div class="sm-row-wrap">
						<a class="sm-btn sm-btn--accent" href="<?php echo esc_url( $box['login_url'] ); ?>"><?php sm_icon( 'user' ); ?><span><?php esc_html_e( 'Sign in to continue', 'sm-marketplace' ); ?></span></a>
						<a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( sm_page_url( 'register' ) ); ?>"><?php esc_html_e( 'Create an account', 'sm-marketplace' ); ?></a>
					</div>
				<?php endif; ?>
			<?php else : ?>
				<div class="sm-field sm-field--row">
					<div><label for="buy-name"><?php esc_html_e( 'Full name', 'sm-marketplace' ); ?></label><input id="buy-name" name="name" autocomplete="name" dir="auto" required value="<?php echo esc_attr( sm_theme_old( 'name', $box['contact']['name'] ) ); ?>"<?php sm_theme_aria( 'name' ); ?>><?php sm_theme_error( 'name' ); ?></div>
					<div><label for="buy-phone"><?php esc_html_e( 'Phone', 'sm-marketplace' ); ?></label><input id="buy-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" dir="ltr" required value="<?php echo esc_attr( sm_theme_old( 'phone', $box['contact']['phone'] ) ); ?>"<?php sm_theme_aria( 'phone' ); ?>><?php sm_theme_error( 'phone' ); ?></div>
				</div>
				<div class="sm-field"><label for="buy-email"><?php esc_html_e( 'E-mail', 'sm-marketplace' ); ?></label><input id="buy-email" name="email" type="email" autocomplete="email" dir="ltr" required value="<?php echo esc_attr( sm_theme_old( 'email', $box['contact']['email'] ) ); ?>"<?php sm_theme_aria( 'email' ); ?>><?php sm_theme_error( 'email' ); ?></div>
				<div class="sm-field"><label for="buy-msg"><?php esc_html_e( 'Message to the seller (optional)', 'sm-marketplace' ); ?></label><textarea id="buy-msg" name="message" rows="3" dir="auto" placeholder="<?php esc_attr_e( 'Delivery place, date, cutting…', 'sm-marketplace' ); ?>"><?php echo esc_textarea( sm_theme_old( 'message' ) ); ?></textarea></div>
				<button class="sm-btn sm-btn--accent sm-btn--block" type="submit" data-sm-buy-submit data-order="<?php esc_attr_e( 'Send the order', 'sm-marketplace' ); ?>" data-offer="<?php esc_attr_e( 'Send the offer', 'sm-marketplace' ); ?>"><?php sm_icon( 'cart' ); ?><span><?php echo esc_html( 'offer' === $kind ? __( 'Send the offer', 'sm-marketplace' ) : __( 'Send the order', 'sm-marketplace' ) ); ?></span></button>
				<p class="sm-help"><?php esc_html_e( 'Nothing is paid on ReuSteel. The seller receives your name, phone and e-mail with the quantity and the price; once accepted, you get the seller\'s contact details and agree on payment and delivery directly.', 'sm-marketplace' ); ?></p>
			<?php endif; ?>
		</form>
	<?php endif; ?>
</section>
