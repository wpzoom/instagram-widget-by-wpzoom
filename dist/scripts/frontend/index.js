!function(t){var e={};function i(n){if(e[n])return e[n].exports;var a=e[n]={i:n,l:!1,exports:{}};return t[n].call(a.exports,a,a.exports,i),a.l=!0,a.exports}i.m=t,i.c=e,i.d=function(t,e,n){i.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:n})},i.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},i.t=function(t,e){if(1&e&&(t=i(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var n=Object.create(null);if(i.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var a in t)i.d(n,a,function(e){return t[e]}.bind(null,a));return n},i.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return i.d(e,"a",e),e},i.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},i.p="",i(i.s=11)}({11:function(t,e){jQuery((function(t){var e=!1;function i(){e||(e=!0,(window.requestAnimationFrame||window.webkitRequestAnimationFrame||window.mozRequestAnimationFrame||function(t){window.setTimeout(t,1e3/60)})(n))}function n(){t(".zoom-instagram-widget__items").zoomInstagramWidget(),e=!1}t.fn.zoomLoadAsyncImages=function(){return t(this).each((function(){var e=t(this),i=e.data("image-width"),n=e.data("image-resolution"),a=e.find("li").filter((function(){return t(this).data("media-id")})).map((function(){return{"media-id":t(this).attr("data-media-id"),nonce:t(this).attr("data-nonce"),"regenerate-thumbnails":t(this)[0].hasAttribute("data-regenerate-thumbnails")}}));a.length&&function t(a){if(0!=a.length){var o=a.shift();wp.ajax.post("wpzoom_instagram_get_image_async",{"media-id":o["media-id"],nonce:o.nonce,"image-resolution":n,"image-width":i,"regenerate-thumbnails":o["regenerate-thumbnails"]}).done((function(t){e.find('li[data-media-id="'+o["media-id"]+'"] .zoom-instagram-link').css("background-image","url("+t.image_src+")")})).fail((function(){})).always((function(){t(a)}))}}(a.toArray())}))},t.fn.zoomLightbox=function(){return t(this).each((function(){var e=t(this).closest(".widget").find(".wpz-insta-lightbox-wrapper > .swiper-container");if(e.length>0){var i=e.find(".image-wrapper > .swiper-container");new Swiper(e.get(0),{direction:"horizontal",loop:!1,spaceBetween:20,autoHeight:!0,watchOverflow:!0,navigation:{nextEl:e.find("> .swiper-button-next").get(0),prevEl:e.find("> .swiper-button-prev").get(0)},keyboard:{enabled:!0,onlyInViewport:!0}}),i.each((function(){new Swiper(t(this).get(0),{direction:"horizontal",loop:!1,spaceBetween:20,nested:!0,watchOverflow:!0,pagination:{el:t(this).find("> .swiper-pagination").get(0),type:"bullets",clickable:!0,hideOnClick:!1},navigation:{nextEl:t(this).find("> .swiper-button-next").get(0),prevEl:t(this).find("> .swiper-button-prev").get(0)},keyboard:{enabled:!0,onlyInViewport:!0}})})),t(this).find(".zoom-instagram-link").magnificPopup({items:{type:"inline",src:t(this).closest(".widget").find(".wpz-insta-lightbox-wrapper")},closeBtnInside:!1,mainClass:"wpzoom-lightbox",midClick:!0,callbacks:{open:function(){var e=t.magnificPopup.instance.st.el;this.content.find("> .swiper-container").get(0).swiper.slideTo(this.content.find('> .swiper-container > .swiper-wrapper > .swiper-slide[data-uid="'+e.data("mfp-src")+'"]').index())}}}),t(this).find(".zoom-instagram-link").addClass("magnific-active")}}))},t.fn.zoomInstagramWidget=function(){return t(this).each((function(){var e,i,n=t(this),a=n.data("images-per-row"),o=n.data("image-width"),r=n.data("image-spacing"),s=n.data("image-lazy-loading"),d=n.width();d/o<a?(e=a,i=Math.floor((d-1-(a-1)*r)/a)):(e=Math.floor((d-1)/o),i=Math.floor((d-1-(e-1)*r)/e)),n.find("li").each((function(i){var n=++i;n%e==1?t(this).css("clear","left"):t(this).css("clear","none"),n%e==0?t(this).css("margin-right","0"):(t(this).css("margin-right",r+"px"),t(this).css("margin-bottom",r+"px"))})),n.find("a.zoom-instagram-link").css({width:i,height:i}),s&&n.find("a.zoom-instagram-link").lazy(),n.removeClass("zoom-instagram-widget__items--no-js")}))},t(window).on("resize orientationchange",i),i(),t(".zoom-instagram-widget__items").zoomLoadAsyncImages(),t('.zoom-instagram-widget__items[data-lightbox="1"]').zoomLightbox();var a=_.debounce((function(){t(".zoom-instagram-widget__items").length&&(t(".zoom-instagram-widget__items").zoomInstagramWidget(),t(".zoom-instagram-widget__items").zoomLoadAsyncImages())}),1500);t(document).on("panels_setup_preview",a)}))}});