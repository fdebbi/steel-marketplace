<?php
/**
 * Seller of a listing: company (name, verification, since) or private seller.
 *
 * @var array $args { item: array, company: array|null }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$item    = (array) $args['item'];
$company = $args['company'] ?? null;
?>
<?php if ( $company ) : ?>
	<div class="sm-seller">
		<?php sm_avatar( $company ); ?>
		<div>
			<p class="sm-seller__name"><a href="<?php echo esc_url( sm_page_url( 'company', array( 'c' => $company['slug'] ) ) ); ?>"><?php sm_e( $company['name'] ); ?></a></p>
			<p class="sm-seller__badge<?php echo $company['verification'] >= 2 ? ' is-verified' : ''; ?>"><?php sm_icon( $company['verification'] >= 2 ? 'verified' : 'shield' ); ?><?php echo esc_html( sm_verification_label( $company['verification'], $company['class'] ?? '' ) ); ?></p>
			<p class="sm-seller__since">
				<?php
				/* translators: %s: year. */
				echo esc_html( sprintf( __( 'Active since %s', 'sm-marketplace' ), $company['since'] ) );
				?>
			</p>
		</div>
	</div>
<?php elseif ( isset( $item['seller_public'] ) && ! $item['seller_public'] ) : ?>
	<div class="sm-seller">
		<span class="sm-avatar sm-avatar--client" aria-hidden="true"><?php sm_icon( 'user' ); ?></span>
		<div>
			<p class="sm-seller__name"><?php esc_html_e( 'Private seller', 'sm-marketplace' ); ?></p>
			<p class="sm-seller__since"><?php esc_html_e( 'Listing checked by the platform. Contact the seller with the form; your details are shared only when you write.', 'sm-marketplace' ); ?></p>
		</div>
	</div>
<?php endif; ?>
