'use strict';

jQuery(function($) {
    var hash = window.location.hash;

    if (hash.indexOf('access_token') > 0) {
        var input = $('#wpzoom-instagram-widget-settings_access-token');

        input.val(hash.split('=').pop());

        input.parents('form').find('#submit').click();
    }

    $('.zoom-instagram-widget .button-connect').on('click', function(event) {
        if ($(this).find('.zoom-instagarm-widget-connected').length) {
            var confirm = window.confirm(zoom_instagram_widget_admin.i18n_connect_confirm);

            if (!confirm) {
                event.preventDefault();
            }
        }
    });
});
