<?php
/**
 * This file contains the handler to initialize the plugin.
 *
 * @package product-swatches-light
 */

namespace ProductSwatchesLight\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ProductSwatchesLight\Plugin\Admin\Admin;
use ProductSwatchesLight\Swatches\Rest;
use ProductSwatchesLight\Swatches\WooCommerce;

/**
 * Object to handle the initialization of this plugin.
 */
class Init {
	/**
	 * Instance of the actual object.
	 *
	 * @var Init|null
	 */
	private static ?Init $instance = null;

	/**
	 * Constructor, not used as this a Singleton object.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return the instance of this object as a singleton.
	 *
	 * @return Init
	 */
	public static function get_instance(): Init {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize this plugin.
	 *
	 * @return void
	 */
	public function init(): void {
		// init the settings.
		Settings::get_instance()->init();

		// init templates.
		Templates::get_instance()->init();

		// init WooCommerce.
		WooCommerce::get_instance()->init();

		// init schedules.
		Schedules::get_instance()->init();

		// init admin tasks in admin only.
		Admin::get_instance()->init();

		// initiate REST endpoints.
		Rest::get_instance()->init();

		// misc.
		add_action( 'cli_init', array( $this, 'register_cli' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( LW_SWATCHES_PLUGIN ), array( $this, 'add_setting_link' ) );
		add_filter(
			'product_swatches_light_change_attribute_type_name',
			function ( string|null $attribute_name ) {
				if ( is_null( $attribute_name ) ) {
					return '';
				}
				return ucwords( $attribute_name );
			}
		);
	}

	/**
	 * On plugin deactivation.
	 *
	 * @return void
	 */
	public function deactivation(): void {
		// remove schedules.
		Schedules::get_instance()->delete_all();
	}

	/**
	 * Register WP Cli if WooCommerce is present.
	 *
	 * @since  1.0.0
	 * @author Thomas Zwirner
	 * @noinspection PhpUnused
	 */
	public function register_cli(): void {
		if ( function_exists( 'wc_get_product' ) ) {
			\WP_CLI::add_command( 'product-swatches', 'ProductSwatchesLight\Plugin\Cli' );
		}
	}

	/**
	 * Add the link to plugin-settings in the plugin-list.
	 *
	 * @param array<int,string> $links List of links.
	 * @return array<int,string>
	 * @noinspection PhpUnused
	 */
	public function add_setting_link( array $links ): array {
		// create the link.
		$settings_link = "<a href='" . esc_url( Helper::get_settings_url() ) . "'>" . __( 'Settings', 'product-swatches-light' ) . '</a>';

		// adds the link to the end of the array.
		$links[] = $settings_link;

		// return the resulting list of links.
		return $links;
	}
}
