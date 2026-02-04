=== Product Swatches Light ===
Contributors: laolaweb, threadi
Tags: woocommerce, product swatches, variant swatches, variation swatches
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Stable tag: @@VersionNumber@@

Capture your customers by displaying your product variations beautifully.

== Description ==

Capture your customers by displaying your product variations beautifully. Performant output of properties in the archive view, no matter how many products and variations you have.

#### Features

- show swatches on product archives with respect the ordering
- default activated caching of swatches to optimize the loading-time of your shop
- choose color as attribute-type for your attributes and set one of 12 colors for your attributes
- mouseover-event to change product-images and sales-badge on archive-listing to the touched color
- update the swatches-cache manual on product or every hour automatically if something on a product has been changed
- automatically updates the swatches-cache of single products if their stock changes
- product-thumbs could be changed on hover
- provides some WP CLI commands to delete or update swatch-caches
- migration of color-swatches from plugin "Variation Swatches for WooCommerce" from RadiusTheme

The development repository is on [GitHub](https://github.com/threadi/lw-product-swatches-light).

#### Requirements

- installed and activated WooCommerce-plugin

#### Hint

With [Product Swatches for WooCommerce Pro](https://laolaweb.com/plugins/woocommerce-varianten-plugin/) you will get unlimited colors, more swatch types, swatches on the product detail and much more.

#### the Pro license includes:

- more swatch types like button, two-color, image, multicolor (max. 4 different colors)
- extends support for color-type for optional one or two colors on the same attribute
- support for transparent colors on every color-type
- swatches can optional displayed in frontend on: product detail, cart, checkout
- decide which attribute-type should be visible as swatches on archive or detail
- also decide to use product-thumbs on hover or click or not
- and also in backend if you edit your products
- decide if sales-badge should be visible or not on archive listing
- change the update interval of your swatches
- define which stock status should be considered for the output of swatches

---

== Installation ==

1. Upload "product-swatches-light" to the "/wp-content/plugins/" directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Configure your product attributes under Products > Attributes. Check the [Quickstart](https://github.com/threadi/lw-product-swatches-light/tree/master/docs/quickstart.md).

== Frequently Asked Questions ==

== Screenshots ==

1. Possible list view in frontend.
2. Settings on single color-attribute.
3. Listing of color-attributes in backend.
4. Settings for single color-attribute in backend.
5. General settings with Pro-version.
6. Detail-view of product with Pro-Version.

== Changelog ==

= @@VersionNumber@@ =

- Added GitHub action to build releases
- Added an object handler for transients for each message in the backend
- Added generation of SBOM
- Added PHP Compatibility check
- Added PHP Unit tests
- Added PCP Check in GitHub action for each release
- Now requires PHP 8.2 or newer
- Moved this changelog to GitHub
- Changed the dialog script to "easy-dialog-for-wordpress"
- Renamed hook "lw_swatches_types" to "product_swatches_light_swatches_types"
- Renamed hook "wc_lw_product_swatches_settings" to "product_swatches_light_product_swatches_settings"
- Renamed hook "lw_swatches_product_stockstatus" to "product_swatches_light_product_stockstatus"
- Renamed hook "lw_swatches_hide_attribute" to "product_swatches_light_hide_attribute"
- Renamed hook "lw_swatches_allowed_html" to "product_swatches_light_allowed_html"
- Renamed hook "lw_swatches_admin_init" to "product_swatches_light_admin_init"
- Optimized all templates to prevent direct access
- Now also compatible with PHPStan, PHP 8.2 to 8.5 and PCP
- Setting the default position for swatches resulted in an error

[older changes](https://github.com/threadi/lw-product-swatches-light/blob/master/changelog.md)
