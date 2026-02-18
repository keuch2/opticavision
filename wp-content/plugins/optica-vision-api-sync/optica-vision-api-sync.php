<?php
/**
 * Plugin Name: Optica Vision API Sync
 * Description: Sync products from Optica Vision API to WooCommerce
 * Version: 1.0.1
 * Author: Mister Co.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Reduce error suppression scope - only suppress notices and warnings, not errors
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR);

// Add a custom error handler for plugin-specific errors only
function optica_vision_error_handler($errno, $errstr, $errfile, $errline) {
    // Only handle errors from this plugin
    if (strpos($errfile, 'optica-vision') === false) {
        return false; // Let WordPress handle other errors
    }
    
    // Log the error with more context
    $log_message = sprintf(
        "Optica Vision Plugin Error [%d] %s in %s on line %d",
        $errno,
        $errstr,
        basename($errfile),
        $errline
    );
    error_log($log_message);
    
    // Only suppress display for notices and warnings, not fatal errors
    return in_array($errno, [E_NOTICE, E_WARNING, E_USER_NOTICE, E_USER_WARNING]);
}
set_error_handler('optica_vision_error_handler');

// Define plugin constants
define('OPTICA_VISION_API_SYNC_VERSION', '1.0.1');
define('OPTICA_VISION_API_SYNC_PATH', plugin_dir_path(__FILE__));
define('OPTICA_VISION_API_SYNC_URL', plugin_dir_url(__FILE__));

// Include required files
require_once OPTICA_VISION_API_SYNC_PATH . 'includes/class-optica-vision-api.php';
require_once OPTICA_VISION_API_SYNC_PATH . 'includes/class-optica-vision-product-sync.php';
require_once OPTICA_VISION_API_SYNC_PATH . 'admin/class-optica-vision-admin.php';

/**
 * Main plugin class
 */
class Optica_Vision_API_Sync {
    
    private static $instance = null;
    
    public $api;
    public $product_sync;
    public $admin;
    
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Initialize components without any automatic sync
        $this->api = new Optica_Vision_API();
        $this->product_sync = new Optica_Vision_Product_Sync($this->api);
        
        // Register the scheduled sync hook
        add_action('optica_vision_scheduled_sync', array($this, 'run_scheduled_sync'));
        
        if (is_admin()) {
            $this->admin = new Optica_Vision_Admin($this);
        }
    }
    
    /**
     * Run scheduled synchronization with enhanced error handling
     */
    public function run_scheduled_sync() {
        // Verify WooCommerce is active
        if (!class_exists('WooCommerce')) {
            $this->log_sync_event('error', 'WooCommerce is not active. Cannot run scheduled sync.');
            return new WP_Error('woocommerce_inactive', 'WooCommerce is not active');
        }
        
        // Check API connection before proceeding
        if (!$this->api->is_connected()) {
            $this->log_sync_event('error', 'API not connected. Attempting to reconnect...');
            $connection_result = $this->api->connect();
            if (is_wp_error($connection_result)) {
                $this->log_sync_event('error', 'Failed to reconnect to API: ' . $connection_result->get_error_message());
                return $connection_result;
            }
        }
        
        $this->log_sync_event('info', 'Starting scheduled product synchronization');
        
        // Execute product synchronization
        $result = $this->product_sync->sync_products();
        
        // Log detailed results
        if (is_wp_error($result)) {
            $this->log_sync_event('error', 'Scheduled sync failed: ' . $result->get_error_message());
        } else {
            $message = sprintf(
                'Scheduled sync completed successfully. Created: %d, Updated: %d, Errors: %d',
                $result['created'] ?? 0,
                $result['updated'] ?? 0,
                $result['errors'] ?? 0
            );
            $this->log_sync_event('success', $message);
        }
        
        return $result;
    }
    
    /**
     * Enhanced logging for sync events
     */
    private function log_sync_event($level, $message) {
        $timestamp = current_time('Y-m-d H:i:s');
        $log_entry = sprintf('[%s] [%s] %s', $timestamp, strtoupper($level), $message);
        
        // Log to WordPress error log
        error_log($log_entry);
        
        // Store in plugin-specific log option (keep last 100 entries)
        $logs = get_option('optica_vision_sync_logs', []);
        array_unshift($logs, [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message
        ]);
        
        // Keep only last 100 log entries
        $logs = array_slice($logs, 0, 100);
        update_option('optica_vision_sync_logs', $logs);
    }
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>Optica Vision API Sync:</strong> This plugin requires WooCommerce to be installed and activated.';
            echo '</p></div>';
        });
        return;
    }
    
    if (is_admin()) {
        Optica_Vision_API_Sync::get_instance();
    }
});

/**
 * Plugin activation hook
 */
function optica_vision_api_sync_activate() {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('This plugin requires WooCommerce to be installed and activated.');
    }
    
    // Set default options
    add_option('optica_vision_api_url', 'http://190.104.159.90:8081');
    add_option('optica_vision_sync_interval', 'daily');
    add_option('optica_vision_sync_logs', []);
    
    // Log activation
    error_log('Optica Vision API Sync plugin activated');
}
register_activation_hook(__FILE__, 'optica_vision_api_sync_activate');

/**
 * Plugin deactivation hook
 */
function optica_vision_api_sync_deactivate() {
    // Clear scheduled events
    $timestamp = wp_next_scheduled('optica_vision_scheduled_sync');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'optica_vision_scheduled_sync');
    }
    
    // Log deactivation
    error_log('Optica Vision API Sync plugin deactivated');
}
register_deactivation_hook(__FILE__, 'optica_vision_api_sync_deactivate');

/**
 * Plugin uninstall cleanup
 */
function optica_vision_api_sync_uninstall() {
    // Remove plugin options
    delete_option('optica_vision_api_token');
    delete_option('optica_vision_api_url');
    delete_option('optica_vision_api_username');
    delete_option('optica_vision_api_password');
    delete_option('optica_vision_sync_interval');
    delete_option('optica_vision_sync_logs');
    delete_option('optica_vision_last_sync');
    
    // Clear any remaining scheduled events
    wp_clear_scheduled_hook('optica_vision_scheduled_sync');
}
register_uninstall_hook(__FILE__, 'optica_vision_api_sync_uninstall');
