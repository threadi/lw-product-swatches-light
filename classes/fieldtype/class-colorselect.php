<?php

namespace LW_Swatches\FieldType;

use LW_Swatches\fieldtype;
use LW_Swatches\helper;

/**
 * Handling of field-type colorselect.
 */
class colorselect implements fieldtype {

    /**
     * Return the secured value for this field-type.
     *
     * @param $param
     * @return string
     */
    public static function getSecuredValue($param): string
    {
        return esc_html($param);
    }

    /**
     * Return the html-code for editing this field in backend.
     *
     * @param mixed ...$param
     * @return string
     */
    public static function getField( ...$param ): string
    {
        if( count($param) != 6 ) {
            return '';
        }

        // create list
        $html = '<select name="'.esc_attr($param[0]).'" id="'.esc_attr($param[5]).'" '.(false !== $param[3] ? 'required' : '').'>
            <option value=""></option>';
        foreach( helper::getAllowedColors() as $value => $title ) {
            $html .= '<option value="'.esc_attr($value).'"'.selected($value, $param[1], false).'>'.esc_html($title).'</option>';
        }
        $html .= '</select>';

        // return resulting list
        return $html;
    }
}