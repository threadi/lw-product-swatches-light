<?php
/**
 * File for autoloader of this plugin.
 *
 * @package product-swatches-light
 */

/**
 * Add autoloader for each php-class in this plugin.
 */
spl_autoload_register( 'lw_swatches_autoloader' );

/**
 * Handler for Autoloader.
 *
 * @param string $class_name The requested class-name.
 *
 * @return void
 */
function lw_swatches_autoloader( string $class_name ): void {

	// If the specified $class_name does not include our namespace, duck out.
	if ( ! str_contains( $class_name, 'LW_Swatches' ) ) {
		return;
	}

	// Split the class name into an array to read the namespace and class.
	$file_parts = explode( '\\', $class_name );

	// Do a reverse loop through $file_parts to build the path to the file.
	$namespace        = '';
	$filepath         = '';
	$file_parts_count = count( $file_parts );
	$file_names       = array();
	for ( $i = 1; $i < $file_parts_count; $i++ ) {
		// Read the current component of the file part.
		$current = strtolower( $file_parts[ $i ] );
		$current = str_ireplace( '_', '-', $current );

		// If we're at the first entry, then we're at the filename.
		if ( $file_parts_count - 1 === $i ) {
			$file_names[] = 'class-' . $current . '.php';
			$file_names[] = 'interface-' . $current . '.php';
		} else {
			// otherwise we are at a preceding folder.
			$namespace = '/' . $current . $namespace;
		}
	}

	// If the file exists in the specified path, then include it.
	$file_names_count = count( $file_names );
	for ( $f = 0; $f < $file_names_count; $f++ ) {
		// Now build a path to the file using mapping to the file location.
		$filepath  = trailingslashit( dirname( __DIR__, 1 ) . '/classes/' . $namespace );
		$filepath .= $file_names[ $f ];
		if ( file_exists( $filepath ) ) {
			include_once $filepath;
		}
	}
}
