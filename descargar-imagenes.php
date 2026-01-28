<?php
/**
 * Script para descargar y organizar imágenes de las experiencias
 *
 * INSTRUCCIONES:
 * 1. Asegúrate de haber importado las experiencias primero
 * 2. Accede a: http://fullday.local/wp-content/plugins/fullday-users/descargar-imagenes.php
 * 3. El script descargará y organizará todas las imágenes automáticamente
 * 4. IMPORTANTE: Este proceso puede tomar varios minutos
 * 5. Elimina este archivo después de usar por seguridad
 */

// Aumentar tiempo de ejecución y memoria
set_time_limit(600); // 10 minutos
ini_set('memory_limit', '512M');

// Cargar WordPress
require_once('../../../wp-load.php');

// Verificar que el usuario sea administrador
if (!current_user_can('administrator')) {
    die('❌ Acceso denegado. Solo administradores pueden ejecutar este script.');
}

echo '<h1>Descargador de Imágenes para Experiencias</h1>';
echo '<style>
body{font-family:Arial;padding:20px;background:#f5f5f5;}
.success{color:green;}
.error{color:red;}
.info{color:blue;}
.warning{color:orange;}
.experience-box{
    background:white;
    border-left:4px solid #0073aa;
    padding:15px;
    margin:15px 0;
    box-shadow:0 2px 5px rgba(0,0,0,0.1);
}
.image-preview{
    display:flex;
    gap:10px;
    margin-top:10px;
    flex-wrap:wrap;
}
.image-preview img{
    width:150px;
    height:100px;
    object-fit:cover;
    border-radius:5px;
    border:2px solid #ddd;
}
.progress-bar{
    background:#e0e0e0;
    height:30px;
    border-radius:5px;
    overflow:hidden;
    margin:20px 0;
}
.progress-fill{
    background:#0073aa;
    height:100%;
    transition:width 0.3s;
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-weight:bold;
}
</style>';

// Obtener todos los posts de tipo full-days
$args = array(
    'post_type' => 'full-days',
    'posts_per_page' => -1,
    'post_status' => 'publish'
);

$posts = get_posts($args);

if (empty($posts)) {
    echo '<div class="experience-box error">';
    echo '<h3>❌ No se encontraron experiencias</h3>';
    echo '<p>Asegúrate de haber ejecutado primero el importador de experiencias.</p>';
    echo '<p><a href="importar-experiencias.php">Importar Experiencias</a></p>';
    echo '</div>';
    exit;
}

$total_posts = count($posts);
echo '<div class="experience-box info">';
echo '<h3>📊 Información</h3>';
echo '<p>Se encontraron <strong>' . $total_posts . ' experiencias</strong> para procesar.</p>';
echo '<p>Se descargarán <strong>' . ($total_posts * 4) . ' imágenes</strong> en total.</p>';
echo '<p class="warning">⚠️ Este proceso puede tardar varios minutos. No cierres esta ventana.</p>';
echo '</div>';

// Crear directorio base para imágenes
$upload_dir = wp_upload_dir();
$base_dir = $upload_dir['basedir'] . '/imagenes';

if (!file_exists($base_dir)) {
    wp_mkdir_p($base_dir);
    echo '<p class="success">✅ Carpeta creada: ' . $base_dir . '</p>';
}

$count_success = 0;
$count_errors = 0;
$total_images_downloaded = 0;

echo '<h2>Procesando experiencias...</h2>';

foreach ($posts as $index => $post) {
    $progress = (($index + 1) / $total_posts) * 100;

    echo '<div class="experience-box">';
    echo '<h3>🎯 ' . esc_html($post->post_title) . ' (ID: ' . $post->ID . ')</h3>';

    // Crear slug para la carpeta
    $folder_slug = sanitize_title($post->post_title);
    $post_image_dir = $base_dir . '/' . $folder_slug;

    // Crear carpeta para esta experiencia
    if (!file_exists($post_image_dir)) {
        wp_mkdir_p($post_image_dir);
    }

    echo '<p class="info">📁 Carpeta: /wp-content/uploads/imagenes/' . $folder_slug . '/</p>';

    // Obtener URLs de imágenes desde meta
    $thumbnail_url = get_post_meta($post->ID, '_thumbnail_url_temp', true);
    $gallery_urls = array(
        get_post_meta($post->ID, '_gallery_url_1_temp', true),
        get_post_meta($post->ID, '_gallery_url_2_temp', true),
        get_post_meta($post->ID, '_gallery_url_3_temp', true)
    );

    $images_downloaded = 0;
    $attachment_ids = array();

    // Descargar thumbnail
    if ($thumbnail_url) {
        $filename = 'thumbnail.jpg';
        $filepath = $post_image_dir . '/' . $filename;

        $image_data = @file_get_contents($thumbnail_url);

        if ($image_data !== false) {
            file_put_contents($filepath, $image_data);

            // Crear attachment en WordPress
            $attachment_id = create_wordpress_attachment($filepath, $post->ID, 'Thumbnail - ' . $post->post_title);

            if ($attachment_id) {
                set_post_thumbnail($post->ID, $attachment_id);
                echo '<p class="success">✅ Thumbnail descargado y asignado</p>';
                $images_downloaded++;
                $total_images_downloaded++;
            }
        } else {
            echo '<p class="error">❌ Error al descargar thumbnail</p>';
        }
    }

    // Descargar galería
    foreach ($gallery_urls as $i => $gallery_url) {
        if ($gallery_url) {
            $filename = 'gallery-' . ($i + 1) . '.jpg';
            $filepath = $post_image_dir . '/' . $filename;

            $image_data = @file_get_contents($gallery_url);

            if ($image_data !== false) {
                file_put_contents($filepath, $image_data);

                // Crear attachment en WordPress
                $attachment_id = create_wordpress_attachment($filepath, $post->ID, 'Galería ' . ($i + 1) . ' - ' . $post->post_title);

                if ($attachment_id) {
                    $attachment_ids[] = $attachment_id;
                    echo '<p class="success">✅ Galería ' . ($i + 1) . ' descargada</p>';
                    $images_downloaded++;
                    $total_images_downloaded++;
                }
            } else {
                echo '<p class="error">❌ Error al descargar galería ' . ($i + 1) . '</p>';
            }
        }
    }

    // Guardar IDs de galería como meta
    if (!empty($attachment_ids)) {
        update_post_meta($post->ID, '_gallery_ids', $attachment_ids);
        echo '<p class="info">💾 IDs de galería guardados en meta</p>';
    }

    // Mostrar preview de imágenes descargadas
    if ($images_downloaded > 0) {
        echo '<div class="image-preview">';

        // Preview thumbnail
        if (has_post_thumbnail($post->ID)) {
            echo wp_get_attachment_image(get_post_thumbnail_id($post->ID), 'thumbnail');
        }

        // Preview galería
        foreach ($attachment_ids as $att_id) {
            echo wp_get_attachment_image($att_id, 'thumbnail');
        }

        echo '</div>';
    }

    echo '<p class="success">📥 Total descargado: ' . $images_downloaded . ' / 4 imágenes</p>';

    if ($images_downloaded == 4) {
        $count_success++;
    } else {
        $count_errors++;
    }

    echo '</div>';

    // Pequeña pausa para no saturar el servidor
    usleep(500000); // 0.5 segundos
}

/**
 * Función helper para crear attachments en WordPress
 */
function create_wordpress_attachment($filepath, $post_id, $title) {
    $filetype = wp_check_filetype(basename($filepath), null);

    $attachment = array(
        'guid'           => $filepath,
        'post_mime_type' => $filetype['type'],
        'post_title'     => $title,
        'post_content'   => '',
        'post_status'    => 'inherit'
    );

    $attachment_id = wp_insert_attachment($attachment, $filepath, $post_id);

    if (!is_wp_error($attachment_id)) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $filepath);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        return $attachment_id;
    }

    return false;
}

