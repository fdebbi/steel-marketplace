<?php
/**
 * Verification progress: contact verified -> documents verified.
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$account = $args['account'];
$level   = (int) $account['company']['verification'];
$class   = sm_account_class( $account );
$target  = 'engineer' === $class ? 3 : 2;
$second  = array(
	'company'      => array( __( 'Verified business', 'sm-marketplace' ), __( 'RC, NIF and NIS checked', 'sm-marketplace' ) ),
	'professional' => array( __( 'Verified professional', 'sm-marketplace' ), __( 'Craftsman or auto-entrepreneur card checked', 'sm-marketplace' ) ),
	'client'       => array( __( 'Verified identity', 'sm-marketplace' ), __( 'Identity document checked', 'sm-marketplace' ) ),
	'engineer'     => array( __( 'Verified engineer', 'sm-marketplace' ), __( 'Diploma and approval checked', 'sm-marketplace' ) ),
);
$steps   = array(
	array( 1, __( 'Contact verified', 'sm-marketplace' ), __( '6-digit code', 'sm-marketplace' ), 'verify-phone' ),
	array( $target, $second[ $class ][0] ?? $second['company'][0], $second[ $class ][1] ?? $second['company'][1], 'verification' ),
);
?>
<ol class="sm-vsteps">
	<?php foreach ( $steps as list( $lvl, $label, $hint, $view ) ) : ?>
		<?php $done = $level >= $lvl; ?>
		<li class="<?php echo $done ? 'is-done' : ''; ?>">
			<?php sm_icon( $done ? 'verified' : 'shield' ); ?>
			<div>
				<strong><?php echo esc_html( $label ); ?></strong>
				<small><?php echo esc_html( $hint ); ?></small>
			</div>
			<?php if ( ! $done && ! empty( $account['real'] ) ) : ?>
				<a class="sm-link" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => $view ) ) ); ?>"><?php esc_html_e( 'Do it', 'sm-marketplace' ); ?><?php sm_icon( 'arrow-end' ); ?></a>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ol>
