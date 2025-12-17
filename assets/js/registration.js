/**
 * JavaScript para formularios de registro
 * Fullday Users Plugin
 */

(function($) {
    'use strict';

    $(document).ready(function() {

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

            // Mostrar loading
            showLoading();

            // AJAX para registro
            $.ajax({
                url: fulldayUsers.ajaxurl,
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
                    console.log('Respuesta del servidor:', response);
                    hideLoading();

                    if (response && response.success) {
                        // Redirigir al dashboard
                        window.location.href = response.data.redirect;
                    } else {
                        const errorMsg = (response && response.data && response.data.message)
                            ? response.data.message
                            : 'Error al registrar el usuario.';
                        showError(errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', xhr, status, error);
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
