<?php
/**
 * Tasks to run during plugin uninstallation.
 *
 * @package product-swatches-light
 */

use LW_Swatches\installer;

// if uninstall.php is not called by WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// do nothing if PHP-version is not 8.0 or newer.
if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
	return;
}

// embed necessary files.
require_once 'inc/autoload.php';
require_once 'inc/constants.php';

( new installer() )->removeAllData( array( get_option( 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_delete_on_uninstall', 0 ) ) );
