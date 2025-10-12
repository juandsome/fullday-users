<?php
/**
 * Gestión de Estados y Ciudades de Venezuela
 *
 * @package Fullday_Users
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestión de ubicaciones
 */
class Fullday_Users_Locations {

    /**
     * Inicializar
     */
    public static function init() {
        // AJAX para obtener ciudades por estado
        add_action('wp_ajax_fullday_get_cities', array(__CLASS__, 'ajax_get_cities'));
        add_action('wp_ajax_nopriv_fullday_get_cities', array(__CLASS__, 'ajax_get_cities'));
    }

    /**
     * Obtener lista de estados de Venezuela
     */
    public static function get_estados() {
        return array(
            'amazonas' => 'Amazonas',
            'anzoategui' => 'Anzoátegui',
            'apure' => 'Apure',
            'aragua' => 'Aragua',
            'barinas' => 'Barinas',
            'bolivar' => 'Bolívar',
            'carabobo' => 'Carabobo',
            'cojedes' => 'Cojedes',
            'delta_amacuro' => 'Delta Amacuro',
            'distrito_capital' => 'Distrito Capital',
            'falcon' => 'Falcón',
            'guarico' => 'Guárico',
            'lara' => 'Lara',
            'merida' => 'Mérida',
            'miranda' => 'Miranda',
            'monagas' => 'Monagas',
            'nueva_esparta' => 'Nueva Esparta',
            'portuguesa' => 'Portuguesa',
            'sucre' => 'Sucre',
            'tachira' => 'Táchira',
            'trujillo' => 'Trujillo',
            'vargas' => 'Vargas',
            'yaracuy' => 'Yaracuy',
            'zulia' => 'Zulia',
        );
    }

    /**
     * Obtener ciudades por estado
     */
    public static function get_ciudades($estado = '') {
        $ciudades = array(
            'amazonas' => array('Puerto Ayacucho', 'San Fernando de Atabapo', 'Maroa', 'San Carlos de Río Negro'),
            'anzoategui' => array('Barcelona', 'Puerto La Cruz', 'Lechería', 'El Tigre', 'Anaco', 'Cantaura', 'Clarines', 'Píritu', 'San Tomé'),
            'apure' => array('San Fernando de Apure', 'Biruaca', 'Achaguas', 'Guasdualito', 'Elorza', 'Mantecal'),
            'aragua' => array('Maracay', 'Turmero', 'La Victoria', 'Cagua', 'Villa de Cura', 'El Consejo', 'Santa Rita', 'Palo Negro', 'San Mateo'),
            'barinas' => array('Barinas', 'Barinitas', 'Santa Bárbara', 'Socopó', 'Sabaneta', 'Ciudad Bolivia'),
            'bolivar' => array('Ciudad Bolívar', 'Puerto Ordaz', 'San Félix', 'Upata', 'Ciudad Guayana', 'Caicara del Orinoco', 'Tumeremo', 'El Callao'),
            'carabobo' => array('Valencia', 'Puerto Cabello', 'Guacara', 'Naguanagua', 'San Diego', 'Los Guayos', 'Tocuyito', 'Mariara', 'Morón', 'Bejuma'),
            'cojedes' => array('San Carlos', 'Tinaquillo', 'El Baúl', 'Las Vegas', 'Libertad'),
            'delta_amacuro' => array('Tucupita', 'Pedernales', 'Curiapo', 'San José de Buja'),
            'distrito_capital' => array('Caracas', 'El Hatillo', 'Baruta', 'Chacao', 'Sucre', 'Libertador'),
            'falcon' => array('Coro', 'Punto Fijo', 'Tucacas', 'Chichiriviche', 'Dabajuro', 'Judibana', 'Churuguara', 'Cabure', 'Santa Ana de Coro'),
            'guarico' => array('San Juan de los Morros', 'Calabozo', 'Valle de la Pascua', 'Zaraza', 'Altagracia de Orituco', 'Las Mercedes', 'Santa María de Ipire'),
            'lara' => array('Barquisimeto', 'Carora', 'Cabudare', 'El Tocuyo', 'Quíbor', 'Duaca', 'Sanare', 'Sarare'),
            'merida' => array('Mérida', 'Ejido', 'El Vigía', 'Tovar', 'Santa Cruz de Mora', 'Timotes', 'Mucuchíes', 'Bailadores', 'Lagunillas'),
            'miranda' => array('Los Teques', 'Guarenas', 'Guatire', 'Charallave', 'Ocumare del Tuy', 'Cúa', 'Santa Teresa', 'San Antonio de los Altos', 'Caucagua'),
            'monagas' => array('Maturín', 'Caripe', 'Punta de Mata', 'Temblador', 'Caripito', 'Aguasay', 'San Antonio de Capayacuar'),
            'nueva_esparta' => array('La Asunción', 'Porlamar', 'Pampatar', 'Juan Griego', 'El Valle del Espíritu Santo', 'Boca de Río', 'San Pedro de Coche'),
            'portuguesa' => array('Guanare', 'Acarigua', 'Araure', 'Biscucuy', 'Ospino', 'Turén', 'Villa Bruzual', 'Boconoíto'),
            'sucre' => array('Cumaná', 'Carúpano', 'Güiria', 'Araya', 'Casanay', 'Tunapuy', 'Yaguaraparo'),
            'tachira' => array('San Cristóbal', 'Táriba', 'San Antonio del Táchira', 'Rubio', 'Capacho', 'La Grita', 'Colón', 'San Juan de Colón', 'Palmira'),
            'trujillo' => array('Trujillo', 'Valera', 'Boconó', 'Carache', 'Escuque', 'Sabana Grande', 'Betijoque', 'Santa Ana'),
            'vargas' => array('La Guaira', 'Catia La Mar', 'Maiquetía', 'Macuto', 'Naiguatá', 'Caraballeda', 'Carayaca'),
            'yaracuy' => array('San Felipe', 'Yaritagua', 'Nirgua', 'Chivacoa', 'Aroa', 'Cocorote', 'Urachiche'),
            'zulia' => array('Maracaibo', 'Cabimas', 'Ciudad Ojeda', 'Machiques', 'San Carlos del Zulia', 'Santa Bárbara del Zulia', 'La Villa del Rosario', 'Mene Grande', 'San Francisco'),
        );

        if (empty($estado)) {
            return $ciudades;
        }

        return isset($ciudades[$estado]) ? $ciudades[$estado] : array();
    }

    /**
     * AJAX para obtener ciudades
     */
    public static function ajax_get_cities() {
        $estado = isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : '';

        if (empty($estado)) {
            wp_send_json_error(array('message' => 'Estado no especificado.'));
        }

        $ciudades = self::get_ciudades($estado);

        wp_send_json_success(array('ciudades' => $ciudades));
    }
}
