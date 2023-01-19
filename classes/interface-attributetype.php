<?php

namespace LW_Swatches;

/**
 * Interface for each attribute-type this plugin supports.
 */
interface attributeType {
    /**
     * Output on list page.
     *
     * @param $list
     * @param $images
     * @param $imagesSets
     * @param $values
     * @param $onSales
     * @param $product
     * @return string
     */
    public static function getList( $list, $images, $imagesSets, $values, $onSales, $product ): string;

    /**
     * Output on taxonomy table in backend.
     *
     * @param $term_id
     * @param $fields
     * @return string
     */
    public static function getTaxonomyColumn( $term_id, $fields ): string;

    /**
     * Return values of a field with this attribute-type.
     *
     * @param $term_id
     * @param $termName
     * @return array
     */
    public static function getValues( $term_id, $termName ): array;
}