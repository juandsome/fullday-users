<?php
/**
 * Vista de Crear Full Day para Proveedor
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

// Si no está aprobado, mostrar mensaje
if (!$is_approved) {
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

// Obtener categorías de full-days
$categories = get_terms(array(
    'taxonomy' => 'full_days_category',
    'hide_empty' => false,
));
?>

<div class="crear-container">
    <div class="crear-header">
        <h2 class="crear-title">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Crear Nuevo Full Day
        </h2>
        <p class="crear-description">Completa la información para crear tu experiencia</p>
    </div>

    <form id="crear-fullday-form" class="crear-form">
        <?php wp_nonce_field('fullday_users_nonce', 'fullday_nonce_field'); ?>

        <!-- Título -->
        <div class="form-group">
            <label for="fullday_title">Título del Full Day *</label>
            <input type="text" id="fullday_title" name="fullday_title" value="Aventura en Montaña y Cascadas" placeholder="Ej: Aventura en la Montaña" required>
        </div>

        <!-- Precios -->
        <div class="form-row">
            <div class="form-group">
                <label for="fullday_price">Precio de Venta (USD) *</label>
                <input type="number" id="fullday_price" name="fullday_price" value="89.00" placeholder="99.00" min="0" step="0.01" required>
                <small class="field-hint">Precio actual que pagarán los clientes</small>
            </div>

            <div class="form-group">
                <label for="fullday_original_price">Precio Original (USD)</label>
                <input type="number" id="fullday_original_price" name="fullday_original_price" value="120.00" placeholder="150.00" min="0" step="0.01">
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
            <textarea id="fullday_description" name="fullday_description" rows="5" required>Vive una experiencia única explorando las montañas y cascadas más hermosas de la región. Incluye caminatas guiadas, actividades de aventura y vistas espectaculares. Perfecto para amantes de la naturaleza y la aventura.</textarea>
        </div>

        <!-- Destino y Duración -->
        <div class="form-row">
            <div class="form-group">
                <label for="fullday_destination">Destino *</label>
                <input type="text" id="fullday_destination" name="fullday_destination" value="Baños de Agua Santa, Ecuador" placeholder="Ciudad, País" required>
            </div>

            <div class="form-group">
                <label for="fullday_duration">Duración *</label>
                <input type="text" id="fullday_duration" name="fullday_duration" value="8 horas" placeholder="8 horas" required>
            </div>
        </div>

        <!-- Categoría y Edad Mínima -->
        <div class="form-row">
            <div class="form-group">
                <label for="fullday_category">Categoría *</label>
                <div class="custom-select-wrapper">
                    <div class="custom-select-trigger" id="category-trigger">
                        <span class="selected-text">Selecciona una categoría</span>
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
                                <?php foreach ($categories as $category): ?>
                                    <div class="select-option" data-value="<?php echo esc_attr($category->name); ?>">
                                        <?php echo esc_html($category->name); ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="select-option-empty">No hay categorías disponibles</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <input type="hidden" id="fullday_category" name="fullday_category" required>
                </div>
                <small class="field-hint">Busca o selecciona una categoría</small>
            </div>

            <div class="form-group">
                <label for="fullday_min_age">Edad Mínima</label>
                <input type="number" id="fullday_min_age" name="fullday_min_age" value="12" placeholder="18" min="0" max="100">
                <small class="field-hint">Edad mínima requerida para participar</small>
            </div>
        </div>

        <!-- Máximo Participantes y Teléfono -->
        <div class="form-row">
            <div class="form-group">
                <label for="fullday_max_people">Máximo Participantes *</label>
                <input type="number" id="fullday_max_people" name="fullday_max_people" value="15" placeholder="12" min="1" required>
            </div>

            <div class="form-group">
                <label for="fullday_phone_number">Teléfono WhatsApp</label>
                <input type="tel" id="fullday_phone_number" name="fullday_phone_number" placeholder="+593 99 123 4567">
                <small class="field-hint">Número de contacto para reservas</small>
            </div>
        </div>

        <!-- Mensaje WhatsApp -->
        <div class="form-group">
            <label for="fullday_whatsapp_message">Mensaje WhatsApp</label>
            <textarea id="fullday_whatsapp_message" name="fullday_whatsapp_message" rows="3" placeholder="Hola, estoy interesado en el tour..."></textarea>
            <small class="field-hint">Mensaje predeterminado opcional para WhatsApp</small>
        </div>

        <!-- Qué Incluye -->
        <div class="form-group">
            <label for="fullday_includes">Qué Incluye *</label>
            <textarea id="fullday_includes" name="fullday_includes" rows="4" required>Transporte ida y vuelta
Guía bilingüe certificado
Almuerzo típico ecuatoriano
Equipo de seguridad
Seguro de accidentes
Fotografías digitales</textarea>
            <small class="field-hint">Escribe cada ítem en una línea separada</small>
        </div>

        <!-- Itinerario Dinámico -->
        <div class="form-group">
            <label>Itinerario *</label>
            <div class="itinerary-container" id="itinerary-container">
                <div class="itinerary-item">
                    <div class="itinerary-row">
                        <input type="time" class="itinerary-time" value="07:00">
                        <input type="text" class="itinerary-description" value="Salida desde punto de encuentro" placeholder="Descripción de la actividad">
                        <button type="button" class="btn-remove-itinerary" title="Eliminar">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="itinerary-item">
                    <div class="itinerary-row">
                        <input type="time" class="itinerary-time" value="09:00">
                        <input type="text" class="itinerary-description" value="Llegada y caminata hacia la primera cascada" placeholder="Descripción de la actividad">
                        <button type="button" class="btn-remove-itinerary" title="Eliminar">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="itinerary-item">
                    <div class="itinerary-row">
                        <input type="time" class="itinerary-time" value="12:30">
                        <input type="text" class="itinerary-description" value="Almuerzo en restaurante local" placeholder="Descripción de la actividad">
                        <button type="button" class="btn-remove-itinerary" title="Eliminar">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="itinerary-item">
                    <div class="itinerary-row">
                        <input type="time" class="itinerary-time" value="15:00">
                        <input type="text" class="itinerary-description" value="Retorno al punto de encuentro" placeholder="Descripción de la actividad">
                        <button type="button" class="btn-remove-itinerary" title="Eliminar">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                </div>
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
                <div class="featured-image-preview" id="featured-image-preview">
                    <div class="image-placeholder">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                        <p>Arrastra la imagen aquí o</p>
                        <button type="button" class="btn-secondary" id="btn-upload-featured">Seleccionar Archivo</button>
                    </div>
                </div>
                <input type="file" id="fullday_featured_image" accept="image/jpeg,image/png,image/jpg" style="display: none;">
                <input type="hidden" id="featured_image_id" name="featured_image_id">
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
            </div>
            <small class="field-hint">Mínimo 2 imágenes requeridas. Puedes seleccionar múltiples archivos</small>
        </div>

        <div class="form-message" id="crear-message" style="display: none;"></div>

        <!-- Botones -->
        <div class="form-actions">
            <button type="submit" class="btn-crear" id="btn-crear-fullday">
                <span class="btn-text">Crear Full Day</span>
                <span class="btn-loader" style="display: none;">
                    <svg class="spinner" width="20" height="20" viewBox="0 0 24 24">
                        <circle class="spinner-circle" cx="12" cy="12" r="10" fill="none" stroke-width="3"></circle>
                    </svg>
                </span>
            </button>
            <button type="button" class="btn-secondary" id="btn-guardar-borrador">Guardar Borrador</button>
        </div>
    </form>
</div>
