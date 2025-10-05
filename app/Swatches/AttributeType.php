<?php
/**
 * File for interface for each attribute type.
 *
 * @package product-swatches-light
 */

namespace ProductSwatchesLight\Swatches;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use WP_Term;

/**
 * Interface for each attribute-type this plugin supports.
 */
interface AttributeType {
	/**
	 * Output on list page.
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
	public static function get_list( array $item_list, array $images, array $images_sets, array $values, array $on_sales, string $product_link, string $product_title ): string;

	/**
	 * Output on taxonomy table in backend.
	 *
	 * @param int                 $term_id The term id.
	 * @param array<string,mixed> $fields The fields.
	 *
	 * @return string
	 */
	public static function get_taxonomy_column( int $term_id, array $fields ): string;

	/**
	 * Return values of a field with this attribute-type.
	 *
	 * @param int                        $term_id The term id.
	 * @param string|array<string,mixed> $term The term settings.
	 * @return array<string,mixed>
	 */
	public static function get_values( int $term_id, string|array $term ): array;
}
