<?php
/**
 * File to handle color swatches.
 *
 * @package product-swatches-light
 */

namespace LW_Swatches\AttributeType;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LW_Swatches\AttributeType;
use LW_Swatches\Helper;
use LW_Swatches\Swatches;

/**
 * Object to handle attribute-type color.
 */
class Color extends Swatches implements AttributeType {
	/**
	 * Set the type name for singular.
	 *
	 * @var string
	 */
	protected string $type_name = 'color';

	/**
	 * Set the type name for plural.
	 *
	 * @var string
	 */
	protected string $type_names = 'colors';

	/**
	 * Output of color-attribute-type in any list-view.
	 *
	 * @param array  $items List of entries.
	 * @param array  $images List of images.
	 * @param array  $image_sets List of image sets.
	 * @param array  $values List of values.
	 * @param array  $on_sales List of which is in sale.
	 * @param string $product_link The product link.
	 * @param string $product_title The product title.
	 * @return string
	 */
	public static function get_list( array $items, array $images, array $image_sets, array $values, array $on_sales, string $product_link, string $product_title ): string {
		$html       = '';
		$taxonomy   = '';
		$list_count = count( $items );
		for ( $l = 0;$l < $list_count;$l++ ) {
			$color = $items[ $l ];
			// get color.
			$color1 = $values[ $color->slug ][0];

			// get taxonomy-id.
			$taxonomy_id = wc_attribute_taxonomy_id_by_name( $color->taxonomy );
			$taxonomy    = $color->taxonomy;
			$label       = get_taxonomy_labels( get_taxonomy( $taxonomy ) )->singular_name;

			// get variant thumb image.
			$thumb_image = ( new color() )->get_variant_thumb_as_data( $images, $image_sets, $color->slug );
			$image       = '';
			$srcset      = '';
			if ( ! empty( $thumb_image ) ) {
				$image  = $thumb_image['image'];
				$srcset = $thumb_image['srcset'];
			}

			// set class.
			$class = apply_filters( 'lw_swatches_set_class', 'lw_swatches_' . ( new Color() )->get_type_name() . '_' . $color->slug, $taxonomy_id );

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
					include Helper::get_template( 'parts/list-item-linked.php' );
				} else {
					include Helper::get_template( 'parts/list-item.php' );
				}
				$html .= ob_get_clean();
			}
		}
		return Helper::get_html_list( $html, ( new Color() )->get_type_names(), ( new Color() )->get_type_name(), $taxonomy, false );
	}

	/**
	 * Output of color-attribute-type in taxonomy-column in backend under Products > Attributes.
	 *
	 * @param int   $term_id The term ID.
	 * @param array $fields The list of fields.
	 * @return string
	 */
	public static function get_taxonomy_column( int $term_id, array $fields ): string {
		// get the values.
		list( $color1 ) = self::get_values( $term_id, $fields );

		// set CSS.
		$css = 'background-color: ' . $color1;

		// create output.
		$html = '';
		if ( ! empty( $color1 ) ) {
			$html = '<div class="lw-swatches lw-swatches-' . esc_attr( ( new Color() )->get_type_name() ) . '" style="' . esc_attr( $css ) . '"></div>';
		}

		return $html;
	}

	/**
	 * Return values of a field with this attribute-type.
	 *
	 * @param int          $term_id The term ID.
	 * @param array|string $term_name The term name.
	 * @return array
	 */
	public static function get_values( int $term_id, array|string $term_name ): array {
		if ( ! is_array( $term_name ) ) {
			$term_name = helper::get_attribute_types()[ $term_name ]['fields'];
		}

		$color = get_term_meta( $term_id, $term_name['color']['name'], true );

		// check if value is an allowed value
		// --> if not set $color to nothing to prevent output.
		if ( ! array_key_exists( $color, helper::get_allowed_colors() ) ) {
			$color = '';
		}

		// return as array.
		return array(
			$color,
		);
	}
}
