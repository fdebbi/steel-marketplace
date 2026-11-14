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
// 0.33.0: a building is bought whole (no quantity) or by element (pieces picked in the inventory).
$pick = $det && ! empty( $det['lines'] ) ? (array) $det['lines'] : array();
$is_b = 'buildings' === ( $item['market'] ?? '' );
$old_items = (array) sm_theme_old( 'items', array() );
if ( $is_b && $lot && ! in_array( sm_theme_old( 'mode' ), array( 'lot', 'detail' ), true ) ) {
	$mode = 'lot'; // The whole building first.
}
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

			<?php if ( $lot && $det && $is_b ) : ?>
				<fieldset class="sm-field">
					<legend class="screen-reader-text"><?php esc_html_e( 'What do you buy?', 'sm-marketplace' ); ?></legend>
					<div class="sm-segment sm-buy__kind sm-buy__mode">
						<label><input type="radio" name="mode" value="lot" data-total="<?php echo esc_attr( $lot['total'] ); ?>" <?php checked( $mode, 'lot' ); ?>><span><?php esc_html_e( 'Complete building', 'sm-marketplace' ); ?></span></label>
						<label><input type="radio" name="mode" value="detail" <?php checked( $mode, 'detail' ); ?>><span><?php esc_html_e( 'By element', 'sm-marketplace' ); ?></span></label>
					</div>
				</fieldset>
			<?php endif; ?>

			<fieldset class="sm-field">
				<legend class="screen-reader-text"><?php esc_html_e( 'What do you want to do?', 'sm-marketplace' ); ?></legend>
				<div class="sm-segment sm-buy__kind">
					<label><input type="radio" name="kind" value="order" <?php checked( $kind, 'order' ); ?>><span><?php esc_html_e( 'Buy at this price', 'sm-marketplace' ); ?></span></label>
					<label><input type="radio" name="kind" value="offer" <?php checked( $kind, 'offer' ); ?>><span><?php esc_html_e( 'Make an offer', 'sm-marketplace' ); ?></span></label>
				</div>
			</fieldset>

			<?php if ( $lot && $det && $is_b ) : ?>
				<?php // Shown above, before the kind of request. ?>
			<?php elseif ( $lot && $det ) : ?>
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

			<?php if ( $pick ) : ?>
				<fieldset class="sm-field sm-buy__qty sm-buy__pick" data-sm-buy-pick data-unit-price="<?php echo esc_attr( $det['unit_price'] ); ?>"<?php sm_theme_aria( 'items' ); ?>>
					<legend><?php esc_html_e( 'Choose the elements and their number', 'sm-marketplace' ); ?></legend>
					<?php foreach ( $pick as $n => $l ) : ?>
						<?php
						$l_name = trim( ( $l['label'] ? sm_t( $l['label'] ) . ' · ' : '' ) . $l['designation'] );
						$l_val  = (int) ( $old_items[ $l['key'] ] ?? 0 );
						?>
						<div class="sm-buy__line<?php echo $l_val > 0 ? ' is-on' : ''; ?>" data-sm-buy-line>
							<label for="buy-it-<?php echo (int) $n; ?>">
								<strong><?php echo sm_kses_tech( sm_tech( $l_name ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></strong>
								<small>
									<?php
									echo esc_html(
										sprintf(
											/* translators: 1: length in metres, 2: number of pieces, 3: weight of one piece in kg. */
											__( '%1$s m · %2$s available · %3$s kg each', 'sm-marketplace' ),
											sm_number( $l['length_mm'] / 1000, fmod( $l['length_mm'], 1000 ) ? 2 : 1 ),
											sm_number( $l['qty'] ),
											sm_number( $l['piece_kg'], 1 )
										)
									);
									?>
								</small>
							</label>
							<span class="sm-buy__step">
								<button type="button" data-sm-buy-step="-1" aria-label="<?php esc_attr_e( 'One less', 'sm-marketplace' ); ?>">−</button>
								<input id="buy-it-<?php echo (int) $n; ?>" name="items[<?php echo esc_attr( $l['key'] ); ?>]" type="number" inputmode="numeric" min="0" max="<?php echo esc_attr( $l['qty'] ); ?>" step="1" value="<?php echo esc_attr( (string) $l_val ); ?>" data-kg="<?php echo esc_attr( $l['piece_kg'] ); ?>" dir="ltr">
								<button type="button" data-sm-buy-step="1" aria-label="<?php esc_attr_e( 'One more', 'sm-marketplace' ); ?>">+</button>
							</span>
						</div>
					<?php endforeach; ?>
					<p class="sm-help">
						<?php
						/* translators: %s: price per kg. */
						echo wp_kses_post( sprintf( __( 'Listed price: %s. The weight comes from the section tables.', 'sm-marketplace' ), sm_per_unit( $det['unit_price'], 'kg', false, $det['unit_price'] - floor( $det['unit_price'] ) > 0 ? 2 : 0 ) ) );
						?>
					</p>
					<?php sm_theme_error( 'items' ); ?>
				</fieldset>
			<?php elseif ( $det ) : ?>
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

			<?php if ( $pick ) : ?>
				<div class="sm-field sm-buy__offer sm-buy__offerkg" data-sm-buy-offerkg>
					<label for="buy-amount-kg"><?php esc_html_e( 'Price per kg you offer (DA/kg)', 'sm-marketplace' ); ?></label>
					<input id="buy-amount-kg" name="amount_kg" type="text" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( sm_theme_old( 'amount_kg' ) ); ?>"<?php sm_theme_aria( 'amount_kg' ); ?>>
					<?php sm_theme_error( 'amount_kg' ); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! $is_b ) : ?>
				<p class="sm-buy__total" aria-live="polite"><span><?php esc_html_e( 'Total at the listed price', 'sm-marketplace' ); ?></span><strong data-sm-buy-total data-currency="<?php echo esc_attr( sm_currency_label() ); ?>">—</strong></p>
			<?php endif; ?>

			<div class="sm-field sm-buy__offer sm-buy__offertotal" data-sm-buy-offer>
				<label for="buy-amount"><?php esc_html_e( 'Total price you offer (DA)', 'sm-marketplace' ); ?></label>
				<input id="buy-amount" name="amount" type="text" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( sm_theme_old( 'amount' ) ); ?>"<?php sm_theme_aria( 'amount' ); ?>>
				<p class="sm-help"><?php esc_html_e( 'The seller accepts, refuses or proposes another price. Up to 3 answers with a price; without an answer the offer closes after 7 days.', 'sm-marketplace' ); ?></p>
				<?php sm_theme_error( 'amount' ); ?>
			</div>

			<?php if ( $is_b ) : ?>
				<div class="sm-buy__sum" aria-live="polite">
					<p class="sm-buy__sumrow"><span data-sm-buy-what data-lot="<?php esc_attr_e( 'Complete building', 'sm-marketplace' ); ?>" data-pieces="<?php esc_attr_e( 'pieces', 'sm-marketplace' ); ?>"><?php esc_html_e( 'Complete building', 'sm-marketplace' ); ?></span><strong data-sm-buy-kg data-lot-kg="<?php echo esc_attr( (float) ( $item['weight_kg'] ?? 0 ) ); ?>">—</strong></p>
					<p class="sm-buy__sumrow"><span><?php esc_html_e( 'Listed price', 'sm-marketplace' ); ?></span><strong data-sm-buy-total data-currency="<?php echo esc_attr( sm_currency_label() ); ?>">—</strong></p>
					<p class="sm-buy__sumrow sm-buy__sumrow--offer"><span data-sm-buy-eqlabel data-kg="<?php esc_attr_e( 'Your offer comes to', 'sm-marketplace' ); ?>" data-total="<?php esc_attr_e( 'Total of your offer', 'sm-marketplace' ); ?>"><?php esc_html_e( 'Your offer comes to', 'sm-marketplace' ); ?></span><strong data-sm-buy-eq data-currency="<?php echo esc_attr( sm_currency_label() ); ?>">—</strong></p>
				</div>
			<?php endif; ?>

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
				<div class="sm-field"><label for="buy-msg"><?php esc_html_e( 'Message to the seller (optional)', 'sm-marketplace' ); ?></label><textarea id="buy-msg" name="message" rows="3" dir="auto" placeholder="<?php echo esc_attr( $is_b ? __( 'Date wanted, dismantling, transport…', 'sm-marketplace' ) : __( 'Delivery place, date, cutting…', 'sm-marketplace' ) ); ?>"><?php echo esc_textarea( sm_theme_old( 'message' ) ); ?></textarea></div>
				<button class="sm-btn sm-btn--accent sm-btn--block" type="submit" data-sm-buy-submit data-order="<?php esc_attr_e( 'Send the order', 'sm-marketplace' ); ?>" data-offer="<?php esc_attr_e( 'Send the offer', 'sm-marketplace' ); ?>"><?php sm_icon( 'cart' ); ?><span><?php echo esc_html( 'offer' === $kind ? __( 'Send the offer', 'sm-marketplace' ) : __( 'Send the order', 'sm-marketplace' ) ); ?></span></button>
				<p class="sm-help"><?php esc_html_e( 'Nothing is paid on ReuSteel. The seller receives your name, phone and e-mail with the quantity and the price; once accepted, you get the seller\'s contact details and agree on payment and delivery directly.', 'sm-marketplace' ); ?></p>
			<?php endif; ?>
		</form>
	<?php endif; ?>
</section>
