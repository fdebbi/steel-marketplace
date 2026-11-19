<?php
/**
 * Warehouse: one price per kg (or a change in %) for a whole type of new steel
 * (0.48.0, sm-stock). 1. choose the type; 2. check the products concerned
 * (old and new price, untick the exceptions); 3. apply.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$sel_in = array();
foreach ( array( 'family', 'grade', 'from', 'to', 'mode', 'value' ) as $k ) {
	$sel_in[ $k ] = (string) sm_theme_query_arg( $k, '' );
}
$d = sm_theme_data( 'stock_prices', $sel_in );
if ( ! is_array( $d ) ) {
	return;
}
$back = sm_page_url( 'account', array( 'view' => 'stock' ) );
if ( ! $d['tools'] ) {
	echo '<div class="sm-notice sm-notice--warn">' . sm_get_icon( 'lock' ) . '<p>' . esc_html__( 'The price for a whole type comes with the paid plans.', 'sm-marketplace' ) . ' <a href="' . esc_url( sm_page_url( 'account', array( 'view' => 'plan' ) ) ) . '">' . esc_html__( 'See the plans', 'sm-marketplace' ) . '</a></p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	return;
}
$sel  = $d['sel'];
$fams = $d['families'];
$here = sm_page_url( 'account', array( 'view' => 'stock-prices' ) );
?>
<p><a class="sm-link" href="<?php echo esc_url( $back ); ?>"><?php sm_icon( 'chevron-start' ); ?><?php esc_html_e( 'Back to the warehouse', 'sm-marketplace' ); ?></a></p>
<section class="sm-panel smst-prices">
	<h2 class="sm-panel__title"><?php esc_html_e( '1. Which products?', 'sm-marketplace' ); ?></h2>
	<?php if ( ! $fams ) : ?>
		<p class="sm-muted"><?php esc_html_e( 'Your warehouse has no new steel yet.', 'sm-marketplace' ); ?></p>
	<?php else : ?>
		<form class="sm-form smst-prices__form" method="get" action="<?php echo esc_url( $here ); ?>">
			<div class="sm-field">
				<label for="smsp-family"><?php esc_html_e( 'Profile', 'sm-marketplace' ); ?></label>
				<select id="smsp-family" name="family" required>
					<option value=""><?php esc_html_e( 'Choose…', 'sm-marketplace' ); ?></option>
					<?php foreach ( $fams as $code => $sizes ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( $sel['family'], $code ); ?>><?php echo esc_html( $code ); ?></option><?php endforeach; ?>
				</select>
			</div>
			<?php if ( $sel['family'] && isset( $fams[ $sel['family'] ] ) ) : ?>
				<div class="sm-field"><label for="smsp-from"><?php esc_html_e( 'From the size', 'sm-marketplace' ); ?></label>
					<select id="smsp-from" name="from"><option value=""><?php esc_html_e( 'The smallest', 'sm-marketplace' ); ?></option><?php foreach ( $fams[ $sel['family'] ] as $sid => $dsg ) : ?><option value="<?php echo esc_attr( $sid ); ?>" <?php selected( $sel['from'], $sid ); ?>><?php echo esc_html( $dsg ); ?></option><?php endforeach; ?></select></div>
				<div class="sm-field"><label for="smsp-to"><?php esc_html_e( 'To the size', 'sm-marketplace' ); ?></label>
					<select id="smsp-to" name="to"><option value=""><?php esc_html_e( 'The largest', 'sm-marketplace' ); ?></option><?php foreach ( $fams[ $sel['family'] ] as $sid => $dsg ) : ?><option value="<?php echo esc_attr( $sid ); ?>" <?php selected( $sel['to'], $sid ); ?>><?php echo esc_html( $dsg ); ?></option><?php endforeach; ?></select></div>
			<?php endif; ?>
			<div class="sm-field"><label for="smsp-grade"><?php esc_html_e( 'Grade', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label><input id="smsp-grade" name="grade" value="<?php echo esc_attr( $sel['grade'] ); ?>" placeholder="S275JR" dir="ltr"></div>
			<fieldset class="smst-mode">
				<legend><?php esc_html_e( 'New price', 'sm-marketplace' ); ?></legend>
				<label><input type="radio" name="mode" value="price" <?php checked( 'price', $sel['mode'] ); ?>> <?php esc_html_e( 'Price per kg', 'sm-marketplace' ); ?></label>
				<label><input type="radio" name="mode" value="percent" <?php checked( 'percent', $sel['mode'] ); ?>> <?php esc_html_e( 'Change in %', 'sm-marketplace' ); ?></label>
				<input name="value" inputmode="decimal" value="<?php echo esc_attr( $sel_in['value'] ); ?>" placeholder="135 · +5 · -3" aria-label="<?php esc_attr_e( 'Value', 'sm-marketplace' ); ?>" required>
			</fieldset>
			<button class="sm-btn sm-btn--ghost" type="submit"><?php esc_html_e( 'See the products concerned', 'sm-marketplace' ); ?></button>
		</form>
	<?php endif; ?>
</section>

<?php if ( '' !== $sel_in['family'] && '' !== $sel_in['value'] ) : ?>
	<section class="sm-panel smst-prices">
		<h2 class="sm-panel__title"><?php esc_html_e( '2. Check and apply', 'sm-marketplace' ); ?></h2>
		<?php if ( ! $d['rows'] ) : ?>
			<p class="sm-muted"><?php esc_html_e( 'No product priced per kg matches this choice.', 'sm-marketplace' ); ?></p>
		<?php else : ?>
			<?php sm_theme_form_open( 'stock_prices', array( 'class' => 'sm-form' ) ); ?>
				<?php foreach ( array( 'family', 'grade', 'from', 'to', 'mode', 'value' ) as $k ) : ?><input type="hidden" name="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $sel_in[ $k ] ); ?>"><?php endforeach; ?>
				<div class="sm-table-wrap" tabindex="0">
					<table class="sm-table sm-table--stack smst-table">
						<thead><tr><th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Change', 'sm-marketplace' ); ?></span></th><th scope="col"><?php esc_html_e( 'Product', 'sm-marketplace' ); ?></th><th scope="col"><?php esc_html_e( 'On the market', 'sm-marketplace' ); ?></th><th scope="col" class="num"><?php esc_html_e( 'Now', 'sm-marketplace' ); ?></th><th scope="col" class="num"><?php esc_html_e( 'New', 'sm-marketplace' ); ?></th></tr></thead>
						<tbody>
							<?php foreach ( $d['rows'] as $r ) : ?>
								<tr>
									<td><input type="checkbox" name="ids[]" value="<?php echo esc_attr( $r['id'] ); ?>" checked aria-label="<?php echo esc_attr( $r['title'] ); ?>"></td>
									<td data-label="<?php esc_attr_e( 'Product', 'sm-marketplace' ); ?>"><b><?php echo sm_tech( trim( $r['designation'] . ( $r['grade'] ? ' · ' . $r['grade'] : '' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></b><small><?php echo esc_html( sm_number( $r['kg'], 1 ) . ' kg' ); ?></small></td>
									<td data-label="<?php esc_attr_e( 'On the market', 'sm-marketplace' ); ?>"><?php echo esc_html( in_array( $r['status'], array( 'published', 'pending', 'paused' ), true ) ? __( 'Published', 'sm-marketplace' ) : __( 'In stock only', 'sm-marketplace' ) ); ?></td>
									<td class="num" data-label="<?php esc_attr_e( 'Now', 'sm-marketplace' ); ?>"><?php echo $r['old'] > 0 ? sm_per_unit( $r['old'], 'kg', false, 2 ) : '—'; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
									<td class="num" data-label="<?php esc_attr_e( 'New', 'sm-marketplace' ); ?>"><b><?php echo sm_per_unit( $r['new'], 'kg', false, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></b></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<p class="smst-hint"><?php esc_html_e( 'Untick the exceptions. Orders and offers already in progress keep their price; the platform team is told once.', 'sm-marketplace' ); ?></p>
				<button class="sm-btn sm-btn--accent" type="submit" data-sm-confirm="<?php esc_attr_e( 'Change the price of the ticked products? The new prices are online at once.', 'sm-marketplace' ); ?>"><?php esc_html_e( 'Apply the new price', 'sm-marketplace' ); ?></button>
			</form>
		<?php endif; ?>
	</section>
<?php endif; ?>
