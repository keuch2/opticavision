<?php
/**
 * Optica Vision API Class
 * 
 * Handles all API interactions with the Optica Vision API
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Optica_Vision_API {
    /**
     * Authentication token
     */
    private $token = null;
    
    /**
     * Request timeout (seconds)
     */
    private $timeout = 30;
    
    /**
     * Maximum retry attempts
     */
    private $max_retries = 3;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Get stored token
        $this->token = get_option('optica_vision_api_token');
    }
    
    /**
     * Get API base URL from options
     */
    private function get_api_url() {
        return get_option('optica_vision_api_url', 'http://190.104.159.90:8081');
    }
    
    /**
     * Get API credentials from options
     */
    private function get_credentials() {
        return [
            'username' => get_option('optica_vision_api_username', 'userweb'),
            'password' => get_option('optica_vision_api_password', 'us34.w38')
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
            return new WP_Error('missing_credentials', __('API credentials not configured', 'optica-vision-api-sync'));
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
                error_log('Optica Vision API login error: ' . $response->get_error_message());
                return new WP_Error(
                    'api_connection_failed', 
                    sprintf(__('Failed to connect to API: %s', 'optica-vision-api-sync'), $response->get_error_message())
                );
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            // Log response for debugging
            error_log(sprintf('API login response [%d]: %s', $response_code, substr($body, 0, 200)));
            
            if ($response_code !== 200) {
                return new WP_Error(
                    'api_http_error',
                    sprintf(__('API login returned status code %d', 'optica-vision-api-sync'), $response_code)
                );
            }
            
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Optica Vision API JSON decode error: ' . json_last_error_msg());
                error_log('Optica Vision API response: ' . substr($body, 0, 200));
                return new WP_Error(
                    'api_json_error',
                    sprintf(__('Invalid JSON response: %s', 'optica-vision-api-sync'), json_last_error_msg())
                );
            }
            
            if (isset($data['token'])) {
                $this->token = $data['token'];
                update_option('optica_vision_api_token', $this->token);
                error_log('Optica Vision API: Successfully obtained token');
                return true;
            }
            
            return new WP_Error('api_auth_failed', __('Authentication failed - no token received', 'optica-vision-api-sync'));
            
        } catch (Exception $e) {
            error_log('Optica Vision API exception: ' . $e->getMessage());
            return new WP_Error('api_exception', $e->getMessage());
        }
    }
    
    /**
     * Legacy method name for backward compatibility
     */
    public function manual_login() {
        return $this->connect();
    }
    
    /**
     * Force reconnection by clearing current token
     * 
     * @return bool|WP_Error
     */
    public function force_reconnect() {
        // Clear existing token
        $this->token = null;
        delete_option('optica_vision_api_token');
        
        // Attempt new connection
        return $this->connect();
    }
    
    /**
     * Get products from API with enhanced error handling
     * 
     * @param int $page Page number
     * @param int $per_page Items per page
     * @return array|WP_Error
     */
    public function get_products($page = 1, $per_page = 50) {
            if (!$this->is_connected()) {
                return new WP_Error('api_not_authenticated', 'Not authenticated with API');
            }
            
        try {
            $url = trailingslashit($this->get_api_url()) . 'mercaderias_web';
            
            $response = wp_remote_post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->token
                ],
                'body' => json_encode([
                    'page' => absint($page),
                    'per_page' => absint($per_page)
                ]),
                'timeout' => $this->timeout,
                'sslverify' => false
            ]);
            
            if (is_wp_error($response)) {
                error_log('Optica Vision API products error: ' . $response->get_error_message());
                return $response;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            // Log response for debugging (truncated)
            error_log(sprintf('API mercaderias_web response [%d]: %s', $response_code, substr($body, 0, 200)));
            
            // Handle 401 authentication errors by attempting to reconnect
            if ($response_code === 401) {
                error_log('Optica Vision API: Received 401, attempting to reconnect...');
                
                // Clear the invalid token
                $this->token = null;
                delete_option('optica_vision_api_token');
                
                // Try to reconnect
                $reconnect_result = $this->connect();
                if (is_wp_error($reconnect_result)) {
                    error_log('Optica Vision API: Reconnection failed: ' . $reconnect_result->get_error_message());
                    return new WP_Error(
                        'api_reconnect_failed',
                        sprintf(__('API authentication expired and reconnection failed: %s', 'optica-vision-api-sync'), $reconnect_result->get_error_message())
                    );
                }
                
                // Retry the original request with new token
                error_log('Optica Vision API: Reconnected successfully, retrying request...');
                $response = wp_remote_post($url, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer ' . $this->token
                    ],
                    'body' => json_encode([
                        'page' => absint($page),
                        'per_page' => absint($per_page)
                    ]),
                    'timeout' => $this->timeout,
                    'sslverify' => false
                ]);
                
                if (is_wp_error($response)) {
                    error_log('Optica Vision API products retry error: ' . $response->get_error_message());
                    return $response;
                }
                
                $response_code = wp_remote_retrieve_response_code($response);
                $body = wp_remote_retrieve_body($response);
                
                error_log(sprintf('API mercaderias_web retry response [%d]: %s', $response_code, substr($body, 0, 200)));
            }
            
            if ($response_code !== 200) {
                return new WP_Error(
                    'api_http_error',
                    sprintf(__('API returned status code %d', 'optica-vision-api-sync'), $response_code)
                );
            }
            
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Optica Vision API JSON decode error: ' . json_last_error_msg());
                error_log('Optica Vision API response: ' . substr($body, 0, 200));
                return new WP_Error(
                    'api_json_error',
                    sprintf(__('Invalid JSON response: %s', 'optica-vision-api-sync'), json_last_error_msg())
                );
            }
            
            // Normalize response format
            if (isset($data[0]) && is_array($data[0])) {
                // Direct array of products
                return [
                    'items' => $this->validate_products($data),
                    'total' => count($data),
                    'page' => $page,
                    'per_page' => $per_page
                ];
            }
            
            // Structured response
            return [
                'items' => $this->validate_products($data['items'] ?? []),
                'total' => $data['total'] ?? count($data['items'] ?? []),
                'page' => $page,
                'per_page' => $per_page
            ];
            
        } catch (Exception $e) {
            error_log('Optica Vision API products exception: ' . $e->getMessage());
            return new WP_Error('api_exception', $e->getMessage());
        }
    }
    
    /**
     * Validate product data structure
     * 
     * @param array $products
     * @return array
     */
    private function validate_products($products) {
        if (!is_array($products)) {
            return [];
        }
        
        $validated = [];
        foreach ($products as $product) {
            if ($this->is_valid_product($product)) {
                $validated[] = $this->sanitize_product($product);
            } else {
                error_log('Optica Vision API: Invalid product data: ' . json_encode($product));
            }
        }
        
        return $validated;
    }
    
    /**
     * Check if product data is valid
     * 
     * @param mixed $product
     * @return bool
     */
    private function is_valid_product($product) {
        if (!is_array($product)) {
            return false;
        }
        
        $required_fields = ['codigo', 'descripcion', 'precio'];
        foreach ($required_fields as $field) {
            if (!isset($product[$field]) || empty($product[$field])) {
                return false;
            }
        }
        
        // Validate price is numeric
        if (!is_numeric($product['precio'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitize product data
     * 
     * @param array $product
     * @return array
     */
    private function sanitize_product($product) {
        return [
            'codigo' => sanitize_text_field($product['codigo']),
            'descripcion' => sanitize_text_field($product['descripcion']),
            'precio' => floatval($product['precio']),
            'existencia' => isset($product['existencia']) ? absint($product['existencia']) : 0,
            'marca' => isset($product['marca']) ? sanitize_text_field($product['marca']) : '',
        ];
    }
    
    /**
     * Get all products from API with pagination
     * 
     * @return array|WP_Error
     */
    public function get_all_products() {
        $products = [];
        $page = 1;
        $per_page = 50;
        
        do {
            $response = $this->get_products($page, $per_page);
            
            if (is_wp_error($response)) {
                return $response;
            }
            
            // Extract items from response
            $items = $response['items'] ?? [];
            if (empty($items)) {
                break;
            }
            
            $products = array_merge($products, $items);
            $page++;
            
            // Stop if we got fewer items than requested (last page)
        } while (count($items) === $per_page);
        
        return [
            'items' => $products,
            'total' => count($products)
        ];
    }
    
    /**
     * Test API connection with small data sample
     */
    public function test_connection() {
        return $this->get_products(1, 5); // Only fetch 5 items for testing
    }
    
    /**
     * Get product comparison data
     */
    public function get_product_comparison() {
        // First test the API connection
        $test = $this->test_connection();
        if (is_wp_error($test)) {
            return $test;
        }
        
        // Get products from API
        $api_products = $this->get_products(1, 50); // Limit to 50 products for comparison
        
        // Handle API errors
        if (is_wp_error($api_products)) {
            return $api_products;
        }
        
        // Extract items from the response
        $api_items = isset($api_products['items']) ? $api_products['items'] : [];
        
        // Get WooCommerce products
        $wc_products = $this->get_wc_products();
        
        // Prepare comparison data
        $new_products = [];
        $updated_products = [];
        $unchanged_products = [];
        
        // Return the comparison data
        return [
            'total_api' => count($api_items),
            'total_wc' => count($wc_products),
            'new_products' => $new_products,
            'updated_products' => $updated_products,
            'unchanged_products' => $unchanged_products,
            'api_products' => $api_items,
            'wc_products' => $wc_products
        ];
    }
    
    private function get_wc_products() {
        $products = wc_get_products([
            'limit' => -1,
            'return' => 'ids'
        ]);
    
        $wc_products = [];
        foreach ($products as $product_id) {
            $product = wc_get_product($product_id);
            $wc_products[$product->get_sku()] = [
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'stock' => $product->get_stock_quantity()
            ];
        }
    
        return $wc_products;
    }
    
    private function product_changed($api_product, $wc_product) {
        return (
            $api_product['descripcion'] != $wc_product['name'] ||
            $api_product['precio'] != $wc_product['price'] ||
            $api_product['existencia'] != $wc_product['stock']
        );
    }
    
    public function handle_get_products() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!$this->is_connected()) {
            wp_send_json_error('Not connected to API');
        }
        
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : null;
        
        try {
            $products = $this->get_all_products();
            
            if (is_wp_error($products)) {
                throw new Exception($products->get_error_message());
            }
            
            // Apply limit if specified
            if ($limit && $limit > 0) {
                $products = array_slice($products, 0, $limit);
            }
            
            wp_send_json_success($products);
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    public function is_filesystem_writable() {
        // Try multiple locations to find a writable directory
        $test_locations = [
            wp_upload_dir()['basedir'],
            WP_CONTENT_DIR,
            get_temp_dir()
        ];
        
        foreach ($test_locations as $location) {
            if (!file_exists($location)) {
                continue;
            }
            
            $test_file = trailingslashit($location) . 'optica_test_' . time() . '.tmp';
            
            if (@file_put_contents($test_file, 'test') !== false) {
                @unlink($test_file);
                return true;
            }
        }
        
        // If we get here, we couldn't write to any of the test locations
        return false;
    }
    
    /**
     * Get a writable directory for temporary files
     * 
     * @return string Path to writable directory
     */
    public function get_writable_dir() {
        $test_locations = [
            wp_upload_dir()['basedir'] . '/optica-cache',
            WP_CONTENT_DIR . '/cache/optica-vision',
            get_temp_dir()
        ];
        
        foreach ($test_locations as $location) {
            if (!file_exists($location)) {
                wp_mkdir_p($location);
            }
            
            if (is_writable($location)) {
                return trailingslashit($location);
            }
        }
        
        // Fallback to system temp directory
        return trailingslashit(get_temp_dir());
    }
}
