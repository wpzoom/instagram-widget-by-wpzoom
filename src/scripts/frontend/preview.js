'use strict';

(function() {
	var TAB_PRODUCT_LINKS = 'product-links';
	var BODY_CLASS = 'wpz-insta-product-links-tab-active';

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

	// Initial state from URL (so direct visit to Product Links tab shows buttons)
	parseTabFromUrl();

	// When parent switches tab without reloading iframe
	window.addEventListener( 'message', function( event ) {
		if ( event.data && event.data.action === 'wpz-insta-tab-change' ) {
			setProductLinksTabActive( event.data.tab === TAB_PRODUCT_LINKS );
		}
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
