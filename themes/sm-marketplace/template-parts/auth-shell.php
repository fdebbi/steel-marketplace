<?php
/**
 * Shared authentication layout: brand side panel + form card.
 *
 * @var array $args { title: string, lead: string, content: callable }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="sm-auth">
	<aside class="sm-auth__side">
		<?php sm_brand(); ?>
		<p class="sm-auth__claim"><?php esc_html_e( 'One business account to buy and sell steel, structures and services.', 'sm-marketplace' ); ?></p>
		<ul class="sm-checklist sm-checklist--light">
			<li><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Prices per kg with automatic totals', 'sm-marketplace' ); ?></li>
			<li><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Verified companies and engineers', 'sm-marketplace' ); ?></li>
			<li><?php sm_icon( 'check' ); ?><?php esc_html_e( 'Requests for quotation compared side by side', 'sm-marketplace' ); ?></li>
		</ul>
	</aside>
	<div class="sm-auth__main">
		<div class="sm-auth__card">
			<h1 class="sm-auth__title"><?php echo esc_html( $args['title'] ); ?></h1>
			<?php if ( ! empty( $args['lead'] ) ) : ?><p class="sm-auth__lead"><?php echo esc_html( $args['lead'] ); ?></p><?php endif; ?>
			<?php call_user_func( $args['content'] ); ?>
		</div>
	</div>
</div>
