<?php
/**
 * One order or offer (0.24.0, sm-trade): what was asked, the buyer's details, the
 * answers the visitor can give (accept, refuse, another price, cancel), the seller's
 * contact details once accepted, and every step. Address: /compte/transaction/?deal=ID
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$d = sm_theme_data( 'deal', array( 'id' => (int) sm_theme_query_arg( 'deal' ) ) );
if ( ! is_array( $d ) || empty( $d['ref'] ) ) {
	sm_component( 'empty-state', array( 'icon' => 'cart', 'title' => __( 'Order not found', 'sm-marketplace' ), 'text' => __( 'This order or offer does not exist or belongs to another account.', 'sm-marketplace' ) ) );
	return;
}
$is_seller = 'seller' === $d['role'];
$back      = sm_page_url( 'account', array( 'view' => $is_seller ? 'sales' : 'purchases' ) );
$tone      = array( 'open' => 'warn', 'accepted' => 'ok', 'refused' => 'muted', 'cancelled' => 'muted', 'expired' => 'muted' );
$acts      = (array) $d['actions'];
$qty_text  = 'lot' === $d['mode'] ? ( 'buildings' === $d['market'] ? __( 'Complete building', 'sm-marketplace' ) : __( 'The whole lot', 'sm-marketplace' ) ) : sm_number( $d['quantity'], fmod( $d['quantity'], 1 ) ? 2 : 0 ) . ' ' . $d['unit_label'];
$items     = (array) ( $d['items'] ?? array() ); // Bought by element (0.33.0).
if ( $items ) {
	/* translators: 1: number of pieces, 2: weight. */
	$qty_text = sprintf( __( '%1$s pieces · %2$s kg', 'sm-marketplace' ), sm_number( array_sum( array_map( static fn( $i ) => (int) $i['n'], $items ) ) ), sm_number( $d['quantity'] ) );
}
$c         = $d['seller_contacts'];
?>
<p><a class="sm-link" href="<?php echo esc_url( $back ); ?>"><?php sm_icon( 'chevron-start' ); ?><?php esc_html_e( 'Back', 'sm-marketplace' ); ?></a></p>

