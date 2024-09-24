<?php
/**
 * Output of single linked swatch item in list.
 *
 * @version 2.0.0
 *
 * @package product-swatches-light
 */

echo '<li><span class="' . esc_attr( $class ) . '" title="' . esc_attr( $title ) . '" style="' . esc_attr( $css ) . '" data-image="' . esc_attr( $image ) . '" data-image-srcset="' . esc_attr( $srcset ) . '" data-sale="' . esc_attr( $sale ) . '"></span></li>';
