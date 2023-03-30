<?php

namespace LW_Swatches;

/**
 * Handling for a single attribute in this wp-project, e.g. sizes.
 */
class Attribute {

    // taxonomy
    protected object $taxonomy;

    // fields
    protected array $fields = [];

    /**
     * Constructor for this object.
     *
     * @param $taxonomy
     * @param $fields
     */
    public function __construct( $taxonomy, $fields ) {
        $this->taxonomy = $taxonomy;
        $this->fields = $fields;

        if( !empty($taxonomy) ) {
            $this->addActions();
            $this->addFilter();
        }
    }

    /**
     * Add actions for this attribute.
     *
     * @return void
     */
    private function addActions(): void {
        add_action( $this->getTaxonomyName().'_add_form_fields', [$this, 'add']);
        add_action( $this->getTaxonomyName().'_edit_form_fields', [$this, 'edit']);
        add_action( 'created_term', [$this, 'save'], 10, 3);
        add_action( 'edit_term', [$this, 'save'], 10, 3);
    }

    /**
     * Add filter for this attribute.
     *
     * @return void
     */
    private function addFilter(): void {
        add_filter( 'manage_edit-'.$this->getTaxonomyName().'_columns', [$this, 'addTaxonomyColumns']);
        add_filter( 'manage_'.$this->getTaxonomyName().'_custom_column', [$this, 'addTaxonomyColumn'], 10, 3);
    }

    /**
     * Add the Column for this Attribute in the backend-tables.
     *
     * @param $columns
     * @return array
     */
    public function addTaxonomyColumns( $columns ): array
    {
        $new_columns = array();

        // move checkbox-field in first row
        if (isset($columns['cb'])) {
            $new_columns['cb'] = $columns['cb'];
            unset($columns['cb']);
        }

        // add column with empty title
        $new_columns['lw-swatches-'.$this->getTaxonomyName()] = '';

        return array_merge($new_columns, $columns);
    }

    /**
     * Add the content of this column for this attribute in the backend-tables.
     *
     * @param $columns
     * @param $column
     * @param $term_id
     * @return void
     * @noinspection PhpUnusedParameterInspection
     */
    public function addTaxonomyColumn( $columns, $column, $term_id ): void
    {
        $attribute_type = apply_filters('lw_swatches_change_attribute_type_name', $this->taxonomy->attribute_type);
        $className = '\LW_Swatches\AttributeType\\'.$attribute_type.'::getTaxonomyColumn';
        if( class_exists("\LW_Swatches\AttributeType\\".$attribute_type)
            && is_callable($className) ) {
            echo call_user_func($className, $term_id, $this->fields);
        }
    }

    /**
     * Run on add-a-taxonomy-form in backend.
     *
     * @return void
     */
    public function add(): void
    {
        $this->getFields();
    }

    /**
     * Run on edit-a-taxonomy-form in backend.
     *
     * @param $term
     * @return void
     */
    public function edit($term): void
    {
        $this->getFields($term);
    }

