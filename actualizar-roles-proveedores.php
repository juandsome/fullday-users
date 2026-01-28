<?php
/**
 * Script temporal para actualizar los permisos del rol fullday_proveedor
 *
 * IMPORTANTE: Este archivo debe ser eliminado después de ejecutarlo por razones de seguridad
 *
 * Uso: Accede a este archivo desde tu navegador:
 * http://fullday.local/wp-content/plugins/fullday-users/actualizar-roles-proveedores.php
 */

// Cargar WordPress
$parse_uri = explode('wp-content', $_SERVER['SCRIPT_FILENAME']);
require_once($parse_uri[0] . 'wp-load.php');

// Verificar que el usuario actual es administrador
if (!current_user_can('administrator')) {
    die('ERROR: Debes ser administrador para ejecutar este script.');
}

echo '<h1>Actualización de Permisos - Rol Proveedor Fullday</h1>';
echo '<hr>';

// Eliminar el rol existente
echo '<p>1. Eliminando rol fullday_proveedor existente...</p>';
remove_role('fullday_proveedor');
echo '<p style="color: green;">✓ Rol eliminado correctamente</p>';

// Recrear el rol con los nuevos permisos
echo '<p>2. Creando rol fullday_proveedor con permisos de editor...</p>';
$result = add_role(
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

if ($result) {
    echo '<p style="color: green;">✓ Rol creado correctamente con permisos de editor</p>';
} else {
    echo '<p style="color: red;">✗ Error al crear el rol</p>';
}

// Obtener todos los usuarios con rol fullday_proveedor
echo '<p>3. Actualizando usuarios existentes con rol fullday_proveedor...</p>';

$proveedores = get_users(array(
    'role' => 'fullday_proveedor',
    'fields' => array('ID', 'user_login', 'display_name')
));

if (empty($proveedores)) {
    echo '<p style="color: orange;">⚠ No se encontraron usuarios con rol fullday_proveedor</p>';
} else {
    echo '<p>Encontrados ' . count($proveedores) . ' proveedores:</p>';
    echo '<ul>';

    foreach ($proveedores as $proveedor) {
        // Remover el rol actual
        $user = new WP_User($proveedor->ID);
        $user->remove_role('fullday_proveedor');

        // Agregar el rol actualizado
        $user->add_role('fullday_proveedor');

        echo '<li>✓ Actualizado: ' . $proveedor->display_name . ' (' . $proveedor->user_login . ')</li>';
    }

    echo '</ul>';
    echo '<p style="color: green;">✓ Todos los proveedores actualizados correctamente</p>';
}

// Verificar capacidades del rol
echo '<hr>';
echo '<h2>Verificación de Capacidades del Rol</h2>';

$role = get_role('fullday_proveedor');
if ($role) {
    echo '<p><strong>Capacidades asignadas al rol fullday_proveedor:</strong></p>';
    echo '<ul style="column-count: 2;">';
    foreach ($role->capabilities as $cap => $enabled) {
        $color = $enabled ? 'green' : 'red';
        $symbol = $enabled ? '✓' : '✗';
        echo '<li style="color: ' . $color . ';">' . $symbol . ' ' . $cap . '</li>';
    }
    echo '</ul>';
} else {
    echo '<p style="color: red;">✗ Error: No se pudo obtener el rol</p>';
}

echo '<hr>';
echo '<h2>✓ Proceso Completado</h2>';
echo '<p><strong style="color: red;">IMPORTANTE:</strong> Ahora los proveedores tienen permisos de nivel Editor.</p>';
echo '<p>Esto les permite:</p>';
echo '<ul>';
echo '<li>✓ Ser asignados como autores de posts tipo full-days</li>';
echo '<li>✓ Crear, editar y eliminar sus propios posts full-days</li>';
echo '<li>✓ Ver y editar posts de otros proveedores</li>';
echo '<li>✓ Subir archivos e imágenes</li>';
echo '<li>✓ Publicar posts sin necesidad de aprobación</li>';
echo '<li>✓ Gestionar categorías y etiquetas</li>';
echo '</ul>';

echo '<hr>';
echo '<p style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px;">';
echo '<strong>⚠ SEGURIDAD:</strong> Por favor, elimina este archivo ahora:<br>';
echo '<code style="background: #f8f9fa; padding: 5px;">wp-content/plugins/fullday-users/actualizar-roles-proveedores.php</code>';
echo '</p>';
