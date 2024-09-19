<?php
/**
 * This file contains the REST support for this plugin.
 *
 * @package product-swatches-light
 */

namespace ProductSwatchesLight\Swatches;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use WP_REST_Server;

/**
 * Object to handle the tasks for WooCommerce.
 */
class Rest {
	/**
	 * Instance of actual object.
	 *
	 * @var Rest|null
	 */
	private static ?Rest $instance = null;

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
	 * @return Rest
	 */
	public static function get_instance(): Rest {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the REST support.
	 *
	 * @return void
	 */
	public function init(): void {
		// register our endpoints.
		add_action( 'rest_api_init', array( $this, 'add_rest_api' ) );
	}

	/**
	 * Initialize additional REST API endpoints.
	 *
	 * @return void
	 */
	public function add_rest_api(): void {
		// to update the swatches.
		register_rest_route(
			'product-swatches/v1',
			'/update/',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( Products::get_instance(), 'update_swatches_on_products' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// to get info about the progress.
		register_rest_route(
			'product-swatches/v1',
			'/update/',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( Products::get_instance(), 'get_update_swatches_on_products_info' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}
}
