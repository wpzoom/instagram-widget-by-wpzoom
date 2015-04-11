jQuery(function($) {
    $.fn.zoomInstagramWidget = function () {
        return $(this).each(function () {
            var $list = $(this);

            var minItemsPerRow   = $list.data('images-per-row');
            var desiredItemWidth = $list.data('image-width');
            var itemSpacing      = $list.data('image-spacing');

            var fitPerRow;
            var itemWidth;

            if ($list.width() / desiredItemWidth < minItemsPerRow) {
                fitPerRow = minItemsPerRow;
                itemWidth = Math.floor(($list.width() - 1 - (minItemsPerRow - 1) * itemSpacing) / minItemsPerRow);
            } else {
                fitPerRow = Math.floor(($list.width() - 1) / desiredItemWidth);
                itemWidth = Math.floor(($list.width() - 1 - (fitPerRow - 1) * itemSpacing) / fitPerRow);
            }

            $list.find('li').each(function(i) {
                if ( ++i % fitPerRow == 0 ) {
                    $(this).css('margin-right', '0');
                } else {
                    $(this).css('margin-right', itemSpacing + 'px');
                    $(this).css('margin-bottom', itemSpacing + 'px');
                }
            });

            $list.find('img').width(itemWidth);
            $list.removeClass('zoom-instagram-widget__items--no-js');
        });
    };

    $(window).on('resize orientationchange', function() {
        $('.zoom-instagram-widget__items').zoomInstagramWidget();
    });

    $('.zoom-instagram-widget__items').zoomInstagramWidget();
});
