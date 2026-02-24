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
                    var $attachmentInput = $this.find('.attachment-input').add( $imageMediaControlTarget.find('input#wpz-insta_account-photo') );
                    var selection = this.get('selection');
                    var attachmentId = selection.pluck('id');

                    $attachmentInput.val( '' + attachmentId).trigger('change');
					
					var selectionData = selection.first().toJSON();
					var thumbnail_url = selectionData.sizes.thumbnail?.url ?? selectionData.sizes.full.url;


                    $imageMediaControlTarget.find('img.wpz-insta_profile-photo').attr( 'src', '' + thumbnail_url );
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

	$('#wpzoom_instagram_clear_data').on( 'click', function( e ){
		e.preventDefault();
		var data = {
			action: 'wpzoom_instagram_clear_data',
			nonce: $(this).data( 'nonce' )
		};
		var $this = $(this);

		if ( window.confirm( "Are you sure?" ) ) {

			$this.text('Removing data...');

			$.post( zoom_instagram_widget_admin.ajax_url, data, function(response){
				if ( response.success ) {
					$this.text('Done!');
					$this.prop('disabled', true);
					$this.next().html(response.data.message);
				}
			});
		}
	});

	$(window).on('beforeunload', function (e) {
		if ( ! $.isEmptyObject( formChangedValues ) && ! formSubmitted ) {
			e.preventDefault();
		}
	});

	$('#the-list').on('click', '#wpz-insta_reconnect', function (e) {
		e.preventDefault();
        if( $(this).attr('href').length > 0 ) {
            window.wpzInstaAuthenticateInstagram( $(this).attr('href') );
        }
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

	
    if ( window.opener && window.location.hash.length > 1 || isLikelyPopup( 700, 960, 750, 1200 ) && window.location.hash.length > 1 ) {
            if ( window.opener &&  typeof window.opener.wpzInstaHandleReturnedToken === 'function' ) {
                window.opener.wpzInstaHandleReturnedToken( window.location );
                window.close();
            } else {
                var getToken = getAccessTokenFromURL( window.location );
                var notice = $('#wpz-insta_modal-dialog-connection-failed');
                $('#wpz_generated_token').text( getToken );
                notice.addClass('open');
                $('#wpfooter').show();
            }
		if( window.location.hash.includes( 'access_graph_token' ) ) {
			window.opener.wpzInstaHandleReturnedGraphToken( window.location );
            window.close();
		}
	}

    function getAccessTokenFromURL() {
        // Get the full URL from window.location.href
        const url = window.location.href;
    
        // Check if the URL contains a hash and if it includes 'access_token'
        const hash = url.split('#')[1];  // Get everything after the '#'
        if (hash && hash.includes('access_token=')) {
            // Extract the query parameters in the hash
            const params = new URLSearchParams(hash);
            // Get the value of 'access_token'
            const accessToken = params.get('access_token');
            return accessToken;
        }
        return null; // Return null if no access_token is found
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

	$('#wpz-insta_connect-personal, #wpz-insta_connect-business, .wpz-insta_tabs-config-connect-add').each(function() {
        // Get the current href attribute
        var currentHref = $(this).attr('href');

		// Check if the href attribute exists
        if ( currentHref ) {

			// Construct the new URL part
			var newUrlPart = btoa(encodeURIComponent( zoom_instagram_widget_admin.feeds_url ) );

			// Replace RETURN_URL in the href with the new encoded URL part
			var newHref = currentHref.replace('RETURN_URL', newUrlPart);

			// Set the new href attribute on the current element
			$(this).attr('href', newHref);
		}

    });

	$( '.wpz-insta-wrap .account-options .account-option-button' ).on( 'click', function( e ) {
		e.preventDefault();

		if ( ! $( this ).is( '.disabled' ) ) {
			if ( $( this ).is( '#wpz-insta_connect-personal' ) || $( this ).is( '#wpz-insta_connect-business' ) ) {
				window.wpzInstaAuthenticateInstagram( $( this ).attr( 'href' ) );
			} else if ( $( this ).is( '#wpz-insta_account-token-button' ) ) {
				window.wpzInstaHandleReturnedGraphToken( $( '#wpz-insta_account-token-input' ).val().trim().replace( /[^a-z0-9-_.]+/gi, '' ), true );
			} else if ( $( this ).is( '#wpz-insta-biz_account-token-button' ) ) {
				window.wpzInstaHandleReturnedToken( $( '#wpz-insta_biz_account-token-input' ).val().trim().replace( /[^a-z0-9-_.]+/gi, '' ), true );
			}
		}
	} );

	$( '#wpz-insta_account-token-input' ).on( 'input', function() {
		$( '#wpz-insta_account-token-button' ).toggleClass( 'disabled', ( $( '#wpz-insta_account-token-input' ).val().trim().length <= 0 ) );
	} );

	$( '#wpz-insta_biz_account-token-input' ).on( 'input', function() {
		$( '#wpz-insta-biz_account-token-button' ).toggleClass( 'disabled', ( $( '#wpz-insta_biz_account-token-input' ).val().trim().length <= 0 ) );
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

	$( '.wpz-insta_sidebar-section-layout input[name="_wpz-insta_perpage-num_responsive-enabled"]' ).on( 'change', function() {
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

	$( '#wpz-insta_modal_graph-dialog' ).find( '.wpz-insta_modal-dialog_ok-button, .wpz-insta_modal-dialog_close-button' ).on( 'click', function( e ) {
		e.preventDefault();
		let $dialog = $('#wpz-insta_modal_graph-dialog');
		$dialog.removeClass('open');
	} );

	$( '#wpz-insta_modal-dialog-connection-failed' ).find( '.wpz-insta_modal-dialog_ok-button, .wpz-insta_modal-dialog_close-button' ).on( 'click', function( e ) {
		e.preventDefault();
		let $dialog = $('#wpz-insta_modal-dialog-connection-failed');
        window.close();
		$dialog.removeClass('open');
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
		$( '.wpz-insta_widget-preview-refresh' ).addClass( 'disabled' ).prop( 'disabled', true );

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
		$( '.wpz-insta_widget-preview-refresh' ).removeClass( 'disabled' ).prop( 'disabled', false );

		// Toggle Stories checkbox based on whether the account has a Facebook Page connection
		let hasPageId = $( this ).data( 'has-page-id' ) === 1 || $( this ).data( 'has-page-id' ) === '1';
		let $storiesRow = $( 'input[name="_wpz-insta_show-stories"]' ).not( '[type="hidden"]' ).closest( '.wpz-insta_table-row' );
		let $storiesCheckbox = $storiesRow.find( 'input[type="checkbox"]' );

		if ( hasPageId ) {
			$storiesRow.removeClass( 'wpz-insta_disabled' );
			$storiesCheckbox.prop( 'disabled', false );
		} else {
			$storiesRow.addClass( 'wpz-insta_disabled' );
			$storiesCheckbox.prop( 'disabled', true ).prop( 'checked', false );
		}

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

	// Helper function to get current checkbox array values
	function getCheckboxArrayValue( name ) {
		const checkedValues = [];
		$( 'input[name="' + name + '"]' ).each( function() {
			if ( $(this).is(':checked') ) {
				checkedValues.push( $(this).val() );
			}
		} );
		return checkedValues.sort().join(','); // Sort for consistent comparison
	}

	// Include all named fields (including .preview-exclude) so Save button enables on change; preview-exclude only affects iframe URL
	$( 'form#post .wpz-insta_tabs-content > .wpz-insta_sidebar > .wpz-insta_sidebar-left' ).find( 'input, textarea, select' ).add( 'form#post #title' ).filter( "[name][name!='']" ).each( function( index ) {
		const fieldName = $.trim( $(this).attr('name') );
		
		if ( $(this).is(':radio') ) {
			if ( $(this).is(':checked') ) {
				formFields[ fieldName ] = $(this);
			}
		} else if ( fieldName.endsWith('[]') ) {
			// Handle checkbox arrays - store all checkboxes with this name
			const baseName = fieldName.replace('[]', '');
			if ( ! formFields[ baseName ] ) {
				formFields[ baseName ] = [];
			}
			formFields[ baseName ].push( $(this) );
		} else {
			formFields[ fieldName ] = $(this);
		}
	} );

	$.each( formFields, function( i, val ) {
		if ( Array.isArray( val ) ) {
			// Handle checkbox arrays - get comma-separated list of checked values
			formInitialValues[i] = getCheckboxArrayValue( i + '[]' );
		} else {
			formInitialValues[i] = val.is(':checkbox') ? ( val.is(':checked') ? '1' : '0' ) : $.trim( '' + val.val() );
		}
	} );

	$( 'form#post' ).on( 'submit', () => formSubmitted = true );

	$( 'form#post #title, form#post .wpz-insta_tabs-content > .wpz-insta_sidebar > .wpz-insta_sidebar-left' ).on(
		'input change',
		debounce(
			( e ) => {
				const $target = $( e.target );
				const key = $target.attr('name');

				// Skip elements without a name attribute
				if ( ! key ) {
					return;
				}

				let trackingKey = key;
				let currentValue;

				if ( key.endsWith('[]') ) {
					// Handle checkbox arrays
					trackingKey = key.replace('[]', '');
					currentValue = getCheckboxArrayValue( key );
				} else {
					// Handle regular fields
					currentValue = $target.is(':checkbox') ? ( $target.is(':checked') ? '1' : '0' ) : $.trim( '' + $target.val() );
				}

				if ( trackingKey in formInitialValues && currentValue != formInitialValues[trackingKey] ) {
					if ( ! ( trackingKey in formChangedValues ) ) {
						formChangedValues[trackingKey] = true;
					}
				} else {
					if ( trackingKey in formChangedValues ) {
						delete formChangedValues[trackingKey];
					}
				}

				$( 'input#publish' ).toggleClass( 'disabled', $.isEmptyObject( formChangedValues ) );

				// Update preview: postMessage for design settings (no reload), reload only for server-dependent fields
				if ( key === 'post_title' || $target.is( '.preview-exclude' ) ) return;
				var needsReload = wpzInstaPreviewReloadKeys.indexOf( key ) !== -1 || ( key && key.indexOf( '_wpz-insta_allowed-post-types' ) === 0 );
				if ( needsReload ) {
					window.wpzInstaReloadPreview();
				} else {
					wpzInstaSendPreviewUpdate();
				}
			},
			300
		)
	);

	$( function() {
		// Only set iframe src from JS if PHP did not already set it (initial load uses PHP-built URL).
		var $previewIframe = $( '#wpz-insta_widget-preview-view iframe' );
		if ( ! $previewIframe.attr( 'src' ) || $previewIframe.attr( 'src' ) === '' ) {
			window.wpzInstaReloadPreview();
		}
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
		$(this).removeClass( 'wpz-insta_preview-hidden' );
		$(this).closest( '.wpz-insta_sidebar-right' ).addClass( 'hide-loading' );
		// Sync current form state to preview via postMessage (so unsaved design changes apply without another reload)
		wpzInstaSendPreviewUpdate();
		// Re-send current tab so "Link to a product" / Moderate buttons show if tab was clicked before iframe loaded
		const iframe = this;
		if ( iframe.contentWindow ) {
			const activeTab = $( '.wpz-insta_feed-edit-nav li.active a' ).attr( 'href' );
			if ( activeTab ) {
				const tabId = ( activeTab + '' ).replace( /^#/, '' );
				iframe.contentWindow.postMessage( { action: 'wpz-insta-tab-change', tab: tabId }, '*' );
			}
		}
	} );

	$( '.wpz-insta_color-picker' ).wpColorPicker( {
		change: function ( event, ui ) {
			let changeEvent = $.Event( 'change' );
			changeEvent.target = event.target;

			$( event.target ).closest( 'form#post' ).find( '.wpz-insta_tabs-content > .wpz-insta_sidebar > .wpz-insta_sidebar-left' ).triggerHandler( changeEvent );
		}
	} );

	// Product Links: show/hide settings by display type (icon vs button)
	function wpzInstaToggleProductLinksDisplayType() {
		const $container = $( '#_wpz-insta_product-links-display-type' );
		if ( ! $container.length ) {
			return;
		}
		const $section = $container.closest( '.wpz-insta_sidebar-left-section' );
		const displayType = $container.find( 'input[name="_wpz-insta_product-links-display-type"]:checked' ).val();
		const isIcon = displayType === 'icon';

		$section.find( '.wpz-insta-product-links-popover-title-row' ).toggleClass( 'hidden', ! isIcon );
		$section.find( '.wpz-insta-product-links-icon-position-row' ).toggleClass( 'hidden', ! isIcon );
		$section.find( '.wpz-insta_sidebar-section-product-links-icon-design' ).toggleClass( 'hidden', ! isIcon );
		$section.find( '.wpz-insta-buy-now-button-row' ).toggleClass( 'hidden', isIcon );
		$section.find( '.wpz-insta_sidebar-section-product-links-design' ).toggleClass( 'hidden', isIcon );
	}
	wpzInstaToggleProductLinksDisplayType();
	$( document ).on( 'change', 'input[name="_wpz-insta_product-links-display-type"]', wpzInstaToggleProductLinksDisplayType );

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

			// Notify iframe of tab change so it can show/hide "Link to a product" / Moderate buttons without reload
			const iframe = document.querySelector( '#wpz-insta_widget-preview-view iframe' );
			if ( iframe && iframe.contentWindow ) {
				const tabId = ( id + '' ).replace( /^#/, '' );
				iframe.contentWindow.postMessage( { action: 'wpz-insta-tab-change', tab: tabId }, '*' );
			}
		}
	}

	window.wpzInstaAuthenticateInstagram = function ( url, callback ) {
		let popupWidth = 700,
		    popupHeight = 750,
		    popupTop = ( window.screen.height - popupHeight ) / 2,
		    popupLeft = ( window.screen.width - popupWidth ) / 2;

		window.open( url, '', 'width=' + popupWidth + ',height=' + popupHeight + ',left=' + popupLeft + ',top=' + popupTop );
	};
    
    // Helper function to check if the current window is a popup
    function isLikelyPopup(minWidth, maxWidth, minHeight, maxHeight) {
        const windowWidth = window.outerWidth;
        const windowHeight = window.outerHeight;
    
        // Check if the window is smaller than the full screen
        const isSmallerThanScreen = windowWidth < window.screen.width && windowHeight < window.screen.height;
    
        // Check if the window size falls within the expected range
        const withinWidthRange = windowWidth >= minWidth && windowWidth <= maxWidth;
        const withinHeightRange = windowHeight >= minHeight && windowHeight <= maxHeight;
    
        return isSmallerThanScreen && withinWidthRange && withinHeightRange;
    }

	window.wpzInstaParseQuery = function ( queryString ) {
		var query = {};
		var pairs = ( queryString[0] === '?' || queryString[0] === '#' ? queryString.substr(1) : queryString ).split( '&' );

		for ( var i = 0; i < pairs.length; i++ ) {
			var pair = pairs[i].split( '=' );
			query[ decodeURIComponent( pair[0] ) ] = decodeURIComponent( pair[1] || '' );
		}

		return query;
	};

	// Function to handle the returned token from graph API
	window.wpzInstaHandleReturnedGraphToken = function ( url, rawToken = false ) {
		if ( url ) {
			const parsedHash = ! rawToken && 'hash' in url && '' != ('' + url.hash).trim() ? window.wpzInstaParseQuery( '' + url.hash ) : {};
		
			if ( ( ! rawToken && ! $.isEmptyObject( parsedHash ) ) || ( rawToken && '' != ('' + url).trim() ) ) {
				const token = rawToken ? ('' + url).trim() : ( 'access_graph_token' in parsedHash ? ('' + parsedHash.access_graph_token).trim() : '-1' );
		
				if ( '' != token && '-1' != token ) {
					const args = {
						action: 'wpz-insta_connect_business-user',
						nonce: zoom_instagram_widget_admin.nonce,
						token: token,
					};
		
					if ( ! rawToken ) {
						const parsedQuery = 'search' in url && '' != ('' + url.search).trim() ? window.wpzInstaParseQuery( '' + url.search ) : {};
						args.post_id = ! $.isEmptyObject( parsedQuery ) && 'post' in parsedQuery ? parseInt( parsedQuery.post ) : 0;
					}
		
					$.post( ajaxurl, args )
						.done( function ( data, status, code ) {
							if ( 'success' == status ) {
								
								var getBusinessUsers = $(data);

								if ( getBusinessUsers ) {
									$( '#wpz-insta_modal_graph-dialog' ).find( '.wpz-insta_modal-dialog_content' ).html( getBusinessUsers );
									$( '#wpz-insta_modal_graph-dialog' ).removeClass().addClass( 'open success' );
                                    $( '.wpz-insta_business-accounts-link').on('click', function (e) {
                                        e.preventDefault();
                                        // If the user is not a pro, remove the selected class from all links
                                        if( ! zoom_instagram_widget_admin.is_pro ) {
                                            $('.wpz-insta_business-accounts-link').removeClass('selected'); // Remove from all links
                                            $(this).addClass('selected'); // Add to the clicked link
                                        }
                                        else {
                                            $(this).toggleClass('selected'); // Add to the clicked link
                                        }
                                        $( '#wpz-insta-graph-connect-account').removeClass('disabled');
                                    } );
								}

							}

						} )
						.fail( function () {
							console.log( 'Failed to connect business user' );
						}
					);
				}
			}
		}
	}

	// Set the selected API
	$('#wpz-insta-select-api').on('change', function (e) {
		var selected = $(this).val();
        $(this).parent().find( '#wpz-insta_reconnect' ).attr('href', selected );
	} );

    $('#wpz-add_manual_token').on( 'click', function (e) { 
        e.preventDefault();
        $('#wpz-insta-token_label').toggle();
    } );

	// Function to connect the selected business account
	$('#wpz-insta-graph-connect-account').on('click', function (e) {
		e.preventDefault();

        let selected_accounts = [],
		    post_id = $('.wpz-insta_business-accounts-link').parent().data('post-id');

        $('.wpz-insta_business-accounts-link').each(function() {
            if( $(this).hasClass('selected') ) {
                selected_accounts.push( $(this).data('account-info') );
            }
        });

        if( selected_accounts.length > 0 ) {
			const args = {	
				action: 'wpz-insta_connect_business-account',
				nonce: zoom_instagram_widget_admin.nonce,
				account_info: JSON.stringify( selected_accounts ),
				post_id: post_id,
			};

			$.post( ajaxurl, args )
			.done( function ( data, status, code ) {				
				if ( 'success' == status ) {
					$( '#wpz-insta_modal_graph-dialog' ).removeClass('open');
				}
				window.location.replace( zoom_instagram_widget_admin.feeds_url );
			} )
			.fail( function ( data, status, code ) {
				console.log( data );
			} );
		}
	} );

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
		$( '.inline-edit-save' ).find( '.button-primary' ).addClass( 'disabled' );

		let origInlineEditPost = window.inlineEditPost.edit;

		window.inlineEditPost.edit = function ( id ) {
			origInlineEditPost.apply( this, arguments );

			if ( typeof( id ) === 'object' ) {
				id = window.inlineEditPost.getId( id );
			}

			let fields = [ '_wpz-insta_account-type', '_wpz-insta_token', '_wpz-insta_token_expire', '_thumbnail_id', 'wpz-insta_profile-photo', '_wpz-insta_user_name', '_wpz-insta_user-bio', '_wpz-insta_api-url' ],
			    rowData = $( '#inline_' + id ),
			    editRow = $( '#edit-' + id ),
			    reconnectBtn = $( '#wpz-insta_reconnect', editRow ),
			    val, field;

			for ( let i = 0; i < fields.length; i++ ) {
				field = fields[ i ];
				val = $( '.' + field, rowData );
				val = val.text();

				if ( 'wpz-insta_profile-photo' == field ) {
					$( 'img.' + field ).attr( 'src', val );
				} else if ( '_wpz-insta_token' == field ) {
					$( '#wpz-insta_token', editRow ).val( val );
				} else if( '_wpz-insta_token_expire' == field ) {
					$( '#wpz-insta_token-expire-time', editRow ).html( val );
				} else if( '_wpz-insta_api-url' == field ) {
					$( '#wpz-insta_reconnect', editRow ).attr( 'href', val );
				} else {
					$( ':input[name="' + field + '"]', editRow ).val( val );
				}

				$( ':input[name="' + field + '"]', editRow ).on( 'change paste keyup', function() {
					$( '.inline-edit-save', editRow ).find( '.button-primary' ).removeClass( 'disabled' );
				});
			}

			// Update the RETURN_URL in the values of the options
			$('#wpz-insta-select-api option', editRow ).each(function() {
				var newUrlPart = btoa(encodeURIComponent( zoom_instagram_widget_admin.post_edit_url + id ) );
				let currentValue = $(this).val();
				if (currentValue.includes('RETURN_URL')) {
					// Replace 'RETURN_URL' with the new return URL
					let updatedValue = currentValue.replace('RETURN_URL', encodeURIComponent(newUrlPart));
					$(this).val(updatedValue);
				}
			});

			reconnectBtn.attr( {
				'href': reconnectBtn.attr( 'href' ).replace( 'RETURN_URL', btoa( encodeURIComponent( zoom_instagram_widget_admin.post_edit_url + id ) ) ),
				'data-user-id': id
			} );

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
							$( '.inline-edit-wpz-insta_user #wpz-insta_token' ).val(token);

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
						} 
					);
				}
			}
		}
	};

	// Fields that require a full iframe reload (server-side: different data). All others are applied via postMessage.
	var wpzInstaPreviewReloadKeys = [
		'_wpz-insta_user-id', '_wpz-insta_user-ids', '_wpz-insta_item-num', '_wpz-insta_allowed-post-types-submitted',
		'_wpz-insta_layout', '_wpz-insta_image-size',
		// These change the HTML structure (PHP conditionally renders elements), so a full reload is needed
		'_wpz-insta_show-overlay', '_wpz-insta_hover-link', '_wpz-insta_show-likes', '_wpz-insta_show-comments',
		'_wpz-insta_show-media-type-icons', '_wpz-insta_hover-media-type-icons', '_wpz-insta_hover-date',
		'_wpz-insta_show-view-button', '_wpz-insta_show-load-more', '_wpz-insta_show-stories',
		// PRO multi-account settings that change HTML structure
		'_wpz-insta_multi-account-header-mode', '_wpz-insta_multi-account-show-attribution'
	];

	function wpzInstaCollectPreviewState() {
		var $form = $( 'form#post .wpz-insta_tabs-content > .wpz-insta_sidebar > .wpz-insta_sidebar-left' );
		var state = {};
		$( 'form#post #title' ).add( $form.find( 'input, textarea, select' ).not( '.preview-exclude' ) ).each( function() {
			var $el = $( this );
			var name = $el.attr( 'name' );
			if ( ! name || name === 'post_ID' ) return;
			var key = name.replace( /^_wpz-insta_/, '' ).replace( /\[\]$/, '' );
			var val;
			if ( $el.is( ':radio, :checkbox' ) ) {
				if ( name.indexOf( '[]' ) !== -1 ) {
					val = $( 'form#post' ).find( 'input[name="' + name + '"]:checked' ).map( function() { return $( this ).val(); } ).get().join( ',' );
				} else if ( $el.is( ':radio' ) ) {
					// Radio group: use the checked value so layout/featured-layout etc. are correct
					var $checked = $( 'form#post' ).find( 'input[name="' + name + '"]:checked' );
					val = $checked.length ? ( $checked.val() || '0' ) : '0';
				} else {
					val = $el.is( ':checked' ) ? ( $el.val() || '1' ) : '0';
				}
			} else {
				val = $el.val();
			}
			state[ key ] = val;
		} );
		return state;
	}

	function wpzInstaSendPreviewUpdate() {
		var iframe = document.querySelector( '#wpz-insta_widget-preview-view iframe' );
		if ( ! iframe || ! iframe.contentWindow ) return;
		var state = wpzInstaCollectPreviewState();
		iframe.contentWindow.postMessage( { action: 'wpz-insta-preview-update', data: state }, '*' );
	}

	/**
	 * Build the preview iframe URL (optionally with refresh param to bypass cache).
	 *
	 * @param {boolean} [withRefresh=false] If true, add wpz-insta-preview-refresh=1 to fetch fresh data from Instagram.
	 * @returns {string} Preview URL.
	 */
	function wpzInstaBuildPreviewUrl( withRefresh ) {
		var url = zoom_instagram_widget_admin.preview_url;
		var postId = $( 'form#post input[name="post_ID"]' ).val();
		if ( postId ) {
			url += '&wpz-insta-feed-id=' + encodeURIComponent( postId );
		}
		var activeTab = $( '.wpz-insta_feed-edit-nav li.active a' ).attr( 'href' );
		if ( activeTab ) {
			url += '&wpz-insta-tab=' + encodeURIComponent( ( activeTab + '' ).replace( /^#/, '' ) );
		}
		// Pass all reload-key values as URL params so PHP preview_frame() can overlay them on saved settings.
		wpzInstaPreviewReloadKeys.forEach( function( key ) {
			var $el = $( 'form#post [name="' + key + '"]' );
			if ( ! $el.length ) return;
			// When a hidden+checkbox pair shares the same name, prefer the checkbox element
			var $cb = $el.filter( ':checkbox' );
			if ( $cb.length ) $el = $cb;
			var val;
			if ( $el.is( ':checkbox' ) ) {
				val = $el.is( ':checked' ) ? '1' : '0';
			} else {
				val = $.trim( '' + $el.val() );
			}
			if ( val != null && val !== '' ) {
				url += '&' + encodeURIComponent( key ) + '=' + encodeURIComponent( val );
			}
		} );
		// Also pass allowed post types (checkbox array, not in reload keys)
		var allowedTypes = $( 'form#post input[name="_wpz-insta_allowed-post-types[]"]:checked' ).map( function() { return $( this ).val(); } ).get().join( ',' );
		if ( allowedTypes ) {
			url += '&_wpz-insta_allowed-post-types=' + encodeURIComponent( allowedTypes );
		}
		if ( withRefresh ) {
			url += '&wpz-insta-preview-refresh=1';
		}
		return url;
	}

	window.wpzInstaReloadPreview = function ( withRefresh ) {
		var url = wpzInstaBuildPreviewUrl( !! withRefresh );
		$( '#wpz-insta_widget-preview-view' ).closest( '.wpz-insta_sidebar-right' ).removeClass( 'hide-loading' );
		$( '#wpz-insta_widget-preview-view iframe' ).addClass( 'wpz-insta_preview-hidden' ).attr( 'src', url );
	};

	$( '.wpz-insta_widget-preview-refresh' ).on( 'click', function () {
		window.wpzInstaReloadPreview( true );
	} );

	window.wpzInstaUpdatePreviewHeight = function () {
		const $frame = $( '#wpz-insta_widget-preview-view iframe' );

		$frame.height( parseInt( $frame.contents().find( 'body' ).prop( 'scrollHeight' ) ) );
	};

	// WooCommerce Product Link functionality
	if ( typeof zoom_instagram_widget_admin !== 'undefined' ) {
		// Product link popup (multi-select)
		var $productLinkModal = null;
		var currentMediaId = null;
		var currentFeedId = null;
		var currentSelectedProducts = []; // Selected products with tags: [{ id, tag: { x, y, album_index } | null }]
		var originalSelectedProducts = []; // Original products from DB when modal opened (to detect changes)
		var currentMediaType = 'image'; // 'image', 'video', 'carousel_album'
		var currentImageUrl = ''; // Main image URL for tagging
		var currentAlbumImages = []; // Array of album images for carousel_album
		var currentTaggingProductId = null; // Product being tagged
		var isTaggingViewActive = false; // Whether tagging view is active
		var currentSelectedAlbumIndex = null; // Selected album image index for carousel_album

		// Pending product links stored in memory (not saved to DB until post is saved)
		// New structure: { mediaId: [{ id: productId, tag: { x, y, album_index } | null }], ... }
		var pendingProductLinks = {};

		// Track which media IDs had links when page loaded (from DB)
		var initialProductLinks = {};

		// Initialize pending product links from existing data if available
		function initPendingProductLinks() {
			var $hiddenInput = $( '#wpz-insta-pending-product-links' );
			if ( $hiddenInput.length === 0 ) {
				// Create hidden input to store pending product links for form submission
				$( 'form#post' ).append( '<input type="hidden" id="wpz-insta-pending-product-links" name="_wpz-insta_pending-product-links" value="" />' );
			}
		}

		// Update the hidden input with current pending product links
		function updatePendingProductLinksInput() {
			var $hiddenInput = $( '#wpz-insta-pending-product-links' );
			if ( $hiddenInput.length ) {
				$hiddenInput.val( JSON.stringify( pendingProductLinks ) );
			}

			// Trigger form change detection so Save button becomes active
			if ( ! $.isEmptyObject( pendingProductLinks ) || hasAnyPendingChanges() ) {
				// Mark form as changed
				if ( ! ( '_wpz-insta_pending-product-links' in formChangedValues ) ) {
					formChangedValues[ '_wpz-insta_pending-product-links' ] = true;
				}
			} else {
				if ( '_wpz-insta_pending-product-links' in formChangedValues ) {
					delete formChangedValues[ '_wpz-insta_pending-product-links' ];
				}
			}
			$( 'input#publish' ).toggleClass( 'disabled', $.isEmptyObject( formChangedValues ) );
		}

		// Check if there are any pending changes (different from initial state)
		function hasAnyPendingChanges() {
			var mediaIds = Object.keys( pendingProductLinks );
			for ( var i = 0; i < mediaIds.length; i++ ) {
				var mediaId = mediaIds[ i ];
				var pending = pendingProductLinks[ mediaId ] || [];
				var initial = initialProductLinks[ mediaId ] || [];
				if ( JSON.stringify( pending ) !== JSON.stringify( initial ) ) {
					return true;
				}
			}
			return false;
		}

		// Get linked products for a media item (check pending first, then initial)
		// Returns array of { id, tag } objects
		function getLinkedProducts( mediaId ) {
			if ( mediaId in pendingProductLinks ) {
				return JSON.parse( JSON.stringify( pendingProductLinks[ mediaId ] ) );
			}
			if ( mediaId in initialProductLinks ) {
				return JSON.parse( JSON.stringify( initialProductLinks[ mediaId ] ) );
			}
			return [];
		}

		// Get just the product IDs for a media item (for backward compatibility)
		function getLinkedProductIds( mediaId ) {
			var products = getLinkedProducts( mediaId );
			return products.map( function( p ) { return p.id; } );
		}

		// Get tag data for a specific product in a media item
		function getProductTag( mediaId, productId ) {
			var products = getLinkedProducts( mediaId );
			for ( var i = 0; i < products.length; i++ ) {
				if ( products[ i ].id === productId ) {
					return products[ i ].tag || null;
				}
			}
			return null;
		}

		// Check if a product is tagged in the current media
		function isProductTagged( productId ) {
			for ( var i = 0; i < currentSelectedProducts.length; i++ ) {
				if ( currentSelectedProducts[ i ].id === productId && currentSelectedProducts[ i ].tag ) {
					return true;
				}
			}
			return false;
		}

		// Update preview iframe to reflect has-linked-products class changes
		function updatePreviewLinkedProducts( mediaId, hasLinks ) {
			var iframe = document.querySelector( '#wpz-insta_widget-preview-view iframe' );
			if ( iframe && iframe.contentWindow ) {
				iframe.contentWindow.postMessage( {
					action: 'wpz-insta-product-link-update',
					mediaId: mediaId,
					hasLinks: hasLinks,
					productIds: hasLinks ? getLinkedProductIds( mediaId ) : []
				}, '*' );
			}
		}

		// Find index of a product in the products array by ID
		function findProductIndex( products, productId ) {
			for ( var i = 0; i < products.length; i++ ) {
				if ( products[ i ].id === productId ) {
					return i;
				}
			}
			return -1;
		}

		// Check if two product arrays are equal (for change detection)
		function areProductArraysEqual( arr1, arr2 ) {
			if ( arr1.length !== arr2.length ) return false;
			var sorted1 = arr1.slice().sort( function( a, b ) { return a.id - b.id; } );
			var sorted2 = arr2.slice().sort( function( a, b ) { return a.id - b.id; } );
			return JSON.stringify( sorted1 ) === JSON.stringify( sorted2 );
		}

		// Get current selected product IDs only (for backward compatibility)
		function getCurrentSelectedIds() {
			return currentSelectedProducts.map( function( p ) { return p.id; } );
		}

		// Update the tag button on a product item based on tag state
		function updateProductItemButton( $item, productId ) {
			var $btn = $item.find( '.wpz-insta-tag-in-image-btn' );
			if ( $btn.length === 0 ) return;

			var strings = zoom_instagram_widget_admin.strings || {};
			var hasTag = isProductTagged( productId );
			var btnIcon = '<svg xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 -960 960 960" width="18" fill="currentColor"><path d="M640-760v280l68 68q6 6 9 13.5t3 15.5v23q0 17-11.5 28.5T680-320H520v234q0 17-11.5 28.5T480-46q-17 0-28.5-11.5T440-86v-234H280q-17 0-28.5-11.5T240-360v-23q0-8 3-15.5t9-13.5l68-68v-280q-17 0-28.5-11.5T280-800q0-17 11.5-28.5T320-840h320q17 0 28.5 11.5T680-800q0 17-11.5 28.5T640-760Z"></path></svg>';
			var btnText = hasTag ? ( strings.editTag || 'Edit Tag' ) : ( strings.tagInImage || 'Tag in Image' );

			if ( hasTag ) {
				$btn.addClass( 'wpz-insta-tag-btn--tagged' ).html( btnIcon + ' ' + btnText ).attr( 'title', btnText );
			} else {
				$btn.removeClass( 'wpz-insta-tag-btn--tagged' ).html( btnIcon + ' ' + btnText ).attr( 'title', btnText );
			}
		}

		function initProductLinkModal() {
			if ( $productLinkModal ) {
				return;
			}

			// Initialize the hidden input for pending product links
			initPendingProductLinks();

			var strings = zoom_instagram_widget_admin.strings || {};
			var modalHTML = '<div id="wpz-insta-product-link-modal" class="wpz-insta-modal" style="display: none;">' +
				'<div class="wpz-insta-modal-content">' +
				'<div class="wpz-insta-modal-header">' +
				'<button type="button" class="wpz-insta-back-to-products" style="display: none;" title="' + ( strings.backToProducts || 'Back' ) + '"><span class="dashicons dashicons-arrow-left-alt2"></span></button>' +
				'<h2 class="wpz-insta-modal-title">' + ( strings.linkProduct || 'Link to Product' ) + '</h2>' +
				'<div class="wpz-insta-tagging-header-preview" id="wpz-insta-tagging-header-preview" style="display: none;"></div>' +
				'<button type="button" class="wpz-insta-modal-close">&times;</button>' +
				'</div>' +
				'<div class="wpz-insta-modal-body">' +
				// Product selection view
				'<div class="wpz-insta-product-selection-view">' +
				'<p class="wpz-insta-product-link-hint">' + ( strings.selectProductsHint || 'Select one or more products. Click an item to toggle selection.' ) + '</p>' +
				'<div class="wpz-insta-product-search">' +
				'<input type="text" id="wpz-insta-product-search-input" placeholder="' + ( strings.searchProducts || 'Search products...' ) + '" />' +
				'</div>' +
				'<div class="wpz-insta-product-list" id="wpz-insta-product-list"></div>' +
				'<div class="wpz-insta-product-pagination"></div>' +
				'</div>' +
				// Album selector view (for carousel_album)
				'<div class="wpz-insta-album-selector-view" style="display: none;">' +
				'<p class="wpz-insta-album-hint">' + ( strings.selectAlbumImage || 'Select an image from the album to tag the product on:' ) + '</p>' +
				'<div class="wpz-insta-album-images" id="wpz-insta-album-images"></div>' +
				'</div>' +
				// Tagging view (product info is in header; hidden element here for data only)
				'<div class="wpz-insta-tagging-view" style="display: none;">' +
				'<div id="wpz-insta-tagging-product-info" style="display: none;"></div>' +
				'<p class="wpz-insta-tagging-hint">' + ( strings.taggingHint || 'Click on the image to place the product tag. You can drag to reposition.' ) + '</p>' +
				'<div class="wpz-insta-tagging-canvas-wrap">' +
				'<img id="wpz-insta-tagging-image" src="" alt="" />' +
				'<div class="wpz-insta-tagging-tags" id="wpz-insta-tagging-tags"></div>' +
				'</div>' +
				'</div>' +
				'</div>' +
				'<div class="wpz-insta-modal-footer">' +
				'<div class="wpz-insta-modal-footer-right">' +
				'<button type="button" class="button wpz-insta-remove-link" disabled>' + ( strings.removeLink || 'Remove Link' ) + '</button>' +
				'</div>' +
				'<div class="wpz-insta-modal-footer-left">' +
				'<button type="button" class="button button-primary wpz-insta-save-link" disabled>' + ( strings.save || 'Save' ) + '</button>' +
				'<button type="button" class="button wpz-insta-cancel-link">' + ( strings.cancel || 'Cancel' ) + '</button>' +
				'</div>' +
				'</div>' +
				'<div class="wpz-insta-tagging-footer" style="display: none;">' +
				'<div class="wpz-insta-modal-footer-right">' +
				'<button type="button" class="button wpz-insta-remove-tag-position" disabled>' + ( strings.removeTagPosition || 'Remove tag position' ) + '</button>' +
				'</div>' +
				'<div class="wpz-insta-modal-footer-left">' +
				'<button type="button" class="button button-primary wpz-insta-save-link">' + ( strings.save || 'Save' ) + '</button>' +
				'<button type="button" class="button wpz-insta-cancel-link">' + ( strings.cancel || 'Cancel' ) + '</button>' +
				'</div>' +
				'</div>' +
				'</div>' +
				'</div>';

			$( 'body' ).append( modalHTML );
			$productLinkModal = $( '#wpz-insta-product-link-modal' );

			// Close modal handlers
			$productLinkModal.on( 'click', '.wpz-insta-modal-close, .wpz-insta-cancel-link', function() {
				resetTaggingView();
				$productLinkModal.hide();
			} );

			// Remove link handler
			$productLinkModal.on( 'click', '.wpz-insta-remove-link', function() {
				saveProductLinkToMemory( [] );
			} );

			// Save link handler - now saves to memory, not DB
			$productLinkModal.on( 'click', '.wpz-insta-save-link', function() {
				var products = JSON.parse( JSON.stringify( currentSelectedProducts ) );
				saveProductLinkToMemory( products );
			} );

			// "Tag in Image" button click handler
			$productLinkModal.on( 'click', '.wpz-insta-tag-in-image-btn', function( e ) {
				e.stopPropagation(); // Prevent toggling selection
				var $item = $( this ).closest( '.wpz-insta-product-item' );
				var productId = parseInt( $item.data( 'product-id' ), 10 );
				var productTitle = decodeURIComponent( $item.data( 'product-title' ) || '' );
				var productImage = decodeURIComponent( $item.data( 'product-image' ) || '' );

				// Store the product being tagged
				currentTaggingProductId = productId;

				// For carousel_album, show album selector first
				if ( currentMediaType === 'carousel_album' ) {
					showAlbumSelectorView( productId, productTitle, productImage );
				} else {
					// For single images, go directly to tagging view
					showTaggingView( productId, productTitle, productImage, currentImageUrl );
				}
			} );

			// "Back to Products" button click handler - always go back to Link to Product
			$productLinkModal.on( 'click', '.wpz-insta-back-to-products', function() {
				resetTaggingView();
			} );

			// "Remove tag position" button in tagging view
			$productLinkModal.on( 'click', '.wpz-insta-remove-tag-position', function() {
				if ( $( this ).prop( 'disabled' ) ) return;
				removeCurrentTagPosition();
			} );

			// Album image selection click handler
			$productLinkModal.on( 'click', '.wpz-insta-album-image-item', function() {
				var imageUrl = $( this ).data( 'image-url' );
				var imageIndex = $( this ).data( 'image-index' );
				currentSelectedAlbumIndex = imageIndex;

				// Get stored product info and show tagging view
				var productTitle = $( '#wpz-insta-tagging-product-info' ).data( 'product-title' ) || '';
				var productImage = $( '#wpz-insta-tagging-product-info' ).data( 'product-image' ) || '';

				showTaggingView( currentTaggingProductId, productTitle, productImage, imageUrl );
			} );

			// Tagging canvas click handler - place tag
			$productLinkModal.on( 'click', '#wpz-insta-tagging-image', function( e ) {
				var $img = $( this );
				var offset = $img.offset();
				var x = ( ( e.pageX - offset.left ) / $img.width() ) * 100;
				var y = ( ( e.pageY - offset.top ) / $img.height() ) * 100;

				// Clamp to 0-100
				x = Math.max( 0, Math.min( 100, x ) );
				y = Math.max( 0, Math.min( 100, y ) );

				placeProductTag( currentTaggingProductId, x, y );
			} );

			// Product item click: toggle selection (multi-select)
			$productLinkModal.on( 'click', '.wpz-insta-product-item', function() {
				var id = parseInt( $( this ).data( 'product-id' ), 10 );
				var existingIndex = findProductIndex( currentSelectedProducts, id );
				if ( existingIndex === -1 ) {
					// Add product without tag
					currentSelectedProducts.push( { id: id, tag: null } );
					$( this ).addClass( 'selected' );
				} else {
					// Remove product (and its tag)
					currentSelectedProducts.splice( existingIndex, 1 );
					$( this ).removeClass( 'selected has-tag' );
				}
				// Update button display
				updateProductItemButton( $( this ), id );
				// Enable save button if selection differs from original
				var hasChanges = ! areProductArraysEqual( currentSelectedProducts, originalSelectedProducts );
				$( '.wpz-insta-save-link' ).prop( 'disabled', ! hasChanges );
				$( '.wpz-insta-remove-link' ).prop( 'disabled', ! currentSelectedProducts.length > 0 );
			} );

			// Search handler
			var searchTimeout;
			$( '#wpz-insta-product-search-input' ).on( 'input', function() {
				clearTimeout( searchTimeout );
				var search = $( this ).val();
				searchTimeout = setTimeout( function() {
					loadProducts( search, 1 );
				}, 500 );
			} );
		}

		// Reset modal to product selection view only (no loadProducts). Use when opening the modal.
		function resetProductLinkModalView() {
			isTaggingViewActive = false;
			currentTaggingProductId = null;
			currentSelectedAlbumIndex = null;
			var strings = zoom_instagram_widget_admin.strings || {};
			$productLinkModal.find( '.wpz-insta-modal-title' ).text( strings.linkProduct || 'Link to Product' );
			$productLinkModal.find( '#wpz-insta-tagging-header-preview' ).hide().empty();
			$productLinkModal.find( '.wpz-insta-product-selection-view' ).show();
			$productLinkModal.find( '.wpz-insta-album-selector-view' ).hide();
			$productLinkModal.find( '.wpz-insta-tagging-view' ).hide();
			$productLinkModal.find( '.wpz-insta-back-to-products' ).hide();
			$productLinkModal.find( '.wpz-insta-modal-footer' ).show();
			$productLinkModal.find( '.wpz-insta-tagging-footer' ).hide();
			$( '#wpz-insta-tagging-tags' ).empty();
			$( '#wpz-insta-tagging-image' ).attr( 'src', '' );
		}

		// Reset tagging view and show product selection
		function resetTaggingView() {
			isTaggingViewActive = false;
			currentTaggingProductId = null;
			currentSelectedAlbumIndex = null;
			var strings = zoom_instagram_widget_admin.strings || {};

			// Reset modal title
			$productLinkModal.find( '.wpz-insta-modal-title' ).text( strings.linkProduct || 'Link to Product' );

			// Hide header product preview (used only in tagging view)
			$productLinkModal.find( '#wpz-insta-tagging-header-preview' ).hide().empty();

			// Show product selection, hide tagging/album views
			$productLinkModal.find( '.wpz-insta-product-selection-view' ).show();
			$productLinkModal.find( '.wpz-insta-album-selector-view' ).hide();
			$productLinkModal.find( '.wpz-insta-tagging-view' ).hide();

			// Hide back button in header, show main footer, hide tagging footer
			$productLinkModal.find( '.wpz-insta-back-to-products' ).hide();
			$productLinkModal.find( '.wpz-insta-modal-footer' ).show();
			$productLinkModal.find( '.wpz-insta-tagging-footer' ).hide();

			// Update footer buttons
			var hasChanges = ! areProductArraysEqual( currentSelectedProducts, originalSelectedProducts );
			$productLinkModal.find( '.wpz-insta-save-link' ).prop( 'disabled', ! hasChanges );
			$productLinkModal.find( '.wpz-insta-remove-link' ).prop( 'disabled', ! currentSelectedProducts.length > 0 );

			// Re-render product list to update tag button states
			var search = $( '#wpz-insta-product-search-input' ).val() || '';
			loadProducts( search, 1 );

			// Clear tagging canvas
			$( '#wpz-insta-tagging-tags' ).empty();
			$( '#wpz-insta-tagging-image' ).attr( 'src', '' );
		}

		// Show album selector view for carousel_album
		function showAlbumSelectorView( productId, productTitle, productImage ) {
			var strings = zoom_instagram_widget_admin.strings || {};
			isTaggingViewActive = false;

			// Update modal title
			$productLinkModal.find( '.wpz-insta-modal-title' ).text( strings.selectImage || 'Select Image to Tag' );

			// Store product info for later use (when user picks an album image)
			$( '#wpz-insta-tagging-product-info' )
				.data( 'product-id', productId )
				.data( 'product-title', productTitle )
				.data( 'product-image', productImage );

			// Hide product selection, show album selector
			$productLinkModal.find( '.wpz-insta-product-selection-view' ).hide();
			$productLinkModal.find( '.wpz-insta-album-selector-view' ).show();
			$productLinkModal.find( '.wpz-insta-tagging-view' ).hide();

			// Show back button in header, hide footer
			$productLinkModal.find( '.wpz-insta-back-to-products' ).show();
			$productLinkModal.find( '.wpz-insta-modal-footer' ).hide();

			// Load album images
			loadAlbumImages();
		}

		// Load album images for carousel_album via AJAX
		function loadAlbumImages() {
			var $container = $( '#wpz-insta-album-images' );
			$container.html( '<div class="wpz-insta-loading">Loading album images...</div>' );

			// If we already have album images cached, use them
			if ( currentAlbumImages.length > 0 ) {
				renderAlbumImages( currentAlbumImages );
				return;
			}

			// Fetch album children via AJAX
			$.ajax( {
				url: zoom_instagram_widget_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'wpz-insta_get-album-images',
					nonce: zoom_instagram_widget_admin.product_link_nonce,
					feed_id: currentFeedId,
					media_id: currentMediaId
				},
				success: function( response ) {
					if ( response.success && response.data && response.data.images ) {
						currentAlbumImages = response.data.images;
						renderAlbumImages( currentAlbumImages );
					} else {
						// Fallback: use main image if album fetch fails
						currentAlbumImages = [ { url: currentImageUrl, index: 0 } ];
						renderAlbumImages( currentAlbumImages );
					}
				},
				error: function() {
					// Fallback: use main image
					currentAlbumImages = [ { url: currentImageUrl, index: 0 } ];
					renderAlbumImages( currentAlbumImages );
				}
			} );
		}

		// Render album images in the selector
		function renderAlbumImages( images ) {
			var $container = $( '#wpz-insta-album-images' );
			var html = '';

			if ( images.length === 0 ) {
				html = '<div class="wpz-insta-no-images">No images found in this album.</div>';
			} else {
				images.forEach( function( img, index ) {
					var imageUrl = img.url || img;
					// Skip videos in album
					if ( img.type === 'video' ) {
						return;
					}
					html += '<div class="wpz-insta-album-image-item" data-image-url="' + encodeURIComponent( imageUrl ) + '" data-image-index="' + index + '">' +
						'<img src="' + imageUrl + '" alt="" />' +
						'<span class="wpz-insta-album-image-number">' + ( index + 1 ) + '</span>' +
						'</div>';
				} );
			}

			$container.html( html );
		}

		// Go back to album selector from tagging view
		function showAlbumSelectorFromTagging() {
			var strings = zoom_instagram_widget_admin.strings || {};
			isTaggingViewActive = false;

			// Update modal title
			$productLinkModal.find( '.wpz-insta-modal-title' ).text( strings.selectImage || 'Select Image to Tag' );

			// Show album selector, hide tagging view
			$productLinkModal.find( '.wpz-insta-album-selector-view' ).show();
			$productLinkModal.find( '.wpz-insta-tagging-view' ).hide();

			// Footer stays hidden (we're still in sub-view)
		}

		// Show the tagging view
		function showTaggingView( productId, productTitle, productImage, imageUrl ) {
			var strings = zoom_instagram_widget_admin.strings || {};
			isTaggingViewActive = true;

			// Update modal title
			$productLinkModal.find( '.wpz-insta-modal-title' ).text( strings.tagProduct || 'Tag Product on Image' );

			// Store product data (used by tag logic)
			$( '#wpz-insta-tagging-product-info' )
				.data( 'product-id', productId )
				.data( 'product-title', productTitle )
				.data( 'product-image', productImage );

			// Show product preview in header
			var headerPreviewHtml = '<div class="wpz-insta-tagging-product-preview">' +
				'<img src="' + productImage + '" alt="" />' +
				'<span>' + productTitle + '</span>' +
				'</div>';
			$( '#wpz-insta-tagging-header-preview' ).html( headerPreviewHtml ).show();

			// Set the tagging image
			$( '#wpz-insta-tagging-image' ).attr( 'src', decodeURIComponent( imageUrl ) );

			// Hide other views, show tagging view
			$productLinkModal.find( '.wpz-insta-product-selection-view' ).hide();
			$productLinkModal.find( '.wpz-insta-album-selector-view' ).hide();
			$productLinkModal.find( '.wpz-insta-tagging-view' ).show();

			// Show back button, show tagging footer, hide main footer
			$productLinkModal.find( '.wpz-insta-back-to-products' ).show();
			$productLinkModal.find( '.wpz-insta-modal-footer' ).hide();
			$productLinkModal.find( '.wpz-insta-tagging-footer' ).show();

			// Load existing tag position if any
			loadExistingTagPosition( productId );

			// Enable/disable "Remove tag position" in tagging footer
			updateRemoveTagPositionButtonVisibility();
		}

		// Remove the tag position for the current product in tagging view
		function removeCurrentTagPosition() {
			var productId = $( '#wpz-insta-tagging-product-info' ).data( 'product-id' );
			if ( productId == null ) return;

			var existingIdx = findProductIndex( currentSelectedProducts, productId );
			if ( existingIdx !== -1 ) {
				currentSelectedProducts[ existingIdx ].tag = null;
			}

			// Remove tag from canvas
			$( '#wpz-insta-tagging-tags' ).find( '.wpz-insta-tag[data-product-id="' + productId + '"]' ).remove();

			// Persist to pending
			pendingProductLinks[ currentMediaId ] = JSON.parse( JSON.stringify( currentSelectedProducts ) );
			updatePendingProductLinksInput();

			updateRemoveTagPositionButtonVisibility();
		}

		// Enable or disable the "Remove tag position" button depending on whether current product has a tag on canvas
		function updateRemoveTagPositionButtonVisibility() {
			var productId = $( '#wpz-insta-tagging-product-info' ).data( 'product-id' );
			var hasTag = productId != null && $( '#wpz-insta-tagging-tags .wpz-insta-tag[data-product-id="' + productId + '"]' ).length > 0;
			$productLinkModal.find( '.wpz-insta-remove-tag-position' ).prop( 'disabled', ! hasTag );
		}

		// Place a product tag on the image
		function placeProductTag( productId, x, y ) {
			var $container = $( '#wpz-insta-tagging-tags' );
			var productTitle = $( '#wpz-insta-tagging-product-info' ).data( 'product-title' ) || '';

			// Remove existing tag for this product (only one tag per product per image)
			$container.find( '.wpz-insta-tag[data-product-id="' + productId + '"]' ).remove();

			// Create new tag element
			var $tag = $( '<div class="wpz-insta-tag" data-product-id="' + productId + '" data-x="' + x + '" data-y="' + y + '">' +
				'<span class="wpz-insta-tag-dot"></span>' +
				'<span class="wpz-insta-tag-label">' + productTitle + '</span>' +
				'</div>' );

			$tag.css( {
				left: x + '%',
				top: y + '%'
			} );

			$container.append( $tag );

			// Auto-save the tag position immediately
			saveTagPositionFromCoords( productId, x, y );

			// Show "Remove tag position" button
			updateRemoveTagPositionButtonVisibility();

			// Make tag draggable
			makeTagDraggable( $tag );
		}

		// Save tag position from coordinates (called when placing or dragging)
		function saveTagPositionFromCoords( productId, x, y ) {
			// Find the product in currentSelectedProducts and update its tag
			var existingIdx = findProductIndex( currentSelectedProducts, productId );
			if ( existingIdx !== -1 ) {
				var tagData = { x: x, y: y };
				// For albums, include the album index
				if ( currentMediaType === 'carousel_album' && currentSelectedAlbumIndex !== null ) {
					tagData.album_index = currentSelectedAlbumIndex;
				} else {
					tagData.album_index = null;
				}
				currentSelectedProducts[ existingIdx ].tag = tagData;

				// Save to pending product links immediately
				pendingProductLinks[ currentMediaId ] = JSON.parse( JSON.stringify( currentSelectedProducts ) );
				updatePendingProductLinksInput();

				// Enable Save button when tag position differs from original
				var hasChanges = ! areProductArraysEqual( currentSelectedProducts, originalSelectedProducts );
				$productLinkModal.find( '.wpz-insta-save-link' ).prop( 'disabled', ! hasChanges );
			}
		}

		// Make a tag element draggable
		function makeTagDraggable( $tag ) {
			var isDragging = false;
			var startX, startY, startLeft, startTop;

			$tag.on( 'mousedown', function( e ) {
				if ( e.target.classList.contains( 'wpz-insta-tag-dot' ) || e.target.classList.contains( 'wpz-insta-tag' ) ) {
					isDragging = true;
					startX = e.pageX;
					startY = e.pageY;
					startLeft = parseFloat( $tag.css( 'left' ) );
					startTop = parseFloat( $tag.css( 'top' ) );
					$tag.addClass( 'dragging' );
					e.preventDefault();
				}
			} );

			$( document ).on( 'mousemove.tagdrag', function( e ) {
				if ( ! isDragging ) return;

				var $img = $( '#wpz-insta-tagging-image' );
				var imgWidth = $img.width();
				var imgHeight = $img.height();

				var deltaX = ( ( e.pageX - startX ) / imgWidth ) * 100;
				var deltaY = ( ( e.pageY - startY ) / imgHeight ) * 100;

				var newX = Math.max( 0, Math.min( 100, ( startLeft / imgWidth ) * 100 + deltaX ) );
				var newY = Math.max( 0, Math.min( 100, ( startTop / imgHeight ) * 100 + deltaY ) );

				// Recalculate based on pixel position
				var newLeftPx = startLeft + ( e.pageX - startX );
				var newTopPx = startTop + ( e.pageY - startY );

				newX = ( newLeftPx / imgWidth ) * 100;
				newY = ( newTopPx / imgHeight ) * 100;

				newX = Math.max( 0, Math.min( 100, newX ) );
				newY = Math.max( 0, Math.min( 100, newY ) );

				$tag.css( {
					left: newX + '%',
					top: newY + '%'
				} );
				$tag.data( 'x', newX );
				$tag.data( 'y', newY );
			} );

			$( document ).on( 'mouseup.tagdrag', function() {
				if ( isDragging ) {
					isDragging = false;
					$tag.removeClass( 'dragging' );

					// Auto-save after drag ends
					var productId = parseInt( $tag.data( 'product-id' ), 10 );
					var x = parseFloat( $tag.data( 'x' ) ) || parseFloat( $tag.css( 'left' ) );
					var y = parseFloat( $tag.data( 'y' ) ) || parseFloat( $tag.css( 'top' ) );
					saveTagPositionFromCoords( productId, x, y );
				}
			} );
		}

		// Load existing tag position for a product from currentSelectedProducts
		function loadExistingTagPosition( productId ) {
			var existingIdx = findProductIndex( currentSelectedProducts, productId );
			if ( existingIdx !== -1 && currentSelectedProducts[ existingIdx ].tag ) {
				var tag = currentSelectedProducts[ existingIdx ].tag;
				// For albums, only show tag if it matches the current album index
				if ( currentMediaType === 'carousel_album' && currentSelectedAlbumIndex !== null ) {
					if ( tag.album_index === currentSelectedAlbumIndex ) {
						displayTagOnCanvas( productId, tag.x, tag.y );
					}
				} else {
					displayTagOnCanvas( productId, tag.x, tag.y );
				}
			}
		}

		// Display a tag on canvas without saving (for loading existing tags)
		function displayTagOnCanvas( productId, x, y ) {
			var $container = $( '#wpz-insta-tagging-tags' );
			var productTitle = $( '#wpz-insta-tagging-product-info' ).data( 'product-title' ) || '';

			// Remove existing tag for this product
			$container.find( '.wpz-insta-tag[data-product-id="' + productId + '"]' ).remove();

			// Create tag element
			var $tag = $( '<div class="wpz-insta-tag" data-product-id="' + productId + '" data-x="' + x + '" data-y="' + y + '">' +
				'<span class="wpz-insta-tag-dot"></span>' +
				'<span class="wpz-insta-tag-label">' + productTitle + '</span>' +
				'</div>' );

			$tag.css( {
				left: x + '%',
				top: y + '%'
			} );

			$container.append( $tag );
			makeTagDraggable( $tag );
		}

		function loadProducts( search, page ) {
			page = page || 1;
			$( '#wpz-insta-product-list' ).html( '<div class="wpz-insta-loading">Loading...</div>' );

			$.ajax( {
				url: zoom_instagram_widget_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'wpz-insta_get-products',
					nonce: zoom_instagram_widget_admin.product_link_nonce,
					search: search || '',
					page: page
				},
				success: function( response ) {
					if ( response.success && response.data.products ) {
						displayProducts( response.data.products, response.data.pages, page );
					} else {
						$( '#wpz-insta-product-list' ).html( '<div class="wpz-insta-no-products">No products found.</div>' );
					}
				},
				error: function() {
					$( '#wpz-insta-product-list' ).html( '<div class="wpz-insta-error">Error loading products.</div>' );
				}
			} );
		}

		function displayProducts( products, totalPages, currentPage ) {
			var html = '';
			var strings = zoom_instagram_widget_admin.strings || {};
			// Only show "Tag in Image" button for image and carousel_album (not video)
			var canTag = currentMediaType === 'image' || currentMediaType === 'carousel_album';
			var selectedIds = getCurrentSelectedIds();
			if ( products.length === 0 ) {
				html = '<div class="wpz-insta-no-products">No products found.</div>';
			} else {
				products.forEach( function( product ) {
					var productId = parseInt( product.id, 10 );
					var isSelected = selectedIds.indexOf( productId ) !== -1;
					var hasTag = isProductTagged( productId );
					var selectedClass = isSelected ? ' selected' : '';
					var taggedClass = hasTag ? ' has-tag' : '';
					html += '<div class="wpz-insta-product-item' + selectedClass + taggedClass + '" data-product-id="' + product.id + '" data-product-title="' + encodeURIComponent( product.title ) + '" data-product-image="' + encodeURIComponent( product.image ) + '">' +
						'<span class="wpz-insta-product-item-checkbox" aria-hidden="true"></span>' +
						'<img src="' + product.image + '" alt="" />' +
						'<div class="wpz-insta-product-info">' +
						'<h4>' + product.title + '</h4>' +
						'<span class="wpz-insta-product-price">' + product.price + '</span>' +
						'</div>';
					// Add "Tag in Image" button (visible only when selected, for Photos and Albums)
					if ( canTag ) {
						var btnClass = 'button wpz-insta-tag-in-image-btn' + ( hasTag ? ' wpz-insta-tag-btn--tagged' : '' );
						var btnText = hasTag ? ( strings.editTag || 'Edit Tag' ) : ( strings.tagInImage || 'Tag in Image' );
						var btnIcon = '<svg xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 -960 960 960" width="18" fill="currentColor"><path d="M640-760v280l68 68q6 6 9 13.5t3 15.5v23q0 17-11.5 28.5T680-320H520v234q0 17-11.5 28.5T480-46q-17 0-28.5-11.5T440-86v-234H280q-17 0-28.5-11.5T240-360v-23q0-8 3-15.5t9-13.5l68-68v-280q-17 0-28.5-11.5T280-800q0-17 11.5-28.5T320-840h320q17 0 28.5 11.5T680-800q0 17-11.5 28.5T640-760Z"></path></svg>';

						html += `<button type="button" class="${btnClass}" title="${btnText}">
							${btnIcon} ${btnText}
						</button>`;
					}
					html += '</div>';
				} );
			}
			$( '#wpz-insta-product-list' ).html( html );

			// Pagination
			if ( totalPages > 1 ) {
				var paginationHTML = '';
				if ( currentPage > 1 ) {
					paginationHTML += '<button type="button" class="button wpz-insta-prev-page" data-page="' + ( currentPage - 1 ) + '">Previous</button>';
				}
				if ( currentPage < totalPages ) {
					paginationHTML += '<button type="button" class="button wpz-insta-next-page" data-page="' + ( currentPage + 1 ) + '">Next</button>';
				}
				$( '.wpz-insta-product-pagination' ).html( paginationHTML );
			} else {
				$( '.wpz-insta-product-pagination' ).html( '' );
			}

			// Enable save button if selection differs from original
			var hasChanges = ! areProductArraysEqual( currentSelectedProducts, originalSelectedProducts );
			$( '.wpz-insta-save-link' ).prop( 'disabled', ! hasChanges );
			$( '.wpz-insta-remove-link' ).prop( 'disabled', ! currentSelectedProducts.length > 0 );
		}

		// Save product link to memory (not to DB) - will be saved when post is saved
		function saveProductLinkToMemory( products ) {
			// Accept array of product objects { id, tag } or empty array
			products = Array.isArray( products ) ? products : [];

			// Update pending product links in memory
			if ( products.length > 0 ) {
				pendingProductLinks[ currentMediaId ] = products;
			} else {
				// If removing all links, store empty array to indicate removal
				pendingProductLinks[ currentMediaId ] = [];
			}

			// Update the hidden input for form submission
			updatePendingProductLinksInput();

			// Update preview iframe
			var hasLinks = products.length > 0;
			updatePreviewLinkedProducts( currentMediaId, hasLinks );

			// Close modal
			$productLinkModal.hide();
		}

		// Product Links upsell modal (shown when no valid license)
		var $productLinksUpsellModal = null;
		function initProductLinksUpsellModal() {
			if ( $productLinksUpsellModal ) {
				return;
			}
			var strings = zoom_instagram_widget_admin.strings || {};
			var upsellTitle = strings.productLinksUpsellTitle || 'Unlock Product Links';
			var upsellMessage = strings.productLinksUpsellMessage || 'Link your Instagram posts to WooCommerce products and display the product details or a Buy now button. This feature is available with an active Instagram Widget PRO license.';
			var upsellCta = strings.productLinksUpsellCta || 'Get PRO License';
			var upsellClose = strings.productLinksUpsellClose || 'Close';
			var upsellUrl = zoom_instagram_widget_admin.product_links_upsell_url || 'https://www.wpzoom.com/plugins/instagram-widget/';
			var upsellImageUrl = zoom_instagram_widget_admin.product_links_upsell_image_url || '';
			var upsellImageHTML = upsellImageUrl ? '<img src="' + upsellImageUrl + '" alt="" class="wpz-insta-upsell-image" />' : '';
			var upsellHTML = '<div id="wpz-insta-product-links-upsell-modal" class="wpz-insta-modal wpz-insta-upsell-modal" style="display: none;">' +
				'<div class="wpz-insta-modal-content wpz-insta-upsell-content">' +
				'<div class="wpz-insta-modal-header">' +
				'<h2>' + upsellTitle + '</h2>' +
				'<button type="button" class="wpz-insta-modal-close">&times;</button>' +
				'</div>' +
				'<div class="wpz-insta-modal-body">' +
				upsellImageHTML +
				'<p class="wpz-insta-upsell-message">' + upsellMessage + '</p>' +
				'<div class="wpz-insta-upsell-actions">' +
				'<a href="' + upsellUrl + '" target="_blank" rel="noopener" class="button button-primary wpz-insta-upsell-cta">' + upsellCta + '</a>' +
				'</div>' +
				'</div>' +
				'<div class="wpz-insta-modal-footer">' +
				'<button type="button" class="button wpz-insta-upsell-close">' + upsellClose + '</button>' +
				'</div>' +
				'</div></div>';
			$( 'body' ).append( upsellHTML );
			$productLinksUpsellModal = $( '#wpz-insta-product-links-upsell-modal' );
			$productLinksUpsellModal.on( 'click', '.wpz-insta-modal-close, .wpz-insta-upsell-close', function() {
				$productLinksUpsellModal.hide();
			} );
		}

		// Open product link modal when iframe sends message (button is inside iframe)
		window.addEventListener( 'message', function( e ) {
			if ( ! e.data || e.data.action !== 'wpz-insta-open-product-link' ) {
				return;
			}
			// If no valid license (not PRO), show upsell modal instead of product selection
			if ( ! zoom_instagram_widget_admin.is_pro ) {
				initProductLinksUpsellModal();
				$productLinksUpsellModal.show();
				return;
			}
			currentMediaId = e.data.mediaId || '';
			currentFeedId = e.data.feedId || '';
			currentMediaType = e.data.mediaType || 'image';
			currentImageUrl = e.data.imageUrl || '';
			currentAlbumImages = []; // Reset album images cache

			initProductLinkModal();
			resetProductLinkModalView(); // Always open on "Link to Product" view, not tagging view
			$productLinkModal.show();
			$( '#wpz-insta-product-list' ).html( '<div class="wpz-insta-loading">Loading...</div>' );
			$( '.wpz-insta-save-link' ).prop( 'disabled', true );

			// First check if we have pending changes in memory for this media ID
			if ( currentMediaId in pendingProductLinks ) {
				currentSelectedProducts = JSON.parse( JSON.stringify( pendingProductLinks[ currentMediaId ] ) );
				originalSelectedProducts = JSON.parse( JSON.stringify( currentSelectedProducts ) );
				$( '.wpz-insta-remove-link' ).prop( 'disabled', ! currentSelectedProducts.length > 0 );
				loadProducts( '', 1 );
			} else {
				// Fetch current linked products from DB (supports multi-select with tags)
				$.ajax( {
					url: zoom_instagram_widget_admin.ajax_url,
					type: 'POST',
					data: {
						action: 'wpz-insta_get-product-link',
						nonce: zoom_instagram_widget_admin.product_link_nonce,
						feed_id: currentFeedId,
						media_id: currentMediaId
					},
					success: function( response ) {
						if ( response.success && response.data && response.data.products && Array.isArray( response.data.products ) ) {
							// New format: array of { id, tag }
							currentSelectedProducts = response.data.products.slice();
							initialProductLinks[ currentMediaId ] = JSON.parse( JSON.stringify( response.data.products ) );
						} else if ( response.success && response.data && response.data.product_ids && Array.isArray( response.data.product_ids ) ) {
							// Old format: array of IDs - convert to new format
							currentSelectedProducts = response.data.product_ids.map( function( id ) {
								return { id: id, tag: null };
							} );
							initialProductLinks[ currentMediaId ] = JSON.parse( JSON.stringify( currentSelectedProducts ) );
						} else {
							currentSelectedProducts = [];
							initialProductLinks[ currentMediaId ] = [];
						}
						originalSelectedProducts = JSON.parse( JSON.stringify( currentSelectedProducts ) );
						$( '.wpz-insta-remove-link' ).prop( 'disabled', ! currentSelectedProducts.length > 0 );
						loadProducts( '', 1 );
					},
					error: function() {
						currentSelectedProducts = [];
						originalSelectedProducts = [];
						initialProductLinks[ currentMediaId ] = [];
						$( '.wpz-insta-remove-link' ).prop( 'disabled', true );
						loadProducts( '', 1 );
					}
				} );
			}
		} );

		// Pagination handlers (preserve currentSelectedProducts)
		$( document ).on( 'click', '.wpz-insta-prev-page, .wpz-insta-next-page', function() {
			var page = $( this ).data( 'page' );
			var search = $( '#wpz-insta-product-search-input' ).val();
			loadProducts( search, page );
		} );
	}

	// =============================
	// Moderate Posts functionality
	// =============================

	// Pending hidden posts stored in memory (saved to DB when post is saved)
	// Structure: { mediaId: true, ... } where true = hidden
	var pendingHiddenPosts = {};

	// Track which media IDs were hidden when page loaded (from DB)
	var initialHiddenPosts = {};

	// Initialize pending hidden posts from existing data
	function initPendingHiddenPosts() {
		var $hiddenInput = $( '#wpz-insta-pending-hidden-posts' );
		if ( $hiddenInput.length === 0 ) {
			$( 'form#post' ).append( '<input type="hidden" id="wpz-insta-pending-hidden-posts" name="_wpz-insta_pending-hidden-posts" value="" class="preview-exclude" />' );
		}

		// Load initial hidden posts from localized data
		if ( typeof zoom_instagram_widget_admin !== 'undefined' && zoom_instagram_widget_admin.hidden_posts ) {
			try {
				var parsed = typeof zoom_instagram_widget_admin.hidden_posts === 'string'
					? JSON.parse( zoom_instagram_widget_admin.hidden_posts )
					: zoom_instagram_widget_admin.hidden_posts;
				if ( Array.isArray( parsed ) ) {
					parsed.forEach( function( id ) {
						initialHiddenPosts[ id ] = true;
					} );
				}
			} catch ( e ) {}
		}
	}

	// Update the hidden input with current pending hidden posts
	function updatePendingHiddenPostsInput() {
		var $hiddenInput = $( '#wpz-insta-pending-hidden-posts' );
		if ( $hiddenInput.length ) {
			$hiddenInput.val( JSON.stringify( pendingHiddenPosts ) );
		}

		// Trigger form change detection so Save button becomes active
		if ( hasAnyHiddenPostChanges() ) {
			if ( ! ( '_wpz-insta_pending-hidden-posts' in formChangedValues ) ) {
				formChangedValues[ '_wpz-insta_pending-hidden-posts' ] = true;
			}
		} else {
			if ( '_wpz-insta_pending-hidden-posts' in formChangedValues ) {
				delete formChangedValues[ '_wpz-insta_pending-hidden-posts' ];
			}
		}
		$( 'input#publish' ).toggleClass( 'disabled', $.isEmptyObject( formChangedValues ) );
	}

	// Check if there are any pending changes compared to initial state
	function hasAnyHiddenPostChanges() {
		return ! $.isEmptyObject( pendingHiddenPosts );
	}

	// Check if a media ID is hidden (check pending first, then initial)
	function isPostHidden( mediaId ) {
		if ( mediaId in pendingHiddenPosts ) {
			return pendingHiddenPosts[ mediaId ];
		}
		return !! initialHiddenPosts[ mediaId ];
	}

	// Toggle the hidden state of a media item
	function togglePostVisibility( mediaId ) {
		var currentlyHidden = isPostHidden( mediaId );
		var newState = ! currentlyHidden;

		// Store the new state
		pendingHiddenPosts[ mediaId ] = newState;

		// If we're reverting to the initial state, remove from pending
		if ( ( !! initialHiddenPosts[ mediaId ] ) === newState ) {
			delete pendingHiddenPosts[ mediaId ];
		}

		updatePendingHiddenPostsInput();

		// Notify iframe to update the visual state
		var iframe = document.querySelector( '#wpz-insta_widget-preview-view iframe' );
		if ( iframe && iframe.contentWindow ) {
			iframe.contentWindow.postMessage( {
				action: 'wpz-insta-moderate-update',
				mediaId: mediaId,
				hidden: newState
			}, '*' );
		}
	}

	// Initialize moderate posts
	initPendingHiddenPosts();

	// Listen for moderate toggle messages from the iframe
	window.addEventListener( 'message', function( e ) {
		if ( ! e.data || e.data.action !== 'wpz-insta-toggle-visibility' ) {
			return;
		}
		var mediaId = e.data.mediaId || '';
		if ( mediaId ) {
			togglePostVisibility( mediaId );
		}
	} );
} );
