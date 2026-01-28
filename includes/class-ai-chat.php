<?php
/**
 * Gestión de Chat con IA de Google Gemini
 *
 * @package Fullday_Users
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para chat con IA de Google Gemini
 */
class Fullday_AI_Chat {

    /**
     * Inicializar
     */
    public static function init() {
        // AJAX handlers
        add_action('wp_ajax_fullday_ai_send_message', array(__CLASS__, 'ajax_send_message'));
        add_action('wp_ajax_fullday_ai_create_fullday', array(__CLASS__, 'ajax_create_fullday'));

        // Agregar página de configuración en el admin
        add_action('admin_menu', array(__CLASS__, 'add_settings_page'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));

        // Enqueue scripts para el media uploader
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts'));
    }

    /**
     * Cargar scripts del admin para el media uploader
     */
    public static function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_fullday-ai-settings') {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script('fullday-ai-admin', FULLDAY_USERS_PLUGIN_URL . 'assets/js/ai-admin.js', array('jquery'), '1.0', true);
    }

    /**
     * Obtener mensajes por defecto para el indicador de escritura
     */
    private static function get_default_typing_messages() {
        return "Fully está pensando... 🤔
Fully está organizando las ideas... 💡
Fully está buscando las palabras perfectas... ✨
Fully está preparando algo genial... 🌟
Fully está armando la respuesta... 🎨
Fully está consultando su libreta... 📝
Fully está conectando los puntos... 🔗
Fully está poniendo todo bonito... 🎯
Fully está casi listo... ⏰
Fully está terminando los detalles... 🎁";
    }

    /**
     * Agregar página de configuración en el admin
     */
    public static function add_settings_page() {
        add_options_page(
            'Configuración IA Fullday',
            'IA Fullday',
            'manage_options',
            'fullday-ai-settings',
            array(__CLASS__, 'settings_page_html')
        );
    }

    /**
     * Registrar configuraciones
     */
    public static function register_settings() {
        register_setting('fullday_ai_settings', 'fullday_gemini_api_key');
        register_setting('fullday_ai_settings', 'fullday_ai_avatar');
        register_setting('fullday_ai_settings', 'fullday_ai_typing_messages');
    }

    /**
     * HTML de la página de configuración
     */
    public static function settings_page_html() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Guardar configuración si se envió el formulario
        if (isset($_POST['fullday_ai_settings_nonce']) &&
            wp_verify_nonce($_POST['fullday_ai_settings_nonce'], 'fullday_ai_settings_action')) {
            update_option('fullday_gemini_api_key', sanitize_text_field($_POST['fullday_gemini_api_key']));

            // Guardar avatar
            if (!empty($_POST['fullday_ai_avatar'])) {
                update_option('fullday_ai_avatar', intval($_POST['fullday_ai_avatar']));
            }

            // Guardar mensajes de escritura
            if (isset($_POST['fullday_ai_typing_messages'])) {
                update_option('fullday_ai_typing_messages', sanitize_textarea_field($_POST['fullday_ai_typing_messages']));
            }

            echo '<div class="notice notice-success"><p>Configuración guardada correctamente.</p></div>';
        }

        $api_key = get_option('fullday_gemini_api_key', '');
        $avatar_id = get_option('fullday_ai_avatar', '');
        $typing_messages = get_option('fullday_ai_typing_messages', self::get_default_typing_messages());
        ?>
        <div class="wrap">
            <h1>Configuración de IA para Full Days</h1>
            <p>Configura la API Key de Google Gemini para habilitar el asistente de IA en la creación de Full Days.</p>

            <form method="post" action="">
                <?php wp_nonce_field('fullday_ai_settings_action', 'fullday_ai_settings_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="fullday_gemini_api_key">API Key de Google Gemini</label>
                        </th>
                        <td>
                            <input type="text"
                                   id="fullday_gemini_api_key"
                                   name="fullday_gemini_api_key"
                                   value="<?php echo esc_attr($api_key); ?>"
                                   class="regular-text"
                                   placeholder="AIza...">
                            <p class="description">
                                Obtén tu API Key gratuita en <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="fullday_ai_avatar">Avatar de Fully</label>
                        </th>
                        <td>
                            <div class="fullday-avatar-upload">
                                <input type="hidden" id="fullday_ai_avatar" name="fullday_ai_avatar" value="<?php echo esc_attr($avatar_id); ?>">
                                <button type="button" class="button" id="fullday_upload_avatar_button">
                                    <?php if ($avatar_id): ?>
                                        Cambiar Avatar
                                    <?php else: ?>
                                        Subir Avatar de Fully
                                    <?php endif; ?>
                                </button>
                                <button type="button" class="button" id="fullday_remove_avatar_button" style="<?php echo $avatar_id ? '' : 'display:none;'; ?>">
                                    Eliminar
                                </button>
                                <div id="fullday_avatar_preview" style="margin-top: 10px;">
                                    <?php if ($avatar_id): ?>
                                        <img src="<?php echo wp_get_attachment_url($avatar_id); ?>" style="max-width: 150px; border-radius: 8px;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="description">
                                Sube la imagen de Fully (mascota de Fullday) que aparecerá en el chat con IA.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="fullday_ai_typing_messages">Mensajes de Escritura</label>
                        </th>
                        <td>
                            <textarea
                                id="fullday_ai_typing_messages"
                                name="fullday_ai_typing_messages"
                                rows="12"
                                class="large-text code"
                                placeholder="Un mensaje por línea..."><?php echo esc_textarea($typing_messages); ?></textarea>
                            <p class="description">
                                Mensajes que se mostrarán aleatoriamente cuando Fully esté "escribiendo". Un mensaje por línea. Sé creativo y jocoso! 😄
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Guardar Configuración'); ?>
            </form>

            <hr>
            <h2>Instrucciones</h2>
            <ol>
                <li>Ve a <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a></li>
                <li>Inicia sesión con tu cuenta de Google</li>
                <li>Crea una nueva API Key</li>
                <li>Copia la API Key y pégala en el campo de arriba</li>
                <li>Guarda la configuración</li>
            </ol>
            <p><strong>Nota:</strong> Google Gemini Flash 2.5 tiene un límite gratuito generoso. Revisa los límites actuales en la documentación de Google.</p>
        </div>
        <?php
    }

    /**
     * Obtener regiones disponibles como diccionario
     */
    private static function get_regions_dictionary() {
        $regions = array();

        // Obtener todos los términos de la taxonomía region
        $terms = get_terms(array(
            'taxonomy' => 'region',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));

        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $parent_name = '';
                if ($term->parent > 0) {
                    $parent = get_term($term->parent, 'region');
                    $parent_name = $parent ? $parent->name . ' > ' : '';
                }
                $regions[$term->term_id] = $parent_name . $term->name;
            }
        }

        return $regions;
    }

    /**
     * Obtener categorías disponibles
     */
    private static function get_categories() {
        return array(
            'Aventura',
            'Cultural',
            'Gastronómico',
            'Playa',
            'Montaña',
            'Urbano',
            'Ecoturismo',
            'Deportivo'
        );
    }

    /**
     * Generar el prompt del sistema con contexto
     */
    private static function get_system_prompt() {
        $regions = self::get_regions_dictionary();
        $categories = self::get_categories();

        $regions_list = "";
        foreach ($regions as $id => $name) {
            $regions_list .= "- ID: {$id} → {$name}\n";
        }

        $categories_list = implode(', ', $categories);

        $prompt = "Eres Fully, la mascota amigable y servicial de Fullday. Tu personalidad es alegre, positiva y siempre lista para ayudar. Tu ÚNICA función es ayudar a los proveedores turísticos a crear experiencias Full Day en Venezuela de forma rápida y sencilla.

CONTEXTO IMPORTANTE:
- Un Full Day puede durar desde varias horas hasta 2 días completos (con trayecto, disfrute y regreso)
- NO cuestiones la viabilidad, distancias o duración de ningún Full Day
- ACEPTA toda la información que te dé el proveedor sin debatir
- El proveedor conoce su negocio mejor que tú
- Tu rol es ASISTIR, NO juzgar ni corregir las decisiones del proveedor

REGIONES DISPONIBLES EN VENEZUELA:
{$regions_list}

CATEGORÍAS DISPONIBLES:
{$categories_list}

TU TRABAJO (en orden de prioridad):
1. ACEPTAR toda información que te dé el proveedor tal como la describe
2. Hacer preguntas SOLO para obtener datos faltantes (nunca para cuestionar)
3. Ayudar a redactar textos atractivos cuando el proveedor lo necesite
4. Sugerir opciones SOLO cuando el proveedor pida ayuda o no sepa qué poner
5. Ir preguntando de forma progresiva y amigable

ESTRATEGIA DE CONVERSACIÓN:
- Primero: tipo de experiencia, destino y desde dónde salen
- Segundo: qué incluye el tour
- Tercero: duración estimada (ACEPTAR lo que diga el proveedor)
- Cuarto: precio y capacidad máxima
- Quinto: itinerario con horarios
- Finalmente: redes sociales (opcional)

IMPORTANTE SOBRE PRECIOS:
- La plataforma maneja TODOS los precios en DÓLARES
- Si el proveedor da un precio SIN especificar moneda, asume que son DÓLARES
- Si el proveedor menciona bolívares, Bs, VES o similar:
  * Primero PÍDELE AMABLEMENTE que proporcione el precio en DÓLARES
  * Si insiste en bolívares, pregúntale la tasa del BCV actual
  * Con la tasa, convierte: PRECIO_DOLARES = BOLIVARES / TASA_BCV
  * Redondea a 2 decimales
- Ejemplo conversión: Si dice 3500 Bs y tasa BCV es 50, entonces: 3500 / 50 = 70.00 dólares
- SIEMPRE guarda el precio en el JSON como número decimal en dólares (sin símbolo)

REGLAS ABSOLUTAS:
- NUNCA digas que algo es inviable, poco realista o difícil de lograr
- NUNCA cuestiones distancias, tiempos de viaje o duraciones
- NUNCA sugieras cambiar el destino o la ruta
- Si el proveedor dice que algo se puede hacer, ACÉPTALO
- Si hay algo que no entiendes, pregunta para aclarar, NO para cuestionar
- Sé BREVE en tus respuestas, no escribas párrafos largos

IMPORTANTE: Cuando el usuario presione 'Guardar Full Day', generarás el JSON completando automáticamente cualquier información faltante.

INFORMACIÓN QUE NECESITAS RECOPILAR:
- Título del Full Day (atractivo y descriptivo)
- Descripción completa (mínimo 200 caracteres, máximo 1000)
- Destino (lugar específico donde se realiza)
- Fecha de salida (formato YYYY-MM-DD)
- Duración (ej: '8 horas', '1 día', '12 horas')
- Precio de venta (número decimal, ej: 99.00)
- Precio original (opcional, para mostrar descuento)
- Máximo de participantes (número entero)
- Edad mínima (opcional, número entero)
- Qué incluye (lista de items, cada uno en una línea)
- Itinerario (formato 'HH:MM, Descripción' por cada parada)
- IDs de regiones de salida (array de IDs de la lista de arriba)
- Categoría (una de las categorías listadas arriba)
- Instagram (opcional, URL completa)
- Facebook (opcional, URL completa)

FORMATO DEL JSON FINAL:
Cuando recibas la instrucción de generar el JSON (esto sucede automáticamente cuando el usuario presiona 'Guardar Full Day'), responde ÚNICAMENTE con un JSON en este formato exacto. El usuario NO verá este JSON, es solo para el sistema:

{
  \"title\": \"Título del Full Day\",
  \"description\": \"Descripción completa y atractiva del tour...\",
  \"destination\": \"Ciudad o lugar específico\",
  \"departure_date\": \"2024-12-31\",
  \"duration\": \"8 horas\",
  \"price\": 99.00,
  \"discount_price\": 150.00,
  \"max_people\": 12,
  \"min_age\": 18,
  \"includes\": \"Transporte ida y vuelta\\nGuía profesional certificado\\nAlmuerzo típico venezolano\\nEntradas a sitios turísticos\\nSeguro de viaje\",
  \"itinerary\": \"08:00, Salida desde punto de encuentro en Caracas\\n10:30, Llegada y desayuno en el destino\\n12:00, Inicio de actividades principales\\n14:00, Almuerzo con vista panorámica\\n16:30, Tiempo libre para fotos\\n18:00, Regreso a la ciudad\",
  \"region_ids\": [123, 456],
  \"category\": \"Aventura\",
  \"instagram\": \"https://instagram.com/tuusuario\",
  \"facebook\": \"https://facebook.com/tupagina\"
}

REGLAS IMPORTANTES:
- Sé amigable, profesional y útil
- Haz preguntas una a la vez para no abrumar al proveedor
- Ve recopilando información de forma progresiva y organizada
- Si el usuario está vago en algún detalle, ayúdalo sugiriendo opciones
- Menciona sutilmente qué información aún falta mientras conversas
- NO muestres el JSON al usuario durante la conversación
- Cuando recibas la orden de generar el JSON (el usuario presiona 'Guardar'):
  * COMPLETA AUTOMÁTICAMENTE cualquier campo faltante de forma coherente
  * Si no sabes algo, INVÉNTALO basándote en el contexto de la conversación
  * Todos los campos requeridos DEBEN estar presentes
  * Usa valores realistas y típicos para el tipo de tour descrito
  * Responde ÚNICAMENTE con el JSON válido, sin explicaciones ni texto adicional
- El itinerario debe tener al menos 3 paradas con horarios
- El campo 'includes' debe tener al menos 3 items
- Las fechas deben estar en formato YYYY-MM-DD
- Los IDs de región deben existir en la lista proporcionada
- La categoría debe ser una de las disponibles";

        return $prompt;
    }

    /**
     * AJAX: Enviar mensaje a la IA
     */
    public static function ajax_send_message() {
        check_ajax_referer('fullday_users_nonce', 'nonce');

        // Verificar que sea proveedor aprobado
        $user_id = get_current_user_id();
        if (!Fullday_Users_Roles::is_proveedor($user_id)) {
            wp_send_json_error(array('message' => 'No tienes permisos para usar esta función.'));
        }

        $proveedor_approved = get_user_meta($user_id, 'proveedor_approved', true);
        if ($proveedor_approved !== '1') {
            wp_send_json_error(array('message' => 'Tu cuenta de proveedor debe estar aprobada para usar esta función.'));
        }

        // Obtener API Key
        $api_key = get_option('fullday_gemini_api_key', '');
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'La API Key de Google Gemini no está configurada. Contacta al administrador.'));
        }

        // Obtener mensaje del usuario
        $user_message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        if (empty($user_message)) {
            wp_send_json_error(array('message' => 'El mensaje no puede estar vacío.'));
        }

        // Obtener nombre del proveedor
        $proveedor_nombre = isset($_POST['proveedor_nombre']) ? sanitize_text_field($_POST['proveedor_nombre']) : '';

        // Obtener historial de la sesión (si existe)
        $conversation_history = isset($_POST['history']) ? json_decode(stripslashes($_POST['history']), true) : array();

        // Llamar a la API de Google Gemini
        $response = self::call_gemini_api($api_key, $user_message, $conversation_history, $proveedor_nombre);

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => $response
        ));
    }

    /**
     * AJAX: Generar JSON y crear Full Day
     */
    public static function ajax_create_fullday() {
        check_ajax_referer('fullday_users_nonce', 'nonce');

        // Verificar que sea proveedor aprobado
        $user_id = get_current_user_id();
        if (!Fullday_Users_Roles::is_proveedor($user_id)) {
            wp_send_json_error(array('message' => 'No tienes permisos para usar esta función.'));
        }

        $proveedor_approved = get_user_meta($user_id, 'proveedor_approved', true);
        if ($proveedor_approved !== '1') {
            wp_send_json_error(array('message' => 'Tu cuenta de proveedor debe estar aprobada para usar esta función.'));
        }

        // Obtener API Key
        $api_key = get_option('fullday_gemini_api_key', '');
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'La API Key de Google Gemini no está configurada. Contacta al administrador.'));
        }

        // Obtener historial de conversación
        $conversation_history = isset($_POST['history']) ? json_decode(stripslashes($_POST['history']), true) : array();

        // Enviar prompt final para generar JSON - FORZAR A COMPLETAR TODA LA INFORMACIÓN
        $final_prompt = "IMPORTANTE: El usuario ha decidido guardar el Full Day. Debes generar AHORA el JSON completo con TODA la información.

