!function(e){var t={};function n(r){if(t[r])return t[r].exports;var o=t[r]={i:r,l:!1,exports:{}};return e[r].call(o.exports,o,o.exports,n),o.l=!0,o.exports}n.m=e,n.c=t,n.d=function(e,t,r){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:r})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(n.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var o in e)n.d(r,o,function(t){return e[t]}.bind(null,o));return r},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="",n(n.s=11)}([function(e,t){e.exports=window.wp.i18n},function(e,t){e.exports=window.wp.components},function(e,t){e.exports=window.lodash},function(e,t){e.exports=window.wp.blockEditor},function(e,t){e.exports=window.wp.blocks},function(e,t){e.exports=window.wp.serverSideRender},function(e,t){e.exports=window.wp.data},function(e,t){e.exports=window.wp.coreData},,,,function(e,t,n){"use strict";n.r(t);var r=n(2),o=n(4),i=n(0),a=n(5),c=n.n(a),u=n(3),l=n(6),s=n(1),d=n(7);function f(e,t){(null==t||t>e.length)&&(t=e.length);for(var n=0,r=new Array(t);n<t;n++)r[n]=e[n];return r}function p(e,t,n,r,o,i,a){try{var c=e[i](a),u=c.value}catch(e){return void n(e)}c.done?t(u):Promise.resolve(u).then(r,o)}function m(e){return function(){var t=this,n=arguments;return new Promise((function(r,o){var i=e.apply(t,n);function a(e){p(i,r,o,a,c,"next",e)}function c(e){p(i,r,o,a,c,"throw",e)}a(void 0)}))}}var b=window.fetch;window.fetch=m(regeneratorRuntime.mark((function e(){var t,n,r=arguments;return regeneratorRuntime.wrap((function(e){for(;;)switch(e.prev=e.next){case 0:return t=r.length>0?r.length<=0?void 0:r[0]:"",e.next=3,b.apply(void 0,r);case 3:return n=e.sent,t.includes("wpzoom/instagram-block")&&n.clone().json().then((function(e){return window.setTimeout((function(){return window.wpzInstaFrontendInit()}),300)})).catch((function(e){return console.error(e)})),e.abrupt("return",n);case 6:case"end":return e.stop()}}),e)}))),Object(o.registerBlockType)("wpzoom/instagram-block",{apiVersion:2,title:"Instagram",icon:"instagram",category:"widgets",attributes:{feed:{type:"integer",default:-1}},edit:function(e){var t,n=e.attributes.feed,o=e.setAttributes,a=(e.className,Object(u.useBlockProps)()),p=Object(l.useSelect)((function(e){var t=(0,e(d.store).getEntityRecords)("postType","wpz-insta_feed",Object(r.pickBy)({per_page:-1},(function(e){return!Object(r.isUndefined)(e)})));return{feedsList:Array.isArray(t)?t.map((function(e){return{value:e.id,label:"title"in e&&"rendered"in e.title?e.title.rendered:Object(i.__)("(No title)","instagram-widget-by-wpzoom")}})):t}})).feedsList;return null!=p&&p.length?React.createElement("div",a,React.createElement(u.InspectorControls,null,React.createElement(s.PanelBody,{title:Object(i.__)("Feed settings","instagram-widget-by-wpzoom")},React.createElement(s.SelectControl,{label:Object(i.__)("Feed to Display","instagram-widget-by-wpzoom"),value:n,options:[{label:Object(i.__)("-- Select a Feed --","instagram-widget-by-wpzoom"),value:-1,disabled:!0,hidden:!0}].concat((t=p,function(e){if(Array.isArray(e))return f(e)}(t)||function(e){if("undefined"!=typeof Symbol&&null!=e[Symbol.iterator]||null!=e["@@iterator"])return Array.from(e)}(t)||function(e,t){if(e){if("string"==typeof e)return f(e,void 0);var n=Object.prototype.toString.call(e).slice(8,-1);return"Object"===n&&e.constructor&&(n=e.constructor.name),"Map"===n||"Set"===n?Array.from(e):"Arguments"===n||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)?f(e,void 0):void 0}}(t)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}())),onChange:function(e){o({feed:Number(e)})}}))),React.createElement(c.a,{block:"wpzoom/instagram-block",attributes:e.attributes,EmptyResponsePlaceholder:function(){return React.createElement("span",null,Object(i.__)("Instagram: No feed to show.","instagram-widget-by-wpzoom"))}})):React.createElement("div",a,React.createElement(s.Placeholder,{icon:"instagram",label:Object(i.__)("Instagram Feed")},Array.isArray(p)?Object(i.__)("You must create some feeds to use this block properly.","instagram-widget-by-wpzoom"):React.createElement(s.Spinner,null)))}})}]);