    /**
     * Save individual settings for a term in backend.
     *
     * @param $term_id
     * @param $tt_id
     * @param $taxonomy
     * @return void
     * @noinspection PhpMissingParamTypeInspection
     * @noinspection PhpUnusedParameterInspection
     */
    public function save( $term_id, $tt_id = '', $taxonomy = '' ): void
    {
        if( $taxonomy == $this->getTaxonomyName() ) {
            // loop through the fields of this attribute,
            // check if it is required
            // and save the value of each if all necessary values are available
            $error = false;
            $keys = array_keys($this->fields);
            for( $f=0;$f<count($this->fields);$f++ ) {
                $field = $this->fields[$keys[$f]];
                if( absint($field['required']) == 1 ) {
                    $fieldName = $this->getFieldName($field['id']);
                    if( array_key_exists($fieldName, $_POST) && empty($_POST[$fieldName]) ) {
                        $error = true;
                    }
                    if( !array_key_exists($fieldName, $_POST) ) {
                        $error = true;
                    }
                }
            }

            // go further if no error was detected
            if( false === $error ) {
                for( $f=0;$f<count($this->fields);$f++ ) {
                    $field = $this->fields[$keys[$f]];
                    $fieldName = $this->getFieldName($field['id']);
                    if (array_key_exists($fieldName, $_POST)) {
                        // secure the value depending on its type
                        $className = '\LW_Swatches\FieldType\\' . $field['type'] . '::getSecuredValue';
                        if (class_exists("\LW_Swatches\FieldType\\" . $field['type'])
                            && is_callable($className)) {
                            $post_value = call_user_func($className, $_POST[$fieldName]);

                            // save the value of this field
                            update_term_meta($term_id, $field['name'], $post_value);
                        }
                    } else {
                        // remove the value if it does not exist in request
                        delete_term_meta($term_id, $field['name']);
                    }
                }

                // add task to update all swatch-caches on products using this attribute
                helper::addTaskForScheduler(['\LW_Swatches\helper::updateSwatchesOnProductsByType', 'attribute', $taxonomy]);
            }
            else {
                // show an error message
                set_transient( 'lwSwatchesMessage', [
                    'message' => __('<strong>At least one required field was not filled!</strong> Please fill out the form completely.', 'lw-product-swatches'),
                    'state' => 'error'
                ] );
            }
        }
    }

    /**
     * Add fields for the form in backend for this taxonomy.
     *
     * @param $term
     * @return void
     * @noinspection PhpMissingParamTypeInspection
     */
    private function getFields( $term = false ): void
    {
        if( empty($this->fields) ) {
            return;
        }
        $keys = array_keys($this->fields);
        for( $f=0;$f<count($this->fields);$f++ ) {
            $field = $this->fields[$keys[$f]];
            // prepare each value
            $fieldId = empty($field['id']) ? 0 : $field['id'];
            $depends = empty($field['dependency']) ? '' : wp_json_encode($field['dependency']);
            $placeholder = empty($field['placeholder']) ? '' : $field['placeholder'];
            $required = !empty($field['required']);
            $value =  $field['value'];
            if( $term ) {
                $value = get_term_meta( $term->term_id, $field['name'], true );
                if( empty($value) ) {
                    $value = $field['value'];
                }
            }

            // get the html-output for this field depending on its type
            $className = '\LW_Swatches\FieldType\\'.$field['type'].'::getField';
            $html = '';
            if( class_exists("\LW_Swatches\FieldType\\".$field['type'])
                && is_callable($className) ) {
                $html = call_user_func($className, $this->getFieldName($fieldId), $value, $field['size'], $required, $placeholder, $field['name']);
            }

            if( !empty($html) ) {
                if (!$term) {
                    ?>
                    <div class="form-field term-<?php echo esc_attr($fieldId) ?>-wrap" data-lsw-dependency="<?php echo esc_attr($depends); ?>">
                        <label for="tag-<?php echo esc_attr($fieldId) ?>"><?php echo esc_html($field['label']); ?></label>
                        <?php
                        echo $html;
                        if( !empty($field['desc']) ) {
                            ?>
                            <p class="description"><?php echo wp_kses_post($field['desc']); ?></p>
                            <?php
                        }
                        ?>
                    </div>
                    <?php
                }
                else {
                    // prepare output
                    ?>
                        <tr data-lsw-dependency="<?php echo esc_attr($depends); ?>" class="form-field <?php echo esc_attr($fieldId) ?> <?php echo empty($field['required']) ? '' : 'form-required' ?>">
                            <th scope="row"><label
                                for="<?php echo esc_attr($fieldId) ?>"><?php echo esc_html($field['label']); ?></label></th>
                            <td>
                                <?php
                                echo $html;
                                if( !empty($field['desc']) ) {
                                    ?>
                                        <p class="description"><?php echo wp_kses_post($field['desc']); ?></p>
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
    public function getTaxonomyName(): string
    {
        return wc_attribute_taxonomy_name($this->taxonomy->attribute_name);
    }

    /**
     * Generate the name of a field in backend from given field-Id.
     *
     * @param $fieldId
     * @return string
     */
    private function getFieldName($fieldId): string
    {
        return "lws".$fieldId;
    }
}