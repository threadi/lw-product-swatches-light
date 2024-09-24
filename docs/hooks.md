# Hooks

- [Actions](#actions)
- [Filters](#filters)

## Actions

### `product_swatches_light_option_list`

*Extend output of attributes in product-detail-edit-page with the additional types which are used by this plugin.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attribute_taxonomy` | `\stdClass` | The taxonomy object.
`$all_terms` |  | 
`$product_id` |  | 

Source: [app/Swatches/WooCommerce.php](Swatches/WooCommerce.php), [line 326](Swatches/WooCommerce.php#L326-L367)

## Filters

### `product_swatches_light_get_transients_for_display`

*Filter the transients used and managed by this plugin.*

Hint: with help of this hook you could hide all transients this plugin is using. Simple return an empty array.

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$transients` | `array` | List of transients.

**Changelog**

Version | Description
------- | -----------
`2.0.0` | Available since 2.0.0.

Source: [app/Plugin/Transients.php](Plugin/Transients.php), [line 191](Plugin/Transients.php#L191-L200)

### `product_swatches_light_schedule_interval`

*Filter the interval for a single schedule.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$interval` | `string` | The interval.
`$this` | `\ProductSwatchesLight\Plugin\Schedules_Base` | The schedule-object.

**Changelog**

Version | Description
------- | -----------
`2.0.0` | Available since 2.0.0.

Source: [app/Plugin/Schedules_Base.php](Plugin/Schedules_Base.php), [line 68](Plugin/Schedules_Base.php#L68-L75)

### `product_swatches_light_schedule_enabling`

*Filter whether to activate this schedule.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$false` | `bool` | True if this schedule should NOT be enabled.
`$this` | `\ProductSwatchesLight\Plugin\Schedules_Base` | Actual object.

**Changelog**

Version | Description
------- | -----------
`2.0.0` | Available since 2.0.0.

Source: [app/Plugin/Schedules_Base.php](Plugin/Schedules_Base.php), [line 180](Plugin/Schedules_Base.php#L180-L190)

### `product_swatches_light_transient_hide_on`

*Filter where a single transient should be hidden.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$hide_on` | `array` | List of absolute URLs.
`$this` | `\ProductSwatchesLight\Plugin\Transient` | The actual transient object.

**Changelog**

Version | Description
------- | -----------
`2.0.0` | Available since 2.0.0.

Source: [app/Plugin/Transient.php](Plugin/Transient.php), [line 366](Plugin/Transient.php#L366-L374)

### `product_swatches_light_set_template_directory`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`LW_SWATCHES_PLUGIN` |  | 

Source: [app/Plugin/Templates.php](Plugin/Templates.php), [line 96](Plugin/Templates.php#L96-L96)

### `product_swatches_light_file_version`

*Filter the used file version (for JS- and CSS-files which get enqueued).*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$plugin_version` | `string` | The plugin-version.
`$filepath` | `string` | The absolute path to the requested file.

**Changelog**

Version | Description
------- | -----------
`2.0.0` | Available since 2.0.0.

Source: [app/Plugin/Helper.php](Plugin/Helper.php), [line 179](Plugin/Helper.php#L179-L187)

### `product_swatches_light_schedule_our_events`

*Filter the list of our own events,
e.g. to check if all which are enabled in setting are active.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$our_events` | `array` | List of our own events in WP-cron.

**Changelog**

Version | Description
------- | -----------
`2.0.0` | Available since 2.0.0.

Source: [app/Plugin/Schedules.php](Plugin/Schedules.php), [line 85](Plugin/Schedules.php#L85-L93)

### `product_swatches_light_disable_cron_check`

*Disable the additional cron check.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$false` | `bool` | True if check should be disabled.

**Changelog**

Version | Description
------- | -----------
`2.0.0` | Available since 2.0.0.

Source: [app/Plugin/Schedules.php](Plugin/Schedules.php), [line 110](Plugin/Schedules.php#L110-L118)

### `product_swatches_light_schedules`

*Add custom schedule-objects to use.*

This must be objects based on ProductSwatchesLight\Plugin\Schedules_Base.

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$list_of_schedules` | `array` | List of additional schedules.

**Changelog**

Version | Description
------- | -----------
`2.0.0` | Available since 2.0.0.

Source: [app/Plugin/Schedules.php](Plugin/Schedules.php), [line 189](Plugin/Schedules.php#L189-L198)

### `product_swatches_light_change_attribute_type_name`

*Show swatch content in listings.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$attribute_taxonomy->attribute_type` |  | 

Source: [app/Swatches/WooCommerce.php](Swatches/WooCommerce.php), [line 415](Swatches/WooCommerce.php#L415-L426)

### `product_swatches_light_set_link`

*Filter the link.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`''` |  | 
`$taxonomy_id` | `int` | The taxonomy ID.
`$color` | `string` | The used color.
`$product_link` | `string` | The product URL.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Swatches/AttributeType/Color.php](Swatches/AttributeType/Color.php), [line 78](Swatches/AttributeType/Color.php#L78-L88)

### `product_swatches_light_change_attribute_type_name`

*Filter the used attribute type.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$term_name` | `string` | The type name.

**Changelog**

Version | Description
------- | -----------
`1.0.0` | Available since 1.0.0.

Source: [app/Swatches/Product.php](Swatches/Product.php), [line 127](Swatches/Product.php#L127-L133)

### `product_swatches_light_get_attribute_values`

*Generate the swatches codes for this specific product.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`array()` |  | 
`$attribute_type` |  | 
`$term` |  | 
`$term_name` |  | 

Source: [app/Swatches/Product.php](Swatches/Product.php), [line 46](Swatches/Product.php#L46-L151)

### `product_swatches_light_get_list`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`''` |  | 
`$attribute_type` |  | 
`$resulting_list` |  | 
`$images` |  | 
`$images_sets` |  | 
`$values` |  | 
`$on_sales` |  | 
`$this->get_permalink()` |  | 
`$this->get_title()` |  | 

Source: [app/Swatches/Product.php](Swatches/Product.php), [line 177](Swatches/Product.php#L177-L177)

### `product_swatches_light_change_attribute_type_name`

*Add the content of this column for this attribute in the backend-tables.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$this->taxonomy->attribute_type` |  | 

Source: [app/Swatches/Attribute.php](Swatches/Attribute.php), [line 102](Swatches/Attribute.php#L102-L112)

### `product_swatches_light_change_attribute_type_name`

*Save individual settings for a term in backend.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$field['type']` |  | 

Source: [app/Swatches/Attribute.php](Swatches/Attribute.php), [line 139](Swatches/Attribute.php#L139-L195)

### `product_swatches_light_secure_term_value`

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`sanitize_text_field(wp_unslash($_POST[$field_name]))` |  | 
`$field` |  | 

Source: [app/Swatches/Attribute.php](Swatches/Attribute.php), [line 203](Swatches/Attribute.php#L203-L203)

### `product_swatches_light_change_attribute_type_name`

*Add fields for the form in backend for this taxonomy.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`$field['type']` |  | 

Source: [app/Swatches/Attribute.php](Swatches/Attribute.php), [line 214](Swatches/Attribute.php#L214-L246)

### `product_swatches_light_get_term_edit_field`

*Add fields for the form in backend for this taxonomy.*

**Arguments**

Argument | Type | Description
-------- | ---- | -----------
`''` |  | 
`$field` |  | 
`$field_id` |  | 
`$value` |  | 
`$required` |  | 
`$placeholder` |  | 

Source: [app/Swatches/Attribute.php](Swatches/Attribute.php), [line 214](Swatches/Attribute.php#L214-L249)


<p align="center"><a href="https://github.com/pronamic/wp-documentor"><img src="https://cdn.jsdelivr.net/gh/pronamic/wp-documentor@main/logos/pronamic-wp-documentor.svgo-min.svg" alt="Pronamic WordPress Documentor" width="32" height="32"></a><br><em>Generated by <a href="https://github.com/pronamic/wp-documentor">Pronamic WordPress Documentor</a> <code>1.2.0</code></em><p>

