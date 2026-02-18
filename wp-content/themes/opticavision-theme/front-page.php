<?php
/**
 * The template for displaying the front page
 *
 * @package OpticaVision_Theme
 */

get_header(); ?>

<main id="primary" class="site-main homepage">
    
    <!-- Hero Slider Section -->
    <section class="homepage-hero">
        <?php 
        // Display the new Hero Slider system
        echo do_shortcode('[opticavision_hero_slider]');
        ?>
    </section>

    <!-- Features Bar -->
    <section class="features-bar">
        <div class="container">
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-truck" aria-hidden="true"></i>
                    </div>
                    <div class="feature-text">
                        <h3><?php esc_html_e('ENTREGA EN TODO EL PAÍS', 'opticavision-theme'); ?></h3>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-shopping-cart" aria-hidden="true"></i>
                    </div>
                    <div class="feature-text">
                        <h3><?php esc_html_e('PAGO ONLINE RÁPIDO Y SEGURO', 'opticavision-theme'); ?></h3>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fab fa-whatsapp" aria-hidden="true"></i>
                    </div>
                    <div class="feature-text">
                        <h3><?php esc_html_e('TE ASESORAMOS ONLINE VÍA WHATSAPP', 'opticavision-theme'); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Brands Carousel 
    <section class="homepage-section brands-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><?php esc_html_e('Marcas Destacadas', 'opticavision-theme'); ?></h2>
                <p class="section-subtitle"><?php esc_html_e('Trabajamos con las mejores marcas del mercado', 'opticavision-theme'); ?></p>
            </div>
            
            <div class="brands-carousel" id="brands-carousel">
                <div class="brands-track">
                    <?php
                    // Get featured brands from marcas category
                    if (function_exists('ovc_get_marcas_subcategories')) {
                        $marcas = ovc_get_marcas_subcategories(array('number' => 12));
                        foreach ($marcas as $marca) :
                            $thumbnail_id = get_term_meta($marca['term_id'], 'thumbnail_id', true);
                            $image_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : '';
                            ?>
                            <div class="brand-item">
                                <a href="<?php echo esc_url($marca['url']); ?>" 
                                   aria-label="<?php echo esc_attr(sprintf(__('Ver productos de %s', 'opticavision-theme'), $marca['name'])); ?>">
                                    <?php if ($image_url) : ?>
                                        <img src="<?php echo esc_url($image_url); ?>" 
                                             alt="<?php echo esc_attr($marca['name']); ?>"
                                             loading="lazy">
                                    <?php else : ?>
                                        <span class="brand-name"><?php echo esc_html($marca['name']); ?></span>
                                    <?php endif; ?>
                                </a>
                            </div>
                        <?php endforeach;
                    } else {
                        // Fallback brands if function not available
                        $fallback_brands = array('Ray-Ban', 'Oakley', 'Persol', 'Prada', 'Gucci', 'Versace');
                        foreach ($fallback_brands as $brand) :
                            ?>
                            <div class="brand-item">
                                <span class="brand-name"><?php echo esc_html($brand); ?></span>
                            </div>
                        <?php endforeach;
                    }
                    ?>
                </div>
                
                <button class="carousel-nav-btn carousel-prev" aria-label="<?php esc_attr_e('Marcas anteriores', 'opticavision-theme'); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15,18 9,12 15,6"></polyline>
                    </svg>
                </button>
                <button class="carousel-nav-btn carousel-next" aria-label="<?php esc_attr_e('Siguientes marcas', 'opticavision-theme'); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9,18 15,12 9,6"></polyline>
                    </svg>
                </button>
            </div>
        </div>
    </section>

    -->

    <!-- Promotional Banners 
    <section class="homepage-section banners-section">
        <div class="container">
            <div class="promotional-banners">
                <?php
                $promo_banners = get_theme_mod('promotional_banners', array());
                if (empty($promo_banners)) {
                    // Default banners
                    $promo_banners = array(
                        array(
                            'image' => OPTICAVISION_THEME_URI . '/assets/images/promo-1.jpg',
                            'title' => __('Ofertas Especiales', 'opticavision-theme'),
                            'description' => __('Hasta 30% de descuento en armazones seleccionados', 'opticavision-theme'),
                            'cta_text' => __('Ver Ofertas', 'opticavision-theme'),
                            'cta_url' => add_query_arg('on_sale', '1', wc_get_page_permalink('shop'))
                        ),
                        array(
                            'image' => OPTICAVISION_THEME_URI . '/assets/images/promo-2.jpg',
                            'title' => __('Examen Visual Gratuito', 'opticavision-theme'),
                            'description' => __('Agenda tu cita y cuida tu salud visual', 'opticavision-theme'),
                            'cta_text' => __('Agendar Cita', 'opticavision-theme'),
                            'cta_url' => get_permalink(get_page_by_path('contacto'))
                        )
                    );
                }
                
                foreach ($promo_banners as $banner) :
                    ?>
                    <a href="<?php echo esc_url($banner['cta_url']); ?>" class="promo-banner"
                       style="background-image: url('<?php echo esc_url($banner['image']); ?>');">
                        <div class="promo-content">
                            <h3 class="promo-title"><?php echo esc_html($banner['title']); ?></h3>
                            <p class="promo-description"><?php echo esc_html($banner['description']); ?></p>
                            <span class="promo-cta"><?php echo esc_html($banner['cta_text']); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    -->

    <!-- Latest Products Carousel -->
    <section class="homepage-section products-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><?php esc_html_e('Últimos Productos', 'opticavision-theme'); ?></h2>
                
            </div>
            
            <?php echo do_shortcode('[opticavision_products_carousel type="latest" limit="8"]'); ?>
        </div>
    </section>

    <!-- Contact Lenses Carousel -->
    <section class="homepage-section products-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><?php esc_html_e('Lentes de Contacto', 'opticavision-theme'); ?></h2>
              
            </div>
            
            <?php echo do_shortcode('[opticavision_products_carousel type="contact_lenses" limit="6"]'); ?>
        </div>
    </section>

    <!-- Sunglasses Carousel -->
    <section class="homepage-section products-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><?php esc_html_e('Lentes de Sol', 'opticavision-theme'); ?></h2>
              
            </div>
            
            <?php echo do_shortcode('[opticavision_products_carousel type="sunglasses" limit="6"]'); ?>
        </div>
    </section>

    <!-- Frames Carousel -->
    <section class="homepage-section products-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><?php esc_html_e('Armazones', 'opticavision-theme'); ?></h2>
              
            </div>
            
            <?php echo do_shortcode('[opticavision_products_carousel type="frames" limit="6"]'); ?>
        </div>
    </section>

  

</main>


<script>
jQuery(document).ready(function($) {
    // Newsletter form submission
    $('#newsletter-form').on('submit', function(e) {
        e.preventDefault();
        
        const email = $('#newsletter-email').val();
        const $button = $(this).find('button[type="submit"]');
        const originalText = $button.text();
        
        $button.text('<?php esc_html_e("Suscribiendo...", "opticavision-theme"); ?>').prop('disabled', true);
        
        $.ajax({
            url: opticavision_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'opticavision_newsletter_signup',
                email: email,
                nonce: opticavision_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php esc_html_e("¡Gracias por suscribirte!", "opticavision-theme"); ?>');
                    $('#newsletter-email').val('');
                } else {
                    alert(response.data || '<?php esc_html_e("Error al suscribirse. Inténtalo de nuevo.", "opticavision-theme"); ?>');
                }
            },
            error: function() {
                alert('<?php esc_html_e("Error al suscribirse. Inténtalo de nuevo.", "opticavision-theme"); ?>');
            },
            complete: function() {
                $button.text(originalText).prop('disabled', false);
            }
        });
    });
});
</script>

<?php get_footer(); ?>
