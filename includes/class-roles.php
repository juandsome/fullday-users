<?php
/**
 * Gestión de roles de usuario
 *
 * @package Fullday_Users
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestión de roles
 */
class Fullday_Users_Roles {

    /**
     * Inicializar
     */
    public static function init() {
        // Ocultar admin bar para clientes y proveedores
        add_action('after_setup_theme', array(__CLASS__, 'hide_admin_bar'));

        // Bloquear acceso al admin para clientes y proveedores
        add_action('admin_init', array(__CLASS__, 'block_admin_access'));
    }

    /**
     * Ocultar admin bar para clientes y proveedores
     */
    public static function hide_admin_bar() {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            if (in_array('fullday_cliente', $user->roles) || in_array('fullday_proveedor', $user->roles)) {
                show_admin_bar(false);
            }
        }
    }

    /**
     * Bloquear acceso al admin para clientes y proveedores
     */
    public static function block_admin_access() {
        if (!defined('DOING_AJAX') && is_user_logged_in()) {
            $user = wp_get_current_user();
            if (in_array('fullday_cliente', $user->roles) || in_array('fullday_proveedor', $user->roles)) {
                wp_redirect(home_url('/dashboard'));
                exit;
            }
        }
    }

    /**
     * Crear roles personalizados
     */
    public static function create_roles() {
        // Crear rol Cliente
        add_role(
            'fullday_cliente',
            __('Cliente Fullday', 'fullday-users'),
            array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
            )
        );

        // Crear rol Proveedor con capacidades de editor para gestionar full-days CPT
        add_role(
            'fullday_proveedor',
            __('Proveedor Fullday', 'fullday-users'),
            array(
                'read' => true,
                'edit_posts' => true,
                'delete_posts' => true,
                'publish_posts' => true,
                'edit_published_posts' => true,
                'delete_published_posts' => true,
                'edit_others_posts' => true,
                'delete_others_posts' => true,
                'read_private_posts' => true,
                'edit_private_posts' => true,
                'delete_private_posts' => true,

                // Capacidades para el CPT full-days (nivel editor completo)
                'edit_full_day' => true,
                'read_full_day' => true,
                'delete_full_day' => true,
                'edit_full_days' => true,
                'edit_others_full_days' => true,
                'publish_full_days' => true,
                'read_private_full_days' => true,
                'delete_full_days' => true,
                'delete_private_full_days' => true,
                'delete_published_full_days' => true,
                'delete_others_full_days' => true,
                'edit_private_full_days' => true,
                'edit_published_full_days' => true,

                // Capacidades de medios para poder subir imágenes
                'upload_files' => true,
                'edit_files' => true,

                // Capacidades adicionales de editor
                'moderate_comments' => true,
                'manage_categories' => true,
                'manage_links' => true,
                'unfiltered_html' => true,
            )
        );

        // Agregar capacidades al administrador para gestionar full-days
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('edit_full_day');
            $admin_role->add_cap('read_full_day');
            $admin_role->add_cap('delete_full_day');
            $admin_role->add_cap('edit_full_days');
            $admin_role->add_cap('edit_others_full_days');
            $admin_role->add_cap('publish_full_days');
            $admin_role->add_cap('read_private_full_days');
            $admin_role->add_cap('delete_full_days');
            $admin_role->add_cap('delete_private_full_days');
            $admin_role->add_cap('delete_published_full_days');
            $admin_role->add_cap('delete_others_full_days');
            $admin_role->add_cap('edit_private_full_days');
            $admin_role->add_cap('edit_published_full_days');
        }
    }

    /**
     * Remover roles personalizados
     */
    public static function remove_roles() {
        remove_role('fullday_cliente');
        remove_role('fullday_proveedor');
    }

    /**
     * Verificar si el usuario es cliente
     */
    public static function is_cliente($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $user = get_userdata($user_id);
        return $user && in_array('fullday_cliente', (array) $user->roles);
    }

    /**
     * Verificar si el usuario es proveedor
     */
    public static function is_proveedor($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $user = get_userdata($user_id);
        return $user && in_array('fullday_proveedor', (array) $user->roles);
    }

    /**
     * Obtener tipo de usuario
     */
    public static function get_user_type($user_id = null) {
        if (self::is_cliente($user_id)) {
            return 'cliente';
        } elseif (self::is_proveedor($user_id)) {
            return 'proveedor';
        }
        return false;
    }
}
