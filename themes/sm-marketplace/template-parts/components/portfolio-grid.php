<?php
/**
 * References (portfolio) of a company.
 *
 * @var array $args { items: array, actions?: bool }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$wilayas = sm_theme_wilayas();
?>
<ul class="sms-portfolio" role="list">
	<?php foreach ( (array) $args['items'] as $p ) : ?>
		<li class="sms-ref">
			<?php if ( $p['image'] ) : ?><?php $full = is_numeric( $p['image'] ) ? wp_get_attachment_url( (int) $p['image'] ) : sm_image_url( $p['image'] ); ?><a class="sms-ref__zoom" href="<?php echo esc_url( $full ); ?>" data-sm-lightbox="portfolio" data-sm-caption="<?php echo esc_attr( sm_t( $p['title'] ) ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: project title. */ __( 'Enlarge the photo: %s', 'sm-marketplace' ), sm_t( $p['title'] ) ) ); ?>"><img class="sms-ref__img" src="<?php echo esc_url( sm_image_url( $p['image'] ) ); ?>" alt="" width="480" height="320" loading="lazy"><span class="sms-ref__zoomicon" aria-hidden="true"><?php sm_icon( 'search' ); ?></span></a><?php else : ?><span class="sms-ref__img sms-ref__img--empty" aria-hidden="true"><?php sm_icon( 'image' ); ?></span><?php endif; ?>
			<div class="sms-ref__body">
				<h3 class="sms-ref__title"><?php sm_e( $p['title'] ); ?></h3>
				<p class="sms-ref__meta">
					<?php if ( $p['year'] ) : ?><span><?php echo esc_html( $p['year'] ); ?></span><?php endif; ?>
					<?php if ( $p['wilaya'] ) : ?><span><?php echo esc_html( sm_t( $wilayas[ $p['wilaya'] ] ?? '' ) ); ?></span><?php endif; ?>
					<?php if ( $p['role'] ) : ?><span><?php sm_e( $p['role'] ); ?></span><?php endif; ?>
				</p>
				<?php if ( $p['description'] ) : ?><p class="sms-ref__desc" dir="auto"><?php sm_e( $p['description'] ); ?></p><?php endif; ?>
				<?php if ( ! empty( $args['actions'] ) ) : ?>
					<div class="smq-actions">
						<a class="sm-btn sm-btn--ghost sm-btn--sm" href="<?php echo esc_url( sm_page_url( 'account', array( 'view' => 'portfolio', 'edit' => $p['id'] ) ) ); ?>"><?php sm_icon( 'edit' ); ?><?php esc_html_e( 'Edit', 'sm-marketplace' ); ?></a>
						<?php sm_theme_form_open( 'portfolio_delete', array( 'class' => 'smp-act' ) ); ?>
							<input type="hidden" name="portfolio_id" value="<?php echo esc_attr( $p['id'] ); ?>">
							<button class="sm-btn sm-btn--ghost sm-btn--sm" type="submit" data-sm-confirm="<?php esc_attr_e( 'Delete this reference?', 'sm-marketplace' ); ?>"><?php sm_icon( 'trash' ); ?><?php esc_html_e( 'Delete', 'sm-marketplace' ); ?></button>
						</form>
					</div>
				<?php endif; ?>
			</div>
		</li>
	<?php endforeach; ?>
</ul>
