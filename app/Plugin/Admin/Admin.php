<?php
/**
 * This file contains the handler for admin tasks of this plugin.
 *
 * @package product-swatches-light
 */

namespace ProductSwatchesLight\Plugin\Admin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ProductSwatchesLight\Dependencies\easyTransientsForWordPress\Transients;
use ProductSwatchesLight\Plugin\Helper;
use ProductSwatchesLight\Plugin\Setup;
use ProductSwatchesLight\Plugin\Templates;
use ProductSwatchesLight\Swatches\Attribute;
use ProductSwatchesLight\Swatches\Products;

/**
 * Object to handle the admin tasks of this plugin.
 */
class Admin {
	/**
	 * Instance of actual object.
	 *
	 * @var Admin|null
	 */
	private static ?Admin $instance = null;

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
	 * @return Admin
	 */
	public static function get_instance(): Admin {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the tasks.
	 *
	 * @return void
	 */
	public function init(): void {
		// initialize the setup.
		Setup::get_instance()->init();

		// misc.
		add_action( 'admin_init', array( $this, 'add_handling' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_styles_and_js_admin' ), PHP_INT_MAX );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_dialog' ), PHP_INT_MAX );
		add_action( 'init', array( $this, 'configure_transients' ), 5 );

		// add ajax endpoints.
		add_action( 'wp_ajax_lw_swatches_import_run', array( $this, 'import_run_via_ajax' ) );
		add_action( 'wp_ajax_lw_swatches_import_info', array( $this, 'import_info_via_ajax' ) );
	}

	/**
	 * Add own CSS and JS for backend.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function add_styles_and_js_admin(): void {
		// admin-specific styles.
		wp_enqueue_style(
			'lw-swatches-admin-css',
			plugin_dir_url( LW_SWATCHES_PLUGIN ) . '/admin/styles.css',
			array(),
			Helper::get_file_version( plugin_dir_path( LW_SWATCHES_PLUGIN ) . '/admin/styles.css' ),
		);

		// add frontend js and styles also in backend.
		Templates::get_instance()->add_styles_and_js_frontend();

		// backend-JS.
		wp_enqueue_script(
			'lw-swatches-admin-js',
			plugins_url( '/admin/js.js', LW_SWATCHES_PLUGIN ),
			array( 'jquery', 'easy-dialog-for-wordpress' ),
			Helper::get_file_version( plugin_dir_path( LW_SWATCHES_PLUGIN ) . '/admin/js.js' ),
			true
		);

		// add php-vars to our js-script.
		wp_localize_script(
			'lw-swatches-admin-js',
			'productSwatchesLightJsVars',
			array(
				'ajax_url'                     => admin_url( 'admin-ajax.php' ),
				'rest_update_product_swatches' => rest_url( '/product-swatches/v1/update' ),
				'rest_nonce'                   => wp_create_nonce( 'wp_rest' ),
				'title_update_progress'        => __( 'Swatches updating', 'product-swatches-light' ),
				'get_update_nonce'             => wp_create_nonce( 'product-swatches-get-update-info' ),
				'lbl_ok'                       => __( 'OK', 'product-swatches-light' ),
				'title_update_success'         => __( 'Swatches updated.', 'product-swatches-light' ),
				'txt_update_success'           => __( 'All product swatches has been updated.', 'product-swatches-light' ),
			)
		);
	}

	/**
	 * Get all WooCommerce attributes and add actions to handle them.
	 * Also, add the processing of requests for attributes in the backend.
	 *
	 * This is the main function to start the plugin-magic in admin.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function add_handling(): void {
		$true = true;
		/**
		 * Do not use this plugin in wp-admin.
		 *
		 * @since 1.0.0 Available since 1.0.0
		 * @param bool $true True if it should be used.
		 *
		 * @noinspection PhpConditionAlreadyCheckedInspection
		 */
		if ( Helper::is_woocommerce_activated() && apply_filters( 'lw_swatches_admin_init', $true ) ) {
			// get all attributes and add action on them.
			$attributes      = wc_get_attribute_taxonomies();
			$attribute_types = Helper::get_attribute_types();
			$keys            = array_keys( $attributes );
			$attribute_count = count( $attributes );
			for ( $a = 0;$a < $attribute_count;$a++ ) {
				if ( ! empty( $attribute_types[ $attributes[ $keys[ $a ] ]->attribute_type ] ) ) {
					$fields = $attribute_types[ $attributes[ $keys[ $a ] ]->attribute_type ]['fields'];
					new Attribute( $attributes[ $keys[ $a ] ], $fields );
				}
			}
		}
	}

