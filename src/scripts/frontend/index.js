// Close button of the lightbox (an SVG instead of the library's default "×").
window.wpzInstaLightboxCloseMarkup = '<button title="%title%" type="button" class="mfp-close"><svg width="30" height="30" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 20 20" enable-background="new 0 0 20 20" xml:space="preserve"><path fill-rule="evenodd" clip-rule="evenodd" fill="#fff" d="M11.6,10.1l5.5-5.7c0.5-0.5,0.5-1.1,0-1.6c-0.5-0.5-1.1-0.5-1.6,0 l-5.5,5.6L4.4,2.8C4,2.4,3.3,2.4,2.8,2.8S2.4,4,2.8,4.4L8.4,10l-5.5,5.6c-0.5,0.5-0.5,1.1,0,1.6c0.5,0.5,1.1,0.5,1.6,0l5.5-5.6 l5.5,5.6c0.5,0.5,1.1,0.5,1.6,0c0.5-0.5,0.5-1.1,0-1.6C17,15.6,11.6,10.1,11.6,10.1z"></path></svg></button>';

// Lightbox images are printed with `data-src` and loaded once they come into view:
// when the lightbox opens, and as the visitor moves through the slides.
( function() {
	const selector = 'img.swiper-lazy[data-src]';
	const seen     = new WeakSet();

	function load( img ) {
		const done = function() {
			img.classList.add( 'swiper-lazy-loaded' );

			const slide     = img.closest( '.swiper-slide' ) || img.parentNode;
			const preloader = slide ? slide.querySelector( '.swiper-lazy-preloader' ) : null;

			if ( preloader ) {
				preloader.remove();
			}
		};

		img.addEventListener( 'load', done, { once: true } );
		img.addEventListener( 'error', done, { once: true } );
		img.src = img.getAttribute( 'data-src' );
		img.removeAttribute( 'data-src' );
	}

	const observer = 'IntersectionObserver' in window
		? new IntersectionObserver( function( entries ) {
			entries.forEach( function( entry ) {
				if ( entry.isIntersecting ) {
					observer.unobserve( entry.target );
					load( entry.target );
				}
			} );
		}, { rootMargin: '200px 100%' } )
		: null;

	function watch( root ) {
		const images = [];

		if ( root.matches && root.matches( selector ) ) {
			images.push( root );
		}

		if ( root.querySelectorAll ) {
			images.push.apply( images, root.querySelectorAll( selector ) );
		}

		images.forEach( function( img ) {
			if ( seen.has( img ) ) {
				return;
			}

			seen.add( img );

			if ( observer ) {
				observer.observe( img );
			} else {
				load( img );
			}
		} );
	}

	function start() {
		watch( document );

		// Slides added later (load more, block editor re-renders).
		new MutationObserver( function( mutations ) {
			mutations.forEach( function( mutation ) {
				mutation.addedNodes.forEach( function( node ) {
					if ( node.nodeType === 1 ) {
						watch( node );
					}
				} );
			} );
		} ).observe( document.body, { childList: true, subtree: true } );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
} )();

( function( $ ) {
	// Image loading shimmer effect - needs to run early to catch images before they load
	function initImageLoadingShimmer() {
		$('.zoom-instagram-widget__items').each(function() {
			const $container = $(this);

			$container.find('.zoom-instagram-widget__item').each(function() {
				const $item = $(this);

				// Skip if already processed
				if ($item.hasClass('wpz-insta-loaded') || $item.data('shimmer-init')) {
					return;
				}
				$item.data('shimmer-init', true);

				const $img = $item.find('img.zoom-instagram-link, img.zoom-instagram-link-new').first();

				if ($img.length === 0) {
					$item.addClass('wpz-insta-loaded');
					return;
				}

				// Check if image is already loaded (cached)
				if ($img[0].complete && $img[0].naturalWidth > 0) {
					$item.addClass('wpz-insta-loaded');
					return;
				}

				// Listen for image load
				$img.on('load.shimmer', function() {
					$item.addClass('wpz-insta-loaded');
				});

				// Handle error case - still show the item
				$img.on('error.shimmer', function() {
					$item.addClass('wpz-insta-loaded');
				});
			});
		});
	}

	// Run as early as possible - on DOMContentLoaded
	$(document).ready(function() {
		initImageLoadingShimmer();
	});

	// Export globally so PRO plugin can call after Load More
	window.wpzInstaImageShimmerInit = initImageLoadingShimmer;

	// Initialise on DOM ready, not window.load: the script is enqueued in the
	// footer, so every feed is already in the DOM here, and waiting for the
	// load event held the lazy image loader back until every other asset on
	// the page (fonts, third-party scripts, unrelated images) had finished.
	$( function () {
		var ticking = false;

		// Re-run shimmer init in case new feeds were added
		initImageLoadingShimmer();

		// Fast AJAX Load More functionality
		function initLoadMoreButtons() {
			$('.wpzinsta-pro-load-more-btn').off('click.loadmore').on('click.loadmore', function(e) {
				e.preventDefault();
				
				const $button = $(this);
				const $wrapper = $button.closest('.wpzinsta-pro-load-more-wrapper');
				const $feedContainer = $button.closest('.zoom-instagram');
				const $itemsContainer = $feedContainer.find('.zoom-instagram-widget__items');
				
				// Check if disabled or already loading
				if ($wrapper.attr('data-disabled') === 'true' || $button.prop('disabled') || $button.hasClass('loading')) {
					return;
				}
				
				// Get data from button
				const feedId = $button.attr('data-feed-id');
				const itemAmount = $button.attr('data-item-amount');
				const imageSize = $button.attr('data-image-size');
				const allowedPostTypes = $button.attr('data-allowed-post-types');
				const nextUrl = $button.attr('data-next-url');
				const nonce = $button.attr('data-nonce');
				const cacheOffset = parseInt($button.attr('data-cache-offset') || '-1', 10);
				
				// Need either a cache offset or a next URL to load more
				if (cacheOffset < 0 && !nextUrl) {
					$button.hide();
					return;
				}
				
				// Show loading state
				$button.addClass('loading').prop('disabled', true);
				$wrapper.addClass('loading');
				const originalText = $button.find('.button-text').text();
				$button.find('.button-text').text('Loading...');
				
				// Build request data: cache_offset for cache-based loading; preview for backend iframe (moderate buttons)
				var isWidgetPreview = window.location.search.indexOf( 'wpz-insta-widget-preview' ) !== -1;
				var requestData = {
					action: 'wpzoom_instagram_load_more',
					feed_id: feedId,
					item_amount: itemAmount,
					image_size: imageSize,
					allowed_post_types: allowedPostTypes,
					next: nextUrl,
					_wpnonce: nonce
				};
				if ( cacheOffset >= 0 ) {
					requestData.cache_offset = cacheOffset;
				}
				if ( isWidgetPreview ) {
					requestData.preview = 1;
				}

				// Make AJAX request
				$.ajax({
					url: wpzInstaAjax.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: requestData,
									success: function(response) {
					if (response.success && response.data.html) {
						// Store current item count before adding new items
						const currentItemCount = $itemsContainer.find('li').length;
						
						// Append new items
						$itemsContainer.append(response.data.html);
						
						// Update button state: track cache offset and API next URL
						if (response.data.has_more) {
							if (typeof response.data.cache_offset !== 'undefined' && response.data.cache_offset >= 0) {
								// Still loading from cache - update cache offset
								$button.attr('data-cache-offset', response.data.cache_offset);
							} else {
								// Cache exhausted - switch to API pagination
								$button.attr('data-cache-offset', '-1');
							}
							if (response.data.next_url) {
								$button.attr('data-next-url', response.data.next_url);
							}
						} else {
							$button.hide();
						}
						
						// Reinitialize any image processing for the specific container
						$itemsContainer.zoomLoadAsyncImages();

						// Initialize image loading shimmer for new items
						initImageLoadingShimmer();

						// Check if this is masonry layout and handle accordingly
						if ($itemsContainer.hasClass('layout-masonry')) {
							// Try to use WordPress masonry if available
							if (typeof $.fn.masonry === 'function') {
								const $newItems = $itemsContainer.find('li').slice(currentItemCount);
								
								// Initialize masonry if not already done
								if (!$itemsContainer.data('masonry')) {
									$itemsContainer.masonry({
										itemSelector: '.zoom-instagram-widget__item',
										columnWidth: '.masonry-items-sizer',
										percentPosition: true,
										gutter: parseInt($itemsContainer.data('spacing') || 10)
									});
								}
								
								// Add new items to masonry
								$itemsContainer.masonry('appended', $newItems);
							} else {
								// Fallback for masonry when library not available
								setTimeout(function() {
									$itemsContainer.zoomInstagramWidget({
										onlyNewItems: true,
										startIndex: currentItemCount
									});
								}, 100);
							}
						} else {
							// For grid/other layouts, use existing function
							setTimeout(function() {
								$itemsContainer.zoomInstagramWidget({
									onlyNewItems: true,
									startIndex: currentItemCount
								});
							}, 100);
						}
						
						// Handle lightbox content for new items
						if ($itemsContainer.attr('data-lightbox') === '1' && response.data.lightbox_html) {
							
							// Find the existing lightbox wrapper
							const $lightboxWrapper = $feedContainer.find('.wpz-insta-lightbox-wrapper .swiper-wrapper');
							
							if ($lightboxWrapper.length > 0) {
								// Append new lightbox slides
								$lightboxWrapper.append(response.data.lightbox_html);
								
								// Update the existing Swiper instance to recognize new slides
								const $swiperContainer = $lightboxWrapper.parent();
								if ($swiperContainer.length > 0 && $swiperContainer.get(0).swiper) {
									$swiperContainer.get(0).swiper.update();
								}
								
								// Initialize nested Swipers for albums in the newly added content
								const $allNestedSwipers = $lightboxWrapper.find('.image-wrapper > .swiper');
								$allNestedSwipers.each(function() {
									// Only initialize if not already initialized
									if (!this.swiper) {
										var $nestedSwiper = $(this);
										var $imageWrapper = $nestedSwiper.closest('.image-wrapper');
										new Swiper(this, {
											direction: 'horizontal',
											loop: false,
											spaceBetween: 20,
											nested: true,
											watchOverflow: true,
											pagination: {
												el: $nestedSwiper.find('> .swiper-pagination').get(0),
												type: 'bullets',
												clickable: true,
												hideOnClick: false
											},
											navigation: {
												nextEl: $nestedSwiper.find('> .swiper-button-next').get(0),
												prevEl: $nestedSwiper.find('> .swiper-button-prev').get(0)
											},
											keyboard: {
												enabled: true,
												onlyInViewport: true
											},
											on: {
												init: function() {
													$( document ).trigger( 'wpz-insta:lightbox-album-slide', [ $imageWrapper, 0 ] );
												},
												activeIndexChange: function () {
													// Get the active slide
													const activeSlide = this.slides[this.activeIndex];
													const $activeSlide = $(activeSlide);

													// Play the video in the active slide if it exists
													const video = $activeSlide.find('video').get(0);
													if (video) {
														video.play();
													}

													$( document ).trigger( 'wpz-insta:lightbox-album-slide', [ $imageWrapper, this.activeIndex ] );
												},
											},
										});
									}
								});
								
								// Initialize MagnificPopup on new grid items only
								const $newItems = $itemsContainer.find('li').slice(currentItemCount);
								const $newLinks = $newItems.find('.zoom-instagram-link');
								
								if ($newLinks.length > 0) {
									
									$newLinks.magnificPopup({
										items: {
											type: 'inline',
											src: $lightboxWrapper.closest('.wpz-insta-lightbox-wrapper')
										},
										closeBtnInside: false,
										mainClass: 'wpzoom-lightbox',
										closeMarkup: window.wpzInstaLightboxCloseMarkup,
										midClick: true,
										callbacks: {
											open: function() {
												const magnificPopup = $.magnificPopup.instance,
												currentElement = magnificPopup.st.el,
												$thisSwiper = this.content.find('> .swiper').get(0).swiper;
												
												
												if (this.content.find('> .swiper > .swiper-wrapper > .swiper-slide[data-uid="' + currentElement.data('mfp-src') + '"] video')) {
													this.content.find('> .swiper > .swiper-wrapper > .swiper-slide[data-uid="' + currentElement.data('mfp-src') + '"] video').trigger('play');
												}
												if (typeof $thisSwiper === 'object') {
													const targetSlideIndex = this.content.find('> .swiper > .swiper-wrapper > .swiper-slide[data-uid="' + currentElement.data('mfp-src') + '"]').index();
													$thisSwiper.slideTo(targetSlideIndex);
												}
											},
											afterClose: function() {
												// Destroy swiper videos
												$swiperContainer.find('video').each(function() {
													this.pause();
													this.currentTime = 0;
												});
											}
										}
									});
									
									$newLinks.addClass('magnific-active');
								}
							} else {
								$itemsContainer.zoomLightbox();
							}
						}
						
						// Trigger custom event for other scripts
						$feedContainer.trigger('wpz-insta:loaded-more', [response.data]);
					} else {
						console.error('Load more failed:', response.data || 'Unknown error');
						$button.hide();
					}
				},
					error: function(xhr, status, error) {
						console.error('AJAX load more error:', error);
						$button.hide();
					},
					complete: function() {
						// Remove loading state
						$button.removeClass('loading').prop('disabled', false);
						$wrapper.removeClass('loading');
						$button.find('.button-text').text(originalText);
					}
				});
			});
		}

		// Initialize load more buttons
		initLoadMoreButtons();

		$.fn.zoomLoadAsyncImages = function () {
			// Skip async image uploading in preview mode (images already use CDN URLs)
			if ( window.location.search.indexOf( 'wpz-insta-widget-preview' ) !== -1 ) {
				return this;
			}
			return $(this).each(function () {
				var $list = $(this);

				var desiredItemWidth = $list.data('image-width');
				var imageResolution = $list.data('image-resolution');

				var delayedItems = $list.find('li').filter(function () {
					return $(this).data('media-id');
				}).map(function () {
					return {
						'media-id': $(this).attr('data-media-id'),
						'nonce': $(this).attr('data-nonce'),
						'regenerate-thumbnails': $(this)[0].hasAttribute("data-regenerate-thumbnails")
					};
				});

				// Process images in parallel batches for better performance
				var processImageBatch = function (images, batchSize) {
					batchSize = batchSize || 3; // Process 3 images at once
					
					for (var i = 0; i < images.length; i += batchSize) {
						var batch = images.slice(i, i + batchSize);
						
						batch.forEach(function(image) {
							wp.ajax.post('wpzoom_instagram_get_image_async', {
								'media-id': image['media-id'],
								nonce: image['nonce'],
								'image-resolution': imageResolution,
								'image-width': desiredItemWidth,
								'regenerate-thumbnails': image['regenerate-thumbnails']
							}).done(function (data) {
								$list.find('li[data-media-id="' + image['media-id'] + '"] .zoom-instagram-link').css('background-image', 'url(' + data.image_src + ')');
							}).fail(function () {
								// Silently fail for missing images
							});
						});
					}
				};

				if (delayedItems.length) {
					processImageBatch(delayedItems.toArray(), 3);
				}
			});
		};

			$.fn.zoomLightbox = function () {
		return $( this ).each( function () {
			
			// Try multiple strategies to find the lightbox wrapper
			let $swipe_el = $( this ).closest( '.widget' ).find( '.wpz-insta-lightbox-wrapper > .swiper' );
			
			// Fallback 1: Look in the same Instagram widget container
			if ( $swipe_el.length === 0 ) {
				$swipe_el = $( this ).closest( '.zoom-instagram' ).find( '.wpz-insta-lightbox-wrapper > .swiper' );
			}
			
			// Fallback 2: Look for siblings or nearby elements
			if ( $swipe_el.length === 0 ) {
				$swipe_el = $( this ).parent().parent().find( '.wpz-insta-lightbox-wrapper > .swiper' );
			}
			
			
			// Debug: Check if lightbox wrapper has content
			const $lightboxWrapper = $swipe_el.closest( '.wpz-insta-lightbox-wrapper' );

			if ( $swipe_el.length > 0 && typeof Swiper !== 'undefined' ) {
					const $nested   = $swipe_el.find( '.image-wrapper > .swiper' );

					const swiper = new Swiper( $swipe_el.get(0), {
						direction: 'horizontal',
						loop: false,
						spaceBetween: 20,
						autoHeight: false,
						watchOverflow: true,
						navigation: {
							nextEl: $swipe_el.find( '> .swiper-button-next' ).get(0),
							prevEl: $swipe_el.find( '> .swiper-button-prev' ).get(0)
						},
						keyboard: {
							enabled: true,
							onlyInViewport: true
						},
						on: {
							activeIndexChange: function () {
	
								// Get the active slide
								const activeSlide = this.slides[this.activeIndex];
								const $activeSlide = $(activeSlide);
	
								// Play the video in the active slide if it exists
								const video = $activeSlide.find('video').get(0);
								if (video) {
									video.play();
								}

								$( document ).trigger( 'wpz-insta:lightbox-slide', [ $activeSlide ] );
	
							},
						},
					} );

					$nested.each( function() {
						var $nestedSwiper = $( this );
						var $imageWrapper = $nestedSwiper.closest( '.image-wrapper' );
						new Swiper( $nestedSwiper.get(0), {
							direction: 'horizontal',
							loop: false,
							spaceBetween: 20,
							nested: true,
							watchOverflow: true,
							pagination: {
								el: $nestedSwiper.find( '> .swiper-pagination' ).get(0),
								type: 'bullets',
								clickable: true,
								hideOnClick: false
							},
							navigation: {
								nextEl: $nestedSwiper.find( '> .swiper-button-next' ).get(0),
								prevEl: $nestedSwiper.find( '> .swiper-button-prev' ).get(0)
							},
							keyboard: {
								enabled: true,
								onlyInViewport: true
							},
							on: {
								init: function() {
									$( document ).trigger( 'wpz-insta:lightbox-album-slide', [ $imageWrapper, 0 ] );
								},
								activeIndexChange: function () {
	
									// Get the active slide
									const activeSlide = this.slides[this.activeIndex];
									const $activeSlide = $(activeSlide);
	
									// Play the video in the active slide if it exists
									const video = $activeSlide.find('video').get(0);
									if (video) {
										video.play();
									}

									$( document ).trigger( 'wpz-insta:lightbox-album-slide', [ $imageWrapper, this.activeIndex ] );
								},
							},	
						} );
					} );

					// Find the gallery trigger using the same strategy as swiper element
					let galleryTrigger = $( this ).closest( '.widget' ).find( '.zoom-instagram-widget__items' );
					
					// Fallback 1: Look in the same Instagram widget container
					if ( galleryTrigger.length === 0 ) {
						galleryTrigger = $( this ).closest( '.zoom-instagram' ).find( '.zoom-instagram-widget__items' );
					}
					
					// Fallback 2: Use this element directly if it's the items container
					if ( galleryTrigger.length === 0 ) {
						galleryTrigger = $( this );
					}
					
					// Use the same approach as block.js - call magnificPopup directly on the links
					galleryTrigger.find( '.zoom-instagram-link' ).magnificPopup( {
						items: {
							type: 'inline',
							src: $swipe_el.closest( '.wpz-insta-lightbox-wrapper' )
						},
						closeBtnInside: false,
						mainClass: 'wpzoom-lightbox',
						closeMarkup: window.wpzInstaLightboxCloseMarkup,
						midClick: true,
						callbacks: {
							open: function () {
								const magnificPopup = $.magnificPopup.instance,
								currentElement = magnificPopup.st.el,
								$thisSwiper = this.content.find( '> .swiper' ).get(0).swiper;
								
								if( this.content.find( '> .swiper > .swiper-wrapper > .swiper-slide[data-uid="' + currentElement.data( 'mfp-src' ) + '"] video' ) ) {
									this.content.find( '> .swiper > .swiper-wrapper > .swiper-slide[data-uid="' + currentElement.data( 'mfp-src' ) + '"] video' ).trigger('play');
								}
								if ( typeof $thisSwiper === 'object' ) {
									$thisSwiper.slideTo(
										this.content.find( '> .swiper > .swiper-wrapper > .swiper-slide[data-uid="' + currentElement.data( 'mfp-src' ) + '"]' ).index()
									);
								}
								
								const $currentSlide = this.content.find( '> .swiper > .swiper-wrapper > .swiper-slide[data-uid="' + currentElement.data( 'mfp-src' ) + '"]' );
								$( document ).trigger( 'wpz-insta:lightbox-slide', [ $currentSlide ] );
							},
							afterClose: function () {
								// Destroy swiper videos
								$swipe_el.find( 'video' ).each( function () {
									this.pause();
									this.currentTime = 0;
								} );
							}
						}
					} );
					galleryTrigger.find( '.zoom-instagram-link' ).addClass( 'magnific-active' );
				}
			} );
		};

		$.fn.zoomInstagramWidget = function (options) {
			return $(this).each(function () {
				var $list = $(this);
				var opts = options || {};

				var minItemsPerRow = $list.data('images-per-row');
				var desiredItemWidth = $list.data('image-width');
				var itemSpacing = $list.data('image-spacing');
				var imageLazyLoading = $list.data('image-lazy-loading');

				var containerWidth = $list.width();

				var fitPerRow;
				var itemWidth;

				if (containerWidth / desiredItemWidth < minItemsPerRow) {
					fitPerRow = minItemsPerRow;
					itemWidth = Math.floor(((containerWidth - 1 - (minItemsPerRow - 1) * itemSpacing) / minItemsPerRow));
				} else {
					fitPerRow = Math.floor((containerWidth - 1) / desiredItemWidth);
					itemWidth = Math.floor(((containerWidth - 1 - (fitPerRow - 1) * itemSpacing) / fitPerRow));
				}

				// If onlyNewItems is specified, only process items from that index onwards
				var $itemsToProcess = opts.onlyNewItems && opts.startIndex !== undefined 
					? $list.find('li').slice(opts.startIndex)
					: $list.find('li');

				$itemsToProcess.each(function (relativeIndex) {
					// Calculate the global index (position among all items)
					var globalIndex = opts.onlyNewItems && opts.startIndex !== undefined 
						? opts.startIndex + relativeIndex 
						: relativeIndex;
					
					var loop = globalIndex + 1; // 1-based indexing
					
					if (loop % fitPerRow == 1) {
						$(this).css('clear', 'left');
					} else {
						$(this).css('clear', 'none');
					}
					if (loop % fitPerRow == 0) {
						$(this).css('margin-right', '0');
					} else {
						$(this).css('margin-right', itemSpacing + 'px');
						$(this).css('margin-bottom', itemSpacing + 'px');
					}
				});

				// Always update link dimensions for all items
				$list.find('a.zoom-instagram-link').css({
					width: itemWidth,
					height: itemWidth
				});

				// if (imageLazyLoading) {
				$list.find('a.zoom-instagram-link-old').lazy();
                $list.find('.zoom-instagram-link-new').lazy();
				// }

				$list.removeClass('zoom-instagram-widget__items--no-js');
			});
		};

		function requestTick() {
			if (!ticking) {
				ticking = true;
				requestAnimationFrame()(update);
			}
		}

		function requestAnimationFrame() {
			return window.requestAnimationFrame ||
				window.webkitRequestAnimationFrame ||
				window.mozRequestAnimationFrame ||
				function (callback) {
					window.setTimeout(callback, 1000 / 60);
				};
		}

		function update() {
			$('.zoom-instagram-widget__items').each(function() {
				const $container = $(this);
				
				if ($container.hasClass('layout-masonry')) {
					// Try to use WordPress masonry if available
					if (typeof $.fn.masonry === 'function') {
						// Initialize masonry for masonry layouts
						if (!$container.data('masonry')) {
							$container.masonry({
								itemSelector: '.zoom-instagram-widget__item',
								columnWidth: '.masonry-items-sizer',
								percentPosition: true,
								gutter: parseInt($container.data('spacing') || 10)
							});
						} else {
							// Refresh masonry layout
							$container.masonry('layout');
						}
					} else {
						// Fallback for masonry when library not available
						$container.zoomInstagramWidget();
					}
				} else {
					// Use existing grid function for non-masonry layouts
					$container.zoomInstagramWidget();
				}
			});
			ticking = false;
		}

		$(window).on('resize orientationchange', requestTick);
		
				// Initialize layouts on page load
		$('.zoom-instagram-widget__items').each(function() {
			const $container = $(this);
			
			if ($container.hasClass('layout-masonry')) {
				// Try to use WordPress masonry if available
				if (typeof $.fn.masonry === 'function') {
					// Initialize masonry for masonry layouts
					$container.masonry({
						itemSelector: '.zoom-instagram-widget__item',
						columnWidth: '.masonry-items-sizer',
						percentPosition: true,
						gutter: parseInt($container.data('spacing') || 10)
					});
				} else {
					// Fallback for masonry when library not available
					$container.zoomInstagramWidget();
				}
			} else {
				// Use existing grid function for non-masonry layouts
				$container.zoomInstagramWidget();
			}
		});
		
		$('.zoom-instagram-widget__items').zoomLoadAsyncImages();
		$('.zoom-instagram-widget__items[data-lightbox="1"]').zoomLightbox();

		// Masonry measures item heights, which can still shift as images arrive —
		// settle the layout once everything has loaded.
		$( window ).on( 'load', function () {
			$('.zoom-instagram-widget__items.layout-masonry').each(function() {
				const $container = $(this);
				if ( typeof $.fn.masonry === 'function' && $container.data('masonry') ) {
					$container.masonry('layout');
				}
			});
		});

		// Carousel layout: one Swiper per feed. Marked `carousel-active` so it is only set up once.
		function initCarousels() {
			if ( typeof Swiper === 'undefined' ) {
				return;
			}

			const $editorCanvas = $( 'iframe.edit-site-visual-editor__editor-canvas' ),
			      selector      = '.zoom-instagram.layout-carousel:not(.carousel-active) > .swiper',
			      $carousels    = $editorCanvas.length > 0 ? $editorCanvas.contents().find( selector ) : $( selector );

			$carousels.each( function () {
				const $carousel     = $( this ),
				      $wrapper      = $carousel.find( '> .swiper-wrapper' ),
				      perPage       = parseInt( $wrapper.data( 'perpage' ) ),
				      perPageTablet = parseInt( $wrapper.data( 'perpage-tablet' ) ),
				      perPageMobile = parseInt( $wrapper.data( 'perpage-mobile' ) ),
				      spacing       = parseFloat( $wrapper.data( 'spacing' ) ),
				      slides        = perPage > -1 ? perPage : 3,
				      gap           = spacing > -1 ? spacing : 20;

				new Swiper( this, {
					direction: 'horizontal',
					loop: false,
					slidesPerView: slides,
					spaceBetween: gap,
					breakpoints: {
						320: { slidesPerView: perPageMobile > -1 ? perPageMobile : slides, spaceBetween: gap },
						480: { slidesPerView: perPageTablet > -1 ? perPageTablet : slides, spaceBetween: gap },
						769: { slidesPerView: slides, spaceBetween: gap }
					},
					autoHeight: true,
					watchOverflow: true,
					navigation: {
						nextEl: $carousel.find( '> .swiper-button-next' ).get( 0 ),
						prevEl: $carousel.find( '> .swiper-button-prev' ).get( 0 )
					},
					keyboard: {
						enabled: true,
						onlyInViewport: true
					}
				} );

				$carousel.closest( '.zoom-instagram' ).addClass( 'carousel-active' );
			} );
		}

		// Masonry layout: flag feeds whose grid is handled by the Masonry script, so the
		// CSS-columns fallback used in editor previews steps aside.
		function markActiveMasonry() {
			if ( typeof $.fn.masonry !== 'function' ) {
				return;
			}

			$( '.zoom-instagram.layout-masonry:not(.masonry-active)' ).addClass( 'masonry-active' );
		}

		initCarousels();
		markActiveMasonry();

		$( window ).on( 'wpzInstaServerSideRenderDone', function () {
			initCarousels();
			markActiveMasonry();
		} );

		var siteOriginInit = function () {
			var $widgets = $('.zoom-instagram-widget__items');
			if ($widgets.length) {
				$widgets.each(function() {
					const $container = $(this);
					
					if ($container.hasClass('layout-masonry')) {
						// Try to use WordPress masonry if available
						if (typeof $.fn.masonry === 'function') {
							// Initialize masonry for masonry layouts
							$container.masonry({
								itemSelector: '.zoom-instagram-widget__item',
								columnWidth: '.masonry-items-sizer',
								percentPosition: true,
								gutter: parseInt($container.data('spacing') || 10)
							});
						} else {
							// Fallback for masonry when library not available
							$container.zoomInstagramWidget();
						}
					} else {
						// Use existing grid function for non-masonry layouts
						$container.zoomInstagramWidget();
					}
				});
				$('.zoom-instagram-widget__items').zoomLoadAsyncImages();
			}

		};

		var debounceInit = _.debounce(siteOriginInit, 1500);
		$(document).on('panels_setup_preview', debounceInit);

		// Re-initialize load more buttons when new content is added dynamically
		$(document).on('wpz-insta:loaded-more', function() {
			initLoadMoreButtons();
		});

		$(window).on(
			'elementor/frontend/init',
			function () {
				elementorFrontend.hooks.addAction('frontend/element_ready/widget', function ($scope) {

					if ($scope.data('widget_type') == "wpzoom-elementor-instagram-widget.default") {
						initCarousels();
						// Handle both masonry and regular layouts for Elementor
						$scope.find('.zoom-instagram-widget__items').each(function() {
							const $container = $(this);

							if ($container.hasClass('layout-masonry')) {
								// Try to use WordPress masonry if available
								if (typeof $.fn.masonry === 'function') {
									// Initialize masonry for masonry layouts
									$container.masonry({
										itemSelector: '.zoom-instagram-widget__item',
										columnWidth: '.masonry-items-sizer',
										percentPosition: true,
										gutter: parseInt($container.data('spacing') || 10)
									});
								} else {
									// Fallback for masonry when library not available
									$container.zoomInstagramWidget();
								}
							} else {
								// Use existing grid function for non-masonry layouts
								$container.zoomInstagramWidget();
							}
						});
						markActiveMasonry();
					}

				});
			}
		);

	} );

} )( jQuery );
