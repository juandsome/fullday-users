<?php
/**
 * Script para importar experiencias turísticas desde CSV
 *
 * INSTRUCCIONES:
 * 1. Sube este archivo y experiencias_venezuela.csv al directorio del plugin
 * 2. Accede a: http://fullday.local/wp-content/plugins/fullday-users/importar-experiencias.php
 * 3. El script creará automáticamente los 30 posts de experiencias
 * 4. IMPORTANTE: Elimina este archivo después de usar por seguridad
 */

// Cargar WordPress
require_once('../../../wp-load.php');

// Verificar que el usuario sea administrador
if (!current_user_can('administrator')) {
    die('❌ Acceso denegado. Solo administradores pueden ejecutar este script.');
}

// Leer el archivo CSV
$csv_file = __DIR__ . '/experiencias_venezuela.csv';

if (!file_exists($csv_file)) {
    die('❌ No se encuentra el archivo experiencias_venezuela.csv');
}

echo '<h1>Importador de Experiencias Turísticas</h1>';
echo '<style>
body{font-family:Arial;padding:20px;background:#f5f5f5;}
.success{color:green;}
.error{color:red;}
.info{color:blue;}
.experience-box{
    background:white;
    border-left:4px solid #0073aa;
    padding:15px;
    margin:15px 0;
    box-shadow:0 2px 5px rgba(0,0,0,0.1);
}
.meta-item{
    display:inline-block;
    background:#f0f0f0;
    padding:5px 10px;
    margin:3px;
    border-radius:3px;
    font-size:12px;
}
.gallery{
    display:flex;
    gap:10px;
    margin-top:10px;
}
.gallery img{
    width:100px;
    height:75px;
    object-fit:cover;
    border-radius:5px;
}
</style>';

$handle = fopen($csv_file, 'r');
$header = fgetcsv($handle); // Leer encabezados

$count_success = 0;
$count_errors = 0;
$errors = [];

echo '<h2>Procesando experiencias...</h2>';
echo '<p class="info">Creando posts del tipo "full-days" y asignando al administrador</p>';

// Obtener el ID del administrador
$admin_user = get_users(array('role' => 'administrator', 'number' => 1));
$author_id = !empty($admin_user) ? $admin_user[0]->ID : 1;

while (($data = fgetcsv($handle)) !== false) {
    // Combinar encabezados con datos
    $row = array_combine($header, $data);

    echo '<div class="experience-box">';
    echo '<h3>🎯 ' . esc_html($row['post_title']) . '</h3>';

    // Crear el post
    $post_data = array(
        'post_title'    => $row['post_title'],
        'post_content'  => $row['post_content'],
        'post_status'   => 'publish',
        'post_type'     => 'full-days',
        'post_author'   => $author_id,
        'meta_input'    => array(
            'price' => $row['price'],
            'discount_price' => $row['discount_price'],
            'discount_percentage' => $row['discount_percentage'],
            'description' => $row['description'],
            'destination' => $row['destination'],
            'duration' => $row['duration'],
            'max_people' => $row['max_people'],
            'min_age' => $row['min_age'],
            'includes' => $row['includes'],
            'itinerary' => $row['itinerary'],
            'departure_date' => $row['departure_date'],
            'available_spots' => $row['available_spots'],
            '_thumbnail_url_temp' => $row['thumbnail_url'],
            '_gallery_url_1_temp' => $row['gallery_url_1'],
            '_gallery_url_2_temp' => $row['gallery_url_2'],
            '_gallery_url_3_temp' => $row['gallery_url_3'],
        )
    );

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        echo '<p class="error">❌ Error al crear post: ' . $post_id->get_error_message() . '</p>';
        $errors[] = $row['post_title'] . ': ' . $post_id->get_error_message();
        $count_errors++;
        echo '</div>';
        continue;
    }

    echo '<p class="success">✅ Post creado: ID ' . $post_id . '</p>';

    // Mostrar meta data guardada
    echo '<div class="info" style="margin-top:10px;">';
    echo '<span class="meta-item">💰 $' . esc_html($row['price']) . '</span>';
    echo '<span class="meta-item">🏷️ ' . esc_html($row['discount_percentage']) . '% descuento</span>';
    echo '<span class="meta-item">⏱️ ' . esc_html($row['duration']) . '</span>';
    echo '<span class="meta-item">👥 Máx: ' . esc_html($row['max_people']) . ' personas</span>';
    echo '<span class="meta-item">🎂 Edad mín: ' . esc_html($row['min_age']) . ' años</span>';
    echo '<span class="meta-item">📍 ' . esc_html($row['destination']) . '</span>';
    echo '<span class="meta-item">📅 ' . esc_html($row['departure_date']) . '</span>';
    echo '<span class="meta-item">🎫 ' . esc_html($row['available_spots']) . ' cupos</span>';
    echo '</div>';

    // Mostrar galería de imágenes
    echo '<div class="gallery">';
    echo '<div>';
    echo '<strong>Thumbnail:</strong><br>';
    echo '<a href="' . esc_url($row['thumbnail_url']) . '" target="_blank">';
    echo '<img src="' . esc_url($row['thumbnail_url']) . '" alt="Thumbnail">';
    echo '</a>';
    echo '</div>';
    echo '<div>';
    echo '<strong>Galería:</strong><br>';
    echo '<a href="' . esc_url($row['gallery_url_1']) . '" target="_blank">';
    echo '<img src="' . esc_url($row['gallery_url_1']) . '" alt="Gallery 1">';
    echo '</a>';
    echo '</div>';
    echo '<div>';
    echo '<a href="' . esc_url($row['gallery_url_2']) . '" target="_blank">';
    echo '<img src="' . esc_url($row['gallery_url_2']) . '" alt="Gallery 2">';
    echo '</a>';
    echo '</div>';
    echo '<div>';
    echo '<a href="' . esc_url($row['gallery_url_3']) . '" target="_blank">';
    echo '<img src="' . esc_url($row['gallery_url_3']) . '" alt="Gallery 3">';
    echo '</a>';
    echo '</div>';
    echo '</div>';

    $count_success++;
    echo '</div>';
}

