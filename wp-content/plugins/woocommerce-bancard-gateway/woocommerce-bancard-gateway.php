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

    // Register REST route (alternative callback) and WC-API callback (primary)
    add_action( 'rest_api_init', [ 'WC_Bancard_Webhook_Controller', 'register_routes' ] );

    // Protect Bancard pending orders from WooCommerce auto-cancellation.
    // WooCommerce cancels unpaid pending orders after the "Hold stock" timeout.
    // For Bancard, the webhook may arrive late, so we must prevent premature cancellation.
    add_filter( 'woocommerce_cancel_unpaid_order', 'wc_bancard_prevent_auto_cancel', 10, 2 );
    
    // Register Bancard for WooCommerce Blocks
    if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        wc_bancard_register_blocks_support();
    } else {
        add_action( 'woocommerce_blocks_loaded', 'wc_bancard_register_blocks_support' );
    }
}
add_action( 'plugins_loaded', 'wc_bancard_init', 11 );

/**
 * Prevent WooCommerce from auto-cancelling Bancard pending orders.
 *
 * WooCommerce cancels unpaid pending orders after the "Hold stock" timeout.
 * For redirect-based gateways like Bancard, the webhook may arrive later,
 * so we query Bancard's API before allowing cancellation.
 *
 * @param bool     $cancel Whether to cancel the order.
 * @param WC_Order $order  The order being evaluated.
 * @return bool
 */
function wc_bancard_prevent_auto_cancel( $cancel, $order ) {
    if ( ! $cancel || ! $order ) {
        return $cancel;
    }

    if ( 'bancard' !== $order->get_payment_method() ) {
        return $cancel;
    }

    // Only protect orders less than 24 hours old.
    $created = $order->get_date_created();
    if ( $created && ( time() - $created->getTimestamp() ) > DAY_IN_SECONDS ) {
        WC_Bancard_Logger::info( sprintf( '[AUTO-CANCEL] Order %d is older than 24h. Allowing cancellation.', $order->get_id() ) );
        return $cancel;
    }

    // Query Bancard for the actual payment status before cancelling.
    WC_Bancard_Logger::info( sprintf( '[AUTO-CANCEL] Order %d is Bancard pending. Querying Bancard before cancelling...', $order->get_id() ) );
    $result = WC_Bancard_API::get_confirmation( $order );

    if ( ! empty( $result['confirmed'] ) ) {
        $confirmation = isset( $result['confirmation'] ) ? $result['confirmation'] : array();
        $ticket = isset( $confirmation['ticket_number'] ) ? $confirmation['ticket_number'] : '';
        $auth   = isset( $confirmation['authorization_number'] ) ? $confirmation['authorization_number'] : '';

        $order->payment_complete( $ticket );
        $order->add_order_note( sprintf(
            'Bancard: pago confirmado vía verificación anti-cancelación. Ticket: %s, Autorización: %s',
            $ticket, $auth
        ) );
        WC_Bancard_Logger::info( sprintf( '[AUTO-CANCEL] Order %d confirmed! Preventing cancellation. Status: %s', $order->get_id(), $order->get_status() ) );
        return false; // Do not cancel — payment was actually completed.
    }

    WC_Bancard_Logger::info( sprintf( '[AUTO-CANCEL] Order %d not confirmed by Bancard. Allowing cancellation.', $order->get_id() ) );
    return $cancel;
}

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
