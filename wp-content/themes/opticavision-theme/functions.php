<?php
/**
 * OpticaVision Theme Functions
 * 
 * @package OpticaVision_Theme
 * @version 1.0.0
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

// Define theme constants
define('OPTICAVISION_THEME_VERSION', '1.0.2'); // Forzar recarga de caché - Hero slider altura automática
define('OPTICAVISION_THEME_DIR', get_template_directory());
define('OPTICAVISION_THEME_URI', get_template_directory_uri());

/**
 * WooCommerce Blocks - DESACTIVADO
 * Ahora usamos checkout clásico con templates personalizados
 */

/**
 * Forzar uso de checkout clásico (desactivar Blocks completamente)
 */
add_filter('woocommerce_feature_enabled', 'optica_vision_disable_blocks_features', 10, 2);
function optica_vision_disable_blocks_features($enabled, $feature) {
    // Desactivar todas las features de Blocks
    if (in_array($feature, ['cart_checkout_blocks', 'experimental_blocks'])) {
        return false;
    }
    return $enabled;
}

// Desactivar completamente WooCommerce Blocks
add_action('after_setup_theme', 'optica_vision_remove_blocks_support', 100);
function optica_vision_remove_blocks_support() {
    remove_theme_support('wc-product-gallery-zoom');
    remove_theme_support('wc-product-gallery-lightbox');
    remove_theme_support('wc-product-gallery-slider');
}

// Forzar shortcodes clásicos en lugar de bloques
add_filter('woocommerce_create_pages', 'optica_vision_force_classic_pages');
function optica_vision_force_classic_pages($pages) {
    if (isset($pages['cart'])) {
        $pages['cart']['content'] = '[woocommerce_cart]';
    }
    if (isset($pages['checkout'])) {
        $pages['checkout']['content'] = '[woocommerce_checkout]';
    }
    return $pages;
}

// Convertir páginas de Blocks a shortcodes (ejecutar una sola vez)
add_action('admin_init', 'optica_vision_convert_pages_to_shortcodes');
function optica_vision_convert_pages_to_shortcodes() {
    // Solo ejecutar si no se ha hecho antes
    if (get_option('optica_vision_pages_converted')) {
        return;
    }
    
    // Convertir página de Carrito
    $cart_page = get_page_by_path('carrito');
    if (!$cart_page) {
        $cart_page = get_page_by_path('cart');
    }
    if ($cart_page && strpos($cart_page->post_content, 'wp:woocommerce/cart') !== false) {
        wp_update_post(array(
            'ID' => $cart_page->ID,
            'post_content' => '[woocommerce_cart]'
        ));
    }
    
    // Convertir página de Checkout
    $checkout_page = get_page_by_path('finalizar-compra');
    if (!$checkout_page) {
        $checkout_page = get_page_by_path('checkout');
    }
    if ($checkout_page && strpos($checkout_page->post_content, 'wp:woocommerce/checkout') !== false) {
        wp_update_post(array(
            'ID' => $checkout_page->ID,
            'post_content' => '[woocommerce_checkout]'
        ));
    }
    
    // Marcar como convertido
    update_option('optica_vision_pages_converted', true);
}

/**
 * Theme Setup
 */
function opticavision_theme_setup() {
    // Add theme support for various features
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script'
    ));
    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 300,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    add_theme_support('customize-selective-refresh-widgets');
    add_theme_support('responsive-embeds');
    
    // Soporte para WooCommerce Blocks
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    
    // Soporte para bloques de Gutenberg y WooCommerce
    add_theme_support('align-wide');
    add_theme_support('wp-block-styles');
    add_theme_support('editor-styles');

    // WooCommerce support
    add_theme_support('woocommerce', array(
        'thumbnail_image_width' => 300,
        'single_image_width'    => 600,
        'product_grid'          => array(
            'default_rows'    => 3,
            'min_rows'        => 2,
            'max_rows'        => 8,
            'default_columns' => 4,
            'min_columns'     => 2,
            'max_columns'     => 5,
        ),
    ));
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');

    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Menú Principal', 'opticavision-theme'),
        'footer'  => __('Menú Footer', 'opticavision-theme'),
        'mobile'  => __('Menú Móvil', 'opticavision-theme'),
    ));

    // Set content width
    if (!isset($content_width)) {
        $content_width = 1200;
    }

    // Load text domain
    load_theme_textdomain('opticavision-theme', OPTICAVISION_THEME_DIR . '/languages');
}
add_action('after_setup_theme', 'opticavision_theme_setup');

/**
 * Enqueue scripts and styles
 */
function opticavision_theme_scripts() {
    // Font Awesome con atributo crossorigin
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        array(),
        '6.4.0'
    );
    
    // Agregar atributo crossorigin a Font Awesome
    add_filter('style_loader_tag', function($html, $handle) {
        if ($handle === 'font-awesome') {
            $html = str_replace("rel='stylesheet'", "rel='stylesheet' crossorigin='anonymous'", $html);
        }
        return $html;
    }, 10, 2);

    // Google Fonts - Fira Sans
    wp_enqueue_style(
        'opticavision-google-fonts',
        'https://fonts.googleapis.com/css2?family=Fira+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap',
        array(),
        null
    );

    // Main stylesheet
    wp_enqueue_style(
        'opticavision-style',
        get_stylesheet_uri(),
        array('opticavision-google-fonts'),
        OPTICAVISION_THEME_VERSION
    );

    // Additional stylesheets
    wp_enqueue_style(
        'opticavision-main',
        OPTICAVISION_THEME_URI . '/assets/css/main.css',
        array('opticavision-style'),
        OPTICAVISION_THEME_VERSION
    );

    // Hero Slider CSS
    wp_enqueue_style(
        'opticavision-hero-slider',
        OPTICAVISION_THEME_URI . '/assets/css/hero-slider.css',
        array(),
        OPTICAVISION_THEME_VERSION
    );

    // Product Cards CSS
    wp_enqueue_style(
        'opticavision-product-cards',
        OPTICAVISION_THEME_URI . '/assets/css/product-cards.css',
        array(),
        OPTICAVISION_THEME_VERSION
    );

    // Carousel CSS
    wp_enqueue_style(
        'opticavision-carousel',
        OPTICAVISION_THEME_URI . '/assets/css/carousel.css',
        array(),
        OPTICAVISION_THEME_VERSION
    );

    // Megamenu CSS
    wp_enqueue_style(
        'opticavision-megamenu',
        OPTICAVISION_THEME_URI . '/assets/css/megamenu.css',
        array(),
        OPTICAVISION_THEME_VERSION
    );

    // Hero Slider JavaScript
    wp_enqueue_script(
        'opticavision-hero-slider',
        OPTICAVISION_THEME_URI . '/assets/js/hero-slider.js',
        array('jquery'),
        OPTICAVISION_THEME_VERSION,
        true
    );

    // Product Cards JavaScript
    wp_enqueue_script(
        'opticavision-product-cards',
        OPTICAVISION_THEME_URI . '/assets/js/product-cards.js',
        array('jquery'),
        OPTICAVISION_THEME_VERSION,
        true
    );

    // Main JavaScript
    wp_enqueue_script(
        'opticavision-main',
        OPTICAVISION_THEME_URI . '/assets/js/main.js',
        array('jquery', 'opticavision-hero-slider'),
        OPTICAVISION_THEME_VERSION,
        true
    );

    // Carousel functionality
    wp_enqueue_script(
        'opticavision-carousel',
        OPTICAVISION_THEME_URI . '/assets/js/carousel.js',
        array('jquery'),
        OPTICAVISION_THEME_VERSION,
        true
    );

    // Megamenu functionality
    wp_enqueue_script(
        'opticavision-megamenu',
        OPTICAVISION_THEME_URI . '/assets/js/megamenu.js',
        array('jquery'),
        OPTICAVISION_THEME_VERSION,
        true
    );

    // Localize script for AJAX with WooCommerce translations
    wp_localize_script('opticavision-main', 'opticavision_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('opticavision_nonce'),
        'strings'  => array(
            'loading' => __('Cargando...', 'opticavision-theme'),
            'error'   => __('Error al cargar el contenido', 'opticavision-theme'),
            // WooCommerce strings for JavaScript
            'add_to_cart' => __('Agregar al Carrito', 'opticavision-theme'),
            'added_to_cart' => __('Agregado al carrito', 'opticavision-theme'),
            'view_cart' => __('Ver Carrito', 'opticavision-theme'),
            'continue_shopping' => __('Seguir Comprando', 'opticavision-theme'),
            'quick_view' => __('Vista Rápida', 'opticavision-theme'),
            'close' => __('Cerrar', 'opticavision-theme'),
            'quantity' => __('Cantidad', 'opticavision-theme'),
            'in_stock' => __('En stock', 'opticavision-theme'),
            'out_of_stock' => __('Agotado', 'opticavision-theme'),
            'updating_cart' => __('Actualizando carrito...', 'opticavision-theme'),
            'cart_updated' => __('Carrito actualizado', 'opticavision-theme'),
            'remove_item' => __('Eliminar producto', 'opticavision-theme'),
            'confirm_remove' => __('¿Estás seguro de que deseas eliminar este producto?', 'opticavision-theme'),
        ),
    ));

    // Comment reply script
    if (is_singular() && comments_open() && get_option('thread_comments')) {
    }
}
add_action('wp_enqueue_scripts', 'opticavision_theme_scripts');

