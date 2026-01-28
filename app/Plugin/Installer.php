<?php
/**
 * File to handle plugin installation.
 *
 * @package product-swatches-light
 */

namespace ProductSwatchesLight\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ProductSwatchesLight\Dependencies\easyTransientsForWordPress\Transients;
use ProductSwatchesLight\Swatches\Products;
use WC_Cache_Helper;

/**
 * Helper-function for plugin-activation and -deinstallation.
 */
class Installer {

	/**
	 * Instance of the actual object.
	 *
	 * @var Installer|null
	 */
	private static ?Installer $instance = null;

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
	 * @return Installer
	 */
	public static function get_instance(): Installer {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public function initialize_plugin(): void {
		// check if WooCommerce is installed.
		if ( ! Helper::is_woocommerce_activated() ) {
			$url           = add_query_arg(
				array(
					's'    => 'woocommerce',
					'tab'  => 'search',
					'type' => 'term',
				),
				'plugin-install.php'
			);
			$transient_obj = Transients::get_instance()->add();
			$transient_obj->set_name( 'lwps_woocommerce_missing' );
			/* translators: %1$s is replaced with the search for woocommerce in the plugin repository. */
			$transient_obj->set_message( sprintf( __( '<strong>Product Swatches Light could not be activated!</strong> Please <a href="%1$s">install and activate WooCommerce</a> first.', 'product-swatches-light' ), esc_url( $url ) ) );
			$transient_obj->save();

			// run no further tasks.
			return;
		}

		// enable delete all data on uninstall.
		if ( ! get_option( 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_delete_on_uninstall', false ) ) {
			update_option( 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_delete_on_uninstall', 'yes' );
		}

		// enable delete all data on uninstall.
		if ( ! get_option( 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_disable_cache', false ) ) {
			update_option( 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_disable_cache', 'no' );
		}

		// run all updates.
		Updates::run_all_updates();
	}

	/**
	 * Remove all data of this plugin.
	 *
	 * @param array<int,string> $delete_data Configuration.
	 * @return void
	 */
	public function remove_all_data( array $delete_data = array() ): void {
		// delete transients.
		foreach ( Transients::get_instance()->get_transients() as $transient_obj ) {
			$transient_obj->delete();
		}

		// delete all data the plugin has collected on uninstallation
		// -> only if this is enabled.
		if ( ( Helper::is_woocommerce_activated() && 'yes' === get_option( 'wc_lw_product_swatches_delete_on_uninstall', 'no' ) ) || ( ! empty( $delete_data[0] ) && 1 === absint( $delete_data[0] ) ) ) {
			global $wpdb, $table_prefix;

			// delete the attribute-metas.
			$attributes      = wc_get_attribute_taxonomies();
			$attribute_types = Helper::get_attribute_types();
			foreach ( $attributes as $attribute ) {
				if ( ! empty( $attribute_types[ $attribute->attribute_type ] ) ) {
					$fields = $attribute_types[ $attribute->attribute_type ]['fields'];
					foreach ( $fields as $field ) {
						$wpdb->delete( $table_prefix . 'termmeta', array( 'meta_key' => $field['name'] ) );
					}
				}
			}

			// delete the swatches.
			Products::get_instance()->delete_all_swatches_on_products();

			// remove configured attribute-types on the attributes
			// -> replace our own types with the WooCommerce-default "select".
			foreach ( $attribute_types as $attribute_type_name => $attribute_type ) {
				$wpdb->update(
					$wpdb->prefix . 'woocommerce_attribute_taxonomies',
					array(
						'attribute_type' => 'select',
					),
					array( 'attribute_type' => $attribute_type_name )
				);
			}

			// clear cache and flush rewrite rules.
			wp_schedule_single_event( time(), 'woocommerce_flush_rewrite_rules' );
			delete_transient( 'wc_attribute_taxonomies' );
			WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
		}

		// delete options.
		$options = array(
			LW_SWATCHES_OPTION_MAX,
			LW_SWATCHES_OPTION_COUNT,
			LW_SWATCHES_UPDATE_RUNNING,
			LW_SWATCHES_UPDATE_STATUS,
			LW_SWATCHES_TRANSIENTS_LIST,
			// WooCommerce-settings.
			'wc_' . LW_SWATCH_WC_SETTING_NAME . '_delete_on_uninstall',
			'wc_' . LW_SWATCH_WC_SETTING_NAME . '_disable_cache',
		);
		foreach ( $options as $option ) {
			delete_option( $option );
		}
	}
}
