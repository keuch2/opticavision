<?php
/**
 * Bancard Payment Confirmation Bridge
 * 
 * This file serves as the server-to-server callback endpoint for Bancard's vPOS API.
 * Configure this URL in Bancard's merchant dashboard as the confirmation URL:
 * https://opticavision.com.py/wp-content/plugins/woocommerce-bancard-gateway/confirmation.php
 * 
 * Alternatively, use the REST API endpoint:
 * https://opticavision.com.py/wp-json/wc-bancard/v1/confirmation
 * 
 * This bridge loads WordPress and delegates to WC_Bancard_Webhook_Controller.
 */

// Prevent direct browser access with GET
if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
    http_response_code( 405 );
    header( 'Content-Type: application/json' );
    echo json_encode( array( 'status' => 'error', 'message' => 'Only POST requests are accepted' ) );
    exit;
}

// Read the raw POST body before loading WordPress (which may consume php://input)
$raw_body = file_get_contents( 'php://input' );

// Log immediately (before WP load) for debugging
$log_file = __DIR__ . '/confirmation-debug.log';
$timestamp = date( 'Y-m-d H:i:s' );
file_put_contents( $log_file, "[{$timestamp}] Confirmation received. Length: " . strlen( $raw_body ) . "\n", FILE_APPEND );

// Load WordPress
$wp_load_paths = array(
    dirname( __FILE__ ) . '/../../../../wp-load.php',      // Standard: wp-content/plugins/plugin-name/
    dirname( __FILE__ ) . '/../../../wp-load.php',          // Alternative
);

$wp_loaded = false;
foreach ( $wp_load_paths as $path ) {
    if ( file_exists( $path ) ) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if ( ! $wp_loaded ) {
    file_put_contents( $log_file, "[{$timestamp}] FATAL: Could not find wp-load.php\n", FILE_APPEND );
    http_response_code( 500 );
    header( 'Content-Type: application/json' );
    echo json_encode( array( 'status' => 'error', 'message' => 'Server configuration error' ) );
    exit;
}

// Ensure WooCommerce and our plugin are loaded
if ( ! class_exists( 'WC_Bancard_Webhook_Controller' ) ) {
    file_put_contents( $log_file, "[{$timestamp}] FATAL: WC_Bancard_Webhook_Controller class not found\n", FILE_APPEND );
    http_response_code( 500 );
    header( 'Content-Type: application/json' );
    echo json_encode( array( 'status' => 'error', 'message' => 'Plugin not loaded' ) );
    exit;
}

// Delegate to the webhook controller
$response = WC_Bancard_Webhook_Controller::handle_confirmation_raw( $raw_body );

file_put_contents( $log_file, "[{$timestamp}] Response: code={$response['code']} body=" . json_encode( $response['body'] ) . "\n", FILE_APPEND );

// Send response
http_response_code( $response['code'] );
header( 'Content-Type: application/json' );
echo json_encode( $response['body'] );
exit;
