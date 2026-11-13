<?php
/**
 * Place of a listing and visit request (0.31.0, sm-products 0.12.0).
 *
 * The public sees the address (commune, wilaya, place name). The map is shown
 * when the listing plugin hands the exact position: map made public by the
 * seller, the seller himself, or a buyer whose visit was accepted. Buyers ask
 * for a visit with a date and a time (buildings and whole-lot sales).
 *
 * @var array $args { item: array, place: string }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$item  = (array) $args['item'];
$place = (string) ( $args['place'] ?? '' );
$visit = (array) ( $item['visit'] ?? array() );
$loc   = $item['location'] ?? null;
$req   = $visit['request'] ?? null;
if ( ! $visit || ( empty( $visit['eligible'] ) && ! $loc && '' === (string) ( $item['address'] ?? '' ) ) ) {
	return; // Nothing more than the wilaya already shown on the page.
}
$when = static fn( $dt ) => $dt ? sm_date( $dt ) . ' · ' . substr( (string) $dt, 11, 5 ) : '';
?>
<section class="sm-panel sm-place" id="sm-place">
	<h2 class="sm-panel__title"><?php esc_html_e( 'Location', 'sm-marketplace' ); ?></h2>
	<p class="sm-place__address"><?php sm_icon( 'map-pin' ); ?><span><strong><?php echo esc_html( $place ); ?></strong><?php if ( '' !== (string) ( $item['address'] ?? '' ) ) : ?><br><bdi><?php echo esc_html( $item['address'] ); ?></bdi><?php endif; ?></span></p>

	<?php if ( $req && 'accepted' === $req['status'] ) : ?>
		<p class="sm-notice sm-notice--success"><?php sm_icon( 'check' ); ?><span><?php echo esc_html( sprintf( /* translators: %s: date and time. */ __( 'Visit accepted: %s', 'sm-marketplace' ), $when( $req['visit_at'] ) ) ); ?></span></p>
	<?php endif; ?>

	<?php if ( $loc ) : ?>
		<?php sm_component( 'location-map', array( 'mode' => 'view', 'lat' => $loc['lat'], 'lng' => $loc['lng'], 'label' => sm_t( $item['title'] ) ) ); ?>
		<?php if ( ! empty( $visit['own'] ) && empty( $visit['map_public'] ) ) : ?>
			<p class="sm-help"><?php esc_html_e( 'Only you see this map. Buyers see it after you accept their visit request.', 'sm-marketplace' ); ?></p>
		<?php endif; ?>
	<?php elseif ( ! empty( $visit['has_position'] ) ) : ?>
		<p class="sm-help"><?php esc_html_e( 'The exact position is given after the agreement of the seller.', 'sm-marketplace' ); ?></p>
	<?php endif; ?>

	<?php if ( ! empty( $visit['eligible'] ) ) : ?>
		<?php if ( ! empty( $visit['own'] ) ) : ?>
			<p class="sm-place__actions"><a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'visits' ) ) ); ?>"><?php sm_icon( 'calendar' ); ?><?php esc_html_e( 'Visit requests received', 'sm-marketplace' ); ?></a></p>
		<?php elseif ( ! empty( $visit['guest'] ) ) : ?>
			<p class="sm-place__actions"><a class="sm-btn sm-btn--primary" href="<?php echo esc_url( add_query_arg( 'redirect_to', rawurlencode( sm_theme_current_url() . '#sm-place' ), sm_page_url( 'login' ) ) ); ?>"><?php sm_icon( 'calendar' ); ?><?php esc_html_e( 'Ask for a visit', 'sm-marketplace' ); ?></a></p>
			<p class="sm-help"><?php esc_html_e( 'Sign in to ask for a visit: the seller must know who is coming.', 'sm-marketplace' ); ?></p>
		<?php elseif ( $req && 'pending' === $req['status'] ) : ?>
			<p class="sm-notice sm-notice--info"><?php sm_icon( 'clock' ); ?><span><?php echo esc_html( sprintf( /* translators: %s: date and time. */ __( 'Visit asked for %s. Waiting for the answer of the seller.', 'sm-marketplace' ), $when( $req['visit_at'] ) ) ); ?></span></p>
			<?php sm_theme_form_open( 'visit_answer', array( 'class' => 'sm-place__actions' ) ); ?>
				<input type="hidden" name="visit_id" value="<?php echo esc_attr( (string) $req['id'] ); ?>">
				<button class="sm-btn sm-btn--ghost sm-btn--sm" type="submit" name="do" value="cancel"><?php esc_html_e( 'Cancel my request', 'sm-marketplace' ); ?></button>
			</form>
		<?php elseif ( $req && 'proposed' === $req['status'] ) : ?>
			<p class="sm-notice sm-notice--warn"><?php sm_icon( 'calendar' ); ?><span><?php echo esc_html( sprintf( /* translators: %s: date and time. */ __( 'The seller proposes another date: %s.', 'sm-marketplace' ), $when( $req['proposed_at'] ) ) ); ?><?php echo '' !== $req['reply'] ? ' ' . esc_html( $req['reply'] ) : ''; ?></span></p>
			<?php sm_theme_form_open( 'visit_answer', array( 'class' => 'sm-place__actions' ) ); ?>
				<input type="hidden" name="visit_id" value="<?php echo esc_attr( (string) $req['id'] ); ?>">
				<button class="sm-btn sm-btn--accent" type="submit" name="do" value="accept"><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Confirm this date', 'sm-marketplace' ); ?></button>
				<button class="sm-btn sm-btn--ghost" type="submit" name="do" value="cancel"><?php esc_html_e( 'Cancel my request', 'sm-marketplace' ); ?></button>
			</form>
		<?php elseif ( $req && 'accepted' === $req['status'] ) : ?>
			<?php sm_theme_form_open( 'visit_answer', array( 'class' => 'sm-place__actions' ) ); ?>
				<input type="hidden" name="visit_id" value="<?php echo esc_attr( (string) $req['id'] ); ?>">
				<button class="sm-btn sm-btn--ghost sm-btn--sm" type="submit" name="do" value="cancel"><?php esc_html_e( 'Cancel the visit', 'sm-marketplace' ); ?></button>
			</form>
		<?php else : ?>
			<?php if ( $req && 'refused' === $req['status'] ) : ?>
				<p class="sm-notice sm-notice--warn"><?php sm_icon( 'alert' ); ?><span><?php esc_html_e( 'The seller could not accept your last visit request.', 'sm-marketplace' ); ?><?php echo '' !== $req['reply'] ? ' ' . esc_html( $req['reply'] ) : ''; ?></span></p>
			<?php endif; ?>
			<p class="sm-place__actions"><button type="button" class="sm-btn sm-btn--primary" data-smc-open="sm-visit"><?php sm_icon( 'calendar' ); ?><?php esc_html_e( 'Ask for a visit', 'sm-marketplace' ); ?></button></p>
			<dialog class="smc-dialog" id="sm-visit" aria-labelledby="sm-visit-t">
				<div class="smc-dialog__head">
					<div>
						<h2 id="sm-visit-t"><?php esc_html_e( 'Ask for a visit', 'sm-marketplace' ); ?></h2>
						<p><?php esc_html_e( 'The seller accepts, refuses or proposes another date. You are told by notification and e-mail.', 'sm-marketplace' ); ?></p>
					</div>
					<button type="button" class="smc-dialog__close" data-smc-close aria-label="<?php esc_attr_e( 'Close', 'sm-marketplace' ); ?>"><?php sm_icon( 'close' ); ?></button>
				</div>
				<div class="smc-dialog__body">
					<?php sm_theme_form_open( 'visit_request', array( 'class' => 'sm-form' ) ); ?>
						<input type="hidden" name="product_id" value="<?php echo esc_attr( (string) ( $item['product_id'] ?? 0 ) ); ?>">
						<div class="sm-field sm-field--row">
							<div>
								<label for="sm-visit-date"><?php esc_html_e( 'Date wanted', 'sm-marketplace' ); ?></label>
								<input id="sm-visit-date" type="date" name="visit_date" required min="<?php echo esc_attr( (string) ( $visit['min_date'] ?? '' ) ); ?>" max="<?php echo esc_attr( (string) ( $visit['max_date'] ?? '' ) ); ?>" value="<?php echo esc_attr( (string) sm_theme_old( 'visit_date' ) ); ?>">
							</div>
							<div>
								<label for="sm-visit-time"><?php esc_html_e( 'Time of the visit', 'sm-marketplace' ); ?></label>
								<input id="sm-visit-time" type="time" name="visit_time" required value="<?php echo esc_attr( (string) sm_theme_old( 'visit_time', '10:00' ) ); ?>">
							</div>
						</div>
						<div class="sm-field">
							<label for="sm-visit-msg"><?php esc_html_e( 'Message', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
							<textarea id="sm-visit-msg" name="message" rows="3" dir="auto" maxlength="1000" placeholder="<?php esc_attr_e( 'Number of people, questions…', 'sm-marketplace' ); ?>"><?php echo esc_textarea( (string) sm_theme_old( 'message' ) ); ?></textarea>
						</div>
						<div class="smc-dialog__actions">
							<button type="button" class="sm-btn sm-btn--ghost" data-smc-close><?php esc_html_e( 'Cancel', 'sm-marketplace' ); ?></button>
							<button type="submit" class="sm-btn sm-btn--accent"><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Send the request', 'sm-marketplace' ); ?></button>
						</div>
					</form>
				</div>
			</dialog>
		<?php endif; ?>
	<?php endif; ?>
</section>
