<?php
/**
 * Plugin Name: Optica Vision Image Matcher
 * Description: Asigna automáticamente imágenes a productos WooCommerce basado en SKUs
 * Version: 1.0.0
 * Author: Mister Co.
 * Text Domain: optica-vision-image-matcher
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('OVIM_VERSION', '1.0.0');
define('OVIM_PATH', plugin_dir_path(__FILE__));
define('OVIM_URL', plugin_dir_url(__FILE__));

// Include required files
require_once OVIM_PATH . 'includes/class-image-processor.php';

/**
 * Main plugin class
 */
class Optica_Vision_Image_Matcher {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Check if WooCommerce is active
        if (!$this->check_woocommerce()) {
            return;
        }
        
        // Hook into WordPress
        $this->init_hooks();
    }
    
    /**
     * Check if WooCommerce is active
     */
    private function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>';
                echo __('Optica Vision Image Matcher requiere WooCommerce para funcionar.', 'optica-vision-image-matcher');
                echo '</p></div>';
            });
            return false;
        }
        return true;
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Hook into media upload
        add_filter('wp_handle_upload', array($this, 'handle_image_upload'), 10, 2);
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add bulk upload option
        add_action('admin_footer', array($this, 'add_bulk_upload_button'));
        
        // Handle bulk upload
        add_action('admin_post_ovim_bulk_upload', array($this, 'handle_bulk_upload'));
        
        // Handle processing of existing images
        add_action('admin_post_ovim_process_existing', array($this, 'handle_process_existing'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Asignación de Imágenes', 'optica-vision-image-matcher'),
            __('Asignación de Imágenes', 'optica-vision-image-matcher'),
            'manage_woocommerce',
            'ovim-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('ovim_settings', 'ovim_auto_assign', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ));
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Asignación de Imágenes por SKU', 'optica-vision-image-matcher'); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('ovim_settings'); ?>
                <?php do_settings_sections('ovim_settings'); ?>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('Asignación automática', 'optica-vision-image-matcher'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ovim_auto_assign" value="1" <?php checked(get_option('ovim_auto_assign', true)); ?> />
                                <?php echo esc_html__('Asignar automáticamente imágenes a productos al subirlas', 'optica-vision-image-matcher'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <hr>
            
            <h2><?php echo esc_html__('Subida masiva de imágenes', 'optica-vision-image-matcher'); ?></h2>
            <p><?php echo esc_html__('Sube múltiples imágenes y asígnalas automáticamente a los productos correspondientes.', 'optica-vision-image-matcher'); ?></p>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="ovim_bulk_upload">
                <?php wp_nonce_field('ovim_bulk_upload', 'ovim_nonce'); ?>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('Imágenes', 'optica-vision-image-matcher'); ?></th>
                        <td>
                            <input type="file" name="ovim_images[]" multiple accept="image/*">
                            <p class="description"><?php echo esc_html__('Selecciona múltiples imágenes para subir. Los nombres de archivo deben coincidir con los SKUs de los productos.', 'optica-vision-image-matcher'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Subir y asignar imágenes', 'optica-vision-image-matcher')); ?>
            </form>
            
            <hr>
            
            <h2><?php echo esc_html__('Procesar imágenes existentes', 'optica-vision-image-matcher'); ?></h2>
            <p><?php echo esc_html__('Busca en la biblioteca de medios imágenes que coincidan con SKUs de productos y asígnalas.', 'optica-vision-image-matcher'); ?></p>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="ovim_process_existing">
                <?php wp_nonce_field('ovim_process_existing', 'ovim_nonce'); ?>
                
                <?php submit_button(__('Procesar imágenes existentes', 'optica-vision-image-matcher'), 'secondary'); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Add bulk upload button to media library
     */
    public function add_bulk_upload_button() {
        $screen = get_current_screen();
        
        if ($screen && $screen->id === 'upload') {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('.page-title-action').after('<a href="<?php echo esc_url(admin_url('admin.php?page=ovim-settings')); ?>" class="page-title-action"><?php echo esc_html__('Asignar a productos', 'optica-vision-image-matcher'); ?></a>');
                });
            </script>
            <?php
        }
    }
    
    /**
     * Handle image upload
     */
    public function handle_image_upload($file, $context) {
        // Check if auto-assign is enabled
        if (!get_option('ovim_auto_assign', true)) {
            return $file;
        }
        
        // Only process images
        if (strpos($file['type'], 'image/') !== 0) {
            return $file;
        }
        
        // Schedule the image processing to happen after the upload is complete
        add_action('add_attachment', function($attachment_id) use ($file) {
            $this->process_image($attachment_id, $file);
        });
        
        return $file;
    }
    
    /**
     * Process an image and assign it to a product if SKU matches
     */
    public function process_image($attachment_id, $file = null) {
        // Get file info if not provided
        if (!$file) {
            $file = get_attached_file($attachment_id);
            if (!$file) {
                return false;
            }
        }
        
        // Get filename without extension
        $filename = pathinfo(basename($file['file'] ?? $file), PATHINFO_FILENAME);
        
        // Clean the filename (remove any non-alphanumeric characters)
        $sku = preg_replace('/[^a-zA-Z0-9]/', '', $filename);
        
        // Find product by SKU
        $product_id = OVIM_Image_Processor::get_product_id_by_sku($sku);
        
        if ($product_id) {
            // Set product image
            OVIM_Image_Processor::set_product_image($product_id, $attachment_id);
            
            // Log the assignment
            error_log(sprintf('Assigned image %s (ID: %d) to product %d with SKU %s', 
                basename($file['file'] ?? $file), 
                $attachment_id, 
                $product_id, 
                $sku
            ));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Handle processing of existing images
     */
    public function handle_process_existing() {
        // Check nonce
        if (!isset($_POST['ovim_nonce']) || !wp_verify_nonce($_POST['ovim_nonce'], 'ovim_process_existing')) {
            wp_die(__('Error de seguridad. Por favor, intenta de nuevo.', 'optica-vision-image-matcher'));
        }
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'optica-vision-image-matcher'));
        }
        
        // Process existing images
        $results = OVIM_Image_Processor::process_existing_images();
        
        // Redirect back with status
        $redirect = add_query_arg(
            array(
                'message'  => 'process-complete',
                'total'    => $results['total'],
                'matched'  => $results['matched'],
                'already'  => $results['already_assigned']
            ),
            admin_url('admin.php?page=ovim-settings')
        );
        
        wp_redirect($redirect);
        exit;
    }
    
    /**
     * Handle bulk upload
     */
    public function handle_bulk_upload() {
        // Check nonce
        if (!isset($_POST['ovim_nonce']) || !wp_verify_nonce($_POST['ovim_nonce'], 'ovim_bulk_upload')) {
            wp_die(__('Error de seguridad. Por favor, intenta de nuevo.', 'optica-vision-image-matcher'));
        }
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'optica-vision-image-matcher'));
        }
        
        // Check if files were uploaded
        if (empty($_FILES['ovim_images']['name']) || !is_array($_FILES['ovim_images']['name'])) {
            wp_redirect(add_query_arg('message', 'no-files', admin_url('admin.php?page=ovim-settings')));
            exit;
        }
        
        $uploaded = 0;
        $assigned = 0;
        $errors = 0;
        
        // Process each file
        foreach ($_FILES['ovim_images']['name'] as $key => $value) {
            if ($_FILES['ovim_images']['error'][$key] === 0) {
                $file = array(
                    'name'     => $_FILES['ovim_images']['name'][$key],
                    'type'     => $_FILES['ovim_images']['type'][$key],
                    'tmp_name' => $_FILES['ovim_images']['tmp_name'][$key],
                    'error'    => $_FILES['ovim_images']['error'][$key],
                    'size'     => $_FILES['ovim_images']['size'][$key]
                );
                
                // Upload file
                $upload = wp_handle_upload($file, array('test_form' => false));
                
                if (!isset($upload['error'])) {
                    $uploaded++;
                    
                    // Add to media library
                    $attachment = array(
                        'guid'           => $upload['url'],
                        'post_mime_type' => $upload['type'],
                        'post_title'     => preg_replace('/\.[^.]+$/', '', basename($upload['file'])),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    );
                    
                    $attachment_id = wp_insert_attachment($attachment, $upload['file']);
                    
                    if (!is_wp_error($attachment_id)) {
                        // Generate metadata
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
                        wp_update_attachment_metadata($attachment_id, $attachment_data);
                        
                        // Process image
                        if ($this->process_image($attachment_id, $upload)) {
                            $assigned++;
                        }
                    }
                } else {
                    $errors++;
                }
            } else {
                $errors++;
            }
        }
        
        // Redirect back with status
        $redirect = add_query_arg(
            array(
                'message'  => 'upload-complete',
                'uploaded' => $uploaded,
                'assigned' => $assigned,
                'errors'   => $errors
            ),
            admin_url('admin.php?page=ovim-settings')
        );
        
        wp_redirect($redirect);
        exit;
    }
}

