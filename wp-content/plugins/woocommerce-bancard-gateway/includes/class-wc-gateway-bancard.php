<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Gateway_Bancard extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'bancard';
        $this->method_title       = __( 'Bancard', 'wc-bancard' );
        $this->method_description = __( 'Procesa pagos con Bancard (API 0.3).', 'wc-bancard' );
        $this->has_fields         = false;
        $this->supports           = array( 'products', 'refunds' );

        $this->init_form_fields();
        $this->init_settings();

        $this->title        = $this->get_option( 'title', __( 'Bancard', 'wc-bancard' ) );
        $this->description  = $this->get_option( 'description', '' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __( 'Enable/Disable', 'wc-bancard' ),
                'type'    => 'checkbox',
                'label'   => __( 'Habilitar Bancard', 'wc-bancard' ),
                'default' => 'no',
            ),
            'title' => array(
                'title'       => __( 'Title', 'wc-bancard' ),
                'type'        => 'text',
                'description' => __( 'Esto controla el título que el usuario ve durante el checkout.', 'wc-bancard' ),
                'default'     => __( 'Bancard', 'wc-bancard' ),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __( 'Description', 'wc-bancard' ),
                'type'        => 'textarea',
                'description' => __( 'Texto que el cliente verá durante el checkout.', 'wc-bancard' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'environment' => array(
                'title'       => __( 'Environment', 'wc-bancard' ),
                'type'        => 'select',
                'description' => __( 'Seleccione Sandbox para pruebas.', 'wc-bancard' ),
                'default'     => 'production',
                'desc_tip'    => true,
                'options'     => array(
                    'production' => __( 'Production', 'wc-bancard' ),
                    'sandbox'    => __( 'Sandbox', 'wc-bancard' ),
                ),
            ),
            'public_key' => array(
                'title'       => __( 'Public Key', 'wc-bancard' ),
                'type'        => 'text',
                'description' => __( 'Clave pública provista por Bancard.', 'wc-bancard' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'private_key' => array(
                'title'       => __( 'Private Key', 'wc-bancard' ),
                'type'        => 'password',
                'description' => __( 'Clave privada provista por Bancard.', 'wc-bancard' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'exchange_rate' => array(
                'title'       => __( 'Exchange rate to PYG', 'wc-bancard' ),
                'type'        => 'text',
                'description' => __( 'Si la moneda de la tienda no es PYG, se multiplicará el total por esta tasa.', 'wc-bancard' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
        );
    }

    public function is_available() {
        if ( 'yes' !== $this->get_option( 'enabled', 'no' ) ) {
            return false;
        }
        return parent::is_available();
    }

    public function admin_options() {
        echo '<h2>' . esc_html( $this->get_method_title() );
        wc_back_link( __( 'Volver a métodos de pago', 'wc-bancard' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );
        echo '</h2>';
        echo wp_kses_post( wpautop( $this->get_method_description() ) );
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';
    }

    public function thankyou_page( $order_id ) {
        // Mensajes contextualizados a resultado
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }
        $is_bancard = $order->get_payment_method() === $this->id;
        if ( ! $is_bancard ) {
            return;
        }
        $has_error = (bool) get_post_meta( $order_id, WC_BANCARD_META_ERROR, true );
        if ( $has_error && ! $order->is_paid() ) {
            echo '<p class="woocommerce-notice woocommerce-notice--error">' . esc_html__( 'Unfortunately, an error has occurred while completing the payment.', 'wc-bancard' ) . '</p>';
        } elseif ( $order->is_paid() ) {
            echo '<p class="woocommerce-notice woocommerce-notice--success">' . esc_html__( 'Payment has been received. Thank you for your purchase!', 'wc-bancard' ) . '</p>';
        }
    }

    /**
     * Process payment: create Bancard operation and redirect.
     */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wc_add_notice( __( 'Invalid order.', 'wc-bancard' ), 'error' );
            return array( 'result' => 'fail' );
        }

        WC_Bancard_Logger::info( sprintf( 'process_payment: order #%d, total: %s', $order_id, $order->get_total() ) );

        // Prepare amount in PYG
        $store_currency = get_woocommerce_currency();
        $currency = 'PYG';
        $total = (float) $order->get_total();
        if ( $store_currency !== 'PYG' ) {
            $rate = wc_bancard_get_exchange_rate( $order );
            if ( ! $rate || $rate <= 0 ) {
                WC_Bancard_Logger::error( 'process_payment: exchange rate not configured' );
                wc_add_notice( __( 'Bancard: exchange rate not configured.', 'wc-bancard' ), 'error' );
                return array( 'result' => 'fail' );
            }
            $total = $total * (float) $rate;
        }

        $args = array(
            'amount'      => $total,
            'currency'    => $currency,
            'description' => sprintf( __( 'Order #%d', 'wc-bancard' ), $order_id ),
            'return_url'  => $order->get_checkout_order_received_url(),
            'cancel_url'  => $order->get_checkout_order_received_url(),
        );

        $api_resp = WC_Bancard_API::single_buy( $order, $args );

        if ( 'success' !== $api_resp['status'] ) {
            $error_msg = isset( $api_resp['error'] ) ? $api_resp['error'] : 'unknown';
            WC_Bancard_Logger::error( 'process_payment failed for order #' . $order_id . ': ' . $error_msg );
            wc_add_notice( __( 'Bancard: failed to create payment.', 'wc-bancard' ), 'error' );
            return array( 'result' => 'fail' );
        }

        $process_id = $api_resp['process_id'];
        update_post_meta( $order_id, WC_BANCARD_META_PROCESS_ID, $process_id );
        WC_Bancard_Logger::info( sprintf( 'process_payment: order #%d, process_id: %s, redirecting to Bancard', $order_id, $process_id ) );

        return array(
            'result'   => 'success',
            'redirect' => WC_Bancard_API::payment_redirect_url( $process_id ),
        );
    }

    /**
     * Process refund by triggering Bancard rollback.
     *
     * @param int $order_id
     * @param float $amount
     * @param string $reason
     * @return bool|WP_Error
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return new WP_Error( 'invalid_order', __( 'Invalid order.', 'wc-bancard' ) );
        }

        $resp = WC_Bancard_API::rollback( $order );
        if ( 'success' === $resp['status'] ) {
            $order->add_order_note( sprintf( __( 'Bancard rollback executed. Reason: %s', 'wc-bancard' ), $reason ) );
            update_post_meta( $order_id, WC_BANCARD_META_ROLLBACKED, true );
            return true;
        }

        $msg = isset( $resp['error'] ) ? $resp['error'] : __( 'Unknown error', 'wc-bancard' );
        return new WP_Error( 'bancard_refund_failed', $msg );
    }
}
