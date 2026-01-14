<?php
/**
 * Gestión de dashboards
 *
 * @package Fullday_Users
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestión de dashboards
 */
class Fullday_Users_Dashboard {

    /**
     * Inicializar
     */
    public static function init() {
        // Registrar shortcode de dashboard
        add_shortcode('fullday_dashboard', array(__CLASS__, 'dashboard_shortcode'));
        add_shortcode('fullday_proveedor_banner', array(__CLASS__, 'proveedor_banner_shortcode'));
        add_shortcode('fullday_author_banner', array(__CLASS__, 'author_banner_shortcode'));

        // AJAX handlers para actualizar perfil
        add_action('wp_ajax_fullday_update_profile', array(__CLASS__, 'ajax_update_profile'));
        add_action('wp_ajax_fullday_update_password', array(__CLASS__, 'ajax_update_password'));
        add_action('wp_ajax_fullday_upload_avatar', array(__CLASS__, 'ajax_upload_avatar'));
        add_action('wp_ajax_fullday_upload_banner', array(__CLASS__, 'ajax_upload_banner'));

        // AJAX handlers para crear Full Days
        add_action('wp_ajax_fullday_upload_fullday_image', array(__CLASS__, 'ajax_upload_fullday_image'));
        add_action('wp_ajax_fullday_create_fullday', array(__CLASS__, 'ajax_create_fullday'));
        add_action('wp_ajax_fullday_save_draft', array(__CLASS__, 'ajax_save_draft'));

        // AJAX handlers para gestión de Full Days
        add_action('wp_ajax_fullday_toggle_status', array(__CLASS__, 'ajax_toggle_status'));
        add_action('wp_ajax_fullday_delete_fullday', array(__CLASS__, 'ajax_delete_fullday'));
        add_action('wp_ajax_fullday_update_fullday', array(__CLASS__, 'ajax_update_fullday'));
        add_action('wp_ajax_fullday_update_availability', array(__CLASS__, 'ajax_update_availability'));

        // AJAX handlers para favoritos
        add_action('wp_ajax_fullday_toggle_favorite', array(__CLASS__, 'ajax_toggle_favorite'));

        // Filtro para integrar avatares personalizados con get_avatar de WordPress
        add_filter('get_avatar_url', array(__CLASS__, 'filter_get_avatar_url'), 10, 3);
        add_filter('get_avatar', array(__CLASS__, 'filter_get_avatar'), 10, 6);
    }

    /**
     * Shortcode de dashboard
     */
    public static function dashboard_shortcode($atts) {
        // Verificar si el usuario está logueado
        if (!is_user_logged_in()) {
            // Mostrar formulario de login
            return Fullday_Users_Registration::login_shortcode($atts);
        }

        $user_id = get_current_user_id();
        $user_type = Fullday_Users_Roles::get_user_type($user_id);

        if (!$user_type) {
            return '<p>No tienes permisos para acceder a este dashboard.</p>';
        }

        ob_start();

        // Cargar el dashboard correspondiente según el tipo de usuario
        if ($user_type === 'cliente') {
            include FULLDAY_USERS_PLUGIN_DIR . 'templates/dashboard/cliente/dashboard-cliente.php';
        } elseif ($user_type === 'proveedor') {
            include FULLDAY_USERS_PLUGIN_DIR . 'templates/dashboard/proveedor/dashboard-proveedor.php';
        }

        return ob_get_clean();
    }

