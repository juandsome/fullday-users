<?php
/**
 * Vista de Perfil para Cliente
 *
 * @package Fullday_Users
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$user = get_userdata($user_id);
$display_name = $user->display_name ?: $user->user_login;
$email = $user->user_email;
$whatsapp = get_user_meta($user_id, 'whatsapp', true);
// Separar prefijo y número si existe
$whatsapp_prefix = '0412'; // Default
$whatsapp_number = '';
if ($whatsapp) {
    $whatsapp_prefix = substr($whatsapp, 0, 4);
    $whatsapp_number = substr($whatsapp, 4);
}
$fecha_nacimiento = get_user_meta($user_id, 'fecha_nacimiento', true);
$estado = get_user_meta($user_id, 'estado', true);
$ciudad = get_user_meta($user_id, 'ciudad', true);
$avatar_url = Fullday_Users_Dashboard::get_user_avatar($user_id);
$initials = Fullday_Users_Dashboard::get_user_initials($user_id);
?>

<div class="perfil-container">
    <h2 class="perfil-title">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
        </svg>
        Información Personal
    </h2>

    <div class="avatar-section">
        <div class="avatar-circle" id="avatar-display">
            <?php if ($avatar_url): ?>
                <img src="<?php echo esc_url($avatar_url); ?>" alt="Avatar">
            <?php else: ?>
                <span class="avatar-initials"><?php echo esc_html($initials); ?></span>
            <?php endif; ?>
        </div>
        <button type="button" class="btn-cambiar-foto" id="cliente-btn-cambiar-foto">Cambiar Foto</button>
        <input type="file" id="cliente-avatar-upload" accept="image/jpeg,image/png" style="display: none;">
    </div>

    <form id="perfil-form" class="perfil-form">
        <?php wp_nonce_field('fullday_users_nonce', 'fullday_nonce_field'); ?>

        <div class="form-row">
            <div class="form-group">
                <label for="display_name">Nombre Completo</label>
                <input type="text" id="display_name" name="display_name" value="<?php echo esc_attr($display_name); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo esc_attr($email); ?>" readonly>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="whatsapp">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 4px;">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    WhatsApp (opcional)
                </label>
                <div style="display: flex; gap: 10px;">
                    <select id="whatsapp_prefix" name="whatsapp_prefix" style="width: 100px;">
                        <option value="0412" <?php selected($whatsapp_prefix, '0412'); ?>>0412</option>
                        <option value="0416" <?php selected($whatsapp_prefix, '0416'); ?>>0416</option>
                        <option value="0426" <?php selected($whatsapp_prefix, '0426'); ?>>0426</option>
                        <option value="0424" <?php selected($whatsapp_prefix, '0424'); ?>>0424</option>
                        <option value="0414" <?php selected($whatsapp_prefix, '0414'); ?>>0414</option>
                    </select>
                    <input type="text" id="whatsapp_number" name="whatsapp_number" value="<?php echo esc_attr($whatsapp_number); ?>" placeholder="1234567" maxlength="7" pattern="[0-9]{7}" style="flex: 1;">
                </div>
            </div>

            <div class="form-group">
                <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo esc_attr($fecha_nacimiento); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="estado">Estado</label>
                <select id="estado" name="estado" required>
                    <option value="">Selecciona un estado</option>
                    <?php foreach (Fullday_Users_Locations::get_estados() as $key => $nombre): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($estado, $key); ?>><?php echo esc_html($nombre); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="ciudad">Ciudad</label>
                <select id="ciudad" name="ciudad" required>
                    <option value="">Selecciona una ciudad</option>
                    <?php if ($estado && $ciudad): ?>
                        <?php foreach (Fullday_Users_Locations::get_ciudades($estado) as $ciudad_option): ?>
                            <option value="<?php echo esc_attr($ciudad_option); ?>" <?php selected($ciudad, $ciudad_option); ?>><?php echo esc_html($ciudad_option); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <div class="form-message" id="perfil-message" style="display: none;"></div>

        <button type="submit" class="btn-actualizar" id="btn-actualizar-perfil">
            <span class="btn-text">Actualizar Perfil</span>
            <span class="btn-loader" style="display: none;">
                <svg class="spinner" width="20" height="20" viewBox="0 0 24 24">
                    <circle class="spinner-circle" cx="12" cy="12" r="10" fill="none" stroke-width="3"></circle>
                </svg>
            </span>
        </button>
    </form>

    <!-- Sección de cambio de contraseña -->
    <div class="password-section">
        <h3 class="password-title">Cambiar Contraseña</h3>

        <form id="password-form" class="password-form">
            <?php wp_nonce_field('fullday_users_nonce', 'fullday_password_nonce_field'); ?>

            <div class="form-group">
                <label for="current_password">Contraseña Actual</label>
                <input type="password" id="current_password" name="current_password" placeholder="••••••••" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="new_password">Nueva Contraseña</label>
                    <input type="password" id="new_password" name="new_password" placeholder="••••••••" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
                </div>
            </div>

            <div class="form-message" id="password-message" style="display: none;"></div>

            <button type="submit" class="btn-actualizar btn-secondary" id="btn-actualizar-password">
                <span class="btn-text">Actualizar Contraseña</span>
                <span class="btn-loader" style="display: none;">
                    <svg class="spinner" width="20" height="20" viewBox="0 0 24 24">
                        <circle class="spinner-circle" cx="12" cy="12" r="10" fill="none" stroke-width="3"></circle>
                    </svg>
                </span>
            </button>
        </form>
    </div>
</div>
