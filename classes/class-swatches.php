<?php
/**
 * File to handle main swatches attribute functions.
 *
 * @package product-swatches-light
 */

namespace LW_Swatches;

/**
 * The object which handles main swatches attribute functions.
 */
class swatches {

	/**
	 * Get variant-image as data-attribute
	 *
	 * @param $images
	 * @param $imagesSets
	 * @param $slug
	 * @return array
	 */
	public function getVariantThumbAsData( $images, $imagesSets, $slug ): array {
		// bail if no image set.
		if( empty($images[$slug]) ) {
			return array();
		}

		// return image data.
		return array(
			'image' => $images[$slug],
			'srcset' => $imagesSets[$slug]
		);
	}

}
