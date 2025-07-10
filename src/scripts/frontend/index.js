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
								console.log('Instagram Widget: Masonry library not available, using grid fallback');
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
							console.log('Instagram Widget: Adding new lightbox content after load more');
							
							// Find the existing lightbox wrapper
							const $lightboxWrapper = $feedContainer.find('.wpz-insta-lightbox-wrapper .swiper-wrapper');
							
							if ($lightboxWrapper.length > 0) {
								// Append new lightbox slides
								$lightboxWrapper.append(response.data.lightbox_html);
								console.log('Instagram Widget: Added new lightbox slides');
								
								// Update the existing Swiper instance to recognize new slides
								const $swiperContainer = $lightboxWrapper.parent();
								if ($swiperContainer.length > 0 && $swiperContainer.get(0).swiper) {
									console.log('Instagram Widget: Updating Swiper with new slides');
									$swiperContainer.get(0).swiper.update();
								}
								
								// Initialize nested Swipers for albums in the newly added content
								const $allNestedSwipers = $lightboxWrapper.find('.image-wrapper > .swiper');
								$allNestedSwipers.each(function() {
									// Only initialize if not already initialized
									if (!this.swiper) {
										console.log('Instagram Widget: Initializing nested Swiper for album');
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
									console.log('Instagram Widget: Initializing lightbox for new items:', $newLinks.length);
									
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
												console.log('Instagram Widget: MagnificPopup opened for new item');
												const magnificPopup = $.magnificPopup.instance,
												currentElement = magnificPopup.st.el,
												$thisSwiper = this.content.find('> .swiper').get(0).swiper;
												
												console.log('Instagram Widget: Current element data-mfp-src:', currentElement.data('mfp-src'));
												
												if (this.content.find('> .swiper > .swiper-wrapper > .swiper-slide[data-uid="' + currentElement.data('mfp-src') + '"] video')) {
													this.content.find('> .swiper > .swiper-wrapper > .swiper-slide[data-uid="' + currentElement.data('mfp-src') + '"] video').trigger('play');
												}
												if (typeof $thisSwiper === 'object') {
													const targetSlideIndex = this.content.find('> .swiper > .swiper-wrapper > .swiper-slide[data-uid="' + currentElement.data('mfp-src') + '"]').index();
													console.log('Instagram Widget: Sliding to index:', targetSlideIndex);
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
								console.log('Instagram Widget: Lightbox wrapper not found, initializing for entire container');
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
			console.log('Instagram Widget: Initializing lightbox for element:', this);
			
			// Try multiple strategies to find the lightbox wrapper
			let $swipe_el = $( this ).closest( '.widget' ).find( '.wpz-insta-lightbox-wrapper > .swiper' );
			console.log('Instagram Widget: Strategy 1 (.widget context):', $swipe_el.length > 0);
			
			// Fallback 1: Look in the same Instagram widget container
			if ( $swipe_el.length === 0 ) {
				$swipe_el = $( this ).closest( '.zoom-instagram' ).find( '.wpz-insta-lightbox-wrapper > .swiper' );
				console.log('Instagram Widget: Strategy 2 (.zoom-instagram context):', $swipe_el.length > 0);
			}
			
			// Fallback 2: Look for siblings or nearby elements
			if ( $swipe_el.length === 0 ) {
				$swipe_el = $( this ).parent().parent().find( '.wpz-insta-lightbox-wrapper > .swiper' );
				console.log('Instagram Widget: Strategy 3 (parent context):', $swipe_el.length > 0);
			}
			
			console.log('Instagram Widget: Final swiper element:', $swipe_el.length > 0, $swipe_el);
			
			// Debug: Check if lightbox wrapper has content
			const $lightboxWrapper = $swipe_el.closest( '.wpz-insta-lightbox-wrapper' );
			console.log('Instagram Widget: Lightbox wrapper:', $lightboxWrapper.length > 0);
			console.log('Instagram Widget: Lightbox wrapper content:', $lightboxWrapper.html());

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
					console.log('Instagram Widget: Gallery trigger strategy 1 (.widget):', galleryTrigger.length > 0);
					
					// Fallback 1: Look in the same Instagram widget container
					if ( galleryTrigger.length === 0 ) {
						galleryTrigger = $( this ).closest( '.zoom-instagram' ).find( '.zoom-instagram-widget__items' );
						console.log('Instagram Widget: Gallery trigger strategy 2 (.zoom-instagram):', galleryTrigger.length > 0);
					}
					
					// Fallback 2: Use this element directly if it's the items container
					if ( galleryTrigger.length === 0 ) {
						galleryTrigger = $( this );
						console.log('Instagram Widget: Gallery trigger strategy 3 (this element):', galleryTrigger.length > 0);
					}
					
					console.log('Instagram Widget: Final gallery trigger:', galleryTrigger.length > 0, galleryTrigger);

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
								console.log('Instagram Widget: MagnificPopup opened');
								const magnificPopup = $.magnificPopup.instance,
								currentElement = magnificPopup.st.el,
								$thisSwiper = this.content.find( '> .swiper' ).get(0).swiper;
								
								console.log('Instagram Widget: Current element:', currentElement);
								console.log('Instagram Widget: Swiper instance:', $thisSwiper);

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
