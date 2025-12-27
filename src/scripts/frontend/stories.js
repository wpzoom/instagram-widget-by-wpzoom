/**
 * Instagram Stories using Zuck.js
 *
 * @package instagram-widget-by-wpzoom
 * @see https://github.com/ramonszo/zuck.js
 */

import { Zuck } from 'zuck.js';
import 'zuck.js/css';
import 'zuck.js/skins/snapgram';

( function( $ ) {
	'use strict';

	// Inject CSS fixes for iOS Safari viewport height issue and modal styling
	function injectModalStyles() {
		const styleId = 'wpz-insta-stories-modal-fix';
		if ( document.getElementById( styleId ) ) {
			return;
		}

		const style = document.createElement( 'style' );
		style.id = styleId;
		style.textContent = `
			/* Body scroll lock when stories modal is open */
			body.wpz-insta-stories-open {
				overflow: hidden !important;
				position: fixed !important;
				width: 100% !important;
				height: 100% !important;
			}

			/* Full-screen overlay background */
			.wpz-insta-stories-overlay {
				position: fixed !important;
				top: 0 !important;
				left: 0 !important;
				right: 0 !important;
				bottom: 0 !important;
				width: 100% !important;
				height: 100% !important;
				background: #000 !important;
				z-index: 99999 !important;
				display: none;
			}
			.wpz-insta-stories-overlay.active {
				display: block !important;
			}

			/* Fix iOS Safari viewport height issue */
			#zuck-modal {
				position: fixed !important;
				top: 0 !important;
				left: 0 !important;
				right: 0 !important;
				bottom: 0 !important;
				width: 100% !important;
				height: 100% !important;
				height: 100dvh !important;
				min-height: -webkit-fill-available !important;
				z-index: 100000 !important;
				background: #000 !important;
			}
			#zuck-modal-content,
			#zuck-modal-content .story-viewer,
			#zuck-modal-content .story-viewer > .slides,
			#zuck-modal-content .story-viewer > .slides > * {
				height: 100% !important;
				height: 100dvh !important;
				min-height: -webkit-fill-available !important;
			}
			/* Ensure the slider also uses full height */
			#zuck-modal .slider {
				height: 100% !important;
				height: 100dvh !important;
				min-height: -webkit-fill-available !important;
			}
			#zuck-modal .slider > * {
				height: 100% !important;
				height: 100dvh !important;
				min-height: -webkit-fill-available !important;
			}
		`;
		document.head.appendChild( style );

		// Create overlay element
		if ( ! document.getElementById( 'wpz-insta-stories-overlay' ) ) {
			const overlay = document.createElement( 'div' );
			overlay.id = 'wpz-insta-stories-overlay';
			overlay.className = 'wpz-insta-stories-overlay';
			document.body.appendChild( overlay );
		}
	}

	// Show/hide overlay and lock body scroll
	function showModalOverlay() {
		const overlay = document.getElementById( 'wpz-insta-stories-overlay' );
		if ( overlay ) {
			overlay.classList.add( 'active' );
		}
		document.body.classList.add( 'wpz-insta-stories-open' );
		// Store scroll position
		document.body.dataset.scrollY = window.scrollY;
	}

	function hideModalOverlay() {
		const overlay = document.getElementById( 'wpz-insta-stories-overlay' );
		if ( overlay ) {
			overlay.classList.remove( 'active' );
		}
		document.body.classList.remove( 'wpz-insta-stories-open' );
		// Restore scroll position
		const scrollY = document.body.dataset.scrollY;
		if ( scrollY ) {
			window.scrollTo( 0, parseInt( scrollY, 10 ) );
		}
	}

	// Inject modal styles immediately
	injectModalStyles();

	// Track initialized containers to prevent duplicate initialization
	const initializedContainers = new Set();

	// Store active Zuck instance for keyboard navigation
	let activeZuckInstance = null;

	// Counter for unique instance IDs
	let instanceCounter = 0;

	// Track if touch handlers are set up
	let touchHandlersInitialized = false;

	/**
	 * Setup touch handlers:
	 * - Block horizontal swipes (no user switching in single-user mode)
	 * - Swipe down to close the modal
	 */
	function setupTouchHandlers() {
		if ( touchHandlersInitialized ) {
			return;
		}
		touchHandlersInitialized = true;

		let touchStartX = 0;
		let touchStartY = 0;
		let touchMoveY = 0;
		let isTracking = false;

		// Intercept touch events on the modal
		document.addEventListener( 'touchstart', function( e ) {
			const $modal = $( '#zuck-modal' );
			if ( ! $modal.is( ':visible' ) ) {
				return;
			}

			const $target = $( e.target );
			// Only track if touch is on the story viewer (not on close button, etc.)
			if ( ! $target.closest( '.story-viewer' ).length ) {
				return;
			}

			const touch = e.touches[ 0 ];
			touchStartX = touch.clientX;
			touchStartY = touch.clientY;
			touchMoveY = touchStartY;
			isTracking = true;
		}, { passive: true } );

		document.addEventListener( 'touchmove', function( e ) {
			if ( ! isTracking ) {
				return;
			}

			const $modal = $( '#zuck-modal' );
			if ( ! $modal.is( ':visible' ) ) {
				isTracking = false;
				return;
			}

			const touch = e.touches[ 0 ];
			const deltaX = Math.abs( touch.clientX - touchStartX );
			const deltaY = touch.clientY - touchStartY;
			touchMoveY = touch.clientY;

			// If this is a horizontal swipe, block it
			if ( deltaX > 30 && deltaX > Math.abs( deltaY ) ) {
				e.preventDefault();
				e.stopPropagation();
			}
		}, { passive: false } );

		document.addEventListener( 'touchend', function( e ) {
			if ( ! isTracking ) {
				return;
			}

			const $modal = $( '#zuck-modal' );
			if ( ! $modal.is( ':visible' ) ) {
				isTracking = false;
				return;
			}

			const deltaY = touchMoveY - touchStartY;
			const deltaX = Math.abs( e.changedTouches[ 0 ].clientX - touchStartX );

			isTracking = false;

			// Swipe down to close (vertical movement > 100px and more vertical than horizontal)
			if ( deltaY > 100 && deltaY > deltaX ) {
				// Find and click the close button
				const $closeBtn = $modal.find( '.close' );
				if ( $closeBtn.length ) {
					$closeBtn.get( 0 ).click();
				}
			}
		}, { passive: true } );
	}

	/**
	 * Add global keyboard handler for arrow key navigation
	 */
	function setupGlobalKeyboardHandler() {
		$( document ).on( 'keydown.wpzInstaStories', function( e ) {
			const $modal = $( '#zuck-modal' );

			// Only handle if modal is visible and we have an active instance
			if ( ! $modal.is( ':visible' ) || ! activeZuckInstance ) {
				return;
			}

			// Arrow Right or Arrow Down - next item
			if ( e.key === 'ArrowRight' || e.key === 'ArrowDown' ) {
				e.preventDefault();
				activeZuckInstance.navigateItem( 'next', e );
			}

			// Arrow Left or Arrow Up - previous item
			if ( e.key === 'ArrowLeft' || e.key === 'ArrowUp' ) {
				e.preventDefault();
				activeZuckInstance.navigateItem( 'previous', e );
			}
		} );
	}

	/**
	 * Initialize Instagram Stories with Zuck.js
	 */
	function initInstagramStories() {
		// Skip initialization in feed editor preview (iframe) or admin context
		if ( window.location.search.indexOf( 'wpz-insta-widget-preview' ) !== -1 ) {
			return;
		}

		// Find all story containers
		$( '.wpz-insta-stories' ).each( function() {
			const $container = $( this );
			const containerElement = $container.get( 0 );

			// Skip if already initialized
			if ( initializedContainers.has( containerElement ) ) {
				return;
			}

			const storiesDataAttr = $container.attr( 'data-stories' );

			if ( ! storiesDataAttr ) {
				return;
			}

			let storiesData;
			try {
				storiesData = JSON.parse( storiesDataAttr );
			} catch ( e ) {
				console.error( 'Instagram Stories: Failed to parse stories data', e );
				return;
			}

			if ( ! storiesData || ! storiesData.items || storiesData.items.length === 0 ) {
				return;
			}

			// Mark as initialized
			initializedContainers.add( containerElement );

			// Generate unique instance ID for this feed
			const instanceId = ++instanceCounter;

			// Transform data to Zuck.js format
			// Use instanceId to ensure unique story IDs across different feeds
			const timeline = [
				{
					id: 'wpz-feed-' + instanceId + '-' + ( storiesData.id || 'story' ),
					photo: storiesData.photo || '',
					name: storiesData.name || '',
					link: storiesData.link || '',
					lastUpdated: storiesData.lastUpdated || Math.floor( Date.now() / 1000 ),
					seen: false,
					items: storiesData.items.map( ( item, index ) => ( {
						id: item.id || 'item-' + index,
						type: item.type || 'photo', // 'photo' or 'video'
						src: item.src || '',
						preview: item.preview || item.src || '',
						length: item.length || ( item.type === 'video' ? 0 : 5 ), // 0 = use video duration
						link: item.link || '',
						linkText: item.linkText || 'View on Instagram',
						time: item.time || Math.floor( Date.now() / 1000 ),
						seen: false,
					} ) ),
				},
			];

			// Create a unique container for Zuck.js
			// Use instanceId to ensure each feed has its own container
			const storiesContainerId = 'wpz-insta-zuck-' + instanceId;

			// Create new container for this feed instance
			const $storiesContainer = $( '<div>' )
				.attr( 'id', storiesContainerId )
				.addClass( 'wpz-insta-zuck-container' )
				.css( {
					position: 'absolute',
					left: '-9999px',
					top: '-9999px',
					width: '1px',
					height: '1px',
					overflow: 'hidden',
				} );

			// Append to body for the modal to work properly
			$( 'body' ).append( $storiesContainer );

			// Initialize Zuck.js
			const zuckInstance = new Zuck( $storiesContainer.get( 0 ), {
				skin: 'snapgram',
				avatars: true,
				list: false,
				cubeEffect: true,
				autoFullScreen: false,
				backButton: true,
				backNative: false, // Disable to prevent URL hash issues
				previousTap: true,
				localStorage: false,
				stories: timeline,
				language: {
					unmute: ( typeof wpzInstaStories !== 'undefined' && wpzInstaStories?.i18n?.unmute ) || 'Touch to unmute',
					keyboardTip: ( typeof wpzInstaStories !== 'undefined' && wpzInstaStories?.i18n?.keyboardTip ) || 'Press space to see next',
					visitLink: ( typeof wpzInstaStories !== 'undefined' && wpzInstaStories?.i18n?.visitLink ) || 'Visit link',
					time: {
						ago: ( typeof wpzInstaStories !== 'undefined' && wpzInstaStories?.i18n?.ago ) || 'ago',
						hour: ( typeof wpzInstaStories !== 'undefined' && wpzInstaStories?.i18n?.hour ) || 'hour',
						hours: ( typeof wpzInstaStories !== 'undefined' && wpzInstaStories?.i18n?.hours ) || 'hours',
						minute: ( typeof wpzInstaStories !== 'undefined' && wpzInstaStories?.i18n?.minute ) || 'minute',
						minutes: ( typeof wpzInstaStories !== 'undefined' && wpzInstaStories?.i18n?.minutes ) || 'minutes',
						fromnow: ( typeof wpzInstaStories !== 'undefined' && wpzInstaStories?.i18n?.fromnow ) || 'from now',
						seconds: ( typeof wpzInstaStories !== 'undefined' && wpzInstaStories?.i18n?.seconds ) || 'seconds',
						yesterday: ( typeof wpzInstaStories !== 'undefined' && wpzInstaStories?.i18n?.yesterday ) || 'yesterday',
						tomorrow: ( typeof wpzInstaStories !== 'undefined' && wpzInstaStories?.i18n?.tomorrow ) || 'tomorrow',
						days: ( typeof wpzInstaStories !== 'undefined' && wpzInstaStories?.i18n?.days ) || 'days',
					},
				},
				callbacks: {
					onOpen: function( storyId, callback ) {
						// Show overlay and lock body scroll
						showModalOverlay();

						// If a different Zuck instance was active, we need to clear the modal
						// to prevent mixing stories from different feeds
						if ( activeZuckInstance && activeZuckInstance !== zuckInstance ) {
							// Clear the modal content to force fresh rendering
							const $modalContent = $( '#zuck-modal-content' );
							if ( $modalContent.length ) {
								$modalContent.html( '' );
							}
						}

						// Set active instance for keyboard navigation
						activeZuckInstance = zuckInstance;

						// Reset story to first item when opening
						const storyIndex = zuckInstance.findStoryIndex( storyId );
						if ( storyIndex !== -1 && zuckInstance.data[ storyIndex ] ) {
							zuckInstance.data[ storyIndex ].currentItem = 0;
							zuckInstance.data[ storyIndex ].seen = false;

							// Reset all items' seen status
							if ( zuckInstance.data[ storyIndex ].items ) {
								zuckInstance.data[ storyIndex ].items.forEach( function( item ) {
									item.seen = false;
								} );
							}
						}

						callback();
					},
					onView: function( storyId ) {
						// Story viewed - nothing special needed
					},
					onEnd: function( storyId, callback ) {
						// All stories viewed - close the modal
						callback();
					},
					onClose: function( storyId, callback ) {
						// Hide overlay and unlock body scroll
						hideModalOverlay();

						// Modal closed - clear active instance
						activeZuckInstance = null;

						// Reset story position for next open
						const storyIndex = zuckInstance.findStoryIndex( storyId );
						if ( storyIndex !== -1 && zuckInstance.data[ storyIndex ] ) {
							zuckInstance.data[ storyIndex ].currentItem = 0;
							zuckInstance.data[ storyIndex ].seen = false;

							// Reset all items' seen status
							if ( zuckInstance.data[ storyIndex ].items ) {
								zuckInstance.data[ storyIndex ].items.forEach( function( item ) {
									item.seen = false;
								} );
							}
						}

						callback();
					},
					onNavigateItem: function( storyId, nextStoryId, callback ) {
						// IMPORTANT: Must call callback for navigation to work
						callback();
					},
				},
			} );

			// Store reference for later use
			$container.data( 'zuck', zuckInstance );
			$container.data( 'zuck-container-id', storiesContainerId );

			// Handle click on the profile image to open stories
			$container.on( 'click.wpzInstaStories', function( e ) {
				e.preventDefault();
				e.stopPropagation();

				// Find and click the story in Zuck's container to trigger the modal
				const $zuckContainer = $( '#' + storiesContainerId );
				const $storyLink = $zuckContainer.find( '.story > a' );

				if ( $storyLink.length ) {
					$storyLink.get( 0 ).click();
				}
			} );

			// Also handle keyboard activation
			$container.on( 'keydown.wpzInstaStories', function( e ) {
				if ( e.key === 'Enter' || e.key === ' ' ) {
					e.preventDefault();
					$( this ).trigger( 'click' );
				}
			} );
		} );
	}

	// Setup global handlers once
	setupGlobalKeyboardHandler();
	setupTouchHandlers();

	// Initialize on DOM ready
	$( document ).ready( initInstagramStories );

	// Also initialize on window load (for late-loaded content)
	$( window ).on( 'load', initInstagramStories );

	// Export for external use
	window.wpzInstaInitStories = initInstagramStories;

} )( jQuery );
