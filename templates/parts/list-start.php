<?php
/**
 * Output of starting swatch-list.
 *
 * @version 2.0.0
 *
 * @package product-swatches-light
 */

echo '<ul class="lw_product_swatches lw_product_swatches_' . esc_attr( $typenames ) . '" data-type="' . esc_attr( $typename ) . '" data-attribute="' . esc_attr( $taxonomy ) . '" data-changed-by-gallery="' . esc_attr( $changed_by_gallery ) . '">';
