<?php

/**
 * Output of single linked swatch item in list.
 */

echo '<li><span class="'.esc_attr($class). '" title="' . sanitize_text_field($title) . '" style="'.esc_attr($css).'" data-image="' . esc_attr($image) . '" data-image-srcset="' . esc_attr($srcset) . '" data-sale="' . esc_attr($sale) . '"></span></li>';