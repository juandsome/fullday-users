/**
 * JavaScript para Dashboard de Proveedor
 * Fullday Users Plugin
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Dropdown dependiente Estado -> Ciudad en perfil
        $('#estado').on('change', function() {
            const estado = $(this).val();
            const ciudadSelect = $('#ciudad');

            console.log('=== DASHBOARD PROVEEDOR - Cambio de Estado ===');
            console.log('Estado seleccionado:', estado);

            if (!estado) {
                ciudadSelect.html('<option value="">Selecciona primero un estado</option>');
                return;
            }

            // Deshabilitar mientras carga
            ciudadSelect.html('<option value="">Cargando ciudades...</option>').prop('disabled', true);

            // Determinar URL de AJAX con fallback
            var ajaxUrl = '/wp-admin/admin-ajax.php';
            if (typeof fulldayUsers !== 'undefined') {
                if (fulldayUsers.ajaxurl) {
                    ajaxUrl = fulldayUsers.ajaxurl;
                } else if (fulldayUsers.ajaxUrl) {
                    ajaxUrl = fulldayUsers.ajaxUrl;
                }
            }

            console.log('URL AJAX para ciudades:', ajaxUrl);

            // AJAX para obtener ciudades
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fullday_get_cities',
                    estado: estado
                },
                success: function(response) {
                    console.log('Respuesta ciudades:', response);

                    if (typeof response === 'string') {
                        console.error('ERROR: Respuesta es HTML, no JSON');
                        console.log('Primeros 500 caracteres:', response.substring(0, 500));
                        ciudadSelect.html('<option value="">Error de configuración</option>');
                        return;
                    }

                    if (response.success && response.data.ciudades) {
                        const ciudades = response.data.ciudades;
                        console.log('Ciudades recibidas:', ciudades.length);
                        let options = '<option value="">Selecciona una ciudad</option>';

                        ciudades.forEach(function(ciudad) {
                            options += '<option value="' + ciudad.id + '">' + ciudad.name + '</option>';
                        });

                        ciudadSelect.html(options).prop('disabled', false);
                    } else {
                        ciudadSelect.html('<option value="">No hay ciudades disponibles</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error en AJAX ciudades:', error);
                    ciudadSelect.html('<option value="">Error al cargar ciudades</option>');
                }
            });
        });

        // Sistema de tabs
        $('.dashboard-tab').on('click', function() {
            const tab = $(this).data('tab');

            // Si hacen click en "crear" y hay parámetros en la URL, recargar la página
            if (tab === 'crear') {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('post_id') || urlParams.get('tab') === 'editar') {
                    // Limpiar URL y recargar
                    const currentUrl = window.location.href.split('?')[0];
                    window.location.href = currentUrl + '?tab=crear';
                    return;
                }
            }

            // Si hacen click en "editar" redirigir al tab crear-editar
            const targetTab = (tab === 'editar' || tab === 'crear') ? 'crear-editar' : tab;

            // Actualizar tabs
            $('.dashboard-tab').removeClass('active');
            $(this).addClass('active');

            // Actualizar contenido
            $('.tab-content').removeClass('active');
            $('#' + targetTab + '-tab').addClass('active');

            // Actualizar URL sin recargar
            if (history.pushState) {
                const url = new URL(window.location);
                url.searchParams.set('tab', tab);
                // Si es crear, asegurarse de que no haya post_id
                if (tab === 'crear') {
                    url.searchParams.delete('post_id');
                }
                window.history.pushState({}, '', url);
            }
        });

        // Upload de avatar
        $('#proveedor-btn-cambiar-foto').on('click', function() {
            $('#proveedor-avatar-upload').click();
        });

        $('#proveedor-avatar-upload').on('change', function() {
            if (this.files.length > 0) {
                uploadAvatar(this.files[0]);
                // Limpiar input para permitir seleccionar el mismo archivo
                $(this).val('');
            }
        });

        // Upload de banner
        $('#proveedor-btn-cambiar-banner').on('click', function() {
            $('#proveedor-banner-upload').click();
        });

        $('#proveedor-banner-upload').on('change', function() {
            if (this.files.length > 0) {
                uploadBanner(this.files[0]);
                // Limpiar input para permitir seleccionar el mismo archivo
                $(this).val('');
            }
        });

        // Función para subir avatar
        function uploadAvatar(file) {
            console.log('=== UPLOAD AVATAR ===');
            console.log('Archivo:', file.name, file.type, file.size);

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

            // Determinar URL de AJAX y nonce con fallback
            var ajaxUrl = '/wp-admin/admin-ajax.php';
            var nonce = '';
            if (typeof fulldayUsers !== 'undefined') {
                if (fulldayUsers.ajaxurl) {
                    ajaxUrl = fulldayUsers.ajaxurl;
                } else if (fulldayUsers.ajaxUrl) {
                    ajaxUrl = fulldayUsers.ajaxUrl;
                }
                nonce = fulldayUsers.nonce || '';
            }

            console.log('URL AJAX para avatar:', ajaxUrl);

            // Crear FormData
            const formData = new FormData();
            formData.append('action', 'fullday_upload_avatar');
            formData.append('nonce', nonce);
            formData.append('avatar', file);

            // Mostrar loading
            $('#avatar-display').css('opacity', '0.5');

            // Upload AJAX
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Respuesta upload avatar:', response);
                    $('#avatar-display').css('opacity', '1');

                    if (typeof response === 'string') {
                        console.error('ERROR: Respuesta es HTML, no JSON');
                        console.log('Primeros 500 caracteres:', response.substring(0, 500));
                        showMessage('perfil', 'Error de configuración en upload de avatar', 'error');
                        return;
                    }

                    if (response && response.success) {
                        // Actualizar avatar en sección de información personal
                        const avatarHtml = '<img src="' + response.data.url + '" alt="Avatar">';
                        $('#avatar-display').html(avatarHtml);

                        // Actualizar avatar en preview del banner
                        const bannerAvatarContainer = $('.banner-avatar');
                        bannerAvatarContainer.html('<img src="' + response.data.url + '" alt="Avatar" id="banner-avatar-preview">');

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

        // Función para subir banner
        function uploadBanner(file) {
            console.log('=== UPLOAD BANNER ===');
            console.log('Archivo:', file.name, file.type, file.size);

            // Validar tipo
            const allowedTypes = ['image/jpeg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                showMessage('perfil', 'Solo se permiten imágenes JPG o PNG.', 'error');
                return;
            }

            // Validar tamaño (5MB max)
            const maxSize = 5 * 1024 * 1024;
            if (file.size > maxSize) {
                showMessage('perfil', 'La imagen es demasiado grande. Máximo 5MB.', 'error');
                return;
            }

            // Determinar URL de AJAX y nonce con fallback
            var ajaxUrl = '/wp-admin/admin-ajax.php';
            var nonce = '';
            if (typeof fulldayUsers !== 'undefined') {
                if (fulldayUsers.ajaxurl) {
                    ajaxUrl = fulldayUsers.ajaxurl;
                } else if (fulldayUsers.ajaxUrl) {
                    ajaxUrl = fulldayUsers.ajaxUrl;
                }
                nonce = fulldayUsers.nonce || '';
            }

            console.log('URL AJAX para banner:', ajaxUrl);

            // Crear FormData
            const formData = new FormData();
            formData.append('action', 'fullday_upload_banner');
            formData.append('nonce', nonce);
            formData.append('banner', file);

            // Mostrar loading
            $('#banner-preview-display').css('opacity', '0.5');

            // Upload AJAX
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Respuesta upload banner:', response);
                    $('#banner-preview-display').css('opacity', '1');

                    if (typeof response === 'string') {
                        console.error('ERROR: Respuesta es HTML, no JSON');
                        console.log('Primeros 500 caracteres:', response.substring(0, 500));
                        showMessage('perfil', 'Error de configuración en upload de banner', 'error');
                        return;
                    }

                    if (response && response.success) {
                        // Actualizar banner en el preview
                        $('#banner-preview-display').css('background-image', 'url(' + response.data.url + ')');
                        $('#banner-preview-display').find('.banner-placeholder').remove();
                        showMessage('perfil', 'Banner actualizado correctamente.', 'success');
                    } else {
                        showMessage('perfil', response.data.message || 'Error al subir el banner.', 'error');
                    }
                },
                error: function() {
                    $('#banner-preview-display').css('opacity', '1');
                    showMessage('perfil', 'Error de conexión. Intenta nuevamente.', 'error');
                }
            });
        }

        // Actualizar preview en tiempo real cuando cambien los campos
        $('#empresa').on('input', function() {
            const empresa = $(this).val() || 'Nombre de tu Empresa';
            $('#banner-empresa-preview').text(empresa);
        });

        $('#facebook_url').on('input', function() {
            const url = $(this).val();
            if (url) {
                if ($('#banner-facebook-preview').length === 0) {
                    const icon = `<a href="${url}" target="_blank" class="social-icon" id="banner-facebook-preview">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>`;
                    $('.banner-social').prepend(icon);
                } else {
                    $('#banner-facebook-preview').attr('href', url);
                }
            } else {
                $('#banner-facebook-preview').remove();
            }
        });

        $('#instagram_url').on('input', function() {
            const url = $(this).val();
            if (url) {
                if ($('#banner-instagram-preview').length === 0) {
                    const icon = `<a href="${url}" target="_blank" class="social-icon" id="banner-instagram-preview">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </a>`;
                    $('.banner-social').append(icon);
                } else {
                    $('#banner-instagram-preview').attr('href', url);
                }
            } else {
                $('#banner-instagram-preview').remove();
            }
        });

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
                empresa: $('#empresa').val(),
                descripcion: $('#descripcion').val(),
                estado: $('#estado').val(),
                ciudad: $('#ciudad').val(),
                facebook_url: $('#facebook_url').val(),
                instagram_url: $('#instagram_url').val(),
                whatsapp: whatsapp,
                cashea_code: $('#cashea_code').val()
            };

            // LOG: Datos que se van a enviar
            console.log('=== ENVIANDO DATOS DE PERFIL ===');
            console.log('Form Data:', formData);
            console.log('Empresa:', formData.empresa);
            console.log('Descripcion:', formData.descripcion);
            console.log('Facebook URL:', formData.facebook_url);
            console.log('Instagram URL:', formData.instagram_url);

            // Mostrar loading
            showLoading('perfil');

            // AJAX
            $.ajax({
                url: fulldayUsers.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    hideLoading('perfil');

                    // LOG: Respuesta del servidor
                    console.log('=== RESPUESTA DEL SERVIDOR ===');
                    console.log('Response:', response);

                    if (response.success) {
                        showMessage('perfil', response.data.message, 'success');
                    } else {
                        showMessage('perfil', response.data.message || 'Error al actualizar el perfil.', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    hideLoading('perfil');

                    // LOG: Error
                    console.log('=== ERROR EN AJAX ===');
                    console.log('Status:', status);
                    console.log('Error:', error);
                    console.log('XHR:', xhr);

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
