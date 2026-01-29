<?php
/**
 * Gestión del Admin de WordPress
 *
 * @package Fullday_Users
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestión del admin
 */
class Fullday_Users_Admin {

    /**
     * Inicializar
     */
    public static function init() {
        // Agregar columnas personalizadas en la lista de usuarios
        add_filter('manage_users_columns', array(__CLASS__, 'add_user_columns'));
        add_filter('manage_users_custom_column', array(__CLASS__, 'show_user_column_content'), 10, 3);

        // Agregar campos personalizados en el perfil de usuario
        add_action('show_user_profile', array(__CLASS__, 'show_extra_profile_fields'));
        add_action('edit_user_profile', array(__CLASS__, 'show_extra_profile_fields'));

        // Guardar campos personalizados
        add_action('personal_options_update', array(__CLASS__, 'save_extra_profile_fields'));
        add_action('edit_user_profile_update', array(__CLASS__, 'save_extra_profile_fields'));

        // AJAX para aprobar/desaprobar proveedor
        add_action('wp_ajax_fullday_toggle_approval', array(__CLASS__, 'ajax_toggle_approval'));

        // Agregar estilos personalizados en el admin
        add_action('admin_head', array(__CLASS__, 'add_admin_styles'));

        // Agregar página de configuración
        add_action('admin_menu', array(__CLASS__, 'add_settings_page'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
    }

    /**
     * Agregar columnas personalizadas
     */
    public static function add_user_columns($columns) {
        $columns['user_role_type'] = 'Tipo de Usuario';
        $columns['user_estado'] = 'Estado';
        $columns['user_ciudad'] = 'Ciudad';
        $columns['proveedor_approval'] = 'Estado Proveedor';
        return $columns;
    }

    /**
     * Mostrar contenido de columnas personalizadas
     */
    public static function show_user_column_content($value, $column_name, $user_id) {
        $user = get_userdata($user_id);

        switch ($column_name) {
            case 'user_role_type':
                if (Fullday_Users_Roles::is_cliente($user_id)) {
                    return '<span style="color: #2563eb; font-weight: 600;">Cliente</span>';
                } elseif (Fullday_Users_Roles::is_proveedor($user_id)) {
                    return '<span style="color: #dc2626; font-weight: 600;">Proveedor</span>';
                }
                return '-';

            case 'user_estado':
                $estado = get_user_meta($user_id, 'estado', true);
                return $estado ? esc_html(ucfirst($estado)) : '-';

            case 'user_ciudad':
                $ciudad = get_user_meta($user_id, 'ciudad', true);
                return $ciudad ? esc_html($ciudad) : '-';

            case 'proveedor_approval':
                if (!Fullday_Users_Roles::is_proveedor($user_id)) {
                    return '-';
                }

                $approved = get_user_meta($user_id, 'proveedor_approved', true);
                $is_approved = ($approved === '1' || $approved === 1);

                $button_text = $is_approved ? 'Aprobado ✓' : 'Pendiente';
                $button_class = $is_approved ? 'button-primary' : 'button-secondary';
                $button_style = $is_approved ? 'background: #10b981; border-color: #10b981;' : 'background: #ef4444; border-color: #ef4444; color: white;';

                return sprintf(
                    '<button class="fullday-toggle-approval %s" data-user-id="%d" data-approved="%d" style="%s">%s</button>',
                    esc_attr($button_class),
                    $user_id,
                    $is_approved ? 1 : 0,
                    $button_style,
                    $button_text
                );
        }

        return $value;
    }

    /**
     * Mostrar campos extras en el perfil
     */
    public static function show_extra_profile_fields($user) {
        $user_type = Fullday_Users_Roles::get_user_type($user->ID);
        $whatsapp = get_user_meta($user->ID, 'whatsapp', true);
        $estado = get_user_meta($user->ID, 'estado', true);
        $ciudad = get_user_meta($user->ID, 'ciudad', true);
        $documento_id = get_user_meta($user->ID, 'documento_id', true);
        $proveedor_approved = get_user_meta($user->ID, 'proveedor_approved', true);

        // Separar prefijo y número de WhatsApp
        $whatsapp_prefix = '';
        $whatsapp_number = '';
        if ($whatsapp && strlen($whatsapp) >= 11) {
            $whatsapp_prefix = substr($whatsapp, 0, 4);
            $whatsapp_number = substr($whatsapp, 4);
        }

        ?>
        <h2>Información Adicional de Fullday</h2>
        <table class="form-table">
            <tr>
                <th><label for="user_type_display">Tipo de Usuario</label></th>
                <td>
                    <?php if ($user_type === 'cliente'): ?>
                        <strong style="color: #2563eb;">Cliente</strong>
                    <?php elseif ($user_type === 'proveedor'): ?>
                        <strong style="color: #dc2626;">Proveedor</strong>
                    <?php else: ?>
                        <em>Usuario regular de WordPress</em>
                    <?php endif; ?>
                </td>
            </tr>

            <tr>
                <th><label for="whatsapp">WhatsApp</label></th>
                <td>
                    <div style="display: flex; gap: 10px;">
                        <select name="whatsapp_prefix" id="whatsapp_prefix" style="width: 100px;">
                            <option value="0412" <?php selected($whatsapp_prefix, '0412'); ?>>0412</option>
                            <option value="0416" <?php selected($whatsapp_prefix, '0416'); ?>>0416</option>
                            <option value="0426" <?php selected($whatsapp_prefix, '0426'); ?>>0426</option>
                            <option value="0424" <?php selected($whatsapp_prefix, '0424'); ?>>0424</option>
                            <option value="0414" <?php selected($whatsapp_prefix, '0414'); ?>>0414</option>
                        </select>
                        <input type="text" name="whatsapp_number" id="whatsapp_number" value="<?php echo esc_attr($whatsapp_number); ?>" maxlength="7" pattern="[0-9]{7}" style="flex: 1; max-width: 150px;" />
                    </div>
                    <p class="description">Número de WhatsApp del usuario (prefijo + 7 dígitos)</p>
                </td>
            </tr>

            <tr>
                <th><label for="estado">Estado</label></th>
                <td>
                    <input type="text" name="estado" id="estado" value="<?php echo esc_attr($estado); ?>" class="regular-text" />
                    <p class="description">Estado de residencia</p>
                </td>
            </tr>

            <tr>
                <th><label for="ciudad">Ciudad</label></th>
                <td>
                    <input type="text" name="ciudad" id="ciudad" value="<?php echo esc_attr($ciudad); ?>" class="regular-text" />
                    <p class="description">Ciudad de residencia</p>
                </td>
            </tr>

            <?php if ($user_type === 'cliente'): ?>
                <?php
                $fecha_nacimiento = get_user_meta($user->ID, 'fecha_nacimiento', true);
                ?>
                <tr>
                    <th><label for="fecha_nacimiento">Fecha de Nacimiento</label></th>
                    <td>
                        <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" value="<?php echo esc_attr($fecha_nacimiento); ?>" class="regular-text" />
                    </td>
                </tr>
            <?php endif; ?>

            <?php if ($user_type === 'proveedor'): ?>
                <?php
                $empresa = get_user_meta($user->ID, 'empresa', true);
                $descripcion = get_user_meta($user->ID, 'descripcion', true);
                ?>
                <tr>
                    <th><label for="empresa">Empresa</label></th>
                    <td>
                        <input type="text" name="empresa" id="empresa" value="<?php echo esc_attr($empresa); ?>" class="regular-text" />
                        <p class="description">Nombre de la empresa del proveedor</p>
                    </td>
                </tr>

                <tr>
                    <th><label for="descripcion">Descripción</label></th>
                    <td>
                        <textarea name="descripcion" id="descripcion" rows="5" class="large-text"><?php echo esc_textarea($descripcion); ?></textarea>
                        <p class="description">Descripción del proveedor y sus servicios</p>
                    </td>
                </tr>

                <tr>
                    <th><label for="documento_id">Documento de Identidad</label></th>
                    <td>
                        <?php if ($documento_id): ?>
                            <?php $doc_url = wp_get_attachment_url($documento_id); ?>
                            <a href="<?php echo esc_url($doc_url); ?>" target="_blank" class="button">Ver Documento</a>
                            <p class="description">ID del attachment: <?php echo esc_html($documento_id); ?></p>
                        <?php else: ?>
                            <em>No se ha subido ningún documento</em>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th><label for="proveedor_approved">Estado de Aprobación</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="proveedor_approved" id="proveedor_approved" value="1" <?php checked($proveedor_approved, '1'); ?> />
                            Proveedor Aprobado
                        </label>
                        <p class="description">Marca esta casilla para aprobar al proveedor y permitirle crear full-days</p>
                    </td>
                </tr>
            <?php endif; ?>
        </table>
        <?php
    }

    /**
     * Guardar campos extras del perfil
     */
    public static function save_extra_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        // Campos comunes
        if (isset($_POST['whatsapp_prefix']) && isset($_POST['whatsapp_number'])) {
            $whatsapp_prefix = sanitize_text_field($_POST['whatsapp_prefix']);
            $whatsapp_number = sanitize_text_field($_POST['whatsapp_number']);

            // Solo guardar si ambos campos tienen valor
            if (!empty($whatsapp_prefix) && !empty($whatsapp_number)) {
                $whatsapp = $whatsapp_prefix . $whatsapp_number;
                update_user_meta($user_id, 'whatsapp', $whatsapp);
            } else {
                // Si están vacíos, eliminar el meta
                delete_user_meta($user_id, 'whatsapp');
            }
        }

        if (isset($_POST['estado'])) {
            update_user_meta($user_id, 'estado', sanitize_text_field($_POST['estado']));
        }

        if (isset($_POST['ciudad'])) {
            update_user_meta($user_id, 'ciudad', sanitize_text_field($_POST['ciudad']));
        }

        // Campos de cliente
        if (isset($_POST['fecha_nacimiento'])) {
            update_user_meta($user_id, 'fecha_nacimiento', sanitize_text_field($_POST['fecha_nacimiento']));
        }

        // Campos de proveedor
        if (isset($_POST['empresa'])) {
            update_user_meta($user_id, 'empresa', sanitize_text_field($_POST['empresa']));
        }

        if (isset($_POST['descripcion'])) {
            update_user_meta($user_id, 'descripcion', sanitize_textarea_field($_POST['descripcion']));
        }

        if (isset($_POST['proveedor_approved'])) {
            update_user_meta($user_id, 'proveedor_approved', '1');
        } else {
            // Si es proveedor, actualizar el estado
            if (Fullday_Users_Roles::is_proveedor($user_id)) {
                update_user_meta($user_id, 'proveedor_approved', '0');
            }
        }
    }

    /**
     * AJAX para toggle de aprobación
     */
    public static function ajax_toggle_approval() {
        check_ajax_referer('fullday_admin_nonce', 'nonce');

        if (!current_user_can('edit_users')) {
            wp_send_json_error(array('message' => 'No tienes permisos.'));
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $current_status = isset($_POST['approved']) ? intval($_POST['approved']) : 0;

        if (!$user_id) {
            wp_send_json_error(array('message' => 'Usuario no válido.'));
        }

        // Toggle el estado
        $new_status = $current_status ? '0' : '1';
        update_user_meta($user_id, 'proveedor_approved', $new_status);

        $button_text = $new_status === '1' ? 'Aprobado ✓' : 'Pendiente';
        $button_style = $new_status === '1' ? 'background: #10b981; border-color: #10b981;' : 'background: #ef4444; border-color: #ef4444; color: white;';

        wp_send_json_success(array(
            'approved' => $new_status,
            'button_text' => $button_text,
            'button_style' => $button_style
        ));
    }

    /**
     * Agregar estilos y scripts en el admin
     */
    public static function add_admin_styles() {
        $screen = get_current_screen();
        if ($screen->id !== 'users') {
            return;
        }
        ?>
        <style>
            .fullday-toggle-approval {
                cursor: pointer;
                font-size: 12px;
                padding: 5px 10px;
            }
        </style>
        <script>
        jQuery(document).ready(function($) {
            $('.fullday-toggle-approval').on('click', function(e) {
                e.preventDefault();
                const btn = $(this);
                const userId = btn.data('user-id');
                const approved = btn.data('approved');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fullday_toggle_approval',
                        nonce: '<?php echo wp_create_nonce('fullday_admin_nonce'); ?>',
                        user_id: userId,
                        approved: approved
                    },
                    success: function(response) {
                        if (response.success) {
                            btn.data('approved', response.data.approved);
                            btn.text(response.data.button_text);
                            btn.attr('style', response.data.button_style);
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Agregar página de configuración al menú
     */
    public static function add_settings_page() {
        $hook = add_options_page(
            'Fullday Users - Configuración',
            'Fullday Users',
            'manage_options',
            'fullday-users-settings',
            array(__CLASS__, 'render_settings_page')
        );

        // Cargar media uploader en esta página
        add_action('load-' . $hook, array(__CLASS__, 'load_settings_page_scripts'));
    }

    /**
     * Cargar scripts necesarios para la página de configuración
     */
    public static function load_settings_page_scripts() {
        // Cargar media uploader de WordPress
        wp_enqueue_media();
    }

    /**
     * Registrar configuraciones
     */
    public static function register_settings() {
        // Sección de Login Social
        add_settings_section(
            'fullday_social_login_section',
            'Configuración de Login Social',
            array(__CLASS__, 'social_login_section_callback'),
            'fullday-users-settings'
        );

        // Google Client ID
        register_setting('fullday_users_settings', 'fullday_google_client_id');
        add_settings_field(
            'fullday_google_client_id',
            'Google Client ID',
            array(__CLASS__, 'google_client_id_callback'),
            'fullday-users-settings',
            'fullday_social_login_section'
        );

        // Google Client Secret
        register_setting('fullday_users_settings', 'fullday_google_client_secret');
        add_settings_field(
            'fullday_google_client_secret',
            'Google Client Secret',
            array(__CLASS__, 'google_client_secret_callback'),
            'fullday-users-settings',
            'fullday_social_login_section'
        );

        // Facebook App ID
        register_setting('fullday_users_settings', 'fullday_facebook_app_id');
        add_settings_field(
            'fullday_facebook_app_id',
            'Facebook App ID',
            array(__CLASS__, 'facebook_app_id_callback'),
            'fullday-users-settings',
            'fullday_social_login_section'
        );

        // Facebook App Secret
        register_setting('fullday_users_settings', 'fullday_facebook_app_secret');
        add_settings_field(
            'fullday_facebook_app_secret',
            'Facebook App Secret',
            array(__CLASS__, 'facebook_app_secret_callback'),
            'fullday-users-settings',
            'fullday_social_login_section'
        );

        // Sección de Imágenes
        add_settings_section(
            'fullday_images_section',
            'Configuración de Imágenes',
            array(__CLASS__, 'images_section_callback'),
            'fullday-users-settings'
        );

        // Banner Placeholder
        register_setting('fullday_users_settings', 'fullday_banner_placeholder');
        add_settings_field(
            'fullday_banner_placeholder',
            'Banner Placeholder',
            array(__CLASS__, 'banner_placeholder_callback'),
            'fullday-users-settings',
            'fullday_images_section'
        );
    }

    /**
     * Renderizar página de configuración
     */
    public static function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Fullday Users - Configuración</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('fullday_users_settings');
                do_settings_sections('fullday-users-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Callback para la sección de login social
     */
    public static function social_login_section_callback() {
        echo '<p>Configura las credenciales de API para permitir login con Google y Facebook. <strong>Nota:</strong> Solo los usuarios tipo Cliente pueden usar login social.</p>';
        echo '<p><strong>Google OAuth 2.0:</strong> Obtén tus credenciales en <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a></p>';
        echo '<p><strong>Facebook Login:</strong> Obtén tus credenciales en <a href="https://developers.facebook.com/apps/" target="_blank">Facebook Developers</a></p>';
        echo '<p><strong>URL de Redirección:</strong> <code>' . esc_url(home_url('/fullday-auth-callback')) . '</code></p>';
    }

    /**
     * Campo Google Client ID
     */
    public static function google_client_id_callback() {
        $value = get_option('fullday_google_client_id', '');
        echo '<input type="text" name="fullday_google_client_id" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">ID de cliente de Google OAuth 2.0</p>';
    }

    /**
     * Campo Google Client Secret
     */
    public static function google_client_secret_callback() {
        $value = get_option('fullday_google_client_secret', '');
        echo '<input type="password" name="fullday_google_client_secret" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Secret de cliente de Google OAuth 2.0</p>';
    }

    /**
     * Campo Facebook App ID
     */
    public static function facebook_app_id_callback() {
        $value = get_option('fullday_facebook_app_id', '');
        echo '<input type="text" name="fullday_facebook_app_id" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">ID de la aplicación de Facebook</p>';
    }

    /**
     * Campo Facebook App Secret
     */
    public static function facebook_app_secret_callback() {
        $value = get_option('fullday_facebook_app_secret', '');
        echo '<input type="password" name="fullday_facebook_app_secret" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Secret de la aplicación de Facebook</p>';
    }

    /**
     * Callback para la sección de imágenes
     */
    public static function images_section_callback() {
        echo '<p>Configura las imágenes por defecto que se mostrarán cuando los proveedores no tengan imágenes personalizadas.</p>';
    }

    /**
     * Campo Banner Placeholder
     */
    public static function banner_placeholder_callback() {
        $image_id = get_option('fullday_banner_placeholder', '');
        $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
        ?>
        <div class="fullday-banner-placeholder-wrapper">
            <input type="hidden" id="fullday_banner_placeholder" name="fullday_banner_placeholder" value="<?php echo esc_attr($image_id); ?>" />
            <div class="banner-preview" style="margin-bottom: 10px;">
                <?php if ($image_url): ?>
                    <img src="<?php echo esc_url($image_url); ?>" style="max-width: 400px; height: auto; display: block; border: 1px solid #ddd; border-radius: 4px;" id="banner-preview-image" />
                <?php else: ?>
                    <div style="width: 400px; height: 150px; background: #f0f0f0; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; border-radius: 4px;" id="banner-preview-placeholder">
                        <span style="color: #999;">No hay imagen seleccionada</span>
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" class="button button-primary" id="fullday_banner_placeholder_button">
                <?php echo $image_url ? 'Cambiar Imagen' : 'Seleccionar Imagen'; ?>
            </button>
            <?php if ($image_url): ?>
                <button type="button" class="button" id="fullday_banner_placeholder_remove">Eliminar Imagen</button>
            <?php endif; ?>
            <p class="description">Esta imagen se mostrará en el banner de los proveedores que no tengan una imagen de banner personalizada.</p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            var frame;

            // Abrir media uploader
            $('#fullday_banner_placeholder_button').on('click', function(e) {
                e.preventDefault();

                if (frame) {
                    frame.open();
                    return;
                }

                frame = wp.media({
                    title: 'Seleccionar Banner Placeholder',
                    button: {
                        text: 'Usar esta imagen'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#fullday_banner_placeholder').val(attachment.id);

                    // Actualizar preview
                    var previewHtml = '<img src="' + attachment.url + '" style="max-width: 400px; height: auto; display: block; border: 1px solid #ddd; border-radius: 4px;" id="banner-preview-image" />';
                    $('.banner-preview').html(previewHtml);

                    // Actualizar botón
                    $('#fullday_banner_placeholder_button').text('Cambiar Imagen');

                    // Mostrar botón de eliminar si no existe
                    if (!$('#fullday_banner_placeholder_remove').length) {
                        $('#fullday_banner_placeholder_button').after('<button type="button" class="button" id="fullday_banner_placeholder_remove" style="margin-left: 5px;">Eliminar Imagen</button>');
                    }
                });

                frame.open();
            });

            // Eliminar imagen
            $(document).on('click', '#fullday_banner_placeholder_remove', function(e) {
                e.preventDefault();

                $('#fullday_banner_placeholder').val('');

                // Actualizar preview
                var placeholderHtml = '<div style="width: 400px; height: 150px; background: #f0f0f0; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; border-radius: 4px;" id="banner-preview-placeholder"><span style="color: #999;">No hay imagen seleccionada</span></div>';
                $('.banner-preview').html(placeholderHtml);

                // Actualizar botón
                $('#fullday_banner_placeholder_button').text('Seleccionar Imagen');
                $(this).remove();
            });
        });
        </script>
        <?php
    }
}
