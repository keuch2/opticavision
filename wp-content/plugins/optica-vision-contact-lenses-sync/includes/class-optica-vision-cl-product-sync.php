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
     * Prescription term cache to avoid repeated DB queries
     * @var array [slug => term_object]
     */
    private $prescription_term_cache = [];
    
    /**
     * Whether term cache has been populated
     * @var bool
     */
    private $term_cache_loaded = false;
    
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
            @set_time_limit(600); // 10 minutes
        }
        
        // Increase memory limit if possible
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }
        
        // Pre-load prescription term cache for the entire sync
        $this->load_prescription_term_cache();
        
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
        
        // Defer term counting during bulk processing (safe)
        wp_defer_term_counting(true);
        // NOTE: wp_suspend_cache_invalidation removed - causes PHP segfault with WC hooks
        
        // Process each product group
        $processed = 0;
        $total_groups = count($grouped_products);
        foreach ($grouped_products as $base_sku => $group) {
            $processed++;
            if ($processed % 5 === 1 || $processed === $total_groups) {
                error_log(sprintf('[CL SYNC] Processing group %d/%d: %s', $processed, $total_groups, $base_sku));
            }
            
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
            if ($processed % 20 === 0) {
                wc_delete_product_transients();
                wp_cache_flush();
                error_log(sprintf('[CL SYNC] Progress: %d/%d groups processed', $processed, $total_groups));
            }
        }
        
        // Re-enable deferred operations
        wp_defer_term_counting(false);
        
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
                // Only log first few skipped items to avoid log spam
                if ($this->stats['skipped'] < 3) {
                    error_log('[CL SYNC] Skipping invalid lens data: ' . json_encode($lens));
                }
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
            error_log(sprintf('[CL SYNC] sync_variable_product: looking up SKU %s', $base_sku));
            $existing_product_id = wc_get_product_id_by_sku($base_sku);
            
            if ($existing_product_id) {
                error_log(sprintf('[CL SYNC] Found existing product ID %d for SKU %s, updating...', $existing_product_id, $base_sku));
                $result = $this->update_variable_product($existing_product_id, $base_info, $variations);
            } else {
                error_log(sprintf('[CL SYNC] No existing product for SKU %s, creating...', $base_sku));
                $result = $this->create_variable_product($base_info, $variations);
            }
            
            error_log(sprintf('[CL SYNC] sync_variable_product completed for %s', $base_sku));
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
            
            error_log(sprintf('[CL SYNC] Created product: %s (ID: %d) with %d variations', 
                $base_info['base_sku'], $product_id, $variation_count));
            
            return $product_id;
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            error_log('Failed to create variable product ' . $base_info['base_sku'] . ': ' . $e->getMessage());
            return new WP_Error('product_creation_exception', $e->getMessage());
        }
    }
    
    /**
     * Update an existing variable product using direct DB queries
     * Avoids $product->save() which triggers WC_Product_Variable::sync()
     * and causes multi-minute delays recalculating all variation data
     */
    private function update_variable_product($product_id, $base_info, $variations) {
        try {
            global $wpdb;
            
            // Update post data directly (bypasses WC hooks)
            $wpdb->update(
                $wpdb->posts,
                [
                    'post_title'   => sanitize_text_field($base_info['name']),
                    'post_content' => wp_kses_post($base_info['description']),
                    'post_excerpt' => wp_kses_post($base_info['description']),
                ],
                ['ID' => $product_id],
                ['%s', '%s', '%s'],
                ['%d']
            );
            
            // Update categories
            $category_ids = $this->get_or_create_categories($base_info['brand']);
            if (!empty($category_ids)) {
                wp_set_object_terms($product_id, $category_ids, 'product_cat');
            }
            
            // Update sync meta
            update_post_meta($product_id, '_optica_vision_cl_last_sync', current_time('timestamp'));
            
            error_log(sprintf('[CL SYNC] Parent product %d updated via direct DB. Setting attributes...', $product_id));
            
            // Create prescription attribute if it doesn't exist
            optica_vision_cl_ensure_prescription_attribute();
            
            // Set product attributes
            $this->set_product_attributes($product_id, $variations);
            error_log(sprintf('[CL SYNC] Attributes set for product %d. Syncing %d variations...', $product_id, count($variations)));
            
            // Update or create variations
            $variation_count = $this->sync_variations($product_id, $variations);
            error_log(sprintf('[CL SYNC] Variations synced (%d). Updating price range...', $variation_count));
            
            // Update the variable product with price range
            $this->update_variable_product_price_range($product_id);
            error_log(sprintf('[CL SYNC] Price range updated for product %d', $product_id));
            
            // Clean the product cache once at the end
            wc_delete_product_transients($product_id);
            clean_post_cache($product_id);
            
            $this->stats['updated']++;
            $this->stats['variations'] += $variation_count;
            
            error_log(sprintf('[CL SYNC] Updated product: %s (ID: %d) with %d variations', 
                $base_info['base_sku'], $product_id, $variation_count));
            
            return $product_id;
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            error_log('Failed to update variable product ' . $base_info['base_sku'] . ': ' . $e->getMessage());
            return new WP_Error('product_update_exception', $e->getMessage());
        }
    }
    

    
    /**
     * Set product attributes for variations - DIRECT DB VERSION
     * Uses update_post_meta instead of $product->save() to avoid WC_Product_Variable::sync()
     */
    private function set_product_attributes($product_id, $variations) {
        // Get all prescription values from API data
        $prescription_values = [];
        foreach ($variations as $variation_data) {
            $prescription_values[] = $variation_data['graduacion'];
        }
        $prescription_values = array_unique($prescription_values);
        
        // Batch create/get prescription terms
        $prescription_terms = $this->batch_get_or_create_prescription_terms($prescription_values);
        
        $attribute_taxonomy_name = 'pa_prescription';
        
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
        
        // Build WC-compatible attribute data and save directly to post meta
        // This bypasses $product->save() which triggers WC_Product_Variable::sync()
        $attribute_data = [
            $attribute_taxonomy_name => [
                'name'         => $attribute_taxonomy_name,
                'value'        => '',
                'position'     => 0,
                'is_visible'   => 1,
                'is_variation' => 1,
                'is_taxonomy'  => 1,
            ]
        ];
        update_post_meta($product_id, '_product_attributes', $attribute_data);
        
        // Set the attribute terms on the product
        wp_set_object_terms($product_id, $prescription_terms, $attribute_taxonomy_name);
        
        error_log(sprintf('[CL SYNC] Attributes set for product %d with %d terms (direct DB)', 
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
     * Uses direct database queries for bulk updates instead of WC CRUD per variation
     */
    private function sync_variations($product_id, $variations) {
        $count = 0;
        global $wpdb;
        
        // Get existing variations data with a single query
        $existing_by_sku = [];
        $existing_by_prescription = [];
        
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
        
        // Separate into updates vs creates
        $to_update = [];
        $to_create = [];
        
        foreach ($variations as $var_data) {
            $prescription = $var_data['graduacion'];
            $sku = $var_data['codigo'];
            
            $existing_variation_id = null;
            if (isset($existing_by_sku[$sku])) {
                $existing_variation_id = $existing_by_sku[$sku];
            } elseif (isset($existing_by_prescription[$prescription])) {
                $existing_variation_id = $existing_by_prescription[$prescription];
            }
            
            if ($existing_variation_id) {
                $to_update[] = ['id' => $existing_variation_id, 'data' => $var_data];
            } else {
                $to_create[] = $var_data;
            }
        }
        
        error_log(sprintf('[CL SYNC] Product %d: %d to update, %d to create', 
            $product_id, count($to_update), count($to_create)));
        
        // BATCH UPDATE existing variations using direct SQL (main optimization)
        if (!empty($to_update)) {
            $updated = $this->batch_update_variations_direct($to_update);
            $count += $updated;
        }
        
        // Create new variations (still uses WC CRUD but only for truly new items)
        foreach ($to_create as $var_data) {
            $result = $this->create_single_variation($product_id, $var_data, true);
            if ($result && !is_wp_error($result)) {
                $count++;
                $this->stats['variations']++;
            } else {
                $error_msg = is_wp_error($result) ? $result->get_error_message() : 'Unknown error';
                error_log("[CL SYNC] Failed to create variation for {$var_data['codigo']}: $error_msg");
            }
        }
        
        return $count;
    }
    
    /**
     * Batch update existing variations using direct SQL queries
     * Avoids loading WC product objects entirely - ~50x faster than WC CRUD
     */
    private function batch_update_variations_direct($updates) {
        global $wpdb;
        $count = 0;
        $now = current_time('timestamp');
        
        foreach ($updates as $item) {
            $variation_id = $item['id'];
            $data = $item['data'];
            $price = floatval($data['precio']);
            $stock = floatval($data['existencia']);
            $stock_status = $stock > 0 ? 'instock' : 'outofstock';
            
            // Direct meta updates - replaces wc_get_product() + save() chain
            $meta_updates = [
                '_regular_price' => $price,
                '_price'         => $price,
                '_stock'         => $stock,
                '_stock_status'  => $stock_status,
                '_optica_vision_cl_last_sync' => $now,
            ];
            
            foreach ($meta_updates as $meta_key => $meta_value) {
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s",
                    $variation_id, $meta_key
                ));
                
                if ($exists) {
                    $wpdb->update(
                        $wpdb->postmeta,
                        ['meta_value' => $meta_value],
                        ['post_id' => $variation_id, 'meta_key' => $meta_key],
                        ['%s'],
                        ['%d', '%s']
                    );
                } else {
                    $wpdb->insert(
                        $wpdb->postmeta,
                        ['post_id' => $variation_id, 'meta_key' => $meta_key, 'meta_value' => $meta_value],
                        ['%d', '%s', '%s']
                    );
                }
            }
            
            // Update prescription term if needed
            $prescription = sanitize_text_field($data['graduacion']);
            $term = $this->get_or_create_prescription_term($prescription);
            if ($term && !is_wp_error($term)) {
                wp_set_object_terms($variation_id, $term->term_id, 'pa_prescription', false);
                
                // Also update the variation attribute meta directly
                update_post_meta($variation_id, 'attribute_pa_prescription', $term->slug);
            }
            
            $count++;
        }
        
        error_log(sprintf('[CL SYNC] Batch updated %d variations via direct SQL', $count));
        return $count;
    }
    
    /**
     * Create a single variation
     * 
     * @param int   $product_id    Parent product ID
     * @param array $variation_data API variation data
     * @param bool  $skip_sku_check Skip SKU existence check (caller already verified)
     */
    private function create_single_variation($product_id, $variation_data, $skip_sku_check = false) {
        try {
            $sku = isset($variation_data['codigo']) ? sanitize_text_field($variation_data['codigo']) : '';
            $price = isset($variation_data['precio']) ? floatval($variation_data['precio']) : 0;
            $stock = isset($variation_data['existencia']) ? floatval($variation_data['existencia']) : 0;
            $prescription = isset($variation_data['graduacion']) ? sanitize_text_field($variation_data['graduacion']) : '';
            
            if (empty($sku)) {
                return new WP_Error('missing_sku', 'Variation SKU is required');
            }
            
            // Check if SKU already exists (skip when caller already verified)
            if (!$skip_sku_check) {
                $existing_id = wc_get_product_id_by_sku($sku);
                if ($existing_id) {
                    return $this->update_single_variation($existing_id, $variation_data);
                }
            }
            
            $variation = new WC_Product_Variation();
            $variation->set_parent_id($product_id);
            $variation->set_sku($sku);
            $variation->set_regular_price($price);
            $variation->set_stock_quantity($stock);
            $variation->set_manage_stock(true);
            $variation->set_stock_status($stock > 0 ? 'instock' : 'outofstock');
            
            // Get prescription term from cache
            $term = $this->get_or_create_prescription_term($prescription);
            
            if ($term && !is_wp_error($term)) {
                $variation->set_attributes([
                    'pa_prescription' => $term->slug
                ]);
            } else {
                error_log('[CL SYNC] Failed to get/create prescription term for: ' . $prescription);
                return new WP_Error('term_creation_failed', 'Failed to create prescription term');
            }
            
            // Add variation meta
            $variation->add_meta_data('_optica_vision_cl_sync', true);
            $variation->add_meta_data('_optica_vision_cl_last_sync', current_time('timestamp'));
            
            $variation_id = $variation->save();
            
            if ($variation_id) {
                if ($term && !is_wp_error($term)) {
                    wp_set_object_terms($variation_id, $term->term_id, 'pa_prescription', false);
                }
                return $variation_id;
            }
            
            return new WP_Error('variation_creation_failed', 'Failed to create variation');
            
        } catch (Exception $e) {
            error_log('[CL SYNC] Failed to create variation: ' . $e->getMessage());
            return new WP_Error('variation_creation_exception', $e->getMessage());
        }
    }
    
    /**
     * Update a single variation using direct DB queries
     * Used as fallback from create_single_variation when SKU already exists
     */
    private function update_single_variation($variation_id, $variation_data) {
        try {
            global $wpdb;
            
            $price = floatval($variation_data['precio']);
            $stock = floatval($variation_data['existencia']);
            $stock_status = $stock > 0 ? 'instock' : 'outofstock';
            $now = current_time('timestamp');
            
            // Direct meta updates - avoids wc_get_product() + save() overhead
            $meta_updates = [
                '_regular_price' => $price,
                '_price'         => $price,
                '_stock'         => $stock,
                '_stock_status'  => $stock_status,
                '_optica_vision_cl_last_sync' => $now,
            ];
            
            foreach ($meta_updates as $meta_key => $meta_value) {
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s",
                    $variation_id, $meta_key
                ));
                
                if ($exists) {
                    $wpdb->update(
                        $wpdb->postmeta,
                        ['meta_value' => $meta_value],
                        ['post_id' => $variation_id, 'meta_key' => $meta_key],
                        ['%s'],
                        ['%d', '%s']
                    );
                } else {
                    $wpdb->insert(
                        $wpdb->postmeta,
                        ['post_id' => $variation_id, 'meta_key' => $meta_key, 'meta_value' => $meta_value],
                        ['%d', '%s', '%s']
                    );
                }
            }
            
            // Update prescription term
            $prescription = sanitize_text_field($variation_data['graduacion']);
            $term = $this->get_or_create_prescription_term($prescription);
            if ($term && !is_wp_error($term)) {
                wp_set_object_terms($variation_id, $term->term_id, 'pa_prescription', false);
                update_post_meta($variation_id, 'attribute_pa_prescription', $term->slug);
            }
            
            return $variation_id;
            
        } catch (Exception $e) {
            error_log('[CL SYNC] Failed to update variation: ' . $e->getMessage());
            return new WP_Error('variation_update_exception', $e->getMessage());
        }
    }
    
    /**
     * Load all prescription terms into cache (called once per sync)
     */
    private function load_prescription_term_cache() {
        $taxonomy = 'pa_prescription';
        
        if (!taxonomy_exists($taxonomy)) {
            return;
        }
        
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ]);
        
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $this->prescription_term_cache[$term->slug] = $term;
                $this->prescription_term_cache['name:' . $term->name] = $term;
            }
        }
        
        $this->term_cache_loaded = true;
        error_log(sprintf('[CL SYNC] Loaded %d prescription terms into cache', count($terms)));
    }
    
    /**
     * Get or create prescription term - uses cache to avoid per-variation DB queries
     */
    private function get_or_create_prescription_term($prescription) {
        $taxonomy = 'pa_prescription';
        
        if (!taxonomy_exists($taxonomy)) {
            return new WP_Error('taxonomy_missing', "Prescription taxonomy does not exist: $taxonomy");
        }
        
        $slug = sanitize_title($prescription);
        
        // Check cache first (avoids DB query entirely)
        if (isset($this->prescription_term_cache[$slug])) {
            return $this->prescription_term_cache[$slug];
        }
        if (isset($this->prescription_term_cache['name:' . $prescription])) {
            return $this->prescription_term_cache['name:' . $prescription];
        }
        
        // Not in cache - check DB (rare case: new term during sync)
        $term = get_term_by('slug', $slug, $taxonomy);
        if (!$term) {
            $term = get_term_by('name', $prescription, $taxonomy);
        }
        
        if (!$term) {
            $result = wp_insert_term($prescription, $taxonomy, ['slug' => $slug]);
            if (is_wp_error($result)) {
                error_log('[CL SYNC] Failed to create prescription term "' . $prescription . '": ' . $result->get_error_message());
                return $result;
            }
            $term = get_term($result['term_id'], $taxonomy);
            error_log('[CL SYNC] Created new prescription term: ' . $prescription);
        }
        
        // Add to cache for subsequent lookups
        if ($term && !is_wp_error($term)) {
            $this->prescription_term_cache[$term->slug] = $term;
            $this->prescription_term_cache['name:' . $term->name] = $term;
        }
        
        return $term;
    }
    
    /**
     * Update variable product price range using direct SQL
     * Replaces $product->variable_product_sync() which is extremely expensive
     */
    private function update_variable_product_price_range($product_id) {
        global $wpdb;
        
        // Calculate min/max prices from variations directly
        $price_data = $wpdb->get_row($wpdb->prepare("
            SELECT 
                MIN(CAST(pm.meta_value AS DECIMAL(10,2))) as min_price,
                MAX(CAST(pm.meta_value AS DECIMAL(10,2))) as max_price
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_parent = %d 
            AND p.post_type = 'product_variation'
            AND p.post_status = 'publish'
            AND pm.meta_key = '_price'
            AND pm.meta_value != ''
            AND pm.meta_value IS NOT NULL
        ", $product_id));
        
        if ($price_data && $price_data->min_price !== null) {
            update_post_meta($product_id, '_price', $price_data->min_price);
            update_post_meta($product_id, '_min_variation_price', $price_data->min_price);
            update_post_meta($product_id, '_max_variation_price', $price_data->max_price);
            update_post_meta($product_id, '_min_variation_regular_price', $price_data->min_price);
            update_post_meta($product_id, '_max_variation_regular_price', $price_data->max_price);
        }
        
        // Calculate stock status from variations
        $in_stock_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_parent = %d 
            AND p.post_type = 'product_variation'
            AND pm.meta_key = '_stock_status'
            AND pm.meta_value = 'instock'
        ", $product_id));
        
        $stock_status = $in_stock_count > 0 ? 'instock' : 'outofstock';
        update_post_meta($product_id, '_stock_status', $stock_status);
        
        // Delete transients so prices refresh on frontend
        delete_transient('wc_var_prices_' . $product_id);
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