<?php
/**
 * File to handle the regeneration of swatches.
 *
 * @package product-swatches-light
 */

namespace ProductSwatchesLight\Plugin\Schedules;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ProductSwatchesLight\Plugin\Schedules_Base;
use ProductSwatchesLight\Swatches\Products;

/**
 * Object for this schedule.
 */
class RegenerateSwatches extends Schedules_Base {

	/**
	 * Name of this event.
	 *
	 * @var string
	 */
	protected string $name = 'product_swatches_schedule_regeneration';

	/**
	 * Name of the option used to enable this event.
	 *
	 * @var string
	 */
	protected string $option_name = 'productSwatchesEnableRegenerationSchedule';

	/**
	 * Name of the option where the interval is set.
	 *
	 * @var string
	 */
	protected string $interval_option_name = 'productSwatchesRegenerationScheduleInterval';

	/**
	 * Initialize this schedule.
	 */
	public function __construct() {
		// get interval from settings.
		$this->interval = get_option( $this->get_interval_option_name() );
	}

	/**
	 * Run this schedule.
	 *
	 * @return void
	 */
	public function run(): void {
		if ( $this->is_enabled() ) {
			Products::get_instance()->update_swatches_on_products();
		}
	}
}
