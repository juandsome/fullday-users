<?php
/**
 * Vista de Favoritos para Cliente
 *
 * @package Fullday_Users
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();

// Obtener favoritos del usuario usando la clase Fullday_Favorites
$favorites_class = new Fullday_Favorites();
$favoritos = $favorites_class->get_favorites($user_id);

// Query para obtener los Full Days favoritos
$args = array(
    'post_type' => 'full-days',
    'post__in' => !empty($favoritos) ? $favoritos : array(0), // Si está vacío, usar 0 para que no devuelva nada
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'post__in'
);

$favoritos_query = new WP_Query($args);
?>

<div class="favoritos-container">
    <div class="favoritos-header">
        <h2 class="favoritos-title">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
            Mis Favoritos
        </h2>
        <p class="favoritos-description">Experiencias que has guardado para más tarde</p>
    </div>

    <?php if ($favoritos_query->have_posts()) : ?>
        <div class="favoritos-grid">
            <?php while ($favoritos_query->have_posts()) : $favoritos_query->the_post();
                $post_id = get_the_ID();
                $thumbnail_url = get_the_post_thumbnail_url($post_id, 'large');
                $price = get_post_meta($post_id, 'full_days_price', true);
                $original_price = get_post_meta($post_id, 'full_days_discount_price', true);
                $rating = get_post_meta($post_id, 'full_days_rating', true) ?: 0;
                $destination = get_post_meta($post_id, 'full_days_destination', true);
                $departure_date = get_post_meta($post_id, 'full_days_departure_date', true);
                $duration = get_post_meta($post_id, 'full_days_duration', true);
                $post_slug = get_post_field('post_name', $post_id);

                // Calcular descuento si existe
                $has_discount = false;
                $discount_percentage = 0;
                if ($original_price && $original_price > $price) {
                    $has_discount = true;
                    $discount_percentage = round((($original_price - $price) / $original_price) * 100);
                }
            ?>
                <div class="favorito-card" data-post-id="<?php echo esc_attr($post_id); ?>">
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

                        <!-- Botón de favorito -->
                        <button type="button"
                                class="fullday-favorite-btn is-favorite"
                                data-post-id="<?php echo esc_attr($post_id); ?>"
                                data-nonce="<?php echo wp_create_nonce('fullday_favorites_nonce'); ?>"
                                title="Eliminar de favoritos"
                                aria-label="Eliminar de favoritos">
                            <svg class="heart-outline" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                            </svg>
                            <svg class="heart-filled" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                            </svg>
                        </button>

                        <?php if ($has_discount): ?>
                            <div class="card-discount-badge">
                                -<?php echo esc_html($discount_percentage); ?>%
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-content">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo esc_html(get_the_title()); ?></h3>
                            <div class="card-location">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <?php echo esc_html($destination); ?>
                            </div>
                        </div>

                        <div class="card-meta">
                            <div class="card-rating">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="#FCD34D" stroke="#FCD34D" stroke-width="2">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                </svg>
                                <span><?php echo number_format($rating, 1); ?></span>
                            </div>
                            <?php if ($departure_date): ?>
                            <div class="card-departure">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <?php echo date('d/m/Y', strtotime($departure_date)); ?>
                            </div>
                            <?php endif; ?>
                            <div class="card-duration">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <?php echo esc_html($duration); ?>
                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="card-price-wrapper">
                                <?php if ($has_discount): ?>
                                    <span class="card-price-original">$<?php echo number_format($original_price, 0); ?></span>
                                <?php endif; ?>
                                <span class="card-price">$<?php echo number_format($price, 0); ?></span>
                            </div>
                            <a href="<?php echo esc_url(home_url('/full-days/' . $post_slug)); ?>" class="btn-ver-detalle" target="_blank">
                                Ver Detalle
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
            <h3>No tienes favoritos guardados</h3>
            <p>Explora nuestras experiencias y guarda tus favoritas para verlas más tarde</p>
            <a href="<?php echo esc_url(home_url('/full-days')); ?>" class="btn-primary">
                Explorar Experiencias
            </a>
        </div>
    <?php endif; ?>

    <?php wp_reset_postdata(); ?>

    <div class="form-message" id="favoritos-message" style="display: none;"></div>
</div>
