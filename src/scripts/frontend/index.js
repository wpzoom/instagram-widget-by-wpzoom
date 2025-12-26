( function( $ ) {
	$( window ).on( 'load', function () {
		var ticking = false;

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
				
				if (!nextUrl) {
					$button.hide();
					return;
				}
				
				// Show loading state
				$button.addClass('loading').prop('disabled', true);
				$wrapper.addClass('loading');
				const originalText = $button.find('.button-text').text();
				$button.find('.button-text').text('Loading...');
				
				// Make AJAX request
				$.ajax({
					url: wpzInstaAjax.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'wpzoom_instagram_load_more',
						feed_id: feedId,
						item_amount: itemAmount,
						image_size: imageSize,
						allowed_post_types: allowedPostTypes,
						next: nextUrl,
						_wpnonce: nonce
					},
									success: function(response) {
					if (response.success && response.data.html) {
						// Store current item count before adding new items
						const currentItemCount = $itemsContainer.find('li').length;
						
						// Append new items
						$itemsContainer.append(response.data.html);
						
						// Update button state
						if (response.data.has_more && response.data.next_url) {
							$button.attr('data-next-url', response.data.next_url);
						} else {
							$button.hide();
						}
						
						// Reinitialize any image processing for the specific container
						$itemsContainer.zoomLoadAsyncImages();
						
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
										new Swiper(this, {
											lazy: {
												threshold: 50
											},
											watchSlidesVisibility: true,
											preloadImages: false,
											lazy: true,
											direction: 'horizontal',
											loop: false,
											spaceBetween: 20,
											nested: true,
											watchOverflow: true,
											pagination: {
												el: $(this).find('> .swiper-pagination').get(0),
												type: 'bullets',
												clickable: true,
												hideOnClick: false
											},
											navigation: {
												nextEl: $(this).find('> .swiper-button-next').get(0),
												prevEl: $(this).find('> .swiper-button-prev').get(0)
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

			if ( $swipe_el.length > 0 ) {
					const $nested   = $swipe_el.find( '.image-wrapper > .swiper' );

					const swiper = new Swiper( $swipe_el.get(0), {
						lazy:{
							threshold: 50
						},
						watchSlidesVisibility: true,
                        preloadImages: false,
                        lazy: true,
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
	
							},
						},
					} );

					$nested.each( function() {
						new Swiper( $( this ).get(0), {
							lazy:{
								threshold: 50
							},
							watchSlidesVisibility: true,
                            preloadImages: false,
                            lazy: true,
							direction: 'horizontal',
							loop: false,
							spaceBetween: 20,
							nested: true,
							watchOverflow: true,
							pagination: {
								el: $( this ).find( '> .swiper-pagination' ).get(0),
								type: 'bullets',
								clickable: true,
								hideOnClick: false
							},
							navigation: {
								nextEl: $( this ).find( '> .swiper-button-next' ).get(0),
								prevEl: $( this ).find( '> .swiper-button-prev' ).get(0)
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
					}

				});
			}
		);

		// Add Stories functionality
		function initInstagramStories() {
			let storyTimer;
			const IMAGE_DURATION = 5000; // 5 seconds for images

			function playNextStory($items, currentIndex) {
				const nextIndex = (currentIndex + 1) % $items.length;

				// Pause current video if any
				const currentVideo = $items.eq(currentIndex).find('video')[0];
				if (currentVideo) {
					currentVideo.pause();
					currentVideo.ontimeupdate = null;
					currentVideo.onended = null;
				}

				$items.eq(currentIndex).removeClass('active');
				$items.eq(nextIndex).addClass('active');

				// Play next item
				playStoryItem($items, nextIndex);
			}

			function playStoryItem($items, index) {
				clearTimeout(storyTimer);
				const $currentItem = $items.eq(index);
				const video = $currentItem.find('video')[0];
				const $progressSegments = $currentItem.closest('.wpz-insta-stories-popup').find('.progress-segment');
				const $currentSegment = $progressSegments.eq(index);

				// Reset all segments after current index
				$progressSegments.each(function(i) {
					if (i > index) {
						$(this).removeClass('active completed').find('.progress').css({
							'width': '0',
							'transition': 'none'
						});
					}
				});

				// Mark all previous segments as completed
				$progressSegments.each(function(i) {
					if (i < index) {
						$(this).removeClass('active').addClass('completed').find('.progress').css({
							'width': '100%',
							'transition': 'none'
						});
					}
				});

				// Activate current segment
				$progressSegments.removeClass('active');
				$currentSegment.addClass('active').removeClass('completed');

				if (video) {
					// If it's a video, play it and update progress based on video time
					video.currentTime = 0;
					video.play();

					const updateVideoProgress = () => {
						const progress = (video.currentTime / video.duration) * 100;
						$currentSegment.find('.progress').css({
							'width': progress + '%',
							'transition': 'width .1s linear'
						});
					};

					video.ontimeupdate = updateVideoProgress;

					// When video ends, play next story
					video.onended = function() {
						$currentSegment.addClass('completed');
						playNextStory($items, index);
					};
				} else {
					// For images, trigger CSS transition after a small delay
					$currentSegment.find('.progress').css({
						'width': '0',
						'transition': 'none'
					});

					// Force reflow
					$currentSegment.find('.progress')[0].offsetHeight;

					// Start animation
					$currentSegment.find('.progress').css({
						'width': '100%',
						'transition': 'width 5s linear'
					});

					// Set timeout for next story
					storyTimer = setTimeout(() => {
						$currentSegment.addClass('completed');
						playNextStory($items, index);
					}, IMAGE_DURATION);
				}
			}

			$('.zoom-instagram-widget__header-column-left img[data-stories="true"]').each(function() {
				const $img = $(this);
				const $popup = $img.siblings('.wpz-insta-stories-popup');

				if ($popup.length > 0) {
					$img.magnificPopup({
						type: 'inline',
						mainClass: 'wpz-insta-stories-popup-container',
						closeMarkup: '<button title="%title%" type="button" class="mfp-close wpz-insta-stories-close">×</button>',
						items: {
							src: $popup,
							type: 'inline'
						},
						callbacks: {
							open: function() {
								const $content = $(this.content);
								const $items = $content.find('.wpz-insta-story-item');
								$items.first().addClass('active');

								// Start playing the first story
								playStoryItem($items, 0);
							},
							close: function() {
								const $content = $(this.content);
								clearTimeout(storyTimer);

								// Stop all videos
								$content.find('video').each(function() {
									this.pause();
									this.onended = null;
								});

								$content.find('.wpz-insta-story-item').removeClass('active');
							}
						},
						removalDelay: 300,
						midClick: true,
						fixedContentPos: true,
						overflowY: 'hidden'
					});
				}
			});

			// Handle manual story navigation
			$(document).on('click', '.wpz-insta-story-item', function(e) {
				if (!$(e.target).is('a')) {
					e.preventDefault();
					e.stopPropagation();

					const $items = $(this).parent().children('.wpz-insta-story-item');
					const currentIndex = $items.index(this);

					// Clear any existing timers
					clearTimeout(storyTimer);

					// Play next story
					playNextStory($items, currentIndex);
				}
			});

			// Add keyboard navigation
			$(document).on('keyup', function(e) {
				const $popup = $('.mfp-wrap.wpz-insta-stories-popup-container');
				if ($popup.length) {
					const $items = $popup.find('.wpz-insta-story-item');
					const $current = $items.filter('.active');
					const currentIndex = $items.index($current);
					let nextIndex;

					if (e.key === 'ArrowLeft') {
						nextIndex = currentIndex - 1;
						if (nextIndex < 0) nextIndex = $items.length - 1;
					} else if (e.key === 'ArrowRight' || e.key === ' ') {
						nextIndex = (currentIndex + 1) % $items.length;
					} else {
						return;
					}

					// Clear any existing timers
					clearTimeout(storyTimer);

					// Stop current video if any
					const currentVideo = $current.find('video')[0];
					if (currentVideo) {
						currentVideo.pause();
						currentVideo.onended = null;
					}

					$current.removeClass('active');
					$items.eq(nextIndex).addClass('active');

					// Play the new story
					playStoryItem($items, nextIndex);
				}
			});
		}

		// Initialize stories functionality
		initInstagramStories();
	} );

} )( jQuery );