<section class="sm-panel sm-order sm-dealview">
	<div class="sm-order__head">
		<div>
			<p class="sm-subnow__kicker"><span dir="ltr"><?php echo esc_html( $d['ref'] ); ?></span> · <?php echo esc_html( $d['kind_label'] ); ?></p>
			<h2 class="sm-subnow__name"><a href="<?php echo esc_url( $d['listing_url'] ); ?>"><?php echo esc_html( $d['title'] ); ?></a></h2>
		</div>
		<div class="sm-order__amount">
			<strong><?php echo esc_html( sm_money( $d['amount'] ) ); ?></strong>
			<small><?php echo esc_html( 'accepted' === $d['status'] ? __( 'Price agreed', 'sm-marketplace' ) : ( 'offer' === $d['kind'] ? __( 'Price proposed', 'sm-marketplace' ) : __( 'At the listed price', 'sm-marketplace' ) ) ); ?></small>
		</div>
	</div>
	<p>
		<span class="sm-status sm-status--<?php echo esc_attr( $tone[ $d['status'] ] ?? 'muted' ); ?>"><?php echo esc_html( $d['status_label'] ); ?></span>
		<?php if ( $d['my_turn'] ) : ?><span class="sm-deal__turn"><?php esc_html_e( 'Your answer is awaited', 'sm-marketplace' ); ?></span><?php endif; ?>
	</p>
	<dl class="sm-paydetails">
		<div><dt><?php esc_html_e( 'Quantity', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( $qty_text ); ?></dd></div>
		<div><dt><?php esc_html_e( 'Total at the listed price', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( sm_money( $d['listed_total'] ) ); ?></dd></div>
		<?php if ( 'offer' === $d['kind'] ) : ?>
			<div><dt><?php esc_html_e( 'Answers with a price left', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( sm_number( $d['counters_left'] ) ); ?></dd></div>
		<?php endif; ?>
		<div><dt><?php esc_html_e( 'Sent', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( sm_date( $d['created'] ) ); ?></dd></div>
		<?php if ( $d['expires'] ) : ?>
			<div><dt><?php esc_html_e( 'Closes without an answer on', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( sm_date( $d['expires'] ) ); ?></dd></div>
		<?php endif; ?>
		<div><dt><?php echo esc_html( $is_seller ? __( 'Buyer', 'sm-marketplace' ) : __( 'Seller', 'sm-marketplace' ) ); ?></dt><dd><?php echo esc_html( $is_seller ? $d['buyer']['name'] : $d['seller']['name'] ); ?></dd></div>
	</dl>
	<?php if ( $items ) : ?>
		<ul class="sm-dealview__items" role="list">
			<?php foreach ( $items as $it ) : ?>
				<li><span><strong><?php echo esc_html( sm_number( (int) $it['n'] ) ); ?> ×</strong> <?php echo sm_kses_tech( sm_tech( trim( ( ! empty( $it['label'] ) ? sm_t( $it['label'] ) . ' · ' : '' ) . $it['designation'] ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <small><?php echo sm_kses_tech( sm_tech( sm_number( $it['length_mm'] / 1000, fmod( $it['length_mm'], 1000 ) ? 2 : 1 ) . ' m' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></small></span><span><?php echo sm_kses_tech( sm_tech( sm_number( $it['kg'] ) . ' kg' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php if ( '' !== $d['message'] ) : ?>
		<blockquote class="sm-dealview__msg"><p><?php echo nl2br( esc_html( $d['message'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p></blockquote>
	<?php endif; ?>
</section>

<?php if ( $is_seller ) : ?>
	<section class="sm-panel sm-dealview__contact">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Buyer\'s contact details', 'sm-marketplace' ); ?></h2>
		<ul class="sm-contactlist">
			<li><?php sm_icon( 'user' ); ?><span><?php echo esc_html( $d['buyer']['name'] ); ?></span></li>
			<li><?php sm_icon( 'phone' ); ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $d['buyer']['phone'] ) ); ?>" dir="ltr"><?php echo esc_html( $d['buyer']['phone'] ); ?></a></li>
			<li><?php sm_icon( 'mail' ); ?><a href="mailto:<?php echo esc_attr( $d['buyer']['email'] ); ?>" dir="ltr"><?php echo esc_html( $d['buyer']['email'] ); ?></a></li>
		</ul>
	</section>
<?php elseif ( $c ) : ?>
	<section class="sm-panel sm-dealview__contact is-accepted">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Seller\'s contact details', 'sm-marketplace' ); ?></h2>
		<ul class="sm-contactlist">
			<li><?php sm_icon( 'company' ); ?><span><?php echo esc_html( $c['name'] ); ?></span></li>
			<?php if ( $c['phone'] ) : ?><li><?php sm_icon( 'phone' ); ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $c['phone'] ) ); ?>" dir="ltr"><?php echo esc_html( $c['phone'] ); ?></a></li><?php endif; ?>
			<?php if ( $c['whatsapp'] ) : ?><li><?php sm_icon( 'social-whatsapp' ); ?><a href="https://wa.me/<?php echo esc_attr( preg_replace( '/\D/', '', $c['whatsapp'] ) ); ?>" dir="ltr" rel="noopener" target="_blank"><?php echo esc_html( $c['whatsapp'] ); ?></a></li><?php endif; ?>
			<?php if ( $c['email'] ) : ?><li><?php sm_icon( 'mail' ); ?><a href="mailto:<?php echo esc_attr( $c['email'] ); ?>" dir="ltr"><?php echo esc_html( $c['email'] ); ?></a></li><?php endif; ?>
			<?php if ( $c['address'] ) : ?><li><?php sm_icon( 'map-pin' ); ?><span><?php echo esc_html( $c['address'] ); ?></span></li><?php endif; ?>
		</ul>
	</section>
<?php endif; ?>

<?php if ( 'accepted' === $d['status'] ) : ?>
	<p class="sm-notice sm-notice--info"><?php sm_icon( 'info' ); ?><span><?php esc_html_e( 'Payment and delivery are agreed directly between buyer and seller. ReuSteel does not take payments for listings: never pay before you have checked the goods or the seller.', 'sm-marketplace' ); ?></span></p>
<?php endif; ?>

<?php if ( $acts ) : ?>
	<section class="sm-panel sm-dealview__answer">
		<h2 class="sm-panel__title"><?php esc_html_e( 'Your answer', 'sm-marketplace' ); ?></h2>
		<?php if ( in_array( 'accept', $acts, true ) ) : ?>
			<?php sm_theme_form_open( 'trade_reply', array( 'class' => 'sm-form sm-dealview__form' ) ); ?>
				<input type="hidden" name="deal_id" value="<?php echo esc_attr( $d['id'] ); ?>"><input type="hidden" name="do" value="accept">
				<div class="sm-field"><label for="deal-note-a"><?php esc_html_e( 'Message (optional)', 'sm-marketplace' ); ?></label><textarea id="deal-note-a" name="note" rows="2" dir="auto"></textarea></div>
				<button class="sm-btn sm-btn--accent" type="submit"><?php sm_icon( 'check' ); ?><span>
					<?php
					/* translators: %s: total price. */
					echo esc_html( sprintf( __( 'Accept · %s', 'sm-marketplace' ), sm_money( $d['amount'] ) ) );
					?>
				</span></button>
				<?php if ( $is_seller ) : ?><p class="sm-help"><?php esc_html_e( 'The quantity is taken from your listing; at zero the listing is marked sold. The buyer then gets your contact details.', 'sm-marketplace' ); ?></p><?php endif; ?>
			</form>
		<?php endif; ?>

		<?php if ( in_array( 'counter', $acts, true ) ) : ?>
			<?php sm_theme_form_open( 'trade_reply', array( 'class' => 'sm-form sm-dealview__form' ) ); ?>
				<input type="hidden" name="deal_id" value="<?php echo esc_attr( $d['id'] ); ?>"><input type="hidden" name="do" value="counter">
				<div class="sm-field"><label for="deal-amount"><?php esc_html_e( 'Another total price (DA)', 'sm-marketplace' ); ?></label><input id="deal-amount" name="amount" type="text" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( sm_theme_old( 'amount' ) ); ?>"<?php sm_theme_aria( 'amount' ); ?>><?php sm_theme_error( 'amount' ); ?></div>
				<div class="sm-field"><label for="deal-note-c"><?php esc_html_e( 'Message (optional)', 'sm-marketplace' ); ?></label><textarea id="deal-note-c" name="note" rows="2" dir="auto"></textarea></div>
				<button class="sm-btn sm-btn--primary-outline" type="submit"><?php sm_icon( 'tag' ); ?><span><?php esc_html_e( 'Propose this price', 'sm-marketplace' ); ?></span></button>
				<p class="sm-help">
					<?php
					/* translators: %d: answers with a price left. */
					echo esc_html( sprintf( _n( '%d answer with a price left on this offer.', '%d answers with a price left on this offer.', (int) $d['counters_left'], 'sm-marketplace' ), (int) $d['counters_left'] ) );
					?>
				</p>
			</form>
		<?php endif; ?>

		<?php foreach ( array_intersect( array( 'refuse', 'cancel' ), $acts ) as $a ) : ?>
			<?php sm_theme_form_open( 'trade_reply', array( 'class' => 'sm-form sm-dealview__form sm-dealview__form--end' ) ); ?>
				<input type="hidden" name="deal_id" value="<?php echo esc_attr( $d['id'] ); ?>"><input type="hidden" name="do" value="<?php echo esc_attr( $a ); ?>">
				<div class="sm-field"><label for="deal-note-<?php echo esc_attr( $a ); ?>"><?php esc_html_e( 'Reason (optional, sent to the other party)', 'sm-marketplace' ); ?></label><textarea id="deal-note-<?php echo esc_attr( $a ); ?>" name="note" rows="2" dir="auto"></textarea></div>
				<button class="sm-btn sm-btn--ghost" type="submit"><?php sm_icon( 'close' ); ?><span><?php echo esc_html( 'cancel' === $a ? __( 'Cancel my request', 'sm-marketplace' ) : __( 'Refuse', 'sm-marketplace' ) ); ?></span></button>
			</form>
		<?php endforeach; ?>
	</section>
<?php endif; ?>

<section class="sm-panel">
	<h2 class="sm-panel__title"><?php esc_html_e( 'Steps', 'sm-marketplace' ); ?></h2>
	<ol class="sm-timeline">
		<?php foreach ( $d['events'] as $e ) : ?>
			<li class="sm-timeline__item sm-timeline__item--<?php echo esc_attr( $e['action'] ); ?>">
				<p><strong><?php echo esc_html( $e['label'] ); ?></strong><?php if ( in_array( $e['action'], array( 'created', 'countered', 'accepted' ), true ) ) : ?> · <?php echo esc_html( sm_money( $e['amount'] ) ); ?><?php endif; ?></p>
				<?php if ( '' !== $e['note'] ) : ?><p class="sm-timeline__note">« <?php echo esc_html( $e['note'] ); ?> »</p><?php endif; ?>
				<p class="sm-help"><?php echo esc_html( sm_date( $e['date'] ) . ' · ' . sm_ago( $e['date'] ) ); ?></p>
			</li>
		<?php endforeach; ?>
	</ol>
</section>
