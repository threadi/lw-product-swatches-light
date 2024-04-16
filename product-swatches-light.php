<?php
/**
 * Plugin Name:       Product Swatches Light
 * Description:       Provides product swatches for WooCommerce.
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Version:           @@VersionNumber@@
 * Author:            laOlaWeb
 * Author URI:		  https://laolaweb.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       product-swatches-light
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use LW_Swatches\helper;

const LW_SWATCHES_PLUGIN = __FILE__;

// embed necessary files
require_once 'inc/autoload.php';
require_once 'inc/constants.php';
require_once 'inc/woocommerce.php';
if( is_admin() ) {
    require_once 'inc/admin.php';
    require_once 'inc/transients.php';
}

/**
 * On plugin activation.
 */
register_activation_hook( LW_SWATCHES_PLUGIN, 'LW_Swatches\installer::initializePlugin' );

/**
 * On plugin deactivation.
 *
 * @return void
 */
function lw_swatches_on_deactivation(): void
{
    // remove schedules
    wp_clear_scheduled_hook('lw_swatches_run_tasks' );
}
register_deactivation_hook( LW_SWATCHES_PLUGIN, 'lw_swatches_on_deactivation' );

/**
 * Add task to update all swatches after plugin-update.
 *
 * @param $upgrader_object
 * @param $options
 * @return void
 * @noinspection PhpUnused
 * @noinspection PhpUnusedParameterInspection
 */
function lw_swatches_on_update( $upgrader_object, $options ): void
{
    if ($options['action'] == 'update' && $options['type'] == 'plugin' ) {
        if( !empty($options['plugins']) ) {
            foreach ($options['plugins'] as $each_plugin) {
                if ($each_plugin == LW_SWATCHES_PLUGIN) {
                    helper::addTaskForScheduler(['\LW_Swatches\helper::updateSwatchesOnProducts']);
                }
            }
        }
    }
}
add_action( 'upgrader_process_complete', 'lw_swatches_on_update', 10, 2);

/**
 * Register WP Cli if WooCommerce is present.
 *
 * @since  1.0.0
 * @author Thomas Zwirner
 * @noinspection PhpUnused
 */
function lw_swatches_cli_register_commands() {
    if( function_exists("wc_get_product") ) {
        WP_CLI::add_command('lw-product-swatches', 'LW_Swatches\cli');
    }
}
add_action( 'cli_init', 'lw_swatches_cli_register_commands' );

/**
 * Add own CSS and JS for frontend.
 *
 * @return void
 * @noinspection PhpUnused
 */
function lw_swatches_add_styles_and_js_frontend(): void {
    wp_enqueue_style(
        'lw-swatches-styles',
        plugin_dir_url(LW_SWATCHES_PLUGIN) . '/css/styles.css',
        array(),
        filemtime(plugin_dir_path(LW_SWATCHES_PLUGIN) . '/css/styles.css')
    );
    wp_enqueue_script( 'lw-swatches-script',
        plugins_url( '/js/frontend.js' , LW_SWATCHES_PLUGIN ),
        array( 'jquery' ),
        filemtime(plugin_dir_path(LW_SWATCHES_PLUGIN) . '/js/frontend.js'),
        true
    );
}
add_action('wp_enqueue_scripts', 'lw_swatches_add_styles_and_js_frontend', PHP_INT_MAX);

/**
 * General initialization.
 *
 * @return void
 * @noinspection PhpUnused
 */
function lw_swatches_init(): void {
    load_plugin_textdomain( 'product-swatches-light', false, dirname( plugin_basename( LW_SWATCHES_PLUGIN ) ) . '/languages' );
}
add_action( 'init', 'lw_swatches_init', -1 );

/**
 * Checks for task to run, e.g. to update swatches for a single attribute.
 *
 * @return void
 * @noinspection PhpUnused
 */
function lw_swatches_run_tasks_from_list(): void {
    $taskList = get_option( 'lw_swatches_tasks', array() );

    // loop through the tasks
    foreach( $taskList as $i => $task ) {
        // check if first entry is a callable
        if( is_callable($task[0]) ) {
            // get the parameter as array
            $params = $task;
            unset($params[0]);

            // call the function
            call_user_func_array($task[0], $params);

            // remove the task from list
            unset($taskList[$i]);
        }
    }
    update_option('lw_swatches_tasks', $taskList);
}
add_action('lw_swatches_run_tasks', 'lw_swatches_run_tasks_from_list');
