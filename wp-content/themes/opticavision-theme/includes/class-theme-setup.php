<?php
/**
 * Theme Setup and Configuration
 *
 * @package OpticaVision_Theme
 */

defined('ABSPATH') || exit;

class OpticaVision_Theme_Setup {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('after_setup_theme', array($this, 'theme_setup'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_head', array($this, 'add_meta_tags'));
        add_filter('wp_nav_menu_args', array($this, 'nav_menu_args'));
        add_filter('nav_menu_link_attributes', array($this, 'nav_menu_link_attributes'), 10, 4);
        add_action('wp_footer', array($this, 'add_structured_data'));
        
        // Performance optimizations
        add_action('init', array($this, 'remove_unnecessary_features'));
        add_filter('script_loader_tag', array($this, 'add_async_defer_attributes'), 10, 2);
        
        // Security enhancements
        add_action('init', array($this, 'security_headers'));
        add_filter('wp_headers', array($this, 'add_security_headers'));
    }

    /**
     * Theme setup
     */
    public function theme_setup() {
        // Make theme available for translation
        load_theme_textdomain('opticavision-theme', get_template_directory() . '/languages');

        // Add default posts and comments RSS feed links to head
        add_theme_support('automatic-feed-links');

        // Let WordPress manage the document title
        add_theme_support('title-tag');

        // Enable support for Post Thumbnails on posts and pages
        add_theme_support('post-thumbnails');

        // Switch default core markup to output valid HTML5
        add_theme_support('html5', array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        ));

        // Set up the WordPress core custom background feature
        add_theme_support('custom-background', apply_filters('opticavision_custom_background_args', array(
            'default-color' => 'ffffff',
            'default-image' => '',
        )));

        // Add theme support for selective refresh for widgets
        add_theme_support('customize-selective-refresh-widgets');

        // Add support for core custom logo
        add_theme_support('custom-logo', array(
            'height'      => 100,
            'width'       => 300,
            'flex-width'  => true,
            'flex-height' => true,
        ));

        // Add support for responsive embeds
        add_theme_support('responsive-embeds');

        // Add support for editor styles
        add_theme_support('editor-styles');

        // Add support for wide and full alignment
        add_theme_support('align-wide');

        // Add support for custom line height
        add_theme_support('custom-line-height');

        // Add support for custom units
        add_theme_support('custom-units');

        // Register navigation menus
        register_nav_menus(array(
            'primary' => esc_html__('Primary Menu', 'opticavision-theme'),
            'footer'  => esc_html__('Footer Menu', 'opticavision-theme'),
            'mobile'  => esc_html__('Mobile Menu', 'opticavision-theme'),
        ));

        // Add image sizes
        add_image_size('opticavision-hero', 1920, 800, true);
        add_image_size('opticavision-carousel', 400, 300, true);
        add_image_size('opticavision-product-thumb', 300, 300, true);
        add_image_size('opticavision-product-large', 800, 800, true);
        add_image_size('opticavision-brand-logo', 200, 120, true);
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Theme stylesheet
        wp_enqueue_style('opticavision-style', get_stylesheet_uri(), array(), OPTICAVISION_THEME_VERSION);

        // Main CSS
        wp_enqueue_style(
            'opticavision-main',
            OPTICAVISION_THEME_URI . '/assets/css/main.css',
            array('opticavision-style'),
            OPTICAVISION_THEME_VERSION
        );

        // Main JavaScript
        wp_enqueue_script(
            'opticavision-main',
            OPTICAVISION_THEME_URI . '/assets/js/main.js',
            array('jquery'),
            OPTICAVISION_THEME_VERSION,
            true
        );

        // Carousel JavaScript
        wp_enqueue_script(
            'opticavision-carousel',
            OPTICAVISION_THEME_URI . '/assets/js/carousel.js',
            array('jquery'),
            OPTICAVISION_THEME_VERSION,
            true
        );

        // Localize script for AJAX
        wp_localize_script('opticavision-main', 'opticavision_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('opticavision_nonce'),
            'strings'  => array(
                'loading'     => __('Cargando...', 'opticavision-theme'),
                'error'       => __('Error al cargar el contenido', 'opticavision-theme'),
                'cart_added'  => __('Producto agregado al carrito', 'opticavision-theme'),
                'cart_error'  => __('Error al agregar al carrito', 'opticavision-theme'),
            ),
        ));

