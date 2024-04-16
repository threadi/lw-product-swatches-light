<?php
/**
 * File for constants used by this plugin.
 *
 * @package product-swatches-light
 */

/**
 * Cache-Key for post-meta on products.
 */
const LW_SWATCH_CACHEKEY = 'lw_product_swatches';

/**
 * Name for WooCommerce settings.
 */
const LW_SWATCH_WC_SETTING_NAME = 'lw_product_swatches';

/**
 * Definition of attribute-types with its settings.
 * Each attribute provides one or more settings to define the output.
 * E.g. 'color' might be single or multiple-color.
 */
const LW_ATTRIBUTE_TYPES = array(
	'color' => array(
		'fields' => array(
			'color' => array(
				'id'          => '1', // unique ID.
				'name'        => 'color', // internal name.
				'value'       => '', // preset - should be empty.
				'size'        => '7', // optional size of the inputfield.
				'required'    => 1, // set field as required (1) or not (0).
				'placeholder' => '#000000', // optional placeholder for field.
				'dependency'  => array(), // optional dependency for this field.
				'type'        => 'colorselect', // field-type in backend, possible values: color, image, checkbox, angle.
			),
		),
	),
);

/**
 * Define names for progressbar during import.
 */
const LW_SWATCHES_OPTION_COUNT   = 'lsImportCount';
const LW_SWATCHES_OPTION_MAX     = 'lsImportMax';
const LW_SWATCHES_UPDATE_RUNNING = 'lsRunning';

/**
 * Define our transients.
 */
const LW_SWATCHES_TRANSIENTS = array(
	'lwSwatchesMessage',
);
