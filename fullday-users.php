<?php
/**
 * Plugin Name: Fullday Users
 * Plugin URI: https://fullday.com
 * Description: Sistema de registro y gestión de usuarios cliente y proveedor para Fullday
 * Version: 1.0.2
 * Author: Fullday Team
 * Author URI: https://fullday.com
 * Text Domain: fullday-users
 * Domain Path: /languages
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('FULLDAY_USERS_VERSION', '1.0.2');
define('FULLDAY_USERS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FULLDAY_USERS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FULLDAY_USERS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del plugin
 */
class Fullday_Users_Plugin {

    /**
     * Instancia única del plugin
     */
    private static $instance = null;

    /**
     * Obtener instancia única
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Cargar dependencias
     */
    private function load_dependencies() {
        require_once FULLDAY_USERS_PLUGIN_DIR . 'includes/class-roles.php';
        require_once FULLDAY_USERS_PLUGIN_DIR . 'includes/class-registration.php';
        require_once FULLDAY_USERS_PLUGIN_DIR . 'includes/class-dashboard.php';
        require_once FULLDAY_USERS_PLUGIN_DIR . 'includes/class-locations.php';
        require_once FULLDAY_USERS_PLUGIN_DIR . 'includes/class-admin.php';
        require_once FULLDAY_USERS_PLUGIN_DIR . 'includes/class-favorites.php';
        require_once FULLDAY_USERS_PLUGIN_DIR . 'includes/class-ai-chat.php';
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Hook de activación
        register_activation_hook(__FILE__, array($this, 'activate'));

        // Hook de desactivación
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Inicializar componentes
        add_action('plugins_loaded', array($this, 'init_components'));

        // Cargar assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Activación del plugin
     */
    public function activate() {
        // Crear roles
        Fullday_Users_Roles::create_roles();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Desactivación del plugin
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Inicializar componentes
     */
    public function init_components() {
        Fullday_Users_Roles::init();
        Fullday_Users_Registration::init();
        Fullday_Users_Dashboard::init();
        Fullday_Users_Locations::init();
        Fullday_AI_Chat::init();

        // Inicializar admin solo en el backend
        if (is_admin()) {
            Fullday_Users_Admin::init();
        }
    }

    /**
     * Cargar scripts y estilos
     */
    public function enqueue_scripts() {
        // CSS
        wp_enqueue_style(
            'fullday-users-registration',
            FULLDAY_USERS_PLUGIN_URL . 'assets/css/registration.css',
            array(),
            FULLDAY_USERS_VERSION
        );

        wp_enqueue_style(
            'fullday-users-dashboard-cliente',
            FULLDAY_USERS_PLUGIN_URL . 'assets/css/dashboard-cliente.css',
            array(),
            FULLDAY_USERS_VERSION
        );

        wp_enqueue_style(
            'fullday-users-dashboard-proveedor',
            FULLDAY_USERS_PLUGIN_URL . 'assets/css/dashboard-proveedor.css',
            array(),
            FULLDAY_USERS_VERSION
        );

        wp_enqueue_style(
            'fullday-users-proveedor-banner',
            FULLDAY_USERS_PLUGIN_URL . 'assets/css/proveedor-banner.css',
            array(),
            FULLDAY_USERS_VERSION
        );

        wp_enqueue_style(
            'fullday-users-dashboard-crear',
            FULLDAY_USERS_PLUGIN_URL . 'assets/css/dashboard-crear.css',
            array(),
            FULLDAY_USERS_VERSION
        );

        wp_enqueue_style(
            'fullday-users-dashboard-mis-viajes',
            FULLDAY_USERS_PLUGIN_URL . 'assets/css/dashboard-mis-viajes.css',
            array(),
            FULLDAY_USERS_VERSION
        );

        wp_enqueue_style(
            'fullday-users-favoritos',
            FULLDAY_USERS_PLUGIN_URL . 'assets/css/favoritos.css',
            array(),
            FULLDAY_USERS_VERSION
        );

        wp_enqueue_style(
            'fullday-users-ai-chat',
            FULLDAY_USERS_PLUGIN_URL . 'assets/css/dashboard-ai-chat.css',
            array(),
            FULLDAY_USERS_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'fullday-users-registration',
            FULLDAY_USERS_PLUGIN_URL . 'assets/js/registration.js',
            array('jquery'),
            FULLDAY_USERS_VERSION,
            true
        );

        wp_enqueue_script(
            'fullday-users-dashboard-cliente',
            FULLDAY_USERS_PLUGIN_URL . 'assets/js/dashboard-cliente.js',
            array('jquery'),
            FULLDAY_USERS_VERSION,
            true
        );

        wp_enqueue_script(
            'fullday-users-dashboard-proveedor',
            FULLDAY_USERS_PLUGIN_URL . 'assets/js/dashboard-proveedor.js',
            array('jquery'),
            FULLDAY_USERS_VERSION,
            true
        );

        wp_enqueue_script(
            'fullday-users-dashboard-crear-editar',
            FULLDAY_USERS_PLUGIN_URL . 'assets/js/dashboard-crear-editar.js',
            array('jquery'),
            FULLDAY_USERS_VERSION,
            true
        );

        wp_enqueue_script(
            'fullday-users-dashboard-crear-editar',
            FULLDAY_USERS_PLUGIN_URL . 'assets/js/dashboard-crear-editar.js',
            array('jquery'),
            FULLDAY_USERS_VERSION,
            true
        );

        wp_enqueue_script(
            'fullday-users-dashboard-mis-viajes',
            FULLDAY_USERS_PLUGIN_URL . 'assets/js/dashboard-mis-viajes.js',
            array('jquery'),
            FULLDAY_USERS_VERSION,
            true
        );

        wp_enqueue_script(
            'fullday-users-login',
            FULLDAY_USERS_PLUGIN_URL . 'assets/js/login.js',
            array('jquery'),
            FULLDAY_USERS_VERSION,
            true
        );

        wp_enqueue_script(
            'fullday-users-favoritos',
            FULLDAY_USERS_PLUGIN_URL . 'assets/js/favoritos.js',
            array('jquery'),
            FULLDAY_USERS_VERSION,
            true
        );

        wp_enqueue_script(
            'fullday-users-ai-chat',
            FULLDAY_USERS_PLUGIN_URL . 'assets/js/dashboard-ai-chat.js',
            array('jquery'),
            FULLDAY_USERS_VERSION,
            true
        );

        // Datos compartidos para todos los scripts
        $shared_data = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'ajaxUrl' => admin_url('admin-ajax.php'), // Ambas versiones por compatibilidad
            'nonce' => wp_create_nonce('fullday_users_nonce'),
            'pluginUrl' => FULLDAY_USERS_PLUGIN_URL,
            'homeUrl' => home_url(),
            'loginUrl' => home_url('/login')
        );

        // Datos adicionales para el chat de IA
        $avatar_id = get_option('fullday_ai_avatar', '');
        $user_id = get_current_user_id();
        $empresa = get_user_meta($user_id, 'empresa', true);
        $user = get_userdata($user_id);
        $nombre_proveedor = !empty($empresa) ? $empresa : ($user ? $user->display_name : '');

        $ai_data = array_merge($shared_data, array(
            'fullyAvatarUrl' => $avatar_id ? wp_get_attachment_url($avatar_id) : '',
            'typingMessages' => $this->get_typing_messages_array(),
            'proveedorNombre' => $nombre_proveedor
        ));

        // Localizar script con datos de WordPress
        wp_localize_script('fullday-users-registration', 'fulldayUsers', $shared_data);
        wp_localize_script('fullday-users-dashboard-cliente', 'fulldayUsers', $shared_data);
        wp_localize_script('fullday-users-dashboard-proveedor', 'fulldayUsers', $shared_data);
        wp_localize_script('fullday-users-dashboard-crear-editar', 'fulldayUsers', $shared_data);
        wp_localize_script('fullday-users-dashboard-mis-viajes', 'fulldayUsers', $shared_data);
        wp_localize_script('fullday-users-login', 'fulldayUsers', $shared_data);
        wp_localize_script('fullday-users-favoritos', 'fulldayUsers', $shared_data);
        wp_localize_script('fullday-users-ai-chat', 'fulldayUsers', $ai_data);
    }

    /**
     * Obtener mensajes de escritura como array
     */
    private function get_typing_messages_array() {
        $messages = get_option('fullday_ai_typing_messages', '');
        if (empty($messages)) {
            $messages = "Fully está pensando... 🤔\nFully está organizando las ideas... 💡\nFully está buscando las palabras perfectas... ✨\nFully está preparando algo genial... 🌟\nFully está armando la respuesta... 🎨\nFully está consultando su libreta... 📝\nFully está conectando los puntos... 🔗\nFully está poniendo todo bonito... 🎯\nFully está casi listo... ⏰\nFully está terminando los detalles... 🎁";
        }
        $lines = explode("\n", $messages);
        return array_values(array_filter(array_map('trim', $lines)));
    }
}

/**
 * Inicializar el plugin
 */
function fullday_users_init() {
    return Fullday_Users_Plugin::get_instance();
}

// Iniciar el plugin
fullday_users_init();
