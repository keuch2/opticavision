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
        $response = self::handle_confirmation_raw( $raw );
        if ( is_wp_error( $response ) ) {
            return new WP_REST_Response( array( 'status' => 'error', 'message' => $response->get_error_message() ), 400 );
        }
        return new WP_REST_Response( $response['body'], $response['code'] );
    }

    /**
     * Core confirmation handler used by both REST route and legacy bridge.
     *
     * @param string $raw_body
     * @return array { code: int, body: array }
     */
    public static function handle_confirmation_raw( $raw_body ) {
        $settings = wc_bancard_get_settings();
        $private  = isset( $settings['private_key'] ) ? $settings['private_key'] : '';
        if ( empty( $private ) ) {
            return array( 'code' => 500, 'body' => array( 'status' => 'error', 'message' => 'Missing private key' ) );
        }

        $data = json_decode( $raw_body, true );
        if ( ! is_array( $data ) || ! isset( $data['operation'] ) ) {
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

        if ( ! $shop_process_id || '' === $token || '' === $currency || '' === $amount ) {
            return array( 'code' => 400, 'body' => array( 'status' => 'error', 'message' => 'Missing required fields' ) );
        }

        $expected = md5( $private . $shop_process_id . 'confirm' . $amount . $currency );
        if ( ! hash_equals( $expected, $token ) ) {
            return array( 'code' => 403, 'body' => array( 'status' => 'forbidden' ) );
        }

        $order = wc_get_order( $shop_process_id );
        if ( ! $order ) {
            return array( 'code' => 404, 'body' => array( 'status' => 'error', 'message' => 'Order not found' ) );
        }

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
            // success
            $order->payment_complete( $ticket_number );
            $order->add_order_note( sprintf( 'Bancard: ticket %s, authorization %s', $ticket_number, $authorization_number ) );
        } else {
            // failure
            $msg = isset( $codes_es[ $response_code ] ) ? $codes_es[ $response_code ] : '--';
            $order->add_order_note( sprintf( 'Bancard payment failed. Response code: %s, reason: %s', $response_code, $msg ) );
            update_post_meta( $order->get_id(), WC_BANCARD_META_ERROR, sprintf( 'Response code: %s, error message: %s', $response_code, isset( $codes_en[ $response_code ] ) ? $codes_en[ $response_code ] : '--' ) );
        }

        return array( 'code' => 200, 'body' => array( 'status' => 'success', 'info' => 'processed by WooCommerce Bancard Gateway' ) );
    }
}
