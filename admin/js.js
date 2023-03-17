jQuery( document ).ready( function ($) {
    // create update-flyout with progressbar
    $('a.lw-update-swatches').on('click', function (e) {
        e.preventDefault();

        // create dialog if it does not exist atm
        let dialogEl = $('#lw-update-dialog');
        if( dialogEl.length === 0 ) {
            $('<div id="lw-update-dialog" title="' + lwProductSwatchesVars.label_update_is_running + '"><div id="lw-update-description"></div><div id="lwSwatchesProgressBar"></div></div>').dialog({
                width: 500,
                closeOnEscape: false,
                dialogClass: "lw-update-close",
                resizable: false,
                modal: true,
                draggable: false,
                buttons: [
                    {
                        text: lwProductSwatchesVars.label_ok,
                        click: function () {
                            location.reload();
                        }
                    }
                ]
            });
        }
        else {
            dialogEl.dialog('open');
        }

        // disable button in dialog
        $('.lw-update-close .ui-button').prop('disabled', true);

        // init description
        let stepDescription = $('#lw-update-description');
        stepDescription.html('<p>' + lwProductSwatchesVars.txt_please_wait + '</p>');

        // init progressbar
        let progressbar = jQuery("#lwSwatchesProgressBar");
        progressbar.progressbar({
            value: 0
        }).removeClass("hidden");

        // start update
        $.ajax({
            type: "POST",
            url: lwProductSwatchesVars.ajax_url,
            data: {
                'action': 'lw_swatches_import_run',
                'nonce': lwProductSwatchesVars.run_update_nonce
            },
            beforeSend: function() {
                // get update-infos
                setTimeout(function() { lw_swatches_get_update_info(progressbar, stepDescription); }, 1000);
            }
        });
    });
});

/**
 * Get import info until updates are done.
 *
 * @param progressbar
 * @param stepDescription
 */
function lw_swatches_get_update_info(progressbar, stepDescription) {
    jQuery.ajax({
        type: "POST",
        url: lwProductSwatchesVars.ajax_url,
        data: {
            'action': 'lw_swatches_import_info',
            'nonce': lwProductSwatchesVars.get_update_nonce
        },
        success: function(data) {
            let stepData = data.split(";");
            let count = parseInt(stepData[0]);
            let max = parseInt(stepData[1]);
            let running = parseInt(stepData[2]);

            // update progressbar
            progressbar.progressbar({
                value: (count/max)*100
            });

            // get next info until running is not 1
            if( running === 1 ) {
                setTimeout(function() { lw_swatches_get_update_info(progressbar, stepDescription) }, 500);
            }
            else {
                progressbar.addClass("hidden");
                stepDescription.html(lwProductSwatchesVars.txt_update_has_been_run);
                jQuery('.lw-update-close .ui-button').prop('disabled', false);
            }
        }
    })
}