	/**
	 * Start updates via AJAX.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function import_run_via_ajax(): void {
		// check nonce.
		check_ajax_referer( 'product-swatches-update-run', 'nonce' );

		// run import.
		Products::get_instance()->update_swatches_on_products();

		// return ok back.
		wp_send_json_success();
	}

	/**
	 * Return state of the actual running update via AJAX.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function import_info_via_ajax(): void {
		// check nonce.
		check_ajax_referer( 'product-swatches-update-info', 'nonce' );

		// return actual and max count of import steps.
		echo absint( get_option( LW_SWATCHES_OPTION_COUNT, 0 ) ) . ';' . absint( get_option( LW_SWATCHES_OPTION_MAX ) ) . ';' . absint( get_option( LW_SWATCHES_UPDATE_RUNNING, 0 ) );

		// return ok back.
		wp_send_json_success();
	}

	/**
	 * Add the dialog-scripts and -styles.
	 *
	 * @return void
	 */
	public function add_dialog(): void {
		// embed necessary scripts for dialog.
		$path = Helper::get_plugin_path() . 'vendor/threadi/easy-dialog-for-wordpress/';
		$url  = Helper::get_plugin_url() . 'vendor/threadi/easy-dialog-for-wordpress/';

		// bail if path does not exist.
		if ( ! file_exists( $path ) ) {
			return;
		}

		// embed the dialog-components JS-script.
		$script_asset_path = $path . 'build/index.asset.php';

		// bail if script does not exist.
		if ( ! file_exists( $script_asset_path ) ) {
			return;
		}

		// embed script.
		$script_asset = require $script_asset_path;
		wp_enqueue_script(
			'easy-dialog-for-wordpress',
			$url . 'build/index.js',
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		// embed the dialog-components CSS-file.
		$admin_css      = $url . 'build/style-index.css';
		$admin_css_path = $path . 'build/style-index.css';
		wp_enqueue_style(
			'easy-dialog-for-wordpress',
			$admin_css,
			array( 'wp-components' ),
			Helper::get_file_version( $admin_css_path )
		);
	}

	/**
	 * Set the base configuration for each transient.
	 *
	 * @return void
	 */
	public function configure_transients(): void {
		$transients_obj = Transients::get_instance();
		$transients_obj->set_slug( 'lwps' );
		$transients_obj->set_capability( 'manage_options' );
		$transients_obj->set_template( 'grouped.php' );
		$transients_obj->set_display_method( 'grouped' );
		$transients_obj->set_vendor_path( Helper::get_plugin_path() . 'vendor/' );
		$transients_obj->set_translations(
			array(
				/* translators: %1$d will be replaced by the days this message will be hidden. */
				'hide_message' => __( 'Hide this message for %1$d days.', 'product-swatches-light' ),
				'dismiss'      => __( 'Dismiss', 'product-swatches-light' ),
			)
		);
		$transients_obj->set_url( Helper::get_plugin_url() . '/app/Dependencies/easyTransientsForWordPress/' );
		$transients_obj->set_path( Helper::get_plugin_path() . '/app/Dependencies/easyTransientsForWordPress/' );
		$transients_obj->init();
	}
}
