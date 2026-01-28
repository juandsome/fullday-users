/**
 * JavaScript para Chat con IA
 * Fullday Users Plugin
 */

(function($) {
    'use strict';

    // Variables globales
    let conversationHistory = [];
    let isProcessing = false;
    let attachedImages = []; // Array para almacenar imágenes adjuntas actualmente

    $(document).ready(function() {
        // Solo ejecutar si estamos en la pestaña de IA
        if ($('#ai-chat-messages').length === 0) {
            return;
        }

        // Event listeners
        $('#ai-send-message').on('click', sendMessage);
        $('#ai-chat-input').on('keydown', handleKeyPress);
        $('#ai-clear-chat').on('click', clearChat);
        $('#ai-save-fullday').on('click', saveFullDay);
        $('#ai-attach-image').on('click', openImageSelector);
        $('#ai-image-input').on('change', handleImageSelection);

        // Habilitar botón de guardar después de al menos 2 intercambios
        updateSaveButton();
    });

    /**
     * Manejar tecla Enter
     */
    function handleKeyPress(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    }

    /**
     * Abrir selector de imágenes
     */
    function openImageSelector() {
        $('#ai-image-input').click();
    }

    /**
     * Manejar selección de imágenes
     */
    function handleImageSelection(e) {
        const files = e.target.files;
        
        if (files.length === 0) {
            return;
        }

        // Limitar a 5 imágenes
        if (attachedImages.length + files.length > 5) {
            alert('Puedes adjuntar un máximo de 5 imágenes por mensaje');
            return;
        }

        // Procesar cada archivo
        Array.from(files).forEach(file => {
            // Validar que sea imagen
            if (!file.type.startsWith('image/')) {
                return;
            }

            // Validar tamaño (máximo 5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('Las imágenes deben ser menores a 5MB');
                return;
            }

            // Leer archivo como base64
            const reader = new FileReader();
            reader.onload = function(event) {
                const imageData = {
                    data: event.target.result,
                    name: file.name,
                    type: file.type
                };

                attachedImages.push(imageData);
                displayImagePreview(imageData, attachedImages.length - 1);
            };
            reader.readAsDataURL(file);
        });

        // Limpiar input
        e.target.value = '';
    }

    /**
     * Mostrar preview de imagen
     */
    function displayImagePreview(imageData, index) {
        const previewHtml = `
            <div class="image-preview" data-index="${index}">
                <img src="${imageData.data}" alt="${escapeHtml(imageData.name)}">
                <button type="button" class="remove-image" data-index="${index}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        `;

        $('#ai-image-preview-container').append(previewHtml).show();

        // Event listener para eliminar imagen
        $(`.remove-image[data-index="${index}"]`).on('click', function() {
            removeImage($(this).data('index'));
        });
    }

    /**
     * Eliminar imagen adjunta
     */
    function removeImage(index) {
        // Eliminar del array
        attachedImages.splice(index, 1);

        // Eliminar preview
        $(`.image-preview[data-index="${index}"]`).remove();

        // Actualizar índices
        $('.image-preview').each(function(i) {
            $(this).attr('data-index', i);
            $(this).find('.remove-image').attr('data-index', i);
        });

        // Ocultar contenedor si no hay imágenes
        if (attachedImages.length === 0) {
            $('#ai-image-preview-container').hide();
        }
    }

    /**
     * Enviar mensaje a la IA
     */
    function sendMessage() {
        if (isProcessing) {
            return;
        }

        const input = $('#ai-chat-input');
        const message = input.val().trim();

        // Verificar que haya mensaje o imágenes
        if (!message && attachedImages.length === 0) {
            return;
        }

        // Preparar contenido del mensaje (texto + imágenes)
        const messageContent = {
            text: message,
            images: attachedImages.length > 0 ? [...attachedImages] : null
        };

        // Agregar mensaje del usuario al historial
        conversationHistory.push({
            role: 'user',
            content: messageContent
        });

        // Mostrar mensaje del usuario
        appendUserMessage(message, attachedImages.length > 0 ? [...attachedImages] : null);

        // Limpiar input e imágenes
        input.val('');
        const imagesToSend = [...attachedImages];
        attachedImages = [];
        $('#ai-image-preview-container').empty().hide();

        // Deshabilitar input y botón
        isProcessing = true;
        input.prop('disabled', true);
        $('#ai-send-message').prop('disabled', true);
        $('#ai-attach-image').prop('disabled', true);

        // Mostrar indicador de escritura
        showTypingIndicator();

        // Determinar URL de AJAX
        const ajaxUrl = getAjaxUrl();
        const nonce = getNonce();

        // Obtener nombre del proveedor
        const proveedorNombre = typeof fulldayUsers !== 'undefined' && fulldayUsers.proveedorNombre
            ? fulldayUsers.proveedorNombre
            : '';

        // Enviar a la API
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'fullday_ai_send_message',
                nonce: nonce,
                message: message || '',
                images: imagesToSend.length > 0 ? JSON.stringify(imagesToSend) : '',
                history: JSON.stringify(conversationHistory.slice(0, -1)), // Enviar historial sin el último mensaje
                proveedor_nombre: proveedorNombre
            },
            success: function(response) {
                hideTypingIndicator();

                if (response.success) {
                    const aiMessage = response.data.message;

                    // Agregar respuesta al historial
                    conversationHistory.push({
                        role: 'assistant',
                        content: aiMessage
                    });

                    // Mostrar mensaje de la IA
                    appendAiMessage(aiMessage);

                    // Actualizar botón de guardar
                    updateSaveButton();
                } else {
                    showError(response.data.message || 'Error al comunicarse con la IA');
                }
            },
            error: function() {
                hideTypingIndicator();
                showError('Error de conexión. Intenta nuevamente.');
            },
            complete: function() {
                isProcessing = false;
                input.prop('disabled', false);
                $('#ai-send-message').prop('disabled', false);
                $('#ai-attach-image').prop('disabled', false);
                input.focus();
            }
        });
    }

    /**
     * Agregar mensaje del usuario al chat
     */
    function appendUserMessage(message, images = null) {
        let imagesHtml = '';
        
        if (images && images.length > 0) {
            imagesHtml = '<div class="message-images">';
            images.forEach(img => {
                imagesHtml += `<img src="${img.data}" alt="${escapeHtml(img.name)}" class="message-image">`;
            });
            imagesHtml += '</div>';
        }

        const messageHtml = `
            <div class="user-message">
                <div class="message-avatar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="message-content">
                    ${message ? '<p>' + escapeHtml(message) + '</p>' : ''}
                    ${imagesHtml}
                </div>
            </div>
        `;

        $('#ai-chat-messages').append(messageHtml);
        scrollToBottom();
    }

    /**
     * Agregar mensaje de la IA al chat
     */
    function appendAiMessage(message) {
        // Convertir saltos de línea a párrafos
        const paragraphs = message.split('\n\n').map(p => {
            if (p.trim()) {
                return '<p>' + escapeHtml(p.trim()).replace(/\n/g, '<br>') + '</p>';
            }
            return '';
        }).join('');

        const avatarHtml = getAvatarHtml();

        const messageHtml = `
            <div class="ai-message">
                ${avatarHtml}
                <div class="message-content">
                    ${paragraphs}
                </div>
            </div>
        `;

        $('#ai-chat-messages').append(messageHtml);
        scrollToBottom();
    }

    /**
     * Mostrar indicador de escritura
     */
    function showTypingIndicator() {
        const avatarHtml = getAvatarHtml();
        const typingMessage = getRandomTypingMessage();

        const typingHtml = `
            <div class="typing-indicator" id="typing-indicator">
                ${avatarHtml}
                <div class="typing-content">
                    <div class="typing-message">${escapeHtml(typingMessage)}</div>
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        `;

        $('#ai-chat-messages').append(typingHtml);
        scrollToBottom();
    }

    /**
     * Ocultar indicador de escritura
     */
    function hideTypingIndicator() {
        $('#typing-indicator').remove();
    }

    /**
     * Limpiar chat
     */
    function clearChat() {
        if (!confirm('¿Estás seguro de que quieres limpiar toda la conversación?')) {
            return;
        }

        // Limpiar historial
        conversationHistory = [];

        // Limpiar imágenes adjuntas
        attachedImages = [];
        $('#ai-image-preview-container').empty().hide();

        // Limpiar mensajes (mantener solo el mensaje de bienvenida inicial)
        $('#ai-chat-messages').find('.user-message, .ai-message:not(:first)').remove();

        // Deshabilitar botón de guardar
        updateSaveButton();

        // Limpiar input
        $('#ai-chat-input').val('').focus();
    }

    /**
     * Guardar Full Day
     */
    function saveFullDay() {
        if (isProcessing) {
            return;
        }

        if (conversationHistory.length < 2) {
            showError('Necesitas conversar más con la IA antes de guardar el Full Day');
            return;
        }

        if (!confirm('¿Crear el Full Day ahora?\n\nLa IA completará automáticamente cualquier información que falte y guardará el Full Day como borrador.\n\nPodrás editarlo después para agregar imágenes y ajustar los detalles.\n\n¿Continuar?')) {
            return;
        }

        // Deshabilitar todo
        isProcessing = true;
        const saveBtn = $('#ai-save-fullday');
        const originalText = saveBtn.html();
        saveBtn.prop('disabled', true).addClass('loading');
        $('#ai-send-message').prop('disabled', true);
        $('#ai-chat-input').prop('disabled', true);
        $('#ai-clear-chat').prop('disabled', true);

        // Determinar URL de AJAX
        const ajaxUrl = getAjaxUrl();
        const nonce = getNonce();

        // Enviar petición para crear Full Day
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'fullday_ai_create_fullday',
                nonce: nonce,
                history: JSON.stringify(conversationHistory)
            },
            success: function(response) {
                if (response.success) {
                    // Mostrar mensaje de éxito
                    showSuccess('Full Day creado exitosamente. Redirigiendo...');

                    // Esperar 2 segundos y redirigir
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 2000);
                } else {
                    showError(response.data.message || 'Error al crear el Full Day');

                    // Restaurar botones
                    isProcessing = false;
                    saveBtn.prop('disabled', false).removeClass('loading').html(originalText);
                    $('#ai-send-message').prop('disabled', false);
                    $('#ai-chat-input').prop('disabled', false);
                    $('#ai-clear-chat').prop('disabled', false);
                }
            },
            error: function() {
                showError('Error de conexión. Intenta nuevamente.');

                // Restaurar botones
                isProcessing = false;
                saveBtn.prop('disabled', false).removeClass('loading').html(originalText);
                $('#ai-send-message').prop('disabled', false);
                $('#ai-chat-input').prop('disabled', false);
                $('#ai-clear-chat').prop('disabled', false);
            }
        });
    }

    /**
     * Actualizar estado del botón de guardar
     */
    function updateSaveButton() {
        // Habilitar después de al menos 2 intercambios (4 mensajes)
        if (conversationHistory.length >= 4) {
            $('#ai-save-fullday').prop('disabled', false);
        } else {
            $('#ai-save-fullday').prop('disabled', true);
        }
    }

    /**
     * Scroll al final del chat
     */
    function scrollToBottom() {
        const messagesContainer = $('#ai-chat-messages');
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    }

    /**
     * Mostrar error
     */
    function showError(message) {
        const errorHtml = `
            <div class="ai-message">
                <div class="message-avatar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <div class="message-content" style="background: #fee; border-color: #fcc; color: #c33;">
                    <p><strong>Error:</strong> ${escapeHtml(message)}</p>
                </div>
            </div>
        `;

        $('#ai-chat-messages').append(errorHtml);
        scrollToBottom();
    }

    /**
     * Mostrar mensaje de éxito
     */
    function showSuccess(message) {
        const successHtml = `
            <div class="ai-message">
                <div class="message-avatar" style="background: #10B981;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <div class="message-content" style="background: #d1fae5; border-color: #a7f3d0; color: #065f46;">
                    <p><strong>Éxito:</strong> ${escapeHtml(message)}</p>
                </div>
            </div>
        `;

        $('#ai-chat-messages').append(successHtml);
        scrollToBottom();
    }

    /**
     * Escapar HTML para prevenir XSS
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    /**
     * Obtener URL de AJAX con fallback
     */
    function getAjaxUrl() {
        if (typeof fulldayUsers !== 'undefined' && fulldayUsers.ajaxurl) {
            return fulldayUsers.ajaxurl;
        }
        if (typeof fulldayUsers !== 'undefined' && fulldayUsers.ajaxUrl) {
            return fulldayUsers.ajaxUrl;
        }
        return '/wp-admin/admin-ajax.php';
    }

    /**
     * Obtener nonce
     */
    function getNonce() {
        if (typeof fulldayUsers !== 'undefined' && fulldayUsers.nonce) {
            return fulldayUsers.nonce;
        }
        return '';
    }

    /**
     * Obtener HTML del avatar de Fully
     */
    function getAvatarHtml() {
        const avatarUrl = typeof fulldayUsers !== 'undefined' && fulldayUsers.fullyAvatarUrl
            ? fulldayUsers.fullyAvatarUrl
            : '';

        if (avatarUrl) {
            return `<div class="message-avatar fully-avatar">
                <img src="${avatarUrl}" alt="Fully">
            </div>`;
        }

        return `<div class="message-avatar">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
        </div>`;
    }

    /**
     * Obtener mensaje aleatorio de escritura
     */
    function getRandomTypingMessage() {
        const messages = typeof fulldayUsers !== 'undefined' && fulldayUsers.typingMessages
            ? fulldayUsers.typingMessages
            : [
                'Fully está pensando... 🤔',
                'Fully está organizando las ideas... 💡',
                'Fully está buscando las palabras perfectas... ✨',
                'Fully está preparando algo genial... 🌟',
                'Fully está armando la respuesta... 🎨'
            ];

        return messages[Math.floor(Math.random() * messages.length)];
    }

})(jQuery);
