/**
 * JavaScript para gestión de Favoritos
 * Fullday Users Plugin
 */

jQuery(document).ready(function($) {
    'use strict';

    /**
     * Toggle favorito
     */
    $(document).on('click', '.fullday-favorite-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const $btn = $(this);
        const postId = $btn.data('post-id');
        const nonce = $btn.data('nonce');

        // Verificar que hay post ID
        if (!postId) {
            console.error('No se encontró el ID del post');
            return;
        }

        // Deshabilitar botón mientras se procesa
        $btn.prop('disabled', true);

        // Hacer request AJAX
        $.ajax({
            url: fulldayUsers.ajaxUrl,
            type: 'POST',
            data: {
                action: 'toggle_favorite',
                post_id: postId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // Actualizar estado del botón
                    if (response.data.is_favorite) {
                        $btn.addClass('is-favorite');
                        $btn.attr('aria-label', 'Eliminar de favoritos');
                        $btn.attr('title', 'Eliminar de favoritos');
                    } else {
                        $btn.removeClass('is-favorite');
                        $btn.attr('aria-label', 'Agregar a favoritos');
                        $btn.attr('title', 'Agregar a favoritos');

                        // Si estamos en la página de favoritos, remover la tarjeta con animación
                        if ($('.favoritos-container').length) {
                            const $card = $btn.closest('.favorito-card');
                            $card.fadeOut(300, function() {
                                $(this).remove();

                                // Si ya no hay más tarjetas, mostrar estado vacío
                                if ($('.favorito-card').length === 0) {
                                    showEmptyState();
                                }
                            });
                        }
                    }

                    // Mostrar mensaje de éxito
                    showMessage(response.data.message, 'success');
                } else {
                    showMessage(response.data.message || 'Error al actualizar favoritos', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX:', error);
                showMessage('Error de conexión. Intenta nuevamente.', 'error');
            },
            complete: function() {
                // Rehabilitar botón
                $btn.prop('disabled', false);
            }
        });
    });

    /**
     * Mostrar mensaje
     */
    function showMessage(message, type) {
        // Buscar contenedor de mensajes en favoritos o crear uno genérico
        let $messageContainer = $('#favoritos-message');

        if (!$messageContainer.length) {
            // Si no existe, buscar uno genérico o crearlo temporalmente
            $messageContainer = $('.form-message').first();

            if (!$messageContainer.length) {
                // Crear mensaje flotante temporal
                $messageContainer = $('<div class="fullday-toast-message"></div>');
                $('body').append($messageContainer);
            }
        }

        $messageContainer
            .removeClass('success error')
            .addClass(type)
            .text(message)
            .fadeIn(300);

        setTimeout(function() {
            $messageContainer.fadeOut(300, function() {
                if ($(this).hasClass('fullday-toast-message')) {
                    $(this).remove();
                }
            });
        }, 3000);
    }

    /**
     * Mostrar estado vacío en favoritos
     */
    function showEmptyState() {
        const emptyStateHTML = `
            <div class="empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
                <h3>No tienes favoritos guardados</h3>
                <p>Explora nuestras experiencias y guarda tus favoritas para verlas más tarde</p>
                <a href="${fulldayUsers.homeUrl}/full-days" class="btn-primary">
                    Explorar Experiencias
                </a>
            </div>
        `;

        $('.favoritos-grid').fadeOut(300, function() {
            $(this).replaceWith(emptyStateHTML);
            $('.empty-state').hide().fadeIn(300);
        });
    }
});
