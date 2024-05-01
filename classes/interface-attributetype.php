<?php
/**
 * File to handle base functions for attribute types.
 *
 * @package product-swatches-light
 */

namespace LW_Swatches;

/**
 * Interface for each attribute-type this plugin supports.
 */
interface AttributeType {
	/**
	 * Output on list page.
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
	public static function get_list( array $items, array $images, array $image_sets, array $values, array $on_sales, string $product_link, string $product_title ): string;

	/**
	 * Output on taxonomy table in backend.
	 *
	 * @param int   $term_id The term ID.
	 * @param array $fields List of fields.
	 * @return string
	 */
	public static function get_taxonomy_column( int $term_id, array $fields ): string;

	/**
	 * Return values of a field with this attribute-type.
	 *
	 * @param int          $term_id The term ID.
	 * @param array|string $term_name The term name.
	 * @return array
	 */
	public static function get_values( int $term_id, array|string $term_name ): array;
}
