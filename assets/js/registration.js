/**
 * JavaScript para formularios de registro
 * Fullday Users Plugin
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Toggle entre tabs Cliente/Proveedor
        $('.fullday-tab').on('click', function() {
            const type = $(this).data('type');

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
        });

        // Submit del formulario
        $('#fullday-registration-form').on('submit', function(e) {
            e.preventDefault();

            // Obtener valores
            const email = $('#email').val().trim();
            const password = $('#password').val();
            const confirmPassword = $('#confirm_password').val();
            const userType = $('#user_type').val();
            const nonce = $('#fullday_register_nonce_field').val();

            // Validaciones básicas
            if (!email || !password || !confirmPassword) {
                showError('Todos los campos son obligatorios.');
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
                    email: email,
                    password: password,
                    confirm_password: confirmPassword,
                    user_type: userType
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