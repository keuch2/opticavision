<?php
/**
 * Customizer functionality for OpticaVision Theme
 *
 * @package OpticaVision_Theme
 */

defined('ABSPATH') || exit;

class OpticaVision_Customizer {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('customize_register', array($this, 'customize_register'));
        add_action('customize_preview_init', array($this, 'customize_preview_js'));
        add_action('wp_head', array($this, 'customizer_css'));
    }

    /**
     * Register customizer settings
     */
    public function customize_register($wp_customize) {
        // Add OpticaVision Theme Panel
        $wp_customize->add_panel('opticavision_theme_panel', array(
            'title'       => __('OpticaVision Theme', 'opticavision-theme'),
            'description' => __('Configuración del tema OpticaVision', 'opticavision-theme'),
            'priority'    => 30,
        ));

        // Hero Section
        $this->add_hero_section($wp_customize);
        
        // Brand Carousel Section
        $this->add_brands_section($wp_customize);
        
        // Promotional Banners Section
        $this->add_banners_section($wp_customize);
        
        // Colors Section
        $this->add_colors_section($wp_customize);
        
        // Typography Section
        $this->add_typography_section($wp_customize);
        
        // Layout Section
        $this->add_layout_section($wp_customize);
        
        // Footer Section
        $this->add_footer_section($wp_customize);
    }

    /**
     * Add Hero Section
     */
    private function add_hero_section($wp_customize) {
        $wp_customize->add_section('opticavision_hero_section', array(
            'title'    => __('Hero Slider', 'opticavision-theme'),
            'panel'    => 'opticavision_theme_panel',
            'priority' => 10,
        ));

        // Hero Slides Repeater
        $wp_customize->add_setting('hero_slides', array(
            'default'           => array(),
            'sanitize_callback' => array($this, 'sanitize_hero_slides'),
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control(new WP_Customize_Control($wp_customize, 'hero_slides', array(
            'label'       => __('Slides del Hero', 'opticavision-theme'),
            'description' => __('Configura las slides del hero principal', 'opticavision-theme'),
            'section'     => 'opticavision_hero_section',
            'type'        => 'textarea',
            'input_attrs' => array(
                'placeholder' => __('Configuración JSON de slides', 'opticavision-theme'),
            ),
        )));

        // Hero Autoplay
        $wp_customize->add_setting('hero_autoplay', array(
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control('hero_autoplay', array(
            'label'   => __('Autoplay del Hero', 'opticavision-theme'),
            'section' => 'opticavision_hero_section',
            'type'    => 'checkbox',
        ));

        // Hero Speed
        $wp_customize->add_setting('hero_speed', array(
            'default'           => 5000,
            'sanitize_callback' => 'absint',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control('hero_speed', array(
            'label'       => __('Velocidad del Hero (ms)', 'opticavision-theme'),
            'section'     => 'opticavision_hero_section',
            'type'        => 'number',
            'input_attrs' => array(
                'min'  => 1000,
                'max'  => 10000,
                'step' => 500,
            ),
        ));
    }

    /**
     * Add Brands Section
     */
    private function add_brands_section($wp_customize) {
        $wp_customize->add_section('opticavision_brands_section', array(
            'title'    => __('Marcas Destacadas', 'opticavision-theme'),
            'panel'    => 'opticavision_theme_panel',
            'priority' => 20,
        ));

        // Show Brands Section
        $wp_customize->add_setting('show_brands_section', array(
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control('show_brands_section', array(
            'label'   => __('Mostrar Sección de Marcas', 'opticavision-theme'),
            'section' => 'opticavision_brands_section',
            'type'    => 'checkbox',
        ));

        // Brands Title
        $wp_customize->add_setting('brands_title', array(
            'default'           => __('Marcas Destacadas', 'opticavision-theme'),
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control('brands_title', array(
            'label'   => __('Título de la Sección', 'opticavision-theme'),
            'section' => 'opticavision_brands_section',
            'type'    => 'text',
        ));

        // Brands Subtitle
        $wp_customize->add_setting('brands_subtitle', array(
            'default'           => __('Trabajamos con las mejores marcas del mercado', 'opticavision-theme'),
            'sanitize_callback' => 'sanitize_textarea_field',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control('brands_subtitle', array(
            'label'   => __('Subtítulo de la Sección', 'opticavision-theme'),
            'section' => 'opticavision_brands_section',
            'type'    => 'textarea',
        ));
    }

    /**
     * Add Banners Section
     */
    private function add_banners_section($wp_customize) {
        $wp_customize->add_section('opticavision_banners_section', array(
            'title'    => __('Banners Promocionales', 'opticavision-theme'),
            'panel'    => 'opticavision_theme_panel',
            'priority' => 30,
        ));

        // Promotional Banners
        $wp_customize->add_setting('promotional_banners', array(
            'default'           => array(),
            'sanitize_callback' => array($this, 'sanitize_promotional_banners'),
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control(new WP_Customize_Control($wp_customize, 'promotional_banners', array(
            'label'       => __('Banners Promocionales', 'opticavision-theme'),
            'description' => __('Configura los banners promocionales', 'opticavision-theme'),
            'section'     => 'opticavision_banners_section',
            'type'        => 'textarea',
            'input_attrs' => array(
                'placeholder' => __('Configuración JSON de banners', 'opticavision-theme'),
            ),
        )));
    }

    /**
     * Add Colors Section
     */
    private function add_colors_section($wp_customize) {
        $wp_customize->add_section('opticavision_colors_section', array(
            'title'    => __('Colores', 'opticavision-theme'),
            'panel'    => 'opticavision_theme_panel',
            'priority' => 40,
        ));

        // Primary Color
        $wp_customize->add_setting('primary_color', array(
            'default'           => '#ff0000',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'primary_color', array(
            'label'   => __('Color Primario', 'opticavision-theme'),
            'section' => 'opticavision_colors_section',
        )));

        // Secondary Color
        $wp_customize->add_setting('secondary_color', array(
            'default'           => '#000000',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'secondary_color', array(
            'label'   => __('Color Secundario', 'opticavision-theme'),
            'section' => 'opticavision_colors_section',
        )));

        // Accent Color
        $wp_customize->add_setting('accent_color', array(
            'default'           => '#27ae60',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'accent_color', array(
            'label'   => __('Color de Acento', 'opticavision-theme'),
            'section' => 'opticavision_colors_section',
        )));
    }

    /**
     * Add Typography Section
     */
    private function add_typography_section($wp_customize) {
        $wp_customize->add_section('opticavision_typography_section', array(
            'title'    => __('Tipografía', 'opticavision-theme'),
            'panel'    => 'opticavision_theme_panel',
            'priority' => 50,
        ));

        // Body Font
        $wp_customize->add_setting('body_font', array(
            'default'           => 'system',
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control('body_font', array(
            'label'   => __('Fuente del Cuerpo', 'opticavision-theme'),
            'section' => 'opticavision_typography_section',
            'type'    => 'select',
            'choices' => array(
                'system'    => __('Fuente del Sistema', 'opticavision-theme'),
                'roboto'    => 'Roboto',
                'open-sans' => 'Open Sans',
                'lato'      => 'Lato',
                'poppins'   => 'Poppins',
            ),
        ));

        // Heading Font
        $wp_customize->add_setting('heading_font', array(
            'default'           => 'system',
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control('heading_font', array(
            'label'   => __('Fuente de los Títulos', 'opticavision-theme'),
            'section' => 'opticavision_typography_section',
            'type'    => 'select',
            'choices' => array(
                'system'    => __('Fuente del Sistema', 'opticavision-theme'),
                'roboto'    => 'Roboto',
                'open-sans' => 'Open Sans',
                'lato'      => 'Lato',
                'poppins'   => 'Poppins',
            ),
        ));

        // Font Size
        $wp_customize->add_setting('base_font_size', array(
            'default'           => 16,
            'sanitize_callback' => 'absint',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control('base_font_size', array(
            'label'       => __('Tamaño Base de Fuente (px)', 'opticavision-theme'),
            'section'     => 'opticavision_typography_section',
            'type'        => 'number',
            'input_attrs' => array(
                'min'  => 12,
                'max'  => 24,
                'step' => 1,
            ),
        ));
    }

    /**
     * Add Layout Section
     */
    private function add_layout_section($wp_customize) {
        $wp_customize->add_section('opticavision_layout_section', array(
            'title'    => __('Diseño', 'opticavision-theme'),
            'panel'    => 'opticavision_theme_panel',
            'priority' => 60,
        ));

        // Container Width
        $wp_customize->add_setting('container_width', array(
            'default'           => 1200,
            'sanitize_callback' => 'absint',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control('container_width', array(
            'label'       => __('Ancho del Contenedor (px)', 'opticavision-theme'),
            'section'     => 'opticavision_layout_section',
            'type'        => 'number',
            'input_attrs' => array(
                'min'  => 960,
                'max'  => 1400,
                'step' => 20,
            ),
        ));

        // Sidebar Position
        $wp_customize->add_setting('sidebar_position', array(
            'default'           => 'right',
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ));

        $wp_customize->add_control('sidebar_position', array(
            'label'   => __('Posición del Sidebar', 'opticavision-theme'),
            'section' => 'opticavision_layout_section',
            'type'    => 'select',
            'choices' => array(
                'left'  => __('Izquierda', 'opticavision-theme'),
                'right' => __('Derecha', 'opticavision-theme'),
                'none'  => __('Sin Sidebar', 'opticavision-theme'),
            ),
        ));
    }

    /**
     * Add Footer Section
     */
    private function add_footer_section($wp_customize) {
        $wp_customize->add_section('opticavision_footer_section', array(
            'title'    => __('Footer', 'opticavision-theme'),
            'panel'    => 'opticavision_theme_panel',
            'priority' => 70,
        ));

        // Footer Copyright
        $wp_customize->add_setting('footer_copyright', array(
            'default'           => sprintf(__('© %s %s. Todos los derechos reservados.', 'opticavision-theme'), date('Y'), get_bloginfo('name')),
            'sanitize_callback' => 'wp_kses_post',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control('footer_copyright', array(
            'label'   => __('Texto de Copyright', 'opticavision-theme'),
            'section' => 'opticavision_footer_section',
            'type'    => 'textarea',
        ));

        // Show Back to Top
        $wp_customize->add_setting('show_back_to_top', array(
            'default'           => true,
            'sanitize_callback' => 'wp_validate_boolean',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control('show_back_to_top', array(
            'label'   => __('Mostrar Botón "Volver Arriba"', 'opticavision-theme'),
            'section' => 'opticavision_footer_section',
            'type'    => 'checkbox',
        ));
    }

    /**
     * Sanitize hero slides
     */
    public function sanitize_hero_slides($input) {
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        return array();
    }

    /**
     * Sanitize promotional banners
     */
    public function sanitize_promotional_banners($input) {
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        return array();
    }

    /**
     * Enqueue customizer preview JavaScript
     */
    public function customize_preview_js() {
        wp_enqueue_script(
            'opticavision-customizer-preview',
            OPTICAVISION_THEME_URI . '/assets/js/customizer-preview.js',
            array('customize-preview', 'jquery'),
            OPTICAVISION_THEME_VERSION,
            true
        );
    }

    /**
     * Output customizer CSS
     */
    public function customizer_css() {
        $primary_color = get_theme_mod('primary_color', '#ff0000');
        $secondary_color = get_theme_mod('secondary_color', '#000000');
        $accent_color = get_theme_mod('accent_color', '#27ae60');
        $container_width = get_theme_mod('container_width', 1200);
        $base_font_size = get_theme_mod('base_font_size', 16);
        $body_font = get_theme_mod('body_font', 'fira-sans');
        $heading_font = get_theme_mod('heading_font', 'fira-sans');

        $css = "
        <style type='text/css'>
        :root {
            --primary-color: {$primary_color};
            --secondary-color: {$secondary_color};
            --accent-color: {$accent_color};
            --container-width: {$container_width}px;
            --base-font-size: {$base_font_size}px;
        }
        
        body {
            font-family: 'Fira Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Fira Sans', sans-serif;
        }
        
        .container {
            max-width: var(--container-width);
        }
        
        html {
            font-size: var(--base-font-size);
        }
        ";

        // Font families
        if ($body_font !== 'system') {
            $css .= "
            body {
                font-family: '{$body_font}', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            ";
        }

        if ($heading_font !== 'system') {
            $css .= "
            h1, h2, h3, h4, h5, h6 {
                font-family: '{$heading_font}', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            ";
        }

        $css .= "
        .btn-primary,
        .single_add_to_cart_button,
        .hero-cta {
            background-color: var(--primary-color);
        }
        
        .btn-primary:hover,
        .single_add_to_cart_button:hover,
        .hero-cta:hover {
            background-color: var(--secondary-color);
        }
        
        a,
        .nav-menu a:hover,
        .product-title,
        .woocommerce-loop-product__title {
            color: var(--primary-color);
        }
        
        .trust-item svg,
        .product-badge.featured-badge {
            color: var(--accent-color);
        }
        </style>
        ";

        echo $css;
    }
}
