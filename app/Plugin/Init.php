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
	 * Instance of actual object.
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
	 * Return instance of this object as singleton.
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
		// init transients.
		Transients::get_instance()->init();

		// init templates.
		Templates::get_instance()->init();

		// init WooCommerce.
		WooCommerce::get_instance()->init();

		// init schedules.
		Schedules::get_instance()->init();

		// init admin tasks in admin only.
		if ( is_admin() ) {
			Admin::get_instance()->init();
		}

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
	 * Add link to plugin-settings in plugin-list.
	 *
	 * @param array $links List of links.
	 * @return array
	 * @noinspection PhpUnused
	 */
	public function add_setting_link( array $links ): array {
		// build and escape the URL.
		$url = add_query_arg(
			array(
				'page' => 'wc-settings',
				'tab'  => 'lw_product_swatches',
			),
			get_admin_url() . 'admin.php'
		);

		// create the link.
		$settings_link = "<a href='" . esc_url( $url ) . "'>" . __( 'Settings', 'product-swatches-light' ) . '</a>';

		// adds the link to the end of the array.
		$links[] = $settings_link;

		// return resulting list of links.
		return $links;
	}
}
