<?php
/**
 * File for interface for each field type.
 *
 * @package product-swatches-light
 */

namespace ProductSwatchesLight\Swatches;

/**
 * Interface for each field-type this plugin supports.
 * E.g. color, image, text etc.
 */
interface FieldType {

	/**
	 * Return a secured variable for this field-content.
	 *
	 * @param string $param The string to secure.
	 *
	 * @return string
	 */
	public static function get_secured_value( string $param ): string;

	/**
	 * Return the html-code for editing this field in backend.
	 *
	 * @param mixed ...$param List of params.
	 * @return string
	 */
	public static function get_field( ...$param ): string;
}
