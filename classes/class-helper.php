<?php

namespace LW_Swatches;

use WC_Product_Attribute;
use WP_Query;

trait helper {

    /**
     * Updates the swatches on all products.
     *
     * @return void
     */
    public static function updateSwatchesOnProducts()
    {
        // do not import if it is already running in another process
        if( get_option(LW_SWATCHES_UPDATE_RUNNING, 0) == 1 ) {
            return;
        }

        // mark import as running
        update_option(LW_SWATCHES_UPDATE_RUNNING, 1);

        // get the products
        $query = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => 'any'
        ];
        $results = new WP_Query($query);
        $countProducts = count($results->posts);

        // set counter for progressbar in backend
        update_option(LW_SWATCHES_OPTION_MAX, $countProducts);
        update_option(LW_SWATCHES_OPTION_COUNT, 0);
        $count = 0;

        $progress = self::isCLI() ? \WP_CLI\Utils\make_progress_bar( 'Updating products', $countProducts ) : false;

        // loop through the products
        foreach( $results->posts as $productId ) {
            // Produkt initialisieren
            $product = wc_get_product($productId);
            Product::update($product);
            // show progress
            update_option(LW_SWATCHES_OPTION_COUNT, ++$count);
            !$progress ?: $progress->tick();
        }
        // show finished progress
        !$progress ?: $progress->finish();

        // output success-message
        !$progress ?: \WP_CLI::success($countProducts." products were updated.");

