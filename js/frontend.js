jQuery(document).ready(function( $ ) {
    // thumb-change
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
    // sale change
    $(".lw_product_swatches a[data-sale], .lw_product_swatches span[data-sale]").each(function() {
        if( $(this).data("sale") === 1 ) {
            console.log("aaaa");
        }
        else {
            console.log("bbb");
        }
    });
});
