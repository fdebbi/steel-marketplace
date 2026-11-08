<?php
/**
 * Services board (fabrication & site work, or engineering): category pills,
 * filters, service cards. Data from sm-services; demo cards until a service exists.
 *
 * @var array $args { kind: string, page: string }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$kind     = $args['kind'];
$page_key = $args['page'];
$set      = (array) sm_theme_data( 'services_settings' );
$cats     = (array) ( $set['kinds'][ $kind ]['categories'] ?? array() );
$current  = sm_theme_query_arg( 'category' );
$base     = sm_page_url( $page_key );
$services = sm_theme_services(
	array(
		'kind'     => $kind,
		'category' => $current,
		'wilaya'   => sm_theme_query_arg( 'wilaya' ),
		'q'        => sm_theme_query_arg( 'q' ),
		'per_page' => 48,
	)
);
$wilayas  = sm_theme_wilayas();
?>
<?php if ( $cats ) : ?>
	<ul class="sm-catpills" aria-label="<?php esc_attr_e( 'Services', 'sm-marketplace' ); ?>">
		<li><a href="<?php echo esc_url( $base ); ?>"<?php echo '' === $current ? ' aria-current="true"' : ''; ?>><?php esc_html_e( 'All', 'sm-marketplace' ); ?></a></li>
		<?php foreach ( $cats as $code => $label ) : ?>
			<li><a href="<?php echo esc_url( add_query_arg( 'category', $code, $base ) ); ?>"<?php echo $code === $current ? ' aria-current="true"' : ''; ?>><?php echo esc_html( sm_t( $label ) ); ?></a></li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
<form class="smq-filters sms-filters" method="get" role="search" aria-label="<?php esc_attr_e( 'Filter the services', 'sm-marketplace' ); ?>">
	<?php if ( $current ) : ?><input type="hidden" name="category" value="<?php echo esc_attr( $current ); ?>"><?php endif; ?>
	<div class="sm-field">
		<label for="sms-q"><?php esc_html_e( 'Keyword', 'sm-marketplace' ); ?></label>
		<input id="sms-q" name="q" type="search" value="<?php echo esc_attr( sm_theme_query_arg( 'q' ) ); ?>" placeholder="<?php echo esc_attr( 'engineering' === $kind ? __( 'e.g. calculation note, inspection', 'sm-marketplace' ) : __( 'e.g. welding, galvanizing, hangar', 'sm-marketplace' ) ); ?>">
	</div>
	<div class="sm-field">
		<label for="sms-wilaya"><?php esc_html_e( 'Wilaya of the project', 'sm-marketplace' ); ?></label>
		<select id="sms-wilaya" name="wilaya"><option value=""><?php esc_html_e( 'All wilayas', 'sm-marketplace' ); ?></option><?php foreach ( $wilayas as $c => $n ) : ?><option value="<?php echo esc_attr( $c ); ?>" <?php selected( sm_theme_query_arg( 'wilaya' ), (string) $c ); ?>><?php echo esc_html( $c . ' — ' . sm_t( $n ) ); ?></option><?php endforeach; ?></select>
	</div>
	<button class="sm-btn sm-btn--primary" type="submit"><?php sm_icon( 'search' ); ?><?php esc_html_e( 'Filter', 'sm-marketplace' ); ?></button>
</form>
<?php if ( ! $services ) : ?>
	<?php sm_component( 'empty-state', array( 'icon' => 'engineering' === $kind ? 'hard-hat' : 'wrench', 'title' => __( 'No service matches these filters', 'sm-marketplace' ), 'text' => __( 'Widen the search, or publish a request for quotation.', 'sm-marketplace' ), 'action_label' => __( 'See all services', 'sm-marketplace' ), 'action_url' => $base ) ); ?>
<?php else : ?>
	<div class="sm-grid sm-grid--grid sm-grid--3">
		<?php foreach ( $services as $service ) : ?>
			<?php sm_component( 'card-service', array( 'service' => $service ) ); ?>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
