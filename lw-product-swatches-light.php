<?php
/**
 * Plugin Name:       Product Swatches for WooCommerce Light
 * Description:       Provides product swatches for WooCommerce.
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Version:           @@VersionNumber@@
 * Author:            laOlaWeb
 * Author URI:		  https://laolaweb.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       lw-product-swatches
 */

// Exit if accessed directly.
use LW_Swatches\helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const LW_SWATCHES_PLUGIN = __FILE__;

// embed necessary files
require_once 'inc/autoload.php';
require_once 'inc/constants.php';
require_once 'inc/woocommerce.php';
require_once 'inc/functions.php';
if( is_admin() ) {
    require_once 'inc/admin.php';
    require_once 'inc/transients.php';
}

/**
 * On plugin activation.
 */
function lw_swatches_on_activation() {
    $error = false;

    // check if WooCommerce is installed
    if( !lw_swatches_is_woocommerce_activated() ) {
        set_transient( 'lw_swatches_activation_error_woocommerce', true );
        $error = true;
    }

    if( false === $error ) {
        // add scheduler for automatic swatches generation, if it does not exist already
        if (!wp_next_scheduled('lw_swatches_run_tasks')) {
            wp_schedule_event(time(), 'hourly', 'lw_swatches_run_tasks');
        }

        // add daily schedule to add a task to update all swatches during next scheduled run
        if( !wp_next_scheduled('lw_swatches_add_regeneration_tasks')) {
            wp_schedule_event(time(), get_option('wc_lw_product_swatches_regeneration_interval', 'daily'), 'lw_swatches_add_regeneration_tasks');
        }

        // set empty task list if not set
        if( !get_option('lw_swatches_tasks', false) ) {
            update_option('lw_swatches_tasks', []);
        }
    }
}
register_activation_hook( LW_SWATCHES_PLUGIN, 'lw_swatches_on_activation' );

/**
 * On plugin deactivation.
 *
 * @return void
 */
function lw_swatches_on_deactivation() {
    // remove schedules
    wp_clear_scheduled_hook('lw_swatches_run_tasks' );
    wp_clear_scheduled_hook('lw_swatches_add_regeneration_tasks');
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
function lw_swatches_on_update( $upgrader_object, $options ) {
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
function lw_swatches_add_styles_and_js_frontend()
{
    wp_enqueue_style(
        'lw-swatches-styles',
        plugin_dir_url(LW_SWATCHES_PLUGIN) . '/css/styles.css',
        [],
        filemtime(plugin_dir_path(LW_SWATCHES_PLUGIN) . '/css/styles.css')
    );
    wp_enqueue_script( 'lw-swatches-script',
        plugins_url( '/js/frontend.js' , LW_SWATCHES_PLUGIN ),
        ['jquery'],
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
function lw_swatches_init() {
    load_plugin_textdomain( 'lw-product-swatches', false, dirname( plugin_basename( LW_SWATCHES_PLUGIN ) ) . '/languages' );
}
add_action( 'init', 'lw_swatches_init', -1 );

/**
 * Checks for task to run, e.g. to update swatches for a single attribute.
 *
 * @return void
 * @noinspection PhpUnused
 */
function lw_swatches_run_tasks_from_list() {
    $taskList = get_option('lw_swatches_tasks', []);

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

/**
 * Nur für Entwicklung um Cronjob per http://v1.woocommercetest.de/shop/?the_cron_test=1 manuell auszuführen.
 *
 * TODO entfernen
 */
add_action( 'init', function() {
    if ( ! isset( $_GET['the_cron_test'] ) ) {
        return;
    }
    error_reporting( 1 );
    do_action( 'lw_swatches_run_tasks' );
    die();
} );

/**
 * Add task to scheduler to update all swatches automatically
 *
 * @return void
 * @noinspection PhpUnused
 */
function lw_swatches_add_regeneration_tasks() {
    helper::addTaskForScheduler(['\LW_Swatches\helper::updateSwatchesOnProducts']);
}
add_action( 'lw_swatches_add_regeneration_tasks', 'lw_swatches_add_regeneration_tasks');