    /**
     * Shortcode de banner público de proveedor
     */
    public static function proveedor_banner_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => isset($_GET['proveedor_id']) ? intval($_GET['proveedor_id']) : 0
        ), $atts);

        $proveedor_id = $atts['id'];

        if (!$proveedor_id || !Fullday_Users_Roles::is_proveedor($proveedor_id)) {
            return '<p>Proveedor no encontrado.</p>';
        }

        $user = get_userdata($proveedor_id);
        $empresa = get_user_meta($proveedor_id, 'empresa', true);
        $facebook_url = get_user_meta($proveedor_id, 'facebook_url', true);
        $instagram_url = get_user_meta($proveedor_id, 'instagram_url', true);
        $avatar_url = self::get_user_avatar($proveedor_id);
        $banner_id = get_user_meta($proveedor_id, 'banner', true);
        $banner_url = $banner_id ? wp_get_attachment_url($banner_id) : '';
        $initials = self::get_user_initials($proveedor_id);

        ob_start();
        ?>
        <div class="fullday-proveedor-banner-public">
            <div class="banner-image-public" style="background-image: url('<?php echo $banner_url ? esc_url($banner_url) : 'https://fulldayvenezuela.com/wp-content/uploads/2026/01/Gemini_Generated_Image_73nd8o73nd8o73nd-1.png'; ?>');">
               
            </div>
            <div class="banner-content-public">
                <div class="banner-avatar-public">
                    <?php if ($avatar_url): ?>
                        <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($empresa); ?>">
                    <?php else: ?>
                        <span class="banner-avatar-initials-public"><?php echo esc_html($initials); ?></span>
                    <?php endif; ?>
                </div>
                <div class="banner-info-public">
                    <h2 class="banner-empresa-public"><?php echo $empresa ? esc_html($empresa) : esc_html($user->display_name); ?></h2>
                    <?php if ($facebook_url || $instagram_url): ?>
                        <div class="banner-social-public">
                            <?php if ($facebook_url): ?>
                                <a href="<?php echo esc_url($facebook_url); ?>" target="_blank" rel="nofollow" class="social-icon-public">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </a>
                            <?php endif; ?>
                            <?php if ($instagram_url): ?>
                                <a href="<?php echo esc_url($instagram_url); ?>" target="_blank" rel="nofollow" class="social-icon-public">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode de banner de autor - Detecta automáticamente el autor de la página
     * Uso: [fullday_author_banner]
     * Este shortcode es ideal para usar en plantillas de autor (author.php)
     */
    public static function author_banner_shortcode($atts) {
        error_log('=== FULLDAY AUTHOR BANNER SHORTCODE ===');
        error_log('Atributos recibidos: ' . print_r($atts, true));

        // Intentar obtener el ID del autor de diferentes maneras
        $proveedor_id = 0;

        // 1. Si estamos en una página de autor
        if (is_author()) {
            $author = get_queried_object();
            if ($author && isset($author->ID)) {
                $proveedor_id = $author->ID;
                error_log('✓ ID detectado desde is_author(): ' . $proveedor_id);
            }
        }

        // 2. Si se está en el loop y hay un post
        if (!$proveedor_id && in_the_loop()) {
            $proveedor_id = get_the_author_meta('ID');
            if ($proveedor_id) {
                error_log('✓ ID detectado desde in_the_loop(): ' . $proveedor_id);
            }
        }

        // 3. Si hay un post global disponible
        if (!$proveedor_id) {
            global $post;
            if ($post && isset($post->post_author)) {
                $proveedor_id = $post->post_author;
                error_log('✓ ID detectado desde post global: ' . $proveedor_id);
            }
        }

        // 4. Permitir override manual con atributo 'id'
        $atts = shortcode_atts(array(
            'id' => $proveedor_id
        ), $atts);

        error_log('Atributos después de shortcode_atts: ' . print_r($atts, true));

        if (!empty($atts['id'])) {
            $proveedor_id = intval($atts['id']);
            error_log('✓ ID desde atributo: ' . $proveedor_id);
        }

        error_log('ID final del proveedor: ' . $proveedor_id);

        // Verificar que tenemos un ID válido
        if (!$proveedor_id) {
            error_log('✗ ERROR: No se pudo detectar el autor');
            return '<p>No se pudo detectar el autor de esta página.</p>';
        }

        // Verificar que el usuario es un proveedor
        $is_proveedor = Fullday_Users_Roles::is_proveedor($proveedor_id);
        error_log('¿Es proveedor? ' . ($is_proveedor ? 'SÍ' : 'NO'));

        if (!$is_proveedor) {
            error_log('✗ El usuario ID ' . $proveedor_id . ' no es proveedor');
            return '<!-- Usuario no es proveedor -->'; // No mostrar nada si no es proveedor
        }

        // Obtener datos del proveedor
        $user = get_userdata($proveedor_id);
        if (!$user) {
            return '<p>Usuario no encontrado.</p>';
        }

        $empresa = get_user_meta($proveedor_id, 'empresa', true);
        $descripcion = get_user_meta($proveedor_id, 'descripcion', true);
        $facebook_url = get_user_meta($proveedor_id, 'facebook_url', true);
        $instagram_url = get_user_meta($proveedor_id, 'instagram_url', true);
        $whatsapp = get_user_meta($proveedor_id, 'whatsapp', true);
        $estado = get_user_meta($proveedor_id, 'estado', true);
        $ciudad = get_user_meta($proveedor_id, 'ciudad', true);
        $avatar_url = self::get_user_avatar($proveedor_id);
        $banner_id = get_user_meta($proveedor_id, 'banner', true);
        $banner_url = $banner_id ? wp_get_attachment_url($banner_id) : '';
        $initials = self::get_user_initials($proveedor_id);

        ob_start();
        ?>
        <div class="fullday-proveedor-banner-public fullday-author-banner">
            <div class="banner-image-public" style="background-image: url('<?php echo $banner_url ? esc_url($banner_url) : 'https://fulldayvenezuela.com/wp-content/uploads/2026/01/Gemini_Generated_Image_73nd8o73nd8o73nd-1.png'; ?>');">
               
            </div>
            <div class="banner-content-public">
                <div class="banner-avatar-public">
                    <?php if ($avatar_url): ?>
                        <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($empresa ? $empresa : $user->display_name); ?>">
                    <?php else: ?>
                        <span class="banner-avatar-initials-public"><?php echo esc_html($initials); ?></span>
                    <?php endif; ?>
                </div>
                <div class="banner-info-public">
                    <h2 class="banner-empresa-public"><?php echo $empresa ? esc_html($empresa) : esc_html($user->display_name); ?></h2>

                    <?php if ($descripcion): ?>
                        <p class="banner-descripcion-public"><?php echo esc_html($descripcion); ?></p>
                    <?php endif; ?>

                    <?php if ($estado || $ciudad): ?>
                        <p class="banner-ubicacion-public">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <?php
                            if ($ciudad && $estado) {
                                echo esc_html($ciudad . ', ' . $estado);
                            } elseif ($ciudad) {
                                echo esc_html($ciudad);
                            } else {
                                echo esc_html($estado);
                            }
                            ?>
                        </p>
                    <?php endif; ?>

                    <div class="banner-actions-public">
                        <?php if ($whatsapp): ?>
                            <a href="https://wa.me/58<?php echo esc_attr(substr($whatsapp, 1)); ?>" target="_blank" rel="nofollow" class="banner-btn-whatsapp">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                                Contactar por WhatsApp
                            </a>
                        <?php endif; ?>

                        <?php if ($facebook_url || $instagram_url): ?>
                            <div class="banner-social-public">
                                <?php if ($facebook_url): ?>
                                    <a href="<?php echo esc_url($facebook_url); ?>" target="_blank" rel="nofollow" class="social-icon-public" title="Facebook">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                                <?php if ($instagram_url): ?>
                                    <a href="<?php echo esc_url($instagram_url); ?>" target="_blank" rel="nofollow" class="social-icon-public" title="Instagram">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler para actualizar perfil
     */
    public static function ajax_update_profile() {
        check_ajax_referer('fullday_users_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Debes iniciar sesión.'));
        }

        $user_id = get_current_user_id();
        $user_type = Fullday_Users_Roles::get_user_type($user_id);

        // LOG: Datos recibidos
        error_log('=== FULLDAY UPDATE PROFILE ===');
        error_log('User ID: ' . $user_id);
        error_log('User Type: ' . $user_type);
        error_log('POST Data: ' . print_r($_POST, true));

        // Datos comunes
        $display_name = sanitize_text_field($_POST['display_name']);
        $estado = sanitize_text_field($_POST['estado']);
        $ciudad = sanitize_text_field($_POST['ciudad']);

        // Actualizar nombre
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $display_name
        ));

        // Actualizar meta data común
        update_user_meta($user_id, 'estado', $estado);
        update_user_meta($user_id, 'ciudad', $ciudad);

        // Datos específicos de cliente
        if ($user_type === 'cliente') {
            $fecha_nacimiento = sanitize_text_field($_POST['fecha_nacimiento']);
            update_user_meta($user_id, 'fecha_nacimiento', $fecha_nacimiento);
        }

        // Datos específicos de proveedor
        if ($user_type === 'proveedor') {
            $empresa = sanitize_text_field($_POST['empresa']);
            $descripcion = sanitize_textarea_field($_POST['descripcion']);
            $facebook_url = isset($_POST['facebook_url']) ? esc_url_raw($_POST['facebook_url']) : '';
            $instagram_url = isset($_POST['instagram_url']) ? esc_url_raw($_POST['instagram_url']) : '';
            $whatsapp = isset($_POST['whatsapp']) ? sanitize_text_field($_POST['whatsapp']) : '';
            $cashea_code = isset($_POST['cashea_code']) ? sanitize_text_field($_POST['cashea_code']) : '';

            // LOG: Datos de proveedor antes de guardar
            error_log('Empresa: ' . $empresa);
            error_log('Descripcion: ' . $descripcion);
            error_log('Facebook URL: ' . $facebook_url);
            error_log('Instagram URL: ' . $instagram_url);
            error_log('WhatsApp: ' . $whatsapp);
            error_log('Cashea Code: ' . $cashea_code);

            update_user_meta($user_id, 'empresa', $empresa);
            update_user_meta($user_id, 'descripcion', $descripcion);
            update_user_meta($user_id, 'facebook_url', $facebook_url);
            update_user_meta($user_id, 'instagram_url', $instagram_url);
            update_user_meta($user_id, 'whatsapp', $whatsapp);
            update_user_meta($user_id, 'cashea_code', $cashea_code);

            // LOG: Verificar que se guardó correctamente
            error_log('Empresa guardada: ' . get_user_meta($user_id, 'empresa', true));
            error_log('Descripcion guardada: ' . get_user_meta($user_id, 'descripcion', true));
            error_log('Facebook guardada: ' . get_user_meta($user_id, 'facebook_url', true));
            error_log('Instagram guardada: ' . get_user_meta($user_id, 'instagram_url', true));
            error_log('WhatsApp guardada: ' . get_user_meta($user_id, 'whatsapp', true));
            error_log('Cashea Code guardada: ' . get_user_meta($user_id, 'cashea_code', true));
        }

        error_log('=== FIN UPDATE PROFILE ===');

        wp_send_json_success(array('message' => 'Perfil actualizado correctamente.'));
    }

    /**
     * AJAX handler para actualizar contraseña
     */
    public static function ajax_update_password() {
        check_ajax_referer('fullday_users_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Debes iniciar sesión.'));
        }

        $user_id = get_current_user_id();
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Verificar contraseña actual
        $user = get_userdata($user_id);
        if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
            wp_send_json_error(array('message' => 'La contraseña actual es incorrecta.'));
        }

        // Validar nueva contraseña
        if (strlen($new_password) < 6) {
            wp_send_json_error(array('message' => 'La nueva contraseña debe tener al menos 6 caracteres.'));
        }

        if ($new_password !== $confirm_password) {
            wp_send_json_error(array('message' => 'Las contraseñas no coinciden.'));
        }

        // Actualizar contraseña
        wp_set_password($new_password, $user_id);

        wp_send_json_success(array('message' => 'Contraseña actualizada correctamente.'));
    }

    /**
     * AJAX handler para upload de avatar
     */
    public static function ajax_upload_avatar() {
        check_ajax_referer('fullday_users_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Debes iniciar sesión.'));
        }

        if (!isset($_FILES['avatar'])) {
            wp_send_json_error(array('message' => 'No se recibió ningún archivo.'));
        }

        $file = $_FILES['avatar'];

        // Validar tipo de archivo
        $allowed_types = array('image/jpeg', 'image/png');
        $file_type = $file['type'];

        if (!in_array($file_type, $allowed_types)) {
            wp_send_json_error(array('message' => 'Tipo de archivo no permitido. Solo JPG o PNG.'));
        }

        // Validar tamaño (2MB max)
        $max_size = 2 * 1024 * 1024; // 2MB en bytes
        if ($file['size'] > $max_size) {
            wp_send_json_error(array('message' => 'El archivo es demasiado grande. Máximo 2MB.'));
        }

        // Subir archivo
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $upload = wp_handle_upload($file, array('test_form' => false));

        if (isset($upload['error'])) {
            wp_send_json_error(array('message' => $upload['error']));
        }

        // Crear attachment
        $attachment = array(
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name($file['name']),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Guardar como avatar del usuario
        $user_id = get_current_user_id();
        update_user_meta($user_id, 'avatar', $attach_id);

        // También guardarlo en el meta estándar para compatibilidad con WordPress
        update_user_meta($user_id, 'wp_user_avatar', $attach_id);
        update_user_meta($user_id, 'simple_local_avatar', array('media_id' => $attach_id));

        wp_send_json_success(array(
            'attachment_id' => $attach_id,
            'url' => $upload['url']
        ));
    }

    /**
     * AJAX handler para upload de banner
     */
    public static function ajax_upload_banner() {
        check_ajax_referer('fullday_users_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Debes iniciar sesión.'));
        }

        if (!isset($_FILES['banner'])) {
            wp_send_json_error(array('message' => 'No se recibió ningún archivo.'));
        }

        $file = $_FILES['banner'];

        // Validar tipo de archivo
        $allowed_types = array('image/jpeg', 'image/png');
        $file_type = $file['type'];

        if (!in_array($file_type, $allowed_types)) {
            wp_send_json_error(array('message' => 'Tipo de archivo no permitido. Solo JPG o PNG.'));
        }

        // Validar tamaño (5MB max)
        $max_size = 5 * 1024 * 1024; // 5MB en bytes
        if ($file['size'] > $max_size) {
            wp_send_json_error(array('message' => 'El archivo es demasiado grande. Máximo 5MB.'));
        }

        // Subir archivo
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $upload = wp_handle_upload($file, array('test_form' => false));

        if (isset($upload['error'])) {
            wp_send_json_error(array('message' => $upload['error']));
        }

        // Crear attachment
        $attachment = array(
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name($file['name']),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Guardar como banner del usuario
        $user_id = get_current_user_id();
        update_user_meta($user_id, 'banner', $attach_id);

        wp_send_json_success(array(
            'attachment_id' => $attach_id,
            'url' => $upload['url']
        ));
    }

    /**
     * Obtener iniciales del usuario
     */
    public static function get_user_initials($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $user = get_userdata($user_id);
        $display_name = $user->display_name ?: $user->user_login;

        $words = explode(' ', $display_name);
        $initials = '';

        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= mb_strtoupper(mb_substr($word, 0, 1));
                if (strlen($initials) >= 2) {
                    break;
                }
            }
        }

        return $initials ?: mb_strtoupper(mb_substr($display_name, 0, 2));
    }

    /**
     * Obtener avatar del usuario
     */
    public static function get_user_avatar($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $avatar_id = get_user_meta($user_id, 'avatar', true);

        if ($avatar_id) {
            return wp_get_attachment_url($avatar_id);
        }

        return false;
    }

    /**
     * AJAX handler para upload de imágenes de Full Day
     */
    public static function ajax_upload_fullday_image() {
        check_ajax_referer('fullday_users_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Debes iniciar sesión.'));
        }

        // Verificar que sea proveedor aprobado
        $user_id = get_current_user_id();
        if (!Fullday_Users_Roles::is_proveedor($user_id)) {
            wp_send_json_error(array('message' => 'No tienes permisos para realizar esta acción.'));
        }

        $approved = get_user_meta($user_id, 'proveedor_approved', true);
        if ($approved !== '1' && $approved !== 1) {
            wp_send_json_error(array('message' => 'Tu cuenta debe estar aprobada.'));
        }

        if (!isset($_FILES['image'])) {
            wp_send_json_error(array('message' => 'No se recibió ningún archivo.'));
        }

        $file = $_FILES['image'];

        // Validar tipo de archivo
        $allowed_types = array('image/jpeg', 'image/png', 'image/jpg');
        $file_type = $file['type'];

        if (!in_array($file_type, $allowed_types)) {
            wp_send_json_error(array('message' => 'Tipo de archivo no permitido. Solo JPG o PNG.'));
        }

        // Validar tamaño (5MB max)
        $max_size = 5 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            wp_send_json_error(array('message' => 'El archivo es demasiado grande. Máximo 5MB.'));
        }

        // Subir archivo
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $upload = wp_handle_upload($file, array('test_form' => false));

        if (isset($upload['error'])) {
            wp_send_json_error(array('message' => $upload['error']));
        }

        // Crear attachment
        $attachment = array(
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name($file['name']),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        wp_send_json_success(array(
            'attachment_id' => $attach_id,
            'url' => $upload['url']
        ));
    }

    /**
     * AJAX handler para crear Full Day
     */
    public static function ajax_create_fullday() {
        check_ajax_referer('fullday_users_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Debes iniciar sesión.'));
        }

        // Verificar que sea proveedor aprobado
        $user_id = get_current_user_id();
        if (!Fullday_Users_Roles::is_proveedor($user_id)) {
            wp_send_json_error(array('message' => 'No tienes permisos para realizar esta acción.'));
        }

        $approved = get_user_meta($user_id, 'proveedor_approved', true);
        if ($approved !== '1' && $approved !== 1) {
            wp_send_json_error(array('message' => 'Tu cuenta debe estar aprobada.'));
        }

        // Validar campos requeridos
        $title = sanitize_text_field($_POST['fullday_title']);
        $price = floatval($_POST['fullday_price']);
        $original_price = isset($_POST['fullday_original_price']) ? floatval($_POST['fullday_original_price']) : 0;
        $description = sanitize_textarea_field($_POST['fullday_description']);
        $destination = sanitize_text_field($_POST['fullday_destination']);
        $departure_date = isset($_POST['fullday_departure_date']) ? sanitize_text_field($_POST['fullday_departure_date']) : '';
        $duration = sanitize_text_field($_POST['fullday_duration']);
        $category = sanitize_text_field($_POST['fullday_category']);
        $max_people = intval($_POST['fullday_max_people']);
        $min_age = isset($_POST['fullday_min_age']) ? intval($_POST['fullday_min_age']) : 0;
        $includes = sanitize_textarea_field($_POST['fullday_includes']);
        $itinerary = sanitize_textarea_field($_POST['fullday_itinerary']);
        $featured_image_id = intval($_POST['featured_image_id']);
        $gallery_images_ids = sanitize_text_field($_POST['gallery_images_ids']);
        $phone_number = isset($_POST['fullday_phone_number']) ? sanitize_text_field($_POST['fullday_phone_number']) : '';
        $whatsapp_message = isset($_POST['fullday_whatsapp_message']) ? sanitize_textarea_field($_POST['fullday_whatsapp_message']) : '';

        if (empty($title) || empty($description) || empty($destination) || empty($duration)) {
            wp_send_json_error(array('message' => 'Por favor completa todos los campos obligatorios.'));
        }

        if ($price <= 0) {
            wp_send_json_error(array('message' => 'El precio debe ser mayor a 0.'));
        }

        if (!$featured_image_id) {
            wp_send_json_error(array('message' => 'Debes seleccionar una imagen destacada.'));
        }

        $gallery_ids_array = !empty($gallery_images_ids) ? explode(',', $gallery_images_ids) : array();
        if (count($gallery_ids_array) < 2) {
            wp_send_json_error(array('message' => 'Debes subir al menos 2 imágenes para la galería.'));
        }

        // Crear el post
        $post_data = array(
            'post_title' => $title,
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => 'full-days',
            'post_author' => $user_id
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => 'Error al crear el Full Day.'));
        }

        // Asignar imagen destacada
        set_post_thumbnail($post_id, $featured_image_id);

        // Calcular descuento si hay precio original
        $discount_percentage = 0;
        if ($original_price > 0 && $price > 0 && $original_price > $price) {
            $discount_amount = $original_price - $price;
            $discount_percentage = round(($discount_amount / $original_price) * 100);
        }

        // Guardar meta fields según README.md del plugin full-days
        update_post_meta($post_id, 'full_days_price', $price);
        update_post_meta($post_id, 'full_days_discount_price', $original_price);
        update_post_meta($post_id, 'full_days_discount_percentage', $discount_percentage);
        update_post_meta($post_id, 'full_days_description', $description);
        update_post_meta($post_id, 'full_days_destination', $destination);
        update_post_meta($post_id, 'full_days_departure_date', $departure_date);
        update_post_meta($post_id, 'full_days_duration', $duration);
        update_post_meta($post_id, 'full_days_max_people', $max_people);
        update_post_meta($post_id, 'full_days_min_age', $min_age);
        update_post_meta($post_id, 'full_days_includes', $includes);
        update_post_meta($post_id, 'full_days_itinerary', $itinerary);

        // Guardar galería como array de URLs
        $gallery_urls = array();
        foreach ($gallery_ids_array as $img_id) {
            $img_id = intval($img_id);
            $url = wp_get_attachment_url($img_id);
            if ($url) {
                $gallery_urls[] = $url;
            }
        }
        update_post_meta($post_id, 'full_days_gallery', $gallery_urls);

        // Asignar categoría
        if (!empty($category)) {
            $categories = array_map('trim', explode(',', $category));
            wp_set_object_terms($post_id, $categories, 'full_days_category');
        }

        // Guardar regiones con orden
        // Formato guardado: array('123' => 1, '456' => 2, '789' => 3)
        // Donde la clave es el term_id y el valor es el orden (1, 2, 3)
        if (!empty($_POST['region_order'])) {
            error_log('=== INICIO ASIGNACIÓN DE REGIONES ===');
            error_log('POST region_order recibido: ' . $_POST['region_order']);

            $region_order_json = sanitize_text_field($_POST['region_order']);
            error_log('region_order_json después de sanitize: ' . $region_order_json);

            // Remover las barras invertidas que WordPress agrega automáticamente
            $region_order_json = stripslashes($region_order_json);
            error_log('region_order_json después de stripslashes: ' . $region_order_json);

            $region_order = json_decode($region_order_json, true);
            error_log('region_order después de json_decode: ' . print_r($region_order, true));
            error_log('Es array? ' . (is_array($region_order) ? 'SI' : 'NO'));
            error_log('Está vacío? ' . (empty($region_order) ? 'SI' : 'NO'));

            if (is_array($region_order) && !empty($region_order)) {
                // Guardar el array de orden como meta
                $meta_result = update_post_meta($post_id, 'region_order', $region_order);
                error_log('Resultado de update_post_meta: ' . ($meta_result ? 'SUCCESS' : 'FAILED'));

                // También asignar las regiones (ciudades) y sus padres (estados) a la taxonomía
                $region_ids = array_map('intval', array_keys($region_order));
                error_log('region_ids (ciudades seleccionadas): ' . print_r($region_ids, true));

                $all_region_ids = array();

                foreach ($region_ids as $region_id) {
                    error_log('Procesando region_id: ' . $region_id);

                    // Agregar la ciudad
                    $all_region_ids[] = $region_id;
                    error_log('Ciudad agregada: ' . $region_id);

                    // Obtener y agregar el estado padre si existe
                    $term = get_term($region_id, 'region');
                    error_log('get_term resultado para ' . $region_id . ': ' . print_r($term, true));

                    if ($term && !is_wp_error($term)) {
                        error_log('Term válido. Parent ID: ' . $term->parent);
                        if ($term->parent > 0) {
                            $all_region_ids[] = $term->parent;
                            error_log('Estado padre agregado: ' . $term->parent);
                        } else {
                            error_log('Este término NO tiene padre (parent = 0)');
                        }
                    } else {
                        if (is_wp_error($term)) {
                            error_log('ERROR en get_term: ' . $term->get_error_message());
                        } else {
                            error_log('Term no válido o vacío');
                        }
                    }
                }

                // Eliminar duplicados y asignar todas las regiones (ciudades + estados)
                $all_region_ids = array_unique($all_region_ids);
                error_log('all_region_ids final (después de unique): ' . print_r($all_region_ids, true));
                error_log('Post ID para asignación: ' . $post_id);
                error_log('Taxonomía: region');

                $result = wp_set_object_terms($post_id, $all_region_ids, 'region');
                error_log('Resultado de wp_set_object_terms: ' . print_r($result, true));

                if (is_wp_error($result)) {
                    error_log('ERROR en wp_set_object_terms: ' . $result->get_error_message());
                } else {
                    error_log('wp_set_object_terms ejecutado correctamente');
                    // Verificar que se asignaron
                    $assigned_terms = wp_get_object_terms($post_id, 'region');
                    error_log('Términos asignados después de wp_set_object_terms: ' . print_r($assigned_terms, true));
                }
            } else {
                error_log('region_order NO es array o está vacío - no se procesó');
            }
            error_log('=== FIN ASIGNACIÓN DE REGIONES ===');
        } else {
            error_log('=== NO SE RECIBIÓ region_order en POST ===');
        }

        /**
         * EJEMPLO DE RECUPERACIÓN EN FRONTEND:
         *
         * $region_order = get_post_meta($post_id, 'region_order', true);
         * // Resultado: array('123' => 1, '456' => 2, '789' => 3)
         *
         * // Ordenar por valor (orden de parada)
         * asort($region_order);
         *
         * // Mostrar en orden
         * foreach ($region_order as $term_id => $order) {
         *     $term = get_term($term_id, 'region');
         *     echo $order . '. ' . $term->name . '<br>';
         * }
         *
         * // Output esperado:
         * // 1. Maracaibo
         * // 2. Cabimas
         * // 3. Ojeda
         */

        // Guardar teléfono y mensaje WhatsApp del formulario
        if (!empty($phone_number)) {
            update_post_meta($post_id, 'full_days_phone_number', $phone_number);
        }

        if (!empty($whatsapp_message)) {
            update_post_meta($post_id, 'full_days_whatsapp_message', $whatsapp_message);
        }

        // Obtener redes sociales del proveedor
        $instagram = get_user_meta($user_id, 'instagram_url', true);
        if (!empty($instagram)) {
            update_post_meta($post_id, 'full_days_instagram', $instagram);
        }

        $facebook = get_user_meta($user_id, 'facebook_url', true);
        if (!empty($facebook)) {
            update_post_meta($post_id, 'full_days_facebook', $facebook);
        }

        wp_send_json_success(array(
            'message' => 'Full Day creado exitosamente.',
            'post_id' => $post_id
        ));
    }

    /**
     * AJAX handler para guardar borrador
     */
    public static function ajax_save_draft() {
        check_ajax_referer('fullday_users_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Debes iniciar sesión.'));
        }

        $user_id = get_current_user_id();
        if (!Fullday_Users_Roles::is_proveedor($user_id)) {
            wp_send_json_error(array('message' => 'No tienes permisos para realizar esta acción.'));
        }

        // Obtener datos del formulario
        $title = sanitize_text_field($_POST['fullday_title']);

        if (empty($title)) {
            wp_send_json_error(array('message' => 'El título es obligatorio para guardar el borrador.'));
        }

        // Crear post como borrador
        $post_data = array(
            'post_title' => $title,
            'post_status' => 'draft',
            'post_type' => 'full-days',
            'post_author' => $user_id
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => 'Error al guardar el borrador.'));
        }

        // Guardar los campos disponibles
        if (!empty($_POST['fullday_price'])) {
            $price = floatval($_POST['fullday_price']);
            $original_price = isset($_POST['fullday_original_price']) ? floatval($_POST['fullday_original_price']) : 0;

            update_post_meta($post_id, 'full_days_price', $price);
            update_post_meta($post_id, 'full_days_discount_price', $original_price);

            // Calcular descuento
            $discount_percentage = 0;
            if ($original_price > 0 && $price > 0 && $original_price > $price) {
                $discount_amount = $original_price - $price;
                $discount_percentage = round(($discount_amount / $original_price) * 100);
            }
            update_post_meta($post_id, 'full_days_discount_percentage', $discount_percentage);
        }

        if (!empty($_POST['fullday_description'])) {
            update_post_meta($post_id, 'full_days_description', sanitize_textarea_field($_POST['fullday_description']));
        }

        if (!empty($_POST['fullday_destination'])) {
            update_post_meta($post_id, 'full_days_destination', sanitize_text_field($_POST['fullday_destination']));
        }

        if (!empty($_POST['fullday_duration'])) {
            update_post_meta($post_id, 'full_days_duration', sanitize_text_field($_POST['fullday_duration']));
        }

        if (isset($_POST['fullday_min_age'])) {
            update_post_meta($post_id, 'full_days_min_age', intval($_POST['fullday_min_age']));
        }

        if (!empty($_POST['fullday_category'])) {
            update_post_meta($post_id, 'full_days_category', sanitize_text_field($_POST['fullday_category']));
        }

        if (!empty($_POST['fullday_max_people'])) {
            update_post_meta($post_id, 'full_days_max_people', intval($_POST['fullday_max_people']));
        }

        if (!empty($_POST['fullday_includes'])) {
            update_post_meta($post_id, 'full_days_includes', sanitize_textarea_field($_POST['fullday_includes']));
        }

        if (!empty($_POST['fullday_itinerary'])) {
            update_post_meta($post_id, 'full_days_itinerary', sanitize_textarea_field($_POST['fullday_itinerary']));
        }

        if (!empty($_POST['fullday_phone_number'])) {
            update_post_meta($post_id, 'full_days_phone_number', sanitize_text_field($_POST['fullday_phone_number']));
        }

        if (!empty($_POST['fullday_whatsapp_message'])) {
            update_post_meta($post_id, 'full_days_whatsapp_message', sanitize_textarea_field($_POST['fullday_whatsapp_message']));
        }

        if (!empty($_POST['featured_image_id'])) {
            set_post_thumbnail($post_id, intval($_POST['featured_image_id']));
        }

        if (!empty($_POST['gallery_images_ids'])) {
            $gallery_ids = array_map('intval', explode(',', $_POST['gallery_images_ids']));
            $gallery_urls = array();
            foreach ($gallery_ids as $image_id) {
                $url = wp_get_attachment_url($image_id);
                if ($url) {
                    $gallery_urls[] = $url;
                }
            }
            update_post_meta($post_id, 'full_days_gallery', $gallery_urls);
        }

        wp_send_json_success(array(
            'message' => 'Borrador guardado correctamente.',
            'post_id' => $post_id
        ));
    }

    /**
     * AJAX: Toggle status de Full Day (publish/draft)
     */
    public static function ajax_toggle_status() {
        check_ajax_referer('fullday_users_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Debes iniciar sesión.'));
        }

        $user_id = get_current_user_id();
        $post_id = intval($_POST['post_id']);
        $new_status = sanitize_text_field($_POST['new_status']);

        // Validar que el post existe
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'full-days') {
            wp_send_json_error(array('message' => 'Full Day no encontrado.'));
        }

        // Verificar que el usuario es el autor
        if ($post->post_author != $user_id) {
            wp_send_json_error(array('message' => 'No tienes permiso para modificar este Full Day.'));
        }

        // Validar nuevo estado
        if (!in_array($new_status, array('publish', 'draft'))) {
            wp_send_json_error(array('message' => 'Estado inválido.'));
        }

        // Actualizar estado
        $updated = wp_update_post(array(
            'ID' => $post_id,
            'post_status' => $new_status
        ));

        if (is_wp_error($updated)) {
            wp_send_json_error(array('message' => 'Error al actualizar el estado.'));
        }

        wp_send_json_success(array(
            'message' => $new_status === 'publish' ? 'Full Day activado' : 'Full Day pausado',
            'new_status' => $new_status
        ));
    }

    /**
     * AJAX: Eliminar Full Day
     */
    public static function ajax_delete_fullday() {
        check_ajax_referer('fullday_users_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Debes iniciar sesión.'));
        }

        $user_id = get_current_user_id();
        $post_id = intval($_POST['post_id']);

        // Validar que el post existe
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'full-days') {
            wp_send_json_error(array('message' => 'Full Day no encontrado.'));
        }

        // Verificar que el usuario es el autor
        if ($post->post_author != $user_id) {
            wp_send_json_error(array('message' => 'No tienes permiso para eliminar este Full Day.'));
        }

        // Eliminar post (mover a trash)
        $deleted = wp_trash_post($post_id);

        if (!$deleted) {
            wp_send_json_error(array('message' => 'Error al eliminar el Full Day.'));
        }

        wp_send_json_success(array('message' => 'Full Day eliminado correctamente.'));
    }

    /**
     * AJAX: Actualizar disponibilidad de cupos
     */
    public static function ajax_update_availability() {
        // LOG: Inicio de función
        error_log('=== AJAX UPDATE AVAILABILITY ===');
        error_log('POST data: ' . print_r($_POST, true));

        check_ajax_referer('fullday_users_nonce', 'nonce');

        if (!is_user_logged_in()) {
            error_log('ERROR: Usuario no logueado');
            wp_send_json_error(array('message' => 'Debes iniciar sesión.'));
        }

        $user_id = get_current_user_id();
        $post_id = intval($_POST['post_id']);
        $available_spots = intval($_POST['available_spots']);

        error_log('User ID: ' . $user_id);
        error_log('Post ID: ' . $post_id);
        error_log('Available spots: ' . $available_spots);

        // Validar que el post existe
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'full-days') {
            wp_send_json_error(array('message' => 'Full Day no encontrado.'));
        }

        // Verificar que el usuario es el autor
        if ($post->post_author != $user_id) {
            wp_send_json_error(array('message' => 'No tienes permiso para modificar este Full Day.'));
        }

        // Obtener máximo de personas
        $max_people = intval(get_post_meta($post_id, 'full_days_max_people', true));

        // Validar que no exceda el máximo
        if ($available_spots < 0 || $available_spots > $max_people) {
            wp_send_json_error(array('message' => 'Valor inválido.'));
        }

        // Actualizar disponibilidad
        update_post_meta($post_id, 'full_days_available_spots', $available_spots);

        error_log('Meta actualizada correctamente');
        error_log('=== FIN AJAX UPDATE AVAILABILITY ===');

        wp_send_json_success(array(
            'message' => 'Disponibilidad actualizada.',
            'available_spots' => $available_spots
        ));
    }

    /**
     * AJAX: Actualizar Full Day existente
     */
    public static function ajax_update_fullday() {
        error_log('=== INICIO AJAX UPDATE FULLDAY ===');
        error_log('POST data keys: ' . print_r(array_keys($_POST), true));

        check_ajax_referer('fullday_users_nonce', 'nonce');

        if (!is_user_logged_in()) {
            error_log('ERROR: Usuario no logueado');
            wp_send_json_error(array('message' => 'Debes iniciar sesión.'));
        }

        $user_id = get_current_user_id();
        error_log('User ID: ' . $user_id);

        $post_id = intval($_POST['edit_post_id']);
        error_log('Post ID to edit: ' . $post_id);

        // Validar que el post existe
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'full-days') {
            wp_send_json_error(array('message' => 'Full Day no encontrado.'));
        }

        // Verificar que el usuario es el autor
        if ($post->post_author != $user_id) {
            wp_send_json_error(array('message' => 'No tienes permiso para editar este Full Day.'));
        }

        // Obtener datos del formulario
        $title = sanitize_text_field($_POST['fullday_title']);
        $price = floatval($_POST['fullday_price']);
        $original_price = isset($_POST['fullday_original_price']) ? floatval($_POST['fullday_original_price']) : 0;
        $description = sanitize_textarea_field($_POST['fullday_description']);
        $destination = sanitize_text_field($_POST['fullday_destination']);
        $departure_date = isset($_POST['fullday_departure_date']) ? sanitize_text_field($_POST['fullday_departure_date']) : '';
        $duration = sanitize_text_field($_POST['fullday_duration']);
        $category = sanitize_text_field($_POST['fullday_category']);
        $max_people = intval($_POST['fullday_max_people']);
        $min_age = isset($_POST['fullday_min_age']) ? intval($_POST['fullday_min_age']) : 0;
        $includes = sanitize_textarea_field($_POST['fullday_includes']);
        $itinerary = sanitize_textarea_field($_POST['fullday_itinerary']);
        $featured_image_id = intval($_POST['featured_image_id']);
        $gallery_images_ids = sanitize_text_field($_POST['gallery_images_ids']);
        $phone_number = isset($_POST['fullday_phone_number']) ? sanitize_text_field($_POST['fullday_phone_number']) : '';
        $whatsapp_message = isset($_POST['fullday_whatsapp_message']) ? sanitize_textarea_field($_POST['fullday_whatsapp_message']) : '';

        // Validar campos requeridos
        if (empty($title) || empty($description) || empty($destination) || empty($duration)) {
            wp_send_json_error(array('message' => 'Por favor completa todos los campos obligatorios.'));
        }

        if ($price <= 0) {
            wp_send_json_error(array('message' => 'El precio debe ser mayor a 0.'));
        }

        // Actualizar el post
        $updated = wp_update_post(array(
            'ID' => $post_id,
            'post_title' => $title
        ));

        if (is_wp_error($updated)) {
            wp_send_json_error(array('message' => 'Error al actualizar el Full Day.'));
        }

        // Actualizar imagen destacada
        if ($featured_image_id) {
            set_post_thumbnail($post_id, $featured_image_id);
        }

        // Calcular descuento
        $discount_percentage = 0;
        if ($original_price > 0 && $price > 0 && $original_price > $price) {
            $discount_amount = $original_price - $price;
            $discount_percentage = round(($discount_amount / $original_price) * 100);
        }

        // Actualizar meta fields
        update_post_meta($post_id, 'full_days_price', $price);
        update_post_meta($post_id, 'full_days_discount_price', $original_price);
        update_post_meta($post_id, 'full_days_discount_percentage', $discount_percentage);
        update_post_meta($post_id, 'full_days_description', $description);
        update_post_meta($post_id, 'full_days_destination', $destination);
        update_post_meta($post_id, 'full_days_departure_date', $departure_date);
        update_post_meta($post_id, 'full_days_duration', $duration);
        update_post_meta($post_id, 'full_days_max_people', $max_people);
        update_post_meta($post_id, 'full_days_min_age', $min_age);
        update_post_meta($post_id, 'full_days_includes', $includes);
        update_post_meta($post_id, 'full_days_itinerary', $itinerary);

        // Guardar teléfono y mensaje WhatsApp
        if (!empty($phone_number)) {
            update_post_meta($post_id, 'full_days_phone_number', $phone_number);
        } else {
            delete_post_meta($post_id, 'full_days_phone_number');
        }

        if (!empty($whatsapp_message)) {
            update_post_meta($post_id, 'full_days_whatsapp_message', $whatsapp_message);
        } else {
            delete_post_meta($post_id, 'full_days_whatsapp_message');
        }

        // Actualizar galería
        if (!empty($gallery_images_ids)) {
            $gallery_items = explode(',', $gallery_images_ids);
            $gallery_urls = array();

            foreach ($gallery_items as $item) {
                $item = trim($item);
                // Si es una URL existente
                if (strpos($item, 'url:') === 0) {
                    $url = substr($item, 4);
                    $gallery_urls[] = $url;
                } else {
                    // Si es un ID nuevo
                    $img_id = intval($item);
                    $url = wp_get_attachment_url($img_id);
                    if ($url) {
                        $gallery_urls[] = $url;
                    }
                }
            }

            update_post_meta($post_id, 'full_days_gallery', $gallery_urls);
        }

        // Actualizar categoría
        if (!empty($category)) {
            $categories = array_map('trim', explode(',', $category));
            wp_set_object_terms($post_id, $categories, 'full_days_category');
        }

        // Actualizar regiones con orden
        // Formato guardado: array('123' => 1, '456' => 2, '789' => 3)
        if (!empty($_POST['region_order'])) {
            error_log('=== INICIO ACTUALIZACIÓN DE REGIONES ===');
            error_log('POST region_order recibido: ' . $_POST['region_order']);

            $region_order_json = sanitize_text_field($_POST['region_order']);
            error_log('region_order_json después de sanitize: ' . $region_order_json);

            // Remover las barras invertidas que WordPress agrega automáticamente
            $region_order_json = stripslashes($region_order_json);
            error_log('region_order_json después de stripslashes: ' . $region_order_json);

            $region_order = json_decode($region_order_json, true);
            error_log('region_order después de json_decode: ' . print_r($region_order, true));
            error_log('Es array? ' . (is_array($region_order) ? 'SI' : 'NO'));
            error_log('Está vacío? ' . (empty($region_order) ? 'SI' : 'NO'));

            if (is_array($region_order) && !empty($region_order)) {
                // Guardar el array de orden como meta
                $meta_result = update_post_meta($post_id, 'region_order', $region_order);
                error_log('Resultado de update_post_meta: ' . ($meta_result ? 'SUCCESS' : 'FAILED'));

                // También asignar las regiones (ciudades) y sus padres (estados) a la taxonomía
                $region_ids = array_map('intval', array_keys($region_order));
                error_log('region_ids (ciudades seleccionadas): ' . print_r($region_ids, true));

                $all_region_ids = array();

                foreach ($region_ids as $region_id) {
                    error_log('Procesando region_id: ' . $region_id);

                    // Agregar la ciudad
                    $all_region_ids[] = $region_id;
                    error_log('Ciudad agregada: ' . $region_id);

                    // Obtener y agregar el estado padre si existe
                    $term = get_term($region_id, 'region');
                    error_log('get_term resultado para ' . $region_id . ': ' . print_r($term, true));

                    if ($term && !is_wp_error($term)) {
                        error_log('Term válido. Parent ID: ' . $term->parent);
                        if ($term->parent > 0) {
                            $all_region_ids[] = $term->parent;
                            error_log('Estado padre agregado: ' . $term->parent);
                        } else {
                            error_log('Este término NO tiene padre (parent = 0)');
                        }
                    } else {
                        if (is_wp_error($term)) {
                            error_log('ERROR en get_term: ' . $term->get_error_message());
                        } else {
                            error_log('Term no válido o vacío');
                        }
                    }
                }

                // Eliminar duplicados y asignar todas las regiones (ciudades + estados)
                $all_region_ids = array_unique($all_region_ids);
                error_log('all_region_ids final (después de unique): ' . print_r($all_region_ids, true));
                error_log('Post ID para asignación: ' . $post_id);
                error_log('Taxonomía: region');

                $result = wp_set_object_terms($post_id, $all_region_ids, 'region');
                error_log('Resultado de wp_set_object_terms: ' . print_r($result, true));

                if (is_wp_error($result)) {
                    error_log('ERROR en wp_set_object_terms: ' . $result->get_error_message());
                } else {
                    error_log('wp_set_object_terms ejecutado correctamente');
                    // Verificar que se asignaron
                    $assigned_terms = wp_get_object_terms($post_id, 'region');
                    error_log('Términos asignados después de wp_set_object_terms: ' . print_r($assigned_terms, true));
                }
            } else {
                error_log('region_order NO es array o está vacío - no se procesó');
            }
            error_log('=== FIN ACTUALIZACIÓN DE REGIONES ===');
        } else {
            // Si no hay regiones, limpiar
            error_log('=== NO SE RECIBIÓ region_order en POST - Limpiando regiones ===');
            delete_post_meta($post_id, 'region_order');
            wp_set_object_terms($post_id, array(), 'region');
        }

        error_log('Full Day actualizado exitosamente');
        error_log('=== FIN AJAX UPDATE FULLDAY ===');

        wp_send_json_success(array(
            'message' => 'Full Day actualizado exitosamente.',
            'post_id' => $post_id
        ));
    }

    /**
     * AJAX handler para agregar/quitar favoritos
     */
    public static function ajax_toggle_favorite() {
        check_ajax_referer('fullday_users_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Debes iniciar sesión.'));
        }

        $user_id = get_current_user_id();
        $post_id = intval($_POST['post_id']);

        // Verificar que el post existe
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'full-days') {
            wp_send_json_error(array('message' => 'Full Day no encontrado.'));
        }

        // Obtener favoritos actuales
        $favoritos = get_user_meta($user_id, 'fullday_favoritos', true);
        if (!is_array($favoritos)) {
            $favoritos = array();
        }

        // Toggle favorito
        $is_favorite = false;
        if (in_array($post_id, $favoritos)) {
            // Quitar de favoritos
            $favoritos = array_diff($favoritos, array($post_id));
            $message = 'Quitado de favoritos';
        } else {
            // Agregar a favoritos
            $favoritos[] = $post_id;
            $is_favorite = true;
            $message = 'Agregado a favoritos';
        }

        // Actualizar meta
        update_user_meta($user_id, 'fullday_favoritos', array_values($favoritos));

        wp_send_json_success(array(
            'message' => $message,
            'is_favorite' => $is_favorite,
            'total_favorites' => count($favoritos)
        ));
    }

    /**
     * Filtro para get_avatar_url - reemplaza la URL del avatar
     */
    public static function filter_get_avatar_url($url, $id_or_email, $args) {
        // Obtener el user ID
        $user_id = null;
        if (is_numeric($id_or_email)) {
            $user_id = (int) $id_or_email;
        } elseif (is_object($id_or_email) && isset($id_or_email->user_id)) {
            $user_id = (int) $id_or_email->user_id;
        } elseif (is_object($id_or_email) && isset($id_or_email->ID)) {
            $user_id = (int) $id_or_email->ID;
        } elseif (is_string($id_or_email) && is_email($id_or_email)) {
            $user = get_user_by('email', $id_or_email);
            if ($user) {
                $user_id = $user->ID;
            }
        }

        if (!$user_id) {
            return $url;
        }

        // Obtener avatar personalizado
        $avatar_url = self::get_user_avatar($user_id);

        if ($avatar_url) {
            return $avatar_url;
        }

        return $url;
    }

    /**
     * Filtro para get_avatar - reemplaza el HTML completo del avatar
     */
    public static function filter_get_avatar($avatar, $id_or_email, $size, $default, $alt, $args) {
        // Obtener el user ID
        $user_id = null;
        if (is_numeric($id_or_email)) {
            $user_id = (int) $id_or_email;
        } elseif (is_object($id_or_email) && isset($id_or_email->user_id)) {
            $user_id = (int) $id_or_email->user_id;
        } elseif (is_object($id_or_email) && isset($id_or_email->ID)) {
            $user_id = (int) $id_or_email->ID;
        } elseif (is_string($id_or_email) && is_email($id_or_email)) {
            $user = get_user_by('email', $id_or_email);
            if ($user) {
                $user_id = $user->ID;
            }
        }

        if (!$user_id) {
            return $avatar;
        }

        // Obtener avatar personalizado
        $avatar_url = self::get_user_avatar($user_id);

        if (!$avatar_url) {
            return $avatar;
        }

        // Generar HTML del avatar
        $class = array('avatar', 'avatar-' . (int) $size, 'photo');

        if ($args['class']) {
            if (is_array($args['class'])) {
                $class = array_merge($class, $args['class']);
            } else {
                $class[] = $args['class'];
            }
        }

        $avatar = sprintf(
            "<img alt='%s' src='%s' srcset='%s' class='%s' height='%d' width='%d' %s/>",
            esc_attr($alt),
            esc_url($avatar_url),
            esc_url($avatar_url) . ' 2x',
            esc_attr(join(' ', $class)),
            (int) $size,
            (int) $size,
            $args['extra_attr']
        );

        return $avatar;
    }
}
