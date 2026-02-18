<?php
/**
 * WooCommerce Integration for OpticaVision Theme
 *
 * @package OpticaVision_Theme
 */

defined('ABSPATH') || exit;

class OpticaVision_WooCommerce {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('after_setup_theme', array($this, 'setup'));
        add_filter('woocommerce_enqueue_styles', array($this, 'disable_default_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_woocommerce_styles'));
        
        // AJAX handlers
        add_action('wp_ajax_opticavision_get_cart_count', array($this, 'get_cart_count'));
        add_action('wp_ajax_nopriv_opticavision_get_cart_count', array($this, 'get_cart_count'));
        add_action('wp_ajax_opticavision_quick_view', array($this, 'quick_view'));
        add_action('wp_ajax_nopriv_opticavision_quick_view', array($this, 'quick_view'));
        add_action('wp_ajax_opticavision_newsletter_signup', array($this, 'newsletter_signup'));
        add_action('wp_ajax_nopriv_opticavision_newsletter_signup', array($this, 'newsletter_signup'));
        
        // WooCommerce hooks
        add_filter('woocommerce_add_to_cart_fragments', array($this, 'cart_link_fragment'));
        add_action('woocommerce_before_shop_loop_item_title', array($this, 'add_product_hover_effects'), 5);
        add_filter('woocommerce_loop_product_link_classes', array($this, 'product_link_classes'));
        
        // Remove default WooCommerce actions
        remove_action('woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10);
        remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5);
        
        // Remove duplicate breadcrumbs - theme has its own breadcrumb system
        remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
        
