<?php
/**
 * File to handle setup for this plugin.
 *
 * @package product-swatches-light
 */

namespace ProductSwatchesLight\Plugin;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ProductSwatchesLight\Dependencies\easyTransientsForWordPress\Transient;
use ProductSwatchesLight\Dependencies\easyTransientsForWordPress\Transients;
use ProductSwatchesLight\Swatches\AttributeType\Color;
use ProductSwatchesLight\Swatches\Products;

/**
 * Initialize the setup object.
 */
class Setup {
	/**
	 * Instance of this object.
	 *
	 * @var ?Setup
	 */
	private static ?Setup $instance = null;

	/**
	 * Define setup as an array with steps.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private array $setup = array();

	/**
	 * Constructor for this handler.
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
	public static function get_instance(): Setup {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the setup-object.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'init_setup' ) );
	}

	/**
	 * Initialize the setup.
	 *
	 * @return void
	 */
	public function init_setup(): void {
		// check to show a hint if setup should be run.
		$this->show_hint();

		// only load setup if it is not completed.
		if ( ! $this->is_completed() ) {
			// initialize the setup object.
			$setup_obj = \easySetupForWordPress\Setup::get_instance();
			$setup_obj->init();

			// get the setup-object.
			$setup_obj->set_url( Helper::get_plugin_url() );
			$setup_obj->set_path( Helper::get_plugin_path() );
			$setup_obj->set_texts(
				array(
					'title_error' => __( 'Error', 'product-swatches-light' ),
					'txt_error_1' => __( 'The following error occurred:', 'product-swatches-light' ),
					/* translators: %1$s will be replaced with the URL of the plugin-forum on wp.org */
					'txt_error_2' => sprintf( __( '<strong>If reason is unclear</strong> please contact our <a href="%1$s" target="_blank">support-forum (opens new window)</a> with as much detail as possible.', 'product-swatches-light' ), esc_url( Helper::get_plugin_support_url() ) ),
				)
			);
			$setup_obj->set_display_hook( '_page_productSwatchesLight' );

			// set configuration for setup.
			$setup_obj->set_config( $this->get_config() );

			// add hooks to enable the setup of this plugin.
			add_action( 'admin_menu', array( $this, 'add_setup_menu' ) );

			// only load setup if it is not completed.
			add_action( 'esfw_set_completed', array( $this, 'set_completed' ) );
			add_action( 'esfw_process', array( $this, 'run_process' ) );
			add_action( 'esfw_process', array( $this, 'show_process_end' ), PHP_INT_MAX );
		}
	}

	/**
	 * Return whether setup is completed.
	 *
	 * @return bool
	 */
	public function is_completed(): bool {
		$completed = \easySetupForWordPress\Setup::get_instance()->is_completed( $this->get_setup_name() );
		/**
		 * Filter the setup complete marker.
		 *
		 * @since 3.0.0 Available since 3.0.0.
		 * @param bool $completed True if setup has been completed.
		 */
		return apply_filters( 'product_swatches_light_setup_is_completed', $completed );
	}

	/**
	 * Return the setup-URL.
	 *
	 * @return string
	 */
	public function get_setup_link(): string {
		return add_query_arg( array( 'page' => 'productSwatchesLight' ), admin_url() . 'admin.php' );
	}

	/**
	 * Check if setup should be run and show the hint for it.
	 *
	 * @return void
	 */
	public function show_hint(): void {
		// get transients object.
		$transients_obj = Transients::get_instance();

		// check if setup should be run.
		if ( ! $this->is_completed() ) {
			// bail if the hint is already set.
			if ( $transients_obj->get_transient_by_name( 'product_swatches_light_start_setup_hint' )->is_set() ) {
				return;
			}

			// delete all other transients.
			foreach ( $transients_obj->get_transients() as $transient_obj ) {
				// bail if the object is not ours.
				if ( ! $transient_obj instanceof Transient ) { // @phpstan-ignore instanceof.alwaysTrue
					continue;
				}

				// delete it.
				$transient_obj->delete();
			}

			// add a hint to run setup.
			$transient_obj = Transients::get_instance()->add();
			$transient_obj->set_name( 'product_swatches_light_start_setup_hint' );
			$transient_obj->set_message( __( '<strong>You have installed Product Swatches Light - nice, and thank you!</strong> Now run the setup to expand your website with the possibilities of this plugin to promote your WooCommerce products with swatches.', 'product-swatches-light' ) . '<br><br>' . sprintf( '<a href="%1$s" class="button button-primary">' . __( 'Start setup', 'product-swatches-light' ) . '</a>', esc_url( $this->get_setup_link() ) ) );
			$transient_obj->set_type( 'error' );
			$transient_obj->set_dismissible_days( 365 );
			$transient_obj->set_hide_on( array(
				$this->get_setup_link(),
			) );
			$transient_obj->save();
		} else {
			$transients_obj->get_transient_by_name( 'product_swatches_light_start_setup_hint' )->delete();
		}
	}

