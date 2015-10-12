'use strict';

jQuery(function($) {
    var hash = window.location.hash;

    if (hash.indexOf('access_token') > 0) {
        var input = $('#wpzoom-instagram-widget-settings_access-token');

        input.val(hash.split('=').pop());

        input.parents('form').find('#submit').click();
    }
});
