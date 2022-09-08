'use strict';

jQuery( function( $ ) {
	$( '.wpz-insta-cron-notice .notice-dismiss' ).on( 'click', function () {
		const $notice = $(this).closest( '.notice' );

		$.post( ajaxurl, {
			action:  'wpz-insta_dismiss-cron-notice',
			nonce:   $notice.attr( 'data-nonce' ),
			user_id: $notice.attr( 'data-user-id' ),
		} );
	} );
} );
