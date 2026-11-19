<?php
/**
 * Movements of the warehouse (0.48.0): date, what happened, item, change.
 *
 * @var array $args { moves: array[] (kind, label, qty, unit, kg, note, ref, at) }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$kinds = array(
	'add'      => __( 'Added to the stock', 'sm-marketplace' ),
	'sale'     => __( 'Sale accepted', 'sm-marketplace' ),
	'adjust'   => __( 'Correction', 'sm-marketplace' ),
	'publish'  => __( 'Published on the market', 'sm-marketplace' ),
	'withdraw' => __( 'Kept in stock only', 'sm-marketplace' ),
	'price'    => __( 'Price changed', 'sm-marketplace' ),
	'cutting'  => __( 'Offcuts of the optimiser', 'sm-marketplace' ),
);
$units = static function ( $q, $u ) {
	$n = abs( $q );
	if ( 'bar' === $u ) {
		/* translators: %s: number of bars. */
		return sprintf( _n( '%s bar', '%s bars', (int) ceil( $n ), 'sm-marketplace' ), sm_number( $n, fmod( $n, 1 ) ? 2 : 0 ) );
	}
	if ( 'piece' === $u ) {
		/* translators: %s: number of pieces. */
		return sprintf( _n( '%s piece', '%s pieces', (int) ceil( $n ), 'sm-marketplace' ), sm_number( $n ) );
	}
	return '';
};
?>
<ul class="smst-mv">
	<?php foreach ( (array) $args['moves'] as $mv ) : ?>
		<?php
		$q    = (float) $mv['qty'];
		$kg   = (float) $mv['kg'];
		$sign = ( $q < 0 || ( ! $q && $kg < 0 ) ) ? 'neg' : ( ( $q > 0 || $kg > 0 ) ? 'pos' : '' );
		$chg  = trim( ( $q ? ( $q < 0 ? '− ' : '+ ' ) . $units( $q, $mv['unit'] ) : '' ) . ( $kg ? ' (' . ( $kg < 0 ? '− ' : '+ ' ) . sm_number( abs( $kg ), 1 ) . ' kg)' : '' ) );
		?>
		<li>
			<span class="smst-mv__when"><?php echo esc_html( sm_date( substr( $mv['at'], 0, 10 ) ) ); ?></span>
			<span class="smst-mv__what"><b><?php echo esc_html( $kinds[ $mv['kind'] ] ?? $mv['kind'] ); ?></b><?php if ( $mv['ref'] ) : ?> <bdi dir="ltr"><?php echo esc_html( $mv['ref'] ); ?></bdi><?php endif; ?> · <?php echo esc_html( $mv['label'] ); ?><?php if ( $mv['note'] ) : ?> <span class="smst-note"><?php echo esc_html( $mv['note'] ); ?></span><?php endif; ?></span>
			<span class="smst-mv__chg <?php echo esc_attr( $sign ); ?>"><?php echo esc_html( $chg ); ?></span>
		</li>
	<?php endforeach; ?>
</ul>
