<?php
/**
 * File to handle helper tasks.
 *
 * @package product-swatches-light
 */

namespace ProductSwatchesLight\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use WP_Post;
use WP_Post_Type;
use WP_Rewrite;

/**
 * The helper object.
 */
class Helper {
	/**
	 * Get variant-image as data-attribute
	 *
	 * @param array<string,string> $images List of images.
	 * @param array<string,string> $images_sets List of image sets.
	 * @param string               $slug The slug.
	 * @return array<string,string>
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
	 * Return available attribute types incl. their language-specific labels.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_attribute_types(): array {
		$attribute_types       = apply_filters( 'product_swatches_light_swatches_types', LW_ATTRIBUTE_TYPES );
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
		return array_merge_recursive( $attribute_types, apply_filters( 'product_swatches_light_swatches_types_label', $attribute_types_label ) );
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
	 * Return colors.
	 *
	 * @return array<string,string>
	 */
	public static function get_colors(): array {
		return array(
			'black'  => __( 'Black', 'product-swatches-light' ),
			'blue'   => __( 'Blue', 'product-swatches-light' ),
			'brown'  => __( 'Brown', 'product-swatches-light' ),
			'green'  => __( 'Green', 'product-swatches-light' ),
			'red'    => __( 'Red', 'product-swatches-light' ),
			'white'  => __( 'White', 'product-swatches-light' ),
			'yellow' => __( 'Yellow', 'product-swatches-light' ),
		);
	}

	/**
	 * Check if WooCommerce is active and running.
	 *
	 * @return bool     true if WooCommerce is active and running
	 */
	public static function is_woocommerce_activated(): bool {
		return class_exists( 'woocommerce' );
	}

	/**
	 * Return the logo as img
	 *
	 * @return string
	 */
	public static function get_logo_img(): string {
		return '<img src="' . self::get_plugin_url() . 'gfx/laolaweb.svg" alt="laOlaWeb Logo" class="logo">';
	}

	/**
	 * Return the absolute URL to the plugin (already trailed with slash).
	 *
	 * @return string
	 */
	public static function get_plugin_url(): string {
		return trailingslashit( plugin_dir_url( LW_SWATCHES_PLUGIN ) );
	}

	/**
	 * Return the absolute local filesystem-path (already trailed with slash) to the plugin.
	 *
	 * @return string
	 */
	public static function get_plugin_path(): string {
		return trailingslashit( plugin_dir_path( LW_SWATCHES_PLUGIN ) );
	}

	/**
	 * Return the name of this plugin.
	 *
	 * @return string
	 */
	public static function get_plugin_name(): string {
		$plugin_data = get_plugin_data( LW_SWATCHES_PLUGIN );
		if ( ! empty( $plugin_data ) && ! empty( $plugin_data['Name'] ) ) { // @phpstan-ignore empty.variable
			return $plugin_data['Name'];
		}
		return '';
	}

	/**
	 * Get current URL in frontend and backend.
	 *
	 * @return string
	 */
	public static function get_current_url(): string {
		if ( is_admin() && ! empty( $_SERVER['REQUEST_URI'] ) ) {
			return admin_url( basename( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) );
		}

		// set return value for page url.
		$page_url = '';

		// get actual object.
		$object = get_queried_object();
		if ( $object instanceof WP_Post_Type ) {
			$page_url = get_post_type_archive_link( $object->name );
		}
		if ( $object instanceof WP_Post ) {
			$page_url = get_permalink( $object->ID );
		}

		// return result.
		return (string) $page_url;
	}

	/**
	 * Return the version of the given file.
	 *
	 * With WP_DEBUG or plugin-debug enabled its @filemtime().
	 * Without this it's the plugin-version.
	 *
	 * @param string $filepath The absolute path to the requested file.
	 *
	 * @return string
	 */
	public static function get_file_version( string $filepath ): string {
		// check for WP_DEBUG.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return (string) filemtime( $filepath );
		}

		$plugin_version = LW_SWATCHES_PLUGIN;

		/**
		 * Filter the used file version (for JS- and CSS-files which get enqueued).
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 *
		 * @param string $plugin_version The plugin-version.
		 * @param string $filepath The absolute path to the requested file.
		 */
		return apply_filters( 'product_swatches_light_file_version', $plugin_version, $filepath );
	}

	/**
	 * Return the list of blogs in a multisite-installation.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_blogs(): array {
		if ( false === is_multisite() ) {
			return array();
		}

		// Get DB-connection.
		global $wpdb;

		// get blogs in this site-network.
		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"
            SELECT blog_id
            FROM {$wpdb->blogs}
            WHERE site_id = '{$wpdb->siteid}'
            AND spam = '0'
            AND deleted = '0'
            AND archived = '0'
        "
		);
	}

	/**
	 * Return the plugin support url: the forum on WordPress.org.
	 *
	 * @return string
	 */
	public static function get_plugin_support_url(): string {
		return 'https://wordpress.org/support/plugin/product-swatches-light/';
	}

	/**
	 * Return the settings-URL.
	 *
	 * @return string
	 */
	public static function get_settings_url(): string {
		return add_query_arg(
			array(
				'page' => 'wc-settings',
				'tab'  => 'lw_product_swatches',
			),
			get_admin_url() . 'admin.php'
		);
	}

	/**
	 * Checks if the current request is a WP REST API request.
	 *
	 * Case #1: After WP_REST_Request initialization
	 * Case #2: Support "plain" permalink settings and check if `rest_route` starts with `/`
	 * Case #3: It can happen that WP_Rewrite is not yet initialized,
	 *          so do this (wp-settings.php)
	 * Case #4: URL Path begins with wp-json/ (your REST prefix)
	 *          Also supports WP installations in the subfolders
	 *
	 * @returns boolean
	 * @author matzeeable
	 */
	public static function is_rest_request(): bool {
		if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) // Case #1.
			|| ( isset( $GLOBALS['wp']->query_vars['rest_route'] ) // (#2)
					&& str_starts_with( $GLOBALS['wp']->query_vars['rest_route'], '/' ) ) ) {
			return true;
		}

		// Case #3.
		global $wp_rewrite;
		if ( is_null( $wp_rewrite ) ) {
			$wp_rewrite = new WP_Rewrite();
		}

		// Case #4.
		$rest_url    = wp_parse_url( trailingslashit( rest_url() ) );
		$current_url = wp_parse_url( add_query_arg( array() ) );
		if ( is_array( $current_url ) && is_array( $rest_url ) && isset( $current_url['path'], $rest_url['path'] ) ) {
			return str_starts_with( $current_url['path'], $rest_url['path'] );
		}
		return false;
	}
}
