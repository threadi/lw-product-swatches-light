<?php
/**
 * File for interface for each attribute type.
 *
 * @package product-swatches-light
 */

namespace ProductSwatches;

/**
 * Interface for each attribute-type this plugin supports.
 */
interface AttributeType {
	/**
	 * Output on list page.
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
	public static function get_list( array $item_list, array $images, array $images_sets, array $values, array $on_sales, string $product_link, string $product_title ): string;

	/**
	 * Output on taxonomy table in backend.
	 *
	 * @param int    $term_id The term id.
	 * @param string $fields The fields.
	 *
	 * @return string
	 */
	public static function get_taxonomy_column( int $term_id, string $fields ): string;

	/**
	 * Return values of a field with this attribute-type.
	 *
	 * @param int    $term_id The term id.
	 * @param string $term_name The term name.
	 * @return array
	 */
	public static function get_values( int $term_id, string $term_name ): array;
}