INSTRUCCIONES CRÍTICAS:
1. Si falta algún campo requerido que no discutimos, INVÉNTALO de forma coherente y realista
2. Todos los campos requeridos DEBEN estar presentes en el JSON
3. Si no tengo fecha específica, usa una fecha futura razonable
4. Si no tengo precio exacto, sugiere un precio típico para este tipo de tour
5. Si falta algún detalle en el itinerario o qué incluye, complétalo con opciones estándar y lógicas
6. Si no mencioné regiones específicas, usa las regiones de Venezuela que sean más lógicas según el destino
7. Si falta categoría, asígnala según el tipo de experiencia que describimos

Genera el JSON COMPLETO ahora. Responde ÚNICAMENTE con el JSON válido, sin texto adicional antes o después. No expliques nada, solo el JSON.";

        $json_response = self::call_gemini_api($api_key, $final_prompt, $conversation_history);

        if (is_wp_error($json_response)) {
            wp_send_json_error(array('message' => $json_response->get_error_message()));
        }

        // LOG: Guardar respuesta completa de la IA
        error_log('=== AI CHAT - RESPUESTA COMPLETA DE LA IA ===');
        error_log($json_response);
        error_log('=== FIN RESPUESTA IA ===');

        // Extraer JSON de la respuesta usando función especializada
        $json_extracted = self::extract_json_from_response($json_response);

        if ($json_extracted === false) {
            error_log('AI Chat - No se pudo extraer JSON de la respuesta');
            wp_send_json_error(array(
                'message' => 'La IA no pudo generar el formato correcto. Por favor, intenta nuevamente o proporciona más detalles en la conversación.'
            ));
        }

        // LOG: JSON extraído
        error_log('=== AI CHAT - JSON EXTRAÍDO ===');
        error_log($json_extracted);
        error_log('=== FIN JSON EXTRAÍDO ===');

        // Decodificar JSON
        $fullday_data = json_decode($json_extracted, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Log del error para debugging
            error_log('AI Chat - Error al decodificar JSON: ' . json_last_error_msg());
            error_log('AI Chat - JSON que intentó decodificar: ' . $json_extracted);

            wp_send_json_error(array(
                'message' => 'La IA no pudo generar el formato correcto. Por favor, intenta nuevamente o proporciona más detalles en la conversación.'
            ));
        }

        // LOG: Datos finales parseados
        error_log('=== AI CHAT - DATOS PARSEADOS ===');
        error_log(print_r($fullday_data, true));
        error_log('=== FIN DATOS PARSEADOS ===');

        // Crear el Full Day
        $result = self::create_fullday_from_json($fullday_data, $user_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => 'Full Day creado exitosamente como borrador.',
            'post_id' => $result,
            'redirect' => home_url('/dashboard?tab=mis-viajes')
        ));
    }

    /**
     * Extraer JSON de la respuesta de la IA
     * Busca y extrae el JSON incluso si viene rodeado de texto
     */
    private static function extract_json_from_response($response) {
        // Limpiar la respuesta
        $response = trim($response);

        // Método 1: Buscar JSON entre bloques de código markdown
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $response, $matches)) {
            error_log('AI Chat - JSON encontrado en bloque markdown');
            return trim($matches[1]);
        }

        // Método 2: Buscar JSON entre bloques de código sin especificar lenguaje
        if (preg_match('/```\s*(\{.*?\})\s*```/s', $response, $matches)) {
            error_log('AI Chat - JSON encontrado en bloque de código genérico');
            return trim($matches[1]);
        }

        // Método 3: Buscar el primer objeto JSON válido en el texto
        // Buscar desde el primer { hasta el último } balanceado
        $start_pos = strpos($response, '{');
        if ($start_pos !== false) {
            $brace_count = 0;
            $in_string = false;
            $escape_next = false;
            $json_str = '';

            for ($i = $start_pos; $i < strlen($response); $i++) {
                $char = $response[$i];
                $json_str .= $char;

                // Manejar strings para no contar llaves dentro de ellas
                if ($char === '"' && !$escape_next) {
                    $in_string = !$in_string;
                }

                if ($char === '\\') {
                    $escape_next = !$escape_next;
                } else {
                    $escape_next = false;
                }

                // Contar llaves solo fuera de strings
                if (!$in_string) {
                    if ($char === '{') {
                        $brace_count++;
                    } elseif ($char === '}') {
                        $brace_count--;

                        // Si llegamos a 0, encontramos el JSON completo
                        if ($brace_count === 0) {
                            error_log('AI Chat - JSON encontrado en el texto (sin marcadores)');
                            return trim($json_str);
                        }
                    }
                }
            }
        }

        // Método 4: Si todo lo demás falla, intentar limpiar la respuesta completa
        // Eliminar texto antes del primer { y después del último }
        $first_brace = strpos($response, '{');
        $last_brace = strrpos($response, '}');

        if ($first_brace !== false && $last_brace !== false && $last_brace > $first_brace) {
            $potential_json = substr($response, $first_brace, $last_brace - $first_brace + 1);
            error_log('AI Chat - Intentando extraer JSON simple (primer { al último })');
            return trim($potential_json);
        }

        // No se pudo extraer JSON
        error_log('AI Chat - No se pudo extraer ningún JSON válido');
        return false;
    }

    /**
     * Llamar a la API de Google Gemini
     */
    private static function call_gemini_api($api_key, $user_message, $conversation_history = array(), $proveedor_nombre = '') {
        // Construir el contexto completo
        $system_prompt = self::get_system_prompt();

        // Si es el primer mensaje y hay nombre de proveedor, agregarlo al prompt
        if (empty($conversation_history) && !empty($proveedor_nombre)) {
            $system_prompt .= "\n\nINFORMACIÓN DEL PROVEEDOR:\nEstás ayudando a: {$proveedor_nombre}\nDiríjete al proveedor por este nombre cuando sea apropiado en la conversación.";
        }

        // Construir el contenido completo
        $full_content = $system_prompt . "\n\n";

        // Agregar historial de conversación
        if (!empty($conversation_history)) {
            foreach ($conversation_history as $msg) {
                $role = $msg['role'] === 'user' ? 'Usuario' : 'Asistente';
                $full_content .= "{$role}: {$msg['content']}\n\n";
            }
        }

        // Agregar mensaje actual
        $full_content .= "Usuario: {$user_message}\n\nAsistente:";

        // URL de la API de Google Gemini (Flash 2.5)
        $api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$api_key}";

        // Preparar el body de la petición
        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => $full_content
                        )
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.7,
                'maxOutputTokens' => 2048,
            )
        );

        // Realizar la petición
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return new WP_Error('api_error', 'Error al conectar con Google Gemini: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($response_code !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Error desconocido';
            return new WP_Error('api_error', 'Error de la API: ' . $error_message);
        }

        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }

        return new WP_Error('api_error', 'Respuesta inesperada de la API');
    }

    /**
     * Crear Full Day desde JSON generado por la IA
     */
    private static function create_fullday_from_json($data, $user_id) {
        // Validar datos requeridos
        $required_fields = array('title', 'description', 'destination', 'departure_date', 'duration', 'price', 'max_people', 'includes', 'itinerary', 'region_ids', 'category');

        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', "Falta el campo requerido: {$field}");
            }
        }

        // Crear el post
        $post_data = array(
            'post_title' => sanitize_text_field($data['title']),
            'post_content' => '',
            'post_status' => 'draft',
            'post_type' => 'full-days',
            'post_author' => $user_id
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Guardar metadatos
        update_post_meta($post_id, 'full_days_price', floatval($data['price']));

        if (!empty($data['discount_price'])) {
            $discount_price = floatval($data['discount_price']);
            update_post_meta($post_id, 'full_days_discount_price', $discount_price);

            // Calcular porcentaje de descuento
            $price = floatval($data['price']);
            $discount_percentage = round((($discount_price - $price) / $discount_price) * 100);
            update_post_meta($post_id, 'full_days_discount_percentage', $discount_percentage);
        }

        update_post_meta($post_id, 'full_days_description', sanitize_textarea_field($data['description']));
        update_post_meta($post_id, 'full_days_destination', sanitize_text_field($data['destination']));
        update_post_meta($post_id, 'full_days_departure_date', sanitize_text_field($data['departure_date']));
        update_post_meta($post_id, 'full_days_duration', sanitize_text_field($data['duration']));
        update_post_meta($post_id, 'full_days_max_people', intval($data['max_people']));
        update_post_meta($post_id, 'full_days_available_spots', intval($data['max_people']));

        if (!empty($data['min_age'])) {
            update_post_meta($post_id, 'full_days_min_age', intval($data['min_age']));
        }

        update_post_meta($post_id, 'full_days_includes', sanitize_textarea_field($data['includes']));
        update_post_meta($post_id, 'full_days_itinerary', sanitize_textarea_field($data['itinerary']));

        if (!empty($data['instagram'])) {
            update_post_meta($post_id, 'full_days_instagram', esc_url_raw($data['instagram']));
        }

        if (!empty($data['facebook'])) {
            update_post_meta($post_id, 'full_days_facebook', esc_url_raw($data['facebook']));
        }

        // Asignar regiones
        $region_ids = array_map('intval', $data['region_ids']);
        wp_set_object_terms($post_id, $region_ids, 'region');

        // Crear region_order
        $region_order = array();
        foreach ($region_ids as $index => $region_id) {
            $region_order[$region_id] = $index + 1;
        }
        update_post_meta($post_id, 'region_order', $region_order);

        // Asignar categoría
        wp_set_object_terms($post_id, sanitize_text_field($data['category']), 'full_days_category');

        // Inicializar galería vacía (el proveedor deberá agregar imágenes manualmente después)
        update_post_meta($post_id, 'full_days_gallery', array());

        return $post_id;
    }
}
