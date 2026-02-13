/**
 * WPZOOM Notice Center – Carousel & Dismiss Logic
 *
 * Vanilla JS (no jQuery dependency).
 *
 * @package WPZOOM_Notice_Center
 * @version 1.0.0
 */
( function () {
	'use strict';

	var container, slides, dots, currentIndex, total;
	var DEFAULT_TIMEOUT = 6;
	var rotateTimer = null;

	function init() {
		container = document.getElementById( 'wpzoom-notice-center' );

		if ( ! container ) {
			// Single-notice mode — just bind dismiss buttons.
			bindSingleDismiss();
			return;
		}

		slides         = container.querySelectorAll( '.wpzoom-nc-slide' );
		dots           = container.querySelectorAll( '.wpzoom-nc-dot' );
		currentIndex   = 0;
		total          = slides.length;

		// Dot navigation.
		dots.forEach( function ( dot ) {
			dot.addEventListener( 'click', function () {
				clearRotateTimer();
				goTo( parseInt( dot.dataset.slideIndex, 10 ) );
				startRotateTimer();
			} );
		} );

		// Dismiss current slide (button in header).
		var dismissBtn = container.querySelector( '.wpzoom-nc-dismiss-slide' );
		if ( dismissBtn ) {
			dismissBtn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				clearRotateTimer();
				var activeSlide = container.querySelector( '.wpzoom-nc-slide--active' );
				if ( activeSlide && activeSlide.dataset.noticeId ) {
					dismissNotice( activeSlide.dataset.noticeId, false, function () {
						removeSlide( activeSlide );
					} );
				}
			} );
		}

		startRotateTimer();
		requestAnimationFrame( function () {
			updateViewportHeight();
		} );
	}

	function clearRotateTimer() {
		if ( rotateTimer ) {
			clearTimeout( rotateTimer );
			rotateTimer = null;
		}
	}

	function getSlideTimeoutMs( slideIndex ) {
		if ( ! slides[ slideIndex ] ) {
			return DEFAULT_TIMEOUT * 1000;
		}
		var timeout = parseInt( slides[ slideIndex ].dataset.slideTimeout, 10 );
		return ( isNaN( timeout ) || timeout < 1 ? DEFAULT_TIMEOUT : timeout ) * 1000;
	}

	function startRotateTimer() {
		clearRotateTimer();
		if ( total <= 0 ) {
			return;
		}
		rotateTimer = setTimeout( function () {
			rotateTimer = null;
			goTo( currentIndex + 1 );
			startRotateTimer();
		}, getSlideTimeoutMs( currentIndex ) );
	}

	/**
	 * Navigate to a slide by index (wraps around).
	 */
	function goTo( index ) {
		if ( total <= 0 ) {
			return;
		}
		if ( index < 0 ) {
			index = total - 1;
		}
		if ( index >= total ) {
			index = 0;
		}

		slides[ currentIndex ].classList.remove( 'wpzoom-nc-slide--active' );
		if ( dots[ currentIndex ] ) {
			dots[ currentIndex ].classList.remove( 'wpzoom-nc-dot--active' );
		}

		currentIndex = index;

		slides[ currentIndex ].classList.add( 'wpzoom-nc-slide--active' );
		if ( dots[ currentIndex ] ) {
			dots[ currentIndex ].classList.add( 'wpzoom-nc-dot--active' );
		}

		updateViewportHeight();
	}

	function updateViewportHeight() {
		var viewport = container && container.querySelector( '.wpzoom-nc-slides-viewport' );
		var activeSlide = slides[ currentIndex ];
		if ( viewport && activeSlide ) {
			viewport.style.height = activeSlide.offsetHeight + 'px';
		}
	}

	/**
	 * Remove a slide from the DOM after dismissal.
	 */
	function removeSlide( slideEl ) {
		if ( ! slideEl ) {
			return;
		}

		slideEl.style.transition = 'opacity 0.25s ease';
		slideEl.style.opacity    = '0';

		setTimeout( function () {
			slideEl.remove();

			// Rebuild references.
			slides = container.querySelectorAll( '.wpzoom-nc-slide' );
			total  = slides.length;

			// Remove the corresponding dot.
			var dotsContainer = container.querySelector( '.wpzoom-nc-dots' );
			if ( dotsContainer ) {
				var oldDots = dotsContainer.querySelectorAll( '.wpzoom-nc-dot' );
				if ( oldDots.length > total ) {
					oldDots[ oldDots.length - 1 ].remove();
				}
			}
			dots = container.querySelectorAll( '.wpzoom-nc-dot' );

			// Re-index data attributes.
			slides.forEach( function ( s, i ) {
				s.dataset.slideIndex = i;
			} );
			dots.forEach( function ( d, i ) {
				d.dataset.slideIndex = i;
			} );

			// Nothing left — remove the entire container.
			if ( total === 0 ) {
				container.style.transition = 'opacity 0.3s ease';
				container.style.opacity    = '0';
				setTimeout( function () {
					container.remove();
				}, 300 );
				return;
			}

			if ( currentIndex >= total ) {
				currentIndex = total - 1;
			}
			goTo( currentIndex );
			startRotateTimer();
		}, 250 );
	}

	/**
	 * Send an AJAX request to dismiss a notice (or all notices).
	 */
	function dismissNotice( noticeId, dismissAll, onSuccess ) {
		var data = new FormData();
		data.append( 'action', 'wpzoom_notice_center_dismiss' );
		data.append( 'nonce', wpzoomNoticeCenterData.nonce );
		data.append( 'notice_id', noticeId );
		data.append( 'dismiss_all', dismissAll ? 'true' : 'false' );

		fetch( wpzoomNoticeCenterData.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: data,
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( result ) {
				if ( result.success && onSuccess ) {
					onSuccess();
				}
			} );
	}

	/**
	 * Bind dismiss buttons for single-notice mode.
	 */
	function bindSingleDismiss() {
		document.querySelectorAll( '.wpzoom-nc-dismiss-single' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var noticeId = btn.dataset.noticeId;
				var noticeEl = btn.closest( '.wpzoom-notice-single' );
				dismissNotice( noticeId, false, function () {
					if ( noticeEl ) {
						noticeEl.style.transition = 'opacity 0.3s ease';
						noticeEl.style.opacity    = '0';
						setTimeout( function () {
							noticeEl.remove();
						}, 300 );
					}
				} );
			} );
		} );
	}

	// Initialize when DOM is ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
