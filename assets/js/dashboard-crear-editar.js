/**
 * JavaScript Unificado para Crear/Editar Full Day
 * Fullday Users Plugin
 */

(function($) {
    'use strict';

    let featuredImageId = null;
    let galleryImagesIds = [];
    let formMode = 'create'; // 'create' o 'edit'

    $(document).ready(function() {
        // Solo inicializar si el formulario existe
        if ($('#fullday-form').length === 0) {
            return;
        }

        // Determinar el modo del formulario
        formMode = $('#form_mode').val() || 'create';
        console.log('Form mode:', formMode);

        // Si estamos en modo editar, cargar datos existentes
        if (formMode === 'edit') {
            loadExistingData();
        }

        // Inicializar funciones
        initFeaturedImageUpload();
        initGalleryUpload();
        initItinerary();
        initDiscountCalculator();
        initCategorySearch();
        initRegionSelector();
        initFormSubmit();
    });

    /**
     * Cargar datos existentes (solo en modo editar)
     */
    function loadExistingData() {
        console.log('Loading existing data...');

        // Cargar imagen destacada existente
        const existingFeaturedId = $('#featured_image_id').val();
        if (existingFeaturedId) {
            featuredImageId = parseInt(existingFeaturedId);
            console.log('Featured image ID:', featuredImageId);
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

        // Click en botón (usar delegación de eventos)
        $(document).on('click', '#btn-upload-featured', function(e) {
            e.preventDefault();
            $('#fullday_featured_image').click();
        });

        // Click en botón de eliminar imagen destacada
        $(document).on('click', '.btn-remove-featured-image', function(e) {
            e.preventDefault();
            e.stopPropagation();
            removeFeaturedImage();
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

        // Si ya hay imagen destacada en modo editar, agregar botón X
        if ($featuredPreview.hasClass('has-image') && $featuredPreview.find('img').length > 0) {
            addRemoveButtonToFeaturedImage();
        }
    }

    /**
     * Agregar botón de eliminar a imagen destacada existente
     */
    function addRemoveButtonToFeaturedImage() {
        const $preview = $('#featured-image-preview');
        if ($preview.find('.btn-remove-featured-image').length === 0) {
            const btnHtml = `
                <button type="button" class="btn-remove-featured-image" title="Eliminar imagen">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            `;
            $preview.append(btnHtml);
        }
    }

    /**
     * Eliminar imagen destacada
     */
    function removeFeaturedImage() {
        featuredImageId = null;
        $('#featured_image_id').val('');
        resetFeaturedImagePreview();
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

                    const imgHtml = `
                        <img src="${response.data.url}" alt="Imagen destacada">
                        <button type="button" class="btn-remove-featured-image" title="Eliminar imagen">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    `;
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

    /**
     * Reset preview de imagen destacada
     */
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
        // Click en botón (usar delegación de eventos)
        $(document).on('click', '#btn-upload-gallery', function(e) {
            e.preventDefault();
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

        // Eliminar imagen de galería (usar delegación de eventos)
        $(document).on('click', '.btn-remove-gallery-image', function(e) {
            e.preventDefault();
            const index = $(this).data('index');
            console.log('Removing gallery image at index:', index);
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
        console.log('Gallery IDs updated:', ids);
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
        const $trigger = $('#category-trigger');
        const $dropdown = $('#category-dropdown');
        const $search = $('#category-search');
        const $hiddenInput = $('#fullday_category');
        const $selectedText = $('.selected-text');

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
            if (!$(e.target).closest('.custom-select-wrapper').length) {
                $trigger.removeClass('active');
                $dropdown.removeClass('active');
            }
        });

        // Búsqueda en tiempo real
        $search.on('input', function() {
            const searchTerm = $(this).val().toLowerCase();

            $('.select-option').each(function() {
                const optionText = $(this).text().toLowerCase();
                if (optionText.includes(searchTerm)) {
                    $(this).removeClass('hidden');
                } else {
                    $(this).addClass('hidden');
                }
            });
        });

        // Seleccionar opción
        $(document).on('click', '.select-option', function() {
            const value = $(this).data('value');
            const text = $(this).text();

            // Actualizar hidden input
            $hiddenInput.val(value);

            // Actualizar texto seleccionado
            $selectedText.text(text).removeClass('placeholder');

            // Marcar como seleccionado
            $('.select-option').removeClass('selected');
            $(this).addClass('selected');

            // Cerrar dropdown
            $trigger.removeClass('active');
            $dropdown.removeClass('active');

            // Limpiar búsqueda
            $search.val('');
            $('.select-option').removeClass('hidden');
        });
    }

    /**
     * Inicializar selector de regiones con orden
     */
    function initRegionSelector() {
        let selectedRegions = []; // Array de objetos {id, name, stateName, order}

        // Cargar regiones existentes si estamos en modo editar
        const existingRegionOrder = $('#existing_region_order').val();
        if (existingRegionOrder && existingRegionOrder !== '' && existingRegionOrder !== '{}') {
            try {
                const savedOrder = JSON.parse(existingRegionOrder);
                console.log('Existing region order:', savedOrder);

                // Convertir el objeto savedOrder a array de regiones seleccionadas
                Object.keys(savedOrder).forEach(regionId => {
                    const order = savedOrder[regionId];
                    const $option = $(`.region-option[data-region-id="${regionId}"]`);
                    if ($option.length > 0) {
                        selectedRegions.push({
                            id: regionId,
                            name: $option.data('region-name'),
                            stateName: $option.data('state-name'),
                            order: order
                        });
                    }
                });

                // Ordenar por el valor de order
                selectedRegions.sort((a, b) => a.order - b.order);

                // Actualizar la UI
                updateSelectedRegionsUI();
                updateRegionOrderInput();
            } catch (e) {
                console.error('Error parsing existing region order:', e);
            }
        }

        // Búsqueda en tiempo real
        $('#region-search').on('input', function() {
            const searchTerm = $(this).val().toLowerCase().trim();

            if (searchTerm === '') {
                $('.region-option, .region-state-group').show();
                return;
            }

            $('.region-option').each(function() {
                const regionName = $(this).data('region-name').toLowerCase();
                const stateName = $(this).data('state-name').toLowerCase();

                if (regionName.includes(searchTerm) || stateName.includes(searchTerm)) {
                    $(this).show();
                    $(this).closest('.region-state-group').show();
                } else {
                    $(this).hide();
                }
            });

            // Ocultar grupos de estados que no tengan opciones visibles
            $('.region-state-group').each(function() {
                const visibleOptions = $(this).find('.region-option:visible').length;
                if (visibleOptions === 0) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
        });

        // Seleccionar región
        $(document).on('click', '.region-option', function() {
            const regionId = $(this).data('region-id').toString();
            const regionName = $(this).data('region-name');
            const stateName = $(this).data('state-name');

            // Verificar si ya está seleccionada
            const alreadySelected = selectedRegions.find(r => r.id === regionId);
            if (alreadySelected) {
                showMessage('Esta región ya está seleccionada', 'error', true);
                return;
            }

            // Verificar límite de 3
            if (selectedRegions.length >= 3) {
                showMessage('Solo puedes seleccionar hasta 3 paradas', 'error', true);
                return;
            }

            // Agregar a la lista con el siguiente número de orden
            const nextOrder = selectedRegions.length + 1;
            selectedRegions.push({
                id: regionId,
                name: regionName,
                stateName: stateName,
                order: nextOrder
            });

            // Actualizar UI
            updateSelectedRegionsUI();
            updateRegionOrderInput();

            // Marcar como seleccionada
            $(this).addClass('selected');

            // Limpiar búsqueda
            $('#region-search').val('');
            $('.region-option, .region-state-group').show();
        });

        // Eliminar región seleccionada
        $(document).on('click', '.btn-remove-region', function(e) {
            e.stopPropagation();
            const regionId = $(this).data('region-id').toString();

            // Eliminar de la lista
            selectedRegions = selectedRegions.filter(r => r.id !== regionId);

            // Reordenar los números
            selectedRegions.forEach((region, index) => {
                region.order = index + 1;
            });

            // Actualizar UI
            updateSelectedRegionsUI();
            updateRegionOrderInput();

            // Desmarcar en opciones
            $(`.region-option[data-region-id="${regionId}"]`).removeClass('selected');
        });

        // Limpiar todas las regiones
        $('#btn-clear-regions').on('click', function() {
            selectedRegions = [];
            updateSelectedRegionsUI();
            updateRegionOrderInput();
            $('.region-option').removeClass('selected');
        });

        // Actualizar UI de regiones seleccionadas
        function updateSelectedRegionsUI() {
            const $list = $('#region-selected-list');
            const $count = $('#region-count');

            $count.text(selectedRegions.length);

            if (selectedRegions.length === 0) {
                $list.html('<div class="region-selected-empty">No hay paradas seleccionadas</div>');
                return;
            }

            let html = '';
            selectedRegions.forEach(region => {
                html += `
                    <div class="region-selected-item" data-region-id="${region.id}">
                        <span class="region-order-badge">${region.order}</span>
                        <div class="region-selected-info">
                            <span class="region-selected-name">${region.name}</span>
                            <span class="region-selected-state">${region.stateName}</span>
                        </div>
                        <button type="button" class="btn-remove-region" data-region-id="${region.id}" title="Eliminar">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                `;
            });

            $list.html(html);
        }

        // Actualizar input hidden con el orden de regiones
        function updateRegionOrderInput() {
            const regionOrder = {};
            selectedRegions.forEach(region => {
                regionOrder[region.id] = region.order;
            });

            $('#region_order').val(JSON.stringify(regionOrder));
            console.log('Region order updated:', regionOrder);
        }
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
        const $form = $('#fullday-form');

        // Validar campos antes del submit
        $form.on('submit', function(e) {
            e.preventDefault();

            // Validar campos requeridos uno por uno
            const requiredFields = [
                { id: '#fullday_title', label: 'Título del Full Day' },
                { id: '#fullday_price', label: 'Precio de Venta' },
                { id: '#fullday_description', label: 'Descripción' },
                { id: '#fullday_destination', label: 'Destino' },
                { id: '#fullday_departure_date', label: 'Fecha de Salida' },
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

            // Determinar la acción según el modo
            const action = formMode === 'edit' ? 'fullday_update_fullday' : 'fullday_create_fullday';
            formData.append('action', action);

            // Obtener nonce del formulario o de fulldayUsers
            const nonce = (typeof fulldayUsers !== 'undefined' && fulldayUsers.nonce)
                ? fulldayUsers.nonce
                : $('#fullday_nonce_field').val();

            formData.append('nonce', nonce);

            const $btn = $('#btn-submit-fullday');
            $btn.prop('disabled', true);
            $btn.find('.btn-text').hide();
            $btn.find('.btn-loader').show();

            // Fallback para la URL de AJAX
            const ajaxUrl = (typeof fulldayUsers !== 'undefined' && fulldayUsers.ajaxurl)
                ? fulldayUsers.ajaxurl
                : '/wp-admin/admin-ajax.php';

            console.log('=== AJAX REQUEST ===');
            console.log('URL:', ajaxUrl);
            console.log('Action:', action);
            console.log('Form mode:', formMode);
            console.log('FormData keys:', Array.from(formData.keys()));

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('=== RESPONSE FULLDAY FORM ===');
                    console.log('Response:', response);
                    console.log('Success:', response.success);
                    console.log('Data:', response.data);

                    if (response.success) {
                        const message = formMode === 'edit'
                            ? '¡Full Day actualizado exitosamente!'
                            : '¡Full Day creado exitosamente!';
                        showMessage(message, 'success');

                        // Limpiar parámetros de URL y redirigir a Mis Viajes
                        setTimeout(function() {
                            // Limpiar URL de parámetros (post_id, tab)
                            const currentUrl = window.location.href.split('?')[0];
                            window.location.href = currentUrl + '?tab=mis-viajes';
                        }, 2000);
                    } else {
                        console.error('Error en respuesta:', response);
                        const errorMessage = (response.data && response.data.message)
                            ? response.data.message
                            : (formMode === 'edit'
                                ? 'Error al actualizar el Full Day'
                                : 'Error al crear el Full Day');
                        showMessage(errorMessage, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('=== AJAX ERROR ===');
                    console.error('Status:', status);
                    console.error('Error:', error);
                    console.error('Response:', xhr.responseText);
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
    function showMessage(message, type, noScroll = false) {
        const $messageDiv = $('#form-message');
        $messageDiv
            .removeClass('success error')
            .addClass(type)
            .text(message)
            .fadeIn();

        setTimeout(function() {
            $messageDiv.fadeOut();
        }, 8000);

        // Scroll al top solo si no se deshabilita
        if (!noScroll) {
            $('html, body').animate({
                scrollTop: 0
            }, 400);
        }
    }

})(jQuery);
