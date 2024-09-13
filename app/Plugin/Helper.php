<?php
/**
 * File to handle helper tasks.
 *
 * @package product-swatches-light
 */

namespace ProductSwatches\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ProductSwatches\Swatches\Product;
use WC_Product;
use WC_Product_Attribute;
use WP_Query;

/**
 * The helper object.
 */
class Helper {

	/**
	 * Updates the swatches on all products.
	 *
	 * @return void
	 */
	public static function update_swatches_on_products(): void {
		// do not import if it is already running in another process.
		if ( 1 === absint( get_option( LW_SWATCHES_UPDATE_RUNNING, 0 ) ) ) {
			return;
		}

		// mark import as running.
		update_option( LW_SWATCHES_UPDATE_RUNNING, 1 );

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

		$progress = self::is_cli() ? \WP_CLI\Utils\make_progress_bar( 'Updating products', $count_products ) : false;

		// loop through the products.
		for ( $p = 0;$p < $count_products;$p++ ) {
			// Produkt initialisieren.
			$product = wc_get_product( $results->posts[ $p ] );
			Product::update( $product );
			// show progress.
			update_option( LW_SWATCHES_OPTION_COUNT, ++$count );
			$progress ? $progress->tick() : false;
		}
		// show finished progress.
		$progress ? $progress->finish() : false;

		// output success-message.
		$progress ? \WP_CLI::success( $count_products . ' products were updated.' ) : false;

		// remove running flag.
		delete_option( LW_SWATCHES_UPDATE_RUNNING );
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
			$progress = self::is_cli() ? \WP_CLI\Utils\make_progress_bar( 'Updating products', $count_products ) : false;
			for ( $p = 0;$p < $count_products;$p++ ) {
				// get product.
				$product = wc_get_product( $results->posts[ $p ] );

				// update product.
				Product::update( $product );

				// show progress.
				$progress ? $progress->tick() : false;
			}

			// show finished progress.
			$progress ? $progress->finish() : false;
		}
	}

	/**
	 * Add given callback to task list for scheduler.
	 *
	 * @param array $function_to_call The function to add.
	 * @return void
	 */
	public static function add_task_for_scheduler( array $function_to_call ): void {
		if ( ! empty( $function_to_call ) ) {
			$md5       = md5( wp_json_encode( $function_to_call ) );
			$task_list = array_merge( get_option( 'lw_swatches_tasks', array() ), array( $md5 => $function_to_call ) );
			update_option( 'lw_swatches_tasks', $task_list );
		}
	}

	/**
	 * Remove the Wordpress-home URL from given string.
	 *
	 * @param string $string_to_change The string to change.
	 * @return string
	 */
	public static function remove_own_home_from_string( string $string_to_change ): string {
		return str_replace( get_option( 'home' ), '', $string_to_change );
	}

	/**
	 * Get variant-image as data-attribute
	 *
	 * @param array  $images List of images.
	 * @param array  $images_sets List of image sets.
	 * @param string $slug The slug.
	 * @return array
	 */
	public static function get_variant_thumb_as_data( array $images, array $images_sets, string $slug ): array {
		if ( empty( $images[ $slug ] ) ) {
			return array();
		}
		return array(
			'image'  => $images[ $slug ],
			'srcset' => $images_sets[ $slug ],
		);
	}

	/**
	 * Get variant-image as data-attribute from array
	 *
	 * @param array  $variations List of variations.
	 * @param string $attribute The name of the attribute.
	 * @param string $slug Name of the slug.
	 * @return array
	 */
	public static function get_variant_thumb_as_data_from_array( array $variations, string $attribute, string $slug ): array {
		$image            = '';
		$image_src_set    = '';
		$variations_count = count( $variations );
		for ( $v = 0;$v < $variations_count;$v++ ) {
			if ( $slug === $variations[ $v ]['attributes'][ 'attribute_' . $attribute ] ) {
				$image         = $variations[ $v ]['image']['src'];
				$image_src_set = $variations[ $v ]['image']['srcset'];
				break;
			}
		}
		if ( ! empty( $image ) && ! empty( $image_src_set ) ) {
			return self::get_variant_thumb_as_data( array( 0 => $image ), array( 0 => $image_src_set ), 0 );
		}
		return array();
	}

	/**
	 * Get variant by given attribute and slug.
	 *
	 * @param false|WC_Product $product The product object.
	 * @param string           $attribute The name of the attribute.
	 * @param string           $slug The slug.
	 * @return false|\WC_Product
	 */
	public static function get_variant_from_array( false|WC_Product $product, string $attribute, string $slug ): false|WC_Product {
		$variations       = $product->get_available_variations();
		$variant          = false;
		$variations_count = count( $variations );
		for ( $v = 0;$v < $variations_count;$v++ ) {
			if ( $slug === $variations[ $v ]['attributes'][ 'attribute_' . $attribute ] ) {
				$variant = wc_get_product( $variations[ $v ]['variation_id'] );
				break;
			}
		}
		return $variant;
	}

	/**
	 * Delete all swatches on all products.
	 *
	 * @return void
	 */
	public static function delete_all_swatches_on_products(): void {
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
		$progress       = self::is_cli() ? \WP_CLI\Utils\make_progress_bar( 'Deleting product-swatches', $count_products ) : false;

		// loop through the products.
		for ( $p = 0;$p < $count_products;$p++ ) {
			Product::delete( $results->posts[ $p ] );
			// show progress.
			$progress ? $progress->tick() : false;
		}
		// show finished progress.
		$progress ? $progress->finish() : false;

		// output success-message.
		$progress ? \WP_CLI::success( $count_products . ' product-swatches were deleted.' ) : false;
	}

	/**
	 * Return available attribute types incl. their language-specific labels.
	 *
	 * @return array[]
	 */
	public static function get_attribute_types(): array {
		$attribute_types       = apply_filters( 'lw_swatches_types', LW_ATTRIBUTE_TYPES );
		$attribute_types_label = array(
			'color' => array(
				'label'  => __( 'Color', 'lw-product-swatches' ),
				'fields' => array(
					'color' => array(
						'label' => __( 'Color', 'lw-product-swatches' ),
						'desc'  => __( 'Choose a color.', 'lw-product-swatches' ),
					),
				),
			),
		);
		return array_merge_recursive( $attribute_types, apply_filters( 'lw_swatches_types_label', $attribute_types_label ) );
	}

	/**
	 * Prüfe, ob der Import per CLI aufgerufen wird.
	 * Z.B. um einen Fortschrittsbalken anzuzeigen.
	 *
	 * @return bool
	 */
	public static function is_cli(): bool {
		return defined( 'WP_CLI' ) && \WP_CLI;
	}

	/**
	 * Load a template if it exists.
	 * Also load the requested file if is located in the /wp-content/themes/xy/lw-product-swatches/ directory.
	 *
	 * @param string $template The path to the template.
	 * @return string
	 */
	public static function get_template( string $template ): string {
		if ( is_embed() ) {
			return $template;
		}

		$theme_template = locate_template( trailingslashit( basename( dirname( LW_SWATCHES_PLUGIN ) ) ) . $template );
		if ( $theme_template ) {
			return $theme_template;
		}
		return plugin_dir_path( apply_filters( 'lw_product_swatches_set_template_directory', LW_SWATCHES_PLUGIN ) ) . 'templates/' . $template;
	}

	/**
	 * Get the resulting HTML-list.
	 *
	 * @param string $html The HTML-code for the output.
	 * @param string  $typenames The type names.
	 * @param string $typename The type name.
	 * @param string $taxonomy The taxonomy name.
	 * @param bool   $changed_by_gallery Marker.
	 * @return string
	 * @noinspection SpellCheckingInspection
	 */
	public static function get_html_list( string $html, string $typenames, string $typename, string $taxonomy, bool $changed_by_gallery ): string {
		if ( empty( $html ) ) {
			return '';
		}

		if ( empty( $typenames ) ) {
			$typenames = array();
		}

		if ( empty( $typename ) ) {
			$typename = array();
		}
		if ( empty( $taxonomy ) ) {
			$taxonomy = '';
		}
		if ( empty( $changed_by_gallery ) ) {
			$changed_by_gallery = false;
		}

		ob_start();
		/**
		 * Close surrounding link if it has been run.
		 */
		woocommerce_template_loop_product_link_close();
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );

		/**
		 * Output starting and ending list template surrounding the given html-code.
		 */
		include self::get_template( 'parts/list-start.php' );
		echo wp_kses_post( $html );
		include self::get_template( 'parts/list-end.php' );
		return ob_get_clean();
	}

	/**
	 * Return allowed colors.
	 *
	 * @return array
	 */
	public static function get_colors(): array {
		return array(
			'black'  => __( 'black', 'lw-product-swatches' ),
			'blue'   => __( 'blue', 'lw-product-swatches' ),
			'brown'  => __( 'brown', 'lw-product-swatches' ),
			'green'  => __( 'green', 'lw-product-swatches' ),
			'red'    => __( 'red', 'lw-product-swatches' ),
			'white'  => __( 'white', 'lw-product-swatches' ),
			'yellow' => __( 'yellow', 'lw-product-swatches' ),
		);
	}

	/**
	 * Check if WooCommerce is active and running.
	 *
	 * @return bool     true if WooCommerce is active and running
	 */
	public static function lw_swatches_is_woocommerce_activated(): bool {
		return class_exists( 'woocommerce' );
	}
}
