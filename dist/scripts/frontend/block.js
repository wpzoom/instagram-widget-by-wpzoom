/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./src/scripts/frontend/block.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./src/scripts/frontend/block.js":
/*!***************************************!*\
  !*** ./src/scripts/frontend/block.js ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function ($) {
  window.wpzInstaFrontendInit = function () {
    $.fn.zoomLightbox = function () {
      return $(this).each(function () {
        var $swipe_el = $(this).parent().find('.wpz-insta-lightbox-wrapper > .swiper-container');

        if ($swipe_el.length > 0) {
          var $nested = $swipe_el.find('.image-wrapper > .swiper-container');
          new Swiper($swipe_el.get(0), {
            direction: 'horizontal',
            loop: false,
            spaceBetween: 20,
            autoHeight: true,
            watchOverflow: true,
            navigation: {
              nextEl: $swipe_el.find('> .swiper-button-next').get(0),
              prevEl: $swipe_el.find('> .swiper-button-prev').get(0)
            },
            keyboard: {
              enabled: true,
              onlyInViewport: true
            }
          });
          $nested.each(function () {
            new Swiper($(this).get(0), {
              direction: 'horizontal',
              loop: false,
              spaceBetween: 20,
              nested: true,
              watchOverflow: true,
              pagination: {
                el: $(this).find('> .swiper-pagination').get(0),
                type: 'bullets',
                clickable: true,
                hideOnClick: false
              },
              navigation: {
                nextEl: $(this).find('> .swiper-button-next').get(0),
                prevEl: $(this).find('> .swiper-button-prev').get(0)
              },
              keyboard: {
                enabled: true,
                onlyInViewport: true
              }
            });
          });
          $(this).find('.zoom-instagram-link').magnificPopup({
            items: {
              type: 'inline',
              src: $(this).parent().find('.wpz-insta-lightbox-wrapper')
            },
            closeBtnInside: false,
            mainClass: 'wpzoom-lightbox',
            midClick: true,
            callbacks: {
              open: function open() {
                this.content.find('> .swiper-container').get(0).swiper.slideTo(this.content.find('> .swiper-container > .swiper-wrapper > .swiper-slide[data-uid="' + $(this._lastFocusedEl).data('mfp-src') + '"]').index());
              }
            }
          });
        }
      });
    };

    $('.zoom-instagram-widget__items[data-lightbox="1"]').zoomLightbox();
  };

  $(window.wpzInstaFrontendInit);
})(jQuery);

/***/ })

/******/ });
//# sourceMappingURL=block.js.map