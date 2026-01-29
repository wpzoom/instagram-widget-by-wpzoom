/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/scripts/backend/custom-server-side-render/custom-server-side-render.js"
/*!************************************************************************************!*\
  !*** ./src/scripts/backend/custom-server-side-render/custom-server-side-render.js ***!
  \************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ CustomServerSideRender),
/* harmony export */   rendererPath: () => (/* binding */ rendererPath)
/* harmony export */ });
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/url */ "@wordpress/url");
/* harmony import */ var _wordpress_url__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_url__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_7__);
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = !0, o = !1; try { if (i = (t = t.call(r)).next, 0 === l) { if (Object(t) !== t) return; f = !1; } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = !0, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */







function rendererPath(block) {
  var attributes = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
  var urlQueryArgs = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
  return (0,_wordpress_url__WEBPACK_IMPORTED_MODULE_5__.addQueryArgs)("/wp/v2/block-renderer/".concat(block), _objectSpread(_objectSpread({
    context: 'edit'
  }, null !== attributes ? {
    attributes: attributes
  } : {}), urlQueryArgs));
}
function DefaultEmptyResponsePlaceholder(_ref) {
  var className = _ref.className;
  return /*#__PURE__*/React.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Placeholder, {
    className: className
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Block rendered as empty.'));
}
function DefaultErrorResponsePlaceholder(_ref2) {
  var response = _ref2.response,
    className = _ref2.className;
  var errorMessage = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.sprintf)(
  // translators: %s: error message describing the problem
  (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)('Error loading block: %s'), response.errorMsg);
  return /*#__PURE__*/React.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Placeholder, {
    className: className
  }, errorMessage);
}
function DefaultLoadingResponsePlaceholder(_ref3) {
  var children = _ref3.children,
    showLoader = _ref3.showLoader;
  return /*#__PURE__*/React.createElement("div", {
    style: {
      position: 'relative'
    }
  }, showLoader && /*#__PURE__*/React.createElement("div", {
    style: {
      position: 'absolute',
      top: '50%',
      left: '50%',
      marginTop: '-9px',
      marginLeft: '-9px'
    }
  }, /*#__PURE__*/React.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Spinner, null)), /*#__PURE__*/React.createElement("div", {
    style: {
      opacity: showLoader ? '0.3' : 1
    }
  }, children));
}
function CustomServerSideRender(props) {
  var attributes = props.attributes,
    block = props.block,
    className = props.className,
    _props$httpMethod = props.httpMethod,
    httpMethod = _props$httpMethod === void 0 ? 'GET' : _props$httpMethod,
    urlQueryArgs = props.urlQueryArgs,
    _props$EmptyResponseP = props.EmptyResponsePlaceholder,
    EmptyResponsePlaceholder = _props$EmptyResponseP === void 0 ? DefaultEmptyResponsePlaceholder : _props$EmptyResponseP,
    _props$ErrorResponseP = props.ErrorResponsePlaceholder,
    ErrorResponsePlaceholder = _props$ErrorResponseP === void 0 ? DefaultErrorResponsePlaceholder : _props$ErrorResponseP,
    _props$LoadingRespons = props.LoadingResponsePlaceholder,
    LoadingResponsePlaceholder = _props$LoadingRespons === void 0 ? DefaultLoadingResponsePlaceholder : _props$LoadingRespons;
  var isMountedRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useRef)(true);
  var _useState = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(false),
    _useState2 = _slicedToArray(_useState, 2),
    showLoader = _useState2[0],
    setShowLoader = _useState2[1];
  var fetchRequestRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useRef)();
  var _useState3 = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(null),
    _useState4 = _slicedToArray(_useState3, 2),
    response = _useState4[0],
    setResponse = _useState4[1];
  var prevProps = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__.usePrevious)(props);
  var _useState5 = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(false),
    _useState6 = _slicedToArray(_useState5, 2),
    isLoading = _useState6[0],
    setIsLoading = _useState6[1];
  function fetchData() {
    if (!isMountedRef.current) {
      return;
    }
    setIsLoading(true);
    var sanitizedAttributes = attributes && (0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_7__.__experimentalSanitizeBlockAttributes)(block, attributes);

    // If httpMethod is 'POST', send the attributes in the request body instead of the URL.
    // This allows sending a larger attributes object than in a GET request, where the attributes are in the URL.
    var isPostRequest = 'POST' === httpMethod;
    var urlAttributes = isPostRequest ? null : sanitizedAttributes !== null && sanitizedAttributes !== void 0 ? sanitizedAttributes : null;
    var path = rendererPath(block, urlAttributes, urlQueryArgs);
    var data = isPostRequest ? {
      attributes: sanitizedAttributes !== null && sanitizedAttributes !== void 0 ? sanitizedAttributes : null
    } : null;

    // Store the latest fetch request so that when we process it, we can
    // check if it is the current request, to avoid race conditions on slow networks.
    var fetchRequest = fetchRequestRef.current = _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_4___default()({
      path: path,
      data: data,
      method: isPostRequest ? 'POST' : 'GET'
    }).then(function (fetchResponse) {
      if (isMountedRef.current && fetchRequest === fetchRequestRef.current && fetchResponse) {
        setResponse(fetchResponse.rendered);
      }
    })["catch"](function (error) {
      if (isMountedRef.current && fetchRequest === fetchRequestRef.current) {
        setResponse({
          error: true,
          errorMsg: error.message
        });
      }
    })["finally"](function () {
      if (isMountedRef.current && fetchRequest === fetchRequestRef.current) {
        setIsLoading(false);
        setTimeout(function () {
          window.dispatchEvent(new Event('wpzInstaServerSideRenderDone'));
        }, 300);
      }
    });
    return fetchRequest;
  }
  var debouncedFetchData = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__.useDebounce)(fetchData, 200);

  // When the component unmounts, set isMountedRef to false. This will
  // let the async fetch callbacks know when to stop.
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useEffect)(function () {
    return function () {
      isMountedRef.current = false;
    };
  }, []);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useEffect)(function () {
    // Don't debounce the first fetch. This ensures that the first render
    // shows data as soon as possible.
    if (prevProps === undefined) {
      fetchData();
    } else if (!(0,lodash__WEBPACK_IMPORTED_MODULE_0__.isEqual)(prevProps, props)) {
      debouncedFetchData();
    }
  });

  /**
   * Effect to handle showing the loading placeholder.
   * Show it only if there is no previous response or
   * the request takes more than one second.
   */
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useEffect)(function () {
    if (!isLoading) {
      return;
    }
    var timeout = setTimeout(function () {
      setShowLoader(true);
    }, 300);
    return function () {
      return clearTimeout(timeout);
    };
  }, [isLoading]);
  var hasResponse = !!response;
  var hasEmptyResponse = response === '';
  var hasError = response === null || response === void 0 ? void 0 : response.error;
  if (isLoading) {
    return /*#__PURE__*/React.createElement(LoadingResponsePlaceholder, _extends({}, props, {
      showLoader: showLoader
    }), hasResponse && /*#__PURE__*/React.createElement(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.RawHTML, {
      className: className
    }, response));
  }
  if (hasEmptyResponse || !hasResponse) {
    return /*#__PURE__*/React.createElement(EmptyResponsePlaceholder, props);
  }
  if (hasError) {
    return /*#__PURE__*/React.createElement(ErrorResponsePlaceholder, _extends({
      response: response
    }, props));
  }
  return /*#__PURE__*/React.createElement(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.RawHTML, {
    className: className
  }, response);
}

