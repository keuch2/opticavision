<?php
/**
 * Plugin Name: WooCommerce Bancard Gateway
 * Description: Pasarela de pago Bancard para WooCommerce (API 0.3). Integra single_buy, confirmación y rollback con mejores prácticas.
 * Author: Mister Co.
 * Version: 0.1.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: wc-bancard
 * Domain Path: /languages
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define constants
if ( ! defined( 'WC_BANCARD_VERSION' ) ) {
    define( 'WC_BANCARD_VERSION', '0.1.0' );
}
if ( ! defined( 'WC_BANCARD_PLUGIN_FILE' ) ) {
    define( 'WC_BANCARD_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'WC_BANCARD_PLUGIN_DIR' ) ) {
    define( 'WC_BANCARD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WC_BANCARD_PLUGIN_URL' ) ) {
    define( 'WC_BANCARD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Admin notice if WooCommerce is not active
 */
function wc_bancard_missing_wc_notice() {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'WooCommerce Bancard Gateway requiere WooCommerce activo.', 'wc-bancard' ) . '</p></div>';
}

/**
 * Init plugin once all plugins are loaded
 */
function wc_bancard_init() {
    if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_Payment_Gateway' ) ) {
        add_action( 'admin_notices', 'wc_bancard_missing_wc_notice' );
        return;
    }

    // Includes
    require_once WC_BANCARD_PLUGIN_DIR . 'includes/helpers.php';
    require_once WC_BANCARD_PLUGIN_DIR . 'includes/class-wc-bancard-logger.php';
    require_once WC_BANCARD_PLUGIN_DIR . 'includes/class-wc-bancard-api.php';
    require_once WC_BANCARD_PLUGIN_DIR . 'includes/class-wc-bancard-webhook-controller.php';
    require_once WC_BANCARD_PLUGIN_DIR . 'includes/class-wc-gateway-bancard.php';
    require_once WC_BANCARD_PLUGIN_DIR . 'includes/class-wc-bancard-diagnostics.php';
    require_once WC_BANCARD_PLUGIN_DIR . 'includes/class-wc-bancard-checkout-diagnostics.php';
    
    // Admin includes
    if (is_admin()) {
        require_once WC_BANCARD_PLUGIN_DIR . 'includes/class-wc-bancard-admin.php';
        new WC_Bancard_Admin();
    }
    
    // WooCommerce Blocks support
    if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        require_once WC_BANCARD_PLUGIN_DIR . 'includes/class-wc-bancard-blocks-support.php';
    }

    // Register payment gateway
    add_filter( 'woocommerce_payment_gateways', function( $methods ) {
        $methods[] = 'WC_Gateway_Bancard';
        return $methods;
    } );

    // Optional: register REST routes (we keep legacy /bancard/confirmation.php as official callback)
    add_action( 'rest_api_init', [ 'WC_Bancard_Webhook_Controller', 'register_routes' ] );
    
    // Register Bancard for WooCommerce Blocks
    if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        wc_bancard_register_blocks_support();
    } else {
        add_action( 'woocommerce_blocks_loaded', 'wc_bancard_register_blocks_support' );
    }
}
add_action( 'plugins_loaded', 'wc_bancard_init', 11 );

/**
 * Load plugin textdomain
 */
function wc_bancard_load_textdomain() {
    load_plugin_textdomain( 'wc-bancard', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'wc_bancard_load_textdomain' );

/**
 * Register Bancard payment method with WooCommerce Blocks
 */
function wc_bancard_register_blocks_support() {
    if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                if (class_exists('WC_Bancard_Blocks_Support')) {
                    $payment_method_registry->register( new WC_Bancard_Blocks_Support() );
                }
            }
        );
    }
}
