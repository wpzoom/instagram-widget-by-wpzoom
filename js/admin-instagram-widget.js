'use strict';

jQuery(function($) {

    $.fn.imageMediaControl = function () {

        this.each(function () {
            var $this = $(this);

            var mediaControl = {
                // Initializes a new media manager or returns an existing frame.
                // @see wp.media.featuredImage.frame()
                frame: function () {
                    if (this._frame)
                        return this._frame;

                    this._frame = wp.media({
                        title: $this.data('title'),
                        library: {
                            type: $this.data('type')
                        },
                        button: {
                            text: $this.data('button')
                        },
                        multiple: false,
                        selection: []
                    });

                    this._frame.on('open', this.updateFrame).state('library').on('select', this.select);

                    return this._frame;
                },
                select: function () {
                    var $attachmentInput = $this.find('.attachment-input');
                    var selection = this.get('selection');
                    var attachmentId = selection.pluck('id');

                    $attachmentInput.val(attachmentId).trigger('change');
                },

                updateFrame: function () {
                },

                init: function () {

                    var $fileWrapper = $this.find('.file-wrapper');
                    var $attachmentInput = $this.find('.attachment-input');
                    var $addButton = $this.find('.add-media');
                    var $removeButton = $this.find('.remove-avatar');

                    $addButton.on('click', function (e) {
                        e.preventDefault();
                        mediaControl.frame().open();
                    });

                    $removeButton.on('click', function (e) {
                        e.preventDefault();
                        $attachmentInput.val('').trigger('change');
                    });


                    $attachmentInput.on('change', function (e) {
                        e.preventDefault();

                        var attachmentId = $this.find('.attachment-input').val();
                        if (!!attachmentId) {
                            $addButton.text($this.data('button-replace-text'));
                            $removeButton.show();
                            var attachment = wp.media.attachment(attachmentId);
                            attachment.fetch().then(function (fetched) {
                                $fileWrapper.fadeOut(400, function () {
                                    var imgSrc = fetched.url;

                                    if (_.findKey(fetched, 'thumbnail')) {
                                        imgSrc = fetched.sizes.thumbnail.url;
                                    }

                                    $(this).html('<img width="150" height="150" src="' + imgSrc + '"/>').fadeIn(400);
                                });
                            });
                        } else {
                            $fileWrapper.hide();
                            $removeButton.hide();
                            $addButton.text($this.data('button-add-text'));
                        }
                    }).trigger('change');
                }
            };

            mediaControl.frame().on('open', function () {
                var $attachmentInput = $this.find('.attachment-input');
                var frame = mediaControl.frame();
                var selection = frame.state().get('selection'),
                    attachmentId = $attachmentInput.val(),
                    attachment = wp.media.attachment(attachmentId);

                frame.reset();

                if (attachment.id) {
                    selection.add(attachment);
                }
            });
            mediaControl.init();
        });
    }

    $('.zoom-instagram-user-avatar-media-uploader').imageMediaControl();

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
