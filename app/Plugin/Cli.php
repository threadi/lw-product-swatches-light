<?php
/**
 * File to handle cli tasks.
 *
 * @package product-swatches-light
 */

namespace ProductSwatchesLight\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ProductSwatchesLight\Swatches\Attribute;
use ProductSwatchesLight\Swatches\Products;

/**
 * Helper for CLI-handling with data of this plugin.
 */
class Cli {
	/**
	 * Updates the Swatches on all products or on given attributes.
	 *
	 * @param array $args Arguments for the update.
	 *
	 * @since  1.0.0
	 * @author Thomas Zwirner
	 * @noinspection PhpUnused
	 **/
	public function update_swatches( array $args = array() ): void {
		if ( empty( $args ) ) {
			Products::get_instance()->update_swatches_on_products();
		}
		if ( ! empty( $args ) && isset( $args[0] ) && isset( $args[1] ) ) {
			Products::get_instance()->update_swatches_on_products_by_type( $args[0], $args[1] );
		}
	}

	/**
	 * Remove the Swatches on all products.
	 *
	 * @since  1.0.0
	 * @author Thomas Zwirner
	 */
	public function delete_swatches(): void {
		Products::get_instance()->delete_all_swatches_on_products();
	}

	/**
	 * Migrate data from other swatches-plugin to this one.
	 *
	 * @return void
	 */
	public function migrate(): void {
		/**
		 * Migration from "Variation Swatches for WooCommerce" from "RadiusTheme"
		 */
		if ( is_plugin_active( 'woo-product-variation-swatches/woo-product-variation-swatches.php' ) ) {
			$fields          = array();
			$fields['color'] = array_merge(
				apply_filters(
					'rtwpvs_get_taxonomy_meta_color',
					array(
						array(
							'label' => esc_html__( 'Color', 'product-swatches-light' ),
							'desc'  => esc_html__( 'Choose a color.', 'product-swatches-light' ),
							'id'    => 'product_attribute_color',
							'type'  => 'color',
						),
					)
				),
				$fields
			);
			$meta_added_for  = apply_filters( 'rtwpvs_product_taxonomy_meta_for', array_keys( $fields ) );

			$attribute_types = Helper::get_attribute_types();

			// get all attribute-taxonomies.
			$attribute_taxonomies = wc_get_attribute_taxonomies();
			if ( ! empty( $attribute_taxonomies ) ) {
				foreach ( $attribute_taxonomies as $tax ) {
					$product_attr_type = $tax->attribute_type;
					if ( in_array( $product_attr_type, $meta_added_for, true ) ) {
						// secure taxonomy.
						$taxonomy = wc_attribute_taxonomy_name( $tax->attribute_name );

						// set our own fields.
						$our_fields = $attribute_types[ $product_attr_type ]['fields'];

						// data from own plugin.
						$terms = get_terms(
							array(
								'taxonomy'   => $taxonomy,
								'hide_empty' => false,
							)
						);
						foreach ( $terms as $term ) {
							$_POST['lws1'] = get_term_meta( $term->term_id, $fields[ $product_attr_type ][0]['id'], true );
							$_POST['lws2'] = 'yes' === get_term_meta( $term->term_id, $fields[ $product_attr_type ][1]['id'], true ) ? 1 : 0;
							$_POST['lws3'] = get_term_meta( $term->term_id, $fields[ $product_attr_type ][2]['id'], true );
							$obj           = new Attribute( $tax, $our_fields );
							$obj->save( $term->term_id, '', $taxonomy );
						}
					}
				}
			}
		}
	}

	/**
	 * Resets all settings of this plugin.
	 *
	 * @param array $delete_data Marker to delete data.
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function reset_plugin( array $delete_data = array() ): void {
		$installer_obj = Installer::get_instance();
		$installer_obj->remove_all_data( $delete_data );
		$installer_obj->initialize_plugin();
	}
}
