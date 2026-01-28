/**
 * JavaScript para Admin de IA
 * Fullday Users Plugin
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        let mediaUploader;

        // Upload Avatar
        $('#fullday_upload_avatar_button').on('click', function(e) {
            e.preventDefault();

            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media({
                title: 'Seleccionar Avatar de Fully',
                button: {
                    text: 'Usar esta imagen'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();

                $('#fullday_ai_avatar').val(attachment.id);
                $('#fullday_avatar_preview').html('<img src="' + attachment.url + '" style="max-width: 150px; border-radius: 8px;">');
                $('#fullday_upload_avatar_button').text('Cambiar Avatar');
                $('#fullday_remove_avatar_button').show();
            });

            mediaUploader.open();
        });

        // Remove Avatar
        $('#fullday_remove_avatar_button').on('click', function(e) {
            e.preventDefault();

            $('#fullday_ai_avatar').val('');
            $('#fullday_avatar_preview').html('');
            $('#fullday_upload_avatar_button').text('Subir Avatar de Fully');
            $(this).hide();
        });
    });

})(jQuery);
