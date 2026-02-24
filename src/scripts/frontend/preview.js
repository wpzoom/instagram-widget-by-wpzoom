'use strict';

(function() {
	var TAB_PRODUCT_LINKS = 'product-links';
	var TAB_MODERATE = 'moderate';
	var BODY_CLASS_PRODUCT_LINKS = 'wpz-insta-product-links-tab-active';
	var BODY_CLASS_MODERATE = 'wpz-insta-moderate-tab-active';
	var LAYOUT_NAMES = [ 'grid', 'fullwidth', 'masonry', 'carousel' ];

	function setProductLinksTabActive( active ) {
		if ( active ) {
			document.body.classList.add( BODY_CLASS_PRODUCT_LINKS );
		} else {
			document.body.classList.remove( BODY_CLASS_PRODUCT_LINKS );
		}
	}

	function setModerateTabActive( active ) {
		if ( active ) {
			document.body.classList.add( BODY_CLASS_MODERATE );
		} else {
			document.body.classList.remove( BODY_CLASS_MODERATE );
		}
	}

	function parseTabFromUrl() {
		var params = new URLSearchParams( window.location.search || '' );
		var tab = params.get( 'wpz-insta-tab' ) || '';
		setProductLinksTabActive( tab === TAB_PRODUCT_LINKS );
		setModerateTabActive( tab === TAB_MODERATE );
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
			var isCarouselPreview = layoutName === 'carousel';
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
			// Carousel notice: show when layout is carousel
			var carouselNoticeEl = root.querySelector( '.wpz-insta-preview-carousel-notice' );
			if ( isCarouselPreview ) {
				if ( ! carouselNoticeEl ) {
					carouselNoticeEl = document.createElement( 'p' );
					carouselNoticeEl.className = 'wpz-insta-preview-carousel-notice';
					if ( wrapperForNotice && wrapperForNotice.parentNode ) {
						wrapperForNotice.parentNode.insertBefore( carouselNoticeEl, wrapperForNotice );
					}
				}
				carouselNoticeEl.textContent = 'The carousel slider will be applied on your live site. This preview shows a static layout for reference.';
				carouselNoticeEl.style.display = '';
			} else if ( carouselNoticeEl ) {
				carouselNoticeEl.style.display = 'none';
			}
			LAYOUT_NAMES.forEach( function( name ) {
				root.classList.remove( 'layout-' + name );
			} );
			root.classList.add( 'layout-' + layoutName );
			// Sync body class for layout-dependent CSS (e.g. hide .wpz-insta-loaded when not grid/masonry).
			LAYOUT_NAMES.forEach( function( name ) {
				document.body.classList.remove( 'page-layout-' + name );
			} );
			document.body.classList.add( 'page-layout-' + layoutName );
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
			if ( data[ 'view-button-text' ] ) {
				// Update only the text node to preserve the icon <span>
				var lastChild = viewBtn.lastChild;
				if ( lastChild && lastChild.nodeType === 3 ) {
					lastChild.textContent = ' ' + data[ 'view-button-text' ];
				}
			}
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

		// Single-account and multi-account both use .wpzinsta-pro-load-more-wrapper for the block.
		var loadMoreWrap = root.querySelector( '.wpzinsta-pro-load-more-wrapper' );
		if ( loadMoreWrap ) loadMoreWrap.style.display = boolVal( data[ 'show-load-more' ] ) ? '' : 'none';
		// Single: .wpzinsta-pro-load-more-btn; multi: .wpz-insta-multi-load-more (both may have .button-text inside).
		var loadMoreBtn = root.querySelector( '.wpzinsta-pro-load-more-btn' ) || root.querySelector( '.wpz-insta-multi-load-more' );
		if ( loadMoreBtn ) {
			var btnTextEl = loadMoreBtn.querySelector( '.button-text' );
			if ( data[ 'load-more-text' ] ) ( btnTextEl || loadMoreBtn ).textContent = data[ 'load-more-text' ];
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
	 * Preview Load More: load cached posts via AJAX so the user can see and
	 * link products to posts beyond the initial display count.
	 */
	function initPreviewLoadMore() {
		var loadMoreBtn = document.querySelector( '.wpzinsta-pro-load-more-btn' );
		if ( ! loadMoreBtn ) return;

		// Track offset: how many items are currently displayed
		var currentOffset = parseInt( loadMoreBtn.getAttribute( 'data-cache-offset' ) || '0', 10 );
		if ( ! currentOffset || currentOffset < 1 ) {
			var itemsList = document.querySelectorAll( '.zoom-instagram-widget__item' );
			currentOffset = itemsList.length;
		}

		// Use capturing listener to intercept before the frontend index.js handler
		loadMoreBtn.addEventListener( 'click', function( e ) {
			e.preventDefault();
			e.stopImmediatePropagation();

			if ( loadMoreBtn.classList.contains( 'loading' ) || loadMoreBtn.disabled ) return;

			var feedId    = loadMoreBtn.getAttribute( 'data-feed-id' ) || '';
			var amount    = parseInt( loadMoreBtn.getAttribute( 'data-item-amount' ) || '9', 10 );
			var imageSize = loadMoreBtn.getAttribute( 'data-image-size' ) || 'standard_resolution';
			var ajaxUrl   = ( typeof wpzInstaPreview !== 'undefined' && wpzInstaPreview.ajaxurl )
				? wpzInstaPreview.ajaxurl
				: ( typeof wpzInstaAjax !== 'undefined' ? wpzInstaAjax.ajaxurl : '' );
			var nonce = ( typeof wpzInstaPreview !== 'undefined' && wpzInstaPreview.nonce )
				? wpzInstaPreview.nonce
				: '';

			if ( ! ajaxUrl || ! feedId || ! nonce ) return;

			// Show loading state (add .loading to both the wrapper and button to trigger the CSS spinner)
			var loadMoreWrapper = loadMoreBtn.closest( '.wpzinsta-pro-load-more-wrapper' );
			if ( loadMoreWrapper ) loadMoreWrapper.classList.add( 'loading' );
			loadMoreBtn.classList.add( 'loading' );
			loadMoreBtn.disabled = true;
			var btnText = loadMoreBtn.querySelector( '.button-text' );
			var originalText = btnText ? btnText.textContent : '';
			if ( btnText ) btnText.textContent = 'Loading...';

			// Get the user ID from the preview URL (needed for unsaved/new feeds
			// where the user-id post meta doesn't exist yet).
			var previewParams = new URLSearchParams( window.location.search || '' );
			var userId = previewParams.get( '_wpz-insta_user-id' ) || '';

			var formData = new FormData();
			formData.append( 'action', 'wpzoom_instagram_preview_load_more' );
			formData.append( 'feed_id', feedId );
			formData.append( 'offset', currentOffset );
			formData.append( 'amount', amount );
			formData.append( 'image_size', imageSize );
			formData.append( '_wpnonce', nonce );
			if ( userId ) {
				formData.append( 'user_id', userId );
			}

			fetch( ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' } )
				.then( function( resp ) { return resp.json(); } )
				.then( function( data ) {
					if ( data.success && data.data && data.data.html ) {
						var itemsContainer = document.querySelector( '.zoom-instagram-widget__items' );
						if ( itemsContainer ) {
							var countBefore = itemsContainer.querySelectorAll( '.zoom-instagram-widget__item' ).length;
							itemsContainer.insertAdjacentHTML( 'beforeend', data.data.html );

							// Mark only the items just inserted via AJAX (used by CSS to hide them in fullwidth/carousel layouts).
							var allItems = itemsContainer.querySelectorAll( '.zoom-instagram-widget__item' );
							for ( var i = countBefore; i < allItems.length; i++ ) {
								allItems[ i ].classList.add( 'wpz-insta-ajax-loaded-item' );
							}
							// Initialize shimmer / loaded state for newly added items.
							// The CSS shows a shimmer animation on items without .wpz-insta-loaded.
							var newItems = [];
							for ( i = countBefore; i < allItems.length; i++ ) {
								newItems.push( allItems[ i ] );
							}
							newItems.forEach( function( item ) {
								var img = item.querySelector( 'img.zoom-instagram-link, img.zoom-instagram-link-new' );
								if ( ! img ) {
									item.classList.add( 'wpz-insta-loaded' );
									return;
								}
								if ( img.complete && img.naturalWidth > 0 ) {
									item.classList.add( 'wpz-insta-loaded' );
									return;
								}
								img.addEventListener( 'load', function() {
									item.classList.add( 'wpz-insta-loaded' );
								} );
								img.addEventListener( 'error', function() {
									item.classList.add( 'wpz-insta-loaded' );
								} );
							} );
						}

						currentOffset = data.data.offset || ( currentOffset + amount );

						// Hide button if no more cached items
						if ( ! data.data.has_more ) {
							loadMoreBtn.style.display = 'none';
						}

						// Update iframe height
						if ( typeof window.parent.wpzInstaUpdatePreviewHeight === 'function' ) {
							requestAnimationFrame( function() {
								window.parent.wpzInstaUpdatePreviewHeight();
							} );
						}

						// Apply current preview styles to new items (aspect ratio, border radius, etc.)
						if ( lastPreviewData ) {
							applyPreviewUpdate( lastPreviewData );
						}
					} else {
						// No more cached items
						loadMoreBtn.style.display = 'none';
					}
				} )
				.catch( function() {
					loadMoreBtn.style.display = 'none';
				} )
				.finally( function() {
					if ( loadMoreWrapper ) loadMoreWrapper.classList.remove( 'loading' );
					loadMoreBtn.classList.remove( 'loading' );
					loadMoreBtn.disabled = false;
					if ( btnText ) btnText.textContent = originalText;
				} );
		}, true ); // capture phase: runs before jQuery handlers
	}

	// Initialize preview load more on DOM ready
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initPreviewLoadMore );
	} else {
		initPreviewLoadMore();
	}

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

	/**
	 * Updates the visibility state of an item in the preview (moderate posts).
	 */
	function updateItemVisibility( mediaId, hidden ) {
		if ( ! mediaId ) return;

		var items = document.querySelectorAll( '.zoom-instagram-widget__item' );
		items.forEach( function( item ) {
			var btn = item.querySelector( '.wpz-insta-moderate-btn' );
			if ( btn ) {
				var btnMediaId = btn.getAttribute( 'data-media-id' ) || '';
				if ( btnMediaId === mediaId ) {
					item.classList.toggle( 'wpz-insta-post-hidden', hidden );
					if ( hidden ) {
						btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
						btn.setAttribute( 'title', 'Show post' );
					} else {
						btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
						btn.setAttribute( 'title', 'Hide post' );
					}
				}
			}
		} );
	}

	// Handle click on moderate button (eye icon) inside the iframe
	document.addEventListener( 'click', function( e ) {
		var btn = e.target.closest( '.wpz-insta-moderate-btn' );
		if ( btn ) {
			e.preventDefault();
			e.stopPropagation();
			var mediaId = btn.getAttribute( 'data-media-id' ) || '';
			if ( mediaId && window.parent ) {
				window.parent.postMessage( {
					action: 'wpz-insta-toggle-visibility',
					mediaId: mediaId
				}, '*' );
			}
		}
	} );

	// When parent switches tab or sends preview design update (no iframe reload)
	window.addEventListener( 'message', function( event ) {
		if ( ! event.data ) return;
		if ( event.data.action === 'wpz-insta-tab-change' ) {
			setProductLinksTabActive( event.data.tab === TAB_PRODUCT_LINKS );
			setModerateTabActive( event.data.tab === TAB_MODERATE );
		} else if ( event.data.action === 'wpz-insta-preview-update' && event.data.data ) {
			applyPreviewUpdate( event.data.data );
		} else if ( event.data.action === 'wpz-insta-product-link-update' ) {
			updateItemLinkedProductsClass( event.data.mediaId, event.data.hasLinks );
		} else if ( event.data.action === 'wpz-insta-moderate-update' ) {
			updateItemVisibility( event.data.mediaId, event.data.hidden );
		}
	} );

	// When viewport size changes (e.g. Desktop/Tablet/Mobile toggle), update column count only
	window.addEventListener( 'resize', function() {
		applyColNumToPreview();
	} );

	// On load: sync body.page-layout-* from .zoom-instagram.layout-* when HTML is server-rendered (before first postMessage).
	function syncBodyLayoutClass() {
		var root = document.querySelector( '.zoom-new-instagram-widget .zoom-instagram' );
		if ( ! root ) return;
		for ( var i = 0; i < LAYOUT_NAMES.length; i++ ) {
			if ( root.classList.contains( 'layout-' + LAYOUT_NAMES[ i ] ) ) {
				LAYOUT_NAMES.forEach( function( name ) {
					document.body.classList.remove( 'page-layout-' + name );
				} );
				document.body.classList.add( 'page-layout-' + LAYOUT_NAMES[ i ] );
				return;
			}
		}
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', syncBodyLayoutClass );
	} else {
		syncBodyLayoutClass();
	}
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
