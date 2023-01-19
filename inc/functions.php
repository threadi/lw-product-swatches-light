<?php

/**
 * Check if WooCommerce is active and running.
 *
 * @return bool     true if WooCommerce is active and running
 */
function lw_swatches_is_woocommerce_activated(): bool
{
    if ( class_exists( 'woocommerce' ) ) { return true; } else { return false; }
}
