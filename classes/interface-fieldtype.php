<?php

namespace LW_Swatches;

/**
 * Interface for each field-type this plugin supports.
 * E.g. color, image, text ..
 */
interface fieldType {

    /**
     * Return a secured variable for this field-content.
     *
     * @param $param
     * @return mixed
     */
    public static function getSecuredValue( $param );

    /**
     * Return the html-code for editing this field in backend.
     *
     * @param $param
     * @return mixed
     */
    public static function getField( ...$param );

}