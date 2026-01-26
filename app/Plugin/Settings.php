<?php
/**
 * File to handle plugin-settings.
 *
 * @package product-swatches-light
 */

namespace ProductSwatchesLight\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to handle settings.
 */
class Settings {
	/**
	 * Instance of this object.
	 *
	 * @var ?Settings
	 */
	private static ?Settings $instance = null;

	/**
	 * Constructor for Settings-Handler.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return the instance of this Singleton object.
	 */
	public static function get_instance(): Settings {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the settings.
	 *
	 * @return void
	 */
	public function init(): void {
		// set all settings for this plugin.
		add_action( 'init', array( $this, 'add_the_settings' ) );
	}

	/**
	 * Add the settings.
	 *
	 * @return void
	 */
	public function add_the_settings(): void {
		/**
		 * Configure the basic settings object.
		 */
		$settings_obj  = \ProductSwatchesLight\Dependencies\easySettingsForWordPress\Settings::get_instance();
		$settings_obj->set_slug( 'product-swatches-light' );
		$settings_obj->set_plugin_slug( LW_SWATCHES_PLUGIN );
		$settings_obj->set_menu_title( __( 'Settings', 'product-swatches-light' ) );
		$settings_obj->set_title( __( '', '' ) );
		$settings_obj->set_menu_slug( 'psl_settings' );
		$settings_obj->set_url( Helper::get_plugin_url() . '/app/Dependencies/easySettingsForWordPress/' );
		$settings_obj->set_path( Helper::get_plugin_path() . '/app/Dependencies/easySettingsForWordPress/' );

		// initialize this setting object if setup has been completed or if this is a REST API request.
		/*if ( Helper::is_rest_request() || Setup::get_instance()->is_completed() ) {
			$settings_obj->init();
		}*/

		// create a hidden page for hidden settings.
		$hidden_page = $settings_obj->add_page( 'hidden_page' );

		// create a hidden tab on this page.
		$hidden_tab = $hidden_page->add_tab( 'hidden_tab', 10 );

		// the hidden section for any not visible settings.
		$hidden = $hidden_tab->add_section( 'hidden_section', 20 );
		$hidden->set_setting( $settings_obj );

		// add setting.
		$setting = $settings_obj->add_setting( 'psl_product_attribute' );
		$setting->set_section( $hidden );
		$setting->set_type( 'string' );
		$setting->set_default( '' );
		$setting->prevent_export( true );
		$setting->set_show_in_rest( true );

		// add setting.
		$setting = $settings_obj->add_setting( 'psl_product_attribute_terms' );
		$setting->set_section( $hidden );
		$setting->set_type( 'array' );
		$setting->set_default( array() );
		$setting->prevent_export( true );
		$setting->set_show_in_rest( true );

		// initialize the settings.
		$settings_obj->init();
	}
}
