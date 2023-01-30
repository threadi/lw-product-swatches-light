<?php

/**
 * Tasks to run during plugin uninstallation.
 */

// get the constants
use LW_Swatches\installer;

require_once 'inc/autoload.php';
require_once 'inc/constants.php';
require_once 'inc/functions.php';

(new installer)->removeAllData();