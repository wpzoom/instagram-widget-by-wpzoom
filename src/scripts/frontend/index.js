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

		/**
		 * AJAX Initial Feed Load functionality
		 * Finds placeholder elements and fetches actual content via AJAX
		 */
		function initAjaxFeeds() {
			$('.wpz-insta-ajax-placeholder').each(function() {
				const $placeholder = $(this);
				const feedId = $placeholder.data('feed-id');
				const nonce = $placeholder.data('nonce');

				// Skip if already loading, loaded, or missing required data
				if (!feedId || $placeholder.hasClass('loading') || $placeholder.hasClass('loaded')) {
					return;
				}

				$placeholder.addClass('loading');

				$.ajax({
					url: wpzInstaAjax.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'wpzoom_instagram_initial_load',
						feed_id: feedId,
						_wpnonce: nonce
					},
					success: function(response) {
						if (response.success && response.data.html) {
							// Replace placeholder with actual content
							const $newContent = $(response.data.html);
							$placeholder.replaceWith($newContent);

							// Find the new feed container
							const $newFeed = $newContent.hasClass('zoom-instagram')
								? $newContent
								: $newContent.find('.zoom-instagram');

							if ($newFeed.length > 0) {
								// Initialize the Instagram widget functionality
								const $itemsContainer = $newFeed.find('.zoom-instagram-widget__items');

								if ($itemsContainer.length > 0) {
								// Check if this is a carousel layout
								const isCarousel = $newFeed.hasClass('layout-carousel');

								if (isCarousel) {
									// Initialize Swiper for carousel layout
									// The swiper element is .zoom-instagram-widget__items-wrapper.swiper
									const $swiperEl = $newFeed.find('> .zoom-instagram-widget__items-wrapper.swiper');
									if ($swiperEl.length > 0 && typeof Swiper !== 'undefined') {
										// data-perpage is on the ul.swiper-wrapper element
										const perpage = parseInt($itemsContainer.data('perpage')) || 3;
										const perpageTablet = parseInt($itemsContainer.data('perpage-tablet')) || perpage;
										const perpageMobile = parseInt($itemsContainer.data('perpage-mobile')) || perpage;
										const spacing = parseFloat($itemsContainer.data('spacing')) || 20;

										// Initialize lazy loading BEFORE Swiper so images start loading
										if (typeof $.fn.lazy === 'function') {
											$itemsContainer.find('.zoom-instagram-link-new').lazy();
											$itemsContainer.find('a.zoom-instagram-link-old').lazy();
										}

										// Create Swiper instance
										const carouselSwiper = new Swiper($swiperEl.get(0), {
											direction: 'horizontal',
											loop: false,
											slidesPerView: perpage,
											spaceBetween: spacing,
											breakpoints: {
												320: {
													slidesPerView: perpageMobile,
													spaceBetween: spacing
												},
												480: {
													slidesPerView: perpageTablet,
													spaceBetween: spacing
												},
												769: {
													slidesPerView: perpage,
													spaceBetween: spacing
												}
											},
											autoHeight: true,
											watchOverflow: true,
											navigation: {
												nextEl: $swiperEl.find('> .swiper-button-next').get(0),
												prevEl: $swiperEl.find('> .swiper-button-prev').get(0)
											},
											keyboard: {
												enabled: true,
												onlyInViewport: true
											}
										});

										$newFeed.addClass('carousel-active');

										// Update Swiper after images load to fix autoHeight
										const $images = $itemsContainer.find('img');
										let loadedCount = 0;
										const totalImages = $images.length;

										if (totalImages > 0) {
											$images.each(function() {
												const img = this;
												if (img.complete) {
													loadedCount++;
													if (loadedCount === totalImages) {
														carouselSwiper.update();
													}
												} else {
													$(img).on('load error', function() {
														loadedCount++;
														if (loadedCount === totalImages) {
															carouselSwiper.update();
														}
													});
												}
											});
											// Fallback: update after a delay in case load events don't fire
											setTimeout(function() {
												carouselSwiper.update();
											}, 500);
										}
									}
								} else if ($itemsContainer.hasClass('layout-masonry') && typeof $.fn.masonry === 'function') {
									// Initialize masonry layout
									$itemsContainer.masonry({
										itemSelector: '.zoom-instagram-widget__item',
										columnWidth: '.masonry-items-sizer',
										percentPosition: true,
										gutter: parseInt($itemsContainer.data('spacing') || 10)
									});
								} else {
									// Initialize grid layout
									$itemsContainer.zoomInstagramWidget();
								}

									// Load async images
									$itemsContainer.zoomLoadAsyncImages();

									// Initialize lightbox if enabled
									if ($itemsContainer.attr('data-lightbox') === '1') {
										$itemsContainer.zoomLightbox();
									}
								}

								// Reinitialize load more buttons
								initLoadMoreButtons();

								// Trigger custom event for other scripts
								$newFeed.trigger('wpz-insta:ajax-loaded', [feedId, response.data]);
							}
						} else {
							console.error('Failed to load Instagram feed:', response.data || 'Unknown error');
							$placeholder.removeClass('loading').addClass('error');
						}
					},
					error: function(xhr, status, error) {
						console.error('AJAX initial feed load error:', error);
						$placeholder.removeClass('loading').addClass('error');
					}
				});
			});
		}

		// Initialize AJAX feeds on page load
		initAjaxFeeds();

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

	} );

} )( jQuery );
