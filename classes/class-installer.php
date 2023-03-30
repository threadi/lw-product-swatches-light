<?php

namespace LW_Swatches;

use WC_Cache_Helper;

/**
 * Helper-function for plugin-activation and -deinstallation.
 */
class installer
{
    public static function initializePlugin(): void
    {
        $error = false;

        // check if WooCommerce is installed
        if (!helper::lw_swatches_is_woocommerce_activated()) {
            $url = add_query_arg(
                [
                    's' => 'woocommerce',
                    'tab' => 'search',
                    'type' => 'term'
                ],
                'plugin-install.php'
            );
            set_transient('lwSwatchesMessage', [
                /* translators: %1$s is replaced with "string" */
                'message' => sprintf(__('<strong>Product Swatches for WooCommerce Light could not be activated!</strong> Please <a href="%1$s">install and activate WooCommerce</a> first.', 'lw-product-swatches'), $url),
                'state' => 'error',
                'disable_plugin' => true
            ]);
            $error = true;
        }

        if (false === $error) {
            // add scheduler for automatic swatches generation, if it does not exist already
            if (!wp_next_scheduled('lw_swatches_run_tasks')) {
                wp_schedule_event(time(), 'hourly', 'lw_swatches_run_tasks');
            }

            // set empty task list if not set
            if (!get_option('lw_swatches_tasks', false)) {
                update_option('lw_swatches_tasks', []);
            }

            // enable delete all data on uninstall
            if (!get_option('wc_'.LW_SWATCH_WC_SETTING_NAME.'_delete_on_uninstall', false)) {
                update_option('wc_'.LW_SWATCH_WC_SETTING_NAME.'_delete_on_uninstall', 'yes');
            }

            // enable delete all data on uninstall
            if (!get_option('wc_'.LW_SWATCH_WC_SETTING_NAME.'_disable_cache', false)) {
                update_option('wc_'.LW_SWATCH_WC_SETTING_NAME.'_disable_cache', 'no');
            }

            // add task to generate initial swatches-cache
            helper::addTaskForScheduler(['\LW_Swatches\helper::updateSwatchesOnProducts']);

            // run all updates
            updates::runAllUpdates();
        }
    }

    /**
     * Remove all data of this plugin.
     *
     * @param $deleteData
     * @return void
     */
    public static function removeAllData( $deleteData ): void
    {
        // delete transitions of this plugin
        foreach( LW_SWATCHES_TRANSIENTS as $transient ) {
            delete_transient($transient);
        }

        // delete all data the plugin has collected on uninstall
        // -> only if this is enabled
        if( ( helper::lw_swatches_is_woocommerce_activated() && get_option('wc_lw_product_swatches_delete_on_uninstall', 'no') == 'yes' ) || (!empty($deleteData[0]) && absint($deleteData[0]) == 1 ) ) {
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
                $wpdb->update(
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
    }
}