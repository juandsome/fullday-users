<?php
/**
 * Vista de Mis Viajes para Proveedor
 *
 * @package Fullday_Users
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();

// Query para obtener Full Days del proveedor
$args = array(
    'post_type' => 'full-days',
    'author' => $user_id,
    'posts_per_page' => -1,
    'post_status' => array('publish', 'draft'),
    'orderby' => 'date',
    'order' => 'DESC'
);

$fulldays_query = new WP_Query($args);
?>

<div class="mis-viajes-container">
    <div class="mis-viajes-header">
        <div class="header-content">
            <h2 class="mis-viajes-title">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
                Mis Full Days
            </h2>
            <p class="mis-viajes-description">Gestiona tus experiencias publicadas</p>
        </div>
        <button type="button" class="btn-nuevo-fullday" id="btn-nuevo-fullday">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Nuevo Full Day
        </button>
    </div>

    <?php if ($fulldays_query->have_posts()) : ?>
        <div class="fulldays-grid">
            <?php while ($fulldays_query->have_posts()) : $fulldays_query->the_post();
                $post_id = get_the_ID();
                $thumbnail_url = get_the_post_thumbnail_url($post_id, 'large');
                $price = get_post_meta($post_id, 'full_days_price', true);
                $departure_date = get_post_meta($post_id, 'full_days_departure_date', true);
                $max_people = get_post_meta($post_id, 'full_days_max_people', true) ?: 0;
                $available_spots = get_post_meta($post_id, 'full_days_available_spots', true);
                // Si no existe available_spots, usar max_people como valor inicial
                if ($available_spots === '') {
                    $available_spots = $max_people;
                }
                $rating = get_post_meta($post_id, 'full_days_rating', true) ?: 0;
                $reviews_count = get_post_meta($post_id, 'full_days_reviews_count', true) ?: 0;
                $views = get_post_meta($post_id, 'full_days_views', true) ?: 0;
                $is_active = get_post_status() === 'publish';
                $post_slug = get_post_field('post_name', $post_id);
            ?>
                <div class="fullday-card" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <div class="card-image">
                        <?php if ($thumbnail_url): ?>
                            <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                        <?php else: ?>
                            <div class="card-image-placeholder">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-content">
                        <div class="card-header">
                            <div class="card-info">
                                <h3 class="card-title"><?php echo esc_html(get_the_title()); ?></h3>
                                <p class="card-price">$<?php echo number_format($price, 0); ?></p>
                            </div>
                            <button type="button"
                                    class="card-status-badge <?php echo $is_active ? 'active' : 'paused'; ?>"
                                    data-post-id="<?php echo esc_attr($post_id); ?>"
                                    data-status="<?php echo $is_active ? 'publish' : 'draft'; ?>"
                                    title="Click para cambiar estado">
                                <?php echo $is_active ? 'Activo' : 'Pausado'; ?>
                            </button>
                        </div>

                        <!-- Control de cupos disponibles -->
                        <div class="card-availability">
                            <span class="availability-label">Cupos disponibles:</span>
                            <div class="availability-control">
                                <button type="button" class="btn-availability btn-decrease" data-post-id="<?php echo esc_attr($post_id); ?>" aria-label="Disminuir">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                </button>
                                <input type="number"
                                       class="availability-input"
                                       data-post-id="<?php echo esc_attr($post_id); ?>"
                                       data-max="<?php echo esc_attr($max_people); ?>"
                                       value="<?php echo esc_attr($available_spots); ?>"
                                       min="0"
                                       max="<?php echo esc_attr($max_people); ?>"
                                       readonly>
                                <button type="button" class="btn-availability btn-increase" data-post-id="<?php echo esc_attr($post_id); ?>" aria-label="Aumentar">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                </button>
                            </div>
                            <span class="availability-max">de <?php echo esc_html($max_people); ?></span>
                        </div>

                        <div class="card-stats">
                            <span class="stat-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                </svg>
                                <?php echo number_format($rating, 1); ?> estrellas
                            </span>
                            <?php if ($departure_date): ?>
                            <span class="stat-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <?php echo date('d/m/Y', strtotime($departure_date)); ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <div class="card-actions">
                            <button type="button" class="btn-action btn-editar" data-post-id="<?php echo esc_attr($post_id); ?>" title="Editar" aria-label="Editar">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <a href="<?php echo $is_active ? esc_url(home_url('/full-days/' . $post_slug)) : '#'; ?>"
                               class="btn-action btn-ver <?php echo !$is_active ? 'disabled' : ''; ?>"
                               data-post-id="<?php echo esc_attr($post_id); ?>"
                               data-slug="<?php echo esc_attr($post_slug); ?>"
                               title="Ver"
                               aria-label="Ver"
                               <?php echo $is_active ? 'target="_blank"' : ''; ?>
                               <?php echo !$is_active ? 'onclick="return false;"' : ''; ?>>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </a>
                            <button type="button" class="btn-action btn-eliminar" data-post-id="<?php echo esc_attr($post_id); ?>" title="Eliminar" aria-label="Eliminar">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
            </svg>
            <h3>No tienes Full Days publicados</h3>
            <p>Crea tu primera experiencia para que los clientes puedan reservar</p>
            <button type="button" class="btn-primary" id="btn-crear-primer-fullday">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Crear Mi Primer Full Day
            </button>
        </div>
    <?php endif; ?>

    <?php wp_reset_postdata(); ?>

    <div class="form-message" id="mis-viajes-message" style="display: none;"></div>
</div>
