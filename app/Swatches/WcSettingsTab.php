<?php
/**
 * File for handler for WooCommerce settings tab.
 *
 * @package product-swatches-light
 */

namespace ProductSwatchesLight\Swatches;

/**
 * Define Plugin-Options which will be available in WooCommerce-settings.
 */
class WcSettingsTab {

	/**
	 * Initialization
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
		add_action( 'woocommerce_settings_tabs_' . LW_SWATCH_WC_SETTING_NAME, __CLASS__ . '::settings_tab' );
		add_action( 'woocommerce_update_options_' . LW_SWATCH_WC_SETTING_NAME, __CLASS__ . '::update_settings' );
		add_action( 'woocommerce_admin_field_generate_product_swatches', __CLASS__ . '::add_button', 0, 0 );
	}

	/**
	 * Add the tab
	 *
	 * @param array $settings_tabs List of tabs.
	 * @return array
	 * @noinspection PhpUnused
	 */
	public static function add_settings_tab( array $settings_tabs ): array {
		$settings_tabs[ LW_SWATCH_WC_SETTING_NAME ] = __( 'Product Swatches', 'product-swatches-light' );
		return $settings_tabs;
	}

	/**
	 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
	 *
	 * @uses woocommerce_admin_fields()
	 * @uses self::get_settings()
	 * @noinspection PhpUnused
	 */
	public static function settings_tab(): void {
		woocommerce_admin_fields( self::get_settings() );
	}

	/**
	 * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
	 *
	 * @uses woocommerce_update_options()
	 * @uses self::get_settings()
	 */
	public static function update_settings(): void {
		woocommerce_update_options( self::get_settings() );
	}

	/**
	 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
	 *
	 * @return array Array of settings for @see woocommerce_admin_fields() function.
	 */
	public static function get_settings(): array {
		// array with sections.
		$sections = array(
			'general' => array(
				'start'    => array(
					'name' => __( 'Settings for Product Swatches', 'product-swatches-light' ),
					'type' => 'title',
					'desc' => '',
					'id'   => 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_section_title',
				),
				'settings' => array(),
				'end'      => array(
					'type' => 'sectionend',
					'id'   => 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_section_end',
				),
			),
			'list'    => array(
				'start'    => array(
					'name' => __( 'List view', 'product-swatches-light' ),
					'type' => 'title',
					'desc' => '',
					'id'   => 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_section_list_title',
				),
				'settings' => array(),
				'end'      => array(
					'type' => 'sectionend',
					'id'   => 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_section_list_end',
				),
			),
			'detail'  => array(
				'start'    => array(
					'name' => __( 'Detail view', 'product-swatches-light' ),
					'type' => 'title',
					'desc' => '',
					'id'   => 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_section_detail_title',
				),
				'settings' => array(),
				'end'      => array(
					'type' => 'sectionend',
					'id'   => 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_section_detail_end',
				),
			),
			'other'   => array(
				'start'    => array(
					'name' => __( 'Other settings', 'product-swatches-light' ),
					'type' => 'title',
					'desc' => '',
					'id'   => 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_section_other_title',
				),
				'settings' => array(),
				'end'      => array(
					'type' => 'sectionend',
					'id'   => 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_section_other_end',
				),
			),
		);

		// add our settings to the sections.
		$sections['general']['settings']['regenerateSwatches']  = array(
			'name' => __( 'Regenerate Product Swatches', 'product-swatches-light' ),
			'type' => 'generate_product_swatches',
			'desc' => '',
			'id'   => 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_generate_product_swatches',
		);
		$sections['general']['settings']['deleteOnUninstall']   = array(
			'name' => __( 'Delete all plugin-data on uninstall', 'product-swatches-light' ),
			'type' => 'checkbox',
			'desc' => '',
			'id'   => 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_delete_on_uninstall',
		);
		$sections['general']['settings']['disableCache']        = array(
			'name' => __( 'Disable plugin-own caching of swatches', 'product-swatches-light' ),
			'type' => 'checkbox',
			'id'   => 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_disable_cache',
			'desc' => __( 'Without this cache, the page will have a significantly higher load time depending on the number of products and expressions. It is not recommended to disable this cache.', 'product-swatches-light' ),
		);
		$sections['list']['settings']['swatchesPositionInList'] = array(
			'name'    => __( 'Position in list', 'product-swatches-light' ),
			'type'    => 'select',
			'options' => array(
				'beforeprice' => __( 'before price', 'product-swatches-light' ),
				'afterprice'  => __( 'after price', 'product-swatches-light' ),
				'beforecart'  => __( 'before cart', 'product-swatches-light' ),
				'aftercart'   => __( 'after cart', 'product-swatches-light' ),
			),
			'desc'    => '',
			'id'      => 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_position_in_list',
		);

		// filter the settings.
		$sections = apply_filters( 'wc_lw_product_swatches_settings', $sections );

		// generate settings-array for wc.
		$settings = array();
		foreach ( $sections as $section_name => $section ) {
			if ( ! empty( $section['settings'] ) ) {
				$settings[ $section_name . '_start' ] = $section['start'];
				foreach ( $section['settings'] as $field ) {
					$settings[] = $field;
				}
				$settings[ $section_name . '_end' ] = $section['end'];
			}
		}

		// return settings.
		return $settings;
	}

	/**
	 * Add "generate_product_swatches"-button in WooCommerce settings.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public static function add_button(): void {
		// create url.
		$url = add_query_arg(
			array(
				'action' => 'product_swatches_regenerate',
				'nonce'  => wp_create_nonce( 'product-swatches-regenerate-swatches' ),
			),
			get_admin_url() . 'admin.php'
		);

		// create dialog.
		$dialog = array(
			'title'   => __( 'Do you really want to regenerate swatches on all products?', 'product-swatches-light' ),
			'texts'   => array(
				'<p>' . __( '<strong>All swatches will be updated.</strong> This may take a while.', 'product-swatches-light' ) . '</p>',
			),
			'buttons' => array(
				array(
					'action'  => 'product_swatches_regenerate();',
					'variant' => 'primary',
					'text'    => __( 'Yes, I want', 'product-swatches-light' ),
				),
				array(
					'action'  => 'closeDialog();',
					'variant' => 'secondary',
					'text'    => __( 'No', 'product-swatches-light' ),
				),
			),
		);
		?><tr><th class="titledesc"><a href="<?php echo esc_url( wp_nonce_url( $url, 'lws-generate' ) ); ?>" class="button button-large wp-easy-dialog" data-dialog="<?php echo esc_attr( wp_json_encode( $dialog ) ); ?>"><?php echo esc_html__( 'Regenerate all swatches', 'product-swatches-light' ); ?></a></th><td>&nbsp;</td></tr>
		<?php
	}
}
