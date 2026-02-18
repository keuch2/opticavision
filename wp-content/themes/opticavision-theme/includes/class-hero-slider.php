<?php
/**
 * Hero Slider Management Class
 * 
 * Gestiona el slider hero con imágenes responsivas
 * 
 * @package OpticaVision_Theme
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

class OpticaVision_Hero_Slider {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_ajax_save_hero_slides', array($this, 'save_hero_slides'));
        add_action('wp_ajax_delete_hero_slide', array($this, 'delete_hero_slide'));
    }
    
    /**
     * Initialize the class
     */
    public function init() {
        // Register shortcode for displaying slider
        add_shortcode('opticavision_hero_slider', array($this, 'display_slider'));
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_submenu_page(
            'themes.php',
            __('Hero Slider', 'opticavision-theme'),
            __('Hero Slider', 'opticavision-theme'),
            'manage_options',
            'opticavision-hero-slider',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_scripts($hook) {
        if ($hook !== 'appearance_page_opticavision-hero-slider') {
            return;
        }
        
        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-sortable');
        
        wp_enqueue_script(
            'opticavision-hero-admin',
            OPTICAVISION_THEME_URI . '/assets/js/hero-slider-admin.js',
            array('jquery', 'jquery-ui-sortable'),
            OPTICAVISION_THEME_VERSION,
            true
        );
        
        wp_localize_script('opticavision-hero-admin', 'heroSliderAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hero_slider_nonce'),
            'strings' => array(
                'selectDesktopImage' => __('Seleccionar imagen para desktop', 'opticavision-theme'),
                'selectMobileImage' => __('Seleccionar imagen para móvil', 'opticavision-theme'),
                'removeSlide' => __('¿Estás seguro de eliminar este slide?', 'opticavision-theme'),
                'saved' => __('Slides guardados correctamente', 'opticavision-theme'),
                'error' => __('Error al guardar los slides', 'opticavision-theme')
            )
        ));
        
        wp_enqueue_style(
            'opticavision-hero-admin',
            OPTICAVISION_THEME_URI . '/assets/css/hero-slider-admin.css',
            array(),
            OPTICAVISION_THEME_VERSION
        );
    }
    
    /**
     * Admin page HTML
     */
    public function admin_page() {
        $slides = $this->get_slides();
        ?>
        <div class="wrap">
            <h1><?php _e('Gestión del Hero Slider', 'opticavision-theme'); ?></h1>
            
            <div class="hero-slider-admin">
                <div class="hero-slider-header">
                    <button type="button" class="button button-primary" id="add-new-slide">
                        <?php _e('Agregar Nuevo Slide', 'opticavision-theme'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="save-slides">
                        <?php _e('Guardar Cambios', 'opticavision-theme'); ?>
                    </button>
                </div>
                
                <div class="hero-slides-container" id="hero-slides-container">
                    <?php if (!empty($slides)): ?>
                        <?php foreach ($slides as $index => $slide): ?>
                            <?php $this->render_slide_item($slide, $index); ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-slides-message">
                            <p><?php _e('No hay slides configurados. Haz clic en "Agregar Nuevo Slide" para comenzar.', 'opticavision-theme'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Template para nuevos slides -->
                <script type="text/template" id="slide-template">
                    <?php $this->render_slide_template(); ?>
                </script>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render individual slide item
     */
    private function render_slide_item($slide, $index) {
        ?>
        <div class="slide-item" data-index="<?php echo esc_attr($index); ?>">
            <div class="slide-header">
                <span class="slide-number"><?php printf(__('Slide %d', 'opticavision-theme'), $index + 1); ?></span>
                <div class="slide-controls">
                    <span class="dashicons dashicons-menu slide-handle" title="<?php _e('Arrastrar para reordenar', 'opticavision-theme'); ?>"></span>
                    <button type="button" class="button-link slide-toggle" title="<?php _e('Expandir/Contraer', 'opticavision-theme'); ?>">
                        <span class="dashicons dashicons-arrow-up-alt2"></span>
                    </button>
                    <button type="button" class="button-link slide-delete" title="<?php _e('Eliminar slide', 'opticavision-theme'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
            
            <div class="slide-content">
                <div class="slide-images">
                    <div class="image-field">
                        <label><?php _e('Imagen Desktop', 'opticavision-theme'); ?></label>
                        <div class="image-preview">
                            <?php if (!empty($slide['desktop_image'])): ?>
                                <img src="<?php echo esc_url(wp_get_attachment_image_url($slide['desktop_image'], 'medium')); ?>" alt="">
                                <button type="button" class="remove-image">×</button>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button select-image" data-target="desktop">
                            <?php _e('Seleccionar Imagen', 'opticavision-theme'); ?>
                        </button>
                        <input type="hidden" name="slides[<?php echo $index; ?>][desktop_image]" value="<?php echo esc_attr($slide['desktop_image'] ?? ''); ?>">
                    </div>
                    
                    <div class="image-field">
                        <label><?php _e('Imagen Mobile', 'opticavision-theme'); ?></label>
                        <div class="image-preview">
                            <?php if (!empty($slide['mobile_image'])): ?>
                                <img src="<?php echo esc_url(wp_get_attachment_image_url($slide['mobile_image'], 'medium')); ?>" alt="">
                                <button type="button" class="remove-image">×</button>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button select-image" data-target="mobile">
                            <?php _e('Seleccionar Imagen', 'opticavision-theme'); ?>
                        </button>
                        <input type="hidden" name="slides[<?php echo $index; ?>][mobile_image]" value="<?php echo esc_attr($slide['mobile_image'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="slide-options">
                    <div class="option-field">
                        <label>
                            <input type="checkbox" name="slides[<?php echo $index; ?>][active]" value="1" <?php checked(!empty($slide['active'])); ?>>
                            <?php _e('Slide activo', 'opticavision-theme'); ?>
                        </label>
                    </div>
                    
                    <div class="option-field">
                        <label><?php _e('URL de enlace (opcional)', 'opticavision-theme'); ?></label>
                        <input type="url" name="slides[<?php echo $index; ?>][link_url]" value="<?php echo esc_attr($slide['link_url'] ?? ''); ?>" placeholder="https://">
                    </div>
                    
                    <div class="option-field">
                        <label>
                            <input type="checkbox" name="slides[<?php echo $index; ?>][link_new_tab]" value="1" <?php checked(!empty($slide['link_new_tab'])); ?>>
                            <?php _e('Abrir enlace en nueva pestaña', 'opticavision-theme'); ?>
                        </label>
                    </div>
                    
                    <div class="option-field">
                        <label><?php _e('Texto alternativo', 'opticavision-theme'); ?></label>
                        <input type="text" name="slides[<?php echo $index; ?>][alt_text]" value="<?php echo esc_attr($slide['alt_text'] ?? ''); ?>" placeholder="<?php _e('Descripción de la imagen', 'opticavision-theme'); ?>">
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render slide template for JavaScript
     */
    private function render_slide_template() {
        ?>
        <div class="slide-item" data-index="{{INDEX}}">
            <div class="slide-header">
                <span class="slide-number"><?php _e('Slide {{NUMBER}}', 'opticavision-theme'); ?></span>
                <div class="slide-controls">
                    <span class="dashicons dashicons-menu slide-handle"></span>
                    <button type="button" class="button-link slide-toggle">
                        <span class="dashicons dashicons-arrow-up-alt2"></span>
                    </button>
                    <button type="button" class="button-link slide-delete">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
            
            <div class="slide-content">
                <div class="slide-images">
                    <div class="image-field">
                        <label><?php _e('Imagen Desktop', 'opticavision-theme'); ?></label>
                        <div class="image-preview"></div>
                        <button type="button" class="button select-image" data-target="desktop">
                            <?php _e('Seleccionar Imagen', 'opticavision-theme'); ?>
                        </button>
                        <input type="hidden" name="slides[{{INDEX}}][desktop_image]" value="">
                    </div>
                    
                    <div class="image-field">
                        <label><?php _e('Imagen Mobile', 'opticavision-theme'); ?></label>
                        <div class="image-preview"></div>
                        <button type="button" class="button select-image" data-target="mobile">
                            <?php _e('Seleccionar Imagen', 'opticavision-theme'); ?>
                        </button>
                        <input type="hidden" name="slides[{{INDEX}}][mobile_image]" value="">
                    </div>
                </div>
                
                <div class="slide-options">
                    <div class="option-field">
                        <label>
                            <input type="checkbox" name="slides[{{INDEX}}][active]" value="1" checked>
                            <?php _e('Slide activo', 'opticavision-theme'); ?>
                        </label>
                    </div>
                    
                    <div class="option-field">
                        <label><?php _e('URL de enlace (opcional)', 'opticavision-theme'); ?></label>
                        <input type="url" name="slides[{{INDEX}}][link_url]" value="" placeholder="https://">
                    </div>
                    
                    <div class="option-field">
                        <label>
                            <input type="checkbox" name="slides[{{INDEX}}][link_new_tab]" value="1">
                            <?php _e('Abrir enlace en nueva pestaña', 'opticavision-theme'); ?>
                        </label>
                    </div>
                    
                    <div class="option-field">
                        <label><?php _e('Texto alternativo', 'opticavision-theme'); ?></label>
                        <input type="text" name="slides[{{INDEX}}][alt_text]" value="" placeholder="<?php _e('Descripción de la imagen', 'opticavision-theme'); ?>">
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get slides from database
     */
    public function get_slides() {
        $slides = get_option('opticavision_hero_slides', array());
        return is_array($slides) ? $slides : array();
    }
    
    /**
     * Save slides via AJAX
     */
    public function save_hero_slides() {
        check_ajax_referer('hero_slider_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'opticavision-theme'));
        }
        
        $slides = isset($_POST['slides']) ? $_POST['slides'] : array();
        $sanitized_slides = array();
        
        foreach ($slides as $slide) {
            $sanitized_slide = array(
                'desktop_image' => absint($slide['desktop_image'] ?? 0),
                'mobile_image' => absint($slide['mobile_image'] ?? 0),
                'active' => !empty($slide['active']),
                'link_url' => esc_url_raw($slide['link_url'] ?? ''),
                'link_new_tab' => !empty($slide['link_new_tab']),
                'alt_text' => sanitize_text_field($slide['alt_text'] ?? '')
            );
            
            // Solo guardar slides que tengan al menos una imagen
            if ($sanitized_slide['desktop_image'] || $sanitized_slide['mobile_image']) {
                $sanitized_slides[] = $sanitized_slide;
            }
        }
        
        update_option('opticavision_hero_slides', $sanitized_slides);
        
        // Log the action
        if (function_exists('opticavision_log')) {
            opticavision_log(sprintf('[THEME] Hero slider actualizado: %d slides guardados', count($sanitized_slides)));
        }
        
        wp_send_json_success(array(
            'message' => __('Slides guardados correctamente', 'opticavision-theme'),
            'slides_count' => count($sanitized_slides)
        ));
    }
    
    /**
     * Display the hero slider
     */
    public function display_slider($atts = array()) {
        $atts = shortcode_atts(array(
            'autoplay' => true,
            'autoplay_delay' => 5000,
            'show_dots' => true,
            'show_arrows' => true,
            'fade_effect' => true
        ), $atts);
        
        $slides = $this->get_slides();
        $active_slides = array_filter($slides, function($slide) {
            return !empty($slide['active']);
        });
        
        if (empty($active_slides)) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="opticavision-hero-slider" 
             data-autoplay="<?php echo esc_attr($atts['autoplay'] ? 'true' : 'false'); ?>"
             data-autoplay-delay="<?php echo esc_attr($atts['autoplay_delay']); ?>"
             data-fade="<?php echo esc_attr($atts['fade_effect'] ? 'true' : 'false'); ?>">
            
            <div class="hero-slider-container">
                <?php foreach ($active_slides as $index => $slide): ?>
                    <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                        <?php if (!empty($slide['link_url'])): ?>
                            <a href="<?php echo esc_url($slide['link_url']); ?>" 
                               <?php echo !empty($slide['link_new_tab']) ? 'target="_blank" rel="noopener"' : ''; ?>
                               class="hero-slide-link">
                        <?php endif; ?>
                        
                        <picture class="hero-slide-image">
                            <?php if (!empty($slide['mobile_image'])): ?>
                                <source media="(max-width: 768px)" 
                                        srcset="<?php echo esc_url(wp_get_attachment_image_url($slide['mobile_image'], 'full')); ?>">
                            <?php endif; ?>
                            
                            <?php if (!empty($slide['desktop_image'])): ?>
                                <img src="<?php echo esc_url(wp_get_attachment_image_url($slide['desktop_image'], 'full')); ?>" 
                                     alt="<?php echo esc_attr($slide['alt_text'] ?: __('Hero slide', 'opticavision-theme')); ?>"
                                     loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>">
                            <?php elseif (!empty($slide['mobile_image'])): ?>
                                <img src="<?php echo esc_url(wp_get_attachment_image_url($slide['mobile_image'], 'full')); ?>" 
                                     alt="<?php echo esc_attr($slide['alt_text'] ?: __('Hero slide', 'opticavision-theme')); ?>"
                                     loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>">
                            <?php endif; ?>
                        </picture>
                        
                        <?php if (!empty($slide['link_url'])): ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($atts['show_arrows'] && count($active_slides) > 1): ?>
                <button class="hero-slider-prev" aria-label="<?php _e('Slide anterior', 'opticavision-theme'); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
                    </svg>
                </button>
                <button class="hero-slider-next" aria-label="<?php _e('Siguiente slide', 'opticavision-theme'); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
                    </svg>
                </button>
            <?php endif; ?>
            
            <?php if ($atts['show_dots'] && count($active_slides) > 1): ?>
                <div class="hero-slider-dots">
                    <?php foreach ($active_slides as $index => $slide): ?>
                        <button class="hero-slider-dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                                data-slide="<?php echo esc_attr($index); ?>"
                                aria-label="<?php printf(__('Ir al slide %d', 'opticavision-theme'), $index + 1); ?>"></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
}

// Initialize the class
new OpticaVision_Hero_Slider();
