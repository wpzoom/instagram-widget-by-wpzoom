'use strict';

(function() {
	var TAB_PRODUCT_LINKS = 'product-links';
	var BODY_CLASS = 'wpz-insta-product-links-tab-active';
	var LAYOUT_NAMES = [ 'grid', 'fullwidth', 'masonry', 'carousel' ];

	function setProductLinksTabActive( active ) {
		if ( active ) {
			document.body.classList.add( BODY_CLASS );
		} else {
			document.body.classList.remove( BODY_CLASS );
		}
	}

	function parseTabFromUrl() {
		var params = new URLSearchParams( window.location.search || '' );
		var tab = params.get( 'wpz-insta-tab' ) || '';
		setProductLinksTabActive( tab === TAB_PRODUCT_LINKS );
	}

	function boolVal( v ) {
		if ( v === undefined || v === null ) return false;
		if ( typeof v === 'boolean' ) return v;
		var s = String( v ).toLowerCase();
		return s === '1' || s === 'true' || s === 'yes' || s === 'on';
	}

	// Breakpoints aligned with PHP (style_content): 1200px desktop, 768px tablet, 480px mobile
	function getEffectiveColNum( data ) {
		if ( ! data || typeof data !== 'object' ) return 3;
		var responsive = boolVal( data[ 'col-num_responsive-enabled' ] );
		var colDesktop = parseInt( data[ 'col-num' ], 10 );
		var colTablet = parseInt( data[ 'col-num_tablet' ], 10 );
		var colMobile = parseInt( data[ 'col-num_mobile' ], 10 );
		if ( isNaN( colDesktop ) || colDesktop < 0 ) colDesktop = 3;
		if ( isNaN( colTablet ) || colTablet < 0 ) colTablet = 2;
		if ( isNaN( colMobile ) || colMobile < 0 ) colMobile = 1;
		if ( ! responsive ) {
			return colDesktop;
		}
		var w = window.innerWidth;
		// PHP: min-width 1200 desktop; max-width 768 tablet; max-width 480 mobile
		if ( w > 768 ) return colDesktop;
		if ( w > 480 ) return colTablet;
		return colMobile;
	}

	/**
	 * Applies the effective column number (based on viewport + responsive settings) to the preview DOM.
	 * Uses lastPreviewData; call after applyPreviewUpdate or on window resize.
	 */
	function applyColNumToPreview() {
		if ( ! lastPreviewData ) return;
		var root = document.querySelector( '.zoom-new-instagram-widget .zoom-instagram' );
		if ( ! root ) return;
		var colNum = getEffectiveColNum( lastPreviewData );
		[].slice.call( root.classList ).forEach( function( c ) {
			if ( c.indexOf( 'columns-' ) === 0 ) root.classList.remove( c );
		} );
		root.classList.add( 'columns-' + colNum );
		var layoutInt = parseInt( lastPreviewData.layout, 10 );
		var layoutName = ! isNaN( layoutInt ) && layoutInt >= 0 && layoutInt < LAYOUT_NAMES.length ? LAYOUT_NAMES[ layoutInt ] : '';
		if ( layoutName === 'grid' || layoutName === 'masonry' ) {
			var itemsEl = root.querySelector( '.zoom-instagram-widget__items' );
			if ( itemsEl && itemsEl.style ) {
				itemsEl.style.gridTemplateColumns = 'repeat(' + colNum + ', 1fr)';
			}
		}
	}

	var lastPreviewData = null;

	function applyPreviewUpdate( data ) {
		lastPreviewData = data;
		if ( ! data || typeof data !== 'object' ) return;
		var root = document.querySelector( '.zoom-new-instagram-widget .zoom-instagram' );
		if ( ! root ) return;

		var layoutInt = parseInt( data.layout, 10 );
		if ( ! isNaN( layoutInt ) && layoutInt >= 0 && layoutInt < LAYOUT_NAMES.length ) {
			var layoutName = LAYOUT_NAMES[ layoutInt ];
			// Masonry in preview: show as grid and display a notice (masonry is applied on frontend only)
			var isMasonryPreview = layoutName === 'masonry';
			if ( isMasonryPreview ) {
				root.classList.add( 'wpz-insta-preview-masonry' );
			} else {
				root.classList.remove( 'wpz-insta-preview-masonry' );
			}
			// Masonry notice: show when layout is masonry
			var wrapperForNotice = root.querySelector( '.zoom-instagram-widget__items-wrapper' );
			var noticeEl = root.querySelector( '.wpz-insta-preview-masonry-notice' );
			if ( isMasonryPreview ) {
				if ( ! noticeEl ) {
					noticeEl = document.createElement( 'p' );
					noticeEl.className = 'wpz-insta-preview-masonry-notice';
					if ( wrapperForNotice && wrapperForNotice.parentNode ) {
						wrapperForNotice.parentNode.insertBefore( noticeEl, wrapperForNotice );
					}
				}
				noticeEl.textContent = 'This preview shows a grid for reference. The masonry layout will be applied on your live site.';
				noticeEl.style.display = '';
			} else if ( noticeEl ) {
				noticeEl.style.display = 'none';
			}
			LAYOUT_NAMES.forEach( function( name ) {
				root.classList.remove( 'layout-' + name );
			} );
			root.classList.add( 'layout-' + layoutName );
			var items = root.querySelector( '.zoom-instagram-widget__items' );
			if ( items && items.style ) {
				// Masonry in preview: use grid class so existing grid styles apply
				var itemsLayoutClass = layoutName === 'masonry' ? 'grid' : layoutName;
				LAYOUT_NAMES.forEach( function( name ) {
					items.classList.remove( 'layout-' + name );
				} );
				items.classList.add( 'layout-' + itemsLayoutClass );
				items.classList.toggle( 'swiper-wrapper', layoutName === 'carousel' );
				var itemNum = parseInt( data[ 'item-num' ], 10 );
				if ( isNaN( itemNum ) || itemNum < 1 ) itemNum = 6;
				var perpageNum = parseInt( data[ 'perpage-num' ], 10 );
				if ( isNaN( perpageNum ) || perpageNum < 1 ) perpageNum = 3;
				if ( layoutName === 'grid' || layoutName === 'masonry' ) {
					items.style.display = 'grid';
					items.style.removeProperty( '--wpz-insta-perpage' );
				} else if ( layoutName === 'fullwidth' ) {
					items.style.display = 'grid';
					items.style.gridTemplateColumns = 'repeat(' + itemNum + ', 1fr)';
					items.style.removeProperty( '--wpz-insta-perpage' );
				} else if ( layoutName === 'carousel' ) {
					// Carousel uses "Number of visible items" (perpage-num)
					items.style.display = 'flex';
					items.style.gridTemplateColumns = '';
					items.style.setProperty( '--wpz-insta-perpage', String( perpageNum ) );
				} else {
					items.style.display = '';
					items.style.gridTemplateColumns = '';
					items.style.removeProperty( '--wpz-insta-perpage' );
				}
			}
			var wrapper = root.querySelector( '.zoom-instagram-widget__items-wrapper' );
			if ( wrapper ) {
				wrapper.classList.toggle( 'swiper', layoutName === 'carousel' );
			}
		}

		applyColNumToPreview();

		var spacing = parseFloat( data[ 'spacing-between' ], 10 );
		var itemsListGap = root.querySelector( '.zoom-instagram-widget__items' );
		if ( itemsListGap && itemsListGap.style ) {
			if ( ! isNaN( spacing ) && spacing >= 0 ) {
				var gapSuffix = { 0: 'px', 1: 'em', 2: '%' }[ parseInt( data[ 'spacing-between-suffix' ], 10 ) ] || 'px';
				itemsListGap.style.setProperty( 'gap', spacing + gapSuffix, 'important' );
			} else {
				itemsListGap.style.removeProperty( 'gap' );
			}
		}

		var featEnable = boolVal( data[ 'featured-layout-enable' ] );
		var featId = parseInt( data[ 'featured-layout' ], 10 );
		root.classList.toggle( 'featured-layout', featEnable && featId > 0 );
		[].slice.call( root.classList ).forEach( function( c ) {
			if ( c.indexOf( 'featured-layout-' ) === 0 ) root.classList.remove( c );
		} );
		if ( featEnable && featId > 0 ) {
			root.classList.add( 'featured-layout-' + featId );
		}

		var header = root.querySelector( '.zoom-instagram-widget__header' );
		if ( header ) {
			var nameEl = header.querySelector( '.zoom-instagram-widget__header-name' );
			if ( nameEl ) nameEl.style.display = boolVal( data[ 'show-account-name' ] ) ? '' : 'none';
			var userEl = header.querySelector( '.zoom-instagram-widget__header-user' );
			if ( userEl ) userEl.style.display = boolVal( data[ 'show-account-username' ] ) ? '' : 'none';
			var stats = header.querySelector( '.wpz-insta-stats' );
			if ( stats ) stats.style.display = boolVal( data[ 'show-account-stats' ] ) ? '' : 'none';
			var stories = header.querySelector( '.wpz-insta-stories' );
			if ( stories ) stories.style.display = boolVal( data[ 'show-stories' ] ) ? '' : 'none';
			var leftCol = header.querySelector( '.zoom-instagram-widget__header-column-left' );
			if ( leftCol ) leftCol.style.display = boolVal( data[ 'show-account-image' ] ) ? '' : 'none';
			var bio = header.querySelector( '.zoom-instagram-widget__header-bio' );
			if ( bio ) bio.style.display = boolVal( data[ 'show-account-bio' ] ) ? '' : 'none';
		}
		// Display verified badge: query from root; use important to override CSS
		var badge = root.querySelector( '.wpz-insta-badge' );
		if ( badge ) {
			badge.style.setProperty( 'display', boolVal( data[ 'show-account-badge' ] ) ? '' : 'none', 'important' );
		}

		var viewBtn = root.querySelector( '.wpz-insta-view-on-insta-button' );
		if ( viewBtn ) {
			viewBtn.style.display = boolVal( data[ 'show-view-button' ] ) ? '' : 'none';
			if ( data[ 'view-button-text' ] ) viewBtn.textContent = data[ 'view-button-text' ];
			if ( data[ 'view-button-bg-color' ] != null && data[ 'view-button-bg-color' ] !== '' ) {
				viewBtn.style.setProperty( 'background-color', data[ 'view-button-bg-color' ], 'important' );
			} else {
				viewBtn.style.removeProperty( 'background-color' );
			}
		}

		if ( data[ 'bg-color' ] != null && data[ 'bg-color' ] !== '' ) {
			root.style.setProperty( 'background-color', data[ 'bg-color' ], 'important' );
		} else {
			root.style.removeProperty( 'background-color' );
		}
		if ( data[ 'font-size' ] ) {
			var fsSuffix = { 0: 'px', 1: 'em', 2: 'pt' }[ parseInt( data[ 'font-size-suffix' ], 10 ) ] || 'px';
			root.style.fontSize = data[ 'font-size' ] + fsSuffix;
		}
		if ( data[ 'spacing-around' ] !== undefined && data[ 'spacing-around' ] !== '' ) {
			var aroundSuffix = { 0: 'px', 1: 'em', 2: '%' }[ parseInt( data[ 'spacing-around-suffix' ], 10 ) ] || 'px';
			root.style.setProperty( 'padding', data[ 'spacing-around' ] + aroundSuffix, 'important' );
		} else {
			root.style.removeProperty( 'padding' );
		}

		var loadMoreWrap = root.querySelector( '.wpzinsta-pro-load-more-wrapper' );
		if ( loadMoreWrap ) loadMoreWrap.style.display = boolVal( data[ 'show-load-more' ] ) ? '' : 'none';
		var loadMoreBtn = root.querySelector( '.wpzinsta-pro-load-more-btn' );
		if ( loadMoreBtn ) {
			if ( data[ 'load-more-text' ] ) loadMoreBtn.textContent = data[ 'load-more-text' ];
			if ( data[ 'load-more-color' ] != null && data[ 'load-more-color' ] !== '' ) {
				loadMoreBtn.style.setProperty( 'background-color', data[ 'load-more-color' ], 'important' );
			} else {
				loadMoreBtn.style.removeProperty( 'background-color' );
			}
		}

		// Image aspect ratio (match frontend: grid + square = 1/1, grid + portrait = 3/4, fullwidth/carousel = 1)
		var layoutNameForAspect = layoutInt >= 0 && layoutInt < LAYOUT_NAMES.length ? LAYOUT_NAMES[ layoutInt ] : 'grid';
		var imageAspectRatio = ( data[ 'image-aspect-ratio' ] || 'square' ).toLowerCase();
		var aspectValue = '1';
		if ( layoutNameForAspect === 'grid' ) {
			aspectValue = imageAspectRatio === 'portrait' ? '3/4' : '1/1';
		}
		var feedImgs = root.querySelectorAll( '.zoom-instagram-widget__item img:not(.wpz-insta-product-popover-item-img)' );
		feedImgs.forEach( function( img ) {
			img.style.setProperty( 'aspect-ratio', aspectValue, 'important' );
		} );
		var innerWraps = root.querySelectorAll( '.zoom-instagram-widget__item-inner-wrap' );
		innerWraps.forEach( function( wrap ) {
			wrap.classList.toggle( 'aspect-portrait', layoutNameForAspect === 'grid' && imageAspectRatio === 'portrait' );
			wrap.classList.toggle( 'aspect-square', layoutNameForAspect === 'grid' && imageAspectRatio !== 'portrait' );
		} );

		// Rounded corners: apply border-radius to item inner wraps (matches display PHP)
		var borderRadius = parseFloat( data[ 'border-radius' ], 10 );
		var radiusSuffix = { 0: 'px', 1: 'em', 2: '%' }[ parseInt( data[ 'border-radius-suffix' ], 10 ) ] || 'px';
		innerWraps.forEach( function( wrap ) {
			if ( ! isNaN( borderRadius ) && borderRadius >= 0 ) {
				wrap.style.setProperty( 'border-radius', borderRadius + radiusSuffix, 'important' );
			} else {
				wrap.style.removeProperty( 'border-radius' );
			}
		} );

		var itemsListAll = root.querySelectorAll( '.zoom-instagram-widget__item' );
		itemsListAll.forEach( function( item ) {
			item.classList.toggle( 'media-icons-normal', boolVal( data[ 'show-media-type-icons' ] ) );
			item.classList.toggle( 'media-icons-hover', boolVal( data[ 'hover-media-type-icons' ] ) );
			item.classList.toggle( 'date-hover', boolVal( data[ 'hover-date' ] ) );
		} );

		// Show overlay with Instagram icon on hover: hide overlay when disabled (PHP only outputs overlay when enabled)
		var showOverlay = boolVal( data[ 'show-overlay' ] );
		var overlays = root.querySelectorAll( '.zoom-instagram-widget__overlay' );
		overlays.forEach( function( overlay ) {
			overlay.style.display = showOverlay ? '' : 'none';
		} );

		// Recalculate iframe height after layout/design changes (e.g. grid, masonry, fullwidth, carousel)
		if ( typeof window.parent.wpzInstaUpdatePreviewHeight === 'function' ) {
			requestAnimationFrame( function() {
				window.parent.wpzInstaUpdatePreviewHeight();
			} );
		}
	}

	// Initial state from URL (so direct visit to Product Links tab shows buttons)
	parseTabFromUrl();

	/**
	 * Updates the has-linked-products class on an item based on product link changes.
	 * Also updates button text and badge visibility.
	 *
	 * @param {string} mediaId - The Instagram media ID.
	 * @param {boolean} hasLinks - Whether the item has linked products.
	 */
	function updateItemLinkedProductsClass( mediaId, hasLinks ) {
		if ( ! mediaId ) return;

		// Find the item by checking the "Link to a product" button's data-media-id attribute
		// This is the most reliable way since the button always has this attribute in preview mode
		var items = document.querySelectorAll( '.zoom-instagram-widget__item' );
		items.forEach( function( item ) {
			var matchFound = false;

			// Primary method: check the "Link to a product" button's data-media-id (preview mode)
			var btn = item.querySelector( '.wpz-insta-link-product-btn' );
			if ( btn ) {
				var btnMediaId = btn.getAttribute( 'data-media-id' ) || '';
				if ( btnMediaId === mediaId ) {
					matchFound = true;
				}
			}

			// Fallback: check data-mfp-src on inner link/image elements (non-preview mode)
			if ( ! matchFound ) {
				var innerLink = item.querySelector( '.zoom-instagram-link' );
				if ( innerLink ) {
					var linkMediaId = innerLink.getAttribute( 'data-mfp-src' ) || '';
					if ( linkMediaId === mediaId ) {
						matchFound = true;
					}
				}
			}

			if ( matchFound ) {
				// Toggle has-linked-products class on the item
				item.classList.toggle( 'has-linked-products', hasLinks );

				// Update button text and icon
				if ( btn ) {
					btn.classList.toggle( 'wpz-insta-link-product-btn--linked', hasLinks );
					if ( hasLinks ) {
						btn.innerHTML = '<span class="dashicons dashicons-edit"></span> Edit Product Link';
						btn.setAttribute( 'title', 'Edit Product Link' );
					} else {
						btn.innerHTML = '<span class="dashicons dashicons-cart"></span> Link to a product';
						btn.setAttribute( 'title', 'Link to a product' );
					}
				}

				// Toggle badge visibility
				var badge = item.querySelector( '.wpz-insta-product-badge' );
				if ( badge ) {
					badge.style.display = hasLinks ? '' : 'none';
				} else if ( hasLinks ) {
					// Create badge if doesn't exist and has links
					var innerWrap = item.querySelector( '.zoom-instagram-widget__item-inner-wrap' );
					if ( innerWrap ) {
						var newBadge = document.createElement( 'span' );
						newBadge.className = 'wpz-insta-product-badge';
						newBadge.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 6v.75H5.513c-.96 0-1.764.724-1.865 1.679l-1.263 12A1.875 1.875 0 0 0 4.25 22.5h15.5a1.875 1.875 0 0 0 1.865-2.071l-1.263-12a1.875 1.875 0 0 0-1.865-1.679H16.5V6a4.5 4.5 0 1 0-9 0ZM12 3a3 3 0 0 0-3 3v.75h6V6a3 3 0 0 0-3-3Zm-3 8.25a3 3 0 1 0 6 0v-.75a.75.75 0 0 1 1.5 0v.75a4.5 4.5 0 1 1-9 0v-.75a.75.75 0 0 1 1.5 0v.75Z" clip-rule="evenodd" /></svg>Product';
						innerWrap.appendChild( newBadge );
					}
				}
			}
		} );
	}

	// When parent switches tab or sends preview design update (no iframe reload)
	window.addEventListener( 'message', function( event ) {
		if ( ! event.data ) return;
		if ( event.data.action === 'wpz-insta-tab-change' ) {
			setProductLinksTabActive( event.data.tab === TAB_PRODUCT_LINKS );
		} else if ( event.data.action === 'wpz-insta-preview-update' && event.data.data ) {
			applyPreviewUpdate( event.data.data );
		} else if ( event.data.action === 'wpz-insta-product-link-update' ) {
			// Handle product link changes from the parent modal
			updateItemLinkedProductsClass( event.data.mediaId, event.data.hasLinks );
		}
	} );

	// When viewport size changes (e.g. Desktop/Tablet/Mobile toggle), update column count only
	window.addEventListener( 'resize', function() {
		applyColNumToPreview();
	} );
})();

document.body.addEventListener(
	'load',
	function( event ) {
		if ( 'IMG' == event.target.tagName && event.target.closest( '.zoom-instagram-widget__item' ) && typeof window.parent.wpzInstaUpdatePreviewHeight === 'function' ) {
			window.parent.wpzInstaUpdatePreviewHeight();
		}
	},
	true
);
