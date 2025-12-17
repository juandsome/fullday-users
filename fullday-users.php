<?php
/**
 * Plugin Name: Fullday Users
 * Plugin URI: https://fullday.com
 * Description: Sistema de registro y gestión de usuarios cliente y proveedor para Fullday
 * Version: 1.0.1
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
define('FULLDAY_USERS_VERSION', '1.0.1');
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

        // Localizar script con datos de WordPress
        wp_localize_script('fullday-users-registration', 'fulldayUsers', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fullday_users_nonce'),
            'pluginUrl' => FULLDAY_USERS_PLUGIN_URL
        ));

        wp_localize_script('fullday-users-dashboard-cliente', 'fulldayUsers', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fullday_users_nonce'),
            'pluginUrl' => FULLDAY_USERS_PLUGIN_URL
        ));

        wp_localize_script('fullday-users-dashboard-proveedor', 'fulldayUsers', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fullday_users_nonce'),
            'pluginUrl' => FULLDAY_USERS_PLUGIN_URL
        ));

        wp_localize_script('fullday-users-dashboard-crear-editar', 'fulldayUsers', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fullday_users_nonce'),
            'pluginUrl' => FULLDAY_USERS_PLUGIN_URL
        ));

        wp_localize_script('fullday-users-dashboard-mis-viajes', 'fulldayUsers', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fullday_users_nonce'),
            'pluginUrl' => FULLDAY_USERS_PLUGIN_URL
        ));

        wp_localize_script('fullday-users-login', 'fulldayUsers', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fullday_users_nonce'),
            'pluginUrl' => FULLDAY_USERS_PLUGIN_URL
        ));

        wp_localize_script('fullday-users-favoritos', 'fulldayUsers', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'homeUrl' => home_url(),
            'nonce' => wp_create_nonce('fullday_users_nonce'),
            'pluginUrl' => FULLDAY_USERS_PLUGIN_URL
        ));
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
