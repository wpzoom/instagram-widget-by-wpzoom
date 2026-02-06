/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/*!*********************************************!*\
  !*** ./src/scripts/backend/cron-dismiss.js ***!
  \*********************************************/


jQuery(function ($) {
  $('.wpz-insta-cron-notice .notice-dismiss').on('click', function () {
    var $notice = $(this).closest('.notice');
    $.post(ajaxurl, {
      action: 'wpz-insta_dismiss-cron-notice',
      nonce: $notice.attr('data-nonce'),
      user_id: $notice.attr('data-user-id')
    });
  });
});
/******/ })()
;
//# sourceMappingURL=cron-dismiss.js.map