<?php

namespace LW_Swatches\AttributeType;

use LW_Swatches\attributeType;
use LW_Swatches\helper;

/**
 * Object to handle attribute-type color.
 */
class color implements attributeType
{

    use helper;

    // set type name singular
    const _typeName = 'color';

    // set type name plural
    const _typeNames = 'colors';

    /**
     * Output of color-attribute-type in any list-view.
     *
     * @param $list
     * @param $images
     * @param $imagesSets
     * @param $values
     * @param $onSales
     * @param $product
     * @return string
     */
    public static function getList( $list, $images, $imagesSets, $values, $onSales, $product ): string
    {
        $html = '';
        $taxonomy = '';
        for( $l=0;$l<count($list);$l++ ) {
            $color = $list[$l];
            // get color
            $color1 = $values[$color->slug][0];

            // get taxonomy-id
            $taxonomy_id = wc_attribute_taxonomy_id_by_name( $color->taxonomy );
            $taxonomy = $color->taxonomy;
            $label = get_taxonomy_labels(get_taxonomy($taxonomy))->singular_name;

            // get variant thumb image
            $thumbImage = (new color)->getVariantThumbAsData($images, $imagesSets, $color->slug);
            $image = '';
            $srcset = '';
            if( !empty($thumbImage) ) {
                $image = $thumbImage['image'];
                $srcset = $thumbImage['srcset'];
            }

            // set class
            $class = apply_filters( 'lw_swatches_set_class', 'lw_swatches_'.self::_typeName.'_' . sanitize_text_field($color->slug), $taxonomy_id);

            // set link
            $link = apply_filters( 'lw_swatches_set_link', '', $taxonomy_id, $color, $product);

            // set slug
            $slug = $color->slug;

            // set title
            $title = $product->get_title().' '.$label.' '.$color->name;

            // set CSS
            $css = 'background-color: ' . $color1;

            // set text
            $text = '';

            // set sale
            $sale = $onSales[$color->slug];

            // add output
            if( !empty($color1) ) {
                ob_start();
                if( !empty($link) ) {
                    include helper::getTemplate('parts/list-item-linked.php');
                }
                else {
                    include helper::getTemplate('parts/list-item.php');
                }
                $html .= ob_get_clean();
            }
        }
        return helper::getHTMList($html, self::_typeNames, self::_typeName, $taxonomy);
    }

    /**
     * Output of color-attribute-type in taxonomy-column in backend under Products > Attributes.
     *
     * @param $term_id
     * @param $fields
     * @return string
     */
    public static function getTaxonomyColumn( $term_id, $fields ): string
    {
        // get the values
        list( $color1 ) = self::getValues( $term_id, $fields );

        // set CSS
        $css = 'background-color: '.$color1;

        // create output
        $html = '';
        if( !empty($color1) ) {
            $html = '<div class="lw-swatches lw-swatches-'.esc_attr(self::_typeName).'" style="'.esc_attr($css).'"></div>';
        }

        return $html;
    }

    /**
     * Return values of a field with this attribute-type.
     *
     * @param $term_id
     * @param $termName
     * @return array
     */
    public static function getValues( $term_id, $termName ): array
    {
        if( !is_array($termName) ) {
            $termName = helper::getAttributeTypes()[$termName]['fields'];
        }

        $color = sanitize_text_field(get_term_meta($term_id, $termName['color']['name'], true));

        // check if value is an allowed value
        // --> if not set $color to nothing to prevent output
        if( !array_key_exists($color, helper::getAllowedColors()) ) {
            $color = '';
        }

        // return as array
        return [
            $color
        ];
    }
}