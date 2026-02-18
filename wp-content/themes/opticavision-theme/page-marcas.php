<?php
/**
 * Template Name: Marcas
 * 
 * Página que muestra todas las marcas del catálogo en cuadrícula
 * con enlaces a sus respectivas páginas de categoría
 * 
 * @package OpticaVision_Theme
 * @since 1.0.0
 */

get_header(); ?>

<div class="marcas-page">
    <div class="container">
        
        <!-- Hero Section -->
        <div class="marcas-hero">
            <h1 class="marcas-title">Nuestras Marcas</h1>
            <p class="marcas-subtitle">
                Trabajamos con las mejores marcas internacionales para ofrecerte productos de la más alta calidad. 
                Explora nuestra selección completa de marcas premium en óptica.
            </p>
        </div>

        <?php
        // Obtener las marcas usando la función existente del sistema
        $marcas = array();
        
        if (function_exists('ovc_get_marcas_subcategories')) {
            $marcas = ovc_get_marcas_subcategories();
        } else {
            // Fallback: obtener subcategorías de la categoría "marcas" manualmente
            $marcas_category = get_term_by('slug', 'marcas', 'product_cat');
            if ($marcas_category) {
                $marcas = get_terms(array(
                    'taxonomy' => 'product_cat',
                    'parent' => $marcas_category->term_id,
                    'hide_empty' => false,
                    'orderby' => 'name',
                    'order' => 'ASC'
                ));
            }
        }

        if (!empty($marcas) && !is_wp_error($marcas)): ?>
            
            <!-- Contador de marcas -->
            <div class="marcas-stats">
                <div class="stats-item">
                    <span class="stats-number"><?php echo count($marcas); ?></span>
                    <span class="stats-label">Marcas Disponibles</span>
                </div>
            </div>

            <!-- Grid de marcas -->
            <div class="marcas-grid">
                <?php foreach ($marcas as $marca): 
                    $marca_link = get_term_link($marca);
                    $marca_image_id = get_term_meta($marca->term_id, 'thumbnail_id', true);
                    $marca_image = '';
                    
                    // Obtener imagen de la marca si existe en WordPress
                    if ($marca_image_id) {
                        $marca_image = wp_get_attachment_image_src($marca_image_id, 'medium');
                        $marca_image = $marca_image ? $marca_image[0] : '';
                    }
                    
                    // Si no hay imagen en WordPress, buscar en carpeta de logos
                    if (!$marca_image) {
                        // Convertir nombre de marca a formato de archivo: minúsculas y guiones
                        $logo_filename = strtolower(str_replace(' ', '-', $marca->name)) . '.jpg';
                        $logo_path = get_template_directory() . '/marcas/' . $logo_filename;
                        
                        // Verificar si el archivo existe
                        if (file_exists($logo_path)) {
                            $marca_image = get_template_directory_uri() . '/marcas/' . $logo_filename;
                        }
                    }
                    
                    // Contar productos en esta marca
                    $product_count = $marca->count;
                    ?>
                    
                    <div class="marca-card">
                        <a href="<?php echo esc_url($marca_link); ?>" class="marca-link">
                            <div class="marca-image-container">
                                <?php if ($marca_image): ?>
                                    <img src="<?php echo esc_url($marca_image); ?>" 
                                         alt="<?php echo esc_attr($marca->name); ?>" 
                                         class="marca-image"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="marca-placeholder">
                                        <i class="fas fa-eye"></i>
                                        <span class="marca-name-fallback"><?php echo esc_html($marca->name); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="marca-info">
                                <h3 class="marca-name"><?php echo esc_html($marca->name); ?></h3>
                                
                                <?php if ($marca->description): ?>
                                    <p class="marca-description"><?php echo esc_html(wp_trim_words($marca->description, 15)); ?></p>
                                <?php endif; ?>
                                
                                <div class="marca-stats">
                                    <span class="product-count">
                                        <i class="fas fa-glasses"></i>
                                        <?php echo $product_count; ?> <?php echo $product_count == 1 ? 'producto' : 'productos'; ?>
                                    </span>
                                </div>
                                
                                <div class="marca-cta">
                                    <span class="cta-text">Ver Productos</span>
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                    
                <?php endforeach; ?>
            </div>

            

        <?php else: ?>
            
            <!-- Mensaje cuando no hay marcas -->
            <div class="no-marcas">
                <div class="no-marcas-content">
                    <i class="fas fa-search"></i>
                    <h2>No se encontraron marcas</h2>
                    <p>Actualmente no hay marcas disponibles en el catálogo.</p>
                    <a href="<?php echo home_url('/tienda'); ?>" class="btn-primary">
                        <i class="fas fa-shopping-bag"></i>
                        Ver Todos los Productos
                    </a>
                </div>
            </div>
            
        <?php endif; ?>
        
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Animación para las tarjetas de marca al hacer scroll
    if (typeof IntersectionObserver !== 'undefined') {
        const marcasObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        $('.marca-card').each(function(index) {
            $(this).css('animation-delay', (index * 0.1) + 's');
            marcasObserver.observe(this);
        });
    }

    // Efecto hover mejorado
    $('.marca-card').on('mouseenter', function() {
        $(this).find('.marca-cta').addClass('hover');
    }).on('mouseleave', function() {
        $(this).find('.marca-cta').removeClass('hover');
    });

    // Analytics tracking para clicks en marcas
    $('.marca-link').on('click', function() {
        const marcaName = $(this).find('.marca-name').text();
        
        // Google Analytics si está disponible
        if (typeof gtag !== 'undefined') {
            gtag('event', 'marca_click', {
                event_category: 'Marcas',
                event_label: marcaName
            });
        }
        
        // Logging para análisis interno
        if (typeof optica_log_info === 'function') {
            console.log('Marca visitada: ' + marcaName);
        }
    });
});
</script>

<?php get_footer(); ?>
