/**
 * JavaScript para Dashboard Mis Viajes
 * Fullday Users Plugin
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initNavigationButtons();
        initToggleStatus();
        initDeleteButton();
        initEditButton();
        initAvailabilityControl();
    });

    /**
     * Inicializar botones de navegación
     */
    function initNavigationButtons() {
        // Botón "Nuevo Full Day"
        $('#btn-nuevo-fullday, #btn-crear-primer-fullday').on('click', function() {
            $('.dashboard-tab[data-tab="crear"]').click();
        });
    }

    /**
     * Inicializar toggle de estado activo/pausado
     */
    function initToggleStatus() {
        $(document).on('click', '.card-status-badge', function() {
            const $badge = $(this);
            const postId = $badge.data('post-id');
            const currentStatus = $badge.data('status');
            const newStatus = currentStatus === 'publish' ? 'draft' : 'publish';
            const $card = $badge.closest('.fullday-card');
            const $verButton = $card.find('.btn-ver');

            // Deshabilitar botón mientras procesa
            $badge.prop('disabled', true);

            $.ajax({
                url: fulldayUsers.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fullday_toggle_status',
                    nonce: fulldayUsers.nonce,
                    post_id: postId,
                    new_status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        // Actualizar badge
                        $badge.data('status', newStatus);

                        if (newStatus === 'publish') {
                            // Activar
                            $badge.removeClass('paused').addClass('active');
                            $badge.text('Activo');

                            // Habilitar botón Ver y actualizar URL
                            const slug = $verButton.data('slug');
                            const fullUrl = response.data.post_url || window.location.origin + '/full-days/' + slug;

                            $verButton.removeClass('disabled');
                            $verButton.attr('href', fullUrl);
                            $verButton.attr('target', '_blank');
                            $verButton.attr('onclick', '');

                            showMessage('Full Day activado correctamente', 'success');
                        } else {
                            // Pausar
                            $badge.removeClass('active').addClass('paused');
                            $badge.text('Pausado');

                            // Deshabilitar botón Ver
                            $verButton.addClass('disabled');
                            $verButton.attr('href', '#');
                            $verButton.removeAttr('target');
                            $verButton.attr('onclick', 'return false;');

                            showMessage('Full Day pausado correctamente', 'success');
                        }
                    } else {
                        showMessage(response.data.message || 'Error al cambiar el estado', 'error');
                    }
                },
                error: function() {
                    showMessage('Error de conexión. Intenta nuevamente.', 'error');
                },
                complete: function() {
                    $badge.prop('disabled', false);
                }
            });
        });
    }

    /**
     * Inicializar botón de eliminar
     */
    function initDeleteButton() {
        $(document).on('click', '.btn-eliminar', function() {
            const postId = $(this).data('post-id');
            const $card = $(this).closest('.fullday-card');
            const title = $card.find('.card-title').text();

            // Confirmar eliminación
            if (!confirm('¿Estás seguro de eliminar "' + title + '"?\n\nEsta acción no se puede deshacer.')) {
                return;
            }

            // Deshabilitar botón
            $(this).prop('disabled', true).text('Eliminando...');

            $.ajax({
                url: fulldayUsers.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fullday_delete_fullday',
                    nonce: fulldayUsers.nonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        // Animar y remover card
                        $card.fadeOut(400, function() {
                            $(this).remove();

                            // Si no quedan más cards, mostrar empty state
                            if ($('.fullday-card').length === 0) {
                                location.reload();
                            }
                        });

                        showMessage('Full Day eliminado correctamente', 'success');
                    } else {
                        showMessage(response.data.message || 'Error al eliminar el Full Day', 'error');
                        $(this).prop('disabled', false).text('Eliminar');
                    }
                },
                error: function() {
                    showMessage('Error de conexión. Intenta nuevamente.', 'error');
                    $(this).prop('disabled', false).text('Eliminar');
                }
            });
        });
    }

    /**
     * Inicializar botón de editar
     */
    function initEditButton() {
        $(document).on('click', '.btn-editar', function() {
            const postId = $(this).data('post-id');

            // Cambiar a tab de editar con post_id
            const url = new URL(window.location.href);
            url.searchParams.set('tab', 'editar');
            url.searchParams.set('post_id', postId);
            window.location.href = url.toString();
        });
    }

    /**
     * Inicializar control de disponibilidad
     */
    function initAvailabilityControl() {
        console.log('Availability control initialized');

        // Botón de aumentar
        $(document).on('click', '.btn-availability.btn-increase', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Increase button clicked');

            const $input = $(this).siblings('.availability-input');
            const currentValue = parseInt($input.val()) || 0;
            const maxValue = parseInt($input.data('max')) || 0;

            console.log('Current:', currentValue, 'Max:', maxValue);

            if (currentValue < maxValue) {
                updateAvailability($input, currentValue + 1);
            }

            return false;
        });

        // Botón de disminuir
        $(document).on('click', '.btn-availability.btn-decrease', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Decrease button clicked');

            const $input = $(this).siblings('.availability-input');
            const currentValue = parseInt($input.val()) || 0;

            console.log('Current:', currentValue);

            if (currentValue > 0) {
                updateAvailability($input, currentValue - 1);
            }

            return false;
        });
    }

    /**
     * Actualizar disponibilidad vía AJAX
     */
    function updateAvailability($input, newValue) {
        const postId = $input.data('post-id');
        const $control = $input.closest('.availability-control');
        const $buttons = $control.find('.btn-availability');
        const oldValue = $input.val();

        console.log('=== UPDATE AVAILABILITY ===');
        console.log('Post ID:', postId);
        console.log('Old Value:', oldValue);
        console.log('New Value:', newValue);
        console.log('AJAX URL:', fulldayUsers.ajaxUrl);
        console.log('Nonce:', fulldayUsers.nonce);

        // Deshabilitar botones mientras procesa
        $buttons.prop('disabled', true);

        // Mostrar loader en el input
        $input.addClass('loading');
        $input.val('...');

        $.ajax({
            url: fulldayUsers.ajaxUrl,
            type: 'POST',
            data: {
                action: 'fullday_update_availability',
                nonce: fulldayUsers.nonce,
                post_id: postId,
                available_spots: newValue
            },
            success: function(response) {
                console.log('Response TYPE:', typeof response);
                console.log('Response:', response);

                if (response && response.success) {
                    // Actualizar valor del input
                    $input.val(newValue);
                } else {
                    // Restaurar valor anterior
                    $input.val(oldValue);

                    const errorMessage = (response && response.data && response.data.message)
                        ? response.data.message
                        : 'Error al actualizar disponibilidad';
                    showMessage(errorMessage, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);

                // Restaurar valor anterior
                $input.val(oldValue);
                showMessage('Error de conexión. Intenta nuevamente.', 'error');
            },
            complete: function() {
                $buttons.prop('disabled', false);
                $input.removeClass('loading');
            }
        });
    }

    /**
     * Mostrar mensaje
     */
    function showMessage(message, type) {
        const $messageDiv = $('#mis-viajes-message');
        $messageDiv
            .removeClass('success error')
            .addClass(type)
            .text(message)
            .fadeIn();

        setTimeout(function() {
            $messageDiv.fadeOut();
        }, 5000);

        // Scroll al mensaje
        $('html, body').animate({
            scrollTop: 0
        }, 400);
    }

})(jQuery);
