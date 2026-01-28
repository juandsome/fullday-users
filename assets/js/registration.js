/**
 * JavaScript para formularios de registro
 * Fullday Users Plugin
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Variables globales
        let currentType = 'cliente';
        let uploadedDocumentoId = null;

        // Toggle entre tabs Cliente/Proveedor
        $('.fullday-tab').on('click', function() {
            const type = $(this).data('type');
            currentType = type;

            // Actualizar tabs
            $('.fullday-tab').removeClass('active');
            $(this).addClass('active');

            // Actualizar subtítulo
            const subtitle = type === 'cliente' ? 'Regístrate como cliente' : 'Regístrate como proveedor';
            $('#fullday-registration-subtitle').text(subtitle);

            // Actualizar botón de envío
            const btnText = type === 'cliente' ? 'Registrarse como Cliente' : 'Registrarse como Proveedor';
            $('#fullday-submit-btn .btn-text').text(btnText);

            // Actualizar campo hidden
            $('#user_type').val(type);

            // Mostrar/ocultar campo de documento y nota de WhatsApp
            if (type === 'proveedor') {
                $('#documento-group').slideDown();
                $('#whatsapp-note').slideDown();
            } else {
                $('#documento-group').slideUp();
                $('#whatsapp-note').slideUp();
            }

            // Limpiar errores
            hideError();
        });

        // Validación de WhatsApp: solo números y exactamente 7 dígitos
        $('#whatsapp_number').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 7) {
                this.value = this.value.slice(0, 7);
            }
        });

        // Dropdown dependiente Estado -> Ciudad
        $('#estado').on('change', function() {
            const estado = $(this).val();
            const ciudadSelect = $('#ciudad_region');

            if (!estado) {
                ciudadSelect.html('<option value="">Selecciona primero un estado</option>').prop('disabled', true);
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

            // AJAX para obtener ciudades
            $.ajax({
                url: ajaxUrl,
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
                            options += '<option value="' + ciudad.id + '">' + ciudad.name + '</option>';
                        });

                        ciudadSelect.html(options).prop('disabled', false);
                    } else {
                        ciudadSelect.html('<option value="">No hay ciudades disponibles</option>');
                    }
                },
                error: function(xhr, status, error) {
                    ciudadSelect.html('<option value="">Error al cargar ciudades</option>');
                }
            });
        });

        // Drag & Drop para documento
        const dropzone = $('#documento-dropzone');
        const fileInput = $('#documento');

        // Click en dropzone
        dropzone.on('click', function() {
            fileInput.click();
        });

        // Prevenir comportamiento por defecto
        dropzone.on('dragover dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('dragover');
        });

        dropzone.on('dragleave dragend drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
        });

        // Drop de archivo
        dropzone.on('drop', function(e) {
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                handleDocumentoUpload(files[0]);
            }
        });

        // Selección de archivo
        fileInput.on('change', function() {
            if (this.files.length > 0) {
                handleDocumentoUpload(this.files[0]);
            }
        });

        // Remover documento
        $('#documento-remove').on('click', function(e) {
            e.stopPropagation();
            removeDocumento();
        });

        // Función para manejar upload de documento
        function handleDocumentoUpload(file) {
            // Validar tipo de archivo
            const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                showError('Tipo de archivo no permitido. Solo JPG, PNG o PDF.');
                return;
            }

            // Validar tamaño (5MB max)
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (file.size > maxSize) {
                showError('El archivo es demasiado grande. Máximo 5MB.');
                return;
            }

            // Determinar URL de AJAX con fallback
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

            // Crear FormData
            const formData = new FormData();
            formData.append('action', 'fullday_upload_documento');
            formData.append('nonce', nonce);
            formData.append('documento', file);

            // Mostrar loading
            dropzone.html('<p>Subiendo...</p>');

            // Upload AJAX
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response && response.success) {
                        uploadedDocumentoId = response.data.attachment_id;
                        $('#documento_id').val(uploadedDocumentoId);
                        showDocumentoPreview(response.data.url, file.type);
                        hideError();
                    } else {
                        var errorMsg = 'Error al subir el documento.';
                        if (response && response.data && response.data.message) {
                            errorMsg = response.data.message;
                        }
                        showError(errorMsg);
                        resetDropzone();
                    }
                },
                error: function(xhr, status, error) {
                    showError('Error de conexión. Intenta nuevamente.');
                    resetDropzone();
                }
            });
        }

        // Mostrar preview del documento
        function showDocumentoPreview(url, type) {
            dropzone.hide();

            if (type === 'application/pdf') {
                $('#documento-preview-img').attr('src', fulldayUsers.pluginUrl + 'assets/images/pdf-icon.png');
            } else {
                $('#documento-preview-img').attr('src', url);
            }

            $('#documento-preview').show();
        }

        // Remover documento
        function removeDocumento() {
            uploadedDocumentoId = null;
            $('#documento_id').val('');
            $('#documento-preview').hide();
            resetDropzone();
            fileInput.val('');
        }

        // Reset dropzone
        function resetDropzone() {
            dropzone.html(`
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                <p class="dropzone-text">Arrastra tu documento aquí</p>
                <p class="dropzone-subtext">o haz clic para seleccionar</p>
                <p class="dropzone-formats">JPG, PNG o PDF (máx. 5MB)</p>
            `).show();
        }

        // Submit del formulario
        $('#fullday-registration-form').on('submit', function(e) {
            e.preventDefault();

            // Obtener valores
            const username = $('#username').val().trim();
            const email = $('#email').val().trim();
            const whatsappPrefix = $('#whatsapp_prefix').val();
            const whatsappNumber = $('#whatsapp_number').val().trim();
            const estado = $('#estado').val();
            const ciudad = $('#ciudad_region').val(); // USAR NUEVO SELECT
            const password = $('#password').val();
            const confirmPassword = $('#confirm_password').val();
            const userType = $('#user_type').val();
            const nonce = $('#fullday_register_nonce_field').val();

            // Validaciones básicas
            if (!username || !email || !password || !confirmPassword || !estado || !ciudad) {
                showError('Todos los campos obligatorios deben ser completados.');
                return;
            }

            if (password !== confirmPassword) {
                showError('Las contraseñas no coinciden.');
                return;
            }

            if (password.length < 6) {
                showError('La contraseña debe tener al menos 6 caracteres.');
                return;
            }

            // Validar WhatsApp si está presente
            if (whatsappNumber && whatsappNumber.length !== 7) {
                showError('El número de WhatsApp debe tener exactamente 7 dígitos.');
                return;
            }

            // Mostrar loading
            showLoading();

            // Determinar URL de AJAX con fallback
            var ajaxUrl = '/wp-admin/admin-ajax.php';
            if (typeof fulldayUsers !== 'undefined') {
                if (fulldayUsers.ajaxurl) {
                    ajaxUrl = fulldayUsers.ajaxurl;
                } else if (fulldayUsers.ajaxUrl) {
                    ajaxUrl = fulldayUsers.ajaxUrl;
                }
            }

            // AJAX para registro
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fullday_register_user',
                    nonce: nonce,
                    username: username,
                    email: email,
                    whatsapp_prefix: whatsappPrefix,
                    whatsapp_number: whatsappNumber,
                    estado: estado,
                    ciudad: ciudad,
                    password: password,
                    confirm_password: confirmPassword,
                    user_type: userType,
                    documento_id: uploadedDocumentoId
                },
                success: function(response) {
                    hideLoading();

                    if (response && response.success) {
                        // Redirigir al dashboard
                        window.location.href = response.data.redirect;
                    } else {
                        // Manejo seguro de errores
                        var errorMessage = 'Error al registrar el usuario.';
                        if (response && response.data && response.data.message) {
                            errorMessage = response.data.message;
                        }
                        showError(errorMessage);
                    }
                },
                error: function(xhr, status, error) {
                    hideLoading();
                    showError('Error de conexión. Intenta nuevamente.');
                }
            });
        });

        // Funciones de utilidad
        function showError(message) {
            $('#fullday-form-error').text(message).slideDown();
        }

        function hideError() {
            $('#fullday-form-error').slideUp();
        }

        function showLoading() {
            const btn = $('#fullday-submit-btn');
            btn.prop('disabled', true);
            btn.find('.btn-text').hide();
            btn.find('.btn-loader').show();
        }

        function hideLoading() {
            const btn = $('#fullday-submit-btn');
            btn.prop('disabled', false);
            btn.find('.btn-text').show();
            btn.find('.btn-loader').hide();
        }
    });

})(jQuery);
