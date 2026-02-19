<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Bancard_Webhook_Controller {

    /**
     * Register optional REST route (not used as official callback yet).
     */
    public static function register_routes() {
        register_rest_route(
            'wc-bancard/v1',
            '/confirmation',
            array(
                'methods'             => 'POST',
                'callback'            => array( __CLASS__, 'handle_rest_request' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    /**
     * REST entrypoint wrapper
     */
    public static function handle_rest_request( WP_REST_Request $request ) {
        $raw = $request->get_body();
        WC_Bancard_Logger::info( 'Webhook REST request received. Body length: ' . strlen( $raw ) );
        $response = self::handle_confirmation_raw( $raw );
        if ( is_wp_error( $response ) ) {
            WC_Bancard_Logger::error( 'Webhook REST error: ' . $response->get_error_message() );
            return new WP_REST_Response( array( 'status' => 'error', 'message' => $response->get_error_message() ), 400 );
        }
        WC_Bancard_Logger::info( 'Webhook REST response: code=' . $response['code'] . ' body=' . wp_json_encode( $response['body'] ) );
        return new WP_REST_Response( $response['body'], $response['code'] );
    }

    /**
     * Core confirmation handler used by both REST route and legacy bridge.
     *
     * @param string $raw_body
     * @return array { code: int, body: array }
     */
    public static function handle_confirmation_raw( $raw_body ) {
        WC_Bancard_Logger::info( '[WEBHOOK] handle_confirmation_raw called. Body: ' . substr( $raw_body, 0, 500 ) );

        $settings = wc_bancard_get_settings();
        $private  = isset( $settings['private_key'] ) ? $settings['private_key'] : '';
        if ( empty( $private ) ) {
            WC_Bancard_Logger::error( '[WEBHOOK] Missing private key in settings' );
            return array( 'code' => 500, 'body' => array( 'status' => 'error', 'message' => 'Missing private key' ) );
        }

        $data = json_decode( $raw_body, true );
        if ( ! is_array( $data ) || ! isset( $data['operation'] ) ) {
            WC_Bancard_Logger::error( '[WEBHOOK] Invalid payload: not JSON or missing operation key' );
            return array( 'code' => 400, 'body' => array( 'status' => 'error', 'message' => 'Invalid payload' ) );
        }

        $op = $data['operation'];
        $shop_process_id      = isset( $op['shop_process_id'] ) ? (int) $op['shop_process_id'] : 0;
        $token                = isset( $op['token'] ) ? (string) $op['token'] : '';
        $currency             = isset( $op['currency'] ) ? (string) $op['currency'] : '';
        $amount               = isset( $op['amount'] ) ? (string) $op['amount'] : '';
        $response_code        = isset( $op['response_code'] ) ? (string) $op['response_code'] : '';
        $authorization_number = isset( $op['authorization_number'] ) ? (string) $op['authorization_number'] : '';
        $ticket_number        = isset( $op['ticket_number'] ) ? (string) $op['ticket_number'] : '';

        WC_Bancard_Logger::info( sprintf( '[WEBHOOK] Parsed: order=%d, response_code=%s, amount=%s, currency=%s, ticket=%s',
            $shop_process_id, $response_code, $amount, $currency, $ticket_number ) );

        if ( ! $shop_process_id || '' === $token || '' === $currency || '' === $amount ) {
            WC_Bancard_Logger::error( '[WEBHOOK] Missing required fields in operation data' );
            return array( 'code' => 400, 'body' => array( 'status' => 'error', 'message' => 'Missing required fields' ) );
        }

        $expected = md5( $private . $shop_process_id . 'confirm' . $amount . $currency );
        if ( ! hash_equals( $expected, $token ) ) {
            WC_Bancard_Logger::error( sprintf( '[WEBHOOK] Token mismatch for order %d. Expected: %s, Got: %s',
                $shop_process_id, $expected, $token ) );
            return array( 'code' => 403, 'body' => array( 'status' => 'forbidden' ) );
        }

        $order = wc_get_order( $shop_process_id );
        if ( ! $order ) {
            WC_Bancard_Logger::error( '[WEBHOOK] Order not found: ' . $shop_process_id );
            return array( 'code' => 404, 'body' => array( 'status' => 'error', 'message' => 'Order not found' ) );
        }

        WC_Bancard_Logger::info( sprintf( '[WEBHOOK] Order %d found. Current status: %s', $shop_process_id, $order->get_status() ) );

        $codes_es = array(
            '00' => 'Transacción aprobada',
            '05' => 'Tarjeta inhabilitada',
            '12' => 'Transacción inválida',
            '15' => 'Tarjeta inválida',
            '51' => 'Fondos insuficientes',
        );
        $codes_en = array(
            '00' => 'Transaction approved',
            '05' => 'Card is disabled',
            '12' => 'Invalid transaction',
            '15' => 'Invalid card',
            '51' => 'Insufficient funds',
        );

        if ( '00' === $response_code ) {
            WC_Bancard_Logger::info( sprintf( '[WEBHOOK] Payment APPROVED for order %d. Ticket: %s, Auth: %s. Calling payment_complete()...',
                $shop_process_id, $ticket_number, $authorization_number ) );
            $order->payment_complete( $ticket_number );
            $order->add_order_note( sprintf( 'Bancard: pago aprobado. Ticket: %s, Autorización: %s', $ticket_number, $authorization_number ) );
            WC_Bancard_Logger::info( sprintf( '[WEBHOOK] payment_complete() done for order %d. New status: %s',
                $shop_process_id, $order->get_status() ) );
        } else {
            $msg_es = isset( $codes_es[ $response_code ] ) ? $codes_es[ $response_code ] : 'Código desconocido';
            $msg_en = isset( $codes_en[ $response_code ] ) ? $codes_en[ $response_code ] : 'Unknown code';
            WC_Bancard_Logger::warning( sprintf( '[WEBHOOK] Payment FAILED for order %d. Code: %s, Reason: %s',
                $shop_process_id, $response_code, $msg_en ) );
            $order->update_status( 'failed', sprintf( 'Bancard: pago rechazado. Código: %s, Motivo: %s', $response_code, $msg_es ) );
            update_post_meta( $order->get_id(), WC_BANCARD_META_ERROR, sprintf( 'Response code: %s, error: %s', $response_code, $msg_en ) );
        }

        return array( 'code' => 200, 'body' => array( 'status' => 'success', 'info' => 'processed by WooCommerce Bancard Gateway' ) );
    }
}
