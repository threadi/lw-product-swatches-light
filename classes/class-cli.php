<?php

namespace LW_Swatches;

/**
 * Helper for CLI-handling with data of this plugin.
 */
class cli
{

    /**
     * Updates the Swatches on all products or on given attributes.
     *
     * @since  1.0.0
     * @author Thomas Zwirner
     */
    public function update($args)
    {
        if (empty($args)) {
            helper::updateSwatchesOnProducts();
        }
        if (!empty($args) && isset($args[0]) && isset($args[1])) {
            helper::updateSwatchesOnProductsByType($args[0], $args[1]);
        }
    }

    /**
     * Remove the Swatches on all products.
     *
     * @since  1.0.0
     * @author Thomas Zwirner
     */
    public function delete()
    {
        helper::deleteAllSwatchesOnProducts();
    }

    /**
     * Migrate data from other swatches-plugin to this one.
     *
     * @return void
     */
    public function migrate()
    {
        /**
         * Migration from "Variation Swatches for WooCommerce" from "RadiusTheme"
         */
        if( is_plugin_active('woo-product-variation-swatches/woo-product-variation-swatches.php') ) {
            $fields = [];
            $fields['color'] = array_merge(
                apply_filters('rtwpvs_get_taxonomy_meta_color', [
                    [
                        'label' => esc_html__('Color', 'woo-product-variation-swatches'),
                        'desc' => esc_html__('Choose a color', 'woo-product-variation-swatches'),
                        'id' => 'product_attribute_color',
                        'type' => 'color'
                    ],
                ]), $fields
            );
            $meta_added_for = apply_filters('rtwpvs_product_taxonomy_meta_for', array_keys($fields));

            $attribute_types = helper::getAttributeTypes();

            // get all attribute-taxonomies
            $attribute_taxonomies = wc_get_attribute_taxonomies();
            if ($attribute_taxonomies) {
                foreach ($attribute_taxonomies as $tax) {
                    $product_attr = wc_attribute_taxonomy_name($tax->attribute_name);
                    $product_attr_type = $tax->attribute_type;
                    if (in_array($product_attr_type, $meta_added_for)) {
                        // secure taxonomy
                        $taxonomy = wc_attribute_taxonomy_name($tax->attribute_name);

                        // set our own fields
                        $ourFields = $attribute_types[$product_attr_type]['fields'];

                        // data from own plugin
                        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
                        foreach ($terms as $term) {
                            $_POST['lws1'] = get_term_meta($term->term_id, $fields[$product_attr_type][0]['id'], true);
                            $_POST['lws2'] = get_term_meta($term->term_id, $fields[$product_attr_type][1]['id'], true) == 'yes' ? 1 : 0;
                            $_POST['lws3'] = get_term_meta($term->term_id, $fields[$product_attr_type][2]['id'], true);
                            $obj = new Attribute($tax, $ourFields);
                            $obj->save($term->term_id, '', $taxonomy);
                        }
                    }
                }
            }
        }
    }

    /**
     * Resets all settings of this plugin.
     *
     * @param array $deleteData
     * @return void
     * @noinspection PhpUnused
     */
    public function resetPlugin( $deleteData = [] ): void
    {
        (new installer)->removeAllData( $deleteData );
        installer::initializePlugin();
    }
}