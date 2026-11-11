<?php
/**
 * Service page form (sm-services): services offered, texts, capabilities,
 * equipment, area served, lead time, indicative price, photos.
 *
 * @var array $args { edit_id?: int, kind?: string }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$set     = (array) sm_theme_data( 'services_settings' );
$edit_id = (int) ( $args['edit_id'] ?? 0 );
$edit    = $edit_id ? sm_theme_data( 'service_form', array( 'id' => $edit_id ) ) : null;
if ( $edit_id && ! $edit ) {
	echo '<div class="sm-notice sm-notice--warn">' . sm_get_icon( 'lock' ) . '<p>' . esc_html__( 'This service cannot be edited from your account.', 'sm-marketplace' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	return;
}
$allowed = array_keys( array_filter( (array) ( $set['kinds'] ?? array() ), static fn( $k ) => ! empty( $k['allowed'] ) ) );
$kind    = $edit ? $edit['values']['kind'] : ( in_array( $args['kind'] ?? '', $allowed, true ) ? $args['kind'] : ( $allowed[0] ?? '' ) );
if ( ! $kind || ! isset( $set['kinds'][ $kind ] ) ) {
	$reasons = array_filter( wp_list_pluck( (array) ( $set['kinds'] ?? array() ), 'reason' ) );
	echo '<div class="sm-notice sm-notice--warn">' . sm_get_icon( 'shield' ) . '<p>' . esc_html( $reasons ? reset( $reasons ) : __( 'Your account cannot publish services.', 'sm-marketplace' ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	return;
}
$def   = $set['kinds'][ $kind ];
$state = $edit ? $edit['values'] : array( 'coverage' => 'all', 'price_mode' => 'quote' );
$old   = sm_theme_old_all();
if ( $old ) {
	$state = array_merge( $state, $old );
}
$v     = static fn( $k, $d = '' ) => isset( $state[ $k ] ) && ! is_array( $state[ $k ] ) ? (string) $state[ $k ] : $d;
$cats  = (array) ( $state['categories'] ?? array() );
$cat   = (string) ( $state['category'] ?? ( $cats[0] ?? '' ) );
$other = (string) ( $set['other'] ?? 'other' );
$wsel  = array_map( 'strval', (array) ( $state['wilayas'] ?? array() ) );
$units = array();
foreach ( (array) $def['units'] as $u ) {
	$units[ $u ] = sm_service_unit_label( $u );
}
?>
<?php sm_theme_form_open( 'service_save', array( 'class' => 'sm-form sms-form', 'enctype' => 'multipart/form-data' ) ); ?>
	<input type="hidden" name="service_id" value="<?php echo esc_attr( $v( 'service_id' ) ); ?>">
	<input type="hidden" name="kind" value="<?php echo esc_attr( $kind ); ?>">
	<?php if ( $edit && 'rejected' === $edit['status'] && $edit['reject_reason'] ) : ?>
		<div class="sm-notice sm-notice--warn"><?php sm_icon( 'alert' ); ?><p><strong><?php esc_html_e( 'Changes requested by the platform team:', 'sm-marketplace' ); ?></strong> <?php echo esc_html( $edit['reject_reason'] ); ?></p></div>
	<?php elseif ( $edit && ! empty( $edit['values']['revision'] ) ) : ?>
		<div class="sm-notice sm-notice--info"><?php sm_icon( 'info' ); ?><p><?php esc_html_e( 'Your previous changes are waiting for the check of the platform team (shown below). The current version stays online meanwhile.', 'sm-marketplace' ); ?></p></div>
	<?php endif; ?>

	<section class="sm-panel smp-step">
		<h2 class="smw-step__title"><span class="smw-step__n">1</span><?php esc_html_e( 'Service', 'sm-marketplace' ); ?></h2>
		<p class="sm-help"><?php esc_html_e( 'One service per page, with its own price. Offer several services? Create one page for each.', 'sm-marketplace' ); ?></p>
		<div class="sm-field">
			<label for="sms-cat"><?php esc_html_e( 'Choose the service', 'sm-marketplace' ); ?></label>
			<select id="sms-cat" name="category" required data-sms-category<?php sm_theme_aria( 'categories' ); ?>>
				<option value=""><?php esc_html_e( 'Choose…', 'sm-marketplace' ); ?></option>
				<?php foreach ( $def['categories'] as $code => $label ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $cat, $code ); ?>><?php echo esc_html( sm_t( $label ) ); ?></option>
				<?php endforeach; ?>
				<option value="<?php echo esc_attr( $other ); ?>" <?php selected( $cat, $other ); ?>><?php esc_html_e( 'Other (write the name of your service)', 'sm-marketplace' ); ?></option>
			</select>
			<?php sm_theme_error( 'categories' ); ?>
		</div>
		<div class="sm-field" data-sms-custom<?php echo $other === $cat ? '' : ' hidden'; ?>>
			<label for="sms-custom"><?php esc_html_e( 'Name of your service', 'sm-marketplace' ); ?></label>
			<input id="sms-custom" name="custom_service" maxlength="80" dir="auto" value="<?php echo esc_attr( $v( 'custom_service' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. Bending of steel sheets', 'sm-marketplace' ); ?>"<?php sm_theme_aria( 'custom_service' ); ?>>
			<p class="sm-help"><?php esc_html_e( 'Your page is published under "Other services"; the platform team may add this name to the list.', 'sm-marketplace' ); ?></p>
			<?php sm_theme_error( 'custom_service' ); ?>
		</div>
	</section>

	<section class="sm-panel smp-step">
		<h2 class="smw-step__title"><span class="smw-step__n">2</span><?php esc_html_e( 'Presentation', 'sm-marketplace' ); ?></h2>
		<div class="sm-field">
			<label for="sms-desc"><?php esc_html_e( 'Description of the service', 'sm-marketplace' ); ?></label>
			<textarea id="sms-desc" name="description" rows="5" dir="auto" maxlength="4000"<?php sm_theme_aria( 'description' ); ?>><?php echo esc_textarea( $v( 'description' ) ); ?></textarea>
			<p class="sm-help"><?php esc_html_e( '15 characters at least. Say what you do, the thicknesses or sizes, your lead times and your equipment: clients choose better.', 'sm-marketplace' ); ?></p>
			<?php sm_theme_error( 'description' ); ?>
		</div>
		<div class="sm-field-grid">
			<div class="sm-field">
				<label for="sms-caps"><?php esc_html_e( 'Technical capabilities (one per line)', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
				<textarea id="sms-caps" name="capabilities" rows="5" dir="auto" maxlength="2000" placeholder="<?php echo esc_attr( __( "Built-up beams up to 1 200 mm\nPlates 4 to 40 mm\nSpans up to 30 m", 'sm-marketplace' ) ); ?>"><?php echo esc_textarea( $v( 'capabilities' ) ); ?></textarea>
				<?php sm_theme_error( 'capabilities' ); ?>
			</div>
			<div class="sm-field">
				<label for="sms-equip"><?php esc_html_e( 'Equipment (one per line)', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
				<textarea id="sms-equip" name="equipment" rows="5" dir="auto" maxlength="2000" placeholder="<?php echo esc_attr( __( "Band saw\nDrilling line\nMobile crane 30 t", 'sm-marketplace' ) ); ?>"><?php echo esc_textarea( $v( 'equipment' ) ); ?></textarea>
				<?php sm_theme_error( 'equipment' ); ?>
			</div>
		</div>
	</section>

	<section class="sm-panel smp-step">
		<h2 class="smw-step__title"><span class="smw-step__n">3</span><?php esc_html_e( 'Area served, lead time and price', 'sm-marketplace' ); ?></h2>
		<?php $cov = 'list' === $v( 'coverage', 'all' ) ? 'list' : 'all'; ?>
		<fieldset class="sm-field" data-sms-coverage>
			<legend class="sm-field__legend"><?php esc_html_e( 'Area served', 'sm-marketplace' ); ?></legend>
			<div class="sm-segment">
				<label><input type="radio" name="coverage" value="all" <?php checked( $cov, 'all' ); ?>><span><?php esc_html_e( 'All of Algeria', 'sm-marketplace' ); ?></span></label>
				<label><input type="radio" name="coverage" value="list" <?php checked( $cov, 'list' ); ?>><span><?php esc_html_e( 'Some wilayas', 'sm-marketplace' ); ?></span></label>
			</div>
			<div class="sms-wilayas">
				<p class="sms-wilayas__title"><?php esc_html_e( 'Choose the wilayas', 'sm-marketplace' ); ?></p>
				<div class="sms-wilayas__grid">
					<?php foreach ( sm_theme_wilayas() as $code => $name ) : ?>
						<label class="sm-check"><input type="checkbox" name="wilayas[]" value="<?php echo esc_attr( $code ); ?>" <?php checked( in_array( (string) $code, $wsel, true ) ); ?>><span><?php echo esc_html( $code . ' ' . sm_t( $name ) ); ?></span></label>
					<?php endforeach; ?>
				</div>
			</div>
			<?php sm_theme_error( 'wilayas' ); ?>
		</fieldset>
		<?php $pm = 'from' === $v( 'price_mode', 'quote' ) ? 'from' : 'quote'; ?>
		<div class="sm-field-grid">
			<div class="sm-field">
				<label for="sms-lead"><?php esc_html_e( 'Usual lead time (days)', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
				<input id="sms-lead" name="lead_days" inputmode="numeric" dir="ltr" value="<?php echo esc_attr( $v( 'lead_days' ) ); ?>"<?php sm_theme_aria( 'lead_days' ); ?>>
				<?php sm_theme_error( 'lead_days' ); ?>
			</div>
			<fieldset class="sm-field">
				<legend class="sm-field__legend"><?php esc_html_e( 'Price shown', 'sm-marketplace' ); ?></legend>
				<div class="sm-segment">
					<label><input type="radio" name="price_mode" value="quote" <?php checked( $pm, 'quote' ); ?>><span><?php esc_html_e( 'On quotation', 'sm-marketplace' ); ?></span></label>
					<label><input type="radio" name="price_mode" value="from" <?php checked( $pm, 'from' ); ?>><span><?php esc_html_e( 'Starting price', 'sm-marketplace' ); ?></span></label>
				</div>
			</fieldset>
		</div>
		<div class="sm-field sm-field--row sms-pricefrom">
			<div>
				<label for="sms-price"><?php esc_html_e( 'From', 'sm-marketplace' ); ?> <small>(<?php echo esc_html( sm_currency_label() ); ?>)</small></label>
				<input id="sms-price" name="price_from" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $v( 'price_from' ) ); ?>"<?php sm_theme_aria( 'price_from' ); ?>>
				<?php sm_theme_error( 'price_from' ); ?>
			</div>
			<div>
				<label for="sms-unit"><?php esc_html_e( 'Per', 'sm-marketplace' ); ?></label>
				<select id="sms-unit" name="price_unit"><?php foreach ( $units as $u => $l ) : ?><option value="<?php echo esc_attr( $u ); ?>" <?php selected( $v( 'price_unit' ), $u ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select>
				<?php sm_theme_error( 'price_unit' ); ?>
			</div>
		</div>
		<p class="sm-help"><?php esc_html_e( 'The price is indicative; each client gets your quote.', 'sm-marketplace' ); ?></p>
	</section>

	<section class="sm-panel smp-step">
		<h2 class="smw-step__title"><span class="smw-step__n">4</span><?php esc_html_e( 'Image of the service', 'sm-marketplace' ); ?></h2>
		<?php $kept = array_filter( array_map( 'absint', (array) ( $state['keep_photos'] ?? array() ) ) ); ?>
		<?php sm_saved_files( array( 'ids' => $kept, 'field' => 'photos[]', 'keep' => 'keep_photos[]' ) ); ?>
		<label class="sm-upload" data-sm-upload>
			<input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp">
			<?php sm_icon( 'camera', 'sm-upload__icon' ); ?>
			<strong><?php echo esc_html( $kept ? __( 'Replace the image', 'sm-marketplace' ) : __( 'Add an image', 'sm-marketplace' ) ); ?></strong>
			<small><?php echo esc_html( sprintf( /* translators: %d: MB. */ __( 'One image (JPG, PNG, WebP, %d MB maximum) that shows this service. Put your other photos in "Completed projects".', 'sm-marketplace' ), (int) ( $set['photo_mb'] ?? 8 ) ) ); ?></small>
		</label>
		<ul class="sm-upload__previews" data-sm-previews></ul>
	</section>

	<div class="smp-actions">
		<a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'services' ) ) ); ?>"><?php esc_html_e( 'Cancel', 'sm-marketplace' ); ?></a>
		<button class="sm-btn sm-btn--accent" type="submit"><?php sm_icon( 'check' ); ?><?php echo esc_html( $edit ? __( 'Save the service', 'sm-marketplace' ) : __( 'Publish the service', 'sm-marketplace' ) ); ?></button>
	</div>
</form>
