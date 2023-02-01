<?php

namespace LW_Swatches;

/**
 * Define Plugin-Options which will be available in WooCommerce-settings.
 */
class WC_Settings_Tab {

    /**
     * Initialization
     *
     * @return void
     */
    public static function init() {
        add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
        add_action( 'woocommerce_settings_tabs_'.LW_SWATCH_WC_SETTING_NAME, __CLASS__ . '::settings_tab' );
        add_action( 'woocommerce_update_options_'.LW_SWATCH_WC_SETTING_NAME, __CLASS__ . '::update_settings' );
    }

    /**
     * Add the tab
     *
     * @param $settings_tabs
     * @return mixed
     * @noinspection PhpUnused
     */
    public static function add_settings_tab( $settings_tabs ) {
        $settings_tabs[LW_SWATCH_WC_SETTING_NAME] = __('Product Swatches', 'lw-product-swatches');
        return $settings_tabs;
    }

    /**
     * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
     *
     * @uses woocommerce_admin_fields()
     * @uses self::get_settings()
     * @noinspection PhpUnused
     */
    public static function settings_tab() {
        woocommerce_admin_fields( self::get_settings() );
    }

    /**
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses self::get_settings()
     */
    public static function update_settings() {
        woocommerce_update_options( self::get_settings() );
    }

    /**
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     */
    public static function get_settings(): array
    {
        // define settings
        $settings = array(
            'section_title' => array(
                'name'     => __('Settings for Product Swatches', 'lw-product-swatches'),
                'type'     => 'title',
                'desc'     => '',
                'id'       => 'wc_'.LW_SWATCH_WC_SETTING_NAME.'_section_title'
            ),
            'deleteOnUninstall' => array(
                'name' => __('Delete all plugin-data on uninstall', 'lw-product-swatches'),
                'type' => 'checkbox',
                'desc' => '',
                'id'   => 'wc_'.LW_SWATCH_WC_SETTING_NAME.'_delete_on_uninstall'
            ),
            'disableCache' => array(
                'name' => __('Disable plugin-own caching of swatches', 'lw-product-swatches'),
                'type' => 'checkbox',
                'id'   => 'wc_'.LW_SWATCH_WC_SETTING_NAME.'_disable_cache',
                'desc' => __('Without this cache, the page will have a significantly higher load time depending on the number of products and expressions. It is not recommended to disable this cache.', 'lw-product-swatches')
            ),
            'swatchesPositionInList' => array(
                'name' => __('Position in list', 'lw-product-swatches'),
                'type' => 'select',
                'options' => [
                    'beforeprice' => __('before price', 'lw-product-swatches'),
                    'afterprice' => __('after price', 'lw-product-swatches'),
                    'beforecart' => __('before cart', 'lw-product-swatches'),
                    'aftercart' => __('after cart', 'lw-product-swatches'),
                ],
                'desc' => '',
                'id'   => 'wc_'.LW_SWATCH_WC_SETTING_NAME.'_position_in_list'
            ),
            'regenerateSwatches' => array(
                'name' => __('Regenerate Product Swatches', 'lw-product-swatches'),
                'type' => 'generate_product_swatches',
                'desc' => '',
                'id'   => 'wc_'.LW_SWATCH_WC_SETTING_NAME.'_generate_product_swatches'
            )
        );

        // add additional or remove settings by filter
        $settings = apply_filters( 'wc_'.LW_SWATCH_WC_SETTING_NAME.'_settings', $settings );

        // add ending section
        $settings['section_end'] = [
            'type' => 'sectionend',
            'id' => 'wc_'.LW_SWATCH_WC_SETTING_NAME.'_section_end'
        ];

        // return settings
        return $settings;
    }

}