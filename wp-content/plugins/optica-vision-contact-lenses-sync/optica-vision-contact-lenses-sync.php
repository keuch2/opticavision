<?php
/**
 * Plugin Name: Optica Vision Contact Lenses Sync
 * Description: Sync contact lenses from Optica Vision API to WooCommerce Variable Products with prescription variations
 * Version: 1.0.0
 * Author: Mister Co.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('OPTICA_VISION_CL_SYNC_VERSION', '1.0.0');
define('OPTICA_VISION_CL_SYNC_PATH', plugin_dir_path(__FILE__));
define('OPTICA_VISION_CL_SYNC_URL', plugin_dir_url(__FILE__));

// Log plugin load
error_log('[CL SYNC] ========== Plugin file loaded ==========');

// Include required files
require_once OPTICA_VISION_CL_SYNC_PATH . 'includes/class-optica-vision-cl-api.php';
require_once OPTICA_VISION_CL_SYNC_PATH . 'includes/class-optica-vision-cl-product-sync.php';
require_once OPTICA_VISION_CL_SYNC_PATH . 'admin/class-optica-vision-cl-admin.php';

error_log('[CL SYNC] Required files included successfully');

/**
 * Main plugin class
 */
class Optica_Vision_Contact_Lenses_Sync {
    
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
        error_log('[CL SYNC] Constructor called - initializing plugin');
        
        // Initialize components
        $this->api = new Optica_Vision_CL_API();
        error_log('[CL SYNC] API class instantiated');
        
        $this->product_sync = new Optica_Vision_CL_Product_Sync($this->api);
        error_log('[CL SYNC] Product Sync class instantiated');
        
        // Register the scheduled sync hook
        add_action('optica_vision_cl_scheduled_sync', array($this, 'run_scheduled_sync'));
        error_log('[CL SYNC] Scheduled sync hook registered');
        
        if (is_admin()) {
            $this->admin = new Optica_Vision_CL_Admin($this);
            error_log('[CL SYNC] Admin class instantiated (is_admin = true)');
        } else {
            error_log('[CL SYNC] Not in admin context, skipping admin class');
        }
        
        error_log('[CL SYNC] Constructor completed');
    }
    
    /**
     * Run scheduled synchronization
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
        
        $this->log_sync_event('info', 'Starting scheduled contact lens synchronization');
        
        // Execute product synchronization
        $result = $this->product_sync->sync_products();
        
        // Log detailed results
        if (is_wp_error($result)) {
            $this->log_sync_event('error', 'Scheduled sync failed: ' . $result->get_error_message());
        } else {
            $message = sprintf(
                'Scheduled sync completed successfully. Created: %d, Updated: %d, Variations: %d, Errors: %d',
                $result['created'] ?? 0,
                $result['updated'] ?? 0,
                $result['variations'] ?? 0,
                $result['errors'] ?? 0
            );
            $this->log_sync_event('success', $message);
        }
        
        return $result;
    }
    
    /**
     * Enhanced logging for sync events
     */
    public function log_sync_event($level, $message) {
        $timestamp = current_time('Y-m-d H:i:s');
        $log_entry = sprintf('[%s] [%s] %s', $timestamp, strtoupper($level), $message);
        
        // Log to WordPress error log
        error_log($log_entry);
        
        // Store in plugin-specific log option (keep last 100 entries)
        $logs = get_option('optica_vision_cl_sync_logs', []);
        array_unshift($logs, [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message
        ]);
        
        // Keep only last 100 log entries
        $logs = array_slice($logs, 0, 100);
        update_option('optica_vision_cl_sync_logs', $logs);
    }
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>Optica Vision Contact Lenses Sync:</strong> This plugin requires WooCommerce to be installed and activated.';
            echo '</p></div>';
        });
        return;
    }
    
    if (is_admin()) {
        Optica_Vision_Contact_Lenses_Sync::get_instance();
    }
});

// Initialize prescription attribute when WooCommerce is fully loaded
add_action('woocommerce_init', function() {
    optica_vision_cl_ensure_prescription_attribute();
});

/**
 * Ensure prescription attribute exists (can be called safely after WooCommerce init)
 */
