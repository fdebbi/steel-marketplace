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
// 0.34.0: whole bars: the buyer counts bars, the weight and the total follow; an offer is made per kg.
$bars   = $det && ! $pick && ! empty( $det['bar_kg'] ) ? $det : null;
$sum    = $is_b || $bars || $pick;
// 0.38.0: reuse in several lengths: bars of the lengths chosen, or the whole lot.
$pick_add = $pick && ! $is_b;
$per_kg = $pick || ( $bars && ! empty( $bars['per_kg'] ) );
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

			<?php if ( $lot && $det && ( $is_b || $bars || $pick ) ) : ?>
				<fieldset class="sm-field">
					<legend class="screen-reader-text"><?php esc_html_e( 'What do you buy?', 'sm-marketplace' ); ?></legend>
					<div class="sm-segment sm-buy__kind sm-buy__mode">
						<?php if ( $is_b ) : ?>
							<label><input type="radio" name="mode" value="lot" data-total="<?php echo esc_attr( $lot['total'] ); ?>" <?php checked( $mode, 'lot' ); ?>><span><?php esc_html_e( 'Complete building', 'sm-marketplace' ); ?></span></label>
							<label><input type="radio" name="mode" value="detail" <?php checked( $mode, 'detail' ); ?>><span><?php esc_html_e( 'By element', 'sm-marketplace' ); ?></span></label>
						<?php elseif ( $pick_add ) : ?>
							<label><input type="radio" name="mode" value="detail" <?php checked( $mode, 'detail' ); ?>><span><?php esc_html_e( 'Bars', 'sm-marketplace' ); ?></span></label>
							<label><input type="radio" name="mode" value="lot" data-total="<?php echo esc_attr( $lot['total'] ); ?>" <?php checked( $mode, 'lot' ); ?>><span><?php esc_html_e( 'Complete lot', 'sm-marketplace' ); ?></span></label>
						<?php else : ?>
							<label><input type="radio" name="mode" value="detail" <?php checked( $mode, 'detail' ); ?>><span><?php esc_html_e( 'Retail', 'sm-marketplace' ); ?></span></label>
							<label><input type="radio" name="mode" value="lot" data-total="<?php echo esc_attr( $lot['total'] ); ?>" <?php checked( $mode, 'lot' ); ?>><span><?php esc_html_e( 'Complete lot', 'sm-marketplace' ); ?></span></label>
						<?php endif; ?>
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

			<?php if ( $lot && $det && ( $is_b || $bars || $pick ) ) : ?>
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
				<fieldset class="sm-field sm-buy__qty sm-buy__pick" data-sm-buy-pick data-unit-price="<?php echo esc_attr( $det['unit_price'] ); ?>"<?php echo $pick_add ? ' data-sm-pick-add' : ''; ?><?php sm_theme_aria( 'items' ); ?>>
					<legend><?php echo esc_html( $pick_add ? __( 'Choose the lengths and the number of bars', 'sm-marketplace' ) : __( 'Choose the elements and their number', 'sm-marketplace' ) ); ?></legend>
					<?php foreach ( $pick as $n => $l ) : ?>
						<?php
						$l_name = trim( ( $l['label'] ? sm_t( $l['label'] ) . ' · ' : '' ) . $l['designation'] );
						$l_val  = (int) ( $old_items[ $l['key'] ] ?? 0 );
						?>
						<div class="sm-buy__line<?php echo $l_val > 0 ? ' is-on' : ''; ?>" data-sm-buy-line data-sm-pick-label="<?php echo esc_attr( wp_strip_all_tags( $l_name ) . ' · ' . sm_number( $l['length_mm'] / 1000, fmod( $l['length_mm'], 100 ) ? 2 : ( fmod( $l['length_mm'], 1000 ) ? 1 : 0 ) ) . ' m (' . sprintf( /* translators: %s: number of pieces. */ __( '%s available', 'sm-marketplace' ), sm_number( $l['qty'] ) ) . ')' ); ?>">
							<label for="buy-it-<?php echo (int) $n; ?>">
								<strong><?php echo sm_kses_tech( sm_tech( $l_name ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></strong>
								<small>
									<?php
									echo esc_html(
										sprintf(
											/* translators: 1: length in metres, 2: number of pieces, 3: weight of one piece in kg. */
											__( '%1$s m · %2$s available · %3$s kg each', 'sm-marketplace' ),
											sm_number( $l['length_mm'] / 1000, fmod( $l['length_mm'], 100 ) ? 2 : ( fmod( $l['length_mm'], 1000 ) ? 1 : 0 ) ),
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
							<?php if ( $pick_add ) : ?><button type="button" class="sm-buy__remove" data-sm-pick-remove aria-label="<?php esc_attr_e( 'Remove this length', 'sm-marketplace' ); ?>" hidden>×</button><?php endif; ?>
						</div>
					<?php endforeach; ?>
					<?php if ( $pick_add ) : ?>
						<p class="sm-buy__max" data-sm-pick-max hidden data-text="<?php /* translators: 1: number of bars, 2: length. */ esc_attr_e( 'Maximum reached: %1$s bars of %2$s available.', 'sm-marketplace' ); ?>"></p>
						<div class="sm-buy__add" data-sm-pick-adder hidden>
							<label class="screen-reader-text" for="buy-add-len"><?php esc_html_e( 'Add another length', 'sm-marketplace' ); ?></label>
							<select id="buy-add-len" data-sm-pick-select><option value=""><?php esc_html_e( '+ Add another length', 'sm-marketplace' ); ?></option></select>
						</div>
					<?php endif; ?>
					<p class="sm-help">
						<?php
						/* translators: %s: price per kg. */
						echo wp_kses_post( sprintf( __( 'Listed price: %s. The weight comes from the section tables.', 'sm-marketplace' ), sm_per_unit( $det['unit_price'], 'kg', false, $det['unit_price'] - floor( $det['unit_price'] ) > 0 ? 2 : 0 ) ) );
						?>
					</p>
					<?php sm_theme_error( 'items' ); ?>
				</fieldset>
			<?php elseif ( $bars ) : ?>
				<?php $len = sm_number( $bars['bar_len_mm'] / 1000, fmod( $bars['bar_len_mm'], 1000 ) ? 1 : 0 ); ?>
				<div class="sm-field sm-buy__qty sm-buy__pick" data-sm-buy-qty>
					<label for="buy-qty" class="sm-buy__picktitle">
						<?php
						/* translators: %s: length of a bar in metres. */
						echo esc_html( sprintf( __( 'Number of bars of %s m', 'sm-marketplace' ), $len ) );
						?>
					</label>
					<div class="sm-buy__line is-on" data-sm-buy-line>
						<span class="sm-buy__linetext">
							<strong><?php echo sm_kses_tech( sm_tech( sm_t( $item['title'] ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></strong>
							<small>
								<?php
								/* translators: 1: number of bars, 2: weight of one bar in kg. */
								echo esc_html( sprintf( __( '%1$s available · %2$s kg a bar', 'sm-marketplace' ), sm_number( $bars['max'] ), sm_number( $bars['bar_kg'], 1 ) ) );
								?>
							</small>
						</span>
						<span class="sm-buy__step">
							<button type="button" data-sm-buy-step="-1" aria-label="<?php esc_attr_e( 'One less', 'sm-marketplace' ); ?>">−</button>
							<input id="buy-qty" name="quantity" type="number" inputmode="numeric" min="1" max="<?php echo esc_attr( $bars['max'] ); ?>" step="1" value="<?php echo esc_attr( sm_theme_old( 'quantity', '1' ) ); ?>" data-unit-price="<?php echo esc_attr( $bars['unit_price'] ); ?>" data-kg="<?php echo esc_attr( $bars['bar_kg'] ); ?>" dir="ltr"<?php sm_theme_aria( 'quantity' ); ?>>
							<button type="button" data-sm-buy-step="1" aria-label="<?php esc_attr_e( 'One more', 'sm-marketplace' ); ?>">+</button>
						</span>
					</div>
					<?php sm_theme_error( 'quantity' ); ?>
				</div>
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

			<?php if ( $per_kg ) : ?>
				<div class="sm-field sm-buy__offer sm-buy__offerkg" data-sm-buy-offerkg>
					<label for="buy-amount-kg"><?php esc_html_e( 'Price per kg you offer (DA/kg)', 'sm-marketplace' ); ?></label>
					<input id="buy-amount-kg" name="amount_kg" type="text" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( sm_theme_old( 'amount_kg' ) ); ?>"<?php sm_theme_aria( 'amount_kg' ); ?>>
					<?php sm_theme_error( 'amount_kg' ); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! $sum ) : ?>
				<p class="sm-buy__total" aria-live="polite"><span><?php esc_html_e( 'Total at the listed price', 'sm-marketplace' ); ?></span><strong data-sm-buy-total data-currency="<?php echo esc_attr( sm_currency_label() ); ?>">—</strong></p>
			<?php endif; ?>

			<div class="sm-field sm-buy__offer sm-buy__offertotal" data-sm-buy-offer>
				<label for="buy-amount"><?php esc_html_e( 'Total price you offer (DA)', 'sm-marketplace' ); ?></label>
				<input id="buy-amount" name="amount" type="text" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( sm_theme_old( 'amount' ) ); ?>"<?php sm_theme_aria( 'amount' ); ?>>
				<p class="sm-help"><?php esc_html_e( 'The seller accepts, refuses or proposes another price. Up to 3 answers with a price; without an answer the offer closes after 7 days.', 'sm-marketplace' ); ?></p>
				<?php sm_theme_error( 'amount' ); ?>
			</div>

			<?php if ( $sum ) : ?>
				<?php $sum_lot = $is_b ? __( 'Complete building', 'sm-marketplace' ) : __( 'Complete lot', 'sm-marketplace' ); ?>
				<div class="sm-buy__sum" aria-live="polite">
					<p class="sm-buy__sumrow"><span data-sm-buy-what data-lot="<?php echo esc_attr( $sum_lot ); ?>" data-pieces="<?php echo esc_attr( $is_b ? __( 'pieces', 'sm-marketplace' ) : __( 'bars', 'sm-marketplace' ) ); ?>"><?php echo esc_html( $sum_lot ); ?></span><strong data-sm-buy-kg data-lot-kg="<?php echo esc_attr( (float) ( $item['weight_kg'] ?? 0 ) ); ?>">—</strong></p>
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
				<?php $me = (array) ( $box['contact'] ?? array() ); // 0.38.0: the order goes with the buyer's account, nothing to type. ?>
				<div class="sm-buy__me">
					<span class="sm-buy__avatar" aria-hidden="true"><?php echo esc_html( mb_strtoupper( mb_substr( (string) ( $me['name'] ?? '?' ), 0, 2 ) ) ); ?></span>
					<p><?php esc_html_e( 'Sent to the seller with the details of your account:', 'sm-marketplace' ); ?> <strong dir="auto"><?php echo esc_html( (string) ( $me['name'] ?? '' ) ); ?></strong><?php if ( ! empty( $me['phone'] ) ) : ?> · <bdi dir="ltr"><?php echo esc_html( $me['phone'] ); ?></bdi><?php endif; ?> · <bdi dir="ltr"><?php echo esc_html( (string) ( $me['email'] ?? '' ) ); ?></bdi></p>
				</div>
				<?php if ( empty( $me['phone'] ) ) : ?>
					<p class="sm-notice sm-notice--warn"><?php sm_icon( 'phone' ); ?><span><?php esc_html_e( 'Add your phone number in your account: the seller needs it to call you.', 'sm-marketplace' ); ?> <a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'settings' ) ) ); ?>"><?php esc_html_e( 'My account', 'sm-marketplace' ); ?></a></span></p>
				<?php endif; ?>
				<div class="sm-field"><label for="buy-msg"><?php esc_html_e( 'Message to the seller (optional)', 'sm-marketplace' ); ?></label><textarea id="buy-msg" name="message" rows="3" dir="auto" placeholder="<?php echo esc_attr( $is_b ? __( 'Date wanted, dismantling, transport…', 'sm-marketplace' ) : __( 'Delivery place, date, cutting…', 'sm-marketplace' ) ); ?>"><?php echo esc_textarea( sm_theme_old( 'message' ) ); ?></textarea></div>
				<button class="sm-btn sm-btn--accent sm-btn--block" type="submit" data-sm-buy-submit data-order="<?php esc_attr_e( 'Send the order', 'sm-marketplace' ); ?>" data-offer="<?php esc_attr_e( 'Send the offer', 'sm-marketplace' ); ?>"><?php sm_icon( 'cart' ); ?><span><?php echo esc_html( 'offer' === $kind ? __( 'Send the offer', 'sm-marketplace' ) : __( 'Send the order', 'sm-marketplace' ) ); ?></span></button>
				<p class="sm-help"><?php esc_html_e( 'Nothing is paid on ReuSteel. Once the seller accepts, you get the seller\'s contact details and agree on payment and delivery directly.', 'sm-marketplace' ); ?></p>
			<?php endif; ?>
		</form>
	<?php endif; ?>
</section>
