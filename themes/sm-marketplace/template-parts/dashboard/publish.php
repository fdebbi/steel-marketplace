<?php
/**
 * Publish form layout: 4-step stepper, mobile first.
 *
 * Presentation only: field names follow the planned listing schema; validation,
 * weight and price calculation are performed server-side by sm-weight-engine and the
 * listing plugins, which fill the summary through the sm_slot 'publish.summary'.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$market = sm_theme_query_arg( 'market', 'products' );
if ( ! in_array( $market, array( 'products', 'reuse', 'buildings' ), true ) ) {
	$market = 'products';
}
$account = $args['account'];
if ( 'engineering' === $account['class'] ) {
	echo '<div class="sm-notice sm-notice--warn">' . sm_get_icon( 'lock' ) . '<p>' . esc_html__( 'Engineering accounts offer services only. To trade steel, open a separate business account.', 'sm-marketplace' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	return;
}
$cap_reason = sm_theme_cannot_reason( 'listing.' . $market );
if ( $cap_reason ) {
	echo '<div class="sm-notice sm-notice--warn">' . sm_get_icon( 'shield' ) . '<p>' . esc_html( $cap_reason ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
}
$steps = array(
	__( 'Market', 'sm-marketplace' ),
	__( 'Technical details', 'sm-marketplace' ),
	__( 'Photos', 'sm-marketplace' ),
	__( 'Price & location', 'sm-marketplace' ),
);
?>
<form class="sm-form sm-publish" method="post" action="#" enctype="multipart/form-data" data-sm-stepper data-sm-demo-form>
	<ol class="sm-stepper" aria-label="<?php esc_attr_e( 'Steps', 'sm-marketplace' ); ?>">
		<?php foreach ( $steps as $i => $label ) : ?>
			<li data-step-label="<?php echo esc_attr( $i ); ?>" <?php echo 0 === $i ? 'aria-current="step"' : ''; ?>><span class="sm-stepper__num"><?php echo esc_html( $i + 1 ); ?></span><span class="sm-stepper__label"><?php echo esc_html( $label ); ?></span></li>
		<?php endforeach; ?>
	</ol>

	<section class="sm-panel sm-step" data-step="0">
		<h2 class="sm-panel__title"><?php esc_html_e( 'What are you selling?', 'sm-marketplace' ); ?></h2>
		<fieldset class="sm-choice sm-choice--3">
			<legend class="screen-reader-text"><?php esc_html_e( 'Market', 'sm-marketplace' ); ?></legend>
			<?php
			$choices = array(
				'products'  => array( 'layers', __( 'New steel', 'sm-marketplace' ), __( 'Profiles, tubes, plates, accessories', 'sm-marketplace' ) ),
				'reuse'     => array( 'recycle', __( 'Reuse lot', 'sm-marketplace' ), __( 'Offcuts, surplus, used, scrap', 'sm-marketplace' ) ),
				'buildings' => array( 'warehouse', __( 'Building', 'sm-marketplace' ), __( 'Warehouse, hangar, kit', 'sm-marketplace' ) ),
			);
			foreach ( $choices as $key => list( $icon, $label, $desc ) ) :
				?>
				<label class="sm-choice__item">
					<input type="radio" name="market" value="<?php echo esc_attr( $key ); ?>" <?php checked( $market, $key ); ?> data-sm-market>
					<span class="sm-choice__card"><?php sm_icon( $icon ); ?><strong><?php echo esc_html( $label ); ?></strong><small><?php echo esc_html( $desc ); ?></small></span>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<div class="sm-field">
			<label for="p-cat"><?php esc_html_e( 'Category', 'sm-marketplace' ); ?></label>
			<?php foreach ( array( 'products', 'reuse', 'buildings' ) as $m ) : ?>
				<select id="<?php echo 'products' === $m ? 'p-cat' : 'p-cat-' . esc_attr( $m ); ?>" name="category" data-sm-for-market="<?php echo esc_attr( $m ); ?>" <?php echo $m !== $market ? 'hidden disabled' : ''; ?>>
					<?php foreach ( sm_theme_categories( $m ) as $slug => $label ) : ?><option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( sm_t( $label ) ); ?></option><?php endforeach; ?>
				</select>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="sm-panel sm-step" data-step="1" hidden>
		<h2 class="sm-panel__title"><?php esc_html_e( 'Technical details', 'sm-marketplace' ); ?></h2>

		<div data-sm-for-market="products reuse">
			<div class="sm-field sm-field--row">
				<div>
					<label for="p-desig"><?php esc_html_e( 'Profile designation', 'sm-marketplace' ); ?></label>
					<input id="p-desig" name="designation" list="sm-profiles" placeholder="IPE 200" dir="ltr" autocomplete="off">
					<datalist id="sm-profiles"><?php foreach ( array( 'IPE', 'IPN', 'HEA', 'HEB', 'HEM', 'UPN' ) as $fam ) : ?><option value="<?php echo esc_attr( $fam ); ?> "></option><?php endforeach; ?></datalist>
					<p class="sm-help"><?php esc_html_e( 'Choose from the standard profile list; unit weights come from the platform tables.', 'sm-marketplace' ); ?></p>
				</div>
				<div><label for="p-grade"><?php esc_html_e( 'Steel grade', 'sm-marketplace' ); ?></label><input id="p-grade" name="grade" placeholder="S275JR" dir="ltr"></div>
			</div>
			<div class="sm-field sm-field--row sm-field--row3">
				<div><label for="p-len"><?php esc_html_e( 'Length (m)', 'sm-marketplace' ); ?></label><input id="p-len" name="length_m" type="number" inputmode="decimal" step="0.01" min="0" dir="ltr"></div>
				<div><label for="p-qty"><?php esc_html_e( 'Number of pieces', 'sm-marketplace' ); ?></label><input id="p-qty" name="quantity" type="number" inputmode="numeric" min="1" dir="ltr"></div>
				<div data-sm-for-market="reuse">
					<label for="p-cond"><?php esc_html_e( 'Condition', 'sm-marketplace' ); ?></label>
					<select id="p-cond" name="condition"><?php foreach ( sm_condition_labels() as $code => $label ) : ?><option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
				</div>
			</div>
			<fieldset class="sm-field">
				<legend><?php esc_html_e( 'Weight', 'sm-marketplace' ); ?></legend>
				<div class="sm-segment">
					<label><input type="radio" name="weight_source" value="theoretical" checked data-sm-weight-source><span><?php sm_icon( 'ruler' ); ?><?php esc_html_e( 'Theoretical (calculated)', 'sm-marketplace' ); ?></span></label>
					<label><input type="radio" name="weight_source" value="actual" data-sm-weight-source><span><?php sm_icon( 'scale' ); ?><?php esc_html_e( 'Actual (weighed)', 'sm-marketplace' ); ?></span></label>
				</div>
				<div class="sm-field" data-sm-actual-weight hidden>
					<label for="p-weight"><?php esc_html_e( 'Measured weight (kg)', 'sm-marketplace' ); ?></label>
					<input id="p-weight" name="actual_weight" type="number" inputmode="decimal" min="0" step="0.1" dir="ltr">
				</div>
			</fieldset>
		</div>

		<div data-sm-for-market="buildings" hidden>
			<div class="sm-segment">
				<label><input type="radio" name="mode" value="assembled" checked data-sm-mode><span><?php esc_html_e( 'Assembled', 'sm-marketplace' ); ?></span></label>
				<label><input type="radio" name="mode" value="dismantled" data-sm-mode><span><?php esc_html_e( 'Dismantled', 'sm-marketplace' ); ?></span></label>
			</div>
			<div class="sm-field sm-field--row sm-field--row3">
				<div><label for="b-l"><?php esc_html_e( 'Length (m)', 'sm-marketplace' ); ?></label><input id="b-l" name="length" type="number" inputmode="decimal" dir="ltr"></div>
				<div><label for="b-w"><?php esc_html_e( 'Width (m)', 'sm-marketplace' ); ?></label><input id="b-w" name="width" type="number" inputmode="decimal" dir="ltr"></div>
				<div><label for="b-h"><?php esc_html_e( 'Height (m)', 'sm-marketplace' ); ?></label><input id="b-h" name="height" type="number" inputmode="decimal" dir="ltr"></div>
			</div>
			<div class="sm-field sm-field--row">
				<div><label for="b-p"><?php esc_html_e( 'Number of portals', 'sm-marketplace' ); ?></label><input id="b-p" name="portals" type="number" inputmode="numeric" dir="ltr"></div>
				<div><label for="b-roof"><?php esc_html_e( 'Roof covering', 'sm-marketplace' ); ?></label><input id="b-roof" name="roof_cover" dir="auto"></div>
			</div>
			<div data-sm-inventory hidden>
				<p class="sm-field__legend"><?php esc_html_e( 'Element inventory', 'sm-marketplace' ); ?></p>
				<div class="sm-table-wrap">
					<table class="sm-table sm-table--edit">
						<thead><tr><th><?php esc_html_e( 'Element', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Profile', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Quantity', 'sm-marketplace' ); ?></th><th><?php esc_html_e( 'Length (m)', 'sm-marketplace' ); ?></th><th></th></tr></thead>
						<tbody data-sm-rows>
							<tr>
								<td><input name="el_type[]" aria-label="<?php esc_attr_e( 'Element', 'sm-marketplace' ); ?>" dir="auto"></td>
								<td><input name="el_profile[]" list="sm-profiles" aria-label="<?php esc_attr_e( 'Profile', 'sm-marketplace' ); ?>" dir="ltr"></td>
								<td><input name="el_qty[]" type="number" inputmode="numeric" aria-label="<?php esc_attr_e( 'Quantity', 'sm-marketplace' ); ?>" dir="ltr"></td>
								<td><input name="el_len[]" type="number" inputmode="decimal" aria-label="<?php esc_attr_e( 'Length (m)', 'sm-marketplace' ); ?>" dir="ltr"></td>
								<td><button type="button" class="sm-icon-btn" data-sm-row-remove><?php sm_icon( 'trash', '', __( 'Remove row', 'sm-marketplace' ) ); ?></button></td>
							</tr>
						</tbody>
					</table>
				</div>
				<button type="button" class="sm-btn sm-btn--ghost" data-sm-row-add><?php sm_icon( 'plus' ); ?><?php esc_html_e( 'Add an element', 'sm-marketplace' ); ?></button>
			</div>
		</div>

		<div class="sm-field">
			<label for="p-title-fr"><?php esc_html_e( 'Title in French', 'sm-marketplace' ); ?></label>
			<input id="p-title-fr" name="title[fr]" lang="fr" dir="ltr">
		</div>
		<div class="sm-field">
			<label for="p-title-ar"><?php esc_html_e( 'Title in Arabic (optional)', 'sm-marketplace' ); ?></label>
			<input id="p-title-ar" name="title[ar]" lang="ar" dir="rtl">
		</div>
		<div class="sm-field">
			<label for="p-desc"><?php esc_html_e( 'Description and technical notes', 'sm-marketplace' ); ?></label>
			<textarea id="p-desc" name="description" rows="4" dir="auto"></textarea>
		</div>
	</section>

	<section class="sm-panel sm-step" data-step="2" hidden>
		<h2 class="sm-panel__title"><?php esc_html_e( 'Photos and documents', 'sm-marketplace' ); ?></h2>
		<label class="sm-upload" data-sm-upload>
			<input type="file" name="photos[]" accept="image/*" capture="environment" multiple>
			<?php sm_icon( 'camera', 'sm-upload__icon' ); ?>
			<strong><?php esc_html_e( 'Take or add photos', 'sm-marketplace' ); ?></strong>
			<small><?php esc_html_e( 'Up to 10 photos. Show the section, the length marks and the condition.', 'sm-marketplace' ); ?></small>
		</label>
		<ul class="sm-upload__previews" data-sm-previews></ul>
		<label class="sm-upload sm-upload--small">
			<input type="file" name="documents[]" accept=".pdf,.dwg,.dxf,image/*" multiple>
			<?php sm_icon( 'file', 'sm-upload__icon' ); ?>
			<strong><?php esc_html_e( 'Add technical documents (optional)', 'sm-marketplace' ); ?></strong>
			<small><?php esc_html_e( 'Material certificates, drawings, inventory lists (PDF, DWG).', 'sm-marketplace' ); ?></small>
		</label>
		<p class="sm-callout sm-callout--ai"><?php sm_icon( 'ai' ); ?><span><?php esc_html_e( 'Soon: the AI assistant will suggest the category and description from your photos. You always confirm the technical data.', 'sm-marketplace' ); ?></span></p>
	</section>

	<section class="sm-panel sm-step" data-step="3" hidden>
		<h2 class="sm-panel__title"><?php esc_html_e( 'Price & location', 'sm-marketplace' ); ?></h2>
		<div class="sm-field sm-field--row">
			<div>
				<label for="p-price"><span data-sm-for-market="products reuse"><?php esc_html_e( 'Price per kg (DA)', 'sm-marketplace' ); ?></span><span data-sm-for-market="buildings" hidden><?php esc_html_e( 'Asking price (DA)', 'sm-marketplace' ); ?></span></label>
				<div class="sm-input-group"><input id="p-price" name="price" type="number" inputmode="decimal" min="0" dir="ltr"><span class="sm-input-group__addon"><?php echo esc_html( sm_currency_label() ); ?></span></div>
			</div>
			<div>
				<label for="p-wilaya"><?php esc_html_e( 'Wilaya', 'sm-marketplace' ); ?></label>
				<select id="p-wilaya" name="wilaya"><?php foreach ( sm_theme_wilayas() as $code => $name ) : ?><option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $code . ' — ' . sm_t( $name ) ); ?></option><?php endforeach; ?></select>
			</div>
		</div>
		<div class="sm-summary">
			<p class="sm-summary__title"><?php esc_html_e( 'Summary shown to buyers', 'sm-marketplace' ); ?></p>
			<?php
			ob_start();
			do_action( 'sm_slot', 'publish.summary', $args['account'] );
			$slot = ob_get_clean();
			if ( $slot ) {
				echo $slot; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the plugin.
			} else {
				?>
				<dl class="sm-spec sm-spec--compact">
					<div><dt><?php esc_html_e( 'Total weight', 'sm-marketplace' ); ?></dt><dd>—</dd></div>
					<div><dt><?php esc_html_e( 'Total price', 'sm-marketplace' ); ?></dt><dd>—</dd></div>
				</dl>
				<p class="sm-help"><?php esc_html_e( 'Weight and total price are calculated by the weight engine (Phase 3).', 'sm-marketplace' ); ?></p>
			<?php } ?>
		</div>
	</section>

	<div class="sm-stepper__nav">
		<button type="button" class="sm-btn sm-btn--ghost" data-sm-prev hidden><?php sm_icon( 'chevron-start' ); ?><?php esc_html_e( 'Back', 'sm-marketplace' ); ?></button>
		<button type="button" class="sm-btn sm-btn--ghost" name="save_draft"><?php esc_html_e( 'Save draft', 'sm-marketplace' ); ?></button>
		<button type="button" class="sm-btn sm-btn--primary" data-sm-next><?php esc_html_e( 'Continue', 'sm-marketplace' ); ?><?php sm_icon( 'chevron-end' ); ?></button>
		<button type="submit" class="sm-btn sm-btn--accent" data-sm-submit hidden><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Publish', 'sm-marketplace' ); ?></button>
	</div>
</form>
