<?php
/**
 * File with helper functions.
 *
 * @package product-swatches-light
 */

namespace LW_Swatches;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_Query;

/**
 * Object with helper functions for this plugin.
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
		$query   = array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'post_status'    => 'any',
		);
		$results = new WP_Query( $query );

		// set counter for progressbar in backend.
		update_option( LW_SWATCHES_OPTION_MAX, $results->post_count );
		update_option( LW_SWATCHES_OPTION_COUNT, 0 );
		$count = 0;

		$progress = self::is_cli() ? \WP_CLI\Utils\make_progress_bar( 'Updating products', $results->post_count ) : false;

		// loop through the products.
		for ( $p = 0;$p < $results->post_count;$p++ ) {
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
		$progress ? \WP_CLI::success( $results->post_count . ' products were updated.' ) : false;

		// remove running flag.
		delete_option( LW_SWATCHES_UPDATE_RUNNING );
	}

	/**
	 * Update swatches on selected attribute.
	 *
	 * @param string $type The type to search for, e.g. "attribute".
	 * @param string $name The name of the type.
	 * @return void
	 */
	public static function update_swatches_on_products_by_type( string $type, string $name ): void {
		if ( 'attribute' === $type && ! empty( $name ) ) {
			// update all swatch-caches on products using this attribute.
			$query   = array(
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
			$results = new WP_Query( $query );

			// create progress bar on cli.
			$progress = self::is_cli() ? \WP_CLI\Utils\make_progress_bar( 'Updating products', $results->post_count ) : false;
			for ( $p = 0;$p < $results->post_count;$p++ ) {
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
	 * @param array $tasks List of tasks.
	 * @return void
	 */
	public static function add_task_for_scheduler( array $tasks ): void {
		if ( ! empty( $tasks ) ) {
			$md5       = md5( serialize( $tasks ) );
			$task_list = array_merge( get_option( 'lw_swatches_tasks', array() ), array( $md5 => $tasks ) );
			update_option( 'lw_swatches_tasks', $task_list );
		}
	}

	/**
	 * Delete all swatches on all products.
	 *
	 * @return void
	 */
	public static function delete_all_swatches_on_products(): void {
		// get the products where a product swatch is set.
		$query    = array(
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
		$results  = new WP_Query( $query );
		$progress = self::is_cli() ? \WP_CLI\Utils\make_progress_bar( 'Deleting product-swatches', $results->post_count ) : false;

		// loop through the products.
		for ( $p = 0;$p < $results->post_count;$p++ ) {
			Product::delete( $results->posts[ $p ] );
			// show progress.
			$progress ? $progress->tick() : false;
		}
		// show finished progress.
		$progress ? $progress->finish() : false;

		// output success-message.
		$progress ? \WP_CLI::success( $results->post_count . ' product-swatches were deleted.' ) : false;
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
				'label'  => __( 'Color', 'product-swatches-light' ),
				'fields' => array(
					'color' => array(
						'label' => __( 'Color', 'product-swatches-light' ),
						'desc'  => __( 'Choose a color.', 'product-swatches-light' ),
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
	private static function is_cli(): bool {
		return defined( 'WP_CLI' ) && \WP_CLI;
	}

	/**
	 * Load a template if it exists.
	 * Also load the requested file if is located in the /wp-content/themes/xy/lw-product-swatches/ directory.
	 *
	 * @param string $template The requested template.
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
	 * @param string $html The HTML to output.
	 * @param string $type_names The type name plural.
	 * @param string $typename The type name singular.
	 * @param string $taxonomy Used taxonomy.
	 * @param bool   $changed_by_gallery Changed by gallery.
	 * @return string
	 */
	public static function get_html_list( string $html, string $type_names, string $typename, string $taxonomy, bool $changed_by_gallery ): string {
		if ( empty( $html ) ) {
			return '';
		}

		if ( empty( $type_names ) ) {
			return '';
		}

		if ( empty( $typename ) && empty( $taxonomy ) && empty( $changed_by_gallery ) ) {
			return '';
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
	public static function get_allowed_colors(): array {
		return array(
			'black'  => __( 'black', 'product-swatches-light' ),
			'blue'   => __( 'blue', 'product-swatches-light' ),
			'brown'  => __( 'brown', 'product-swatches-light' ),
			'green'  => __( 'green', 'product-swatches-light' ),
			'red'    => __( 'red', 'product-swatches-light' ),
			'white'  => __( 'white', 'product-swatches-light' ),
			'yellow' => __( 'yellow', 'product-swatches-light' ),
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
