<?php
/**
 * This file contains the handler for templates this plugin is using.
 *
 * @package product-swatches-light
 */

namespace ProductSwatchesLight\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to handle the initialization of this plugin.
 */
class Templates {
	/**
	 * Instance of actual object.
	 *
	 * @var Templates|null
	 */
	private static ?Templates $instance = null;

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
	 * @return Templates
	 */
	public static function get_instance(): Templates {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize template handlings.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'add_styles_and_js_frontend' ), PHP_INT_MAX );
	}

	/**
	 * Add own CSS and JS for frontend.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function add_styles_and_js_frontend(): void {
		wp_enqueue_style(
			'lw-swatches-styles',
			plugin_dir_url( LW_SWATCHES_PLUGIN ) . '/css/styles.css',
			array(),
			Helper::get_file_version( plugin_dir_path( LW_SWATCHES_PLUGIN ) . '/css/styles.css' )
		);
		wp_enqueue_script(
			'lw-swatches-script',
			plugins_url( '/js/frontend.js', LW_SWATCHES_PLUGIN ),
			array( 'jquery' ),
			Helper::get_file_version( plugin_dir_path( LW_SWATCHES_PLUGIN ) . '/js/frontend.js' ),
			true
		);
	}

	/**
	 * Load a template if it exists.
	 * Also load the requested file if is located in the /wp-content/themes/xy/product-swatches-light/ directory.
	 *
	 * @param string $template The path to the template.
	 * @return string
	 */
	public function get_template( string $template ): string {
		if ( is_embed() ) {
			return $template;
		}

		$theme_template = locate_template( trailingslashit( basename( dirname( LW_SWATCHES_PLUGIN ) ) ) . $template );
		if ( $theme_template ) {
			return $theme_template;
		}
		return plugin_dir_path( apply_filters( 'product_swatches_light_set_template_directory', LW_SWATCHES_PLUGIN ) ) . 'templates/' . $template;
	}

	/**
	 * Get the resulting HTML-list.
	 *
	 * @param string $html The HTML-code for the output.
	 * @param string $typenames The type names.
	 * @param string $typename The type name.
	 * @param string $taxonomy The taxonomy name.
	 * @param bool   $changed_by_gallery Marker.
	 * @return string
	 * @noinspection SpellCheckingInspection
	 */
	public function get_html_list( string $html, string $typenames, string $typename, string $taxonomy, bool $changed_by_gallery ): string {
		// return if empty html is given.
		if ( empty( $html ) ) {
			return '';
		}

		// just do use this var.
		if ( empty( $typenames ) ) {
			$typenames = '';
		}

		// just do use this var.
		if ( empty( $typename ) ) {
			$typename = '';
		}

		// just do use this var.
		if ( empty( $taxonomy ) ) {
			$taxonomy = '';
		}

		// set marker to false if not set.
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
		include $this->get_template( 'parts/list-start.php' );
		echo wp_kses_post( $html );
		include $this->get_template( 'parts/list-end.php' );
		$content = ob_get_clean();
		if ( ! $content ) {
			return '';
		}
		return $content;
	}
}
