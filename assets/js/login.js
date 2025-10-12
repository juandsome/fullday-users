jQuery(document).ready(function($) {
    'use strict';

    // Funciones helper para obtener AJAX URL y nonce con fallbacks
    function getAjaxUrl() {
        if (typeof fulldayUsers !== 'undefined' && fulldayUsers.ajaxurl) {
            return fulldayUsers.ajaxurl;
        } else if (typeof fulldayUsers !== 'undefined' && fulldayUsers.ajaxUrl) {
            return fulldayUsers.ajaxUrl;
        } else if (typeof ajaxurl !== 'undefined') {
            return ajaxurl;
        } else {
            return '/wp-admin/admin-ajax.php';
        }
    }

    function getNonce() {
        if (typeof fulldayUsers !== 'undefined' && fulldayUsers.nonce) {
            return fulldayUsers.nonce;
        }
        return '';
    }

    // Login form submission
    $('#fullday-login-form').on('submit', function(e) {
        e.preventDefault();
        console.log('=== LOGIN FORM SUBMIT ===');

        var $form = $(this);
        var $submitBtn = $('#fullday-login-btn');
        var $btnText = $submitBtn.find('.btn-text');
        var $btnLoader = $submitBtn.find('.btn-loader');
        var $errorDiv = $('#fullday-login-error');

        // Verificar que los elementos existen
        console.log('Submit button:', $submitBtn.length);
        console.log('Error div:', $errorDiv.length);

        // Ocultar errores previos
        $errorDiv.hide().text('');

        // Deshabilitar el botón
        $submitBtn.prop('disabled', true);
        $btnText.hide();
        $btnLoader.show();

        // Obtener AJAX URL
        var ajaxUrl = getAjaxUrl();
        console.log('AJAX URL:', ajaxUrl);

        // Preparar datos
        var formData = {
            action: 'fullday_login_user',
            nonce: $('#fullday_login_nonce_field').val(),
            username: $('#login_username').val(),
            password: $('#login_password').val(),
            remember_me: $('#remember_me').is(':checked') ? 1 : 0
        };

        console.log('Form data:', {
            action: formData.action,
            nonce: formData.nonce ? 'presente' : 'FALTA',
            username: formData.username ? 'presente' : 'FALTA',
            password: formData.password ? 'presente' : 'FALTA',
            remember_me: formData.remember_me
        });

        // Enviar petición AJAX
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('AJAX Success response:', response);

                if (response.success) {
                    // Login exitoso
                    console.log('✓ Login exitoso, redirigiendo a:', response.data.redirect);
                    window.location.href = response.data.redirect;
                } else {
                    // Mostrar error
                    console.log('✗ Login fallido:', response);
                    var errorMessage = (response.data && response.data.message)
                        ? response.data.message
                        : 'Error al iniciar sesión. Por favor intenta de nuevo.';
                    $errorDiv.text(errorMessage).show();

                    // Rehabilitar el botón
                    $submitBtn.prop('disabled', false);
                    $btnText.show();
                    $btnLoader.hide();
                }
            },
            error: function(xhr, status, error) {
                console.error('✗ AJAX Error:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });
                $errorDiv.text('Ocurrió un error. Por favor intenta de nuevo.').show();

                // Rehabilitar el botón
                $submitBtn.prop('disabled', false);
                $btnText.show();
                $btnLoader.hide();
            }
        });
    });

    // Google login
    $('#fullday-google-login').on('click', function() {
        var $btn = $(this);
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).text('Conectando con Google...');

        // Obtener AJAX URL y nonce
        var ajaxUrl = getAjaxUrl();
        var nonce = getNonce();

        // Solicitar URL de autenticación
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'fullday_get_google_auth_url',
                nonce: nonce
            },
            success: function(response) {
                if (response.success && response.data.auth_url) {
                    // Redirigir a Google OAuth
                    window.location.href = response.data.auth_url;
                } else {
                    alert(response.data.message || 'Error: Google login no está configurado correctamente.');
                    $btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function() {
                alert('Error de conexión. Intenta nuevamente.');
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // Facebook login
    $('#fullday-facebook-login').on('click', function() {
        var $btn = $(this);
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).text('Conectando con Facebook...');

        // Obtener AJAX URL y nonce
        var ajaxUrl = getAjaxUrl();
        var nonce = getNonce();

        // Solicitar URL de autenticación
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'fullday_get_facebook_auth_url',
                nonce: nonce
            },
            success: function(response) {
                if (response.success && response.data.auth_url) {
                    // Redirigir a Facebook OAuth
                    window.location.href = response.data.auth_url;
                } else {
                    alert(response.data.message || 'Error: Facebook login no está configurado correctamente.');
                    $btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function() {
                alert('Error de conexión. Intenta nuevamente.');
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
});
