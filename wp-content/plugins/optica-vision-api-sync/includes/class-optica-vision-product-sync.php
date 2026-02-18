<?php
/**
 * Product Synchronization Class
 * 
 * Handles syncing products from Optica Vision API to WooCommerce
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Optica_Vision_Product_Sync {
    
    /**
     * API instance
     */
    private $api;
    
    /**
     * Sync statistics
     */
    private $stats = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0
    ];
    
    /**
     * Constructor
     * 
     * @param Optica_Vision_API $api API instance
     */
    public function __construct($api) {
        $this->api = $api;
    }
    
    /**
     * Sync all products with enhanced error handling and backup
     */
    public function sync_products() {
        // Reset statistics
        $this->stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];
        
        // Create backup before sync
        $backup_result = $this->create_backup();
        if (is_wp_error($backup_result)) {
            error_log('Failed to create backup: ' . $backup_result->get_error_message());
            // Continue with sync despite backup failure
        }
        
        // Get products from API
        $products_response = $this->api->get_all_products();
        
        if (is_wp_error($products_response)) {
            return $products_response;
        }
        
        $products = isset($products_response['items']) ? $products_response['items'] : $products_response;
        
        if (empty($products) || !is_array($products)) {
            return new WP_Error('no_products', 'No products received from API');
        }
        
        // Process products in batches to avoid memory issues
        $batch_size = 25;
        $total_products = count($products);
        $processed = 0;
        
        for ($i = 0; $i < $total_products; $i += $batch_size) {
            $batch = array_slice($products, $i, $batch_size);
            
            foreach ($batch as $product_data) {
                $result = $this->sync_product($product_data);
                $processed++;
                
                // Log progress every 50 products
                if ($processed % 50 === 0) {
                    error_log(sprintf(
                        'Sync progress: %d/%d products processed. Created: %d, Updated: %d, Errors: %d',
                        $processed,
                        $total_products,
                        $this->stats['created'],
                        $this->stats['updated'],
                        $this->stats['errors']
                    ));
                }
            }
            
            // Brief pause between batches to prevent overwhelming the server
            if ($i + $batch_size < $total_products) {
                usleep(100000); // 0.1 second pause
            }
        }
        
        // Store final sync results
        update_option('optica_vision_last_sync', [
            'timestamp' => current_time('timestamp'),
            'stats' => $this->stats,
            'total_products' => $total_products
        ]);
        
        return $this->stats;
    }
    
    /**
     * Sync individual product with enhanced validation and conflict resolution
     */
    public function sync_product($product_data) {
        try {
            // Validate product data
            $validation_result = $this->validate_product_data($product_data);
            if (is_wp_error($validation_result)) {
                $this->stats['errors']++;
                error_log('Product validation failed: ' . $validation_result->get_error_message());
                return $validation_result;
            }
            
            // Extract and process product information
            $product_info = $this->process_product_data($product_data);
            
            // Find existing product by SKU
            $existing_product_id = wc_get_product_id_by_sku($product_data['codigo']);
            
            if ($existing_product_id) {
                return $this->update_existing_product($existing_product_id, $product_info, $product_data);
            } else {
                return $this->create_new_product($product_info, $product_data);
            }
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            error_log('Exception in sync_product: ' . $e->getMessage());
            return new WP_Error('sync_exception', $e->getMessage());
        }
    }
    
    /**
     * Validate product data before processing
     */
    private function validate_product_data($product_data) {
        if (!is_array($product_data)) {
            return new WP_Error('invalid_product_format', 'Product data must be an array');
        }
        
        $required_fields = ['codigo', 'descripcion', 'precio'];
        foreach ($required_fields as $field) {
            if (!isset($product_data[$field]) || empty($product_data[$field])) {
                return new WP_Error('missing_required_field', sprintf('Missing required field: %s', $field));
            }
        }
        
        // Validate SKU format
        if (!preg_match('/^[A-Za-z0-9\-_]+$/', $product_data['codigo'])) {
            return new WP_Error('invalid_sku_format', 'SKU contains invalid characters');
        }
        
        // Validate price
        if (!is_numeric($product_data['precio']) || floatval($product_data['precio']) < 0) {
            return new WP_Error('invalid_price', 'Price must be a positive number');
        }
        
        // Validate stock if present
        if (isset($product_data['existencia']) && !is_numeric($product_data['existencia'])) {
            return new WP_Error('invalid_stock', 'Stock must be numeric');
        }
        
        return true;
    }
    
    /**
     * Process and clean product data
     */
    private function process_product_data($product_data) {
        // Extract product type from first 2 characters
        $tipo = substr($product_data['descripcion'], 0, 2);
        $description = trim(substr($product_data['descripcion'], 2));
        
        // Get brand from marca field
        $brand = !empty($product_data['marca']) ? trim($product_data['marca']) : 'Sin Marca';
        
        // Product type mapping
        $tipo_names = [
            'AR' => 'Armazón',
            'AS' => 'Lentes de Sol', 
            'LC' => 'Lentes de Contacto',
            'AC' => 'Accesorios',
            'LP' => 'Línea Premium',
            'LT' => 'Lentes de Lectura',
            'LD' => 'Lentes Deportivos',
            'NI' => 'Niños',
            'PR' => 'Promociones',
            'OF' => 'Ofertas',
            'NO' => 'Novedades',
            'MA' => 'Marca Blanca',
            'ES' => 'Especiales',
            'CO' => 'Colecciones',
            'TE' => 'Tendencias',
            'EX' => 'Exclusivos',
            'LI' => 'Línea Institucional',
            'PA' => 'Paquetes',
            'SE' => 'Servicios',
            'OT' => 'Otros'
        ];
        
        $tipo_name = $tipo_names[$tipo] ?? 'Otros';
        
        return [
            'sku' => sanitize_text_field($product_data['codigo']),
            'name' => sanitize_text_field($description),
            'description' => sanitize_textarea_field($description),
            'price' => floatval($product_data['precio']),
            'stock' => isset($product_data['existencia']) ? absint($product_data['existencia']) : 0,
            'brand' => sanitize_text_field($brand),
            'type' => sanitize_text_field($tipo_name),
            'raw_description' => sanitize_text_field($product_data['descripcion'])
        ];
    }
    
    /**
     * Create new product
     */
    private function create_new_product($product_info, $raw_data) {
        try {
            $product = new WC_Product();
            
            // Set basic product data
            $product->set_name($product_info['name']);
            $product->set_sku($product_info['sku']);
            $product->set_description($product_info['description']);
            $product->set_short_description($product_info['description']);
            $product->set_regular_price($product_info['price']);
            $product->set_stock_quantity($product_info['stock']);
            $product->set_manage_stock(true);
            $product->set_stock_status($product_info['stock'] > 0 ? 'instock' : 'outofstock');
            
            // Set categories
            $category_ids = $this->get_or_create_categories($product_info['brand'], $product_info['type']);
            $product->set_category_ids($category_ids);
        
            // Add custom meta data for tracking
            $product->add_meta_data('_optica_vision_sync', true);
            $product->add_meta_data('_optica_vision_last_sync', current_time('timestamp'));
            $product->add_meta_data('_optica_vision_raw_data', json_encode($raw_data));
            
            // Save product
            $product_id = $product->save();
            
            if ($product_id) {
                $this->stats['created']++;
                error_log(sprintf('Created product: %s (ID: %d)', $product_info['sku'], $product_id));
                return $product_id;
            } else {
                $this->stats['errors']++;
                return new WP_Error('product_creation_failed', 'Failed to create product');
            }
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            error_log('Failed to create product ' . $product_info['sku'] . ': ' . $e->getMessage());
            return new WP_Error('product_creation_exception', $e->getMessage());
        }
    }
    
    /**
     * Update existing product with conflict resolution
     */
    private function update_existing_product($product_id, $product_info, $raw_data) {
        try {
            $product = wc_get_product($product_id);
            if (!$product) {
                $this->stats['errors']++;
                return new WP_Error('product_not_found', 'Product not found');
            }
            
            // Check if product needs updating
            $needs_update = $this->product_needs_update($product, $product_info);
            
            if (!$needs_update) {
                $this->stats['skipped']++;
                return 'skipped';
            }
            
            // Handle conflicts (e.g., manual price changes)
            $conflict_resolution = $this->resolve_conflicts($product, $product_info);
            if (is_wp_error($conflict_resolution)) {
                $this->stats['errors']++;
                return $conflict_resolution;
            }
            
            // Update product data
            $product->set_name($product_info['name']);
            $product->set_description($product_info['description']);
            $product->set_short_description($product_info['description']);
            
            // Only update price if no manual override
            if (!get_post_meta($product_id, '_optica_vision_price_override', true)) {
                $product->set_regular_price($product_info['price']);
            }
            
            // Update stock
            $product->set_stock_quantity($product_info['stock']);
            $product->set_stock_status($product_info['stock'] > 0 ? 'instock' : 'outofstock');
            
            // Update categories
            $category_ids = $this->get_or_create_categories($product_info['brand'], $product_info['type']);
            $product->set_category_ids($category_ids);
        
            // Update meta data
            $product->update_meta_data('_optica_vision_last_sync', current_time('timestamp'));
            $product->update_meta_data('_optica_vision_raw_data', json_encode($raw_data));
            
            $product->save();
            
            $this->stats['updated']++;
            error_log(sprintf('Updated product: %s (ID: %d)', $product_info['sku'], $product_id));
            return $product_id;
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            error_log('Failed to update product ' . $product_info['sku'] . ': ' . $e->getMessage());
            return new WP_Error('product_update_exception', $e->getMessage());
        }
    }
    
    /**
     * Check if product needs updating
     */
    private function product_needs_update($product, $product_info) {
        // Compare key fields
        if ($product->get_name() !== $product_info['name']) return true;
        if ($product->get_description() !== $product_info['description']) return true;
        if (floatval($product->get_regular_price()) !== $product_info['price']) return true;
        if (intval($product->get_stock_quantity()) !== $product_info['stock']) return true;
        
        return false;
    }
    
    /**
     * Resolve conflicts between API data and existing product data
     */
    private function resolve_conflicts($product, $product_info) {
        $product_id = $product->get_id();
        
        // Check for manual price overrides
        $current_price = floatval($product->get_regular_price());
        $api_price = $product_info['price'];
        
        // If price differs significantly and was manually changed, flag it
        if (abs($current_price - $api_price) > 0.01) {
            $last_api_price = get_post_meta($product_id, '_optica_vision_last_api_price', true);
            
            if ($last_api_price && abs(floatval($last_api_price) - $current_price) > 0.01) {
                // Price was manually changed, don't override
                update_post_meta($product_id, '_optica_vision_price_override', true);
                error_log(sprintf('Price override detected for product %s. Manual: %s, API: %s', 
                    $product->get_sku(), $current_price, $api_price));
            }
        }
        
        // Store current API price for future conflict detection
        update_post_meta($product_id, '_optica_vision_last_api_price', $api_price);
        
        return true;
    }
    
    /**
     * Get or create categories with caching
     */
    private function get_or_create_categories($brand, $type) {
        static $category_cache = [];
        
        $cache_key = $brand . '|' . $type;
        if (isset($category_cache[$cache_key])) {
            return $category_cache[$cache_key];
        }
        
        $category_ids = [];
        
        // Create/get brand category
        $brand_category_id = $this->get_or_create_category($brand, 'Marcas');
        if ($brand_category_id) {
            $category_ids[] = $brand_category_id;
        }
        
        // Create/get type category
        $type_category_id = $this->get_or_create_category($type, 'Tipos');
        if ($type_category_id) {
            $category_ids[] = $type_category_id;
        }
        
        $category_cache[$cache_key] = $category_ids;
        return $category_ids;
    }
    
    /**
     * Get or create category with parent
     */
    private function get_or_create_category($name, $parent_name = '') {
        $parent_id = 0;
        
        if ($parent_name) {
            $parent_term = term_exists($parent_name, 'product_cat');
            if (!$parent_term) {
                $parent_term = wp_insert_term($parent_name, 'product_cat');
                if (is_wp_error($parent_term)) {
                    error_log('Failed to create parent category: ' . $parent_term->get_error_message());
                    return false;
                }
            }
            $parent_id = is_array($parent_term) ? $parent_term['term_id'] : $parent_term;
        }
        
        $term = term_exists($name, 'product_cat', $parent_id);
        if (!$term) {
            $term = wp_insert_term($name, 'product_cat', [
                'parent' => $parent_id
            ]);
            if (is_wp_error($term)) {
                error_log('Failed to create category: ' . $term->get_error_message());
                return false;
            }
        }
        
        return is_array($term) ? $term['term_id'] : $term;
    }
    
    /**
     * Create backup of current products
     */
    private function create_backup() {
        try {
            $backup_data = [];
            
            // Get all WooCommerce products
            $products = wc_get_products([
                'limit' => -1,
                'meta_key' => '_optica_vision_sync',
                'meta_value' => true
            ]);
            
            foreach ($products as $product) {
                $backup_data[] = [
                    'id' => $product->get_id(),
                    'sku' => $product->get_sku(),
                    'name' => $product->get_name(),
                    'price' => $product->get_regular_price(),
                    'stock' => $product->get_stock_quantity(),
                    'meta' => get_post_meta($product->get_id())
                ];
            }
            
            // Store backup with timestamp
            $backup_key = 'optica_vision_backup_' . current_time('timestamp');
            update_option($backup_key, $backup_data);
            
            // Keep only last 5 backups
            $this->cleanup_old_backups();
            
            error_log(sprintf('Created backup with %d products', count($backup_data)));
            return true;
            
        } catch (Exception $e) {
            return new WP_Error('backup_failed', $e->getMessage());
        }
    }
    
    /**
     * Cleanup old backups
     */
    private function cleanup_old_backups() {
        global $wpdb;
        
        $backup_options = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE 'optica_vision_backup_%' 
             ORDER BY option_name DESC"
        );
        
        // Keep only the 5 most recent backups
        if (count($backup_options) > 5) {
            $to_delete = array_slice($backup_options, 5);
            foreach ($to_delete as $option) {
                delete_option($option->option_name);
            }
        }
    }
    
    /**
     * Get sync statistics
     */
    public function get_stats() {
        return $this->stats;
    }
}
