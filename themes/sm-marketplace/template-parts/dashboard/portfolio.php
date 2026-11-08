<?php
/**
 * "References": completed projects shown on the company page.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$items = sm_theme_data( 'portfolio' );
if ( ! is_array( $items ) ) {
	sm_component( 'empty-state', array( 'icon' => 'layers', 'title' => __( 'References', 'sm-marketplace' ), 'text' => __( 'This module is not active yet.', 'sm-marketplace' ) ) );
	return;
}
$edit_id = absint( sm_theme_query_arg( 'edit', '' ) );
$adding  = $edit_id || sm_theme_query_arg( 'new', '' );
$current = array();
foreach ( $items as $p ) {
	if ( (int) $p['id'] === $edit_id ) {
		$current = $p;
	}
}
if ( $adding ) :
	$old   = sm_theme_old_all();
	$val   = static function ( $key, $lang = '' ) use ( $old, $current ) {
		if ( $old ) {
			return (string) ( $lang ? ( $old[ $key ][ $lang ] ?? '' ) : ( $old[ $key ] ?? '' ) );
		}
		if ( $lang ) {
			return (string) ( $current[ $key ][ $lang ] ?? '' );
		}
		$v = $current[ $key ] ?? '';
		return is_array( $v ) ? (string) reset( $v ) : (string) $v;
	};
	$years = range( (int) gmdate( 'Y' ) + 1, 1970 );
	?>
	<?php sm_theme_form_open( 'portfolio_save', array( 'class' => 'sm-form sm-panel sms-pform', 'enctype' => 'multipart/form-data' ) ); ?>
		<input type="hidden" name="portfolio_id" value="<?php echo esc_attr( $edit_id ); ?>">
		<h2 class="sm-panel__title"><?php echo esc_html( $edit_id ? __( 'Edit the reference', 'sm-marketplace' ) : __( 'New reference', 'sm-marketplace' ) ); ?></h2>
		<div class="sm-field-grid">
			<div class="sm-field">
				<label for="pf-title-fr"><?php esc_html_e( 'Project title in French', 'sm-marketplace' ); ?></label>
				<input id="pf-title-fr" name="title[fr]" lang="fr" dir="ltr" maxlength="140" value="<?php echo esc_attr( $val( 'title', 'fr' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. Storage hall 40 × 20 m', 'sm-marketplace' ); ?>"<?php sm_theme_aria( 'title' ); ?>>
				<?php sm_theme_error( 'title' ); ?>
			</div>
			<div class="sm-field">
				<label for="pf-title-ar"><?php esc_html_e( 'Project title in Arabic', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
				<input id="pf-title-ar" name="title[ar]" lang="ar" dir="rtl" maxlength="140" value="<?php echo esc_attr( $val( 'title', 'ar' ) ); ?>">
			</div>
		</div>
		<div class="sm-field-grid">
			<div class="sm-field">
				<label for="pf-role"><?php esc_html_e( 'Your role', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
				<input id="pf-role" name="role" dir="auto" maxlength="120" value="<?php echo esc_attr( $val( 'role' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. Design and calculation notes', 'sm-marketplace' ); ?>">
			</div>
			<div class="sm-field">
				<label for="pf-year"><?php esc_html_e( 'Year', 'sm-marketplace' ); ?></label>
				<select id="pf-year" name="year"<?php sm_theme_aria( 'year' ); ?>>
					<option value=""><?php esc_html_e( '—', 'sm-marketplace' ); ?></option>
					<?php foreach ( $years as $y ) : ?><option value="<?php echo esc_attr( $y ); ?>" <?php selected( $val( 'year' ), (string) $y ); ?>><?php echo esc_html( $y ); ?></option><?php endforeach; ?>
				</select>
				<?php sm_theme_error( 'year' ); ?>
			</div>
			<div class="sm-field">
				<label for="pf-wilaya"><?php esc_html_e( 'Wilaya', 'sm-marketplace' ); ?></label>
				<select id="pf-wilaya" name="wilaya">
					<option value=""><?php esc_html_e( '—', 'sm-marketplace' ); ?></option>
					<?php foreach ( sm_theme_wilayas() as $code => $name ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( $val( 'wilaya' ), (string) $code ); ?>><?php echo esc_html( $code . ' ' . sm_t( $name ) ); ?></option><?php endforeach; ?>
				</select>
			</div>
		</div>
		<div class="sm-field">
			<label for="pf-desc"><?php esc_html_e( 'Short description', 'sm-marketplace' ); ?> <small class="sm-optional"><?php esc_html_e( 'optional', 'sm-marketplace' ); ?></small></label>
			<textarea id="pf-desc" name="description" rows="4" dir="auto" maxlength="1500"<?php sm_theme_aria( 'description' ); ?>><?php echo esc_textarea( $val( 'description' ) ); ?></textarea>
			<?php sm_theme_error( 'description' ); ?>
		</div>
		<label class="sm-upload" data-sm-upload>
			<input type="file" name="photo[]" accept="image/jpeg,image/png,image/webp">
			<?php sm_icon( 'camera', 'sm-upload__icon' ); ?>
			<strong><?php echo esc_html( ! empty( $current['image'] ) ? __( 'Replace the photo', 'sm-marketplace' ) : __( 'Add a photo', 'sm-marketplace' ) ); ?></strong>
			<small><?php esc_html_e( 'One photo of the finished project (JPG, PNG, WebP).', 'sm-marketplace' ); ?></small>
		</label>
		<ul class="sm-upload__previews" data-sm-previews></ul>
		<div class="smp-actions">
			<a class="sm-btn sm-btn--ghost" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'portfolio' ) ) ); ?>"><?php esc_html_e( 'Cancel', 'sm-marketplace' ); ?></a>
			<button class="sm-btn sm-btn--accent" type="submit"><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Save the reference', 'sm-marketplace' ); ?></button>
		</div>
	</form>
	<?php
	return;
endif;
$new_url = sm_page_url( 'account', array( 'view' => 'portfolio', 'new' => 1 ) );
?>
<div class="sm-toolbar">
	<p class="sm-muted"><?php esc_html_e( 'Completed projects shown on your public page. Clients trust what they can see.', 'sm-marketplace' ); ?></p>
	<a class="sm-btn sm-btn--accent" href="<?php echo esc_url( $new_url ); ?>"><?php sm_icon( 'plus' ); ?><?php esc_html_e( 'Add a reference', 'sm-marketplace' ); ?></a>
</div>
<?php if ( ! $items ) : ?>
	<?php sm_component( 'empty-state', array( 'icon' => 'layers', 'title' => __( 'No reference yet', 'sm-marketplace' ), 'text' => __( 'Add your completed projects with a photo, the year and your role.', 'sm-marketplace' ), 'action_label' => __( 'Add a reference', 'sm-marketplace' ), 'action_url' => $new_url ) ); ?>
<?php else : ?>
	<?php sm_component( 'portfolio-grid', array( 'items' => $items, 'actions' => true ) ); ?>
<?php endif; ?>