fclose($handle);

// Resumen
echo '<hr>';
echo '<div style="background:white; padding:20px; border-radius:5px; margin-top:20px;">';
echo '<h2>📊 Resumen de Importación</h2>';
echo '<p class="success" style="font-size:18px;">✅ Experiencias creadas exitosamente: <strong>' . $count_success . '</strong></p>';
echo '<p class="error" style="font-size:18px;">❌ Errores: <strong>' . $count_errors . '</strong></p>';

if (!empty($errors)) {
    echo '<h3>Detalles de errores:</h3><ul>';
    foreach ($errors as $error) {
        echo '<li>' . esc_html($error) . '</li>';
    }
    echo '</ul>';
}
echo '</div>';

echo '<hr>';
echo '<div style="background:#fff3cd; padding:20px; border-radius:5px; border-left:4px solid #ffc107;">';
echo '<h3>⚠️ IMPORTANTE - Próximos pasos:</h3>';
echo '<ol style="line-height:2;">';
echo '<li>Se han creado <strong>' . $count_success . ' experiencias</strong> tipo "full-days"</li>';
echo '<li>Todas las experiencias están <strong>publicadas</strong> y asignadas al administrador</li>';
echo '<li>Las URLs de las imágenes están guardadas como meta temporal (_thumbnail_url_temp, _gallery_url_X_temp)</li>';
echo '<li>Puedes usar estas URLs para descargar y asignar las imágenes desde el panel de WordPress</li>';
echo '<li><strong style="color:red;">ELIMINA este archivo (importar-experiencias.php) por seguridad</strong></li>';
echo '<li>También puedes eliminar el archivo experiencias_venezuela.csv si ya no lo necesitas</li>';
echo '</ol>';
echo '</div>';

echo '<hr>';
echo '<div style="background:white; padding:20px; border-radius:5px;">';
echo '<h3>🎨 Categorías de Experiencias Creadas:</h3>';
echo '<ul style="columns:2; line-height:1.8;">';
echo '<li>🏝️ Snorkeling y Buceo</li>';
echo '<li>🪂 Deportes Extremos</li>';
echo '<li>⛰️ Trekking y Montañismo</li>';
echo '<li>🚤 Tours Acuáticos</li>';
echo '<li>🦜 Ecoturismo y Naturaleza</li>';
echo '<li>🎣 Pesca Deportiva</li>';
echo '<li>🏄 Deportes de Tabla</li>';
echo '<li>🚴 Ciclismo de Aventura</li>';
echo '<li>🏕️ Camping y Expediciones</li>';
echo '<li>🍴 Tours Gastronómicos</li>';
echo '</ul>';
echo '</div>';

echo '<hr>';
echo '<div style="background:white; padding:20px; border-radius:5px;">';
echo '<h3>📍 Destinos Incluidos:</h3>';
echo '<div style="columns:2; line-height:1.8;">';
echo '<p>• Los Roques • Mérida • Canaima • Margarita</p>';
echo '<p>• Morrocoy • Roraima • Los Llanos • Barinas</p>';
echo '<p>• Henri Pittier • Médanos de Coro • El Yaque</p>';
echo '<p>• Galipán • Tacarigua • Valencia • Monagas</p>';
echo '<p>• Amazonas • Adicora • Sierra Nevada • Puerto La Cruz</p>';
echo '<p>• Cuyagua • La Culata • Delta del Orinoco</p>';
echo '<p>• Apartaderos • Mochima • Colonia Tovar • Caracas</p>';
echo '<p>• Gran Sabana • Parque La Llovizna</p>';
echo '</div>';
echo '</div>';

echo '<hr>';
echo '<p style="text-align:center; margin-top:30px;">';
echo '<a href="' . admin_url('edit.php?post_type=full-days') . '" style="background:#0073aa; color:white; padding:15px 30px; text-decoration:none; border-radius:5px; font-size:16px; display:inline-block;">📋 Ver Todas las Experiencias</a>';
echo ' ';
echo '<a href="' . admin_url() . '" style="background:#46b450; color:white; padding:15px 30px; text-decoration:none; border-radius:5px; font-size:16px; display:inline-block;">🏠 Ir al Dashboard</a>';
echo '</p>';
