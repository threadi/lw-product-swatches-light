<?php

namespace LW_Swatches;

/**
 * Handling of changes on products regarding swatches.
 */

class Product {

    /**
     * Update the swatches on the given product with only one parameter.
     *
     * @param $product
     * @return void
     */
    public static function update($product): void {
        // get the swatches code
        $html = self::getSwatches($product);
        if( empty($html) ) {
            // delete any code to cleanup product
            self::delete($product->get_id());
        }
        else {
            // save the resulting html-code
            update_post_meta($product->get_id(), LW_SWATCH_CACHEKEY, $html);
        }
    }

    /**
     * Update the swatches on the given product with 2 parameters.
     *
     * @param $product_id
     * @param $product
     * @return void
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    public static function update2($product_id, $product): void {
        self::update($product);
    }

    /**
     * Delete the swatches on the given product.
     *
     * @param $productId
     * @return void
     */
    public static function delete($productId): void {
        delete_post_meta($productId, LW_SWATCH_CACHEKEY);
    }

    /**
     * Generate the swatches codes for this specific product.
     *
     * @param $product
     * @return string
     */
    public static function getSwatches($product): string
    {
        // only if products is variable
        if( $product->is_type( 'variable') ){
            // go through all variants of this product
            // and save their color expression in an array,
            // if the respective variant is currently available
            $attributeTermsToDisplay = [];
            $images = [];
            $imagesSets = [];
            $onSales = [];
            $attribute_types = helper::getAttributeTypes();
            $children = $product->get_children();
            $count_children = count($children);
            for( $c=0;$c<$count_children;$c++ ) {
                // get the child as object
                $child = wc_get_product($children[$c]);

                // only if variant is purchasable
                if( $child->is_purchasable() && apply_filters('lw_swatches_product_stockstatus', $child->get_stock_status() == 'instock', $child ) ) {
                    // get its attributes
                    $attributes = $child->get_attributes();

                    // loop through the attribute-types this plugin supports
                    $keys = array_keys($attributes);
                    for( $a=0;$a<count($attributes);$a++ ) {
                        $type = $keys[$a];
                        $slug = $attributes[$type];
                        $attributeId = wc_attribute_taxonomy_id_by_name($type);
                        if( apply_filters('lw_swatches_hide_attribute', $attributeId) && $attributeId > 0 ) {
                            $attributeObject = wc_get_attribute($attributeId);
                            if( !empty($attribute_types[$attributeObject->type]) ) {
                                // get variant thumbnail and add it to list
                                $attachment_id = get_post_thumbnail_id($children[$c]);
                                if ($attachment_id > 0) {
                                    $images[$slug] = wp_get_attachment_url($attachment_id);
                                    $imagesSets[$slug] = wp_get_attachment_image_srcset($attachment_id);
                                }

                                // get sales marker
                                if( empty($onSales[$slug]) || ($onSales[$slug] == 0) ) {
                                    $onSales[$slug] = $child->is_on_sale() ? 1 : 0;
                                }

                                // add this attribute (e.g. a specific size) to the list
                                $attributeTermsToDisplay[$attributeObject->type][] = [
                                    'slug' => $slug,
                                    'type' => $type
                                ];
                            }
                        }
                    }
                }
            }

            if( !empty($attributeTermsToDisplay) ) {
                // create the HTML code for the category page from the determined terms
                $html = '';
                $keys = array_keys($attributeTermsToDisplay);
                for( $a=0;$a<count($attributeTermsToDisplay);$a++ ) {
                    $termName = $keys[$a];
                    $attributeTerm = $attributeTermsToDisplay[$termName];
                    $attribute_type = apply_filters('lw_swatches_change_attribute_type_name', $termName);

                    // determine all available properties to find their names and values
                    $values = [];
                    $list = [];
                    for( $t=0;$t<count($attributeTerm);$t++ ) {
                        $terms = get_terms(['taxonomy' => $attributeTerm[$t]['type'], 'hide_empty' => false]);
                        for( $t2=0;$t2<count($terms);$t2++ ) {
                            $term = $terms[$t2];
                            // add only available terms to the resulting list
                            if( $attributeTerm[$t]['slug'] == $term->slug ) {
                                $values[$term->slug] = [];
                                // generate output depending on the attribute-type
                                $className = '\LW_Swatches\AttributeType\\' . $attribute_type . '::getValues';
                                if (class_exists("\LW_Swatches\AttributeType\\" . $attribute_type)
                                    && is_callable($className)) {
                                    $values[$term->slug] = call_user_func($className, $term->term_id, $termName);
                                }
                                $list[$term->slug] = $term;
                            }
                        }
                    }

                    // get terms of product with ordering
                    $terms = wc_get_product_terms(
                        $product->get_id(),
                        $attributeTerm[0]['type'],
                        array(
                            'fields' => 'all',
                        )
                    );
                    // sort our own list accordingly
                    $resulting_list = [];
                    for( $t=0;$t<count($terms);$t++ ) {
                        $term = $terms[$t];
                        if( !empty($list[$term->slug]) ) {
                            $resulting_list[] = $list[$term->slug];
                        }
                    }

                    // generate output depending on the attribute-type
                    $className = '\LW_Swatches\AttributeType\\'.$attribute_type.'::getList';
                    if( class_exists("\LW_Swatches\AttributeType\\".$attribute_type)
                        && is_callable($className) ) {
                        $html .= call_user_func($className, $resulting_list, $images, $imagesSets, $values, $onSales, $product->get_permalink(), $product->get_title());
                    }
                }
                return $html;
            }
        }
        return '';
    }
}