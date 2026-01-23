<?php
/**
 * File to handle the main object for each test class.
 *
 * @package product-swatches-light
 */

namespace ProductSwatchesLight\Tests;

use WP_UnitTestCase;

/**
 * Object to handle the preparations for each test class.
 */
abstract class SwatchesTestCase extends WP_UnitTestCase {

	/**
	 * Prepare the test environment for each test class.
	 *
	 * @return void
	 */
	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		// prepare the loading just one time.
		if ( ! did_action('product_swatches_light_test_preparation_loaded') ) {
			// enable WooCommerce.
			activate_plugin( 'woocommerce/woocommerce.php' );

			// Plugin initialisieren
			\ProductSwatchesLight\Plugin\Installer::get_instance()->initialize_plugin();

			// run initialization.
			do_action( 'after_setup_theme' );
			do_action( 'init' );

			// mark as loaded.
			do_action('product_swatches_light_test_preparation_loaded');
		}
	}
}
