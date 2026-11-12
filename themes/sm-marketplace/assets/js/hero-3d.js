/**
 * Home hero: a small Three.js scene (0.18.0).
 *
 * Runs only on a desktop-size screen, with WebGL, without "reduce motion", and
 * only after the page has loaded (idle time), so it never delays the page.
 * Otherwise, or on any error, the SVG beam drawn by hero-steel.php stays.
 *
 * The beam is an IPE 200 cross-section (h 200, b 100, tw 5.6, tf 8.5, r 12 mm,
 * EN 10365), extruded and drawn in brushed metal, with a faint green edge and
 * a slow field of particles. It renders only while the hero is on screen.
 */
( function () {
	'use strict';
	var box = document.querySelector( '[data-sm-hero3d]' );
	if ( ! box || ! window.matchMedia ) { return; }
	if ( ! matchMedia( '(min-width: 1024px)' ).matches || matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) { return; }
	try {
		var probe = document.createElement( 'canvas' );
		if ( ! ( probe.getContext( 'webgl2' ) || probe.getContext( 'webgl' ) ) ) { return; }
	} catch ( e ) { return; }

	function start() {
		import( box.getAttribute( 'data-three' ) ).then( function ( THREE ) {
			return import( box.getAttribute( 'data-env' ) ).then( function ( env ) { run( THREE, env.RoomEnvironment ); } );
		} ).catch( function () { /* keep the SVG beam */ } );
	}
	function later() {
		if ( 'requestIdleCallback' in window ) { window.requestIdleCallback( start, { timeout: 2500 } ); } else { setTimeout( start, 800 ); }
	}
	if ( document.readyState === 'complete' ) { later(); } else { window.addEventListener( 'load', later, { once: true } ); }

	function ipeShape( THREE ) {
		// 1 unit = 100 mm.
		var h = 2, b = 1, tw = 0.056, tf = 0.085, r = 0.12;
		var x1 = b / 2, y1 = h / 2, xw = tw / 2, yi = y1 - tf;
		var s = new THREE.Shape();
		s.moveTo( -x1, -y1 ); s.lineTo( x1, -y1 ); s.lineTo( x1, -yi );
		s.lineTo( xw + r, -yi ); s.quadraticCurveTo( xw, -yi, xw, -yi + r );
		s.lineTo( xw, yi - r ); s.quadraticCurveTo( xw, yi, xw + r, yi );
		s.lineTo( x1, yi ); s.lineTo( x1, y1 ); s.lineTo( -x1, y1 ); s.lineTo( -x1, yi );
		s.lineTo( -xw - r, yi ); s.quadraticCurveTo( -xw, yi, -xw, yi - r );
		s.lineTo( -xw, -yi + r ); s.quadraticCurveTo( -xw, -yi, -xw - r, -yi );
		s.lineTo( -x1, -yi ); s.closePath();
		return s;
	}

	function particles( THREE, count, color, size, opacity ) {
		var pos = new Float32Array( count * 3 );
		for ( var i = 0; i < count; i++ ) {
			var rad = 3.2 + Math.random() * 3.4, th = Math.random() * Math.PI * 2, ph = Math.acos( 2 * Math.random() - 1 );
			pos[ i * 3 ] = rad * Math.sin( ph ) * Math.cos( th );
			pos[ i * 3 + 1 ] = rad * Math.cos( ph ) * 0.6;
			pos[ i * 3 + 2 ] = rad * Math.sin( ph ) * Math.sin( th ) - 1;
		}
		var g = new THREE.BufferGeometry();
		g.setAttribute( 'position', new THREE.BufferAttribute( pos, 3 ) );
		return new THREE.Points( g, new THREE.PointsMaterial( { color: color, size: size, transparent: true, opacity: opacity, depthWrite: false } ) );
	}

	function run( THREE, RoomEnvironment ) {
		var w = box.clientWidth, h = box.clientHeight;
		if ( ! w || ! h ) { return; }
		var renderer = new THREE.WebGLRenderer( { antialias: true, alpha: true, powerPreference: 'low-power' } );
		renderer.setPixelRatio( Math.min( window.devicePixelRatio || 1, 1.75 ) );
		renderer.setSize( w, h );
		renderer.outputColorSpace = THREE.SRGBColorSpace;
		renderer.toneMapping = THREE.ACESFilmicToneMapping;
		renderer.domElement.className = 'sm-hero3d__canvas';

		var scene = new THREE.Scene();
		var pmrem = new THREE.PMREMGenerator( renderer );
		scene.environment = pmrem.fromScene( new RoomEnvironment( renderer ), 0.04 ).texture;
		pmrem.dispose();

		var camera = new THREE.PerspectiveCamera( 30, w / h, 0.1, 100 );
		camera.position.set( 0, 0.3, 11 );

		var geo = new THREE.ExtrudeGeometry( ipeShape( THREE ), { depth: 5.2, bevelEnabled: true, bevelThickness: 0.012, bevelSize: 0.012, bevelSegments: 2, curveSegments: 6 } );
		geo.center();
		var beam = new THREE.Mesh( geo, new THREE.MeshStandardMaterial( { color: 0xb4bdc4, metalness: 0.95, roughness: 0.34, envMapIntensity: 1.0 } ) );
		var edges = new THREE.LineSegments( new THREE.EdgesGeometry( geo, 30 ), new THREE.LineBasicMaterial( { color: 0x06ce77, transparent: true, opacity: 0.32 } ) );
		var group = new THREE.Group();
		group.add( beam, edges );
		group.rotation.set( 0.42, -0.85, 0.08 );
		scene.add( group );

		var key = new THREE.DirectionalLight( 0xffffff, 1.0 ); key.position.set( -4, 5, 6 ); scene.add( key );
		var rim = new THREE.DirectionalLight( 0x06ce77, 2.2 ); rim.position.set( 5, -1, -4 ); scene.add( rim );

		var dust = particles( THREE, 520, 0xc6cdd2, 0.025, 0.6 );
		var sparks = particles( THREE, 70, 0x06ce77, 0.045, 0.85 );
		scene.add( dust, sparks );

		box.appendChild( renderer.domElement );

		var visible = true, raf = 0, t0 = performance.now(), px = 0, py = 0, tx = 0, ty = 0;
		function frame( now ) {
			var t = ( now - t0 ) / 1000;
			px += ( tx - px ) * 0.04; py += ( ty - py ) * 0.04;
			group.rotation.y = -0.85 + Math.sin( t * 0.22 ) * 0.38 + px * 0.25; // sway, never edge-on
			group.rotation.x = 0.42 + py * 0.15;
			group.position.y = Math.sin( t * 0.6 ) * 0.08;
			dust.rotation.y = t * 0.02; sparks.rotation.y = -t * 0.03;
			renderer.render( scene, camera );
			raf = visible ? requestAnimationFrame( frame ) : 0;
		}
		raf = requestAnimationFrame( function ( now ) { frame( now ); box.classList.add( 'is-3d' ); } );

		function resume() { if ( visible && ! raf && ! document.hidden ) { raf = requestAnimationFrame( frame ); } }
		if ( 'IntersectionObserver' in window ) {
			new IntersectionObserver( function ( entries ) { visible = entries[ 0 ].isIntersecting; resume(); } ).observe( box );
		}
		document.addEventListener( 'visibilitychange', function () { visible = ! document.hidden; resume(); } );
		window.addEventListener( 'pointermove', function ( e ) {
			tx = ( e.clientX / window.innerWidth - 0.5 ) * 2; ty = ( e.clientY / window.innerHeight - 0.5 ) * 2;
		}, { passive: true } );
		if ( 'ResizeObserver' in window ) {
			new ResizeObserver( function () {
				var nw = box.clientWidth, nh = box.clientHeight;
				if ( nw && nh ) { renderer.setSize( nw, nh ); camera.aspect = nw / nh; camera.updateProjectionMatrix(); }
			} ).observe( box );
		}
	}
}() );
