<?php
/**
 * Stock Control Admin Class
 *
 * Handles admin interface and settings
 *
 * @package OpticaVision_Stock_Control
 */

defined('ABSPATH') || exit;

/**
 * Optica_Vision_Stock_Control_Admin class
 */
class Optica_Vision_Stock_Control_Admin {

    /**
     * Initialize admin functionality
     */
    public function init() {
        // Add menu page
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add admin styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Control de Stock', 'optica-vision-stock-control'),
            __('Control de Stock', 'optica-vision-stock-control'),
            'manage_woocommerce',
            'optica-stock-control',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting(
            'optica_stock_control_settings_group',
            'optica_stock_control_settings',
            array($this, 'sanitize_settings')
        );

        add_settings_section(
            'optica_stock_control_main_section',
            __('Configuración de Visibilidad de Productos', 'optica-vision-stock-control'),
            array($this, 'render_section_description'),
            'optica-stock-control'
        );

        add_settings_field(
            'hide_simple_out_of_stock',
            __('Productos Simples sin Stock', 'optica-vision-stock-control'),
            array($this, 'render_simple_products_field'),
            'optica-stock-control',
            'optica_stock_control_main_section'
        );

        add_settings_field(
            'hide_variable_out_of_stock',
            __('Productos Variables sin Variaciones', 'optica-vision-stock-control'),
            array($this, 'render_variable_products_field'),
            'optica-stock-control',
            'optica_stock_control_main_section'
        );

        add_settings_field(
            'hide_without_featured_image',
            __('Productos sin Imagen Destacada', 'optica-vision-stock-control'),
            array($this, 'render_featured_image_field'),
            'optica-stock-control',
            'optica_stock_control_main_section'
        );
    }

