<?php
/**
 * Vista Unificada de Crear/Editar Full Day para Proveedor
 *
 * @package Fullday_Users
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$approved = get_user_meta($user_id, 'proveedor_approved', true);
$is_approved = ($approved === '1' || $approved === 1);

// Determinar si estamos en modo editar
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
$is_edit_mode = ($post_id > 0);

// Verificar aprobación solo para crear nuevo
if (!$is_edit_mode && !$is_approved) {
    ?>
    <div class="crear-container">
        <div class="approval-required">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <h2>Cuenta Pendiente de Aprobación</h2>
            <p>Tu cuenta debe ser aprobada por nuestro equipo antes de poder crear experiencias Full Day.</p>
        </div>
    </div>
    <?php
    return;
}

// Si estamos en modo editar, cargar datos del post
$title = '';
$price = '';
$original_price = '';
$description = '';
$destination = '';
$duration = '';
$category = '';
$min_age = '';
$max_people = '';
$includes = '';
$itinerary_items = array(array('time' => '', 'description' => ''));
$featured_image_id = '';
$featured_image_url = '';
$gallery = array();

if ($is_edit_mode) {
    $post = get_post($post_id);

    // Verificar que el post existe y pertenece al usuario
    if (!$post || $post->post_type !== 'full-days' || $post->post_author != $user_id) {
        ?>
        <div class="crear-container">
            <div class="approval-required">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
                <h2>Acceso Denegado</h2>
                <p>No tienes permiso para editar este Full Day.</p>
                <button type="button" class="btn-secondary" onclick="jQuery('.dashboard-tab[data-tab=\'mis-viajes\']').click();">Volver a Mis Viajes</button>
            </div>
        </div>
        <?php
        return;
    }

    // Cargar datos del post
    $title = $post->post_title;
    $price = get_post_meta($post_id, 'full_days_price', true);
    $original_price = get_post_meta($post_id, 'full_days_discount_price', true);
    $description = get_post_meta($post_id, 'full_days_description', true);
    $destination = get_post_meta($post_id, 'full_days_destination', true);
    $departure_date = get_post_meta($post_id, 'full_days_departure_date', true);
    $duration = get_post_meta($post_id, 'full_days_duration', true);

    // Obtener categoría desde taxonomy
    $terms = wp_get_post_terms($post_id, 'full_days_category');
    $category = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->name : '';

    $min_age = get_post_meta($post_id, 'full_days_min_age', true);
    $max_people = get_post_meta($post_id, 'full_days_max_people', true);
    $includes = get_post_meta($post_id, 'full_days_includes', true);
    $itinerary = get_post_meta($post_id, 'full_days_itinerary', true);
    $featured_image_id = get_post_thumbnail_id($post_id);
    $featured_image_url = get_the_post_thumbnail_url($post_id, 'large');
    $gallery = get_post_meta($post_id, 'full_days_gallery', true);

    // Parsear itinerario
    $itinerary_items = array();
    if ($itinerary) {
        $lines = explode("\n", trim($itinerary));
        foreach ($lines as $line) {
            if (strpos($line, ',') !== false) {
                list($time, $desc) = explode(',', $line, 2);
                $itinerary_items[] = array(
                    'time' => trim($time),
                    'description' => trim($desc)
                );
            }
        }
    }

    // Si no hay items, agregar uno vacío
    if (empty($itinerary_items)) {
        $itinerary_items[] = array('time' => '', 'description' => '');
    }
}

// Obtener categorías
$categories = get_terms(array(
    'taxonomy' => 'full_days_category',
    'hide_empty' => false,
));

// Obtener regiones (ciudades)
$regions = get_terms(array(
    'taxonomy' => 'region',
    'hide_empty' => false,
    'parent' => 0, // Solo obtener padres (estados)
));

// Obtener las regiones seleccionadas y su orden (solo en modo editar)
$selected_regions_order = array();
if ($is_edit_mode) {
    $saved_order = get_post_meta($post_id, 'region_order', true);
    if (!empty($saved_order) && is_array($saved_order)) {
        $selected_regions_order = $saved_order;
    }
}

// Asegurar que gallery es un array válido
$gallery_data = (is_array($gallery) && !empty($gallery)) ? $gallery : array();
?>

<div class="crear-container">
    <div class="crear-header">
        <h2 class="crear-title">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <?php if ($is_edit_mode): ?>
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                <?php else: ?>
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                <?php endif; ?>
            </svg>
            <?php echo $is_edit_mode ? 'Editar Full Day' : 'Crear Nuevo Full Day'; ?>
        </h2>
        <p class="crear-description"><?php echo $is_edit_mode ? 'Actualiza la información de tu experiencia' : 'Completa la información para crear tu experiencia'; ?></p>
    </div>

    <form id="fullday-form" class="crear-form">
        <?php wp_nonce_field('fullday_users_nonce', 'fullday_nonce_field'); ?>
        <input type="hidden" id="form_mode" name="form_mode" value="<?php echo $is_edit_mode ? 'edit' : 'create'; ?>">
        <input type="hidden" id="edit_post_id" name="edit_post_id" value="<?php echo esc_attr($post_id); ?>">

        <!-- Título -->
        <div class="form-group">
            <label for="fullday_title">Título del Full Day *</label>
            <input type="text" id="fullday_title" name="fullday_title" value="<?php echo esc_attr($title); ?>" placeholder="Ej: Aventura en la Montaña" required>
        </div>

        <!-- Precios -->
        <div class="form-row">
            <div class="form-group">
                <label for="fullday_price">Precio de Venta (USD) *</label>
                <input type="number" id="fullday_price" name="fullday_price" value="<?php echo esc_attr($price); ?>" placeholder="99.00" min="0" step="0.01" required>
                <small class="field-hint">Precio actual que pagarán los clientes</small>
            </div>

            <div class="form-group">
                <label for="fullday_original_price">Precio Original (USD)</label>
                <input type="number" id="fullday_original_price" name="fullday_original_price" value="<?php echo esc_attr($original_price); ?>" placeholder="150.00" min="0" step="0.01">
                <small class="field-hint">Opcional: Si hay descuento, ingresa el precio antes de la rebaja</small>
            </div>
        </div>

        <!-- Preview de Descuento -->
        <div id="discount-preview" class="discount-preview" style="display: none;">
            <div class="discount-badge">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <span id="discount-percentage">0</span>% de descuento
            </div>
            <p class="discount-savings">Ahorro de $<span id="discount-amount">0</span> USD</p>
        </div>

        <!-- Descripción -->
        <div class="form-group">
            <label for="fullday_description">Descripción *</label>
            <textarea id="fullday_description" name="fullday_description" rows="5" required><?php echo esc_textarea($description); ?></textarea>
        </div>

        <!-- Destino, Fecha de Salida y Duración -->
        <div class="form-row">
            <div class="form-group">
                <label for="fullday_destination">Destino *</label>
                <input type="text" id="fullday_destination" name="fullday_destination" value="<?php echo esc_attr($destination); ?>" placeholder="Ciudad, País" required>
            </div>

            <div class="form-group">
                <label for="fullday_departure_date">Fecha de Salida *</label>
                <input type="date" id="fullday_departure_date" name="fullday_departure_date" value="<?php echo esc_attr($departure_date); ?>" min="<?php echo date('Y-m-d'); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="fullday_duration">Duración *</label>
            <input type="text" id="fullday_duration" name="fullday_duration" value="<?php echo esc_attr($duration); ?>" placeholder="8 horas" required>
        </div>

        <!-- Categoría y Edad Mínima -->
        <div class="form-row">
            <div class="form-group">
                <label for="fullday_category">Categoría *</label>
                <div class="custom-select-wrapper">
                    <div class="custom-select-trigger" id="category-trigger">
                        <span class="selected-text<?php echo $category ? '' : ' placeholder'; ?>"><?php echo $category ? esc_html($category) : 'Selecciona una categoría'; ?></span>
                        <svg class="select-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                    <div class="custom-select-dropdown" id="category-dropdown">
                        <div class="select-search">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                            <input type="text" id="category-search" placeholder="Buscar categoría..." autocomplete="off">
                        </div>
                        <div class="select-options" id="category-options">
                            <?php if ($categories && !is_wp_error($categories)): ?>
                                <?php foreach ($categories as $cat): ?>
                                    <div class="select-option<?php echo ($cat->name === $category) ? ' selected' : ''; ?>" data-value="<?php echo esc_attr($cat->name); ?>">
                                        <?php echo esc_html($cat->name); ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="select-option-empty">No hay categorías disponibles</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <input type="hidden" id="fullday_category" name="fullday_category" value="<?php echo esc_attr($category); ?>" required>
                </div>
                <small class="field-hint">Busca o selecciona una categoría</small>
            </div>

            <div class="form-group">
                <label for="fullday_min_age">Edad Mínima</label>
                <input type="number" id="fullday_min_age" name="fullday_min_age" value="<?php echo esc_attr($min_age); ?>" placeholder="18" min="0" max="100">
                <small class="field-hint">Edad mínima requerida para participar</small>
            </div>
        </div>

        <!-- Selector de Regiones (Paradas) -->
        <div class="form-group">
            <label for="region-selector">Regiones de Salida (Paradas) *</label>
            <div class="region-selector-container">
                <div class="region-search-container">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="text" id="region-search" placeholder="Buscar ciudad..." autocomplete="off">
                </div>

                <div class="region-options-container" id="region-options-container">
                    <?php if ($regions && !is_wp_error($regions)): ?>
                        <?php foreach ($regions as $estado): ?>
                            <?php
                            // Obtener las ciudades de este estado
                            $ciudades = get_terms(array(
                                'taxonomy' => 'region',
                                'hide_empty' => false,
                                'parent' => $estado->term_id,
                            ));
                            ?>
                            <?php if ($ciudades && !is_wp_error($ciudades)): ?>
                                <div class="region-state-group">
                                    <div class="region-state-name"><?php echo esc_html($estado->name); ?></div>
                                    <?php foreach ($ciudades as $ciudad): ?>
                                        <div class="region-option" data-region-id="<?php echo $ciudad->term_id; ?>" data-region-name="<?php echo esc_attr($ciudad->name); ?>" data-state-name="<?php echo esc_attr($estado->name); ?>">
                                            <span class="region-option-text"><?php echo esc_html($ciudad->name); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="region-option-empty">No hay regiones disponibles</div>
                    <?php endif; ?>
                </div>

                <div class="region-selected-container" id="region-selected-container">
                    <div class="region-selected-header">
                        <span>Paradas seleccionadas (<span id="region-count">0</span>/3)</span>
                        <button type="button" class="btn-clear-regions" id="btn-clear-regions">Limpiar</button>
                    </div>
                    <div class="region-selected-list" id="region-selected-list">
                        <div class="region-selected-empty">No hay paradas seleccionadas</div>
                    </div>
                </div>

                <input type="hidden" id="region_order" name="region_order" value="">
                <input type="hidden" id="existing_region_order" value='<?php echo esc_attr(json_encode($selected_regions_order)); ?>'>
            </div>
            <small class="field-hint">Selecciona hasta 3 ciudades en el orden de las paradas del viaje. Ej: Maracaibo → Cabimas → Ojeda</small>
        </div>

        <!-- Máximo Participantes -->
        <div class="form-group">
            <label for="fullday_max_people">Máximo Participantes *</label>
            <input type="number" id="fullday_max_people" name="fullday_max_people" value="<?php echo esc_attr($max_people); ?>" placeholder="12" min="1" required>
        </div>

        <!-- Qué Incluye -->
        <div class="form-group">
            <label for="fullday_includes">Qué Incluye *</label>
            <textarea id="fullday_includes" name="fullday_includes" rows="4" required><?php echo esc_textarea($includes); ?></textarea>
            <small class="field-hint">Escribe cada ítem en una línea separada</small>
        </div>

        <!-- Itinerario Dinámico -->
        <div class="form-group">
            <label>Itinerario *</label>
            <div class="itinerary-container" id="itinerary-container">
                <?php foreach ($itinerary_items as $item): ?>
                <div class="itinerary-item">
                    <div class="itinerary-row">
                        <input type="time" class="itinerary-time" value="<?php echo esc_attr($item['time']); ?>">
                        <input type="text" class="itinerary-description" value="<?php echo esc_attr($item['description']); ?>" placeholder="Descripción de la actividad">
                        <button type="button" class="btn-remove-itinerary" title="Eliminar">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn-add-itinerary" id="btn-add-itinerary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Agregar Item al Itinerario
            </button>
            <input type="hidden" id="fullday_itinerary" name="fullday_itinerary">
            <small class="field-hint">Usa el selector de hora para cada actividad. Mínimo 1 item requerido</small>
        </div>

        <!-- Imagen Destacada -->
        <div class="form-group">
            <label for="fullday_featured_image">Imagen Destacada *</label>
            <div class="image-upload-container featured-image-container">
                <div class="featured-image-preview<?php echo $featured_image_url ? ' has-image' : ''; ?>" id="featured-image-preview">
                    <?php if ($featured_image_url): ?>
                        <img src="<?php echo esc_url($featured_image_url); ?>" alt="Imagen destacada">
                    <?php else: ?>
                        <div class="image-placeholder">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            <p>Arrastra la imagen aquí o</p>
                            <button type="button" class="btn-secondary" id="btn-upload-featured">Seleccionar Archivo</button>
                        </div>
                    <?php endif; ?>
                </div>
                <input type="file" id="fullday_featured_image" accept="image/jpeg,image/png,image/jpg" style="display: none;">
                <input type="hidden" id="featured_image_id" name="featured_image_id" value="<?php echo esc_attr($featured_image_id); ?>">
            </div>
            <small class="field-hint">Esta es la imagen que se mostrará en la portada de tu servicio</small>
        </div>

        <!-- Galería de Imágenes -->
        <div class="form-group">
            <label for="fullday_gallery">Imágenes de la Galería *</label>
            <div class="gallery-upload-container">
                <div class="gallery-preview" id="gallery-preview">
                    <div class="gallery-placeholder">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                        <p>Arrastra las imágenes aquí o</p>
                        <button type="button" class="btn-secondary" id="btn-upload-gallery">Seleccionar Archivos</button>
                    </div>
                </div>
                <input type="file" id="fullday_gallery" accept="image/jpeg,image/png,image/jpg" multiple style="display: none;">
                <input type="hidden" id="gallery_images_ids" name="gallery_images_ids">
                <input type="hidden" id="existing_gallery_data" value='<?php echo esc_attr(json_encode($gallery_data)); ?>'>
            </div>
            <small class="field-hint">Mínimo 2 imágenes requeridas. Puedes seleccionar múltiples archivos</small>
        </div>

        <div class="form-message" id="form-message" style="display: none;"></div>

        <!-- Botones -->
        <div class="form-actions">
            <button type="submit" class="btn-crear" id="btn-submit-fullday">
                <span class="btn-text"><?php echo $is_edit_mode ? 'Actualizar Full Day' : 'Crear Full Day'; ?></span>
                <span class="btn-loader" style="display: none;">
                    <svg class="spinner" width="20" height="20" viewBox="0 0 24 24">
                        <circle class="spinner-circle" cx="12" cy="12" r="10" fill="none" stroke-width="3"></circle>
                    </svg>
                </span>
            </button>
            <button type="button" class="btn-secondary" onclick="jQuery('.dashboard-tab[data-tab=\'mis-viajes\']').click();">Cancelar</button>
        </div>
    </form>
</div>
