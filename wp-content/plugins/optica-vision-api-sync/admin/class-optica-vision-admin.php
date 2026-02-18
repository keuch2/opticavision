<?php
/**
 * Admin Class
 * 
 * Handles plugin admin interface
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Optica_Vision_Admin {
    
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
        $this->plugin = $plugin;
        $this->api = $plugin->api;
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add admin page content
        add_action('admin_init', array($this, 'init_settings'));
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers with nonce verification
        add_action('wp_ajax_optica_vision_sync_products', array($this, 'ajax_sync_products'));
        add_action('wp_ajax_optica_vision_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_optica_vision_check_connection', array($this, 'ajax_check_connection'));
        add_action('wp_ajax_optica_vision_delete_products', array($this, 'ajax_delete_products'));
        add_action('wp_ajax_optica_vision_connect', array($this, 'ajax_connect'));
        add_action('wp_ajax_optica_vision_force_reconnect', array($this, 'ajax_force_reconnect'));
        add_action('wp_ajax_optica_vision_get_products', array($this, 'ajax_get_products'));
        add_action('wp_ajax_optica_vision_get_scheduled_sync_status', array($this, 'ajax_get_scheduled_sync_status'));
        add_action('wp_ajax_optica_vision_enable_scheduled_sync', array($this, 'ajax_enable_scheduled_sync'));
        add_action('wp_ajax_optica_vision_disable_scheduled_sync', array($this, 'ajax_disable_scheduled_sync'));
        add_action('wp_ajax_optica_vision_update_sync_interval', array($this, 'ajax_update_sync_interval'));
        add_action('wp_ajax_optica_vision_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_optica_vision_get_sync_logs', array($this, 'ajax_get_sync_logs'));
        add_action('wp_ajax_optica_vision_restore_backup', array($this, 'ajax_restore_backup'));
        add_action('wp_ajax_optica_vision_get_backups', array($this, 'ajax_get_backups'));
        
        // Handle manual connection request
        add_action('admin_init', array($this, 'handle_connection_request'));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_optica-vision-api-sync') return;
        
        // Estilos CSS
        wp_enqueue_style(
            'optica-vision-admin',
            plugin_dir_url(__FILE__) . 'css/admin.css',
            array(),
            OPTICA_VISION_API_SYNC_VERSION
        );
        
        // Script principal
        wp_enqueue_script(
            'optica-vision-admin',
            plugin_dir_url(__FILE__) . 'js/admin.js',
            array('jquery'),
            OPTICA_VISION_API_SYNC_VERSION,
            true
        );
        
        // Datos localizados para el script
        wp_localize_script('optica-vision-admin', 'optica_vision_vars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('optica_vision_nonce'),
            'connecting_text' => __('Conectando a la API...', 'optica-vision-api-sync'),
            'connected_text' => __('¡Conectado!', 'optica-vision-api-sync'),
            'disconnect_text' => __('Desconectar', 'optica-vision-api-sync'),
            'connect_text' => __('Conectar a la API', 'optica-vision-api-sync'),
            'sync_in_progress' => __('Sincronización en curso...', 'optica-vision-api-sync'),
            'sync_complete' => __('Sincronización completada', 'optica-vision-api-sync'),
            'error_text' => __('Error', 'optica-vision-api-sync')
        ));
    }
    
    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_menu_page(
            'Optica Vision Sync',
            'Optica Vision',
            'manage_options',
            'optica-vision-api-sync',
            array($this, 'render_admin_page'),
            'dashicons-products',
            56
        );
    }
    
    /**
     * Initialize admin page
     */
    public function init_settings() {
        register_setting('optica_vision_settings', 'optica_vision_options');
        
        // API Settings Section
        add_settings_section(
            'optica_vision_api',
            'Configuración de API',
            array($this, 'render_api_section'),
            'optica-vision-api-sync'
        );
        
        add_settings_field(
            'api_url',
            'URL de API',
            array($this, 'render_api_url_field'),
            'optica-vision-api-sync',
            'optica_vision_api'
        );
        
        // Sync Section
        add_settings_section(
            'optica_vision_sync',
            'Sincronización',
            array($this, 'render_sync_section'),
            'optica-vision-api-sync'
        );
        
        add_settings_field(
            'sync_status',
            'Estado',
            array($this, 'render_sync_status'),
            'optica-vision-api-sync',
            'optica_vision_sync'
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Verificar permisos del sistema de archivos
        $is_filesystem_writable = $this->plugin->api->is_filesystem_writable();
        $is_connected = $this->plugin->api->is_connected();
        $last_sync = get_option('optica_vision_last_sync');
        
        echo '<div class="wrap">';
        echo '<h1>Optica Vision API Sync</h1>';
        
        // Mensaje de error de permisos
        if (!$is_filesystem_writable) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>Advertencia:</strong> El plugin requiere permisos de escritura en uno de estos directorios:</p>';
            echo '<ul>';
            echo '<li>'.WP_CONTENT_DIR.'</li>';
            echo '<li>'.wp_upload_dir()['basedir'].'</li>';
            echo '<li>'.WP_CONTENT_DIR.'/cache</li>';
            echo '</ul>';
            echo '<p>Por favor verifica los permisos del sistema de archivos.</p>';
            echo '</div>';
        }
        
        // API Settings Section
        echo '<div class="optica-card" style="margin-bottom: 20px;">';
        echo '<h2>Configuración de API</h2>';
        echo '<form id="api-settings-form">';
        wp_nonce_field('optica_vision_settings', 'optica_vision_settings_nonce');
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th scope="row">URL de API</th>';
        echo '<td><input type="url" name="api_url" value="' . esc_url(get_option('optica_vision_api_url', 'http://190.104.159.90:8081')) . '" class="regular-text" /></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row">Usuario</th>';
        echo '<td><input type="text" name="api_username" value="' . esc_attr(get_option('optica_vision_api_username', 'userweb')) . '" class="regular-text" /></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row">Contraseña</th>';
        echo '<td><input type="password" name="api_password" value="' . esc_attr(get_option('optica_vision_api_password', '')) . '" class="regular-text" /></td>';
        echo '</tr>';
        echo '</table>';
        echo '<p class="submit">';
        echo '<input type="submit" class="button-primary" value="Guardar Configuración" />';
        echo '</p>';
        echo '</form>';
        
        echo '<div class="connection-status" style="margin-top: 20px;">';
        echo '<h3>Estado de Conexión</h3>';
        echo '<p>Estado: <span class="api-status status-' . ($is_connected ? 'connected' : 'disconnected') . '" style="font-weight: bold;">' . 
             ($is_connected ? 'Conectado' : 'Desconectado') . '</span></p>';
        echo '<button id="optica-connect-btn" class="button ' . ($is_connected ? 'button-secondary' : 'button-primary') . '" style="margin-top: 10px;">' . 
             ($is_connected ? 'Desconectar de la API' : 'Conectar a la API') . '</button>';
        if ($is_connected) {
            echo '<button id="force-reconnect-btn" class="button button-secondary" style="margin-top: 10px; margin-left: 10px;">Forzar Reconexión</button>';
        }
        echo '</div>';
        
        // Contenedor para la información de la API
        echo '<div id="api-info" style="display: ' . ($is_connected ? 'block' : 'none') . ';">';
        
        // Botón para cargar productos
        echo '<div style="margin: 15px 0;">';
        echo '<button id="load-products-btn" class="button button-primary">Cargar Productos</button>';
        echo '<div id="load-progress" class="progress-container" style="display: none; margin-top: 10px;">';
        echo '<div class="progress-bar" style="width: 0%;"></div>';
        echo '<div class="progress-text">0%</div>';
        echo '</div>';
        echo '</div>';
        
        // Estadísticas de productos
        echo '<div id="products-stats" class="stats-container" style="display: none; margin: 20px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">';
        echo '<h3>Resumen de Productos</h3>';
        echo '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 10px;">';
        echo '<div class="stat-item"><strong>Total de Productos:</strong> <span id="total-products">0</span></div>';
        echo '<div class="stat-item"><strong>Total de Categorías:</strong> <span id="total-categories">0</span></div>';
        echo '</div>';
        echo '</div>';
        
        // Botón para sincronizar productos
        echo '<div style="margin: 20px 0;">';
        echo '<h3>Sincronizar con WooCommerce</h3>';
        echo '<button id="sync-products-btn" class="button button-primary">Sincronizar Productos</button>';
        echo '<div id="sync-progress" class="progress-container" style="display: none; margin-top: 10px;">';
        echo '<div class="progress-bar" style="width: 0%;"></div>';
        echo '<div class="progress-text">0%</div>';
        echo '</div>';
        echo '<div id="sync-results" style="margin-top: 15px; display: none;"></div>';
        echo '</div>';
        
        // Configuración de sincronización programada
        echo '<div style="margin: 30px 0; padding: 20px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">';
        echo '<h3>Sincronización Programada</h3>';
        
        // Estado actual de la sincronización programada
        $scheduled_status = get_option('optica_vision_scheduled_sync', 'inactive');
        $scheduled_interval = get_option('optica_vision_sync_interval', 'hourly');
        $next_scheduled = wp_next_scheduled('optica_vision_scheduled_sync_event');
        
        echo '<div style="margin: 15px 0;">';
        echo '<p><strong>Estado actual:</strong> <span id="scheduled-status" style="font-weight: bold;">' . 
             ($scheduled_status === 'active' ? 'Activo' : 'Inactivo') . '</span></p>';
        
        if ($next_scheduled) {
            echo '<p><strong>Próxima sincronización:</strong> ' . 
                 date_i18n('d/m/Y H:i:s', $next_scheduled) . '</p>';
        }
        
        echo '</div>';
        
        // Controles de programación
        echo '<div style="margin: 15px 0;">';
        echo '<label for="sync-interval" style="display: block; margin-bottom: 5px;"><strong>Intervalo de sincronización:</strong></label>';
        echo '<select id="sync-interval" class="regular-text" style="min-width: 200px;" ' . 
             ($scheduled_status === 'active' ? 'disabled' : '') . '>';
        echo '<option value="30min" ' . selected($scheduled_interval, '30min', false) . '>Cada 30 minutos</option>';
        echo '<option value="hourly" ' . selected($scheduled_interval, 'hourly', false) . '>Cada hora</option>';
        echo '<option value="2hours" ' . selected($scheduled_interval, '2hours', false) . '>Cada 2 horas</option>';
        echo '</select>';
        echo '</div>';
        
        // Botón para activar/desactivar la sincronización programada
        echo '<div style="margin: 20px 0;">';
        echo '<button id="toggle-scheduled-sync" class="button ' . 
             ($scheduled_status === 'active' ? 'button-secondary' : 'button-primary') . '">' . 
             ($scheduled_status === 'active' ? 'Desactivar Sincronización Programada' : 'Activar Sincronización Programada') . 
             '</button>';
        echo '<span id="scheduled-sync-status" style="margin-left: 10px; display: none;"></span>';
        echo '</div>';
        
        echo '</div>'; // Cierre de la sección de sincronización programada
        
        echo '</div>'; // Cierre del contenedor de información de la API
        
        echo '</div>'; // Cierre de la tarjeta de conexión
        
        // Sección de registros
        echo '<div class="optica-card" style="margin-top: 20px;">';
        echo '<h2>Registro de Actividades</h2>';
        echo '<div id="sync-logs" class="log-container" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9; border-radius: 4px;">';
        echo '<p>Los registros aparecerán aquí durante la operación.</p>';
        echo '</div>';
        echo '</div>';
        
        // Última sincronización
        if ($last_sync) {
            echo '<div style="margin-top: 15px; font-style: italic; color: #666;">';
            echo 'Última sincronización: ' . date_i18n('d/m/Y H:i:s', $last_sync);
            echo '</div>';
        }
        
        // Nonce para seguridad
        $nonce_value = wp_create_nonce('optica_vision_nonce');
        echo '<script>console.log("Nonce value:", "' . $nonce_value . '");</script>';
        echo '<input type="hidden" id="optica_vision_nonce" name="optica_vision_nonce" value="' . $nonce_value . '" />';
        
        echo '</div>'; // Cierre de .wrap
    }
    
    /**
     * Render API section
     */
    public function render_api_section() {
        echo '<p>Configure la conexión con la API de Optica Vision</p>';
    }
    
    /**
     * Render API URL field
     */
    public function render_api_url_field() {
        $options = get_option('optica_vision_options');
        ?>
        <input type="text" id="api_url" name="optica_vision_options[api_url]" value="<?php echo $options['api_url']; ?>">
        <?php
    }
    
    /**
     * Render sync section
     */
    public function render_sync_section() {
        echo '<p>Configure la sincronización de productos</p>';
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
     * AJAX: Sync products with enhanced security
     */
    public function ajax_sync_products() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        try {
            $result = $this->plugin->product_sync->sync_products();
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }
        
        wp_send_json_success([
                'message' => 'Sync completed successfully',
                'stats' => $result
        ]);
            
        } catch (Exception $e) {
            error_log('Sync AJAX error: ' . $e->getMessage());
            wp_send_json_error('An unexpected error occurred during synchronization');
        }
    }
    
    /**
     * AJAX: Test connection with enhanced security
     */
    public function ajax_test_connection() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $result = $this->plugin->api->test_connection();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success([
            'message' => 'Connection test successful',
            'sample_data' => array_slice($result['items'] ?? $result, 0, 5)
        ]);
    }
    
    /**
     * AJAX: Check connection status
     */
    public function ajax_check_connection() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        wp_send_json_success([
            'connected' => $this->plugin->api->is_connected()
        ]);
    }
    
    /**
     * AJAX: Save API settings with enhanced security
     */
    public function ajax_save_settings() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['optica_vision_settings_nonce'] ?? '', 'optica_vision_settings')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Sanitize and validate inputs
        $api_url = esc_url_raw($_POST['api_url'] ?? '');
        $api_username = sanitize_text_field($_POST['api_username'] ?? '');
        $api_password = sanitize_text_field($_POST['api_password'] ?? '');
        
        if (empty($api_url) || empty($api_username) || empty($api_password)) {
            wp_send_json_error('All fields are required');
        }
        
        // Validate URL format
        if (!filter_var($api_url, FILTER_VALIDATE_URL)) {
            wp_send_json_error('Invalid API URL format');
        }
        
        // Save settings
        update_option('optica_vision_api_url', $api_url);
        update_option('optica_vision_api_username', $api_username);
        update_option('optica_vision_api_password', $api_password);
        
        // Clear existing token to force reconnection
        delete_option('optica_vision_api_token');
        
        wp_send_json_success('Settings saved successfully');
    }
    
    /**
     * AJAX: Get sync logs
     */
    public function ajax_get_sync_logs() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $logs = get_option('optica_vision_sync_logs', []);
        
        // Format logs for display
        $formatted_logs = array_map(function($log) {
            return [
                'timestamp' => $log['timestamp'],
                'level' => $log['level'],
                'message' => $log['message'],
                'formatted_time' => date('Y-m-d H:i:s', strtotime($log['timestamp']))
            ];
        }, $logs);
        
        wp_send_json_success($formatted_logs);
    }
    
    /**
     * AJAX: Get backup list
     */
    public function ajax_get_backups() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $backup_options = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} 
             WHERE option_name LIKE 'optica_vision_backup_%' 
             ORDER BY option_name DESC"
        );
        
        $backups = [];
        foreach ($backup_options as $option) {
            $timestamp = str_replace('optica_vision_backup_', '', $option->option_name);
            $backup_data = maybe_unserialize($option->option_value);
            
            if (is_array($backup_data)) {
                $backups[] = [
                    'key' => $option->option_name,
                    'timestamp' => $timestamp,
                    'formatted_date' => date('Y-m-d H:i:s', $timestamp),
                    'product_count' => count($backup_data)
                ];
            }
        }
        
        wp_send_json_success($backups);
    }
    
    /**
     * AJAX: Restore backup
     */
    public function ajax_restore_backup() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $backup_key = sanitize_text_field($_POST['backup_key'] ?? '');
        
        if (empty($backup_key) || !preg_match('/^optica_vision_backup_\d+$/', $backup_key)) {
            wp_send_json_error('Invalid backup key');
        }
        
        $backup_data = get_option($backup_key);
        
        if (!$backup_data || !is_array($backup_data)) {
            wp_send_json_error('Backup not found or corrupted');
        }
        
        try {
            $restored = 0;
            $errors = 0;
            
            foreach ($backup_data as $product_backup) {
                $product_id = $product_backup['id'];
                $product = wc_get_product($product_id);
                
                if ($product) {
                    // Restore basic product data
                    $product->set_name($product_backup['name']);
                    $product->set_regular_price($product_backup['price']);
                    $product->set_stock_quantity($product_backup['stock']);
                    
                    // Restore meta data
                    if (isset($product_backup['meta']) && is_array($product_backup['meta'])) {
                        foreach ($product_backup['meta'] as $meta_key => $meta_values) {
                            if (is_array($meta_values) && !empty($meta_values)) {
                                update_post_meta($product_id, $meta_key, $meta_values[0]);
                            }
                        }
                    }
                    
                    $product->save();
                    $restored++;
                } else {
                    $errors++;
                }
            }
            
            wp_send_json_success([
                'message' => sprintf('Backup restored successfully. %d products restored, %d errors.', $restored, $errors),
                'restored' => $restored,
                'errors' => $errors
            ]);
            
        } catch (Exception $e) {
            error_log('Backup restore error: ' . $e->getMessage());
            wp_send_json_error('Failed to restore backup: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Get scheduled sync status
     */
    public function ajax_get_scheduled_sync_status() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $is_active = wp_next_scheduled('optica_vision_scheduled_sync') !== false;
        $interval = get_option('optica_vision_sync_interval', 'daily');
        
        wp_send_json_success(array(
            'active' => $is_active,
            'interval' => $interval
        ));
    }
    
    /**
     * Habilitar la sincronización programada
     */
    public function ajax_enable_scheduled_sync() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $interval = isset($_POST['interval']) ? sanitize_text_field($_POST['interval']) : 'daily';
        
        // Validar intervalo
        $valid_intervals = array('hourly', 'twicedaily', 'daily');
        if (!in_array($interval, $valid_intervals)) {
            $interval = 'daily';
        }
        
        // Programar el evento si no está ya programado
        if (!wp_next_scheduled('optica_vision_scheduled_sync')) {
            wp_schedule_event(time(), $interval, 'optica_vision_scheduled_sync');
            update_option('optica_vision_sync_interval', $interval);
            
            wp_send_json_success(array(
                'message' => 'Sincronización programada activada correctamente',
                'interval' => $interval
            ));
        } else {
            wp_send_json_error('La sincronización programada ya está activa');
        }
    }
    
    /**
     * Deshabilitar la sincronización programada
     */
    public function ajax_disable_scheduled_sync() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $timestamp = wp_next_scheduled('optica_vision_scheduled_sync');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'optica_vision_scheduled_sync');
            wp_send_json_success('Sincronización programada desactivada correctamente');
        } else {
            wp_send_json_error('La sincronización programada no está activa');
        }
    }
    
    /**
     * Actualizar el intervalo de sincronización programada
     */
    public function ajax_update_sync_interval() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $interval = isset($_POST['interval']) ? sanitize_text_field($_POST['interval']) : 'daily';
        
        // Validar intervalo
        $valid_intervals = array('hourly', 'twicedaily', 'daily');
        if (!in_array($interval, $valid_intervals)) {
            $interval = 'daily';
        }
        
        // Actualizar el intervalo solo si la sincronización está activa
        if (wp_next_scheduled('optica_vision_scheduled_sync')) {
            // Primero eliminamos el evento actual
            $timestamp = wp_next_scheduled('optica_vision_scheduled_sync');
            wp_unschedule_event($timestamp, 'optica_vision_scheduled_sync');
            
            // Luego lo volvemos a programar con el nuevo intervalo
            wp_schedule_event(time(), $interval, 'optica_vision_scheduled_sync');
        }
        
        // Actualizamos la opción en cualquier caso
        update_option('optica_vision_sync_interval', $interval);
        
        wp_send_json_success(array(
            'message' => 'Intervalo de sincronización actualizado correctamente',
            'interval' => $interval
        ));
    }
    
    /**
     * Eliminar todos los productos importados
     */
    public function ajax_delete_products() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $products = wc_get_products([
            'limit' => -1,
            'return' => 'ids'
        ]);
        
        $deleted = 0;
        foreach ($products as $product_id) {
            if (wp_delete_post($product_id, true)) {
                $deleted++;
            }
        }
        
        wp_send_json_success([
            'message' => sprintf('Se eliminaron %d productos', $deleted),
            'count' => $deleted
        ]);
    }
    
    /**
     * AJAX: Connect to API
     */
    public function ajax_connect() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $result = $this->plugin->api->connect();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success([
            'message' => 'Successfully connected to API',
            'connected' => true
        ]);
    }
    
    /**
     * AJAX: Force reconnect to API (clears existing token first)
     */
    public function ajax_force_reconnect() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_nonce')) {
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $result = $this->plugin->api->force_reconnect();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success([
            'message' => 'Successfully reconnected to API',
            'connected' => true
        ]);
    }
    
    /**
     * Handle manual connection request
     */
    public function handle_connection_request() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        if (isset($_GET['connect']) && $_GET['connect'] === '1' && isset($_GET['page']) && $_GET['page'] === 'optica-vision-api-sync') {
            check_admin_referer('optica_vision_connect');
            
            $result = $this->plugin->api->manual_login();
            
            if (is_wp_error($result)) {
                wp_redirect(admin_url('admin.php?page=optica-vision-api-sync&error='.$result->get_error_code()));
                exit;
            }
            
            wp_redirect(admin_url('admin.php?page=optica-vision-api-sync'));
            exit;
        }
    }
    
    /**
     * AJAX handler for getting products
     */
    public function ajax_get_products() {
        error_log('AJAX get_products called');
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'optica_vision_nonce')) {
            error_log('Nonce verification failed');
            wp_send_json_error('Invalid security token');
        }
        
        if (!current_user_can('manage_options')) {
            error_log('User permission check failed');
            wp_send_json_error('Insufficient permissions');
        }
        
        // Check connection and attempt to reconnect if needed
        if (!$this->plugin->api->is_connected()) {
            error_log('API not connected, attempting to connect...');
            $connect_result = $this->plugin->api->connect();
            if (is_wp_error($connect_result)) {
                error_log('Failed to connect to API: ' . $connect_result->get_error_message());
                wp_send_json_error('API connection failed: ' . $connect_result->get_error_message());
            }
        }
        
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : null;
        error_log('Limit set to: ' . ($limit ?? 'null'));
        
        try {
            // Get sample products for preview
            $products = $limit ? $this->plugin->api->get_products(1, $limit) : $this->plugin->api->get_all_products();
            
            if (is_wp_error($products)) {
                error_log('API returned error: ' . $products->get_error_message());
                
                // If we get a reconnect failed error, provide more specific feedback
                if ($products->get_error_code() === 'api_reconnect_failed') {
                    wp_send_json_error('Authentication expired and automatic reconnection failed. Please try using the "Force Reconnect" button.');
                }
                
                throw new Exception($products->get_error_message());
            }
            
            // Log the response for debugging (limit to first 500 chars)
            $debug_products = is_array($products) ? array_slice($products, 0, 2) : $products;
            error_log('Product response sample: ' . substr(print_r($debug_products, true), 0, 500));
            
            // Format response data
            $data = isset($products['items']) ? $products['items'] : $products;
            
            if (empty($data)) {
                error_log('No products found in response');
                wp_send_json_error('No products found');
            }
            
            error_log('Sending success response with ' . count($data) . ' products');
            wp_send_json_success($data);
            
        } catch (Exception $e) {
            error_log('Exception in ajax_get_products: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }
}
