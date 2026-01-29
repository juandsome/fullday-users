<?php
/**
 * Template: Crear Full Day con IA
 *
 * @package Fullday_Users
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$proveedor_approved = get_user_meta($user_id, 'proveedor_approved', true);

// Verificar si hay API Key configurada
$api_key_configured = !empty(get_option('fullday_gemini_api_key', ''));

// Obtener avatar de Fully
$avatar_id = get_option('fullday_ai_avatar', '');
$avatar_url = $avatar_id ? wp_get_attachment_url($avatar_id) : '';

// Obtener nombre de la empresa o usuario
$user = get_userdata($user_id);
$empresa = get_user_meta($user_id, 'empresa', true);
$nombre_proveedor = !empty($empresa) ? $empresa : $user->display_name;
?>

<div class="fullday-ai-chat-container">
    <div class="ai-chat-header">
        <h2>Crear Full Day con Fully</h2>
        <p class="ai-chat-subtitle">Conversa con Fully, nuestra mascota, para crear tu Full Day de manera fácil y rápida</p>
    </div>

    <?php if ($proveedor_approved !== '1'): ?>
        <div class="ai-chat-warning">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <div>
                <h3>Cuenta pendiente de aprobación</h3>
                <p>Tu cuenta de proveedor debe estar aprobada para poder crear Full Days con IA.</p>
            </div>
        </div>
    <?php elseif (!$api_key_configured): ?>
        <div class="ai-chat-warning">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <div>
                <h3>API de IA no configurada</h3>
                <p>El administrador del sitio debe configurar la API Key de Google Gemini para usar esta función.</p>
                <?php if (current_user_can('manage_options')): ?>
                    <a href="<?php echo admin_url('options-general.php?page=fullday-ai-settings'); ?>" class="btn-primary">Configurar API Key</a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="ai-chat-box">
            <div class="ai-chat-messages" id="ai-chat-messages">
                <div class="ai-message">
                    <div class="message-avatar fully-avatar" data-avatar-url="<?php echo esc_url($avatar_url); ?>">
                        <?php if ($avatar_url): ?>
                            <img src="<?php echo esc_url($avatar_url); ?>" alt="Fully">
                        <?php else: ?>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <div class="message-content">
                        <p>¡Hola! Soy Fully 🎉 y estoy listo para ayudarte a crear un Full Day increíble para <strong><?php echo esc_html($nombre_proveedor); ?></strong>.</p>
                        <p><strong>¿Qué experiencia quieres ofrecer?</strong> Cuéntame sobre el destino y qué incluye tu tour.</p>
                    </div>
                </div>
            </div>

            <div class="ai-chat-input-container">
                <div class="ai-chat-actions">
                    <button type="button" class="btn-secondary" id="ai-clear-chat">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="1 4 1 10 7 10"></polyline>
                            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                        </svg>
                        Limpiar Chat
                    </button>
                    <button type="button" class="btn-primary" id="ai-save-fullday" disabled>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        Guardar Full Day
                    </button>
                </div>

                <div class="ai-chat-input-wrapper">
                    <div class="input-controls">
                        <textarea
                            id="ai-chat-input"
                            placeholder="Escribe tu mensaje..."
                            rows="3"
                        ></textarea>
                        <button type="button" class="btn-send" id="ai-send-message">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="ai-chat-tips">
            <h3>💡 Cómo funciona:</h3>
            <ul>
                <li>La IA te hará preguntas paso a paso sobre tu Full Day</li>
                <li>Responde con la información que tengas disponible</li>
                <li>No te preocupes si no tienes todos los detalles - la IA te ayudará</li>
                <li>Cuando presiones "Guardar Full Day", la IA completará automáticamente cualquier información faltante</li>
                <li>El Full Day se guardará como borrador para que puedas editarlo después</li>
                <li>Las imágenes las agregarás después en la edición del Full Day</li>
                <li>Mientras más detalles proporciones, mejor será el resultado inicial</li>
            </ul>
        </div>
    <?php endif; ?>
</div>
