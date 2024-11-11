( function( $ ) {
	$( window ).on( 'load', function () {
		var ticking = false;

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

				var getAsyncImages = function (images) {

					var isLastImage = images.length == 0;

					if (isLastImage) {
						return;
					}

					var image = images.shift();

					wp.ajax.post('wpzoom_instagram_get_image_async', {
						'media-id': image['media-id'],
						nonce: image['nonce'],
						'image-resolution': imageResolution,
						'image-width': desiredItemWidth,
						'regenerate-thumbnails': image['regenerate-thumbnails']
					}).done(function (data) {
						$list.find('li[data-media-id="' + image['media-id'] + '"] .zoom-instagram-link').css('background-image', 'url(' + data.image_src + ')');
					}).fail(function () {
					}).always(function () {
						getAsyncImages(images);
					});
				};

				if (delayedItems.length) {
					getAsyncImages(delayedItems.toArray());
				}
			});
		};

		$.fn.zoomLightbox = function () {
			return $( this ).each( function () {
				const $swipe_el = $( this ).closest( '.widget' ).find( '.wpz-insta-lightbox-wrapper > .swiper' );

				if ( $swipe_el.length > 0 ) {
					const $nested   = $swipe_el.find( '.image-wrapper > .swiper' );

					new Swiper( $swipe_el.get(0), {
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
						}
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
							}
						} );
					} );

					$( this ).find( '.zoom-instagram-link' ).magnificPopup( {
						items: {
							type: 'inline',
							src: $( this ).closest( '.widget' ).find( '.wpz-insta-lightbox-wrapper' )
						},
						closeBtnInside: false,
						mainClass: 'wpzoom-lightbox',
						midClick: true,
						callbacks: {
							open: function () {
								const magnificPopup = $.magnificPopup.instance,
								      currentElement = magnificPopup.st.el,
								      $thisSwiper = this.content.find( '> .swiper' ).get(0).swiper;
										if(this.content.find( '> .swiper > .swiper-wrapper > .swiper-slide[data-uid="' + currentElement.data( 'mfp-src' ) + '"] video')){
											this.content.find( '> .swiper > .swiper-wrapper > .swiper-slide[data-uid="' + currentElement.data( 'mfp-src' ) + '"] video' ).trigger('play');
										}
									  //console.log( currentElement );
								if ( typeof $thisSwiper === 'object' ) {
									$thisSwiper.slideTo(
										this.content.find( '> .swiper > .swiper-wrapper > .swiper-slide[data-uid="' + currentElement.data( 'mfp-src' ) + '"]' ).index()
									);
								}
							}
						}
					} );
					$( this ).find( '.zoom-instagram-link' ).addClass( 'magnific-active' );
				}
			} );
		};

		$.fn.zoomInstagramWidget = function () {
			return $(this).each(function () {
				var $list = $(this);

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

				$list.find('li').each(function (i) {
					var loop = ++i;
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
			$('.zoom-instagram-widget__items').zoomInstagramWidget();
			ticking = false;
		}

		$(window).on('resize orientationchange', requestTick);
		requestTick();

		$('.zoom-instagram-widget__items').zoomLoadAsyncImages();
		$('.zoom-instagram-widget__items[data-lightbox="1"]').zoomLightbox();

		var siteOriginInit = function () {
			var $widgets = $('.zoom-instagram-widget__items');
			if ($widgets.length) {
				$('.zoom-instagram-widget__items').zoomInstagramWidget();
				$('.zoom-instagram-widget__items').zoomLoadAsyncImages();
			}

		};

		var debounceInit = _.debounce(siteOriginInit, 1500);
		$(document).on('panels_setup_preview', debounceInit);

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
