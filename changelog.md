# Changelog

## [Unreleased]

### Added

- Added GitHub action to build releases
- Added an object handler for transients for each message in the backend
- Added generation of SBOM
- Added PHP Compatibility check
- Added PHP Unit tests
- Added PCP Check in GitHub action for each release

### Changed

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

### Fixed

- Setting the default position for swatches resulted in an error

## [2.0.3] - 30.11.2024

### Fixed

- Fixed some typos

## [2.0.2] - 24.09.2024

### Fixed

- Fixed plugin version number

## [2.0.1] - 24.09.2024

### Fixed

- Fixed files in SVN

## [2.0.0] - 19.09.2024

### Changed

- Complete rewritten plugin
- Using composer
- Compatible with WordPress Coding Standards
- Compatible with modern WooCommerce 9.x
- New text domain according the WordPress rules for plugin: product-swatches-light (old: lw-product-swatches)
- Added generating of hook documentation
- Now also available in every german language

## [1.0.4] - 26.10.2023

### Fixed

- Fixed missing translation files

## [1.0.3] - 26.10.2023

### Changed

- Changed text domain to match WordPress requirements
- Compatibility with WordPress 6.4

### Fixed

- Fixed uninstaller

## [1.0.2] - 11.07.2023

### Fixed

- Fixed SVN issue

## [1.0.1] - 29.03.2023

### Added

- Add filter for HTML-codes used in backend

### Changed

- Updated styling for TwentyTwentyThree theme
- Compatibility with WordPress 6.3

## [1.0.0] - 19.01.2023

### Added

- Initial release
