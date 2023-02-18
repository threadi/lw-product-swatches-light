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
     *
     * @return string
     */
    public static function getSecuredValue( $param ): string;

    /**
     * Return the html-code for editing this field in backend.
     *
     * @param $param
     * @return string
     */
    public static function getField( ...$param ): string;

}