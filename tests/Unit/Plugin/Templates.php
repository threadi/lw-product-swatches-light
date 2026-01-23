<?php
/**
 * Tests for class ProductSwatchesLight\Plugin\Templates.
 *
 * @package product-swatches-light
 */

namespace ProductSwatchesLight\Tests\Unit\Plugin;

use ProductSwatchesLight\Tests\SwatchesTestCase;

/**
 * Object to test functions in class ProductSwatchesLight\Plugin\Templates.
 */
class Templates extends SwatchesTestCase {
	/**
	 *
	 *
	 * @return void
	 */
	public function test_get_html_list(): void {
		// set the test variables.
		$test_html = 'Hallo Welt';

		// test it.
		$html = \ProductSwatchesLight\Plugin\Templates::get_instance()->get_html_list( $test_html, 'colors', 'color', 'test-taxonomy', false );
		$this->assertIsString( $html );
		$this->assertNotEmpty( $html );
		$this->assertStringContainsString( $test_html, $html );
	}
}
