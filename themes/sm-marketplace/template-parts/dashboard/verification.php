<?php
/**
 * Verification: progress, documents upload, request status.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$account = $args['account'];
$state   = sm_theme_data( 'verification' );
$class   = sm_account_class( $account );
$is_eng  = 'engineer' === $class;
$level   = (int) $account['company']['verification'];
?>
<section class="sm-panel">
	<h2 class="sm-panel__title"><?php esc_html_e( 'Verification steps', 'sm-marketplace' ); ?></h2>
	<?php sm_component( 'verification-steps', array( 'account' => $account ) ); ?>
	<p class="sm-help"><?php esc_html_e( 'Verified accounts get the badge on their company page and listings, and can answer requests for quotation.', 'sm-marketplace' ); ?></p>
</section>

<?php if ( ! $state ) : ?>
	<?php return; ?>
<?php endif; ?>

<?php $request = $state['request']; ?>
<?php if ( $level >= $state['target_level'] ) : ?>
	<div class="sm-notice sm-notice--success"><?php sm_icon( 'verified' ); ?><p><?php echo esc_html( $is_eng ? __( 'Your engineering credentials are verified.', 'sm-marketplace' ) : ( 'company' === $class ? __( 'Your business is verified.', 'sm-marketplace' ) : __( 'Your account is verified.', 'sm-marketplace' ) ) ); ?></p></div>
<?php elseif ( $request && 'pending' === $request['status'] ) : ?>
	<div class="sm-notice sm-notice--info"><?php sm_icon( 'calendar' ); ?><p><?php esc_html_e( 'Your documents are being reviewed by the platform team, usually within 2 working days.', 'sm-marketplace' ); ?></p></div>
<?php elseif ( $level < 1 ) : ?>
	<div class="sm-notice sm-notice--warn"><?php sm_icon( 'shield' ); ?><p><?php esc_html_e( 'Verify your contact details first.', 'sm-marketplace' ); ?> <a href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'verify-phone' ) ) ); ?>"><?php esc_html_e( 'Enter the code', 'sm-marketplace' ); ?></a></p></div>
<?php else : ?>
	<?php if ( $request && 'rejected' === $request['status'] ) : ?>
		<div class="sm-notice sm-notice--error"><?php sm_icon( 'alert' ); ?><p><?php esc_html_e( 'Your previous request was not validated.', 'sm-marketplace' ); ?> <?php echo esc_html( $request['note'] ); ?></p></div>
	<?php endif; ?>
	<?php sm_theme_form_open( 'verification_submit', array( 'class' => 'sm-form sm-panel', 'enctype' => 'multipart/form-data' ) ); ?>
		<h2 class="sm-panel__title"><?php echo esc_html( $is_eng ? __( 'Send your engineering credentials', 'sm-marketplace' ) : ( 'company' === $class ? __( 'Send your company documents', 'sm-marketplace' ) : __( 'Send your documents', 'sm-marketplace' ) ) ); ?></h2>
		<?php if ( 'company' === $class ) : ?>
			<div class="sm-field sm-field--row sm-field--row3">
				<div><label for="v-rc"><?php esc_html_e( 'RC number', 'sm-marketplace' ); ?></label><input id="v-rc" name="rc_number" value="<?php echo esc_attr( sm_theme_old( 'rc_number', $state['legal']['rc_number'] ) ); ?>" dir="ltr" autocomplete="off"></div>
				<div><label for="v-nif">NIF</label><input id="v-nif" name="nif" value="<?php echo esc_attr( sm_theme_old( 'nif', $state['legal']['nif'] ) ); ?>" dir="ltr" inputmode="numeric" autocomplete="off"></div>
				<div><label for="v-nis">NIS</label><input id="v-nis" name="nis" value="<?php echo esc_attr( sm_theme_old( 'nis', $state['legal']['nis'] ) ); ?>" dir="ltr" inputmode="numeric" autocomplete="off"></div>
			</div>
		<?php endif; ?>
		<?php $first = true; ?>
		<?php foreach ( $state['documents'] as $key => $label ) : ?>
			<div class="sm-field">
				<label class="sm-upload sm-upload--small">
					<input type="file" name="doc_<?php echo esc_attr( $key ); ?>" accept=".pdf,image/jpeg,image/png" <?php echo $first ? 'required' : ''; ?> data-sm-filename>
					<?php sm_icon( 'file', 'sm-upload__icon' ); ?>
					<strong><?php echo esc_html( $label ); ?><?php echo $first ? ' *' : ''; ?></strong>
					<small data-sm-filename-label><?php esc_html_e( 'PDF, JPG or PNG, 5 MB maximum', 'sm-marketplace' ); ?></small>
				</label>
				<?php sm_theme_error( 'doc_' . $key ); ?>
			</div>
			<?php $first = false; ?>
		<?php endforeach; ?>
		<div class="sm-field"><label for="v-note"><?php esc_html_e( 'Note for the platform team (optional)', 'sm-marketplace' ); ?></label><textarea id="v-note" name="note" rows="2" dir="auto"></textarea></div>
		<p class="sm-help"><?php esc_html_e( 'Documents are stored privately and are only seen by the platform team.', 'sm-marketplace' ); ?></p>
		<button class="sm-btn sm-btn--accent" type="submit"><?php sm_icon( 'upload' ); ?><?php esc_html_e( 'Send for verification', 'sm-marketplace' ); ?></button>
	</form>
<?php endif; ?>