        // remove running flag
        delete_option(LW_SWATCHES_UPDATE_RUNNING);
    }

    /**
     * Update swatches on selected attribute.
     *
     * @param $type - the type to search for, e.g. "attribute"
     * @param $name - the name of the type
     * @return void
     */
    public static function updateSwatchesOnProductsByType( $type, $name ) {
        if( $type == "attribute" && !empty($name) ) {
            // update all swatch-caches on products using this attribute
            $query = [
                'post_type' => 'product',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'post_status' => 'any',
                'tax_query' => [
                    [
                        'taxonomy' => $name,
                        'field' => 'id',
                        'terms' => get_terms( [ 'taxonomy' => $name, 'fields' => 'ids'  ] )
                    ]
                ]
            ];
            $results = new WP_Query($query);
            $countProducts = $results->post_count;

            // create progress bar on cli
            $progress = self::isCLI() ? \WP_CLI\Utils\make_progress_bar( 'Updating products', $countProducts ) : false;
            foreach ($results->posts as $productId) {
                // get product
                $product = wc_get_product($productId);

                // update product
                Product::update($product);

                // show progress
                !$progress ?: $progress->tick();
            }

            // show finished progress
            !$progress ?: $progress->finish();
        }
    }

    /**
     * Add given callback to task list for scheduler.
     *
     * @param array $function
     * @return void
     */
    public static function addTaskForScheduler(array $function)
    {
        if( !empty($function) ) {
            $md5 = md5(serialize($function));
            $task_list = array_merge(get_option('lw_swatches_tasks', []), [$md5 => $function]);
            update_option('lw_swatches_tasks', $task_list);
        }
    }

    /**
     * Remove the Wordpress-homeUrl from given string.
     *
     * @param $string
     * @return string
     */
    public static function removeOwnHomeFromString($string): string
    {
        return str_replace(get_option('home'), '', $string);
    }

    /**
     * Get variant-image as data-attribute
     *
     * @param $images
     * @param $imagesSets
     * @param $slug
     * @return array
     */
    public function getVariantThumbAsData( $images, $imagesSets, $slug ): array
    {
        if( empty($images[$slug]) ) {
            return [];
        }
        return [
            'image' => $images[$slug],
            'srcset' => $imagesSets[$slug]
        ];
    }

    /**
     * Get variant-image as data-attribute from array
     *
     * @param $product
     * @param $attribute
     * @param $slug
     * @return array
     */
    public function getVariantThumbAsDataFromArray( $product, $attribute, $slug ): array
    {
        $variations = $product->get_available_variations();
        $image = '';
        $imageSrcset = '';
        foreach ( $variations as $variation ) {
            if( $variation['attributes']['attribute_' . $attribute] == $slug ) {
                $image = $variation['image']['src'];
                $imageSrcset = $variation['image']['srcset'];
                break;
            }
        }
        if( !empty($image) && !empty($imageSrcset) ) {
            return $this->getVariantThumbAsData([0 => $image], [0 => $imageSrcset], 0);
        }
        return [];
    }

    /**
     * Get variant by given attribute and slug.
     *
     * @param $product
     * @param $attribute
     * @param $slug
     * @return false|\WC_Product
     */
    public static function getVariantFromArray( $product, $attribute, $slug )
    {
        $variations = $product->get_available_variations();
        $variant = false;
        foreach ( $variations as $variation ) {
            if( $variation['attributes']['attribute_' . $attribute] == $slug ) {
                $variant = wc_get_product($variation['variation_id']);
                break;
            }
        }
        return $variant;
    }

    /**
     * Delete all swatches on all products.
     *
     * @return void
     */
    public static function deleteAllSwatchesOnProducts() {
        // get the products where a product swatch is set
        $query = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => 'any',
            'meta_query' => [
                [
                    'key' => LW_SWATCH_CACHEKEY,
                    'compare' => 'EXISTS'
                ]
            ]
        ];
        $results = new WP_Query($query);
        $countProducts = count($results->posts);
        $progress = self::isCLI() ? \WP_CLI\Utils\make_progress_bar( 'Deleting product-swatches', $countProducts ) : false;

        // loop through the products
        foreach( $results->posts as $productId ) {
            Product::delete($productId);
            // show progress
            !$progress ?: $progress->tick();
        }
        // show finished progress
        !$progress ?: $progress->finish();

        // output success-message
        !$progress ?: \WP_CLI::success($countProducts." product-swatches were deleted.");
    }

    /**
     * Return available attribute types incl. their language-specific labels.
     *
     * @return array[]
     */
    public static function getAttributeTypes(): array
    {
        $attribute_types = apply_filters('lw_swatches_types', LW_ATTRIBUTE_TYPES);
        $attribute_types_label = [
            'color' => [
                'label' => __('Color', 'lw-product-swatches'),
                'fields' => [
                    'color' => [
                        'label' => __('Color', 'lw-product-swatches'),
                        'desc' => __('Choose a color.', 'lw-product-swatches')
                    ]
                ]
            ]
        ];
        return array_merge_recursive($attribute_types, apply_filters('lw_swatches_types_label', $attribute_types_label));
    }

    /**
     * Prüfe, ob der Import per CLI aufgerufen wird.
     * Z.B. um einen Fortschrittsbalken anzuzeigen.
     *
     * @return bool
     */
    public static function isCLI(): bool
    {
        return defined( 'WP_CLI' ) && \WP_CLI;
    }

    /**
     * get attribute name to get the taxonomy-object to get the attribute-type
     * @source WooCommerce class-wc-ajax.php:586
     *
     * @param $taxonomy
     * @return string
     */
    public static function getAttributeTypeByTaxonomyName( $taxonomy ): string
    {
        $attribute = new WC_Product_Attribute();
        $attribute->set_id( wc_attribute_taxonomy_id_by_name( sanitize_text_field( $taxonomy ) ) );
        $attribute->set_name( sanitize_text_field( $taxonomy ) );
        $attribute->set_visible( apply_filters( 'woocommerce_attribute_default_visibility', 1 ) );
        $attribute->set_variation( apply_filters( 'woocommerce_attribute_default_is_variation', 0 ) );
        $attribute_taxonomy = $attribute->get_taxonomy_object();
        if( null !== $attribute_taxonomy ) {
            return $attribute_taxonomy->attribute_type;
        }
        return '';
    }

    /**
     * get attribute id to get the taxonomy-object to get the attribute-type
     * @source WooCommerce class-wc-ajax.php:586
     *
     * @param $taxonomy
     * @return int
     */
    public static function getAttributeTypeIdByTaxonomyName( $taxonomy ): int
    {
        $attribute = new WC_Product_Attribute();
        $attribute->set_id( wc_attribute_taxonomy_id_by_name( sanitize_text_field( $taxonomy ) ) );
        $attribute->set_name( sanitize_text_field( $taxonomy ) );
        $attribute->set_visible( apply_filters( 'woocommerce_attribute_default_visibility', 1 ) );
        $attribute->set_variation( apply_filters( 'woocommerce_attribute_default_is_variation', 0 ) );
        $attribute_taxonomy = $attribute->get_taxonomy_object();
        if( null !== $attribute_taxonomy ) {
            return $attribute_taxonomy->attribute_id;
        }
        return 0;
    }

    /**
     * Load a template if it exists.
     * Also load the requested file if is located in the /wp-content/themes/xy/lw-product-swatches/ directory.
     *
     * @param $template
     * @return mixed|string
     */
    public static function getTemplate( $template )
    {
        if( is_embed() ) {
            return $template;
        }

        $themeTemplate = locate_template(trailingslashit(basename( dirname( LW_SWATCHES_PLUGIN ) )).$template);
        if( $themeTemplate ) {
            return $themeTemplate;
        }
        return plugin_dir_path(apply_filters('lw_product_swatches_set_template_directory', LW_SWATCHES_PLUGIN)).'templates/'.$template;
    }

    /**
     * Get the resulting HTML-list.
     *
     * @param $html
     * @param $typenames
     * @param $typename
     * @param $taxonomy
     * @return false|string
     * @noinspection PhpUnusedParameterInspection
     * @noinspection SpellCheckingInspection
     */
    public static function getHTMList( $html, $typenames, $typename, $taxonomy ) {
        if( empty($html) ) {
            return '';
        }
        ob_start();
        /**
         * Close surrounding link if it has been run.
         */
        woocommerce_template_loop_product_link_close();
        remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5);

        /**
         * Output starting and ending list template surrounding the given html-code.
         */
        include helper::getTemplate('parts/list-start.php');
        echo $html;
        include helper::getTemplate('parts/list-end.php');
        return ob_get_clean();
    }

    /**
     * Return allowed colors.
     *
     * @return array
     */
    public static function getAllowedColors(): array
    {
        return [
            'black' => __('black', 'lw-product-swatches'),
            'blue' => __('blue', 'lw-product-swatches'),
            'brown' => __('brown', 'lw-product-swatches'),
            'green' => __('green', 'lw-product-swatches'),
            'red' => __('red', 'lw-product-swatches'),
            'white' => __('white', 'lw-product-swatches'),
            'yellow' => __('yellow', 'lw-product-swatches')
        ];
    }
}