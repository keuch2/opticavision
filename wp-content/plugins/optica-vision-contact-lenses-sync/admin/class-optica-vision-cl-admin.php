<?php
/**
 * Contact Lenses Admin Class
 * 
 * Handles plugin admin interface for contact lens synchronization
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Optica_Vision_CL_Admin {
    
    /**
     * Plugin instance
     */
    private $plugin;
    private $api;
    private $last_sync = null;
    
    /**
     * Constructor
     */
    public function __construct($plugin) {
        error_log('[CL SYNC ADMIN] Constructor called');
        
        $this->plugin = $plugin;
        $this->api = $plugin->api;
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add admin page content
        add_action('admin_init', array($this, 'init_settings'));
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers with nonce verification
        add_action('wp_ajax_optica_vision_cl_sync_products', array($this, 'ajax_sync_products'));
        add_action('wp_ajax_optica_vision_cl_sync_batch', array($this, 'ajax_sync_batch'));
        add_action('wp_ajax_optica_vision_cl_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_optica_vision_cl_check_connection', array($this, 'ajax_check_connection'));
        add_action('wp_ajax_optica_vision_cl_delete_products', array($this, 'ajax_delete_products'));
        add_action('wp_ajax_optica_vision_cl_connect', array($this, 'ajax_connect'));
        add_action('wp_ajax_optica_vision_cl_force_reconnect', array($this, 'ajax_force_reconnect'));
        add_action('wp_ajax_optica_vision_cl_get_products', array($this, 'ajax_get_products'));
        add_action('wp_ajax_optica_vision_cl_debug_attributes', array($this, 'ajax_debug_attributes'));
        
        error_log('[CL SYNC ADMIN] All AJAX handlers registered');
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_optica-vision-contact-lenses-sync') {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('optica-vision-cl-admin', 
            OPTICA_VISION_CL_SYNC_URL . 'admin/js/admin.js', 
            array('jquery'), 
            OPTICA_VISION_CL_SYNC_VERSION, 
            true
        );
        
        wp_localize_script('optica-vision-cl-admin', 'optica_vision_cl_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('optica_vision_cl_nonce')
        ));
    }
    
    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_menu_page(
            'Optica Vision Contact Lenses',
            'CL Sync',
            'manage_options',
            'optica-vision-contact-lenses-sync',
            array($this, 'render_admin_page'),
            'dashicons-visibility',
            57
        );
    }
    
    /**
     * Initialize admin page
     */
    public function init_settings() {
        register_setting('optica_vision_cl_settings', 'optica_vision_cl_options');
        
        // API Settings Section
        add_settings_section(
            'optica_vision_cl_api',
            'Configuración de API',
            array($this, 'render_api_section'),
            'optica-vision-contact-lenses-sync'
        );
        
        add_settings_field(
            'api_url',
            'URL de API',
            array($this, 'render_api_url_field'),
            'optica-vision-contact-lenses-sync',
            'optica_vision_cl_api'
        );
        
        // Sync Section
        add_settings_section(
            'optica_vision_cl_sync',
            'Sincronización',
            array($this, 'render_sync_section'),
            'optica-vision-contact-lenses-sync'
        );
        
        add_settings_field(
            'sync_status',
            'Estado',
            array($this, 'render_sync_status'),
            'optica-vision-contact-lenses-sync',
            'optica_vision_cl_sync'
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $is_connected = $this->plugin->api->is_connected();
        $last_sync = get_option('optica_vision_cl_last_sync');
        
        echo '<div class="wrap">';
        echo '<h1>Optica Vision Contact Lenses Sync</h1>';
        echo '<p>Sincronización especializada para lentes de contacto con productos variables y variaciones de graduación.</p>';
        
        // API Settings Section
        echo '<div class="optica-card" style="margin-bottom: 20px; padding: 20px; background: white; border: 1px solid #ccd0d4; border-radius: 4px;">';
        echo '<h2>Configuración de API</h2>';
        echo '<form id="api-settings-form">';
        wp_nonce_field('optica_vision_cl_settings', 'optica_vision_cl_settings_nonce');
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th scope="row">URL de API</th>';
        echo '<td><input type="url" name="api_url" value="' . esc_url(get_option('optica_vision_cl_api_url', 'http://190.104.159.90:8081')) . '" class="regular-text" /></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row">Usuario</th>';
        echo '<td><input type="text" name="api_username" value="' . esc_attr(get_option('optica_vision_cl_api_username', 'userweb')) . '" class="regular-text" /></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row">Contraseña</th>';
        echo '<td><input type="password" name="api_password" value="' . esc_attr(get_option('optica_vision_cl_api_password', 'us34.w38')) . '" class="regular-text" /></td>';
        echo '</tr>';
        echo '</table>';
        echo '<p class="submit">';
        echo '<input type="submit" class="button-primary" value="Guardar Configuración" />';
        echo '</p>';
        echo '</form>';
        echo '</div>';
        
        // Connection Status Section
        echo '<div class="optica-card" style="margin-bottom: 20px; padding: 20px; background: white; border: 1px solid #ccd0d4; border-radius: 4px;">';
        echo '<h2>Estado de Conexión</h2>';
        echo '<div id="connection-status">';
        
        if ($is_connected) {
            echo '<div class="notice notice-success inline"><p>✅ Conectado a la API</p></div>';
        } else {
            echo '<div class="notice notice-warning inline"><p>⚠️ No conectado a la API</p></div>';
        }
        
        echo '</div>';
        
        echo '<div style="margin-top: 15px;">';
        echo '<button id="test-connection" class="button">Probar Conexión</button>';
        echo '<button id="connect-api" class="button">Conectar</button>';
        echo '<button id="force-reconnect" class="button">Reconectar</button>';
        echo '</div>';
        echo '</div>';
        
        // Synchronization Section
        echo '<div class="optica-card" style="margin-bottom: 20px; padding: 20px; background: white; border: 1px solid #ccd0d4; border-radius: 4px;">';
        echo '<h2>Sincronización de Lentes de Contacto</h2>';
        echo '<p>Los lentes de contacto se organizan en productos variables por marca y tipo, con variaciones por graduación.</p>';
        
        if ($is_connected) {
            echo '<div style="margin-bottom: 15px;">';
            echo '<button id="sync-products" class="button button-primary button-large">🔄 Sincronizar Lentes de Contacto</button>';
            echo '<button id="get-products" class="button" style="margin-left: 10px;">👁️ Ver Datos de API</button>';
            echo '<button id="debug-attributes" class="button" style="margin-left: 10px;">🔧 Debug Atributos</button>';
            echo '</div>';
            
            echo '<div style="margin-bottom: 15px;">';
            echo '<button id="delete-products" class="button button-secondary" onclick="return confirm(\'¿Estás seguro de que quieres eliminar todos los lentes de contacto sincronizados?\')">🗑️ Eliminar Productos Sincronizados</button>';
            echo '</div>';
        } else {
            echo '<div class="notice notice-error inline">';
            echo '<p>Debes conectarte a la API antes de sincronizar productos.</p>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Progress and Logs Section
        echo '<div class="optica-card" style="margin-top: 20px; padding: 20px; background: white; border: 1px solid #ccd0d4; border-radius: 4px;">';
        echo '<h2>Registro de Actividades</h2>';
        echo '<div id="sync-logs" class="log-container" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9; border-radius: 4px;">';
        echo '<p>Los registros aparecerán aquí durante la operación.</p>';
        echo '</div>';
        echo '</div>';
        
        // Última sincronización
        if ($last_sync) {
            echo '<div style="margin-top: 15px; padding: 15px; background: #f0f0f1; border-radius: 4px;">';
            echo '<h3>Última Sincronización</h3>';
            echo '<p><strong>Fecha:</strong> ' . date_i18n('d/m/Y H:i:s', $last_sync['timestamp']) . '</p>';
            if (isset($last_sync['stats'])) {
                echo '<p><strong>Estadísticas:</strong></p>';
                echo '<ul>';
                echo '<li>Productos creados: ' . ($last_sync['stats']['created'] ?? 0) . '</li>';
                echo '<li>Productos actualizados: ' . ($last_sync['stats']['updated'] ?? 0) . '</li>';
                echo '<li>Variaciones procesadas: ' . ($last_sync['stats']['variations'] ?? 0) . '</li>';
                echo '<li>Errores: ' . ($last_sync['stats']['errors'] ?? 0) . '</li>';
                echo '</ul>';
            }
            echo '</div>';
        }
        
        // Nonce para seguridad
        $nonce_value = wp_create_nonce('optica_vision_cl_nonce');
        echo '<input type="hidden" id="optica_vision_cl_nonce" name="optica_vision_cl_nonce" value="' . $nonce_value . '" />';
        
        echo '</div>'; // Cierre de .wrap
    }
    
    /**
     * Render API section
     */
    public function render_api_section() {
        echo '<p>Configure la conexión con la API de Optica Vision para lentes de contacto</p>';
    }
    
    /**
     * Render API URL field
     */
    public function render_api_url_field() {
        $options = get_option('optica_vision_cl_options');
        ?>
        <input type="text" id="api_url" name="optica_vision_cl_options[api_url]" value="<?php echo $options['api_url']; ?>">
        <?php
    }
    
    /**
     * Render sync section
     */
    public function render_sync_section() {
        echo '<p>Configure la sincronización de lentes de contacto</p>';
    }
    
    /**
     * Render sync status
     */
    public function render_sync_status() {
        $last_sync = $this->last_sync;
        if ($last_sync) {
            echo '<p>Última sincronización: ' . $last_sync . '</p>';
        } else {
            echo '<p>No se ha sincronizado nunca</p>';
        }
    }
    
    /**
     * AJAX: Initialize batch sync - fetches data and stores for batch processing
     */
    public function ajax_sync_products() {
        error_log('[CL SYNC AJAX] ========== ajax_sync_products CALLED ==========');
        error_log('[CL SYNC AJAX] POST data: ' . print_r($_POST, true));
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_cl_nonce')) {
            error_log('[CL SYNC AJAX] NONCE FAILED');
            wp_send_json_error('Invalid security token');
        }
        
        error_log('[CL SYNC AJAX] Nonce verified OK');
        
        if (!current_user_can('manage_options')) {
            error_log('[CL SYNC AJAX] Permission denied');
            wp_send_json_error('Insufficient permissions');
        }
        
        error_log('[CL SYNC AJAX] Permissions OK, fetching API data...');
        
        try {
            // Get contact lenses from API
            $contact_lenses = $this->api->get_contact_lenses();
            
            if (is_wp_error($contact_lenses)) {
                wp_send_json_error('API Error: ' . $contact_lenses->get_error_message());
            }
            
            if (empty($contact_lenses) || !is_array($contact_lenses)) {
                wp_send_json_error('No contact lenses received from API');
            }
            
            // Group products
            $grouped = $this->plugin->product_sync->group_products_by_base($contact_lenses);
            
            if (empty($grouped)) {
                wp_send_json_error('No valid product groups found');
            }
            
            // Store grouped products in transient for batch processing
            $batch_id = 'cl_sync_' . time();
            set_transient($batch_id, $grouped, HOUR_IN_SECONDS);
            
            // Reset stats
            delete_transient($batch_id . '_stats');
            set_transient($batch_id . '_stats', [
                'created' => 0,
                'updated' => 0,
                'variations' => 0,
                'skipped' => 0,
                'errors' => 0
            ], HOUR_IN_SECONDS);
            
            wp_send_json_success([
                'batch_id' => $batch_id,
                'total_groups' => count($grouped),
                'total_items' => count($contact_lenses),
                'message' => sprintf('Preparados %d grupos de productos para sincronizar', count($grouped))
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Process a batch of products
     */
    public function ajax_sync_batch() {
        error_log('[CL SYNC BATCH] ========== ajax_sync_batch STARTED ==========');
        
        // Increase PHP limits for this request
        @set_time_limit(300); // 5 minutes
        @ini_set('memory_limit', '512M');
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_cl_nonce')) {
            error_log('[CL SYNC BATCH] Invalid nonce');
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            error_log('[CL SYNC BATCH] Insufficient permissions');
            wp_send_json_error('Insufficient permissions');
        }
        
        $batch_id = sanitize_text_field($_POST['batch_id'] ?? '');
        $offset = absint($_POST['offset'] ?? 0);
        $batch_size = absint($_POST['batch_size'] ?? 5);
        
        error_log("[CL SYNC BATCH] batch_id=$batch_id, offset=$offset, batch_size=$batch_size");
        
        if (empty($batch_id)) {
            wp_send_json_error('Invalid batch ID');
        }
        
        // Get stored products
        $grouped = get_transient($batch_id);
        if (!$grouped) {
            error_log('[CL SYNC BATCH] Transient not found: ' . $batch_id);
            wp_send_json_error('Batch expired or not found. Please restart sync.');
        }
        
        error_log('[CL SYNC BATCH] Transient found with ' . count($grouped) . ' groups');
        
        // Get current stats
        $stats = get_transient($batch_id . '_stats') ?: [
            'created' => 0,
            'updated' => 0,
            'variations' => 0,
            'skipped' => 0,
            'errors' => 0
        ];
        
        // Get batch to process
        $keys = array_keys($grouped);
        $total = count($keys);
        $batch_keys = array_slice($keys, $offset, $batch_size);
        
        error_log("[CL SYNC BATCH] Processing keys: " . implode(', ', $batch_keys));
        
        if (empty($batch_keys)) {
            // All done - cleanup and return final stats
            error_log('[CL SYNC BATCH] All done - cleaning up');
            delete_transient($batch_id);
            delete_transient($batch_id . '_stats');
            
            // Store final sync results
            update_option('optica_vision_cl_last_sync', [
                'timestamp' => current_time('timestamp'),
                'stats' => $stats,
                'total_groups' => $total
            ]);
            
            wp_send_json_success([
                'done' => true,
                'stats' => $stats,
                'message' => 'Sincronización completada'
            ]);
        }
        
        // Process this batch
        foreach ($batch_keys as $base_sku) {
            $group = $grouped[$base_sku];
            $variation_count = count($group['variations'] ?? []);
            
            error_log("[CL SYNC BATCH] Processing SKU: $base_sku with $variation_count variations");
            $start_time = microtime(true);
            
            try {
                $result = $this->plugin->product_sync->sync_variable_product($base_sku, $group);
                
                $elapsed = round(microtime(true) - $start_time, 2);
                error_log("[CL SYNC BATCH] SKU $base_sku completed in {$elapsed}s");
                
                if (is_wp_error($result)) {
                    $stats['errors']++;
                    error_log("[CL SYNC BATCH] SKU $base_sku ERROR: " . $result->get_error_message());
                } elseif (is_numeric($result)) {
                    // Result is product ID - count as created or updated
                    $stats['updated']++;
                    error_log("[CL SYNC BATCH] SKU $base_sku SUCCESS: Product ID $result");
                } elseif (is_array($result)) {
                    // Result contains stats from sync
                    $stats['created'] += $result['created'] ?? 0;
                    $stats['updated'] += $result['updated'] ?? 0;
                    $stats['variations'] += $result['variations'] ?? 0;
                    error_log("[CL SYNC BATCH] SKU $base_sku SUCCESS with stats");
                }
            } catch (Exception $e) {
                $stats['errors']++;
                error_log("[CL SYNC BATCH] SKU $base_sku EXCEPTION: " . $e->getMessage());
            }
        }
        
        // Save updated stats
        set_transient($batch_id . '_stats', $stats, HOUR_IN_SECONDS);
        
        $processed = $offset + count($batch_keys);
        $progress = round(($processed / $total) * 100);
        
        error_log("[CL SYNC BATCH] Batch complete: $processed/$total ($progress%)");
        error_log('[CL SYNC BATCH] ========== ajax_sync_batch FINISHED ==========');
        
        wp_send_json_success([
            'done' => false,
            'processed' => $processed,
            'total' => $total,
            'progress' => $progress,
            'stats' => $stats,
            'message' => sprintf('Procesados %d de %d grupos (%d%%)', $processed, $total, $progress)
        ]);
    }
    
    /**
     * AJAX: Test connection
     */
    public function ajax_test_connection() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_cl_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        try {
            $result = $this->api->test_connection();
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }
            
            wp_send_json_success([
                'message' => 'Connection test successful',
                'sample_data' => $result,
                'total_items' => count($result)
            ]);
            
        } catch (Exception $e) {
            error_log('Contact lens test connection AJAX error: ' . $e->getMessage());
            wp_send_json_error('Connection test failed: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Check connection status
     */
    public function ajax_check_connection() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_cl_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $is_connected = $this->api->is_connected();
        
        wp_send_json_success([
            'connected' => $is_connected,
            'message' => $is_connected ? 'API is connected' : 'API is not connected'
        ]);
    }
    
    /**
     * AJAX: Connect to API
     */
    public function ajax_connect() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_cl_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        try {
            $result = $this->api->connect();
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }
            
            wp_send_json_success([
                'message' => 'Successfully connected to API'
            ]);
            
        } catch (Exception $e) {
            error_log('Contact lens connect AJAX error: ' . $e->getMessage());
            wp_send_json_error('Connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Force reconnect
     */
    public function ajax_force_reconnect() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_cl_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        try {
            $result = $this->api->force_reconnect();
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }
            
            wp_send_json_success([
                'message' => 'Successfully reconnected to API'
            ]);
            
        } catch (Exception $e) {
            error_log('Contact lens force reconnect AJAX error: ' . $e->getMessage());
            wp_send_json_error('Reconnection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Get products from API
     */
    public function ajax_get_products() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_cl_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        try {
            $products = $this->api->get_contact_lenses();
            
            if (is_wp_error($products)) {
                wp_send_json_error($products->get_error_message());
            }
            
            // Return first 10 items for preview
            $sample = array_slice($products, 0, 10);
            
            wp_send_json_success([
                'message' => 'Products retrieved successfully',
                'total_count' => count($products),
                'sample_data' => $sample,
                'grouping_info' => $this->analyze_product_grouping($products)
            ]);
            
        } catch (Exception $e) {
            error_log('Contact lens get products AJAX error: ' . $e->getMessage());
            wp_send_json_error('Failed to retrieve products: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Delete synced products
     */
    public function ajax_delete_products() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_cl_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        try {
            // Get all synced contact lens products
            $products = wc_get_products([
                'limit' => -1,
                'meta_key' => '_optica_vision_cl_sync',
                'meta_value' => true,
                'return' => 'ids'
            ]);
            
            $deleted_count = 0;
            $errors = 0;
            
            foreach ($products as $product_id) {
                $result = wp_delete_post($product_id, true);
                if ($result) {
                    $deleted_count++;
                } else {
                    $errors++;
                }
            }
            
            wp_send_json_success([
                'message' => sprintf('Deleted %d contact lens products', $deleted_count),
                'deleted_count' => $deleted_count,
                'errors' => $errors
            ]);
            
        } catch (Exception $e) {
            error_log('Contact lens delete products AJAX error: ' . $e->getMessage());
            wp_send_json_error('Failed to delete products: ' . $e->getMessage());
        }
    }
    
    /**
     * Analyze how products would be grouped
     */
    private function analyze_product_grouping($products) {
        $groups = [];
        $brands = [];
        
        foreach ($products as $product) {
            $brand = $product['marca'];
            $brands[$brand] = ($brands[$brand] ?? 0) + 1;
            
            // Simulate grouping logic
            $base_desc = preg_replace('/Simple visión Lentes de Co[^A-Z]*/', '', $product['descripcion']);
            $base_desc = preg_replace('/ME \d+\.\d+ \d+\.\d+/', '', $base_desc);
            $base_desc = preg_replace('/\s+(INCOLORO|AZUL|VERDE|GRIS)$/', '', $base_desc);
            $base_desc = trim($base_desc);
            
            $group_key = $brand . '_' . $base_desc;
            $groups[$group_key] = ($groups[$group_key] ?? 0) + 1;
        }
        
        return [
            'total_products' => count($products),
            'estimated_groups' => count($groups),
            'brands' => $brands,
            'average_variations_per_group' => count($products) / max(1, count($groups))
        ];
    }
    
    /**
     * AJAX: Debug attributes and taxonomies
     */
    public function ajax_debug_attributes() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_cl_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        try {
            $debug_info = [];
            
            // Check WooCommerce functions
            $debug_info['woocommerce_functions'] = [
                'wc_create_attribute' => function_exists('wc_create_attribute'),
                'wc_get_attribute_taxonomy_name' => function_exists('wc_get_attribute_taxonomy_name'),
                'wc_get_attribute_taxonomies' => function_exists('wc_get_attribute_taxonomies'),
            ];
            
            // Check prescription attribute
            if (function_exists('wc_get_attribute_taxonomy_name')) {
                $taxonomy_name = wc_get_attribute_taxonomy_name('prescription');
                $debug_info['prescription_taxonomy'] = [
                    'name' => $taxonomy_name,
                    'exists' => taxonomy_exists($taxonomy_name),
                ];
                
                // Get all WooCommerce attributes
                if (function_exists('wc_get_attribute_taxonomies')) {
                    $attributes = wc_get_attribute_taxonomies();
                    $debug_info['all_attributes'] = [];
                    foreach ($attributes as $attr) {
                        $debug_info['all_attributes'][] = [
                            'id' => $attr->attribute_id,
                            'name' => $attr->attribute_name,
                            'label' => $attr->attribute_label,
                            'type' => $attr->attribute_type,
                        ];
                    }
                }
                
                // Get prescription terms if taxonomy exists
                if (taxonomy_exists($taxonomy_name)) {
                    $terms = get_terms([
                        'taxonomy' => $taxonomy_name,
                        'hide_empty' => false,
                    ]);
                    
                    $debug_info['prescription_terms'] = [];
                    if (!is_wp_error($terms)) {
                        foreach ($terms as $term) {
                            $debug_info['prescription_terms'][] = [
                                'id' => $term->term_id,
                                'name' => $term->name,
                                'slug' => $term->slug,
                                'count' => $term->count,
                            ];
                        }
                    }
                }
            }
            
            // Try to create/ensure prescription attribute
            $attribute_creation_result = optica_vision_cl_ensure_prescription_attribute();
            $debug_info['attribute_creation_attempt'] = $attribute_creation_result;
            
            // Get existing contact lens products
            $cl_products = wc_get_products([
                'limit' => 5,
                'meta_key' => '_optica_vision_cl_sync',
                'meta_value' => true,
            ]);
            
            $debug_info['existing_cl_products'] = [];
            foreach ($cl_products as $product) {
                $product_data = [
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'type' => $product->get_type(),
                    'sku' => $product->get_sku(),
                ];
                
                if ($product->is_type('variable')) {
                    $product_data['variations_count'] = count($product->get_children());
                    $product_data['attributes'] = $product->get_attributes();
                    
                    // Get first few variations
                    $variations = $product->get_children();
                    $product_data['sample_variations'] = [];
                    foreach (array_slice($variations, 0, 3) as $variation_id) {
                        $variation = wc_get_product($variation_id);
                        if ($variation) {
                            $product_data['sample_variations'][] = [
                                'id' => $variation_id,
                                'sku' => $variation->get_sku(),
                                'attributes' => $variation->get_attributes(),
                                'price' => $variation->get_price(),
                                'stock' => $variation->get_stock_quantity(),
                            ];
                        }
                    }
                }
                
                $debug_info['existing_cl_products'][] = $product_data;
            }
            
            wp_send_json_success([
                'message' => 'Debug information collected',
                'debug_info' => $debug_info
            ]);
            
        } catch (Exception $e) {
            error_log('Debug attributes AJAX error: ' . $e->getMessage());
            wp_send_json_error('Failed to collect debug information: ' . $e->getMessage());
        }
    }
} 