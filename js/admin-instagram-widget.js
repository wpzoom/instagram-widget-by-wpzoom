'use strict';

jQuery(function($) {

    $('.wpzoom-instagram-widget-settins-request-type-wrapper').find('input[type=radio]').on('change', function (e) {
        e.preventDefault();

        var activeClass = $(this).val();
        var oposite = activeClass == 'with-access-token' ? 'without-access-token' : 'with-access-token';

        $(this).closest('.form-table').find('.wpzoom-instagram-widget-' + activeClass + '-group').show();
        $(this).closest('.form-table').find('.wpzoom-instagram-widget-' + oposite + '-group').hide();


    });

    $('.wpzoom-instagram-widget-settins-request-type-wrapper').find('input[type=radio]:checked').change();

    var hash = window.location.hash;

    if (hash.indexOf('access_token') > 0) {
        var input = $('#wpzoom-instagram-widget-settings_access-token');

        input.val(hash.split('=').pop());

        input.closest('.form-table').find('input[type=radio]').removeAttr('checked');

        var $radio = input.closest('.form-table').find('#wpzoom-instagram-widget-settings_with-access-token');
        $radio.prop('checked', true);
        $radio.trigger('change');

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
