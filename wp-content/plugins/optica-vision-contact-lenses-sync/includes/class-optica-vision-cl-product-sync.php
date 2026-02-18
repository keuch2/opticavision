<?php
/**
 * Contact Lenses Product Synchronization Class
 * 
 * Handles syncing contact lenses from Optica Vision API to WooCommerce Variable Products
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Optica_Vision_CL_Product_Sync {
    
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
        'variations' => 0,
        'skipped' => 0,
        'errors' => 0
    ];
    
    /**
     * Constructor
     * 
     * @param Optica_Vision_CL_API $api API instance
     */
    public function __construct($api) {
        $this->api = $api;
    }
    
    /**
     * Sync all contact lens products
     */
    public function sync_products() {
        // Increase execution time for large syncs
        if (function_exists('set_time_limit')) {
            @set_time_limit(300); // 5 minutes
        }
        
        // Increase memory limit if possible
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }
        
        // Reset statistics
        $this->stats = ['created' => 0, 'updated' => 0, 'variations' => 0, 'skipped' => 0, 'errors' => 0];
        
        error_log('[CL SYNC] Starting contact lens synchronization...');
        
        // Get contact lenses from API
        $contact_lenses = $this->api->get_contact_lenses();
        
        if (is_wp_error($contact_lenses)) {
            error_log('[CL SYNC] API Error: ' . $contact_lenses->get_error_message());
            return $contact_lenses;
        }
        
        if (empty($contact_lenses) || !is_array($contact_lenses)) {
            error_log('[CL SYNC] No products received from API');
            return new WP_Error('no_products', 'No contact lenses received from API');
        }
        
        error_log(sprintf('[CL SYNC] Processing %d contact lens items from API', count($contact_lenses)));
        
        // Log sample of first item structure for debugging
        if (!empty($contact_lenses[0])) {
            error_log('[CL SYNC] First item structure: ' . json_encode(array_keys($contact_lenses[0])));
            error_log('[CL SYNC] First item sample: ' . json_encode($contact_lenses[0]));
        }
        
        // Group products by base description (without prescription)
        $grouped_products = $this->group_products_by_base($contact_lenses);
        
        error_log(sprintf('[CL SYNC] Grouped into %d base products (skipped: %d)', count($grouped_products), $this->stats['skipped']));
        
        if (empty($grouped_products)) {
            error_log('[CL SYNC] No valid product groups after validation');
            return new WP_Error('no_valid_products', 'No valid contact lens products found after validation. Check API data structure.');
        }
        
        // Process each product group
        $processed = 0;
        foreach ($grouped_products as $base_sku => $group) {
            $processed++;
            error_log(sprintf('[CL SYNC] Processing group %d/%d: %s', $processed, count($grouped_products), $base_sku));
            
            try {
                $result = $this->sync_variable_product($base_sku, $group);
                
                if (is_wp_error($result)) {
                    $this->stats['errors']++;
                    error_log('[CL SYNC] Failed to sync product group ' . $base_sku . ': ' . $result->get_error_message());
                }
            } catch (Exception $e) {
                $this->stats['errors']++;
                error_log('[CL SYNC] Exception syncing product group ' . $base_sku . ': ' . $e->getMessage());
            }
            
            // Clear WooCommerce caches periodically to prevent memory issues
            if ($processed % 10 === 0) {
                wc_delete_product_transients();
                if (function_exists('wp_cache_flush')) {
                    wp_cache_flush();
                }
            }
        }
        
        // Store final sync results
        update_option('optica_vision_cl_last_sync', [
            'timestamp' => current_time('timestamp'),
            'stats' => $this->stats,
            'total_groups' => count($grouped_products)
        ]);
        
        error_log(sprintf('[CL SYNC] Sync completed. Created: %d, Updated: %d, Variations: %d, Skipped: %d, Errors: %d',
            $this->stats['created'],
            $this->stats['updated'],
            $this->stats['variations'],
            $this->stats['skipped'],
            $this->stats['errors']
        ));
        
        return $this->stats;
    }
    
    /**
     * Group contact lens products by their base description
     */
    public function group_products_by_base($contact_lenses) {
        $groups = [];
        
        foreach ($contact_lenses as $lens) {
            // Validate required fields exist
            if (!$this->validate_lens_data($lens)) {
                error_log('Skipping invalid lens data: ' . json_encode($lens));
                $this->stats['skipped']++;
                continue;
            }
            
            // Create a base product identifier
            $base_info = $this->extract_base_product_info($lens);
            $base_sku = $base_info['base_sku'];
            
            if (!isset($groups[$base_sku])) {
                $groups[$base_sku] = [
                    'base_info' => $base_info,
                    'variations' => []
                ];
            }
            
            $groups[$base_sku]['variations'][] = $lens;
        }
        
        return $groups;
    }
    
    /**
     * Validate lens data has all required fields
     */
    private function validate_lens_data($lens) {
        $required_fields = ['marca', 'descripcion', 'graduacion', 'codigo', 'precio', 'existencia'];
        
        foreach ($required_fields as $field) {
            if (!isset($lens[$field]) || $lens[$field] === null) {
                error_log("Missing required field '$field' in lens data");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Extract base product information from a contact lens item
     */
    private function extract_base_product_info($lens) {
        // Get brand and base description without prescription (with safe defaults)
        $brand = isset($lens['marca']) ? trim($lens['marca']) : 'Sin Marca';
        $description = isset($lens['descripcion']) ? trim($lens['descripcion']) : 'Lente de Contacto';
        
        // Remove prescription information from description to get base product name
        // The pattern is usually "Brand Name ... INCOLORO" or similar
        $base_description = $this->clean_description_for_base($description);
        
        // Create a unique base SKU
        $base_sku = 'CL_' . sanitize_title($brand . '_' . $base_description);
        
        return [
            'base_sku' => $base_sku,
            'name' => $brand . ' ' . $base_description,
            'brand' => $brand,
            'description' => $base_description,
            'raw_description' => $description
        ];
    }
    
    /**
     * Clean description to extract base product name
     */
    private function clean_description_for_base($description) {
        // Remove common patterns to get base description
        $cleaned = $description;
        
        // Remove "Simple visión Lentes de Co ME 8.60 14.0" type patterns
        $cleaned = preg_replace('/Simple visión Lentes de Co[^A-Z]*/', '', $cleaned);
        $cleaned = preg_replace('/ME \d+\.\d+ \d+\.\d+/', '', $cleaned);
        
        // Remove color information at the end
        $cleaned = preg_replace('/\s+(INCOLORO|AZUL|VERDE|GRIS)$/', '', $cleaned);
        
        // Clean up extra spaces
        $cleaned = preg_replace('/\s+/', ' ', trim($cleaned));
        
        // If cleaning resulted in empty string, use original
        if (empty($cleaned)) {
            $cleaned = $description;
        }
        
        return $cleaned;
    }
    
    /**
     * Sync a variable product with its variations
     * Returns array with stats or WP_Error
     */
    public function sync_variable_product($base_sku, $group) {
        try {
            $base_info = $group['base_info'];
            $variations = $group['variations'];
            
            // Find existing product by base SKU
            $existing_product_id = wc_get_product_id_by_sku($base_sku);
            
            if ($existing_product_id) {
                $result = $this->update_variable_product($existing_product_id, $base_info, $variations);
            } else {
                $result = $this->create_variable_product($base_info, $variations);
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            error_log('Exception in sync_variable_product: ' . $e->getMessage());
            return new WP_Error('sync_exception', $e->getMessage());
        }
    }
    
    /**
     * Create a new variable product with variations
     */
    private function create_variable_product($base_info, $variations) {
        try {
            // Create the variable product
            $product = new WC_Product_Variable();
            
            // Set basic product data
            $product->set_name($base_info['name']);
            $product->set_sku($base_info['base_sku']);
            $product->set_description($base_info['description']);
            $product->set_short_description($base_info['description']);
            $product->set_status('publish');
            $product->set_manage_stock(false); // Variations will manage their own stock
            
            // Set categories
            $category_ids = $this->get_or_create_categories($base_info['brand']);
            $product->set_category_ids($category_ids);
            
            // Add custom meta data for tracking
            $product->add_meta_data('_optica_vision_cl_sync', true);
            $product->add_meta_data('_optica_vision_cl_last_sync', current_time('timestamp'));
            
            // Save the variable product first
            $product_id = $product->save();
            
            if (!$product_id) {
                return new WP_Error('product_creation_failed', 'Failed to create variable product');
            }
            
            // Create prescription attribute if it doesn't exist
            optica_vision_cl_ensure_prescription_attribute();
            
            // Set product attributes
            $this->set_product_attributes($product_id, $variations);
            
            // Create variations
            $variation_count = $this->create_variations($product_id, $variations);
            
            // Update the variable product with price range
            $this->update_variable_product_price_range($product_id);
            
            $this->stats['created']++;
            $this->stats['variations'] += $variation_count;
            
            error_log(sprintf('Created variable product: %s (ID: %d) with %d variations', 
                $base_info['base_sku'], $product_id, $variation_count));
            
            return $product_id;
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            error_log('Failed to create variable product ' . $base_info['base_sku'] . ': ' . $e->getMessage());
            return new WP_Error('product_creation_exception', $e->getMessage());
        }
    }
    
    /**
     * Update an existing variable product
     */
    private function update_variable_product($product_id, $base_info, $variations) {
        try {
            $product = wc_get_product($product_id);
            if (!$product) {
                return new WP_Error('product_not_found', 'Product not found');
            }
            
            // Update basic product data
            $product->set_name($base_info['name']);
            $product->set_description($base_info['description']);
            $product->set_short_description($base_info['description']);
            
            // Update categories
            $category_ids = $this->get_or_create_categories($base_info['brand']);
            $product->set_category_ids($category_ids);
            
            // Update meta data
            $product->update_meta_data('_optica_vision_cl_last_sync', current_time('timestamp'));
            
            $product->save();
            
            // Update product attributes with all prescription terms from variations
            $this->set_product_attributes($product_id, $variations);
            
            // Update or create variations
            $variation_count = $this->sync_variations($product_id, $variations);
            
            // Update the variable product with price range
            $this->update_variable_product_price_range($product_id);
            
            $this->stats['updated']++;
            $this->stats['variations'] += $variation_count;
            
            error_log(sprintf('Updated variable product: %s (ID: %d) with %d variations', 
                $base_info['base_sku'], $product_id, $variation_count));
            
            return $product_id;
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            error_log('Failed to update variable product ' . $base_info['base_sku'] . ': ' . $e->getMessage());
            return new WP_Error('product_update_exception', $e->getMessage());
        }
    }
    

    
    /**
     * Set product attributes for variations - OPTIMIZED VERSION
     * Eliminates N+1 queries by not iterating over existing variations
     */
    private function set_product_attributes($product_id, $variations) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return false;
        }
        
        // Get all prescription values from API data (not from DB)
        $prescription_values = [];
        foreach ($variations as $variation_data) {
            $prescription_values[] = $variation_data['graduacion'];
        }
        $prescription_values = array_unique($prescription_values);
        
        // Batch create/get prescription terms - use cache to avoid repeated queries
        $prescription_terms = $this->batch_get_or_create_prescription_terms($prescription_values);
        
        error_log(sprintf('[CL SYNC PERF] Setting attributes for product %d with %d prescription values', 
            $product_id, count($prescription_values)));
        
        // Create the attribute for the product using proper WC_Product_Attribute class
        $attribute_taxonomy_name = 'pa_prescription';
        
        // Create a proper WooCommerce product attribute
        $attribute = new WC_Product_Attribute();
        
        // Get attribute ID - cache this value
        static $attribute_id = null;
        if ($attribute_id === null) {
            if (function_exists('wc_attribute_taxonomy_id_by_name')) {
                $attribute_id = wc_attribute_taxonomy_id_by_name('prescription');
            } else {
                global $wpdb;
                $result = $wpdb->get_var($wpdb->prepare(
                    "SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s",
                    'prescription'
                ));
                $attribute_id = $result ? (int) $result : 0;
            }
        }
        
        $attribute->set_id($attribute_id);
        $attribute->set_name($attribute_taxonomy_name);
        $attribute->set_options($prescription_terms);
        $attribute->set_position(0);
        $attribute->set_visible(true);
        $attribute->set_variation(true);
        
        // Set the attributes on the product
        $attributes = [];
        $attributes[$attribute_taxonomy_name] = $attribute;
        $product->set_attributes($attributes);
        
        // Set the attribute terms on the product
        wp_set_object_terms($product_id, $prescription_terms, $attribute_taxonomy_name);
        
        // Save the product
        $product->save();
        
        // OPTIMIZATION: Skip iterating over existing variations here
        // Term relationships will be set when creating/updating individual variations
        // This eliminates N+1 queries that were causing timeouts
        
        // OPTIMIZATION: Skip WC_Product_Variable::sync() during batch processing
        // It will be called once at the end via update_variable_product_price_range()
        // WC_Product_Variable::sync($product_id); // REMOVED - too expensive
        
        error_log(sprintf('[CL SYNC PERF] Attributes set for product %d with %d terms (optimized)', 
            $product_id, count($prescription_terms)));
        
        return true;
    }
    
    /**
     * Batch get or create prescription terms - OPTIMIZED
     * Reduces database queries by fetching all terms at once
     */
    private function batch_get_or_create_prescription_terms($prescription_values) {
        $taxonomy = 'pa_prescription';
        $term_ids = [];
        
        // Get all existing terms in one query
        $existing_terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'fields' => 'all'
        ]);
        
        // Build lookup arrays
        $terms_by_slug = [];
        $terms_by_name = [];
        if (!is_wp_error($existing_terms)) {
            foreach ($existing_terms as $term) {
                $terms_by_slug[$term->slug] = $term;
                $terms_by_name[$term->name] = $term;
            }
        }
        
        // Process each prescription value
        foreach ($prescription_values as $prescription) {
            $slug = sanitize_title($prescription);
            
            // Check if term exists
            if (isset($terms_by_slug[$slug])) {
                $term_ids[] = $terms_by_slug[$slug]->term_id;
            } elseif (isset($terms_by_name[$prescription])) {
                $term_ids[] = $terms_by_name[$prescription]->term_id;
            } else {
                // Create the term
                $result = wp_insert_term($prescription, $taxonomy, ['slug' => $slug]);
                if (!is_wp_error($result)) {
                    $term_ids[] = $result['term_id'];
                    // Add to cache for subsequent lookups
                    $terms_by_slug[$slug] = (object)['term_id' => $result['term_id'], 'slug' => $slug, 'name' => $prescription];
                }
            }
        }
        
        return $term_ids;
    }
    
    /**
     * Create variations for a product
     */
    private function create_variations($product_id, $variations) {
        $count = 0;
        
        foreach ($variations as $variation_data) {
            $variation_id = $this->create_single_variation($product_id, $variation_data);
            if ($variation_id && !is_wp_error($variation_id)) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Sync variations (update existing or create new) - OPTIMIZED VERSION
     * Uses direct database queries instead of N+1 wc_get_product() calls
     */
    private function sync_variations($product_id, $variations) {
        $count = 0;
        global $wpdb;
        
        // OPTIMIZATION: Get existing variations data with a single query instead of N+1
        $existing_by_sku = [];
        $existing_by_prescription = [];
        
        // Single query to get all variation IDs and their SKUs
        $variation_data = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID as variation_id, pm.meta_value as sku
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
            WHERE p.post_parent = %d AND p.post_type = 'product_variation'
        ", $product_id));
        
        foreach ($variation_data as $row) {
            if (!empty($row->sku)) {
                $existing_by_sku[$row->sku] = $row->variation_id;
            }
        }
        
        // Get prescription attributes for existing variations (single query)
        $prescription_data = $wpdb->get_results($wpdb->prepare("
            SELECT tr.object_id as variation_id, t.name as prescription
            FROM {$wpdb->term_relationships} tr
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
            WHERE tt.taxonomy = 'pa_prescription' 
            AND p.post_parent = %d 
            AND p.post_type = 'product_variation'
        ", $product_id));
        
        foreach ($prescription_data as $row) {
            if (!empty($row->prescription)) {
                $existing_by_prescription[$row->prescription] = $row->variation_id;
            }
        }
        
        error_log(sprintf('[CL SYNC PERF] Found %d existing variations by SKU, %d by prescription for product %d (optimized query)', 
            count($existing_by_sku), count($existing_by_prescription), $product_id));
        
        // Process each variation from API
        foreach ($variations as $variation_data) {
            $prescription = $variation_data['graduacion'];
            $sku = $variation_data['codigo'];
            
            $existing_variation_id = null;
            
            // First try to match by SKU (most reliable)
            if (isset($existing_by_sku[$sku])) {
                $existing_variation_id = $existing_by_sku[$sku];
                error_log("Found existing variation by SKU: $sku -> $existing_variation_id");
            }
            // Then try to match by prescription
            elseif (isset($existing_by_prescription[$prescription])) {
                $existing_variation_id = $existing_by_prescription[$prescription];
                error_log("Found existing variation by prescription: $prescription -> $existing_variation_id");
            }
            
            if ($existing_variation_id) {
                // Update existing variation
                $result = $this->update_single_variation($existing_variation_id, $variation_data);
                if ($result && !is_wp_error($result)) {
                    $count++;
                    error_log("Updated variation $existing_variation_id for prescription $prescription");
                }
            } else {
                // Create new variation - SKU conflict handling is now in create_single_variation
                error_log("[CL SYNC] Creating new variation for SKU $sku / prescription $prescription");
                $result = $this->create_single_variation($product_id, $variation_data);
                if ($result && !is_wp_error($result)) {
                    $count++;
                    $this->stats['variations']++;
                    error_log("[CL SYNC] Created new variation for prescription $prescription");
                } else {
                    $error_msg = is_wp_error($result) ? $result->get_error_message() : 'Unknown error';
                    error_log("[CL SYNC] Failed to create variation for $sku: $error_msg");
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Create a single variation
     */
    private function create_single_variation($product_id, $variation_data) {
        try {
            // Validate variation data
            $sku = isset($variation_data['codigo']) ? sanitize_text_field($variation_data['codigo']) : '';
            $price = isset($variation_data['precio']) ? floatval($variation_data['precio']) : 0;
            $stock = isset($variation_data['existencia']) ? floatval($variation_data['existencia']) : 0;
            $prescription = isset($variation_data['graduacion']) ? sanitize_text_field($variation_data['graduacion']) : '';
            
            if (empty($sku)) {
                error_log('Cannot create variation without SKU');
                return new WP_Error('missing_sku', 'Variation SKU is required');
            }
            
            // Check if SKU already exists
            $existing_id = wc_get_product_id_by_sku($sku);
            if ($existing_id) {
                error_log("SKU already exists: $sku (Product ID: $existing_id), updating instead");
                return $this->update_single_variation($existing_id, $variation_data);
            }
            
            $variation = new WC_Product_Variation();
            $variation->set_parent_id($product_id);
            
            // Set basic variation data
            $variation->set_sku($sku);
            $variation->set_regular_price($price);
            $variation->set_stock_quantity($stock);
            $variation->set_manage_stock(true);
            $variation->set_stock_status($stock > 0 ? 'instock' : 'outofstock');
            
            // Set prescription attribute
            $prescription_attr = 'pa_prescription'; // WooCommerce attribute taxonomy naming convention
            $prescription_value = $prescription;
            
            // Create or get prescription term
            $term = $this->get_or_create_prescription_term($prescription_value);
            
            if ($term && !is_wp_error($term)) {
                // Set the attribute using the term slug
                $variation->set_attributes([
                    $prescription_attr => $term->slug
                ]);
                
                error_log(sprintf('Setting variation attribute: %s = %s (term ID: %d)', 
                    $prescription_attr, $term->slug, $term->term_id));
            } else {
                error_log('Failed to get/create prescription term for: ' . $prescription_value);
                if (is_wp_error($term)) {
                    error_log('Term error: ' . $term->get_error_message());
                }
                return new WP_Error('term_creation_failed', 'Failed to create prescription term');
            }
            
            // Add variation meta
            $variation->add_meta_data('_optica_vision_cl_sync', true);
            $variation->add_meta_data('_optica_vision_cl_last_sync', current_time('timestamp'));
            $variation->add_meta_data('_optica_vision_cl_raw_data', json_encode($variation_data));
            
            $variation_id = $variation->save();
            
            if ($variation_id) {
                // Set the term relationship for the variation after it's saved
                if ($term && !is_wp_error($term)) {
                    wp_set_object_terms($variation_id, $term->term_id, $prescription_attr, false);
                }
                
                // OPTIMIZATION: Removed wc_get_product() call here - it was only for logging
                // and caused unnecessary database queries
                error_log(sprintf('[CL SYNC PERF] Created variation: %s (ID: %d) for prescription %s', 
                    $variation_data['codigo'], $variation_id, $variation_data['graduacion']));
                
                return $variation_id;
            }
            
            return new WP_Error('variation_creation_failed', 'Failed to create variation');
            
        } catch (Exception $e) {
            error_log('Failed to create variation: ' . $e->getMessage());
            return new WP_Error('variation_creation_exception', $e->getMessage());
        }
    }
    
    /**
     * Update a single variation
     */
    private function update_single_variation($variation_id, $variation_data) {
        try {
            $variation = wc_get_product($variation_id);
            if (!$variation) {
                return new WP_Error('variation_not_found', 'Variation not found');
            }
            
            // Update variation data
            $variation->set_regular_price($variation_data['precio']);
            $variation->set_stock_quantity(floatval($variation_data['existencia']));
            $variation->set_stock_status(floatval($variation_data['existencia']) > 0 ? 'instock' : 'outofstock');
            
            // Set prescription attribute if not already set
            $prescription_attr = 'pa_prescription';
            $prescription_value = $variation_data['graduacion'];
            
            // Create or get prescription term
            $term = $this->get_or_create_prescription_term($prescription_value);
            
            if ($term && !is_wp_error($term)) {
                // Set the attribute using the term slug
                $variation->set_attributes([
                    $prescription_attr => $term->slug
                ]);
                
                error_log(sprintf('Set prescription attribute for variation %d: %s = %s', 
                    $variation_id, $prescription_attr, $term->slug));
            } else {
                error_log('Failed to get/create prescription term for variation update: ' . $prescription_value);
            }
            
            // Update meta data
            $variation->update_meta_data('_optica_vision_cl_last_sync', current_time('timestamp'));
            $variation->update_meta_data('_optica_vision_cl_raw_data', json_encode($variation_data));
            
            $variation->save();
            
            // Set the term relationship for the variation after it's saved
            if ($term && !is_wp_error($term)) {
                wp_set_object_terms($variation_id, $term->term_id, $prescription_attr, false);
            }
            
            error_log(sprintf('Updated variation: %s for prescription %s', 
                $variation_data['codigo'], $variation_data['graduacion']));
            
            return $variation_id;
            
        } catch (Exception $e) {
            error_log('Failed to update variation: ' . $e->getMessage());
            return new WP_Error('variation_update_exception', $e->getMessage());
        }
    }
    
    /**
     * Get or create prescription term
     */
    private function get_or_create_prescription_term($prescription) {
        $taxonomy = 'pa_prescription'; // WooCommerce attribute taxonomy naming convention
        
        // Ensure taxonomy exists
        if (!taxonomy_exists($taxonomy)) {
            error_log("Prescription taxonomy does not exist: $taxonomy");
            return new WP_Error('taxonomy_missing', "Prescription taxonomy does not exist: $taxonomy");
        }
        
        // Check if term exists by slug first (more reliable)
        $slug = sanitize_title($prescription);
        $term = get_term_by('slug', $slug, $taxonomy);
        
        if (!$term) {
            // Check by name as fallback
            $term = get_term_by('name', $prescription, $taxonomy);
        }
        
        if (!$term) {
            // Create the term
            $result = wp_insert_term($prescription, $taxonomy, [
                'slug' => $slug
            ]);
            
            if (is_wp_error($result)) {
                error_log('Failed to create prescription term "' . $prescription . '": ' . $result->get_error_message());
                return $result;
            }
            
            $term = get_term($result['term_id'], $taxonomy);
            error_log('Created prescription term: ' . $prescription . ' (ID: ' . $term->term_id . ')');
        } else {
            error_log('Found existing prescription term: ' . $prescription . ' (ID: ' . $term->term_id . ')');
        }
        
        return $term;
    }
    
    /**
     * Update variable product price range
     */
    private function update_variable_product_price_range($product_id) {
        $product = wc_get_product($product_id);
        if ($product && $product->is_type('variable')) {
            // Force WooCommerce to recalculate price range
            $product->variable_product_sync();
        }
    }
    
    /**
     * Get or create product categories
     */
    private function get_or_create_categories($brand) {
        $category_ids = [];
        
        // Main contact lenses category
        $main_cat = $this->get_or_create_category('Lentes de Contacto', 0);
        if ($main_cat) {
            $category_ids[] = $main_cat->term_id;
            
            // Brand subcategory
            $brand_cat = $this->get_or_create_category($brand, $main_cat->term_id);
            if ($brand_cat) {
                $category_ids[] = $brand_cat->term_id;
            }
        }
        
        return $category_ids;
    }
    
    /**
     * Get or create a single category
     */
    private function get_or_create_category($name, $parent_id = 0) {
        $term = get_term_by('name', $name, 'product_cat');
        
        if (!$term) {
            $result = wp_insert_term($name, 'product_cat', [
                'parent' => $parent_id,
                'slug' => sanitize_title($name)
            ]);
            
            if (is_wp_error($result)) {
                error_log('Failed to create category: ' . $result->get_error_message());
                return null;
            }
            
            $term = get_term($result['term_id'], 'product_cat');
        }
        
        return $term;
    }
} 