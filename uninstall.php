<?php

/**
 * Tasks to run during plugin uninstallation.
 */

// get the constants
use LW_Swatches\installer;

require_once 'inc/autoload.php';
require_once 'inc/constants.php';
require_once 'inc/functions.php';

(new installer)->removeAllData([get_option('wc_'.LW_SWATCH_WC_SETTING_NAME.'_delete_on_uninstall', 0)]);