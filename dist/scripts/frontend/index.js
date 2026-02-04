/******/ (() => { // webpackBootstrap
/*!***************************************!*\
  !*** ./src/scripts/frontend/index.js ***!
  \***************************************/
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
(function ($) {
  // Image loading shimmer effect - needs to run early to catch images before they load
  function initImageLoadingShimmer() {
    $('.zoom-instagram-widget__items').each(function () {
      var $container = $(this);
      $container.find('.zoom-instagram-widget__item').each(function () {
        var $item = $(this);

        // Skip if already processed
        if ($item.hasClass('wpz-insta-loaded') || $item.data('shimmer-init')) {
          return;
        }
        $item.data('shimmer-init', true);
        var $img = $item.find('img.zoom-instagram-link, img.zoom-instagram-link-new').first();
        if ($img.length === 0) {
          $item.addClass('wpz-insta-loaded');
          return;
        }

        // Check if image is already loaded (cached)
        if ($img[0].complete && $img[0].naturalWidth > 0) {
          $item.addClass('wpz-insta-loaded');
          return;
        }

        // Listen for image load
        $img.on('load.shimmer', function () {
          $item.addClass('wpz-insta-loaded');
        });

        // Handle error case - still show the item
        $img.on('error.shimmer', function () {
          $item.addClass('wpz-insta-loaded');
        });
      });
    });
  }

  // Run as early as possible - on DOMContentLoaded
  $(document).ready(function () {
    initImageLoadingShimmer();
  });

  // Export globally so PRO plugin can call after Load More
  window.wpzInstaImageShimmerInit = initImageLoadingShimmer;
  $(window).on('load', function () {
    var ticking = false;

    // Re-run shimmer init in case new feeds were added
    initImageLoadingShimmer();

    // Fast AJAX Load More functionality
    function initLoadMoreButtons() {
      $('.wpzinsta-pro-load-more-btn').off('click.loadmore').on('click.loadmore', function (e) {
        e.preventDefault();
        var $button = $(this);
        var $wrapper = $button.closest('.wpzinsta-pro-load-more-wrapper');
        var $feedContainer = $button.closest('.zoom-instagram');
        var $itemsContainer = $feedContainer.find('.zoom-instagram-widget__items');

        // Check if disabled or already loading
        if ($wrapper.attr('data-disabled') === 'true' || $button.prop('disabled') || $button.hasClass('loading')) {
          return;
        }

        // Get data from button
        var feedId = $button.attr('data-feed-id');
        var itemAmount = $button.attr('data-item-amount');
        var imageSize = $button.attr('data-image-size');
        var allowedPostTypes = $button.attr('data-allowed-post-types');
        var nextUrl = $button.attr('data-next-url');
        var nonce = $button.attr('data-nonce');
        if (!nextUrl) {
          $button.hide();
          return;
        }

        // Show loading state
        $button.addClass('loading').prop('disabled', true);
        $wrapper.addClass('loading');
        var originalText = $button.find('.button-text').text();
        $button.find('.button-text').text('Loading...');

        // Make AJAX request
        $.ajax({
          url: wpzInstaAjax.ajaxurl,
          type: 'POST',
          dataType: 'json',
          data: {
            action: 'wpzoom_instagram_load_more',
            feed_id: feedId,
            item_amount: itemAmount,
            image_size: imageSize,
            allowed_post_types: allowedPostTypes,
            next: nextUrl,
            _wpnonce: nonce
          },
          success: function success(response) {
            if (response.success && response.data.html) {
              // Store current item count before adding new items
              var currentItemCount = $itemsContainer.find('li').length;

              // Append new items
              $itemsContainer.append(response.data.html);

              // Update button state
              if (response.data.has_more && response.data.next_url) {
                $button.attr('data-next-url', response.data.next_url);
              } else {
                $button.hide();
              }

              // Reinitialize any image processing for the specific container
              $itemsContainer.zoomLoadAsyncImages();

              // Initialize image loading shimmer for new items
              initImageLoadingShimmer();

              // Check if this is masonry layout and handle accordingly
              if ($itemsContainer.hasClass('layout-masonry')) {
                // Try to use WordPress masonry if available
                if (typeof $.fn.masonry === 'function') {
                  var $newItems = $itemsContainer.find('li').slice(currentItemCount);

                  // Initialize masonry if not already done
                  if (!$itemsContainer.data('masonry')) {
                    $itemsContainer.masonry({
                      itemSelector: '.zoom-instagram-widget__item',
                      columnWidth: '.masonry-items-sizer',
                      percentPosition: true,
                      gutter: parseInt($itemsContainer.data('spacing') || 10)
                    });
                  }

                  // Add new items to masonry
                  $itemsContainer.masonry('appended', $newItems);
                } else {
                  // Fallback for masonry when library not available
                  setTimeout(function () {
                    $itemsContainer.zoomInstagramWidget({
                      onlyNewItems: true,
                      startIndex: currentItemCount
                    });
                  }, 100);
                }
              } else {
                // For grid/other layouts, use existing function
                setTimeout(function () {
                  $itemsContainer.zoomInstagramWidget({
                    onlyNewItems: true,
                    startIndex: currentItemCount
                  });
                }, 100);
              }

              // Handle lightbox content for new items
              if ($itemsContainer.attr('data-lightbox') === '1' && response.data.lightbox_html) {
                // Find the existing lightbox wrapper
                var $lightboxWrapper = $feedContainer.find('.wpz-insta-lightbox-wrapper .swiper-wrapper');
                if ($lightboxWrapper.length > 0) {
                  // Append new lightbox slides
                  $lightboxWrapper.append(response.data.lightbox_html);

                  // Update the existing Swiper instance to recognize new slides
                  var $swiperContainer = $lightboxWrapper.parent();
                  if ($swiperContainer.length > 0 && $swiperContainer.get(0).swiper) {
                    $swiperContainer.get(0).swiper.update();
                  }

                  // Initialize nested Swipers for albums in the newly added content
                  var $allNestedSwipers = $lightboxWrapper.find('.image-wrapper > .swiper');
                  $allNestedSwipers.each(function () {
                    // Only initialize if not already initialized
                    if (!this.swiper) {
                      new Swiper(this, _defineProperty(_defineProperty(_defineProperty(_defineProperty(_defineProperty(_defineProperty(_defineProperty(_defineProperty(_defineProperty(_defineProperty({
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
                    }
                  });

                  // Initialize MagnificPopup on new grid items only
                  var _$newItems = $itemsContainer.find('li').slice(currentItemCount);
                  var $newLinks = _$newItems.find('.zoom-instagram-link');
                  if ($newLinks.length > 0) {
                    $newLinks.magnificPopup({
                      items: {
                        type: 'inline',
                        src: $lightboxWrapper.closest('.wpz-insta-lightbox-wrapper')
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
                            var targetSlideIndex = this.content.find('> .swiper > .swiper-wrapper > .swiper-slide[data-uid="' + currentElement.data('mfp-src') + '"]').index();
                            $thisSwiper.slideTo(targetSlideIndex);
                          }
                        },
                        afterClose: function afterClose() {
                          // Destroy swiper videos
                          $swiperContainer.find('video').each(function () {
                            this.pause();
                            this.currentTime = 0;
                          });
                        }
                      }
                    });
                    $newLinks.addClass('magnific-active');
                  }
                } else {
                  $itemsContainer.zoomLightbox();
                }
              }

              // Trigger custom event for other scripts
              $feedContainer.trigger('wpz-insta:loaded-more', [response.data]);
            } else {
              console.error('Load more failed:', response.data || 'Unknown error');
              $button.hide();
            }
          },
          error: function error(xhr, status, _error) {
            console.error('AJAX load more error:', _error);
            $button.hide();
          },
          complete: function complete() {
            // Remove loading state
            $button.removeClass('loading').prop('disabled', false);
            $wrapper.removeClass('loading');
            $button.find('.button-text').text(originalText);
          }
        });
      });
    }

    // Initialize load more buttons
    initLoadMoreButtons();

    /**
     * AJAX Initial Feed Load functionality
     * Finds placeholder elements and fetches actual content via AJAX
     */
    function initAjaxFeeds() {
      $('.wpz-insta-ajax-placeholder').each(function () {
        var $placeholder = $(this);
        var feedId = $placeholder.data('feed-id');
        // Skip if already loading, loaded, or missing required data
        if (!feedId || $placeholder.hasClass('loading') || $placeholder.hasClass('loaded')) {
          return;
        }
        $placeholder.addClass('loading');
        $.ajax({
          url: wpzInstaAjax.ajaxurl,
          type: 'POST',
          dataType: 'json',
          data: {
            action: 'wpzoom_instagram_initial_load',
            feed_id: feedId
          },
          success: function success(response) {
            if (response.success && response.data.html) {
              // Replace placeholder with actual content
              var $newContent = $(response.data.html);
              $placeholder.replaceWith($newContent);

              // Find the new feed container
              var $newFeed = $newContent.hasClass('zoom-instagram') ? $newContent : $newContent.find('.zoom-instagram');
              if ($newFeed.length > 0) {
                // Initialize the Instagram widget functionality
                var $itemsContainer = $newFeed.find('.zoom-instagram-widget__items');
                if ($itemsContainer.length > 0) {
                  // Check if this is a carousel layout
                  var isCarousel = $newFeed.hasClass('layout-carousel');
                  if (isCarousel) {
                    // Initialize Swiper for carousel layout
                    // The swiper element is .zoom-instagram-widget__items-wrapper.swiper
                    var $swiperEl = $newFeed.find('> .zoom-instagram-widget__items-wrapper.swiper');
                    if ($swiperEl.length > 0 && typeof Swiper !== 'undefined') {
                      // data-perpage is on the ul.swiper-wrapper element
                      var perpage = parseInt($itemsContainer.data('perpage')) || 3;
                      var perpageTablet = parseInt($itemsContainer.data('perpage-tablet')) || perpage;
                      var perpageMobile = parseInt($itemsContainer.data('perpage-mobile')) || perpage;
                      var spacing = parseFloat($itemsContainer.data('spacing')) || 20;

                      // Initialize lazy loading BEFORE Swiper so images start loading
                      if (typeof $.fn.lazy === 'function') {
                        $itemsContainer.find('.zoom-instagram-link-new').lazy();
                        $itemsContainer.find('a.zoom-instagram-link-old').lazy();
                      }

                      // Create Swiper instance
                      var carouselSwiper = new Swiper($swiperEl.get(0), {
                        direction: 'horizontal',
                        loop: false,
                        slidesPerView: perpage,
                        spaceBetween: spacing,
                        breakpoints: {
                          320: {
                            slidesPerView: perpageMobile,
                            spaceBetween: spacing
                          },
                          480: {
                            slidesPerView: perpageTablet,
                            spaceBetween: spacing
                          },
                          769: {
                            slidesPerView: perpage,
                            spaceBetween: spacing
                          }
                        },
                        autoHeight: true,
                        watchOverflow: true,
                        navigation: {
                          nextEl: $swiperEl.find('> .swiper-button-next').get(0),
                          prevEl: $swiperEl.find('> .swiper-button-prev').get(0)
                        },
                        keyboard: {
                          enabled: true,
                          onlyInViewport: true
                        }
                      });
                      $newFeed.addClass('carousel-active');

                      // Update Swiper after images load to fix autoHeight
                      var $images = $itemsContainer.find('img');
                      var loadedCount = 0;
                      var totalImages = $images.length;
                      if (totalImages > 0) {
                        $images.each(function () {
                          var img = this;
                          if (img.complete) {
                            loadedCount++;
                            if (loadedCount === totalImages) {
                              carouselSwiper.update();
                            }
                          } else {
                            $(img).on('load error', function () {
                              loadedCount++;
                              if (loadedCount === totalImages) {
                                carouselSwiper.update();
                              }
                            });
                          }
                        });
                        // Fallback: update after a delay in case load events don't fire
                        setTimeout(function () {
                          carouselSwiper.update();
                        }, 500);
                      }
                    }
                  } else if ($itemsContainer.hasClass('layout-masonry') && typeof $.fn.masonry === 'function') {
                    // Initialize masonry layout
                    $itemsContainer.masonry({
                      itemSelector: '.zoom-instagram-widget__item',
                      columnWidth: '.masonry-items-sizer',
                      percentPosition: true,
                      gutter: parseInt($itemsContainer.data('spacing') || 10)
                    });
                  } else {
                    // Initialize grid layout
                    $itemsContainer.zoomInstagramWidget();
                  }

                  // Load async images
                  $itemsContainer.zoomLoadAsyncImages();

                  // Initialize lightbox if enabled
                  if ($itemsContainer.attr('data-lightbox') === '1') {
                    $itemsContainer.zoomLightbox();
                  }
                }

                // Reinitialize load more buttons (free plugin button-based)
                initLoadMoreButtons();

                // Initialize image loading shimmer for new content
                initImageLoadingShimmer();

                // Initialize frontend (lightbox swipers from block.js)
                if (typeof window.wpzInstaFrontendInit === 'function') {
                  window.wpzInstaFrontendInit();
                }

                // Initialize stories (single account)
                if (typeof window.wpzInstaInitStories === 'function') {
                  window.wpzInstaInitStories();
                }

                // Initialize multi-account stories (PRO)
                if (typeof window.wpzInstaMultiAccountStoriesInit === 'function') {
                  window.wpzInstaMultiAccountStoriesInit();
                }

                // Initialize multi-account load more (PRO)
                if (typeof window.wpzInstaMultiAccountLoadMoreInit === 'function') {
                  window.wpzInstaMultiAccountLoadMoreInit();
                }

                // Initialize PRO form-based load more (single account)
                if (typeof window.wpzInstaProLoadMoreInit === 'function') {
                  window.wpzInstaProLoadMoreInit();
                }

                // Trigger custom event for other scripts
                $newFeed.trigger('wpz-insta:ajax-loaded', [feedId, response.data]);
              }
            } else {
              console.error('Failed to load Instagram feed:', response.data || 'Unknown error');
              $placeholder.removeClass('loading').addClass('error');
            }
          },
          error: function error(xhr, status, _error2) {
            console.error('AJAX initial feed load error:', _error2);
            $placeholder.removeClass('loading').addClass('error');
          }
        });
      });
    }

    // Initialize AJAX feeds on page load
    initAjaxFeeds();
    $.fn.zoomLoadAsyncImages = function () {
      return $(this).each(function () {
        var $list = $(this);
        var desiredItemWidth = $list.data('image-width');
        var imageResolution = $list.data('image-resolution');
        var delayedItems = $list.find('li').filter(function () {
          return $(this).data('media-id');
        }).map(function () {
          return {
            'media-id': $(this).attr('data-media-id'),
            'nonce': $(this).attr('data-nonce'),
            'regenerate-thumbnails': $(this)[0].hasAttribute("data-regenerate-thumbnails")
          };
        });

        // Process images in parallel batches for better performance
        var processImageBatch = function processImageBatch(images, batchSize) {
          batchSize = batchSize || 3; // Process 3 images at once

          for (var i = 0; i < images.length; i += batchSize) {
            var batch = images.slice(i, i + batchSize);
            batch.forEach(function (image) {
              wp.ajax.post('wpzoom_instagram_get_image_async', {
                'media-id': image['media-id'],
                nonce: image['nonce'],
                'image-resolution': imageResolution,
                'image-width': desiredItemWidth,
                'regenerate-thumbnails': image['regenerate-thumbnails']
              }).done(function (data) {
                $list.find('li[data-media-id="' + image['media-id'] + '"] .zoom-instagram-link').css('background-image', 'url(' + data.image_src + ')');
              }).fail(function () {
                // Silently fail for missing images
              });
            });
          }
        };
        if (delayedItems.length) {
          processImageBatch(delayedItems.toArray(), 3);
        }
      });
    };
    $.fn.zoomLightbox = function () {
      return $(this).each(function () {
        // Try multiple strategies to find the lightbox wrapper
        var $swipe_el = $(this).closest('.widget').find('.wpz-insta-lightbox-wrapper > .swiper');

        // Fallback 1: Look in the same Instagram widget container
        if ($swipe_el.length === 0) {
          $swipe_el = $(this).closest('.zoom-instagram').find('.wpz-insta-lightbox-wrapper > .swiper');
        }

        // Fallback 2: Look for siblings or nearby elements
        if ($swipe_el.length === 0) {
          $swipe_el = $(this).parent().parent().find('.wpz-insta-lightbox-wrapper > .swiper');
        }

        // Debug: Check if lightbox wrapper has content
        var $lightboxWrapper = $swipe_el.closest('.wpz-insta-lightbox-wrapper');
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

              // Initialize product carousel in the active slide
              if (typeof window.wpzInstaInitProductCarousel === 'function') {
                window.wpzInstaInitProductCarousel($activeSlide);
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

          // Find the gallery trigger using the same strategy as swiper element
          var galleryTrigger = $(this).closest('.widget').find('.zoom-instagram-widget__items');

          // Fallback 1: Look in the same Instagram widget container
          if (galleryTrigger.length === 0) {
            galleryTrigger = $(this).closest('.zoom-instagram').find('.zoom-instagram-widget__items');
          }

          // Fallback 2: Use this element directly if it's the items container
          if (galleryTrigger.length === 0) {
            galleryTrigger = $(this);
          }

          // Use the same approach as block.js - call magnificPopup directly on the links
          galleryTrigger.find('.zoom-instagram-link').magnificPopup({
            items: {
              type: 'inline',
              src: $swipe_el.closest('.wpz-insta-lightbox-wrapper')
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

                // Initialize product carousel in the current slide
                var $currentSlide = this.content.find('> .swiper > .swiper-wrapper > .swiper-slide[data-uid="' + currentElement.data('mfp-src') + '"]');
                if (typeof window.wpzInstaInitProductCarousel === 'function') {
                  window.wpzInstaInitProductCarousel($currentSlide);
                }
              },
              afterClose: function afterClose() {
                // Destroy swiper videos
                $swipe_el.find('video').each(function () {
                  this.pause();
                  this.currentTime = 0;
                });
              }
            }
          });
          galleryTrigger.find('.zoom-instagram-link').addClass('magnific-active');
        }
      });
    };

    // Initialize product card carousel in lightbox
    function initProductCarousel($container) {
      var $carousels = $container.find('.wpz-insta-lightbox-product--carousel');
      $carousels.each(function () {
        var $carousel = $(this);

        // Skip if already initialized
        if ($carousel.data('carousel-initialized')) {
          return;
        }
        var $inner = $carousel.find('.wpz-insta-lightbox-product__carousel-inner');
        var $cards = $inner.find('.wpz-insta-lightbox-product__card');
        var $prevBtn = $carousel.find('.wpz-insta-lightbox-product__carousel-prev');
        var $nextBtn = $carousel.find('.wpz-insta-lightbox-product__carousel-next');
        var $dotsContainer = $carousel.find('.wpz-insta-lightbox-product__carousel-dots');
        if ($cards.length <= 1) {
          $prevBtn.hide();
          $nextBtn.hide();
          $dotsContainer.hide();
          return;
        }
        var currentIndex = 0;
        var totalSlides = $cards.length;

        // Create dots
        $dotsContainer.empty();
        var _loop = function _loop(i) {
          var $dot = $('<span class="wpz-insta-lightbox-product__carousel-dot"></span>');
          if (i === 0) {
            $dot.addClass('active');
          }
          $dot.on('click', function () {
            goToSlide(i);
          });
          $dotsContainer.append($dot);
        };
        for (var i = 0; i < totalSlides; i++) {
          _loop(i);
        }
        function updateCarousel() {
          $inner.css('transform', 'translateX(-' + currentIndex * 100 + '%)');

          // Update dots
          $dotsContainer.find('.wpz-insta-lightbox-product__carousel-dot').removeClass('active').eq(currentIndex).addClass('active');

          // Update buttons
          $prevBtn.prop('disabled', currentIndex === 0);
          $nextBtn.prop('disabled', currentIndex === totalSlides - 1);
        }
        function goToSlide(index) {
          if (index >= 0 && index < totalSlides) {
            currentIndex = index;
            updateCarousel();
          }
        }
        $prevBtn.on('click', function () {
          if (currentIndex > 0) {
            goToSlide(currentIndex - 1);
          }
        });
        $nextBtn.on('click', function () {
          if (currentIndex < totalSlides - 1) {
            goToSlide(currentIndex + 1);
          }
        });

        // Initialize
        updateCarousel();
        $carousel.data('carousel-initialized', true);
      });
    }

    // Expose globally for use in lightbox callbacks
    window.wpzInstaInitProductCarousel = initProductCarousel;
    $.fn.zoomInstagramWidget = function (options) {
      return $(this).each(function () {
        var $list = $(this);
        var opts = options || {};
        var minItemsPerRow = $list.data('images-per-row');
        var desiredItemWidth = $list.data('image-width');
        var itemSpacing = $list.data('image-spacing');
        var imageLazyLoading = $list.data('image-lazy-loading');
        var containerWidth = $list.width();
        var fitPerRow;
        var itemWidth;
        if (containerWidth / desiredItemWidth < minItemsPerRow) {
          fitPerRow = minItemsPerRow;
          itemWidth = Math.floor((containerWidth - 1 - (minItemsPerRow - 1) * itemSpacing) / minItemsPerRow);
        } else {
          fitPerRow = Math.floor((containerWidth - 1) / desiredItemWidth);
          itemWidth = Math.floor((containerWidth - 1 - (fitPerRow - 1) * itemSpacing) / fitPerRow);
        }

        // If onlyNewItems is specified, only process items from that index onwards
        var $itemsToProcess = opts.onlyNewItems && opts.startIndex !== undefined ? $list.find('li').slice(opts.startIndex) : $list.find('li');
        $itemsToProcess.each(function (relativeIndex) {
          // Calculate the global index (position among all items)
          var globalIndex = opts.onlyNewItems && opts.startIndex !== undefined ? opts.startIndex + relativeIndex : relativeIndex;
          var loop = globalIndex + 1; // 1-based indexing

          if (loop % fitPerRow == 1) {
            $(this).css('clear', 'left');
          } else {
            $(this).css('clear', 'none');
          }
          if (loop % fitPerRow == 0) {
            $(this).css('margin-right', '0');
          } else {
            $(this).css('margin-right', itemSpacing + 'px');
            $(this).css('margin-bottom', itemSpacing + 'px');
          }
        });

        // Always update link dimensions for all items
        $list.find('a.zoom-instagram-link').css({
          width: itemWidth,
          height: itemWidth
        });

        // if (imageLazyLoading) {
        $list.find('a.zoom-instagram-link-old').lazy();
        $list.find('.zoom-instagram-link-new').lazy();
        // }

        $list.removeClass('zoom-instagram-widget__items--no-js');
      });
    };
    function requestTick() {
      if (!ticking) {
        ticking = true;
        requestAnimationFrame()(update);
      }
    }
    function requestAnimationFrame() {
      return window.requestAnimationFrame || window.webkitRequestAnimationFrame || window.mozRequestAnimationFrame || function (callback) {
        window.setTimeout(callback, 1000 / 60);
      };
    }
    function update() {
      $('.zoom-instagram-widget__items').each(function () {
        var $container = $(this);
        if ($container.hasClass('layout-masonry')) {
          // Try to use WordPress masonry if available
          if (typeof $.fn.masonry === 'function') {
            // Initialize masonry for masonry layouts
            if (!$container.data('masonry')) {
              $container.masonry({
                itemSelector: '.zoom-instagram-widget__item',
                columnWidth: '.masonry-items-sizer',
                percentPosition: true,
                gutter: parseInt($container.data('spacing') || 10)
              });
            } else {
              // Refresh masonry layout
              $container.masonry('layout');
            }
          } else {
            // Fallback for masonry when library not available
            $container.zoomInstagramWidget();
          }
        } else {
          // Use existing grid function for non-masonry layouts
          $container.zoomInstagramWidget();
        }
      });
      ticking = false;
    }
    $(window).on('resize orientationchange', requestTick);

    // Initialize layouts on page load
    $('.zoom-instagram-widget__items').each(function () {
      var $container = $(this);
      if ($container.hasClass('layout-masonry')) {
        // Try to use WordPress masonry if available
        if (typeof $.fn.masonry === 'function') {
          // Initialize masonry for masonry layouts
          $container.masonry({
            itemSelector: '.zoom-instagram-widget__item',
            columnWidth: '.masonry-items-sizer',
            percentPosition: true,
            gutter: parseInt($container.data('spacing') || 10)
          });
        } else {
          // Fallback for masonry when library not available
          $container.zoomInstagramWidget();
        }
      } else {
        // Use existing grid function for non-masonry layouts
        $container.zoomInstagramWidget();
      }
    });
    $('.zoom-instagram-widget__items').zoomLoadAsyncImages();
    $('.zoom-instagram-widget__items[data-lightbox="1"]').zoomLightbox();
    var siteOriginInit = function siteOriginInit() {
      var $widgets = $('.zoom-instagram-widget__items');
      if ($widgets.length) {
        $widgets.each(function () {
          var $container = $(this);
          if ($container.hasClass('layout-masonry')) {
            // Try to use WordPress masonry if available
            if (typeof $.fn.masonry === 'function') {
              // Initialize masonry for masonry layouts
              $container.masonry({
                itemSelector: '.zoom-instagram-widget__item',
                columnWidth: '.masonry-items-sizer',
                percentPosition: true,
                gutter: parseInt($container.data('spacing') || 10)
              });
            } else {
              // Fallback for masonry when library not available
              $container.zoomInstagramWidget();
            }
          } else {
            // Use existing grid function for non-masonry layouts
            $container.zoomInstagramWidget();
          }
        });
        $('.zoom-instagram-widget__items').zoomLoadAsyncImages();
      }
    };
    var debounceInit = _.debounce(siteOriginInit, 1500);
    $(document).on('panels_setup_preview', debounceInit);

    // Re-initialize load more buttons when new content is added dynamically
    $(document).on('wpz-insta:loaded-more', function () {
      initLoadMoreButtons();
    });
    $(window).on('elementor/frontend/init', function () {
      elementorFrontend.hooks.addAction('frontend/element_ready/widget', function ($scope) {
        if ($scope.data('widget_type') == "wpzoom-elementor-instagram-widget.default") {
          // Handle both masonry and regular layouts for Elementor
          $scope.find('.zoom-instagram-widget__items').each(function () {
            var $container = $(this);
            if ($container.hasClass('layout-masonry')) {
              // Try to use WordPress masonry if available
              if (typeof $.fn.masonry === 'function') {
                // Initialize masonry for masonry layouts
                $container.masonry({
                  itemSelector: '.zoom-instagram-widget__item',
                  columnWidth: '.masonry-items-sizer',
                  percentPosition: true,
                  gutter: parseInt($container.data('spacing') || 10)
                });
              } else {
                // Fallback for masonry when library not available
                $container.zoomInstagramWidget();
              }
            } else {
              // Use existing grid function for non-masonry layouts
              $container.zoomInstagramWidget();
            }
          });
        }
      });
    });
  });
})(jQuery);

// WooCommerce Product Link: notify parent when "Link to a product" is clicked (we're inside iframe)
document.body.addEventListener('click', function (event) {
  var btn = event.target.closest('.wpz-insta-link-product-btn');
  if (!btn) {
    return;
  }
  event.preventDefault();
  event.stopPropagation();
  if (typeof window.parent.postMessage === 'function') {
    window.parent.postMessage({
      action: 'wpz-insta-open-product-link',
      mediaId: btn.getAttribute('data-media-id') || '',
      feedId: btn.getAttribute('data-feed-id') || '',
      productId: btn.getAttribute('data-product-id') || ''
    }, '*');
  }
}, true);
/******/ })()
;
//# sourceMappingURL=index.js.map