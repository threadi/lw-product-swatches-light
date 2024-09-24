<?php
/**
 * This file contains the tasks for multiple product handlings.
 *
 * @package product-swatches-light
 */

namespace ProductSwatchesLight\Swatches;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ProductSwatchesLight\Plugin\Helper;
use ProductSwatchesLight\Plugin\Schedules;
use WP_Query;

/**
 * Object to handle the tasks for WooCommerce.
 */
class Products {
	/**
	 * Instance of actual object.
	 *
	 * @var Products|null
	 */
	private static ?Products $instance = null;

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
	 * @return Products
	 */
	public static function get_instance(): Products {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get our own product object for specific variable product.
	 *
	 * @param int $product_id The ID of the product.
	 *
	 * @return false|Product
	 */
	public function get_product( int $product_id ): false|Product {
		// get the WooCommerce-own product object to check the type.
		$product = wc_get_product( $product_id );

		// bail if product is not variable.
		if ( ! $product->is_type( 'variable' ) ) {
			return false;
		}

		// return our own product object.
		return new Product( $product );
	}

	/**
	 * Updates the swatches on all products.
	 *
	 * @return void
	 */
	public function update_swatches_on_products(): void {
		// do not import if it is already running in another process.
		if ( 1 === absint( get_option( LW_SWATCHES_UPDATE_RUNNING, 0 ) ) ) {
			return;
		}

		// mark import as running.
		update_option( LW_SWATCHES_UPDATE_RUNNING, 1 );

		// set label.
		update_option( LW_SWATCHES_UPDATE_STATUS, __( 'Product swatches update is running ..', 'product-swatches-light' ) );

		// get the products.
		$query          = array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'post_status'    => 'any',
		);
		$results        = new WP_Query( $query );
		$count_products = $results->post_count;

		// set counter for progressbar in backend.
		update_option( LW_SWATCHES_OPTION_MAX, $count_products );
		update_option( LW_SWATCHES_OPTION_COUNT, 0 );
		$count = 0;

		// initiate the CLI handler for progress there.
		$progress = Helper::is_cli() ? \WP_CLI\Utils\make_progress_bar( 'Updating products', $count_products ) : false;

		// loop through the products.
		for ( $p = 0;$p < $count_products;$p++ ) {
			// initiate the product.
			$product_obj = self::get_instance()->get_product( $results->posts[ $p ] );

			// bail if product could not be loaded.
			if ( ! ( $product_obj instanceof Product ) ) {
				continue;
			}

			// update the swatches.
			$product_obj->update_swatches();

			// show progress.
			update_option( LW_SWATCHES_OPTION_COUNT, ++$count );

			// show progress on CLI.
			$progress ? $progress->tick() : false;
		}

		// show finished progress.
		$progress ? $progress->finish() : false;

		// output success-message.
		$progress ? \WP_CLI::success( $count_products . ' products were updated.' ) : false;

		// update the status.
		update_option( LW_SWATCHES_UPDATE_STATUS, __( 'Product swatches update has been run.', 'product-swatches-light' ) );

		// remove running flag.
		delete_option( LW_SWATCHES_UPDATE_RUNNING );
	}

	/**
	 * Return info about state of the swatches update on products.
	 *
	 * @return void
	 */
	public function get_update_swatches_on_products_info(): void {
		// return actual and max count of import steps.
		wp_send_json(
			array(
				absint( get_option( LW_SWATCHES_OPTION_COUNT, 0 ) ),
				absint( get_option( LW_SWATCHES_OPTION_MAX ) ),
				absint( get_option( LW_SWATCHES_UPDATE_RUNNING, 0 ) ),
				wp_kses_post( get_option( LW_SWATCHES_UPDATE_STATUS, '' ) ),
			)
		);
	}

	/**
	 * Update swatches on selected attribute.
	 *
	 * @param string $type - the type to search for, e.g. "attribute".
	 * @param string $name - the name of the type.
	 * @return void
	 */
	public static function update_swatches_on_products_by_type( string $type, string $name ): void {
		if ( 'attribute' === $type && ! empty( $name ) ) {
			// update all swatch-caches on products using this attribute.
			$query          = array(
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'post_status'    => 'any',
				'tax_query'      => array(
					array(
						'taxonomy' => $name,
						'field'    => 'id',
						'terms'    => get_terms(
							array(
								'taxonomy' => $name,
								'fields'   => 'ids',
							)
						),
					),
				),
			);
			$results        = new WP_Query( $query );
			$count_products = $results->post_count;

			// create progress bar on cli.
			$progress = Helper::is_cli() ? \WP_CLI\Utils\make_progress_bar( 'Updating products', $count_products ) : false;
			for ( $p = 0;$p < $count_products;$p++ ) {
				// get product.
				$product = self::get_instance()->get_product( $results->posts[ $p ] );

				// update product.
				$product->update_swatches();

				// show progress.
				$progress ? $progress->tick() : false;
			}

			// show finished progress.
			$progress ? $progress->finish() : false;
		}
	}

	/**
	 * Delete all swatches on all products.
	 *
	 * @return void
	 */
	public function delete_all_swatches_on_products(): void {
		// get the products where a product swatch is set.
		$query          = array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'post_status'    => 'any',
			'meta_query'     => array(
				array(
					'key'     => LW_SWATCH_CACHEKEY,
					'compare' => 'EXISTS',
				),
			),
		);
		$results        = new WP_Query( $query );
		$count_products = count( $results->posts );
		$progress       = Helper::is_cli() ? \WP_CLI\Utils\make_progress_bar( 'Deleting product-swatches', $count_products ) : false;

		// loop through the products.
		for ( $p = 0;$p < $count_products;$p++ ) {
			$product = self::get_instance()->get_product( $results->posts[ $p ] );
			$product->delete_swatches();
			// show progress.
			$progress ? $progress->tick() : false;
		}
		// show finished progress.
		$progress ? $progress->finish() : false;

		// output success-message.
		$progress ? \WP_CLI::success( $count_products . ' product-swatches were deleted.' ) : false;
	}

	/**
	 * Update single swatches on product by given ID.
	 *
	 * @param mixed $product The product ID or object.
	 *
	 * @return void
	 */
	public function update_product( mixed $product ): void {
		// if parameter is an object, get its ID.
		if ( $product instanceof \WC_Product ) {
			$product = $product->get_id();
		}

		// if the product is an ID get our product and update its swatches.
		if ( is_int( $product ) ) {
			$product = $this->get_product( $product );
			if ( $product instanceof Product ) {
				$product->update_swatches();
			}

			// add task to update all swatch-caches on products.
			Schedules::get_instance()->add_single_event( 'product_swatches_schedule_regeneration' );
		}
	}
}
