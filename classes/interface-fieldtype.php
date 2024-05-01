<?php
/**
 * File to handle base functions for field types.
 *
 * @package product-swatches-light
 */

namespace LW_Swatches;

/**
 * Interface for each field-type this plugin supports.
 * E.g. color, image, text etc.
 */
interface FieldType {
	/**
	 * Return a secured variable for this field-content.
	 *
	 * @param string $param Value to secure.
	 *
	 * @return string
	 */
	public static function get_secured_value( string $param ): string;

	/**
	 * Return the html-code for editing this field in backend.
	 *
	 * @param array ...$params List of params.
	 * @return string
	 */
	public static function get_field( ...$params ): string;
}
