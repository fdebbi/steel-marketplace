<?php
/**
 * Reuse condition on the 5-step scale (1 = new surplus, 5 = scrap).
 *
 * @var array $args { condition: string, compact?: bool }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$code   = $args['condition'] ?? '';
$labels = sm_condition_labels();
$level  = sm_condition_level( $code );
if ( ! $level ) {
	return;
}
?>
<span class="sm-condition sm-condition--<?php echo esc_attr( $level ); ?><?php echo ! empty( $args['compact'] ) ? ' sm-condition--compact' : ''; ?>">
	<span class="sm-condition__scale" aria-hidden="true"><?php for ( $i = 1; $i <= 5; $i++ ) : ?><i class="<?php echo $i <= ( 6 - $level ) ? 'on' : ''; ?>"></i><?php endfor; ?></span>
	<span class="sm-condition__label"><?php echo esc_html( $labels[ $code ] ); ?></span>
</span>
