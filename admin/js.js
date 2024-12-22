/**
 * Initiate the update progress for all swatches.
 */
function product_swatches_regenerate() {
	// start import.
	jQuery.ajax({
		url: productSwatchesLightJsVars.rest_update_product_swatches,
		type: 'POST',
		dataType: 'json',
		contentType: false,
		processData: false,
		beforeSend: function( xhr ) {
			// set header for authentication.
			xhr.setRequestHeader('X-WP-Nonce', productSwatchesLightJsVars.rest_nonce);

			// show progress.
			let dialog_config = {
				detail: {
					title: productSwatchesLightJsVars.title_update_progress,
					progressbar: {
						active: true,
						progress: 0,
						id: 'progress',
						label_id: 'progress_status'
					},
				}
			}
			product_swatches_create_dialog( dialog_config );

			// get info about progress.
			setTimeout(function() { product_swatches_get_update_info() }, 1000);
		}
	});
}

/**
 * Get info about update progress.
 */
function product_swatches_get_update_info() {
	jQuery.ajax( {
		url: productSwatchesLightJsVars.rest_update_product_swatches,
		type: 'GET',
		dataType: 'json',
		contentType: false,
		processData: false,
		beforeSend: function( xhr ) {
			// set header for authentication.
			xhr.setRequestHeader('X-WP-Nonce', productSwatchesLightJsVars.rest_nonce);
		},
		success: function (data) {
			let count = parseInt( data[0] );
			let max = parseInt( data[1] );
			let running = parseInt( data[2] );
			let status = data[3];

			// show progress.
			jQuery( '#progress' ).attr( 'value', (count / max) * 100 );
			jQuery( '#progress_status' ).html( status );

			/**
			 * If import is still running, get next info in 500ms.
			 * If import is not running and error occurred, show the error.
			 * If import is not running and no error occurred, show ok-message.
			 */
			if ( running >= 1 ) {
				setTimeout( function () {
					product_swatches_get_update_info()
				}, 500 );
			} else {
				let dialog_config = {
					detail: {
						title: productSwatchesLightJsVars.title_update_success,
						texts: [
							'<p>' + productSwatchesLightJsVars.txt_update_success + '</p>'
						],
						buttons: [
							{
								'action': 'location.reload();',
								'variant': 'primary',
								'text': productSwatchesLightJsVars.lbl_ok
							}
						]
					}
				}
				product_swatches_create_dialog( dialog_config );
			}
		}
	} )
}

/**
 * Helper to create a new dialog with given config.
 *
 * @param config
 */
function product_swatches_create_dialog( config ) {
	document.body.dispatchEvent(new CustomEvent("easy-dialog-for-wordpress", config));
}
