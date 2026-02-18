<?php
/**
 * Soporte para WooCommerce Blocks
 * Los Blocks requieren registro específico de gateways personalizados
 */

if (!defined('ABSPATH')) {
    exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Integración de Bancard con WooCommerce Blocks
 */
class WC_Bancard_Blocks_Support extends AbstractPaymentMethodType {

    /**
     * Gateway name
     */
    protected $name = 'bancard';

    /**
     * Constructor
     */
    public function __construct() {
        error_log('[BANCARD BLOCKS DEBUG] Constructor called');
        $this->initialize();
    }

    /**
     * Initialize
     */
    public function initialize() {
        error_log('[BANCARD BLOCKS DEBUG] Initialize called');
        $this->settings = get_option('woocommerce_bancard_settings', []);
        // Redact sensitive data before logging
        $safe_settings = $this->settings;
        if (isset($safe_settings['private_key'])) {
            $safe_settings['private_key'] = '[REDACTED]';
        }
        if (isset($safe_settings['public_key'])) {
            $safe_settings['public_key'] = substr($safe_settings['public_key'], 0, 8) . '...';
        }
        error_log('[BANCARD BLOCKS DEBUG] Settings loaded: ' . print_r($safe_settings, true));
    }

    /**
     * Check if gateway is active
     */
    public function is_active() {
        error_log('[BANCARD BLOCKS DEBUG] is_active() called');
        $gateway = WC()->payment_gateways()->payment_gateways()['bancard'] ?? null;
        $is_available = $gateway && $gateway->is_available();
        error_log('[BANCARD BLOCKS DEBUG] is_active() result: ' . ($is_available ? 'true' : 'false'));
        return $is_available;
    }

    /**
     * Get payment method script handles for the frontend
     */
    public function get_payment_method_script_handles() {
        $script_path = WC_BANCARD_PLUGIN_URL . 'assets/js/bancard-blocks.js';
        $script_asset_path = WC_BANCARD_PLUGIN_DIR . 'assets/js/bancard-blocks.asset.php';
        
        // Debug: Log paths para verificar
        error_log('[BANCARD BLOCKS DEBUG] Script path: ' . $script_path);
        error_log('[BANCARD BLOCKS DEBUG] Script asset path: ' . $script_asset_path);
        error_log('[BANCARD BLOCKS DEBUG] Asset file exists: ' . (file_exists($script_asset_path) ? 'YES' : 'NO'));
        error_log('[BANCARD BLOCKS DEBUG] JS file exists: ' . (file_exists(WC_BANCARD_PLUGIN_DIR . 'assets/js/bancard-blocks.js') ? 'YES' : 'NO'));
        
        $script_asset = file_exists($script_asset_path)
            ? require($script_asset_path)
            : array(
                'dependencies' => ['wc-blocks-registry', 'wp-element', 'wp-html-entities', 'wp-i18n'],
                'version' => defined('WC_BANCARD_VERSION') ? WC_BANCARD_VERSION : '1.0.0'
            );
            
        error_log('[BANCARD BLOCKS DEBUG] Dependencies: ' . implode(', ', $script_asset['dependencies']));
        
        wp_register_script(
            'wc-bancard-payments-blocks',
            $script_path,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );
        
        // Pasar datos al script usando el método correcto para Blocks
        wp_add_inline_script(
            'wc-bancard-payments-blocks',
            sprintf(
                'if (window.wc && window.wc.wcSettings && typeof window.wc.wcSettings.setSetting === "function") {
                    window.wc.wcSettings.setSetting("bancard_data", %s);
                } else {
                    console.log("WooCommerce Blocks settings not available yet, deferring Bancard configuration");
                    document.addEventListener("DOMContentLoaded", function() {
                        if (window.wc && window.wc.wcSettings && typeof window.wc.wcSettings.setSetting === "function") {
                            window.wc.wcSettings.setSetting("bancard_data", %s);
                        }
                    });
                }',
                wp_json_encode(array(
                    'title' => $this->get_setting('title', 'Bancard'),
                    'description' => $this->get_setting('description', ''),
                    'supports' => $this->get_gateway() ? $this->get_gateway()->supports : ['products']
                )),
                wp_json_encode(array(
                    'title' => $this->get_setting('title', 'Bancard'),
                    'description' => $this->get_setting('description', ''),
                    'supports' => $this->get_gateway() ? $this->get_gateway()->supports : ['products']
                ))
            ),
            'before'
        );

        error_log('[BANCARD BLOCKS DEBUG] Script registered: wc-bancard-payments-blocks');
        return array('wc-bancard-payments-blocks');
    }

    /**
     * Get payment method data for the frontend
     */
    public function get_payment_method_data() {
        $gateway = $this->get_gateway();
        
        return array(
            'title' => $gateway ? $gateway->get_title() : 'Bancard',
            'description' => $gateway ? $gateway->get_description() : '',
            'supports' => $gateway ? $gateway->supports : ['products'],
            'icon' => '', // Opcional: agregar icono
        );
    }

    /**
     * Get gateway instance
     */
    private function get_gateway() {
        if (WC() && WC()->payment_gateways()) {
            $gateways = WC()->payment_gateways()->payment_gateways();
            return isset($gateways['bancard']) ? $gateways['bancard'] : null;
        }
        return null;
    }

    /**
     * Get setting value with fallback
     * Must be protected to match parent class
     */
    protected function get_setting($key, $default = '') {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
}
