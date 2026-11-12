<?php
/**
 * Invoice of a paid order (Phase 10), printable (Ctrl+P / "Print" button: the
 * print style keeps only the invoice). The seller and buyer details are the
 * ones saved when the payment was confirmed (resource invoice).
 * Address: /compte/facture/?id=ORDER
 *
 * @var array $args { account: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$id  = absint( $_GET['id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
$inv = $id ? sm_theme_data( 'invoice', array( 'id' => $id ) ) : null;
if ( ! is_array( $inv ) || empty( $inv['number'] ) ) {
	sm_component( 'empty-state', array( 'icon' => 'file', 'title' => __( 'Invoice not found', 'sm-marketplace' ), 'text' => __( 'This invoice does not exist or belongs to another account.', 'sm-marketplace' ) ) );
	return;
}
$s       = $inv['seller'];
$b       = $inv['buyer'];
$wilayas = sm_theme_wilayas();
$legal   = static function ( array $p ) {
	$out = array();
	foreach ( array( 'rc' => 'RC', 'nif' => 'NIF', 'nis' => 'NIS', 'art' => 'ART' ) as $k => $l ) {
		if ( ! empty( $p[ $k ] ) ) {
			$out[] = $l . ' : ' . $p[ $k ];
		}
	}
	return $out;
};
?>
<div class="sm-invoice-tools">
	<a class="sm-link" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'plan' ) ) ); ?>"><?php sm_icon( 'chevron-start' ); ?><?php esc_html_e( 'Back', 'sm-marketplace' ); ?></a>
	<button class="sm-btn sm-btn--primary" type="button" onclick="window.print()"><?php sm_icon( 'download' ); ?><span><?php esc_html_e( 'Print or save as PDF', 'sm-marketplace' ); ?></span></button>
</div>

<article class="sm-invoice" aria-label="<?php esc_attr_e( 'Invoice', 'sm-marketplace' ); ?>">
	<header class="sm-invoice__head">
		<div>
			<p class="sm-invoice__brand"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
			<?php if ( ! empty( $s['legal_name'] ) ) : ?><p><strong><?php echo esc_html( $s['legal_name'] ); ?></strong></p><?php endif; ?>
			<?php if ( ! empty( $s['address'] ) ) : ?><p><?php echo nl2br( esc_html( $s['address'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p><?php endif; ?>
			<?php foreach ( $legal( $s ) as $line ) : ?><p dir="ltr" class="sm-invoice__legal"><?php echo esc_html( $line ); ?></p><?php endforeach; ?>
		</div>
		<div class="sm-invoice__meta">
			<h2><?php esc_html_e( 'Invoice', 'sm-marketplace' ); ?></h2>
			<p><span><?php esc_html_e( 'Number', 'sm-marketplace' ); ?></span><strong dir="ltr"><?php echo esc_html( $inv['number'] ); ?></strong></p>
			<p><span><?php esc_html_e( 'Date', 'sm-marketplace' ); ?></span><strong><?php echo esc_html( sm_date( $inv['date'] ) ); ?></strong></p>
			<p><span><?php esc_html_e( 'Order', 'sm-marketplace' ); ?></span><strong dir="ltr"><?php echo esc_html( $inv['ref'] ); ?></strong></p>
		</div>
	</header>

	<section class="sm-invoice__buyer">
		<h3><?php esc_html_e( 'Billed to', 'sm-marketplace' ); ?></h3>
		<p><strong><?php echo esc_html( $b['name'] ?? '' ); ?></strong></p>
		<?php if ( ! empty( $b['address'] ) || ! empty( $b['wilaya'] ) ) : ?>
			<p><?php echo esc_html( trim( ( $b['address'] ?? '' ) . ' ' . ( ! empty( $b['wilaya'] ) && isset( $wilayas[ $b['wilaya'] ] ) ? sm_t( $wilayas[ $b['wilaya'] ] ) : '' ) ) ); ?></p>
		<?php endif; ?>
		<?php foreach ( $legal( $b ) as $line ) : ?><p dir="ltr" class="sm-invoice__legal"><?php echo esc_html( $line ); ?></p><?php endforeach; ?>
	</section>

	<table class="sm-table sm-invoice__lines">
		<thead>
			<tr><th scope="col"><?php esc_html_e( 'Description', 'sm-marketplace' ); ?></th><th scope="col" class="num"><?php esc_html_e( 'Amount before tax', 'sm-marketplace' ); ?></th></tr>
		</thead>
		<tbody>
			<tr><td><?php echo esc_html( $inv['label'] ); ?></td><td class="num"><?php echo esc_html( sm_money( $inv['ht'], 2 ) ); ?></td></tr>
		</tbody>
		<tfoot>
			<tr><th scope="row"><?php esc_html_e( 'Total before tax', 'sm-marketplace' ); ?></th><td class="num"><?php echo esc_html( sm_money( $inv['ht'], 2 ) ); ?></td></tr>
			<tr><th scope="row"><?php /* translators: %s: TVA rate. */ echo esc_html( sprintf( __( 'TVA %s %%', 'sm-marketplace' ), sm_number( $inv['rate'], floor( $inv['rate'] ) == $inv['rate'] ? 0 : 2 ) ) ); ?></th><td class="num"><?php echo esc_html( sm_money( $inv['tva'], 2 ) ); ?></td></tr>
			<tr class="sm-invoice__total"><th scope="row"><?php esc_html_e( 'Total including tax', 'sm-marketplace' ); ?></th><td class="num"><?php echo esc_html( sm_money( $inv['ttc'], 2 ) ); ?></td></tr>
		</tfoot>
	</table>

	<footer class="sm-invoice__foot">
		<?php if ( $inv['method'] ) : ?>
			<p>
				<?php
				/* translators: 1: payment method, 2: date. */
				echo esc_html( sprintf( __( 'Paid by %1$s, confirmed on %2$s.', 'sm-marketplace' ), $inv['method'], sm_date( $inv['date'] ) ) );
				?>
			</p>
		<?php endif; ?>
	</footer>
</article>
