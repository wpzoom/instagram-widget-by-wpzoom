/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/*!**************************************!*\
  !*** ./src/scripts/backend/index.js ***!
  \**************************************/


function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
jQuery(function ($) {
  var $imageMediaControlTarget = $();
  $.fn.imageMediaControl = function () {
    this.each(function () {
      var $this = $(this);
      var mediaControl = {
        // Initializes a new media manager or returns an existing frame.
        // @see wp.media.featuredImage.frame()
        frame: function frame() {
          if (this._frame) return this._frame;
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
        select: function select() {
          var _selectionData$sizes$, _selectionData$sizes$2;
          var $attachmentInput = $this.find('.attachment-input').add($imageMediaControlTarget.find('input#wpz-insta_account-photo'));
          var selection = this.get('selection');
          var attachmentId = selection.pluck('id');
          $attachmentInput.val('' + attachmentId).trigger('change');
          var selectionData = selection.first().toJSON();
          var thumbnail_url = (_selectionData$sizes$ = (_selectionData$sizes$2 = selectionData.sizes.thumbnail) === null || _selectionData$sizes$2 === void 0 ? void 0 : _selectionData$sizes$2.url) !== null && _selectionData$sizes$ !== void 0 ? _selectionData$sizes$ : selectionData.sizes.full.url;
          $imageMediaControlTarget.find('img.wpz-insta_profile-photo').attr('src', '' + thumbnail_url);
        },
        updateFrame: function updateFrame() {},
        init: function init() {
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
  };
  $('.zoom-instagram-user-avatar-media-uploader, .inline-edit-wpz-insta_user .wpz-insta_quick-edit-columns .wpz-insta_two-columns').imageMediaControl();
  $('#wpzoom_instagram_clear_data').on('click', function (e) {
    e.preventDefault();
    var data = {
      action: 'wpzoom_instagram_clear_data',
      nonce: $(this).data('nonce')
    };
    var $this = $(this);
    if (window.confirm("Are you sure?")) {
      $this.text('Removing data...');
      $.post(zoom_instagram_widget_admin.ajax_url, data, function (response) {
        if (response.success) {
          $this.text('Done!');
          $this.prop('disabled', true);
          $this.next().html(response.data.message);
        }
      });
    }
  });
  $(window).on('beforeunload', function (e) {
    if (!$.isEmptyObject(formChangedValues) && !formSubmitted) {
      e.preventDefault();
    }
  });
  $('#the-list').on('click', '#wpz-insta_reconnect', function (e) {
    e.preventDefault();
    if ($(this).attr('href').length > 0) {
      window.wpzInstaAuthenticateInstagram($(this).attr('href'));
    }
  });
  $('.wpzoom-instagram-widget-settings-request-type-wrapper').find('input[type=radio]').on('change', function (e) {
    e.preventDefault();
    var activeClass = $(this).val();
    var allDivs = ['with-access-token', 'with-basic-access-token', 'without-access-token'];
    var inactiveDivs = allDivs.filter(function (item) {
      return item !== activeClass;
    });
    var $formTable = $(this).closest('.form-table');
    $formTable.find('.wpzoom-instagram-widget-' + activeClass + '-group').show();
    inactiveDivs.forEach(function (inactive) {
      $formTable.find('.wpzoom-instagram-widget-' + inactive + '-group').hide();
    });
  });
  $('.wpzoom-instagram-widget-settings-request-type-wrapper').find('input[type=radio]:checked').change();
  setTimeout(function () {
    if ('hash' in window.location && '' != ('' + window.location.hash).trim()) {
      var $elem = $('.edit-php.post-type-wpz-insta_user #the-list').find(('' + window.location.hash).trim());
      if ($elem.length > 0) {
        $elem.find('button.editinline').trigger('click');
      }
    }
  }, 100);
  if (window.opener && window.location.hash.length > 1 || isLikelyPopup(700, 960, 750, 1200) && window.location.hash.length > 1) {
    if (window.opener && typeof window.opener.wpzInstaHandleReturnedToken === 'function') {
      window.opener.wpzInstaHandleReturnedToken(window.location);
      window.close();
    } else {
      var getToken = getAccessTokenFromURL(window.location);
      var notice = $('#wpz-insta_modal-dialog-connection-failed');
      $('#wpz_generated_token').text(getToken);
      notice.addClass('open');
      $('#wpfooter').show();
    }
    if (window.location.hash.includes('access_graph_token')) {
      window.opener.wpzInstaHandleReturnedGraphToken(window.location);
      window.close();
    }
  }
  function getAccessTokenFromURL() {
    // Get the full URL from window.location.href
    var url = window.location.href;

    // Check if the URL contains a hash and if it includes 'access_token'
    var hash = url.split('#')[1]; // Get everything after the '#'
    if (hash && hash.includes('access_token=')) {
      // Extract the query parameters in the hash
      var params = new URLSearchParams(hash);
      // Get the value of 'access_token'
      var accessToken = params.get('access_token');
      return accessToken;
    }
    return null; // Return null if no access_token is found
  }
  $('#screen-meta #wpz-insta_account-photo-hide, #screen-meta #wpz-insta_account-bio-hide, #screen-meta #wpz-insta_account-token-hide, #screen-meta #wpz-insta_actions-hide').closest('label').remove();
  $('#titlediv').remove();
  if ($('#title').length > 0) {
    $('#title').attr('size', $('#title').val().trim().length + 3);
    $('#title').on('input', function () {
      $(this).attr('size', $(this).val().trim().length + 3);
    });
  }
  if ($('.wpz-insta_feed-edit-nav').length > 0) {
    if (window.location.hash) {
      setTab(window.location.hash);
    }
    $('.wpz-insta_feed-edit-nav a').on('click', function () {
      setTab($(this).attr('href'));
    });
  }
  $('#wpz-insta_show-pro').on('change', function (e) {
    e.preventDefault();
    $(this).closest('.wpz-insta_sidebar').toggleClass('show-pro', this.checked);
  });
  $('#wpz-insta_connect-personal, #wpz-insta_connect-business, .wpz-insta_tabs-config-connect-add').each(function () {
    // Get the current href attribute
    var currentHref = $(this).attr('href');

    // Check if the href attribute exists
    if (currentHref) {
      // Construct the new URL part
      var newUrlPart = btoa(encodeURIComponent(zoom_instagram_widget_admin.feeds_url));

      // Replace RETURN_URL in the href with the new encoded URL part
      var newHref = currentHref.replace('RETURN_URL', newUrlPart);

      // Set the new href attribute on the current element
      $(this).attr('href', newHref);
    }
  });
  $('.wpz-insta-wrap .account-options .account-option-button').on('click', function (e) {
    e.preventDefault();
    if (!$(this).is('.disabled')) {
      if ($(this).is('#wpz-insta_connect-personal') || $(this).is('#wpz-insta_connect-business')) {
        window.wpzInstaAuthenticateInstagram($(this).attr('href'));
      } else if ($(this).is('#wpz-insta_account-token-button')) {
        window.wpzInstaHandleReturnedGraphToken($('#wpz-insta_account-token-input').val().trim().replace(/[^a-z0-9-_.]+/gi, ''), true);
      } else if ($(this).is('#wpz-insta-biz_account-token-button')) {
        window.wpzInstaHandleReturnedToken($('#wpz-insta_biz_account-token-input').val().trim().replace(/[^a-z0-9-_.]+/gi, ''), true);
      }
    }
  });
  $('#wpz-insta_account-token-input').on('input', function () {
    $('#wpz-insta_account-token-button').toggleClass('disabled', $('#wpz-insta_account-token-input').val().trim().length <= 0);
  });
  $('#wpz-insta_biz_account-token-input').on('input', function () {
    $('#wpz-insta-biz_account-token-button').toggleClass('disabled', $('#wpz-insta_biz_account-token-input').val().trim().length <= 0);
  });
  $('.wpz-insta_sidebar-section-layout input[name="_wpz-insta_layout"]').on('change', function () {
    var $colNumOption = $(this).closest('.wpz-insta_sidebar-section-layout').find('input[name="_wpz-insta_col-num"]').closest('.wpz-insta_table-row'),
      $perPageOption = $(this).closest('.wpz-insta_sidebar-section-layout').find('input[name="_wpz-insta_perpage-num"]').closest('.wpz-insta_table-row'),
      $featOption = $(this).closest('.wpz-insta_sidebar-section-layout').find('.wpz-insta_table-row-featured-layout'),
      $featOptionWrap = $featOption.closest('.wpz-insta_feed-only-pro'),
      $parentLeftSect = $(this).closest('.wpz-insta_sidebar-left-section'),
      $proFieldset = $parentLeftSect.find('.wpz-insta_sidebar-section-feed .wpz-insta_show-on-hover fieldset.wpz-insta_feed-only-pro.wpz-insta_pro-only'),
      $loadMoreOptions = $parentLeftSect.find('.wpz-insta_sidebar-section-load-more'),
      $toggleItems = $colNumOption.add($proFieldset).add($loadMoreOptions);
    $toggleItems.toggleClass('hidden', $(this).val() == '1' || $(this).val() == '3');
    $('.wpz-insta-admin .wpz-insta_widget-preview .wpz-insta_widget-preview-view').toggleClass('layout-fullwidth', $(this).val() == '1');
    $featOption.toggleClass('hidden', $(this).val() != '0');
    if (!$('.wpz-insta_sidebar .wpz-insta_sidebar-left').hasClass('.is-pro')) $featOptionWrap.toggleClass('hidden', $(this).val() != '0');
    $perPageOption.toggleClass('hidden', $(this).val() != '3');
  });
  $('.wpz-insta_sidebar-section-layout input[name="_wpz-insta_col-num"]').on('input', function () {
    if ($('.wpz-insta_sidebar-section-layout input[name="_wpz-insta_layout"]:checked').val() == '0') {
      var colNum = parseInt($(this).closest('.wpz-insta_sidebar-section-layout').find('input[name="_wpz-insta_col-num"]').val()),
        $featuredLayouts = $(this).closest('.wpz-insta_table').find('label.featured-layout'),
        $featuredLayoutsWrap = $featuredLayouts.closest('.wpz-insta_table-row');
      if (colNum < 3 || colNum > 6) {
        $featuredLayoutsWrap.addClass('hidden');
      } else {
        $featuredLayoutsWrap.removeClass('hidden');
        $featuredLayouts.addClass('hidden');
        $featuredLayouts.each(function () {
          if ($(this).is('.featured-layout-columns_' + colNum)) {
            $(this).removeClass('hidden');
          }
        });
      }
    }
  });
  $('.wpz-insta_sidebar-section-layout input[name="_wpz-insta_col-num_responsive-enabled"]').on('change', function () {
    $(this).closest('.wpz-insta_responsive-table-row').toggleClass('wpz-insta_responsive-enabled', $(this).is(':checked'));
  });
  $('.wpz-insta_sidebar-section-layout input[name="_wpz-insta_perpage-num_responsive-enabled"]').on('change', function () {
    $(this).closest('.wpz-insta_responsive-table-row').toggleClass('wpz-insta_responsive-enabled', $(this).is(':checked'));
  });
  $('#_wpz-insta_featured-layout-enable').on('change', function () {
    $(this).closest('.wpz-insta_table-row').find('.wpz-insta_image-select').toggleClass('hidden', !$(this).is(':checked'));
  });
  $('#wpz-insta_modal-dialog').find('.wpz-insta_modal-dialog_ok-button, .wpz-insta_modal-dialog_close-button').on('click', function (e) {
    e.preventDefault();
    var $dialog = $('#wpz-insta_modal-dialog');
    window.wpzInstaCloseConnectDoneDialog($dialog.hasClass('success'), $dialog.hasClass('update'));
  });
  $('#wpz-insta_modal_graph-dialog').find('.wpz-insta_modal-dialog_ok-button, .wpz-insta_modal-dialog_close-button').on('click', function (e) {
    e.preventDefault();
    var $dialog = $('#wpz-insta_modal_graph-dialog');
    $dialog.removeClass('open');
  });
  $('#wpz-insta_modal-dialog-connection-failed').find('.wpz-insta_modal-dialog_ok-button, .wpz-insta_modal-dialog_close-button').on('click', function (e) {
    e.preventDefault();
    var $dialog = $('#wpz-insta_modal-dialog-connection-failed');
    window.close();
    $dialog.removeClass('open');
  });
  $('#wpz-insta_feed-user-select-btn').on('click', function (e) {
    e.preventDefault();
    $('#wpz-insta_tabs-config-cnnct').removeClass('active').addClass('active').prev('.wpz-insta_sidebar').removeClass('active');
    $('#wpz-insta_tabs-config-cnnct').closest('.wpz-insta_tabs-content').find('> .wpz-insta_sidebar').addClass('hide');
  });
  $('#wpz-insta_feed-user-remove-btn').on('click', function (e) {
    e.preventDefault();
    var $btn = $('#wpz-insta_feed-user-select-btn'),
      $select = $btn.closest('.wpz-insta_feed-user-select'),
      $info = $select.find('.wpz-insta_feed-user-select-info');
    $('#wpz-insta_user-id').val('-1').trigger('change');
    $('#wpz-insta_user-token').val('-1').trigger('change');
    $('#wpz-insta_user-token, #wpz-insta_check-new-posts-interval-number, #wpz-insta_enable-request-timeout').closest('.wpz-insta_sidebar-section').removeClass('active');
    $('#wpz-insta_widget-preview-links').addClass('disabled');
    $('.wpz-insta_widget-preview-refresh').addClass('disabled').prop('disabled', true);
    $select.removeClass('is-set');
    $info.find('.wpz-insta_feed-user-select-info-name').html('None');
    $info.find('.wpz-insta_feed-user-select-info-type').html('None');
    $select.closest('.wrap').find('.wpz-insta_settings-header .wpz-insta_feed-edit-nav li:not(:first-child)').addClass('disable');
  });
  $('#wpz-insta_tabs-config-cnnct .wpz-insta_tabs-config-connect-accounts li').on('click', function (e) {
    e.preventDefault();
    var $btn = $('#wpz-insta_feed-user-select-btn'),
      $select = $btn.closest('.wpz-insta_feed-user-select'),
      $info = $select.find('.wpz-insta_feed-user-select-info');
    $('#wpz-insta_user-id').val($(this).data('user-id')).trigger('change');
    $('#wpz-insta_user-token').val($(this).data('user-token')).trigger('change');
    $('#wpz-insta_user-token, #wpz-insta_check-new-posts-interval-number, #wpz-insta_enable-request-timeout').closest('.wpz-insta_sidebar-section').addClass('active');
    $select.addClass('is-set');
    $info.find('.wpz-insta_feed-user-select-info-name').html($(this).data('user-name'));
    $info.find('.wpz-insta_feed-user-select-info-type').html($(this).data('user-type'));
    $select.closest('.wrap').find('.wpz-insta_settings-header .wpz-insta_feed-edit-nav li').removeClass('disable');
    $select.find('.wpz-insta_feed-user-select-edit-link').attr('href', zoom_instagram_widget_admin.edit_user_url + $(this).data('user-id'));
    $('#wpz-insta_widget-preview-links').removeClass('disabled');
    $('.wpz-insta_widget-preview-refresh').removeClass('disabled').prop('disabled', false);

    // Toggle Stories checkbox based on whether the account has a Facebook Page connection
    var hasPageId = $(this).data('has-page-id') === 1 || $(this).data('has-page-id') === '1';
    var $storiesRow = $('input[name="_wpz-insta_show-stories"]').not('[type="hidden"]').closest('.wpz-insta_table-row');
    var $storiesCheckbox = $storiesRow.find('input[type="checkbox"]');
    if (hasPageId) {
      $storiesRow.removeClass('wpz-insta_disabled');
      $storiesCheckbox.prop('disabled', false);
    } else {
      $storiesRow.addClass('wpz-insta_disabled');
      $storiesCheckbox.prop('disabled', true).prop('checked', false);
    }
    $('#wpz-insta_tabs-config-cnnct').removeClass('active').prev('.wpz-insta_sidebar').addClass('active');
    $('#wpz-insta_tabs-config-cnnct').closest('.wpz-insta_tabs-content').find('> .wpz-insta_sidebar').removeClass('hide');
  });
  var formFields = {};
  var formInitialValues = {};
  var formChangedValues = {};
  var formSubmitted = false;

  // Helper function to get current checkbox array values
  function getCheckboxArrayValue(name) {
    var checkedValues = [];
    $('input[name="' + name + '"]').each(function () {
      if ($(this).is(':checked')) {
        checkedValues.push($(this).val());
      }
    });
    return checkedValues.sort().join(','); // Sort for consistent comparison
  }

  // Include all named fields (including .preview-exclude) so Save button enables on change; preview-exclude only affects iframe URL
  $('form#post .wpz-insta_tabs-content > .wpz-insta_sidebar > .wpz-insta_sidebar-left').find('input, textarea, select').add('form#post #title').filter("[name][name!='']").each(function (index) {
    var fieldName = $.trim($(this).attr('name'));
    if ($(this).is(':radio')) {
      if ($(this).is(':checked')) {
        formFields[fieldName] = $(this);
      }
    } else if (fieldName.endsWith('[]')) {
      // Handle checkbox arrays - store all checkboxes with this name
      var baseName = fieldName.replace('[]', '');
      if (!formFields[baseName]) {
        formFields[baseName] = [];
      }
      formFields[baseName].push($(this));
    } else {
      formFields[fieldName] = $(this);
    }
  });
  $.each(formFields, function (i, val) {
    if (Array.isArray(val)) {
      // Handle checkbox arrays - get comma-separated list of checked values
      formInitialValues[i] = getCheckboxArrayValue(i + '[]');
    } else {
      formInitialValues[i] = val.is(':checkbox') ? val.is(':checked') ? '1' : '0' : $.trim('' + val.val());
    }
  });
  $('form#post').on('submit', function () {
    return formSubmitted = true;
  });
  $('form#post #title, form#post .wpz-insta_tabs-content > .wpz-insta_sidebar > .wpz-insta_sidebar-left').on('input change', debounce(function (e) {
    var $target = $(e.target);
    var key = $target.attr('name');

    // Skip elements without a name attribute
    if (!key) {
      return;
    }
    var trackingKey = key;
    var currentValue;
    if (key.endsWith('[]')) {
      // Handle checkbox arrays
      trackingKey = key.replace('[]', '');
      currentValue = getCheckboxArrayValue(key);
    } else {
      // Handle regular fields
      currentValue = $target.is(':checkbox') ? $target.is(':checked') ? '1' : '0' : $.trim('' + $target.val());
    }
    if (trackingKey in formInitialValues && currentValue != formInitialValues[trackingKey]) {
      if (!(trackingKey in formChangedValues)) {
        formChangedValues[trackingKey] = true;
      }
    } else {
      if (trackingKey in formChangedValues) {
        delete formChangedValues[trackingKey];
      }
    }
    $('input#publish').toggleClass('disabled', $.isEmptyObject(formChangedValues));

    // Update preview: postMessage for design settings (no reload), reload only for server-dependent fields
    if (key === 'post_title' || $target.is('.preview-exclude')) return;
    var needsReload = wpzInstaPreviewReloadKeys.indexOf(key) !== -1 || key && key.indexOf('_wpz-insta_allowed-post-types') === 0;
    if (needsReload) {
      window.wpzInstaReloadPreview();
    } else {
      wpzInstaSendPreviewUpdate();
    }
  }, 300));
  $(function () {
    // Only set iframe src from JS if PHP did not already set it (initial load uses PHP-built URL).
    var $previewIframe = $('#wpz-insta_widget-preview-view iframe');
    if (!$previewIframe.attr('src') || $previewIframe.attr('src') === '') {
      window.wpzInstaReloadPreview();
    }
  });
  $('#wpz-insta_widget-preview-links .wpz-insta_widget-preview-header-link').on('click', function () {
    if (!$(this).hasClass('active')) {
      $(this).addClass('active').siblings('.wpz-insta_widget-preview-header-link').removeClass('active');
      $(this).closest('.wpz-insta_widget-preview').find('.wpz-insta_widget-preview-view').removeClass('wpz-insta_widget-preview-size-desktop wpz-insta_widget-preview-size-tablet wpz-insta_widget-preview-size-mobile').addClass($(this).hasClass('wpz-insta_widget-preview-header-links-tablet') ? 'wpz-insta_widget-preview-size-tablet' : $(this).hasClass('wpz-insta_widget-preview-header-links-mobile') ? 'wpz-insta_widget-preview-size-mobile' : 'wpz-insta_widget-preview-size-desktop');
    }
  });
  $('#wpz-insta_widget-preview-view').on('transitionend', function () {
    var $iframe = $(this).find('iframe');
    $iframe.height(parseInt($iframe.contents().find('body').prop('scrollHeight')) + 20);
  });
  $('#wpz-insta_widget-preview-view iframe').on('load', function () {
    $(this).removeClass('wpz-insta_preview-hidden');
    $(this).closest('.wpz-insta_sidebar-right').addClass('hide-loading');
    // Sync current form state to preview via postMessage (so unsaved design changes apply without another reload)
    wpzInstaSendPreviewUpdate();
    // Re-send current tab so "Link to a product" buttons show if Product Links tab was clicked before iframe loaded
    var iframe = this;
    if (iframe.contentWindow) {
      var activeTab = $('.wpz-insta_feed-edit-nav li.active a').attr('href');
      if (activeTab) {
        var tabId = (activeTab + '').replace(/^#/, '');
        iframe.contentWindow.postMessage({
          action: 'wpz-insta-tab-change',
          tab: tabId
        }, '*');
      }
    }
  });
  $('.wpz-insta_color-picker').wpColorPicker({
    change: function change(event, ui) {
      var changeEvent = $.Event('change');
      changeEvent.target = event.target;
      $(event.target).closest('form#post').find('.wpz-insta_tabs-content > .wpz-insta_sidebar > .wpz-insta_sidebar-left').triggerHandler(changeEvent);
    }
  });

  // Product Links: show/hide settings by display type (icon vs button)
  function wpzInstaToggleProductLinksDisplayType() {
    var $container = $('#_wpz-insta_product-links-display-type');
    if (!$container.length) {
      return;
    }
    var $section = $container.closest('.wpz-insta_sidebar-left-section');
    var displayType = $container.find('input[name="_wpz-insta_product-links-display-type"]:checked').val();
    var isIcon = displayType === 'icon';
    $section.find('.wpz-insta-product-links-popover-title-row').toggleClass('hidden', !isIcon);
    $section.find('.wpz-insta-product-links-icon-position-row').toggleClass('hidden', !isIcon);
    $section.find('.wpz-insta_sidebar-section-product-links-icon-design').toggleClass('hidden', !isIcon);
    $section.find('.wpz-insta-buy-now-button-row').toggleClass('hidden', isIcon);
    $section.find('.wpz-insta-product-links-badge-row').toggleClass('hidden', isIcon);
    $section.find('.wpz-insta_sidebar-section-product-links-design').toggleClass('hidden', isIcon);
  }
  wpzInstaToggleProductLinksDisplayType();
  $(document).on('change', 'input[name="_wpz-insta_product-links-display-type"]', wpzInstaToggleProductLinksDisplayType);
  $('.wpzinsta-pointer').each(function () {
    $(this).parent().addBack().one('click', function (e) {
      e.stopPropagation();
      var $target = $(this);
      if ($(this).is('li')) {
        $target = $(this).find('.wpzinsta-pointer');
      }
      $target.remove();
    });
  });
  $('#post-body-content .wpz-insta_sidebar .wpz-insta_sidebar-left .wpz-insta_sidebar-left-section').on('scroll', function () {
    $(this).find('.wp-picker-holder').each(function () {
      var $parent = $(this).closest('.wp-picker-container');
      var parentOffset = $parent.offset();
      $(this).offset({
        top: parentOffset.top + $parent.outerHeight(),
        left: parentOffset.left
      });
    });
  }).triggerHandler('scroll');
  $(window).on('scroll', function () {
    $('#post-body-content .wpz-insta_sidebar .wpz-insta_sidebar-left .wpz-insta_sidebar-left-section').each(function () {
      $(this).triggerHandler('scroll');
    });
  });
  if ($('#post-body-content .wpz-insta_sidebar .wpz-insta_sidebar-left .wpz-insta_sidebar-left-section .wpz-insta_color-picker').length > 0) {
    var observer = new IntersectionObserver(function (es, os) {
      return es.forEach(function (en) {
        return en.target.blur();
      });
    }, {
      root: null,
      threshold: .1
    });
    observer.observe($('#post-body-content .wpz-insta_sidebar .wpz-insta_sidebar-left .wpz-insta_sidebar-left-section .wpz-insta_color-picker')[0]);
  }
  $('#wpz-insta_shortcode').on('focus', function (e) {
    e.preventDefault();
    $(this).select();
  });
  var wpzInstaShortcodeCopyTimer;
  $('#wpz-insta_shortcode-copy-btn').on('click', debounce(function () {
    window.wpzInstaCopyToClipboard($('#wpz-insta_shortcode').val()).then(function () {
      $('#wpz-insta_shortcode-copy-btn').addClass('success');
      clearTimeout(wpzInstaShortcodeCopyTimer);
      setTimeout(function () {
        $('#wpz-insta_shortcode-copy-btn').removeClass('success');
      }, 3000);
    });
  }, 300));
  $('.wpz-insta_actions-menu_copy-shortcode').on('click', function (e) {
    e.preventDefault();
    var id = $(this).closest('tr').attr('id').replace('post-', '');
    window.wpzInstaCopyToClipboard('[instagram feed="' + id + '"]').then(function () {
      window.wpzInstaShowDialog(zoom_instagram_widget_admin.i18n_shortcode_success_title, zoom_instagram_widget_admin.i18n_shortcode_success_content, 'success update');
    });
  });
  $('.wpz-insta_actions-menu_delete').on('click', function (e) {
    e.preventDefault();
    var isFeed = $(this).hasClass('wpz-insta_actions-menu_delete-feed'),
      href = $(this).find('a').attr('href');
    window.wpzInstaShowConfirmDialog(zoom_instagram_widget_admin['i18n_delete_' + (isFeed ? 'feed' : 'user') + '_confirm_title'], zoom_instagram_widget_admin['i18n_delete_' + (isFeed ? 'feed' : 'user') + '_confirm_content'], zoom_instagram_widget_admin['i18n_delete_confirm_button_ok'], zoom_instagram_widget_admin['i18n_delete_confirm_button_cancel']).then(function (result) {
      if (result === true) {
        window.location = href;
      }
      window.wpzInstaCloseDialog();
    });
  });
  function setTab(id) {
    if (id) {
      var $target = $('.wpz-insta_feed-edit-nav a[href="' + id + '"]'),
        $tabs = $target.closest('form').find('.wpz-insta_tabs-content .wpz-insta_sidebar-left-section');
      $target.closest('.wpz-insta_feed-edit-nav').find('li').removeClass('active');
      $target.closest('li').addClass('active');
      $tabs.removeClass('active');
      $tabs.filter('[data-id="' + id + '"]').addClass('active');

      // Notify iframe of tab change so it can show/hide "Link to a product" buttons without reload
      var iframe = document.querySelector('#wpz-insta_widget-preview-view iframe');
      if (iframe && iframe.contentWindow) {
        var tabId = (id + '').replace(/^#/, '');
        iframe.contentWindow.postMessage({
          action: 'wpz-insta-tab-change',
          tab: tabId
        }, '*');
      }
    }
  }
  window.wpzInstaAuthenticateInstagram = function (url, callback) {
    var popupWidth = 700,
      popupHeight = 750,
      popupTop = (window.screen.height - popupHeight) / 2,
      popupLeft = (window.screen.width - popupWidth) / 2;
    window.open(url, '', 'width=' + popupWidth + ',height=' + popupHeight + ',left=' + popupLeft + ',top=' + popupTop);
  };

  // Helper function to check if the current window is a popup
  function isLikelyPopup(minWidth, maxWidth, minHeight, maxHeight) {
    var windowWidth = window.outerWidth;
    var windowHeight = window.outerHeight;

    // Check if the window is smaller than the full screen
    var isSmallerThanScreen = windowWidth < window.screen.width && windowHeight < window.screen.height;

    // Check if the window size falls within the expected range
    var withinWidthRange = windowWidth >= minWidth && windowWidth <= maxWidth;
    var withinHeightRange = windowHeight >= minHeight && windowHeight <= maxHeight;
    return isSmallerThanScreen && withinWidthRange && withinHeightRange;
  }
  window.wpzInstaParseQuery = function (queryString) {
    var query = {};
    var pairs = (queryString[0] === '?' || queryString[0] === '#' ? queryString.substr(1) : queryString).split('&');
    for (var i = 0; i < pairs.length; i++) {
      var pair = pairs[i].split('=');
      query[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1] || '');
    }
    return query;
  };

  // Function to handle the returned token from graph API
  window.wpzInstaHandleReturnedGraphToken = function (url) {
    var rawToken = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
    if (url) {
      var parsedHash = !rawToken && 'hash' in url && '' != ('' + url.hash).trim() ? window.wpzInstaParseQuery('' + url.hash) : {};
      if (!rawToken && !$.isEmptyObject(parsedHash) || rawToken && '' != ('' + url).trim()) {
        var token = rawToken ? ('' + url).trim() : 'access_graph_token' in parsedHash ? ('' + parsedHash.access_graph_token).trim() : '-1';
        if ('' != token && '-1' != token) {
          var args = {
            action: 'wpz-insta_connect_business-user',
            nonce: zoom_instagram_widget_admin.nonce,
            token: token
          };
          if (!rawToken) {
            var parsedQuery = 'search' in url && '' != ('' + url.search).trim() ? window.wpzInstaParseQuery('' + url.search) : {};
            args.post_id = !$.isEmptyObject(parsedQuery) && 'post' in parsedQuery ? parseInt(parsedQuery.post) : 0;
          }
          $.post(ajaxurl, args).done(function (data, status, code) {
            if ('success' == status) {
              var getBusinessUsers = $(data);
              if (getBusinessUsers) {
                $('#wpz-insta_modal_graph-dialog').find('.wpz-insta_modal-dialog_content').html(getBusinessUsers);
                $('#wpz-insta_modal_graph-dialog').removeClass().addClass('open success');
                $('.wpz-insta_business-accounts-link').on('click', function (e) {
                  e.preventDefault();
                  // If the user is not a pro, remove the selected class from all links
                  if (!zoom_instagram_widget_admin.is_pro) {
                    $('.wpz-insta_business-accounts-link').removeClass('selected'); // Remove from all links
                    $(this).addClass('selected'); // Add to the clicked link
                  } else {
                    $(this).toggleClass('selected'); // Add to the clicked link
                  }
                  $('#wpz-insta-graph-connect-account').removeClass('disabled');
                });
              }
            }
          }).fail(function () {
            console.log('Failed to connect business user');
          });
        }
      }
    }
  };

  // Set the selected API
  $('#wpz-insta-select-api').on('change', function (e) {
    var selected = $(this).val();
    $(this).parent().find('#wpz-insta_reconnect').attr('href', selected);
  });
  $('#wpz-add_manual_token').on('click', function (e) {
    e.preventDefault();
    $('#wpz-insta-token_label').toggle();
  });

  // Function to connect the selected business account
  $('#wpz-insta-graph-connect-account').on('click', function (e) {
    e.preventDefault();
    var selected_accounts = [],
      post_id = $('.wpz-insta_business-accounts-link').parent().data('post-id');
    $('.wpz-insta_business-accounts-link').each(function () {
      if ($(this).hasClass('selected')) {
        selected_accounts.push($(this).data('account-info'));
      }
    });
    if (selected_accounts.length > 0) {
      var args = {
        action: 'wpz-insta_connect_business-account',
        nonce: zoom_instagram_widget_admin.nonce,
        account_info: JSON.stringify(selected_accounts),
        post_id: post_id
      };
      $.post(ajaxurl, args).done(function (data, status, code) {
        if ('success' == status) {
          $('#wpz-insta_modal_graph-dialog').removeClass('open');
        }
        window.location.replace(zoom_instagram_widget_admin.feeds_url);
      }).fail(function (data, status, code) {
        console.log(data);
      });
    }
  });
  window.wpzInstaShowConnectDoneDialog = function (success) {
    var update = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
    window.wpzInstaShowDialog(success ? update ? zoom_instagram_widget_admin.i18n_reconnect_success_title : zoom_instagram_widget_admin.i18n_connect_success_title : zoom_instagram_widget_admin.i18n_connect_fail_title, success ? update ? zoom_instagram_widget_admin.i18n_reconnect_success_content : zoom_instagram_widget_admin.i18n_connect_success_content : zoom_instagram_widget_admin.i18n_connect_fail_content, (success ? 'success' : 'fail') + (update ? ' update' : ''));
  };
  window.wpzInstaShowDialog = function () {
    var title = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '[DIALOG TITLE]';
    var content = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '[DIALOG CONTENT]';
    var wrapperClass = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 'success';
    var $dialog = $('#wpz-insta_modal-dialog'),
      $title = $dialog.find('.wpz-insta_modal-dialog_header-title'),
      $content = $dialog.find('.wpz-insta_modal-dialog_content'),
      $buttonOk = $dialog.find('.wpz-insta_modal-dialog_footer .wpz-insta_modal-dialog_ok-button'),
      $buttonCancel = $dialog.find('.wpz-insta_modal-dialog_footer .wpz-insta_modal-dialog_cancel-button');
    $title.html('' + title);
    $content.html('' + content);
    $buttonCancel.addClass('hidden');
    $dialog.removeClass().addClass('open ' + wrapperClass);
  };
  window.wpzInstaShowConfirmDialog = function () {
    var title = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '[DIALOG TITLE]';
    var content = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '[DIALOG CONTENT]';
    var okButtonLabel = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : '[OK]';
    var cancelButtonLabel = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : '[CANCEL]';
    return new Promise(function (resolve, reject) {
      var $dialog = $('#wpz-insta_modal-dialog'),
        $title = $dialog.find('.wpz-insta_modal-dialog_header-title'),
        $content = $dialog.find('.wpz-insta_modal-dialog_content'),
        $buttonOk = $dialog.find('.wpz-insta_modal-dialog_footer .wpz-insta_modal-dialog_ok-button'),
        $buttonCancel = $dialog.find('.wpz-insta_modal-dialog_footer .wpz-insta_modal-dialog_cancel-button');
      $title.html('' + title);
      $content.html('' + content);
      $buttonOk.removeClass('hidden').html('' + okButtonLabel);
      $buttonOk.on('click', function () {
        return resolve(true);
      });
      $buttonCancel.removeClass('hidden').html('' + cancelButtonLabel);
      $buttonCancel.on('click', function () {
        return resolve(false);
      });
      $dialog.removeClass().addClass('open confirm');
    });
  };
  window.wpzInstaCloseConnectDoneDialog = function (success) {
    var update = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
    window.wpzInstaCloseDialog();
    if (success && !update) {
      window.location.replace(zoom_instagram_widget_admin.feeds_url);
    }
  };
  window.wpzInstaCloseDialog = function () {
    $('#wpz-insta_modal-dialog').removeClass('open');
  };
  function debounce(func) {
    var _this = this;
    var timeout = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 300;
    var timer;
    return function () {
      for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
        args[_key] = arguments[_key];
      }
      clearTimeout(timer);
      timer = setTimeout(function () {
        func.apply(_this, args);
      }, timeout);
    };
  }
  window.wpzInstaCopyToClipboard = function (textToCopy) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(textToCopy);
    } else {
      var textArea = document.createElement('textarea');
      textArea.value = textToCopy;
      textArea.style.position = 'fixed';
      textArea.style.left = '-999999px';
      textArea.style.top = '-999999px';
      document.body.appendChild(textArea);
      textArea.focus();
      textArea.select();
      return new Promise(function (res, rej) {
        document.execCommand('copy') ? res() : rej();
        textArea.remove();
      });
    }
  };
  if ('inlineEditPost' in window) {
    $('.inline-edit-save').find('.button-primary').addClass('disabled');
    var origInlineEditPost = window.inlineEditPost.edit;
    window.inlineEditPost.edit = function (id) {
      origInlineEditPost.apply(this, arguments);
      if (_typeof(id) === 'object') {
        id = window.inlineEditPost.getId(id);
      }
      var fields = ['_wpz-insta_account-type', '_wpz-insta_token', '_wpz-insta_token_expire', '_thumbnail_id', 'wpz-insta_profile-photo', '_wpz-insta_user_name', '_wpz-insta_user-bio', '_wpz-insta_api-url'],
        rowData = $('#inline_' + id),
        editRow = $('#edit-' + id),
        reconnectBtn = $('#wpz-insta_reconnect', editRow),
        val,
        field;
      for (var i = 0; i < fields.length; i++) {
        field = fields[i];
        val = $('.' + field, rowData);
        val = val.text();
        if ('wpz-insta_profile-photo' == field) {
          $('img.' + field).attr('src', val);
        } else if ('_wpz-insta_token' == field) {
          $('#wpz-insta_token', editRow).val(val);
        } else if ('_wpz-insta_token_expire' == field) {
          $('#wpz-insta_token-expire-time', editRow).html(val);
        } else if ('_wpz-insta_api-url' == field) {
          $('#wpz-insta_reconnect', editRow).attr('href', val);
        } else {
          $(':input[name="' + field + '"]', editRow).val(val);
        }
        $(':input[name="' + field + '"]', editRow).on('change paste keyup', function () {
          $('.inline-edit-save', editRow).find('.button-primary').removeClass('disabled');
        });
      }

      // Update the RETURN_URL in the values of the options
      $('#wpz-insta-select-api option', editRow).each(function () {
        var newUrlPart = btoa(encodeURIComponent(zoom_instagram_widget_admin.post_edit_url + id));
        var currentValue = $(this).val();
        if (currentValue.includes('RETURN_URL')) {
          // Replace 'RETURN_URL' with the new return URL
          var updatedValue = currentValue.replace('RETURN_URL', encodeURIComponent(newUrlPart));
          $(this).val(updatedValue);
        }
      });
      reconnectBtn.attr({
        'href': reconnectBtn.attr('href').replace('RETURN_URL', btoa(encodeURIComponent(zoom_instagram_widget_admin.post_edit_url + id))),
        'data-user-id': id
      });
    };
  }
  window.wpzInstaHandleReturnedToken = function (url) {
    var rawToken = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
    if (url) {
      var parsedHash = !rawToken && 'hash' in url && '' != ('' + url.hash).trim() ? window.wpzInstaParseQuery('' + url.hash) : {};
      if (!rawToken && !$.isEmptyObject(parsedHash) || rawToken && '' != ('' + url).trim()) {
        var token = rawToken ? ('' + url).trim() : 'access_token' in parsedHash ? ('' + parsedHash.access_token).trim() : '-1';
        if ('' != token && '-1' != token) {
          var args = {
            action: 'wpz-insta_connect-user',
            nonce: zoom_instagram_widget_admin.nonce,
            token: token
          };
          if (!rawToken) {
            var parsedQuery = 'search' in url && '' != ('' + url.search).trim() ? window.wpzInstaParseQuery('' + url.search) : {};
            args.post_id = !$.isEmptyObject(parsedQuery) && 'post' in parsedQuery ? parseInt(parsedQuery.post) : 0;
          }
          $.post(ajaxurl, args).done(function (response) {
            $('.inline-edit-wpz-insta_user #wpz-insta_token').val(token);
            var date = new Date();
            date.setDate(date.getDate() + 60);
            $('#the-list #wpz-insta_token-expire-time').html(date.toLocaleDateString('en-US', {
              weekday: 'long',
              day: 'numeric',
              month: 'long',
              year: 'numeric'
            }));
            window.wpzInstaShowConnectDoneDialog(response.success, 'data' in response && 'update' in response.data && response.data.update);
          }).fail(function () {
            window.wpzInstaShowConnectDoneDialog(false);
          });
        }
      }
    }
  };

  // Fields that require a full iframe reload (server-side: different data). All others are applied via postMessage.
  var wpzInstaPreviewReloadKeys = ['_wpz-insta_user-id', '_wpz-insta_item-num', '_wpz-insta_allowed-post-types-submitted'];
  function wpzInstaCollectPreviewState() {
    var $form = $('form#post .wpz-insta_tabs-content > .wpz-insta_sidebar > .wpz-insta_sidebar-left');
    var state = {};
    $('form#post #title').add($form.find('input, textarea, select').not('.preview-exclude')).each(function () {
      var $el = $(this);
      var name = $el.attr('name');
      if (!name || name === 'post_ID') return;
      var key = name.replace(/^_wpz-insta_/, '').replace(/\[\]$/, '');
      var val;
      if ($el.is(':radio, :checkbox')) {
        if (name.indexOf('[]') !== -1) {
          val = $('form#post').find('input[name="' + name + '"]:checked').map(function () {
            return $(this).val();
          }).get().join(',');
        } else if ($el.is(':radio')) {
          // Radio group: use the checked value so layout/featured-layout etc. are correct
          var $checked = $('form#post').find('input[name="' + name + '"]:checked');
          val = $checked.length ? $checked.val() || '0' : '0';
        } else {
          val = $el.is(':checked') ? $el.val() || '1' : '0';
        }
      } else {
        val = $el.val();
      }
      state[key] = val;
    });
    return state;
  }
  function wpzInstaSendPreviewUpdate() {
    var iframe = document.querySelector('#wpz-insta_widget-preview-view iframe');
    if (!iframe || !iframe.contentWindow) return;
    var state = wpzInstaCollectPreviewState();
    iframe.contentWindow.postMessage({
      action: 'wpz-insta-preview-update',
      data: state
    }, '*');
  }

  /**
   * Build the preview iframe URL (optionally with refresh param to bypass cache).
   *
   * @param {boolean} [withRefresh=false] If true, add wpz-insta-preview-refresh=1 to fetch fresh data from Instagram.
   * @returns {string} Preview URL.
   */
  function wpzInstaBuildPreviewUrl(withRefresh) {
    var url = zoom_instagram_widget_admin.preview_url;
    var postId = $('form#post input[name="post_ID"]').val();
    if (postId) {
      url += '&wpz-insta-feed-id=' + encodeURIComponent(postId);
    }
    var activeTab = $('.wpz-insta_feed-edit-nav li.active a').attr('href');
    if (activeTab) {
      url += '&wpz-insta-tab=' + encodeURIComponent((activeTab + '').replace(/^#/, ''));
    }
    var itemNum = $('form#post input[name="_wpz-insta_item-num"]').val();
    if (itemNum != null && itemNum !== '') {
      url += '&_wpz-insta_item-num=' + encodeURIComponent(itemNum);
    }
    var userId = $('form#post input[name="_wpz-insta_user-id"]').val();
    if (userId != null && userId !== '') {
      url += '&_wpz-insta_user-id=' + encodeURIComponent(userId);
    }
    var allowedTypes = $('form#post input[name="_wpz-insta_allowed-post-types[]"]:checked').map(function () {
      return $(this).val();
    }).get().join(',');
    if (allowedTypes) {
      url += '&_wpz-insta_allowed-post-types=' + encodeURIComponent(allowedTypes);
    }
    if (withRefresh) {
      url += '&wpz-insta-preview-refresh=1';
    }
    return url;
  }
  window.wpzInstaReloadPreview = function (withRefresh) {
    var url = wpzInstaBuildPreviewUrl(!!withRefresh);
    $('#wpz-insta_widget-preview-view').closest('.wpz-insta_sidebar-right').removeClass('hide-loading');
    $('#wpz-insta_widget-preview-view iframe').addClass('wpz-insta_preview-hidden').attr('src', url);
  };
  $('.wpz-insta_widget-preview-refresh').on('click', function () {
    window.wpzInstaReloadPreview(true);
  });
  window.wpzInstaUpdatePreviewHeight = function () {
    var $frame = $('#wpz-insta_widget-preview-view iframe');
    $frame.height(parseInt($frame.contents().find('body').prop('scrollHeight')));
  };

  // WooCommerce Product Link functionality
  if (typeof zoom_instagram_widget_admin !== 'undefined') {
    // Selected product IDs (persists across pagination)
    var initProductLinkModal = function initProductLinkModal() {
      if ($productLinkModal) {
        return;
      }
      var strings = zoom_instagram_widget_admin.strings || {};
      var modalHTML = '<div id="wpz-insta-product-link-modal" class="wpz-insta-modal" style="display: none;">' + '<div class="wpz-insta-modal-content">' + '<div class="wpz-insta-modal-header">' + '<h2>' + (strings.linkProduct || 'Link to Product') + '</h2>' + '<button type="button" class="wpz-insta-modal-close">&times;</button>' + '</div>' + '<div class="wpz-insta-modal-body">' + '<p class="wpz-insta-product-link-hint">' + (strings.selectProductsHint || 'Select one or more products. Click an item to toggle selection.') + '</p>' + '<div class="wpz-insta-product-search">' + '<input type="text" id="wpz-insta-product-search-input" placeholder="' + (strings.searchProducts || 'Search products...') + '" />' + '</div>' + '<div class="wpz-insta-product-list" id="wpz-insta-product-list"></div>' + '<div class="wpz-insta-product-pagination"></div>' + '</div>' + '<div class="wpz-insta-modal-footer">' + '<button type="button" class="button wpz-insta-remove-link" style="display: none;">' + (strings.removeLink || 'Remove Link') + '</button>' + '<button type="button" class="button button-primary wpz-insta-save-link" disabled>' + (strings.save || 'Save') + '</button>' + '<button type="button" class="button wpz-insta-cancel-link">' + (strings.cancel || 'Cancel') + '</button>' + '</div>' + '</div>' + '</div>';
      $('body').append(modalHTML);
      $productLinkModal = $('#wpz-insta-product-link-modal');

      // Close modal handlers
      $productLinkModal.on('click', '.wpz-insta-modal-close, .wpz-insta-cancel-link', function () {
        $productLinkModal.hide();
      });

      // Remove link handler
      $productLinkModal.on('click', '.wpz-insta-remove-link', function () {
        saveProductLink([]);
      });

      // Save link handler
      $productLinkModal.on('click', '.wpz-insta-save-link', function () {
        var ids = currentSelectedIds.slice();
        saveProductLink(ids);
      });

      // Product item click: toggle selection (multi-select)
      $productLinkModal.on('click', '.wpz-insta-product-item', function () {
        var id = parseInt($(this).data('product-id'), 10);
        var idx = currentSelectedIds.indexOf(id);
        if (idx === -1) {
          currentSelectedIds.push(id);
          $(this).addClass('selected');
        } else {
          currentSelectedIds.splice(idx, 1);
          $(this).removeClass('selected');
        }
        $('.wpz-insta-save-link').prop('disabled', currentSelectedIds.length === 0);
        $('.wpz-insta-remove-link').toggle(currentSelectedIds.length > 0);
      });

      // Search handler
      var searchTimeout;
      $('#wpz-insta-product-search-input').on('input', function () {
        clearTimeout(searchTimeout);
        var search = $(this).val();
        searchTimeout = setTimeout(function () {
          loadProducts(search, 1);
        }, 500);
      });
    };
    var loadProducts = function loadProducts(search, page) {
      page = page || 1;
      $('#wpz-insta-product-list').html('<div class="wpz-insta-loading">Loading...</div>');
      $.ajax({
        url: zoom_instagram_widget_admin.ajax_url,
        type: 'POST',
        data: {
          action: 'wpz-insta_get-products',
          nonce: zoom_instagram_widget_admin.product_link_nonce,
          search: search || '',
          page: page
        },
        success: function success(response) {
          if (response.success && response.data.products) {
            displayProducts(response.data.products, response.data.pages, page);
          } else {
            $('#wpz-insta-product-list').html('<div class="wpz-insta-no-products">No products found.</div>');
          }
        },
        error: function error() {
          $('#wpz-insta-product-list').html('<div class="wpz-insta-error">Error loading products.</div>');
        }
      });
    };
    var displayProducts = function displayProducts(products, totalPages, currentPage) {
      var html = '';
      if (products.length === 0) {
        html = '<div class="wpz-insta-no-products">No products found.</div>';
      } else {
        products.forEach(function (product) {
          var isSelected = currentSelectedIds.indexOf(parseInt(product.id, 10)) !== -1;
          var selectedClass = isSelected ? ' selected' : '';
          html += '<div class="wpz-insta-product-item' + selectedClass + '" data-product-id="' + product.id + '">' + '<span class="wpz-insta-product-item-checkbox" aria-hidden="true"></span>' + '<img src="' + product.image + '" alt="" />' + '<div class="wpz-insta-product-info">' + '<h4>' + product.title + '</h4>' + '<span class="wpz-insta-product-price">' + product.price + '</span>' + '</div>' + '</div>';
        });
      }
      $('#wpz-insta-product-list').html(html);

      // Pagination
      if (totalPages > 1) {
        var paginationHTML = '';
        if (currentPage > 1) {
          paginationHTML += '<button type="button" class="button wpz-insta-prev-page" data-page="' + (currentPage - 1) + '">Previous</button>';
        }
        if (currentPage < totalPages) {
          paginationHTML += '<button type="button" class="button wpz-insta-next-page" data-page="' + (currentPage + 1) + '">Next</button>';
        }
        $('.wpz-insta-product-pagination').html(paginationHTML);
      } else {
        $('.wpz-insta-product-pagination').html('');
      }
      $('.wpz-insta-save-link').prop('disabled', currentSelectedIds.length === 0);
      $('.wpz-insta-remove-link').toggle(currentSelectedIds.length > 0);
    };
    var saveProductLink = function saveProductLink(productIds) {
      productIds = Array.isArray(productIds) ? productIds : productIds ? [productIds] : [];
      $.ajax({
        url: zoom_instagram_widget_admin.ajax_url,
        type: 'POST',
        data: {
          action: 'wpz-insta_save-product-link',
          nonce: zoom_instagram_widget_admin.product_link_nonce,
          feed_id: currentFeedId,
          media_id: currentMediaId,
          product_ids: productIds
        },
        success: function success(response) {
          if (response.success) {
            $productLinkModal.hide();
            if (typeof window.wpzInstaReloadPreview === 'function') {
              window.wpzInstaReloadPreview();
            }
          } else {
            alert(response.data && response.data.message ? response.data.message : 'Error saving product link(s).');
          }
        },
        error: function error() {
          alert('Error saving product link(s).');
        }
      });
    }; // Product Links upsell modal (shown when no valid license)
    var initProductLinksUpsellModal = function initProductLinksUpsellModal() {
      if ($productLinksUpsellModal) {
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
      var upsellHTML = '<div id="wpz-insta-product-links-upsell-modal" class="wpz-insta-modal wpz-insta-upsell-modal" style="display: none;">' + '<div class="wpz-insta-modal-content wpz-insta-upsell-content">' + '<div class="wpz-insta-modal-header">' + '<h2>' + upsellTitle + '</h2>' + '<button type="button" class="wpz-insta-modal-close">&times;</button>' + '</div>' + '<div class="wpz-insta-modal-body">' + upsellImageHTML + '<p class="wpz-insta-upsell-message">' + upsellMessage + '</p>' + '<div class="wpz-insta-upsell-actions">' + '<a href="' + upsellUrl + '" target="_blank" rel="noopener" class="button button-primary wpz-insta-upsell-cta">' + upsellCta + '</a>' + '</div>' + '</div>' + '<div class="wpz-insta-modal-footer">' + '<button type="button" class="button wpz-insta-upsell-close">' + upsellClose + '</button>' + '</div>' + '</div></div>';
      $('body').append(upsellHTML);
      $productLinksUpsellModal = $('#wpz-insta-product-links-upsell-modal');
      $productLinksUpsellModal.on('click', '.wpz-insta-modal-close, .wpz-insta-upsell-close', function () {
        $productLinksUpsellModal.hide();
      });
    }; // Open product link modal when iframe sends message (button is inside iframe)
    // Product link popup (multi-select)
    var $productLinkModal = null;
    var currentMediaId = null;
    var currentFeedId = null;
    var currentSelectedIds = [];
    var $productLinksUpsellModal = null;
    window.addEventListener('message', function (e) {
      if (!e.data || e.data.action !== 'wpz-insta-open-product-link') {
        return;
      }
      // If no valid license (not PRO), show upsell modal instead of product selection
      if (!zoom_instagram_widget_admin.is_pro) {
        initProductLinksUpsellModal();
        $productLinksUpsellModal.show();
        return;
      }
      currentMediaId = e.data.mediaId || '';
      currentFeedId = e.data.feedId || '';
      initProductLinkModal();
      $productLinkModal.show();
      $('#wpz-insta-product-list').html('<div class="wpz-insta-loading">Loading...</div>');
      $('.wpz-insta-save-link').prop('disabled', true);

      // Fetch current linked product IDs (supports multi-select)
      $.ajax({
        url: zoom_instagram_widget_admin.ajax_url,
        type: 'POST',
        data: {
          action: 'wpz-insta_get-product-link',
          nonce: zoom_instagram_widget_admin.product_link_nonce,
          feed_id: currentFeedId,
          media_id: currentMediaId
        },
        success: function success(response) {
          if (response.success && response.data && response.data.product_ids && Array.isArray(response.data.product_ids)) {
            currentSelectedIds = response.data.product_ids.slice();
          } else {
            currentSelectedIds = [];
          }
          $('.wpz-insta-remove-link').toggle(currentSelectedIds.length > 0);
          loadProducts('', 1);
        },
        error: function error() {
          currentSelectedIds = [];
          $('.wpz-insta-remove-link').hide();
          loadProducts('', 1);
        }
      });
    });

    // Pagination handlers (preserve currentSelectedIds)
    $(document).on('click', '.wpz-insta-prev-page, .wpz-insta-next-page', function () {
      var page = $(this).data('page');
      var search = $('#wpz-insta-product-search-input').val();
      loadProducts(search, page);
    });
  }
});
/******/ })()
;
//# sourceMappingURL=index.js.map