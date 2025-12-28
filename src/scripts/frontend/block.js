( function( $ ) {
	window.wpzInstaFrontendInit = function () {
		$( '.zoom-instagram-widget__items[data-lightbox="1"]' ).each( function () {
			const $swipe_el = $( this ).parent().parent().find( '.wpz-insta-lightbox-wrapper > .swiper' );

			if ( $swipe_el.length > 0 ) {
				const $nested = $swipe_el.find( '.image-wrapper > .swiper' );

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

	$( window ).on( 'load', window.wpzInstaFrontendInit );
} )( jQuery );
