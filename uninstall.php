<?php

/**
 * Tasks to run during plugin uninstallation.
 */

// remove transients
use LW_Swatches\helper;

// get the constants
require_once 'inc/autoload.php';
require_once 'inc/constants.php';
require_once 'inc/functions.php';

// delete transitions of this plugin
$transients = [
    'lw_swatches_activation_error_woocommerce',
    'lw_swatches_required_field_missing',
    'lw_swatches_resetted',
    'lw_swatches_bulk_done'
];
foreach( $transients as $transient ) {
    delete_transient($transient);
}

// delete all data the plugin has collected on uninstall
// -> only if this is enabled
if( lw_swatches_is_woocommerce_activated() && get_option('wc_lw_product_swatches_delete_on_uninstall', 'no') == 'yes' ) {
    global $wpdb, $table_prefix;

    // delete the attribute-metas
    $attributes = wc_get_attribute_taxonomies();
    $attribute_types = helper::getAttributeTypes();
    foreach( $attributes as $attribute ) {
        if( !empty($attribute_types[$attribute->attribute_type]) ) {
            $fields = $attribute_types[$attribute->attribute_type]['fields'];
            foreach( $fields as $field ) {
                $wpdb->delete($table_prefix.'termmeta', ['meta_key' => $field['name']]);
            }
        }
    }

    // delete the swatches
    helper::deleteAllSwatchesOnProducts();

    // remove configured attribute-types on the attributes
    // -> replace our own types with the WooCommerce-default "select"
    foreach( $attribute_types as $attribute_type_name => $attribute_type ) {
        $results = $wpdb->update(
            $wpdb->prefix . 'woocommerce_attribute_taxonomies',
            [
                'attribute_type' => 'select'
            ],
            array('attribute_type' => $attribute_type_name )
        );
    }

    // Clear cache and flush rewrite rules.
    wp_schedule_single_event( time(), 'woocommerce_flush_rewrite_rules' );
    delete_transient( 'wc_attribute_taxonomies' );
    WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
}

// delete options
$options = [
    LW_SWATCHES_OPTION_MAX,
    LW_SWATCHES_OPTION_COUNT,
    LW_SWATCHES_UPDATE_RUNNING,
    'lw_swatches_tasks',
    // WooCommerce-settings
    'wc_'.LW_SWATCH_WC_SETTING_NAME.'_delete_on_uninstall',
    'wc_'.LW_SWATCH_WC_SETTING_NAME.'_disable_cache',
];
foreach( $options as $option ) {
    delete_option($option);
}
