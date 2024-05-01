<?php
/**
 * File to handle main swatches attribute functions.
 *
 * @package product-swatches-light
 */

namespace LW_Swatches;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The object which handles main swatches attribute functions.
 */
class Swatches {
	/**
	 * Set the type name for singular.
	 *
	 * @var string
	 */
	protected string $type_name = '';

	/**
	 * Set the type name for plural.
	 *
	 * @var string
	 */
	protected string $type_names = '';

	/**
	 * Return single type name.
	 *
	 * @return string
	 */
	public function get_type_name(): string {
		return $this->type_name;
	}

	/**
	 * Return single type names.
	 *
	 * @return string
	 */
	public function get_type_names(): string {
		return $this->type_names;
	}

	/**
	 * Get variant-image as data-attribute
	 *
	 * @param array  $images List of images.
	 * @param array  $images_sets List of image sets.
	 * @param string $slug The used slug.
	 * @return array
	 */
	public function get_variant_thumb_as_data( array $images, array $images_sets, string $slug ): array {
		// bail if no image set.
		if ( empty( $images[ $slug ] ) ) {
			return array();
		}

		// return image data.
		return array(
			'image'  => $images[ $slug ],
			'srcset' => $images_sets[ $slug ],
		);
	}
}