/***/ },

/***/ "./src/scripts/backend/custom-server-side-render/index.js"
/*!****************************************************************!*\
  !*** ./src/scripts/backend/custom-server-side-render/index.js ***!
  \****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/deprecated */ "@wordpress/deprecated");
/* harmony import */ var _wordpress_deprecated__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_deprecated__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _custom_server_side_render__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./custom-server-side-render */ "./src/scripts/backend/custom-server-side-render/custom-server-side-render.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
var _excluded = ["urlQueryArgs", "currentPostId"];
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _objectWithoutProperties(e, t) { if (null == e) return {}; var o, r, i = _objectWithoutPropertiesLoose(e, t); if (Object.getOwnPropertySymbols) { var n = Object.getOwnPropertySymbols(e); for (r = 0; r < n.length; r++) o = n[r], -1 === t.indexOf(o) && {}.propertyIsEnumerable.call(e, o) && (i[o] = e[o]); } return i; }
function _objectWithoutPropertiesLoose(r, e) { if (null == r) return {}; var t = {}; for (var n in r) if ({}.hasOwnProperty.call(r, n)) { if (-1 !== e.indexOf(n)) continue; t[n] = r[n]; } return t; }
/**
 * WordPress dependencies
 */




/**
 * Internal dependencies
 */


