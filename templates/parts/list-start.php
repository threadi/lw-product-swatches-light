<?php

/**
 * Output of starting swatch-list.
 */

echo '<ul class="lw_product_swatches lw_product_swatches_' . esc_attr( $typenames ) . '" data-type="' . esc_attr( $typename ) . '" data-attribute="' . esc_attr( $taxonomy ) . '" data-changed-by-gallery="' . esc_attr( $changed_by_gallery ) . '">';
