<?php
/**
 * Vista de Perfil para Proveedor
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
$empresa = get_user_meta($user_id, 'empresa', true);
$descripcion = get_user_meta($user_id, 'descripcion', true);
$estado = get_user_meta($user_id, 'estado', true);
$ciudad = get_user_meta($user_id, 'ciudad', true);
$facebook_url = get_user_meta($user_id, 'facebook_url', true);
$instagram_url = get_user_meta($user_id, 'instagram_url', true);
$cashea_code = get_user_meta($user_id, 'cashea_code', true);
$avatar_url = Fullday_Users_Dashboard::get_user_avatar($user_id);
$banner_id = get_user_meta($user_id, 'banner', true);
$banner_url = $banner_id ? wp_get_attachment_url($banner_id) : '';
$initials = Fullday_Users_Dashboard::get_user_initials($user_id);
$approved = get_user_meta($user_id, 'proveedor_approved', true);
$is_approved = ($approved === '1' || $approved === 1);

// Generar URL del perfil público
$profile_slug = $empresa ? sanitize_title($empresa) : sanitize_title($user->user_login);
$profile_url = home_url('/perfil/' . $profile_slug . '/');
?>

<div class="perfil-container">
    <!-- Badge de Status -->
    <div class="account-status-banner">
        <span class="status-label">Status:</span>
        <span class="status-badge <?php echo $is_approved ? 'status-approved' : 'status-pending'; ?>">
            <?php echo $is_approved ? 'Aprobada' : 'Pendiente'; ?>
        </span>
    </div>

    <!-- Preview del Banner -->
    <div class="banner-preview-section">
        <p class="section-description">Así se verá tu banner para los clientes</p>

        <!-- Botón de copiar link del perfil -->
        <div class="profile-link-section">
            <div class="profile-link-container">
                <div class="profile-link-preview">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                    </svg>
                    <span class="profile-link-url"><?php echo esc_html($profile_url); ?></span>
                </div>
                <button type="button" class="btn-copy-profile-link" id="btn-copy-profile-link" data-url="<?php echo esc_attr($profile_url); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>
                    <span class="btn-copy-text">Copiar link</span>
                    <span class="btn-copied-text" style="display: none;">¡Copiado!</span>
                </button>
            </div>
        </div>

        <div class="proveedor-banner-preview">
            <div class="banner-image" id="banner-preview-display" style="background-image: url('<?php echo $banner_url ? esc_url($banner_url) : ''; ?>');">
                <?php if (!$banner_url): ?>
                    <div class="banner-placeholder">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                        <p>Sube un banner</p>
                    </div>
                <?php endif; ?>

                <!-- Upload section que aparece en hover -->
                <div class="banner-upload-overlay">
                    <button type="button" class="btn-cambiar-banner" id="proveedor-btn-cambiar-banner">Cambiar Banner</button>
                    <p class="banner-upload-hint">Recomendado: 1200x400px, JPG o PNG</p>
                </div>
            </div>
            <div class="banner-content-overlay">
                <div class="banner-avatar">
                    <?php if ($avatar_url): ?>
                        <img src="<?php echo esc_url($avatar_url); ?>" alt="Avatar" id="banner-avatar-preview">
                    <?php else: ?>
                        <span class="banner-avatar-initials" id="banner-avatar-preview"><?php echo esc_html($initials); ?></span>
                    <?php endif; ?>
                </div>
                <div class="banner-info">
                    <h3 class="banner-empresa" id="banner-empresa-preview"><?php echo $empresa ? esc_html($empresa) : 'Nombre de tu Empresa'; ?></h3>
                    <div class="banner-social">
                        <?php if ($facebook_url): ?>
                            <a href="<?php echo esc_url($facebook_url); ?>" target="_blank" class="social-icon" id="banner-facebook-preview">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                        <?php if ($instagram_url): ?>
                            <a href="<?php echo esc_url($instagram_url); ?>" target="_blank" class="social-icon" id="banner-instagram-preview">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <input type="file" id="proveedor-banner-upload" accept="image/jpeg,image/png" style="display: none;">
    </div>

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
        <button type="button" class="btn-cambiar-foto" id="proveedor-btn-cambiar-foto">Cambiar Foto</button>
        <input type="file" id="proveedor-avatar-upload" accept="image/jpeg,image/png" style="display: none;">
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
                <label for="empresa">Empresa</label>
                <input type="text" id="empresa" name="empresa" value="<?php echo esc_attr($empresa); ?>" placeholder="Aventuras Extremas SPA">
            </div>

            <div class="form-group">
                <label for="whatsapp">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 4px;">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    WhatsApp
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
                <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">Este número se usará de manera pública como WhatsApp. Déjalo en blanco si no quieres utilizarlo.</small>
            </div>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="4" placeholder="Especialista en turismo aventura con más de 10 años de experiencia. Ofrezco experiencias únicas e inolvidables en la naturaleza."><?php echo esc_textarea($descripcion); ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="estado">Estado</label>
                <select id="estado" name="estado" required>
                    <option value="">Selecciona un estado</option>
                    <?php
                    // Obtener estados (términos padre) de la taxonomía region
                    $estados_terms = get_terms(array(
                        'taxonomy' => 'region',
                        'hide_empty' => false,
                        'parent' => 0
                    ));
                    foreach ($estados_terms as $estado_term):
                    ?>
                        <option value="<?php echo esc_attr($estado_term->term_id); ?>" <?php selected($estado, $estado_term->term_id); ?>><?php echo esc_html($estado_term->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="ciudad">Ciudad</label>
                <select id="ciudad" name="ciudad" required>
                    <option value="">Selecciona una ciudad</option>
                    <?php if ($estado): ?>
                        <?php
                        // Obtener ciudades (términos hijos) del estado seleccionado
                        $ciudades_terms = get_terms(array(
                            'taxonomy' => 'region',
                            'hide_empty' => false,
                            'parent' => $estado
                        ));
                        foreach ($ciudades_terms as $ciudad_term):
                        ?>
                            <option value="<?php echo esc_attr($ciudad_term->term_id); ?>" <?php selected($ciudad, $ciudad_term->term_id); ?>><?php echo esc_html($ciudad_term->name); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <h3 class="section-subtitle">Redes Sociales</h3>

        <div class="form-row">
            <div class="form-group">
                <label for="facebook_url">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 4px;">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    Facebook
                </label>
                <input type="url" id="facebook_url" name="facebook_url" value="<?php echo esc_attr($facebook_url); ?>" placeholder="https://facebook.com/tupagina">
            </div>

            <div class="form-group">
                <label for="instagram_url">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 4px;">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                    Instagram
                </label>
                <input type="url" id="instagram_url" name="instagram_url" value="<?php echo esc_attr($instagram_url); ?>" placeholder="https://instagram.com/tuperfil">
            </div>
        </div>

        <div class="form-group">
            <label for="cashea_code">Cashea</label>
            <input type="text" id="cashea_code" name="cashea_code" value="<?php echo esc_attr($cashea_code); ?>" placeholder="TuCodigoCashea">
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
