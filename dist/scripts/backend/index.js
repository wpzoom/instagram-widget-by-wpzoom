!function(t){var e={};function n(i){if(e[i])return e[i].exports;var a=e[i]={i:i,l:!1,exports:{}};return t[i].call(a.exports,a,a.exports,n),a.l=!0,a.exports}n.m=t,n.c=e,n.d=function(t,e,i){n.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:i})},n.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},n.t=function(t,e){if(1&e&&(t=n(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var i=Object.create(null);if(n.r(i),Object.defineProperty(i,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var a in t)n.d(i,a,function(e){return t[e]}.bind(null,a));return i},n.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return n.d(e,"a",e),e},n.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},n.p="",n(n.s=13)}({13:function(t,e,n){"use strict";function i(t){return(i="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t})(t)}jQuery((function(t){var e,n,a,s=t();if(t.fn.imageMediaControl=function(){this.each((function(){var e=t(this),n={frame:function(){return this._frame||(this._frame=wp.media({title:e.data("title"),library:{type:e.data("type")},button:{text:e.data("button")},multiple:!1,selection:[]}),this._frame.on("open",this.updateFrame).state("library").on("select",this.select)),this._frame},select:function(){var t,n,i=e.find(".attachment-input").add(s.find("input#wpz-insta_account-photo")),a=this.get("selection"),o=a.pluck("id");i.val(""+o).trigger("change");var r=a.first().toJSON(),d=null!==(t=null===(n=r.sizes.thumbnail)||void 0===n?void 0:n.url)&&void 0!==t?t:r.sizes.full.url;s.find("img.wpz-insta_profile-photo").attr("src",""+d)},updateFrame:function(){},init:function(){var i=e.find(".file-wrapper"),a=e.find(".attachment-input, #wpz-insta_account-photo"),o=e.find(".add-media, #wpz-insta_edit-account-photo"),r=e.find(".remove-avatar, #wpz-insta_reset-account-photo");o.on("click",(function(e){e.preventDefault(),s=t(this).closest(".wpz-insta_account-photo-wrapper"),n.frame().open()})),r.on("click",(function(e){e.preventDefault(),t("#the-list input.wpz-insta_profile-photo-input").val("-1").trigger("change"),t("#the-list img.wpz-insta_profile-photo").attr("src",zoom_instagram_widget_admin.default_user_thumbnail)})),a.on("change",(function(n){n.preventDefault();var a=e.find(".attachment-input").val();a?(o.text(e.data("button-replace-text")),r.show(),wp.media.attachment(a).fetch().then((function(e){i.fadeOut(400,(function(){var n=e.url;_.findKey(e,"thumbnail")&&(n=e.sizes.thumbnail.url),t(this).html('<img width="150" height="150" src="'+n+'"/>').fadeIn(400)}))}))):(i.hide(),r.hide(),o.text(e.data("button-add-text")))})).trigger("change")}};n.frame().on("open",(function(){var t=e.find(".attachment-input, #wpz-insta_account-photo"),i=n.frame(),a=i.state().get("selection"),s=t.val(),o=wp.media.attachment(s);i.reset(),o.id&&a.add(o)})),n.init()}))},t(".zoom-instagram-user-avatar-media-uploader, .inline-edit-wpz-insta_user .wpz-insta_quick-edit-columns .wpz-insta_two-columns").imageMediaControl(),t("#wpzoom_instagram_clear_data").on("click",(function(e){e.preventDefault();var n={action:"wpzoom_instagram_clear_data",nonce:t(this).data("nonce")},i=t(this);window.confirm("Are you sure?")&&(i.text("Removing data..."),t.post(zoom_instagram_widget_admin.ajax_url,n,(function(t){t.success&&(i.text("Done!"),i.prop("disabled",!0),i.next().html(t.data.message))})))})),t(window).on("beforeunload",(function(e){t.isEmptyObject(l)||p||e.preventDefault()})),t("#the-list").on("click","#wpz-insta_reconnect",(function(e){e.preventDefault(),t(this).attr("href").length>0&&window.wpzInstaAuthenticateInstagram(t(this).attr("href"))})),t(".wpzoom-instagram-widget-settings-request-type-wrapper").find("input[type=radio]").on("change",(function(e){e.preventDefault();var n=t(this).val(),i=["with-access-token","with-basic-access-token","without-access-token"].filter((function(t){return t!==n})),a=t(this).closest(".form-table");a.find(".wpzoom-instagram-widget-"+n+"-group").show(),i.forEach((function(t){a.find(".wpzoom-instagram-widget-"+t+"-group").hide()}))})),t(".wpzoom-instagram-widget-settings-request-type-wrapper").find("input[type=radio]:checked").change(),setTimeout((function(){if("hash"in window.location&&""!=(""+window.location.hash).trim()){var e=t(".edit-php.post-type-wpz-insta_user #the-list").find((""+window.location.hash).trim());e.length>0&&e.find("button.editinline").trigger("click")}}),100),window.opener&&window.location.hash.length>1||(700,960,750,1200,e=window.outerWidth,n=window.outerHeight,e<window.screen.width&&n<window.screen.height&&(e>=700&&e<=960)&&(n>=750&&n<=1200)&&window.location.hash.length>1)){if(window.opener&&"function"==typeof window.opener.wpzInstaHandleReturnedToken)window.opener.wpzInstaHandleReturnedToken(window.location),window.close();else{var o=(window.location,(a=window.location.href.split("#")[1])&&a.includes("access_token=")?new URLSearchParams(a).get("access_token"):null),r=t("#wpz-insta_modal-dialog-connection-failed");t("#wpz_generated_token").text(o),r.addClass("open"),t("#wpfooter").show()}window.location.hash.includes("access_graph_token")&&(window.opener.wpzInstaHandleReturnedGraphToken(window.location),window.close())}t("#screen-meta #wpz-insta_account-photo-hide, #screen-meta #wpz-insta_account-bio-hide, #screen-meta #wpz-insta_account-token-hide, #screen-meta #wpz-insta_actions-hide").closest("label").remove(),t("#titlediv").remove(),t("#title").length>0&&(t("#title").attr("size",t("#title").val().trim().length+3),t("#title").on("input",(function(){t(this).attr("size",t(this).val().trim().length+3)}))),t(".wpz-insta_feed-edit-nav").length>0&&(window.location.hash&&w(window.location.hash),t(".wpz-insta_feed-edit-nav a").on("click",(function(){w(t(this).attr("href"))}))),t("#wpz-insta_show-pro").on("change",(function(e){e.preventDefault(),t(this).closest(".wpz-insta_sidebar").toggleClass("show-pro",this.checked)})),t("#wpz-insta_connect-personal, #wpz-insta_connect-business, .wpz-insta_tabs-config-connect-add").each((function(){var e=t(this).attr("href");if(e){var n=btoa(encodeURIComponent(zoom_instagram_widget_admin.feeds_url)),i=e.replace("RETURN_URL",n);t(this).attr("href",i)}})),t(".wpz-insta-wrap .account-options .account-option-button").on("click",(function(e){e.preventDefault(),t(this).is(".disabled")||(t(this).is("#wpz-insta_connect-personal")||t(this).is("#wpz-insta_connect-business")?window.wpzInstaAuthenticateInstagram(t(this).attr("href")):t(this).is("#wpz-insta_account-token-button")?window.wpzInstaHandleReturnedGraphToken(t("#wpz-insta_account-token-input").val().trim().replace(/[^a-z0-9-_.]+/gi,""),!0):t(this).is("#wpz-insta-biz_account-token-button")&&window.wpzInstaHandleReturnedToken(t("#wpz-insta_biz_account-token-input").val().trim().replace(/[^a-z0-9-_.]+/gi,""),!0))})),t("#wpz-insta_account-token-input").on("input",(function(){t("#wpz-insta_account-token-button").toggleClass("disabled",t("#wpz-insta_account-token-input").val().trim().length<=0)})),t("#wpz-insta_biz_account-token-input").on("input",(function(){t("#wpz-insta-biz_account-token-button").toggleClass("disabled",t("#wpz-insta_biz_account-token-input").val().trim().length<=0)})),t('.wpz-insta_sidebar-section-layout input[name="_wpz-insta_layout"]').on("change",(function(){var e=t(this).closest(".wpz-insta_sidebar-section-layout").find('input[name="_wpz-insta_col-num"]').closest(".wpz-insta_table-row"),n=t(this).closest(".wpz-insta_sidebar-section-layout").find('input[name="_wpz-insta_perpage-num"]').closest(".wpz-insta_table-row"),i=t(this).closest(".wpz-insta_sidebar-section-layout").find(".wpz-insta_table-row-featured-layout"),a=i.closest(".wpz-insta_feed-only-pro"),s=t(this).closest(".wpz-insta_sidebar-left-section"),o=s.find(".wpz-insta_sidebar-section-feed .wpz-insta_show-on-hover fieldset.wpz-insta_feed-only-pro.wpz-insta_pro-only"),r=s.find(".wpz-insta_sidebar-section-load-more");e.add(o).add(r).toggleClass("hidden","1"==t(this).val()||"3"==t(this).val()),t(".wpz-insta-admin .wpz-insta_widget-preview .wpz-insta_widget-preview-view").toggleClass("layout-fullwidth","1"==t(this).val()),i.toggleClass("hidden","0"!=t(this).val()),t(".wpz-insta_sidebar .wpz-insta_sidebar-left").hasClass(".is-pro")||a.toggleClass("hidden","0"!=t(this).val()),n.toggleClass("hidden","3"!=t(this).val())})),t('.wpz-insta_sidebar-section-layout input[name="_wpz-insta_col-num"]').on("input",(function(){if("0"==t('.wpz-insta_sidebar-section-layout input[name="_wpz-insta_layout"]:checked').val()){var e=parseInt(t(this).closest(".wpz-insta_sidebar-section-layout").find('input[name="_wpz-insta_col-num"]').val()),n=t(this).closest(".wpz-insta_table").find("label.featured-layout"),i=n.closest(".wpz-insta_table-row");e<3||e>6?i.addClass("hidden"):(i.removeClass("hidden"),n.addClass("hidden"),n.each((function(){t(this).is(".featured-layout-columns_"+e)&&t(this).removeClass("hidden")})))}})),t('.wpz-insta_sidebar-section-layout input[name="_wpz-insta_col-num_responsive-enabled"]').on("change",(function(){t(this).closest(".wpz-insta_responsive-table-row").toggleClass("wpz-insta_responsive-enabled",t(this).is(":checked"))})),t('.wpz-insta_sidebar-section-layout input[name="_wpz-insta_perpage-num_responsive-enabled"]').on("change",(function(){t(this).closest(".wpz-insta_responsive-table-row").toggleClass("wpz-insta_responsive-enabled",t(this).is(":checked"))})),t("#_wpz-insta_featured-layout-enable").on("change",(function(){t(this).closest(".wpz-insta_table-row").find(".wpz-insta_image-select").toggleClass("hidden",!t(this).is(":checked"))})),t("#wpz-insta_modal-dialog").find(".wpz-insta_modal-dialog_ok-button, .wpz-insta_modal-dialog_close-button").on("click",(function(e){e.preventDefault();var n=t("#wpz-insta_modal-dialog");window.wpzInstaCloseConnectDoneDialog(n.hasClass("success"),n.hasClass("update"))})),t("#wpz-insta_modal_graph-dialog").find(".wpz-insta_modal-dialog_ok-button, .wpz-insta_modal-dialog_close-button").on("click",(function(e){e.preventDefault(),t("#wpz-insta_modal_graph-dialog").removeClass("open")})),t("#wpz-insta_modal-dialog-connection-failed").find(".wpz-insta_modal-dialog_ok-button, .wpz-insta_modal-dialog_close-button").on("click",(function(e){e.preventDefault();var n=t("#wpz-insta_modal-dialog-connection-failed");window.close(),n.removeClass("open")})),t("#wpz-insta_feed-user-select-btn").on("click",(function(e){e.preventDefault(),t("#wpz-insta_tabs-config-cnnct").removeClass("active").addClass("active").prev(".wpz-insta_sidebar").removeClass("active"),t("#wpz-insta_tabs-config-cnnct").closest(".wpz-insta_tabs-content").find("> .wpz-insta_sidebar").addClass("hide")})),t("#wpz-insta_feed-user-remove-btn").on("click",(function(e){e.preventDefault();var n=t("#wpz-insta_feed-user-select-btn").closest(".wpz-insta_feed-user-select"),i=n.find(".wpz-insta_feed-user-select-info");t("#wpz-insta_user-id").val("-1").trigger("change"),t("#wpz-insta_user-token").val("-1").trigger("change"),t("#wpz-insta_user-token, #wpz-insta_check-new-posts-interval-number, #wpz-insta_enable-request-timeout").closest(".wpz-insta_sidebar-section").removeClass("active"),t("#wpz-insta_widget-preview-links").addClass("disabled"),n.removeClass("is-set"),i.find(".wpz-insta_feed-user-select-info-name").html("None"),i.find(".wpz-insta_feed-user-select-info-type").html("None"),n.closest(".wrap").find(".wpz-insta_settings-header .wpz-insta_feed-edit-nav li:not(:first-child)").addClass("disable")})),t("#wpz-insta_tabs-config-cnnct .wpz-insta_tabs-config-connect-accounts li").on("click",(function(e){e.preventDefault();var n=t("#wpz-insta_feed-user-select-btn").closest(".wpz-insta_feed-user-select"),i=n.find(".wpz-insta_feed-user-select-info");t("#wpz-insta_user-id").val(t(this).data("user-id")).trigger("change"),t("#wpz-insta_user-token").val(t(this).data("user-token")).trigger("change"),t("#wpz-insta_user-token, #wpz-insta_check-new-posts-interval-number, #wpz-insta_enable-request-timeout").closest(".wpz-insta_sidebar-section").addClass("active"),n.addClass("is-set"),i.find(".wpz-insta_feed-user-select-info-name").html(t(this).data("user-name")),i.find(".wpz-insta_feed-user-select-info-type").html(t(this).data("user-type")),n.closest(".wrap").find(".wpz-insta_settings-header .wpz-insta_feed-edit-nav li").removeClass("disable"),n.find(".wpz-insta_feed-user-select-edit-link").attr("href",zoom_instagram_widget_admin.edit_user_url+t(this).data("user-id")),t("#wpz-insta_widget-preview-links").removeClass("disabled"),t("#wpz-insta_tabs-config-cnnct").removeClass("active").prev(".wpz-insta_sidebar").addClass("active"),t("#wpz-insta_tabs-config-cnnct").closest(".wpz-insta_tabs-content").find("> .wpz-insta_sidebar").removeClass("hide")}));var d={},c={},l={},p=!1;function w(e){if(e){var n=t('.wpz-insta_feed-edit-nav a[href="'+e+'"]'),i=n.closest("form").find(".wpz-insta_tabs-content .wpz-insta_sidebar-left-section");n.closest(".wpz-insta_feed-edit-nav").find("li").removeClass("active"),n.closest("li").addClass("active"),i.removeClass("active"),i.filter('[data-id="'+e+'"]').addClass("active")}}function u(t){var e,n=this,i=arguments.length>1&&void 0!==arguments[1]?arguments[1]:300;return function(){for(var a=arguments.length,s=new Array(a),o=0;o<a;o++)s[o]=arguments[o];clearTimeout(e),e=setTimeout((function(){t.apply(n,s)}),i)}}if(t("form#post .wpz-insta_tabs-content > .wpz-insta_sidebar > .wpz-insta_sidebar-left").find("input, textarea, select").add("form#post #title").filter("[name][name!='']").not(".preview-exclude").each((function(e){t(this).is(":radio")?t(this).is(":checked")&&(d[t.trim(t(this).attr("name"))]=t(this)):d[t.trim(t(this).attr("name"))]=t(this)})),t.each(d,(function(e,n){c[e]=n.is(":checkbox")?n.is(":checked")?"1":"0":t.trim(""+n.val())})),t("form#post").on("submit",(function(){return p=!0})),t("form#post #title, form#post .wpz-insta_tabs-content > .wpz-insta_sidebar > .wpz-insta_sidebar-left").on("input change",u((function(e){var n=t(e.target);if(!n.is(".preview-exclude")){var i=n.attr("name"),a=n.is(":checkbox")?n.is(":checked")?"1":"0":t.trim(""+n.val());i in c&&a!=c[i]?i in l||(l[i]=!0):i in l&&delete l[i],t("input#publish").toggleClass("disabled",t.isEmptyObject(l)),"post_title"!==i&&window.wpzInstaReloadPreview()}}),300)),t((function(){window.wpzInstaReloadPreview()})),t("#wpz-insta_widget-preview-links .wpz-insta_widget-preview-header-link").on("click",(function(){t(this).hasClass("active")||(t(this).addClass("active").siblings(".wpz-insta_widget-preview-header-link").removeClass("active"),t(this).closest(".wpz-insta_widget-preview").find(".wpz-insta_widget-preview-view").removeClass("wpz-insta_widget-preview-size-desktop wpz-insta_widget-preview-size-tablet wpz-insta_widget-preview-size-mobile").addClass(t(this).hasClass("wpz-insta_widget-preview-header-links-tablet")?"wpz-insta_widget-preview-size-tablet":t(this).hasClass("wpz-insta_widget-preview-header-links-mobile")?"wpz-insta_widget-preview-size-mobile":"wpz-insta_widget-preview-size-desktop"))})),t("#wpz-insta_widget-preview-view").on("transitionend",(function(){var e=t(this).find("iframe");e.height(parseInt(e.contents().find("body").prop("scrollHeight"))+20)})),t("#wpz-insta_widget-preview-view iframe").on("load",(function(){t(this).removeClass("wpz-insta_preview-hidden"),t(this).closest(".wpz-insta_sidebar-right").addClass("hide-loading")})),t(".wpz-insta_color-picker").wpColorPicker({change:function(e,n){var i=t.Event("change");i.target=e.target,t(e.target).closest("form#post").find(".wpz-insta_tabs-content > .wpz-insta_sidebar > .wpz-insta_sidebar-left").triggerHandler(i)}}),t(".wpzinsta-pointer").each((function(){t(this).parent().addBack().one("click",(function(e){e.stopPropagation();var n=t(this);t(this).is("li")&&(n=t(this).find(".wpzinsta-pointer")),n.remove()}))})),t("#post-body-content .wpz-insta_sidebar .wpz-insta_sidebar-left .wpz-insta_sidebar-left-section").on("scroll",(function(){t(this).find(".wp-picker-holder").each((function(){var e=t(this).closest(".wp-picker-container"),n=e.offset();t(this).offset({top:n.top+e.outerHeight(),left:n.left})}))})).triggerHandler("scroll"),t(window).on("scroll",(function(){t("#post-body-content .wpz-insta_sidebar .wpz-insta_sidebar-left .wpz-insta_sidebar-left-section").each((function(){t(this).triggerHandler("scroll")}))})),t("#post-body-content .wpz-insta_sidebar .wpz-insta_sidebar-left .wpz-insta_sidebar-left-section .wpz-insta_color-picker").length>0&&new IntersectionObserver((function(t,e){return t.forEach((function(t){return t.target.blur()}))}),{root:null,threshold:.1}).observe(t("#post-body-content .wpz-insta_sidebar .wpz-insta_sidebar-left .wpz-insta_sidebar-left-section .wpz-insta_color-picker")[0]),t("#wpz-insta_shortcode").on("focus",(function(e){e.preventDefault(),t(this).select()})),t("#wpz-insta_shortcode-copy-btn").on("click",u((function(){window.wpzInstaCopyToClipboard(t("#wpz-insta_shortcode").val()).then((function(){t("#wpz-insta_shortcode-copy-btn").addClass("success"),clearTimeout(void 0),setTimeout((function(){t("#wpz-insta_shortcode-copy-btn").removeClass("success")}),3e3)}))}),300)),t(".wpz-insta_actions-menu_copy-shortcode").on("click",(function(e){e.preventDefault();var n=t(this).closest("tr").attr("id").replace("post-","");window.wpzInstaCopyToClipboard('[instagram feed="'+n+'"]').then((function(){window.wpzInstaShowDialog(zoom_instagram_widget_admin.i18n_shortcode_success_title,zoom_instagram_widget_admin.i18n_shortcode_success_content,"success update")}))})),t(".wpz-insta_actions-menu_delete").on("click",(function(e){e.preventDefault();var n=t(this).hasClass("wpz-insta_actions-menu_delete-feed"),i=t(this).find("a").attr("href");window.wpzInstaShowConfirmDialog(zoom_instagram_widget_admin["i18n_delete_"+(n?"feed":"user")+"_confirm_title"],zoom_instagram_widget_admin["i18n_delete_"+(n?"feed":"user")+"_confirm_content"],zoom_instagram_widget_admin.i18n_delete_confirm_button_ok,zoom_instagram_widget_admin.i18n_delete_confirm_button_cancel).then((function(t){!0===t&&(window.location=i),window.wpzInstaCloseDialog()}))})),window.wpzInstaAuthenticateInstagram=function(t,e){var n=(window.screen.height-750)/2,i=(window.screen.width-700)/2;window.open(t,"","width=700,height=750,left="+i+",top="+n)},window.wpzInstaParseQuery=function(t){for(var e={},n=("?"===t[0]||"#"===t[0]?t.substr(1):t).split("&"),i=0;i<n.length;i++){var a=n[i].split("=");e[decodeURIComponent(a[0])]=decodeURIComponent(a[1]||"")}return e},window.wpzInstaHandleReturnedGraphToken=function(e){var n=arguments.length>1&&void 0!==arguments[1]&&arguments[1];if(e){var i=!n&&"hash"in e&&""!=(""+e.hash).trim()?window.wpzInstaParseQuery(""+e.hash):{};if(!n&&!t.isEmptyObject(i)||n&&""!=(""+e).trim()){var a=n?(""+e).trim():"access_graph_token"in i?(""+i.access_graph_token).trim():"-1";if(""!=a&&"-1"!=a){var s={action:"wpz-insta_connect_business-user",nonce:zoom_instagram_widget_admin.nonce,token:a};if(!n){var o="search"in e&&""!=(""+e.search).trim()?window.wpzInstaParseQuery(""+e.search):{};s.post_id=!t.isEmptyObject(o)&&"post"in o?parseInt(o.post):0}t.post(ajaxurl,s).done((function(e,n,i){if("success"==n){var a=t(e);a&&(t("#wpz-insta_modal_graph-dialog").find(".wpz-insta_modal-dialog_content").html(a),t("#wpz-insta_modal_graph-dialog").removeClass().addClass("open success"),t(".wpz-insta_business-accounts-link").on("click",(function(e){e.preventDefault(),zoom_instagram_widget_admin.is_pro?t(this).toggleClass("selected"):(t(".wpz-insta_business-accounts-link").removeClass("selected"),t(this).addClass("selected")),t("#wpz-insta-graph-connect-account").removeClass("disabled")})))}})).fail((function(){console.log("Failed to connect business user")}))}}}},t("#wpz-insta-select-api").on("change",(function(e){var n=t(this).val();t(this).parent().find("#wpz-insta_reconnect").attr("href",n)})),t("#wpz-add_manual_token").on("click",(function(e){e.preventDefault(),t("#wpz-insta-token_label").toggle()})),t("#wpz-insta-graph-connect-account").on("click",(function(e){e.preventDefault();var n=[],i=t(".wpz-insta_business-accounts-link").parent().data("post-id");if(t(".wpz-insta_business-accounts-link").each((function(){t(this).hasClass("selected")&&n.push(t(this).data("account-info"))})),n.length>0){var a={action:"wpz-insta_connect_business-account",nonce:zoom_instagram_widget_admin.nonce,account_info:JSON.stringify(n),post_id:i};t.post(ajaxurl,a).done((function(e,n,i){"success"==n&&t("#wpz-insta_modal_graph-dialog").removeClass("open"),window.location.replace(zoom_instagram_widget_admin.feeds_url)})).fail((function(t,e,n){console.log(t)}))}})),window.wpzInstaShowConnectDoneDialog=function(t){var e=arguments.length>1&&void 0!==arguments[1]&&arguments[1];window.wpzInstaShowDialog(t?e?zoom_instagram_widget_admin.i18n_reconnect_success_title:zoom_instagram_widget_admin.i18n_connect_success_title:zoom_instagram_widget_admin.i18n_connect_fail_title,t?e?zoom_instagram_widget_admin.i18n_reconnect_success_content:zoom_instagram_widget_admin.i18n_connect_success_content:zoom_instagram_widget_admin.i18n_connect_fail_content,(t?"success":"fail")+(e?" update":""))},window.wpzInstaShowDialog=function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"[DIALOG TITLE]",n=arguments.length>1&&void 0!==arguments[1]?arguments[1]:"[DIALOG CONTENT]",i=arguments.length>2&&void 0!==arguments[2]?arguments[2]:"success",a=t("#wpz-insta_modal-dialog"),s=a.find(".wpz-insta_modal-dialog_header-title"),o=a.find(".wpz-insta_modal-dialog_content"),r=(a.find(".wpz-insta_modal-dialog_footer .wpz-insta_modal-dialog_ok-button"),a.find(".wpz-insta_modal-dialog_footer .wpz-insta_modal-dialog_cancel-button"));s.html(""+e),o.html(""+n),r.addClass("hidden"),a.removeClass().addClass("open "+i)},window.wpzInstaShowConfirmDialog=function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"[DIALOG TITLE]",n=arguments.length>1&&void 0!==arguments[1]?arguments[1]:"[DIALOG CONTENT]",i=arguments.length>2&&void 0!==arguments[2]?arguments[2]:"[OK]",a=arguments.length>3&&void 0!==arguments[3]?arguments[3]:"[CANCEL]";return new Promise((function(s,o){var r=t("#wpz-insta_modal-dialog"),d=r.find(".wpz-insta_modal-dialog_header-title"),c=r.find(".wpz-insta_modal-dialog_content"),l=r.find(".wpz-insta_modal-dialog_footer .wpz-insta_modal-dialog_ok-button"),p=r.find(".wpz-insta_modal-dialog_footer .wpz-insta_modal-dialog_cancel-button");d.html(""+e),c.html(""+n),l.removeClass("hidden").html(""+i),l.on("click",(function(){return s(!0)})),p.removeClass("hidden").html(""+a),p.on("click",(function(){return s(!1)})),r.removeClass().addClass("open confirm")}))},window.wpzInstaCloseConnectDoneDialog=function(t){var e=arguments.length>1&&void 0!==arguments[1]&&arguments[1];window.wpzInstaCloseDialog(),t&&!e&&window.location.replace(zoom_instagram_widget_admin.feeds_url)},window.wpzInstaCloseDialog=function(){t("#wpz-insta_modal-dialog").removeClass("open")},window.wpzInstaCopyToClipboard=function(t){if(navigator.clipboard&&window.isSecureContext)return navigator.clipboard.writeText(t);var e=document.createElement("textarea");return e.value=t,e.style.position="fixed",e.style.left="-999999px",e.style.top="-999999px",document.body.appendChild(e),e.focus(),e.select(),new Promise((function(t,n){document.execCommand("copy")?t():n(),e.remove()}))},"inlineEditPost"in window){t(".inline-edit-save").find(".button-primary").addClass("disabled");var f=window.inlineEditPost.edit;window.inlineEditPost.edit=function(e){f.apply(this,arguments),"object"===i(e)&&(e=window.inlineEditPost.getId(e));for(var n,a,s=["_wpz-insta_account-type","_wpz-insta_token","_wpz-insta_token_expire","_thumbnail_id","wpz-insta_profile-photo","_wpz-insta_user_name","_wpz-insta_user-bio","_wpz-insta_api-url"],o=t("#inline_"+e),r=t("#edit-"+e),d=t("#wpz-insta_reconnect",r),c=0;c<s.length;c++)a=s[c],n=(n=t("."+a,o)).text(),"wpz-insta_profile-photo"==a?t("img."+a).attr("src",n):"_wpz-insta_token"==a?t("#wpz-insta_token",r).val(n):"_wpz-insta_token_expire"==a?t("#wpz-insta_token-expire-time",r).html(n):"_wpz-insta_api-url"==a?t("#wpz-insta_reconnect",r).attr("href",n):t(':input[name="'+a+'"]',r).val(n),t(':input[name="'+a+'"]',r).on("change paste keyup",(function(){t(".inline-edit-save",r).find(".button-primary").removeClass("disabled")}));t("#wpz-insta-select-api option",r).each((function(){var n=btoa(encodeURIComponent(zoom_instagram_widget_admin.post_edit_url+e)),i=t(this).val();if(i.includes("RETURN_URL")){var a=i.replace("RETURN_URL",encodeURIComponent(n));t(this).val(a)}})),d.attr({href:d.attr("href").replace("RETURN_URL",btoa(encodeURIComponent(zoom_instagram_widget_admin.post_edit_url+e))),"data-user-id":e})}}window.wpzInstaHandleReturnedToken=function(e){var n=arguments.length>1&&void 0!==arguments[1]&&arguments[1];if(e){var i=!n&&"hash"in e&&""!=(""+e.hash).trim()?window.wpzInstaParseQuery(""+e.hash):{};if(!n&&!t.isEmptyObject(i)||n&&""!=(""+e).trim()){var a=n?(""+e).trim():"access_token"in i?(""+i.access_token).trim():"-1";if(""!=a&&"-1"!=a){var s={action:"wpz-insta_connect-user",nonce:zoom_instagram_widget_admin.nonce,token:a};if(!n){var o="search"in e&&""!=(""+e.search).trim()?window.wpzInstaParseQuery(""+e.search):{};s.post_id=!t.isEmptyObject(o)&&"post"in o?parseInt(o.post):0}t.post(ajaxurl,s).done((function(e){t(".inline-edit-wpz-insta_user #wpz-insta_token").val(a);var n=new Date;n.setDate(n.getDate()+60),t("#the-list #wpz-insta_token-expire-time").html(n.toLocaleDateString("en-US",{weekday:"long",day:"numeric",month:"long",year:"numeric"})),window.wpzInstaShowConnectDoneDialog(e.success,"data"in e&&"update"in e.data&&e.data.update)})).fail((function(){window.wpzInstaShowConnectDoneDialog(!1)}))}}}},window.wpzInstaReloadPreview=function(){var e=zoom_instagram_widget_admin.preview_url,n=t.param(t("form#post #title, form#post .wpz-insta_tabs-content > .wpz-insta_sidebar > .wpz-insta_sidebar-left").find("input, textarea, select").not(".preview-exclude").serializeArray());n&&(e+="&"+n),t("#wpz-insta_widget-preview-view").closest(".wpz-insta_sidebar-right").removeClass("hide-loading"),t("#wpz-insta_widget-preview-view iframe").addClass("wpz-insta_preview-hidden").attr("src",e)},window.wpzInstaUpdatePreviewHeight=function(){var e=t("#wpz-insta_widget-preview-view iframe");e.height(parseInt(e.contents().find("body").prop("scrollHeight")))}}))}});