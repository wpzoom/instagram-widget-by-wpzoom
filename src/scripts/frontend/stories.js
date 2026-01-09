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

			/* Custom mute/unmute button styling */
			#zuck-modal .story-viewer .head .right .wpz-insta-mute-btn {
				display: inline-flex;
				align-items: flex-start;
				justify-content: center;
				width: 42px;
				height: 42px;
				background: none;
				border: none;
				border-radius: 50%;
				cursor: pointer;
				padding: 0;
				margin-right: 8px;
				vertical-align: middle;
				transition: background 0.2s ease;
			}
			#zuck-modal .story-viewer .head .right .wpz-insta-mute-btn:hover {
				background: rgba(0, 0, 0, 0.5);
			}
			#zuck-modal .story-viewer .head .right .wpz-insta-mute-btn svg {
				width: 30px;
				height: 30px;
				fill: #fff;
			}
			/* Hide the default Zuck.js mute tip since we have our own button */
			#zuck-modal .story-viewer .tip.muted {
				display: none !important;
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

	/**
	 * SVG icons for mute/unmute button
	 */
	const muteIconSVG = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/></svg>`;
	const unmuteIconSVG = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>`;

	/**
	 * Check if the current story item has a video
	 */
	function currentItemHasVideo() {
		const $activeItem = $( '#zuck-modal .story-viewer.viewing .slides .item.active' );
		return $activeItem.find( 'video' ).length > 0;
	}

	/**
	 * Update the mute button icon and visibility based on current state
	 */
	function updateMuteButtonIcon() {
		const $muteBtn = $( '#zuck-modal .wpz-insta-mute-btn' );
		if ( $muteBtn.length ) {
			// Only show mute button if current item has video
			if ( currentItemHasVideo() ) {
				$muteBtn.show();
				$muteBtn.html( isGloballyMuted ? muteIconSVG : unmuteIconSVG );
				$muteBtn.attr( 'aria-label', isGloballyMuted ? 'Unmute' : 'Mute' );
			} else {
				$muteBtn.hide();
			}
		}
	}

	/**
	 * Apply mute state to all videos in the modal.
	 *
	 * We control the video.muted property directly for actual muting,
	 * and we ALWAYS remove Zuck.js's 'muted' class to prevent its
	 * tap-to-unmute behavior from interfering with navigation.
	 *
	 * Zuck.js normally: if 'muted' class present, tap unmutes then navigates.
	 * We want: tap ONLY navigates, mute is controlled by our button.
	 */
	function applyMuteState() {
		const $videos = $( '#zuck-modal video' );
		$videos.each( function() {
			this.muted = isGloballyMuted;
			if ( ! isGloballyMuted ) {
				this.volume = 1.0;
			}
		} );

		// ALWAYS remove Zuck's 'muted' class to prevent tap-to-unmute behavior.
		// This ensures tapping always navigates, never unmutes.
		$( '#zuck-modal .story-viewer' ).removeClass( 'muted' );

		updateMuteButtonIcon();
	}

	/**
	 * Toggle mute state
	 */
	function toggleMute( e ) {
		if ( e ) {
			e.preventDefault();
			e.stopPropagation();
		}
		isGloballyMuted = ! isGloballyMuted;
		applyMuteState();
	}

	/**
	 * Inject mute button into the story viewer header
	 */
	function injectMuteButton() {
		const $modal = $( '#zuck-modal' );
		if ( ! $modal.length ) {
			return;
		}

		// Check if button already exists
		if ( $modal.find( '.wpz-insta-mute-btn' ).length ) {
			updateMuteButtonIcon();
			return;
		}

		// Find the header right section and inject the button before the close button
		const $headerRight = $modal.find( '.story-viewer .head .right' );
		if ( $headerRight.length ) {
			const $closeBtn = $headerRight.find( '.close' );
			const $muteBtn = $( '<button>' )
				.addClass( 'wpz-insta-mute-btn' )
				.attr( 'type', 'button' )
				.attr( 'aria-label', isGloballyMuted ? 'Unmute' : 'Mute' )
				.html( isGloballyMuted ? muteIconSVG : unmuteIconSVG )
				.on( 'click', toggleMute );

			if ( $closeBtn.length ) {
				$closeBtn.before( $muteBtn );
			} else {
				$headerRight.append( $muteBtn );
			}
		}
	}

	// Inject modal styles immediately
	injectModalStyles();

	// Track initialized containers to prevent duplicate initialization
	const initializedContainers = new Set();

	// MutationObserver to:
	// 1. Remove Zuck's 'muted' class (prevents tap-to-unmute behavior)
	// 2. Intercept new video elements and enforce mute state (prevents audio blip)
	let mutedClassObserver = null;

	/**
	 * Enforce mute state on a video element.
	 * Overrides the muted property setter to block Zuck.js from unmuting.
	 */
	function enforceVideoMuteState( video ) {
		if ( video.wpzMuteEnforced ) {
			return;
		}
		video.wpzMuteEnforced = true;

		// Store the original muted property descriptor
		const originalMutedDescriptor = Object.getOwnPropertyDescriptor( HTMLMediaElement.prototype, 'muted' );

		// Override the muted property on this specific video element
		Object.defineProperty( video, 'muted', {
			get: function() {
				return originalMutedDescriptor.get.call( this );
			},
			set: function( value ) {
				// If we want it muted, ALWAYS set to true regardless of what Zuck tries
				if ( isGloballyMuted ) {
					originalMutedDescriptor.set.call( this, true );
				} else {
					originalMutedDescriptor.set.call( this, value );
				}
			},
			configurable: true,
		} );

		// Also override volume property
		const originalVolumeDescriptor = Object.getOwnPropertyDescriptor( HTMLMediaElement.prototype, 'volume' );
		Object.defineProperty( video, 'volume', {
			get: function() {
				return originalVolumeDescriptor.get.call( this );
			},
			set: function( value ) {
				// If we want it muted, ALWAYS set volume to 0
				if ( isGloballyMuted ) {
					originalVolumeDescriptor.set.call( this, 0 );
				} else {
					originalVolumeDescriptor.set.call( this, value );
				}
			},
			configurable: true,
		} );

		// Set initial state using the original setters directly
		originalMutedDescriptor.set.call( video, isGloballyMuted );
		originalVolumeDescriptor.set.call( video, isGloballyMuted ? 0 : 1 );
	}

	function startMutedClassObserver() {
		if ( mutedClassObserver ) {
			return;
		}

		mutedClassObserver = new MutationObserver( function( mutations ) {
			mutations.forEach( function( mutation ) {
				// Handle class changes on story-viewer (remove 'muted' class)
				if ( mutation.type === 'attributes' && mutation.attributeName === 'class' ) {
					const target = mutation.target;
					if ( target.classList && target.classList.contains( 'muted' ) && target.classList.contains( 'story-viewer' ) ) {
						target.classList.remove( 'muted' );
					}
				}

				// Handle new nodes being added (enforce mute on new videos)
				if ( mutation.type === 'childList' && mutation.addedNodes.length > 0 ) {
					mutation.addedNodes.forEach( function( node ) {
						if ( node.nodeType === Node.ELEMENT_NODE ) {
							// Check if the node itself is a video
							if ( node.tagName === 'VIDEO' ) {
								enforceVideoMuteState( node );
							}
							// Check for videos inside the added node
							const videos = node.querySelectorAll ? node.querySelectorAll( 'video' ) : [];
							videos.forEach( function( video ) {
								enforceVideoMuteState( video );
							} );
						}
					} );
				}
			} );
		} );

		// Observe the modal for class changes and new child elements
		const modal = document.getElementById( 'zuck-modal' );
		if ( modal ) {
			mutedClassObserver.observe( modal, {
				attributes: true,
				attributeFilter: [ 'class' ],
				childList: true,
				subtree: true,
			} );

			// Also enforce mute on any existing videos
			modal.querySelectorAll( 'video' ).forEach( enforceVideoMuteState );
		}
	}

	function stopMutedClassObserver() {
		if ( mutedClassObserver ) {
			mutedClassObserver.disconnect();
			mutedClassObserver = null;
		}
	}

	// Store active Zuck instance for keyboard navigation
	let activeZuckInstance = null;

	// Counter for unique instance IDs
	let instanceCounter = 0;

	// Track if touch handlers are set up
	let touchHandlersInitialized = false;

	// Track global mute state (muted by default, like Instagram)
	let isGloballyMuted = true;

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

						// Start observer to continuously remove 'muted' class
						startMutedClassObserver();

						// After callback (which renders the modal), inject mute button and apply mute state
						// Use setTimeout to ensure DOM is ready
						setTimeout( function() {
							injectMuteButton();
							applyMuteState();
						}, 100 );
					},
					onView: function( storyId ) {
						// Story viewed - apply mute state when navigating between items
						setTimeout( applyMuteState, 50 );
					},
					onEnd: function( storyId, callback ) {
						// All stories viewed - close the modal
						callback();
					},
					onClose: function( storyId, callback ) {
						// Stop the muted class observer
						stopMutedClassObserver();

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

						// Apply mute state when navigating to next item
						setTimeout( applyMuteState, 50 );
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
