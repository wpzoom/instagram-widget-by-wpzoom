'use strict';

jQuery( function( $ ) {

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

    /*var parsedHash = new URLSearchParams(
        window.location.hash.substr(1) // skip the first char (#)
    );

	if ( !!parsedHash.get( 'access_token' ) ) {
		let requestType = !!parsedHash.get( 'request_type' ) && parsedHash.get( 'request_type' ) === 'with-basic-access-token' ? 'with-basic-access-token' : 'with-access-token';
		let token = parsedHash.get( 'access_token' );

		$.post(
			ajaxurl,
			{
				action: 'wpz-insta_connect-user',
				nonce: zoom_instagram_widget_admin.nonce,
				type: requestType,
				token: token
			}
		).done( function( data ) {
			console.dir(data);
		} );
	}*/

	if ( window.opener && window.location.hash.length > 1 && window.location.hash.includes( 'access_token' ) ) {
		window.opener.handleReturnedToken( window.location.hash );
		window.close();
	}

	if ( $('#title').length > 0 ) {
		$('#title').attr( 'size', $('#title').val().trim().length + 1 );
		$('#title').on( 'input', function(){ $(this).attr( 'size', $(this).val().trim().length + 1 ); } );
	}

	if ( $( '.wpz-insta_feed-edit-nav' ).length > 0 ) {
		if ( window.location.hash ) {
			setTab( window.location.hash );
		}
		$( '.wpz-insta_feed-edit-nav a' ).on( 'click', function() { setTab( $( this ).attr( 'href' ) ) } );
	}

	$( '.wpz-insta-wrap .account-options .account-option-button:not(.disabled)' ).on( 'click', function( e ) {
		e.preventDefault();

		if ( $( this ).is( '#wpz-insta_connect-personal' ) || $( this ).is( '#wpz-insta_connect-business' ) ) {
			authenticateInstagram( $( this ).attr( 'href' ) );
		}
	} );

	$( '#wpz-insta_account-token-input' ).on( 'input', function() {
		$( '#wpz-insta_account-token-button' ).toggleClass( 'disabled', ( $( '#wpz-insta_account-token-input' ).val().trim().length <= 0 ) );
	} );

	$( '#wpz-insta_modal-dialog' ).find( '.wpz-insta_modal-dialog_ok-button, .wpz-insta_modal-dialog_close-button' ).on( 'click', function( e ) {
		e.preventDefault();

		closeConnectDoneDialog( $( '#wpz-insta_modal-dialog' ).hasClass( 'success' ) );
	} );

	function setTab( id ) {
		if ( id ) {
			const $target = $( '.wpz-insta_feed-edit-nav a[href="' + id + '"]' ),
			      $tabs   = $target.closest( 'form' ).find( '.wpz-insta_tabs-content .wpz-insta_tabs-tab' );

			$target.closest( '.wpz-insta_feed-edit-nav' ).find( 'li' ).removeClass( 'active' );
			$target.closest( 'li' ).addClass( 'active' );

			$tabs.removeClass( 'active' );
			$tabs.filter( '[data-id="' + id + '"]' ).addClass( 'active' );
		}
	}

	function authenticateInstagram( url, callback ) {
		let popupWidth = 700,
		    popupHeight = 500,
		    popupTop = ( window.screen.height - popupHeight ) / 2,
		    popupLeft = ( window.screen.width - popupWidth ) / 2;

		window.open( url, '', 'width=' + popupWidth + ',height=' + popupHeight + ',left=' + popupLeft + ',top=' + popupTop );
	}

	function parseQuery( queryString ) {
		var query = {};
		var pairs = ( queryString[0] === '?' ? queryString.substr(1) : queryString ).split( '&' );

		for ( var i = 0; i < pairs.length; i++ ) {
			var pair = pairs[i].split( '=' );
			query[ decodeURIComponent( pair[0] ) ] = decodeURIComponent( pair[1] || '' );
		}

		return query;
	}

	function showConnectDoneDialog( success ) {
		let title = success ? zoom_instagram_widget_admin.i18n_connect_success_title : zoom_instagram_widget_admin.i18n_connect_fail_title,
		    content = success ? zoom_instagram_widget_admin.i18n_connect_success_content : zoom_instagram_widget_admin.i18n_connect_fail_content,
		    $dialog = $( '#wpz-insta_modal-dialog' );

		$dialog.find( '.wpz-insta_modal-dialog_header-title' ).html( title );
		$dialog.find( '.wpz-insta_modal-dialog_content' ).html( content );
		$dialog.removeClass( 'open success fail' ).addClass( 'open ' + ( success ? 'success' : 'fail' ) );
	}

	function closeConnectDoneDialog( success ) {
		$( '#wpz-insta_modal-dialog' ).removeClass( 'open' );

		if ( success ) {
			window.location.replace( zoom_instagram_widget_admin.feeds_url );
		}
	}

	window.handleReturnedToken = function( raw ) {
		if ( raw && raw.length > 1 ) {
			if ( raw[0] === '#' ) {
				raw = raw.substring( 1 );
			}

			if ( raw.length > 1 ) {
				let parts = parseQuery( raw );

				if ( 'access_token' in parts ) {
					let token = parts.access_token;

					$.post(
						ajaxurl,
						{
							action: 'wpz-insta_connect-user',
							nonce: zoom_instagram_widget_admin.nonce,
							token: token
						}
					).done( function( data ) {
						showConnectDoneDialog( data.success );
					} );
				}
			}
		}
	};

} );