/**
 * Constants
 */
var EMPTY_OBJECT = {};
var ExportedCustomServerSideRender = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_1__.withSelect)(function (select) {
  // FIXME: @wordpress/server-side-render should not depend on @wordpress/editor.
  // It is used by blocks that can be loaded into a *non-post* block editor.
  // eslint-disable-next-line @wordpress/data-no-store-string-literals
  var coreEditorSelect = select('core/editor');
  if (coreEditorSelect) {
    var currentPostId = coreEditorSelect.getCurrentPostId();
    // For templates and template parts we use a custom ID format.
    // Since they aren't real posts, we don't want to use their ID
    // for server-side rendering. Since they use a string based ID,
    // we can assume real post IDs are numbers.
    if (currentPostId && typeof currentPostId === 'number') {
      return {
        currentPostId: currentPostId
      };
    }
  }
  return EMPTY_OBJECT;
})(function (_ref) {
  var _ref$urlQueryArgs = _ref.urlQueryArgs,
    urlQueryArgs = _ref$urlQueryArgs === void 0 ? EMPTY_OBJECT : _ref$urlQueryArgs,
    currentPostId = _ref.currentPostId,
    props = _objectWithoutProperties(_ref, _excluded);
  var newUrlQueryArgs = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useMemo)(function () {
    if (!currentPostId) {
      return urlQueryArgs;
    }
    return _objectSpread({
      post_id: currentPostId
    }, urlQueryArgs);
  }, [currentPostId, urlQueryArgs]);
  return /*#__PURE__*/React.createElement(_custom_server_side_render__WEBPACK_IMPORTED_MODULE_3__["default"], _extends({
    urlQueryArgs: newUrlQueryArgs
  }, props));
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ExportedCustomServerSideRender);

/***/ },

/***/ "@wordpress/api-fetch"
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
(module) {

module.exports = window["wp"]["apiFetch"];

/***/ },

/***/ "@wordpress/block-editor"
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
(module) {

module.exports = window["wp"]["blockEditor"];

/***/ },

/***/ "@wordpress/blocks"
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
(module) {

module.exports = window["wp"]["blocks"];

/***/ },

/***/ "@wordpress/components"
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
(module) {

module.exports = window["wp"]["components"];

/***/ },

/***/ "@wordpress/compose"
/*!*********************************!*\
  !*** external ["wp","compose"] ***!
  \*********************************/
(module) {

module.exports = window["wp"]["compose"];

/***/ },

/***/ "@wordpress/core-data"
/*!**********************************!*\
  !*** external ["wp","coreData"] ***!
  \**********************************/
(module) {

module.exports = window["wp"]["coreData"];

/***/ },

/***/ "@wordpress/data"
/*!******************************!*\
  !*** external ["wp","data"] ***!
  \******************************/
(module) {

module.exports = window["wp"]["data"];

/***/ },

/***/ "@wordpress/deprecated"
/*!************************************!*\
  !*** external ["wp","deprecated"] ***!
  \************************************/
(module) {

module.exports = window["wp"]["deprecated"];

/***/ },

/***/ "@wordpress/element"
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
(module) {

module.exports = window["wp"]["element"];

/***/ },

/***/ "@wordpress/i18n"
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
(module) {

module.exports = window["wp"]["i18n"];

/***/ },

/***/ "@wordpress/url"
/*!*****************************!*\
  !*** external ["wp","url"] ***!
  \*****************************/
(module) {

module.exports = window["wp"]["url"];

/***/ },

/***/ "lodash"
/*!*************************!*\
  !*** external "lodash" ***!
  \*************************/
