<?php
/**
 * File for attribute type color handler.
 *
 * @package product-swatches-light
 */

namespace ProductSwatchesLight\Swatches\AttributeType;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ProductSwatchesLight\Plugin\Helper;
use ProductSwatchesLight\Plugin\Templates;
use ProductSwatchesLight\Swatches\AttributeType;
use WP_Taxonomy;
use WP_Term;

/**
 * Object to handle attribute-type color.
 */
class Color implements AttributeType {

	/**
	 * Set the single type name.
	 */
	const _TYPE_NAME = 'color';

	/**
	 * Set the plural type name.
	 */
	const _TYPE_NAMES = 'colors';

	/**
	 * Output of color-attribute-type in any list-view.
	 *
	 * @param array<int,WP_Term>   $item_list The list.
	 * @param array<string,string> $images The images.
	 * @param array<string,string> $images_sets The image sets.
	 * @param array<string,mixed>  $values The values.
	 * @param array<string,mixed>  $on_sales The sales markers.
	 * @param string               $product_link The product URL.
	 * @param string               $product_title The product title.
	 * @return string
	 */
	public static function get_list( array $item_list, array $images, array $images_sets, array $values, array $on_sales, string $product_link, string $product_title ): string {
		$html             = '';
		$taxonomy         = '';
		$color_list_count = count( $item_list );
		for ( $l = 0;$l < $color_list_count;$l++ ) {
			$color = $item_list[ $l ];
			// get color.
			$color1 = $values[ $color->slug ][0];

			// get taxonomy-id.
			$taxonomy_id = wc_attribute_taxonomy_id_by_name( $color->taxonomy );

			// get the taxonomy name.
			$taxonomy = $color->taxonomy;

			// get the taxonomy object.
			$taxonomy_obj = get_taxonomy( $taxonomy );

			// bail if taxonomy could not be loaded.
			if ( ! $taxonomy_obj instanceof WP_Taxonomy ) {
				continue;
			}

			// get the label.
			$label = get_taxonomy_labels( $taxonomy_obj )->singular_name;

			// get variant thumb image.
			$thumb_image = Helper::get_variant_thumb_as_data( $images, $images_sets, $color->slug );
			$image       = '';
			$srcset      = '';
			if ( ! empty( $thumb_image ) ) {
				$image  = $thumb_image['image'];
				$srcset = $thumb_image['srcset'];
			}

			$li_class = '';
			$class    = 'lw_swatches_' . self::_TYPE_NAME . '_' . $color->slug;
			/**
			 * Filter for class.
			 *
			 * @since 1.0.0 Available since 1.0.0.
			 * @param string $class The class name.
			 * @param int $taxonomy_id The used taxonomy ID.
			 */
			$class = apply_filters( 'product_swatches_set_class', $class, $taxonomy_id );

			/**
			 * Filter the link.
			 *
			 * @since 1.0.0 Available since 1.0.0.
			 *
			 * @param string $link The link to use.
			 * @param int $taxonomy_id The taxonomy ID.
			 * @param WP_Term $color The used color.
			 * @param string $product_link The product URL.
			 */
			$link = apply_filters( 'product_swatches_light_set_link', '', $taxonomy_id, $color, $product_link );

			// set slug.
			$slug = $color->slug;

			// set title.
			$title = $product_title . ' ' . $label . ' ' . $color->name;

			// set CSS.
			$css = 'background-color: ' . $color1;

			// set text.
			$text = '';

			// set sale.
			$sale = $on_sales[ $color->slug ];

			// set onclick.
			$onclick_link = '';

			// add output.
			if ( ! empty( $color1 ) ) {
				ob_start();
				if ( ! empty( $link ) ) {
					include Templates::get_instance()->get_template( 'parts/list-item-linked.php' );
				} else {
					include Templates::get_instance()->get_template( 'parts/list-item.php' );
				}
				$html .= ob_get_clean();
			}
		}
		return Templates::get_instance()->get_html_list( $html, self::_TYPE_NAMES, self::_TYPE_NAME, $taxonomy, false );
	}

	/**
	 * Output of color-attribute-type in taxonomy-column in backend under Products > Attributes.
	 *
	 * @param int                 $term_id The term id.
	 * @param array<string,mixed> $fields The fields.
	 * @return string
	 */
	public static function get_taxonomy_column( int $term_id, array $fields ): string {
		// get the values.
		list( $color1 ) = self::get_values( $term_id, self::_TYPE_NAME );

		// set CSS.
		$css = 'background-color: ' . $color1;

		// create output.
		$html = '';
		if ( ! empty( $color1 ) ) {
			$html = '<div class="lw-swatches lw-swatches-' . esc_attr( self::_TYPE_NAME ) . '" style="' . esc_attr( $css ) . '"></div>';
		}

		// return resulting HTML-code.
		return $html;
	}

	/**
	 * Return values of a field with this attribute-type.
	 *
	 * @param int                        $term_id The term id.
	 * @param string|array<string,mixed> $term The term name.
	 * @return array<int,mixed>
	 */
	public static function get_values( int $term_id, string|array $term ): array {
		if ( ! is_array( $term ) ) {
			$term = Helper::get_attribute_types()[ $term ]['fields'];
		}

		$color = get_term_meta( $term_id, $term['color']['name'], true );

		// check if value is an allowed value
		// --> if not set $color to nothing to prevent output.
		if ( ! array_key_exists( $color, Helper::get_colors() ) ) {
			$color = '';
		}

		// return as array.
		return array(
			$color,
		);
	}
}
