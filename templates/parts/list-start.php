<?php
/**
 * Output of starting swatch-list.
 *
 * @version 1.1.0
 * @package product-swatches-light
 */

echo '<ul class="lw_product_swatches lw_product_swatches_' . esc_attr( $type_names ) . '" data-type="' . esc_attr( $typename ) . '" data-attribute="' . esc_attr( $taxonomy ) . '" data-changed-by-gallery="' . esc_attr( $changed_by_gallery ) . '">';
