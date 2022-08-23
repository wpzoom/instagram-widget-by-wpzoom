'use strict';

jQuery( function( $ ) {
    var $imageMediaControlTarget = $();

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
                    var $attachmentInput = $this.find('.attachment-input').add($imageMediaControlTarget.find('input#wpz-insta_account-photo'));
                    var selection = this.get('selection');
                    var attachmentId = selection.pluck('id');

                    $attachmentInput.val('' + attachmentId).trigger('change');
                    $imageMediaControlTarget.find('img.wpz-insta_profile-photo').attr('src', '' + selection.first().toJSON().sizes.thumbnail.url);
                },

                updateFrame: function () {
                },

                init: function () {

                    var $fileWrapper = $this.find('.file-wrapper');
                    var $attachmentInput = $this.find('.attachment-input, #wpz-insta_account-photo');
                    var $addButton = $this.find('.add-media, #wpz-insta_edit-account-photo');
                    var $removeButton = $this.find('.remove-avatar, #wpz-insta_reset-account-photo');

                    $addButton.on('click', function (e) {
                        e.preventDefault();
                        $imageMediaControlTarget = $(this).closest('.wpz-insta_account-photo-wrapper');
                        mediaControl.frame().open();
                    });

                    $removeButton.on('click', function (e) {
                        e.preventDefault();

                        $('#the-list input.wpz-insta_profile-photo-input').val('-1').trigger('change');
                        $('#the-list img.wpz-insta_profile-photo').attr('src', zoom_instagram_widget_admin.default_user_thumbnail);
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
                var $attachmentInput = $this.find('.attachment-input, #wpz-insta_account-photo');
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

	$('.zoom-instagram-user-avatar-media-uploader, .inline-edit-wpz-insta_user .wpz-insta_quick-edit-columns .wpz-insta_two-columns').imageMediaControl();

	$(window).on('beforeunload', function (e) {
		if ( ! $.isEmptyObject( formChangedValues ) && ! formSubmitted ) {
			e.preventDefault();
		}
	});

	$('#the-list').on('click', '#wpz-insta_reconnect', function (e) {
		e.preventDefault();
		window.wpzInstaAuthenticateInstagram( $(this).attr('href') );
	});

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

	setTimeout(
		() => {
			if ( 'hash' in window.location && '' != ('' + window.location.hash).trim() ) {
				let $elem = $('.edit-php.post-type-wpz-insta_user #the-list').find( ('' + window.location.hash).trim() );

				if ( $elem.length > 0 ) {
					$elem.find('button.editinline').trigger('click');
				}
			}
		},
		100
	);

	if ( window.opener && window.location.hash.length > 1 && window.location.hash.includes( 'access_token' ) ) {
		window.opener.wpzInstaHandleReturnedToken( window.location );
		window.close();
	}

	$( '#screen-meta #wpz-insta_account-photo-hide, #screen-meta #wpz-insta_account-bio-hide, #screen-meta #wpz-insta_account-token-hide, #screen-meta #wpz-insta_actions-hide' ).closest( 'label' ).remove();

	$('#titlediv').remove();

	if ( $('#title').length > 0 ) {
		$('#title').attr( 'size', $('#title').val().trim().length + 3 );
		$('#title').on( 'input', function(){ $(this).attr( 'size', $(this).val().trim().length + 3 ); } );
	}

	if ( $( '.wpz-insta_feed-edit-nav' ).length > 0 ) {
		if ( window.location.hash ) {
			setTab( window.location.hash );
		}
		$( '.wpz-insta_feed-edit-nav a' ).on( 'click', function() { setTab( $( this ).attr( 'href' ) ) } );
	}

	$( '#wpz-insta_show-pro' ).on( 'change', function( e ) {
		e.preventDefault();

		$( this ).closest( '.wpz-insta_sidebar' ).toggleClass( 'show-pro', this.checked );
	} );

	$( '.wpz-insta-wrap .account-options .account-option-button' ).on( 'click', function( e ) {
		e.preventDefault();

		if ( ! $( this ).is( '.disabled' ) ) {
			if ( $( this ).is( '#wpz-insta_connect-personal' ) || $( this ).is( '#wpz-insta_connect-business' ) ) {
				window.wpzInstaAuthenticateInstagram( $( this ).attr( 'href' ) );
			} else if ( $( this ).is( '#wpz-insta_account-token-button' ) ) {
				window.wpzInstaHandleReturnedToken( $( '#wpz-insta_account-token-input' ).val().trim().replace( /[^a-z0-9-_.]+/gi, '' ), true );
			}
		}
	} );

	$( '#wpz-insta_account-token-input' ).on( 'input', function() {
		$( '#wpz-insta_account-token-button' ).toggleClass( 'disabled', ( $( '#wpz-insta_account-token-input' ).val().trim().length <= 0 ) );
	} );

	$( '.wpz-insta_sidebar-section-layout input[name="_wpz-insta_layout"]' ).on( 'change', function() {
		const $colNumOption    = $( this ).closest( '.wpz-insta_sidebar-section-layout' ).find( 'input[name="_wpz-insta_col-num"]' ).closest( '.wpz-insta_table-row' ),
		      $perPageOption   = $( this ).closest( '.wpz-insta_sidebar-section-layout' ).find( 'input[name="_wpz-insta_perpage-num"]' ).closest( '.wpz-insta_table-row' ),
		      $featOption      = $( this ).closest( '.wpz-insta_sidebar-section-layout' ).find( '.wpz-insta_table-row-featured-layout' ),
		      $featOptionWrap  = $featOption.closest( '.wpz-insta_feed-only-pro' ),
		      $parentLeftSect  = $( this ).closest( '.wpz-insta_sidebar-left-section' ),
		      $proFieldset     = $parentLeftSect.find( '.wpz-insta_sidebar-section-feed .wpz-insta_show-on-hover fieldset.wpz-insta_feed-only-pro.wpz-insta_pro-only' ),
		      $loadMoreOptions = $parentLeftSect.find( '.wpz-insta_sidebar-section-load-more' ),
		      $toggleItems     = $colNumOption.add( $proFieldset ).add( $loadMoreOptions );

		$toggleItems.toggleClass( 'hidden', $( this ).val() == '1' || $( this ).val() == '3' );

		$( '.wpz-insta-admin .wpz-insta_widget-preview .wpz-insta_widget-preview-view' ).toggleClass( 'layout-fullwidth', $( this ).val() == '1' );

		$featOption.toggleClass( 'hidden', $( this ).val() != '0' );
		if ( ! $( '.wpz-insta_sidebar .wpz-insta_sidebar-left' ).hasClass( '.is-pro' ) ) $featOptionWrap.toggleClass( 'hidden', $( this ).val() != '0' );
		$perPageOption.toggleClass( 'hidden', $( this ).val() != '3' );
	} );

	$( '.wpz-insta_sidebar-section-layout input[name="_wpz-insta_col-num"]' ).on( 'input', function() {
		if ( $( '.wpz-insta_sidebar-section-layout input[name="_wpz-insta_layout"]:checked' ).val() == '0' ) {
			const colNum               = parseInt( $( this ).closest( '.wpz-insta_sidebar-section-layout' ).find( 'input[name="_wpz-insta_col-num"]' ).val() ),
			      $featuredLayouts     = $( this ).closest( '.wpz-insta_table' ).find( 'label.featured-layout' ),
			      $featuredLayoutsWrap = $featuredLayouts.closest( '.wpz-insta_table-row' );

			if ( colNum < 3 || colNum > 6 ) {
				$featuredLayoutsWrap.addClass( 'hidden' );
			} else {
				$featuredLayoutsWrap.removeClass( 'hidden' );
				$featuredLayouts.addClass( 'hidden' );
				$featuredLayouts.each( function() {
					if ( $( this ).is( '.featured-layout-columns_' + colNum ) ) {
						$( this ).removeClass( 'hidden' );
					}
				} );
			}
		}
	} );

	$( '.wpz-insta_sidebar-section-layout input[name="_wpz-insta_col-num_responsive-enabled"]' ).on( 'change', function() {
		$( this ).closest( '.wpz-insta_responsive-table-row' ).toggleClass( 'wpz-insta_responsive-enabled', $( this ).is( ':checked' ) );
	} );

	$( '#_wpz-insta_featured-layout-enable' ).on( 'change', function() {
		$( this ).closest( '.wpz-insta_table-row' ).find( '.wpz-insta_image-select' ).toggleClass( 'hidden', ! $( this ).is( ':checked' ) );
	} );

	$( '#wpz-insta_modal-dialog' ).find( '.wpz-insta_modal-dialog_ok-button, .wpz-insta_modal-dialog_close-button' ).on( 'click', function( e ) {
		e.preventDefault();

		let $dialog = $('#wpz-insta_modal-dialog');

		window.wpzInstaCloseConnectDoneDialog( $dialog.hasClass('success'), $dialog.hasClass('update') );
	} );

	$( '#wpz-insta_feed-user-select-btn' ).on( 'click', function( e ) {
		e.preventDefault();

		$( '#wpz-insta_tabs-config-cnnct' )
			.removeClass( 'active' )
			.addClass( 'active' )
			.prev( '.wpz-insta_sidebar' )
				.removeClass( 'active' );

		$( '#wpz-insta_tabs-config-cnnct' ).closest( '.wpz-insta_tabs-content' ).find( '> .wpz-insta_sidebar').addClass( 'hide' );
	} );

	$( '#wpz-insta_feed-user-remove-btn' ).on( 'click', function( e ) {
		e.preventDefault();

		let $btn    = $( '#wpz-insta_feed-user-select-btn' ),
		    $select = $btn.closest( '.wpz-insta_feed-user-select' ),
		    $info   = $select.find( '.wpz-insta_feed-user-select-info' );

		$( '#wpz-insta_user-id' ).val( '-1' ).trigger( 'change' );
		$( '#wpz-insta_user-token' ).val( '-1' ).trigger( 'change' );

		$( '#wpz-insta_user-token, #wpz-insta_check-new-posts-interval-number, #wpz-insta_enable-request-timeout' )
			.closest( '.wpz-insta_sidebar-section' )
				.removeClass( 'active' );

		$( '#wpz-insta_widget-preview-links' ).addClass( 'disabled' );

		$select.removeClass( 'is-set' );
		$info.find( '.wpz-insta_feed-user-select-info-name' ).html( 'None' );
		$info.find( '.wpz-insta_feed-user-select-info-type' ).html( 'None' );
		$select.closest( '.wrap' ).find( '.wpz-insta_settings-header .wpz-insta_feed-edit-nav li:not(:first-child)' ).addClass( 'disable' );
	} );

	$( '#wpz-insta_tabs-config-cnnct .wpz-insta_tabs-config-connect-accounts li' ).on( 'click', function( e ) {
		e.preventDefault();

		let $btn    = $( '#wpz-insta_feed-user-select-btn' ),
		    $select = $btn.closest( '.wpz-insta_feed-user-select' ),
		    $info   = $select.find( '.wpz-insta_feed-user-select-info' );

		$( '#wpz-insta_user-id' ).val( $( this ).data( 'user-id' ) ).trigger( 'change' );
		$( '#wpz-insta_user-token' ).val( $( this ).data( 'user-token' ) ).trigger( 'change' );
		
		$( '#wpz-insta_user-token, #wpz-insta_check-new-posts-interval-number, #wpz-insta_enable-request-timeout' )
			.closest( '.wpz-insta_sidebar-section' )
				.addClass( 'active' );

		$select.addClass( 'is-set' );
		$info.find( '.wpz-insta_feed-user-select-info-name' ).html( $( this ).data( 'user-name' ) );
		$info.find( '.wpz-insta_feed-user-select-info-type' ).html( $( this ).data( 'user-type' ) );
		$select.closest( '.wrap' ).find( '.wpz-insta_settings-header .wpz-insta_feed-edit-nav li' ).removeClass( 'disable' );
		$select.find( '.wpz-insta_feed-user-select-edit-link' ).attr( 'href', zoom_instagram_widget_admin.edit_user_url + $( this ).data( 'user-id' ) );
		
		$( '#wpz-insta_widget-preview-links' ).removeClass( 'disabled' );

		$( '#wpz-insta_tabs-config-cnnct' )
			.removeClass( 'active' )
			.prev( '.wpz-insta_sidebar' )
				.addClass( 'active' );

		$( '#wpz-insta_tabs-config-cnnct' ).closest( '.wpz-insta_tabs-content' ).find( '> .wpz-insta_sidebar').removeClass( 'hide' );
	} );

	let formFields = {};
	let formInitialValues = {};
	let formChangedValues = {};
	let formSubmitted = false;

	$( 'form#post .wpz-insta_tabs-content > .wpz-insta_sidebar > .wpz-insta_sidebar-left' ).find( 'input, textarea, select' ).add( 'form#post #title' ).filter( "[name][name!='']" ).not( '.preview-exclude' ).each( function( index ) {
		if ( $(this).is(':radio') ) {
			if ( $(this).is(':checked') ) {
				formFields[ $.trim( $(this).attr('name') ) ] = $(this);
			}
		} else {
			formFields[ $.trim( $(this).attr('name') ) ] = $(this);
		}
	} );

	$.each( formFields, function( i, val ) {
		formInitialValues[i] = val.is(':checkbox') ? ( val.is(':checked') ? '1' : '0' ) : $.trim( '' + val.val() );
	} );

	$( 'form#post' ).on( 'submit', () => formSubmitted = true );

	$( 'form#post #title, form#post .wpz-insta_tabs-content > .wpz-insta_sidebar > .wpz-insta_sidebar-left' ).on(
		'input change',
		debounce(
			( e ) => {
				const $target = $( e.target );

				if ( ! $target.is( '.preview-exclude' ) ) {
					const key          = $target.attr('name'),
					      currentValue = $target.is(':checkbox') ? ( $target.is(':checked') ? '1' : '0' ) : $.trim( '' + $target.val() );

					if ( key in formInitialValues && currentValue != formInitialValues[key] ) {
						if ( ! ( key in formChangedValues ) ) {
							formChangedValues[key] = true;
						}
					} else {
						if ( key in formChangedValues ) {
							delete formChangedValues[key];
						}
					}

					$( 'input#publish' ).toggleClass( 'disabled', $.isEmptyObject( formChangedValues ) );

					window.wpzInstaReloadPreview();
				}
			},
			300
		)
	);

	$( function() {
		window.wpzInstaReloadPreview();

		$( '.wpz-insta-cron-notice .notice-dismiss' ).on( 'click', function () {
			let $notice = $(this).closest( '.notice' );

			$.post( ajaxurl, {
				action:  'wpz-insta_dismiss-cron-notice',
				nonce:   $notice.attr( 'data-nonce' ),
				user_id: $notice.attr( 'data-user-id' ),
			} );
		} );
	} );

	$( '#wpz-insta_widget-preview-links .wpz-insta_widget-preview-header-link' ).on( 'click', function () {
		if ( ! $( this ).hasClass( 'active' ) ) {
			$( this ).addClass( 'active' ).siblings( '.wpz-insta_widget-preview-header-link' ).removeClass( 'active' );

			$( this ).closest( '.wpz-insta_widget-preview' ).find( '.wpz-insta_widget-preview-view' )
				.removeClass( 'wpz-insta_widget-preview-size-desktop wpz-insta_widget-preview-size-tablet wpz-insta_widget-preview-size-mobile' )
				.addClass(
					$( this ).hasClass( 'wpz-insta_widget-preview-header-links-tablet' )
						? 'wpz-insta_widget-preview-size-tablet'
						: ( $( this ).hasClass( 'wpz-insta_widget-preview-header-links-mobile' )
							? 'wpz-insta_widget-preview-size-mobile'
							: 'wpz-insta_widget-preview-size-desktop' )
				);
		}
	} );

	$( '#wpz-insta_widget-preview-view' ).on( 'transitionend', function () {
		let $iframe = $(this).find('iframe');
		$iframe.height( parseInt( $iframe.contents().find('body').prop('scrollHeight') ) + 20 );
	} );

	$( '#wpz-insta_widget-preview-view iframe' ).on( 'load', function() {
		/*$(this).height( parseInt( $(this).contents().find('body').prop('scrollHeight') ) + 20 );*/
		$(this).removeClass( 'wpz-insta_preview-hidden' );
		$(this).closest( '.wpz-insta_sidebar-right' ).addClass( 'hide-loading' );
	} );

	$( '.wpz-insta_color-picker' ).wpColorPicker( {
		change: function ( event, ui ) {
			let changeEvent = $.Event( 'change' );
			changeEvent.target = event.target;

			$( event.target ).closest( 'form#post' ).find( '.wpz-insta_tabs-content > .wpz-insta_sidebar > .wpz-insta_sidebar-left' ).triggerHandler( changeEvent );
		}
	} );

	$( '.wpzinsta-pointer' ).each( function () {
		$(this).parent().addBack().one( 'click', function ( e ) {
			e.stopPropagation();

			let $target = $(this);

			if ( $(this).is( 'li' ) ) {
				$target = $(this).find( '.wpzinsta-pointer' );
			}

			$target.remove();
		} );
	} );

	$( '#post-body-content .wpz-insta_sidebar .wpz-insta_sidebar-left .wpz-insta_sidebar-left-section' ).on( 'scroll', function () {
		$(this).find( '.wp-picker-holder' ).each( function () {
			let $parent = $(this).closest('.wp-picker-container');
			let parentOffset = $parent.offset();
			$(this).offset( { top: ( parentOffset.top + $parent.outerHeight() ), left: parentOffset.left } );
		} );
	} ).triggerHandler( 'scroll' );

	$(window).on( 'scroll', function () {
		$( '#post-body-content .wpz-insta_sidebar .wpz-insta_sidebar-left .wpz-insta_sidebar-left-section' ).each( function () {
			$(this).triggerHandler( 'scroll' );
		} );
	} );

	if ( $( '#post-body-content .wpz-insta_sidebar .wpz-insta_sidebar-left .wpz-insta_sidebar-left-section .wpz-insta_color-picker' ).length > 0 ) {
		let observer = new IntersectionObserver( ( es, os ) => es.forEach( en => en.target.blur() ), { root: null, threshold: .1 } );
		observer.observe( $( '#post-body-content .wpz-insta_sidebar .wpz-insta_sidebar-left .wpz-insta_sidebar-left-section .wpz-insta_color-picker' )[0] );
	}

	$( '#wpz-insta_shortcode' ).on( 'focus', function ( e ) {
		e.preventDefault();
		$( this ).select();
	} );

	var wpzInstaShortcodeCopyTimer;

	$( '#wpz-insta_shortcode-copy-btn' ).on(
		'click',
		debounce(
			() => {
				window.wpzInstaCopyToClipboard( $( '#wpz-insta_shortcode' ).val() )
					.then( function() {
						$( '#wpz-insta_shortcode-copy-btn' ).addClass( 'success' );

						clearTimeout( wpzInstaShortcodeCopyTimer );
						setTimeout(
							() => {
								$( '#wpz-insta_shortcode-copy-btn' ).removeClass( 'success' );
							},
							3000
						);
					} );
			},
			300
		)
	);

	$('.wpz-insta_actions-menu_copy-shortcode').on(
		'click',
		function ( e ) {
			e.preventDefault();

			let id = $(this).closest( 'tr' ).attr( 'id' ).replace( 'post-', '' );

			window.wpzInstaCopyToClipboard( '[instagram feed="' + id + '"]' )
				.then( () => {
					window.wpzInstaShowDialog(
						zoom_instagram_widget_admin.i18n_shortcode_success_title,
						zoom_instagram_widget_admin.i18n_shortcode_success_content,
						'success update'
					);
				} );
		}
	);

	$('.wpz-insta_actions-menu_delete').on(
		'click',
		function ( e ) {
			e.preventDefault();

			let isFeed = $(this).hasClass('wpz-insta_actions-menu_delete-feed'),
			    href = $(this).find('a').attr('href');

			window.wpzInstaShowConfirmDialog(
				zoom_instagram_widget_admin[ 'i18n_delete_' + ( isFeed ? 'feed' : 'user' ) + '_confirm_title' ],
				zoom_instagram_widget_admin[ 'i18n_delete_' + ( isFeed ? 'feed' : 'user' ) + '_confirm_content' ],
				zoom_instagram_widget_admin[ 'i18n_delete_confirm_button_ok' ],
				zoom_instagram_widget_admin[ 'i18n_delete_confirm_button_cancel' ]
			).then( ( result ) => {
				if ( result === true ) {
					window.location = href;
				}

				window.wpzInstaCloseDialog();
			} );
		}
	)

	function setTab( id ) {
		if ( id ) {
			const $target = $( '.wpz-insta_feed-edit-nav a[href="' + id + '"]' ),
			      $tabs   = $target.closest( 'form' ).find( '.wpz-insta_tabs-content .wpz-insta_sidebar-left-section' );

			$target.closest( '.wpz-insta_feed-edit-nav' ).find( 'li' ).removeClass( 'active' );
			$target.closest( 'li' ).addClass( 'active' );

			$tabs.removeClass( 'active' );
			$tabs.filter( '[data-id="' + id + '"]' ).addClass( 'active' );
		}
	}

	window.wpzInstaAuthenticateInstagram = function ( url, callback ) {
		let popupWidth = 700,
		    popupHeight = 500,
		    popupTop = ( window.screen.height - popupHeight ) / 2,
		    popupLeft = ( window.screen.width - popupWidth ) / 2;

		window.open( url, '', 'width=' + popupWidth + ',height=' + popupHeight + ',left=' + popupLeft + ',top=' + popupTop );
	};

	window.wpzInstaParseQuery = function ( queryString ) {
		var query = {};
		var pairs = ( queryString[0] === '?' || queryString[0] === '#' ? queryString.substr(1) : queryString ).split( '&' );

		for ( var i = 0; i < pairs.length; i++ ) {
			var pair = pairs[i].split( '=' );
			query[ decodeURIComponent( pair[0] ) ] = decodeURIComponent( pair[1] || '' );
		}

		return query;
	};

	window.wpzInstaShowConnectDoneDialog = function ( success, update = false ) {
		window.wpzInstaShowDialog(
			( success
				? ( update
					? zoom_instagram_widget_admin.i18n_reconnect_success_title
					: zoom_instagram_widget_admin.i18n_connect_success_title )
				: zoom_instagram_widget_admin.i18n_connect_fail_title
			),
			( success
				? ( update
					? zoom_instagram_widget_admin.i18n_reconnect_success_content
					: zoom_instagram_widget_admin.i18n_connect_success_content )
				: zoom_instagram_widget_admin.i18n_connect_fail_content ),
			( ( success ? 'success' : 'fail' ) + ( update ? ' update' : '' ) )
		);
	};

	window.wpzInstaShowDialog = function ( title = '[DIALOG TITLE]', content = '[DIALOG CONTENT]', wrapperClass = 'success' ) {
		let $dialog = $( '#wpz-insta_modal-dialog' ),
		    $title = $dialog.find( '.wpz-insta_modal-dialog_header-title' ),
		    $content = $dialog.find( '.wpz-insta_modal-dialog_content' ),
		    $buttonOk = $dialog.find( '.wpz-insta_modal-dialog_footer .wpz-insta_modal-dialog_ok-button' ),
		    $buttonCancel = $dialog.find( '.wpz-insta_modal-dialog_footer .wpz-insta_modal-dialog_cancel-button' );

		$title.html( '' + title );
		$content.html( '' + content );
		$buttonCancel.addClass( 'hidden' );
		$dialog.removeClass().addClass( 'open ' + wrapperClass );
	};

	window.wpzInstaShowConfirmDialog = function ( title = '[DIALOG TITLE]', content = '[DIALOG CONTENT]', okButtonLabel = '[OK]', cancelButtonLabel = '[CANCEL]' ) {
		return new Promise( function ( resolve, reject ) {
			let $dialog = $( '#wpz-insta_modal-dialog' ),
			    $title = $dialog.find( '.wpz-insta_modal-dialog_header-title' ),
			    $content = $dialog.find( '.wpz-insta_modal-dialog_content' ),
			    $buttonOk = $dialog.find( '.wpz-insta_modal-dialog_footer .wpz-insta_modal-dialog_ok-button' ),
			    $buttonCancel = $dialog.find( '.wpz-insta_modal-dialog_footer .wpz-insta_modal-dialog_cancel-button' );

			$title.html( '' + title );
			$content.html( '' + content );
			$buttonOk.removeClass( 'hidden' ).html( '' + okButtonLabel );
			$buttonOk.on( 'click', () => resolve( true ) );
			$buttonCancel.removeClass( 'hidden' ).html( '' + cancelButtonLabel );
			$buttonCancel.on( 'click', () => resolve( false ) );
			$dialog.removeClass().addClass( 'open confirm' );
		} );
	};

	window.wpzInstaCloseConnectDoneDialog = function ( success, update = false ) {
		window.wpzInstaCloseDialog();

		if ( success && ! update ) {
			window.location.replace( zoom_instagram_widget_admin.feeds_url );
		}
	};

	window.wpzInstaCloseDialog = function () {
		$( '#wpz-insta_modal-dialog' ).removeClass( 'open' );
	};

	function debounce( func, timeout = 300 ) {
		let timer;

		return ( ...args ) => {
		  clearTimeout( timer );
		  timer = setTimeout( () => { func.apply( this, args ); }, timeout );
		};
	}

	window.wpzInstaCopyToClipboard = function ( textToCopy ) {
		if ( navigator.clipboard && window.isSecureContext ) {
			return navigator.clipboard.writeText( textToCopy );
		} else {
			let textArea = document.createElement( 'textarea' );
			textArea.value = textToCopy;
			textArea.style.position = 'fixed';
			textArea.style.left = '-999999px';
			textArea.style.top = '-999999px';
			document.body.appendChild( textArea );
			textArea.focus();
			textArea.select();
			return new Promise( ( res, rej ) => {
				document.execCommand( 'copy' ) ? res() : rej();
				textArea.remove();
			} );
		}
	};

	if ( 'inlineEditPost' in window ) {
		let origInlineEditPost = window.inlineEditPost.edit;

		window.inlineEditPost.edit = function ( id ) {
			origInlineEditPost.call( this, id );

			if ( typeof( id ) === 'object' ) {
				id = window.inlineEditPost.getId( id );
			}

			let fields = [ '_wpz-insta_account-type', '_wpz-insta_token', '_wpz-insta_token_expire', '_thumbnail_id', 'wpz-insta_profile-photo', '_wpz-insta_user_name', '_wpz-insta_user-bio' ],
			    rowData = $( '#inline_' + id ),
			    editRow = $( '#edit-' + id ),
			    val, field;

			for ( let i = 0; i < fields.length; i++ ) {
				field = fields[ i ];
				val = $( '.' + field, rowData );
				val = val.text();

				if ( 'wpz-insta_profile-photo' == field ) {
					$( 'img.' + field ).attr( 'src', val );
				} else {
					$( ':input[name="' + field + '"]', editRow ).val( val );
				}
			}
		};
	}

	window.wpzInstaHandleReturnedToken = function ( url, rawToken = false ) {
		if ( url ) {
			const parsedHash = ! rawToken && 'hash' in url && '' != ('' + url.hash).trim() ? window.wpzInstaParseQuery( '' + url.hash ) : {};

			if ( ( ! rawToken && ! $.isEmptyObject( parsedHash ) ) || ( rawToken && '' != ('' + url).trim() ) ) {
				const token = rawToken ? ('' + url).trim() : ( 'access_token' in parsedHash ? ('' + parsedHash.access_token).trim() : '-1' );

				if ( '' != token && '-1' != token ) {
					const args = {
						action: 'wpz-insta_connect-user',
						nonce: zoom_instagram_widget_admin.nonce,
						token: token,
					};

					if ( ! rawToken ) {
						const parsedQuery = 'search' in url && '' != ('' + url.search).trim() ? window.wpzInstaParseQuery( '' + url.search ) : {};
						args.post_id = ! $.isEmptyObject( parsedQuery ) && 'post' in parsedQuery ? parseInt( parsedQuery.post ) : 0;
					}

					$.post( ajaxurl, args )
						.done( function ( response ) {
							$('#the-list #wpz-insta_token').val(token);

							let date = new Date();
							date.setDate( date.getDate() + 60 );
							$('#the-list #wpz-insta_token-expire-time').html(
								date.toLocaleDateString(
									'en-US',
									{
										weekday: 'long',
										day:     'numeric',
										month:   'long',
										year:    'numeric',
									}
								)
							);

							window.wpzInstaShowConnectDoneDialog(
								response.success,
								( 'data' in response && 'update' in response.data && response.data.update )
							);
						} )
						.fail( function () {
							window.wpzInstaShowConnectDoneDialog( false );
						} );
				}
			}
		}
	};

	window.wpzInstaReloadPreview = function () {
		let url = zoom_instagram_widget_admin.preview_url,
		    params = $.param( $( 'form#post #title, form#post .wpz-insta_tabs-content > .wpz-insta_sidebar > .wpz-insta_sidebar-left' ).find( 'input, textarea, select' ).not( '.preview-exclude' ).serializeArray() );

		if ( params ) {
			url += '&' + params;
		}

		$( '#wpz-insta_widget-preview-view' ).closest( '.wpz-insta_sidebar-right' ).removeClass( 'hide-loading' );
		$( '#wpz-insta_widget-preview-view iframe' ).addClass( 'wpz-insta_preview-hidden' ).attr( 'src', url );
	};

	window.wpzInstaUpdatePreviewHeight = function () {
		const $frame = $( '#wpz-insta_widget-preview-view iframe' );

		$frame.height( parseInt( $frame.contents().find( 'body' ).prop( 'scrollHeight' ) ) );
	};
} );
