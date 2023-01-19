<?php

/**
 * Show hints in Admin.
 *
 * @return void
 * @noinspection PhpUnused
 */
function lw_swatches_admin_notices() {

    // Show hint if WooCommerce is missing
    if( get_transient( 'lw_swatches_activation_error_woocommerce' ) ){
        ?>
        <div class="updated error">
            <p><strong><?php echo esc_html__('Plugin not activated!', 'lw-product-swatches'); ?></strong> <?php echo esc_html__('Please activate WooCommerce first.', 'lw-product-swatches'); ?></p>
        </div>
        <?php
        delete_transient( 'lw_swatches_activation_error_woocommerce' );
        deactivate_plugins( plugin_basename( LW_SWATCHES_PLUGIN ) );
    }

    // Show hint if not all required fields are filled
    if( get_transient( 'lw_swatches_required_field_missing' ) ){
        ?>
        <div class="updated error">
            <p><strong><?php echo esc_html__('At least one required field was not filled!', 'lw-product-swatches'); ?></strong> <?php echo esc_html__('Please fill out the form completely.', 'lw-product-swatches'); ?></p>
        </div>
        <?php
        delete_transient( 'lw_swatches_required_field_missing' );
    }

    // Show hint if not all required fields are filled
    if( get_transient( 'lw_swatches_resetted' ) ){
        ?>
        <div class="updated success">
            <p><strong><?php echo esc_html__('The swatches of the product have been updated.', 'lw-product-swatches'); ?></strong></p>
        </div>
        <?php
        delete_transient( 'lw_swatches_resetted' );
    }

    // Show hint if bulk action has been done
    if( get_transient( 'lw_swatches_bulk_done' ) ){
        ?>
        <div class="updated success">
            <p><strong><?php echo esc_html__('The swatches of the selected products have been updated.', 'lw-product-swatches'); ?></strong></p>
        </div>
        <?php
        delete_transient( 'lw_swatches_bulk_done' );
    }
}
add_action( 'admin_notices', 'lw_swatches_admin_notices' );