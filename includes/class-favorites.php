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
        add_action('wp_ajax_get_favorites', array($this, 'ajax_get_favorites'));

        // Shortcode
        add_shortcode('fullday_favorite_button', array($this, 'favorite_button_shortcode'));
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
            wp_send_json_error(array('message' => 'Debes iniciar sesión para guardar favoritos'));
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
}

// Inicializar
new Fullday_Favorites();
