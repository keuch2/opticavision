<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Meta keys
if ( ! defined( 'WC_BANCARD_META_PROCESS_ID' ) ) {
    define( 'WC_BANCARD_META_PROCESS_ID', '_bancard_process_id' );
}
if ( ! defined( 'WC_BANCARD_META_ROLLBACKED' ) ) {
    define( 'WC_BANCARD_META_ROLLBACKED', '_bancard_rollbacked' );
}
if ( ! defined( 'WC_BANCARD_META_ERROR' ) ) {
    define( 'WC_BANCARD_META_ERROR', '_bancard_error' );
}

/**
 * Get gateway settings stored by WooCommerce for gateway ID 'bancard'.
 * Falls back to constants if defined in wp-config.php.
 *
 * @return array
 */
function wc_bancard_get_settings() {
    $settings = get_option( 'woocommerce_bancard_settings', array() );

    // Allow overriding via wp-config.php constants
    if ( defined( 'BANCARD_PUBLIC_KEY' ) && BANCARD_PUBLIC_KEY ) {
        $settings['public_key'] = BANCARD_PUBLIC_KEY;
    }
    if ( defined( 'BANCARD_PRIVATE_KEY' ) && BANCARD_PRIVATE_KEY ) {
        $settings['private_key'] = BANCARD_PRIVATE_KEY;
    }
    if ( defined( 'BANCARD_ENV' ) && BANCARD_ENV ) {
        $settings['environment'] = BANCARD_ENV; // 'production' or 'sandbox'
    }
    if ( defined( 'BANCARD_EXCHANGE_RATE' ) && BANCARD_EXCHANGE_RATE ) {
        $settings['exchange_rate'] = BANCARD_EXCHANGE_RATE;
    }

    return $settings;
}

/**
 * Whether sandbox is enabled.
 */
function wc_bancard_is_sandbox( $settings = null ) {
    $settings = is_array( $settings ) ? $settings : wc_bancard_get_settings();
    $env = isset( $settings['environment'] ) ? strtolower( $settings['environment'] ) : 'production';
    return ( $env !== 'production' );
}

/**
 * Exchange rate helper. If store currency is not PYG, we convert.
 * You can override via filter 'wc_bancard_exchange_rate'.
 */
function wc_bancard_get_exchange_rate( $order = null ) {
    $settings = wc_bancard_get_settings();
    $rate = isset( $settings['exchange_rate'] ) && is_numeric( $settings['exchange_rate'] ) ? (float) $settings['exchange_rate'] : 1.0;
    return (float) apply_filters( 'wc_bancard_exchange_rate', $rate, $order );
}

/**
 * Format amount as string with 2 decimals and '.' decimal separator for Bancard.
 */
function wc_bancard_format_amount( $amount ) {
    return number_format( (float) $amount, 2, '.', '' );
}