/**
 * Include required files
 */
require_once OPTICAVISION_THEME_DIR . '/includes/class-logger-wrapper.php';
require_once OPTICAVISION_THEME_DIR . '/includes/class-theme-setup.php';
require_once OPTICAVISION_THEME_DIR . '/includes/class-woocommerce.php';
require_once OPTICAVISION_THEME_DIR . '/includes/class-carousel.php';
require_once OPTICAVISION_THEME_DIR . '/includes/class-breadcrumbs.php';
require_once OPTICAVISION_THEME_DIR . '/includes/class-customizer.php';
require_once OPTICAVISION_THEME_DIR . '/includes/class-hero-slider.php';
require_once OPTICAVISION_THEME_DIR . '/includes/class-megamenu-walker.php';
require_once OPTICAVISION_THEME_DIR . '/includes/class-megamenu-admin.php';
require_once OPTICAVISION_THEME_DIR . '/includes/class-sucursales.php';
require_once OPTICAVISION_THEME_DIR . '/includes/class-optica-contact-form.php';
require_once OPTICAVISION_THEME_DIR . '/includes/class-optica-shipping-by-city.php';
require_once OPTICAVISION_THEME_DIR . '/includes/class-optica-city-field-customizer.php';
require_once OPTICAVISION_THEME_DIR . '/includes/product-card-functions.php';
require_once OPTICAVISION_THEME_DIR . '/includes/template-functions.php';
require_once OPTICAVISION_THEME_DIR . '/includes/ajax-handlers.php';

/**
 * Redirigir al carrito después de agregar un producto
 */
add_filter('woocommerce_add_to_cart_redirect', 'optica_vision_redirect_to_cart');
function optica_vision_redirect_to_cart() {
    return wc_get_cart_url();
}

/**
 * Initialize theme classes
 */
function opticavision_theme_init() {
    // Initialize classes only if they exist
    if (class_exists('OpticaVision_Theme_Setup')) {
        new OpticaVision_Theme_Setup();
    }
    
    if (class_exists('OpticaVision_Customizer')) {
        new OpticaVision_Customizer();
    }
    
    if (class_exists('OpticaVision_WooCommerce')) {
        new OpticaVision_WooCommerce();
    }
    
    if (class_exists('OpticaVision_Carousel')) {
        new OpticaVision_Carousel();
    }
    
    if (class_exists('OpticaVision_Breadcrumbs')) {
        new OpticaVision_Breadcrumbs();
    }
    
    if (class_exists('OpticaVision_Megamenu_Admin')) {
        new OpticaVision_Megamenu_Admin();
    }
    
    // Hero Slider is initialized automatically in its class file
}
add_action('init', 'opticavision_theme_init');

/**
 * Widget areas
 */
