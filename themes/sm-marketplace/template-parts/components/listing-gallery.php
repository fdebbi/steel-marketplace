<?php
/**
 * Photo gallery of a listing page (main picture, thumbnails, section sheet).
 *
 * @var array $args { item: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$item = (array) $args['item'];
?>
<div class="sm-gallery" data-sm-gallery>
	<?php $sheet = ! empty( $item['image_drawing'] ) ? ( $item['section_sheet'] ?? null ) : null; ?>
	<figure class="sm-gallery__main<?php echo ! empty( $item['image_drawing'] ) ? ' is-drawing' : ''; ?><?php echo $sheet ? ' has-sheet' : ''; ?>">
		<img src="<?php echo esc_url( sm_image_url( $item['image'] ) ); ?>" alt="<?php echo esc_attr( sm_t( $item['title'] ) ); ?>" width="1280" height="960" data-sm-gallery-main>
		<?php if ( $sheet ) : ?>
			<?php sm_component( 'section-sheet', array( 'sheet' => $sheet ) ); ?>
		<?php endif; ?>
	</figure>
	<div class="sm-gallery__thumbs">
		<?php $gallery = ! empty( $item['photos'] ) ? $item['photos'] : ( ! empty( $item['demo'] ) ? array( $item['image'], 'demo:detail-section', 'demo:detail-stack' ) : array( $item['image'] ) ); ?>
		<?php foreach ( count( $gallery ) > 1 ? $gallery : array() as $n => $img ) : ?>
			<button type="button" class="sm-gallery__thumb" aria-pressed="<?php echo 0 === $n ? 'true' : 'false'; ?>" data-src="<?php echo esc_url( sm_image_url( $img ) ); ?>">
				<img src="<?php echo esc_url( sm_image_url( $img ) ); ?>" alt="" width="160" height="120" loading="lazy">
				<span class="screen-reader-text">
					<?php
					/* translators: %d: photo number. */
					echo esc_html( sprintf( __( 'Photo %d', 'sm-marketplace' ), $n + 1 ) );
					?>
				</span>
			</button>
		<?php endforeach; ?>
	</div>
</div>
