jQuery(document).ready(function( $ ) {
    // change of thumb
    $(".lw_product_swatches a[data-image], .lw_product_swatches span[data-image]").each(function() {
        if( $(this).data("image").length > 0 ) {
            $(this).on({
                mouseenter: function () {
                    let imgEl = $(this).parents('.product, .wc-block-grid__product').first().find("img.attachment-woocommerce_thumbnail, img.woocommerce-placeholder, img.wp-post-image");
                    if (imgEl.length === 0) {
                        imgEl = $(".single-product img.wp-post-image");
                    }

                    // secure original values
                    imgEl.attr("data-original-src", imgEl.attr("src"));
                    imgEl.attr("data-original-srcset", imgEl.attr("srcset"));
                    // set the new thumb
                    imgEl.attr("src", $(this).data("image"));
                    imgEl.attr("srcset", $(this).data("image-srcset"));
                },
                mouseleave: function () {
                    let imgEl = $(this).parents('.product, .wc-block-grid__product').first().find("img.attachment-woocommerce_thumbnail, img.woocommerce-placeholder, img.wp-post-image");
                    if (imgEl.length === 0) {
                        imgEl = $(".single-product img.wp-post-image");
                    }
                    // reset the old thumb
                    imgEl.attr("src", imgEl.attr("data-original-src"));
                    imgEl.attr("srcset", imgEl.attr("data-original-srcset"));
                }
            });
        }
    });

    // change the sales-badge
    $(".lw_product_swatches a[data-sale], .lw_product_swatches span[data-sale]").each(function() {
        $(this).on({
            mouseenter: function() {
                let onSaleEl = $(this).parents('.product, .wc-block-grid__product').first().find('span.onsale');
                if( onSaleEl.length === 0 ) {
                    // get elements
                    let aEl = $(this).parents('.product, .wc-block-grid__product').first().find('a.woocommerce-loop-product__link');
                    aEl.append('<span class="onsale">Sale!</span>');
                    onSaleEl = $(this).parents('.product, .wc-block-grid__product').first().find('span.onsale');
                }
                if ($(this).data("sale") === 1) {
                    onSaleEl.show();
                } else {
                    onSaleEl.hide();
                }
            },
            mouseleave: function() {
                let onSaleEl = $(this).parents('.product, .wc-block-grid__product').first().find('span.onsale');
                if( onSaleEl.length === 0 ) {
                    // get elements
                    let aEl = $(this).parents('.product, .wc-block-grid__product').first().find('a.woocommerce-loop-product__link');
                    aEl.append('<span class="onsale">Sale!</span>');
                    onSaleEl = $(this).parents('.product, .wc-block-grid__product').first().find('span.onsale');
                }
                if ($(this).data("sale") === 1) {
                    onSaleEl.show();
                } else {
                    onSaleEl.hide();
                }
            }
        });
    });
});
