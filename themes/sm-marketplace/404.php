<?php
/**
 * 404 page.
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<div class="sm-container sm-section">
	<?php
	sm_component(
		'empty-state',
		array(
			'icon'         => 'alert',
			'title'        => __( 'Page not found', 'sm-marketplace' ),
			'text'         => __( 'The page you are looking for has moved or no longer exists.', 'sm-marketplace' ),
			'action_label' => __( 'Search the marketplace', 'sm-marketplace' ),
			'action_url'   => sm_page_url( 'search' ),
		)
	);
	?>
</div>
<?php
get_footer();