// Initialize plugin
add_action('plugins_loaded', function() {
    Optica_Vision_Image_Matcher::get_instance();
});

// Add admin notice for upload status
add_action('admin_notices', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'ovim-settings' && isset($_GET['message'])) {
        $message = '';
        $type = 'info';
        
        switch ($_GET['message']) {
            case 'upload-complete':
                $uploaded = intval($_GET['uploaded'] ?? 0);
                $assigned = intval($_GET['assigned'] ?? 0);
                $errors = intval($_GET['errors'] ?? 0);
                
                $message = sprintf(
                    __('Proceso completado: %d imágenes subidas, %d asignadas a productos, %d errores.', 'optica-vision-image-matcher'),
                    $uploaded,
                    $assigned,
                    $errors
                );
                $type = $errors > 0 ? 'warning' : 'success';
                break;
                
            case 'process-complete':
                $total = intval($_GET['total'] ?? 0);
                $matched = intval($_GET['matched'] ?? 0);
                $already = intval($_GET['already'] ?? 0);
                
                $message = sprintf(
                    __('Procesamiento completado: %d imágenes analizadas, %d asignadas a productos, %d ya estaban asignadas.', 'optica-vision-image-matcher'),
                    $total,
                    $matched,
                    $already
                );
                $type = 'success';
                break;
                
            case 'no-files':
                $message = __('No se seleccionaron archivos para subir.', 'optica-vision-image-matcher');
                $type = 'error';
                break;
        }
        
        if ($message) {
            printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>', $type, $message);
        }
    }
});