	/**
	 * Return the configured setup.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_setup(): array {
		$setup = $this->setup;
		if ( empty( $setup ) ) {
			$this->set_config();
			$setup = $this->setup;
		}

		/**
		 * Filter the configured setup for this plugin.
		 *
		 * @since 3.0.0 Available since 3.0.0.
		 *
		 * @param array<int,array<string,mixed>> $setup The setup-configuration.
		 */
		return apply_filters( 'product_swatches_light_setup', $setup );
	}

	/**
	 * Show the setup dialog.
	 *
	 * @return void
	 */
	public function display(): void {
		// create help in case of error during loading of the setup.
		$error_help = '<div class="notice notice-success"><h3>' . wp_kses_post( Helper::get_logo_img() ) . ' ' . esc_html( apply_filters( 'product_swatches_light_transient_title', Helper::get_plugin_name() ) ) . '</h3><p><strong>' . __( 'Setup is loading', 'product-swatches-light' ) . '</strong><br>' . __( 'Please wait while we load the setup.', 'product-swatches-light' ) . '<br>' . __( 'However, you can also skip the setup and configure the plugin manually.', 'product-swatches-light' ) . '</p><p><a href="' . esc_url( \easySetupForWordPress\Setup::get_instance()->get_skip_url( $this->get_setup_name(), Helper::get_settings_url() ) ) . '" class="button button-primary">' . __( 'Skip setup', 'product-swatches-light' ) . '</a></p></div>';

		// add error text.
		\easySetupForWordPress\Setup::get_instance()->set_error_help( $error_help );

		// output.
		echo wp_kses_post( \easySetupForWordPress\Setup::get_instance()->display( $this->get_setup_name() ) );
	}

	/**
	 * Return configuration for setup.
	 *
	 * Here we define which steps and texts are used by wp-easy-setup.
	 *
	 * @return array<string,array<int,mixed>|string>
	 */
	private function get_config(): array {
		// collect configuration for the setup.
		$config = array(
			'name'                  => $this->get_setup_name(),
			'title'                 => __( 'Product Swatches Light', 'product-swatches-light' ) . ' ' . __( 'Setup', 'product-swatches-light' ),
			'steps'                 => $this->get_setup(),
			'back_button_label'     => __( 'Back', 'product-swatches-light' ) . '<span class="dashicons dashicons-undo"></span>',
			'continue_button_label' => __( 'Continue', 'product-swatches-light' ) . '<span class="dashicons dashicons-controls-play"></span>',
			'finish_button_label'   => __( 'Completed', 'product-swatches-light' ) . '<span class="dashicons dashicons-saved"></span>',
			'skip_button_label'     => __( 'Skip', 'product-swatches-light' ) . '<span class="dashicons dashicons-undo"></span>',
			'skip_url'              => \easySetupForWordPress\Setup::get_instance()->get_skip_url( $this->get_setup_name(), Helper::get_settings_url() ),
			'error_label'           => __( 'An error occurred. Received an incorrect response from the server. Please check your permalink settings.', 'product-swatches-light' ),
		);

		/**
		 * Filter the setup configuration.
		 *
		 * @since 3.0.0 Available since 3.0.0.
		 * @param array<string,array<int,mixed>|string> $config List of configuration for the setup.
		 */
		return apply_filters( 'product_swatches_light_setup_config', $config );
	}

	/**
	 * Set the process label.
	 *
	 * @param string $label The label to process.
	 *
	 * @return void
	 */
	public function set_process_label( string $label ): void {
		update_option( 'esfw_step_label', $label );
	}

	/**
	 * Updates the process step.
	 *
	 * @param int $step Steps to add.
	 *
	 * @return void
	 */
	public function update_process_step( int $step = 1 ): void {
		update_option( 'esfw_step', absint( get_option( 'esfw_step' ) + $step ) );
	}

	/**
	 * Sets the setup configuration.
	 *
	 * @return void
	 */
	public function set_config(): void {
		// define setup.
		$this->setup = array(
			1 => array(
				'help'                                     => array(
					'type' => 'Text',
					'text' => '<p>' . __( '<strong>Nice that you want to use Product Swatches Light to optimize the visibility of your products.</strong> We will now guide you through the necessary steps.<br>You can also change the settings mentioned here yourself at any time when editing attributes.', 'product-swatches-light' ) . '</p>',
				),
				'psl_product_attribute'              => array(
					'type'                => 'RadioControl',
					'label'               => __( 'Select the attribute where you want to make a color swatch', 'product-swatches-light' ),
					'help'                => __( 'This should be the attribute where your want to show swatches.', 'product-swatches-light' ),
					'required'            => true,
					'options'             => $this->get_product_attributes(),
				),
			),
			2 => array(
				'help'                                     => array(
					'type' => 'Text',
					'text' => '<p>' . __( '<strong>Now select the desired color for each term of the chosen attribute.</strong>', 'product-swatches-light' ) . '</p>',
				),
				'psl_product_attribute_terms' => array(
					'type'                => 'Table',
					'load_callback' => array( $this, 'load_product_attribute_terms_table' ),
					'labels' => array(
						__( 'Term', 'product-swatches-light' ),
						__( 'Color', 'product-swatches-light' ),
					)
				)
			),
			3 => array(
				'runSetup' => array(
					'type'  => 'ProgressBar',
					'label' => __( 'Setup preparing your swatches', 'product-swatches-light' ),
				),
			),
		);
	}

	/**
	 * Return the list of terms.
	 *
	 * @return array
	 */
	public function load_product_attribute_terms_table(): array {
		// get the terms for the chosen attribute.
		$terms = $this->get_terms_for_attribute();

		// get the possible colors and convert it to a React compatible array.
		$colors = array(
			array(
				'label' => __( 'None', 'product-swatches-light' ),
				'value' => ''
			)
		);
		foreach( Helper::get_colors() as $key => $label ) {
			$colors[] = array(
				'label' => $label,
				'value' => $key
			);
		}

		// create the return list.
		$list = array();
		foreach( $terms as $term ) {
			$list[$term->term_id] = array(
				'label' => $term->name,
				'values' => $colors
			);
		}

		// return the resulting list.
		return $list;
	}

	/**
	 * Update max count.
	 *
	 * @param int $add_to_max_count The value to add.
	 *
	 * @return void
	 */
	public function update_max_step( int $add_to_max_count ): void {
		update_option( 'esfw_max_steps', absint( get_option( 'esfw_max_steps' ) ) + $add_to_max_count );
	}

	/**
	 * Run the process.
	 *
	 * @param string $config_name The name of the setup-configuration.
	 *
	 * @return void
	 */
	public function run_process( string $config_name ): void {
		// bail if this is not our setup.
		if ( $config_name !== $this->get_setup_name() ) {
			return;
		}

		global $wpdb;

		// get the terms for the chosen attribute.
		$terms = $this->get_terms_for_attribute();

		// update the max steps for this process.
		$this->update_max_step( count( $terms ) + 2 ); // +2 for the attribute configuration and the swatches' generation.

		// run it.
		$this->set_process_label( __( 'Configuring product attribute.', 'product-swatches-light' ) );
		$this->update_process_step();

		sleep( 2 );

		// get the chosen attribute name.
		$attribute_name = get_option( 'psl_product_attribute' );

		// get the attribute values.
		$attribute_to_edit = $wpdb->get_row(
			$wpdb->prepare(
				"
				SELECT attribute_id, attribute_type, attribute_label, attribute_name, attribute_orderby, attribute_public
				FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s
				",
				$attribute_name
			)
		);

		// update the attribute.
		$args      = array(
			'name'         => $attribute_to_edit->attribute_label,
			'slug'         => $attribute_to_edit->attribute_name,
			'type'         => Color::_TYPE_NAME,
			'order_by'     => $attribute_to_edit->attribute_orderby,
			'has_archives' => $attribute_to_edit->attribute_public,
		);
		wc_update_attribute( $attribute_to_edit->attribute_id, $args );

		// run it.
		$this->set_process_label( __( 'Configuring attribute terms.', 'product-swatches-light' ) );

		// get the settings.
		$attribute_settings = get_option( 'psl_product_attribute_terms' );

		// check if we have more than 100 terms.
		$no_sleep = count( $terms ) > 100;

		// loop through all terms and configure its settings.
		foreach( $terms as $term ) {
			$this->update_process_step();

			// sleep a bit to show progress if we do not have more than 100 terms.
			if( ! $no_sleep ) {
				sleep( 1 );
			}

			// get the chosen color from setting.
			$color = '';
			foreach( $attribute_settings as $attribute_setting ) {
				// bail if it does not match.
				if( absint( $attribute_setting['entry']) !== absint( $term->term_id ) ) {
					continue;
				}

				// use this color.
				$color = $attribute_setting['value'];
			}

			// bail if no color could be found.
			if( empty( $color ) ) {
				continue;
			}

			// update the term.
			update_term_meta( $term->term_id, Color::_TYPE_NAME, $color );
		}

		// generate swatches.
		$this->set_process_label( __( 'Generating swatches.', 'product-swatches-light' ) );
		$this->update_process_step();
		Products::get_instance()->update_swatches_on_products();

		sleep( 2 );

		// enable to show the swatches, if not set.
		if( empty( get_option( 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_position_in_list' ) ) ) {
			update_option( 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_position_in_list', 'beforeprice' );
		}
	}

	/**
	 * Show process end text.
	 *
	 * @param string $config_name The name of the setup-configuration.
	 *
	 * @return void
	 */
	public function show_process_end( string $config_name ): void {
		// bail if this is not our setup.
		if ( $config_name !== $this->get_setup_name() ) {
			return;
		}

		$completed_text = __( 'Setup has been run. The swatches for your products has been created. Click on "Completed" to view them.', 'product-swatches-light' );
		/**
		 * Filter the text for display if the setup has been run.
		 *
		 * @since 3.0.0 Available since 3.0.0
		 * @param string $completed_text The text to show.
		 * @param string $config_name The name of the setup-configuration used.
		 */
		$this->set_process_label( apply_filters( 'product_swatches_light_setup_process_completed_text', $completed_text, $config_name ) );

		// set steps to max steps.
		$this->update_process_step( $this->get_max_step() );
	}

	/**
	 * Run additional tasks if the setup has been marked as completed.
	 *
	 * @param string $config_name The name of the setup-configuration.
	 *
	 * @return void
	 */
	public function set_completed( string $config_name ): void {
		// bail if this is not our setup.
		if ( $this->get_setup_name() !== $config_name ) {
			return;
		}

		// bail if this is not a request from API.
		if ( ! Helper::is_rest_request() ) {
			return;
		}

		/**
		 * Run additional tasks if the setup is marked as completed.
		 *
		 * @since 3.0.0 Available since 3.0.0.
		 */
		do_action( 'product_swatches_light_setup_completed' );

		// return JSON with forward-URL.
		wp_send_json(
			array(
				'forward' => wc_get_page_permalink( 'shop' ),
			)
		);
	}

	/**
	 * Return the name for the setup configuration.
	 *
	 * @return string
	 */
	public function get_setup_name(): string {
		return 'product-swatches-light';
	}

	/**
	 * Return the actual max steps.
	 *
	 * @return int
	 */
	public function get_max_step(): int {
		return absint( get_option( 'esfw_max_steps' ) );
	}

	/**
	 * Uninstall the setup.
	 *
	 * @return void
	 */
	public function uninstall(): void {
		\easySetupForWordPress\Setup::get_instance()->uninstall( $this->get_setup_name() );
	}

	/**
	 * Add a hidden menu entry to start the setup.
	 *
	 * @return void
	 */
	public function add_setup_menu(): void {
		// add setup entry as submenu, so it will not be visible in the menu.
		add_submenu_page(
			'productSwatchesLightMain',
			__( 'Product Swatches Light', 'product-swatches-light' ) . ' ' . __( 'Setup', 'product-swatches-light' ),
			__( 'Setup', 'product-swatches-light' ),
			'manage_options',
			'productSwatchesLight',
			array( $this, 'display' ),
			1
		);
	}

	/**
	 * Return the list of possible product attributes.
	 *
	 * @return array
	 */
	private function get_product_attributes(): array {
		// bail if no WC is installed.
		if( ! function_exists( 'wc_get_attribute_taxonomies') ) {
			return array();
		}

		// get the list of product attributes.
		$taxonomies = wc_get_attribute_taxonomies();

		// add them to the list for the setup.
		$list = array();
		foreach( $taxonomies as $taxonomy ) {
			$list[] = array(
				'label' => $taxonomy->attribute_label,
				'value' => $taxonomy->attribute_name,
			);
		}

		// return the resulting list.
		return $list;
	}

	/**
	 * Return the terms for the chosen attribute.
	 *
	 * @return array
	 */
	private function get_terms_for_attribute(): array {
		// get the chosen attribute.
		$attribute_name = get_option( 'psl_product_attribute' );

		// bail if no attribute is chosen.
		if( empty( $attribute_name ) ) {
			return array();
		}

		// get all terms of the chosen attribute.
		$terms = get_terms( array( 'taxonomy' => 'pa_' . $attribute_name, 'hide_empty' => false ) );

		// bail if no terms could be loaded.
		if( ! is_array( $terms ) ) {
			return array();
		}

		// return the terms.
		return $terms;
	}
}
