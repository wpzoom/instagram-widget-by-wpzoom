'use strict';

jQuery(function($) {

    $('.wpzoom-instagram-widget-settings-request-type-wrapper').find('input[type=radio]').on('change', function (e) {
        e.preventDefault();

        var activeClass = $(this).val();

        var allDivs = ['with-access-token', 'with-basic-access-token', 'without-access-token'];

        var inactiveDivs = allDivs.filter(function(item){
            return item !== activeClass;
        });

        var $formTable = $(this).closest('.form-table');
        $formTable.find('.wpzoom-instagram-widget-' + activeClass + '-group').show();

        inactiveDivs.forEach(function(inactive){
            $formTable.find('.wpzoom-instagram-widget-' + inactive + '-group').hide();

        });


    });

    $('.wpzoom-instagram-widget-settings-request-type-wrapper').find('input[type=radio]:checked').change();

    var parsedHash = new URLSearchParams(
        window.location.hash.substr(1) // skip the first char (#)
    );

    if (!!parsedHash.get('access_token')) {

        var requestType = !!parsedHash.get('request_type') && parsedHash.get('request_type') === 'with-basic-access-token' ? 'with-basic-access-token' : 'with-access-token';
        var accessTokenInputName = requestType === 'with-basic-access-token' ? 'basic-access-token' : 'access-token';
        var $input = $('#wpzoom-instagram-widget-settings_' + accessTokenInputName);

        $input.val(parsedHash.get('access_token'));
        $input.closest('.form-table').find('input[type=radio]').removeAttr('checked');

        var $radio = $input.closest('.form-table').find('#wpzoom-instagram-widget-settings_' + requestType);
        $radio.prop('checked', true);
        $radio.trigger('change');

        $input.parents('form').find('#submit').click();

    }

    $('.zoom-instagram-widget .button-connect').on('click', function(event) {
        if ($(this).find('.zoom-instagarm-widget-connected').length) {
            var confirm = window.confirm(zoom_instagram_widget_admin.i18n_connect_confirm);

            if (!confirm) {
                event.preventDefault();
            }
        }
    });

    $('#wpzoom-instagram-widget-settings_is-forced-timeout').on('change', function(e){
        e.preventDefault();
        $('.wpzoom-instagram-widget-request-timeout')[$(this).is(":checked") ? 'show' :'hide']();
    }).trigger('change');

});
