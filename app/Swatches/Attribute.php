<?php
/**
 * File to handle attributes of products.
 *
 * @package product-swatches-light
 */

namespace ProductSwatches\Swatches;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ProductSwatches\Plugin\Helper;
use WP_Term;

/**
 * Handling for a single attribute in this wp-project, e.g. sizes.
 */
class Attribute {

	/**
	 * The used taxonomy.
	 *
	 * @var object
	 */
	protected object $taxonomy;

	/**
	 * The used fields.
	 *
	 * @var array
	 */
	protected array $fields = array();

	/**
	 * Constructor for this object.
	 *
	 * @param object $taxonomy The used taxonomy.
	 * @param array  $fields The used fields.
	 */
	public function __construct( object $taxonomy, array $fields ) {
		$this->taxonomy = $taxonomy;
		$this->fields   = $fields;

		if ( ! empty( $taxonomy ) ) {
			$this->add_actions();
			$this->add_filter();
		}
	}

	/**
	 * Add actions for this attribute.
	 *
	 * @return void
	 */
	private function add_actions(): void {
		add_action( $this->get_taxonomy_name() . '_add_form_fields', array( $this, 'add' ) );
		add_action( $this->get_taxonomy_name() . '_edit_form_fields', array( $this, 'edit' ) );
		add_action( 'created_term', array( $this, 'save' ), 10, 3 );
		add_action( 'edit_term', array( $this, 'save' ), 10, 3 );
	}

	/**
	 * Add filter for this attribute.
	 *
	 * @return void
	 */
	private function add_filter(): void {
		add_filter( 'manage_edit-' . $this->get_taxonomy_name() . '_columns', array( $this, 'add_taxonomy_columns' ) );
		add_filter( 'manage_' . $this->get_taxonomy_name() . '_custom_column', array( $this, 'add_taxonomy_column' ), 10, 3 );
	}

	/**
	 * Add the Column for this Attribute in the backend-tables.
	 *
	 * @param array $columns List of columns.
	 * @return array
	 */
	public function add_taxonomy_columns( array $columns ): array {
		$new_columns = array();

		// move checkbox-field in first row.
		if ( isset( $columns['cb'] ) ) {
			$new_columns['cb'] = $columns['cb'];
			unset( $columns['cb'] );
		}

		// add column with empty title.
		$new_columns[ 'lw-swatches-' . $this->get_taxonomy_name() ] = '';

		// return resulting merged list.
		return array_merge( $new_columns, $columns );
	}

	/**
	 * Add the content of this column for this attribute in the backend-tables.
	 *
	 * @param string $output The output.
	 * @param string $column The column name.
	 * @param int    $term_id The term id.
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function add_taxonomy_column( string $output, string $column, int $term_id ): void {
		$attribute_type = apply_filters( 'lw_swatches_change_attribute_type_name', $this->taxonomy->attribute_type );
		$class_name     = '\ProductSwatches\AttributeType\\' . $attribute_type . '::get_taxonomy_column';
		// TODO prüfen wieso "color" hier klein geschrieben ist.
		var_dump($class_name, class_exists( '\ProductSwatches\AttributeType\\' . $attribute_type ) );
		if ( class_exists( '\ProductSwatches\AttributeType\\' . $attribute_type )
			&& is_callable( $class_name ) ) {
			echo wp_kses_post( call_user_func( $class_name, $term_id, $this->fields ) );
		}
	}

	/**
	 * Run on add-a-taxonomy-form in backend.
	 *
	 * @return void
	 */
	public function add(): void {
		$this->get_fields();
	}

	/**
	 * Run on edit-a-taxonomy-form in backend.
	 *
	 * @param WP_Term $term The term as object.
	 * @return void
	 */
	public function edit( WP_Term $term ): void {
		$this->get_fields( $term );
	}

