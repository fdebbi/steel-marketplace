/**
 * SM Marketplace — company location map (Leaflet).
 * Presentation only: positions are validated by sm-core when the form is saved.
 */
( function () {
	'use strict';

	var cfg = window.smMap || {};
	var t = cfg.i18n || {};
	if ( ! window.L ) { return; }
	var L = window.L;
	if ( cfg.imagePath ) { L.Icon.Default.imagePath = cfg.imagePath; }

	var B = cfg.bounds || { lat_min: 18.9, lat_max: 37.2, lng_min: -8.7, lng_max: 12.0 };
	var country = cfg.country || [ 28.0, 2.6, 5 ];

	function inAlgeria( lat, lng ) {
		return lat >= B.lat_min && lat <= B.lat_max && lng >= B.lng_min && lng <= B.lng_max;
	}
	function fmt( n ) { return Number( n ).toFixed( 5 ); }

	function baseMap( el, opts ) {
		var map = L.map( el, Object.assign( {
			maxBounds: [ [ B.lat_min - 2, B.lng_min - 3 ], [ B.lat_max + 2, B.lng_max + 3 ] ],
			maxBoundsViscosity: 0.8,
			minZoom: 4,
			attributionControl: true
		}, opts || {} ) );
		map.attributionControl.setPrefix( '<a href="https://leafletjs.com" target="_blank" rel="noopener">Leaflet</a>' );
		L.tileLayer( cfg.tiles || 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
			maxZoom: cfg.maxZoom || 19,
			attribution: cfg.attribution || '&copy; OpenStreetMap'
		} ).addTo( map );
		return map;
	}

	/* ---- Read-only map (public company page) ---------------------------- */
	Array.prototype.forEach.call( document.querySelectorAll( '[data-sm-map="view"]' ), function ( el ) {
		var lat = parseFloat( el.dataset.lat ), lng = parseFloat( el.dataset.lng );
		if ( isNaN( lat ) || isNaN( lng ) ) { return; }
		var map = baseMap( el, { scrollWheelZoom: false, dragging: ! L.Browser.mobile, tap: false } );
		map.setView( [ lat, lng ], parseInt( el.dataset.zoom, 10 ) || 15 );
		if ( el.dataset.area ) { return; } // Area only (0.33.0): no marker, the exact place is not public.
		var m = L.marker( [ lat, lng ], { keyboard: false, title: el.dataset.label || '', alt: el.dataset.label || '' } ).addTo( map );
		if ( el.dataset.label ) { m.bindTooltip( el.dataset.label ); }
	} );

	/* ---- Picker (company form) ------------------------------------------ */
	var box = document.querySelector( '[data-sm-map-picker]' );
	if ( ! box ) { return; }
	var el = box.querySelector( '[data-sm-map="picker"]' );
	var latIn = box.querySelector( '[data-sm-map-lat]' );
	var lngIn = box.querySelector( '[data-sm-map-lng]' );
	var status = box.querySelector( '[data-sm-map-status]' );
	var locateBtn = box.querySelector( '[data-sm-map-locate]' );
	var clearBtn = box.querySelector( '[data-sm-map-clear]' );
	var select = document.querySelector( box.dataset.wilayaSelect || '#c-wilaya' );
	var centres = cfg.centres || {};

	var map = baseMap( el, { scrollWheelZoom: 'center' } );
	el.smMap = map; // Exposed for automated tests.
	var marker = L.marker( [ 0, 0 ], {
		draggable: true,
		autoPan: true,
		keyboard: true,
		title: t.marker || '',
		alt: t.marker || ''
	} );
	var accuracy = null;

	function say( text, cls ) {
		status.textContent = '';
		status.className = 'sm-map__status' + ( cls ? ' ' + cls : '' );
		if ( text instanceof Node ) { status.appendChild( text ); } else { status.textContent = text; }
	}
	function sayPosition( prefix, suffix ) {
		var frag = document.createDocumentFragment();
		frag.appendChild( document.createTextNode( prefix + ' ' ) );
		var b = document.createElement( 'bdi' );
		b.dir = 'ltr';
		b.textContent = fmt( latIn.value ) + ', ' + fmt( lngIn.value );
		frag.appendChild( b );
		if ( suffix ) { frag.appendChild( document.createTextNode( ' ' + suffix ) ); }
		say( frag, 'is-set' );
	}
	function clearAccuracy() {
		if ( accuracy ) { map.removeLayer( accuracy ); accuracy = null; }
	}
	function setPosition( latlng, prefix ) {
		if ( ! inAlgeria( latlng.lat, latlng.lng ) ) {
			say( t.outside || '', 'is-error' );
			return false;
		}
		marker.setLatLng( latlng );
		if ( ! map.hasLayer( marker ) ) { marker.addTo( map ); }
		latIn.value = latlng.lat.toFixed( 6 );
		lngIn.value = latlng.lng.toFixed( 6 );
		if ( clearBtn ) { clearBtn.hidden = false; }
		sayPosition( prefix || t.set || '' );
		return true;
	}
	function unset( message ) {
		latIn.value = '';
		lngIn.value = '';
		clearAccuracy();
		if ( clearBtn ) { clearBtn.hidden = true; }
		say( message || t.none || '' );
	}
	/** Show the wilaya and a suggested marker that is not saved until moved. */
	function showWilaya( code, zoom ) {
		var c = centres[ code ];
		if ( ! c ) { map.setView( [ country[ 0 ], country[ 1 ] ], country[ 2 ] ); return; }
		map.setView( c, zoom || 11 );
		marker.setLatLng( c );
		if ( ! map.hasLayer( marker ) ) { marker.addTo( map ); }
	}

	// Initial view.
	var lat0 = parseFloat( latIn.value ), lng0 = parseFloat( lngIn.value );
	if ( ! isNaN( lat0 ) && ! isNaN( lng0 ) ) {
		map.setView( [ lat0, lng0 ], 16 );
		marker.setLatLng( [ lat0, lng0 ] ).addTo( map );
	} else {
		showWilaya( select ? select.value : '' );
		say( t.drag || '' );
	}

	marker.on( 'dragend', function () {
		clearAccuracy();
		if ( ! setPosition( marker.getLatLng() ) && latIn.value ) {
			marker.setLatLng( [ parseFloat( latIn.value ), parseFloat( lngIn.value ) ] );
		}
	} );
	map.on( 'click', function ( e ) {
		clearAccuracy();
		setPosition( e.latlng );
	} );

	// Keyboard: arrow keys move the focused marker (about 10 px per press, 50 with Shift).
	marker.on( 'add', function () {
		var icon = marker.getElement();
		if ( ! icon || icon._smKeys ) { return; }
		icon._smKeys = true;
		icon.setAttribute( 'aria-describedby', 'sm-map-status' );
		icon.addEventListener( 'keydown', function ( e ) {
			var d = { ArrowUp: [ 0, -1 ], ArrowDown: [ 0, 1 ], ArrowLeft: [ -1, 0 ], ArrowRight: [ 1, 0 ] }[ e.key ];
			if ( ! d ) { return; }
			e.preventDefault();
			var step = e.shiftKey ? 50 : 10;
			var p = map.latLngToContainerPoint( marker.getLatLng() ).add( [ d[ 0 ] * step, d[ 1 ] * step ] );
			setPosition( map.containerPointToLatLng( p ) );
			map.panInside( marker.getLatLng(), { padding: [ 40, 40 ] } );
		} );
	} );
	if ( map.hasLayer( marker ) ) { marker.fire( 'add' ); }

	if ( select ) {
		select.addEventListener( 'change', function () {
			if ( latIn.value ) { unset( t.wilayaChanged || '' ); } else { say( t.drag || '' ); }
			showWilaya( select.value );
		} );
	}

	if ( clearBtn ) {
		clearBtn.addEventListener( 'click', function () {
			unset( t.removed || '' );
			showWilaya( select ? select.value : '' );
		} );
	}

	if ( locateBtn ) {
		if ( ! ( 'geolocation' in navigator ) ) {
			locateBtn.hidden = true;
		}
		locateBtn.addEventListener( 'click', function () {
			if ( ! window.isSecureContext ) { say( t.insecure || '', 'is-error' ); return; }
			locateBtn.setAttribute( 'aria-busy', 'true' );
			locateBtn.disabled = true;
			say( t.locating || '' );
			navigator.geolocation.getCurrentPosition( function ( pos ) {
				locateBtn.removeAttribute( 'aria-busy' );
				locateBtn.disabled = false;
				var ll = L.latLng( pos.coords.latitude, pos.coords.longitude );
				clearAccuracy();
				if ( ! setPosition( ll, t.located || '' ) ) { return; }
				var acc = Math.round( pos.coords.accuracy || 0 );
				if ( acc > 0 ) {
					accuracy = L.circle( ll, { radius: acc, weight: 1, fillOpacity: 0.12, interactive: false } ).addTo( map );
					if ( acc > 100 ) {
						sayPosition( t.located || '', ( t.approx || '' ).replace( '%d', acc ) );
					}
				}
				map.setView( ll, acc > 500 ? 14 : 17 );
			}, function ( err ) {
				locateBtn.removeAttribute( 'aria-busy' );
				locateBtn.disabled = false;
				var msg = 1 === err.code ? t.denied : ( 3 === err.code ? t.timeout : t.unavailable );
				say( msg || '', 'is-error' );
			}, { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 } );
		} );
	}

	// The form may sit in a hidden tab or a resized layout: keep tiles correct.
	if ( 'ResizeObserver' in window ) {
		new ResizeObserver( function () { map.invalidateSize(); } ).observe( el );
	}
}() );
