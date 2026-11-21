<?php
/**
 * Photo gallery of a listing page (main picture, thumbnails, section sheet).
 *
 * 0.34.0: with photos, the drawing and sheet of the section stay in the gallery
 * as one slide (first for new steel, last for reuse: sheet_slide from SM Products).
 *
 * @var array $args { item: array }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$item  = (array) $args['item'];
$slide = ! empty( $item['sheet_slide']['img'] ) ? (array) $item['sheet_slide'] : null;
$sheet = ! empty( $item['image_drawing'] ) ? ( $item['section_sheet'] ?? null ) : ( $slide['sheet'] ?? null );

// Slides: { src, sheet (bool) }.
$gallery = ! empty( $item['photos'] ) ? $item['photos'] : ( ! empty( $item['demo'] ) ? array( $item['image'], 'demo:detail-section', 'demo:detail-stack' ) : array( $item['image'] ) );
$slides  = array_map( static fn( $img ) => array( 'src' => sm_image_url( $img ), 'sheet' => false ), $gallery );
if ( ! empty( $item['image_drawing'] ) && $slides ) {
	$slides[0]['sheet'] = (bool) $sheet;
}
if ( $slide ) {
	$s = array( 'src' => (string) $slide['img'], 'sheet' => ! empty( $slide['sheet'] ) );
	if ( ! empty( $slide['first'] ) ) {
		array_unshift( $slides, $s );
	} else {
		$slides[] = $s;
	}
}
$first   = $slides[0];
$drawing = ! empty( $item['image_drawing'] ) || ( $slide && $first['src'] === $slide['img'] );
?>
<div class="sm-gallery" data-sm-gallery>
	<figure class="sm-gallery__main<?php echo $drawing ? ' is-drawing' : ''; ?><?php echo $first['sheet'] ? ' has-sheet' : ''; ?>" data-sm-gallery-figure>
		<img src="<?php echo esc_url( $first['src'] ); ?>" alt="<?php echo esc_attr( sm_t( $item['title'] ) ); ?>" width="1280" height="960" fetchpriority="high" decoding="async" data-sm-gallery-main>
		<?php if ( $sheet ) : ?>
			<div class="sm-gallery__sheet" data-sm-gallery-sheet<?php echo $first['sheet'] ? '' : ' hidden'; ?>><?php sm_component( 'section-sheet', array( 'sheet' => $sheet ) ); ?></div>
		<?php endif; ?>
	</figure>
	<div class="sm-gallery__thumbs">
		<?php foreach ( count( $slides ) > 1 ? $slides : array() as $n => $sl ) : ?>
			<?php $is_drawing = $slide && $sl['src'] === $slide['img']; ?>
			<button type="button" class="sm-gallery__thumb<?php echo $is_drawing ? ' is-drawing' : ''; ?>" aria-pressed="<?php echo 0 === $n ? 'true' : 'false'; ?>" data-src="<?php echo esc_url( $sl['src'] ); ?>"<?php echo $is_drawing ? ' data-drawing' : ''; ?><?php echo $sl['sheet'] ? ' data-sheet' : ''; ?>>
				<img src="<?php echo esc_url( $sl['src'] ); ?>" alt="" width="160" height="120" loading="lazy">
				<span class="screen-reader-text">
					<?php
					echo esc_html(
						$is_drawing ? __( 'Drawing of the section', 'sm-marketplace' ) : sprintf(
							/* translators: %d: photo number. */
							__( 'Photo %d', 'sm-marketplace' ),
							$n + ( $slide && ! empty( $slide['first'] ) ? 0 : 1 )
						)
					);
					?>
				</span>
			</button>
		<?php endforeach; ?>
	</div>
</div>
