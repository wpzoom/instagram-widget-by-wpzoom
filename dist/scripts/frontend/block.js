/******/ (() => { // webpackBootstrap
/*!***************************************!*\
  !*** ./src/scripts/frontend/block.js ***!
  \***************************************/
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
(function ($) {
  window.wpzInstaFrontendInit = function () {
    $('.zoom-instagram-widget__items[data-lightbox="1"]').each(function () {
      var $swipe_el = $(this).parent().parent().find('.wpz-insta-lightbox-wrapper > .swiper');
      if ($swipe_el.length > 0) {
        var $nested = $swipe_el.find('.image-wrapper > .swiper');
        var swiper = new Swiper($swipe_el.get(0), _defineProperty(_defineProperty(_defineProperty(_defineProperty(_defineProperty(_defineProperty(_defineProperty(_defineProperty(_defineProperty({
          lazy: {
            threshold: 50
          },
          watchSlidesVisibility: true,
          preloadImages: false
        }, "lazy", true), "direction", 'horizontal'), "loop", false), "spaceBetween", 20), "autoHeight", false), "watchOverflow", true), "navigation", {
          nextEl: $swipe_el.find('> .swiper-button-next').get(0),
          prevEl: $swipe_el.find('> .swiper-button-prev').get(0)
        }), "keyboard", {
          enabled: true,
          onlyInViewport: true
        }), "on", {
          activeIndexChange: function activeIndexChange() {
            // Get the active slide
            var activeSlide = this.slides[this.activeIndex];
            var $activeSlide = $(activeSlide);

            // Play the video in the active slide if it exists
            var video = $activeSlide.find('video').get(0);
            if (video) {
              video.play();
            }
          }
        }));
        $nested.each(function () {
          new Swiper($(this).get(0), _defineProperty(_defineProperty(_defineProperty(_defineProperty(_defineProperty(_defineProperty(_defineProperty(_defineProperty(_defineProperty(_defineProperty({
            lazy: {
              threshold: 50
            },
            watchSlidesVisibility: true,
            preloadImages: false
          }, "lazy", true), "direction", 'horizontal'), "loop", false), "spaceBetween", 20), "nested", true), "watchOverflow", true), "pagination", {
            el: $(this).find('> .swiper-pagination').get(0),
            type: 'bullets',
            clickable: true,
            hideOnClick: false
          }), "navigation", {
            nextEl: $(this).find('> .swiper-button-next').get(0),
            prevEl: $(this).find('> .swiper-button-prev').get(0)
          }), "keyboard", {
            enabled: true,
            onlyInViewport: true
          }), "on", {
            activeIndexChange: function activeIndexChange() {
              // Get the active slide
              var activeSlide = this.slides[this.activeIndex];
              var $activeSlide = $(activeSlide);

              // Play the video in the active slide if it exists
              var video = $activeSlide.find('video').get(0);
              if (video) {
                video.play();
              }
            }
          }));
        });
        $(this).find('.zoom-instagram-link').magnificPopup({
          items: {
            type: 'inline',
            src: $(this).parent().parent().find('.wpz-insta-lightbox-wrapper')
          },
          closeBtnInside: false,
          mainClass: 'wpzoom-lightbox',
          midClick: true,
          callbacks: {
            open: function open() {
              var magnificPopup = $.magnificPopup.instance,
                currentElement = magnificPopup.st.el,
                $thisSwiper = this.content.find('> .swiper').get(0).swiper;
              if (this.content.find('> .swiper > .swiper-wrapper > .swiper-slide[data-uid="' + currentElement.data('mfp-src') + '"] video')) {
                this.content.find('> .swiper > .swiper-wrapper > .swiper-slide[data-uid="' + currentElement.data('mfp-src') + '"] video').trigger('play');
              }
              if (_typeof($thisSwiper) === 'object') {
                $thisSwiper.slideTo(this.content.find('> .swiper > .swiper-wrapper > .swiper-slide[data-uid="' + currentElement.data('mfp-src') + '"]').index());
              }
            }
          }
        });
        $(this).find('.zoom-instagram-link').addClass('magnific-active');
      }
    });
  };
  $(window).on('load', window.wpzInstaFrontendInit);
})(jQuery);
/******/ })()
;
//# sourceMappingURL=block.js.map