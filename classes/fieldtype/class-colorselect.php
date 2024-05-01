<?php
/**
 * File to handle the field type Colorselect.
 *
 * @package product-swatches-light
 */

namespace LW_Swatches\FieldType;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LW_Swatches\FieldType;
use LW_Swatches\helper;

/**
 * Handling of field-type Colorselect.
 */
class Colorselect implements FieldType {

	/**
	 * Return the secured value for this field-type.
	 *
	 * @param string $param The string to secure.
	 * @return string
	 */
	public static function get_secured_value( string $param ): string {
		return sanitize_text_field( $param );
	}

	/**
	 * Return the html-code for editing this field in backend.
	 *
	 * @param array ...$param List of params.
	 * @return string
	 */
	public static function get_field( ...$param ): string {
		if ( 6 !== count( $param ) ) {
			return '';
		}

		// create list.
		$html = '<select name="' . esc_attr( $param[0] ) . '" id="' . esc_attr( $param[5] ) . '" ' . ( false !== $param[3] ? 'required' : '' ) . '>
            <option value=""></option>';
		foreach ( helper::get_allowed_colors() as $value => $title ) {
			$html .= '<option value="' . esc_attr( $value ) . '"' . selected( $value, $param[1], false ) . '>' . esc_html( $title ) . '</option>';
		}
		$html .= '</select>';

		// return resulting list.
		return $html;
	}
}
