( function( $ ) {
	window.wpzInstaFrontendInit = function () {
		$( '.zoom-instagram-widget__items[data-lightbox="1"]' ).each( function () {
			const $swipe_el = $( this ).parent().parent().find( '.wpz-insta-lightbox-wrapper > .swiper' );

			if ( $swipe_el.length > 0 && typeof Swiper !== 'undefined' ) {
				const $nested = $swipe_el.find( '.image-wrapper > .swiper' );

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

						},
					},
				} );

				$nested.each( function () {
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

			}
		} );
	};

	$( window ).on( 'load', window.wpzInstaFrontendInit );
} )( jQuery );
