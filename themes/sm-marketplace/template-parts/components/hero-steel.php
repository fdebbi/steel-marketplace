<?php
/**
 * Steel visual of the home hero (0.18.0): an IPE beam drawn in SVG, shown on
 * every device. On a desktop with WebGL and without "reduce motion", hero-3d.js
 * replaces it after the page has loaded with a small Three.js scene (a real
 * IPE 200 cross-section, extruded, in brushed metal).
 *
 * @package SM_Marketplace
 */

defined( 'ABSPATH' ) || exit;

$base = SM_THEME_URI . '/assets/vendor/three/';
?>
<div class="sm-hero3d" aria-hidden="true" data-sm-hero3d data-three="<?php echo esc_url( $base . 'three.module.min.js' ); ?>" data-env="<?php echo esc_url( $base . 'RoomEnvironment.js' ); ?>">
	<span class="sm-hero3d__orbit"><span></span></span>
	<span class="sm-hero3d__ring"></span>
	<span class="sm-hero3d__glow"></span>
	<svg class="sm-hero3d__beam" viewBox="-40 -200 460 400" focusable="false">
		<defs>
			<linearGradient id="smh-front" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#E4E9EC"/><stop offset=".55" stop-color="#9AA6AF"/><stop offset="1" stop-color="#5E6A73"/></linearGradient>
			<linearGradient id="smh-top" x1="0" y1="1" x2="1" y2="0"><stop offset="0" stop-color="#C6CDD2"/><stop offset=".5" stop-color="#F5F7F8"/><stop offset="1" stop-color="#8A969F"/></linearGradient>
			<linearGradient id="smh-side" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#3C474F"/><stop offset="1" stop-color="#1B232A"/></linearGradient>
			<linearGradient id="smh-web" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#6F7B84"/><stop offset="1" stop-color="#2C363E"/></linearGradient>
		</defs>
		<g transform="translate(40 40)">
			<polygon points="67,18 327,-132 327,-8 67,142" fill="url(#smh-web)"/>
			<polygon points="67,142 120,142 380,-8 327,-8" fill="url(#smh-top)" opacity=".85"/>
			<polygon points="120,142 380,-8 380,10 120,160" fill="url(#smh-side)"/>
			<polygon points="0,0 120,0 380,-150 260,-150" fill="url(#smh-top)"/>
			<polygon points="120,0 380,-150 380,-132 120,18" fill="url(#smh-side)"/>
			<polygon points="0,0 120,0 120,18 67,18 67,142 120,142 120,160 0,160 0,142 53,142 53,18 0,18" fill="url(#smh-front)" stroke="rgba(6,206,119,.55)" stroke-width="1"/>
		</g>
	</svg>
</div>
