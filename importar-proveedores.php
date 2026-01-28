<?php
/**
 * Script para importar proveedores de turismo desde CSV
 *
 * INSTRUCCIONES:
 * 1. Sube este archivo y proveedores_venezuela.csv al directorio del plugin
 * 2. Accede a: http://fullday.local/wp-content/plugins/fullday-users/importar-proveedores.php
 * 3. El script creará automáticamente los 10 proveedores
 * 4. IMPORTANTE: Elimina este archivo después de usar por seguridad
 */

// Cargar WordPress
require_once('../../../wp-load.php');

// Verificar que el usuario sea administrador
if (!current_user_can('administrator')) {
    die('❌ Acceso denegado. Solo administradores pueden ejecutar este script.');
}

// Leer el archivo CSV
$csv_file = __DIR__ . '/proveedores_venezuela.csv';

if (!file_exists($csv_file)) {
    die('❌ No se encuentra el archivo proveedores_venezuela.csv');
}

echo '<h1>Importador de Proveedores de Turismo</h1>';
echo '<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>';

$handle = fopen($csv_file, 'r');
$header = fgetcsv($handle); // Leer encabezados

$count_success = 0;
$count_errors = 0;
$errors = [];

echo '<h2>Procesando proveedores...</h2>';

while (($data = fgetcsv($handle)) !== false) {
    // Combinar encabezados con datos
    $row = array_combine($header, $data);

    echo '<div style="border:1px solid #ccc; padding:10px; margin:10px 0;">';
    echo '<h3>📍 ' . esc_html($row['empresa']) . '</h3>';

    // Verificar si el usuario ya existe
    if (username_exists($row['username'])) {
        echo '<p class="error">⚠️ El usuario "' . esc_html($row['username']) . '" ya existe. Saltando...</p>';
        $count_errors++;
        echo '</div>';
        continue;
    }

    if (email_exists($row['email'])) {
        echo '<p class="error">⚠️ El email "' . esc_html($row['email']) . '" ya existe. Saltando...</p>';
        $count_errors++;
        echo '</div>';
        continue;
    }

    // Crear usuario
    $user_data = array(
        'user_login' => $row['username'],
        'user_email' => $row['email'],
        'user_pass' => $row['password'],
        'role' => 'fullday_proveedor',
        'nickname' => $row['nickname'],
        'display_name' => $row['empresa']
    );

    $user_id = wp_insert_user($user_data);

    if (is_wp_error($user_id)) {
        echo '<p class="error">❌ Error al crear usuario: ' . $user_id->get_error_message() . '</p>';
        $errors[] = $row['username'] . ': ' . $user_id->get_error_message();
        $count_errors++;
        echo '</div>';
        continue;
    }

    echo '<p class="success">✅ Usuario creado: ' . esc_html($row['username']) . ' (ID: ' . $user_id . ')</p>';

    // Guardar meta data
    update_user_meta($user_id, 'empresa', sanitize_text_field($row['empresa']));
    update_user_meta($user_id, 'descripcion', sanitize_textarea_field($row['descripcion']));
    update_user_meta($user_id, 'phone', sanitize_text_field($row['phone']));
    update_user_meta($user_id, 'whatsapp', sanitize_text_field($row['whatsapp']));
    update_user_meta($user_id, 'estado', intval($row['estado']));
    update_user_meta($user_id, 'ciudad', intval($row['ciudad']));
    update_user_meta($user_id, 'facebook_url', esc_url_raw($row['facebook_url']));
    update_user_meta($user_id, 'instagram_url', esc_url_raw($row['instagram_url']));

    // Aprobar proveedor automáticamente
    update_user_meta($user_id, 'proveedor_approved', '1');

    // Guardar URLs de imágenes como meta temporal (para que las descargues manualmente o las uses como referencia)
    update_user_meta($user_id, '_avatar_url_temp', esc_url_raw($row['avatar_url']));
    update_user_meta($user_id, '_banner_url_temp', esc_url_raw($row['banner_url']));

    echo '<p class="info">📝 Meta data guardada correctamente</p>';
    echo '<p class="info">🖼️ Avatar URL: <a href="' . esc_url($row['avatar_url']) . '" target="_blank">Ver imagen</a></p>';
    echo '<p class="info">🎨 Banner URL: <a href="' . esc_url($row['banner_url']) . '" target="_blank">Ver imagen</a></p>';

    $count_success++;
    echo '</div>';
}

fclose($handle);

// Resumen
echo '<hr>';
echo '<h2>Resumen de Importación</h2>';
echo '<p class="success">✅ Proveedores creados exitosamente: <strong>' . $count_success . '</strong></p>';
echo '<p class="error">❌ Errores: <strong>' . $count_errors . '</strong></p>';

if (!empty($errors)) {
    echo '<h3>Detalles de errores:</h3><ul>';
    foreach ($errors as $error) {
        echo '<li>' . esc_html($error) . '</li>';
    }
    echo '</ul>';
}

echo '<hr>';
echo '<h3>⚠️ IMPORTANTE - Próximos pasos:</h3>';
echo '<ol>';
echo '<li>Los proveedores han sido creados y aprobados automáticamente</li>';
echo '<li>Las URLs de avatar y banner están guardadas en los meta "_avatar_url_temp" y "_banner_url_temp"</li>';
echo '<li>Puedes usar estas URLs para subir las imágenes manualmente desde el dashboard de cada proveedor</li>';
echo '<li><strong>ELIMINA este archivo (importar-proveedores.php) por seguridad</strong></li>';
echo '<li>También puedes eliminar el archivo proveedores_venezuela.csv si ya no lo necesitas</li>';
echo '</ol>';

echo '<hr>';
echo '<h3>📋 Credenciales de los proveedores:</h3>';
echo '<p><strong>Contraseña para todos:</strong> fullday2024</p>';
echo '<table border="1" cellpadding="10" style="border-collapse:collapse; width:100%;">';
echo '<tr><th>Usuario</th><th>Email</th><th>Empresa</th></tr>';

// Volver a leer el CSV para mostrar las credenciales
$handle = fopen($csv_file, 'r');
fgetcsv($handle); // Saltar encabezados
while (($data = fgetcsv($handle)) !== false) {
    $row = array_combine($header, $data);
    echo '<tr>';
    echo '<td>' . esc_html($row['username']) . '</td>';
    echo '<td>' . esc_html($row['email']) . '</td>';
    echo '<td>' . esc_html($row['empresa']) . '</td>';
    echo '</tr>';
}
fclose($handle);
echo '</table>';

echo '<hr>';
echo '<p style="text-align:center; margin-top:30px;"><a href="' . admin_url() . '" style="background:#0073aa; color:white; padding:10px 20px; text-decoration:none; border-radius:3px;">Ir al Panel de Administración</a></p>';
