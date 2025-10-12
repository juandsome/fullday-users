<?php
/**
 * Dashboard de Cliente
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

<div class="fullday-dashboard fullday-dashboard-cliente">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Mi Dashboard</h1>
        <p class="dashboard-subtitle">Gestiona tu perfil y experiencias favoritas</p>
    </div>

    <div class="dashboard-tabs">
        <button class="dashboard-tab <?php echo $current_tab === 'perfil' ? 'active' : ''; ?>" data-tab="perfil">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            Perfil
        </button>
        <button class="dashboard-tab <?php echo $current_tab === 'favoritos' ? 'active' : ''; ?>" data-tab="favoritos">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
            Favoritos
        </button>
    </div>

    <div class="dashboard-content">
        <div class="tab-content <?php echo $current_tab === 'perfil' ? 'active' : ''; ?>" id="perfil-tab">
            <?php include FULLDAY_USERS_PLUGIN_DIR . 'templates/dashboard/cliente/perfil.php'; ?>
        </div>

        <div class="tab-content <?php echo $current_tab === 'favoritos' ? 'active' : ''; ?>" id="favoritos-tab">
            <?php include FULLDAY_USERS_PLUGIN_DIR . 'templates/dashboard/cliente/favoritos.php'; ?>
        </div>
    </div>
</div>
