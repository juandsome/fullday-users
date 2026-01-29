<?php
/**
 * Gestión de registro de usuarios
 *
 * @package Fullday_Users
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para registro de usuarios
 */
class Fullday_Users_Registration {

    /**
     * Inicializar
     */
    public static function init() {
        // Registrar shortcode de registro
        add_shortcode('fullday_registration', array(__CLASS__, 'registration_shortcode'));

        // Registrar shortcode de login
        add_shortcode('fullday_login', array(__CLASS__, 'login_shortcode'));

        // AJAX handlers
        add_action('wp_ajax_nopriv_fullday_register_user', array(__CLASS__, 'ajax_register_user'));
        add_action('wp_ajax_fullday_register_user', array(__CLASS__, 'ajax_register_user'));

        // AJAX handler para login
        add_action('wp_ajax_nopriv_fullday_login_user', array(__CLASS__, 'ajax_login_user'));
        add_action('wp_ajax_fullday_login_user', array(__CLASS__, 'ajax_login_user'));
    }

    /**
     * Shortcode de registro
     */
    public static function registration_shortcode($atts) {
        // Si el usuario ya está logueado y NO es administrador, redirigir
        if (is_user_logged_in() && !current_user_can('administrator')) {
            wp_redirect(home_url('/dashboard'));
            exit;
        }

        ob_start();
        ?>
        <div class="fullday-registration-container">
            <div class="fullday-registration-box">
                <h2 class="fullday-registration-title">Crear cuenta</h2>
                <p class="fullday-registration-subtitle">Regístrate en la plataforma</p>

                <form id="fullday-registration-form" class="fullday-registration-form">
                    <?php wp_nonce_field('fullday_register_nonce', 'fullday_register_nonce_field'); ?>

                    <div class="fullday-form-group">
                        <label for="email">Correo electrónico</label>
                        <input type="email" id="email" name="email" placeholder="tu@email.com" required>
                    </div>

                    <div class="fullday-form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                    </div>

                    <div class="fullday-form-group">
                        <label for="confirm_password">Confirmar contraseña</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
                    </div>

                    <div class="fullday-form-error" id="fullday-form-error" style="display: none;"></div>

                    <button type="submit" class="fullday-submit-btn" id="fullday-submit-btn">
                        <span class="btn-text">Registrarse</span>
                        <span class="btn-loader" style="display: none;">
                            <svg class="spinner" width="20" height="20" viewBox="0 0 24 24">
                                <circle class="spinner-circle" cx="12" cy="12" r="10" fill="none" stroke-width="3"></circle>
                            </svg>
                        </span>
                    </button>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }



