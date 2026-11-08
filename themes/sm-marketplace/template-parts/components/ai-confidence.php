<?php
/**
 * Confidence of an AI suggestion (bar + words).
 *
 * @var array $args { value: float 0..1 }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$v     = max( 0, min( 1, (float) ( $args['value'] ?? 0 ) ) );
$level = $v >= 0.7 ? 'high' : ( $v >= 0.4 ? 'mid' : 'low' );
$words = array( 'high' => __( 'Likely', 'sm-marketplace' ), 'mid' => __( 'Possible', 'sm-marketplace' ), 'low' => __( 'Uncertain', 'sm-marketplace' ) );
?>
<span class="smai-conf smai-conf--<?php echo esc_attr( $level ); ?>">
	<span class="smai-conf__bar" aria-hidden="true"><span style="inline-size:<?php echo esc_attr( (int) round( $v * 100 ) ); ?>%"></span></span>
	<span class="smai-conf__text"><?php echo esc_html( $words[ $level ] . ' · ' . (int) round( $v * 100 ) . ' %' ); ?></span>
</span>
