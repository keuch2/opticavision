<?php
/**
 * Plugin Name: OpticaVision reCAPTCHA v3
 * Plugin URI: https://www.opticavision.com.py
 * Description: Protección completa con Google reCAPTCHA v3 para todos los formularios del sitio (login, registro, checkout, contacto, comentarios)
 * Version: 1.0.0
 * Author: OpticaVision Development Team
 * Author URI: https://www.opticavision.com.py
 * Text Domain: opticavision-recaptcha
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('OPTICAVISION_RECAPTCHA_VERSION', '1.0.0');
define('OPTICAVISION_RECAPTCHA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OPTICAVISION_RECAPTCHA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OPTICAVISION_RECAPTCHA_PLUGIN_FILE', __FILE__);

/**
 * Main plugin class
 */
class OpticaVision_Recaptcha {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Admin instance
     */
    public $admin = null;
    
    /**
     * Frontend instance
     */
    public $frontend = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once OPTICAVISION_RECAPTCHA_PLUGIN_DIR . 'includes/class-admin.php';
        require_once OPTICAVISION_RECAPTCHA_PLUGIN_DIR . 'includes/class-frontend.php';
        require_once OPTICAVISION_RECAPTCHA_PLUGIN_DIR . 'includes/class-validator.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize admin and frontend
        add_action('plugins_loaded', array($this, 'init'));
        
        // Load text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }
    
    /**
     * Initialize plugin components
     */
    public function init() {
        // Initialize admin
        if (is_admin()) {
            $this->admin = new OpticaVision_Recaptcha_Admin();
        }
        
        // Initialize frontend (only if reCAPTCHA is configured)
        if ($this->is_configured()) {
            $this->frontend = new OpticaVision_Recaptcha_Frontend();
        }
    }
    
    /**
     * Check if reCAPTCHA is configured
     */
    public function is_configured() {
        $site_key = get_option('opticavision_recaptcha_site_key', '');
        $secret_key = get_option('opticavision_recaptcha_secret_key', '');
        
        return !empty($site_key) && !empty($secret_key);
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'opticavision-recaptcha',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        add_option('opticavision_recaptcha_enabled', '1');
        add_option('opticavision_recaptcha_threshold', '0.5');
        add_option('opticavision_recaptcha_forms', array(
            'login' => '1',
            'register' => '1',
            'comment' => '1',
            'wc_checkout' => '1',
            'wc_register' => '1',
            'contact' => '1'
        ));
        
        // Log activation
        if (function_exists('optica_log_info')) {
            optica_log_info('OpticaVision reCAPTCHA v3 plugin activated', 'RECAPTCHA');
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Log deactivation
        if (function_exists('optica_log_info')) {
            optica_log_info('OpticaVision reCAPTCHA v3 plugin deactivated', 'RECAPTCHA');
        }
    }
}

/**
 * Initialize the plugin
 */
function opticavision_recaptcha() {
    return OpticaVision_Recaptcha::get_instance();
}

// Start the plugin
opticavision_recaptcha();