    /**
     * AJAX handler para registro de usuario
     */
    public static function ajax_register_user() {
        check_ajax_referer('fullday_register_nonce', 'nonce');

        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validaciones
        if (empty($email) || empty($password)) {
            wp_send_json_error(array('message' => 'Todos los campos obligatorios deben ser completados.'));
        }

        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'El correo electrónico no es válido.'));
        }

        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'El correo electrónico ya está registrado.'));
        }

        if ($password !== $confirm_password) {
            wp_send_json_error(array('message' => 'Las contraseñas no coinciden.'));
        }

        if (strlen($password) < 6) {
            wp_send_json_error(array('message' => 'La contraseña debe tener al menos 6 caracteres.'));
        }

        // Generar username único a partir del email
        $username_base = sanitize_user(current(explode('@', $email)));
        $username = $username_base;
        $counter = 1;
        while (username_exists($username)) {
            $username = $username_base . $counter;
            $counter++;
        }

        // Crear usuario
        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'role' => 'fullday_cliente'
        );

        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }

        // Auto login
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        wp_send_json_success(array(
            'message' => 'Registro exitoso.',
            'redirect' => home_url('/dashboard')
        ));
    }



    /**
     * Shortcode de login
     */
    public static function login_shortcode($atts) {
        // Si el usuario ya está logueado y NO es administrador, redirigir
        if (is_user_logged_in() && !current_user_can('administrator')) {
            wp_redirect(home_url('/dashboard'));
            exit;
        }

        ob_start();
        ?>
        <div class="fullday-login-container">
            <div class="fullday-login-box">
                <h2 class="fullday-login-title">Iniciar sesión</h2>
                <p class="fullday-login-subtitle">Accede a tu cuenta</p>

                <form id="fullday-login-form" class="fullday-login-form">
                    <?php wp_nonce_field('fullday_login_nonce', 'fullday_login_nonce_field'); ?>

                    <div class="fullday-form-group">
                        <label for="login_username">Correo o nombre de usuario</label>
                        <input type="text" id="login_username" name="username" placeholder="tu@email.com" required>
                    </div>

                    <div class="fullday-form-group">
                        <label for="login_password">Contraseña</label>
                        <input type="password" id="login_password" name="password" placeholder="••••••••" required>
                    </div>

                    <div class="fullday-form-group fullday-form-remember">
                        <label>
                            <input type="checkbox" id="remember_me" name="remember_me" value="1">
                            <span>Recordarme</span>
                        </label>
                    </div>

                    <div class="fullday-form-error" id="fullday-login-error" style="display: none;"></div>

                    <button type="submit" class="fullday-submit-btn" id="fullday-login-btn">
                        <span class="btn-text">Iniciar sesión</span>
                        <span class="btn-loader" style="display: none;">
                            <svg class="spinner" width="20" height="20" viewBox="0 0 24 24">
                                <circle class="spinner-circle" cx="12" cy="12" r="10" fill="none" stroke-width="3"></circle>
                            </svg>
                        </span>
                    </button>
                </form>

                <div class="fullday-login-divider">
                    <span>o continúa con</span>
                </div>

                <div class="fullday-social-login">
                    <button type="button" class="fullday-social-btn fullday-google-btn" id="fullday-google-login">
                        <svg width="20" height="20" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Google
                    </button>
                    <button type="button" class="fullday-social-btn fullday-facebook-btn" id="fullday-facebook-login">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="#1877F2">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        Facebook
                    </button>
                </div>

                <div class="fullday-login-footer">
                    <p>¿No tienes cuenta? <a href="<?php echo esc_url(home_url('/registro')); ?>">Regístrate aquí</a></p>
                    <p><a href="<?php echo esc_url(wp_lostpassword_url()); ?>">¿Olvidaste tu contraseña?</a></p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler para login de usuario
     */
    public static function ajax_login_user() {
        error_log('=== INICIO LOGIN AJAX ===');
        error_log('POST data: ' . print_r($_POST, true));

        // Verificar nonce
        try {
            check_ajax_referer('fullday_login_nonce', 'nonce');
            error_log('✓ Nonce verificado correctamente');
        } catch (Exception $e) {
            error_log('✗ Error en nonce: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Error de seguridad. Recarga la página e intenta de nuevo.'));
            return;
        }

        $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $remember = isset($_POST['remember_me']) ? true : false;

        error_log('Username: ' . $username);
        error_log('Password length: ' . strlen($password));
        error_log('Remember: ' . ($remember ? 'yes' : 'no'));

        // Validaciones
        if (empty($username) || empty($password)) {
            error_log('✗ Campos vacíos');
            wp_send_json_error(array('message' => 'Por favor completa todos los campos.'));
            return;
        }

        // Intentar login
        $credentials = array(
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $remember
        );

        error_log('Intentando wp_signon...');
        $user = wp_signon($credentials, false);

        if (is_wp_error($user)) {
            error_log('✗ Error en wp_signon: ' . $user->get_error_message());
            wp_send_json_error(array('message' => 'Usuario o contraseña incorrectos.'));
            return;
        }

        error_log('✓ Login exitoso - User ID: ' . $user->ID);

        // Login exitoso
        wp_send_json_success(array(
            'message' => 'Login exitoso.',
            'redirect' => home_url('/dashboard')
        ));

        error_log('=== FIN LOGIN AJAX ===');
    }
}