	/**
	 * Save individual settings for a term in backend.
	 *
	 * @param int    $term_id The term id.
	 * @param int    $tt_id The taxonomy id.
	 * @param string $taxonomy The taxonomy.
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function save( int $term_id, int $tt_id = 0, string $taxonomy = '' ): void {
		// check for nonce.
		if ( isset( $_GET['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'product-swatches-settings' ) ) {
			return;
		}

		if ( $this->get_taxonomy_name() === $taxonomy ) {
			// loop through the fields of this attribute,
			// check if it is required
			// and save the value of each if all necessary values are available.
			$error       = false;
			$keys        = array_keys( $this->fields );
			$field_count = count( $this->fields );
			for ( $f = 0;$f < $field_count;$f++ ) {
				$field = $this->fields[ $keys[ $f ] ];
				if ( 1 === absint( $field['required'] ) ) {
					$field_name = $this->get_field_name( $field['id'] );
					if ( array_key_exists( $field_name, $_POST ) && empty( $_POST[ $field_name ] ) ) {
						$error = true;
					}
					if ( ! array_key_exists( $field_name, $_POST ) ) {
						$error = true;
					}
				}
			}

			// go further if no error was detected.
			if ( false === $error ) {
				for ( $f = 0;$f < $field_count;$f++ ) {
					$field      = $this->fields[ $keys[ $f ] ];
					$field_name = $this->get_field_name( $field['id'] );
					if ( array_key_exists( $field_name, $_POST ) ) {
						// secure the value depending on its type.
						$class_name = '\ProductSwatches\FieldType\\' . $field['type'] . '::get_secured_value';
						if ( class_exists( '\ProductSwatches\FieldType\\' . $field['type'] )
							&& is_callable( $class_name ) ) {
							$post_value = call_user_func( $class_name, sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) ) );

							// save the value of this field.
							update_term_meta( $term_id, $field['name'], $post_value );
						}
					} else {
						// remove the value if it does not exist in request.
						delete_term_meta( $term_id, $field['name'] );
					}
				}

				// add task to update all swatch-caches on products using this attribute.
				Helper::add_task_for_scheduler( array( '\ProductSwatches\Plugin\Helper::update_swatches_on_products_by_type', 'attribute', $taxonomy ) );
			} else {
				// show an error message.
				set_transient(
					'lwSwatchesMessage',
					array(
						'message' => __( '<strong>At least one required field was not filled!</strong> Please fill out the form completely.', 'lw-product-swatches' ),
						'state'   => 'error',
					)
				);
			}
		}
	}

	/**
	 * Add fields for the form in backend for this taxonomy.
	 *
	 * @param WP_Term|false $term The term as object or nothing.
	 * @return void
	 */
	private function get_fields( WP_Term|false $term = false ): void {
		if ( empty( $this->fields ) ) {
			return;
		}
		$keys        = array_keys( $this->fields );
		$field_count = count( $this->fields );
		for ( $f = 0;$f < $field_count;$f++ ) {
			$field = $this->fields[ $keys[ $f ] ];

			// prepare each value.
			$field_id    = empty( $field['id'] ) ? 0 : $field['id'];
			$depends     = empty( $field['dependency'] ) ? '' : wp_json_encode( $field['dependency'] );
			$placeholder = empty( $field['placeholder'] ) ? '' : $field['placeholder'];
			$required    = ! empty( $field['required'] );
			$value       = $field['value'];
			if ( $term ) {
				$value = get_term_meta( $term->term_id, $field['name'], true );
				if ( empty( $value ) ) {
					$value = $field['value'];
				}
			}

			// get the html-output for this field depending on its type.
			$class_name = '\ProductSwatches\FieldType\\' . $field['type'] . '::get_field';
			$html       = '';
			if ( class_exists( '\ProductSwatches\FieldType\\' . $field['type'] )
				&& is_callable( $class_name ) ) {
				$html = call_user_func( $class_name, $this->get_field_name( $field_id ), $value, $field['size'], $required, $placeholder, $field['name'] );
			}

			// set allowed html for field-output.
			$allowed_html = apply_filters(
				'lw_swatches_allowed_html',
				array(
					'select' => array(
						'id'       => array(),
						'name'     => array(),
						'required' => array(),
					),
					'option' => array(
						'value'    => array(),
						'selected' => array(),
					),
				)
			);

			if ( ! empty( $html ) ) {
				if ( ! $term ) {
					?>
					<div class="form-field term-<?php echo esc_attr( $field_id ); ?>-wrap" data-lsw-dependency="<?php echo esc_attr( $depends ); ?>">
						<label for="tag-<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
						<?php
						echo wp_kses( $html, $allowed_html );
						if ( ! empty( $field['desc'] ) ) {
							?>
							<p class="description"><?php echo wp_kses_post( $field['desc'] ); ?></p>
							<?php
						}
						?>
					</div>
					<?php
				} else {
					// prepare output.
					?>
					<tr data-lsw-dependency="<?php echo esc_attr( $depends ); ?>" class="form-field <?php echo esc_attr( $field_id ); ?> <?php echo empty( $field['required'] ) ? '' : 'form-required'; ?>">
						<th scope="row"><label
								for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
						<td>
							<?php
							echo wp_kses( $html, $allowed_html );
							if ( ! empty( $field['desc'] ) ) {
								?>
								<p class="description"><?php echo wp_kses_post( $field['desc'] ); ?></p>
								<?php
							}
							?>
						</td>
					</tr>
					<?php
				}
			}
		}
	}

	/**
	 * Get the cleaned internal taxonomy name.
	 *
	 * @return string
	 */
	public function get_taxonomy_name(): string {
		return wc_attribute_taxonomy_name( $this->taxonomy->attribute_name );
	}

	/**
	 * Generate the name of a field in backend from given field-Id.
	 *
	 * @param int $field_id The field id.
	 * @return string
	 */
	private function get_field_name( int $field_id ): string {
		return 'lws' . $field_id;
	}
}
