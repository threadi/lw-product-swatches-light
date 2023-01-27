<?php

/**
 * File with WooCommerces-specific handlings.
 */

/**
 * Each time a product is inserted or updated, update its Product Swatches Cache
 * which are then displayed in the category loop.
 */

use LW_Swatches\helper;
use LW_Swatches\Product;

/**
 * Set hooks where swatches of single products will be updated directly.
 */
add_action( 'woocommerce_new_product', 'LW_Swatches\Product::update2', 10, 2 );
add_action( 'woocommerce_update_product', 'LW_Swatches\Product::update2', 10, 2 );
add_action( 'woocommerce_product_set_stock', 'LW_Swatches\Product::update', 10, 1 );
add_action( 'woocommerce_variation_set_stock', 'LW_Swatches\Product::update', 10, 1 );

/**
 * Initialize plugin-settings-tab in WooCommerce-settings
 */
LW_Swatches\WC_Settings_Tab::init();

/**
 * Set position where our swatches on the listing will be visible.
 *
 * @return void
 * @noinspection PhpUnused
 */
function lw_swatches_list_position() {
    if( !is_single() ) {
        switch (get_option('wc_' . LW_SWATCH_WC_SETTING_NAME . '_position_in_list', 'afterprice')) {
            case 'beforecart':
            case 'aftercart':
                add_action('woocommerce_loop_add_to_cart_link', 'lw_swatches_add_product_swatches_in_loop', PHP_INT_MAX, 2);
                break;
            case 'beforeprice':
                add_action('woocommerce_after_shop_loop_item_title', 'lw_swatches_add_product_swatches_in_loop_end', 5, 0);
                break;
            case 'afterprice':
                add_action('woocommerce_after_shop_loop_item_title', 'lw_swatches_add_product_swatches_in_loop_after_prices', 20, 0);
                break;
            default:
                add_action('woocommerce_loop_add_to_cart_link', 'lw_swatches_add_product_swatches_in_loop', 10, 2);
                break;
        }
    }
}
add_action( 'wp', 'lw_swatches_list_position', 10);

/**
 * Add "generate_product_swatches"-button in WooCommerce settings.
 *
 * @param $value
 * @return void
 * @noinspection PhpUnused
 */
function lw_generate_product_swatches_button( $value ) {
    $url = add_query_arg(
        [
            'page' => 'wc-settings',
            'tab' => 'lw_product_swatches',
            'generateLWSwatches' => '1'
        ],
        get_admin_url() . 'admin.php'
    );
    ?><a href="<?php echo esc_url(wp_nonce_url($url, 'lws-generate')); ?>" class="button button-large lw-update-swatches"><?php _e('Regenerate all swatches', 'lw-product-swatches'); ?></a> (<i><?php _e('takes a moment', 'lw-product-swatches'); ?></i>)<?php
}
add_action( 'woocommerce_admin_field_generate_product_swatches', 'lw_generate_product_swatches_button' );

/**
 * Show product swatches on the product in listings above or under the cart-button.
 *
 * @param $add_to_cart_html
 * @param $product
 * @return string
 * @noinspection PhpUnused
 */
function lw_swatches_add_product_swatches_in_loop($add_to_cart_html, $product): string
{
    // if this is a variation get its parent for swatches
    if($product instanceof WC_Product_Variation) {
        $product = wc_get_product($product->get_parent_id());
    }

    // get the code depending on cache-setting
    if( get_option('wc_'.LW_SWATCH_WC_SETTING_NAME.'_disable_cache', 'no') == 'yes' ) {
        $code = Product::getSwatches( $product );
    }
    else {
        $code = get_post_meta($product->get_id(), LW_SWATCH_CACHEKEY, true);
    }

    // set code on configured position in relation to the card-button
    $after = '';
    $before = '';
    if( get_option('wc_'.LW_SWATCH_WC_SETTING_NAME.'_position_in_list', 'afterprice') == 'beforecart' ) {
        $before = $code;
    }
    else {
        $after = $code;
    }

    // return result
    return $before . $add_to_cart_html . $after;
}

/**
 * Show product swatches on the product in listings at the end of the product-loop above prices.
 *
 * @return void
 * @noinspection PhpUnused
 */
function lw_swatches_add_product_swatches_in_loop_end(): void
{
    $product = wc_get_product(get_the_ID());
    if($product instanceof WC_Product) {
        echo lw_swatches_add_product_swatches_in_loop('', $product);
    }
}

/**
 * Show product swatches on the product in listings at the end of the product-loop after prices.
 *
 * @return void
 * @noinspection PhpUnused
 */
function lw_swatches_add_product_swatches_in_loop_after_prices(): void
{
    $product = wc_get_product(get_the_ID());
    if($product instanceof WC_Product) {
        echo lw_swatches_add_product_swatches_in_loop('', $product);
    }
}

