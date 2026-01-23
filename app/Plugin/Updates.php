<?php
/**
 * File to handle updates of this plugin.
 *
 * @package product-swatches-light
 */

namespace ProductSwatchesLight\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ProductSwatchesLight\Swatches\Products;

/**
 * Object which holds all version-specific updates.
 */
class Updates {
	/**
	 * Wrapper to run all version-specific updates, which are in this class.
	 *
	 * ADD HERE ANY NEW version-update-function.
	 *
	 * @return void
	 */
	public static function run_all_updates(): void {
		// add task to update swatches.
		Products::get_instance()->update_swatches_on_products();

		// delete options from 1.x.
		delete_option( 'lw_swatches_tasks' );

		// remove the old schedule.
		if ( ! wp_next_scheduled( 'lw_swatches_run_tasks' ) ) {
			wp_clear_scheduled_hook( 'lw_swatches_run_tasks' );
		}

		// enable the new schedules, if not set.
		if ( ! get_option( 'productSwatchesEnableRegenerationSchedule' ) ) {
			add_option( 'productSwatchesEnableRegenerationSchedule', 1, '', true );
		}

		// set the interval, if not set.
		if ( ! get_option( 'productSwatchesRegenerationScheduleInterval' ) ) {
			add_option( 'productSwatchesRegenerationScheduleInterval', 'daily', '', true );
		}

		// create our schedules.
		Schedules::get_instance()->create_schedules();
	}
}
