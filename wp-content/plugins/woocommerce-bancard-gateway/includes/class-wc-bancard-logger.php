<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Bancard_Logger {

    /**
     * Get shared logger instance.
     *
     * @return WC_Logger
     */
    public static function get_logger() {
        if ( function_exists( 'wc_get_logger' ) ) {
            return wc_get_logger();
        }
        // Fallback simple logger
        return new class {
            public function log( $level, $message, $context = array() ) {
                error_log( '[' . strtoupper( $level ) . "] WC_BANCARD: " . $message );
            }
        };
    }

    /**
     * Log with level.
     */
    public static function log( $level, $message, $context = array() ) {
        $logger = self::get_logger();
        if ( is_array( $context ) ) {
            $context['source'] = isset( $context['source'] ) ? $context['source'] : 'bancard-gateway';
        }
        if ( method_exists( $logger, 'log' ) ) {
            $logger->log( $level, $message, $context );
        } else {
            // Fallback
            error_log( '[' . strtoupper( $level ) . "] WC_BANCARD: " . $message );
        }
    }

    public static function info( $message, $context = array() ) { self::log( 'info', $message, $context ); }
    public static function warning( $message, $context = array() ) { self::log( 'warning', $message, $context ); }
    public static function error( $message, $context = array() ) { self::log( 'error', $message, $context ); }
}
