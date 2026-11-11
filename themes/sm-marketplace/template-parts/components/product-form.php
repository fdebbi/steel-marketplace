<?php
/**
 * Publication form of the steel listing engine (sm-products, and sm-reuse for
 * the reuse market).
 *
 * 1. market and category (links, no JavaScript needed); 2. technical part, condition, quantity and
 * price with a live summary (product-tech); 3. title and description;
 * 4. photos and technical sheet; 5. location; draft or submit.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$markets = (array) sm_theme_data( 'product_markets' );
$cats    = (array) sm_theme_data( 'product_categories' );
$edit_id = absint( sm_theme_query_arg( 'edit', '' ) );
$edit    = $edit_id ? sm_theme_data( 'product_form', array( 'id' => $edit_id ) ) : null;
$old     = sm_theme_old_all();
$state   = $edit ? $edit['values'] : array();
if ( $old ) {
	$state = array_merge( $state, $old );
}
$get_cat = sanitize_key( sm_theme_query_arg( 'cat', '' ) );
if ( $get_cat && isset( $cats[ $get_cat ] ) ) {
	if ( ( $state['category'] ?? '' ) !== $get_cat ) {
		$state = array_merge( array_intersect_key( $state, array_flip( array( 'product_id', 'title', 'description', 'wilaya', 'keep_photos', 'keep_sheets' ) ) ), array( 'category' => $get_cat ) );
	}
}
$cat_slug = (string) ( $state['category'] ?? '' );
$cat      = $cats[ $cat_slug ] ?? null;
// Market: the one of the chosen category, else the one asked in the address, else the first.
$market = $cat ? $cat['market'] : sanitize_key( sm_theme_query_arg( 'market', '' ) );
if ( ! isset( $markets[ $market ] ) ) {
	$market = (string) array_key_first( $markets );
}
$mk        = $markets[ $market ] ?? array( 'allowed' => true, 'conditions' => array(), 'fields' => array() );
$mcats     = array_filter( $cats, static fn( $c ) => ( $c['market'] ?? 'products' ) === $market );
$is_reuse  = 'reuse' === $market;
$is_build  = 'buildings' === $market;
$ps       = (array) sm_theme_data( 'product_settings' );
$base_url = sm_page_url( 'account', array( 'view' => 'publish' ) );

if ( $edit_id && ! $edit ) {
	echo '<div class="sm-notice sm-notice--warn">' . sm_get_icon( 'lock' ) . '<p>' . esc_html__( 'This product cannot be edited from your account.', 'sm-marketplace' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	return;
}
wp_enqueue_script( 'sm-product-form', SM_THEME_URI . '/assets/js/product-form.js', array(), sm_theme_asset_version( 'assets/js/product-form.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
?>
<?php if ( $edit && 'rejected' === $edit['status'] && $edit['reject_reason'] ) : ?>
	<div class="sm-notice sm-notice--warn"><?php sm_icon( 'alert' ); ?><p><strong><?php esc_html_e( 'Changes requested by the platform team:', 'sm-marketplace' ); ?></strong> <?php echo esc_html( $edit['reject_reason'] ); ?></p></div>
<?php elseif ( $edit && 'published' === $edit['status'] && ! empty( $ps['moderation'] ) ) : ?>
	<div class="sm-notice sm-notice--info"><?php sm_icon( 'info' ); ?><p><?php esc_html_e( 'This product is online. After your changes it will be checked again before it reappears.', 'sm-marketplace' ); ?></p></div>
<?php endif; ?>

<?php if ( ! $edit && ! $old ) : ?>
	<?php sm_component( 'ai-callout', array( 'tool' => 'listing', 'text' => __( 'Save time: send photos to the AI assistant, it prepares the category, the titles in French and Arabic and the description.', 'sm-marketplace' ), 'button' => __( 'Prepare with the AI', 'sm-marketplace' ) ) ); ?>
<?php endif; ?>
<section class="sm-panel smp-step">
	<h2 class="smw-step__title"><span class="smw-step__n">1</span><?php echo esc_html( count( $markets ) > 1 ? __( 'Market and category', 'sm-marketplace' ) : __( 'Category', 'sm-marketplace' ) ); ?></h2>
	<?php if ( count( $markets ) > 1 && ! $edit ) : ?>
		<ul class="smp-markets" role="list" aria-label="<?php esc_attr_e( 'Market', 'sm-marketplace' ); ?>">
			<?php foreach ( $markets as $key => $m ) : ?>
				<?php $cur = $key === $market; ?>
				<li>
					<a class="smp-market<?php echo $cur ? ' is-current' : ''; ?><?php echo $m['allowed'] ? '' : ' is-locked'; ?>" href="<?php echo esc_url( add_query_arg( 'market', $key, $base_url ) ); ?>"<?php echo $cur ? ' aria-current="true"' : ''; ?>>
						<span class="smp-market__icon"><?php sm_icon( $m['icon'] ? $m['icon'] : 'layers' ); ?></span>
						<span class="smp-market__text">
							<strong><?php echo esc_html( sm_t( $m['label'] ) ); ?></strong>
							<small><?php echo esc_html( sm_t( $m['intro'] ) ); ?></small>
						</span>
						<?php if ( ! $m['allowed'] ) : ?><?php sm_icon( 'lock', 'smp-market__lock' ); ?><?php endif; ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php if ( ! $mk['allowed'] ) : ?>
		<div class="sm-notice sm-notice--warn"><?php sm_icon( 'lock' ); ?><p><?php echo esc_html( $mk['reason'] ); ?></p></div>
	<?php endif; ?>
	<ul class="smp-cats" role="list">
		<?php foreach ( $mcats as $slug => $c ) : ?>
			<li>
				<a class="smp-cat<?php echo $slug === $cat_slug ? ' is-current' : ''; ?>" href="<?php echo esc_url( add_query_arg( array_filter( array( 'market' => $market, 'cat' => $slug, 'edit' => $edit_id ? $edit_id : null ) ), $base_url ) ); ?>"<?php echo $slug === $cat_slug ? ' aria-current="true"' : ''; ?>>
					<?php sm_icon( $c['icon'] ); ?><span><?php echo esc_html( sm_t( $c['name'] ) ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</section>

<?php if ( ! $cat ) : ?>
	<p class="smw-result__empty"><?php echo esc_html( $is_reuse ? __( 'Choose the kind of lot to see its form.', 'sm-marketplace' ) : __( 'Choose the category of the product to see its form.', 'sm-marketplace' ) ); ?></p>
	<?php return; ?>
<?php endif; ?>

<?php sm_theme_form_open( 'product_save', array( 'class' => 'sm-form smp-form', 'enctype' => 'multipart/form-data', 'data-smp' => '', 'data-smp-partial' => add_query_arg( 'smp_partial', '1', $base_url ) ) ); ?>
	<input type="hidden" name="category" value="<?php echo esc_attr( $cat_slug ); ?>">
	<input type="hidden" name="product_id" value="<?php echo esc_attr( (string) ( $state['product_id'] ?? '' ) ); ?>">

	<section class="sm-panel smp-step">
		<h2 class="smw-step__title"><span class="smw-step__n">2</span><?php echo esc_html( sm_t( $cat['name'] ) ); ?> — <?php esc_html_e( 'technical data, quantity and price', 'sm-marketplace' ); ?></h2>
		<?php sm_component( 'product-tech', array( 'state' => $state, 'category' => $cat, 'market' => $mk ) ); ?>
	</section>

	<section class="sm-panel smp-step">
		<h2 class="smw-step__title"><span class="smw-step__n">3</span><?php esc_html_e( 'Title and description', 'sm-marketplace' ); ?></h2>
		<div class="sm-field">
			<label for="smp-title-fr"><?php esc_html_e( 'Title in French', 'sm-marketplace' ); ?></label>
			<input id="smp-title-fr" name="title[fr]" lang="fr" dir="ltr" maxlength="140" value="<?php echo esc_attr( (string) ( $state['title']['fr'] ?? '' ) ); ?>" placeholder="<?php echo esc_attr( $is_build ? __( 'e.g. Industrial hangar 40 × 20 m, assembled', 'sm-marketplace' ) : ( $is_reuse ? __( 'e.g. IPE and HEA offcuts, 2 to 4 m, good condition', 'sm-marketplace' ) : __( 'e.g. IPE 200 S275JR new, 12 m bars', 'sm-marketplace' ) ) ); ?>"<?php sm_theme_aria( 'title' ); ?>>
			<?php sm_theme_error( 'title' ); ?>
		</div>
		<div class="sm-field">
			<label for="smp-title-ar"><?php esc_html_e( 'Title in Arabic', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
			<input id="smp-title-ar" name="title[ar]" lang="ar" dir="rtl" maxlength="140" value="<?php echo esc_attr( (string) ( $state['title']['ar'] ?? '' ) ); ?>">
		</div>
		<div class="sm-field">
			<label for="smp-desc"><?php esc_html_e( 'Description and technical notes', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
			<textarea id="smp-desc" name="description" rows="5" dir="auto" maxlength="4000"><?php echo esc_textarea( (string) ( $state['description'] ?? '' ) ); ?></textarea>
			<?php sm_theme_error( 'description' ); ?>
		</div>
	</section>

	<section class="sm-panel smp-step">
		<h2 class="smw-step__title"><span class="smw-step__n">4</span><?php esc_html_e( 'Photos and technical sheet', 'sm-marketplace' ); ?></h2>
		<?php $kept = array_filter( array_map( 'absint', (array) ( $state['keep_photos'] ?? array() ) ) ); ?>
		<?php sm_saved_files( array( 'ids' => $kept, 'field' => 'photos[]', 'keep' => 'keep_photos[]' ) ); ?>
		<label class="sm-upload" data-sm-upload>
			<input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" capture="environment" multiple>
			<?php sm_icon( 'camera', 'sm-upload__icon' ); ?>
			<strong><?php esc_html_e( 'Take or add photos', 'sm-marketplace' ); ?></strong>
			<small>
				<?php
				/* translators: 1: number of photos, 2: size in MB. */
				$photo_help = $is_build ? __( 'Up to %1$d photos (JPG, PNG, WebP, %2$d MB each). Show the whole building, the frames, the connections and the roof.', 'sm-marketplace' ) : ( $is_reuse ? __( 'Up to %1$d photos (JPG, PNG, WebP, %2$d MB each). Show the whole lot, the condition of the surfaces and the ends.', 'sm-marketplace' ) : __( 'Up to %1$d photos (JPG, PNG, WebP, %2$d MB each). Show the section, the markings and the stock.', 'sm-marketplace' ) ); // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
				echo esc_html( sprintf( $photo_help, (int) ( $ps['max_photos'] ?? 10 ), (int) ( $ps['photo_mb'] ?? 8 ) ) );
				?>
			</small>
		</label>
		<ul class="sm-upload__previews" data-sm-previews></ul>
		<?php sm_theme_error( 'photos' ); ?>

		<?php $sheets = array_filter( array_map( 'absint', (array) ( $state['keep_sheets'] ?? array() ) ) ); ?>
		<?php sm_saved_files( array( 'ids' => $sheets, 'field' => 'sheets[]', 'keep' => 'keep_sheets[]' ) ); ?>
		<label class="sm-upload sm-upload--small">
			<input type="file" name="sheets[]" accept="application/pdf" multiple>
			<?php sm_icon( 'file', 'sm-upload__icon' ); ?>
			<strong><?php esc_html_e( 'Add the technical sheet (PDF)', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></strong>
			<small>
				<?php
				/* translators: %d: size in MB. */
				echo esc_html( sprintf( __( 'Manufacturer data sheet, certificate of inspection (up to 3 PDF, %d MB each). Buyers can download it.', 'sm-marketplace' ), (int) ( $ps['sheet_mb'] ?? 10 ) ) );
				?>
			</small>
		</label>
	</section>

	<section class="sm-panel smp-step">
		<h2 class="smw-step__title"><span class="smw-step__n">5</span><?php esc_html_e( 'Location', 'sm-marketplace' ); ?></h2>
		<?php $wilaya = (string) ( $state['wilaya'] ?? ( $args['account']['company']['wilaya'] ?? '' ) ); ?>
		<div class="sm-field">
			<label for="smp-wilaya"><?php esc_html_e( 'Wilaya where the product is', 'sm-marketplace' ); ?></label>
			<select id="smp-wilaya" name="wilaya"<?php sm_theme_aria( 'wilaya' ); ?>>
				<option value=""><?php esc_html_e( 'Choose…', 'sm-marketplace' ); ?></option>
				<?php foreach ( sm_theme_wilayas() as $code => $name ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( $wilaya, (string) $code ); ?>><?php echo esc_html( $code . ' — ' . sm_t( $name ) ); ?></option><?php endforeach; ?>
			</select>
			<?php sm_theme_error( 'wilaya' ); ?>
		</div>
	</section>

	<div class="smp-actions">
		<button type="submit" class="sm-btn sm-btn--ghost" name="intent" value="draft"><?php esc_html_e( 'Save draft', 'sm-marketplace' ); ?></button>
		<button type="submit" class="sm-btn sm-btn--accent" name="intent" value="submit"><?php sm_icon( 'check' ); ?><?php echo esc_html( ! empty( $ps['moderation'] ) ? __( 'Send for publication', 'sm-marketplace' ) : __( 'Publish', 'sm-marketplace' ) ); ?></button>
	</div>
	<?php if ( ! empty( $ps['moderation'] ) ) : ?>
		<p class="sm-help"><?php esc_html_e( 'The platform team checks each product (usually within a working day) before it is shown to buyers.', 'sm-marketplace' ); ?></p>
	<?php endif; ?>
</form>