        // Remove wishlist buttons from product loops
        add_action('init', array($this, 'remove_wishlist_buttons'));
    }

    /**
     * Setup WooCommerce integration
     */
    public function setup() {
        add_theme_support('wc-product-gallery-zoom');
        add_theme_support('wc-product-gallery-lightbox');
        add_theme_support('wc-product-gallery-slider');
    }

    /**
     * Disable default WooCommerce styles
     */
    public function disable_default_styles($enqueue_styles) {
        unset($enqueue_styles['woocommerce-general']);
        unset($enqueue_styles['woocommerce-layout']);
        unset($enqueue_styles['woocommerce-smallscreen']);
        return $enqueue_styles;
    }

    /**
     * Enqueue custom WooCommerce styles
     */
    public function enqueue_woocommerce_styles() {
        if (function_exists('is_woocommerce') && (is_woocommerce() || is_cart() || is_checkout() || is_account_page())) {
            wp_enqueue_style(
                'opticavision-woocommerce',
                OPTICAVISION_THEME_URI . '/assets/css/woocommerce.css',
                array('opticavision-main'),
                OPTICAVISION_THEME_VERSION . '.3'
            );
            
            // Enqueue shop layout styles for shop and category pages
            if (is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy()) {
                wp_enqueue_style(
                    'opticavision-woocommerce-shop',
                    OPTICAVISION_THEME_URI . '/assets/css/woocommerce-shop.css',
                    array('opticavision-woocommerce'),
                    OPTICAVISION_THEME_VERSION
                );
            }
        }
    }

    /**
     * AJAX handler for getting cart count
     */
    public function get_cart_count() {
        if (!wp_verify_nonce($_POST['nonce'], 'opticavision_nonce')) {
            wp_die(__('Error de seguridad', 'opticavision-theme'));
        }

        $count = WC()->cart->get_cart_contents_count();
        
        wp_send_json_success(array(
            'count' => $count,
            'total' => WC()->cart->get_cart_total()
        ));
    }

    /**
     * AJAX handler for quick view
     */
    public function quick_view() {
        if (!wp_verify_nonce($_POST['nonce'], 'opticavision_nonce')) {
            wp_die(__('Error de seguridad', 'opticavision-theme'));
        }

        $product_id = absint($_POST['product_id']);
        $product = wc_get_product($product_id);

        if (!$product) {
            wp_send_json_error(__('Producto no encontrado', 'opticavision-theme'));
        }

        ob_start();
        $this->render_quick_view_content($product);
        $html = ob_get_clean();

        wp_send_json_success($html);
    }

    /**
     * Render quick view content
     */
    private function render_quick_view_content($product) {
        ?>
        <div class="quick-view-product">
            <div class="quick-view-images">
                <?php
                $main_image = wp_get_attachment_image_src(get_post_thumbnail_id($product->get_id()), 'medium');
                
                if ($main_image) {
                    echo '<img src="' . esc_url($main_image[0]) . '" alt="' . esc_attr($product->get_name()) . '">';
                }
                ?>
            </div>
            
            <div class="quick-view-details">
                <h2 class="product-title"><?php echo esc_html($product->get_name()); ?></h2>
                
                <div class="product-price">
                    <?php echo $product->get_price_html(); ?>
                </div>
                
                <?php if ($product->get_short_description()) : ?>
                    <div class="product-description">
                        <?php echo wp_kses_post($product->get_short_description()); ?>
                    </div>
                <?php endif; ?>
                
                <div class="product-meta">
                    <?php if ($product->get_sku()) : ?>
                        <div class="meta-item">
                            <strong><?php esc_html_e('SKU:', 'opticavision-theme'); ?></strong>
                            <span><?php echo esc_html($product->get_sku()); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    $categories = wp_get_post_terms($product->get_id(), 'product_cat');
                    if (!empty($categories)) :
                    ?>
                        <div class="meta-item">
                            <strong><?php esc_html_e('Categoría:', 'opticavision-theme'); ?></strong>
                            <span><?php echo esc_html($categories[0]->name); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($product->is_purchasable() && $product->is_in_stock()) : ?>
                    <form class="cart" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post" enctype='multipart/form-data'>
                        <div class="quantity-wrapper">
                            <label for="quantity"><?php esc_html_e('Cantidad:', 'opticavision-theme'); ?></label>
                            <input type="number" id="quantity" class="quantity-input" name="quantity" value="1" min="1" max="<?php echo esc_attr($product->get_max_purchase_quantity()); ?>">
                        </div>
                        
                        <button type="submit" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" class="single_add_to_cart_button">
                            <?php esc_html_e('AGREGAR AL CARRITO', 'opticavision-theme'); ?>
                        </button>
                    </form>
                <?php else : ?>
                    <p class="stock out-of-stock"><?php esc_html_e('Producto agotado', 'opticavision-theme'); ?></p>
                <?php endif; ?>
                
                <div class="quick-view-actions">
                    <a href="<?php echo esc_url($product->get_permalink()); ?>" class="view-full-details">
                        <?php esc_html_e('Ver detalles completos', 'opticavision-theme'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for newsletter signup
     */
    public function newsletter_signup() {
        if (!wp_verify_nonce($_POST['nonce'], 'opticavision_nonce')) {
            wp_die(__('Error de seguridad', 'opticavision-theme'));
        }

        $email = sanitize_email($_POST['email']);
        
        if (!is_email($email)) {
            wp_send_json_error(__('Email inválido', 'opticavision-theme'));
        }

        // Check if email already exists
        $existing = get_option('opticavision_newsletter_subscribers', array());
        
        if (in_array($email, $existing)) {
            wp_send_json_error(__('Este email ya está suscrito', 'opticavision-theme'));
        }

        // Add email to subscribers
        $existing[] = $email;
        update_option('opticavision_newsletter_subscribers', $existing);

        // Log subscription if logger is available
        if (function_exists('opticavision_log')) {
            opticavision_log('[NEWSLETTER] Nueva suscripción: ' . $email);
        }

        wp_send_json_success(__('¡Suscripción exitosa!', 'opticavision-theme'));
    }

    /**
     * Update cart fragments
     */
    public function cart_link_fragment($fragments) {
        $count = WC()->cart->get_cart_contents_count();
        
        ob_start();
        ?>
        <span class="cart-count" <?php echo $count > 0 ? '' : 'style="display: none;"'; ?>>
            <?php echo esc_html($count); ?>
        </span>
        <?php
        $fragments['.cart-count'] = ob_get_clean();

        return $fragments;
    }

    /**
     * Add product hover effects
     */
    public function add_product_hover_effects() {
        echo '<div class="product-hover-overlay">';
    }

    /**
     * Product title classes
     */
    public function product_title_classes($classes) {
        return $classes . ' product-loop-title';
    }

    /**
     * Product link classes
     */
    public function product_link_classes($classes) {
        return $classes . ' product-loop-link';
    }

    /**
     * Custom product link open
     */
    public function custom_product_link_open() {
        global $product;
        echo '<div class="product-item-wrapper">';
        echo '<a href="' . esc_url($product->get_permalink()) . '" class="woocommerce-LoopProduct-link woocommerce-loop-product__link">';
    }

    /**
     * Custom product link close
     */
    public function custom_product_link_close() {
        echo '</a>';
        echo '</div>';
    }

    /**
     * Add product badges
     */
    public function add_product_badges() {
        global $product;
        
        echo '<div class="product-badges">';
        
        if ($product->is_on_sale()) {
            echo '<span class="product-badge sale-badge">' . esc_html__('Oferta', 'opticavision-theme') . '</span>';
        }
        
        if ($product->is_featured()) {
            echo '<span class="product-badge featured-badge">' . esc_html__('Destacado', 'opticavision-theme') . '</span>';
        }
        
        // Badge de sin stock - SOLO para productos simples
        if (!$product->is_in_stock() && !$product->is_type('variable')) {
            echo '<span class="product-badge out-of-stock-badge">' . esc_html__('Agotado', 'opticavision-theme') . '</span>';
        }
        
        echo '</div>';
    }

    /**
     * Custom product title
     */
    public function custom_product_title() {
        global $product;
        echo '<h2 class="woocommerce-loop-product__title">' . esc_html($product->get_name()) . '</h2>';
    }

    /**
     * Add product category
     */
    public function add_product_category() {
        global $product;
        
        $categories = wp_get_post_terms($product->get_id(), 'product_cat');
        if (!empty($categories)) {
            echo '<div class="product-category">' . esc_html($categories[0]->name) . '</div>';
        }
    }

    /**
     * Add quick view button
     */
    public function add_quick_view_button() {
        global $product;
        
        echo '<div class="product-actions">';
        echo '<button class="quick-view-btn" data-product-id="' . esc_attr($product->get_id()) . '">';
        echo esc_html__('Vista Rápida', 'opticavision-theme');
        echo '</button>';
        echo '</div>';
    }

    /**
     * Customize checkout fields
     */
    public function customize_checkout_fields($fields) {
        // Reorder billing fields
        $fields['billing']['billing_first_name']['priority'] = 10;
        $fields['billing']['billing_last_name']['priority'] = 20;
        $fields['billing']['billing_email']['priority'] = 30;
        $fields['billing']['billing_phone']['priority'] = 40;
        $fields['billing']['billing_country']['priority'] = 50;
        $fields['billing']['billing_state']['priority'] = 60;
        $fields['billing']['billing_city']['priority'] = 70;
        $fields['billing']['billing_address_1']['priority'] = 80;
        $fields['billing']['billing_address_2']['priority'] = 90;
        $fields['billing']['billing_postcode']['priority'] = 100;

        // Make phone required
        $fields['billing']['billing_phone']['required'] = true;

        // Add custom placeholders
        $fields['billing']['billing_first_name']['placeholder'] = __('Nombre', 'opticavision-theme');
        $fields['billing']['billing_last_name']['placeholder'] = __('Apellido', 'opticavision-theme');
        $fields['billing']['billing_email']['placeholder'] = __('Correo electrónico', 'opticavision-theme');
        $fields['billing']['billing_phone']['placeholder'] = __('Teléfono', 'opticavision-theme');

        return $fields;
    }

    /**
     * Add checkout security notice
     */
    public function add_checkout_security_notice() {
        ?>
        <div class="checkout-security-notice">
            <div class="security-icons">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                <span><?php esc_html_e('Compra 100% segura', 'opticavision-theme'); ?></span>
            </div>
            <p><?php esc_html_e('Tus datos están protegidos con encriptación SSL', 'opticavision-theme'); ?></p>
        </div>
        <?php
    }

    /**
     * Get default menu fallback
     */
    public static function default_menu_fallback() {
        echo '<ul class="nav-menu">';
        echo '<li><a href="' . esc_url(home_url('/')) . '">' . esc_html__('Inicio', 'opticavision-theme') . '</a></li>';
        
        if (function_exists('wc_get_page_permalink')) {
            echo '<li><a href="' . esc_url(wc_get_page_permalink('shop')) . '">' . esc_html__('Tienda', 'opticavision-theme') . '</a></li>';
        }
        
        echo '<li><a href="' . esc_url(get_permalink(get_page_by_path('sobre-nosotros'))) . '">' . esc_html__('Sobre Nosotros', 'opticavision-theme') . '</a></li>';
        echo '<li><a href="' . esc_url(get_permalink(get_page_by_path('contacto'))) . '">' . esc_html__('Contacto', 'opticavision-theme') . '</a></li>';
        echo '</ul>';
    }

    /**
     * Customize pagination arguments for better UX
     */
    public function customize_pagination_args($args) {
        $args['mid_size'] = 2; // Show 2 pages to either side of current page
        $args['end_size'] = 1; // Show 1 page at the beginning and end
        $args['prev_text'] = '‹';
        $args['next_text'] = '›';
        return $args;
    }

    /**
     * Remove wishlist buttons from product loops
     */
    public function remove_wishlist_buttons() {
        // Remove YITH Wishlist hooks
        if (class_exists('YITH_WCWL')) {
            remove_action('woocommerce_after_shop_loop_item', array(YITH_WCWL_Frontend(), 'print_button'), 7);
            remove_action('woocommerce_after_shop_loop_item', array(YITH_WCWL_Frontend(), 'print_button'), 15);
            remove_action('woocommerce_after_single_product_summary', array(YITH_WCWL_Frontend(), 'print_button'), 25);
            remove_action('woocommerce_single_product_summary', array(YITH_WCWL_Frontend(), 'print_button'), 31);
        }

        // Remove TI WooCommerce Wishlist hooks
        if (class_exists('TInvWL_Public_WishList')) {
            remove_action('woocommerce_after_shop_loop_item', 'tinvwl_view_addto_html', 9);
            remove_action('woocommerce_after_shop_loop_item', 'tinvwl_view_addto_htmlloop', 10);
            remove_action('woocommerce_after_single_product_summary', 'tinvwl_view_addto_html', 21);
            remove_action('woocommerce_single_product_summary', 'tinvwl_view_addto_html', 31);
        }

        // Remove WooCommerce Wishlist hooks
        if (class_exists('WC_Wishlist')) {
            remove_action('woocommerce_after_shop_loop_item', array('WC_Wishlist', 'add_button'), 15);
            remove_action('woocommerce_single_product_summary', array('WC_Wishlist', 'add_button'), 31);
        }

        // Generic removal for other wishlist plugins
        remove_all_actions('woocommerce_after_shop_loop_item', 15);
        remove_all_actions('woocommerce_single_product_summary', 31);

        // Remove compare buttons too
        if (class_exists('YITH_Woocompare_Frontend')) {
            remove_action('woocommerce_after_shop_loop_item', array(YITH_Woocompare_Frontend(), 'add_compare_link'), 20);
            remove_action('woocommerce_single_product_summary', array(YITH_Woocompare_Frontend(), 'add_compare_link'), 35);
        }

        // Log removal if logger is available
        if (function_exists('opticavision_log')) {
            opticavision_log('[WISHLIST] Wishlist and compare buttons removed from product loops', 'info');
        }
    }
}
