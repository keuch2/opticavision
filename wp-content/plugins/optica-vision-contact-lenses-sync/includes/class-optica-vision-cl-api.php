<?php
/**
 * Optica Vision Contact Lenses API Class
 * 
 * Handles all API interactions for contact lenses from the Optica Vision API
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Optica_Vision_CL_API {
    /**
     * Authentication token
     */
    private $token = null;
    
    /**
     * Request timeout (seconds)
     */
    private $timeout = 120;
    
    /**
     * Maximum retry attempts
     */
    private $max_retries = 3;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Get stored token
        $this->token = get_option('optica_vision_cl_api_token');
    }
    
    /**
     * Get API base URL from options
     */
    private function get_api_url() {
        return get_option('optica_vision_cl_api_url', 'http://190.104.159.90:8081');
    }
    
    /**
     * Get API credentials from options
     */
    private function get_credentials() {
        return [
            'username' => get_option('optica_vision_cl_api_username', 'userweb'),
            'password' => get_option('optica_vision_cl_api_password', 'us34.w38')
        ];
    }
    
    /**
     * Check if API is connected
     * 
     * @return bool
     */
    public function is_connected() {
        return !empty($this->token);
    }
    
    /**
     * Connect to API and store token
     * 
     * @return bool|WP_Error
     */
    public function connect() {
        $credentials = $this->get_credentials();
        
        if (empty($credentials['username']) || empty($credentials['password'])) {
            return new WP_Error('missing_credentials', __('API credentials not configured', 'optica-vision-cl-sync'));
        }
        
        try {
            $url = trailingslashit($this->get_api_url()) . 'login';
            
            $response = wp_remote_post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'body' => json_encode([
                    'username' => $credentials['username'],
                    'password' => $credentials['password']
                ]),
                'timeout' => $this->timeout,
                'sslverify' => false
            ]);
            
            if (is_wp_error($response)) {
                error_log('Optica Vision CL API login error: ' . $response->get_error_message());
                return new WP_Error(
                    'api_connection_failed', 
                    sprintf(__('Failed to connect to API: %s', 'optica-vision-cl-sync'), $response->get_error_message())
                );
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            // Log response for debugging
            error_log(sprintf('API login response [%d]: %s', $response_code, substr($body, 0, 200)));
            
            if ($response_code !== 200) {
                return new WP_Error(
                    'api_http_error',
                    sprintf(__('API login returned status code %d', 'optica-vision-cl-sync'), $response_code)
                );
            }
            
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Optica Vision CL API JSON decode error: ' . json_last_error_msg());
                error_log('Optica Vision CL API response: ' . substr($body, 0, 200));
                return new WP_Error(
                    'api_json_error',
                    sprintf(__('Invalid JSON response: %s', 'optica-vision-cl-sync'), json_last_error_msg())
                );
            }
            
            if (isset($data['token'])) {
                $this->token = $data['token'];
                update_option('optica_vision_cl_api_token', $this->token);
                error_log('Optica Vision CL API: Successfully obtained token');
                return true;
            }
            
            return new WP_Error('api_auth_failed', __('Authentication failed - no token received', 'optica-vision-cl-sync'));
            
        } catch (Exception $e) {
            error_log('Optica Vision CL API exception: ' . $e->getMessage());
            return new WP_Error('api_exception', $e->getMessage());
        }
    }
    
    /**
     * Force reconnection by clearing current token
     * 
     * @return bool|WP_Error
     */
    public function force_reconnect() {
        // Clear existing token
        $this->token = null;
        delete_option('optica_vision_cl_api_token');
        
        // Attempt new connection
        return $this->connect();
    }
    
    /**
     * Get contact lenses from API
     * 
     * @return array|WP_Error
     */
    public function get_contact_lenses() {
        error_log('[CL SYNC API] get_contact_lenses() called');
        
        if (!$this->is_connected()) {
            error_log('[CL SYNC API] Not connected, attempting to connect...');
            $connect_result = $this->connect();
            if (is_wp_error($connect_result)) {
                error_log('[CL SYNC API] Connection failed: ' . $connect_result->get_error_message());
                return $connect_result;
            }
            error_log('[CL SYNC API] Connection successful');
        } else {
            error_log('[CL SYNC API] Already connected with token');
        }
        
        try {
            $url = trailingslashit($this->get_api_url()) . 'mercaderias_web_lc';
            error_log('[CL SYNC API] Requesting URL: ' . $url);
            error_log('[CL SYNC API] Timeout set to: ' . $this->timeout . ' seconds');
            
            $start_time = microtime(true);
            $response = wp_remote_post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->token
                ],
                'body' => json_encode([]),
                'timeout' => $this->timeout,
                'sslverify' => false
            ]);
            
            $elapsed = round(microtime(true) - $start_time, 2);
            error_log('[CL SYNC API] API request completed in ' . $elapsed . ' seconds');
            
            if (is_wp_error($response)) {
                error_log('[CL SYNC API] API ERROR: ' . $response->get_error_message());
                return $response;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            error_log('[CL SYNC API] Response code: ' . $response_code . ', Body length: ' . strlen($body));
            
            // Handle 401 authentication errors
            if ($response_code === 401) {
                error_log('Optica Vision CL API: Received 401, attempting to reconnect...');
                
                // Clear the invalid token
                $this->token = null;
                delete_option('optica_vision_cl_api_token');
                
                // Try to reconnect
                $reconnect_result = $this->connect();
                if (is_wp_error($reconnect_result)) {
                    error_log('Optica Vision CL API: Reconnection failed: ' . $reconnect_result->get_error_message());
                    return new WP_Error(
                        'api_reconnect_failed',
                        sprintf(__('API authentication expired and reconnection failed: %s', 'optica-vision-cl-sync'), $reconnect_result->get_error_message())
                    );
                }
                
                // Retry the original request with new token
                error_log('Optica Vision CL API: Reconnected successfully, retrying request...');
                $response = wp_remote_post($url, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer ' . $this->token
                    ],
                    'body' => json_encode([]),
                    'timeout' => $this->timeout,
                    'sslverify' => false
                ]);
                
                if (is_wp_error($response)) {
                    error_log('Optica Vision CL API products retry error: ' . $response->get_error_message());
                    return $response;
                }
                
                $response_code = wp_remote_retrieve_response_code($response);
                $body = wp_remote_retrieve_body($response);
            }
            
            // Log response for debugging
            error_log(sprintf('Contact lenses API response [%d]: %s', $response_code, substr($body, 0, 200)));
            
            if ($response_code !== 200) {
                return new WP_Error(
                    'api_products_error',
                    sprintf(__('API returned status code %d', 'optica-vision-cl-sync'), $response_code)
                );
            }
            
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Optica Vision CL API JSON decode error: ' . json_last_error_msg());
                error_log('Optica Vision CL API response body: ' . substr($body, 0, 500));
                return new WP_Error(
                    'api_json_error',
                    sprintf(__('Invalid JSON response: %s', 'optica-vision-cl-sync'), json_last_error_msg())
                );
            }
            
            // The contact lenses endpoint returns a direct array, not wrapped in an object
            if (!is_array($data)) {
                return new WP_Error('api_invalid_data', __('Expected array response from contact lenses API', 'optica-vision-cl-sync'));
            }
            
            error_log(sprintf('Successfully retrieved %d contact lenses from API', count($data)));
            return $data;
            
        } catch (Exception $e) {
            error_log('Optica Vision CL API products exception: ' . $e->getMessage());
            return new WP_Error('api_exception', $e->getMessage());
        }
    }
    
    /**
     * Test API connection
     * 
     * @return array|WP_Error
     */
    public function test_connection() {
        // First ensure we have a valid token
        if (!$this->is_connected()) {
            $connect_result = $this->connect();
            if (is_wp_error($connect_result)) {
                return $connect_result;
            }
        }
        
        // Get a small sample of contact lenses to test
        $result = $this->get_contact_lenses();
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Return first 5 items as test data
        return array_slice($result, 0, 5);
    }
    
    /**
     * Get product comparison between API and WooCommerce
     */
    public function get_product_comparison() {
        // First test the API connection
        $test = $this->test_connection();
        if (is_wp_error($test)) {
            return $test;
        }
        
        // Get contact lenses from API
        $api_products = $this->get_contact_lenses();
        
        // Handle API errors
        if (is_wp_error($api_products)) {
            return $api_products;
        }
        
        // Get WooCommerce contact lens products
        $wc_products = $this->get_wc_contact_lens_products();
        
        // Prepare comparison data
        $new_products = [];
        $updated_products = [];
        $unchanged_products = [];
        
        // Return the comparison data
        return [
            'total_api' => count($api_products),
            'total_wc' => count($wc_products),
            'new_products' => $new_products,
            'updated_products' => $updated_products,
            'unchanged_products' => $unchanged_products,
            'api_products' => $api_products,
            'wc_products' => $wc_products
        ];
    }
    
    /**
     * Get WooCommerce contact lens products
     */
    private function get_wc_contact_lens_products() {
        $products = wc_get_products([
            'limit' => -1,
            'meta_key' => '_optica_vision_cl_sync',
            'meta_value' => true,
            'return' => 'ids'
        ]);
    
        $wc_products = [];
        foreach ($products as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $wc_products[$product->get_sku()] = [
                    'name' => $product->get_name(),
                    'type' => $product->get_type(),
                    'variations' => $product->get_type() === 'variable' ? count($product->get_children()) : 0
                ];
            }
        }
    
        return $wc_products;
    }
} 