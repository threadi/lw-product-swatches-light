<?php

use LW_Swatches\helper;

/**
 * Add own CSS and JS for backend.
 *
 * @return void
 * @noinspection PhpUnused
 */
function lw_swatches_add_styles_and_js_admin() {
    // admin-specific styles
    wp_enqueue_style('lw-swatches-admin-css',
        plugin_dir_url(LW_SWATCHES_PLUGIN) . '/admin/styles.css',
        [],
        filemtime(plugin_dir_path(LW_SWATCHES_PLUGIN) . '/admin/styles.css'),
    );

    lw_swatches_add_styles_and_js_frontend();

    // backend-JS
    wp_enqueue_script( 'lw-swatches-admin-js',
        plugins_url( '/admin/js.js' , LW_SWATCHES_PLUGIN ),
        ['jquery'],
        filemtime(plugin_dir_path(LW_SWATCHES_PLUGIN) . '/admin/js.js'),
    );

    // embed necessary scripts for progressbar only in settings-page
    if( !empty($_GET["tab"]) && $_GET["tab"] === 'lw_product_swatches' ) {
        // add php-vars to our js-script
        wp_localize_script( 'lw-swatches-admin-js', 'lwProductSwatchesVars', [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'run_update_nonce' => wp_create_nonce( 'lw-swatches-update-run' ),
                'get_update_nonce' => wp_create_nonce( 'lw-swatches-update-info' ),
                'label_run_update' => __('Generate now', 'lw-product-swatches'),
                'label_update_is_running' => __('Update of swatches is running', 'lw-product-swatches'),
                'txt_please_wait' => __('Please wait', 'lw-product-swatches'),
                'txt_update_hint' => __('Performing the update could take a few minutes.', 'lw-product-swatches'),
                'txt_update_has_been_run' => __('<strong>The update has been run.</strong> Please check the product categories in frontend.', 'lw-product-swatches'),
                'label_ok' => __('OK', 'lw-product-swatches')
            ]
        );
        wp_enqueue_script('jquery-ui-progressbar');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style(
            'lw-swatches-jquery-ui-styles',
            plugins_url( '/lib/jquery/jquery-ui.min.css' , LW_SWATCHES_PLUGIN ),
            false,
            filemtime(plugin_dir_path(LW_SWATCHES_PLUGIN) . '/lib/jquery/jquery-ui.min.css'),
            false
        );
    }
}
add_action( 'admin_enqueue_scripts', 'lw_swatches_add_styles_and_js_admin', PHP_INT_MAX );

/**
 * Get all WooCommerce attributes and add actions to handle them.
 * Also add the processing of requests for attributes in the backend.
 *
 * This is the main function to start the plugin-magic in admin.
 *
 * @return void
 * @noinspection PhpUnused
 */
function lw_swatches_attribute_handling(): void
{
    if( lw_swatches_is_woocommerce_activated() ) {
        // get all attributes and add action on them
        $attributes = wc_get_attribute_taxonomies();
        $attribute_types = helper::getAttributeTypes();
        $keys = array_keys($attributes);
        for( $a=0;$a<count($attributes);$a++ ) {
            if (!empty($attribute_types[$attributes[$keys[$a]]->attribute_type])) {
                $fields = $attribute_types[$attributes[$keys[$a]]->attribute_type]['fields'];
                new LW_Swatches\Attribute($attributes[$keys[$a]], $fields);
            }
        }

        // generate all swatches on request
        if (!empty($_GET["generateLWSwatches"]) && absint($_GET["generateLWSwatches"]) == 1 && check_admin_referer('lws-generate') ) {
            // update them
            helper::updateSwatchesOnProducts();

            // redirect user
            wp_redirect($_SERVER['HTTP_REFERER']);
        }
    }
}
add_action('admin_init', 'lw_swatches_attribute_handling');

/**
 * Add AJAX-endpoints.
 */
add_action( 'admin_init', function() {
    add_action('wp_ajax_nopriv_lw_swatches_import_run', 'lw_swatches_import_run');
    add_action('wp_ajax_lw_swatches_import_run', 'lw_swatches_import_run');

    add_action('wp_ajax_nopriv_lw_swatches_import_info', 'lw_swatches_import_info');
    add_action('wp_ajax_lw_swatches_import_info', 'lw_swatches_import_info');
});

/**
 * Start updates via AJAX.
 *
 * @return void
 * @noinspection PhpUnused
 */
function lw_swatches_import_run() {
    // check nonce
    check_ajax_referer( 'lw-swatches-update-run', 'nonce' );

    // run import
    helper::updateSwatchesOnProducts();

    // return nothing
    wp_die();
}

/**
 * Return state of the actual running update via AJAX.
 *
 * @return void
 * @noinspection PhpUnused
 */
function lw_swatches_import_info() {
    // check nonce
    check_ajax_referer( 'lw-swatches-update-info', 'nonce' );

    // return actual and max count of import steps
    echo absint(get_option(LW_SWATCHES_OPTION_COUNT, 0)).";".absint(get_option(LW_SWATCHES_OPTION_MAX)).";".absint(get_option(LW_SWATCHES_UPDATE_RUNNING, 0));

    // return nothing else
    wp_die();
}

/**
 * Add link to plugin-settings in plugin-list.
 *
 * @param $links
 * @return array
 * @noinspection PhpUnused
 */
function lw_swatches_admin_add_setting_link( $links ): array
{
    if( is_plugin_active( plugin_basename(LW_SWATCHES_PLUGIN) ) ) {
        // build and escape the URL
        $url = add_query_arg(
            [
                'page' => 'wc-settings',
                'tab' => 'lw_product_swatches'
            ],
            get_admin_url() . 'admin.php'
        );

        // create the link
        $settings_link = "<a href='" . esc_url($url) . "'>" . __('Settings', 'lw-product-swatches') . '</a>';

        // adds the link to the end of the array
        $links[] = $settings_link;
    }

    return $links;
}
add_filter( 'plugin_action_links_lw-product-swatches-light/lw-product-swatches-light.php', 'lw_swatches_admin_add_setting_link' );