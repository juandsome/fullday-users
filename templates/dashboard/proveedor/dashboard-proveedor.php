<?php
/**
 * Dashboard de Proveedor
 *
 * @package Fullday_Users
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$user = get_userdata($user_id);
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'perfil';
?>

<div class="fullday-dashboard fullday-dashboard-proveedor">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Gestiona tu perfil y tus experiencias full day</h1>
    </div>

    <div class="dashboard-tabs">
        <button class="dashboard-tab <?php echo $current_tab === 'perfil' ? 'active' : ''; ?>" data-tab="perfil">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            Perfil
        </button>
        <button class="dashboard-tab <?php echo $current_tab === 'mis-viajes' ? 'active' : ''; ?>" data-tab="mis-viajes">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
            </svg>
            Mis Viajes
        </button>
        <button class="dashboard-tab <?php echo $current_tab === 'crear' ? 'active' : ''; ?>" data-tab="crear">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Crear
        </button>
        <button class="dashboard-tab <?php echo $current_tab === 'crear-ia' ? 'active' : ''; ?>" data-tab="crear-ia">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                <circle cx="9" cy="10" r="1" fill="currentColor"></circle>
                <circle cx="12" cy="10" r="1" fill="currentColor"></circle>
                <circle cx="15" cy="10" r="1" fill="currentColor"></circle>
            </svg>
            Crear Full Day con IA
        </button>
    </div>

    <div class="dashboard-content">
        <div class="tab-content <?php echo $current_tab === 'perfil' ? 'active' : ''; ?>" id="perfil-tab">
            <?php include FULLDAY_USERS_PLUGIN_DIR . 'templates/dashboard/proveedor/perfil.php'; ?>
        </div>

        <div class="tab-content <?php echo $current_tab === 'mis-viajes' ? 'active' : ''; ?>" id="mis-viajes-tab">
            <?php include FULLDAY_USERS_PLUGIN_DIR . 'templates/dashboard/proveedor/mis-viajes.php'; ?>
        </div>

        <div class="tab-content <?php echo ($current_tab === 'crear' || $current_tab === 'editar') ? 'active' : ''; ?>" id="crear-editar-tab">
            <?php include FULLDAY_USERS_PLUGIN_DIR . 'templates/dashboard/proveedor/crear-editar.php'; ?>
        </div>

        <div class="tab-content <?php echo $current_tab === 'crear-ia' ? 'active' : ''; ?>" id="crear-ia-tab">
            <?php include FULLDAY_USERS_PLUGIN_DIR . 'templates/dashboard/proveedor/crear-ia.php'; ?>
        </div>
    </div>
</div>
