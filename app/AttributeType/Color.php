<?php
/**
 * File for attribute type color handler.
 *
 * @package product-swatches-light
 */

namespace ProductSwatches\AttributeType;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ProductSwatches\AttributeType;
use ProductSwatches\Plugin\Helper;

/**
 * Object to handle attribute-type color.
 */
class Color implements AttributeType {

	/**
	 * Set single type name.
	 */
	const _TYPE_NAME = 'color';

	/**
	 * Set plural type name.
	 */
	const _TYPE_NAMES = 'colors';

	/**
	 * Output of color-attribute-type in any list-view.
	 *
	 * @param array  $item_list The list.
	 * @param array  $images The images.
	 * @param array  $images_sets The image sets.
	 * @param array  $values The values.
	 * @param array  $on_sales The sales markers.
	 * @param string $product_link The product URL.
	 * @param string $product_title The product title.
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
			$taxonomy    = $color->taxonomy;
			$label       = get_taxonomy_labels( get_taxonomy( $taxonomy ) )->singular_name;

			// get variant thumb image.
			$thumb_image = Helper::get_variant_thumb_as_data( $images, $images_sets, $color->slug );
			$image       = '';
			$srcset      = '';
			if ( ! empty( $thumb_image ) ) {
				$image  = $thumb_image['image'];
				$srcset = $thumb_image['srcset'];
			}

			// set class.
			$class = apply_filters( 'lw_swatches_set_class', 'lw_swatches_' . self::_TYPE_NAME . '_' . $color->slug, $taxonomy_id );

			// set link.
			$link = apply_filters( 'lw_swatches_set_link', '', $taxonomy_id, $color, $product_link );

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

			// add output.
			if ( ! empty( $color1 ) ) {
				ob_start();
				if ( ! empty( $link ) ) {
					include helper::get_template( 'parts/list-item-linked.php' );
				} else {
					include helper::get_template( 'parts/list-item.php' );
				}
				$html .= ob_get_clean();
			}
		}
		return Helper::get_html_list( $html, self::_TYPE_NAMES, self::_TYPE_NAME, $taxonomy, false );
	}

	/**
	 * Output of color-attribute-type in taxonomy-column in backend under Products > Attributes.
	 *
	 * @param int    $term_id The term id.
	 * @param string $fields The fields.
	 * @return string
	 */
	public static function get_taxonomy_column( int $term_id, string $fields ): string {
		// get the values.
		list( $color1 ) = self::get_values( $term_id, $fields );

		// set CSS.
		$css = 'background-color: ' . $color1;

		// create output.
		$html = '';
		if ( ! empty( $color1 ) ) {
			$html = '<div class="lw-swatches lw-swatches-' . esc_attr( self::_TYPE_NAME ) . '" style="' . esc_attr( $css ) . '"></div>';
		}

		return $html;
	}

	/**
	 * Return values of a field with this attribute-type.
	 *
	 * @param int    $term_id The term id.
	 * @param string $term_name The term name.
	 * @return array
	 */
	public static function get_values( int $term_id, string $term_name ): array {
		$term_name = helper::get_attribute_types()[ $term_name ]['fields'];

		$color = get_term_meta( $term_id, $term_name['color']['name'], true );

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