/**
 * Add Swatches in Gutenberg-Block for single product.
 *
 * @param $html
 * @param $data
 * @param $product
 * @return mixed|string
 * @noinspection PhpUnused
 */
function lw_swatches_add_in_block( $html, $data, $product )
{
    if( $product->get_type() == 'variable' ) {
        if( get_option('wc_'.LW_SWATCH_WC_SETTING_NAME.'_disable_cache', 'no') == 'yes' ) {
            $productSwatches = Product::getSwatches( $product );
        }
        else {
            $productSwatches = get_post_meta($product->get_id(), LW_SWATCH_CACHEKEY, true);
        }

        if( !empty($productSwatches) ) {
            // prepare output
            $html = '<li class="wc-block-grid__product">
				<a href="' . $data->permalink . '" class="wc-block-grid__product-link">
					' . $data->image . '
					' . $data->title . '
				</a>
				' . $data->badge . '
				' . $data->price . '
				' . $data->rating . '
				' . $productSwatches . '
				' . $data->button . '
			</li>';
        }
    }
    return $html;
}
add_filter( 'woocommerce_blocks_product_grid_item_html', 'lw_swatches_add_in_block', 10, 3);

/**
 * Run if link to update the swatches of a single product has been called.
 *
 * @return void
 * @noinspection PhpUnused
 */
function lw_swatches_run_product_action() {
    if ( empty( $_REQUEST['post'] ) ) {
        wp_die( esc_html__( 'No product has been supplied!', 'lw-product-swatches' ) );
    }

    // get product id
    $product_id = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : 0;

    // check nonce
    check_admin_referer( 'woocommerce-lws-resetswatches_' . $product_id );

    // get the product
    $product = wc_get_product( $product_id );

    // update the swatches
    Product::update($product);

    // show success-message
    set_transient( 'lw_swatches_resetted', true, 0 );

    // Redirect to the edit screen for the new draft page.
    wp_redirect( admin_url( 'post.php?action=edit&post=' . $product_id ) );
    exit;
}
add_action( 'admin_action_lws_resetswatches', 'lw_swatches_run_product_action');

/**
 * Add bulk action to regenerate multiple swatches via product-table in backend.
 *
 * @param $actions
 * @return mixed
 * @noinspection PhpUnused
 */
function lw_swatches_add_bulk_actions( $actions ) {
    $actions['lws-generate-swatches'] = __( 'Swatches generieren', 'lw-product-swatches' );
    return $actions;
}
add_filter( 'bulk_actions-edit-product', 'lw_swatches_add_bulk_actions', 20, 1 );

/**
 * Run bulk aktion to regenerate multiple swatches via product-table in backend.
 *
 * @param $redirect_to
 * @param $action
 * @param $post_ids
 * @return void
 * @noinspection PhpUnused
 * @noinspection PhpUnusedParameterInspection
 */
function lw_swatches_run_bulk_actions( $redirect_to, $action, $post_ids ) {
    foreach($post_ids as $post_id ) {
        $product = wc_get_product( $post_id );
        Product::update($product);
    }

    // set transient to show success
    set_transient( 'lw_swatches_bulk_done', true );

    // return the redirect-url
    return $redirect_to;
}
add_filter( 'handle_bulk_actions-edit-product', 'lw_swatches_run_bulk_actions', 10, 3 );

/**
 * Add link to reset the swatches of a single product in product-edit-page.
 *
 * @param $post
 * @return void
 * @noinspection PhpUnused
 */
function lw_swatches_add_product_action( $post ) {
    if ( 'product' !== $post->post_type ) {
        return;
    }

    $product = wc_get_product($post->ID);
    if( $product->get_type() == 'variable' ) {
        $url = wp_nonce_url( admin_url( 'edit.php?post_type=product&action=lws_resetswatches&post=' . absint( $post->ID ) ), 'woocommerce-lws-resetswatches_' . $post->ID );
        /* translators: %1$s is replaced with "string" */
        echo '<div class="misc-pub-section">'.sprintf(__('<a href="%s">Save swatches</a> of this product', 'lw-product-swatches'), esc_url($url)).'</div>';
    }
}
add_filter( 'post_submitbox_misc_actions', 'lw_swatches_add_product_action', 10, 1);

/**
 * Add additional attribute types which are used by this plugin.
 * Only if they do not already exist.
 *
 * @param $attributeType
 * @return array
 * @noinspection PhpUnused
 */
function lw_swatches_add_attribute_types( $attributeType ): array
{
    foreach( helper::getAttributeTypes() as $key => $attribute ) {
        if( empty($attributeType[$key]) ) {
            $attributeType[$key] = $attribute['label'];
        }
    }
    return $attributeType;
}
add_filter('product_attributes_type_selector', 'lw_swatches_add_attribute_types', 10, 1);
