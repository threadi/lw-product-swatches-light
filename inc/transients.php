<?php
/**
 * File for usage of transient in this plugin.
 *
 * @package product-swatches-light
 */

/**
 * Show transient-based hints in wp-admin.
 *
 * @return void
 * @noinspection PhpUnused
 */
function lw_swatches_admin_notices(): void {
	foreach ( LW_SWATCHES_TRANSIENTS as $transient ) {
		if ( get_transient( $transient ) ) {
			// get transient data.
			$transient_data = get_transient( $transient );

			// set classes.
			$class = 'updated';
			if ( 'error' === $transient_data['state'] ) {
				$class = 'notice notice-error';
			}
			?>
			<div class="lw-swatches-transient <?php echo esc_attr( $class ); ?>">
				<p>
					<?php
						echo wp_kses_post( $transient_data['message'] );
					?>
				</p>
			</div>
			<?php

			// remove the transient.
			delete_transient( $transient );

			// disable plugin on request.
			if ( ! empty( $transient_data['disable_plugin'] ) ) {
				deactivate_plugins( plugin_basename( LW_SWATCHES_PLUGIN ) );
			}
		}
	}
}
add_action( 'admin_notices', 'lw_swatches_admin_notices' );
