( function( $ ) {
	window.wpzInstaFrontendInit = function () {
		$.fn.zoomLightbox = function () {
			return $( this ).each( function () {
				const $swipe_el = $( this ).parent().find( '.wpz-insta-lightbox-wrapper > .swiper-container' );

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
							src: $( this ).parent().find( '.wpz-insta-lightbox-wrapper' )
						},
						closeBtnInside: false,
						mainClass: 'wpzoom-lightbox',
						midClick: true,
						callbacks: {
							open: function () {
								this.content.find( '> .swiper-container' ).get(0).swiper.slideTo(
									this.content.find( '> .swiper-container > .swiper-wrapper > .swiper-slide[data-uid="' + $( this._lastFocusedEl ).data( 'mfp-src' ) + '"]' ).index()
								);
							}
						}
					} );
				}
			} );
		};

		$('.zoom-instagram-widget__items[data-lightbox="1"]').zoomLightbox();
	};

	$( window.wpzInstaFrontendInit );
} )( jQuery );
