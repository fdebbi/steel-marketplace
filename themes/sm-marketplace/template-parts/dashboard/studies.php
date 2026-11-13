<?php
/**
 * Engineering studies requested by the account (companies, professionals,
 * clients). The platform answers with a quote and carries out the study with
 * one of its partner engineers; the client only deals with the platform.
 * ?id= opens one request.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$id   = absint( sm_theme_query_arg( 'id' ) );
$data = sm_theme_data( 'studies', array( 'id' => $id ) );
if ( ! is_array( $data ) ) {
	sm_component( 'empty-state', array( 'icon' => 'hard-hat', 'title' => __( 'Engineering studies arrive with their plugin', 'sm-marketplace' ), 'text' => __( 'This section needs the SM Engineering plugin.', 'sm-marketplace' ) ) );
	return;
}
$back = sm_page_url( 'account', array( 'view' => 'studies' ) );

if ( $id ) :
	$m = $data['mission'];
	if ( ! $m ) {
		sm_component( 'empty-state', array( 'icon' => 'hard-hat', 'title' => __( 'Request not found', 'sm-marketplace' ), 'action_label' => __( 'Back to my studies', 'sm-marketplace' ), 'action_url' => $back ) );
		return;
	}
	?>
	<p><a class="sm-link" href="<?php echo esc_url( $back ); ?>"><?php sm_icon( 'chevron-start' ); ?><?php esc_html_e( 'All my studies', 'sm-marketplace' ); ?></a></p>
	<section class="sm-panel sme-head">
		<div class="sme-head__top">
			<div>
				<p class="sme-ref" dir="ltr"><?php echo esc_html( $m['ref'] ); ?></p>
				<h2 class="sm-panel__title" dir="auto"><?php echo esc_html( $m['title'] ); ?></h2>
				<p class="sm-muted"><?php echo esc_html( $m['type'] ); ?> · <?php echo esc_html( sm_t( sm_theme_wilayas()[ $m['wilaya'] ] ?? '' ) ); ?> · <?php echo esc_html( sm_ago( $m['created'] ) ); ?></p>
				<?php if ( ! empty( $m['prestations'] ) ) : ?>
					<ul class="sme-prest-list" aria-label="<?php esc_attr_e( 'Prestations wanted', 'sm-marketplace' ); ?>"><?php foreach ( $m['prestations'] as $p ) : ?><li><?php echo esc_html( $p ); ?></li><?php endforeach; ?></ul>
				<?php endif; ?>
			</div>
			<?php sm_mission_status( $m ); ?>
		</div>
		<?php if ( 'off' !== $m['tone'] ) : ?>
			<ol class="sme-steps" role="list">
				<?php foreach ( $m['steps'] as $step ) : ?>
					<li class="is-<?php echo esc_attr( $step['state'] ); ?>"><span aria-hidden="true"></span><?php echo esc_html( $step['label'] ); ?></li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
		<?php if ( $m['reason'] ) : ?>
			<div class="sm-notice sm-notice--warn"><?php sm_icon( 'alert' ); ?><p><strong><?php esc_html_e( 'The platform cannot take this request:', 'sm-marketplace' ); ?></strong> <?php echo esc_html( $m['reason'] ); ?></p></div>
		<?php endif; ?>
	</section>

	<?php if ( $m['quote'] ) : ?>
		<section class="sm-panel sme-quote<?php echo $m['can']['accept'] ? ' is-open' : ''; ?>">
			<h2 class="sm-panel__title"><?php esc_html_e( 'Quote of the platform', 'sm-marketplace' ); ?></h2>
			<dl class="sme-facts">
				<div><dt><?php esc_html_e( 'Price', 'sm-marketplace' ); ?></dt><dd class="sme-price"><?php echo esc_html( sm_money( $m['quote']['amount'] ) ); ?></dd></div>
				<?php if ( $m['quote']['days'] ) : ?><div><dt><?php esc_html_e( 'Time', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( sprintf( /* translators: %d: days. */ _n( '%d day', '%d days', $m['quote']['days'], 'sm-marketplace' ), $m['quote']['days'] ) ); ?></dd></div><?php endif; ?>
				<?php if ( $m['quote']['valid_until'] ) : ?><div><dt><?php esc_html_e( 'Valid until', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( sm_date( $m['quote']['valid_until'] ) ); ?><?php if ( $m['quote']['expired'] ) : ?> <span class="sm-status sm-status--muted"><?php esc_html_e( 'Expired', 'sm-marketplace' ); ?></span><?php endif; ?></dd></div><?php endif; ?>
			</dl>
			<?php if ( $m['quote']['message'] ) : ?><p class="sme-quote__msg" dir="auto"><?php echo nl2br( esc_html( $m['quote']['message'] ) ); ?></p><?php endif; ?>
			<?php if ( $m['can']['accept'] || $m['can']['refuse'] ) : ?>
				<?php sm_theme_form_open( 'mission_client', array( 'class' => 'sm-form sme-actions' ) ); ?>
					<input type="hidden" name="mission_id" value="<?php echo (int) $m['id']; ?>">
					<?php if ( $m['can']['accept'] ) : ?><button class="sm-btn sm-btn--accent" name="do" value="accept" type="submit"><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Accept the quote', 'sm-marketplace' ); ?></button><?php endif; ?>
					<details class="sme-refuse">
						<summary class="sm-btn sm-btn--ghost"><?php esc_html_e( 'Decline', 'sm-marketplace' ); ?></summary>
						<label for="sme-reason"><?php esc_html_e( 'Why? (optional, it helps the platform)', 'sm-marketplace' ); ?></label>
						<textarea id="sme-reason" name="reason" rows="2" dir="auto" maxlength="1000"></textarea>
						<button class="sm-btn sm-btn--ghost" name="do" value="refuse" type="submit"><?php esc_html_e( 'Decline the quote', 'sm-marketplace' ); ?></button>
					</details>
				</form>
				<p class="sm-help"><?php esc_html_e( 'Once you accept, the platform appoints one of its partner engineers. The study is delivered in this page.', 'sm-marketplace' ); ?></p>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<?php if ( $m['deliverables'] ) : ?>
		<section class="sm-panel sme-delivered">
			<h2 class="sm-panel__title"><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Your study is delivered', 'sm-marketplace' ); ?></h2>
			<?php sm_component( 'mission-files', array( 'files' => $m['deliverables'] ) ); ?>
			<?php if ( $m['can']['confirm'] ) : ?>
				<?php sm_theme_form_open( 'mission_client', array( 'class' => 'sm-form sme-actions' ) ); ?>
					<input type="hidden" name="mission_id" value="<?php echo (int) $m['id']; ?>">
					<button class="sm-btn sm-btn--primary" name="do" value="confirm" type="submit"><?php esc_html_e( 'I received the study — close the request', 'sm-marketplace' ); ?></button>
				</form>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<div class="sm-dash__cols">
		<section class="sm-panel">
			<h2 class="sm-panel__title"><?php esc_html_e( 'Your request', 'sm-marketplace' ); ?></h2>
			<p class="sme-desc" dir="auto"><?php echo nl2br( esc_html( $m['description'] ) ); ?></p>
			<dl class="sme-facts">
				<?php if ( $m['needed_by'] ) : ?><div><dt><?php esc_html_e( 'Wanted for', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( sm_date( $m['needed_by'] ) ); ?></dd></div><?php endif; ?>
				<?php if ( $m['budget'] > 0 ) : ?><div><dt><?php esc_html_e( 'Indicative budget', 'sm-marketplace' ); ?></dt><dd><?php echo esc_html( sm_money( $m['budget'] ) ); ?></dd></div><?php endif; ?>
			</dl>
			<?php sm_component( 'mission-files', array( 'files' => $m['brief'], 'title' => __( 'Your documents', 'sm-marketplace' ) ) ); ?>
			<?php sm_component( 'mission-files', array( 'files' => $m['platform'], 'title' => __( 'Documents of the platform', 'sm-marketplace' ) ) ); ?>
			<?php if ( $m['can']['files'] ) : ?>
				<?php sm_theme_form_open( 'mission_client', array( 'class' => 'sm-form', 'enctype' => 'multipart/form-data' ) ); ?>
					<input type="hidden" name="mission_id" value="<?php echo (int) $m['id']; ?>">
					<label class="sm-upload sm-upload--small">
						<input type="file" name="files[]" multiple data-sm-filename>
						<?php sm_icon( 'upload', 'sm-upload__icon' ); ?>
						<strong><?php esc_html_e( 'Add documents', 'sm-marketplace' ); ?></strong>
						<small data-sm-filename-label><?php esc_html_e( 'Plans, photos, soil report…', 'sm-marketplace' ); ?></small>
					</label>
					<button class="sm-btn sm-btn--ghost" name="do" value="files" type="submit"><?php sm_icon( 'upload' ); ?><?php esc_html_e( 'Send the documents', 'sm-marketplace' ); ?></button>
				</form>
			<?php endif; ?>
			<?php if ( $m['can']['cancel'] ) : ?>
				<?php sm_theme_form_open( 'mission_client', array( 'class' => 'sm-form sme-actions' ) ); ?>
					<input type="hidden" name="mission_id" value="<?php echo (int) $m['id']; ?>">
					<button class="sm-btn sm-btn--ghost sm-btn--sm" name="do" value="cancel" type="submit" data-sm-confirm="<?php esc_attr_e( 'Cancel this request?', 'sm-marketplace' ); ?>"><?php sm_icon( 'close' ); ?><?php esc_html_e( 'Cancel the request', 'sm-marketplace' ); ?></button>
				</form>
			<?php endif; ?>
		</section>
		<?php sm_component( 'mission-thread', array( 'mission' => $m, 'intro' => __( 'Questions, details, documents: the platform answers here and coordinates the engineer.', 'sm-marketplace' ) ) ); ?>
	</div>
	<?php
	return;
endif;

$items = $data['items'];
?>
<div class="sm-panel sme-intro">
	<div>
		<h2 class="sm-panel__title"><?php esc_html_e( 'Studies and inspections by the platform', 'sm-marketplace' ); ?></h2>
		<p class="sm-muted"><?php esc_html_e( 'Design, calculation notes, drawings, inspection of a used structure: send your request, receive a quote from the platform, and get the study done by a verified partner engineer.', 'sm-marketplace' ); ?></p>
	</div>
	<?php if ( ! $data['block'] ) : ?>
		<a class="sm-btn sm-btn--accent" href="<?php echo esc_url( $data['request_url'] ); ?>"><?php sm_icon( 'plus' ); ?><?php esc_html_e( 'Ask for a study', 'sm-marketplace' ); ?></a>
	<?php endif; ?>
</div>
<?php if ( ! $items ) : ?>
	<?php sm_component( 'empty-state', array( 'icon' => 'hard-hat', 'title' => __( 'No study request yet', 'sm-marketplace' ), 'text' => __( 'Your requests and their quotes appear here.', 'sm-marketplace' ) ) ); ?>
<?php else : ?>
	<div class="sm-table-wrap">
		<table class="sm-table sm-table--stack">
			<thead><tr><th><?php esc_html_e( 'Request', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Service', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Quote', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Status', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Updated', 'sm-marketplace' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $items as $m ) : ?>
					<tr>
						<td data-label="<?php esc_attr_e( 'Request', 'sm-marketplace' ); ?>"><a href="<?php echo esc_url( $m['url'] ); ?>"><strong dir="auto"><?php echo esc_html( $m['title'] ); ?></strong></a><br><small dir="ltr"><?php echo esc_html( $m['ref'] ); ?></small></td>
						<td data-label="<?php esc_attr_e( 'Service', 'sm-marketplace' ); ?>"><?php echo esc_html( $m['type'] ); ?></td>
						<td data-label="<?php esc_attr_e( 'Quote', 'sm-marketplace' ); ?>"><?php echo $m['amount'] ? esc_html( sm_money( $m['amount'] ) ) : '—'; ?></td>
						<td data-label="<?php esc_attr_e( 'Status', 'sm-marketplace' ); ?>"><?php sm_mission_status( $m ); ?></td>
						<td data-label="<?php esc_attr_e( 'Updated', 'sm-marketplace' ); ?>"><?php echo esc_html( sm_ago( $m['updated'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