function opticavision_theme_widgets_init() {
    register_sidebar(array(
        'name'          => __('Sidebar Principal', 'opticavision-theme'),
        'id'            => 'sidebar-1',
        'description'   => __('Widgets para la barra lateral principal', 'opticavision-theme'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));

    register_sidebar(array(
        'name'          => __('Footer Columna 1', 'opticavision-theme'),
        'id'            => 'footer-1',
        'description'   => __('Widgets para la primera columna del footer', 'opticavision-theme'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));

    register_sidebar(array(
        'name'          => __('Footer Columna 2', 'opticavision-theme'),
        'id'            => 'footer-2',
        'description'   => __('Widgets para la segunda columna del footer', 'opticavision-theme'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));

    register_sidebar(array(
        'name'          => __('Footer Columna 3', 'opticavision-theme'),
        'id'            => 'footer-3',
        'description'   => __('Widgets para la tercera columna del footer', 'opticavision-theme'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));

    register_sidebar(array(
        'name'          => __('Footer Columna 4', 'opticavision-theme'),
        'id'            => 'footer-4',
        'description'   => __('Widgets para la cuarta columna del footer', 'opticavision-theme'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'opticavision_theme_widgets_init');

/**
 * Custom excerpt length
 */
function opticavision_theme_excerpt_length($length) {
    return 20;
}
add_filter('excerpt_length', 'opticavision_theme_excerpt_length');

/**
 * Custom excerpt more
 */
function opticavision_theme_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'opticavision_theme_excerpt_more');

/**
 * Add custom body classes
 */
function opticavision_theme_body_classes($classes) {
    // Add class for WooCommerce pages
    if (function_exists('is_woocommerce') && is_woocommerce()) {
        $classes[] = 'woocommerce-page';
    }

    // Add class for homepage
    if (is_front_page()) {
        $classes[] = 'homepage';
    }

    // Add class for mobile detection
    if (wp_is_mobile()) {
        $classes[] = 'mobile-device';
    }

    return $classes;
}
add_filter('body_class', 'opticavision_theme_body_classes');

/**
 * Add preconnect for Google Fonts
 */
function opticavision_theme_resource_hints($urls, $relation_type) {
    if (wp_style_is('opticavision-fonts', 'queue') && 'preconnect' === $relation_type) {
        $urls[] = array(
            'href' => 'https://fonts.gstatic.com',
            'crossorigin',
        );
    }

    return $urls;
}
add_filter('wp_resource_hints', 'opticavision_theme_resource_hints', 10, 2);

/**
 * Disable Gutenberg for certain post types if needed
 */
function opticavision_theme_disable_gutenberg($current_status, $post_type) {
    // Disable for products if needed
    if ($post_type === 'product') {
        return false;
    }
    return $current_status;
}
// Uncomment if needed
// add_filter('use_block_editor_for_post_type', 'opticavision_theme_disable_gutenberg', 10, 2);

/**
 * Optimize performance
 */
function opticavision_theme_performance_optimizations() {
    // Remove unnecessary WordPress features
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    
    // Remove emoji scripts
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
}
add_action('init', 'opticavision_theme_performance_optimizations');

/**
 * Security enhancements
 */
function opticavision_theme_security() {
    // Hide WordPress version
    remove_action('wp_head', 'wp_generator');
    
    // Remove version from scripts and styles
    function remove_version_scripts_styles($src) {
        if (strpos($src, 'ver=')) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }
    add_filter('style_loader_src', 'remove_version_scripts_styles', 9999);
    add_filter('script_loader_src', 'remove_version_scripts_styles', 9999);
}
add_action('init', 'opticavision_theme_security');

/**
 * Custom image sizes
 */
function opticavision_theme_image_sizes() {
    add_image_size('carousel-item', 400, 300, true);
    add_image_size('hero-slide', 1920, 800, true);
    add_image_size('product-thumb', 300, 300, true);
    add_image_size('product-large', 800, 800, true);
}
add_action('after_setup_theme', 'opticavision_theme_image_sizes');

/**
 * Compatibility with existing OpticaVision functionality
 */
function opticavision_theme_compatibility() {
    // Ensure compatibility with existing plugins and child theme functionality
    if (function_exists('optica_vision_logger')) {
        // Use existing logger if available
        function opticavision_log($message, $level = 'info') {
            if (function_exists('optica_log_' . $level)) {
                call_user_func('optica_log_' . $level, '[THEME] ' . $message);
            }
        }
    }

    // Maintain compatibility with existing mega menu functionality
    if (function_exists('ovc_get_marcas_subcategories')) {
        // Integrate with existing marcas system
        add_action('wp_enqueue_scripts', function() {
            wp_localize_script('opticavision-main', 'opticavision_marcas', array(
                'marcas' => ovc_get_marcas_subcategories(),
            ));
        });
    }
}
add_action('init', 'opticavision_theme_compatibility');

/**
 * Theme activation hook
 */
function opticavision_theme_activation() {
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Set default customizer options
    set_theme_mod('hero_slides', array());
    set_theme_mod('featured_brands', array());
    set_theme_mod('promotional_banners', array());
}
add_action('after_switch_theme', 'opticavision_theme_activation');

/**
 * Theme deactivation hook
 */
function opticavision_theme_deactivation() {
    // Clean up theme-specific options if needed
    flush_rewrite_rules();
}
add_action('switch_theme', 'opticavision_theme_deactivation');

/**
 * Check WooCommerce template compatibility
 * Verifica que los templates de WooCommerce estén actualizados
 */
function opticavision_check_woocommerce_template_compatibility() {
    if (!class_exists('WooCommerce')) {
        return false;
    }

    $templates_to_check = array(
        'archive-product.php' => '8.2.0',
        'single-product.php' => '8.2.0',
        'taxonomy-product_cat.php' => '8.2.0'
    );

    $theme_dir = get_template_directory();
    $woocommerce_dir = $theme_dir . '/woocommerce/';
    $outdated_templates = array();

    foreach ($templates_to_check as $template => $expected_version) {
        $template_path = $woocommerce_dir . $template;
        
        if (file_exists($template_path)) {
            $template_content = file_get_contents($template_path);
            
            // Buscar la línea @version en el template
            if (preg_match('/@version\s+([0-9.]+)/', $template_content, $matches)) {
                $current_version = $matches[1];
                
                if (version_compare($current_version, $expected_version, '<')) {
                    $outdated_templates[$template] = array(
                        'current' => $current_version,
                        'expected' => $expected_version
                    );
                }
            } else {
                $outdated_templates[$template] = array(
                    'current' => 'unknown',
                    'expected' => $expected_version
                );
            }
        }
    }

    // Log results if logger is available
    if (function_exists('opticavision_log')) {
        if (empty($outdated_templates)) {
            opticavision_log('[THEME] Todos los templates de WooCommerce están actualizados');
        } else {
            foreach ($outdated_templates as $template => $versions) {
                opticavision_log(sprintf(
                    '[THEME] Template obsoleto: %s (actual: %s, esperada: %s)',
                    $template,
                    $versions['current'],
                    $versions['expected']
                ));
            }
        }
    }

    return empty($outdated_templates) ? true : $outdated_templates;
}

/**
 * Add admin notice for outdated WooCommerce templates
 */
function opticavision_woocommerce_template_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $outdated = opticavision_check_woocommerce_template_compatibility();
    
    if (is_array($outdated) && !empty($outdated)) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>OpticaVision Theme:</strong> ' . 
             __('Se han detectado templates de WooCommerce que podrían necesitar actualización.', 'opticavision-theme') . '</p>';
        echo '<p>' . __('Revisa el archivo WOOCOMMERCE_COMPATIBILITY.md para más información.', 'opticavision-theme') . '</p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'opticavision_woocommerce_template_notice');

/**
 * Run template compatibility check on theme activation
 */
function opticavision_check_templates_on_activation() {
    opticavision_check_woocommerce_template_compatibility();
}
add_action('after_switch_theme', 'opticavision_check_templates_on_activation');

/**
 * Ensure WooCommerce templates are loaded correctly
 */
function opticavision_woocommerce_template_loader($template) {
    // Importante: NO interceptar páginas de archivo/tienda/categorías para permitir
    // que WooCommerce cargue archive-product.php y taxonomy-product_cat.php
    if (is_cart() || is_checkout() || is_account_page()) {
        $theme_template = trailingslashit(OPTICAVISION_THEME_DIR) . 'woocommerce.php';

        if (file_exists($theme_template)) {
            if (function_exists('opticavision_log')) {
                opticavision_log(sprintf('[THEME LOADER] Wrapper woocommerce.php del tema principal aplicado (%s)', $theme_template), 'debug');
            }
            return $theme_template;
        }

        $located = locate_template('woocommerce.php');
        if ($located && strpos($located, '/opticavision-theme/') === false) {
            if (function_exists('opticavision_log')) {
                opticavision_log(sprintf('[THEME LOADER] Ignorado wrapper heredado (%s). Manteniendo %s', $located, $template), 'warning');
            }
            return $template;
        }

        if ($located) {
            if (function_exists('opticavision_log')) {
                opticavision_log(sprintf('[THEME LOADER] Usando wrapper localizado (%s)', $located), 'debug');
            }
            return $located;
        }
    } else {
        if (function_exists('opticavision_log') && (is_shop() || is_product_category() || is_product_tag() || is_post_type_archive('product'))) {
            opticavision_log(sprintf('[THEME LOADER] No wrapper: dejando que WooCommerce use plantillas de archivo (%s)', $template), 'debug');
        }
    }

    return $template;
}
add_filter('template_include', 'opticavision_woocommerce_template_loader', 99);

/**
 * Fallback: asegúrate de que los shortcodes de filtros AJAX existan.
 * Si el plugin 'woo-ajax-filters' no está activo/cargado, intentamos cargar su archivo principal
 * para que la tienda no quede sin la UI de filtros.
 */
function opticavision_bootstrap_wc_ajax_filters_shortcodes() {
    // Evita contaminar requests de admin-AJAX únicamente
    if (defined('DOING_AJAX') && DOING_AJAX && is_admin()) {
        return;
    }

    $missing = !shortcode_exists('wc_ajax_filters') || !shortcode_exists('wc_ajax_filtered_products');
    if (!$missing) {
        return;
    }

    $plugin_file = trailingslashit(WP_PLUGIN_DIR) . 'woo-ajax-filters/woocommerce-ajax-filters.php';
    if (file_exists($plugin_file)) {
        include_once $plugin_file;
        if (function_exists('opticavision_log')) {
            opticavision_log('[THEME] Fallback: cargado woo-ajax-filters desde el tema (shortcodes estaban ausentes)', 'warning');
        }
    } else {
        if (function_exists('opticavision_log')) {
            opticavision_log('[THEME] Fallback: no se encontró el plugin woo-ajax-filters', 'error');
        }
    }
}
add_action('init', 'opticavision_bootstrap_wc_ajax_filters_shortcodes', 7);

/**
 * CRITICAL CSS: Mobile menu functionality
 * This CSS is loaded directly in the head to ensure mobile menu works immediately
 */
function opticavision_mobile_menu_critical_css() {
    ?>
    <style id="opticavision-mobile-menu-critical">
    @media (max-width: 768px) {
        .main-navigation.mobile-active {
            display: block !important;
            position: relative !important;
            background: white !important;
            width: 100% !important;
            border-top: 1px solid #e0e0e0 !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
            z-index: 99999 !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        
        .opticavision-navigation-bar .main-navigation.mobile-active {
            display: block !important;
            position: relative !important;
            background: white !important;
            width: 100% !important;
            border-top: 1px solid #e0e0e0 !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
            z-index: 99999 !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        
        .opticavision-main-header {
            z-index: 1000 !important;
        }
        
        .opticavision-navigation-bar {
            position: relative !important;
            overflow: visible !important;
            z-index: 99998 !important;
        }
        
        .main-navigation.mobile-active .menu {
            display: flex !important;
            flex-direction: column !important;
            list-style: none !important;
            margin: 0 !important;
            padding: 1rem 0 !important;
        }
        
        .main-navigation.mobile-active .menu-item {
            width: 100% !important;
            border-bottom: 1px solid #f0f0f0 !important;
        }
        
        .main-navigation.mobile-active .menu-item:last-child {
            border-bottom: none !important;
        }
        
        .main-navigation.mobile-active .menu-item a {
            display: block !important;
            padding: 1rem 1.5rem !important;
            color: #333 !important;
            text-decoration: none !important;
            border: none !important;
        }
        
        .main-navigation.mobile-active .menu-item a:hover {
            background-color: #f8f9fa !important;
            color: #1a2b88 !important;
        }
        
        /* Mobile Megamenu to Submenu Conversion */
        .main-navigation.mobile-active .has-megamenu .megamenu-dropdown {
            position: static !important;
            opacity: 1 !important;
            visibility: visible !important;
            transform: none !important;
            background: #f8f9fa !important;
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            min-width: auto !important;
            max-height: 0 !important;
            overflow: hidden !important;
            transition: max-height 0.3s ease !important;
        }
        
        .main-navigation.mobile-active .has-megamenu.submenu-open .megamenu-dropdown {
            max-height: 400px !important;
            overflow-y: auto !important;
        }
        
        .main-navigation.mobile-active .megamenu-container {
            display: flex !important;
            flex-direction: column !important;
            padding: 0 !important;
            gap: 0 !important;
            grid-template-columns: none !important;
        }
        
        .main-navigation.mobile-active .megamenu-column {
            width: 100% !important;
            margin-bottom: 0 !important;
        }
        
        .main-navigation.mobile-active .megamenu-column-header {
            background: #e9ecef !important;
            margin: 0 !important;
        }
        
        .main-navigation.mobile-active .megamenu-column-header > a {
            display: block !important;
            padding: 12px 20px !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            color: #495057 !important;
            border-bottom: 1px solid #dee2e6 !important;
        }
        
        .main-navigation.mobile-active .megamenu-column .sub-menu {
            list-style: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .main-navigation.mobile-active .megamenu-column .sub-menu li {
            border-bottom: 1px solid #f1f3f4 !important;
        }
        
        .main-navigation.mobile-active .megamenu-column .sub-menu a {
            display: block !important;
            padding: 10px 30px !important;
            font-size: 13px !important;
            color: #666 !important;
            text-decoration: none !important;
        }
        
        .main-navigation.mobile-active .megamenu-column .sub-menu a:hover {
            background-color: #ffffff !important;
            color: #1a2b88 !important;
        }
        
        /* Mobile dropdown toggle */
        .main-navigation.mobile-active .has-megamenu > a::after {
            content: '\f078' !important;
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900 !important;
            margin-left: auto !important;
            font-size: 12px !important;
            transition: transform 0.3s ease !important;
        }
        
        .main-navigation.mobile-active .has-megamenu.submenu-open > a::after {
            transform: rotate(180deg) !important;
        }
    }
    </style>
    <?php
}
add_action('wp_head', 'opticavision_mobile_menu_critical_css', 1);

/**
 * WooCommerce Spanish Translations
 * Traduce todos los textos de WooCommerce al español
 */
function opticavision_woocommerce_spanish_translations() {
    // Solo ejecutar si WooCommerce está activo
    if (!class_exists('WooCommerce')) {
        return;
    }

    // Textos de botones y acciones
    add_filter('woocommerce_product_add_to_cart_text', function() { return 'Agregar al Carrito'; });
    add_filter('woocommerce_product_single_add_to_cart_text', function() { return 'Agregar al Carrito'; });
    
    // Textos del carrito
    add_filter('woocommerce_cart_item_remove_link', function($link) {
        return str_replace('Remove this item', 'Eliminar producto', $link);
    });
    
    // Textos de checkout
    add_filter('woocommerce_order_button_text', function() { return 'Finalizar Compra'; });
    
    // Textos de productos
    add_filter('woocommerce_product_description_heading', function() { return 'Descripción'; });
    add_filter('woocommerce_product_additional_information_heading', function() { return 'Información Adicional'; });
    add_filter('woocommerce_product_reviews_heading', function() { return 'Reseñas'; });
    
    // Textos de navegación y paginación
    add_filter('woocommerce_pagination_args', function($args) {
        $args['prev_text'] = '‹ Anterior';
        $args['next_text'] = 'Siguiente ›';
        return $args;
    });
}

/**
 * Inicializar traducciones de WooCommerce después de que se carguen los plugins
 */
function opticavision_init_woocommerce_translations() {
    // Asegurar que WooCommerce esté cargado
    if (class_exists('WooCommerce')) {
        opticavision_woocommerce_spanish_translations();
        
        // Cambiar textos de WooCommerce usando gettext con prioridad alta
        add_filter('gettext', 'opticavision_translate_woocommerce_strings', 999, 3);
        add_filter('ngettext', 'opticavision_translate_woocommerce_plural_strings', 999, 5);
        add_filter('gettext_with_context', 'opticavision_translate_woocommerce_strings_with_context', 999, 4);
    }
}
add_action('plugins_loaded', 'opticavision_init_woocommerce_translations', 100);

/**
 * Traduce strings específicos de WooCommerce
 */
function opticavision_translate_woocommerce_strings($translated_text, $text, $domain) {
    if ($domain !== 'woocommerce') {
        return $translated_text;
    }
    
    $translations = array(
        // Botones principales
        'Add to cart' => 'Agregar al Carrito',
        'Add to Cart' => 'Agregar al Carrito',
        'Buy now' => 'Comprar Ahora',
        'View cart' => 'Ver Carrito',
        'Checkout' => 'Finalizar Compra',
        'Continue shopping' => 'Seguir Comprando',
        'Update cart' => 'Actualizar Carrito',
        'Apply coupon' => 'Aplicar Cupón',
        'Remove coupon' => 'Eliminar Cupón',
        
        // Estado de productos
        'In stock' => 'En Stock',
        'Out of stock' => 'Agotado',
        'On backorder' => 'En Lista de Espera',
        'Available on backorder' => 'Disponible en Lista de Espera',
        'Sale!' => '¡Oferta!',
        'New' => 'Nuevo',
        'Featured' => 'Destacado',
        
        // Carrito y checkout
        'Cart' => 'Carrito',
        'Your cart is currently empty.' => 'Tu carrito está vacío.',
        'Return to shop' => 'Volver a la Tienda',
        'Cart totals' => 'Totales del Carrito',
        'Subtotal' => 'Subtotal',
        'Total' => 'Total',
        'Shipping' => 'Envío',
        'Free shipping' => 'Envío Gratis',
        'Calculate shipping' => 'Calcular Envío',
        
        // Formularios de checkout
        'Billing details' => 'Datos de Facturación',
        'Shipping details' => 'Datos de Envío',
        'Order notes' => 'Notas del Pedido',
        'Your order' => 'Tu Pedido',
        'Payment method' => 'Método de Pago',
        'Place order' => 'Realizar Pedido',
        
        // Campos de formulario
        'First name' => 'Nombre',
        'Last name' => 'Apellido',
        'Company name' => 'Empresa',
        'Country / Region' => 'País / Región',
        'Street address' => 'Dirección',
        'Apartment, suite, unit, etc.' => 'Apartamento, suite, etc.',
        'Town / City' => 'Ciudad',
        'State / County' => 'Provincia / Estado',
        'Postcode / ZIP' => 'Código Postal',
        'Phone' => 'Teléfono',
        'Email address' => 'Correo Electrónico',
        
        // Cuenta de usuario
        'My account' => 'Mi Cuenta',
        'Dashboard' => 'Panel',
        'Orders' => 'Pedidos',
        'Downloads' => 'Descargas',
        'Addresses' => 'Direcciones',
        'Account details' => 'Datos de la Cuenta',
        'Logout' => 'Cerrar Sesión',
        'Login' => 'Iniciar Sesión',
        'Register' => 'Registrarse',
        'Lost password' => 'Recuperar Contraseña',
        
        // Navegación de productos
        'Sort by popularity' => 'Ordenar por Popularidad',
        'Sort by average rating' => 'Ordenar por Valoración',
        'Sort by latest' => 'Ordenar por Más Recientes',
        'Sort by price: low to high' => 'Ordenar por Precio: Menor a Mayor',
        'Sort by price: high to low' => 'Ordenar por Precio: Mayor a Menor',
        'Default sorting' => 'Orden por Defecto',
        
        // Filtros y categorías
        'Filter by price' => 'Filtrar por Precio',
        'Categories' => 'Categorías',
        'Product categories' => 'Categorías de Productos',
        'All categories' => 'Todas las Categorías',
        
        // Textos de productos
        'SKU:' => 'SKU:',
        'Category:' => 'Categoría:',
        'Categories:' => 'Categorías:',
        'Tag:' => 'Etiqueta:',
        'Tags:' => 'Etiquetas:',
        'Share:' => 'Compartir:',
        
        // Reseñas
        'Reviews' => 'Reseñas',
        'Review' => 'Reseña',
        'Add a review' => 'Agregar Reseña',
        'Be the first to review' => 'Sé el Primero en Reseñar',
        'There are no reviews yet.' => 'No hay reseñas aún.',
        'Your rating' => 'Tu Valoración',
        'Your review' => 'Tu Reseña',
        'Submit' => 'Enviar',
        
        // Mensajes del sistema
        'Product successfully added to your cart.' => 'Producto agregado exitosamente al carrito.',
        'View Cart' => 'Ver Carrito',
        'Continue Shopping' => 'Seguir Comprando',
        'Item removed.' => 'Producto eliminado.',
        'Undo?' => '¿Deshacer?',
        
        // Quick View y otros
        'Quick View' => 'Vista Rápida',
        'Compare' => 'Comparar',
        'Wishlist' => 'Lista de Deseos',
        'Add to wishlist' => 'Agregar a Lista de Deseos',
        'Remove from wishlist' => 'Eliminar de Lista de Deseos',
        
        // Cantidades y stock
        'Quantity' => 'Cantidad',
        'Only %s left in stock' => 'Solo quedan %s en stock',
        '%s in stock' => '%s en stock',
        'Available on backorder' => 'Disponible en lista de espera',
        
        // Precios
        'From:' => 'Desde:',
        'Free!' => '¡Gratis!',
        'Price:' => 'Precio:',
        'Sale price:' => 'Precio de oferta:',
        'Regular price:' => 'Precio regular:',
        
        // Cupones
        'Coupon code' => 'Código de Cupón',
        'Coupon:' => 'Cupón:',
        'Remove' => 'Eliminar',
        'Coupon code already applied!' => '¡Código de cupón ya aplicado!',
        'Coupon removed.' => 'Cupón eliminado.',
        'Invalid coupon.' => 'Cupón inválido.',
        
        // Varios
        'Read more' => 'Leer Más',
        'Show more' => 'Mostrar Más',
        'Show less' => 'Mostrar Menos',
        'Loading...' => 'Cargando...',
        'Search products...' => 'Buscar productos...',
        'No products found' => 'No se encontraron productos',
        'Showing %1$s–%2$s of %3$s results' => 'Mostrando %1$s–%2$s de %3$s resultados',
        'Showing the single result' => 'Mostrando el único resultado',
        'Showing all %s results' => 'Mostrando todos los %s resultados',
        
        // Mensajes de notificación
        'has been added to your cart.' => 'ha sido agregado a tu carrito.',
        'Product added to cart successfully.' => 'Producto agregado al carrito exitosamente.',
        'Cart updated.' => 'Carrito actualizado.',
        'Proceed to Checkout' => 'Proceder al Checkout',
        
        // Formularios y validación
        'is a required field.' => 'es un campo requerido.',
        'Please enter a valid email address.' => 'Por favor ingresa una dirección de email válida.',
        'Please select a country.' => 'Por favor selecciona un país.',
        'Please enter your phone number.' => 'Por favor ingresa tu número de teléfono.',
        
        // Stock y disponibilidad
        'Only %d left in stock' => 'Solo quedan %d en stock',
        'Available on backorder.' => 'Disponible en lista de espera.',
        'This product is currently out of stock and unavailable.' => 'Este producto está agotado y no disponible.',
        
        // Páginas especiales
        'Shop' => 'Tienda',
        'Products' => 'Productos',
        'All Products' => 'Todos los Productos',
        'Product' => 'Producto',
        'Home' => 'Inicio',
        
        // Breadcrumbs
        'You are here:' => 'Estás aquí:',
        'Home /' => 'Inicio /',
        
        // Filtros adicionales
        'Clear' => 'Limpiar',
        'Reset' => 'Restablecer',
        'Apply' => 'Aplicar',
        'Filter' => 'Filtrar',
        'Sort' => 'Ordenar',
        'Show' => 'Mostrar',
        'Hide' => 'Ocultar',
        
        // Términos de búsqueda
        'Search results for' => 'Resultados de búsqueda para',
        'No products were found matching your selection.' => 'No se encontraron productos que coincidan con tu selección.',
        'Try changing your search terms.' => 'Intenta cambiar tus términos de búsqueda.',
        
        // Estados de pedido 
        'Pending payment' => 'Pago pendiente',
        'Processing' => 'Procesando',
        'On hold' => 'En espera',
        'Completed' => 'Completado',
        'Cancelled' => 'Cancelado',
        'Refunded' => 'Reembolsado',
        'Failed' => 'Fallido',
        
        // Textos adicionales del tema
        'Menu' => 'Menú',
        'Close Menu' => 'Cerrar Menú',
        'Search' => 'Buscar',
        'Contact' => 'Contacto',
        'About' => 'Acerca de',
        'Privacy Policy' => 'Política de Privacidad',
        'Terms and Conditions' => 'Términos y Condiciones'
    );
    
    if (isset($translations[$text])) {
        return $translations[$text];
    }
    
    return $translated_text;
}

/**
 * Traduce strings plurales de WooCommerce
 */
function opticavision_translate_woocommerce_plural_strings($translated, $single, $plural, $number, $domain) {
    if ($domain !== 'woocommerce') {
        return $translated;
    }
    
    $plural_translations = array(
        '%s item' => array(
            'single' => '%s producto',
            'plural' => '%s productos'
        ),
        '%s review for %s' => array(
            'single' => '%s reseña para %s',
            'plural' => '%s reseñas para %s'
        ),
        'View %s review' => array(
            'single' => 'Ver %s reseña',
            'plural' => 'Ver %s reseñas'
        )
    );
    
    if (isset($plural_translations[$single])) {
        return $number == 1 ? $plural_translations[$single]['single'] : $plural_translations[$single]['plural'];
    }
    
    return $translated;
}

/**
 * Traduce strings con contexto de WooCommerce
 */
function opticavision_translate_woocommerce_strings_with_context($translated_text, $text, $context, $domain) {
    if ($domain !== 'woocommerce') {
        return $translated_text;
    }
    
    // Traducciones específicas por contexto
    $context_translations = array(
        'enhanced select' => array(
            'Select an option&hellip;' => 'Seleccionar una opción&hellip;',
            'Please select an option&hellip;' => 'Por favor selecciona una opción&hellip;',
        ),
        'placeholder' => array(
            'Search for products' => 'Buscar productos',
            'Product search' => 'Búsqueda de productos',
        ),
        'add-to-cart' => array(
            'Add to cart' => 'Agregar al Carrito',
            'Add to Cart' => 'Agregar al Carrito',
        )
    );
    
    if (isset($context_translations[$context][$text])) {
        return $context_translations[$context][$text];
    }
    
    // Fallback a la función principal sin contexto
    return opticavision_translate_woocommerce_strings($translated_text, $text, $domain);
}

/**
 * Enqueue Bancard Checkout Fix Script
 * ELIMINADO: Interfería con WooCommerce Blocks
 */

/**
 * FILTROS DESHABILITADOS - Mostrar todos los productos
 * (sin filtrar por stock ni por imagen)
 */
// add_action('pre_get_posts', 'opticavision_exclude_products_without_images_or_stock');
function opticavision_exclude_products_without_images_or_stock($query) {
    // Función deshabilitada - no se aplican filtros
    return;
}

/**
 * Habilitar búsqueda de productos por SKU
 * Permite encontrar productos ingresando su SKU en el buscador
 */
add_action('pre_get_posts', 'opticavision_search_by_sku');
function opticavision_search_by_sku($query) {
    // Solo en búsquedas del frontend
    if (is_admin() || !$query->is_search() || !$query->is_main_query()) {
        return;
    }
    
    // Solo si hay un término de búsqueda
    $search_term = $query->get('s');
    if (empty($search_term)) {
        return;
    }
    
    // Buscar productos por SKU
    $sku_products = new WP_Query(array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => '_sku',
                'value' => $search_term,
                'compare' => 'LIKE'
            )
        )
    ));
    
    // Si encontramos productos por SKU, agregarlos a la búsqueda principal
    if (!empty($sku_products->posts)) {
        // Obtener los post__in existentes o crear array vacío
        $post__in = $query->get('post__in');
        if (!is_array($post__in)) {
            $post__in = array();
        }
        
        // Combinar con los productos encontrados por SKU
        $post__in = array_merge($post__in, $sku_products->posts);
        $post__in = array_unique($post__in);
        
        // Si tenemos IDs, establecerlos en la query
        if (!empty($post__in)) {
            $query->set('post__in', $post__in);
        }
    }
}

/**
 * Filtrar variaciones de productos variables
 * Solo mostrar variaciones que tengan stock Y precio
 * Este filtro afecta el dropdown de variaciones en la página del producto
 */
add_filter('woocommerce_available_variation', 'opticavision_filter_available_variations', 10, 3);
function opticavision_filter_available_variations($variation_data, $product, $variation) {
    // Verificar si la variación tiene stock
    if (!$variation->is_in_stock()) {
        return false; // Ocultar variación sin stock del dropdown
    }
    
    // Verificar si la variación tiene precio válido
    $price = $variation->get_price();
    if (empty($price) || $price <= 0) {
        return false; // Ocultar variación sin precio del dropdown
    }
    
    return $variation_data;
}

/**
 * Filtrar variaciones al obtener hijos del producto variable
 * Asegura que solo se listen variaciones con stock y precio
 */
add_filter('woocommerce_product_get_children', 'opticavision_filter_product_children', 10, 2);
function opticavision_filter_product_children($children, $product) {
    if (!$product->is_type('variable')) {
        return $children;
    }
    
    $filtered_children = array();
    
    foreach ($children as $child_id) {
        $variation = wc_get_product($child_id);
        
        if (!$variation) {
            continue;
        }
        
        // Solo incluir variaciones con stock
        if (!$variation->is_in_stock()) {
            continue;
        }
        
        // Solo incluir variaciones con precio válido
        $price = $variation->get_price();
        if (empty($price) || $price <= 0) {
            continue;
        }
        
        $filtered_children[] = $child_id;
    }
    
    return $filtered_children;
}

/**
 * Recalcular el precio mínimo de productos variables
 * Solo considerar variaciones con stock y precio válido
 */
add_filter('woocommerce_variable_price_html', 'opticavision_variable_price_html', 10, 2);
function opticavision_variable_price_html($price_html, $product) {
    if (!$product->is_type('variable')) {
        return $price_html;
    }
    
    $variations = $product->get_available_variations();
    $valid_prices = array();
    
    foreach ($variations as $variation_data) {
        $variation_id = $variation_data['variation_id'];
        $variation = wc_get_product($variation_id);
        
        if (!$variation) {
            continue;
        }
        
        // Solo considerar variaciones con stock
        if (!$variation->is_in_stock()) {
            continue;
        }
        
        // Solo considerar variaciones con precio válido
        $price = $variation->get_price();
        if (empty($price) || $price <= 0) {
            continue;
        }
        
        $valid_prices[] = floatval($price);
    }
    
    // Si no hay precios válidos, retornar mensaje apropiado
    if (empty($valid_prices)) {
        return '<span class="price">' . __('Sin stock disponible', 'opticavision-theme') . '</span>';
    }
    
    // Obtener el precio mínimo de las variaciones válidas
    $min_price = min($valid_prices);
    $max_price = max($valid_prices);
    
    // Formatear el precio
    if ($min_price === $max_price) {
        $price_html = wc_price($min_price);
    } else {
        $price_html = sprintf(
            '%s %s',
            __('desde', 'opticavision-theme'),
            wc_price($min_price)
        );
    }
    
    return '<span class="price">' . $price_html . '</span>';
}

/**
 * Sincronizar precios de productos variables después de guardar
 * Asegura que el precio mínimo se calcule correctamente
 */
add_action('woocommerce_after_product_object_save', 'opticavision_sync_variable_product_prices', 10, 1);
function opticavision_sync_variable_product_prices($product) {
    if (!$product->is_type('variable')) {
        return;
    }
    
    $children = $product->get_children();
    $valid_prices = array();
    
    foreach ($children as $child_id) {
        $variation = wc_get_product($child_id);
        
        if (!$variation) {
            continue;
        }
        
        // Solo considerar variaciones con stock
        if (!$variation->is_in_stock()) {
            continue;
        }
        
        // Solo considerar variaciones con precio válido
        $price = $variation->get_price();
        if (empty($price) || $price <= 0) {
            continue;
        }
        
        $valid_prices[] = floatval($price);
    }
    
    // Actualizar precios del producto padre
    if (!empty($valid_prices)) {
        $product->set_price(min($valid_prices));
        $product->save();
    }
}

/**
 * Traducir título de página del carrito de "Cart" a "Carrito"
 */
add_filter('the_title', 'opticavision_translate_cart_title', 10, 2);
function opticavision_translate_cart_title($title, $id = null) {
    // Solo aplicar en la página del carrito
    if ($id && is_cart() && $id === get_option('woocommerce_cart_page_id')) {
        if (trim($title) === 'Cart') {
            return __('Carrito', 'opticavision-theme');
        }
    }
    return $title;
}

/**
 * Remover el hook que agrega productos "New in store" al carrito vacío
 */
add_action('init', 'opticavision_remove_empty_cart_new_products', 20);
function opticavision_remove_empty_cart_new_products() {
    // Remover el contenido del inner block que WooCommerce agrega automáticamente
    remove_action('woocommerce_cart_is_empty', 'woocommerce_output_all_notices', 5);
    
    // Agregar nuestro propio mensaje limpio
    add_action('woocommerce_cart_is_empty', 'opticavision_custom_empty_cart_message', 10);
}

/**
 * Remover el bloque inner "New in store" del carrito vacío
 * Este filtro se ejecuta cuando WooCommerce Blocks renderiza el carrito
 */
add_filter('render_block', 'opticavision_remove_empty_cart_inner_blocks', 10, 2);
function opticavision_remove_empty_cart_inner_blocks($block_content, $block) {
    // Solo aplicar al bloque woocommerce/cart
    if (isset($block['blockName']) && $block['blockName'] === 'woocommerce/cart') {
        // Verificar si tiene el inner block woocommerce/empty-cart-block
        if (isset($block['innerBlocks']) && is_array($block['innerBlocks'])) {
            foreach ($block['innerBlocks'] as $key => $inner_block) {
                if (isset($inner_block['blockName']) && $inner_block['blockName'] === 'woocommerce/empty-cart-block') {
                    // Remover todos los inner blocks del empty-cart-block (productos nuevos)
                    if (isset($inner_block['innerBlocks'])) {
                        unset($block['innerBlocks'][$key]['innerBlocks']);
                    }
                }
            }
        }
    }
    
    return $block_content;
}

/**
 * Mensaje personalizado de carrito vacío sin productos sugeridos
 */
function opticavision_custom_empty_cart_message() {
    echo '<div class="opticavision-empty-cart-message">';
    echo '<div class="empty-cart-icon">';
    echo '<svg width="120" height="120" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">';
    echo '<circle cx="12" cy="12" r="10" stroke="#ddd" stroke-width="2" fill="none"/>';
    echo '<path d="M8 14c.5 1 2 2 4 2s3.5-1 4-2M9 9h.01M15 9h.01" stroke="#ddd" stroke-width="2" stroke-linecap="round"/>';
    echo '</svg>';
    echo '</div>';
    echo '<h2>' . esc_html__('Tu carrito está vacío', 'opticavision-theme') . '</h2>';
    echo '<p>' . esc_html__('Parece que no has agregado nada a tu carrito. Continúa y explora las mejores ofertas.', 'opticavision-theme') . '</p>';
    
    if (wc_get_page_id('shop') > 0) {
        echo '<p class="return-to-shop">';
        echo '<a class="button wc-backward" href="' . esc_url(apply_filters('woocommerce_return_to_shop_redirect', wc_get_page_permalink('shop'))) . '">';
        echo esc_html__('Continuar Comprando', 'opticavision-theme');
        echo '</a>';
        echo '</p>';
    }
    echo '</div>';
    
    // Agregar estilos inline
    echo '<style>
    .opticavision-empty-cart-message {
        text-align: center;
        padding: 60px 20px;
        max-width: 500px;
        margin: 0 auto;
    }
    .opticavision-empty-cart-message .empty-cart-icon {
        margin-bottom: 30px;
    }
    .opticavision-empty-cart-message h2 {
        font-size: 24px;
        margin-bottom: 15px;
        color: #333;
        font-weight: 600;
    }
    .opticavision-empty-cart-message p {
        font-size: 16px;
        color: #666;
        line-height: 1.6;
        margin-bottom: 30px;
    }
    .opticavision-empty-cart-message .button {
        background: #e53e3e;
        color: white;
        padding: 12px 30px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        display: inline-block;
        transition: background 0.3s ease;
    }
    .opticavision-empty-cart-message .button:hover {
        background: #c53030;
        color: white;
    }
    
    /* Ocultar la sección "New in store" que WooCommerce Blocks agrega automáticamente */
    .wp-block-woocommerce-empty-cart-block .wp-block-woocommerce-product-collection,
    .wp-block-woocommerce-empty-cart-block .wp-block-separator,
    .wp-block-woocommerce-empty-cart-block .wc-block-grid,
    .wp-block-woocommerce-empty-cart-block .wp-block-heading:not(.wc-block-cart__empty-cart__title) {
        display: none !important;
    }
    </style>';
}

/**
 * Personalizar campos del checkout de WooCommerce
 * Compatible con WooCommerce Blocks y Checkout Clásico
 * - Agregar campo Número de Cédula o RUC después del teléfono
 * - Hacer obligatorio el campo Teléfono
 * - Hacer opcional el Código Postal con valor por defecto 0000
 * - Cambiar "Población" por "Ciudad"
 */
add_filter('woocommerce_checkout_fields', 'optica_vision_customize_checkout_fields', 9999);
function optica_vision_customize_checkout_fields($fields) {
    
    // ORDEN SEGÚN DISEÑO:
    // 1. País/Región (wide)
    // 2. Nombre (first) + Apellidos (last)
    // 3. Dirección (wide)
    // 4. Apartamento (wide, opcional, colapsado)
    // 5. Ciudad (first) + Departamento (last)
    // 6. Código Postal (first) + Teléfono (last, opcional)
    // 7. Email (wide)
    // 8. Cédula/RUC (wide)
    
    // 1. País/Región - Priority 10
    if (isset($fields['billing']['billing_country'])) {
        $fields['billing']['billing_country']['priority'] = 10;
        $fields['billing']['billing_country']['class'] = array('form-row-wide');
    }
    
    // 2. Nombre - Priority 20
    if (isset($fields['billing']['billing_first_name'])) {
        $fields['billing']['billing_first_name']['priority'] = 20;
        $fields['billing']['billing_first_name']['class'] = array('form-row-first');
    }
    
    // 3. Apellidos - Priority 30
    if (isset($fields['billing']['billing_last_name'])) {
        $fields['billing']['billing_last_name']['priority'] = 30;
        $fields['billing']['billing_last_name']['class'] = array('form-row-last');
    }
    
    // 4. Dirección - Priority 40
    if (isset($fields['billing']['billing_address_1'])) {
        $fields['billing']['billing_address_1']['priority'] = 40;
        $fields['billing']['billing_address_1']['class'] = array('form-row-wide');
        $fields['billing']['billing_address_1']['label'] = 'Dirección';
    }
    
    // 5. Apartamento - Priority 50 (opcional, colapsado)
    if (isset($fields['billing']['billing_address_2'])) {
        $fields['billing']['billing_address_2']['priority'] = 50;
        $fields['billing']['billing_address_2']['class'] = array('form-row-wide');
        $fields['billing']['billing_address_2']['required'] = false;
        $fields['billing']['billing_address_2']['label'] = '+ Añadir apartamento, habitación, escalera, etc.';
    }
    
    // 6. Ciudad - Priority 60 (FORZAR sin traducción)
    if (isset($fields['billing']['billing_city'])) {
        $fields['billing']['billing_city']['priority'] = 60;
        $fields['billing']['billing_city']['class'] = array('form-row-first');
        $fields['billing']['billing_city']['label'] = 'Ciudad';
    }
    
    // 7. Departamento - Priority 70
    if (isset($fields['billing']['billing_state'])) {
        $fields['billing']['billing_state']['priority'] = 70;
        $fields['billing']['billing_state']['class'] = array('form-row-last');
    }
    
    // 8. Código Postal - OCULTO (se asigna automáticamente "0000")
    if (isset($fields['billing']['billing_postcode'])) {
        $fields['billing']['billing_postcode']['required'] = false;
        $fields['billing']['billing_postcode']['class'] = array('form-row-wide', 'hidden');
        $fields['billing']['billing_postcode']['label'] = '';
        $fields['billing']['billing_postcode']['placeholder'] = '';
        $fields['billing']['billing_postcode']['default'] = '0000';
    }
    
    // 9. Teléfono - Priority 90 (OBLIGATORIO, ancho completo ya que código postal está oculto)
    if (isset($fields['billing']['billing_phone'])) {
        $fields['billing']['billing_phone']['priority'] = 90;
        $fields['billing']['billing_phone']['class'] = array('form-row-wide');
        $fields['billing']['billing_phone']['required'] = true;
        $fields['billing']['billing_phone']['label'] = 'Teléfono / Whatsapp';
        $fields['billing']['billing_phone']['placeholder'] = 'Ingrese su teléfono o Whatsapp';
    }
    
    // 10. Email - Priority 100
    if (isset($fields['billing']['billing_email'])) {
        $fields['billing']['billing_email']['priority'] = 100;
        $fields['billing']['billing_email']['class'] = array('form-row-wide');
    }
    
    // 11. Cédula/RUC - Priority 110
    $fields['billing']['billing_cedula_ruc'] = array(
        'type'        => 'text',
        'label'       => __('Número de Cédula o RUC', 'opticavision-theme'),
        'placeholder' => __('Ingrese su Cédula o RUC', 'opticavision-theme'),
        'required'    => true,
        'class'       => array('form-row-wide'),
        'priority'    => 110,
        'clear'       => true
    );
    
    // Aplicar cambios a shipping también
    if (isset($fields['shipping']['shipping_city'])) {
        $fields['shipping']['shipping_city']['label'] = 'Ciudad';
    }
    
    if (isset($fields['shipping']['shipping_postcode'])) {
        $fields['shipping']['shipping_postcode']['required'] = false;
        $fields['shipping']['shipping_postcode']['placeholder'] = '0000';
    }
    
    return $fields;
}

/**
 * Filtro adicional para forzar "Ciudad" en vez de "Población"
 * Sobrescribe las traducciones de WooCommerce
 */
add_filter('gettext', 'optica_vision_change_poblacion_to_ciudad', 20, 3);
function optica_vision_change_poblacion_to_ciudad($translated_text, $text, $domain) {
    if ($domain === 'woocommerce' || $domain === 'default') {
        if ($translated_text === 'Población' || $text === 'City' || $text === 'Town / City') {
            return 'Ciudad';
        }
    }
    return $translated_text;
}

/**
 * Soporte para WooCommerce Blocks: Registrar campo personalizado
 */
add_action('woocommerce_blocks_checkout_block_registration', 'optica_vision_register_checkout_blocks');
function optica_vision_register_checkout_blocks($integration_registry) {
    // Los campos se manejan vía el filtro anterior pero necesitamos asegurar compatibilidad
    // con el sistema de extensibilidad de Blocks
}

/**
 * Hacer compatibles los campos personalizados con Store API (WooCommerce Blocks)
 */
add_action('woocommerce_store_api_checkout_update_order_from_request', 'optica_vision_blocks_save_cedula_ruc', 10, 2);
function optica_vision_blocks_save_cedula_ruc($order, $request) {
    $data = $request->get_params();
    
    if (isset($data['billing_address']['cedula_ruc'])) {
        $cedula_ruc = sanitize_text_field($data['billing_address']['cedula_ruc']);
        $order->update_meta_data('_billing_cedula_ruc', $cedula_ruc);
    }
}

/**
 * Asignar valor por defecto "0000" al código postal si está vacío
 * Esto asegura compatibilidad con WooCommerce si el campo es requerido internamente
 */
add_filter('woocommerce_checkout_posted_data', 'optica_vision_default_postcode');
function optica_vision_default_postcode($data) {
    // Si el código postal está vacío, asignar 0000
    if (empty($data['billing_postcode'])) {
        $data['billing_postcode'] = '0000';
    }
    
    if (empty($data['shipping_postcode'])) {
        $data['shipping_postcode'] = '0000';
    }
    
    return $data;
}

/**
 * Validar formato de Cédula/RUC
 * Acepta números con o sin guiones
 */
add_action('woocommerce_after_checkout_validation', 'optica_vision_validate_cedula_ruc', 10, 2);
function optica_vision_validate_cedula_ruc($data, $errors) {
    if (isset($data['billing_cedula_ruc'])) {
        $cedula_ruc = sanitize_text_field($data['billing_cedula_ruc']);
        
        // Remover guiones y espacios para validación
        $cedula_ruc_clean = str_replace(['-', ' '], '', $cedula_ruc);
        
        // Validar que contenga solo números y tenga entre 6 y 12 dígitos
        if (!preg_match('/^[0-9]{6,12}$/', $cedula_ruc_clean)) {
            $errors->add(
                'cedula_ruc_error',
                __('El Número de Cédula o RUC debe contener entre 6 y 12 dígitos.', 'opticavision-theme')
            );
        }
    }
}

/**
 * Guardar el campo Cédula/RUC en los metadatos del pedido
 */
add_action('woocommerce_checkout_update_order_meta', 'optica_vision_save_cedula_ruc');
function optica_vision_save_cedula_ruc($order_id) {
    if (isset($_POST['billing_cedula_ruc'])) {
        $cedula_ruc = sanitize_text_field($_POST['billing_cedula_ruc']);
        update_post_meta($order_id, '_billing_cedula_ruc', $cedula_ruc);
    }
}

/**
 * Mostrar Cédula/RUC en la página de detalles del pedido (admin)
 */
add_action('woocommerce_admin_order_data_after_billing_address', 'optica_vision_display_cedula_ruc_admin');
function optica_vision_display_cedula_ruc_admin($order) {
    $cedula_ruc = get_post_meta($order->get_id(), '_billing_cedula_ruc', true);
    
    if ($cedula_ruc) {
        echo '<p><strong>' . __('Cédula/RUC:', 'opticavision-theme') . '</strong> ' . esc_html($cedula_ruc) . '</p>';
    }
}

/**
 * Mostrar Cédula/RUC en emails de WooCommerce
 */
add_filter('woocommerce_email_order_meta_fields', 'optica_vision_cedula_ruc_email', 10, 3);
function optica_vision_cedula_ruc_email($fields, $sent_to_admin, $order) {
    $cedula_ruc = get_post_meta($order->get_id(), '_billing_cedula_ruc', true);
    
    if ($cedula_ruc) {
        $fields['cedula_ruc'] = array(
            'label' => __('Cédula/RUC', 'opticavision-theme'),
            'value' => $cedula_ruc
        );
    }
    
    return $fields;
}





// ==== 2. GOOGLE reCAPTCHA v3 on Checkout & My-Account ====
// DESACTIVADO TEMPORALMENTE - Clave inválida bloqueaba el checkout
/*
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=YOUR_SITE_KEY_HERE' );
} );

add_action( 'wp_head', function() { ?>
    <script>
    grecaptcha.ready(function() {
        grecaptcha.execute('6Le7mf8rAAAAAEOPKkHl5sgsZUppPycBhFEADFlq', {action: 'woocommerce_register'}).then(function(token) {
            document.querySelectorAll('#reg_email, #billing_email').forEach(el => {
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'g-recaptcha-response';
                input.value = token;
                el.closest('form').appendChild(input);
            });
        });
    });
    </script>
<?php } );

// Verify on server side
add_filter( 'woocommerce_registration_errors', function( $errors, $username, $email ) {
    if ( empty( $_POST['g-recaptcha-response'] ) ) {
        $errors->add( 'recaptcha', 'reCAPTCHA failed.' );
        return $errors;
    }
    $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
        'body' => [
            'secret'   => '6Le7mf8rAAAAAOU7YrlZo8RAVaQaF6kayouDVQEa',
            'response' => $_POST['g-recaptcha-response'],
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ]
    ] );
    $result = json_decode( wp_remote_retrieve_body( $response ) );
    if ( ! $result->success || $result->score < 0.5 ) {
        $errors->add( 'recaptcha', 'You look like a bot. Try again.' );
    }
    return $errors;
}, 10, 3 );
*/

// ==== 3. BLOCK disposable emails ====
add_filter( 'woocommerce_registration_errors', function( $errors, $username, $email ) {
    $domain = strtolower( substr( strrchr( $email, "@"), 1 ) );
    $trash = [ 'tempmail', '10minutemail', 'mailinator', 'yopmail', 'guerrillamail', 'sharklasers', 'disposable' ];
    if ( in_array( $domain, $trash ) || strlen( $domain ) > 40 ) {
        $errors->add( 'spam', 'Use a real email address.' );
    }
    return $errors;
}, 20, 3 );

// ==== 4. HONEYPOT (invisible field) ====
add_action( 'woocommerce_register_form', function() { ?>
    <p class="honeypot" style="display:none;">
        <label>Leave blank <input type="text" name="hp_email" autocomplete="off" /></label>
    </p>
<?php } );

add_filter( 'woocommerce_registration_errors', function( $errors ) {
    if ( ! empty( $_POST['hp_email'] ) ) {
        $errors->add( 'bot', 'Bot detected.' );
    }
    return $errors;
}, 30 );

/**
 * Cambiar símbolo de moneda de $ a ₲ para guaraníes paraguayos
 * Funciona independientemente de la moneda configurada
 */
add_filter('woocommerce_currency_symbol', 'optica_vision_change_currency_symbol', 10, 2);
function optica_vision_change_currency_symbol($currency_symbol, $currency) {
    // Siempre retornar ₲ independientemente de la moneda configurada
    return '₲';
}

/**
 * Cambiar estado inicial de pedidos Bancard de 'on-hold' a 'processing'
 * Esto se ejecuta después de que el usuario completa el pago en Bancard
 * pero antes de que llegue la confirmación del webhook
 */
add_action('woocommerce_checkout_order_processed', 'optica_vision_bancard_set_processing_status', 10, 3);
function optica_vision_bancard_set_processing_status($order_id, $posted_data, $order) {
    // Solo aplicar para pagos con Bancard
    if ($order->get_payment_method() === 'bancard') {
        // Cambiar de 'pending' u 'on-hold' a 'processing'
        $order->update_status('processing', __('Pago procesado con Bancard - Esperando confirmación.', 'opticavision-theme'));
    }
}

// ==== 5. RATE LIMIT: max 2 registrations per IP per hour ====
add_action( 'woocommerce_created_customer', function( $customer_id ) {
    $key = 'wc_reg_limit_' . $_SERVER['REMOTE_ADDR'];
    $count = (int) get_transient( $key ) + 1;
    set_transient( $key, $count, HOUR_IN_SECONDS );
    if ( $count > 2 ) {
        wp_delete_user( $customer_id );
        wp_die( 'Too many attempts. Wait 1 hour.' );
    }
} );
/**
 * SKU Search - Simple implementation for search pages only
 * Only applies to search.php template, not to category pages or AJAX filters
 */
add_filter('posts_search', 'optica_vision_search_by_sku', 10, 2);
function optica_vision_search_by_sku($search, $query) {
    global $wpdb;
    
    // Only for frontend product searches
    if (is_admin()) {
        return $search;
    }
    
    if (!$query->is_search()) {
        return $search;
    }
    
    // Only for product post type
    $post_type = $query->get('post_type');
    
    if ($post_type !== 'product') {
        return $search;
    }
    
    $search_term = $query->get('s');
    
    if (empty($search_term)) {
        return $search;
    }
    
    // Sanitize
    $search_term = $wpdb->esc_like($search_term);
    
    // Add SKU search to existing search
    $sku_search = " OR EXISTS (
        SELECT 1 FROM {$wpdb->postmeta} 
        WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID 
        AND {$wpdb->postmeta}.meta_key = '_sku' 
        AND {$wpdb->postmeta}.meta_value LIKE '%{$search_term}%'
    )";
    
    // Find the position after the title/content/excerpt search and before post_password
    // We need to insert before " AND (wp_posts.post_password"
    if (!empty($search) && strpos($search, 'post_password') !== false) {
        $search = preg_replace(
            '/(\)\)\))\s+(AND\s+\(wp_posts\.post_password)/',
            '$1' . $sku_search . ' $2',
            $search
        );
    }
    
    return $search;
}