        // Comment reply script
        if (is_singular() && comments_open() && get_option('thread_comments')) {
            wp_enqueue_script('comment-reply');
        }

        // Conditional scripts
        if (is_front_page()) {
            wp_enqueue_script(
                'opticavision-homepage',
                OPTICAVISION_THEME_URI . '/assets/js/homepage.js',
                array('jquery', 'opticavision-main'),
                OPTICAVISION_THEME_VERSION,
                true
            );
        }
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        // Only load on theme customizer and widgets page
        if ('customize.php' === $hook || 'widgets.php' === $hook) {
            wp_enqueue_style(
                'opticavision-admin',
                OPTICAVISION_THEME_URI . '/assets/css/admin.css',
                array(),
                OPTICAVISION_THEME_VERSION
            );

            wp_enqueue_script(
                'opticavision-admin',
                OPTICAVISION_THEME_URI . '/assets/js/admin.js',
                array('jquery'),
                OPTICAVISION_THEME_VERSION,
                true
            );
        }
    }

    /**
     * Add meta tags to head
     */
    public function add_meta_tags() {
        // Viewport meta tag
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
        
        // Theme color for mobile browsers
        echo '<meta name="theme-color" content="#ff0000">' . "\n";
        
        // Apple touch icon
        if (has_site_icon()) {
            $icon_url = get_site_icon_url(180);
            echo '<link rel="apple-touch-icon" href="' . esc_url($icon_url) . '">' . "\n";
        }

        // Preconnect to external domains
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";

        // DNS prefetch for performance
        echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">' . "\n";
        echo '<link rel="dns-prefetch" href="//www.google-analytics.com">' . "\n";

        // Open Graph tags for social sharing
        if (is_singular()) {
            global $post;
            echo '<meta property="og:title" content="' . esc_attr(get_the_title()) . '">' . "\n";
            echo '<meta property="og:description" content="' . esc_attr(wp_strip_all_tags(get_the_excerpt())) . '">' . "\n";
            echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '">' . "\n";
            echo '<meta property="og:type" content="article">' . "\n";
            
            if (has_post_thumbnail()) {
                $image_url = get_the_post_thumbnail_url($post->ID, 'large');
                echo '<meta property="og:image" content="' . esc_url($image_url) . '">' . "\n";
            }
        }

        // Twitter Card tags
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:site" content="@opticavision">' . "\n";

        // Additional SEO meta tags
        if (is_front_page()) {
            echo '<meta name="description" content="' . esc_attr(get_bloginfo('description')) . '">' . "\n";
        }
    }

    /**
     * Modify navigation menu arguments
     */
    public function nav_menu_args($args) {
        // DISABLED: This was causing menu to not display
        // Add fallback for primary menu
        // if (isset($args['theme_location']) && $args['theme_location'] === 'primary') {
        //     $args['fallback_cb'] = array('OpticaVision_WooCommerce', 'default_menu_fallback');
        // }

        return $args;
    }

    /**
     * Add attributes to navigation menu links
     */
    public function nav_menu_link_attributes($atts, $item, $args, $depth) {
        // Add ARIA attributes for accessibility
        if (in_array('menu-item-has-children', $item->classes)) {
            $atts['aria-haspopup'] = 'true';
            $atts['aria-expanded'] = 'false';
        }

        // Add target="_blank" for external links
        if (strpos($item->url, home_url()) === false && strpos($item->url, 'http') === 0) {
            $atts['target'] = '_blank';
            $atts['rel'] = 'noopener noreferrer';
        }

        return $atts;
    }

    /**
     * Add structured data to footer
     */
    public function add_structured_data() {
        if (is_front_page()) {
            $schema = array(
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'url' => home_url(),
                'description' => get_bloginfo('description'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => get_site_icon_url(512)
                ),
                'contactPoint' => array(
                    '@type' => 'ContactPoint',
                    'telephone' => '+595-21-123-456',
                    'contactType' => 'customer service',
                    'availableLanguage' => 'Spanish'
                ),
                'sameAs' => array(
                    'https://www.facebook.com/Optica.VisionPy',
                    'https://www.instagram.com/opticavisionpy/'
                )
            );

            echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>' . "\n";
        }

        if (function_exists('is_woocommerce') && is_product()) {
            global $product;
            
            $schema = array(
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $product->get_name(),
                'description' => wp_strip_all_tags($product->get_short_description()),
                'sku' => $product->get_sku(),
                'offers' => array(
                    '@type' => 'Offer',
                    'price' => $product->get_price(),
                    'priceCurrency' => get_woocommerce_currency(),
                    'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'seller' => array(
                        '@type' => 'Organization',
                        'name' => get_bloginfo('name')
                    )
                )
            );

            if (has_post_thumbnail()) {
                $schema['image'] = get_the_post_thumbnail_url(get_the_ID(), 'large');
            }

            echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>' . "\n";
        }
    }

    /**
     * Remove unnecessary WordPress features for performance
     */
    public function remove_unnecessary_features() {
        // Remove emoji scripts and styles
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        // COMENTADO: Esta línea estaba bloqueando los emails de WooCommerce
        // remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

        // Remove unnecessary meta tags
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wp_shortlink_wp_head');

        // Remove REST API links if not needed
        remove_action('wp_head', 'rest_output_link_wp_head');
        remove_action('wp_head', 'wp_oembed_add_discovery_links');

        // Disable XML-RPC
        add_filter('xmlrpc_enabled', '__return_false');

        // Remove version from scripts and styles
        add_filter('style_loader_src', array($this, 'remove_version_scripts_styles'), 9999);
        add_filter('script_loader_src', array($this, 'remove_version_scripts_styles'), 9999);
    }

    /**
     * Remove version from scripts and styles
     */
    public function remove_version_scripts_styles($src) {
        if (strpos($src, 'ver=')) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }

    /**
     * Add async/defer attributes to scripts
     */
    public function add_async_defer_attributes($tag, $handle) {
        // Scripts to defer
        $defer_scripts = array(
            'opticavision-carousel',
            'opticavision-homepage'
        );

        // Scripts to async
        $async_scripts = array(
            'google-analytics'
        );

        if (in_array($handle, $defer_scripts)) {
            return str_replace('<script ', '<script defer ', $tag);
        }

        if (in_array($handle, $async_scripts)) {
            return str_replace('<script ', '<script async ', $tag);
        }

        return $tag;
    }

    /**
     * Security headers
     */
    public function security_headers() {
        // Hide WordPress version
        remove_action('wp_head', 'wp_generator');
        
        // Disable file editing
        if (!defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }
    }

    /**
     * Add security headers
     */
    public function add_security_headers($headers) {
        $headers['X-Content-Type-Options'] = 'nosniff';
        $headers['X-Frame-Options'] = 'SAMEORIGIN';
        $headers['X-XSS-Protection'] = '1; mode=block';
        $headers['Referrer-Policy'] = 'strict-origin-when-cross-origin';
        
        return $headers;
    }

    /**
     * Get theme option with default fallback
     */
    public static function get_theme_option($option_name, $default = '') {
        return get_theme_mod($option_name, $default);
    }

    /**
     * Check if theme feature is enabled
     */
    public static function is_feature_enabled($feature) {
        $enabled_features = self::get_theme_option('enabled_features', array());
        return in_array($feature, $enabled_features);
    }

    /**
     * Get theme version
     */
    public static function get_theme_version() {
        return OPTICAVISION_THEME_VERSION;
    }
}
