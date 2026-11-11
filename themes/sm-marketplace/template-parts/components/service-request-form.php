<?php
/**
 * Request form on a service page (sm-services).
 *
 * @var array $args { item: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$item = $args['item'];
$set  = (array) sm_theme_data( 'services_settings' );
$acc  = sm_theme_account();
$old  = sm_theme_old_all();
$val  = static fn( $k, $d = '' ) => isset( $old[ $k ] ) && ! is_array( $old[ $k ] ) ? (string) $old[ $k ] : $d;
?>
<?php sm_theme_form_open( 'service_request', array( 'class' => 'sm-form sms-request__form', 'enctype' => 'multipart/form-data' ) ); ?>
	<input type="hidden" name="service_id" value="<?php echo esc_attr( $item['id'] ); ?>">
	<div class="sm-field">
		<label for="sms-msg"><?php esc_html_e( 'Your project', 'sm-marketplace' ); ?></label>
		<textarea id="sms-msg" name="message" rows="5" dir="auto" maxlength="3000" placeholder="<?php esc_attr_e( 'What must be made or installed: profiles, quantities, dimensions, site, finish…', 'sm-marketplace' ); ?>"<?php sm_theme_aria( 'message' ); ?>><?php echo esc_textarea( $val( 'message' ) ); ?></textarea>
		<?php sm_theme_error( 'message' ); ?>
	</div>
	<div class="sm-field sm-field--row sm-field--row3">
		<div>
			<label for="sms-w"><?php esc_html_e( 'Wilaya of the project', 'sm-marketplace' ); ?></label>
			<select id="sms-w" name="wilaya"<?php sm_theme_aria( 'wilaya' ); ?>>
				<option value=""><?php esc_html_e( 'Choose…', 'sm-marketplace' ); ?></option>
				<?php foreach ( sm_theme_wilayas() as $c => $n ) : ?><option value="<?php echo esc_attr( $c ); ?>" <?php selected( $val( 'wilaya', (string) ( $acc['company']['wilaya'] ?? '' ) ), (string) $c ); ?>><?php echo esc_html( $c . ' — ' . sm_t( $n ) ); ?></option><?php endforeach; ?>
			</select>
			<?php sm_theme_error( 'wilaya' ); ?>
		</div>
		<div>
			<label for="sms-date"><?php esc_html_e( 'Wanted by', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
			<input id="sms-date" name="needed_by" type="date" value="<?php echo esc_attr( $val( 'needed_by' ) ); ?>"<?php sm_theme_aria( 'needed_by' ); ?>>
			<?php sm_theme_error( 'needed_by' ); ?>
		</div>
		<div>
			<label for="sms-budget"><?php esc_html_e( 'Budget', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
			<input id="sms-budget" name="budget" inputmode="decimal" dir="ltr" value="<?php echo esc_attr( $val( 'budget' ) ); ?>" placeholder="<?php echo esc_attr( sm_currency_label() ); ?>"<?php sm_theme_aria( 'budget' ); ?>>
			<?php sm_theme_error( 'budget' ); ?>
		</div>
	</div>
	<label class="sm-upload sm-upload--small">
		<input type="file" name="files[]" accept="application/pdf,image/jpeg,image/png,image/webp" multiple>
		<?php sm_icon( 'upload', 'sm-upload__icon' ); ?>
		<strong><?php esc_html_e( 'Attach drawings or photos', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></strong>
		<small><?php echo esc_html( sprintf( /* translators: 1: files, 2: MB. */ __( 'PDF or images, up to %1$d files of %2$d MB.', 'sm-marketplace' ), (int) ( $set['max_files'] ?? 3 ), (int) ( $set['file_mb'] ?? 10 ) ) ); ?></small>
	</label>
	<button class="sm-btn sm-btn--accent" type="submit"><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Send my request', 'sm-marketplace' ); ?></button>
	<p class="sm-help"><?php esc_html_e( 'The provider answers with a quote (amount and time). You decide afterwards; nothing is binding before.', 'sm-marketplace' ); ?></p>
</form>
