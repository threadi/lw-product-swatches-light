<?php
/**
 * Handler for file type of simple color selection.
 *
 * @package product-swatches-light
 */

namespace ProductSwatches\FieldType;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ProductSwatches\FieldType;
use ProductSwatches\Plugin\Helper;

/**
 * Handling of field-type colorselect.
 */
class Colorselect implements FieldType {

	/**
	 * Return the secured value for this field-type.
	 *
	 * @param string $param The string to secure.
	 * @return string
	 */
	public static function get_secured_value( $param ): string {
		return sanitize_text_field( $param );
	}

	/**
	 * Return the html-code for editing this field in backend.
	 *
	 * @param mixed ...$param The list of params.
	 * @return string
	 */
	public static function get_field( ...$param ): string {
		if ( 6 !== count( $param ) ) {
			return '';
		}

		// create list.
		$html = '<select name="' . esc_attr( $param[0] ) . '" id="' . esc_attr( $param[5] ) . '" ' . ( false !== $param[3] ? 'required' : '' ) . '>
            <option value=""></option>';
		foreach ( Helper::get_colors() as $value => $title ) {
			$html .= '<option value="' . esc_attr( $value ) . '"' . selected( $value, $param[1], false ) . '>' . esc_html( $title ) . '</option>';
		}
		$html .= '</select>';

		// return resulting list.
		return $html;
	}
}