    /**
     * Render section description
     */
    public function render_section_description() {
        ?>
        <p>
            <?php esc_html_e('Configure qué productos desea ocultar del catálogo basándose en su disponibilidad de stock.', 'optica-vision-stock-control'); ?>
        </p>
        <div class="notice notice-info inline">
            <p>
                <strong><?php esc_html_e('Nota:', 'optica-vision-stock-control'); ?></strong>
                <?php esc_html_e('Los cambios se aplicarán inmediatamente en todo el sitio (tienda, búsquedas, categorías, etc.).', 'optica-vision-stock-control'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render simple products field
     */
    public function render_simple_products_field() {
        $settings = get_option('optica_stock_control_settings', array());
        $value = isset($settings['hide_simple_out_of_stock']) ? $settings['hide_simple_out_of_stock'] : 'no';
        ?>
        <label>
            <input 
                type="checkbox" 
                name="optica_stock_control_settings[hide_simple_out_of_stock]" 
                value="yes" 
                <?php checked($value, 'yes'); ?>
            >
            <?php esc_html_e('Ocultar productos simples que no tengan stock', 'optica-vision-stock-control'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('Si se activa, los productos simples sin stock no aparecerán en la tienda, búsquedas ni categorías.', 'optica-vision-stock-control'); ?>
        </p>
        <?php
    }

    /**
     * Render variable products field
     */
    public function render_variable_products_field() {
        $settings = get_option('optica_stock_control_settings', array());
        $value = isset($settings['hide_variable_out_of_stock']) ? $settings['hide_variable_out_of_stock'] : 'no';
        ?>
        <label>
            <input 
                type="checkbox" 
                name="optica_stock_control_settings[hide_variable_out_of_stock]" 
                value="yes" 
                <?php checked($value, 'yes'); ?>
            >
            <?php esc_html_e('Ocultar productos variables que no tengan variaciones con stock', 'optica-vision-stock-control'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('Si se activa, los productos variables que no tengan ninguna variación con stock disponible no aparecerán en la tienda.', 'optica-vision-stock-control'); ?>
        </p>
        <?php
    }

    /**
     * Render featured image field
     */
    public function render_featured_image_field() {
        $settings = get_option('optica_stock_control_settings', array());
        $value = isset($settings['hide_without_featured_image']) ? $settings['hide_without_featured_image'] : 'no';
        ?>
        <label>
            <input 
                type="checkbox" 
                name="optica_stock_control_settings[hide_without_featured_image]" 
                value="yes" 
                <?php checked($value, 'yes'); ?>
            >
            <?php esc_html_e('Ocultar productos que no tengan imagen destacada', 'optica-vision-stock-control'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('Si se activa, los productos sin imagen destacada (tanto simples como variables) no aparecerán en la tienda.', 'optica-vision-stock-control'); ?>
        </p>
        <?php
    }

    /**
     * Sanitize settings before saving
     *
     * @param array $input Input settings
     * @return array Sanitized settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        $sanitized['hide_simple_out_of_stock'] = isset($input['hide_simple_out_of_stock']) && $input['hide_simple_out_of_stock'] === 'yes' ? 'yes' : 'no';
        $sanitized['hide_variable_out_of_stock'] = isset($input['hide_variable_out_of_stock']) && $input['hide_variable_out_of_stock'] === 'yes' ? 'yes' : 'no';
        $sanitized['hide_without_featured_image'] = isset($input['hide_without_featured_image']) && $input['hide_without_featured_image'] === 'yes' ? 'yes' : 'no';

        return $sanitized;
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Check user capabilities
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('No tienes permisos para acceder a esta página.', 'optica-vision-stock-control'));
        }

        // Get current settings for stats
        $settings = get_option('optica_stock_control_settings', array());
        ?>
        <div class="wrap optica-stock-control-admin">
            <h1>
                <?php echo esc_html(get_admin_page_title()); ?>
            </h1>

            <div class="optica-stock-control-header">
                <div class="optica-stock-control-stats">
                    <div class="stat-box">
                        <span class="dashicons dashicons-products"></span>
                        <div>
                            <strong><?php echo esc_html($this->get_simple_out_of_stock_count()); ?></strong>
                            <p><?php esc_html_e('Productos simples sin stock', 'optica-vision-stock-control'); ?></p>
                        </div>
                    </div>
                    <div class="stat-box">
                        <span class="dashicons dashicons-grid-view"></span>
                        <div>
                            <strong><?php echo esc_html($this->get_variable_out_of_stock_count()); ?></strong>
                            <p><?php esc_html_e('Productos variables sin variaciones', 'optica-vision-stock-control'); ?></p>
                        </div>
                    </div>
                    <div class="stat-box">
                        <span class="dashicons dashicons-format-image"></span>
                        <div>
                            <strong><?php echo esc_html($this->get_without_image_count()); ?></strong>
                            <p><?php esc_html_e('Productos sin imagen destacada', 'optica-vision-stock-control'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields('optica_stock_control_settings_group');
                do_settings_sections('optica-stock-control');
                submit_button(__('Guardar Configuración', 'optica-vision-stock-control'));
                ?>
            </form>

            <div class="optica-stock-control-info">
                <h2><?php esc_html_e('Información del Plugin', 'optica-vision-stock-control'); ?></h2>
                <p>
                    <strong><?php esc_html_e('Versión:', 'optica-vision-stock-control'); ?></strong> 
                    <?php echo esc_html(OPTICA_STOCK_CONTROL_VERSION); ?>
                </p>
                <p>
                    <?php esc_html_e('Este plugin permite controlar la visibilidad de productos en tu tienda basándose en la disponibilidad de stock.', 'optica-vision-stock-control'); ?>
                </p>
                <h3><?php esc_html_e('Funcionalidades:', 'optica-vision-stock-control'); ?></h3>
                <ul>
                    <li>✓ <?php esc_html_e('Control de productos simples sin stock', 'optica-vision-stock-control'); ?></li>
                    <li>✓ <?php esc_html_e('Control de productos variables sin variaciones disponibles', 'optica-vision-stock-control'); ?></li>
                    <li>✓ <?php esc_html_e('Control de productos sin imagen destacada', 'optica-vision-stock-control'); ?></li>
                    <li>✓ <?php esc_html_e('Integración con sistema de logging de OpticaVision', 'optica-vision-stock-control'); ?></li>
                    <li>✓ <?php esc_html_e('Aplicación automática en toda la tienda', 'optica-vision-stock-control'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Get count of simple products without stock
     *
     * @return int Count of products
     */
    private function get_simple_out_of_stock_count() {
        $args = array(
            'type' => 'simple',
            'stock_status' => 'outofstock',
            'limit' => -1,
            'return' => 'ids'
        );

        $products = wc_get_products($args);
        return count($products);
    }

    /**
     * Get count of variable products without available variations
     *
     * @return int Count of products
     */
    private function get_variable_out_of_stock_count() {
        $args = array(
            'type' => 'variable',
            'limit' => -1,
            'return' => 'ids'
        );

        $products = wc_get_products($args);
        $count = 0;

        foreach ($products as $product_id) {
            $product = wc_get_product($product_id);
            $variations = $product->get_available_variations();
            
            $has_stock = false;
            foreach ($variations as $variation) {
                $variation_obj = wc_get_product($variation['variation_id']);
                if ($variation_obj && $variation_obj->is_in_stock()) {
                    $has_stock = true;
                    break;
                }
            }

            if (!$has_stock) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get count of products without featured image
     *
     * @return int Count of products
     */
    private function get_without_image_count() {
        $args = array(
            'limit' => -1,
            'return' => 'ids'
        );

        $products = wc_get_products($args);
        $count = 0;

        foreach ($products as $product_id) {
            if (!has_post_thumbnail($product_id)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Enqueue admin styles
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_styles($hook) {
        // Only load on our admin page
        if ($hook !== 'woocommerce_page_optica-stock-control') {
            return;
        }

        // Inline styles for admin page
        $custom_css = "
            .optica-stock-control-admin .optica-stock-control-header {
                background: #fff;
                padding: 20px;
                margin: 20px 0;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
            }
            .optica-stock-control-admin .optica-stock-control-stats {
                display: flex;
                gap: 20px;
                flex-wrap: wrap;
            }
            .optica-stock-control-admin .stat-box {
                display: flex;
                align-items: center;
                gap: 15px;
                padding: 15px;
                background: #f6f7f7;
                border-radius: 4px;
                flex: 1;
                min-width: 250px;
            }
            .optica-stock-control-admin .stat-box .dashicons {
                font-size: 40px;
                width: 40px;
                height: 40px;
                color: #ff6900;
            }
            .optica-stock-control-admin .stat-box strong {
                font-size: 24px;
                display: block;
                color: #1d2327;
            }
            .optica-stock-control-admin .stat-box p {
                margin: 5px 0 0;
                color: #646970;
                font-size: 13px;
            }
            .optica-stock-control-admin .optica-stock-control-info {
                background: #fff;
                padding: 20px;
                margin: 20px 0;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
            }
            .optica-stock-control-admin .optica-stock-control-info ul {
                list-style: none;
                padding-left: 0;
            }
            .optica-stock-control-admin .optica-stock-control-info ul li {
                padding: 5px 0;
            }
        ";
        wp_add_inline_style('wp-admin', $custom_css);
    }
}
