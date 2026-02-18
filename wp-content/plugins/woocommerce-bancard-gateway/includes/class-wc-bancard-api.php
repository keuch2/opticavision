<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Bancard_API {

    /**
     * Get base URL depending on environment.
     */
    protected static function base_url( $settings = null ) {
        $settings = is_array( $settings ) ? $settings : wc_bancard_get_settings();
        $sandbox = wc_bancard_is_sandbox( $settings );
        return $sandbox ? 'https://vpos.infonet.com.py:8888' : 'https://vpos.infonet.com.py';
    }

    /**
     * Create single_buy operation.
     *
     * @param WC_Order $order
     * @param array $args { amount, currency, description, return_url, cancel_url }
     * @return array { status: success|error, process_id?: string, raw?: array, error?: string }
     */
    public static function single_buy( $order, $args = array() ) {
        $settings = wc_bancard_get_settings();
        $public  = isset( $settings['public_key'] ) ? $settings['public_key'] : '';
        $private = isset( $settings['private_key'] ) ? $settings['private_key'] : '';

        if ( empty( $public ) || empty( $private ) ) {
            return array( 'status' => 'error', 'error' => 'Missing Bancard keys' );
        }

        $amount   = wc_bancard_format_amount( $args['amount'] );
        $currency = isset( $args['currency'] ) ? $args['currency'] : 'PYG';
        $desc     = isset( $args['description'] ) ? $args['description'] : sprintf( 'Order #%d', $order->get_id() );
        $ret      = isset( $args['return_url'] ) ? $args['return_url'] : $order->get_checkout_order_received_url();
        $cancel   = isset( $args['cancel_url'] ) ? $args['cancel_url'] : $order->get_checkout_order_received_url();

        $shop_process_id = (int) $order->get_id();
        $token = md5( $private . $shop_process_id . $amount . $currency );

        $payload = array(
            'public_key' => $public,
            'operation'  => array(
                'currency'       => $currency,
                'token'          => $token,
                'amount'         => $amount,
                'additional_data'=> '',
                'return_url'     => $ret,
                'cancel_url'     => $cancel,
                'description'    => $desc,
                'shop_process_id'=> $shop_process_id,
            ),
        );

        $url = trailingslashit( self::base_url( $settings ) ) . 'vpos/api/0.3/single_buy';
        $resp = self::post_json( $url, $payload );

        if ( is_wp_error( $resp ) ) {
            WC_Bancard_Logger::error( 'single_buy error: ' . $resp->get_error_message() );
            return array( 'status' => 'error', 'error' => $resp->get_error_message() );
        }

        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        $code = wp_remote_retrieve_response_code( $resp );
        WC_Bancard_Logger::info( 'single_buy response code ' . $code . ' body: ' . wp_json_encode( $data ) );

        if ( $code >= 200 && $code < 300 && isset( $data['status'] ) && $data['status'] === 'success' && ! empty( $data['process_id'] ) ) {
            return array( 'status' => 'success', 'process_id' => $data['process_id'], 'raw' => $data );
        }

        return array( 'status' => 'error', 'error' => 'Unexpected response', 'raw' => $data );
    }

    /**
     * Perform rollback for the given order.
     *
     * @param WC_Order $order
     * @return array { status: success|error, raw?: array, error?: string }
     */
    public static function rollback( $order ) {
        $settings = wc_bancard_get_settings();
        $public  = isset( $settings['public_key'] ) ? $settings['public_key'] : '';
        $private = isset( $settings['private_key'] ) ? $settings['private_key'] : '';

        if ( empty( $public ) || empty( $private ) ) {
            return array( 'status' => 'error', 'error' => 'Missing Bancard keys' );
        }

        $shop_process_id = (int) $order->get_id();
        $token = md5( $private . $shop_process_id . 'rollback' . '0.00' );

        $payload = array(
            'public_key' => $public,
            'operation'  => array(
                'token'          => $token,
                'shop_process_id'=> $shop_process_id,
            ),
        );

        $url = trailingslashit( self::base_url( $settings ) ) . 'vpos/api/0.3/single_buy/rollback';
        $resp = self::post_json( $url, $payload );

        if ( is_wp_error( $resp ) ) {
            WC_Bancard_Logger::error( 'rollback error: ' . $resp->get_error_message() );
            return array( 'status' => 'error', 'error' => $resp->get_error_message() );
        }

        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        $code = wp_remote_retrieve_response_code( $resp );
        WC_Bancard_Logger::info( 'rollback response code ' . $code . ' body: ' . wp_json_encode( $data ) );

        if ( $code >= 200 && $code < 300 && isset( $data['status'] ) && $data['status'] === 'success' ) {
            return array( 'status' => 'success', 'raw' => $data );
        }

        return array( 'status' => 'error', 'error' => 'Unexpected response', 'raw' => $data );
    }

    /**
     * Get the hosted payment URL for redirect.
     */
    public static function payment_redirect_url( $process_id, $settings = null ) {
        $base = self::base_url( $settings );
        return trailingslashit( $base ) . 'payment/single_buy?process_id=' . rawurlencode( $process_id );
    }

    /**
     * Perform POST with JSON body via WP HTTP API.
     */
    protected static function post_json( $url, $payload ) {
        $args = array(
            'method'      => 'POST',
            'timeout'     => 20,
            'redirection' => 2,
            'headers'     => array(
                'Content-Type' => 'application/json',
            ),
            'body'        => wp_json_encode( $payload ),
        );
        return wp_remote_post( $url, $args );
    }
}
