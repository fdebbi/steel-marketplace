<?php
/**
 * Partner engineer: files and plans of all the missions (client documents,
 * platform documents, deliverables, invoices), grouped by mission.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$d = sm_theme_data( 'engineer_account', array( 'section' => 'files' ) );
if ( ! is_array( $d ) || 'approved' !== $d['status'] ) {
	sm_component( 'empty-state', array( 'icon' => 'file', 'title' => __( 'Files open after the approval of your application', 'sm-marketplace' ) ) );
	return;
}
if ( ! $d['files'] ) {
	sm_component( 'empty-state', array( 'icon' => 'file', 'title' => __( 'No file yet', 'sm-marketplace' ), 'text' => __( 'The plans and documents of your missions are gathered here.', 'sm-marketplace' ) ) );
	return;
}
$kinds = array(
	'brief'       => __( 'Client', 'sm-marketplace' ),
	'platform'    => __( 'Platform', 'sm-marketplace' ),
	'deliverable' => __( 'Deliverable', 'sm-marketplace' ),
	'invoice'     => __( 'Invoice', 'sm-marketplace' ),
);
foreach ( $d['files'] as $group ) :
	?>
	<section class="sm-panel">
		<div class="sm-panel__head">
			<h2 class="sm-panel__title" dir="auto"><?php echo esc_html( $group['title'] ); ?> <small dir="ltr"><?php echo esc_html( $group['ref'] ); ?></small></h2>
			<a class="sm-link" href="<?php echo esc_url( $group['url'] ); ?>"><?php esc_html_e( 'Open the mission', 'sm-marketplace' ); ?><?php sm_icon( 'arrow-end' ); ?></a>
		</div>
		<ul class="sme-files__list" role="list">
			<?php foreach ( $group['files'] as $f ) : ?>
				<li>
					<a href="<?php echo esc_url( $f['url'] ); ?>" target="_blank" rel="noopener"><?php sm_icon( $f['image'] ? 'image' : 'file' ); ?><span dir="auto"><?php echo esc_html( $f['name'] ); ?></span></a>
					<span class="sm-status sm-status--muted"><?php echo esc_html( $kinds[ $f['kind'] ] ?? $f['kind'] ); ?></span>
					<small><?php echo esc_html( $f['size'] ); ?> · <?php echo esc_html( sm_ago( $f['date'] ) ); ?></small>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php endforeach; ?>
