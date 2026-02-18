<?php
/**
 * Plugin Name: OpticaVision Stock Control
 * Plugin URI: https://opticavision.com.py
 * Description: Control de visibilidad de productos según disponibilidad de stock. Permite ocultar productos simples sin stock y productos variables sin variaciones disponibles.
 * Version: 1.0.0
 * Author: OpticaVision
 * Author URI: https://opticavision.com.py
 * Text Domain: optica-vision-stock-control
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 *
 * @package OpticaVision_Stock_Control
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('OPTICA_STOCK_CONTROL_VERSION', '1.0.0');
define('OPTICA_STOCK_CONTROL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OPTICA_STOCK_CONTROL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OPTICA_STOCK_CONTROL_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Check if WooCommerce is active
 */
function optica_stock_control_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'optica_stock_control_woocommerce_missing_notice');
        deactivate_plugins(plugin_basename(__FILE__));
        return false;
    }
    return true;
}

/**
 * Admin notice if WooCommerce is missing
 */
function optica_stock_control_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p>
            <?php
            echo esc_html__(
                'OpticaVision Stock Control requiere WooCommerce para funcionar. Por favor instale y active WooCommerce.',
                'optica-vision-stock-control'
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * Initialize the plugin
 */
function optica_stock_control_init() {
    // Check WooCommerce
    if (!optica_stock_control_check_woocommerce()) {
        return;
    }

    // Load text domain
    load_plugin_textdomain(
        'optica-vision-stock-control',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );

    // Include required files
    require_once OPTICA_STOCK_CONTROL_PLUGIN_DIR . 'includes/class-stock-control.php';
    require_once OPTICA_STOCK_CONTROL_PLUGIN_DIR . 'includes/class-stock-control-admin.php';

    // Initialize classes
    $stock_control = new Optica_Vision_Stock_Control();
    $stock_control->init();

    if (is_admin()) {
        $stock_control_admin = new Optica_Vision_Stock_Control_Admin();
        $stock_control_admin->init();
    }
}
add_action('plugins_loaded', 'optica_stock_control_init');

/**
 * Activation hook
 */
function optica_stock_control_activate() {
    // Set default options
    if (get_option('optica_stock_control_settings') === false) {
        $default_settings = array(
            'hide_simple_out_of_stock' => 'no',
            'hide_variable_out_of_stock' => 'no',
            'hide_without_featured_image' => 'no'
        );
        add_option('optica_stock_control_settings', $default_settings);
    }

    // Log activation
    if (function_exists('optica_log_info')) {
        optica_log_info('OpticaVision Stock Control activado', array(
            'version' => OPTICA_STOCK_CONTROL_VERSION
        ));
    }
}
register_activation_hook(__FILE__, 'optica_stock_control_activate');

/**
 * Deactivation hook
 */
function optica_stock_control_deactivate() {
    // Log deactivation
    if (function_exists('optica_log_info')) {
        optica_log_info('OpticaVision Stock Control desactivado');
    }
}
register_deactivation_hook(__FILE__, 'optica_stock_control_deactivate');

/**
 * Add settings link in plugins page
 */
function optica_stock_control_plugin_action_links($links) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url('admin.php?page=optica-stock-control'),
        esc_html__('Configuración', 'optica-vision-stock-control')
    );
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . OPTICA_STOCK_CONTROL_PLUGIN_BASENAME, 'optica_stock_control_plugin_action_links');
