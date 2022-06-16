'use strict';

document.body.addEventListener(
	'load',
	function( event ) {
		if ( 'IMG' == event.target.tagName && event.target.closest( '.zoom-instagram-widget__item' ) && typeof window.parent.wpzInstaUpdatePreviewHeight === 'function' ) {
			window.parent.wpzInstaUpdatePreviewHeight();
		}
	},
	true
);
