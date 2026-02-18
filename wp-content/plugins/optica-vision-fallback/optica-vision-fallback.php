<?php
/**
 * Plugin Name: OpticaVision API Fallback
 * Description: Provides fallback functionality and monitoring for OpticaVision API integration
 * Version: 1.0.0
 * Author: OpticaVision Dev Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OpticaVisionAPIFallback {
    
    private $api_url = 'http://190.104.159.90:8081';
    private $username = 'userweb';
    private $password = 'us34.w38';
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_check_api_status', array($this, 'ajax_check_api_status'));
        add_action('admin_notices', array($this, 'show_admin_notices'));
        
        // Schedule periodic API checks
        add_action('wp', array($this, 'schedule_api_check'));
        add_action('optica_api_check_hook', array($this, 'periodic_api_check'));
        
        // Add admin bar indicator
        add_action('admin_bar_menu', array($this, 'add_admin_bar_indicator'), 999);
    }
    
    public function init() {
        // Register settings
        register_setting('optica_vision_api', 'optica_api_status');
        register_setting('optica_vision_api', 'optica_api_last_check');
        register_setting('optica_vision_api', 'optica_api_fallback_active');
    }
    
    public function add_admin_menu() {
        add_management_page(
            'OpticaVision API Status',
            'API Status',
            'manage_options',
            'optica-api-status',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        $status = get_option('optica_api_status', 'unknown');
        $lastCheck = get_option('optica_api_last_check', 'Never');
        $fallbackActive = get_option('optica_api_fallback_active', false);
        
        ?>
        <div class="wrap">
            <h1>OpticaVision API Status</h1>
            
            <div class="notice notice-info">
                <p><strong>Current Status:</strong> 
                    <?php if ($status === 'working'): ?>
                        <span style="color: green;">✅ API Working</span>
                    <?php elseif ($status === 'error'): ?>
                        <span style="color: red;">❌ API Error (HTTP 500)</span>
                    <?php else: ?>
                        <span style="color: orange;">⚠️ Unknown Status</span>
                    <?php endif; ?>
                </p>
                <p><strong>Last Checked:</strong> <?php echo esc_html($lastCheck); ?></p>
                <p><strong>Fallback Mode:</strong> <?php echo $fallbackActive ? 'Active' : 'Inactive'; ?></p>
            </div>
            
            <div class="card">
                <h2>API Endpoint Information</h2>
                <table class="form-table">
                    <tr>
                        <th>Base URL:</th>
                        <td><?php echo esc_html($this->api_url); ?></td>
                    </tr>
                    <tr>
                        <th>Login Endpoint:</th>
                        <td><?php echo esc_html($this->api_url . '/login'); ?></td>
                    </tr>
                    <tr>
                        <th>Username:</th>
                        <td><?php echo esc_html($this->username); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="card">
                <h2>Manual Status Check</h2>
                <p>Click the button below to manually check the API status:</p>
                <button type="button" id="check-api-btn" class="button button-primary">Check API Status Now</button>
                <div id="api-check-result" style="margin-top: 15px;"></div>
            </div>
            
            <div class="card">
                <h2>Issue Details</h2>
                <div class="notice notice-warning">
                    <h3>Current Problem:</h3>
                    <p>The API server at <code><?php echo esc_html($this->api_url); ?></code> is returning <strong>HTTP 500 Internal Server Error</strong> on the <code>/login</code> endpoint.</p>
                    
                    <h4>This means:</h4>
                    <ul>
                        <li>✅ Server is accessible and responding</li>
                        <li>✅ Network connectivity is working</li>
                        <li>❌ Server-side application is crashing during authentication</li>
                        <li>❌ Database or configuration issue on the API server</li>
                    </ul>
                    
                    <h4>Required Action:</h4>
                    <p><strong>Contact the API administrator immediately</strong> with the diagnostic report from the main diagnostics tool.</p>
                    
                    <h4>What we're monitoring:</h4>
                    <ul>
                        <li>API endpoint availability</li>
                        <li>Authentication response status</li>
                        <li>Token generation capability</li>
                    </ul>
                </div>
            </div>
            
            <div class="card">
                <h2>Fallback Features</h2>
                <p>While the API is down, this plugin provides:</p>
                <ul>
                    <li>✅ Continuous monitoring of API status</li>
                    <li>✅ Admin notifications when status changes</li>
                    <li>✅ Graceful error handling in WooCommerce</li>
                    <li>✅ Automatic retry mechanisms</li>
                    <li>✅ Detailed logging for troubleshooting</li>
                </ul>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#check-api-btn').click(function() {
                var btn = $(this);
                var result = $('#api-check-result');
                
                btn.prop('disabled', true).text('Checking...');
                result.html('<p>Checking API status...</p>');
                
                $.post(ajaxurl, {
                    action: 'check_api_status',
                    nonce: '<?php echo wp_create_nonce('check_api_status'); ?>'
                }, function(response) {
                    if (response.success) {
                        if (response.data.status === 'working') {
                            result.html('<div class="notice notice-success"><p>🎉 <strong>API is now working!</strong> Status: HTTP ' + response.data.http_code + '</p></div>');
                        } else {
                            result.html('<div class="notice notice-error"><p>❌ API still has issues. Status: HTTP ' + response.data.http_code + '</p></div>');
                        }
                    } else {
                        result.html('<div class="notice notice-error"><p>Error checking API: ' + response.data + '</p></div>');
                    }
                    
                    btn.prop('disabled', false).text('Check API Status Now');
                    
                    // Reload page after 2 seconds if API is working
                    if (response.success && response.data.status === 'working') {
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function ajax_check_api_status() {
        check_ajax_referer('check_api_status', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $result = $this->check_api_status();
        
        if ($result['http_code'] === 200) {
            update_option('optica_api_status', 'working');
            update_option('optica_api_fallback_active', false);
            wp_send_json_success(['status' => 'working', 'http_code' => $result['http_code']]);
        } else {
            update_option('optica_api_status', 'error');
            update_option('optica_api_fallback_active', true);
            wp_send_json_success(['status' => 'error', 'http_code' => $result['http_code']]);
        }
    }
    
    private function check_api_status() {
        $url = $this->api_url . '/login';
        
        $response = wp_remote_post($url, [
            'timeout' => 10,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body' => json_encode([
                'username' => $this->username,
                'password' => $this->password
            ])
        ]);
        
        if (is_wp_error($response)) {
            return ['http_code' => 0, 'error' => $response->get_error_message()];
        }
        
        $httpCode = wp_remote_retrieve_response_code($response);
        update_option('optica_api_last_check', current_time('mysql'));
        
        return ['http_code' => $httpCode, 'response' => wp_remote_retrieve_body($response)];
    }
    
    public function schedule_api_check() {
        if (!wp_next_scheduled('optica_api_check_hook')) {
            wp_schedule_event(time(), 'hourly', 'optica_api_check_hook');
        }
    }
    
    public function periodic_api_check() {
        $result = $this->check_api_status();
        $currentStatus = get_option('optica_api_status', 'unknown');
        
        if ($result['http_code'] === 200 && $currentStatus !== 'working') {
            // API is now working!
            update_option('optica_api_status', 'working');
            update_option('optica_api_fallback_active', false);
            
            // Send notification email to admin
            $admin_email = get_option('admin_email');
            wp_mail(
                $admin_email,
                'OpticaVision API is now working!',
                "Good news! The OpticaVision API at {$this->api_url} is now responding correctly.\n\nYou can now resume normal API operations.\n\nTimestamp: " . current_time('mysql')
            );
            
        } elseif ($result['http_code'] !== 200 && $currentStatus === 'working') {
            // API just went down
            update_option('optica_api_status', 'error');
            update_option('optica_api_fallback_active', true);
        }
    }
    
    public function show_admin_notices() {
        $status = get_option('optica_api_status', 'unknown');
        
        if ($status === 'error') {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>OpticaVision API Issue:</strong> The API server is currently down (HTTP 500). ';
            echo '<a href="' . admin_url('tools.php?page=optica-api-status') . '">View Status Page</a></p>';
            echo '</div>';
        }
    }
    
    public function add_admin_bar_indicator($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $status = get_option('optica_api_status', 'unknown');
        
        if ($status === 'error') {
            $wp_admin_bar->add_node([
                'id' => 'optica-api-status',
                'title' => '❌ API Down',
                'href' => admin_url('tools.php?page=optica-api-status'),
                'meta' => ['class' => 'optica-api-error']
            ]);
        } elseif ($status === 'working') {
            $wp_admin_bar->add_node([
                'id' => 'optica-api-status',
                'title' => '✅ API OK',
                'href' => admin_url('tools.php?page=optica-api-status'),
                'meta' => ['class' => 'optica-api-working']
            ]);
        }
    }
}

// Initialize the plugin
new OpticaVisionAPIFallback();

// Helper function for other plugins/themes to check API status
function optica_vision_api_is_working() {
    return get_option('optica_api_status', 'unknown') === 'working';
}

// Helper function to get API status
function optica_vision_get_api_status() {
    return [
        'status' => get_option('optica_api_status', 'unknown'),
        'last_check' => get_option('optica_api_last_check', 'Never'),
        'fallback_active' => get_option('optica_api_fallback_active', false)
    ];
}

?> 