# Sistema de Chat con IA y Generación Automática de Imágenes

## Resumen
Sistema completo para que proveedores creen Full Days conversando con "Fully" (mascota IA basada en Gemini). El sistema incluye:
- Chat conversacional con análisis de imágenes adjuntas
- Generación automática de 3 imágenes con Gemini 2.5 Flash Image
- Guardado automático: 1 featured image + 2 gallery images

## Archivos Modificados

### 1. templates/dashboard/proveedor/crear-ia.php
- Agregado botón para adjuntar imágenes (📷)
- Input file para seleccionar imágenes (máx 5 por mensaje)
- Preview de imágenes antes de enviar
- Sistema de chat interactivo con Fully

### 2. assets/js/dashboard-ai-chat.js
- Variables: `conversationHistory`, `isProcessing`, `attachedImages`
- Funciones de manejo de imágenes: `openImageSelector()`, `handleImageSelection()`, `displayImagePreview()`, `removeImage()`
- Función `sendMessage()`: envía texto + imágenes a backend
- Función `saveFullDay()`: solicita creación del Full Day con todas las imágenes de la conversación
- Validación: máx 5 imágenes, máx 5MB cada una, solo JPG/PNG

### 3. assets/css/dashboard-ai-chat.css
- Estilos para `.input-attachments`, `.image-preview`, `.btn-attach`
- Estilos para `.message-images` y `.message-image` en el chat
- Preview de imágenes con botón eliminar (X rojo)
- Diseño responsive para móviles

### 4. includes/class-ai-chat.php (PRINCIPAL)

#### Configuración (líneas 50-175)
- Setting: `fullday_gemini_api_key` (única API key necesaria)
- Campo para avatar de Fully
- Campo para mensajes de "escribiendo" personalizados
- Instrucciones para obtener API Key de Google

#### AJAX Handlers
- `ajax_send_message()`: Procesa mensajes del chat + imágenes adjuntas
- `ajax_create_fullday()`: Genera JSON y crea Full Day con imágenes automáticas

#### System Prompt Mejorado (líneas 200-450)
**Contexto agregado:**
- Lista de regiones de Venezuela con IDs
- Lista de categorías disponibles
- Instrucciones para manejar precios en bolívares/dólares
- **NUEVO:** Instrucciones para generar `image_prompts` array con 3 descripciones

**Sección ANÁLISIS DE IMÁGENES:**
- Extraer precios, destinos, fechas, horarios
- OCR de texto visible en imágenes
- Convertir bolívares a dólares
- Traducir texto si está en otro idioma

**Campo image_prompts requerido en JSON:**
```json
"image_prompts": [
  "Descripción detallada en INGLÉS de imagen 1 (Featured)...",
  "Descripción detallada en INGLÉS de imagen 2 (Gallery)...",
  "Descripción detallada en INGLÉS de imagen 3 (Gallery)..."
]
```

#### Función `generate_images_with_gemini()` (líneas ~480-570)
```php
- Modelo: gemini-2.5-flash-image
- Endpoint: /v1beta/models/gemini-2.5-flash-image:generateContent
- Input: Array de 3 prompts en inglés
- Output: Array de 3 imágenes en base64 con mime_type
- Formato respuesta: inline_data { data: base64, mime_type: string }
- Timeout: 60 segundos por imagen
- Error handling: continúa si falla, permite Full Day sin imágenes
```

#### Función `create_fullday_from_json()` (líneas ~690-850)
**Validación:**
- Todos los campos requeridos incluyendo `image_prompts`
- Mínimo 3 prompts en el array

**Proceso de generación de imágenes:**
1. Llama a `generate_images_with_gemini()` con los 3 prompts
2. Recibe array de imágenes en base64
3. Decodifica cada base64
4. Guarda archivos en `/wp-content/uploads/`
5. Crea attachments de WordPress
6. Genera metadata (thumbnails, dimensiones)
7. **Imagen índice 0:** `set_post_thumbnail()` → Featured Image
8. **Imágenes índice 1-2:** `full_days_gallery` meta → Gallery

**Logs detallados:**
- Cada paso del proceso
- URLs generadas
- IDs de attachments
- Errores específicos

## Configuración Requerida

### API Key de Google Gemini
1. Ir a: https://aistudio.google.com/app/apikey
2. Crear nueva API Key
3. Pegarla en Settings > IA Fullday
4. **Esta misma key sirve para:**
   - Conversación (gemini-2.5-flash)
   - Análisis de imágenes (multimodal)
   - Generación de imágenes (gemini-2.5-flash-image)

## Flujo Completo de Uso

### 1. Proveedor inicia conversación
- Navega a "Crear con IA"
- Ve mensaje de bienvenida de Fully

### 2. Conversación iterativa
- Proveedor describe el tour
- Puede adjuntar imágenes de referencia (folletos, fotos del destino)
- Fully analiza imágenes y extrae información (precios, lugares, texto)
- Fully hace preguntas para completar información

