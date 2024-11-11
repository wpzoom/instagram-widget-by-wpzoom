( function( $ ) {
	window.wpzInstaFrontendInit = function () {
		$( '.zoom-instagram-widget__items[data-lightbox="1"]' ).each( function () {
			const $swipe_el = $( this ).parent().parent().find( '.wpz-insta-lightbox-wrapper > .swiper' );

			if ( $swipe_el.length > 0 ) {
				const $nested = $swipe_el.find( '.image-wrapper > .swiper' );

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

				$nested.each( function () {
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
						src: $( this ).parent().parent().find( '.wpz-insta-lightbox-wrapper' )
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

							//console.log( typeof $thisSwiper );

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

		// Add Stories functionality
		function initInstagramStories() {
			$('.zoom-instagram-widget__header-column-left img[data-stories="true"]').each(function() {
				const $img = $(this);
				const $popup = $img.siblings('.wpz-insta-stories-popup');
				
				// Only initialize if popup content exists
				if ($popup.length > 0) {
					$img.magnificPopup({
						type: 'inline',
						mainClass: 'wpz-insta-stories-popup-container',
						closeMarkup: '<button title="%title%" type="button" class="mfp-close wpz-insta-stories-close">×</button>',
						items: {
							src: $popup.get(0),
							type: 'inline'
						},
						callbacks: {
							open: function() {
								const $popup = $(this.content);
								$popup.find('.wpz-insta-story-item:first').addClass('active');
								
								const firstVideo = $popup.find('.wpz-insta-story-item.active video')[0];
								if (firstVideo) {
									firstVideo.currentTime = 0;
									firstVideo.play();
								}
							},
							close: function() {
								const $popup = $(this.content);
								$popup.find('video').each(function() {
									this.pause();
								});
								$popup.find('.wpz-insta-story-item').removeClass('active');
							}
						},
						removalDelay: 300,
						midClick: true
					});
				}
			});

			// Handle story navigation
			$(document).on('click', '.wpz-insta-story-item', function(e) {
				if (!$(e.target).is('a')) {
					const $items = $(this).parent().children('.wpz-insta-story-item');
					const currentIndex = $items.index(this);
					const nextIndex = (currentIndex + 1) % $items.length;
					
					// Pause current video if any
					const currentVideo = $(this).find('video')[0];
					if (currentVideo) {
						currentVideo.pause();
					}
					
					$(this).removeClass('active');
					$items.eq(nextIndex).addClass('active');
					
					// Play next video if any
					const nextVideo = $items.eq(nextIndex).find('video')[0];
					if (nextVideo) {
						nextVideo.currentTime = 0;
						nextVideo.play();
					}
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

					// Pause current video
					const currentVideo = $current.find('video')[0];
					if (currentVideo) {
						currentVideo.pause();
					}

					$current.removeClass('active');
					$items.eq(nextIndex).addClass('active');

					// Play next video
					const nextVideo = $items.eq(nextIndex).find('video')[0];
					if (nextVideo) {
						nextVideo.currentTime = 0;
						nextVideo.play();
					}
				}
			});
		}

		// Initialize stories functionality
		initInstagramStories();
	};

	$( window ).on( 'load', window.wpzInstaFrontendInit );
} )( jQuery );
