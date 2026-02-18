<?php
/**
 * Carousel functionality for OpticaVision Theme
 *
 * @package OpticaVision_Theme
 */

defined('ABSPATH') || exit;

class OpticaVision_Carousel {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_carousel_scripts'));
        add_shortcode('opticavision_products_carousel', array($this, 'products_carousel_shortcode'));
        add_action('wp_ajax_opticavision_load_carousel_products', array($this, 'load_carousel_products'));
        add_action('wp_ajax_nopriv_opticavision_load_carousel_products', array($this, 'load_carousel_products'));
    }

    /**
     * Enqueue carousel scripts
     */
    public function enqueue_carousel_scripts() {
        wp_enqueue_script(
            'opticavision-carousel',
            OPTICAVISION_THEME_URI . '/assets/js/carousel.js',
            array('jquery'),
            OPTICAVISION_THEME_VERSION,
            true
        );
    }

    /**
     * Products carousel shortcode
     */
    public function products_carousel_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'latest',
            'limit' => 8,
            'category' => '',
            'class' => '',
        ), $atts, 'opticavision_products_carousel');

        $products = $this->get_carousel_products($atts['type'], $atts['limit'], $atts['category']);
        
        if (empty($products)) {
            return '<div class="carousel-debug"><p>' . esc_html__('No se encontraron productos para el tipo: ', 'opticavision-theme') . esc_html($atts['type']) . '</p><p>Intentando mostrar cualquier producto disponible...</p></div>';
        }

        $carousel_id = 'carousel-' . uniqid();
        
        ob_start();
        ?>
        <div class="products-carousel <?php echo esc_attr($atts['class']); ?>" id="<?php echo esc_attr($carousel_id); ?>">
            <div class="products-track">
                <?php foreach ($products as $product) : ?>
                    <div class="product-carousel-item">
                        <?php $this->render_product_card($product); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button class="carousel-nav-btn carousel-prev" 
                    aria-label="<?php esc_attr_e('Productos anteriores', 'opticavision-theme'); ?>"
                    data-carousel="<?php echo esc_attr($carousel_id); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
            </button>
            <button class="carousel-nav-btn carousel-next" 
                    aria-label="<?php esc_attr_e('Siguientes productos', 'opticavision-theme'); ?>"
                    data-carousel="<?php echo esc_attr($carousel_id); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9,18 15,12 9,6"></polyline>
                </svg>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get products for carousel
     * Filtra productos sin imagen y sin stock
     */
    private function get_carousel_products($type, $limit, $category = '') {
        // Use WooCommerce function for better compatibility
        $args = array(
            'status' => 'publish',
            'limit' => $limit * 3, // Obtenemos más para filtrar
            'visibility' => 'catalog',
            // No filtrar por stock aquí para permitir productos variables
        );

        // Set query based on type
        switch ($type) {
            case 'latest':
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
                
            case 'featured':
                $args['featured'] = true;
                break;
                
            case 'on_sale':
                $args['on_sale'] = true;
                break;
                
            case 'contact_lenses':
                $args['category'] = array('lentes-de-contacto');
                break;
                
            case 'sunglasses':
                $args['category'] = array('lentes-de-sol');
                break;
                
            case 'frames':
                $args['category'] = array('armazon');
                break;
        }

        // Add custom category if specified
        if (!empty($category)) {
            $args['category'] = array($category);
        }

        // Use WooCommerce function to get products
        $all_products = wc_get_products($args);
        
        // Filtrar productos según configuración del plugin Stock Control
        $filtered_products = array();
        
        foreach ($all_products as $product) {
            // Aplicar filtro de visibilidad (respeta plugin stock control)
            if (apply_filters('woocommerce_product_is_visible', true, $product->get_id())) {
                $filtered_products[] = $product;
                
                // Si ya tenemos suficientes productos, salir
                if (count($filtered_products) >= $limit) {
                    break;
                }
            }
        }

        // If no products found, try to get any products as fallback
        if (empty($filtered_products) && $type !== 'latest') {
            $fallback_args = array(
                'status' => 'publish',
                'limit' => $limit * 3, // Obtener más para compensar filtros
                'visibility' => 'catalog',
                'orderby' => 'date',
                'order' => 'DESC'
            );
            $all_products = wc_get_products($fallback_args);
            
            // Filtrar también el fallback
            foreach ($all_products as $product) {
                if (apply_filters('woocommerce_product_is_visible', true, $product->get_id())) {
                    $filtered_products[] = $product;
                    
                    if (count($filtered_products) >= $limit) {
                        break;
                    }
                }
            }
        }

        return $filtered_products;
    }

    /**
     * Render product card
     */
    private function render_product_card($product) {
        // Use the new product card function
        echo opticavision_product_card($product->get_id(), array(
            'show_badges' => true,
            'show_category' => true,
            'show_actions' => true,
            'class' => 'carousel-product-card'
        ));
    }
    
    /**
     * Render product card (legacy method - kept for compatibility)
     */
    private function render_product_card_legacy($product) {
        $product_id = $product->get_id();
        $product_url = get_permalink($product_id);
        $product_image = wp_get_attachment_image_src(get_post_thumbnail_id($product_id), 'medium');
        $product_image_url = $product_image ? $product_image[0] : wc_placeholder_img_src();
        
        // Get product categories
        $categories = wp_get_post_terms($product_id, 'product_cat');
        $category_name = !empty($categories) ? $categories[0]->name : '';
        
        // Check if product is on sale
        $is_on_sale = $product->is_on_sale();
        $regular_price = $product->get_regular_price();
        $sale_price = $product->get_sale_price();
        
        ?>
        <div class="product-carousel-card">
            <div class="product-carousel-image">
                <a href="<?php echo esc_url($product_url); ?>">
                    <img src="<?php echo esc_url($product_image_url); ?>" 
                         alt="<?php echo esc_attr($product->get_name()); ?>"
                         loading="lazy">
                </a>
                
                <?php if ($is_on_sale) : ?>
                    <span class="product-badge sale-badge">
                        <?php esc_html_e('Oferta', 'opticavision-theme'); ?>
                    </span>
                <?php elseif ($product->is_featured()) : ?>
                    <span class="product-badge featured-badge">
                        <?php esc_html_e('Destacado', 'opticavision-theme'); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="product-carousel-info">
                <?php if ($category_name) : ?>
                    <div class="product-carousel-category">
                        <?php echo esc_html($category_name); ?>
                    </div>
                <?php endif; ?>
                
                <h3 class="product-carousel-title">
                    <a href="<?php echo esc_url($product_url); ?>">
                        <?php echo esc_html($product->get_name()); ?>
                    </a>
                </h3>
                
                <div class="product-carousel-price">
                    <?php if ($is_on_sale && $sale_price) : ?>
                        <span class="sale-price">
                            <?php echo wc_price($sale_price); ?>
                        </span>
                        <span class="regular-price">
                            <?php echo wc_price($regular_price); ?>
                        </span>
                    <?php else : ?>
                        <?php echo $product->get_price_html(); ?>
                    <?php endif; ?>
                </div>
                
                <div class="product-carousel-actions">
                    <button class="quick-view-btn" 
                            data-product-id="<?php echo esc_attr($product_id); ?>"
                            aria-label="<?php esc_attr_e('Vista rápida', 'opticavision-theme'); ?>">
                        <?php esc_html_e('Vista Rápida', 'opticavision-theme'); ?>
                    </button>
                    
                    <?php if ($product->is_purchasable() && $product->is_in_stock()) : ?>
                        <button class="add-to-cart-carousel-btn" 
                                data-product-id="<?php echo esc_attr($product_id); ?>"
                                data-product-sku="<?php echo esc_attr($product->get_sku()); ?>">
                            <?php esc_html_e('Agregar al Carrito', 'opticavision-theme'); ?>
                        </button>
                    <?php else : ?>
                        <span class="out-of-stock-notice">
                            <?php esc_html_e('Agotado', 'opticavision-theme'); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for loading carousel products
     */
    public function load_carousel_products() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'opticavision_nonce')) {
            wp_die(__('Error de seguridad', 'opticavision-theme'));
        }

        $type = sanitize_text_field($_POST['type']);
        $limit = absint($_POST['limit']);
        $category = sanitize_text_field($_POST['category']);

        $products = $this->get_carousel_products($type, $limit, $category);
        
        if (empty($products)) {
            wp_send_json_error(__('No se encontraron productos.', 'opticavision-theme'));
        }

        ob_start();
        foreach ($products as $product) {
            echo '<div class="product-carousel-item">';
            $this->render_product_card($product);
            echo '</div>';
        }
        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html,
            'count' => count($products)
        ));
    }

    /**
     * Get carousel navigation HTML
     */
    public static function get_carousel_navigation($carousel_id) {
        ob_start();
        ?>
        <button class="carousel-nav-btn carousel-prev" 
                data-carousel="<?php echo esc_attr($carousel_id); ?>"
                aria-label="<?php esc_attr_e('Anterior', 'opticavision-theme'); ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15,18 9,12 15,6"></polyline>
            </svg>
        </button>
        <button class="carousel-nav-btn carousel-next" 
                data-carousel="<?php echo esc_attr($carousel_id); ?>"
                aria-label="<?php esc_attr_e('Siguiente', 'opticavision-theme'); ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9,18 15,12 9,6"></polyline>
            </svg>
        </button>
        <?php
        return ob_get_clean();
    }
}