function optica_vision_cl_ensure_prescription_attribute() {
    global $wpdb;
    
    $attribute_taxonomy_name = 'pa_prescription';
    
    // Check if the taxonomy already exists
    if (taxonomy_exists($attribute_taxonomy_name)) {
        error_log('Prescription taxonomy already exists: ' . $attribute_taxonomy_name);
        return true;
    }
    
    // Check if attribute exists in database
    $attribute_table = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
    $existing_attribute = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$attribute_table} WHERE attribute_name = %s",
        'prescription'
    ));
    
    if (!$existing_attribute) {
        // Create attribute directly in database
        $result = $wpdb->insert(
            $attribute_table,
            [
                'attribute_name' => 'prescription',
                'attribute_label' => 'Graduación',
                'attribute_type' => 'select',
                'attribute_orderby' => 'menu_order',
                'attribute_public' => 0
            ],
            ['%s', '%s', '%s', '%s', '%d']
        );
        
        if ($result === false) {
            error_log('Failed to insert prescription attribute into database: ' . $wpdb->last_error);
            return false;
        }
        
        error_log('Created prescription attribute in database with ID: ' . $wpdb->insert_id);
        
        // Clear WooCommerce attribute cache
        delete_transient('wc_attribute_taxonomies');
        wp_cache_delete('woocommerce_attribute_taxonomies', 'woocommerce');
        delete_option('_transient_wc_attribute_taxonomies');
        delete_option('_transient_timeout_wc_attribute_taxonomies');
        
        // Clear WordPress object cache
        wp_cache_flush();
    } else {
        error_log('Found existing prescription attribute in database with ID: ' . $existing_attribute->attribute_id);
    }
    
    // Register the taxonomy
    $taxonomy_args = [
        'hierarchical' => false,
        'show_ui' => false,
        'query_var' => true,
        'rewrite' => false,
        'public' => false,
        'show_in_nav_menus' => false,
        'show_in_menu' => false,
        'meta_box_cb' => false,
        'show_in_rest' => false,
    ];
    
    error_log('Registering prescription taxonomy: ' . $attribute_taxonomy_name);
    register_taxonomy($attribute_taxonomy_name, ['product', 'product_variation'], $taxonomy_args);
    
    // Verify taxonomy was registered
    if (taxonomy_exists($attribute_taxonomy_name)) {
        error_log('Successfully registered prescription taxonomy: ' . $attribute_taxonomy_name);
        return true;
    } else {
        error_log('Failed to register prescription taxonomy: ' . $attribute_taxonomy_name);
        return false;
    }
}

/**
 * Plugin activation hook
 */
function optica_vision_cl_sync_activate() {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('This plugin requires WooCommerce to be installed and activated.');
    }
    
    // Set default options
    add_option('optica_vision_cl_api_url', 'http://190.104.159.90:8081');
    add_option('optica_vision_cl_api_username', 'userweb');
    add_option('optica_vision_cl_api_password', 'us34.w38');
    add_option('optica_vision_cl_sync_interval', 'daily');
    add_option('optica_vision_cl_sync_logs', []);
    
    // Note: Prescription attribute creation is handled during first sync
    // to ensure WooCommerce is fully loaded
    
    // Log activation
    error_log('Optica Vision Contact Lenses Sync plugin activated');
}
register_activation_hook(__FILE__, 'optica_vision_cl_sync_activate');

/**
 * Plugin deactivation hook
 */
function optica_vision_cl_sync_deactivate() {
    // Clear scheduled events
    $timestamp = wp_next_scheduled('optica_vision_cl_scheduled_sync');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'optica_vision_cl_scheduled_sync');
    }
    
    // Log deactivation
    error_log('Optica Vision Contact Lenses Sync plugin deactivated');
}
register_deactivation_hook(__FILE__, 'optica_vision_cl_sync_deactivate');

/**
 * Hook to ensure prescription attribute is always available
 */
add_action('init', function() {
    if (class_exists('WooCommerce')) {
        optica_vision_cl_ensure_prescription_attribute();
    }
}, 20); // Run after WooCommerce init

/**
 * Plugin uninstall cleanup
 */
function optica_vision_cl_sync_uninstall() {
    // Remove plugin options
    delete_option('optica_vision_cl_api_token');
    delete_option('optica_vision_cl_api_url');
    delete_option('optica_vision_cl_api_username');
    delete_option('optica_vision_cl_api_password');
    delete_option('optica_vision_cl_sync_interval');
    delete_option('optica_vision_cl_sync_logs');
    delete_option('optica_vision_cl_last_sync');
    
    // Clear any remaining scheduled events
    wp_clear_scheduled_hook('optica_vision_cl_scheduled_sync');
}
register_uninstall_hook(__FILE__, 'optica_vision_cl_sync_uninstall'); 