### 3. Usuario presiona "Guardar Full Day"
- Frontend envía `conversationHistory` completo
- Backend solicita a Fully generar JSON final

### 4. Fully genera JSON con image_prompts
```json
{
  "title": "Tour a Los Roques",
  "description": "...",
  "price": 150.00,
  "image_prompts": [
    "Breathtaking wide-angle photo of turquoise waters at Los Roques...",
    "Vibrant underwater photo with tourists snorkeling...",
    "Delicious Venezuelan seafood lunch at beach restaurant..."
  ],
  ...
}
```

### 5. Sistema genera 3 imágenes
- Llama 3 veces a Gemini Image API
- Recibe 3 imágenes en base64
- Las guarda en WordPress

### 6. Full Day creado
- Estado: draft
- Featured image: imagen 1
- Gallery: imágenes 2 y 3
- Proveedor puede editarlo y publicar

## Características Técnicas

### Imágenes Adjuntadas por Usuario
- **Propósito:** Para que IA analice y extraiga información
- **Proceso:** Se envían a Gemini como `inline_data` en el mensaje
- **Análisis:** OCR, detección de precios, lugares, horarios
- **NO se guardan:** Solo se usan para análisis, no van al Full Day final

### Imágenes Generadas Automáticamente
- **Propósito:** Ilustrar el Full Day con imágenes realistas
- **Cantidad:** Exactamente 3
- **Calidad:** 1024x1024, formato PNG/JPG/WebP
- **Marca de agua:** SynthID invisible (identificación IA)
- **Distribución:**
  - Imagen 1: Featured (portada del Full Day)
  - Imagen 2: Gallery carousel
  - Imagen 3: Gallery carousel

### Prompts para Imágenes
**Características de buen prompt:**
- Mínimo 50 palabras
- En INGLÉS (mejores resultados)
- Muy descriptivo: paisaje, colores, iluminación, personas, actividades
- Estilo fotográfico: "professional travel photography", "golden hour"
- Detalles: "crystal clear water", "vibrant colors", "ultra detailed"

**Ejemplo completo:**
```
A breathtaking wide-angle photograph of crystal clear turquoise 
waters at Los Roques, Venezuela, with pristine white sand beaches 
in the foreground, small wooden boats floating peacefully, palm 
trees swaying gently, bright blue sky with few white clouds, golden 
hour lighting, vibrant tropical colors, professional travel 
photography style, high resolution, ultra detailed
```

## Estructura de Datos

### Post Meta del Full Day
```php
// Featured Image
set_post_thumbnail($post_id, $attach_id);

// Gallery
update_post_meta($post_id, 'full_days_gallery', [
  'https://ejemplo.com/wp-content/uploads/image1.png',
  'https://ejemplo.com/wp-content/uploads/image2.png'
]);

// Otros campos estándar
update_post_meta($post_id, 'full_days_price', 150.00);
update_post_meta($post_id, 'full_days_description', '...');
// etc.
```

## Costos y Límites

### Google Gemini (2026)
- **Gemini 2.5 Flash (texto):** GRATIS con cuotas generosas
- **Gemini 2.5 Flash Image:** GRATIS con cuotas
- **Multimodal (análisis imágenes):** GRATIS con cuotas

### Cuotas Aproximadas (verificar en Google AI Studio)
- Texto: ~60 requests/minuto
- Imágenes: Variable según uso

### Costo por Full Day
- **$0.00 USD** dentro de cuotas gratuitas
- Costo marginal muy bajo fuera de cuotas

## Manejo de Errores

### Si falla generación de imágenes
- Log detallado del error
- Full Day se crea SIN imágenes
- Proveedor puede agregar imágenes manualmente después
- No bloquea la creación del Full Day

### Si API Key no configurada
- Muestra mensaje al proveedor
- No permite usar función de IA
- Link para que admin configure

## Testing y Debugging

### Logs importantes (ver error_log de WordPress)
```
=== GENERACIÓN DE IMÁGENES CON GEMINI 2.5 FLASH IMAGE ===
Generando imagen 1/3
Prompt: Breathtaking wide-angle photo...
Imagen generada exitosamente (base64)
Attachment ID creado: 1234
Imagen establecida como FEATURED IMAGE
...
```

### Archivos a revisar en caso de problemas
1. `wp-content/debug.log` (logs de PHP)
2. Consola del navegador (logs de JavaScript)
3. Network tab (ver peticiones AJAX)

## Próximas Mejoras Potenciales
- [ ] Permitir regenerar imágenes individuales
- [ ] Opción de estilo de imagen (realista, artístico, etc.)
- [ ] Preview de imágenes antes de guardar Full Day
- [ ] Caché de imágenes generadas
- [ ] Galería de prompts predefinidos
