<?php
/**
 * File to handle single product for swatches.
 *
 * @package product-swatches-light
 */

namespace ProductSwatches\Swatches;

// prevent direct access.
use ProductSwatches\Plugin\Helper;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Handling of changes on products regarding swatches.
 */
class Product {
	/**
	 * Update the swatches on the given product with only one parameter.
	 *
	 * @param WC_Product $product The product as object.
	 * @return void
	 */
	public static function update( WC_Product $product ): void {
		// get the swatches code.
		$html = self::get_swatches( $product );
		if ( empty( $html ) ) {
			// delete any code to cleanup product.
			self::delete( $product->get_id() );
		} else {
			// save the resulting html-code.
			update_post_meta( $product->get_id(), LW_SWATCH_CACHEKEY, $html );
		}
	}

	/**
	 * Update the swatches on the given product with 2 parameters.
	 *
	 * @param int        $product_id The ID of the product.
	 * @param WC_Product $product The object of the product.
	 * @return void
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function update2( $product_id, $product ): void {
		// TODO necessary?
		self::update( $product );
	}

	/**
	 * Delete the swatches on the given product.
	 *
	 * @param int $product_id The id of the product.
	 * @return void
	 */
	public static function delete( int $product_id ): void {
		delete_post_meta( $product_id, LW_SWATCH_CACHEKEY );
	}

	/**
	 * Generate the swatches codes for this specific product.
	 *
	 * @param WC_Product $product The product as object.
	 * @return string
	 */
	public static function get_swatches( WC_Product $product ): string {
		// only if products is variable.
		if ( $product->is_type( 'variable' ) ) {
			// go through all variants of this product
			// and save their color expression in an array,
			// if the respective variant is currently available.
			$attribute_terms_to_display = array();
			$images                     = array();
			$images_sets                = array();
			$on_sales                   = array();
			$attribute_types            = Helper::get_attribute_types();
			$children                   = $product->get_children();
			$count_children             = count( $children );
			for ( $c = 0;$c < $count_children;$c++ ) {
				// get the child as object.
				$child = wc_get_product( $children[ $c ] );
				$stock_status = $child->get_stock_status();
				/**
				 * Filter only for specific stock status.
				 *
				 * @since 1.0.0 Available since 1.0.0.
				 * @param string $stock_status The actual stock status.
				 * @param WC_Product $child The child product.
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
						if ( apply_filters( 'lw_swatches_hide_attribute', $attribute_id ) && $attribute_id > 0 ) {
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
				$html       = '';
				$keys       = array_keys( $attribute_terms_to_display );
				$term_count = count( $attribute_terms_to_display );
				for ( $a = 0;$a < $term_count;$a++ ) {
					$term_name      = $keys[ $a ];
					$attribute_term = $attribute_terms_to_display[ $term_name ];

					/**
					 * Filter the used attribute type.
					 *
					 * @since 1.0.0 Available since 1.0.0.
					 * @param string $term_name The type name.
					 */
					$attribute_type = apply_filters( 'lw_swatches_change_attribute_type_name', $term_name );

					// determine all available properties to find their names and values.
					$values       = array();
					$list         = array();
					$term_count_2 = count( $attribute_term );
					for ( $t = 0;$t < $term_count_2;$t++ ) {
						$terms      = get_terms(
							array(
								'taxonomy'   => $attribute_term[ $t ]['type'],
								'hide_empty' => false,
							)
						);
						$term_count = count( $terms );
						for ( $t2 = 0;$t2 < $term_count;$t2++ ) {
							$term = $terms[ $t2 ];
							// add only available terms to the resulting list.
							if ( $term->slug === $attribute_term[ $t ]['slug'] ) {
								$values[ $term->slug ] = array();
								// generate output depending on the attribute-type.
								$class_name = '\ProductSwatches\AttributeType\\' . $attribute_type . '::get_values';
								if ( class_exists( '\ProductSwatches\AttributeType\\' . $attribute_type )
									&& is_callable( $class_name ) ) {
									$values[ $term->slug ] = call_user_func( $class_name, $term->term_id, $term_name );
								}
								$list[ $term->slug ] = $term;
							}
						}
					}

					// get terms of product with ordering.
					$terms = wc_get_product_terms(
						$product->get_id(),
						$attribute_term[0]['type'],
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
					$class_name = '\ProductSwatches\AttributeType\\' . $attribute_type . '::get_list';
					if ( class_exists( '\ProductSwatches\AttributeType\\' . $attribute_type )
						&& is_callable( $class_name ) ) {
						$html .= call_user_func( $class_name, $resulting_list, $images, $images_sets, $values, $on_sales, $product->get_permalink(), $product->get_title() );
					}
				}
				return $html;
			}
		}
		return '';
	}
}
