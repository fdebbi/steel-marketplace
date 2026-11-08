<?php
/**
 * Request for quotation form (sm-rfq): what, quantity (profile rows weighed by
 * the server or free description), description, delivery, drawings.
 *
 * @var array $args { edit_id?: int }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$set     = (array) sm_theme_data( 'rfq_settings' );
$edit_id = (int) ( $args['edit_id'] ?? 0 );
$edit    = $edit_id ? sm_theme_data( 'rfq_form', array( 'id' => $edit_id ) ) : null;
if ( $edit_id && ! $edit ) {
	echo '<div class="sm-notice sm-notice--warn">' . sm_get_icon( 'lock' ) . '<p>' . esc_html__( 'This request cannot be edited from your account.', 'sm-marketplace' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	return;
}
if ( empty( $set['can_create'] ) ) {
	echo '<div class="sm-notice sm-notice--warn">' . sm_get_icon( 'shield' ) . '<p>' . esc_html( $set['create_reason'] ?? '' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	return;
}
if ( $edit && $edit['offers_count'] > 0 ) {
	echo '<div class="sm-notice sm-notice--warn">' . sm_get_icon( 'lock' ) . '<p>' . esc_html__( 'This request already has offers: it can no longer be edited. Close it and publish a new one if your need changed.', 'sm-marketplace' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	return;
}
$state = $edit ? $edit['values'] : array( 'deadline' => $set['default_deadline'] ?? '', 'wilaya' => '' );
$old   = sm_theme_old_all();
if ( $old ) {
	$state = array_merge( $state, $old );
}
$v       = static fn( $k, $d = '' ) => isset( $state[ $k ] ) && ! is_array( $state[ $k ] ) ? (string) $state[ $k ] : $d;
$base    = sm_page_url( 'account', array( 'view' => 'rfq' ) );
$markets = (array) ( $set['markets'] ?? array() );
$cats    = (array) ( $set['categories'] ?? array() );
wp_enqueue_script( 'sm-product-form', SM_THEME_URI . '/assets/js/product-form.js', array(), sm_theme_asset_version( 'assets/js/product-form.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
?>
<?php sm_theme_form_open( 'rfq_save', array( 'class' => 'sm-form smp-form smq-form', 'enctype' => 'multipart/form-data', 'data-smp' => '', 'data-smp-partial' => add_query_arg( 'smq_partial', '1', $base ) ) ); ?>
	<input type="hidden" name="rfq_id" value="<?php echo esc_attr( $v( 'rfq_id' ) ); ?>">

	<section class="sm-panel smp-step">
		<h2 class="smw-step__title"><span class="smw-step__n">1</span><?php esc_html_e( 'What do you need?', 'sm-marketplace' ); ?></h2>
		<div class="sm-field-grid">
			<div class="sm-field">
				<label for="smq-market"><?php esc_html_e( 'Market', 'sm-marketplace' ); ?></label>
				<select id="smq-market" name="market"<?php sm_theme_aria( 'market' ); ?>>
					<option value=""><?php esc_html_e( 'Choose…', 'sm-marketplace' ); ?></option>
					<?php foreach ( $markets as $k => $l ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $v( 'market' ), $k ); ?>><?php echo esc_html( sm_t( $l ) ); ?></option><?php endforeach; ?>
				</select>
				<?php sm_theme_error( 'market' ); ?>
			</div>
			<div class="sm-field">
				<label for="smq-cat"><?php esc_html_e( 'Category', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
				<select id="smq-cat" name="category"<?php sm_theme_aria( 'category' ); ?>>
					<option value=""><?php esc_html_e( 'Any category', 'sm-marketplace' ); ?></option>
					<?php foreach ( $cats as $m => $list ) : ?>
						<optgroup label="<?php echo esc_attr( sm_t( $markets[ $m ] ?? array( 'fr' => $m ) ) ); ?>" data-market="<?php echo esc_attr( $m ); ?>">
							<?php foreach ( $list as $slug => $name ) : ?><option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $v( 'category' ), $slug ); ?>><?php echo esc_html( sm_t( $name ) ); ?></option><?php endforeach; ?>
						</optgroup>
					<?php endforeach; ?>
				</select>
				<?php sm_theme_error( 'category' ); ?>
			</div>
		</div>
	</section>

	<section class="sm-panel smp-step">
		<h2 class="smw-step__title"><span class="smw-step__n">2</span><?php esc_html_e( 'Quantity', 'sm-marketplace' ); ?></h2>
		<?php sm_component( 'rfq-tech', array( 'state' => $state ) ); ?>
	</section>

	<section class="sm-panel smp-step">
		<h2 class="smw-step__title"><span class="smw-step__n">3</span><?php esc_html_e( 'Title and description', 'sm-marketplace' ); ?></h2>
		<div class="sm-field">
			<label for="smq-title-fr"><?php esc_html_e( 'Title in French', 'sm-marketplace' ); ?></label>
			<input id="smq-title-fr" name="title[fr]" lang="fr" dir="ltr" maxlength="140" value="<?php echo esc_attr( (string) ( $state['title']['fr'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. IPE 270 beams for a hangar, delivery on site', 'sm-marketplace' ); ?>"<?php sm_theme_aria( 'title' ); ?>>
			<?php sm_theme_error( 'title' ); ?>
		</div>
		<div class="sm-field">
			<label for="smq-title-ar"><?php esc_html_e( 'Title in Arabic', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
			<input id="smq-title-ar" name="title[ar]" lang="ar" dir="rtl" maxlength="140" value="<?php echo esc_attr( (string) ( $state['title']['ar'] ?? '' ) ); ?>">
		</div>
		<div class="sm-field">
			<label for="smq-desc"><?php esc_html_e( 'Technical description', 'sm-marketplace' ); ?></label>
			<textarea id="smq-desc" name="description" rows="5" dir="auto" maxlength="4000" placeholder="<?php esc_attr_e( 'Steel grade, standard, finish, tolerances, certificates, cutting…', 'sm-marketplace' ); ?>"<?php sm_theme_aria( 'description' ); ?>><?php echo esc_textarea( $v( 'description' ) ); ?></textarea>
			<?php sm_theme_error( 'description' ); ?>
		</div>
		<div class="sm-field">
			<label for="smq-notes"><?php esc_html_e( 'Additional notes', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
			<textarea id="smq-notes" name="notes" rows="3" dir="auto" maxlength="2000" placeholder="<?php esc_attr_e( 'Site access, unloading, invoicing…', 'sm-marketplace' ); ?>"><?php echo esc_textarea( $v( 'notes' ) ); ?></textarea>
			<?php sm_theme_error( 'notes' ); ?>
		</div>
	</section>

	<section class="sm-panel smp-step">
		<h2 class="smw-step__title"><span class="smw-step__n">4</span><?php esc_html_e( 'Delivery and deadline', 'sm-marketplace' ); ?></h2>
		<div class="sm-field-grid">
			<div class="sm-field">
				<label for="smq-wilaya"><?php esc_html_e( 'Delivery wilaya', 'sm-marketplace' ); ?></label>
				<select id="smq-wilaya" name="wilaya"<?php sm_theme_aria( 'wilaya' ); ?>>
					<option value=""><?php esc_html_e( 'Choose…', 'sm-marketplace' ); ?></option>
					<?php foreach ( sm_theme_wilayas() as $code => $name ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( $v( 'wilaya' ), (string) $code ); ?>><?php echo esc_html( $code . ' — ' . sm_t( $name ) ); ?></option><?php endforeach; ?>
				</select>
				<?php sm_theme_error( 'wilaya' ); ?>
			</div>
			<div class="sm-field">
				<label for="smq-place"><?php esc_html_e( 'Delivery place', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
				<input id="smq-place" name="place" dir="auto" maxlength="190" value="<?php echo esc_attr( $v( 'place' ) ); ?>" placeholder="<?php esc_attr_e( 'Town, industrial zone, site', 'sm-marketplace' ); ?>">
			</div>
			<div class="sm-field">
				<label for="smq-needed"><?php esc_html_e( 'Needed by', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
				<input id="smq-needed" name="needed_by" type="date" min="<?php echo esc_attr( $set['today'] ?? '' ); ?>" value="<?php echo esc_attr( $v( 'needed_by' ) ); ?>"<?php sm_theme_aria( 'needed_by' ); ?>>
				<?php sm_theme_error( 'needed_by' ); ?>
			</div>
			<div class="sm-field">
				<label for="smq-deadline"><?php esc_html_e( 'Offers accepted until', 'sm-marketplace' ); ?></label>
				<input id="smq-deadline" name="deadline" type="date" min="<?php echo esc_attr( $set['min_deadline'] ?? '' ); ?>" max="<?php echo esc_attr( $set['max_deadline'] ?? '' ); ?>" value="<?php echo esc_attr( $v( 'deadline' ) ); ?>"<?php sm_theme_aria( 'deadline' ); ?>>
				<?php sm_theme_error( 'deadline' ); ?>
			</div>
		</div>
	</section>

	<section class="sm-panel smp-step">
		<h2 class="smw-step__title"><span class="smw-step__n">5</span><?php esc_html_e( 'Drawings and documents', 'sm-marketplace' ); ?></h2>
		<?php foreach ( array_filter( array_map( 'absint', (array) ( $state['keep_files'] ?? array() ) ) ) as $aid ) : ?>
			<label class="sm-check"><input type="checkbox" name="keep_files[]" value="<?php echo esc_attr( $aid ); ?>" checked><span><?php sm_icon( 'file' ); ?><?php echo esc_html( get_the_title( $aid ) ); ?></span></label>
		<?php endforeach; ?>
		<label class="sm-upload">
			<input type="file" name="files[]" accept="application/pdf,image/jpeg,image/png,image/webp" multiple>
			<?php sm_icon( 'upload', 'sm-upload__icon' ); ?>
			<strong><?php esc_html_e( 'Attach drawings, a bill of materials or photos', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></strong>
			<small><?php echo esc_html( sprintf( /* translators: 1: files, 2: MB. */ __( 'PDF or images, up to %1$d files of %2$d MB. Export DWG/DXF drawings to PDF.', 'sm-marketplace' ), (int) ( $set['max_files'] ?? 5 ), (int) ( $set['file_mb'] ?? 10 ) ) ); ?></small>
		</label>
		<p class="sm-help"><?php esc_html_e( 'Documents are shown only to signed-in companies.', 'sm-marketplace' ); ?></p>
	</section>

	<div class="smp-actions">
		<a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( $base ); ?>"><?php esc_html_e( 'Cancel', 'sm-marketplace' ); ?></a>
		<button type="submit" class="sm-btn sm-btn--accent" name="intent" value="submit"><?php sm_icon( 'check' ); ?><?php echo esc_html( $edit ? __( 'Save the request', 'sm-marketplace' ) : __( 'Publish my request', 'sm-marketplace' ) ); ?></button>
	</div>
	<?php if ( ! empty( $set['moderation'] ) ) : ?>
		<p class="sm-help"><?php esc_html_e( 'The platform team checks each request before it is shown to suppliers.', 'sm-marketplace' ); ?></p>
	<?php endif; ?>
</form>
