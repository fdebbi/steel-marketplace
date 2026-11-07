<?php
/**
 * Company location map (Leaflet + OpenStreetMap, self-hosted library).
 *
 * Modes:
 * - picker: inside the company form. The wilaya <select> (data-sm-map-wilaya)
 *   recentres the map, the marker is dragged (or the map clicked) to the exact
 *   place, "Use my current location" asks the browser. The position is sent in
 *   hidden lat/lng fields; sm-core validates it.
 * - view: read-only map on the public company page, with a directions link.
 *
 * Without JavaScript the picker shows a short note and the rest of the form
 * still works; the view shows the directions link only.
 *
 * @var array $args {
 *   mode: 'picker'|'view', lat: string|float, lng: string|float, wilaya: string,
 *   wilaya_select: string (CSS selector, picker), map_public: int (picker), label: string (view)
 * }
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$mode = ( $args['mode'] ?? 'view' ) === 'picker' ? 'picker' : 'view';
$lat  = (string) ( $args['lat'] ?? '' );
$lng  = (string) ( $args['lng'] ?? '' );
$has  = '' !== $lat && '' !== $lng;

if ( 'view' === $mode && ! $has ) {
	return;
}
sm_theme_enqueue_map();

if ( 'view' === $mode ) :
	$dest = rawurlencode( $lat . ',' . $lng );
	?>
	<div class="sm-map sm-map--view">
		<div class="sm-map__canvas" dir="ltr" data-sm-map="view" data-lat="<?php echo esc_attr( $lat ); ?>" data-lng="<?php echo esc_attr( $lng ); ?>" data-label="<?php echo esc_attr( $args['label'] ?? '' ); ?>" role="region" aria-label="<?php esc_attr_e( 'Map of the company location', 'sm-marketplace' ); ?>"></div>
		<div class="sm-map__actions">
			<a class="sm-btn sm-btn--ghost sm-btn--sm" href="https://www.google.com/maps/dir/?api=1&amp;destination=<?php echo esc_attr( $dest ); ?>" target="_blank" rel="noopener nofollow"><?php sm_icon( 'navigation' ); ?><?php esc_html_e( 'Directions', 'sm-marketplace' ); ?></a>
			<a class="sm-btn sm-btn--ghost sm-btn--sm" href="https://www.openstreetmap.org/?mlat=<?php echo esc_attr( $lat ); ?>&amp;mlon=<?php echo esc_attr( $lng ); ?>#map=17/<?php echo esc_attr( $lat ); ?>/<?php echo esc_attr( $lng ); ?>" target="_blank" rel="noopener nofollow"><?php sm_icon( 'map-pin' ); ?><?php esc_html_e( 'Open the map', 'sm-marketplace' ); ?></a>
		</div>
	</div>
	<?php
	return;
endif;

$public = (int) ( $args['map_public'] ?? 1 );
?>
<div class="sm-field sm-map sm-map--picker" data-sm-map-picker data-wilaya-select="<?php echo esc_attr( $args['wilaya_select'] ?? '#c-wilaya' ); ?>">
	<span class="sm-field__legend" id="sm-map-label"><?php esc_html_e( 'Position on the map', 'sm-marketplace' ); ?></span>
	<p class="sm-help" id="sm-map-help"><?php esc_html_e( 'Choose the wilaya: the map opens on it. Then drag the marker (or click the map) to your exact address, or use your current location if you are on site.', 'sm-marketplace' ); ?></p>
	<div class="sm-map__tools">
		<button type="button" class="sm-btn sm-btn--ghost sm-btn--sm" data-sm-map-locate><?php sm_icon( 'locate' ); ?><?php esc_html_e( 'Use my current location', 'sm-marketplace' ); ?></button>
		<button type="button" class="sm-btn sm-btn--ghost sm-btn--sm" data-sm-map-clear<?php echo $has ? '' : ' hidden'; ?>><?php sm_icon( 'trash' ); ?><?php esc_html_e( 'Remove the position', 'sm-marketplace' ); ?></button>
	</div>
	<div class="sm-map__canvas" dir="ltr" data-sm-map="picker" role="application" aria-labelledby="sm-map-label" aria-describedby="sm-map-help sm-map-status"></div>
	<noscript><p class="sm-help"><?php esc_html_e( 'Enable JavaScript to place your company on the map.', 'sm-marketplace' ); ?></p></noscript>
	<p class="sm-map__status" id="sm-map-status" role="status" aria-live="polite" data-sm-map-status><?php echo $has ? esc_html__( 'Position saved. Drag the marker to correct it.', 'sm-marketplace' ) : esc_html__( 'No position yet.', 'sm-marketplace' ); ?></p>
	<input type="hidden" name="lat" value="<?php echo esc_attr( $lat ); ?>" data-sm-map-lat>
	<input type="hidden" name="lng" value="<?php echo esc_attr( $lng ); ?>" data-sm-map-lng>
	<?php sm_theme_error( 'location' ); ?>
	<label class="sm-check"><input type="checkbox" name="map_public" value="1" <?php checked( $public ); ?>><span><?php esc_html_e( 'Show the map on my public page', 'sm-marketplace' ); ?></span></label>
</div>
