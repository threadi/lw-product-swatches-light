<?php
/**
 * File to handle attributes of products.
 *
 * @package product-swatches-light
 */

namespace ProductSwatchesLight\Swatches;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easyTransientsForWordPress\Transients;
use ProductSwatchesLight\Plugin\Schedules;
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
	 * @var array<string,mixed>
	 */
	protected array $fields = array();

	/**
	 * Constructor for this object.
	 *
	 * @param object              $taxonomy The used taxonomy.
	 * @param array<string,mixed> $fields The used fields.
	 */
	public function __construct( object $taxonomy, array $fields ) {
		// save it.
		$this->taxonomy = $taxonomy;
		$this->fields   = $fields;

		// set hooks.
		$this->add_actions();
		$this->add_filter();
	}

	/**
	 * Add actions for this attribute.
	 *
	 * @return void
	 */
	private function add_actions(): void {
		// use WP hooks.
		add_action( $this->get_taxonomy_name() . '_add_form_fields', array( $this, 'add' ) );
		add_action( $this->get_taxonomy_name() . '_edit_form_fields', array( $this, 'edit' ) );
		add_action( 'created_term', array( $this, 'save' ), 10, 3 );
		add_action( 'edit_term', array( $this, 'save' ), 10, 3 );

		// use our own hooks.
		add_filter( 'product_swatches_light_get_term_edit_field', array( $this, 'get_edit_field' ), 10, 6 );
		add_filter( 'product_swatches_light_secure_term_value', array( $this, 'secure_edit_field' ), 10, 2 );
	}

	/**
	 * Add filter for this attribute.
	 *
	 * @return void
	 */
	private function add_filter(): void {
		add_filter( 'manage_edit-' . $this->get_taxonomy_name() . '_columns', array( $this, 'add_taxonomy_columns' ) );
		add_action( 'manage_' . $this->get_taxonomy_name() . '_custom_column', array( $this, 'add_taxonomy_column' ), 10, 3 );
	}

	/**
	 * Add the Column for this Attribute in the backend-tables.
	 *
	 * @param array<string,string> $columns List of columns.
	 * @return array<string,string>
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
		$attribute_type = apply_filters( 'product_swatches_light_change_attribute_type_name', $this->taxonomy->attribute_type ); // @phpstan-ignore property.notFound
		$class_name     = '\ProductSwatchesLight\Swatches\AttributeType\\' . $attribute_type . '::get_taxonomy_column';
		if ( class_exists( '\ProductSwatchesLight\Swatches\AttributeType\\' . $attribute_type )
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

		// bail if this is not our taxonomy.
		if ( $this->get_taxonomy_name() !== $taxonomy ) {
			return;
		}

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

		// bail if error occurred.
		if ( $error ) {
			// add error as info for user.
			$transient_obj = Transients::get_instance()->add();
			$transient_obj->set_name( 'lwps_error_term_fields' );
			$transient_obj->set_message( __( '<strong>At least one required field was not filled!</strong> Please fill out the form completely.', 'product-swatches-light' ) );
			$transient_obj->set_type( 'error' );
			$transient_obj->save();
			return;
		}

		// go further if no error was detected.
		for ( $f = 0;$f < $field_count;$f++ ) {
			// get the field.
			$field = $this->fields[ $keys[ $f ] ];

			// format the type.
			$field['type'] = apply_filters( 'product_swatches_light_change_attribute_type_name', $field['type'] );

			// get its name.
			$field_name = $this->get_field_name( $field['id'] );

			// save term depending on request data.
			if ( array_key_exists( $field_name, $_POST ) ) {
				// save the value of this field.
				update_term_meta( $term_id, $field['name'], apply_filters( 'product_swatches_light_secure_term_value', sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) ), $field ) );
			} else {
				// remove the value if it does not exist in request.
				delete_term_meta( $term_id, $field['name'] );
			}
		}

		// add task to update all swatch-caches on products.
		Schedules::get_instance()->add_single_event( 'product_swatches_schedule_regeneration' );
	}

	/**
	 * Add fields for the form in backend for this taxonomy.
	 *
	 * @param WP_Term|false $term The term as object or nothing.
	 * @return void
	 */
	private function get_fields( WP_Term|false $term = false ): void {
		// bail if no fields are defined for this attribute.
		if ( empty( $this->fields ) ) {
			return;
		}

		// loop through the fields.
		$keys        = array_keys( $this->fields );
		$field_count = count( $this->fields );
		for ( $f = 0;$f < $field_count;$f++ ) {
			$field = $this->fields[ $keys[ $f ] ];

			// prepare each value.
			$field_id    = empty( $field['id'] ) ? '0' : (string) $field['id'];
			$depends     = empty( $field['dependency'] ) ? '' : (string) wp_json_encode( $field['dependency'] );
			$placeholder = empty( $field['placeholder'] ) ? '' : (string) $field['placeholder'];
			$required    = ! empty( $field['required'] );
			$value       = $field['value'];
			if ( $term ) {
				$value = get_term_meta( $term->term_id, $field['name'], true );
				if ( empty( $value ) ) {
					$value = $field['value'];
				}
			}

			// format the type.
			$field['type'] = apply_filters( 'product_swatches_light_change_attribute_type_name', $field['type'] );

			// get the html-output for this field depending on its type.
			$html = apply_filters( 'product_swatches_light_get_term_edit_field', '', $field, $field_id, $value, $required, $placeholder );

			// bail if no html is collected.
			if ( empty( $html ) ) {
				return;
			}

			/**
			 * Filter allowed HTML.
			 *
			 * @since 1.0.0 Available since 1.0.0
			 * @param array $html List of allowed HTML-elements.
			 */
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

	/**
	 * Get the cleaned internal taxonomy name.
	 *
	 * @return string
	 */
	public function get_taxonomy_name(): string {
		return wc_attribute_taxonomy_name( $this->taxonomy->attribute_name ); // @phpstan-ignore property.notFound
	}

	/**
	 * Generate the name of a field in backend from given field-Id.
	 *
	 * @param int $field_id The field id.
	 *
	 * @return string
	 */
	protected function get_field_name( int $field_id ): string {
		return 'lwps' . $field_id;
	}

	/**
	 * Get output of field from type the light plugin is using.
	 *
	 * @param string              $html The html to output.
	 * @param array<string,mixed> $field The field.
	 * @param int                 $field_id The field id.
	 * @param string              $value The value.
	 * @param bool                $required If field is required.
	 * @param string              $placeholder The placeholder.
	 *
	 * @return string
	 */
	public function get_edit_field( string $html, array $field, int $field_id, string $value, bool $required, string $placeholder ): string {
		// bail if we already have output.
		if ( ! empty( $html ) ) {
			return $html;
		}

		// get the class name.
		$class_name = '\ProductSwatchesLight\Swatches\FieldType\\' . $field['type'] . '::get_field';

		// output only if class is usable.
		if ( class_exists( '\ProductSwatchesLight\Swatches\FieldType\\' . $field['type'] )
			&& is_callable( $class_name ) ) {
			return call_user_func( $class_name, $this->get_field_name( $field_id ), $value, $field['size'], $required, $placeholder, $field['name'] );
		}

		// return nothing.
		return '';
	}

	/**
	 * Secure term values.
	 *
	 * @param string              $value_to_secure The value to secure.
	 * @param array<string,mixed> $field The field.
	 *
	 * @return string
	 */
	public function secure_edit_field( string $value_to_secure, array $field ): string {
		// secure the value depending on its type.
		$class_name = '\ProductSwatchesLight\Swatches\FieldType\\' . $field['type'] . '::get_secured_value';
		if ( class_exists( '\ProductSwatchesLight\Swatches\FieldType\\' . $field['type'] )
			&& is_callable( $class_name ) ) {
			return call_user_func( $class_name, $value_to_secure );
		}

		// return nothing.
		return '';
	}
}
