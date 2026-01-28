<?php
/**
 * Output of the single not linked item in a swatch list.
 *
 * @version 2.1.0
 *
 * @package product-swatches-light
 */

// prevent direct access.
defined( 'ABSPATH' ) || exit;

echo '<li><span class="' . esc_attr( $class ) . '" title="' . esc_attr( $title ) . '" style="' . esc_attr( $css ) . '" data-image="' . esc_attr( $image ) . '" data-image-srcset="' . esc_attr( $srcset ) . '" data-sale="' . esc_attr( $sale ) . '"></span></li>';
