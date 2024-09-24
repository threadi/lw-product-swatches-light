<?php
/**
 * File to handle single product for swatches.
 *
 * @package product-swatches-light
 */

namespace ProductSwatchesLight\Swatches;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ProductSwatchesLight\Plugin\Helper;
use WC_Product_Variable;

/**
 * Extend the WooCommerce product object with our custom functions.
 */
class Product extends WC_Product_Variable {
	/**
	 * Update the swatches on the given product with only one parameter.
	 *
	 * @return void
	 */
	public function update_swatches(): void {
		// get the swatches code for this product.
		$html = $this->get_swatches();
		if ( empty( $html ) ) {
			// delete any swatches code to clean up product.
			$this->delete_swatches();
		} else {
			// save the resulting html-code on the product.
			update_post_meta( $this->get_id(), LW_SWATCH_CACHEKEY, $html );
		}
	}

	/**
	 * Delete the swatches on the given product.
	 *
	 * @return void
	 */
	public function delete_swatches(): void {
		delete_post_meta( $this->get_id(), LW_SWATCH_CACHEKEY );
	}

	/**
	 * Generate the swatches codes for this specific product.
	 *
	 * @return string
	 */
	public function get_swatches(): string {
		// bail if this is not a variable product.
		if ( ! $this->is_type( 'variable' ) ) {
			return '';
		}

		/**
		 * Go through all variants of this product
		 * and save their attributes in an array,
		 * if the respective variant is currently available.
		 */
		$attribute_terms_to_display = array();
		$images                     = array();
		$images_sets                = array();
		$on_sales                   = array();
		$attribute_types            = Helper::get_attribute_types();
		$children                   = $this->get_children();
		$count_children             = count( $children );
		for ( $c = 0;$c < $count_children;$c++ ) {
			// get the child as object.
			$child        = wc_get_product( $children[ $c ] );
			$stock_status = $child->get_stock_status();
			/**
			 * Filter only for specific stock status.
			 *
			 * @since 1.0.0 Available since 1.0.0.
			 * @param string $stock_status The actual stock status.
			 * @param WC_Product_Variable $child The child product.
			 */
			if ( $child->is_purchasable() && 'instock' === apply_filters( 'lw_swatches_product_stockstatus', $stock_status, $child ) ) {
				// get its attributes.
				$attributes = $child->get_attributes();

				// loop through the attribute-types this plugin supports.
				$keys            = array_keys( $attributes );
				$attribute_count = count( $attributes );
				for ( $a = 0;$a < $attribute_count;$a++ ) {
					$type         = $keys[ $a ];
					$slug         = $attributes[ $type ];
					$attribute_id = wc_attribute_taxonomy_id_by_name( $type );
					/**
					 * Hide attribute by its term id.
					 *
					 * @since 1.0.0 Available since 1.0.0
					 * @param int $attribute_id The term id.
					 */
					if ( apply_filters( 'lw_swatches_hide_attribute', $attribute_id ) > 0 ) {
						$attribute_object = wc_get_attribute( $attribute_id );
						if ( ! empty( $attribute_types[ $attribute_object->type ] ) ) {
							// get variant thumbnail and add it to list.
							$attachment_id = get_post_thumbnail_id( $children[ $c ] );
							if ( $attachment_id > 0 ) {
								$images[ $slug ]      = wp_get_attachment_url( $attachment_id );
								$images_sets[ $slug ] = wp_get_attachment_image_srcset( $attachment_id );
							}

							// get sales marker.
							if ( empty( $on_sales[ $slug ] ) || ( 0 === absint( $on_sales[ $slug ] ) ) ) {
								$on_sales[ $slug ] = $child->is_on_sale() ? 1 : 0;
							}

							// add this attribute (e.g. a specific size) to the list.
							$attribute_terms_to_display[ $attribute_object->type ][] = array(
								'slug' => $slug,
								'type' => $type,
							);
						}
					}
				}
			}
		}

		if ( ! empty( $attribute_terms_to_display ) ) {
			// create the HTML code for the category page from the determined terms.
			$html = '';
			foreach ( $attribute_terms_to_display as $term_name => $attribute_term_to_display ) {
				/**
				 * Filter the used attribute type.
				 *
				 * @since 1.0.0 Available since 1.0.0.
				 * @param string $term_name The type name.
				 */
				$attribute_type = apply_filters( 'product_swatches_light_change_attribute_type_name', $term_name );

				// determine all available properties to find their names and values.
				$values       = array();
				$list         = array();
				$term_count_2 = count( $attribute_term_to_display );
				for ( $t = 0;$t < $term_count_2;$t++ ) {
					$terms      = get_terms(
						array(
							'taxonomy'   => $attribute_term_to_display[ $t ]['type'],
							'hide_empty' => false,
						)
					);
					$term_count = count( $terms );
					for ( $t2 = 0;$t2 < $term_count;$t2++ ) {
						$term = $terms[ $t2 ];
						// add only available terms to the resulting list.
						if ( $term->slug === $attribute_term_to_display[ $t ]['slug'] ) {
							$values[ $term->slug ] = apply_filters( 'product_swatches_light_get_attribute_values', array(), $attribute_type, $term, $term_name );
							$list[ $term->slug ]   = $term;
						}
					}
				}

				// get terms of product with ordering.
				$terms = wc_get_product_terms(
					$this->get_id(),
					$attribute_term_to_display[0]['type'],
					array(
						'fields' => 'all',
					)
				);

				// sort our own list accordingly.
				$resulting_list = array();
				$term_count     = count( $terms );
				for ( $t = 0;$t < $term_count;$t++ ) {
					$term = $terms[ $t ];
					if ( ! empty( $list[ $term->slug ] ) ) {
						$resulting_list[] = $list[ $term->slug ];
					}
				}

				// generate output depending on the attribute-type.
				$html .= apply_filters( 'product_swatches_light_get_list', '', $attribute_type, $resulting_list, $images, $images_sets, $values, $on_sales, $this->get_permalink(), $this->get_title() );
			}
			return $html;
		}
		return '';
	}
}
