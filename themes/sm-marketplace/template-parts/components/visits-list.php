<?php
/**
 * Visit requests of the company, received (seller) or sent (buyer) — 0.31.0.
 *
 * @var array $args { box: received|sent }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$box  = 'sent' === ( $args['box'] ?? '' ) ? 'sent' : 'received';
$rows = sm_theme_data( 'product_visits', array( 'box' => $box ) );
if ( ! is_array( $rows ) ) {
	sm_component( 'empty-state', array( 'icon' => 'calendar', 'title' => __( 'Visits', 'sm-marketplace' ), 'text' => __( 'This module is not active yet.', 'sm-marketplace' ) ) );
	return;
}
$labels = array(
	'pending'   => array( __( 'Waiting for the seller', 'sm-marketplace' ), 'warn' ),
	'proposed'  => array( __( 'Other date proposed', 'sm-marketplace' ), 'info' ),
	'accepted'  => array( __( 'Accepted', 'sm-marketplace' ), 'ok' ),
	'refused'   => array( __( 'Refused', 'sm-marketplace' ), 'muted' ),
	'cancelled' => array( __( 'Cancelled', 'sm-marketplace' ), 'muted' ),
);
if ( 'received' === $box ) {
	$labels['pending'][0] = __( 'To answer', 'sm-marketplace' );
}
$when = static fn( $dt ) => $dt ? sm_date( $dt ) . ' · ' . substr( (string) $dt, 11, 5 ) : '';
?>
<div class="sm-toolbar">
	<p class="sm-muted"><?php echo esc_html( 'sent' === $box ? __( 'The visits you asked for. When the seller accepts, the exact position is shown on the listing.', 'sm-marketplace' ) : __( 'Buyers who want to see your building or your lot. Accepting a visit shows the map to that buyer only.', 'sm-marketplace' ) ); ?></p>
</div>
<?php if ( ! $rows ) : ?>
	<?php
	sm_component(
		'empty-state',
		'sent' === $box
			? array( 'icon' => 'calendar', 'title' => __( 'No visit asked', 'sm-marketplace' ), 'text' => __( 'Open the listing of a building or of a lot and use "Ask for a visit".', 'sm-marketplace' ), 'action_label' => __( 'See the steel buildings', 'sm-marketplace' ), 'action_url' => sm_page_url( 'buildings' ) )
			: array( 'icon' => 'calendar', 'title' => __( 'No visit request yet', 'sm-marketplace' ), 'text' => __( 'Requests arrive here from your building listings and your whole-lot sales.', 'sm-marketplace' ) )
	);
	return;
	?>
<?php endif; ?>
<ul class="sm-visits" role="list">
	<?php foreach ( $rows as $r ) : ?>
		<?php
		$st    = $labels[ $r['status'] ] ?? array( $r['status'], 'muted' );
		$open  = in_array( $r['status'], array( 'pending', 'proposed', 'accepted' ), true ) && ! ( 'accepted' === $r['status'] && $r['passed'] );
		$url   = $r['listing_id'] ? sm_page_url( 'listing', array( 'id' => $r['listing_id'] ) ) : '';
		?>
		<li class="sm-panel sm-visit<?php echo $open ? '' : ' is-closed'; ?>">
			<div class="sm-visit__head">
				<div>
					<p class="sm-visit__title"><?php if ( $url && $r['online'] ) : ?><a href="<?php echo esc_url( $url ); ?>#sm-place"><?php echo esc_html( $r['title'] ); ?></a><?php else : ?><?php echo esc_html( $r['title'] ? $r['title'] : __( 'Listing removed', 'sm-marketplace' ) ); ?><?php endif; ?></p>
					<p class="sm-muted"><?php echo esc_html( 'sent' === $box ? sprintf( /* translators: %s: company. */ __( 'Seller: %s', 'sm-marketplace' ), $r['company'] ) : trim( $r['company'] . ( $r['contact'] && $r['contact'] !== $r['company'] ? ' · ' . $r['contact'] : '' ) ) ); ?></p>
				</div>
				<span class="sm-status sm-status--<?php echo esc_attr( $st[1] ); ?>"><?php echo esc_html( $st[0] ); ?></span>
			</div>
			<p class="sm-visit__when"><?php sm_icon( 'calendar' ); ?><strong><?php echo esc_html( $when( $r['visit_at'] ) ); ?></strong><?php if ( 'proposed' === $r['status'] ) : ?> <span class="sm-muted">→ <?php echo esc_html( sprintf( /* translators: %s: date and time. */ __( 'proposed: %s', 'sm-marketplace' ), $when( $r['proposed_at'] ) ) ); ?></span><?php endif; ?></p>
			<?php if ( '' !== $r['message'] ) : ?><p class="sm-visit__msg" dir="auto"><?php echo esc_html( $r['message'] ); ?></p><?php endif; ?>
			<?php if ( '' !== $r['reply'] ) : ?><p class="sm-visit__msg sm-visit__msg--reply" dir="auto"><strong><?php echo esc_html( 'sent' === $box ? __( 'Answer of the seller:', 'sm-marketplace' ) : __( 'Your answer:', 'sm-marketplace' ) ); ?></strong> <?php echo esc_html( $r['reply'] ); ?></p><?php endif; ?>

			<?php if ( 'received' === $box && $open ) : ?>
				<?php sm_theme_form_open( 'visit_reply', array( 'class' => 'sm-visit__actions' ) ); ?>
					<input type="hidden" name="visit_id" value="<?php echo esc_attr( (string) $r['id'] ); ?>">
					<?php if ( 'pending' === $r['status'] ) : ?>
						<button class="sm-btn sm-btn--accent" type="submit" name="do" value="accept"><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Accept', 'sm-marketplace' ); ?></button>
					<?php endif; ?>
					<button class="sm-btn sm-btn--ghost" type="submit" name="do" value="refuse"><?php echo esc_html( 'accepted' === $r['status'] ? __( 'Cancel the visit', 'sm-marketplace' ) : __( 'Refuse', 'sm-marketplace' ) ); ?></button>
				</form>
				<?php if ( 'accepted' !== $r['status'] ) : ?>
					<details class="sm-visit__propose">
						<summary><?php sm_icon( 'calendar' ); ?><?php esc_html_e( 'Propose another date', 'sm-marketplace' ); ?></summary>
						<?php sm_theme_form_open( 'visit_reply', array( 'class' => 'sm-form' ) ); ?>
							<input type="hidden" name="visit_id" value="<?php echo esc_attr( (string) $r['id'] ); ?>">
							<input type="hidden" name="do" value="propose">
							<div class="sm-field sm-field--row">
								<div><label for="sm-vp-d<?php echo esc_attr( (string) $r['id'] ); ?>"><?php esc_html_e( 'Date', 'sm-marketplace' ); ?></label><input id="sm-vp-d<?php echo esc_attr( (string) $r['id'] ); ?>" type="date" name="visit_date" required min="<?php echo esc_attr( $r['min_date'] ); ?>" max="<?php echo esc_attr( $r['max_date'] ); ?>"></div>
								<div><label for="sm-vp-t<?php echo esc_attr( (string) $r['id'] ); ?>"><?php esc_html_e( 'Time of the visit', 'sm-marketplace' ); ?></label><input id="sm-vp-t<?php echo esc_attr( (string) $r['id'] ); ?>" type="time" name="visit_time" required value="10:00"></div>
							</div>
							<div class="sm-field"><label for="sm-vp-n<?php echo esc_attr( (string) $r['id'] ); ?>"><?php esc_html_e( 'Note for the buyer', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label><input id="sm-vp-n<?php echo esc_attr( (string) $r['id'] ); ?>" name="reply" dir="auto" maxlength="500"></div>
							<button class="sm-btn sm-btn--primary-outline" type="submit"><?php esc_html_e( 'Send this date', 'sm-marketplace' ); ?></button>
						</form>
					</details>
				<?php endif; ?>
			<?php elseif ( 'sent' === $box && $open ) : ?>
				<?php sm_theme_form_open( 'visit_answer', array( 'class' => 'sm-visit__actions' ) ); ?>
					<input type="hidden" name="visit_id" value="<?php echo esc_attr( (string) $r['id'] ); ?>">
					<?php if ( 'proposed' === $r['status'] ) : ?>
						<button class="sm-btn sm-btn--accent" type="submit" name="do" value="accept"><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Confirm this date', 'sm-marketplace' ); ?></button>
					<?php endif; ?>
					<?php if ( 'accepted' === $r['status'] && $url ) : ?>
						<a class="sm-btn sm-btn--primary-outline" href="<?php echo esc_url( $url ); ?>#sm-place"><?php sm_icon( 'map-pin' ); ?><?php esc_html_e( 'See the map', 'sm-marketplace' ); ?></a>
					<?php endif; ?>
					<button class="sm-btn sm-btn--ghost" type="submit" name="do" value="cancel"><?php echo esc_html( 'accepted' === $r['status'] ? __( 'Cancel the visit', 'sm-marketplace' ) : __( 'Cancel my request', 'sm-marketplace' ) ); ?></button>
				</form>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>
