<?php
/**
 * Template Name: SM — Company directory
 *
 * Companies and professionals (0.13.0). Filters: ?class=company|professional
 * and ?activity= (type of company or trade). Clients and partner engineers are
 * never listed here.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
$types    = sm_account_types();
$class    = in_array( sm_theme_query_arg( 'class' ), array( 'company', 'professional' ), true ) ? sm_theme_query_arg( 'class' ) : '';
$activity = sanitize_key( (string) sm_theme_query_arg( 'activity' ) );
$base     = sm_page_url( 'companies' );
$pills    = $class ? (array) ( $types['classes'][ $class ]['activities'] ?? array() ) : array();
$list     = sm_theme_companies( array_filter( array( 'class' => $class, 'activity' => $activity ) ) );
if ( $activity ) {
	$list = array_values( array_filter( (array) $list, static fn( $c ) => in_array( $activity, (array) $c['activities'], true ) ) );
}
?>
<section class="sm-pagehead">
	<div class="sm-container">
		<?php sm_breadcrumb( array( array( __( 'Company directory', 'sm-marketplace' ), '' ) ) ); ?>
		<h1 class="sm-pagehead__title"><?php esc_html_e( 'Companies and professionals', 'sm-marketplace' ); ?></h1>
		<p class="sm-pagehead__intro"><?php esc_html_e( 'Steel construction companies, manufacturers, distributors, recyclers and professionals of steel structures.', 'sm-marketplace' ); ?></p>
		<ul class="sm-catpills">
			<li><a href="<?php echo esc_url( $base ); ?>" <?php echo '' === $class ? 'aria-current="true"' : ''; ?>><?php esc_html_e( 'All', 'sm-marketplace' ); ?></a></li>
			<li><a href="<?php echo esc_url( add_query_arg( 'class', 'company', $base ) ); ?>" <?php echo 'company' === $class && ! $activity ? 'aria-current="true"' : ''; ?>><?php sm_icon( 'company' ); ?><?php esc_html_e( 'Companies', 'sm-marketplace' ); ?></a></li>
			<li><a href="<?php echo esc_url( add_query_arg( 'class', 'professional', $base ) ); ?>" <?php echo 'professional' === $class && ! $activity ? 'aria-current="true"' : ''; ?>><?php sm_icon( 'wrench' ); ?><?php esc_html_e( 'Professionals', 'sm-marketplace' ); ?></a></li>
		</ul>
		<?php if ( $pills ) : ?>
			<ul class="sm-catpills sm-catpills--sub">
				<?php foreach ( $pills as $code => $label ) : ?>
					<li><a href="<?php echo esc_url( add_query_arg( array( 'class' => $class, 'activity' => $code ), $base ) ); ?>" <?php echo $code === $activity ? 'aria-current="true"' : ''; ?>><?php echo esc_html( $label ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
<div class="sm-container sm-section">
	<?php if ( $list ) : ?>
		<div class="sm-grid sm-grid--companies">
			<?php foreach ( $list as $company ) : ?>
				<?php sm_component( 'card-company', array( 'company' => $company ) ); ?>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<?php sm_component( 'empty-state', array( 'icon' => 'company', 'title' => __( 'Nobody here yet', 'sm-marketplace' ), 'action_label' => __( 'See the whole directory', 'sm-marketplace' ), 'action_url' => $base ) ); ?>
	<?php endif; ?>
</div>
<?php
get_footer();
