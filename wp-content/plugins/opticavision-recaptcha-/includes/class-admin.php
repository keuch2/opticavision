<?php
/**
 * Admin functionality for OpticaVision reCAPTCHA
 *
 * @package OpticaVision_Recaptcha
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class OpticaVision_Recaptcha_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_filter('plugin_action_links_' . plugin_basename(OPTICAVISION_RECAPTCHA_PLUGIN_FILE), array($this, 'add_action_links'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('OpticaVision reCAPTCHA', 'opticavision-recaptcha'),
            __('reCAPTCHA', 'opticavision-recaptcha'),
            'manage_options',
            'opticavision-recaptcha',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // General settings
        register_setting('opticavision_recaptcha_settings', 'opticavision_recaptcha_enabled', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '1'
        ));
        
        register_setting('opticavision_recaptcha_settings', 'opticavision_recaptcha_site_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('opticavision_recaptcha_settings', 'opticavision_recaptcha_secret_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('opticavision_recaptcha_settings', 'opticavision_recaptcha_threshold', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_threshold'),
            'default' => '0.5'
        ));
        
        register_setting('opticavision_recaptcha_settings', 'opticavision_recaptcha_forms', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_forms'),
            'default' => array()
        ));
        
        // Add settings sections
        add_settings_section(
            'opticavision_recaptcha_api',
            __('Configuración de API', 'opticavision-recaptcha'),
            array($this, 'render_api_section'),
            'opticavision-recaptcha'
        );
        
        add_settings_section(
            'opticavision_recaptcha_forms_section',
            __('Formularios Protegidos', 'opticavision-recaptcha'),
            array($this, 'render_forms_section'),
            'opticavision-recaptcha'
        );
        
        // Add settings fields
        add_settings_field(
            'opticavision_recaptcha_enabled',
            __('Habilitar reCAPTCHA', 'opticavision-recaptcha'),
            array($this, 'render_enabled_field'),
            'opticavision-recaptcha',
            'opticavision_recaptcha_api'
        );
        
        add_settings_field(
            'opticavision_recaptcha_site_key',
            __('Site Key', 'opticavision-recaptcha'),
            array($this, 'render_site_key_field'),
            'opticavision-recaptcha',
            'opticavision_recaptcha_api'
        );
        
        add_settings_field(
            'opticavision_recaptcha_secret_key',
            __('Secret Key', 'opticavision-recaptcha'),
            array($this, 'render_secret_key_field'),
            'opticavision-recaptcha',
            'opticavision_recaptcha_api'
        );
        
        add_settings_field(
            'opticavision_recaptcha_threshold',
            __('Umbral de Score', 'opticavision-recaptcha'),
            array($this, 'render_threshold_field'),
            'opticavision-recaptcha',
            'opticavision_recaptcha_api'
        );
        
        add_settings_field(
            'opticavision_recaptcha_forms',
            __('Seleccionar Formularios', 'opticavision-recaptcha'),
            array($this, 'render_forms_field'),
            'opticavision-recaptcha',
            'opticavision_recaptcha_forms_section'
        );
    }
    
    /**
     * Sanitize threshold value
     */
    public function sanitize_threshold($value) {
        $value = floatval($value);
        if ($value < 0) $value = 0;
        if ($value > 1) $value = 1;
        return (string)$value;
    }
    
    /**
     * Sanitize forms array
     */
    public function sanitize_forms($value) {
        if (!is_array($value)) {
            return array();
        }
        return array_map('sanitize_text_field', $value);
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="opticavision-recaptcha-admin">
                <div class="opticavision-recaptcha-header">
                    <p class="description">
                        <?php esc_html_e('Protege tu sitio con Google reCAPTCHA v3. Esta tecnología invisible verifica automáticamente que los visitantes sean humanos sin interrumpir la experiencia del usuario.', 'opticavision-recaptcha'); ?>
                    </p>
                    <p class="description">
                        <?php 
                        printf(
                            /* translators: %s: URL to Google reCAPTCHA admin */
                            esc_html__('Obtén tus claves en %s', 'opticavision-recaptcha'),
                            '<a href="https://www.google.com/recaptcha/admin" target="_blank">Google reCAPTCHA Admin</a>'
                        );
                        ?>
                    </p>
                </div>
                
                <?php settings_errors('opticavision_recaptcha_settings'); ?>
                
                <form method="post" action="options.php">
                    <?php
                    settings_fields('opticavision_recaptcha_settings');
                    do_settings_sections('opticavision-recaptcha');
                    submit_button(__('Guardar Configuración', 'opticavision-recaptcha'));
                    ?>
                </form>
                
                <div class="opticavision-recaptcha-info">
                    <h2><?php esc_html_e('Información', 'opticavision-recaptcha'); ?></h2>
                    <ul>
                        <li><strong><?php esc_html_e('Versión:', 'opticavision-recaptcha'); ?></strong> <?php echo esc_html(OPTICAVISION_RECAPTCHA_VERSION); ?></li>
                        <li><strong><?php esc_html_e('Estado:', 'opticavision-recaptcha'); ?></strong> 
                            <?php
                            $is_configured = opticavision_recaptcha()->is_configured();
                            if ($is_configured) {
                                echo '<span style="color: green;">✓ ' . esc_html__('Configurado correctamente', 'opticavision-recaptcha') . '</span>';
                            } else {
                                echo '<span style="color: red;">✗ ' . esc_html__('Faltan claves de API', 'opticavision-recaptcha') . '</span>';
                            }
                            ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render API section
     */
    public function render_api_section() {
        echo '<p>' . esc_html__('Configura las claves de Google reCAPTCHA v3 y ajusta el umbral de seguridad.', 'opticavision-recaptcha') . '</p>';
    }
    
    /**
     * Render forms section
     */
    public function render_forms_section() {
        echo '<p>' . esc_html__('Selecciona en qué formularios deseas activar la protección de reCAPTCHA.', 'opticavision-recaptcha') . '</p>';
    }
    
    /**
     * Render enabled field
     */
    public function render_enabled_field() {
        $value = get_option('opticavision_recaptcha_enabled', '1');
        ?>
        <label>
            <input type="checkbox" name="opticavision_recaptcha_enabled" value="1" <?php checked($value, '1'); ?>>
            <?php esc_html_e('Activar protección reCAPTCHA v3', 'opticavision-recaptcha'); ?>
        </label>
        <?php
    }
    
    /**
     * Render site key field
     */
    public function render_site_key_field() {
        $value = get_option('opticavision_recaptcha_site_key', '');
        ?>
        <input type="text" name="opticavision_recaptcha_site_key" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="6Lc...">
        <p class="description"><?php esc_html_e('La clave pública para el frontend (Site Key)', 'opticavision-recaptcha'); ?></p>
        <?php
    }
    
    /**
     * Render secret key field
     */
    public function render_secret_key_field() {
        $value = get_option('opticavision_recaptcha_secret_key', '');
        ?>
        <input type="password" name="opticavision_recaptcha_secret_key" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="6Lc...">
        <p class="description"><?php esc_html_e('La clave privada para el backend (Secret Key)', 'opticavision-recaptcha'); ?></p>
        <?php
    }
    
    /**
     * Render threshold field
     */
    public function render_threshold_field() {
        $value = get_option('opticavision_recaptcha_threshold', '0.5');
        ?>
        <input type="number" name="opticavision_recaptcha_threshold" value="<?php echo esc_attr($value); ?>" min="0" max="1" step="0.1" class="small-text">
        <p class="description">
            <?php esc_html_e('Umbral de score (0.0 - 1.0). Valores más altos son más estrictos. Recomendado: 0.5', 'opticavision-recaptcha'); ?>
        </p>
        <?php
    }
    
    /**
     * Render forms field
     */
    public function render_forms_field() {
        $forms = get_option('opticavision_recaptcha_forms', array());
        
        $available_forms = array(
            'login' => __('Formulario de Login', 'opticavision-recaptcha'),
            'register' => __('Formulario de Registro', 'opticavision-recaptcha'),
            'comment' => __('Formulario de Comentarios', 'opticavision-recaptcha'),
            'wc_checkout' => __('WooCommerce - Checkout', 'opticavision-recaptcha'),
            'wc_register' => __('WooCommerce - Registro', 'opticavision-recaptcha'),
            'contact' => __('Formularios de Contacto', 'opticavision-recaptcha')
        );
        
        foreach ($available_forms as $form_key => $form_label) {
            $checked = isset($forms[$form_key]) && $forms[$form_key] === '1';
            ?>
            <label style="display: block; margin-bottom: 8px;">
                <input type="checkbox" name="opticavision_recaptcha_forms[<?php echo esc_attr($form_key); ?>]" value="1" <?php checked($checked); ?>>
                <?php echo esc_html($form_label); ?>
            </label>
            <?php
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_opticavision-recaptcha' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'opticavision-recaptcha-admin',
            OPTICAVISION_RECAPTCHA_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            OPTICAVISION_RECAPTCHA_VERSION
        );
    }
    
    /**
     * Add action links
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=opticavision-recaptcha') . '">' . __('Configuración', 'opticavision-recaptcha') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}
