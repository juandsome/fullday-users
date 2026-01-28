<?php
/**
 * Gestión de Favoritos
 *
 * @package Fullday_Users
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class Fullday_Favorites {

    /**
     * Meta key para almacenar favoritos
     */
    const META_KEY = 'fullday_favorites';

    /**
     * CPT permitido
     */
    const ALLOWED_POST_TYPE = 'full-days';

    /**
     * Constructor
     */
    public function __construct() {
        // AJAX handlers
        add_action('wp_ajax_toggle_favorite', array($this, 'ajax_toggle_favorite'));
        add_action('wp_ajax_nopriv_toggle_favorite', array($this, 'ajax_toggle_favorite'));
        add_action('wp_ajax_get_favorites', array($this, 'ajax_get_favorites'));

        // Shortcode
        add_shortcode('fullday_favorite_button', array($this, 'favorite_button_shortcode'));

        // Hook para agregar el popup de login al footer
        add_action('wp_footer', array($this, 'render_login_popup'));
    }

    /**
     * Agregar un favorito
     *
     * @param int $user_id ID del usuario
     * @param int $post_id ID del post
     * @return bool
     */
    public function add_favorite($user_id, $post_id) {
        // Validar que sea el CPT correcto
        if (get_post_type($post_id) !== self::ALLOWED_POST_TYPE) {
            return false;
        }

        $favorites = $this->get_favorites($user_id);

        // Si ya está en favoritos, no hacer nada
        if (in_array($post_id, $favorites)) {
            return true;
        }

        $favorites[] = $post_id;
        return update_user_meta($user_id, self::META_KEY, $favorites);
    }

    /**
     * Eliminar un favorito
     *
     * @param int $user_id ID del usuario
     * @param int $post_id ID del post
     * @return bool
     */
    public function remove_favorite($user_id, $post_id) {
        $favorites = $this->get_favorites($user_id);

        $key = array_search($post_id, $favorites);
        if ($key !== false) {
            unset($favorites[$key]);
            $favorites = array_values($favorites); // Reindexar
            return update_user_meta($user_id, self::META_KEY, $favorites);
        }

        return true;
    }

    /**
     * Obtener favoritos del usuario
     *
     * @param int $user_id ID del usuario
     * @return array Array de IDs de posts
     */
    public function get_favorites($user_id) {
        $favorites = get_user_meta($user_id, self::META_KEY, true);

        if (!is_array($favorites)) {
            return array();
        }

        return $favorites;
    }

    /**
     * Verificar si un post es favorito
     *
     * @param int $user_id ID del usuario
     * @param int $post_id ID del post
     * @return bool
     */
    public function is_favorite($user_id, $post_id) {
        $favorites = $this->get_favorites($user_id);
        return in_array($post_id, $favorites);
    }

    /**
     * AJAX: Toggle favorito
     */
    public function ajax_toggle_favorite() {
        // Verificar nonce
        if (!check_ajax_referer('fullday_favorites_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Seguridad: Nonce inválido'));
            return;
        }

        // Verificar usuario logueado
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => 'Debes iniciar sesión para guardar favoritos',
                'require_login' => true
            ));
            return;
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $user_id = get_current_user_id();

        if (!$post_id) {
            wp_send_json_error(array('message' => 'ID de post inválido'));
            return;
        }

        // Verificar que el post existe y es del tipo correcto
        if (get_post_type($post_id) !== self::ALLOWED_POST_TYPE) {
            wp_send_json_error(array('message' => 'Este contenido no se puede agregar a favoritos'));
            return;
        }

        // Toggle favorito
        if ($this->is_favorite($user_id, $post_id)) {
            $result = $this->remove_favorite($user_id, $post_id);
            $action = 'removed';
            $message = 'Eliminado de favoritos';
        } else {
            $result = $this->add_favorite($user_id, $post_id);
            $action = 'added';
            $message = 'Agregado a favoritos';
        }

        if ($result) {
            wp_send_json_success(array(
                'message' => $message,
                'action' => $action,
                'is_favorite' => $this->is_favorite($user_id, $post_id)
            ));
        } else {
            wp_send_json_error(array('message' => 'Error al actualizar favoritos'));
        }
    }

    /**
     * AJAX: Obtener favoritos del usuario
     */
    public function ajax_get_favorites() {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'No autorizado'));
            return;
        }

        $user_id = get_current_user_id();
        $favorites = $this->get_favorites($user_id);

        wp_send_json_success(array('favorites' => $favorites));
    }

    /**
     * Shortcode: Botón de favorito
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML del botón
     */
    public function favorite_button_shortcode($atts) {
        // Obtener el post ID del contexto actual
        $post_id = get_the_ID();

        // Si no hay post ID o no es del tipo correcto, no mostrar nada
        if (!$post_id || get_post_type($post_id) !== self::ALLOWED_POST_TYPE) {
            return '';
        }

        // Verificar si el usuario está logueado
        $user_id = get_current_user_id();
        $is_favorite = $user_id ? $this->is_favorite($user_id, $post_id) : false;

        // Generar HTML del botón
        ob_start();
        ?>
        <button type="button"
                class="fullday-favorite-btn <?php echo $is_favorite ? 'is-favorite' : ''; ?>"
                data-post-id="<?php echo esc_attr($post_id); ?>"
                data-nonce="<?php echo wp_create_nonce('fullday_favorites_nonce'); ?>"
                aria-label="<?php echo $is_favorite ? 'Eliminar de favoritos' : 'Agregar a favoritos'; ?>">
            <svg class="heart-outline" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
            <svg class="heart-filled" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
        </button>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar popup de login para usuarios no logueados
     */
    public function render_login_popup() {
        // Solo renderizar si el usuario NO está logueado
        if (is_user_logged_in()) {
            return;
        }
        ?>
        <div id="fullday-favorite-login-popup" class="fullday-popup-overlay" style="display: none;">
            <div class="fullday-popup-container">
                <button type="button" class="fullday-popup-close" aria-label="Cerrar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>

                <div class="fullday-popup-content">
                    <div class="fullday-popup-header">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                        <h2>Inicia sesión para guardar favoritos</h2>
                        <p>Accede a tu cuenta para guardar tus actividades favoritas y verlas cuando quieras.</p>
                    </div>

                    <?php echo do_shortcode('[fullday_login]'); ?>
                </div>
            </div>
        </div>

        <style>
        .fullday-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .fullday-popup-overlay.active {
            opacity: 1;
        }

        .fullday-popup-container {
            background: #fff;
            border-radius: 12px;
            max-width: 480px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }

        .fullday-popup-overlay.active .fullday-popup-container {
            transform: translateY(0);
        }

        .fullday-popup-close {
            position: absolute;
            top: 16px;
            right: 16px;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 8px;
            color: #666;
            z-index: 10;
            transition: color 0.2s ease;
        }

        .fullday-popup-close:hover {
            color: #000;
        }

        .fullday-popup-content {
            padding: 32px;
        }

        .fullday-popup-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .fullday-popup-header svg {
            color: #ff6b6b;
            margin-bottom: 16px;
        }

        .fullday-popup-header h2 {
            font-size: 24px;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0 0 12px 0;
        }

        .fullday-popup-header p {
            font-size: 14px;
            color: #666;
            margin: 0;
            line-height: 1.5;
        }

        /* Ajustar el contenedor de login dentro del popup */
        #fullday-favorite-login-popup .fullday-login-container {
            padding: 0;
            background: transparent;
        }

        #fullday-favorite-login-popup .fullday-login-box {
            background: transparent;
            box-shadow: none;
            padding: 0;
            border-radius: 0;
        }

        #fullday-favorite-login-popup .fullday-login-title {
            display: none;
        }

        #fullday-favorite-login-popup .fullday-login-subtitle {
            display: none;
        }

        /* Responsive */
        @media (max-width: 640px) {
            .fullday-popup-content {
                padding: 24px;
            }

            .fullday-popup-header h2 {
                font-size: 20px;
            }
        }
        </style>

        <script>
        (function() {
            // Cerrar popup al hacer clic en el overlay o botón cerrar
            document.addEventListener('click', function(e) {
                const popup = document.getElementById('fullday-favorite-login-popup');
                if (!popup) return;

                if (e.target.classList.contains('fullday-popup-overlay') ||
                    e.target.closest('.fullday-popup-close')) {
                    popup.style.display = 'none';
                    popup.classList.remove('active');
                }
            });

            // Cerrar con tecla ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const popup = document.getElementById('fullday-favorite-login-popup');
                    if (popup && popup.style.display !== 'none') {
                        popup.style.display = 'none';
                        popup.classList.remove('active');
                    }
                }
            });

            // Función para abrir el popup
            window.openFavoriteLoginPopup = function() {
                const popup = document.getElementById('fullday-favorite-login-popup');
                if (popup) {
                    popup.style.display = 'flex';
                    // Delay para activar la animación
                    setTimeout(function() {
                        popup.classList.add('active');
                    }, 10);
                }
            };

            // Interceptar el evento de login exitoso para cerrar el popup y recargar
            document.addEventListener('fullday_login_success', function() {
                const popup = document.getElementById('fullday-favorite-login-popup');
                if (popup) {
                    popup.style.display = 'none';
                    popup.classList.remove('active');
                }
                // Recargar la página para actualizar el estado de favoritos
                setTimeout(function() {
                    window.location.reload();
                }, 500);
            });
        })();
        </script>
        <?php
    }
}

// Inicializar
new Fullday_Favorites();
