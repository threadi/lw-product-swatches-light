<?php
/**
 * This file contains the tasks for WooCommerce.
 *
 * @package product-swatches-light
 */

namespace ProductSwatchesLight\Swatches;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ProductSwatchesLight\Plugin\Helper;
use ProductSwatchesLight\Plugin\Transients;
use stdClass;
use WC_Product;
use WC_Product_Variation;
use WP_Post;
use WP_Term;

/**
 * Object to handle the tasks for WooCommerce.
 */
class WooCommerce {
	/**
	 * Instance of actual object.
	 *
	 * @var WooCommerce|null
	 */
	private static ?WooCommerce $instance = null;

	/**
	 * Constructor, not used as this a Singleton object.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning of this object.
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Return instance of this object as singleton.
	 *
	 * @return WooCommerce
	 */
	public static function get_instance(): WooCommerce {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the tasks.
	 *
	 * @return void
	 */
	public function init(): void {
		// initialize the custom settings in WooCommerce.
		WcSettingsTab::init();

		// add functional settings.
		add_filter( 'product_attributes_type_selector', array( $this, 'add_attribute_types' ) );
		add_action( 'woocommerce_product_option_terms', array( $this, 'extend_option_terms' ), 20, 2 );

		// list swatches in frontend.
		add_action( 'wp', array( $this, 'set_position' ) );
		add_filter( 'woocommerce_blocks_product_grid_item_html', array( $this, 'add_in_block' ), 10, 3 );

		// add actions in backend.
		add_filter( 'bulk_actions-edit-product', array( $this, 'add_bulk_actions' ), 20 );
		add_filter( 'handle_bulk_actions-edit-product', array( $this, 'run_bulk_actions' ), 10, 3 );

		// add single actions.
		add_filter( 'post_submitbox_misc_actions', array( $this, 'add_product_action' ) );
		add_action( 'admin_action_product_swatches_regenerate', array( $this, 'regenerate_swatches_by_request' ) );

		// use hooks to update swatches on single products.
		add_action( 'woocommerce_new_product', array( Products::get_instance(), 'update_product' ) );
		add_action( 'woocommerce_update_product', array( Products::get_instance(), 'update_product' ) );
		add_action( 'woocommerce_product_set_stock', array( Products::get_instance(), 'update_product' ) );
		add_action( 'woocommerce_variation_set_stock', array( Products::get_instance(), 'update_product' ) );

		// use our own hooks.
		add_action( 'product_swatches_light_option_list', array( $this, 'show_in_list' ), 10, 3 );
		add_filter( 'product_swatches_light_get_attribute_values', array( $this, 'get_attribute_values' ), 10, 4 );
		add_filter( 'product_swatches_light_get_list', array( $this, 'get_list' ), 10, 9 );
	}

	/**
	 * Set position where our swatches on the listing will be visible.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function set_position(): void {
		if ( ! is_single() ) {
			switch ( get_option( 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_position_in_list', 'afterprice' ) ) {
				case 'beforecart':
				case 'aftercart':
					add_action( 'woocommerce_loop_add_to_cart_link', array( $this, 'add_product_swatches_in_loop' ), PHP_INT_MAX, 2 );
					break;
				case 'beforeprice':
					add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'add_product_swatches_in_loop_end' ), 5, 0 );
					break;
				case 'afterprice':
					add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'add_product_swatches_in_loop_after_prices' ), 20, 0 );
					break;
				default:
					add_action( 'woocommerce_loop_add_to_cart_link', array( $this, 'lw_swatches_add_product_swatches_in_loop' ), 10, 2 );
					break;
			}
		}
	}

	/**
	 * Show product swatches on the product in listings.
	 *
	 * @param string     $add_to_cart_html The HTML to output.
	 * @param WC_Product $product The product object.
	 * @return string
	 * @noinspection PhpUnused
	 */
	public function add_product_swatches_in_loop( string $add_to_cart_html, WC_Product $product ): string {
		// if this is a variation get its parent for swatches.
		if ( $product instanceof WC_Product_Variation ) {
			$product = wc_get_product( $product->get_parent_id() );
		}

		// get the code depending on cache-setting.
		$code = '';
		if ( 'yes' === get_option( 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_disable_cache', 'no' ) ) {
			$product = Products::get_instance()->get_product( $product->get_id() );
			if ( $product instanceof Product ) {
				$code = $product->get_swatches();
			}
		} else {
			$code = get_post_meta( $product->get_id(), LW_SWATCH_CACHEKEY, true );
		}

		// set code on configured position in relation to the card-button.
		$after  = '';
		$before = '';
		if ( 'beforecart' === get_option( 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_position_in_list', 'afterprice' ) ) {
			$before = $code;
		} else {
			$after = $code;
		}

		// return result.
		return $before . $add_to_cart_html . $after;
	}

	/**
	 * Show product swatches on the product in listings at the end of the product-loop above prices.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function add_product_swatches_in_loop_end(): void {
		$product = wc_get_product( get_the_ID() );
		if ( $product instanceof WC_Product ) {
			echo wp_kses_post( $this->add_product_swatches_in_loop( '', $product ) );
		}
	}

	/**
	 * Show product swatches on the product in listings at the end of the product-loop after prices.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function add_product_swatches_in_loop_after_prices(): void {
		$product = wc_get_product( get_the_ID() );
		if ( $product instanceof WC_Product ) {
			echo wp_kses_post( $this->add_product_swatches_in_loop( '', $product ) );
		}
	}

	/**
	 * Add Swatches in Gutenberg-Block for single product.
	 *
	 * @param string     $html The returning HTML-code.
	 * @param stdClass   $data The object with data.
	 * @param WC_Product $product The product as object.
	 * @return string
	 * @noinspection PhpUnused
	 */
	public function add_in_block( string $html, stdClass $data, WC_Product $product ): string {
		// bail if this is not a variable product.
		if ( 'variable' !== $product->get_type() ) {
			return $html;
		}

		// get swatches depending on cache setting.
		if ( 'yes' === get_option( 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_disable_cache', 'no' ) ) {
			$product          = Products::get_instance()->get_product( $product->get_id() );
			$product_swatches = $product->get_swatches();
		} else {
			$product_swatches = get_post_meta( $product->get_id(), LW_SWATCH_CACHEKEY, true );
		}

		// bail if no swatches available.
		if ( empty( $product_swatches ) ) {
			return $html;
		}

		// return html code for block.
		return '<li class="wc-block-grid__product">
			<a href="' . $data->permalink . '" class="wc-block-grid__product-link">
				' . $data->image . '
				' . $data->title . '
			</a>
			' . $data->badge . '
			' . $data->price . '
			' . $data->rating . '
			' . $product_swatches . '
			' . $data->button . '
		</li>';
	}

	/**
	 * Add bulk action to regenerate multiple swatches via product-table in backend.
	 *
	 * @param array $actions List of actions.
	 * @return array
	 * @noinspection PhpUnused
	 */
	public function add_bulk_actions( array $actions ): array {
		// add our action.
		$actions['lws-generate-swatches'] = __( 'Swatches generieren', 'product-swatches-light' );

		// return all actions.
		return $actions;
	}

	/**
	 * Run bulk aktion to regenerate multiple swatches via product-table in backend.
	 *
	 * @param string $redirect_to The URL to redirect to.
	 * @param string $do_action The action-settings.
	 * @param array  $items The IDs chosen.
	 *
	 * @return string
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection*/
	public function run_bulk_actions( string $redirect_to, string $do_action, array $items ): string {
		foreach ( $items as $post_id ) {
			$product = Products::get_instance()->get_product( $post_id );
			$product->update_swatches();
		}

		// set transient to show success.
		$transient_obj = Transients::get_instance()->add();
		$transient_obj->set_name( 'lwps_bulk_generated' );
		$transient_obj->set_message( __( 'The swatches of the selected products has been updated.', 'product-swatches-light' ) );
		$transient_obj->set_type( 'success' );
		$transient_obj->save();

		// return the redirect-url.
		return $redirect_to;
	}

	/**
	 * Add link to reset the swatches of a single product in product-edit-page.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function add_product_action( WP_Post $post ): void {
		// bail if this is no a product type single edit view.
		if ( 'product' !== $post->post_type ) {
			return;
		}

		// get the product object.
		$product = wc_get_product( $post->ID );

		// bail if this is not a variable product.
		if ( 'variable' !== $product->get_type() ) {
			return;
		}

		// create URL for action.
		$url = add_query_arg(
			array(
				'action' => 'product_swatches_regenerate',
				'nonce'  => wp_create_nonce( 'product-swatches-regenerate-swatches' ),
				'post'   => $post->ID,
			),
			admin_url() . 'admin.php'
		);

		?><div class="misc-pub-section">
		<?php
		/* translators: %1$s is replaced with the URL for regenerate the swatches. */
		echo wp_kses_post( sprintf( __( '<a href="%1$s">Save swatches</a> of this product', 'product-swatches-light' ), esc_url( $url ) ) );
		?>
		</div>
		<?php
	}

	/**
	 * Add additional attribute types which are used by this plugin.
	 * Only if they do not already exist.
	 *
	 * @param array $attribute_type The setting for the attribute.
	 * @return array
	 * @noinspection PhpUnused
	 */
	public function add_attribute_types( array $attribute_type ): array {
		foreach ( Helper::get_attribute_types() as $key => $attribute ) {
			if ( empty( $attribute_type[ $key ] ) ) {
				$attribute_type[ $key ] = $attribute['label'];
			}
		}
		return $attribute_type;
	}

	/**
	 * Extend output of attributes in product-detail-edit-page with the additional types which are used by this plugin.
	 *
	 * @param stdClass $attribute_taxonomy The taxonomy object.
	 * @param int      $i The counter.
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function extend_option_terms( stdClass $attribute_taxonomy, int $i ): void {
		global $thepostid;

		// bail if this is not one of our own types.
		if ( ! array_key_exists( $attribute_taxonomy->attribute_type, Helper::get_attribute_types() ) ) {
			return;
		}

		// check for nonce.
		if ( isset( $_GET['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'product-swatches-settings' ) ) {
			return;
		}

		// get taxonomy name.
		$taxonomy = wc_attribute_taxonomy_name( $attribute_taxonomy->attribute_name );

		// get the product-id.
		$product_id = $thepostid;
		if ( is_null( $thepostid ) && isset( $_POST['post_id'] ) ) {
			$product_id = absint( $_POST['post_id'] );
		}

		// create a select-box with the values of this attribute.
		$args = array(
			'taxonomy'   => $taxonomy,
			'orderby'    => 'name',
			'hide_empty' => 0,
		);

		// get all terms and loop through them.
		$all_terms = get_terms( $args );

		// generate output depending on the attribute-type.
		do_action( 'product_swatches_light_option_list', $attribute_taxonomy, $all_terms, $product_id );

		?>
		<select multiple="multiple"
				data-placeholder="<?php esc_attr_e( 'Select term(s)', 'lw-swatches' ); ?>"
				class="multiselect attribute_values wc-taxonomy-term-search lw-product-swatches"
				data-type="<?php echo esc_attr( $attribute_taxonomy->attribute_type ); ?>"
				name="attribute_values[<?php echo esc_attr( $i ); ?>][]">
			<?php
			// get all terms and loop through them.
			if ( ! empty( $all_terms ) ) {
				foreach ( $all_terms as $term ) {
					echo '<option value="' . esc_attr( $term->term_id ) . '" ' . selected( has_term( absint( $term->term_id ), $taxonomy, $product_id ), true, false ) . '>' . esc_attr( apply_filters( 'woocommerce_product_attribute_term_name', $term->name, $term ) ) . '</option>';
				}
			}
			?>
		</select>
		<button class="button plus select_all_attributes"><?php esc_html_e( 'Select all', 'woocommerce' ); ?></button>
		<button class="button minus select_no_attributes"><?php esc_html_e( 'Select none', 'woocommerce' ); ?></button>
		<?php
	}

	/**
	 * Regenerate swatches by request.
	 *
	 * @return void
	 * @noinspection PhpNoReturnAttributeCanBeAddedInspection
	 */
	public function regenerate_swatches_by_request(): void {
		check_ajax_referer( 'product-swatches-regenerate-swatches', 'nonce' );

		// get post id from request, if set.
		$post_id = absint( filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT ) );

		// if single ID is given only regenerate this product.
		if ( $post_id > 0 ) {
			$product = Products::get_instance()->get_product( $post_id );
			$product->update_swatches();
		} else {
			// otherwise regenerate all swatches.
			Products::get_instance()->update_swatches_on_products();
		}

		// redirect user.
		wp_safe_redirect( isset( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ) : '' );
		exit;
	}

	/**
	 * Show swatch content in listings.
	 *
	 * @param stdClass $attribute_taxonomy The attribute taxonomy.
	 * @param array    $all_terms The terms.
	 * @param int      $product_id The product id.
	 *
	 * @return void
	 */
	public function show_in_list( stdClass $attribute_taxonomy, array $all_terms, int $product_id ): void {
		// get the attribute type.
		$attribute_type = apply_filters( 'product_swatches_light_change_attribute_type_name', $attribute_taxonomy->attribute_type );

		// get the class name.
		$class_name = '\ProductSwatchesLight\Swatches\AttributeType\\' . $attribute_type . '::get_edit_list';

		// show the output ot the attribute type class, if available.
		if ( class_exists( '\ProductSwatchesLight\Swatches\AttributeType\\' . $attribute_type )
			&& is_callable( $class_name ) ) {
			echo wp_kses_post( call_user_func( $class_name, $all_terms, $product_id ) );
		}
	}

	/**
	 * Get values of given term by its attribute-type.
	 *
	 * @param array   $value_list List of values.
	 * @param string  $attribute_type The attribute-type.
	 * @param WP_Term $term The term.
	 * @param string  $term_name The term name.
	 *
	 * @return array
	 */
	public function get_attribute_values( array $value_list, string $attribute_type, WP_Term $term, string $term_name ): array {
		$class_name = '\ProductSwatchesLight\Swatches\AttributeType\\' . $attribute_type . '::get_values';
		if ( class_exists( '\ProductSwatchesLight\Swatches\AttributeType\\' . $attribute_type )
			&& is_callable( $class_name ) ) {
			return call_user_func( $class_name, $term->term_id, $term_name );
		}

		return $value_list;
	}

	/**
	 * Get list.
	 *
	 * @param string $html The output.
	 * @param string $attribute_type The attribute type name.
	 * @param array  $resulting_list The item list.
	 * @param array  $images The images.
	 * @param array  $images_sets The list if imagesets.
	 * @param array  $values The values.
	 * @param array  $on_sales The sales-marker.
	 * @param string $permalink The permalink for the product.
	 * @param string $title The title of the product.
	 * @return string
	 */
	public function get_list( string $html, string $attribute_type, array $resulting_list, array $images, array $images_sets, array $values, array $on_sales, string $permalink, string $title ): string {
		$class_name = '\ProductSwatchesLight\Swatches\AttributeType\\' . $attribute_type . '::get_list';
		if ( class_exists( '\ProductSwatchesLight\Swatches\AttributeType\\' . $attribute_type )
			&& is_callable( $class_name ) ) {
			$html .= call_user_func( $class_name, $resulting_list, $images, $images_sets, $values, $on_sales, $permalink, $title );
		}
		return $html;
	}
}
