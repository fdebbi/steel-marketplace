<?php
/**
 * Partner engineer: missions (offered, in progress, done), or one mission
 * with ?id=. The engineer sees the technical brief and the fee, never the
 * client's identity nor the client's price.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$id = absint( sm_theme_query_arg( 'id' ) );
$d  = sm_theme_data( 'engineer_account', array( 'id' => $id ) );
if ( ! is_array( $d ) || 'approved' !== $d['status'] ) {
	sm_component( 'empty-state', array( 'icon' => 'hard-hat', 'title' => __( 'Missions open after the approval of your application', 'sm-marketplace' ) ) );
	return;
}
$back = sm_page_url( 'account', array( 'view' => 'missions' ) );

if ( $id ) :
	$m = $d['mission'];
	if ( ! $m ) {
		sm_component( 'empty-state', array( 'icon' => 'hard-hat', 'title' => __( 'This mission is not assigned to you', 'sm-marketplace' ), 'action_label' => __( 'Back to my missions', 'sm-marketplace' ), 'action_url' => $back ) );
		return;
	}
	?>
	<p><a class="sm-link" href="<?php echo esc_url( $back ); ?>"><?php sm_icon( 'chevron-start' ); ?><?php esc_html_e( 'All my missions', 'sm-marketplace' ); ?></a></p>
	<section class="sm-panel sme-head">
		<div class="sme-head__top">
			<div>
				<p class="sme-ref" dir="ltr"><?php echo esc_html( $m['ref'] ); ?></p>
				<h2 class="sm-panel__title" dir="auto"><?php echo esc_html( $m['title'] ); ?></h2>
				<p class="sm-muted"><?php echo esc_html( $m['type'] ); ?> · <?php echo esc_html( sm_t( sm_theme_wilayas()[ $m['wilaya'] ] ?? '' ) ); ?></p>
			</div>
			<?php sm_mission_status( $m ); ?>
		</div>
		<dl class="sme-facts">
			<div><dt><?php esc_html_e( 'Your fee', 'sm-marketplace' ); ?></dt><dd class="sme-price"><?php echo esc_html( sm_money( $m['fee'] ) ); ?></dd></div>
			<?php if ( $m['days'] ) : ?><div><dt><?php esc_html_e( 'Time', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( sprintf( /* translators: %d: days. */ _n( '%d day', '%d days', $m['days'], 'sm-marketplace' ), $m['days'] ) ); ?></dd></div><?php endif; ?>
			<?php if ( $m['needed_by'] ) : ?><div><dt><?php esc_html_e( 'Wanted for', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( sm_date( $m['needed_by'] ) ); ?></dd></div><?php endif; ?>
		</dl>
		<?php if ( $m['can']['take'] ) : ?>
			<?php sm_theme_form_open( 'mission_engineer', array( 'class' => 'sm-form sme-actions' ) ); ?>
				<input type="hidden" name="mission_id" value="<?php echo (int) $m['id']; ?>">
				<button class="sm-btn sm-btn--accent" name="do" value="take" type="submit"><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Accept the mission', 'sm-marketplace' ); ?></button>
				<details class="sme-refuse">
					<summary class="sm-btn sm-btn--ghost"><?php esc_html_e( 'Decline', 'sm-marketplace' ); ?></summary>
					<label for="sme-reason"><?php esc_html_e( 'Reason (for the platform)', 'sm-marketplace' ); ?></label>
					<textarea id="sme-reason" name="reason" rows="2" dir="auto" maxlength="1000"></textarea>
					<button class="sm-btn sm-btn--ghost" name="do" value="giveup" type="submit"><?php esc_html_e( 'Decline the mission', 'sm-marketplace' ); ?></button>
				</details>
			</form>
		<?php endif; ?>
	</section>

	<div class="sm-dash__cols">
		<section class="sm-panel">
			<h2 class="sm-panel__title"><?php esc_html_e( 'Brief', 'sm-marketplace' ); ?></h2>
			<p class="sme-desc" dir="auto"><?php echo nl2br( esc_html( $m['description'] ) ); ?></p>
			<?php if ( $m['note'] ) : ?>
				<div class="sm-notice sm-notice--info"><?php sm_icon( 'info' ); ?><p dir="auto"><strong><?php esc_html_e( 'Instructions of the platform:', 'sm-marketplace' ); ?></strong> <?php echo nl2br( esc_html( $m['note'] ) ); ?></p></div>
			<?php endif; ?>
			<?php sm_component( 'mission-files', array( 'files' => $m['brief'], 'title' => __( 'Documents of the client', 'sm-marketplace' ) ) ); ?>
			<?php sm_component( 'mission-files', array( 'files' => $m['platform'], 'title' => __( 'Documents of the platform', 'sm-marketplace' ) ) ); ?>
		</section>
		<section class="sm-panel">
			<h2 class="sm-panel__title"><?php esc_html_e( 'Your deliverables', 'sm-marketplace' ); ?></h2>
			<?php sm_component( 'mission-files', array( 'files' => $m['deliverables'], 'empty' => __( 'Plans, calculation notes, inspection report: add them here, then send the work to the platform.', 'sm-marketplace' ), 'remove' => $m['can']['upload'] ? $m['id'] : 0 ) ); ?>
			<?php if ( $m['can']['upload'] ) : ?>
				<?php sm_theme_form_open( 'mission_engineer', array( 'class' => 'sm-form', 'enctype' => 'multipart/form-data' ) ); ?>
					<input type="hidden" name="mission_id" value="<?php echo (int) $m['id']; ?>">
					<label class="sm-upload sm-upload--small">
						<input type="file" name="files[]" multiple data-sm-filename>
						<?php sm_icon( 'upload', 'sm-upload__icon' ); ?>
						<strong><?php esc_html_e( 'Add deliverables', 'sm-marketplace' ); ?></strong>
						<small data-sm-filename-label><?php esc_html_e( 'PDF, DWG, DXF, IFC, Excel, Word, images or ZIP.', 'sm-marketplace' ); ?></small>
					</label>
					<button class="sm-btn sm-btn--ghost" name="do" value="upload" type="submit"><?php sm_icon( 'upload' ); ?><?php esc_html_e( 'Upload', 'sm-marketplace' ); ?></button>
				</form>
			<?php endif; ?>
			<?php if ( $m['can']['submit'] && $m['deliverables'] ) : ?>
				<?php sm_theme_form_open( 'mission_engineer', array( 'class' => 'sm-form sme-actions' ) ); ?>
					<input type="hidden" name="mission_id" value="<?php echo (int) $m['id']; ?>">
					<button class="sm-btn sm-btn--accent" name="do" value="submit" type="submit" data-sm-confirm="<?php esc_attr_e( 'Send the work to the platform? You can no longer change the files while it is checked.', 'sm-marketplace' ); ?>"><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Send the work to the platform', 'sm-marketplace' ); ?></button>
				</form>
			<?php elseif ( 'review' === $m['status'] ) : ?>
				<p class="sm-notice sm-notice--info"><?php sm_icon( 'info' ); ?><span><?php esc_html_e( 'The platform checks your work. It may ask for changes in the messages.', 'sm-marketplace' ); ?></span></p>
			<?php endif; ?>
			<?php if ( $m['can']['invoice'] || $m['invoices'] ) : ?>
				<h3 class="sm-panel__subtitle"><?php esc_html_e( 'Your fee invoice', 'sm-marketplace' ); ?></h3>
				<?php sm_component( 'mission-files', array( 'files' => $m['invoices'] ) ); ?>
				<?php if ( $m['can']['invoice'] ) : ?>
					<?php sm_theme_form_open( 'mission_engineer', array( 'class' => 'sm-form sme-inline', 'enctype' => 'multipart/form-data' ) ); ?>
						<input type="hidden" name="mission_id" value="<?php echo (int) $m['id']; ?>">
						<label class="sm-upload sm-upload--small">
							<input type="file" name="invoice" accept=".pdf,image/jpeg,image/png" data-sm-filename>
							<?php sm_icon( 'file', 'sm-upload__icon' ); ?>
							<strong><?php esc_html_e( 'Send an invoice', 'sm-marketplace' ); ?></strong>
							<small data-sm-filename-label><?php esc_html_e( 'PDF, JPG or PNG. Seen by the platform only.', 'sm-marketplace' ); ?></small>
						</label>
						<button class="sm-btn sm-btn--ghost sm-btn--sm" name="do" value="invoice" type="submit"><?php esc_html_e( 'Send', 'sm-marketplace' ); ?></button>
					</form>
				<?php endif; ?>
			<?php endif; ?>
		</section>
	</div>
	<?php sm_component( 'mission-thread', array( 'mission' => $m, 'intro' => __( 'Questions about the brief go to the platform, which asks the client if needed.', 'sm-marketplace' ) ) ); ?>
	<?php
	return;
endif;

$groups = array(
	'offered' => array( __( 'Offered to you', 'sm-marketplace' ), __( 'No mission waiting for your answer.', 'sm-marketplace' ) ),
	'current' => array( __( 'In progress', 'sm-marketplace' ), __( 'No mission in progress.', 'sm-marketplace' ) ),
	'done'    => array( __( 'Done', 'sm-marketplace' ), __( 'Your completed missions appear here.', 'sm-marketplace' ) ),
);
foreach ( $groups as $key => list( $title, $empty ) ) :
	$list = $d['missions'][ $key ];
	?>
	<section class="sm-panel">
		<h2 class="sm-panel__title"><?php echo esc_html( $title ); ?> <span class="sm-count"><?php echo esc_html( (string) count( $list ) ); ?></span></h2>
		<?php if ( $list ) : ?>
			<div class="sm-table-wrap">
				<table class="sm-table sm-table--stack">
					<thead><tr><th><?php esc_html_e( 'Mission', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Wilaya', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Fee', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Status', 'sm-marketplace' ); ?></th></tr></thead>
					<tbody>
						<?php foreach ( $list as $m ) : ?>
							<tr>
								<td data-label="<?php esc_attr_e( 'Mission', 'sm-marketplace' ); ?>"><a href="<?php echo esc_url( $m['url'] ); ?>"><strong dir="auto"><?php echo esc_html( $m['title'] ); ?></strong></a><br><small><span dir="ltr"><?php echo esc_html( $m['ref'] ); ?></span> · <?php echo esc_html( $m['type'] ); ?></small></td>
								<td data-label="<?php esc_attr_e( 'Wilaya', 'sm-marketplace' ); ?>"><?php echo esc_html( sm_t( sm_theme_wilayas()[ $m['wilaya'] ] ?? '' ) ); ?></td>
								<td data-label="<?php esc_attr_e( 'Fee', 'sm-marketplace' ); ?>"><?php echo esc_html( sm_money( $m['fee'] ) ); ?></td>
								<td data-label="<?php esc_attr_e( 'Status', 'sm-marketplace' ); ?>"><?php sm_mission_status( $m ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<p class="sm-muted"><?php echo esc_html( $empty ); ?></p>
		<?php endif; ?>
	</section>
<?php endforeach; ?>
