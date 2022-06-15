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
				const $swipe_el = $( this ).closest( '.widget' ).find( '.wpz-insta-lightbox-wrapper > .swiper-container' );

				if ( $swipe_el.length > 0 ) {
					const $nested   = $swipe_el.find( '.image-wrapper > .swiper-container' );

					new Swiper( $swipe_el.get(0), {
						direction: 'horizontal',
						loop: false,
						spaceBetween: 20,
						autoHeight: true,
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
								      $thisSwiper = this.content.find( '> .swiper-container' ).get(0).swiper;
									  //console.log( currentElement );
								if ( typeof $thisSwiper === 'object' ) {
									$thisSwiper.slideTo(
										this.content.find( '> .swiper-container > .swiper-wrapper > .swiper-slide[data-uid="' + currentElement.data( 'mfp-src' ) + '"]' ).index()
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

				if (imageLazyLoading) {
					$list.find('a.zoom-instagram-link').lazy();
				}

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
	} );
} )( jQuery );
