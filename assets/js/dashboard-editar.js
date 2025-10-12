/**
 * JavaScript para Dashboard Editar Full Day
 * Fullday Users Plugin
 *
 * Nota: Comparte funcionalidad con dashboard-crear.js
 */

(function($) {
    'use strict';

    let featuredImageId = null;
    let galleryImagesIds = [];
    let postId = null;

    $(document).ready(function() {
        postId = $('#edit_post_id').val();

        // Pre-cargar datos existentes
        loadExistingData();

        // Inicializar funciones (reutilizadas de crear.js)
        initFeaturedImageUpload();
        initGalleryUpload();
        initItinerary();
        initDiscountCalculator();
        initCategorySearch();
        initFormSubmit();
    });

    /**
     * Cargar datos existentes
     */
    function loadExistingData() {
        // Cargar imagen destacada existente
        const existingFeaturedId = $('#featured_image_id').val();
        if (existingFeaturedId) {
            featuredImageId = parseInt(existingFeaturedId);
        }

        // Cargar galería existente
        const existingGalleryData = $('#existing_gallery_data').val();
        console.log('Existing gallery data:', existingGalleryData);

        if (existingGalleryData && existingGalleryData !== '' && existingGalleryData !== '[]') {
            try {
                const galleryUrls = JSON.parse(existingGalleryData);
                console.log('Parsed gallery URLs:', galleryUrls);

                if (Array.isArray(galleryUrls) && galleryUrls.length > 0) {
                    // Convertir URLs a objetos con IDs ficticios
                    galleryImagesIds = galleryUrls.map((url, index) => ({
                        id: 'existing_' + index,
                        url: url,
                        isExisting: true
                    }));

                    console.log('Gallery images loaded:', galleryImagesIds.length, 'images');
                    updateGalleryPreview();
                    updateGalleryIdsInput();
                } else {
                    console.log('Gallery is empty or not an array');
                }
            } catch (e) {
                console.error('Error parsing gallery data:', e);
            }
        } else {
            console.log('No existing gallery data found');
        }

        // Trigger cálculo de descuento inicial
        calculateDiscount();
    }

    /**
     * Inicializar upload de imagen destacada
     */
    function initFeaturedImageUpload() {
        const $featuredPreview = $('#featured-image-preview');

        // Click en botón
        $('#btn-upload-featured').on('click', function() {
            $('#fullday_featured_image').click();
        });

        // Selección de archivo
        $('#fullday_featured_image').on('change', function() {
            if (this.files.length > 0) {
                uploadFeaturedImage(this.files[0]);
                $(this).val('');
            }
        });

        // Drag and drop
        $featuredPreview.on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('dragover');
        });

        $featuredPreview.on('dragleave', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
        });

        $featuredPreview.on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');

            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0 && (files[0].type === 'image/jpeg' || files[0].type === 'image/png' || files[0].type === 'image/jpg')) {
                uploadFeaturedImage(files[0]);
            }
        });
    }

    /**
     * Upload de imagen destacada
     */
    function uploadFeaturedImage(file) {
        const formData = new FormData();
        formData.append('action', 'fullday_upload_fullday_image');
        formData.append('nonce', fulldayUsers.nonce);
        formData.append('image', file);
        formData.append('image_type', 'featured');

        // Mostrar loading
        const $preview = $('#featured-image-preview');
        $preview.html('<div class="image-placeholder"><div class="spinner-upload"></div><p>Subiendo imagen...</p></div>');

        $.ajax({
            url: fulldayUsers.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    featuredImageId = response.data.attachment_id;
                    $('#featured_image_id').val(featuredImageId);

                    const imgHtml = '<img src="' + response.data.url + '" alt="Imagen destacada">';
                    $preview.html(imgHtml).addClass('has-image');
                } else {
                    showMessage(response.data.message || 'Error al subir la imagen', 'error');
                    resetFeaturedImagePreview();
                }
            },
            error: function() {
                showMessage('Error de conexión. Intenta nuevamente.', 'error');
                resetFeaturedImagePreview();
            }
        });
    }

    function resetFeaturedImagePreview() {
        const $preview = $('#featured-image-preview');
        $preview.removeClass('has-image').html(`
            <div class="image-placeholder">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
                <p>Arrastra la imagen aquí o</p>
                <button type="button" class="btn-secondary" id="btn-upload-featured">Seleccionar Archivo</button>
            </div>
        `);
    }

    /**
     * Inicializar upload de galería
     */
    function initGalleryUpload() {
        // Click en botón
        $(document).on('click', '#btn-upload-gallery', function() {
            $('#fullday_gallery').click();
        });

        // Selección de archivos
        $('#fullday_gallery').on('change', function() {
            if (this.files.length > 0) {
                uploadGalleryImages(this.files);
                $(this).val('');
            }
        });

        // Drag and drop
        const $galleryPreview = $('#gallery-preview');

        $galleryPreview.on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('dragover');
        });

        $galleryPreview.on('dragleave', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
        });

        $galleryPreview.on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');

            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                uploadGalleryImages(files);
            }
        });

        // Eliminar imagen de galería
        $(document).on('click', '.btn-remove-gallery-image', function() {
            const index = $(this).data('index');
            galleryImagesIds.splice(index, 1);
            updateGalleryPreview();
            updateGalleryIdsInput();
        });
    }

    /**
     * Upload de imágenes de galería
     */
    function uploadGalleryImages(files) {
        const validFiles = Array.from(files).filter(file => {
            return file.type === 'image/jpeg' || file.type === 'image/png' || file.type === 'image/jpg';
        });

        if (validFiles.length === 0) {
            showMessage('Solo se permiten archivos JPG, JPEG o PNG', 'error');
            return;
        }

        // Mostrar loader durante upload
        const $galleryPreview = $('#gallery-preview');

        // Si ya hay imágenes, agregar loader temporal
        if (galleryImagesIds.length > 0) {
            $galleryPreview.append('<div class="gallery-upload-loader"><div class="spinner-upload"></div><p>Subiendo ' + validFiles.length + ' imagen(es)...</p></div>');
        } else {
            // Si no hay imágenes, reemplazar todo con loader
            $galleryPreview.html('<div class="gallery-placeholder"><div class="spinner-upload"></div><p>Subiendo ' + validFiles.length + ' imagen(es)...</p></div>');
        }

        // Upload cada imagen
        let uploadedCount = 0;
        validFiles.forEach((file, index) => {
            uploadSingleGalleryImage(file, function() {
                uploadedCount++;
                if (uploadedCount === validFiles.length) {
                    // Remover loader
                    $('.gallery-upload-loader').remove();
                    updateGalleryPreview();
                }
            });
        });
    }

    /**
     * Upload de imagen individual de galería
     */
    function uploadSingleGalleryImage(file, callback) {
        const formData = new FormData();
        formData.append('action', 'fullday_upload_fullday_image');
        formData.append('nonce', fulldayUsers.nonce);
        formData.append('image', file);
        formData.append('image_type', 'gallery');

        $.ajax({
            url: fulldayUsers.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    galleryImagesIds.push({
                        id: response.data.attachment_id,
                        url: response.data.url,
                        isExisting: false
                    });
                    updateGalleryIdsInput();
                }
                callback();
            },
            error: function() {
                callback();
            }
        });
    }

    /**
     * Actualizar preview de galería
     */
    function updateGalleryPreview() {
        const $preview = $('#gallery-preview');

        if (galleryImagesIds.length === 0) {
            $preview.html(`
                <div class="gallery-placeholder">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    <p>Arrastra las imágenes aquí o</p>
                    <button type="button" class="btn-secondary" id="btn-upload-gallery">Seleccionar Archivos</button>
                </div>
            `);
            return;
        }

        $preview.addClass('has-images');

        let gridHtml = '<div class="gallery-images-grid">';
        galleryImagesIds.forEach((img, index) => {
            gridHtml += `
                <div class="gallery-image-item" data-index="${index}">
                    <img src="${img.url}" alt="Imagen ${index + 1}">
                    <button type="button" class="btn-remove-gallery-image" data-index="${index}" title="Eliminar imagen">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            `;
        });
        gridHtml += `
            <div class="gallery-image-item gallery-add-more">
                <button type="button" class="btn-add-gallery" id="btn-upload-gallery">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <p>Agregar más</p>
                </button>
            </div>
        </div>`;

        $preview.html(gridHtml);
    }

    /**
     * Actualizar input hidden con IDs de galería
     */
    function updateGalleryIdsInput() {
        const ids = galleryImagesIds.map(img => {
            // Si es imagen existente (URL), mantener el formato
            if (img.isExisting) {
                return 'url:' + img.url;
            }
            return img.id;
        }).join(',');

        $('#gallery_images_ids').val(ids);
    }

    /**
     * Inicializar funcionalidad de itinerario dinámico
     */
    function initItinerary() {
        // Agregar nuevo item
        $('#btn-add-itinerary').on('click', function() {
            addItineraryItem();
        });

        // Bind remove buttons
        bindRemoveItineraryButtons();
    }

    /**
     * Agregar nuevo item de itinerario
     */
    function addItineraryItem() {
        const itemHtml = `
            <div class="itinerary-item">
                <div class="itinerary-row">
                    <input type="time" class="itinerary-time">
                    <input type="text" class="itinerary-description" placeholder="Descripción de la actividad">
                    <button type="button" class="btn-remove-itinerary" title="Eliminar">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>
        `;

        $('#itinerary-container').append(itemHtml);
        bindRemoveItineraryButtons();
    }

    /**
     * Bind botones de eliminar itinerario
     */
    function bindRemoveItineraryButtons() {
        $('.btn-remove-itinerary').off('click').on('click', function() {
            const $container = $('#itinerary-container');
            const itemCount = $container.find('.itinerary-item').length;

            // No permitir eliminar si solo hay un item
            if (itemCount <= 1) {
                showMessage('Debe haber al menos un item en el itinerario', 'error');
                return;
            }

            $(this).closest('.itinerary-item').remove();
        });
    }

    /**
     * Inicializar calculadora de descuento
     */
    function initDiscountCalculator() {
        $('#fullday_price, #fullday_original_price').on('input', function() {
            calculateDiscount();
        });
    }

    /**
     * Calcular descuento automáticamente
     */
    function calculateDiscount() {
        const salePrice = parseFloat($('#fullday_price').val()) || 0;
        const originalPrice = parseFloat($('#fullday_original_price').val()) || 0;

        // Si hay precio original y precio de venta, calcular descuento
        if (originalPrice > 0 && salePrice > 0 && originalPrice > salePrice) {
            const discountAmount = originalPrice - salePrice;
            const discountPercentage = Math.round((discountAmount / originalPrice) * 100);

            // Mostrar preview
            $('#discount-percentage').text(discountPercentage);
            $('#discount-amount').text(discountAmount.toFixed(2));
            $('#discount-preview').fadeIn();
        } else {
            // Ocultar preview
            $('#discount-preview').fadeOut();
        }
    }

    /**
     * Inicializar búsqueda en categoría
     */
    function initCategorySearch() {
        const $form = $('#editar-fullday-form');
        const $trigger = $form.find('#category-trigger');
        const $dropdown = $form.find('#category-dropdown');
        const $search = $form.find('#category-search');
        const $hiddenInput = $form.find('#fullday_category');
        const $selectedText = $form.find('.selected-text');

        // Toggle dropdown
        $trigger.on('click', function(e) {
            e.stopPropagation();
            $trigger.toggleClass('active');
            $dropdown.toggleClass('active');

            if ($dropdown.hasClass('active')) {
                $search.focus();
            }
        });

        // Cerrar dropdown al hacer click fuera
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#editar-fullday-form .custom-select-wrapper').length) {
                $trigger.removeClass('active');
                $dropdown.removeClass('active');
            }
        });

        // Búsqueda en tiempo real
        $search.on('input', function() {
            const searchTerm = $(this).val().toLowerCase();

            $form.find('.select-option').each(function() {
                const optionText = $(this).text().toLowerCase();
                if (optionText.includes(searchTerm)) {
                    $(this).removeClass('hidden');
                } else {
                    $(this).addClass('hidden');
                }
            });
        });

        // Seleccionar opción
        $form.on('click', '.select-option', function() {
            const value = $(this).data('value');
            const text = $(this).text();

            // Actualizar hidden input
            $hiddenInput.val(value);

            // Actualizar texto seleccionado
            $selectedText.text(text).removeClass('placeholder');

            // Marcar como seleccionado
            $form.find('.select-option').removeClass('selected');
            $(this).addClass('selected');

            // Cerrar dropdown
            $trigger.removeClass('active');
            $dropdown.removeClass('active');

            // Limpiar búsqueda
            $search.val('');
            $form.find('.select-option').removeClass('hidden');
        });
    }

    /**
     * Convertir items de itinerario al formato requerido
     */
    function buildItineraryString() {
        const items = [];
        let isValid = true;

        $('.itinerary-item').each(function() {
            const time = $(this).find('.itinerary-time').val();
            const description = $(this).find('.itinerary-description').val().trim();

            // Si alguno está vacío, es inválido
            if (!time || !description) {
                showMessage('Completa todos los campos del itinerario (hora y descripción)', 'error');
                isValid = false;
                return false; // Break loop
            }

            // El input type="time" ya devuelve formato HH:MM automáticamente
            items.push(time + ', ' + description);
        });

        if (!isValid) {
            return null;
        }

        // Retornar formato: cada línea separada por salto de línea
        return items.join('\n');
    }

    /**
     * Inicializar submit del formulario
     */
    function initFormSubmit() {
        const $form = $('#editar-fullday-form');

        // Validar campos antes del submit
        $form.on('submit', function(e) {
            e.preventDefault();

            // Validar campos requeridos uno por uno
            const requiredFields = [
                { id: '#fullday_title', label: 'Título del Full Day' },
                { id: '#fullday_price', label: 'Precio de Venta' },
                { id: '#fullday_description', label: 'Descripción' },
                { id: '#fullday_destination', label: 'Destino' },
                { id: '#fullday_duration', label: 'Duración' },
                { id: '#fullday_category', label: 'Categoría' },
                { id: '#fullday_max_people', label: 'Máximo Participantes' },
                { id: '#fullday_includes', label: 'Qué Incluye' }
            ];

            // Verificar cada campo
            for (let field of requiredFields) {
                const value = $(field.id).val();
                if (!value || value.trim() === '') {
                    showMessage('Por favor completa el campo: ' + field.label, 'error');
                    $(field.id).focus();
                    return;
                }
            }

            // Validar imagen destacada
            if (!featuredImageId) {
                showMessage('Por favor completa el campo: Imagen Destacada', 'error');
                $('#btn-upload-featured').focus();
                return;
            }

            // Validar galería (mínimo 2 imágenes)
            if (galleryImagesIds.length < 2) {
                showMessage('Por favor completa el campo: Imágenes de la Galería (mínimo 2)', 'error');
                $('#btn-upload-gallery').focus();
                return;
            }

            // Construir y validar itinerario
            const itineraryString = buildItineraryString();
            if (itineraryString === null) {
                return; // Error ya mostrado en buildItineraryString
            }

            if (!itineraryString || itineraryString.trim() === '') {
                showMessage('Por favor completa el campo: Itinerario (mínimo 1 item)', 'error');
                $('.itinerary-time').first().focus();
                return;
            }

            // Asignar itinerario al input hidden
            $('#fullday_itinerary').val(itineraryString);

            const formData = new FormData(this);
            formData.append('action', 'fullday_update_fullday');
            formData.append('nonce', fulldayUsers.nonce);

            const $btn = $('#btn-editar-fullday');
            $btn.prop('disabled', true);
            $btn.find('.btn-text').hide();
            $btn.find('.btn-loader').show();

            $.ajax({
                url: fulldayUsers.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showMessage('¡Full Day actualizado exitosamente!', 'success');

                        // Redirigir a Mis Viajes después de 2 segundos
                        setTimeout(function() {
                            $('.dashboard-tab[data-tab="mis-viajes"]').click();
                        }, 2000);
                    } else {
                        showMessage(response.data.message || 'Error al actualizar el Full Day', 'error');
                    }
                },
                error: function() {
                    showMessage('Error de conexión. Intenta nuevamente.', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $btn.find('.btn-text').show();
                    $btn.find('.btn-loader').hide();
                }
            });
        });
    }

    /**
     * Mostrar mensaje
     */
    function showMessage(message, type) {
        const $messageDiv = $('#editar-message');
        $messageDiv
            .removeClass('success error')
            .addClass(type)
            .text(message)
            .fadeIn();

        setTimeout(function() {
            $messageDiv.fadeOut();
        }, 8000);

        // Scroll al top
        $('html, body').animate({
            scrollTop: 0
        }, 400);
    }

})(jQuery);
