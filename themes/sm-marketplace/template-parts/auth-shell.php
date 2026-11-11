<?php
/**
 * Shared authentication layout: brand side panel + form card.
 *
 * @var array $args { title: string, lead: string, content: callable, claim?: string, points?: string[] }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="sm-auth">
	<aside class="sm-auth__side">
		<?php sm_brand(); ?>
		<p class="sm-auth__claim"><?php echo esc_html( $args['claim'] ?? __( 'One account to buy and sell steel, structures and services.', 'sm-marketplace' ) ); ?></p>
		<?php
		$sm_points = $args['points'] ?? array(
			__( 'Prices per kg with automatic totals', 'sm-marketplace' ),
			__( 'Verified companies, professionals and partner engineers', 'sm-marketplace' ),
			__( 'Requests for quotation compared side by side', 'sm-marketplace' ),
		);
		?>
		<ul class="sm-checklist sm-checklist--light">
			<?php foreach ( $sm_points as $sm_point ) : ?><li><?php sm_icon( 'check' ); ?><?php echo esc_html( $sm_point ); ?></li><?php endforeach; ?>
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
