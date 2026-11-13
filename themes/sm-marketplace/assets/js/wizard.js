/**
 * Partner engineer application in steps (0.29.0). Presentation and comfort only:
 * the server checks everything again when the form is sent.
 *
 * - one panel per step, same window height; Continue checks the fields of the step;
 * - draft of the texts on this device (never the password nor the files);
 * - BET: name of the office and approval document; "Other" opens a text field;
 * - services in a drop-down list with several choices;
 * - photos of documents made lighter before sending (canvas, JPEG);
 * - last step: summary, 6-digit code sent to the e-mail address and checked
 *   before the form is sent (the account is created only then).
 */
( function () {
	'use strict';

	var form = document.querySelector( 'form[data-wz]' );
	if ( ! form ) { return; }
	var cfg = {};
	try { cfg = JSON.parse( form.querySelector( '[data-wz-config]' ).textContent ); } catch ( e ) { return; }
	var t = cfg.i18n || {};
	var $ = function ( s, c ) { return ( c || form ).querySelector( s ); };
	var $$ = function ( s, c ) { return Array.prototype.slice.call( ( c || form ).querySelectorAll( s ) ); };
	var fmt = function ( s ) { var a = arguments, i = 0; return String( s ).replace( /%(\d\$)?[sd]/g, function ( m, pos ) { return pos ? a[ parseInt( pos, 10 ) ] : a[ ++i ]; } ); };

	form.classList.add( 'is-js' );
	var panels = $$( '[data-wz-step]' );
	var order = panels.map( function ( p ) { return parseInt( p.getAttribute( 'data-wz-step' ), 10 ); } );
	var current = order[ 0 ];
	var body = $( '[data-wz-body]' );
	var btnPrev = $( '[data-wz-prev]' ), btnNext = $( '[data-wz-next]' ), btnSubmit = $( '[data-wz-submit]' );
	var names = {};
	$$( '[data-wz-nav]' ).forEach( function ( li ) { names[ li.getAttribute( 'data-wz-nav' ) ] = li.textContent.replace( /^\s*\d+/, '' ).trim(); } );

	/* ---- Errors -------------------------------------------------------- */
	function clearError( el ) {
		el.removeAttribute( 'aria-invalid' );
		var box = el.closest( 'div' );
		var e = box && box.querySelector( '.wz__err[data-js]' );
		if ( e ) { e.remove(); }
	}
	function showError( el, msg ) {
		el.setAttribute( 'aria-invalid', 'true' );
		var box = el.closest( '[data-wz-doc]' ) ? el.closest( '[data-wz-doc]' ).querySelector( 'div' ) : el.closest( 'div' );
		var e = box.querySelector( '.wz__err[data-js]' );
		if ( ! e ) { e = document.createElement( 'p' ); e.className = 'wz__err'; e.setAttribute( 'data-js', '' ); e.setAttribute( 'role', 'alert' ); box.appendChild( e ); }
		e.textContent = msg;
	}

	/* ---- BET and "Other" ---------------------------------------------- */
	function isBet() { var r = $( 'input[name="partner_type"]:checked' ); return r && 'bet' === r.value; }
	function syncBet() {
		var bet = isBet();
		$$( '[data-wz-bet]' ).forEach( function ( el ) { el.hidden = ! bet; } );
		$$( '[data-wz-bet-required]' ).forEach( function ( el ) { el.disabled = ! bet; } );
	}
	$$( '[data-wz-type]' ).forEach( function ( r ) { r.addEventListener( 'change', function () { syncBet(); save(); } ); } );
	$$( 'select[data-wz-other]' ).forEach( function ( sel ) {
		var other = document.getElementById( sel.getAttribute( 'data-wz-other' ) );
		function sync() { var on = 'other' === sel.value; other.hidden = ! on; other.required = on; }
		sel.addEventListener( 'change', function () { sync(); if ( 'other' === sel.value ) { other.focus(); } } );
		sync();
	} );

	/* ---- Services: drop-down list with several choices ------------------- */
	var ms = $( '[data-wz-ms]' ), paintMs = function () {};
	if ( ms ) {
		var msBtn = $( '[data-wz-ms-btn]', ms ), msList = $( '[data-wz-ms-list]', ms ), msTags = $( '[data-wz-ms-tags]', ms ), msCount = $( '[data-wz-ms-count]', ms );
		var boxes = $$( 'input[type="checkbox"]', msList );
		var paint = function () {
			var on = boxes.filter( function ( b ) { return b.checked; } );
			msTags.innerHTML = '';
			if ( ! on.length ) { msTags.textContent = t.none; }
			on.forEach( function ( b ) { var s = document.createElement( 'span' ); s.className = 'tag'; s.textContent = b.parentNode.textContent.trim(); msTags.appendChild( s ); } );
			msCount.textContent = fmt( t.chosen, on.length );
			msBtn.classList.toggle( 'is-ok', on.length > 0 );
			if ( on.length ) { clearError( msBtn ); }
		};
		var open = function ( yes ) { msList.hidden = ! yes; msBtn.setAttribute( 'aria-expanded', yes ? 'true' : 'false' ); if ( yes && boxes[ 0 ] ) { boxes[ 0 ].focus(); } };
		msBtn.addEventListener( 'click', function () { open( msList.hidden ); } );
		$( '[data-wz-ms-ok]', ms ).addEventListener( 'click', function () { open( false ); msBtn.focus(); } );
		boxes.forEach( function ( b ) { b.addEventListener( 'change', function () { paint(); save(); } ); } );
		paintMs = paint;
		document.addEventListener( 'click', function ( e ) { if ( ! msList.hidden && ! ms.contains( e.target ) ) { open( false ); } } );
		ms.addEventListener( 'keydown', function ( e ) { if ( 'Escape' === e.key && ! msList.hidden ) { e.stopPropagation(); open( false ); msBtn.focus(); } } );
		paint();
	}

	/* ---- Documents: name, size, lighter photos --------------------------- */
	var maxBytes = ( cfg.maxMb || 10 ) * 1048576;
	function lighter( file, done, bar ) {
		if ( ! /^image\/(jpeg|png|webp)$/.test( file.type ) || file.size < 600000 || ! window.createImageBitmap || ! window.DataTransfer ) { done( file ); return; }
		bar.hidden = false; bar.firstElementChild.style.inlineSize = '35%';
		createImageBitmap( file ).then( function ( img ) {
			var max = 1800, k = Math.min( 1, max / Math.max( img.width, img.height ) );
			var c = document.createElement( 'canvas' );
			c.width = Math.round( img.width * k ); c.height = Math.round( img.height * k );
			var g = c.getContext( '2d' ); g.fillStyle = '#fff'; g.fillRect( 0, 0, c.width, c.height ); g.drawImage( img, 0, 0, c.width, c.height );
			bar.firstElementChild.style.inlineSize = '75%';
			c.toBlob( function ( blob ) {
				bar.firstElementChild.style.inlineSize = '100%';
				setTimeout( function () { bar.hidden = true; }, 300 );
				if ( ! blob || blob.size >= file.size ) { done( file ); return; }
				done( new File( [ blob ], file.name.replace( /\.(png|webp|jpe?g)$/i, '' ) + '.jpg', { type: 'image/jpeg' } ), file.size );
			}, 'image/jpeg', 0.82 );
		} ).catch( function () { bar.hidden = true; done( file ); } );
	}
	var sizeText = function ( b ) { return b > 1048576 ? ( b / 1048576 ).toFixed( 1 ).replace( '.', ',' ) + ' Mo' : Math.max( 1, Math.round( b / 1024 ) ) + ' Ko'; };
	$$( '[data-wz-file]' ).forEach( function ( input ) {
		var doc = input.closest( '[data-wz-doc]' ), info = $( '[data-wz-doc-info]', doc ), bar = $( '[data-wz-doc-bar]', doc ), btn = $( '[data-wz-doc-btn]', doc );
		var first = info.textContent;
		input.addEventListener( 'change', function () {
			var f = input.files && input.files[ 0 ];
			clearError( input ); doc.classList.remove( 'is-bad' );
			if ( ! f ) { doc.classList.remove( 'is-done' ); info.textContent = first; return; }
			info.textContent = t.compress;
			lighter( f, function ( out, before ) {
				if ( out !== f ) { var dt = new DataTransfer(); dt.items.add( out ); input.files = dt.files; }
				if ( out.size > maxBytes ) {
					doc.classList.add( 'is-bad' ); showError( input, fmt( t.tooBig, cfg.maxMb ) ); info.textContent = first; return;
				}
				doc.classList.add( 'is-done' );
				info.textContent = out.name + ' · ' + ( before ? sizeText( before ) + ' → ' : '' ) + sizeText( out.size );
				btn.textContent = t.replace;
			}, bar );
		} );
	} );

	/* ---- Password -------------------------------------------------------- */
	var pass = $( '[data-wz-pass]' ), pass2 = $( '[data-wz-pass2]' );
	if ( pass ) {
		var meter = $( '[data-wz-meter]' ), meterText = $( '[data-wz-meter-text]' ), p2Text = $( '[data-wz-pass2-text]' );
		var score = function ( v ) { var s = 0; if ( v.length >= 10 ) { s++; } if ( v.length >= 14 ) { s++; } if ( /[A-Z]/.test( v ) && /[a-z]/.test( v ) ) { s++; } if ( /\d/.test( v ) && /[^A-Za-z0-9]/.test( v ) ) { s++; } return v.length < 10 ? Math.min( s, 1 ) : Math.max( s, 1 ); };
		var check = function () {
			var v = pass.value, s = v ? score( v ) : 0;
			$$( 'i', meter ).forEach( function ( i, k ) { i.classList.toggle( 'on', k < s ); } );
			meter.classList.toggle( 'is-weak', v.length > 0 && v.length < 10 );
			meterText.textContent = ! v ? t.short : ( v.length < 10 ? t.short : [ t.weak, t.fair, t.good, t.strong ][ Math.max( 0, s - 1 ) ] + ' · ' + t.short );
			meterText.className = 'hint' + ( v.length >= 10 ? ' is-good' : ( v ? ' is-bad' : '' ) );
			pass.classList.toggle( 'is-ok', v.length >= 10 );
			if ( pass2.value ) {
				var same = pass2.value === v;
				p2Text.textContent = same ? '✓ ' + t.identical : t.same;
				p2Text.className = 'hint ' + ( same ? 'is-good' : 'is-bad' );
				pass2.classList.toggle( 'is-ok', same && v.length >= 10 );
			} else { p2Text.textContent = ''; }
		};
		pass.addEventListener( 'input', check ); pass2.addEventListener( 'input', check );
	}

	/* ---- Check a step ---------------------------------------------------- */
	var phoneOk = function ( v ) { return /^0[567]\d{8}$/.test( v.replace( /[\s.\-]/g, '' ).replace( /^\+?213/, '0' ) ); };
	function validStep( n ) {
		var panel = $( '[data-wz-step="' + n + '"]' ), first = null;
		$$( 'input, select, textarea', panel ).forEach( function ( el ) {
			if ( el.disabled || el.closest( '[hidden]' ) || 'checkbox' === el.type && el.name === 'specialties[]' || 'radio' === el.type ) { return; }
			clearError( el );
			var bad = '';
			if ( 'file' === el.type ) {
				if ( el.getAttribute( 'data-required' ) && ! ( el.files && el.files.length ) ) { bad = t.file; el.closest( '[data-wz-doc]' ).classList.add( 'is-bad' ); }
			} else if ( 'checkbox' === el.type ) {
				if ( el.required && ! el.checked ) { bad = t.required; }
			} else if ( el.hasAttribute( 'data-wz-bet-required' ) ) {
				if ( el.value.trim().length < 2 ) { bad = t.required; }
			} else if ( el.required && ! el.value.trim() ) {
				bad = t.required;
			} else if ( 'email' === el.type && el.value && ! /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test( el.value.trim() ) ) {
				bad = t.email;
			} else if ( el.hasAttribute( 'data-wz-phone' ) && el.value && ! phoneOk( el.value ) ) {
				bad = t.phone;
			} else if ( el.minLength > 0 && el.value.trim().length < el.minLength ) {
				bad = el === pass ? t.short : t.required;
			} else if ( el === pass2 && el.value !== pass.value ) {
				bad = t.same;
			}
			if ( bad ) { showError( el, bad ); first = first || el; }
		} );
		if ( ms && panel.contains( ms ) && ! $$( 'input:checked', ms ).length ) {
			var b = $( '[data-wz-ms-btn]', ms ); showError( b, t.services ); first = first || b;
		}
		if ( first ) { first.focus(); first.scrollIntoView && first.scrollIntoView( { block: 'center' } ); }
		return ! first;
	}

	/* ---- Show a step ----------------------------------------------------- */
	function show( n, push ) {
		if ( order.indexOf( n ) < 0 ) { n = order[ 0 ]; }
		current = n;
		var pos = order.indexOf( n );
		panels.forEach( function ( p ) { p.classList.toggle( 'is-on', parseInt( p.getAttribute( 'data-wz-step' ), 10 ) === n ); } );
		$$( '[data-wz-nav]' ).forEach( function ( li ) {
			var k = order.indexOf( parseInt( li.getAttribute( 'data-wz-nav' ), 10 ) );
			li.className = k < pos ? 'is-done' : ( k === pos ? 'is-on' : '' );
			li.querySelector( 'b' ).textContent = k < pos ? '✓' : String( k + 1 );
			if ( k === pos ) { li.setAttribute( 'aria-current', 'step' ); } else { li.removeAttribute( 'aria-current' ); }
		} );
		$( '[data-wz-count]' ).textContent = fmt( t.step, pos + 1, order.length );
		$( '[data-wz-name]' ).textContent = names[ n ] || '';
		$( '[data-wz-progress]' ).style.inlineSize = Math.round( ( pos + 1 ) / order.length * 100 ) + '%';
		btnPrev.hidden = 0 === pos;
		var last = pos === order.length - 1;
		btnNext.hidden = last; btnSubmit.hidden = ! last;
		body.scrollTop = 0;
		if ( push !== false && window.history && history.replaceState ) {
			try { history[ push ? 'pushState' : 'replaceState' ]( { wz: n }, '', '#etape-' + ( pos + 1 ) ); } catch ( e ) {}
		}
		if ( 5 === n ) { enterVerification(); }
		var h = $( '.wz__h', $( '[data-wz-step="' + n + '"]' ) );
		if ( h && push ) { h.setAttribute( 'tabindex', '-1' ); h.focus( { preventScroll: true } ); }
	}
	btnNext.addEventListener( 'click', function () {
		if ( ! validStep( current ) ) { return; }
		save();
		show( order[ order.indexOf( current ) + 1 ], true );
	} );
	btnPrev.addEventListener( 'click', function () { show( order[ Math.max( 0, order.indexOf( current ) - 1 ) ], true ); } );
	window.addEventListener( 'popstate', function ( e ) { if ( e.state && e.state.wz ) { show( e.state.wz, false ); } } );
	$$( '[data-wz-go]' ).forEach( function ( b ) { b.addEventListener( 'click', function () { show( parseInt( b.getAttribute( 'data-wz-go' ), 10 ), true ); } ); } );
	form.addEventListener( 'keydown', function ( e ) {
		if ( 'Enter' === e.key && 'TEXTAREA' !== e.target.tagName && 'BUTTON' !== e.target.tagName && ! btnNext.hidden ) { e.preventDefault(); btnNext.click(); }
	} );

	/* ---- Draft on this device (texts only) ------------------------------- */
	var draftKey = cfg.draft + ( cfg.guest ? '' : '-again' ), saved = $( '[data-wz-saved]' ), timer;
	var skip = function ( el ) { return ! el.name || 'file' === el.type || 'password' === el.type || /^_|^action$|^sm_|^redirect_to$/.test( el.name ); };
	function save() {
		clearTimeout( timer );
		timer = setTimeout( function () {
			var data = {};
			$$( 'input, select, textarea' ).forEach( function ( el ) {
				if ( skip( el ) ) { return; }
				if ( 'checkbox' === el.type ) { data[ el.name ] = data[ el.name ] || []; if ( el.checked ) { data[ el.name ].push( el.value ); } return; }
				if ( 'radio' === el.type ) { if ( el.checked ) { data[ el.name ] = el.value; } return; }
				data[ el.name ] = el.value;
			} );
			try { localStorage.setItem( draftKey, JSON.stringify( { v: data, at: Date.now() } ) ); saved.hidden = false; } catch ( e ) {}
		}, 400 );
	}
	function restore() {
		var d = null;
		try { d = JSON.parse( localStorage.getItem( draftKey ) || 'null' ); } catch ( e ) {}
		if ( ! d || ! d.v || Date.now() - d.at > 30 * 86400000 ) { return false; }
		var hasServer = !! form.querySelector( '.sm-field-error, [aria-invalid="true"]' );
		$$( 'input, select, textarea' ).forEach( function ( el ) {
			if ( skip( el ) || ! ( el.name in d.v ) ) { return; }
			var val = d.v[ el.name ];
			if ( 'checkbox' === el.type ) { if ( ! hasServer ) { el.checked = Array.isArray( val ) && val.indexOf( el.value ) > -1; } return; }
			if ( 'radio' === el.type ) { el.checked = el.value === val; return; }
			if ( ! el.value || ! hasServer ) { el.value = val; }
		} );
		saved.hidden = false;
		return true;
	}
	form.addEventListener( 'input', save );
	form.addEventListener( 'change', save );

	/* ---- Summary and e-mail code ----------------------------------------- */
	var val = function ( n ) { var el = form.elements[ n ]; return el ? String( el.value || '' ).trim() : ''; };
	var text = function ( sel ) { var el = $( sel ); return el && el.selectedIndex > 0 ? el.options[ el.selectedIndex ].text : ''; };
	function line( parts, strong ) { var p = document.createElement( 'p' ); parts.filter( Boolean ).forEach( function ( x, i ) { if ( i ) { p.appendChild( document.createTextNode( ' · ' ) ); } var s = document.createElement( i === 0 && strong ? 'strong' : 'span' ); s.textContent = x; p.appendChild( s ); } ); return p; }
	function recap() {
		var r1 = $( '[data-wz-recap="1"]' ), r2 = $( '[data-wz-recap="2"]' ), r3 = $( '[data-wz-recap="3"]' ), r4 = $( '[data-wz-recap="4"]' );
		if ( ! r1 ) { return; }
		[ r1, r2, r3, r4 ].forEach( function ( r ) { r.innerHTML = ''; } );
		r1.appendChild( line( [ val( 'first_name' ) + ' ' + val( 'last_name' ) ], true ) );
		r1.appendChild( line( [ val( 'phone' ), text( '#w-wilaya' ).replace( /^\d+\s—\s/, '' ) ] ) );
		var em = line( [ val( 'email' ) ] ); em.setAttribute( 'dir', 'ltr' ); r1.appendChild( em );
		var deg = 'other' === val( 'degree' ) ? val( 'degree_other' ) : text( '#w-degree' );
		var spe = 'other' === val( 'speciality' ) ? val( 'speciality_other' ) : text( '#w-spec' );
		if ( isBet() ) { r2.appendChild( line( [ val( 'bet_name' ) ], true ) ); }
		r2.appendChild( line( [ deg, spe, fmt( t.years, val( 'experience' ) ) ] ) );
		r2.appendChild( line( [ $$( '[data-wz-ms] input:checked' ).map( function ( b ) { return b.parentNode.textContent.trim(); } ).join( ', ' ) ] ) );
		r3.appendChild( line( [ $$( '[data-wz-doc]' ).filter( function ( d ) { return ! d.hidden && d.classList.contains( 'is-done' ); } ).map( function ( d ) { return '✓ ' + $( 'strong', d ).firstChild.textContent.trim(); } ).join( ' · ' ) ] ) );
		r4.appendChild( line( [ '✓ ' + ( pass ? $( 'label[for="w-pass"]' ).textContent : '' ), $( 'input[name="terms"]' ).checked ? '✓ ' + $( '.wz__check span' ).textContent.split( ' ' ).slice( 0, 3 ).join( ' ' ) + '…' : '' ] ) );
	}
	var otpBoxes = $$( '[data-wz-boxes] input' ), otpMsg = $( '[data-wz-otp-msg]' ), otpTo = $( '[data-wz-otp-to] span' ), resend = $( '[data-wz-resend]' );
	var sentFor = '', wait = 0, tick, confirmed = '';
	function post( action, extra ) {
		var fd = new FormData();
		fd.append( 'action', action );
		fd.append( '_sm_nonce', form.querySelector( 'input[name="_sm_nonce"]' ).value );
		fd.append( 'sm_lang', ( form.querySelector( 'input[name="sm_lang"]' ) || {} ).value || '' );
		fd.append( 'email', val( 'email' ) );
		Object.keys( extra || {} ).forEach( function ( k ) { fd.append( k, extra[ k ] ); } );
		return fetch( cfg.ajax, { method: 'POST', body: fd, credentials: 'same-origin' } ).then( function ( r ) { return r.json(); } );
	}
	function countdown( s ) {
		wait = s; clearInterval( tick );
		var paint = function () {
			resend.disabled = wait > 0;
			resend.textContent = wait > 0 ? fmt( t.resendIn, '0:' + ( wait < 10 ? '0' : '' ) + wait ) : t.resend;
		};
		paint();
		tick = setInterval( function () { wait--; paint(); if ( wait <= 0 ) { clearInterval( tick ); } }, 1000 );
	}
	function sendCode() {
		otpMsg.className = 'wz__otp-msg'; otpMsg.textContent = t.sending; resend.disabled = true;
		return post( 'sm_eng_code' ).then( function ( j ) {
			if ( j && j.success ) { sentFor = val( 'email' ); otpMsg.textContent = ''; countdown( j.data.wait || cfg.resend ); otpBoxes[ 0 ].focus(); return; }
			otpMsg.textContent = ( j && j.data && j.data.message ) || t.network;
			if ( j && j.data && 'email' === j.data.field ) { show( 1, true ); showError( $( '#w-email' ), j.data.message ); }
			countdown( 0 );
		} ).catch( function () { otpMsg.textContent = t.network; countdown( 0 ); } );
	}
	function enterVerification() {
		recap();
		if ( ! otpTo ) { return; }
		otpTo.textContent = fmt( t.sentTo, val( 'email' ) );
		if ( sentFor !== val( 'email' ) && confirmed !== val( 'email' ) ) { otpBoxes.forEach( function ( b ) { b.value = ''; b.classList.remove( 'is-filled' ); } ); sendCode(); }
	}
	if ( resend ) { resend.addEventListener( 'click', function () { sentFor = ''; sendCode(); } ); }
	otpBoxes.forEach( function ( b, i ) {
		b.addEventListener( 'input', function () {
			var digits = b.value.replace( /\D/g, '' );
			if ( digits.length > 1 ) { digits.split( '' ).slice( 0, 6 - i ).forEach( function ( d, k ) { otpBoxes[ i + k ].value = d; otpBoxes[ i + k ].classList.add( 'is-filled' ); } ); ( otpBoxes[ Math.min( 5, i + digits.length ) ] ).focus(); return; }
			b.value = digits; b.classList.toggle( 'is-filled', !! digits );
			$( '[data-wz-boxes]' ).classList.remove( 'is-bad' );
			if ( digits && otpBoxes[ i + 1 ] ) { otpBoxes[ i + 1 ].focus(); }
		} );
		b.addEventListener( 'keydown', function ( e ) { if ( 'Backspace' === e.key && ! b.value && otpBoxes[ i - 1 ] ) { otpBoxes[ i - 1 ].focus(); } } );
		b.addEventListener( 'paste', function ( e ) { var v = ( e.clipboardData || window.clipboardData ).getData( 'text' ).replace( /\D/g, '' ).slice( 0, 6 ); if ( v.length > 1 ) { e.preventDefault(); v.split( '' ).forEach( function ( d, k ) { otpBoxes[ k ].value = d; otpBoxes[ k ].classList.add( 'is-filled' ); } ); otpBoxes[ Math.min( 5, v.length ) ].focus(); } } );
	} );

	/* ---- Sending --------------------------------------------------------- */
	var sending = false;
	form.addEventListener( 'submit', function ( e ) {
		if ( sending ) { return; }
		e.preventDefault();
		var bad = order.filter( function ( n ) { return 5 !== n; } ).filter( function ( n ) { return ! validStep( n ); } );
		if ( bad.length ) { show( bad[ 0 ], true ); validStep( bad[ 0 ] ); return; }
		var go = function () {
			sending = true;
			btnSubmit.disabled = true; btnSubmit.classList.add( 'is-busy' );
			try { localStorage.removeItem( draftKey ); } catch ( x ) {}
			form.submit();
		};
		if ( ! cfg.guest || ! otpBoxes.length ) { go(); return; }
		var code = otpBoxes.map( function ( b ) { return b.value; } ).join( '' );
		if ( confirmed === val( 'email' ) ) { go(); return; }
		if ( ! /^\d{6}$/.test( code ) ) { otpMsg.className = 'wz__otp-msg'; otpMsg.textContent = t.codeLen; otpBoxes[ code.length < 6 ? code.length : 0 ].focus(); return; }
		otpMsg.className = 'wz__otp-msg is-good'; otpMsg.textContent = t.checking; btnSubmit.disabled = true;
		post( 'sm_eng_check', { code: code } ).then( function ( j ) {
			btnSubmit.disabled = false;
			if ( j && j.success ) { confirmed = val( 'email' ); go(); return; }
			otpMsg.className = 'wz__otp-msg'; otpMsg.textContent = ( j && j.data && j.data.message ) || t.network;
			$( '[data-wz-boxes]' ).classList.add( 'is-bad' );
		} ).catch( function () { btnSubmit.disabled = false; otpMsg.textContent = t.network; } );
	} );

	/* ---- Start ----------------------------------------------------------- */
	restore();
	syncBet();
	$$( 'select[data-wz-other]' ).forEach( function ( s ) { s.dispatchEvent( new Event( 'change' ) ); } );
	paintMs();
	// After an error sent back by the server: open the step of the first field in error.
	var serverErr = form.querySelector( '[aria-invalid="true"], .sm-field-error' );
	var start = order[ 0 ];
	if ( serverErr ) {
		var p = serverErr.closest( '[data-wz-step]' );
		if ( p ) { start = parseInt( p.getAttribute( 'data-wz-step' ), 10 ); }
		var filesOut = $( '[data-wz-filesout]' );
		if ( filesOut ) { filesOut.hidden = false; }
	} else if ( /^#etape-(\d)$/.test( location.hash ) ) {
		start = order[ Math.min( order.length - 1, parseInt( location.hash.slice( 7 ), 10 ) - 1 ) ] || order[ 0 ];
		// A later step is opened only when the steps before it are filled.
		for ( var k = 0; k < order.indexOf( start ); k++ ) { if ( 3 === order[ k ] || ! validStepQuiet( order[ k ] ) ) { start = order[ k ]; break; } }
	}
	function validStepQuiet( n ) {
		return $$( 'input[required], select[required], textarea[required]', $( '[data-wz-step="' + n + '"]' ) ).every( function ( el ) { return el.disabled || el.closest( '[hidden]' ) || ( 'checkbox' === el.type ? el.checked : !! el.value.trim() ); } );
	}
	show( start, false );
} )();