// Resumen final
echo '<hr>';
echo '<div style="background:white; padding:20px; border-radius:5px; margin-top:20px;">';
echo '<h2>📊 Resumen Final</h2>';
echo '<p class="success" style="font-size:18px;">✅ Experiencias procesadas exitosamente: <strong>' . $count_success . '</strong></p>';
echo '<p class="error" style="font-size:18px;">⚠️ Experiencias con errores parciales: <strong>' . $count_errors . '</strong></p>';
echo '<p class="info" style="font-size:18px;">📥 Total de imágenes descargadas: <strong>' . $total_images_downloaded . '</strong></p>';
echo '</div>';

echo '<hr>';
echo '<div style="background:#d4edda; padding:20px; border-radius:5px; border-left:4px solid #28a745;">';
echo '<h3>✅ Proceso Completado</h3>';
echo '<ul style="line-height:2;">';
echo '<li>Las imágenes están organizadas en: <code>/wp-content/uploads/imagenes/</code></li>';
echo '<li>Cada experiencia tiene su propia carpeta con nombre slugificado</li>';
echo '<li>Las imágenes destacadas (thumbnails) están asignadas a cada post</li>';
echo '<li>Las galerías están guardadas en el meta <code>_gallery_ids</code></li>';
echo '<li>Puedes eliminar los meta temporales <code>_thumbnail_url_temp</code> y <code>_gallery_url_X_temp</code></li>';
echo '</ul>';
echo '</div>';

echo '<hr>';
echo '<div style="background:#fff3cd; padding:20px; border-radius:5px; border-left:4px solid #ffc107;">';
echo '<h3>⚠️ IMPORTANTE - Seguridad</h3>';
echo '<p style="font-size:16px;"><strong style="color:red;">ELIMINA este archivo (descargar-imagenes.php) por seguridad</strong></p>';
echo '<p>Este script tiene permisos para descargar archivos de internet, lo cual puede ser un riesgo de seguridad si se deja accesible.</p>';
echo '</div>';

echo '<hr>';
echo '<p style="text-align:center; margin-top:30px;">';
echo '<a href="' . admin_url('edit.php?post_type=full-days') . '" style="background:#0073aa; color:white; padding:15px 30px; text-decoration:none; border-radius:5px; font-size:16px; display:inline-block;">📋 Ver Todas las Experiencias</a>';
echo ' ';
echo '<a href="' . admin_url('upload.php') . '" style="background:#46b450; color:white; padding:15px 30px; text-decoration:none; border-radius:5px; font-size:16px; display:inline-block;">🖼️ Ver Biblioteca de Medios</a>';
echo ' ';
echo '<a href="' . $upload_dir['baseurl'] . '/imagenes" target="_blank" style="background:#00a0d2; color:white; padding:15px 30px; text-decoration:none; border-radius:5px; font-size:16px; display:inline-block;">📁 Ver Carpeta de Imágenes</a>';
echo '</p>';
