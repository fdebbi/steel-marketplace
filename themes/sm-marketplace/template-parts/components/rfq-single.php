<?php
/**
 * One request for quotation. What is shown depends on the viewer (computed by
 * sm-rfq): the buyer gets his actions and the comparison of the offers; a
 * supplier gets the offer form or his own offer; others get the reason they
 * cannot answer.
 *
 * @var array $args { item: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$r       = $args['item'];
$v       = (array) ( $r['viewer'] ?? array() );
$wilayas = sm_theme_wilayas();
$labels  = sm_rfq_status_labels();
$desc    = sm_field( $r['description'] );
$notes   = sm_field( $r['notes'] );
$rows    = array(
	__( 'Market', 'sm-marketplace' ) => esc_html( sm_t( $r['market_label'] ) ) . ( $r['category_label'] ? ' · ' . esc_html( sm_t( $r['category_label'] ) ) : '' ),
);
if ( $r['designation'] && ! $r['lines'] ) {
	$rows[ __( 'Designation', 'sm-marketplace' ) ] = sm_tech( $r['designation'] );
}
if ( $r['dimensions'] ) {
	$rows[ __( 'Dimensions', 'sm-marketplace' ) ] = '<bdi>' . esc_html( $r['dimensions'] ) . '</bdi>';
}
if ( $r['quantity'] ) {
	$rows[ __( 'Quantity', 'sm-marketplace' ) ] = sm_tech( sm_number( $r['quantity'], fmod( (float) $r['quantity'], 1 ) ? 1 : 0 ) . ' ' . sm_rfq_unit_label( $r['unit'], $r['quantity'] ) );
}
if ( $r['weight_kg'] ) {
	$rows[ 'theoretical' === $r['weight_source'] ? __( 'Theoretical weight', 'sm-marketplace' ) : __( 'Weight', 'sm-marketplace' ) ] = sm_kg( $r['weight_kg'] );
}
$rows[ __( 'Delivery', 'sm-marketplace' ) ] = esc_html( sm_t( $wilayas[ $r['wilaya'] ] ?? '' ) ) . ( $r['place'] ? ' — <bdi>' . esc_html( $r['place'] ) . '</bdi>' : '' );
if ( $r['needed_by'] ) {
	$rows[ __( 'Needed by', 'sm-marketplace' ) ] = esc_html( sm_date( $r['needed_by'] ) );
}
if ( $r['deadline'] ) {
	$rows[ __( 'Offers accepted until', 'sm-marketplace' ) ] = esc_html( sm_date( $r['deadline'] ) );
}
?>
<section class="sm-pagehead sm-pagehead--rfq">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Requests for quotation', 'sm-marketplace' ), sm_page_url( 'rfq' ) ), array( sm_t( $r['title'] ), '' ) ) ); ?>
		<p class="sm-card__meta">
			<span class="smq-status smq-status--<?php echo esc_attr( $r['status'] ); ?>"><?php echo esc_html( $labels[ $r['status'] ] ?? $r['status'] ); ?></span>
			<span class="sm-chip sm-chip--quiet"><?php echo esc_html( sprintf( /* translators: %d: request number. */ __( 'Request no. %d', 'sm-marketplace' ), $r['id'] ) ); ?></span>
			<span class="sm-chip sm-chip--quiet"><?php echo esc_html( sm_ago( $r['date'] ) ); ?></span>
		</p>
		<h1 class="sm-pagehead__title"><?php sm_e( $r['title'] ); ?></h1>
		<p class="sm-pagehead__intro smq-buyer">
			<?php sm_icon( 'company' ); ?>
			<?php if ( $r['company'] ) : ?><a href="<?php echo esc_url( sm_page_url( 'company', array( 'c' => $r['company'] ) ) ); ?>"><?php echo esc_html( sm_t( $r['company_name'] ) ); ?></a><?php endif; ?>
			<?php if ( $r['verification'] >= 2 ) : ?><?php sm_icon( 'verified', 'sm-verified', sm_verification_label( $r['verification'], $r['class'] ?? '' ) ); ?><?php endif; ?>
			<span>· <?php echo esc_html( sm_t( $wilayas[ $r['wilaya'] ] ?? '' ) ); ?></span>
			<?php if ( $r['is_open'] && null !== $r['days_left'] ) : ?>
				<span class="smq-left">· <?php echo esc_html( 0 >= $r['days_left'] ? __( 'Last day for offers', 'sm-marketplace' ) : sprintf( /* translators: %d: days. */ _n( '%d day left for offers', '%d days left for offers', $r['days_left'], 'sm-marketplace' ), $r['days_left'] ) ); ?></span>
			<?php endif; ?>
		</p>
	</div>
</section>

