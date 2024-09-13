<?php
/**
 * File with WooCommerces-specific handlings.
 *
 * Each time a product is inserted or updated, update its Product Swatches Cache
 * which are then displayed in the category loop.
 *
 * @package product-swatches-light
 */

use ProductSwatches\Plugin\Helper;
use ProductSwatches\Swatches\Product;

/**
 * Set hooks where swatches of single products will be updated directly.
 */
add_action( 'woocommerce_new_product', 'ProductSwatches\Swatches\Product::update2', 10, 2 );
add_action( 'woocommerce_update_product', 'ProductSwatches\Swatches\Product::update2', 10, 2 );
add_action( 'woocommerce_product_set_stock', 'ProductSwatches\Swatches\Product::update' );
add_action( 'woocommerce_variation_set_stock', 'ProductSwatches\Swatches\Product::update' );

/**
 * Initialize plugin-settings-tab in WooCommerce-settings.
 */
\ProductSwatches\Plugin\WcSettingsTab::init();

/**
 * Set position where our swatches on the listing will be visible.
 *
 * @return void
 * @noinspection PhpUnused
 */
function lw_swatches_list_position(): void {
	if ( ! is_single() ) {
		switch ( get_option( 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_position_in_list', 'afterprice' ) ) {
			case 'beforecart':
			case 'aftercart':
				add_action( 'woocommerce_loop_add_to_cart_link', 'lw_swatches_add_product_swatches_in_loop', PHP_INT_MAX, 2 );
				break;
			case 'beforeprice':
				add_action( 'woocommerce_after_shop_loop_item_title', 'lw_swatches_add_product_swatches_in_loop_end', 5, 0 );
				break;
			case 'afterprice':
				add_action( 'woocommerce_after_shop_loop_item_title', 'lw_swatches_add_product_swatches_in_loop_after_prices', 20, 0 );
				break;
			default:
				add_action( 'woocommerce_loop_add_to_cart_link', 'lw_swatches_add_product_swatches_in_loop', 10, 2 );
				break;
		}
	}
}
add_action( 'wp', 'lw_swatches_list_position', 10 );

/**
 * Add "generate_product_swatches"-button in WooCommerce settings.
 *
 * @return void
 * @noinspection PhpUnused
 */
function lw_generate_product_swatches_button(): void {
	$url = add_query_arg(
		array(
			'page'               => 'wc-settings',
			'tab'                => 'lw_product_swatches',
			'generateLWSwatches' => '1',
		),
		get_admin_url() . 'admin.php'
	);
	?><a href="<?php echo esc_url( wp_nonce_url( $url, 'lws-generate' ) ); ?>" class="button button-large lw-update-swatches"><?php echo esc_html__( 'Regenerate all swatches', 'lw-product-swatches' ); ?></a> (<i><?php echo esc_html__( 'takes a moment', 'lw-product-swatches' ); ?></i>)
	<?php
}
add_action( 'woocommerce_admin_field_generate_product_swatches', 'lw_generate_product_swatches_button', 10, 0 );

/**
 * Show product swatches on the product in listings above or under the cart-button.
 *
 * @param string     $add_to_cart_html The HTML to output.
 * @param WC_Product $product The product object.
 * @return string
 * @noinspection PhpUnused
 */
function lw_swatches_add_product_swatches_in_loop( string $add_to_cart_html, WC_Product $product ): string {
	// if this is a variation get its parent for swatches.
	if ( $product instanceof WC_Product_Variation ) {
		$product = wc_get_product( $product->get_parent_id() );
	}

	// get the code depending on cache-setting.
	if ( 'yes' === get_option( 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_disable_cache', 'no' ) ) {
		$code = Product::getSwatches( $product );
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
function lw_swatches_add_product_swatches_in_loop_end(): void {
	$product = wc_get_product( get_the_ID() );
	if ( $product instanceof WC_Product ) {
		echo wp_kses_post( lw_swatches_add_product_swatches_in_loop( '', $product ) );
	}
}

/**
 * Show product swatches on the product in listings at the end of the product-loop after prices.
 *
 * @return void
 * @noinspection PhpUnused
 */
function lw_swatches_add_product_swatches_in_loop_after_prices(): void {
	$product = wc_get_product( get_the_ID() );
	if ( $product instanceof WC_Product ) {
		echo wp_kses_post( lw_swatches_add_product_swatches_in_loop( '', $product ) );
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
function lw_swatches_add_in_block( string $html, stdClass $data, WC_Product $product ): string {
	if ( 'variable' === $product->get_type() ) {
		if ( get_option( 'wc_' . LW_SWATCH_WC_SETTING_NAME . '_disable_cache', 'no' ) == 'yes' ) {
			$product_swatches = Product::getSwatches( $product );
		} else {
			$product_swatches = get_post_meta( $product->get_id(), LW_SWATCH_CACHEKEY, true );
		}

		if ( ! empty( $product_swatches ) ) {
			// prepare output.
			$html = '<li class="wc-block-grid__product">
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
	}
	return $html;
}
add_filter( 'woocommerce_blocks_product_grid_item_html', 'lw_swatches_add_in_block', 10, 3 );

/**
 * Run if link to update the swatches of a single product has been called.
 *
 * @return void
 * @noinspection PhpUnused
 */
function lw_swatches_run_product_action() {
	if ( empty( $_REQUEST['post'] ) ) {
		wp_die( esc_html__( 'No product has been supplied!', 'lw-product-swatches' ) );
	}

	// get product id.
	$product_id = absint( $_REQUEST['post'] );

	// check nonce.
	check_admin_referer( 'woocommerce-lws-resetswatches_' . $product_id );

	// get the product.
	$product = wc_get_product( $product_id );

	// update the swatches.
	Product::update( $product );

	// show success-message.
	set_transient(
		'lwSwatchesMessage',
		array(
			'message' => '<strong>' . __( 'The swatches of the product have been updated.', 'lw-product-swatches' ) . '</strong>',
			'state'   => 'success',
		)
	);

	// Redirect to the edit screen for the new draft page.
	wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $product_id ) );
	exit;
}
add_action( 'admin_action_lws_resetswatches', 'lw_swatches_run_product_action' );

/**
 * Add bulk action to regenerate multiple swatches via product-table in backend.
 *
 * @param array $actions List of actions.
 * @return array
 * @noinspection PhpUnused
 */
function lw_swatches_add_bulk_actions( array $actions ): array {
	$actions['lws-generate-swatches'] = __( 'Swatches generieren', 'lw-product-swatches' );
	return $actions;
}
add_filter( 'bulk_actions-edit-product', 'lw_swatches_add_bulk_actions', 20, 1 );

/**
 * Run bulk aktion to regenerate multiple swatches via product-table in backend.
 *
 * @param string $redirect_to The URL to redirect to.
 * @param array  $action The action-settings.
 * @param array  $post_ids The IDs chosen.
 * @return string
 * @noinspection PhpUnused
 */
function lw_swatches_run_bulk_actions( string $redirect_to, array $action, array $post_ids ): string {
	foreach ( $post_ids as $post_id ) {
		$product = wc_get_product( $post_id );
		Product::update( $product );
	}

	// set transient to show success.
	set_transient(
		'lwSwatchesMessage',
		array(
			'message' => __( 'The swatches of the selected products have been updated.', 'lw-product-swatches' ),
			'state'   => 'success',
		)
	);

	// return the redirect-url.
	return $redirect_to;
}
add_filter( 'handle_bulk_actions-edit-product', 'lw_swatches_run_bulk_actions', 10, 3 );

/**
 * Add link to reset the swatches of a single product in product-edit-page.
 *
 * @param WP_Post $post The post object.
 * @return void
 * @noinspection PhpUnused
 */
function lw_swatches_add_product_action( WP_Post $post ): void {
	if ( 'product' !== $post->post_type ) {
		return;
	}

	$product = wc_get_product( $post->ID );
	if ( $product->get_type() == 'variable' ) {
		$url = wp_nonce_url( admin_url( 'edit.php?post_type=product&action=lws_resetswatches&post=' . absint( $post->ID ) ), 'woocommerce-lws-resetswatches_' . $post->ID );
		/* translators: %1$s is replaced with "string" */
		?>
		<div class="misc-pub-section"><?php printf( __( '<a href="%1$s">Save swatches</a> of this product', 'lw-product-swatches' ), esc_url( $url ) ); ?></div>
													<?php
	}
}
add_filter( 'post_submitbox_misc_actions', 'lw_swatches_add_product_action', 10, 1 );

/**
 * Add additional attribute types which are used by this plugin.
 * Only if they do not already exist.
 *
 * @param array $attribute_type The setting for the attribute.
 * @return array
 * @noinspection PhpUnused
 */
function lw_swatches_add_attribute_types( array $attribute_type ): array {
	foreach ( Helper::get_attribute_types() as $key => $attribute ) {
		if ( empty( $attribute_type[ $key ] ) ) {
			$attribute_type[ $key ] = $attribute['label'];
		}
	}
	return $attribute_type;
}
add_filter( 'product_attributes_type_selector', 'lw_swatches_add_attribute_types', 10, 1 );

/**
 * Extend output of attributes in product-detail-edit-page with the additional types which are used by this plugin.
 *
 * @param stdClass $attribute_taxonomy The taxonomy object.
 * @param int $i The counter.
 * @return void
 * @noinspection PhpUnused
 */
function lw_swatches_product_option_terms( stdClass $attribute_taxonomy, int $i ): void {
	global $thepostid;

	if ( array_key_exists( $attribute_taxonomy->attribute_type, Helper::getAttributeTypes() ) ) {

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
		$attribute_type = apply_filters( 'lw_swatches_change_attribute_type_name', $attribute_taxonomy->attribute_type );
		$class_name     = '\LW_Swatches\AttributeType\\' . $attribute_type . '::getEditList';
		if ( class_exists( '\LW_Swatches\AttributeType\\' . $attribute_type )
			&& is_callable( $class_name ) ) {
			echo call_user_func( $class_name, $all_terms, $product_id );
		}

		?>
		<select multiple="multiple"
				data-placeholder="<?php esc_attr_e( 'Select term(s)', 'lw-swatches' ); ?>"
				class="multiselect attribute_values wc-taxonomy-term-search lw-product-swatches"
				data-type="<?php echo esc_attr( $attribute_taxonomy->attribute_type ); ?>"
				name="attribute_values[<?php echo esc_attr( $i ); ?>][]">
			<?php
			// get all terms and loop through them
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
}
add_action( 'woocommerce_product_option_terms', 'lw_swatches_product_option_terms', 20, 2 );
