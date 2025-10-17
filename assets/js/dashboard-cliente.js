/**
 * JavaScript para Dashboard de Cliente
 * Fullday Users Plugin
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Dropdown dependiente Estado -> Ciudad en perfil
        $('#estado').on('change', function() {
            const estado = $(this).val();
            const ciudadSelect = $('#ciudad');

            if (!estado) {
                ciudadSelect.html('<option value="">Selecciona primero un estado</option>');
                return;
            }

            // Deshabilitar mientras carga
            ciudadSelect.html('<option value="">Cargando ciudades...</option>').prop('disabled', true);

            // AJAX para obtener ciudades
            $.ajax({
                url: fulldayUsers.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fullday_get_cities',
                    estado: estado
                },
                success: function(response) {
                    if (response.success && response.data.ciudades) {
                        const ciudades = response.data.ciudades;
                        let options = '<option value="">Selecciona una ciudad</option>';

                        ciudades.forEach(function(ciudad) {
                            // Ahora las ciudades vienen con id y name desde la taxonomía
                            options += '<option value="' + ciudad.id + '">' + ciudad.name + '</option>';
                        });

                        ciudadSelect.html(options).prop('disabled', false);
                    } else {
                        ciudadSelect.html('<option value="">No hay ciudades disponibles</option>');
                    }
                },
                error: function() {
                    ciudadSelect.html('<option value="">Error al cargar ciudades</option>');
                }
            });
        });

        // Sistema de tabs
        $('.dashboard-tab').on('click', function() {
            const tab = $(this).data('tab');

            // Actualizar tabs
            $('.dashboard-tab').removeClass('active');
            $(this).addClass('active');

            // Actualizar contenido
            $('.tab-content').removeClass('active');
            $('#' + tab + '-tab').addClass('active');

            // Actualizar URL sin recargar
            if (history.pushState) {
                const url = new URL(window.location);
                url.searchParams.set('tab', tab);
                window.history.pushState({}, '', url);
            }
        });

        // Upload de avatar
        $('#cliente-btn-cambiar-foto').on('click', function() {
            $('#cliente-avatar-upload').click();
        });

        $('#cliente-avatar-upload').on('change', function() {
            if (this.files.length > 0) {
                uploadAvatar(this.files[0]);
                // Limpiar input para permitir seleccionar el mismo archivo
                $(this).val('');
            }
        });

        // Función para subir avatar
        function uploadAvatar(file) {
            // Validar tipo
            const allowedTypes = ['image/jpeg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                showMessage('perfil', 'Solo se permiten imágenes JPG o PNG.', 'error');
                return;
            }

            // Validar tamaño (2MB max)
            const maxSize = 2 * 1024 * 1024;
            if (file.size > maxSize) {
                showMessage('perfil', 'La imagen es demasiado grande. Máximo 2MB.', 'error');
                return;
            }

            // Crear FormData
            const formData = new FormData();
            formData.append('action', 'fullday_upload_avatar');
            formData.append('nonce', fulldayUsers.nonce);
            formData.append('avatar', file);

            // Mostrar loading
            $('#avatar-display').css('opacity', '0.5');

            // Upload AJAX
            $.ajax({
                url: fulldayUsers.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('#avatar-display').css('opacity', '1');

                    if (response.success) {
                        // Actualizar avatar
                        const avatarHtml = '<img src="' + response.data.url + '" alt="Avatar">';
                        $('#avatar-display').html(avatarHtml);
                        showMessage('perfil', 'Avatar actualizado correctamente.', 'success');
                    } else {
                        showMessage('perfil', response.data.message || 'Error al subir el avatar.', 'error');
                    }
                },
                error: function() {
                    $('#avatar-display').css('opacity', '1');
                    showMessage('perfil', 'Error de conexión. Intenta nuevamente.', 'error');
                }
            });
        }

        // Validación de WhatsApp: solo números y exactamente 7 dígitos
        $('#whatsapp_number').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 7) {
                this.value = this.value.slice(0, 7);
            }
        });

        // Submit de formulario de perfil
        $('#perfil-form').on('submit', function(e) {
            e.preventDefault();

            // Validar WhatsApp
            const whatsappPrefix = $('#whatsapp_prefix').val();
            const whatsappNumber = $('#whatsapp_number').val().trim();

            if (whatsappNumber && whatsappNumber.length !== 7) {
                showMessage('perfil', 'El número de WhatsApp debe tener exactamente 7 dígitos.', 'error');
                return;
            }

            // Combinar whatsapp (solo si hay número)
            const whatsapp = whatsappNumber ? whatsappPrefix + whatsappNumber : '';

            const formData = {
                action: 'fullday_update_profile',
                nonce: fulldayUsers.nonce,
                display_name: $('#display_name').val(),
                whatsapp: whatsapp,
                fecha_nacimiento: $('#fecha_nacimiento').val(),
                estado: $('#estado').val(),
                ciudad: $('#ciudad').val()
            };

            // Mostrar loading
            showLoading('perfil');

            // AJAX
            $.ajax({
                url: fulldayUsers.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    hideLoading('perfil');

                    if (response.success) {
                        showMessage('perfil', response.data.message, 'success');
                    } else {
                        showMessage('perfil', response.data.message || 'Error al actualizar el perfil.', 'error');
                    }
                },
                error: function() {
                    hideLoading('perfil');
                    showMessage('perfil', 'Error de conexión. Intenta nuevamente.', 'error');
                }
            });
        });

        // Submit de formulario de contraseña
        $('#password-form').on('submit', function(e) {
            e.preventDefault();

            const currentPassword = $('#current_password').val();
            const newPassword = $('#new_password').val();
            const confirmPassword = $('#confirm_password').val();

            // Validaciones
            if (newPassword.length < 6) {
                showMessage('password', 'La nueva contraseña debe tener al menos 6 caracteres.', 'error');
                return;
            }

            if (newPassword !== confirmPassword) {
                showMessage('password', 'Las contraseñas no coinciden.', 'error');
                return;
            }

            const formData = {
                action: 'fullday_update_password',
                nonce: fulldayUsers.nonce,
                current_password: currentPassword,
                new_password: newPassword,
                confirm_password: confirmPassword
            };

            // Mostrar loading
            showLoading('password');

            // AJAX
            $.ajax({
                url: fulldayUsers.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    hideLoading('password');

                    if (response.success) {
                        showMessage('password', response.data.message, 'success');
                        // Limpiar campos
                        $('#password-form')[0].reset();
                    } else {
                        showMessage('password', response.data.message || 'Error al actualizar la contraseña.', 'error');
                    }
                },
                error: function() {
                    hideLoading('password');
                    showMessage('password', 'Error de conexión. Intenta nuevamente.', 'error');
                }
            });
        });

        // Funciones de utilidad
        function showMessage(formType, message, type) {
            const messageEl = $('#' + formType + '-message');
            messageEl.removeClass('success error').addClass(type);
            messageEl.text(message).slideDown();

            // Ocultar después de 5 segundos
            setTimeout(function() {
                messageEl.slideUp();
            }, 5000);
        }

        function showLoading(formType) {
            const btn = $('#btn-actualizar-' + formType);
            btn.prop('disabled', true);
            btn.find('.btn-text').hide();
            btn.find('.btn-loader').show();
        }

        function hideLoading(formType) {
            const btn = $('#btn-actualizar-' + formType);
            btn.prop('disabled', false);
            btn.find('.btn-text').show();
            btn.find('.btn-loader').hide();
        }
    });

})(jQuery);