(module) {

module.exports = window["lodash"];

/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Check if module exists (development only)
/******/ 		if (__webpack_modules__[moduleId] === undefined) {
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!**************************************!*\
  !*** ./src/scripts/backend/block.js ***!
  \**************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_core_data__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/core-data */ "@wordpress/core-data");
/* harmony import */ var _wordpress_core_data__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_core_data__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _custom_server_side_render__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./custom-server-side-render */ "./src/scripts/backend/custom-server-side-render/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__);
function _toConsumableArray(r) { return _arrayWithoutHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _iterableToArray(r) { if ("undefined" != typeof Symbol && null != r[Symbol.iterator] || null != r["@@iterator"]) return Array.from(r); }
function _arrayWithoutHoles(r) { if (Array.isArray(r)) return _arrayLikeToArray(r); }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }








(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_1__.registerBlockType)('wpzoom/instagram-block', {
  apiVersion: 2,
  title: 'Instagram Feed by WPZOOM',
  icon: 'instagram',
  category: 'wpzoom-blocks',
  supports: {
    align: true,
    html: false
  },
  attributes: {
    feed: {
      type: 'integer',
      "default": -1
    },
    align: {
      type: 'string',
      "default": 'none'
    }
  },
  edit: function edit(props) {
    var _props$attributes = props.attributes,
      feed = _props$attributes.feed,
      align = _props$attributes.align,
      setAttributes = props.setAttributes,
      className = props.className;
    var blockProps = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__.useBlockProps)();
    var _useSelect = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_4__.useSelect)(function (select) {
        var _select = select(_wordpress_core_data__WEBPACK_IMPORTED_MODULE_5__.store),
          getEntityRecords = _select.getEntityRecords;
        var feeds = getEntityRecords('postType', 'wpz-insta_feed', (0,lodash__WEBPACK_IMPORTED_MODULE_0__.pickBy)({
          per_page: -1
        }, function (value) {
          return !(0,lodash__WEBPACK_IMPORTED_MODULE_0__.isUndefined)(value);
        }));
        return {
          feedsList: !Array.isArray(feeds) ? feeds : feeds.map(function (feed) {
            return {
              value: feed.id,
              label: 'title' in feed && 'rendered' in feed.title ? feed.title.rendered : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('(No title)', 'instagram-widget-by-wpzoom')
            };
          })
        };
      }),
      feedsList = _useSelect.feedsList;
    var hasFeeds = !!(feedsList !== null && feedsList !== void 0 && feedsList.length);
    if (!hasFeeds) {
      return /*#__PURE__*/React.createElement("div", blockProps, /*#__PURE__*/React.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__.Placeholder, {
        icon: "instagram",
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Instagram Feed by WPZOOM')
      }, !Array.isArray(feedsList) ? /*#__PURE__*/React.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__.Spinner, null) : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('You must create some feeds to use this block properly.', 'instagram-widget-by-wpzoom')));
    }
    return /*#__PURE__*/React.createElement("div", blockProps, /*#__PURE__*/React.createElement(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__.InspectorControls, null, /*#__PURE__*/React.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__.PanelBody, {
      title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Feed settings', 'instagram-widget-by-wpzoom')
    }, /*#__PURE__*/React.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__.SelectControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Feed to Display', 'instagram-widget-by-wpzoom'),
      value: feed,
      options: [{
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("\u2014 Select a Feed \u2014", 'instagram-widget-by-wpzoom'),
        value: -1,
        disabled: true,
        hidden: true
      }].concat(_toConsumableArray(feedsList)),
      onChange: function onChange(newFeed) {
        setAttributes({
          feed: Number(newFeed)
        });
      }
    }))), feed > 0 ? /*#__PURE__*/React.createElement(_custom_server_side_render__WEBPACK_IMPORTED_MODULE_6__["default"], {
      block: "wpzoom/instagram-block",
      attributes: props.attributes,
      EmptyResponsePlaceholder: function EmptyResponsePlaceholder() {
        return /*#__PURE__*/React.createElement("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Instagram: No feed to show.', 'instagram-widget-by-wpzoom'));
      }
    }) : /*#__PURE__*/React.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__.Card, {
      size: "large"
    }, /*#__PURE__*/React.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__.CardHeader, null, /*#__PURE__*/React.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__.Flex, {
      align: "center",
      justify: "start",
      gap: 2,
      wrap: true
    }, /*#__PURE__*/React.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__.Icon, {
      icon: "instagram"
    }), /*#__PURE__*/React.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__.__experimentalHeading, {
      level: "5"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Instagram Feed by WPZOOM', 'instagram-widget-by-wpzoom')))), /*#__PURE__*/React.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__.CardBody, null, /*#__PURE__*/React.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__.SelectControl, {
      className: "wpzoom-instagram-widget-select-feed",
      value: feed,
      options: [{
        label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)("\u2014 Select a Feed to Display \u2014", 'instagram-widget-by-wpzoom'),
        value: -1,
        disabled: true,
        hidden: true
      }].concat(_toConsumableArray(feedsList)),
      onChange: function onChange(newFeed) {
        setAttributes({
          feed: Number(newFeed)
        });
      }
    }))));
  }
});
})();

/******/ })()
;
//# sourceMappingURL=block.js.map