<div class="sm-container sm-section smq-page">
	<?php sm_theme_notices(); ?>
	<?php if ( 'rejected' === $r['status'] && $r['reject_reason'] && ! empty( $v['is_buyer'] ) ) : ?>
		<div class="sm-notice sm-notice--warn"><?php sm_icon( 'alert' ); ?><p><strong><?php esc_html_e( 'Changes requested by the platform team:', 'sm-marketplace' ); ?></strong> <?php echo esc_html( $r['reject_reason'] ); ?></p></div>
	<?php endif; ?>

	<div class="smq-layout">
		<div class="smq-main">
			<section class="sm-panel">
				<h2 class="sm-panel__title"><?php esc_html_e( 'What is needed', 'sm-marketplace' ); ?></h2>
				<dl class="sm-spec">
					<?php foreach ( $rows as $label => $value ) : ?>
						<div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo wp_kses( $value, array( 'bdi' => array( 'dir' => true, 'class' => true ) ) ); ?></dd></div>
					<?php endforeach; ?>
				</dl>
				<?php if ( $r['lines'] ) : ?>
					<div class="sm-table-wrap" tabindex="0" role="region" aria-label="<?php esc_attr_e( 'Profiles requested', 'sm-marketplace' ); ?>">
						<table class="sm-table sm-table--stack">
							<thead><tr><th scope="col"><?php esc_html_e( 'Profile', 'sm-marketplace' ); ?></th><th scope="col" class="num"><?php esc_html_e( 'Quantity', 'sm-marketplace' ); ?></th><th scope="col" class="num"><?php esc_html_e( 'Length (m)', 'sm-marketplace' ); ?></th><th scope="col" class="num"><?php esc_html_e( 'Unit weight (kg/m)', 'sm-marketplace' ); ?></th><th scope="col" class="num"><?php esc_html_e( 'Total weight (kg)', 'sm-marketplace' ); ?></th></tr></thead>
							<tbody>
								<?php foreach ( $r['lines'] as $l ) : ?>
									<tr>
										<th scope="row" data-label="<?php esc_attr_e( 'Profile', 'sm-marketplace' ); ?>"><?php echo sm_tech( $l['designation'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></th>
										<td class="num" data-label="<?php esc_attr_e( 'Quantity', 'sm-marketplace' ); ?>"><?php echo esc_html( $l['qty'] ); ?></td>
										<td class="num" data-label="<?php esc_attr_e( 'Length (m)', 'sm-marketplace' ); ?>"><?php echo esc_html( sm_number( $l['length'], fmod( $l['length'] * 10, 1 ) ? 2 : ( fmod( $l['length'], 1 ) ? 1 : 0 ) ) ); ?></td>
										<td class="num" data-label="<?php esc_attr_e( 'Unit weight (kg/m)', 'sm-marketplace' ); ?>"><?php echo esc_html( sm_number( $l['unit_weight'], 2 ) ); ?></td>
										<td class="num" data-label="<?php esc_attr_e( 'Total weight (kg)', 'sm-marketplace' ); ?>"><?php echo esc_html( sm_number( $l['total'], 1 ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<p class="sm-muted"><?php esc_html_e( 'Theoretical weights from the European section tables.', 'sm-marketplace' ); ?></p>
				<?php endif; ?>
			</section>

			<section class="sm-panel">
				<h2 class="sm-panel__title"><?php esc_html_e( 'Technical description', 'sm-marketplace' ); ?></h2>
				<p class="sm-prewrap"<?php echo $desc['fallback'] ? ' lang="' . esc_attr( $desc['lang'] ) . '" dir="auto"' : ''; ?>><?php echo esc_html( $desc['text'] ); ?></p>
				<?php if ( '' !== trim( (string) $notes['text'] ) ) : ?>
					<h3 class="smq-sub"><?php esc_html_e( 'Additional notes', 'sm-marketplace' ); ?></h3>
					<p class="sm-prewrap" dir="auto"><?php echo esc_html( $notes['text'] ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $r['files'] ) ) : ?>
					<h3 class="smq-sub"><?php esc_html_e( 'Drawings and documents', 'sm-marketplace' ); ?></h3>
					<ul class="smp-docs" role="list">
						<?php foreach ( $r['files'] as $doc ) : ?>
							<li><a href="<?php echo esc_url( $doc['url'] ); ?>" target="_blank" rel="noopener" download><?php sm_icon( 'file' ); ?><span><?php echo esc_html( $doc['name'] ); ?></span><small><?php echo esc_html( strtoupper( wp_basename( (string) $doc['type'] ) ) ); ?><?php echo $doc['size'] ? ' · ' . esc_html( size_format( $doc['size'], 1 ) ) : ''; ?></small></a></li>
						<?php endforeach; ?>
					</ul>
				<?php elseif ( ! empty( $r['files_count'] ) ) : ?>
					<p class="sm-callout"><?php sm_icon( 'lock' ); ?><span><?php echo esc_html( sprintf( /* translators: %d: number of files. */ _n( '%d drawing or document: sign in with a business account to download it.', '%d drawings or documents: sign in with a business account to download them.', (int) $r['files_count'], 'sm-marketplace' ), (int) $r['files_count'] ) ); ?></span></p>
				<?php endif; ?>
			</section>
		</div>

		<aside class="smq-side">
			<?php if ( ! empty( $v['is_buyer'] ) ) : ?>
				<section class="sm-panel smq-owner">
					<h2 class="sm-panel__title"><?php esc_html_e( 'Your request', 'sm-marketplace' ); ?></h2>
					<p class="smq-big"><strong><?php echo esc_html( (int) $r['offers_count'] ); ?></strong> <?php echo esc_html( _n( 'offer received', 'offers received', (int) $r['offers_count'], 'sm-marketplace' ) ); ?></p>
					<div class="smq-actions">
						<?php if ( ! empty( $v['can_edit'] ) ) : ?>
							<a class="sm-btn sm-btn--primary-outline sm-btn--sm" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'rfq', 'edit' => $r['id'] ) ) ); ?>"><?php sm_icon( 'edit' ); ?><?php esc_html_e( 'Edit', 'sm-marketplace' ); ?></a>
						<?php endif; ?>
						<?php if ( ! empty( $v['can_close'] ) ) : ?>
							<?php sm_theme_form_open( 'rfq_action', array( 'class' => 'smp-act' ) ); ?>
								<input type="hidden" name="rfq_id" value="<?php echo esc_attr( $r['id'] ); ?>"><input type="hidden" name="do" value="close">
								<button class="sm-btn sm-btn--ghost sm-btn--sm" type="submit" data-sm-confirm="<?php esc_attr_e( 'Close this request? Suppliers can no longer answer.', 'sm-marketplace' ); ?>"><?php esc_html_e( 'Close the request', 'sm-marketplace' ); ?></button>
							</form>
						<?php endif; ?>
						<?php if ( ! empty( $v['can_delete'] ) ) : ?>
							<?php sm_theme_form_open( 'rfq_action', array( 'class' => 'smp-act' ) ); ?>
								<input type="hidden" name="rfq_id" value="<?php echo esc_attr( $r['id'] ); ?>"><input type="hidden" name="do" value="delete">
								<button class="sm-btn sm-btn--ghost sm-btn--sm" type="submit" data-sm-confirm="<?php esc_attr_e( 'Delete this request for good?', 'sm-marketplace' ); ?>"><?php sm_icon( 'trash' ); ?><?php esc_html_e( 'Delete', 'sm-marketplace' ); ?></button>
							</form>
						<?php endif; ?>
					</div>
					<p class="sm-help"><?php esc_html_e( 'Only you see the offers. Each supplier only sees his own.', 'sm-marketplace' ); ?></p>
				</section>
			<?php elseif ( ! empty( $r['my_offer'] ) && empty( $v['can_offer'] ) ) : ?>
				<?php sm_component( 'rfq-my-offer', array( 'offer' => $r['my_offer'], 'item' => $r ) ); ?>
			<?php elseif ( ! empty( $v['can_offer'] ) ) : ?>
				<?php if ( ! empty( $r['my_offer'] ) && 'withdrawn' !== $r['my_offer']['status'] ) : ?>
					<?php sm_component( 'rfq-my-offer', array( 'offer' => $r['my_offer'], 'item' => $r, 'compact' => true ) ); ?>
				<?php endif; ?>
				<?php sm_component( 'rfq-offer-form', array( 'item' => $r ) ); ?>
			<?php else : ?>
				<section class="sm-panel smq-blocked">
					<h2 class="sm-panel__title"><?php esc_html_e( 'Answer this request', 'sm-marketplace' ); ?></h2>
					<p><?php echo esc_html( $v['offer_block'] ?? '' ); ?></p>
					<?php if ( empty( $v['signed_in'] ) ) : ?>
						<div class="smq-actions">
							<a class="sm-btn sm-btn--primary" href="<?php echo esc_url( add_query_arg( 'redirect_to', rawurlencode( sm_page_url( 'rfq', array( 'id' => $r['id'] ) ) ), sm_page_url( 'login' ) ) ); ?>"><?php sm_icon( 'user' ); ?><?php esc_html_e( 'Sign in', 'sm-marketplace' ); ?></a>
							<a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( sm_page_url( 'register' ) ); ?>"><?php esc_html_e( 'Create an account', 'sm-marketplace' ); ?></a>
						</div>
					<?php endif; ?>
				</section>
			<?php endif; ?>
		</aside>
	</div>

	<?php if ( ! empty( $v['is_buyer'] ) ) : ?>
		<?php sm_component( 'rfq-compare', array( 'item' => $r ) ); ?>
	<?php endif; ?>
</div>
