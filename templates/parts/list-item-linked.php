<?php

/**
 * Output of single linked swatch item in list.
 */

echo '<li><a href="'.esc_url($link).'" class="'.esc_attr($class).'" title="'.sanitize_text_field($title).'" style="'.esc_attr($css).'" data-value="'.esc_attr($slug).'" data-image="' . esc_attr($image) . '" data-image-srcset="' . esc_attr($srcset) . '" data-sale="' . esc_attr($sale) . '"></a></li>';