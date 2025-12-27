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

	// Track initialized containers to prevent duplicate initialization
	const initializedContainers = new Set();

	// Store active Zuck instance for keyboard navigation
	let activeZuckInstance = null;

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

			// Transform data to Zuck.js format
			const timeline = [
				{
					id: storiesData.id || 'wpz-insta-story-' + Date.now(),
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
			// Use a more stable ID that won't change on re-init
			const storiesContainerId = 'wpz-insta-zuck-' + ( storiesData.id || '' ).replace( /[^a-zA-Z0-9-]/g, '-' );

			// Check if container already exists
			let $storiesContainer = $( '#' + storiesContainerId );
			if ( $storiesContainer.length === 0 ) {
				$storiesContainer = $( '<div>' )
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
			}

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

	// Setup global keyboard handler once
	setupGlobalKeyboardHandler();

	// Initialize on DOM ready
	$( document ).ready( initInstagramStories );

	// Also initialize on window load (for late-loaded content)
	$( window ).on( 'load', initInstagramStories );

	// Export for external use
	window.wpzInstaInitStories = initInstagramStories;

} )( jQuery );
